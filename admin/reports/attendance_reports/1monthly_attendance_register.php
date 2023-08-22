<?php
require_once('../../../classes/class.users.php');
require_once('../../../classes/class.validation.php');
require_once('../../../classes/class.authentication.php');

require_once("../../../classes/school_administration/class.academic_year_months.php");
require_once('../../../classes/school_administration/class.classes.php');
require_once('../../../classes/school_administration/class.students.php');
require_once('../../../classes/school_administration/class.student_details.php');
require_once("../../../classes/school_administration/class.class_attendence.php");

require_once('../../../classes/class.helpers.php');
require_once('../../../classes/class.ui_helpers.php');

require_once('../../../includes/global_defaults.inc.php');

//1. RECHECK IF THE USER IS VALID //
try
{
	$AuthObject = new ApplicationAuthentication;
	$LoggedUser = new User(0, $AuthObject->CheckValidUser());
}

// THIS CATCH BLOCK BUBBLES THE EXCEPTION TO THE BUILT IN 'Exception' CLASS IF THERE ARE ANY UNCAUGHT ERRORS //
catch (ApplicationAuthException $e)
{
	header('location:../../unauthorized_login_admin.php');
	exit;
}
catch (Exception $e)
{
	header('location:../../unauthorized_login_admin.php');
	exit;
}
// END OF 1. //

if ($LoggedUser->HasPermissionForTask(TASK_LIST_MONTHLY_ATTENDANCE_REGISTER) !== true)
{
	header('location:../../unauthorized_login_admin.php');
	exit;
}

$MonthlyAttendanceList = array();
$AllWorkingDays = array();
$StudentsList = array();

$AllAcademicYearMonths = array();
$AllAcademicYearMonths = AcademicYearMonth::GetMonthsByFeePriority('priority');

$AllClasses = array();
$AllClasses = AddedClass::GetActiveClasses();

$ClassSectionsList = array();

$HasErrors = false;

$Clean = array();
$Clean['Process'] = 0;

$Clean['ClassID'] = 0;
$Clean['ClassID'] = key($AllClasses);

$ClassSectionsList = AddedClass::GetClassSections($Clean['ClassID']);

$Clean['ClassSectionID'] = 0;
$Clean['ClassSectionID'] = key($ClassSectionsList);

$Clean['MonthlyAttedanceRegisterMonth'] = 0;

foreach ($AllAcademicYearMonths as $AcademicYearMonthID => $AcademicYearMonthDetails) 
{
    if ($AcademicYearMonthDetails['MonthShortName'] == date('M')) 
    {
        $Clean['MonthlyAttedanceRegisterMonth'] = $AcademicYearMonthID;
    }
}

$Clean['Process'] = 7;

if (isset($_POST['hdnProcess']))
{
    $Clean['Process'] = (int) $_POST['hdnProcess'];
}
switch ($Clean['Process'])
{
    case 7:
        if (isset($_POST['drdClassID']))
        {
            $Clean['ClassID'] = (int) $_POST['drdClassID'];
        }

        if (isset($_POST['drdClassSectionID']))
        {
            $Clean['ClassSectionID'] = (int) $_POST['drdClassSectionID'];
        }

        if (isset($_POST['drdMonthlyAttedanceRegisterMonth']))
        {
            $Clean['MonthlyAttedanceRegisterMonth'] = strip_tags(trim($_POST['drdMonthlyAttedanceRegisterMonth']));
        }

        $NewRecordValidator = new Validator();

        $NewRecordValidator->ValidateInSelect($Clean['ClassID'], $AllClasses, 'Unknown error, please try again');

        if ($NewRecordValidator->HasNotifications())
        {
            $HasErrors = true;
            break;
        }

        $ClassSectionsList = AddedClass::GetClassSections($Clean['ClassID']);
        $NewRecordValidator->ValidateInSelect($Clean['ClassSectionID'], $ClassSectionsList, 'Unknown error, please try again.');

        $NewRecordValidator->ValidateInSelect($Clean['MonthlyAttedanceRegisterMonth'], $AllAcademicYearMonths, 'Unknown error, please try again.');

        if ($NewRecordValidator->HasNotifications())
        {
            $HasErrors = true;
            break;
        }

        $StartDate = Helpers::GetStartDateOfTheMonth($AllAcademicYearMonths[$Clean['MonthlyAttedanceRegisterMonth']]['MonthShortName']);

        $WorkingDayStartDate = date('Y-m-d', strtotime($StartDate));
        $WorkingDaysEndDate = date('Y-m-t', strtotime($StartDate));

        $AllWorkingDays = Helpers::GetClassWorkingDays($WorkingDayStartDate, $WorkingDaysEndDate, $Clean['ClassSectionID']);

        $StudentsList = StudentDetail::GetStudentsByClassSectionID($Clean['ClassSectionID']);
		
		if (isset($_POST['report_submit']) && $_POST['report_submit'] == 2)
        {
            require_once('../../excel/monthly_attendance_register_download_xls.php');
        }
    break;
}

require_once('../../html_header.php');
?>
<title>Monthly Attendance Register</title>
<!-- DataTables CSS -->
<link href="/admin/vendor/datatables-plugins/dataTables.bootstrap.css" rel="stylesheet">

<!-- DataTables Responsive CSS -->
<link href="/admin/vendor/datatables-responsive/dataTables.responsive.css" rel="stylesheet">
</head>

<body>

    <div id="wrapper">
    	<!-- Navigation -->
        <nav class="navbar navbar-default navbar-static-top" role="navigation" style="margin-bottom: 0">
<?php 
			require_once('../../site_header.php');
			require_once('../../left_navigation_menu.php');
?>                    
            <!-- /.navbar-static-side -->
        </nav>

        <div id="page-wrapper">
            <div class="row">
                <div class="col-lg-12">
                    <h1 class="page-header">Filters</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
            <form class="form-horizontal" name="frmMonthlyAttendanceRegister" action="monthly_attendance_register.php" method="post">
                <div class="panel panel-default" id="accordion">
                    <div class="panel-heading">
						<strong>Filters</strong>
                    </div>
                    <div id="collapseOne" class="panel-collapse collapse in">
                        <div class="panel-body">
<?php
                            if ($HasErrors == true)
                            {
                                echo $NewRecordValidator->DisplayErrors();
                            }
?>
                            <div class="form-group">
                                <label for="ClassList" class="col-lg-2 control-label">Class List</label>
                                <div class="col-lg-4">
                                    <select class="form-control"  name="drdClassID" id="ClassID">
<?php
                                        foreach ($AllClasses as $ClassID => $ClassesName)
                                        {
?>
                                            <option <?php echo ($ClassID == $Clean['ClassID'] ? 'selected="selected"' : ''); ?> value="<?php echo $ClassID; ?>"><?php echo $ClassesName; ?></option>
<?php
                                        }
?>
                                    </select>
                                </div>
                                <label for="SectionID" class="col-lg-2 control-label">Section List</label>
                                <div class="col-lg-4">
                                    <select class="form-control" name="drdClassSectionID" id="SectionID">
                                        <option value="0">Select Section</option>
<?php
                                        if (is_array($ClassSectionsList) && count($ClassSectionsList) > 0)
                                        {
                                            foreach ($ClassSectionsList as $ClassSectionID => $SectionName) 
                                            {
                                                echo '<option ' . ($Clean['ClassSectionID'] == $ClassSectionID ? 'selected="selected"' : '') . ' value="' . $ClassSectionID . '">' . $SectionName . '</option>' ;
                                            }
                                        }
?>
                                    </select>
                                </div>
                            </div> 
                            <div class="form-group">
                                <label for="Month" class="col-lg-2 control-label">Month</label>
                                <div class="col-lg-4">
                                    <select class="form-control" name="drdMonthlyAttedanceRegisterMonth" id="MonthlyAttedanceRegisterMonth">
<?php
                                    foreach ($AllAcademicYearMonths as $AcademicYearMonthID => $AcademicYearMonthDetails) 
                                    {
?>
                                        <option <?php echo ($Clean['MonthlyAttedanceRegisterMonth'] == $AcademicYearMonthID ? 'selected="selected"' : '');?> value="<?php echo $AcademicYearMonthID; ?>"><?php echo $AcademicYearMonthDetails['MonthName']; ?></option>
<?php
                                    }
?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="col-lg-offset-2 col-lg-10">
                                    <input type="hidden" name="report_submit" id="get_excel" value="0" />
                                    <input type="hidden" name="hdnProcess" value="7" />
                                    <button type="submit" class="btn btn-primary" id = "SubmitSearch">Search</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    </div>
            </form>
            <!-- /.row -->
            <div class="row">
                <div class="col-lg-12">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <strong>Monthly Attendance Register</strong>
                        </div>
                        <!-- /.panel-heading -->
                        <div class="panel-body">
                            <div>
                                <div class="row">
                                    <div class="col-lg-12" style="text-align: right;">
                                        <div class="print-btn-container">
                                            <button id="PrintButton" type="submit" class="btn btn-primary">Print</button>
                                            <button id="" onclick="$('#get_excel').val(2); $('#SubmitSearch').click();$('#get_excel').val(0);" type="submit" class="btn btn-primary">Export to Excel</button>
                                        </div>
                                    </div>
                                </div>
                                <div class="row" id="RecordTableHeading">
                                    <div class="col-lg-12">
                                        <div class="report-heading-container"><strong>Monthly Attendance Register on <?php echo date('d-m-Y h:i A'); ?></strong></div>
                                    </div>
                                </div>
                                <div class="row" id="RecordTable">
                                    <div class="col-lg-12" style="overflow: auto;">
                                        <table width="100%" class="table table-striped table-bordered table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Student Name</th>
<?php
                                                    $Date = $WorkingDayStartDate;

                                                    while (strtotime($Date) <= strtotime($WorkingDaysEndDate)) 
                                                    {
                                                        echo'<td>'. date('d', strtotime($Date)) .'</td>';

                                                        $Date = date('Y-m-d', strtotime('+1 Day', strtotime($Date)));
                                                    }
?>
                                                    <th>WD</th>
                                                    <th>PD</th>
                                                </tr>
                                            </thead>
                                            <tbody id="TableData">
<?php
                                            foreach ($StudentsList as $StudentID => $StudentDetails) 
                                            {
                                                $Date = $WorkingDayStartDate;
                                                
                                                echo '<tr>';
?>
                                                <td><?php echo $StudentDetails['FirstName'] . ' ' . $StudentDetails['LastName']; ?></td>
<?php
                                                $TotalWorkingDays = 0;
                                                $StudentTotalMonthlyPresent = 0;
                                                
                                                while (strtotime($Date) <= strtotime($WorkingDaysEndDate)) 
                                                {
                                                    if (date('N', strtotime($Date)) > 6)
                                                    {
                                                        $Date = date('Y-m-d', strtotime('+1 Day', strtotime($Date)));
                                                        echo '<td>S</td>';
                                                        continue;
                                                    }
                                                    else if (strtotime($Date) > time())
                                                    {
                                                        $Date = date('Y-m-d', strtotime('+1 Day', strtotime($Date)));
                                                        echo '<td></td>';
                                                        continue;
                                                    }
                                                    
                                                    $TotalWorkingDays++;
                                                    
                                                    $MonthlyAttendanceList = ClassAttendence::GetClassWiseMonthlyAttendance($WorkingDayStartDate, $WorkingDaysEndDate, $Clean['ClassSectionID'], $StudentID);
                                                    $Counter = 0;
                                                    
                                                    $AttendanceStatus = '<span style="color:red;">A</span>';
                                                    /*echo '<pre>';
                                                    var_dump($MonthlyAttendanceList);
                                                    echo $StudentDetails['FirstName'] . ' ' . $StudentDetails['LastName'];
                                                    echo '</pre>';*/
                                                    foreach ($MonthlyAttendanceList as $ClassAttendenceDetailID => $MonthlyAttendanceDetails)
                                                    {          
                                                        if ($Date == $MonthlyAttendanceDetails['AttendenceDate'] && $MonthlyAttendanceDetails['Status'] == 'Present') 
                                                        {
                                                            $StudentTotalMonthlyPresent++;
                                                            
                                                            $Counter = 1;
                                                            
                                                            $AttendanceStatus = '<span style="color:green;">P</span>';
                                                        }
                                                    }

                                                    if ($Counter == 0) 
                                                    {
                                                        if (!Helpers::GetIsClassAttendanceDateIsWorkingDate($WorkingDayStartDate, $WorkingDaysEndDate, $Clean['ClassSectionID'], $Date))
                                                        {
                                                            $TotalWorkingDays--;
                                                            $AttendanceStatus = 'H';
                                                        }
                                                    }
                                                    
                                                    echo '<td>' . $AttendanceStatus . '</td>';

                                                    $Date = date('Y-m-d', strtotime('+1 Day', strtotime($Date)));
                                                }

                                                echo '<td>' . $TotalWorkingDays . '</td>';
                                                echo '<td>' . $StudentTotalMonthlyPresent . '</td>';
                                                echo '</tr>';
                                            }
?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <!-- /.table-responsive -->
                        </div>
                        <!-- /.panel-body -->
                    </div>
                    <!-- /.panel -->
                </div>
                <!-- /.col-lg-12 -->
            </div>
        </div>
        <!-- /#page-wrapper -->

    </div>
    <!-- /#wrapper -->
<?php
require_once('../../footer.php');
?>
    <!-- DataTables JavaScript -->
    <script src="/admin/vendor/datatables/js/jquery.dataTables.min.js"></script>
    <script src="/admin/vendor/datatables-plugins/dataTables.bootstrap.min.js"></script>
	<script src="/admin/vendor/datatables-responsive/dataTables.responsive.js"></script>
	
	<!-- JavaScript To Print A Report -->
    <script src="/admin/js/print-report.js"></script>
	
	<script type="text/javascript">
<?php
    if (isset($_GET['POMode']))
    {
        $PageOperationResultMessage = '';
        $PageOperationResultMessage = UIHelpers::GetPageOperationResultMessage($_GET['POMode']);

        if ($PageOperationResultMessage != '')
        {
            echo 'alert("' . $PageOperationResultMessage . '");';
        }
    }
?>
	$(document).ready(function() 
    {
        $('#DataTableRecords').DataTable({
            responsive: true,
            bPaginate: false,
            bSort: false,
            searching: false, 
            info: false
        });

        $('#ClassID').change(function()
        {
            var ClassID = parseInt($(this).val());
            
            if (ClassID <= 0)
            {
                $('#SectionID').html('<option value="0">Select Section</option>');
                return false;
            }
            
            $.post("/xhttp_calls/get_sections_by_classs.php", {SelectedClassID:ClassID}, function(data)
            {
                ResultArray = data.split("|*****|");
                
                if (ResultArray[0] == 'error')
                {
                    alert (ResultArray[1]);
                    $('#SectionID').html('<option value="0">-Select-</option>');
                    return false;
                }
                else
                {
                    $('#SectionID').html(ResultArray[1]);
                }
             });
        });
	});
    </script>
</body>
</html>
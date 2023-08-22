<?php
require_once('../../../classes/class.users.php');
require_once('../../../classes/class.validation.php');
require_once('../../../classes/class.authentication.php');

require_once("../../../classes/school_administration/class.academic_year_months.php");
require_once('../../../classes/school_administration/class.staff_attendence.php');
require_once("../../../classes/school_administration/class.branch_staff.php");
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

if ($LoggedUser->HasPermissionForTask(TASK_LIST_STAFF_MONTHLY_ATTENDANCE_REGISTER) !== true)
{
	header('location:../../unauthorized_login_admin.php');
	exit;
}

$MonthlyAttendanceList = array();
$AllWorkingDays = array();
$StaffCategoryList = array('Teaching' => 'Teaching Staff', 'NonTeaching' => 'Non Teaching Staff');

$AllAcademicYearMonths = array();
$AllAcademicYearMonths = AcademicYearMonth::GetMonthsByFeePriority('priority');                                                                                                        

$BranchStaffList = array();

$HasErrors = false;

$Clean = array();

$Clean['StaffCategory'] = 'Teaching';

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
        if (isset($_POST['drdStaffCategory']))
        {
            $Clean['StaffCategory'] = strip_tags(trim($_POST['drdStaffCategory']));
        }

        if (isset($_POST['drdMonthlyAttedanceRegisterMonth']))
        {
            $Clean['MonthlyAttedanceRegisterMonth'] = strip_tags(trim($_POST['drdMonthlyAttedanceRegisterMonth']));
        }

        $SearchRecordValidator = new Validator();

        $SearchRecordValidator->ValidateInSelect($Clean['StaffCategory'], $StaffCategoryList, 'Unknown error, please try again.');

        $SearchRecordValidator->ValidateInSelect($Clean['MonthlyAttedanceRegisterMonth'], $AllAcademicYearMonths, 'Unknown error, please try again.');

        if ($SearchRecordValidator->HasNotifications())
        {
            $HasErrors = true;
            break;
        }

        $StartDate = Helpers::GetStartDateOfTheMonth($AllAcademicYearMonths[$Clean['MonthlyAttedanceRegisterMonth']]['MonthShortName']);

        $WorkingDayStartDate = date('Y-m-d', strtotime($StartDate));
        $WorkingDaysEndDate = date('Y-m-t', strtotime($StartDate));

        $AllWorkingDays = Helpers::GetBranchStaffWorkingDays($WorkingDayStartDate, $WorkingDaysEndDate, $Clean['StaffCategory']);
  
        $BranchStaffList = BranchStaff::GetActiveBranchStaff($Clean['StaffCategory']);
    break;
}

require_once('../../html_header.php');
?>
<title>Staff Monthly Attendance Register</title>
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
            <form class="form-horizontal" name="frmMonthlyAttendanceRegister" action="staff_monthly_attendance_register.php" method="post">
                <div class="panel panel-default" id="accordion">
                    <div class="panel-heading">
						<strong>Filters</strong>
                    </div>
                    <div id="collapseOne" class="panel-collapse collapse in">
                        <div class="panel-body">
<?php
                            if ($HasErrors == true)
                            {
                                echo $SearchValidator->DisplayErrors();
                            }
?>
                            <div class="form-group">
                                <label for="StaffCategory" class="col-lg-2 control-label">Staff Category</label>
                                <div class="col-lg-4">
                                    <select class="form-control" id="StaffCategory" name="drdStaffCategory">
<?php
                                    foreach ($StaffCategoryList as $StaffCategory => $StaffCategoryName)
                                    {
?>
                                        <option value="<?php echo $StaffCategory; ?>" <?php echo ($StaffCategory == $Clean['StaffCategory'] ? 'selected="selected"' : ''); ?>><?php echo $StaffCategoryName; ?></option>
<?php
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
                                    <input type="hidden" name="hdnProcess" value="7" />
                                    <button type="submit" class="btn btn-primary">Search</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    </div>
            </form>
<?php
    if ($Clean['Process'] == 7 && $HasErrors != true) 
    {
?>
        <!-- /.row -->
            <div class="row">
                <div class="col-lg-12">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <strong>Staff Monthly Attendance Register</strong>
                        </div>
                        <!-- /.panel-heading -->
                        <div class="panel-body">
                            <div>
                                <div class="row">
                                    <div class="col-lg-12" style="text-align: right;">
                                        <div class="print-btn-container"><button id="PrintButton" type="submit" class="btn btn-primary">Print</button></div>
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
                                                    <th>Branch Staff Name</th>
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
                                            foreach ($BranchStaffList as $BranchStaffID => $BranchStaffDetails) 
                                            {
                                                $Date = $WorkingDayStartDate;
                                                
                                                echo '<tr>';
?>
                                                <td><?php echo $BranchStaffDetails['FirstName'] . ' ' . $BranchStaffDetails['LastName']; ?></td>
<?php
                                                $TotalWorkingDays = 0;
                                                $StaffTotalMonthlyPresent = 0;
                                                
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
                                                    
                                                    $MonthlyAttendanceList = StaffAttendence::GetMonthlyAttendance($BranchStaffID, $Date, date('Y-m-d', strtotime($WorkingDaysEndDate)));
                                                    $Counter = 0;
                                                    
                                                    $AttendanceStatus = '<span style="color:red;">A</span>';

                                                    foreach ($MonthlyAttendanceList as $ClassAttendenceDetailID => $MonthlyAttendanceDetails)
                                                    {          
                                                        if ($Date == $MonthlyAttendanceDetails['AttendenceDate']) 
                                                        {
                                                            $StaffTotalMonthlyPresent++;
                                                            
                                                            $Counter = 1;
                                                            
                                                            $AttendanceStatus = '<span style="color:green;">P</span>';
                                                        }
                                                    }

                                                    if ($Counter == 0) 
                                                    {
                                                        if (!Helpers::GetIsBranchStaffAttendanceDateIsWorkingDay($WorkingDayStartDate, $WorkingDaysEndDate, $Clean['StaffCategory'], $Date))
                                                        {
                                                            $TotalWorkingDays--;
                                                            $AttendanceStatus = 'H';
                                                        }
                                                    }
                                                    
                                                    echo '<td>' . $AttendanceStatus . '</td>';

                                                    $Date = date('Y-m-d', strtotime('+1 Day', strtotime($Date)));
                                                }

                                                echo '<td>' . $TotalWorkingDays . '</td>';
                                                echo '<td>' . $StaffTotalMonthlyPresent . '</td>';
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
<?php
    }
?>
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
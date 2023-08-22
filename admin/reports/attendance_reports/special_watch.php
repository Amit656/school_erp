<?php
require_once('../../../classes/class.users.php');
require_once('../../../classes/class.validation.php');
require_once('../../../classes/class.authentication.php');

require_once("../../../classes/school_administration/class.academic_years.php");
require_once('../../../classes/school_administration/class.staff_attendence.php');
require_once("../../../classes/school_administration/class.branch_staff.php");
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

if ($LoggedUser->HasPermissionForTask(TASK_LIST_SPECIAL_WATCH) !== true)
{
	header('location:../../unauthorized_login_admin.php');
	exit;
}

$UserTypeList = array('Student' => 'Student', 'BranchStaff' => 'Branch Staff');
$StaffCategoryList = array('Teaching' => 'Teaching Staff', 'NonTeaching' => 'Non Teaching Staff');

$AcademicYearName = '';
$AcademicYearID = AcademicYear::GetCurrentAcademicYear($AcademicYearName);

$AllClasses = array();
$AllClasses = AddedClass::GetActiveClasses();

$BranchStaffList = array();
$ClassSectionsList = array();
$StudentsList = array();

$AllAttendenceDetails = array();
    
$HasErrors = false;

$Clean = array();

$Clean['UserType'] = 'Student';

$Clean['AcademicYearID'] = $AcademicYearID;

$Clean['StaffCategory'] = '';

$Clean['BranchStaffID'] = 0;

$Clean['ClassID'] = 0;
$Clean['ClassSectionID'] = 0;
$Clean['StudentID'] = 0;

$Clean['AttendaneDateFrom'] = '';
$Clean['AttendaneDateTo'] = '';

$Clean['AttendanePercentRangeFrom'] = '';
$Clean['AttendanePercentRangeTo'] = '';

// paging and sorting variables start here  //
$Clean['CurrentPage'] = 1;
$TotalPages = 0;

$Start = 0;
$Limit = 50;
// end of paging variables //

$TotalWorkingDays = 0;

$Clean['Process'] = 0;

if (isset($_GET['hdnProcess']))
{
    $Clean['Process'] = (int) $_GET['hdnProcess'];
}
elseif (isset($_GET['Process']))
{
    $Clean['Process'] = (int) $_GET['Process'];
}
switch ($Clean['Process'])
{
    case 7:
        if (isset($_GET['rdbUserType'])) 
        {
            $Clean['UserType'] = strip_tags(trim($_GET['rdbUserType']));
        }
        elseif (isset($_GET['UserType'])) 
        {
            $Clean['UserType'] = strip_tags(trim($_GET['UserType']));
        }

        if (isset($_GET['drdClassID']))
        {
            $Clean['ClassID'] = (int) $_GET['drdClassID'];
        }
        elseif (isset($_GET['ClassID']))
        {
            $Clean['ClassID'] = (int) $_GET['ClassID'];
        }

        if (isset($_GET['drdClassSectionID']))
        {
            $Clean['ClassSectionID'] = (int) $_GET['drdClassSectionID'];
        }
        elseif (isset($_GET['ClassSectionID']))
        {
            $Clean['ClassSectionID'] = (int) $_GET['ClassSectionID'];
        }

        if (isset($_GET['drdStudent']))
        {
            $Clean['StudentID'] = (int) $_GET['drdStudent'];
        }
        elseif (isset($_GET['drdStudent']))
        {
            $Clean['StudentID'] = (int) $_GET['drdStudent'];
        }

        if (isset($_GET['drdStaffCategory']))
        {
            $Clean['StaffCategory'] = strip_tags(trim($_GET['drdStaffCategory']));
        }
        elseif (isset($_GET['StaffCategory']))
        {
            $Clean['StaffCategory'] = strip_tags(trim($_GET['StaffCategory']));
        }

        if (isset($_GET['drdBranchStaff']))
        {
            $Clean['BranchStaffID'] = (int) $_GET['drdBranchStaff'];
        }
        elseif (isset($_GET['BranchStaff']))
        {
            $Clean['BranchStaffID'] = (int) $_GET['BranchStaff'];
        }
        
        #
        if (isset($_GET['txtAttendaneDateFrom'])) 
        {
            $Clean['AttendaneDateFrom'] = strip_tags(trim($_GET['txtAttendaneDateFrom']));
        }
        elseif (isset($_GET['AttendaneDateFrom'])) 
        {
            $Clean['AttendaneDateFrom'] = strip_tags(trim($_GET['AttendaneDateFrom']));
        }

        if (isset($_GET['txtAttendaneDateTo'])) 
        {
            $Clean['AttendaneDateTo'] = strip_tags(trim($_GET['txtAttendaneDateTo']));
        }
        elseif (isset($_GET['AttendaneDateTo'])) 
        {
            $Clean['AttendaneDateTo'] = strip_tags(trim($_GET['AttendaneDateTo']));
        }
        #

        if (isset($_GET['txtAttendanePercentRangeFrom'])) 
        {
            $Clean['AttendanePercentRangeFrom'] = strip_tags(trim($_GET['txtAttendanePercentRangeFrom']));
        }
        elseif (isset($_GET['AttendanePercentRangeFrom'])) 
        {
            $Clean['AttendanePercentRangeFrom'] = strip_tags(trim($_GET['AttendanePercentRangeFrom']));
        }

        if (isset($_GET['txtAttendanePercentRangeTo'])) 
        {
            $Clean['AttendanePercentRangeTo'] = strip_tags(trim($_GET['txtAttendanePercentRangeTo']));
        }
        elseif (isset($_GET['AttendanePercentRangeTo'])) 
        {
            $Clean['AttendanePercentRangeTo'] = strip_tags(trim($_GET['AttendanePercentRangeTo']));
        }

        $SearchRecordValidator = new Validator();

        $SearchRecordValidator->ValidateInSelect($Clean['UserType'], $UserTypeList, 'Unknown error, please try again.');

        if ($Clean['UserType'] == 'Student') 
        {   
            if ($Clean['ClassID'] != 0) 
            {
                $SearchRecordValidator->ValidateInSelect($Clean['ClassID'], $AllClasses, 'Unknown error, please try again.');
            }

            if ($SearchRecordValidator->HasNotifications())
            {
                $HasErrors = true;
                break;
            }

            $ClassSectionsList = AddedClass::GetClassSections($Clean['ClassID']);

            if ($Clean['ClassSectionID'] != 0) 
            {
                $SearchRecordValidator->ValidateInSelect($Clean['ClassSectionID'], $ClassSectionsList, 'Unknown error, please try again.');
            }
            
            if ($Clean['AttendaneDateFrom'] != '' && $Clean['AttendaneDateTo'] != '')
            {
                $SearchRecordValidator->ValidateDate($Clean['AttendaneDateFrom'], 'Please enter a valid date range from.');
                $SearchRecordValidator->ValidateDate($Clean['AttendaneDateTo'], 'Please enter a valid date range to.');
            }
            
            if ($SearchRecordValidator->HasNotifications())
            {
                $HasErrors = true;
                break;
            }

            $StudentsList = StudentDetail::GetStudentsByClassSectionID($Clean['ClassSectionID'], 'Active', $Clean['AcademicYearID']);

            if ($Clean['StudentID'] != 0) 
            {
                $SearchRecordValidator->ValidateInSelect($Clean['StudentID'], $StudentsList, 'Please select a valid student.');
            }
        }
        elseif ($Clean['UserType'] == 'BranchStaff') 
        {   

            if ($Clean['StaffCategory'] != '') 
            {   
                $SearchRecordValidator->ValidateInSelect($Clean['StaffCategory'], $StaffCategoryList, 'Unknown error, please try again.');

                if ($SearchRecordValidator->HasNotifications())
                {
                    $HasErrors = true;
                    break;
                }

                $BranchStaffList = BranchStaff::GetActiveBranchStaff($Clean['StaffCategory']);
            }

            if ($Clean['BranchStaffID'] != 0) 
            {
                $SearchRecordValidator->ValidateInSelect($Clean['BranchStaffID'], $BranchStaffList, 'Unknown error, please try again.');
            }
        } 

        if ($Clean['AttendanePercentRangeFrom'] != '') 
        {
            $SearchRecordValidator->ValidateInteger($Clean['AttendanePercentRangeFrom'], 'Attendane percent Range from should be integer.', 0);
        }

        if ($Clean['AttendanePercentRangeTo'] != '') 
        {
            $SearchRecordValidator->ValidateInteger($Clean['AttendanePercentRangeTo'], 'Attendane percent Range To should be integer.', 0);
        }

        if ($SearchRecordValidator->HasNotifications())
        {
            $HasErrors = true;
            break;
        }

        //set record filters
        $Filters['UserType'] = $Clean['UserType'];
        $Filters['AcademicYearID'] = $Clean['AcademicYearID'];
        $Filters['ClassID'] = $Clean['ClassID'];
        $Filters['ClassSectionID'] = $Clean['ClassSectionID'];
        $Filters['StudentID'] = $Clean['StudentID'];
        $Filters['StaffCategory'] = $Clean['StaffCategory'];
        $Filters['BranchStaffID'] = $Clean['BranchStaffID'];
        $Filters['AttendanePercentRangeFrom'] = $Clean['AttendanePercentRangeFrom'];
        $Filters['AttendanePercentRangeTo'] = $Clean['AttendanePercentRangeTo'];
        
        $Filters['AttendanePercentRangeFrom'] = $Clean['AttendanePercentRangeFrom'];
        $Filters['AttendanePercentRangeTo'] = $Clean['AttendanePercentRangeTo'];
    
        if ($Clean['UserType'] == 'Student') 
        {
            ClassAttendence::SearchClassAttendence($TotalRecords, true, $Filters);
        }
        elseif ($Clean['UserType'] == 'BranchStaff') 
        {
            StaffAttendence::SearchStaffAttendence($TotalRecords, true, $Filters);
        }      
  
        if ($TotalRecords > 0)
        {
            // Paging and sorting calculations start here.
            $TotalPages = (($TotalRecords % $Limit) == 0) ? $TotalRecords / $Limit : floor($TotalRecords / $Limit) + 1;

            if (isset($_GET['CurrentPage']))
            {
                $Clean['CurrentPage'] = (int) $_GET['CurrentPage'];
            }

            if ($Clean['CurrentPage'] <= 0)
            {
                $Clean['CurrentPage'] = 1;
            }
            elseif ($Clean['CurrentPage'] > $TotalPages)
            {
                $Clean['CurrentPage'] = $TotalPages;
            }

            if ($Clean['CurrentPage'] > 1)
            {
                $Start = ($Clean['CurrentPage'] - 1) * $Limit;
            }
            
            // end of Paging and sorting calculations.
            // now get the actual  records

            $CurrentAcademicYearName = '';
            $StartDate = '';
            $EndDate = '';

            AcademicYear::GetCurrentAcademicYear($CurrentAcademicYearName, $StartDate, $EndDate);

            $WorkingDaysStartDate = $StartDate;
            $WorkingDaysEndDate = date('Y-m-d');

            if ($Clean['UserType'] == 'Student') 
            {   

                $WokingDays = Helpers::GetClassWorkingDays($WorkingDaysStartDate, $WorkingDaysEndDate, $Clean['ClassSectionID']);

                $TotalWorkingDays = count($WokingDays);

                $AllAttendenceDetails = ClassAttendence::SearchClassAttendence($TotalRecords, false, $Filters, $Start, $Limit, $TotalWorkingDays);
            }
            elseif ($Clean['UserType'] == 'BranchStaff') 
            {   

                $WokingDays = Helpers::GetBranchStaffWorkingDays($WorkingDaysStartDate, $WorkingDaysEndDate, $Clean['ClassSectionID']);

                $TotalWorkingDays = count($WokingDays);

                $AllAttendenceDetails = StaffAttendence::SearchStaffAttendence($TotalRecords, false, $Filters, $Start, $Limit, $TotalWorkingDays);
            }
        }

    break;
}

require_once('../../html_header.php');
?>
<title>Special Watch</title>
<!-- DataTables CSS -->
<link href="/admin/vendor/datatables-plugins/dataTables.bootstrap.css" rel="stylesheet">

<!-- DataTables Responsive CSS -->
<link href="/admin/vendor/datatables-responsive/dataTables.responsive.css" rel="stylesheet">

<link href="/admin/vendor/jquery-ui/jquery-ui.min.css" rel="stylesheet">
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
                    <h1 class="page-header">Special Watch</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
            <form class="form-horizontal" name="frmSpecialWatch" action="special_watch.php" method="get">
                <div class="panel panel-default" id="accordion">
                    <div class="panel-heading">
						<strong>Filters</strong>
                    </div>
                    <div id="collapseOne" class="panel-collapse collapse in">
                        <div class="panel-body">
<?php
                            if ($HasErrors == true)
                            {
                                echo $SearchRecordValidator->DisplayErrors();
                            }
?>
                            <div class="form-group">
                                <label for="UserType" class="col-lg-2 control-label">User Type</label>
                                <div class="col-lg-4">
<?php
                                    foreach ($UserTypeList as $UserType => $UserTypeName) 
                                    {
?>
                                        <label class="radio-inline">
                                            <input type="radio" class="UserType" name="rdbUserType" <?= ($UserType == $Clean['UserType']) ? 'checked="checked"' : ''; ?> value="<?= $UserType; ?>"/><?= $UserTypeName; ?>
                                        </label>  
<?php
                                    }
?>
                                </div>
                            </div>
                            <div class="form-group" id="ClassList" style="display: <?= ($Clean['UserType'] == 'Student') ? 'block' : 'none'; ?>" >
                                <label for="ClassList" class="col-lg-2 control-label">Class List</label>
                                <div class="col-lg-4">
                                    <select class="form-control"  name="drdClassID" id="ClassID">
                                        <option value="0">-- Select --</option>
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
                                        <option value="0">-- Select --</option>
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
                            <div class="form-group" id="StudentList" style="display: <?= ($Clean['UserType'] == 'Student') ? 'block' : 'none'; ?>" >
                                <label for="Student" class="col-lg-2 control-label">Student</label>
                                <div class="col-lg-6">
                                    <select class="form-control" name="drdStudent" id="Student" onchange="GetBookQuotaAndIssueDetails(this.value)">
                                        <option value="0">-- Select --</option>
<?php
                                        if (is_array($StudentsList) && count($StudentsList) > 0)
                                        {
                                            foreach ($StudentsList as $StudentID => $StudentDetails)
                                            {
                                                echo '<option ' . ($Clean['StudentID'] == $StudentID ? 'selected="selected"' : '') . ' value="' . $StudentID . '">' . $StudentDetails['FirstName'] . ' ' . $StudentDetails['LastName'] . ' (' . $StudentDetails['RollNumber'] . ')</option>'; 
                                            }
                                        }
?>
                                    </select>
                                </div>                                
                            </div>
                            <div class="form-group" id="BranchStaffList" style="display: <?= ($Clean['UserType'] == 'Student') ? 'none' : 'block'; ?>">
                                <label for="StaffCategory" class="col-lg-2 control-label">Staff Category</label>
                                <div class="col-lg-4">
                                    <select class="form-control" id="StaffCategory" name="drdStaffCategory">
                                        <option value="">-- Select --</option>
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
                                <label for="BranchStaff" class="col-lg-2 control-label">Branch Staff</label>
                                <div class="col-lg-4">
                                    <select class="form-control EmployeeSalaryDetails" name="drdBranchStaff" id="BranchStaffID">
                                        <option value="0">-- Select --</option>
<?php
                                        if (is_array($BranchStaffList) && count($BranchStaffList) > 0)
                                        {
                                            foreach ($BranchStaffList as $BranchStaffID => $BranchStaffDetails)
                                            {
?>

                                                <option value="<?php echo $BranchStaffID; ?>" <?php echo ($BranchStaffID == $Clean['BranchStaffID'] ? 'selected="selected"' : ''); ?> ><?php echo $BranchStaffDetails['FirstName'] . " ". $BranchStaffDetails['LastName']; ?></option>
<?php
                                            }
                                        }
?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="AttendanePercentRangeTo" class="col-lg-2 control-label">Attendane Percent Range</label>
                                <div class="col-lg-4" style="padding: 0;">
                                    <div class="col-lg-5">
                                        <input class="form-control" type="text" maxlength="2" name="txtAttendanePercentRangeFrom" value="<?= $Clean['AttendanePercentRangeFrom']; ?>">
                                    </div>
                                    <div class="col-lg-2">To</div>
                                    <div class="col-lg-5">
                                        <input class="form-control" type="text" maxlength="2" name="txtAttendanePercentRangeTo" value="<?= $Clean['AttendanePercentRangeTo']; ?>">
                                    </div>
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
            <!-- /.row -->
<?php
    if ($Clean['Process'] == 7 && $HasErrors != true) 
    {
?>
        <div class="row">
            <div class="col-lg-12">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <strong>Special Watch</strong>
                    </div>
                    <!-- /.panel-heading -->
                    <div class="panel-body">
                        <div>
                            <div class="row">
                               <div class="col-lg-6">
<?php
                                if ($TotalPages > 1)
                                {
                                    $AllParameters = $Filters;
                                    $AllParameters['Process'] = '7';

                                    echo UIHelpers::GetPager('special_watch.php', $TotalPages, $Clean['CurrentPage'], $AllParameters);
                                }
?>                                         
                                </div>
                                <div class="col-lg-6">
                                    <div class="print-btn-container"><button id="PrintButton" type="submit" class="btn btn-primary">Print</button></div>
                                </div>
                            </div>
<?php
                        if ($HasErrors == true)
                        {
                            echo $SearchRecordValidator->DisplayErrorsInTable();
                        }
?>
                            <div class="row" id="RecordTableHeading">
                                <div class="col-lg-12">
                                    <div class="report-heading-container"><strong>Frequency Analysis on <?php echo date('d-m-Y h:i A'); ?></strong></div>
                                </div>
                            </div>
                            <div class="row" id="RecordTable">
                                <div class="col-lg-12" style="overflow: auto;">
                                    <table width="100%" class="table table-striped table-bordered table-hover">
                                        <thead>
                                            <tr>
                                                <th>S. No</th>
                                                <th><?= ($Clean['UserType'] == 'Student') ? 'Student' : 'Branch Staff';?> Name</th>
<?php
                                                if ($Clean['UserType'] == 'Student') 
                                                {
                                                    echo '<th>Class Section</th>';
                                                }
?>
                                                <th>Total Working Days</th>
                                                <th>Present Days</th>
                                                <th>Attendane Percentage</th>
                                            </tr>
                                        </thead>
                                        <tbody id="TableData">
<?php
                                                $Count = 1;
                                                foreach ($AllAttendenceDetails as $AttendenceDetails) 
                                                {
?>
                                                <tr>
                                                    <td><?php echo $Count++; ?></td>
                                                    <td><?php echo $AttendenceDetails['FirstName'] . ' ' . $AttendenceDetails['LastName']; ?></td>
<?php
                                                    if ($Clean['UserType'] == 'Student') 
                                                    {
                                                        echo '<td>' . $AttendenceDetails['ClassName'] . '(' . $AttendenceDetails['SectionName'] . ')</td>';
                                                    }
?>
                                                    <td><?php echo $TotalWorkingDays;?></td>
                                                    <td><?php echo $AttendenceDetails['TotalPresentDays'];?></td>
                                                    <td><?php echo  number_format(($AttendenceDetails['TotalPresentDays'] / $TotalWorkingDays) * 100, 2) ;?></td>                                                  
                                                </tr>
<?php
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

<script src="/admin/vendor/jquery-ui/jquery-ui.min.js"></script>

<script type="text/javascript">
$(document).ready(function() 
{
    $('#DataTableRecords').DataTable({
        responsive: true,
        bPaginate: false,
        bSort: false,
        searching: false, 
        info: false
    });

    $('#StaffCategory').change(function()
    {

        StaffCategory = $(this).val();
        
        if (StaffCategory <= 0)
        {
            $('#BranchStaffID').html('<option value="0">Select Section</option>');
            return;
        }
        
        $.post("/xhttp_calls/get_branch_staff_by_staff_category.php", {SelectedStaffCategory:StaffCategory}, function(data)
        {
            ResultArray = data.split("|*****|");
            
            if (ResultArray[0] == 'error')
            {
                alert(ResultArray[1]);
                $('#StaffCategory').val(StaffCategoryBeforeChange);
            }
            else
            {
                $('#BranchStaffID').html('<option value="0">-- Select --</option>' + ResultArray[1]);
            }
        });
    });

    $('#ClassID').change(function()
    {
        var ClassID = parseInt($(this).val());

        $('#Student').html('<option value="0">-- Select Student --</option>');
        
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
                $('#SectionID').html('<option value="0">-- Select --</option>');
                return false;
            }
            else
            {
                $('#SectionID').html('<option value="0">-- Select --</option>' + ResultArray[1]);
            }
        });
    });

    $('#SectionID').change(function()
    {

        var ClassSectionID = parseInt($(this).val());
        
        if (ClassSectionID <= 0)
        {
            $('#Student').html('<option value="0">-- Select Student --</option>');
            return;
        }
        
        $.post("/xhttp_calls/get_students_by_class_section.php", {SelectedClassSectionID:ClassSectionID}, function(data)
        {
            ResultArray = data.split("|*****|");
            
            if (ResultArray[0] == 'error')
            {
                alert (ResultArray[1]);
                return false;
            }
            else
            {
                $('#Student').html('<option value="0">-- Select --</option>' + ResultArray[1]);
            }
        });
    });

    $('.UserType').change(function()
    {   
        if ($(this).val() == 'Student') 
        {
            $('#BranchStaffList').css('display', 'none');
            $('#ClassList').css('display', 'block');
            $('#StudentList').css('display', 'block');
        }
        else if ($(this).val() == 'BranchStaff')
        {
            $('#BranchStaffList').css('display', 'block');
            $('#ClassList').css('display', 'none');
            $('#StudentList').css('display', 'none');
        }
    });
});
</script>
</body>
</html>
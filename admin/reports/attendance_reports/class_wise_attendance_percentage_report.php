<?php
require_once('../../../classes/class.users.php');
require_once('../../../classes/class.validation.php');
require_once('../../../classes/class.authentication.php');

require_once('../../../classes/school_administration/class.academic_years.php');
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

if ($LoggedUser->HasPermissionForTask(TASK_LIST_CLASS_WISE_ATTENDANCE) !== true)
{
	header('location:../../unauthorized_login_admin.php');
	exit;
}

$AcademicYearName = '';
$AcademicYearID = AcademicYear::GetCurrentAcademicYear($AcademicYearName);

$AllClasses = array();
$AllClasses = AddedClass::GetActiveClasses();

$ClassSectionsList = array();

$HasErrors = false;

$Clean['AcademicYearID'] = $AcademicYearID;

$Clean['ClassID'] = 0;
$Clean['ClassID'] = key($AllClasses);

$ClassSectionsList = AddedClass::GetClassSections($Clean['ClassID']);

$Clean['ClassSectionID'] = 0;
$Clean['ClassSectionID'] = key($ClassSectionsList);

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

        $SearchValidator = new Validator();

        $SearchValidator->ValidateInSelect($Clean['ClassID'], $AllClasses, 'Unknown error, please try again');

        if ($SearchValidator->HasNotifications())
        {
            $HasErrors = true;
            break;
        }

        $ClassSectionsList = AddedClass::GetClassSections($Clean['ClassID']);

        $SearchValidator->ValidateInSelect($Clean['ClassSectionID'], $ClassSectionsList, 'Unknown error, please try again.');

        if ($SearchValidator->HasNotifications())
        {
            $HasErrors = true;
            break;
        }

        $CurrentAcademicYearName = '';
        $StartDate = '';
        $EndDate = '';

        AcademicYear::GetCurrentAcademicYear($CurrentAcademicYearName, $StartDate, $EndDate);

        $WorkingDayStartDate = $StartDate;
        $WorkingDaysEndDate = date('Y-m-d');

        $AllWorkingDays = Helpers::GetClassWorkingDays($WorkingDayStartDate, $WorkingDaysEndDate, $Clean['ClassSectionID']);

        $TotalStudentAttendance = 0;
        $TotalOvelallStudentAttendance = ClassAttendence::GetOverallClassSectionAttendance($Clean['ClassSectionID']);
        $StudentsList = StudentDetail::GetStudentsByClassSectionID($Clean['ClassSectionID'], 'Active', $Clean['AcademicYearID']);

        $AttendancePresentPercentage = ($TotalOvelallStudentAttendance / (count($AllWorkingDays) * count($StudentsList))) * 100;

        $AttendanceAbsentPercentage = 100 - $AttendancePresentPercentage;
    break;
}

require_once('../../html_header.php');
?>
<title>Class Wise Attendance Percentage Report</title>
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
                    <h1 class="page-header">Class Wise Attendance Percentage Report</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
            <form class="form-horizontal" name="frmMonthlyAttendanceRegister" action="class_wise_attendance_percentage_report.php" method="post">
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
                        <strong>Pie Chart Representation</strong>
                    </div>
                    <!-- /.panel-heading -->
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-lg-12">
                                <div id="donutchart" style="width: 900px; height: 500px;"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
<?php
    }
?>
    <!-- /#page-wrapper -->

    </div>
    <!-- /#wrapper -->
<?php
require_once('../../footer.php');
?>
<!-- DataTables JavaScript -->
<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
<script type="text/javascript">
    google.charts.load("current", {packages:["corechart"]});
    google.charts.setOnLoadCallback(drawChart);
    function drawChart() 
    {
        var data = google.visualization.arrayToDataTable([
          ['Task', 'Hours per Day'],
          ['Present Percentage', <?php echo $AttendancePresentPercentage;?>],
          ['Absent Percentage', <?php echo $AttendanceAbsentPercentage;?>]
        ]);

        var options = { 
          title: 'Class Wise Percentage',
          pieHole: 0.4,
        };

        var chart = new google.visualization.PieChart(document.getElementById('donutchart'));
        chart.draw(data, options);
    }
</script>
<script type="text/javascript">
    $(document).ready(function() 
    {
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
<?php
require_once('../../../classes/class.users.php');
require_once('../../../classes/class.validation.php');
require_once('../../../classes/class.authentication.php');

require_once("../../../classes/school_administration/class.academic_years.php");
require_once('../../../classes/school_administration/class.class_attendence.php');

require_once('../../../classes/class.date_processing.php');
require_once('../../../classes/class.ui_helpers.php');

require_once('../../../includes/global_defaults.inc.php');
require_once("../../../includes/helpers.inc.php");

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
if ($LoggedUser->HasPermissionForTask(TASK_LIST_DAY_WISE_CLASS_ATTENDANCE) !== true)
{
	header('location:../../unauthorized_login_admin.php');
	exit;
}

$AcademicYearName = '';
$AcademicYearID = AcademicYear::GetCurrentAcademicYear($AcademicYearName);

$DayWiseClassAttendenceList = array();

$HasErrors = false;
        
$Clean = array();

$Clean['AcademicYearID'] = $AcademicYearID;
$Clean['AttendanceDate'] = '';
$Clean['AttendanceDate'] = date('d/m/Y');

$Clean['Process'] = 7;

if (isset($_POST['hdnProcess']))
{
    $Clean['Process'] = (int) $_POST['hdnProcess'];
}
switch ($Clean['Process'])
{
    case 7:
        if (isset($_POST['txtAttendanceDate']))
        {
            $Clean['AttendanceDate'] = strip_tags(trim($_POST['txtAttendanceDate']));
        }

        $RecordValidator = new Validator();

        $RecordValidator->ValidateDate($Clean['AttendanceDate'], 'Please enter a valid attendence date.');

        if ($RecordValidator->HasNotifications())
        {
            $HasErrors = true;
            break;
        }

        $DayWiseClassAttendenceList = ClassAttendence::GetDayWiseClassAttendance(date('Y-m-d', strtotime(DateProcessing::ToggleDateDayAndMonth(($Clean['AttendanceDate'])))), $Clean['AcademicYearID']);
    break;
}

require_once('../../html_header.php');
?>
<title>Day Wise Classes Attendace Report</title>
<link href="../../vendor/jquery-ui/jquery-ui.min.css" rel="stylesheet">
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
                    <h1 class="page-header">Day Wise Classes Attendace Report</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
            <form class="form-horizontal" name="SearchAttendence" action="day_wise_class_attendance_report.php" method="post">
                <div class="panel panel-default">
                    <div class="panel-heading">
                       Enter Details
                    </div>
                    <div class="panel-body">
<?php
                        if ($HasErrors == true)
                        {
                            echo $RecordValidator->DisplayErrors();
                        }
?>                    
                        <div class="form-group">
                            <label for="AttendanceDate" class="col-lg-2 control-label">Attendance Date</label>
                            <div class="col-lg-8">
                                <input type="text" class="form-control dtepicker" maxlength="10" id="AttendanceDate" name="txtAttendanceDate" value="<?php echo $Clean['AttendanceDate']; ?>"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="col-sm-offset-2 col-lg-10">
                                <input type="hidden" name="hdnProcess" value="7" />
                                <button type="submit" class="btn btn-primary">View Report</button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
<?php
        if ($Clean['Process'] == 7)
        {
?>
            <!-- /.row -->
            <div class="row">
                <div class="col-lg-12">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <strong>Total Records Returned: <?php echo count($DayWiseClassAttendenceList); ?></strong>
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
                                        <div class="report-heading-container"><strong>Day Wise Classes Attendace Report on <?php echo date('d-m-Y h:i A'); ?></strong></div>
                                    </div>
                                </div>
                                <div class="row" id="RecordTable">
                                    <div class="col-lg-12">
                                        <table width="100%" class="table table-striped table-bordered table-hover" id="DataTableRecords">
                                            <thead>
                                                <tr>
                                                    <th>S. No</th>
                                                    <th>Class Section</th>
                                                    <th>Total No. Students</th>
                                                    <th>Total No. Present Students</th>
                                                    <th>Total No. Absent Students</th>
                                                    <th>Class Teacher's Name</th>
                                                    <th>Signature</th>
                                                    <th class="print-hidden">Graphical Analysis</th>
                                                </tr>
                                            </thead>
                                            <tbody style="text-align: center;">
<?php
                                            $Counter = 1;
                                            foreach ($DayWiseClassAttendenceList as $ClassSectionID => $DayWiseClassAttendenceDetails) 
                                            {
?>
                                                <tr>
                                                    <td><?php echo $Counter++; ?></td>
                                                    <td><?php echo $DayWiseClassAttendenceDetails['ClassName'] . "(" . $DayWiseClassAttendenceDetails['SectionName'] . ")"; ?></td>
                                                    <td><?php echo $DayWiseClassAttendenceDetails['TotalStudentsInClass']; ?></td>
                                                    <td><?php echo $DayWiseClassAttendenceDetails['TotalPresentStudent']; ?></td>
                                                    <td><?php echo $DayWiseClassAttendenceDetails['TotalAbsentStudent']; ?></td>
                                                    <td><?php echo (!is_null($DayWiseClassAttendenceDetails['ClassTeacherName']) ? $DayWiseClassAttendenceDetails['ClassTeacherName'] : ''); ?></td>
                                                    <td></td>
                                                    <td class="print-hidden"><input class="btn btn-warning btn-sm ViewChart" data-toggle="modal" data-target="#ModalCenter" totalStudent="<?php echo $DayWiseClassAttendenceDetails['TotalStudentsInClass']; ?>" present="<?php echo $DayWiseClassAttendenceDetails['TotalPresentStudent']; ?>" absent="<?php echo $DayWiseClassAttendenceDetails['TotalAbsentStudent']; ?>" type="button" value="View"></td>
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
<!-- /#Modal -->
<div class="modal" id="ModalCenter" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Graphical View</h2>
                <button type="button" class="close modalClose" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="donutchart" style="width: 550px; height: 300px;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary modalClose" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<?php
require_once('../../footer.php');
?>
<!-- DataTables JavaScript -->
<script src="/admin/vendor/datatables/js/jquery.dataTables.min.js"></script>
<script src="/admin/vendor/datatables-plugins/dataTables.bootstrap.min.js"></script>
<script src="/admin/vendor/datatables-responsive/dataTables.responsive.js"></script>

<script src="/admin/vendor/jquery-ui/jquery-ui.min.js"></script>

<script src="https://www.gstatic.com/charts/loader.js" type="text/javascript"></script>

<!-- JavaScript To Print A Report -->
<script src="/admin/js/print-report.js"></script>

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

    $(".dtepicker").datepicker({
        changeMonth: true,
        changeYear: true,
        dateFormat: 'dd/mm/yy'
    });

    $('.ViewChart').click(function()
    {   
        var Pressent = parseInt($(this).attr('present'));
        var Absent = parseInt($(this).attr('absent'));
        var TotalStudent = parseInt($(this).attr('totalStudent'));

        if (Pressent == 0 && Absent == 0) 
        {
            alert('Attendance not marked.');
            return false;
        }

        var PresentPercentage = (Pressent / TotalStudent) * 100;

        if (Absent > 0) 
        {

            var AbsentPercentage = 100 - PresentPercentage;
        }

        google.charts.load("current", {packages:["corechart"]});
        google.charts.setOnLoadCallback(drawChart);

        function drawChart() 
        {   
            var data = google.visualization.arrayToDataTable([
                          ['Task', 'Hours per Day'],
                          ['Present Percentage', PresentPercentage],
                          ['Absent Percentage', AbsentPercentage]
                        ]);

                        var options = { 
                          title: 'Day Wise Class Attendance',
                          pieHole: 0.4,
                          height: 300,
                          width: 550
                        };

                        var chart = new google.visualization.PieChart(document.getElementById('donutchart'));
                        chart.draw(data, options);
        }
    });
});
    </script>
</body>
</html>
<?php
require_once('../../../classes/class.users.php');
require_once('../../../classes/class.validation.php');
require_once('../../../classes/class.authentication.php');

require_once("../../../classes/school_administration/class.academic_years.php");
require_once('../../../classes/school_administration/class.staff_attendence.php');

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

if ($LoggedUser->HasPermissionForTask(TASK_LIST_STAFF_ATTENDANCE) !== true)
{
	header('location:../../unauthorized_login_admin.php');
	exit;
}

$AllBranchStaffAttendanceList = array();
$AllWorkingDays = array();

$StaffCategory = array('Teaching' => 'Teaching Staff', 'NonTeaching' => 'Non Teaching Staff');

$HasErrors = false;

$Clean['StaffCategory'] = '';
$Clean['StaffCategory'] = 'Teaching';

$Clean['PresentStaffList'] = array();

$Clean['Process'] = 7;

if (isset($_POST['hdnProcess']))
{
    $Clean['Process'] = (int) $_POST['hdnProcess'];
}
elseif (isset($_GET['Process']))
{
    $Clean['Process'] = (int) $_GET['Process'];
}
switch ($Clean['Process'])
{
    case 7:
        if (isset($_POST['drdStaffCategory']))
        {
            $Clean['StaffCategory'] = strip_tags(trim($_POST['drdStaffCategory']));
        }
        
        $NewRecordValidator = new Validator();

        $NewRecordValidator->ValidateInSelect($Clean['StaffCategory'], $StaffCategory, 'Unknown error, please try again.');

        if ($NewRecordValidator->HasNotifications())
        {
            $HasErrors = true;
            break;
        }

        $AllBranchStaffAttendanceList = StaffAttendence::GetOverAllStaffAttendance($Clean['StaffCategory']);

        $EndDate = date('Y-m-d');
        $UserType = '';

        AcademicYear::GetCurrentAcademicYear($AcademicYearName, $AcademicYearStartDate, $EndDate);

        $AllWorkingDays = Helpers::GetBranchStaffWorkingDays($AcademicYearStartDate, $EndDate, $Clean['StaffCategory']);
    break;
}

require_once('../../html_header.php');
?>
<title>Staff Attendace Report</title>
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
                    <h1 class="page-header">Staff Attendace Report</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
            <form class="form-horizontal" name="frmStaffAttendanceReport" action="staff_attendance_report.php" method="post">
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
                                <label for="StaffCategory" class="col-lg-3 control-label">Branch Staff</label>
                                <div class="col-lg-6">
                                    <select class="form-control" id="StaffCategory" name="drdStaffCategory">
<?php
                                        foreach ($StaffCategory as $StaffCategory => $StaffCategoryName)
                                        {
?>
                                            <option <?php echo ($StaffCategory == $Clean['StaffCategory'] ? 'selected="selected"' : ''); ?> value="<?php echo $StaffCategory; ?>"><?php echo $StaffCategoryName; ?></option>
<?php
                                        }
?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="col-lg-offset-3 col-lg-10">
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
                            <strong>Total Records Returned: <?php echo count($AllBranchStaffAttendanceList); ?></strong>
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
                                        <div class="report-heading-container"><strong>Staff Attendace Report on <?php echo date('d-m-Y h:i A'); ?></strong></div>
                                    </div>
                                </div>
                                <div class="row" id="RecordTable">
                                    <div class="col-lg-12">
                                        <table width="100%" class="table table-striped table-bordered table-hover" id="DataTableRecords">
                                            <thead>
                                                <tr>
                                                    <th style="text-align:center;">S. No</th>
                                                    <th style="text-align:center;">Name</th>
                                                    <th style="text-align:center;">Total Working Days</th>
                                                    <th style="text-align:center;">Present</th>
                                                    <th style="text-align:center;">Absent</th>
                                                    <th style="text-align:center;">Total Persent</th>
                                                    <th class="print-hidden">Graphical Analysis</th>
                                                </tr>
                                            </thead>
                                            <tbody>
<?php
                                            $Counter = 1;

                                            foreach ($AllBranchStaffAttendanceList as $BranchStaffID => $BranchStaffAttendanceDeatils) 
                                            {
                                                $TotalWorkingDays = count($AllWorkingDays);
?>
                                                <tr>
                                                    <td style="text-align:center;"><?php echo $Counter++; ?></td>
                                                    <td><?php echo $BranchStaffAttendanceDeatils['FirstName'] .  ' ' . $BranchStaffAttendanceDeatils['LastName']; ?></td>
                                                    <td style="text-align:center;"><?php echo $TotalWorkingDays;?></td>
                                                    <td style="text-align:center;"><?php echo $BranchStaffAttendanceDeatils['TotalPresents'];?></td>
                                                    <td style="text-align:center;"><?php echo $BranchStaffAttendanceDeatils['TotalAbsents'];?></td>
                                                    <td style="text-align:center;">
                                                        <?php
                                                            echo number_format(($BranchStaffAttendanceDeatils['TotalPresents']/$TotalWorkingDays) * 100, 2).'%';
                                                        ?>
                                                    </td>
                                                    <td class="print-hidden"><input class="btn btn-warning btn-sm ViewChart" data-toggle="modal" data-target="#ModalCenter" totalWorkingDays="<?php echo $TotalWorkingDays; ?>" present="<?php echo $BranchStaffAttendanceDeatils['TotalPresents']; ?>" absent="<?php echo $BranchStaffAttendanceDeatils['TotalAbsents']; ?>" type="button" value="View"></td>
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
                <h2 class="modal-title">Graphical View
                    <button type="button" class="close modalClose" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </h2>
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

<script src="https://www.gstatic.com/charts/loader.js" type="text/javascript"></script>

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
	
    $("body").on('click', '.delete-record', function()
    {	
        if (!confirm("Are you sure you want to delete this task?"))
        {
            return false;
        }
    });

    $('#DataTableRecords').DataTable({
        responsive: true,
        bPaginate: false,
        bSort: false,
        searching: false, 
        info: false
    });

    $('.ViewChart').click(function()
    {   
        var Pressent = $(this).attr('present');
        var Absent = $(this).attr('absent');
        var TotalWorkingDays = $(this).attr('totalWorkingDays');

        if (Pressent == 0 && Absent == 0) 
        {
            alert('Attendance not marked.');
            return false;
        }
        
        var PresentPercentage = (Pressent / TotalWorkingDays) * 100;
        var AbsentPercentage = 100 - PresentPercentage;

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
<?php
require_once('../../../classes/class.users.php');
require_once('../../../classes/class.validation.php');
require_once('../../../classes/class.authentication.php');

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

if ($LoggedUser->HasPermissionForTask(TASK_TASK_LIST) !== true)
{
	header('location:../../unauthorized_login_admin.php');
	exit;
}

$HasErrors = false;

require_once('../../html_header.php');
?>
<title>Student Performance Report</title>
<!-- DataTables CSS -->
<link href="/admin/vendor/datatables-plugins/dataTables.bootstrap.css" rel="stylesheet">

<!-- DataTables Responsive CSS -->
<link href="/admin/vendor/datatables-responsive/dataTables.responsive.css" rel="stylesheet">
<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script type="text/javascript">
      google.charts.load('current', {'packages':['bar']});
      google.charts.setOnLoadCallback(drawChart);


    function drawChart() {
        var data = google.visualization.arrayToDataTable([
          ['Month', 'Marks %', 'Class Average', 'Toppers'],
          ['Apr', 70, 80, 92],
          ['May', 76, 84, 95],
          ['July', 78, 80, 90], 
          ['August', 85, 89, 95]
        ]);

        var options = {
          chart: {
            title: 'Student Performance',
            subtitle: 'Marks %, Class Average, and Toppers',
          }
        };

        var chart = new google.charts.Bar(document.getElementById('BarChart'));

        chart.draw(data, google.charts.Bar.convertOptions(options));
      }
    </script>
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
                    <h1 class="page-header">Student Performance Report</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
            <form class="form-horizontal" name="frmMonthlyAttendanceRegister" action="monthly_attendace_register.php" method="get">
                <div class="panel panel-default" id="accordion">
                    <div class="panel-heading">
                        <strong>Filters</strong>
                    </div>
                    <div id="collapseOne" class="panel-collapse collapse in">
                        <div class="panel-body">
                            <div class="form-group">
                                <label for="Class" class="col-lg-2 control-label">Class</label>
                                <div class="col-lg-4">
                                    <select class="form-control" name="drdClass" id="Class">
                                        <option value="1">1</option>
                                        <option value="1">2</option>
                                        <option value="1">3</option>
                                        <option value="1">4</option>
                                        <option value="1">5</option>
                                        <option value="1">6</option>
                                        <option value="1">7</option>
                                        <option value="1">8</option>
                                    </select>
                                </div>
                                <label for="Section" class="col-lg-2 control-label">Section</label>
                                <div class="col-lg-4">
                                    <select class="form-control" name="drdSection" id="Section">
                                        <option value="1">A</option>
                                        <option value="2">B</option>
                                        <option value="3">C</option>
                                        <option value="4">D</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="Student" class="col-lg-2 control-label">Student</label>
                                <div class="col-lg-4">
                                    <select class="form-control" name="drdStudent" id="Student">
                                        <option value="1">Saurabh Singh</option>
                                        <option value="1">2</option>
                                        <option value="1">3</option>
                                        <option value="1">4</option>
                                        <option value="1">5</option>
                                        <option value="1">6</option>
                                        <option value="1">7</option>
                                        <option value="1">8</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="Type" class="col-lg-2 control-label">Type</label>
                                <div class="col-lg-4">
                                    <label style="font-weight: normal;">
                                        <input type="radio" name="rdbType" checked="checked">&nbsp;Test/Exam Wise &nbsp;&nbsp;
                                        <input type="radio" name="rdbType">&nbsp;Period Wise &nbsp;&nbsp;
                                    </label>
                                </div>
                                <label for="SubjectsCriteria" class="col-lg-2 control-label">Subjects</label>
                                <div class="col-lg-4">
                                    <label style="font-weight: normal;">
                                        <input type="radio" name="rdbSubject" checked="checked">&nbsp;Over All Subjects&nbsp;&nbsp;
                                        <input type="radio" name="rdbSubject">&nbsp;Specific Subjects&nbsp;&nbsp;
                                    </label>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="Represent" class="col-lg-2 control-label">Represent Through</label>
                                <div class="col-lg-8">
                                    <label style="font-weight: normal;">
                                        <input type="radio" name="rdbRepresent" checked="checked">&nbsp;Graphical Analysis &nbsp;&nbsp;
                                        <input type="radio" name="rdbRepresent">&nbsp;Tabular Analysis &nbsp;&nbsp;
                                        <input type="radio" name="rdbRepresent">&nbsp;Growth Analysis &nbsp;&nbsp;
                                    </label>
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
            <div class="panel panel-default" id="accordion">
                <div class="panel-heading">
                    <strong>Graphical Representation</strong>
                </div>
                <div id="collapseOne" class="panel-collapse collapse in">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="row">
                                    <div class="col-lg-12">
                                        <div id="BarChart" style="width: 1100px; height: 500px;"></div>
                                    </div>
                                </div>
                            </div>
                            <!-- /.col-lg-12 -->
                        </div>
                    </div>
                </div>
            </div>
            <!-- /.row -->
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

	$(document).ready(function() {
		
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
	});
    </script>
</body>
</html>
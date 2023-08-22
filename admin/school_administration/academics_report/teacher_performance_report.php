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
<title>Faculty Performance Report</title>
<link href="/admin/vendor/jquery-ui/jquery-ui.min.css" rel="stylesheet">
<!-- DataTables CSS -->
<link href="/admin/vendor/datatables-plugins/dataTables.bootstrap.css" rel="stylesheet">

<!-- DataTables Responsive CSS -->
<link href="/admin/vendor/datatables-responsive/dataTables.responsive.css" rel="stylesheet">
<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
<script type="text/javascript">
    google.charts.load("current", {packages:["corechart"]});
    google.charts.setOnLoadCallback(drawChart);
    function drawChart() 
    {
        var data = google.visualization.arrayToDataTable([
          ['Task', 'Student Test Performance'],
          ['Scores < 40%', 4],
		  ['40% - 60%', 17],
		  ['60% - 80%', 12],
		  ['80% - 90%', 4]
        ]);

        var options = { 
          title: 'Faculty Performance Graph',
          pieHole: 0.4,
        };

        var chart = new google.visualization.PieChart(document.getElementById('donutchart'));
        chart.draw(data, options);
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
                    <h1 class="page-header">Faculty Performance Report</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
            <!-- /.row -->
            <div class="row">
                <div class="col-lg-12">
					<form class="form-horizontal" name="AddPromoterDetails" action="add_member.php" method="post">
						<div class="panel panel-default">
							<div class="panel-heading">
								Faculty Performance Report
							</div>
							<!-- /.panel-heading -->
							<div class="panel-body">
								<div class="form-group">
									<label for="Class" class="col-lg-2 control-label">Select Faculty</label>
									<div class="col-lg-4">
										<select class="form-control" name="drdFacultyID" id="Class">
											<option value="1">Amit Mishra</option>
											<option value="1">Jhon Lark</option>
											<option value="1" selected="selected">Sagar Mishra</option>
										</select>
									</div>
								</div>
								
								<div class="form-group">
									<label for="Class" class="col-lg-2 control-label">Class</label>
									<div class="col-lg-4">
										<select class="form-control" name="drdClass" id="Class">
											<option value="1">1</option>
											<option value="2">2</option>
											<option value="3">3</option>
											<option value="4" selected="selected">4</option>
										</select>
									</div>
									
									<label for="Section" class="col-lg-2 control-label">Section</label>
									<div class="col-lg-4">
										<select class="form-control" name="drdSection" id="Section">
											<option value="1">A</option>
											<option value="2">B</option>
											<option value="3" selected="selected">C</option>
											<option value="4">D</option>
										</select>
									</div>
								</div>
								
								<div class="form-group">
									<label for="Subject" class="col-lg-2 control-label">Select Subject</label>
									<div class="col-lg-4">
										<select class="form-control" name="drdSubjectID" id="Subject">
											<option value="1">Commerce</option>
											<option value="1">Commercial Mathematics</option>
											<option value="1">Accounting</option>
										</select>
									</div>
								</div>
								
								<div class="form-group">
									<label for="Class" class="col-lg-2 control-label">From Date</label>
									<div class="col-lg-4">
										<input class="form-control admin-date" type="text" maxlength="10" id="FromDate" name="txtFromDate" value="" placeholder="dd/mm/yyyy" />
									</div>
									
									<label for="Section" class="col-lg-2 control-label">Till Date</label>
									<div class="col-lg-4">
										<input class="form-control admin-date" type="text" maxlength="10" id="FromDate" name="txtFromDate" value="" placeholder="dd/mm/yyyy" />
									</div>
								</div>
								
								<div class="form-group">
									<div class="col-sm-offset-2 col-lg-12">
										<input type="hidden" name="hdnProcess" value="1" />
										<button type="button" class="btn btn-primary">Save</button>
									</div>
								</div>
								
								<hr />
								<table style="" class="align-center table table-striped table-bordered table-hover">
									<tr>
										<td>Total number of lectures during the period</td>
										<th>42/48</th>
									</tr>
									<tr>
										<td>Total number of test taken</td>
										<th>8</th>
									</tr>
									<tr>
										<td>Total number of exams</td>
										<th>2</th>
									</tr>
								</table>
								
								<div class="panel panel-default">
									<div class="panel-heading">
										Faculty Performance
									</div>
									<!-- /.panel-heading -->
									<div class="panel-body">
										<div class="form-group">
											<div class="col-lg-8">
												<div id="donutchart" style="height: 400px;"></div>
											</div>
											<div class="col-lg-4">
												<table style="width: 100%; margin-top: 80px;" class="align-center table table-striped table-bordered table-hover">
													<tr><th colspan="2"><center>Student Scores Report</center></th></tr>
													<tr>
														<td class="text-center">Scores &lt; 40%</td>
														<th class="text-center">4</th>
													</tr>
													<tr>
														<td class="text-center">40% - 60%</td>
														<th class="text-center">17</th>
													</tr>
													<tr>
														<td class="text-center">60% - 80%</td>
														<th class="text-center">12</th>
													</tr>
													<tr>
														<td class="text-center">80% - 90%</td>
														<th class="text-center">4</th>
													</tr>
													<tr>
														<td class="text-center">Scores &gt; 50%</td>
														<th class="text-center">26</th>
													</tr>
												</table>
											</div>
										</div>
										
										<div class="form-group">
											<div class="col-lg-6 text-center"><button type="button" class="btn btn-primary" name="btnCompareWithOtherFaculties">Compare With Other Faculties</button></div>
											<div class="col-lg-6 text-center"><button type="button" class="btn btn-warning" name="btnCompareWithOtherFaculties">Growth Rate</button></div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</form>
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
	<script src="/admin/vendor/jquery-ui/jquery-ui.min.js"></script>
	
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
		$(".admin-date").datepicker({
			changeMonth: true,
			changeYear: true,
			dateFormat: 'dd/mm/yy'
		});

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
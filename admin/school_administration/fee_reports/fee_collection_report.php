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
<title>Fee Collection Report</title>
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
          ['Task', 'Hours per Day'],
          ['Current Month Fee Collection', 94],
          ['Dues For The Month', 6]
        ]);

        var options = { 
          title: 'Fee Collection',
          pieHole: 0.4,
        };

        var chart = new google.visualization.PieChart(document.getElementById('donutchart'));
        chart.draw(data, options);
    }
</script>
<script type="text/javascript">
      google.charts.load('current', {packages: ['corechart', 'bar']});
        google.charts.setOnLoadCallback(drawBasic);

    function drawBasic() {

          var data = new google.visualization.DataTable();
          data.addColumn('number', 'Days');
          data.addColumn('number', 'Rupees');

          data.addRows([
            [1, 50000],
            [2, 0],
            [3, 80000],
            [4, 0],
            [5, 855500],
            [6, 0],
            [7, 0],
            [8, 0],
            [9, 492000],
            [10, 177000],
            [11, 0],
            [12, 104000],
            [13, 43000],
            [14, 1000],
            [15, 1000],
            [16, 1000],
            [17, 0],
            [17, 0],
            [18, 0],
            [19, 0],
            [20, 0],
            [21, 0],
            [22, 0],
            [23, 0],
            [24, 0],
            [25, 0],
            [26, 0],
            [27, 0],
            [28, 0],
            [29, 0],
            [30, 0],
            [31, 0]
          ]);

          // Set chart options
            var options = {
               title : 'Day Wise Collection',
               vAxis: {title: 'Rupees'},
               hAxis: {title: 'Days'},
               seriesType: 'bars',
               series: {1: {type: 'line'}}
            };

          var chart = new google.visualization.ColumnChart(
            document.getElementById('BarChart'));

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
                    <h1 class="page-header">Fee Collection Report</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
            <!-- /.row -->
            <form class="form-horizontal" name="frmHeadWiseFeeCollection" action="head_wise_fee_collection_report.php" method="get">
                <div class="panel panel-default" id="accordion">
                    <div class="panel-heading">
                        <strong>Filters</strong>
                    </div>
                    <div id="collapseOne" class="panel-collapse collapse in">
                        <div class="panel-body">
<?php
                            if ($HasErrors == true)
                            {
                                echo $RecordValidator->DisplayErrorsInTable();
                            }
?>
                            <div class="form-group">
                                <label for="Academic Year" class="col-lg-2 control-label">Academic Year</label>
                                <div class="col-lg-4">
                                    <select class="form-control" name="drdAcademic" id="Academic">
                                        <option value="1" selected="selected">2018 - 2019</option>
                                    </select>
                                </div>
                                <label for="Month" class="col-lg-2 control-label">Month</label>
                                <div class="col-lg-4">
                                    <select class="form-control" name="drdMonth" id="Month">
                                        <option value="April">April</option>
                                        <option value="July" selected="selected">July</option>
                                        <option value="Augest">Augest</option>
                                        <option value="September">September</option>
                                        <option value="October">October</option>
                                        <option value="November">November</option>
                                        <option value="December">December</option>
                                        <option value="january">january</option>
                                        <option value="February">Febuary</option>
                                        <option value="March">March</option>
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
            <div class="row">
                <div class="col-lg-12">
                    <form class="form-horizontal" name="frmMonthlyAttendanceRegister" action="monthly_attendace_register.php" method="get">
                        <div class="panel panel-default" id="accordion">
                            <div class="panel-heading">
                                <strong>Fee Collection Report</strong>
                            </div>
                            <div id="collapseOne" class="panel-collapse collapse in">
                                <div class="panel-body">
                                    <div class="form-group">
                                        <label for="TodaysCollection" class="col-lg-3 control-label">Todays Fee Collection</label>
                                        <div class="col-lg-4">
                                            <input class="form-control" type="text" name="txtTodaysCollection" value="75,000" disabled="disabled">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="CurrentMonthFeeCollect" class="col-lg-3 control-label">Current Month Fee Collect</label>
                                        <div class="col-lg-4">
                                            <input class="form-control" type="text" name="txtCurrentMonthFeeCollect" value="38,06,100" disabled="disabled">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="TotalCollectionForTheMonth" class="col-lg-3 control-label">Total Collection For The Month</label>
                                        <div class="col-lg-4">
                                            <input class="form-control" type="text" name="txtTotalColleectionForTheMonth" value="50,00,000" disabled="disabled">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="AmountDueForThisMonth" class="col-lg-3 control-label">Amount Due For This Month</label>
                                        <div class="col-lg-4">
                                            <input class="form-control" type="text" name="txtAmountDueForThisMonth" value="75,000" disabled="disabled">
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-12">
                                            <div id="donutchart" style="width: 900px; height: 500px;"></div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-12">
                                            <div id="BarChart" style="width: 1100px; height: 500px;"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <!-- /.col-lg-12 -->
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
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
<title>Class Wise Percentage Report</title>
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
          ['Present Percentage', 80],
          ['Absent Percentage', 10]
        ]);

        var options = { 
          title: 'Class Wise Percentage',
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
                    <h1 class="page-header">Class Wise Percentage Report</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
            <!-- /.row -->
            <div class="row">
                <div class="col-lg-12">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <strong>Pie Chart Representation</strong>
                        </div>
                        <!-- /.panel-heading -->
                        <div class="panel-body">
                            <div class="row">
                                <div class="form-group">
                                <label for="Class" class="col-lg-1 control-label">Class</label>
                                <div class="col-lg-4">
                                    <select class="form-control" name="drdClass" id="Class">
                                        <option value="1">1</option>
                                        <option value="1">2</option>
                                        <option value="1" selected="selected">3</option>
                                        <option value="1">4</option>
                                        <option value="1">5</option>
                                        <option value="1">6</option>
                                        <option value="1">7</option>
                                        <option value="1">8</option>
                                    </select>
                                </div>
                                <label for="Section" class="col-lg-1 control-label">Section</label>
                                <div class="col-lg-4">
                                    <select class="form-control" name="drdSection" id="Section">
                                        <option value="1">A</option>
                                        <option value="2">B</option>
                                        <option value="3" selected="selected">C</option>
                                        <option value="4">D</option>
                                    </select>
                                </div>
                            </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-12">
                                    <div id="donutchart" style="width: 900px; height: 500px;"></div>
                                </div>
                            </div>
                        </div>
                    </div>
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
		$('.SchoolClasses').click(function()
        {   
            if ($(this).val() == 1) 
            {   
                google.charts.load("current", {packages:["corechart"]});
                drawChartForClasses();

                function drawChartForClasses() 
                {
                    var data = google.visualization.arrayToDataTable([
                      ['Task', 'Hours per Day'],
                      ['Present Percentage', 80],
                      ['Absent Percentage', 10]
                    ]);

                    var options = { 
                      title: 'Class Wise Percentage',
                      pieHole: 0.4,
                    };

                    var chart = new google.visualization.PieChart(document.getElementById('donutchart'));
                    chart.draw(data, options);
                }
            }

            if ($(this).val() == 2) 
            {   
                google.charts.load("current", {packages:["corechart"]});
                drawChartForClasses();

                function drawChartForClasses() 
                {
                    var data = google.visualization.arrayToDataTable([
                      ['Task', 'Hours per Day'],
                      ['Present Percentage', 80],
                      ['Absent Percentage', 20]
                    ]);

                    var options = { 
                      title: 'Class Wise Percentage',
                      pieHole: 0.4,
                    };

                    var chart = new google.visualization.PieChart(document.getElementById('donutchart'));
                    chart.draw(data, options);
                }
            }

            if ($(this).val() == 3) 
            {   
                google.charts.load("current", {packages:["corechart"]});
                drawChartForClasses();

                function drawChartForClasses() 
                {
                    var data = google.visualization.arrayToDataTable([
                      ['Task', 'Hours per Day'],
                      ['Present Percentage', 92],
                      ['Absent Percentage', 8]
                    ]);

                    var options = { 
                      title: 'Class Wise Percentage',
                      pieHole: 0.4,
                    };

                    var chart = new google.visualization.PieChart(document.getElementById('donutchart'));
                    chart.draw(data, options);
                }
            }

            if ($(this).val() == 4) 
            {   
                google.charts.load("current", {packages:["corechart"]});
                drawChartForClasses();

                function drawChartForClasses() 
                {
                    var data = google.visualization.arrayToDataTable([
                      ['Task', 'Hours per Day'],
                      ['Present Percentage', 75],
                      ['Absent Percentage', 25]
                    ]);

                    var options = { 
                      title: 'Class Wise Percentage',
                      pieHole: 0.4,
                    };

                    var chart = new google.visualization.PieChart(document.getElementById('donutchart'));
                    chart.draw(data, options);
                }
            }

            if ($(this).val() == 5) 
            {   
                google.charts.load("current", {packages:["corechart"]});
                drawChartForClasses();

                function drawChartForClasses() 
                {
                    var data = google.visualization.arrayToDataTable([
                      ['Task', 'Hours per Day'],
                      ['Present Percentage', 95],
                      ['Absent Percentage', 5]
                    ]);

                    var options = { 
                      title: 'Class Wise Percentage',
                      pieHole: 0.4,
                    };

                    var chart = new google.visualization.PieChart(document.getElementById('donutchart'));
                    chart.draw(data, options);
                }
            }

            if ($(this).val() == 6) 
            {   
                google.charts.load("current", {packages:["corechart"]});
                drawChartForClasses();

                function drawChartForClasses() 
                {
                    var data = google.visualization.arrayToDataTable([
                      ['Task', 'Hours per Day'],
                      ['Present Percentage', 98],
                      ['Absent Percentage', 2]
                    ]);

                    var options = { 
                      title: 'Class Wise Percentage',
                      pieHole: 0.4,
                    };

                    var chart = new google.visualization.PieChart(document.getElementById('donutchart'));
                    chart.draw(data, options);
                }
            }

            if ($(this).val() == 7) 
            {   
                google.charts.load("current", {packages:["corechart"]});
                drawChartForClasses();

                function drawChartForClasses() 
                {
                    var data = google.visualization.arrayToDataTable([
                      ['Task', 'Hours per Day'],
                      ['Present Percentage', 90],
                      ['Absent Percentage', 10]
                    ]);

                    var options = { 
                      title: 'Class Wise Percentage',
                      pieHole: 0.4,
                    };

                    var chart = new google.visualization.PieChart(document.getElementById('donutchart'));
                    chart.draw(data, options);
                }
            }

            if ($(this).val() == 8) 
            {   
                google.charts.load("current", {packages:["corechart"]});
                drawChartForClasses();

                function drawChartForClasses() 
                {
                    var data = google.visualization.arrayToDataTable([
                      ['Task', 'Hours per Day'],
                      ['Present Percentage', 84],
                      ['Absent Percentage', 16]
                    ]);

                    var options = { 
                      title: 'Class Wise Percentage',
                      pieHole: 0.4,
                    };

                    var chart = new google.visualization.PieChart(document.getElementById('donutchart'));
                    chart.draw(data, options);
                }
            }

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
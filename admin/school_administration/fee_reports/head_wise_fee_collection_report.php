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
<title>Head Wise Fee Collection Report</title>
<!-- DataTables CSS -->
<link href="/admin/vendor/datatables-plugins/dataTables.bootstrap.css" rel="stylesheet">

<!-- DataTables Responsive CSS -->
<link href="/admin/vendor/datatables-responsive/dataTables.responsive.css" rel="stylesheet">
<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>

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
                    <h1 class="page-header">Head Wise Fee Collection Report</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
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
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <strong>Total Records Returned: <?php echo 7; ?></strong>
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
                                        <div class="report-heading-container"><strong>Head Wise Fee Collection Report on <?php echo date('d-m-Y h:i A'); ?></strong></div>
                                    </div>
                                </div>
                                <div class="row" id="RecordTable">
                                    <div class="col-lg-12">
                                        <table width="100%" class="table table-striped table-bordered table-hover" id="DataTableRecords">
                                            <thead>
                                                <tr>
                                                    <th>Class</th>
                                                    <th>Tution Fee</th>
                                                    <th>Exam Fee</th>
                                                    <th>Transport Fee</th>
                                                    <th>Library Fee</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>1(A)</td>
                                                    <td>59,000</td>
                                                    <td>0</td> 
                                                    <td>6400</td>
                                                    <td>0</td>
                                                </tr>
                                                <tr>
                                                    <td>1(B)</td>
                                                    <td>59,000</td>
                                                    <td>0</td>
                                                    <td>5000</td>
                                                    <td>0</td>
                                                </tr>
                                                <tr>
                                                    <td>1(C)</td>
                                                    <td>59,000</td>
                                                    <td>0</td>
                                                    <td>5400</td>
                                                    <td>0</td>
                                                </tr>
                                                <tr>
                                                    <td>1(D)</td>
                                                    <td>59,000</td>
                                                    <td>0</td>
                                                    <td>5700</td>
                                                    <td>0</td>
                                                </tr>
                                                <tr>
                                                    <td>2(A)</td>
                                                    <td>62,500</td>
                                                    <td>0</td>
                                                    <td>6100</td>
                                                    <td>0</td>
                                                </tr>
                                                <tr>
                                                    <td>2(B)</td>
                                                    <td>62,500</td>
                                                    <td>0</td>
                                                    <td>6700</td>
                                                    <td>0</td>
                                                </tr>
                                                <tr>
                                                    <td>2(C)</td>
                                                    <td>62,500</td>
                                                    <td>0</td>
                                                    <td>4000</td>
                                                    <td>0</td>
                                                </tr>
                                                <tr>
                                                    <td>3(A)</td>
                                                    <td>65,400</td>
                                                    <td>0</td>
                                                    <td>6450</td>
                                                    <td>0</td>
                                                </tr>
                                                <tr>
                                                    <td>3(B)</td>
                                                    <td>65,400</td>
                                                    <td>0</td>
                                                    <td>6160</td>
                                                    <td>0</td>
                                                </tr>
                                                <tr>
                                                    <td>4(A)</td>
                                                    <td>72,400</td>
                                                    <td>0</td>
                                                    <td>5300</td>
                                                    <td>0</td>
                                                </tr>
                                                <tr>
                                                    <td>4(B)</td>
                                                    <td>72,400</td>
                                                    <td>0</td>
                                                    <td>4900</td>
                                                    <td>0</td>
                                                </tr>
                                                <tr>
                                                    <td>5(A)</td>
                                                    <td>76,900</td>
                                                    <td>0</td>
                                                    <td>7600</td>
                                                    <td>0</td>
                                                </tr>
                                                <tr>
                                                    <td>6(A)</td>
                                                    <td>76,900</td>
                                                    <td>0</td>
                                                    <td>8900</td>
                                                    <td>0</td>
                                                </tr>
                                                <tr>
                                                    <td>6(B)</td>
                                                    <td>76,900</td>
                                                    <td>0</td>
                                                    <td>7100</td>
                                                    <td>0</td>
                                                </tr>
                                                <tr>
                                                    <td>7</td>
                                                    <td>72,600</td>
                                                    <td>0</td>
                                                    <td>7800</td>
                                                    <td>0</td>
                                                </tr>
                                                <tr>
                                                    <td>8(A)</td>
                                                    <td>86,000</td>
                                                    <td>0</td>
                                                    <td>6800</td>
                                                    <td>0</td>
                                                </tr>
                                                <tr>
                                                    <td>8(B)</td>
                                                    <td>60,900</td>
                                                    <td>0</td>
                                                    <td>4400</td>
                                                    <td>0</td>
                                                </tr>
                                                <tr>
                                                    <th>Total</th>
                                                    <th>8,76,400</th>
                                                    <th>0</th>
                                                    <th>97,690</th>
                                                    <th>0</th>
                                                </tr>
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
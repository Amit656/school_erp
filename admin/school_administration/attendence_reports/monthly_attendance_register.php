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
$HasSearchErrors = false;

require_once('../../html_header.php');
?>
<title>Monthly Attendance Register</title>
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
            <form class="form-horizontal" name="frmMonthlyAttendanceRegister" action="monthly_attendace_register.php" method="get">
                <div class="panel panel-default" id="accordion">
                    <div class="panel-heading">
						<strong>Filters</strong>
                    </div>
                    <div id="collapseOne" class="panel-collapse collapse in">
                        <div class="panel-body">
<?php
                            if ($HasSearchErrors == true)
                            {
                                echo $SearchValidator->DisplayErrors();
                            }
?>
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
                                <label for="Month" class="col-lg-2 control-label">Month</label>
                                <div class="col-lg-4">
                                    <select class="form-control" name="drdMonth" id="Month">
                                        <option value="April">April</option>
                                        <option value="July">July</option>
                                        <option value="Augest">Augest</option>
                                        <option value="September">September</option>
                                        <option value="October" selected="selected">October</option>
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
            <!-- /.row -->
            <div class="row">
                <div class="col-lg-12">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <strong>Monthly Attendance Register</strong>
                        </div>
                        <!-- /.panel-heading -->
                        <div class="panel-body">
                            <div>
                                <div class="row">
                                    <div class="col-lg-12" style="text-align: right;">
                                        <div class="print-btn-container"><button id="PrintButton" type="submit" class="btn btn-primary">Print</button></div>
                                    </div>
                                </div>
<?php
                            if ($HasErrors == true)
                            {
                                echo $RecordValidator->DisplayErrorsInTable();
                            }
?>
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
                                                    <th>Student Name</th>
<?php
                                                    for ($Date = 1; $Date <= 31; $Date++) 
                                                    { 
                                                        echo'<td>'.$Date.'</td>';   
                                                    }
?>
                                                </tr>
                                            </thead>
                                            <tbody id="TableData">
                                                <tr>
                                                    <td>Abhishek Yadav</td>
                                                    <td>P</td>
                                                    <td></td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>A</td>
                                                    <td>P</td>
                                                    <td></td>
                                                    <td>A</td>
                                                    <td>A</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td></td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td></td>
                                                    <td></td>
                                                    <td></td>
                                                    <td></td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td></td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                </tr>
                                                <tr>
                                                    <td>Aditya Rajput</td>
                                                    <td>P</td>
                                                    <td></td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td></td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td></td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td></td>
                                                    <td></td>
                                                    <td></td>
                                                    <td></td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td></td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                </tr>
                                                <tr>
                                                    <td>Aman Yadav</td>
                                                    <td>P</td>
                                                    <td></td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td></td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td></td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td></td>
                                                    <td></td>
                                                    <td></td>
                                                    <td></td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td></td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                </tr>
                                                <tr>
                                                    <td>Aniruddha</td>
                                                    <td>P</td>
                                                    <td></td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td></td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td></td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td></td>
                                                    <td></td>
                                                    <td></td>
                                                    <td></td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td></td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                </tr>
                                                <tr>
                                                    <td>Ansh Jaiswal</td>
                                                    <td>P</td>
                                                    <td></td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td></td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td></td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td></td>
                                                    <td></td>
                                                    <td></td>
                                                    <td></td>
                                                    <td>P</td>
                                                    <td>A</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td></td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                </tr>
                                                <tr>
                                                    <td>Ansh Verma</td>
                                                    <td>P</td>
                                                    <td></td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td></td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td></td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td></td>
                                                    <td></td>
                                                    <td></td>
                                                    <td></td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td></td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                </tr>
                                                <tr>
                                                    <td>Amitesh Singh</td>
                                                    <td>P</td>
                                                    <td></td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td></td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td></td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td></td>
                                                    <td></td>
                                                    <td></td>
                                                    <td></td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td></td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                </tr>
                                                <tr>
                                                    <td>Ansh Verma</td>
                                                    <td>P</td>
                                                    <td></td>
                                                    <td>P</td>
                                                    <td>A</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td></td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>A</td>
                                                    <td></td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td></td>
                                                    <td></td>
                                                    <td></td>
                                                    <td></td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td></td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                </tr>
                                                <tr>
                                                    <td>Raju Srivastava</td>
                                                    <td>P</td>
                                                    <td></td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td></td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>A</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td></td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td></td>
                                                    <td></td>
                                                    <td></td>
                                                    <td></td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td></td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                </tr>
                                                <tr>
                                                    <td>Ansh Verma</td>
                                                    <td>P</td>
                                                    <td></td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td></td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td></td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td></td>
                                                    <td></td>
                                                    <td></td>
                                                    <td></td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td></td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                </tr>
                                                <tr>
                                                    <td>Shanaya kapoor</td>
                                                    <td>P</td>
                                                    <td></td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td></td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td></td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td></td>
                                                    <td></td>
                                                    <td></td>
                                                    <td></td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td></td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                </tr>
                                                <tr>
                                                    <td>Ansh Verma</td>
                                                    <td>P</td>
                                                    <td></td>
                                                    <td>A</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td></td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>A</td>
                                                    <td></td>
                                                    <td>A</td>
                                                    <td>A</td>
                                                    <td>P</td>
                                                    <td></td>
                                                    <td></td>
                                                    <td></td>
                                                    <td></td>
                                                    <td>A</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td></td>
                                                    <td>P</td>
                                                    <td>P</td>
                                                    <td>P</td>
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
        $(function()
        {
            $('#TableData tr td').each(function()
            {
                if($(this).text() == 'A')
                {
                    $(this).css("color", "red");
                }
            });

            $('#TableData tr').find('td:eq(2)').text('H').css('color', '#d9534f');
            $('#TableData tr').find('td:eq(7)').text('WO').css('color', '#d9534f');
            $('#TableData tr').find('td:eq(14)').text('WO').css('color', '#d9534f');
            $('#TableData tr').find('td:eq(18)').text('H').css('color', '#d9534f');
            $('#TableData tr').find('td:eq(19)').text('H').css('color', '#d9534f');
            $('#TableData tr').find('td:eq(20)').text('H').css('color', '#d9534f');
            $('#TableData tr').find('td:eq(21)').text('H').css('color', '#d9534f');
            $('#TableData tr').find('td:eq(28)').text('WO').css('color', '#d9534f');
        });             
    });

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
	});
    </script>
</body>
</html>
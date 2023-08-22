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
<title>Frequency Analysis</title>
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
                                <label for="UserType" class="col-lg-2 control-label">User Type</label>
                                <div class="col-lg-4">
                                    <label style="font-weight: normal;">
                                        <input type="radio" name="rdbUserType">Staff
                                    </label>&nbsp;&nbsp;
                                    <label style="font-weight: normal;">
                                        <input type="radio" name="rdbUserType" checked="checked">Student
                                    </label>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="Class" class="col-lg-2 control-label">Class</label>
                                <div class="col-lg-4">
									<select class="form-control" name="drdClass" id="Class">
                                        <option value="1">1</option>
                                        <option value="1">2</option>
                                        <option value="1" >3</option>
                                        <option value="1">4</option>
                                        <option value="1" selected="selected">5</option>
                                        <option value="1">6</option>
                                        <option value="1">7</option>
                                        <option value="1">8</option>
									</select>
                                </div>
                                <label for="Section" class="col-lg-2 control-label">Section</label>
                                <div class="col-lg-4">
                                    <select class="form-control" name="drdSection" id="Section">
                                        <option value="1" selected="selected">A</option>
                                        <option value="2">B</option>
                                        <option value="3">C</option>
                                        <option value="4">D</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="FrequencyType" class="col-lg-2 control-label">Type</label>
                                <div class="col-lg-4">
                                    <label style="font-weight: normal;">
                                        <input type="radio" name="rdbType" value="Present">&nbsp;Present
                                    </label>&nbsp;&nbsp;    
                                    <label style="font-weight: normal;">
                                        <input type="radio" name="rdbType" value="Absent" checked="checked">&nbsp;Absent
                                    </label>
                                </div>
                                <label for="FrequencyValue" class="col-lg-2 control-label">Period Type</label>
                                <div class="col-lg-4">
                                    <select class="form-control" name="drdSection" id="Section">
                                        <option value="SpecifiedPeriod" selected="selected">Specified Period</option>
                                        <option value="LastWeek">Last Week</option>
                                        <option value="LastMonth">LastMonth</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="DatePeriod" class="col-lg-2 control-label">Date Period</label>
                                <div class="col-lg-4" style="padding: 0;">
                                    <div class="col-lg-5">
                                        <input class="form-control" type="text" name="FromDatePeriod" value="">
                                    </div>
                                    <div class="col-lg-2">To</div>
                                    <div class="col-lg-5">
                                        <input class="form-control" type="text" name="ToDatePeriod" value="">
                                    </div>
                                </div>
                                <label for="FrequencyValue" class="col-lg-2 control-label">Rule</label>
                                <div class="col-lg-4">
                                    <input class="form-control" type="text" name="FrequencyValue" value="10">
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
                            <strong>Frequency Analysis</strong>
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
                                        <div class="report-heading-container"><strong>Frequency Analysis on <?php echo date('d-m-Y h:i A'); ?></strong></div>
                                    </div>
                                </div>
                                <div class="row" id="RecordTable">
                                    <div class="col-lg-12" style="overflow: auto;">
                                        <table width="100%" class="table table-striped table-bordered table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Student Name</th>
                                                    <th>Total Days</th>
                                                    <th>Present</th>
                                                    <th>Absent</th>
                                                    <th>Attendane Percentage</th>
                                                </tr>
                                            </thead>
                                            <tbody id="TableData">
                                                <tr>
                                                    <td>Nimit Gupta</td>
                                                    <td>110</td>
                                                    <td>70</td>
                                                    <td>40</td>
                                                    <td>63.6</td>
                                                </tr>
                                                <!-- <tr>
                                                    <td>Azam Khan</td>
                                                    <td>110</td>
                                                    <td>55</td>
                                                    <td>55</td>
                                                    <td>50</td>
                                                </tr>
                                                <tr>
                                                    <td>Sachin Agnihotri</td>
                                                    <td>110</td>
                                                    <td>55</td>
                                                    <td>55</td>
                                                    <td>50</td>
                                                </tr> -->
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
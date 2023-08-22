<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once("../../classes/hra/class.leave_deduction_rule.php");

require_once("../../includes/helpers.inc.php");
require_once("../../includes/global_defaults.inc.php");

//1. RECHECK IF THE USER IS VALID //
try
{
	$AuthObject = new ApplicationAuthentication;
	$LoggedUser = new User(0, $AuthObject->CheckValidUser());
}

// THIS CATCH BLOCK BUBBLES THE EXCEPTION TO THE BUILT IN 'Exception' CLASS IF THERE ARE ANY UNCAUGHT ERRORS //
catch (ApplicationAuthException $e)
{
	header('location:unauthorized_login_admin.php');
	exit;
}
catch (Exception $e)
{
	header('location:unauthorized_login_admin.php');
	exit;
}
// END OF 1. //

if ($LoggedUser->HasPermissionForTask(TASK_ADD_LEAVE_DEDUCTION_RULE) !== true)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}

$HasErrors = false;

$Clean = array();
$Clean['Process'] = 0;

$Clean['LeaveDeductionRuleID'] = 0;

if (isset($_GET['Process']))
{
	$Clean['Process'] = (int) $_GET['Process'];
}
switch ($Clean['Process'])
{
	case 5:
		if ($LoggedUser->HasPermissionForTask(TASK_DELETE_LEAVE_DEDUCTION_RULE) !== true)
		{
			header('location:unauthorized_login_admin.php');
			exit;
		}
		
		if (isset($_GET['LeaveDeductionRuleID']))
		{
			$Clean['LeaveDeductionRuleID'] = (int) $_GET['LeaveDeductionRuleID'];			
		}
		
		if ($Clean['LeaveDeductionRuleID'] <= 0)
		{
			header('location:../error.php');
			exit;
		}						
			
		try
		{
			$LeaveDeductionRuleToDelete = new LeaveDeductionRule($Clean['LeaveDeductionRuleID']);
		}
		catch (ApplicationDBException $e)
		{
			header('location:../error.php');
			exit;
		}
		catch (Exception $e)
		{
			header('location:../error.php');
			exit;
		}
		
		$RecordValidator = new Validator();
				
		if (!$LeaveDeductionRuleToDelete->Remove())
		{
			$RecordValidator->AttachTextError(ProcessErrors($LeaveDeductionRuleToDelete->GetLastErrorCode()));
			$HasErrors = true;
			break;
		}
		
		$RecordDeletedSuccessfully = true;
	    break;
}

$AllLeaveDeductionRules = array();
$AllLeaveDeductionRules = LeaveDeductionRule::GetAllLeaveDeductionRules();

require_once('../html_header.php');
?>
<title>Leave Deduction Rules List</title>
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
			require_once('../site_header.php');
            require_once('../left_navigation_menu.php');
?>                    
            <!-- /.navbar-static-side -->
        </nav>

        <div id="page-wrapper">
            <div class="row">
                <div class="col-lg-12">
                    <h1 class="page-header">Leave Deduction Rules List</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
            <!-- /.row -->
            <div class="row">
                <div class="col-lg-12">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <strong>Total Records Returned: <?php echo count($AllLeaveDeductionRules); ?></strong>
                        </div>
                        <!-- /.panel-heading -->
                        <div class="panel-body">
                            <div>
                                <div class="row">
                                    <div class="col-lg-6">
                                    <div class="add-new-btn-container"><a href="add_leave_deduction_rule.php" class="btn btn-primary<?php echo $LoggedUser->HasPermissionForTask(TASK_ADD_LEAVE_DEDUCTION_RULE) === true ? '' : ' disabled'; ?>" role="button">Add Leave Deduction Rule</a></div>
                                    </div>
                                    <div class="col-lg-6">
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
                                    	<div class="report-heading-container"><strong>Leave Deduction Rules on <?php echo date('d-m-Y h:i A'); ?></strong></div>
                                    </div>
								</div>
                                <div class="row" id="RecordTable">
                                    <div class="col-lg-12">
                                        <table width="100%" class="table table-striped table-bordered table-hover" id="DataTableRecords">
                                            <thead>
                                                <tr>
                                                    <th>S. No</th>
                                                    <th>Rule Type</th>
                                                    <th>Staff Category</th>
                                                    <th>Create User</th>
                                                    <th>Create Date</th>
                                                    <th class="print-hidden">Operations</th>
                                                </tr>
                                            </thead>
                                            <tbody>
    <?php
                                    if (is_array($AllLeaveDeductionRules) && count($AllLeaveDeductionRules) > 0)
                                    {
                                        $Counter = 0;
                                        foreach ($AllLeaveDeductionRules as $LeaveDeductionRuleID => $LeaveDeductionRuleDetails)
                                        {
    ?>
                                                <tr>
                                                    <td><?php echo ++$Counter; ?></td>
                                                    <td><?php echo ($LeaveDeductionRuleDetails['RuleType'] == 'FullDay') ? 'Full Day' : 'Half Day'; ?></td>
                                                    <td><?php echo ($LeaveDeductionRuleDetails['StaffCategory'] == 'Teaching') ? 'Teaching Staff' : 'Non Teaching Staff'; ?></td>
                                                    <td><?php echo $LeaveDeductionRuleDetails['CreateUserName']; ?></td>
                                                    <td><?php echo date('d/m/Y', strtotime($LeaveDeductionRuleDetails['CreateDate'])); ?></td>
                                                    <td class="print-hidden">
<?php
                                                        if ($LoggedUser->HasPermissionForTask(TASK_EDIT_LEAVE_DEDUCTION_RULE) === true)
                                                        {
                                                            echo '<a href="edit_leave_deduction_rule.php?Process=2&amp;LeaveDeductionRuleID='. $LeaveDeductionRuleID .'">Edit</a>';
                                                        }
                                                        else
                                                        {
                                                            echo 'Edit';
                                                        }

                                                        echo '&nbsp;|&nbsp;';

                                                        if ($LoggedUser->HasPermissionForTask(TASK_DELETE_LEAVE_DEDUCTION_RULE) === true)
                                                        {
                                                            echo '<a class="delete-record" href="leave_deduction_rules_list.php?Process=5&amp;LeaveDeductionRuleID=' . $LeaveDeductionRuleID . '">Delete</a>'; 
                                                        }
                                                        else
                                                        {
                                                            echo 'Delete';
                                                        }
                                                        
                                                        echo '&nbsp;|&nbsp;';

                                                        if ($LoggedUser->HasPermissionForTask(TASK_VIEW_LEAVE_DEDUCTION_RULE) === true)
                                                        {
                                                            echo '<a href="edit_leave_deduction_rule.php?Process=2&amp;LeaveDeductionRuleID=' . $LeaveDeductionRuleID . '&amp;ViewOnly=true" target="_blank">View</a>';
                                                        }
                                                        else
                                                        {
                                                            echo 'View';
                                                        }
?>
                                                    </td>
                                                </tr>
    <?php
                                        }
                                    }
                                    else
                                    {
    ?>
                                                <tr>
                                                    <td colspan="7">No Records</td>
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
        </div>
        <!-- /#page-wrapper -->

    </div>
    <!-- /#wrapper -->
<?php
require_once('../footer.php');
if (PrintMessage($_GET, $Message))
{
?>
<script type="text/javascript">
    alert('<?php echo $Message; ?>');
</script>
<?php
}
?>
<!-- DataTables JavaScript -->
<script src="/admin/vendor/datatables/js/jquery.dataTables.min.js"></script>
<script src="/admin/vendor/datatables-plugins/dataTables.bootstrap.min.js"></script>
<script src="/admin/vendor/datatables-responsive/dataTables.responsive.js"></script>	
<script type="text/javascript">
$(document).ready(function() {
    $("body").on('click', '.delete-record', function()
    {   
        if (!confirm("Are you sure you want to delete this leave deduction rule"))
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
<!-- JavaScript To Print A Report -->
<script src="/admin/js/print-report.js"></script>
</body>
</html>
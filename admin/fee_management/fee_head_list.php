<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once("../../classes/fee_management/class.fee_heads.php");

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
	header('location:../unauthorized_login_admin.php');
	exit;
}
catch (Exception $e)
{
	header('location:../unauthorized_login_admin.php');
	exit;
}
// END OF 1. //

if ($LoggedUser->HasPermissionForTask(TASK_LIST_FEE_HEAD) !== true)
{
    header('location:/admin/unauthorized_login_admin.php');
    exit;
}

$UserMenusArray = array();
$UserMenusArray = $LoggedUser->GetUserMenus();

if (!is_array($UserMenusArray) || count($UserMenusArray) <= 0)
{
	//header('location:../logout.php');
	//exit;
}

$HasErrors = false;
$RecordDeletedSuccessfully = false;

$Clean = array();
$Clean['Process'] = 0;

$Clean['FeeHeadID'] = 0;

if (isset($_GET['Process']))
{
	$Clean['Process'] = (int) $_GET['Process'];
}
switch ($Clean['Process'])
{
	case 5:
		if ($LoggedUser->HasPermissionForTask(TASK_DELETE_FEE_HEAD) !== true)
		{
			header('location:unauthorized_login_admin.php');
			exit;
		}
		
		if (isset($_GET['FeeHeadID']))
		{
			$Clean['FeeHeadID'] = (int) $_GET['FeeHeadID'];			
		}
		
		if ($Clean['FeeHeadID'] <= 0)
		{
			header('location:../error_page.php');
			exit;
		}						
			
		try
		{
			$FeeHeadToDelete = new FeeHead($Clean['FeeHeadID']);
		}
		catch (ApplicationDBException $e)
		{
			header('location:../error_page.php');
			exit;
		}
		catch (Exception $e)
		{
			header('location:../error_page.php');
			exit;
		}
		
		$RecordValidator = new Validator();
		
		if ($FeeHeadToDelete->CheckDependencies())
		{
			$RecordValidator->AttachTextError('This fee head cannot be deleted. There are dependent records for this head.');
			$HasErrors = true;
			break;
		}
				
		if (!$FeeHeadToDelete->Remove())
		{
			$RecordValidator->AttachTextError(ProcessErrors($FeeHeadToDelete->GetLastErrorCode()));
			$HasErrors = true;
			break;
		}
		
		$RecordDeletedSuccessfully = true;
	break;
}

$AllFeeHeads = array();
$AllFeeHeads = FeeHead::GetAllFeeHeads();

$LandingPageMode = '';
if (isset($_GET['Mode']))
{
    $LandingPageMode = $_GET['Mode'];
}

require_once('../html_header.php');
?>
<title>Fee Head List</title>
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
                    <h1 class="page-header">Fee Head List</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
            <!-- /.row -->
            <div class="row">
                <div class="col-lg-12">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <strong>Total Records Returned: <?php echo count($AllFeeHeads); ?></strong>
                        </div>
                        <!-- /.panel-heading -->
                        <div class="panel-body">
                            <div>
                                <div class="row">
                                    <div class="col-lg-6">
                                    <div class="add-new-btn-container"><a href="add_fee_head.php" class="btn btn-primary<?php echo $LoggedUser->HasPermissionForTask(TASK_ADD_EDIT_FEE_HEAD) === true ? '' : ' disabled'; ?>" role="button">Add New Fee Head</a></div>
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
                            else if ($LandingPageMode == 'AS')
                            {
                                echo '<div class="alert alert-success alert-top-margin">Record saved successfully.</div>';
                            }
                            else if ($RecordDeletedSuccessfully == true)
                            {
                                echo '<div class="alert alert-danger alert-top-margin">Record deleted successfully.</div>';
                            }
                            else if ($LandingPageMode == 'UD')
                            {
                                echo '<div class="alert alert-success alert-top-margin">Record updated successfully.</div>';
                            }
?>
                                <div class="row" id="RecordTableHeading">
                                    <div class="col-lg-12">
                                    	<div class="report-heading-container"><strong>Fee Head Details on <?php echo date('d-m-Y h:i A'); ?></strong></div>
                                    </div>
								</div>
                                <div class="row" id="RecordTable">
                                    <div class="col-lg-12">
                                        <table width="100%" class="table table-striped table-bordered table-hover">
                                            <thead>
                                                <tr>
                                                    <th>S. No</th>
                                                    <th>Fee Head</th>
                                                    <th>Description</th>
                                                    <th>Is SystemGenerated</th>
                                                    <th>Priority</th>
                                                    <th>Is Active</th>
                                                    <th>Create User</th>
                                                    <th>Create Date</th>
                                                    <th class="print-hidden">Operations</th>
                                                </tr>
                                            </thead>
                                            <tbody>
<?php
                                    if (is_array($AllFeeHeads) && count($AllFeeHeads) > 0)
                                    {
                                        $Counter = 0;
                                        foreach ($AllFeeHeads as $FeeHeadID => $FeeHeadDetails)
                                        {
?>
                                            <tr>
                                                <td><?php echo ++$Counter; ?></td>
                                                <td><?php echo $FeeHeadDetails['FeeHead']; ?></td>
                                                <td><?php echo $FeeHeadDetails['FeeHeadDescription']; ?></td>
                                                <td><?php echo (($FeeHeadDetails['IsSystemGenerated']) ? 'Yes' : 'No'); ?></td>
                                                <td><?php echo $FeeHeadDetails['Priority']; ?></td>
                                                <td><?php echo (($FeeHeadDetails['IsActive']) ? 'Yes' : 'No'); ?></td>
                                                <td><?php echo $FeeHeadDetails['CreateUserName']; ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($FeeHeadDetails['CreateDate'])); ?></td>
                                                <td class="print-hidden">
<?php
                                                if ($LoggedUser->HasPermissionForTask(TASK_ADD_EDIT_FEE_HEAD) === true)
                                                {
                                                    echo (($FeeHeadDetails['IsSystemGenerated']) ? 'Not Editable' : '<a href="edit_fee_head.php?Process=2&amp;FeeHeadID=' . $FeeHeadID . '">Edit</a>');
                                                }
                                                else
                                                {
                                                    echo 'Edit';
                                                }

                                                echo '&nbsp;|&nbsp;';

                                                if ($LoggedUser->HasPermissionForTask(TASK_DELETE_FEE_HEAD) === true)
                                                {
                                                    echo (($FeeHeadDetails['IsSystemGenerated']) ? 'Not Deleted' : '<a href="fee_head_list.php?Process=5&amp;FeeHeadID=' . $FeeHeadID . '" class="delete-record">Delete</a>'); 
                                                }
                                                else
                                                {
                                                    echo 'Delete';
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
                                            <td colspan="9">No Records</td>
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
?>
	
<script type="text/javascript">
$(document).ready(function() {
	$(".delete-record").click(function()
    {	
        if (!confirm("Are you sure you want to delete this Fee Head?")) 
        {
            return false;
        }
    });
});
</script>
<!-- JavaScript To Print A Report -->
<script src="/admin/js/print-report.js"></script>
</body>
</html>
<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once("../../classes/fee_management/class.fee_groups.php");

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

if ($LoggedUser->HasPermissionForTask(TASK_LIST_FEE_GROUP) !== true)
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

$Clean['FeeGroupID'] = 0;

if (isset($_GET['Process']))
{
	$Clean['Process'] = (int) $_GET['Process'];
}
switch ($Clean['Process'])
{
	case 5:
		if ($LoggedUser->HasPermissionForTask(TASK_DELETE_FEE_GROUP) !== true)
		{
			header('location:unauthorized_login_admin.php');
			exit;
		}
		
		if (isset($_GET['FeeGroupID']))
		{
			$Clean['FeeGroupID'] = (int) $_GET['FeeGroupID'];			
		}
		
		if ($Clean['FeeGroupID'] <= 0)
		{
			header('location:../error_page.php');
			exit;
		}						
			
		try
		{
			$FeeGroupToDelete = new FeeGroup($Clean['FeeGroupID']);
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
		
		if ($FeeGroupToDelete->CheckDependencies())
		{
			$RecordValidator->AttachTextError('This fee group cannot be deleted. There are dependent records for this task group.');
			$HasErrors = true;
			break;
		}
				
		if (!$FeeGroupToDelete->Remove())
		{
			$RecordValidator->AttachTextError(ProcessErrors($FeeGroupToDelete->GetLastErrorCode()));
			$HasErrors = true;
			break;
		}
		
		$RecordDeletedSuccessfully = true;
	break;
}

$AllFeeGroups = array();
$AllFeeGroups = FeeGroup::GetAllFeeGroups();

$LandingPageMode = '';
if (isset($_GET['Mode']))
{
    $LandingPageMode = $_GET['Mode'];
}

require_once('../html_header.php');
?>
<title>Fee Group List</title>
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
                    <h1 class="page-header">Fee Group List</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
            <!-- /.row -->
            <div class="row">
                <div class="col-lg-12">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <strong>Total Records Returned: <?php echo count($AllFeeGroups); ?></strong>
                        </div>
                        <!-- /.panel-heading -->
                        <div class="panel-body">
                            <div>
                                <div class="row">
                                    <div class="col-lg-6">
                                    <div class="add-new-btn-container"><a href="add_fee_group.php" class="btn btn-primary<?php echo $LoggedUser->HasPermissionForTask(TASK_ADD_EDIT_FEE_GROUP) === true ? '' : ' disabled'; ?>" role="button">Add New Fee Group</a></div>
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
                                    	<div class="report-heading-container"><strong>Fee Group List on <?php echo date('d-m-Y h:i A'); ?></strong></div>
                                    </div>
								</div>
                                <div class="row" id="RecordTable">
                                    <div class="col-lg-12">
                                        <table width="100%" class="table table-striped table-bordered table-hover">
                                            <thead>
                                                <tr>
                                                    <th>S. No</th>
                                                    <th>Fee Group Name</th>                                                    
                                                    <th>Is Active</th>
                                                    <th>Created by</th>
                                                    <th>Created Date</th>
                                                    <th class="print-hidden">Operations</th>
                                                </tr>
                                            </thead>
                                            <tbody>
<?php
                                    if (is_array($AllFeeGroups) && count($AllFeeGroups) > 0)
                                    {
                                        $Counter = 0;
                                        foreach ($AllFeeGroups as $FeeGroupID => $FeeGroupDetails)
                                        {
?>
                                            <tr>
                                                <td><?php echo ++$Counter; ?></td>
                                                <td><?php echo $FeeGroupDetails['FeeGroup']; ?></td>
                                                <td><?php echo (($FeeGroupDetails['IsActive']) ? 'Yes' : 'No'); ?></td>
                                                <td><?php echo $FeeGroupDetails['CreateUserName']; ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($FeeGroupDetails['CreateDate'])); ?></td>
                                                <td class="print-hidden">
<?php
                                                if ($LoggedUser->HasPermissionForTask(TASK_ASSIGN_GROUP_TO_STUDENTS) === true)
                                                {
                                                    echo '<a href="assigned_student_wise_fee_group.php?Process=2&amp">'.($FeeGroupDetails['TotalRecords'] > 0 ? 'Change Students' : 'Assign Students').'</a>';
                                                }
                                                else
                                                {
                                                    echo 'Assign Students';
                                                }

                                                echo '&nbsp;|&nbsp;';

                                                if ($LoggedUser->HasPermissionForTask(TASK_ADD_EDIT_FEE_GROUP) === true)
                                                {
                                                    echo '<a href="edit_fee_group.php?Process=2&amp;FeeGroupID=' . $FeeGroupID . '">Edit</a>';
                                                    echo '&nbsp|&nbsp';
                                                }
                                                else
                                                {
                                                    echo 'Edit';
                                                }

                                                if ($LoggedUser->HasPermissionForTask(TASK_DELETE_FEE_GROUP) === true)
                                                {
                                                    echo '<a class="delete-record" href="fee_group_list.php?Process=5&amp;FeeGroupID=' . $FeeGroupID . '">Delete</a>';
                                                }
                                                else
                                                {
                                                    echo 'Delete';
                                                }
?>                                               </td>
                                            </tr>
<?php
                                        }
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
            if (!confirm("Are you sure you want to delete this Fee Group?"))
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
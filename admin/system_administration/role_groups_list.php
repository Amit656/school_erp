<?php
require_once('../../classes/class.users.php');
require_once('../../classes/class.validation.php');
require_once('../../classes/class.authentication.php');

require_once('../../classes/class.role_groups.php');

require_once('../../classes/class.ui_helpers.php');

require_once('../../includes/global_defaults.inc.php');

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

if ($LoggedUser->HasPermissionForTask(TASK_ROLE_GROUP_LIST) !== true)
{
	header('location:../unauthorized_login_admin.php');
	exit;
}

$HasErrors = false;

$Clean = array();
$Clean['Process'] = 0;

$Clean['RoleGroupID'] = 0;

if (isset($_GET['Process']))
{
	$Clean['Process'] = (int) $_GET['Process'];
}
switch ($Clean['Process'])
{
	case 5:
		if ($LoggedUser->HasPermissionForTask(TASK_DELETE_ROLE_GROUP) !== true)
		{
			header('location:../unauthorized_login_admin.php');
			exit;
		}
		
		if (isset($_GET['RoleGroupID']))
		{
			$Clean['RoleGroupID'] = (int) $_GET['RoleGroupID'];			
		}
		
		if ($Clean['RoleGroupID'] <= 0)
		{
			header('location:../error_page.php');
			exit;
		}						
			
		try
		{
			$RoleGroupToDelete = new RoleGroup($Clean['RoleGroupID']);
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
		
		if ($RoleGroupToDelete->CheckDependencies())
		{
			$RecordValidator->AttachTextError('This role group cannot be deleted. There are dependent records for this role group.');
			$HasErrors = true;
			break;
		}
				
		if (!$RoleGroupToDelete->Remove())
		{
			$RecordValidator->AttachTextError(ProcessErrors($RoleGroupToDelete->GetLastErrorCode()));
			$HasErrors = true;
			break;
		}
		
		header('location:role_groups_list.php?POMode=RD');
		exit;
	break;
}

$AllRoleGroups = array();
$AllRoleGroups = RoleGroup::GetAllRoleGroups();

require_once('../html_header.php');
?>
<title>Role Group List</title>
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
                    <h1 class="page-header">Role Group List</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
            <!-- /.row -->
            <div class="row">
                <div class="col-lg-12">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <strong>Total Records Returned: <?php echo count($AllRoleGroups); ?></strong>
                        </div>
                        <!-- /.panel-heading -->
                        <div class="panel-body">
                            <div>
                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="add-new-btn-container"><a href="add_role_group.php" class="btn btn-primary<?php echo $LoggedUser->HasPermissionForTask(TASK_ADD_ROLE_GROUP) === true ? '' : ' disabled'; ?>" role="button">Add New Role Group</a></div>
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
                                    	<div class="report-heading-container"><strong>Role Group List on <?php echo date('d-m-Y h:i A'); ?></strong></div>
                                    </div>
								</div>
                                <div class="row" id="RecordTable">
                                    <div class="col-lg-12">
                                        <table width="100%" class="table table-striped table-bordered table-hover" id="DataTableRecords">
                                            <thead>
                                                <tr>
                                                    <th>S. No</th>
                                                    <th>Role Group Name</th>
                                                    <th>Is Active</th>
                                                    <th>Create User</th>
                                                    <th>Create Date</th>
                                                    <th class="print-hidden">Operations</th>
                                                </tr>
                                            </thead>
                                            <tbody>
    <?php
                                    if (is_array($AllRoleGroups) && count($AllRoleGroups) > 0)
                                    {
                                        $Counter = 0;
                                        foreach ($AllRoleGroups as $RoleGroupID => $RoleGroupDetails)
                                        {
    ?>
                                                <tr>
                                                    <td><?php echo ++$Counter; ?></td>
                                                    <td><?php echo $RoleGroupDetails['RoleGroupName']; ?></td>
                                                    <td><?php echo (($RoleGroupDetails['IsActive']) ? 'Yes' : 'No'); ?></td>
                                                    <td><?php echo $RoleGroupDetails['CreateUserName']; ?></td>
                                                    <td><?php echo date('d/m/Y', strtotime($RoleGroupDetails['CreateDate'])); ?></td>
                                                    <td class="print-hidden">
<?php
                                                    if ($LoggedUser->HasPermissionForTask(TASK_EDIT_ROLE_GROUP) === true)
                                                    {
                                                        echo '<a href="edit_role_group.php?Process=2&amp;RoleGroupID=' . $RoleGroupID . '">Edit</a>';
                                                    }
                                                    else
                                                    {
                                                        echo 'Edit';
                                                    }

                                                    echo '&nbsp;|&nbsp;';

                                                    if ($LoggedUser->HasPermissionForTask(TASK_DELETE_ROLE_GROUP) === true)
                                                    {
                                                        echo '<a class="delete-record" href="role_groups_list.php?Process=5&amp;RoleGroupID=' . $RoleGroupID . '">Delete</a>';
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
                                                    <td colspan="6">No Records</td>
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
            if (!confirm("Are you sure you want to delete this role group?"))
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
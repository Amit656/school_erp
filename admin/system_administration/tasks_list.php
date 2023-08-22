<?php
require_once('../../classes/class.users.php');
require_once('../../classes/class.validation.php');
require_once('../../classes/class.authentication.php');

require_once('../../classes/class.task_groups.php');
require_once('../../classes/class.tasks.php');

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

if ($LoggedUser->HasPermissionForTask(TASK_TASK_LIST) !== true)
{
	header('location:../unauthorized_login_admin.php');
	exit;
}

$AllTaskGroups = array();
$AllTaskGroups = TaskGroup::GetActiveTaskGroups();

$AllTasks = array();

$HasErrors = false;
$HasSearchErrors = false;

$Clean = array();
$Clean['Process'] = 0;

$Clean['TaskID'] = 0;

$Clean['TaskGroupID'] = 0;
$Clean['TaskGroupID'] = key($AllTaskGroups);

if (isset($_GET['hdnProcess']))
{
	$Clean['Process'] = (int) $_GET['hdnProcess'];
}
else if (isset($_GET['Process']))
{
	$Clean['Process'] = (int) $_GET['Process'];
}
switch ($Clean['Process'])
{
	case 5:
		if ($LoggedUser->HasPermissionForTask(TASK_DELETE_TASK) !== true)
		{
			header('location:../unauthorized_login_admin.php');
			exit;
		}
		
		if (isset($_GET['TaskID']))
		{
			$Clean['TaskID'] = (int) $_GET['TaskID'];			
		}
		
		if ($Clean['TaskID'] <= 0)
		{
			header('location:../error_page.php');
			exit;
		}						
			
		try
		{
            $TaskToDelete = new Task($Clean['TaskID']);
            $Clean['TaskGroupID'] = $TaskToDelete->GetTaskGroupID();
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
        
        $AllTasks = Task::GetAllTasks($Clean['TaskGroupID']);
		
		$RecordValidator = new Validator();
		
		if ($TaskToDelete->CheckDependencies())
		{
			$RecordValidator->AttachTextError('This task cannot be deleted. There are dependent records for this task.');
			$HasErrors = true;
			break;
		}

		if (!$TaskToDelete->Remove())
		{
			$RecordValidator->AttachTextError(ProcessErrors($TaskToDelete->GetLastErrorCode()));
			$HasErrors = true;
			break;
		}
		
		header('location:tasks_list.php?POMode=RD&Process=7&TaskGroupID=' . $Clean['TaskGroupID']);
		exit;
    break;
    
    case 7:
		if (isset($_GET['drdTaskGroup']))
		{
			$Clean['TaskGroupID'] = (int) $_GET['drdTaskGroup'];
		}
		elseif (isset($_GET['TaskGroupID']))
		{
			$Clean['TaskGroupID'] = (int) $_GET['TaskGroupID'];
		}

		$SearchValidator = new Validator();

		if ($Clean['TaskGroupID'] != 0)
		{
			$SearchValidator->ValidateInSelect($Clean['TaskGroupID'], $AllTaskGroups, 'Unknown Error, Please try again.');
		}
		
		if ($SearchValidator->HasNotifications())
		{
			$HasSearchErrors = true;
			break;
		}

		$AllTasks = Task::GetAllTasks($Clean['TaskGroupID']);
        break;
        
    default:
		$AllTasks = Task::GetAllTasks($Clean['TaskGroupID']);
		break;
}

require_once('../html_header.php');
?>
<title>Task List</title>
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
                    <h1 class="page-header">Task List</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
            <form class="form-horizontal" name="frmUserReport" action="tasks_list.php" method="get">
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
                                <label for="TaskGroup" class="col-lg-2 control-label">Task Group</label>
                                <div class="col-lg-4">
									<select class="form-control" name="drdTaskGroup" id="TaskGroup">
<?php
							if (is_array($AllTaskGroups) && count($AllTaskGroups) > 0)
							{
								foreach($AllTaskGroups as $TaskGroupID => $TaskGroupName)
								{
									if ($Clean['TaskGroupID'] != $TaskGroupID)
									{
?>
										<option value="<?php echo $TaskGroupID; ?>"><?php echo $TaskGroupName; ?></option>
<?php
									}
									else
									{
?>
										<option selected="selected" value="<?php echo $TaskGroupID; ?>"><?php echo $TaskGroupName; ?></option>
<?php
									}
								}
							}
?>
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
<?php
		if ($HasSearchErrors == false)
		{
?>
            <div class="row">
                <div class="col-lg-12">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <strong>Total Records Returned: <?php echo count($AllTasks); ?></strong>
                        </div>
                        <!-- /.panel-heading -->
                        <div class="panel-body">
                            <div>
                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="add-new-btn-container"><a href="add_task.php?TaskGroupID=<?php echo $Clean['TaskGroupID']; ?>" class="btn btn-primary<?php echo $LoggedUser->HasPermissionForTask(TASK_ADD_TASK) === true ? '' : ' disabled'; ?>" role="button">Add New Task</a></div>
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
                                    	<div class="report-heading-container"><strong>Task List on <?php echo date('d-m-Y h:i A'); ?></strong></div>
                                    </div>
								</div>
                                <div class="row" id="RecordTable">
                                    <div class="col-lg-12">
                                        <table width="100%" class="table table-striped table-bordered table-hover" id="DataTableRecords">
                                            <thead>
                                                <tr>
                                                    <th>S. No</th>
                                                    <th>Task ID</th>
                                                    <th>Task Group Name</th>
                                                    <th>Task Name</th>
                                                    <th>Is Active</th>
                                                    <th>Create User</th>
                                                    <th>Create Date</th>
                                                    <th class="print-hidden">Operations</th>
                                                </tr>
                                            </thead>
                                            <tbody>
<?php
                                    if (is_array($AllTasks) && count($AllTasks) > 0)
                                    {
                                        $Counter = 0;
                                        foreach ($AllTasks as $TaskID => $TaskDetails)
                                        {
?>
                                                <tr>
                                                    <td><?php echo ++$Counter; ?></td>
                                                    <td><?php echo $TaskID; ?></td>
                                                    <td><?php echo $TaskDetails['TaskGroupName']; ?></td>
                                                    <td><?php echo $TaskDetails['TaskName']; ?></td>
                                                    <td><?php echo (($TaskDetails['IsActive']) ? 'Yes' : 'No'); ?></td>
                                                    <td><?php echo $TaskDetails['CreateUserName']; ?></td>
                                                    <td><?php echo date('d/m/Y', strtotime($TaskDetails['CreateDate'])); ?></td>
                                                    <td class="print-hidden">
<?php
                                                    if ($LoggedUser->HasPermissionForTask(TASK_EDIT_TASK) === true)
                                                    {
                                                        echo '<a href="edit_task.php?Process=2&amp;TaskID=' . $TaskID . '">Edit</a>';
                                                    }
                                                    else
                                                    {
                                                        echo 'Edit';
                                                    }

                                                    echo '&nbsp;|&nbsp;';

                                                    if ($LoggedUser->HasPermissionForTask(TASK_DELETE_TASK) === true)
                                                    {
                                                        echo '<a class="delete-record" href="tasks_list.php?Process=5&amp;TaskID=' . $TaskID . '">Delete</a>';
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
                                                    <td colspan="8">No Records</td>
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
<?php
		}
?>
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
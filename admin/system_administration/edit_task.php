<?php
require_once('../../classes/class.users.php');
require_once('../../classes/class.validation.php');
require_once('../../classes/class.authentication.php');

require_once('../../classes/class.task_groups.php');
require_once('../../classes/class.tasks.php');

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

if ($LoggedUser->HasPermissionForTask(TASK_EDIT_TASK) !== true)
{
	header('location:../unauthorized_login_admin.php');
	exit;
}

if (isset($_POST['btnCancel']))
{
	header('location:tasks_list.php');
	exit;
}

$Clean = array();

$Clean['TaskID'] = 0;

if (isset($_GET['TaskID']))
{
    $Clean['TaskID'] = (int) $_GET['TaskID'];
}
else if (isset($_POST['hdnTaskID']))
{
    $Clean['TaskID'] = (int) $_POST['hdnTaskID'];
}

if ($Clean['TaskID'] <= 0)
{
    header('location:../error.php');
    exit;
}

try
{
	$TaskToEdit = new Task($Clean['TaskID']);
}
catch (ApplicationDBException $e)
{
    header('location:../error.php');
    exit;
}
catch (Exception $e)
{
    header('location:../error.php');
}

$AllTaskGroups = array();
$AllTaskGroups = TaskGroup::GetActiveTaskGroups();

$HasErrors = false;

$Clean['Process'] = 0;

$Clean['TaskGroupID'] = 0;
$Clean['TaskName'] = '';
$Clean['IsActive'] = 1;

if (isset($_POST['hdnProcess']))
{
	$Clean['Process'] = (int) $_POST['hdnProcess'];
}
else if (isset($_GET['Process']))
{
	$Clean['Process'] = (int) $_GET['Process'];
}
switch ($Clean['Process'])
{
	case 3:
		if (isset($_POST['drdTaskGroup']))
		{
			$Clean['TaskGroupID'] = (int) $_POST['drdTaskGroup'];
		}

		if (isset($_POST['txtTaskName']))
		{
			$Clean['TaskName'] = strip_tags(trim($_POST['txtTaskName']));
		}

		if (!isset($_POST['chkIsActive']))
		{
			$Clean['IsActive'] = 0;
		}
		
		$NewRecordValidator = new Validator();
		$NewRecordValidator->ValidateInSelect($Clean['TaskGroupID'], $AllTaskGroups, 'Unknown Error, Please try again.');
		$NewRecordValidator->ValidateStrings($Clean['TaskName'], 'Task name is required and should be between 4 and 30 characters.', 4, 30);
		
		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}

		$TaskToEdit->SetTaskGroupID($Clean['TaskGroupID']);
		$TaskToEdit->SetTaskName($Clean['TaskName']);
		$TaskToEdit->SetIsActive($Clean['IsActive']);
		
		if ($TaskToEdit->RecordExists())
		{
			$NewRecordValidator->AttachTextError('The task name you have added already exists.');
			$HasErrors = true;
			break;
		}

		if (!$TaskToEdit->Save())
		{
			$NewRecordValidator->AttachTextError(ProcessErrors($TaskToEdit->GetLastErrorCode()));
			$HasErrors = true;
			break;
		}
		
		header('location:tasks_list.php?POMode=RU&Process=7&TaskGroupID=' . $Clean['TaskGroupID']);
		exit;
	break;

	case 2:
		$Clean['TaskGroupID'] = $TaskToEdit->GetTaskGroupID();
		$Clean['TaskName'] = $TaskToEdit->GetTaskName();
		$Clean['IsActive'] = $TaskToEdit->GetIsActive();
	break;
}

require_once('../html_header.php');
?>
<title>Edit Task</title>
<link href="/admin/vendor/jquery-ui/jquery-ui.min.css" rel="stylesheet">
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
                    <h1 class="page-header">Edit Task</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="AddTask" action="edit_task.php" method="post">
            	<div class="panel panel-default">
                    <div class="panel-heading">
                        Enter Task Details
                    </div>
                    <div class="panel-body">
<?php
						if ($HasErrors == true)
						{
							echo $NewRecordValidator->DisplayErrors();
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
											echo '<option value="' . $TaskGroupID . '">' . $TaskGroupName . '</option>';
										}
										else
										{
											echo '<option selected="selected" value="' . $TaskGroupID . '">' . $TaskGroupName . '</option>';
										}
									}
								}
?>
								</select>
                            </div>
                        </div>
						<div class="form-group">
                            <label for="TaskName" class="col-lg-2 control-label">Task Name</label>
                            <div class="col-lg-4">
                            	<input class="form-control" type="text" maxlength="30" id="TaskName" name="txtTaskName" value="<?php echo $Clean['TaskName']; ?>" />
                            </div>
                        </div>
						<div class="form-group">
                            <label class="col-lg-2 control-label">Active</label>
                            <div class="col-lg-5">
                                <label class="checkbox-inline">
                                    <input type="checkbox" name="chkIsActive" value="1" <?php echo $Clean['IsActive'] == 1 ? ' checked=checked' : ''; ?> />Yes
                                </label>
                            </div>
                        </div>
                        <div class="form-group">
							<div class="col-sm-offset-2 col-lg-10">
								<input type="hidden" name="hdnProcess" value="3" />
								<input type="hidden" name="hdnTaskID" value="<?php echo $Clean['TaskID']; ?>" />
								<button type="submit" name="btnModify" class="btn btn-primary">Modify</button>
								<button type="submit" name="btnCancel" class="btn btn-success">Cancel</button>
							</div>
                      	</div>
                    </div>
                </div>
            </form>
        </div>
        <!-- /#page-wrapper -->

    </div>
    <!-- /#wrapper -->
<?php
require_once('../footer.php');
?>
</body>
</html>
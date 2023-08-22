<?php
require_once('../../classes/class.users.php');
require_once('../../classes/class.validation.php');
require_once('../../classes/class.authentication.php');

require_once('../../classes/class.task_groups.php');

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

if ($LoggedUser->HasPermissionForTask(TASK_ADD_TASK_GROUP) !== true)
{
	header('location:../unauthorized_login_admin.php');
	exit;
}

$HasErrors = false;

$Clean = array();
$Clean['Process'] = 0;

$Clean['TaskGroupName'] = '';

if (isset($_POST['hdnProcess']))
{
	$Clean['Process'] = (int) $_POST['hdnProcess'];
}
switch ($Clean['Process'])
{
	case 1:						
		if (isset($_POST['txtTaskGroupName']))
		{
			$Clean['TaskGroupName'] = strip_tags(trim($_POST['txtTaskGroupName']));
		}
		
		$NewRecordValidator = new Validator();
		$NewRecordValidator->ValidateStrings($Clean['TaskGroupName'], 'Task group name is required and should be between 4 and 30 characters.', 4, 30);
		
		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}
				
		$NewTaskGroup = new TaskGroup();
				
		$NewTaskGroup->SetTaskGroupName($Clean['TaskGroupName']);
		$NewTaskGroup->SetIsActive(1);
		
		$NewTaskGroup->SetCreateUserID($LoggedUser->GetUserID());

		if ($NewTaskGroup->RecordExists())
		{
			$NewRecordValidator->AttachTextError('The task group name you have added already exists.');
			$HasErrors = true;
			break;
		}

		if (!$NewTaskGroup->Save())
		{
			$NewRecordValidator->AttachTextError(ProcessErrors($NewTaskGroup->GetLastErrorCode()));
			$HasErrors = true;

			break;
		}
		
		header('location:task_groups_list.php?POMode=RA');
		exit;
	break;
}

require_once('../html_header.php');
?>
<title>Add Task Group</title>
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
                    <h1 class="page-header">Add Task Group</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="AddTaskGroup" action="add_task_group.php" method="post">
            	<div class="panel panel-default">
                    <div class="panel-heading">
                        Enter Task Group Details
                    </div>
                    <div class="panel-body">
<?php
						if ($HasErrors == true)
						{
							echo $NewRecordValidator->DisplayErrors();
						}
?>                    
                    	<div class="form-group">
                            <label for="TaskGroupName" class="col-lg-2 control-label">Task Group Name</label>
                            <div class="col-lg-4">
                            	<input class="form-control" type="text" maxlength="30" id="TaskGroupName" name="txtTaskGroupName" value="<?php echo $Clean['TaskGroupName']; ?>" />
                            </div>
                        </div>
                        <div class="form-group">
                        <div class="col-sm-offset-2 col-lg-10">
                        	<input type="hidden" name="hdnProcess" value="1" />
							<button type="submit" class="btn btn-primary">Save</button>
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
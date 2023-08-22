<?php
require_once('../../classes/class.users.php');
require_once('../../classes/class.validation.php');
require_once('../../classes/class.authentication.php');

require_once('../../classes/class.menus.php');
require_once('../../classes/class.submenus.php');

require_once('../../classes/class.tasks.php');
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

if ($LoggedUser->HasPermissionForTask(TASK_ADD_SUB_MENU) !== true)
{
	header('location:../unauthorized_login_admin.php');
	exit;
}

$AllTaskGroups = array();
$AllTaskGroups = TaskGroup::GetActiveTaskGroups();

$AllMenus = array();
$AllMenus = Menu::GetActiveMenus();

$AllTasks = array();
$AllTasks = Task::GetActiveTasksTaskGroupWise();

$HasErrors = false;

$Clean = array();
$Clean['Process'] = 0;

$Clean['MenuID'] = 0;
$Clean['TaskID'] = 0;

$Clean['SubMenuName'] = '';
$Clean['LinkedFileName'] = '';

$Clean['SubMenuPriority'] = 0;
$Clean['IsNew'] = 0;

if (isset($_GET['MenuID']))
{
	$Clean['MenuID'] = (int) $_GET['MenuID'];
}

if (isset($_POST['hdnProcess']))
{
	$Clean['Process'] = (int) $_POST['hdnProcess'];
}
switch ($Clean['Process'])
{
	case 1:
		if (isset($_POST['drdMenu']))
		{
			$Clean['MenuID'] = (int) $_POST['drdMenu'];
		}

		if (isset($_POST['drdTask']))
		{
			$Clean['TaskID'] = (int) $_POST['drdTask'];
		}

		if (isset($_POST['txtSubMenuName']))
		{
			$Clean['SubMenuName'] = strip_tags(trim($_POST['txtSubMenuName']));
		}

		if (isset($_POST['txtLinkedFileName']))
		{
			$Clean['LinkedFileName'] = strip_tags(trim($_POST['txtLinkedFileName']));
		}

		if (isset($_POST['txtSubMenuPriority']))
		{
			$Clean['SubMenuPriority'] = (int) $_POST['txtSubMenuPriority'];
		}

		if (isset($_POST['chkIsNew']))
		{
			$Clean['IsNew'] = 1;
		}
		else
		{
			$Clean['IsNew'] = 0;
		}
		
		$NewRecordValidator = new Validator();
		$NewRecordValidator->ValidateInSelect($Clean['MenuID'], $AllMenus, 'Unknown Error, Please try again.');

		$TaskList = array();
		$TaskList = Task::GetActiveTasks();

		$NewRecordValidator->ValidateInSelect($Clean['TaskID'], $TaskList, 'Unknown Error, Please try again.');

		$NewRecordValidator->ValidateStrings($Clean['SubMenuName'], 'Sub menu name is required and should be between 4 and 30 characters.', 4, 30);
		$NewRecordValidator->ValidateStrings($Clean['LinkedFileName'], 'Linked file name is required and should be between 5 and 100 characters.', 5, 100);

		$NewRecordValidator->ValidateInteger($Clean['SubMenuPriority'], 'Menu priority must be supplied and should be an integer value.', 1);

		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}
				
		$NewSubMenu = new SubMenu();
		
		$NewSubMenu->SetMenuID($Clean['MenuID']);
		$NewSubMenu->SetTaskID($Clean['TaskID']);

		$NewSubMenu->SetSubmenuName($Clean['SubMenuName']);
		$NewSubMenu->SetLinkedFileName($Clean['LinkedFileName']);

		$NewSubMenu->SetSubmenuPriority($Clean['SubMenuPriority']);
		$NewSubMenu->SetIsNew($Clean['IsNew']);

		$NewSubMenu->SetIsActive(1);
		
		$NewSubMenu->SetCreateUserID($LoggedUser->GetUserID());

		if ($NewSubMenu->RecordExists())
		{
			$NewRecordValidator->AttachTextError('The sub menu name you have added already exists.');
			$HasErrors = true;
			break;
		}

		if (!$NewSubMenu->Save())
		{
			$NewRecordValidator->AttachTextError(ProcessErrors($NewSubMenu->GetLastErrorCode()));
			$HasErrors = true;
			break;
		}
		
		header('location:submenus_list.php?Mode=RA&Process=7&MenuID=' . $Clean['MenuID']);
		exit;
	break;
}

require_once('../html_header.php');
?>
<title>Add Sub Menu</title>
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
                    <h1 class="page-header">Add Sub Menu</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="AddMenu" action="add_submenu.php" method="post">
            	<div class="panel panel-default">
                    <div class="panel-heading">
                        Enter Sub Menu Details
                    </div>
                    <div class="panel-body">
<?php
						if ($HasErrors == true)
						{
							echo $NewRecordValidator->DisplayErrors();
						}
?>                    
                    	<div class="form-group">
							<label for="Menu" class="col-lg-2 control-label">Menu</label>
                            <div class="col-lg-4">
                            	<select class="form-control" name="drdMenu" id="Menu">
<?php
								if (is_array($AllMenus) && count($AllMenus) > 0)
								{
									foreach($AllMenus as $MenuID => $MenuName)
									{
										if ($Clean['MenuID'] != $MenuID)
										{
											echo '<option value="' . $MenuID . '">' . $MenuName . '</option>';
										}
										else
										{
											echo '<option selected="selected" value="' . $MenuID . '">' . $MenuName . '</option>';
										}
									}
								}
?>
								</select>
                            </div>
                        </div>
						<div class="form-group">
							<label for="Task" class="col-lg-2 control-label">Task</label>
                            <div class="col-lg-4">
                            	<select class="form-control" name="drdTask" id="Task">
<?php
								if (is_array($AllTasks) && count($AllTasks) > 0)
								{
									foreach($AllTasks as $TaskGroupID => $TaskGroupDetails)
									{
										echo '<optgroup label="' . $AllTaskGroups[$TaskGroupID] . '">';

										foreach ($TaskGroupDetails as $TaskID=>$TaskName)
										{
										if ($Clean['TaskID'] != $TaskID)
										{
											echo '<option value="' . $TaskID . '">' . $TaskName . '</option>';
										}
										else
										{
											echo '<option selected="selected" value="' . $TaskID.'">' . $TaskName . '</option>';
										}
										}

										echo '</optgroup>';
									}
								}
?>
								</select>
                            </div>
                        </div>
						<div class="form-group">
                            <label for="SubMenuName" class="col-lg-2 control-label">Sub Menu Name</label>
                            <div class="col-lg-4">
                            	<input class="form-control" type="text" maxlength="30" id="SubMenuName" name="txtSubMenuName" value="<?php echo $Clean['SubMenuName']; ?>" />
                            </div>
                        </div>
						<div class="form-group">
                            <label for="LinkedFileName" class="col-lg-2 control-label">Linked File Name</label>
                            <div class="col-lg-4">
                            	<input class="form-control" type="text" maxlength="100" id="LinkedFileName" name="txtLinkedFileName" value="<?php echo $Clean['LinkedFileName']; ?>" />
                            </div>
                        </div>
						<div class="form-group">
                            <label for="SubMenuPriority" class="col-lg-2 control-label">Sub Menu Priority</label>
                            <div class="col-lg-4">
                            	<input class="form-control" type="text" maxlength="5" id="SubMenuPriority" name="txtSubMenuPriority" value="<?php echo (($Clean['SubMenuPriority'] != 0) ? $Clean['SubMenuPriority'] : ''); ?>" />
                            </div>
                        </div>
						<div class="form-group">
                            <label for="IsNew" class="col-lg-2 control-label">Is New</label>
                            <div class="col-lg-4">
                                <input type="checkbox" id="IsNew" name="chkIsNew" <?php echo ($Clean['IsNew'] == 1) ? 'checked="checked"' : ''; ?> value="1" />
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
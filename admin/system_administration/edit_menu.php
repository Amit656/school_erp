<?php
require_once('../../classes/class.users.php');
require_once('../../classes/class.validation.php');
require_once('../../classes/class.authentication.php');

require_once('../../classes/class.menus.php');

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

if ($LoggedUser->HasPermissionForTask(TASK_EDIT_MENU) !== true)
{
	header('location:../unauthorized_login_admin.php');
	exit;
}

if (isset($_POST['btnCancel']))
{
	header('location:menus_list.php');
	exit;
}

$Clean = array();

$Clean['MenuID'] = 0;

if (isset($_GET['MenuID']))
{
    $Clean['MenuID'] = (int) $_GET['MenuID'];
}
else if (isset($_POST['hdnMenuID']))
{
    $Clean['MenuID'] = (int) $_POST['hdnMenuID'];
}

if ($Clean['MenuID'] <= 0)
{
    header('location:../error.php');
    exit;
}

try
{
	$MenuToEdit = new Menu($Clean['MenuID']);
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

$HasErrors = false;

$Clean['Process'] = 0;

$Clean['MenuName'] = '';
$Clean['MenuPriority'] = 0;
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
	case 1:						
		if (isset($_POST['txtMenuName']))
		{
			$Clean['MenuName'] = strip_tags(trim($_POST['txtMenuName']));
		}

		if (isset($_POST['txtMenuPriority']))
		{
			$Clean['MenuPriority'] = (int) $_POST['txtMenuPriority'];
		}

		if (!isset($_POST['chkIsActive']))
		{
			$Clean['IsActive'] = 0;
		}
		
		$NewRecordValidator = new Validator();
		$NewRecordValidator->ValidateStrings($Clean['MenuName'], 'Menu name is required and should be between 4 and 30 characters.', 4, 30);
		$NewRecordValidator->ValidateInteger($Clean['MenuPriority'], 'Menu priority must be supplied and should be an integer value.', 1);

		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}
		
		$MenuToEdit->SetMenuName($Clean['MenuName']);
		$MenuToEdit->SetMenuPriority($Clean['MenuPriority']);
		$MenuToEdit->SetIsActive($Clean['IsActive']);

		if ($MenuToEdit->RecordExists())
		{
			$NewRecordValidator->AttachTextError('The menu name you have added already exists.');
			$HasErrors = true;
			break;
		}

		if (!$MenuToEdit->Save())
		{
			$NewRecordValidator->AttachTextError(ProcessErrors($MenuToEdit->GetLastErrorCode()));
			$HasErrors = true;

			break;
		}
		
		header('location:menus_list.php?Mode=RU');
		exit;
	break;

	case 2:
		$Clean['MenuName'] = $MenuToEdit->GetMenuName();
		$Clean['MenuPriority'] = $MenuToEdit->GetMenuPriority();
		$Clean['IsActive'] = $MenuToEdit->GetIsActive();
	break;
}

require_once('../html_header.php');
?>
<title>Edit Menu</title>
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
                    <h1 class="page-header">Edit Menu</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="AddMenu" action="edit_menu.php" method="post">
            	<div class="panel panel-default">
                    <div class="panel-heading">
                        Enter Menu Details
                    </div>
                    <div class="panel-body">
<?php
						if ($HasErrors == true)
						{
							echo $NewRecordValidator->DisplayErrors();
						}
?>                    
                    	<div class="form-group">
                            <label for="MenuName" class="col-lg-2 control-label">Menu Name</label>
                            <div class="col-lg-4">
                            	<input class="form-control" type="text" maxlength="30" id="MenuName" name="txtMenuName" value="<?php echo $Clean['MenuName']; ?>" />
                            </div>
                        </div>
						<div class="form-group">
                            <label for="MenuPriority" class="col-lg-2 control-label">Menu Priority</label>
                            <div class="col-lg-4">
                            	<input class="form-control" type="text" maxlength="5" id="MenuPriority" name="txtMenuPriority" value="<?php echo (($Clean['MenuPriority'] != 0) ? $Clean['MenuPriority'] : ''); ?>" />
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
								<input type="hidden" name="hdnProcess" value="1" />
								<input type="hidden" name="hdnMenuID" value="<?php echo $Clean['MenuID']; ?>" />
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
<?php
require_once('../../classes/class.users.php');
require_once('../../classes/class.validation.php');
require_once('../../classes/class.authentication.php');

require_once('../../classes/class.roles.php');

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

if ($LoggedUser->HasPermissionForTask(TASK_EDIT_ROLE) !== true)
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

$Clean['RoleID'] = 0;

if (isset($_GET['RoleID']))
{
    $Clean['RoleID'] = (int) $_GET['RoleID'];
}
else if (isset($_POST['hdnRoleID']))
{
    $Clean['RoleID'] = (int) $_POST['hdnRoleID'];
}

if ($Clean['RoleID'] <= 0)
{
    header('location:../error.php');
    exit;
}

try
{
	$RoleToEdit = new Role($Clean['RoleID']);
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

$Clean['RoleName'] = '';
$Clean['IsSystemAdminRole'] = 0;
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
		if (isset($_POST['txtRoleName']))
		{
			$Clean['RoleName'] = strip_tags(trim($_POST['txtRoleName']));
		}

		if (isset($_POST['chkIsSystemAdminRole']))
		{
			$Clean['IsSystemAdminRole'] = 1;
		}
		else
		{
			$Clean['IsSystemAdminRole'] = 0;
		}

		if (!isset($_POST['chkIsActive']))
		{
			$Clean['IsActive'] = 0;
		}
		
		$NewRecordValidator = new Validator();
		$NewRecordValidator->ValidateStrings($Clean['RoleName'], 'Role name is required and should be between 4 and 20 characters.', 4, 20);

		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}
				
		$RoleToEdit->SetRoleName($Clean['RoleName']);
		$RoleToEdit->SetIsSystemAdminRole($Clean['IsSystemAdminRole']);
		$RoleToEdit->SetIsActive($Clean['IsActive']);
		
		$RoleToEdit->SetCreateUserID($LoggedUser->GetUserID());

		if ($RoleToEdit->RecordExists())
		{
			$NewRecordValidator->AttachTextError('The role name you have added already exists.');
			$HasErrors = true;
			break;
		}

		if (!$RoleToEdit->Save())
		{
			$NewRecordValidator->AttachTextError(ProcessErrors($RoleToEdit->GetLastErrorCode()));
			$HasErrors = true;

			break;
		}
		
		header('location:roles_list.php?POMode=RU');
		exit;
	break;

	case 2:
		$Clean['RoleName'] = $RoleToEdit->GetRoleName();
		$Clean['IsSystemAdminRole'] = $RoleToEdit->GetIsSystemAdminRole();
		$Clean['IsActive'] = $RoleToEdit->GetIsActive();
	break;
}

require_once('../html_header.php');
?>
<title>Edit Role</title>
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
                    <h1 class="page-header">Edit Role</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="EditRecord" action="edit_role.php" method="post">
            	<div class="panel panel-default">
                    <div class="panel-heading">
                        Enter Role Details
                    </div>
                    <div class="panel-body">
<?php
						if ($HasErrors == true)
						{
							echo $NewRecordValidator->DisplayErrors();
						}
?>                    
                    	<div class="form-group">
                            <label for="RoleName" class="col-lg-2 control-label">Role Name</label>
                            <div class="col-lg-4">
                            	<input class="form-control" type="text" maxlength="20" id="RoleName" name="txtRoleName" value="<?php echo $Clean['RoleName']; ?>" />
                            </div>
                        </div>
						<div class="form-group">
                            <label for="IsSystemAdminRole" class="col-lg-2 control-label">Is System Admin Role</label>
                            <div class="col-lg-4">
                                <input type="checkbox" id="IsSystemAdminRole" name="chkIsSystemAdminRole" <?php echo ($Clean['IsSystemAdminRole'] == 1) ? 'checked="checked"' : ''; ?> value="1" />
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
								<input type="hidden" name="hdnRoleID" value="<?php echo $Clean['RoleID']; ?>" />
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
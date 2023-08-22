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

if ($LoggedUser->HasPermissionForTask(TASK_ADD_ROLE) !== true)
{
	header('location:../unauthorized_login_admin.php');
	exit;
}

$HasErrors = false;

$Clean = array();
$Clean['Process'] = 0;

$Clean['RoleName'] = '';
$Clean['IsSystemAdminRole'] = 0;

if (isset($_POST['hdnProcess']))
{
	$Clean['Process'] = (int) $_POST['hdnProcess'];
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
		
		$NewRecordValidator = new Validator();
		$NewRecordValidator->ValidateStrings($Clean['RoleName'], 'Role name is required and should be between 4 and 20 characters.', 4, 20);

		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}
				
		$NewRole = new Role();
				
		$NewRole->SetRoleName($Clean['RoleName']);
		$NewRole->SetIsSystemAdminRole($Clean['IsSystemAdminRole']);
		$NewRole->SetIsActive(1);
		
		$NewRole->SetCreateUserID($LoggedUser->GetUserID());

		if ($NewRole->RecordExists())
		{
			$NewRecordValidator->AttachTextError('The role name you have added already exists.');
			$HasErrors = true;
			break;
		}

		if (!$NewRole->Save())
		{
			$NewRecordValidator->AttachTextError(ProcessErrors($NewRole->GetLastErrorCode()));
			$HasErrors = true;

			break;
		}
		
		header('location:roles_list.php?POMode=RA');
		exit;
	break;
}

require_once('../html_header.php');
?>
<title>Add Role</title>
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
                    <h1 class="page-header">Add Role</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="AddRecord" action="add_role.php" method="post">
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
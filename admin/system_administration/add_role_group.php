<?php
require_once('../../classes/class.users.php');
require_once('../../classes/class.validation.php');
require_once('../../classes/class.authentication.php');

require_once('../../classes/class.role_groups.php');

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

if ($LoggedUser->HasPermissionForTask(TASK_ADD_MENU) !== true)
{
	header('location:../unauthorized_login_admin.php');
	exit;
}

$HasErrors = false;

$Clean = array();
$Clean['Process'] = 0;

$Clean['RoleGroupName'] = '';

if (isset($_POST['hdnProcess']))
{
	$Clean['Process'] = (int) $_POST['hdnProcess'];
}
switch ($Clean['Process'])
{
	case 1:						
		if (isset($_POST['txtRoleGroupName']))
		{
			$Clean['RoleGroupName'] = strip_tags(trim($_POST['txtRoleGroupName']));
		}

		$NewRecordValidator = new Validator();
		$NewRecordValidator->ValidateStrings($Clean['RoleGroupName'], 'Role group name is required and should be between 4 and 30 characters.', 4, 30);

		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}
				
		$NewRoleGroup = new RoleGroup();
				
		$NewRoleGroup->SetRoleGroupName($Clean['RoleGroupName']);
		$NewRoleGroup->SetIsActive(1);
		
		$NewRoleGroup->SetCreateUserID($LoggedUser->GetUserID());

		if ($NewRoleGroup->RecordExists())
		{
			$NewRecordValidator->AttachTextError('The role group name you have added already exists.');
			$HasErrors = true;
			break;
		}

		if (!$NewRoleGroup->Save())
		{
			$NewRecordValidator->AttachTextError(ProcessErrors($NewRoleGroup->GetLastErrorCode()));
			$HasErrors = true;

			break;
		}
		
		header('location:role_groups_list.php?POMode=RA');
		exit;
	break;
}

require_once('../html_header.php');
?>
<title>Add Role Group</title>
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
                    <h1 class="page-header">Add Role Group</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="AddRecord" action="add_role_group.php" method="post">
            	<div class="panel panel-default">
                    <div class="panel-heading">
                        Enter Role Group Details
                    </div>
                    <div class="panel-body">
<?php
						if ($HasErrors == true)
						{
							echo $NewRecordValidator->DisplayErrors();
						}
?>                    
                    	<div class="form-group">
                            <label for="RoleGroupName" class="col-lg-2 control-label">Role Group Name</label>
                            <div class="col-lg-4">
                            	<input class="form-control" type="text" maxlength="30" id="RoleGroupName" name="txtRoleGroupName" value="<?php echo $Clean['RoleGroupName']; ?>" />
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
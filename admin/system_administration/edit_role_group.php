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

if ($LoggedUser->HasPermissionForTask(TASK_EDIT_ROLE_GROUP) !== true)
{
	header('location:../unauthorized_login_admin.php');
	exit;
}

if (isset($_POST['btnCancel']))
{
	header('location:role_groups_list.php');
	exit;
}

$Clean = array();

$Clean['RoleGroupID'] = 0;

if (isset($_GET['RoleGroupID']))
{
    $Clean['RoleGroupID'] = (int) $_GET['RoleGroupID'];
}
else if (isset($_POST['hdnRoleGroupID']))
{
    $Clean['RoleGroupID'] = (int) $_POST['hdnRoleGroupID'];
}

if ($Clean['RoleGroupID'] <= 0)
{
    header('location:../error.php');
    exit;
}

try
{
	$RoleGroupToEdit = new RoleGroup($Clean['RoleGroupID']);
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

$Clean['RoleGroupName'] = '';
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
		if (isset($_POST['txtRoleGroupName']))
		{
			$Clean['RoleGroupName'] = strip_tags(trim($_POST['txtRoleGroupName']));
		}

		if (!isset($_POST['chkIsActive']))
		{
			$Clean['IsActive'] = 0;
		}

		$NewRecordValidator = new Validator();
		$NewRecordValidator->ValidateStrings($Clean['RoleGroupName'], 'Role group name is required and should be between 4 and 30 characters.', 4, 30);

		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}
				
		$RoleGroupToEdit->SetRoleGroupName($Clean['RoleGroupName']);
		$RoleGroupToEdit->SetIsActive($Clean['IsActive']);

		if ($RoleGroupToEdit->RecordExists())
		{
			$NewRecordValidator->AttachTextError('The role group name you have added already exists.');
			$HasErrors = true;
			break;
		}

		if (!$RoleGroupToEdit->Save())
		{
			$NewRecordValidator->AttachTextError(ProcessErrors($RoleGroupToEdit->GetLastErrorCode()));
			$HasErrors = true;
			break;
		}
		
		header('location:role_groups_list.php?POMode=RU');
		exit;
	break;

	case 2:
		$Clean['RoleGroupName'] = $RoleGroupToEdit->GetRoleGroupName();
		$Clean['IsActive'] = $RoleGroupToEdit->GetIsActive();
	break;
}

require_once('../html_header.php');
?>
<title>Edit Role Group</title>
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
                    <h1 class="page-header">Edit Role Group</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="AddRecord" action="edit_role_group.php" method="post">
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
								<input type="hidden" name="hdnProcess" value="3" />
								<input type="hidden" name="hdnRoleGroupID" value="<?php echo $Clean['RoleGroupID']; ?>" />
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
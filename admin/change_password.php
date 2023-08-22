<?php
require_once("../classes/class.users.php");
require_once("../classes/class.validation.php");
require_once("../classes/class.authentication.php");

require_once("../includes/global_defaults.inc.php");

//1. RECHECK IF THE USER IS VALID //
try
{
	$AuthObject = new ApplicationAuthentication;
	$LoggedUser = new User(0, $AuthObject->CheckValidUser());
}

// THIS CATCH BLOCK BUBBLES THE EXCEPTION TO THE BUILT IN 'Exception' CLASS IF THERE ARE ANY UNCAUGHT ERRORS //
catch (ApplicationAuthException $e)
{
	header('location:unauthorized_login_admin.php');
	exit;
}
catch (Exception $e)
{
	header('location:unauthorized_login_admin.php');
	exit;
}
// END OF 1. //

if ($LoggedUser->HasPermissionForTask(TASK_CHANGE_PASSWORD) !== true)
{
	header('location:unauthorized_login_admin.php');
	exit;
}

$HasErrors = false;

$Clean['Process'] = 0;

$Clean['OldPass'] = '';
$Clean['NewPass']  = '';
$Clean['ConfNewPass'] = '';

if (isset($_POST['hdnProcess']))
{
	$Clean['Process'] = (int) $_POST['hdnProcess'];
}
switch ($Clean['Process'])
{
	case 1:
		if (isset($_POST['txtOldPass']))
		{
			$Clean['OldPass'] = strip_tags(trim((string) $_POST['txtOldPass']));
		}
		
		if (isset($_POST['txtNewPass']))
		{
			$Clean['NewPass'] = strip_tags(trim((string) $_POST['txtNewPass']));
		}
		
		if (isset($_POST['txtConfNewPass']))
		{
			$Clean['ConfNewPass'] = strip_tags(trim((string) $_POST['txtConfNewPass']));
		}
		
		$RecordValidator = new Validator();
		
		if ($LoggedUser->GetPassword() != sha1($Clean['OldPass']))
		{
			$RecordValidator->AttachTextError('You have supplied an invalid old password.');
			$HasErrors = true;
			break;
		}
		
		$RecordValidator->ValidateStringsSpecialChar($Clean['NewPass'], "0-9_.@-", 'New password must be supplied and must be between 6 and 12 chars.', 6, 12);
		
		if ($RecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}
		
		if ($Clean['NewPass'] != $Clean['ConfNewPass'])
		{
			$RecordValidator->AttachTextError('New Passwords do not match.');
			$HasErrors = true;
			break;
		}
		
		if (sha1($Clean['NewPass']) == $LoggedUser->GetPassword())
		{
			$RecordValidator->AttachTextError('The new password you have chosen is the same as the old one. Please choose a different password.');
			$HasErrors = true;
			break;
		}
		
		if ($LoggedUser->ChangeUserPassword($Clean['OldPass'], $Clean['NewPass']))
		{
			$AuthObject->Logout();
			header('location:login.php?Msg=PSWRD');
			exit;
		}
		
		header('location:change_password.php?Msg=N');
		exit;
	break;		
}

require_once('html_header.php');
?>
<title>Change Password</title>
<link href="vendor/jquery-ui/jquery-ui.min.css" rel="stylesheet">
</head>

<body>

    <div id="wrapper">
    	<!-- Navigation -->
        <nav class="navbar navbar-default navbar-static-top" role="navigation" style="margin-bottom: 0">
<?php 
			require_once('site_header.php');
			require_once('left_navigation_menu.php');
?>                    
            <!-- /.navbar-static-side -->
        </nav>

        <div id="page-wrapper">
            <div class="row">
                <div class="col-lg-12">
                    <h1 class="page-header">Change Password</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="AddTaskGroup" action="change_password.php" method="post">
            	<div class="panel panel-default">
                    <div class="panel-heading">
                        Enter Password Details
                    </div>
                    <div class="panel-body">
<?php
						if ($HasErrors == true)
						{
							echo $RecordValidator->DisplayErrors();
						}
?>                    
                    	<div class="form-group">
                            <label for="OldPass" class="col-lg-3 control-label">Old Password</label>
                            <div class="col-lg-4">
								<input class="form-control" type="password"  maxlength="12" id="OldPass" required name="txtOldPass" />
                            </div>
                        </div>
						<div class="form-group">
                            <label for="NewPass" class="col-lg-3 control-label">New Password</label>
                            <div class="col-lg-4">
								<input class="form-control" type="password"  maxlength="12" id="NewPass" required name="txtNewPass" />
                            </div>
                        </div>
						<div class="form-group">
                            <label for="ConfNewPass" class="col-lg-3 control-label">Confirm New Password</label>
                            <div class="col-lg-4">
								<input class="form-control" type="password"  maxlength="12" id="ConfNewPass" required name="txtConfNewPass" />
                            </div>
                        </div>
                        <div class="form-group">
							<div class="col-sm-offset-3 col-lg-9">
								<input type="hidden" name="hdnProcess" value="1" />
								<button type="submit" class="btn btn-primary">Change Password</button>
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
require_once('footer.php');
?>
</body>
</html>
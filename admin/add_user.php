<?php
require_once("../classes/class.users.php");
require_once("../classes/class.validation.php");
require_once("../classes/class.authentication.php");

require_once("../classes/class.roles.php");

require_once("../classes/class.date_processing.php");

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

$UserMenusArray = array();
$UserMenusArray = $LoggedUser->GetUserMenus();

if (!is_array($UserMenusArray) || count($UserMenusArray) <= 0)
{
	//header('location:../logout.php');
	//exit;
}

$AllRoles = array();
$AllRoles = Role::GetActiveRoles();

$HasErrors = false;

$Clean = array();
$Clean['Process'] = 0;

$Clean['UserName'] = '';
$Clean['Password'] = '';

$Clean['RoleID'] = 0;

$Clean['AccountExpiryDate'] = '';
$Clean['HasLoginTimeLimit'] = 1;
$Clean['LoginStartTime'] = '11:00';
$Clean['LoginStartTimeAMPM'] = 'AM';
$Clean['LoginEndTime'] = '09:00';
$Clean['LoginEndTimeAMPM'] = 'PM';

if (isset($_POST['hdnProcess']))
{
	$Clean['Process'] = (int) $_POST['hdnProcess'];
}
switch ($Clean['Process'])
{
	case 1:
		if (isset($_POST['txtUserName']))
		{
			$Clean['UserName'] = strip_tags(trim($_POST['txtUserName']));
		}		
		if (isset($_POST['txtPassword']))
		{
			$Clean['Password'] = strip_tags(trim($_POST['txtPassword']));
		}
		
		if (isset($_POST['drdRole']))
		{
			$Clean['RoleID'] = (int) $_POST['drdRole'];
		}
				
		if (isset($_POST['txtAccountExpiryDate']))
		{
			$Clean['AccountExpiryDate'] = strip_tags(trim($_POST['txtAccountExpiryDate']));
		}
		
		if (isset($_POST['chkHasLoginTimeLimit']))
		{
			$Clean['HasLoginTimeLimit'] = 1;
		}
		else
		{
			$Clean['HasLoginTimeLimit'] = 0;
		}
		
		if (isset($_POST['txtLoginStartTime']))
		{
			$Clean['LoginStartTime'] = $_POST['txtLoginStartTime'];
		}	
		if (isset($_POST['dndLoginStartTimeAMPM']))
		{
			$Clean['LoginStartTimeAMPM'] = $_POST['dndLoginStartTimeAMPM'];
		}	
		
		if (isset($_POST['txtLoginEndTime']))
		{
			$Clean['LoginEndTime'] = $_POST['txtLoginEndTime'];
		}	
		if (isset($_POST['dndLoginEndTimeAMPM']))
		{
			$Clean['LoginEndTimeAMPM'] = $_POST['dndLoginEndTimeAMPM'];
		}
		
		$NewRecordValidator = new Validator();
		$NewRecordValidator->ValidateStrings($Clean['UserName'], "User Name is required and should be between 4 and 12 characters.", 4, 12);
		$NewRecordValidator->ValidateStringsSpecialChar($Clean['Password'], "0-9_.@-", 'Please enter a valid new password. It should be between 5 and 12 chars.', 5, 12);
		
		$NewRecordValidator->ValidateInSelect($Clean['RoleID'], $AllRoles, 'Unknown Error, Please try again.');
				
		if ($Clean['AccountExpiryDate'] != '')
		{
			$NewRecordValidator->ValidateDate($Clean['AccountExpiryDate'], "Please enter a valid expiry date.");
		}
		
		if ($Clean['HasLoginTimeLimit'])
		{
			if((!preg_match("/((([1][0-2])|([0][1-9])):[0-5][0-9] (AM|PM))/", $Clean['LoginStartTime'].' '.$Clean['LoginStartTimeAMPM'])) || (!preg_match("/((([1][0-2])|([0][1-9])):[0-5][0-9] (AM|PM))/", $Clean['LoginEndTime'].' '.$Clean['LoginEndTimeAMPM'])))
			{
				$NewRecordValidator->AttachTextError("Please select a valid login start and end time.");
			}
		}
		
		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}
				
		$NewUser = new User();
				
		$NewUser->SetUserName($Clean['UserName']);
		$NewUser->SetPassword($Clean['Password']);
		$NewUser->SetRoleID($Clean['RoleID']);
		
		if ($Clean['AccountExpiryDate'] != '')
		{
			$NewUser->SetAccountExpiryDate(date('Y-m-d', strtotime(DateProcessing::ToggleDateDayAndMonth($Clean['AccountExpiryDate']))).' 23:59:59');
		}
		else
		{
			$NewUser->SetAccountExpiryDate('0000-00-00 00:00:00');
		}
		
		if ($Clean['HasLoginTimeLimit'])
		{
			$NewUser->SetHasLoginTimeLimit(1);
			$NewUser->SetLoginStartTime(date('H:i:s', strtotime($Clean['LoginStartTime'].' '.$Clean['LoginStartTimeAMPM'])));
			$NewUser->SetLoginEndTime(date('H:i:s', strtotime($Clean['LoginEndTime'].' '.$Clean['LoginEndTimeAMPM'])));
		}
		else
		{
			$NewUser->SetLoginStartTime('00:00:00');
			$NewUser->SetLoginEndTime('00:00:00');
		}
		
		$NewUser->SetCreateUserID($LoggedUser->GetUserID());
		$NewUser->SetIsActive(1);

		if (!$NewUser->Save())
		{
			$NewRecordValidator->AttachTextError(ProcessErrors($NewUser->GetLastErrorCode()));
			$HasErrors = true;

			break;
		}
		
		header('location:users_report.php?Mode=AS&Process=7&RoleID='.$Clean['RoleID'].'&UserName='.$Clean['UserName']);
		exit;
	break;
}

require_once('html_header.php');
?>
<title>Add New User</title>
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
                    <h1 class="page-header">Add New User</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="AddUser" action="add_user.php" method="post">
            	<div class="panel panel-default">
                    <div class="panel-heading">
                        Enter User Details
                    </div>
                    <div class="panel-body">
<?php
						if ($HasErrors == true)
						{
							echo $NewRecordValidator->DisplayErrors();
						}
?>                    
                    	<div class="form-group">
                            <label for="UserName" class="col-lg-2 control-label">User Name</label>
                            <div class="col-lg-4">
                            	<input class="form-control" type="text" maxlength="12" id="UserName" name="txtUserName" value="<?php echo $Clean['UserName']; ?>" />
                            </div>
                            <label for="Password" class="col-lg-2 control-label">Password</label>
                            <div class="col-lg-4">
                            	<input class="form-control" type="password" maxlength="12" id="Password" name="txtPassword" value="" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="Role" class="col-lg-2 control-label">User Role</label>
                            <div class="col-lg-4">
                            	<select class="form-control" name="drdRole" id="Role">
<?php
								if (is_array($AllRoles) && count($AllRoles) > 0)
								{
									foreach($AllRoles as $RoleID=>$RoleName)
									{
										if ($Clean['RoleID'] != $RoleID)
										{
											echo '<option value="'.$RoleID.'">'.$RoleName.'</option>';
										}
										else
										{
											echo '<option selected="selected" value="'.$RoleID.'">'.$RoleName.'</option>';
										}
									}
								}
?>
								</select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="AccountExpiryDate" class="col-lg-2 control-label">Account Expiry Date</label>
                            <div class="col-lg-4">
                            	<input type="text" class="form-control" maxlength="10" id="AccountExpiryDate" name="txtAccountExpiryDate" value="<?php echo $Clean['AccountExpiryDate']; ?>" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="SelectLoginTime" class="col-lg-2 control-label">User Has Time Limit</label>
                            <div class="col-lg-4">
                                <input type="checkbox" id="SelectLoginTime" name="chkHasLoginTimeLimit" <?php echo ($Clean['HasLoginTimeLimit'] == 1) ? 'checked="checked"' : ''; ?> value="1" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="StartTimeID" class="col-lg-2 control-label">Login Start Time</label>
                            <div class="col-lg-2">
                                <input type="text" id="StartTimeID" class="form-control" title="" value="<?php echo isset($Clean['LoginStartTime']) ? $Clean['LoginStartTime'] : ''; ?>" name="txtLoginStartTime" maxlength="5" />
                            </div>
                            <div class="col-lg-2">
                                <select name="dndLoginStartTimeAMPM" id="StartTimeAMPM" class="form-control">
                                    <option <?php echo (isset($Clean['LoginStartTimeAMPM']) && $Clean['LoginStartTimeAMPM'] == 'AM')  ? 'selected="selected"' : ''; ?> value="AM">AM</option>
                                    <option <?php echo (isset($Clean['LoginStartTimeAMPM']) && $Clean['LoginStartTimeAMPM'] == 'PM')  ? 'selected="selected"' : ''; ?> value="PM">PM</option>
                                </select>
                            </div>
                            <label for="EndTimeID" class="col-lg-2 control-label">Login End Time</label>
                            <div class="col-lg-2">
                                <input type="text" id="EndTimeID" class="form-control" title="" value="<?php echo isset($Clean['LoginEndTime']) ? $Clean['LoginEndTime'] : ''; ?>" name="txtLoginEndTime" maxlength="5" />
                            </div>
                            <div class="col-lg-2">
                                <select name="dndLoginEndTimeAMPM" id="EndTimeAMPM" class="form-control">
                                    <option <?php echo (isset($Clean['LoginEndTimeAMPM']) && $Clean['LoginEndTimeAMPM'] == 'AM')  ? 'selected="selected"' : ''; ?> value="AM">AM</option>
                                    <option <?php echo (isset($Clean['LoginEndTimeAMPM']) && $Clean['LoginEndTimeAMPM'] == 'PM')  ? 'selected="selected"' : ''; ?> value="PM">PM</option>
                                </select>
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
require_once('footer.php');
?>
	<script src="vendor/jquery-ui/jquery-ui.min.js"></script>
	<script type="text/javascript">
	$(document).ready(function() {
		$("#AccountExpiryDate").datepicker({
				changeMonth: true,
				changeYear: true,
				dateFormat: 'dd/mm/yy'
		});
	});
    </script>
</body>
</html>
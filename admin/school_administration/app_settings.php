<?php
require_once('../../classes/class.users.php');
require_once('../../classes/class.validation.php');
require_once('../../classes/class.authentication.php');

require_once('../../classes/school_administration/class.chat_settings.php');

require_once('../../includes/helpers.inc.php');
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

if ($LoggedUser->HasPermissionForTask(TASK_UPDATE_APP_SETTINGS) !== true)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}

$ChatEnabledBetweenUsersList = array();
$ChatEnabledBetweenUsersList = ChatSettings::GetChatEnabledBetweenUsersList();

$HasErrors = false;

$Clean = array();

$Clean['Process'] = 0;

$Clean['ChatSettings'] = array();

if (isset($_POST['hdnProcess']))
{
	$Clean['Process'] = (int) $_POST['hdnProcess'];
}
switch ($Clean['Process'])
{
	case 1:						
		if (isset($_POST['hdnChatSettingID']))
		{
			$Clean['ChatSettings'] = $_POST['hdnChatSettingID'];
		}
		
		$NewRecordValidator = new Validator();
		
		foreach ($Clean['ChatSettings'] as $ChatSettingID => $ChatSettingDetails)
		{
			$NewRecordValidator->ValidateInSelect($ChatSettingID, $ChatEnabledBetweenUsersList, 'Unknown error, please try again.');
		}
		
		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}
		
		if (!ChatSettings::SaveChatSettings($Clean['ChatSettings']))
		{
			$NewRecordValidator->AttachTextError('Data not saved. There was some error.');
			$HasErrors = true;
			break;
		}
		
		header('location:app_settings.php?Mode=ED');
		exit;
	break;
}

require_once('../html_header.php');
?>
<title>App Settings</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-switch/3.3.4/css/bootstrap2/bootstrap-switch.min.css" rel="stylesheet">
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
                    <h1 class="page-header">App Settings</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="AddSectionMaster" action="app_settings.php" method="post">
            	<div class="panel panel-default">
                    <div class="panel-heading">
                        Change Chat Settings
                    </div>
                    <div class="panel-body">
<?php
						if ($HasErrors == true)
						{
							echo $NewRecordValidator->DisplayErrors();
						}
						
						if (isset($ChatEnabledBetweenUsersList) && count($ChatEnabledBetweenUsersList) > 0)
						{
							$Counter = 1;
							foreach ($ChatEnabledBetweenUsersList as $ChatSettingID => $ChatSettingDetails)
							{
								if ($Counter == 1 || ($Counter - 1) % 2 == 0)
								{
?>
									<div class="form-group">
<?php
								}
?>
									<label for="chat-setting-<?php echo $ChatSettingID; ?>" class="col-lg-3 control-label" style="font-weight: normal;"><?php echo $ChatSettingDetails['FromUserType']; ?> To <?php echo $ChatSettingDetails['ToUserType']; ?></label>
									<div class="col-lg-2">
										<input class="form-control chat-setting" id="chat-setting-<?php echo $ChatSettingID; ?>" type="checkbox" name="hdnChatSettingID[<?php echo $ChatSettingID; ?>]" <?php echo $ChatSettingDetails['IsActive'] ? 'checked="checked"' : ''; ?> value="1"  />
									</div>
<?php
								if ($Counter % 2 == 0)
								{
?>
									</div>
<?php
								}
								
								$Counter++;
							}
						}
?>
                        <div class="form-group">
							<div class="col-sm-offset-3 col-lg-10">
								<input type="hidden" name="hdnProcess" value="1"/>
								<button type="submit" class="btn btn-primary"><i class="fa fa-refresh" aria-hidden="true"></i>&nbsp;Update</button>
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
if (PrintMessage($_GET, $Message))
{
?>
    <script type="text/javascript">
        alert('<?php echo $Message; ?>');
    </script>
<?php
}
?>
	<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-switch/3.3.4/js/bootstrap-switch.min.js"></script>
	<script type="text/javascript">
	$(function() {
		$(".chat-setting").bootstrapSwitch();
	});
	</script>
</body>
</html>
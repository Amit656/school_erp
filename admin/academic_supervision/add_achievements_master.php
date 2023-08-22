<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once("../../classes/academic_supervision/class.achievements_master.php");

require_once("../../includes/global_defaults.inc.php");

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

if ($LoggedUser->HasPermissionForTask(TASK_ADD_ACHIEVEMENT) !== true)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}

$HasErrors = false;

$Clean = array();
$Clean['Process'] = 0;

$Clean['Achievement'] = '';
$Clean['AchievementDetails'] = '';

if (isset($_POST['hdnProcess']))
{
	$Clean['Process'] = (int) $_POST['hdnProcess'];
}
switch ($Clean['Process'])
{
	case 1:
		if (isset($_POST['txtAchievement']))
		{
			$Clean['Achievement'] = strip_tags(trim($_POST['txtAchievement']));
		}
		if (isset($_POST['txtAchievementDetails']))
		{
			$Clean['AchievementDetails'] = strip_tags(trim($_POST['txtAchievementDetails']));
		}

		$NewRecordValidator = new Validator();

		$NewRecordValidator->ValidateStrings($Clean['Achievement'], 'Achievement is required and should be between 3 and 150 characters.', 3, 150);
		$NewRecordValidator->ValidateStrings($Clean['AchievementDetails'], 'Achievement details is required and should be between 5 and 500 characters.', 5, 500);
		
		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}
				
		$NewAchievementMaster = new AchievementMaster();
				
		$NewAchievementMaster->SetAchievement($Clean['Achievement']);
		$NewAchievementMaster->SetAchievementDetails($Clean['AchievementDetails']);

		$NewAchievementMaster->SetIsActive(1);
		$NewAchievementMaster->SetCreateUserID($LoggedUser->GetUserID());

		if (!$NewAchievementMaster->Save())
		{
			$NewRecordValidator->AttachTextError(ProcessErrors($NewAchievementMaster->GetLastErrorCode()));
			$HasErrors = true;
			break;
		}
		
		header('location:achievements_master_list.php?Mode=AS');
		exit;
	break;
}

require_once('../html_header.php');
?>
<title>Add Achievement Master</title>
<link href="../vendor/jquery-ui/jquery-ui.min.css" rel="stylesheet">
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
                    <h1 class="page-header">Add Achievement Master</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="AddAchievement" action="add_achievements_master.php" method="post">
            	<div class="panel panel-default">
                    <div class="panel-heading">
                        Enter Achievement Master Details
                    </div>
                    <div class="panel-body">
<?php
						if ($HasErrors == true)
						{
							echo $NewRecordValidator->DisplayErrors();
						}
?>                    
						<div class="form-group">
                            <label for="Achievement" class="col-lg-2 control-label">Achievement</label>
                            <div class="col-lg-6">
                            	<input class="form-control" type="text" maxlength="150" id="Achievement" name="txtAchievement" value="<?php echo $Clean['Achievement']; ?>" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="AchievementDetails" class="col-lg-2 control-label">Achievement Details</label>
                            <div class="col-lg-6">
                            	<textarea class="form-control" rows="5" id="AchievementDetails" name="txtAchievementDetails"><?php echo $Clean['AchievementDetails']; ?></textarea>
                            </div>
                        </div>
                        
                        <div class="form-group">
                        <div class="col-sm-offset-2 col-lg-10">
                        	<input type="hidden" name="hdnProcess" value="1" />
							<button type="submit" class="btn btn-primary"><i class="fa fa-save"></i>&nbsp;Save</button>
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
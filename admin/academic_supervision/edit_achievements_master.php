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

if ($LoggedUser->HasPermissionForTask(TASK_EDIT_ACHIEVEMENT) !== true)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}

if (isset($_POST['btnCancel']))
{
    header('location:achievements_master_list.php');
    exit;
}

$Clean = array();

$Clean['AchievementMasterID'] = 0;

if (isset($_GET['AchievementMasterID']))
{
    $Clean['AchievementMasterID'] = (int) $_GET['AchievementMasterID'];
}
else if (isset($_POST['hdnAchievementMasterID']))
{
    $Clean['AchievementMasterID'] = (int) $_POST['hdnAchievementMasterID'];
}

if ($Clean['AchievementMasterID'] <= 0)
{
    header('location:/admin/error.php');
    exit;
}   

try
{
    $AchievementMasterToEdit = new AchievementMaster($Clean['AchievementMasterID']);
}
catch (ApplicationDBException $e)
{
    header('location:/admin/error.php');
    exit;
}
catch (Exception $e)
{
    header('location:/admin/error.php');
    exit;
}

$HasErrors = false;

$Clean['Process'] = 0;

$Clean['Achievement'] = '';
$Clean['AchievementDetails'] = '';

$Clean['IsActive'] = 0;

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
        if (isset($_POST['txtAchievement']))
        {
            $Clean['Achievement'] = strip_tags(trim($_POST['txtAchievement']));
        }
        if (isset($_POST['txtAchievementDetails']))
        {
            $Clean['AchievementDetails'] = strip_tags(trim($_POST['txtAchievementDetails']));
        }

        if (isset($_POST['chkIsActive']))
        {
            $Clean['IsActive'] = 1;
        }

        $NewRecordValidator = new Validator();

        $NewRecordValidator->ValidateStrings($Clean['Achievement'], 'Achievement is required and should be between 3 and 150 characters.', 3, 150);
        $NewRecordValidator->ValidateStrings($Clean['AchievementDetails'], 'Achievement details is required and should be between 5 and 500 characters.', 5, 500);
        
        if ($NewRecordValidator->HasNotifications())
        {
            $HasErrors = true;
            break;
        }
                        
        $AchievementMasterToEdit->SetAchievement($Clean['Achievement']);
        $AchievementMasterToEdit->SetAchievementDetails($Clean['AchievementDetails']);

        $AchievementMasterToEdit->SetIsActive($Clean['IsActive']);

        if (!$AchievementMasterToEdit->Save())
        {
            $NewRecordValidator->AttachTextError(ProcessErrors($AchievementMasterToEdit->GetLastErrorCode()));
            $HasErrors = true;

            break;
        }
        
        header('location:achievements_master_list.php?Mode=UD');
        exit;
    break;

    case 2:
        $Clean['Achievement'] = $AchievementMasterToEdit->GetAchievement();
        $Clean['AchievementDetails'] = $AchievementMasterToEdit->GetAchievementDetails();
        
        $Clean['IsActive'] = $AchievementMasterToEdit->GetIsActive();

    break;
}

require_once('../html_header.php');
?> 
<title>Edit Achievement Master</title>
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
                    <h1 class="page-header">Edit Achievement Master</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="EditAchievementMaster" action="edit_achievements_master.php" method="post">
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
                            <div class="col-lg-5">
                                <input class="form-control" type="text" maxlength="150" id="Achievement" name="txtAchievement" value="<?php echo $Clean['Achievement']; ?>" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="AchievementDetails" class="col-lg-2 control-label">Achievement Details</label>
                            <div class="col-lg-5">
                                <textarea class="form-control" rows="5" id="AchievementDetails" name="txtAchievementDetails"><?php echo $Clean['AchievementDetails']; ?></textarea>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="IsActive" class="col-lg-2 control-label">Is Active</label>
                            <div class="col-lg-4">
                                <input type="checkbox" id="IsActive" name="chkIsActive" <?php echo ($Clean['IsActive'] == 1) ? 'checked="checked"' : ''; ?> value="1" />
                            </div>
                        </div>
                        <div class="form-group">
                        <div class="col-sm-offset-2 col-lg-10">
                            <input type="hidden" name="hdnProcess" value="3"/>
                            <input type="hidden" name="hdnAchievementMasterID" value="<?php echo $Clean['AchievementMasterID']; ?>" />
                            <button type="submit" class="btn btn-primary"><i class="fa fa-upload"></i>&nbsp;Update</button>
                            <button type="submit" class="btn btn-primary" name="btnCancel">Cancel</button>
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
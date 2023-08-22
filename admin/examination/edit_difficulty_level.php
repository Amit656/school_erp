<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once("../../classes/examination/class.difficulty_levels.php");

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

if ($LoggedUser->HasPermissionForTask(TASK_EDIT_DIFFICULTY_LEVEL) !== true)
{
    header('location:/admin/unauthorized_login_admin.php');
    exit;
}

$UserMenusArray = array();
$UserMenusArray = $LoggedUser->GetUserMenus();

if (!is_array($UserMenusArray) || count($UserMenusArray) <= 0)
{
    //header('location:../logout.php');
    //exit;
}

if (isset($_POST['btnCancel']))
{
    header('location:difficulty_level_list.php');
    exit;
}

$Clean = array();

$Clean['DifficultyLevelID'] = 0;

if (isset($_GET['DifficultyLevelID']))
{
    $Clean['DifficultyLevelID'] = (int) $_GET['DifficultyLevelID'];
}
else if (isset($_POST['hdnDifficultyLevelID']))
{
    $Clean['DifficultyLevelID'] = (int) $_POST['hdnDifficultyLevelID'];
}

if ($Clean['DifficultyLevelID'] <= 0)
{
    header('location:../error.php');
    exit;
}   

try
{
    $DifficultyLevelToEdit = new DifficultyLevel($Clean['DifficultyLevelID']);
}
catch (ApplicationDBException $e)
{
    header('location:../error.php');
    exit;
}
catch (Exception $e)
{
    header('location:../error.php');
    exit;
}

$HasErrors = false;

$Clean['Process'] = 0;

$Clean['DifficultyLevel'] = '';

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
        if (isset($_POST['txtDifficultyLevel']))
        {
            $Clean['DifficultyLevel'] = strip_tags(trim($_POST['txtDifficultyLevel']));
        }

        if (!isset($_POST['chkIsActive']))
        {
            $Clean['IsActive'] = 0;
        }
                
        $NewRecordValidator = new Validator();
        $NewRecordValidator->ValidateStrings($Clean['DifficultyLevel'], 'Difficulty Level is required and should be between 3 and 100 characters.', 3, 100);

        if ($NewRecordValidator->HasNotifications())
        {
            $HasErrors = true;
            break;
        }
                
        $DifficultyLevelToEdit->SetDifficultyLevel($Clean['DifficultyLevel']);
        $DifficultyLevelToEdit->SetIsActive($Clean['IsActive']);

        if (!$DifficultyLevelToEdit->Save())
        {
            $NewRecordValidator->AttachTextError(ProcessErrors($DifficultyLevelToEdit->GetLastErrorCode()));
            $HasErrors = true;

            break;
        }
        
        header('location:difficulty_level_list.php?Mode=UD');
        exit;
    break;

    case 2:
        $Clean['DifficultyLevel'] = $DifficultyLevelToEdit->GetDifficultyLevel();
        $Clean['IsActive'] = $DifficultyLevelToEdit->GetIsActive();

    break;
}

require_once('../html_header.php');
?>
<title>Edit Difficulty Level</title>
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
                    <h1 class="page-header">Edit Difficulty Level</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="EditFeeGroup" action="edit_difficulty_level.php" method="post">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        Enter Difficulty Level Details
                    </div>
                    <div class="panel-body">
<?php
                        if ($HasErrors == true)
                        {
                            echo $NewRecordValidator->DisplayErrors();
                        }
?>                    
                        <div class="form-group">
                            <label for="DifficultyLevel" class="col-lg-2 control-label">Difficulty Level</label>
                            <div class="col-lg-4">
                                <input class="form-control" type="text" maxlength="100" id="DifficultyLevel" name="txtDifficultyLevel" value="<?php echo $Clean['DifficultyLevel']; ?>" />
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
                            <input type="hidden" name="hdnDifficultyLevelID" value="<?php echo $Clean['DifficultyLevelID']; ?>" />
                            <button type="submit" class="btn btn-primary">Update</button>
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
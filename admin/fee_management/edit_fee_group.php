<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once("../../classes/fee_management/class.fee_groups.php");

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

if ($LoggedUser->HasPermissionForTask(TASK_ADD_EDIT_FEE_GROUP) !== true)
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
    header('location:fee_group_list.php');
    exit;
}

$Clean = array();

$Clean['FeeGroupID'] = 0;

if (isset($_GET['FeeGroupID']))
{
    $Clean['FeeGroupID'] = (int) $_GET['FeeGroupID'];
}
else if (isset($_POST['hdnFeeGroupID']))
{
    $Clean['FeeGroupID'] = (int) $_POST['hdnFeeGroupID'];
}

if ($Clean['FeeGroupID'] <= 0)
{
    header('location:../error.php');
    exit;
}   

try
{
    $FeeGroupToEdit = new FeeGroup($Clean['FeeGroupID']);
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

$Clean['FeeGroup'] = '';

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

        if (isset($_POST['txtFeeGroup']))
        {
            $Clean['FeeGroup'] = strip_tags(trim($_POST['txtFeeGroup']));
        }

        if (!isset($_POST['chkIsActive']))
        {
            $Clean['IsActive'] = 0;
        }
        
        $NewRecordValidator = new Validator();
        
        $NewRecordValidator->ValidateStrings($Clean['FeeGroup'], 'Fee group is required and should be between 3 and 30 characters.', 3, 30);
        
        if ($NewRecordValidator->HasNotifications())
        {
            $HasErrors = true;
            break;
        }
                        
        $FeeGroupToEdit->SetFeeGroup($Clean['FeeGroup']);

        $FeeGroupToEdit->SetIsActive($Clean['IsActive']);
        
        if (!$FeeGroupToEdit->Save())
        {
            $NewRecordValidator->AttachTextError(ProcessErrors($FeeGroupToEdit->GetLastErrorCode()));
            $HasErrors = true;

            break;
        }
        
        header('location:fee_group_list.php?Mode=UD');
        exit;
    break;

    case 2:        
        $Clean['FeeGroup'] = $FeeGroupToEdit->GetFeeGroup();

        $Clean['IsActive'] = $FeeGroupToEdit->GetIsActive();

    break;
}

require_once('../html_header.php');
?>
<title>Edit Fee Group</title>
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
                    <h1 class="page-header">Edit Fee Group</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="EditFeeGroup" action="edit_fee_group.php" method="post">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <strong>Enter Fee Group Details</strong>
                    </div>
                    <div class="panel-body">
<?php
                        if ($HasErrors == true)
                        {
                            echo $NewRecordValidator->DisplayErrors();
                        }
?>                                            
                        <div class="form-group">
                            <label for="FeeGroup" class="col-lg-2 control-label">Fee Group</label>
                            <div class="col-lg-4">
                                <input class="form-control" type="text" maxlength="30" id="FeeGroup" name="txtFeeGroup" value="<?php echo $Clean['FeeGroup']; ?>" />
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
                            <input type="hidden" name="hdnFeeGroupID" value="<?php echo $Clean['FeeGroupID']; ?>" />
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
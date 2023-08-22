<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once("../../classes/fee_management/class.fee_heads.php");

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

if ($LoggedUser->HasPermissionForTask(TASK_ADD_EDIT_FEE_HEAD) !== true)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}

if (isset($_POST['btnCancel']))
{
    header('location:fee_head_list.php');
    exit;
}

$Clean = array();

$Clean['FeeHeadID'] = 0;

if (isset($_GET['FeeHeadID']))
{
    $Clean['FeeHeadID'] = (int) $_GET['FeeHeadID'];
}
else if (isset($_POST['hdnFeeHeadID']))
{
    $Clean['FeeHeadID'] = (int) $_POST['hdnFeeHeadID'];
}

if ($Clean['FeeHeadID'] <= 0)
{
    header('location:../error.php');
    exit;
}   

try
{
    $FeeHeadToEdit = new FeeHead($Clean['FeeHeadID']);
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

$Clean['FeeHead'] = '';
$Clean['FeeHeadDescription'] = '';
$Clean['Priority'] = 0;
$Clean['IsActive'] = 1;
$Clean['Process'] = 0;

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
        if (isset($_POST['txtFeeHead']))
        {
            $Clean['FeeHead'] = strip_tags(trim($_POST['txtFeeHead']));
        }

        if (isset($_POST['txtFeeHeadDescription']))
        {
            $Clean['FeeHeadDescription'] = strip_tags(trim($_POST['txtFeeHeadDescription']));
        }

        if (isset($_POST['txtPriority']))
        {
            $Clean['Priority'] = (int) $_POST['txtPriority'];
        }
        
        if (!isset($_POST['chkIsActive']))
        {
            $Clean['IsActive'] = 0;
        }
                
        $NewRecordValidator = new Validator();
        
        $NewRecordValidator->ValidateStrings($Clean['FeeHead'], 'Fee head is required and should be between 3 and 50 characters.', 3, 50);

        $NewRecordValidator->ValidateStrings($Clean['FeeHeadDescription'], 'Fee head description is required and should be between 3 and 150 characters.', 3, 150);

        $NewRecordValidator->ValidateInteger($Clean['Priority'], 'please enter valid priority.', 1);
        
        if ($NewRecordValidator->HasNotifications())
        {
            $HasErrors = true;
            break;
        }
                
        $FeeHeadToEdit->SetFeeHead($Clean['FeeHead']);

        $FeeHeadToEdit->SetFeeHeadDescription($Clean['FeeHeadDescription']);

        $FeeHeadToEdit->SetPriority($Clean['Priority']);
        $FeeHeadToEdit->SetIsActive($Clean['IsActive']);

        if (!$FeeHeadToEdit->Save())
        {
            $NewRecordValidator->AttachTextError(ProcessErrors($FeeHeadToEdit->GetLastErrorCode()));
            $HasErrors = true;

            break;
        }
        
        header('location:fee_head_list.php?Mode=UD');
        exit;
    break;

    case 2:
        $Clean['FeeHead'] = $FeeHeadToEdit->GetFeeHead();

        $Clean['FeeHeadDescription'] = $FeeHeadToEdit->GetFeeHeadDescription();
        
        $Clean['Priority'] = $FeeHeadToEdit->GetPriority();
        $Clean['IsActive'] = $FeeHeadToEdit->GetIsActive();

    break;
}

require_once('../html_header.php');
?>
<title>Edit Fee Head</title>
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
                    <h1 class="page-header">Edit Fee Head</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="EditFeeGroup" action="edit_fee_head.php" method="post">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        Enter Fee Head Details
                    </div>
                    <div class="panel-body">
<?php
                        if ($HasErrors == true)
                        {
                            echo $NewRecordValidator->DisplayErrors();
                        }
?>                    
                        <div class="form-group">
                            <label for="FeeHead" class="col-lg-2 control-label">Fee Head</label>
                            <div class="col-lg-4">
                                <input class="form-control" type="text" maxlength="50" id="FeeHead" name="txtFeeHead" value="<?php echo $Clean['FeeHead']; ?>" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="FeeHeadDescription" class="col-lg-2 control-label">Description</label>
                            <div class="col-lg-4">
                                <textarea class="form-control"  id="FeeHeadDescription" name="txtFeeHeadDescription"><?php echo $Clean['FeeHeadDescription']; ?></textarea>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="Priority" class="col-lg-2 control-label">Priority</label>
                            <div class="col-lg-4">
                                <input class="form-control" type="text" maxlength="6" id="Priority" name="txtPriority" value="<?php echo ($Clean['Priority'] ? $Clean['Priority'] : ''); ?>" />
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
                            <input type="hidden" name="hdnFeeHeadID" value="<?php echo $Clean['FeeHeadID']; ?>" />
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
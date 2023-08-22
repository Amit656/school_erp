<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once("../../classes/hostel_management/class.wings.php");

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

$UserMenusArray = array();
$UserMenusArray = $LoggedUser->GetUserMenus();

if (!is_array($UserMenusArray) || count($UserMenusArray) <= 0)
{
    //header('location:../logout.php');
    //exit;
}

if (isset($_POST['btnCancel']))
{
    header('location:wings_list.php');
    exit;
}

$Clean = array();

$Clean['WingID'] = 0;

if (isset($_GET['WingID']))
{
    $Clean['WingID'] = (int) $_GET['WingID'];
}
else if (isset($_POST['hdnWingID']))
{
    $Clean['WingID'] = (int) $_POST['hdnWingID'];
}

if ($Clean['WingID'] <= 0)
{
    header('location:../error.php');
    exit;
}   

try
{
    $WingToEdit = new Wing($Clean['WingID']);
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

$WingForlist = array('Boys' => 'Boys', 'Girls' => 'Girls', 'Both' => 'Both');

$HasErrors = false;

$Clean['Process'] = 0;

$Clean['WingFor'] = '';
$Clean['WingName'] = '';

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
        if (isset($_POST['drdWingFor']))
        {
            $Clean['WingFor'] = strip_tags(trim($_POST['drdWingFor']));
        }

        if (isset($_POST['txtWingName']))
        {
            $Clean['WingName'] = strip_tags(trim($_POST['txtWingName']));
        }

        if (isset($_POST['chkIsActive']))
        {
            $Clean['IsActive'] = 1;
        }

        $NewRecordValidator = new Validator();

        $NewRecordValidator->ValidateInSelect($Clean['WingFor'], $WingForlist, 'Unknown Error, Please try again.');
        $NewRecordValidator->ValidateStrings($Clean['WingName'], 'Wing name is required and should be between 1 and 25 characters.', 1, 25);
        
        if ($NewRecordValidator->HasNotifications())
        {
            $HasErrors = true;
            break;
        }
                
        $WingToEdit->SetWingFor($Clean['WingFor']);
        $WingToEdit->SetWingName($Clean['WingName']);

        $WingToEdit->SetIsActive($Clean['IsActive']);

        if (!$WingToEdit->Save())
        {
            $NewRecordValidator->AttachTextError(ProcessErrors($WingToEdit->GetLastErrorCode()));
            $HasErrors = true;

            break;
        }
        
        header('location:wings_list.php?Mode=UD');
        exit;
    break;

    case 2:
        $Clean['WingFor'] = $WingToEdit->GetWingFor();
        $Clean['WingName'] = $WingToEdit->GetWingName();
        
        $Clean['IsActive'] = $WingToEdit->GetIsActive();

    break;
}

require_once('../html_header.php');
?>
<title>Edit Wing</title>
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
                    <h1 class="page-header">Edit Wing</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="EditWing" action="edit_wing.php" method="post">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        Enter Wing Details
                    </div>
                    <div class="panel-body">
<?php
                        if ($HasErrors == true)
                        {
                            echo $NewRecordValidator->DisplayErrors();
                        }
?>                    
                        <div class="form-group">
                        <label for="WingFor" class="col-lg-2 control-label">Wing For</label>
                            <div class="col-lg-4">
                                <select class="form-control" name="drdWingFor" id="WingFor">
<?php
                                if (is_array($WingForlist) && count($WingForlist) > 0)
                                {
                                    foreach($WingForlist as $WingForID => $WingForName)
                                    {
                                        echo '<option ' . (($Clean['WingFor'] == $WingForID) ? 'selected="selected"' : '' ) . ' value="' . $WingForID . '">' . $WingForName . '</option>';
                                    }
                                }
?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="WingName" class="col-lg-2 control-label">Wing Name</label>
                            <div class="col-lg-4">
                                <input class="form-control" type="text" maxlength="25" id="WingName" name="txtWingName" value="<?php echo $Clean['WingName']; ?>" />
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
                            <input type="hidden" name="hdnWingID" value="<?php echo $Clean['WingID']; ?>" />
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
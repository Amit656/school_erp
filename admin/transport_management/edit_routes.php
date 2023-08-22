<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once("../../classes/transport_management/class.routes.php");

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

if ($LoggedUser->HasPermissionForTask(TASK_EDIT_ROUTES) !== true)
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
    header('location:route_list.php');
    exit;
}

$Clean = array();

$Clean['RouteID'] = 0;

if (isset($_GET['RouteID']))
{
    $Clean['RouteID'] = (int) $_GET['RouteID'];
}
else if (isset($_POST['hdnRouteID']))
{
    $Clean['RouteID'] = (int) $_POST['hdnRouteID'];
}

if ($Clean['RouteID'] <= 0)
{
    header('location:../error.php');
    exit;
}   

try
{
    $RouteToEdit = new Route($Clean['RouteID']);
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

$Clean['RouteNumber'] = '';
$Clean['RouteName'] = '';

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
        if (isset($_POST['txtRouteNumber']))
        {
            $Clean['RouteNumber'] = strip_tags(trim($_POST['txtRouteNumber']));
        }

        if (isset($_POST['txtRouteName']))
        {
            $Clean['RouteName'] = strip_tags(trim($_POST['txtRouteName']));
        }

        if (!isset($_POST['chkIsActive']))
        {
            $Clean['IsActive'] = 0;
        }
                
        $NewRecordValidator = new Validator();
        $NewRecordValidator->ValidateStrings($Clean['RouteNumber'], 'Route number is required and should be between 1 and 50 characters.', 1, 50);

        $NewRecordValidator->ValidateStrings($Clean['RouteName'], 'Route name is required and should be between 1 and 250 characters.', 1, 250);

        if ($NewRecordValidator->HasNotifications())
        {
            $HasErrors = true;
            break;
        }
                
        $RouteToEdit->SetRouteNumber($Clean['RouteNumber']);
        $RouteToEdit->SetRouteName($Clean['RouteName']);

        $RouteToEdit->SetIsActive($Clean['IsActive']);

        if (!$RouteToEdit->Save())
        {
            $NewRecordValidator->AttachTextError(ProcessErrors($RouteToEdit->GetLastErrorCode()));
            $HasErrors = true;

            break;
        }
        
        header('location:route_list.php?Mode=UD');
        exit;
    break;

    case 2:
        $Clean['RouteNumber'] = $RouteToEdit->GetRouteNumber();

        $Clean['RouteName'] = $RouteToEdit->GetRouteName();

        $Clean['IsActive'] = $RouteToEdit->GetIsActive();

    break;
}

require_once('../html_header.php');
?>
<title>Edit Route</title>
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
                    <h1 class="page-header">Edit Route</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="EditFeeGroup" action="edit_routes.php" method="post">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        Enter Route Details
                    </div>
                    <div class="panel-body">
<?php
                        if ($HasErrors == true)
                        {
                            echo $NewRecordValidator->DisplayErrors();
                        }
?>                    
                        <div class="form-group">
                            <label for="RouteNumber" class="col-lg-2 control-label">Add Root No</label>
                            <div class="col-lg-6">
                                <input class="form-control" type="text" maxlength="50" id="RouteNumber" name="txtRouteNumber" value="<?php echo $Clean['RouteNumber']; ?>" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="RouteName" class="col-lg-2 control-label">Add Root Name</label>
                            <div class="col-lg-6">
                                <input class="form-control" type="text" maxlength="250" id="RouteName" name="txtRouteName" value="<?php echo ($Clean['RouteName'] ? $Clean['RouteName'] : ''); ?>" />
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="col-sm-offset-2 col-lg-10">
                                <input type="hidden" name="hdnProcess" value="3"/>
                                <input type="hidden" name="hdnRouteID" value="<?php echo $Clean['RouteID']; ?>" />
                                <button type="submit" class="btn btn-primary">Update</button>
                                <button type="submit" class="btn btn-primary" name="btnCancel">Cancel</button>
                            </div>
                      </div>
                      <div class="form-group">
                            <label for="IsActive" class="col-lg-2 control-label">Is Active</label>
                            <div class="col-lg-4">
                                <input type="checkbox" id="IsActive" name="chkIsActive" <?php echo ($Clean['IsActive'] == 1) ? 'checked="checked"' : ''; ?> value="1" />
                            </div>
                        </div>
                      <div class="form-group">
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
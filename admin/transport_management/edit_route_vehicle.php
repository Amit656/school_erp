<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once("../../classes/transport_management/class.vehicle.php");
require_once("../../classes/transport_management/class.routes.php");

require_once("../../classes/transport_management/class.route_vehicle.php");

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
    header('location:route_vehicle_report.php');
    exit;
}

$Clean = array();

$Clean['RouteVehicleID'] = 0;

if (isset($_GET['RouteVehicleID']))
{
    $Clean['RouteVehicleID'] = (int) $_GET['RouteVehicleID'];
}
else if (isset($_POST['hdnRouteVehicleID']))
{
    $Clean['RouteVehicleID'] = (int) $_POST['hdnRouteVehicleID'];
}

if ($Clean['RouteVehicleID'] <= 0)
{
    header('location:../error.php');
    exit;
}   

try
{
    $RouteVehicleToEdit = new RouteVehicle($Clean['RouteVehicleID']);
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

$RouteList = array();
$RouteList = Route::GetActiveRoutes();

$VehicleList = array();
$VehicleList = vehicle::GetActiveVehicle();

$ClassSectionsList =  array();

$HasErrors = false;

$Clean['Process'] = 0;

$Clean['RouteID'] = 0;
$Clean['VehicleID'] = 0;

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
        if (isset($_POST['drdRouteID'])) 
        {
            $Clean['RouteID'] = (int) $_POST['drdRouteID'];
        }
        if (isset($_POST['drdVehicleID'])) 
        {
            $Clean['VehicleID'] = (int) $_POST['drdVehicleID'];
        }
        if (isset($_POST['chkIsActive']))
        {
            $Clean['IsActive'] = 1;
        }

        $NewRecordValidator = new Validator();
        
            $NewRecordValidator->ValidateInSelect($Clean['RouteID'], $RouteList, 'Unkown Error Please try again.');
             $NewRecordValidator->ValidateInSelect($Clean['VehicleID'], $VehicleList, 'Unkown Error Please try again.');

        if ($NewRecordValidator->HasNotifications())
        {
            $HasErrors = true;
            break;
        }

        $RouteVehicleToEdit->SetRouteID($Clean['RouteID']); 
        $RouteVehicleToEdit->SetVehicleID($Clean['VehicleID']); 
        $RouteVehicleToEdit->SetIsActive($Clean['IsActive']);
        
        if (!$RouteVehicleToEdit->Save())
        {
            $NewRecordValidator->AttachTextError(ProcessErrors($RouteVehicleToEdit->GetLastErrorCode()));
            $HasErrors = true;

            break;
        }

        header('location:route_vehicle_report.php?Mode=UD');
        exit;
    break;

    case 2:
        $Clean['RouteID'] = $RouteVehicleToEdit->GetRouteID();
        $Clean['VehicleID'] = $RouteVehicleToEdit->GetVehicleID();

        $Clean['IsActive'] = $RouteVehicleToEdit->GetIsActive();


    break;
}

require_once('../html_header.php');
?>
<title>Give Vehicle To Route</title>
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
                    <h1 class="page-header">Give Vehicle To Route</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="EditRouteVehicle" action="edit_route_vehicle.php" method="post">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        Enter Route Vehicle Details
                    </div>
                    <div class="panel-body">
<?php
                        if ($HasErrors == true)
                        {
                            echo $NewRecordValidator->DisplayErrors();
                        }
?>                    

                       <div class="form-group">
                                <label for="RouteID" class="col-lg-2 control-label">Select route Name.</label>
                                <div class="col-lg-3">
                                    <select class="form-control"  name="drdRouteID" id="Route">
                                        <option  value="0" >-- Routes --</option>
<?php

                                if (is_array($RouteList) && count($RouteList) > 0)
                                {
                                    foreach($RouteList as $RouteID => $RouteName)
                                    {
    ?>
                                            <option <?php echo ($RouteID == $Clean['RouteID'] ? 'selected="selected"' : ''); ?> value="<?php echo $RouteID; ?>"><?php echo $RouteName; ?></option>
    <?php
                                        }
                                    }
?>
                                    </select>
                                </div>
                                <label for="VehicleID" class="col-lg-2 control-label">Select Vehicle</label>
                            <div class="col-lg-3"> 
                                 <select class="form-control"  name="drdVehicleID" id="Vehicles">
                                        <option  value="0" >-- Vehicles --</option>
<?php
                                if (is_array($VehicleList) && count($VehicleList) > 0)
                                {
                                    foreach($VehicleList as $VehicleID => $vehicleName)
                                    {
?>                                        
                                         <option <?php echo ($VehicleID == $Clean['VehicleID'] ? 'selected="selected"' : ''); ?> value="<?php echo $VehicleID; ?>"><?php echo $vehicleName; ?></option>    
    <?php                                                   
                                    }
                                }
?>
                                </select>
                            </div >
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
                            <input type="hidden" name="hdnRouteVehicleID" value="<?php echo $Clean['RouteVehicleID']; ?>" />
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
<script type="text/javascript">
    /*$(document).ready(function(){

        $('select').hover(function(){
        $(this).prop('title', "You can't change it.");

        $('select').css('pointer-events','none');
    });
    });*/
</script>
</body>
</html>
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

if ($LoggedUser->HasPermissionForTask(TASK_ASSIGN_ROUTE_VEHICLE) !== true)
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

$HasErrors = false;

$RouteList = array();
$RouteList = Route::GetActiveRoutes();

$VehicleList = array();
$VehicleList = Vehicle::GetActiveVehicle();

$Clean = array();
$Clean['Process'] = 0;

$Clean['RouteVehicleID'] = 0;
$Clean['RouteID'] = 0;
$Clean['VehicleID'] = 0;

if (isset($_POST['hdnProcess']))
{
    $Clean['Process'] = (int) $_POST['hdnProcess'];
}
switch ($Clean['Process'])
{
    case 1:
        if (isset($_POST['drdRouteID']))
        {
            $Clean['RouteID'] = strip_tags(trim($_POST['drdRouteID']));
        }
        if (isset($_POST['drdVehicleID']))
        {
            $Clean['VehicleID'] = strip_tags(trim($_POST['drdVehicleID']));
        }

        $NewRecordValidator = new Validator();
        
        $NewRecordValidator->ValidateInSelect($Clean['RouteID'], $RouteList, 'Please select a valid route.');
        $NewRecordValidator->ValidateInSelect($Clean['VehicleID'], $VehicleList, 'Please select a valid vehicle.');
        
        if ($NewRecordValidator->HasNotifications())
        {
            $HasErrors = true;
            break;
        }
                
        $NewRouteVehicle = new RouteVehicle();
        
        $NewRouteVehicle->SetRouteID($Clean['RouteID']);
        $NewRouteVehicle->SetVehicleID($Clean['VehicleID']);
        $NewRouteVehicle->SetIsActive(1);
        $NewRouteVehicle->SetCreateUserID($LoggedUser->GetUserID());

        if (!$NewRouteVehicle->Save())
        {
            $NewRecordValidator->AttachTextError(ProcessErrors($NewRouteVehicle->GetLastErrorCode()));
            $HasErrors = true;
            break;
        }
        header('location:route_vehicle_report.php?Mode=AS');
        exit;
    break;
}

require_once('../html_header.php');
?>
<title>Assign Route To Vehilce</title>
<link href="vendor/jquery-ui/jquery-ui.min.css" rel="stylesheet">
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
                    <h1 class="page-header">Assign Route Vehicle</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="AssignRouteVehicle" action="assign_route_vehicle.php" method="post">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <strong>Assign Route To Vehicle</strong>
                    </div>
                    <div class="panel-body">
<?php
                        if ($HasErrors == true)
                        {
                            echo $NewRecordValidator->DisplayErrors();
                        }
?>       
                          <div class="form-group">
                                <label for="Routes" class="col-lg-2 control-label">Routes</label>
                                <div class="col-lg-3">
                                    <select class="form-control"  name="drdRouteID" id="Routes">
                                        <option  value="0" >-- All Routes --</option>
<?php
                                if (is_array($RouteList) && count($RouteList) > 0)
                                {
                                    foreach($RouteList as $RouteID => $RouteNumber)
                                    {
                                        echo '<option ' . (($Clean['RouteID'] == $RouteID) ? 'selected="selected"' : '' ) . ' value="' . $RouteID . '">' . $RouteNumber . '</option>';
                                    }
                                }
?>
                                    </select>
                                </div>
                             <div class="form-group">
                                <label for="Vehicles" class="col-lg-2 control-label">Vehicles</label>
                                <div class="col-lg-3">
                                    <select class="form-control"  name="drdVehicleID" id="Vehicles">
                                        <option  value="0" >-- All Vehicles --</option>
<?php
                                if (is_array($VehicleList) && count($VehicleList) > 0)
                                {
                                    foreach($VehicleList as $VehicleID => $VehicleName)
                                    {
                                        echo '<option ' . (($Clean['VehicleID'] == $VehicleID) ? 'selected="selected"' : '' ) . ' value="' . $VehicleID . '">' . $VehicleName .'</option>';
                                    }
                                }
?>
                                    </select>
                                </div>
                           </div>
                        </div>
                           
                        <div class="form-group">
                        <div class="col-sm-offset-2 col-lg-10">
                            <input type="hidden" name="hdnProcess" value="1" />
                            <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i>&nbsp;Assign</button>
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
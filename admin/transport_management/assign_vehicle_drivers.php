<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once("../../classes/transport_management/class.vehicle.php");
require_once("../../classes/transport_management/class.driver.php");
require_once("../../classes/transport_management/class.vehicle_drivers.php");

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

if ($LoggedUser->HasPermissionForTask(TASK_ASSIGN_VEHICLE_DRIVER) !== true)
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

$VehicleList = array();
$VehicleList = Vehicle::GetActiveVehicle();

$DriverList = array();
$DriverList = Driver::GetActiveDriver();

$Clean = array();
$Clean['Process'] = 0;

$Clean['VehicleDriverID'] = 0;
$Clean['VehicleID'] = 0;
$Clean['DriverID'] = 0;

if (isset($_POST['hdnProcess']))
{
    $Clean['Process'] = (int) $_POST['hdnProcess'];
}
switch ($Clean['Process'])
{
    case 1:
        if (isset($_POST['drdVehicleID'])) 
        {
            $Clean['VehicleID'] = (int) $_POST['drdVehicleID'];
        }
        if (isset($_POST['drdDriverID'])) 
        {
            $Clean['DriverID'] = (int) $_POST['drdDriverID'];
        }

        $NewRecordValidator = new Validator();

        $NewRecordValidator->ValidateInSelect($Clean['VehicleID'], $VehicleList, 'Please select a valid vehicle.');
        $NewRecordValidator->ValidateInSelect($Clean['DriverID'], $DriverList, 'Please select a valid driver.');
        
        if ($NewRecordValidator->HasNotifications())
        {
            $HasErrors = true;
            break;
        }
                
        $NewVehicleDriver = new VehicleDrivers();

        $NewVehicleDriver->SetVehicleID($Clean['VehicleID']);
        $NewVehicleDriver->SetDriverID($Clean['DriverID']);
        $NewVehicleDriver->SetIsActive(1);
        $NewVehicleDriver->SetCreateUserID($LoggedUser->GetUserID());

        if (!$NewVehicleDriver->Save())
        {
            $NewRecordValidator->AttachTextError(ProcessErrors($NewVehicleDriver->GetLastErrorCode()));
            $HasErrors = true;
            break;
        }
        header('location:vehicle_drivers_report.php?Mode=AS');
        exit;
    break;
}

require_once('../html_header.php');
?>
<title>Assign Vehicle To Driver</title>
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
                    <h1 class="page-header">Assign Vehicle To Driver</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="AddVehicleType" action="assign_vehicle_drivers.php" method="post">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <strong>Assign Vehicle To Driver</strong>
                    </div>
                    <div class="panel-body">
<?php
                        if ($HasErrors == true)
                        {
                            echo $NewRecordValidator->DisplayErrors();
                        }
?>       
                          <div class="form-group">
                                <label for="VehicleID" class="col-lg-2 control-label">Vehicles</label>
                                <div class="col-lg-4">
                                    <select class="form-control"  name="drdVehicleID" id="VehicleID">
                                        <option  value="0" >-- All Vehicles --</option>
<?php
                                if (is_array($VehicleList) && count($VehicleList) > 0)
                                {
                                    foreach($VehicleList as $VehicleID => $VehicleName)
                                    {
                                        echo '<option ' . (($Clean['VehicleID'] == $VehicleID) ? 'selected="selected"' : '' ) . ' value="' . $VehicleID . '">' . $VehicleName . '</option>';
                                    }
                                }
?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="DriverID" class="col-lg-2 control-label">Drivers</label>
                                <div class="col-lg-4">
                                    <select class="form-control"  name="drdDriverID" id="DriverID">
                                        <option  value="0" >-- All Drivers --</option>
<?php
                                if (is_array($DriverList) && count($DriverList) > 0)
                                {
                                    foreach($DriverList as $DriverID => $DriverName)
                                    {
                                        echo '<option ' . (($Clean['DriverID'] == $DriverID) ? 'selected="selected"' : '' ) . ' value="' . $DriverID . '">' . $DriverName . '</option>';
                                    }
                                }
?>
                                    </select>
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
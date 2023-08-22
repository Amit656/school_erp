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

$UserMenusArray = array();
$UserMenusArray = $LoggedUser->GetUserMenus();

if (!is_array($UserMenusArray) || count($UserMenusArray) <= 0)
{
    //header('location:../logout.php');
    //exit;
}

if (isset($_POST['btnCancel']))
{
    header('location:vehicle_drivers_report.php');
    exit;
}

$Clean = array();

$Clean['VehicleDriverID'] = 0;

if (isset($_GET['VehicleDriverID']))
{
    $Clean['VehicleDriverID'] = (int) $_GET['VehicleDriverID'];
}
else if (isset($_POST['hdnVehicleDriverID']))
{
    $Clean['VehicleDriverID'] = (int) $_POST['hdnVehicleDriverID'];
}

if ($Clean['VehicleDriverID'] <= 0)
{
    header('location:../error.php');
    exit;
}   

try
{
    $VehicleDriversToEdit = new VehicleDrivers($Clean['VehicleDriverID']);
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

$VehicleList = array();
$VehicleList = Vehicle::GetActiveVehicle();

$DriverList = array();
$DriverList = Driver::GetActiveDriver();

$HasErrors = false;

$Clean['Process'] = 0;

$Clean['VehicleID'] = 0;
$Clean['DriverID'] = 0;

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
        if (isset($_POST['drdVehicle']))
        {
            $Clean['VehicleID'] = strip_tags(trim($_POST['drdVehicle']));
        }
        if (isset($_POST['drdDriverName']))
        {
            $Clean['DriverID'] = strip_tags(trim($_POST['drdDriverName']));
        }

        if (!isset($_POST['chkIsActive']))
        {
            $Clean['IsActive'] = 0;
        }
             
        $NewRecordValidator = new Validator();

        if ($Clean['VehicleID'] != 0)
        {
           $NewRecordValidator->ValidateInSelect($Clean['VehicleID'], $VehicleList, 'Unknown Error, Please try again.');
        }
        
        if ($Clean['DriverID'] != 0)
        {
            $NewRecordValidator->ValidateInSelect($Clean['DriverID'], $DriverList, 'Unknown Error, Please try again.');
        }

        if ($Clean['ActiveStatus'] < 0 || $Clean['ActiveStatus'] > 2)
        {
            $NewRecordValidator->AttachTextError('Unknown Error, Please try again.');
        }

        if ($NewRecordValidator->HasNotifications())
        {
            $HasErrors = true;
            break;
        }

        $VehicleDriversToEdit->SetVehicleID($Clean['VehicleID']);
        $VehicleDriversToEdit->SetDriverID($Clean['DriverID']);
        
        $VehicleDriversToEdit->SetIsActive($Clean['IsActive']);

        if (!$VehicleDriversToEdit->Save())
        {
            $NewRecordValidator->AttachTextError(ProcessErrors($VehicleDriversToEdit->GetLastErrorCode()));
            $HasErrors = true;

            break;
        }
        header('location:vehicle_drivers_report.php?Mode=UD');
        exit;
    break;

    case 2:
        $Clean['VehicleID'] = $VehicleDriversToEdit->GetVehicleID();
        $Clean['DriverID'] = $VehicleDriversToEdit->GetDriverID();

        $Clean['IsActive'] = $VehicleDriversToEdit->GetIsActive();

    break;
}
require_once('../html_header.php');
?>
<title>Edit Vehicle Driver</title>
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
                    <h1 class="page-header">Edit Vehicle Driver</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="EditFeeGroup" action="edit_assign_vehicle_drivers.php" method="post">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        Enter Vehicle Driver Details
                    </div>
                    <div class="panel-body">
<?php
                        if ($HasErrors == true)
                        {
                            echo $NewRecordValidator->DisplayErrors();
                        }
?>                    
                          <div class="form-group">
                                <label for="Vehicle" class="col-lg-2 control-label">Vehicles</label>
                                <div class="col-lg-4">
                                    <select class="form-control"  name="drdVehicle" id="Vehicle">
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
                                <label for="DriverName" class="col-lg-2 control-label">Drivers</label>
                                <div class="col-lg-4">
                                    <select class="form-control"  name="drdDriverName" id="DriverName">
                                        <option  value="0" >-- All Drivers --</option>
<?php
                                if (is_array($DriverList) && count($DriverList) > 0)
                                {
                                    foreach($DriverList as $DriverID => $DriverName)
                                    {
                                        echo '<option ' . (($Clean['DriverID'] == $DriverID) ? 'selected="selected"' : '' ) . ' value="' . $DriverID . '">' . $DriverName .'</option>';
                                    }
                                }
?>
                                    </select>
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
                            <input type="hidden" name="hdnVehicleDriverID" value="<?php echo $Clean['VehicleDriverID']; ?>" />
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
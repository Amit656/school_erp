
v<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");
require_once("../../classes/class.date_processing.php");

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

if ($LoggedUser->HasPermissionForTask(TASK_EDIT_VEHICLE_DRIVER) !== true)
{
    header('location:/admin/unauthorized_login_admin.php');
    exit;
}

$UserMenusArray = array();
$UserMenusArray = $LoggedUser->GetUserMenus();
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
    $VehicleDriverToEdit = new VehicleDrivers($Clean['VehicleDriverID']);
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

$VehicleNameList =  array();
$VehicleNameList = VehicleDrivers::GetActiveVehicleDrivers();

$HasErrors = false;

$Clean['Process'] = 0;

$Clean['VehicleDriverID'] = 0;
$Clean['VehicleID'] = 0;

$Clean['VehicleName'] = '';
$Clean['DriverName'] = '';

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
        if (isset($_POST['drdVehicleName']))
        {
            $Clean['VehicleID'] = (int) $_POST['drdVehicleName'];
        }
        if (isset($_POST['txtDriverName']))
        {
            $Clean['DriverName'] = strip_tags(trim($_POST['txtDriverName']));
        }
        if (isset($_POST['chkIsActive']))
        {
            $Clean['IsActive'] = 1;
        }

        $NewRecordValidator = new Validator();
        $NewRecordValidator->ValidateInSelect($Clean['VehicleID'], $VehicleNameList, 'Unkown error please try again.');
        $NewRecordValidator->ValidateStrings($Clean['DriverName'], 'Driver name is required and should be between 1 and 60 characters.', 1, 60);
        
        if ($NewRecordValidator->HasNotifications())
        {
            $HasErrors = true;
            break;
        }

        $VehicleDriversToEdit->SetVehicleID($Clean['VehicleName']);
        $VehicleDriversToEdit->SetDriverName($Clean['DriverName']);
        $VehicleToEdit->SetIsActive($Clean['IsActive']);

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
        $Clean['VehicleName'] = $VehicleDriverToEdit->GetVehicleID();   
        $Clean['DriverName'] = $VehicleDriverToEdit->GetDriverID();
        $Clean['IsActive'] = $VehicleDriverToEdit->GetIsActive();

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
             <form class="form-horizontal" name="EditVehicleDriver" action="edit_vehicle_drivers.php" method="post">
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
                                <label for="RouteNumber" class="col-lg-2 control-label">select route no.</label>
                                <div class="col-lg-3">
                                    <select class="form-control"  name="drdRouteID" id="RouteNumber">
                                        <option  value="0" >-- route--</option>
<?php
                                if (is_array($RoutesList) && count($RoutesList) > 0)
                                {
                                    foreach($RoutesList as $RouteID => $RouteNumber)
                                    {
                                        echo '<option ' . (($Clean['RouteID'] == $RouteID) ? 'selected="selected"' : '' ) . ' value="' . $RouteID . '">' . $RouteNumber . '</option>';
                                    }
                                }
?>
                                    </select>
                                </div>
                             <div class="form-group">
                                <label for="AreaName" class="col-lg-2 control-label">select area</label>
                                <div class="col-lg-3">
                                    <select class="form-control"  name="drdAreaID" id="AreaName">
                                        <option  value="0" >-- area --</option>
<?php
                                if (is_array($AreaList) && count($AreaList) > 0)
                                {
                                    foreach($AreaList as $AreaID => $AreaName)
                                    {
                                        echo '<option ' . (($Clean['AreaID'] == $AreaID) ? 'selected="selected"' : '' ) . ' value="' . $AreaID . '">' . $AreaName .'</option>';
                                    }
                                }
?>
                                    </select>
                                </div>
                            </div>
                         </div>
                         
                        <div class="form-group">
                            <label for="DriverName" class="col-lg-2 control-label">Drivers</label>
                            <div class="col-lg-4">
                                <input class="form-control" type="text" maxlength="60" id="DriverName" name="txtDriverName" value="<?php echo $Clean['DriverName']; ?>" />
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
<script src="../vendor/jquery-ui/jquery-ui.min.js"></script>

<script type="text/javascript">
$(document).ready(function(){ 

    $(".select-date").datepicker({
        changeMonth: true,
        changeYear: true,
        dateFormat: 'dd/mm/yy'
    });
}); 
</script>
</body>
</html>_
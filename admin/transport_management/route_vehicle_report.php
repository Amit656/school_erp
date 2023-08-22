<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once("../../classes/class.ui_helpers.php");

require_once("../../classes/transport_management/class.routes.php");
require_once('../../classes/transport_management/class.vehicle.php');

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
    header('location:/admin/unauthorized_login_admin.php');
    exit;
}
catch (Exception $e)
{
    header('location:/admin/unauthorized_login_admin.php');
    exit;
}
// END OF 1. //

if ($LoggedUser->HasPermissionForTask(TASK_REPORT_ROUTE_VEHICLE) !== true)
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

$Filters = array();

$RouteList = array();
$RouteList = Route::GetActiveRoutes();

$VehicleList = array();
$VehicleList = Vehicle::GetActiveVehicle();

$RouteVehicleList = array();

$RecordDeletedSuccessfully = false;
$HasErrors = false;
$TotalRecords = 0;

$Clean = array();
$Clean['Process'] = 0;

$Clean['RouteID'] = 0;
$Clean['VehicleID'] = 0;

$Clean['ActiveStatus'] = 0;

// paging and sorting variables start here  //
$Clean['AllRecords'] = '';
$Clean['CurrentPage'] = 1;
$TotalPages = 0;

$Start = 0;
$Limit = 3;
// end of paging variables//

if (isset($_GET['hdnProcess']))
{
    $Clean['Process'] = (int) $_GET['hdnProcess'];
}
elseif (isset($_GET['Process']))
{
    $Clean['Process'] = (int) $_GET['Process'];
}
switch ($Clean['Process'])
{
    case 5:
        if ($LoggedUser->HasPermissionForTask(TASK_DELETE_ROUTE_VEHICLE) !== true)
        {
            header('location:/admin/unauthorized_login_admin.php');
            exit;
        }
        
        if (isset($_GET['RouteVehicleID']))
        {
            $Clean['RouteVehicleID'] = (int) $_GET['RouteVehicleID'];           
        }
        
        if ($Clean['RouteVehicleID'] <= 0)
        {
            header('location:../error_page.php');
            exit;
        }                       
            
        try
        {
            $RouteVehicleToDelete = new RouteVehicle($Clean['RouteVehicleID']);
        }
        catch (ApplicationDBException $e)
        {
            header('location:../error_page.php');
            exit;
        }
        catch (Exception $e)
        {
            header('location:../error_page.php');
            exit;
        }
        
        // $SearchValidator = new Validator();
        
        // if ($RouteVehicleToDelete->CheckDependencies())
        // {
        //     $SearchValidator->AttachTextError('This Student Vehicle cannot be deleted. There are dependent records for this Student Vehicle.');
        //     $HasErrors = true;
        //     break;
        // }
                
        if (!$RouteVehicleToDelete->Remove())
        {
            $SearchValidator->AttachTextError(ProcessErrors($RouteVehicleToDelete->GetLastErrorCode()));
            $HasErrors = true;
            break;
        }
        
        $RecordDeletedSuccessfully = true;
    break;

    case 7:        
        if (isset($_GET['drdRoute']))
        {
            $Clean['RouteID'] = (int) $_GET['drdRoute'];
        }
        elseif (isset($_GET['RouteID']))
        {
            $Clean['RouteID'] = (int) $_GET['RouteID'];
        }
        if (isset($_GET['drdVehicle']))
        {
            $Clean['VehicleID'] = (int) $_GET['drdVehicle'];
        }
        elseif (isset($_GET['VehicleID']))
        {
            $Clean['VehicleID'] = (int) $_GET['VehicleID'];
        }
        if (isset($_GET['optActiveStatus']))
        {
            $Clean['ActiveStatus'] = (int) $_GET['optActiveStatus'];
        }
        elseif (isset($_GET['ActiveStatus']))
        {
            $Clean['ActiveStatus'] = (int) $_GET['ActiveStatus'];
        }

        $SearchValidator = new Validator();

        if ($Clean['RouteID'] != 0)
        {
            $SearchValidator->ValidateInSelect($Clean['RouteID'], $RouteList, 'Please select a valid route.');
        }

        if ($Clean['VehicleID'] != 0)
        {
            $SearchValidator->ValidateInSelect($Clean['VehicleID'], $VehicleList, 'Please select a valid vehicle.');
        }
       

        if ($Clean['ActiveStatus'] < 0 || $Clean['ActiveStatus'] > 2)
        {
            $SearchValidator->AttachTextError('Unknown Error, Please try again.');
        }
        
        if ($SearchValidator->HasNotifications())
        {
            $HasErrors = true;
            break;
        }

        //set record filters
        $Filters['RouteID'] = $Clean['RouteID'];
        $Filters['VehicleID'] = $Clean['VehicleID'];
        $Filters['ActiveStatus'] = $Clean['ActiveStatus'];

        //get records count
        RouteVehicle::SearchRouteVehicles($TotalRecords, true, $Filters);

        if ($TotalRecords > 0)
        {
            // Paging and sorting calculations start here.
            $TotalPages = (($TotalRecords % $Limit) == 0) ? $TotalRecords / $Limit : floor($TotalRecords / $Limit) + 1;

            if (isset($_GET['CurrentPage']))
            {
                $Clean['CurrentPage'] = (int) $_GET['CurrentPage'];
            }

            if (isset($_GET['AllRecords']))
            {
                $Clean['AllRecords'] = (string) $_GET['AllRecords'];
            }

            if ($Clean['CurrentPage'] <= 0)
            {
                $Clean['CurrentPage'] = 1;
            }
            elseif ($Clean['CurrentPage'] > $TotalPages)
            {
                $Clean['CurrentPage'] = $TotalPages;
            }

            if ($Clean['CurrentPage'] > 1)
            {
                $Start = ($Clean['CurrentPage'] - 1) * $Limit;
            }
            // end of Paging and sorting calculations.
            // now get the actual  records
            if ($Clean['AllRecords'] == 'All')
            {
                $RouteVehicleList = RouteVehicle::SearchRouteVehicles($TotalRecords, false, $Filters, 0, $TotalRecords);
            }
            else
            {
                $RouteVehicleList = RouteVehicle::SearchRouteVehicles($TotalRecords, false, $Filters, $Start, $Limit);
            }
        }
        break;
}

$LandingPageMode = '';
if (isset($_GET['Mode']))
{
    $LandingPageMode = $_GET['Mode'];
}

require_once('../html_header.php');
?>
<title>Route Vehicle Report</title>
<!-- DataTables CSS -->
<link href="../vendor/datatables-plugins/dataTables.bootstrap.css" rel="stylesheet">

<!-- DataTables Responsive CSS -->
<link href="../vendor/datatables-responsive/dataTables.responsive.css" rel="stylesheet">
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
                    <h1 class="page-header">Route Vehicle Report</h1>
                </div>  
                <!-- /.col-lg-12 -->
            </div>
            <form class="form-horizontal" name="frmRouteVehicleReport" action="route_vehicle_report.php" method="get">
                <div class="panel panel-default" id="accordion">
                    <div class="panel-heading">
                        <strong><a data-toggle="collapse" data-parent="#accordion" href="#collapseOne">Filters</a></strong>
                    </div>
                    <div id="collapseOne" class="panel-collapse collapse in">
                        <div class="panel-body">
<?php
                            if ($HasErrors == true)
                            {
                                echo $SearchValidator->DisplayErrors();
                            }
                            else if ($LandingPageMode == 'AS')
                            {
                                echo '<div class="alert alert-success">Record saved successfully.</div>';
                            }
                            else if ($RecordDeletedSuccessfully == true)
                            {
                                echo '<div class="alert alert-danger">Record deleted successfully.</div>';
                            }
                            else if ($LandingPageMode == 'UD')
                            {
                                echo '<div class="alert alert-success">Record updated successfully.</div>';
                            }
?>
                            <div class="form-group">
                                <label for="Route" class="col-lg-2 control-label">Route</label>
                                <div class="col-lg-4">
                                    <select class="form-control"  name="drdRoute" id="Route">
                                        <option  value="0" >-- All Route --</option>
<?php
                                        if (is_array($RouteList) && count($RouteList) > 0)
                                        {
                                            foreach ($RouteList as $RouteID => $RouteNumber)
                                            {
?>
                                                <option <?php echo ($RouteID == $Clean['RouteID'] ? 'selected="selected"' : ''); ?> value="<?php echo $RouteID; ?>"><?php echo $RouteNumber; ?></option>
<?php
                                            }
                                        }
?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                               <label for="VehicleID" class="col-lg-2 control-label">Vehicle</label>
                                <div class="col-lg-4">
                                    <select class="form-control"  name="drdVehicle" id="Vehicle">
                                        <option  value="0" >-- All Vehicle --</option>
<?php
                                if (is_array($VehicleList) && count($VehicleList) > 0)
                                {
                                    foreach($VehicleList as $VehicleID => $VehicleName)
                                    {
                                                echo '<option ' . ($Clean['VehicleID'] == $VehicleID ? 'selected="selected"' : '') . ' value="' . $VehicleID . '">' . $VehicleName . '</option>' ;
                                            }
                                        }
?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="ActiveStatus" class="col-lg-2 control-label">Status</label>
                                <div class="col-lg-6">
                                    <label class="radio-inline">
                                        <input type="radio" <?php echo (($Clean['ActiveStatus'] == 0) ? 'checked="checked"' : ''); ?> name="optActiveStatus" id="ActiveStatus" value="0" checked>All Route vehicle
                                    </label>
                                    <label class="radio-inline">
                                        <input type="radio" <?php echo (($Clean['ActiveStatus'] == 1) ? 'checked="checked"' : ''); ?> name="optActiveStatus" id="ActiveStatus" value="1">Active Route vehicle
                                    </label>
                                    <label class="radio-inline">
                                        <input type="radio" <?php echo (($Clean['ActiveStatus'] == 2) ? 'checked="checked"' : ''); ?> name="optActiveStatus" id="ActiveStatus" value="2">In-Active Route vehicle
                                    </label>
                                </div>
                            </div>                    
                            <div class="form-group">
                                <div class="col-lg-offset-2 col-lg-10">
                                    <input type="hidden" name="hdnProcess" value="7" />
                                    <button type="submit" class="btn btn-primary"><i class="fa fa-search"></i>&nbsp;Search</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    </div>
            </form>
            <!-- /.row -->
<?php
        if ($Clean['Process'] == 7 && $HasErrors == false)
        {
            $ReportHeaderText = '';

            if ($Clean['RouteID'] != 0)
            {
                $ReportHeaderText .= ' RouteName: ' . $RouteList[$Clean['RouteID']] . ',';
            }

            if ($Clean['VehicleID'] != 0)
            {
                $ReportHeaderText .= ' VehicleName: ' . $VehicleList[$Clean['VehicleID']] . ',';
            }

            if ($Clean['ActiveStatus'] == 1)
            {
                $ReportHeaderText .= ' Status: Active,';    
            }
            else if ($Clean['ActiveStatus'] == 2)
            {
                $ReportHeaderText .= ' Status: In-Active,';
            }   

            if ($ReportHeaderText != '')
            {
                $ReportHeaderText = ' for' . rtrim($ReportHeaderText, ',');
            }
?>
            <div class="row">
                <div class="col-lg-12">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <strong>Total Records Returned: <?php echo $TotalRecords; ?></strong>
                        </div>
                        <!-- /.panel-heading -->
                        <div class="panel-body">
                            <div>
                                <div class="row">
                                    <div class="col-lg-6">
<?php
                                    if ($TotalPages > 1)
                                    {
                                        $AllParameters = array('Process' => '7', 'RouteID' => $Clean['RouteID'], 'VehicleID' => $Clean['VehicleID'], 'ActiveStatus' => $Clean['ActiveStatus']);
                                        echo UIHelpers::GetPager('route_vehicle_report.php', $TotalPages, $Clean['CurrentPage'], $AllParameters);
                                    }
?>                                        
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="print-btn-container"><button id="PrintButton" type="submit" class="btn btn-primary">Print</button></div>
                                    </div>
                                </div>
                                <div class="row" id="RecordTableHeading">
                                    <div class="col-lg-12">
                                        <div class="report-heading-container"><strong>Route Vehicle Report on <?php echo date('d-m-Y h:i A') . $ReportHeaderText; ?></strong></div>
                                    </div>
                                </div>
                                <div class="row" id="RecordTable">
                                    <div class="col-lg-12">
                                        <table width="100%" class="table table-striped table-bordered table-hover" id="DataTableRecords">
                                            <thead>
                                                <tr>
                                                    <th>S.No</th>
                                                    <th>Route Number</th>
                                                    <th>Route Name</th>
                                                    <th>Vehicle Name</th>
                                                    <th>Vehicle Number</th>
                                                    <th>Is Active</th>
                                                    <th>Create User</th>
                                                    <th>Create Date</th>
                                                    <th class="print-hidden">Operations</th>
                                                </tr>
                                            </thead>
                                            <tbody>
<?php
                                    if (is_array($RouteVehicleList) && count($RouteVehicleList) > 0)
                                    {
                                        $Counter = $Start;
                                        foreach ($RouteVehicleList as $RouteVehicleID => $RouteVehicleDetails)
                                        {
?>
                                                <tr>
                                                    <td><?php echo ++$Counter; ?></td>
                                                    <td><?php echo $RouteVehicleDetails['RouteNumber']; ?></td>
                                                    <td><?php echo $RouteVehicleDetails['RouteName']; ?></td>
                                                    <td><?php echo $RouteVehicleDetails['VehicleName']; ?></td>
                                                    <td><?php echo $RouteVehicleDetails['VehicleNumber']; ?></td>
                                                   <td><?php echo (($RouteVehicleDetails['IsActive']) ? 'Yes' : 'No'); ?></td> 
                                                    <td><?php echo $RouteVehicleDetails['CreateUserName']; ?></td>
                                                    <td><?php echo date('d/m/Y', strtotime($RouteVehicleDetails['CreateDate'])); ?></td>
                                                    <td class="print-hidden">
<?php
                                                    // echo '<a href="edit_route_vehicle.php?Process=2&amp;RouteVehicleID=' . $RouteVehicleID . '">Edit</a>';
                                                    // echo '&nbsp;|&nbsp;';
                                                    // echo '<a href="route_vehicle_report.php?Process=5&amp;RouteVehicleID=' . $RouteVehicleID . '" class="delete-record">Delete</a>';

                                                    if ($LoggedUser->HasPermissionForTask(TASK_EDIT_ROUTE_VEHICLE) === true)
                                                    {
                                                        echo '<a href="edit_route_vehicle.php?Process=2&amp;RouteVehicleID=' . $RouteVehicleID . '">Edit</a>';
                                                    }
                                                    else
                                                    {
                                                        echo 'Edit';
                                                    }

                                                    echo '&nbsp;|&nbsp;';

                                                    if ($LoggedUser->HasPermissionForTask(TASK_DELETE_ROUTE_VEHICLE) === true)
                                                    {
                                                        echo '<a href="route_vehicle_report.php?Process=5&amp;RouteVehicleID=' . $RouteVehicleID . '" class="delete-record">Delete</a>';
                                                    }
                                                    else
                                                    {
                                                        echo 'Delete';
                                                    }
?>
                                                    </td>
                                                </tr>
<?php
                                        }
                                    }
                                    else
                                    {
?>
                                                <tr>
                                                    <td colspan="8">No Records</td>
                                                </tr>
<?php
                                    }
?>
                                            </tbody>
                                        </table>
                                        </div>
                                    </div>
                                </div>
                            <!-- /.table-responsive -->
                        </div>
                        <!-- /.panel-body -->
                    </div>
                    <!-- /.panel -->
                </div>
                <!-- /.col-lg-12 -->
            </div>
<?php
        }
?>            
        </div>
        <!-- /#page-wrapper -->

    </div>
    <!-- /#wrapper -->
<?php
require_once('../footer.php');
?>
<!-- DataTables JavaScript -->
<script src="../vendor/datatables/js/jquery.dataTables.min.js"></script>
<script src="../vendor/datatables-plugins/dataTables.bootstrap.min.js"></script>
<script src="../vendor/datatables-responsive/dataTables.responsive.js"></script>   
<script type="text/javascript">
$(document).ready(function() {
    $("body").on('click', '.delete-record', function()
    {   
        if (!confirm("Are you sure you want to delete this record?"))
        {
            return false;
        }
    }); 

    $('#DataTableRecords').DataTable({
        responsive: true,
        bPaginate: false,
        bSort: false,
        searching: false, 
        info: false
    });
    
});
</script>
<!-- JavaScript To Print A Report -->
<script src="js/print-report.js"></script>
<script src="/admin/js/print-report.js"></script>
</body>
</html>
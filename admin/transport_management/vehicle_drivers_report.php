<?php
require_once("../../classes/class.users.php");
require_once('../../classes/class.validation.php');
require_once("../../classes/class.authentication.php");

require_once("../../classes/class.ui_helpers.php");

require_once("../../classes/transport_management/class.vehicle.php");
require_once("../../classes/transport_management/class.vehicle_type.php");
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

if ($LoggedUser->HasPermissionForTask(TASK_REPORT_VEHICLE_DRIVER) !== true)
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

if (isset($_GET['btnClearFilter']))
{
    header('location:vehicle_drivers_report.php?Process=7');
    exit;
}

$HasErrors = false;
$RecordDeletedSuccessfully = false;

$Filters = array();

$VehicleTypeList =  array();
$VehicleTypeList = VehicleType::GetActiveVehicleType();

$AllVehicleDrivers = array();

$Clean = array();
$Clean['Process'] = 0;

$Clean['VehicleDriverID'] = 0;
$Clean['VehicleTypeID'] = 0;

$Clean['DriverName'] = '';
$Clean['ActiveStatus'] = 0;
// paging and sorting variables start here  //

$Clean['CurrentPage'] = 1;
$TotalPages = 0;
$TotalRecords = 0;

$Start = 0;
$Limit = 10;
// end of paging variables//

if (isset($_GET['Process']))
{
    $Clean['Process'] = (int) $_GET['Process'];
}
else if (isset($_GET['hdnProcess'])) 
{
    $Clean['Process'] = (int) $_GET['hdnProcess'];
}
switch ($Clean['Process'])
{
    case 5:
        if ($LoggedUser->HasPermissionForTask(TASK_DELETE_VEHICLE_DRIVER) !== true)
        {
            header('location:unauthorized_login_admin.php');
            exit;
        }
        
        if (isset($_GET['VehicleDriverID']))
        {
            $Clean['VehicleDriverID'] = (int) $_GET['VehicleDriverID'];
        }
        
        if ($Clean['VehicleDriverID'] <= 0)
        {
            header('location:../error_page.php');
            exit;
        }                       
            
        try
        {
            $VehicleDriverToDelete = new VehicleDrivers($Clean['VehicleDriverID']);
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
        
        $RecordValidator = new Validator();
        
        // if ($VehicleDriverToDelete->CheckDependencies())
        // {
        //  $RecordValidator->AttachTextError('This driver cannot be deleted. There are dependent records for this driver.');
        //  $HasErrors = true;
        //  break;
        // }
                
        if (!$VehicleDriverToDelete->Remove())
        {
            $RecordValidator->AttachTextError(ProcessErrors($VehicleDriverToDelete->GetLastErrorCode()));
            $HasErrors = true;
            break;
        }
        
        $RecordDeletedSuccessfully = true;
    break;

    case 7:
        if (isset($_GET['drdVehicleType']))
        {
            $Clean['VehicleTypeID'] = (int) $_GET['drdVehicleType'];
        }
        elseif (isset($_GET['VehicleTypeID']))
        {
            $Clean['VehicleTypeID'] = (int) $_GET['VehicleTypeID'];
        }
        if (isset($_GET['txtDriverName']))
        {
            $Clean['DriverName'] = strip_tags(trim($_GET['txtDriverName']));
        }
        else if (isset($_GET['DriverName']))
        {
            $Clean['DriverName'] = strip_tags(trim($_GET['DriverName']));
        }
        if (isset($_GET['optActiveStatus']))
        {
            $Clean['ActiveStatus'] = (int) $_GET['optActiveStatus'];
        }
        elseif (isset($_GET['ActiveStatus']))
        {
            $Clean['ActiveStatus'] = (int) $_GET['ActiveStatus'];
        }
        
        $RecordValidator = new Validator();

        if ($Clean['VehicleTypeID'] != 0)
        {
           $RecordValidator->ValidateInSelect($Clean['VehicleTypeID'], $VehicleTypeList, 'Unknown error, please try again.');
        }

        if ($Clean['DriverName'] != '')
        {
            $RecordValidator->ValidateStrings($Clean['DriverName'], 'Driver name should be between 1 and 30 characters.', 1, 30);
        }

        if ($Clean['ActiveStatus'] < 0 || $Clean['ActiveStatus'] > 2)
        {
            $SearchValidator->AttachTextError('Unknown Error, Please try again.');
        }

        if ($RecordValidator->HasNotifications())
        {
            $HasErrors = true;
            break;
        }
        
        $Filters['VehicleTypeID'] = $Clean['VehicleTypeID'];
        $Filters['DriverName'] = $Clean['DriverName'];
        $Filters['ActiveStatus'] = $Clean['ActiveStatus'];
        //get records count

        VehicleDrivers::SearchVehicleDrivers($TotalRecords, true, $Filters);
        if ($TotalRecords > 0)
        {
            // Paging and sorting calculations start here.
            $TotalPages = (($TotalRecords % $Limit) == 0) ? $TotalRecords / $Limit : floor($TotalRecords / $Limit) + 1;

            if (isset($_GET['CurrentPage']))
            {
                $Clean['CurrentPage'] = (int) $_GET['CurrentPage'];
            }

            if ($Clean['CurrentPage'] <= 0)
            {
                $Clean['CurrentPage'] = 1;
            }
            else if ($Clean['CurrentPage'] > $TotalPages)
            {
                $Clean['CurrentPage'] = $TotalPages;
            }

            if ($Clean['CurrentPage'] > 1)
            {
                $Start = ($Clean['CurrentPage'] - 1) * $Limit;
            }
            // end of Paging and sorting calculations.
            // now get the actual  records
            $AllVehicleDrivers = VehicleDrivers::SearchVehicleDrivers($TotalRecords, false, $Filters, $Start, $Limit);
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
<title>Assign Vehicle Driver Report</title>
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
                    <h1 class="page-header">Assign  Vehicle Driver Report</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
            <!-- /.row -->
            <form class="form-horizontal" name="AddDriver" action="vehicle_drivers_report.php" method="get">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <strong>Apply Filters</strong>
                    </div>
                    <div class="panel-body">
<?php
                    if ($HasErrors == true)
                    {
                        echo $RecordValidator->DisplayErrorsInTable();
                    }
                    else if ($RecordDeletedSuccessfully == true)
                    {
                        echo '<div class="alert alert-danger alert-top-margin">Record deleted successfully.</div>';
                    }
                    else if ($LandingPageMode == 'AS')
                    {
                        echo '<div class="alert alert-success alert-top-margin">Record saved successfully.</div>';
                    }
                    else if ($LandingPageMode == 'UD')
                    {
                        echo '<div class="alert alert-success alert-top-margin">Record updated successfully.</div><br>';
                    }
?>                
                        <div class="form-group">
                                <label for="VehicleType" class="col-lg-2 control-label">Vehicles</label>
                                <div class="col-lg-4">
                                    <select class="form-control"  name="drdVehicleType" id="VehicleType">
                                        <option  value="0" >-- All Vehicles --</option>
<?php
                                if (is_array($VehicleTypeList) && count($VehicleTypeList) > 0)
                                {
                                    foreach($VehicleTypeList as $VehicleTypeID => $VehicleType)
                                    {
                                        echo '<option ' . (($Clean['VehicleTypeID'] == $VehicleTypeID) ? 'selected="selected"' : '' ) . ' value="' . $VehicleTypeID . '">' . $VehicleType . '</option>';
                                    }
                                }
?>
                                    </select>
                                </div>
                        </div>
                        <div class="form-group">
                           <label for="DriverName" class="col-lg-2 control-label">Drivers</label>
                             <div class="col-lg-4">
                                <input class="form-control" type="text" maxlength="15" id="DriverName" name="txtDriverName" value="<?php echo $Clean['DriverName']; ?>" />
                            </div>
                        </div>
                        <div class="form-group">
                                <label for="ActiveStatus" class="col-lg-2 control-label">Status</label>
                                <div class="col-lg-6">
                                    <label class="radio-inline">
                                        <input type="radio" <?php echo (($Clean['ActiveStatus'] == 0) ? 'checked="checked"' : ''); ?> name="optActiveStatus" id="ActiveStatus" value="0" checked>All Vehicle Driver
                                    </label>
                                    <label class="radio-inline">
                                        <input type="radio" <?php echo (($Clean['ActiveStatus'] == 1) ? 'checked="checked"' : ''); ?> name="optActiveStatus" id="ActiveStatus" value="1">Active Vehicle Driver
                                    </label>
                                    <label class="radio-inline">
                                        <input type="radio" <?php echo (($Clean['ActiveStatus'] == 2) ? 'checked="checked"' : ''); ?> name="optActiveStatus" id="ActiveStatus" value="2">In-Active Vehicle Driver
                                    </label>
                                </div>
                        </div>     
                        <div class="form-group">
                            <div class="col-sm-offset-2 col-lg-7">
                                <input type="hidden" name="hdnProcess" value="7"/>
                                <button type="submit" class="btn btn-primary">Search</button>
                                <button type="submit" class="btn btn-primary" name="btnClearFilter">Clear Filter</button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
<?php
        if ($Clean['Process'] == 7 && $HasErrors == false)
        {
            $ReportHeaderText = '';

            if ($Clean['VehicleTypeID'] != '')
            {
                $ReportHeaderText .= ' Vehicle Type: ' . $Clean['VehicleTypeID'] . ',';
            }

            if ($Clean['DriverName'] != '')
            {
                $ReportHeaderText .= ' Driver Name: ' . $Clean['DriverName'] . ',';
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
                                    <div class="col-lg-3">
                                    <div class="add-new-btn-container"><a href="assign_vehicle_drivers.php" class="btn btn-primary<?php //echo $LoggedUser->HasPermissionForTask(TASK_ADD_MENU) === true ? '' : ' disabled'; ?>" role="button">Assign Vehicle Driver</a></div>

                                    </div>
                                    <div class="col-lg-6">
<?php
                                    if ($TotalPages > 1)
                                    {

                                        $AllParameters = array('Process' => '7', 'VehicleID' => $Clean['VehicleID'], 'DriverName' => $Clean['DriverName']);

                                        echo UIHelpers::GetPager('vehicle_drivers_report.php', $TotalPages, $Clean['CurrentPage'], $AllParameters);
                                    }
?>  
                                    </div>
                                    <div class="col-lg-3">
                                        <div class="print-btn-container"><button id="PrintButton" type="submit" class="btn btn-primary">Print</button></div>
                                    </div>
                                </div>

                                <div class="row" id="RecordTableHeading">
                                    <div class="col-lg-12">
                                        <div class="report-heading-container"><strong> Vehicle Driver Report on <?php echo date('d-m-Y h:i A') . $ReportHeaderText; ?></strong></div>
                                    </div>
                                </div>
                                <div class="row" id="RecordTable">
                                    <div class="col-lg-12">
                                        <table width="100%" class="table table-striped table-bordered table-hover" id="DataTableRecords">
                                            <thead>
                                                <tr>
                                                    <th>S. No</th>
                                                    <th>Driver Name</th>
                                                    <th>Father Name</th>
                                                    <th>Vehicle Type</th>
                                                    <th>Vehicle Name</th>
                                                    <th>Vehicle Number</th>
                                                    <th>Contact Number</th>
                                                    <th>Create User</th>
                                                    <th>IsActive</th>
                                                    <th>Create Date</th>
                                                    <th class="print-hidden">Operations</th>
                                                </tr>
                                            </thead>
                                            <tbody>
<?php
                                    if (is_array($AllVehicleDrivers) && count($AllVehicleDrivers) > 0)
                                    {
                                        $Counter = $Start;
                                        foreach ($AllVehicleDrivers as $VehicleDriverID => $VehicleDriverDetails)
                                        {
?>
                                                <tr>
                                                    <td><?php echo ++$Counter; ?></td>
                                                    <td><?php echo $VehicleDriverDetails['DriverFirstName'] . ' ' . $VehicleDriverDetails['DriverLastName']; ?></td>
                                                    <td><?php echo $VehicleDriverDetails['FatherName']; ?></td>
                                                    <td><?php echo $VehicleDriverDetails['VehicleType']; ?></td>
                                                    <td><?php echo $VehicleDriverDetails['VehicleName']; ?></td>
                                                    <td><?php echo $VehicleDriverDetails['VehicleNumber']; ?></td>
                                                    <td><?php echo $VehicleDriverDetails['ContactNumber']; ?></td>
                                                    <td><?php echo $VehicleDriverDetails['CreateUserName']; ?></td>
                                                    <td><?php echo (($VehicleDriverDetails['IsActive']) ? 'Yes' : 'No'); ?></td>
                                                    <td><?php echo date('d/m/Y', strtotime($VehicleDriverDetails['CreateDate'])); ?></td>
                                                    <td class="print-hidden">
<?php
                                                    // echo '<a href="edit_assign_vehicle_drivers.php?Process=2&amp;VehicleDriverID=' . $VehicleDriverID . '">Edit</a>';
                                                    // echo '&nbsp;|&nbsp;';
                                                    // echo '<a class="delete-record" href="vehicle_drivers_report.php?Process=5&amp;VehicleDriverID=' . $VehicleDriverID . '">Delete</a>';
                                                    
                                                    if ($LoggedUser->HasPermissionForTask(TASK_EDIT_VEHICLE_DRIVER) === true)
                                                    {
                                                        echo '<a href="edit_assign_vehicle_drivers.php?Process=2&amp;VehicleDriverID=' . $VehicleDriverID . '">Edit</a>';
                                                    }
                                                    else
                                                    {
                                                        echo 'Edit';
                                                    }

                                                    echo '&nbsp;|&nbsp;';

                                                    if ($LoggedUser->HasPermissionForTask(TASK_DELETE_VEHICLE_DRIVER) === true)
                                                    {
                                                        echo '<a class="delete-record" href="vehicle_drivers_report.php?Process=5&amp;VehicleDriverID=' . $VehicleDriverID . '">Delete</a>';
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

    $('#DataTableRecords').DataTable({
        responsive: true,
        bPaginate: false,
        bSort: false,
        searching: false, 
        info: false
    });

    $(".delete-record").click(function()
    {   
        if (!confirm("Are you sure to delete this Driver ?"))
        {
            return false;
        }
    });
});

</script>
    <!-- JavaScript To Print A Report -->
    <script src="/admin/js/print-report.js"></script>
</body>
</html> 

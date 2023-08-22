<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once("../../classes/class.ui_helpers.php");

require_once("../../classes/transport_management/class.vehicle_type.php");
require_once("../../classes/transport_management/class.vehicle.php");

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

if ($LoggedUser->HasPermissionForTask(TASK_VEHICLE_REPORT) !== true)
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

$VehicleTypeList =  array();
$VehicleTypeList = VehicleType::GetActiveVehicleType();

$VehiclesList = array();
$RecordDeletedSuccessfully = false;
$HasErrors = false;
$TotalRecords = 0;

$Clean = array();
$Clean['Process'] = 0;

$Clean['VehicleTypeID'] = 0;
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
        if ($LoggedUser->HasPermissionForTask(TASK_DELETE_VEHICLES) !== true)
        {
            header('location:/admin/unauthorized_login_admin.php');
            exit;
        }
        
        if (isset($_GET['VehicleID']))
        {
            $Clean['VehicleID'] = (int) $_GET['VehicleID'];           
        }
        
        if ($Clean['VehicleID'] <= 0)
        {
            header('location:../error_page.php');
            exit;
        }                       
            
        try
        {
            $VehicleToDelete = new Vehicle($Clean['VehicleID']);
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
        
        // if ($VehicleToDelete->CheckDependencies())
        // {
        //     $SearchValidator->AttachTextError('This Vehicle cannot be deleted. There are dependent records for this Vehicle.');
        //     $HasErrors = true;
        //     break;
        // }
                
        if (!$VehicleToDelete->Remove())
        {
            $SearchValidator->AttachTextError(ProcessErrors($VehicleToDelete->GetLastErrorCode()));
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
        if (isset($_GET['optActiveStatus']))
        {
            $Clean['ActiveStatus'] = (int) $_GET['optActiveStatus'];
        }
        elseif (isset($_GET['ActiveStatus']))
        {
            $Clean['ActiveStatus'] = (int) $_GET['ActiveStatus'];
        }

        $SearchValidator = new Validator();

        if ($Clean['VehicleTypeID'] != 0)
        {
            $SearchValidator->ValidateInSelect($Clean['VehicleTypeID'], $VehicleTypeList, 'Unknown Error, Please try again.');
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
        $Filters['VehicleTypeID'] = $Clean['VehicleTypeID'];
        $Filters['ActiveStatus'] = $Clean['ActiveStatus'];
        //get records count
        Vehicle::SearchVehicles($TotalRecords, true, $Filters);

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
                $VehiclesList = Vehicle::SearchVehicles($TotalRecords, false, $Filters, 0, $TotalRecords);
            } 
            else
            {
              $VehiclesList = Vehicle::SearchVehicles($TotalRecords, false, $Filters, $Start, $Limit);  
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
<title>Vehicle Report</title>
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
                    <h1 class="page-header">Vehicle Report</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
            <form class="form-horizontal" name="frmVehicleReport" action="vehicle_report.php" method="get">
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
                                <label for="VehicleType" class="col-lg-2 control-label">VehicleType</label>
                                <div class="col-lg-4">
                                    <select class="form-control"  name="drdVehicleType" id="VehicleType">
                                        <option  value="0" >-- All VehicleType --</option>
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
                                <label for="ActiveStatus" class="col-lg-2 control-label">Status</label>
                                <div class="col-lg-6">
                                    <label class="radio-inline">
                                        <input type="radio" <?php echo (($Clean['ActiveStatus'] == 0) ? 'checked="checked"' : ''); ?> name="optActiveStatus" id="ActiveStatus" value="0" checked>All Vehicle
                                    </label>
                                    <label class="radio-inline">
                                        <input type="radio" <?php echo (($Clean['ActiveStatus'] == 1) ? 'checked="checked"' : ''); ?> name="optActiveStatus" id="ActiveStatus" value="1">Active Vehicle
                                    </label>
                                    <label class="radio-inline">
                                        <input type="radio" <?php echo (($Clean['ActiveStatus'] == 2) ? 'checked="checked"' : ''); ?> name="optActiveStatus" id="ActiveStatus" value="2">In-Active Vehicle
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

            if ($Clean['VehicleTypeID'] != 0)
            {
                $ReportHeaderText .= ' VehicleTypeID: ' . $VehicleTypeList[$Clean['VehicleTypeID']] . ',';
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
                                        $AllParameters = array('Process' => '7', 'VehicleTypeID' => $Clean['VehicleTypeID'], 'ActiveStatus' => $Clean['ActiveStatus']);
                                        echo UIHelpers::GetPager('vehicle_report.php', $TotalPages, $Clean['CurrentPage'], $AllParameters);
                                    }
?>                                        
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="print-btn-container"><button id="PrintButton" type="submit" class="btn btn-primary">Print</button></div>
                                    </div>
                                </div>
                                <div class="row" id="RecordTableHeading">
                                    <div class="col-lg-12">
                                        <div class="report-heading-container"><strong>Vehicle Report on <?php echo date('d-m-Y h:i A') . $ReportHeaderText; ?></strong></div>
                                    </div>
                                </div>
                                <div class="row" id="RecordTable">
                                    <div class="col-lg-12">
                                        <table width="100%" class="table table-striped table-bordered table-hover " id="DataTableRecords">
                                            <thead>
                                                <tr>
                                                    <th>S.No</th>   
                                                    <th>Vehicle Type</th>                                                
                                                    <th>Vehicle Name</th>
                                                    <th>Vehicle Number</th>
                                                    <th>Registration From</th>
                                                    <th>Registration To</th>
                                                    <th>Insurance From</th>
                                                    <th>Insurance To</th>
                                                    <th>Pollution From</th>
                                                    <th>Pollution To</th>
                                                    <th>Approval From</th>
                                                    <th>Approval To</th>
                                                    <th>Fitness DocumentNumber</th>
                                                    <th>Permit Number</th>
                                                    <th>Available Saets</th>
                                                    <th>IsDiesel</th>
                                                    <th>IsPetrol</th>
                                                    <th>IsGas</th>
                                                    <th>Last ServicedDate</th>
                                                    <th>Service DueDate</th>
                                                    <th>Is Active</th>
                                                    <th>Create User</th>
                                                    <th>Create Date</th>
                                                    <th class="print-hidden">Operations</th>
                                                </tr>
                                            </thead>
                                            <tbody>
<?php
                                    if (is_array($VehiclesList) && count($VehiclesList) > 0)
                                    {
                                        $Counter = $Start;
                                        foreach ($VehiclesList as $VehicleID => $VehicleDetails)
                                        {
?>
                                                <tr>
                                                    <td><?php echo ++$Counter; ?></td>                                                    
                                                    <td><?php echo $VehicleDetails['VehicleType']; ?></td>
                                                    <td><?php echo $VehicleDetails['VehicleName']; ?></td>
                                                    <td><?php echo $VehicleDetails['VehicleNumber']; ?></td>

                                                    <td><?php echo $VehicleDetails['RegistrationFrom']; ?></td>
                                                    <td><?php echo $VehicleDetails['RegistrationTo']; ?></td>
                                                    <td><?php echo $VehicleDetails['InsuranceFrom']; ?></td>
                                                    <td><?php echo $VehicleDetails['InsuranceTo']; ?></td>
                                                    <td><?php echo $VehicleDetails['PollutionFrom']; ?></td>
                                                    <td><?php echo $VehicleDetails['PollutionTo']; ?></td>
                                                    <td><?php echo $VehicleDetails['ApprovalFrom']; ?></td>
                                                    <td><?php echo $VehicleDetails['ApprovalTo']; ?></td>

                                                    <td><?php echo $VehicleDetails['FitnessDocumentNumber']; ?></td>
                                                    <td><?php echo $VehicleDetails['PermitNumber']; ?></td>
                                                    <td><?php echo $VehicleDetails['AvailableSaets']; ?></td>

                                                    <td><?php echo $VehicleDetails['IsDiesel']; ?></td>
                                                    <td><?php echo $VehicleDetails['IsPetrol']; ?></td>
                                                    <td><?php echo $VehicleDetails['IsGas']; ?></td>

                                                    <td><?php echo $VehicleDetails['LastServicedDate']; ?></td>
                                                    <td><?php echo $VehicleDetails['ServiceDueDate']; ?></td>

                                                    <td><?php echo (($VehicleDetails['IsActive']) ? 'Yes' : 'No'); ?></td>
                                                    <td><?php echo $VehicleDetails['CreateUserName']; ?></td>
                                                    <td><?php echo date('d/m/Y', strtotime($VehicleDetails['CreateDate'])); ?></td>
                                                    <td class="print-hidden">
<?php
                                                    // echo '<a href="edit_vehicle.php?Process=2&amp;VehicleID=' . $VehicleID . '">Edit</a>';
                                                    // echo '&nbsp;|&nbsp;';
                                                    // echo '<a href="vehicle_report.php?Process=5&amp;VehicleID=' . $VehicleID . '" class="delete-record">Delete</a>';
                                                     
                                                    if ($LoggedUser->HasPermissionForTask(TASK_EDIT_VEHICLE) === true)
                                                    {
                                                        echo '<a href="edit_vehicle.php?Process=2&amp;VehicleID=' . $VehicleID . '">Edit</a>';
                                                    }
                                                    else
                                                    {
                                                        echo 'Edit';
                                                    }

                                                    echo '&nbsp;|&nbsp;';

                                                    if ($LoggedUser->HasPermissionForTask(TASK_DELETE_VEHICLES) === true)
                                                    {
                                                        echo '<a href="vehicle_report.php?Process=5&amp;VehicleID=' . $VehicleID . '" class="delete-record">Delete</a>'; 
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
<!-- JavaScript To Print A Report -->
<script src="/admin/js/print-report.js"></script>

<!-- DataTables JavaScript -->
<script src="../vendor/datatables/js/jquery.dataTables.min.js"></script>
<script src="../vendor/datatables-plugins/dataTables.bootstrap.min.js"></script>
<script src="../vendor/datatables-responsive/dataTables.responsive.js"></script>   
<script type="text/javascript">
    
$(document).ready(function() {
    $("body").on('click', '.delete-record', function()
    {   
        if (!confirm("Are you sure you want to delete this Vehicle?"))
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
</body>
</html>
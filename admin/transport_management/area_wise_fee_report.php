<?php
require_once("../../classes/class.users.php");
require_once('../../classes/class.validation.php');
require_once("../../classes/class.authentication.php");

require_once("../../classes/class.ui_helpers.php");
require_once("../../classes/school_administration/class.academic_years.php");
require_once("../../classes/transport_management/class.routes.php");
require_once("../../classes/transport_management/class.area_master.php");

require_once("../../classes/transport_management/class.areawise_fee.php");
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

if ($LoggedUser->HasPermissionForTask(TASK_REPORT_AREAWISE_FEE) !== true)
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
    header('location:area_wise_fee_report.php?Process=7');
    exit;
}

$HasErrors = false;
$RecordDeletedSuccessfully = false;

$Filters = array();

$RouteList =  array();
$RouteList = Route::GetActiveRoutes();

$AreaList = array();
$AreaList = AreaMaster::GetActiveArea();

$AcademicYears = array();
$AcademicYears = AcademicYear::GetAllAcademicYears();

$AllAreaWiseFee = array();

$Clean = array();
$Clean['Process'] = 0;

$Clean['AreaWiseFeeID'] = 0;
$Clean['RouteID'] = 0;

$Clean['AreaID'] = 0;
$Clean['AcademicYearID'] = 0;
$Clean['ActiveStatus'] = 0;
// paging and sorting variables start here  //p

$Clean['AllRecords'] = '';
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
        if ($LoggedUser->HasPermissionForTask(TASK_DELETE_AREAWISE_FEE) !== true)
        {
            header('location:unauthorized_login_admin.php');
            exit;
        }
        
        if (isset($_GET['AreaWiseFeeID']))
        {
            $Clean['AreaWiseFeeID'] = (int) $_GET['AreaWiseFeeID'];
        }
        
        if ($Clean['AreaWiseFeeID'] <= 0)
        {
            header('location:../error_page.php');
            exit;
        }                       
            
        try
        {
            $AreaWiseFeeToDelete = new AreaWiseFee($Clean['AreaWiseFeeID']);
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
        
        // if ($AreaWiseFeeToDelete->CheckDependencies())
        // {
        //  $RecordValidator->AttachTextError('This driver cannot be deleted. There are dependent records for this driver.');
        //  $HasErrors = true;
        //  break;
        // }
                
        if (!$AreaWiseFeeToDelete->Remove())
        {
            $RecordValidator->AttachTextError(ProcessErrors($AreaWiseFeeToDelete->GetLastErrorCode()));
            $HasErrors = true;
            break;
        }
        
        $RecordDeletedSuccessfully = true;
    break;

    case 7:
        if (isset($_GET['drdRouteID']))
        {
            $Clean['RouteID'] = (int) $_GET['drdRouteID'];
        }
        elseif (isset($_GET['RouteID']))
        {
            $Clean['RouteID'] = (int) $_GET['RouteID'];
        }
        if (isset($_GET['drdAreaID']))
        {
            $Clean['AreaID'] = (int) $_GET['drdAreaID'];
        }
        elseif (isset($_GET['AreaID']))
        {
            $Clean['AreaID'] = (int) $_GET['AreaID'];
        }
        if (isset($_GET['drdAcademicYearID']))
        {
            $Clean['AcademicYearID'] = (int) $_GET['drdAcademicYearID'];
        }
        elseif (isset($_GET['AcademicYearID']))
        {
            $Clean['AcademicYearID'] = (int) $_GET['AcademicYearID'];
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

        if ($Clean['RouteID'] != 0)
        {
           $RecordValidator->ValidateInSelect($Clean['RouteID'], $RouteList, 'Unknown Error, Please try again.');
        }

        if ($Clean['AreaID'] != 0)
        {
           $RecordValidator->ValidateInSelect($Clean['AreaID'], $AreaList, 'Unknown Error, Please try again.');
        }

        if ($Clean['AcademicYearID'] != 0)
        {
            $RecordValidator->ValidateInSelect($Clean['AcademicYearID'], $AcademicYears, 'Unknown error, please try again.');
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
        
        $Filters['RouteID'] = $Clean['RouteID'];
        $Filters['AreaID'] = $Clean['AreaID'];
        $Filters['AcademicYearID'] = $Clean['AcademicYearID'];
        $Filters['ActiveStatus'] = $Clean['ActiveStatus'];
        //get records count

        AreaWiseFee::SearchAreaWiseFee($TotalRecords, true, $Filters);
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
            if ($Clean['AllRecords'] == 'All') 
            {
                 $AllAreaWiseFee = AreaWiseFee::SearchAreaWiseFee($TotalRecords, false, $Filters, 0, $TotalRecords);
            }
            else
            {
                $AllAreaWiseFee = AreaWiseFee::SearchAreaWiseFee($TotalRecords, false, $Filters, $Start, $Limit);
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
<title>Area Wise Fee Report</title>
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
                    <h1 class="page-header">Area Wise Fee Report</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
            <!-- /.row -->
            <form class="form-horizontal" name="AddDriver" action="area_wise_fee_report.php" method="get">
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
                                <label for="RouteID" class="col-lg-2 control-label">Route No.</label>
                                <div class="col-lg-3">
                                    <select class="form-control"  name="drdRouteID" id="RouteID">32
                                        <option  value="0" >-- All Routes --</option>
<?php
                                if (is_array($RouteList) && count($RouteList) > 0)
                                {
                                    foreach($RouteList as $RouteID => $RouteNumber)
                                    {
                                        echo '<option ' . (($Clean['RouteID'] == $RouteID) ? 'selected="selected"' : '' ) . ' value="' . $RouteID . '">' . $RouteNumber .'</option>';
                                    }
                                }
?>
                                    </select>
                                </div>

							<label for="AreaID" class="col-lg-2 control-label">Area</label>
                                <div class="col-lg-3">
                                    <select class="form-control"  name="drdAreaID" id="AreaID">
                                        <option  value="0" >-- All Areas --</option>
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

                        <div class="form-group">
                            <label for="AcademicYearID" class="col-lg-2 control-label">Academic Session</label>
                            <div class="col-lg-3">
                                <select class="form-control" name="drdAcademicYearID" id="AcademicYearID">
                                    <?php
                                    if (is_array($AcademicYears) && count($AcademicYears) > 0)
                                    {
                                        foreach ($AcademicYears as $AcademicYearID => $AcademicYearDetails)
                                        {
                                            echo '<option ' . ($Clean['AcademicYearID'] == $AcademicYearID ? 'selected="selected"' : '') . ' value="' . $AcademicYearID . '">' . date('Y', strtotime($AcademicYearDetails['StartDate'])) . ' - ' . date('Y', strtotime($AcademicYearDetails['EndDate'])) . '</option>';
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                                <label for="ActiveStatus" class="col-lg-2 control-label">Status</label>
							<div class="col-lg-4">
                                    <label class="radio-inline">
                                        <input type="radio" <?php echo (($Clean['ActiveStatus'] == 0) ? 'checked="checked"' : ''); ?> name="optActiveStatus" id="ActiveStatus" value="0" checked>All Area 
                                    </label>
                                    <label class="radio-inline">
                                        <input type="radio" <?php echo (($Clean['ActiveStatus'] == 1) ? 'checked="checked"' : ''); ?> name="optActiveStatus" id="ActiveStatus" value="1">Active Area
                                    </label>
                                    <label class="radio-inline">
                                        <input type="radio" <?php echo (($Clean['ActiveStatus'] == 2) ? 'checked="checked"' : ''); ?> name="optActiveStatus" id="ActiveStatus" value="2">In-Active Area
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

            if ($Clean['RouteID'] != '')
            {
                $ReportHeaderText .= ' Route Number : ' . $Clean['RouteID'] . ',';
            }

            if ($Clean['AreaID'] != '')
            {
                $ReportHeaderText .= ' Area Name: ' . $Clean['AreaID'] . ',';
            }

                if ($Clean['AcademicYearID'] != '')
                {
                    $ReportHeaderText .= ' Academic Year: ' . $Clean['AcademicYearID'] . ',';
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
                                    <div class="add-new-btn-container"><a href="add_area_wise_fee.php" class="btn btn-primary<?php //echo $LoggedUser->HasPermissionForTask(TASK_ADD_MENU) === true ? '' : ' disabled'; ?>" role="button">Add Area Wise Fee</a></div>

                                    </div>
                                    <div class="col-lg-6">
<?php
                                    if ($TotalPages > 1)
                                    {
                                        //$Filters['AcademicYearID'] = ;

                                        $AllParameters = array('Process' => '7', 'RouteID' => $Clean['RouteID'], 'AreaId' => $Clean['AreaID'], 'AcademicYearID' => $Clean['AcademicYearID']);

                                        echo UIHelpers::GetPager('area_wise_fee_report.php', $TotalPages, $Clean['CurrentPage'], $AllParameters);
                                    }
?>  
                                    </div>
                                    <div class="col-lg-3">
                                        <div class="print-btn-container"><button id="PrintButton" type="submit" class="btn btn-primary">Print</button></div>
                                    </div>
                                </div>

                                <div class="row" id="RecordTableHeading">
                                    <div class="col-lg-12">
                                        <div class="report-heading-container"><strong>Area Wise Fee Report on <?php echo date('d-m-Y h:i A') . $ReportHeaderText; ?></strong></div>
                                    </div>
                                </div>
                                <div class="row" id="RecordTable">
                                    <div class="col-lg-12">
                                        <table width="100%" class="table table-striped table-bordered table-hover" id="DataTableRecords">
                                            <thead>
                                                <tr>
                                                    <th>S. No</th>
                                                    <th>Route Number</th>
                                                    <th>Route Name</th>
                                                    <th>Area Name</th>
                                                    <th>Amount</th>
                                                    <th>Create User</th>
                                                    <th>IsActive</th>
                                                    <th>Create Date</th>
                                                    <th class="print-hidden">Operations</th>
                                                </tr>
                                            </thead>
                                            <tbody>
<?php
                                    if (is_array($AllAreaWiseFee) && count($AllAreaWiseFee) > 0)
                                    {
                                        $Counter = $Start;
                                        foreach ($AllAreaWiseFee as $AreaWiseFeeID => $AreaWiseFeeDetails)
                                        {
?>
                                                <tr>
                                                    <td><?php echo ++$Counter; ?></td>
                                                    <td><?php echo $AreaWiseFeeDetails['RouteNumber']; ?></td>
                                                    <td><?php echo $AreaWiseFeeDetails['RouteName']; ?></td>
                                                    <td><?php echo $AreaWiseFeeDetails['AreaName']; ?></td>
                                                    <td><?php echo $AreaWiseFeeDetails['Amount']; ?></td>
                                                    <td><?php echo $AreaWiseFeeDetails['CreateUserName']; ?></td>
                                                    <td><?php echo (($AreaWiseFeeDetails['IsActive']) ? 'Yes' : 'No'); ?></td>
                                                    <td><?php echo date('d/m/Y', strtotime($AreaWiseFeeDetails['CreateDate'])); ?></td>
                                                    <td class="print-hidden">
<?php
                                                    // echo '<a href="edit_area_wise_fee.php?Process=2&amp;AreaWiseFeeID=' . $AreaWiseFeeID . '">Edit</a>';
                                                    // echo '&nbsp;|&nbsp;';
                                                    // echo '<a class="delete-record" href="area_wise_fee_report.php?Process=5&amp;AreaWiseFeeID=' . $AreaWiseFeeID . '">Delete</a>';
                                                    
                                                    if ($LoggedUser->HasPermissionForTask(TASK_EDIT_AREAWISE_FEE) === true)
                                                    {
                                                        echo '<a href="edit_area_wise_fee.php?Process=2&amp;AreaWiseFeeID=' . $AreaWiseFeeID . '">Edit</a>';
                                                    }
                                                    else
                                                    {
                                                        echo 'Edit';
                                                    }

                                                    echo '&nbsp;|&nbsp;';

                                                    if ($LoggedUser->HasPermissionForTask(TASK_DELETE_AREAWISE_FEE) === true)
                                                    {
                                                        echo '<a class="delete-record" href="area_wise_fee_report.php?Process=5&amp;AreaWiseFeeID=' . $AreaWiseFeeID . '">Delete</a>';
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
		$ (document).ready (function ()
		{

    $('#DataTableRecords').DataTable({
        responsive: true,
        bPaginate: false,
        bSort: false,
        searching: false, 
        info: false
    });

    $(".delete-record").click(function()
    {   
        if (!confirm("Are you sure to delete this Record?"))
        {
            return false;
        }
    });
});

</script>
    <!-- JavaScript To Print A Report -->
    <script src="js/print-report.js"></script>
    <script src="/admin/js/print-report.js"></script>
</body>
</html> 

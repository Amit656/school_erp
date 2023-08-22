<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once("../../classes/class.ui_helpers.php");

require_once("../../classes/hostel_management/class.wings.php");
require_once("../../classes/hostel_management/class.room_types.php");
require_once("../../classes/hostel_management/class.rooms.php");

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

$UserMenusArray = array();
$UserMenusArray = $LoggedUser->GetUserMenus();

if (!is_array($UserMenusArray) || count($UserMenusArray) <= 0)
{
    //header('location:../logout.php');
    //exit;
}

$Filters = array();

$Winglist = array();
$Winglist = Wing::GetActiveWings();

$RoomTypelist = array();
$RoomTypelist = RoomType::GetActiveRoomTypes();

$RoomList = array();

$RecordDeletedSuccessfully = false;
$HasErrors = false;
$TotalRecords = 0;

$Clean = array();
$Clean['Process'] = 0;

$Clean['WingID'] = 0;
$Clean['RoomTypeID'] = 0;
$Clean['ActiveStatus'] = 0;

// paging and sorting variables start here  //
$Clean['CurrentPage'] = 1;
$TotalPages = 0;

$Start = 0;
$Limit = GLOBAL_SITE_PAGGING;
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
        /*if ($LoggedUser->HasPermissionForTask(TASK_ADD_EDIT_TASK) !== true)
        {
            header('location:/admin/unauthorized_login_admin.php');
            exit;
        }*/
        
        if (isset($_GET['RoomID']))
        {
            $Clean['RoomID'] = (int) $_GET['RoomID'];           
        }
        
        if ($Clean['RoomID'] <= 0)
        {
            header('location:../error_page.php');
            exit;
        }                       
            
        try
        {
            $RoomToDelete = new Room($Clean['RoomID']);
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
        
        $SearchValidator = new Validator();
        
        if ($RoomToDelete->CheckDependencies())
        {
            $SearchValidator->AttachTextError('This room cannot be deleted. There are dependent records for this room.');
            $HasErrors = true;
            break;
        }
                
        if (!$RoomToDelete->Remove())
        {
            $SearchValidator->AttachTextError(ProcessErrors($RoomToDelete->GetLastErrorCode()));
            $HasErrors = true;
            break;
        }
        
        $RecordDeletedSuccessfully = true;
    break;

    case 7:        
        if (isset($_GET['drdWing']))
        {
            $Clean['WingID'] = (int) $_GET['drdWing'];
        }
        elseif (isset($_GET['WingID']))
        {
            $Clean['WingID'] = (int) $_GET['WingID'];
        }

        if (isset($_GET['drdRoomType']))
        {
            $Clean['RoomTypeID'] = (int) $_GET['drdRoomType'];
        }
        elseif (isset($_GET['RoomTypeID']))
        {
            $Clean['RoomTypeID'] = (int) $_GET['RoomTypeID'];
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

        if ($Clean['WingID'] != 0)
        {
            $SearchValidator->ValidateInSelect($Clean['WingID'], $Winglist, 'Unknown Error, Please try again.');
        }

        if ($Clean['RoomTypeID'] != 0)
        {
            $SearchValidator->ValidateInSelect($Clean['RoomTypeID'], $RoomTypelist, 'Unknown Error, Please try again.');
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
        $Filters['WingID'] = $Clean['WingID'];
        $Filters['RoomTypeID'] = $Clean['RoomTypeID'];
        $Filters['ActiveStatus'] = $Clean['ActiveStatus'];

        //get records count
        Room::SearchRooms($TotalRecords, true, $Filters);

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
            $RoomList = Room::SearchRooms($TotalRecords, false, $Filters, $Start, $Limit);
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
<title>Room Report</title>
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
                    <h1 class="page-header">Room Report</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
            <form class="form-horizontal" name="frmRoomReport" action="room_list.php" method="get">
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
                                <label for="Wing" class="col-lg-2 control-label">Wing</label>
                                <div class="col-lg-4">
                                    <select class="form-control" name="drdWing" id="Wing">
                                        <option<?php echo (($Clean['WingID'] == 0) ? ' selected="selected"' : ''); ?> value="0">All Wings</option>
<?php
                            if (is_array($Winglist) && count($Winglist) > 0)
                            {
                                foreach($Winglist as $WingID => $WingName)
                                {
                                    echo '<option ' . (($Clean['WingID'] == $WingID) ? 'selected="selected"' : '' ) . ' value="' . $WingID . '">' . $WingName . '</option>';
                                }
                            }
?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">                            
                                <label for="RoomType" class="col-lg-2 control-label">RoomType</label>
                                <div class="col-lg-4">
                                    <select class="form-control" name="drdRoomType" id="RoomType">
                                        <option<?php echo (($Clean['RoomTypeID'] == 0) ? ' selected="selected"' : ''); ?> value="0">All Room Types</option>
<?php
                            if (is_array($RoomTypelist) && count($RoomTypelist) > 0)
                            {
                                foreach($RoomTypelist as $RoomTypeID => $RoomType)
                                {
                                    echo '<option ' . (($Clean['RoomTypeID'] == $RoomTypeID) ? 'selected="selected"' : '' ) . ' value="' . $RoomTypeID . '">' . $RoomType . '</option>';
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
                                        <input type="radio" <?php echo (($Clean['ActiveStatus'] == 0) ? 'checked="checked"' : ''); ?> name="optActiveStatus" id="ActiveStatus" value="0" checked>All Room
                                    </label>
                                    <label class="radio-inline">
                                        <input type="radio" <?php echo (($Clean['ActiveStatus'] == 1) ? 'checked="checked"' : ''); ?> name="optActiveStatus" id="ActiveStatus" value="1">Active Room
                                    </label>
                                    <label class="radio-inline">
                                        <input type="radio" <?php echo (($Clean['ActiveStatus'] == 2) ? 'checked="checked"' : ''); ?> name="optActiveStatus" id="ActiveStatus" value="2">In-Active Room
                                    </label>
                                </div>
                            </div>                    
                            <div class="form-group">
                                <div class="col-lg-offset-2 col-lg-10">
                                    <input type="hidden" name="hdnProcess" value="7" />
                                    <button type="submit" class="btn btn-primary">Search</button>
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

            if ($Clean['WingID'] != 0)
            {
                $ReportHeaderText .= ' Wing: ' . $Winglist[$Clean['WingID']] . ',';
            }

            if ($Clean['RoomTypeID'] != 0)
            {
                $ReportHeaderText .= ' Room Type: ' . $RoomTypelist[$Clean['RoomTypeID']] . ',';
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
                                        $AllParameters = array('Process' => '7', 'WingID' => $Clean['WingID'], 'RoomTypeID' => $Clean['RoomTypeID'], 'ActiveStatus' => $Clean['ActiveStatus']);
                                        echo UIHelpers::GetPager('room_list.php', $TotalPages, $Clean['CurrentPage'], $AllParameters);
                                    }
?>                                        
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="print-btn-container"><button id="PrintButton" type="submit" class="btn btn-primary">Print</button></div>
                                    </div>
                                </div>
                                <div class="row" id="RecordTableHeading">
                                    <div class="col-lg-12">
                                        <div class="report-heading-container"><strong>Room Report on <?php echo date('d-m-Y h:i A') . $ReportHeaderText; ?></strong></div>
                                    </div>
                                </div>
                                <div class="row" id="RecordTable">
                                    <div class="col-lg-12">
                                        <table width="100%" class="table table-striped table-bordered table-hover" id="DataTableRecords">
                                            <thead>
                                                <tr>
                                                    <th>S.No</th>
                                                    <th>Room Name</th>
                                                    <th>Wing Name</th>
                                                    <th>Room Type</th>
                                                    <th>Bed</th>
                                                    <th>Free Bed</th>
                                                    <th>Monthly Fee</th>
                                                    <th>Quarterly Fee</th>
                                                    <th>Semi-Annual Fee</th>
                                                    <th>Annual Fee</th>
                                                    <th>Is Active</th>
                                                    <th>Create User</th>
                                                    <th>Create Date</th>
                                                    <th class="print-hidden">Operations</th>
                                                </tr>
                                            </thead>
                                            <tbody>
<?php
                                    if (is_array($RoomList) && count($RoomList) > 0)
                                    {
                                        $Counter = $Start;
                                        foreach ($RoomList as $RoomID => $RoomDetails)
                                        {
?>
                                                <tr>
                                                    <td><?php echo ++$Counter; ?></td>
                                                    <td><?php echo $RoomDetails['RoomName']; ?></td>
                                                    <td><?php echo $RoomDetails['WingName']; ?></td>
                                                    <td><?php echo $RoomDetails['RoomType']; ?></td>
                                                    <td><?php echo $RoomDetails['BedCount']; ?></td>
                                                    <td><?php echo $RoomDetails['FreeBed']; ?></td>
                                                    <td><?php echo $RoomDetails['MonthlyFee']; ?></td>
                                                    <td><?php echo $RoomDetails['QuarterlyFee']; ?></td>
                                                    <td><?php echo $RoomDetails['SemiAnnualFee']; ?></td>
                                                    <td><?php echo $RoomDetails['AnnualFee']; ?></td>
                                                    <td><?php echo (($RoomDetails['IsActive']) ? 'Yes' : 'No'); ?></td>
                                                    <td><?php echo $RoomDetails['CreateUserName']; ?></td>
                                                    <td><?php echo date('d/m/Y', strtotime($RoomDetails['CreateDate'])); ?></td>
                                                    <td class="print-hidden">
<?php
                                                    echo '<a href="edit_room.php?Process=2&amp;RoomID=' . $RoomID . '">Edit</a>';
                                                    echo '&nbsp;|&nbsp;';
                                                    echo '<a href="room_list.php?Process=5&amp;RoomID=' . $RoomID . '" class="delete-record">Delete</a>'; 
                                                    /*if ($LoggedUser->HasPermissionForTask(TASK_EDIT_MENU) === true)
                                                    {
                                                        echo '<a href="edit_room.php?Process=2&amp;RoomID=' . $RoomID . '">Edit</a>';
                                                    }
                                                    else
                                                    {
                                                        echo 'Edit';
                                                    }

                                                    echo '&nbsp;|&nbsp;';

                                                    if ($LoggedUser->HasPermissionForTask(TASK_DELETE_MENU) === true)
                                                    {
                                                        echo '<a href="room_list.php?Process=5&amp;RoomID=' . $RoomID . '" class="delete-record">Delete</a>' 
                                                    }
                                                    else
                                                    {
                                                        echo 'Delete';
                                                    }*/
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
                                                    <td colspan="13">No Records</td>
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
        if (!confirm("Are you sure you want to delete this room?"))
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
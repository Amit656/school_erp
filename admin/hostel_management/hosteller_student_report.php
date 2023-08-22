<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once("../../classes/class.ui_helpers.php");

require_once("../../classes/hostel_management/class.wings.php");
require_once("../../classes/hostel_management/class.room_types.php");
require_once("../../classes/hostel_management/class.rooms.php");
require_once("../../classes/hostel_management/class.student_hostel_allotment.php");

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

$WingList = array();
$WingList = Wing::GetActiveWings();

$RoomTypeList = array();
$RoomTypeList = RoomType::GetActiveRoomTypes();

$RoomList = array();
$HostellerStudentsList = array();

$RecordDeletedSuccessfully = false;
$HasErrors = false;
$TotalRecords = 0;

$Clean = array();
$Clean['Process'] = 0;

$Clean['WingID'] = 0;
$Clean['RoomTypeID'] = 0;
$Clean['RoomID'] = 0;
$Clean['StudentName'] = '';
$Clean['ActiveStatus'] = 0;

// paging and sorting variables start here  //
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

        if (isset($_GET['drdRoom']))
        {
            $Clean['RoomID'] = (int) $_GET['drdRoom'];
        }
        elseif (isset($_GET['RoomID']))
        {
            $Clean['RoomID'] = (int) $_GET['RoomID'];
        }

        if (isset($_GET['txtStudentName']))
        {
            $Clean['StudentName'] = strip_tags(trim($_GET['txtStudentName']));
        }
        elseif (isset($_GET['StudentName']))
        {
            $Clean['StudentName'] = strip_tags(trim($_GET['StudentName']));
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
            $SearchValidator->ValidateInSelect($Clean['WingID'], $WingList, 'Unknown error, please try again.');
        }

        if ($Clean['RoomTypeID'] != 0)
        {
            $SearchValidator->ValidateInSelect($Clean['RoomTypeID'], $RoomTypeList, 'Unknown error, please try again.');
        }

        if ($Clean['WingID'] != 0) 
        {
            $Filters['WingID'] = $Clean['WingID'];
            $Filters['RoomTypeID'] = $Clean['RoomTypeID'];

            $RoomList = Room::SearchRooms($TotalRecords, false, $Filters);
        }

        if ($Clean['RoomID'] != 0)
        {
            $SearchValidator->ValidateInSelect($Clean['RoomID'], $RoomList, 'Unknown error, please try again.');
        }

        if ($Clean['StudentName'] != '')
        {
            $SearchValidator->ValidateStrings($Clean['StudentName'], 'Student name should be between 1 to 50 character.');
        }

        if ($Clean['ActiveStatus'] < 0 || $Clean['ActiveStatus'] > 2)
        {
            $SearchValidator->AttachTextError('Unknown error, please try again.');
        }
        
        if ($SearchValidator->HasNotifications())
        {
            $HasErrors = true;
            break;
        }

        //set record filters
        $Filters['WingID'] = $Clean['WingID'];
        $Filters['RoomTypeID'] = $Clean['RoomTypeID'];
        $Filters['RoomID'] = $Clean['RoomID'];
        $Filters['StudentName'] = $Clean['StudentName'];
        $Filters['ActiveStatus'] = $Clean['ActiveStatus'];

        //get records count
        StudentHostelAllotment::SearchHostellerStudents($TotalRecords, true, $Filters);

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
            $HostellerStudentsList = StudentHostelAllotment::SearchHostellerStudents($TotalRecords, false, $Filters, $Start, $Limit);
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
<title>Hosteller Student Report</title>
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
                    <h1 class="page-header">Hosteller Student Report</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
            <form class="form-horizontal" name="frmRoomReport" action="hosteller_student_report.php" method="get">
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
                                        <option<?php echo (($Clean['WingID'] == 0) ? ' selected="selected"' : ''); ?> value="0">-- All Wings --</option>
<?php
                            if (is_array($WingList) && count($WingList) > 0)
                            {
                                foreach($WingList as $WingID => $WingName)
                                {
                                    echo '<option ' . (($Clean['WingID'] == $WingID) ? 'selected="selected"' : '' ) . ' value="' . $WingID . '">' . $WingName . '</option>';
                                }
                            }
?>
                                    </select>
                                </div>
                                <label for="RoomType" class="col-lg-2 control-label">Room Type</label>
                                <div class="col-lg-4">
                                    <select class="form-control" name="drdRoomType" id="RoomType">
                                        <option<?php echo (($Clean['RoomTypeID'] == 0) ? ' selected="selected"' : ''); ?> value="0">-- All Room Types --</option>
<?php
                            if (is_array($RoomTypeList) && count($RoomTypeList) > 0)
                            {
                                foreach($RoomTypeList as $RoomTypeID => $RoomType)
                                {
                                    echo '<option ' . (($Clean['RoomTypeID'] == $RoomTypeID) ? 'selected="selected"' : '' ) . ' value="' . $RoomTypeID . '">' . $RoomType . '</option>';
                                }
                            }
?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">                            
                                <label for="Room" class="col-lg-2 control-label">Room</label>
                                <div class="col-lg-4">
                                    <select class="form-control" name="drdRoom" id="Room">
                                        <option<?php echo (($Clean['RoomID'] == 0) ? ' selected="selected"' : ''); ?> value="0">-- All Rooms --</option>
<?php
                            if (is_array($RoomList) && count($RoomList) > 0)
                            {
                                foreach($RoomList as $RoomID => $RoomDetails)
                                {
                                    echo '<option ' . (($Clean['RoomID'] == $RoomID) ? 'selected="selected"' : '' ) . ' value="' . $RoomID . '">' . $RoomDetails['RoomName'] .' ( '. $RoomDetails['RoomType'] .' )' . '</option>';
                                }
                            }
?>
                                    </select>
                                </div>
                                <label for="StudentName" class="col-lg-2 control-label">Student Name</label>
                                <div class="col-lg-4">
                                    <input class="form-control" type="text" maxlength="50" id="StudentName" name="txtStudentName" value="<?php echo $Clean['StudentName']; ?>" />
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
                $ReportHeaderText .= ' Wing: ' . $WingList[$Clean['WingID']] . ',';
            }

            if ($Clean['RoomTypeID'] != 0)
            {
                $ReportHeaderText .= ' Room Type: ' . $RoomTypeList[$Clean['RoomTypeID']] . ',';
            }

            if ($Clean['RoomID'] != 0)
            {
                $ReportHeaderText .= ' Room : ' . $RoomList[$Clean['RoomID']]['RoomName'] . ',';
            }

            if ($Clean['StudentName'] != '')
            {
                $ReportHeaderText .= ' Student : ' . $Clean['StudentName'] . ',';
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
                                        $AllParameters = array('Process' => '7', 'WingID' => $Clean['WingID'], 'RoomTypeID' => $Clean['RoomTypeID'], 'RoomID' => $Clean['RoomID'], 'StudentName' => $Clean['StudentName'], 'ActiveStatus' => $Clean['ActiveStatus']);
                                        echo UIHelpers::GetPager('hosteller_student_report.php', $TotalPages, $Clean['CurrentPage'], $AllParameters);
                                    }
?>                                        
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="print-btn-container"><button id="PrintButton" type="submit" class="btn btn-primary">Print</button></div>
                                    </div>
                                </div>
                                <div class="row" id="RecordTableHeading">
                                    <div class="col-lg-12">
                                        <div class="report-heading-container"><strong>Hosteller Student Report on <?php echo date('d-m-Y h:i A') . $ReportHeaderText; ?></strong></div>
                                    </div>
                                </div>
                                <div class="row" id="RecordTable">
                                    <div class="col-lg-12">
                                        <table width="100%" class="table table-striped table-bordered table-hover" id="DataTableRecords">
                                            <thead>
                                                <tr>
                                                    <th>S.No</th>
                                                    <th>Student Name</th>
                                                    <th>Wing Name</th>
                                                    <th>Room</th>
                                                    <th>Mess</th>
                                                    <th>Is Active</th>
                                                    <th>Create User</th>
                                                    <th>Create Date</th>
                                                </tr>
                                            </thead>
                                            <tbody>
<?php
                                    if (is_array($HostellerStudentsList) && count($HostellerStudentsList) > 0)
                                    {
                                        $Counter = $Start;
                                        foreach ($HostellerStudentsList as $StudentHostelAllotmentID => $Details)
                                        {
?>
                                                <tr>
                                                    <td><?php echo ++$Counter; ?></td>
                                                    <td><?php echo $Details['StudentName']; ?></td>
                                                    <td><?php echo $Details['WingName']; ?></td>
                                                    <td><?php echo $Details['RoomName'] .' ( '. $Details['RoomType'] .' )'; ?></td>
                                                    <td><?php echo (($Details['MessName'] == '') ? 'N/A' : $Details['Mess']); ?></td>
                                                    <td><?php echo (($Details['IsActive']) ? 'Yes' : 'No'); ?></td>
                                                    <td><?php echo $Details['CreateUserName']; ?></td>
                                                    <td><?php echo date('d/m/Y', strtotime($Details['CreateDate'])); ?></td>
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

    $('#Wing').change(function(){

        var WingID = parseInt($(this).val());
        var RoomTypeID = parseInt($('#RoomType').val());
        
        if (WingID <= 0)
        {
            $('#Room').html('<option value="0">-- All Rooms --</option>');
            return false;
        }
        
        $.post("/xhttp_calls/get_rooms_by_wing.php", {SelectedWingID:WingID, SelectedRoomTypeID:RoomTypeID}, function(data)
        {
            ResultArray = data.split("|*****|");
            
            if (ResultArray[0] == 'error')
            {
                alert (ResultArray[1]);
                $('#Room').html('<option value="0">-- All Rooms --</option>');
                return false;
            }
            else
            {
                $('#Room').html('<option value="0">-- All Rooms --</option>' + ResultArray[1]);
            }
        });
    });

    $('#RoomType').change(function(){

        var WingID = parseInt($('#Wing').val());
        var RoomTypeID = parseInt($('#RoomType').val());
        
        if (WingID <= 0)
        {
            $('#Room').html('<option value="0">-- All Rooms --</option>');
            return false;
        }
        
        $.post("/xhttp_calls/get_rooms_by_wing.php", {SelectedWingID:WingID, SelectedRoomTypeID:RoomTypeID}, function(data)
        {
            ResultArray = data.split("|*****|");
            
            if (ResultArray[0] == 'error')
            {
                alert (ResultArray[1]);
                $('#Room').html('<option value="0">-- All Rooms --</option>');
                return false;
            }
            else
            {
                $('#Room').html('<option value="0">-- All Rooms --</option>' + ResultArray[1]);
            }
        });
    });
});
</script>
<!-- JavaScript To Print A Report -->
<script src="/admin/js/print-report.js"></script>
</body>
</html>
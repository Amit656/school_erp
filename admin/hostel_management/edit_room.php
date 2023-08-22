<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

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
    header('location:room_list.php');
    exit;
}

$Clean = array();

$Clean['RoomID'] = 0;

if (isset($_GET['RoomID']))
{
    $Clean['RoomID'] = (int) $_GET['RoomID'];
}
else if (isset($_POST['hdnRoomID']))
{
    $Clean['RoomID'] = (int) $_POST['hdnRoomID'];
}

if ($Clean['RoomID'] <= 0)
{
    header('location:../error.php');
    exit;
}   

try 
{
    $RoomToEdit = new Room($Clean['RoomID']);
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

$Winglist = array();
$Winglist = Wing::GetActiveWings();

$RoomTypelist = array();
$RoomTypelist = RoomType::GetActiveRoomTypes();

$HasErrors = false;

$Clean['Process'] = 0;

$Clean['WingID'] = 0;
$Clean['RoomTypeID'] = 0;

$Clean['RoomName'] = '';
$Clean['BedCount'] = 0;

$Clean['MonthlyFee'] = 0;
$Clean['QuarterlyFee'] = 0;
$Clean['SemiAnnualFee'] = 0;
$Clean['AnnualFee'] = 0;

$Clean['IsActive'] = 0;

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
        if (isset($_POST['drdWing']))
        {
            $Clean['WingID'] = (int) $_POST['drdWing'];
        }
        if (isset($_POST['drdRoomType']))
        {
            $Clean['RoomTypeID'] = (int) $_POST['drdRoomType'];
        }
        if (isset($_POST['txtRoomName']))
        {
            $Clean['RoomName'] = strip_tags(trim($_POST['txtRoomName']));
        }
        if (isset($_POST['txtBedCount']))
        {
            $Clean['BedCount'] = strip_tags(trim($_POST['txtBedCount']));
        }
        if (isset($_POST['txtMonthlyFee']))
        {
            $Clean['MonthlyFee'] = strip_tags(trim($_POST['txtMonthlyFee']));
        }
        if (isset($_POST['txtQuarterlyFee']))
        {
            $Clean['QuarterlyFee'] = strip_tags(trim($_POST['txtQuarterlyFee']));
        }
        if (isset($_POST['txtSemiAnnualFee']))
        {
            $Clean['SemiAnnualFee'] = strip_tags(trim($_POST['txtSemiAnnualFee']));
        }
        if (isset($_POST['txtAnnualFee']))
        {
            $Clean['AnnualFee'] = strip_tags(trim($_POST['txtAnnualFee']));
        }

        $NewRecordValidator = new Validator();

        $NewRecordValidator->ValidateInSelect($Clean['WingID'], $Winglist, 'Please select a wing.');
        $NewRecordValidator->ValidateInSelect($Clean['RoomTypeID'], $RoomTypelist, 'Please Select a room type.');
        
        $NewRecordValidator->ValidateStrings($Clean['RoomName'], 'Room name is required and should be between 3 and 50 characters.', 3, 50);
        $NewRecordValidator->ValidateInteger($Clean['BedCount'], 'Invalid bed count, please enter valid number.', 1);

        if ($Clean['MonthlyFee'] <= 0 && $Clean['QuarterlyFee'] <= 0 && $Clean['SemiAnnualFee'] <= 0 && $Clean['AnnualFee'] <= 0) 
        {
            $NewRecordValidator->AttachTextError('Please enter atleast one type of fee.');
            $HasErrors = true;
            break;
        }

        if ($Clean['MonthlyFee'] > 0) 
        {
            $NewRecordValidator->ValidateNumeric($Clean['MonthlyFee'], 'Invalid monthly fee, please enter valid numeric value.');
        }
        if ($Clean['QuarterlyFee'] > 0) 
        {
            $NewRecordValidator->ValidateNumeric($Clean['QuarterlyFee'], 'Invalid quarterly fee, please enter valid numeric value.');
        }
        if ($Clean['SemiAnnualFee'] > 0) 
        {
            $NewRecordValidator->ValidateNumeric($Clean['SemiAnnualFee'], 'Invalid semi annually fee, please enter valid numeric value.');
        }
        if ($Clean['AnnualFee'] > 0) 
        {
            $NewRecordValidator->ValidateNumeric($Clean['AnnualFee'], 'Invalid annually fee, please enter valid numeric value.');
        }
        
        if ($NewRecordValidator->HasNotifications())
        {
            $HasErrors = true;
            break;
        }
                
        $RoomToEdit->SetWingID($Clean['WingID']);
        $RoomToEdit->SetRoomTypeID($Clean['RoomTypeID']);

        $RoomToEdit->SetRoomName($Clean['RoomName']);
        $RoomToEdit->SetBedCount($Clean['BedCount']);

        $RoomToEdit->SetMonthlyFee($Clean['MonthlyFee']);
        $RoomToEdit->SetQuarterlyFee($Clean['QuarterlyFee']);
        $RoomToEdit->SetSemiAnnualFee($Clean['SemiAnnualFee']);
        $RoomToEdit->SetAnnualFee($Clean['AnnualFee']);

        $RoomToEdit->SetIsActive($Clean['IsActive']);

        if (!$RoomToEdit->Save())
        {
            $NewRecordValidator->AttachTextError(ProcessErrors($RoomToEdit->GetLastErrorCode()));
            $HasErrors = true;
            break;
        }
        
        header('location:room_list.php?Mode=UD');
        exit;
    break;

    case 2:
        $Clean['WingID'] = $RoomToEdit->GetWingID();
        $Clean['RoomTypeID'] = $RoomToEdit->GetRoomTypeID();

        $Clean['RoomName'] = $RoomToEdit->GetRoomName();
        $Clean['BedCount'] = $RoomToEdit->GetBedCount();

        $Clean['MonthlyFee'] = $RoomToEdit->GetMonthlyFee();
        $Clean['QuarterlyFee'] = $RoomToEdit->GetQuarterlyFee();
        $Clean['SemiAnnualFee'] = $RoomToEdit->GetSemiAnnualFee();
        $Clean['AnnualFee'] = $RoomToEdit->GetAnnualFee();

        $Clean['IsActive'] = $RoomToEdit->GetIsActive();

    break;
}

require_once('../html_header.php');
?>
<title>Edit Room</title>
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
                    <h1 class="page-header">Edit Room</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="EditRoom" action="edit_room.php" method="post">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        Enter Room Details
                    </div>
                    <div class="panel-body">
<?php
                        if ($HasErrors == true)
                        {
                            echo $NewRecordValidator->DisplayErrors();
                        }
?>                    
                        <div class="form-group">
                            <label for="Wing" class="col-lg-2 control-label">Wing</label>
                            <div class="col-lg-4">
                                <select class="form-control" name="drdWing" id="Wing">
                                    <option value="">-- Select Wing --</option>
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
                            <label for="RoomType" class="col-lg-2 control-label">Room Type</label>
                            <div class="col-lg-4">
                                <select class="form-control" name="drdRoomType" id="RoomType">
                                    <option value="">-- Select Room Type --</option>
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
                            <label for="RoomName" class="col-lg-2 control-label">Room Name</label>
                            <div class="col-lg-4">
                                <input class="form-control" type="text" maxlength="50" id="RoomName" name="txtRoomName" value="<?php echo $Clean['RoomName']; ?>" />
                            </div>
                            <label for="BedCount" class="col-lg-2 control-label">Bed Count</label>
                            <div class="col-lg-4">
                                <input class="form-control" type="text" maxlength="5" id="BedCount" name="txtBedCount" value="<?php echo ($Clean['BedCount']) ? $Clean['BedCount'] : ''; ?>" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="MonthlyFee" class="col-lg-2 control-label">Monthly Fee</label>
                            <div class="col-lg-4">
                                <div class="input-group">
                                    <input class="form-control" type="text" maxlength="10" id="MonthlyFee" name="txtMonthlyFee" value="<?php echo ($Clean['MonthlyFee']) ? $Clean['MonthlyFee'] : ''; ?>" />
                                    <span class="input-group-addon">Per bed</span>
                                </div>
                            </div>
                            <label for="QuarterlyFee" class="col-lg-2 control-label">Quarterly Fee</label>
                            <div class="col-lg-4">
                                <div class="input-group">
                                    <input class="form-control" type="text" maxlength="10" id="QuarterlyFee" name="txtQuarterlyFee" value="<?php echo ($Clean['QuarterlyFee']) ? $Clean['QuarterlyFee'] : ''; ?>" />
                                    <span class="input-group-addon">Per bed</span>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="SemiAnnualFee" class="col-lg-2 control-label">Semi-Annual Fee</label>
                            <div class="col-lg-4">
                                <div class="input-group">
                                    <input class="form-control" type="text" maxlength="10" id="SemiAnnualFee" name="txtSemiAnnualFee" value="<?php echo ($Clean['SemiAnnualFee']) ? $Clean['SemiAnnualFee'] : ''; ?>" />
                                    <span class="input-group-addon">Per bed</span>
                                </div>
                            </div>
                            <label for="AnnualFee" class="col-lg-2 control-label">Annual Fee</label>
                            <div class="col-lg-4">
                                <div class="input-group">
                                    <input class="form-control" type="text" maxlength="10" id="AnnualFee" name="txtAnnualFee" value="<?php echo ($Clean['AnnualFee']) ? $Clean['AnnualFee'] : ''; ?>" />
                                    <span class="input-group-addon">Per bed</span>
                                </div>
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
                            <input type="hidden" name="hdnRoomID" value="<?php echo $Clean['RoomID']; ?>" />
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
</body>
</html>
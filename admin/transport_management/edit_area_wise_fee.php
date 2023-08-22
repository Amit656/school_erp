<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");
require_once("../../classes/class.date_processing.php");

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

if ($LoggedUser->HasPermissionForTask(TASK_EDIT_AREAWISE_FEE) !== true)
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
    header('location:area_wise_fee_report.php');
    exit;
}

$Clean = array();

$Clean['AreaWiseFeeID'] = 0;

if (isset($_GET['AreaWiseFeeID']))
{
    $Clean['AreaWiseFeeID'] = (int) $_GET['AreaWiseFeeID'];
}
else if (isset($_POST['hdnAreaWiseFeeID']))
{
    $Clean['AreaWiseFeeID'] = (int) $_POST['hdnAreaWiseFeeID'];
}
if ($Clean['AreaWiseFeeID'] <= 0)
{
    header('location:../error.php');
    exit;
}  
try
{
    $AreaWiseFeeToEdit = new AreaWiseFee($Clean['AreaWiseFeeID']);
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

$RouteList =  array();
$RouteList = Route::GetActiveRoutes();

$AreaList = array();
$AreaList = AreaMaster::GetActiveArea();

$AcademicYears = array();
$AcademicYears = AcademicYear::GetAllAcademicYears();

$HasErrors = false;

$Clean['Process'] = 0;

$Clean['RouteID'] = 0;
$Clean['AreaID'] = 0;
$Clean ['Amount'] = 0;
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
        if (isset($_POST['drdRouteID']))
        {
            $Clean['RouteID'] = (int) $_POST['drdRouteID'];
        }
        if (isset($_POST['drdAreaID']))
        {
            $Clean['AreaID'] = (int) $_POST['drdAreaID'];
        }
        if (isset($_POST['drdAcademicYearID']))
        {
            $Clean['AcademicYearID'] = (int) $_POST['drdAcademicYearID'];
        }
        if (isset($_POST['txtAmount']))
        {
            $Clean['Amount'] = strip_tags(trim($_POST['txtAmount']));
        }
        if (isset($_POST['chkIsActive']))
        {
            $Clean['IsActive'] = 1;
        }

        $NewRecordValidator = new Validator();
        $NewRecordValidator->ValidateInSelect($Clean['RouteID'], $RouteList, 'Unkown error please try again.');
        $NewRecordValidator->ValidateInSelect($Clean['AreaID'], $AreaList, 'Unkown error please try again.');
        $NewRecordValidator->ValidateInSelect($Clean['AcademicYearID'], $AcademicYears, 'Unknown error, please try again.');
        $NewRecordValidator->ValidateNumeric($Clean['Amount'], 'Please Enter Numeric value.');

        if ($NewRecordValidator->HasNotifications())
        {
            $HasErrors = true;
            break;
        }

        $AreaWiseFeeToEdit->SetRouteID($Clean['RouteID']);
        $AreaWiseFeeToEdit->SetAreaID($Clean['AreaID']);
        $AreaWiseFeeToEdit->SetAcademicYearID($Clean['AcademicYearID']);
        $AreaWiseFeeToEdit->SetAmount($Clean['Amount']);
        $AreaWiseFeeToEdit->SetIsActive($Clean['IsActive']);

        if (!$AreaWiseFeeToEdit->Save())
        {
            $NewRecordValidator->AttachTextError(ProcessErrors($AreaWiseFeeToEdit->GetLastErrorCode()));
            $HasErrors = true;

            break;
        }
        
        header('location:area_wise_fee_report.php?Mode=UD');
        exit;
    break;

    case 2:
        $Clean['RouteID'] = $AreaWiseFeeToEdit->GetRouteID();   
        $Clean['AreaID'] = $AreaWiseFeeToEdit->GetAreaID();
        $Clean['AcademicYearID'] = $AreaWiseFeeToEdit->GetAcademicYearID();
        $Clean['Amount'] = $AreaWiseFeeToEdit->GetAmount();
        $Clean['IsActive'] = $AreaWiseFeeToEdit->GetIsActive();

    break;
}

require_once('../html_header.php');
?>
<title>Edit Area Wise Fee</title>
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
                    <h1 class="page-header">Edit Area Wise Fee</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="EditVehicleDriver" action="edit_area_wise_fee.php" method="post">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        Enter Area Wise Fee Details
                    </div>
                    <div class="panel-body">
<?php
                        if ($HasErrors == true)
                        {
                            echo $NewRecordValidator->DisplayErrors();
                        }
?>                    
                        <div class="form-group">
                            <label for="RouteID" class="col-lg-2 control-label">Route No.</label>
                                <div class="col-lg-3">
                                    <select class="form-control"  name="drdRouteID" id="RouteID">
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
                            <label for="Amount" class="col-lg-2 control-label">Amount</label>
                            <div class="col-lg-3">
                                <input class="form-control" type="text" maxlength="10" id="Amount" name="txtAmount" value="<?php echo $Clean['Amount']; ?>" />
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
                            <input type="hidden" name="hdnAreaWiseFeeID" value="<?php echo $Clean['AreaWiseFeeID']; ?>" />
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
		$ (document).ready (function ()
		{

    $(".select-date").datepicker({
        changeMonth: true,
        changeYear: true,
        dateFormat: 'dd/mm/yy'
    });
}); 
</script>
</body>
</html>_
<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

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

if ($LoggedUser->HasPermissionForTask(TASK_ADD_AREAWISE_FEE) !== true)
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

if (isset($_POST['btnCancel']))
{
    header('location:add_area_wise_fee.php');
    exit;
}

$HasErrors = false;

$RouteList = array();
$RouteList = Route::GetActiveRoutes();

$AreaList = array();
$AreaList = AreaMaster::GetActiveArea();

$AcademicYears = array();
$AcademicYears = AcademicYear::GetAllAcademicYears();

$Clean = array();
$Clean['Process'] = 0;

$Clean['RouteID'] = 0;
$Clean['AreaID'] = 0;
$Clean['AcademicYearID'] = 0;
$Clean['Amount'] = 0;

if (isset($_POST['hdnProcess']))
{
    $Clean['Process'] = (int) $_POST['hdnProcess'];
}
switch ($Clean['Process'])
{
    case 1:
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

        $NewRecordValidator = new Validator();

        $NewRecordValidator->ValidateInSelect($Clean['RouteID'], $RouteList, 'Please select a valid route.');
        $NewRecordValidator->ValidateInSelect($Clean['AreaID'], $AreaList, 'Please select a valid area.');
		$NewRecordValidator->ValidateInSelect($Clean['AcademicYearID'], $AcademicYears, 'Unknown error, please try again.');
        $NewRecordValidator->ValidateNumeric($Clean['Amount'], 'Please enter numeric value for amount.');
        
        if ($NewRecordValidator->HasNotifications())
        {
            $HasErrors = true;
            break;
        }
                
        $NewAreaWiseFee = new AreaWiseFee();
        $NewAreaWiseFee->SetRouteID($Clean['RouteID']);
        $NewAreaWiseFee->SetAreaID($Clean['AreaID']);
		$NewAreaWiseFee->SetAcademicYearID($Clean['AcademicYearID']);
        $NewAreaWiseFee->SetAmount($Clean['Amount']);

        $NewAreaWiseFee->SetIsActive(1);
        $NewAreaWiseFee->SetCreateUserID($LoggedUser->GetUserID());

		if ($NewAreaWiseFee->RecordExists())
		{
			$NewRecordValidator->AttachTextError('Area wise fee already added for this route in this academic year.');
			$HasErrors = true;
			break;
		}

        if (!$NewAreaWiseFee->Save())
        {
            $NewRecordValidator->AttachTextError(ProcessErrors($NewAreaWiseFee->GetLastErrorCode()));
            $HasErrors = true;
            break;
        }
        
        header('location:area_wise_fee_report.php?Mode=AS');
        exit;
    break;
}

require_once('../html_header.php');
?>
<title>Add Fee AreaWise</title>
<link href="vendor/jquery-ui/jquery-ui.min.css" rel="stylesheet">
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
                    <h1 class="page-header">Fee Area Wise</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="AddAreaWiseFee" action="add_area_wise_fee.php" method="post">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <strong>Enter Fee Area Details</strong>
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
                                <input class="form-control" type="text" maxlength="10" id="Amount" name="txtAmount" value="<?php echo $Clean['Amount']?>" />
                            </div>
                        </div>
                        <div class="form-group">
                        <div class="col-sm-offset-2 col-lg-10">
                            <input type="hidden" name="hdnProcess" value="1" /> 
                            <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i>&nbsp;Save</button>
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
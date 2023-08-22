<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once("../../classes/class.date_processing.php");

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
	header('location:../unauthorized_login_admin.php');
	exit;
}
catch (Exception $e)
{
	header('location:../unauthorized_login_admin.php');
	exit;
}
// END OF 1. //

if ($LoggedUser->HasPermissionForTask(TASK_ADD_VEHICLE) !== true)
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

$HasErrors = false;

$VehicleTypeList = array();
$VehicleTypeList = VehicleType::GetActiveVehicleType();

$Clean = array();
$Clean['Process'] = 0;

$Clean['VehicleTypeID'] = 0;
$Clean['VehicleName'] = '';
$Clean['VehicleNumber'] ='' ;

$Clean['RegistrationFrom'] = '';
$Clean['RegistrationTo'] = '';
$Clean['InsuranceFrom'] = '';
$Clean['InsuranceTo'] = '';

$Clean['PollutionFrom'] = '';
$Clean['PollutionTo'] = '';
$Clean['ApprovalFrom'] = '';
$Clean['ApprovalTo'] = '';

$Clean['FitnessDocumentNumber'] = '';
$Clean['PermitNumber'] = '';
$Clean['AvailableSaets'] = 0;

$Clean['IsDiesel'] = 0;
$Clean['IsPetrol'] = 0;
$Clean['IsGas'] = 0;

$Clean['LastServicedDate'] = '';
$Clean['ServiceDueDate'] = '';

if (isset($_POST['hdnProcess']))
{
	$Clean['Process'] = (int) $_POST['hdnProcess'];
}
switch ($Clean['Process'])
{
	case 1:
		if (isset($_POST['drdVehicleType']))
		{
			$Clean['VehicleTypeID'] = (int) $_POST['drdVehicleType'];
		}
		if (isset($_POST['txtVehicleName']))
		{
			$Clean['VehicleName'] = strip_tags(trim($_POST['txtVehicleName']));
		}
		if (isset($_POST['txtVehicleNumber']))
		{
			$Clean['VehicleNumber'] = strip_tags(trim($_POST['txtVehicleNumber']));
		}
		if (isset($_POST['txtRegistrationFrom']))
		{
			$Clean['RegistrationFrom'] = strip_tags(trim($_POST['txtRegistrationFrom']));
		}
		if (isset($_POST['txtRegistrationTo']))
		{
			$Clean['RegistrationTo'] = strip_tags(trim($_POST['txtRegistrationTo']));
		}
		if (isset($_POST['txtInsuranceFrom']))
		{
			$Clean['InsuranceFrom'] = strip_tags(trim($_POST['txtInsuranceFrom']));
		}
		if (isset($_POST['txtInsuranceTo']))
		{
			$Clean['InsuranceTo'] = strip_tags(trim($_POST['txtInsuranceTo']));
		}
		if (isset($_POST['txtPollutionFrom']))
		{
			$Clean['PollutionFrom'] = strip_tags(trim($_POST['txtPollutionFrom']));
		}
		if (isset($_POST['txtPollutionTo']))
		{
			$Clean['PollutionTo'] = strip_tags(trim($_POST['txtPollutionTo']));
		}
			if (isset($_POST['txtApprovalFrom']))
		{
			$Clean['ApprovalFrom'] = strip_tags(trim($_POST['txtApprovalFrom']));
		}
		if (isset($_POST['txtApprovalTo']))
		{
			$Clean['ApprovalTo'] = strip_tags(trim($_POST['txtApprovalTo']));
		}
		if (isset($_POST['txtFitnessDocumentNumber']))
		{
			$Clean['FitnessDocumentNumber'] = strip_tags(trim($_POST['txtFitnessDocumentNumber']));
		}
		if (isset($_POST['txtPermitNumber']))
		{
			$Clean['PermitNumber'] = strip_tags(trim($_POST['txtPermitNumber']));
		}
		if (isset($_POST['txtAvailableSaets']))
		{
			$Clean['AvailableSaets'] = strip_tags(trim($_POST['txtAvailableSaets']));
		}	
		if (isset($_POST['chkDiesel']))
        {
            $Clean['IsDiesel'] = 1;
        }
        if (isset($_POST['chkPetrol']))
        {
            $Clean['IsPetrol'] = 1;
        }
 		if (isset($_POST['chkGas']))
        {
            $Clean['IsGas'] = 1;
        }
		if (isset($_POST['txtLastServicedDate']))
		{
			$Clean['LastServicedDate'] = strip_tags(trim($_POST['txtLastServicedDate']));
		}
		if (isset($_POST['txtServiceDueDate']))
		{
			$Clean['ServiceDueDate'] = strip_tags(trim($_POST['txtServiceDueDate']));
		}

		$NewRecordValidator = new Validator();

			$NewRecordValidator->ValidateInteger($Clean['VehicleTypeID'], $VehicleTypeList, 'Please select valid vehicle type.');
			$NewRecordValidator->ValidateStrings($Clean['VehicleName'], 'Vehicle name is required and should be between 1 and 100 characters.', 1, 100);
			$NewRecordValidator->ValidateStrings($Clean['VehicleNumber'], 'Vehicle number is required and should be between 1 and 50 characters.', 1, 50);

		if ($Clean['RegistrationFrom'] != '')
        {
			$NewRecordValidator->ValidateDate($Clean['RegistrationFrom'], 'Please enter valid registration from date.');
		}

		if ($Clean['RegistrationTo'] != '')
   		{
			$NewRecordValidator->ValidateDate($Clean['RegistrationTo'], 'Please enter valid registration to date.');
		}
			$NewRecordValidator->ValidateDate($Clean['InsuranceFrom'], 'Please enter valid insurance from date.');
			$NewRecordValidator->ValidateDate($Clean['InsuranceTo'], 'Please enter valid insurance to date.');

			$NewRecordValidator->ValidateDate($Clean['PollutionFrom'], 'Please enter valid pollution from date.');
			$NewRecordValidator->ValidateDate($Clean['PollutionTo'], 'Please enter valid pollution to date.');
			
		if ($Clean['ApprovalFrom'] != '')
        {
            $NewRecordValidator->ValidateDate($Clean['ApprovalFrom'], 'Please enter valid approval from date.');
        }
    
        if ($Clean['ApprovalTo'] != '')
        {
            $NewRecordValidator->ValidateDate($Clean['ApprovalTo'], 'Please enter valid approval to date.');
        }

	    if ($Clean['FitnessDocumentNumber'] != '')
   		{
        	$NewRecordValidator->ValidateStrings($Clean['FitnessDocumentNumber'], 'fitness documentNumber number is required and should be between 1 and 50 characters.', 1, 50);
    	}
		    $NewRecordValidator->ValidateStrings($Clean['PermitNumber'], 'permit number is required and should be between 1 and 50 characters.', 1, 50);
		    $NewRecordValidator->ValidateInteger($Clean['AvailableSaets'], 'Please enter numeric value for quantity of Available saets.', 1);

		if ($Clean['LastServicedDate'] != '')
   		{
       		$NewRecordValidator->ValidateDate($Clean['LastServicedDate'], 'Please enter valid lastservice date.');
    	}

	    if ($Clean['ServiceDueDate'] != '')
        {
       	    $NewRecordValidator->ValidateDate($Clean['ServiceDueDate'], 'Please enter valid servicedue date.');
        }

		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}

		$NewVehicle = new Vehicle();
				
		$NewVehicle->SetVehicleTypeID($Clean['VehicleTypeID']);

		$NewVehicle->SetVehicleName($Clean['VehicleName']);
		$NewVehicle->SetVehicleNumber($Clean['VehicleNumber']);

		if ($Clean['RegistrationFrom'] != '')
        {	
			$NewVehicle->SetRegistrationFrom(date('Y-m-d', strtotime(DateProcessing::ToggleDateDayAndMonth(($Clean['RegistrationFrom'])))));
	    }
	    else
	    {
	    	 $NewVehicle->SetRegistrationFrom($Clean['RegistrationFrom']);
	    }

	    if ($Clean['RegistrationTo'] != '') 
	    {
	    	$NewVehicle->SetRegistrationTo(date('Y-m-d', strtotime(DateProcessing::ToggleDateDayAndMonth(($Clean['RegistrationTo'])))));
	    }
	    else
	    {
	    	 $NewVehicle->SetRegistrationFrom($Clean['RegistrationTo']);
	    }
		$NewVehicle->SetInsuranceFrom(date('Y-m-d', strtotime(DateProcessing::ToggleDateDayAndMonth(($Clean['InsuranceFrom'])))));
		$NewVehicle->SetInsuranceTo(date('Y-m-d', strtotime(DateProcessing::ToggleDateDayAndMonth(($Clean['InsuranceTo'])))));
		$NewVehicle->SetPollutionFrom(date('Y-m-d', strtotime(DateProcessing::ToggleDateDayAndMonth(($Clean['PollutionFrom'])))));
		$NewVehicle->SetPollutionTo(date('Y-m-d', strtotime(DateProcessing::ToggleDateDayAndMonth(($Clean['PollutionTo'])))));
		
		if ($Clean['ApprovalFrom'] != '')
        {
			$NewVehicle->SetApprovalFrom(date('Y-m-d', strtotime(DateProcessing::ToggleDateDayAndMonth(($Clean['ApprovalFrom'])))));
		}
		else
		{
			$NewVehicle->SetRegistrationFrom($Clean['ApprovalFrom']);
		}

		if ($Clean['ApprovalTo'] != '')
        {	
			$NewVehicle->SetApprovalTo(date('Y-m-d', strtotime(DateProcessing::ToggleDateDayAndMonth(($Clean['ApprovalTo'])))));
   		}
   		else
   		{
   			$NewVehicle->SetRegistrationFrom($Clean['ApprovalTo']);
   		}

		$NewVehicle->SetFitnessDocumentNumber($Clean['FitnessDocumentNumber']);
		$NewVehicle->SetPermitNumber($Clean['PermitNumber']);
		$NewVehicle->SetAvailableSeats($Clean['AvailableSaets']);

		$NewVehicle->SetIsDiesel($Clean['IsDiesel']);
		$NewVehicle->SetIsPetrol($Clean['IsPetrol']);
		$NewVehicle->SetIsGas($Clean['IsGas']);

	    if ($Clean['LastServicedDate'] != '')
        {	
			$NewVehicle->SetLastServicedDate(date('Y-m-d', strtotime(DateProcessing::ToggleDateDayAndMonth(($Clean['LastServicedDate'])))));
		}
		else
		{
			$NewVehicle->SetRegistrationFrom($Clean['LastServicedDate']);
		}

		if ($Clean['ServiceDueDate'] != '')
        {	
			$NewVehicle->SetServiceDueDate(date('Y-m-d', strtotime(DateProcessing::ToggleDateDayAndMonth(($Clean['ServiceDueDate'])))));
	    }
	    else
	    {
	    	$NewVehicle->SetRegistrationFrom($Clean['ServiceDueDate']);
	    }
		$NewVehicle->SetIsActive(1);
		$NewVehicle->SetCreateUserID($LoggedUser->GetUserID());

		if ($NewVehicle->RecordExists())
		{
			$NewRecordValidator->AttachTextError('The vehicle name you have added already exists.');
			$HasErrors = true;
			break;
		}
		
		if (!$NewVehicle->Save())
		{
			$NewRecordValidator->AttachTextError(ProcessErrors($NewVehicle->GetLastErrorCode()));
			$HasErrors = true;
			break;
		}
		
		header('location:vehicle_report.php?Mode=AS');
		exit;
	break;
		
}

require_once('../html_header.php');
?>
<title>Add Vehicle </title>
<link href="../vendor/jquery-ui/jquery-ui.min.css" rel="stylesheet">
</head>
<body>
	
  <div id="wrapper">
    	<!-- Navigation -->
        <nav class="navbar navbar-default navbar-static-top" role="navigation" style="margin-bottom:0">
<?php 
			require_once('../site_header.php');
			require_once('../left_navigation_menu.php');
?>                    
            <!-- /.navbar-static-side -->
        </nav>

        <div id="page-wrapper">
            <div class="row">
                <div class="col-lg-12">
                    <h1 class="page-header">Add Vehicle </h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="AddVehicleName" action="add_vehicle.php" method="post">
            	<div class="panel panel-default">
                    <div class="panel-heading">
                        <strong>Enter Vehicle Details</strong>
                    </div>
                    <div class="panel-body">
<?php
						if ($HasErrors == true)
						{
							echo $NewRecordValidator->DisplayErrors();
						}
?>   
	          	<div class="form-group">
							<label for="VehicleType" class="col-lg-2 control-label">Vehicle Type</label>
                            <div class="col-lg-8">
                            	<select class="form-control" name="drdVehicleType" id="VehicleType">
                            		<option value="0">-- Select Vehicle Type --</option>
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
                            <label for="VehicleName" class="col-lg-2 control-label">Vehicle Name</label>
                            <div class="col-lg-8">
                            	<input class="form-control" type="text" maxlength="100" id="VehicleName" name="txtVehicleName" value="<?php echo $Clean['VehicleName']; ?>" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="VehicleNumber" class="col-lg-2 control-label">Vehicle Number</label>
                            <div class="col-lg-8">
                            	<input class="form-control" type="text" maxlength="50" id="VehicleNumber" name="txtVehicleNumber" value="<?php echo $Clean['VehicleNumber']; ?>" />
                            </div>
                        </div>
                          <div class="form-group">
                          <label for="RegistrationFrom" class="col-lg-2 control-label">Registration From</label>
                            <div class="col-lg-3"> 
                            	<input class="form-control select-date" type="text" maxlength="10" id="RegistrationFrom" name="txtRegistrationFrom" value="<?php echo $Clean['RegistrationFrom']; ?>" />
                            </div>
                          <label for="RegistrationTo" class="col-lg-2 control-label">Registration To</label>
                            <div class="col-lg-3"> 
                            	<input class="form-control select-date" type="text" maxlength="10" id="RegistrationTo" name="txtRegistrationTo" value="<?php echo $Clean['RegistrationTo']; ?>" />
                            </div>
                        </div>
                         <div class="form-group">
                          <label for="InsuranceFrom" class="col-lg-2 control-label">Insurance From</label>
                            <div class="col-lg-3"> 
                            	<input class="form-control select-date" type="text" maxlength="10" id="InsuranceFrom" name="txtInsuranceFrom" value="<?php echo $Clean['InsuranceFrom']; ?>" />
                            </div>
                          <label for="InsuranceTo" class="col-lg-2 control-label">Insurance To</label>
                            <div class="col-lg-3"> 
                            	<input class="form-control select-date" type="text" maxlength="10" id="InsuranceTo" name="txtInsuranceTo" value="<?php echo $Clean['InsuranceTo']; ?>" />
                            </div>
                        </div>
                          <div class="form-group">
                          <label for="PollutionFrom" class="col-lg-2 control-label">Pollution From</label>
                            <div class="col-lg-3"> 
                            	<input class="form-control select-date" type="text" maxlength="10" id="PollutionFrom" name="txtPollutionFrom" value="<?php echo $Clean['PollutionFrom']; ?>" />
                            </div>
                          <label for="PollutionTo" class="col-lg-2 control-label">Pollution To</label>
                            <div class="col-lg-3"> 
                            	<input class="form-control select-date" type="text" maxlength="10" id="PollutionTo" name="txtPollutionTo" value="<?php echo $Clean['PollutionTo']; ?>" />
                            </div>
                        </div>
                          <div class="form-group">
                          <label for="ApprovalFrom" class="col-lg-2 control-label">Approval From</label>
                            <div class="col-lg-3"> 
                            	<input class="form-control select-date" type="text" maxlength="10" id="ApprovalFrom" name="txtApprovalFrom" value="<?php echo $Clean['ApprovalFrom']; ?>" />
                            </div>
                          <label for="ApprovalTo" class="col-lg-2 control-label">Approval To</label>
                            <div class="col-lg-3"> 
                            	<input class="form-control select-date" type="text" maxlength="10" id="ApprovalTo" name="txtApprovalTo" value="<?php echo $Clean['ApprovalTo']; ?>" />
                            </div>
                        </div>
						<div class="form-group">
                            <label for="FitnessDocumentNumber" class="col-lg-2 control-label">Fitness Document Number</label>
                            <div class="col-lg-8">
                            	<input class="form-control" type="text" maxlength="50" id="FitnessDocumentNumber" name="txtFitnessDocumentNumber" value="<?php echo $Clean['FitnessDocumentNumber']; ?>" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="PermitNumber" class="col-lg-2 control-label">Permit Number</label>
                            <div class="col-lg-8">
                            	<input class="form-control" type="text" maxlength="50" id="PermitNumber" name="txtPermitNumber" value="<?php echo $Clean['PermitNumber']; ?>" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="AvailableSaets" class="col-lg-2 control-label">Available Seats</label>
                            <div class="col-lg-3">
                            	<input class="form-control" type="text" maxlength="6" id="AvailableSaets" name="txtAvailableSaets" value="<?php echo ($Clean['AvailableSaets']) ? $Clean['AvailableSaets'] : ''; ?>" />
                            </div>
                              <label for="FuelType" class="col-lg-2 control-label">Fuel Type</label>
                            <div class="col-lg-4">	
                                <input type="checkbox" id="Diesel" name="chkDiesel"  <?php echo ($Clean['IsDiesel'] == 1) ? 'checked="checked"' : ''; ?>  value="1" />&nbsp; <label for="Diesel" style="font-weight: normal;">Diesel</label>
                                <input type="checkbox" id="Petrol" name="chkPetrol"  <?php echo ($Clean['IsPetrol'] == 1) ? 'checked="checked"' : ''; ?>  value="1" />&nbsp; <label for="Petrol" style="font-weight: normal;">Petrol</label>
                                <input type="checkbox" id="Gas" name="chkGas"  <?php echo ($Clean['IsGas'] == 1) ? 'checked="checked"' : ''; ?> value="1" />&nbsp; <label for="Gas" style="font-weight: normal;">Gas</label>
                            </div>
                        </div>
                        <div class="form-group">
                          <label for="LastServicedDate" class="col-lg-2 control-label">Last ServicedDate</label>
                            <div class="col-lg-3"> 
                            	<input class="form-control select-date" type="text" maxlength="10" id="LastServicedDate" name="txtLastServicedDate" value="<?php echo $Clean['LastServicedDate']; ?>" />
                            </div>
                          <label for="ServiceDueDate" class="col-lg-2 control-label">Service DueDate</label>
                            <div class="col-lg-3"> 
                            	<input class="form-control select-date" type="text" maxlength="10" id="ServiceDueDate" name="txtServiceDueDate" value="<?php echo $Clean['ServiceDueDate']; ?>" />
                            </div>
                        </div>
                        	<div class="form-group">
	                        <div class="col-sm-offset-2 col-lg-10">
	                        	<input type="hidden" name="hdnProcess" value="1" />
								<button type="submit" class="btn btn-primary"><i class="fa fa-save"></i>&nbsp;Save</button>
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
</html>
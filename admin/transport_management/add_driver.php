<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");
require_once("../../classes/class.date_processing.php");

require_once("../../classes/transport_management/class.driver.php");
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

if ($LoggedUser->HasPermissionForTask(TASK_ADD_DRIVERS) !== true)
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

$DriverList = array();
$DriverList = Driver::GetActiveDriver();

$Clean = array();
$Clean['Process'] = 0;

$Clean['DriverID'] = 0;

$Clean['DriverFirstName'] = '';
$Clean['DriverLastName'] = '';

$Clean['FatherName'] = '';
$Clean['DOB'] = '';

$Clean['Address'] = '';
$Clean['ContactNumber'] = '';
$Clean['Email'] = '';

$Clean['IsAdharCard'] = 0;
$Clean['AdharNumber'] = 0;

$Clean['IsPanCard'] = 0;
$Clean['PanNumber'] = '';

$Clean['IsVoterID'] = 0;
$Clean['VoterIDNumber'] = '';

$Clean['DrivingLicenceNumber'] = '';
$Clean['DrivingLicenceValidityFrom'] = '';
$Clean['DrivingLicenceValidityTo'] = '';

if (isset($_POST['hdnProcess']))
{
	$Clean['Process'] = (int) $_POST['hdnProcess'];
}
switch ($Clean['Process'])
{
	case 1:
		if (isset($_POST['txtDriverFirstName']))
		{
			$Clean['DriverFirstName'] = strip_tags(trim($_POST['txtDriverFirstName']));
		}
		if (isset($_POST['txtDriverLastName']))
		{
			$Clean['DriverLastName'] = strip_tags(trim($_POST['txtDriverLastName']));
		}
		if (isset($_POST['txtFatherName']))
		{
			$Clean['FatherName'] = strip_tags(trim($_POST['txtFatherName']));
		}
		if (isset($_POST['txtDOB']))
		{
			$Clean['DOB'] = strip_tags(trim($_POST['txtDOB']));
		}
		if (isset($_POST['txtAddress']))
		{
			$Clean['Address'] = strip_tags(trim($_POST['txtAddress']));
		}
		if (isset($_POST['txtContactNumber']))
		{
			$Clean['ContactNumber'] = strip_tags(trim($_POST['txtContactNumber']));
		}
		if (isset($_POST['txtEmail']))
		{
			$Clean['Email'] = strip_tags(trim($_POST['txtEmail']));
		}
		if (isset($_POST['chkIsAdharCard']))
        {
            $Clean['IsAdharCard'] = 1;
        }
		if (isset($_POST['txtAdharNumber']))
		{
			$Clean['AdharNumber'] = strip_tags(trim($_POST['txtAdharNumber']));
		}
		if (isset($_POST['chkIsPanCard']))
        {
            $Clean['IsPanCard'] = 1;
        }
		if (isset($_POST['txtPanNumber']))
		{
			$Clean['PanNumber'] = strip_tags(trim($_POST['txtPanNumber']));
		}
		if (isset($_POST['chkIsVoterID']))
        {
            $Clean['IsVoterID'] = 1;
        }
		if (isset($_POST['txtVoterIDNumber']))
		{
			$Clean['VoterIDNumber'] = strip_tags(trim($_POST['txtVoterIDNumber']));
		}
		if (isset($_POST['txtDrivingLicenceNumber']))
		{
			$Clean['DrivingLicenceNumber'] = strip_tags(trim($_POST['txtDrivingLicenceNumber']));
		}
		if (isset($_POST['txtDrivingLicenceValidityFrom']))
		{
			$Clean['DrivingLicenceValidityFrom'] = strip_tags(trim($_POST['txtDrivingLicenceValidityFrom']));
		}
		if (isset($_POST['txtDrivingLicenceValidityTo']))
		{
			$Clean['DrivingLicenceValidityTo'] = strip_tags(trim($_POST['txtDrivingLicenceValidityTo']));
		}	

		$NewRecordValidator = new Validator();

			$NewRecordValidator->ValidateStrings($Clean['DriverFirstName'], 'Driver first name is required and should be between 1 and 30 characters.', 1, 30);

			if ($Clean['DriverLastName'] != '') 
			{
				$NewRecordValidator->ValidateStrings($Clean['DriverLastName'], 'Driver last name is required and should be between 1 and 30 characters.', 1, 30);
			}

			$NewRecordValidator->ValidateStrings($Clean['FatherName'], 'Father name is required and should be between 1 and 50 characters.', 1, 50);

			$NewRecordValidator->ValidateDate($Clean['DOB'], 'Please enter valid dob.');
			$NewRecordValidator->ValidateStrings($Clean['Address'], 'Address is required and should be between 5 and 300 characters.', 5, 300);
			$NewRecordValidator->ValidateStrings($Clean['ContactNumber'], 'Contact number is required and should be between 1 and 15 characters.', 1, 15);
			$NewRecordValidator->ValidateStrings($Clean['Email'], 'Email is required and should be between 5 and 150 characters.', 5, 150);

			if ($Clean['IsAdharCard'] == 1) 
			{
				$NewRecordValidator->ValidateInteger($Clean['AdharNumber'], 'Please select valid Adhar number.', 1, 12);	
			}

			if($Clean['IsPanCard'] == 1)
			{
            $NewRecordValidator->ValidateStrings($Clean['PanNumber'], 'Please select a valid Pancard number.', 1, 10);
			}
			
			if ($Clean['IsVoterID'] == 1)
			{
			$NewRecordValidator->ValidateStrings($Clean['VoterIDNumber'], 'Please select a valid Voterid number', 1, 11);	
			}

		    $NewRecordValidator->ValidateStrings($Clean['DrivingLicenceNumber'], 'Driving licencenumber is required and should be between 1 and 20 characters.', 1, 20);

		    $NewRecordValidator->ValidateDate($Clean['DrivingLicenceValidityFrom'], 'Please enter valid driving licence validityfrom date.');
		    $NewRecordValidator->ValidateDate($Clean['DrivingLicenceValidityTo'], 'Please enter valid driving licence validityto date.');

		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}

		$NewDriver = new Driver();

		$NewDriver->SetDriverFirstName($Clean['DriverFirstName']);
		$NewDriver->SetDriverLastName($Clean['DriverLastName']);

		$NewDriver->SetFatherName($Clean['FatherName']);
		$NewDriver->SetDOB(date('Y-m-d', strtotime(DateProcessing::ToggleDateDayAndMonth(($Clean['DOB'])))));

		$NewDriver->SetAddress($Clean['Address']);
		$NewDriver->SetContactNumber($Clean['ContactNumber']);
		$NewDriver->SetEmail($Clean['Email']);

		$NewDriver->SetIsAdharCard($Clean['IsAdharCard']);
		$NewDriver->SetAdharNumber($Clean['AdharNumber']);

		$NewDriver->SetIsPanCard($Clean['IsPanCard']);
		$NewDriver->SetPanNumber($Clean['PanNumber']);

		$NewDriver->SetIsVoterID($Clean['IsVoterID']);
		$NewDriver->SetVoterIDNumber($Clean['VoterIDNumber']);
		$NewDriver->SetDrivingLicenceNumber($Clean['DrivingLicenceNumber']);

		$NewDriver->SetDrivingLicenceValidityFrom(date('Y-m-d', strtotime(DateProcessing::ToggleDateDayAndMonth(($Clean['DrivingLicenceValidityFrom'])))));
		$NewDriver->SetDrivingLicenceValidityTo(date('Y-m-d', strtotime(DateProcessing::ToggleDateDayAndMonth(($Clean['DrivingLicenceValidityTo'])))));

		$NewDriver->SetIsActive(1);
		$NewDriver->SetCreateUserID($LoggedUser->GetUserID());
		
		if (!$NewDriver->Save())
		{
			$NewRecordValidator->AttachTextError(ProcessErrors($NewDriver->GetLastErrorCode()));
			$HasErrors = true;
			break;
		}
		
		header('location:driver_report.php?Mode=AS');
		exit;
	break;		
}

require_once('../html_header.php');
?>
<title>Add Driver</title>
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
                    <h1 class="page-header">Add Driver</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="AddDriver" action="add_driver.php" method="post">
            	<div class="panel panel-default">
                    <div class="panel-heading">
                        <strong>Enter Driver Details</strong>
                    </div>
                    <div class="panel-body">
<?php
						if ($HasErrors == true)
						{
							echo $NewRecordValidator->DisplayErrors();
						}
?>                    
						<div class="form-group">
							<label for="DriverFirstName" class="col-lg-2 control-label">First Name</label>
                            <div class="col-lg-3">
                            	<input class="form-control" type="text" maxlength="30" id="DriverFirstName" name="txtDriverFirstName" value="<?php echo $Clean['DriverFirstName']; ?>" />
                            </div>
                            <label for="DriverLastName" class="col-lg-2 control-label">Last Name</label>
                            <div class="col-lg-3">
                            	<input class="form-control" type="text" maxlength="30" id="DriverLastName" name="txtDriverLastName" value="<?php echo $Clean['DriverLastName']; ?>" />
                            </div>
                        </div>
						<div class="form-group">
                            <label for="FatherName" class="col-lg-2 control-label">Father Name</label>
                            <div class="col-lg-8">
                            	<input class="form-control" type="text" maxlength="50" id="FatherName" name="txtFatherName" value="<?php echo $Clean['FatherName']; ?>" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="Address" class="col-lg-2 control-label">Address</label>
                            <div class="col-lg-8">
                                <textarea class="form-control"  id="Address" name="txtAddress"><?php echo $Clean['Address']; ?></textarea>
                            </div>
                        </div>
                         <div class="form-group">
                         	<label for="DOB" class="col-lg-2 control-label">DOB</label>
                            <div class="col-lg-3">
                               <input class="form-control select-date" type="text" maxlength="10" id="DOB" name="txtDOB" value="<?php echo $Clean['DOB']; ?>" />
                            </div>
                            <label for="ContactNumber" class="col-lg-2 control-label">Contact Number</label>
                            <div class="col-lg-3">
                            	<input class="form-control" type="text" maxlength="15" id="ContactNumber" name="txtContactNumber" value="<?php echo $Clean['ContactNumber']; ?>" />
                            </div>
                        </div>
                         <div class="form-group">
                           <label for="Email" class="col-lg-2 control-label">Email</label>
                            <div class="col-lg-8">
                            	<input class="form-control" type="email" maxlength="150" id="Email" name="txtEmail" value="<?php echo $Clean['Email']; ?>" />
                            </div> 
                        </div>
                        <div class="form-group">
                            <label for="IsAdharCard" class="col-lg-2 control-label">Is AdharCard</label>
                            <div class="col-lg-3">
                                <input type="checkbox" id="IsAdharCard" name="chkIsAdharCard" <?php echo ($Clean['IsAdharCard'] == 1) ? 'checked="checked"' : ''; ?>  value="1" />&nbsp; <label for="IsAdharCard" style="font-weight: normal;">Yes</label>
                            </div>
                            <label for="AdharNumber" class="col-lg-2 control-label">Adhar Number</label>
                            <div class="col-lg-3">
                            	<input class="form-control" type="text" maxlength="12" id="AdharNumber" name="txtAdharNumber" value="<?php echo ($Clean['AdharNumber']) ? $Clean['AdharNumber'] : ''; ?>" / disabled>
                            </div>
                        </div>	
                         <div class="form-group">
                            <label for="IsPanCard" class="col-lg-2 control-label">Is PanCard</label>
                            <div class="col-lg-3">
                                <input type="checkbox" id="IsPanCard" name="chkIsPanCard" <?php echo ($Clean['IsPanCard'] == 1) ? 'checked="checked"' : ''; ?> value="1" />&nbsp; <label for="IsPanCard" style="font-weight: normal;">Yes</label>
                            </div>
                            <label for="PanNumber" class="col-lg-2 control-label">Pan Number</label>
                            <div class="col-lg-3">
                            	<input class="form-control" type="text" maxlength="10" id="PanNumber" name="txtPanNumber" value="<?php echo $Clean['PanNumber']; ?>" / disabled>
                            </div>
                        </div>
                         <div class="form-group">
                            <label for="IsVoterID" class="col-lg-2 control-label">Is VoterID</label>
                            <div class="col-lg-3">
                                <input type="checkbox" id="IsVoterID" name="chkIsVoterID" <?php echo ($Clean['IsVoterID'] == 1) ? 'checked="checked"' : ''; ?>  value="1" />&nbsp; <label for="IsVoterID" style="font-weight: normal;">Yes</label>
                            </div>
                            <label for="VoterIDNumber" class="col-lg-2 control-label">Voter IDNumber</label>
                            <div class="col-lg-3">
                            	<input class="form-control" type="text" maxlength="11" id="VoterIDNumber" name="txtVoterIDNumber" value="<?php echo $Clean['VoterIDNumber']; ?>" / disabled>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="DrivingLicenceNumber" class="col-lg-2 control-label">DL Number</label>
                            <div class="col-lg-8">
                            	<input class="form-control" type="text" maxlength="20" id="DrivingLicenceNumber" name="txtDrivingLicenceNumber" value="<?php echo $Clean['DrivingLicenceNumber']; ?>"/>
                            </div>
                        </div>
                        <div class="form-group">
                         <label for="DrivingLicenceValidityFrom" class="col-lg-2 control-label">DL ValidityFrom</label>
                            <div class="col-lg-3"> 
                            	<input class="form-control select-date" type="text" maxlength="10" id="DrivingLicenceValidityFrom" name="txtDrivingLicenceValidityFrom" value="<?php echo $Clean['DrivingLicenceValidityFrom']; ?>" />
                            </div>
                           <label for="DrivingLicenceValidityTo" class="col-lg-2 control-label">DL ValidityTo</label>
                            <div class="col-lg-3"> 
                            	<input class="form-control select-date" type="text" maxlength="10" id="DrivingLicenceValidityTo" name="txtDrivingLicenceValidityTo" value="<?php echo $Clean['DrivingLicenceValidityTo']; ?>" />
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

	$('#IsAdharCard').change(function(){
		if ($(this).is(":checked")) 
		{
			$('#AdharNumber').prop('disabled', false);
		}
		else
		{
			$('#AdharNumber').prop('disabled', true);
			$('#AdharNumber').val('');
		}
	});

	$('#IsPanCard').change(function(){
		if ($(this).is(":checked")) 
		{
			$('#PanNumber').prop('disabled', false);
		}
		else
		{
			$('#PanNumber').prop('disabled', true);
			$('#PanNumber').val('');
		}
	});

	$('#IsVoterID').change(function(){
		if ($(this).is(":checked")) 
		{
			$('#VoterIDNumber').prop('disabled', false);
		}
		else
		{
			$('#VoterIDNumber').prop('disabled', true);
			$('#VoterIDNumber').val('');
		}
	});
	
	$(".chkcc9").on('click', function() {
            $(this)
            .parents('table')
            .find('.group1') 
            .prop('checked', $(this).is(':checked')); 
});
});
</script>

</body>
</html>
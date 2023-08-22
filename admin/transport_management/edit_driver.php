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

if ($LoggedUser->HasPermissionForTask(TASK_EDIT_DRIVERS) !== true)
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
    header('location:driver_report.php');
    exit;
}

$Clean = array();

$Clean['DriverID'] = 0;

if (isset($_GET['DriverID']))
{
    $Clean['DriverID'] = (int) $_GET['DriverID'];
}
else if (isset($_POST['hdnDriverID']))
{
    $Clean['DriverID'] = (int) $_POST['hdnDriverID'];
}

if ($Clean['DriverID'] <= 0)
{
    header('location:../error.php');
    exit;
} 
try
{
    $DriverToEdit = new Driver($Clean['DriverID']);
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

$DriverList =  array();
$DriverList = Driver::GetActiveDriver();

$HasErrors = false;

$Clean['Process'] = 0;
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
        if (isset($_POST['txtDOB']))
        {
            $Clean['DOB'] = strip_tags(trim($_POST['txtDOB']));
        }
        if (isset($_POST['txtIsAdharCard']))
        {
            $Clean['IsAdharCard'] = strip_tags(trim($_POST['txtIsAdharCard']));
        }
        if (isset($_POST['txtAdharNumber']))
        {
            $Clean['AdharNumber'] = strip_tags(trim($_POST['txtAdharNumber']));
        }
        if (isset($_POST['txtIsPanCard']))
        {
            $Clean['IsPanCard'] = strip_tags(trim($_POST['txtIsPanCard']));
        }
            if (isset($_POST['txtPanNumber']))
        {
            $Clean['PanNumber'] = strip_tags(trim($_POST['txtPanNumber']));
        }
        if (isset($_POST['txtIsVoterID']))
        {
            $Clean['IsVoterID'] = strip_tags(trim($_POST['txtIsVoterID']));
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
        if (isset($_POST['chkIsActive']))
        {
            $Clean['IsActive'] = 1;
        }

        $NewRecordValidator = new Validator();
        $NewRecordValidator->ValidateStrings($Clean['DriverFirstName'], 'Driver firstName is required and should be between 1 and 150 characters.', 1, 30);
        $NewRecordValidator->ValidateStrings($Clean['DriverLastName'], 'Driver lastName is required and should be between 1 and 150 characters.', 1, 30);

        $NewRecordValidator->ValidateStrings($Clean['FatherName'], 'father name is required and should be between 1 and 150 characters.', 1, 50);

        $NewRecordValidator->ValidateStrings($Clean['Address'], 'Please enter valid Address.');
        $NewRecordValidator->ValidateStrings($Clean['ContactNumber'], 'Contact number is required and should be between 1 and 15 characters.', 1, 15);
		$NewRecordValidator->ValidateStrings($Clean['Email'], 'Email is required and should be between 5 and 150 characters.', 5, 150);
        $NewRecordValidator->ValidateDate($Clean['DOB'], 'Please enter valid dob date.');

        if ($Clean['IsAdharCard'] == 1) 
            {
                $NewRecordValidator->ValidateInteger($Clean['AdharNumber'], 'Please select valid Adhar number.', 1);    
            }

        if ($Clean['IsPanCard'] == 1) 
        {
            $NewRecordValidator->ValidateStrings($Clean['PanNumber'], 'Pan number is required and should be between 1 and 10 characters.', 1, 10);  
        }

        if ($Clean['IsVoterID'] == 1) 
        {
            $NewRecordValidator->ValidateStrings($Clean['VoterIDNumber'], 'Voterid number is required and should be between 1 and 50 characters.', 1, 11);  
        }

        $NewRecordValidator->ValidateStrings($Clean['DrivingLicenceNumber'], 'Driving licence number is required and should be between 1 and 20 characters.', 1, 20);
        $NewRecordValidator->ValidateDate($Clean['DrivingLicenceValidityFrom'], 'Please enter valid Dl Validityfrom date');

        $NewRecordValidator->ValidateDate($Clean['DrivingLicenceValidityTo'], ' Please enter valid Dl Validityto date');

        if ($NewRecordValidator->HasNotifications())
        {
            $HasErrors = true;
            break;
        }

        $DriverToEdit->SetDriverFirstName($Clean['DriverFirstName']);
        $DriverToEdit->SetDriverLastName($Clean['DriverLastName']);

        $DriverToEdit->SetFatherName($Clean['FatherName']);
        $DriverToEdit->SetAddress($Clean['Address']);
        $DriverToEdit->SetContactNumber($Clean['ContactNumber']);
        $DriverToEdit->SetEmail($Clean['Email']);
        $DriverToEdit->SetDob(date('Y-m-d', strtotime(DateProcessing::ToggleDateDayAndMonth(($Clean['DOB'])))));

        $DriverToEdit->SetIsAdharCard($Clean['IsAdharCard']);
        $DriverToEdit->SetAdharNumber($Clean['AdharNumber']);

        $DriverToEdit->SetIsPanCard($Clean['IsPanCard']);
        $DriverToEdit->SetPanNumber($Clean['PanNumber']);

        $DriverToEdit->SetIsVoterID($Clean['IsVoterID']);
        $DriverToEdit->SetVoterIDNumber($Clean['VoterIDNumber']);

        $DriverToEdit->SetDrivingLicenceNumber($Clean['DrivingLicenceNumber']);
        $DriverToEdit->SetDrivingLicenceValidityFrom(date('Y-m-d', strtotime(DateProcessing::ToggleDateDayAndMonth(($Clean['DrivingLicenceValidityFrom'])))));
        $DriverToEdit->SetDrivingLicenceValidityTo(date('Y-m-d', strtotime(DateProcessing::ToggleDateDayAndMonth(($Clean['DrivingLicenceValidityTo'])))));

        $DriverToEdit->SetIsActive($Clean['IsActive']);

        if (!$DriverToEdit->Save())
        {
            $NewRecordValidator->AttachTextError(ProcessErrors($DriverToEdit->GetLastErrorCode()));
            $HasErrors = true;

            break;
        }
        
        header('location:driver_report.php?Mode=UD');
        exit;
    break;

    case 2:
        $Clean['DriverFirstName'] = $DriverToEdit->GetDriverFirstName();
        $Clean['DriverLastName'] = $DriverToEdit->GetDriverLastName();

        $Clean['FatherName'] = $DriverToEdit->GetFatherName();
        $Clean['Address'] = $DriverToEdit->GetAddress();
        $Clean['DOB'] = date('d/m/Y', strtotime($DriverToEdit->GetDOB()));

        $Clean['Address'] = $DriverToEdit->GetAddress();
        $Clean['ContactNumber'] = $DriverToEdit->GetContactNumber();
        $Clean['Email'] = $DriverToEdit->GetEmail();

        $Clean['IsAdharCard'] = $DriverToEdit->GetIsAdharCard();
        $Clean['AdharNumber'] = $DriverToEdit->GetAdharNumber();

        $Clean['IsPanCard'] = $DriverToEdit->GetIsPanCard();
        $Clean['PanNumber'] = $DriverToEdit->GetPanNumber();

        $Clean['IsVoterID'] = $DriverToEdit->GetIsVoterID();
        $Clean['VoterIDNumber'] = $DriverToEdit->GetVoterIDNumber();

        $Clean['DrivingLicenceNumber'] = $DriverToEdit->GetDrivingLicenceNumber();
        $Clean['DrivingLicenceValidityFrom'] = date('d/m/Y', strtotime($DriverToEdit->GetDrivingLicenceValidityFrom()));
        $Clean['DrivingLicenceValidityTo'] = date('d/m/Y', strtotime($DriverToEdit->GetDrivingLicenceValidityTo()));

        $Clean['IsActive'] = $DriverToEdit->GetIsActive();

    break;
}

require_once('../html_header.php');
?>
<title>Edit Driver</title>
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
                    <h1 class="page-header">Edit Driver</h1>    
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="EditDriver" action="edit_driver.php" method="post">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        Enter Driver Details
                    </div>
                    <div class="panel-body">
<?php
                        if ($HasErrors == true)
                        {
                            echo $NewRecordValidator->DisplayErrors();
                        }
?>                    
                       <div class="form-group">
                            <label for="DriverFirstName" class="col-lg-2 control-label"> Driver First Name</label>
                            <div class="col-lg-3">
                                <input class="form-control" type="text" maxlength="30" id="DriverFirstName" name="txtDriverFirstName" value="<?php echo $Clean['DriverFirstName']; ?>" />
                            </div>
                            <label for="DriverLastName" class="col-lg-2 control-label">Driver Last Name</label>
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
                            <label for="IsActive" class="col-lg-2 control-label">Is Active</label>
                            <div class="col-lg-4">
                                <input type="checkbox" id="IsActive" name="chkIsActive" <?php echo ($Clean['IsActive'] == 1) ? 'checked="checked"' : ''; ?> value="1" />
                            </div>
                        </div>
                        <div class="form-group">
                        <div class="col-sm-offset-2 col-lg-10">
                            <input type="hidden" name="hdnProcess" value="3"/>
                            <input type="hidden" name="hdnDriverID" value="<?php echo $Clean['DriverID']; ?>" />
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
</html>_
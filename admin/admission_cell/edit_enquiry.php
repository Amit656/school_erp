<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once("../../classes/school_administration/class.academic_years.php");
require_once("../../classes/school_administration/class.classes.php");
require_once("../../classes/school_administration/class.countries.php");
require_once("../../classes/school_administration/class.states.php");
require_once("../../classes/school_administration/class.cities.php");
require_once("../../classes/admission_cell/class.exams.php");
require_once("../../classes/admission_cell/class.exam_registrations.php");
require_once("../../classes/admission_cell/class.enquiries.php");

require_once("../../classes/class.date_processing.php");

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
	header('location:unauthorized_login_admin.php');
	exit;
}
catch (Exception $e)
{
	header('location:unauthorized_login_admin.php');
	exit;
}
// END OF 1. //

if ($LoggedUser->HasPermissionForTask(TASK_EDIT_ENQUIRY) !== true)
{
    header('location:/admin/unauthorized_login_admin.php');
    exit;
}

$Clean = array();

$Clean['EnquiryID'] = 0;

if (isset($_GET['EnquiryID']))
{
    $Clean['EnquiryID'] = (int) $_GET['EnquiryID'];
}
elseif (isset($_POST['hdnEnquiryID']))
{
    $Clean['EnquiryID'] = (int) $_POST['hdnEnquiryID'];
}

if ($Clean['EnquiryID'] <= 0)
{
    header('location:../error.php');
    exit;
}   

try
{
    $EnquiryToEdit = new Enquiry($Clean['EnquiryID']);
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

$GenderList = array('Male' => 'Male', 'Female' => 'Female', 'Others' => 'Others');
$LastClassStatusList = array('Passed' => 'Passed', 'Failed' => 'Failed', 'Awaited' => 'Awaited');
$SourceOfInformationList = array('Website' => 'Website', 'SocialMedia' => 'SocialMedia', 'Hording' => 'Hording', 'Reference' => 'Reference');

$AcademicYears =  array();
$AcademicYears = AcademicYear::GetAllAcademicYears();

$ClassList =  array();
$ClassList = AddedClass::GetActiveClasses();

$CountryList =  array();
$CountryList = Country::GetAllCountries();

$AllExamsList = array();
$AllExamsList = Exam::GetActiveExams();

$AllExams = array();
$AllExams = Exam::GetAllExams();

$StateList =  array();
$CityList =  array();

$HasErrors = false;

$Clean['Process'] = 0;
$Clean['AcademicYearID'] = 0;
$Clean['ClassID'] = 0;
$Clean['ClassID'] = key($ClassList);

$Clean['FirstName'] = '';
$Clean['LastName'] = '';
$Clean['DOB'] = '';
$Clean['Gender'] = 'Male';

$Clean['FatherName'] = '';
$Clean['MotherName'] = '';
$Clean['MobileNumber'] = '';
$Clean['Address'] = '';

$Clean['CountryID'] = key($CountryList);
$StateList = State::GetAllStates($Clean['CountryID']);

$Clean['StateID'] = key($StateList);

$DistrictList = City::GetAllDistricts($Clean['StateID']);
$Clean['DistrictID'] = key($DistrictList);

$CityList = City::GetAllCities($Clean['StateID'], $Clean['DistrictID']);
$Clean['CityID'] = key($CityList);

$Clean['CountryID'] = 0;
$Clean['PinCode'] = '';

$Clean['LastSchool'] = '';
$Clean['LastClass'] = '';
$Clean['LastClassStatus'] = 'Passed';
$Clean['SourceOfInformation'] = 'Website';
$Clean['Description'] = '';

$Clean['IsActive'] = 1;

$Clean['FormFee'] = 0.00;

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
	    if (isset($_POST['drdAcademicYear']))
        {
            $Clean['AcademicYearID'] = (int) $_POST['drdAcademicYear'];
        }
        
		if (isset($_POST['drdClass']))
        {
            $Clean['ClassID'] = (int) $_POST['drdClass'];
        }

        if (isset($_POST['txtFirstName']))
        {
            $Clean['FirstName'] = strip_tags(trim($_POST['txtFirstName']));
        }

        if (isset($_POST['txtLastName']))
        {
            $Clean['LastName'] = strip_tags(trim($_POST['txtLastName']));
        }

        if (isset($_POST['txtDOB']))
        {
            $Clean['DOB'] = strip_tags(trim($_POST['txtDOB']));
        }

        if (isset($_POST['rdbGender']))
        {
            $Clean['Gender'] = strip_tags(trim($_POST['rdbGender']));
        }

        if (isset($_POST['txtFatherName']))
        {
            $Clean['FatherName'] = strip_tags(trim($_POST['txtFatherName']));
        }

        if (isset($_POST['txtMotherName']))
        {
            $Clean['MotherName'] = strip_tags(trim($_POST['txtMotherName']));
        }

        if (isset($_POST['txtMobileNumber']))
        {
            $Clean['MobileNumber'] = strip_tags(trim($_POST['txtMobileNumber']));
        }

        if (isset($_POST['txtAddress']))
        {
            $Clean['Address'] = strip_tags(trim($_POST['txtAddress']));
        }

        if (isset($_POST['drdCity']))
        {
            $Clean['CityID'] = (int) $_POST['drdCity'];
        }

        if (isset($_POST['drdDistrict']))
        {
            $Clean['DistrictID'] = (int) $_POST['drdDistrict'];
        }

        if (isset($_POST['drdState']))
        {
            $Clean['StateID'] = (int) $_POST['drdState'];
        }

        if (isset($_POST['drdCountry']))
        {
            $Clean['CountryID'] = (int) $_POST['drdCountry'];
        }

        if (isset($_POST['txtPinCode']))
        {
            $Clean['PinCode'] = strip_tags(trim($_POST['txtPinCode']));
        }

        if (isset($_POST['txtLastSchool']))
        {
            $Clean['LastSchool'] = strip_tags(trim($_POST['txtLastSchool']));
        }

        if (isset($_POST['txtLastClass']))
        {
            $Clean['LastClass'] = strip_tags(trim($_POST['txtLastClass']));
        }

        if (isset($_POST['drdLastClassStatus']))
        {
            $Clean['LastClassStatus'] = strip_tags(trim($_POST['drdLastClassStatus']));
        }

        if (isset($_POST['drdSourceOfInformation']))
        {
            $Clean['SourceOfInformation'] = strip_tags(trim($_POST['drdSourceOfInformation']));
        }

        if (isset($_POST['txtDescription']))
        {
            $Clean['Description'] = strip_tags(trim($_POST['txtDescription']));
        }

        if (isset($_POST['drdSourceOfInformation']))
        {
            $Clean['SourceOfInformation'] = strip_tags(trim($_POST['drdSourceOfInformation']));
        }

        if (isset($_POST['txtDescription']))
        {
            $Clean['Description'] = strip_tags(trim($_POST['txtDescription']));
        }

        if (isset($_POST['txtFormFee']))
        {
            $Clean['FormFee'] = strip_tags(trim($_POST['txtFormFee']));
        }

        if (isset($_POST['chkIsActive']))
        {
            $Clean['IsActive'] = 1;
        }

		$NewRecordValidator = new Validator();
		$NewRecordValidator->ValidateInSelect($Clean['AcademicYearID'], $AcademicYears, 'Unknown error 107, please try again.');
		
		$NewRecordValidator->ValidateInSelect($Clean['ClassID'], $ClassList, 'Unknown error 108, please try again.');
		$NewRecordValidator->ValidateStrings($Clean['FirstName'], 'First name is required and should be between 2 and 20 characters.', 2, 20);
		$NewRecordValidator->ValidateStrings($Clean['LastName'], 'Last name is required and should be between 2 and 30 characters.', 2, 30);
		$NewRecordValidator->ValidateDate($Clean['DOB'], 'Please enter a valid date of birth.');
		$NewRecordValidator->ValidateInSelect($Clean['Gender'], $GenderList, 'Unknown error 109, please try again.');

		if ($Clean['FatherName'] != '') 
        {
            $NewRecordValidator->ValidateStrings($Clean['FatherName'], 'Father name is required and should be between 2 and 50 characters.', 2, 50);
        }

        if ($Clean['MotherName'] != '') 
        {
            $NewRecordValidator->ValidateStrings($Clean['MotherName'], 'Mother Name is required and should be between 2 and 50 characters.', 2, 50);
        }

      //  $NewRecordValidator->ValidateStrings($Clean['MobileNumber'], 'Mobile number is required and should be between 10 and 15 characters.', 10, 15);

        if ($Clean['Address'] != '') 
        {
            $NewRecordValidator->ValidateStrings($Clean['Address'], 'Address is required and should be between 5 and 150 characters.', 5, 150);
        }

        if ($NewRecordValidator->ValidateInSelect($Clean['CountryID'], $CountryList, 'Unknown error, please try again.'))
        {
            $StateList = State::GetAllStates($Clean['CountryID']);
            
            if ($NewRecordValidator->ValidateInSelect($Clean['StateID'], $StateList, 'Unknown error, please try again.'))
            {
                $DistrictList = City::GetAllDistricts($Clean['StateID']);
                
                if ($NewRecordValidator->ValidateInSelect($Clean['DistrictID'], $DistrictList, 'Unknown error, please try again.'))
                {
                    $CityList = City::GetAllCities($Clean['StateID'], $Clean['DistrictID']);
                    $NewRecordValidator->ValidateInSelect($Clean['CityID'], $CityList, 'Unknown error, please try again.');   
                }
            }
        }

        if ($Clean['PinCode'] != '') 
        {
            $NewRecordValidator->ValidateStrings($Clean['PinCode'], 'PinCode is required and should be between 3 and 6 characters.', 3, 6);
        }

        if ($Clean['LastSchool'] != '') 
        {
        	$NewRecordValidator->ValidateStrings($Clean['LastSchool'], 'Last School is required and should be between 1 and 50 characters.', 1, 50);
        }

        if ($Clean['LastClass'] != '') 
        {
            $NewRecordValidator->ValidateStrings($Clean['LastClass'], 'PinCode is required and should be between 1 and 20 characters.', 1, 20);
        }

        if ($Clean['LastClassStatus'] != '') 
        {
        	$NewRecordValidator->ValidateInSelect($Clean['LastClassStatus'], $LastClassStatusList, 'Unknown error 105, please try again.');
        }

        if ($Clean['SourceOfInformation'] != '') 
        {
        	$NewRecordValidator->ValidateInSelect($Clean['SourceOfInformation'], $SourceOfInformationList, 'Unknown error 106, please try again.');
        }

        if ($Clean['Description'] != '') 
        {
        	$NewRecordValidator->ValidateStrings($Clean['Description'], 'Description is required and should be between 5 and 250 characters.', 5, 250);
        }

        if ($Clean['FormFee'] != '') 
        {
            $NewRecordValidator->ValidateNumeric($Clean['FormFee'], 'Form fee should be numeric.');
        }

		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}
				
		$EnquiryToEdit->SetClassID($Clean['ClassID']);
		$EnquiryToEdit->SetFirstName($Clean['FirstName']);
		$EnquiryToEdit->SetLastName($Clean['LastName']);
		$EnquiryToEdit->SetDOB(date('Y-m-d', strtotime(DateProcessing::ToggleDateDayAndMonth(($Clean['DOB'])))));
		$EnquiryToEdit->SetGender($Clean['Gender']);
		$EnquiryToEdit->SetFatherName($Clean['FatherName']);
		$EnquiryToEdit->SetMotherName($Clean['MotherName']);
		$EnquiryToEdit->SetMobileNumber($Clean['MobileNumber']);
		$EnquiryToEdit->SetAddress($Clean['Address']);
		$EnquiryToEdit->SetCityID($Clean['CityID']);
		$EnquiryToEdit->SetDistrictID($Clean['DistrictID']);
		$EnquiryToEdit->SetStateID($Clean['StateID']);
		$EnquiryToEdit->SetCountryID($Clean['CountryID']);
		$EnquiryToEdit->SetPinCode($Clean['PinCode']);
		$EnquiryToEdit->SetLastSchool($Clean['LastSchool']);
		$EnquiryToEdit->SetLastClass($Clean['LastClass']);
		$EnquiryToEdit->SetLastClassStatus($Clean['LastClassStatus']);
		$EnquiryToEdit->SetSourceOfInformation($Clean['SourceOfInformation']);
        $EnquiryToEdit->SetDescription($Clean['Description']);
		$EnquiryToEdit->SetFormFee($Clean['FormFee']);
		$EnquiryToEdit->SetAcademicYearID($Clean['AcademicYearID']);
		$EnquiryToEdit->SetIsActive($Clean['IsActive']);
		
		$EnquiryToEdit->SetCreateUserID($LoggedUser->GetUserID());

        // if ($EnquiryToEdit->RecordExists())
        // {
        //     $NewRecordValidator->AttachTextError('Student you have added already exists');
        //     $HasErrors = true;
        //     break;
        // }

		if (!$EnquiryToEdit->Save())
		{
			$NewRecordValidator->AttachTextError(ProcessErrors($EnquiryToEdit->GetLastErrorCode()));
			$HasErrors = true;
			break;
		}
		
		header('location:enquiry_list.php?Process=7&IsActive=1&Mode=ED');
		exit;
		break;

    case 2:
        $Clean['ClassID'] = $EnquiryToEdit->GetClassID();
        $Clean['FirstName'] = $EnquiryToEdit->GetFirstName();
        $Clean['LastName'] = $EnquiryToEdit->GetLastName();
        $Clean['DOB'] = $EnquiryToEdit->GetDOB();
        $Clean['Gender'] = $EnquiryToEdit->GetGender();
        $Clean['FatherName'] = $EnquiryToEdit->GetFatherName();
        $Clean['MotherName'] = $EnquiryToEdit->GetMotherName();
        $Clean['MobileNumber'] = $EnquiryToEdit->GetMobileNumber();
        $Clean['Address'] = $EnquiryToEdit->GetAddress();
        $Clean['CityID'] = $EnquiryToEdit->GetCityID();
        $Clean['DistrictID'] = $EnquiryToEdit->GetDistrictID();
        $Clean['StateID'] = $EnquiryToEdit->GetStateID();
        $Clean['CountryID'] = $EnquiryToEdit->GetCountryID();
        
        $StateList = State::GetAllStates($Clean['CountryID']);
        $DistrictList = City::GetAllDistricts($Clean['StateID']);
        $CityList = City::GetAllCities($Clean['StateID'], $Clean['DistrictID']);
        
        $Clean['PinCode'] = $EnquiryToEdit->GetPinCode();
        $Clean['LastSchool'] = $EnquiryToEdit->GetLastSchool();
        $Clean['LastClass'] = $EnquiryToEdit->GetLastClass();
        $Clean['LastClassStatus'] = $EnquiryToEdit->GetLastClassStatus();
        $Clean['SourceOfInformation'] = $EnquiryToEdit->GetSourceOfInformation();
        $Clean['Description'] = $EnquiryToEdit->GetDescription();
        $Clean['FormFee'] = $EnquiryToEdit->GetFormFee();
        $Clean['AcademicYearID'] = $EnquiryToEdit->GetAcademicYearID();
        $Clean['IsActive'] = $EnquiryToEdit->GetIsActive();

        break;
}

require_once('../html_header.php');
?>
<title>Edit Enquiry</title>
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
                    <h1 class="page-header">Edit Enquiry</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="EditEnquiry" action="edit_enquiry.php" method="post">
            	<div class="panel panel-default">
                    <div class="panel-heading">
                        Enter Student Details
                    </div>
                    <div class="panel-body">
<?php
						if ($HasErrors == true)
						{
							echo $NewRecordValidator->DisplayErrors();
						}
?>                    
                        <div class="form-group">
                            <label for="AcademicYearID" class="col-lg-2 control-label">Academic Session</label>
                            <div class="col-lg-4">
                            	<select class="form-control" name="drdAcademicYear" id="AcademicYearID">
<?php
                                if (is_array($AcademicYears) && count($AcademicYears) > 0)
                                {
                                    foreach ($AcademicYears as $AcademicYearID => $AcademicYearDetails)
                                    {
                                        echo '<option ' . ($Clean['AcademicYearID'] == $AcademicYearID ? 'selected="selected"' : '') . ' value="' . $AcademicYearID . '">' . date('Y', strtotime($AcademicYearDetails['StartDate'])) .' - '. date('Y', strtotime($AcademicYearDetails['EndDate'])) . '</option>';
                                    }
                                }
?>
								</select>
                            </div>
                        </div>
                        
                    	<div class="form-group">
                            <label for="ClassID" class="col-lg-2 control-label">Class</label>
                            <div class="col-lg-4">
                            	<select class="form-control" name="drdClass" id="ClassID">
<?php
                                if (is_array($ClassList) && count($ClassList) > 0)
                                {
                                    foreach ($ClassList as $ClassID => $ClassName)
                                    {
                                        echo '<option ' . ($Clean['ClassID'] == $ClassID ? 'selected="selected"' : '') . ' value="' . $ClassID . '">' . $ClassName . '</option>';
                                    }
                                }
?>
								</select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="FirstName" class="col-lg-2 control-label">First Name</label>
                            <div class="col-lg-4">
                            	<input class="form-control" type="text" maxlength="20" id="FirstName" name="txtFirstName" value="<?php echo $Clean['FirstName']; ?>" />
                            </div>
                            <label for="LastName" class="col-lg-2 control-label">Last Name</label>
                            <div class="col-lg-4">
                            	<input class="form-control" type="text" maxlength="30" id="LastName" name="txtLastName" value="<?php echo $Clean['LastName']; ?>" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="DOB" class="col-lg-2 control-label">Date Of Birth</label>
                            <div class="col-lg-4">
                            	<input class="form-control select-date dtepicker" type="text" maxlength="10" id="DOB" name="txtDOB" value="<?php echo date('d/m/Y', strtotime($Clean['DOB'])); ?>" />
                            </div>
                            <label for="Age" class="col-lg-2 control-label">Age</label>
                            
                            <label for="Year" class="col-lg-1 control-label"><input class="form-control" type="text" maxlength="3" id="Year" name="txtYear" value="" readonly />&nbsp;Year</label>

                            <label for="Month" class="col-lg-1 control-label"><input class="form-control" type="text" maxlength="3" id="Month" name="txtMonth" value="" readonly />&nbsp;Month</label>
                            
                            <label for="Day" class="col-sm-1 control-label"><input class="form-control" type="text" maxlength="3" id="Day" name="txtDay" value="" readonly/>&nbsp;Day</label>
                        </div>
                        <div class="form-group">
                            <label for="Gender" class="col-lg-2 control-label">Gender</label>
                            <div class="col-sm-4">
<?php
                            foreach($GenderList as $GenderID => $GenderName)
                            {
?>                              
                                <label style="font-weight: normal;"><input class="custom-radio" type="radio" id="<?php echo $GenderID; ?>" name="rdbGender" value="<?php echo $GenderID; ?>" <?php echo ($Clean['Gender'] == $GenderID ? 'checked="checked"' : ''); ?> >&nbsp;<?php echo $GenderName; ?></label>&nbsp;
<?php                                       
                            }
?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="panel panel-default">
                    <div class="panel-heading">
                        Enter Parent Details
                    </div>
                    <div class="panel-body">
                    	<div class="form-group">
                            <label for="FatherName" class="col-lg-2 control-label">Father's Name</label>
                            <div class="col-lg-4">
                                <input class="form-control" type="text" maxlength="50" id="FatherName" name="txtFatherName" value="<?php echo $Clean['FatherName']; ?>" />
                            </div>
                            <label for="MotherName" class="col-lg-2 control-label">Mother's Name</label>
                            <div class="col-lg-4">
                                <input class="form-control" type="text" maxlength="50" id="MotherName" name="txtMotherName" value="<?php echo $Clean['MotherName']; ?>" />
                            </div>
                        </div>
                        <div class="form-group">
                        	<label for="MobileNumber" class="col-lg-2 control-label">Mobile Number</label>
                            <div class="col-lg-4">
                                <input class="form-control" type="text" maxlength="15" id="MobileNumber" name="txtMobileNumber" value="<?php echo $Clean['MobileNumber']; ?>" />
                            </div>
                            <label for="Address" class="col-lg-2 control-label">Address</label>
                            <div class="col-lg-4">
                                <textarea class="form-control" id="Address" name="txtAddress"><?php echo $Clean['Address']; ?></textarea>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="Country" class="col-lg-2 control-label">Country</label>
                            <div class="col-lg-4">
                                <select class="form-control" name="drdCountry" id="Country">
<?php
                                if (is_array($CountryList) && count($CountryList) > 0)
                                {
                                    foreach ($CountryList as $CountryID => $CountryName) 
                                    {
                                        echo '<option ' . ($Clean['CountryID'] == $CountryID ? 'selected="selected"' : '') . ' value="' . $CountryID . '">' . $CountryName . '</option>';
                                    }
                                }
?>
                                </select>
                            </div>
                            <label for="State" class="col-lg-2 control-label">State</label>
                            <div class="col-lg-4">
                                <select class="form-control" name="drdState" id="State">
<?php
                                if (is_array($StateList) && count($StateList) > 0)
                                {
                                    foreach ($StateList as $StateID => $StateName) 
                                    {
                                        echo '<option ' . ($Clean['StateID'] == $StateID ? 'selected="selected"' : '') . ' value="' . $StateID . '">' . $StateName . '</option>';
                                    }
                                }
?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="District" class="col-lg-2 control-label">District</label>
                            <div class="col-lg-4">
                                <select class="form-control" name="drdDistrict" id="District">
<?php
                                if (is_array($DistrictList) && count($DistrictList) > 0)
                                {
                                    foreach ($DistrictList as $DistrictID => $DistrictName) {
                                        echo '<option ' . ($Clean['DistrictID'] == $DistrictID ? 'selected="selected"' : '') . ' value="' . $DistrictID . '">' . $DistrictName . '</option>';
                                    }
                                }
?>
                                </select>
                            </div>
                            <label for="City" class="col-lg-2 control-label">City</label>
                            <div class="col-lg-4">
                                <select class="form-control" name="drdCity" id="City">
<?php
                                if (is_array($CityList) && count($CityList) > 0)
                                {
                                    foreach ($CityList as $CityID => $CityName) 
                                    {
                                        echo '<option ' . ($Clean['CityID'] == $CityID ? 'selected="selected"' : '') . ' value="' . $CityID . '">' . $CityName . '</option>';
                                    }
                                }
?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="PinCode" class="col-lg-2 control-label">PIN Code</label>
                            <div class="col-lg-4">
                                <input class="form-control" type="text" maxlength="6" id="PinCode" name="txtPinCode" value="<?php echo $Clean['PinCode']; ?>" />
                            </div>
                        </div>
                    </div>
                </div>
                <div class="panel panel-default">
                    <div class="panel-heading">
                        Enter Other Details
                    </div>
                    <div class="panel-body">
                    	<div class="form-group">
                            <label for="LastSchool" class="col-lg-2 control-label">Last School</label>
                            <div class="col-lg-4">
                                <textarea class="form-control" id="LastSchool" maxlength="50" name="txtLastSchool"><?php echo $Clean['LastSchool']; ?></textarea>
                            </div>
                            <label for="LastClass" class="col-lg-2 control-label">Last Class</label>
                            <div class="col-lg-4">
                                <input class="form-control" type="text" id="LastClass" maxlength="20" name="txtLastClass" value="<?php echo $Clean['LastClass'];?>" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="LastClassStatus" class="col-lg-2 control-label">Last Class Status</label>
                            <div class="col-lg-4">
                                <select class="form-control" name="drdLastClassStatus" id="LastClassStatus">
<?php
                                if (is_array($LastClassStatusList) && count($LastClassStatusList) > 0)
                                {
                                    foreach ($LastClassStatusList as $LastClassStatus => $LastClassStatusName)
                                    {
                                        echo '<option ' . ($Clean['LastClassStatus'] == $LastClassStatus ? 'selected="selected"' : '') . ' value="' . $LastClassStatus . '">' . $LastClassStatusName . '</option>';
                                    }
                                }
?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="SourceOfInformation" class="col-lg-2 control-label">Source Of Information</label>
                            <div class="col-lg-4">
                                <select class="form-control" name="drdSourceOfInformation" id="SourceOfInformation">
<?php
                                if (is_array($SourceOfInformationList) && count($SourceOfInformationList) > 0)
                                {
                                    foreach ($SourceOfInformationList as $SourceOfInformation => $SourceOfInformationName)
                                    {
                                        echo '<option ' . ($Clean['SourceOfInformation'] == $SourceOfInformation ? 'selected="selected"' : '') . ' value="' . $SourceOfInformation . '">' . $SourceOfInformationName . '</option>';
                                    }
                                }
?>
                                </select>
                            </div>
                            <label for="Description" class="col-lg-2 control-label">Description</label>
                            <div class="col-lg-4">
                                <textarea class="form-control" id="Description" name="txtDescription"><?php echo $Clean['Description']; ?></textarea>
                            </div>
                        </div>
                       <!-- <div class="form-group">
                            <label for="FormFee" class="col-lg-2 control-label">Form Fee</label>
                            <div class="col-lg-4">
                                <input class="form-control" type="text" id="FormFee" maxlength="9" name="txtFormFee" value="<?php echo ($Clean['FormFee']) ? $Clean['FormFee'] : '';?>"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="IsActive" class="col-lg-2 control-label">Is Active</label>
                            <div class="col-lg-4">
                                <input type="checkbox" id="IsActive" name="chkIsActive" value="1" <?php echo ($Clean['IsActive'] == 1) ? 'checked="checked"' : ''; ?>/>
                            </div>
                        </div>-->
                        <div class="form-group">
                            <div class="col-sm-offset-2 col-lg-10">
                                <input type="hidden" name="hdnProcess" value="3" />
                                <input type="hidden" name="hdnEnquiryID" value="<?php echo $Clean['EnquiryID'];?>" />
                                <button type="submit" class="btn btn-primary">Save</button>
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
$(document).ready(function()
{	
	$(".dtepicker").datepicker({
            changeMonth: true,
            changeYear: true,
            dateFormat: 'dd/mm/yy'
    });

    $('#Country').change(function(){
        
        var CountryID = parseInt($(this).val());
		
		if (CountryID <= 0)
		{
			$('#State').html('<option value="0">Select State</option>');
			return;
		}
		
		$.post("/xhttp_calls/get_all_states.php", {SelectedCountryID:CountryID}, function(data){
		
			ResultArray = data.split("|*****|");
			
			if (ResultArray[0] == 'error')
			{
				alert (ResultArray[1]);
				return false;
			}
			else
			{
				$('#State').html(ResultArray[1]);
			}
		 });
    });

    $('#State').change(function(){
		var StateID = parseInt($(this).val());
		
		if (StateID <= 0)
		{
			$('#District').html('<option value="0">Select District</option>');
			return;
		}
		
		$.post("/xhttp_calls/get_all_district.php", {SelectedStateID:StateID}, function(data){
		
			ResultArray = data.split("|*****|");
			
			if (ResultArray[0] == 'error')
			{
				alert (ResultArray[1]);
				return false;
			}
			else
			{
				$('#District').html(ResultArray[1]);
			}
		 });
	});
    
    $('#District').change(function(){
		var DistrictID = parseInt($(this).val());
		var StateID = parseInt($('#State').val());
		
		if (DistrictID <= 0)
		{
			$('#City').html('<option value="0">Select District</option>');
			return;
		}
		
		$.post("/xhttp_calls/get_all_cities.php", {SelectedStateID:StateID, SelectedDistrictID:DistrictID}, function(data){
		
			ResultArray = data.split("|*****|");
			
			if (ResultArray[0] == 'error')
			{
				alert (ResultArray[1]);
				return false;
			}
			else
			{
				$('#City').html(ResultArray[1]);
			}
		 });
	});
	
	// Calculate Age
    $("#DOB").change(function(){
        var DOB = $("#DOB").val().toString();

        if (DOB == '') 
        {
            return false;
        }
        var BirthYear = parseInt(DOB.substring(6,10), 10);
        var BirthMonth = parseInt(DOB.substring(3,5), 10);
        var Birthday = parseInt(DOB.substring(0,2), 10);

        var CurrentDate = new Date();
        var BirthDate = new Date(BirthYear, BirthMonth-1, Birthday);
        
        var DifferenceInMilisecond = CurrentDate.valueOf() - BirthDate.valueOf();
        
        var YearDifference = Math.floor(DifferenceInMilisecond / 31536000000);
        var DayDifference = Math.floor((DifferenceInMilisecond % 31536000000) / 86400000);
        
        if ((CurrentDate.getMonth() == BirthDate.getMonth()) && (CurrentDate.getDate() == BirthDate.getDate())) {
            alert("Happy B'day!!!");
        }
        
        var MonthDifference = Math.floor(DayDifference/30);
        DayDifference = DayDifference % 30;

        if (isNaN(YearDifference) || isNaN(MonthDifference) || isNaN(DayDifference)) 
        {
            alert("Invalid date of Birth - Please try again!");
            return false;
        }
        else 
        {
            $('#Year').val(YearDifference);
            $('#Month').val(MonthDifference);
            $('#Day').val(DayDifference);
        }
    }).trigger('change');
});
</script>
</body>
</html>
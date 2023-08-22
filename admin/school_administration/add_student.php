<?php
// error_reporting(E_ALL);
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once("../../classes/school_administration/class.students.php");
require_once("../../classes/school_administration/class.parent_details.php");
require_once("../../classes/school_administration/class.student_details.php");

require_once("../../classes/school_administration/class.colour_houses.php");
require_once("../../classes/school_administration/class.classes.php");
require_once("../../classes/school_administration/class.section_master.php");
require_once("../../classes/school_administration/class.class_sections.php");
require_once("../../classes/school_administration/class.countries.php");
require_once("../../classes/school_administration/class.states.php");
require_once("../../classes/school_administration/class.cities.php");

require_once("../../classes/class.date_processing.php");
require_once("../../classes/class.helpers.php");

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

if ($LoggedUser->HasPermissionForTask(TASK_ADD_STUDENT) !== true)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}

$HasErrors = false;

$StudentTypeList = array('New' => 'New', 'Old' => 'Old');
$GenderList = array('Male' => 'Male', 'Female' => 'Female', 'Others' => 'Others');
$BloodGroupList = array('A+' => 'A+', 'A-' => 'A-', 'B+' => 'B+', 'AB+' => 'AB+', 'AB-' => 'AB-', 'O+' => 'O+', 'O-' => 'O-');
$CategoryList = array('General' => 'General', 'OBC' => 'OBC', 'SC' => 'SC', 'ST' => 'ST');
$OtherCategoryList = array('IsEWS' => 'EWS', 'HasDisability' => 'Disabled', 'IsSingleGirl' => 'Single Girl');
$LastSchoolBoardList = array('CBSE' => 'CBSE', 'ICSE' => 'ICSE', 'UP' => 'UP');
$TCReceivedList = array('Yes' => 'Yes', 'No' => 'No');
$LastExamStatusList = array('Passed' => 'Passed', 'Failed' => 'Failed', 'Awaited' => 'Awaited');
$MotherTongueList = array('Hindi' => 'Hindi', 'English' => 'English');

$ColorHouseList = array();
$ColorHouseList = ColourHouse::GetActiveColourHouses();

$ClassList =  array();
$ClassList = AddedClass::GetActiveClasses();

$AllSectionMasters = array();
$AllSectionMasters = SectionMaster::GetActiveSectionMasters();

$ClassSectionsList =  array();

$CountryList =  array();
$CountryList = Country::GetAllCountries();

$StateList =  array();
$DistrictList =  array();
$CityList =  array();

$ResidentailStateList =  array();
$ResidentailDistrictList =  array();
$ResidentailCityList =  array();

$PermanentStateList =  array();
$PermanentDistrictList =  array();
$PermanentCityList =  array();

$acceptable_extensions = array('jpeg', 'jpg', 'png', 'gif');
$acceptable_mime_types = array(
    'image/jpeg',
    'image/jpg', 
    'image/png', 
    'image/gif' 
);

$Clean = array();
$Clean['UploadFile'] = array();

$Clean['Process'] = 0;

$Clean['RollNumber'] = 0;
$Clean['EnrollmentID'] = '';
$Clean['StudentType'] = 'New';
$Clean['ColourHouseID'] = 0;
$Clean['ClassID'] = 0;
$Clean['ClassSectionID'] = 0;

$Clean['FirstName'] = '';
$Clean['LastName'] = '';
$Clean['DOB'] = '';

$Clean['Address1'] = '';
$Clean['Address2'] = '';

$Clean['Gender'] = 'Male';
$Clean['BloodGroup'] = '';
$Clean['Category'] = 'General';

$Clean['IsEWS'] = 0;
$Clean['HasDisability'] = 0;
$Clean['IsSingleGirl'] = 0;

$Clean['CountryID'] = key($CountryList);
$StateList = State::GetAllStates($Clean['CountryID']);

$Clean['StateID'] = key($StateList);

$DistrictList = City::GetAllDistricts($Clean['StateID']);
$Clean['DistrictID'] = key($DistrictList);

$CityList = City::GetAllCities($Clean['StateID'], $Clean['DistrictID']);
$Clean['CityID'] = key($CityList);

$Clean['Pincode'] = '';
$Clean['AadharNumber'] = 0;

$Clean['AdmissionDate'] = '';
$Clean['MobileNumber'] = '';
$Clean['Email'] = '';
$Clean['Status'] = 'Active';

// Last Academic Details
$Clean['LastClass'] = '';
$Clean['LastSchool'] = '';
$Clean['LastSchoolBoard'] = 'CBSE';
$Clean['TCReceived'] = 0;
$Clean['TCDate'] = '';

$Clean['SubjectsProposed'] = '';
$Clean['MotherTongue'] = '';
$Clean['HomeTown'] = '';

$Clean['LastExamStatus'] = 'Passed';
$Clean['LastExamPercentage'] = 0;

// Parent Details
$Clean['ParentType'] = 'New';

$Clean['FatherFirstName'] = '';
$Clean['FeeCode'] = '';
$Clean['FatherLastName'] = '';
$Clean['MotherFirstName'] = '';
$Clean['MotherLastName'] = '';

$Clean['FatherOccupation'] = '';
$Clean['MotherOccupation'] = '';

$Clean['FatherOfficeName'] = '';
$Clean['MotherOfficeName'] = '';
$Clean['FatherOfficeAddress'] = '';
$Clean['MotherOfficeAddress'] = '';

$Clean['ResidentailAddress'] = '';
$Clean['ResidentailCountryID'] = key($CountryList);

$ResidentailStateList = State::GetAllStates($Clean['ResidentailCountryID']);
$Clean['ResidentailStateID'] = key($ResidentailStateList);

$ResidentailDistrictList = City::GetAllDistricts($Clean['ResidentailStateID']);
$Clean['ResidentailDistrictID'] = key($ResidentailDistrictList);

$ResidentailCityList = City::GetAllCities($Clean['ResidentailStateID'], $Clean['ResidentailDistrictID']);
$Clean['ResidentailCityID'] = key($ResidentailCityList);

$Clean['ResidentailPinCode'] = '';

$Clean['PermanentAddress'] = '';
$Clean['PermanentCountryID'] = key($CountryList);

$PermanentStateList = State::GetAllStates($Clean['PermanentCountryID']);
$Clean['PermanentStateID'] = key($PermanentStateList);

$PermanentDistrictList = City::GetAllDistricts($Clean['PermanentStateID']);
$Clean['PermanentDistrictID'] = key($PermanentDistrictList);

$PermanentCityList = City::GetAllCities($Clean['PermanentStateID'], $Clean['PermanentDistrictID']);
$Clean['PermanentCityID'] = key($PermanentCityList);

$Clean['PermanentPinCode'] = '';

$Clean['PhoneNumber'] = '';
$Clean['ParentAadharNumber'] = 0;
$Clean['FatherMobileNumber'] = '';
$Clean['MotherMobileNumber'] = '';
$Clean['FatherEmail'] = '';
$Clean['MotherEmail'] = '';

$Clean['ParentID'] = 0;

if (isset($_POST['hdnProcess']))
{
	$Clean['Process'] = (int) $_POST['hdnProcess'];
}
switch ($Clean['Process'])
{
	case 1:				
		if (isset($_POST['txtRollNumber']))
        {
            $Clean['RollNumber'] = (int) $_POST['txtRollNumber'];
        }

        if (isset($_POST['txtEnrollmentID']))
        {
            $Clean['EnrollmentID'] = strip_tags(trim($_POST['txtEnrollmentID']));
        }
        
        if (isset($_POST['txtFeeCode']))
        {
            $Clean['FeeCode'] = strip_tags(trim($_POST['txtFeeCode']));
        }

        if (isset($_POST['drdStudentType']))
        {
            $Clean['StudentType'] = strip_tags(trim($_POST['drdStudentType']));
        }

        if (isset($_POST['drdColourHouse']))
        {
            $Clean['ColourHouseID'] = (int) $_POST['drdColourHouse'];
        }
		
        if (isset($_POST['drdClass']))
        {
            $Clean['ClassID'] = (int) $_POST['drdClass'];
        }

        if (isset($_POST['drdClassSection']))
        {
            $Clean['ClassSectionID'] = (int) $_POST['drdClassSection'];
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

        if (isset($_POST['optGender']))
        {
            $Clean['Gender'] = strip_tags(trim($_POST['optGender']));
        }

        if (isset($_POST['drdBloodGroup']))
        {
            $Clean['BloodGroup'] = strip_tags(trim($_POST['drdBloodGroup']));
        }

        if (isset($_POST['optCategory']))
        {
            $Clean['Category'] = strip_tags(trim($_POST['optCategory']));
        }

        if (isset($_POST['chkIsEWS']))
        {
            $Clean['IsEWS'] = 1;
        }

        if (isset($_POST['chkHasDisability']))
        {
            $Clean['HasDisability'] = 1;
        }

        if (isset($_POST['chkIsSingleGirl']))
        {
            $Clean['IsSingleGirl'] = 1;
        }

        if (isset($_POST['txtAddress1']))
        {
            $Clean['Address1'] = strip_tags(trim($_POST['txtAddress1']));
        }

        if (isset($_POST['txtAddress2']))
        {
            $Clean['Address2'] = strip_tags(trim($_POST['txtAddress2']));
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

        if (isset($_POST['txtPincode']))
        {
            $Clean['Pincode'] = strip_tags(trim($_POST['txtPincode']));
        }

        if (isset($_POST['txtAadharNumber']))
        {
            $Clean['AadharNumber'] = (int) $_POST['txtAadharNumber'];
        }
        
        if (isset($_FILES['fleEventImage']) && is_array($_FILES['fleEventImage']))
        {
            $Clean['UploadFile'] = $_FILES['fleEventImage'];
        }
        
        if (isset($_POST['txtAdmissionDate']))
        {
            $Clean['AdmissionDate'] = strip_tags(trim($_POST['txtAdmissionDate']));
        }

        if (isset($_POST['txtMobileNumber']))
        {
            $Clean['MobileNumber'] = strip_tags(trim($_POST['txtMobileNumber']));
        }

        if (isset($_POST['txtEmail']))
        {
            $Clean['Email'] = strip_tags(trim($_POST['txtEmail']));
        }

        if (isset($_POST['txtLastClass']))
        {
            $Clean['LastClass'] = strip_tags(trim($_POST['txtLastClass']));
        }

        if (isset($_POST['txtLastSchool']))
        {
            $Clean['LastSchool'] = strip_tags(trim($_POST['txtLastSchool']));
        }

        if (isset($_POST['optLastSchoolBoard']))
        {
            $Clean['LastSchoolBoard'] = strip_tags(trim($_POST['optLastSchoolBoard']));
        }

        if (isset($_POST['optTCReceived']))
        {
            $Clean['TCReceived'] = strip_tags(trim($_POST['optTCReceived']));
        }

        if (isset($_POST['txtTCDate']))
        {
            $Clean['TCDate'] = strip_tags(trim($_POST['txtTCDate']));
        }

        if (isset($_POST['txtSubjectsProposed']))
        {
            $Clean['SubjectsProposed'] = strip_tags(trim($_POST['txtSubjectsProposed']));
        }

        if (isset($_POST['drdMotherTongue']))
        {
            $Clean['MotherTongue'] = strip_tags(trim($_POST['drdMotherTongue']));
        }

        if (isset($_POST['drdHomeTown']))
        {
            $Clean['HomeTown'] = strip_tags(trim($_POST['drdHomeTown']));
        }

        if (isset($_POST['optLastExamStatus']))
        {
            $Clean['LastExamStatus'] = strip_tags(trim($_POST['optLastExamStatus']));
        }

        if (isset($_POST['txtLastExamPercentage']))
        {
            $Clean['LastExamPercentage'] = (float) $_POST['txtLastExamPercentage'];
        }
		
		if (isset($_POST['chkParentType']))
        {
            $Clean['ParentType'] = strip_tags(trim($_POST['chkParentType']));
        }
		
		if (isset($_POST['txtParentID']))
        {
            $Clean['ParentID'] = (int) $_POST['txtParentID'];
        }
		
        if (isset($_POST['txtFatherFirstName']))
        {
            $Clean['FatherFirstName'] = strip_tags(trim($_POST['txtFatherFirstName']));
        }

        if (isset($_POST['txtFatherLastName']))
        {
            $Clean['FatherLastName'] = strip_tags(trim($_POST['txtFatherLastName']));
        }

        if (isset($_POST['txtMotherFirstName']))
        {
            $Clean['MotherFirstName'] = strip_tags(trim($_POST['txtMotherFirstName']));
        }

        if (isset($_POST['txtMotherLastName']))
        {
            $Clean['MotherLastName'] = strip_tags(trim($_POST['txtMotherLastName']));
        }

        if (isset($_POST['txtFatherOccupation']))
        {
            $Clean['FatherOccupation'] = strip_tags(trim($_POST['txtFatherOccupation']));
        }

        if (isset($_POST['txtMotherOccupation']))
        {
            $Clean['MotherOccupation'] = strip_tags(trim($_POST['txtMotherOccupation']));
        }

        if (isset($_POST['txtFatherOfficeName']))
        {
            $Clean['FatherOfficeName'] = strip_tags(trim($_POST['txtFatherOfficeName']));
        }

        if (isset($_POST['txtMotherOfficeName']))
        {
            $Clean['MotherOfficeName'] = strip_tags(trim($_POST['txtMotherOfficeName']));
        }

        if (isset($_POST['txtFatherOfficeAddress']))
        {
            $Clean['FatherOfficeAddress'] = strip_tags(trim($_POST['txtFatherOfficeAddress']));
        }

        if (isset($_POST['txtMotherOfficeAddress']))
        {
            $Clean['MotherOfficeAddress'] = strip_tags(trim($_POST['txtMotherOfficeAddress']));
        }

        if (isset($_POST['txtResidentailAddress']))
        {
            $Clean['ResidentailAddress'] = strip_tags(trim($_POST['txtResidentailAddress']));
        }

        if (isset($_POST['drdResidentailCity']))
        {
            $Clean['ResidentailCityID'] = (int) $_POST['drdResidentailCity'];
        }

        if (isset($_POST['drdResidentailDistrict']))
        {
            $Clean['ResidentailDistrictID'] = (int) $_POST['drdResidentailDistrict'];
        }

        if (isset($_POST['drdResidentailState']))
        {
            $Clean['ResidentailStateID'] = (int) $_POST['drdResidentailState'];
        }

        if (isset($_POST['drdResidentailCountry']))
        {
            $Clean['ResidentailCountryID'] = (int) $_POST['drdResidentailCountry'];
        }

        if (isset($_POST['txtResidentailPinCode']))
        {
            $Clean['ResidentailPinCode'] = strip_tags(trim($_POST['txtResidentailPinCode']));
        }

        if (isset($_POST['txtPermanentAddress']))
        {
            $Clean['PermanentAddress'] = strip_tags(trim($_POST['txtPermanentAddress']));
        }

        if (isset($_POST['txtPermanentCity']))
        {
            $Clean['PermanentCityID'] = (int) $_POST['txtPermanentCity'];
        }

        if (isset($_POST['txtPermanentDistrict']))
        {
            $Clean['PermanentDistrictID'] = (int) $_POST['txtPermanentDistrict'];
        }

        if (isset($_POST['txtPermanentState']))
        {
            $Clean['PermanentStateID'] = (int) $_POST['txtPermanentState'];
        }

        if (isset($_POST['txtPermanentCountry']))
        {
            $Clean['PermanentCountryID'] = (int) $_POST['txtPermanentCountry'];
        }

        if (isset($_POST['txtPermanentPinCode']))
        {
            $Clean['PermanentPinCode'] = strip_tags(trim($_POST['txtPermanentPinCode']));
        }

        if (isset($_POST['txtPhoneNumber']))
        {
            $Clean['PhoneNumber'] = strip_tags(trim($_POST['txtPhoneNumber']));
        }

        if (isset($_POST['txtFatherMobileNumber']))
        {
            $Clean['FatherMobileNumber'] = strip_tags(trim($_POST['txtFatherMobileNumber']));
        }

        if (isset($_POST['txtMotherMobileNumber']))
        {
            $Clean['MotherMobileNumber'] = strip_tags(trim($_POST['txtMotherMobileNumber']));
        }

        if (isset($_POST['txtFatherEmail']))
        {
            $Clean['FatherEmail'] = strip_tags(trim($_POST['txtFatherEmail']));
        }

        if (isset($_POST['txtMotherEmail']))
        {
            $Clean['MotherEmail'] = strip_tags(trim($_POST['txtMotherEmail']));
        }

        if (isset($_POST['txtParentAadharNumber']))
        {
            $Clean['ParentAadharNumber'] = strip_tags(trim($_POST['txtParentAadharNumber']));
        }

		$NewRecordValidator = new Validator(); 
		
		if ($Clean['RollNumber'] > 0)
		{
		    $NewRecordValidator->ValidateInteger($Clean['RollNumber'], 'Please Enter valid Roll Number.', 1);   
		}
		
		if ($Clean['EnrollmentID'] != '')
		{
		    $NewRecordValidator->ValidateStrings($Clean['EnrollmentID'], 'Enrollment ID is required and should be between 3 and 50 characters.', 3, 50);
		}

        $NewRecordValidator->ValidateStrings($Clean['FeeCode'], 'FeeCode is required and should be between 3 and 20 characters.', 3, 20);

        $NewRecordValidator->ValidateInSelect($Clean['StudentType'], $StudentTypeList, 'Unknown error in student type, please try again.');
        
        if ($Clean['ColourHouseID'] > 0) 
        {
            $NewRecordValidator->ValidateInSelect($Clean['ColourHouseID'], $ColorHouseList, 'Unknown error in house, please try again.');
        }
        
		if ($NewRecordValidator->ValidateInSelect($Clean['ClassID'], $ClassList, 'Please select a class.'))
		{
            $ClassSectionsList = ClassSections::GetClassSections($Clean['ClassID']);
			$NewRecordValidator->ValidateInSelect($Clean['ClassSectionID'], $ClassSectionsList, 'Please select a section.');
		}

        $NewRecordValidator->ValidateStrings($Clean['FirstName'], 'First Name is required and should be between 1 and 20 characters.', 1, 20);
        
        if ($Clean['LastName'] != '') 
        {
            $NewRecordValidator->ValidateStrings($Clean['LastName'], 'Last Name is required and should be between 1 and 30 characters.', 1, 30);
        }
    
        $NewRecordValidator->ValidateDate($Clean['DOB'], 'Please enter a valid date of birth.');
        
        if ($Clean['Address1'] != '') 
        {
            $NewRecordValidator->ValidateStrings($Clean['Address1'], 'Address1 is required and should be between 5 and 150 characters.', 5, 150);
        }
        
        if ($Clean['Address2'] != '') 
        {
            $NewRecordValidator->ValidateStrings($Clean['Address2'], 'Address2 is required and should be between 5 and 150 characters.', 5, 150);
        }

        $NewRecordValidator->ValidateInSelect($Clean['Gender'], $GenderList, 'Unknown error, please try again.');

        if ($Clean['BloodGroup'] != 0) 
        {
            $NewRecordValidator->ValidateInSelect($Clean['BloodGroup'], $BloodGroupList, 'Unknown blood group, please try again.');
        }
        
        $NewRecordValidator->ValidateInSelect($Clean['Category'], $CategoryList, 'Unknown error, please try again.');
        
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

        if ($Clean['Pincode'] != '') 
        {
            $NewRecordValidator->ValidateStrings($Clean['Pincode'], 'Pincode is required and should be between 3 and 6 characters.', 3, 6);
        }

        if ($Clean['AadharNumber'] > 0) 
        {
            $NewRecordValidator->ValidateInteger($Clean['AadharNumber'], 'please enter valid Aadhar Number.', 1);
        }
        
        // if ($Clean['UploadFile']['error'] == 4) 
        // {
        //     $NewRecordValidator->AttachTextError('Please select a image.');
        //     $HasErrors = true;
        //     break;
        // }

        $FileName = '';
        $FileExtension = '';

        if ($Clean['UploadFile']['name'] != '')
        {
            if ($Clean['UploadFile']['size'] > MAX_UPLOADED_FILE_SIZE || $Clean['UploadFile']['size'] <= 0) 
            {
                $NewRecordValidator->AttachTextError('File size cannot be greater then ' . (MAX_UPLOADED_FILE_SIZE / 1024 /1024) . ' MB.');
            }

            $FileExtension = strtolower(pathinfo($Clean['UploadFile']['name'], PATHINFO_EXTENSION));

            if (!in_array($Clean['UploadFile']['type'], $acceptable_mime_types) || !in_array($FileExtension, $acceptable_extensions))
            {
               $NewRecordValidator->AttachTextError('Only ' . implode(', ', $acceptable_extensions) . ' files are allowed.');
            }

            if (strlen($Clean['UploadFile']['name']) > MAX_UPLOADED_FILE_NAME_LENGTH)
            {
                $NewRecordValidator->AttachTextError('Uploaded file name cannot be greater then ' . MAX_UPLOADED_FILE_NAME_LENGTH . ' chars.');
            }

            $FileName = $Clean['UploadFile']['name'];
        }
        
        if ($Clean['AdmissionDate'] != '') 
        {
            $NewRecordValidator->ValidateDate($Clean['AdmissionDate'], 'Please enter a valid date.');
        }

        if ($Clean['MobileNumber'] != '') 
        {
            $NewRecordValidator->ValidateStrings($Clean['MobileNumber'], 'Mobile Number is required and should be between 9 and 15 characters.', 9, 15);
        }
        
        if ($Clean['Email'] != '') 
        {
            $NewRecordValidator->ValidateEmail($Clean['Email'], 'Please enter valid Email and it is required and should be between 5 and 150 characters.', 5, 150);
        }
        
        if ($Clean['LastClass'] != '')
        {
            $NewRecordValidator->ValidateStrings($Clean['LastClass'], 'Last Class is required and should be between 1 and 25 characters.', 1, 25);
        }
        
        if ($Clean['LastSchool'] != '')
        {
            $NewRecordValidator->ValidateStrings($Clean['LastSchool'], 'Last School is required and should be between 5 and 150 characters.', 5, 150);
        }
        
        $NewRecordValidator->ValidateInSelect($Clean['LastSchoolBoard'], $LastSchoolBoardList, 'Unknown error, please try again.');
        $NewRecordValidator->ValidateInSelect($Clean['TCReceived'], $TCReceivedList, 'Unknown error, please try again.');

        if ($Clean['TCDate'] != '')
        {
            $NewRecordValidator->ValidateDate($Clean['TCDate'], 'Please enter a valid TC date.');
        }

        $NewRecordValidator->ValidateInSelect($Clean['LastExamStatus'], $LastExamStatusList, 'Unknown error, please try again.');
        
        if ($Clean['ParentType'] != 'New') 
        {
            $NewRecordValidator->ValidateInteger($Clean['ParentID'], 'Invalid parent id.', 1);
        }
		
        $NewRecordValidator->ValidateStrings($Clean['FatherFirstName'], 'Father First Name is required and should be between 1 and 20 characters.', 1, 20);
        
        if ($Clean['FatherLastName'] != '') 
        {
            $NewRecordValidator->ValidateStrings($Clean['FatherLastName'], 'Father Last Name is required and should be between 1 and 30 characters.', 1, 30);
        }
        
        $NewRecordValidator->ValidateStrings($Clean['MotherFirstName'], 'Mother First Name is required and should be between 1 and 20 characters.', 1, 20);
        
        if ($Clean['MotherLastName'] != '') 
        {
            $NewRecordValidator->ValidateStrings($Clean['MotherLastName'], 'Mother Last Name is required and should be between 3 and 30 characters.', 3, 30);
        }

        if ($Clean['FatherOccupation'] != '') 
        {
            $NewRecordValidator->ValidateStrings($Clean['FatherOccupation'], 'Father Occupation is required and should be between 1 and 30 characters.', 1, 30);
        }

        if ($Clean['MotherOccupation'] != '') 
        {
            $NewRecordValidator->ValidateStrings($Clean['MotherOccupation'], 'Mother Occupation is required and should be between 3 and 30 characters.', 3, 30);
        }
        
        if ($Clean['FatherOfficeName'] != '') 
        {
            $NewRecordValidator->ValidateStrings($Clean['FatherOfficeName'], 'Father Office Name is required and should be between 1 and 150 characters.', 1, 150);
        }

        if ($Clean['MotherOfficeName'] != '') 
        {
            $NewRecordValidator->ValidateStrings($Clean['MotherOfficeName'], 'Mother Office Name is required and should be between 3 and 150 characters.', 3, 150);
        }
        
        if ($Clean['FatherOfficeAddress'] != '') 
        {
            $NewRecordValidator->ValidateStrings($Clean['FatherOfficeAddress'], 'Father Office Address is required and should be between 3 and 500 characters.', 3, 500);
        }
        
        if ($Clean['MotherOfficeAddress'] != '') 
        {
            $NewRecordValidator->ValidateStrings($Clean['MotherOfficeAddress'], 'Mother Office Address is required and should be between 3 and 500 characters.', 3, 500);
        }
        
        if ($Clean['ResidentailAddress'] != '') 
        {
            $NewRecordValidator->ValidateStrings($Clean['ResidentailAddress'], 'Residentail Address is required and should be between 3 and 300 characters.', 3, 300);
        }
        
        if ($NewRecordValidator->ValidateInSelect($Clean['ResidentailCountryID'], $CountryList, 'Unknown error, please try again.'))
        {
            $ResidentailStateList = State::GetAllStates($Clean['ResidentailCountryID']);
            
            if ($NewRecordValidator->ValidateInSelect($Clean['ResidentailStateID'], $ResidentailStateList, 'Unknown error, please try again.'))
            {
                $ResidentailDistrictList = City::GetAllDistricts($Clean['ResidentailStateID']);
                
                if ($NewRecordValidator->ValidateInSelect($Clean['ResidentailDistrictID'], $ResidentailDistrictList, 'Unknown error, please try again.'))
                {
                    $ResidentailCityList = City::GetAllCities($Clean['ResidentailStateID'], $Clean['ResidentailDistrictID']);
                    $NewRecordValidator->ValidateInSelect($Clean['ResidentailCityID'], $ResidentailCityList, 'Unknown error, please try again.');   
                }
            }
        }

        if ($Clean['ResidentailPinCode'] != '') 
        {
            $NewRecordValidator->ValidateStrings($Clean['ResidentailPinCode'], 'Residentail PinCode is required and should be between 3 and 6 characters.', 3, 6);
        }

        if ($Clean['PermanentAddress'] != '') 
        {
            $NewRecordValidator->ValidateStrings($Clean['PermanentAddress'], 'Permanent Address is required and should be between 3 and 300 characters.', 3, 300);
        }
        
        if ($NewRecordValidator->ValidateInSelect($Clean['PermanentCountryID'], $CountryList, 'Unknown error, please try again.'))
        {
            $PermanentStateList = State::GetAllStates($Clean['PermanentCountryID']);
            
            if ($NewRecordValidator->ValidateInSelect($Clean['PermanentStateID'], $PermanentStateList, 'Unknown error, please try again.'))
            {
                $PermanentDistrictList = City::GetAllDistricts($Clean['PermanentStateID']);
                
                if ($NewRecordValidator->ValidateInSelect($Clean['PermanentDistrictID'], $PermanentDistrictList, 'Unknown error, please try again.'))
                {
                    $PermanentCityList = City::GetAllCities($Clean['PermanentStateID'], $Clean['PermanentDistrictID']);
                    $NewRecordValidator->ValidateInSelect($Clean['PermanentCityID'], $PermanentCityList, 'Unknown error, please try again.');   
                }
            }
        }
        
        if ($Clean['PermanentPinCode'] != '') 
        {
            $NewRecordValidator->ValidateStrings($Clean['PermanentPinCode'], 'Permanent PinCode is required and should be between 3 and 6 characters.', 3, 6);
        }
        
        if ($Clean['PhoneNumber'] != '') 
        {
            $NewRecordValidator->ValidateStrings($Clean['PhoneNumber'], 'Phone Number is required and should be between 3 and 15 characters.', 3, 15);
        }

        if ($Clean['ParentAadharNumber'] != '') 
        {
            $NewRecordValidator->ValidateInteger($Clean['ParentAadharNumber'], 'please enter valid Aadhar Number for Parent.', 1);
        }
        
        if ($Clean['FatherMobileNumber'] != '') 
        {
            $NewRecordValidator->ValidateStrings($Clean['FatherMobileNumber'], 'Father Mobile Number is required and should be between 3 and 15 characters.', 3, 15);
        }

        if ($Clean['MotherMobileNumber'] != '') 
        {
            $NewRecordValidator->ValidateStrings($Clean['MotherMobileNumber'], 'Mother Mobile Number is required and should be between 3 and 15 characters.', 3, 15);
        }

        if ($Clean['FatherEmail'] != '') 
        {
            $NewRecordValidator->ValidateEmail($Clean['FatherEmail'], 'Father Email is required and should be between 3 and 150 characters.', 3, 150);
        }
        
        if ($Clean['MotherEmail'] != '') 
        {
            $NewRecordValidator->ValidateEmail($Clean['MotherEmail'], 'Mother Email is required and should be between 3 and 150 characters.', 3, 150);
        }
        
		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}

		$NewStudentDetail = new StudentDetail();
		
		if ($Clean['ParentType'] != 'New' && $Clean['ParentID'] > 0)
		{
			$NewStudentDetail->SetParentID($Clean['ParentID']);
		}
		
        $NewStudentDetail->SetRollNumber($Clean['RollNumber']);
        $NewStudentDetail->SetEnrollmentID($Clean['EnrollmentID']);
        $NewStudentDetail->SetStudentType($Clean['StudentType']);
        $NewStudentDetail->SetColourHouseID($Clean['ColourHouseID']);
        $NewStudentDetail->SetClassSectionID($Clean['ClassSectionID']);
        
        $NewStudentDetail->SetFirstName($Clean['FirstName']);
        $NewStudentDetail->SetLastName($Clean['LastName']);

        if ($Clean['DOB'] != '')
        {
            $NewStudentDetail->SetDOB(date('Y-m-d', strtotime(DateProcessing::ToggleDateDayAndMonth(($Clean['DOB'])))));
        }

        $NewStudentDetail->SetAddress1($Clean['Address1']);
        $NewStudentDetail->SetAddress2($Clean['Address2']);

        $NewStudentDetail->SetGender($Clean['Gender']);        
        $NewStudentDetail->SetBloodGroup($Clean['BloodGroup']);
        $NewStudentDetail->SetCategory($Clean['Category']);

        $NewStudentDetail->SetIsEWS($Clean['IsEWS']);
        $NewStudentDetail->SetHasDisability($Clean['HasDisability']);
        $NewStudentDetail->SetIsSingleGirl($Clean['IsSingleGirl']);

        $NewStudentDetail->SetCountryID($Clean['CountryID']);
        $NewStudentDetail->SetStateID($Clean['StateID']);
        $NewStudentDetail->SetDistrictID($Clean['DistrictID']);
        $NewStudentDetail->SetCityID($Clean['CityID']);
        
        $NewStudentDetail->SetPincode($Clean['Pincode']);
        $NewStudentDetail->SetAadharNumber($Clean['AadharNumber']);

        if ($Clean['AdmissionDate'] != '')
        {
            $NewStudentDetail->SetAdmissionDate(date('Y-m-d', strtotime(DateProcessing::ToggleDateDayAndMonth(($Clean['AdmissionDate'])))));
        }
        
        $NewStudentDetail->SetMobileNumber($Clean['MobileNumber']);
        $NewStudentDetail->SetEmail($Clean['Email']);
        $NewStudentDetail->SetStatus($Clean['Status']);
        
        // Last Academic Details

        if($Clean['LastClass'] != '')
        {
            $NewStudentDetail->SetLastClass($Clean['LastClass']);       
        }
        if($Clean['LastSchool'] != '')
        {
            $NewStudentDetail->SetLastSchool($Clean['LastSchool']);    
        }
        if($Clean['LastSchoolBoard'] != '')
        {
            $NewStudentDetail->SetLastSchoolBoard($Clean['LastSchoolBoard']);    
        }

        if($Clean['TCReceived'] != '')
        {
            $NewStudentDetail->SetTCReceived($Clean['TCReceived']);
        }

        if ($Clean['TCDate'] != '')
        {
            $NewStudentDetail->SetTCDate(date('Y-m-d', strtotime(DateProcessing::ToggleDateDayAndMonth(($Clean['TCDate'])))));
        }
    
        $NewStudentDetail->SetSubjectsProposed($Clean['SubjectsProposed']);
        $NewStudentDetail->SetMotherTongue($Clean['MotherTongue']);
        $NewStudentDetail->SetHomeTown($Clean['HomeTown']);

        $NewStudentDetail->SetLastExamStatus($Clean['LastExamStatus']);
        $NewStudentDetail->SetLastExamPercentage($Clean['LastExamPercentage']);
		
		$NewStudentDetail->SetCreateUserID($LoggedUser->GetUserID());
		
        //Parent Details
        
		$NewParentDetail = new ParentDetail();
		
        $NewParentDetail->SetFatherFirstName($Clean['FatherFirstName']);
        $NewParentDetail->SetFatherLastName($Clean['FatherLastName']);
         $NewParentDetail->SetFeeCode($Clean['FeeCode']);
        $NewParentDetail->SetMotherFirstName($Clean['MotherFirstName']);
        $NewParentDetail->SetMotherLastName($Clean['MotherLastName']);

        $NewParentDetail->SetFatherOccupation($Clean['FatherOccupation']);
        $NewParentDetail->SetMotherOccupation($Clean['MotherOccupation']);

        $NewParentDetail->SetFatherOfficeName($Clean['FatherOfficeName']);
        $NewParentDetail->SetMotherOfficeName($Clean['MotherOfficeName']);
        $NewParentDetail->SetFatherOfficeAddress($Clean['FatherOfficeAddress']);
        $NewParentDetail->SetMotherOfficeAddress($Clean['MotherOfficeAddress']);

        $NewParentDetail->SetResidentailAddress($Clean['ResidentailAddress']);
        $NewParentDetail->SetResidentailCountryID($Clean['ResidentailCountryID']);
        $NewParentDetail->SetResidentailStateID($Clean['ResidentailStateID']);
        $NewParentDetail->SetResidentailDistrictID($Clean['ResidentailDistrictID']);
        $NewParentDetail->SetResidentailCityID($Clean['ResidentailCityID']);      
        $NewParentDetail->SetResidentailPinCode($Clean['ResidentailPinCode']);

        $NewParentDetail->SetPermanentAddress($Clean['PermanentAddress']);
        $NewParentDetail->SetPermanentCountryID($Clean['PermanentCountryID']);
        $NewParentDetail->SetPermanentStateID($Clean['PermanentStateID']);
        $NewParentDetail->SetPermanentDistrictID($Clean['PermanentDistrictID']);
        $NewParentDetail->SetPermanentCityID($Clean['PermanentCityID']);
        $NewParentDetail->SetPermanentPinCode($Clean['PermanentPinCode']);

        $NewParentDetail->SetPhoneNumber($Clean['PhoneNumber']);
        $NewParentDetail->SetAadharNumber($Clean['ParentAadharNumber']);
        $NewParentDetail->SetFatherMobileNumber($Clean['FatherMobileNumber']);
        $NewParentDetail->SetMotherMobileNumber($Clean['MotherMobileNumber']);

        $NewParentDetail->SetFatherEmail($Clean['FatherEmail']);
        $NewParentDetail->SetMotherEmail($Clean['MotherEmail']);

        $NewParentDetail->SetIsActive(1);

		if (!$NewStudentDetail->Save($NewParentDetail))
		{
			$NewRecordValidator->AttachTextError(ProcessErrors($NewStudentDetail->GetLastErrorCode()));
			$HasErrors = true;

			break;
		}
		
        if($FileName != '') 
        {
        $FileName = md5(uniqid(rand(), true) . $NewStudentDetail->GetStudentID()) . '.' . $FileExtension;
        $NewStudentDetail->SetStudentPhoto($FileName);
        }
        // Generate a Unique Name for the uploaded document
        
		$UniqueID = Helpers::GenerateUniqueAddedID($Clean['FatherFirstName'] . $Clean['FatherLastName'], date('Y-m-d', strtotime(DateProcessing::ToggleDateDayAndMonth(($Clean['DOB'])))));
		
		if ($UniqueID != '')
		{
			$NewParentDetail->SetUserName($UniqueID);
			
			if ($NewStudentDetail->Save($NewParentDetail))
			{
				if (!Helpers::SaveUniqueID($UniqueID, $Clean['ParentAadharNumber']))
                {
                    error_log('Criticle Error: Generated Unique ID could not be saved into Added Central DB. StudentID: ' . $NewStudentDetail->GetStudentID() . ' UniqueID: ' . $UniqueID);
                }
			}
			else
			{
				$NewRecordValidator->AttachTextError(ProcessErrors($NewStudentDetail->GetLastErrorCode()));
				$HasErrors = true;

				break;
			}
		}
		else
        {
            error_log('Criticle Error: Generated Unique ID found balnk. StudentID: ' . $NewStudentDetail->GetStudentID());
        }
        
         if (!$NewStudentDetail->Save($NewParentDetail))
            {
                $NewRecordValidator->AttachTextError(ProcessErrors($NewStudentDetail->GetLastErrorCode()));
                $HasErrors = true;

                break;
            }
        
        if ($FileName != '') 
        {
            $UniqueUserFileUploadDirectory = SITE_FS_PATH . '/site_images/student_images/'.$NewStudentDetail->GetStudentID().'/';

            if (!is_dir($UniqueUserFileUploadDirectory))
            {
                mkdir($UniqueUserFileUploadDirectory);
            }
           // now move the uploaded file to application document folder
            // move_uploaded_file($Clean['UploadFile']['tmp_name'], $UniqueUserFileUploadDirectory . $NewStudentDetail->GetStudentID().'.jpg');

            move_uploaded_file($Clean['UploadFile']['tmp_name'], $UniqueUserFileUploadDirectory . $FileName);
        }   
		
		header('location:add_student.php?Mode=AS');
		exit;
	break;
}

$LandingPageMode = '';

if (isset($_GET['Mode']))
{
    $LandingPageMode = $_GET['Mode'];
}

require_once('../html_header.php');
?>
<title>Add Student</title>
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
                    <h1 class="page-header">Add Student</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="AddStudent" action="add_student.php" method="post" enctype="multipart/form-data">
            	<div class="panel panel-default">
                    <div class="panel-heading">
                        <strong>Enter Student Details</strong>
                    </div>
                    <div class="panel-body">
<?php
						if ($HasErrors == true)
						{
							echo $NewRecordValidator->DisplayErrors();
						}
						else if ($LandingPageMode == 'AS')
                        {
                            echo '<div class="alert alert-success alert-top-margin">Record saved successfully.</div>';
                        }
?>                    
                    	<div class="form-group">
                            <label for="RollNumber" class="col-lg-2 control-label">Roll Number</label>
                            <div class="col-lg-4">
                            	<input class="form-control" type="text" maxlength="5" id="RollNumber" name="txtRollNumber" value="<?php echo ($Clean['RollNumber'] ? $Clean['RollNumber'] : ''); ?>" />
                            </div>
                            <label for="EnrollmentID" class="col-lg-2 control-label">Enrollment ID</label>
                            <div class="col-lg-4">
                            	<input class="form-control" type="text" maxlength="50" id="EnrollmentID" name="txtEnrollmentID" value="<?php echo $Clean['EnrollmentID']; ?>" />
                            </div>
                        </div>
                          <div class="form-group">
                            <label for="FeeCode" class="col-lg-2 control-label">Fee Code</label>
                             <div class="col-lg-4">
                                <input class="form-control" type="text" maxlength="20" id="FeeCode" name="txtFeeCode" value="<?php echo $Clean['FeeCode']; ?>">
                            </div>
                           </div>
                        <div class="form-group">
                            <label for="StudentType" class="col-lg-2 control-label">Student Type</label>
                            <div class="col-lg-4">
                            	<select class="form-control" name="drdStudentType" id="StudentType">
<?php
                                foreach ($StudentTypeList as $StudentTypeID => $StudentType) 
                                {
                                    echo '<option value="' . $StudentTypeID . '">' . $StudentType . '</option>';
                                }
?>
								</select>
                            </div>
                            <label for="ColourHouse" class="col-lg-2 control-label">House</label>
                            <div class="col-lg-4">
                            	<select class="form-control" name="drdColourHouse" id="ColourHouse">
                                    <option value="0">Select Color House</option>
<?php
                                if (is_array($ColorHouseList) && count($ColorHouseList) > 0)
                                {
                                    foreach ($ColorHouseList as $ColorHouseID => $ColorHouseName) {
                                        echo '<option ' . ($Clean['ColourHouseID'] == $ColorHouseID ? 'selected="selected"' : '') . ' value="' . $ColorHouseID . '">' . $ColorHouseName . '</option>';
                                    }
                                }
?>
								</select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="Class" class="col-lg-2 control-label">Class</label>
                            <div class="col-lg-4">
                            	<select class="form-control" name="drdClass" id="Class">
									<option value="0">Select Class</option>
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
                            <label for="Section" class="col-lg-2 control-label">Section</label>
                            <div class="col-lg-4">
                            	<select class="form-control" name="drdClassSection" id="Section">
									<option value="0">Select Section</option>
<?php
                                if (is_array($ClassSectionsList) && count($ClassSectionsList) > 0)
                                {
                                    foreach ($ClassSectionsList as $ClassSectionID => $SectionName) 
                                    {
                                        echo '<option ' . ($Clean['ClassSectionID'] == $ClassSectionID ? 'selected="selected"' : '') . ' value="' . $ClassSectionID . '">' . $SectionName . '</option>' ;
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
                            	<input class="form-control select-date" type="text" maxlength="10" id="DOB" name="txtDOB" value="<?php echo $Clean['DOB']; ?>" />
                            </div>
                            <label for="Age" class="col-lg-2 control-label">Age</label>
                            
                            <label for="Year" class="col-lg-1 control-label"><input class="form-control" type="text" maxlength="3" id="Year" name="txtYear" value="" readonly />&nbsp;Year</label>

                            <label for="Month" class="col-lg-1 control-label"><input class="form-control" type="text" maxlength="3" id="Month" name="txtMonth" value="" readonly />&nbsp;Month</label>
                            
                            <label for="Day" class="col-sm-1 control-label"><input class="form-control" type="text" maxlength="3" id="Day" name="txtDay" value="" readonly/>&nbsp;Day</label>
                        </div>
                        <div class="form-group">
                            <label for="Address1" class="col-lg-2 control-label">Address 1</label>
                            <div class="col-lg-10">
                            	<textarea class="form-control"  id="Address1" name="txtAddress1"><?php echo $Clean['Address1']; ?></textarea>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="Address2" class="col-lg-2 control-label">Address 2</label>
                            <div class="col-lg-10">
                            	<textarea class="form-control"  id="Address2" name="txtAddress2"><?php echo $Clean['Address2']; ?></textarea>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="Gender" class="col-lg-2 control-label">Gender</label>
                            <div class="col-sm-4">
<?php
                            foreach($GenderList as $GenderID => $GenderName)
                            {
?>                              
                                <label class="col-sm-6"><input class="custom-radio" type="radio" id="<?php echo $GenderID; ?>" name="optGender" value="<?php echo $GenderID; ?>" <?php echo ($Clean['Gender'] == $GenderID ? 'checked="checked"' : ''); ?> >&nbsp;&nbsp;<?php echo $GenderName; ?></label>            
<?php                                       
                            }
?>
                            </div>
                            <label for="BloodGroup" class="col-lg-2 control-label">Blood Group</label>
                            <div class="col-lg-4">
                            	<select class="form-control" name="drdBloodGroup" id="BloodGroup">
										<option value="">Select Blood Group</option>
<?php
                                if (is_array($BloodGroupList) && count($BloodGroupList) > 0)
                                {
                                    foreach ($BloodGroupList as $BloodGroupID => $BloodGroupName) {
                                        echo '<option ' . ($Clean['BloodGroup'] == $BloodGroupID ? 'selected="selected"' : '') . ' value="' . $BloodGroupID . '">' . $BloodGroupName . '</option>';
                                    }
                                }
?>
								</select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="Category" class="col-lg-2 control-label">Category</label>
                            <div class="col-lg-4">
<?php
                            foreach($CategoryList as $CategoryID => $CategoryName)
                            {
?>                              
                                <label class="col-sm-6"><input class="custom-radio" type="radio" id="<?php echo $CategoryID; ?>" name="optCategory" value="<?php echo $CategoryID; ?>" <?php echo ($Clean['Category'] == $CategoryID ? 'checked="checked"' : ''); ?> >&nbsp;&nbsp;<?php echo $CategoryName; ?></label>            
<?php                                       
                            }
?>
                            </div>
                             <label for="Others" class="col-lg-2 control-label">Others</label>
                            <div class="col-lg-4">
<?php
                            foreach($OtherCategoryList as $OtherCategoryID => $OtherCategoryName)
                            {
?>                              
                                <label class="checkbox-inline"><input class="custom-radio" type="checkbox" id="<?php echo $OtherCategoryID; ?>" name="chk<?php echo $OtherCategoryID; ?>" value="1" <?php echo ($Clean[$OtherCategoryID] == 1 ? 'checked="checked"' : ''); ?> ><?php echo $OtherCategoryName; ?></label>           
<?php                                       
                            }
?>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="Country" class="col-lg-2 control-label">Country</label>
                            <div class="col-lg-4">
                            	<select class="form-control" name="drdCountry" id="Country">
<?php
                                if (is_array($CountryList) && count($CountryList) > 0)
                                {
                                    foreach ($CountryList as $CountryID => $CountryName) {
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
                                    foreach ($StateList as $StateID => $StateName) {
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
                                    foreach ($CityList as $CityID => $CityName) {
                                        echo '<option ' . ($Clean['CityID'] == $CityID ? 'selected="selected"' : '') . ' value="' . $CityID . '">' . $CityName . '</option>';
                                    }
                                }
?>
								</select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="Pincode" class="col-lg-2 control-label">Pincode</label>
                            <div class="col-lg-4">
                                <input class="form-control" type="text" maxlength="6" id="Pincode" name="txtPincode" value="<?php echo $Clean['Pincode']; ?>" />
                            </div>
                            <label for="AadharNumber" class="col-lg-2 control-label">Aadhar Number</label>
                            <div class="col-lg-4">
                                <input class="form-control" type="text" maxlength="12" id="AadharNumber" name="txtAadharNumber" value="<?php echo ($Clean['AadharNumber'] ? $Clean['AadharNumber'] : ''); ?>" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="MobileNumber" class="col-lg-2 control-label">Mobile Number</label>
                            <div class="col-lg-4">
                                <input class="form-control" type="text" maxlength="15" id="MobileNumber" name="txtMobileNumber" value="<?php echo $Clean['MobileNumber']; ?>" />
                            </div>
                            <label for="Email" class="col-lg-2 control-label">Email</label>
                            <div class="col-lg-4">
                                <input class="form-control" type="text" maxlength="100" id="Email" name="txtEmail" value="<?php echo $Clean['Email']; ?>" />
                            </div>
                        </div> 
                        <div class="form-group">
                            <label for="AdmissionDate" class="col-lg-2 control-label">Admission Date</label>
                            <div class="col-lg-4">
                                <input class="form-control select-date" type="text" maxlength="10" id="AdmissionDate" name="txtAdmissionDate" value="<?php echo $Clean['AdmissionDate']; ?>" />
                            </div>
                        </div>
                        <div class="form-group">
                              <label for="Upload" class="col-lg-2 control-label">Upload Image</label>
                            <div class="col-lg-4">
                                <input class="form-control" type="file" name="fleEventImage" onchange="readURL(this);"/>
                            </div>
                            <div class="col-lg-4 EventImage" style="display: none;">
                                <img class="img-responsive center-block" src="" style="height: 100px; width: 140px;" />
                            </div> 
                        </div> 
                    </div>
                </div>
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <strong>Student's Last Academic Details</strong>
                    </div>
                    <div class="panel-body">

                        <div class="form-group">
                            <label for="LastClass" class="col-lg-2 control-label">Last Class</label>
                            <div class="col-lg-4">
                                <input class="form-control" type="text" maxlength="25" id="LastClass" name="txtLastClass" value="<?php echo $Clean['LastClass']; ?>" />
                            </div>
                            <label for="LastSchool" class="col-lg-2 control-label">Last School Name</label>
                            <div class="col-lg-4">
                                <textarea class="form-control"  id="LastSchool" name="txtLastSchool"><?php echo $Clean['LastSchool']; ?></textarea>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="LastSchoolBoard" class="col-lg-2 control-label">Last School Board</label>
                            <div class="col-lg-10">
<?php
                            foreach($LastSchoolBoardList as $LastSchoolBoardID => $LastSchoolBoardName)
                            {
?>                              
                                <label class="col-sm-3"><input class="custom-radio" type="radio" id="<?php echo $LastSchoolBoardID; ?>" name="optLastSchoolBoard" value="<?php echo $LastSchoolBoardID; ?>" <?php echo ($Clean['LastSchoolBoard'] == $LastSchoolBoardID ? 'checked="checked"' : ''); ?> >&nbsp;&nbsp;<?php echo $LastSchoolBoardName; ?></label>            
<?php                                       
                            }
?>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="TCReceived" class="col-lg-2 control-label">TC Received</label>
                            <div class="col-lg-4">
<?php
                            foreach($TCReceivedList as $TCReceivedID => $TCReceivedName)
                            {
?>                              
                                <label class="col-sm-3"><input class="custom-radio" type="radio" id="<?php echo $TCReceivedID; ?>" name="optTCReceived" value="<?php echo $TCReceivedID; ?>" <?php echo ($Clean['TCReceived'] == $TCReceivedID ? 'checked="checked"' : ''); ?> >&nbsp;&nbsp;<?php echo $TCReceivedName; ?></label>            
<?php                                       
                            }
?>
                            </div>
                            <label for="TCDate" class="col-lg-2 control-label">TC Date</label>
                            <div class="col-lg-4">
                                <input class="form-control select-date" type="text" maxlength="10" id="TCDate" name="txtTCDate" value="<?php echo $Clean['TCDate']; ?>" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="SubjectsProposed" class="col-lg-2 control-label">Subjects Proposed</label>
                            <div class="col-lg-10">
                                <select class="form-control" name="txtSubjectsProposed" id="SubjectsProposed" multiple="multiple">
                                        <option value="0">Select Subjects</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="MotherTongue" class="col-lg-2 control-label">Mother Tongue</label>
                            <div class="col-lg-4">
                                <select class="form-control" name="drdMotherTongue" id="MotherTongue">
<?php
                                foreach ($MotherTongueList as $MotherTongueID => $MotherTongue) 
                                {
                                    echo '<option value="' . $MotherTongueID . '">' . $MotherTongue . '</option>';
                                }
?>                                                
                                </select>
                            </div>
                            <label for="HomeTown" class="col-lg-2 control-label">Home Town</label>
                            <div class="col-lg-4">
                                <select class="form-control" name="drdHomeTown" id="HomeTown">
                                        <option value="0">Select Home Town</option>
<?php
                                if (is_array($CityList) && count($CityList) > 0)
                                {
                                    foreach ($CityList as $CityID => $CityName) {
                                        echo '<option ' . ($Clean['HomeTown'] == $CityID ? 'selected="selected"' : '') . ' value="' . $CityID . '">' . $CityName . '</option>';
                                    }
                                }
?>                                                
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                          <label for="LastExamStatus" class="col-lg-2 control-label">Last Exam Result</label>
                          <div class="col-lg-4">
<?php
                            foreach($LastExamStatusList as $LastExamStatusID => $LastExamStatusName)
                            {
?>                              
                                <label class="col-sm-3"><input class="custom-radio" type="radio" id="<?php echo $LastExamStatusID; ?>" name="optLastExamStatus" value="<?php echo $LastExamStatusID; ?>" <?php echo ($Clean['LastExamStatus'] == $LastExamStatusID ? 'checked="checked"' : ''); ?> >&nbsp;&nbsp;<?php echo $LastExamStatusName; ?></label>            
<?php                                       
                            }
?>
                          </div>
                            <label for="LastExamPercentage" class="col-lg-2 control-label">Last Exam Percentage</label>
                            <div class="col-lg-4">
                                <input class="form-control" type="text" maxlength="10" id="LastExamPercentage" name="txtLastExamPercentage" value="<?php echo ($Clean['LastExamPercentage'] ? $Clean['LastExamPercentage'] : ''); ?>" />
                            </div>
                        </div>
                    </div>
                </div>
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <strong>Parent Details</strong>
                    </div>
                    <div class="panel-body">
                        <div class="form-group">
                          <div class="col-lg-12">
                                <label class="col-sm-4"><input class="custom-radio" <?php echo ($Clean['ParentType'] == 'Existing' ? 'checked="checked"' : ''); ?> type="radio" id="AlreadyExistingParent" name="chkParentType" value="Existing">&nbsp;&nbsp;Already Existing Parent</label>
			                    <label class="col-sm-4"><input class="custom-radio" <?php echo ($Clean['ParentType'] == 'New' ? 'checked="checked"' : ''); ?> type="radio" id="NewParent" name="chkParentType" value="New">&nbsp;&nbsp;New Parent</label>
                          </div>
                        </div>
                        <div id="ParentDetailsContainer">
                            <div class="form-group">
                                <label for="FatherFirstName" class="col-lg-2 control-label">Father's First Name</label>
                                <div class="col-lg-4">
                                    <input class="form-control" type="text" maxlength="20" id="FatherFirstName" name="txtFatherFirstName" value="<?php echo $Clean['FatherFirstName']; ?>" />
                                    <input type="hidden" maxlength="20" name="txtParentID" value="<?php echo $Clean['ParentID']; ?>" />
                                </div>
                                <label for="FatherLastName" class="col-lg-2 control-label">Father's Last Name</label>
                                <div class="col-lg-4">
                                    <input class="form-control" type="text" maxlength="30" id="FatherLastName" name="txtFatherLastName" value="<?php echo $Clean['FatherLastName']; ?>" />
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="MotherFirstName" class="col-lg-2 control-label">Mother's First Name</label>
                                <div class="col-lg-4">
                                    <input class="form-control" type="text" maxlength="20" id="MotherFirstName" name="txtMotherFirstName" value="<?php echo $Clean['MotherFirstName']; ?>" />
                                </div>
                                <label for="MotherLastName" class="col-lg-2 control-label">Mother's Last Name</label>
                                <div class="col-lg-4">
                                    <input class="form-control" type="text" maxlength="30" id="MotherLastName" name="txtMotherLastName" value="<?php echo $Clean['MotherLastName']; ?>" />
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="FatherOccupation" class="col-lg-2 control-label">Father's Occupation</label>
                                <div class="col-lg-4">
                                    <input class="form-control" type="text" maxlength="30" id="FatherOccupation" name="txtFatherOccupation" value="<?php echo $Clean['FatherOccupation']; ?>" />
                                </div>
                                <label for="MotherOccupation" class="col-lg-2 control-label">Mother's Occupation</label>
                                <div class="col-lg-4">
                                    <input class="form-control" type="text" maxlength="30" id="MotherOccupation" name="txtMotherOccupation" value="<?php echo $Clean['MotherOccupation']; ?>" />
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="FatherOfficeName" class="col-lg-2 control-label">Father's Office Name</label>
                                <div class="col-lg-4">
                                    <input class="form-control" type="text" maxlength="150" id="FatherOfficeName" name="txtFatherOfficeName" value="<?php echo $Clean['FatherOfficeName']; ?>" />
                                </div>
                                <label for="MotherOfficeName" class="col-lg-2 control-label">Mother's Office Name</label>
                                <div class="col-lg-4">
                                    <input class="form-control" type="text" maxlength="150" id="MotherOfficeName" name="txtMotherOfficeName" value="<?php echo $Clean['MotherOfficeName']; ?>" />
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="FatherOfficeAddress" class="col-lg-2 control-label">Father's Office Address</label>
                                <div class="col-lg-4">
                                    <textarea class="form-control"  id="FatherOfficeAddress" name="txtFatherOfficeAddress"><?php echo $Clean['FatherOfficeAddress']; ?></textarea>
                                </div>
                                <label for="MotherOfficeAddress" class="col-lg-2 control-label">Mother's Office Address</label>
                                <div class="col-lg-4">
                                    <textarea class="form-control"  id="MotherOfficeAddress" name="txtMotherOfficeAddress"><?php echo $Clean['MotherOfficeAddress']; ?></textarea>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="ResidentailAddress" class="col-lg-2 control-label">Residentail Address</label>
                                <div class="col-lg-4">
                                    <textarea class="form-control"  id="ResidentailAddress" name="txtResidentailAddress"><?php echo $Clean['ResidentailAddress']; ?></textarea>
                                </div>
                                <label for="ResidentailCountry" class="col-lg-2 control-label">Residentail Country</label>
                                <div class="col-lg-4">
                                    <select class="form-control" name="drdResidentailCountry" id="ResidentailCountry">
<?php
                                    if (is_array($CountryList) && count($CountryList) > 0)
                                    {
                                        foreach ($CountryList as $CountryID => $CountryName) {
                                            echo '<option ' . ($Clean['ResidentailCountryID'] == $CountryID ? 'selected="selected"' : '') . ' value="' . $CountryID . '">' . $CountryName . '</option>';
                                        }
                                    }
?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="ResidentailState" class="col-lg-2 control-label">Residentail State</label>
                                <div class="col-lg-4">
                                    <select class="form-control" name="drdResidentailState" id="ResidentailState">
<?php
                                    if (is_array($ResidentailStateList) && count($ResidentailStateList) > 0)
                                    {
                                        foreach ($ResidentailStateList as $StateID => $StateName) {
                                            echo '<option ' . ($Clean['ResidentailStateID'] == $StateID ? 'selected="selected"' : '') . ' value="' . $StateID . '">' . $StateName . '</option>';
                                        }
                                    }
?>
                                    </select>
                                </div>
                                <label for="ResidentailDistrict" class="col-lg-2 control-label">Residentail District</label>
                                <div class="col-lg-4">
                                    <select class="form-control" name="drdResidentailDistrict" id="ResidentailDistrict">
<?php
                                    if (is_array($ResidentailDistrictList) && count($ResidentailDistrictList) > 0)
                                    {
                                        foreach ($ResidentailDistrictList as $DistrictID => $DistrictName) {
                                            echo '<option ' . ($Clean['ResidentailDistrictID'] == $DistrictID ? 'selected="selected"' : '') . ' value="' . $DistrictID . '">' . $DistrictName . '</option>';
                                        }
                                    }
?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="ResidentailCity" class="col-lg-2 control-label">Residentail City</label>
                                <div class="col-lg-4">
                                    <select class="form-control" name="drdResidentailCity" id="ResidentailCity">
<?php
                                    if (is_array($ResidentailCityList) && count($ResidentailCityList) > 0)
                                    {
                                        foreach ($ResidentailCityList as $CityID => $CityName) {
                                            echo '<option ' . ($Clean['ResidentailCityID'] == $CityID ? 'selected="selected"' : '') . ' value="' . $CityID . '">' . $CityName . '</option>';
                                        }
                                    }
?>
                                    </select>
                                </div>
                                <label for="ResidentailPinCode" class="col-lg-2 control-label">Residentail Pincode</label>
                                <div class="col-lg-4">
                                    <input class="form-control" type="text" maxlength="15" id="ResidentailPinCode" name="txtResidentailPinCode" value="<?php echo $Clean['ResidentailPinCode']; ?>" />
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="PermanentAddress" class="col-lg-2 control-label">Permanent Address</label>
                                <div class="col-lg-4">
                                    <textarea class="form-control"  id="PermanentAddress" name="txtPermanentAddress"><?php echo $Clean['PermanentAddress']; ?></textarea>
                                </div>
                                <label for="PermanentCountry" class="col-lg-2 control-label">Permanent Country</label>
                                <div class="col-lg-4">
                                    <select class="form-control" name="txtPermanentCountry" id="PermanentCountry">
<?php
                                    if (is_array($CountryList) && count($CountryList) > 0)
                                    {
                                        foreach ($CountryList as $CountryID => $CountryName) {
                                            echo '<option ' . ($Clean['PermanentCountryID'] == $CountryID ? 'selected="selected"' : '') . ' value="' . $CountryID . '">' . $CountryName . '</option>';
                                        }
                                    }
?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="PermanentState" class="col-lg-2 control-label">Permanent State</label>
                                <div class="col-lg-4">
                                    <select class="form-control" name="txtPermanentState" id="PermanentState">
<?php
                                    if (is_array($PermanentStateList) && count($PermanentStateList) > 0)
                                    {
                                        foreach ($PermanentStateList as $StateID => $StateName) {
                                            echo '<option ' . ($Clean['PermanentStateID'] == $StateID ? 'selected="selected"' : '') . ' value="' . $StateID . '">' . $StateName . '</option>';
                                        }
                                    }
?>
                                    </select>
                                </div>
                                <label for="PermanentDistrict" class="col-lg-2 control-label">Permanent District</label>
                                <div class="col-lg-4">
                                    <select class="form-control" name="txtPermanentDistrict" id="PermanentDistrict">
<?php
                                    if (is_array($PermanentDistrictList) && count($PermanentDistrictList) > 0)
                                    {
                                        foreach ($PermanentDistrictList as $DistrictID => $DistrictName) {
                                            echo '<option ' . ($Clean['PermanentDistrictID'] == $DistrictID ? 'selected="selected"' : '') . ' value="' . $DistrictID . '">' . $DistrictName . '</option>';
                                        }
                                    }
?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="PermanentCity" class="col-lg-2 control-label">Permanent City</label>
                                <div class="col-lg-4">
                                    <select class="form-control" name="txtPermanentCity" id="PermanentCity">
<?php
                                    if (is_array($PermanentCityList) && count($PermanentCityList) > 0)
                                    {
                                        foreach ($PermanentCityList as $CityID => $CityName) {
                                            echo '<option ' . ($Clean['PermanentCityID'] == $CityID ? 'selected="selected"' : '') . ' value="' . $CityID . '">' . $CityName . '</option>';
                                        }
                                    }
?>
                                    </select>
                                </div>
                                <label for="PermanentPinCode" class="col-lg-2 control-label">Permanent Pincode</label>
                                <div class="col-lg-4">
                                    <input class="form-control" type="text" maxlength="6" id="PermanentPinCode" name="txtPermanentPinCode" value="<?php echo $Clean['PermanentPinCode']; ?>" />
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="PhoneNumber" class="col-lg-2 control-label">Phone Number</label>
                                <div class="col-lg-4">
                                    <input class="form-control" type="text" maxlength="15" id="PhoneNumber" name="txtPhoneNumber" value="<?php echo $Clean['PhoneNumber']; ?>" />
                                </div>
                                <label for="ParentAadharNumber" class="col-lg-2 control-label">Aadhar Number</label>
                                <div class="col-lg-4">
                                    <input class="form-control" type="text" maxlength="12" id="ParentAadharNumber" name="txtParentAadharNumber" value="<?php echo ($Clean['ParentAadharNumber'] ? $Clean['ParentAadharNumber'] : ''); ?>" />
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="FatherMobileNumber" class="col-lg-2 control-label">Father Mobile Number</label>
                                <div class="col-lg-4">
                                    <input class="form-control" type="text" maxlength="15" id="FatherMobileNumber" name="txtFatherMobileNumber" value="<?php echo $Clean['FatherMobileNumber']; ?>" />
                                </div>
                                <label for="MotherMobileNumber" class="col-lg-2 control-label">Mother Mobile Number</label>
                                <div class="col-lg-4">
                                    <input class="form-control" type="text" maxlength="15" id="MotherMobileNumber" name="txtMotherMobileNumber" value="<?php echo $Clean['MotherMobileNumber']; ?>" />
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="FatherEmail" class="col-lg-2 control-label">Father Email</label>
                                <div class="col-lg-4">
                                    <input class="form-control" type="text" maxlength="150" id="FatherEmail" name="txtFatherEmail" value="<?php echo $Clean['FatherEmail']; ?>" />
                                </div>
                                <label for="MotherEmail" class="col-lg-2 control-label">Mother Email</label>
                                <div class="col-lg-4">
                                    <input class="form-control" type="text" maxlength="150" id="MotherEmail" name="txtMotherEmail" value="<?php echo $Clean['MotherEmail']; ?>" />
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                        <div class="col-sm-offset-2 col-lg-10">
                            <input type="hidden" name="hdnProcess" value="1" />
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
<link href="../vendor/jquery-ui/jquery-ui.min.css" rel="stylesheet">

<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-3-typeahead/4.0.2/bootstrap3-typeahead.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-3-typeahead/4.0.2/bootstrap3-typeahead.min.js"></script>

<script type="text/javascript">
$(document).ready(function(){ 
    
	$('#SiblingName').typeahead({
		displayKey: "FirstName", 
		templates: {
						empty: ['<a class="list-group-item">Nothing found.</a>'],
						header: ['<div class="input-group input-results-dropdown">'],
                		suggestion: function (data) { return '<a class="list-group-item">'  + data.first_name + ' ' +data.last_name + '</a>' } 
				   }, 
        source: function (query, process) {
            return $.get('/xhttp_calls/search_sibling_name_list.php?Query=' + query, function (data) {
				//console.log(data);
                return process(data.search_results);
            });
        }
    });
	
    $(".select-date").datepicker({
		changeMonth: true,
		changeYear: true,
		dateFormat: 'dd/mm/yy'
    });

    $('#Country').change(function(){
        $('#State').load('/xhttp_calls/get_all_states.php', { SelectedCountryID: $(this).val() });
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
	
    $('#ResidentailCountry').change(function(){
        $('#ResidentailState').load('/xhttp_calls/get_all_states.php', { SelectedCountryID: $(this).val() });
    });
    
    $('#ResidentailState').change(function(){
		var StateID = parseInt($(this).val());
		
		if (StateID <= 0)
		{
			$('#ResidentailDistrict').html('<option value="0">Select District</option>');
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
				$('#ResidentailDistrict').html(ResultArray[1]);
			}
		 });
	});
    
    $('#ResidentailDistrict').change(function(){
		var DistrictID = parseInt($(this).val()); 
		var StateID = parseInt($('#ResidentailState').val());
		
		if (DistrictID <= 0)
		{
			$('#ResidentailCity').html('<option value="0">Select City</option>');
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
				$('#ResidentailCity').html(ResultArray[1]);
			}
		 });
	});

    $('#PermanentCountry').change(function(){
        $('#PermanentState').load('/xhttp_calls/get_all_states.php', { SelectedCountryID: $(this).val() });
    });

    $('#PermanentState').change(function(){
		var StateID = parseInt($(this).val());
		
		if (StateID <= 0)
		{
			$('#PermanentDistrict').html('<option value="0">Select District</option>');
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
				$('#PermanentDistrict').html(ResultArray[1]);
			}
		 });
	});
    
    $('#PermanentDistrict').change(function(){
		var DistrictID = parseInt($(this).val());
		var StateID = parseInt($('#PermanentState').val());
		
		if (DistrictID <= 0)
		{
			$('#PermanentCity').html('<option value="0">Select City</option>');
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
				$('#PermanentCity').html(ResultArray[1]);
			}
		 });
	});
	
	$('#Class').change(function(){
		var ClassID = parseInt($(this).val());
		
		if (ClassID <= 0)
		{
			$('#Section').html('<option value="0">Select Section</option>');
			return;
		}
		
		$.post("/xhttp_calls/get_sections_by_classs.php", {SelectedClassID:ClassID}, function(data){
		
			ResultArray = data.split("|*****|");
			
			if (ResultArray[0] == 'error')
			{
				alert (ResultArray[1]);
				return false;
			}
			else
			{
				$('#Section').html(ResultArray[1]);
			}
		 });
	});
	
	$(document).on('change', ':file', function() {
		var input = $(this),
			numFiles = input.get(0).files ? input.get(0).files.length : 1,
			label = input.val().replace(/\\/g, '/').replace(/.*\//, '');
		input.trigger('fileselect', [numFiles, label]);
	});
	
	$(':file').on('fileselect', function(event, numFiles, label) {
		$('#abc').text('  '+label);
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

    $("input[name='chkParentType']").click(function() {
        var ParentType = $(this).val();
        if (ParentType == 'Existing') 
        {
            var URL = 'search_existing_parent.php';
            var NewWindow = window.open(URL, 'Search Existing Parent', 'height=auto, width=1400');

            return false;
        }
    });
    
    $('#NewParent').click(function() {
        if (!confirm('Parent details will be lost, do you really want to continue?'))
        {
            return false;
        }

        $('#NewParent').prop('checked', true);
        $('#ParentDetailsContainer').html('');
        $('#ParentDetailsContainer').load('/xhttp_calls/get_parent_details.php', { SelectedParentID: 0 });
    });
});

window.CallParent = function(ParentID) {
    $('#AlreadyExistingParent').prop('checked', true);
    $('#ParentDetailsContainer').html('');
    $('#ParentDetailsContainer').load('/xhttp_calls/get_parent_details.php', { SelectedParentID: ParentID });
}
</script>
<script type="text/javascript">
    function readURL(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();

            $('.EventImage').removeAttr('style');
            reader.onload = function (e) {
                $('.img-responsive').attr('src', e.target.result);
            }

            reader.readAsDataURL(input.files[0]);
        }
    }
</script>
</body>
</html>
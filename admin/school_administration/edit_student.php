<?php
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

if ($LoggedUser->HasPermissionForTask(TASK_EDIT_STUDENT) !== true)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}

if (isset($_POST['btnCancel']))
{
    header('location:student_list.php');
    exit;
}

$Clean = array();

$Clean['StudentID'] = 0;

if (isset($_GET['StudentID']))
{
    $Clean['StudentID'] = (int) $_GET['StudentID'];
}
elseif (isset($_POST['hdnStudentID']))
{
    $Clean['StudentID'] = (int) $_POST['hdnStudentID'];
}

if ($Clean['StudentID'] <= 0)
{
    header('location:../error.php');
    exit;
}   

try
{
    $StudentToEdit = new StudentDetail($Clean['StudentID']);    
}
catch (ApplicationDBException $e)
{
    header('location:../error1.php');
    exit;
}
catch (Exception $e)
{
    header('location:../error2.php');
    exit;
}

$HasErrors = false;
    
$StudentTypeList = array('New'=>'New', 'Old'=>'Old');
$BloodGroupList = array('A+'=>'A+', 'A-'=>'A-', 'B+'=>'B+', 'AB+'=>'AB+', 'AB-'=>'AB-', 'O+'=>'O+', 'O-'=>'O-');
$MotherTongueList = array('Hindi'=>'Hindi', 'English'=>'English');

$acceptable_extensions = array('jpeg', 'jpg', 'png', 'gif');
$acceptable_mime_types = array(
    'image/jpeg',
    'image/jpg', 
    'image/png', 
    'image/gif' 
);

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

$Clean['UploadFile'] = array();

$Clean['Process'] = 0;

$Clean['RollNumber'] = 0;
$Clean['EnrollmentID'] = '';
$Clean['FeeCode'] = '';
$Clean['StudentType'] = 'New';
$Clean['Status'] = 'Active';
$Clean['DateFrom'] = '';
$Clean['ColourHouseID'] = 0;
$Clean['ClassID'] = 0;
$Clean['ClassSectionID'] = 0;

$Clean['FirstName'] = '';
$Clean['LastName'] = '';

$Clean['DOB'] = '';
$Clean['Gender'] = 'Male';
$Clean['BloodGroup'] = '';
$Clean['Category'] = 'General';

$Clean['IsEWS'] = 0;
$Clean['HasDisability'] = 0;
$Clean['IsSingleGirl'] = 0;

$Clean['Address1'] = '';
$Clean['Address2'] = '';

$Clean['CountryID'] = key($CountryList);
$StateList = State::GetAllStates($Clean['CountryID']);

$Clean['StateID'] = key($StateList);

$DistrictList = City::GetAllDistricts($Clean['StateID']);
$Clean['DistrictID'] = key($DistrictList);

$CityList = City::GetAllCities($Clean['StateID'], $Clean['DistrictID']);
$Clean['CityID'] = key($CityList);

$Clean['Pincode'] = '';

$Clean['AdmissionDate'] = '';
$Clean['MobileNumber'] = '';
$Clean['Email'] = '';
$Clean['AadharNumber'] = 0;

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

$Clean['FatherFirstName'] = '';
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
$Clean['PermanentCountryID'] = 0;

$PermanentStateList = State::GetAllStates($Clean['PermanentCountryID']);
$Clean['PermanentStateID'] = key($PermanentStateList);

$PermanentDistrictList = City::GetAllDistricts($Clean['PermanentStateID']);
$Clean['PermanentDistrictID'] = key($PermanentDistrictList);

$PermanentCityList = City::GetAllCities($Clean['PermanentStateID'], $Clean['PermanentDistrictID']);
$Clean['PermanentCityID'] = key($PermanentCityList);

$Clean['PermanentPinCode'] = '';

$Clean['PhoneNumber'] = '';
$Clean['FatherMobileNumber'] = '';
$Clean['MotherMobileNumber'] = '';
$Clean['FatherEmail'] = '';
$Clean['MotherEmail'] = '';

$Clean['ParentAadharNumber'] = 0;
$Clean['IsActive'] = 1;

$Clean['ParentType'] = 'New'; 
$Clean['SiblingName'] = '';
$Clean['ParentUserId'] = '';

$Clean['ParentID'] = 0;

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

        if (isset($_POST['txtGender']))
        {
            $Clean['Gender'] = strip_tags(trim($_POST['txtGender']));
        }

        if (isset($_POST['txtBloodGroup']))
        {
            $Clean['BloodGroup'] = strip_tags(trim($_POST['txtBloodGroup']));
        }

        if (isset($_POST['txtCategory']))
        {
            $Clean['Category'] = strip_tags(trim($_POST['txtCategory']));
        }

        if (isset($_POST['txtIsEWS']))
        {
            $Clean['IsEWS'] = 1;
        }

        if (isset($_POST['txtHasDisability']))
        {
            $Clean['HasDisability'] = 1;
        }

        if (isset($_POST['txtIsSingleGirl']))
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

        if (isset($_POST['txtAdmissionDate']))
        {
            $Clean['AdmissionDate'] = strip_tags(trim($_POST['txtAdmissionDate']));
        }

        if (isset($_FILES['fleEventImage']) && is_array($_FILES['fleEventImage']))
        {
            $Clean['UploadFile'] = $_FILES['fleEventImage'];
        }

        if (isset($_POST['txtMobileNumber']))
        {
            $Clean['MobileNumber'] = strip_tags(trim($_POST['txtMobileNumber']));
        }

        if (isset($_POST['txtEmail']))
        {
            $Clean['Email'] = strip_tags(trim($_POST['txtEmail']));
        }

        if (isset($_POST['txtAadharNumber']))
        {
            $Clean['AadharNumber'] = (int) $_POST['txtAadharNumber'];
        }

        if (isset($_POST['txtLastClass']))
        {
            $Clean['LastClass'] = strip_tags(trim($_POST['txtLastClass']));
        }

        if (isset($_POST['txtLastSchool']))
        {
            $Clean['LastSchool'] = strip_tags(trim($_POST['txtLastSchool']));
        }

        if (isset($_POST['txtLastSchoolBoard']))
        {
            $Clean['LastSchoolBoard'] = strip_tags(trim($_POST['txtLastSchoolBoard']));
        }

        if (isset($_POST['txtTCReceived']))
        {
            $Clean['TCReceived'] = (int) $_POST['txtTCReceived'];
        }

        if (isset($_POST['txtTCDate']))
        {
            $Clean['TCDate'] = strip_tags(trim($_POST['txtTCDate']));
        }

        if (isset($_POST['txtSubjectsProposed']))
        {
            $Clean['SubjectsProposed'] = strip_tags(trim($_POST['txtSubjectsProposed']));
        }

        if (isset($_POST['txtMotherTongue']))
        {
            $Clean['MotherTongue'] = strip_tags(trim($_POST['txtMotherTongue']));
        }

        if (isset($_POST['txtHomeTown']))
        {
            $Clean['HomeTown'] = strip_tags(trim($_POST['txtHomeTown']));
        }

        if (isset($_POST['txtLastExamStatus']))
        {
            $Clean['LastExamStatus'] = strip_tags(trim($_POST['txtLastExamStatus']));
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

        if (isset($_POST['txtStatus']))
        {
            $Clean['Status'] = strip_tags(trim($_POST['txtStatus']));
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

        if (isset($_POST['drdPermanentCity']))
        {
            $Clean['PermanentCityID'] = (int) $_POST['drdPermanentCity'];
        }

        if (isset($_POST['drdPermanentDistrict']))
        {
            $Clean['PermanentDistrictID'] = (int) $_POST['drdPermanentDistrict'];
        }

        if (isset($_POST['drdPermanentState']))
        {
            $Clean['PermanentStateID'] = (int) $_POST['drdPermanentState'];
        }

        if (isset($_POST['drdPermanentCountry']))
        {
            $Clean['PermanentCountryID'] = (int) $_POST['drdPermanentCountry'];
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

        if (isset($_POST['txtDateFrom']))
        {
            $Clean['DateFrom'] = strip_tags(trim($_POST['txtDateFrom']));
        }

        $NewRecordValidator = new Validator();

        $NewRecordValidator->ValidateInSelect($Clean['StudentType'], $StudentTypeList, 'Unknown error in student type, please try again.');
        
        if ($Clean['EnrollmentID'] != '')
        {
            $NewRecordValidator->ValidateStrings($Clean['EnrollmentID'], 'Enrollment ID is required and should be between 1 and 50 characters.', 1, 50);    
        }
        
        if ($Clean['FeeCode'] != '')
        {
            $NewRecordValidator->ValidateStrings($Clean['FeeCode'], 'Fee Code is required and should be between 1 and 20 characters.', 3, 20);
        }

        if ($Clean['ColourHouseID'] > 0) 
        {
            $NewRecordValidator->ValidateInSelect($Clean['ColourHouseID'], $ColorHouseList, 'Unknown error in house, please try again.');
        }

       if ($NewRecordValidator->ValidateInSelect($Clean['ClassID'], $ClassList, 'Unknown error, please try again.1'))
        {
            $ClassSectionsList = ClassSections::GetClassSections($Clean['ClassID']);
            $NewRecordValidator->ValidateInSelect($Clean['ClassSectionID'], $ClassSectionsList, 'Unknown error, please try again.2');
        }

        $NewRecordValidator->ValidateStrings($Clean['FirstName'], "First Name is required and should be between 1 and 20 characters.", 1, 20);
        
        if ($Clean['LastName'] > 0) 
        {
            $NewRecordValidator->ValidateStrings($Clean['LastName'], "Last Name is required and should be between 1 and 30 characters.", 1, 30);
        }
        
        if ($Clean['DOB'] > 0) 
        {
            $NewRecordValidator->ValidateDate($Clean['DOB'], "Please enter a valid date of birth.");
        }
        
        if ($Clean['Address1'] != '')
        {
            $NewRecordValidator->ValidateStrings($Clean['Address1'], "Address is required and should be between 5 and 150 characters.", 5, 150);
        }
        
        if ($Clean['Address2'] != '')
        {
            $NewRecordValidator->ValidateStrings($Clean['Address2'], "Address is required and should be between 5 and 150 characters.", 5, 150);
        }

        if ($Clean['Gender'] != 'Male' && $Clean['Gender'] != 'Female')
        {
            $NewRecordValidator->AttachTextError('Unknown error  in gender, please try again.');
        }

        // $NewRecordValidator->ValidateInSelect($Clean['BloodGroup'], $BloodGroupList, 'Unknown error in blood group, please try again.');

        if ($Clean['Category'] != 'General' && $Clean['Category'] != 'OBC' && $Clean['Category'] != 'SC' && $Clean['Category'] != 'ST')
        {
            $NewRecordValidator->AttachTextError('Unknown error in category, please try again.');
        }

        $NewRecordValidator->ValidateInSelect($Clean['CountryID'], $CountryList, 'Unknown error in country, please try again.');
        
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
            $NewRecordValidator->ValidateStrings($Clean['Pincode'], "Pincode is required and should be between 3 and 6 characters.", 3, 6);
        }
        
        if ($Clean['AadharNumber'] != '')
        {
            $NewRecordValidator->ValidateInteger($Clean['AadharNumber'], "please enter valid Aadhar Number.",1);
        }
        
        // if ($Clean['AdmissionDate'] != '')
        // {
        //     $NewRecordValidator->ValidateDate($Clean['AdmissionDate'], "Please enter a valid date.");
        // }
    
        $FileName = '';
        $FileExtension = '';

        if ($Clean['UploadFile']['name'] != '') 
        {

            if ($Clean['UploadFile']['size'] > MAX_UPLOADED_FILE_SIZE || $Clean['UploadFile']['size'] <= 0) 
            {
                $NewRecordValidator->AttachTextError('File size cannot be greater than ' . (MAX_UPLOADED_FILE_SIZE / 1024 /1024) . ' MB.');
            }

            $FileExtension = strtolower(pathinfo($Clean['UploadFile']['name'], PATHINFO_EXTENSION));

            if (!in_array($Clean['UploadFile']['type'], $acceptable_mime_types) || !in_array($FileExtension, $acceptable_extensions))
            {
               $NewRecordValidator->AttachTextError('Only ' . implode(', ', $acceptable_extensions) . ' files are allowed.');
            }

            if (strlen($Clean['UploadFile']['name']) > MAX_UPLOADED_FILE_NAME_LENGTH)
            {
                $NewRecordValidator->AttachTextError('Uploaded file name cannot be greater than ' . MAX_UPLOADED_FILE_NAME_LENGTH . ' chars.');
            }   
        }

        $NewRecordValidator->ValidateStrings($Clean['MobileNumber'], "Mobile Number is required and should be between 9 and 15 characters.", 9, 15);
        
        if ($Clean['Email'] != '')
        {
            $NewRecordValidator->ValidateEmail($Clean['Email'], "Please enter valid Email and it is required and should be between 5 and 150 characters.", 5, 150);
        }
        
        if ($Clean['LastClass'] != '')
        {
            $NewRecordValidator->ValidateStrings($Clean['LastClass'], "Last Class is required and should be between 1 and 25 characters.", 1, 25);
        }
        
        if ($Clean['LastSchool'] != '')
        {
            $NewRecordValidator->ValidateStrings($Clean['LastSchool'], "Last School is required and should be between 5 and 150 characters.", 5, 150);
        }
        
        if ($Clean['LastSchoolBoard'] != 'CBSE' && $Clean['LastSchoolBoard'] != 'ICSE' && $Clean['LastSchoolBoard'] != 'UP')
        {
            $NewRecordValidator->AttachTextError('Unknown error in last school board, please try again.');
        }
        
        // if ($Clean['TCDate'] != '')
        // {
        //     $NewRecordValidator->ValidateDate($Clean['TCDate'], "Please enter a valid TC date.");
        // }
        
        if ($Clean['LastExamStatus'] != 'Passed' && $Clean['LastExamStatus'] != 'Failed' && $Clean['LastExamStatus'] != 'Awaited')
        {
            $NewRecordValidator->AttachTextError('Unknown error in last exam status, please try again.');
        }
        
        if ($Clean['ParentType'] != 'New') 
        {
            $NewRecordValidator->ValidateInteger($Clean['ParentID'], 'Invalid parent id.', 1);
        }

        if ($Clean['Status'] != 'Active' && $Clean['Status'] != 'Suspended' && $Clean['Status'] != 'Terminated' && $Clean['Status'] != 'InActive' && $Clean['Status'] != 'Passout')
        {
            $NewRecordValidator->AttachTextError('Unknown error in student status, please try again.');
			
			if ($StudentToEdit->GetStatus() != $Clean['Status'])
			{
				$NewRecordValidator->ValidateDate($Clean['DateFrom'], "Please enter a valid a date.");
			}
        }

        $NewRecordValidator->ValidateStrings($Clean['FatherFirstName'], "Father First Name is required and should be between 1 and 20 characters.", 1, 20);
        
        if ($Clean['FatherLastName'] != '')
        {
             $NewRecordValidator->ValidateStrings($Clean['FatherLastName'], "Father Last Name is required and should be between 1 and 30 characters.", 1, 30);
        }
        
        $NewRecordValidator->ValidateStrings($Clean['MotherFirstName'], "Mother First Name is required and should be between 3 and 20 characters.", 3, 20);
        
        if ($Clean['MotherLastName'] != '')
        {
            $NewRecordValidator->ValidateStrings($Clean['MotherLastName'], "Mother Last Name is required and should be between 3 and 30 characters.", 3, 30);
        }
        
        if ($Clean['FatherOccupation'] != '')
        {
            $NewRecordValidator->ValidateStrings($Clean['FatherOccupation'], "Father Occupation is required and should be between 3 and 30 characters.", 3, 30);
        }
        
        if ($Clean['MotherOccupation'] != '')
        {
             $NewRecordValidator->ValidateStrings($Clean['MotherOccupation'], "Mother Occupation is required and should be between 3 and 30 characters.", 3, 30);
        }
        
        if ($Clean['FatherOfficeName'] != '')
        {
            $NewRecordValidator->ValidateStrings($Clean['FatherOfficeName'], "Father Office Name is required and should be between 1 and 150 characters.", 1, 150);
        }
        
        if ($Clean['MotherOfficeName'] != '')
        {
             $NewRecordValidator->ValidateStrings($Clean['MotherOfficeName'], "Mother Office Name is required and should be between 3 and 150 characters.", 3, 150);
        }
        
        if ($Clean['FatherOfficeAddress'] != '')
        {
            $NewRecordValidator->ValidateStrings($Clean['FatherOfficeAddress'], "Father Office Address is required and should be between 3 and 500 characters.", 3, 500);
        }
        
        if ($Clean['MotherOfficeAddress'] != '')
        {
            $NewRecordValidator->ValidateStrings($Clean['MotherOfficeAddress'], "Mother Office Address is required and should be between 3 and 500 characters.", 3, 500);
        }
        
        if ($Clean['ResidentailAddress'] != '')
        {
             $NewRecordValidator->ValidateStrings($Clean['ResidentailAddress'], "Residentail Address is required and should be between 3 and 300 characters.", 3, 300);
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
            $NewRecordValidator->ValidateStrings($Clean['ResidentailPinCode'], "Residentail PinCode is required and should be between 3 and 6 characters.", 3, 6);
        }
        
        if ($Clean['PermanentAddress'] != '')
        {
            $NewRecordValidator->ValidateStrings($Clean['PermanentAddress'], "Permanent Address is required and should be between 3 and 300 characters.", 3, 300);
        }
        
        // if ($NewRecordValidator->ValidateInSelect($Clean['PermanentCountryID'], $CountryList, 'Unknown error, please try again.'))
        // {
        //     $PermanentStateList = State::GetAllStates($Clean['PermanentCountryID']);
            
        //     if ($NewRecordValidator->ValidateInSelect($Clean['PermanentStateID'], $PermanentStateList, 'Unknown error, please try again.'))
        //     {
        //         $PermanentDistrictList = City::GetAllDistricts($Clean['PermanentStateID']);
                
        //         if ($NewRecordValidator->ValidateInSelect($Clean['PermanentDistrictID'], $PermanentDistrictList, 'Unknown error, please try again.'))
        //         {
        //             $PermanentCityList = City::GetAllCities($Clean['PermanentStateID'], $Clean['PermanentDistrictID']);
        //             $NewRecordValidator->ValidateInSelect($Clean['PermanentCityID'], $PermanentCityList, 'Unknown error, please try again.');   
        //         }
        //     }
        // }
        
        if ($Clean['PermanentPinCode'] != '')
        {
            $NewRecordValidator->ValidateStrings($Clean['PermanentPinCode'], "Permanent PinCode is required and should be between 3 and 6 characters.", 3, 6);
        }
        
        if ($Clean['PhoneNumber'] != '')
        {
            $NewRecordValidator->ValidateStrings($Clean['PhoneNumber'], "Phone Number is required and should be between 3 and 15 characters.", 3, 15);
        }
        
        if ($Clean['ParentAadharNumber'] != '')
        {
            $NewRecordValidator->ValidateInteger($Clean['ParentAadharNumber'], "please enter valid Aadhar Number for Parent.",1);
        }
        
        if ($Clean['FatherMobileNumber'] != '')
        {
            $NewRecordValidator->ValidateStrings($Clean['FatherMobileNumber'], "Father Mobile Number is required and should be between 3 and 15 characters.", 3, 15);
        }
        
        if ($Clean['MotherMobileNumber'] != '')
        {
            $NewRecordValidator->ValidateStrings($Clean['MotherMobileNumber'], "Mother Mobile Number is required and should be between 3 and 15 characters.", 3, 15);
        }
        
        if ($Clean['FatherEmail'] != '')
        {
             $NewRecordValidator->ValidateEmail($Clean['FatherEmail'], "Father Email is required and should be between 3 and 150 characters.", 3, 150);
        }
        
        if ($Clean['MotherEmail'] != '')
        {
            $NewRecordValidator->ValidateEmail($Clean['MotherEmail'], "Mother Email is required and should be between 3 and 150 characters.", 3, 150);
        }
    
        $FileName = $Clean['UploadFile']['name'];
        if ($FileName != '') 
        {
            unlink(SITE_FS_PATH . '/site_images/student_images/' .$StudentToEdit->GetStudentID() .'/' . $StudentToEdit->GetStudentPhoto());
        }
        
        if ($NewRecordValidator->HasNotifications())
        {
            $HasErrors = true;
            break;
        }
        
        if ($Clean['ParentType'] != 'New' && $Clean['ParentID'] > 0)
        {
            $StudentToEdit->SetParentID($Clean['ParentID']);
        }
        
        $StudentToEdit->SetRollNumber($Clean['RollNumber']);
        $StudentToEdit->SetEnrollmentID($Clean['EnrollmentID']);
        $StudentToEdit->SetStudentType($Clean['StudentType']);
        $StudentToEdit->SetStatus($Clean['Status']);
        $StudentToEdit->SetColourHouseID($Clean['ColourHouseID']);
        $StudentToEdit->SetClassSectionID($Clean['ClassSectionID']); 
        
        $StudentToEdit->SetFirstName($Clean['FirstName']);
        $StudentToEdit->SetLastName($Clean['LastName']);
        $StudentToEdit->SetDOB(date('Y-m-d', strtotime(DateProcessing::ToggleDateDayAndMonth(($Clean['DOB'])))));
        $StudentToEdit->SetGender($Clean['Gender']);        
        $StudentToEdit->SetBloodGroup($Clean['BloodGroup']);
        $StudentToEdit->SetCategory($Clean['Category']);

        $StudentToEdit->SetIsEWS($Clean['IsEWS']);
        $StudentToEdit->SetHasDisability($Clean['HasDisability']);
        $StudentToEdit->SetIsSingleGirl($Clean['IsSingleGirl']);

        $StudentToEdit->SetAddress1($Clean['Address1']);
        $StudentToEdit->SetAddress2($Clean['Address2']);
        $StudentToEdit->SetCityID($Clean['CityID']);
        $StudentToEdit->SetDistrictID($Clean['DistrictID']);
        $StudentToEdit->SetStateID($Clean['StateID']);
        $StudentToEdit->SetCountryID($Clean['CountryID']);
        $StudentToEdit->SetPincode($Clean['Pincode']);

        $StudentToEdit->SetAdmissionDate(date('Y-m-d', strtotime(DateProcessing::ToggleDateDayAndMonth(($Clean['AdmissionDate'])))));
        $StudentToEdit->SetMobileNumber($Clean['MobileNumber']);
        $StudentToEdit->SetEmail($Clean['Email']);
        $StudentToEdit->SetAadharNumber($Clean['AadharNumber']);

        $StudentToEdit->SetLastClass($Clean['LastClass']);
        $StudentToEdit->SetLastSchool($Clean['LastSchool']);
        $StudentToEdit->SetLastSchoolBoard($Clean['LastSchoolBoard']);
        $StudentToEdit->SetTCReceived($Clean['TCReceived']);
        $StudentToEdit->SetTCDate(date('Y-m-d', strtotime(DateProcessing::ToggleDateDayAndMonth(($Clean['TCDate'])))));

        $StudentToEdit->SetSubjectsProposed($Clean['SubjectsProposed']);
        $StudentToEdit->SetMotherTongue($Clean['MotherTongue']);
        $StudentToEdit->SetHomeTown($Clean['HomeTown']);

        $StudentToEdit->SetLastExamStatus($Clean['LastExamStatus']);
        $StudentToEdit->SetLastExamPercentage($Clean['LastExamPercentage']);

		if ($Clean['DateFrom'] != '')
		{
			$StudentToEdit->SetDateFrom(date('Y-m-d', strtotime(DateProcessing::ToggleDateDayAndMonth(($Clean['DateFrom'])))));
		}

        $NewParentDetail = new ParentDetail();

        $NewParentDetail->SetFatherFirstName($Clean['FatherFirstName']);
        $NewParentDetail->SetFeeCode($Clean['FeeCode']);
        $NewParentDetail->SetFatherLastName($Clean['FatherLastName']);
        $NewParentDetail->SetMotherFirstName($Clean['MotherFirstName']);
        $NewParentDetail->SetMotherLastName($Clean['MotherLastName']);

        $NewParentDetail->SetFatherOccupation($Clean['FatherOccupation']);
        $NewParentDetail->SetMotherOccupation($Clean['MotherOccupation']);
        $NewParentDetail->SetFatherOfficeName($Clean['FatherOfficeName']);
        $NewParentDetail->SetMotherOfficeName($Clean['MotherOfficeName']);
        $NewParentDetail->SetFatherOfficeAddress($Clean['FatherOfficeAddress']);
        $NewParentDetail->SetMotherOfficeAddress($Clean['MotherOfficeAddress']);

        $NewParentDetail->SetResidentailAddress($Clean['ResidentailAddress']);
        $NewParentDetail->SetResidentailCityID($Clean['ResidentailCityID']);
        $NewParentDetail->SetResidentailDistrictID($Clean['ResidentailDistrictID']);
        $NewParentDetail->SetResidentailStateID($Clean['ResidentailStateID']);
        $NewParentDetail->SetResidentailCountryID($Clean['ResidentailCountryID']);
        $NewParentDetail->SetResidentailPinCode($Clean['ResidentailPinCode']);

        $NewParentDetail->SetPermanentAddress($Clean['PermanentAddress']);
        $NewParentDetail->SetPermanentCityID($Clean['PermanentCityID']);
        $NewParentDetail->SetPermanentDistrictID($Clean['PermanentDistrictID']);
        $NewParentDetail->SetPermanentStateID($Clean['PermanentStateID']);
        $NewParentDetail->SetPermanentCountryID($Clean['PermanentCountryID']);
        $NewParentDetail->SetPermanentPinCode($Clean['PermanentPinCode']);

        $NewParentDetail->SetPhoneNumber($Clean['PhoneNumber']);
        $NewParentDetail->SetFatherMobileNumber($Clean['FatherMobileNumber']);
        $NewParentDetail->SetMotherMobileNumber($Clean['MotherMobileNumber']);

        $NewParentDetail->SetFatherEmail($Clean['FatherEmail']);
        $NewParentDetail->SetMotherEmail($Clean['MotherEmail']);

        $NewParentDetail->SetAadharNumber($Clean['ParentAadharNumber']);
        $NewParentDetail->SetIsActive($Clean['IsActive']);

          // Generate a Unique Name for the uploaded document

        if($FileName != '') 
        {
            $FileName = md5(uniqid(rand(), true) . $StudentToEdit->GetStudentID()) . '.' . $FileExtension;
            $StudentToEdit->SetStudentPhoto($FileName);
            // var_dump($FileName);exit;
        }
        
        $StudentToEdit->SetCreateUserID($LoggedUser->GetUserID());
        
        // Generate a Unique Name for the uploaded document
        
        $ParentObject = new ParentDetail($StudentToEdit->GetParentID());
        
        if ($ParentObject->GetUserName() == '')
        {
            $UniqueID = Helpers::GenerateUniqueAddedID($Clean['FatherFirstName'] . $Clean['FatherLastName'], date('Y-m-d', strtotime(DateProcessing::ToggleDateDayAndMonth(($Clean['DOB'])))));
		
    		if ($UniqueID != '')
    		{
    			$NewParentDetail->SetUserName($UniqueID);
    			
    			if ($StudentToEdit->Save($NewParentDetail))
    			{
    				if (!Helpers::SaveUniqueID($UniqueID, $Clean['ParentAadharNumber']))
                    {
                        error_log('Criticle Error: Generated Unique ID could not be saved into Added Central DB. StudentID: ' . $StudentToEdit->GetStudentID() . ' UniqueID: ' . $UniqueID);
                    }
    			}
    			else
    			{
    				$NewRecordValidator->AttachTextError(ProcessErrors($StudentToEdit->GetLastErrorCode()));
    				$HasErrors = true;
    
    				break;
    			}
    		}
    		else
            {
                error_log('Criticle Error: Generated Unique ID found balnk. StudentID: ' . $StudentToEdit->GetStudentID());
            }
        }
        else
        {
            $NewParentDetail->SetUserName($ParentObject->GetUserName());
            
            if (!$StudentToEdit->Save($NewParentDetail))
            {
                $NewRecordValidator->AttachTextError(ProcessErrors($StudentToEdit->GetLastErrorCode()));
                $HasErrors = true;
    
                break;
            }   
        }

        if ($FileName != '') 
        {
            $UniqueUserFileUploadDirectory = SITE_FS_PATH . '/site_images/student_images/'.$StudentToEdit->GetStudentID().'/';

            if (!is_dir($UniqueUserFileUploadDirectory))
            {
                mkdir($UniqueUserFileUploadDirectory);
            }
            
            move_uploaded_file($Clean['UploadFile']['tmp_name'], $UniqueUserFileUploadDirectory . $FileName);
        }   
        
        header('location:students_list.php?Mode=UD');
        exit;
    break;

    case 2:
      
        $Clean['RollNumber'] = $StudentToEdit->GetRollNumber();
        $Clean['EnrollmentID'] = $StudentToEdit->GetEnrollmentID();
        $Clean['StudentType'] = $StudentToEdit->GetStudentType();
        $Clean['Status'] = $StudentToEdit->GetStatus();
        $Clean['ColourHouseID'] = $StudentToEdit->GetColourHouseID();
        $Clean['ClassSectionID'] = $StudentToEdit->GetClassSectionID();

        $ClassSectionDetails = new ClassSections($StudentToEdit->GetClassSectionID());
        
        $Clean['ClassID'] = $ClassSectionDetails->GetClassID();
        $ClassSectionsList = ClassSections::GetClassSections($Clean['ClassID']);

        $Clean['FirstName'] = $StudentToEdit->GetFirstName();
        $Clean['LastName'] = $StudentToEdit->GetLastName();
        $Clean['DOB'] = date('d/m/Y', strtotime($StudentToEdit->GetDOB()));
        $Clean['Gender'] = $StudentToEdit->GetGender();        
        $Clean['BloodGroup'] = $StudentToEdit->GetBloodGroup();
        $Clean['Category'] = $StudentToEdit->GetCategory();

        $Clean['IsEWS'] = $StudentToEdit->GetIsEWS();
        $Clean['HasDisability'] = $StudentToEdit->GetHasDisability();
        $Clean['IsSingleGirl'] = $StudentToEdit->GetIsSingleGirl();

        $Clean['Address1'] = $StudentToEdit->GetAddress1();
        $Clean['Address2'] = $StudentToEdit->GetAddress2();
        
        $Clean['CountryID'] = $StudentToEdit->GetCountryID();
        
        $StateList = State::GetAllStates($Clean['CountryID']);
        $Clean['StateID'] = $StudentToEdit->GetStateID();
        
        $DistrictList = City::GetAllDistricts($Clean['StateID']);
        $Clean['DistrictID'] = $StudentToEdit->GetDistrictID();
        
        $CityList = City::GetAllCities($Clean['StateID'], $Clean['DistrictID']);
        $Clean['CityID'] = $StudentToEdit->GetCityID();
        
        $Clean['Pincode'] = $StudentToEdit->GetPincode();

        $Clean['AdmissionDate'] = date('d/m/Y', strtotime($StudentToEdit->GetAdmissionDate()));
        $Clean['MobileNumber'] = $StudentToEdit->GetMobileNumber();
        $Clean['Email'] = $StudentToEdit->GetEmail();
        $Clean['AadharNumber'] = $StudentToEdit->GetAadharNumber();

        $Clean['LastClass'] = $StudentToEdit->GetLastClass();
        $Clean['LastSchool'] = $StudentToEdit->GetLastSchool();
        $Clean['LastSchoolBoard'] = $StudentToEdit->GetLastSchoolBoard();
        $Clean['TCReceived'] = $StudentToEdit->GetTCReceived();
        $Clean['TCDate'] = date('d/m/Y', strtotime($StudentToEdit->GetTCDate()));

        $Clean['SubjectsProposed'] = $StudentToEdit->GetSubjectsProposed();
        $Clean['MotherTongue'] = $StudentToEdit->GetMotherTongue();
        $Clean['HomeTown'] = $StudentToEdit->GetHomeTown();

        $Clean['LastExamStatus'] = $StudentToEdit->GetLastExamStatus();
        $Clean['LastExamPercentage'] = $StudentToEdit->GetLastExamPercentage();
       
        $NewParentDetail = new ParentDetail($StudentToEdit->GetParentID());

        $Clean['FatherFirstName'] = $NewParentDetail->GetFatherFirstName();
        $Clean['FeeCode'] = $NewParentDetail->GetFeeCode();
        $Clean['FatherLastName'] = $NewParentDetail->GetFatherLastName();
        $Clean['MotherFirstName'] = $NewParentDetail->GetMotherFirstName();
        $Clean['MotherLastName'] = $NewParentDetail->GetMotherLastName();

        $Clean['FatherOccupation'] = $NewParentDetail->GetFatherOccupation();
        $Clean['MotherOccupation'] = $NewParentDetail->GetMotherOccupation();
        $Clean['FatherOfficeName'] = $NewParentDetail->GetFatherOfficeName();
        $Clean['MotherOfficeName'] = $NewParentDetail->GetMotherOfficeName();
        $Clean['FatherOfficeAddress'] = $NewParentDetail->GetFatherOfficeAddress();
        $Clean['MotherOfficeAddress'] = $NewParentDetail->GetMotherOfficeAddress();

        $Clean['ResidentailAddress'] = $NewParentDetail->GetResidentailAddress();
        $Clean['ResidentailCityID'] = $NewParentDetail->GetResidentailCityID();
        $Clean['ResidentailDistrictID'] = $NewParentDetail->GetResidentailDistrictID();
        $Clean['ResidentailStateID'] = $NewParentDetail->GetResidentailStateID();
        $Clean['ResidentailCountryID'] = $NewParentDetail->GetResidentailCountryID();
        $Clean['ResidentailPinCode'] = $NewParentDetail->GetResidentailPinCode();
        
        $ResidentailStateList = State::GetAllStates($Clean['ResidentailCountryID']);
        $ResidentailDistrictList = City::GetAllDistricts($Clean['ResidentailStateID']);
        $ResidentailCityList = City::GetAllCities($Clean['ResidentailStateID'], $Clean['ResidentailDistrictID']);

        $Clean['PermanentAddress'] = $NewParentDetail->GetPermanentAddress();
        $Clean['PermanentCityID'] = $NewParentDetail->GetPermanentCityID();
        $Clean['PermanentDistrictID'] = $NewParentDetail->GetPermanentDistrictID();
        $Clean['PermanentStateID'] = $NewParentDetail->GetPermanentStateID();
        $Clean['PermanentCountryID'] = $NewParentDetail->GetPermanentCountryID();
        $Clean['PermanentPinCode'] = $NewParentDetail->GetPermanentPinCode();
        
        $PermanentStateList = State::GetAllStates($Clean['PermanentCountryID']);
        $PermanentDistrictList = City::GetAllDistricts($Clean['PermanentStateID']);
        $PermanentCityList = City::GetAllCities($Clean['PermanentStateID'], $Clean['PermanentDistrictID']);

        $Clean['PhoneNumber'] = $NewParentDetail->GetPhoneNumber();
        $Clean['FatherMobileNumber'] = $NewParentDetail->GetFatherMobileNumber();
        $Clean['MotherMobileNumber'] = $NewParentDetail->GetMotherMobileNumber();

        $Clean['FatherEmail'] = $NewParentDetail->GetFatherEmail();
        $Clean['MotherEmail'] = $NewParentDetail->GetMotherEmail();

        $Clean['ParentAadharNumber'] = $NewParentDetail->GetAadharNumber();
        $Clean['IsActive'] = $NewParentDetail->GetIsActive();

    break;
}

require_once('../html_header.php');
?>
<title>Edit Student Details </title>
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
                    <h1 class="page-header">Edit Student Details</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="EditStudent" action="edit_student.php" method="post" enctype="multipart/form-data">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <strong>Student Details</strong>
                    </div>
                    <div class="panel-body">
<?php
                        if ($HasErrors == true)
                        {
                            echo $NewRecordValidator->DisplayErrors();
                        }
?>                    
                        <div class="form-group">
                            <label for="RollNumber" class="col-lg-2 control-label">Roll Number</label>
                            <div class="col-lg-4">
                                <input class="form-control" type="text" maxlength="5" id="RollNumber" name="txtRollNumber" value="<?php echo ($Clean['RollNumber'] ? $Clean['RollNumber'] : ''); ?>" disabled = 'disabled' />
                            </div>
                            <label for="EnrollmentID" class="col-lg-2 control-label">Enrollment ID</label>
                            <div class="col-lg-4">
                                <input class="form-control" type="text" maxlength="50" id="EnrollmentID" name="txtEnrollmentID" value="<?php echo $Clean['EnrollmentID']; ?>">
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
                                    echo '<option value="'.$StudentTypeID.'">'.$StudentType.'</option>';
                                }
?>
                                </select>
                            </div>
                            <label for="ColourHouseID" class="col-lg-2 control-label">House</label>
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
                            <label for="ClassID" class="col-lg-2 control-label">Class</label>
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
                            <label for="SectionID" class="col-lg-2 control-label">Section</label>
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
                            
                            <label for="Year" class="col-lg-1 control-label"><input class="form-control" type="text" maxlength="25" id="Year" name="txtYear" value="" readonly />&nbsp;Year</label>

                            <label for="Month" class="col-lg-1 control-label"><input class="form-control" type="text" maxlength="25" id="Month" name="txtMonth" value="" readonly />&nbsp;Month</label>
                            
                            <label for="Day" class="col-sm-1 control-label"><input class="form-control" type="text" maxlength="25" id="Day" name="txtDay" value="" readonly/>&nbsp;Day</label>
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
                                <label class="col-sm-6"><input class="custom-radio" type="radio" id="Male" name="txtGender" value="Male" <?php echo ($Clean['Gender'] == 'Male' ? 'checked="checked"' : ''); ?> >&nbsp;&nbsp;Male</label>
                                <label class="col-sm-6"><input class="custom-radio" type="radio" id="Female" name="txtGender" value="Female" <?php echo ($Clean['Gender'] == 'Female' ? 'checked="checked"' : ''); ?> >&nbsp;&nbsp;Female</label>
                            </div>
                            <label for="BloodGroup" class="col-lg-2 control-label">Blood Group</label>
                            <div class="col-lg-4">
                                <select class="form-control" name="txtBloodGroup" id="BloodGroup">
                                        <option value="0">Select Blood Group</option>
<?php
                                if (is_array($BloodGroupList) && count($BloodGroupList) > 0)
                                {
                                    foreach ($BloodGroupList as $BloodGroupID => $BloodGroupName) {
                                        echo '<option '.($Clean['BloodGroup'] == $BloodGroupID ? 'selected="selected"' : '').' value="'.$BloodGroupID.'">'.$BloodGroupName.'</option>';
                                    }
                                }
?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="Category" class="col-lg-2 control-label">Category</label>
                            <div class="col-lg-4">
                                <label class="col-sm-6"><input class="custom-radio" type="radio" id="General" name="txtCategory" value="General" <?php echo ($Clean['Category'] == 'General' ? 'checked="checked"' : ''); ?> >&nbsp;&nbsp;General</label>
                                <label class="col-sm-6"><input class="custom-radio" type="radio" id="OBC" name="txtCategory" value="OBC" <?php echo ($Clean['Category'] == 'OBC' ? 'checked="checked"' : ''); ?> >&nbsp;&nbsp;OBC</label>
                                <label class="col-sm-6"><input class="custom-radio" type="radio" id="SC" name="txtCategory" value="SC" <?php echo ($Clean['Category'] == 'SC' ? 'checked="checked"' : ''); ?> >&nbsp;&nbsp;SC</label>
                                <label class="col-sm-6"><input class="custom-radio" type="radio" id="ST" name="txtCategory" value="ST" <?php echo ($Clean['Category'] == 'ST' ? 'checked="checked"' : ''); ?> >&nbsp;&nbsp;ST</label>
                            </div>
                             <label for="Others" class="col-lg-2 control-label">Others</label>
                            <div class="col-lg-4">
                                <label class="checkbox-inline"><input class="custom-radio" type="checkbox" id="IsEWS" name="txtIsEWS" value="<?php echo $Clean['IsEWS']; ?>" <?php echo ($Clean['IsEWS'] == 1 ? 'checked="checked"' : ''); ?> >&nbsp;&nbsp;EWS</label>
                                <label class="checkbox-inline"><input class="custom-radio" type="checkbox" id="HasDisability" name="txtHasDisability" value="<?php echo $Clean['HasDisability']; ?>" <?php echo ($Clean['HasDisability'] == 1 ? 'checked="checked"' : ''); ?> >Disabled</label>
                                <label class="checkbox-inline"><input class="custom-radio" type="checkbox" id="IsSingleGirl" name="txtIsSingleGirl" value="<?php echo $Clean['IsSingleGirl']; ?>" <?php echo ($Clean['IsSingleGirl'] == 1 ? 'checked="checked"' : ''); ?> >SingleGirl</label>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="CountryID" class="col-lg-2 control-label">Country</label>
                            <div class="col-lg-4">
                                <select class="form-control" name="drdCountry" id="Country">
<?php
                                if (is_array($CountryList) && count($CountryList) > 0)
                                {
                                    foreach ($CountryList as $CountryID => $CountryName) {
                                        echo '<option '.($Clean['CountryID'] == $CountryID ? 'selected="selected"' : '').' value="'.$CountryID.'">'.$CountryName.'</option>';
                                    }
                                }
?>
                                </select>
                            </div>
                            <label for="StateID" class="col-lg-2 control-label">State</label>
                            <div class="col-lg-4">
                                <select class="form-control" name="drdState" id="State">
<?php
                                if (is_array($StateList) && count($StateList) > 0)
                                {
                                    foreach ($StateList as $StateID => $StateName) {
                                        echo '<option '.($Clean['StateID'] == $StateID ? 'selected="selected"' : '').' value="'.$StateID.'">'.$StateName.'</option>';
                                    }
                                }
?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="DistrictID" class="col-lg-2 control-label">District</label>
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
                            <label for="CityID" class="col-lg-2 control-label">City</label>
                            <div class="col-lg-4">
                                <select class="form-control" name="drdCity" id="City">
<?php
                                if (is_array($CityList) && count($CityList) > 0)
                                {
                                    foreach ($CityList as $CityID => $CityName) {
                                        echo '<option '.($Clean['CityID'] == $CityID ? 'selected="selected"' : '').' value="'.$CityID.'">'.$CityName.'</option>';
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
                            <label for="Status" class="col-lg-2 control-label">Student Status</label>
                            <div class="col-lg-4">
                                <label class="col-sm-6"><input class="custom-radio status" type="radio" id="Active" name="txtStatus" value="Active" <?php echo ($Clean['Status'] == 'Active' ? 'checked="checked"' : ''); ?> >&nbsp;&nbsp;Active</label>
                                <label class="col-sm-6"><input class="custom-radio status" type="radio" id="Suspended" name="txtStatus" value="Suspended" <?php echo ($Clean['Status'] == 'Suspended' ? 'checked="checked"' : ''); ?> >&nbsp;&nbsp;Suspended</label>
                                <label class="col-sm-6"><input class="custom-radio status" type="radio" id="Terminated" name="txtStatus" value="Terminated" <?php echo ($Clean['Status'] == 'Terminated' ? 'checked="checked"' : ''); ?> >&nbsp;&nbsp;Terminated</label>
                                <label class="col-sm-6"><input class="custom-radio status" type="radio" id="InActive" name="txtStatus" value="InActive" <?php echo ($Clean['Status'] == 'InActive' ? 'checked="checked"' : ''); ?> >&nbsp;&nbsp;InActive</label>
                                <label class="col-sm-6"><input class="custom-radio status" type="radio" id="Passout" name="txtStatus" value="Passout" <?php echo ($Clean['Status'] == 'Passout' ? 'checked="checked"' : ''); ?> >&nbsp;&nbsp;Passout</label>
                            </div>
                        </div>
                        <div class="form-group" id="DateFromDiv" style="display: none;">
                            <label for="Date" class="col-lg-2 control-label">Date From</label>
                            <div class="col-lg-4">
                                <input class="form-control select-date" type="text" maxlength="10" id="DateFrom" name="txtDateFrom" value="<?php echo $Clean['DateFrom']; ?>" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="Upload" class="col-lg-2 control-label">Upload Image</label>
                            <div class="col-lg-4">
                                <input class="form-control" type="file" name="fleEventImage" onchange="readURL(this);"/>
                          </div>
                           <label for="Photo" class="col-lg-2 control-label">Image</label>
                            <div class="col-lg-4">
                                <img class="img-responsive" src="<?php echo '../../site_images/student_images/' .$StudentToEdit->GetStudentID() .'/' . $StudentToEdit->GetStudentPhoto();?>" width="115px" height="50px"  style ="border:1px solid black; border-radius:2px; margin-left: 5px;">
                            </div>
                            <div class="col-lg-6 EventImage" style="display: none;">
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
                                        <label class="col-sm-3"><input class="custom-radio" type="radio" id="CBSE" name="txtLastSchoolBoard" value="CBSE" <?php echo ($Clean['LastSchoolBoard'] == 'CBSE' ? 'checked="checked"' : ''); ?> >&nbsp;&nbsp;CBSE</label>
                                        <label class="col-sm-3"><input class="custom-radio" type="radio" id="UP" name="txtLastSchoolBoard" value="UP" <?php echo ($Clean['LastSchoolBoard'] == 'UP' ? 'checked="checked"' : ''); ?> >&nbsp;&nbsp;Uttar Pradesh</label>
                                        <label class="col-sm-3"><input class="custom-radio" type="radio" id="ICSE" name="txtLastSchoolBoard" value="ICSE" <?php echo ($Clean['LastSchoolBoard'] == 'ICSE' ? 'checked="checked"' : ''); ?> >&nbsp;&nbsp;ICSE</label>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="TCReceived" class="col-lg-2 control-label">TC Received</label>
                                    <div class="col-lg-4">
                                        <label class="col-sm-3"><input class="custom-radio" type="radio" id="Yes" name="txtTCReceived" value="1" <?php echo ($Clean['TCReceived'] == 1 ? 'checked="checked"' : ''); ?> >&nbsp;&nbsp;Yes</label>
                                        <label class="col-sm-3"><input class="custom-radio" type="radio" id="No" name="txtTCReceived" value="0" <?php echo ($Clean['TCReceived'] == 0 ? 'checked="checked"' : ''); ?> >&nbsp;&nbsp;No</label>
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
                                        <select class="form-control" name="txtMotherTongue" id="MotherTongue">
<?php
                                        foreach ($MotherTongueList as $MotherTongueID => $MotherTongue) 
                                        {
                                            echo '<option value="'.$MotherTongueID.'">'.$MotherTongue.'</option>';
                                        }
?>                                                
                                        </select>
                                    </div>
                                    <label for="HomeTown" class="col-lg-2 control-label">Home Town</label>
                                    <div class="col-lg-4">
                                        <select class="form-control" name="txtHomeTown" id="HomeTown">
                                                <option value="0">Select Home Town</option>
<?php
                                        if (is_array($CityList) && count($CityList) > 0)
                                        {
                                            foreach ($CityList as $CityID => $CityName) {
                                                echo '<option '.($Clean['HomeTown'] == $CityID ? 'selected="selected"' : '').' value="'.$CityID.'">'.$CityName.'</option>';
                                            }
                                        }
?>                                                
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="LastExamStatus" class="col-lg-2 control-label">Last Exam Result</label>
                                    <div class="col-lg-4">
                                        <label class="radio-inline"><input class="custom-radio" type="radio" id="Passed" name="txtLastExamStatus" value="Passed" <?php echo ($Clean['LastExamStatus'] == 'Passed' ? 'checked="checked"' : ''); ?> >&nbsp;&nbsp;Passed</label>
                                        <label class="radio-inline"><input class="custom-radio" type="radio" id="Failed" name="txtLastExamStatus" value="Failed" <?php echo ($Clean['LastExamStatus'] == 'Failed' ? 'checked="checked"' : ''); ?> >&nbsp;&nbsp;Failed</label>
                                        <label class="radio-inline"><input class="custom-radio" type="radio" id="Awaited" name="txtLastExamStatus" value="Awaited" <?php echo ($Clean['LastExamStatus'] == 'Awaited' ? 'checked="checked"' : ''); ?> >&nbsp;&nbsp;Awaited</label>
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
                                <div id="Existing" class="ParentType" style="display: none;">
                                    <div class="form-group">
                                        <label for="SiblingName" class="col-lg-2 control-label">Sibling Name</label>
                                        <div class="col-lg-4">
                                            <input class="form-control" type="text" maxlength="50" id="SiblingName" name="txtSiblingName" value="<?php echo $Clean['SiblingName']; ?>" />
                                        </div>
                                        <label for="ParentUserId" class="col-lg-2 control-label">Parent User Id</label>
                                        <div class="col-lg-4">
                                            <input class="form-control" type="text" maxlength="150" id="ParentUserId" name="txtParentUserId" value="<?php echo $Clean['ParentUserId']; ?>" />
                                        </div>
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
                                        <label for="ResidentailCountryID" class="col-lg-2 control-label">Residentail Country</label>
                                        <div class="col-lg-4">
                                            <select class="form-control" name="drdResidentailCountry" id="ResidentailCountry">
<?php
                                            if (is_array($CountryList) && count($CountryList) > 0)
                                            {
                                                foreach ($CountryList as $CountryID => $CountryName) {
                                                    echo '<option '.($Clean['CountryID'] == $CountryID ? 'selected="selected"' : '').' value="'.$CountryID.'">'.$CountryName.'</option>';
                                                }
                                            }
?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="ResidentailStateID" class="col-lg-2 control-label">Residentail State</label>
                                        <div class="col-lg-4">
                                            <select class="form-control" name="drdResidentailState" id="ResidentailState">
<?php
                                            if (is_array($ResidentailStateList) && count($ResidentailStateList) > 0)
                                            {
                                                foreach ($ResidentailStateList as $StateID => $StateName) {
                                                    echo '<option '.($Clean['StateID'] == $StateID ? 'selected="selected"' : '').' value="'.$StateID.'">'.$StateName.'</option>';
                                                }
                                            }
?>
                                            </select>
                                        </div>
                                        <label for="ResidentailDistrictID" class="col-lg-2 control-label">Residentail District</label>
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
                                        <label for="ResidentailCityID" class="col-lg-2 control-label">Residentail City</label>
                                        <div class="col-lg-4">
                                            <select class="form-control" name="drdResidentailCity" id="ResidentailCity">
<?php
                                            if (is_array($ResidentailCityList) && count($ResidentailCityList) > 0)
                                            {
                                                foreach ($ResidentailCityList as $CityID => $CityName) {
                                                    echo '<option '.($Clean['CityID'] == $CityID ? 'selected="selected"' : '').' value="'.$CityID.'">'.$CityName.'</option>';
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
                                        <label for="PermanentCountryID" class="col-lg-2 control-label">Permanent Country</label>
                                        <div class="col-lg-4">
                                            <select class="form-control" name="drdPermanentCountry" id="PermanentCountry">
<?php
                                            if (is_array($CountryList) && count($CountryList) > 0)
                                            {
                                                foreach ($CountryList as $CountryID => $CountryName) {
                                                    echo '<option '.($Clean['CountryID'] == $CountryID ? 'selected="selected"' : '').' value="'.$CountryID.'">'.$CountryName.'</option>';
                                                }
                                            }
?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="PermanentStateID" class="col-lg-2 control-label">Permanent State</label>
                                        <div class="col-lg-4">
                                            <select class="form-control" name="drdPermanentState" id="PermanentState">
<?php
                                            if (is_array($PermanentStateList) && count($PermanentStateList) > 0)
                                            {
                                                foreach ($PermanentStateList as $StateID => $StateName) {
                                                    echo '<option '.($Clean['StateID'] == $StateID ? 'selected="selected"' : '').' value="'.$StateID.'">'.$StateName.'</option>';
                                                }
                                            }
?>
                                            </select>
                                        </div>
                                        <label for="PermanentDistrictID" class="col-lg-2 control-label">Permanent District</label>
                                        <div class="col-lg-4">
                                            <select class="form-control" name="drdPermanentDistrict" id="PermanentDistrict">
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
                                        <label for="PermanentCityID" class="col-lg-2 control-label">Permanent City</label>
                                        <div class="col-lg-4">
                                            <select class="form-control" name="drdPermanentCity" id="PermanentCity">
<?php
                                            if (is_array($PermanentCityList) && count($PermanentCityList) > 0)
                                            {
                                                foreach ($PermanentCityList as $CityID => $CityName) {
                                                    echo '<option '.($Clean['CityID'] == $CityID ? 'selected="selected"' : '').' value="'.$CityID.'">'.$CityName.'</option>';
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
                                    <input type="hidden" name="hdnProcess" value="3"/>
                                    <input type="hidden" name="hdnStudentID" value="<?php echo $Clean['StudentID']; ?>" />
                                    <button type="submit" class="btn btn-primary">Update</button>
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

var previousStatus = '<?php echo $Clean['Status']; ?>';

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
			$('#City').html('<option value="0">Select City</option>');
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

    $("body").on('change', '.status', function()
	{
        var Status = $(this).val();
        console.log(Status);
        console.log(previousStatus);
        if (Status == previousStatus)
        {
            $('#DateFromDiv').slideUp();
        }
        else
        {
            $('#DateFromDiv').slideDown();
        }        
	});
});

window.CallParent = function(ParentID) {
    $('#AlreadyExistingParent').prop('checked', true);
    $('#ParentDetailsContainer').html('');
    $('#ParentDetailsContainer').load('/xhttp_calls/get_parent_details.php', { SelectedParentID: ParentID });
    $('#ParentDetailsContainer').find('txtParentID').val(ParentID);
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
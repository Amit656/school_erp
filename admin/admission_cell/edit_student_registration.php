<?php
// error_reporting(E_ALL);
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once("../../classes/admission_cell/class.student_registrations.php");
require_once("../../classes/admission_cell/class.exams.php");
require_once("../../classes/admission_cell/class.exam_registrations.php");

require_once("../../classes/school_administration/class.academic_years.php");
require_once("../../classes/school_administration/class.classes.php");
require_once("../../classes/school_administration/class.countries.php");
require_once("../../classes/school_administration/class.states.php");
require_once("../../classes/school_administration/class.cities.php");

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
	header('location:../unauthorized_login_admin.php');
	exit;
}
catch (Exception $e)
{
	header('location:../unauthorized_login_admin.php');
	exit;
}
// END OF 1. //

if ($LoggedUser->HasPermissionForTask(TASK_EDIT_STUDENT_REGISTRATION) !== true)
{
    header('location:/admin/unauthorized_login_admin.php');
    exit;
}

$Clean = array();

$Clean['StudentRegistrationID'] = 0;

if (isset($_GET['StudentRegistrationID']))
{
    $Clean['StudentRegistrationID'] = (int) $_GET['StudentRegistrationID'];
}
elseif (isset($_POST['hdnStudentRegistrationID']))
{
    $Clean['StudentRegistrationID'] = (int) $_POST['hdnStudentRegistrationID'];
}

if ($Clean['StudentRegistrationID'] <= 0)
{
    header('location:../error.php');
    exit;
}   

try
{
    $StudentRegistrationToEdit = new StudentRegistration($Clean['StudentRegistrationID']);
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

$HasErrors = false;

$StudentTypeList = array('New' => 'New', 'Old' => 'Old');
$GenderList = array('Male' => 'Male', 'Female' => 'Female', 'Others' => 'Others');
$BloodGroupList = array('A+' => 'A+', 'A-' => 'A-', 'B+' => 'B+', 'AB+' => 'AB+', 'AB-' => 'AB-', 'O+' => 'O+', 'O-' => 'O-');
$CategoryList = array('General' => 'General', 'OBC' => 'OBC', 'SC' => 'SC', 'ST' => 'ST');
$OtherCategoryList = array('IsEWS' => 'EWS', 'HasDisability' => 'Disabled', 'IsSingleGirl' => 'Single Girl');
$LastSchoolBoardList = array('CBSE' => 'CBSE', 'ICSE' => 'ICSE', 'UP' => 'UP');
$TCReceivedList = array(1 => 'Yes', 0 => 'No');
$LastExamStatusList = array('Passed' => 'Passed', 'Failed' => 'Failed', 'Awaited' => 'Awaited');
$MotherTongueList = array('Hindi' => 'Hindi', 'English' => 'English');

$AcademicYears =  array();
$AcademicYears = AcademicYear::GetAllAcademicYears();

$ClassList =  array();
$ClassList = AddedClass::GetActiveClasses();

$CountryList =  array();
$CountryList = Country::GetAllCountries();

$StateList =  array();
$CityList =  array();

$AllExamsList = array();
$AllExamsList = Exam::GetActiveExams();

$AllExams = array();
$AllExams = Exam::GetAllExams();

$Clean['Process'] = 0;

$Clean['AcademicYearID'] = 0;

$Clean['ClassID'] = 0;
$Clean['ClassID'] = key($ClassList);

$Clean['FirstName'] = '';
$Clean['LastName'] = '';
$Clean['DOB'] = '';
$Clean['Gender'] = 'Male';

$Clean['BloodGroup'] = key($BloodGroupList);
$Clean['Category'] = 'General';

$Clean['IsEWS'] = 0;
$Clean['HasDisability'] = 0;
$Clean['IsSingleGirl'] = 0;

$Clean['FatherName'] = '';
$Clean['MotherName'] = '';

$Clean['ResidentAddress'] = '';
$Clean['PermanentAddress'] = '';

$Clean['CountryID'] = key($CountryList);
$StateList = State::GetAllStates($Clean['CountryID']);

$Clean['StateID'] = key($StateList);

$DistrictList = City::GetAllDistricts($Clean['StateID']);
$Clean['DistrictID'] = key($DistrictList);

$CityList = City::GetAllCities($Clean['StateID'], $Clean['DistrictID']);
$Clean['CityID'] = key($CityList);

$Clean['PinCode'] = '';
$Clean['AadharNumber'] = 0;
    
$Clean['MobileNumber'] = '';
$Clean['Email'] = '';

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

$Clean['RegistrationFee'] = 0.00;

$Clean['ExamID'] = 0;
$Clean['ExamDate'] = '';
$Clean['ExamTime'] = '';

$Clean['IsActive'] = 0;

$EntranceExamDetails = array();

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

        if (isset($_POST['drdBloodGroup']))
        {
            $Clean['BloodGroup'] = strip_tags(trim($_POST['drdBloodGroup']));
        }

        if (isset($_POST['rdbCategory']))
        {
            $Clean['Category'] = strip_tags(trim($_POST['rdbCategory']));
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

        if (isset($_POST['txtFatherName']))
        {
            $Clean['FatherName'] = strip_tags(trim($_POST['txtFatherName']));
        }

        if (isset($_POST['txtMotherName']))
        {
            $Clean['MotherName'] = strip_tags(trim($_POST['txtMotherName']));
        }

        if (isset($_POST['txtResidentAddress']))
        {
            $Clean['ResidentAddress'] = strip_tags(trim($_POST['txtResidentAddress']));
        }

        if (isset($_POST['txtPermanentAddress']))
        {
            $Clean['PermanentAddress'] = strip_tags(trim($_POST['txtPermanentAddress']));
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

        if (isset($_POST['txtAadharNumber']))
        {
            $Clean['AadharNumber'] = $_POST['txtAadharNumber'];
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

        if (isset($_POST['rdbLastSchoolBoard']))
        {
            $Clean['LastSchoolBoard'] = strip_tags(trim($_POST['rdbLastSchoolBoard']));
        }

        if (isset($_POST['rdbTCReceived']))
        {
            $Clean['TCReceived'] = (int) $_POST['rdbTCReceived'];
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

        if (isset($_POST['txtHomeTown']))
        {
            $Clean['HomeTown'] = strip_tags(trim($_POST['txtHomeTown']));
        }

        if (isset($_POST['rdbLastExamStatus']))
        {
            $Clean['LastExamStatus'] = strip_tags(trim($_POST['rdbLastExamStatus']));
        }

        if (isset($_POST['txtLastExamPercentage']))
        {
            $Clean['LastExamPercentage'] = strip_tags(trim($_POST['txtLastExamPercentage']));
        }		

        if (isset($_POST['drdExam']))
        {
            $Clean['ExamID'] = (int) $_POST['drdExam'];
        }

        if (isset($_POST['txtExamDate']))
        {
            $Clean['ExamDate'] = strip_tags(trim($_POST['txtExamDate']));
        }

        if (isset($_POST['txtExamTime']))
        {
            $Clean['ExamTime'] = strip_tags(trim($_POST['txtExamTime']));
        }

        if (isset($_POST['txtRegistrationFee']))
        {
            $Clean['RegistrationFee'] = strip_tags(trim($_POST['txtRegistrationFee']));
        }

        if (isset($_POST['chkIsActive']))
        {
            $Clean['IsActive'] = 1;
        }

		$NewRecordValidator = new Validator(); 
        
        // $NewRecordValidator->ValidateInSelect($Clean['AcademicYearID'], $AcademicYears, 'Unknown error 101, please try again.');
        $NewRecordValidator->ValidateInSelect($Clean['ClassID'], $ClassList, 'Unknown error, please try again.');

        $NewRecordValidator->ValidateStrings($Clean['FirstName'], 'First Name is required and should be between 3 and 20 characters.', 3, 20);
        $NewRecordValidator->ValidateStrings($Clean['LastName'], 'Last Name is required and should be between 3 and 30 characters.', 3, 30);
        $NewRecordValidator->ValidateDate($Clean['DOB'], 'Please enter a valid date of birth.');

        $NewRecordValidator->ValidateInSelect($Clean['Gender'], $GenderList, 'Unknown error, please try again.');

        if ($Clean['BloodGroup'] != '') 
        {
            // $NewRecordValidator->ValidateInSelect($Clean['BloodGroup'], $BloodGroupList, 'Unknown error, please try again.');
        }

        $NewRecordValidator->ValidateInSelect($Clean['Category'], $CategoryList, 'Unknown error, please try again.');

        $NewRecordValidator->ValidateStrings($Clean['FatherName'], 'Father First Name is required and should be between 3 and 50 characters.', 3, 50);
        $NewRecordValidator->ValidateStrings($Clean['MotherName'], 'Mother First Name is required and should be between 3 and 50 characters.', 3, 50);

       // $NewRecordValidator->ValidateStrings($Clean['ResidentAddress'], 'ResidentAddress is required and should be between 5 and 150 characters.', 5, 150);

        if ($Clean['PermanentAddress'] != '') 
        {
            $NewRecordValidator->ValidateStrings($Clean['PermanentAddress'], 'Permanent Address is required and should be between 5 and 150 characters.', 5, 150);
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

        if ($Clean['AadharNumber'] != '') 
        {
            $NewRecordValidator->ValidateStrings($Clean['AadharNumber'], 'Aadhar number is required and should be between 9 and 12 characters.', 9, 12);
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

        if ($Clean['SubjectsProposed'] != '') 
        {
            $NewRecordValidator->ValidateStrings($Clean['SubjectsProposed'], 'Subjects proposed is required and should be between 5 and 500 characters.', 5, 500);
        }

        $NewRecordValidator->ValidateInSelect($Clean['MotherTongue'], $MotherTongueList, 'Unknown error, please try again.');

        if ($Clean['HomeTown'] != '') 
        {
            $NewRecordValidator->ValidateStrings($Clean['HomeTown'], 'Home Town is required and should be between 3 and 50 characters.', 3, 50);
        }

        $NewRecordValidator->ValidateInSelect($Clean['LastExamStatus'], $LastExamStatusList, 'Unknown error, please try again.');

        if ($Clean['LastExamPercentage'] != '') 
        {
            $NewRecordValidator->ValidateNumeric($Clean['LastExamPercentage'], 'Last exam percentage is required and should be numeric.');
        }

        if ($Clean['RegistrationFee'] != '') 
        {
            $NewRecordValidator->ValidateNumeric($Clean['RegistrationFee'], 'Registration fee should be numeric.');
        }

        if ($Clean['ExamID'] > 0) 
        {
            $NewRecordValidator->ValidateInSelect($Clean['ExamID'], $AllExamsList, 'Unknown error, please try again.');
            $NewRecordValidator->ValidateDate($Clean['ExamDate'], 'Please enter a valid exam date.');
            $NewRecordValidator->ValidateStrings($Clean['ExamTime'], 'Please enter valid time.', 1, 8);
        }
        
		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}

        $StudentRegistrationToEdit->SetClassID($Clean['ClassID']);
        $StudentRegistrationToEdit->SetFirstName($Clean['FirstName']);
        $StudentRegistrationToEdit->SetLastName($Clean['LastName']);

        if ($Clean['DOB'] != '')
        {
            $StudentRegistrationToEdit->SetDOB(date('Y-m-d', strtotime(DateProcessing::ToggleDateDayAndMonth(($Clean['DOB'])))));
        }

        $StudentRegistrationToEdit->SetGender($Clean['Gender']);        
        $StudentRegistrationToEdit->SetBloodGroup($Clean['BloodGroup']);
        $StudentRegistrationToEdit->SetCategory($Clean['Category']);

        $StudentRegistrationToEdit->SetIsEWS($Clean['IsEWS']);
        $StudentRegistrationToEdit->SetHasDisability($Clean['HasDisability']);
        $StudentRegistrationToEdit->SetIsSingleGirl($Clean['IsSingleGirl']);

        $StudentRegistrationToEdit->SetFatherName($Clean['FatherName']);
        $StudentRegistrationToEdit->SetMotherName($Clean['MotherName']);

        $StudentRegistrationToEdit->SetResidentAddress($Clean['ResidentAddress']);
        $StudentRegistrationToEdit->SetPermanentAddress($Clean['PermanentAddress']);

        $StudentRegistrationToEdit->SetCountryID($Clean['CountryID']);
        $StudentRegistrationToEdit->SetStateID($Clean['StateID']);
        $StudentRegistrationToEdit->SetDistrictID($Clean['DistrictID']);
        $StudentRegistrationToEdit->SetCityID($Clean['CityID']);
        $StudentRegistrationToEdit->SetPinCode($Clean['PinCode']);

        $StudentRegistrationToEdit->SetAadharNumber($Clean['AadharNumber']);        
        $StudentRegistrationToEdit->SetMobileNumber($Clean['MobileNumber']);
        $StudentRegistrationToEdit->SetEmail($Clean['Email']);
        
        // Last Academic Details

        if($Clean['LastClass'] != '')
        {
            $StudentRegistrationToEdit->SetLastClass($Clean['LastClass']);       
        }

        $StudentRegistrationToEdit->SetLastSchool($Clean['LastSchool']);
        $StudentRegistrationToEdit->SetLastSchoolBoard($Clean['LastSchoolBoard']);
        $StudentRegistrationToEdit->SetTCReceived($Clean['TCReceived']);

        if ($Clean['TCDate'] != '')
        {
            $StudentRegistrationToEdit->SetTCDate(date('Y-m-d', strtotime(DateProcessing::ToggleDateDayAndMonth(($Clean['TCDate'])))));
        }
    
        $StudentRegistrationToEdit->SetSubjectsProposed($Clean['SubjectsProposed']);
        $StudentRegistrationToEdit->SetMotherTongue($Clean['MotherTongue']);
        $StudentRegistrationToEdit->SetHomeTown($Clean['HomeTown']);

        $StudentRegistrationToEdit->SetLastExamStatus($Clean['LastExamStatus']);
        $StudentRegistrationToEdit->SetLastExamPercentage($Clean['LastExamPercentage']);

        $StudentRegistrationToEdit->SetRegistrationFee($Clean['RegistrationFee']);
        $StudentRegistrationToEdit->SetAcademicYearID($Clean['AcademicYearID']);
		
		$StudentRegistrationToEdit->SetCreateUserID($LoggedUser->GetUserID());
        $StudentRegistrationToEdit->SetIsActive($Clean['IsActive']);

        if ($Clean['AadharNumber'] != '') 
        {
            if ($NewStudentRegistration->AadharExist())
            {
                $NewRecordValidator->AttachTextError('Aadhar you have added already exists.');
                $HasErrors = true;
                break;
            }
        }

		if (!$StudentRegistrationToEdit->Save())
		{
			$NewRecordValidator->AttachTextError(ProcessErrors($StudentRegistrationToEdit->GetLastErrorCode()));
			$HasErrors = true;
			break;
		}

        if ($Clean['ExamID'] > 0)
        {
            $NewExamRegistration = new ExamRegistration();
                
            $NewExamRegistration->SetStudentRegistrationID($NewStudentRegistration->GetStudentRegistrationID());
            $NewExamRegistration->SetExamID($Clean['ExamID']);
            
            if ($Clean['ExamDate'] != '') 
            {
                $NewExamRegistration->SetExamDate(date('Y-m-d', strtotime(DateProcessing::ToggleDateDayAndMonth(($Clean['ExamDate'])))));    
            }

            $NewExamRegistration->SetExamTime($Clean['ExamTime']);
            $NewExamRegistration->SetRegistrationAmount($Clean['RegistrationFee']);
            $NewExamRegistration->SetIsActive(1);
            
            $NewExamRegistration->SetCreateUserID($LoggedUser->GetUserID());

            if (!$NewExamRegistration->Save())
            {
                $NewRecordValidator->AttachTextError(ProcessErrors($NewExamRegistration->GetLastErrorCode()));
                $HasErrors = true;
                break;
            }
        }

		header('location:student_registrations_list.php?Process=7&IsActive=1&Mode=UD');
		exit;
	    break;

    case 2:
        $Clean['ClassID'] = $StudentRegistrationToEdit->GetClassID();
        $Clean['FirstName'] = $StudentRegistrationToEdit->GetFirstName();
        $Clean['LastName'] = $StudentRegistrationToEdit->GetLastName();
        $Clean['DOB'] = $StudentRegistrationToEdit->GetDOB();

        $Clean['Gender'] = $StudentRegistrationToEdit->GetGender();        
        $Clean['BloodGroup'] = $StudentRegistrationToEdit->GetBloodGroup();
        $Clean['Category'] = $StudentRegistrationToEdit->GetCategory();

        $Clean['IsEWS'] = $StudentRegistrationToEdit->GetIsEWS();
        $Clean['HasDisability'] = $StudentRegistrationToEdit->GetHasDisability();
        $Clean['IsSingleGirl'] = $StudentRegistrationToEdit->GetIsSingleGirl();

        $Clean['FatherName'] = $StudentRegistrationToEdit->GetFatherName();
        $Clean['MotherName'] = $StudentRegistrationToEdit->GetMotherName();

        $Clean['ResidentAddress'] = $StudentRegistrationToEdit->GetResidentAddress();
        $Clean['PermanentAddress'] = $StudentRegistrationToEdit->GetPermanentAddress();

        $Clean['CountryID'] = $StudentRegistrationToEdit->GetCountryID();
        $Clean['StateID'] = $StudentRegistrationToEdit->GetStateID();
        $Clean['DistrictID'] = $StudentRegistrationToEdit->GetDistrictID();
        $Clean['CityID'] = $StudentRegistrationToEdit->GetCityID();
        $Clean['PinCode'] = $StudentRegistrationToEdit->GetPinCode();
        
        $StateList = State::GetAllStates($Clean['CountryID']);
        $DistrictList = City::GetAllDistricts($Clean['StateID']);
        $CityList = City::GetAllCities($Clean['StateID'], $Clean['DistrictID']);

        $Clean['AadharNumber'] = $StudentRegistrationToEdit->GetAadharNumber();        
        $Clean['MobileNumber'] = $StudentRegistrationToEdit->GetMobileNumber();
        $Clean['Email'] = $StudentRegistrationToEdit->GetEmail();
        
        // Last Academic Details

        $Clean['LastClass'] = $StudentRegistrationToEdit->GetLastClass();

        $Clean['LastSchool'] = $StudentRegistrationToEdit->GetLastSchool();
        $Clean['LastSchoolBoard'] = $StudentRegistrationToEdit->GetLastSchoolBoard();
        $Clean['TCReceived'] = $StudentRegistrationToEdit->GetTCReceived();
        $Clean['TCDate'] = $StudentRegistrationToEdit->GetTCDate();
    
        $Clean['SubjectsProposed'] = $StudentRegistrationToEdit->GetSubjectsProposed();
        $Clean['MotherTongue'] = $StudentRegistrationToEdit->GetMotherTongue();
        $Clean['HomeTown'] = $StudentRegistrationToEdit->GetHomeTown();

        $Clean['LastExamStatus'] = $StudentRegistrationToEdit->GetLastExamStatus();
        $Clean['LastExamPercentage'] = $StudentRegistrationToEdit->GetLastExamPercentage();

        $Clean['RegistrationFee'] = $StudentRegistrationToEdit->GetRegistrationFee();
        $Clean['AcademicYearID'] = $StudentRegistrationToEdit->GetAcademicYearID();
        
        $Clean['IsActive'] = $StudentRegistrationToEdit->GetIsActive();

        $StudentRegistrationToEdit->FillEntranceExamDetails();
        $EntranceExamDetails = $StudentRegistrationToEdit->GetEntranceExamDetails();

        if (count($EntranceExamDetails) > 0)
        {    
            $Clean['ExamID'] = $EntranceExamDetails['ExamID'];

            if ($EntranceExamDetails['ExamDate'] != '0000-00-00') 
            {
                $Clean['ExamDate'] = date('d/m/y', strtotime($EntranceExamDetails['ExamDate']));    
            }
            
            $Clean['ExamTime'] = $EntranceExamDetails['ExamTime'];
        }

        break;
}

require_once('../html_header.php');
?>
<title>Edit Student Registration</title>
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
                    <h1 class="page-header">Edit Registration</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="EditRegistrationt" action="edit_student_registration.php" method="post">
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
?>                    
                        <div class="form-group">
                            <label for="AcademicYearID" class="col-lg-2 control-label">Academic Session</label>
                            <div class="col-lg-4">
                            	<select class="form-control" name="drdAcademicYearID" id="AcademicYearID">
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
                            	<input class="form-control select-date" type="text" maxlength="10" id="DOB" name="txtDOB" value="<?php echo ($Clean['DOB']) ? date('d/m/Y', strtotime($Clean['DOB'])) : ''; ?>" />
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
                            foreach($GenderList as $Gender => $GenderName)
                            {
?>                              
                                <label style="font-weight: normal;"><input class="custom-radio" type="radio" id="<?php echo $Gender; ?>" name="rdbGender" value="<?php echo $Gender; ?>" <?php echo ($Clean['Gender'] == $Gender ? 'checked="checked"' : ''); ?> >&nbsp;<?php echo $GenderName; ?>&nbsp;</label>            
<?php                                       
                            }
?>
                            </div>
                            <label for="BloodGroup" class="col-lg-2 control-label">Blood Group</label>
                            <div class="col-lg-4">
                                <select class="form-control" name="drdBloodGroup" id="BloodGroup">
                                    <option value="0">Select Blood Group</option>
<?php
                                foreach ($BloodGroupList as $BloodGroupID => $BloodGroupName) 
                                {
                                    echo '<option ' . ($Clean['BloodGroup'] == $BloodGroupID ? 'selected="selected"' : '') . ' value="' . $BloodGroupID . '">' . $BloodGroupName . '</option>';
                                }
?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="Category" class="col-lg-2 control-label">Category</label>
                            <div class="col-lg-4">
<?php
                            foreach($CategoryList as $Category => $CategoryName)
                            {
?>                              
                                <label style="font-weight: normal;"><input class="custom-radio" type="radio" id="<?php echo $Category; ?>" name="rdbCategory" value="<?php echo $Category; ?>" <?php echo ($Clean['Category'] == $Category ? 'checked="checked"' : ''); ?> >&nbsp;&nbsp;<?php echo $CategoryName; ?></label>            
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
                            <label for="ResidentAddress" class="col-lg-2 control-label">Resident Address</label>
                            <div class="col-lg-4">
                                <textarea class="form-control" id="ResidentAddress" name="txtResidentAddress"><?php echo $Clean['ResidentAddress']; ?></textarea>
                            </div>
                            <label for="PermanentAddress" class="col-lg-2 control-label">Permanent Address</label>
                            <div class="col-lg-4">
                            	<textarea class="form-control" id="PermanentAddress" name="txtPermanentAddress"><?php echo $Clean['PermanentAddress']; ?></textarea>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="Country" class="col-lg-2 control-label">Country</label>
                            <div class="col-lg-4">
                            	<select class="form-control" name="drdCountry" id="Country">
<?php
                                foreach ($CountryList as $CountryID => $CountryName) 
                                {
                                    echo '<option ' . ($Clean['CountryID'] == $CountryID ? 'selected="selected"' : '') . ' value="' . $CountryID . '">' . $CountryName . '</option>';
                                }
?>
								</select>
                            </div>
                            <label for="State" class="col-lg-2 control-label">State</label>
                            <div class="col-lg-4">
                            	<select class="form-control" name="drdState" id="State">
<?php
                                foreach ($StateList as $StateID => $StateName) 
                                {
                                    echo '<option ' . ($Clean['StateID'] == $StateID ? 'selected="selected"' : '') . ' value="' . $StateID . '">' . $StateName . '</option>';
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
                                foreach ($CityList as $CityID => $CityName) 
                                {
                                    echo '<option ' . ($Clean['CityID'] == $CityID ? 'selected="selected"' : '') . ' value="' . $CityID . '">' . $CityName . '</option>';
                                }
?>
								</select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="PinCode" class="col-lg-2 control-label">PinCode</label>
                            <div class="col-lg-4">
                                <input class="form-control" type="text" maxlength="6" id="PinCode" name="txtPinCode" value="<?php echo $Clean['PinCode']; ?>" />
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
                                <textarea class="form-control" id="LastSchool" name="txtLastSchool"><?php echo $Clean['LastSchool']; ?></textarea>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="LastSchoolBoard" class="col-lg-2 control-label">Last School Board</label>
                            <div class="col-lg-10">
<?php
                            foreach($LastSchoolBoardList as $LastSchoolBoard => $LastSchoolBoardName)
                            {
?>                              
                                <label style="font-weight: normal;"><input class="custom-radio" type="radio" id="<?php echo $LastSchoolBoard; ?>" name="rdbLastSchoolBoard" value="<?php echo $LastSchoolBoard; ?>" <?php echo ($Clean['LastSchoolBoard'] == $LastSchoolBoard ? 'checked="checked"' : ''); ?> >&nbsp;&nbsp;<?php echo $LastSchoolBoardName; ?></label>            
<?php                                       
                            }
?>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="TCReceived" class="col-lg-2 control-label">TC Received</label>
                            <div class="col-lg-4">
<?php
                            foreach($TCReceivedList as $TCReceived => $TCReceivedName)
                            {
?>                              
                                <label style="font-weight: normal;"><input class="custom-radio TCReceived" type="radio" id="<?php echo $TCReceived; ?>" name="rdbTCReceived" value="<?php echo $TCReceived; ?>" <?php echo ($Clean['TCReceived'] == $TCReceived ? 'checked="checked"' : ''); ?> >&nbsp;&nbsp;<?php echo $TCReceivedName; ?></label>            
<?php                                       
                            }
?>
                            </div>
                            <label for="TCDate" class="col-lg-2 control-label">TC Date</label>
                            <div class="col-lg-4">
                                <input class="form-control select-date" type="text" maxlength="10" id="TCDate" name="txtTCDate" value="<?php echo ($Clean['TCDate']) ? date('d/m/Y', strtotime($Clean['TCDate'])) : ''; ?>" <?php echo(!$Clean['TCReceived']) ? 'disabled="disabled"' : ''?>/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="SubjectsProposed" class="col-lg-2 control-label">Subjects Proposed</label>
                            <div class="col-lg-4">
                                <textarea class="form-control" type="text" name="txtSubjectsProposed"><?php echo $Clean['SubjectsProposed']; ?></textarea>
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
                                <input class="form-control" id="HomeTown" maxlength="50" type="text" name="txtHomeTown">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="LastExamStatus" class="col-lg-2 control-label">Last Exam Result</label>
                            <div class="col-lg-4">
<?php
                            foreach($LastExamStatusList as $LastExamStatus => $LastExamStatusName)
                            {
?>                              
                                <label style="font-weight: normal;"><input class="custom-radio" type="radio" id="<?php echo $LastExamStatus; ?>" name="rdbLastExamStatus" value="<?php echo $LastExamStatus; ?>" <?php echo ($Clean['LastExamStatus'] == $LastExamStatus ? 'checked="checked"' : ''); ?> >&nbsp;&nbsp;<?php echo $LastExamStatusName; ?></label>            
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

                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <strong>Entrance Exam Details</strong>
                        </div>
                        <div class="panel-body">
                            <div class="form-group">
                                <label for="ExamID" class="col-lg-2 control-label">Exam</label>
                                <div class="col-lg-4">
                                    <select class="form-control" id="ExamID" name="drdExam" >
                                        <option value="0">Select Entrance Exam</option>
    <?php
                                    foreach ($AllExamsList as $ExamID => $ExamName) 
                                    {
    ?>
                                        <option value="<?php echo $ExamID; ?>" exam-date="<?php echo date('d/m/Y', strtotime($AllExams[$ExamID]['ExamDate']));?>" exam-time="<?php echo $AllExams[$ExamID]['ExamTime'];?>" exam-duration="<?php echo $AllExams[$ExamID]['ExamDuration'];?>" <?php echo($ExamID == $Clean['ExamID']) ? 'selected="selected"' : '';?> ><?php echo $ExamName; ?></option>
    <?php
                                    }
    ?>                          
                                    </select>                               
                                </div>
                                <label for="ExamDuration" class="col-lg-2 control-label">Exam Duration <br><small>(In Minutes)</small></label>
                                <div class="col-lg-4">
                                    <input class="form-control" type="text" maxlength="3" id="ExamDuration" name="txtExamDuration" value="<?php echo ($Clean['ExamID']) ? $AllExams[$Clean['ExamID']]['ExamDuration'] : '';?>" disabled="disabled"/>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="ExamDate" class="col-lg-2 control-label">Exam Date</label>
                                <div class="col-lg-4">
                                    <input class="form-control dtepicker" type="text" maxlength="10" id="ExamDate" name="txtExamDate" value="<?php echo ($Clean['ExamDate'] != '') ? $Clean['ExamDate'] : '';?>" />
                                </div>
                                <label for="ExamTime" class="col-lg-2 control-label">Exam Time</label>
                                <div class="col-lg-4">
                                    <input class="form-control" type="time" maxlength="8" id="ExamTime" name="txtExamTime" value="<?php echo ($Clean['ExamID']) ? $Clean['ExamTime'] : '';?>"/>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="RegistrationFee" class="col-lg-2 control-label">Registration Fee</label>
                                <div class="col-lg-4">
                                    <input class="form-control" type="text" id="RegistrationFee" maxlength="9" name="txtRegistrationFee" value="<?php echo ($Clean['RegistrationFee']) ? $Clean['RegistrationFee'] : '';?>" >
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="IsActive" class="col-lg-2 control-label">Is Active</label>
                                <div class="col-lg-4">
                                    <input type="checkbox" id="IsActive" name="chkIsActive" value="1" <?php echo ($Clean['IsActive'] == 1) ? 'checked="checked"' : ''; ?>/>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="col-sm-offset-2 col-lg-10">
                                    <input type="hidden" name="hdnProcess" value="3" />
                                    <input type="hidden" name="hdnStudentRegistrationID" value="<?php echo $Clean['StudentRegistrationID'];?>" />
                                    <button type="submit" class="btn btn-primary">Save</button>
                                </div>
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
<script type="text/javascript">
$(document).ready(function(){ 
  
    $(".select-date").datepicker({
		changeMonth: true,
		changeYear: true,
		dateFormat: 'dd/mm/yy'
    });

    $('.TCReceived').change(function(){
        
        if ($(this).val() == 1) 
        {            
            $('#TCDate').prop('disabled', false);
        }
        else
        {
            $('#TCDate').prop('disabled', true);
        }
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
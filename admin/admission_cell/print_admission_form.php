<?php
// error_reporting(E_ALL);
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once("../../classes/admission_cell/class.enquiries.php");
require_once("../../classes/admission_cell/class.student_registrations.php");

require_once("../../classes/school_administration/class.academic_years.php");
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

require_once("../../classes/fee_management/class.fee_groups.php");

require_once("../../classes/class.date_processing.php");
require_once("../../classes/class.helpers.php");

require_once("../../includes/helpers.inc.php");
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
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}
catch (Exception $e)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}
// END OF 1. //

if ($LoggedUser->HasPermissionForTask(TASK_ADD_STUDENT) !== true)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}

$SchoolName = 'Lucknow International Public School';
$AffilationDetails = 'RECOGNIZED (ENGLISH MEDIUM)';
$OtherDetails = 'Run Under Christian Community Educational Society, Lucknow';
$Address = 'Sitapur Rd, Opp Banshidhar Petrol Pump';
$Pincode = 226203;
$City = 'Itaunja';
$State = 'UP';
$ContactNumber = '075218 00903, 7521800904';
$AffilationNumber = '';

$HasErrors = false;

$AcademicYears =  array();
$AcademicYears = AcademicYear::GetAllAcademicYears();

$ActiveFeeGroups = array();
$ActiveFeeGroups = FeeGroup::GetActiveFeeGroups();

$StudentTypeList = array('New' => 'New', 'Old' => 'Old');
$GenderList = array('Male' => 'Male', 'Female' => 'Female', 'Others' => 'Others');
$BloodGroupList = array('A+' => 'A+', 'A-' => 'A-', 'B+' => 'B+', 'AB+' => 'AB+', 'AB-' => 'AB-', 'O+' => 'O+', 'O-' => 'O-');
$ReligionList = array('Hindu' => 'Hindu', 'Muslim' => 'Muslim', 'Sikh' => 'Sikh', 'Christian' => 'Christian', 'Others' => 'Others', 'Not Specified' => 'Not Specified');
$CategoryList = array('General' => 'General', 'OBC' => 'OBC', 'SC' => 'SC', 'ST' => 'ST');
$OtherCategoryList = array('IsEWS' => 'EWS', 'HasDisability' => 'Disabled', 'IsSingleGirl' => 'Single Girl');
$LastSchoolBoardList = array('CBSE' => 'CBSE', 'ICSE' => 'ICSE', 'UP' => 'UP');
$TCReceivedList = array(1 => 'Yes', 0 => 'No');
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

$Clean['StudentID'] = 0;
$Clean['AcademicYearID'] = 0;
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
$Clean['Religion'] = '';
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

$Clean['PinCode'] = '';
$Clean['AadharNumber'] = 0;

$Clean['AdmissionDate'] = date('d/m/Y');
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

$Clean['FeeGroupID'] = 0;
$Clean['FeeCode'] = '';
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
$Clean['RegistrationFee'] = 0;
$Clean['StudentPhoto'] = '';

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
	case 2:
        if (isset($_GET['StudentID']))
        {
            $Clean['StudentID'] = (int) $_GET['StudentID'];
        }  

        if ($Clean['StudentID'] <= 0)
        {
            header('location:../error.php');
            exit;
        }

        try
        {
            $StudentDetails = new StudentDetail($Clean['StudentID']);
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

        $Clean['ParentID'] = $StudentDetails->GetParentID();
        $Clean['RollNumber'] = $StudentDetails->GetRollNumber();
        $Clean['EnrollmentID'] = $StudentDetails->GetEnrollmentID();
        $Clean['StudentType'] = $StudentDetails->GetStudentType();
        $Clean['Status'] = $StudentDetails->GetStatus();
        $Clean['ColourHouseID'] = $StudentDetails->GetColourHouseID();
        $Clean['ClassSectionID'] = $StudentDetails->GetClassSectionID();

        $ClassSectionDetails = new ClassSections($StudentDetails->GetClassSectionID());
        
        $Clean['ClassID'] = $ClassSectionDetails->GetClassID();
        $ClassSectionsList = ClassSections::GetClassSections($Clean['ClassID']);

        $Clean['FirstName'] = $StudentDetails->GetFirstName();
        $Clean['LastName'] = $StudentDetails->GetLastName();

        if ($StudentDetails->GetDOB() != '0000-00-00') 
        {
            $Clean['DOB'] = date('d/m/Y', strtotime($StudentDetails->GetDOB()));
        }
        
        $Clean['Gender'] = $StudentDetails->GetGender();        
        $Clean['BloodGroup'] = $StudentDetails->GetBloodGroup();
        $Clean['Religion'] = $StudentDetails->GetReligion();
        $Clean['Category'] = $StudentDetails->GetCategory();

        $Clean['IsEWS'] = $StudentDetails->GetIsEWS();
        $Clean['HasDisability'] = $StudentDetails->GetHasDisability();
        $Clean['IsSingleGirl'] = $StudentDetails->GetIsSingleGirl();

        $Clean['Address1'] = $StudentDetails->GetAddress1();
        $Clean['Address2'] = $StudentDetails->GetAddress2();
        
        $Clean['CountryID'] = $StudentDetails->GetCountryID();
        
        $StateList = State::GetAllStates($Clean['CountryID']);
        $Clean['StateID'] = $StudentDetails->GetStateID();
        
        $DistrictList = City::GetAllDistricts($Clean['StateID']);
        $Clean['DistrictID'] = $StudentDetails->GetDistrictID();
        
        $CityList = City::GetAllCities($Clean['StateID'], $Clean['DistrictID']);
        $Clean['CityID'] = $StudentDetails->GetCityID();
        
        $Clean['Pincode'] = $StudentDetails->GetPincode();

        if ($StudentDetails->GetAdmissionDate() != '0000-00-00') 
        {
            $Clean['AdmissionDate'] = date('d/m/Y', strtotime($StudentDetails->GetAdmissionDate()));
        }
        
        $Clean['MobileNumber'] = $StudentDetails->GetMobileNumber();
        $Clean['Email'] = $StudentDetails->GetEmail();
        $Clean['AadharNumber'] = $StudentDetails->GetAadharNumber();

        $Clean['LastClass'] = $StudentDetails->GetLastClass();
        $Clean['LastSchool'] = $StudentDetails->GetLastSchool();
        $Clean['LastSchoolBoard'] = $StudentDetails->GetLastSchoolBoard();
        $Clean['TCReceived'] = $StudentDetails->GetTCReceived();

        if ($StudentDetails->GetTCDate() != '0000-00-00') 
        {
            $Clean['TCDate'] = date('d/m/Y', strtotime($StudentDetails->GetTCDate()));
        }

        $Clean['SubjectsProposed'] = $StudentDetails->GetSubjectsProposed();
        $Clean['MotherTongue'] = $StudentDetails->GetMotherTongue();
        $Clean['HomeTown'] = $StudentDetails->GetHomeTown();

        $Clean['LastExamStatus'] = $StudentDetails->GetLastExamStatus();
        $Clean['LastExamPercentage'] = $StudentDetails->GetLastExamPercentage();
       
        $ParentDetails = new ParentDetail($StudentDetails->GetParentID());

        $Clean['FatherFirstName'] = $ParentDetails->GetFatherFirstName();
        $Clean['FeeCode'] = $ParentDetails->GetFeeCode();
        $Clean['FatherLastName'] = $ParentDetails->GetFatherLastName();
        $Clean['MotherFirstName'] = $ParentDetails->GetMotherFirstName();
        $Clean['MotherLastName'] = $ParentDetails->GetMotherLastName();

        $Clean['FatherOccupation'] = $ParentDetails->GetFatherOccupation();
        $Clean['MotherOccupation'] = $ParentDetails->GetMotherOccupation();
        $Clean['FatherOfficeName'] = $ParentDetails->GetFatherOfficeName();
        $Clean['MotherOfficeName'] = $ParentDetails->GetMotherOfficeName();
        $Clean['FatherOfficeAddress'] = $ParentDetails->GetFatherOfficeAddress();
        $Clean['MotherOfficeAddress'] = $ParentDetails->GetMotherOfficeAddress();

        $Clean['ResidentailAddress'] = $ParentDetails->GetResidentailAddress();
        $Clean['ResidentailCityID'] = $ParentDetails->GetResidentailCityID();
        $Clean['ResidentailDistrictID'] = $ParentDetails->GetResidentailDistrictID();
        $Clean['ResidentailStateID'] = $ParentDetails->GetResidentailStateID();
        $Clean['ResidentailCountryID'] = $ParentDetails->GetResidentailCountryID();
        $Clean['ResidentailPinCode'] = $ParentDetails->GetResidentailPinCode();
        
        $ResidentailStateList = State::GetAllStates($Clean['ResidentailCountryID']);
        $ResidentailDistrictList = City::GetAllDistricts($Clean['ResidentailStateID']);
        $ResidentailCityList = City::GetAllCities($Clean['ResidentailStateID'], $Clean['ResidentailDistrictID']);

        $Clean['PermanentAddress'] = $ParentDetails->GetPermanentAddress();
        $Clean['PermanentCityID'] = $ParentDetails->GetPermanentCityID();
        $Clean['PermanentDistrictID'] = $ParentDetails->GetPermanentDistrictID();
        $Clean['PermanentStateID'] = $ParentDetails->GetPermanentStateID();
        $Clean['PermanentCountryID'] = $ParentDetails->GetPermanentCountryID();
        $Clean['PermanentPinCode'] = $ParentDetails->GetPermanentPinCode();
        
        $PermanentStateList = State::GetAllStates($Clean['PermanentCountryID']);
        $PermanentDistrictList = City::GetAllDistricts($Clean['PermanentStateID']);
        $PermanentCityList = City::GetAllCities($Clean['PermanentStateID'], $Clean['PermanentDistrictID']);

        $Clean['PhoneNumber'] = $ParentDetails->GetPhoneNumber();
        $Clean['FatherMobileNumber'] = $ParentDetails->GetFatherMobileNumber();
        $Clean['MotherMobileNumber'] = $ParentDetails->GetMotherMobileNumber();

        $Clean['FatherEmail'] = $ParentDetails->GetFatherEmail();
        $Clean['MotherEmail'] = $ParentDetails->GetMotherEmail();

        $Clean['ParentAadharNumber'] = $ParentDetails->GetAadharNumber();

        $Clean['AcademicYearID'] = $StudentDetails->GetAcademicYearID();
        
        $Clean['StudentPhoto'] = $StudentDetails->GetStudentPhoto();

        $StudentRegistrationOgj = new StudentRegistration($StudentDetails->GetStudentRegistrationID());

        $Clean['RegistrationFee'] = $StudentRegistrationOgj->GetRegistrationFee();

        $StudentPhoto = '/images/profile-dummy.png';
        
        if (file_exists(SITE_FS_PATH . '/site_images/student_images/' . $StudentDetails->GetStudentID() . '/' . $StudentDetails->GetStudentPhoto()))
        {
            $StudentPhoto = '/site_images/student_images/' . $StudentDetails->GetStudentID() . '/' . $StudentDetails->GetStudentPhoto();
        }
        
        break;
}

require_once('../html_header.php');
?>
<title>Print Admission Form</title>
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
                    <h1 class="page-header">Print Admission Form</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
            	<div class="panel panel-default">
                    <div class="panel-heading">
                        <strong>Student Details</strong>
                    </div>
                    <div class="panel-body">
                    <div>
                        <div class="row">
                            <div class="col-lg-6">                                     
                            </div>
                            <div class="col-lg-6">
                                <div class="print-btn-container"><button id="PrintButton" type="button" class="btn btn-primary">Print</button></div>
                            </div>
                        </div>
                        <div class="row" id="RecordTableHeading" style="page-break-before: always;">
                            <style type="text/css">
                                .flex-class{
                                    font-family: normal; 
                                    margin-top: 10px; 
                                    display: flex;
                                }

                                .span-data{
                                    border-bottom: 1px dotted #000; 
                                    text-decoration: none; 
                                }

                                .card {
                                  /* Add shadows to create the "card" effect */
                                  border: 1px solid black;
                                  width:350px;
                                  margin:10px;
                                  box-shadow: 0 4px 8px 0 rgba(0,0,0,0.2);
                                  transition: 0.3s;
                                }

                                /* On mouse-over, add a deeper shadow */
                                .card:hover {
                                  box-shadow: 0 8px 16px 0 rgba(0,0,0,0.2);
                                }

                                /* Add some padding inside the card container */
                                .container {
                                  padding: 2px 16px;
                                }

                                .card {
                                  box-shadow: 0 4px 8px 0 rgba(0,0,0,0.2);
                                  transition: 0.3s;
                                  border-radius: 5px; /* 5px rounded corners */
                                }

                                /* Add rounded corners to the top left and the top right corner of the image */
                                img {
                                  border-radius: 5px;
                                }

                                .id-card-header {
                                    padding:10px;
                                }

                                .organization-info {
                                    text-align:center;
                                }

                                .id-card-header {
                                    border-bottom:1px solid;
                                    padding-bottom: 10px;
                                }

                                .id-card-container {
                                    padding:10px;
                                    padding-top:4px;
                                }
                            </style>


                            <div class="align-center" style="position: relative; width:740px; height:1050px; border:1px solid grey; display:block;">
                                <div style="height:100px; width:130px; margin-left:10px; float:left; margin-top:5px;">
                                    <img src="../../../site_images/school_logo/GMS-LOGO-ADDEd.png" height="80px;" width="130px;" style="margin-left:12px; margin-top:10px;" align="center;">
                                </div>
                                
                                <div style="height:100px; width:550px; float:left; margin-left:10px; margin-top:10px; text-align:center;">
                                    <strong style="font-size:25px;"><?php echo $SchoolName; ?></strong>
                                    <br><strong style="font-size:15px;"><?php echo $Address ; ?></strong>
                                    <br><strong style="font-size:15px;"><?php echo 'PIN CODE- '. $Pincode .' Phone: '. $ContactNumber ; ?></strong>
                                    <br><strong style="font-size:18px;"><?php echo $AffilationDetails; ?></strong>
                                </div>
                                
                                <!--<div style="height:100px; width:130px; margin-right:10px; float:left; margin-top:5px;">-->
                                <!--    <img src="../../../site_images/school_logo/GMS-LOGO-ADDEd.png" height="80px;" width="90px;" style="margin-left:22px; margin-top:10px;" align="center;">-->
                                <!--</div>-->
                                
                                <center>
                                    <div style="height:30px; width:300px; margin-top:115px; margin-left: 70px; text-align:center; border:1px solid black; display:block; border-radius: 5px;">
                                        <p style=" font-size:18px; margin-top: 1px;"><b>Admission Form <?php echo date('Y', strtotime($AcademicYears[$Clean['AcademicYearID']]['StartDate'])) .' - '. date('Y', strtotime($AcademicYears[$Clean['AcademicYearID']]['EndDate'])) ; ?> </b></p>
                                    </div>
                                </center>
                                <div style="float: left; width:740px; margin-top: 20px; margin-left: 10px;">

                                    <p class="flex-class"><span style="float: left; width: 70px; text-align: left;">Roll No :</span><span class="span-data" style="width: 70px; margin-left: 15px;"><b>&nbsp;&nbsp;<?php echo ($Clean['RollNumber']) ? $Clean['RollNumber'] : '' ;?>&nbsp;&nbsp;</b></span><span style="float: left; width: 80px; text-align: left; margin-left: 45px;">SR No : </span><span class="span-data" style="width: 120px; margin-left: 5px;"><b>&nbsp;&nbsp;<?php echo $Clean['EnrollmentID'];?>&nbsp;&nbsp;</b></span><span style="float: left; width: 120px; text-align: left; margin-left: 45px;">Admission Date :</span><span class="span-data" style="width: 130px; margin-left: 5px;"><b>&nbsp;&nbsp;<?php echo $Clean['AdmissionDate'] ;?>&nbsp;&nbsp;</b></span></p>
                                    <div style=" width: 550; float: left;">
                                        <p class="flex-class"><span style="float: left; width: 140px; text-align: left;">Name of the Applicant :</span><span class="span-data" style="width: 390px; margin-left: 15px;"><b>&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $Clean['FirstName'] .' '. $Clean['LastName'];?></b></span></p>
                                        <p class="flex-class"><span style="float: left; width: 140px; text-align: left;">Class :</span><span class="span-data" style="width: 150px; margin-left: 15px;"><b>&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $ClassList[$Clean['ClassID']] .' '. $ClassSectionsList[$Clean['ClassSectionID']];?></b></span><span style="float: left; width: 80px; text-align: left; margin-left: 15px;">Gender :</span><span class="span-data" style="width: 140px; margin-left: 5px;"><b>&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $Clean['Gender'];?></b></span></p>
                                        <p class="flex-class"><span style="float: left; width: 140px; text-align: left;">Date Of Birth :</span><span class="span-data" style="width: 150px; margin-left: 15px;"><b>&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $Clean['DOB'];?></b></span><span style="float: left; width: 80px; text-align: left; margin-left: 15px;">Category :</span><span class="span-data" style="width: 140px; margin-left: 5px;"><b>&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $Clean['Category'];?></b></span></p>
                                        <p class="flex-class"><span style="float: left; width: 140px; text-align: left;">Nationality :</span><span class="span-data" style="width: 150px; margin-left: 15px;"><b>&nbsp;&nbsp;&nbsp;&nbsp;Indian</b></span><span style="float: left; width: 80px; text-align: left; margin-left: 15px;">Religion :</span><span class="span-data" style="width: 140px; margin-left: 5px;"><b>&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $Clean['Religion'];?></b></span></p>
                                        <p class="flex-class">
                                            <span style="float: left; width: 140px; text-align: left;">Blood Group :</span><span class="span-data" style="width: 150px; margin-left: 15px;"><b>&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $Clean['BloodGroup'];?></b></span>
                                            <span style="float: left; width: 270px; text-align: left; margin-left: 10px;"><label class="checkbox-inline"><input class="custom-radio" type="checkbox" name="chkIsEWS" disabled="disabled" value="1" <?php echo ($Clean['IsEWS'] == 1 ? 'checked="checked"' : ''); ?> >EWS</label><label class="checkbox-inline"><input class="custom-radio" type="checkbox" name="chkHasDisability" value="1" disabled="disabled" <?php echo ($Clean['HasDisability'] == 1 ? 'checked="checked"' : ''); ?> >Disabled</label><label class="checkbox-inline"><input class="custom-radio" type="checkbox" name="chkIsSingleGirl" value="1" disabled="disabled" <?php echo ($Clean['IsSingleGirl'] == 1 ? 'checked="checked"' : ''); ?> >Single Girl</label></b></span>
                                        </p>
                                    </div>
                                    <div style="width:150px; float:right;">
                                        
<?php
                                        if ($Clean['StudentPhoto'] == '') 
                                        {
                                            echo '<div style="display: block; height: 150px; width: 130px; margin-top:1px; border: 1px solid black; border-radius: 5px; line-height: 150px; text-align: center;">Photo</div>';
                                        }
                                        else
                                        {
?>
                                            <img src="<?php echo '../../site_images/student_images/' .$Clean['StudentID'] .'/' . $Clean['StudentPhoto'];?>" style="display: block; height: 150px; width: 130px; margin-top:1px; border: 1px solid black; border-radius: 5px; line-height: 150px; text-align: center;" align="center;" alt="Photo" >
<?php        
                                        }
?>
                                        <!-- <img src="../../../site_images/school_logo/aaa.png" style="height: 150px; width: 130px; margin-top:1px; border: 1px solid black; border-radius: 5px;" align="center;"> -->
                                    </div>
                                </div>
                                <div style="float: left; width:740px; margin-left: 10px;">

                                    <p class="flex-class"><span style="float: left; width: 140px; text-align: left;">Mobile Number :</span><span class="span-data" style="width: 225px; margin-left: 15px;"><b>&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $Clean['MobileNumber'];?></b></span><span style="float: left; width: 70px; text-align: left; margin-left: 25px;">Email :</span><span class="span-data" style="width: 230px; margin-left: 5px;"><b>&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $Clean['Email'];?></b></span></p>
                                    <p class="flex-class"><span style="float: left; width: 140px; text-align: left;">Permanent Address :</span><span class="span-data" style="width: 555px; margin-left: 15px;"><b>&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $Clean['Address1'];?></b></span></p>
                                    <p class="flex-class"><span style="float: left; width: 140px; text-align: left;">Residential Address :</span><span class="span-data" style="width: 555px; margin-left: 15px;"><b>&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $Clean['Address2'];?></b></span></p>
                                    <p class="flex-class"><span style="float: left; width: 140px; text-align: left;">Pincode :</span><span class="span-data" style="width: 225px; margin-left: 15px;"><b>&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $Clean['PinCode'];?></b></span><span style="float: left; width: 80px; text-align: left; margin-left: 25px;">Aadhar No :</span><span class="span-data" style="width: 220px; margin-left: 5px;"><b>&nbsp;&nbsp;&nbsp;&nbsp;<?php echo ($Clean['AadharNumber']) ? $Clean['AadharNumber'] : '';?></b></span></p>
                                    
                                    <p class="flex-class"><span style="float: left; width: 165px; text-align: left; font-size: 16px; border-bottom: 2px solid black; margin-top: 5px;"><b>Last Academic Details :</b></span></p>
                                    <p class="flex-class"><span style="float: left; width: 140px; text-align: left;">Last Class :</span><span class="span-data" style="width: 225px; margin-left: 15px;"><b>&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $Clean['LastClass'];?></b></span><span style="float: left; width: 90px; text-align: left; margin-left: 25px;">School Board :</span><span class="span-data" style="width: 210px; margin-left: 5px;"><b>&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $Clean['LastSchoolBoard'];?></b></span></p>
                                    <p class="flex-class"><span style="float: left; width: 140px; text-align: left;">Last Exam Result :</span><span class="span-data" style="width: 225px; margin-left: 15px;"><b>&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $Clean['LastExamStatus'];?></b></span><span style="float: left; width: 90px; text-align: left; margin-left: 25px;">Percentage :</span><span class="span-data" style="width: 210px; margin-left: 5px;"><b>&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $Clean['LastExamPercentage'];?></b></span></p>
                                    <p class="flex-class"><span style="float: left; width: 140px; text-align: left;">TC Received :</span><span class="span-data" style="width: 225px; margin-left: 15px;"><b>&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $TCReceivedList[$Clean['TCReceived']] ;?></b></span><span style="float: left; width: 90px; text-align: left; margin-left: 25px;">TC Date :</span><span class="span-data" style="width: 210px; margin-left: 5px;"><b>&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $Clean['TCDate'];?></b></span></p>
                                    <p class="flex-class"><span style="float: left; width: 140px; text-align: left;">Last School :</span><span class="span-data" style="width: 555px; margin-left: 15px;"><b>&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $Clean['LastSchool'];?></b></span></p>

                                </div>

                                <div style="float: left; width:740px; margin-left: 10px;">
                                    <p class="flex-class"><span style="float: left; width: 125px; text-align: left; font-size: 16px; border-bottom: 2px solid black; margin-top: 5px;"><b>Parent Details :</b></span></p>
                                    <p class="flex-class"><span style="float: left; width: 140px; text-align: left;">Father's Name :</span><span class="span-data" style="width: 225px; margin-left: 15px;"><b>&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $Clean['FatherFirstName'] .' '. $Clean['FatherLastName'];?></b></span><span style="float: left; width: 130px; text-align: left; margin-left: 10px;">Mother's Name :</span><span class="span-data" style="width: 180px; margin-left: 5px;"><b>&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $Clean['MotherFirstName'] .' '. $Clean['MotherLastName'];?></b></span></p>
                                    <p class="flex-class"><span style="float: left; width: 140px; text-align: left;">Father's Occupation :</span><span class="span-data" style="width: 225px; margin-left: 15px;"><b>&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $Clean['FatherOccupation'];?></b></span><span style="float: left; width: 130px; text-align: left; margin-left: 10px;">Mother's Occupation :</span><span class="span-data" style="width: 180px; margin-left: 5px;"><b>&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $Clean['MotherOccupation'];?></b></span></p>
                                    <p class="flex-class"><span style="float: left; width: 140px; text-align: left;">Father Mobile No :</span><span class="span-data" style="width: 225px; margin-left: 15px;"><b>&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $Clean['FatherMobileNumber'] ;?></b></span><span style="float: left; width: 130px; text-align: left; margin-left: 10px;">Mother Mobile No :</span><span class="span-data" style="width: 180px; margin-left: 5px;"><b>&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $Clean['MotherMobileNumber'];?></b></span></p>
                                    <p class="flex-class"><span style="float: left; width: 140px; text-align: left;">Father Email :</span><span class="span-data" style="width: 225px; margin-left: 15px;"><b>&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $Clean['FatherEmail'] ;?></b></span><span style="float: left; width: 100px; text-align: left; margin-left: 10px;">Mother Email :</span><span class="span-data" style="width: 210px; margin-left: 5px;"><b>&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $Clean['MotherEmail'];?></b></span></p>
                                    <p class="flex-class"><span style="float: left; width: 140px; text-align: left;">Permanent Address :</span><span class="span-data" style="width: 555px; margin-left: 15px;"><b>&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $Clean['ResidentailAddress'];?></b></span></p>
                                    <p class="flex-class"><span style="float: left; width: 140px; text-align: left;">Residential Address :</span><span class="span-data" style="width: 555px; margin-left: 15px;"><b>&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $Clean['PermanentAddress'];?></b></span></p>
                                    <p class="flex-class" style="margin-top: 20px;"><span style="float: left; width: 140px; text-align: left;">Registration Fee :</span><span class="span-data" style="width: 120px; margin-left: 15px;"><b>&nbsp;&nbsp;&nbsp;&nbsp;<?php echo ($Clean['RegistrationFee']) ? $Clean['RegistrationFee'] : '';?></b></span></p>
                                    
                                </div>
                                <div style="float: left; margin-top: 30px; width: 740px;">
                                    <p style="margin-right: 60px;"><span style="float: right; width: 165px; text-align: right; border-top: 1px solid black; margin-top: 5px;">Prepared & Checked by</span></p>
                                </div>
                            </div>
                            <div style="page-break-after: always;"></div>

                            <!-- ID Card -->
                            <div class="card col-sm-6">
                                <div class="id-card-header">
                                    <table>
                                        <tr>
                                            <td width="100">
                                                <img src="../../../site_images/school_logo/GMS-LOGO-ADDEd.png" height="70px;" width="80px;" style="" align="center;">
                                            </td>
                                            <td>
                                                <div class="organization-info">
                                                    <strong style="font-size:15px;"><?php echo $SchoolName; ?></strong>
                                                    <p style="margin-bottom:2px; height:10px;"><strong style="font-size:10px;"><?php echo $AffilationDetails; ?></strong></p>
                                                    <p style="margin-bottom:2px; height:10px;"><strong style="font-size:10px;"><?php echo $Address; ?></strong></p>
                                                    <p style="margin-bottom:2px; height:10px;"><strong style="font-size:10px;"><?php echo 'Phone No. '. $ContactNumber; ?></strong></p>
                                                </div>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                                
                                <div class="id-card-container">
                                    <p style="text-align:center; margin:5px; margin-top:0px; font-size:14px;"><u>Indentity Card</u></p>
                                    <table>
                                        <tr>
                                            <td><img src="<?php echo $StudentPhoto; ?>" height="88" width="75" /></td>
                                            <td style="padding-left: 10px;">
                                                <strong  style="margin-bottom:8px;"><?php echo strtoupper($Clean['FirstName'] . ' ' . $Clean['LastName']); ?></strong>
                                                <p style="margin-bottom:5px;"><?php echo strtoupper($Clean['FatherFirstName'] . ' ' . $Clean['FatherLastName']); ?></p>
                                                <p style="margin-bottom:5px;"><?php echo $ClassList[$Clean['ClassID']] .' ('. $ClassSectionsList[$Clean['ClassSectionID']] .') '; ?></p>
                                                <p style="margin-bottom:5px; color:red;"><b><?php echo $Clean['FatherMobileNumber'] .', '. $Clean['MobileNumber'] ; ?></b></p>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>

                            <!-- Bus Pass -->
                            <div class="card col-sm-6">
                                <div class="id-card-header">
                                    <table>
                                        <tr>
                                            <td width="100">
                                                <img src="../../../site_images/school_logo/GMS-LOGO-ADDEd.png" height="70px;" width="80px;" style="" align="center;">
                                            </td>
                                            <td>
                                                <div class="organization-info">
                                                    <strong style="font-size:15px;"><?php echo $SchoolName; ?></strong>
                                                    <p style="margin-bottom:2px; height:10px;"><strong style="font-size:10px;"><?php echo $AffilationDetails; ?></strong></p>
                                                    <p style="margin-bottom:2px; height:10px;"><strong style="font-size:10px;"><?php echo $Address; ?></strong></p>
                                                    <p style="margin-bottom:2px; height:10px;"><strong style="font-size:10px;"><?php echo 'Phone No. '. $ContactNumber; ?></strong></p>
                                                </div>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                                
                                <div class="id-card-container">
                                    <p style="text-align:center; margin:5px; margin-top:0px; font-size:14px;"><u>Bus Card</u></p>
                                    <table>
                                        <tr>
                                            <td><img src="<?php echo $StudentPhoto; ?>" height="88" width="75" /></td>
                                            <td style="padding-left: 10px;">
                                                <strong  style="margin-bottom:8px;"><?php echo strtoupper($Clean['FirstName'] . ' ' . $Clean['LastName']); ?></strong>
                                                <p style="margin-bottom:5px;"><?php echo strtoupper($Clean['FatherFirstName'] . ' ' . $Clean['FatherLastName']); ?></p>
                                                <p style="margin-bottom:5px;"><?php echo $ClassList[$Clean['ClassID']] .' ('. $ClassSectionsList[$Clean['ClassSectionID']] .') '; ?></p>
                                                <p style="margin-bottom:5px; color:red;"><b><?php echo $Clean['FatherMobileNumber'] .', '. $Clean['MobileNumber'] ; ?></b></p>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>

                            <!-- Parent Pass -->
                            <div class="card col-sm-6">
                                <div class="id-card-header">
                                    <table>
                                        <tr>
                                            <td width="100">
                                                <img src="../../../site_images/school_logo/GMS-LOGO-ADDEd.png" height="70px;" width="80px;" style="" align="center;">
                                            </td>
                                            <td>
                                                <div class="organization-info">
                                                    <strong style="font-size:15px;"><?php echo $SchoolName; ?></strong>
                                                    <p style="margin-bottom:2px; height:10px;"><strong style="font-size:10px;"><?php echo $AffilationDetails; ?></strong></p>
                                                    <p style="margin-bottom:2px; height:10px;"><strong style="font-size:10px;"><?php echo $Address; ?></strong></p>
                                                    <p style="margin-bottom:2px; height:10px;"><strong style="font-size:10px;"><?php echo 'Phone No. '. $ContactNumber; ?></strong></p>
                                                </div>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                                
                                <div class="id-card-container">
                                    <p style="text-align:center; margin:5px; margin-top:0px; font-size:14px;"><u>Parent Pass</u></p>
                                    <table>
                                        <tr>
                                            <td><img src="<?php echo $StudentPhoto; ?>" height="88" width="75" /></td>
                                            <td style="padding-left: 10px;">
                                                <strong  style="margin-bottom:8px;"><?php echo strtoupper($Clean['FirstName'] . ' ' . $Clean['LastName']); ?></strong>
                                                <p style="margin-bottom:5px;"><?php echo strtoupper($Clean['FatherFirstName'] . ' ' . $Clean['FatherLastName']); ?></p>
                                                <p style="margin-bottom:5px;"><?php echo $ClassList[$Clean['ClassID']] .' ('. $ClassSectionsList[$Clean['ClassSectionID']] .') '; ?></p>
                                                <p style="margin-bottom:5px; color:red;"><b><?php echo $Clean['FatherMobileNumber'] .', '. $Clean['MobileNumber'] ; ?></b></p>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- /#page-wrapper -->
    </div>
    <!-- /#wrapper -->
<?php
require_once('../footer.php');
if (PrintMessage($_GET, $Message))
{
?>
    <script type="text/javascript">
        alert('<?php echo $Message; ?>');
    </script>
<?php
}
?>
<script src="../vendor/jquery-ui/jquery-ui.min.js"></script>
<link href="../vendor/jquery-ui/jquery-ui.min.css" rel="stylesheet">

<script src="../js/print-report.js"></script>

</body>
</html>
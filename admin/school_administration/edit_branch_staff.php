<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once("../../classes/school_administration/class.countries.php");
require_once("../../classes/school_administration/class.states.php");
require_once("../../classes/school_administration/class.cities.php");
require_once("../../classes/school_administration/class.subject_master.php");
require_once("../../classes/school_administration/class.branch_staff.php");

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

if ($LoggedUser->HasPermissionForTask(TASK_EDIT_BRANCH_STAFF) !== true)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}

$Clean = array();
$Clean['BranchStaffID'] = 0;

if (isset($_GET['BranchStaffID']))
{
    $Clean['BranchStaffID'] = (int) $_GET['BranchStaffID'];
}
else if (isset($_POST['hdnBranchStaffID']))
{
    $Clean['BranchStaffID'] = (int) $_POST['hdnBranchStaffID'];
}

if ($Clean['BranchStaffID'] <= 0)
{
    header('location:../error.php');
    exit;
}   

try
{
    $BranchStaffToEdit = new BranchStaff($Clean['BranchStaffID']);
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

$CountryList = array();
$CountryList = Country::GetAllCountries();

$StateList = array();
$CityList = array();
$DistrictList = array();

$AllSubjectMastersList = array();
$AllSubjectMastersList = SubjectMaster::GetActiveSubjectMasters();

$HasErrors = false;

$StaffCategoryList = array('Teaching' => 'Teaching', 'NonTeaching' => 'Non Teaching');
$acceptable_extensions = array('jpeg', 'jpg', 'png', 'gif');
$acceptable_mime_types = array(
    'image/jpeg',
    'image/jpg', 
    'image/png', 
    'image/gif' 
);
$Clean['UploadFile'] = array();

$Clean['Process'] = 0;

$Clean['FirstName'] = '';
$Clean['LastName'] = '';
$Clean['Gender'] = 'Male';

$Clean['Address1'] = '';
$Clean['Address2'] = '';

$Clean['CountryID'] = '';
$Clean['CountryID'] = key($CountryList);
$StateList = State::GetAllStates($Clean['CountryID']);

$Clean['StateID'] = key($StateList);

$DistrictList = City::GetAllDistricts($Clean['StateID']);
$Clean['DistrictID'] = key($DistrictList);

$CityList = City::GetAllCities($Clean['StateID'], $Clean['DistrictID']);
$Clean['CityID'] = key($CityList);

$Clean['PinCode'] = '';

$Clean['PhoneNumber'] = '';
$Clean['MobileNumber1'] = '';
$Clean['MobileNumber2'] = '';

$Clean['Email'] = '';
$Clean['AadharNumber'] = 0;
$Clean['DOB'] = '';

$Clean['StaffCategory'] = '';

$Clean['HighestQualification'] = '';
$Clean['SpecialitySubjectID'] = 0;
$Clean['JoiningDate'] = '';

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
        if (isset($_POST['txtFirstName']))
        {
            $Clean['FirstName'] = strip_tags(trim($_POST['txtFirstName']));
        }

        if (isset($_POST['txtLastName']))
        {
            $Clean['LastName'] = strip_tags(trim($_POST['txtLastName']));
        }

        if (isset($_POST['rdbGender']))
        {
            $Clean['Gender'] = strip_tags(trim($_POST['rdbGender']));
        }

        if (isset($_POST['txtAddress1']))
        {
            $Clean['Address1'] = strip_tags(trim($_POST['txtAddress1']));
        }

        if (isset($_POST['txtAddress2']))
        {
            $Clean['Address2'] = strip_tags(trim($_POST['txtAddress2']));
        }

        if (isset($_POST['drdCityID']))
        {
            $Clean['CityID'] = (int) $_POST['drdCityID'];
        }

        if (isset($_POST['drdDistrictID']))
        {
            $Clean['DistrictID'] = (int) $_POST['drdDistrictID'];
        }

        if (isset($_POST['drdStateID']))
        {
            $Clean['StateID'] = (int) $_POST['drdStateID'];
        }

        if (isset($_POST['drdCountryID']))
        {
            $Clean['CountryID'] = (int) $_POST['drdCountryID'];
        }

        if (isset($_POST['txtPinCode']))
        {
            $Clean['PinCode'] = strip_tags(trim($_POST['txtPinCode']));
        }

        if (isset($_POST['txtPhoneNumber']))
        {
            $Clean['PhoneNumber'] = strip_tags(trim($_POST['txtPhoneNumber']));
        }

        if (isset($_POST['txtMobileNumber1']))
        {
            $Clean['MobileNumber1'] = strip_tags(trim($_POST['txtMobileNumber1']));
        }

        if (isset($_POST['txtMobileNumber2']))
        {
            $Clean['MobileNumber2'] = strip_tags(trim($_POST['txtMobileNumber2']));
        }

        if (isset($_POST['txtEmail']))
        {
            $Clean['Email'] = strip_tags(trim($_POST['txtEmail']));
        }

        if (isset($_POST['txtAadharNumber']))
        {
            $Clean['AadharNumber'] = strip_tags(trim($_POST['txtAadharNumber']));
        }

        if (isset($_POST['txtDOB']))
        {
            $Clean['DOB'] = strip_tags(trim($_POST['txtDOB']));
        }

        if (isset($_POST['drdStaffCategory']))
        {
            $Clean['StaffCategory'] = strip_tags(trim($_POST['drdStaffCategory']));
        }

        if (isset($_POST['txtHighestQualification']))
        {
            $Clean['HighestQualification'] = strip_tags(trim($_POST['txtHighestQualification']));
        }

        if (isset($_POST['drdSpecialitySubjectID']))
        {
            $Clean['SpecialitySubjectID'] = (int) $_POST['drdSpecialitySubjectID'];
        }

        if (isset($_POST['txtJoiningDate']))
        {
            $Clean['JoiningDate'] = strip_tags(trim($_POST['txtJoiningDate']));
        }
        
        if (isset($_FILES['fleEventImage']) && is_array($_FILES['fleEventImage']))
        {
            $Clean['UploadFile'] = $_FILES['fleEventImage'];
        }

        $NewRecordValidator = new Validator();

        $NewRecordValidator->ValidateStrings($Clean['FirstName'], 'First Name is required and should be between 2 and 20 characters.', 2, 20);
        $NewRecordValidator->ValidateStrings($Clean['LastName'], 'Last Name is required and should be between 2 and 30 characters.', 2, 30);

        $NewRecordValidator->ValidateStrings($Clean['Gender'], 'Gender is required and should be between 2 and 20 characters.', 2, 20);
        $NewRecordValidator->ValidateStrings($Clean['Address1'], 'Address1 is required and should be between 2 and 150 characters.', 2, 150);

        if ($Clean['Address2'] != '')
        {
            $NewRecordValidator->ValidateStrings($Clean['Address2'], 'Address 2 is required and should be between 2 and 150 characters.', 2, 150);
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
        
        $NewRecordValidator->ValidateStrings($Clean['PinCode'], 'Pin Code is required and should be between 6 and 6 characters.', 6, 6);

        if ($Clean['PhoneNumber'] != '')
        {
            $NewRecordValidator->ValidateStrings($Clean['PhoneNumber'], 'Phone Number is required and should be between 7 and 15 characters.', 7, 15);    
        }
        
        $NewRecordValidator->ValidateStrings($Clean['MobileNumber1'], 'Mobile Number1 is required and should be between 10 and 15 characters.', 10, 15);

        if ($Clean['MobileNumber2'] != '')
        {
            $NewRecordValidator->ValidateStrings($Clean['MobileNumber2'], 'Mobile Number1 is required and should be between 10 and 15 characters.', 10, 15);
        }

        if ($Clean['Email'] != '') 
        {
            $NewRecordValidator->ValidateEmail($Clean['Email'], 'Email is required and should be between 3 and 150 characters.', 3, 150);
        }

        if ($Clean['AadharNumber'] != '') 
        {
            $NewRecordValidator->ValidateStrings($Clean['AadharNumber'], 'Aadhar Number is required and should be between 10 and 12 characters.', 10, 12);
        }

        $NewRecordValidator->ValidateDate($Clean['DOB'], 'Please enter a valid date of Birth.');
        $NewRecordValidator->ValidateInSelect($Clean['StaffCategory'], $StaffCategoryList, 'Unknown Error, Please try again.');

        // if ($NewRecordValidator->ValidateStrings($Clean['HighestQualification']) != '') 
        // {
        //     $NewRecordValidator->ValidateStrings($Clean['HighestQualification'], 'Highest Qualification is required and should be between 2 and 20 characters.', 2, 20);
        // }
        
        if ($Clean['HighestQualification'] != '') 
        {
            $NewRecordValidator->ValidateStrings($Clean['HighestQualification'], 'Highest qualification is required and should be between 2 and 20 characters.', 2, 20);
        }
        
        if ($Clean['SpecialitySubjectID'] > 0)
        {
            $NewRecordValidator->ValidateInSelect($Clean['SpecialitySubjectID'], $AllSubjectMastersList, 'Unknown Error, Please try again.');
        }
        
        $NewRecordValidator->ValidateDate($Clean['JoiningDate'], 'Please enter a valid joining date.');
        
   
        $FileName = '';
        $FileExtension = '';
        
    if ($Clean['UploadFile']['error'] != 4) 
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

        $FileName = $Clean['UploadFile']['name'];
        
         if (file_exists(SITE_FS_PATH . '/site_images/branch_staff_images/' .$BranchStaffToEdit->GetBranchStaffID() .'/' . $BranchStaffToEdit->GetStaffPhoto())) 
            {
                unlink(SITE_FS_PATH . '/site_images/branch_staff_images/' .$BranchStaffToEdit->GetBranchStaffID() .'/' . $BranchStaffToEdit->GetStaffPhoto());
            }
     }    

        if ($NewRecordValidator->HasNotifications())
        {
            $HasErrors = true;
            break;
        }
                
        $BranchStaffToEdit->SetFirstName($Clean['FirstName']);
        $BranchStaffToEdit->SetLastName($Clean['LastName']);
        $BranchStaffToEdit->SetGender($Clean['Gender']);

        $BranchStaffToEdit->SetAddress1($Clean['Address1']);
        $BranchStaffToEdit->SetAddress2($Clean['Address2']);

        $BranchStaffToEdit->SetCityID($Clean['CityID']);
        $BranchStaffToEdit->SetDistrictID($Clean['DistrictID']);
        $BranchStaffToEdit->SetStateID($Clean['StateID']);
        $BranchStaffToEdit->SetCountryID($Clean['CountryID']);
        $BranchStaffToEdit->SetPinCode($Clean['PinCode']);

        $BranchStaffToEdit->SetPhoneNumber($Clean['PhoneNumber']);
        $BranchStaffToEdit->SetMobileNumber1($Clean['MobileNumber1']);
        $BranchStaffToEdit->SetMobileNumber2($Clean['MobileNumber2']);
        $BranchStaffToEdit->SetEmail($Clean['Email']);

        $BranchStaffToEdit->SetAadharNumber($Clean['AadharNumber']);
        $BranchStaffToEdit->SetDOB(date('Y-m-d', strtotime(DateProcessing::ToggleDateDayAndMonth(($Clean['DOB'])))));

        $BranchStaffToEdit->SetStaffCategory($Clean['StaffCategory']);
        $BranchStaffToEdit->SetHighestQualification($Clean['HighestQualification']);
        $BranchStaffToEdit->SetSpecialitySubjectID($Clean['SpecialitySubjectID']);

        $BranchStaffToEdit->SetJoiningDate(date('Y-m-d', strtotime(DateProcessing::ToggleDateDayAndMonth(($Clean['JoiningDate'])))));
        $BranchStaffToEdit->SetIsActive(1);
        
        // Generate a Unique Name for the uploaded document
        if($FileName != '') 
        {
        $FileName = md5(uniqid(rand(), true) . $BranchStaffToEdit->GetBranchStaffID()) . '.' . $FileExtension;
        $BranchStaffToEdit->SetStaffPhoto($FileName);
        }
        
        $BranchStaffToEdit->SetCreateUserID($LoggedUser->GetUserID());

        if ($Clean['AadharNumber'] != '') 
        {
            if ($BranchStaffToEdit->AadharExist())
            {
                $NewRecordValidator->AttachTextError('The aadhar of branch staff you have added already exists.');
                $HasErrors = true;
                break;
            }
        }

        if (!$BranchStaffToEdit->Save())
        {
            $NewRecordValidator->AttachTextError(ProcessErrors($BranchStaffToEdit->GetLastErrorCode()));
            $HasErrors = true;

            break;
        }
        
        if ($FileName != '') 
        {   
            // var_dump($FileName);exit;
            $UniqueUserFileUploadDirectory = SITE_FS_PATH . '/site_images/branch_staff_images/'.$BranchStaffToEdit ->GetBranchStaffID().'/';

            if (!is_dir($UniqueUserFileUploadDirectory))
            {
                mkdir($UniqueUserFileUploadDirectory);
            }
            
           // now move the uploaded file to application document folder
            if (move_uploaded_file($Clean['UploadFile']['tmp_name'], $UniqueUserFileUploadDirectory . $FileName)) 
            {
                 if (!$BranchStaffToEdit->Save())
                {            
                    $NewRecordValidator->AttachTextError(ProcessErrors($BranchStaffToEdit->GetLastErrorCode()));
                    $HasErrors = true;

                    break;
                }
            }
        }
        
        header('location:branch_staff_list.php?Mode=UD');
        exit;
        break;

    case 2:
        $Clean['FirstName'] = $BranchStaffToEdit->GetFirstName();
        $Clean['LastName'] = $BranchStaffToEdit->GetLastName();
        $Clean['Gender'] = $BranchStaffToEdit->GetGender();

        $Clean['Address1'] = $BranchStaffToEdit->GetAddress1();
        $Clean['Address2'] = $BranchStaffToEdit->GetAddress2();

        $Clean['CityID'] = $BranchStaffToEdit->GetCityID();
        $Clean['DistrictID'] = $BranchStaffToEdit->GetDistrictID();
        $Clean['StateID'] = $BranchStaffToEdit->GetStateID();
        $Clean['CountryID'] = $BranchStaffToEdit->GetCountryID();
        
        $StateList = State::GetAllStates($Clean['CountryID']);
        $DistrictList = City::GetAllDistricts($Clean['StateID']);
        $CityList = City::GetAllCities($Clean['StateID'], $Clean['DistrictID']);
        
        $Clean['PinCode'] = $BranchStaffToEdit->GetPinCode();

        $Clean['PhoneNumber'] = $BranchStaffToEdit->GetPhoneNumber();
        $Clean['MobileNumber1'] = $BranchStaffToEdit->GetMobileNumber1();
        $Clean['MobileNumber2'] = $BranchStaffToEdit->GetMobileNumber2();

        $Clean['Email'] = $BranchStaffToEdit->GetEmail();
        $Clean['AadharNumber'] = $BranchStaffToEdit->GetAadharNumber();
        $Clean['DOB'] = $BranchStaffToEdit->GetDOB();

        $Clean['StaffCategory'] = $BranchStaffToEdit->GetStaffCategory();

        $Clean['HighestQualification'] = $BranchStaffToEdit->GetHighestQualification();
        $Clean['SpecialitySubjectID'] = $BranchStaffToEdit->GetSpecialitySubjectID();
        $Clean['JoiningDate'] = $BranchStaffToEdit->GetJoiningDate();
        $Clean['IsActive'] = $BranchStaffToEdit->GetIsActive();
        break;
}

require_once('../html_header.php');
?>
<title>Edit Branch Staff</title>
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
                    <h1 class="page-header">Edit Branch Staff</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="EditBranchStaff" action="edit_branch_staff.php" method="post" enctype="multipart/form-data">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        Enter Branch Staff Details
                    </div>
                    <div class="panel-body">
<?php
                        if ($HasErrors == true)
                        {
                            echo $NewRecordValidator->DisplayErrors();
                        }
?>                    
                       <div class="form-group">
                            <label for="FirstName" class="col-lg-2 control-label">First Name</label>
                            <div class="col-lg-4">
                                <input class="form-control" type="text" maxlength="20" id="FirstName" name="txtFirstName" value="<?php echo $Clean['FirstName']; ?>" />
                            </div>
                            <label for="LastName" class="col-lg-2 control-label">Last Name</label>
                            <div class="col-lg-4">
                                <input class="form-control" type="LastName" maxlength="30" id="LastName" name="txtLastName" value="<?php echo $Clean['LastName']; ?>"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="Gender" class="col-lg-2 control-label">Gender</label>
                            <div class="col-sm-4">
                                <label style="font-weight: normal;">
                                    <input class="custom-radio" type="radio" id="Male" name="rdbGender" value="Male" <?php echo ($Clean['Gender'] == 'Male' ? 'checked="checked"' : ''); ?> >&nbsp;&nbsp;Male
                                </label>
                                <label style="font-weight: normal;">
                                    <input class="custom-radio" type="radio" id="Female" name="rdbGender" value="Female" <?php echo ($Clean['Gender'] == 'Female' ? 'checked="checked"' : ''); ?> >&nbsp;&nbsp;Female
                                </label>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="Address1" class="col-lg-2 control-label">Resident Address</label>
                            <div class="col-lg-4">
                                <textarea class="form-control" id="Address1" name="txtAddress1"><?php echo $Clean['Address1']; ?></textarea>
                            </div>
                            <label for="Address2" class="col-lg-2 control-label">Permanent Address</label>
                            <div class="col-lg-4">
                                <textarea class="form-control" id="Address2" name="txtAddress2"><?php echo $Clean['Address2']; ?></textarea>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="CountryID" class="col-lg-2 control-label">Country</label>
                            <div class="col-lg-4">
                                <select class="form-control" name="drdCountryID" id="Country">
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
                            <label for="StateID" class="col-lg-2 control-label">State</label>
                            <div class="col-lg-4">
                                <select class="form-control" name="drdStateID" id="State">
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
                            <label for="DistrictID" class="col-lg-2 control-label">District</label>
                            <div class="col-lg-4">
                                <select class="form-control" name="drdDistrictID" id="District">
<?php
                                    if (is_array($DistrictList) && count($DistrictList) > 0)
                                    {
                                        foreach ($DistrictList as $DistrictID => $DistrictName) 
                                        {
                                            echo '<option ' . ($Clean['DistrictID'] == $DistrictID ? 'selected="selected"' : '') . ' value="' . $DistrictID . '">' . $DistrictName . '</option>';
                                        }
                                    }
?>
                                </select>
                            </div>
                            <label for="CityID" class="col-lg-2 control-label">City</label>
                            <div class="col-lg-4">
                                <select class="form-control" name="drdCityID" id="City">
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
                            <label for="PinCode" class="col-lg-2 control-label">PIN Code</label>
                            <div class="col-lg-4">
                                <input class="form-control" type="PinCode" maxlength="6" id="PinCode" name="txtPinCode" value="<?php echo $Clean['PinCode']; ?>"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="PhoneNumber" class="col-lg-2 control-label">Phone Number</label>
                            <div class="col-lg-4">
                                <input class="form-control" type="text" maxlength="15" id="PhoneNumber" name="txtPhoneNumber" value="<?php echo $Clean['PhoneNumber']; ?>" />
                            </div>
                            <label for="MobileNumber1" class="col-lg-2 control-label">MobileNumber 1</label>
                            <div class="col-lg-4">
                                <input class="form-control" type="MobileNumber1" maxlength="15" id="PhoneNumber" name="txtMobileNumber1" value="<?php echo $Clean['MobileNumber1']; ?>"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="MobileNumber2" class="col-lg-2 control-label">Mobile Number 2</label>
                            <div class="col-lg-4">
                                <input class="form-control" type="text" maxlength="15" id="MobileNumber2" name="txtMobileNumber2" value="<?php echo $Clean['MobileNumber2']; ?>" />
                            </div>
                            <label for="Email" class="col-lg-2 control-label">Email</label>
                            <div class="col-lg-4">
                                <input class="form-control" type="Email" maxlength="150" id="Email" name="txtEmail" value="<?php echo $Clean['Email']; ?>"/>
                            </div>
                        </div>
                         <div class="form-group">
                            <label for="AadharNumber" class="col-lg-2 control-label">Aadhar Number</label>
                            <div class="col-lg-4">
                                <input class="form-control" type="text" maxlength="12" id="AadharNumber" name="txtAadharNumber" value="<?php echo ($Clean['AadharNumber'] ? $Clean['AadharNumber'] : ''); ?>" />
                            </div>
                            <label for="DOB" class="col-lg-2 control-label">Date Of Birth</label>
                            <div class="col-lg-4">
                                <input class="form-control dtepicker" type="text" maxlength="10" id="DOB" name="txtDOB" value="<?php echo date('d/m/Y', strtotime($Clean['DOB'])); ?>"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="StaffCategory" class="col-lg-2 control-label">Staff Category</label>
                            <div class="col-lg-4">
                                <select class="form-control" id="StaffCategory" name="drdStaffCategory" value="<?php echo $Clean['StaffCategory']; ?>">
<?php
                                foreach ($StaffCategoryList as $StaffCategoryID => $StaffCategory) 
                                {
                                    echo '<option ' . ($Clean['StaffCategory'] == $StaffCategoryID ? 'selected="selected"' : '') . ' value="' . $StaffCategoryID . '">' . $StaffCategory . '</option>';
                                }
?>
                                </select>
                            </div>
                            <label for="HighestQualification" class="col-lg-2 control-label">Highest Qualification</label>
                            <div class="col-lg-4">
                                <input class="form-control" type="text" maxlength="20" id="HighestQualification" name="txtHighestQualification" value="<?php echo $Clean['HighestQualification']; ?>"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="SpecialitySubjectID" class="col-lg-2 control-label">SpecialitySubjectID</label>
                            <div class="col-lg-4">
                               <select class="form-control" name="drdSpecialitySubjectID" id="SpecialitySubjectID">
<?php
                                if (is_array($AllSubjectMastersList) && count($AllSubjectMastersList) > 0)
                                {
                                    foreach ($AllSubjectMastersList as $SubjectID => $SubjectName) 
                                    {
?>
                                          <option value="<?php echo $SubjectID;?>" <?php echo(($Clean['SpecialitySubjectID'] == $SubjectID) ? 'selected="selected"' : '' ); ?> ><?php echo $SubjectName;?></option>     
<?php
                                    }
                                }
?>
                                </select>
                            </div>
                            <label for="JoiningDate" class="col-lg-2 control-label">Joining Date</label>
                            <div class="col-lg-4">
                                <input class="form-control dtepicker" type="text" maxlength="10" id="JoiningDate" name="txtJoiningDate" value="<?php echo date('d/m/Y', strtotime($Clean['JoiningDate']));?>"/>
                            </div>
                        </div>
                        <!-- <div class="form-group">-->
                        <!--     <label for="Photo" class="col-lg-2 control-label">Image</label>-->
                        <!--    <div class="col-lg-6">-->
                        <!--        <img class="img-responsive" src="<?php echo '../../site_images/branch_staff_images/' .$BranchStaffToEdit->GetBranchStaffID() .'/' . $BranchStaffToEdit->GetStaffPhoto();?>" width="115px" height="70px"  style ="border:1px solid black; border-radius:2px; margin-left: 5px;">-->
                        <!--    </div>-->
                        <!--</div>-->
                         <div class="form-group">
                            <label for="Upload" class="col-lg-2 control-label">Upload Image</label>
                            <div class="col-lg-4">
                                <input class="form-control" type="file" name="fleEventImage" onchange="readURL(this);"/>
                          </div>
                           <label for="Photo" class="col-lg-2 control-label">Image</label>
                            <div class="col-lg-4">
                                <img class="img-responsive" src="<?php echo '../../site_images/branch_staff_images/' .$BranchStaffToEdit->GetBranchStaffID() .'/' . $BranchStaffToEdit->GetStaffPhoto();?>" width="115px" height="70px"  style ="border:1px solid black; border-radius:2px; margin-left: 5px;">
                            </div>
                            <div class="col-lg-6 EventImage" style="display: none;">
                            </div>
                        </div>
                         <div class="form-group">
                            <div class="col-sm-offset-2 col-lg-10">
                                <input type="hidden" name="hdnProcess" value="3"/>
                                <input type="hidden" name="hdnBranchStaffID" value="<?php echo $Clean['BranchStaffID'];?>" />
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
<script src="/admin/vendor/jquery-ui/jquery-ui.min.js"></script>
    <script type="text/javascript">
    $(document).ready(function() 
    {
        $(".dtepicker").datepicker({
                changeMonth: true,
                changeYear: true,
                dateFormat: 'dd/mm/yy'
        });

        $('[name="SameAddress"]').change(function()
        {
            if ($(this).is(':checked'))
             {
               $("#Address2").text($("#Address1").val())
               return false;
            };
             $("#Address2").empty();
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
 });
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
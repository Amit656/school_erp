<?php
require_once("../../classes/class.users.php");
require_once('../../classes/class.validation.php');
require_once("../../classes/class.authentication.php");

require_once("../../classes/school_administration/class.academic_years.php");
require_once("../../classes/school_administration/class.classes.php");
require_once("../../classes/school_administration/class.countries.php");
require_once("../../classes/school_administration/class.states.php");
require_once("../../classes/school_administration/class.cities.php");
require_once("../../classes/admission_cell/class.student_registrations.php");

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

if ($LoggedUser->HasPermissionForTask(TASK_LIST_STUDENT_REGISTRATION) !== true)
{
    header('location:/admin/unauthorized_login_admin.php');
    exit;
}

$GenderList = array('Male' => 'Male', 'Female' => 'Female', 'Others' => 'Others');
$BloodGroupList = array('A+' => 'A+', 'A-' => 'A-', 'B+' => 'B+', 'AB+' => 'AB+', 'AB-' => 'AB-', 'O+' => 'O+', 'O-' => 'O-');
$CategoryList = array('General' => 'General', 'OBC' => 'OBC', 'SC' => 'SC', 'ST' => 'ST');
$ActiveStatusList = array(1 => 'Yes', 0 => 'No');

$AcademicYears =  array();
$AcademicYears = AcademicYear::GetAllAcademicYears();

$ClassList =  array();
$ClassList = AddedClass::GetActiveClasses();

$CountryList =  array();
$CountryList = Country::GetAllCountries();

$AllAdmittedStudents = array();

$DistrictList =  array();
$StateList =  array();
$CityList =  array();

$HasErrors = false;

$Clean = array();
$Clean['Process'] = 0;

$Clean['AcademicYearID'] = 0;

$Clean['StudentRegistrationID'] = 0;
$Clean['ClassID'] = array();

$Clean['EnrollmentID'] = '';
$Clean['StudentName'] = '';
$Clean['DOB'] = '';
$Clean['Gender'] = array();

$Clean['MobileNumber'] = '';

$Clean['BloodGroup'] = array();
$Clean['Category'] = array();

$Clean['FatherName'] = '';
$Clean['MotherName'] = '';

$Clean['CountryID'] = 0;
$Clean['StateID'] = 0;
$Clean['DistrictID'] = 0;
$Clean['CityID'] = 0;

$Clean['IsActive'] = 1;

// paging and sorting variables start here  //
$Clean['CurrentPage'] = 1;
$TotalPages = 0;

$Start = 0;
$Limit = 30;
// end of paging variables //

if (isset($_GET['hdnProcess']))
{
    $Clean['Process'] = (int) $_GET['hdnProcess'];
}
elseif (isset($_GET['Process']))
{
	$Clean['Process'] = (int) $_GET['Process'];
}
switch ($Clean['Process'])
{
	case 5:
		if ($LoggedUser->HasPermissionForTask(TASK_DELETE_STUDENT_REGISTRATION) !== true)
		{
			header('location:/admin/unauthorized_login_admin.php');
			exit;
		}
		
		if (isset($_GET['StudentRegistrationID']))
		{
			$Clean['StudentRegistrationID'] = (int) $_GET['StudentRegistrationID'];			
		}
		
		if ($Clean['StudentRegistrationID'] <= 0)
		{
			header('location:../error.php');
			exit;
		}						
			
		try
		{
			$StudentRegistrationToDelete = new StudentRegistration($Clean['StudentRegistrationID']);
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
		
		$RecordValidator = new Validator();
		
		// if ($StudentRegistrationToDelete->CheckDependencies())
		// {
		// 	$RecordValidator->AttachTextError('This record cannot be deleted. There are dependent records for this record.');
		// 	$HasErrors = true;
		// 	break;
		// }
				
		if (!$StudentRegistrationToDelete->Remove())
		{
			$RecordValidator->AttachTextError(ProcessErrors($StudentRegistrationToDelete->GetLastErrorCode()));
			$HasErrors = true;
			break;
		}
		
		header('location:new_admission_list.php?Mode=DD');
	    break;

    case 7:
        if (isset($_GET['drdAcademicYearID']))
        {
            $Clean['AcademicYearID'] = $_GET['drdAcademicYearID'];
        }
        
        if (isset($_GET['drdClass']) && is_array($_GET['drdClass']))
        {
            $Clean['ClassID'] = $_GET['drdClass'];
        }

        if (isset($_GET['txtEnrollmentID']))
        {
            $Clean['EnrollmentID'] = strip_tags(trim($_GET['txtEnrollmentID']));
        }
        
        if (isset($_GET['txtStudentName']))
        {
            $Clean['StudentName'] = strip_tags(trim($_GET['txtStudentName']));
        }

        if (isset($_GET['drdGender']) && $_GET['drdGender'])
        {
            $Clean['Gender'] = $_GET['drdGender'];
        }

        if (isset($_GET['txtMobileNumber']))
        {
            $Clean['MobileNumber'] = strip_tags(trim($_GET['txtMobileNumber']));
        }

        if (isset($_GET['drdBloodGroup']) && is_array($_GET['drdBloodGroup']))
        {
            $Clean['BloodGroup'] = $_GET['drdBloodGroup'];
        }

        if (isset($_GET['drdCategory']) && is_array($_GET['drdCategory']))
        {
            $Clean['Category'] = $_GET['drdCategory'];
        }

        if (isset($_GET['txtFatherName']))
        {
            $Clean['FatherName'] = strip_tags(trim($_GET['txtFatherName']));
        }

        if (isset($_GET['txtMotherName']))
        {
            $Clean['MotherName'] = strip_tags(trim($_GET['txtMotherName']));
        }

        if (isset($_GET['drdCountry']))
        {
            $Clean['CountryID'] = (int) $_GET['drdCountry'];
        }

        if (isset($_GET['drdState']))
        {
            $Clean['StateID'] = (int) $_GET['drdState'];
        }
        
        if (isset($_GET['drdDistrict']))
        {
            $Clean['DistrictID'] = (int) $_GET['drdDistrict'];
        }
        
        if (isset($_GET['drdCity']))
        {
            $Clean['CityID'] = (int) $_GET['drdCity'];
        }

        if (isset($_GET['rdbIsActive']))
        {
            $Clean['IsActive'] = (int) $_GET['rdbIsActive'];
        }
        else if (isset($_GET['IsActive']))
        {
            $Clean['IsActive'] = (int) $_GET['IsActive'];
        }

        $RecordValidator = new Validator();
        
        if (count($Clean['ClassID']) > 0) 
        {   
            foreach ($Clean['ClassID'] as $ClassID) 
            {
                $RecordValidator->ValidateInSelect($ClassID, $ClassList, 'Unknown error, please try again.');
            }
        }

        if ($Clean['AcademicYearID'] > 0) 
        {
            $RecordValidator->ValidateInSelect($Clean['AcademicYearID'], $AcademicYears, 'Unknown error, please try again.');
        }
        
        if ($Clean['EnrollmentID'] != '') 
        {
            $RecordValidator->ValidateStrings($Clean['EnrollmentID'], 'Sr No is required and should be between 1 and 30 characters.', 1, 30);    
        }
        
        if ($Clean['StudentName'] != '') 
        {
            $RecordValidator->ValidateStrings($Clean['StudentName'], 'Student name is required and should be between 1 and 20 characters.', 1, 20);    
        }
        
        if (count($Clean['Gender']) > 0)  
        {
            foreach ($Clean['Gender'] as $Gender) 
            {
                $RecordValidator->ValidateInSelect($Gender, $GenderList, 'Unknown error, please try again.');
            }
        }

        if ($Clean['MobileNumber'] != '') 
        {
            $RecordValidator->ValidateStrings($Clean['MobileNumber'], 'Mobile number is required and should be between 10 and 15 characters.', 10, 15);
        }

        if (count($Clean['BloodGroup']) > 0) 
        {   
            foreach ($Clean['BloodGroup'] as $BloodGroup) 
            {
                $RecordValidator->ValidateInSelect($BloodGroup, $BloodGroupList, 'Unknown error, please try again.');
            }
        }

        if (count($Clean['Category']) > 0) 
        {   
            foreach ($Clean['Category'] as $Category) 
            {
                $RecordValidator->ValidateInSelect($Category, $CategoryList, 'Unknown error, please try again.');
            }
        }

        if ($Clean['FatherName'] != '')     
        {
            $RecordValidator->ValidateStrings($Clean['FatherName'], 'Father name is required and should be between 1 and 30 characters.', 1, 30);
        }

        if ($Clean['MotherName'] != '') 
        {
            $RecordValidator->ValidateStrings($Clean['MotherName'], 'Mother Name is required and should be between 1 and 30 characters.', 1, 30);
        }        

        if ($Clean['CountryID'] > 0) 
        {
            if ($RecordValidator->ValidateInSelect($Clean['CountryID'], $CountryList, 'Please select valid country.'))
            {
                $StateList = State::GetAllStates($Clean['CountryID']);
                
                if ($Clean['StateID'] > 0)
                {
                    if ($RecordValidator->ValidateInSelect($Clean['StateID'], $StateList, 'Please select valid state.'))
                    {
                        $DistrictList = City::GetAllDistricts($Clean['StateID']);
                        
                        if ($Clean['DistrictID'] > 0)
                        {
                            if ($RecordValidator->ValidateInSelect($Clean['DistrictID'], $DistrictList, 'Please select valid district.'))
                            {
                                $CityList = City::GetAllCities($Clean['StateID'], $Clean['DistrictID']);
                                
                                if ($Clean['CityID'] > 0)
                                {
                                    $RecordValidator->ValidateInSelect($Clean['CityID'], $CityList, 'Please select valid city.');      
                                }
                            }   
                        }
                    }   
                }
            }
        }

        $RecordValidator->ValidateInSelect($Clean['IsActive'], $ActiveStatusList, 'Unknown error, please try again.');

        if ($RecordValidator->HasNotifications())
        {
            $HasErrors = true;
            break;
        }   

        //set record filters
        $Filters['AcademicYearID'] = $Clean['AcademicYearID'];
        $Filters['EnrollmentID'] = $Clean['EnrollmentID'];
        $Filters['ClassID'] = $Clean['ClassID'];
        $Filters['StudentName'] = $Clean['StudentName'];
        $Filters['Genders'] = $Clean['Gender'];

        $Filters['MobileNumber'] = $Clean['MobileNumber'];
        $Filters['BloodGroups'] = $Clean['BloodGroup'];
        $Filters['Categories'] = $Clean['Category'];

        $Filters['FatherName'] = $Clean['FatherName'];
        $Filters['MotherName'] = $Clean['MotherName'];

        $Filters['CountryID'] = $Clean['CountryID'];
        $Filters['StateID'] = $Clean['StateID'];
        $Filters['DistrictID'] = $Clean['DistrictID'];
        $Filters['CityID'] = $Clean['CityID'];

        $Filters['ActiveStatus'] = $Clean['IsActive'];

        //get records count
        StudentRegistration::SearchAdmittedStudents($TotalRecords, true, $Filters);

        if ($TotalRecords > 0)
        {
            // Paging and sorting calculations start here.
            $TotalPages = (($TotalRecords % $Limit) == 0) ? $TotalRecords / $Limit : floor($TotalRecords / $Limit) + 1;

            if (isset($_GET['CurrentPage']))
            {
                $Clean['CurrentPage'] = (int) $_GET['CurrentPage'];
            }

            if ($Clean['CurrentPage'] <= 0)
            {
                $Clean['CurrentPage'] = 1;
            }
            elseif ($Clean['CurrentPage'] > $TotalPages)
            {
                $Clean['CurrentPage'] = $TotalPages;
            }

            if ($Clean['CurrentPage'] > 1)
            {
                $Start = ($Clean['CurrentPage'] - 1) * $Limit;
            }
            
            // end of Paging and sorting calculations.
            // now get the actual  records
            $AllAdmittedStudents = StudentRegistration::SearchAdmittedStudents($TotalRecords, false, $Filters, $Start, $Limit);
        }

        break;
}

require_once('../html_header.php');
?>
<title>New Admission List</title>
<!-- DataTables CSS -->
<link href="/admin/vendor/datatables-plugins/dataTables.bootstrap.css" rel="stylesheet">

<!-- DataTables Responsive CSS -->
<link href="/admin/vendor/datatables-responsive/dataTables.responsive.css" rel="stylesheet">
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
                    <h1 class="page-header">New Admission List</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
            <div class="row">
                <div class="col-lg-12">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <strong>Filter New Admission List</strong>
                        </div>
<?php
                        if ($HasErrors == true)
                        {
                            echo $RecordValidator->DisplayErrorsInTable();
                        }
?>
                         <div class="panel-body">
                            <div>
                                <div class="row" >
                                    <div class="col-lg-12">
                                         <form class="form-horizontal" name="FilterStudentRegistration" id="FilterStudentRegistration" action="new_admission_list.php" method="get">
                                            <div class="form-group">
                                                <label for="AcademicYearID" class="col-lg-2 control-label">Academic Session</label>
                                                <div class="col-lg-4">
                                                	<select class="form-control" name="drdAcademicYearID" id="AcademicYearID">
                                                	    <option value="0">All Academic Session</option>
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
                                                <label for="EnrollmentID" class="col-lg-2 control-label">Sr No</label>
                                                <div class="col-lg-4">
                                                    <input class="form-control" type="text" maxlength="30" id="EnrollmentID" name="txtEnrollmentID" value="<?php echo $Clean['EnrollmentID']; ?>" />
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label for="ClassID" class="col-lg-2 control-label">Class</label>
                                                <div class="col-lg-4">
                                                    <select class="form-control" name="drdClass[]" id="ClassID" multiple="multiple">
<?php
                                                    if (is_array($ClassList) && count($ClassList) > 0)
                                                    {
                                                        foreach ($ClassList as $ClassID => $ClassName)
                                                        {
?>
                                                            <option value="<?php echo $ClassID; ?>" <?php echo(in_array($ClassID, $Clean['ClassID']) ? 'selected="selected"' : ''); ?> ><?php echo $ClassName; ?> </option>
<?php
                                                        }
                                                    }
?>
                                                    </select>
                                                </div>
                                                <label for="StudentName" class="col-lg-2 control-label">Student Name</label>
                                                <div class="col-lg-4">
                                                    <input class="form-control" type="text" maxlength="20" id="StudentName" name="txtStudentName" value="<?php echo $Clean['StudentName']; ?>" />
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label for="Gender" class="col-lg-2 control-label">Gender</label>
                                                <div class="col-sm-4">
                                                    <select class="form-control" name="drdGender[]" id="Gender" multiple="multiple">
<?php
                                                    foreach ($GenderList as $Gender => $GenderName)
                                                    {
?>
                                                        <option value="<?php echo $Gender; ?>" <?php echo(in_array($Gender, $Clean['Gender']) ? 'selected="selected"' : ''); ?> ><?php echo $GenderName; ?> </option>
<?php
                                                    }
?>
                                                    </select>                                            
                                                </div>
                                                <label for="MobileNumber" class="col-lg-2 control-label">Mobile Number</label>
                                                <div class="col-lg-4">
                                                    <input class="form-control" type="text" maxlength="15" id="MobileNumber" name="txtMobileNumber" value="<?php echo $Clean['MobileNumber']; ?>" />
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label for="BloodGroup" class="col-lg-2 control-label">BloodGroup</label>
                                                <div class="col-sm-4">
                                                    <select class="form-control" name="drdBloodGroup[]" id="BloodGroup" multiple="multiple">
<?php
                                                    foreach ($BloodGroupList as $BloodGroup => $BloodGroupName)
                                                    {
?>
                                                        <option value="<?php echo $BloodGroup; ?>" <?php echo(in_array($BloodGroup, $Clean['BloodGroup']) ? 'selected="selected"' : ''); ?> ><?php echo $BloodGroupName; ?> </option>
<?php
                                                    }
?>
                                                    </select>                                            
                                                </div>
                                                <label for="Category" class="col-lg-2 control-label">Category</label>
                                                <div class="col-sm-4">
                                                    <select class="form-control" name="drdCategory[]" id="Category" multiple="multiple">
<?php
                                                    foreach ($CategoryList as $Category => $CategoryName)
                                                    {
?>
                                                        <option value="<?php echo $Category; ?>" <?php echo(in_array($Category, $Clean['Category']) ? 'selected="selected"' : ''); ?> ><?php echo $CategoryName; ?> </option>
<?php
                                                    }
?>
                                                    </select>                                            
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
                                                <label for="Country" class="col-lg-2 control-label">Country</label>
                                                <div class="col-lg-4">
                                                    <select class="form-control" name="drdCountry" id="Country">
                                                        <option value="0">Select Country</option>
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
                                                        <option value="0">Select State</option>
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
                                                        <option value="0">Select District</option>
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
                                                        <option value="0">Select City</option>
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
                                                <label for="IsActive" class="col-lg-2 control-label">Is Active</label>
                                                <div class="col-lg-4">
<?php
                                                foreach ($ActiveStatusList as $ActiveStatus => $ActiveStatusName) 
                                                {
?>
                                                    <label style="font-weight: normal;">
                                                        <input type="radio" id="IsActive" name="rdbIsActive" value="<?php echo $ActiveStatus; ?>" <?php echo($Clean['IsActive'] == $ActiveStatus ? 'checked="checked"' : '');?> />&nbsp;<?php echo $ActiveStatusName;?>&nbsp;
                                                    </label>
<?php
                                                }
?>
                                                </div>    
                                            </div>
                                            <div class="form-group">
                                                <div class="col-sm-offset-2 col-lg-10">
                                                    <input type="hidden" name="hdnProcess" value="7"/>  
                                                    <button type="submit" class="btn btn-primary">Search</button>
                                                </div>
                                            </div>
                                        </form>
                                     </div>
                                </div>
                            </div>
                         </div>
                     </div>
                </div>
            </div>
            <!-- /.row -->
<?php
        if ($Clean['Process'] == 7)
        {
?>
            <div class="row">
                <div class="col-lg-12">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <strong>Total Records Returned: <?php echo count($AllAdmittedStudents); ?></strong>
                        </div>
                        <!-- /.panel-heading -->
                        <div class="panel-body">
                            <div>
                                <div class="row">
                                    <div class="col-lg-6">
                                    <div class="add-new-btn-container"><a href="add_new_admission.php" class="btn btn-primary<?php echo $LoggedUser->HasPermissionForTask(TASK_ADD_STUDENT_REGISTRATION) === true ? '' : ' disabled'; ?>" role="button">Add New Admission</a></div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="print-btn-container"><button id="PrintButton" type="submit" class="btn btn-primary">Print</button></div>
                                    </div>
                                </div>
<?php
                            if ($HasErrors == true)
                            {
                                echo $RecordValidator->DisplayErrorsInTable();
                            }
?>
                                <div class="row" id="RecordTableHeading">
                                    <div class="col-lg-12">
                                        <div class="report-heading-container"><strong>New Admissions on <?php echo date('d-m-Y h:i A'); ?></strong></div>
                                    </div>
                                </div>
                                <div class="row" id="RecordTable">
                                    <div class="col-lg-12">
                                        <table width="100%" class="table table-striped table-bordered table-hover" id="DataTableRecords">
                                            <thead>
                                                <tr>
                                                    <th>S. No</th>
                                                    <th>Sr. No</th>
                                                    <th>Student Name</th>
                                                    <th>Class</th>
                                                    <th>Father Name</th>
                                                    <th>Mother Name</th>
                                                    <th>Gender</th>
                                                    <th>Category</th>
                                                    <th>Admission Date</th>
                                                    <th>Registration Fee</th>
                                                    <th>Mobile Number</th>
                                                    <th>Create User</th>
                                                    <th>Create Date</th>
                                                    <th class="print-hidden">Operations</th>
                                                </tr>
                                            </thead>
                                            <tbody>
<?php
                                    if (is_array($AllAdmittedStudents) && count($AllAdmittedStudents) > 0)
                                    {
                                        $Counter = 0;
                                        foreach ($AllAdmittedStudents as $StudentID => $Details)
                                        {
?>
                                            <tr>
                                                <td><?php echo ++$Counter; ?></td>
                                                <td><?php echo $Details['EnrollmentID']; ?></td>
                                                <td><?php echo $Details['StudentName']; ?></td>
                                                <td><?php echo $Details['ClassName']; ?></td>
                                                <td><?php echo $Details['FatherName']; ?></td>
                                                <td><?php echo $Details['MotherName']; ?></td>
                                                <td><?php echo $Details['Gender']; ?></td>
                                                <td><?php echo $Details['Category']; ?></td>
                                                <td><?php echo ($Details['AdmissionDate'] != '0000-00-00') ? date('d/m/Y', strtotime($Details['AdmissionDate'])) : ''; ?></td>
                                                <td><?php echo (($Details['RegistrationFee']) ? $Details['RegistrationFee'] : ''); ?></td>
                                                <td><?php echo $Details['MobileNumber']; ?></td>
                                                <td><?php echo $Details['CreateUserName']; ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($Details['CreateDate'])); ?></td>
                                                <td class="print-hidden">
<?php
                                                if ($LoggedUser->HasPermissionForTask(TASK_EDIT_STUDENT_REGISTRATION) === true)
                                                {
                                                    echo '<a href="edit_new_admission.php?Process=2&amp;StudentID=' . $StudentID . '" target="_blank">Edit Student</a>';
                                                    echo '&nbsp;|&nbsp;';
                                                    echo '<a href="print_admission_form.php?Process=2&amp;StudentID=' . $StudentID . '" target="_blank">Print Form</a>';
                                                }
                                                else
                                                {
                                                    echo 'Print Form';
                                                }
?>
                                                </td>
                                            </tr>
<?php
                                        }
                                    }
                                    else
                                    {
?>
                                        <tr>
                                            <td colspan="13">No Records</td>
                                        </tr>
<?php
                                    }
?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <!-- /.table-responsive -->
                        </div>
                        <!-- /.panel-body -->
                    </div>
                    <!-- /.panel -->
                </div>
                <!-- /.col-lg-12 -->
            </div>
<?php
        }
?>
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
<!-- DataTables JavaScript -->
<script src="/admin/vendor/datatables/js/jquery.dataTables.min.js"></script>
<script src="/admin/vendor/datatables-plugins/dataTables.bootstrap.min.js"></script>
<script src="/admin/vendor/datatables-responsive/dataTables.responsive.js"></script>	
<script type="text/javascript">
$(document).ready(function() {
    $("body").on('click', '.delete-record', function()
    {   
        if (!confirm("Are you sure you want to delete this record?"))
        {
            return false;
        }
    });

    $('#DataTableRecords').DataTable({
        responsive: true,
        bPaginate: false,
        bSort: false,
        searching: false, 
        info: false
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
				$('#State').html('<option value="0">Select State</option>' + ResultArray[1]);
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
				$('#District').html('<option value="0">Select District</option>' + ResultArray[1]);
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
				$('#City').html('<option value="0">Select City</option>' + ResultArray[1]);
			}
		 });
	});
});
</script>
<!-- JavaScript To Print A Report -->
<script src="/admin/js/print-report.js"></script>
</body>
</html>
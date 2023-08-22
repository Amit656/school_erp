<?php
require_once("../../classes/class.users.php");
require_once('../../classes/class.validation.php');
require_once("../../classes/class.authentication.php");

require_once("../../classes/school_administration/class.classes.php");
require_once("../../classes/school_administration/class.countries.php");
require_once("../../classes/school_administration/class.states.php");
require_once("../../classes/school_administration/class.cities.php");
require_once("../../classes/admission_cell/class.exams.php");
require_once("../../classes/admission_cell/class.exam_registrations.php");

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
	header('location:unauthorized_login_admin.php');
	exit;
}
catch (Exception $e)
{
	header('location:unauthorized_login_admin.php');
	exit;
}
// END OF 1. //

if ($LoggedUser->HasPermissionForTask(TASK_LIST_EXAM_REGISTRATION) !== true)
{
    header('location:/admin/unauthorized_login_admin.php');
    exit;
}

$GenderList = array('Male' => 'Male', 'Female' => 'Female', 'Others' => 'Others');
$ActiveStatusList = array(1 => 'Yes', 0 => 'No');
$AdmissionConfirmedList = array('Confirmed' => 'Confirmed', 'Pending' => 'Pending');
$ExamStatusList = array('Awaited' => 'Awaited', 'Passed' => 'Passed', 'Failed' => 'Failed');

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
$AllExamRegistrations = array();

$HasErrors = false;

$Clean = array();
$Clean['Process'] = 0;
$Clean['ExamRegistrationID'] = 0;

$Clean['ExamID'] = 0;

$Clean['ClassID'] = array();

$Clean['StudentName'] = '';
$Clean['DOB'] = '';
$Clean['Gender'] = array();

$Clean['MobileNumber'] = '';
$Clean['FatherName'] = '';
$Clean['MotherName'] = '';


$Clean['CountryID'] = 0;
$Clean['StateID'] = 0;
$Clean['DistrictID'] = 0;
$Clean['CityID'] = 0;

$Clean['IsAdmissionConfirmed'] = '';
$Clean['ExamStatus'] = '';

$Clean['MarksFrom'] = 0;
$Clean['MarksTo'] = 0;

$Clean['IsActive'] = 1;

// paging and sorting variables start here  //
$Clean['CurrentPage'] = 1;
$TotalPages = 0;

$Start = 0;
$Limit = 10;
// end of paging variables //

if (isset($_GET['hdnProcess']))
{
    $Clean['Process'] = (int) $_GET['hdnProcess'];
}
else if (isset($_GET['Process']))
{
	$Clean['Process'] = (int) $_GET['Process'];
}
switch ($Clean['Process'])
{
	case 5:
		if ($LoggedUser->HasPermissionForTask(TASK_DELETE_EXAM_REGISTRATION) !== true)
		{
			header('location:unauthorized_login_admin.php');
			exit;
		}
		
		if (isset($_GET['ExamRegistrationID']))
		{
			$Clean['ExamRegistrationID'] = (int) $_GET['ExamRegistrationID'];			
		}
		
		if ($Clean['ExamRegistrationID'] <= 0)
		{
			header('location:../error.php');
			exit;
		}						
			
		try
		{
			$ExamRegistrationToDelete = new ExamRegistration($Clean['ExamRegistrationID']);
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
		
		// if ($ExamRegistrationToDelete->CheckDependencies())
		// {
		// 	$RecordValidator->AttachTextError('This task group cannot be deleted. There are dependent records for this task group.');
		// 	$HasErrors = true;
		// 	break;
		// }
				
		if (!$ExamRegistrationToDelete->Remove())
		{
			$RecordValidator->AttachTextError(ProcessErrors($ExamRegistrationToDelete->GetLastErrorCode()));
			$HasErrors = true;
			break;
		}
		
		$RecordDeletedSuccessfully = true;
	    break;

    case 7:
        if (isset($_GET['drdClass']) && is_array($_GET['drdClass']))
        {
            $Clean['ClassID'] = $_GET['drdClass'];
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

        if (isset($_GET['drdIsAdmissionConfirmed']))
        {
            $Clean['IsAdmissionConfirmed'] = strip_tags(trim($_GET['drdIsAdmissionConfirmed']));
        }

        if (isset($_GET['drdExamStatus']))
        {
            $Clean['ExamStatus'] = strip_tags(trim($_GET['drdExamStatus']));
        }

        if (isset($_GET['txtMarksFrom']))
        {
            $Clean['MarksFrom'] = strip_tags(trim($_GET['txtMarksFrom']));
        }

        if (isset($_GET['txtMarksTo']))
        {
            $Clean['MarksTo'] = strip_tags(trim($_GET['txtMarksTo']));
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

        if ($Clean['FatherName'] != '') 
        {
            $RecordValidator->ValidateStrings($Clean['FatherName'], 'Father name is required and should be between 1 and 30 characters.', 1, 30);
        }

        if ($Clean['MotherName'] != '') 
        {
            $RecordValidator->ValidateStrings($Clean['MotherName'], 'Mother Name is required and should be between 1 and 30 characters.', 2, 30);
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

        if ($Clean['IsAdmissionConfirmed'] != '') 
        {
            $RecordValidator->ValidateInSelect($Clean['IsAdmissionConfirmed'], $AdmissionConfirmedList, 'Unknown error, please try again.');
        }

        if ($Clean['ExamStatus'] != '')
        {
            $RecordValidator->ValidateInSelect($Clean['ExamStatus'], $ExamStatusList, 'Unknown error, please try again.');
        }

        if ($Clean['MarksFrom'] != '')
        {
            $RecordValidator->ValidateInteger($Clean['MarksFrom'], 'Marks from should be Interger.', 1);
        }

        if ($Clean['MarksTo'] != '')
        {
            $RecordValidator->ValidateInteger($Clean['MarksTo'], 'Marks to should be Interger.', 1);
        }

        $RecordValidator->ValidateInSelect($Clean['IsActive'], $ActiveStatusList, 'Unknown error, please try again.');

        if ($RecordValidator->HasNotifications())
        {
            $HasErrors = true;
            break;
        }   

        //set record filters
        $Filters['ClassID'] = $Clean['ClassID'];
        $Filters['StudentName'] = $Clean['StudentName'];
        $Filters['Genders'] = $Clean['Gender'];

        $Filters['MobileNumber'] = $Clean['MobileNumber'];
        $Filters['FatherName'] = $Clean['FatherName'];
        $Filters['MotherName'] = $Clean['MotherName'];

        $Filters['CountryID'] = $Clean['CountryID'];
        $Filters['StateID'] = $Clean['StateID'];
        $Filters['DistrictID'] = $Clean['StateID'];
        $Filters['CityID'] = $Clean['CityID'];
        $Filters['IsAdmissionConfirmed'] = $Clean['IsAdmissionConfirmed'];

        if ($Clean['IsAdmissionConfirmed'] == 'Confirmed') 
        {
            $Filters['IsAdmissionConfirmed'] = 1;
        }
        else if($Clean['IsAdmissionConfirmed'] == 'Pending')
        {
            $Filters['IsAdmissionConfirmed'] = 0;
        }

        $Filters['ExamStatus'] = $Clean['ExamStatus'];

        $Filters['MarksFrom'] = $Clean['MarksFrom'];
        $Filters['MarksTo'] = $Clean['MarksTo'];

        $Filters['ActiveStatus'] = $Clean['IsActive'];

        //get records count
        ExamRegistration::SearchExamRegistrations($TotalRecords, true, $Filters);

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
            $AllExamRegistrations = ExamRegistration::SearchExamRegistrations($TotalRecords, false, $Filters, $Start, $Limit);
        }

        break;
}

require_once('../html_header.php');
?>
<title>Exam Registration List</title>
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
                    <h1 class="page-header">Exam Registration List</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <div class="row">
                <div class="col-lg-12">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <strong>Filter ExamRegistration List</strong>
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
                                         <form class="form-horizontal" name="FilterExamRegistration" id="FilterExamRegistration" action="exam_registrations_list.php" method="get">
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
                                                    if (is_array($GenderList) && count($GenderList) > 0)
                                                    {
                                                        foreach ($GenderList as $Gender => $GenderName)
                                                        {
?>
                                                            <option value="<?php echo $Gender; ?>" <?php echo(in_array($Gender, $Clean['Gender']) ? 'selected="selected"' : ''); ?> ><?php echo $GenderName; ?> </option>
<?php
                                                        }
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
                                                    <select class="form-control" name="drdCountry" id="CountryID">
                                                        <option value="0">Select-</option>
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
                                                    <select class="form-control" name="drdState" id="StateID">
                                                        <option value="0">Select-</option>
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
                                                    <select class="form-control" name="drdDistrict" id="DistrictID">
                                                        <option value="0">Select-</option>
<?php
                                                    if (is_array($CityList) && count($CityList) > 0)
                                                    {
                                                        foreach ($CityList as $CityID => $CityName) 
                                                        {
                                                            echo '<option ' . ($Clean['DistrictID'] == $CityID ? 'selected="selected"' : '') . ' value="' . $CityID . '">' . $CityName . '</option>';
                                                        }
                                                    }
?>
                                                    </select>
                                                </div>
                                                <label for="City" class="col-lg-2 control-label">City</label>
                                                <div class="col-lg-4">
                                                    <select class="form-control" name="drdCity" id="City">
                                                        <option value="0">Select-</option>
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
                                                <label for="AdmissionStatus" class="col-lg-2 control-label">Admission Status</label>
                                                <div class="col-lg-4">
                                                    <select class="form-control" name="drdIsAdmissionConfirmed" id="AdmissionStatus">
                                                        <option value="" selected="selected">Select-</option>
<?php
                                                    foreach ($AdmissionConfirmedList as $AdmissionConfirmedName) 
                                                    {
?>
                                                        <option value="<?php echo $AdmissionConfirmedName; ?>" <?php echo($Clean['IsAdmissionConfirmed'] == $AdmissionConfirmedName) ? 'selected="selected"' : ''; ?> ><?php echo $AdmissionConfirmedName; ?></option>
<?php
                                                    }
?>
                                                    </select>
                                                </div>
                                                <label for="ExamStatus" class="col-lg-2 control-label">Exam Status</label>
                                                <div class="col-lg-4">
                                                    <select class="form-control" name="drdExamStatus" id="ExamStatus">
                                                        <option value="">Select-</option>
<?php
                                                    foreach ($ExamStatusList as $ExamStatus => $ExamStatusName) 
                                                    {
?>
                                                        <option value="<?php echo $ExamStatus; ?>" <?php echo($Clean['ExamStatus'] == $ExamStatus) ? 'selected="selected"' : ''; ?> ><?php echo $ExamStatusName; ?></option>
<?php
                                                    }
?>
                                                    </select>
                                                </div>    
                                            </div>
                                            <div class="form-group">
                                                <label for="MarksRange" class="col-lg-2 control-label">Marks Range</label>
                                                <div class="col-lg-4">
                                                    <div class="col-lg-4"><input class="form-control" id="MarksFrom" maxlength="3" type="text" name="txtMarksFrom" value="<?php echo($Clean['MarksFrom']) ? $Clean['MarksFrom'] : '' ?>"></div>
                                                    <div class="col-lg-2"><label for="MarksRange" class="control-label">To</label></div>
                                                    <div class="col-lg-4"><input class="form-control" id="MarksTo" maxlength="3" type="text" name="txtMarksTo" value="<?php echo($Clean['MarksTo']) ? $Clean['MarksTo'] : '' ?>"></div>
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
                            <strong>Total Records Returned: <?php echo count($AllExamRegistrations); ?></strong>
                        </div>
                        <!-- /.panel-heading -->
                        <div class="panel-body">
                            <div>
                                <div class="row">
                                    <div class="col-lg-6">
                                    <div class="add-new-btn-container"><a href="add_exam_registration.php" class="btn btn-primary<?php echo $LoggedUser->HasPermissionForTask(TASK_ADD_EXAM_REGISTRATION) === true ? '' : ' disabled'; ?>" role="button">Add Exam Registration</a></div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="print-btn-container"><button id="PrintButton" type="submit" class="btn btn-primary">Print</button></div>
                                    </div>
                                </div>
                                <div class="row" id="RecordTableHeading">
                                    <div class="col-lg-12">
                                        <div class="report-heading-container"><strong>Exam Registrations on <?php echo date('d-m-Y h:i A'); ?></strong></div>
                                    </div>
                                </div>
                                <div class="row" id="RecordTable">
                                    <div class="col-lg-12">
                                        <table width="100%" class="table table-striped table-bordered table-hover" id="DataTableRecords">
                                            <thead>
                                                <tr>
                                                    <th>S. No</th>
                                                    <th>Class</th>
                                                    <th>Exam Name</th>
                                                    <th>Student Name</th>
                                                    <th>Father Name</th>
                                                    <th>Mother Name</th>
                                                    <th>Registration Amount</th>
                                                    <th>Exam Status</th>
                                                    <th>Obtain Marks</th>
                                                    <th>Is Admission Confirmed</th>
                                                    <th>Is Active</th>
                                                    <th>Create User</th>
                                                    <th>Create Date</th>
                                                    <th class="print-hidden">Operations</th>
                                                </tr>
                                            </thead>
                                            <tbody>
<?php
                                    if (is_array($AllExamRegistrations) && count($AllExamRegistrations) > 0)
                                    {
                                        $Counter = 0;
                                        foreach ($AllExamRegistrations as $ExamRegistrationID => $ExamRegistrationDetails)
                                        {
?>
                                            <tr>
                                                <td><?php echo ++$Counter; ?></td>
                                                <td><?php echo $ExamRegistrationDetails['ClassName']; ?></td>
                                                <td><?php echo $ExamRegistrationDetails['ExamName']; ?></td>
                                                <td><?php echo $ExamRegistrationDetails['FirstName'] . " " . $ExamRegistrationDetails['LastName']; ?></td>
                                                <td><?php echo $ExamRegistrationDetails['FatherName']; ?></td>
                                                <td><?php echo $ExamRegistrationDetails['MotherName']; ?></td>
                                                <td><?php echo $ExamRegistrationDetails['RegistrationAmount']; ?></td>
                                                <td><?php echo $ExamRegistrationDetails['ExamStatus']; ?></td>
                                                <td><?php echo $ExamRegistrationDetails['ObtainedMarks']; ?></td>
                                                <td><?php echo (($ExamRegistrationDetails['IsAdmissionConfirmed']) ? 'Yes' : 'No'); ?></td>
                                                <td><?php echo (($ExamRegistrationDetails['IsActive']) ? 'Yes' : 'No'); ?></td>
                                                <td><?php echo $ExamRegistrationDetails['CreateUserName']; ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($ExamRegistrationDetails['CreateDate'])); ?></td>
                                                <td class="print-hidden">
<?php
                                                if ($LoggedUser->HasPermissionForTask(TASK_DELETE_EXAM_REGISTRATION) === true)
                                                {
                                                    echo '<a href="edit_exam_registration.php?Process=11&amp;ExamRegistrationID=' . $ExamRegistrationID . '">Add Marks</a>';
                                                }
                                                else
                                                {
                                                    echo 'Add Marks';
                                                }

                                                echo '&nbsp;|&nbsp;';

                                                if ($LoggedUser->HasPermissionForTask(TASK_EDIT_EXAM_REGISTRATION) === true)
                                                {
                                                    echo '<a href="edit_exam_registration.php?Process=2&amp;ExamRegistrationID='. $ExamRegistrationID .'">Edit</a>';
                                                }
                                                else
                                                {
                                                    echo 'Edit';
                                                }

                                                echo '&nbsp;|&nbsp;';

                                                if ($LoggedUser->HasPermissionForTask(TASK_DELETE_EXAM_REGISTRATION) === true)
                                                {
                                                    echo '<a class="delete-record" href="exam_registrations_list.php?Process=5&amp;ExamRegistrationID=' . $ExamRegistrationID . '">Delete</a>'; 
                                                }
                                                else
                                                {
                                                    echo 'Delete';
                                                }
?>
                                                </td>
                                            </tr>
<?php
                                        }
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
        if (!confirm("Are you sure you want to delete this exam registration?"))
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
});
</script>
<!-- JavaScript To Print A Report -->
<script src="/admin/js/print-report.js"></script>
</body>
</html>
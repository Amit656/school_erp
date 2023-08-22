<?php
error_log(json_encode($_REQUEST));
header("Content-Type:application/json");

require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.users.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.validation.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.authentication.php');

require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.helpers.php');

require_once($_SERVER['DOCUMENT_ROOT'] . '/mobile_app_services/classes/class.json_response.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/mobile_app_services/includes/global_defaults.php');

require_once($_SERVER['DOCUMENT_ROOT'] . '/mobile_app_services/includes/process_errors.php');

//	Other Required Classes
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/school_administration/class.classes.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/school_administration/class.staff_attendence.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/school_administration/class.branch_staff.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/mobile_app_services/classes/class.app.branch_staff.php');

$Response = new JSONResponse();

$Clean = array();

$Clean['SchoolCode'] = '';
$Clean['UniqueToken'] = md5(uniqid(rand(), true));

$Clean['BranchStaffID'] = 0;
$Clean['ClassTimeTableDetailID'] = 0;

if (isset($_REQUEST['SchoolCode']))
{
	$Clean['SchoolCode'] = strip_tags(trim((string) $_REQUEST['SchoolCode']));
}

if (isset($_REQUEST['Token']))
{
	$Clean['UniqueToken'] = strip_tags(trim((string) $_REQUEST['Token']));
}

if (isset($_REQUEST['BranchStaffID']))
{
	$Clean['BranchStaffID'] = strip_tags(trim((string) $_REQUEST['BranchStaffID']));
}

if (isset($_REQUEST['ClassTimeTableDetailID']))
{
	$Clean['ClassTimeTableDetailID'] = strip_tags(trim((string) $_REQUEST['ClassTimeTableDetailID']));
}

try
{
	$LoggedInBranchStaff = new AppBranchStaff($Clean['UniqueToken']);
    
    if ($LoggedInBranchStaff->GetStaffCategory() != 'Management')
	{
		$Response->SetError(1);
		$Response->SetErrorCode(100025);
		$Response->SetMessage('Error: you are not a management staff.');

		echo json_encode($Response->GetResponseAsArray());
		exit;
	}
	
	$BranchStaffList = array();
	$BranchStaffList = BranchStaff::GetActiveBranchStaff('Teaching');
	
	if (!array_key_exists($Clean['BranchStaffID'], $BranchStaffList))
	{
		$Response->SetError(1);
    	$Response->SetErrorCode(UNKNOWN_ERROR);
    	$Response->SetMessage(ProcessAppErrors(UNKNOWN_ERROR));

		echo json_encode($Response->GetResponseAsArray());
		exit;
	}
	
	$BranchStaffObject = new BranchStaff($Clean['BranchStaffID']);
	
	$Clean['DayID'] = Date('N'); // gives 1 for monday through 7 for sunday
	
	$AllPeriodsDetails = array();
    $AllPeriodsDetails = $BranchStaffObject->GetCurrentDayTimeTable($Clean['DayID']);
	
	$CurrentDaySubstitutions = array();
    $CurrentDaySubstitutions = Helpers::GetCurrentDaySubstitutions();
	
	$TimeTableDetails = array();
	$TimeTableDetails = $AllPeriodsDetails[$Clean['ClassTimeTableDetailID']];
			
	$AvailableTeacherList = array();
	$AvailableTeacherList = AddedClass::GetAvailableTeachersForSubstitution($Clean['DayID'], $TimeTableDetails['ClassID'], $TimeTableDetails['ClassSubjectID'], $TimeTableDetails['PeriodStartTime'], $TimeTableDetails['PeriodEndTime']);
	
	$PeriodSubstitutionDetails = array();
	$AvailableTeachers = array();
	
	if (isset($CurrentDaySubstitutions[$Clean['ClassTimeTableDetailID']]))
	{
		$PeriodSubstitutionDetails = $CurrentDaySubstitutions[$Clean['ClassTimeTableDetailID']];
	}
	
	foreach ($AvailableTeacherList as $TeacherID => $TeacherDetails)
	{
	    $AvailableTeachers[$TeacherID]['SubtitutedTeacherClassID'] = '';
		$AvailableTeachers[$TeacherID]['SubtitutedTeacherName'] = '';
		
		if (count($PeriodSubstitutionDetails) > 0 && $PeriodSubstitutionDetails['TeacherClassID'] == $TeacherID)
		{
		    $AvailableTeachers[$TeacherID]['SubtitutedTeacherClassID'] = $TeacherID;
		    $AvailableTeachers[$TeacherID]['SubtitutedTeacherName'] = $TeacherDetails['FirstName'] .' '. $TeacherDetails['LastName'];
			continue;
		}
		else if ($TeacherDetails['IsBusy'] == 'Class')
		{
		    $AvailableTeachers[$TeacherID]['TeacherClassID'] = $TeacherID;
		    $AvailableTeachers[$TeacherID]['TeacherName'] = $TeacherDetails['FirstName'] .' '. $TeacherDetails['LastName'] .' (Already Assigned Class)';
			continue;
		}
		else if ($TeacherDetails['IsBusy'] == 'Substitution')
		{
		    $AvailableTeachers[$TeacherID]['TeacherClassID'] = $TeacherID;
		    $AvailableTeachers[$TeacherID]['TeacherName'] = $TeacherDetails['FirstName'] .' '. $TeacherDetails['LastName'] .' (Already Substituted)';
			continue;
		}
		
		$AvailableTeachers[$TeacherID]['TeacherClassID'] = $TeacherID;
		$AvailableTeachers[$TeacherID]['TeacherName'] = $TeacherDetails['FirstName'] .' '. $TeacherDetails['LastName'];
	}
	
	
	$Response->PushData('AvailableTeachers', $AvailableTeachers);
}
catch (ApplicationDBException $e)
{
	$Response->SetError(1);
	$Response->SetErrorCode(UNKNOWN_ERROR);
	$Response->SetMessage(ProcessAppErrors(UNKNOWN_ERROR));
	
	echo json_encode($Response->GetResponseAsArray());
	exit;
}
catch (Exception $e)
{
	$Response->SetError(1);
	$Response->SetErrorCode(UNKNOWN_ERROR);
	$Response->SetMessage(ProcessAppErrors(UNKNOWN_ERROR));

	echo json_encode($Response->GetResponseAsArray());
	exit;
}

echo json_encode($Response->GetResponseAsArray());
exit;
?>
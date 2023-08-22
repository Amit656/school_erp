<?php
header("Content-Type:application/json");

require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.users.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.validation.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.authentication.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.date_processing.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.helpers.php');

require_once($_SERVER['DOCUMENT_ROOT'] . '/mobile_app_services/classes/class.json_response.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/mobile_app_services/includes/global_defaults.php');

require_once($_SERVER['DOCUMENT_ROOT'] . '/mobile_app_services/includes/process_errors.php');

//	Other Required Classes
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/school_administration/class.academic_years.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/school_administration/class.academic_calendar.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/mobile_app_services/classes/class.app.academic_calendar.php');

require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/school_administration/class.parent_details.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/mobile_app_services/classes/class.app.parent_details.php');

$Response = new JSONResponse();

$Clean = array();

$Clean['SchoolCode'] = '';
$Clean['Token'] = '';
$Clean['StudentID'] = 0;

//	Other Variables

if (isset($_REQUEST['SchoolCode']))
{
	$Clean['SchoolCode'] = strip_tags(trim((string) $_REQUEST['SchoolCode']));
}

if (isset($_REQUEST['Token']))
{
	$Clean['Token'] = strip_tags(trim((string) $_REQUEST['Token']));
}

if (isset($_REQUEST['StudentID']))
{
	$Clean['StudentID'] = (int) $_REQUEST['StudentID'];
}

try
{
	$LoggedInParent = new AppParentDetail($Clean['Token']);
	
	if (!array_key_exists($Clean['StudentID'], $LoggedInParent->GetApplicableStudents())) 
	{
		$Response->SetError(1);
		$Response->SetErrorCode(UNKNOWN_ERROR);
		$Response->SetMessage(ProcessAppErrors(UNKNOWN_ERROR));

		echo json_encode($Response->GetResponseAsArray());
		exit;
	}

	$LoggedInParent->GetClassSectionDetails($Clean['StudentID']);

	$CurrentAcademicYearName = '';
	$StartDate = '';
	$EndDate = '';
	AcademicYear::GetCurrentAcademicYear($CurrentAcademicYearName, $StartDate, $EndDate);

	$AcademicYearStartDate = $StartDate;
	$AcademicYearEndDate = $EndDate;
	
	$AllWorkingDays = Helpers::GetClassWorkingDays($AcademicYearStartDate, $AcademicYearEndDate, $LoggedInParent->GetClassSectionID());

	$AllDays = array(); // for whole year;

	$Date = $AcademicYearStartDate;

	while (strtotime($Date) <= strtotime($AcademicYearEndDate)) 
	{
		$AllDays[date('Y-m-d', strtotime($Date))] = 1;
		$Date = date('Y-m-d', strtotime('+1 Day', strtotime($Date)));
	}

	$WeekOff = array();
	$WeekOff = array_keys(array_diff_key($AllDays, $AllWorkingDays));

	$AllEventDates = array();
	$AllEventDates = AppAcademicCalendar::GetAcademicCalenderHolidayEventDates();

	if (count($WeekOff) > 0) 
	{
		$Response->SetDataOnKey('Week_offs', $WeekOff);
	}
	
	if (count($AllEventDates) > 0) 
	{
		$Response->PushData('academic_calendar_event', $AllEventDates);
	}
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
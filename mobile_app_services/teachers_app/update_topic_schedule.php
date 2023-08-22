<?php
header("Content-Type:application/json");

require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.users.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.validation.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.authentication.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.date_processing.php');

require_once($_SERVER['DOCUMENT_ROOT'] . '/includes/global_defaults.inc.php');

require_once($_SERVER['DOCUMENT_ROOT'] . '/mobile_app_services/classes/class.json_response.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/mobile_app_services/includes/global_defaults.php');

require_once($_SERVER['DOCUMENT_ROOT'] . '/mobile_app_services/includes/process_errors.php');

//Other Required Classess
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/school_administration/class.classes.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/school_administration/class.class_sections.php');

require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/academic_supervision/class.topic_schedules.php');

require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/school_administration/class.branch_staff.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/mobile_app_services/classes/class.app.branch_staff.php');

$Response = new JSONResponse();

$Clean = array();
$Filters = array();

$Clean['SchoolCode'] = '';
$Clean['Token'] = '';

//	Other Variables
$Clean['TopicScheduleDetailID'] = 0;
$Clean['TopicStatus'] = '';
$Clean['Remark'] = '';
$Clean['EndDate'] = '';

if (isset($_REQUEST['SchoolCode']))
{
	$Clean['SchoolCode'] = strip_tags(trim((string) $_REQUEST['SchoolCode']));
}

if (isset($_REQUEST['Token']))
{
	$Clean['Token'] = strip_tags(trim((string) $_REQUEST['Token']));
}

if (isset($_REQUEST['TopicScheduleDetailID']))
{
	$Clean['TopicScheduleDetailID'] = (int) $_REQUEST['TopicScheduleDetailID'];
}
if (isset($_REQUEST['TopicStatus']))
{
	$Clean['TopicStatus'] = (string) $_REQUEST['TopicStatus'];
}
if (isset($_REQUEST['Remark']))
{
	$Clean['Remark'] = (string) $_REQUEST['Remark'];
}
if (isset($_REQUEST['EndDate']))
{
	$Clean['EndDate'] = (string) $_REQUEST['EndDate'];
}

try
{
	$LoggedInBranchStaff = new AppBranchStaff($Clean['Token']);

	if ($Clean['TopicScheduleDetailID'] <= 0 || $Clean['TopicStatus'] == '')
	{
		$Response->SetError(1);
		$Response->SetErrorCode(UNKNOWN_ERROR);
		$Response->SetMessage(ProcessAppErrors(UNKNOWN_ERROR));	

		echo json_encode($Response->GetResponseAsArray());
		exit;
	}

	$TopicScheduleDetail = array();
	$UpdatedScheduleDetails = array();

	$TopicScheduleDetail[$Clean['TopicScheduleDetailID']]['EndDate'] = '';

	if ($Clean['EndDate'] != '') 
	{
		$TopicScheduleDetail[$Clean['TopicScheduleDetailID']]['EndDate'] = date('Y-m-d', strtotime(DateProcessing::ToggleDateDayAndMonth(($Clean['EndDate']))));
	}

	$TopicScheduleDetail[$Clean['TopicScheduleDetailID']]['Status'] = $Clean['TopicStatus'];
	$TopicScheduleDetail[$Clean['TopicScheduleDetailID']]['Remark'] = $Clean['Remark'];

	$UpdatedScheduleDetails = TopicSchedule::UpdateTopicStatus($TopicScheduleDetail);

	if (count($UpdatedScheduleDetails) <= 0)
	{
		$Response->SetError(1);
		$Response->SetErrorCode(UNKNOWN_ERROR);
		$Response->SetMessage(ProcessAppErrors(UNKNOWN_ERROR));	

		echo json_encode($Response->GetResponseAsArray());
		exit;
	}
	
	$Response->SetMessage(ProcessAppMessages(SAVED_SUCCESSFULLY));
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
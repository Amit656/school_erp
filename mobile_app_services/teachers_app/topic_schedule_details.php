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
$Clean['TopicScheduleID'] = 0;

if (isset($_REQUEST['SchoolCode']))
{
	$Clean['SchoolCode'] = strip_tags(trim((string) $_REQUEST['SchoolCode']));
}

if (isset($_REQUEST['Token']))
{
	$Clean['Token'] = strip_tags(trim((string) $_REQUEST['Token']));
}

if (isset($_REQUEST['TopicScheduleID']))
{
	$Clean['TopicScheduleID'] = (int) $_REQUEST['TopicScheduleID'];
}

try
{
	$LoggedInBranchStaff = new AppBranchStaff($Clean['Token']);

	$TopicScheduleObject = new TopicSchedule($Clean['TopicScheduleID']);

	$TopicScheduleObject->FillTopicScheduleDetails();
	$ScheduledTopicsList = $TopicScheduleObject->GetTopicScheduleDetails();

	$Data = array();
	$Counter = 0;

	foreach ($ScheduledTopicsList as $ChapterName => $TopicDetails) 
	{
		$Data[$Counter]['ChapterName'] = $ChapterName;
		$Count = 0;

		foreach ($TopicDetails as $TopicScheduleDetailID => $Details) 
		{
			$Details['TopicScheduleDetailID'] = $TopicScheduleDetailID;

			$Data[$Counter]['TopicDetails'][$Count] = $Details;
			$Count++;
		}

		$Counter++;
	}

	$Response->PushData('ScheduledTopicsList', $Data);
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
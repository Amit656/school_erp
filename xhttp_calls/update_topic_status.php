<?php
//header('Content-Type: application/json');

require_once('../classes/class.users.php');
require_once('../classes/class.authentication.php');

try
{
	$AuthObject = new ApplicationAuthentication;
	$LoggedUser = new User(0, $AuthObject->CheckValidUser());
}

// THIS CATCH BLOCK BUBBLES THE EXCEPTION TO THE BUILT IN 'Exception' CLASS IF THERE ARE ANY UNCAUGHT ERRORS //
catch (ApplicationAuthException $e)
{
	echo 'error|*****|Unknown error, please try again.';
	exit;
}
catch (Exception $e)
{
	echo 'error|*****|Unknown error, please try again.';
	exit;
}

require_once("../classes/class.date_processing.php");
require_once("../classes/academic_supervision/class.topic_schedules.php");

$TopicScheduleDetailID = 0;
$TopicStatus = '';
$Remark = '';
$EndDate = '';

if (isset($_POST['SelectedTopicScheduleDetailID']))
{
	$TopicScheduleDetailID = (int) $_POST['SelectedTopicScheduleDetailID'];
}

if (isset($_POST['SelectedTopicStatus']))
{
	$TopicStatus = (string) $_POST['SelectedTopicStatus'];
}
if (isset($_POST['Remark']))
{
	$Remark = (string) $_POST['Remark'];
}
if (isset($_POST['EndDate']))
{
	$EndDate = (string) $_POST['EndDate'];
}

if ($TopicScheduleDetailID <= 0 || $TopicStatus == '')
{
	echo 'error|*****|Unknown error, please try again.';
	exit;
}

$UpdatedScheduleDetails = '';
$TopicScheduleDetail = array();

if ($EndDate != '') 
{
	$TopicScheduleDetail[$TopicScheduleDetailID]['EndDate'] = date('Y-m-d', strtotime(DateProcessing::ToggleDateDayAndMonth(($EndDate))));
}

$TopicScheduleDetail[$TopicScheduleDetailID]['Status'] = $TopicStatus;
$TopicScheduleDetail[$TopicScheduleDetailID]['Remark'] = $Remark;

$UpdatedScheduleDetails = TopicSchedule::UpdateTopicStatus($TopicScheduleDetail);

if (count($UpdatedScheduleDetails) <= 0)
{
	echo 'error|*****|Error in execution query.';
	exit;
}

echo 'success|*****|';
echo $UpdatedScheduleDetails[$TopicScheduleDetailID]['Status'];

echo '|*****|';
echo date('d/m/Y', strtotime($UpdatedScheduleDetails[$TopicScheduleDetailID]['EndDate']));

echo '|*****|';
echo $UpdatedScheduleDetails[$TopicScheduleDetailID]['Remark'];


exit;
?>
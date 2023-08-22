<?php
header("Content-Type:application/json");

require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.users.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.validation.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.authentication.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.date_processing.php');

require_once($_SERVER['DOCUMENT_ROOT'] . '/mobile_app_services/classes/class.json_response.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/mobile_app_services/includes/global_defaults.php');

require_once($_SERVER['DOCUMENT_ROOT'] . '/mobile_app_services/includes/process_errors.php');

//	Other Required Classess
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/school_administration/class.classes.php');

require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/academic_supervision/class.chapters.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/academic_supervision/class.chapter_topics.php');

require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/school_administration/class.branch_staff.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/mobile_app_services/classes/class.app.branch_staff.php');

$Response = new JSONResponse();

$Clean = array();

$Clean['SchoolCode'] = '';
$Clean['UniqueToken'] = md5(uniqid(rand(), true));

//	Other Variables
$Clean['ChapterTopicID'] = 0;
$Clean['ClassID'] = 0;
$Clean['ClassSubjectID'] = 0;
$Clean['ChapterID'] = 0;
$Clean['TopicName'] = '';
$Clean['ExpectedClasses'] = 0;
$Clean['Priority'] = 0;

if (isset($_REQUEST['SchoolCode']))
{
	$Clean['SchoolCode'] = strip_tags(trim((string) $_REQUEST['SchoolCode']));
}

if (isset($_REQUEST['Token']))
{
	$Clean['UniqueToken'] = strip_tags(trim((string) $_REQUEST['Token']));
}

if (isset($_REQUEST['ChapterTopicID']))
{
	$Clean['ChapterTopicID'] = (int) $_REQUEST['ChapterTopicID'];
}

if (isset($_REQUEST['ClassID']))
{
	$Clean['ClassID'] = (int) $_REQUEST['ClassID'];
}

if (isset($_REQUEST['ClassSubjectID']))
{
	$Clean['ClassSubjectID'] = (int) $_REQUEST['ClassSubjectID'];
}

if (isset($_REQUEST['ChapterID']))
{
	$Clean['ChapterID'] = (int) $_REQUEST['ChapterID'];
}

if (isset($_REQUEST['TopicName']))
{
	$Clean['TopicName'] = strip_tags(trim((string) $_REQUEST['TopicName']));
}

if (isset($_REQUEST['ExpectedClasses']))
{
	$Clean['ExpectedClasses'] = strip_tags(trim((string) $_REQUEST['ExpectedClasses']));
}

if (isset($_REQUEST['Priority']))
{
	$Clean['Priority'] = (int) $_REQUEST['Priority'];
}

try
{
	$LoggedInBranchStaff = new AppBranchStaff($Clean['UniqueToken']);

	$AllClasses = array();
	$AllClasses = $LoggedInBranchStaff->GetTeacherApplicableClasses(false);

	$RecordValidator = new Validator();

	if (!$RecordValidator->ValidateInSelect($Clean['ClassID'], $AllClasses, 'Unknown error, please try again.')) 
	{
		$Response->SetError(1);
		$Response->SetErrorCode(UNKNOWN_ERROR);
		$Response->SetMessage(ProcessAppErrors(UNKNOWN_ERROR));

		echo json_encode($Response->GetResponseAsArray());
		exit;
	}

	$SelectedAddedClass = New AddedClass($Clean['ClassID']);
	$SelectedAddedClass->FillAssignedSubjects();

	$ClassSubjectsList = $SelectedAddedClass->GetAssignedSubjects();

	if (!$RecordValidator->ValidateInSelect($Clean['ClassSubjectID'], $ClassSubjectsList, 'Unknown error, please try again.')) 
	{
		$Response->SetError(1);
		$Response->SetErrorCode(UNKNOWN_ERROR);
		$Response->SetMessage(ProcessAppErrors(UNKNOWN_ERROR));

		echo json_encode($Response->GetResponseAsArray());
		exit;
	}

	$ChaptersList = array();
	$ChaptersList = Chapter::GetChapterByClassSubject($Clean['ClassSubjectID']);

	if (!$RecordValidator->ValidateInSelect($Clean['ChapterID'], $ChaptersList, 'Unknown error, please try again.')) 
	{
		$Response->SetError(1);
		$Response->SetErrorCode(UNKNOWN_ERROR);
		$Response->SetMessage(ProcessAppErrors(UNKNOWN_ERROR));

		echo json_encode($Response->GetResponseAsArray());
		exit;
	}

	if (!$RecordValidator->ValidateStrings($Clean['TopicName'], 'Topic name is required and should be between 1 and 100 characters.', 1, 100)) 
	{
		$Response->SetError(1);
		$Response->SetErrorCode(0);
		$Response->SetMessage('Topic name is required and should be between 1 and 100 characters.');

		echo json_encode($Response->GetResponseAsArray());
		exit;
	}

	$RecordValidator->ValidateInteger($Clean['ExpectedClasses'], 'Please enter numeric value for expected classes.', 1);

	if (!$RecordValidator->ValidateStrings($Clean['TopicName'], 'Topic name is required and should be between 1 and 100 characters.', 1, 100)) 
	{
		$Response->SetError(1);
		$Response->SetErrorCode(0);
		$Response->SetMessage('Expected classes is required and should be interger.');

		echo json_encode($Response->GetResponseAsArray());
		exit;
	}

	if (!$RecordValidator->ValidateInteger($Clean['Priority'], 'Priority is required and should be interger.', 1)) 
	{
		$Response->SetError(1);
		$Response->SetErrorCode(0);
		$Response->SetMessage('Priority is required and should be interger.');

		echo json_encode($Response->GetResponseAsArray());
		exit;
	}

	if ($Clean['ChapterTopicID'] > 0) 
	{
		$CurrentChapterTopic = new ChapterTopic($Clean['ChapterTopicID']);
	}
	else
	{	
		$CurrentChapterTopic = new ChapterTopic();
		$CurrentChapterTopic->SetIsActive(1);
	}

	$CurrentChapterTopic->SetChapterID($Clean['ChapterID']);
	$CurrentChapterTopic->SetTopicName($Clean['TopicName']);
	$CurrentChapterTopic->SetExpectedClasses($Clean['ExpectedClasses']);

	$CurrentChapterTopic->SetPriority($Clean['Priority']);

	$CurrentChapterTopic->SetCreateUserID($LoggedInBranchStaff->GetUserID());

	if (!$CurrentChapterTopic->Save())
	{
		$RecordValidator->AttachTextError(ProcessErrors($RecordValidator->GetLastErrorCode()));
		$HasErrors = true;
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
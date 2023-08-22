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
$Clean['ClassID'] = 0;
$Clean['ClassSubjectID'] = 0;
$Clean['ChapterID'] = 0;

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

	$AllTopicList = ChapterTopic::GetAllTopicsByChapterID($Clean['ChapterID']);

	if (count($AllTopicList) > 0) 
	{
		$Response->PushData('chapter_topics_list', $AllTopicList);
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
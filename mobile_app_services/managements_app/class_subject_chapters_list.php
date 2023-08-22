<?php
header("Content-Type:application/json");

require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.users.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.validation.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.authentication.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.date_processing.php');

require_once($_SERVER['DOCUMENT_ROOT'] . '/mobile_app_services/classes/class.json_response.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/mobile_app_services/includes/global_defaults.php');

require_once($_SERVER['DOCUMENT_ROOT'] . '/mobile_app_services/includes/process_errors.php');

//	Other Required Classes
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/academic_supervision/class.chapters.php');

require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/school_administration/class.branch_staff.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/mobile_app_services/classes/class.app.branch_staff.php');

$Response = new JSONResponse();

$Clean = array();

$Clean['SchoolCode'] = '';
$Clean['UniqueToken'] = md5(uniqid(rand(), true));

//	Other Variables
$Clean['ClassID'] = 0;
$Clean['ClassSubjectID'] = 0;

if (isset($_REQUEST['SchoolCode']))
{
	$Clean['SchoolCode'] = strip_tags(trim((string) $_REQUEST['SchoolCode']));
}

if (isset($_REQUEST['Token']))
{
	$Clean['UniqueToken'] = strip_tags(trim((string) $_REQUEST['Token']));
}

if (isset($_REQUEST['ClassID']))
{
	$Clean['ClassID'] = strip_tags(trim((string) $_REQUEST['ClassID']));
}

if (isset($_REQUEST['ClassSubjectID']))
{
	$Clean['ClassSubjectID'] = strip_tags(trim((string) $_REQUEST['ClassSubjectID']));
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
		$Response->SetErrorCode(0);
		$Response->SetMessage('Unknown error, please try again.');

		echo json_encode($Response->GetResponseAsArray());
		exit;
	}

	$AllSubjects = array();
	$AllSubjects = $LoggedInBranchStaff->GetTeacherClassSubjects($Clean['ClassID']);

	if (!$RecordValidator->ValidateInSelect($Clean['ClassSubjectID'], $AllSubjects, 'Unknown error, please try again.')) 
	{
		$Response->SetError(1);
		$Response->SetErrorCode(0);
		$Response->SetMessage('Unknown error, please try again.');

		echo json_encode($Response->GetResponseAsArray());
		exit;
	}
	
	$AllChapterList = Chapter::GetChapterByClassSubject($Clean['ClassSubjectID'], false);

	if (count($AllChapterList) > 0) 
	{
		$Response->PushData('class_subjects_chapters_list', $AllChapterList);
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
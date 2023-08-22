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
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/school_administration/class.academic_years.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/school_administration/class.classes.php');

require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/academic_supervision/class.chapters.php');

require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/school_administration/class.branch_staff.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/mobile_app_services/classes/class.app.branch_staff.php');

$Response = new JSONResponse();

$Clean = array();

$Clean['SchoolCode'] = '';
$Clean['UniqueToken'] = md5(uniqid(rand(), true));

//	Other Variables
$Clean['ChapterID'] = 0;
$Clean['ClassID'] = 0;
$Clean['ClassSubjectID'] = 0;
$Clean['ChapterName'] = '';
$Clean['Priority'] = 0;

if (isset($_REQUEST['SchoolCode']))
{
	$Clean['SchoolCode'] = strip_tags(trim((string) $_REQUEST['SchoolCode']));
}

if (isset($_REQUEST['Token']))
{
	$Clean['UniqueToken'] = strip_tags(trim((string) $_REQUEST['Token']));
}

if (isset($_REQUEST['ChapterID']))
{
	$Clean['ChapterID'] = (int) $_REQUEST['ChapterID'];
}

if (isset($_REQUEST['ClassID']))
{
	$Clean['ClassID'] = (int) $_REQUEST['ClassID'];
}

if (isset($_REQUEST['ClassSubjectID']))
{
	$Clean['ClassSubjectID'] = (int) $_REQUEST['ClassSubjectID'];
}

if (isset($_REQUEST['ChapterName']))
{
	$Clean['ChapterName'] = strip_tags(trim((string) $_REQUEST['ChapterName']));
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
		$Response->SetErrorCode(0);
		$Response->SetMessage('Unknown error, please try again.');

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

	if (!$RecordValidator->ValidateStrings($Clean['ChapterName'], 'Chapter name is required and should be between 3 and 150 characters.', 3, 150)) 
	{
		$Response->SetError(1);
		$Response->SetErrorCode(0);
		$Response->SetMessage('Chapter name is required and should be between 3 and 150 characters.');

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

	if ($Clean['ChapterID'] > 0) 
	{
		$CurrentChapter = new Chapter($Clean['ChapterID']);
	}
	else
	{	
		$CurrentChapter = new Chapter();
		$CurrentChapter->SetIsActive(1);
	}

	$CurrentChapter->SetAcademicYearID(AcademicYear::GetCurrentAcademicYear());

	$CurrentChapter->SetClassSubjectID($Clean['ClassSubjectID']);
	$CurrentChapter->SetChapterName($Clean['ChapterName']);

	$CurrentChapter->SetPriority($Clean['Priority']);

	$CurrentChapter->SetCreateUserID($LoggedInBranchStaff->GetUserID());

	if (!$CurrentChapter->Save())
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
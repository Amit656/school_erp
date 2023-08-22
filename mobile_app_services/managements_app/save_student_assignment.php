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
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/school_administration/class.class_sections.php');

require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/academic_supervision/class.chapters.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/academic_supervision/class.chapter_topics.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/academic_supervision/class.student_assignment.php');

require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/school_administration/class.branch_staff.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/mobile_app_services/classes/class.app.branch_staff.php');

$Response = new JSONResponse();

$Clean = array();

$Clean['SchoolCode'] = '';
$Clean['UniqueToken'] = md5(uniqid(rand(), true));

//	Other Variables
$Clean['AssignmentID'] = 0;
$Clean['ClassSectionID'] = 0;
$Clean['ClassSubjectID'] = 0;
$Clean['ChapterID'] = 0;
$Clean['ChapterTopicID'] = 0;

$Clean['AssignmentHeading'] = '';
$Clean['Assignment'] = '';

$Clean['IssueDate'] = '0000-00-00';
$Clean['EndDate'] = '0000-00-00';
$Clean['IsDraft'] = 0;

$Clean['UploadFile'] = array();

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

if (isset($_REQUEST['ClassSectionID']))
{
	$Clean['ClassSectionID'] = (int) $_REQUEST['ClassSectionID'];
}

if (isset($_REQUEST['ClassSubjectID']))
{
	$Clean['ClassSubjectID'] = (int) $_REQUEST['ClassSubjectID'];
}

if (isset($_REQUEST['ChapterID']))
{
	$Clean['ChapterID'] = (int) $_REQUEST['ChapterID'];
}

if (isset($_REQUEST['ChapterTopicID']))
{
	$Clean['ChapterTopicID'] = (int) $_REQUEST['ChapterTopicID'];
}

if (isset($_REQUEST['AssignmentHeading']))
{
	$Clean['AssignmentHeading'] = strip_tags(trim((string) $_REQUEST['AssignmentHeading']));
}

if (isset($_REQUEST['Assignment']))
{
	$Clean['Assignment'] = strip_tags(trim((string) $_REQUEST['Assignment']));
}

if (isset($_REQUEST['IssueDate']))
{
	$Clean['IssueDate'] = strip_tags(trim((string) $_REQUEST['IssueDate']));
}

if (isset($_REQUEST['EndDate']))
{
	$Clean['EndDate'] = strip_tags(trim((string) $_REQUEST['EndDate']));
}

if (isset($_REQUEST['IsDraft']))
{
	$Clean['IsDraft'] = (int) $_REQUEST['IsDraft'];
}

if (isset($_FILES['fleAssignmentImage']) && is_array($_FILES['fleAssignmentImage']))
{
    $Clean['UploadFile'] = $_FILES['fleAssignmentImage'];
}

try
{
	$LoggedInBranchStaff = new AppBranchStaff($Clean['UniqueToken']);

	$RecordValidator = new Validator();

	if ($LoggedInBranchStaff->IsClassSectionValid($Clean['ClassSectionID'])) 
	{
		$Response->SetError(1);
		$Response->SetErrorCode(UNKNOWN_ERROR);
		$Response->SetMessage(ProcessAppErrors(UNKNOWN_ERROR));

		echo json_encode($Response->GetResponseAsArray());
		exit;
	}

	$CurrentClassSection = new ClassSections($Clean['ClassSectionID']);

	$SelectedAddedClass = new AddedClass($CurrentClassSection->GetClassID());
	// getting subjects of class
	$SelectedAddedClass->FillAssignedSubjects();

	$ClassSubjectsList = array();
	$ClassSubjectsList = $SelectedAddedClass->GetAssignedSubjects();

	if (!$RecordValidator->ValidateInSelect($Clean['ClassSubjectID'], $ClassSubjectsList, 'Unknown error, please try again.')) 
	{
		$Response->SetError(1);
		$Response->SetErrorCode(UNKNOWN_ERROR);
		$Response->SetMessage(ProcessAppErrors(UNKNOWN_ERROR));

		echo json_encode($Response->GetResponseAsArray());
		exit;
	}

	// getting chapters of subject
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
	// getting chapters of topics
	$ChapterTopicList = array();
	$ChapterTopicList = ChapterTopic::GetAllTopicsByChapterID($Clean['ChapterID']);
	
	if (!$RecordValidator->ValidateInSelect($Clean['ChapterTopicID'], $ChapterTopicList, 'Unknown error, please try again.')) 
	{
		$Response->SetError(1);
		$Response->SetErrorCode(UNKNOWN_ERROR);
		$Response->SetMessage(ProcessAppErrors(UNKNOWN_ERROR));

		echo json_encode($Response->GetResponseAsArray());
		exit;
	}

	if (!$RecordValidator->ValidateStrings($Clean['AssignmentHeading'], 'Assignment heading is required and should be between 1 and 150 characters.', 1, 150)) 
	{
		$Response->SetError(1);
		$Response->SetErrorCode(0);
		$Response->SetMessage('Assignment heading is required and should be between 1 and 150 characters.');

		echo json_encode($Response->GetResponseAsArray());
		exit;
	}

	if (!$RecordValidator->ValidateStrings($Clean['Assignment'], 'Assignment is required and should be between 1 and 1500 characters.', 1, 1500)) 
	{
		$Response->SetError(1);
		$Response->SetErrorCode(0);
		$Response->SetMessage('Assignment is required and should be between 1 and 1500 characters.');

		echo json_encode($Response->GetResponseAsArray());
		exit;
	}

	if (!$RecordValidator->ValidateDate($Clean['IssueDate'], 'Please enter a valid issue date.')) 
	{
		$Response->SetError(1);
		$Response->SetErrorCode(0);
		$Response->SetMessage('Please enter a valid issue date.');

		echo json_encode($Response->GetResponseAsArray());
		exit;
	}

	if (!$RecordValidator->ValidateDate($Clean['EndDate'], 'Please enter a valid end date.')) 
	{
		$Response->SetError(1);
		$Response->SetErrorCode(0);
		$Response->SetMessage('Please enter a valid end date.');

		echo json_encode($Response->GetResponseAsArray());
		exit;
	}

	if (strtotime(DateProcessing::ToggleDateDayAndMonth($Clean['IssueDate'])) > strtotime(DateProcessing::ToggleDateDayAndMonth($Clean['EndDate'])))
    {	

    	$Response->SetError(1);
		$Response->SetErrorCode(0);
		$Response->SetMessage('Issue date can not be less than end date.');

		echo json_encode($Response->GetResponseAsArray());
		exit;
    }

    if ($Clean['IsDraft'] != 0 && $Clean['IsDraft'] != 1) 
    {
    	$Response->SetError(1);
		$Response->SetErrorCode(0);
		$Response->SetMessage('Is Draft should be interger.');

		echo json_encode($Response->GetResponseAsArray());
		exit;# code...
    }

	$FileName = '';
    $FileExtension = '';

    if (count($Clean['UploadFile']) > 0 && $Clean['UploadFile']['error'] != 4) 
    {
        if ($Clean['UploadFile']['size'] > MAX_UPLOADED_FILE_SIZE || $Clean['UploadFile']['size'] <= 0) 
        {	
        	$Response->SetError(1);
			$Response->SetErrorCode(0);
			$Response->SetMessage('File size cannot be greater than ' . (MAX_UPLOADED_FILE_SIZE / 1024 /1024) . ' MB.');

			echo json_encode($Response->GetResponseAsArray());
			exit;
        }

        $FileExtension = strtolower(pathinfo($Clean['UploadFile']['name'], PATHINFO_EXTENSION));

        if (!in_array($Clean['UploadFile']['type'], $acceptable_mime_types) || !in_array($FileExtension, $acceptable_extensions))
        {	
        	if ($Clean['UploadFile']['size'] > MAX_UPLOADED_FILE_SIZE || $Clean['UploadFile']['size'] <= 0) 
	        {	
	        	$Response->SetError(1);
				$Response->SetErrorCode(0);
				$Response->SetMessage('File size cannot be greater than ' . (MAX_UPLOADED_FILE_SIZE / 1024 /1024) . ' MB.');

				echo json_encode($Response->GetResponseAsArray());
				exit;
	        }
        }

        $FileName = $Clean['UploadFile']['name'];
    }

	if ($Clean['AssignmentID'] > 0) 
	{
		$CurrentStudentAssignment = new StudentAssignment($Clean['AssignmentID']);
	}
	else
	{	
		$CurrentStudentAssignment = new StudentAssignment();
		$CurrentStudentAssignment->SetIsActive(1);
	}

	$CurrentStudentAssignment->SetClassSectionID($Clean['ClassSectionID']);
	$CurrentStudentAssignment->SetChapterTopicID($Clean['ChapterTopicID']);

    $CurrentStudentAssignment->SetAssignmentHeading($Clean['AssignmentHeading']);
	$CurrentStudentAssignment->SetAssignment($Clean['Assignment']);

    $CurrentStudentAssignment->SetIssueDate(date('Y-m-d', strtotime(DateProcessing::ToggleDateDayAndMonth(($Clean['IssueDate'])))));
    $CurrentStudentAssignment->SetEndDate(date('Y-m-d', strtotime(DateProcessing::ToggleDateDayAndMonth(($Clean['EndDate'])))));

    $CurrentStudentAssignment->SetIsDraft($Clean['IsDraft']);

	$CurrentStudentAssignment->SetCreateUserID($LoggedInBranchStaff->GetUserID());

	if (!$CurrentStudentAssignment->Save())
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
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
$Filters = array();

$Clean['SchoolCode'] = '';
$Clean['UniqueToken'] = md5(uniqid(rand(), true));

//	Other Variables
$Clean['ClassID'] = 0;
$Clean['ClassSectionID'] = 0;
$Clean['ClassSubjectID'] = 0;
$Clean['ChapterID'] = 0;

$Clean['AssignmentHeading'] = '';

$Clean['IssueDate'] = '';
$Clean['EndDate'] = '';
$Clean['IsDraft'] = 0;

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

if (isset($_REQUEST['AssignmentHeading']))
{
	$Clean['AssignmentHeading'] = strip_tags(trim((string) $_REQUEST['AssignmentHeading']));
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

try
{
	$LoggedInBranchStaff = new AppBranchStaff($Clean['UniqueToken']);

	$AllClasses = array();
	$AllClasses = $LoggedInBranchStaff->GetTeacherApplicableClasses(false);

	$RecordValidator = new Validator();

	if ($Clean['ClassID'] != 0) 
	{
		if (!$RecordValidator->ValidateInSelect($Clean['ClassID'], $AllClasses, 'Unknown error, please try again.')) 
		{
			$Response->SetError(1);
			$Response->SetErrorCode(UNKNOWN_ERROR);
			$Response->SetMessage(ProcessAppErrors(UNKNOWN_ERROR));

			echo json_encode($Response->GetResponseAsArray());
			exit;
		}
	}


	if ($Clean['ClassID'] != 0) 
	{
		// getting sections of class
		$ClassSectionsList = array();
		$ClassSectionsList = ClassSections::GetClassSections($Clean['ClassID']);

		if ($Clean['ClassSectionID'] != 0) 
		{
			if (!$RecordValidator->ValidateInSelect($Clean['ClassSectionID'], $ClassSectionsList, 'Unknown error, please try again.')) 
			{
				$Response->SetError(1);
				$Response->SetErrorCode(UNKNOWN_ERROR);
				$Response->SetMessage(ProcessAppErrors(UNKNOWN_ERROR));

				echo json_encode($Response->GetResponseAsArray());
				exit;
			}
		}

		$SelectedAddedClass = New AddedClass($Clean['ClassID']);
		// getting subjects of class
		$SelectedAddedClass->FillAssignedSubjects();

		$ClassSubjectsList = array();
		$ClassSubjectsList = $SelectedAddedClass->GetAssignedSubjects();

		if ($Clean['ClassSubjectID'] != 0) 
		{
			if (!$RecordValidator->ValidateInSelect($Clean['ClassSubjectID'], $ClassSubjectsList, 'Unknown error, please try again.')) 
			{
				$Response->SetError(1);
				$Response->SetErrorCode(UNKNOWN_ERROR);
				$Response->SetMessage(ProcessAppErrors(UNKNOWN_ERROR));

				echo json_encode($Response->GetResponseAsArray());
				exit;
			}
		}	
	}
	
	if ($Clean['ClassSubjectID'] != 0) 
	{
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
	}

	if ($Clean['AssignmentHeading'] != '') 
	{
		if (!$RecordValidator->ValidateStrings($Clean['AssignmentHeading'], 'Assignment heading is required and should be between 1 and 150 characters.', 1, 150)) 
		{
			$Response->SetError(1);
			$Response->SetErrorCode(0);
			$Response->SetMessage('Assignment heading is required and should be between 1 and 150 characters.');

			echo json_encode($Response->GetResponseAsArray());
			exit;
		}
	}

	if ($Clean['IssueDate'] != '') 
	{
		if (!$RecordValidator->ValidateDate($Clean['IssueDate'], 'Please enter a valid issue date.')) 
		{
			$Response->SetError(1);
			$Response->SetErrorCode(0);
			$Response->SetMessage('Please enter a valid issue date.');

			echo json_encode($Response->GetResponseAsArray());
			exit;
		}
	}

	if ($Clean['EndDate'] != '') 
	{
		if (!$RecordValidator->ValidateDate($Clean['EndDate'], 'Please enter a valid end date.')) 
		{
			$Response->SetError(1);
			$Response->SetErrorCode(0);
			$Response->SetMessage('Please enter a valid end date.');

			echo json_encode($Response->GetResponseAsArray());
			exit;
		}
	}

	if ($Clean['IssueDate'] != '' && $Clean['EndDate'] != '') 
	{
		if (strtotime(DateProcessing::ToggleDateDayAndMonth($Clean['IssueDate'])) > strtotime(DateProcessing::ToggleDateDayAndMonth($Clean['EndDate'])))
	    {	

	    	$Response->SetError(1);
			$Response->SetErrorCode(0);
			$Response->SetMessage('Issue date can not be less than end date.');

			echo json_encode($Response->GetResponseAsArray());
			exit;
	    }
	}

	if ($Clean['IsDraft'] != 0 && $Clean['IsDraft'] != 1) 
    {
    	$Response->SetError(1);
		$Response->SetErrorCode(0);
		$Response->SetMessage('Is Draft should be interger.');

		echo json_encode($Response->GetResponseAsArray());
		exit;    
	}

	//set record filters
    $Filters['ClassID'] = $Clean['ClassID'];
    $Filters['ClassSubjectID'] = $Clean['ClassSubjectID'];
    $Filters['ChapterID'] = $Clean['ChapterID'];
    $Filters['DraftStatus'] = $Clean['IsDraft'];
    $Filters['AssignmentHeading'] = $Clean['AssignmentHeading'];

    if ($Clean['IssueDate'] != '') 
    {
        $Filters['IssueDate'] = date('Y-m-d', strtotime(DateProcessing::ToggleDateDayAndMonth(($Clean['IssueDate']))));
    }

    if ($Clean['EndDate'] != '') 
    {
        $Filters['EndDate'] = date('Y-m-d', strtotime(DateProcessing::ToggleDateDayAndMonth(($Clean['EndDate']))));
    }

    // paging and sorting variables start here  //
	$TotalPages = 0;

	$Start = 0;
	$Limit = GLOBAL_SITE_PAGGING;

	$AssignmentsList = array();
	// end of paging variables//
    //get records count
    StudentAssignment::SearchAssignments($TotalRecords, true, $Filters);

    if ($TotalRecords > 0)
    {
        $AssignmentsList = StudentAssignment::SearchAssignments($TotalRecords, false, $Filters, $Start, $Limit);
    }

	$Response->PushData('student_assignment', $AssignmentsList);
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
<?php
error_log(json_encode($_REQUEST));
header("Content-Type:application/json");

require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.users.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.validation.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.authentication.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.global_settings.php');

require_once($_SERVER['DOCUMENT_ROOT'] . '/includes/global_defaults.inc.php');

require_once($_SERVER['DOCUMENT_ROOT'] . '/mobile_app_services/classes/class.json_response.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/mobile_app_services/includes/global_defaults.php');

require_once($_SERVER['DOCUMENT_ROOT'] . '/mobile_app_services/includes/process_errors.php');

//	Other Required Classes
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/school_administration/class.academic_year_months.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/school_administration/class.classes.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/school_administration/class.parent_details.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/academic_supervision/class.student_assignment.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/mobile_app_services/classes/class.app.parent_details.php');

$Clean = array();

$Response = new JSONResponse();

$Clean['Token'] = '';
$Clean['ClassSubjectID'] = 0;
$Clean['StudentID'] = 0;

$ClassID = 0;
$ClassSectionID = 0;
$ClassSubjectsList = array();
$ChapterStatusDetails = array();

if (isset($_REQUEST['Token']))
{
	$Clean['Token'] = strip_tags(trim((string) $_REQUEST['Token']));
}

if (isset($_REQUEST['StudentID']))
{
	$Clean['StudentID'] = (int) $_REQUEST['StudentID'];
}   

if (isset($_REQUEST['ClassSubjectID']))
{
	$Clean['ClassSubjectID'] = (int) $_REQUEST['ClassSubjectID'];
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
	
	$ClassID = $LoggedInParent->GetUserInfo()['ClassID'];
	$ClassSectionID = $LoggedInParent->GetUserInfo()['ClassSectionID'];

	$SelectedAddedClass = New AddedClass($LoggedInParent->GetClassID());
	$SelectedAddedClass->FillAssignedSubjects();

	$ClassSubjectsList = $SelectedAddedClass->GetAssignedSubjects();

	$RecordValidator = new Validator();

	if (!$RecordValidator->ValidateInSelect($Clean['ClassSubjectID'], $ClassSubjectsList, 'Unknown error, please try again.')) 
	{
		$Response->SetError(1);
		$Response->SetErrorCode(UNKNOWN_ERROR);
		$Response->SetMessage(ProcessAppErrors(UNKNOWN_ERROR));

		echo json_encode($Response->GetResponseAsArray());
		exit;
	}

	$ChapterStatusDetails = $LoggedInParent->GetStudentChapterStatusDetails($LoggedInParent->GetClassSectionID(), $Clean['ClassSubjectID']);
    
    $TopicData = array();
	$Counter = 0;

	foreach ($ChapterStatusDetails as $ChapterName => $TopicDetails) 
	{
		$TopicData[$Counter]['ChapterName'] = $ChapterName;
		$Count = 0;
		foreach ($TopicDetails as $ChapterTopicID => $Details) 
		{
			$TopicData[$Counter]['TopicDetails'][$Count] = $Details;	
			$Count++;
		}
		$Counter++;
	}	
	
	$Response->PushData('ChapterStatusDetails', $TopicData);
	// $Response->SetData($ChapterStatusDetails);
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
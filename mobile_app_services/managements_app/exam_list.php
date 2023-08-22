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

require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/examination/class.exam_types.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/examination/class.exams.php');

require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/school_administration/class.branch_staff.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/mobile_app_services/classes/class.app.branch_staff.php');

$Response = new JSONResponse();

$AllAchievementsList= array();

$Clean['SchoolCode'] = '';
$Clean['Token'] = md5(uniqid(rand(), true));

//	Other Variables
$Clean['ClassID'] = 0;
$Clean['ClassSectionID'] = 0;
$Clean['ClassSubjectID'] = 0;
$Clean['ExamTypeID'] = 0;

if (isset($_REQUEST['SchoolCode']))
{
	$Clean['SchoolCode'] = strip_tags(trim((string) $_REQUEST['SchoolCode']));
}

if (isset($_REQUEST['Token']))
{
	$Clean['Token'] = strip_tags(trim((string) $_REQUEST['Token']));
}

if (isset($_REQUEST['ClassSectionID']))
{
	$Clean['ClassSectionID'] = (int) $_REQUEST['ClassSectionID'];
}

if (isset($_REQUEST['ClassSubjectID']))
{
	$Clean['ClassSubjectID'] = (int) $_REQUEST['ClassSubjectID'];
}

if (isset($_REQUEST['ExamTypeID']))
{
	$Clean['ExamTypeID'] = (int) $_REQUEST['ExamTypeID'];
}

try
{
	$LoggedInBranchStaff = new AppBranchStaff($Clean['Token']);

	$ClassSectionList = $LoggedInBranchStaff->GetApplicableClassSections();

	$RecordValidator = new Validator();

	if (!$RecordValidator->ValidateInSelect($Clean['ClassSectionID'], $ClassSectionList, 'Unknown error, please try again.')) 
	{
		$Response->SetError(1);
		$Response->SetErrorCode(UNKNOWN_ERROR);
		$Response->SetMessage(ProcessAppErrors(UNKNOWN_ERROR));

		echo json_encode($Response->GetResponseAsArray());
		exit;
	}

	$ClassSectionObject = new ClassSections($Clean['ClassSectionID']);

	$Clean['ClassID'] = $ClassSectionObject->GetClassID();

	$SelectedAddedClass = New AddedClass($Clean['ClassID']);
    $SelectedAddedClass->FillAssignedSubjects();

    $ClassSubjectList = $SelectedAddedClass->GetAssignedSubjects();

	if (!$RecordValidator->ValidateInSelect($Clean['ClassSubjectID'], $ClassSubjectList, 'Unknown error, please try again.')) 
	{
		$Response->SetError(1);
		$Response->SetErrorCode(UNKNOWN_ERROR);
		$Response->SetMessage(ProcessAppErrors(UNKNOWN_ERROR));

		echo json_encode($Response->GetResponseAsArray());
		exit;
	}

	$ActiveExamTypes = ExamType::GetActiveExamTypes();

	if (!$RecordValidator->ValidateInSelect($Clean['ExamTypeID'], $ActiveExamTypes, 'Unknown error, please try again.')) 
	{
		$Response->SetError(1);
		$Response->SetErrorCode(UNKNOWN_ERROR);
		$Response->SetMessage(ProcessAppErrors(UNKNOWN_ERROR));

		echo json_encode($Response->GetResponseAsArray());
		exit;
	}

	$Filters['ClassID'] = $Clean['ClassID'];
    $Filters['ClassSectionID'] = $Clean['ClassSectionID'];
    $Filters['ClassSubjectID'] = $Clean['ClassSubjectID'];
    $Filters['ExamTypeID'] = $Clean['ExamTypeID'];   

    $ExamList = array();
    $Data = array();

    Exam::SearchExams($TotalRecords, true, $Filters);

    if ($TotalRecords > 0)
    {
    	$ExamList = Exam::SearchExams($TotalRecords, false, $Filters);

    	foreach ($ExamList as $ExamID => $Details) 
	    {
	    	$Details['ExamID'] = $ExamID;

	    	$Data[$ExamID] = $Details;
	    }
    }

	$Response->PushData('ExamList', $Data);
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
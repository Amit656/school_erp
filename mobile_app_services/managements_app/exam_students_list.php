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
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/school_administration/class.students.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/school_administration/class.student_details.php');

require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/examination/class.exam_types.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/examination/class.exams.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/examination/class.student_exam_mark.php');

require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/school_administration/class.branch_staff.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/mobile_app_services/classes/class.app.branch_staff.php');

$Response = new JSONResponse();

$AllAchievementsList= array();

$Clean['SchoolCode'] = '';
$Clean['Token'] = md5(uniqid(rand(), true));

//	Other Variables
$Clean['ExamID'] = 0;

if (isset($_REQUEST['SchoolCode']))
{
	$Clean['SchoolCode'] = strip_tags(trim((string) $_REQUEST['SchoolCode']));
}

if (isset($_REQUEST['Token']))
{
	$Clean['Token'] = strip_tags(trim((string) $_REQUEST['Token']));
}

if (isset($_REQUEST['ExamID']))
{
	$Clean['ExamID'] = (int) $_REQUEST['ExamID'];
}

try
{
	$LoggedInBranchStaff = new AppBranchStaff($Clean['Token']);

	$CurrentExam = new Exam($Clean['ExamID']);

	$ClassSectionList = $LoggedInBranchStaff->GetApplicableClassSections();

	$RecordValidator = new Validator();

	if (!$RecordValidator->ValidateInSelect($CurrentExam->GetClassSectionID(), $ClassSectionList, 'Unknown error, please try again.')) 
	{
		$Response->SetError(1);
		$Response->SetErrorCode(UNKNOWN_ERROR);
		$Response->SetMessage(ProcessAppErrors(UNKNOWN_ERROR));

		echo json_encode($Response->GetResponseAsArray());
		exit;
	}

	$StudentsList = array();
	$StudentsList = StudentDetail::GetStudentsByClassSectionID($CurrentExam->GetClassSectionID(), 'Active');

	$StudentObtainedmarks = array();
	$Obtainedmarks = array();
	$Obtainedmarks = StudentExamMark::FillObtainedMarks($Clean['ExamID']);

	foreach ($Obtainedmarks as $StudentID => $Marks) 
	{
		$StudentObtainedmarks[$StudentID]['StudentID'] = $StudentID;
		$StudentObtainedmarks[$StudentID]['Obtainedmark'] = $Marks;
	}

	$Response->PushData('StudentsList', $StudentsList);
	$Response->PushData('Obtainedmarks', $StudentObtainedmarks);
	$Response->PushData('MaximumMarks', $CurrentExam->GetMaximumMarks());
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
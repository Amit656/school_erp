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
$Clean['Token'] = '';

//	Other Variables
$Clean['ExamID'] = 0;
$Clean['StudentIdList'] = array();
$Clean['Obtainedmarks'] = array();

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

if (isset($_REQUEST['StudentIdList']))
{
	$Clean['StudentIdList'] = json_decode($_REQUEST['StudentIdList']);
}

if (isset($_REQUEST['ObtainedMarks']))
{
	$Clean['Obtainedmarks'] = json_decode($_REQUEST['ObtainedMarks']);
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

	if (count($Clean['StudentIdList']) <= 0 || count($Clean['Obtainedmarks']) <= 0) 
    {
        $Response->SetError(1);
		$Response->SetErrorCode(UNKNOWN_ERROR);
		$Response->SetMessage(ProcessAppErrors(UNKNOWN_ERROR));

		echo json_encode($Response->GetResponseAsArray());
		exit;
    }
    
    if (count($Clean['StudentIdList']) != count($Clean['Obtainedmarks'])) 
    {
        $Response->SetError(1);
		$Response->SetErrorCode(UNKNOWN_ERROR);
		$Response->SetMessage(ProcessAppErrors(UNKNOWN_ERROR));

		echo json_encode($Response->GetResponseAsArray());
		exit;
    }

    $StudentsList = array();
    $StudentsList = StudentDetail::GetStudentsByClassSectionID($CurrentExam->GetClassSectionID(), 'Active');

    foreach ($Clean['StudentIdList'] as $Key => $StudentID) 
    {
		if (!array_key_exists($StudentID, $StudentsList))
		{
			$Response->SetError(1);
			$Response->SetErrorCode(UNKNOWN_ERROR);
			$Response->SetMessage(ProcessAppErrors(UNKNOWN_ERROR));

			echo json_encode($Response->GetResponseAsArray());
			exit;
		}
		
		$Marks = (float) $Clean['Obtainedmarks'][$Key];
		
        /*if ($RecordValidator->ValidateInteger($Marks, 'Marks is required and should be integer.', 0)) 
		{
			$Response->SetError(1);
			$Response->SetErrorCode(UNKNOWN_ERROR);
			$Response->SetMessage(ProcessAppErrors(UNKNOWN_ERROR));

			echo json_encode($Response->GetResponseAsArray());
			exit;
		}*/
		
		$Obtainedmarks[$StudentID] = $Marks;
    }

	$NewStudentExamMark = new StudentExamMark();
			
	$NewStudentExamMark->SetExamID($Clean['ExamID']);
	$NewStudentExamMark->SetObtainedmarks($Obtainedmarks);

	$NewStudentExamMark->SetCreateUserID($LoggedInBranchStaff->GetUserID());

	if (!$NewStudentExamMark->Save())
	{
		$Response->SetError(1);
		$Response->SetErrorCode(UNKNOWN_ERROR);
		$Response->SetMessage(ProcessAppErrors(UNKNOWN_ERROR));

		echo json_encode($Response->GetResponseAsArray());
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
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

require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/school_administration/class.students.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/school_administration/class.student_details.php');

require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/academic_supervision/class.achievements_master.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/academic_supervision/class.achievements_students.php');

require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/school_administration/class.branch_staff.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/mobile_app_services/classes/class.app.branch_staff.php');

$Response = new JSONResponse();

$Clean = array();

$Clean['SchoolCode'] = '';
$Clean['UniqueToken'] = md5(uniqid(rand(), true));

//	Other Variables
$Clean['AchievementsStudentID'] = 0;
$Clean['ClassSectionID'] = 0;
$Clean['AchievementID'] = 0;
$Clean['StudentIDList'] = array();
$Clean['RemoveStudentIDList'] = array();

if (isset($_REQUEST['SchoolCode']))
{
	$Clean['SchoolCode'] = strip_tags(trim((string) $_REQUEST['SchoolCode']));
}

if (isset($_REQUEST['Token']))
{
	$Clean['UniqueToken'] = strip_tags(trim((string) $_REQUEST['Token']));
}

if (isset($_REQUEST['AchievementsStudentID']))
{
	$Clean['AchievementsStudentID'] = (int) $_REQUEST['AchievementsStudentID'];
}

if (isset($_REQUEST['ClassSectionID']))
{
	$Clean['ClassSectionID'] = (int) $_REQUEST['ClassSectionID'];
}

if (isset($_REQUEST['AchievementID'])) 
{
	$Clean['AchievementID'] = (int) $_REQUEST['AchievementID'];
}

if (isset($_REQUEST['StudentIDList']) && is_aray($_REQUEST['StudentIDList']))
{
	$Clean['StudentIDList'] = $_REQUEST['StudentIDList'];
}

try
{
	$LoggedInBranchStaff = new AppBranchStaff($Clean['UniqueToken']);

	$AllClasses = array();
	$AllClasses = $LoggedInBranchStaff->GetTeacherApplicableClasses(false);

	$RecordValidator = new Validator();

	if ($LoggedInBranchStaff->IsClassSectionValid($Clean['ClassSectionID'])) 
	{
		$Response->SetError(1);
		$Response->SetErrorCode(UNKNOWN_ERROR);
		$Response->SetMessage(ProcessAppErrors(UNKNOWN_ERROR));

		echo json_encode($Response->GetResponseAsArray());
		exit;
	}

	$StudentsList = array();
	$StudentsList = StudentDetail::GetStudentsByClassSectionID($Clean['ClassSectionID']);

	if (count($Clean['StudentIDList']) <= 0) 
    {
        $Response->SetError(1);
		$Response->SetErrorCode(0);
		$Response->SetMessage('please select atleast a student.');

		echo json_encode($Response->GetResponseAsArray());
		exit;
    }

	$ErrorCounter = 0;

	foreach ($Clean['StudentIDList'] as $StudentID) 
	{
		if (!$RecordValidator->ValidateInSelect($StudentID, $StudentsList, 'Unknown error, please try again.')) 
		{	
			$ErrorCounter = 1;
		}
	}

	if ($ErrorCounter) 
	{
		$Response->SetError(1);
		$Response->SetErrorCode(UNKNOWN_ERROR);
		$Response->SetMessage(ProcessAppErrors(UNKNOWN_ERROR));

		echo json_encode($Response->GetResponseAsArray());
		exit;
	}

	$StudentsList = StudentDetail::GetStudentsByClassSectionID($Clean['ClassSectionID']);

	$AllreadyPresentStudents = AchievementsStudent::FillAchievementsRecordsToStudent($Clean['AchievementID'], $Clean['ClassSectionID']);

	if (is_array($AllreadyPresentStudents) && count($AllreadyPresentStudents) > 0) 
	{
		foreach ($AllreadyPresentStudents as $AchievementsStudentID => $StudentID)
		{
		    if (!in_array($StudentID, $Clean['StudentIDList'])) 
		    {
		        $Clean['RemoveStudentIDList'][$AchievementsStudentID] = $StudentID;
		    }
		}
	}

	$ArrayToBeSaved = array();
	$ArrayToBeSaved = array_diff($Clean['StudentIDList'], $AllreadyPresentStudents);

	if ($Clean['AchievementsStudentID'] > 0)
	{
		$CurrentAchievementsStudent = new AchievementsStudent($Clean['AchievementsStudentID']);
	}
	else
	{	
		$CurrentAchievementsStudent = new AchievementsStudent();
		$CurrentAchievementsStudent->SetIsActive(1);
	}

	$CurrentAchievementsStudent->SetAchievementID($Clean['AchievementID']);
    $CurrentAchievementsStudent->SetStudentIDList($ArrayToBeSaved);
    $CurrentAchievementsStudent->SetRemoveStudentIDList($Clean['RemoveStudentIDList']);

	$CurrentAchievementsStudent->SetCreateUserID($LoggedInBranchStaff->GetUserID());

	if (!$CurrentAchievementsStudent->Save())
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
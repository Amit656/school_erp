<?php
// error_log(json_encode($_REQUEST));
header("Content-Type:application/json");

require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.users.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.authentication.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.helpers.php');

require_once($_SERVER['DOCUMENT_ROOT'] . '/mobile_app_services/classes/class.json_response.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/mobile_app_services/includes/global_defaults.php');

require_once($_SERVER['DOCUMENT_ROOT'] . '/mobile_app_services/includes/process_errors.php');

//	Other Required Classes
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/school_administration/class.students.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/school_administration/class.student_details.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/school_administration/class.class_attendence.php');

require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.sms_queue.php');

require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/school_administration/class.branch_staff.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/mobile_app_services/classes/class.app.branch_staff.php');

$Response = new JSONResponse();

$Clean = array();

$Clean['SchoolCode'] = '';
$Clean['UniqueToken'] = md5(uniqid(rand(), true));

//	Other Variables
$Clean['ClassSectionID'] = 0;
$Clean['AbsentStudents'] = array();

if (isset($_REQUEST['SchoolCode']))
{
	$Clean['SchoolCode'] = strip_tags(trim((string) $_REQUEST['SchoolCode']));
}

if (isset($_REQUEST['Token']))
{
	$Clean['UniqueToken'] = strip_tags(trim((string) $_REQUEST['Token']));
}

if (isset($_REQUEST['ClassSectionID']))
{
	$Clean['ClassSectionID'] = (int) $_REQUEST['ClassSectionID'];
}

if (isset($_REQUEST['AbsentStudents']))
{
	$Clean['AbsentStudents'] = json_decode($_REQUEST['AbsentStudents']);
}

try
{
	$LoggedInBranchStaff = new AppBranchStaff($Clean['UniqueToken']);

	/*if (!$LoggedInBranchStaff->IsClassSectionValid($Clean['ClassSectionID'])) 
	{
		$Response->SetError(1);
		$Response->SetErrorCode(UNKNOWN_ERROR);
		$Response->SetMessage(ProcessAppErrors(UNKNOWN_ERROR));

		echo json_encode($Response->GetResponseAsArray());
		exit;
	}*/

	$StudentsList = StudentDetail::GetStudentsByClassSectionID($Clean['ClassSectionID'], 'Active');

	if (is_array($Clean['AbsentStudents']) && count($Clean['AbsentStudents']) > 0) 
	{
		foreach ($Clean['AbsentStudents'] as $Key => $StudentID)
		{
			if (!array_key_exists($StudentID, $StudentsList)) 
			{
				$Response->SetError(1);
        		$Response->SetErrorCode(UNKNOWN_ERROR);
        		$Response->SetMessage(ProcessAppErrors(UNKNOWN_ERROR));
        
        		echo json_encode($Response->GetResponseAsArray());
        		exit;
			}
		}
	}

	$AttendenceDate = date('Y-m-d');

	$StartDate = '';	

    $StartDate = Helpers::GetStartDateOfTheMonth(date('M', strtotime($AttendenceDate)));

    $WorkingDayStartDate = $StartDate;
    $WorkingDayEndDate = date('Y-m-t', strtotime($AttendenceDate));

	if (!Helpers::GetIsClassAttendanceDateIsWorkingDate($WorkingDayStartDate, $WorkingDayEndDate, $Clean['ClassSectionID'], $AttendenceDate))
	{		
		$Response->SetError(1);
		$Response->SetErrorCode(NOT_A_WORKING_DAY);
		$Response->SetMessage(ProcessAppErrors(NOT_A_WORKING_DAY));

		echo json_encode($Response->GetResponseAsArray());
		exit;
	}
	
	$ClassAttendenceID = 0;

	if (ClassAttendence::IsAttendenceTaken($Clean['ClassSectionID'], $AttendenceDate, $ClassAttendenceID))
	{
		$CurrentClassAttendence = new ClassAttendence($ClassAttendenceID);
	}
	else
	{
		$CurrentClassAttendence = new ClassAttendence();
		$CurrentClassAttendence->SetCreateUserID($LoggedInBranchStaff->GetUserID());
	}
	
    $CurrentClassAttendence->SetAttendenceDate($AttendenceDate);
	$CurrentClassAttendence->SetClassSectionID($Clean['ClassSectionID']);
	$CurrentClassAttendence->SetAttendenceStatusAbsentStudentsList(array_flip($Clean['AbsentStudents']));

	if (!$CurrentClassAttendence->Save())
	{
		$Response->SetError(1);
		$Response->SetErrorCode(UNKNOWN_ERROR);
		$Response->SetMessage(ProcessAppErrors(UNKNOWN_ERROR));

		echo json_encode($Response->GetResponseAsArray());
		exit;
	}
	
	$MSGContent = 'Dear Parent, 
	Your ward ({StudentName}) is absent today.';
	
	if (count($Clean['AbsentStudents']) > 0)
	{
	    foreach ($Clean['AbsentStudents'] as $StudentID)
	    {
	        $CurrentStudent = new StudentDetail($StudentID);
	        
	        $MSG = str_replace('{StudentName}', $CurrentStudent->GetFirstName() . ' ' . $CurrentStudent->GetLastName(), $MSGContent);
	        
	        $NewSMSQueue = new SMSQueue();

			$NewSMSQueue->SetPhoneNumber($CurrentStudent->GetMobileNumber());
			$NewSMSQueue->SetSMSMessage($MSG);
			$NewSMSQueue->SetCreateUserID($LoggedInBranchStaff->GetUserID());

			$NewSMSQueue->Save();
	    }
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
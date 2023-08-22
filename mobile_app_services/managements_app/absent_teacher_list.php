<?php
header("Content-Type:application/json");

require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.users.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.validation.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.authentication.php');

require_once($_SERVER['DOCUMENT_ROOT'] . '/mobile_app_services/classes/class.json_response.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/mobile_app_services/includes/global_defaults.php');

require_once($_SERVER['DOCUMENT_ROOT'] . '/mobile_app_services/includes/process_errors.php');

//	Other Required Classes
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/school_administration/class.staff_attendence.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/school_administration/class.branch_staff.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/mobile_app_services/classes/class.app.branch_staff.php');

$Response = new JSONResponse();

$Clean = array();

$Clean['SchoolCode'] = '';
$Clean['UniqueToken'] = md5(uniqid(rand(), true));

if (isset($_REQUEST['SchoolCode']))
{
	$Clean['SchoolCode'] = strip_tags(trim((string) $_REQUEST['SchoolCode']));
}

if (isset($_REQUEST['Token']))
{
	$Clean['UniqueToken'] = strip_tags(trim((string) $_REQUEST['Token']));
}

try
{
	$LoggedInBranchStaff = new AppBranchStaff($Clean['UniqueToken']);
    
    if ($LoggedInBranchStaff->GetStaffCategory() != 'Management')
	{
		$Response->SetError(1);
		$Response->SetErrorCode(100025);
		$Response->SetMessage('Error: you are not a management staff.');

		echo json_encode($Response->GetResponseAsArray());
		exit;
	}
	
	$AbsentTeacherList = array();
	
	$BranchStaffList = array();
	$BranchStaffList = BranchStaff::GetActiveBranchStaff('Teaching');
	
	$AttendanceDate = date('Y-m-d');
// 	$AttendanceDate = '2018-04-03';
	
	if(!StaffAttendence::IsAttendenceTaken('Teaching', $AttendanceDate, $StaffAttendenceID))
	{
		$Response->SetError(1);
		$Response->SetErrorCode(100025);
		$Response->SetMessage('Attendance not marked.');

		echo json_encode($Response->GetResponseAsArray());
		exit;
	}
	else
	{
		$CurrentBranchStaffAttendence = new StaffAttendence($StaffAttendenceID);
		$CurrentBranchStaffAttendence->ViewStaffAttendenceStatus();

		$PresentStaffList = $CurrentBranchStaffAttendence->GetAttendenceStatusPresentStaffList();
	}
	
	foreach ($BranchStaffList as $BranchStaffID => $BranchStaffDetails)
	{
	    if (!array_key_exists($BranchStaffID, $PresentStaffList))
	    {
	        $AbsentTeacherList[$BranchStaffID]['BranchStaffID'] = $BranchStaffID;
	        $AbsentTeacherList[$BranchStaffID]['BranchStaffName'] = $BranchStaffDetails['FirstName'] . ' ' . $BranchStaffDetails['LastName'];
	    }
	}

	$Response->PushData('AbsentTeacherList', $AbsentTeacherList);
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
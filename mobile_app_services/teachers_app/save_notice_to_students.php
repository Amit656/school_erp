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
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/school_administration/class.notices_circulars.php');

require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/school_administration/class.branch_staff.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/mobile_app_services/classes/class.app.branch_staff.php');

$Response = new JSONResponse();

$Clean = array();

$Clean['SchoolCode'] = '';
$Clean['UniqueToken'] = md5(uniqid(rand(), true));

//	Other Variables
$Clean['NoticeCircularDate'] = date('Y-m-d');
$Clean['NoticeCircularSubject'] = '';
$Clean['NoticeCircularDetails'] = '';
$Clean['ApplicableFor'] = 'Class';
$Clean['Classes'] = array();

if (isset($_REQUEST['SchoolCode']))
{
	$Clean['SchoolCode'] = strip_tags(trim((string) $_REQUEST['SchoolCode']));
}

if (isset($_REQUEST['Token']))
{
	$Clean['UniqueToken'] = strip_tags(trim((string) $_REQUEST['Token']));
}

if (isset($_REQUEST['NoticeCircularSubject']))
{
	$Clean['NoticeCircularSubject'] = strip_tags(trim((string) $_REQUEST['NoticeCircularSubject']));
}

if (isset($_REQUEST['NoticeCircularDetails']))
{
	$Clean['NoticeCircularDetails'] = strip_tags(trim((string) $_REQUEST['NoticeCircularDetails']));
}

if (isset($_REQUEST['Classes']) && is_array($_REQUEST['Classes']))
{
	$Clean['Classes'] = $_REQUEST['Classes'];
}

try
{
	$LoggedInBranchStaff = new AppBranchStaff($Clean['UniqueToken']);

	$AllClasses = array();
	$AllClasses = $LoggedInBranchStaff->GetTeacherApplicableClasses(false);

	$RecordValidator = new Validator();

	if (!$RecordValidator->ValidateStrings($Clean['NoticeCircularSubject'], 'Notice subject is required and should be between 3 and 100 characters.', 3, 100)) 
	{
		$Response->SetError(1);
		$Response->SetErrorCode(0);
		$Response->SetMessage('Notice subject is required and should be between 3 and 100 characters.');

		echo json_encode($Response->GetResponseAsArray());
		exit;
	}
	
	$RecordValidator->ValidateStrings($Clean['NoticeCircularDetails'], 'Notice details is required and should be between 10 and characters characters.', 10, 2000);

	if (!$RecordValidator->ValidateStrings($Clean['NoticeCircularDetails'], 'Notice details is required and should be between 10 and characters characters.', 10, 2000)) 
	{
		$Response->SetError(1);
		$Response->SetErrorCode(0);
		$Response->SetMessage('Notice details is required and should be between 10 and characters characters.');

		echo json_encode($Response->GetResponseAsArray());
		exit;
	}
	$NoticeCircularApplicableFor = array();
	$ErrorCount = 0;
	if (count($Clean['Classes']) > 0) 
	{
		foreach ($Clean['Classes'] as $ClassID) 
		{
			if (!$RecordValidator->ValidateInSelect($ClassID, $AllClasses, 'Unknown Error, Please Try Again.')) 
			{	
				$ErrorCount = 1;
			}

			$NoticeCircularApplicableFor[$ClassID]['ApplicableFor'] = $Clean['ApplicableFor'];
			$NoticeCircularApplicableFor[$ClassID]['StaffOrClassID'] = $ClassID;
		}
	}

	if ($ErrorCount) 
	{
		$Response->SetError(1);
		$Response->SetErrorCode(UNKNOWN_ERROR);
		$Response->SetMessage(ProcessAppErrors(UNKNOWN_ERROR));

		echo json_encode($Response->GetResponseAsArray());
		exit;
	}

	$NewNoticeCircular = new NoticeCircular();
				
	$NewNoticeCircular->SetNoticeCircularDate($Clean['NoticeCircularDate']);
	$NewNoticeCircular->SetNoticeCircularSubject($Clean['NoticeCircularSubject']);
    $NewNoticeCircular->SetNoticeCircularDetails($Clean['NoticeCircularDetails']);
    
	$NewNoticeCircular->SetIsActive(1);

    $NewNoticeCircular->SetCreateUserID($LoggedInBranchStaff->GetUserID());
    
    $NewNoticeCircular->SetNoticeCircularApplicableFor($NoticeCircularApplicableFor);

	if (!$NewNoticeCircular->Save())
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
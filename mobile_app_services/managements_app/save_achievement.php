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
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/academic_supervision/class.achievements_master.php');

require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/school_administration/class.branch_staff.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/mobile_app_services/classes/class.app.branch_staff.php');

$Response = new JSONResponse();

$Clean = array();

$Clean['SchoolCode'] = '';
$Clean['UniqueToken'] = md5(uniqid(rand(), true));

//	Other Variables
$Clean['AchievementMasterID'] = 0;
$Clean['Achievement'] = '';
$Clean['AchievementDetails'] = '';

if (isset($_REQUEST['SchoolCode']))
{
	$Clean['SchoolCode'] = strip_tags(trim((string) $_REQUEST['SchoolCode']));
}

if (isset($_REQUEST['Token']))
{
	$Clean['UniqueToken'] = strip_tags(trim((string) $_REQUEST['Token']));
}

if (isset($_REQUEST['AchievementMasterID']))
{
	$Clean['AchievementMasterID'] = (int) $_REQUEST['AchievementMasterID'];
}

if (isset($_REQUEST['Achievement']))
{
	$Clean['Achievement'] = strip_tags(trim((string) $_REQUEST['Achievement']));
}

if (isset($_REQUEST['AchievementDetails']))
{
	$Clean['AchievementDetails'] = strip_tags(trim((string) $_REQUEST['AchievementDetails']));
}

try
{
	$LoggedInBranchStaff = new AppBranchStaff($Clean['UniqueToken']);

	$RecordValidator = new Validator();

	if (!$RecordValidator->ValidateStrings($Clean['Achievement'], 'Achievement is required and should be between 3 and 150 characters.', 3, 150)) 
	{
		$Response->SetError(1);
		$Response->SetErrorCode(0);
		$Response->SetMessage('Achievement is required and should be between 3 and 150 characterss.');

		echo json_encode($Response->GetResponseAsArray());
		exit;
	}

	if (!$RecordValidator->ValidateStrings($Clean['AchievementDetails'], 'Achievement details is required and should be between 5 and 500 characters.', 5, 500)) 
	{
		$Response->SetError(1);
		$Response->SetErrorCode(0);
		$Response->SetMessage('Achievement details is required and should be between 5 and 500 characters.');

		echo json_encode($Response->GetResponseAsArray());
		exit;
	}

	if ($Clean['AchievementMasterID'] > 0)
	{
		$CurrentAchievementMaster = new AchievementMaster($Clean['AchievementMasterID']);
	}
	else
	{	
		$CurrentAchievementMaster = new AchievementMaster();
		$CurrentAchievementMaster->SetIsActive(1);
	}
				
	$CurrentAchievementMaster->SetAchievement($Clean['Achievement']);
	$CurrentAchievementMaster->SetAchievementDetails($Clean['AchievementDetails']);

	$CurrentAchievementMaster->SetCreateUserID($LoggedInBranchStaff->GetUserID());

	if (!$CurrentAchievementMaster->Save())
	{

		$Response->SetError(1);
		$Response->SetErrorCode($CurrentAchievementMaster->GetLastErrorCode());
		$Response->SetMessage(ProcessErrors($CurrentAchievementMaster->GetLastErrorCode()));

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
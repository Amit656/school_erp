<?php
header("Content-Type:application/json");

require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.users.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.validation.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.authentication.php');

require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.fcm_push.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.fcm_firebase.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.fcm_send_notification.php');

require_once($_SERVER['DOCUMENT_ROOT'] . '/includes/global_defaults.inc.php');

require_once($_SERVER['DOCUMENT_ROOT'] . '/mobile_app_services/classes/class.json_response.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/mobile_app_services/includes/global_defaults.php');

require_once($_SERVER['DOCUMENT_ROOT'] . '/mobile_app_services/includes/process_errors.php');

//	Other Required Classes
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/school_administration/class.parent_details.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/mobile_app_services/classes/class.app.parent_details.php');

require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/school_administration/class.branch_staff.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/school_administration/class.chat_settings.php');

$Clean = array();

$Response = new JSONResponse();

$Clean['Token'] = '';

$Clean['StudentID'] = 0;
$Clean['BranchStaffID'] = 0;
$Clean['Message'] = '';

if (isset($_REQUEST['Token']))
{
	$Clean['Token'] = strip_tags(trim((string) $_REQUEST['Token']));
}

if (isset($_REQUEST['BranchStaffID']))
{
	$Clean['BranchStaffID'] = (int) $_REQUEST['BranchStaffID'];
}

if (isset($_REQUEST['StudentID']))
{
	$Clean['StudentID'] = (int) $_REQUEST['StudentID'];
}

if (isset($_REQUEST['Message']))
{
	$Clean['Message'] = strip_tags(trim($_REQUEST['Message']));
}

try
{
	$LoggedInParent = new AppParentDetail($Clean['Token']);
	
	$CurrentBranchStaff = new BranchStaff($Clean['BranchStaffID']);
	
	# Check weather the event gallery is active or not
	if (!ChatSettings::GetChatEnabledBetweenUsersOrNot('Parent', 'Teacher'))
	{
		$Response->SetError(1);
		$Response->SetErrorCode(CHAT_NOT_ENABLED);
		$Response->SetMessage(ProcessAppErrors(CHAT_NOT_ENABLED));
		
		echo json_encode($Response->GetResponseAsArray());
		exit;
	}
	
	$SenderType = 'Parent';
	$SenderID = $Clean['StudentID'];
	
	$ReceiverType = 'Teacher';
	$ReceiverID = $Clean['BranchStaffID'];
	
	FcmSendNotification::SendChatNotification($SenderType, $SenderID, $ReceiverType, $ReceiverID, $Clean['Message']);
	
	$Response->PushData('message_sent', $LoggedInParent->SendChatMessage($Clean['BranchStaffID'], $Clean['Message']));
	$Response->PushData('chat_enabled', ChatSettings::GetChatEnabledBetweenUsersOrNot('Parent', 'Teacher'));
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
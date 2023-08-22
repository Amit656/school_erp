<?php
error_log(json_encode($_REQUEST));
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
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/school_administration/class.branch_staff.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/mobile_app_services/classes/class.app.branch_staff.php');

require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/school_administration/class.chat_settings.php');

$Clean = array();

$Response = new JSONResponse();

$Clean['Token'] = '';

$Clean['RecordType'] = '';
$Clean['RecordID'] = 0;
$Clean['Message'] = '';

if (isset($_REQUEST['Token']))
{
	$Clean['Token'] = strip_tags(trim((string) $_REQUEST['Token']));
}

if (isset($_REQUEST['RecordType']))
{
	$Clean['RecordType'] = strip_tags(trim((string) $_REQUEST['RecordType']));
}

if (isset($_REQUEST['RecordID']))
{
	$Clean['RecordID'] = (int) $_REQUEST['RecordID'];
}

if (isset($_REQUEST['Message']))
{
	$Clean['Message'] = strip_tags(trim($_REQUEST['Message']));
}

try
{
	$LoggedInBranchStaff = new AppBranchStaff($Clean['Token']);
	
	if ($Clean['RecordID'] <= 0)
	{
		$Response->SetError(1);
		$Response->SetErrorCode(CHAT_NOT_ENABLED);
		$Response->SetMessage(ProcessAppErrors(CHAT_NOT_ENABLED));

		echo json_encode($Response->GetResponseAsArray());
		exit;
	}
	
	if ($Clean['RecordType'] == 'Parent')
	{
		$CurrentParent = new ParentDetail($Clean['RecordID']);
		
		if (!ChatSettings::GetChatEnabledBetweenUsersOrNot('Parent', 'Teacher'))
		{
			$Response->SetError(1);
			$Response->SetErrorCode(CHAT_NOT_ENABLED);
			$Response->SetMessage(ProcessAppErrors(CHAT_NOT_ENABLED));

			echo json_encode($Response->GetResponseAsArray());
			exit;
		}
	}
	else if ($Clean['RecordType'] == 'Teacher')
	{
		$CurrentBranchStaff = new BranchStaff($Clean['RecordID']);
		
		if (!ChatSettings::GetChatEnabledBetweenUsersOrNot('Teacher', 'Teacher'))
		{
			$Response->SetError(1);
			$Response->SetErrorCode(CHAT_NOT_ENABLED);
			$Response->SetMessage(ProcessAppErrors(CHAT_NOT_ENABLED));

			echo json_encode($Response->GetResponseAsArray());
			exit;
		}
	}
	else
	{
		$Response->SetError(1);
		$Response->SetErrorCode(UNKNOWN_ERROR);
		$Response->SetMessage(ProcessAppErrors(UNKNOWN_ERROR));

		echo json_encode($Response->GetResponseAsArray());
		exit;
	}
	
	$SenderType = 'Teacher';
	$SenderID = $LoggedInBranchStaff->GetBranchStaffID();
	
	$ReceiverType = $Clean['RecordType'];
	$ReceiverID = $Clean['RecordID'];
	
	FcmSendNotification::SendChatNotification($SenderType, $SenderID, $ReceiverType, $ReceiverID, $Clean['Message']);
	
	$Response->PushData('message_sent', $LoggedInBranchStaff->SendChatMessage($Clean['RecordType'], $Clean['RecordID'], $Clean['Message']));
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
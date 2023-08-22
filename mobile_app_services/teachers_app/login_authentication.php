<?php
error_log(json_encode($_REQUEST));
header("Content-Type:application/json");

require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.users.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.validation.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.authentication.php');

require_once($_SERVER['DOCUMENT_ROOT'] . '/includes/global_defaults.inc.php');

require_once($_SERVER['DOCUMENT_ROOT'] . '/mobile_app_services/includes/global_defaults.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/mobile_app_services/classes/class.json_response.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/mobile_app_services/classes/class.api_helpers.php');

require_once($_SERVER['DOCUMENT_ROOT'] . '/mobile_app_services/includes/process_errors.php');

$Response = new JSONResponse();

$Clean = array();

$Clean['UserName'] = '';
$Clean['Password'] = '';

$HasErrors = false;
$ExceptionErrorCode = 0;

if (isset($_REQUEST['UserName']))
{
	$Clean['UserName'] = strip_tags(trim((string) $_REQUEST['UserName']));
}

if (isset($_REQUEST['Password']))
{
	$Clean['Password'] = strip_tags(trim((string) $_REQUEST['Password']));
}

$RecordValidator = new Validator();

$RecordValidator->ValidateStrings($Clean['UserName'], 'User Name must be supplied and must be valid and between 4 and 150 chars', 4, 150);
$RecordValidator->ValidateStrings($Clean['Password'], 'Password must be supplied and must be between 4 and 12 chars', 4, 12);

if ($RecordValidator->HasNotifications())
{
	$Response->SetError(1);
	$Response->SetErrorCode(INVALID_USERNAME_PASSWORD);
	$Response->SetMessage(ProcessAppErrors(INVALID_USERNAME_PASSWORD));

	echo json_encode($Response->GetResponseAsArray());
	exit;
}

$UniqueToken = '';
$UniqueToken = APIHelpers::GetUniqueToken();

//1. CHECK IF THE USER IS VALID //
try
{
	$AuthObject = new ApplicationAuthentication;
	$AuthObject->UserName = $Clean['UserName'];
	$AuthObject->Password = $Clean['Password'];
	$AuthObject->AppLogin($UniqueToken, ROLE_SITE_FACULTY);
}

// THIS CATCH BLOCK BUBBLES THE EXCEPTION TO THE BUILT IN 'Exception' CLASS IF THERE ARE ANY UNCAUGHT ERRORS //
catch (ApplicationDBException $e)
{
	$ExceptionErrorCode = $e->getCode();
	return false;
}
catch (ApplicationAuthException $e)
{
	$ExceptionErrorCode = $e->getCode();
}
catch (Exception $e)
{
	$ExceptionErrorCode= APP_ERROR_UNDEFINED_ERROR;
}
// END OF 1. //

if ($ExceptionErrorCode === 0)
{
	$Response->SetMessage(ProcessAppMessages(LOGIN_SUCCESSFUL));
	$Response->PushData('UniqueToken', $UniqueToken);
}
else
{
	$Response->SetError(1);
	$Response->SetErrorCode($ExceptionErrorCode);
	$Response->SetMessage(ProcessErrors($ExceptionErrorCode));
}

echo json_encode($Response->GetResponseAsArray());
exit;
?>
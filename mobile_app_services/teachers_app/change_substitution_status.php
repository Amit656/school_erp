<?php
header("Content-Type:application/json");

require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.users.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.validation.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.authentication.php');

require_once($_SERVER['DOCUMENT_ROOT'] . '/mobile_app_services/classes/class.json_response.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/mobile_app_services/includes/global_defaults.php');

require_once($_SERVER['DOCUMENT_ROOT'] . '/mobile_app_services/includes/process_errors.php');

//	Other Required Classes
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/school_administration/class.branch_staff.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/mobile_app_services/classes/class.app.branch_staff.php');

$Clean = array();

$Response = new JSONResponse();

$Clean['Token'] = '';

$Clean['ClassSubstitutionID'] = 0;
$Clean['SubstitutionStatus'] = '';

if (isset($_REQUEST['Token']))
{
	$Clean['Token'] = strip_tags(trim((string) $_REQUEST['Token']));
}

if (isset($_REQUEST['ClassSubstitutionID']))
{
	$Clean['ClassSubstitutionID'] = (int) $_REQUEST['ClassSubstitutionID'];
}

if (isset($_REQUEST['SubstitutionStatus']))
{
	$Clean['SubstitutionStatus'] = strip_tags(trim((string) $_REQUEST['SubstitutionStatus']));
}

try
{
	$LoggedInBranchStaff = new AppBranchStaff($Clean['Token']);
	
	if ($Clean['SubstitutionStatus'] != 'Confirmed' && $Clean['SubstitutionStatus'] != 'Ignored')
	{
		$Response->SetError(1);
		$Response->SetErrorCode(UNKNOWN_ERROR);
		$Response->SetMessage(ProcessAppErrors(UNKNOWN_ERROR));

		echo json_encode($Response->GetResponseAsArray());
		exit;
	}

	$LoggedInBranchStaff->ChangeSubstitutionStatus($Clean['SubstitutionStatus'], $Clean['ClassSubstitutionID']);
	
	$Response->PushData('success', 'success');
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
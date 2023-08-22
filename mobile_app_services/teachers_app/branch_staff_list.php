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
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/school_administration/class.branch_staff.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/mobile_app_services/classes/class.app.branch_staff.php');

$Clean = array();

$Response = new JSONResponse();

$Clean['Token'] = '';

if (isset($_REQUEST['Token']))
{
	$Clean['Token'] = strip_tags(trim((string) $_REQUEST['Token']));
}

try
{
	$LoggedInBranchStaff = new AppBranchStaff($Clean['Token']);
	
	$BranchStaffDetails = array();
	
	$BranchStaffList = array();
	$BranchStaffList = $LoggedInBranchStaff->FetchBranchStaffForChat();
	
	foreach ($BranchStaffList as $BranchStaffID => $Details)
	{
	    $Details['Image'] = '';
	    
        if (file_exists(SITE_FS_PATH . '/site_images/branch_staff_images/' . $BranchStaffID . '/' . $Details['StaffPhoto'])) 
        {   
            $Details['Image'] = SITE_HTTP_PATH . '/site_images/branch_staff_images/' . $BranchStaffID . '/' . $Details['StaffPhoto'];
        }
        
        $BranchStaffDetails[$BranchStaffID] = $Details;
	}
	
	$Response->PushData('branch_staff_list', $BranchStaffDetails);

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
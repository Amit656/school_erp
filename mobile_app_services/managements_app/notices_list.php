<?php
header("Content-Type:application/json");

require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.users.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.validation.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.authentication.php');

require_once($_SERVER['DOCUMENT_ROOT'] . '/includes/global_defaults.inc.php');

require_once($_SERVER['DOCUMENT_ROOT'] . '/mobile_app_services/classes/class.json_response.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/mobile_app_services/includes/global_defaults.php');

require_once($_SERVER['DOCUMENT_ROOT'] . '/mobile_app_services/includes/process_errors.php');

//	Other Required Classes

require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/school_administration/class.notices_circulars.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/school_administration/class.branch_staff.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/mobile_app_services/classes/class.app.branch_staff.php');

$Clean = array();

$Response = new JSONResponse();

$Clean['UniqueToken'] = '';

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
	
	$AllNoticeCircularsList = array();
	
	$AllNoticeCirculars = array();
    $AllNoticeCirculars = NoticeCircular::NoticeCircularReports();
    
    foreach ($AllNoticeCirculars as $NoticeCircularID => $NoticeCircularDetails)
    {
        $AllNoticeCircularsList[$NoticeCircularID]['NoticeCircularID'] = $NoticeCircularID;
        $AllNoticeCircularsList[$NoticeCircularID]['NoticeCircularSubject'] = $NoticeCircularDetails['NoticeCircularSubject'];
        $AllNoticeCircularsList[$NoticeCircularID]['NoticeCircularDate'] = date('d/m/Y', strtotime($NoticeCircularDetails['NoticeCircularDate']));
        $AllNoticeCircularsList[$NoticeCircularID]['NoticeCircularDetails'] = $NoticeCircularDetails['NoticeCircularDetails'];
        $AllNoticeCircularsList[$NoticeCircularID]['ApplicableForClass'] = 'No';
        $AllNoticeCircularsList[$NoticeCircularID]['ApplicableForStaff'] = 'No';
        
        if ($NoticeCircularDetails['TotalClassApplicable'] > 0)
        {
            $AllNoticeCircularsList[$NoticeCircularID]['ApplicableForClass'] = 'Yes';
        }
        
        if ($NoticeCircularDetails['TotalStaffApplicable'] > 0)
        {
            $AllNoticeCircularsList[$NoticeCircularID]['ApplicableForStaff'] = 'Yes';
        }
    }
		
	$Response->PushData('NoticeList', $AllNoticeCircularsList);
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
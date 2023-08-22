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
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/school_administration/class.parent_details.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/mobile_app_services/classes/class.app.parent_details.php');

require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/school_administration/class.event_gallery.php');

$Clean = array();

$Response = new JSONResponse();

$Clean['Token'] = '';
$Clean['EventGalleryID'] = 0;

if (isset($_REQUEST['Token']))
{
	$Clean['Token'] = strip_tags(trim((string) $_REQUEST['Token']));
}
if (isset($_REQUEST['EventGalleryID']))
{
	$Clean['EventGalleryID'] = (int) $_REQUEST['EventGalleryID'];
}

if ($Clean['EventGalleryID'] <= 0)
{
	$Response->SetError(1);
	$Response->SetErrorCode(UNKNOWN_ERROR);
	$Response->SetMessage(ProcessAppErrors(UNKNOWN_ERROR));

	echo json_encode($Response->GetResponseAsArray());
	exit;
}

try
{
	$LoggedInParent = new AppParentDetail($Clean['Token']);
	
	$CurrentEventGallery = new EventGallery($Clean['EventGalleryID']);
	
	# Check weather the event gallery is active or not
	$Response->PushData('events_images', $CurrentEventGallery->GetEventGalleryImages());
	$Response->PushData('event_images_path', SITE_HTTP_PATH . '/site_images/');
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
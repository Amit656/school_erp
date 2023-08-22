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

$Clean['OperationType'] = 'ClockIN';
$Clean['Token'] = '';

$Clean['Latitude'] = '27.5688089';
$Clean['Longitude'] = '80.6671718';

$Clean['UserLatitude'] = '';
$Clean['UserLongitude'] = '';

if (isset($_REQUEST['OperationType']))
{
	$Clean['OperationType'] = strip_tags(trim((string) $_REQUEST['OperationType']));
}

if (isset($_REQUEST['Token']))
{
	$Clean['Token'] = strip_tags(trim((string) $_REQUEST['Token']));
}

if (isset($_REQUEST['UserLatitude']))
{
	$Clean['UserLatitude'] = strip_tags(trim((string) $_REQUEST['UserLatitude']));
}

if (isset($_REQUEST['UserLongitude']))
{
	$Clean['UserLongitude'] = strip_tags(trim((string) $_REQUEST['UserLongitude']));
}

try
{
	$LoggedInBranchStaff = new AppBranchStaff($Clean['Token']);

	if ( !getDistance( $Clean['Latitude'], $Clean['Longitude'], $Clean['UserLatitude'], $Clean['UserLongitude'], 60))
	{
		$Response->SetError(1);
		$Response->SetErrorCode(100025);
		$Response->SetMessage('Cannot clock in, may be you are not near to the school location.');

		echo json_encode($Response->GetResponseAsArray());
		exit;
	}

	if ($Clean['OperationType'] != 'ClockIN' && $Clean['OperationType'] != 'ClockOUT')
	{
		$Response->SetError(1);
		$Response->SetErrorCode(UNKNOWN_ERROR);
		$Response->SetMessage(ProcessAppErrors(UNKNOWN_ERROR));

		echo json_encode($Response->GetResponseAsArray());
		exit;
	}

	$LoggedInBranchStaff->MarkClockInOut($Clean['OperationType']);

	$Time = date('h:i A');

	$Response->PushData('operation_type' , $Clean['OperationType']);
	$Response->PushData('clock_in_out_time' ,$Time);
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

function getDistance( $latitude1, $longitude1, $latitude2, $longitude2, $radius)
{
	var_dump($latitude1);
	var_dump($longitude1);
	var_dump($latitude2);
	var_dump($longitude2);
	var_dump($radius);
    $earth_radius = 6371;

    $dLat = deg2rad( $latitude2 - $latitude1 );  
    $dLon = deg2rad( $longitude2 - $longitude1 );  

    $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($latitude1)) * cos(deg2rad($latitude2)) * sin($dLon/2) * sin($dLon/2);
    
    $c = 2 * asin(sqrt($a));  
    $d = $earth_radius * $c;  

    //return $d; 
    
    if ($d > $radius)
    {
        return false;
    }
    
    return true;
}
?>
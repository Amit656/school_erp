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

require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/school_administration/class.branch_staff.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/mobile_app_services/classes/class.app.branch_staff.php');

$Clean = array();

$Response = new JSONResponse();

$Clean['UniqueToken'] = '';
$Clean['BranchStaffID'] = 0;

if (isset($_REQUEST['Token']))
{
	$Clean['UniqueToken'] = strip_tags(trim((string) $_REQUEST['Token']));
}

if (isset($_REQUEST['BranchStaffID']))
{
	$Clean['BranchStaffID'] = (int) $_REQUEST['BranchStaffID'];
}

try
{
	$LoggedInBranchStaff = new AppBranchStaff($Clean['UniqueToken']);
	
	if (!array_key_exists($Clean['BranchStaffID'], BranchStaff::GetAllBranchStaff()))
	{
	    $Response->SetError(1);
    	$Response->SetErrorCode(UNKNOWN_ERROR);
    	$Response->SetMessage(ProcessAppErrors(UNKNOWN_ERROR));
    	
    	echo json_encode($Response->GetResponseAsArray());
    	exit;   
	}

	$DayList = array();

	$Counter = 0;
	for ($i = 0; $i < 7; $i++) 
	{
		$DayList[++$Counter] = jddayofweek($i, 1);
	}

	$TimeTable = $LoggedInBranchStaff->GetTimeTable($Clean['BranchStaffID']);

 	// for array format
	$TimeTableList = array();

	foreach ($DayList as $DayID => $DayName) 
	{	
		$TimeTableList[$DayName] = array();
		$Counter = 0;
		foreach ($TimeTable as $ClassTimeTableDetailID => $TimeTableDetail) 
		{	
			if ($DayID == $TimeTableDetail['DayID']) 
			{
				$TimeTableList[$DayName][$Counter]['ClassName'] = $TimeTableDetail['ClassName'];
				$TimeTableList[$DayName][$Counter]['ClassSymbol'] = $TimeTableDetail['ClassSymbol'];
				$TimeTableList[$DayName][$Counter]['SectionName'] = $TimeTableDetail['SectionName'];
				$TimeTableList[$DayName][$Counter]['PeriodName'] = $TimeTableDetail['PeriodName'];
				$TimeTableList[$DayName][$Counter]['Subject'] = $TimeTableDetail['Subject'];
				$TimeTableList[$DayName][$Counter]['PeriodStartTime'] = date("g:i a", strtotime($TimeTableDetail['PeriodStartTime']));
				$TimeTableList[$DayName][$Counter]['PeriodEndTime'] = date("g:i a", strtotime($TimeTableDetail['PeriodEndTime']));
				$TimeTableList[$DayName][$Counter]['DayName'] = $DayName;

				$Counter++;
			}
		}
	}		

	$Response->SetDataOnKey('TimeTable', $TimeTableList);
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
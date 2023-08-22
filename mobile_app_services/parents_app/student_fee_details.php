<?php
header("Content-Type:application/json");

require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.users.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.validation.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.authentication.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.global_settings.php');

require_once($_SERVER['DOCUMENT_ROOT'] . '/includes/global_defaults.inc.php');

require_once($_SERVER['DOCUMENT_ROOT'] . '/mobile_app_services/classes/class.json_response.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/mobile_app_services/includes/global_defaults.php');

require_once($_SERVER['DOCUMENT_ROOT'] . '/mobile_app_services/includes/process_errors.php');

//	Other Required Classes
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/school_administration/class.academic_years.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/school_administration/class.academic_year_months.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/school_administration/class.parent_details.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/fee_management/class.fee_heads.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/mobile_app_services/classes/class.app.parent_details.php');

$Clean = array();

$Response = new JSONResponse();

$Clean['Token'] = '';
$Clean['StudentID'] = 0;

$FeeSubmissionLastDate = '';
$FeeSubmissionFrequency = 0;
$FeeSubmissionType = '';

$FeeDetails = array();
$Data = array();

$AllFeeHeads = array();
$AllFeeHeads = FeeHead::GetAllFeeHeads();

if (isset($_REQUEST['Token']))
{
	$Clean['Token'] = strip_tags(trim((string) $_REQUEST['Token']));
}

if (isset($_REQUEST['StudentID']))
{
	$Clean['StudentID'] = (int) $_REQUEST['StudentID'];
}   

try
{
	$LoggedInParent = new AppParentDetail($Clean['Token']);
	
	if (!array_key_exists($Clean['StudentID'], $LoggedInParent->GetApplicableStudents())) 
	{
		$Response->SetError(1);
		$Response->SetErrorCode(UNKNOWN_ERROR);
		$Response->SetMessage(ProcessAppErrors(UNKNOWN_ERROR));

		echo json_encode($Response->GetResponseAsArray());
		exit;
	}

	$FeeDetails = $LoggedInParent->GetStudentFeeDetails($Clean['StudentID']);

	$Counter = 0;
	foreach ($FeeDetails as $Month => $Details) 
	{
		foreach ($AllFeeHeads as $FeeHeadID => $FeeHeadDetails) 
		{
			if (!array_search($FeeHeadDetails['FeeHead'], array_keys($Details))) 
			{
				$Details[$FeeHeadDetails['FeeHead']]['StudentFeeStructureID'] = 0;
				$Details[$FeeHeadDetails['FeeHead']]['AmountPayable'] = 0;
				$Details[$FeeHeadDetails['FeeHead']]['AmountPaid'] = 0;
				$Details[$FeeHeadDetails['FeeHead']]['DueAmount'] = 0;
			}
		}
		
		$Data['Month'][$Counter] = $Details; 
		$Counter++;
	}
	
	//$Response->SetData($Data);
	$Response->PushData('fee_details', $Data, true);

	// Calculation for defaulted fee month
	$AcademicYearMonths =  array();
	$AcademicYearMonths =  AcademicYearMonth::GetMonthsByFeePriority();

	$GlobalSettingObject = new GlobalSetting();

	$FeeSubmissionLastDate = $GlobalSettingObject->GetFeeSubmissionLastDate();
	$FeeSubmissionFrequency = $GlobalSettingObject->GetFeeSubmissionFrequency();
	$FeeSubmissionType = $GlobalSettingObject->GetFeeSubmissionType();
    
    AcademicYear::GetCurrentAcademicYear($CurrentAcademicYearName, $SessionStartDate, $SessionEndDate);
    
	$CurrentMonthID = 0;
	
	if (strtotime(date('Y-m-d')) < strtotime($SessionStartDate))
	{
	    $CurrentMonthID = AcademicYearMonth::GetMonthIDByMonthName(date('M', strtotime($SessionStartDate)));
	}
	else
	{
	    $CurrentMonthID = AcademicYearMonth::GetMonthIDByMonthName(date('M'));
	}

	$FeeSubmissionLastMonthPriority = 0;

	foreach (array_chunk($AcademicYearMonths, $FeeSubmissionFrequency, true) as $Key => $Value) 
	{
	    if (array_key_exists($CurrentMonthID, $Value)) 
	    {
	        end($Value);
	        $FeeSubmissionLastMonthPriority = $Value[key($Value)]['FeePriority'];
	    }
	}
	
	$Response->PushData('FeeSubmissionLastMonthPriority', $FeeSubmissionLastMonthPriority);
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
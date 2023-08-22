<?php
error_log(json_encode($_REQUEST));
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

require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/school_administration/class.classes.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/school_administration/class.class_sections.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/school_administration/class.class_helpers.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/school_administration/class.school_session_class_daywise_timings.php');

$Clean = array();

$Response = new JSONResponse();

$Clean['Token'] = '';
$Clean['ClassSectionID'] = 0;

if (isset($_REQUEST['Token']))
{
	$Clean['Token'] = strip_tags(trim((string) $_REQUEST['Token']));
}

if (isset($_REQUEST['ClassSectionID']))
{
	$Clean['ClassSectionID'] = (int) $_REQUEST['ClassSectionID'];
}

try
{
	$LoggedInBranchStaff = new AppBranchStaff($Clean['Token']);
	
	$AllClassSections = array();
	$AllClassSections = $LoggedInBranchStaff->GetAllClassSections();
	
	if (!array_key_exists($Clean['ClassSectionID'], $AllClassSections))
	{
	    $Response->SetError(1);
    	$Response->SetErrorCode(UNKNOWN_ERROR);
    	$Response->SetMessage(ProcessAppErrors(UNKNOWN_ERROR));
    	
    	echo json_encode($Response->GetResponseAsArray());
    	exit;   
	}
	
	$CurrentClassSection = new ClassSections($Clean['ClassSectionID']);
			
	$ClassPeriodList = array();
	$ClassPeriodList = SchoolSessionClassDaywiseTiming::GetClassAllPeriodDetails($CurrentClassSection->GetClassID());
	
	$ClassTimeTable = array();
	$ClassTimeTable = ClassHelpers::GetClassSectionTimeTable($Clean['ClassSectionID'], true);
	
	$DayList = array();

	$Counter = 0;
	for ($i = 0; $i < 7; $i++) {
		$DayList[++$Counter] = jddayofweek($i, 1);
	}

	$Data = array();
	
	foreach ($DayList as $DayID => $DayName) 
	{
		$Counter = 0;
		$Data[$DayName] = array();
		
		foreach ($ClassPeriodList as $SchoolTimingPartID => $TimingPartDetails) 
		{			
			if ($TimingPartDetails['PartType'] != 'Class')
			{
				$ClassDayPeriodDetails = false;

				$PeriodTimingID = 0;
				$DaywiseTimingsID = 0;

				$PeriodStartTime = '';
				$PeriodEndTime = '';

				$ClassDayPeriodDetails = SchoolSessionClassDaywiseTiming::IsPeriodApplicableForClass($CurrentClassSection->GetClassID(), $SchoolTimingPartID, $DayID, $DaywiseTimingsID, $PeriodStartTime, $PeriodEndTime, $PeriodTimingID);

				if ($ClassDayPeriodDetails)
				{
					$Data[$DayName][$Counter]['StartTime'] = date('h:i A', strtotime($PeriodStartTime));
					$Data[$DayName][$Counter]['EndTime'] = date('h:i A', strtotime($PeriodEndTime));
					$Data[$DayName][$Counter]['PeriodName'] = $TimingPartDetails['TimingPart'];
					
					$Data[$DayName][$Counter]['SubjectName'] = $TimingPartDetails['PartType'];
					
					//$RecordSet = ['DayName' => $DayName, 'Period' => $TimingPartDetails['TimingPart'], 'StartTime' => date('h:i A', strtotime($PeriodStartTime)), 'EndTime' => date('h:i A', strtotime($PeriodEndTime)), 'PeriodName' => $TimingPartDetails['PartType']];
					
					//array_push($Data, $RecordSet);
				}
			}
			else
			{
				$ClassDayPeriodDetails = false;

				$PeriodTimingID = 0;
				$DaywiseTimingsID = 0;

				$PeriodStartTime = '';
				$PeriodEndTime = '';

				$ClassDayPeriodDetails = SchoolSessionClassDaywiseTiming::IsPeriodApplicableForClass($CurrentClassSection->GetClassID(), $SchoolTimingPartID, $DayID, $DaywiseTimingsID, $PeriodStartTime, $PeriodEndTime, $PeriodTimingID);
				
				if ($ClassDayPeriodDetails)
				{
					if (isset($ClassTimeTable[$DaywiseTimingsID][$PeriodTimingID]))
					{
						$Data[$DayName][$Counter]['StartTime'] = date('h:i A', strtotime($PeriodStartTime));
						$Data[$DayName][$Counter]['EndTime'] = date('h:i A', strtotime($PeriodEndTime));
						$Data[$DayName][$Counter]['PeriodName'] = $TimingPartDetails['TimingPart'];
						$Data[$DayName][$Counter]['SubjectName'] = $ClassTimeTable[$DaywiseTimingsID][$PeriodTimingID]['ClassSubjectName'];
						$Data[$DayName][$Counter]['TeacherName'] = $ClassTimeTable[$DaywiseTimingsID][$PeriodTimingID]['TeacherClassName'];
						
						//$RecordSet = ['DayName' => $DayName, 'Period' => $TimingPartDetails['TimingPart'], 'StartTime' => date('h:i A', strtotime($PeriodStartTime)), 'EndTime' => date('h:i A', strtotime($PeriodEndTime)), 'PeriodName' => $ClassTimeTable[$DaywiseTimingsID][$PeriodTimingID]['ClassSubjectName'], 'TeacherName' => $ClassTimeTable[$DaywiseTimingsID][$PeriodTimingID]['TeacherClassName']];

						//array_push($Data, $RecordSet);
					}
					else
					{
					    $Data[$DayName][$Counter]['StartTime'] = date('h:i A', strtotime($PeriodStartTime));
						$Data[$DayName][$Counter]['EndTime'] = date('h:i A', strtotime($PeriodEndTime));
						$Data[$DayName][$Counter]['PeriodName'] = $TimingPartDetails['TimingPart'];
						$Data[$DayName][$Counter]['SubjectName'] = '';
						$Data[$DayName][$Counter]['TeacherName'] = '';
					}
				}
			}
			
			$Counter++;
		}
	}
	
	$Response->SetDataOnKey('TimeTable', $Data);
	//$Response->PushData('timetable', $Data, true);
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
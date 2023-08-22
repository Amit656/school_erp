<?php
// error_log(json_encode($_REQUEST));
header("Content-Type:application/json");

require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.users.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.validation.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.authentication.php');

require_once($_SERVER['DOCUMENT_ROOT'] . '/mobile_app_services/classes/class.json_response.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/mobile_app_services/includes/global_defaults.php');

require_once($_SERVER['DOCUMENT_ROOT'] . '/mobile_app_services/includes/process_errors.php');

//	Other Required Classes
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/school_administration/class.parent_details.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/mobile_app_services/classes/class.app.parent_details.php');

require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/school_administration/class.class_sections.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/school_administration/class.class_helpers.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/school_administration/class.school_session_class_daywise_timings.php');

$Clean = array();

$Response = new JSONResponse();

$Clean['Token'] = '';
$Clean['StudentID'] = 0;

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

	$LoggedInParent->GetClassSectionDetails($Clean['StudentID']);
	
	if ($LoggedInParent->GetClassSectionID() <= 0)
	{
		throw Exception('Unknown error, please try again.');
	}
	
	$CurrentClassSection = new ClassSections($LoggedInParent->GetClassSectionID());
			
	$ClassPeriodList = array();
	$ClassPeriodList = SchoolSessionClassDaywiseTiming::GetClassAllPeriodDetails($CurrentClassSection->GetClassID());
	
	$ClassTimeTable = array();
	$ClassTimeTable = ClassHelpers::GetClassSectionTimeTable($LoggedInParent->GetClassSectionID(), true);
	
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

				$ClassDayPeriodDetails = SchoolSessionClassDaywiseTiming::IsPeriodApplicableForClass($LoggedInParent->GetClassID(), $SchoolTimingPartID, $DayID, $DaywiseTimingsID, $PeriodStartTime, $PeriodEndTime, $PeriodTimingID);

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
	
	$Response->SetDataOnKey('time_table', $Data);
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
<?php
// error_log(json_encode($_REQUEST));
header("Content-Type:application/json");

require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.users.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.validation.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.authentication.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.date_processing.php');

require_once($_SERVER['DOCUMENT_ROOT'] . '/includes/global_defaults.inc.php');

require_once($_SERVER['DOCUMENT_ROOT'] . '/mobile_app_services/classes/class.json_response.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/mobile_app_services/includes/global_defaults.php');

require_once($_SERVER['DOCUMENT_ROOT'] . '/mobile_app_services/includes/process_errors.php');

//Other Required Classess
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/school_administration/class.classes.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/school_administration/class.class_sections.php');

require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/academic_supervision/class.topic_schedules.php');

require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/school_administration/class.branch_staff.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/mobile_app_services/classes/class.app.branch_staff.php');

$Response = new JSONResponse();

$Clean = array();
$Filters = array();

$Clean['SchoolCode'] = '';
$Clean['Token'] = '';
$Clean['ClassSectionID'] = 0;
$Clean['ClassSubjectID'] = 0;

//	Other Variables
$Clean['ClassID'] = 0;

if (isset($_REQUEST['SchoolCode']))
{
	$Clean['SchoolCode'] = strip_tags(trim((string) $_REQUEST['SchoolCode']));
}

if (isset($_REQUEST['Token']))
{
	$Clean['Token'] = strip_tags(trim((string) $_REQUEST['Token']));
}

if (isset($_REQUEST['ClassSectionID']))
{
	$Clean['ClassSectionID'] = (int) $_REQUEST['ClassSectionID'];
}

if (isset($_REQUEST['ClassSubjectID']))
{
	$Clean['ClassSubjectID'] = (int) $_REQUEST['ClassSubjectID'];
}

try
{
	$LoggedInBranchStaff = new AppBranchStaff($Clean['Token']);

	$ClassSectionList = $LoggedInBranchStaff->GetApplicableClassSections();

	$RecordValidator = new Validator();

	if (!$RecordValidator->ValidateInSelect($Clean['ClassSectionID'], $ClassSectionList, 'Unknown error, please try again.')) 
	{
		$Response->SetError(1);
		$Response->SetErrorCode(UNKNOWN_ERROR);
		$Response->SetMessage(ProcessAppErrors(UNKNOWN_ERROR));

		echo json_encode($Response->GetResponseAsArray());
		exit;
	}

	$ClassSectionObject = new ClassSections($Clean['ClassSectionID']);

	$Clean['ClassID'] = $ClassSectionObject->GetClassID();

	$SelectedAddedClass = New AddedClass($Clean['ClassID']);
    $SelectedAddedClass->FillAssignedSubjects();

    $ClassSubjectList = $SelectedAddedClass->GetAssignedSubjects();

	if ($Clean['ClassSubjectID'] != 0) 
	{
		if (!$RecordValidator->ValidateInSelect($Clean['ClassSubjectID'], $ClassSubjectList, 'Unknown error, please try again.')) 
		{
			$Response->SetError(1);
			$Response->SetErrorCode(UNKNOWN_ERROR);
			$Response->SetMessage(ProcessAppErrors(UNKNOWN_ERROR));

			echo json_encode($Response->GetResponseAsArray());
			exit;
		}
	}

    //set record filters
    $Filters['ClassSectionID'] = $Clean['ClassSectionID'];
    $Filters['ClassSubjectID'] = $Clean['ClassSubjectID'];
    // $Filters['BranchStaffID'] = $LoggedInBranchStaff->GetBranchStaffID();

    // paging and sorting variables start here  //
	$TotalPages = 0;

	$Start = 0;
	$Limit = GLOBAL_SITE_PAGGING;

	$TopicScheduleList = array();
	// end of paging variables//
    //get records count
    TopicSchedule::SearchTopicSchedules($TotalRecords, true, $Filters);

    if ($TotalRecords > 0)
    {
        $TopicScheduleList = TopicSchedule::SearchTopicSchedules($TotalRecords, false, $Filters, $Start, $Limit);
        
        foreach ($TopicScheduleList as $TopicScheduleID => $TopicScheduleDetails)
        {
            if ($TopicScheduleDetails['ScheduleType'] == 'Weekly')
            {
                $TopicScheduleList[$TopicScheduleID]['ScheduleType'] = date('jS M', strtotime($TopicScheduleDetails['StartDate'])) . ' To ' . date('jS M', strtotime('+5 days', strtotime($TopicScheduleDetails['StartDate'])));
            }
            else
            {
                $TopicScheduleList[$TopicScheduleID]['ScheduleType'] = date('jS M', strtotime($TopicScheduleDetails['StartDate'])) . ' To ' . date('tS M', strtotime('+5 days', strtotime($TopicScheduleDetails['StartDate'])));
            }
        }
    }

	$Response->PushData('TopicScheduleList', $TopicScheduleList);
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
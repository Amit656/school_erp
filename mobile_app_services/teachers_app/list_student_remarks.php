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
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/school_administration/class.classes.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/school_administration/class.class_sections.php');

require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/school_administration/class.students.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/school_administration/class.student_details.php');

require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/academic_supervision/class.remarks.php');

require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/school_administration/class.branch_staff.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/mobile_app_services/classes/class.app.branch_staff.php');

$Response = new JSONResponse();

$RemarkTypeList = array('Positive' => 'Positive', 'Negative' => 'Negative');
$Clean = array();
$Filters = array();

$Clean['SchoolCode'] = '';
$Clean['UniqueToken'] = md5(uniqid(rand(), true));

//	Other Variables
$Clean['ClassID'] = 0;
$Clean['ClassSectionID'] = 0;
$Clean['StudentID'] = 0;

$Clean['RemarkType'] = 'Positive';

if (isset($_REQUEST['SchoolCode']))
{
	$Clean['SchoolCode'] = strip_tags(trim((string) $_REQUEST['SchoolCode']));
}

if (isset($_REQUEST['Token']))
{
	$Clean['UniqueToken'] = strip_tags(trim((string) $_REQUEST['Token']));
}

if (isset($_REQUEST['ClassID']))
{
	$Clean['ClassID'] = (int) $_REQUEST['ClassID'];
}

if (isset($_REQUEST['ClassSectionID']))
{
	$Clean['ClassSectionID'] = (int) $_REQUEST['ClassSectionID'];
}

if (isset($_REQUEST['StudentID']))
{
	$Clean['StudentID'] = (int) $_REQUEST['StudentID'];
}

if (isset($_REQUEST['RemarkType']))
{
	$Clean['RemarkType'] = strip_tags(trim($_REQUEST['RemarkType']));
}

try
{
	$LoggedInBranchStaff = new AppBranchStaff($Clean['UniqueToken']);

	$AllClasses = array();
	$AllClasses = $LoggedInBranchStaff->GetTeacherApplicableClasses(false);

	$RecordValidator = new Validator();

	if (!$RecordValidator->ValidateInSelect($Clean['ClassID'], $AllClasses, 'Unknown error, please try again.')) 
	{
		$Response->SetError(1);
		$Response->SetErrorCode(UNKNOWN_ERROR);
		$Response->SetMessage(ProcessAppErrors(UNKNOWN_ERROR));

		echo json_encode($Response->GetResponseAsArray());
		exit;
	}

	// getting sections of class
	$ClassSectionsList = array();
	$ClassSectionsList = ClassSections::GetClassSections($Clean['ClassID']);

	if ($Clean['ClassSectionID'] != 0) 
	{
		if (!$RecordValidator->ValidateInSelect($Clean['ClassSectionID'], $ClassSectionsList, 'Unknown error, please try again.')) 
		{
			$Response->SetError(1);
			$Response->SetErrorCode(UNKNOWN_ERROR);
			$Response->SetMessage(ProcessAppErrors(UNKNOWN_ERROR));

			echo json_encode($Response->GetResponseAsArray());
			exit;
		}

		$StudentsList = array();
		$StudentsList = StudentDetail::GetStudentsByClassSectionID($Clean['ClassSectionID']);
		

		if ($Clean['StudentID'] != 0) 
		{
			if (!$RecordValidator->ValidateInSelect($Clean['StudentID'], $StudentsList, 'Unknown error, please try again.')) 
			{
				$Response->SetError(1);
				$Response->SetErrorCode(UNKNOWN_ERROR);
				$Response->SetMessage(ProcessAppErrors(UNKNOWN_ERROR));

				echo json_encode($Response->GetResponseAsArray());
				exit;
			}
		}
	}

	if ($Clean['RemarkType'] != '') 
	{
		if (!$RecordValidator->ValidateInSelect($Clean['RemarkType'], $RemarkTypeList, 'Unknown error, please try again.')) 
		{
			$Response->SetError(1);
			$Response->SetErrorCode(UNKNOWN_ERROR);
			$Response->SetMessage(ProcessAppErrors(UNKNOWN_ERROR));

			echo json_encode($Response->GetResponseAsArray());
			exit;
		}
	}

    //set record filters
    $Filters['ClassID'] = $Clean['ClassID'];
    $Filters['ClassSectionID'] = $Clean['ClassSectionID'];
    $Filters['StudentID'] = $Clean['StudentID'];
    $Filters['RemarkType'] = $Clean['RemarkType'];

    // paging and sorting variables start here  //
	$TotalPages = 0;

	$Start = 0;
	$Limit = GLOBAL_SITE_PAGGING;

	$StudentRemarksList = array();
	// end of paging variables//
    //get records count
    Remark::SearchRemarks($TotalRecords, true, $Filters);

    if ($TotalRecords > 0)
    {
        $StudentRemarksList = Remark::SearchRemarks($TotalRecords, false, $Filters, $Start, $Limit);
    }

	$Response->PushData('remark_list', $StudentRemarksList);
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
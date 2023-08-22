<?php
header("Content-Type:application/json");

require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.users.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.validation.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.authentication.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.date_processing.php');

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

$Clean = array();
$RemarkTypeList = array('Positive' => 'Positive', 'Negative' => 'Negative');

$Clean['SchoolCode'] = '';
$Clean['UniqueToken'] = md5(uniqid(rand(), true));

//	Other Variables
$Clean['RemarkID'] = 0;
$Clean['ClassSectionID'] = 0;
$Clean['StudentID'] = 0;

$Clean['RemarkType'] = 'Positive';
$Clean['RemarkType'] = '';

if (isset($_REQUEST['SchoolCode']))
{
	$Clean['SchoolCode'] = strip_tags(trim((string) $_REQUEST['SchoolCode']));
}

if (isset($_REQUEST['Token']))
{
	$Clean['UniqueToken'] = strip_tags(trim((string) $_REQUEST['Token']));
}

if (isset($_REQUEST['RemarkID']))
{
	$Clean['RemarkID'] = (int) $_REQUEST['RemarkID'];
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

if (isset($_REQUEST['Remark']))
{
	$Clean['Remark'] = strip_tags(trim($_REQUEST['Remark']));
}

try
{
	$LoggedInBranchStaff = new AppBranchStaff($Clean['UniqueToken']);

	if ($LoggedInBranchStaff->IsClassSectionValid($Clean['ClassSectionID'])) 
	{
		$Response->SetError(1);
		$Response->SetErrorCode(UNKNOWN_ERROR);
		$Response->SetMessage(ProcessAppErrors(UNKNOWN_ERROR));

		echo json_encode($Response->GetResponseAsArray());
		exit;
	}

	$RecordValidator = new Validator();

	$StudentsList = array();
	$StudentsList = StudentDetail::GetStudentsByClassSectionID($Clean['ClassSectionID']);
	
	if (!$RecordValidator->ValidateInSelect($Clean['StudentID'], $StudentsList, 'Unknown error, please try again.')) 
	{
		$Response->SetError(1);
		$Response->SetErrorCode(UNKNOWN_ERROR);
		$Response->SetMessage(ProcessAppErrors(UNKNOWN_ERROR));

		echo json_encode($Response->GetResponseAsArray());
		exit;
	}
	
	if (!$RecordValidator->ValidateInSelect($Clean['RemarkType'], $RemarkTypeList, 'Unknown error, please try again.')) 
	{
		$Response->SetError(1);
		$Response->SetErrorCode(UNKNOWN_ERROR);
		$Response->SetMessage(ProcessAppErrors(UNKNOWN_ERROR));

		echo json_encode($Response->GetResponseAsArray());
		exit;
	}	

	if (!$RecordValidator->ValidateStrings($Clean['Remark'], 'Remark is required and should be between 5 and 500 characters.', 5, 500)) 
	{
		$Response->SetError(1);
		$Response->SetErrorCode(0);
		$Response->SetMessage('Remark is required and should be between 5 and 500 characters.');

		echo json_encode($Response->GetResponseAsArray());
		exit;
	}

	if ($Clean['RemarkID'] > 0)
	{
		$CurrentRemark = new Remark($Clean['RemarkID']);
	}
	else
	{	
		$CurrentRemark = new Remark();
		$CurrentRemark->SetIsActive(1);
	}

	$CurrentRemark->SetStudentID($Clean['StudentID']);
	$CurrentRemark->SetRemarkType($Clean['RemarkType']);
	$CurrentRemark->SetRemark($Clean['Remark']);

	$CurrentRemark->SetCreateUserID($LoggedInBranchStaff->GetUserID());

	if (!$CurrentRemark->Save())
	{
		$RecordValidator->AttachTextError(ProcessErrors($RecordValidator->GetLastErrorCode()));
		$HasErrors = true;
		exit;
	}

	$Response->SetMessage(ProcessAppMessages(SAVED_SUCCESSFULLY));
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
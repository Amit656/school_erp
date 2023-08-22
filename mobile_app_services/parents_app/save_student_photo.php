<?php
error_log(json_encode($_REQUEST));
error_log(json_encode($_FILES));
header("Content-Type:application/json");

require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.users.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.validation.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.authentication.php');

require_once($_SERVER['DOCUMENT_ROOT'] . '/mobile_app_services/classes/class.json_response.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/mobile_app_services/includes/global_defaults.php');

require_once($_SERVER['DOCUMENT_ROOT'] . '/mobile_app_services/includes/process_errors.php');

require_once($_SERVER['DOCUMENT_ROOT'] . '/includes/global_defaults.inc.php');

//	Other Required Classes
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/school_administration/class.parent_details.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/school_administration/class.students.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/school_administration/class.student_details.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/mobile_app_services/classes/class.app.parent_details.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/mobile_app_services/classes/class.app.student_details.php');

$acceptable_extensions = array('jpeg', 'jpg', 'png', 'gif');
$acceptable_mime_types = array(
	'image/jpeg',
	'image/jpg',
	'image/png',
	'image/gif'
);

$Clean = array();

$Clean['StudentPhoto'] = array();

$Response = new JSONResponse();

$Clean['Token'] = 'd21e9fa63bf7597f95d88ee492d212d3';

$Clean['StudentID'] = 74;

if (isset($_REQUEST['Token']))
{
	$Clean['Token'] = strip_tags(trim((string) $_REQUEST['Token']));
}

if (isset($_REQUEST['StudentID']))
{
	$Clean['StudentID'] = (int) $_REQUEST['StudentID'];
}

if (isset($_FILES['StudentPhoto']) && is_array($_FILES['StudentPhoto']))
{
	$Clean['StudentPhoto'] = $_FILES['StudentPhoto'];
}

//error_log(json_encode($Clean));

try
{
	$LoggedInParent = new AppParentDetail($Clean['Token']);

	$EditStudentPhoto = new AppStudentDetail($Clean['StudentID']);

	$FileName = '';
	$FileExtension = '';

	if (count($Clean['StudentPhoto']) > 0)
	{
		if ($Clean['StudentPhoto']['size'] > MAX_UPLOADED_FILE_SIZE_NEW || $Clean['StudentPhoto']['size'] <= 0)
		{
			$Response->SetError(1);
			$Response->SetErrorCode(UNKNOWN_ERROR);
			$Response->SetMessage('File size cannot be greater then ' . (MAX_UPLOADED_FILE_SIZE_NEW / 1024 / 1024) . ' MB.');

			echo json_encode($Response->GetResponseAsArray());
			exit;
		}

		$FileExtension = strtolower(pathinfo($Clean['StudentPhoto']['name'], PATHINFO_EXTENSION));
		//error_log($Clean['StudentPhoto']['type']);

		if (in_array($Clean['StudentPhoto']['type'], $acceptable_extensions))
		{

			$Response->SetError(1);
			$Response->SetErrorCode(UNKNOWN_ERROR);
			$Response->SetMessage('Only ' . implode(', ', $acceptable_extensions) . ' files are allowed.');

			echo json_encode($Response->GetResponseAsArray());
			exit;
		}

		if (strlen($Clean['StudentPhoto']['name']) > MAX_UPLOADED_FILE_NAME_LENGTH)
		{

			$Response->SetError(1);
			$Response->SetErrorCode(UNKNOWN_ERROR);
			$Response->SetMessage('Uploaded file name cannot be greater then ' . MAX_UPLOADED_FILE_NAME_LENGTH . ' chars.');

			echo json_encode($Response->GetResponseAsArray());
			exit;
		}

		$FileName = $Clean['StudentPhoto']['name'];

		$FileName = md5(uniqid(rand(), true) . $EditStudentPhoto->GetStudentID()) . '.' . $FileExtension;
		//$EditStudentPhoto->SetStudentPhoto($FileName);

		$UniqueUserFileUploadDirectory = SITE_FS_PATH . '/site_images/student_images/' . $EditStudentPhoto->GetStudentID() . '/';
		
		//error_log($UniqueUserFileUploadDirectory);

		if (!is_dir($UniqueUserFileUploadDirectory))
		{
			mkdir($UniqueUserFileUploadDirectory, 0777, true);
		}

		if (!$EditStudentPhoto->SaveStudentPhoto($FileName))
		{
			$Response->SetError(1);
			$Response->SetErrorCode(UNKNOWN_ERROR);
			$Response->SetMessage(ProcessAppErrors(UNKNOWN_ERROR));

			echo json_encode($Response->GetResponseAsArray());
			exit;
		}

		// now move the uploaded file to application document folder
		// move_uploaded_file($Clean['StudentPhoto']['tmp_name'], $UniqueUserFileUploadDirectory . $EditStudentPhoto->GetStudentID().'.jpg');
		

		if (!move_uploaded_file($Clean['StudentPhoto']['tmp_name'], $UniqueUserFileUploadDirectory . $FileName))
		{
			$Response->SetError(1);
			$Response->SetErrorCode(UNKNOWN_ERROR);
			$Response->SetMessage(ProcessAppErrors(UNKNOWN_ERROR));

			echo json_encode($Response->GetResponseAsArray());
			exit;
		}
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
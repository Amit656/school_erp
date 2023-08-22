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
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/school_administration/class.students.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/school_administration/class.student_details.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/school_administration/class.branch_staff.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/mobile_app_services/classes/class.app.branch_staff.php');

$Response = new JSONResponse();

$Clean = array();

$Clean['SchoolCode'] = '';
$Clean['UniqueToken'] = md5(uniqid(rand(), true));

if (isset($_REQUEST['SchoolCode']))
{
	$Clean['SchoolCode'] = strip_tags(trim((string) $_REQUEST['SchoolCode']));
}

if (isset($_REQUEST['Token']))
{
	$Clean['UniqueToken'] = strip_tags(trim((string) $_REQUEST['Token']));
}

try
{
	$LoggedInBranchStaff = new AppBranchStaff($Clean['UniqueToken']);

	$LoggedInBranchStaff->FillAssignedClasses();

	$ClassSectionList = $LoggedInBranchStaff->GetApplicableClassSections();
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

$Clean['ClassSectionID'] = 0;

if (isset($_REQUEST['ClassSectionID']))
{
	$Clean['ClassSectionID'] = (int) $_REQUEST['ClassSectionID'];
}

if (!array_key_exists($Clean['ClassSectionID'], $ClassSectionList))
{
	$Response->SetError(1);
	$Response->SetErrorCode(UNKNOWN_ERROR);
	$Response->SetMessage(ProcessAppErrors(UNKNOWN_ERROR));
	
	echo json_encode($Response->GetResponseAsArray());
	exit;
}


$StudentsList = array();
$StudentsList = StudentDetail::GetStudentsByClassSectionID($Clean['ClassSectionID'], 'Active');

if (count($StudentsList) > 0) 
{
	foreach ($StudentsList as $StudentID => $Datails) 
	{	
		$FilePath = '';
		if (is_dir($_SERVER['DOCUMENT_ROOT'] . '/student_profile_images/' . $StudentID)) 
		{
			$dir = $_SERVER['DOCUMENT_ROOT'] . '/student_profile_images/' . $StudentID;

			foreach(scandir($dir) as $file) 
			{
				if ('.' === $file || '..' === $file) 
				{
					continue;
				}

				$FilePath = "$dir/$file";
			}
		}


		$StudentsList[$StudentID]['ProfileImage'] = $FilePath;
	}
}

$Response->PushData('students_list', $StudentsList);

echo json_encode($Response->GetResponseAsArray());
exit;
?>
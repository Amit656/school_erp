<?php
// error_log(json_encode($_REQUEST));
header("Content-Type:application/json");

require_once($_SERVER['DOCUMENT_ROOT'] . '/includes/global_defaults.inc.php');

require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.users.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.validation.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.authentication.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.date_processing.php');

require_once($_SERVER['DOCUMENT_ROOT'] . '/mobile_app_services/classes/class.json_response.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/mobile_app_services/includes/global_defaults.php');

require_once($_SERVER['DOCUMENT_ROOT'] . '/mobile_app_services/includes/process_errors.php');

//	Other Required Classes
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/school_administration/class.students.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/school_administration/class.student_details.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/school_administration/class.class_sections.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/school_administration/class.class_attendence.php');

require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/school_administration/class.branch_staff.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/mobile_app_services/classes/class.app.branch_staff.php');

$Response = new JSONResponse();

$Clean = array();

$Clean['SchoolCode'] = '';
$Clean['UniqueToken'] = md5(uniqid(rand(), true));

//	Other Variables
$Clean['ClassSectionID'] = 0;
$Clean['AttendanceDate'] = date('Y-m-d');

if (isset($_REQUEST['SchoolCode']))
{
	$Clean['SchoolCode'] = strip_tags(trim((string) $_REQUEST['SchoolCode']));
}

if (isset($_REQUEST['Token']))
{
	$Clean['UniqueToken'] = strip_tags(trim((string) $_REQUEST['Token']));
}

if (isset($_REQUEST['ClassSectionID']))
{
	$Clean['ClassSectionID'] = (int) $_REQUEST['ClassSectionID'];
}

if (isset($_REQUEST['AttendanceDate']))
{
	$Clean['AttendanceDate'] = strip_tags(trim((string) $_REQUEST['AttendanceDate']));
}

try
{
	$LoggedInBranchStaff = new AppBranchStaff($Clean['UniqueToken']);
	
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
	
	$RecordValidator = new Validator();

	$AllAttedanceDeatils = array();

	$StudentsList = array();
	$StudentsList = ClassAttendence::GetClassAttendence($Clean['ClassSectionID'], $Clean['AttendanceDate']);
	
	if (count($StudentsList) > 0)
	{
	    foreach ($StudentsList as $StudentID => $Details)
    	{
    	    $AllAttedanceDeatils[$StudentID] = $Details;
    	    if (file_exists(SITE_FS_PATH . '/site_images/student_images/' . $StudentID . '/' . $Details['Image'])) 
            {   
                $AllAttedanceDeatils[$StudentID]['Image'] = SITE_HTTP_PATH . '/site_images/student_images/' . $StudentID . '/' . $Details['Image'];
            }
    	}   
	}
	else
	{
	    $StudentsList = StudentDetail::GetStudentsByClassSectionID($Clean['ClassSectionID'], 'Active');
	    foreach ($StudentsList as $StudentID => $Details)
	    {
	        $AllAttedanceDeatils[$StudentID] = $Details;
	        $AllAttedanceDeatils[$StudentID]['AttendenceStatus'] = 'NotMarked';
	        
	        if (file_exists(SITE_FS_PATH . '/site_images/student_images/' . $StudentID . '/' . $Details['Image'])) 
            {   
                $AllAttedanceDeatils[$StudentID]['Image'] = SITE_HTTP_PATH . '/site_images/student_images/' . $StudentID . '/' . $Details['Image'];
            }
            
            $AllAttedanceDeatils[$StudentID]['ClassAttendenceID'] = '';
	    }
	}
	
	$Response->PushData('students_list', $AllAttedanceDeatils);
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
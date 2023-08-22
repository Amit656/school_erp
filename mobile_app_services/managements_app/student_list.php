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
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/school_administration/class.classes.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/school_administration/class.students.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/school_administration/class.student_details.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/school_administration/class.branch_staff.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/mobile_app_services/classes/class.app.branch_staff.php');

$Response = new JSONResponse();

$Clean = array();

$Clean['SchoolCode'] = '';
$Clean['UniqueToken'] = md5(uniqid(rand(), true));

$Clean['ClassID'] = 0;
$Clean['ClassSectionID'] = 0;

$Filters = array();

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
	
	$Start = 0;
    $Limit = 10;
	
	$Filters['ClassID'] = $Clean['ClassID'];
    $Filters['ClassSectionID'] = $Clean['ClassSectionID'];
    
    // $TotalStudents = StudentDetail::GetAllStudents($TotalRecords, true, $Filters);
    
    $StudentsList = array();
    $StudentsList = StudentDetail::GetAllStudents($TotalRecords, false, $Filters, $Start, $Limit);
    
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
    
    $Response->PushData('StudentList', $StudentsList);
	
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
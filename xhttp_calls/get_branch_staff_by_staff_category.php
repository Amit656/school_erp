<?php
//header('Content-Type: application/json');

require_once('../classes/class.users.php');
require_once('../classes/class.authentication.php');

try
{
	$AuthObject = new ApplicationAuthentication;
	$LoggedUser = new User(0, $AuthObject->CheckValidUser());
}

// THIS CATCH BLOCK BUBBLES THE EXCEPTION TO THE BUILT IN 'Exception' CLASS IF THERE ARE ANY UNCAUGHT ERRORS //
catch (ApplicationAuthException $e)
{
	echo 'error|*****|Unknown error, please try again.';
	exit;
}
catch (Exception $e)
{
	echo 'error|*****|Unknown error, please try again.';
	exit;
}

require_once("../classes/school_administration/class.branch_staff.php");

$StaffCategory = '';

if (isset($_POST['SelectedStaffCategory']))
{
	$StaffCategory = strip_tags(trim($_POST['SelectedStaffCategory']));
}

if (empty($StaffCategory))
{
	echo 'error|*****|Unknown error, please try again.';
	exit;
}

$BranchStaffList = array();
$BranchStaffList = BranchStaff::GetActiveBranchStaff($StaffCategory);

if (count($BranchStaffList) <= 0)
{
	echo 'error|*****|No records found.';
	exit;
}

echo 'success|*****|';

foreach ($BranchStaffList as $BranchStaffID=>$BranchStaffDetails)
{
	
	echo '<option value="'.$BranchStaffID.'">'. $BranchStaffDetails['FirstName'] . " ". $BranchStaffDetails['LastName'] .'</option>';	
}

exit;
?>
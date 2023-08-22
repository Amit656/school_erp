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

require_once('../classes/school_administration/class.class_helpers.php');

$Clean = array();

$Clean['TimeTableDetailID'] = 0;

if (isset($_POST['TimeTableDetailIDToBeDeleted']))
{
	$Clean['TimeTableDetailID'] = (int) $_POST['TimeTableDetailIDToBeDeleted'];
}

if ($Clean['TimeTableDetailID'] <= 0)
{
	echo 'error|*****|Unknown error, please try again.';
	exit;
}

if ( !ClassHelpers::DeleteClassSectionTimeTableDetails($Clean['TimeTableDetailID']) )
{
	echo 'error|*****|Unknown error, please try again.';
	exit;
}

echo 'success|*****|success';
?>
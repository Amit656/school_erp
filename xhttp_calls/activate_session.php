<?php
header('Content-Type: application/json');

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
	echo 'error';
	exit;
}
catch (Exception $e)
{
	echo 'error';
	exit;
}

require_once("../classes/school_administration/class.school_sessions.php");

$ActivateSessionID = 0;

if (isset($_GET['ActivateSessionID']))
{
	$ActivateSessionID = (int) $_GET['ActivateSessionID'];
}

if ($ActivateSessionID == 0)
{
	echo 'alert("Please Select Valid Session")';
	exit; 
}

SchoolSessions::DeactivateSession($ActivateSessionID, $LoggedUser->GetUserID());

echo '<div class="alert alert-success">
  <strong>Update Successfully</strong>.
</div>';
?>


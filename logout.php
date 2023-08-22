<?php
ob_start();
require_once('classes/class.authentication.php');

$LoggedOutFromAdminSection = false;
if (isset($_GET['Admin']))
{
	$LoggedOutFromAdminSection = true;
}

//1. RECHECK IF THE USER IS VALID //
try
{
	$AuthObject = new ApplicationAuthentication;
	$AuthObject->Logout();	
}
 
// THIS CATCH BLOCK BUBBLES THE EXCEPTION TO THE BUILT IN 'Exception' CLASS IF THERE ARE ANY UNCAUGHT ERRORS //
catch (AppAuthException $e)
{
	header('location:unauthorized_login.php');
	exit;
}
catch (Exception $e)
{
	header('location:unauthorized_login.php');
	exit;
}
// END OF 1. //

if ($LoggedOutFromAdminSection)
{
	header('location:admin/login.php');
}
else
{
	header('location:index.php');
}
exit;
?>
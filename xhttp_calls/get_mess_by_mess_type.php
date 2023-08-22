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

require_once("../classes/hostel_management/class.mess.php");

$MessType = '';

if (isset($_POST['SelectedMessType']))
{
	$MessType = strip_tags(trim($_POST['SelectedMessType']));
}

if ($MessType == '')
{
	echo 'error|*****|Unknown error, please try again.';
	exit;
}

$Messlist = array();
$Messlist = Mess::GetMessByType($MessType);

if (count($Messlist) <= 0)
{
	echo 'error|*****|No records found.';
	exit;
}

echo 'success|*****|';

foreach($Messlist as $MessID => $MessName)
{
	echo '<option value="' . $MessID . '">' . $MessName . '</option>';
}

exit;
?>
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

require_once("../classes/class.date_processing.php");
require_once("../classes/fee_management/class.fee_collection.php");

$FeePaymentModeDetailID = 0;
$ChequeStatus = '';

$ChequeBouncedDescription = '';

if (isset($_POST['FeePaymentModeDetailID']))
{
	$FeePaymentModeDetailID = (int) $_POST['FeePaymentModeDetailID'];
}
if (isset($_POST['ChequeStatus']))
{
	$ChequeStatus = (string) $_POST['ChequeStatus'];
}
if (isset($_POST['ChequeBouncedDescription']))
{
	$ChequeBouncedDescription = (string) $_POST['ChequeBouncedDescription'];
}

if ($FeePaymentModeDetailID <= 0 || $ChequeStatus == '')
{
	echo 'error|*****|Unknown error, please try again.';
	exit;
}

if (!FeeCollection::UpdateChequeStatus($FeePaymentModeDetailID, $ChequeStatus, $LoggedUser->GetUserID(), $ChequeBouncedDescription))
{
	echo 'error|*****|Error in execution query.';
	exit;
}

echo 'success|*****|';
echo $ChequeStatus;

exit;
?>
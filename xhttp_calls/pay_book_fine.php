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
require_once("../classes/library_management/class.books_fine.php");

$BooksFineID = 0;

$PaymentDate = '';

if (isset($_POST['SelectedBooksFineID']))
{
	$BooksFineID = (int) $_POST['SelectedBooksFineID'];
}

if (isset($_POST['PaymentDate']))
{
	$PaymentDate = (string) $_POST['PaymentDate'];
}

if ($BooksFineID <= 0 || $PaymentDate == '')
{
	echo 'error|*****|Unknown error, please try again.';
	exit;
}

$PayBookFine = new BooksFine($BooksFineID);

$PayBookFine->SetIsPaid(1);
$PayBookFine->SetPaidDate(date('Y-m-d', strtotime(DateProcessing::ToggleDateDayAndMonth(($PaymentDate)))));
$PayBookFine->SetPaymentReceivedBy($LoggedUser->GetUserID());

if (!$PayBookFine->Save())
{			
	echo ProcessErrors($PayBookFine->GetLastErrorCode());
	exit;
}	

echo 'success|*****|';

echo $PayBookFine->GetIsPaid();

exit;
?>
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

require_once("../classes/library_management/class.books_issue_conditions.php");

$UserType = '';
$ExtraDays = 0;

if (isset($_POST['SelectedUserType']))
{
	$UserType = (string) $_POST['SelectedUserType'];
}

if (isset($_POST['ExtraDays']))
{
	$ExtraDays = (int) $_POST['ExtraDays'];
}

if ($UserType == '' || $ExtraDays <= 0)
{
	echo 'error|*****|Unknown error, please try again.';
	exit;
}

$BooksIssueConditions = array();
$BooksIssueConditions = BooksIssueCondition::GetBooksIssueConditions($UserType);

if (count($BooksIssueConditions) <= 0)
{
	echo 'error|*****|No records found, please make conditions first.';
	exit;
}

$NumberOfWeeks = 0;
$BooksIssueConditionID = key($BooksIssueConditions); 

if ($BooksIssueConditions[$BooksIssueConditionID]['LateFineType'] == 'Daily') 
{
	$FineAmount = $BooksIssueConditions[$BooksIssueConditionID]['FineAmount'] * $ExtraDays;
}
else if ($BooksIssueConditions[$BooksIssueConditionID]['LateFineType'] == 'weekly') 
{
	$NumberOfWeeks = ceil($ExtraDays/7);

	$FineAmount = $BooksIssueConditions[$BooksIssueConditionID]['FineAmount'] * $NumberOfWeeks;
}

echo 'success|*****|';
echo $FineAmount; //FineAmount

exit;
?>

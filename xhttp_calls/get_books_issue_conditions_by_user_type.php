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
require_once("../classes/library_management/class.books_issue_conditions.php");
require_once("../classes/library_management/class.book_issue.php");

$UserType = '';
$IssuedToID = 0;

if (isset($_POST['SelectedUserType']))
{
	$UserType = (string) $_POST['SelectedUserType'];
}

if (isset($_POST['SelectedIssuedToID']))
{
	$IssuedToID = (int) $_POST['SelectedIssuedToID'];
}

if ($UserType == '' || $IssuedToID <= 0)
{
	echo 'error|*****|Unknown error, please try again.';
	exit;
}

$BooksIssueConditions = array();
$BooksIssueConditions = BooksIssueCondition::GetBooksIssueConditions($UserType);

$AllreadyIssuedBooksAtPresentTime = 0;
$AllreadyIssuedBooksAtPresentTime = BookIssue::GetAllreadyIssuedBooksToUser($UserType, $IssuedToID);

if (count($BooksIssueConditions) <= 0)
{
	echo 'error|*****|No records found, please make conditions first.';
	exit;
}

$BooksIssueConditionID = key($BooksIssueConditions); // quota of books for selected user

echo 'success|*****|';
echo $BooksIssueConditions[$BooksIssueConditionID]['DefaultDuration']; //duration

echo '|*****|';
echo $AllreadyIssuedBooksAtPresentTime; // Issued

echo '|*****|';
echo $BooksIssueConditions[$BooksIssueConditionID]['Quota'] - $AllreadyIssuedBooksAtPresentTime; // Available Quota
exit;
?>

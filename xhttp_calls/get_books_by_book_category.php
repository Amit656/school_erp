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

require_once("../classes/library_management/class.books.php");

$BookCategoryID = 0;

if (isset($_POST['SelectedBookCategoryID']))
{
	$BookCategoryID = (int) $_POST['SelectedBookCategoryID'];
}

if ($BookCategoryID <= 0)
{
	echo 'error|*****|Unknown error, please try again.';
	exit;
}

$BookList = array();
$BookList = Book::GetBooksByCategory($BookCategoryID);

if (count($BookList) <= 0)
{
	echo 'error|*****|No records found.';
	exit;
}

echo 'success|*****|';

foreach ($BookList as $BookID=>$BookName)
{
	
	echo '<option value="'.$BookID.'">'. $BookName .'</option>';	
}

exit;
?>
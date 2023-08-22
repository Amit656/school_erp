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

$AuthorName = '';

if (isset($_POST['SelectedAuthorName']))
{
	$AuthorName = (string) $_POST['SelectedAuthorName'];
}

if ($AuthorName == '')
{
	echo 'error|*****|Unknown error, please try again.';
	exit;
}

$AuthorList = array();
$AuthorList = Book::SearchAuthors($AuthorName);

echo 'success|*****|';

foreach ($AuthorList as $AuthorID => $AuthorName)
{
	
	echo '<option id="' . $AuthorID . '" value="' . $AuthorName . '"></option>';	
}

exit;
?>
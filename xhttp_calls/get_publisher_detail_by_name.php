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

$PublisherName = '';

if (isset($_POST['SelectedPublisherName']))
{
	$PublisherName = (string) $_POST['SelectedPublisherName'];
}

if ($PublisherName == '')
{
	echo 'error|*****|Unknown error, please try again.';
	exit;
}

$PublisherList = array();
$PublisherList = Book::SearchPublishers($PublisherName);

echo 'success|*****|';

foreach ($PublisherList as $PublisherID => $PublisherName)
{
	
	echo '<option id="' . $PublisherID . '" value="' . $PublisherName . '"></option>';	
}

exit;
?>
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

require_once("../classes/library_management/class.book_categories.php");

$ParentCategoryID = 0;

if (isset($_POST['SelectedParentCategoryID']))
{
	$ParentCategoryID = (int) $_POST['SelectedParentCategoryID'];
}

if ($ParentCategoryID <= 0)
{
	echo 'error|*****|Unknown error, please try again.';
	exit;
}

$BookCategoryList = array();
$BookCategoryList = BookCategory::GetActiveSubCategories($ParentCategoryID);

if (count($BookCategoryList) <= 0)
{
	echo 'error|*****|No records found.';
	exit;
}

echo 'success|*****|';

foreach($BookCategoryList as $BookCategoryID => $BookCategoryName)
{
	echo '<option value="' . $BookCategoryID . '">' . $BookCategoryName . '</option>';
}

exit;
?>
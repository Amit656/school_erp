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

require_once("../classes/school_administration/class.classes.php");
require_once("../classes/library_management/class.books.php");

$BookID = 0;

if (isset($_POST['SelectedBookID']))
{
	$BookID = (int) $_POST['SelectedBookID'];
}

if ($BookID <= 0)
{
	echo 'error|*****|Unknown error, please try again.';
	exit;
}

$ClassList =  array();
$ClassList = AddedClass::GetActiveClasses();

$BookCopies = array();
$BookCopies = Book::GetBookCopiesByBook($BookID);

if (count($BookCopies) <= 0)
{
	echo 'error|*****|No records found.';
	exit;
}

echo 'success|*****|';

foreach ($BookCopies as $BooksCopyID => $BooksCopyID)
{
	
	echo '<option value="'. $BooksCopyID .'"> Copy '. $BooksCopyID .'</option>';	
}

$BookIssuableTo = array();
$BookIssuableTo = Book::GetBookIssuableToByBook($BookID);

echo '|*****|';

foreach ($BookIssuableTo as $BookIssuableToID => $BookIssuableTo)
{
	if ($BookIssuableToID == 'All') 
	{
		echo '<option value="Student">Student</option>';
		echo '<option value="Teaching">Teaching</option>';
		echo '<option value="NonTeaching">Non-Teaching</option>';
	}
	else if ($BookIssuableToID == 'StudentTeaching') 
	{
		echo '<option value="Student">Student</option>';
		echo '<option value="Teaching">Teaching</option>';		
	}
	else if ($BookIssuableToID == 'AllStaff') 
	{
		echo '<option value="Student">Student</option>';
		echo '<option value="NonTeaching">Non-Teaching</option>';		
	}
	else
	{
		echo '<option value="'. $BookIssuableToID .'">'. $BookIssuableTo .'</option>';	
	}	
}

echo '|*****|';

$BookClasses = array();
$BookClasses = Book::GetBookClasses($BookID);

if (count($BookClasses) > 0) 
{
	foreach ($BookClasses as $BooksClassID => $ClassID)
    {
    	echo '<option value="'. $ClassID .'">'. $ClassList[$ClassID] .'</option>';
    }
}
else
{
	foreach ($ClassList as $ClassID => $ClassName)
    {
    	echo '<option value="'. $ClassID .'">'. $ClassName .'</option>';
    }
}

exit;
?>

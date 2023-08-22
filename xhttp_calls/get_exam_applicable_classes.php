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

require_once('../classes/examination/class.exams.php');

$ExamTypeID = 0;

if (isset($_POST['SelectedExamType']))
{
	$ExamTypeID = (int) $_POST['SelectedExamType'];
}

if ($ExamTypeID <= 0)
{
	echo 'error|*****|Unknown error, please try again.';
	exit;
}

$AllClasses = array();
$AllClasses = Exam::GetExamApplicableClasses($ExamTypeID);

if (count($AllClasses) <= 0)
{
	echo 'error|*****|No records found.';
	exit;
}

echo 'success|*****|';

foreach ($AllClasses as $ClassID => $ClassName)
{
	echo '<option value="'. $ClassID .'">'. $ClassName .'</option>';
}

exit;
?>
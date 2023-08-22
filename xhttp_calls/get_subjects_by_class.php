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

$ClassID = 0;

if (isset($_POST['SelectedClassID']))
{
	$ClassID = (int) $_POST['SelectedClassID'];
}

if ($ClassID <= 0)
{
	echo 'error|*****|Unknown error, please try again.';
	exit;
}

$SelectedAddedClass = New AddedClass($ClassID);
$SelectedAddedClass->FillAssignedSubjects();

$ClassSubjectList = $SelectedAddedClass->GetAssignedSubjects();

if (count($ClassSubjectList) <= 0)
{
	echo 'error|*****|No subject found for this class.';
	exit;
}

echo 'success|*****|';

foreach ($ClassSubjectList as $ClassSubjectID => $ClassSubjectDetail)
{	
	echo '<option value="'. $ClassSubjectID .'">'. $ClassSubjectDetail['Subject'] .'</option>';	
}

exit;
?>
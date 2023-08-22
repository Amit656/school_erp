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

require_once("../classes/academic_supervision/class.chapters.php");

$ClassSubjectID = 0;

if (isset($_POST['SelectedClassSubjectID']))
{
	$ClassSubjectID = (int) $_POST['SelectedClassSubjectID'];
}

if ($ClassSubjectID <= 0)
{
	echo 'error|*****|Unknown error, please try again.';
	exit;
}

$ChaptersList = array();
$ChaptersList = Chapter::GetChapterByClassSubject($ClassSubjectID);

if (count($ChaptersList) <= 0)
{
	echo 'error|*****|No chapter found in this subject.';
	exit;
}

echo 'success|*****|';

foreach ($ChaptersList as $ChapterID => $ChapterName)
{
	
	echo '<option value="'. $ChapterID .'">'. $ChapterName .'</option>';	
}

exit;
?>
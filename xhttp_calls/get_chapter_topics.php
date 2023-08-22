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

require_once("../classes/academic_supervision/class.chapter_topics.php");

$ChapterID = 0;

if (isset($_POST['SelectedChapterID']))
{
	$ChapterID = (int) $_POST['SelectedChapterID'];
}

if ($ChapterID <= 0)
{
	echo 'error|*****|Unknown error, please try again.';
	exit;
}

$ChapterTopicsList = array();
$ChapterTopicsList = ChapterTopic::GetTopicByChapter($ChapterID);

if (count($ChapterTopicsList) <= 0)
{
	echo 'error|*****|No topic found in this capter.';
	exit;
}

echo 'success|*****|';

foreach ($ChapterTopicsList as $ChapterTopicID => $ChapterTopicName)
{
	
	echo '<option value="'. $ChapterTopicID .'">'. $ChapterTopicName .'</option>';	
}

exit;
?>
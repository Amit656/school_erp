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

require_once("../classes/academic_supervision/class.chapter_topics.php");

$ChapterTopicName = '';
$ChapterID = 0;

if (isset($_POST['SelectedChapterTopicName']))
{
	$ChapterTopicName = (string) $_POST['SelectedChapterTopicName'];
}
if (isset($_POST['SelectedChapterID']))
{
	$ChapterID = (int) $_POST['SelectedChapterID'];
}

if ($ChapterTopicName == '' || $ChapterID <= 0)
{
	echo 'error|*****|Unknown error, please try again.';
	exit;
}

$Filters = array();
$Filters['ChapterTopicName'] = $ChapterTopicName;
$Filters['ChapterID'] = $ChapterID;
$Filters['ActiveStatus'] = 1;

$ChapterTopicList = array();
$ChapterTopicList = ChapterTopic::SearchChapterTopics($TotalRecords, false, $Filters, 0, 20);

/*if (count($ChapterTopicList) <= 0)
{
	echo 'error|*****|No records found.';
	exit;
}*/

echo 'success|*****|';

foreach ($ChapterTopicList as $ChapterTopicID => $ChapterTopicDetails)
{
	
	echo '<option id="' . $ChapterTopicID . '" value="' . $ChapterTopicDetails['TopicName'] . '"></option>';	
}

exit;
?>
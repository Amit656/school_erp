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

$ClassSectionID = 0;

if (isset($_POST['SelectedClassSectionID']))
{
	$ClassSectionID = (int) $_POST['SelectedClassSectionID'];
}

if ($ClassSectionID <= 0)
{
	echo 'error|*****|Unknown error, please try again.';
	exit;
}

$AllExamTypeList = array();
$AllExamTypeList = Exam::GetAllExamsForReportCard($ClassSectionID, false);

if (count($AllExamTypeList) <= 0)
{
	echo 'error|*****|No records found.';
	exit;
}

echo 'success|*****|';

echo '<div class="form-group">';
echo '<label for="Class" class="col-lg-2 control-label">Exam Types</label>';
echo'<div class="col-lg-8">';
echo '<label class="checkbox-inline"><input type="checkbox" id="CheckAllExams" name="chkCheckAll" value="1" checked="checked">All</label>';

foreach ($AllExamTypeList as $ExamTypeID => $ExamTypeDetails)
{
	echo '<label class="checkbox-inline"><input class="CheckAllExams" type="checkbox" name="chkExamTypes['. $ExamTypeID .']" value="1" checked="checked">'. $ExamTypeDetails['ExamType'] .'</label>';
}

echo '</div>';
echo '</div>';

exit;
?>
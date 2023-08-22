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

require_once('../classes/school_administration/class.classes.php');
require_once('../classes/class.validation.php');

$DayList = array();

$Counter = 0;
for ($i = 0; $i < 7; $i++) {
	$DayList[++$Counter] = jddayofweek($i, 1);
}

$Clean = array();

$Clean['DayID'] = 0;
$Clean['ClassID'] = 0;
$Clean['ClassSubjectID'] = 0;

$Clean['PeriodStartTime'] = '';
$Clean['PeriodEndTime'] = '';
$Clean['SelectedPeriodTimingID'] = 0;

if (isset($_POST['SelectedDayID']))
{
	$Clean['DayID'] = (int) $_POST['SelectedDayID'];
}

if (isset($_POST['SelectedClassID']))
{
	$Clean['ClassID'] = (int) $_POST['SelectedClassID'];
}

if (isset($_POST['SelectedSubjectID']))
{
	$Clean['ClassSubjectID'] = (int) $_POST['SelectedSubjectID'];
}

if (isset($_POST['SelectedPeriodStartTime']))
{
	$Clean['PeriodStartTime'] = strip_tags(trim($_POST['SelectedPeriodStartTime']));
}

if (isset($_POST['SelectedPeriodEndTime']))
{
	$Clean['PeriodEndTime'] = strip_tags(trim($_POST['SelectedPeriodEndTime']));
}

if (isset($_POST['SelectedPeriodTimingID']))
{
	$Clean['PeriodTimingID'] = strip_tags(trim($_POST['SelectedPeriodTimingID']));
}

if ($Clean['ClassID'] <= 0 || $Clean['ClassSubjectID'] <= 0 || !array_key_exists($Clean['DayID'], $DayList))
{
	echo 'error|*****|Unknown error, please try again.';
	exit;
}

$RecordValidator = new Validator();

try
{
	$CurrentClass = new AddedClass($Clean['ClassID']);

	$CurrentClass->FillAssignedSubjects();

	$ClassSubjectList = array();
	$ClassSubjectList = $CurrentClass->GetAssignedSubjects();
}
catch (ApplicationDBException $e)
{
    echo 'error|*****|Unknown error, please try again.';
	exit;
}
catch (Exception $e)
{
    echo 'error|*****|Unknown error, please try again.';
	exit;
}

if (!array_key_exists($Clean['ClassSubjectID'], $ClassSubjectList))
{
	echo 'error|*****|Unknown error, please try again.';
	exit;
}

if (!$RecordValidator->ValidateTime($Clean['PeriodStartTime'], 'Unknown error, please try again.'))
{
	echo 'error|*****|Unknown error, please try again.';
	exit;
}
else if (!$RecordValidator->ValidateTime($Clean['PeriodEndTime'], 'Unknown error, please try again.'))
{
	echo 'error|*****|Unknown error, please try again.';
	exit;
}

$ClassSubjectTeacherList = array();
$ClassSubjectTeacherList = AddedClass::GetClassSubjectTeachers($Clean['DayID'], $Clean['ClassID'], $Clean['ClassSubjectID'], $Clean['PeriodStartTime'], $Clean['PeriodEndTime'], $Clean['PeriodTimingID']);

if (count($ClassSubjectTeacherList) <= 0)
{
	echo 'error|*****|No records found.';
	exit;
}

echo 'success|*****|';

foreach ($ClassSubjectTeacherList as $TeacherClassID=>$ClassSubjectTeacherdetails)
{
	if ($ClassSubjectTeacherdetails['IsBusy'])
	{
		echo '<option value="'.$TeacherClassID.'">'.$ClassSubjectTeacherdetails['FirstName'].' '.$ClassSubjectTeacherdetails['LastName'].' (Already Assigned to Class : '.$ClassSubjectTeacherdetails['ClassSymbol']. 'Section : ' . $ClassSubjectTeacherdetails['SectionName'] . ')</option>';
		continue;
	}

	echo '<option value="'.$TeacherClassID.'">'.$ClassSubjectTeacherdetails['FirstName'].' '.$ClassSubjectTeacherdetails['LastName'].'</option>';
}

exit;
?>
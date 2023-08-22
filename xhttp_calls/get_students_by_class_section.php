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

require_once('../classes/school_administration/class.class_sections.php');
require_once('../classes/school_administration/class.students.php');
require_once('../classes/school_administration/class.student_details.php');

$ClassSectionID = 0;
$AcademicYearID = 0;

if (isset($_POST['SelectedClassSectionID']))
{
	$ClassSectionID = (int) $_POST['SelectedClassSectionID'];
}

if (isset($_POST['SelectedAcademicYearID']))
{
	$AcademicYearID = (int) $_POST['SelectedAcademicYearID'];
}

if ($ClassSectionID <= 0)
{
	echo 'error|*****|Unknown error, please try again.';
	exit;
}

$StudentsList = array();
$StudentsList = StudentDetail::GetStudentsByClassSectionID($ClassSectionID, 'Active', $AcademicYearID);

if (count($StudentsList) <= 0)
{
	echo 'error|*****|No Student found in this class for the academic year.';
	exit;
}

echo 'success|*****|';

foreach ($StudentsList as $StudentID=>$StudentDetails)
{
	
	echo '<option value="'.$StudentID.'">'.$StudentDetails['FirstName'].' '.$StudentDetails['LastName'].'('.$StudentDetails['RollNumber'].')</option>';	
}

exit;
?>
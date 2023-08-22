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

$StudentName = '';

if (isset($_POST['SelectedStudentName']))
{
	$StudentName = (string) $_POST['SelectedStudentName'];
}

if ($StudentName == '')
{
	echo 'error|*****|Unknown error, please try again.';
	exit;
}

$Filters = array();
$Filters['StudentName'] = $StudentName;

$StudentsList = array();
$StudentsList = StudentDetail::GetAllStudents($TotalRecords, false, $Filters, 0, 20);

if (count($StudentsList) <= 0)
{
	echo 'error|*****|No records found.';
	exit;
}

echo 'success|*****|';

foreach ($StudentsList as $StudentID => $StudentDetails)
{
	echo '<option id="' . $StudentID . '" value="' . $StudentDetails['FirstName'] . ' ' . $StudentDetails['LastName'] . '"></option>';	
}

exit;
?>
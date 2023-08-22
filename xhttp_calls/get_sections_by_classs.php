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

$ClassSectionsList = array();
$ClassSectionsList = ClassSections::GetClassSections($ClassID);

if (count($ClassSectionsList) <= 0)
{
	echo 'error|*****|No records found.';
	exit;
}

echo 'success|*****|';

foreach ($ClassSectionsList as $ClassSectionID=>$SectionMasterID)
{
	echo '<option value="'.$ClassSectionID.'">'.$SectionMasterID.'</option>';
}

exit;
?>
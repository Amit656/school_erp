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

require_once('../classes/school_administration/class.cities.php');

$StateID = 0;
$DistrictID = 0;

if (isset($_POST['SelectedStateID']))
{
	$StateID = (int) $_POST['SelectedStateID'];
}

if (isset($_POST['SelectedDistrictID']))
{
	$DistrictID = (int) $_POST['SelectedDistrictID'];
}

if ($StateID <= 0 && $DistrictID <=0)
{
	echo 'error|*****|Unknown error, please try again.';
	exit;
}

$AllCities = array();
$AllCities = City::GetAllCities($StateID, $DistrictID);

if (count($AllCities) <= 0)
{
	echo 'error|*****|No records found.';
	exit;
}

echo 'success|*****|';

foreach ($AllCities as $CityID => $CityName)
{
	echo '<option value="'. $CityID .'">'. $CityName .'</option>';
}

exit;
?>
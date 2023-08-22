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

require_once('../classes/transport_management/class.areawise_fee.php');

$RouteID = 0;

if (isset($_POST['SelectedRouteID']))
{
	$RouteID = (int) $_POST['SelectedRouteID'];
}

if (isset($_POST['SelectedAcademicYearID']))
{
	$AcademicYearID = (int) $_POST['SelectedAcademicYearID'];
}

if ($RouteID <= 0)
{
	echo 'error|*****|Unknown error, please try again.';
	exit;
}

$RouteAreasList = array();
$RouteAreasList = AreaWiseFee::GetRouteAreasByAcademicYear($RouteID, $AcademicYearID);

if (count($RouteAreasList) <= 0)
{
	echo 'error|*****|No area wise fee found in this academic year.';
	exit;
}

echo 'success|*****|';

foreach ($RouteAreasList as $AreaWiseFeeID => $AreaName)
{
	echo '<option value="'.$AreaWiseFeeID.'">'.$AreaName.'</option>';
}

exit;
?>
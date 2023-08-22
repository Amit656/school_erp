<?php
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

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
	echo 'error';
	exit;
}
catch (Exception $e)
{
	echo 'error';
	exit;
}

require_once('../classes/school_administration/class.countries.php');
require_once('../classes/school_administration/class.states.php');

$CountryID = 0;

if (isset($_POST['SelectedCountryID']))
{
	$CountryID = (int) $_POST['SelectedCountryID'];
}

if ($CountryID <= 0)
{
	echo 'error';
	exit;
}

try
{
	$CountryObj = new Country($CountryID);
}
catch (ApplicationDBException $e)
{
	header('location:../error_page.php');
	exit;
}
catch (Exception $e)
{
	header('location:../error_page.php');
	exit;
}

$StateList = array();
$StateList = State::GetAllStates($CountryID);

/*$String = 'selected="selected"';
if (!isset($_POST['Multiple']))
{
	$String = '';
	echo '<option value="0">Select</option>';
}*/

if (isset($StateList) && is_array($StateList) && count($StateList) > 0)
{
	foreach ($StateList as $StateID=>$StateName)
	{
		echo '<option value="'.$StateID.'">'.$StateName.'</option>';
	}
}

exit;
?>
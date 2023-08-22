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

require_once("../classes/hostel_management/class.rooms.php");

$WingID = 0;
$RoomTypeID = 0;

if (isset($_POST['SelectedWingID']))
{
	$WingID = (int) $_POST['SelectedWingID'];
}

if (isset($_POST['SelectedRoomTypeID']))
{
	$RoomTypeID = (int) $_POST['SelectedRoomTypeID'];
}

if ($WingID <= 0)
{
	echo 'error|*****|Unknown error, please try again.';
	exit;
}

$Filters['WingID'] = $WingID;
$Filters['RoomTypeID'] = $RoomTypeID;

$RoomList = array();
$RoomList = Room::SearchRooms($TotalRecords, false, $Filters);

if (count($RoomList) <= 0)
{
	echo 'error|*****|No rooms found.';
	exit;
}

echo 'success|*****|';

foreach($RoomList as $RoomID => $RoomDetails)
{
	echo '<option value="' . $RoomID . '">' . $RoomDetails['RoomName'] .' ( '. $RoomDetails['RoomType'] .' )' . '</option>';
}

exit;
?>
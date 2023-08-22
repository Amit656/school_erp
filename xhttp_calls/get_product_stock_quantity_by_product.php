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

require_once("../classes/inventory_management/class.products.php");

$ProductID = 0;

if (isset($_POST['ProductID']))
{
	$ProductID = (int) $_POST['ProductID']; 
}

if (isset($_GET['SelectedProductID']))
{
	$ProductID = (int) $_GET['SelectedProductID']; 
}

if (empty($ProductID))
{
	echo 'error|*****|Unknown error, please try again.';
	exit;
}

$ProductStockQuantity = 0;
$ProductStockQuantity = Product::GetAvailabileStockQuantity($ProductID);

echo "success|*****|Product Stock Quantity = ". $ProductStockQuantity;

exit;
?>
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

require_once("../classes/inventory_management/class.product_purchases.php");

$PurchaseID = 0;
$ProductID = 0;

$PurchasedProductsToSave = array();

if (isset($_POST['SelectedPurchaseID']))
{
	$PurchaseID = (int) $_POST['SelectedPurchaseID'];
}
if (isset($_POST['SelectedProductID']))
{
	$ProductID = (int) $_POST['SelectedProductID'];
}

if ($PurchaseID <= 0)
{
	echo 'error|*****|Unknown error, please try again.';
	exit;
}

try
{
	$ProductPurchaseToDetele = new ProductPurchase($PurchaseID);
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

if ($ProductPurchaseToDetele->ProductExists($ProductID))
{
	if (!$ProductPurchaseToDetele->DeleteProductFromPurchaseDetails($ProductID))
	{
		echo 'error|*****|Error in execution query.';
		exit;
	}
}

echo 'success|*****|';
echo 'Deleted successfully.';
exit;
?>
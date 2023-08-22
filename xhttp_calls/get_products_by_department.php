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

require_once("../classes/inventory_management/class.stock_issue.php");

$DepartmentID = '';

if (isset($_GET['SelectedDepartmentID']))
{
	$DepartmentID = (int) $_GET['SelectedDepartmentID']; 
}

if (empty($DepartmentID))
{
	echo 'error|*****|Unknown error, please try again.';
	exit;
}

$AllProductList = array();
$AllProductList = StockIssue::GetProductsByDepartmentID($DepartmentID);

if (count($AllProductList) <= 0)
{
	echo 'error|*****|No records found.';
	exit;
}

echo 'success|*****|';

foreach ($AllProductList as $ProductID => $ProductName)
{	
?>
	 <option value="<?php echo $ProductID;?>"><?php echo $ProductName; ?></option>
<?php
}

exit;
?>
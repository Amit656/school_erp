<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once("../../classes/inventory_management/class.product_categories.php");
require_once("../../classes/inventory_management/class.master_product_units.php");
require_once("../../classes/inventory_management/class.products.php");

require_once("../../includes/global_defaults.inc.php");

//1. RECHECK IF THE USER IS VALID //
try
{
	$AuthObject = new ApplicationAuthentication;
	$LoggedUser = new User(0, $AuthObject->CheckValidUser());
}

// THIS CATCH BLOCK BUBBLES THE EXCEPTION TO THE BUILT IN 'Exception' CLASS IF THERE ARE ANY UNCAUGHT ERRORS //
catch (ApplicationAuthException $e)
{
	header('location:unauthorized_login_admin.php');
	exit;
}
catch (Exception $e)
{
	header('location:unauthorized_login_admin.php');
	exit;
}
// END OF 1. //

if ($LoggedUser->HasPermissionForTask(TASK_LIST_PRODUCT) !== true)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}

$Clean = array();

$Clean['ProductID'] = 0;

if (isset($_GET['ProductID']))
{
    $Clean['ProductID'] = (int) $_GET['ProductID'];
}
elseif (isset($_POST['hdnProductID']))
{
    $Clean['ProductID'] = (int) $_POST['hdnProductID'];
}

if ($Clean['ProductID'] <= 0)
{
    header('location:../error.php');
    exit;
}   

try
{
    $ProductToEdit = new Product($Clean['ProductID']);
}
catch (ApplicationDBException $e)
{
    header('location:../error.php');
    exit;
}
catch (Exception $e)
{
    header('location:../error.php');
    exit;
}

$AllProductCategoryList = array();
$AllProductCategoryList = ProductCategory::GetActiveProductCategories();

$AllMasterProductUnitList = array();
$AllMasterProductUnitList = MasterProductUnit::GetActiveMasterProductUnits();

$AllProductTypeList = array('Perishable' => 'Perishable', 'NonPerishable' => 'Non Perishable');

$HasErrors = false;

$Clean['Process'] = 0;

$Clean['ProductCategoryID'] = 0;
$Clean['ProductName'] = '';

$Clean['ProductUnitID'] = 0;
$Clean['ProductUnitValue'] = '';
$Clean['ProductDescription'] = '';
$Clean['IsActive'] = 0;

if (isset($_POST['hdnProcess']))
{
	$Clean['Process'] = (int) $_POST['hdnProcess'];
}
else if (isset($_GET['Process']))
{
	$Clean['Process'] = (int) $_GET['Process'];
}
switch ($Clean['Process'])
{
	case 3:
		if (isset($_POST['drdProductCategory']))
		{
			$Clean['ProductCategoryID'] = (int) $_POST['drdProductCategory'];
		}

		if (isset($_POST['txtProductName']))
		{
			$Clean['ProductName'] = strip_tags(trim($_POST['txtProductName']));
		}

        if (isset($_POST['drdProductType']))
        {
            $Clean['ProductType'] = strip_tags(trim($_POST['drdProductType']));
        }

		if (isset($_POST['drdProductUnit']))
		{
			$Clean['ProductUnitID'] = (int) $_POST['drdProductUnit'];
		}

		if (isset($_POST['txtProductUnitValue']))
		{
			$Clean['ProductUnitValue'] = strip_tags(trim($_POST['txtProductUnitValue']));
		}

		if (isset($_POST['txtProductDescription']))
		{
			$Clean['ProductDescription'] = strip_tags(trim($_POST['txtProductDescription']));
		}
		
		if (isset($_POST['chkIsActive']))
        {
            $Clean['IsActive'] = 1;
        }

		$NewRecordValidator = new Validator();

		$NewRecordValidator->ValidateInSelect($Clean['ProductCategoryID'], $AllProductCategoryList, 'Unknown error, please try again.');	
		$NewRecordValidator->ValidateStrings($Clean['ProductName'], 'Product unit name is required and should be between 3 and 100 characters.', 3, 100);
        $NewRecordValidator->ValidateInSelect($Clean['ProductType'], $AllProductTypeList, 'Unknown error, please try again.');
		$NewRecordValidator->ValidateInSelect($Clean['ProductUnitID'], $AllMasterProductUnitList, 'Unknown error, please try again.');

		$NewRecordValidator->ValidateNumeric($Clean['ProductUnitValue'], 'Product unit value should be numeric.');

		if ($Clean['ProductDescription'] != '') 
		{
			$NewRecordValidator->ValidateStrings($Clean['ProductDescription'], 'Product description is required and should be between 3 and 500 characters.', 3, 500);
		}
		
		
		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}
				
		$ProductToEdit->SetProductCategoryID($Clean['ProductCategoryID']);
		$ProductToEdit->SetProductName($Clean['ProductName']);
        $ProductToEdit->SetProductType($Clean['ProductType']);
		$ProductToEdit->SetProductUnitID($Clean['ProductUnitID']);
		$ProductToEdit->SetProductUnitValue($Clean['ProductUnitValue']);
		$ProductToEdit->SetProductDescription($Clean['ProductDescription']);
		$ProductToEdit->SetIsActive($Clean['IsActive']);
		
		$ProductToEdit->SetCreateUserID($LoggedUser->GetUserID());

		if ($ProductToEdit->RecordExists())
        {
            $NewRecordValidator->AttachTextError('product you have added already exists.');
            $HasErrors = true;
            break;
        }

		if (!$ProductToEdit->Save())
		{
			$NewRecordValidator->AttachTextError(ProcessErrors($ProductToEdit->GetLastErrorCode()));
			$HasErrors = true;

			break;
		}
		
		header('location:products_list.php?Mode=ED');
		exit;
		break;

	case 2:
		$Clean['ProductCategoryID'] = $ProductToEdit->GetProductCategoryID();
		$Clean['ProductName'] = $ProductToEdit->GetProductName();
        $Clean['ProductType'] = $ProductToEdit->GetProductType();
		$Clean['ProductUnitID'] = $ProductToEdit->GetProductUnitID();
		$Clean['ProductUnitValue'] = $ProductToEdit->GetProductUnitValue();
		$Clean['ProductDescription'] = $ProductToEdit->GetProductDescription();
		$Clean['IsActive'] = $ProductToEdit->GetIsActive();
		break;
}

require_once('../html_header.php');
?>
<title>Edit Product</title>
</head>

<body>

    <div id="wrapper">
    	<!-- Navigation -->
        <nav class="navbar navbar-default navbar-static-top" role="navigation" style="margin-bottom: 0">
<?php 
			require_once('../site_header.php');
			require_once('../left_navigation_menu.php');
?>                    
            <!-- /.navbar-static-side -->
        </nav>

        <div id="page-wrapper">
            <div class="row">
                <div class="col-lg-12">
                    <h1 class="page-header">Edit Product</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="EditProduct" action="edit_product.php" method="post">
            	<div class="panel panel-default">
                    <div class="panel-heading">
                        Enter Product Details
                    </div>
                    <div class="panel-body">
<?php
						if ($HasErrors == true)
						{
							echo $NewRecordValidator->DisplayErrors();
						}
?>                    
						<div class="form-group">
                            <label for="ProductCategory" class="col-lg-3 control-label">Product Category</label>
                            <div class="col-lg-4">
                            	<select class="form-control" id="ProductCategory" name="drdProductCategory">
<?php
                                    foreach ($AllProductCategoryList as $ProductCategoryID => $ProductCategoryName)
                                    {
?>
                                        <option <?php echo ($Clean['ProductCategoryID'] == $ProductCategoryID) ? 'selected="selected"' : ''; ?> value="<?php echo $ProductCategoryID;?>"><?php echo $ProductCategoryName; ?></option>
<?php
                                    }
?>
                        		</select>
                            </div>
                        </div>
                    	<div class="form-group">
                            <label for="ProductName" class="col-lg-3 control-label">Product Name</label>
                            <div class="col-lg-4">
                            	<input class="form-control" type="text" maxlength="100" id="ProductName" name="txtProductName" value="<?php echo $Clean['ProductName']; ?>" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="ProductType" class="col-lg-3 control-label">Product Type</label>
                            <div class="col-lg-4">
                                <select class="form-control" id="ProductType" name="drdProductType">
<?php
                                foreach ($AllProductTypeList as $ProductType => $ProductTypeName)
                                {
?>
                                    <option <?php echo ($Clean['ProductType'] == $ProductType) ? 'selected="selected"' : ''; ?> value="<?php echo $ProductType;?>"><?php echo $ProductTypeName; ?></option>
<?php
                                }
?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="ProductUnitID" class="col-lg-3 control-label">Product Unit</label>
                            <div class="col-lg-4">
                            	<select class="form-control" id="ProductUnitID" name="drdProductUnit">
<?php
                                foreach ($AllMasterProductUnitList as $ProductUnitID => $ProductUnitName)
                                {
?>
                                    <option <?php echo ($Clean['ProductUnitID'] == $ProductUnitID) ? 'selected="selected"' : ''; ?> value="<?php echo $ProductUnitID;?>"><?php echo $ProductUnitName; ?></option>
<?php
                                }
?>
                        		</select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="ProductUnitValue" class="col-lg-3 control-label">Product Unit Value</label>
                            <div class="col-lg-4">
                            	<input class="form-control" type="text" maxlength="10" id="ProductUnitValue" name="txtProductUnitValue" value="<?php echo $Clean['ProductUnitValue']; ?>" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="ProductDescription" class="col-lg-3 control-label">Product Description</label>
                            <div class="col-lg-4">
                            	<textarea class="form-control" id="ProductDescription" maxlength="500" name="txtProductDescription"><?php echo $Clean['ProductDescription']; ?></textarea>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="IsActive" class="col-lg-3 control-label">Is Active</label>
                            <div class="col-lg-4">
                                <input type="checkbox" id="IsActive" name="chkIsActive" value="1" <?php echo ($Clean['IsActive'] == 1) ? 'checked="checked"' : ''; ?>/>
                            </div>
                        </div>
                        <div class="form-group">
                        <div class="col-sm-offset-3 col-lg-10">
                        	<input type="hidden" name="hdnProcess" value="3" />
                        	<input type="hidden" name="hdnProductID" value="<?php echo $Clean['ProductID'];?>" />
							<button type="submit" class="btn btn-primary">Save</button>
                        </div>
                      </div>
                    </div>
                </div>
            </form>
        </div>
        <!-- /#page-wrapper -->

    </div>
    <!-- /#wrapper -->
<?php
require_once('../footer.php');
?>
</body>
</html>
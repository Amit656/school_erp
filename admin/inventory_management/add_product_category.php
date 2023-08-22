<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once("../../classes/inventory_management/class.product_categories.php");

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

if ($LoggedUser->HasPermissionForTask(TASK_ADD_PRODUCT_CATEGORY) !== true)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}

$HasErrors = false;

$AllProductCategoryList = array();
$AllProductCategoryList = ProductCategory::GetActiveFirstLevelProductCategory();

$Clean = array();
$Clean['Process'] = 0;

$Clean['ParentCategoryID'] = 0;

if (isset($_GET['ParentCategory'])) 
{
	$Clean['ParentCategoryID'] = (int) $_GET['ParentCategory'];
}

$Clean['ProductCategoryName'] = '';

if (isset($_POST['hdnProcess']))
{
	$Clean['Process'] = (int) $_POST['hdnProcess'];
}
switch ($Clean['Process'])
{
	case 1:						
		if (isset($_POST['drdParentCategory']))
		{
			$Clean['ParentCategoryID'] = (int) $_POST['drdParentCategory'];
		}

		if (isset($_POST['txtProductCategoryName']))
		{
			$Clean['ProductCategoryName'] = strip_tags(trim($_POST['txtProductCategoryName']));
		}
		
		$NewRecordValidator = new Validator();

		if ($Clean['ParentCategoryID'] != 0)
		{
			$NewRecordValidator->ValidateInSelect($Clean['ParentCategoryID'], $AllProductCategoryList, 'Unknown error, please try again.');	
		}

		$NewRecordValidator->ValidateStrings($Clean['ProductCategoryName'], 'Product category name is required and should be between 3 and 30 characters.', 3, 30);
		
		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}
				
		$NewProductCategory = new ProductCategory();
		
		$NewProductCategory->SetParentCategoryID($Clean['ParentCategoryID']);				
		$NewProductCategory->SetProductCategoryName($Clean['ProductCategoryName']);
		$NewProductCategory->SetIsActive(1);
		
		$NewProductCategory->SetCreateUserID($LoggedUser->GetUserID());

		if ($NewProductCategory->RecordExists())
        {
            $NewRecordValidator->AttachTextError('Product category you have added already exists.');
            $HasErrors = true;
            break;
        }

		if (!$NewProductCategory->Save())
		{
			$NewRecordValidator->AttachTextError(ProcessErrors($NewProductCategory->GetLastErrorCode()));
			$HasErrors = true;

			break;
		}
		
		header('location:product_categories_list.php?Mode=ED');
		exit;
		break;
}

require_once('../html_header.php');
?>
<title>Add Product Category</title>
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
                    <h1 class="page-header">Add Product Category</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="AddProductCategory" action="add_product_category.php" method="post">
            	<div class="panel panel-default">
                    <div class="panel-heading">
                        Enter Product Category Details
                    </div>
                    <div class="panel-body">
<?php
						if ($HasErrors == true)
						{
							echo $NewRecordValidator->DisplayErrors();
						}
?>                    	
						<div class="form-group">
                            <label for="ProductCategory" class="col-lg-3 control-label">Parent Product Category</label>
                            <div class="col-lg-4">
                            	<select class="form-control" name="drdParentCategory">
                        			<option value="0">-- Select --</option>
<?php
                                    foreach ($AllProductCategoryList as $ProductCategoryID => $ParentProductCategoryName)
                                    {
?>
                                        <option <?php echo ($Clean['ParentCategoryID'] == $ProductCategoryID) ? 'selected="selected"' : ''; ?> value="<?php echo $ProductCategoryID;?>"><?php echo $ParentProductCategoryName; ?></option>
<?php
                                    }
?>
                        		</select>
                            </div>
                        </div>
                    	<div class="form-group">
                            <label for="ProductCategoryName" class="col-lg-3 control-label">Product Category Name</label>
                            <div class="col-lg-4">
                            	<input class="form-control" type="text" maxlength="30" id="ProductCategoryName" name="txtProductCategoryName" value="<?php echo $Clean['ProductCategoryName']; ?>" />
                            </div>
                        </div>
                        <div class="form-group">
	                        <div class="col-sm-offset-3 col-lg-10">
	                        	<input type="hidden" name="hdnProcess" value="1" />
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
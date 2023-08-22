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

if ($LoggedUser->HasPermissionForTask(TASK_EDIT_PRODUCT_CATEGORY) !== true)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}

$Clean = array();

$Clean['ProductCategoryID'] = 0;

if (isset($_GET['ProductCategoryID']))
{
    $Clean['ProductCategoryID'] = (int) $_GET['ProductCategoryID'];
}
elseif (isset($_POST['hdnProductCategoryID']))
{
    $Clean['ProductCategoryID'] = (int) $_POST['hdnProductCategoryID'];
}

if ($Clean['ProductCategoryID'] <= 0)
{
    header('location:../error.php');
    exit;
}   

try
{
    $ProductCategoryToEdit = new ProductCategory($Clean['ProductCategoryID']);
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

$HasErrors = false;

$AllProductCategoryList = array();
$AllProductCategoryList = ProductCategory::GetActiveFirstLevelProductCategory();

$Clean['Process'] = 0;

$Clean['ParentCategoryID'] = 0;

$Clean['ProductCategoryName'] = '';
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
		if (isset($_POST['drdParentCategory']))
		{
			$Clean['ParentCategoryID'] = (int) $_POST['drdParentCategory'];
		}

		if (isset($_POST['txtProductCategoryName']))
		{
			$Clean['ProductCategoryName'] = strip_tags(trim($_POST['txtProductCategoryName']));
		}

		if (isset($_POST['chkIsActive']))
        {
            $Clean['IsActive'] = 1;
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
		
		$ProductCategoryToEdit->SetParentCategoryID($Clean['ParentCategoryID']);				
		$ProductCategoryToEdit->SetProductCategoryName($Clean['ProductCategoryName']);
		$ProductCategoryToEdit->SetIsActive($Clean['IsActive']);
		
		$ProductCategoryToEdit->SetCreateUserID($LoggedUser->GetUserID());

		if ($ProductCategoryToEdit->RecordExists())
        {
            $NewRecordValidator->AttachTextError('Product category you have added already exists.');
            $HasErrors = true;
            break;
        }

		if (!$ProductCategoryToEdit->Save())
		{
			$NewRecordValidator->AttachTextError(ProcessErrors($ProductCategoryToEdit->GetLastErrorCode()));
			$HasErrors = true;

			break;
		}
		
		header('location:product_categories_list.php?Mode=UD');
		exit;
		break;

	case 2:
		$Clean['ParentCategoryID'] = $ProductCategoryToEdit->GetParentCategoryID();				
		$Clean['ProductCategoryName'] = $ProductCategoryToEdit->GetProductCategoryName();
		$Clean['IsActive'] = $ProductCategoryToEdit->GetIsActive();
		break;
}

require_once('../html_header.php');
?>
<title>Edit Product Category</title>
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
                    <h1 class="page-header">Edit Product Category</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="EditProductCategory" action="edit_product_category.php" method="post">
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
                        			<option value="0">Select-</option>
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
                            <label for="IsActive" class="col-lg-3 control-label">Is Active</label>
                            <div class="col-lg-4">
                                <input type="checkbox" id="IsActive" name="chkIsActive" value="1" <?php echo ($Clean['IsActive'] == 1) ? 'checked="checked"' : ''; ?>/>
                            </div> 
                        </div>
                        <div class="form-group">
	                        <div class="col-sm-offset-3 col-lg-10">
	                        	<input type="hidden" name="hdnProcess" value="3" />
	                        	<input type="hidden" name="hdnProductCategoryID" value="<?php echo $Clean['ProductCategoryID'];?>" />
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
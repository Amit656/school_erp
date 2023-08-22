<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");
require_once("../../classes/class.date_processing.php");

require_once("../../classes/inventory_management/class.product_vendors.php");
require_once("../../classes/inventory_management/class.product_categories.php");
require_once("../../classes/inventory_management/class.products.php");
require_once("../../classes/inventory_management/class.product_purchases.php");

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

if ($LoggedUser->HasPermissionForTask(TASK_ADD_PRODUCT_PURCHASE) !== true)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}

$AllProductVendorList = array();
$AllProductVendorList = ProductVendor::GetActiveProductVendors();

$AllProductCategoryList = array();
$AllProductCategoryList = ProductCategory::GetActiveProductCategories();

$AllProductList = array();

$KeyProductCategoryID = key($AllProductCategoryList);
$AllProductList = Product::GetProductsByProductCategoryID($KeyProductCategoryID);

$InputErrors = array();

$HasErrors = false;

$Clean = array();
$Clean['Process'] = 0;

$Clean['ProductVendorID'] = '';
$Clean['Description'] = '';
$Clean['PurchaseDate'] = '';

$Clean['PurchasedProductDetails'] = array();

if (isset($_POST['hdnProcess']))
{
	$Clean['Process'] = (int) $_POST['hdnProcess'];
}
switch ($Clean['Process'])
{
	case 1:
		if (isset($_POST['drdProductVendor']))
		{
			$Clean['ProductVendorID'] = (int) $_POST['drdProductVendor'];
		}

		if (isset($_POST['txtDescription']))
		{
			$Clean['Description'] = strip_tags(trim($_POST['txtDescription']));
		}
		if (isset($_POST['txtPurchaseDate']))
		{
			$Clean['PurchaseDate'] = strip_tags(trim($_POST['txtPurchaseDate']));
		}

		if (isset($_POST['PurchasedProductDetails']) && is_array($_POST['PurchasedProductDetails']))
		{
			$Clean['PurchasedProductDetails'] = $_POST['PurchasedProductDetails'];
		}

		$NewRecordValidator = new Validator();

		$NewRecordValidator->ValidateInSelect($Clean['ProductVendorID'], $AllProductVendorList, 'Unknown error, please try again.');
		$NewRecordValidator->ValidateStrings($Clean['Description'], 'Description is required and should be between 4 and 500 characters.', 4, 500);
		$NewRecordValidator->ValidateDate($Clean['PurchaseDate'], 'Please enter valid issue date.');

		$Counter = 0;

		$PurchasedProductsToSave = array();

		foreach ($Clean['PurchasedProductDetails'] as $PurchasedProductDetails)
		{
			$Counter++;

			$InputErrors[$Counter]['Rate'] = 0;
			$InputErrors[$Counter]['Quantity'] = 0;
			$InputErrors[$Counter]['Amount'] = 0;

			$ProductCategoryID = 0;
			$ProductID = 0;
			$Rate = 0;
			$Quantity = 0;

			if (isset($PurchasedProductDetails['ProductCategoryID']))
			{
				$ProductCategoryID = (int) $PurchasedProductDetails['ProductCategoryID'];
			}

			if (isset($PurchasedProductDetails['ProductID']))
			{
				$ProductID = (int) $PurchasedProductDetails['ProductID'];
			}

			if (isset($PurchasedProductDetails['Rate']))
			{
				$Rate = (float) $PurchasedProductDetails['Rate'];
			}

			if (isset($PurchasedProductDetails['Quantity']))
			{
				$Quantity = (int) $PurchasedProductDetails['Quantity'];
			}

			if ($Rate <= 0 && $Quantity <= 0)
			{
				continue;
			}

			if ($NewRecordValidator->ValidateInSelect($ProductCategoryID, $AllProductCategoryList, 'Unknown error, please try again.'))
			{
				$AllProductList = Product::GetProductsByProductCategoryID($ProductCategoryID);
				$NewRecordValidator->ValidateInSelect($ProductID, $AllProductList, 'Unknown error, please try again.');
			}

			if (!$NewRecordValidator->ValidateNumeric($Rate, 'Please enter a valid numaric value for <strong>rate</strong>', 1))
			{
				$InputErrors[$Counter]['Rate'] = 1;
			}

			if (!$NewRecordValidator->ValidateInteger($Quantity, 'Please enter a valid integer value for <strong>quantity</strong>', 1))
			{
				$InputErrors[$Counter]['Quantity'] = 1;
			}

			$PurchasedProductsToSave[$Counter]['ProductCategoryID'] = $ProductCategoryID;
			$PurchasedProductsToSave[$Counter]['ProductID'] = $ProductID;
			$PurchasedProductsToSave[$Counter]['Rate'] = $Rate;
			$PurchasedProductsToSave[$Counter]['Quantity'] = $Quantity;
			$PurchasedProductsToSave[$Counter]['Amount'] = $Rate * $Quantity;
		}

		if (count($PurchasedProductsToSave) <= 0)
		{
			$NewRecordValidator->AttachTextError('Please select atlast 1 product');
		}

		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}

		$NewProductPurchase = new ProductPurchase();

		$NewProductPurchase->SetProductVendorID($Clean['ProductVendorID']);
		$NewProductPurchase->SetDescription($Clean['Description']);
		$NewProductPurchase->SetPurchaseDate(date('Y-m-d', strtotime(DateProcessing::ToggleDateDayAndMonth($Clean['PurchaseDate']))));
		$NewProductPurchase->SetPurchasedProductDetails($Clean['PurchasedProductDetails']);
		$NewProductPurchase->SetIsActive(1);

		$NewProductPurchase->SetCreateUserID($LoggedUser->GetUserID());

		// if ($NewProductPurchase->RecordExists())
		//       {
		//           $NewRecordValidator->AttachTextError('Product you have added already exists.');
		//           $HasErrors = true;
		//           break;
		//       }

		if (!$NewProductPurchase->Save())
		{
			$NewRecordValidator->AttachTextError(ProcessErrors($NewProductPurchase->GetLastErrorCode()));
			$HasErrors = true;

			break;
		}

		header('location:product_purchases_list.php?Mode=ED');
		exit;
		break;

	default;
		for ($CounterForRowNumber = 1; $CounterForRowNumber <= 2; $CounterForRowNumber++)
		{
			$Clean['PurchasedProductDetails'][$CounterForRowNumber]['ProductCategoryID'] = 0;
			$Clean['PurchasedProductDetails'][$CounterForRowNumber]['ProductID'] = 0;
			$Clean['PurchasedProductDetails'][$CounterForRowNumber]['Rate'] = '';
			$Clean['PurchasedProductDetails'][$CounterForRowNumber]['Quantity'] = '';
			$Clean['PurchasedProductDetails'][$CounterForRowNumber]['Amount'] = '';

			// initializing Input Error Array
			$InputErrors[$CounterForRowNumber]['Rate'] = 0;
			$InputErrors[$CounterForRowNumber]['Quantity'] = 0;
			$InputErrors[$CounterForRowNumber]['Amount'] = 0;
		}
		break;
}

require_once('../html_header.php');
?>
<title>Add Product Purchase</title>
<link href="/admin/vendor/jquery-ui/jquery-ui.min.css" rel="stylesheet">
<!-- DataTables CSS -->
<link href="/admin/vendor/datatables-plugins/dataTables.bootstrap.css" rel="stylesheet">

<!-- DataTables Responsive CSS -->
<link href="/admin/vendor/datatables-responsive/dataTables.responsive.css" rel="stylesheet">
<style type="text/css">
	#AddProductSpecificationRow .input-style
	{
		width:100%;
	}
</style>
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
                    <h1 class="page-header">Add Product Purchase</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
			<form class="form-horizontal" name="AddProductPurchase" action="add_product_purchase.php" method="post">
				<div class="panel panel-default">
                    <div class="panel-heading">
                        Enter Product Vendor Details
                    </div>
                    <div class="panel-body">
						<?php
						if ($HasErrors == true)
						{
							echo $NewRecordValidator->DisplayErrors();
						}
						?>                    
						<div class="form-group">
                            <label for="ProductVendor" class="col-lg-3 control-label">Product Vendor</label>
                            <div class="col-lg-4">
								<select class="form-control" name="drdProductVendor">
									<?php
									foreach ($AllProductVendorList as $ProductVendorID => $VendorName)
									{
										?>
										<option <?php echo ($Clean['ProductVendorID'] == $ProductVendorID) ? 'selected="selected"' : ''; ?> value="<?php echo $ProductVendorID; ?>"><?php echo $VendorName; ?></option>
										<?php
									}
									?>
								</select>
                            </div>
                        </div>
						<div class="form-group">
                            <label for="Description" class="col-lg-3 control-label">Description</label>
                            <div class="col-lg-4">
								<textarea class="form-control" maxlength="500" id="Description" name="txtDescription"><?php echo $Clean['Description']; ?></textarea>
                            </div>
                        </div>

						<div class="form-group">
							<label for="PurchaseDate" class="col-lg-3 control-label">PurchaseDate</label>
                            <div class="col-lg-4">
								<input class="form-control dtepicker" type="text" maxlength="25" id="PurchaseDate" name="txtPurchaseDate" value="<?php echo $Clean['PurchaseDate']; ?>" />
                            </div>

						</div>
                    </div>
                </div>
                <div class="panel panel-default">
                    <div class="panel-heading">
                        Enter Product Purchase Details
                    </div>
                    <div class="panel-body">
						<div class="row" id="RecordTable">
							<div class="col-lg-12">
								<table width="100%" class="table table-striped table-bordered table-hover" id="DataTableRecords">
									<thead>
										<tr>
											<th>Product Category</th>
											<th>Product</th>
											<th>Rate</th>
											<th>Quantity</th>
											<th>Amount</th>
											<th>Action</th>
										</tr>
									</thead>
									<tbody id="AddProductSpecificationRow">
										<?php
										if (is_array($Clean['PurchasedProductDetails']) && count($Clean['PurchasedProductDetails']) > 0)
										{
											foreach ($Clean['PurchasedProductDetails'] as $Key => $PurchasedProductValue)
											{
												?>
												<tr>
													<td>
														<select class="form-control ProductCategory input-style" name="PurchasedProductDetails[<?php echo $Key; ?>][ProductCategoryID]">
															<?php
															foreach ($AllProductCategoryList as $ProductCategoryID => $ProductCategoryName)
															{
																?>
																<option <?php echo ($PurchasedProductValue['ProductCategoryID'] == $ProductCategoryID) ? 'selected="selected"' : ''; ?> value="<?php echo $ProductCategoryID; ?>"><?php echo $ProductCategoryName; ?></option>
																<?php
															}
															?>
														</select>
													</td>
													<td>
														<select class="form-control ProductList input-style" name="PurchasedProductDetails[<?php echo $Key; ?>][ProductID]">
															<?php
															if (!empty($PurchasedProductValue['ProductCategoryID']))
															{
																$AllProductList = Product::GetProductsByProductCategoryID($PurchasedProductValue['ProductCategoryID']);
															}

															foreach ($AllProductList as $ProductID => $ProductName)
															{
																?>
																<option <?php echo ($PurchasedProductValue['ProductID'] == $ProductID) ? 'selected="selected"' : ''; ?> value="<?php echo $ProductID; ?>"><?php echo $ProductName; ?></option>
																<?php
															}
															?>
														</select>
													</td>
													<td <?php echo($InputErrors[$Key]['Rate']) ? 'class="has-error"' : ''; ?>>
														<input class="form-control Rate" type="text" maxlength="7" id="Rate" name="PurchasedProductDetails[<?php echo $Key; ?>][Rate]" value="<?php echo ($PurchasedProductValue['Rate']) ? $PurchasedProductValue['Rate'] : '' ?>" /></td>
													</td>
													<td <?php echo($InputErrors[$Key]['Quantity']) ? 'class="has-error"' : ''; ?>>
														<input class="form-control Quantity" type="text" maxlength="7" id="Quantity" name="PurchasedProductDetails[<?php echo $Key; ?>][Quantity]" value="<?php echo ($PurchasedProductValue['Quantity']) ? $PurchasedProductValue['Quantity'] : '' ?>" /></td>
													<td <?php echo($InputErrors[$Key]['Amount']) ? 'class="has-error"' : ''; ?>>
														<input class="form-control Amount" type="text" maxlength="10" id="Amount" name="PurchasedProductDetails[<?php echo $Key; ?>][Amount]" value="<?php echo ($PurchasedProductValue['Amount']) ? $PurchasedProductValue['Amount'] : '' ?>" readonly="readonly" /></td>
													</td>
													<td>
														<button type="button" class="btn btn-danger RemoveRow" style="margin-right: 2%;"> <span class="icon icon-remove">X</span></button>
													</td>
												</tr>
												<?php
											}
										}
										?>
									</tbody>
								</table>
							</div>
						</div>
						<div class="form-group">
							<div class="col-lg-12 text-right">
								<button type="button" class="btn btn-success" id="AddMoreSpecifications">Add More</button>
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
	<!-- DataTables JavaScript -->
	<script src="/admin/vendor/datatables/js/jquery.dataTables.min.js"></script>
	<script src="/admin/vendor/datatables-plugins/dataTables.bootstrap.min.js"></script>
	<script src="/admin/vendor/datatables-responsive/dataTables.responsive.js"></script>	
	<script src="/admin/vendor/jquery-ui/jquery-ui.min.js"></script>	
	<script type="text/javascript">
		$ (document).ready (function ()
		{
			$ ('#AddMoreSpecifications').click (function ()
			{
				var rowCount = $ ('#DataTableRecords > tbody >tr').length;
				rowCount += 1;

				var dataColumn1 = '<select class="form-control ProductCategory input-style" name="PurchasedProductDetails[' + rowCount + '][ProductCategoryID]">'
<?php
foreach ($AllProductCategoryList as $ProductCategoryID => $ProductCategoryName)
{
	?>
					dataColumn1 += '<option value="<?php echo $ProductCategoryID; ?>"><?php echo $ProductCategoryName; ?></option>'
	<?php
}
?>
				dataColumn1 += '</select>'
				var dataColumn2 = '<select class="form-control ProductList input-style" name="PurchasedProductDetails[' + rowCount + '][ProductID]">'
<?php
$AllProductList = Product::GetProductsByProductCategoryID($KeyProductCategoryID);
foreach ($AllProductList as $ProductID => $ProductName)
{
	?>
					dataColumn2 += '<option value="<?php echo $ProductID; ?>"><?php echo $ProductName; ?></option>'
	<?php
}
?>
				dataColumn2 += '</select>'
				var dataColumn3 = '<input class="form-control Rate" type="text" maxlength="7" id="Rate" name="PurchasedProductDetails[' + rowCount + '][Rate]" value" />'
				var dataColumn4 = '<input class="form-control Quantity" type="text" maxlength="7" id="Quantity" name="PurchasedProductDetails[' + rowCount + '][Quantity]" value="" />'
				var dataColumn5 = '<input class="form-control Amount" type="text" maxlength="10" id="Amount" name="PurchasedProductDetails[' + rowCount + '][Amount]" value="" readonly="readonly"/>'
				var dataColumn6 = '<button type="button" class="btn btn-danger RemoveRow" style="margin-right: 2%;"> <span class="icon icon-remove">X</span></button>'

				// $('#AddProductSpecificationRow').append(data);
				$ ("#DataTableRecords").DataTable ().row.add ([dataColumn1, dataColumn2, dataColumn3, dataColumn4, dataColumn5, dataColumn6]).draw ();
			});

			$ ('body').on ("blur", '.Rate', function ()
			{
				var TotalQuantity = 0;
				var TotalRate = 0;
				var TotalAmount = 0;

				TotalQuantity = $ (this).closest ('tr').find ('.Quantity').val ();
				TotalRate = $ (this).closest ('tr').find ('.Rate').val ();

				$ (this).closest ('tr').find ('.Amount').val ('');

				if (TotalRate)
				{
					TotalAmount = CalculateProductAmount (TotalRate, TotalQuantity);

					if (TotalAmount > 0)
					{
						$ (this).closest ('tr').find ('.Amount').val (TotalAmount);
					}
				}
			});

			$ ('body').on ("blur", '.Quantity', function ()
			{
				var TotalQuantity = 0;
				var TotalRate = 0;
				var TotalAmount = 0;

				TotalQuantity = $ (this).closest ('tr').find ('.Quantity').val ();
				TotalRate = $ (this).closest ('tr').find ('.Rate').val ();

				$ (this).closest ('tr').find ('.Amount').val ('');

				if (TotalQuantity)
				{
					TotalAmount = CalculateProductAmount (TotalRate, TotalQuantity);

					if (TotalAmount > 0)
					{
						$ (this).closest ('tr').find ('.Amount').val (TotalAmount);
					}
				}
			});

			$ ('body').on ("change", '.ProductCategory', function ()
			{
				var CurrentTR = $ (this).closest ('tr');

				var ProductCategoryID = parseInt ($ (this).val ());

				if (ProductCategoryID <= 0)
				{
					alert ("Please Select Product Category");
					return false;
				}

				$.get ("/xhttp_calls/get_products_by_product_category.php", {SelectedProductCategoryID: ProductCategoryID}, function ( data )
				{
					ResultArray = data.split ("|*****|");

					if (ResultArray[0] == 'error')
					{
						alert (ResultArray[1]);
						CurrentTR.find ('.ProductList').html ('<option value="0">Select</option>')
						return false;
					}
					else
					{
						CurrentTR.find ('.ProductList').html (ResultArray[1]);
					}
				});
			});

			$ ('body').on ("click", '.RemoveRow', function ()
			{
				var row = $ (this).closest ("tr");
				var table = row.closest ('table').dataTable ();
				table.api ().row (row).remove ().draw ();
			});

			$ ('#DataTableRecords').DataTable ({
				responsive: true,
				bPaginate: false,
				bSort: false,
				searching: false,
				info: false
			});
			
			$ (".dtepicker").datepicker ({
				changeMonth: true,
				changeYear: true,
				dateFormat: 'dd/mm/yy'
			});
		});
		function CalculateProductAmount ( ProductRate, ProductQuantity )
		{
			var ProductAmount = ProductRate * ProductQuantity;
			return ProductAmount;
		}
	</script>
	<!-- JavaScript To Print A Report -->
</body>
</html>
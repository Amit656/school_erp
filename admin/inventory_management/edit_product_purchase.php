<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

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

if ($LoggedUser->HasPermissionForTask(TASK_EDIT_PRODUCT_PURCHASE) !== true)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}

$Clean = array();

$Clean['PurchaseID'] = 0;

if (isset($_GET['PurchaseID']))
{
	$Clean['PurchaseID'] = (int) $_GET['PurchaseID'];
}
elseif (isset($_POST['hdnPurchaseID']))
{
	$Clean['PurchaseID'] = (int) $_POST['hdnPurchaseID'];
}

if ($Clean['PurchaseID'] <= 0)
{
	header('location:../error.php');
	exit;
}

try
{
	$ProductPurchaseToEdit = new ProductPurchase($Clean['PurchaseID']);
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

//var_dump($ProductPurchaseToEdit);exit;

$AllProductVendorList = array();
$AllProductVendorList = ProductVendor::GetActiveProductVendors();

$AllProductCategoryList = array();
$AllProductCategoryList = ProductCategory::GetActiveProductCategories();

$AllProductList = array();

$KeyProductCategoryID = key($AllProductCategoryList);
$AllProductList = Product::GetProductsByProductCategoryID($KeyProductCategoryID);

$InputErrors = array();

$HasErrors = false;
$ViewOnly = false;

$Clean['Process'] = 0;

$Clean['ProductVendorID'] = '';
$Clean['Description'] = '';

$Clean['PurchasedProductDetails'] = array();

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
	case 2:
		$Clean['ProductSupplierID'] = $ProductPurchaseToEdit->GetProductVendorID();
		$Clean['Description'] = $ProductPurchaseToEdit->GetDescription();
		$Clean['PurchaseDate'] = $ProductPurchaseToEdit->GetPurchaseDate();

		$ProductPurchaseToEdit->FillPurchasedProductDetails();
		$Clean['PurchasedProductDetails'] = $ProductPurchaseToEdit->GetPurchasedProductDetails();

		foreach ($Clean['PurchasedProductDetails'] as $ProductPurchaseID => $PurchasedProductDetails)
		{
			$InputErrors[$ProductPurchaseID]['Rate'] = 0;
			$InputErrors[$ProductPurchaseID]['Quantity'] = 0;
			$InputErrors[$ProductPurchaseID]['Amount'] = 0;
		}
		break;
}

require_once('../html_header.php');
?>
<title>Edit Product Purchase</title>

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
                    <h1 class="page-header">Edit Product Purchase</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
			<form class="form-horizontal" name="EditProductPurchase" action="edit_product_purchase.php" method="post">
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
								<select class="form-control" name="drdProductVendor" disabled="disabled">
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
							<label for="PurchaseDate" class="col-lg-3 control-label">PurchaseDate</label>
                            <div class="col-lg-4">
								<input class="form-control dtepicker" type="text" maxlength="25" id="PurchaseDate" name="txtPurchaseDate" value="<?php echo $Clean['PurchaseDate']; ?>" disabled="disabled" />
                            </div>
						</div>
						<div class="form-group">
                            <label for="Description" class="col-lg-3 control-label">Description</label>
                            <div class="col-lg-4">
								<textarea class="form-control" maxlength="500" id="Description" name="txtDescription" disabled="disabled"><?php echo $Clean['Description']; ?></textarea>
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
											foreach ($Clean['PurchasedProductDetails'] as $PurchaseDetailID => $PurchasedProductValue)
											{
												$AllProductList = Product::GetProductsByProductCategoryID($PurchasedProductValue['ProductCategoryID']);
												?>
												<tr>
													<td>
														<select class="form-control ProductCategory input-style" name="PurchasedProductDetails[<?php echo $PurchaseDetailID; ?>][ProductCategoryID]" readonly="readonly">
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
														<select class="form-control ProductList input-style" name="PurchasedProductDetails[<?php echo $PurchaseDetailID; ?>][ProductID]" readonly="readonly">
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
													<td>
														<input class="form-control Rate" type="text" maxlength="7" name="PurchasedProductDetails[<?php echo $PurchaseDetailID; ?>][Rate]" value="<?php echo ($PurchasedProductValue['Rate']) ? $PurchasedProductValue['Rate'] : '' ?>" />
													</td>

													<td>
														<input class="form-control Quantity" type="text" maxlength="7" name="PurchasedProductDetails[<?php echo $PurchaseDetailID; ?>][Quantity]" value="<?php echo ($PurchasedProductValue['Quantity']) ? $PurchasedProductValue['Quantity'] : '' ?>" />
													</td>
													<td>
														<input class="form-control Amount" type="text" maxlength="10" name="PurchasedProductDetails[<?php echo $PurchaseDetailID; ?>][Amount]" value="<?php echo ($PurchasedProductValue['Amount']) ? $PurchasedProductValue['Amount'] : '' ?>" readonly="readonly" />
													</td>

													<td style="width: 90px;">
														<button type="button" class="btn btn-danger RemoveRow" PurchaseID="<?php echo $Clean['PurchaseID']; ?>" PurchaseDetailID="<?php echo $PurchaseDetailID; ?>"><span class="fa fa-trash"></span></button>
														<button type="button" class="btn btn-primary UpdateRow" PurchaseID="<?php echo $Clean['PurchaseID']; ?>" PurchaseDetailID="<?php echo $PurchaseDetailID; ?>"><span class="fa fa-save"></span></button>
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
                    </div>
                </div>
            </form>
        </div>
        <!-- /#page-wrapper -->

    </div>
    <!-- /#wrapper -->
	<?php
	require_once('../footer.php');
	if (isset($_GET['ViewOnly']))
	{
		$ViewOnly = true;
	}
	?>
	<!-- DataTables JavaScript -->
	<script src="/admin/vendor/datatables/js/jquery.dataTables.min.js"></script>
	<script src="/admin/vendor/datatables-plugins/dataTables.bootstrap.min.js"></script>
	<script src="/admin/vendor/datatables-responsive/dataTables.responsive.js"></script>	
	<script src="/admin/vendor/jquery-ui/jquery-ui.min.js"></script>	
	<script type="text/javascript">
		$ (function ()
		{
			var ViewOnly = '<?php echo $ViewOnly; ?>';

			if (ViewOnly)
			{
				$ ('input, select, textarea, button[type="button"]').prop ('disabled', true);
				$ ('#Check').hide ();
				$ ('button[type="submit"]').text ('Close').attr ('onClick', 'window.close();');

			}

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
				var dataColumn6 = '<button type="button" class="btn btn-danger RemoveRow" PurchaseID="<?php echo $Clean['PurchaseID']; ?>" PurchaseDetailID="<?php echo "0"; ?>"><span class="fa fa-trash"></span></button> '
				dataColumn6 += '<button type="button" class="btn btn-primary UpdateRow" PurchaseID="<?php echo $Clean['PurchaseID']; ?>" PurchaseDetailID="<?php echo "0"; ?>"><span class="fa fa-save"></span></button>'

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

				var PurchaseID = $ (this).attr ("PurchaseID");
				var ProductID = row.find ('.ProductList').val ();

				$.post ("/xhttp_calls/delete_product_purchase.php", {SelectedPurchaseID: PurchaseID, SelectedProductID: ProductID}, function ( data )
				{
					ResultArray = data.split ("|*****|");

					if (ResultArray[0] == 'error')
					{
						alert (ResultArray[1]);
						return false;
					}
					else
					{
						alert (ResultArray[1]);
					}
				});
				var table = row.closest ('table').dataTable ();
				table.api ().row (row).remove ().draw ();
			});

			$ ('body').on ("click", '.UpdateRow', function ()
			{
				var current_row = $ (this).closest ("tr");

				var PurchaseID = $ (this).attr ("PurchaseID");
				var PurchaseDetailID = $ (this).attr ("PurchaseDetailID");

				if (PurchaseID <= 0)
				{
					alert ('Unknown error, please try again.');
					return false;
				}

				var ProductID = current_row.find ('.ProductList').val ();

				if (ProductID <= 0)
				{
					alert ('Unknown error, please try again.');
					return false;
				}

				var Rate = current_row.find ('.Rate').val ();

				if (Rate <= 0)
				{
					alert ('Please enter rate.');
					return false;
				}

				var Quantity = current_row.find ('.Quantity').val ();

				if (Quantity <= 0)
				{
					alert ('Please enter rate.');
					return false;
				}

				$.post ("/xhttp_calls/update_product_purchase.php", {SelectedPurchaseID: PurchaseID, SelectedPurchaseDetailID: PurchaseDetailID, SelectedProductID: ProductID, SelectedRate: Rate, SelectedQuantity: Quantity}, function ( data )
				{
					ResultArray = data.split ("|*****|");

					if (ResultArray[0] == 'error')
					{
						alert (ResultArray[1]);
						return false;
					}
					else
					{
						alert (ResultArray[1]);
					}
				});
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
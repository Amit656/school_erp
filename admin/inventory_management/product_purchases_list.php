<?php
require_once("../../classes/class.users.php");
require_once('../../classes/class.validation.php');
require_once("../../classes/class.authentication.php");
require_once("../../classes/class.ui_helpers.php");

require_once("../../classes/inventory_management/class.product_vendors.php");
require_once("../../classes/inventory_management/class.product_categories.php");
require_once("../../classes/inventory_management/class.products.php");
require_once("../../classes/inventory_management/class.product_purchases.php");

require_once("../../includes/helpers.inc.php");
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

if ($LoggedUser->HasPermissionForTask(TASK_LIST_PRODUCT_PURCHASE) !== true)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}

$AllProductVendorList = array();
$AllProductVendorList = ProductVendor::GetActiveProductVendors();

$AllProductCategoryList = array();
$AllProductCategoryList = ProductCategory::GetActiveProductCategories();

$AllProductList = array();
//$AllProductList = Product::GetProductsByProductCategoryID(key($AllProductCategoryList));

$AllProductPurchases = array();
$HasErrors = false;

$Clean = array();
$Clean['Process'] = 0;
$Clean['VendorID'] = 0;
$Clean['VoucherNumber'] = '';
$Clean['ProductCategoryID'] = 0;
$Clean['ProductID'] = 0;
$Clean['FromDate'] = '';
$Clean['ToDate'] = '';

// paging and sorting variables start here  //
$Clean['CurrentPage'] = 1;
$TotalPages = 0;

$Start = 0;
$Limit = 100;
// end of paging variables //

if (isset($_GET['Process']))
{
	$Clean['Process'] = (int) $_GET['Process'];
}
elseif (isset($_GET['hdnProcess']))
{
	$Clean['Process'] = (int) $_GET['hdnProcess'];
}
switch ($Clean['Process'])
{
	case 5:
		if ($LoggedUser->HasPermissionForTask(TASK_DELETE_PRODUCT_PURCHASE) !== true)
		{
			header('location:unauthorized_login_admin.php');
			exit;
		}

		if (isset($_GET['PurchaseID']))
		{
			$Clean['PurchaseID'] = (int) $_GET['PurchaseID'];
		}

		if ($Clean['PurchaseID'] <= 0)
		{
			header('location:../error.php');
			exit;
		}

		try
		{
			$ProductPurchaseToDelete = new ProductPurchase($Clean['PurchaseID']);
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

		$RecordValidator = new Validator();

		// if ($ProductPurchaseToDelete->CheckDependencies())
		// {
		// 	$RecordValidator->AttachTextError('This master product unit cannot be deleted. There are dependent records for this master product unit.');
		// 	$HasErrors = true;
		// 	break;
		// }

		if (!$ProductPurchaseToDelete->Remove())
		{
			$RecordValidator->AttachTextError(ProcessErrors($ProductPurchaseToDelete->GetLastErrorCode()));
			$HasErrors = true;
			break;
		}

		header('location:product_purchases_list.php?Mode=DD');
		break;

	case 7;
		if (isset($_GET['drdVendorID']))
		{
			$Clean['VendorID'] = (int) $_GET['drdVendorID'];
		}
		elseif (isset($_GET['VendorID']))
		{
			$Clean['VendorID'] = (int) $_GET['VendorID']; 
		}
		
		if (isset($_GET['txtVoucherNumber']))
		{
			$Clean['VoucherNumber'] = strip_tags(trim($_GET['txtVoucherNumber']));
		}
		elseif (isset($_GET['VoucherNumber']))
		{
			$Clean['VoucherNumber'] = strip_tags(trim($_GET['VoucherNumber']));
		}

		if (isset($_GET['drdProductCategoryID']))
		{
			$Clean['ProductCategoryID'] = (int) $_GET['drdProductCategoryID'];
		}
		elseif (isset($_GET['ProductCategoryID']))
		{
			$Clean['ProductCategoryID'] = (int) $_GET['ProductCategoryID'];
		}

		if (isset($_GET['drdProductID']))
		{
			$Clean['ProductID'] = (int) $_GET['drdProductID'];
		}
		elseif (isset($_GET['ProductID']))
		{
			$Clean['ProductID'] = (int) $_GET['ProductID'];
		}

		if (isset($_GET['txtFromDate']))
		{
			$Clean['FromDate'] = strip_tags(trim($_GET['txtFromDate']));
		}
		elseif (isset($_GET['FromDate']))
		{
			$Clean['FromDate'] = strip_tags(trim($_GET['FromDate']));
		}

		if (isset($_GET['txtToDate']))
		{
			$Clean['ToDate'] = strip_tags(trim($_GET['txtToDate']));
		}
		elseif (isset($_GET['ToDate']))
		{
			$Clean['ToDate'] = strip_tags(trim($_GET['ToDate']));
		}

		$RecordValidator = new Validator();

		if ($Clean['VendorID'] != 0)
		{
			$RecordValidator->ValidateInSelect($Clean['VendorID'], $AllProductVendorList, 'Unknown Error, Please try again.');
		}
		
		if ($Clean['VoucherNumber'] != '')
		{
			$RecordValidator->ValidateStrings($Clean['VoucherNumber'], 'Voucher number is required and should be between 1 and 15 characters.', 1, 15);
		}

		if ($Clean['ProductCategoryID'] != 0)
		{
			$RecordValidator->ValidateInSelect($Clean['ProductCategoryID'], $AllProductCategoryList, 'Unknown Error, Please try again.');
		}

		if ($Clean['ProductID'] != 0)
		{
			$AllProductList = Product::GetProductsByProductCategoryID($Clean['ProductCategoryID']);
			$RecordValidator->ValidateInSelect($Clean['ProductID'], $AllProductList, 'Unknown Error, Please try again.');
		}
		
		if ($Clean['FromDate'] != '')
		{
			$RecordValidator->ValidateDate($Clean['FromDate'], 'Please enter valid from date.');
		}
		
		if ($Clean['ToDate'] != '')
		{
			$RecordValidator->ValidateDate($Clean['ToDate'], 'Please enter valid to date.');
		}

		if ($RecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}

		//set record filters
		$Filters['VendorID'] = $Clean['VendorID'];
		$Filters['VoucherNumber'] = $Clean['VoucherNumber'];
		$Filters['ProductCategoryID'] = $Clean['ProductCategoryID'];
		$Filters['ProductID'] = $Clean['ProductID'];
		
		if ($Clean['FromDate'] != '' && $Clean['ToDate'] != '')
		{
			$Filters['FromDate'] = date('Y-m-d', strtotime(DateProcessing::ToggleDateDayAndMonth(($Clean['FromDate']))));
			$Filters['ToDate'] = date('Y-m-d', strtotime(DateProcessing::ToggleDateDayAndMonth(($Clean['ToDate']))));
		}
		
		//var_dump($Filters);exit;
		

		//get records count
		ProductPurchase::GetAllProductPurchases($TotalRecords, true, $Filters);

		if ($TotalRecords > 0)
		{
			// Paging and sorting calculations start here.
			$TotalPages = (($TotalRecords % $Limit) == 0) ? $TotalRecords / $Limit : floor($TotalRecords / $Limit) + 1;

			if (isset($_GET['CurrentPage']))
			{
				$Clean['CurrentPage'] = (int) $_GET['CurrentPage'];
			}

			if ($Clean['CurrentPage'] <= 0)
			{
				$Clean['CurrentPage'] = 1;
			}
			elseif ($Clean['CurrentPage'] > $TotalPages)
			{
				$Clean['CurrentPage'] = $TotalPages;
			}

			if ($Clean['CurrentPage'] > 1)
			{
				$Start = ($Clean['CurrentPage'] - 1) * $Limit;
			}

			// end of Paging and sorting calculations.
			// now get the actual  records
			$AllProductPurchases = ProductPurchase::GetAllProductPurchases($TotalRecords, false, $Filters, $Start, $Limit);
		}
		break;
}

require_once('../html_header.php');
?>
<title>Product Purchase List</title>
<!-- DataTables CSS -->
<link href="/admin/vendor/datatables-plugins/dataTables.bootstrap.css" rel="stylesheet">

<!-- DataTables Responsive CSS -->
<link href="/admin/vendor/datatables-responsive/dataTables.responsive.css" rel="stylesheet">

<link href="../vendor/jquery-ui/jquery-ui.min.css" rel="stylesheet">
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
                    <h1 class="page-header">Product Purchase List</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
            <form class="form-horizontal" name="SearchProductPurchase" action="product_purchases_list.php" method="get">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        Filter
                    </div>
					<?php
					if ($HasErrors == true)
					{
						echo $RecordValidator->DisplayErrorsInTable();
					}
					?>
                    <div class="panel-body">
						<div class="form-group">
                            <label for="ProductVendor" class="col-lg-2 control-label">Product Vendor</label>
                            <div class="col-lg-4">
                            	<select class="form-control" name="drdVendorID">
									<option value="0">-- Select --</option>
<?php
								foreach ($AllProductVendorList as $VendorID => $VendorName)
								{
?>
									<option <?php echo ($Clean['VendorID'] == $VendorID) ? 'selected="selected"' : ''; ?> value="<?php echo $VendorID; ?>"><?php echo $VendorName; ?></option>
<?php
								}
?>
                        		</select>
                            </div>
							<label for="VoucherNumber" class="col-lg-2 control-label">Voucher No</label>
                            <div class="col-lg-4">
                                <input class="form-control" type="text" maxlength="25" id="VoucherNumber" name="txtVoucherNumber" value="<?php echo $Clean['VoucherNumber']; ?>" />
                            </div>
                        </div>
						<div class="form-group">
                            <label for="ProductCategory" class="col-lg-2 control-label">Product Category</label>
                            <div class="col-lg-4">
                                <select class="form-control" name="drdProductCategoryID" id="ProductCategory">
                                    <option value="0">-- Select --</option>
									<?php
									foreach ($AllProductCategoryList as $ProductCategoryID => $ProductCategoryName)
									{
										?>
	                                    <option <?php echo ($Clean['ProductCategoryID'] == $ProductCategoryID) ? 'selected="selected"' : ''; ?> value="<?php echo $ProductCategoryID; ?>"><?php echo $ProductCategoryName; ?></option>
										<?php
									}
									?>
                                </select>
                            </div>
                            <label for="Product" class="col-lg-2 control-label">Product</label>
                            <div class="col-lg-4">
                                <select class="form-control" name="drdProductID" id="Product">
                                    <option value="0">-- Select --</option>
									<?php
									foreach ($AllProductList as $ProductID => $ProductName)
									{
										?>
	                                    <option <?php echo ($Clean['ProductID'] == $ProductID) ? 'selected="selected"' : ''; ?> value="<?php echo $ProductID; ?>"><?php echo $ProductName; ?></option>
										<?php
									}
									?>
                                </select>

                            </div>
                        </div>                        
                        <div class="form-group">
                            <label for="FromDate" class="col-lg-2 control-label">From Date</label>
                            <div class="col-lg-4">
                                <input class="form-control dtepicker" type="text" name="txtFromDate" value="<?php echo $Clean['FromDate']; ?>"/>  
                            </div>
                            <label for="ToDate" class="col-lg-2 control-label">To Date</label>
                            <div class="col-lg-4">
                                <input class="form-control dtepicker" type="text" name="txtToDate" value="<?php echo $Clean['ToDate']; ?>"/>    
                            </div>
                        </div>
						
                        
                        <div class="form-group">
                            <div class="col-sm-offset-2 col-lg-10">
                                <input type="hidden" name="hdnProcess" value="7" />
                                <button type="submit" class="btn btn-primary">Search</button>
                            </div>
						</div>
                    </div>  
                </div>
            </form>
            <!-- /.row -->
			<?php
			if ($Clean['Process'] == 7 && $HasErrors == false)
			{
				?>
	            <div class="row">
	                <div class="col-lg-12">
	                    <div class="panel panel-default">
	                        <div class="panel-heading">
	                            <strong>Total Records Returned: <?php echo $TotalRecords; ?></strong>
	                        </div>
	                        <!-- /.panel-heading -->
	                        <div class="panel-body">
	                            <div>
	                                <div class="row">
	                                    <div class="col-lg-6">
											<div class="add-new-btn-container"><a href="add_product_purchase.php" class="btn btn-primary<?php echo $LoggedUser->HasPermissionForTask(TASK_ADD_PRODUCT_PURCHASE) === true ? '' : ' disabled'; ?>" role="button">Add New Product Purchase</a></div>
	                                    </div>
	                                    <div class="col-lg-6">
	                                        <div class="print-btn-container"><button id="PrintButton" type="submit" class="btn btn-primary">Print</button></div>
	                                    </div>
	                                </div>
	                                <br/>
	                                <div class="row">
										<div class="col-lg-6">
											<?php
											if ($TotalPages > 1)
											{
												$AllParameters = $Filters;
												$AllParameters['Process'] = '7';

												echo UIHelpers::GetPager('product_purchases_list.php', $TotalPages, $Clean['CurrentPage'], $AllParameters);
											}
											?>                                        
	                                    </div>
	                                    <div class="col-lg-6"></div>  
	                                </div>
	                                <div class="row" id="RecordTableHeading">
	                                    <div class="col-lg-12">
	                                        <div class="report-heading-container"><strong>Product Vendors on <?php echo date('d-m-Y h:i A'); ?></strong></div>
	                                    </div>
	                                </div>
	                                <div class="row" id="RecordTable">
	                                    <div class="col-lg-12">
	                                        <table width="100%" class="table table-striped table-bordered table-hover" id="DataTableRecords">
	                                            <thead>
                                                <tr>
                                                    <th>S. No</th>
                                                    <th>Product Vendor Name</th>
                                                    <th>Voucher Number</th>
                                                    <th>Purchase Date</th>
                                                    <th>Is Active</th>
                                                    <th>Create User</th>
                                                    <th>Create Date</th>
                                                    <th class="print-hidden">Operations</th>
                                                </tr>
                                            </thead>
	                                            <tbody>
												<?php
												if (is_array($AllProductPurchases) && count($AllProductPurchases) > 0)
												{
													$Counter = 0;
													foreach ($AllProductPurchases as $PurchaseID => $ProductPurchaseDetails)
													{
														?>
														<tr>
															<td><?php echo ++$Counter; ?></td>
															<td><?php echo $ProductPurchaseDetails['VendorName']; ?></td>
															<td><?php echo $ProductPurchaseDetails['VoucherNumber']; ?></td>
															<td><?php echo $ProductPurchaseDetails['PurchaseDate']; ?></td>
															<td><?php echo ($ProductPurchaseDetails['IsActive']) ? 'Yes' : 'No' ?></td>
															<td><?php echo $ProductPurchaseDetails['CreateUserName']; ?></td>
															<td><?php echo date('d/m/Y', strtotime($ProductPurchaseDetails['CreateDate'])); ?></td>
															<td class="print-hidden">
																<?php
																if ($LoggedUser->HasPermissionForTask(TASK_EDIT_PRODUCT_PURCHASE) === true)
																{
																	echo '<a href="edit_product_purchase.php?Process=2&amp;PurchaseID=' . $PurchaseID . '">Edit</a>';
																}
																else
																{
																	echo 'Edit';
																}

																echo '&nbsp;|&nbsp;';

																if ($LoggedUser->HasPermissionForTask(TASK_DELETE_PRODUCT_PURCHASE) === true)
																{
																	echo '<a class="delete-record" href="product_purchases_list.php?Process=5&amp;PurchaseID=' . $PurchaseID . '">Delete</a>';
																}
																else
																{
																	echo 'Delete';
																}

																echo '&nbsp;|&nbsp;';

																if ($LoggedUser->HasPermissionForTask(TASK_VIEW_PRODUCT_PURCHASE_DETAILS) === true)
																{
																	echo '<a href="edit_product_purchase.php?Process=2&amp;ViewOnly=true&amp;PurchaseID=' . $PurchaseID . '" target="_blank">View</a>';
																}
																else
																{
																	echo 'View';
																}
																?>
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
	                            </div>
	                            <!-- /.table-responsive -->
	                        </div>
	                        <!-- /.panel-body -->
	                    </div>
	                    <!-- /.panel -->
	                </div>
	                <!-- /.col-lg-12 -->
	            </div>
				<?php
			}
			?>
        </div>
        <!-- /#page-wrapper -->

    </div>
    <!-- /#wrapper -->
	<?php
	require_once('../footer.php');
	if (PrintMessage($_GET, $Message))
	{
		?>
		<script type="text/javascript">
			alert ('<?php echo $Message; ?>');
		</script>
		<?php
	}
	?>
	<script src="/admin/vendor/jquery-ui/jquery-ui.min.js"></script>
	<!-- DataTables JavaScript -->
	<script src="/admin/vendor/datatables/js/jquery.dataTables.min.js"></script>
	<script src="/admin/vendor/datatables-plugins/dataTables.bootstrap.min.js"></script>
	<script src="/admin/vendor/datatables-responsive/dataTables.responsive.js"></script>	
	<script type="text/javascript">
		$ (document).ready (function ()
		{
			$ (".dtepicker").datepicker ({
				changeMonth: true,
				changeYear: true,
				dateFormat: 'dd/mm/yy'
			});
			
			$ ("body").on ('click', '.delete-record', function ()
			{
				if (! confirm ("Are you sure you want to delete this product Vendor?"))
				{
					return false;
				}
			});

			$ ('#DataTableRecords').DataTable ({
				responsive: true,
				bPaginate: false,
				bSort: false,
				searching: false,
				info: false
			});
			
			$ ('#ProductCategory').change (function ()
			{
				var ProductCategoryID = parseInt ($ (this).val ());

				if (ProductCategoryID <= 0)
				{
					$ ('#Product').html ('<option value="0">-- Select  --</option>');
					return false;
				}

				$.get ("/xhttp_calls/get_products_by_product_category.php", {SelectedProductCategoryID: ProductCategoryID}, function ( data )
				{
					ResultArray = data.split ("|*****|");

					if (ResultArray[0] == 'error')
					{
						alert (ResultArray[1]);
						$ ('#Product').html ('<option value="0">-- Select --</option>');
						return false;
					}
					else
					{
						$ ('#Product').html ('<option value="0">-- Select --</option>' + ResultArray[1]);
					}
				});
			});
		});
	</script>
	<!-- JavaScript To Print A Report -->
	<script src="/admin/js/print-report.js"></script>
</body>
</html>
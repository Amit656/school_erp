<?php
require_once("../../classes/class.users.php");
require_once('../../classes/class.validation.php');
require_once("../../classes/class.authentication.php");
require_once("../../classes/class.ui_helpers.php");


require_once("../../classes/inventory_management/class.product_categories.php");
require_once("../../classes/inventory_management/class.products.php");

require_once("../../classes/inventory_management/class.master_product_units.php");

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

$AllProducts = array();

$AllProductCategoryList = array();
$AllProductCategoryList = ProductCategory::GetActiveProductCategories();

$AllProductTypeList = array('Perishable' => 'Perishable', 'NonPerishable' => 'Non Perishable');

$AllMasterProductUnitList = array();
$AllMasterProductUnitList = MasterProductUnit::GetActiveMasterProductUnits();

$HasErrors = false;

$Clean = array();
$Clean['Process'] = 0;
$Clean['ProductCategoryID'] = 0;
$Clean['ProductName'] = '';
$Clean['ProductType'] = '';
$Clean['ProductUnitID'] = 0;

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
		if ($LoggedUser->HasPermissionForTask(TASK_DELETE_PRODUCT) !== true)
		{
			header('location:unauthorized_login_admin.php');
			exit;
		}

		if (isset($_GET['ProductID']))
		{
			$Clean['ProductID'] = (int) $_GET['ProductID'];
		}

		if ($Clean['ProductID'] <= 0)
		{
			header('location:../error.php');
			exit;
		}

		try
		{
			$ProductToDelete = new Product($Clean['ProductID']);
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

		// if ($ProductToDelete->CheckDependencies())
		// {
		// 	$RecordValidator->AttachTextError('This master product unit cannot be deleted. There are dependent records for this master product unit.');
		// 	$HasErrors = true;
		// 	break;
		// }

		if (!$ProductToDelete->Remove())
		{
			$RecordValidator->AttachTextError(ProcessErrors($ProductToDelete->GetLastErrorCode()));
			$HasErrors = true;
			break;
		}

		header('location:products_list.php?Mode=DD');
		break;

	case 7;
		
		if (isset($_GET['drdProductCategoryID']))
		{
			$Clean['ProductCategoryID'] = (int) $_GET['drdProductCategoryID'];
		}
		elseif (isset($_GET['ProductCategoryID']))
		{
			$Clean['ProductCategoryID'] = (int) $_GET['ProductCategoryID'];
		}
		
		if (isset($_GET['txtProductName']))
		{
			$Clean['ProductName'] = strip_tags(trim($_GET['txtProductName'])) ;
		}
		elseif (isset($_GET['txtProductName']))
		{
			$Clean['ProductName'] = strip_tags(trim($_GET['txtProductName'])) ;
		}
		
		if (isset($_GET['drdProductType']))
		{
			$Clean['ProductType'] = strip_tags(trim($_GET['drdProductType'])) ;
		}
		elseif (isset($_GET['ProductType']))
		{
			$Clean['ProductType'] = strip_tags(trim($_GET['drdProductType'])) ;
		}

		if (isset($_GET['drdProductUnit']))
		{
			$Clean['ProductUnitID'] = strip_tags(trim($_GET['drdProductUnit']));
		}
		elseif (isset($_GET['ProductUnitID']))
		{
			$Clean['ProductUnitID'] = strip_tags(trim($_GET['ProductUnitID']));
		}

		$RecordValidator = new Validator();

		if ($Clean['ProductCategoryID'] != 0)
		{
			$RecordValidator->ValidateInSelect($Clean['ProductCategoryID'], $AllProductCategoryList, 'Unknown Error, Please try again.');
		}

		if ($Clean['ProductName'] != '')
		{
			$RecordValidator->ValidateStrings($Clean['ProductName'], 'Product name is required and should be between 3 and 100 characters.', 3, 100);
		}

		if ($Clean['ProductType'] != '')
		{
			$RecordValidator->ValidateInSelect($Clean['ProductType'], $AllProductTypeList, 'Unknown error, please try again.');
		}

		if ($Clean['ProductUnitID'] != 0)
		{
			$RecordValidator->ValidateInSelect($Clean['ProductUnitID'], $AllMasterProductUnitList, 'Unknown error, please try again.');
		}

		if ($RecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}

		//set record filters
		$Filters['ProductCategoryID'] = $Clean['ProductCategoryID'];
		$Filters['ProductName'] = $Clean['ProductName'];
		$Filters['ProductType'] = $Clean['ProductType'];
		$Filters['ProductUnitID'] = $Clean['ProductUnitID'];
		
		//get records count
		Product::GetAllProducts($TotalRecords, true, $Filters);

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
			$AllProducts = Product::GetAllProducts($TotalRecords, false, $Filters, $Start, $Limit);
		}
		break;
}

require_once('../html_header.php');
?>
<title>Product List</title>
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
                    <h1 class="page-header">Product List</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
            <form class="form-horizontal" name="SearchProductPurchase" action="products_list.php" method="get">
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
                            <label for="ProductName" class="col-lg-2 control-label">Product Name</label>
                            <div class="col-lg-4">
								<input class="form-control" type="text" maxlength="100" id="ProductName" name="txtProductName" value="<?php echo $Clean['ProductName']; ?>" />
                            </div>
                        </div> 
						<div class="form-group">
                            <label for="ProductType" class="col-lg-2 control-label">Product Type</label>
                            <div class="col-lg-4">
								<select class="form-control" id="ProductType" name="drdProductType">
									<option value="">-- Select --</option>
									<?php
									foreach ($AllProductTypeList as $ProductType => $ProductTypeName)
									{
										?>
										<option <?php echo ($Clean['ProductType'] == $ProductType) ? 'selected="selected"' : ''; ?> value="<?php echo $ProductType; ?>"><?php echo $ProductTypeName; ?></option>
										<?php
									}
									?>
								</select>
                            </div>
							<label for="ProductUnitID" class="col-lg-2 control-label">Product Unit</label>
                            <div class="col-lg-4">
								<select class="form-control" id="ProductUnitID" name="drdProductUnit">
									<option value="0">-- Select --</option>
									<?php
									foreach ($AllMasterProductUnitList as $ProductUnitID => $ProductUnitName)
									{
										?>
	                                    <option <?php echo ($Clean['ProductUnitID'] == $ProductUnitID) ? 'selected="selected"' : ''; ?> value="<?php echo $ProductUnitID; ?>"><?php echo $ProductUnitName; ?></option>
										<?php
									}
									?>
								</select>
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
											<div class="add-new-btn-container"><a href="add_product.php" class="btn btn-primary<?php echo $LoggedUser->HasPermissionForTask(TASK_ADD_PRODUCT) === true ? '' : ' disabled'; ?>" role="button">Add New Product</a></div>
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

												echo UIHelpers::GetPager('products_list.php', $TotalPages, $Clean['CurrentPage'], $AllParameters);
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
														<th>Product Category Name</th>
														<th>Product Name</th>
														<th>Product Type</th>
														<th>Product Unit Name</th>
														<th>Product Unit Value</th>
														<th>Stock Quantity</th>
														<th>Is Active</th>
														<th>Create User</th>
														<th>Create Date</th>
														<th class="print-hidden">Operations</th>
													</tr>
												</thead>
												<tbody>
													<?php
													if (is_array($AllProducts) && count($AllProducts) > 0)
													{
														$Counter = 0;
														foreach ($AllProducts as $ProductID => $ProductDetails)
														{
															?>
															<tr>
																<td><?php echo ++$Counter; ?></td>
																<td><?php echo $ProductDetails['ProductCategoryName']; ?></td>
																<td><?php echo $ProductDetails['ProductName']; ?></td>
																<td><?php echo ($ProductDetails['ProductType'] == 'Perishable') ? 'Perishable' : 'Non Perishable'; ?></td>
																<td><?php echo $ProductDetails['ProductUnitName']; ?></td>
																<td><?php echo $ProductDetails['ProductUnitValue']; ?></td>
																<td><?php echo $ProductDetails['StockQuantity']; ?></td>
																<td><?php echo ($ProductDetails['IsActive']) ? 'Yes' : 'No' ?></td>
																<td><?php echo $ProductDetails['CreateUserName']; ?></td>
																<td><?php echo date('d/m/Y', strtotime($ProductDetails['CreateDate'])); ?></td>
																<td class="print-hidden">
																	<?php
																	if ($LoggedUser->HasPermissionForTask(TASK_EDIT_PRODUCT) === true)
																	{
																		echo '<a href="edit_product.php?Process=2&amp;ProductID=' . $ProductID . '">Edit</a>';
																	}
																	else
																	{
																		echo 'Edit';
																	}

																	echo '&nbsp;|&nbsp;';

																	if ($LoggedUser->HasPermissionForTask(TASK_DELETE_PRODUCT) === true)
																	{
																		echo '<a class="delete-record" href="products_list.php?Process=5&amp;ProductID=' . $ProductID . '">Delete</a>';
																	}
																	else
																	{
																		echo 'Delete';
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
			});
	</script>
	<!-- JavaScript To Print A Report -->
	<script src="/admin/js/print-report.js"></script>
</body>
</html>
<?php
require_once("../../classes/class.users.php");
require_once('../../classes/class.validation.php');
require_once("../../classes/class.authentication.php");
require_once("../../classes/class.ui_helpers.php");
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

if ($LoggedUser->HasPermissionForTask(TASK_AVAILABLE_STOCK_REPORT) !== true)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}

require_once("../../classes/inventory_management/class.product_categories.php");
require_once("../../classes/inventory_management/class.products.php");
require_once("../../classes/inventory_management/class.departments.php");
require_once("../../classes/school_administration/class.branch_staff.php");
require_once("../../classes/inventory_management/class.stock_issue.php");

$IssueTypeList = array('Staff' => 'Staff', 'Department' => 'Department');

$AllDepartmentList = array();
$AllDepartmentList = Department::GetActiveDepartments();

$StaffCategoryList = array('Teaching' => 'Teaching Staff', 'NonTeaching' => 'Non Teaching Staff');

$AllBranchStaffList = array();
$AllBranchStaffList = BranchStaff::GetActiveBranchStaff('Teaching');

$AllProductCategoryList = array();
$AllProductCategoryList = ProductCategory::GetActiveProductCategories();

$AllProductList = array();
$AllProductList = Product::GetProductsByProductCategoryID(key($AllProductCategoryList));

$AllStockIssued = array();

$HasErrors = false;

$Clean = array();
$Clean['Process'] = 0;



$Clean['IssueType'] = '';
$Clean['DepartmentID'] = 0;

$Clean['StaffCategory'] = '';
$Clean['BranchStaffID'] = 0;

$Clean['ProductCategoryID'] = 0;
$Clean['ProductID'] = 0;

$Clean['IssueDate'] = DATE('d/m/Y');
$Clean['ReturnDate'] = DATE('d/m/Y');

// paging and sorting variables start here  //
$Clean['CurrentPage'] = 1;
$TotalPages = 0;

$Start = 0;
$Limit = 1;
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
	case 7: #Search Case

		if (isset($_GET['drdProductCategory']))
		{
			$Clean['ProductCategoryID'] = (int) $_GET['drdProductCategory'];
		}

		if (isset($_GET['drdProduct']))
		{
			$Clean['ProductID'] = (int) $_GET['drdProduct'];
		}

		if (isset($_GET['txtIssueDate']))
		{
			$Clean['IssueDate'] = strip_tags(trim($_GET['txtIssueDate']));
		}

		if (isset($_GET['txtReturnDate']))
		{
			$Clean['ReturnDate'] = strip_tags(trim($_GET['txtReturnDate']));
		}
		//var_dump($Clean);exit;

		$NewRecordValidator = new Validator();

		if ($Clean['ProductCategoryID'] != '')
		{
			$NewRecordValidator->ValidateInSelect($Clean['ProductCategoryID'], $AllProductCategoryList, 'Unknown error, please try again.');
			$AllProductList = Product::GetProductsByProductCategoryID($Clean['ProductCategoryID']);

			if ($Clean['ProductID'] != '')
			{
				$NewRecordValidator->ValidateInSelect($Clean['ProductID'], $AllProductList, 'Unknown error, please try again.');
			}
		}
		
		$NewRecordValidator->ValidateDate($Clean['IssueDate'], 'Please enter valid issue date.');
		$NewRecordValidator->ValidateDate($Clean['ReturnDate'], 'Please enter valid return  date.');

		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}

		//set record filters
		$Filters['ProductCategoryID'] = $Clean['ProductCategoryID'];
		$Filters['ProductID'] = $Clean['ProductID'];

		if ($Clean['IssueDate'] != '')
		{
			$Filters['IssueDate'] = date('Y-m-d', strtotime(DateProcessing::ToggleDateDayAndMonth(($Clean['IssueDate']))));
		}
		if ($Clean['ReturnDate'] != '')
		{
			$Filters['ReturnDate'] = date('Y-m-d', strtotime(DateProcessing::ToggleDateDayAndMonth(($Clean['ReturnDate']))));
		}
		
		//get records count
		
		Product::GetAllProducts($TotalRecords, true, $Filters);
		
		$AllProducts = Product::GetAllProducts($TotalRecords, false, $Filters, 0, $TotalRecords);
		
		$AllStock = StockIssue::GetAllTransaction($TotalRecords, true, $Filters);

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
			$AllStock = StockIssue::GetAllTransaction($TotalRecords, false, $Filters, $Start, $Limit);

			//var_dump($AllStockIssued);exit;
		}
		break;
}

require_once('../html_header.php');
?>
<title>Inventory Report</title>
<link href="../vendor/jquery-ui/jquery-ui.min.css" rel="stylesheet">
<!-- DataTables CSS -->
<link href="/admin/vendor/datatables-plugins/dataTables.bootstrap.css" rel="stylesheet">

<!-- DataTables Responsive CSS -->
<link href="/admin/vendor/datatables-responsive/dataTables.responsive.css" rel="stylesheet">
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
                    <h1 class="page-header">Inventory Report</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
            <!-- /.row -->
            <form class="form-horizontal" name="SearchDepartmentStockIssues" action="inventory_report.php" method="get">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        Filter
                    </div>
                    <div class="panel-body">
						<?php
						if ($HasErrors == true)
						{
							echo $NewRecordValidator->DisplayErrors();
						}
						?>
                        <div class="form-group">
                            <label for="ProductCategory" class="col-lg-2 control-label">Product Category</label>
                            <div class="col-lg-4">
                                <select class="form-control" name="drdProductCategory" id="ProductCategory">
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
                                <select class="form-control" name="drdProduct" id="Product">
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
                            <label for="IssueDate" class="col-lg-2 control-label">Start Date</label>
                            <div class="col-lg-4">
                                <input class="form-control dtepicker" type="text" name="txtIssueDate" value="<?php echo $Clean['IssueDate']; ?>"/>  
                            </div>
                            <label for="ReturnDate" class="col-lg-2 control-label">End Date</label>
                            <div class="col-lg-4">
                                <input class="form-control dtepicker" type="text" name="txtReturnDate" value="<?php echo $Clean['ReturnDate']; ?>"/>    
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
			<?php
			if ($Clean['Process'] == 7 && $HasErrors == false)
			{
				?>
	            <div class="row">
	                <div class="col-lg-12">
	                    <div class="panel panel-default">
	                        <div class="panel-heading">
	                            <strong>Total Records Returned: <?php echo count($AllProducts); ?></strong>
	                        </div>
	                        <!-- /.panel-heading -->
	                        <div class="panel-body">
	                            <div>
	                                <div class="row">
	                                    <div class="col-lg-6">
	                                        <div class="print-btn-container"><button id="PrintButton" type="submit" class="btn btn-primary">Print</button></div>
	                                    </div>
	                                </div>
	                                <br>
	                                <div class="row">
										<div class="col-lg-6">
											<?php
											if ($TotalPages > 1)
											{
												$AllParameters = $Filters;
												$AllParameters['Process'] = '7';

												echo UIHelpers::GetPager('inventory_report.php', $TotalPages, $Clean['CurrentPage'], $AllParameters);
											}
											?>                                        
	                                    </div>
	                                    <div class="col-lg-6"></div>  
	                                </div>
	                                <div class="row" id="RecordTableHeading">
	                                    <div class="col-lg-12">
	                                        <div class="report-heading-container"><strong>Report on <?php echo date('d-m-Y h:i A'); ?></strong></div>
	                                    </div>
	                                </div>
	                                <div class="row" id="RecordTable">
	                                    <div class="col-lg-12">
	                                        <table style="width:100%;" class="table table-striped table-bordered table-hover" align="center">
	                                            <thead>
	                                                <tr>
	                                                    <th>S. No</th>
	                                                    <th>Product Category Name</th>
	                                                    <th>Product Name</th>
	                                                    <th>Product Type</th>
	                                                    <th>Opening Stock</th>
	                                                    <th>Purchase Quantity</th>
	                                                    <th>Issued Quantity</th>
	                                                    <th>Returned Quantity</th>
	                                                    <th>Closing Stock</th>
	                                                </tr>
	                                            </thead>
	                                            <tbody>
													<?php
													
													if (is_array($AllProducts) && count($AllProducts) > 0)
													{
														$Counter = 0;
														foreach ($AllProducts as $ProductID => $ProductDetails)
														{
															$OpeningStock = 0;
															$ClosingStock = 0;
															
															if (isset($AllStock['Purchase'][$ProductID]))
															{
																$OpeningStock += $AllStock['Purchase'][$ProductID];
															}
															if (isset($AllStock['Return'][$ProductID]))
															{
																$OpeningStock += $AllStock['Return'][$ProductID];
															}
															if (isset($AllStock['Issue'][$ProductID]))
															{
																$OpeningStock -= $AllStock['Issue'][$ProductID];
															}
															
															$ClosingStock += $OpeningStock;
															
															
															
															if (isset($AllStock['PurchaseBetween'][$ProductID]))
															{
																$ClosingStock += $AllStock['PurchaseBetween'][$ProductID];
															}
															if (isset($AllStock['ReturnBetween'][$ProductID]))
															{
																$ClosingStock += $AllStock['ReturnBetween'][$ProductID];
															}
															if (isset($AllStock['IssueBetween'][$ProductID]))
															{
																$ClosingStock -= $AllStock['IssueBetween'][$ProductID];
															}
															?>
															<tr>
																<td><?php echo ++$Counter; ?></td>
																<td><?php echo $ProductDetails['ProductCategoryName']; ?></td>
																<td><?php echo $ProductDetails['ProductName']; ?></td>
																<td><?php echo ($ProductDetails['ProductType'] == 'Perishable') ? 'Perishable' : 'Non Perishable'; ?></td>
																<td><?php echo $OpeningStock; ?>
																<td><?php echo isset($AllStock['PurchaseBetween'][$ProductID]) ? $AllStock['PurchaseBetween'][$ProductID] : 0; ?>
																<td><?php echo isset($AllStock['IssueBetween'][$ProductID]) ? $AllStock['IssueBetween'][$ProductID] : 0; ?>
																<td><?php echo isset($AllStock['ReturnBetween'][$ProductID]) ? $AllStock['ReturnBetween'][$ProductID] : 0; ?>
																<td><?php echo $ClosingStock; ?>
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
<?php
require_once("../../classes/class.users.php");
require_once('../../classes/class.validation.php');
require_once("../../classes/class.authentication.php");
require_once("../../includes/helpers.inc.php");
require_once("../../includes/global_defaults.inc.php");
require_once("../../classes/class.date_processing.php");

require_once("../../classes/inventory_management/class.stock_issue.php");
require_once("../../classes/inventory_management/class.stock_return.php");
require_once("../../classes/school_administration/class.branch_staff.php");
require_once("../../classes/inventory_management/class.product_categories.php");
require_once("../../classes/inventory_management/class.products.php");

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

if ($LoggedUser->HasPermissionForTask(TASK_LIST_STOCK_RETURN) !== true)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}

$HasErrors = false;

$StaffCategoryList = array('Teaching' => 'Teaching Staff', 'NonTeaching' => 'Non Teaching Staff');

$AllProductCategoryList = array();
$AllProductCategoryList = ProductCategory::GetActiveProductCategories();

$AllProductList = array();

$AllBranchStaffList = array();

$AllStockIssues = array();

$Clean = array();
$Clean['Process'] = 0;

$Clean['StaffCategory'] = '';
$Clean['BranchStaff'] = 0;
$Clean['VoucherNumber'] = '';
$Clean['Description'] = '';
$Clean['ProductCategoryID'] = 0;
$Clean['ProductID'] = 0;
$Clean['FromDate'] = '';
$Clean['ToDate'] = '';

// paging and sorting variables start here  //
$Clean['AllRecords'] = '';
$Clean['CurrentPage'] = 1;
$TotalPages = 0;

$Start = 0;
$Limit = 100;
// end of paging variables //

if (isset($_GET['Process']))
{
	$Clean['Process'] = (int) $_GET['Process'];
}
if (isset($_GET['hdnProcess']))
{
	$Clean['Process'] = (int) $_GET['hdnProcess'];
}
switch ($Clean['Process'])
{
	case 5:
		if ($LoggedUser->HasPermissionForTask(TASK_DELETE_STOCK_RETURN) !== true)
		{
			header('location:unauthorized_login_admin.php');
			exit;
		}

		if (isset($_GET['StockReturnID']))
		{
			$Clean['StockReturnID'] = (int) $_GET['StockReturnID'];
		}

		if ($Clean['StockReturnID'] <= 0)
		{
			header('location:../error.php');
			exit;
		}

		try
		{
			$StockReturnToDelete = new StockReturn($Clean['StockReturnID']);
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

		// if ($StockReturnToDelete->CheckDependencies())
		// {
		// 	$RecordValidator->AttachTextError('This master product unit cannot be deleted. There are dependent records for this master product unit.');
		// 	$HasErrors = true;
		// 	break;
		// }
		
		

		if (!$StockReturnToDelete->Remove())
		{
			$RecordValidator->AttachTextError(ProcessErrors($StockReturnToDelete->GetLastErrorCode()));
			$HasErrors = true;
			break;
		}
		
		header('location:stock_return_list.php?Mode=DD');
		break;
		
	case 7;
		if (isset($_GET['drdStaffCategory']))
		{
			$Clean['StaffCategory'] = strip_tags(trim($_GET['drdStaffCategory']));
		}
		elseif (isset($_GET['StaffCategory']))
		{
			$Clean['StaffCategory'] = strip_tags(trim($_GET['StaffCategory']));
		}

		if (isset($_GET['drdBranchStaff']))
		{
			$Clean['BranchStaff'] = (int) $_GET['drdBranchStaff'];
		}
		elseif (isset($_GET['drdBranchStaff']))
		{
			$Clean['drdBranchStaff'] = (int) $_GET['BranchStaff'];
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
		
		if (isset($_GET['txtVoucherNumber']))
		{
			$Clean['VoucherNumber'] = strip_tags(trim($_GET['txtVoucherNumber']));
		}
		elseif (isset($_GET['VoucherNumber']))
		{
			$Clean['VoucherNumber'] = strip_tags(trim($_GET['VoucherNumber']));
		}

		if (isset($_GET['txtDescription']))
		{
			$Clean['Description'] = strip_tags(trim($_GET['txtDescription']));
		}
		elseif (isset($_GET['Description']))
		{
			$Clean['Description'] = strip_tags(trim($_GET['Description']));
		}

		$RecordValidator = new Validator();

		if ($Clean['StaffCategory'] != '')
		{
			$RecordValidator->ValidateInSelect($Clean['StaffCategory'], $StaffCategoryList, 'Unknown error, please try again.');
		}
		
		$AllBranchStaffList = BranchStaff::GetActiveBranchStaff($Clean['StaffCategory']);

		if ($Clean['BranchStaff'] > 0)
		{
			$RecordValidator->ValidateInSelect($Clean['BranchStaff'], $AllBranchStaffList, 'Unknown error, please try again.');
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
		$Filters['StaffCategory'] = $Clean['StaffCategory'];
		$Filters['BranchStaffID'] = $Clean['BranchStaff'];
		
		$Filters['ProductCategoryID'] = $Clean['ProductCategoryID'];
		$Filters['ProductID'] = $Clean['ProductID'];

		if ($Clean['FromDate'] != '' && $Clean['ToDate'] != '')
		{
			$Filters['FromDate'] = date('Y-m-d', strtotime(DateProcessing::ToggleDateDayAndMonth(($Clean['FromDate']))));
			$Filters['ToDate'] = date('Y-m-d', strtotime(DateProcessing::ToggleDateDayAndMonth(($Clean['ToDate']))));
		}
		
		

		StockReturn::GetAllStockReturn($TotalRecords, true, $Filters);

		// Paging and sorting calculations start here.
		if ($TotalRecords > 0)
		{
			$TotalPages = (($TotalRecords % $Limit) == 0) ? $TotalRecords / $Limit : floor($TotalRecords / $Limit) + 1;

			if (isset($_GET['CurrentPage']))
			{
				$Clean['CurrentPage'] = (int) $_GET['CurrentPage'];
			}

			if (isset($_GET['AllRecords']))
			{
				$Clean['AllRecords'] = (string) $_GET['AllRecords'];
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
			// now get the actual records			
			if ($Clean['AllRecords'] == 'All')
			{
				$AllStockIssues = StockReturn::GetAllStockReturn($TotalRecords, false, $Filters, 0, $TotalRecords);
			}
			else
			{
				$AllStockIssues = StockReturn::GetAllStockReturn($TotalRecords, false, $Filters, $Start, $Limit);
			}
		}
	break;
}

require_once('../html_header.php');
?>
<title>Stock Return List</title>
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
                    <h1 class="page-header">Stock Returned List</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
            <!-- /.row -->
			<form class="form-horizontal" name="SearchStockIssue" action="stock_return_list.php" method="get">
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
							<label for="StaffCategory" class="col-lg-2 control-label">Staff Category</label>
							<div class="col-lg-4">
								<select class="form-control" name="drdStaffCategory" id="StaffCategory">
									<option value="">-- Select --</option>
									<?php
									foreach ($StaffCategoryList as $StaffCategory => $StaffCategoryName)
									{
										?>
										<option <?php echo ($StaffCategory == $Clean['StaffCategory'] ? 'selected="selected"' : ''); ?> value="<?php echo $StaffCategory; ?>"><?php echo $StaffCategoryName; ?></option>
										<?php
									}
									?>
								</select>
							</div>
							<label for="BranchStaff" class="col-lg-2 control-label">Branch Staff</label>
							<div class="col-lg-4">
								<select class="form-control"  name="drdBranchStaff" id="BranchStaffID">
									<option value="0">-- Select --</option>
									<?php
									foreach ($AllBranchStaffList as $BranchStaffID => $BranchStaffName)
									{
										?>
										<option <?php echo ($BranchStaffID == $Clean['BranchStaff'] ? 'selected="selected"' : ''); ?> value="<?php echo $BranchStaffID; ?>"><?php echo $BranchStaffName['FirstName'] . " " . $BranchStaffName['LastName']; ?></option>
										<?php
									}
									?>
								</select>
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
            <?php 
			if ($Clean['Process'] == 7 && $HasErrors == false)
			{
			?>
			<div class="row">
                <div class="col-lg-12">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <strong>Total Records Returned: <?php echo count($AllStockIssues); ?></strong>
                        </div>
                        <!-- /.panel-heading -->
                        <div class="panel-body">
                            <div>
                                <div class="row">
                                    <div class="col-lg-6">
										<div class="add-new-btn-container"><a href="add_stock_return.php" class="btn btn-primary<?php echo $LoggedUser->HasPermissionForTask(TASK_ADD_STOCK_RETURN) === true ? '' : ' disabled'; ?>" role="button">Add New Stock Return</a></div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="print-btn-container"><button id="PrintButton" type="submit" class="btn btn-primary">Print</button></div>
                                    </div>
                                </div>
								<?php
								if ($HasErrors == true)
								{
									echo $RecordValidator->DisplayErrorsInTable();
								}
								?>
                                <div class="row" id="RecordTableHeading">
                                    <div class="col-lg-12">
										<div class="report-heading-container"><strong>Stock Returned on <?php echo date('d-m-Y h:i A'); ?></strong></div>
                                    </div>
								</div>
                                <div class="row" id="RecordTable">
                                    <div class="col-lg-12">
                                        <table width="100%" class="table table-striped table-bordered table-hover" id="DataTableRecords">
                                            <thead>
                                                <tr>
                                                    <th>S. No</th>
                                                    <th>Issue Type</th>
                                                    <th>Returned By</th>
                                                    <th>Create User</th>
                                                    <th>Create Date</th>
                                                    <th class="print-hidden">Operations</th>
                                                </tr>
                                            </thead>
                                            <tbody>
												<?php
												if (is_array($AllStockIssues) && count($AllStockIssues) > 0)
												{
													$Counter = 0;
													foreach ($AllStockIssues as $StockIssueID => $StockIssueDetails)
													{
														?>
		                                                <tr>
		                                                    <td><?php echo ++$Counter; ?></td>
		                                                    <td><?php echo $StockIssueDetails['IssueType']; ?></td>
		                                                    <td><?php echo (isset($StockIssueDetails['DepartmentName']) ? $StockIssueDetails['DepartmentName'] : $StockIssueDetails['BranchStaffName']) ?></td>
		                                                    <td><?php echo $StockIssueDetails['CreateUserName']; ?></td>
		                                                    <td><?php echo date('d/m/Y', strtotime($StockIssueDetails['CreateDate'])); ?></td>
		                                                    <td class="print-hidden">
																<?php
																if ($LoggedUser->HasPermissionForTask(TASK_EDIT_STOCK_RETURN) === true)
																{
																    echo 'Edit';
																	//echo '<a href="edit_stock_return.php?Process=2&amp;StockReturnID=' . $StockIssueID . '">Edit</a>';
																}
																else
																{
																	echo 'Edit';
																}

																echo '&nbsp;|&nbsp;';

																if ($LoggedUser->HasPermissionForTask(TASK_DELETE_STOCK_RETURN) === true)
																{
																    //echo 'Delete';
																	echo '<a class="delete-record" href="stock_return_list.php?Process=5&amp;StockReturnID=' . $StockIssueID . '">Delete</a>';
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
				$ ("body").on ('click', '.delete-record', function ()
				{
					if (! confirm ("Are you sure you want to delete this stock issue?"))
					{
						return false;
					}
				});
				
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
				
				$ ('#StaffCategory').change (function ()
				{
					StaffCategory = $ (this).val ();

					if (StaffCategory <= 0)
					{
						$ ('#BranchStaffID').html ('<option value="0">-- Select --</option>');
						return false;
					}

					$.post ("/xhttp_calls/get_branch_staff_by_staff_category.php", {SelectedStaffCategory: StaffCategory}, function ( data )
					{

						ResultArray = data.split ("|*****|");

						if (ResultArray[0] == 'error')
						{
							alert (ResultArray[1]);
						}
						else
						{
							$ ('#BranchStaffID').html ('<option value="0">-- Select --</option>' + ResultArray[1]);
						}
					});
				});
			});
	</script>
	<!-- JavaScript To Print A Report -->
	<script src="/admin/js/print-report.js"></script>
</body>
</html>
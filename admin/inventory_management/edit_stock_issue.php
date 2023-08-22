<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");
require_once("../../includes/global_defaults.inc.php");
require_once("../../classes/class.date_processing.php");

require_once("../../classes/inventory_management/class.product_categories.php");
require_once("../../classes/inventory_management/class.products.php");
require_once("../../classes/inventory_management/class.departments.php");
require_once("../../classes/school_administration/class.branch_staff.php");
require_once("../../classes/inventory_management/class.stock_issue.php");

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

if ($LoggedUser->HasPermissionForTask(TASK_ADD_STOCK_ISSUE) !== true)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}

$StaffCategoryList = array('Teaching' => 'Teaching Staff', 'NonTeaching' => 'Non Teaching Staff');

$AllProductCategoryList = array();
$AllProductCategoryList = ProductCategory::GetActiveProductCategories();

$AllProductList = array();
$AllProductList = Product::GetProductsByProductCategoryID(key($AllProductCategoryList));

$IssueTypeList = array();
$IssueTypeList = array('Staff' => 'Staff', 'Department' => 'Department');

$Clean = array();

$Clean['StockIssueID'] = 0;

$Clean['StaffCategory'] = '';
$Clean['BranchStaffID'] = 0;

$InputErrors = array();

$HasErrors = false;
$ViewOnly = false;

$Clean['Process'] = 0;

$Clean['ProductID'] = (key($AllProductList));

$Clean['IssueType'] = 'Staff';
$Clean['DepartmentID'] = 0;

$Clean['VoucherNumber'] = 0;

$Clean['StockIssueDetailsRow'] = array();

$AllBranchStaffList = array();

if (isset($_GET['StockIssueID']))
{
	$Clean['StockIssueID'] = (int) $_GET['StockIssueID'];
}
elseif (isset($_POST['hdnStockIssueID']))
{
	$Clean['StockIssueID'] = (int) $_POST['hdnStockIssueID'];
}

if ($Clean['StockIssueID'] <= 0)
{
	header('location:../error.php');
	exit;
}

try
{
	$StockIssueToEdit = new StockIssue($Clean['StockIssueID']);
	$StockIssueToEdit->FillStockIssuedDetails();
	$Clean['StockIssueDetailsRow'] = $StockIssueToEdit->GetStockIssuedDetails();
	$Clean['VoucherNumber'] = $StockIssueToEdit->GetVoucherNumber();
	$Clean['BranchStaffID'] = $StockIssueToEdit->GetBranchStaffID();
	$BranchStaffDetails = new BranchStaff($Clean['BranchStaffID']);
	$Clean['StaffCategory'] = $BranchStaffDetails->GetStaffCategory();

	$AllBranchStaffList = BranchStaff::GetActiveBranchStaff($Clean['StaffCategory']);
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
		if (isset($_POST['drdIssueType']))
		{
			$Clean['IssueType'] = strip_tags(trim($_POST['drdIssueType']));
		}

		if (isset($_POST['drdDepartment']))
		{
			$Clean['DepartmentID'] = (int) $_POST['drdDepartment'];
		}

		if (isset($_POST['drdStaffCategory']))
		{
			$Clean['StaffCategory'] = strip_tags(trim($_POST['drdStaffCategory']));
		}

		if (isset($_POST['drdBranchStaff']))
		{
			$Clean['BranchStaffID'] = (int) $_POST['drdBranchStaff'];
		}

		if (isset($_POST['txtVoucherNumber']))
		{
			$Clean['VoucherNumber'] = (int) $_POST['txtVoucherNumber'];
		}

		if (isset($_POST['StockIssueDetailsRow']) && is_array($_POST['StockIssueDetailsRow']))
		{
			$Clean['StockIssueDetailsRow'] = $_POST['StockIssueDetailsRow'];
		}

		$NewRecordValidator = new Validator();

		$AllBranchStaffList = BranchStaff::GetActiveBranchStaff($Clean['StaffCategory']);

		$NewRecordValidator->ValidateInSelect($Clean['StaffCategory'], $StaffCategoryList, 'Unknown error, please try again.1');
		$NewRecordValidator->ValidateInSelect($Clean['IssueType'], $IssueTypeList, 'Unknown error, please try again.3');
		$NewRecordValidator->ValidateInSelect($Clean['BranchStaffID'], $AllBranchStaffList, 'Unknown error, please try again.2');
		$NewRecordValidator->ValidateStrings($Clean['VoucherNumber'], 'Voucher number is required and should be between 1 and 10 characters.', 1, 10);

		$Counter = 0;

		$NewStockIssuedToSave = array();

		foreach ($Clean['StockIssueDetailsRow'] as $StockIssueDetailsRow)
		{
			$Counter++;

			$InputErrors[$Counter]['Quantity'] = 0;
			$InputErrors[$Counter]['IssueDate'] = 0;
			$InputErrors[$Counter]['ReturnDate'] = 0;

			$ProductCategoryID = 0;
			$ProductID = 0;
			$Quantity = 0;
			$IssueDate = 0;
			$ReturnDate = 0;

			if (isset($StockIssueDetailsRow['ProductCategoryID']))
			{
				$ProductCategoryID = (int) $StockIssueDetailsRow['ProductCategoryID'];
			}

			if (isset($StockIssueDetailsRow['ProductID']))
			{
				$ProductID = (int) $StockIssueDetailsRow['ProductID'];
			}

			if (isset($StockIssueDetailsRow['Quantity']))
			{
				$Quantity = (int) $StockIssueDetailsRow['Quantity'];
			}

			if (isset($StockIssueDetailsRow['IssueDate']))
			{
				$IssueDate = $StockIssueDetailsRow['IssueDate'];
			}

			if (isset($StockIssueDetailsRow['ReturnDate']))
			{
				$ReturnDate = $StockIssueDetailsRow['ReturnDate'];
			}

			if ($NewRecordValidator->ValidateInSelect($ProductCategoryID, $AllProductCategoryList, 'Unknown error, please try again.3'))
			{
				$AllProductList = Product::GetProductsByProductCategoryID($ProductCategoryID);
				$NewRecordValidator->ValidateInSelect($ProductID, $AllProductList, 'Unknown error, please try again.4');
			}

			if (!$NewRecordValidator->ValidateInteger($Quantity, 'Please enter a valid integer value for <strong>issued quantity</strong>', 1))
			{
				$InputErrors[$Counter]['Quantity'] = 1;
			}

			if (!$NewRecordValidator->ValidateDate($IssueDate, 'Please enter valid issue date.'))
			{
				$InputErrors[$Counter]['IssueDate'] = 1;
			}

			$IssueDate = date('Y-m-d', strtotime(DateProcessing::ToggleDateDayAndMonth($IssueDate)));

			if ($ReturnDate != '')
			{
				$NewRecordValidator->ValidateDate($ReturnDate, 'Please enter valid return date.');
				$ReturnDate = date('Y-m-d', strtotime(DateProcessing::ToggleDateDayAndMonth($ReturnDate)));

				if ($ReturnDate < $IssueDate)
				{
					$NewRecordValidator->AttachTextError('Return date cannot be less than issue date.');
				}
			}

			$NewStockIssuedToSave[$Counter]['ProductID'] = $ProductID;
			$NewStockIssuedToSave[$Counter]['Quantity'] = $Quantity;
			$NewStockIssuedToSave[$Counter]['IssueDate'] = $IssueDate;
			$NewStockIssuedToSave[$Counter]['ReturnDate'] = $ReturnDate;
		}

		if (count($NewStockIssuedToSave) <= 0)
		{
			$NewRecordValidator->AttachTextError('Please select atlast 1 product');
		}

		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}

		$StockIssueToEdit->SetIssueType($Clean['IssueType']);
		$StockIssueToEdit->SetDepartmentID($Clean['DepartmentID']);
		$StockIssueToEdit->SetBranchStaffID($Clean['BranchStaffID']);
		$StockIssueToEdit->SetVoucherNumber($Clean['VoucherNumber']);
		$StockIssueToEdit->SetStockIssuedDetails($NewStockIssuedToSave);

		$StockIssueToEdit->SetCreateUserID($LoggedUser->GetUserID());

		if (!$StockIssueToEdit->Save())
		{
			$NewRecordValidator->AttachTextError(ProcessErrors($StockIssueToEdit->GetLastErrorCode()));
			$HasErrors = true;

			break;
		}

		header('location:stock_issues_list.php?Mode=ED');
		exit;
		break;
}

require_once('../html_header.php');
?>
<title>Edit Stock Issue</title>
<!-- DataTables CSS -->
<link href="/admin/vendor/jquery-ui/jquery-ui.min.css" rel="stylesheet">
<link href="/admin/vendor/datatables-plugins/dataTables.bootstrap.css" rel="stylesheet">

<!-- DataTables Responsive CSS -->
<link href="/admin/vendor/datatables-responsive/dataTables.responsive.css" rel="stylesheet">
<style type="text/css">
	#AddIssuedProductRow .input-style
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
                    <h1 class="page-header">Edit Stock Issue</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
			<form class="form-horizontal" name="AddProductPurchase" action="edit_stock_issue.php" method="post">
				<div class="panel panel-default">
                    <div class="panel-heading">
                        Enter Stock Issuee Details
                    </div>
                    <div class="panel-body">
						<?php
						if ($HasErrors == true)
						{
							echo $NewRecordValidator->DisplayErrors();
						}
						?>
                        <div class="form-group">
							<label for="StaffCategory" class="col-lg-2 control-label">Staff Category</label>
							<div class="col-lg-4">
								<select class="form-control" name="drdStaffCategory" id="StaffCategory" readonly="readonly">
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
								<select class="form-control"  name="drdBranchStaff" id="BranchStaffID" readonly="readonly">
									<?php
									foreach ($AllBranchStaffList as $BranchStaffID => $BranchStaffName)
									{
										?>
										<option <?php echo ($BranchStaffID == $Clean['BranchStaffID'] ? 'selected="selected"' : ''); ?> value="<?php echo $BranchStaffID; ?>"><?php echo $BranchStaffName['FirstName'] . " " . $BranchStaffName['LastName']; ?></option>
										<?php
									}
									?>
								</select>
							</div>
						</div>
						<div class="form-group">
							<label for="VoucherNumber" class="col-lg-2 control-label">Voucher Number</label>
                            <div class="col-lg-4">
								<input class="form-control" type="text" maxlength="25" id="VoucherNumber" name="txtVoucherNumber" value="<?php echo $Clean['VoucherNumber']; ?>" / readonly="readonly">
                            </div>
						</div>
                    </div>
                </div>
                <div class="panel panel-default">
                    <div class="panel-heading">
                        Enter Stock Issue Details
                    </div>
                    <div class="panel-body">
						<div class="row" id="RecordTable">
							<div class="col-lg-12">
								<table width="100%" class="table table-striped table-bordered table-hover" id="DataTableRecords">
									<thead>
										<tr>
											<th>Product Category</th>
											<th>Product</th>
											<th>Stock Quantity</th>
											<th>Issued Quantity</th>
											<th>Issue Date</th>
											<th>Return Date</th>
											<th>Action</th>
										</tr>
									</thead>
									<tbody id="AddIssuedProductRow">
										<?php
										if (is_array($Clean['StockIssueDetailsRow']) && count($Clean['StockIssueDetailsRow']) > 0)
										{
											foreach ($Clean['StockIssueDetailsRow'] as $StockIssueDetailID => $IssuedStockDetails)
											{
												?>
												<tr>
													<td>
														<select class="form-control ProductCategory input-style" name="StockIssueDetailsRow[<?php echo $StockIssueDetailID; ?>][ProductCategoryID]" readonly="readonly">
															<?php
															foreach ($AllProductCategoryList as $ProductCategoryID => $ProductCategoryName)
															{
																?>
																<option <?php echo ($IssuedStockDetails['ProductCategoryID'] == $ProductCategoryID) ? 'selected="selected"' : ''; ?> value="<?php echo $ProductCategoryID; ?>"><?php echo $ProductCategoryName; ?></option>
																<?php
															}
															?>
														</select>
													</td>
													<td>
														<select class="form-control ProductList input-style" name="StockIssueDetailsRow[<?php echo $StockIssueDetailID; ?>][ProductID]" readonly="readonly">
															<?php
															if (!empty($IssuedStockDetails['ProductCategoryID']))
															{
																$AllProductList = Product::GetProductsByProductCategoryID($IssuedStockDetails['ProductCategoryID']);
															}
															else
															{
																$AllProductList = Product::GetProductsByProductCategoryID($Clean['ProductID']);
															}

															foreach ($AllProductList as $ProductID => $ProductName)
															{
																?>
																<option <?php echo ($IssuedStockDetails['ProductID'] == $ProductID) ? 'selected="selected"' : ''; ?> value="<?php echo $ProductID; ?>"><?php echo $ProductName; ?></option>
																<?php
															}
															?>
														</select>
													</td>

													<td>
														<span id="StockQuantity" value=""><?php echo Product::GetAvailabileStockQuantity($IssuedStockDetails['ProductID']); ?></span>
													</td>
													<td>
														<input class="form-control IssuedQuantity" type="text" maxlength="7" name="StockIssueDetailsRow[<?php echo $StockIssueDetailID; ?>][Quantity]" value="<?php echo ($IssuedStockDetails['Quantity']) ? $IssuedStockDetails['Quantity'] : '' ?>" />
													</td>

													<td>
														<input class="form-control IssueDate dtepicker" type="text" maxlength="10" name="StockIssueDetailsRow[<?php echo $StockIssueDetailID; ?>][IssueDate]" value="<?php echo ($IssuedStockDetails['IssueDate']) ? date('d/m/Y', strtotime($IssuedStockDetails['IssueDate'])) : '' ?>" />
													</td>

													<td>
														<input class="form-control ReturnDate dtepicker" type="text" maxlength="10" name="StockIssueDetailsRow[<?php echo $StockIssueDetailID; ?>][ReturnDate]" value="<?php echo ($IssuedStockDetails['ReturnDate']) ? date('d/m/Y', strtotime($IssuedStockDetails['ReturnDate'])) : '' ?>" />
													</td>

													<td  style="width: 90px;">
														<button type="button" class="btn btn-danger RemoveRow" StockIssueID="<?php echo $Clean['StockIssueID']; ?>" StockIssueDetailID="<?php echo $StockIssueDetailID; ?>"><span class="fa fa-trash"></span></button>
														<button type="button" class="btn btn-primary UpdateRow" StockIssueID="<?php echo $Clean['StockIssueID']; ?>" StockIssueDetailID="<?php echo $StockIssueDetailID; ?>"><span class="fa fa-save"></span></button>
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
								<button type="button" class="btn btn-success" id="AddMoreIssuedProductRow">Add More</button>
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
		$ (document).ready (function ()
		{
			var ViewOnly = '<?php echo $ViewOnly; ?>';

			if (ViewOnly)
			{
				$ ('input, select, textarea, button[type="button"]').prop ('disabled', true);
				$ ('#Check').hide ();
				$ ('button[type="submit"]').text ('Close').attr ('onClick', 'window.close();');

			}

			var rowCount = $ ('#DataTableRecords > tbody >tr').length;
			$ ('#AddMoreIssuedProductRow').click (function ()
			{
				//var rowCount = $('#DataTableRecords > tbody >tr').length;
				rowCount += 1;

				var dataColumn1 = '<select class="form-control ProductCategory input-style" name="StockIssueDetailsRow[' + rowCount + '][ProductCategoryID]">'
<?php
foreach ($AllProductCategoryList as $ProductCategoryID => $ProductCategoryName)
{
	?>
					dataColumn1 += '<option value="<?php echo $ProductCategoryID; ?>"><?php echo $ProductCategoryName; ?></option>'
	<?php
}
?>
				dataColumn1 += '</select>'
				var dataColumn2 = '<select class="form-control ProductList input-style" name="StockIssueDetailsRow[' + rowCount + '][ProductID]">';
<?php
//$AllProductList = Product::GetProductsByProductCategoryID(key($AllProductCategoryList));

foreach ($AllProductList as $ProductID => $ProductName)
{
	?>
					dataColumn2 += '<option value="<?php echo $ProductID; ?>"><?php echo $ProductName; ?></option>'
	<?php
}
?>
				dataColumn2 += '</select>';
				var dataColumn3 = '<td><span id="StockQuantity" value=""><?php echo Product::GetAvailabileStockQuantity($Clean['ProductID']); ?></span></td>'
				var dataColumn4 = '<input class="form-control IssuedQuantity" type="text" maxlength="7" id="Quantity" name="StockIssueDetailsRow[' + rowCount + '][Quantity]" value="" />'
				var dataColumn5 = '<input class="form-control IssueDate dtepicker" type="text" maxlength="10" name="StockIssueDetailsRow[' + rowCount + '][IssueDate]" value="" />'
				var dataColumn6 = '<input class="form-control ReturnDate dtepicker" type="text" maxlength="10" name="StockIssueDetailsRow[' + rowCount + '][ReturnDate]" value="" />'
				var dataColumn7 = '<button type="button" class="btn btn-danger RemoveRow" StockIssueID="<?php echo $Clean['StockIssueID']; ?>" StockIssueDetailID="<?php echo "0"; ?>"><span class="fa fa-trash"></span></button> '
				dataColumn7 += '<button type="button" class="btn btn-primary UpdateRow" StockIssueID="<?php echo $Clean['StockIssueID']; ?>" StockIssueDetailID="<?php echo "0"; ?>"><span class="fa fa-save"></span></button>'

				// $('#AddIssuedProductRow').append(data);
				$ ("#DataTableRecords").DataTable ().row.add ([dataColumn1, dataColumn2, dataColumn3, dataColumn4, dataColumn5, dataColumn6, dataColumn7]).draw ();
				GetDatePicker ();
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
						CurrentTR.find ('.ProductList').change ();
						return false;
					}
					else
					{
						CurrentTR.find ('.ProductList').html (ResultArray[1]);
						CurrentTR.find ('.ProductList').change ();
					}
				});
			});


			$ ('body').on ("change", '.ProductList', function ()
			{
				var CurrentTR = $ (this).closest ('tr');

				var ProductID = parseInt ($ (this).val ());

				if (ProductID > 0)
				{
					$.get ("/xhttp_calls/get_product_stock_quantity_by_product.php", {SelectedProductID: ProductID}, function ( data )
					{
						ResultArray = data.split ("|*****|");

						if (ResultArray[0] == 'error')
						{
							alert (ResultArray[1]);
							CurrentTR.find ('#StockQuantity').html ('0')
							return false;
						}
						else
						{
							CurrentTR.find ('#StockQuantity').html (ResultArray[1]);
						}
					});
				}

				CurrentTR.find ('#StockQuantity').html ('0')
				return false;
			});

			GetDatePicker ();

			$ ('body').on ("click", '.RemoveRow', function ()
			{
				var row = $ (this).closest ("tr");

				var StockIssueID = $ (this).attr ("StockIssueID");
				var ProductID = row.find ('.ProductList').val ();

				$.post ("/xhttp_calls/delete_product_issue.php", {SelectedStockIssueID: StockIssueID, SelectedProductID: ProductID}, function ( data )
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

				var StockIssueID = $ (this).attr ("StockIssueID");
				var StockIssueDetailID = $ (this).attr ("StockIssueDetailID");

				if (StockIssueID <= 0)
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
				
				var Quantity = current_row.find ('.IssuedQuantity').val ();

				if (Quantity <= 0)
				{
					alert ('Please enter rate.');
					return false;
				}

				var IssueDate = current_row.find ('.IssueDate').val ();

				if (IssueDate == '')
				{
					alert ('Please enter issue date.');
					return false;
				}
				
				var ReturnDate = current_row.find ('.ReturnDate').val ();

				if (ReturnDate == '')
				{
					alert ('Please enter return date.');
					return false;
				}
				
				$.post ("/xhttp_calls/update_product_issue.php", {SelectedStockIssueID: StockIssueID, 
																	SelectedStockIssueDetailID: StockIssueDetailID, 
																	SelectedProductID: ProductID, 
																	SelectedQuantity: Quantity, 
																	SelectedIssueDate: IssueDate, 
																	SelectedReturnDate: ReturnDate}, function ( data )
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
						current_row.find ('.ProductList').attr('readonly', 'readonly');
						current_row.find ('.ProductCategory').attr('readonly', 'readonly');
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

			$ ('#StaffCategory').change (function ()
			{
				StaffCategory = $ (this).val ();

				if (StaffCategory <= 0)
				{
					$ ('#BranchStaffID').html ('<option value="0">Select Section</option>');
					return;
				}

				$.post ("/xhttp_calls/get_branch_staff_by_staff_category.php", {SelectedStaffCategory: StaffCategory}, function ( data )
				{

					ResultArray = data.split ("|*****|");

					if (ResultArray[0] == 'error')
					{
						alert (ResultArray[1]);
						$ ('#StaffCategory').val (StaffCategoryBeforeChange);
					}
					else
					{
						$ ('#BranchStaffID').html (ResultArray[1]);
					}
				});
			});
		});

		function GetDatePicker ()
		{
			$ (".dtepicker").datepicker ({
				changeMonth: true,
				changeYear: true,
				dateFormat: 'dd/mm/yy'
			});
		}
	</script>
	<!-- JavaScript To Print A Report -->
</body>
</html>
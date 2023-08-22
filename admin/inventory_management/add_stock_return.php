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
require_once("../../classes/inventory_management/class.stock_return.php");

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

if ($LoggedUser->HasPermissionForTask(TASK_ADD_STOCK_RETURN) !== true)
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
	$StockIssueToReturn = new StockIssue($Clean['StockIssueID']);
	$Clean['BranchStaffID'] = $StockIssueToReturn->GetBranchStaffID();
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
	case 1:
		//var_dump($_POST);exit;

		if (isset($_POST['StockIssueDetails']) && is_array($_POST['StockIssueDetails']))
		{
			$Clean['StockIssueDetails'] = $_POST['StockIssueDetails'];
		}

		$NewRecordValidator = new Validator();

		$NewStockIssuedToReturn = array();

		$StockIssueToReturn->FillStockIssuedDetails();
		$StockIssuedDetails = $StockIssueToReturn->GetStockIssuedDetails();
		
		foreach ($Clean['StockIssueDetails'] as $StockIssueDetailID => $StockReturnDetailsRow)
		{
			//var_dump($StockIssueDetailID);exit;
			$InputErrors[$StockIssueDetailID]['ReturnQuantity'] = 0;


			$ReturnQuantity = 0;

			if (isset($StockReturnDetailsRow['ReturnQuantity']))
			{
				$ReturnQuantity = $StockReturnDetailsRow['ReturnQuantity'];
			}

			if ($ReturnQuantity <= 0)
			{
				continue;
			}

			if (!$NewRecordValidator->ValidateInteger($ReturnQuantity, 'Please enter a valid integer value for <strong>return quantity</strong>', 1))
			{
				$InputErrors[$StockIssueDetailID]['ReturnQuantity'] = 1;
			}

			$NewStockIssuedToReturn[$StockIssueDetailID]['ProductID'] = $StockIssuedDetails[$StockIssueDetailID]['ProductID'];
			$NewStockIssuedToReturn[$StockIssueDetailID]['ReturnQuantity'] = $ReturnQuantity;
		}

		if (count($NewStockIssuedToReturn) <= 0)
		{
			$NewRecordValidator->AttachTextError('Please enter valid return quantity.');
		}

		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}

		$NewStockReturn = new StockReturn();

		$NewStockReturn->SetIssueType($StockIssueToReturn->GetIssueType());
		$NewStockReturn->SetDepartmentID($StockIssueToReturn->GetDepartmentID());
		$NewStockReturn->SetBranchStaffID($StockIssueToReturn->GetBranchStaffID());
		$NewStockReturn->SetStockReturnDetails($NewStockIssuedToReturn);

		$NewStockReturn->SetCreateUserID($LoggedUser->GetUserID());

		//var_dump($NewStockReturn->GetStockReturnDetails());exit;

		if (!$NewStockReturn->Save())
		{
			$NewRecordValidator->AttachTextError(ProcessErrors($NewStockReturn->GetLastErrorCode()));
			$HasErrors = true;

			break;
		}

		header('location:stock_return_list.php?Mode=ED');
		exit;
		break;
	case 2:
		$StockIssueToReturn->FillStockIssuedDetails();
		$StockIssuedDetails = $StockIssueToReturn->GetStockIssuedDetails();
		foreach ($StockIssuedDetails as $StockIssueDetailID => $Details)
		{
			$InputErrors[$StockIssueDetailID]['ReturnQuantity'] = 0;
		}
		break;
}

require_once('../html_header.php');
?>
<title>Add Stock Return</title>
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
                    <h1 class="page-header">Add Stock Return</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
			<form class="form-horizontal" name="AddStockReturn" action="add_stock_return.php" method="post">
				<div class="panel panel-default">
                    <div class="panel-heading">
                        Stock Returner's Details
                    </div>
                    <div class="panel-body">
						<?php
						if ($HasErrors == true)
						{
							echo $NewRecordValidator->DisplayErrors();
						}
						?>
                        <div class="form-group">
							<label for="StaffCategory" class="col-lg-2 control-label">Staff Category:</label>
							<div class="col-lg-4">
								<p class="form-control-static"><?php echo $StockIssueToReturn->GetIssueType(); ?></p>
							</div>
							<label for="BranchStaff" class="col-lg-2 control-label">Branch Staff:</label>
							<div class="col-lg-4">
								<p class="form-control-static"><?php echo $BranchStaffDetails->GetFirstName() . " " . $BranchStaffDetails->GetLastName(); ?></p>
							</div>
						</div>
						<div class="form-group">
							<label for="VoucherNumber" class="col-lg-2 control-label">Voucher Number:</label>
                            <div class="col-lg-4">
								<p class="form-control-static"><?php echo $StockIssueToReturn->GetVoucherNumber(); ?></p>
                            </div>
						</div>
                    </div>
                </div>
                <div class="panel panel-default">
                    <div class="panel-heading">
                        Enter Return Stock Details
                    </div>
                    <div class="panel-body">
						<div class="row" id="RecordTable">
							<div class="col-lg-12">
								<table width="100%" class="table table-striped table-bordered table-hover" id="DataTableRecords">
									<thead>
										<tr>
											<th>Product Category</th>
											<th>Product</th>
											<th>Remaining Quantity</th>
											<th>Issue Date</th>
											<th>Return Date</th>
											<th>Return Quantity</th>
										</tr>
									</thead>
									<tbody id="AddIssuedProductRow">
										<?php
										if (is_array($StockIssuedDetails) && count($StockIssuedDetails) > 0)
										{
											foreach ($StockIssuedDetails as $StockIssueDetailID => $IssuedStockDetails)
											{
												if ($IssuedStockDetails['Quantity'] <= $IssuedStockDetails['ReturnedQuantity'])
												{
													continue;
												}
												?>
												<tr>
													<td>
														<?php echo $AllProductCategoryList[$IssuedStockDetails['ProductCategoryID']]; ?>
													</td>
													<td>
														<?php echo $AllProductList[$IssuedStockDetails['ProductID']]; ?>
													</td>
													<td>
														<?php echo $IssuedStockDetails['Quantity'] - $IssuedStockDetails['ReturnedQuantity']; ?>
													</td>
													<td>
														<?php echo ($IssuedStockDetails['IssueDate']) ? date('d/m/Y', strtotime($IssuedStockDetails['IssueDate'])) : '' ?>
													</td>
													<td>
														<?php echo ($IssuedStockDetails['ReturnDate']) ? date('d/m/Y', strtotime($IssuedStockDetails['ReturnDate'])) : '' ?>
													</td>
													<td <?php echo($InputErrors[$StockIssueDetailID]['ReturnQuantity']) ? 'class="has-error"' : ''; ?>>
														<input class="form-control ReturnQuantity" type="text" maxlength="10" name="StockIssueDetails[<?php echo $StockIssueDetailID; ?>][ReturnQuantity]" value="" />
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
							<div class="col-sm-offset-3 col-lg-10">
								<input type="hidden" name="hdnProcess" value="1" />
								<input type="hidden" name="hdnStockIssueID" value="<?php echo $Clean['StockIssueID']; ?>" />
								<button type="submit" class="btn btn-primary" onClick="return ValidateForm ();">Save</button>
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
										$ ('#DataTableRecords').DataTable ({
											responsive: true,
											bPaginate: false,
											bSort: false,
											searching: false,
											info: false
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
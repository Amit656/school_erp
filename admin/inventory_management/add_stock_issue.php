<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.date_processing.php");
require_once("../../classes/class.authentication.php");
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

if ($LoggedUser->HasPermissionForTask(TASK_ADD_STOCK_ISSUE) !== true)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}

require_once("../../classes/inventory_management/class.product_categories.php");
require_once("../../classes/inventory_management/class.products.php");
require_once("../../classes/inventory_management/class.departments.php");
require_once("../../classes/school_administration/class.branch_staff.php");
require_once("../../classes/inventory_management/class.stock_issue.php");

$StaffCategoryList = array('Teaching' => 'Teaching Staff', 'NonTeaching' => 'Non Teaching Staff');

$IssueTypeList = array('Staff' => 'Staff', 'Department' => 'Department');

$AllDepartmentList = array();
$AllDepartmentList = Department::GetActiveDepartments();

$AllBranchStaffList = array();
$AllBranchStaffList = BranchStaff::GetActiveBranchStaff('Teaching');

$AllProductCategoryList = array();
$AllProductCategoryList = ProductCategory::GetActiveProductCategories();

$InputErrors = array();

$HasErrors = false;

$Clean = array();

$Clean['ProductID'] = key($AllProductCategoryList);

$Clean['Process'] = 0;

$Clean['IssueType'] = 'Staff';
$Clean['DepartmentID'] = 0;

$Clean['StaffCategory'] = '';
$Clean['BranchStaffID'] = 0;
$Clean['VoucherNumber'] = 0;
$Clean['Description'] = '';

$Clean['StockIssueDetailsRow'] = array();

if (isset($_POST['hdnProcess']))
{
	$Clean['Process'] = (int) $_POST['hdnProcess'];
}

switch ($Clean['Process'])
{
	case 1:	
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

		if (isset($_POST['txtDescription']))
		{
			$Clean['Description'] = strip_tags(trim($_POST['txtDescription']));
		}

		if (isset($_POST['StockIssueDetailsRow']) && is_array($_POST['StockIssueDetailsRow']))
		{
			$Clean['StockIssueDetailsRow'] = $_POST['StockIssueDetailsRow'];
		}
		
		$NewRecordValidator = new Validator();

		$AllBranchStaffList = BranchStaff::GetActiveBranchStaff($Clean['StaffCategory']);
		
		$NewRecordValidator->ValidateInSelect($Clean['StaffCategory'], $StaffCategoryList, 'Unknown error, please try again.1');
		$NewRecordValidator->ValidateInSelect($Clean['BranchStaffID'], $AllBranchStaffList, 'Unknown error, please try again.2');
		$NewRecordValidator->ValidateStrings($Clean['VoucherNumber'], 'Voucher number is required and should be between 1 and 10 characters.', 1, 10);
		if ($Clean['Description'] != '')
		{
			$NewRecordValidator->ValidateStrings($Clean['Description'], 'Description is required and should be between 3 and 10 characters.', 3, 500);
		}

		$Counter = 0;

		$NewStockIssuedToSave = array();

		foreach ($Clean['StockIssueDetailsRow'] as $StockIssueDetailsRow) 
		{
			$Counter++;

			$InputErrors[$Counter]['IssuedQuantity'] = 0;
			$InputErrors[$Counter]['IssueDate'] = 0;
			$InputErrors[$Counter]['ReturnDate'] = 0;

			$ProductCategoryID = 0;
			$ProductID = 0;
			$IssuedQuantity = 0;
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

			if (isset($StockIssueDetailsRow['IssuedQuantity']))
			{
				$IssuedQuantity = (int) $StockIssueDetailsRow['IssuedQuantity'];
			}

			if (isset($StockIssueDetailsRow['IssueDate']))
			{
				$IssueDate =  $StockIssueDetailsRow['IssueDate'];
			}

			if (isset($StockIssueDetailsRow['ReturnDate']))
			{
				$ReturnDate = $StockIssueDetailsRow['ReturnDate'];
			}

			if ($NewRecordValidator->ValidateInSelect($ProductCategoryID, $AllProductCategoryList, 'Unknown error, please try again.'))
			{
				$AllProductList = Product::GetProductsByProductCategoryID($ProductCategoryID);
				$NewRecordValidator->ValidateInSelect($ProductID, $AllProductList, 'Unknown error, please try again.');
			}

			if (!$NewRecordValidator->ValidateInteger($IssuedQuantity, 'Please enter a valid integer value for <strong>issued quantity</strong>', 1))
			{
				$InputErrors[$Counter]['IssuedQuantity'] = 1;
			}

			if (!$NewRecordValidator->ValidateDate($IssueDate, 'Please enter valid issue date.'))
			{
				$InputErrors[$Counter]['IssueDate'] = 1;
			}

			$IssueDate = date('Y-m-d',strtotime(DateProcessing::ToggleDateDayAndMonth($IssueDate)));

			if ($ReturnDate != '') 
			{
				$NewRecordValidator->ValidateDate($ReturnDate, 'Please enter valid return date.');
				$ReturnDate = date('Y-m-d',strtotime(DateProcessing::ToggleDateDayAndMonth($ReturnDate)));

				if ($ReturnDate <=  $IssueDate) 
				{
					$NewRecordValidator->AttachTextError('Return  date cannot be less than issue date.');
				}
			}

			$NewStockIssuedToSave[$Counter]['ProductID'] = $ProductID;
			$NewStockIssuedToSave[$Counter]['IssuedQuantity'] = $IssuedQuantity;
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
				
		$NewStockIssue = new StockIssue();
				
		$NewStockIssue->SetIssueType($Clean['IssueType']);
		$NewStockIssue->SetDepartmentID($Clean['DepartmentID']);
		$NewStockIssue->SetBranchStaffID($Clean['BranchStaffID']);
		$NewStockIssue->SetVoucherNumber($Clean['VoucherNumber']);
		$NewStockIssue->SetDescription($Clean['Description']);
		$NewStockIssue->SetStockIssuedDetails($NewStockIssuedToSave);
		
		$NewStockIssue->SetCreateUserID($LoggedUser->GetUserID());

		if (!$NewStockIssue->Save())
		{
			$NewRecordValidator->AttachTextError(ProcessErrors($NewStockIssue->GetLastErrorCode()));
			$HasErrors = true;

			break;
		}
		
		header('location:stock_issues_list.php?Mode=ED');
		exit;
		break;

	default;
		for ($CounterForRowNumber = 1; $CounterForRowNumber <= 2; $CounterForRowNumber++)
		{
			$Clean['StockIssueDetailsRow'][$CounterForRowNumber]['ProductCategoryID'] = 0;
			$Clean['StockIssueDetailsRow'][$CounterForRowNumber]['ProductID'] = 0;
			$Clean['StockIssueDetailsRow'][$CounterForRowNumber]['IssuedQuantity'] = '';
			$Clean['StockIssueDetailsRow'][$CounterForRowNumber]['IssueDate'] = '';
			$Clean['StockIssueDetailsRow'][$CounterForRowNumber]['ReturnDate'] = '';

			// initializing Input Error Array
			$InputErrors[$CounterForRowNumber]['IssuedQuantity'] = 0;
			$InputErrors[$CounterForRowNumber]['IssueDate'] = 0;
			$InputErrors[$CounterForRowNumber]['ReturnDate'] = 0;
		}
	break;		
}

require_once('../html_header.php');
?>
<title>Add Stock Issue</title>
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
                    <h1 class="page-header">Add Stock Issue</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="AddProductPurchase" action="add_stock_issue.php" method="post">
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
                    	<!--<div class="form-group">
                    		<label for="IssueType" class="col-lg-2 control-label">Issue Type</label>
                    		<div class="col-lg-4">
	                            <select class="form-control" name="drdIssueType" id="IssueType">
<?php
								foreach ($IssueTypeList as $IssueType => $IssueTypeName)
								{
?>
									<option <?php echo ($Clean['IssueType'] == $IssueType) ? 'selected="selected"' : ''; ?> value="<?php echo $IssueType; ?>"><?php echo $IssueTypeName; ?></option>
<?php
								}
?>
								</select>
							</div>
							<label for="Department" class="col-lg-2 control-label">Department</label>
							<div class="col-lg-4">
								<select class="form-control" name="drdDepartment" id="Department" <?php echo($Clean['IssueType'] == 'Staff') ? 'disabled="disabled"' : ''?> >
<?php
								foreach ($AllDepartmentList as $DepartmentID => $DepartmentName)
								{
?>
									<option <?php echo ($Clean['DepartmentID'] == $DepartmentID) ? 'selected="selected"' : ''; ?> value="<?php echo $DepartmentID; ?>"><?php echo $DepartmentName; ?></option>
<?php
								}
?>
								</select>
							</div>
                        </div>-->
                        <div class="form-group">
	                        <label for="StaffCategory" class="col-lg-2 control-label">Staff Category</label>
	                        <div class="col-lg-4">
	                            <select class="form-control" name="drdStaffCategory" id="StaffCategory">
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
<?php
                                foreach ($AllBranchStaffList as $BranchStaffID => $BranchStaffName)
                                {
?>
                                    <option <?php echo ($BranchStaffID == $Clean['BranchStaffID'] ? 'selected="selected"' : ''); ?> value="<?php echo $BranchStaffID; ?>"><?php echo $BranchStaffName['FirstName'] . " ". $BranchStaffName['LastName']; ?></option>
<?php
                                }
?>
	                            </select>
	                        </div>
	                    </div>
	                    <div class="form-group">
	                        <label for="VoucherNumber" class="col-lg-2 control-label">Voucher Number</label>
                            <div class="col-lg-4">
                            	<input class="form-control" type="text" maxlength="25" id="VoucherNumber" name="txtVoucherNumber" value="<?php echo $Clean['VoucherNumber']; ?>" />
                            </div>
							<label for="Description" class="col-lg-2 control-label">Description</label>
                            <div class="col-lg-4">
								<textarea  class="form-control" name="txtDescription" ><?php echo $Clean['Description']; ?></textarea>
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
											foreach ($Clean['StockIssueDetailsRow'] as $Key => $IssuedStockDetails)
											{
?>
											<tr>
												<td>
													<select class="form-control ProductCategory input-style" name="StockIssueDetailsRow[<?php echo $Key; ?>][ProductCategoryID]">
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
													<select class="form-control ProductList input-style" name="StockIssueDetailsRow[<?php echo $Key; ?>][ProductID]">
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
												
												<td><span id="StockQuantity" value=""><?php echo Product::GetAvailabileStockQuantity($Clean['ProductID']); ?></td>
												<td <?php echo($InputErrors[$Key]['IssuedQuantity']) ? 'class="has-error"' : ''; ?>>
													<input class="form-control IssuedQuantity" type="text" maxlength="7" name="StockIssueDetailsRow[<?php echo $Key; ?>][IssuedQuantity]" value="<?php echo ($IssuedStockDetails['IssuedQuantity']) ? $IssuedStockDetails['IssuedQuantity'] : ''?>" />
												</td>

												<td <?php echo($InputErrors[$Key]['IssueDate']) ? 'class="has-error"' : ''; ?>>
													<input class="form-control IssueDate dtepicker" type="text" maxlength="10" name="StockIssueDetailsRow[<?php echo $Key; ?>][IssueDate]" value="<?php echo ($IssuedStockDetails['IssueDate']) ? $IssuedStockDetails['IssueDate'] : ''?>" />
												</td>

												<td <?php echo($InputErrors[$Key]['ReturnDate']) ? 'class="has-error"' : ''; ?>>
													<input class="form-control ReturnDate dtepicker" type="text" maxlength="10" name="StockIssueDetailsRow[<?php echo $Key; ?>][ReturnDate]" value="<?php echo ($IssuedStockDetails['ReturnDate']) ? $IssuedStockDetails['ReturnDate'] : ''?>" />
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
								<button type="button" class="btn btn-success" id="AddMoreIssuedProductRow">Add More</button>
							</div>
						</div>
                        <div class="form-group">
	                        <div class="col-sm-offset-3 col-lg-10">
	                        	<input type="hidden" name="hdnProcess" value="1" />
								<button type="submit" class="btn btn-primary" onClick="return ValidateForm();">Save</button>
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
$(document).ready(function() 
{
	var rowCount = 2
    $('#AddMoreIssuedProductRow').click(function()
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
			var dataColumn4 = '<input class="form-control IssuedQuantity" type="text" maxlength="7" id="IssuedQuantity" name="StockIssueDetailsRow[' + rowCount + '][IssuedQuantity]" value="" />'
			var dataColumn5 = '<input class="form-control IssueDate dtepicker" type="text" maxlength="10" name="StockIssueDetailsRow[' + rowCount + '][IssueDate]" value="" />'
			var dataColumn6 = '<input class="form-control ReturnDate dtepicker" type="text" maxlength="10" name="StockIssueDetailsRow[' + rowCount + '][ReturnDate]" value="" />'
			var dataColumn7 = '<button type="button" class="btn btn-danger RemoveRow" style="margin-right: 2%;"> <span class="icon icon-remove">X</span></button>'

		// $('#AddIssuedProductRow').append(data);
		$("#DataTableRecords").DataTable().row.add([dataColumn1, dataColumn2, dataColumn3, dataColumn4, dataColumn5, dataColumn6, dataColumn7]).draw();
		GetDatePicker();
	});

	$('body').on("change",'.ProductCategory', function() 
	{
		var CurrentTR = $(this).closest('tr');

		var ProductCategoryID = parseInt($(this).val());

		if (ProductCategoryID <= 0)
		{
			alert("Please Select Product Category");
			return false;
		}

		$.get("/xhttp_calls/get_products_by_product_category.php", {SelectedProductCategoryID: ProductCategoryID}, function(data)
		{	
			ResultArray = data.split("|*****|");
	
			if (ResultArray[0] == 'error')
			{
				alert (ResultArray[1]);
				CurrentTR.find('.ProductList').html('<option value="0">Select</option>')
				CurrentTR.find('.ProductList').change();
				return false;
			}
			else
			{	
				CurrentTR.find('.ProductList').html(ResultArray[1]);
				CurrentTR.find('.ProductList').change();
			}
		});
	});


	$('body').on("change",'.ProductList', function() 
	{
		var CurrentTR = $(this).closest('tr');

		var ProductID = parseInt($(this).val());

		$.get("/xhttp_calls/get_product_stock_quantity_by_product.php", {SelectedProductID: ProductID}, function(data)
		{	
			ResultArray = data.split("|*****|");
	
			if (ResultArray[0] == 'error')
			{
				alert (ResultArray[1]);
				CurrentTR.find('#StockQuantity').html('0')
				return false;
			}
			else
			{	
				CurrentTR.find('#StockQuantity').html(ResultArray[1]);
			}			
		});
	});

	GetDatePicker();

	$('body').on("click",'.RemoveRow', function() 
	{
		var TotalTR = $('#DataTableRecords > tbody > tr').length;
		
		if (TotalTR <= 1)
		{
			alert('You can not remove all items.');
			return false;
		}

		var row = $(this).closest("tr");
		var table = row.closest('table').dataTable();
		table.api().row( row ).remove().draw();
		return true;
	});

	$('#DataTableRecords').DataTable({
        responsive: true,
        bPaginate: false,
        bSort: false,
        searching: false, 
        info: false
    });

    $('#StaffCategory').change(function()
	{
		StaffCategory = $(this).val();
		
		if (StaffCategory <= 0)
		{
			$('#BranchStaffID').html('<option value="0">Select Section</option>');
			return;
		}
		
		$.post("/xhttp_calls/get_branch_staff_by_staff_category.php", {SelectedStaffCategory:StaffCategory}, function(data)
		{
		
			ResultArray = data.split("|*****|");
			
			if (ResultArray[0] == 'error')
			{
				alert(ResultArray[1]);
				$('#StaffCategory').val(StaffCategoryBeforeChange);
			}
			else
			{
				$('#BranchStaffID').html(ResultArray[1]);
			}
		 });
	});

	/*$('#IssueType').change(function()
	{
		if ($(this).val() == 'Staff') 
		{
			$('#Department').attr('disabled', true);
			$('#StaffCategory').attr('disabled', false);
			$('#BranchStaffID').attr('disabled', false);
			return true;
		}
		else if ($(this).val() == 'Department') 
		{
			$('#Department').attr('disabled', false);
			$('#StaffCategory').attr('disabled', true);
			$('#BranchStaffID').attr('disabled', true);
			return true;
		}
	});*/
});

function GetDatePicker()
{
	$(".dtepicker").datepicker({
        changeMonth: true,
        changeYear: true,
        dateFormat: 'dd/mm/yy'
    });
}

function ValidateForm()
{
	ReturnValue = true;

	var ProductList = [];

	$('.ProductList').each(function () {
		if (ProductList.includes($(this).val()))
		{
			alert('You cannot select a product twice in the same transaction.');
			ReturnValue = false;
		}

		ProductList.push($(this).val());
	});
	return ReturnValue;
}
</script>
<!-- JavaScript To Print A Report -->
</body>
</html>
<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once("../../classes/school_administration/class.branch_staff.php");
require_once("../../classes/hra/class.advance_salary.php");

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

if ($LoggedUser->HasPermissionForTask(TASK_ADD_ADVANCE_SALARY) !== true)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}

$StaffCategoryList = array();
$StaffCategoryList = array('Teaching' => 'Teaching Staff', 'NonTeaching' => 'Non Teaching Staff');

$BranchStaffList = array();

$AllPaymentModeList = array();
$AllPaymentModeList = array('Cheque' => 'Cheque', 'Cash' => 'Cash', 'Online' => 'Online');

$AllAdvanceTypeList = array();
$AllAdvanceTypeList = array('GeneralAdvance' => 'General Advance', 'InterestFreeLoan' => 'Interest Free Loan');

$AllMonthsList = array();
$AllMonthsList = AdvanceSalary::GetAllMonths();

$HasErrors = false;

$Clean = array();

$Clean['StaffCategory'] = 'Teaching';

if (isset($_GET['StaffCategory']))
{
    $Clean['StaffCategory'] = strip_tags(trim($_GET['StaffCategory']));

    if (!array_key_exists($Clean['StaffCategory'], $StaffCategoryList))
    {
        header('location:../error.php');
        exit;
    }
}

$BranchStaffList = BranchStaff::GetActiveBranchStaff($Clean['StaffCategory']);
$Clean['BranchStaffID'] = key($BranchStaffList);

$Clean['AdvanceAmount'] = 0.0;
$Clean['PaymentMode'] = 'Cheque';

$Clean['AdvanceType'] = 'GeneralAdvance';
$Clean['NoOfInstallments'] = 0;

$Clean['AdvanceSalaryDedutionMonths'] = array();

$Clean['AdvanceSalaryInstalments'] = array();

$Clean['Process'] = 0;

if (isset($_POST['hdnProcess']))
{
	$Clean['Process'] = (int) $_POST['hdnProcess'];
}
switch ($Clean['Process'])
{
	case 1:
		if (isset($_POST['drdStaffCategory']))
		{
			$Clean['StaffCategory'] = strip_tags(trim($_POST['drdStaffCategory']));
		}

		if (isset($_POST['drdBranchStaff']))
		{
			$Clean['BranchStaffID'] = (int) $_POST['drdBranchStaff'];
		}

		if (isset($_POST['txtAdvanceAmount']))
		{
			$Clean['AdvanceAmount'] = strip_tags(trim($_POST['txtAdvanceAmount']));
		}

		if (isset($_POST['rdbPaymentMode']))
		{
			$Clean['PaymentMode'] = strip_tags(trim($_POST['rdbPaymentMode']));
		}

		if (isset($_POST['rdbAdvanceType']))
		{
			$Clean['AdvanceType'] = strip_tags(trim($_POST['rdbAdvanceType']));
		}

		if (isset($_POST['txtNoOfInstallments']))
		{
			$Clean['NoOfInstallments'] = strip_tags(trim($_POST['txtNoOfInstallments']));
		}

		if (isset($_POST['AdvanceSalaryDedutionMonths']) && is_array($_POST['AdvanceSalaryDedutionMonths']))
		{
			$Clean['AdvanceSalaryDedutionMonths'] = $_POST['AdvanceSalaryDedutionMonths'];
		}

		if (isset($_POST['txtAdvanceSalaryInstalmentAmount']) && is_array($_POST['txtAdvanceSalaryInstalmentAmount']))
		{
			$Clean['AdvanceSalaryInstalments'] = $_POST['txtAdvanceSalaryInstalmentAmount'];
		}

		$AdvanceSalaryDedutionMonths = array();
		$AdvanceSalaryDedutionMonths = $Clean['AdvanceSalaryDedutionMonths'];

		$NewRecordValidator = new Validator();

		$NewRecordValidator->ValidateInSelect($Clean['StaffCategory'], $StaffCategoryList, 'Unknown error, please try again.');

		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}

		$BranchStaffList = BranchStaff::GetActiveBranchStaff($Clean['StaffCategory']);

		$NewRecordValidator->ValidateInSelect($Clean['BranchStaffID'], $BranchStaffList, 'Unknown error, please try again.');
		$NewRecordValidator->ValidateNumeric($Clean['AdvanceAmount'], 'Advance salary amount should be numeric.');
		$NewRecordValidator->ValidateInSelect($Clean['PaymentMode'], $AllPaymentModeList, 'Unknown error, please try again.');
		$NewRecordValidator->ValidateInSelect($Clean['AdvanceType'], $AllAdvanceTypeList, 'Unknown error, please try again.');

		if ($Clean['AdvanceType'] == 'InterestFreeLoan') 
		{
			$NewRecordValidator->ValidateInteger($Clean['NoOfInstallments'], 'Advance salary amount should be integer.', 1);
		}

		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}
		
		if (count($Clean['AdvanceSalaryInstalments']) == 0) 
		{
			$NewRecordValidator->AttachTextError('please select deduction month.');
		}

		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}

		// Getting TimeStamp Of current month, First Date of month
		$CurrentMonthDate = '01-' . date('m-Y');
		$TimeStampCurrentMonthDate  = strtotime($CurrentMonthDate);
		
		$CounterForRow = 1;

		// Deduction month's time stamp cannot be less than starting month
		foreach ($Clean['AdvanceSalaryInstalments'] as $TimeStampOfMonth => $Value) 
		{	

			if ($TimeStampCurrentMonthDate > $TimeStampOfMonth) 
			{
				header('location:../error.php');
        		exit;
			}

			if (!isset($Clean['AdvanceSalaryDedutionMonths'][$TimeStampOfMonth])) 
			{
				$Clean['AdvanceSalaryDedutionMonths'][$TimeStampOfMonth]['Amount'] = $Value;
				$Clean['AdvanceSalaryDedutionMonths'][$TimeStampOfMonth]['IsChecked'] = 0;
			}

			$CounterForRow++;
		}

		Ksort($Clean['AdvanceSalaryDedutionMonths']);

		$AdvanceSalaryDetails = array();

		if ($Clean['AdvanceType'] == 'GeneralAdvance') 
		{
			$Clean['NoOfInstallments'] = 1;
			
			foreach ($AdvanceSalaryDedutionMonths as $TimeStampOfMonth => $Value) 
			{
				$AdvanceSalaryDetails[$TimeStampOfMonth]['Month'] = date('m', $TimeStampOfMonth);
				$AdvanceSalaryDetails[$TimeStampOfMonth]['Year'] = date('Y', $TimeStampOfMonth);
				$AdvanceSalaryDetails[$TimeStampOfMonth]['Amount'] = $Clean['AdvanceAmount'];
			}
		}
		else 
		{	
			$Amount = 0;
			$RemainingAmount = 0;

			$RemainingAmount = CalculateInstalmentAmount($Clean['AdvanceAmount'], $Clean['NoOfInstallments']);
			$Amount = (int) ($Clean['AdvanceAmount'] / $Clean['NoOfInstallments']);

			$CounterForRow = 1;

			foreach ($AdvanceSalaryDedutionMonths as $TimeStampOfMonth => $Value) 
			{
				if ($CounterForRow == $Clean['NoOfInstallments']) 
				{
					$Amount = $Amount + $RemainingAmount;
				}

				$AdvanceSalaryDetails[$TimeStampOfMonth]['Month'] = date('m', $TimeStampOfMonth);
				$AdvanceSalaryDetails[$TimeStampOfMonth]['Year'] = date('Y', $TimeStampOfMonth);;
				$AdvanceSalaryDetails[$TimeStampOfMonth]['Amount'] = $Amount;

				$CounterForRow++;
			}
		}

		$NewAdvanceSalary = new AdvanceSalary();
				
		$NewAdvanceSalary->SetBranchStaffID($Clean['BranchStaffID']);
		$NewAdvanceSalary->SetAdvanceAmount($Clean['AdvanceAmount']);
		$NewAdvanceSalary->SetPaymentMode($Clean['PaymentMode']);
		$NewAdvanceSalary->SetAdvanceType($Clean['AdvanceType']);
		$NewAdvanceSalary->SetNoOfInstallments($Clean['NoOfInstallments']);
		$NewAdvanceSalary->SetAdvanceSalaryInstallments($AdvanceSalaryDetails);
		
		$NewAdvanceSalary->SetCreateUserID($LoggedUser->GetUserID());

		if (!$NewAdvanceSalary->Save())
		{
			$NewRecordValidator->AttachTextError(ProcessErrors($NewAdvanceSalary->GetLastErrorCode()));
			$HasErrors = true;
			break;
		}

		header('location:advance_salary_list.php?Mode=ED&Process=7&StaffCategory=' . $Clean['StaffCategory']);
		exit;
		break;
}

require_once('../html_header.php');
?>
<title>Add Advance Salary</title>
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
                    <h1 class="page-header">Add Advance Salary</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="AddAdvanceSalary" action="add_advance_salary.php" method="post">
            	<div class="panel panel-default">
                    <div class="panel-heading">
                        Enter Advance Salary Details
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
                                <select class="form-control" id="StaffCategory" name="drdStaffCategory">
<?php
                                foreach ($StaffCategoryList as $StaffCategory => $StaffCategoryName)
                                {
?>
                                	<option value="<?php echo $StaffCategory; ?>" <?php echo ($StaffCategory == $Clean['StaffCategory'] ? 'selected="selected"' : ''); ?>><?php echo $StaffCategoryName; ?></option>
<?php
                                }
?>
                                </select>
                            </div>
                            <label for="BranchStaff" class="col-lg-2 control-label">Branch Staff</label>
                            <div class="col-lg-4">
                                <select class="form-control EmployeeSalaryDetails" name="drdBranchStaff" id="BranchStaffID">
<?php
                                foreach ($BranchStaffList as $BranchStaffID => $BranchStaffDetails)
                                {
?>
                                    <option value="<?php echo $BranchStaffID; ?>" <?php echo ($BranchStaffID == $Clean['BranchStaffID'] ? 'selected="selected"' : ''); ?> ><?php echo $BranchStaffDetails['FirstName'] . " ". $BranchStaffDetails['LastName']; ?></option>
<?php
                                }
?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="AdvanceAmount" class="col-lg-2 control-label">Advance Amount</label>
                            <div class="col-lg-4">
                        		<input class="form-control" type="text" id="AdvanceAmount" maxlength="10" name="txtAdvanceAmount" value="<?php echo ($Clean['AdvanceAmount']) ? $Clean['AdvanceAmount'] : ''; ?>">
                            </div>
                            <label for="PaymentMode" class="col-lg-2 control-label">Payment Mode</label>
                            <div class="col-lg-4">
<?php
                                foreach ($AllPaymentModeList as $PaymentMode => $PaymentModeName)
                                {
?>
								<label class="radio-inline">
									<input type="radio" id="" name="rdbPaymentMode" value="<?php echo $PaymentMode; ?>" <?php echo($Clean['PaymentMode'] == $PaymentMode) ? 'checked="checked"' : '';?> />&nbsp;<?php echo $PaymentModeName; ?>&nbsp;
								</label>                                    
<?php
                                }
?>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="AdvanceType" class="col-lg-2 control-label">Advance Type</label>
                            <div class="col-lg-4">
<?php
                                foreach ($AllAdvanceTypeList as $AdvanceType => $AdvanceTypeName)
                                {
?>
								<label class="radio-inline">
									<input class="advance-type" type="radio" id="" name="rdbAdvanceType" value="<?php echo $AdvanceType; ?>" <?php echo($Clean['AdvanceType'] == $AdvanceType) ? 'checked="checked"' : '';?> />&nbsp;<?php echo $AdvanceTypeName; ?>&nbsp;
								</label>                                    
<?php
                                }
?>
                            </div>
                        </div>
                        <div class="form-group InterestFreeLoan" style="<?php echo($Clean['AdvanceType'] == 'InterestFreeLoan') ? '' : 'display: none';?> ">
                        	<label for="NoOfInstallments" class="col-lg-2 control-label InterestFreeLoan" style="<?php echo($Clean['AdvanceType'] == 'InterestFreeLoan') ? '' : 'display: none'; ?>" >No Of Installments</label>
                            <div class="col-lg-4 InterestFreeLoan" style="<?php echo($Clean['AdvanceType'] == 'InterestFreeLoan') ? '' : 'display: none';?> ">
                        		<input class="form-control NoOfInstallments" type="text" id="NoOfInstallments" maxlength="3" name="txtNoOfInstallments" value="<?php echo ($Clean['NoOfInstallments']) ? $Clean['NoOfInstallments'] : ''; ?>" <?php echo($Clean['AdvanceType'] == 'InterestFreeLoan') ? '' : 'disabled="disabled"'; ?>/>
                            </div>
                            <input class="btn btn-primary btn-sm InterestFreeLoan" id="ViewInstallments" Number-of-installments="<?php echo ($Clean['NoOfInstallments']) ? $Clean['NoOfInstallments'] : ''; ?>"  type="button" name="ViewInstallment" value="View Installments" style="<?php echo($Clean['AdvanceType'] == 'InterestFreeLoan') ? '' : 'display: none'; ?>">
                        </div>
                        <div class="form-group" id="DeductionMonthList">
<?php
							if (Count($Clean['AdvanceSalaryDedutionMonths']) > 0 && is_array($Clean['AdvanceSalaryDedutionMonths'])) 
							{	
?>
								<label for="AdvanceType" class="col-lg-2 control-label">Deduction Month</label>	
								<div class="col-lg-8">
									<input type="hidden" name="txtTotalNumberOfMonth" id="TotalNumberOfMonthRows" value="<?php echo count($Clean['AdvanceSalaryInstalments']); ?>">
									<table width="100%" class="table table-striped table-bordered table-hover" id="DataTableRecords">
										<thead>
                                            <tr>
                                                <th>Deduction Months</th>
                                                <th>Deduction Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>									
<?php
										foreach ($Clean['AdvanceSalaryDedutionMonths'] as $TimeStamp => $AdvanceSalaryDedutionMonthDetail) 
										{
											$IsChecked = 1;
											$Amount = 0;

											if (isset($Clean['AdvanceSalaryDedutionMonths'][$TimeStamp]['IsChecked'])) 
											{
												$IsChecked = $Clean['AdvanceSalaryDedutionMonths'][$TimeStamp]['IsChecked'];
											}

											if (isset($Clean['AdvanceSalaryDedutionMonths'][$TimeStamp]['Amount'])) 
											{
												$Amount = $Clean['AdvanceSalaryDedutionMonths'][$TimeStamp]['Amount'];
											}	
?>
											<tr>
												<td>
													<label style="font-weight: normal;">
														<input class="GetNextMonthForDeduction" type="checkbox" time-stamp="<?php echo $TimeStamp; ?>" name="AdvanceSalaryDedutionMonths[<?php echo $TimeStamp; ?>][Amount]" value="<?php echo $Amount;?>" <?php echo ($IsChecked) ? 'checked="checked"' : ''; ?>/>&nbsp;<?php echo date('M Y', $TimeStamp); ?>
													</label>
												</td>
												<td>
													<input class="form-control" style="width:100%;" type="text" name="txtAdvanceSalaryInstalmentAmount[<?php echo $TimeStamp; ?>]" value="<?php echo $Amount; ?>" readonly="readonly">
												</td>
											</tr>																					
<?php
										}
?>
									</tbody>
									</table>
								</div>
<?php
							}
							
?>
                      	</div>
                        <div class="form-group">
	                        <div class="col-sm-offset-2 col-lg-10">
	                        	<input type="hidden" name="hdnProcess" value="1"/>
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
<script type="text/javascript">
$(function()
{ 	
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

	$('#AdvanceAmount').keyup(function()
	{
		if ($('input[name="rdbAdvanceType"]:checked').val() == 'GeneralAdvance') 
		{			

			var AdvanceSalaryAmount = parseFloat($('#AdvanceAmount').val());

			var NoOfInstalments = 1; //should be 1 because of GeneralAdvance

			if (!AdvanceSalaryAmount) 
			{
				$('#AdvanceAmount').focus();
				$('#DeductionMonthList').html('');
				alert('please enter advance amount.');
				return false;
			}

			$.ajax({
				url: '/xhttp_calls/get_advance_salary_breakup_monthwise.php?txtAdvanceAmount=' + AdvanceSalaryAmount +'&txtNoOfInstalments=' + NoOfInstalments,
				dataType: 'text',
				cache: false,
				contentType: false,
				processData: false,
				type: 'get',
				success: function(response){
					ResultArray = response.split("|*****|");

					if (ResultArray[0] == 'error')
					{
						alert (ResultArray[1]);
						return false;
					}
					else
					{
						$('#DeductionMonthList').html(ResultArray[1]);
					}
				}
			});
		}
		else if ($('input[name="rdbAdvanceType"]:checked').val() == 'InterestFreeLoan')
		{	

			var AdvanceSalaryAmount = parseFloat($('#AdvanceAmount').val());

			if (!AdvanceSalaryAmount) 
			{
				$('#AdvanceAmount').focus();
				$('#DeductionMonthList').html('');
				alert('please enter advance amount.');
				return false;
			}

			$('.InterestFreeLoan').prop('disabled', false);
			$('#NoOfInstallments').prop('disabled', false);
			$('#DeductionMonthList').html('');
			$('.InterestFreeLoan').show();
		}
	});

	$('.advance-type').click(function()
	{
		$('#DeductionMonthList').html('');

		if ($(this).val() == 'GeneralAdvance') 
		{			

			$('.InterestFreeLoan').prop('disabled', true);
			$('#NoOfInstallments').prop('disabled', true);
			$('.InterestFreeLoan').hide();

			var AdvanceSalaryAmount = parseFloat($('#AdvanceAmount').val());

			var NoOfInstalments = 1; //should be 1 because of GeneralAdvance

			if (!AdvanceSalaryAmount) 
			{
				$('#AdvanceAmount').focus();
				$('#DeductionMonthList').html('');
				alert('please enter advance amount.');
				return false;
			}

			if (!AdvanceSalaryAmount) 
			{
				$('#AdvanceAmount').focus();
				alert('please enter advance amount.');
				return false;
			}

			$.ajax({
				url: '/xhttp_calls/get_advance_salary_breakup_monthwise.php?txtAdvanceAmount=' + AdvanceSalaryAmount +'&txtNoOfInstalments=' + NoOfInstalments,
				dataType: 'text',
				cache: false,
				contentType: false,
				processData: false,
				type: 'get',
				success: function(response){
					ResultArray = response.split("|*****|");

					if (ResultArray[0] == 'error')
					{
						alert (ResultArray[1]);
						return false;
					}
					else
					{
						$('#DeductionMonthList').html(ResultArray[1]);
					}
				}
			});
		}
		else if ($(this).val() == 'InterestFreeLoan')
		{	
			var AdvanceSalaryAmount = parseFloat($('#AdvanceAmount').val());

			if (!AdvanceSalaryAmount) 
			{
				$('#AdvanceAmount').focus();
				$('#DeductionMonthList').html('');
				alert('please enter advance amount.');
				return false;
			}

			$('.InterestFreeLoan').prop('disabled', false);
			$('#NoOfInstallments').prop('disabled', false);
			$('.InterestFreeLoan').show();
		}
	});

	$('#ViewInstallments').click(function()
	{
		var AdvanceSalaryAmount = parseFloat($('#AdvanceAmount').val());
		var NoOfInstalments =  parseInt($('#NoOfInstallments').val());

		if (!AdvanceSalaryAmount) 
		{
			$('#AdvanceAmount').focus();
			$('#DeductionMonthList').html('');
			alert('please enter advance amount.');
			return false;
		}

		$.ajax({
			url: '/xhttp_calls/get_advance_salary_breakup_monthwise.php?txtAdvanceAmount=' + AdvanceSalaryAmount +'&txtNoOfInstalments=' + NoOfInstalments,
			dataType: 'text',
			cache: false,
			contentType: false,
			processData: false,
			type: 'get',
			success: function(response){
				ResultArray = response.split("|*****|");

				if (ResultArray[0] == 'error')
				{
					alert (ResultArray[1]);
					return false;
				}
				else
				{
					$('#DeductionMonthList').html(ResultArray[1]);
				}
			}
		});
	});

	$(document).on('change', '.GetNextMonthForDeduction', function()
	{	
	  	var AdvanceSalaryAmount = parseFloat($('#AdvanceAmount').val());
		var TotalNumberOfMonthRows =  parseInt($('#TotalNumberOfMonthRows').val());

		var Amount =  $(this).val();

		if (!AdvanceSalaryAmount) 
		{
			$('#AdvanceAmount').focus();
			$('#DeductionMonthList').html('');
			alert('please enter advance amount.');
			return false;
		}

		if ($(this).is(':checked')) // if checkbox is chechek them remove month row
		{	
			if ($(this).closest('tr').next('tr').length == 1) 
			{
				$('#DataTableRecords tr:last').remove();
			}
			else if ($(this).closest('tr').next('tr').length == 0) 
			{
				$('#DataTableRecords tr:eq(2)').remove();
			}

			if ($('input[name="rdbAdvanceType"]:checked').val() == 'GeneralAdvance') 
			{
				$('.GetNextMonthForDeduction').prop('checked', false);
				$(this).prop('checked', true);
			}

			$('#TotalNumberOfMonthRows').val(TotalNumberOfMonthRows - 1);

			return true;
		}

		var TimeStamp = $(this).attr('time-stamp');

		$.ajax({
			url: '/xhttp_calls/get_next_month_for_deduction.php?txtTimeStamp=' + TimeStamp + '&txtAmount=' + Amount + '&txtTotalNumberOfMonthRows=' + TotalNumberOfMonthRows,
			dataType: 'text',
			cache: false,
			contentType: false,
			processData: false,
			type: 'get',
			success: function(response){
				ResultArray = response.split("|*****|");

				if (ResultArray[0] == 'error')
				{
					alert (ResultArray[1]);
					return false;
				}
				else
				{	
					$('#TotalNumberOfMonthRows').val(TotalNumberOfMonthRows + 1);
					$('#DataTableRecords').append(ResultArray[1]);
				}
			}
		});

		return true;
	})
});
</script>
</body>
</html>
<?php
function CalculateInstalmentAmount($AdvanceSalaryAmount, $NoOfInstalments)
{	
	$Amount = 0;
	$RemainingAmount = 0;

	$Amount = (int) ($AdvanceSalaryAmount / $NoOfInstalments);
	$RemainingAmount = $AdvanceSalaryAmount - ($NoOfInstalments * $Amount);

	return $RemainingAmount;
}

?>
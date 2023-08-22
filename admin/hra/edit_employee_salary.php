<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once("../../classes/school_administration/class.branch_staff.php");
require_once("../../classes/hra/class.salary_parts.php");
require_once("../../classes/hra/class.employee_salary.php");

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

if ($LoggedUser->HasPermissionForTask(TASK_EDIT_EMPLOYEE_SALARY) !== true)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}

$Clean = array();
$Clean['EmployeeSalaryID'] = 0;
$Clean['StaffCategory'] = '';

if (isset($_GET['EmployeeSalaryID']))
{
    $Clean['EmployeeSalaryID'] = (int) $_GET['EmployeeSalaryID'];
}
elseif (isset($_POST['hdnEmployeeSalaryID']))
{
    $Clean['EmployeeSalaryID'] = (int) $_POST['hdnEmployeeSalaryID'];
}

if ($Clean['EmployeeSalaryID'] <= 0)
{
    header('location:../error.php');
    exit;
}   

try
{
    $EmployeeSalaryToEdit = new EmployeeSalary($Clean['EmployeeSalaryID']);
}
catch (ApplicationDBException $e)
{
    header('location:../error.php');
    exit;
}
catch (Exception $e)
{
    header('location:../error.php'); 
}

$StaffCategoryList = array();
$StaffCategoryList = array('Teaching' => 'Teaching Staff', 'NonTeaching' => 'Non Teaching Staff');

if (isset($_GET['StaffCategory']))
{
	$Clean['StaffCategory'] = strip_tags(trim($_GET['StaffCategory']));

	if (!array_key_exists($Clean['StaffCategory'], $StaffCategoryList))
	{
		header('location:../error.php');
		exit;
	}
}

$SalaryType = array();
$SalaryType = array('Monthly' => 'Monthly', 'Yearly' => 'Yearly');

$AllSalaryParts = array();
$AllSalaryParts = SalaryPart::GetActiveSalaryParts();

$BranchStaffList = array();

$HasErrors = false;
$Clean['Process'] = 0;

$BranchStaffList = BranchStaff::GetActiveBranchStaff($Clean['StaffCategory']);

$Clean['BranchStaffID'] = 0;
$Clean['SalaryType'] = 'Monthly';
$Clean['BasicSalary'] = 0.0;

$Clean['IsActive'] = 0;

$Clean['SalaryDetails'] = array();

if (isset($_POST['hdnProcess']))
{
	$Clean['Process'] = (int) $_POST['hdnProcess'];
}
elseif (isset($_GET['Process']))
{
	$Clean['Process'] = (int) $_GET['Process'];
}
switch ($Clean['Process'])
{
	case 3:
		if (isset($_POST['drdStaffCategory']))
		{
			$Clean['StaffCategory'] = strip_tags(trim($_POST['drdStaffCategory']));
		}

		if (isset($_POST['drdBranchStaffID']))
		{
			$Clean['BranchStaffID'] = (int) $_POST['drdBranchStaffID'];
		}

		if (isset($_POST['drdSalaryType']))
		{
			$Clean['SalaryType'] = strip_tags(trim($_POST['drdSalaryType']));
		}

		if (isset($_POST['txtBasicSalary']))
		{
			$Clean['BasicSalary'] = strip_tags(trim($_POST['txtBasicSalary']));
		}

		if (isset($_POST['txtSalaryAllowance']) && is_array($_POST['txtSalaryAllowance']))
		{
			$Clean['SalaryDetails'] = $_POST['txtSalaryAllowance'];
		}

		if (isset($_POST['chkIsActive']))
        {
            $Clean['IsActive'] = 1;
        }

		$BranchStaffList = BranchStaff::GetActiveBranchStaff($Clean['StaffCategory']);

		$NewRecordValidator = new Validator();

		$NewRecordValidator->ValidateInSelect($Clean['StaffCategory'], $StaffCategoryList, 'Unknown error, please try again.');

		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}

		$BranchStaffList = BranchStaff::GetActiveBranchStaff($Clean['StaffCategory']);

		$NewRecordValidator->ValidateInSelect($Clean['BranchStaffID'], $BranchStaffList, 'Unknown error, please try again.');

		$NewRecordValidator->ValidateInSelect($Clean['SalaryType'], $SalaryType, 'Unknown error, please try again.');
		$NewRecordValidator->ValidateNumeric($Clean['BasicSalary'], 'Basic salary is required and should be Integer.', 0);

		$Counter = 1;
		$EmployeeSalaryDetails = array();

		foreach ($Clean['SalaryDetails'] as $SalaryPartID => $SalaryDetailsValue)
		{	
			$PercentageOfBasic = 0;
			$Amount = 0;

			if (isset($SalaryDetailsValue['PercentageOfBasic'])) 
			{
				$PercentageOfBasic = $SalaryDetailsValue['PercentageOfBasic'];
			}

			if (isset($SalaryDetailsValue['Amount'])) 
			{
				$Amount = $SalaryDetailsValue['Amount'];
			}

			if (empty($PercentageOfBasic) && empty($Amount))
			{
				$PercentageOfBasic = 0;
				$Amount = 0;
			}

			if (!empty($PercentageOfBasic) && !empty($Amount))
			{
				$NewRecordValidator->ValidateInSelect($SalaryPartID, $AllSalaryParts, 'Unknown error, please try again.');
				$NewRecordValidator->ValidateNumeric($PercentageOfBasic, 'Percent of basic salary is required and should be numberic at row ' . $Counter);
				$NewRecordValidator->ValidateNumeric($Amount, 'Amount is required and should be numberic at row ' . $Counter);
				$Counter++;
			}

			$EmployeeSalaryDetails[$SalaryPartID]['PercentageOfBasic'] = $PercentageOfBasic;
			$EmployeeSalaryDetails[$SalaryPartID]['Amount'] = round($Amount);	
		}
		
		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}
	
		$EmployeeSalaryToEdit->SetBranchStaffID($Clean['BranchStaffID']);
		$EmployeeSalaryToEdit->SetSalaryType($Clean['SalaryType']);
		$EmployeeSalaryToEdit->SetBasicSalary($Clean['BasicSalary']);
		
		$EmployeeSalaryToEdit->SetSalaryDetails($EmployeeSalaryDetails);
		
		$EmployeeSalaryToEdit->SetIsActive($Clean['IsActive']);

		$EmployeeSalaryToEdit->SetCreateUserID($LoggedUser->GetUserID());

		if ($EmployeeSalaryToEdit->SalaryStructureExists())
		{
			$NewRecordValidator->AttachTextError('The salary structure of current branch staff you have added already exists.');
			$HasErrors = true;
			break;
		}

		if (!$EmployeeSalaryToEdit->Save())
		{
			$NewRecordValidator->AttachTextError(ProcessErrors($EmployeeSalaryToEdit->GetLastErrorCode()));
			$HasErrors = true;
			break;
		}
		
		header('location:employee_salary_list.php?Mode=UD&Process=7&StaffCategory=' . $Clean['StaffCategory']);
		exit;
		break;

	case 2:
		$Clean['BranchStaffID'] = $EmployeeSalaryToEdit->GetBranchStaffID();
		$Clean['SalaryType'] = $EmployeeSalaryToEdit->GetSalaryType();
	    $Clean['BasicSalary'] = $EmployeeSalaryToEdit->GetBasicSalary();
	    
	    $Clean['IsActive'] = $EmployeeSalaryToEdit->GetIsActive();

	    $CurrentBranchStaff = new BranchStaff($Clean['BranchStaffID']);
	    $Clean['StaffCategory'] = $CurrentBranchStaff->GetStaffCategory();
	    $BranchStaffList = BranchStaff::GetActiveBranchStaff($Clean['StaffCategory']);

	    $EmployeeSalaryToEdit->FillSalaryDetails($Clean['StaffCategory']);
	    $Clean['SalaryDetails'] = $EmployeeSalaryToEdit->GetSalaryDetails();
		break;
}
require_once('../html_header.php');
?>
<title>Edit Employee Salary</title>
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
                    <h1 class="page-header">Edit Employee Salary</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="EditEmployeeSalary" action="edit_employee_salary.php" method="post">
            	<div class="panel panel-default">
                    <div class="panel-heading">
                        Enter Employee Salary Details
                    </div>
                    <div class="panel-body">
<?php
						if ($HasErrors == true)
						{
							echo $NewRecordValidator->DisplayErrors();
						}
?>                    
                        <div class="form-group">
                            <label for="StaffCategory" class="col-lg-2 control-label">Select Staff Category</label>
                            <div class="col-lg-4">
                                <select class="form-control EmployeeSalaryDetails" name="drdStaffCategory" id="StaffCategory">
<?php
                                foreach ($StaffCategoryList as $StaffCategory => $StaffCategoryName)
                                {
?>
                                    <option value="<?php echo $StaffCategory; ?>" <?php echo ($StaffCategory == $Clean['StaffCategory']) ? 'selected="selected"' : ''; ?> ><?php echo $StaffCategoryName; ?></option>
<?php
                                }

?>
                                </select>
                            </div>
                            <label for="BranchStaff" class="col-lg-2 control-label">Select Branch Staff</label>
                            <div class="col-lg-4">
                                <select class="form-control EmployeeSalaryDetails"  name="drdBranchStaffID" id="BranchStaffID">>
                                	<option value="0">Select Branch Staff</option>
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
                            <label for="SalaryType" class="col-lg-2 control-label">Select Salary Type</label>
                            <div class="col-lg-4">
                                <select class="form-control EmployeeSalaryDetails" id="SalaryType" name="drdSalaryType">
<?php
                                foreach ($SalaryType as $Key => $SalaryTypeName)
                                {
?>
                                <option value="<?php echo $Key; ?>" <?php echo ($Key == $Clean['SalaryType'] ? 'selected="selected"' : ''); ?> ><?php echo $SalaryTypeName; ?></option>
<?php
                                }
?>
                                </select>
                            </div>
                            <label for="BasicSalary" class="col-lg-2 control-label">Basic Salary</label>
                            <div class="col-lg-4">
                                <input class="form-control EmployeeSalaryDetails" type="text" id="BasicSalary" name="txtBasicSalary" value="<?php echo ($Clean['BasicSalary']) ? $Clean['BasicSalary'] : ''?>">
                            </div>
                        </div> 
                    </div>
                </div>
                <div class="panel panel-default">
	                <div class="panel-heading">
	                    Enter Employee Allowances
	                </div>
	                <div class="panel-body">
<?php
					foreach ($AllSalaryParts as $SalaryPartID => $AllSalaryPartsDetails) 
					{
						if ($AllSalaryPartsDetails['SalaryPartType'] == 'Allowance')
						{
							$PercentageOfBasic = 0;
							$Amount = 0;

							if (isset($Clean['SalaryDetails'][$SalaryPartID]['PercentageOfBasic']))
							{
								$PercentageOfBasic = $Clean['SalaryDetails'][$SalaryPartID]['PercentageOfBasic'];
							}

							if (isset($Clean['SalaryDetails'][$SalaryPartID]['Amount']))
							{
								$Amount = round($Clean['SalaryDetails'][$SalaryPartID]['Amount']);
							}

?>							<div class="form-group">
								<label for="AllowancePercent" class="col-lg-2 control-label"><?php echo $AllSalaryPartsDetails['SalaryPartName']; ?>
								</label>
	                            <div class="col-lg-4">
	                                <input class="form-control ClassAllowancePercent"  salary_part_id="<?php echo $SalaryPartID; ?>" style="text-transform:lowercase;" type="text" id="AllowancePercent<?php echo $SalaryPartID; ?>" name="txtSalaryAllowance[<?php echo $SalaryPartID; ?>][PercentageOfBasic]" value="<?php echo($PercentageOfBasic) ? $PercentageOfBasic : ''; ?>" placeholder="Enter <?php echo $AllSalaryPartsDetails['SalaryPartName'];?> in %">
	                            </div>
	                            <label for="AllowanceAmount" class="col-lg-2 control-label">Amount</label>
	                            <div class="col-lg-4">
	                                <input class="form-control ClassAllowanceAmount" salary_part_id="<?php echo $SalaryPartID; ?>" type="text" id="AllowanceAmount<?php echo $SalaryPartID; ?>" name="txtSalaryAllowance[<?php echo $SalaryPartID; ?>][Amount]" value="<?php echo($Amount) ? $Amount : ''; ?>" placeholder="(in ₹ )">
	                            </div>
	                        </div>
<?php
						}
					}
?>
                    </div>
                </div>
                <div class="panel panel-default">
                    <div class="panel-heading">
                        Enter Employee Deductions
                    </div>
                    <div class="panel-body">
<?php
					foreach ($AllSalaryParts as $SalaryPartID => $AllSalaryPartsDetails) 
					{
						if ($AllSalaryPartsDetails['SalaryPartType'] == 'Deduction')
						{
							$PercentageOfBasic = 0;
							$Amount = 0;

							if (isset($Clean['SalaryDetails'][$SalaryPartID]['PercentageOfBasic'])) 
							{
								$PercentageOfBasic = $Clean['SalaryDetails'][$SalaryPartID]['PercentageOfBasic'];
							}

							if (isset($Clean['SalaryDetails'][$SalaryPartID]['Amount'])) 
							{
								$Amount = round($Clean['SalaryDetails'][$SalaryPartID]['Amount']);
							}

?>							<div class="form-group">
								<label for="AllowancePercent" class="col-lg-2 control-label"><?php echo $AllSalaryPartsDetails['SalaryPartName']; ?></label>
	                            <div class="col-lg-4">
	                                <input class="form-control ClassAllowancePercent" salary_part_id="<?php echo $SalaryPartID; ?>"  style="text-transform:lowercase"; type="text" id="AllowancePercent<?php echo $SalaryPartID; ?>" name="txtSalaryAllowance[<?php echo $SalaryPartID; ?>][PercentageOfBasic]" value="<?php echo($PercentageOfBasic) ? $PercentageOfBasic : ''; ?>" placeholder="Enter <?php echo $AllSalaryPartsDetails['SalaryPartName']; ?> in %">
	                            </div>
	                            <label for="AllowanceAmount" class="col-lg-2 control-label">Amount</label>
	                            <div class="col-lg-4">
	                                <input class="form-control ClassAllowanceAmount" salary_part_id="<?php echo $SalaryPartID; ?>" type="text" id="AllowanceAmount<?php echo $SalaryPartID; ?>" name="txtSalaryAllowance[<?php echo $SalaryPartID; ?>][Amount]" value="<?php echo($Amount) ? $Amount : ''; ?>" placeholder="(in ₹ )">
	                            </div>
	                        </div>
<?php
						}
					}
?>
                    </div>
                    <div class="form-group">
                        <label for="IsActive" class="col-lg-2 control-label">Is Active</label>
                        <div class="col-lg-4">
                            <input type="checkbox" id="IsActive" name="chkIsActive" value="1" <?php echo ($Clean['IsActive'] == 1) ? 'checked="checked"' : '';?> />
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-sm-offset-2 col-lg-10">
                        	<input type="hidden" name="hdnProcess" value="3"/>
                        	<input type="hidden" name="hdnEmployeeSalaryID" value="<?php echo $Clean['EmployeeSalaryID']; ?>"/>
							<button type="submit" class="btn btn-primary">Save</button>
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
<script type="text/javascript">
$(function()
{ 	
	var StaffCategoryBeforeChange;

	$('#StaffCategory').focus(function()
	{
		StaffCategoryBeforeChange = $(this).val();		
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

	$('.EmployeeSalaryDetails').change(function(){
		$('.ClassAllowancePercent').val('');
		$('.ClassAllowanceAmount').val('');
	});

	$('.ClassAllowancePercent').focus(function()
	{
		var AllowancePercent = 'AllowancePercent'+ $(this).attr("salary_part_id");
		var AllowanceAmount = 'AllowanceAmount'+ $(this).attr("salary_part_id");

		$('#'+AllowanceAmount).val('');
	});

	$('.ClassAllowancePercent').blur(function()
	{

		var BasicSalary = 0;
		var AllowancePercentageOfBasic = 0;
		var SalaryType = '';

		var AmountOfPercent = 0;

		var StringAllowancePercent = 'AllowancePercent'+ $(this).attr("salary_part_id");
		var StringAllowanceAmount = 'AllowanceAmount'+ $(this).attr("salary_part_id");

		AllowancePercentageOfBasic = parseFloat($('#'+StringAllowancePercent).val());

		if($('#BasicSalary').val() == '')
		{
			$('#BasicSalary').focus();
			return false;
		}

		SalaryType = $('#SalaryType').val();
		BasicSalary = parseFloat($('#BasicSalary').val());

		if(BasicSalary == 0)
		{
			$('#BasicSalary').focus();
			return false;
		}

		if (SalaryType == 'Yearly') 
		{
			BasicSalary = parseFloat(BasicSalary / 12).toFixed(2);
		}


		AmountOfPercent = CalculateAmount(BasicSalary, AllowancePercentageOfBasic);

		if(AmountOfPercent)
		{	
			$('#'+StringAllowanceAmount).val(AmountOfPercent);
		}		
	});

	$('.ClassAllowanceAmount').blur(function()
	{
		var BasicSalary = 0;
		var AmountForSalaryPart = 0;
		var SalaryType = '';

		var AllowanceAmount = 0;

		var StringAllowancePercent = 'AllowancePercent'+ $(this).attr("salary_part_id");
		var StringAllowanceAmount = 'AllowanceAmount'+ $(this).attr("salary_part_id");

		AmountForSalaryPart = parseFloat($('#'+StringAllowanceAmount).val());

		if($('#BasicSalary').val() == '')
		{
			$('#BasicSalary').focus();
			return false;
		}

		SalaryType = $('#SalaryType').val();
		BasicSalary = parseFloat($('#BasicSalary').val());

		if(BasicSalary == 0)
		{
			$('#BasicSalary').focus();
			return false;
		}

		if (SalaryType == 'Yearly') 
		{
			BasicSalary = parseFloat(BasicSalary / 12).toFixed(2);
		}

		var PercentOfAmount = CalculatePercentage(BasicSalary, AmountForSalaryPart);

		if (PercentOfAmount)
		{	
			$('#'+StringAllowancePercent).val(PercentOfAmount);
		}		
	});
});

function CalculateAmount(BasicSalary, AllowancePercentageOfBasic)
{
	var Amount = Math.round(((BasicSalary * AllowancePercentageOfBasic) / 100));

	if (Amount == 'NaN') 
	{
		return 0;
	}

	return Amount;
}

function CalculatePercentage(BasicSalary, AmountForSalaryPart)
{
	var Percent = ((AmountForSalaryPart * 100) / BasicSalary).toFixed(2);
	if (Percent == 'NaN') 
	{
		return 0;
	}

	return Percent;
}
</script>
</body>
</html>
</script>
</body>
</html>
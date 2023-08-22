<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once("../../classes/hra/class.salary_parts.php");
require_once("../../classes/hra/class.leave_deduction_rule.php");

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

if ($LoggedUser->HasPermissionForTask(TASK_ADD_LEAVE_DEDUCTION_RULE) !== true)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}

$StaffCategoryList = array();
$StaffCategoryList = array('Teaching' => 'Teaching Staff', 'NonTeaching' => 'Non Teaching Staff');

$RuleTypeList = array();
$RuleTypeList = array('FullDay' => 'Full Day', 'HalfDay' => 'Half Day');

$AllSalaryPartList = array();
$AllSalaryPartList = SalaryPart::GetActiveSalaryParts('Allowance');

$HasErrors = false;

$Clean = array();
$Clean['Process'] = 0;

$Clean['StaffCategory'] = 'Teaching';
$Clean['RuleType'] = 'FullDay';
$Clean['DeductionPercentageDetails'] = array();

if (isset($_POST['hdnProcess']))
{
	$Clean['Process'] = (int) $_POST['hdnProcess'];
}
switch ($Clean['Process'])
{
	case 1:						
		if (isset($_POST['rdbRuleType']))
		{
			$Clean['RuleType'] = strip_tags(trim($_POST['rdbRuleType']));
		}

		if (isset($_POST['drdStaffCategory']))
		{
			$Clean['StaffCategory'] = strip_tags(trim($_POST['drdStaffCategory']));
		}

		if (isset($_POST['txtDeductionPercentageDetails']) && is_array($_POST['txtDeductionPercentageDetails']))
		{
			$Clean['DeductionPercentageDetails'] = $_POST['txtDeductionPercentageDetails'];
		}
		
		$NewRecordValidator = new Validator();
		$NewRecordValidator->ValidateInSelect($Clean['RuleType'], $RuleTypeList, 'Unknown Error, Please try again.');
		$NewRecordValidator->ValidateInSelect($Clean['StaffCategory'], $StaffCategoryList, 'Unknown Error, Please try again.');

		$CounterForDeductionPercentage = 1;

		foreach ($Clean['DeductionPercentageDetails'] as $SalaryPartID => $DeductionPercentage) 
		{
			$NewRecordValidator->ValidateInSelect($SalaryPartID, $AllSalaryPartList, 'Unknown Error, Please try again.');
			$NewRecordValidator->ValidateNumeric($DeductionPercentage, 'Percentage of allowance should be numeric at row' . $CounterForDeductionPercentage);

			$CounterForDeductionPercentage++;
		}
		
		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}
				
		$NewLeaveDeductionRule = new LeaveDeductionRule();
				
		$NewLeaveDeductionRule->SetRuleType($Clean['RuleType']);
		$NewLeaveDeductionRule->SetStaffCategory($Clean['StaffCategory']);
		$NewLeaveDeductionRule->SetDeductionPercentageDetails($Clean['DeductionPercentageDetails']);
		
		$NewLeaveDeductionRule->SetCreateUserID($LoggedUser->GetUserID());

		if ($NewLeaveDeductionRule->RecordExists())
		{
			$NewRecordValidator->AttachTextError('The deduction rule you have added already exists.');
			$HasErrors = true;
			break;
		}

		if (!$NewLeaveDeductionRule->Save())
		{
			$NewRecordValidator->AttachTextError(ProcessErrors($NewLeaveDeductionRule->GetLastErrorCode()));
			$HasErrors = true;

			break;
		}
		
		header('location:leave_deduction_rules_list.php?Mode=ED');
		exit;
		break;

	default:
		foreach ($AllSalaryPartList as $SalaryPartID => $Value) 
		{
			$Clean['DeductionPercentageDetails'][$SalaryPartID] = '';
		}

		break;
}

require_once('../html_header.php');
?>
<title>Add Leave Deduction Rule</title>
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
                    <h1 class="page-header">Add Leave Deduction Rule</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="AddLeaveDeductionRule" action="add_leave_deduction_rule.php" method="post">
            	<div class="panel panel-default">
                    <div class="panel-heading">
                        Deduction Rule
                    </div>
                    <div class="panel-body">
<?php
						if ($HasErrors == true)
						{
							echo $NewRecordValidator->DisplayErrors();
						}
?>                    	
						<div class="form-group">
                            <label for="RuleType" class="col-lg-3 control-label">Rule Type</label>
                            <div class="col-lg-4">
<?php
                                foreach ($RuleTypeList as $RuleType => $RuleTypeName)
                                {
?>
									<label style="font-weight: normal;"><input type="radio" name="rdbRuleType" value="<?php echo $RuleType; ?>" <?php echo ($RuleType == $Clean['RuleType'] ? 'checked="checked"' : ''); ?> />&nbsp;<?php echo $RuleTypeName; ?></label>&nbsp;&nbsp;
<?php
                                }
?>
                            </div>
                        </div>
                    	<div class="form-group">
                            <label for="StaffCategory" class="col-lg-3 control-label">Staff Category</label>
                            <div class="col-lg-4">
                                <select class="form-control" id="StaffCategory" name="drdStaffCategory">
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
                        </div>
                    </div>
                </div>
            	<div class="panel panel-default">
                    <div class="panel-heading">
                        Deduction Rule Details
                    </div>
                    <div class="panel-body">                  	
						
<?php
                        foreach ($AllSalaryPartList as $SalaryPartID => $SalaryPartName)
                        {
?>
						<div class="form-group">
                            <label for="RuleType<?php echo $SalaryPartID;?>" class="col-lg-3 control-label"><?php echo $SalaryPartName;?></label>
                            <div class="col-lg-4">
								<input class="form-control" type="text" id="RuleType<?php echo $SalaryPartID;?>" maxlength="3" name="txtDeductionPercentageDetails[<?php echo $SalaryPartID; ?>]" value="<?php echo $Clean['DeductionPercentageDetails'][$SalaryPartID]; ?>" placeholder="Deduction in %" />
							</div>
            			</div>
<?php
                        }
?>
                        <div class="form-group">
	                        <div class="col-sm-offset-3 col-lg-10">
	                        	<input type="hidden" name="hdnProcess" value="1" />
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
</body>
</html>
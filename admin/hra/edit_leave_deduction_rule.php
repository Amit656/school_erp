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

if ($LoggedUser->HasPermissionForTask(TASK_EDIT_LEAVE_DEDUCTION_RULE) !== true)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}

$Clean = array();
$Clean['LeaveDeductionRuleID'] = 0;

if (isset($_GET['LeaveDeductionRuleID']))
{
    $Clean['LeaveDeductionRuleID'] = (int) $_GET['LeaveDeductionRuleID'];
}
else if (isset($_POST['hdnLeaveDeductionRuleID']))
{
    $Clean['LeaveDeductionRuleID'] = (int) $_POST['hdnLeaveDeductionRuleID'];
}

if ($Clean['LeaveDeductionRuleID'] <= 0)
{
    header('location:../error.php');
    exit;
}   

try
{
    $LeaveDeductionRuleToEdit = new LeaveDeductionRule($Clean['LeaveDeductionRuleID']);
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

$RuleTypeList = array();
$RuleTypeList = array('FullDay' => 'Full Day', 'HalfDay' => 'Half Day');

$AllSalaryPartList = array();
$AllSalaryPartList = SalaryPart::GetActiveSalaryParts('Allowance');

$HasErrors = false;
$ViewOnly = false;

$Clean['Process'] = 0;

$Clean['StaffCategory'] = 'Teaching';
$Clean['RuleType'] = 'FullDay';
$Clean['DeductionPercentageDetails'] = array();

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
							
		$LeaveDeductionRuleToEdit->SetRuleType($Clean['RuleType']);
		$LeaveDeductionRuleToEdit->SetStaffCategory($Clean['StaffCategory']);
		$LeaveDeductionRuleToEdit->SetDeductionPercentageDetails($Clean['DeductionPercentageDetails']);
		
		$LeaveDeductionRuleToEdit->SetCreateUserID($LoggedUser->GetUserID());

		if ($LeaveDeductionRuleToEdit->RecordExists())
		{
			exit;
			$NewRecordValidator->AttachTextError('The deduction rule you have added already exists.');
			$HasErrors = true;
			break;
		}
		
		if (!$LeaveDeductionRuleToEdit->Save())
		{
			$NewRecordValidator->AttachTextError(ProcessErrors($LeaveDeductionRuleToEdit->GetLastErrorCode()));
			$HasErrors = true;

			break;
		}
		
		header('location:leave_deduction_rules_list.php?Mode=UD');
		exit;
		break;

	case 2:
	    $Clean['RuleType'] = $LeaveDeductionRuleToEdit->GetRuleType();
	    $Clean['StaffCategory'] = $LeaveDeductionRuleToEdit->GetStaffCategory();

	    $LeaveDeductionRuleToEdit->FillDeductionPercentageDetails();
	    $Clean['DeductionPercentageDetails'] = $LeaveDeductionRuleToEdit->GetDeductionPercentageDetails();
	    break;
}

require_once('../html_header.php');
?>
<title>Edit Leave Deduction Rule</title>
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
                    <h1 class="page-header">Edit Leave Deduction Rule</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="EditLeaveDeductionRule" action="edit_leave_deduction_rule.php" method="post">
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
                                <select class="form-control"  name="drdStaffCategory">
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
                            <label for="RuleType" class="col-lg-3 control-label"><?php echo $SalaryPartName;?></label>
                            <div class="col-lg-4">
								<input class="form-control" type="text" name="txtDeductionPercentageDetails[<?php echo $SalaryPartID; ?>]" value="<?php echo $Clean['DeductionPercentageDetails'][$SalaryPartID]; ?>" placeholder="Deduction in %" />
							</div>
            			</div>
<?php
                        }
?>
                        <div class="form-group">
	                        <div class="col-sm-offset-3 col-lg-10">
	                        	<input type="hidden" name="hdnProcess" value="3" />
	                        	<input type="hidden" name="hdnLeaveDeductionRuleID" value="<?php echo $Clean['LeaveDeductionRuleID']; ?>" />
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
if (isset($_GET['ViewOnly']))
{
    $ViewOnly = true;
}
?>
<script type="text/javascript">
$(document).ready(function() 
{	
	var ViewOnly = '<?php echo $ViewOnly; ?>';

	if (ViewOnly)
    {
        $('input, select, textarea').prop('disabled', true);
        $('#Check').hide();
        $('button[type="submit"]').text('Close').attr('onClick', 'window.close();');
    }
 });
 </script>
</body>
</html>
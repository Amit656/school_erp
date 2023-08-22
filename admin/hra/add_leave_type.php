<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once("../../classes/hra/class.leave_types.php");

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

if ($LoggedUser->HasPermissionForTask(TASK_ADD_LEAVE_TYPE) !== true)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}

$StaffCategoryList = array();
$StaffCategoryList = array('Teaching' => 'Teaching Staff', 'NonTeaching' => 'Non Teaching Staff');

$LeaveModeList = array();
$LeaveModeList = array('Monthly' => 'Monthly', 'Yearly' => 'Yearly');

$LeavePayTypeList = array();
$LeavePayTypeList = array('WithoutPay' => 'Without Pay', 'WithPay' => 'With Pay');

$HasErrors = false;

$Clean = array();
$Clean['Process'] = 0;

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

$Clean['LeaveType'] = '';
$Clean['NoOfLeaves'] = 0;
$Clean['LeaveMode'] = 'Monthly';

$Clean['LeavePayType'] = 'WithoutPay';
$Clean['MonthlyCarryForward'] = 0;

$Clean['Priority'] = 0;

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

		if (isset($_POST['txtLeaveType']))
		{
			$Clean['LeaveType'] = strip_tags(trim($_POST['txtLeaveType']));
		}

		if (isset($_POST['txtNoOfLeaves']))
		{
			$Clean['NoOfLeaves'] = strip_tags(trim($_POST['txtNoOfLeaves'])) ;
		}

		if (isset($_POST['rdoLeaveMode']))
		{
			$Clean['LeaveMode'] = strip_tags(trim($_POST['rdoLeaveMode']));
		}

		if (isset($_POST['rdoLeavePayType']))
		{
			$Clean['LeavePayType'] = strip_tags(trim($_POST['rdoLeavePayType']));
		}

		if (isset($_POST['chkIsMonthlyCarryForward']))
		{
			$Clean['MonthlyCarryForward'] = 1;
		}

		if (isset($_POST['txtPriority']))
		{
			$Clean['Priority'] = strip_tags(trim($_POST['txtPriority']));
		}
		
		$NewRecordValidator = new Validator();

		$NewRecordValidator->ValidateInSelect($Clean['StaffCategory'], $StaffCategoryList, 'Unknown error, please try again.');
		$NewRecordValidator->ValidateStrings($Clean['LeaveType'], 'Leave type is required and should be between 2 and 25 characters.', 2, 25);
		$NewRecordValidator->ValidateInteger($Clean['NoOfLeaves'], 'No Of Leaves Allowed is required and should be Integer.', 0);

		$NewRecordValidator->ValidateInSelect($Clean['LeaveMode'], $LeaveModeList, 'Unknown error, please try again.');
		$NewRecordValidator->ValidateInSelect($Clean['LeavePayType'], $LeavePayTypeList, 'Unknown error, please try again.');	
		
		$NewRecordValidator->ValidateInteger($Clean['Priority'], 'Priority is required and should be integer.', 0);

		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}
				
		$NewLeaveType = new LeaveType();
				
		$NewLeaveType->SetStaffCategory($Clean['StaffCategory']);
		$NewLeaveType->SetLeaveType($Clean['LeaveType']);
		$NewLeaveType->SetNoOfLeaves($Clean['NoOfLeaves']);

		$NewLeaveType->SetLeaveMode($Clean['LeaveMode']);
		$NewLeaveType->SetLeavePayType($Clean['LeavePayType']);
		$NewLeaveType->SetMonthlyCarryForward($Clean['MonthlyCarryForward']);

		$NewLeaveType->SetPriority($Clean['Priority']);

		$NewLeaveType->SetCreateUserID($LoggedUser->GetUserID());

		if ($NewLeaveType->RecordExists())
		{
			$NewRecordValidator->AttachTextError('The leave type name you have added already exists.');
			$HasErrors = true;
			break;
		}

		if (!$NewLeaveType->Save())
		{
			$NewRecordValidator->AttachTextError(ProcessErrors($NewLeaveType->GetLastErrorCode()));
			$HasErrors = true;
			break;
		}
		
		header('location:leave_types_list.php?Mode=ED&Process=7&StaffCategory=' . $Clean['StaffCategory']);
		exit;
		break;
}
require_once('../html_header.php');
?>
<title>Add Leave Type</title>
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
                    <h1 class="page-header">Add Leave Type</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="AddLeaveTypes" action="add_leave_type.php" method="post">
            	<div class="panel panel-default">
                    <div class="panel-heading">
                        Enter Leave Types Details
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
                            <label for="LeaveType" class="col-lg-2 control-label">Leave Type Name</label>
                            <div class="col-lg-4">
                            	<input class="form-control" type="text" maxlength="25" id="LeaveType" name="txtLeaveType" value="<?php echo $Clean['LeaveType'];?>">
                            </div>
                        </div> 
                        <div class="form-group">
							<label for="NoOfLeaves" class="col-lg-2 control-label">No Of Leaves Allowed</label>
                            <div class="col-lg-4">
                            	<input class="form-control" type="text" maxlength="2" id="NoOfLeaves" name="txtNoOfLeaves" value="<?php echo($Clean['NoOfLeaves']) ? $Clean['NoOfLeaves'] : "" ;?>">
                            </div>
                            <label for="LeaveMode" class="col-lg-2 control-label LableLeaveMode">Leave Mode</label>
                            <div class="col-lg-4">
<?php
							foreach($LeaveModeList as $LeaveMode => $LeaveModeName)
							{
?>
								<label class="radio-inline">
									<input class="rdbLeaveMode" type="radio" id="LeaveMode<?php echo $LeaveMode; ?>" name="rdoLeaveMode" <?php echo (($Clean['LeaveMode'] == $LeaveMode) ? 'checked="checked"' : ''); ?> value="<?php echo $LeaveMode; ?>"> <?php echo $LeaveModeName; ?>
								</label>						
<?php										
							}
?>
                            </div>
                        </div>
                        <div class="form-group">
							<label for="LeavePayType" class="col-lg-2 control-label">Leave Pay Type</label>
                            <div class="col-lg-4">
<?php
							foreach($LeavePayTypeList as $LeavePayType => $LeavePayTypeName)
							{
?>
								<label class="radio-inline rdnLable">
									<input type="radio" id="LeavePayType<?php echo $LeavePayType; ?>" name="rdoLeavePayType" <?php echo (($Clean['LeavePayType'] == $LeavePayType) ? 'checked="checked"' : ''); ?> value="<?php echo $LeavePayType; ?>"> <?php echo $LeavePayTypeName; ?>
								</label>						
<?php										
							}
?>
                            </div>
	                        <div id="MonthlyCarryForward">
	                            <label for="IsMonthlyCarryForward" class="col-lg-2 control-label">Is Monthly Carry Forward</label>
	                            <div class="col-lg-4">
	                            	<label style="font-weight: normal;">
										<input class="IsMonthlyCarryForward" type="checkbox" id="IsMonthlyCarryForward" name="chkIsMonthlyCarryForward" value="1" <?php echo ($Clean['MonthlyCarryForward'] == 1) ? 'checked="checked"' : '';?> />&nbsp;
									</label>
	                            </div>
	                        </div>
                        </div>
                        <div class="form-group">
                            <label for="Priority" class="col-lg-2 control-label">Priority</label>
                            <div class="col-lg-4">
                            	<input class="form-control" type="text" maxlength="3" id="Priority" name="txtPriority" value="<?php echo($Clean['Priority']) ? $Clean['Priority'] : '';?>" />
                            </div>
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
<script type="text/javascript">
$(function()
{
	$('.rdbLeaveMode').change(function()
	{
		if($(this).val() == 'Yearly')
		{
			$('.IsMonthlyCarryForward').attr('disabled', true);
		}
		else
		{
			$('.IsMonthlyCarryForward').attr('disabled', false);
		}
	});
})
</script>
</body>
</html>
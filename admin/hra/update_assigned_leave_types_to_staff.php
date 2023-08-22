<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once("../../classes/hra/class.leave_types.php");
require_once("../../classes/school_administration/class.branch_staff.php");

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

if ($LoggedUser->HasPermissionForTask(TASK_ASSIGN_LEAVE_TYPE_TO_STAFF_CATEGORY) !== true)
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

$MonthlyCarryForwardList = array();
$MonthlyCarryForwardList = array(0 => 'No', 1 => 'Yes');

$LeaveModeList = array();
$LeaveModeList = array('Monthly' => 'Monthly', 'Yearly' => 'Yearly');

$BranchStaffDetails = array();
$AllLeaveTypes = array();

$AllBranchStaffList = array(); 

$HasErrors = false;

$Clean = array();
$Clean['Process'] = 0;

$Clean['BranchStaffID'] = 0;
$Clean['StaffCategory'] = 'Teaching';

$AllBranchStaffList = BranchStaff::GetActiveBranchStaff($Clean['StaffCategory']);

$Clean['LeaveTypesDetails'] = array();

$Clean['NoOfLeaves'] = array();
$Clean['LeaveMode'] = array();

$Clean['LeavePayType'] = array();
$Clean['MonthlyCarryForward'] = array();

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
		if (isset($_POST['hdnBranchStaffID']))
		{
			$Clean['BranchStaffID'] = (int) $_POST['hdnBranchStaffID'];
		}

		if (isset($_POST['hdnStaffCategory']))
		{
			$Clean['StaffCategory'] = strip_tags(trim($_POST['hdnStaffCategory']));
		}

		if (isset($_POST['LeaveType']) && is_array($_POST['LeaveType']))
		{
			$Clean['LeaveTypesDetails'] = $_POST['LeaveType'];
		}

		if ($Clean['BranchStaffID'] <= 0) 
		{
			header('location:../error.php');
			exit;
		}

		$NewRecordValidator = new Validator();

		$NewRecordValidator->ValidateInSelect($Clean['StaffCategory'], $StaffCategoryList, 'Unknown Error, Please try again.');
		$AllLeaveTypes = LeaveType::GetBranchStaffAssignedLeaveTypes($Clean['BranchStaffID']);

		foreach ($Clean['LeaveTypesDetails'] as $LeaveTypeForStaffID => $LeaveTypesDetailsValue) 
		{
			$NewRecordValidator->ValidateInSelect($LeaveTypeForStaffID, $AllLeaveTypes, 'Unknown Error, Please try again.');
			$NewRecordValidator->ValidateInteger($LeaveTypesDetailsValue['NoOfLeaves'], 'No Of Leaves Allowed is required and should be Integer.', 0);
			$NewRecordValidator->ValidateInSelect($LeaveTypesDetailsValue['LeaveMode'], $LeaveModeList, 'Unknown Error, Please try again.');
			$NewRecordValidator->ValidateInSelect($LeaveTypesDetailsValue['LeavePayType'], $LeavePayTypeList, 'Unknown Error, Please try again.');
			$NewRecordValidator->ValidateInSelect($LeaveTypesDetailsValue['MonthlyCarryForward'], $MonthlyCarryForwardList, 'Unknown Error, Please try again.');
		}
		
		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}

		if (!LeaveType::UpdateBranchStaffLeaveTypes($Clean['LeaveTypesDetails']))
		{
			$NewRecordValidator->AttachTextError('There is problem in updating records');
			$HasErrors = true;
			break;
		}

		header('location:update_assigned_leave_types_to_staff.php?Mode=UD&Process=7&StaffCategory=' . $Clean['StaffCategory'] . '&BranchStaffID=' . $Clean['BranchStaffID']);
		exit;
		break;

	case 7:
    	if (isset($_POST['drdStaffCategory']))
		{
			$Clean['StaffCategory'] = strip_tags(trim($_POST['drdStaffCategory']));
		}

		if (isset($_POST['drdBranchStaff']))
		{
			$Clean['BranchStaffID'] = (int) $_POST['drdBranchStaff'];
		}
		else if (isset($_GET['BranchStaffID']))
		{
			$Clean['BranchStaffID'] = (int) $_GET['BranchStaffID'];
		}

		if ($Clean['BranchStaffID'] <= 0) 
		{
			header('location:../error.php');
			exit;
		}

		$NewRecordValidator = new Validator();

		$NewRecordValidator->ValidateInSelect($Clean['StaffCategory'], $StaffCategoryList, 'Unknown Error, Please try again.');
		
		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}

		$AllBranchStaffList = BranchStaff::GetActiveBranchStaff($Clean['StaffCategory']);
		$AllLeaveTypes = LeaveType::GetBranchStaffAssignedLeaveTypes($Clean['BranchStaffID']);
		$BranchStaffDetails = BranchStaff::GetBranchStaffDetailsByBranchStaffID($Clean['BranchStaffID']);
		break;
}

require_once('../html_header.php');
?>
<title>Update Assigned Leave Type To Staff</title>
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
                    <h1 class="page-header">Update Assigned Leave Type To Staff</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
            <form class="form-horizontal" name="UpdateAssignedLeaveTypeToStaff" action="update_assigned_leave_types_to_staff.php" method="post">
	        	<div class="panel panel-default">
	                <div class="panel-heading">
	                   Select Branch Staff
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
                            <label for="BranchStaffID" class="col-lg-2 control-label">BranchStaff List</label>
                            <div class="col-lg-4">
                            	<select class="form-control" name="drdBranchStaff" id="BranchStaffID">
<?php
									foreach ($AllBranchStaffList as $BranchStaffID => $BranchStaffName)
									{
?>
										<option <?php echo ($BranchStaffID == $Clean['BranchStaffID'] ? 'selected="selected"' : ''); ?> value="<?php echo $BranchStaffID; ?>"><?php echo $BranchStaffName['FirstName'] .' ' . $BranchStaffName['LastName']; ?></option>
<?php
									}

?>
                            	</select>
                            </div>
                        </div> 
                        <div class="form-group">
						    <div class="col-sm-offset-2 col-lg-10">
						    	<input type="hidden" name="hdnProcess" value="7"/>
						        <button type="submit" class="btn btn-primary">View</button>
						    </div>
						</div>
	                </div>
	            </div>
        	</form>
<?php
			if($Clean['Process'] == 7 && $HasErrors == false)
			{
?>
			<form class="form-horizontal" name="UpdateAssignedLeaveTypeToStaff" action="update_assigned_leave_types_to_staff.php" method="post">
            	<div class="panel panel-default">
                    <div class="panel-heading">
                        Enter Leave Types Details
                    </div>
                    <div class="panel-body">                    	
                        <div class="row" id="RecordTable">
                            <div class="col-lg-12">
                                <table width="100%" class="table table-striped table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th>S. No</th>
                                            <th>Leave Type</th>
                                            <th>No Of Leaves Allowed</th>
                                            <th>Leave Mode</th>
                                            <th>Leave Pay Type</th>
                                            <th>Is Monthly Carry Forward</th>
                                        </tr>
                                    </thead>
                                    <tbody>
<?php
                            if (is_array($AllLeaveTypes) && count($AllLeaveTypes) > 0)
                            {
                                $Counter = 0;
                                foreach ($AllLeaveTypes as $LeaveTypeForStaffID => $LeaveTypesDetails)
                                {
?>
                                        <tr>
                                            <td><?php echo ++$Counter; ?></td>
                                            <td><?php echo $LeaveTypesDetails['LeaveType']; ?></td>
                                            <td>
                                        		<input class="form-control" type="text" maxlength="25" id="NoOfLeaves" name="LeaveType[<?php echo $LeaveTypeForStaffID; ?>][NoOfLeaves]" value="<?php echo $LeaveTypesDetails['NoOfLeaves'];?>">
											</td>
											<td>
<?php
											foreach($LeaveModeList as $LeaveMode => $LeaveModeName)
											{
?>
												<label class="radio-inline">
													<input class="rdbLeaveMode" type="radio" id="LeaveMode<?php echo $LeaveMode; ?>" name="LeaveType[<?php echo $LeaveTypeForStaffID; ?>][LeaveMode]" <?php echo (($LeaveTypesDetails['LeaveMode'] == $LeaveMode) ? 'checked="checked"' : ''); ?> value="<?php echo $LeaveMode; ?>"> <?php echo $LeaveModeName; ?>
												</label>						
<?php										
											}
?>
											</td>
                                            <td>
<?php
											foreach($LeavePayTypeList as $LeavePayType => $LeavePayTypeName)
											{
?>
											<label class="radio-inline rdnLable">
												<input type="radio" id="LeavePayType<?php echo $LeavePayType; ?>" name="LeaveType[<?php echo $LeaveTypeForStaffID; ?>][LeavePayType]" <?php echo (($LeaveTypesDetails['LeavePayType'] == $LeavePayType) ? 'checked="checked"' : ''); ?> value="<?php echo $LeavePayType; ?>"> <?php echo $LeavePayTypeName; ?>
											</label>						
<?php										
											}
?>
                                            </td>
                                            <td>
                                            	<label style="font-weight: normal;">
<?php
													foreach ($MonthlyCarryForwardList as $MonthlyCarryForward => $MonthlyCarryForwardName) 
													{
?>
													<label class="radio-inline">
														<input class="MonthlyCarryForward" type="radio" id="LeaveMode<?php echo $LeaveMode; ?>" name="LeaveType[<?php echo $LeaveTypeForStaffID; ?>][MonthlyCarryForward]" <?php echo (($MonthlyCarryForward == 0) ? 'checked="checked"' : ''); ?> <?php echo (($LeaveTypesDetails['MonthlyCarryForward'] == 1) ? 'checked="checked"' : ''); ?> <?php echo (($LeaveTypesDetails['LeaveMode'] == 'Yearly' && $MonthlyCarryForward == 1) ? 'disabled="disabled"' : ''); ?> value="<?php echo $MonthlyCarryForward; ?>"> <?php echo $MonthlyCarryForwardName; ?>
													</label>
<?php													
													}
?>
												</label>                                           	
                                            </td>

                                        </tr>
<?php
                                }
                            }
                            else
                            {
?>
                                        <tr>
                                            <td colspan="6">No Records</td>
                                        </tr>
<?php
                            }
?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="form-group">
                        <div class="col-sm-offset-2 col-lg-10">
                        	<input type="hidden" name="hdnProcess" value="3"/>
                        	<input type="hidden" name="hdnStaffCategory" value="<?php echo $Clean['StaffCategory'];?>"/>
                        	<input type="hidden" name="hdnBranchStaffID" value="<?php echo $Clean['BranchStaffID'];?>"/>
							<button type="submit" class="btn btn-primary">Update</button>
                        </div>
                      </div>
                    </div>
                </div>
            </form>
<?php
		}	
?>
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
	$('#StaffCategory').change(function()
	{
		StaffCategory = $(this).val();
		
		if (StaffCategory <= 0)
		{
			$('#BranchStaffID').html('<option value="0">Select Section</option>');
			return false;
		}
		
		$.post("/xhttp_calls/get_branch_staff_by_staff_category.php", {SelectedStaffCategory:StaffCategory}, function(data)
		{
			ResultArray = data.split("|*****|");
			
			if (ResultArray[0] == 'error')
			{
				alert(ResultArray[1]);
			}
			else
			{
				$('#BranchStaffID').html(ResultArray[1]);
			}
		 });
	});

	$('.rdbLeaveMode').change(function()
	{
		if($(this).val() == 'Yearly')
		{
			$(this).closest('tr').find('.MonthlyCarryForward').eq(0).prop('checked', true);
			$(this).closest('tr').find('.MonthlyCarryForward').eq(1).attr('disabled', true);
		}
		else
		{			
			$(this).closest('tr').find('.MonthlyCarryForward').eq(1).attr('disabled', false);
		}
	});
})
</script>
</body>
</html>
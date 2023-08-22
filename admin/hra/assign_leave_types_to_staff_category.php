<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once("../../classes/school_administration/class.branch_staff.php");
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

if ($LoggedUser->HasPermissionForTask(TASK_ASSIGN_LEAVE_TYPE_TO_STAFF) !== true)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}

$StaffCategoryList = array();
$StaffCategoryList = array('Teaching' => 'Teaching Staff', 'NonTeaching' => 'Non Teaching Staff');

$AllLeaveTypesList = array();
$BranchStaffList = array();
$AssignedLeaveTypesToStaffCategory = array();

$HasErrors = false;

$Clean = array();
$Clean['StaffCategory'] = 'Teaching';
    
if (isset($_GET['StaffCategory']))
{
    $Clean['StaffCategory'] = strip_tags(trim($_GET['StaffCategory']));

    if (!array_key_exists($Clean['StaffCategory'], $StaffCategoryList))
    {
        header('location:../error.php');
    }
}

$Clean['Process'] = 0;
$Clean['StaffCategory'] = 'Teaching';

$Clean['LeaveType'] = '';
$Clean['NoOfLeaves'] = 0;
$Clean['LeaveMode'] = 'Monthly';

$Clean['LeavePayType'] = 'WithoutPay';
$Clean['MonthlyCarryForward'] = 0;

$Clean['AssignLeaveTypesToStaffCategory'] = array();

if (isset($_GET['Process']))
{
    $Clean['Process'] = (int) $_GET['Process'];
}
elseif (isset($_POST['hdnProcess']))
{
    $Clean['Process'] = (int) $_POST['hdnProcess'];
}
switch ($Clean['Process'])
{
	case 1:
		if (isset($_POST['chkAssignLeaveTypesToStaffCategory']) && is_array($_POST['chkAssignLeaveTypesToStaffCategory']))
		{
			$Clean['AssignLeaveTypesToStaffCategory'] = $_POST['chkAssignLeaveTypesToStaffCategory'];
		}

		if (isset($_POST['hdnStaffCategory']))
		{
			$Clean['StaffCategory'] = strip_tags(trim($_POST['hdnStaffCategory']));
		}

		$NewRecordValidator = new Validator();
		$NewRecordValidator->ValidateInSelect($Clean['StaffCategory'], $StaffCategoryList, 'Unknown Error, Please try again.');

		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}

		$AllLeaveTypesList = LeaveType::GetAllLeaveTypes($Clean['StaffCategory']);
		
		if(count($Clean['AssignLeaveTypesToStaffCategory']) > 0)
		{
			foreach ($Clean['AssignLeaveTypesToStaffCategory'] as $LeaveTypeID => $Value)
			{
				$NewRecordValidator->ValidateInSelect($LeaveTypeID, $AllLeaveTypesList, 'Unknown Error, Please try again.');
			}
		}
		
		if (!LeaveType::AssignedLeaveTypeExistsForBranchStaff($Clean['StaffCategory'], $Clean['AssignLeaveTypesToStaffCategory']))
		{
			$NewRecordValidator->AttachTextError('The leave type for staff already exists.');
			$HasErrors = true;
			break;
		}
		
		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}
		
		$BranchStaffList = BranchStaff::GetAllBranchStaff($Clean['StaffCategory'], true);

		$AssignedLeaveTypesToStaffCategory = new LeaveType();
				
		if (!$AssignedLeaveTypesToStaffCategory->AssignedLeaveTypesToStaffCategory($Clean['AssignLeaveTypesToStaffCategory'], $BranchStaffList))
		{
			$NewRecordValidator->AttachTextError(ProcessErrors($AssignedLeaveTypesToStaffCategory->GetLastErrorCode()));
			$HasErrors = true;
			break;
		}
        
		header('location:assign_leave_types_to_staff_category.php?Mode=ED&Process=7&StaffCategory=' . $Clean['StaffCategory']);
		exit;
	    break;

	case 7:
		if (isset($_POST['drdStaffCategory']))
		{
			$Clean['StaffCategory'] = strip_tags(trim($_POST['drdStaffCategory']));
		}

		$NewRecordValidator = new Validator();
		$NewRecordValidator->ValidateInSelect($Clean['StaffCategory'], $StaffCategoryList, 'Unknown Error, Please try again.');

		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}

		$AllLeaveTypesList = LeaveType::GetAllLeaveTypes($Clean['StaffCategory']);
		$AssignedLeaveTypesToStaffCategory = LeaveType::GetAssignedLeaveTypesToStaffCategory($Clean['StaffCategory']);
	    break;
}
require_once('../html_header.php');
?>
<title>Assign Leave Type To Staff Category</title>
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
                    <h1 class="page-header">Assign Leave Type To Staff Category</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="AssignLeaveTypesToStaffCategory" action="assign_leave_types_to_staff_category.php" method="post">
            	<div class="panel panel-default">
                    <div class="panel-heading">
                        Enter Assign Leave Type To Staff Category
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
                                <select class="form-control" name="drdStaffCategory">
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
                        <div class="form-group">
                        <div class="col-sm-offset-2 col-lg-10">
                        	<input type="hidden" name="hdnProcess" value="7"/>
							<button type="submit" class="btn btn-primary">View Leave Type</button>
                        </div>
                      </div>
                    </div>
                </div>
            </form>
<?php
    if($Clean['Process'] == 7)
    {
?>
            <div class="row">
                <div class="col-lg-12">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <strong>Total Records Returned: <?php echo(count($AllLeaveTypesList) > 0) ? count($AllLeaveTypesList) : '0'; ?></strong>
                        </div>

                        <!-- /.panel-heading -->
                        <div class="panel-body">
                            <div>
                                <div class="row">
                                    <div class="col-lg-6">&nbsp;</div>
                                    <div class="col-lg-6">
                                        <div class="print-btn-container"><button id="PrintButton" type="submit" class="btn btn-primary">Print</button></div>
                                    </div>
                                </div>
                                <div class="row" id="RecordTableHeading">
                                    <div class="col-lg-12">
                                        <div class="report-heading-container"><strong>Leave Types on <?php echo date('d-m-Y h:i A'); ?></strong></div>
                                    </div>
                                </div>
                                <form action="assign_leave_types_to_staff_category.php" method="post">
                                    <div class="row" id="RecordTable">
                                        <div class="col-lg-12">
                                            <table width="100%" class="table table-striped table-bordered table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>S. No</th>
                                                        <th>leave Type</th>
                                                        <th>No Of Leaves Allowed</th>
                                                        <th>leave Mode</th>
                                                        <th>leave Pay Type</th>
                                                        <th>monthly Carry Forward</th>
                                                        <th>
                                                        <label class="print-hidden"><input type="checkbox" id="SelectAll" name="chkSelectAll" value="1" checked="checked" />Select All</label>
                                                        </th>
                                                    </tr>
                                                </thead>
                                                <tbody>
<?php
                                        if (is_array($AllLeaveTypesList) && count($AllLeaveTypesList) > 0)
                                        {
                                            $Counter = 0;
                                            foreach ($AllLeaveTypesList as $LeaveTypeID => $LeaveTypesDetails)
                                            {
?>
                                                    <tr>
                                                        <td><?php echo ++$Counter; ?></td>
                                                        <td><?php echo $LeaveTypesDetails['LeaveType']; ?></td>
                                                        <td><?php echo $LeaveTypesDetails['NoOfLeaves']; ?></td>

                                                        <td><?php echo $LeaveTypesDetails['LeaveMode']; ?></td>
                                                        <td><?php echo $LeaveTypesDetails['LeavePayType']; ?></td>
                                                        <td><?php echo (($LeaveTypesDetails['MonthlyCarryForward']) ? 'Yes' : 'No'); ?></td>
                                                        <td>
                                                            <input class="chkAssignLeaveTypesToStaffCategory print-hidden" type="checkbox" name="chkAssignLeaveTypesToStaffCategory[<?php echo $LeaveTypeID; ?>]" value="<?php echo $LeaveTypeID; ?>" <?php echo(array_key_exists($LeaveTypeID, $AssignedLeaveTypesToStaffCategory) ? 'checked="checked" disabled="disabled"' : "")?> />&nbsp;
                                                              <?php echo(array_key_exists($LeaveTypeID, $AssignedLeaveTypesToStaffCategory) ? 'Yes' : 'No')?>  
                                                        </td>
                                                    </tr>
<?php
                                            }
                                        }
                                        else
                                        {
?>
                                                    <tr>
                                                        <td colspan="7">No Records</td>
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
			                        	<input type="hidden" name="hdnProcess" value="1"/>
			                        	<input type="hidden" name="hdnStaffCategory" value="<?php echo$Clean['StaffCategory'];?>"/>
										<button type="submit" class="btn btn-primary">Assign</button>
			                        </div>
			                      </div>
                                </form>
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
	$('#SelectAll').click(function()
		{
			if($('#SelectAll').prop("checked"))
			{
				$('.chkAssignLeaveTypesToStaffCategory').prop("checked", true);
			}
			else
			{
				$('.chkAssignLeaveTypesToStaffCategory').prop("checked", false);
			}
		});
});
</script>
<script src="/admin/js/print-report.js"></script>
</body>
</html>
<?php
ob_start();
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once("../../classes/school_administration/class.branch_staff.php");
require_once("../../classes/hra/class.leave_types.php");
require_once("../../classes/hra/class.employee_leaves.php");

require_once("../../includes/helpers.inc.php");
require_once("../../includes/global_defaults.inc.php");

require_once("../../classes/class.date_processing.php");

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

if ($LoggedUser->HasPermissionForTask(TASK_EDIT_EMPLOYEE_LEAVE) !== true && $LoggedUser->HasPermissionForTask(TASK_VIEW_EMPLOYEE_LEAVE) !== true)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}

$Clean = array();
$Clean['EmployeeLeaveID'] = 0;

if (isset($_GET['EmployeeLeaveID']))
{
    $Clean['EmployeeLeaveID'] = (int) $_GET['EmployeeLeaveID'];
}
elseif (isset($_POST['hdnEmployeeLeaveID']))
{
    $Clean['EmployeeLeaveID'] = (int) $_POST['hdnEmployeeLeaveID'];
}

if ($Clean['EmployeeLeaveID'] <= 0)
{
    header('location:../error.php');
    exit;
}   

try
{
    $EmployeeLeaveToEdit = new EmployeeLeave($Clean['EmployeeLeaveID']);
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

$EmployeeLeaveTypeList = array();
$AllBranchStaffList = array();

$BranchStaffDetails = array();
$EmployeeRemainingLeavesList = array();
$EmployeeLeaveSummaryList = array();
$EmployeeLeaveHistoryList = array();

$HasErrors = false;
$ViewOnly = false;

$Clean['Process'] = 0;
$Clean['StaffCategory'] = 'Teaching';

if (isset($_GET['StaffCategory']))
{
	$Clean['StaffCategory'] = strip_tags(trim($_GET['StaffCategory']));
}

if (!array_key_exists($Clean['StaffCategory'], $StaffCategoryList))
{
	header('location:../error.php');
	exit;
}

$AllBranchStaffList = BranchStaff::GetActiveBranchStaff($Clean['StaffCategory']);

$Clean['BranchStaffID'] = key($AllBranchStaffList);

$Clean['LeaveDateFrom'] = '';
$Clean['LeaveDateTo'] = '';

$Clean['AllRequestingLeaveDates'] = array();
$Clean['AcceptedLeaveDates'] = array();
$Clean['LeaveCategory'] = array();
$Clean['AppliedLeaveType'] = array();

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
		if ($LoggedUser->HasPermissionForTask(TASK_EDIT_EMPLOYEE_LEAVE) !== true)
		{
			header('location:/admin/unauthorized_login_admin.php');
			exit;
		}

		if (isset($_POST['hdnStaffCategory']))
		{
			$Clean['StaffCategory'] = strip_tags(trim($_POST['hdnStaffCategory']));
		}

		if (isset($_POST['hdnBranchStaffID']))
		{
			$Clean['BranchStaffID'] = (int) $_POST['hdnBranchStaffID'];
		}

		if ($Clean['BranchStaffID'] <= 0)
		{
			header('location:../error.php');
			exit;	
		}

		if (isset($_POST['hdnLeaveDateFrom']))
		{
			$Clean['LeaveDateFrom'] = strip_tags(trim($_POST['hdnLeaveDateFrom']));
		}

		if (isset($_POST['hdnLeaveDateTo']))
		{
			$Clean['LeaveDateTo'] = strip_tags(trim($_POST['hdnLeaveDateTo']));
		}

		if (isset($_POST['chkAcceptedLeave']) && is_array($_POST['chkAcceptedLeave']))
		{
			$Clean['AcceptedLeaveDates'] = $_POST['chkAcceptedLeave'];
		}

		if (isset($_POST['rdoLeaveCategory']) && is_array($_POST['rdoLeaveCategory']))
		{
			$Clean['LeaveCategory'] = $_POST['rdoLeaveCategory'];
		}

		if (isset($_POST['rdoLeaveType']) && is_array($_POST['rdoLeaveType']))
		{
			$Clean['AppliedLeaveType'] = $_POST['rdoLeaveType'];
		}

		$NewRecordValidator = new Validator();

		$NewRecordValidator->ValidateInSelect($Clean['StaffCategory'], $StaffCategoryList, 'Unknown Error, Please try again.');

		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			// Setting Default value
			foreach ($Clean['AllRequestingLeaveDates'] as $RequestingLeaveDate) 
			{
				if(!array_key_exists($RequestingLeaveDate, $Clean['AcceptedLeaveDates']))
				{
    				$Clean['LeaveCategory'][date('d/m/Y', strtotime($RequestingLeaveDate))] = 'WithoutPay';

    				$CounterRemainingLeave = 0;

					foreach ($EmployeeLeaveTypeList as $EmployeeLeaveTypeDetails) 
		    		{	
		    			if ($EmployeeLeaveTypeDetails['TotalRemainingLeave'] > 0)
		    			{	
		    				$CounterRemainingLeave = 1;
		    				$Clean['LeaveCategory'][date('d/m/Y', strtotime($LeaveDates))] = $EmployeeLeaveTypeDetails['LeavePayType'];
		    				$Clean['AppliedLeaveType'][date('d/m/Y', strtotime($LeaveDates))] = $EmployeeLeaveTypeDetails['LeaveTypeID'];
		    				break;
		    			}
		    		}

		    		// if All leave type consumed then checked leave type whose LeavePayType is WithoutPay
		    		if ($CounterRemainingLeave == 0) 
		    		{
		    			foreach ($EmployeeLeaveTypeList as $EmployeeLeaveTypeDetails) 
			    		{	
			    			if ($EmployeeLeaveTypeDetails['LeavePayType'] == 'WithoutPay')
			    			{	
			    				$Clean['LeaveCategory'][date('d/m/Y', strtotime($LeaveDates))] = $EmployeeLeaveTypeDetails['LeavePayType'];
			    				$Clean['AppliedLeaveType'][date('d/m/Y', strtotime($LeaveDates))] = $EmployeeLeaveTypeDetails['LeaveTypeID'];
			    				break;
			    			}
			    		}
		    		}
				}
			}

			$BranchStaffDetails = BranchStaff::GetBranchStaffDetailsByBranchStaffID($Clean['BranchStaffID']);
			break;
		}

		$AllBranchStaffList = BranchStaff::GetActiveBranchStaff($Clean['StaffCategory']);
		$EmployeeLeaveSummaryList = EmployeeLeave::GetEmployeeLeaveSummary($Clean['BranchStaffID']);
		$EmployeeLeaveHistoryList = EmployeeLeave::GetEmployeeLeaveHistory($Clean['BranchStaffID']);

		$NewRecordValidator->ValidateDate($Clean['LeaveDateFrom'], 'Please enter a Leave Date From.');
		$NewRecordValidator->ValidateDate($Clean['LeaveDateTo'], 'Please enter a Leave Date To.');

		$EmployeeLeaveDetails = array();
		$AllRequestingLeaveDates = array();
		
		$EmployeeLeaveTypeList = EmployeeLeave::GetEmployeeLeaveTypeDetails($Clean['BranchStaffID']);
		$Clean['AllRequestingLeaveDates'] = GetRangeDates(DateProcessing::ToggleDateDayAndMonth($Clean['LeaveDateFrom']), DateProcessing::ToggleDateDayAndMonth($Clean['LeaveDateTo']));
		$Clean['LeaveDateFrom'] = date('Y-m-d',strtotime(DateProcessing::ToggleDateDayAndMonth($Clean['LeaveDateFrom'])));
		$Clean['LeaveDateTo'] = date('Y-m-d',strtotime(DateProcessing::ToggleDateDayAndMonth($Clean['LeaveDateTo'])));

		foreach ($Clean['AllRequestingLeaveDates'] as $RequestingLeaveDate) 
		{
			$AllRequestingLeaveDates[date('d/m/Y', strtotime($RequestingLeaveDate))] = 1;
		}

		$LeaveTypeList = array();
		$LeaveTypeList =  LeaveType::GetAllLeaveTypes($Clean['StaffCategory']);

		foreach ($Clean['AppliedLeaveType'] as $LeaveDate => $LeaveTypeID) 
		{
			$NewRecordValidator->ValidateInSelect($LeaveTypeID, $LeaveTypeList, 'Unknown error, please try again.');	
			$NewRecordValidator->ValidateInSelect($LeaveDate, $AllRequestingLeaveDates, 'Unknown error, please try again.');
		}

		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			// Setting Default value
			foreach ($Clean['AllRequestingLeaveDates'] as $RequestingLeaveDate) 
			{
				if(!array_key_exists($RequestingLeaveDate, $Clean['AcceptedLeaveDates']))
				{
    				$Clean['LeaveCategory'][date('d/m/Y', strtotime($RequestingLeaveDate))] = 'WithoutPay';

    				$CounterRemainingLeave = 0;

					foreach ($EmployeeLeaveTypeList as $EmployeeLeaveTypeDetails) 
		    		{	
		    			if ($EmployeeLeaveTypeDetails['TotalRemainingLeave'] > 0)
		    			{	
		    				$CounterRemainingLeave = 1;
		    				$Clean['LeaveCategory'][date('d/m/Y', strtotime($LeaveDates))] = $EmployeeLeaveTypeDetails['LeavePayType'];
		    				$Clean['AppliedLeaveType'][date('d/m/Y', strtotime($LeaveDates))] = $EmployeeLeaveTypeDetails['LeaveTypeID'];
		    				break;
		    			}
		    		}

		    		// if All leave type consumed then checked leave type whose LeavePayType is WithoutPay
		    		if ($CounterRemainingLeave == 0) 
		    		{
		    			foreach ($EmployeeLeaveTypeList as $EmployeeLeaveTypeDetails) 
			    		{	
			    			if ($EmployeeLeaveTypeDetails['LeavePayType'] == 'WithoutPay')
			    			{	
			    				$Clean['LeaveCategory'][date('d/m/Y', strtotime($LeaveDates))] = $EmployeeLeaveTypeDetails['LeavePayType'];
			    				$Clean['AppliedLeaveType'][date('d/m/Y', strtotime($LeaveDates))] = $EmployeeLeaveTypeDetails['LeaveTypeID'];
			    				break;
			    			}
			    		}
		    		}
				}
			}

			$BranchStaffDetails = BranchStaff::GetBranchStaffDetailsByBranchStaffID($Clean['BranchStaffID']);
			break;
		}

		$LeaveDateToBeSaved = '';

		foreach ($AllRequestingLeaveDates as $RequestingLeaveDate => $Value) 
		{
			foreach ($Clean['AppliedLeaveType'] as $LeaveDate => $LeaveTypeID)
			{
				$LeaveDateToBeSaved = date('Y-m-d', strtotime(DateProcessing::ToggleDateDayAndMonth($LeaveDate)));

				if ($RequestingLeaveDate == $LeaveDate) 
				{	
					$EmployeeLeaveDetails[$LeaveDateToBeSaved]['WithoutPay']	= 0;

					if ($Clean['LeaveCategory'][$LeaveDate] == 'WithoutPay')
					{
						$EmployeeLeaveDetails[$LeaveDateToBeSaved]['WithoutPay']	= 1;	
					}
						
					$EmployeeLeaveDetails[$LeaveDateToBeSaved]['IsApproved']	= 1;		

					foreach ($EmployeeLeaveTypeList as $LeaveTypeForStaffID => $LeaveTypeForStaffDetails) 
					{
						if ($LeaveTypeID == $LeaveTypeForStaffDetails['LeaveTypeID']) 
						{
							$EmployeeLeaveDetails[$LeaveDateToBeSaved]['LeaveTypeForStaffID'] = $LeaveTypeForStaffID;
						}
					}
				}
			}			
		}
				
		$EmployeeLeaveToEdit->SetBranchStaffID($Clean['BranchStaffID']);
		$EmployeeLeaveToEdit->SetEmployeeLeaveDetails($EmployeeLeaveDetails);
		
		$EmployeeLeaveToEdit->SetCreateUserID($LoggedUser->GetUserID());

		if ($EmployeeLeaveToEdit->CheckDependencies())
		{
			$RecordValidator->AttachTextError('This employee leave cannot be deleted. leave already taken by employee.');
			$HasErrors = true;
			break;
		}

		if (!$EmployeeLeaveToEdit->Save())
		{
			$NewRecordValidator->AttachTextError(ProcessErrors($EmployeeLeaveToEdit->GetLastErrorCode()));
			$HasErrors = true;

			break;
		}

		header('location:employee_leaves_list.php?Mode=UD&Process=7&StaffCategory=' . $Clean['StaffCategory']);
		exit;
		break;

	case 2:
		$EmployeeLeaveDetails = array();
		$Clean['BranchStaffID'] = $EmployeeLeaveToEdit->GetBranchStaffID();

		$EmployeeLeaveToEdit->FillEmployeeLeaveDetails();
		$EmployeeLeaveDetails = $EmployeeLeaveToEdit->GetEmployeeLeaveDetails();

		$BranchStaffDetails = BranchStaff::GetBranchStaffDetailsByBranchStaffID($Clean['BranchStaffID']);
		$Clean['StaffCategory'] = $BranchStaffDetails[$Clean['BranchStaffID']]['StaffCategory'];

		
		$CounterForLeaveDate = 1;
		$LeaveDate = '';
		foreach ($EmployeeLeaveDetails as $EmployeeLeaveDetailsValue) 
		{
			// Getting from and to date
			$LeaveDate = $EmployeeLeaveDetailsValue['LeaveDate'];

			if ($CounterForLeaveDate == 1) 
			{
				$Clean['LeaveDateFrom'] = $LeaveDate;
			}

			$CounterForLeaveDate++;

			$Clean['AcceptedLeaveDates'][date('d/m/Y', strtotime($EmployeeLeaveDetailsValue['LeaveDate']))] = 1;
			$Clean['LeaveCategory'][date('d/m/Y', strtotime($EmployeeLeaveDetailsValue['LeaveDate']))] = ($EmployeeLeaveDetailsValue['IsWithoutPay'] == 1) ? 'WithoutPay' : 'WithPay';
			$Clean['AppliedLeaveType'][date('d/m/Y', strtotime($EmployeeLeaveDetailsValue['LeaveDate']))] = $EmployeeLeaveDetailsValue['LeaveTypeID'];
		}

		$Clean['LeaveDateTo'] = $LeaveDate;
		$Clean['AllRequestingLeaveDates'] = GetRangeDates($Clean['LeaveDateFrom'], $Clean['LeaveDateTo']);

		$EmployeeLeaveTypeList = EmployeeLeave::GetEmployeeLeaveTypeDetails($Clean['BranchStaffID']);
		$EmployeeLeaveSummaryList = EmployeeLeave::GetEmployeeLeaveSummary($Clean['BranchStaffID']);
		$EmployeeLeaveHistoryList = EmployeeLeave::GetEmployeeLeaveHistory($Clean['BranchStaffID']);
		break;

	case 11:
		if (isset($_POST['hdnBranchStaffID']))
		{
			$Clean['BranchStaffID'] = (int) $_POST['hdnBranchStaffID'];
		}

		if ($Clean['BranchStaffID'] <= 0)
		{
			header('location:../error.php');
			exit;	
		}

		if (isset($_POST['hdnStaffCategory']))
		{
			$Clean['StaffCategory'] = strip_tags(trim($_POST['hdnStaffCategory']));
		}

		if (isset($_POST['txtLeaveDateFrom']))
		{
			$Clean['LeaveDateFrom'] = strip_tags(trim($_POST['txtLeaveDateFrom']));
		}

		if (isset($_POST['txtLeaveDateTo']))
		{
			$Clean['LeaveDateTo'] = strip_tags(trim($_POST['txtLeaveDateTo']));
		}

		$BranchStaffDetails = BranchStaff::GetBranchStaffDetailsByBranchStaffID($Clean['BranchStaffID']);
		$EmployeeLeaveSummaryList = EmployeeLeave::GetEmployeeLeaveSummary($Clean['BranchStaffID']);
		$EmployeeLeaveHistoryList = EmployeeLeave::GetEmployeeLeaveHistory($Clean['BranchStaffID']);

		$NewRecordValidator = new Validator();
		$NewRecordValidator->ValidateInSelect($Clean['StaffCategory'], $StaffCategoryList, 'Unknown Error, Please try again.');

		if ($NewRecordValidator->HasNotifications())
        {
            $HasErrors = true;
            break;
        }

    	$AllBranchStaffList = BranchStaff::GetActiveBranchStaff($Clean['StaffCategory']);

		$NewRecordValidator->ValidateDate($Clean['LeaveDateFrom'], 'Please enter a leave date from.');
		$NewRecordValidator->ValidateDate($Clean['LeaveDateTo'], 'Please enter a leave date from.');

        if (strtotime(date('Y-m-d',strtotime(DateProcessing::ToggleDateDayAndMonth($Clean['LeaveDateTo'])))) < date('Y-m-d',strtotime(DateProcessing::ToggleDateDayAndMonth($Clean['LeaveDateFrom']))))
        {
        	$NewRecordValidator->AttachTextError('To date not be greater than from.');	
        }

        $Clean['AllRequestingLeaveDates'] = GetRangeDates(DateProcessing::ToggleDateDayAndMonth($Clean['LeaveDateFrom']), DateProcessing::ToggleDateDayAndMonth($Clean['LeaveDateTo']));

		$Clean['LeaveDateFrom'] = date('Y-m-d', strtotime(DateProcessing::ToggleDateDayAndMonth($Clean['LeaveDateFrom'])));
		$Clean['LeaveDateTo'] = date('Y-m-d', strtotime(DateProcessing::ToggleDateDayAndMonth($Clean['LeaveDateTo'])));

		if ($NewRecordValidator->HasNotifications())
        {
            $HasErrors = true;
            break;
        }

        $EmployeeLeaveTypeList = EmployeeLeave::GetEmployeeLeaveTypeDetails($Clean['BranchStaffID']);
        
        // Setting Default value
        foreach ($Clean['AllRequestingLeaveDates'] as $LeaveDates) 
        {
    		$Clean['AcceptedLeaveDates'][date('d/m/Y', strtotime($LeaveDates))] = 1;

    		$CounterRemainingLeave = 0;

    		foreach ($EmployeeLeaveTypeList as $EmployeeLeaveTypeDetails) 
    		{	
    			if ($EmployeeLeaveTypeDetails['TotalRemainingLeave'] > 0)
    			{	
    				$CounterRemainingLeave = 1;
    				$Clean['LeaveCategory'][date('d/m/Y', strtotime($LeaveDates))] = $EmployeeLeaveTypeDetails['LeavePayType'];
    				$Clean['AppliedLeaveType'][date('d/m/Y', strtotime($LeaveDates))] = $EmployeeLeaveTypeDetails['LeaveTypeID'];
    				break;
    			}
    		}

    		// if All leave type consumed then checked leave type whose LeavePayType is WithoutPay
    		if ($CounterRemainingLeave == 0) 
    		{
    			foreach ($EmployeeLeaveTypeList as $EmployeeLeaveTypeDetails) 
	    		{	
	    			if ($EmployeeLeaveTypeDetails['LeavePayType'] == 'WithoutPay')
	    			{	
	    				$Clean['LeaveCategory'][date('d/m/Y', strtotime($LeaveDates))] = $EmployeeLeaveTypeDetails['LeavePayType'];
	    				$Clean['AppliedLeaveType'][date('d/m/Y', strtotime($LeaveDates))] = $EmployeeLeaveTypeDetails['LeaveTypeID'];
	    				break;
	    			}
	    		}
    		}
    	}	

		break;
}
require_once('../html_header.php');
?>
<title>Edit Employee Leave</title>
<link href="../vendor/jquery-ui/jquery-ui.min.css" rel="stylesheet">
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
                    <h1 class="page-header">Edit Employee Leave</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
        	<div class="panel panel-default">
                <div class="panel-heading">
                    Enter Employee Details
                </div>
                <div class="panel-body">
<?php
					if ($HasErrors == true)
					{
						echo $NewRecordValidator->DisplayErrors();
					}
?>                    
					<form class="form-horizontal" name="SearchEmployeeLeave" action="edit_employee_leaves.php" method="post">
	                    <div class="form-group">
	                        <label for="StaffCategory" class="col-lg-2 control-label">Staff Category</label>
	                        <div class="col-lg-4">
	                        	<input class="form-control" type="text" value="<?php echo ($Clean['StaffCategory'] == 'Teaching') ? 'Teaching Staff' : 'Non Teaching Stafd'?>" disabled="disabled">
	                        </div>
	                        <label for="BranchStaff" class="col-lg-2 control-label">Branch Staff</label>
	                        <div class="col-lg-4">
	                        	<input class="form-control" type="text" name="txtBranchStaffName" value="<?php echo $BranchStaffDetails[$Clean['BranchStaffID']]['FirstName'] . " " . $BranchStaffDetails[$Clean['BranchStaffID']]['LastName']; ?>" disabled="disabled">	
	                        </div>
	                    </div> 
	                </form>
                </div>
            </div>
<?php
		if($Clean['Process'] == 1 || $Clean['Process'] == 2 || $Clean['Process'] == 7 || $Clean['Process'] == 11)
		{
?>
			<div class="panel panel-default">
                <div class="panel-heading">
                    Employee Remaining Leaves Details
                </div>
                <div class="panel-body">
                	<form class="form-horizontal" name="EditEmployeeLeaves" action="edit_employee_leaves.php" method="post">
						<div class="form-group">
                            <label for="BranchStaffEmail" class="col-lg-2 control-label">Email</label>
                            <div class="col-lg-4">
                            	<input class="form-control" type="text" name="BranchStaffEmail" value="<?php echo $BranchStaffDetails[$Clean['BranchStaffID']]['Email']; ?>" disabled="disabled">	
                            </div>
                            <label for="DOJ" class="col-lg-2 control-label">Date OF Joining</label>
                            <div class="col-lg-4">
                            	<input class="form-control" type="text" name="txtDOJ" value="<?php echo date('d/m/Y', strtotime($BranchStaffDetails[$Clean['BranchStaffID']]['JoiningDate'])); ?>" disabled="disabled">	
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="BranchStaffMobile" class="col-lg-2 control-label">Mobile</label>
                            <div class="col-lg-4">
                            	<input class="form-control" type="text" name="txtBranchStaffMobile" value="<?php echo $BranchStaffDetails[$Clean['BranchStaffID']]['MobileNumber1']; ?>" disabled="disabled">	
                            </div>
                        </div>
                        <div class="row" id="RecordTable">
                            <div class="col-lg-12">
                                <table width="100%" class="table table-striped table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th>S. No</th>
                                            <th>Leave Type</th>
                                            <th>Leave Mode</th>
                                            <th>Leave Pay Type</th>
                                            <th>Total Allowed Leave</th>
                                            <th>Total Remaining leave</th>
                                        </tr>
                                    </thead>
                                    <tbody>
<?php
										if (is_array($EmployeeLeaveTypeList) && count($EmployeeLeaveTypeList) > 0) 
										{
											$CounterRemainingLeave = 1;
?>
											<tr>
<?php
											foreach ($EmployeeLeaveTypeList as $EmployeeLeaveTypeDetails) 
											{	
?>
											<tr>			
												<td><?php echo $CounterRemainingLeave++;?></td>
												<td><?php echo $EmployeeLeaveTypeDetails['LeaveType'];?></td>
												<td><?php echo $EmployeeLeaveTypeDetails['LeaveMode'];?></td>
												<td><?php echo $EmployeeLeaveTypeDetails['LeavePayType'];?></td>
												<td><?php echo $EmployeeLeaveTypeDetails['NoOfLeaves'];?></td>
												<td><?php echo $EmployeeLeaveTypeDetails['TotalRemainingLeave'];?></td>
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
                    </form>	                        
                </div>
            </div>
            <div class="panel panel-default">
                <div class="panel-heading">
                    Employee Leave History
                </div>
            	<div class="panel-body">
            		<form  method="post">
                        <div class="row" id="RecordTable">
							<div class="col-lg-4">
								<table width="100%" class="table table-striped table-bordered table-hover">
                                    <tbody>
                                    	<tr>
                                            <th colspan="2" style="text-align: center;">Leave Summary</th>
                                        </tr>
                                			<tr>                                                        
	                                            <th>Requested Leaves</th>
	                                            <td><?php echo $EmployeeLeaveSummaryList['RequestedLeaves'] ?></td>
                                        	</tr>
                                        	<tr>                                                        
	                                            <th>Approved Leaves</th>
	                                            <td><?php echo $EmployeeLeaveSummaryList['ApprovedLeaves'] ?></td>
                                        	</tr>
                                        	<tr>                                                        
	                                            <th>Taken Leaves</th>
	                                            <td><?php echo $EmployeeLeaveSummaryList['TakenLeaves'] ?></td>
                                        	</tr>
                                        	<tr>                                                        
	                                            <th>Cancel Leaves</th>
	                                            <td><?php echo $EmployeeLeaveSummaryList['CancelLeaves'] ?></td>
                                        	</tr>
                                   </tbody>
                                </table>
							</div>
							<div class="col-lg-8">
								<table width="100%" class="table table-striped table-bordered table-hover">
                                    <thead>
                                    	<tr>	
                                            <th colspan="5" style="text-align: center;">Employee Leave History</th>
                                        </tr>
                                        <tr>
                                            <th>S. No</th>	
                                            <th>Date</th>
                                            <th>Applied Leave Type</th>
                                            <th>Action</th>
                                            <th>Is Taken</th>
                                        </tr>
                                    </thead>
                                    <tbody>
<?php
										if (count($EmployeeLeaveHistoryList) > 0) 
										{
											$CounterEmployeeLeaveHistory = 1;
											foreach ($EmployeeLeaveHistoryList as $EmployeeLeaveDayID => $EmployeeLeaveHistoryDetails) 
											{
?>
											<tr>
	                                            <td><?php echo $CounterEmployeeLeaveHistory++; ?></td>
	                                            <td><?php echo date('d/m/Y', strtotime($EmployeeLeaveHistoryDetails['LeaveDate'])); ?></td>
	                                            <td><?php echo $EmployeeLeaveHistoryDetails['AppliedLeaveType']; ?></td>
	                                            <td><?php echo ($EmployeeLeaveHistoryDetails['IsApproved'] == 1) ? 'Approved' : 'Cancel'; ?></td>
	                                            <td><?php echo ($EmployeeLeaveHistoryDetails['IsTaken'] == 1) ? 'Yes' : 'No'; ?></td>
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
                    </form>
				</div>
    		</div>
    		<div class="panel panel-default">
                    <div class="panel-heading">
                        Employee Leave Request
                    </div>
                	<div class="panel-body">
                		<form  class="form-horizontal" name="EditEmployeeLeaveRequest" action="edit_employee_leaves.php" method="post">
	                        <div class="form-group">
	                            <label for="LeaveDateFrom" class="col-lg-2 control-label">Leave Date From</label>
	                            <div class="col-lg-4">
	                            	<input class="form-control dtepicker" type="text" name="txtLeaveDateFrom" value="<?php echo($Clean['LeaveDateFrom'] == '') ? ''  : date('d/m/Y',strtotime($Clean['LeaveDateFrom'])); ?>"/>	
	                            </div>
	                            <label for="LeaveDateTo" class="col-lg-2 control-label">Leave Date To</label>
	                            <div class="col-lg-4">
	                            	<input class="form-control dtepicker" type="text" name="txtLeaveDateTo" value="<?php echo($Clean['LeaveDateTo'] == '') ? '' : date('d/m/Y', strtotime($Clean['LeaveDateTo'])); ?>"/>	
	                            </div>
	                        </div>
	                        <div class="form-group">
		                        <div class="col-sm-offset-2 col-lg-10">
		                        	<input type="hidden" name="hdnProcess" value="11"/>
		                        	<input type="hidden" name="hdnEmployeeLeaveID" value="<?php echo $Clean['EmployeeLeaveID']; ?>" />
		                        	<input type="hidden" name="hdnStaffCategory" value="<?php echo $Clean['StaffCategory']; ?>"/>
                        			<input type="hidden" name="hdnBranchStaffID" value="<?php echo $Clean['BranchStaffID']; ?>"/>
									<button type="submit" id="Check" class="btn btn-primary">Check</button>
		                        </div>
		                  	</div>
                        </form>
<?php
					if($Clean['Process'] == 1 || $Clean['Process'] == 2 || $Clean['Process'] == 11)
					{
?>
						<form  name="EditLeave" action="edit_employee_leaves.php" method="post">
	                        <div class="row" id="RecordTable">
	                            <div class="col-lg-12">
	                                <table width="100%" class="table table-striped table-bordered table-hover" id="LeaveRequest">
	                                    <thead>
	                                        <tr>
	                                            <th>Accept</th>
	                                            <th>Leave Date</th>
	                                            <th>Leave Considered AS</th>
	                                        </tr>
	                                    </thead>
	                                    <tbody>
<?php
										if (count($EmployeeLeaveTypeList) > 0 && count($Clean['AllRequestingLeaveDates']) > 0)
										{	
											$RowCounter = 1;
											foreach ($Clean['AllRequestingLeaveDates'] as $Key => $LeaveDates) 
											{
?>
											<tr>
	                                            <td>
	                                            	<input class="ActiveRow" type="checkbox" name="chkAcceptedLeave[<?php echo date('d/m/Y', strtotime($LeaveDates));?>]" value="1" <?php echo(array_key_exists(date('d/m/Y', strtotime($LeaveDates)), $Clean['AcceptedLeaveDates']) ? 'checked="checked"' : '');?> >
                                            	</td>
	                                            <td><?php echo date('d/m/Y', strtotime($LeaveDates));?></td>
	                                            <td style="padding: 0px; margin: 0px;">
	                                            	<table style="padding: 0px; margin: 0px;" class="table table-striped table-bordered table-hover">
	                                            		<tr>
	                                            			<td>
																<label for="WithOutPay<?php echo $RowCounter;?>" class="control-label" style="font-weight: normal;">
                                            						<input type="radio" class="LeaveType" id="WithOutPay<?php echo $RowCounter;?>" name="rdoLeaveCategory[<?php echo date('d/m/Y', strtotime($LeaveDates));?>]" value="WithoutPay" <?php echo($Clean['LeaveCategory'][date('d/m/Y', strtotime($LeaveDates))]  == 'WithoutPay') ? 'checked="checked"' : '';?> <?php echo(array_key_exists(date('d/m/Y', strtotime($LeaveDates)), $Clean['AcceptedLeaveDates']) ? '' : 'disabled="disabled"');?> >&nbsp;WithOut Pay
                                            					</label>
                                            				</td>
	                                            			<td>
	                                            				<label for="WithPay<?php echo $RowCounter;?>" class="control-label" style="font-weight: normal;">
                                        							<input type="radio" class="LeaveType" id="WithPay<?php echo $RowCounter;?>" name="rdoLeaveCategory[<?php echo date('d/m/Y', strtotime($LeaveDates));?>]" value="WithPay" <?php echo($Clean['LeaveCategory'][date('d/m/Y', strtotime($LeaveDates))]  == 'WithPay') ? 'checked="checked"' : '';?> <?php echo(array_key_exists(date('d/m/Y', strtotime($LeaveDates)), $Clean['AcceptedLeaveDates']) ? '' : 'disabled="disabled"');?> >&nbsp;With Pay
                                        						</label>
                                        					</td>
	                                            		</tr>
	                                            		<tr>
	                                            			<td>
<?php
															if (is_array($EmployeeLeaveTypeList) && count($EmployeeLeaveTypeList) > 0)
															{
																foreach ($EmployeeLeaveTypeList as $LeaveTypeForStaffID => $EmployeeLeaveTypeDetails) 
																{
																	if ($EmployeeLeaveTypeDetails['LeavePayType'] == 'WithoutPay')
																	{
?>
																	<label for="WithPayLeaveType<?php echo $RowCounter . $EmployeeLeaveTypeDetails['LeaveTypeID'];?>" style="font-weight: normal;">
	                                        							<input type="radio" class="WithoutPayLeaveType IsLeaveTypeRemaining <?php echo $EmployeeLeaveTypeDetails['LeaveType']; ?>" total-leave="<?php echo $EmployeeLeaveTypeDetails['TotalRemainingLeave']; ?>" leave-type="<?php echo $EmployeeLeaveTypeDetails['LeaveType']; ?>" id="WithPayLeaveType<?php echo $RowCounter . $EmployeeLeaveTypeDetails['LeaveTypeID'];?>" name="rdoLeaveType[<?php echo date('d/m/Y', strtotime($LeaveDates));?>]" value="<?php echo $EmployeeLeaveTypeDetails['LeaveTypeID'];?>" <?php echo($Clean['AppliedLeaveType'][date('d/m/Y', strtotime($LeaveDates))] == $EmployeeLeaveTypeDetails['LeaveTypeID']) ? 'checked="checked"' : '';?> <?php echo($Clean['LeaveCategory'][date('d/m/Y', strtotime($LeaveDates))]  == 'WithoutPay') ? '' : 'disabled="disabled"';?> <?php echo(array_key_exists(date('d/m/Y', strtotime($LeaveDates)), $Clean['AcceptedLeaveDates']) ? '' : 'disabled="disabled"');?> />
	                                        								&nbsp;<?php echo $EmployeeLeaveTypeDetails['LeaveType'];?>
                                        							</label>
<?php
																	}
																}
															}
?>
	                                            			</td>
	                                            			<td>
<?php
															if (is_array($EmployeeLeaveTypeList) && count($EmployeeLeaveTypeList) > 0)
															{
																foreach ($EmployeeLeaveTypeList as $LeaveTypeForStaffID => $EmployeeLeaveTypeDetails) 
																{
																	if ($EmployeeLeaveTypeDetails['LeavePayType'] == 'WithPay')
																	{
?>
																	<label for="WithOutPayLeaveType<?php echo $RowCounter . $EmployeeLeaveTypeDetails['LeaveTypeID'];?>" style="font-weight: normal;">
	                                        							<input type="radio" class="WithPayLeaveType IsLeaveTypeRemaining <?php echo $EmployeeLeaveTypeDetails['LeaveType']; ?>" total-leave="<?php echo $EmployeeLeaveTypeDetails['TotalRemainingLeave']; ?>" leave-type="<?php echo $EmployeeLeaveTypeDetails['LeaveType']; ?>" id="WithOutPayLeaveType<?php echo $RowCounter . $EmployeeLeaveTypeDetails['LeaveTypeID'];?>" name="rdoLeaveType[<?php echo date('d/m/Y', strtotime($LeaveDates));?>]" value="<?php echo $EmployeeLeaveTypeDetails['LeaveTypeID'];?>" <?php echo($Clean['AppliedLeaveType'][date('d/m/Y', strtotime($LeaveDates))] == $EmployeeLeaveTypeDetails['LeaveTypeID']) ? 'checked="checked"' : '';?> <?php echo($Clean['LeaveCategory'][date('d/m/Y', strtotime($LeaveDates))]  == 'WithPay') ? '' : 'disabled="disabled"';?> <?php echo(array_key_exists(date('d/m/Y', strtotime($LeaveDates)), $Clean['AcceptedLeaveDates']) ? '' : 'disabled="disabled"');?> />
	                                        								&nbsp;<?php echo $EmployeeLeaveTypeDetails['LeaveType'];?>
	                                    							</label>
<?php
																	}
																}
															}
?>
	                                            			</td>
	                                            		</tr>
	                                            	</table>
	                                            </td>
                                            </tr>										
<?php
											$RowCounter++;
											}

										}
										else
										{
?>
											<tr>
<?php
												if (count($EmployeeLeaveTypeList) == 0)
												{
													echo '<script>alert("please assign leave type to current branch staff")</script>';
												}
?>
                                                <td colspan="3">No Records</td>
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
		                        	<input type="hidden" name="hdnEmployeeLeaveID" value="<?php echo $Clean['EmployeeLeaveID']; ?>" />
		                        	<input type="hidden" name="hdnLeaveDateFrom" value="<?php echo date('d/m/Y', strtotime($Clean['LeaveDateFrom'])); ?>"/>
		                        	<input type="hidden" name="hdnLeaveDateTo" value="<?php echo date('d/m/Y', strtotime($Clean['LeaveDateTo'])); ?>"/>
		                        	<input type="hidden" name="hdnStaffCategory" value="<?php echo $Clean['StaffCategory']; ?>"/>
		                        	<input type="hidden" name="hdnBranchStaffID" value="<?php echo $Clean['BranchStaffID']; ?>"/>
									<button type="submit" class="btn btn-primary">Save</button>
		                        </div>
		                  	</div>
	                    </form>
<?php
					}	
?>   			
            	</div>
        </div>
        <!-- /#page-wrapper -->
<?php
		}
?>            
    </div>
    <!-- /#wrapper -->
<?php
require_once('../footer.php');
if (isset($_GET['ViewOnly']))
{
    $ViewOnly = true;
}
?>
<script src="/admin/vendor/jquery-ui/jquery-ui.min.js"></script>
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

    $(".dtepicker").datepicker({
            changeMonth: true,
            changeYear: true,
            dateFormat: 'dd/mm/yy'
    });
 });

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

	$('.ActiveRow').click(function()
	{	
		var CurrentTR = $(this).closest('tr');
		if ($(this).is(":checked"))
		{
			CurrentTR.find('input[type = "radio"]').prop('disabled', false);
			CurrentTR.find('.WithoutPayLeaveType').prop('checked', true);
			CurrentTR.find('.WithPayLeaveType').prop('disabled', true);
			CurrentTR.find('.WithoutPayLeaveType').eq(0).prop('checked', true);
		}
		else
		{
			CurrentTR.closest("tr").find('input[type = "radio"]').prop('disabled', true);
		}
	});

	$('.LeaveType').click(function()
	{
		var CurrentTR = $(this).closest('tr').next('tr');

		if ($(this).val() == 'WithoutPay') 
		{
			CurrentTR.find('.WithPayLeaveType').prop('disabled', true);
			CurrentTR.find('.WithoutPayLeaveType').prop('disabled', false);
			CurrentTR.find('.WithoutPayLeaveType').eq(0).prop('checked', true);
		}
		else if ($(this).val() == 'WithPay')
		{
			CurrentTR.find('.WithoutPayLeaveType').prop('disabled', true);
			CurrentTR.find('.WithPayLeaveType').prop('disabled', false);
			CurrentTR.find('.WithPayLeaveType').eq(0).prop('checked', true);
		}
	});

	$('.IsLeaveTypeRemaining').click(function(){
		var RemainingLeaves = parseInt($(this).attr('total-leave'));
		var LeaveType = $(this).attr('leave-type');
		var CountCheckedBoxes = parseInt($('input.' + LeaveType + ':checked').length);

		if ((RemainingLeaves - CountCheckedBoxes) < 0) 
		{
			if(!confirm("Leave exceeded maximum number of allowed leave. Do you still want to continue"))
			{
				return false;
			}

			return true;
		}
	});
});
</script>
</body>
</html>
</script>
</body>
</html>
<?php
//if (php_sapi_name() !='cli') exit;

error_log('Mark Branch Staff Leve Cron Run Start: ' . date('d/m/Y h:i A'));
require_once('../classes/class.db_connect.php');

$Clean = array();

try
{
	$DBConnObject = new DBConnect();

	$DBConnObject->BeginTransaction();
	
	$RSAbsentBranchStaff = $DBConnObject->Prepare('SELECT asa.*, asad.* 
													FROM asa_staff_attendence asa
													INNER JOIN asa_staff_attendence_details asad ON asad.staffAttendenceID = asa.staffAttendenceID
													WHERE attendenceStatus = "Absent" AND asa.attendenceDate = CURDATE();');
    $RSAbsentBranchStaff->Execute();
	
	if ($RSAbsentBranchStaff->Result->num_rows <= 0)
    {
		echo 'success';
		exit;
    }
	
	while ($AbsentBranchStaffRow = $RSAbsentBranchStaff->FetchRow())
	{
		$BranchStaffID = $AbsentBranchStaffRow->branchStaffID;
		$StaffCategory = $AbsentBranchStaffRow->staffCategory;

		$RSCkeckEmployeeLeaveID = $DBConnObject->Prepare('SELECT employeeLeaveID FROM ahr_employee_leaves WHERE branchStaffID = :|1 LIMIT 1;');
		$RSCkeckEmployeeLeaveID->Execute($BranchStaffID);

		if ($RSCkeckEmployeeLeaveID->Result->num_rows > 0)
		{
			$EmployeeLeaveID = $RSCkeckEmployeeLeaveID->FetchRow()->employeeLeaveID;
		}
		else
		{
			$RSSaveStaffLeave = $DBConnObject->Prepare('INSERT INTO ahr_employee_leaves (branchStaffID, createDate) 
															VALUES (:|1, NOW());');
			$RSSaveStaffLeave->Execute($BranchStaffID);

			$EmployeeLeaveID = $RSSaveStaffLeave->LastID;
		}

		// Ckeck if leave is requested and approved in ahr_employee_leave_days

    	$RSCkeckEmployeeLeaveDayID = $DBConnObject->Prepare('SELECT employeeLeaveDayID, isTaken FROM ahr_employee_leave_days WHERE employeeLeaveID = :|1 AND leaveDate = CURDATE() LIMIT 1;');
		$RSCkeckEmployeeLeaveDayID->Execute($EmployeeLeaveID);

		if ($RSCkeckEmployeeLeaveDayID->Result->num_rows > 0)
		{
			if ($RSCkeckEmployeeLeaveDayID->FetchRow()->isTaken != 1)
			{
				$EmployeeLeaveDayID = $RSCkeckEmployeeLeaveDayID->FetchRow()->employeeLeaveDayID;

				$RSUpdate = $DBConnObject->Prepare('UPDATE ahr_employee_leave_days SET isTaken = 1 WHERE employeeLeaveDayID = :|1 LIMIT 1;');
				$RSUpdate->Execute($EmployeeLeaveDayID);
			}			
		}
		else
		{
			$RSSaveStaffLeaveDay = $DBConnObject->Prepare('INSERT INTO ahr_employee_leave_days (employeeLeaveID, leaveDate, isWithoutPay, isApproved, isTaken) 
																VALUES(:|1, CURDATE(), 1, 0, 1);');

			$RSSaveStaffLeaveDay->Execute($EmployeeLeaveID);

			$EmployeeLeaveDayID = $RSSaveStaffLeaveDay->LastID;

			// Check for 'Casual Leave' leave type in ahr_leave_types table 
			$RSCkeckCasualLeaveType = $DBConnObject->Prepare('SELECT leaveTypeID FROM ahr_leave_types WHERE leaveType = \'Casual Leave\' AND leavePayType = \'WithoutPay\' AND staffCategory = :|1 LIMIT 1;');
			$RSCkeckCasualLeaveType->Execute($StaffCategory);

			//if present use that id otherwise insert a new eave type named 'Casual Leave' and leavePayType = 'Without Pay'
			if ($RSCkeckCasualLeaveType->Result->num_rows > 0)
			{
				$LeaveTypeID = $RSCkeckCasualLeaveType->FetchRow()->leaveTypeID;
			}
			else
			{
				$RSSaveLeaveType = $DBConnObject->Prepare('INSERT INTO ahr_leave_types (staffCategory, leaveType, leavePayType) 
																		VALUES(:|1, \'Casual Leave\', \'WithoutPay\');');
				$RSSaveLeaveType->Execute($StaffCategory);

				$LeaveTypeID = $RSSaveLeaveType->LastID;
			}

			// Check for 'Casual Leave' assigned the branchStaffID in ahr_leave_types_for_staff table
			$RSCkeckCasualLeaveTypeAssignedToBranchStaff = $DBConnObject->Prepare('SELECT leaveTypeForStaffID FROM ahr_leave_types_for_staff WHERE leaveTypeID = :|1 AND branchStaffID = :|2 LIMIT 1;');
			$RSCkeckCasualLeaveTypeAssignedToBranchStaff->Execute($LeaveTypeID, $BranchStaffID);

			//if present, use that id otherwise 'Casual Leave' to the brnachStaffID
			if ($RSCkeckCasualLeaveTypeAssignedToBranchStaff->Result->num_rows > 0)
			{
				$LeaveTypeForStaffID = $RSCkeckCasualLeaveTypeAssignedToBranchStaff->FetchRow()->leaveTypeForStaffID;
			}
			else
			{
				$RSSaveCasualLeaveTypeAssignedToBranchStaff = $DBConnObject->Prepare('INSERT INTO ahr_leave_types_for_staff (leaveTypeID, branchStaffID) 
																		VALUES(:|1, :|2);');
				$RSSaveCasualLeaveTypeAssignedToBranchStaff->Execute($LeaveTypeID, $BranchStaffID);

				$LeaveTypeForStaffID = $RSSaveCasualLeaveTypeAssignedToBranchStaff->LastID;
			}

			// Now insert into ahr_employee_leaves_details table
			$RSSaveStaffLeaveDetails = $DBConnObject->Prepare('INSERT INTO ahr_employee_leaves_details (employeeLeaveDayID, leaveTypeForStaffID) 
																VALUES(:|1, :|2);');

			$RSSaveStaffLeaveDetails->Execute($EmployeeLeaveDayID, $LeaveTypeForStaffID);
		}
	}

	$DBConnObject->CommitTransaction();
	echo 'success';
	exit;
}
catch (ApplicationDBException $e)
{
	$DBConnObject->RollBackTransaction();
	echo 'error';
	exit;
}
catch (Exception $e)
{
	$DBConnObject->RollBackTransaction();
	echo 'error';
	exit;
}
?>


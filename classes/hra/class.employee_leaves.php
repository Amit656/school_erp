<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.db_connect.php');
error_reporting(E_ALL);

class EmployeeLeave
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	
	private $EmployeeLeaveID;
	private $BranchStaffID;

	private $CreateUserID;
	private $CreateDate;

	private $EmployeeLeaveDetails = array();
	
	// PUBLIC METHODS START HERE	//
	public function __construct($EmployeeLeaveID = 0)
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;
		
		if($EmployeeLeaveID != 0)
		{
			$this->EmployeeLeaveID = $EmployeeLeaveID;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetEmployeeLeaveByID();
		}
		else
		{
			//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
			$this->EmployeeLeaveID = 0;
			$this->BranchStaffID = '';

			$this->CreateUserID = 0;
			$this->CreateDate = '0000-00-00 00:00:00';

			$this->EmployeeLeaveDetails = array();
		}
	}
	
	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetEmployeeLeaveID()
	{
		return $this->EmployeeLeaveID;
	}
	
	public function GetBranchStaffID()
	{
		return $this->BranchStaffID;
	}
	public function SetBranchStaffID($BranchStaffID)
	{
		$this->BranchStaffID = $BranchStaffID;
	}	

	public function GetCreateUserID()
	{
		return $this->CreateUserID;
	}
	public function SetCreateUserID($CreateUserID)
	{
		$this->CreateUserID = $CreateUserID;
	}
	
	public function GetCreateDate()
	{
		return $this->CreateDate;
	}

	public function GetEmployeeLeaveDetails()
	{
		return $this->EmployeeLeaveDetails;
	}
	public function SetEmployeeLeaveDetails($EmployeeLeaveDetails)
	{
		$this->EmployeeLeaveDetails = $EmployeeLeaveDetails;
	}
	
	public function GetLastErrorCode()
	{
		return $this->LastErrorCode;
	}
	
	//  END OF GETTER AND SETTER FUNCTIONS 	//
	
	public function Save()
	{
		try
		{
			$this->DBObject->BeginTransaction();
			if ($this->SaveDetails())
			{
				$this->DBObject->CommitTransaction();
				return true;
			}
			$this->DBObject->RollBackTransaction();
			return false;
		}
		catch (ApplicationDBException $e)
		{
			$this->DBObject->RollBackTransaction();
			$this->LastErrorCode = $e->getCode();
			return false;
		}
		catch (ApplicationARException $e)
		{
			$this->DBObject->RollBackTransaction();
			$this->LastErrorCode = $e->getCode();
			return false;
		}
		catch (Exception $e)
		{
			$this->DBObject->RollBackTransaction();
			$this->LastErrorCode = APP_ERROR_UNDEFINED_ERROR;
			return false;
		}
	}

	public function Remove()
    {
    	try
		{
			$this->DBObject->BeginTransaction();
			if ($this->RemoveEmployeeLeave())
			{
				$this->DBObject->CommitTransaction();
				return true;
			}
			$this->DBObject->RollBackTransaction();
			return false;
		}
		catch (ApplicationDBException $e)
		{
			$this->DBObject->RollBackTransaction();
			$this->LastErrorCode = $e->getCode();
			return false;
		}
		catch (ApplicationARException $e)
		{
			$this->DBObject->RollBackTransaction();
			$this->LastErrorCode = $e->getCode();
			return false;
		}
		catch (Exception $e)
		{
			$this->DBObject->RollBackTransaction();
			$this->LastErrorCode = APP_ERROR_UNDEFINED_ERROR;
			return false;
		}
    }

    public function CheckDependencies()
	{
		try
		{
			$RSTotal = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM ahr_employee_leaves ael 
												 INNER JOIN ahr_employee_leave_days aeld ON ael.employeeLeaveID = aeld.employeeLeaveID
												 WHERE ael.employeeLeaveID = :|1 AND isApproved = 0 OR isTaken = 1;');
			$RSTotal->Execute($this->EmployeeLeaveID);
	
			if ($RSTotal->FetchRow()->totalRecords > 0) 
			{
				return true;
			}
			
			return false;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException: EmployeeLeave::CheckDependencies(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception: EmployeeLeave::CheckDependencies(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
	}

	public function FillEmployeeLeaveDetails()
	{
		try
		{
			$RSSearch = $this->DBObject->Prepare('SELECT ael.branchStaffID, ael.employeeLeaveID, aeld.*, altfs.leaveTypeForStaffID, altfs.leaveTypeID 
												 FROM ahr_employee_leaves ael
												 INNER JOIN ahr_employee_leave_days aeld ON ael.employeeLeaveID = aeld.employeeLeaveID
												 INNER JOIN ahr_employee_leaves_details aeldls ON aeld.employeeLeaveDayID = aeldls.employeeLeaveDayID
												 INNER JOIN ahr_leave_types_for_staff altfs ON aeldls.leaveTypeForStaffID = altfs.leaveTypeForStaffID
												 WHERE ael.employeeLeaveID = :|1 ORDER BY aeld.leaveDate;');
			$RSSearch->Execute($this->EmployeeLeaveID);
			$Counter = 1;
			while($SearchRow = $RSSearch->FetchRow())
            {
            	$this->EmployeeLeaveDetails[$Counter]['BranchStaffID'] = $SearchRow->branchStaffID;
            	$this->EmployeeLeaveDetails[$Counter]['LeaveDate'] = $SearchRow->leaveDate;
            	$this->EmployeeLeaveDetails[$Counter]['IsWithoutPay'] = $SearchRow->isWithoutPay;
            	$this->EmployeeLeaveDetails[$Counter]['IsApproved'] = $SearchRow->isApproved;
            	$this->EmployeeLeaveDetails[$Counter]['LeaveTypeID'] = $SearchRow->leaveTypeID;
            	$Counter++;
            }

			return true;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException: EmployeeLeave::FillEmployeeLeaveDetails(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception: EmployeeLeave::FillEmployeeLeaveDetails(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
	}
	// END OF PUBLIC METHODS	//
	
	// START OF STATIC METHODS	//
	static function GetAllEmployeeLeaves($StaffCategory)
	{
		$AllEmployeeLeaves = array();

		try
		{
			$DBConnObject = new DBConnect();

			$RSSearch = $DBConnObject->Prepare('SELECT ael.*, MIN(aeld.leaveDate) AS leaveStartDate, 
													MAX(aeld.leaveDate) AS leaveEndDate , abs.firstName, abs.lastName, u.userName AS createUserName
												FROM ahr_employee_leaves ael 
												INNER JOIN ahr_employee_leave_days aeld ON ael.employeeLeaveID = aeld.employeeLeaveID 
												INNER JOIN asa_branch_staff abs ON ael.branchStaffID = abs.branchStaffID 
												INNER JOIN users u ON ael.createUserID = u.userID
												WHERE abs.staffCategory = :|1 GROUP BY branchStaffID, employeeLeaveID;');
			$RSSearch->Execute($StaffCategory);

			if ($RSSearch->Result->num_rows <= 0)
	        {
				return $AllEmployeeLeaves;	            
	        }

	        while($SearchRow = $RSSearch->FetchRow())
            {
            	$AllEmployeeLeaves[$SearchRow->employeeLeaveID]['FirstName'] = $SearchRow->firstName;
            	$AllEmployeeLeaves[$SearchRow->employeeLeaveID]['LastName'] = $SearchRow->lastName;
            	$AllEmployeeLeaves[$SearchRow->employeeLeaveID]['BranchStaffID'] = $SearchRow->branchStaffID;
            	$AllEmployeeLeaves[$SearchRow->employeeLeaveID]['LeaveStartDate'] = $SearchRow->leaveStartDate;
            	$AllEmployeeLeaves[$SearchRow->employeeLeaveID]['LeaveEndDate'] = $SearchRow->leaveEndDate;
            	$AllEmployeeLeaves[$SearchRow->employeeLeaveID]['CreateUserName'] = $SearchRow->createUserName;
            	$AllEmployeeLeaves[$SearchRow->employeeLeaveID]['CreateDate'] = $SearchRow->createDate;
            }

	        return $AllEmployeeLeaves;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at EmployeeLeave::GetAllEmployeeLeaves(). Stack Trace: ' . $e->getTraceAsString());
			return $AllEmployeeLeaves;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at EmployeeLeave::GetAllEmployeeLeaves(). Stack Trace: ' . $e->getTraceAsString());
			return $AllEmployeeLeaves;
		}
	}

    static function GetEmployeeLeaveTypeDetails($BranchStaffID)
    {
    	$AllLeaveTypeDetails = array();

    	try
		{
			$DBConnObject = new DBConnect();

			$RSSearch = $DBConnObject->Prepare('SELECT leaveTypeForStaffID, leaveTypeID, leaveType, leaveMode, leavePayType, monthlyCarryForward, noOfLeaves, 
																		(noOfLeaves - totalLeavesTaken) AS totalRemainingLeave FROM 
												(
													SELECT alt.leaveType, COUNT(aeldls.leaveTypeForStaffID) AS totalLeavesTaken, altfs.noOfLeaves, altfs.leaveMode, altfs.leavePayType, altfs.monthlyCarryForward, altfs.leaveTypeForStaffID, altfs.leaveTypeID					
														FROM ahr_employee_leaves ael 
													INNER JOIN ahr_employee_leave_days aeld ON ael.employeeLeaveID = aeld.employeeLeaveID 
													INNER JOIN ahr_employee_leaves_details aeldls ON aeld.employeeLeaveDayID = aeldls.employeeLeaveDayID AND aeld.isApproved = 1 AND aeld.isTaken = 0
													INNER JOIN ahr_leave_types_for_staff altfs ON aeldls.leaveTypeForStaffID = altfs.leaveTypeForStaffID 
													INNER JOIN ahr_leave_types alt ON altfs.leaveTypeID = alt.leaveTypeID 
													WHERE altfs.branchStaffID = :|1 GROUP BY altfs.leaveTypeID 

													UNION  

													SELECT leaveType, 0 AS totalLeavesTaken, u_altfs.noOfLeaves, u_altfs.leaveMode, u_altfs.leavePayType, u_altfs.monthlyCarryForward, u_altfs.leaveTypeForStaffID , u_altfs.leaveTypeID					
													FROM ahr_leave_types_for_staff u_altfs 
													INNER JOIN ahr_leave_types u_alt ON u_altfs.leaveTypeID = u_alt.leaveTypeID 
													WHERE u_altfs.branchStaffID = :|2
												) AS dt GROUP BY dt.leaveType;');
			$RSSearch->Execute($BranchStaffID, $BranchStaffID);

			if ($RSSearch->Result->num_rows <= 0)
            {
                return $AllLeaveTypeDetails;
            }

            while($SearchRow = $RSSearch->FetchRow())
            {
            	$AllLeaveTypeDetails[$SearchRow->leaveTypeForStaffID]['LeaveTypeID'] = $SearchRow->leaveTypeID;
                $AllLeaveTypeDetails[$SearchRow->leaveTypeForStaffID]['LeaveType'] = $SearchRow->leaveType;
                $AllLeaveTypeDetails[$SearchRow->leaveTypeForStaffID]['NoOfLeaves'] = $SearchRow->noOfLeaves;

                $AllLeaveTypeDetails[$SearchRow->leaveTypeForStaffID]['LeaveMode'] = $SearchRow->leaveMode;
                $AllLeaveTypeDetails[$SearchRow->leaveTypeForStaffID]['LeavePayType'] = $SearchRow->leavePayType;
                $AllLeaveTypeDetails[$SearchRow->leaveTypeForStaffID]['MonthlyCarryForward'] = $SearchRow->monthlyCarryForward;
                $AllLeaveTypeDetails[$SearchRow->leaveTypeForStaffID]['TotalRemainingLeave'] = $SearchRow->totalRemainingLeave;
            }
		
			return $AllLeaveTypeDetails;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at EmployeeLeave::GetEmployeeLeaveTypeDetails(). Stack Trace: ' . $e->getTraceAsString());
			return $AllLeaveTypeDetails;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at EmployeeLeave::GetEmployeeLeaveTypeDetails(). Stack Trace: ' . $e->getTraceAsString());
			return $AllLeaveTypeDetails;
		}
    }

    static function GetEmployeeLeaveSummary($BranchStaffID)
    {
    	$EmployeeLeaveSummary = array();
    	$EmployeeLeaveSummary['RequestedLeaves'] = 0;
    	$EmployeeLeaveSummary['ApprovedLeaves'] = 0;
    	$EmployeeLeaveSummary['TakenLeaves'] = 0;
    	$EmployeeLeaveSummary['CancelLeaves'] = 0;

    	try
		{
			$DBConnObject = new DBConnect();

			$RSRequestedLeavesSearch = $DBConnObject->Prepare('SELECT COUNT(aeld.leaveDate) AS totalRequestedLeaves FROM ahr_employee_leaves ael 
																INNER JOIN ahr_employee_leave_days aeld ON ael.employeeLeaveID = aeld.employeeLeaveID 
																WHERE branchStaffID = :|1;');
			$RSRequestedLeavesSearch->Execute($BranchStaffID);

			if ($RSRequestedLeavesSearch->Result->num_rows > 0)
            {
                while($RequestedLeavesSearchRow = $RSRequestedLeavesSearch->FetchRow())
	            {
	            	$EmployeeLeaveSummary['RequestedLeaves'] = $RequestedLeavesSearchRow->totalRequestedLeaves;
	            }
            }

            $RSApprovedLeavesSearch = $DBConnObject->Prepare('SELECT COUNT(aeld.leaveDate) AS totalApprovedLeaves FROM ahr_employee_leaves ael
															INNER JOIN ahr_employee_leave_days aeld ON ael.employeeLeaveID = aeld.employeeLeaveID
															WHERE branchStaffID = :|1 AND aeld.isApproved = 1;');
			$RSApprovedLeavesSearch->Execute($BranchStaffID);

			if ($RSApprovedLeavesSearch->Result->num_rows > 0)
            {
                while($ApprovedLeavesSearchRow = $RSApprovedLeavesSearch->FetchRow())
	            {
	            	$EmployeeLeaveSummary['ApprovedLeaves'] = $ApprovedLeavesSearchRow->totalApprovedLeaves;
	            }
            }

            $RSTakenLeavesSearch = $DBConnObject->Prepare('SELECT COUNT(aeld.leaveDate) AS totalTakenLeaves FROM ahr_employee_leaves ael
															INNER JOIN ahr_employee_leave_days aeld ON ael.employeeLeaveID = aeld.employeeLeaveID
															WHERE branchStaffID = :|1 AND aeld.isTaken = 1;');
			$RSTakenLeavesSearch->Execute($BranchStaffID);

			if ($RSTakenLeavesSearch->Result->num_rows > 0)
            {
                while($TakenLeavesSearchRow = $RSTakenLeavesSearch->FetchRow())
	            {
	            	$EmployeeLeaveSummary['TakenLeaves'] = $TakenLeavesSearchRow->totalTakenLeaves;
	            }
            }

            $RSCancelLeavesSearch = $DBConnObject->Prepare('SELECT COUNT(aeld.leaveDate) AS totalCancelLeaves FROM ahr_employee_leaves ael
															INNER JOIN ahr_employee_leave_days aeld ON ael.employeeLeaveID = aeld.employeeLeaveID
															WHERE branchStaffID = :|1 AND aeld.isApproved = 0;');
			$RSCancelLeavesSearch->Execute($BranchStaffID);

			if ($RSCancelLeavesSearch->Result->num_rows > 0)
            {
                while($CancelLeavesSearchRow = $RSCancelLeavesSearch->FetchRow())
	            {
	            	$EmployeeLeaveSummary['CancelLeaves'] = $CancelLeavesSearchRow->totalCancelLeaves;
	            }
            }         
		
			return $EmployeeLeaveSummary;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at EmployeeLeave::GetEmployeeLeaveSummary(). Stack Trace: ' . $e->getTraceAsString());
			return $EmployeeLeaveSummary;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at EmployeeLeave::GetEmployeeLeaveSummary(). Stack Trace: ' . $e->getTraceAsString());
			return $EmployeeLeaveSummary;
		}
    }

    static function GetEmployeeLeaveHistory($BranchStaffID)
    {
    	$EmployeeLeaveHistory = array();

    	try
		{
			$DBConnObject = new DBConnect();

			$RSLeaveHistorySearch = $DBConnObject->Prepare('SELECT aeld.*, alt.leaveType FROM ahr_employee_leaves ael 
															INNER JOIN ahr_employee_leave_days aeld ON ael.employeeLeaveID = aeld.employeeLeaveID 
															INNER JOIN ahr_employee_leaves_details aeldls ON aeld.employeeLeaveDayID = aeldls.employeeLeaveDayID
															INNER JOIN ahr_leave_types_for_staff altfs ON aeldls.leaveTypeForStaffID = altfs.leaveTypeForStaffID
															INNER JOIN ahr_leave_types alt ON altfs.leaveTypeID = alt.leaveTypeID
													    	WHERE ael.branchStaffID = :|1 ORDER BY aeld.leaveDate DESC;');
			$RSLeaveHistorySearch->Execute($BranchStaffID);

			if ($RSLeaveHistorySearch->Result->num_rows <= 0)
            {
            	return $EmployeeLeaveHistory;
            }

            while($LeaveHistorySearchRow = $RSLeaveHistorySearch->FetchRow())
            {
            	$EmployeeLeaveHistory[$LeaveHistorySearchRow->employeeLeaveDayID]['LeaveDate'] = $LeaveHistorySearchRow->leaveDate;
            	$EmployeeLeaveHistory[$LeaveHistorySearchRow->employeeLeaveDayID]['AppliedLeaveType'] = $LeaveHistorySearchRow->leaveType;
            	$EmployeeLeaveHistory[$LeaveHistorySearchRow->employeeLeaveDayID]['IsApproved'] = $LeaveHistorySearchRow->isApproved;
            	$EmployeeLeaveHistory[$LeaveHistorySearchRow->employeeLeaveDayID]['IsTaken'] = $LeaveHistorySearchRow->isTaken;
            }

			return $EmployeeLeaveHistory;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at EmployeeLeave::GetEmployeeLeaveHistory(). Stack Trace: ' . $e->getTraceAsString());
			return $EmployeeLeaveHistory;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at EmployeeLeave::GetEmployeeLeaveHistory(). Stack Trace: ' . $e->getTraceAsString());
			return $EmployeeLeaveHistory;
		}
    }
	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//
	private function SaveDetails()
	{		
		if ($this->EmployeeLeaveID == 0)
		{
			$RSEmployeeLeaveSave = $this->DBObject->Prepare('INSERT INTO ahr_employee_leaves (branchStaffID, createUserID, createDate)
														VALUES (:|1, :|2, NOW());');
			$RSEmployeeLeaveSave->Execute($this->BranchStaffID, $this->CreateUserID);
			
			$this->EmployeeLeaveID = $RSEmployeeLeaveSave->LastID;

			$EmployeeLeaveDayID = 0;
			foreach ($this->EmployeeLeaveDetails as $LeaveDate => $EmployeeLeaveDetailsValue) 
			{
				$RSEmployeeLeaveDaysSave = $this->DBObject->Prepare('INSERT INTO ahr_employee_leave_days (employeeLeaveID, leaveDate, isWithoutPay, isApproved)
														VALUES (:|1, :|2, :|3, :|4);');
				$RSEmployeeLeaveDaysSave->Execute($this->EmployeeLeaveID, $LeaveDate, $EmployeeLeaveDetailsValue['WithoutPay'], $EmployeeLeaveDetailsValue['IsApproved']);

				$EmployeeLeaveDayID = $RSEmployeeLeaveDaysSave->LastID;

				$RSEmployeeLeavesDatailsSave = $this->DBObject->Prepare('INSERT INTO ahr_employee_leaves_details (employeeLeaveDayID, leaveTypeForStaffID)
														VALUES (:|1, :|2);');
				$RSEmployeeLeavesDatailsSave->Execute($EmployeeLeaveDayID, $EmployeeLeaveDetailsValue['LeaveTypeForStaffID']);
			}
			
			return true;
		}
		else
		{
			$RSUpdate = $this->DBObject->Prepare('UPDATE ahr_employee_leaves
													SET	branchStaffID = :|1
													WHERE employeeLeaveID = :|2;');
			$RSUpdate->Execute($this->BranchStaffID, $this->EmployeeLeaveID);

			$RSDeleteEmployeeLeaveDetails = $this->DBObject->Prepare('DELETE FROM ahr_employee_leaves_details WHERE employeeLeaveDayID IN 
        															(SELECT employeeLeaveDayID FROM ahr_employee_leave_days WHERE employeeLeaveID = :|1);');
	        $RSDeleteEmployeeLeaveDetails->Execute($this->EmployeeLeaveID);

	        $RSDeleteEmployeeLeaveType = $this->DBObject->Prepare('DELETE FROM ahr_employee_leave_days WHERE employeeLeaveID = :|1;');
	        $RSDeleteEmployeeLeaveType->Execute($this->EmployeeLeaveID);

	        $EmployeeLeaveDayID = 0;
			foreach ($this->EmployeeLeaveDetails as $LeaveDate => $EmployeeLeaveDetailsValue) 
			{
				$RSEmployeeLeaveDaysSave = $this->DBObject->Prepare('INSERT INTO ahr_employee_leave_days (employeeLeaveID, leaveDate, isWithoutPay, isApproved)
														VALUES (:|1, :|2, :|3, :|4);');
				$RSEmployeeLeaveDaysSave->Execute($this->EmployeeLeaveID, $LeaveDate, $EmployeeLeaveDetailsValue['WithoutPay'], $EmployeeLeaveDetailsValue['IsApproved']);

				$EmployeeLeaveDayID = $RSEmployeeLeaveDaysSave->LastID;

				$RSEmployeeLeavesDatailsSave = $this->DBObject->Prepare('INSERT INTO ahr_employee_leaves_details (employeeLeaveDayID, leaveTypeForStaffID)
														VALUES (:|1, :|2);');
				$RSEmployeeLeavesDatailsSave->Execute($EmployeeLeaveDayID, $EmployeeLeaveDetailsValue['LeaveTypeForStaffID']);
			}
		}
		
		return true;
	}
	
	private function RemoveEmployeeLeave()
	{
		if (!isset($this->EmployeeLeaveID)) 
        {
            throw new ApplicationARException('', APP_AR_ERROR_DELETE_WITHOUT_ID);
        }
        
        $RSDeleteEmployeeLeaveDetails = $this->DBObject->Prepare('DELETE FROM ahr_employee_leaves_details WHERE employeeLeaveDayID IN 
        															(SELECT employeeLeaveDayID FROM ahr_employee_leave_days WHERE employeeLeaveID = :|1);');
        $RSDeleteEmployeeLeaveDetails->Execute($this->EmployeeLeaveID);

        $RSDeleteEmployeeLeaveType = $this->DBObject->Prepare('DELETE FROM ahr_employee_leave_days WHERE employeeLeaveID = :|1;');
        $RSDeleteEmployeeLeaveType->Execute($this->EmployeeLeaveID);

        $RSDeleteEmployeeLeaveType = $this->DBObject->Prepare('DELETE FROM ahr_employee_leaves WHERE employeeLeaveID = :|1 LIMIT 1;');
        $RSDeleteEmployeeLeaveType->Execute($this->EmployeeLeaveID);

        return true;
	}

	private function GetEmployeeLeaveByID()
	{
		$RSEmployeeLeave = $this->DBObject->Prepare('SELECT * FROM ahr_employee_leaves WHERE employeeLeaveID = :|1 LIMIT 1;');
		$RSEmployeeLeave->Execute($this->EmployeeLeaveID);
		
		$EmployeeLeaveRow = $RSEmployeeLeave->FetchRow();
		
		$this->SetAttributesFromDB($EmployeeLeaveRow);				
	}
	
	private function SetAttributesFromDB($EmployeeLeaveRow)
	{
		$this->GetEmployeeLeaveID = $EmployeeLeaveRow->employeeLeaveID;
		$this->BranchStaffID = $EmployeeLeaveRow->branchStaffID;

		$this->CreateUserID = $EmployeeLeaveRow->createUserID;
		$this->CreateDate = $EmployeeLeaveRow->createDate;
	}	
}
?>
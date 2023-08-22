<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.db_connect.php');
error_reporting(E_ALL);

class LeaveType
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	
	private $LeaveTypeID;
	private $StaffCategory;

	private $LeaveType;
	private $NoOfLeaves;
	private $LeaveMode;

	private $LeavePayType;
	private $MonthlyCarryForward;
	private $Priority;

	private $CreateUserID;
	private $CreateDate;
	
	// PUBLIC METHODS START HERE	//
	public function __construct($LeaveTypeID = 0)
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;
		
		if($LeaveTypeID != 0)
		{
			$this->LeaveTypeID = $LeaveTypeID;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetLeaveTypeByID();
		}
		else
		{
			//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
			$this->LeaveTypeID = 0;
			$this->StaffCategory = 'Teaching';

			$this->LeaveType = 'Monthly';
			$this->NoOfLeaves = 0;
			$this->LeaveMode = 'WithoutPay';

			$this->LeavePayType = '';
			$this->MonthlyCarryForward = 0;
			$this->Priority = 0;

			$this->CreateUserID = 0;
			$this->CreateDate = '0000-00-00 00:00:00';
		}
	}
	
	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetLeaveTypeID()
	{
		return $this->LeaveTypeID;
	}
	
	public function GetStaffCategory()
	{
		return $this->StaffCategory;
	}
	public function SetStaffCategory($StaffCategory)
	{
		$this->StaffCategory = $StaffCategory;
	}

	public function GetLeaveType()
	{
		return $this->LeaveType;
	}
	public function SetLeaveType($LeaveType)
	{
		$this->LeaveType = $LeaveType;
	}
	
	public function GetNoOfLeaves()
	{
		return $this->NoOfLeaves;
	}
	public function SetNoOfLeaves($NoOfLeaves)
	{
		$this->NoOfLeaves = $NoOfLeaves;
	}
	
	public function GetLeaveMode()
	{
		return $this->LeaveMode;
	}
	public function SetLeaveMode($LeaveMode)
	{
		$this->LeaveMode = $LeaveMode;
	}

	public function GetLeavePayType()
	{
		return $this->LeavePayType;
	}
	public function SetLeavePayType($LeavePayType)
	{
		$this->LeavePayType = $LeavePayType;
	}

	public function GetMonthlyCarryForward()
	{
		return $this->MonthlyCarryForward;
	}
	public function SetMonthlyCarryForward($MonthlyCarryForward)
	{
		$this->MonthlyCarryForward = $MonthlyCarryForward;
	}	
	
	public function GetPriority()
	{
		return $this->Priority;
	}
	public function SetPriority($Priority)
	{
		$this->Priority = $Priority;
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
	
	public function GetLastErrorCode()
	{
		return $this->LastErrorCode;
	}
	//  END OF GETTER AND SETTER FUNCTIONS 	//
	
	public function Save()
	{
		try
		{
			return $this->SaveDetails();
		}
		catch (ApplicationDBException $e)
		{
			$this->LastErrorCode = $e->getCode();
			return false;
		}
		catch (ApplicationARException $e)
		{
			$this->LastErrorCode = $e->getCode();
			return false;
		}
		catch (Exception $e)
		{
			$this->LastErrorCode = APP_ERROR_UNDEFINED_ERROR;
			return false;
		}
	}

	public function Remove()
    {
        try
        {
            $this->RemoveLeaveType();
            return true;
        }
        catch (ApplicationDBException $e)
        {
            $this->LastErrorCode = $e->getCode();
            return false;
        }
        catch (ApplicationARException $e)
        {
            $this->LastErrorCode = $e->getCode();
            return false;
        }
        catch (Exception $e)
        {
            $this->LastErrorCode = APP_ERROR_UNDEFINED_ERROR;
            return false;
        }
    }

    public function CheckDependencies()
	{
		try
		{
			$RSTotal = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM ahr_leave_types_for_staff WHERE leaveTypeID = :|1;');
			$RSTotal->Execute($this->LeaveTypeID);
	
			if ($RSTotal->FetchRow()->totalRecords > 0) 
			{
				return true;
			}
			
			return false;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException: LeaveType::CheckDependencies(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception: LeaveType::CheckDependencies(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
	}

	public function RecordExists()
	{
		try
		{
			$QueryString = '';
			
			if ($this->LeaveTypeID > 0)
			{
				$QueryString = ' AND leaveTypeID != ' . $this->DBObject->RealEscapeVariable($this->LeaveTypeID);
			}

			$RSTotal = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM ahr_leave_types WHERE leaveType = :|1' . $QueryString . ';');
			$RSTotal->Execute($this->LeaveType);
			
			if ($RSTotal->FetchRow()->totalRecords > 0)
			{
				return true;
			}
			
			return false;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at LeaveType::RecordExists(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at LeaveType::RecordExists(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
	}
    
    public function AssignedLeaveTypesToStaffCategory($AssignLeaveTypesToStaff, $BranchStaffList)
    {
        try
        {	
        	$this->DBObject->BeginTransaction();

        	foreach ($AssignLeaveTypesToStaff as $LeaveTypeID => $Value)
        	{
        		foreach ($BranchStaffList as $BranchStaffID => $BranchStaffDetails) 
        		{
        			$RSSave = $this->DBObject->Prepare('INSERT INTO ahr_leave_types_for_staff (leaveTypeID, branchStaffID, noOfLeaves, 
        																						leaveMode, leavePayType, monthlyCarryForward)
													 			SELECT :|1, :|2, noOfLeaves, leaveMode, leavePayType, monthlyCarryForward FROM ahr_leave_types WHERE leaveTypeID = :|3;');
					$RSSave->Execute($LeaveTypeID, $BranchStaffID, $LeaveTypeID);
					
					$this->LeaveTypeID = $RSSave->LastID;
        		}
        	}

        	$this->DBObject->CommitTransaction();
	        return true;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at LeaveType::AssignedLeaveTypesToStaffCategory(). Stack Trace: ' . $e->getTraceAsString());
            $this->DBObject->RollBackTransaction();
            return false;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception at LeaveType::AssignedLeaveTypesToStaffCategory(). Stack Trace: ' . $e->getTraceAsString());
            $this->DBObject->RollBackTransaction();
            return false;
        }
    }

	// END OF PUBLIC METHODS	//
	
	// START OF STATIC METHODS	//
	static function GetAllLeaveTypes($StaffCategory, $GetOnlyName = false)
	{
		$AllLeaveTypes = array();

        try
        {
	        $DBConnObject = new DBConnect();

	        $RSSearch = $DBConnObject->Prepare('SELECT alt.*, u.userName AS createUserName FROM ahr_leave_types alt
														INNER JOIN users u ON alt.createUserID = u.userID
														WHERE staffCategory = :|1 ORDER BY alt.priority;');
            $RSSearch->Execute($StaffCategory);

            if ($RSSearch->Result->num_rows <= 0)
            {
                return $AllLeaveTypes;
            }

            while($SearchRow = $RSSearch->FetchRow())
            {	
            	if($GetOnlyName)
            	{
            		$AllLeaveTypes[$SearchRow->leaveTypeID] = $SearchRow->leaveType;
            		continue;
            	}
            	
		        $AllLeaveTypes[$SearchRow->leaveTypeID]['StaffCategory'] = $SearchRow->staffCategory;
		        $AllLeaveTypes[$SearchRow->leaveTypeID]['LeaveType'] = $SearchRow->leaveType;

		        $AllLeaveTypes[$SearchRow->leaveTypeID]['NoOfLeaves'] = $SearchRow->noOfLeaves;
		        $AllLeaveTypes[$SearchRow->leaveTypeID]['LeaveMode'] = $SearchRow->leaveMode;
		        $AllLeaveTypes[$SearchRow->leaveTypeID]['LeavePayType'] = $SearchRow->leavePayType;
		        $AllLeaveTypes[$SearchRow->leaveTypeID]['MonthlyCarryForward'] = $SearchRow->monthlyCarryForward;
		        $AllLeaveTypes[$SearchRow->leaveTypeID]['Priority'] = $SearchRow->priority;

		        $AllLeaveTypes[$SearchRow->leaveTypeID]['CreateUserID'] = $SearchRow->createUserID;
		        $AllLeaveTypes[$SearchRow->leaveTypeID]['CreateUserName'] = $SearchRow->createUserName;
				$AllLeaveTypes[$SearchRow->leaveTypeID]['CreateDate'] = $SearchRow->createDate;
            }

            return $AllLeaveTypes;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at LeaveType::GetAllLeaveTypes(). Stack Trace: ' . $e->getTraceAsString());
            return $AllLeaveTypes;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception at LeaveType::GetAllLeaveTypes(). Stack Trace: ' . $e->getTraceAsString());
            return $AllLeaveTypes;
        }
	}

	static function UpdateLeaveTypePriorities($Priorities)
	{
		try
		{
			$DBConnObject = new DBConnect();
			
			$DBConnObject->BeginTransaction();

			foreach ($Priorities as $LeaveTypeID => $Priority)
			{
				$RSUpdate = $DBConnObject->Prepare('UPDATE ahr_leave_types SET priority = :|1 WHERE leaveTypeID = :|2 LIMIT 1;');
				$RSUpdate->Execute($Priority, $LeaveTypeID);
			}

			$DBConnObject->CommitTransaction();
			return true;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException: LeaveType::UpdateLeaveTypePriorities(). Stack Trace: ' . $e->getTraceAsString());
			$DBConnObject->RollBackTransaction();
			return false;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception: LeaveType::UpdateLeaveTypePriorities(). Stack Trace: ' . $e->getTraceAsString());
			$DBConnObject->RollBackTransaction();
			return false;
		}
	}

	static function AssignedLeaveTypeExistsForBranchStaff($StaffCategory, $AssignLeaveTypesToStaff)
	{
		$LeaveTypeIDs = '';

		try
		{
			$DBConnObject = new DBConnect();

			if (count($AssignLeaveTypesToStaff) <= 0) 
			{
				return false;
			}

			$LeaveTypeIDs = implode(',', $AssignLeaveTypesToStaff);

			$RSCount = $DBConnObject->Prepare('SELECT COUNT(*) AS totalRecords FROM ahr_leave_types_for_staff altfs
														INNER JOIN ahr_leave_types alt ON alt.leaveTypeID = altfs.leaveTypeID 
														wHERE alt.staffCategory = :|1 AND alt.leaveTypeID IN (' . $LeaveTypeIDs . ');');
			$RSCount->Execute($StaffCategory);

			if($RSCount->FetchRow()->totalRecords == 0)
			{
				return true;
			}

			return false;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException: LeaveType::AssignedLeaveTypeExistsForBranchStaff(). Stack Trace: ' . $e->getTraceAsString());
			return false;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception: LeaveType::AssignedLeaveTypeExistsForBranchStaff(). Stack Trace: ' . $e->getTraceAsString());
			return false;
		}
	}

	static function UpdateBranchStaffLeaveTypes($LeaveTypesDetails)
    {
    	try
        {	
    		$DBConnObject = new DBConnect();
    		
    		$DBConnObject->BeginTransaction();

    		foreach ($LeaveTypesDetails as $LeaveTypeForStaffID => $LeaveTypesDetailsValue) 
    		{
				$RSUpdate = $DBConnObject->Prepare('UPDATE ahr_leave_types_for_staff 
																SET noOfLeaves = :|1,
																	leaveMode = :|2,
																	leavePayType = :|3,
																	monthlyCarryForward = :|4
																WHERE leaveTypeForStaffID = :|5;');
            	$RSUpdate->Execute($LeaveTypesDetailsValue['NoOfLeaves'], $LeaveTypesDetailsValue['LeaveMode'], $LeaveTypesDetailsValue['LeavePayType'], 
            										$LeaveTypesDetailsValue['MonthlyCarryForward'], $LeaveTypeForStaffID);
    		}

    		$DBConnObject->CommitTransaction();
            return true;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException: LeaveType::UpdateBranchStaffLeaveTypes. Stack Trace: ' . $e->getTraceAsString());
            $DBConnObject->RollBackTransaction();
            return false;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception: LeaveType::UpdateBranchStaffLeaveTypes . Stack Trace: ' . $e->getTraceAsString());
            $DBConnObject->RollBackTransaction();
            return false;
        }
    }

	static function GetAssignedLeaveTypesToStaffCategory($BranchStaffCategory)
    {
    	$AssignedLeaveTypesToStaff = array();

    	try
        {
        	$DBConnObject = new DBConnect();

        	$RSSearch = $DBConnObject->Prepare('SELECT altfs.leaveTypeID, altfs.leaveTypeForStaffID FROM ahr_leave_types_for_staff altfs
											 				INNER JOIN ahr_leave_types ON ahr_leave_types.leaveTypeID = altfs.leaveTypeID
												  			WHERE ahr_leave_types.staffCategory = :|1;');
			$RSSearch->Execute($BranchStaffCategory);

			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AssignedLeaveTypesToStaff;	
			}

			while($SearchRow = $RSSearch->FetchRow())
			{
				$AssignedLeaveTypesToStaff[$SearchRow->leaveTypeID] = $SearchRow->leaveTypeForStaffID;
			}

	        return $AssignedLeaveTypesToStaff;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at LeaveType::GetAssignedLeaveTypesToStaffCategory(). Stack Trace: ' . $e->getTraceAsString());
            return $AssignedLeaveTypesToStaff;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception at LeaveType::GetAssignedLeaveTypesToStaffCategory(). Stack Trace: ' . $e->getTraceAsString());
            return $AssignedLeaveTypesToStaff;
        }
    }
	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//
	private function SaveDetails()
	{
		if ($this->LeaveTypeID == 0)
		{
			$RSSave = $this->DBObject->Prepare('INSERT INTO ahr_leave_types (staffCategory, leaveType, noOfLeaves, leaveMode, leavePayType, 
																				monthlyCarryForward, priority, createUserID, createDate)
														VALUES (:|1, :|2, :|3, :|4, :|5, :|6, :|7, :|8, NOW());');
			$RSSave->Execute($this->StaffCategory, $this->LeaveType, $this->NoOfLeaves, $this->LeaveMode, $this->LeavePayType, $this->MonthlyCarryForward,
																 $this->Priority, $this->CreateUserID);
			
			$this->LeaveTypeID = $RSSave->LastID;
		}
		else
		{
			$RSUpdate = $this->DBObject->Prepare('UPDATE ahr_leave_types
													SET	staffCategory = :|1,
														leaveType = :|2,
														noOfLeaves = :|3,
														leaveMode = :|4,
														leavePayType = :|5,
														monthlyCarryForward = :|6,
														priority = :|7
													WHERE leaveTypeID = :|8;');						
			$RSUpdate->Execute($this->StaffCategory, $this->LeaveType, $this->NoOfLeaves, $this->LeaveMode, $this->LeavePayType, $this->MonthlyCarryForward, 
										$this->Priority, $this->LeaveTypeID);
		}
		
		return true;
	}

	private function RemoveLeaveType()
	{
		if(!isset($this->LeaveTypeID)) 
        {
            throw new ApplicationARException('', APP_AR_ERROR_DELETE_WITHOUT_ID);
        }
        
        $RSDeleteLeaveType = $this->DBObject->Prepare('DELETE FROM ahr_leave_types WHERE leaveTypeID = :|1 LIMIT 1;');
        $RSDeleteLeaveType->Execute($this->LeaveTypeID);
	}
	
	private function GetLeaveTypeByID()
	{
		$RSLeaveType = $this->DBObject->Prepare('SELECT * FROM ahr_leave_types WHERE leaveTypeID = :|1 LIMIT 1;');
		$RSLeaveType->Execute($this->LeaveTypeID);
		
		$LeaveTypeRow = $RSLeaveType->FetchRow();
		
		$this->SetAttributesFromDB($LeaveTypeRow);				
	}
	
	private function SetAttributesFromDB($LeaveTypeRow)
	{
		$this->LeaveTypeID = $LeaveTypeRow->leaveTypeID;
		$this->StaffCategory = $LeaveTypeRow->staffCategory;

		$this->LeaveType = $LeaveTypeRow->leaveType;
		$this->NoOfLeaves = $LeaveTypeRow->noOfLeaves;
		$this->LeaveMode = $LeaveTypeRow->leaveMode;

		$this->LeavePayType = $LeaveTypeRow->leavePayType;
		$this->MonthlyCarryForward = $LeaveTypeRow->monthlyCarryForward;
		$this->Priority = $LeaveTypeRow->priority;

		$this->CreateUserID = $LeaveTypeRow->createUserID;
		$this->CreateDate = $LeaveTypeRow->createDate;
	}	
}
?>
<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.db_connect.php');
error_reporting(E_ALL);

class LeaveDeductionRule
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	
	private $LeaveDeductionRuleID;
	private $StaffCategory;
	private $RuleType;

	private $CreateUserID;
	private $CreateDate;

	private $DeductionPercentageDetails = array();
	
	// PUBLIC METHODS START HERE	//
	public function __construct($LeaveDeductionRuleID = 0)
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;
		
		if($LeaveDeductionRuleID != 0)
		{
			$this->LeaveDeductionRuleID = $LeaveDeductionRuleID;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetLeaveDeductionRuleByID();
		}
		else
		{
			//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
			$this->LeaveDeductionRuleID = 0;
			$this->StaffCategory = 'Teaching';
			$this->RuleType = 'FullDay';

			$this->CreateUserID = 0;
			$this->CreateDate = '0000-00-00 00:00:00';

			$this->DeductionPercentageDetails = array();
		}
	}
	
	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetLeaveDeductionRuleID()
	{
		return $this->LeaveDeductionRuleID;
	}
	
	public function GetStaffCategory()
	{
		return $this->StaffCategory;
	}
	public function SetStaffCategory($StaffCategory)
	{
		$this->StaffCategory = $StaffCategory;
	}
	
	public function GetRuleType()
	{
		return $this->RuleType;
	}
	public function SetRuleType($RuleType)
	{
		$this->RuleType = $RuleType;
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
	public function SetCreateDate($CreateDate)
	{
		$this->CreateDate = $CreateDate;
	}

	public function GetDeductionPercentageDetails()
	{
		return $this->DeductionPercentageDetails;
	}
	public function SetDeductionPercentageDetails($DeductionPercentageDetails)
	{
		$this->DeductionPercentageDetails = $DeductionPercentageDetails;
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
			if ($this->RemoveLeaveDeductionRule())
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
	
	public function RecordExists()
	{
		try
		{
			$QueryString = '';

			if ($this->LeaveDeductionRuleID > 0)
			{
				$QueryString = ' AND leaveDeductionRuleID != ' . $this->DBObject->RealEscapeVariable($this->LeaveDeductionRuleID);
			}

			$RSTotal = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM ahr_leave_deduction_rules WHERE ruleType = :|1 AND staffCategory = :|2' . $QueryString . ';');
			$RSTotal->Execute($this->RuleType, $this->StaffCategory);
			
			if ($RSTotal->FetchRow()->totalRecords > 0)
			{
				return true;
			}
			
			return false;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at LeaveDeductionRule::RecordExists(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at LeaveDeductionRule::RecordExists(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
	}

	public function FillDeductionPercentageDetails()
	{	
    	try
        {	
        	$DBConnObject = new DBConnect();

        	$RSSearch = $DBConnObject->Prepare('SELECT * FROM ahr_leave_deduction_rules_details WHERE leaveDeductionRuleID = :|1;');
            $RSSearch->Execute($this->LeaveDeductionRuleID );

            while($SearchRow = $RSSearch->FetchRow())
            {
                $this->DeductionPercentageDetails[$SearchRow->salaryPartID] = $SearchRow->deductionPercentage;
           }
            
            return true;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException::LeaveDeductionRule::GetAllLeaveDeductionRules(). Stack Trace: ' . $e->getTraceAsString());
            return false;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception: LeaveDeductionRule::GetAllLeaveDeductionRules() . Stack Trace: ' . $e->getTraceAsString());
            return false;
        }
	}

	// END OF PUBLIC METHODS	//
	
	// START OF STATIC METHODS	//
	static function GetAllLeaveDeductionRules()
    { 
		$AllLeaveDeductionRules = array();
		
    	try
        {	
        	$DBConnObject = new DBConnect();
        	$RSSearch = $DBConnObject->Prepare('SELECT aldr.*, u.userName AS createUserName 
        											FROM ahr_leave_deduction_rules aldr
													INNER JOIN users u ON aldr.createUserID = u.userID 
	    											ORDER BY staffCategory;');
            $RSSearch->Execute();

            if ($RSSearch->Result->num_rows <= 0)
            {
                return $AllLeaveDeductionRules;
            }

            while($SearchRow = $RSSearch->FetchRow())
            {
                $AllLeaveDeductionRules[$SearchRow->leaveDeductionRuleID]['StaffCategory'] = $SearchRow->staffCategory;
                $AllLeaveDeductionRules[$SearchRow->leaveDeductionRuleID]['RuleType'] = $SearchRow->ruleType;
                $AllLeaveDeductionRules[$SearchRow->leaveDeductionRuleID]['CreateUserName'] = $SearchRow->createUserName;
                $AllLeaveDeductionRules[$SearchRow->leaveDeductionRuleID]['CreateDate'] = $SearchRow->createDate;
           }
            
            return $AllLeaveDeductionRules;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException::LeaveDeductionRule::GetAllLeaveDeductionRules(). Stack Trace: ' . $e->getTraceAsString());
            return $AllLeaveDeductionRules;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception: LeaveDeductionRule::GetAllLeaveDeductionRules() . Stack Trace: ' . $e->getTraceAsString());
            return $AllLeaveDeductionRules;
        }
    }

    static function GetActiveLeaveDeductionRules()
	{
		$AllLeaveDeductionRules = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT * FROM ahr_leave_deduction_rules WHERE isActive = 1;');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllLeaveDeductionRules;
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$AllLeaveDeductionRules[$SearchRow->leaveDeductionRuleID] = $SearchRow->staffCategory;
			}
			
			return $AllLeaveDeductionRules;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at LeaveDeductionRule::GetActiveLeaveDeductionRules(). Stack Trace: ' . $e->getTraceAsString());
			return $AllLeaveDeductionRules;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at LeaveDeductionRule::GetActiveLeaveDeductionRules(). Stack Trace: ' . $e->getTraceAsString());
			return $AllLeaveDeductionRules;
		}		
	}
    
	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//
	private function SaveDetails()
	{
		if ($this->LeaveDeductionRuleID == 0)
		{
			$RSLeaveDeductionRuleSave = $this->DBObject->Prepare('INSERT INTO ahr_leave_deduction_rules (ruleType, staffCategory, createUserID, createDate)
																			VALUES (:|1, :|2, :|3, NOW());');
			$RSLeaveDeductionRuleSave->Execute($this->RuleType, $this->StaffCategory, $this->CreateUserID);

			$this->LeaveDeductionRuleID = $RSLeaveDeductionRuleSave->LastID;

			foreach ($this->DeductionPercentageDetails as $SalaryPartID => $DeductionPercentage) 
			{
				$RSLeaveDeductionRuleSave = $this->DBObject->Prepare('INSERT INTO ahr_leave_deduction_rules_details (leaveDeductionRuleID, salaryPartID, deductionPercentage)
																			VALUES (:|1, :|2, :|3);');
				$RSLeaveDeductionRuleSave->Execute($this->LeaveDeductionRuleID, $SalaryPartID, $DeductionPercentage);
			}
		}
		else
		{
			$RSUpdate = $this->DBObject->Prepare('UPDATE ahr_leave_deduction_rules
													SET	ruleType = :|1,
														staffCategory = :|2
													WHERE leaveDeductionRuleID = :|3 LIMIT 1;');
			$RSUpdate->Execute($this->RuleType, $this->StaffCategory, $this->LeaveDeductionRuleID);

			$RSDeleteLeaveDeductionRuleDetials = $this->DBObject->Prepare('DELETE FROM ahr_leave_deduction_rules_details WHERE leaveDeductionRuleID = :|1;');
			$RSDeleteLeaveDeductionRuleDetials->Execute($this->LeaveDeductionRuleID);

			foreach ($this->DeductionPercentageDetails as $SalaryPartID => $DeductionPercentage) 
			{
				$RSLeaveDeductionRuleSave = $this->DBObject->Prepare('INSERT INTO ahr_leave_deduction_rules_details (leaveDeductionRuleID, salaryPartID, deductionPercentage)
																				VALUES (:|1, :|2, :|3);');
				$RSLeaveDeductionRuleSave->Execute($this->LeaveDeductionRuleID, $SalaryPartID, $DeductionPercentage);
			}
		}
		
		return true;
	}

	private function RemoveLeaveDeductionRule()
	{
		if(!isset($this->LeaveDeductionRuleID)) 
		{
    		throw new ApplicationARException('', APP_AR_ERROR_DELETE_WITHOUT_ID);
		}

		$RSDeleteLeaveDeductionRuleDetials = $this->DBObject->Prepare('DELETE FROM ahr_leave_deduction_rules_details WHERE leaveDeductionRuleID = :|1;');
		$RSDeleteLeaveDeductionRuleDetials->Execute($this->LeaveDeductionRuleID);
		
		$RSDeleteLeaveDeductionRule = $this->DBObject->Prepare('DELETE FROM ahr_leave_deduction_rules WHERE leaveDeductionRuleID = :|1 LIMIT 1;');
		$RSDeleteLeaveDeductionRule->Execute($this->LeaveDeductionRuleID);				

		return true;
	}
	
	private function GetLeaveDeductionRuleByID()
	{
		$RSLeaveDeductionRule = $this->DBObject->Prepare('SELECT * FROM ahr_leave_deduction_rules WHERE leaveDeductionRuleID = :|1 LIMIT 1;');
		$RSLeaveDeductionRule->Execute($this->LeaveDeductionRuleID);
		
		$LeaveDeductionRuleRow = $RSLeaveDeductionRule->FetchRow();
		
		$this->SetAttributesFromDB($LeaveDeductionRuleRow);				
	}
	
	private function SetAttributesFromDB($LeaveDeductionRuleRow)
	{
		$this->LeaveDeductionRuleID = $LeaveDeductionRuleRow->leaveDeductionRuleID;
		$this->RuleType = $LeaveDeductionRuleRow->ruleType;
		$this->StaffCategory = $LeaveDeductionRuleRow->staffCategory;

		$this->CreateUserID = $LeaveDeductionRuleRow->createUserID;
		$this->CreateDate = $LeaveDeductionRuleRow->createDate;
	}	
}
?>
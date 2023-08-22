<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.db_connect.php');
error_reporting(E_ALL);

class LateFeeRule
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	
	private $LateFeeRuleID;

	private $ChargeMethod;
	private $RangeFromDay;
	private $RangeToDay;
	private $LateFeeAmount;

	private $CreateUserID;
	private $CreateDate;

	private $LateFeeRules = array();
	
	// PUBLIC METHODS START HERE	//
	public function __construct($LateFeeRuleID = 0)
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;
		
		if($LateFeeRuleID != 0)
		{
			$this->LateFeeRuleID = $LateFeeRuleID;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetLateFeeRuleByID();
		}
		else
		{
			//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
			$this->LateFeeRuleID = 0;

			$this->ChargeMethod = '';
			$this->RangeFromDay = 0;
			$this->RangeToDay = 0;
			$this->LateFeeAmount = 0;

			$this->CreateUserID = 0;
			$this->CreateDate = '0000-00-00 00:00:00';

			$this->LateFeeRules = array();
		}
	}
	
	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetLateFeeRuleID()
	{
		return $this->LateFeeRuleID;
	}
	
	public function GetChargeMethod()
	{
		return $this->ChargeMethod;
	}
	public function SetChargeMethod($ChargeMethod)
	{
		$this->ChargeMethod = $ChargeMethod;
	}

	public function GetRangeFromDay()
	{
		return $this->RangeFromDay;
	}
	public function SetRangeFromDay($RangeFromDay)
	{
		$this->RangeFromDay = $RangeFromDay;
	}

	public function GetRangeToDay()
	{
		return $this->RangeToDay;
	}
	public function SetRangeToDay($RangeToDay)
	{
		$this->RangeToDay = $RangeToDay;
	}

	public function GetLateFeeAmount()
	{
		return $this->LateFeeAmount;
	}
	public function SetLateFeeAmount($LateFeeAmount)
	{
		$this->LateFeeAmount = $LateFeeAmount;
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

	public function GetLateFeeRules()
	{
		return $this->LateFeeRules;
	}
	public function SetLateFeeRules($LateFeeRules)
	{
		$this->LateFeeRules = $LateFeeRules;
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
            $this->RemoveLateFeeRule();
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
		
	// END OF PUBLIC METHODS	//
	
	// START OF STATIC METHODS	//
	static function GetAllLateFeeRules()
	{
		$AllLateFeeRules = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT * FROM afm_late_fee_rules ORDER BY lateFeeRuleID;');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllLateFeeRules; 
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$AllLateFeeRules[$SearchRow->lateFeeRuleID]['ChargeMethod'] = $SearchRow->chargeMethod;
				$AllLateFeeRules[$SearchRow->lateFeeRuleID]['RangeFromDay'] = $SearchRow->rangeFromDay;
				$AllLateFeeRules[$SearchRow->lateFeeRuleID]['RangeToDay'] = $SearchRow->rangeToDay;
				$AllLateFeeRules[$SearchRow->lateFeeRuleID]['LateFeeAmount'] = $SearchRow->lateFeeAmount;
			}
			
			return $AllLateFeeRules;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at LateFeeRule::GetAllLateFeeRules(). Stack Trace: ' . $e->getTraceAsString());
			return $AllLateFeeRules;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at LateFeeRule::GetAllLateFeeRules(). Stack Trace: ' . $e->getTraceAsString());
			return $AllLateFeeRules;
		}
	}
	
	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//
	private function SaveDetails()
	{
		if (count($this->LateFeeRules) > 0)
		{
			$RSDelete = $this->DBObject->Prepare('DELETE FROM afm_late_fee_rules');
			$RSDelete->Execute();

			foreach ($this->LateFeeRules as $Counter => $Details) 
			{
				$RSSave = $this->DBObject->Prepare('INSERT INTO afm_late_fee_rules (chargeMethod, rangeFromDay, rangeToDay, lateFeeAmount,createUserID, createDate)
													VALUES (:|1, :|2, :|3, :|4, :|5, NOW());');
		
				$RSSave->Execute($Details['ChargeMethod'], $Details['RangeFromDay'], $Details['RangeToDay'], $Details['LateFeeAmount'], $this->CreateUserID);
			}
		}
		
		return true;
	}

	private function RemoveLateFeeRule()
    {
        if(!isset($this->LateFeeRuleID)) 
        {
            throw new ApplicationARException('', APP_AR_ERROR_DELETE_WITHOUT_ID);
        }
        
        $RSDeleteLateFeeRule = $this->DBObject->Prepare('DELETE FROM afm_late_fee_rules WHERE lateFeeRuleID = :|1 LIMIT 1;');
        $RSDeleteLateFeeRule->Execute($this->LateFeeRuleID);  

        return true;              
    }
	
	private function GetLateFeeRuleByID()
	{
		$RSLateFeeRule = $this->DBObject->Prepare('SELECT * FROM afm_late_fee_rules WHERE lateFeeRuleID = :|1 LIMIT 1;');
		$RSLateFeeRule->Execute($this->LateFeeRuleID);
		
		$LateFeeRuleRow = $RSLateFeeRule->FetchRow();
		
		$this->SetAttributesFromDB($LateFeeRuleRow);				
	}
	
	private function SetAttributesFromDB($LateFeeRuleRow)
	{
		$this->LateFeeRuleID = $LateFeeRuleRow->lateFeeRuleID;

		$this->ChargeMethod = $LateFeeRuleRow->chargeMethod;
		$this->RangeFromDay = $LateFeeRuleRow->rangeFromDay;
		$this->RangeToDay = $LateFeeRuleRow->rangeToDay;
		$this->LateFeeAmount = $LateFeeRuleRow->lateFeeAmount;

		$this->IsActive = $LateFeeRuleRow->isActive;

		$this->CreateUserID = $LateFeeRuleRow->createUserID;
		$this->CreateDate = $LateFeeRuleRow->createDate;
	}	
}
?>
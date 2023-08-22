<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.db_connect.php');
error_reporting(E_ALL);

class FeeReceiptTermCondition
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	
	private $FeeReceiptTermConditionID;
	private $TermConditionMessage;

	private $TermConditionMessageList = array();
	private $RemoveConditionMessageList = array();

	private $CreateUserID;
	private $CreateDate;
	// PUBLIC METHODS START HERE	//
	public function __construct($FeeReceiptTermConditionID = 0)
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;
		
		if($FeeReceiptTermConditionID != 0)
		{
			$this->FeeReceiptTermConditionID = $FeeReceiptTermConditionID;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetFeeReceiptTermConditionByID();
		}
		else
		{
			//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
			$this->FeeReceiptTermConditionID = 0;
			$this->TermConditionMessage = '';

			$this->TermConditionMessageList = array();
			$this->RemoveConditionMessageList = array();

			$this->CreateUserID = 0;
			$this->CreateDate = '0000-00-00 00:00:00';
		}
	}
	
	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetFeeReceiptTermConditionID()
	{
		return $this->FeeReceiptTermConditionID;
	}
	
	public function GetTermConditionMessage()
	{
		return $this->TermConditionMessage;
	}
	public function SetTermConditionMessage($TermConditionMessage)
	{
		$this->TermConditionMessage = $TermConditionMessage;
	}
	
	public function GetTermConditionMessageList()
	{
		return $this->TermConditionMessageList;
	}
	public function SetTermConditionMessageList($TermConditionMessageList)
	{
		$this->TermConditionMessageList = $TermConditionMessageList;
	}

	public function GetRemoveConditionMessageList()
	{
		return $this->RemoveConditionMessageList;
	}
	public function SetRemoveConditionMessageList($RemoveConditionMessageList)
	{
		 $this->RemoveConditionMessageList = $RemoveConditionMessageList;
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
            $this->RemoveFeeReceiptTermCondition();
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

    // START OF STATIC METHODS	//
 
 static function GetAllTermConditionMessage()
	{
		$AllTermConditionMessage = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT afrtc.* FROM afm_fee_receipt_terms_conditions afrtc
												ORDER BY afrtc.feeReceiptTermConditionID;');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllTermConditionMessage; 
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$AllTermConditionMessage[$SearchRow->feeReceiptTermConditionID] = $SearchRow->termConditionMessage;

			}
			
			return $AllTermConditionMessage;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at FeeReceiptTermCondition::GetAllTermConditionMessage(). Stack Trace: ' . $e->getTraceAsString());
			return $AllTermConditionMessage;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at FeeReceiptTermCondition::GetAllTermConditionMessage(). Stack Trace: ' . $e->getTraceAsString());
			return $AllTermConditionMessage;
		}
	}
	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//

	private function SaveDetails()
	{ 
		if ($this->FeeReceiptTermConditionID == 0)
		{
			$DeleteTermConditionMessage = $this->DBObject->Prepare('DELETE FROM afm_fee_receipt_terms_conditions;');
		
			$DeleteTermConditionMessage->Execute();

			foreach ($this->TermConditionMessageList as $Counter => $TermConditionMessage) 
			{
				$RSSaveTermConditionMessage = $this->DBObject->Prepare('INSERT INTO afm_fee_receipt_terms_conditions (termConditionMessage, createUserID, createDate)
																VALUES (:|1, :|2, NOW());');
				
				$RSSaveTermConditionMessage->Execute($TermConditionMessage, $this->CreateUserID);			
			}
		}

		return true;
		
	}

	private function RemoveFeeReceiptTermCondition()
    {
        if(!isset($this->FeeReceiptTermConditionID)) 
        {
            throw new ApplicationARException('', APP_AR_ERROR_DELETE_WITHOUT_ID);
        }
        
        $RSDeleteFeeReceiptTermCondition = $this->DBObject->Prepare('DELETE FROM afm_fee_receipt_terms_conditions WHERE feeReceiptTermConditionID = :|1 LIMIT 1;');
        $RSDeleteFeeReceiptTermCondition->Execute($this->FeeReceiptTermConditionID); 

        return true;               
    }

    private function RemoveConditionMessageList()
    {
        if(!isset($this->FeeReceiptTermConditionID)) 
        {
            throw new ApplicationARException('', APP_AR_ERROR_DELETE_WITHOUT_ID);
        }
        
        $RSDeleteFeeReceiptTermCondition = $this->DBObject->Prepare('DELETE FROM afm_fee_receipt_terms_conditions WHERE feeReceiptTermConditionID = :|1 LIMIT 1;');
        $RSFeeReceiptTermCondition = $this->DBObject->Prepare('DELETE FROM afm_fee_receipt_terms_conditions WHERE termConditionMessage = :2 LIMIT 2;');

        $RSDeleteFeeReceiptTermCondition->Execute($this->FeeReceiptTermConditionID);

        $RSDeleteFeeTermConditionMessage->Execute($this->TermConditionMessage);

        return true;               
    }


	private function GetFeeReceiptTermConditionByID()
	{
		$RSFeeReceiptTermCondition = $this->DBObject->Prepare('SELECT * FROM afm_fee_receipt_terms_conditions WHERE feeReceiptTermConditionID = :|1;');
		$RSFeeReceiptTermCondition->Execute($this->FeeReceiptTermConditionID);
		
		$FeeReceiptTermConditionRow = $RSFeeReceiptTermCondition->FetchRow();
		
		$this->SetAttributesFromDB($FeeReceiptTermConditionRow);				
	}
	
	private function SetAttributesFromDB($FeeReceiptTermConditionRow)
	{
		$this->FeeReceiptTermConditionID = $FeeReceiptTermConditionRow->feeReceiptTermConditionID;
		$this->TermConditionMessage = $FeeReceiptTermConditionRow->termConditionMessage;

		$this->CreateUserID = $FeeReceiptTermConditionRow->createUserID;
		$this->CreateDate = $FeeReceiptTermConditionRow->createDate;

	}	
}
?>
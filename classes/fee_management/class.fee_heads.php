<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.db_connect.php');
error_reporting(E_ALL);

class FeeHead
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	
	private $FeeHeadID;
	private $FeeHead;

	private $FeeHeadDescription;
	private $IsSystemGenerated;

	private $Priority;
	private $IsActive;

	private $CreateUserID;
	private $CreateDate;
	
	// PUBLIC METHODS START HERE	//
	public function __construct($FeeHeadID = 0)
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;
		
		if($FeeHeadID != 0)
		{
			$this->FeeHeadID = $FeeHeadID;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetFeeHeadByID();
		}
		else
		{
			//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
			$this->FeeHeadID = 0;
			$this->FeeHead = '';

			$this->FeeHeadDescription = '';
			$this->IsSystemGenerated = 0;

			$this->Priority = 0;
			$this->IsActive = 0;

			$this->CreateUserID = 0;
			$this->CreateDate = '0000-00-00 00:00:00';
		}
	}
	
	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetFeeHeadID()
	{
		return $this->FeeHeadID;
	}
	
	public function GetFeeHead()
	{
		return $this->FeeHead;
	}
	public function SetFeeHead($FeeHead)
	{
		$this->FeeHead = $FeeHead;
	}

	public function GetFeeHeadDescription()
	{
		return $this->FeeHeadDescription;
	}
	public function SetFeeHeadDescription($FeeHeadDescription)
	{
		$this->FeeHeadDescription = $FeeHeadDescription;
	}

	public function GetIsSystemGenerated()
	{
		return $this->IsSystemGenerated;
	}
	public function SetIsSystemGenerated($IsSystemGenerated)
	{
		$this->IsSystemGenerated = $IsSystemGenerated;
	}

	public function GetPriority()
	{
		return $this->Priority;
	}
	public function SetPriority($Priority)
	{
		$this->Priority = $Priority;
	}

	public function GetIsActive()
	{
		return $this->IsActive;
	}
	public function SetIsActive($IsActive)
	{
		$this->IsActive = $IsActive;
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
            $this->RemoveFeeHead();
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
            $RSFeeHeadCount = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM afm_fee_structure_details WHERE feeHeadID = :|1;');
            $RSFeeHeadCount->Execute($this->FeeHeadID);

            if ($RSFeeHeadCount->FetchRow()->totalRecords > 0) 
            {
                return true;
            }

            return false;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at FeeHead::CheckDependencies(). Stack Trace: ' . $e->getTraceAsString());
            return true;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: ApplicationDBException at FeeHead::CheckDependencies(). Stack Trace: ' . $e->getTraceAsString());
            return true;
        }       
    }
		
	// END OF PUBLIC METHODS	//
	
	// START OF STATIC METHODS	//
	static function GetAllFeeHeads()
	{
		$AllFeeHeads = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT afh.*, u.userName AS createUserName FROM afm_fee_heads afh
												INNER JOIN users u ON afh.createUserID = u.userID 
												ORDER BY afh.priority;');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllFeeHeads; 
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$AllFeeHeads[$SearchRow->feeHeadID]['FeeHead'] = $SearchRow->feeHead;

				$AllFeeHeads[$SearchRow->feeHeadID]['FeeHeadDescription'] = $SearchRow->feeHeadDescription;
				$AllFeeHeads[$SearchRow->feeHeadID]['IsSystemGenerated'] = $SearchRow->isSystemGenerated;

				$AllFeeHeads[$SearchRow->feeHeadID]['Priority'] = $SearchRow->priority;
				$AllFeeHeads[$SearchRow->feeHeadID]['IsActive'] = $SearchRow->isActive;

				$AllFeeHeads[$SearchRow->feeHeadID]['CreateUserID'] = $SearchRow->createUserID;
				$AllFeeHeads[$SearchRow->feeHeadID]['CreateUserName'] = $SearchRow->createUserName;
				$AllFeeHeads[$SearchRow->feeHeadID]['CreateDate'] = $SearchRow->createDate;
			}
			
			return $AllFeeHeads;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at FeeHead::GetAllFeeHeads(). Stack Trace: ' . $e->getTraceAsString());
			return $AllFeeHeads;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at FeeHead::GetAllFeeHeads(). Stack Trace: ' . $e->getTraceAsString());
			return $AllFeeHeads;
		}
	}

	static function GetActiveFeeHeads()
	{
		$ActiveFeeHeads = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT * FROM afm_fee_heads WHERE isActive = 1;');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $ActiveFeeHeads;
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$ActiveFeeHeads[$SearchRow->feeHeadID]['FeeHead'] = $SearchRow->feeHead;
				$ActiveFeeHeads[$SearchRow->feeHeadID]['IsSystemGenerated'] = $SearchRow->isSystemGenerated;
			}
			
			return $ActiveFeeHeads;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at FeeHead::GetActiveFeeHeads(). Stack Trace: ' . $e->getTraceAsString());
			return $ActiveFeeHeads;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at FeeHead::GetActiveFeeHeads(). Stack Trace: ' . $e->getTraceAsString());
			return $ActiveFeeHeads;
		}		
	}
		
	// END OF PUBLIC METHODS	//
	
	// START OF STATIC METHODS	//
	
	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//
	private function SaveDetails()
	{
		if ($this->FeeHeadID == 0)
		{
			$RSSave = $this->DBObject->Prepare('INSERT INTO afm_fee_heads (feeHead, feeHeadDescription, isSystemGenerated, priority, isActive,
														createUserID, createDate)
														VALUES (:|1, :|2, :|3, :|4, :|5, :|6, NOW());');
		
			$RSSave->Execute($this->FeeHead, $this->FeeHeadDescription, $this->IsSystemGenerated, $this->Priority, $this->IsActive, 
							$this->CreateUserID);
			
			$this->FeeHeadID = $RSSave->LastID;
		}
		else
		{
			$RSUpdate = $this->DBObject->Prepare('UPDATE afm_fee_heads
													SET	feeHead = :|1,
														feeHeadDescription = :|2,
														isSystemGenerated = :|3,
														priority = :|4,
														isActive = :|5
													WHERE feeHeadID = :|6 LIMIT 1;');
													
			$RSUpdate->Execute($this->FeeHead, $this->FeeHeadDescription, $this->IsSystemGenerated, $this->Priority, $this->IsActive, $this->FeeHeadID);
		}
		
		return true;
	}

	private function RemoveFeeHead()
    {
        if(!isset($this->FeeHeadID)) 
        {
            throw new ApplicationARException('', APP_AR_ERROR_DELETE_WITHOUT_ID);
        }
        
        $RSDeleteFeeHead = $this->DBObject->Prepare('DELETE FROM afm_fee_heads WHERE feeHeadID = :|1 LIMIT 1;');
        $RSDeleteFeeHead->Execute($this->FeeHeadID);  

        return true;              
    }
	
	private function GetFeeHeadByID()
	{
		$RSFeeHead = $this->DBObject->Prepare('SELECT * FROM afm_fee_heads WHERE feeHeadID = :|1 LIMIT 1;');
		$RSFeeHead->Execute($this->FeeHeadID);
		
		$FeeHeadRow = $RSFeeHead->FetchRow();
		
		$this->SetAttributesFromDB($FeeHeadRow);				
	}
	
	private function SetAttributesFromDB($FeeHeadRow)
	{
		$this->FeeHeadID = $FeeHeadRow->feeHeadID;
		$this->FeeHead = $FeeHeadRow->feeHead;

		$this->FeeHeadDescription = $FeeHeadRow->feeHeadDescription;
		$this->IsSystemGenerated = $FeeHeadRow->isSystemGenerated;

		$this->Priority = $FeeHeadRow->priority;
		$this->IsActive = $FeeHeadRow->isActive;

		$this->CreateUserID = $FeeHeadRow->createUserID;
		$this->CreateDate = $FeeHeadRow->createDate;
	}	
}
?>
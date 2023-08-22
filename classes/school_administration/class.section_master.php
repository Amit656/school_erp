<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.db_connect.php');
error_reporting(E_ALL);

class SectionMaster
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	
	private $SectionMasterID;
	private $SectionName;

	private $IsActive;
	private $CreateUserID;
	private $CreateDate;
	
	// PUBLIC METHODS START HERE	//
	public function __construct($SectionMasterID = 0)
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;
		
		if($SectionMasterID != 0)
		{
			$this->SectionMasterID = $SectionMasterID;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetSectionMasterByID();
		}
		else
		{
			//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
			$this->SectionMasterID = 0;
			$this->SectionName = '';
			
			$this->IsActive = 0;
			$this->CreateUserID = 0;
  			$this->CreateDate = '0000-00-00 00:00:00';
		}
	}
	
	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetSectionMasterID()
	{
		return $this->SectionMasterID;
	}
	
	public function GetSectionName()
	{
		return $this->SectionName;
	}
	public function SetSectionName($SectionName)
	{
		$this->SectionName = $SectionName;
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
            $this->RemoveSectionMaster();
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
            $RSCount = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM asa_class_sections WHERE sectionMasterID = :|1;');
            $RSCount->Execute($this->SectionMasterID);
    
            if ($RSCount->FetchRow()->totalRecords > 0) 
            {
                return true;
            }
            
            return false;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException: SectionMaster::CheckDependencies. Stack Trace:' . $e->getTraceAsString());
            return false;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception: SectionMaster::CheckDependencies. Stack Trace:' . $e->getTraceAsString());
            return false;
        }       
    }

    public function RecordExists()
	{
		try
		{
			$QueryString = '';

			if ($this->SectionMasterID > 0)
			{
				$QueryString = ' AND sectionMasterID != ' . $this->DBObject->RealEscapeVariable($this->SectionMasterID);
			}

			$RSTotal = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM asa_section_master WHERE sectionName = :|1' . $QueryString . ';');
			$RSTotal->Execute($this->SectionName);
			
			if ($RSTotal->FetchRow()->totalRecords > 0)
			{
				return true;
			}
			
			return false;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at SectionMaster::RecordExists(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at SectionMaster::RecordExists(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
	}

	// END OF PUBLIC METHODS	//
	
	// START OF STATIC METHODS	//

	static function GetAllSectionMasters($GetOnlyNames = false)
    {
        $AllSectionMasters = array();

        try
        {
            $DBConnObject = new DBConnect();

            $RSSearch = $DBConnObject->Prepare('SELECT asm.*, u.userName AS createUserName FROM asa_section_master asm 
												INNER JOIN users u ON asm.createUserID = u.userID
												ORDER BY asm.sectionMasterID;');
            $RSSearch->Execute();

            if ($RSSearch->Result->num_rows <= 0)
            {
                return $AllSectionMasters;
			}
			
			if ($GetOnlyNames)
			{
				while($SearchRow = $RSSearch->FetchRow())
				{
					$AllSectionMasters[$SearchRow->sectionMasterID] = $SearchRow->sectionName;
				}

				return $AllSectionMasters;
			}

            while($SearchRow = $RSSearch->FetchRow())
            {
                $AllSectionMasters[$SearchRow->sectionMasterID]['SectionName'] = $SearchRow->sectionName;
				$AllSectionMasters[$SearchRow->sectionMasterID]['IsActive'] = $SearchRow->isActive;
				
				$AllSectionMasters[$SearchRow->sectionMasterID]['CreateUserID'] = $SearchRow->createUserID;
				$AllSectionMasters[$SearchRow->sectionMasterID]['CreateUserName'] = $SearchRow->createUserName;
				
		        $AllSectionMasters[$SearchRow->sectionMasterID]['CreateDate'] = $SearchRow->createDate;
            }

            return $AllSectionMasters;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at SectionMaster::GetAllSectionMasters(). Stack Trace: '.$e->getTraceAsString());
            return $AllSectionMasters;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception at SectionMaster::GetAllSectionMasters(). Stack Trace: '.$e->getTraceAsString());
            return $AllSectionMasters;
        }
    }

    static function GetActiveSectionMasters()
	{
		$AllSectionMasters = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT sectionMasterID, sectionName FROM asa_section_master WHERE isActive = 1;');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllSectionMasters;
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$AllSectionMasters[$SearchRow->sectionMasterID] = $SearchRow->sectionName;
			}
			
			return $AllSectionMasters;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at SectionMaster::GetActiveSectionMasters(). Stack Trace: ' . $e->getTraceAsString());
			return $AllSectionMasters;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at SectionMaster::GetActiveSectionMasters(). Stack Trace: ' . $e->getTraceAsString());
			return $AllSectionMasters;
		}		
	}

	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//
	private function SaveDetails()
	{
		if ($this->SectionMasterID == 0)
		{
			$RSSave = $this->DBObject->Prepare('INSERT INTO asa_section_master (sectionName, isActive, createUserID, createDate)
														VALUES (:|1, :|2, :|3, NOW());');
		
			$RSSave->Execute($this->SectionName, $this->IsActive, $this->CreateUserID);
			
			$this->SectionMasterID = $RSSave->LastID;
		}
		else
		{
			$RSUpdate = $this->DBObject->Prepare('UPDATE asa_section_master
													SET	sectionName = :|1,
														isActive = :|2
													WHERE sectionMasterID = :|3 LIMIT 1;');
													
			$RSUpdate->Execute($this->SectionName, $this->IsActive, $this->SectionMasterID);
		}
		
		return true;
	}

	private function RemoveSectionMaster()
    {
        if(!isset($this->SectionMasterID)) 
        {
            throw new ApplicationARException('', APP_AR_ERROR_DELETE_WITHOUT_ID);
        }
        
        $RSDeleteTaskGroup = $this->DBObject->Prepare('DELETE FROM asa_section_master WHERE sectionMasterID = :|1 LIMIT 1;');
        $RSDeleteTaskGroup->Execute($this->SectionMasterID);                
    }
	
	private function GetSectionMasterByID()
	{
		$RSSectionMaster = $this->DBObject->Prepare('SELECT * FROM asa_section_master WHERE sectionMasterID = :|1 LIMIT 1;');
		$RSSectionMaster->Execute($this->SectionMasterID);
		
		$SectionMasterRow = $RSSectionMaster->FetchRow();
		
		$this->SetAttributesFromDB($SectionMasterRow);				
	}
	
	private function SetAttributesFromDB($SectionMasterRow)
	{
		$this->SectionMasterID = $SectionMasterRow->sectionMasterID;
		$this->SectionName = $SectionMasterRow->sectionName;

		$this->IsActive = $SectionMasterRow->isActive;
		$this->CreateUserID = $SectionMasterRow->createUserID;
  		$this->CreateDate = $SectionMasterRow->createDate;
	}	
}
?>
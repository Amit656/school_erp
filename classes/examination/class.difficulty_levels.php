<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.db_connect.php');
error_reporting(E_ALL);

class DifficultyLevel
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	
	private $DifficultyLevelID;
	private $DifficultyLevel;
	private $IsActive;
	
	// PUBLIC METHODS START HERE	//
	public function __construct($DifficultyLevelID = 0)
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;
		
		if($DifficultyLevelID != 0)
		{
			$this->DifficultyLevelID = $DifficultyLevelID;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetDifficultyLevelByID();
		}
		else
		{
			//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
			$this->DifficultyLevelID = 0;
			$this->DifficultyLevel = '';
			$this->IsActive = 0;
		}
	}
	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetDifficultyLevelID()
	{
		return $this->DifficultyLevelID;
	}
	
	public function GetDifficultyLevel()
	{
		return $this->DifficultyLevel;
	}
	public function SetDifficultyLevel($DifficultyLevel)
	{
		$this->DifficultyLevel = $DifficultyLevel;
	}

	public function GetIsActive()
	{
		return $this->IsActive;
	}
	public function SetIsActive($IsActive)
	{
		$this->IsActive = $IsActive;
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
            $this->RemoveDifficultyLevel();
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
            $RSDifficultyLevelCount = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM aem_questions WHERE difficultyLevelID = :|1;');
            $RSDifficultyLevelCount->Execute($this->DifficultyLevelID);

            if ($RSDifficultyLevelCount->FetchRow()->totalRecords > 0) 
            {
                return true;
            }

            return false;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at DifficultyLevel::CheckDependencies(). Stack Trace: ' . $e->getTraceAsString());
            return true;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: ApplicationDBException at DifficultyLevel::CheckDependencies(). Stack Trace: ' . $e->getTraceAsString());
            return true;
        }       
    }
	// END OF PUBLIC METHODS	//
	
	// START OF STATIC METHODS	//
	static function GetActiveDifficultyLevel()
	{
		$ActiveDifficultyLevel = array();
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT * FROM aem_difficulty_levels WHERE isActive = 1;');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $ActiveDifficultyLevel;
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$ActiveDifficultyLevel[$SearchRow->difficultyLevelID] = $SearchRow->difficultyLevel;
			}
			
			return $ActiveDifficultyLevel;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at ActiveDifficultyLevel::GetActiveDifficultyLevel(). Stack Trace: ' . $e->getTraceAsString());
			return $ActiveDifficultyLevel;
		}
		catch (Exception $e) 
		{
			error_log('DEBUG: Exception at ActiveDifficultyLevel::GetActiveDifficultyLevel(). Stack Trace: ' . $e->getTraceAsString());
			return $ActiveDifficultyLevel;
		}		
	}

	static function GetAllDifficultyLevel()
	{
		$AllDifficultyLevel = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT adl.* FROM aem_difficulty_levels adl
												ORDER BY adl.difficultyLevel;');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllDifficultyLevel; 
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$AllDifficultyLevel[$SearchRow->difficultyLevelID]['DifficultyLevel'] = $SearchRow->difficultyLevel;
				$AllDifficultyLevel[$SearchRow->difficultyLevelID]['IsActive'] = $SearchRow->isActive;
			}
			
			return $AllDifficultyLevel;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at DifficultyLevel::GetAllDifficultyLevel(). Stack Trace: ' . $e->getTraceAsString());
			return $AllDifficultyLevel;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at DifficultyLevel::GetAllDifficultyLevel(). Stack Trace: ' . $e->getTraceAsString());
			return $AllDifficultyLevel;
		}
	}
	
	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//
	private function SaveDetails()
	{
		if ($this->DifficultyLevelID == 0)
		{
			$RSSave = $this->DBObject->Prepare('INSERT INTO aem_difficulty_levels (difficultyLevel, isActive)
														VALUES (:|1, :|2);');
		
			$RSSave->Execute($this->DifficultyLevel, $this->IsActive);
			
			$this->DifficultyLevelID = $RSSave->LastID;
		}
		else
		{
			$RSUpdate = $this->DBObject->Prepare('UPDATE aem_difficulty_levels
													SET	difficultyLevel = :|1,
														isActive = :|2
													WHERE difficultyLevelID = :|3 LIMIT 1;');
													
			$RSUpdate->Execute($this->DifficultyLevel, $this->IsActive, $this->DifficultyLevelID);
		}
		
		return true;
	}
	
	private function RemoveDifficultyLevel()
    {
        if(!isset($this->DifficultyLevelID)) 
        {
            throw new ApplicationARException('', APP_AR_ERROR_DELETE_WITHOUT_ID);
        }
        
        $RSDeleteDifficultyLevel = $this->DBObject->Prepare('DELETE FROM aem_difficulty_levels WHERE difficultyLevelID = :|1 LIMIT 1;');
        $RSDeleteDifficultyLevel->Execute($this->DifficultyLevelID); 

        return true;               
    }
	
	private function GetDifficultyLevelByID()
	{
		$RSDifficultyLevel = $this->DBObject->Prepare('SELECT * FROM aem_difficulty_levels WHERE difficultyLevelID = :|1 LIMIT 1;');
		$RSDifficultyLevel->Execute($this->DifficultyLevelID);
		
		$DifficultyLevelRow = $RSDifficultyLevel->FetchRow();
		
		$this->SetAttributesFromDB($DifficultyLevelRow);				
	}
	
	private function SetAttributesFromDB($DifficultyLevelRow)
	{
		$this->DifficultyLevelID = $DifficultyLevelRow->difficultyLevelID;
		$this->DifficultyLevel = $DifficultyLevelRow->difficultyLevel;
		$this->IsActive = $DifficultyLevelRow->isActive;
	}	
}
?>
<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.db_connect.php');
error_reporting(E_ALL);

class AchievementMaster
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	
	private $AchievementMasterID;
	private $Achievement;
	private $AchievementDetails;

	private $IsActive;
	private $CreateUserID;
	private $CreateDate;
	
	// PUBLIC METHODS START HERE	//
	public function __construct($AchievementMasterID = 0)
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;
		
		if($AchievementMasterID != 0)
		{
			$this->AchievementMasterID = $AchievementMasterID;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetAchievementMasterByID();
		}
		else
		{
			//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
			$this->AchievementMasterID = 0;
			$this->Achievement = '';
			$this->AchievementDetails = '';

			$this->IsActive = 0;
			$this->CreateUserID = 0;
			$this->CreateDate = '0000-00-00 00:00:00';
		}
	}
	
	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetAchievementMasterID()
	{
		return $this->AchievementMasterID;
	}
	
	public function GetAchievement()
	{
		return $this->Achievement;
	}
	public function SetAchievement($Achievement)
	{
		$this->Achievement = $Achievement;
	}

	public function GetAchievementDetails()
	{
		return $this->AchievementDetails;
	}
	public function SetAchievementDetails($AchievementDetails)
	{
		$this->AchievementDetails = $AchievementDetails;
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
            $this->RemoveAchievementMaster();
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
            $RSAchievementMasterCount = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM aas_achievements_students WHERE achievementID = :|1;');
            $RSAchievementMasterCount->Execute($this->AchievementMasterID);

            if ($RSAchievementMasterCount->FetchRow()->totalRecords > 0) 
            {
                return true;
            }

            return false;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at AchievementMaster::CheckDependencies(). Stack Trace: ' . $e->getTraceAsString());
            return true;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: ApplicationDBException at AchievementMaster::CheckDependencies(). Stack Trace: ' . $e->getTraceAsString());
            return true;
        }       
    }
		
	// END OF PUBLIC METHODS	//
	
	// START OF STATIC METHODS	//
	
	static function GetAllAchievementMasters()
	{
		$AllAchievementMasters = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT asa.*, u.userName AS createUserName FROM aas_achievements_master asa
												INNER JOIN users u ON asa.createUserID = u.userID 
												ORDER BY asa.achievement;');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllAchievementMasters; 
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$AllAchievementMasters[$SearchRow->achievementMasterID]['Achievement'] = $SearchRow->achievement;
				$AllAchievementMasters[$SearchRow->achievementMasterID]['AchievementDetails'] = $SearchRow->achievementDetails;

				$AllAchievementMasters[$SearchRow->achievementMasterID]['IsActive'] = $SearchRow->isActive;

				$AllAchievementMasters[$SearchRow->achievementMasterID]['CreateUserID'] = $SearchRow->createUserID;
				$AllAchievementMasters[$SearchRow->achievementMasterID]['CreateUserName'] = $SearchRow->createUserName;
				$AllAchievementMasters[$SearchRow->achievementMasterID]['CreateDate'] = $SearchRow->createDate;
			}
			
			return $AllAchievementMasters;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at AchievementMaster::GetAllAchievementMasters(). Stack Trace: ' . $e->getTraceAsString());
			return $AllAchievementMasters;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at AchievementMaster::GetAllAchievementMasters(). Stack Trace: ' . $e->getTraceAsString());
			return $AllAchievementMasters;
		}
	}

	static function GetActiveAchievementMasters($GetNameonly = true)
	{
		$ActiveAchievementMasters = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT * FROM aas_achievements_master WHERE isActive = 1;');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $ActiveAchievementMasters;
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{	
				if ($GetNameonly) 
				{
					$ActiveAchievementMasters[$SearchRow->achievementMasterID] = $SearchRow->achievement;
					continue;
				}
				
				$ActiveAchievementMasters[$SearchRow->achievementMasterID]['Achievement'] = $SearchRow->achievement;
				$ActiveAchievementMasters[$SearchRow->achievementMasterID]['AchievementDetails'] = $SearchRow->achievementDetails;			
			}
			
			return $ActiveAchievementMasters;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at AchievementMaster::GetActiveAchievementMasters(). Stack Trace: ' . $e->getTraceAsString());
			return $ActiveAchievementMasters;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at AchievementMaster::GetActiveAchievementMasters(). Stack Trace: ' . $e->getTraceAsString());
			return $ActiveAchievementMasters;
		}		
	}
	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//
	private function SaveDetails()
	{
		if ($this->AchievementMasterID == 0)
		{
			$RSSave = $this->DBObject->Prepare('INSERT INTO aas_achievements_master (achievement, achievementDetails, isActive, createUserID, createDate)
														VALUES (:|1, :|2, :|3, :|4, NOW());');
		
			$RSSave->Execute($this->Achievement, $this->AchievementDetails, $this->IsActive, $this->CreateUserID);
			
			$this->AchievementMasterID = $RSSave->LastID;
		}
		else
		{
			$RSUpdate = $this->DBObject->Prepare('UPDATE aas_achievements_master
													SET	achievement = :|1,
														achievementDetails = :|2,
														isActive = :|3
													WHERE achievementMasterID = :|4;');
													
			$RSUpdate->Execute($this->Achievement, $this->AchievementDetails, $this->IsActive, $this->AchievementMasterID);
		}
		
		return true;
	}

	private function RemoveAchievementMaster()
    {
        if(!isset($this->AchievementMasterID)) 
        {
            throw new ApplicationARException('', APP_AR_ERROR_DELETE_WITHOUT_ID);
        }
        
        $RSDeleteAchievementMaster = $this->DBObject->Prepare('DELETE FROM aas_achievements_master WHERE achievementMasterID = :|1 LIMIT 1;');
        $RSDeleteAchievementMaster->Execute($this->AchievementMasterID);  

        return true;              
    }
	
	private function GetAchievementMasterByID()
	{
		$RSAchievementMaster = $this->DBObject->Prepare('SELECT * FROM aas_achievements_master WHERE achievementMasterID = :|1 LIMIT 1;');
		$RSAchievementMaster->Execute($this->AchievementMasterID);
		
		$AchievementMasterRow = $RSAchievementMaster->FetchRow();
		
		$this->SetAttributesFromDB($AchievementMasterRow);				
	}
	
	private function SetAttributesFromDB($AchievementMasterRow)
	{
		$this->AchievementMasterID = $AchievementMasterRow->achievementMasterID;
		$this->Achievement = $AchievementMasterRow->achievement;
		$this->AchievementDetails = $AchievementMasterRow->achievementDetails;

		$this->IsActive = $AchievementMasterRow->isActive;
		$this->CreateUserID = $AchievementMasterRow->createUserID;
		$this->CreateDate = $AchievementMasterRow->createDate;
	}	
}
?>
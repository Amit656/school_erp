<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.db_connect.php');
error_reporting(E_ALL);

class AreaMaster
{
	// CLASS MEMBERS ARE DEFINED HERE	AreaID//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	
	private $AreaID;
	private $AreaName;
	private $IsActive;
	private $createUserID;
	private $CreateDate;
	
	// PUBLIC METHODS START HERE	//
	public function __construct($AreaID = 0)
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;
		
		if($AreaID != 0)
		{
			$this->AreaID = $AreaID;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetAreaByID();
		}
		else
		{
			//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
			$this->AreaID = 0;
			$this->AreaName = '';
			$this->IsActive = 0;
			$this->CreateUserID = 0;
			$this->CreateDate = '0000-00-00 00:00:00';
		}
	}
	
	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetAreaID()
	{
		return $this->AreaID;
	}
	
	public function GetAreaName()
	{
		return $this->AreaName;
	}
	public function SetAreaName($AreaName)
	{
		$this->AreaName = $AreaName;
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
            $this->RemoveAreaMaster();
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
	
	public function RecordExists()
	{
		try
		{
			$QueryString = '';
			
			if ($this->AreaID > 0)
			{
				$QueryString = ' AND areaID != ' . $this->DBObject->RealEscapeVariable($this->AreaID);
			}

			$RSTotal = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM atm_area_master WHERE areaName = :|1' . $QueryString . ';');
			$RSTotal->Execute($this->AreaName);
			
			if ($RSTotal->FetchRow()->totalRecords > 0)
			{
				return true;
			}
			
			return false;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at AreaMaster::RecordExists(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at AreaMaster::RecordExists(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
	}

	// END OF PUBLIC METHODS	//
	
	// START OF STATIC METHODS	//
	static function GetActiveArea()
	{
		$ActiveArea = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT * FROM atm_area_master WHERE isActive = 1;');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $ActiveArea;
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$ActiveArea[$SearchRow->areaID] = $SearchRow->areaName;
			}
			
			return $ActiveArea;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at AreaMaster::GetActiveArea(). Stack Trace: ' . $e->getTraceAsString());
			return $ActiveAreaMaster;
		}
		catch (Exception $e) 
		{
			error_log('DEBUG: Exception at AreaMaster::GetActiveArea(). Stack Trace: ' . $e->getTraceAsString());
			return $ActiveArea;
		}		
	}

	static function GetAllArea()
	 {
			$AllArea = array();
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT aam.*, u.userName AS createUserName FROM atm_area_master aam
												INNER JOIN users u ON aam.createUserID = u.userID 
												ORDER BY aam.areaID;');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllArea; 
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$AllArea[$SearchRow->areaID]['AreaName'] = $SearchRow->areaName;
				$AllArea[$SearchRow->areaID]['IsActive'] = $SearchRow->isActive;

				$AllArea[$SearchRow->areaID]['CreateUserID'] = $SearchRow->createUserID;
				$AllArea[$SearchRow->areaID]['CreateUserName'] = $SearchRow->createUserName;
				$AllArea[$SearchRow->areaID]['CreateDate'] = $SearchRow->createDate;

			}
			
			return $AllArea;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at AreaMaster::GetAllArea(). Stack Trace: ' . $e->getTraceAsString());
			return $AllArea;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at AreaMaster::GetAllArea(). Stack Trace: ' . $e->getTraceAsString());
			return $AllArea;
		}
	}
	
	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//
	private function SaveDetails()
	{
		if ($this->AreaID == 0)
		{
			$RSSave = $this->DBObject->Prepare('INSERT INTO atm_area_master (areaName, isActive, createUserID, createDate)
														VALUES (:|1, :|2, :|3, NOW());');
		
			$RSSave->Execute($this->AreaName, $this->IsActive, $this->CreateUserID);
			
			$this->AreaID = $RSSave->LastID;
		}
		else
		{
			$RSUpdate = $this->DBObject->Prepare('UPDATE atm_area_master
													SET	areaName = :|1,
													isActive = :|2
													WHERE areaID = :|3;');
													
			$RSUpdate->Execute($this->AreaName, $this->IsActive, $this->AreaID);
		}
		
		return true;
	}

	 private function RemoveAreaMaster()
    {
        if(!isset($this->AreaID)) 
        {
            throw new ApplicationARException('', APP_AR_ERROR_DELETE_WITHOUT_ID);
        }
        
        $RSDeleteArea = $this->DBObject->Prepare('DELETE FROM atm_area_master WHERE areaID = :|1 LIMIT 1;');
        $RSDeleteArea->Execute($this->AreaID); 

        return true;               
    }
	
	private function GetAreaByID()
	{
		$RArea = $this->DBObject->Prepare('SELECT * FROM atm_area_master WHERE areaID = :|1;');
		$RArea->Execute($this->AreaID);
		
		$AreaRow = $RArea->FetchRow();
		
		$this->SetAttributesFromDB($AreaRow);				
	}
	
	private function SetAttributesFromDB($AreaRow)
	{
		$this->AreaID = $AreaRow->areaID;
		$this->AreaName = $AreaRow->areaName;

		$this->IsActive = $AreaRow->isActive;
		$this->CreateUserID = $AreaRow->createUserID;
		$this->CreateDate = $AreaRow->createDate;

	}	
}
?>
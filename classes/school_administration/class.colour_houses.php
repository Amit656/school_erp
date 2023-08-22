<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.db_connect.php');
error_reporting(E_ALL);

class ColourHouse
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	
	private $ColourHouseID;
	private $HouseName;
	private $HouseColour;

	private $IsActive;
	private $CreateUserID;
	private $CreateDate;
	
	// PUBLIC METHODS START HERE	//
	public function __construct($ColourHouseID = 0)
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;
		
		if($ColourHouseID != 0)
		{
			$this->ColourHouseID = $ColourHouseID;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetColourHouseByID();
		}
		else
		{
			//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
			$this->ColourHouseID = 0;
			$this->HouseName = '';
			$this->HouseColour = '';

			$this->IsActive = 0;
			$this->CreateUserID = 0;
			$this->CreateDate = '0000-00-00 00:00:00';
		}
	}
	
	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetColourHouseID()
	{
		return $this->ColourHouseID;
	}
	
	public function GetHouseName()
	{
		return $this->HouseName;
	}
	public function SetHouseName($HouseName)
	{
		$this->HouseName = $HouseName;
	}

	public function GetHouseColour()
	{
		return $this->HouseColour;
	}
	public function SetHouseColour($HouseColour)
	{
		$this->HouseColour = $HouseColour;
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
            $this->RemoveColourHouse();
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
            $RSColourHouseInStudentsCount = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM asa_students WHERE colourHouseID = :|1;');
            $RSColourHouseInStudentsCount->Execute($this->ColourHouseID);

            if ($RSColourHouseInStudentsCount->FetchRow()->totalRecords > 0) 
            {
                return true;
            }

            return false;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at ColourHouse::CheckDependencies(). Stack Trace: ' . $e->getTraceAsString());
            return false;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: ApplicationDBException at ColourHouse::CheckDependencies(). Stack Trace: ' . $e->getTraceAsString());
            return false;
        }       
    }

    public function RecordExists()
	{
		try
		{
			$QueryString = '';
			if ($this->ColourHouseID > 0)
			{
				$QueryString = ' AND colourHouseID != ' . $this->DBObject->RealEscapeVariable($this->ColourHouseID);
			}

			$RSTotal = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM asa_colour_houses WHERE houseName = :|1' . $QueryString . ';');
			$RSTotal->Execute($this->HouseName);
			
			if ($RSTotal->FetchRow()->totalRecords > 0)
			{
				return true;
			}
			
			return false;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at ColourHouse::RecordExists(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at ColourHouse::RecordExists(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
	}
		
	// END OF PUBLIC METHODS	//
	
	// START OF STATIC METHODS	//
	static function GetAllColourHouses()
	{
		$AllColourHouses = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT ach.*, u.userName AS createUserName FROM asa_colour_houses ach 
												INNER JOIN users u ON ach.createUserID = u.userID
												ORDER BY ach.colourHouseID;');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllColourHouses;
			}
			
			while ($SearchRow = $RSSearch->FetchRow())
			{
				$AllColourHouses[$SearchRow->colourHouseID]['HouseName'] = $SearchRow->houseName;
				$AllColourHouses[$SearchRow->colourHouseID]['HouseColour'] = $SearchRow->houseColour;
				$AllColourHouses[$SearchRow->colourHouseID]['IsActive'] = $SearchRow->isActive;

				$AllColourHouses[$SearchRow->colourHouseID]['CreateUserID'] = $SearchRow->createUserID;
				$AllColourHouses[$SearchRow->colourHouseID]['CreateUserName'] = $SearchRow->createUserName;
				
				$AllColourHouses[$SearchRow->colourHouseID]['CreateDate'] = $SearchRow->createDate;
			}
			
			return $AllColourHouses;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at ColourHouse::GetAllColourHouses(). Stack Trace: '.$e->getTraceAsString());
			return $AllColourHouses;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at ColourHouse::GetAllColourHouses(). Stack Trace: '.$e->getTraceAsString());
			return $AllColourHouses;
		}
	}

	static function GetActiveColourHouses()
	{
		$AllColourHouses = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT * FROM asa_colour_houses ach WHERE isActive = 1;');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllColourHouses;
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$AllColourHouses[$SearchRow->colourHouseID] = $SearchRow->houseName;
			}
			
			return $AllColourHouses;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at ColourHouse::GetActiveColourHouses(). Stack Trace: ' . $e->getTraceAsString());
			return $AllColourHouses;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at ColourHouse::GetActiveColourHouses(). Stack Trace: ' . $e->getTraceAsString());
			return $AllColourHouses;
		}		
	}

	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//
	private function SaveDetails()
	{
		if ($this->ColourHouseID == 0)
		{
			$RSSave = $this->DBObject->Prepare('INSERT INTO asa_colour_houses (houseName, houseColour, isActive, createUserID, createDate)
														VALUES (:|1, :|2, :|3, :|4, NOW());');
		
			$RSSave->Execute($this->HouseName, $this->HouseColour, $this->IsActive, $this->CreateUserID);
			
			$this->ColourHouseID = $RSSave->LastID;
		}
		else
		{
			$RSUpdate = $this->DBObject->Prepare('UPDATE asa_colour_houses
													SET	houseName = :|1,
														houseColour = :|2,
														isActive = :|3
													WHERE colourHouseID = :|4 LIMIT 1;');
													
			$RSUpdate->Execute($this->HouseName, $this->HouseColour, $this->IsActive, $this->ColourHouseID);
		}
		
		return true;
	}

	private function RemoveColourHouse()
    {
        if(!isset($this->ColourHouseID)) 
        {
            throw new ApplicationARException('', APP_AR_ERROR_DELETE_WITHOUT_ID);
        }
        
        $RSDeleteColourHouse = $this->DBObject->Prepare('DELETE FROM asa_colour_houses WHERE colourHouseID = :|1 LIMIT 1;');
        $RSDeleteColourHouse->Execute($this->ColourHouseID);                
    }
	
	private function GetColourHouseByID()
	{
		$RSColourHouse = $this->DBObject->Prepare('SELECT * FROM asa_colour_houses WHERE colourHouseID = :|1 LIMIT 1;');
		$RSColourHouse->Execute($this->ColourHouseID);
		
		$ColourHouseRow = $RSColourHouse->FetchRow();
		
		$this->SetAttributesFromDB($ColourHouseRow);				
	}
	
	private function SetAttributesFromDB($ColourHouseRow)
	{
		$this->ColourHouseID = $ColourHouseRow->colourHouseID;
		$this->HouseName = $ColourHouseRow->houseName;
		$this->HouseColour = $ColourHouseRow->houseColour;

		$this->IsActive = $ColourHouseRow->isActive;
		$this->CreateUserID = $ColourHouseRow->createUserID;
		$this->CreateDate = $ColourHouseRow->createDate;
	}	
}
?>
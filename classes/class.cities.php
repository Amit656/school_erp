<?php
require_once('class.db_connect.php');
error_reporting(E_ALL);

class City
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	
	private $CityID;
	private $StateID;
	private $CityName;
	
	private $IsActive;
	private $CreateUserID;
	private $CreateDate;
	
	// PUBLIC METHODS START HERE	//
	public function __construct($CityID = 0)
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;
		
		if($CityID != 0)
		{
			$this->CityID = $CityID;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetCityByID();
		}
		else
		{
			//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
			$this->CityID = 0;
			$this->StateID = 0;
			$this->CityName = '';
			
			$this->IsActive = 0;
			$this->CreateUserID = 0;
			$this->CreateDate = '0000-00-00 00:00:00';
		}
	}
	
	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetCityID()
	{
		return $this->CityID;
	}

	public function GetStateID()
	{
		return $this->StateID;
	}
	public function SetStateID($StateID)
	{
		$this->StateID = $StateID;
	}

	public function GetCityName()
	{
		return $this->CityName;
	}
	public function SetCityName($CityName)
	{
		$this->CityName = $CityName;
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
		
	// END OF PUBLIC METHODS	//
	
	// START OF STATIC METHODS	//
	static function GetActiveCities($StateID)
	{
		$AllCities = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT * FROM cities WHERE stateID = :|1 AND isActive = 1;');
			$RSSearch->Execute($StateID);
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllCities;
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$AllCities[$SearchRow->cityID] = $SearchRow->cityName;
			}
			
			return $AllCities;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at City::GetActiveCities(). Stack Trace: ' . $e->getTraceAsString());
			return $AllCities;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at City::GetActiveCities(). Stack Trace: ' . $e->getTraceAsString());
			return $AllCities;
		}		
	}
	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//
	private function SaveDetails()
	{
		if ($this->CityID == 0)
		{
			$RSSave = $this->DBObject->Prepare('INSERT INTO cities (stateID, cityName, isActive, createUserID, createDate)
														VALUES (:|1, :|2, :|3, :|4, NOW());');
		
			$RSSave->Execute($this->StateID, $this->CityName, $this->IsActive, $this->CreateUserID);
			
			$this->CityID = $RSSave->LastID;
		}
		else
		{
			$RSUpdate = $this->DBObject->Prepare('UPDATE cities
													SET	stateID = :|1,
														cityName = :|2,
														isActive = :|3
													WHERE cityID = :|4 LIMIT 1;');
													
			$RSUpdate->Execute($this->StateID, $this->CityName, $this->IsActive, $this->CityID);
		}
		
		return true;
	}
	
	private function GetCityByID()
	{
		$RSCity = $this->DBObject->Prepare('SELECT * FROM cities WHERE cityID = :|1 LIMIT 1;');
		$RSCity->Execute($this->CityID);
		
		$CityRow = $RSCity->FetchRow();
		
		$this->SetAttributesFromDB($CityRow);				
	}
	
	private function SetAttributesFromDB($CityRow)
	{
		$this->CityID = $CityRow->cityID;
		$this->StateID = $CityRow->stateID;
		$this->CityName = $CityRow->cityName;
		
		$this->IsActive = $CityRow->isActive;
		$this->CreateUserID = $CityRow->createUserID;
		$this->CreateDate = $CityRow->createDate;
	}	
}
?>
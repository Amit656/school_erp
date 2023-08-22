<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.db_connect.php');
error_reporting(E_ALL);

class City
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	
	private $CityID;
	private $CityName;

	private $StateID;
	private $IsActive;

	private $CreateBranchID;
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
			$this->CityName = '';

			$this->StateID = 0;
			$this->IsActive = 0;
			
			$this->CreateBranchID = 0;
			$this->CreateUserID = 0;
			$this->CreateDate = '';
			
		}
	}
	
	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetCityID()
	{
		return $this->CityID;
	}
	
	public function GetCityName()
	{
		return $this->CityName;
	}
	public function SetCityName($CityName)
	{
		$this->CityName = $CityName;
	}
	
	public function GetLastErrorCode()
	{
		return $this->LastErrorCode;
	}
	
	//  END OF GETTER AND SETTER FUNCTIONS 	//
		
	// END OF PUBLIC METHODS	//
	
	// START OF STATIC METHODS	//
	static function GetAllDistricts($StateID)
	{
		$AllDistricts = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT * FROM districts  
												WHERE stateID = :|1 
												ORDER BY districtName;');
			$RSSearch->Execute($StateID);
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllDistricts;
			}
			
			while ($SearchRow = $RSSearch->FetchRow())
			{
				$AllDistricts[$SearchRow->districtID] = $SearchRow->districtName;
			}
			
			return $AllDistricts;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at City::GetAllDistricts(). Stack Trace: '.$e->getTraceAsString());
			return $AllDistricts;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at City::GetAllDistricts(). Stack Trace: '.$e->getTraceAsString());
			return $AllDistricts;
		}
	}	

	static function GetAllCities($StateID, $DistrictID)
	{
		$AllCities = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT * FROM cities  
												WHERE stateID = :|1 AND districtID = :|2 
												ORDER BY cityName;');
			$RSSearch->Execute($StateID, $DistrictID);
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllCities;
			}
			
			while ($SearchRow = $RSSearch->FetchRow())
			{
				$AllCities[$SearchRow->cityID] = $SearchRow->cityName;
			}
			
			return $AllCities;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at City::GetAllCities(). Stack Trace: '.$e->getTraceAsString());
			return $AllCities;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at City::GetAllCities(). Stack Trace: '.$e->getTraceAsString());
			return $AllCities;
		}
	}	
	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//
	
	private function GetCityByID()
	{
		$RSCity = $this->DBObject->Prepare("SELECT * FROM cities WHERE cityID = :|1");
		$RSCity->Execute($this->CityID);
		
		$CityRow = $RSCity->FetchRow();
		
		$this->SetAttributesFromDB($CityRow);				
	}
	
	private function SetAttributesFromDB($CityRow)
	{
		$this->CityID = $CityRow->cityID;
		$this->CityName = $CityRow->cityName;
	}	
}
?>
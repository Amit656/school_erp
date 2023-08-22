<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.db_connect.php');
error_reporting(E_ALL);

class Country
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	
	private $CountryID;
	private $Country;
	
	// PUBLIC METHODS START HERE	//
	public function __construct($CountryID = 0)
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;
		
		if($CountryID != 0)
		{
			$this->CountryID = $CountryID;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetCountryByID();
		}
		else
		{
			//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
			$this->CountryID = 0;
			$this->Country = '';
		}
	}
	
	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetCountryID()
	{
		return $this->CountryID;
	}
	
	public function GetCountry()
	{
		return $this->Country;
	}
	public function SetCountry($Country)
	{
		$this->Country = $Country;
	}
	
	public function GetLastErrorCode()
	{
		return $this->LastErrorCode;
	}
	
	//  END OF GETTER AND SETTER FUNCTIONS 	//
		
	// END OF PUBLIC METHODS	//
	
	// START OF STATIC METHODS	//
	static function GetAllCountries()
	{
		$AllCountries = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT * FROM countries  
												ORDER BY countryID;');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllCountries;
			}
			
			while ($SearchRow = $RSSearch->FetchRow())
			{
				$AllCountries[$SearchRow->countryID] = $SearchRow->country;
			}
			
			return $AllCountries;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at Country::GetAllCountries(). Stack Trace: '.$e->getTraceAsString());
			return $AllCountries;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at Country::GetAllCountries(). Stack Trace: '.$e->getTraceAsString());
			return $AllCountries;
		}
	}
	
	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//
	private function GetCountryByID()
	{
		$RSCountry = $this->DBObject->Prepare("SELECT * FROM countries WHERE countryID = :|1");
		$RSCountry->Execute($this->CountryID);
		
		$CountryRow = $RSCountry->FetchRow();
		
		$this->SetAttributesFromDB($CountryRow);				
	}
	
	private function SetAttributesFromDB($CountryRow)
	{
		$this->CountryID = $CountryRow->countryID;
		$this->Country = $CountryRow->country;
	}	
}
?>
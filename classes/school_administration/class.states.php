<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.db_connect.php');
error_reporting(E_ALL);

class State
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	
	private $StateID;
	private $StateName;

	private $CountryID;
	private $IsActive;
	
	// PUBLIC METHODS START HERE	//
	public function __construct($StateID = 0)
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;
		
		if($StateID != 0)
		{
			$this->StateID = $StateID;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetStateByID();
		}
		else
		{
			//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
			$this->StateID = 0;
			$this->StateName = '';

			$this->CountryID = 0;
			$this->IsActive = 0;
		}
	}
	
	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetStateID()
	{
		return $this->StateID;
	}
	
	public function GetStateName()
	{
		return $this->StateName;
	}
	public function SetStateName($StateName)
	{
		$this->StateName = $StateName;
	}
	
	public function GetLastErrorCode()
	{
		return $this->LastErrorCode;
	}
	
	//  END OF GETTER AND SETTER FUNCTIONS 	//

	// END OF PUBLIC METHODS	//
	
	// START OF STATIC METHODS	//
	static function GetAllStates($CountryID)
	{
		$AllStates = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT * FROM states
												WHERE countryID = :|1  
												ORDER BY stateID;');
			$RSSearch->Execute($CountryID);
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllStates;
			}
			
			while ($SearchRow = $RSSearch->FetchRow())
			{
				$AllStates[$SearchRow->stateID] = $SearchRow->stateName;
			}
			
			return $AllStates;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at State::GetAllStates(). Stack Trace: '.$e->getTraceAsString());
			return $AllStates;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at State::GetAllStates(). Stack Trace: '.$e->getTraceAsString());
			return $AllStates;
		}
	}
	
	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//
	
	private function GetStateByID()
	{
		$RSState = $this->DBObject->Prepare("SELECT * FROM states WHERE stateID = :|1");
		$RSState->Execute($this->StateID);
		
		$StateRow = $RSState->FetchRow();
		
		$this->SetAttributesFromDB($StateRow);				
	}
	
	private function SetAttributesFromDB($StateRow)
	{
		$this->StateID = $StateRow->stateID;
		$this->StateName = $StateRow->stateName;
		$this->CountryID = $StateRow->countryID;
	}	
}
?>
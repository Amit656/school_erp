<?php
require_once('class.db_connect.php');
error_reporting(E_ALL);

class State
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	
	private $StateID;
	private $StateName;
	
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
	static function GetActiveStates()
	{
		$AllStates = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT * FROM states WHERE isActive = 1;');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllStates;
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$AllStates[$SearchRow->stateID] = $SearchRow->stateName;
			}
			
			return $AllStates;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at State::GetActiveStates(). Stack Trace: ' . $e->getTraceAsString());
			return $AllStates;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at State::GetActiveStates(). Stack Trace: ' . $e->getTraceAsString());
			return $AllStates;
		}		
	}
	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//
	private function SaveDetails()
	{
		if ($this->StateID == 0)
		{
			$RSSave = $this->DBObject->Prepare('INSERT INTO states (stateCode)
														VALUES (:|1);');
		
			$RSSave->Execute($this->StateName);
			
			$this->StateID = $RSSave->LastID;
		}
		else
		{
			$RSUpdate = $this->DBObject->Prepare('UPDATE states
													SET	stateCode = :|1
													WHERE stateID = :|2 LIMIT 1;');
													
			$RSUpdate->Execute($this->StateName, $this->StateID);
		}
		
		return true;
	}
	
	private function GetStateByID()
	{
		$RSState = $this->DBObject->Prepare('SELECT * FROM states WHERE stateID = :|1 LIMIT 1;');
		$RSState->Execute($this->StateID);
		
		$StateRow = $RSState->FetchRow();
		
		$this->SetAttributesFromDB($StateRow);				
	}
	
	private function SetAttributesFromDB($StateRow)
	{
		$this->StateID = $StateRow->stateID;
		$this->StateName = $StateRow->stateCode;
	}	
}
?>
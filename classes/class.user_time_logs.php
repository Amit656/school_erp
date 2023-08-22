<?php
require_once('class.db_connect.php');
error_reporting(E_ALL);

class UserTimeLog
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	
	private $UserTimeLogID;
	private $UserName;
	
	private $UserIPAddress;
	private $LoginDateTime;
	private $LogoutDateTime;
	
	// PUBLIC METHODS START HERE	//
	public function __construct($UserTimeLogID = 0)
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;
		
		if($UserTimeLogID != 0)
		{
			$this->UserTimeLogID = $UserTimeLogID;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetUserTimeLogByID();
		}
		else
		{
			//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
			$this->UserTimeLogID = 0;
			$this->UserName = '';
			
			$this->UserIPAddress = '';
			$this->LoginDateTime = '0000-00-00 00:00:00';
			$this->LogoutDateTime = '0000-00-00 00:00:00';
		}
	}
	
	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetUserTimeLogID()
	{
		return $this->UserTimeLogID;
	}
	
	public function GetUserName()
	{
		return $this->UserName;
	}
	public function SetUserName($UserName)
	{
		$this->UserName = $UserName;
	}
	
	public function GetUserIPAddress()
	{
		return $this->UserIPAddress;
	}
	public function SetUserIPAddress($UserIPAddress)
	{
		$this->UserIPAddress = $UserIPAddress;
	}
	
	public function GetLoginDateTime()
	{
		return $this->LoginDateTime;
	}
	public function SetLoginDateTime($LoginDateTime)
	{
		$this->LoginDateTime = $LoginDateTime;
	}
	
	public function GetLogoutDateTime()
	{
		return $this->LogoutDateTime;
	}
	public function SetLogoutDateTime($LogoutDateTime)
	{
		$this->LogoutDateTime = $LogoutDateTime;
	}
	
	public function GetLastErrorCode()
	{
		return $this->LastErrorCode;
	}
	
	//  END OF GETTER AND SETTER FUNCTIONS 	//
			
	// END OF PUBLIC METHODS	//
	
	// START OF STATIC METHODS	//
	
	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//	
	private function GetUserTimeLogByID()
	{
		$RSUserTimeLog = $this->DBObject->Prepare("SELECT * FROM user_time_logs WHERE userTimeLogID = :|1");
		$RSUserTimeLog->Execute($this->UserTimeLogID);
		
		$UserTimeLogRow = $RSUserTimeLog->FetchRow();
		
		$this->SetAttributesFromDB($UserTimeLogRow);				
	}
	
	private function SetAttributesFromDB($UserTimeLogRow)
	{
		$this->UserTimeLogID = $UserTimeLogRow->userTimeLogID;
		$this->UserName = $UserTimeLogRow->userName;
		
		$this->UserIPAddress = $UserTimeLogRow->userIPAddress;
		$this->LoginDateTime = $UserTimeLogRow->loginDateTime;
		$this->LogoutDateTime = $UserTimeLogRow->logoutDateTime;
	}	
}
?>
<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.db_connect.php');
error_reporting(E_ALL);

class User
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	
	private $UserID;
	private $UserName;
	
	// PUBLIC METHODS START HERE	//
	public function __construct($UserID = 0)
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;
		
		if($UserID != 0)
		{
			$this->UserID = $UserID;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetUserByID();
		}
		else
		{
			//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
			$this->UserID = 0;
			$this->UserName = '';
		}
	}
	
	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetUserID()
	{
		return $this->UserID;
	}
	
	public function GetUserName()
	{
		return $this->UserName;
	}
	public function SetUserName($UserName)
	{
		$this->UserName = $UserName;
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
	
	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//
	private function SaveDetails()
	{
		if ($this->UserID == 0)
		{
			$RSSave = $this->DBObject->Prepare('INSERT INTO users (userName)
														VALUES (:|1);');
		
			$RSSave->Execute($this->UserName);
			
			$this->UserID = $RSSave->LastID;
		}
		else
		{
			$RSUpdate = $this->DBObject->Prepare('UPDATE users
													SET	userName = :|1
													WHERE userID = :|2;');
													
			$RSUpdate->Execute($this->UserName, $this->UserID);
		}
		
		return true;
	}
	
	private function GetUserByID()
	{
		$RSUser = $this->DBObject->Prepare('SELECT * FROM users WHERE userID = :|1;');
		$RSUser->Execute($this->UserID);
		
		$UserRow = $RSUser->FetchRow();
		
		$this->SetAttributesFromDB($UserRow);				
	}
	
	private function SetAttributesFromDB($UserRow)
	{
		$this->UserID = $UserRow->userID;
		$this->UserName = $UserRow->userName;
	}	
}
?>
<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.db_connect.php');
error_reporting(E_ALL);

class ClassMaster
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	
	private $ClassMasterID;
	private $ClassName;
	private $SectionName;

	private $IsActive;
	private $CreateUserID;
	private $CreateDate;
	
	// PUBLIC METHODS START HERE	//
	public function __construct($ClassMasterID = 0)
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;
		
		if($ClassMasterID != 0)
		{
			$this->ClassMasterID = $ClassMasterID;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetClassMasterByID();
		}
		else
		{
			//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
			$this->ClassMasterID = 0;
			$this->ClassName = '';

			$this->IsActive = 0;
			$this->CreateUserID = 0;
			$this->CreateDate = '0000-00-00 00:00:00';
		}
	}
	
	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetClassMasterID()
	{
		return $this->ClassMasterID;
	}
	
	public function GetClassName()
	{
		return $this->ClassName;
	}
	public function SetClassName($ClassName)
	{
		$this->ClassMasterName = $ClassMasterName;
	}

	public function GetSectionName()
	{
		return $this->SectionName;
	}
	public function SetSectionName($SectionName)
	{
		$this->SectionName = $SectionName;
	}

	public function GetisActive()
	{
		return $this->isActive;
	}
	public function SetisActive($isActive)
	{
		$this->isActive = $isActive;
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
	
	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//
	private function SaveDetails()
	{
		if ($this->ClassMasterID == 0)
		{
			$RSSave = $this->DBObject->Prepare('INSERT INTO atm_class_master (className, sectionName, isActive, createUserID, createDate)
														VALUES (:|1, :|2, :|3, :|4, Now());');
		
			$RSSave->Execute($this->ClassName, $this->SectionName, $this->isActive, $this->CreateUserID,  $this->CreateDate);
			
			$this->ClassMaterID = $RSSave->LastID;
		}
		else
		{
			$RSUpdate = $this->DBObject->Prepare('UPDATE atm_class_master
													SET	className = :|1,
														sectionName = :|2,
														isActive = :|3,
													WHERE classMaterID = :|4;');
			$RSUpdate->Execute($this->ClassName, $this->SectionName, $this->isActive);
		}
		
		return true;
	}
	
	private function GetClassMasterByID()
	{
		$RSClassMaster = $this->DBObject->Prepare('SELECT * FROM atm_class_master WHERE classMaterID = :|1;');
		$RSClassMaster->Execute($this->ClassMasterID);
		
		$ClassMasterRow = $RSClassMaster->FetchRow();
		
		$this->SetAttributesFromDB($ClassMasterRow);				
	}
	
	private function SetAttributesFromDB($ClassMasterRow)
	{
		$this->ClassMasterID = $ClassMasterRow->userID;
		$this->ClassName = $ClassMasterRow->className;
		$this->SectionName = $ClassMasterRow->userName;
		$this->IsActive = $ClassMasterRow->userName;
		$this->SectionName = $ClassMasterRow->userName;
		$this->SectionName = $ClassMasterRow->userName;
	}	
}
?>
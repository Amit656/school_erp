<?php
require_once('class.db_connect.php');
error_reporting(E_ALL);

class SubMenu
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	
	private $SubmenuID;
	private $MenuID;	
	private $TaskID;
	private $SubmenuName;
	
	private $LinkedFileName;
	private $SubmenuPriority;
	private $IsNew;
	
	private $IsActive;
	private $CreateUserID;
	private $CreateDate;
	
	// PUBLIC METHODS START HERE	//
	public function __construct($SubmenuID = 0)
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;
		
		if($SubmenuID != 0)
		{
			$this->SubmenuID = $SubmenuID;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetSubmenuByID();
		}
		else
		{
			//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
			$this->SubmenuID = 0;
			$this->MenuID = 0;
			$this->TaskID = 0;
			$this->SubmenuName = '';
			
			$this->LinkedFileName = '';
			$this->SubmenuPriority = 0;
			$this->IsNew = 0;
			
			$this->IsActive = 0;
			$this->CreateUserID = 0;
			$this->CreateDate = '0000-00-00 00:00:00';
		}
	}
	
	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetSubmenuID()
	{
		return $this->SubmenuID;
	}
	
	public function GetMenuID()
	{
		return $this->MenuID;
	}
	public function SetMenuID($MenuID)
	{
		$this->MenuID = $MenuID;
	}
	
	public function GetTaskID()
	{
		return $this->TaskID;
	}
	public function SetTaskID($TaskID)
	{
		$this->TaskID = $TaskID;
	}
	
	public function GetSubmenuName()
	{
		return $this->SubmenuName;
	}
	public function SetSubmenuName($SubmenuName)
	{
		$this->SubmenuName = $SubmenuName;
	}
	
	public function GetLinkedFileName()
	{
		return $this->LinkedFileName;
	}
	public function SetLinkedFileName($LinkedFileName)
	{
		$this->LinkedFileName = $LinkedFileName;
	}
	
	public function GetSubmenuPriority()
	{
		return $this->SubmenuPriority;
	}
	public function SetSubmenuPriority($SubmenuPriority)
	{
		$this->SubmenuPriority = $SubmenuPriority;
	}
	
	public function GetIsNew()
	{
		return $this->IsNew;
	}
	public function SetIsNew($IsNew)
	{
		$this->IsNew = $IsNew;
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
			$this->RemoveSubMenu();
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
			if ($this->SubmenuID > 0)
			{
				$QueryString = ' AND submenuID != ' . $this->DBObject->RealEscapeVariable($this->SubmenuID);
			}

			$RSTotal = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM submenus WHERE menuID = :|1 AND submenuName = :|2' . $QueryString . ';');
			$RSTotal->Execute($this->MenuID, $this->SubmenuName);
			
			if ($RSTotal->FetchRow()->totalRecords > 0)
			{
				return true;
			}
			
			return false;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at SubMenu::RecordExists(). Stack Trace: ' . $e->getTraceAsString());
			return false;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at SubMenu::RecordExists(). Stack Trace: ' . $e->getTraceAsString());
			return false;
		}
	}
		
	// END OF PUBLIC METHODS	//
	
	// START OF STATIC METHODS	//
	static function GetAllSubMenus($MenuID)
	{
		$AllSubMenus = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT sm.*, t.taskName, u.userName FROM submenus sm 
												INNER JOIN tasks t ON sm.taskID = t.taskID
												INNER JOIN users u ON sm.createUserID = u.userID
												WHERE sm.menuID = :|1 ORDER BY sm.submenuPriority;');
			$RSSearch->Execute($MenuID);
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllSubMenus;
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$AllSubMenus[$SearchRow->submenuID]['TaskID'] = $SearchRow->taskID;
				$AllSubMenus[$SearchRow->submenuID]['TaskName'] = $SearchRow->taskName;

				$AllSubMenus[$SearchRow->submenuID]['SubmenuName'] = $SearchRow->submenuName;
				$AllSubMenus[$SearchRow->submenuID]['LinkedFileName'] = $SearchRow->linkedFileName;
				
				$AllSubMenus[$SearchRow->submenuID]['SubmenuPriority'] = $SearchRow->submenuPriority;
				$AllSubMenus[$SearchRow->submenuID]['IsActive'] = $SearchRow->isActive;
				
				$AllSubMenus[$SearchRow->submenuID]['CreateUserID'] = $SearchRow->createUserID;
				$AllSubMenus[$SearchRow->submenuID]['CreateUserName'] = $SearchRow->userName;
				$AllSubMenus[$SearchRow->submenuID]['CreateDate'] = $SearchRow->createDate;
			}
			
			return $AllSubMenus;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at SubMenu::GetAllSubMenus(). Stack Trace: ' . $e->getTraceAsString());
			return $AllSubMenus;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at SubMenu::GetAllSubMenus(). Stack Trace: ' . $e->getTraceAsString());
			return $AllSubMenus;
		}
	}
	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//
	private function SaveDetails()
	{
		if ($this->SubmenuID == 0)
		{
			$RSSave = $this->DBObject->Prepare('INSERT INTO submenus (menuID, taskID, submenuName, linkedFileName, submenuPriority, isNew, 
																		isActive, createUserID, createDate)
														VALUES (:|1, :|2, :|3, :|4, :|5, :|6, :|7, :|8, NOW());');
		
			$RSSave->Execute($this->MenuID, $this->TaskID, $this->SubmenuName, $this->LinkedFileName, $this->SubmenuPriority, $this->IsNew, 
								$this->IsActive, $this->CreateUserID);
			
			$this->SubmenuID = $RSSave->LastID;
		}
		else
		{
			$RSUpdate = $this->DBObject->Prepare('UPDATE submenus
													SET	menuID = :|1,
														taskID = :|2,
														submenuName = :|3,
														linkedFileName = :|4,
														submenuPriority = :|5,
														isNew = :|6,
														isActive = :|7
													WHERE submenuID = :|8 LIMIT 1;');
													
			$RSUpdate->Execute($this->MenuID, $this->TaskID, $this->SubmenuName, $this->LinkedFileName, $this->SubmenuPriority, $this->IsNew, 
								$this->IsActive, $this->SubmenuID);
		}
		
		return true;
	}

	private function RemoveSubMenu()
	{
		if(!isset($this->SubmenuID)) 
		{
    		throw new ApplicationARException('', APP_AR_ERROR_DELETE_WITHOUT_ID);
		}
		
		$RSDeleteSubmenu = $this->DBObject->Prepare('DELETE FROM submenus WHERE submenuID = :|1 LIMIT 1;');
		$RSDeleteSubmenu->Execute($this->SubmenuID);				
	}
	
	private function GetSubmenuByID()
	{
		$RSSubmenu = $this->DBObject->Prepare('SELECT * FROM submenus WHERE submenuID = :|1 LIMIT 1');
		$RSSubmenu->Execute($this->SubmenuID);
		
		$SubmenuRow = $RSSubmenu->FetchRow();
		
		$this->SetAttributesFromDB($SubmenuRow);				
	}
	
	private function SetAttributesFromDB($SubmenuRow)
	{
		$this->SubmenuID = $SubmenuRow->submenuID;
		$this->MenuID = $SubmenuRow->menuID;
		$this->TaskID = $SubmenuRow->taskID;
		$this->SubmenuName = $SubmenuRow->submenuName;
		
		$this->LinkedFileName = $SubmenuRow->linkedFileName;
		$this->SubmenuPriority = $SubmenuRow->submenuPriority;
		$this->IsNew = $SubmenuRow->isNew;
		
		$this->IsActive = $SubmenuRow->isActive;
		$this->CreateUserID = $SubmenuRow->createUserID;
		$this->CreateDate = $SubmenuRow->createDate;
	}	
}
?>
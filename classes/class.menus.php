<?php
require_once('class.db_connect.php');
error_reporting(E_ALL);

class Menu
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	
	private $MenuID;
	private $MenuName;
	
	private $MenuPriority;
	
	private $IsActive;
	private $CreateUserID;
	private $CreateDate;
	
	// PUBLIC METHODS START HERE	//
	public function __construct($MenuID = 0)
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;
		
		if($MenuID != 0)
		{
			$this->MenuID = $MenuID;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetMenuByID();
		}
		else
		{
			//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
			$this->MenuID = 0;
			$this->MenuName = '';
			
			$this->MenuPriority = 0;
			
			$this->IsActive = 0;
			$this->CreateUserID = 0;
			$this->CreateDate = '0000-00-00 00:00:00';
		}
	}
	
	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetMenuID()
	{
		return $this->MenuID;
	}
	
	public function GetMenuName()
	{
		return $this->MenuName;
	}
	public function SetMenuName($MenuName)
	{
		$this->MenuName = $MenuName;
	}
	
	public function GetMenuPriority()
	{
		return $this->MenuPriority;
	}
	public function SetMenuPriority($MenuPriority)
	{
		$this->MenuPriority = $MenuPriority;
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
			$this->RemoveMenu();
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
			$RSTotal = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM submenus WHERE menuID = :|1;');
			$RSTotal->Execute($this->MenuID);
	
			if ($RSTotal->FetchRow()->totalRecords > 0) 
			{
				return true;
			}
			
			return false;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException: Menu::CheckDependencies(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception: Menu::CheckDependencies(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
	}

	public function RecordExists()
	{
		try
		{
			$QueryString = '';
			if ($this->MenuID > 0)
			{
				$QueryString = ' AND menuID != ' . $this->DBObject->RealEscapeVariable($this->MenuID);
			}

			$RSTotal = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM menus WHERE menuName = :|1' . $QueryString . ';');
			$RSTotal->Execute($this->MenuName);
			
			if ($RSTotal->FetchRow()->totalRecords > 0)
			{
				return true;
			}
			
			return false;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at Menu::RecordExists(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at Menu::RecordExists(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
	}
	// END OF PUBLIC METHODS	//
	
	// START OF STATIC METHODS	//
	static function GetAllMenus()
	{
		$AllMenus = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT m.*, u.userName FROM menus m 
												INNER JOIN users u ON m.createUserID = u.userID
												ORDER BY m.menuPriority;');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllMenus;
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$AllMenus[$SearchRow->menuID]['MenuName'] = $SearchRow->menuName;
				$AllMenus[$SearchRow->menuID]['MenuPriority'] = $SearchRow->menuPriority;
				$AllMenus[$SearchRow->menuID]['IsActive'] = $SearchRow->isActive;
				
				$AllMenus[$SearchRow->menuID]['CreateUserID'] = $SearchRow->createUserID;
				$AllMenus[$SearchRow->menuID]['CreateUserName'] = $SearchRow->userName;
				$AllMenus[$SearchRow->menuID]['CreateDate'] = $SearchRow->createDate;
			}
			
			return $AllMenus;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at Menu::GetAllMenus(). Stack Trace: ' . $e->getTraceAsString());
			return $AllMenus;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at Menu::GetAllMenus(). Stack Trace: ' . $e->getTraceAsString());
			return $AllMenus;
		}
	}

	static function GetActiveMenus()
	{
		$AllMenus = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT * FROM menus WHERE isActive = 1;');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllMenus;
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$AllMenus[$SearchRow->menuID] = $SearchRow->menuName;
			}
			
			return $AllMenus;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at Menu::GetActiveMenus(). Stack Trace: ' . $e->getTraceAsString());
			return $AllMenus;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at Menu::GetActiveMenus(). Stack Trace: ' . $e->getTraceAsString());
			return $AllMenus;
		}		
	}
	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//
	private function SaveDetails()
	{
		if ($this->MenuID == 0)
		{
			$RSSave = $this->DBObject->Prepare('INSERT INTO menus (menuName, menuPriority, isActive, createUserID, createDate)
														VALUES (:|1, :|2, :|3, :|4, NOW());');
		
			$RSSave->Execute($this->MenuName, $this->MenuPriority, $this->IsActive, $this->CreateUserID);
			
			$this->MenuID = $RSSave->LastID;
		}
		else
		{
			$RSUpdate = $this->DBObject->Prepare('UPDATE menus
													SET	menuName = :|1,
														menuPriority = :|2,
														isActive = :|3
													WHERE menuID = :|4;');
													
			$RSUpdate->Execute($this->MenuName, $this->MenuPriority, $this->IsActive, $this->MenuID);
		}
		
		return true;
	}

	private function RemoveMenu()
	{
		if(!isset($this->MenuID)) 
		{
    		throw new ApplicationARException('', APP_AR_ERROR_DELETE_WITHOUT_ID);
		}
		
		$RSDeleteMenu = $this->DBObject->Prepare('DELETE FROM menus WHERE menuID = :|1 LIMIT 1;');
		$RSDeleteMenu->Execute($this->MenuID);				
	}
	
	private function GetMenuByID()
	{
		$RSMenu = $this->DBObject->Prepare('SELECT * FROM menus WHERE menuID = :|1 LIMIT 1;');
		$RSMenu->Execute($this->MenuID);
		
		$MenuRow = $RSMenu->FetchRow();
		
		$this->SetAttributesFromDB($MenuRow);				
	}
	
	private function SetAttributesFromDB($MenuRow)
	{
		$this->MenuID = $MenuRow->menuID;
		$this->MenuName = $MenuRow->menuName;
		
		$this->MenuPriority = $MenuRow->menuPriority;
		
		$this->IsActive = $MenuRow->isActive;
		$this->CreateUserID = $MenuRow->createUserID;
		$this->CreateDate = $MenuRow->createDate;
	}	
}
?>
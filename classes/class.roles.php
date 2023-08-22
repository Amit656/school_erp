<?php
require_once('class.db_connect.php');
error_reporting(E_ALL);

class Role
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	
	private $RoleID;
	private $RoleName;
	private $IsSystemAdminRole;
	
	private $IsActive;
	private $CreateUserID;
	private $CreateDate;
	
	// PUBLIC METHODS START HERE	//
	public function __construct($RoleID = 0)
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;
		
		if($RoleID != 0)
		{
			$this->RoleID = $RoleID;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetRoleByID();
		}
		else
		{
			//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
			$this->RoleID = 0;
			$this->RoleName = '';
			$this->IsSystemAdminRole = 0;
			
			$this->IsActive = 0;
			$this->CreateUserID = 0;
			$this->CreateDate = '0000-00-00 00:00:00';
		}
	}
	
	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetRoleID()
	{
		return $this->RoleID;
	}
	
	public function GetRoleName()
	{
		return $this->RoleName;
	}
	public function SetRoleName($RoleName)
	{
		$this->RoleName = $RoleName;
	}
	
	public function GetIsSystemAdminRole()
	{
		return $this->IsSystemAdminRole;
	}
	public function SetIsSystemAdminRole($IsSystemAdminRole)
	{
		$this->IsSystemAdminRole = $IsSystemAdminRole;
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
			$this->RemoveRole();
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
			$RSTotalUserRecords = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM users WHERE roleID = :|1;');
			$RSTotalUserRecords->Execute($this->RoleID);
	
			if ($RSTotalUserRecords->FetchRow()->totalRecords > 0) 
			{
				return true;
			}

			$RSTotalRoleTasksRecords = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM role_tasks WHERE roleID = :|1;');
			$RSTotalRoleTasksRecords->Execute($this->RoleID);
	
			if ($RSTotalRoleTasksRecords->FetchRow()->totalRecords > 0) 
			{
				return true;
			}
			
			return false;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException: Role::CheckDependencies(). Stack Trace: ' . $e->getTraceAsString());
			return false;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception: Role::CheckDependencies(). Stack Trace: ' . $e->getTraceAsString());
			return false;
		}		
	}

	public function RecordExists()
	{
		try
		{
			$QueryString = '';
			if ($this->RoleID > 0)
			{
				$QueryString = ' AND roleID != ' . $this->DBObject->RealEscapeVariable($this->RoleID);
			}

			$RSTotal = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM roles WHERE roleName = :|1' . $QueryString . ';');
			$RSTotal->Execute($this->RoleName);
			
			if ($RSTotal->FetchRow()->totalRecords > 0)
			{
				return true;
			}
			
			return false;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at Role::RecordExists(). Stack Trace: ' . $e->getTraceAsString());
			return false;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at Role::RecordExists(). Stack Trace: ' . $e->getTraceAsString());
			return false;
		}
	}

	public function GetRoleTasks()
	{
		$AllRoleTasks = array();

		try
		{
			$RSRoleTasks = $this->DBObject->Prepare('SELECT roleTaskID, taskID FROM role_tasks WHERE roleID = :|1;');		
			$RSRoleTasks->Execute($this->RoleID);
			
			if ($RSRoleTasks->Result->num_rows <= 0)
			{
				return $AllRoleTasks;
			}
			
			while($RoleTasksRow = $RSRoleTasks->FetchRow())
			{
				$AllRoleTasks[$RoleTasksRow->roleTaskID] = (int) $RoleTasksRow->taskID;
			}
			
			return $AllRoleTasks;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at Role::GetRoleTasks(). Stack Trace: ' . $e->getTraceAsString());
			return $AllRoleTasks;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: ApplicationDBException at Role::GetRoleTasks(). Stack Trace: ' . $e->getTraceAsString());
			return $AllRoleTasks;
		}		
	}
	
	public function SetRoleTasks($SelectedRoleTasks, $CreateUserID)
	{
		try
		{
			if (is_array($SelectedRoleTasks) && count($SelectedRoleTasks) > 0)
			{
				$AllTasksAssigned = '';
				$NewTasksToInsert = '';
				
				foreach ($SelectedRoleTasks as $TaskID => $Value)
				{
					$AllTasksAssigned .= $TaskID . ', ';
					
					$RSSelectedTasks = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM role_tasks WHERE roleID = :|1 AND taskID = :|2;');
					$RSSelectedTasks->Execute($this->RoleID, $TaskID);
					
					if ($RSSelectedTasks->FetchRow()->totalRecords == 0)
					{
						$NewTasksToInsert .= ' (' . $this->RoleID . ', ' . $TaskID . ', ' . $CreateUserID . ', NOW()), ';
					}
				}
												
				$AllTasksAssigned = substr($AllTasksAssigned, 0, -2);
				
				$this->DBObject->BeginTransaction();
				
				$RSDelete = $this->DBObject->Prepare('DELETE FROM role_tasks WHERE roleID = :|1 AND taskID NOT IN(' . $AllTasksAssigned . ');');
				$RSDelete->Execute($this->RoleID);
				
				if ($NewTasksToInsert != '')
				{
					$NewTasksToInsert = substr($NewTasksToInsert, 0, -2);
					
					$RSSave = $this->DBObject->Prepare('INSERT INTO role_tasks (roleID, taskID, createUserID, createDate) VALUES ' . $NewTasksToInsert . ';');
					$RSSave->Execute();
				}
				
				$this->DBObject->CommitTransaction();
			}
			else
			{
				$RSDelete = $this->DBObject->Prepare('DELETE FROM role_tasks WHERE roleID = :|1;');
				$RSDelete->Execute($this->RoleID);
			}
						
			return true;
		}
		catch (ApplicationDBException $e)
		{
			$this->DBObject->RollBackTransaction();
			error_log('DEBUG: ApplicationDBException at Role::SetRoleTasks(). Stack Trace: ' . $e->getTraceAsString());
			return false;
		}
		catch (Exception $e)
		{
			$this->DBObject->RollBackTransaction();
			error_log('DEBUG: Exception at Role::SetRoleTasks(). Stack Trace: ' . $e->getTraceAsString());
			return false;
		}		
	}
	// END OF PUBLIC METHODS	//
	
	// START OF STATIC METHODS	//
	static function GetAllRoles()
	{
		$AllRoles = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT r.*, u.userName FROM roles r 
												INNER JOIN users u ON r.createUserID = u.userID
												ORDER BY roleID;');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllRoles;
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$AllRoles[$SearchRow->roleID]['RoleName'] = $SearchRow->roleName;
				$AllRoles[$SearchRow->roleID]['IsSystemAdminRole'] = $SearchRow->isSystemAdminRole;
				$AllRoles[$SearchRow->roleID]['IsActive'] = $SearchRow->isActive;
				
				$AllRoles[$SearchRow->roleID]['CreateUserID'] = $SearchRow->createUserID;
				$AllRoles[$SearchRow->roleID]['CreateUserName'] = $SearchRow->userName;
				$AllRoles[$SearchRow->roleID]['CreateDate'] = $SearchRow->createDate;
			}
			
			return $AllRoles;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at Role::GetAllRoles(). Stack Trace: ' . $e->getTraceAsString());
			return $AllRoles;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at Role::GetAllRoles(). Stack Trace: ' . $e->getTraceAsString());
			return $AllRoles;
		}
	}
	
	static function GetActiveRoles()
	{
		$AllRoles = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT * FROM roles WHERE isActive = 1;');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllRoles;
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$AllRoles[$SearchRow->roleID] = $SearchRow->roleName;
			}
			
			return $AllRoles;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at Role::GetActiveRoles(). Stack Trace: ' . $e->getTraceAsString());
			return $AllRoles;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at Role::GetActiveRoles(). Stack Trace: ' . $e->getTraceAsString());
			return $AllRoles;
		}		
	}
	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//
	private function SaveDetails()
	{
		if ($this->RoleID == 0)
		{
			$RSSave = $this->DBObject->Prepare('INSERT INTO roles (roleName, isSystemAdminRole, isActive, createUserID, createDate)
														VALUES (:|1, :|2, :|3, :|4, NOW());');
		
			$RSSave->Execute($this->RoleName, $this->IsSystemAdminRole, $this->IsActive, $this->CreateUserID);
			
			$this->RoleID = $RSSave->LastID;
		}
		else
		{
			$RSUpdate = $this->DBObject->Prepare('UPDATE roles
													SET	roleName = :|1,
														isSystemAdminRole = :|2,
														isActive = :|3
													WHERE roleID = :|4 LIMIT 1;');
													
			$RSUpdate->Execute($this->RoleName, $this->IsSystemAdminRole, $this->IsActive, $this->RoleID);
		}
		
		return true;
	}

	private function RemoveRole()
	{
		if(!isset($this->RoleID)) 
		{
    		throw new ApplicationARException('', APP_AR_ERROR_DELETE_WITHOUT_ID);
		}
		
		$RSDeleteRole = $this->DBObject->Prepare('DELETE FROM roles WHERE roleID = :|1 LIMIT 1;');
		$RSDeleteRole->Execute($this->RoleID);				
	}
	
	private function GetRoleByID()
	{
		$RSRole = $this->DBObject->Prepare('SELECT * FROM roles WHERE roleID = :|1 LIMIT 1;');
		$RSRole->Execute($this->RoleID);
		
		$RoleRow = $RSRole->FetchRow();
		
		$this->SetAttributesFromDB($RoleRow);				
	}
	
	private function SetAttributesFromDB($RoleRow)
	{
		$this->RoleID = $RoleRow->roleID;
		$this->RoleName = $RoleRow->roleName;
		$this->IsSystemAdminRole = $RoleRow->isSystemAdminRole;
		
		$this->IsActive = $RoleRow->isActive;
		$this->CreateUserID = $RoleRow->createUserID;
		$this->CreateDate = $RoleRow->createDate;
	}	
}
?>
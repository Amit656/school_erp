<?php
require_once('class.db_connect.php');
error_reporting(E_ALL);

class User
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	
	private $UserID;
	private $UserName;	
	private $Password;
	private $RoleID;
	
	private $AccountExpiryDate;
	private $HasLoginTimeLimit;
	private $LoginStartTime;
	private $LoginEndTime;
	
	private $LastLoginDate;
	private $LastLogoutDate;
	
	private $IsActive;
	private $CreateUserID;
	private $CreateDate;
	
	// PUBLIC METHODS START HERE	//
	public function __construct($UserID = 0, $UserName = '')
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;
		
		if($UserID != 0)
		{
			$this->UserID = $UserID;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetUserByID();
		}
		elseif($UserName != '')
		{
			$this->UserName = $UserName;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetUserByUserName();
		}
		else
		{
			//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
			$this->UserID = 0;
			$this->UserName = '';
			$this->Password = '';
			$this->RoleID = 0;
			
			$this->AccountExpiryDate = '0000-00-00 00:00:00';
			$this->HasLoginTimeLimit = 0;
			$this->LoginStartTime = '00:00:00';
			$this->LoginEndTime = '00:00:00';
			
			$this->LastLoginDate = '0000-00-00 00:00:00';
			$this->LastLogoutDate = '0000-00-00 00:00:00';
			
			$this->IsActive = 0;
			$this->CreateUserID = 0;
			$this->CreateDate = '0000-00-00 00:00:00';
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
	
	public function GetPassword()
	{
		return $this->Password;
	}
	public function SetPassword($Password)
	{
		$this->Password = $Password;
	}
	
	public function GetRoleID()
	{
		return $this->RoleID;
	}
	public function SetRoleID($RoleID)
	{
		$this->RoleID = $RoleID;
	}
	
	public function GetAccountExpiryDate()
	{
		return $this->AccountExpiryDate;
	}
	public function SetAccountExpiryDate($AccountExpiryDate)
	{
		$this->AccountExpiryDate = $AccountExpiryDate;
	}
	
	public function GetHasLoginTimeLimit()
	{
		return $this->HasLoginTimeLimit;
	}
	public function SetHasLoginTimeLimit($HasLoginTimeLimit)
	{
		$this->HasLoginTimeLimit = $HasLoginTimeLimit;
	}
	
	public function GetLoginStartTime()
	{
		return $this->LoginStartTime;
	}
	public function SetLoginStartTime($LoginStartTime)
	{
		$this->LoginStartTime = $LoginStartTime;
	}
	
	public function GetLoginEndTime()
	{
		return $this->LoginEndTime;
	}
	public function SetLoginEndTime($LoginEndTime)
	{
		$this->LoginEndTime = $LoginEndTime;
	}
	
	public function GetLastLoginDate()
	{
		if ($this->LastLoginDate == '0000-00-00 00:00:00')
		{
			return 'N/A';
		}
		else
		{
			return $this->LastLoginDate;
		}
	}
	
	public function GetLastLogoutDate()
	{
		if ($this->LastLogoutDate == '0000-00-00 00:00:00')
		{
			return 'N/A';
		}
		else
		{
			return $this->LastLogoutDate;
		}
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
			$this->DBObject->BeginTransaction();
			if ($this->SaveDetails())
			{
				$this->DBObject->CommitTransaction();
				return true;
			}
			
			$this->DBObject->RollBackTransaction();
			return false;
		}
		catch (ApplicationDBException $e)
		{
			$this->DBObject->RollBackTransaction();
			$this->LastErrorCode = $e->getCode();
			return false;
		}
		catch (ApplicationARException $e)
		{
			$this->DBObject->RollBackTransaction();
			$this->LastErrorCode = $e->getCode();
			return false;
		}
		catch (Exception $e)
		{
			$this->DBObject->RollBackTransaction();
			$this->LastErrorCode = APP_ERROR_UNDEFINED_ERROR;
			return false;
		}
	}
	
	public function GetUserMenus($ModuleID = 0)
	{
		$AllMenusArray = array();

		$QueryString = '';

		if ($ModuleID > 0)
		{
			$QueryString = ' AND sm.taskID IN (SELECT taskID FROM tasks WHERE moduleID = ' . $this->DBObject->RealEscapeVariable($ModuleID) . ')';
		}
		else
		{
			$QueryString = ' AND sm.taskID IN (SELECT taskID FROM tasks WHERE moduleID = 0 OR moduleID IN (SELECT moduleID FROM sa_modules WHERE isDefault = 1))';
		}

		try
		{
			$RSUserMenus = $this->DBObject->Prepare('SELECT sm.submenuID, sm.submenuName, sm.linkedFilename, sm.isNew, m.menuName
													FROM submenus sm 
													INNER JOIN menus m ON sm.menuID = m.menuID
													WHERE sm.taskID IN (SELECT taskID FROM user_tasks WHERE userID = :|1 AND isRevoked = 0)
													AND m.isActive AND sm.isActive' . $QueryString . '
													ORDER BY m.menuPriority, sm.submenuPriority;');		
			$RSUserMenus->Execute($this->UserID);
			
			if ($RSUserMenus->Result->num_rows <= 0)
			{
				return $AllMenusArray;
			}
			
			while($UserMenusRow = $RSUserMenus->FetchRow())
			{
				$AllMenusArray[$UserMenusRow->menuName][$UserMenusRow->submenuID]['SubmenuName'] = $UserMenusRow->submenuName;
				$AllMenusArray[$UserMenusRow->menuName][$UserMenusRow->submenuID]['LinkedFilename'] = $UserMenusRow->linkedFilename;
				$AllMenusArray[$UserMenusRow->menuName][$UserMenusRow->submenuID]['IsNew'] = $UserMenusRow->isNew;
			}
			
			return $AllMenusArray;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at User::GetUserMenus(). Stack Trace: ' . $e->getTraceAsString());
			return $AllMenusArray;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: ApplicationDBException at User::GetUserMenus(). Stack Trace: ' . $e->getTraceAsString());
			return $AllMenusArray;
		}		
	}

	public function GetTaskGroupWiseUserTasks()
	{
		$AllUserTasks = array();

		try
		{
			$RSUserTasks = $this->DBObject->Prepare('SELECT ut.userTaskID, ut.taskID, t.taskGroupID 
													 FROM user_tasks ut 
													 INNER JOIN tasks t ON t.taskID = ut.taskID 
													 WHERE ut.userID = :|1 AND ut.isRevoked = 0 
													 ORDER BY t.taskGroupID, t.taskName;');
			$RSUserTasks->Execute($this->UserID);
			
			if ($RSUserTasks->Result->num_rows <= 0)
			{
				return $AllUserTasks;
			}
			
			while($UserTasksRow = $RSUserTasks->FetchRow())
			{
				$AllUserTasks[$UserTasksRow->taskGroupID][$UserTasksRow->userTaskID] = $UserTasksRow->taskID;
			}
			
			return $AllUserTasks;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at User::GetTaskGroupWiseUserTasks(). Stack Trace: ' . $e->getTraceAsString());
			return $AllUserTasks;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: ApplicationDBException at User::GetTaskGroupWiseUserTasks(). Stack Trace: ' . $e->getTraceAsString());
			return $AllUserTasks;
		}		
	}
	
	public function GetUserTasks()
	{
		$AllUserTasks = array();

		try
		{
			$RSUserTasks = $this->DBObject->Prepare('SELECT userTaskID, taskID FROM user_tasks WHERE userID = :|1 AND isRevoked = 0;');		
			$RSUserTasks->Execute($this->UserID);
			
			if ($RSUserTasks->Result->num_rows <= 0)
			{
				return $AllUserTasks;
			}
			
			while($UserTasksRow = $RSUserTasks->FetchRow())
			{
				$AllUserTasks[$UserTasksRow->userTaskID] = (int) $UserTasksRow->taskID;
			}
			
			return $AllUserTasks;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at User::GetUserTasks(). Stack Trace: ' . $e->getTraceAsString());
			return $AllUserTasks;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: ApplicationDBException at User::GetUserTasks(). Stack Trace: ' . $e->getTraceAsString());
			return $AllUserTasks;
		}		
	}

	public function SetUserTasks($UserTasksArray, $UpdateUserID)
	{
		try
		{
			if (is_array($UserTasksArray) && count($UserTasksArray) > 0)
			{
				$TasksAlreadyAssigned = '';
				$NewTaskForInsertQuery = '';
				
				foreach ($UserTasksArray as $TaskID => $Value)
				{
					$TasksAlreadyAssigned .= $TaskID . ', ';
					
					$RSSelectTasksOFUser = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM user_tasks WHERE userID = :|1 AND taskID = :|2 AND isRevoked = 0;');
					$RSSelectTasksOFUser->Execute($this->UserID, $TaskID);
					
					if ($RSSelectTasksOFUser->FetchRow()->totalRecords == 0)
					{
						$NewTaskForInsertQuery .= ' (' . $this->UserID . ', ' . $TaskID . ', ' . $UpdateUserID . ', NOW()), ';
					}
				}
												
				$TasksAlreadyAssigned = substr($TasksAlreadyAssigned, 0, -2);
				
				$this->DBObject->BeginTransaction();
				
				$RSDelete = $this->DBObject->Prepare('UPDATE user_tasks SET isRevoked = 1, revokedByUserID = :|1, revokedByDate = NOW() WHERE userID = :|2 AND taskID NOT IN(' . $TasksAlreadyAssigned . ') AND isRevoked = 0;');
				$RSDelete->Execute($UpdateUserID, $this->UserID);
				
				if ($NewTaskForInsertQuery != '')
				{
					$NewTaskForInsertQuery = substr($NewTaskForInsertQuery, 0, -2);
					
					$RSSave = $this->DBObject->Prepare('INSERT INTO user_tasks (userID, taskID, grantedByUserID, grantedByDate) VALUES ' . $NewTaskForInsertQuery . ';');
					$RSSave->Execute();
				}
				
				$this->DBObject->CommitTransaction();
			}
			else
			{
				$RSDelete = $this->DBObject->Prepare('UPDATE user_tasks SET isRevoked = 1, revokedByUserID = :|1, revokedByDate = NOW() WHERE userID = :|2 AND isRevoked = 0;');
				$RSDelete->Execute($UpdateUserID, $this->UserID);
			}
						
			return true;
		}
		catch (ApplicationDBException $e)
		{
			$this->DBObject->RollBackTransaction();
			error_log('DEBUG: ApplicationDBException at User::SetUserTasks(). Stack Trace: ' . $e->getTraceAsString());
			return false;
		}
		catch (Exception $e)
		{
			$this->DBObject->RollBackTransaction();
			error_log('DEBUG: Exception at User::SetUserTasks(). Stack Trace: ' . $e->getTraceAsString());
			return false;
		}		
	}

	public function HasPermissionForTask($TaskID)
	{
		if (in_array($TaskID, $this->GetUserTasks(), TRUE))
		{
			return true;
		}

		return false;
	}

	public function ChangeUserPassword($NewPassword)
	{
		try
		{
			$RSUpdate = $this->DBObject->Prepare('UPDATE users SET password = SHA1(:|1) WHERE userID = :|2 LIMIT 1;');
			$RSUpdate->Execute($NewPassword, $this->UserID);
			
			return true;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at User::ChangeUserPassword(). Stack Trace: ' . $e->getTraceAsString());
			return false;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: ApplicationDBException at User::ChangeUserPassword(). Stack Trace: ' . $e->getTraceAsString());
			return false;
		}
	}
	// END OF PUBLIC METHODS	//
	
	// START OF STATIC METHODS	//
	static function SearchSystemAdminUsers(&$TotalRecords = 0, $GetTotalsOnly = false, $Filters = array(), $Start = 0, $Limit = 100)
	{
		$UsersList = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$Conditions = array();
			
			if (count($Filters) > 0)
			{
				if (!empty($Filters['UserName']))
				{
					$Conditions[] = 'u.userName = ' . $DBConnObject->RealEscapeVariable($Filters['UserName']);
				}
				
				if (!empty($Filters['RoleID']))
				{
					$Conditions[] = 'u.roleID =  '. $DBConnObject->RealEscapeVariable($Filters['RoleID']);
				}
				
				if (!empty($Filters['ActiveStatus']))
				{
					if ($Filters['ActiveStatus'] == 1) //active records
					{
						$Conditions[] = 'u.isActive = 1';
					}
					else if ($Filters['ActiveStatus'] == 2) //non active records
					{
						$Conditions[] = 'u.isActive = 0';
					}
				}
			}
			
			$QueryString = '';
			if (count($Conditions) > 0)
			{
				$QueryString = ' WHERE ' . implode(' AND ', $Conditions);
			}
			
			if ($GetTotalsOnly)
			{
				$RSTotal = $DBConnObject->Prepare('SELECT COUNT(*) AS totalRecords 
													FROM users u
													INNER JOIN roles r ON u.roleID = r.roleID
													' . $QueryString . ';');
				$RSTotal->Execute();
				
				$TotalRecords = $RSTotal->FetchRow()->totalRecords;
				return;
			}
			
			$RSRoleUsers = $DBConnObject->Prepare('SELECT u.userID, u.userName, u.roleID, u.accountExpiryDate, u.hasLoginTimeLimit, 
													u.loginStartTime, u.loginEndTime, u.isActive, r.roleName 
													FROM users u
													INNER JOIN roles r ON u.roleID = r.roleID
													' . $QueryString . ' 
													ORDER BY u.userName LIMIT ' . (int) $Start . ', ' . (int) $Limit . ';');
			$RSRoleUsers->Execute();
			
			while($RoleUsersRow = $RSRoleUsers->FetchRow())
			{
				$UsersList[$RoleUsersRow->userID]['UserName'] = $RoleUsersRow->userName;
				$UsersList[$RoleUsersRow->userID]['RoleID'] = $RoleUsersRow->roleID;
				
				$UsersList[$RoleUsersRow->userID]['Role'] = $RoleUsersRow->roleName;
				
				$UsersList[$RoleUsersRow->userID]['AccountExpiryDate'] = $RoleUsersRow->accountExpiryDate;
				$UsersList[$RoleUsersRow->userID]['HasLoginTimeLimit'] = $RoleUsersRow->hasLoginTimeLimit;
				$UsersList[$RoleUsersRow->userID]['LoginStartTime'] = $RoleUsersRow->loginStartTime;
				$UsersList[$RoleUsersRow->userID]['LoginEndTime'] = $RoleUsersRow->loginEndTime;
				
				$UsersList[$RoleUsersRow->userID]['IsActive'] = $RoleUsersRow->isActive;
			}
			
			return $UsersList;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at User::SearchSystemAdminUsers(). Stack Trace: ' . $e->getTraceAsString());
			return $UsersList;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at User::SearchSystemAdminUsers(). Stack Trace: ' . $e->getTraceAsString());
			return $UsersList;
		}		
	}

	static function GetAllUsersForList()
	{
		$AllUsers = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT * FROM users ORDER BY userName;');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllUsers;
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$AllUsers[$SearchRow->userID] = $SearchRow->userName;
			}
			
			return $AllUsers;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at User::GetAllUsersForList(). Stack Trace: ' . $e->getTraceAsString());
			return $AllUsers;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at User::GetAllUsersForList(). Stack Trace: ' . $e->getTraceAsString());
			return $AllUsers;
		}		
	}

	static function GetActiveUsers()
	{
		$AllUsers = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT * FROM users WHERE isActive = 1 ORDER BY userName;');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllUsers;
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$AllUsers[$SearchRow->userID] = $SearchRow->userName;
			}
			
			return $AllUsers;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at User::GetActiveUsers(). Stack Trace: ' . $e->getTraceAsString());
			return $AllUsers;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at User::GetActiveUsers(). Stack Trace: ' . $e->getTraceAsString());
			return $AllUsers;
		}		
	}
	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//
	private function SaveDetails()
	{
		if ($this->UserID == 0)
		{
			$RSSave = $this->DBObject->Prepare('INSERT INTO users (userName, password, roleID, accountExpiryDate, hasLoginTimeLimit, 
																	loginStartTime, loginEndTime, isActive, createUserID, createDate)
														VALUES (:|1, SHA1(:|2), :|3, :|4, :|5, :|6, :|7, :|8, :|9, NOW());');
		
			$RSSave->Execute($this->UserName, $this->Password, $this->RoleID, $this->AccountExpiryDate, $this->HasLoginTimeLimit, 
								$this->LoginStartTime, $this->LoginEndTime, $this->IsActive, $this->CreateUserID);
						
			$this->UserID = $RSSave->LastID;

			$RSAddUserTasks = $this->DBObject->Prepare('INSERT INTO user_tasks (userID, taskID, grantedByUserID, grantedByDate) SELECT :|1, taskID, :|2, NOW() FROM role_tasks WHERE roleID = :|3;');
			$RSAddUserTasks->Execute($this->UserID, $this->CreateUserID, $this->RoleID);
		}
		else
		{
			$RSUpdate = $this->DBObject->Prepare('UPDATE users
													SET	roleID = :|1,
														accountExpiryDate = :|2,
														hasLoginTimeLimit = :|3,
														loginStartTime = :|4,
														loginEndTime = :|5,
														isActive = :|6
													WHERE userID = :|7 LIMIT 1;');
													
			$RSUpdate->Execute($this->RoleID, $this->AccountExpiryDate, $this->HasLoginTimeLimit, 
								$this->LoginStartTime, $this->LoginEndTime, $this->IsActive, $this->UserID);
		}
		
		return true;
	}
	
	private function GetUserByID()
	{
		$RSUser = $this->DBObject->Prepare('SELECT * FROM users WHERE userID = :|1 LIMIT 1;');
		$RSUser->Execute($this->UserID);
		
		if ($RSUser->Result->num_rows <= 0)
		{
			throw new ApplicationDBException('', APP_DB_ERROR_NO_RECORDS);
		}
			
		$UserRow = $RSUser->FetchRow();
		
		$this->SetAttributesFromDB($UserRow);				
	}
	
	private function GetUserByUserName()
	{
		$RSUser = $this->DBObject->Prepare('SELECT * FROM users WHERE userName = :|1 LIMIT 1;');
		$RSUser->Execute($this->UserName);
		
		if ($RSUser->Result->num_rows <= 0)
		{
			throw new ApplicationDBException('', APP_DB_ERROR_NO_RECORDS);
		}
			
		$UserRow = $RSUser->FetchRow();
		
		$this->SetAttributesFromDB($UserRow);				
	}
	
	private function SetAttributesFromDB($UserRow)
	{
		$this->UserID = $UserRow->userID;
		$this->UserName = $UserRow->userName;
		$this->Password = $UserRow->password;
		$this->RoleID = $UserRow->roleID;
		
		$this->AccountExpiryDate = $UserRow->accountExpiryDate;
		$this->HasLoginTimeLimit = $UserRow->hasLoginTimeLimit;
		$this->LoginStartTime = $UserRow->loginStartTime;
		$this->LoginEndTime = $UserRow->loginEndTime;
		
		$this->LastLoginDate = $UserRow->lastLoginDate;
		$this->LastLogoutDate = $UserRow->lastLogoutDate;
		
		$this->IsActive = $UserRow->isActive;
		$this->CreateUserID = $UserRow->createUserID;
		$this->CreateDate = $UserRow->createDate;
	}	
}
?>
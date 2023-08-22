<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.db_connect.php');
error_reporting(E_ALL);

class RoleGroup
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	
	private $RoleGroupID;
	private $RoleGroupName;

	private $IsActive;
	private $CreateUserID;
	private $CreateDate;
	
	// PUBLIC METHODS START HERE	//
	public function __construct($RoleGroupID = 0)
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;
		
		if($RoleGroupID != 0)
		{
			$this->RoleGroupID = $RoleGroupID;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetRoleGroupByID();
		}
		else
		{
			//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
			$this->RoleGroupID = 0;
			$this->RoleGroupName = '';

			$this->IsActive = 0;
			$this->CreateUserID = 0;
			$this->CreateDate = '0000-00-00 00:00:00';
		}
	}
	
	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetRoleGroupID()
	{
		return $this->RoleGroupID;
	}
	
	public function GetRoleGroupName()
	{
		return $this->RoleGroupName;
	}
	public function SetRoleGroupName($RoleGroupName)
	{
		$this->RoleGroupName = $RoleGroupName;
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
			$this->RemoveRoleGroup();
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
			$RSTotal = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM role_group_roles WHERE roleGroupID = :|1;');
			$RSTotal->Execute($this->RoleGroupID);
	
			if ($RSTotal->FetchRow()->totalRecords > 0) 
			{
				return true;
			}
			
			return false;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException: RoleGroup::CheckDependencies(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception: RoleGroup::CheckDependencies(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
	}

	public function RecordExists()
	{
		try
		{
			$QueryString = '';
			if ($this->RoleGroupID > 0)
			{
				$QueryString = ' AND roleGroupID != ' . $this->DBObject->RealEscapeVariable($this->RoleGroupID);
			}

			$RSTotal = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM role_groups WHERE roleGroupName = :|1' . $QueryString . ';');
			$RSTotal->Execute($this->RoleGroupName);
			
			if ($RSTotal->FetchRow()->totalRecords > 0)
			{
				return true;
			}
			
			return false;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at RoleGroup::RecordExists(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at RoleGroup::RecordExists(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
	}

	public function GetRoleGroupRoles()
	{
		$AllRoleGroupRoles = array();

		try
		{
			$RSRoleGroupRoles = $this->DBObject->Prepare('SELECT roleGroupRoleID, roleID FROM role_group_roles WHERE roleGroupID = :|1;');		
			$RSRoleGroupRoles->Execute($this->RoleGroupID);
			
			if ($RSRoleGroupRoles->Result->num_rows <= 0)
			{
				return $AllRoleGroupRoles;
			}
			
			while($RoleGroupRolesRow = $RSRoleGroupRoles->FetchRow())
			{
				$AllRoleGroupRoles[$RoleGroupRolesRow->roleGroupRoleID] = (int) $RoleGroupRolesRow->roleID;
			}
			
			return $AllRoleGroupRoles;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at RoleGroup::GetRoleGroupRoles(). Stack Trace: ' . $e->getTraceAsString());
			return $AllRoleGroupRoles;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: ApplicationDBException at RoleGroup::GetRoleGroupRoles(). Stack Trace: ' . $e->getTraceAsString());
			return $AllRoleGroupRoles;
		}		
	}
	
	public function SetRoleGroupRoles($SelectedRoleGroupRoles, $CreateUserID)
	{
		try
		{
			if (is_array($SelectedRoleGroupRoles) && count($SelectedRoleGroupRoles) > 0)
			{
				$AlRolesAssigned = '';
				$NewRolesToInsert = '';
				
				foreach ($SelectedRoleGroupRoles as $RoleID => $Value)
				{
					$AlRolesAssigned .= $RoleID . ', ';
					
					$RSSelectedRoles = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM role_group_roles WHERE roleGroupID = :|1 AND roleID = :|2;');
					$RSSelectedRoles->Execute($this->RoleGroupID, $RoleID);
					
					if ($RSSelectedRoles->FetchRow()->totalRecords == 0)
					{
						$NewRolesToInsert .= ' (' . $this->RoleGroupID . ', ' . $RoleID . ', ' . $CreateUserID . ', NOW()), ';
					}
				}
												
				$AlRolesAssigned = substr($AlRolesAssigned, 0, -2);
				
				$this->DBObject->BeginTransaction();
				
				$RSDelete = $this->DBObject->Prepare('DELETE FROM role_group_roles WHERE roleGroupID = :|1 AND roleID NOT IN(' . $AlRolesAssigned . ');');
				$RSDelete->Execute($this->RoleGroupID);
				
				if ($NewRolesToInsert != '')
				{
					$NewRolesToInsert = substr($NewRolesToInsert, 0, -2);
					
					$RSSave = $this->DBObject->Prepare('INSERT INTO role_group_roles (roleGroupID, roleID, createUserID, createDate) VALUES ' . $NewRolesToInsert . ';');
					$RSSave->Execute();
				}
				
				$this->DBObject->CommitTransaction();
			}
			else
			{
				$RSDelete = $this->DBObject->Prepare('DELETE FROM role_group_roles WHERE roleGroupID = :|1;');
				$RSDelete->Execute($this->RoleGroupID);
			}
						
			return true;
		}
		catch (ApplicationDBException $e)
		{
			$this->DBObject->RollBackTransaction();
			error_log('DEBUG: ApplicationDBException at RoleGroup::SetRoleGroupRoles(). Stack Trace: ' . $e->getTraceAsString());
			return false;
		}
		catch (Exception $e)
		{
			$this->DBObject->RollBackTransaction();
			error_log('DEBUG: Exception at RoleGroup::SetRoleGroupRoles(). Stack Trace: ' . $e->getTraceAsString());
			return false;
		}		
	}
	// END OF PUBLIC METHODS	//
	
	// START OF STATIC METHODS	//
	static function GetAllRoleGroups()
    { 
		$AllRoleGroups = array();
		
    	try
        {	
        	$DBConnObject = new DBConnect();
        	$RSSearch = $DBConnObject->Prepare('SELECT rg.*, u.userName FROM role_groups rg 
												INNER JOIN users u ON rg.createUserID = u.userID
												ORDER BY rg.roleGroupName;');
            $RSSearch->Execute();

            if ($RSSearch->Result->num_rows <= 0)
            {
                return $AllRoleGroups;
            }

            while($SearchRow = $RSSearch->FetchRow())
            {
                $AllRoleGroups[$SearchRow->roleGroupID]['RoleGroupName'] = $SearchRow->roleGroupName;
				$AllRoleGroups[$SearchRow->roleGroupID]['IsActive'] = $SearchRow->isActive;
				
				$AllRoleGroups[$SearchRow->roleGroupID]['CreateUserID'] = $SearchRow->createUserID;
				$AllRoleGroups[$SearchRow->roleGroupID]['CreateUserName'] = $SearchRow->userName;
                $AllRoleGroups[$SearchRow->roleGroupID]['CreateDate'] = $SearchRow->createDate;
            }
            
            return $AllRoleGroups;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException::RoleGroup::GetAllRoleGroups(). Stack Trace: '.$e->getTraceAsString());
            return $AllRoleGroups;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception: RoleGroup::GetAllRoleGroups() . Stack Trace: '.$e->getTraceAsString());
            return $AllRoleGroups;
        }
    }

    static function GetActiveRoleGroups()
	{
		$AllRoleGroups = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare("SELECT * FROM role_groups WHERE isActive = 1;");
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllRoleGroups;
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$AllRoleGroups[$SearchRow->roleGroupID] = $SearchRow->roleGroupName;
			}
			
			return $AllRoleGroups;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at RoleGroup::GetActiveRoleGroups(). Stack Trace: '.$e->getTraceAsString());
			return $AllRoleGroups;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at RoleGroup::GetActiveRoleGroups(). Stack Trace: '.$e->getTraceAsString());
			return $AllRoleGroups;
		}		
	}
    
	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//
	private function SaveDetails()
	{
		if ($this->RoleGroupID == 0)
		{
			$RSSave = $this->DBObject->Prepare('INSERT INTO role_groups (roleGroupName, isActive, createUserID, createDate)
														VALUES (:|1, :|2, :|3, NOW());');
		
			$RSSave->Execute($this->RoleGroupName, $this->IsActive, $this->CreateUserID);

			$this->RoleGroupID = $RSSave->LastID;
		}
		else
		{
			$RSUpdate = $this->DBObject->Prepare('UPDATE role_groups
													SET	roleGroupName = :|1
													WHERE roleGroupID = :|2 LIMIT 1;');
													
			$RSUpdate->Execute($this->RoleGroupName, $this->RoleGroupID);
		}
		
		return true;
	}

	private function RemoveRoleGroup()
	{
		if(!isset($this->RoleGroupID)) 
		{
    		throw new ApplicationARException('', APP_AR_ERROR_DELETE_WITHOUT_ID);
		}
		
		$RSDeleteRoleGroup = $this->DBObject->Prepare('DELETE FROM role_groups WHERE roleGroupID = :|1 LIMIT 1;');
		$RSDeleteRoleGroup->Execute($this->RoleGroupID);				
	}
	
	private function GetRoleGroupByID()
	{
		$RSRoleGroup = $this->DBObject->Prepare('SELECT * FROM role_groups WHERE roleGroupID = :|1 LIMIT 1;');
		$RSRoleGroup->Execute($this->RoleGroupID);
		
		$RoleGroupRow = $RSRoleGroup->FetchRow();
		
		$this->SetAttributesFromDB($RoleGroupRow);				
	}
	
	private function SetAttributesFromDB($RoleGroupRow)
	{
		$this->RoleGroupID = $RoleGroupRow->roleGroupID;
		$this->RoleGroupName = $RoleGroupRow->roleGroupName;

		$this->IsActive = $RoleGroupRow->isActive;
		$this->CreateUserID = $RoleGroupRow->createUserID;
		$this->CreateDate = $RoleGroupRow->createDate;
	}	
}
?>
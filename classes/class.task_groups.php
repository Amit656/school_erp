<?php
require_once('class.db_connect.php');
error_reporting(E_ALL);

class TaskGroup
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	
	private $TaskGroupID;
	private $TaskGroupName;
		
	private $IsActive;
	private $CreateUserID;
	private $CreateDate;
	
	// PUBLIC METHODS START HERE	//
	public function __construct($TaskGroupID = 0)
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;
		
		if($TaskGroupID != 0)
		{
			$this->TaskGroupID = $TaskGroupID;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetTaskGroupByID();
		}
		else
		{
			//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
			$this->TaskGroupID = 0;
			$this->TaskGroupName = '';
			
			$this->IsActive = 0;
			$this->CreateUserID = 0;
			$this->CreateDate = '0000-00-00 00:00:00';
		}
	}
	
	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetTaskGroupID()
	{
		return $this->TaskGroupID;
	}
	
	public function GetTaskGroupName()
	{
		return $this->TaskGroupName;
	}
	public function SetTaskGroupName($TaskGroupName)
	{
		$this->TaskGroupName = $TaskGroupName;
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
			$this->RemoveTaskGroup();
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
			$RSTotal = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM tasks WHERE taskGroupID = :|1;');
			$RSTotal->Execute($this->TaskGroupID);
	
			if ($RSTotal->FetchRow()->totalRecords > 0) 
			{
				return true;
			}
			
			return false;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException: TaskGroup::CheckDependencies(). Stack Trace(): ' . $e->getTraceAsString());
			return false;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception: TaskGroup::CheckDependencies(). Stack Trace(): ' . $e->getTraceAsString());
			return false;
		}		
	}

	public function RecordExists()
	{
		try
		{
			$QueryString = '';
			if ($this->TaskGroupID > 0)
			{
				$QueryString = ' AND taskGroupID != ' . $this->DBObject->RealEscapeVariable($this->TaskGroupID);
			}

			$RSTotal = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM task_groups WHERE taskGroupName = :|1' . $QueryString . ';');
			$RSTotal->Execute($this->TaskGroupName);
			
			if ($RSTotal->FetchRow()->totalRecords > 0)
			{
				return true;
			}
			
			return false;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at TaskGroup::RecordExists(). Stack Trace: ' . $e->getTraceAsString());
			return false;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at TaskGroup::RecordExists(). Stack Trace: ' . $e->getTraceAsString());
			return false;
		}
	}
		
	// END OF PUBLIC METHODS	//
	
	// START OF STATIC METHODS	//
	static function GetAllTaskGroups()
	{
		$AllTaskGroups = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT tg.*, u.userName FROM task_groups tg 
												INNER JOIN users u ON tg.createUserID = u.userID
												ORDER BY tg.taskGroupID;');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllTaskGroups;
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$AllTaskGroups[$SearchRow->taskGroupID]['TaskGroupName'] = $SearchRow->taskGroupName;
				$AllTaskGroups[$SearchRow->taskGroupID]['IsActive'] = $SearchRow->isActive;
				
				$AllTaskGroups[$SearchRow->taskGroupID]['CreateUserID'] = $SearchRow->createUserID;
				$AllTaskGroups[$SearchRow->taskGroupID]['CreateUserName'] = $SearchRow->userName;				
				$AllTaskGroups[$SearchRow->taskGroupID]['CreateDate'] = $SearchRow->createDate;
			}
			
			return $AllTaskGroups;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at TaskGroup::GetAllTaskGroups(). Stack Trace: ' . $e->getTraceAsString());
			return $AllTaskGroups;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at TaskGroup::GetAllTaskGroups(). Stack Trace: ' . $e->getTraceAsString());
			return $AllTaskGroups;
		}
	}
	
	static function GetActiveTaskGroups()
	{
		$AllTaskGroups = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT * FROM task_groups WHERE isActive = 1;');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllTaskGroups;
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$AllTaskGroups[$SearchRow->taskGroupID] = $SearchRow->taskGroupName;
			}
			
			return $AllTaskGroups;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at TaskGroup::GetActiveTaskGroups(). Stack Trace: ' . $e->getTraceAsString());
			return $AllTaskGroups;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at TaskGroup::GetActiveTaskGroups(). Stack Trace: ' . $e->getTraceAsString());
			return $AllTaskGroups;
		}		
	}
	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//
	private function SaveDetails()
	{
		if ($this->TaskGroupID == 0)
		{
			$RSSave = $this->DBObject->Prepare('INSERT INTO task_groups (taskGroupName, isActive, createUserID, createDate)
														VALUES (:|1, :|2, :|3, NOW());');
		
			$RSSave->Execute($this->TaskGroupName, $this->IsActive, $this->CreateUserID);
			
			$this->TaskGroupID = $RSSave->LastID;
		}
		else
		{
			$RSUpdate = $this->DBObject->Prepare('UPDATE task_groups
													SET	taskGroupName = :|1,
														isActive = :|2
													WHERE taskGroupID = :|3;');
													
			$RSUpdate->Execute($this->TaskGroupName, $this->IsActive, $this->TaskGroupID);
		}
		
		return true;
	}

	private function RemoveTaskGroup()
	{
		if(!isset($this->TaskGroupID)) 
		{
    		throw new ApplicationARException('', APP_AR_ERROR_DELETE_WITHOUT_ID);
		}
		
		$RSDeleteTaskGroup = $this->DBObject->Prepare('DELETE FROM task_groups WHERE taskGroupID = :|1 LIMIT 1;');
		$RSDeleteTaskGroup->Execute($this->TaskGroupID);				
	}
	
	private function GetTaskGroupByID()
	{
		$RSTaskGroup = $this->DBObject->Prepare('SELECT * FROM task_groups WHERE taskGroupID = :|1 LIMIT 1;');
		$RSTaskGroup->Execute($this->TaskGroupID);
		
		$TaskGroupRow = $RSTaskGroup->FetchRow();
		
		$this->SetAttributesFromDB($TaskGroupRow);				
	}
	
	private function SetAttributesFromDB($TaskGroupRow)
	{
		$this->TaskGroupID = $TaskGroupRow->taskGroupID;
		$this->TaskGroupName = $TaskGroupRow->taskGroupName;
		
		$this->IsActive = $TaskGroupRow->isActive;
		$this->CreateUserID = $TaskGroupRow->createUserID;
		$this->CreateDate = $TaskGroupRow->createDate;
	}	
}
?>
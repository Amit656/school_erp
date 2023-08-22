<?php
require_once('class.db_connect.php');
error_reporting(E_ALL);

class Task
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	
	private $TaskID;
	private $TaskGroupID;
	private $TaskName;
	
	private $IsActive;
	private $CreateUserID;
	private $CreateDate;
	
	// PUBLIC METHODS START HERE	//
	public function __construct($TaskID = 0)
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;
		
		if($TaskID != 0)
		{
			$this->TaskID = $TaskID;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetTaskByID();
		}
		else
		{
			//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
			$this->TaskID = 0;
			$this->TaskGroupID = 0;
			$this->TaskName = '';
			
			$this->IsActive = 0;
			$this->CreateUserID = 0;
			$this->CreateDate = '0000-00-00 00:00:00';
		}
	}
	
	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetTaskID()
	{
		return $this->TaskID;
	}
	
	public function GetTaskGroupID()
	{
		return $this->TaskGroupID;
	}
	public function SetTaskGroupID($TaskGroupID)
	{
		$this->TaskGroupID = $TaskGroupID;
	}
	
	public function GetTaskName()
	{
		return $this->TaskName;
	}
	public function SetTaskName($TaskName)
	{
		$this->TaskName = $TaskName;
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
			$this->RemoveTask();
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
			$RSTotal = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM user_tasks WHERE taskID = :|1;');
			$RSTotal->Execute($this->TaskID);
	
			if ($RSTotal->FetchRow()->totalRecords > 0) 
			{
				return true;
			}
			
			return false;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException: Task::CheckDependencies(). Stack Trace: ' . $e->getTraceAsString());
			return false;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception: Task::CheckDependencies(). Stack Trace: ' . $e->getTraceAsString());
			return false;
		}		
	}

	public function RecordExists()
	{
		try
		{
			$QueryString = '';
			if ($this->TaskID > 0)
			{
				$QueryString = ' AND taskID != ' . $this->DBObject->RealEscapeVariable($this->TaskID);
			}

			$RSTotal = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM tasks WHERE taskName = :|1' . $QueryString . ';');
			$RSTotal->Execute($this->TaskName);
			
			if ($RSTotal->FetchRow()->totalRecords > 0)
			{
				return true;
			}
			
			return false;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at Task::RecordExists(). Stack Trace: ' . $e->getTraceAsString());
			return false;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at Task::RecordExists(). Stack Trace: ' . $e->getTraceAsString());
			return false;
		}
	}
		
	// END OF PUBLIC METHODS	//
	
	// START OF STATIC METHODS	//
	static function GetAllTasks($TaskGroupID)
	{
		$AllTasks = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT t.*, tg.taskGroupName, u.userName FROM tasks t 
												INNER JOIN task_groups tg ON t.taskGroupID = tg.taskGroupID 
												INNER JOIN users u ON t.createUserID = u.userID 
												WHERE t.taskGroupID = :|1 ORDER BY t.taskID;');
			$RSSearch->Execute($TaskGroupID);
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllTasks;
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$AllTasks[$SearchRow->taskID]['TaskGroupID'] = $SearchRow->taskGroupID;
				$AllTasks[$SearchRow->taskID]['TaskGroupName'] = $SearchRow->taskGroupName;

				$AllTasks[$SearchRow->taskID]['TaskName'] = $SearchRow->taskName;
				$AllTasks[$SearchRow->taskID]['IsActive'] = $SearchRow->isActive;
				
				$AllTasks[$SearchRow->taskID]['CreateUserID'] = $SearchRow->createUserID;
				$AllTasks[$SearchRow->taskID]['CreateUserName'] = $SearchRow->userName;				
				$AllTasks[$SearchRow->taskID]['CreateDate'] = $SearchRow->createDate;
			}
			
			return $AllTasks;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at Task::GetAllTasks(). Stack Trace: ' . $e->getTraceAsString());
			return $AllTasks;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at Task::GetAllTasks(). Stack Trace: ' . $e->getTraceAsString());
			return $AllTasks;
		}
	}

	static function GetActiveTasks()
	{
		$AllTasks = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT * FROM tasks WHERE isActive = 1;');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllTasks;
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$AllTasks[$SearchRow->taskID] = $SearchRow->taskName;
			}
			
			return $AllTasks;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at Task::GetActiveTasks(). Stack Trace: ' . $e->getTraceAsString());
			return $AllTasks;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at Task::GetActiveTasks(). Stack Trace: ' . $e->getTraceAsString());
			return $AllTasks;
		}		
	}
	
	static function GetActiveTasksTaskGroupWise()
	{
		$AllTasks = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT taskID, taskGroupID, taskName 
												FROM tasks WHERE isActive = 1 
												ORDER BY taskGroupID, taskName;');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllTasks;
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$AllTasks[$SearchRow->taskGroupID][$SearchRow->taskID] = $SearchRow->taskName;
			}
			
			return $AllTasks;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at Task::GetActiveTasksTaskGroupWise(). Stack Trace: ' . $e->getTraceAsString());
			return $AllTasks;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at Task::GetActiveTasksTaskGroupWise(). Stack Trace: ' . $e->getTraceAsString());
			return $AllTasks;
		}		
	}
	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//
	private function SaveDetails()
	{
		if ($this->TaskID == 0)
		{
			$RSSave = $this->DBObject->Prepare('INSERT INTO tasks (taskGroupID, taskName, isActive, createUserID, createDate)
														VALUES (:|1, :|2, :|3, :|4, NOW());');
		
			$RSSave->Execute($this->TaskGroupID, $this->TaskName, $this->IsActive, $this->CreateUserID);
			
			$this->TaskID = $RSSave->LastID;
		}
		else
		{
			$RSUpdate = $this->DBObject->Prepare('UPDATE tasks
													SET	taskGroupID = :|1,
														taskName = :|2,
														isActive = :|3
													WHERE taskID = :|4;');
													
			$RSUpdate->Execute($this->TaskGroupID, $this->TaskName, $this->IsActive, $this->TaskID);
		}
		
		return true;
	}

	private function RemoveTask()
	{
		if(!isset($this->TaskID)) 
		{
    		throw new ApplicationARException('', APP_AR_ERROR_DELETE_WITHOUT_ID);
		}
		
		$RSDeleteTaskGroup = $this->DBObject->Prepare('DELETE FROM tasks WHERE taskID = :|1 LIMIT 1;');
		$RSDeleteTaskGroup->Execute($this->TaskID);				
	}
	
	private function GetTaskByID()
	{
		$RSTask = $this->DBObject->Prepare('SELECT * FROM tasks WHERE taskID = :|1 LIMIT 1;');
		$RSTask->Execute($this->TaskID);
		
		$TaskRow = $RSTask->FetchRow();
		
		$this->SetAttributesFromDB($TaskRow);				
	}
	
	private function SetAttributesFromDB($TaskRow)
	{
		$this->TaskID = $TaskRow->taskID;
		$this->TaskGroupID = $TaskRow->taskGroupID;
		$this->TaskName = $TaskRow->taskName;
		
		$this->IsActive = $TaskRow->isActive;
		$this->CreateUserID = $TaskRow->createUserID;
		$this->CreateDate = $TaskRow->createDate;
	}	
}
?>
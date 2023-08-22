<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.db_connect.php');
error_reporting(E_ALL);

class Department
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	
	private $DepartmentID;
	private $DepartmentName;

	private $IsActive;
	private $CreateUserID;
	private $CreateDate;
	
	// PUBLIC METHODS START HERE	//
	public function __construct($DepartmentID = 0)
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;
		
		if($DepartmentID != 0)
		{
			$this->DepartmentID = $DepartmentID;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetDepartmentByID();
		}
		else
		{
			//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
			$this->DepartmentID = 0;
			$this->DepartmentName = '';

			$this->IsActive = 0;
			$this->CreateUserID = 0;
			$this->CreateDate = '0000-00-00 00:00:00';
		}
	}
	
	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetDepartmentID()
	{
		return $this->DepartmentID;
	}
	
	public function GetDepartmentName()
	{
		return $this->DepartmentName;
	}
	public function SetDepartmentName($DepartmentName)
	{
		$this->DepartmentName = $DepartmentName;
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
			$this->RemoveDepartment();
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
			$RSTotal = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM role_group_roles WHERE departmentID = :|1;');
			$RSTotal->Execute($this->DepartmentID);
	
			if ($RSTotal->FetchRow()->totalRecords > 0) 
			{
				return true;
			}
			
			return false;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException: Department::CheckDependencies(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception: Department::CheckDependencies(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
	}

	public function RecordExists()
	{
		try
		{
			$QueryString = '';
			
			if ($this->DepartmentID > 0)
			{
				$QueryString = ' AND departmentID != ' . $this->DBObject->RealEscapeVariable($this->DepartmentID);
			}

			$RSTotal = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM aim_departments WHERE departmentName = :|1' . $QueryString . ';');
			$RSTotal->Execute($this->DepartmentName);
			
			if ($RSTotal->FetchRow()->totalRecords > 0)
			{
				return true;
			}
			
			return false;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at Department::RecordExists(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at Department::RecordExists(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
	}

	// END OF PUBLIC METHODS	//
	
	// START OF STATIC METHODS	//
	static function GetAllDepartments()
    { 
		$AllDepartments = array();
		
    	try
        {	
        	$DBConnObject = new DBConnect();
        	$RSSearch = $DBConnObject->Prepare('SELECT ad.*, u.userName AS createUserName FROM aim_departments ad 
													INNER JOIN users u ON ad.createUserID = u.userID 
        											ORDER BY departmentName;');
            $RSSearch->Execute();

            if ($RSSearch->Result->num_rows <= 0)
            {
                return $AllDepartments;
            }

            while($SearchRow = $RSSearch->FetchRow())
            {
                $AllDepartments[$SearchRow->departmentID]['DepartmentName'] = $SearchRow->departmentName;

				$AllDepartments[$SearchRow->departmentID]['IsActive'] = $SearchRow->isActive;
                $AllDepartments[$SearchRow->departmentID]['CreateUserID'] = $SearchRow->createUserID;
                $AllDepartments[$SearchRow->departmentID]['CreateUserName'] = $SearchRow->createUserName;

                $AllDepartments[$SearchRow->departmentID]['CreateDate'] = $SearchRow->createDate;
           }
            
            return $AllDepartments;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException::Department::GetAllDepartments(). Stack Trace: '. $e->getTraceAsString());
            return $AllDepartments;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception: Department::GetAllDepartments() . Stack Trace: '. $e->getTraceAsString());
            return $AllDepartments;
        }
    }

    static function GetActiveDepartments()
	{
		$AllDepartments = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT * FROM aim_departments WHERE isActive = 1;');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllDepartments;
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$AllDepartments[$SearchRow->departmentID] = $SearchRow->departmentName;
			}
			
			return $AllDepartments;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at Department::GetActiveDepartments(). Stack Trace: ' . $e->getTraceAsString());
			return $AllDepartments;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at Department::GetActiveDepartments(). Stack Trace: ' . $e->getTraceAsString());
			return $AllDepartments;
		}		
	}
    
	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//
	private function SaveDetails()
	{
		if ($this->DepartmentID == 0)
		{
			$RSSave = $this->DBObject->Prepare('INSERT INTO aim_departments (departmentName, isActive, createUserID, createDate)
														VALUES (:|1, :|2, :|3, NOW());');
			$RSSave->Execute($this->DepartmentName, $this->IsActive, $this->CreateUserID);

			$this->DepartmentID = $RSSave->LastID;
		}
		else
		{
			$RSUpdate = $this->DBObject->Prepare('UPDATE aim_departments
													SET	departmentName = :|1,
														isActive = :|2
													WHERE departmentID = :|3 LIMIT 1;');
			$RSUpdate->Execute($this->DepartmentName, $this->IsActive, $this->DepartmentID);
		}
		
		return true;
	}

	private function RemoveDepartment()
	{
		if(!isset($this->DepartmentID)) 
		{
    		throw new ApplicationARException('', APP_AR_ERROR_DELETE_WITHOUT_ID);
		}
		
		$RSDeleteDepartment = $this->DBObject->Prepare('DELETE FROM aim_departments WHERE departmentID = :|1 LIMIT 1;');
		$RSDeleteDepartment->Execute($this->DepartmentID);				
	}
	
	private function GetDepartmentByID()
	{
		$RSDepartment = $this->DBObject->Prepare('SELECT * FROM aim_departments WHERE departmentID = :|1 LIMIT 1;');
		$RSDepartment->Execute($this->DepartmentID);
		
		$DepartmentRow = $RSDepartment->FetchRow();
		
		$this->SetAttributesFromDB($DepartmentRow);				
	}
	
	private function SetAttributesFromDB($DepartmentRow)
	{
		$this->DepartmentID = $DepartmentRow->departmentID;
		$this->DepartmentName = $DepartmentRow->departmentName;

		$this->IsActive = $DepartmentRow->isActive;
		$this->CreateUserID = $DepartmentRow->createUserID;
		$this->CreateDate = $DepartmentRow->createDate;
	}	
}
?>
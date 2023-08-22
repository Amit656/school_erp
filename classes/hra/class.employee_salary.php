<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.db_connect.php');
error_reporting(E_ALL);

class EmployeeSalary
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	
	private $EmployeeSalaryID;
	private $BranchStaffID;

	private $SalaryType;
	private $BasicSalary;

	private $IsActive;
	private $CreateUserID;
	private $CreateDate;

	private $SalaryDetails = array();
	
	// PUBLIC METHODS START HERE	//
	public function __construct($EmployeeSalaryID = 0)
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;
		
		if($EmployeeSalaryID != 0)
		{
			$this->EmployeeSalaryID = $EmployeeSalaryID;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetEmployeeSalaryByID();
		}
		else
		{
			//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
			$this->EmployeeSalaryID = 0;
			$this->BranchStaffID = 0;

			$this->SalaryType = '';
			$this->BasicSalary = 0.0;

			$this->IsActive = 0;
			$this->CreateUserID = 0;
			$this->CreateDate = '0000-00-00 00:00:00';

			$this->SalaryDetails = array();
		}
	}
	
	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetEmployeeSalaryID()
	{
		return $this->EmployeeSalaryID;
	}
	
	public function GetBranchStaffID()
	{
		return $this->BranchStaffID;
	}
	public function SetBranchStaffID($BranchStaffID)
	{
		$this->BranchStaffID = $BranchStaffID;
	}
	
	public function GetSalaryType()
	{
		return $this->SalaryType;
	}
	public function SetSalaryType($SalaryType)
	{
		$this->SalaryType = $SalaryType;
	}
	
	public function GetBasicSalary()
	{
		return $this->BasicSalary;
	}
	public function SetBasicSalary($BasicSalary)
	{
		$this->BasicSalary = $BasicSalary;
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

	public function GetSalaryDetails()
	{
		return $this->SalaryDetails;
	}
	public function SetSalaryDetails($SalaryDetails)
	{
		$this->SalaryDetails = $SalaryDetails;
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

	public function Remove()
	{
		try
		{
			$this->RemoveEmployeeSalary();
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
			$RSTotal = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM ahr_employee_salary WHERE employeeSalaryID = :|1;');
			$RSTotal->Execute($this->EmployeeSalaryID);
	
			if ($RSTotal->FetchRow()->totalRecords > 0) 
			{
				return true;
			}
			
			return false;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException: EmployeeSalary::CheckDependencies(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception: EmployeeSalary::CheckDependencies(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
	}

	public function FillSalaryDetails($StaffCategory)
	{
		try
		{
			$RSSearch = $this->DBObject->Prepare('SELECT aesd.*, asp.salaryPartType FROM ahr_employee_salary_details aesd
														INNER JOIN ahr_employee_salary aes ON  aesd.employeeSalaryID = aes.employeeSalaryID
														INNER JOIN asa_branch_staff abs ON  aes.branchStaffID = abs.branchStaffID
														INNER JOIN ahr_salary_parts asp ON  aesd.salaryPartID = asp.salaryPartID
														WHERE abs.staffCategory = :|1;');
            $RSSearch->Execute($StaffCategory);
	
			if ($RSSearch->Result->num_rows <= 0) 
			{
				return $this->SalaryDetails;
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$this->SalaryDetails[$SearchRow->salaryPartID]['EmployeeSalaryID'] = $SearchRow->employeeSalaryID;
				$this->SalaryDetails[$SearchRow->salaryPartID]['SalaryPartType'] = $SearchRow->salaryPartType;
				$this->SalaryDetails[$SearchRow->salaryPartID]['SalaryPartID'] = $SearchRow->salaryPartID;
				$this->SalaryDetails[$SearchRow->salaryPartID]['PercentageOfBasic'] = $SearchRow->percentageOfBasic;
				$this->SalaryDetails[$SearchRow->salaryPartID]['Amount'] = $SearchRow->salaryPartAmount;
			}

			return true;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException: EmployeeSalary::FillSalaryDetails(). Stack Trace: ' . $e->getTraceAsString());
			return false;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception: EmployeeSalary::FillSalaryDetails(). Stack Trace: ' . $e->getTraceAsString());
			return false;
		}
	}

	public function SalaryStructureExists()
	{
		try
		{
			$QueryString = '';

			if ($this->EmployeeSalaryID > 0)
			{
				$QueryString = ' AND employeeSalaryID != ' . $this->DBObject->RealEscapeVariable($this->EmployeeSalaryID);
			}

			$RSTotal = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM ahr_employee_salary WHERE branchStaffID = :|1' . $QueryString . ';');
			$RSTotal->Execute($this->BranchStaffID);
			
			if ($RSTotal->FetchRow()->totalRecords > 0)
			{
				return true;
			}
			
			return false;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at EmployeeSalary::SalaryStructureExists(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at EmployeeSalary::SalaryStructureExists(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
	}

	// END OF PUBLIC METHODS	//
	
	// START OF STATIC METHODS	//
	static function GetAllEmployeeSalaries($StaffCategory)
    { 
		$AllEmployeeSalaries = array();
		
    	try
        {	
        	$DBConnObject = new DBConnect();

        	$RSEmployeesSalary = $DBConnObject->Prepare('SELECT aes.*, abs.firstName, abs.lastName, u.userName AS createUserName 
															FROM ahr_employee_salary aes 
															INNER JOIN asa_branch_staff abs ON  aes.branchStaffID = abs.branchStaffID 
															INNER JOIN users u ON aes.createUserID = u.userID 
															WHERE abs.staffCategory = :|1;');
			$RSEmployeesSalary->Execute($StaffCategory);

			if($RSEmployeesSalary->Result->num_rows <= 0)
			{
				return $AllEmployeeSalaries;
			}

			while($SearchRow = $RSEmployeesSalary->FetchRow())
			{
				$AllEmployeeSalaries[$SearchRow->employeeSalaryID]['FirstName'] = $SearchRow->firstName;
				$AllEmployeeSalaries[$SearchRow->employeeSalaryID]['LastName'] = $SearchRow->lastName;

				$AllEmployeeSalaries[$SearchRow->employeeSalaryID]['SalaryType'] = $SearchRow->salaryType;
				$AllEmployeeSalaries[$SearchRow->employeeSalaryID]['BasicSalary'] = $SearchRow->basicSalary;

				$AllEmployeeSalaries[$SearchRow->employeeSalaryID]['IsActive'] = $SearchRow->isActive;

				$AllEmployeeSalaries[$SearchRow->employeeSalaryID]['CreateUserID'] = $SearchRow->createUserID;
				$AllEmployeeSalaries[$SearchRow->employeeSalaryID]['CreateUserName'] = $SearchRow->createUserName;
				
				$AllEmployeeSalaries[$SearchRow->employeeSalaryID]['CreateDate'] = $SearchRow->createDate;
			}
            
            return $AllEmployeeSalaries;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException::EmployeeSalary::GetAllEmployeeSalaries(). Stack Trace: '.$e->getTraceAsString());
            return $AllEmployeeSalaries;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception: EmployeeSalary::GetAllEmployeeSalaries() . Stack Trace: '.$e->getTraceAsString());
            return $AllEmployeeSalaries;
        }
    }

    static function GetAllEmployeeSalaryDetails($StaffCategory)
    { 
		$AllEmployeeSalaryDetails = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT aesd.*, asp.salaryPartType FROM ahr_employee_salary_details aesd
														INNER JOIN ahr_employee_salary aes ON  aesd.employeeSalaryID = aes.employeeSalaryID
														INNER JOIN asa_branch_staff abs ON  aes.branchStaffID = abs.branchStaffID
														INNER JOIN ahr_salary_parts asp ON  aesd.salaryPartID = asp.salaryPartID
														WHERE abs.staffCategory = :|1;');
			$RSSearch->Execute($StaffCategory);
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllEmployeeSalaryDetails;
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$AllEmployeeSalaryDetails[$SearchRow->employeeSalaryDetailID]['EmployeeSalaryID'] = $SearchRow->employeeSalaryID;
				$AllEmployeeSalaryDetails[$SearchRow->employeeSalaryDetailID]['SalaryPartType'] = $SearchRow->salaryPartType;
				$AllEmployeeSalaryDetails[$SearchRow->employeeSalaryDetailID]['SalaryPartID'] = $SearchRow->salaryPartID;
				$AllEmployeeSalaryDetails[$SearchRow->employeeSalaryDetailID]['PercentageOfBasic'] = $SearchRow->percentageOfBasic;
				$AllEmployeeSalaryDetails[$SearchRow->employeeSalaryDetailID]['Amount'] = $SearchRow->salaryPartAmount;
			}
			
			return $AllEmployeeSalaryDetails;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at EmployeeSalary::GetAllEmployeeSalaryDetails(). Stack Trace: ' . $e->getTraceAsString());
			return $AllEmployeeSalaryDetails;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at EmployeeSalary::GetAllEmployeeSalaryDetails(). Stack Trace: ' . $e->getTraceAsString());
			return $AllEmployeeSalaryDetails;
		}
    }

    static function GetActiveEmployeeSalaries()
	{
		$AllEmployeeSalaries = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT * FROM ahr_employee_salary WHERE isActive = 1;');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllEmployeeSalaries;
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$AllEmployeeSalaries[$SearchRow->employeeSalaryID] = $SearchRow->branchStaffID;
			}
			
			return $AllEmployeeSalaries;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at EmployeeSalary::GetActiveEmployeeSalaries(). Stack Trace: ' . $e->getTraceAsString());
			return $AllEmployeeSalaries;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at EmployeeSalary::GetActiveEmployeeSalaries(). Stack Trace: ' . $e->getTraceAsString());
			return $AllEmployeeSalaries;
		}		
	}

	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//
	private function SaveDetails()
	{
		if ($this->EmployeeSalaryID == 0)
		{
			$RSBasicSalary = $this->DBObject->Prepare('INSERT INTO ahr_employee_salary (branchStaffID, salaryType, basicSalary, IsActive, 
																							createUserID, createDate)
																						VALUES (:|1,:|2,:|3, :|4, :|5, NOW());');
			$RSBasicSalary->Execute($this->BranchStaffID, $this->SalaryType, $this->BasicSalary, $this->IsActive, $this->CreateUserID);

			$this->EmployeeSalaryID = $RSBasicSalary->LastID;

			foreach ($this->SalaryDetails as $SalaryPartID => $SalaryDetailsDetails) 
			{
				$RSSalaryDetailsDetails = $this->DBObject->Prepare('INSERT INTO ahr_employee_salary_details (employeeSalaryID, salaryPartID, 
																											percentageOfBasic, salaryPartAmount)
																						VALUES (:|1, :|2, :|3, :|4);');
				$RSSalaryDetailsDetails->Execute($this->EmployeeSalaryID, $SalaryPartID, $SalaryDetailsDetails['PercentageOfBasic'], $SalaryDetailsDetails['Amount']);
			}
		}
		else
		{
			$RSUpdate = $this->DBObject->Prepare('UPDATE ahr_employee_salary
													SET	branchStaffID = :|1,
														salaryType = :|2,
														basicSalary = :|3,
														IsActive = :|4
													WHERE employeeSalaryID = :|5;');
			$RSUpdate->Execute($this->BranchStaffID, $this->SalaryType, $this->BasicSalary, $this->IsActive, $this->EmployeeSalaryID);

			foreach ($this->SalaryDetails as $SalaryPartID => $SalaryDetailsDetails) 
			{
				$RSUpdate = $this->DBObject->Prepare('UPDATE ahr_employee_salary_details
															SET percentageOfBasic = :|1,
																salaryPartAmount = :|2
															WHERE employeeSalaryID = :|3 AND salaryPartID = :|4;');
				$RSUpdate->Execute($SalaryDetailsDetails['PercentageOfBasic'], $SalaryDetailsDetails['Amount'], 
														$this->EmployeeSalaryID, $SalaryPartID);
			}
		}
		
		return true;
	}

	private function RemoveEmployeeSalary()
	{
		if(!isset($this->EmployeeSalaryID)) 
		{
    		throw new ApplicationARException('', APP_AR_ERROR_DELETE_WITHOUT_ID);
		}
		
		$RSDeleteEmployeeSalary = $this->DBObject->Prepare('DELETE FROM ahr_employee_salary WHERE employeeSalaryID = :|1 LIMIT 1;');
		$RSDeleteEmployeeSalary->Execute($this->EmployeeSalaryID);				
	}
	
	private function GetEmployeeSalaryByID()
	{
		$RSEmployeeSalary = $this->DBObject->Prepare('SELECT * FROM ahr_employee_salary WHERE employeeSalaryID = :|1 LIMIT 1;');
		$RSEmployeeSalary->Execute($this->EmployeeSalaryID);
		
		$EmployeeSalaryRow = $RSEmployeeSalary->FetchRow();
		
		$this->SetAttributesFromDB($EmployeeSalaryRow);				
	}
	
	private function SetAttributesFromDB($EmployeeSalaryRow)
	{
		$this->EmployeeSalaryID = $EmployeeSalaryRow->employeeSalaryID;
		$this->BranchStaffID = $EmployeeSalaryRow->branchStaffID;

		$this->SalaryType = $EmployeeSalaryRow->salaryType;
		$this->BasicSalary = $EmployeeSalaryRow->basicSalary;

		$this->IsActive = $EmployeeSalaryRow->isActive;
		$this->CreateUserID = $EmployeeSalaryRow->createUserID;
		$this->CreateDate = $EmployeeSalaryRow->createDate;
	}	
}
?>
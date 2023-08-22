<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.db_connect.php');
error_reporting(E_ALL);

class SalaryPart
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	
	private $SalaryPartID;

	private $SalaryPartType;
	private $SalaryPartName;

	private $Priority;

	private $IsActive;
	private $CreateUserID;
	private $CreateDate;
	
	// PUBLIC METHODS START HERE	//
	public function __construct($SalaryPartID = 0)
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;
		
		if($SalaryPartID != 0)
		{
			$this->SalaryPartID = $SalaryPartID;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetSalaryPartByID();
		}
		else
		{
			//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
			$this->SalaryPartID = 0;
			$this->SalaryPartType = 'Allowance';
			$this->SalaryPartName = '';

			$this->Priority = 0;

			$this->IsActive = 0;
			$this->CreateUserID = 0;
			$this->CreateDate = '0000-00-00 00:00:00';
		}
	}
	
	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetSalaryPartID()
	{
		return $this->SalaryPartID;
	}
	
	public function GetSalaryPartType()
	{
		return $this->SalaryPartType;
	}
	public function SetSalaryPartType($SalaryPartType)
	{
		$this->SalaryPartType = $SalaryPartType;
	}
	
	public function GetSalaryPartName()
	{
		return $this->SalaryPartName;
	}
	public function SetSalaryPartName($SalaryPartName)
	{
		$this->SalaryPartName = $SalaryPartName;
	}
	
	public function GetPriority()
	{
		return $this->Priority;
	}
	public function SetPriority($Priority)
	{
		$this->Priority = $Priority;
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
            $this->RemoveSalaryPart();
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
			$RSTotal = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM ahr_employee_salary_details WHERE salaryPartID = :|1;');
			$RSTotal->Execute($this->SalaryPartID);
	
			if ($RSTotal->FetchRow()->totalRecords > 0) 
			{
				return true;
			}
			
			return false;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException: SalaryPart::CheckDependencies(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception: SalaryPart::CheckDependencies(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
	}

	public function RecordExists()
	{
		try
		{
			$QueryString = '';
			
			if ($this->SalaryPartID > 0)
			{
				$QueryString = ' AND salaryPartID != ' . $this->DBObject->RealEscapeVariable($this->SalaryPartID);
			}

			$RSTotal = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM ahr_salary_parts WHERE salaryPartName = :|1' . $QueryString . ';');
			$RSTotal->Execute($this->SalaryPartID);
			
			if ($RSTotal->FetchRow()->totalRecords > 0)
			{
				return true;
			}
			
			return false;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at SalaryPart::RecordExists(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at SalaryPart::RecordExists(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
	}

	// END OF PUBLIC METHODS	//
	
	// START OF STATIC METHODS	//
	static function GetAllSalaryParts($SalaryPartType)
	{	
		$AllSalaryParts = array();
       
        try
        {
	        $DBConnObject = new DBConnect();

	        $RSSearch = $DBConnObject->Prepare('SELECT asp.*, u.userName AS createUserName FROM ahr_salary_parts asp
														INNER JOIN users u ON asp.createUserID = u.userID 
														WHERE salaryPartType = :|1 ORDER BY asp.priority;');
            $RSSearch->Execute($SalaryPartType);

            if ($RSSearch->Result->num_rows <= 0)
            {
                return $AllSalaryParts;
            }

            while($SearchRow = $RSSearch->FetchRow())
            {
		        $AllSalaryParts[$SearchRow->salaryPartID]['SalaryPartType'] = $SearchRow->salaryPartType;
		        $AllSalaryParts[$SearchRow->salaryPartID]['SalaryPartName'] = $SearchRow->salaryPartName;

		        $AllSalaryParts[$SearchRow->salaryPartID]['Priority'] = $SearchRow->priority;
		        $AllSalaryParts[$SearchRow->salaryPartID]['IsActive'] = $SearchRow->isActive;

		        $AllSalaryParts[$SearchRow->salaryPartID]['CreateUserID'] = $SearchRow->createUserID;
		        $AllSalaryParts[$SearchRow->salaryPartID]['CreateUserName'] = $SearchRow->createUserName;
				$AllSalaryParts[$SearchRow->salaryPartID]['CreateDate'] = $SearchRow->createDate;
            }

            return $AllSalaryParts;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at SalaryPart::GetAllSalaryParts(). Stack Trace: ' . $e->getTraceAsString());
            return $AllSalaryParts;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception at SalaryPart::GetAllSalaryParts(). Stack Trace: ' . $e->getTraceAsString());
            return $AllSalaryParts;
        }
	}

	static function GetActiveSalaryParts($SalaryPartType = '')
	{
		$AllSalaryParts = array();
		
		try
		{	
			$DBConnObject = new DBConnect();

			$QueryString = '';
			
			if ($SalaryPartType != '')
			{
				$QueryString = ' AND salaryPartType = ' . $DBConnObject->RealEscapeVariable($SalaryPartType);
			}

	        $RSSearch = $DBConnObject->Prepare('SELECT salaryPartID, salaryPartType, salaryPartName FROM ahr_salary_parts WHERE isActive = 1' . $QueryString . ' ORDER BY priority;');
            $RSSearch->Execute();

            if ($RSSearch->Result->num_rows <= 0)
            {
                return $AllSalaryParts;
            }

            while($SearchRow = $RSSearch->FetchRow())
            {	
            	if ($SalaryPartType != '')
            	{
            		$AllSalaryParts[$SearchRow->salaryPartID] = $SearchRow->salaryPartName;
            		continue;
            	}

		        $AllSalaryParts[$SearchRow->salaryPartID]['SalaryPartType'] = $SearchRow->salaryPartType;
		        $AllSalaryParts[$SearchRow->salaryPartID]['SalaryPartName'] = $SearchRow->salaryPartName;
            }
							
			return $AllSalaryParts;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at SalaryPart::GetActiveSalaryParts(). Stack Trace: ' . $e->getTraceAsString());
			return $AllSalaryParts;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at SalaryPart::GetActiveSalaryParts(). Stack Trace: ' . $e->getTraceAsString());
			return $AllSalaryParts;
		}		
	}

	static function UpdateSalaryPartPriorities($Priorities)
	{
		try
		{
			$DBConnObject = new DBConnect();
			
			$DBConnObject->BeginTransaction();

			foreach ($Priorities as $SalaryPartID => $Priority)
			{
				$RSUpdate = $DBConnObject->Prepare('UPDATE ahr_salary_parts SET priority = :|1 WHERE salaryPartID = :|2 LIMIT 1;');
				$RSUpdate->Execute($Priority, $SalaryPartID);
			}
			
			$DBConnObject->CommitTransaction();
			return true;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException: SalaryPart::UpdateSalaryPartPriorities(). Stack Trace: ' . $e->getTraceAsString());
			$DBConnObject->RollBackTransaction();
			return false;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception: SalaryPart::UpdateSalaryPartPriorities(). Stack Trace: ' . $e->getTraceAsString());
			$DBConnObject->RollBackTransaction();
			return false;
		}
	}

	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//
	private function SaveDetails()
	{
		if ($this->SalaryPartID == 0)
		{
			$RSSave = $this->DBObject->Prepare('INSERT INTO ahr_salary_parts(salaryPartType, salaryPartName, priority, 
																				isActive, createUserID, createDate)
																VALUES (:|1, :|2, :|3, :|4, :|5, NOW());');
		
			$RSSave->Execute($this->SalaryPartType, $this->SalaryPartName, $this->Priority, 
								$this->IsActive, $this->CreateUserID);
			
			$this->SalaryPartID = $RSSave->LastID;
		}
		else
		{
			$RSUpdate = $this->DBObject->Prepare('UPDATE ahr_salary_parts
													SET	salaryPartType = :|1,
														salaryPartName = :|2,
														priority = :|3,
														isActive = :|4
													WHERE salaryPartID = :|5;');
													
			$RSUpdate->Execute($this->SalaryPartType, $this->SalaryPartName, $this->Priority, 
								$this->IsActive, $this->SalaryPartID);
		}
		
		return true;
	}

	private function RemoveSalaryPart()
	{
		if(!isset($this->SalaryPartID)) 
        {
            throw new ApplicationARException('', APP_AR_ERROR_DELETE_WITHOUT_ID);
        }
        
        $RSDeleteAddedClass = $this->DBObject->Prepare('DELETE FROM ahr_salary_parts WHERE salaryPartID = :|1 LIMIT 1;');
        $RSDeleteAddedClass->Execute($this->SalaryPartID);
	}
	
	private function GetSalaryPartByID()
	{
		$RSSalaryPart = $this->DBObject->Prepare('SELECT * FROM ahr_salary_parts WHERE salaryPartID = :|1 LIMIT 1;');
		$RSSalaryPart->Execute($this->SalaryPartID);
		
		$SalaryPartRow = $RSSalaryPart->FetchRow();
		
		$this->SetAttributesFromDB($SalaryPartRow);				
	}
	
	private function SetAttributesFromDB($SalaryPartRow)
	{
		$this->SalaryPartID = $SalaryPartRow->salaryPartID;
		$this->SalaryPartType = $SalaryPartRow->salaryPartType;
		$this->SalaryPartName = $SalaryPartRow->salaryPartName;

		$this->Priority = $SalaryPartRow->priority;

		$this->IsActive = $SalaryPartRow->isActive;
		$this->CreateUserID = $SalaryPartRow->createUserID;
		$this->CreateDate = $SalaryPartRow->createDate;
	}	
}
?>
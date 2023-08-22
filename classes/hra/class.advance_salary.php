<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.db_connect.php');
error_reporting(E_ALL);

class AdvanceSalary
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	
	private $AdvanceSalaryID;
	private $BranchStaffID;

	private $AdvanceAmount;
	private $PaymentMode;

	private $AdvanceType;
	private $NoOfInstallments;

	private $CreateUserID;
	private $CreateDate;

	private $AdvanceSalaryInstallments = array();
	
	// PUBLIC METHODS START HERE	//
	public function __construct($AdvanceSalaryID = 0)
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;
		
		if($AdvanceSalaryID != 0)
		{
			$this->AdvanceSalaryID = $AdvanceSalaryID;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetAdvanceSalaryByID();
		}
		else
		{
			//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
			$this->AdvanceSalaryID = 0;
			$this->BranchStaffID = 0;

			$this->AdvanceAmount = 	0.00;
			$this->PaymentMode = 'Cheque';

			$this->AdvanceType = 'GeneralAdvance';
			$this->NoOfInstallments = 1;

			$this->CreateUserID = 0;
			$this->CreateDate = '0000-00-00 00:00:00';

			$this->AdvanceSalaryInstallments = array();
		}
	}
	
	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetAdvanceSalaryID()
	{
		return $this->AdvanceSalaryID;
	}
	
	public function GetBranchStaffID()
	{
		return $this->BranchStaffID;
	}
	public function SetBranchStaffID($BranchStaffID)
	{
		$this->BranchStaffID = $BranchStaffID;
	}

	public function GetAdvanceAmount()
	{
		return $this->AdvanceAmount;
	}
	public function SetAdvanceAmount($AdvanceAmount)
	{
		$this->AdvanceAmount = $AdvanceAmount;
	}
	
	public function GetPaymentMode()
	{
		return $this->PaymentMode;
	}
	public function SetPaymentMode($PaymentMode)
	{
		$this->PaymentMode = $PaymentMode;
	}

	public function GetAdvanceType()
	{
		return $this->AdvanceType;
	}
	public function SetAdvanceType($AdvanceType)
	{
		$this->AdvanceType = $AdvanceType;
	}
	
	public function GetNoOfInstallments()
	{
		return $this->NoOfInstallments;
	}
	public function SetNoOfInstallments($NoOfInstallments)
	{
		$this->NoOfInstallments = $NoOfInstallments;
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

	public function GetAdvanceSalaryInstallments()
	{
		return $this->AdvanceSalaryInstallments;
	}
	public function SetAdvanceSalaryInstallments($AdvanceSalaryInstallments)
	{
		$this->AdvanceSalaryInstallments = $AdvanceSalaryInstallments;
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
			$this->DBObject->BeginTransaction();
			if ($this->RemoveAdvanceSalary())
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
	
	public function CheckDependencies()
	{
		try
		{
			$RSTotal = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM ahr_advance_salary_installments WHERE advanceSalaryID = :|1 AND isDeducted = 1;');
			$RSTotal->Execute($this->AdvanceSalaryID);
	
			if ($RSTotal->FetchRow()->totalRecords > 0) 
			{
				return true;
			}
			
			return false;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException: AdvanceSalary::CheckDependencies(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception: AdvanceSalary::CheckDependencies(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
	}

	public function FillAdvanceSalaryInstallments($TobeDeductOnly = false)
	{
		try
		{
			$Query = '';

			if (!$TobeDeductOnly)
			{
				$Query = ' AND isDeducted = 0';
			}

			$RSSearch = $this->DBObject->Prepare('SELECT * FROM ahr_advance_salary_installments WHERE advanceSalaryID = :|1 ' . $Query . ';');
			$RSSearch->Execute($this->AdvanceSalaryID);
	
			if ($RSSearch->Result->num_rows <= 0) 
			{
				return true;
			}
			
			while($SearchRow = $RSSearch->FetchRow())
            {	
                $this->AdvanceSalaryInstallments[$SearchRow->advanceSalaryInstallmentID]['DeductionMonth'] = $SearchRow->deductionMonth;
                $this->AdvanceSalaryInstallments[$SearchRow->advanceSalaryInstallmentID]['Year'] = $SearchRow->year;
                $this->AdvanceSalaryInstallments[$SearchRow->advanceSalaryInstallmentID]['DeductionAmount'] = $SearchRow->deductionAmount;
                $this->AdvanceSalaryInstallments[$SearchRow->advanceSalaryInstallmentID]['IsDeducted'] = $SearchRow->isDeducted;
            }
			return true;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException: AdvanceSalary::FillAdvanceSalaryInstallments(). Stack Trace: ' . $e->getTraceAsString());
			return false;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception: AdvanceSalary::FillAdvanceSalaryInstallments(). Stack Trace: ' . $e->getTraceAsString());
			return false;
		}
	}

	// END OF PUBLIC METHODS	//
	
	// START OF STATIC METHODS	//
	static function GetAllAdvanceSalaries($StaffCategory)
    { 
		$AllAdvanceSalaries = array();
		
    	try
        {	
        	$DBConnObject = new DBConnect();

        	$RSSearch = $DBConnObject->Prepare('SELECT aas.*, abs.firstName, abs.lastName, 
        										(SELECT SUM(deductionAmount) FROM ahr_advance_salary_installments WHERE advanceSalaryID = aas.advanceSalaryID AND isDeducted = 1) AS totalPaidAmount, 
												    u.userName AS createUserName FROM ahr_advance_salary aas 
												INNER JOIN ahr_advance_salary_installments assi ON aas.advanceSalaryID = assi.advanceSalaryID
												INNER JOIN asa_branch_staff abs ON aas.branchStaffID = abs.branchStaffID
												INNER JOIN users u ON aas.createUserID = u.userID
												WHERE abs.staffCategory = :|1 
												GROUP BY aas.advanceSalaryID ORDER BY aas.createDate DESC');
            $RSSearch->Execute($StaffCategory);


            if ($RSSearch->Result->num_rows <= 0)
            {
                return $AllAdvanceSalaries;
            }

            while($SearchRow = $RSSearch->FetchRow())
            {
                $AllAdvanceSalaries[$SearchRow->advanceSalaryID]['BranchStaffID'] = $SearchRow->branchStaffID;
                $AllAdvanceSalaries[$SearchRow->advanceSalaryID]['FirstName'] = $SearchRow->firstName;
                $AllAdvanceSalaries[$SearchRow->advanceSalaryID]['LastName'] = $SearchRow->lastName;

                $AllAdvanceSalaries[$SearchRow->advanceSalaryID]['AdvanceAmount'] = $SearchRow->advanceAmount;
                $AllAdvanceSalaries[$SearchRow->advanceSalaryID]['TotalPaidAmount'] = $SearchRow->totalPaidAmount;
                $AllAdvanceSalaries[$SearchRow->advanceSalaryID]['PaymentMode'] = $SearchRow->paymentMode;
                
                $AllAdvanceSalaries[$SearchRow->advanceSalaryID]['AdvanceType'] = $SearchRow->advanceType;
                $AllAdvanceSalaries[$SearchRow->advanceSalaryID]['NoOfInstallments'] = $SearchRow->noOfInstallments;

                $AllAdvanceSalaries[$SearchRow->advanceSalaryID]['CreateUserID'] = $SearchRow->createUserID;
                $AllAdvanceSalaries[$SearchRow->advanceSalaryID]['CreateUserName'] = $SearchRow->createUserName;

                $AllAdvanceSalaries[$SearchRow->advanceSalaryID]['CreateDate'] = $SearchRow->createDate;
           }
            
            return $AllAdvanceSalaries;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException::AdvanceSalary::GetAllAdvanceSalaries(). Stack Trace: ' . $e->getTraceAsString());
            return $AllAdvanceSalaries;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception: AdvanceSalary::GetAllAdvanceSalaries() . Stack Trace: ' . $e->getTraceAsString());
            return $AllAdvanceSalaries;
        }
    }

	static function GetAllMonths()
	{
		$AllMonths = array();
		
    	try
        {	
        	$DBConnObject = new DBConnect();
        	$RSSearch = $DBConnObject->Prepare('SELECT * FROM financial_year_months 
        										WHERE priority >= (SELECT priority FROM financial_year_months WHERE monthShortName = DATE_FORMAT(CURDATE(), "%b")) 
        										ORDER BY priority;');
            $RSSearch->Execute();

            if ($RSSearch->Result->num_rows <= 0)
            {
                return $AllMonths;
            }

            while($SearchRow = $RSSearch->FetchRow())
            {
                $AllMonths[$SearchRow->financialYearMonthID] = $SearchRow->monthShortName;
           	}
            
            return $AllMonths;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException::AdvanceSalary::GetAllMonths(). Stack Trace: ' . $e->getTraceAsString());
            return $AllMonths;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception: AdvanceSalary::GetAllMonths() . Stack Trace: ' . $e->getTraceAsString());
            return $AllMonths;
        }
	}
    
	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//
	private function SaveDetails()
	{
		if ($this->AdvanceSalaryID == 0)
		{
			$RSAdvanceSalarySave = $this->DBObject->Prepare('INSERT INTO ahr_advance_salary (branchStaffID, advanceAmount, paymentMode, advanceType, noOfInstallments, 	
																										createUserID, createDate)
																			VALUES (:|1, :|2, :|3, :|4, :|5, :|6, NOW());');
			$RSAdvanceSalarySave->Execute($this->BranchStaffID, $this->AdvanceAmount, $this->PaymentMode, $this->AdvanceType, $this->NoOfInstallments, $this->CreateUserID);

			$this->AdvanceSalaryID = $RSAdvanceSalarySave->LastID;

			foreach ($this->AdvanceSalaryInstallments as $TimeStamp => $AdvanceSalaryInstallmentDetails) 
			{
				$RSAdvanceSalaryInstallmentsSave = $this->DBObject->Prepare('INSERT INTO ahr_advance_salary_installments (advanceSalaryID, deductionMonth, year, deductionAmount)
																							VALUES (:|1, :|2, :|3, :|4);');
				$RSAdvanceSalaryInstallmentsSave->Execute($this->AdvanceSalaryID, $AdvanceSalaryInstallmentDetails['Month'], $AdvanceSalaryInstallmentDetails['Year'], $AdvanceSalaryInstallmentDetails['Amount']);	
			}
		}
		else
		{
			$RSUpdate = $this->DBObject->Prepare('UPDATE ahr_advance_salary
													SET	branchStaffID = :|1,
														advanceAmount = :|2,
														paymentMode = :|3,
														advanceType = :|4,
														noOfInstallments = :|5
													WHERE advanceSalaryID = :|6 LIMIT 1;');
			$RSUpdate->Execute($this->BranchStaffID, $this->AdvanceAmount, $this->PaymentMode, $this->AdvanceType, $this->NoOfInstallments, $this->AdvanceSalaryID);

			$RSDeleteAdvanceSalaryInstallments = $this->DBObject->Prepare('DELETE FROM ahr_advance_salary_installments WHERE advanceSalaryID = :|1 AND isDeducted = 0;');
			$RSDeleteAdvanceSalaryInstallments->Execute($this->AdvanceSalaryID);

			foreach ($this->AdvanceSalaryInstallments as $TimeStamp => $AdvanceSalaryInstallmentDetails) 
			{
				$RSAdvanceSalaryInstallmentsSave = $this->DBObject->Prepare('INSERT INTO ahr_advance_salary_installments (advanceSalaryID, deductionMonth, year, deductionAmount)
																							VALUES (:|1, :|2, :|3, :|4);');
				$RSAdvanceSalaryInstallmentsSave->Execute($this->AdvanceSalaryID, $AdvanceSalaryInstallmentDetails['Month'], $AdvanceSalaryInstallmentDetails['Year'], $AdvanceSalaryInstallmentDetails['Amount']);	
			}
		}
		
		return true;
	}

	private function RemoveAdvanceSalary()
	{
		if(!isset($this->AdvanceSalaryID)) 
		{
    		throw new ApplicationARException('', APP_AR_ERROR_DELETE_WITHOUT_ID);
		}
		
		$RSDeleteAdvanceSalaryInstallments = $this->DBObject->Prepare('DELETE FROM ahr_advance_salary_installments WHERE advanceSalaryID = :|1;');
		$RSDeleteAdvanceSalaryInstallments->Execute($this->AdvanceSalaryID);

		$RSDeleteAdvanceSalary = $this->DBObject->Prepare('DELETE FROM ahr_advance_salary WHERE advanceSalaryID = :|1 LIMIT 1;');
		$RSDeleteAdvanceSalary->Execute($this->AdvanceSalaryID);	

		return true;			
	}
	
	private function GetAdvanceSalaryByID()
	{
		$RSAdvanceSalary = $this->DBObject->Prepare('SELECT * FROM ahr_advance_salary WHERE advanceSalaryID = :|1 LIMIT 1;');
		$RSAdvanceSalary->Execute($this->AdvanceSalaryID);
		
		$AdvanceSalaryRow = $RSAdvanceSalary->FetchRow();
		
		$this->SetAttributesFromDB($AdvanceSalaryRow);				
	}
	
	private function SetAttributesFromDB($AdvanceSalaryRow)
	{
		$this->AdvanceSalaryID = $AdvanceSalaryRow->advanceSalaryID;
		$this->BranchStaffID = $AdvanceSalaryRow->branchStaffID;

		$this->AdvanceAmount = $AdvanceSalaryRow->advanceAmount;
		$this->PaymentMode = $AdvanceSalaryRow->paymentMode;

		$this->AdvanceType = $AdvanceSalaryRow->advanceType;
		$this->NoOfInstallments = $AdvanceSalaryRow->noOfInstallments;

		$this->CreateUserID = $AdvanceSalaryRow->createUserID;
		$this->CreateDate = $AdvanceSalaryRow->createDate;
	}	
}
?>
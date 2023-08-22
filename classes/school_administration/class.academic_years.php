<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.db_connect.php');
error_reporting(E_ALL);

class AcademicYear
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	
	private $AcademicYearID;
	private $StartDate;
	private $EndDate;

	private $IsCurrentYear;
	private $CreateUserID;
	private $CreateDate;
	
	// PUBLIC METHODS START HERE	//
	public function __construct($AcademicYearID = 0)
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;
		
		if($AcademicYearID != 0)
		{
			$this->AcademicYearID = $AcademicYearID;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetAcademicYearByID();
		}
		else
		{
			//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
			$this->AcademicYearID = 0;
			$this->StartDate = '0000-00-00';
			$this->EndDate = '0000-00-00';

			$this->IsCurrentYear = 0;
			$this->CreateUserID = 0;
			$this->CreateDate = '0000-00-00 00:00:00';
		}
	}
	
	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetAcademicYearID()
	{
		return $this->AcademicYearID;
	}
	
	public function GetStartDate()
	{
		return $this->StartDate;
	}
	public function SetStartDate($StartDate)
	{
		$this->StartDate = $StartDate;
	}

	public function GetEndDate()
	{
		return $this->EndDate;
	}
	public function SetEndDate($EndDate)
	{
		$this->EndDate = $EndDate;
	}

	public function GetIsCurrentYear()
	{
		return $this->IsCurrentYear;
	}
	public function SetIsCurrentYear($IsCurrentYear)
	{
		$this->IsCurrentYear = $IsCurrentYear;
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
            $this->RemoveAcademicYear();
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
            $RSAcademicYearInClassesCount = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM asa_classes WHERE academicYearID = :|1;');
            $RSAcademicYearInClassesCount->Execute($this->AcademicYearID);

            if ($RSAcademicYearInClassesCount->FetchRow()->totalRecords > 0) 
            {
                return true;
            }

			$RSAcademicYearInStudentYearlyDetailsCount = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM asa_student_yearly_details WHERE academicYearID = :|1;');
            $RSAcademicYearInStudentYearlyDetailsCount->Execute($this->AcademicYearID);

            if ($RSAcademicYearInStudentYearlyDetailsCount->FetchRow()->totalRecords > 0) 
            {
                return true;
            }

            return false;
        }
        catch (ApplicationDBException $e)
        {
        	error_log('DEBUG: ApplicationDBException at AcademicYear::CheckDependencies(). Stack Trace: '.$e->getTraceAsString());
            return false;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: ApplicationDBException at AcademicYear::CheckDependencies(). Stack Trace: '.$e->getTraceAsString());
            return false;
        }       
    }

    public function RecordExists()
	{
		try
		{
			$QueryString = '';
			if ($this->AcademicYearID > 0)
			{
				$QueryString = ' AND academicYearID != ' . $this->DBObject->RealEscapeVariable($this->AcademicYearID);
			}

			$RSTotal = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM asa_academic_years WHERE startDate = :|1 AND endDate = :|2' . $QueryString . ';');
			$RSTotal->Execute($this->StartDate, $this->EndDate);
			
			if ($RSTotal->FetchRow()->totalRecords > 0)
			{
				return true;
			}
			
			return false;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at AcademicYear::RecordExists(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at AcademicYear::RecordExists(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
	}

	// END OF PUBLIC METHODS	//
	
	// START OF STATIC METHODS	//
	static function GetAllAcademicYears()
	{
		$AllAcademicYears = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT aay.*, u.userName AS createUserName FROM asa_academic_years aay 
												INNER JOIN users u ON aay.createUserID = u.userID
												ORDER BY aay.academicYearID;');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllAcademicYears;
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$AllAcademicYears[$SearchRow->academicYearID]['StartDate'] = $SearchRow->startDate;
				$AllAcademicYears[$SearchRow->academicYearID]['EndDate'] = $SearchRow->endDate;
				$AllAcademicYears[$SearchRow->academicYearID]['IsCurrentYear'] = $SearchRow->isCurrentYear;

				$AllAcademicYears[$SearchRow->academicYearID]['CreateUserID'] = $SearchRow->createUserID;
				$AllAcademicYears[$SearchRow->academicYearID]['CreateUserName'] = $SearchRow->createUserName;
				
				$AllAcademicYears[$SearchRow->academicYearID]['CreateDate'] = $SearchRow->createDate;
			}
			
			return $AllAcademicYears;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at AcademicYear::GetAllAcademicYears(). Stack Trace: '.$e->getTraceAsString());
			return $AllAcademicYears;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at AcademicYear::GetAllAcademicYears(). Stack Trace: '.$e->getTraceAsString());
			return $AllAcademicYears;
		}
	}

	static function GetCurrentAcademicYear(&$CurrentAcademicYearName = '', &$StartDate = '', &$EndDate = '')
	{
		$CurrentAcademicYearID = 0;
		$CurrentAcademicYearName = '';
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT academicYearID, startDate, endDate, CONCAT( YEAR(startDate), \'-\', DATE_FORMAT(endDate, \'%y\')) AS academicYearName FROM asa_academic_years WHERE isCurrentYear = 1;');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows != 1)
			{
				error_log('Critical Error: No current academic year returned from asa_academic_years table.');
				return $CurrentAcademicYearID;
			}
			
			$SearchAcademicYearRow = $RSSearch->FetchRow();
			$CurrentAcademicYearID = $SearchAcademicYearRow->academicYearID;

			$CurrentAcademicYearName = $SearchAcademicYearRow->academicYearName;
			$StartDate = $SearchAcademicYearRow->startDate;
			$EndDate = $SearchAcademicYearRow->endDate;

			return $CurrentAcademicYearID;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at AcademicYear::GetCurrentAcademicYear(). Stack Trace: ' . $e->getTraceAsString());
			return $CurrentAcademicYearID;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at AcademicYear::GetCurrentAcademicYear(). Stack Trace: ' . $e->getTraceAsString());
			return $CurrentAcademicYearID;
		}		
	}
		
	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//
	private function SaveDetails()
	{
		if ($this->AcademicYearID == 0)
		{
			$RSSave = $this->DBObject->Prepare('INSERT INTO asa_academic_years (startDate, endDate, createUserID, createDate)
														VALUES (:|1, :|2, :|3, NOW());');
		
			$RSSave->Execute($this->StartDate, $this->EndDate, $this->CreateUserID);
			
			$this->AcademicYearID = $RSSave->LastID;
		}
		else
		{
			$RSUpdate = $this->DBObject->Prepare('UPDATE asa_academic_years
													SET	startDate = :|1,
														endDate = :|2,
														isCurrentYear = :|3
													WHERE academicYearID = :|4 LIMIT 1;');
													
			$RSUpdate->Execute($this->StartDate, $this->EndDate, $this->IsCurrentYear, $this->AcademicYearID);
			
			if ($this->IsCurrentYear == 1)
			{
			    $RSUpdate = $this->DBObject->Prepare('UPDATE asa_academic_years
    													SET	isCurrentYear = 0
    													WHERE academicYearID != :|1 ;');
    													
    			$RSUpdate->Execute($this->AcademicYearID);
			}
		}
		
		return true;
	}
	
	private function RemoveAcademicYear()
    {
        if(!isset($this->AcademicYearID)) 
        {
            throw new ApplicationARException('', APP_AR_ERROR_DELETE_WITHOUT_ID);
        }
        
        $RSDeleteAcademicYear = $this->DBObject->Prepare('DELETE FROM asa_academic_years WHERE academicYearID = :|1 LIMIT 1;');
        $RSDeleteAcademicYear->Execute($this->AcademicYearID);                
    }

	private function GetAcademicYearByID()
	{
		$RSAcademicYear = $this->DBObject->Prepare('SELECT * FROM asa_academic_years WHERE academicYearID = :|1 LIMIT 1;');
		$RSAcademicYear->Execute($this->AcademicYearID);
		
		$AcademicYearRow = $RSAcademicYear->FetchRow();
		
		$this->SetAttributesFromDB($AcademicYearRow);				
	}
	
	private function SetAttributesFromDB($AcademicYearRow)
	{
		$this->AcademicYearID = $AcademicYearRow->academicYearID;
		$this->StartDate = $AcademicYearRow->startDate;
		$this->EndDate = $AcademicYearRow->endDate;

		$this->IsCurrentYear = $AcademicYearRow->isCurrentYear;
		$this->CreateUserID = $AcademicYearRow->createUserID;
		$this->CreateDate = $AcademicYearRow->createDate;
	}	

}
?>
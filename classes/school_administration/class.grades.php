<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.db_connect.php');
error_reporting(E_ALL);

class Grade
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	
	private $GradeID;
	private $Grade;

	private $FromPercentage;
	private $ToPercentage;
	private $IsActive;
	
	private $CreateUserID;
	private $CreateDate;
	
	// PUBLIC METHODS START HERE	//
	public function __construct($GradeID = 0)
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;
		
		if($GradeID != 0)
		{
			$this->GradeID = $GradeID;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetGradeByID();
		}
		else
		{
			//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
			$this->GradeID = 0;
			$this->Grade = '';

			$this->FromPercentage = '';
			$this->ToPercentage = '';
			$this->IsActive = 0;
			
			$this->CreateUserID = 0;
			$this->CreateDate = '0000-00-00 00:00:00';
		}
	}
	
	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetGradeID()
	{
		return $this->GradeID;
	}
	
	public function GetGrade()
	{
		return $this->Grade;
	}
	public function SetGrade($Grade)
	{
		$this->Grade = $Grade;
	}
	
	public function GetFromPercentage()
	{
		return $this->FromPercentage;
	}
	public function SetFromPercentage($FromPercentage)
	{
		$this->FromPercentage = $FromPercentage;
	}
	
	public function GetToPercentage()
	{
		return $this->ToPercentage;
	}
	public function SetToPercentage($ToPercentage)
	{
		$this->ToPercentage = $ToPercentage;
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
            $this->RemoveGrade();
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

	public function RecordExists()
	{
		try
		{
			$QueryString = '';

			if ($this->GradeID > 0)
			{
				$QueryString = ' AND gradeID != ' . $this->DBObject->RealEscapeVariable($this->GradeID);
			}

			$RSTotal = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM asa_grades WHERE grade = :|1' . $QueryString . ';');
			$RSTotal->Execute($this->Grade);
			
			if ($RSTotal->FetchRow()->totalRecords > 0)
			{
				return true;
			}
			
			return false;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at Grade::RecordExists(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at Grade::RecordExists(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
	}

	public function CheckDependencies()
    {
        try
        {  
            $RSCount = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords 
												FROM aem_student_exam_marks 
												WHERE gradeID = :|1 ;');
            $RSCount->Execute($this->GradeID);

            if ($RSCount->FetchRow()->totalRecords > 0)
            {
            	return true;
            }
            
            return false;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException: Grade::CheckDependencies. Stack Trace: ' . $e->getTraceAsString());
            return false;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception: Grade::CheckDependencies. Stack Trace: ' . $e->getTraceAsString());
            return false;
        }       
    }
		
	// END OF PUBLIC METHODS	//
	
	// START OF STATIC METHODS	//
	static function GetAllGrades()
    {
        $AllGrades = array();

        try
        {
            $DBConnObject = new DBConnect();

            $RSSearch = $DBConnObject->Prepare('SELECT ag.*, u.userName AS createUserName 
            									FROM asa_grades ag 
												INNER JOIN users u ON ag.createUserID = u.userID;');
            $RSSearch->Execute();

            if ($RSSearch->Result->num_rows <= 0)
            {
                return $AllGrades;
            }

            while($SearchRow = $RSSearch->FetchRow())
            {
                $AllGrades[$SearchRow->gradeID]['Grade'] = $SearchRow->grade;
                $AllGrades[$SearchRow->gradeID]['FromPercentage'] = $SearchRow->fromPercentage;
                $AllGrades[$SearchRow->gradeID]['ToPercentage'] = $SearchRow->toPercentage;

		        $AllGrades[$SearchRow->gradeID]['IsActive'] = $SearchRow->isActive;
		        $AllGrades[$SearchRow->gradeID]['CreateUserName'] = $SearchRow->createUserName;
		        $AllGrades[$SearchRow->gradeID]['CreateUserID'] = $SearchRow->createUserID;
		        $AllGrades[$SearchRow->gradeID]['CreateDate'] = $SearchRow->createDate;
            }

            return $AllGrades;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at Grade::GetAllGrades(). Stack Trace: ' . $e->getTraceAsString());
            return $AllGrades;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception at Grade::GetAllGrades(). Stack Trace: ' . $e->getTraceAsString());
            return $AllGrades;
        }
	}

	static function GetActiveGrades()
	{
		$AllGrades = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT gradeID, grade FROM asa_grades WHERE isActive = 1;');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllGrades;
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$AllGrades[$SearchRow->gradeID] = $SearchRow->grade;
			}
			
			return $AllGrades;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at Grade::GetActiveGrades(). Stack Trace: ' . $e->getTraceAsString());
			return $AllGrades;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at Grade::GetActiveGrades(). Stack Trace: ' . $e->getTraceAsString());
			return $AllGrades;
		}		
	}
	
	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//
	private function SaveDetails()
	{
		if ($this->GradeID == 0)
		{
			$RSSave = $this->DBObject->Prepare('INSERT INTO asa_grades (grade, fromPercentage, toPercentage, isActive, createUserID, createDate)
														VALUES (:|1, :|2, :|3, :|4, :|5, NOW());');
		
			$RSSave->Execute($this->Grade, $this->FromPercentage, $this->ToPercentage, $this->IsActive, $this->CreateUserID);
			
			$this->GradeID = $RSSave->LastID;
		}
		else
		{
			$RSUpdate = $this->DBObject->Prepare('UPDATE asa_grades
													SET	grade = :|1,
														fromPercentage = :|2,
														toPercentage = :|3,
														isActive = :|4
													WHERE gradeID = :|5 LIMIT 1;');
													
			$RSUpdate->Execute($this->Grade, $this->FromPercentage, $this->ToPercentage, $this->IsActive, $this->GradeID);
		}
		
		return true;
	}

	private function RemoveGrade()
    {
        if (!isset($this->GradeID)) 
        {
            throw new ApplicationARException('', APP_AR_ERROR_DELETE_WITHOUT_ID);
        }
        
        $RSDeleteGrade = $this->DBObject->Prepare('DELETE FROM asa_grades WHERE gradeID = :|1 LIMIT 1;');
        $RSDeleteGrade->Execute($this->GradeID);                
    }
	
	private function GetGradeByID()
	{
		$RSGrade = $this->DBObject->Prepare('SELECT * FROM asa_grades WHERE gradeID = :|1 LIMIT 1;');
		$RSGrade->Execute($this->GradeID);
		
		$GradeRow = $RSGrade->FetchRow();
		
		$this->SetAttributesFromDB($GradeRow);				
	}
	
	private function SetAttributesFromDB($GradeRow)
	{
		$this->GradeID = $GradeRow->gradeID;
		$this->Grade = $GradeRow->grade;
		$this->FromPercentage = $GradeRow->fromPercentage;
		$this->ToPercentage = $GradeRow->toPercentage;
		$this->IsActive = $GradeRow->isActive;
		
		$this->CreateUserID = $GradeRow->createUserID;
		$this->CreateDate = $GradeRow->createDate;
	}	
}
?>
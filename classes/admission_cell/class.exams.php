<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.db_connect.php');
error_reporting(E_ALL);

class Exam
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	
	private $ExamID;
	private $ExamName;

	private $ExamDate;
	private $ExamTime;
	
	private $ExamDuration;
	private $MaximumMarks;

	private $IsActive;
	private $CreateUserID;
	private $CreateDate;
	
	// PUBLIC METHODS START HERE	//
	public function __construct($ExamID = 0)
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;
		
		if($ExamID != 0)
		{
			$this->ExamID = $ExamID;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetExamByID();
		}
		else
		{
			//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
			$this->ExamID = 0;
			$this->ExamName = '';

			$this->ExamDate = '0000-00-00';
			$this->ExamTime = '00:00:00';

			$this->ExamDuration = 0;
			$this->MaximumMarks = 0;

			$this->IsActive = 0;
			$this->CreateUserID = 0;
			$this->CreateDate = '0000-00-00 00:00:00';
		}
	}
	
	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetExamID()
	{
		return $this->ExamID;
	}
	
	public function GetExamName()
	{
		return $this->ExamName;
	}
	public function SetExamName($ExamName)
	{
		$this->ExamName = $ExamName;
	}

	public function GetExamDate()
	{
		return $this->ExamDate;
	}
	public function SetExamDate($ExamDate)
	{
		$this->ExamDate = $ExamDate;
	}
	
	public function GetExamTime()
	{
		return $this->ExamTime;
	}
	public function SetExamTime($ExamTime)
	{
		$this->ExamTime = $ExamTime;
	}	
	
	public function GetExamDuration()
	{
		return $this->ExamDuration;
	}
	public function SetExamDuration($ExamDuration)
	{
		$this->ExamDuration = $ExamDuration;
	}

	public function GetMaximumMarks()
	{
		return $this->MaximumMarks;
	}
	public function SetMaximumMarks($MaximumMarks)
	{
		$this->MaximumMarks = $MaximumMarks;
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
			$this->RemoveExam();
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
			$RSTotal = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM role_group_roles WHERE examID = :|1;');
			$RSTotal->Execute($this->ExamID);
	
			if ($RSTotal->FetchRow()->totalRecords > 0) 
			{
				return true;
			}
			
			return false;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException: Exam::CheckDependencies(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception: Exam::CheckDependencies(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
	}

	public function RecordExists()
	{
		try
		{
			$QueryString = '';
			
			if ($this->ExamID > 0)
			{
				$QueryString = ' AND examID != ' . $this->DBObject->RealEscapeVariable($this->ExamID);
			}

			$RSTotal = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM aad_exams WHERE examName = :|1' . $QueryString . ';');
			$RSTotal->Execute($this->ExamName);
			
			if ($RSTotal->FetchRow()->totalRecords > 0)
			{
				return true;
			}
			
			return false;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at Exam::RecordExists(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at Exam::RecordExists(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
	}

	// END OF PUBLIC METHODS	//
	
	// START OF STATIC METHODS	//
	static function GetAllExams()
    { 
		$AllExams = array();
		
    	try
        {	
        	$DBConnObject = new DBConnect();
        	$RSSearch = $DBConnObject->Prepare('SELECT aae.*, u.userName AS createUserName FROM aad_exams aae 
													INNER JOIN users u ON aae.createUserID = u.userID 
        											ORDER BY examName;');
            $RSSearch->Execute();

            if ($RSSearch->Result->num_rows <= 0)
            {
                return $AllExams;
            }

            while($SearchRow = $RSSearch->FetchRow())
            {
                $AllExams[$SearchRow->examID]['ExamName'] = $SearchRow->examName;
                $AllExams[$SearchRow->examID]['ExamDate'] = $SearchRow->examDate;
                $AllExams[$SearchRow->examID]['ExamTime'] = $SearchRow->examTime;
                $AllExams[$SearchRow->examID]['ExamDuration'] = $SearchRow->examDuration;
                $AllExams[$SearchRow->examID]['MaximumMarks'] = $SearchRow->maximumMarks;

				$AllExams[$SearchRow->examID]['IsActive'] = $SearchRow->isActive;
                $AllExams[$SearchRow->examID]['CreateUserID'] = $SearchRow->createUserID;
                $AllExams[$SearchRow->examID]['CreateUserName'] = $SearchRow->createUserName;

                $AllExams[$SearchRow->examID]['CreateDate'] = $SearchRow->createDate;
           }
            
            return $AllExams;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException::Exam::GetAllExams(). Stack Trace: '. $e->getTraceAsString());
            return $AllExams;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception: Exam::GetAllExams() . Stack Trace: '. $e->getTraceAsString());
            return $AllExams;
        }
    }

    static function GetActiveExams()
	{
		$AllExams = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT examID, examName FROM aad_exams WHERE isActive = 1;');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllExams;
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$AllExams[$SearchRow->examID] = $SearchRow->examName;
			}
			
			return $AllExams;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at Exam::GetActiveExams(). Stack Trace: ' . $e->getTraceAsString());
			return $AllExams;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at Exam::GetActiveExams(). Stack Trace: ' . $e->getTraceAsString());
			return $AllExams;
		}		
	}
    
	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//
	private function SaveDetails()
	{
		if ($this->ExamID == 0)
		{
			$RSSave = $this->DBObject->Prepare('INSERT INTO aad_exams (examName, examDate, examTime, examDuration, maximumMarks, 
																			isActive, createUserID, createDate)
														VALUES (:|1, :|2, :|3, :|4, :|5, :|6, :|7, NOW());');
			$RSSave->Execute($this->ExamName, $this->ExamDate, $this->ExamTime, $this->ExamDuration, $this->MaximumMarks, $this->IsActive, $this->CreateUserID);

			$this->ExamID = $RSSave->LastID;
		}
		else
		{
			$RSUpdate = $this->DBObject->Prepare('UPDATE aad_exams
													SET	examName = :|1,
														examDate = :|2,
														examTime = :|3,
														examDuration = :|4,
														maximumMarks = :|5,
														isActive = :|6
													WHERE examID = :|7 LIMIT 1;');
			$RSUpdate->Execute($this->ExamName, $this->ExamDate, $this->ExamTime, $this->ExamDuration, $this->MaximumMarks, $this->IsActive, $this->ExamID);
		}
		
		return true;
	}

	private function RemoveExam()
	{
		if(!isset($this->ExamID)) 
		{
    		throw new ApplicationARException('', APP_AR_ERROR_DELETE_WITHOUT_ID);
		}
		
		$RSDeleteExam = $this->DBObject->Prepare('DELETE FROM aad_exams WHERE examID = :|1 LIMIT 1;');
		$RSDeleteExam->Execute($this->ExamID);			
	}
	
	private function GetExamByID()
	{
		$RSExam = $this->DBObject->Prepare('SELECT * FROM aad_exams WHERE examID = :|1 LIMIT 1;');
		$RSExam->Execute($this->ExamID);
		
		$ExamRow = $RSExam->FetchRow();
		
		$this->SetAttributesFromDB($ExamRow);				
	}
	
	private function SetAttributesFromDB($ExamRow)
	{
		$this->ExamID = $ExamRow->examID;
		$this->ExamName = $ExamRow->examName;

		$this->ExamDate = $ExamRow->examDate;
		$this->ExamTime = $ExamRow->examTime;

		$this->ExamDuration = $ExamRow->examDuration;
		$this->MaximumMarks = $ExamRow->maximumMarks;

		$this->IsActive = $ExamRow->isActive;
		$this->CreateUserID = $ExamRow->createUserID;
		$this->CreateDate = $ExamRow->createDate;
	}	
}
?>
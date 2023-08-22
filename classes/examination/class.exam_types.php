<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.db_connect.php');
error_reporting(E_ALL);

class ExamType
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	
	private $ExamTypeID;
	private $ExamType;

	private $IsActive;

	private $CreateUserID;
	private $CreateDate;
	
	// PUBLIC METHODS START HERE	//
	public function __construct($ExamTypeID = 0)
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;
		
		if($ExamTypeID != 0)
		{
			$this->ExamTypeID = $ExamTypeID;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetExamTypeByID();
		}
		else
		{
			//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
			$this->ExamTypeID = 0;
			$this->ExamType = '';

			$this->IsActive = 0;

			$this->CreateUserID = 0;
			$this->CreateDate = '0000-00-00 00:00:00';
		}
	}
	
	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetExamTypeID()
	{
		return $this->ExamTypeID;
	}
	
	public function GetExamType()
	{
		return $this->ExamType;
	}
	public function SetExamType($ExamType)
	{
		$this->ExamType = $ExamType;
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
            $this->RemoveExamType();
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
            $RSExamTypeCount = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM aem_exams WHERE examTypeID = :|1;');
            $RSExamTypeCount->Execute($this->ExamTypeID);

            if ($RSExamTypeCount->FetchRow()->totalRecords > 0) 
            {
                return true;
            }

            return false;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at ExamType::CheckDependencies(). Stack Trace: ' . $e->getTraceAsString());
            return true;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: ApplicationDBException at ExamType::CheckDependencies(). Stack Trace: ' . $e->getTraceAsString());
            return true;
        }       
    }
		
	// END OF PUBLIC METHODS	//
		
	// START OF STATIC METHODS	//
	static function GetAllExamTypes()
	{
		$AllExamTypes = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT aet.*, u.userName AS createUserName FROM aem_exam_types aet
												INNER JOIN users u ON aet.createUserID = u.userID 
												ORDER BY aet.examType;');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllExamTypes; 
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$AllExamTypes[$SearchRow->examTypeID]['ExamType'] = $SearchRow->examType;

				$AllExamTypes[$SearchRow->examTypeID]['IsActive'] = $SearchRow->isActive;

				$AllExamTypes[$SearchRow->examTypeID]['CreateUserID'] = $SearchRow->createUserID;
				$AllExamTypes[$SearchRow->examTypeID]['CreateUserName'] = $SearchRow->createUserName;
				$AllExamTypes[$SearchRow->examTypeID]['CreateDate'] = $SearchRow->createDate;
			}
			
			return $AllExamTypes;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at ExamType::GetAllExamTypes(). Stack Trace: ' . $e->getTraceAsString());
			return $AllExamTypes;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at ExamType::GetAllExamTypes(). Stack Trace: ' . $e->getTraceAsString());
			return $AllExamTypes;
		}
	}

	static function GetActiveExamTypes()
	{
		$ActiveExamTypes = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT * FROM aem_exam_types WHERE isActive = 1;');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $ActiveExamTypes;
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$ActiveExamTypes[$SearchRow->examTypeID] = $SearchRow->examType;				
			}
			
			return $ActiveExamTypes;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at ExamType::GetActiveExamTypes(). Stack Trace: ' . $e->getTraceAsString());
			return $ActiveExamTypes;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at ExamType::GetActiveExamTypes(). Stack Trace: ' . $e->getTraceAsString());
			return $ActiveExamTypes;
		}		
	}
	
	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//
	private function SaveDetails()
	{
		if ($this->ExamTypeID == 0)
		{
			$RSSave = $this->DBObject->Prepare('INSERT INTO aem_exam_types (examType, isActive, createUserID, createDate)
														VALUES (:|1, :|2, :|3, NOW());');
		
			$RSSave->Execute($this->ExamType, $this->IsActive, $this->CreateUserID);
			
			$this->ExamTypeID = $RSSave->LastID;
		}
		else
		{
			$RSUpdate = $this->DBObject->Prepare('UPDATE aem_exam_types
													SET	examType = :|1,
														isActive = :|2
													WHERE examTypeID = :|3;');
													
			$RSUpdate->Execute($this->ExamType, $this->IsActive, $this->ExamTypeID);
		}
		
		return true;
	}

	private function RemoveExamType()
    {
        if(!isset($this->ExamTypeID)) 
        {
            throw new ApplicationARException('', APP_AR_ERROR_DELETE_WITHOUT_ID);
        }
        
        $RSDeleteExamType = $this->DBObject->Prepare('DELETE FROM aem_exam_types WHERE examTypeID = :|1 LIMIT 1;');
        $RSDeleteExamType->Execute($this->ExamTypeID);  

        return true;              
    }
	
	private function GetExamTypeByID()
	{
		$RSExamType = $this->DBObject->Prepare('SELECT * FROM aem_exam_types WHERE examTypeID = :|1 LIMIT 1;');
		$RSExamType->Execute($this->ExamTypeID);
		
		$ExamTypeRow = $RSExamType->FetchRow();
		
		$this->SetAttributesFromDB($ExamTypeRow);				
	}
	
	private function SetAttributesFromDB($ExamTypeRow)
	{
		$this->ExamTypeID = $ExamTypeRow->examTypeID;
		$this->ExamType = $ExamTypeRow->examType;

		$this->IsActive = $ExamTypeRow->isActive;

		$this->CreateUserID = $ExamTypeRow->createUserID;
		$this->CreateDate = $ExamTypeRow->createDate;
	}	
}
?>
<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.db_connect.php');
error_reporting(E_ALL);

class Chapter
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	
	private $ChapterID;
	private $AcademicYearID;

	private $MasterChapterID;
	private $ClassSubjectID;
	private $ChapterName;
	
	private $Priority;
	private $IsActive;

	private $CreateUserID;
	private $CreateDate;

	// PUBLIC METHODS START HERE	//
	public function __construct($ChapterID = 0)
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;
		
		if($ChapterID != 0)
		{
			$this->ChapterID = $ChapterID;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetChapterByID();
		}
		else
		{
			//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
			$this->ChapterID = 0;
			$this->AcademicYearID = 0;

			$this->MasterChapterID = 0;
			$this->ClassSubjectID = 0;
			$this->ChapterName = '';

			$this->Priority = 0;
			$this->IsActive = 0;

			$this->CreateUserID = 0;
			$this->CreateDate = '0000-00-00 00:00:00';
		}
	}
	
	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetChapterID()
	{
		return $this->ChapterID;
	}
	
	public function GetAcademicYearID()
	{
		return $this->AcademicYearID;
	}
	public function SetAcademicYearID($AcademicYearID)
	{
		$this->AcademicYearID = $AcademicYearID;
	}

	public function GetMasterChapterID()
	{
		return $this->MasterChapterID;
	}
	public function SetMasterChapterID($MasterChapterID)
	{
		$this->MasterChapterID = $MasterChapterID;
	}

	public function GetClassSubjectID()
	{
		return $this->ClassSubjectID;
	}
	public function SetClassSubjectID($ClassSubjectID)
	{
		$this->ClassSubjectID = $ClassSubjectID;
	}

	public function GetChapterName()
	{
		return $this->ChapterName;
	}
	public function SetChapterName($ChapterName)
	{
		$this->ChapterName = $ChapterName;
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
            $this->RemoveChapter();
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
            $RSChapterCount = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM aas_chapter_topics WHERE chapterID = :|1;');
            $RSChapterCount->Execute($this->ChapterID);

            if ($RSChapterCount->FetchRow()->totalRecords > 0) 
            {
                return true;
            }

            return false;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at Chapter::CheckDependencies(). Stack Trace: ' . $e->getTraceAsString());
            return true;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: ApplicationDBException at Chapter::CheckDependencies(). Stack Trace: ' . $e->getTraceAsString());
            return true;
        }       
    }
		
	// END OF PUBLIC METHODS	//
		
	// START OF STATIC METHODS	//
	static function SearchChapters(&$TotalRecords = 0, $GetTotalsOnly = false, $Filters = array(), $Start = 0, $Limit = 100)
	{
		$AllChapters = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$Conditions = array();
			
			if (count($Filters) > 0)
			{	
				if (!empty($Filters['ClassID']))
				{
					$Conditions[] = 'acl.classID = '.$DBConnObject->RealEscapeVariable($Filters['ClassID']);
				}			
				if (!empty($Filters['ClassSubjectID']))
				{
					$Conditions[] = 'ac.classSubjectID = '.$DBConnObject->RealEscapeVariable($Filters['ClassSubjectID']);
				}
				
				if (!empty($Filters['ActiveStatus']))
				{
					if ($Filters['ActiveStatus'] == 1) //active records
					{
						$Conditions[] = 'ac.isActive = 1';
					}
					else if ($Filters['ActiveStatus'] == 2) //non active records
					{
						$Conditions[] = 'ac.isActive = 0';
					}
				}
			}
			
			$QueryString = '';

			if (count($Conditions) > 0)
			{
				$QueryString = implode(') AND (', $Conditions);
				
				$QueryString = ' WHERE (' . $QueryString . ')';
			}

			if ($GetTotalsOnly)
			{
				$RSTotal = $DBConnObject->Prepare('SELECT COUNT(*) AS totalRecords 
													FROM aas_chapters ac
													INNER JOIN asa_class_subjects acs ON acs.classSubjectID = ac.classSubjectID 
													INNER JOIN asa_subject_master asm ON asm.subjectID = acs.subjectID 
													INNER JOIN asa_classes acl ON acl.classID = acs.classID 
													INNER JOIN users u ON ac.createUserID = u.userID 
													'. $QueryString .';');
				$RSTotal->Execute();
				
				$TotalRecords = $RSTotal->FetchRow()->totalRecords;
				return;
			}
			
			$RSSearch = $DBConnObject->Prepare('SELECT ac.*, asm.subject, acl.className, u.userName AS createUserName 
												FROM aas_chapters ac
												INNER JOIN asa_class_subjects acs ON acs.classSubjectID = ac.classSubjectID 
												INNER JOIN asa_subject_master asm ON asm.subjectID = acs.subjectID 
												INNER JOIN asa_classes acl ON acl.classID = acs.classID 
												INNER JOIN users u ON ac.createUserID = u.userID 
												'. $QueryString .' 
												ORDER BY ac.priority LIMIT ' . (int) $Start . ', ' . (int) $Limit . ';');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllChapters; 
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$AllChapters[$SearchRow->chapterID]['AcademicYearID'] = $SearchRow->academicYearID;
				$AllChapters[$SearchRow->chapterID]['ChapterName'] = $SearchRow->chapterName;
				$AllChapters[$SearchRow->chapterID]['SubjectName'] = $SearchRow->subject;
				$AllChapters[$SearchRow->chapterID]['ClassName'] = $SearchRow->className;

				$AllChapters[$SearchRow->chapterID]['Priority'] = $SearchRow->priority;
				$AllChapters[$SearchRow->chapterID]['IsActive'] = $SearchRow->isActive;

				$AllChapters[$SearchRow->chapterID]['CreateUserID'] = $SearchRow->createUserID;
				$AllChapters[$SearchRow->chapterID]['CreateUserName'] = $SearchRow->createUserName;
				$AllChapters[$SearchRow->chapterID]['CreateDate'] = $SearchRow->createDate;
			}
			
			return $AllChapters;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at Chapter::SearchChapters(). Stack Trace: ' . $e->getTraceAsString());
			return $AllChapters;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at Chapter::SearchChapters(). Stack Trace: ' . $e->getTraceAsString());
			return $AllChapters;
		}
	}

	static function GetChapterByClassSubject($ClassSubjectID, $GetNameOnly = true)
	{
		$ChaptersList = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT * FROM aas_chapters WHERE classSubjectID = :|1 AND isActive = 1;');
			$RSSearch->Execute($ClassSubjectID);
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $ChaptersList;
			}
			
			if ($GetNameOnly) 
			{
				while($SearchRow = $RSSearch->FetchRow())
				{
					$ChaptersList[$SearchRow->chapterID] = $SearchRow->chapterName;
				}
				
				return $ChaptersList;
			}

			while($SearchRow = $RSSearch->FetchRow())
			{
				$ChaptersList[$SearchRow->chapterID]['ChapterName'] = $SearchRow->chapterName;
			}
			
			return $ChaptersList;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at Chapter::GetChapterByClassSubject(). Stack Trace: ' . $e->getTraceAsString());
			return $ChaptersList;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at Chapter::GetChapterByClassSubject(). Stack Trace: ' . $e->getTraceAsString());
			return $ChaptersList;
		}		
	}

	static function GetUnScheduledChapters($ClassSubjectID)
	{
		$UnScheduledChaptersList = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT ac.chapterID, ac.chapterName FROM aas_chapter_topics act 
												INNER JOIN aas_chapters ac ON ac.chapterID = act.chapterID
												WHERE act.chapterTopicID  NOT IN (SELECT chapterTopicID FROM aas_topic_schedule_details)
												AND ac.classSubjectID = :|1 AND ac.isActive = 1
												ORDER BY ac.priority;');
			$RSSearch->Execute($ClassSubjectID);
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $UnScheduledChaptersList;
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$UnScheduledChaptersList[$SearchRow->chapterID] = $SearchRow->chapterName;
			}
			
			return $UnScheduledChaptersList;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at Chapter::GetUnScheduledChapters(). Stack Trace: ' . $e->getTraceAsString());
			return $UnScheduledChaptersList;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at Chapter::GetUnScheduledChapters(). Stack Trace: ' . $e->getTraceAsString());
			return $UnScheduledChaptersList;
		}		
	}
	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//
	private function SaveDetails()
	{
		if ($this->ChapterID == 0)
		{
			$RSSave = $this->DBObject->Prepare('INSERT INTO aas_chapters (academicYearID, masterChapterID, classSubjectID, chapterName, priority, isActive, createUserID, createDate)
														VALUES (:|1, :|2, :|3, :|4, :|5, :|6, :|7, NOW());');
		
			$RSSave->Execute($this->AcademicYearID, $this->MasterChapterID, $this->ClassSubjectID, $this->ChapterName, $this->Priority, $this->IsActive, $this->CreateUserID);
			
			$this->ChapterID = $RSSave->LastID;
		}
		else
		{
			$RSUpdate = $this->DBObject->Prepare('UPDATE aas_chapters
													SET	academicYearID = :|1,
														masterChapterID = :|2,
														classSubjectID = :|3,
														chapterName = :|4,
														priority = :|5,
														isActive = :|6
													WHERE chapterID = :|7;');
													
			$RSUpdate->Execute($this->AcademicYearID, $this->MasterChapterID, $this->ClassSubjectID, $this->ChapterName, $this->Priority, $this->IsActive, $this->ChapterID);
		}
		
		return true;
	}
	
	private function RemoveChapter()
    {
        if(!isset($this->ChapterID)) 
        {
            throw new ApplicationARException('', APP_AR_ERROR_DELETE_WITHOUT_ID);
        }
        
        $RSDeleteChapter = $this->DBObject->Prepare('DELETE FROM aas_chapters WHERE chapterID = :|1 LIMIT 1;');
        $RSDeleteChapter->Execute($this->ChapterID);  

        return true;              
    }

	private function GetChapterByID()
	{
		$RSChapter = $this->DBObject->Prepare('SELECT * FROM aas_chapters WHERE chapterID = :|1 LIMIT 1;');
		$RSChapter->Execute($this->ChapterID);
		
		$ChapterRow = $RSChapter->FetchRow();
		
		$this->SetAttributesFromDB($ChapterRow);				
	}
	
	private function SetAttributesFromDB($ChapterRow)
	{
		$this->ChapterID = $ChapterRow->chapterID;
		$this->AcademicYearID = $ChapterRow->academicYearID;

		$this->MasterChapterID = $ChapterRow->masterChapterID;
		$this->ClassSubjectID = $ChapterRow->classSubjectID;
		$this->ChapterName = $ChapterRow->chapterName;

		$this->Priority = $ChapterRow->priority;
		$this->IsActive = $ChapterRow->isActive;

		$this->CreateUserID = $ChapterRow->createUserID;
		$this->CreateDate = $ChapterRow->createDate;
	}	
}
?>
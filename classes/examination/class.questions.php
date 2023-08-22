<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.db_connect.php');
error_reporting(E_ALL);

class Question
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	
	private $QuestionID;
	private $ChapterTopicID;

	private $DifficultyLevelID;
	private $Question;

	private $IsActive;
	private $CreateUserID;
	private $CreateDate;
	
	// PUBLIC METHODS START HERE	//
	public function __construct($QuestionID = 0)
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;
		
		if($QuestionID != 0)
		{
			$this->QuestionID = $QuestionID;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetQuestionByID();
		}
		else
		{
			//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
			$this->QuestionID = 0;
			$this->ChapterTopicID = 0;

			$this->DifficultyLevelID = 0;
			$this->Question = '';

			$this->IsActive = 0;
			$this->CreateUserID = 0;
			$this->CreateDate = '0000-00-00 00:00:00';
		}
	}
	
	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetQuestionID()
	{
		return $this->QuestionID;
	}
	
	public function GetChapterTopicID()
	{
		return $this->ChapterTopicID;
	}
	public function SetChapterTopicID($ChapterTopicID)
	{
		$this->ChapterTopicID = $ChapterTopicID;
	}

	public function GetDifficultyLevelID()
	{
		return $this->DifficultyLevelID;
	}
	public function SetDifficultyLevelID($DifficultyLevelID)
	{
		$this->DifficultyLevelID = $DifficultyLevelID;
	}

	public function GetQuestion()
	{
		return $this->Question;
	}
	public function SetQuestion($Question)
	{
		$this->Question = $Question;
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
	public function SetCreateDate($CreateDate)
	{
		$this->CreateDate = $CreateDate;
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
            $this->RemoveQuestion();
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
		
	// END OF PUBLIC METHODS	//
	
	// START OF STATIC METHODS	//
	static function SearchQuestion(&$TotalRecords = 0, $GetTotalsOnly = false, $Filters = array(), $Start = 0, $Limit = 100)
	{
		$AllQuestion = array();
		
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
					$Conditions[] = 'ac.classSubjectID = '. $DBConnObject->RealEscapeVariable($Filters['ClassSubjectID']);
				}
				if (!empty($Filters['ChapterID']))
				{
					$Conditions[] = 'act.chapterID = '. $DBConnObject->RealEscapeVariable($Filters['ChapterID']);
				}
				if (!empty($Filters['ChapterTopicID']))
				{
					$Conditions[] = 'aqs.chapterTopicID = '. $DBConnObject->RealEscapeVariable($Filters['ChapterTopicID']);
				}
				if (!empty($Filters['DifficultyLevelID']))
				{
					$Conditions[] = 'adl.difficultyLevelID = '. $DBConnObject->RealEscapeVariable($Filters['DifficultyLevelID']);
				}
								
				if (!empty($Filters['ActiveStatus']))
				{
					if ($Filters['ActiveStatus'] == 1) //active records
					{
						$Conditions[] = 'aqs.isActive = 1';
					}
					else if ($Filters['ActiveStatus'] == 2) //non active records
					{
						$Conditions[] = 'aqs.isActive = 0';
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
													FROM aem_questions aqs
													INNER JOIN aas_chapter_topics act ON act.chapterTopicID = aqs.chapterTopicID
													INNER JOIN aas_chapters ac ON ac.chapterID = act.chapterID 
													INNER JOIN asa_class_subjects acs ON acs.classSubjectID = ac.classSubjectID 
													INNER JOIN asa_subject_master asm ON asm.subjectID = acs.subjectID 
													INNER JOIN asa_classes acl ON acl.classID = acs.classID 
													INNER JOIN aem_difficulty_levels adl ON adl.difficultyLevelID = aqs.difficultyLevelID 
													INNER JOIN users u ON aqs.createUserID = u.userID 
													'. $QueryString .';');
				$RSTotal->Execute();
				
				$TotalRecords = $RSTotal->FetchRow()->totalRecords;
				return;
			}

			$RSSearch = $DBConnObject->Prepare('SELECT aqs.*, act.topicName, ac.chapterName, asm.subject, acl.className, adl.difficultyLevel, u.userName AS createUserName 
												FROM aem_questions aqs
												INNER JOIN aas_chapter_topics act ON act.chapterTopicID = aqs.chapterTopicID
												INNER JOIN aas_chapters ac ON ac.chapterID = act.chapterID 
												INNER JOIN asa_class_subjects acs ON acs.classSubjectID = ac.classSubjectID 
												INNER JOIN asa_subject_master asm ON asm.subjectID = acs.subjectID 
												INNER JOIN asa_classes acl ON acl.classID = acs.classID 
												INNER JOIN aem_difficulty_levels adl ON adl.difficultyLevelID = aqs.difficultyLevelID  
												INNER JOIN users u ON aqs.createUserID = u.userID 
												'. $QueryString .' 
												ORDER BY aqs.difficultyLevelID LIMIT ' . (int) $Start . ', ' . (int) $Limit . ';');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllQuestion; 
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$AllQuestion[$SearchRow->questionID]['TopicName'] = $SearchRow->topicName;
				$AllQuestion[$SearchRow->questionID]['ChapterName'] = $SearchRow->chapterName;
				$AllQuestion[$SearchRow->questionID]['SubjectName'] = $SearchRow->subject;
				$AllQuestion[$SearchRow->questionID]['ClassName'] = $SearchRow->className;
				$AllQuestion[$SearchRow->questionID]['DifficultyLevel'] = $SearchRow->difficultyLevel;
				$AllQuestion[$SearchRow->questionID]['Question'] = $SearchRow->question;

				$AllQuestion[$SearchRow->questionID]['IsActive'] = $SearchRow->isActive;

				$AllQuestion[$SearchRow->questionID]['CreateUserID'] = $SearchRow->createUserID;
				$AllQuestion[$SearchRow->questionID]['CreateUserName'] = $SearchRow->createUserName;
				$AllQuestion[$SearchRow->questionID]['CreateDate'] = $SearchRow->createDate;
			}
			
			return $AllQuestion;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at Question::SearchQuestion(). Stack Trace: ' . $e->getTraceAsString());
			return $AllQuestion;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at Question::SearchQuestion(). Stack Trace: ' . $e->getTraceAsString());
			return $AllQuestion;
		}
	}
	
	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//
	private function SaveDetails()
	{
		if ($this->QuestionID == 0)
		{
			$RSSave = $this->DBObject->Prepare('INSERT INTO aem_questions (chapterTopicID, difficultyLevelID, question, isActive, createUserID, createDate)
														VALUES (:|1, :|2, :|3, :|4, :|5, NOW());');
		
			$RSSave->Execute($this->ChapterTopicID, $this->DifficultyLevelID, $this->Question, $this->IsActive, $this->CreateUserID);
			
			$this->QuestionID = $RSSave->LastID;
		}
		else
		{
			$RSUpdate = $this->DBObject->Prepare('UPDATE aem_questions
													SET	chapterTopicID = :|1,
														difficultyLevelID = :|2,
														question = :|3,
														isActive = :|4
													WHERE questionID = :|5 LIMIT 1;');
													
			$RSUpdate->Execute($this->ChapterTopicID, $this->DifficultyLevelID, $this->Question, $this->IsActive, $this->QuestionID);
		}
		
		return true;
	}
	
	private function RemoveQuestion()
    {
        if(!isset($this->QuestionID)) 
        {
            throw new ApplicationARException('', APP_AR_ERROR_DELETE_WITHOUT_ID);
        }
        
        $RSDeleteQuestion = $this->DBObject->Prepare('DELETE FROM aem_questions WHERE questionID = :|1 LIMIT 1;');
        $RSDeleteQuestion->Execute($this->QuestionID); 

        return true;               
    }
	
	private function GetQuestionByID()
	{
		$RSQuestion = $this->DBObject->Prepare('SELECT * FROM aem_questions WHERE questionID = :|1 LIMIT 1;');
		$RSQuestion->Execute($this->QuestionID);
		
		$QuestionRow = $RSQuestion->FetchRow();
		
		$this->SetAttributesFromDB($QuestionRow);				
	}
	
	private function SetAttributesFromDB($QuestionRow)
	{
		$this->QuestionID = $QuestionRow->questionID;
		$this->ChapterTopicID = $QuestionRow->chapterTopicID;

		$this->DifficultyLevelID = $QuestionRow->difficultyLevelID;
		$this->Question = $QuestionRow->question;
		$this->IsActive = $QuestionRow->isActive;

		$this->CreateUserID = $QuestionRow->createUserID;
		$this->CreateDate = $QuestionRow->createDate;
	}	
}
?>
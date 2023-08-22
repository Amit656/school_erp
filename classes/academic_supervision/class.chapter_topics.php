<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.db_connect.php');
error_reporting(E_ALL);

class ChapterTopic
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	
	private $ChapterTopicID;
	private $ChapterID;

	private $TopicName;
	private $ExpectedClasses;

	private $Priority;
	private $IsActive;

	private $CreateUserID;
	private $CreateDate;
	
	// PUBLIC METHODS START HERE	//
	public function __construct($ChapterTopicID = 0)
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;
		
		if($ChapterTopicID != 0)
		{
			$this->ChapterTopicID = $ChapterTopicID;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetChapterTopicByID();
		}
		else
		{
			//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
			$this->ChapterTopicID = 0;
			$this->ChapterID = 0;

			$this->TopicName = '';
			$this->ExpectedClasses = '';

			$this->Priority = 0;
			$this->IsActive = 0;

			$this->CreateUserID = 0;
			$this->CreateDate = '0000-00-00 00:00:00';
		}
	}
	
	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetChapterTopicID()
	{
		return $this->ChapterTopicID;
	}
	
	public function GetChapterID()
	{
		return $this->ChapterID;
	}
	public function SetChapterID($ChapterID)
	{
		$this->ChapterID = $ChapterID;
	}

	public function GetTopicName()
	{
		return $this->TopicName;
	}
	public function SetTopicName($TopicName)
	{
		$this->TopicName = $TopicName;
	}

	public function GetExpectedClasses()
	{
		return $this->ExpectedClasses;
	}
	public function SetExpectedClasses($ExpectedClasses)
	{
		$this->ExpectedClasses = $ExpectedClasses;
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
            $this->RemoveChapterTopic();
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
           /* $RSChapterTopicCount = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM ahm_rooms WHERE chapterTopicID = :|1;');
            $RSChapterTopicCount->Execute($this->ChapterTopicID);

            if ($RSChapterTopicCount->FetchRow()->totalRecords > 0) 
            {
                return true;
            }*/

            return false;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at ChapterTopic::CheckDependencies(). Stack Trace: ' . $e->getTraceAsString());
            return true;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: ApplicationDBException at ChapterTopic::CheckDependencies(). Stack Trace: ' . $e->getTraceAsString());
            return true;
        }       
    }	
	// END OF PUBLIC METHODS	//
	
	// START OF STATIC METHODS	//

	static function SearchChapterTopics(&$TotalRecords = 0, $GetTotalsOnly = false, $Filters = array(), $Start = 0, $Limit = 100)
	{
		$AllChapterTopics = array();
		
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
				if (!empty($Filters['ChapterTopicName']))
				{
					$Conditions[] = 'act.topicName LIKE '. $DBConnObject->RealEscapeVariable($Filters['ChapterTopicName'] . '%');
				}
								
				if (!empty($Filters['ActiveStatus']))
				{
					if ($Filters['ActiveStatus'] == 1) //active records
					{
						$Conditions[] = 'act.isActive = 1';
					}
					else if ($Filters['ActiveStatus'] == 2) //non active records
					{
						$Conditions[] = 'act.isActive = 0';
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
													FROM aas_chapter_topics act
													INNER JOIN aas_chapters ac ON ac.chapterID = act.chapterID 
													INNER JOIN asa_class_subjects acs ON acs.classSubjectID = ac.classSubjectID 
													INNER JOIN asa_subject_master asm ON asm.subjectID = acs.subjectID 
													INNER JOIN asa_classes acl ON acl.classID = acs.classID 
													INNER JOIN users u ON act.createUserID = u.userID 
													'. $QueryString .';');
				$RSTotal->Execute();
				
				$TotalRecords = $RSTotal->FetchRow()->totalRecords;
				return;
			}

			$RSSearch = $DBConnObject->Prepare('SELECT act.*, ac.chapterName, asm.subject, acl.className, u.userName AS createUserName 
												FROM aas_chapter_topics act
												INNER JOIN aas_chapters ac ON ac.chapterID = act.chapterID 
												INNER JOIN asa_class_subjects acs ON acs.classSubjectID = ac.classSubjectID 
												INNER JOIN asa_subject_master asm ON asm.subjectID = acs.subjectID 
												INNER JOIN asa_classes acl ON acl.classID = acs.classID 
												INNER JOIN users u ON act.createUserID = u.userID 
												'. $QueryString .' 
												ORDER BY acl.priority, ac.priority, act.priority LIMIT ' . (int) $Start . ', ' . (int) $Limit . ';');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllChapterTopics; 
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$AllChapterTopics[$SearchRow->chapterTopicID]['TopicName'] = $SearchRow->topicName;
				$AllChapterTopics[$SearchRow->chapterTopicID]['ChapterName'] = $SearchRow->chapterName;
				$AllChapterTopics[$SearchRow->chapterTopicID]['SubjectName'] = $SearchRow->subject;
				$AllChapterTopics[$SearchRow->chapterTopicID]['ClassName'] = $SearchRow->className;

				$AllChapterTopics[$SearchRow->chapterTopicID]['ExpectedClasses'] = $SearchRow->expectedClasses;

				$AllChapterTopics[$SearchRow->chapterTopicID]['Priority'] = $SearchRow->priority;
				$AllChapterTopics[$SearchRow->chapterTopicID]['IsActive'] = $SearchRow->isActive;

				$AllChapterTopics[$SearchRow->chapterTopicID]['CreateUserID'] = $SearchRow->createUserID;
				$AllChapterTopics[$SearchRow->chapterTopicID]['CreateUserName'] = $SearchRow->createUserName;
				$AllChapterTopics[$SearchRow->chapterTopicID]['CreateDate'] = $SearchRow->createDate;
			}
			
			return $AllChapterTopics;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at ChapterTopic::SearchChapterTopics(). Stack Trace: ' . $e->getTraceAsString());
			return $AllChapterTopics;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at ChapterTopic::SearchChapterTopics(). Stack Trace: ' . $e->getTraceAsString());
			return $AllChapterTopics;
		}
	}

	static function GetChapterTopicsByChapter($ChapterID)
	{
		$ChapterTopicList = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT * FROM aas_chapter_topics 
												WHERE chapterID = :|1 
												AND isActive = 1
												AND chapterTopicID  NOT IN (SELECT chapterTopicID FROM aas_topic_schedule_details);');
			$RSSearch->Execute($ChapterID);
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $ChapterTopicList;
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$ChapterTopicList[$SearchRow->chapterTopicID] = $SearchRow->topicName;
			}
			
			return $ChapterTopicList;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at ChapterTopic::GetChapterTopicsByChapter(). Stack Trace: ' . $e->getTraceAsString());
			return $ChapterTopicList;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at ChapterTopic::GetChapterTopicsByChapter(). Stack Trace: ' . $e->getTraceAsString());
			return $ChapterTopicList;
		}		
	}

	static function GetAllTopicsByChapterID($ChapterID)
	{
		$ChapterTopicList = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT * FROM aas_chapter_topics 
												WHERE chapterID = :|1 
												AND isActive = 1;');
			$RSSearch->Execute($ChapterID);
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $ChapterTopicList;
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$ChapterTopicList[$SearchRow->chapterTopicID]['TopicName'] = $SearchRow->topicName;
			}
			
			return $ChapterTopicList;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at ChapterTopic::GetAllTopicsByChapterID(). Stack Trace: ' . $e->getTraceAsString());
			return $ChapterTopicList;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at ChapterTopic::GetAllTopicsByChapterID(). Stack Trace: ' . $e->getTraceAsString());
			return $ChapterTopicList;
		}		
	}
	
	static function GetTopicByChapter($chapterID)
	{
		$ChapterTopicsList = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT * FROM aas_chapter_topics WHERE chapterID = :|1 AND isActive = 1;');
			$RSSearch->Execute($chapterID);
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $ChapterTopicsList;
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$ChapterTopicsList[$SearchRow->chapterTopicID] = $SearchRow->topicName;
			}
			
			return $ChapterTopicsList;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at ChapterTopic::GetTopicByChapter(). Stack Trace: ' . $e->getTraceAsString());
			return $ChapterTopicsList;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at ChapterTopic::GetTopicByChapter(). Stack Trace: ' . $e->getTraceAsString());
			return $ChapterTopicsList;
		}		
	}
	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//
	private function SaveDetails()
	{
		if ($this->ChapterTopicID == 0)
		{
			$RSSave = $this->DBObject->Prepare('INSERT INTO aas_chapter_topics (chapterID, topicName, expectedClasses, priority, isActive, createUserID, createDate)
														VALUES (:|1, :|2, :|3, :|4, :|5, :|6, NOW());');
		
			$RSSave->Execute($this->ChapterID, $this->TopicName, $this->ExpectedClasses, $this->Priority, $this->IsActive, $this->CreateUserID);
			
			$this->ChapterTopicID = $RSSave->LastID;
		}
		else
		{
			$RSUpdate = $this->DBObject->Prepare('UPDATE aas_chapter_topics
													SET	chapterID = :|1,
														topicName = :|2,
														expectedClasses = :|3,
														priority = :|4,
														isActive = :|5
													WHERE chapterTopicID = :|6;');
													
			$RSUpdate->Execute($this->ChapterID, $this->TopicName, $this->ExpectedClasses, $this->Priority, $this->IsActive, $this->ChapterTopicID);
		}
		
		return true;
	}

	private function RemoveChapterTopic()
    {
        if(!isset($this->ChapterTopicID)) 
        {
            throw new ApplicationARException('', APP_AR_ERROR_DELETE_WITHOUT_ID);
        }
        
        $RSDeleteChapterTopic = $this->DBObject->Prepare('DELETE FROM aas_chapter_topics WHERE chapterTopicID = :|1 LIMIT 1;');
        $RSDeleteChapterTopic->Execute($this->ChapterTopicID);  

        return true;              
    }
	
	private function GetChapterTopicByID()
	{
		$RSChapterTopic = $this->DBObject->Prepare('SELECT * FROM aas_chapter_topics WHERE chapterTopicID = :|1 LIMIT 1;');
		$RSChapterTopic->Execute($this->ChapterTopicID);
		
		$ChapterTopicRow = $RSChapterTopic->FetchRow();
		
		$this->SetAttributesFromDB($ChapterTopicRow);				
	}
	
	private function SetAttributesFromDB($ChapterTopicRow)
	{
		$this->ChapterTopicID = $ChapterTopicRow->chapterTopicID;
		$this->ChapterID = $ChapterTopicRow->chapterID;

		$this->TopicName = $ChapterTopicRow->topicName;
		$this->ExpectedClasses = $ChapterTopicRow->expectedClasses;

		$this->Priority = $ChapterTopicRow->priority;
		$this->IsActive = $ChapterTopicRow->isActive;

		$this->CreateUserID = $ChapterTopicRow->createUserID;
		$this->CreateDate = $ChapterTopicRow->createDate;
	}	
}
?>
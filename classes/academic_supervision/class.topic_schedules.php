<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.db_connect.php');
error_reporting(E_ALL);

class TopicSchedule
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	
	private $TopicScheduleID;
	private $BranchStaffID;
	private $ClassSectionID;

	private $ScheduleType;
	private $StartDate;
	private $Status;
	private $StatusUpdatedOn;

	private $IsActive;
	private $CreateUserID;
	private $CreateDate;

	// Additional variable
	private $TopicScheduleDetails = array();
	
	// PUBLIC METHODS START HERE	//
	public function __construct($TopicScheduleID = 0)
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;
		
		if($TopicScheduleID != 0)
		{
			$this->TopicScheduleID = $TopicScheduleID;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetTopicScheduleByID();
		}
		else
		{
			//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
			$this->TopicScheduleID = 0;
			$this->BranchStaffID = 0;
			$this->ClassSectionID = 0;

			$this->ScheduleType = '';
			$this->StartDate = '0000-00-00';
			$this->Status = '';
			$this->StatusUpdatedOn = '';

			$this->IsActive = 0;
			$this->CreateUserID = 0;
			$this->CreateDate = '0000-00-00 00:00:00';

			$this->TopicScheduleDetails = array();
		}
	}
	
	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetTopicScheduleID()
	{
		return $this->TopicScheduleID;
	}
	
	public function GetBranchStaffID()
	{
		return $this->BranchStaffID;
	}
	public function SetBranchStaffID($BranchStaffID)
	{
		$this->BranchStaffID = $BranchStaffID;
	}

	public function GetClassSectionID()
	{
		return $this->ClassSectionID;
	}
	public function SetClassSectionID($ClassSectionID)
	{
		$this->ClassSectionID = $ClassSectionID;
	}

	public function GetScheduleType()
	{
		return $this->ScheduleType;
	}
	public function SetScheduleType($ScheduleType)
	{
		$this->ScheduleType = $ScheduleType;
	}

	public function GetStartDate()
	{
		return $this->StartDate;
	}
	public function SetStartDate($StartDate)
	{
		$this->StartDate = $StartDate;
	}

	public function GetStatus()
	{
		return $this->Status;
	}
	public function SetStatus($Status)
	{
		$this->Status = $Status;
	}

	public function GetStatusUpdatedOn()
	{
		return $this->StatusUpdatedOn;
	}
	public function SetStatusUpdatedOn($StatusUpdatedOn)
	{
		$this->StatusUpdatedOn = $StatusUpdatedOn;
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

	public function GetTopicScheduleDetails()
	{
		return $this->TopicScheduleDetails;
	}
	public function SetTopicScheduleDetails($TopicScheduleDetails)
	{
		$this->TopicScheduleDetails = $TopicScheduleDetails;
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
            $this->TopicSchedule();
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
            /*$RSRemarkCount = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM ahm_rooms WHERE topicScheduleID = :|1;');
            $RSRemarkCount->Execute($this->TopicScheduleID);

            if ($RSRemarkCount->FetchRow()->totalRecords > 0) 
            {
                return true;
            }*/

            return false;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at Remark::CheckDependencies(). Stack Trace: ' . $e->getTraceAsString());
            return true;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: ApplicationDBException at Remark::CheckDependencies(). Stack Trace: ' . $e->getTraceAsString());
            return true;
        }       
    }

	public function FillTopicScheduleDetails()
    {
		try
        {	
        	$RSSearch = $this->DBObject->Prepare('SELECT atsd.*, act.topicName, ac.chapterName FROM aas_topic_schedule_details atsd
        											INNER JOIN aas_chapter_topics act ON act.chapterTopicID = atsd.chapterTopicID
        											INNER JOIN aas_chapters ac ON ac.chapterID = act.chapterID
        											WHERE topicScheduleID = :|1;');
			$RSSearch->Execute($this->TopicScheduleID);

			if($RSSearch->Result->num_rows  > 0)
			{
				while($SearchRow = $RSSearch->FetchRow())
				{
					$this->TopicScheduleDetails[$SearchRow->chapterName][$SearchRow->topicScheduleDetailID]['TopicName'] = $SearchRow->topicName;
					$this->TopicScheduleDetails[$SearchRow->chapterName][$SearchRow->topicScheduleDetailID]['ExpectedClasses'] = $SearchRow->expectedClasses;
					$this->TopicScheduleDetails[$SearchRow->chapterName][$SearchRow->topicScheduleDetailID]['StartDate'] = $SearchRow->startDate;
					$this->TopicScheduleDetails[$SearchRow->chapterName][$SearchRow->topicScheduleDetailID]['EndDate'] = $SearchRow->endDate;
					$this->TopicScheduleDetails[$SearchRow->chapterName][$SearchRow->topicScheduleDetailID]['Status'] = $SearchRow->status;
					$this->TopicScheduleDetails[$SearchRow->chapterName][$SearchRow->topicScheduleDetailID]['Remark'] = $SearchRow->remark;
				}
			}	

			return true;   
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException: TopicSchedule::FillTopicScheduleDetails(). Stack Trace: ' . $e->getTraceAsString());
            return false;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception: TopicSchedule::FillTopicScheduleDetails(). Stack Trace: ' . $e->getTraceAsString());
            return false;
        }
    }
		
	// END OF PUBLIC METHODS	//
	
	// START OF STATIC METHODS	//
	
	static function SearchTopicSchedules(&$TotalRecords = 0, $GetTotalsOnly = false, $Filters = array(), $Start = 0, $Limit = 100)
	{
		$ScheduledTopicsList = array();

		try
		{
			$DBConnObject = new DBConnect();
			
			$Conditions = array();
			$WhereConditions = '';
			
			if (count($Filters) > 0)
			{	
				if (!empty($Filters['ClassID']))
				{
					$Conditions[] = 'acl.classID = '. $DBConnObject->RealEscapeVariable($Filters['ClassID']);
				}
				if (!empty($Filters['ClassSectionID']))
				{
					$Conditions[] = 'ats.classSectionID = '. $DBConnObject->RealEscapeVariable($Filters['ClassSectionID']);
				}			
				if (!empty($Filters['ScheduleStartDate']))
				{
					$Conditions[] = 'ats.startDate = '. $DBConnObject->RealEscapeVariable($Filters['ScheduleStartDate']);
				}
				if (!empty($Filters['BranchStaffID']))
				{
					$Conditions[] = 'ats.branchStaffID = '. $DBConnObject->RealEscapeVariable($Filters['BranchStaffID']);
				}

				if (!empty($Filters['ClassSubjectID']))
				{
					$WhereConditions = 'WHERE classSubjectID = '. $DBConnObject->RealEscapeVariable($Filters['ClassSubjectID']);
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
													FROM aas_topic_schedules ats
													INNER JOIN asa_class_sections aclsc ON aclsc.classSectionID = ats.classSectionID
													INNER JOIN asa_classes acl ON acl.classID = aclsc.classID 
													INNER JOIN asa_section_master ascm ON ascm.sectionMasterID = aclsc.sectionMasterID 
													INNER JOIN users u ON ats.createUserID = u.userID
													'. $QueryString .'
													AND ats.topicScheduleID In (SELECT topicScheduleID FROM aas_topic_schedule_details WHERE chapterTopicID IN (SELECT chapterTopicID FROM aas_chapter_topics WHERE chapterID IN (SELECT chapterID FROM aas_chapters WHERE classSubjectID IN (SELECT classSubjectID FROM asa_class_subjects '. $WhereConditions .'))));');
				$RSTotal->Execute();
				
				$TotalRecords = $RSTotal->FetchRow()->totalRecords;
				return;
			}
			
			$RSSearch = $DBConnObject->Prepare('SELECT ats.*, asm.subject, acl.className, acl.classSymbol, ascm.sectionName, u.userName AS createUserName 
												FROM aas_topic_schedules ats
												    INNER JOIN aas_topic_schedule_details atsd ON atsd.topicScheduleID = ats.topicScheduleID
                                                    INNER JOIN aas_chapter_topics act ON act.chapterTopicID = atsd.chapterTopicID
                                                    INNER JOIN aas_chapters ac ON ac.chapterID = act.chapterID
                                                    INNER JOIN asa_class_subjects acs ON acs.classSubjectID = ac.classSubjectID
                                                    INNER JOIN asa_subject_master asm ON asm.subjectID = acs.subjectID
                                                    
													INNER JOIN asa_class_sections aclsc ON aclsc.classSectionID = ats.classSectionID
													INNER JOIN asa_classes acl ON acl.classID = aclsc.classID 
													INNER JOIN asa_section_master ascm ON ascm.sectionMasterID = aclsc.sectionMasterID 
													INNER JOIN users u ON ats.createUserID = u.userID
													'. $QueryString .'
													AND ats.topicScheduleID In (SELECT topicScheduleID FROM aas_topic_schedule_details WHERE chapterTopicID IN (SELECT chapterTopicID FROM aas_chapter_topics WHERE chapterID IN (SELECT chapterID FROM aas_chapters WHERE classSubjectID IN (SELECT classSubjectID FROM asa_class_subjects '. $WhereConditions .'))))
												ORDER BY ats.topicScheduleID LIMIT ' . (int) $Start . ', ' . (int) $Limit . ';');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $ScheduledTopicsList;
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$ScheduledTopicsList[$SearchRow->topicScheduleID]['ScheduleType'] = $SearchRow->scheduleType;
				$ScheduledTopicsList[$SearchRow->topicScheduleID]['Subject'] = $SearchRow->subject;

				$ScheduledTopicsList[$SearchRow->topicScheduleID]['Class'] = $SearchRow->classSymbol . ' ' . $SearchRow->sectionName;

				$ScheduledTopicsList[$SearchRow->topicScheduleID]['StartDate'] = $SearchRow->startDate;
				$ScheduledTopicsList[$SearchRow->topicScheduleID]['Status'] = $SearchRow->status;
				$ScheduledTopicsList[$SearchRow->topicScheduleID]['StatusUpdatedOn'] = $SearchRow->statusUpdatedOn;

				$ScheduledTopicsList[$SearchRow->topicScheduleID]['IsActive'] = $SearchRow->isActive;
				$ScheduledTopicsList[$SearchRow->topicScheduleID]['CreateUserID'] = $SearchRow->createUserID;
				$ScheduledTopicsList[$SearchRow->topicScheduleID]['CreateUserName'] = $SearchRow->createUserName;
				$ScheduledTopicsList[$SearchRow->topicScheduleID]['CreateDate'] = $SearchRow->createDate;
			}
			
			return $ScheduledTopicsList;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at TopicSchedule::SearchTopicSchedules(). Stack Trace: ' . $e->getTraceAsString());
			return $ScheduledTopicsList;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at TopicSchedule::SearchTopicSchedules(). Stack Trace: ' . $e->getTraceAsString());
			return $ScheduledTopicsList;
		}
	}

	static function UpdateTopicStatus($TopicScheduleDetail)
	{		
		$UpdatedScheduleDetails = array();
		try
		{
			$DBConnObject = new DBConnect();
			
			if (count($TopicScheduleDetail) > 0) 
			{
				foreach ($TopicScheduleDetail as $TopicScheduleDetailID => $ScheduleDetail) 
				{
					$RSUpdate = $DBConnObject->Prepare('UPDATE aas_topic_schedule_details
															SET	endDate = :|1,
																status = :|2,
																remark = :|3,
																statusUpdatedOn = NOW()
															WHERE topicScheduleDetailID = :|4 LIMIT 1;');
					$RSUpdate->Execute($ScheduleDetail['EndDate'], $ScheduleDetail['Status'], $ScheduleDetail['Remark'], $TopicScheduleDetailID);
				}
			}
			
			return $UpdatedScheduleDetails = $TopicScheduleDetail;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at TopicSchedule::UpdateTopicStatus(). Stack Trace: ' . $e->getTraceAsString());
			return $UpdatedScheduleDetails;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at TopicSchedule::UpdateTopicStatus(). Stack Trace: ' . $e->getTraceAsString());
			return $UpdatedScheduleDetails;
		}		
	}

	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//
	private function SaveDetails()
	{
		if ($this->TopicScheduleID == 0)
		{
			$RSSave = $this->DBObject->Prepare('INSERT INTO aas_topic_schedules (branchStaffID, ClassSectionID, scheduleType, startDate, status, isActive, createUserID, createDate)
														VALUES (:|1, :|2, :|3, :|4, :|5, :|6, :|7, NOW());');
		
			$RSSave->Execute($this->BranchStaffID, $this->ClassSectionID, $this->ScheduleType, $this->StartDate, $this->Status, $this->IsActive, $this->CreateUserID);
			
			$this->TopicScheduleID = $RSSave->LastID;

			foreach ($this->TopicScheduleDetails as $ChapterTopicID => $ScheduleDetail) 
			{
				$RSSaveDetails = $this->DBObject->Prepare('INSERT INTO aas_topic_schedule_details (topicScheduleID, chapterTopicID, expectedClasses, startDate)
															VALUES (:|1, :|2, :|3, :|4);');
			
				$RSSaveDetails->Execute($this->TopicScheduleID, $ChapterTopicID, $ScheduleDetail['ExpectedClasses'], $ScheduleDetail['StartDate']);
				
			}
		}
		else
		{
			$RSUpdate = $this->DBObject->Prepare('UPDATE aas_topic_schedules
													SET	branchStaffID = :|1,
														classSectionID = :|2,
														scheduleType = :|3,
														startDate = :|4,
														status = :|5,
														isActive = :|6
													WHERE topicScheduleID = :|7 LIMIT 1;');
													
			$RSUpdate->Execute($this->BranchStaffID, $this->ClassSectionID, $this->ScheduleType, $this->StartDate, $this->Status, $this->IsActive, $this->TopicScheduleID);
		}
		
		return true;
	}
	
	private function TopicSchedule()
    {
        if(!isset($this->TopicScheduleID)) 
        {
            throw new ApplicationARException('', APP_AR_ERROR_DELETE_WITHOUT_ID);
        }
        
        $RSDeleteTopicScheduleDetails = $this->DBObject->Prepare('DELETE FROM aas_topic_schedule_details WHERE topicScheduleID = :|1;');
        $RSDeleteTopicScheduleDetails->Execute($this->TopicScheduleID);  
        
        $RSDeleteTopicSchedule = $this->DBObject->Prepare('DELETE FROM aas_topic_schedules WHERE topicScheduleID = :|1 LIMIT 1;');
        $RSDeleteTopicSchedule->Execute($this->TopicScheduleID);  

        return true;              
    }
    
	private function GetTopicScheduleByID()
	{
		$RSTopicSchedule = $this->DBObject->Prepare('SELECT * FROM aas_topic_schedules WHERE topicScheduleID = :|1 LIMIT 1;');
		$RSTopicSchedule->Execute($this->TopicScheduleID);
		
		$TopicScheduleRow = $RSTopicSchedule->FetchRow();
		
		$this->SetAttributesFromDB($TopicScheduleRow);				
	}
	
	private function SetAttributesFromDB($TopicScheduleRow)
	{
		$this->TopicScheduleID = $TopicScheduleRow->topicScheduleID;
		$this->BranchStaffID = $TopicScheduleRow->branchStaffID;
		$this->ClassSectionID = $TopicScheduleRow->classSectionID;

		$this->ScheduleType = $TopicScheduleRow->scheduleType;
		$this->StartDate = $TopicScheduleRow->startDate;
		$this->Status = $TopicScheduleRow->status;
		$this->StatusUpdatedOn = $TopicScheduleRow->statusUpdatedOn;

		$this->IsActive = $TopicScheduleRow->isActive;
		$this->CreateUserID = $TopicScheduleRow->createUserID;
		$this->CreateDate = $TopicScheduleRow->createDate;
	}	
}
?>
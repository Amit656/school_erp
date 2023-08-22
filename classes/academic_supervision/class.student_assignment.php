<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.db_connect.php');
error_reporting(E_ALL);

class StudentAssignment
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	
	private $AssignmentID;
	private $ClassSectionID;
	private $ClassSubjectID;
	private $ChapterTopicID;

	private $AssignmentHeading;
	private $Assignment;

	private $IssueDate;
	private $EndDate;

	private $IsDraft;
	private $IsActive;

	private $CreateUserID;
	private $CreateDate;

	// Additional Variable
	private $ChapterTopicName;
	
	// PUBLIC METHODS START HERE	//
	public function __construct($AssignmentID = 0)
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;
		
		if($AssignmentID != 0)
		{
			$this->AssignmentID = $AssignmentID;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetAssignmentByID();
		}
		else
		{
			//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
			$this->AssignmentID = 0;
			$this->ClassSectionID = 0;
			$this->ClassSubjectID = 0;
			$this->ChapterTopicID = 0;

			$this->AssignmentHeading = '';
			$this->Assignment = '';

			$this->IssueDate = '0000-00-00';
			$this->EndDate = '0000-00-00';
			
			$this->IsDraft = 0;
			$this->IsActive = 0;

			$this->CreateUserID = 0;
			$this->CreateDate = '0000-00-00 00:00:00';

			$this->ChapterTopicName = '';
		}
	}
	
	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetAssignmentID()
	{
		return $this->AssignmentID;
	}
	
	public function GetClassSectionID()
	{
		return $this->ClassSectionID;
	}
	public function SetClassSectionID($ClassSectionID)
	{
		$this->ClassSectionID = $ClassSectionID;
	}
	
	public function GetClassSubjectID()
	{
		return $this->ClassSubjectID;
	}
	public function SetClassSubjectID($ClassSubjectID)
	{
		$this->ClassSubjectID = $ClassSubjectID;
	}

	public function GetChapterTopicID()
	{
		return $this->ChapterTopicID;
	}
	public function SetChapterTopicID($ChapterTopicID)
	{
		$this->ChapterTopicID = $ChapterTopicID;
	}

	public function GetAssignmentHeading()
	{
		return $this->AssignmentHeading;
	}
	public function SetAssignmentHeading($AssignmentHeading)
	{
		$this->AssignmentHeading = $AssignmentHeading;
	}
	
	public function GetAssignment()
	{
		return $this->Assignment;
	}
	public function SetAssignment($Assignment)
	{
		$this->Assignment = $Assignment;
	}

	public function GetIssueDate()
	{
		return $this->IssueDate;
	}
	public function SetIssueDate($IssueDate)
	{
		$this->IssueDate = $IssueDate;
	}
	
	public function GetEndDate()
	{
		return $this->EndDate;
	}
	public function SetEndDate($EndDate)
	{
		$this->EndDate = $EndDate;
	}
	
	public function GetIsDraft()
	{
		return $this->IsDraft;
	}
	public function SetIsDraft($IsDraft)
	{
		$this->IsDraft = $IsDraft;
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
	
	public function SetChapterTopicName($ChapterTopicName)
	{
		$this->ChapterTopicName = $ChapterTopicName;
	}

	public function SetChapterID($ChapterID)
	{
		$this->ChapterID = $ChapterID;
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

	public function SaveAssignmentImage($ImageName, &$AssignmentImageID)
	{
		try
		{	
			if ($AssignmentImageID == 0) 
			{
				$RSSave = $this->DBObject->Prepare('INSERT INTO aas_student_assignment_images (assignmentID, imageName, createDate)
																										VALUES (:|1, :|2, NOW());');
			
				$RSSave->Execute($this->AssignmentID, $ImageName);
				
				$AssignmentImageID = $RSSave->LastID;
			}
			else
			{	
				$RSUpdate = $this->DBObject->Prepare('UPDATE aas_student_assignment_images
													SET	imageName = :|1
													WHERE assignmentImageID = :|2;');
													
				$RSUpdate->Execute($ImageName, $AssignmentImageID);
			}
			

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

	public function GetAllAssignmentImages()
	{	
		$AllAssignmentImages = array();
		try
		{	
			$RSSearch = $this->DBObject->Prepare('SELECT * FROM aas_student_assignment_images WHERE assignmentID = :|1;');
			
			$RSSearch->Execute($this->AssignmentID);
			
			if ($RSSearch->Result->num_rows <= 0) 
			{
				return $AllAssignmentImages;
			}	

			while ($SearchRow = $RSSearch->FetchRow()) 
			{
				$AllAssignmentImages[$SearchRow->assignmentImageID]['ImageName'] = $SearchRow->imageName;
				$AllAssignmentImages[$SearchRow->assignmentImageID]['CreateDate'] = $SearchRow->createDate;
			}

			return $AllAssignmentImages;
		}
		catch (ApplicationDBException $e)
		{
			$this->LastErrorCode = $e->getCode();
			return $AllAssignmentImages;
		}
		catch (ApplicationARException $e)
		{
			$this->LastErrorCode = $e->getCode();
			return $AllAssignmentImages;
		}
		catch (Exception $e)
		{
			$this->LastErrorCode = APP_ERROR_UNDEFINED_ERROR;
			return $AllAssignmentImages;
		}
	}

	public function RemoveAssignmentImage($AssignmentImageID)
	{	
		try
		{	
			$RSSearch = $this->DBObject->Prepare('DELETE FROM aas_student_assignment_images WHERE assignmentImageID = :|1;');
			
			$RSSearch->Execute($AssignmentImageID);
			
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

	public function Remove()
    {
        try
        {
            $this->RemoveStudentAssignment();
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
	
	static function SearchAssignments(&$TotalRecords = 0, $GetTotalsOnly = false, $Filters = array(), $Start = 0, $Limit = 100)
	{
		$AllStudentAssignments = array();
		
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
				if (!empty($Filters['ClassSectionID']))
				{
					$Conditions[] = 'asa.classSectionID = '.$DBConnObject->RealEscapeVariable($Filters['ClassSectionID']);
				}
				if (!empty($Filters['ClassSubjectID']))
				{
					$Conditions[] = 'asa.classSubjectID = '.$DBConnObject->RealEscapeVariable($Filters['ClassSubjectID']);
				}
				if (!empty($Filters['ChapterID']))
				{
					$Conditions[] = 'act.chapterID = '. $DBConnObject->RealEscapeVariable($Filters['ChapterID']);
				}
				
				if (!empty($Filters['AssignmentHeading']))
				{
					$Conditions[] = 'asa.assignmentHeading LIKE '. $DBConnObject->RealEscapeVariable('%' . $Filters['AssignmentHeading'] . '%');
				}

				if (!empty($Filters['IssueDate']))
				{
					$Conditions[] = 'asa.issueDate = '. $DBConnObject->RealEscapeVariable($Filters['IssueDate']);
				}

				if (!empty($Filters['EndDate']))
				{
					$Conditions[] = 'asa.endDate = '. $DBConnObject->RealEscapeVariable($Filters['EndDate']);
				}

				if (!empty($Filters['ActiveStatus']))
				{
					if ($Filters['ActiveStatus'] == 1) //active records
					{
						$Conditions[] = 'asa.isActive = 1';
					}
					else if ($Filters['ActiveStatus'] == 2) //non active records
					{
						$Conditions[] = 'asa.isActive = 0';
					}
				}

				if (!empty($Filters['DraftStatus']))
				{
					if ($Filters['DraftStatus'] == 1) //active records
					{
						$Conditions[] = 'asa.isDraft = 1';
					}
					else if ($Filters['DraftStatus'] == 2) //non active records
					{
						$Conditions[] = 'asa.isDraft = 0';
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
													FROM aas_student_assignment asa
													LEFT JOIN aas_chapter_topics act ON act.chapterTopicID = asa.chapterTopicID 
													LEFT JOIN aas_chapters ac ON ac.chapterID = act.chapterID 
													INNER JOIN asa_class_subjects acsub ON acsub.classSubjectID = asa.classSubjectID 
													INNER JOIN asa_subject_master asm ON asm.subjectID = acsub.subjectID 
													INNER JOIN asa_classes acl ON acl.classID = acsub.classID 
													INNER JOIN asa_class_sections acs ON acs.classSectionID = asa.classSectionID
													INNER JOIN asa_section_master ascm ON ascm.sectionMasterID = acs.sectionMasterID 
													INNER JOIN users u ON asa.createUserID = u.userID 
													'. $QueryString .';');
				$RSTotal->Execute();
				
				$TotalRecords = $RSTotal->FetchRow()->totalRecords;
				return;
			}
			
			$RSSearch = $DBConnObject->Prepare('SELECT asa.*, act.topicName, asm.subject, acl.className, ascm.sectionName, ac.chapterName, u.userName AS createUserName 
												FROM aas_student_assignment asa
												LEFT JOIN aas_chapter_topics act ON act.chapterTopicID = asa.chapterTopicID 
												LEFT JOIN aas_chapters ac ON ac.chapterID = act.chapterID 
												INNER JOIN asa_class_subjects acsub ON acsub.classSubjectID = asa.classSubjectID 
												INNER JOIN asa_subject_master asm ON asm.subjectID = acsub.subjectID 
												INNER JOIN asa_classes acl ON acl.classID = acsub.classID 
												INNER JOIN asa_class_sections acs ON acs.classSectionID = asa.classSectionID
												INNER JOIN asa_section_master ascm ON ascm.sectionMasterID = acs.sectionMasterID 
												INNER JOIN users u ON asa.createUserID = u.userID
												'. $QueryString .' 
												ORDER BY asa.createDate DESC LIMIT ' . (int) $Start . ', ' . (int) $Limit . ';');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllStudentAssignments; 
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$AllStudentAssignments[$SearchRow->assignmentID]['ClassName'] = $SearchRow->className;
				$AllStudentAssignments[$SearchRow->assignmentID]['SubjectName'] = $SearchRow->subject;

				$AllStudentAssignments[$SearchRow->assignmentID]['ChapterName'] = $SearchRow->chapterName ? $SearchRow->chapterName : '';
				$AllStudentAssignments[$SearchRow->assignmentID]['TopicName'] = $SearchRow->topicName ? $SearchRow->topicName : '';
				
				$AllStudentAssignments[$SearchRow->assignmentID]['AssignmentHeading'] = $SearchRow->assignmentHeading;
				$AllStudentAssignments[$SearchRow->assignmentID]['Assignment'] = $SearchRow->assignment;

				$AllStudentAssignments[$SearchRow->assignmentID]['IssueDate'] = $SearchRow->issueDate;
				$AllStudentAssignments[$SearchRow->assignmentID]['EndDate'] = $SearchRow->endDate;

				$AllStudentAssignments[$SearchRow->assignmentID]['IsDraft'] = $SearchRow->isDraft;
				$AllStudentAssignments[$SearchRow->assignmentID]['IsActive'] = $SearchRow->isActive;

				$AllStudentAssignments[$SearchRow->assignmentID]['CreateUserID'] = $SearchRow->createUserID;
				$AllStudentAssignments[$SearchRow->assignmentID]['CreateUserName'] = $SearchRow->createUserName;
				$AllStudentAssignments[$SearchRow->assignmentID]['CreateDate'] = $SearchRow->createDate;
				
				$AllStudentAssignments[$SearchRow->assignmentID]['AssignmentImage'] = array();
				
				$RSSearchImage = $DBConnObject->Prepare('SELECT * FROM aas_student_assignment_images WHERE assignmentID = :|1;');
			    $RSSearchImage->Execute($SearchRow->assignmentID);
			    
			    if ($RSSearchImage->Result->num_rows > 0)
			    {
			        $Counter = 0;
			        while ($SearchImageRow = $RSSearchImage->FetchRow()) 
    			    {
    			        $AllStudentAssignments[$SearchRow->assignmentID]['AssignmentImage'][$Counter]['AssignmentImageID'] = $SearchImageRow->assignmentImageID;
    			        $AllStudentAssignments[$SearchRow->assignmentID]['AssignmentImage'][$Counter]['ImageName'] = $SearchImageRow->imageName;
    			    
    			        $Counter++;
    			    }
			    }
			}
			
			return $AllStudentAssignments;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at StudentAssignment::SearchAssignments(). Stack Trace: ' . $e->getTraceAsString());
			return $AllStudentAssignments;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at StudentAssignment::SearchAssignments(). Stack Trace: ' . $e->getTraceAsString());
			return $AllStudentAssignments;
		}
	}
	
	static function GetTeacherAssignments($IssueDate, $BranchStaffID)
	{
	    $AssignmentList = array();
	    
	    try
	    {
	        $DBConnObject = new DBConnect();
	        
	        $RSSearch = $DBConnObject->Prepare('SELECT asa.*, act.topicName, asm.subject, ac.chapterName, acls.className, asecm.sectionName 
												FROM aas_student_assignment asa
												INNER JOIN aas_chapter_topics act ON act.chapterTopicID = asa.chapterTopicID 
												INNER JOIN aas_chapters ac ON ac.chapterID = act.chapterID 
												INNER JOIN asa_class_subjects acsub ON acsub.classSubjectID = ac.classSubjectID 
												INNER JOIN asa_class_sections acs ON acs.classSectionID = asa.classSectionID 
												INNER JOIN asa_classes acls ON acls.classID = acs.classID 
												INNER JOIN asa_section_master asecm ON asecm.sectionMasterID = acs.sectionMasterID 
												INNER JOIN asa_subject_master asm ON asm.subjectID = acsub.subjectID 
												INNER JOIN users u ON asa.createUserID = u.userID 
												INNER JOIN asa_branch_staff abs ON abs.userName = u.userName 
												WHERE abs.branchStaffID = :|1 AND asa.isActive = 1 AND asa.isDraft = 0 AND asa.issueDate = :|2;');
												
			$RSSearch->Execute($BranchStaffID, $IssueDate);
			
			if ($RSSearch->Result->num_rows <= 0)
			{
			    return $AssignmentList;
			}
			
			while ($SearchRow = $RSSearch->FetchRow())
			{
			    $AssignmentList[$SearchRow->assignmentID]['ClassName'] = $SearchRow->className;
			    $AssignmentList[$SearchRow->assignmentID]['SectionName'] = $SearchRow->sectionName;
			    
			    $AssignmentList[$SearchRow->assignmentID]['SubjectName'] = $SearchRow->subject;
			    $AssignmentList[$SearchRow->assignmentID]['ChapterName'] = $SearchRow->chapterName;
			    $AssignmentList[$SearchRow->assignmentID]['TopicName'] = $SearchRow->topicName;
			    
			    $AssignmentList[$SearchRow->assignmentID]['AssignmentHeading'] = $SearchRow->assignmentHeading;
			    $AssignmentList[$SearchRow->assignmentID]['Assignment'] = $SearchRow->assignment;
			    
			    $AssignmentList[$SearchRow->assignmentID]['AssignmentImages'] = array();
			    
			    $RSSearchAssignmentImages = $DBConnObject->Prepare('SELECT imageName FROM aas_student_assignment_images WHERE assignmentID = :|1;');
			    $RSSearchAssignmentImages->Execute($SearchRow->assignmentID);
			    
			    if ($RSSearchAssignmentImages->Result->num_rows > 0)
			    {
			        while ($SearchAssignmentImageRow = $RSSearchAssignmentImages->FetchRow())
			        {
			            $AssignmentList[$SearchRow->assignmentID]['AssignmentImages'][] = $_SERVER['HTTP_HOST'] . '/site_images/student_assignment/' . $SearchRow->assignmentID . '/' . $SearchAssignmentImageRow->imageName;
			            
			            //array_push($AllStudentAssignments[$SearchRow->assignmentID]['AssignmentImage'][], array('AssignmentImageID' => $SearchImageRow->assignmentImageID, 'ImageName' => $SearchImageRow->imageName));
    			        //$AllStudentAssignments[$SearchRow->assignmentID]['AssignmentImage'][] = array('AssignmentImageID' => $SearchImageRow->assignmentImageID, 'ImageName' => $SearchImageRow->imageName);
    			        //$AllStudentAssignments[$SearchRow->assignmentID]['AssignmentImage'][$Counter]['AssignmentImageID'] = $SearchImageRow->assignmentImageID;
    			        //$AllStudentAssignments[$SearchRow->assignmentID]['AssignmentImage'][$Counter]['ImageName'] = $SearchImageRow->imageName;
			        }
			    }
			}
			
			return $AssignmentList;
	    }
	    catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at StudentAssignment::GetTeacherAssignments(). Stack Trace: ' . $e->getTraceAsString());
			return $AssignmentList;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at StudentAssignment::GetTeacherAssignments(). Stack Trace: ' . $e->getTraceAsString());
			return $AssignmentList;
		}
	}
	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//
	private function SaveDetails()
	{
		if ($this->AssignmentID == 0)
		{
			if ($this->ChapterTopicName != '') 
			{
				$RSSaveTopicName = $this->DBObject->Prepare('INSERT INTO aas_chapter_topics (chapterID, topicName, isActive, createUserID, createDate)
															VALUES (:|1, :|2, :|3, :|4, NOW());');
			
				$RSSaveTopicName->Execute($this->ChapterID, $this->ChapterTopicName, $this->IsActive, $this->CreateUserID);
				
				$this->ChapterTopicID = $RSSaveTopicName->LastID;
			}

			$RSSave = $this->DBObject->Prepare('INSERT INTO aas_student_assignment (classSectionID, classSubjectID, chapterTopicID, assignmentHeading, assignment,
																					issueDate, endDate, isDraft, isActive, createUserID, createDate)
														VALUES (:|1, :|2, :|3, :|4, :|5, :|6, :|7, :|8, :|9, :|10, NOW());');
		
			$RSSave->Execute($this->ClassSectionID, $this->ClassSubjectID, $this->ChapterTopicID, $this->AssignmentHeading, $this->Assignment, 
							$this->IssueDate, $this->EndDate, $this->IsDraft, $this->IsActive, $this->CreateUserID);
			
			$this->AssignmentID = $RSSave->LastID;
		}
		else
		{
			if ($this->ChapterTopicName != '') 
			{
				$RSSaveTopicName = $this->DBObject->Prepare('INSERT INTO aas_chapter_topics (chapterID, topicName, isActive, createUserID, createDate)
															VALUES (:|1, :|2, :|3, :|4, NOW());');
			
				$RSSaveTopicName->Execute($this->ChapterID, $this->ChapterTopicName, $this->IsActive, $this->CreateUserID);
				
				$this->ChapterTopicID = $RSSaveTopicName->LastID;
			}

			$RSUpdate = $this->DBObject->Prepare('UPDATE aas_student_assignment
													SET	classSectionID = :|1,
														chapterTopicID = :|2,
														assignmentHeading = :|3,
														assignment = :|4,
														issueDate = :|5,
														endDate = :|6,
														isDraft = :|7,
														isActive = :|8
													WHERE assignmentID = :|9;');
													
			$RSUpdate->Execute($this->ClassSectionID, $this->ChapterTopicID, $this->AssignmentHeading, $this->Assignment, 
							$this->IssueDate, $this->EndDate, $this->IsDraft, $this->IsActive, $this->AssignmentID);
		}
		
		return true;
	}

	private function RemoveStudentAssignment()
    {
        if(!isset($this->AssignmentID)) 
        {
            throw new ApplicationARException('', APP_AR_ERROR_DELETE_WITHOUT_ID);
        }
        
        $RSDeleteStudentAssignment = $this->DBObject->Prepare('DELETE FROM aas_student_assignment WHERE assignmentID = :|1 LIMIT 1;');
        $RSDeleteStudentAssignment->Execute($this->AssignmentID);  

        return true;              
    }
	
	private function GetAssignmentByID()
	{
		$RSAssignment = $this->DBObject->Prepare('SELECT * FROM aas_student_assignment WHERE assignmentID = :|1 LIMIT 1;');
		$RSAssignment->Execute($this->AssignmentID);
		
		$AssignmentRow = $RSAssignment->FetchRow();
		
		$this->SetAttributesFromDB($AssignmentRow);				
	}
	
	private function SetAttributesFromDB($AssignmentRow)
	{
		$this->AssignmentID = $AssignmentRow->assignmentID;
		$this->ClassSectionID = $AssignmentRow->classSectionID;
		$this->ClassSubjectID = $AssignmentRow->classSubjectID;
		$this->ChapterTopicID = $AssignmentRow->chapterTopicID;

		$this->AssignmentHeading = $AssignmentRow->assignmentHeading;
		$this->Assignment = $AssignmentRow->assignment;

		$this->IssueDate = $AssignmentRow->issueDate;
		$this->EndDate = $AssignmentRow->endDate;

		$this->IsDraft = $AssignmentRow->isDraft;
		$this->IsActive = $AssignmentRow->isActive;

		$this->CreateUserID = $AssignmentRow->createUserID;
		$this->CreateDate = $AssignmentRow->createDate;
	}	
}
?>
<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.db_connect.php');
error_reporting(E_ALL);

class AddedClass
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	
	private $ClassID;
	private $AcademicYearID;

	private $ClassName;
	private $ClassSymbol;	
	private $HasDifferentSubjects;
	private $Priority;
	
	private $IsActive;	
	private $CreateUserID;
	private $CreateDate;

	private $ClassSectionsList = array();
	private $AssignedSubjects = array();	
	private $AssignedSubjectsMarksType = array();	

	// PUBLIC METHODS START HERE	//
	public function __construct($ClassID = 0)
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;
		
		if ($ClassID != 0)
		{
			$this->ClassID = $ClassID;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetClassByID();
		}
		else
		{
			//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
			$this->ClassID = 0;
			$this->AcademicYearID = 0;

			$this->ClassName = '';
			$this->ClassSymbol = '';
			$this->HasDifferentSubjects = 0;
			$this->Priority = 0;

			$this->IsActive = 0;
			$this->CreateUserID = '';
			$this->CreateDate = '0000-00-00 00:00:00';

			$this->ClassSectionsList = array();
			$this->AssignedSubjects = array();
			$this->AssignedSubjectsMarksType = array();
		}
	}
	
	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetClassID()
	{
		return $this->ClassID;
	}
	
	public function GetAcademicYearID()
	{
		return $this->AcademicYearID;
	}
	public function SetAcademicYearID($AcademicYearID)
	{
		$this->AcademicYearID = $AcademicYearID;
	}
	
	public function GetClassName()
	{
		return $this->ClassName;
	}
	public function SetClassName($ClassName)
	{
		$this->ClassName  = $ClassName;
	}
	
	public function GetClassSymbol()
	{
		return $this->ClassSymbol;
	}
	public function SetClassSymbol($ClassSymbol)
	{
		$this->ClassSymbol = $ClassSymbol;
	}
	
	public function GetHasDifferentSubjects()
	{
		return $this->HasDifferentSubjects;
	}
	public function SetHasDifferentSubjects($HasDifferentSubjects)
	{
		$this->HasDifferentSubjects = $HasDifferentSubjects;
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

	public function GetClassSectionsList()
	{
		return $this->ClassSectionsList;
	}
	public function SetClassSectionsList($ClassSectionsList)
	{
		$this->ClassSectionsList = $ClassSectionsList;
	}
	
	public function GetAssignedSubjects()
	{
		return $this->AssignedSubjects;
	}
	public function SetAssignedSubjects($AssignedSubjects)
	{
		$this->AssignedSubjects = $AssignedSubjects;
	}
	
	public function GetAssignedSubjectsMarksType()
	{
		return $this->AssignedSubjectsMarksType;
	}
	public function SetAssignedSubjectsMarksType($AssignedSubjectsMarksType)
	{
		$this->AssignedSubjectsMarksType = $AssignedSubjectsMarksType;
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
            $this->RemoveClass();
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
            $RSClassSectionsCount = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM asa_class_sections WHERE classID = :|1;');
            $RSClassSectionsCount->Execute($this->ClassID);

            $ClassSectionsCountRow = $RSClassSectionsCount->FetchRow();

            if ($ClassSectionsCountRow->totalRecords > 0) 
            {
                return true;
            }

            $RSClassSubjectsCount = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM asa_class_subjects WHERE classID = :|1;');
            $RSClassSubjectsCount->Execute($this->ClassID);
    
            if ($RSClassSubjectsCount->FetchRow()->totalRecords > 0) 
            {
                return true;
            }
            
            return false;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException: AddedClass::CheckDependencies. Stack Trace: ' . $e->getTraceAsString());
            return false;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception: AddedClass::CheckDependencies. Stack Trace: ' . $e->getTraceAsString());
            return false;
        }       
	}
	
	public function RecordExists()
	{
		try
		{
			$QueryString = '';

			if ($this->ClassID > 0)
			{
				$QueryString = ' AND classID != ' . $this->DBObject->RealEscapeVariable($this->ClassID);
			}

			$RSTotal = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM asa_classes WHERE className = :|1' . $QueryString . ';');
			$RSTotal->Execute($this->ClassName);
			
			if ($RSTotal->FetchRow()->totalRecords > 0)
			{
				return true;
			}
			
			return false;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at AddedClass::RecordExists(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at AddedClass::RecordExists(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
	}

    public function CheckClassSectionsDependencies($SectionsToBeRemoved)
    {
    	$SectionIDToBeRemoved = '';

    	if (is_array($SectionsToBeRemoved) && count($SectionsToBeRemoved) == 0)
    	{
    		return false;
    	}

        try
        {
        	$SectionIDToBeRemoved = implode(',', array_keys($SectionsToBeRemoved));    

            $RSClassAttendenceCount = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM asa_class_attendence WHERE classSectionID IN (SELECT classSectionID FROM 													asa_class_sections WHERE 
																   classID = :|1 AND sectionMasterID IN (' . $SectionIDToBeRemoved . '));');
            $RSClassAttendenceCount->Execute($this->ClassID);

            if ($RSClassAttendenceCount->FetchRow()->totalRecords > 0)
            {
            	return true;
            }

            $RSStudentsCount = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM asa_students WHERE classSectionID IN (SELECT classSectionID FROM 
        														asa_class_sections WHERE
        														classID = :|1 AND  sectionMasterID IN (' . $SectionIDToBeRemoved . '));');
            $RSStudentsCount->Execute($this->ClassID);

            if ($RSStudentsCount->FetchRow()->totalRecords > 0)
            {
            	return true;
            }
            
            return false;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException: AddedClass::CheckClassSectionsDependencies. Stack Trace: ' . $e->getTraceAsString());
            return false;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception: AddedClass::CheckClassSectionsDependencies. Stack Trace: ' . $e->getTraceAsString());
            return false;
        }       
    }

    public function CheckClassSubjectsDependencies($SubjectsToBeRemove)
    {
    	$SubjectIDToBeRemoved = '';

    	if (is_array($SubjectsToBeRemove) && count($SubjectsToBeRemove) == 0)
    	{
    		return false;
    	}

        try
        {
        	$SubjectIDToBeRemoved = implode(',', array_keys($SubjectsToBeRemove));    

            $RSClassTimeTableDetailsCount = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM asa_class_time_table_details WHERE classSubjectID IN (SELECT classSubjectID FROM 	
            														asa_class_subjects WHERE classID = :|1 AND subjectID IN (' . $SubjectIDToBeRemoved . '));');
            $RSClassTimeTableDetailsCount->Execute($this->ClassID);

            if ($RSClassTimeTableDetailsCount->FetchRow()->totalRecords > 0)
            {
            	return true;
            }

            $RSStudentsCount = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM asa_student_subjects WHERE classSubjectID IN (SELECT subjectID FROM asa_class_subjects WHERE
            														classID = :|1 AND  subjectID IN (' . $SubjectIDToBeRemoved . '));');
            $RSStudentsCount->Execute($this->ClassID);

            if ($RSStudentsCount->FetchRow()->totalRecords > 0)
            {
            	return true;
            }
            
            return false;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException: AddedClass::CheckClassSubjectsDependencies. Stack Trace: ' . $e->getTraceAsString());
            return false;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception: AddedClass::CheckClassSubjectsDependencies. Stack Trace: ' . $e->getTraceAsString());
            return false;
        }       
    }

    public function FillAssignedSubjects()
    {
		try
        {	
        	$DBConnObject = new DBConnect();

        	$RSClassSubjects = $this->DBObject->Prepare('SELECT acs.classSubjectID, acs.subjectID, acs.subjectMarksType, asm.subject 
														 FROM asa_class_subjects acs 
														 INNER JOIN asa_subject_master asm ON asm.subjectID = acs.subjectID 
														 WHERE classID = :|1 ORDER BY acs.priority;');
			$RSClassSubjects->Execute($this->ClassID);

			if ($RSClassSubjects->Result->num_rows > 0)
			{
				while($SearchRow = $RSClassSubjects->FetchRow())
				{
					$this->AssignedSubjects[$SearchRow->classSubjectID]['SubjectID'] = $SearchRow->subjectID;
					$this->AssignedSubjects[$SearchRow->classSubjectID]['Subject'] = $SearchRow->subject;
					$this->AssignedSubjects[$SearchRow->classSubjectID]['SubjectMarksType'] = $SearchRow->subjectMarksType;
				}
			}

			return true;   
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException: AddedClass::SaveAssignedSubjects. Stack Trace: '.$e->getTraceAsString());
            return false;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception: AddedClass::SaveAssignedSubjects . Stack Trace: '.$e->getTraceAsString());
            return false;
        }
    }

    public function FillAssignedSubjectsMarksType()
    {
		try
        {	
        	$DBConnObject = new DBConnect();

        	$RSClassSubjects = $this->DBObject->Prepare('SELECT acs.classSubjectID, acs.subjectID, acs.subjectMarksType 
														 FROM asa_class_subjects acs 
														 WHERE classID = :|1 ORDER BY acs.priority;');
			$RSClassSubjects->Execute($this->ClassID);

			if ($RSClassSubjects->Result->num_rows  > 0)
			{
				while($SearchRow = $RSClassSubjects->FetchRow())
				{
					$this->AssignedSubjectsMarksType[$SearchRow->subjectID] = $SearchRow->subjectMarksType;
				}
			}

			return true;   
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException: AddedClass::FillAssignedSubjectsMarksType. Stack Trace: '.$e->getTraceAsString());
            return false;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception: AddedClass::FillAssignedSubjectsMarksType . Stack Trace: '.$e->getTraceAsString());
            return false;
        }
    }

	public function SaveClassSections()
	{
		$AssignedClassSectionIds = '';

		try
		{
			if (is_array($this->ClassSectionsList) && count($this->ClassSectionsList) > 0)
	        {
	        	$this->DBObject->BeginTransaction();

	        	foreach ($this->ClassSectionsList as $SectionMasterID => $Priority)
	        	{

	        		$ClassSectionID = 0;

	        		$RSClassSection = $this->DBObject->Prepare('SELECT classSectionID, priority FROM asa_class_sections WHERE classID = :|1 AND sectionMasterID = :|2 LIMIT 1;');
		            $RSClassSection->Execute($this->ClassID, $SectionMasterID);

		            if ($RSClassSection->Result->num_rows > 0)
		            {	
		            	$ClassSectionID = $RSClassSection->FetchRow()->classSectionID;

		            	$RSClassSectionPriorityUpdate = $this->DBObject->Prepare('UPDATE asa_class_sections
																					SET	priority = :|1
																					WHERE classSectionID = :|2;');
						$RSClassSectionPriorityUpdate->Execute($Priority, $ClassSectionID);

						$AssignedClassSectionIds .= $ClassSectionID . ', ';

		            }
		            else
		            {
		            	$RSSave = $this->DBObject->Prepare('INSERT INTO asa_class_sections (classID, sectionMasterID, priority, createUserID, createDate)
														VALUES (:|1, :|2, :|3, :|4, NOW());');
						$RSSave->Execute($this->ClassID, $SectionMasterID, $Priority, $this->CreateUserID);

						$ClassSectionID = $RSSave->LastID;

						$AssignedClassSectionIds .= $ClassSectionID . ', ';
		            }
		        }   

		        $AssignedClassSectionIds = substr($AssignedClassSectionIds, 0, -2);

	            $RSDelete = $this->DBObject->Prepare('DELETE FROM asa_class_sections WHERE classID = :|1 AND classSectionID NOT IN (' . $AssignedClassSectionIds . ');');
		    	$RSDelete->Execute($this->ClassID);

		        $this->DBObject->CommitTransaction();
		    }
		    else
		    {
			    $RSDelete = $this->DBObject->Prepare('DELETE FROM asa_class_sections WHERE classID = :|1;');
			    $RSDelete->Execute($this->ClassID);
		    }

			return true;
		}
		catch (ApplicationDBException $e)
		{

			$this->DBObject->RollBackTransaction();
			error_log('DEBUG: ApplicationDBException at AddedClass::SaveClassSections(). Stack Trace: ' . $e->getTraceAsString());
			return false;
		}
		catch (Exception $e)
		{
			$this->DBObject->RollBackTransaction();
			error_log('DEBUG: Exception at AddedClass::SaveClassSections(). Stack Trace: ' . $e->getTraceAsString());
			return false;
		} 
	}

	public function SaveAssignedSubjects()
    {
		$AssignedClassSubjectIDs = '';   
		$NewAssignedSubjects = '';

		try
        {
        	if (count($this->AssignedSubjects) > 0)
        	{	
        		$this->DBObject->BeginTransaction();

        		foreach ($this->AssignedSubjects as $SubjectID => $SubjectMarksType)
				{
					$AssignedClassSubjectIDs .= $SubjectID . ', ';

					$RSClassSubjectCount = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM asa_class_subjects WHERE classID = :|1 AND subjectID = :|2;');
		            $RSClassSubjectCount->Execute($this->ClassID, $SubjectID);

		            if ($RSClassSubjectCount->FetchRow()->totalRecords == 0)
		            {
		            	$RSSaveClassSubjects = $this->DBObject->Prepare('INSERT INTO asa_class_subjects (classID, subjectID, subjectMarksType, createUserID, createDate) VALUES (:|1, :|2, :|3, :|4, NOW());');
						$RSSaveClassSubjects->Execute($this->ClassID, $SubjectID, $SubjectMarksType, $this->CreateUserID);
		            }
		            else
		            {
		            	$RSSaveClassSubjects = $this->DBObject->Prepare('UPDATE asa_class_subjects
																		SET	subjectMarksType = :|1
																		WHERE classID = :|2 AND subjectID = :|3 LIMIT 1');
						$RSSaveClassSubjects->Execute($SubjectMarksType, $this->ClassID, $SubjectID);
		            }
				}					
 
				$AssignedClassSubjectIDs = substr($AssignedClassSubjectIDs, 0, -2);

				$DeleteClassSubjects = $this->DBObject->Prepare('DELETE FROM asa_class_subjects WHERE classID = :|1 AND subjectID NOT IN(' . $AssignedClassSubjectIDs . ');');
				$DeleteClassSubjects->Execute($this->ClassID);

				$this->DBObject->CommitTransaction();

				return true;
        	}
        	else
        	{
        		$DeleteClassSubjects = $this->DBObject->Prepare('DELETE FROM asa_class_subjects WHERE classID = :|1;');
				$DeleteClassSubjects->Execute($this->ClassID);
        	}
        }
        catch (ApplicationDBException $e)
        {
        	$this->DBObject->RollBackTransaction();
            error_log('DEBUG: ApplicationDBException: AddedClass::SaveAssignedSubjects. Stack Trace: ' . $e->getTraceAsString());
            return false;
        }
        catch (Exception $e)
        {
        	$this->DBObject->RollBackTransaction();
            error_log('DEBUG: Exception: AddedClass::SaveAssignedSubjects . Stack Trace: ' . $e->getTraceAsString());
            return false;
        }
    }

    public function SaveClassSubjectPriority()
    {
		try
        {
        	if (count($this->AssignedSubjects) > 0)
        	{
	        	$this->DBObject->BeginTransaction();

	        	foreach ($this->AssignedSubjects as $ClassSubjectID => $Details) 
	        	{
	        		$RSUpdate = $this->DBObject->Prepare('UPDATE asa_class_subjects
															SET	priority = :|1
															WHERE classSubjectID = :|2 LIMIT 1;');
					$RSUpdate->Execute($Details['Priority'], $ClassSubjectID);
	        	}

				$this->DBObject->CommitTransaction();

				return true;
        	}
        }
        catch (ApplicationDBException $e)
        {
        	$this->DBObject->RollBackTransaction();
            error_log('DEBUG: ApplicationDBException: AddedClass::SaveClassSubjectPriority. Stack Trace: ' . $e->getTraceAsString());
            return false;
        }
        catch (Exception $e)
        {
        	$this->DBObject->RollBackTransaction();
            error_log('DEBUG: Exception: AddedClass::SaveClassSubjectPriority . Stack Trace: ' . $e->getTraceAsString());
            return false;
        }
    }

    public function FillClassSections()
    {
        try
        {
            $RSSearch = $this->DBObject->Prepare('SELECT sectionMasterID, priority FROM asa_class_sections WHERE classID = :|1;');
            $RSSearch->Execute($this->ClassID);

            if ($RSSearch->Result->num_rows <= 0)
            {
                return true;
            }

            while($SearchRow = $RSSearch->FetchRow())
            {
            	$this->ClassSectionsList[$SearchRow->sectionMasterID] = $SearchRow->priority;
            }

            return true;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at AddedClass::FillClassSections(). Stack Trace: ' . $e->getTraceAsString());
            return false;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception at AddedClass::FillClassSections(). Stack Trace: ' . $e->getTraceAsString());
            return false;
        }
    }

    public function GetNextClassIDSectionID($ClassSectionID, &$NextClassID = 0 , &$NextClassSection = 0)
	{
        try
        {	

        	$RSNextClassSectionSearch = $this->DBObject->Prepare('SELECT classSectionID, classID, sectionMasterID 
            														FROM asa_class_sections 
            														WHERE classID = :|1 AND priority > 
        															(SELECT priority FROM asa_class_sections WHERE classSectionID = :|2) ORDER BY priority ASC LIMIT 1;');
			$RSNextClassSectionSearch->Execute($this->ClassID, $ClassSectionID);

			if ($RSNextClassSectionSearch->Result->num_rows > 0)
			{
				$NextClassSectionSearchRow = $RSNextClassSectionSearch->FetchRow();

				$NextClassID = $NextClassSectionSearchRow->classID;
				$NextClassSection  = $NextClassSectionSearchRow->classSectionID;

				return true;	
			}

            $RSNextClassSearch = $this->DBObject->Prepare('SELECT classSectionID, classID, sectionMasterID FROM asa_class_sections  WHERE classID = (SELECT classID 
                										FROM asa_classes WHERE priority > 
                										(SELECT priority FROM asa_classes WHERE classID = :|1) LIMIT 1) LIMIT 1;');
            $RSNextClassSearch->Execute($this->ClassID);

            if ($RSNextClassSearch->Result->num_rows > 0)
            {
            	$NextClassSearchRow = $RSNextClassSearch->FetchRow();

	      		$NextClassID = $NextClassSearchRow->classID;
	    		$NextClassSection  = $NextClassSearchRow->classSectionID;

	    		return true;
            }
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at AddedClass::GetNextClassIDSectionID(). Stack Trace: ' . $e->getTraceAsString());
            return false;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception at AddedClass::GetNextClassIDSectionID(). Stack Trace: ' . $e->getTraceAsString());
            return false;
        }
	}

	// END OF PUBLIC METHODS	//
	
	// START OF STATIC METHODS	//

	static function GetAllClasses($GetOnlyNames = false)
    {
        $AllClasses = array();

        try
        {
            $DBConnObject = new DBConnect();

            $RSSearch = $DBConnObject->Prepare('SELECT ac.*, u.userName AS createUserName FROM asa_classes ac 
												INNER JOIN users u ON ac.createUserID = u.userID
												ORDER BY ac.priority;');
            $RSSearch->Execute();

            if ($RSSearch->Result->num_rows <= 0)
            {
                return $AllClasses;
            }

            while($SearchRow = $RSSearch->FetchRow())
            {
				if ($GetOnlyNames)
				{
					$AllClasses[$SearchRow->classID] = $SearchRow->className;
					continue;
				}

                $AllClasses[$SearchRow->classID]['AcademicYearID'] = $SearchRow->academicYearID;
                
                $AllClasses[$SearchRow->classID]['ClassName'] = $SearchRow->className;
                $AllClasses[$SearchRow->classID]['ClassSymbol'] = $SearchRow->classSymbol;
		        $AllClasses[$SearchRow->classID]['HasDifferentSubjects'] = $SearchRow->hasDifferentSubjects;
		        $AllClasses[$SearchRow->classID]['Priority'] = $SearchRow->priority;

		        $AllClasses[$SearchRow->classID]['CreateUserName'] = $SearchRow->createUserName;

		        $AllClasses[$SearchRow->classID]['IsActive'] = $SearchRow->isActive;
		        $AllClasses[$SearchRow->classID]['CreateUserID'] = $SearchRow->createUserID;
		        $AllClasses[$SearchRow->classID]['CreateDate'] = $SearchRow->createDate;
            }

            return $AllClasses;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at AddedClass::GetAllClasses(). Stack Trace: ' . $e->getTraceAsString());
            return $AllClasses;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception at AddedClass::GetAllClasses(). Stack Trace: ' . $e->getTraceAsString());
            return $AllClasses;
        }
	}

	static function GetActiveClasses()
	{
		$AllClasses = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT classID, className FROM asa_classes 
			                                    WHERE isActive = 1
			                                    ORDER BY priority;');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllClasses;
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$AllClasses[$SearchRow->classID] = $SearchRow->className;
			}
			
			return $AllClasses;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at AddedClass::GetActiveClasses(). Stack Trace: ' . $e->getTraceAsString());
			return $AllClasses;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at AddedClass::GetActiveClasses(). Stack Trace: ' . $e->getTraceAsString());
			return $AllClasses;
		}		
	}

	static function GetClassSections($ClassID)
	{
		$SectionList = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT acs.classSectionID, asm.sectionName FROM asa_class_sections acs 
												INNER JOIN asa_section_master asm ON acs.sectionMasterID = asm.sectionMasterID 
												WHERE classID = :|1;');
            $RSSearch->Execute($ClassID);
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $SectionList;
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$SectionList[$SearchRow->classSectionID] = $SearchRow->sectionName;
			}
			
			return $SectionList;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at AddedClass::GetClassSections(). Stack Trace: ' . $e->getTraceAsString());
			return $SectionList;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at AddedClass::GetClassSections(). Stack Trace: ' . $e->getTraceAsString());
			return $SectionList;
		}		
	}

	static function GetClassSubjects($ClassID, $GetNameOnly = true)
	{
		$ClassSubejcts = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSClassSubjects = $DBConnObject->Prepare('SELECT acs.classSubjectID, asm.subject, acs.priority 
														FROM asa_class_subjects acs
													   INNER JOIN asa_subject_master asm ON acs.subjectID = asm.subjectID
													   WHERE classID = :|1 ORDER BY priority;');
			$RSClassSubjects->Execute($ClassID);
			
			if ($RSClassSubjects->Result->num_rows <= 0)
			{
				return $ClassSubejcts;
			}
			
			while($SearchRow = $RSClassSubjects->FetchRow())
			{	
				if ($GetNameOnly) 
				{
					$ClassSubejcts[$SearchRow->classSubjectID] = $SearchRow->subject;
					continue;
				}

				$ClassSubejcts[$SearchRow->classSubjectID]['SubjectName'] = $SearchRow->subject;
				$ClassSubejcts[$SearchRow->classSubjectID]['Priority'] = $SearchRow->priority;
			}
			
			return $ClassSubejcts;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at AddedClass::GetClassSubjects(). Stack Trace: ' . $e->getTraceAsString());
			return $ClassSubejcts;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at AddedClass::GetClassSubjects(). Stack Trace: ' . $e->getTraceAsString());
			return $ClassSubejcts;
		}		
	}
	
	static function GetClassIDAndSubjectID($ClassSubjectID)
	{
		$ClassSubejctDetails = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT * FROM asa_class_subjects
													   WHERE classSubjectID = :|1 LIMIT 1;');
			$RSSearch->Execute($ClassSubjectID);
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $ClassSubejctDetails;
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$ClassSubejctDetails[$SearchRow->classSubjectID]['ClassID'] = $SearchRow->classID;
				$ClassSubejctDetails[$SearchRow->classSubjectID]['SubjectID'] = $SearchRow->subjectID;
			}
			
			return $ClassSubejctDetails;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at AddedClass::GetClassIDAndSubjectID(). Stack Trace: ' . $e->getTraceAsString());
			return $ClassSubejctDetails;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at AddedClass::GetClassIDAndSubjectID(). Stack Trace: ' . $e->getTraceAsString());
			return $ClassSubejctDetails;
		}		
	}
	
	
	static function GetClassSubjectTeachers($DayID, $ClassID, $ClassSubjectID, $StartTime, $EndTime, $PeriodTimingID)
    {
        $AllTeachers = array();

        try
        {
            $DBConnObject = new DBConnect();

            $RSSearch = $DBConnObject->Prepare('SELECT atc.teacherClassID, abs.branchStaffID, abs.firstName, abs.lastName 
												FROM asa_teacher_classes atc 
												INNER JOIN asa_teacher_subjects ats ON ats.branchStaffID = atc.branchStaffID 
												INNER JOIN asa_class_subjects acsub ON acsub.subjectID = ats.subjectID AND acsub.classID = atc.classID 
												INNER JOIN asa_branch_staff abs ON abs.branchStaffID = atc.branchStaffID 
												WHERE atc.classID = :|1 AND acsub.classSubjectID = :|2;');
			
            $RSSearch->Execute($ClassID, $ClassSubjectID);

            if ($RSSearch->Result->num_rows <= 0)
            {
                return $AllTeachers;
            }

			$RSGetActivatedSession = $DBConnObject->Prepare('SELECT schoolSessionID FROM asa_session_activation_details WHERE isDecativated = 0 LIMIT 1;');
			$RSGetActivatedSession->Execute();
			
			if ($RSGetActivatedSession->Result->num_rows <= 0)
			{
				error_log('Criticle Error: No currently activated session found while creating timetable.');
				return $AllTeachers;
			}
			
			$ActivatedSessionID = $RSGetActivatedSession->FetchRow()->schoolSessionID;
			
            while ($SearchRow = $RSSearch->FetchRow())
            {
				$AllTeachers[$SearchRow->teacherClassID]['IsBusy'] = 0;
				
				/*$CheckTeacherAvailability = $DBConnObject->Prepare('SELECT COUNT(*) AS totalRecords 
																	FROM asa_class_time_table actt 
																	INNER JOIN asa_class_time_table_details acttd ON acttd.classTimeTableID = actt.classTimeTableID 
																	INNER JOIN asa_school_session_class_period_timings asscpt ON asscpt.periodTimingID = acttd.periodTimingID 
																	WHERE acttd.teacherClassID = :|1 AND 
																	actt.daywiseTimingsID 
																	IN (SELECT daywiseTimingsID FROM asa_school_session_class_daywise_timings 
																	WHERE schoolSessionID = :|2 AND dayID = :|3) AND 
																	(asscpt.periodStartTime BETWEEN TIME(:|4) + INTERVAL 1 MINUTE AND TIME(:|5) + INTERVAL 1 MINUTE OR asscpt.periodEndTime BETWEEN TIME(:|6) + INTERVAL 1 MINUTE AND TIME(:|7) + INTERVAL 1 MINUTE);');
				
				$CheckTeacherAvailability->Execute($SearchRow->teacherClassID, $ActivatedSessionID, $DayID, $StartTime, $EndTime, $StartTime, $EndTime);*/
				
				$CheckTeacherAvailability = $DBConnObject->Prepare('SELECT COUNT(*) AS totalRecords, ac.className, asm.sectionName
																	FROM asa_class_time_table actt 
																	INNER JOIN asa_class_sections acs ON acs.classSectionID = actt.classSectionID
																	INNER JOIN asa_classes ac ON ac.classID = acs.classID
																	INNER JOIN asa_section_master asm ON asm.sectionMasterID = acs.sectionMasterID
																	INNER JOIN asa_class_time_table_details acttd ON acttd.classTimeTableID = actt.classTimeTableID 
																	INNER JOIN asa_teacher_classes atc ON atc.teacherClassID = acttd.teacherClassID 
																	INNER JOIN asa_branch_staff abs ON abs.branchStaffID = atc.branchStaffID 
																	INNER JOIN asa_school_session_class_period_timings asscpt ON asscpt.periodTimingID = acttd.periodTimingID 
																	WHERE abs.branchStaffID = :|1 
																	AND asscpt.periodTimingID != :|2 
																	AND actt.daywiseTimingsID IN (SELECT daywiseTimingsID FROM asa_school_session_class_daywise_timings WHERE schoolSessionID = :|3 AND dayID = :|4) 
																	AND (asscpt.periodStartTime BETWEEN :|5 AND :|6 AND asscpt.periodEndTime BETWEEN :|7 AND :|8);');
				
				$CheckTeacherAvailability->Execute($SearchRow->branchStaffID, $PeriodTimingID, $ActivatedSessionID, $DayID, $StartTime, $EndTime, $StartTime, $EndTime);
				
				$CheckTeacherAvailabilityRow = $CheckTeacherAvailability->FetchRow();
				
				if ($CheckTeacherAvailabilityRow->totalRecords > 0)
				{
					$AllTeachers[$SearchRow->teacherClassID]['IsBusy'] = 1;

					$AllTeachers[$SearchRow->teacherClassID]['ClassSymbol'] = $CheckTeacherAvailabilityRow->className;
					$AllTeachers[$SearchRow->teacherClassID]['SectionName'] = $CheckTeacherAvailabilityRow->sectionName;
				}
				
				$AllTeachers[$SearchRow->teacherClassID]['FirstName'] = $SearchRow->firstName;
				$AllTeachers[$SearchRow->teacherClassID]['LastName'] = $SearchRow->lastName;
            }

            return $AllTeachers;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at AddedClass::GetClassSubjectTeachers(). Stack Trace: ' . $e->getTraceAsString());
            return $AllTeachers;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception at AddedClass::GetClassSubjectTeachers(). Stack Trace: ' . $e->getTraceAsString());
            return $AllTeachers;
        }
    }
	
	static function GetAvailableTeachersForSubstitution($DayID, $ClassID, $ClassSubjectID, $StartTime, $EndTime)
    {
        $AllTeachers = array();

        try
        {
            $DBConnObject = new DBConnect();

            $RSSearch = $DBConnObject->Prepare('SELECT atc.teacherClassID, abs.firstName, abs.lastName 
												FROM asa_teacher_classes atc 
												INNER JOIN asa_teacher_subjects ats ON ats.branchStaffID = atc.branchStaffID 
												INNER JOIN asa_class_subjects acsub ON acsub.subjectID = ats.subjectID AND acsub.classID = atc.classID 
												INNER JOIN asa_branch_staff abs ON abs.branchStaffID = atc.branchStaffID 
												INNER JOIN asa_staff_attendence_details asad ON asad.branchStaffID = abs.branchStaffID AND asad.attendenceStatus = \'Present\' 
												INNER JOIN asa_staff_attendence asa ON asa.staffAttendenceID = asad.staffAttendenceID AND asa.attendenceDate = CURRENT_DATE 
												WHERE atc.classID = :|1 AND acsub.classSubjectID = :|2;');
			
            $RSSearch->Execute($ClassID, $ClassSubjectID);

            if ($RSSearch->Result->num_rows <= 0)
            {
                return $AllTeachers;
            }

			$RSGetActivatedSession = $DBConnObject->Prepare('SELECT schoolSessionID FROM asa_session_activation_details WHERE isDecativated = 0 LIMIT 1;');
			$RSGetActivatedSession->Execute();
			
			if ($RSGetActivatedSession->Result->num_rows <= 0)
			{
				error_log('Criticle Error: No currently activated session found while creating timetable.');
				return $AllTeachers;
			}
			
			$ActivatedSessionID = $RSGetActivatedSession->FetchRow()->schoolSessionID;
			
            while ($SearchRow = $RSSearch->FetchRow())
            {
				$AllTeachers[$SearchRow->teacherClassID]['IsBusy'] = '';
				
				$CheckTeacherAvailability = $DBConnObject->Prepare('SELECT COUNT(*) AS totalRecords 
																	FROM asa_class_time_table actt 
																	INNER JOIN asa_class_time_table_details acttd ON acttd.classTimeTableID = actt.classTimeTableID 
																	INNER JOIN asa_school_session_class_period_timings asscpt ON asscpt.periodTimingID = acttd.periodTimingID 
																	WHERE acttd.teacherClassID = :|1 AND actt.daywiseTimingsID IN 
																	(SELECT daywiseTimingsID FROM asa_school_session_class_daywise_timings WHERE schoolSessionID = :|2 AND dayID = :|3) AND 
																	(asscpt.periodStartTime BETWEEN :|4 AND :|5 AND asscpt.periodEndTime BETWEEN :|6 AND :|7);');
				
				$CheckTeacherAvailability->Execute($SearchRow->teacherClassID, $ActivatedSessionID, $DayID, $StartTime, $EndTime, $StartTime, $EndTime);
				
				if ($CheckTeacherAvailability->FetchRow()->totalRecords > 0)
				{
					$AllTeachers[$SearchRow->teacherClassID]['IsBusy'] = 'Class';

					$AllTeachers[$SearchRow->teacherClassID]['ClassSymbol'] = '';
					$AllTeachers[$SearchRow->teacherClassID]['SectionName'] = '';
				}
				
				$CheckTeacherSubstitution = $DBConnObject->Prepare('SELECT COUNT(*) AS totalRecords 
																	FROM asa_class_substitution acs 
																	INNER JOIN asa_class_time_table_details acttd ON acttd.classTimeTableDetailID = acs.classTimeTableDetailID AND acs.teacherClassID = acttd.teacherClassID 
																	INNER JOIN asa_class_time_table actt ON actt.classTimeTableID = acttd.classTimeTableID
																	INNER JOIN asa_school_session_class_period_timings asscpt ON asscpt.periodTimingID = acttd.periodTimingID 
																	WHERE acs.teacherClassID = :|1 AND actt.daywiseTimingsID IN 
																	(SELECT daywiseTimingsID FROM asa_school_session_class_daywise_timings WHERE schoolSessionID = :|2 AND dayID = :|3) AND 
																	(asscpt.periodStartTime BETWEEN :|4 AND :|5 AND asscpt.periodEndTime BETWEEN :|6 AND :|7);');
				
				$CheckTeacherSubstitution->Execute($SearchRow->teacherClassID, $ActivatedSessionID, $DayID, $StartTime, $StartTime, $EndTime, $EndTime);
				
				if ($CheckTeacherSubstitution->FetchRow()->totalRecords > 0)
				{
					$AllTeachers[$SearchRow->teacherClassID]['IsBusy'] = 'Substitution';

					$AllTeachers[$SearchRow->teacherClassID]['ClassSymbol'] = '';
					$AllTeachers[$SearchRow->teacherClassID]['SectionName'] = '';
				}
				
				$AllTeachers[$SearchRow->teacherClassID]['FirstName'] = $SearchRow->firstName;
				$AllTeachers[$SearchRow->teacherClassID]['LastName'] = $SearchRow->lastName;
            }

            return $AllTeachers;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at AddedClass::GetAvailableTeachersForSubstitution(). Stack Trace: ' . $e->getTraceAsString());
            return $AllTeachers;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception at AddedClass::GetAvailableTeachersForSubstitution(). Stack Trace: ' . $e->getTraceAsString());
            return $AllTeachers;
        }
    }
	
	static function GetAvailableTeachersForSubstitution1($DayID, $ClassID, $ClassSubjectID, $StartTime, $EndTime)
    {
        $AllTeachers = array();

        try
        {
            $DBConnObject = new DBConnect();

            $RSSearch = $DBConnObject->Prepare('SELECT atc.teacherClassID, abs.firstName, abs.lastName, ac.className, ac.classSymbol, asm.sectionName, actd.classTimeTableDetailID, asscpt.periodStartTime, asscpt.periodEndTime  
												FROM asa_teacher_classes atc 
												INNER JOIN asa_teacher_subjects ats ON ats.branchStaffID = atc.branchStaffID 
												INNER JOIN asa_class_subjects acsub ON acsub.subjectID = ats.subjectID AND acsub.classID = atc.classID 
												INNER JOIN asa_branch_staff abs ON abs.branchStaffID = atc.branchStaffID 
												LEFT JOIN asa_class_time_table_details actd ON actd.teacherClassID = atc.teacherClassID 
												LEFT JOIN asa_class_time_table act ON act.classTimeTableID = actd.classTimeTableID AND act.daywiseTimingsID = 
												(
												SELECT daywiseTimingsID FROM asa_school_session_class_daywise_timings WHERE schoolSessionID = 
												(SELECT schoolSessionID FROM asa_session_activation_details WHERE isDecativated = 0 LIMIT 1) AND classID = atc.classID AND dayID = :|1 
												)
												LEFT JOIN asa_school_session_class_period_timings asscpt ON asscpt.periodTimingID = actd.periodTimingID AND asscpt.periodStartTime < :|2 AND asscpt.periodEndTime > :|3 
												LEFT JOIN asa_class_sections acs ON acs.classSectionID = act.classSectionID 
												LEFT JOIN asa_classes ac ON ac.classID = acs.classID 
												LEFT JOIN asa_section_master asm ON asm.sectionMasterID = acs.sectionMasterID 
												WHERE atc.classID = :|4 AND acsub.classSubjectID = :|5 AND ac.classID IS NULL AND asscpt.periodStartTime IS NULL AND abs.branchStaffID IN (SELECT branchStaffID FROM asa_staff_attendence_details WHERE staffAttendenceID = (SELECT staffAttendenceID FROM asa_staff_attendence WHERE staffCategory = \'Teaching\' AND attendenceDate = CURRENT_DATE));');

            $RSSearch->Execute($DayID, $EndTime, $StartTime, $ClassID, $ClassSubjectID);

            if ($RSSearch->Result->num_rows <= 0)
            {
                return $AllTeachers;
            }

            while ($SearchRow = $RSSearch->FetchRow())
            {
				$AllTeachers[$SearchRow->teacherClassID]['ClassName'] = '';
				$AllTeachers[$SearchRow->teacherClassID]['ClassSymbol'] = '';
				$AllTeachers[$SearchRow->teacherClassID]['SectionName'] = '';
				
				$AllTeachers[$SearchRow->teacherClassID]['FirstName'] = $SearchRow->firstName;
				$AllTeachers[$SearchRow->teacherClassID]['LastName'] = $SearchRow->lastName;

				$AllTeachers[$SearchRow->teacherClassID]['PeriodStartTime'] = $SearchRow->periodStartTime;
				$AllTeachers[$SearchRow->teacherClassID]['PeriodEndTime'] = $SearchRow->periodEndTime;
				
				if (!$SearchRow->periodStartTime)
				{
					$RSSearchSubstitution = $DBConnObject->Prepare('SELECT ac.className, ac.classSymbol, asm.sectionName 
																	FROM asa_class_substitution acsubstitution 
																	INNER JOIN asa_class_time_table_details actd ON actd.classTimeTableDetailID = acsubstitution.classTimeTableDetailID 
																	INNER JOIN asa_class_time_table act ON act.classTimeTableID = actd.classTimeTableID AND act.daywiseTimingsID IN 
																	(
																	 SELECT daywiseTimingsID FROM asa_school_session_class_daywise_timings WHERE schoolSessionID = 
																	(SELECT schoolSessionID FROM asa_session_activation_details WHERE isDecativated = 0 LIMIT 1) AND dayID = :|1 
																	)
																	INNER JOIN asa_school_session_class_period_timings asscpt ON asscpt.periodTimingID = actd.periodTimingID AND asscpt.periodStartTime <= :|2 AND asscpt.periodEndTime >= :|3 
																	INNER JOIN asa_class_sections acs ON acs.classSectionID = act.classSectionID 
																	INNER JOIN asa_classes ac ON ac.classID = acs.classID 
																	INNER JOIN asa_section_master asm ON asm.sectionMasterID = acs.sectionMasterID
																	WHERE acsubstitution.substitutionDate = CURRENT_DATE AND acsubstitution.teacherClassID = :|4;');

					$RSSearchSubstitution->Execute($DayID, $StartTime, $EndTime, $SearchRow->teacherClassID);

					if ($RSSearchSubstitution->Result->num_rows > 0)
					{
						$SearchSubstitutionRow = $RSSearchSubstitution->FetchRow();

						$AllTeachers[$SearchRow->teacherClassID]['ClassName'] = $SearchSubstitutionRow->className;
						$AllTeachers[$SearchRow->teacherClassID]['ClassSymbol'] = $SearchSubstitutionRow->classSymbol;
						$AllTeachers[$SearchRow->teacherClassID]['SectionName'] = $SearchSubstitutionRow->sectionName;
					}
				}
            }

            return $AllTeachers;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at AddedClass::GetAvailableTeachersForSubstitution(). Stack Trace: ' . $e->getTraceAsString());
            return $AllTeachers;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception at AddedClass::GetAvailableTeachersForSubstitution(). Stack Trace: ' . $e->getTraceAsString());
            return $AllTeachers;
        }
    }

    static function SetClassesPriority($AllPriorities)
    {
    	try
		{
			$DBConnObject = new DBConnect();
			
			if (is_array($AllPriorities) && count($AllPriorities) > 0)
			{
				foreach ($AllPriorities as $ClassID => $Priority)
				{
					$RSUpdateClassPriority = $DBConnObject->Prepare('UPDATE asa_classes SET priority = :|1 WHERE classID = :|2 LIMIT 1;');
					$RSUpdateClassPriority->Execute($Priority, $ClassID);
				}
			}
			
			return true;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException: AddedClass::SetClassesPriority(). Stack Trace: ' . $e->getTraceAsString());
			return false;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception: AddedClass::SetClassesPriority(). Stack Trace: ' . $e->getTraceAsString());
			return false;
		}
    }

    // school_off_rule
    static function GetClassAndSections()
	{
		$ClassAndSections = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT acs.classSectionID, CONCAT(ac.className, "(", asm.sectionName, ")") AS classSectionName 
												FROM asa_class_sections acs
												INNER JOIN asa_section_master asm ON acs.sectionMasterID = asm.sectionMasterID
												INNER JOIN asa_classes ac ON acs.classID = ac.classID
												ORDER BY classSectionName;');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $ClassAndSections;
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$ClassAndSections[$SearchRow->classSectionID] = $SearchRow->classSectionName;
			}
			
			return $ClassAndSections;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at AddedClass::GetClassAndSections(). Stack Trace: ' . $e->getTraceAsString());
			return $ClassAndSections;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at AddedClass::GetClassAndSections(). Stack Trace: ' . $e->getTraceAsString());
			return $ClassAndSections;
		}		
	}

	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//
	private function SaveDetails()
	{
		if ($this->ClassID == 0)
		{
			$RSSave = $this->DBObject->Prepare('INSERT INTO asa_classes (academicYearID, className, classSymbol, hasDifferentSubjects,
													priority, isActive, createUserID, createDate)
														VALUES (:|1, :|2, :|3, :|4, :|5, :|6, :|7 ,NOW());');
		
			$RSSave->Execute($this->AcademicYearID, $this->ClassName, $this->ClassSymbol, $this->HasDifferentSubjects, 
									$this->Priority, $this->IsActive, $this->CreateUserID);
			
			$this->ClassID = $RSSave->LastID;
		}
		else
		{
			$RSUpdate = $this->DBObject->Prepare('UPDATE asa_classes
													SET	academicYearID = :|1,
													    className = :|2,
													    classSymbol = :|3,
													    hasDifferentSubjects = :|4,
													    priority = :|5,
													    isActive = :|6
													WHERE classID = :|7 LIMIT 1;');
													
			$RSUpdate->Execute($this->AcademicYearID, $this->ClassName, $this->ClassSymbol, $this->HasDifferentSubjects, 
									$this->Priority, $this->IsActive, $this->ClassID);
		}
		
		return true;
	}

	private function RemoveClass()
    {
        if (!isset($this->ClassID)) 
        {
            throw new ApplicationARException('', APP_AR_ERROR_DELETE_WITHOUT_ID);
        }
        
        $RSDeleteAddedClass = $this->DBObject->Prepare('DELETE FROM asa_classes WHERE classID = :|1 LIMIT 1;');
        $RSDeleteAddedClass->Execute($this->ClassID);                
    }
	
	private function GetClassByID()
	{
		$RSAddedClass = $this->DBObject->Prepare('SELECT * FROM asa_classes WHERE classID = :|1 LIMIT 1;');
		$RSAddedClass->Execute($this->ClassID);
		
		$AddedClassRow = $RSAddedClass->FetchRow();
		
		$this->SetAttributesFromDB($AddedClassRow);				
	}
	
	private function SetAttributesFromDB($AddedClassRow)
	{
		$this->ClassID = $AddedClassRow->classID;
		$this->AcademicYearID = $AddedClassRow->academicYearID;

		$this->ClassName = $AddedClassRow->className;
		$this->ClassSymbol = $AddedClassRow->classSymbol;
		$this->HasDifferentSubjects = $AddedClassRow->hasDifferentSubjects;
		$this->Priority = $AddedClassRow->priority;

		$this->IsActive = $AddedClassRow->isActive;
		$this->CreateUserID = $AddedClassRow->createUserID;
		$this->CreateDate = $AddedClassRow->createDate;
	}	
}
?>
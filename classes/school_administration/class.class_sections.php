<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.db_connect.php');
error_reporting(E_ALL);

class ClassSections
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	
	private $ClassSectionID;
	private $ClassID;
	private $SectionMasterID;
	private $Priority;

	private $CreateUserID;
	private $CreateDate;

	private $AssignedSection = array();
	
	// PUBLIC METHODS START HERE	//
	public function __construct($ClassSectionID = 0, $ClassID = 0, $SectionID = 0)
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;
		
		if ($ClassSectionID != 0)
		{
			$this->ClassSectionID = $ClassSectionID;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetClassSectionsByID();
		}
		else if ($ClassID != 0 && $SectionID != 0)
		{
			$this->ClassID = $ClassID;
			$this->SectionID = $SectionID;
			
			$this->GetClassSectionsByClassIDSectionID();
		}
		else
		{
			//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
			$this->ClassSectionID = 0;
			$this->ClassID = '';
			$this->SectionMasterID = 0;
			$this->Priority = 0;

			$this->CreateUserID = 0;
			$this->CreateDate = '';
			
			$this->AssignedSection = array();
		}
	}
	
	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetClassSectionID()
	{
		return $this->ClassSectionID;
	}
	
	public function GetClassID()
	{
		return $this->ClassID;
	}
	public function SetClassID($ClassID)
	{
		$this->ClassID = $ClassID;
	}

	public function GetSectionMasterID()
	{
		return $this->SectionMasterID;
	}
	public function SetSectionMasterID($SectionMasterID)
	{
		$this->SectionMasterID = $SectionMasterID;
	}

	public function GetPriority()
	{
		return $this->Priority;
	}
	public function SetPriority($Priority)
	{
		$this->Priority = $Priority;
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

	public function GetAssignedSection()
	{
		return $this->AssignedSection;
	}
	public function SetAssignedSection($AssignedSection)
	{
		$this->AssignedSection = $AssignedSection;
	}
	
	public function GetLastErrorCode()
	{
		return $this->LastErrorCode;
	}
	
	//  END OF GETTER AND SETTER FUNCTIONS 	//
	
	public function Save($UnAssignedSections)
	{
		try
		{
			return $this->SaveDetails($UnAssignedSections);
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

	public function CheckDependencies($UnAssignedSections, $ClassID)
    {
        try
        {
        	$DBConnObject = new DBConnect();

        	foreach ($UnAssignedSections as $SectionID => $UnAssignedSectionsDetails)
        	{
        		$RSGetClassSectionID = $DBConnObject->Prepare('SELECT classSectionID FROM asa_class_sections WHERE classID = :|1 AND sectionMasterID = :|2;');
	            $RSGetClassSectionID->Execute($ClassID, $SectionID);

	            while($SearchRow = $RSGetClassSectionID->FetchRow())
	            {
	            	$ClassSectionID = $SearchRow->classSectionID;

	            	$RSStudentsCount = $DBConnObject->Prepare('SELECT COUNT(*) AS totalRecords FROM asa_students WHERE classSectionID = :|1;');
		            $RSStudentsCount->Execute($ClassSectionID);

		            $StudentsCountRow = $RSStudentsCount->FetchRow();

		            if ($StudentsCountRow->totalRecords > 0) 
		            {
		                return false;
		            }

		            $RSSlassAttendenceCount = $DBConnObject->Prepare('SELECT COUNT(*) AS totalRecords FROM class_attendence WHERE classSectionID = :|1;');
		            $RSSlassAttendenceCount->Execute($ClassSectionID);
		    
		            $ClassAttendenceCountRow = $RSSlassAttendenceCount->FetchRow();
		        
		            if ($ClassAttendenceCountRow->totalRecords > 0) 
		            {
		                return false;
		            }
	            }
        	}

            return true;
        }
        catch (ApplicationDBException $e)
        {
            error_log("DEBUG: ApplicationDBException: ClassSections::CheckDependencies");
            return false;
        }
        catch (Exception $e)
        {
            error_log("DEBUG: Exception: ClassSections::CheckDependencies");
            return false;
        }       
    }
	
	public function GetClassTeacherID()
    {
		$BranchStaffID = 0;
		
        try
        {
        	$DBConnObject = new DBConnect();

        	$RSGetClassTeacehrID = $DBConnObject->Prepare('SELECT branchStaffID FROM asa_class_classteachers WHERE classSectionID = :|1;');
			$RSGetClassTeacehrID->Execute($this->ClassSectionID);
			
			if ($RSGetClassTeacehrID->Result->num_rows > 0)
			{
				$BranchStaffID = $RSGetClassTeacehrID->FetchRow()->branchStaffID;
			}

            return $BranchStaffID;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at ClassSections::GetClassTeacherID(). Stack Trace: '.$e->getTraceAsString());
            return $BranchStaffID;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: ApplicationDBException at ClassSections::GetClassTeacherID(). Stack Trace: '.$e->getTraceAsString());
            return $BranchStaffID;
        }       
    }
	
	public function GetClassSectionName()
    {
		$ClassSectionName = '';
		
        try
        {
        	$DBConnObject = new DBConnect();

        	$RSSearch = $DBConnObject->Prepare('SELECT ac.className, asm.sectionName asa_class_sections acs 
												INNER JOIN asa_classes ac ON ac.classID = acs.classID 
												INNER JOIN asa_section_master asm ON asm.sectionMasterID = acs.sectionMasterID 
												WHERE acs.classSectionID = :|1 LIMIT 1;');
			
			$RSSearch->Execute($this->ClassSectionID);
			
			if ($RSGetClassTeacehrID->Result->num_rows <= 0)
			{
				return $ClassSectionName;
			}
			
			$SearchRow = $RSGetClassTeacehrID->FetchRow();
			
			$ClassSectionName = $SearchRow->className . '( ' . $SearchRow->sectionName . ' )';
			
            return $ClassSectionName;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at ClassSections::GetClassSectionName(). Stack Trace: '.$e->getTraceAsString());
            return $ClassSectionName;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: ApplicationDBException at ClassSections::GetClassSectionName(). Stack Trace: '.$e->getTraceAsString());
            return $ClassSectionName;
        }       
    }
		
	// END OF PUBLIC METHODS	//
	
	// START OF STATIC METHODS	//

	static function GetClassSections($ClassID, $GetPrority = false)
    {
        $ClassSectionsList = array();

        try
        {
            $DBConnObject = new DBConnect();

            $RSSearch = $DBConnObject->Prepare('SELECT acs.classSectionID, acs.priority, asm.sectionName 
												FROM asa_class_sections acs 
												INNER JOIN asa_section_master asm ON acs.sectionMasterID = asm.sectionMasterID 
												WHERE acs.classID = :|1;');
            $RSSearch->Execute($ClassID);

            if ($RSSearch->Result->num_rows <= 0)
            {
                return $ClassSectionsList;
            }

			if ($GetPrority)
			{
				while($SearchRow = $RSSearch->FetchRow())
				{
					$ClassSectionsList[$SearchRow->classSectionID] = $SearchRow->priority;
				}

				return $ClassSectionsList;
			}

            while($SearchRow = $RSSearch->FetchRow())
            {
            	$ClassSectionsList[$SearchRow->classSectionID] = $SearchRow->sectionName;
            }

            return $ClassSectionsList;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at ClassSections::GetClassSections(). Stack Trace: '.$e->getTraceAsString());
            return $ClassSectionsList;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception at ClassSections::GetClassSections(). Stack Trace: '.$e->getTraceAsString());
            return $ClassSectionsList;
        }
    }

	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//
	private function SaveDetails($UnAssignedSections)
	{
		if ($this->ClassSectionID == 0)
		{
			if (count($UnAssignedSections) > 0)
	        {
	        	foreach ($UnAssignedSections as $SectionID => $UnAssignedSectionsDetails)
	        	{
	        		$RSGetClassSectionID = $this->DBObject->Prepare('SELECT classSectionID FROM asa_class_sections WHERE classID = :|1 AND sectionMasterID = :|2;');
		            $RSGetClassSectionID->Execute($this->ClassID, $SectionID);

		            while ($SearchRow = $RSGetClassSectionID->FetchRow())
		            {
		            	$ClassSectionID = $SearchRow->classSectionID;
		    	        
		    	        $RSDeleteAddedClass = $this->DBObject->Prepare('DELETE FROM asa_class_sections WHERE classSectionID = :|1 LIMIT 1;');
	    				$RSDeleteAddedClass->Execute($ClassSectionID); 
		            }
		        }   
		    }
		    
			foreach ($this->AssignedSection as $SectionID => $AssignedSectionDetails)
			{
				$RSSave = $this->DBObject->Prepare('INSERT INTO asa_class_sections (classID, sectionMasterID, priority, createUserID, createDate)
															VALUES (:|1, :|2, :|3, :|4, NOW());');
			
				$RSSave->Execute($this->ClassID, $SectionID, $this->Priority[$SectionID], $this->CreateUserID);
				
				$this->ClassSectionID = $RSSave->LastID;
			}
		}
		else
		{
			$RSUpdate = $this->DBObject->Prepare('UPDATE asa_class_sections
													SET	classID = :|1
													WHERE classSectionID = :|2 LIMIT 1;');
													
			$RSUpdate->Execute($this->ClassID, $this->ClassSectionID);
		}
		
		return true;
	}
	
	private function GetClassSectionsByID()
	{
		$RSClassSections = $this->DBObject->Prepare('SELECT * FROM asa_class_sections WHERE classSectionID = :|1 LIMIT 1;');
		$RSClassSections->Execute($this->ClassSectionID);
		
		$ClassSectionsRow = $RSClassSections->FetchRow();
		
		$this->SetAttributesFromDB($ClassSectionsRow);				
	}
	
	private function GetClassSectionsByClassIDSectionID()
	{
		$RSClassSections = $this->DBObject->Prepare('SELECT * FROM asa_class_sections WHERE classID = :|1 AND sectionMasterID = :|2 LIMIT 1;');
		$RSClassSections->Execute($this->ClassID, $this->SectionID);
		
		$ClassSectionsRow = $RSClassSections->FetchRow();
		
		$this->SetAttributesFromDB($ClassSectionsRow);
	}
	
	private function SetAttributesFromDB($ClassSectionsRow)
	{
		$this->ClassSectionID = $ClassSectionsRow->classSectionID;
		$this->ClassID = $ClassSectionsRow->classID;
		$this->SectionMasterID = $ClassSectionsRow->sectionMasterID;
		$this->Priority = $ClassSectionsRow->priority;

		$this->CreateUserID = $ClassSectionsRow->createUserID;
		$this->CreateDate = $ClassSectionsRow->createDate;
	}	
}
?>
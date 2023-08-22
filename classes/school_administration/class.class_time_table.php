<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.db_connect.php');
error_reporting(E_ALL);

class ClassTimeTable
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	
	private $ClassTimeTableID;
	private $ClassSectionID;
	private $DaywiseTimingsID;

	private $IsActive;
	private $CreateUserID;
	private $CreateDate;

	private $TimeTableDetails = array();
	
	// PUBLIC METHODS START HERE	//
	public function __construct($ClassTimeTableID = 0)
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;
		
		if ($ClassTimeTableID != 0)
		{
			$this->ClassTimeTableID = $ClassTimeTableID;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetClassTimeTableByID();
		}
		else
		{
			//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
			$this->ClassTimeTableID = 0;
			$this->ClassSectionID = 0;
			$this->DaywiseTimingsID = 0;
			
			$this->IsActive = 0;
			$this->CreateUserID = 0;
			$this->CreateDate = '';

			$this->TimeTableDetails = array();
		}
	}
	
	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetClassTimeTableID()
	{
		return $this->ClassTimeTableID;
	}
	
	public function GetClassSectionID()
	{
		return $this->ClassSectionID;
	}
	public function SetClassSectionID($ClassSectionID)
	{
		$this->ClassSectionID = $ClassSectionID;
	}

	public function GetDaywiseTimingsID()
	{
		return $this->DaywiseTimingsID;
	}
	public function SetDaywiseTimingsID($DaywiseTimingsID)
	{
		$this->DaywiseTimingsID = $DaywiseTimingsID;
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

	public function GetTimeTableDetails()
	{
		return $this->TimeTableDetails;
	}
	public function SetTimeTableDetails($TimeTableDetails)
	{
		$this->TimeTableDetails = $TimeTableDetails;
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
			$this->DBObject->BeginTransaction();
			if ($this->SaveDetails())
			{
				$this->DBObject->CommitTransaction();
				return true;
			}
			
			$this->DBObject->RollBackTransaction();
			return false;
		}
		catch (ApplicationDBException $e)
		{
			$this->DBObject->RollBackTransaction();
			$this->LastErrorCode = $e->getCode();
			return false;
		}
		catch (ApplicationARException $e)
		{
			$this->DBObject->RollBackTransaction();
			$this->LastErrorCode = $e->getCode();
			return false;
		}
		catch (Exception $e)
		{
			$this->DBObject->RollBackTransaction();
			$this->LastErrorCode = APP_ERROR_UNDEFINED_ERROR;
			return false;
		}
	}
		
	// END OF PUBLIC METHODS	//
	
	// START OF STATIC METHODS	//
	static function GetTeacherTimeTable($BranchStaffID)
	{
		$TeacherTimeTable = array();
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT acttd.classTimeTableDetailID, asscdt.dayID, asm.subject, actt.daywiseTimingsID, asscpt.periodStartTime, asscpt.periodEndTime, 
	        										ac.className, ac.classSymbol, asecm.sectionName, astpm.timingPart 
													FROM asa_teacher_classes atc 
													INNER JOIN asa_class_time_table_details acttd ON atc.teacherClassID = acttd.teacherClassID 
													INNER JOIN asa_class_time_table actt ON acttd.classTimeTableID = actt.classTimeTableID
													INNER JOIN asa_class_sections acsec ON actt.classSectionID = acsec.classSectionID
													INNER JOIN asa_classes ac ON acsec.classID = ac.classID
													INNER JOIN asa_section_master asecm ON acsec.sectionMasterID = asecm.sectionMasterID
													INNER JOIN asa_school_session_class_period_timings asscpt ON acttd.periodTimingID = asscpt.periodTimingID
													INNER JOIN asa_school_timing_parts_master astpm ON astpm.schoolTimingPartID = asscpt.schoolTimingPartID 
													INNER JOIN asa_school_session_class_daywise_timings asscdt ON actt.daywiseTimingsID = asscdt.daywiseTimingsID
													INNER JOIN asa_class_subjects acs ON acs.classSubjectID = acttd.classSubjectID 
													INNER JOIN asa_subject_master asm ON asm.subjectID = acs.subjectID  
													WHERE atc.branchStaffID = :|1 ORDER BY astpm.schoolTimingPartID;');
			$RSSearch->Execute($BranchStaffID);
			
			if ($RSSearch->Result->num_rows > 0)
            {	
				while ($SearchRow = $RSSearch->FetchRow())
				{
					$TeacherTimeTable[$SearchRow->timingPart][$SearchRow->dayID]['Subject'] = $SearchRow->subject;
					$TeacherTimeTable[$SearchRow->timingPart][$SearchRow->dayID]['PeriodStartTime'] = $SearchRow->periodStartTime;
					$TeacherTimeTable[$SearchRow->timingPart][$SearchRow->dayID]['PeriodEndTime'] = $SearchRow->periodEndTime;

					$TeacherTimeTable[$SearchRow->timingPart][$SearchRow->dayID]['ClassDetails'][$SearchRow->classTimeTableDetailID]['ClassName'] = $SearchRow->className;
					$TeacherTimeTable[$SearchRow->timingPart][$SearchRow->dayID]['ClassDetails'][$SearchRow->classTimeTableDetailID]['SectionName'] = $SearchRow->sectionName;
				}	
            }
			return $TeacherTimeTable;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at ClassTimeTable::GetTeacherTimeTable(). Stack Trace: ' . $e->getTraceAsString());
			return $TeacherTimeTable;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at ClassTimeTable::GetTeacherTimeTable(). Stack Trace: ' . $e->getTraceAsString());
			return $TeacherTimeTable;
		}		
	}
	
	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//
	private function SaveDetails()
	{
		if ($this->ClassTimeTableID == 0)
		{
			$RSSave = $this->DBObject->Prepare('INSERT INTO asa_class_time_table (classSectionID, daywiseTimingsID, isActive, createUserID, createDate)
														VALUES (:|1, :|1, :|1, :|1, NOW());');
		
			$RSSave->Execute($this->ClassSectionID, $this->DaywiseTimingsID, $this->IsActive, $this->CreateUserID);
			
			$this->ClassTimeTableID = $RSSave->LastID;

			foreach ($this->TimeTableDetails as $DaywiseTimingsID=>$DaywiseTimingDetails)
			{
				
			}
		}
		else
		{
			$RSUpdate = $this->DBObject->Prepare('UPDATE asa_class_time_table 
													SET	classSectionID = :|1, 
														daywiseTimingsID = :|2, 
														isActive = :|3 
													WHERE classTimeTableID = :|4 LIMIT 1;');
													
			$RSUpdate->Execute($this->ClassSectionID, $this->DaywiseTimingsID, $this->IsActive, $this->ClassTimeTableID);
		}
		
		return true;
	}
	
	private function GetClassTimeTableByID()
	{
		$RSClassTimeTable = $this->DBObject->Prepare('SELECT * FROM asa_class_time_table WHERE classTimeTableID = :|1 LIMIT 1;');
		$RSClassTimeTable->Execute($this->ClassTimeTableID);
		
		$ClassTimeTableRow = $RSClassTimeTable->FetchRow();
		
		$this->SetAttributesFromDB($ClassTimeTableRow);				
	}
	
	private function SetAttributesFromDB($ClassTimeTableRow)
	{
		$this->ClassTimeTableID = $ClassTimeTableRow->classTimeTableID;
		$this->ClassSectionID = $ClassTimeTableRow->classSectionID;
		$this->DaywiseTimingsID = $ClassTimeTableRow->daywiseTimingsID;
		
		$this->IsActive = $ClassTimeTableRow->isActive;
		$this->CreateUserID = $ClassTimeTableRow->createUserID;
		$this->CreateDate = $ClassTimeTableRow->createDate;
	}	
}
?>
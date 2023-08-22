<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.db_connect.php');
error_reporting(E_ALL);

class AchievementsStudent
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	
	private $AchievementsStudentID;
	private $AchievementID;
	private $StudentID;

	private $IsActive;
	private $CreateUserID;
	private $CreateDate;

	private $StudentIDList = array();
	private $RemoveStudentIDList = array();
	
	// PUBLIC METHODS START HERE	//
	public function __construct($AchievementsStudentID = 0)
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;
		
		if($AchievementsStudentID != 0)
		{
			$this->AchievementsStudentID = $AchievementsStudentID;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetAchievementsStudentByID();
		}
		else
		{
			//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
			$this->AchievementsStudentID = 0;
			$this->AchievementID = 0;
			$this->StudentID = 0;

			$this->IsActive = 0;
			$this->CreateUserID = 0;
			$this->CreateDate = '0000-00-00 00:00:00';

			$this->StudentIDList = array();
			$this->RemoveStudentIDList = array();
		}
	}
	
	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetAchievementsStudentID()
	{
		return $this->AchievementsStudentID;
	}
	
	public function GetAchievementID()
	{
		return $this->AchievementID;
	}
	public function SetAchievementID($AchievementID)
	{
		$this->AchievementID = $AchievementID;
	}

	public function GetStudentID()
	{
		return $this->StudentID;
	}
	public function SetStudentID($StudentID)
	{
		$this->StudentID = $StudentID;
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

	public function SetStudentIDList($StudentIDList)
	{
		$this->StudentIDList = $StudentIDList;
	}
	
	public function SetRemoveStudentIDList($RemoveStudentIDList)
	{
		$this->RemoveStudentIDList = $RemoveStudentIDList;
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

	public function Remove()
    {
        try
        {
            $this->RemoveAchievementsStudent();
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
	
	static function FillAchievementsRecordsToStudent($AchievementID, $ClassSectionID, $GetNameOnly = true)
    {
		$AllAchievementsStudents = array();
		
		try
        {	
        	$DBConnObject = new DBConnect();

        	$RSSearch = $DBConnObject->Prepare('SELECT achievementsStudentID, studentID FROM aas_achievements_students 
        										WHERE achievementID = :|1 
        										AND studentID IN (SELECT studentID FROM asa_students WHERE classSectionID = :|2);');
			$RSSearch->Execute($AchievementID, $ClassSectionID);

			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllAchievementsStudents;
			}

			while($SearchRow = $RSSearch->FetchRow())
			{
				if ($GetNameOnly) 
				{
					$AllAchievementsStudents[$SearchRow->achievementsStudentID] = $SearchRow->studentID;
					continue;
				}

				$AllAchievementsStudents[$SearchRow->achievementsStudentID]['StudentID'] = $SearchRow->studentID;
			}

			return $AllAchievementsStudents;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException: AchievementsStudent::FillAchievementsRecordsToStudent(). Stack Trace: ' . $e->getTraceAsString());
            return $AllAchievementsStudents;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception: AchievementsStudent::FillAchievementsRecordsToStudent(). Stack Trace: ' . $e->getTraceAsString());
            return $AllAchievementsStudents;
        }
    }

    static function SearchAchievementsStudents(&$TotalRecords = 0, $GetTotalsOnly = false, $Filters = array(), $Start = 0, $Limit = 100)
	{
		$AllAchievementsStudents = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$Conditions = array();
			
			if (count($Filters) > 0)
			{	
				if (!empty($Filters['AchievementID']))
				{
					$Conditions[] = 'aas.achievementID = '. $DBConnObject->RealEscapeVariable($Filters['AchievementID']);
				}
				if (!empty($Filters['ClassID']))
				{
					$Conditions[] = 'acl.classID = '. $DBConnObject->RealEscapeVariable($Filters['ClassID']);
				}			
				if (!empty($Filters['ClassSectionID']))
				{
					$Conditions[] = 'acs.classSectionID = '. $DBConnObject->RealEscapeVariable($Filters['ClassSectionID']);
				}
				if (!empty($Filters['StudentName']))
				{
					$Conditions[] = 'CONCAT(asd.firstName, " ", asd.lastName) LIKE ' . $DBConnObject->RealEscapeVariable('%' . $Filters['StudentName'] . '%');
				}

				if (!empty($Filters['ActiveStatus']))
				{
					if ($Filters['ActiveStatus'] == 1) //active records
					{
						$Conditions[] = 'aas.isActive = 1';
					}
					else if ($Filters['ActiveStatus'] == 2) //non active records
					{
						$Conditions[] = 'aas.isActive = 0';
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
													FROM aas_achievements_students aas
													INNER JOIN aas_achievements_master aam ON aam.achievementMasterID = aas.achievementID 
													INNER JOIN asa_students ass ON ass.studentID = aas.studentID 
													INNER JOIN asa_student_details asd ON asd.studentID = ass.studentID 
													INNER JOIN asa_class_sections acs ON acs.classSectionID = ass.classSectionID
													INNER JOIN asa_classes acl ON acl.classID = acs.classID 
													INNER JOIN asa_section_master ascm ON ascm.sectionMasterID = acs.sectionMasterID 
													INNER JOIN users u ON aas.createUserID = u.userID 
													'. $QueryString .';');
				$RSTotal->Execute();
				
				$TotalRecords = $RSTotal->FetchRow()->totalRecords;
				return;
			}
			
			$RSSearch = $DBConnObject->Prepare('SELECT aas.*, aam.achievement, asd.firstName, asd.lastName, acl.className, ascm.sectionName, u.userName AS createUserName 
												FROM aas_achievements_students aas
												INNER JOIN aas_achievements_master aam ON aam.achievementMasterID = aas.achievementID 
												INNER JOIN asa_students ass ON ass.studentID = aas.studentID 
												INNER JOIN asa_student_details asd ON asd.studentID = ass.studentID 
												INNER JOIN asa_class_sections acs ON acs.classSectionID = ass.classSectionID
												INNER JOIN asa_classes acl ON acl.classID = acs.classID 
												INNER JOIN asa_section_master ascm ON ascm.sectionMasterID = acs.sectionMasterID 
												INNER JOIN users u ON aas.createUserID = u.userID 
												'. $QueryString .' 
												ORDER BY aam.achievement LIMIT ' . (int) $Start . ', ' . (int) $Limit . ';');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllAchievementsStudents; 
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$AllAchievementsStudents[$SearchRow->achievementsStudentID]['Achievement'] = $SearchRow->achievement;
				$AllAchievementsStudents[$SearchRow->achievementsStudentID]['StudentName'] = $SearchRow->firstName .' '. $SearchRow->lastName;

				$AllAchievementsStudents[$SearchRow->achievementsStudentID]['ClassName'] = $SearchRow->className;
				$AllAchievementsStudents[$SearchRow->achievementsStudentID]['SectionName'] = $SearchRow->sectionName;
				
				$AllAchievementsStudents[$SearchRow->achievementsStudentID]['IsActive'] = $SearchRow->isActive;

				$AllAchievementsStudents[$SearchRow->achievementsStudentID]['CreateUserID'] = $SearchRow->createUserID;
				$AllAchievementsStudents[$SearchRow->achievementsStudentID]['CreateUserName'] = $SearchRow->createUserName;
				$AllAchievementsStudents[$SearchRow->achievementsStudentID]['CreateDate'] = $SearchRow->createDate;
			}
			
			return $AllAchievementsStudents;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at AchievementsStudent::SearchAchievementsStudents(). Stack Trace: ' . $e->getTraceAsString());
			return $AllAchievementsStudents;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at AchievementsStudent::SearchAchievementsStudents(). Stack Trace: ' . $e->getTraceAsString());
			return $AllAchievementsStudents;
		}
	}
	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//
	private function SaveDetails()
	{
		if ($this->AchievementsStudentID == 0)
		{
		    foreach ($this->RemoveStudentIDList as $AchievementsStudentID => $StudentID) 
			{
				$RSDelete = $this->DBObject->Prepare('DELETE FROM aas_achievements_students WHERE achievementsStudentID = :|1 LIMIT 1;');
			
				$RSDelete->Execute($AchievementsStudentID);
			}
			
			foreach ($this->StudentIDList as $key => $StudentID) 
			{
				$RSSave = $this->DBObject->Prepare('INSERT INTO aas_achievements_students (achievementID, studentID, isActive, createUserID, createDate)
															VALUES (:|1, :|2, :|3, :|4, NOW());');
			
				$RSSave->Execute($this->AchievementID, $StudentID, $this->IsActive, $this->CreateUserID);
			
			}
		}
		
		return true;
	}
	
	private function RemoveAchievementsStudent()
    {
        if(!isset($this->AchievementsStudentID)) 
        {
            throw new ApplicationARException('', APP_AR_ERROR_DELETE_WITHOUT_ID);
        }
        
        $RSDeleteAchievementsStudent = $this->DBObject->Prepare('DELETE FROM aas_achievements_students WHERE achievementsStudentID = :|1 LIMIT 1;');
        $RSDeleteAchievementsStudent->Execute($this->AchievementsStudentID);  

        return true;              
    }

	private function GetAchievementsStudentByID()
	{
		$RSAchievementsStudent = $this->DBObject->Prepare('SELECT * FROM aas_achievements_students WHERE achievementsStudentID = :|1 LIMIT 1;');
		$RSAchievementsStudent->Execute($this->AchievementsStudentID);
		
		$AchievementsStudentRow = $RSAchievementsStudent->FetchRow();
		
		$this->SetAttributesFromDB($AchievementsStudentRow);				
	}
	
	private function SetAttributesFromDB($AchievementsStudentRow)
	{
		$this->AchievementsStudentID = $AchievementsStudentRow->achievementsStudentID;
		$this->AchievementID = $AchievementsStudentRow->achievementID;
		$this->StudentID = $AchievementsStudentRow->studentID;

		$this->IsActive = $AchievementsStudentRow->isActive;
		$this->CreateUserID = $AchievementsStudentRow->createUserID;
		$this->CreateDate = $AchievementsStudentRow->createDate;
	}	
}
?>
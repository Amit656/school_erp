<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.db_connect.php');
error_reporting(E_ALL);

class StudentDiary
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	
	private $StudentDiaryID;
	private $ClassSectionID;
	
	private $Heading;
	private $Details;

	private $IsActive;

	private $CreateUserID;
	private $CreateDate;
	
	// PUBLIC METHODS START HERE	//
	public function __construct($StudentDiaryID = 0)
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;
		
		if($StudentDiaryID != 0)
		{
			$this->StudentDiaryID = $StudentDiaryID;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetStudentDiaryByID();
		}
		else
		{
			//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
			$this->StudentDiaryID = 0;
			$this->ClassSectionID = 0;
			
			$this->Heading = '';
			$this->Details = '';

			$this->IsActive = 0;

			$this->CreateUserID = 0;
			$this->CreateDate = '0000-00-00 00:00:00';

		}
	}
	
	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetStudentDiaryID()
	{
		return $this->StudentDiaryID;
	}
	
	public function GetClassSectionID()
	{
		return $this->ClassSectionID;
	}
	public function SetClassSectionID($ClassSectionID)
	{
		$this->ClassSectionID = $ClassSectionID;
	}

	public function GetHeading()
	{
		return $this->Heading;
	}
	public function SetHeading($Heading)
	{
		$this->Heading = $Heading;
	}
	
	public function GetDetails()
	{
		return $this->Details;
	}
	public function SetDetails($Details)
	{
		$this->Details = $Details;
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
            $this->RemoveStudentDiary();
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
	
	static function SearchStudentDiaries(&$TotalRecords = 0, $GetTotalsOnly = false, $Filters = array(), $Start = 0, $Limit = 100)
	{
		$AllStudentDiaries = array();
		
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
					$Conditions[] = 'asd.classSectionID = '.$DBConnObject->RealEscapeVariable($Filters['ClassSectionID']);
				}

				if (!empty($Filters['IntervalDays']))
				{
					$Conditions[] = 'asd.createDate >= CURRENT_DATE - INTERVAL '.$DBConnObject->RealEscapeVariable($Filters['IntervalDays']) .' DAY';
				}

				if (!empty($Filters['ActiveStatus']))
				{
					if ($Filters['ActiveStatus'] == 1) //active records
					{
						$Conditions[] = 'asd.isActive = 1';
					}
					else if ($Filters['ActiveStatus'] == 2) //non active records
					{
						$Conditions[] = 'asd.isActive = 0';
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
													FROM aas_student_diaries asd
													INNER JOIN asa_class_sections acs ON acs.classSectionID = asd.classSectionID
													INNER JOIN asa_classes acl ON acl.classID = acs.classID 
													INNER JOIN asa_section_master ascm ON ascm.sectionMasterID = acs.sectionMasterID 
													INNER JOIN users u ON asd.createUserID = u.userID 
													'. $QueryString .';');
				$RSTotal->Execute();
				
				$TotalRecords = $RSTotal->FetchRow()->totalRecords;
				return;
			}
			
			$RSSearch = $DBConnObject->Prepare('SELECT asd.*, acl.className, ascm.sectionName, u.userName AS createUserName 
												FROM aas_student_diaries asd
												INNER JOIN asa_class_sections acs ON acs.classSectionID = asd.classSectionID
												INNER JOIN asa_classes acl ON acl.classID = acs.classID 												
												INNER JOIN asa_section_master ascm ON ascm.sectionMasterID = acs.sectionMasterID 
												INNER JOIN users u ON asd.createUserID = u.userID
												'. $QueryString .' 
												ORDER BY asd.createDate DESC LIMIT ' . (int) $Start . ', ' . (int) $Limit . ';');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllStudentDiaries; 
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$AllStudentDiaries[$SearchRow->studentDiaryID]['ClassName'] = $SearchRow->className;
				$AllStudentDiaries[$SearchRow->studentDiaryID]['SectionName'] = $SearchRow->sectionName;
				
				$AllStudentDiaries[$SearchRow->studentDiaryID]['Heading'] = $SearchRow->heading;
				$AllStudentDiaries[$SearchRow->studentDiaryID]['Details'] = $SearchRow->details;
	
				$AllStudentDiaries[$SearchRow->studentDiaryID]['IsActive'] = $SearchRow->isActive;

				$AllStudentDiaries[$SearchRow->studentDiaryID]['CreateUserID'] = $SearchRow->createUserID;
				$AllStudentDiaries[$SearchRow->studentDiaryID]['CreateUserName'] = $SearchRow->createUserName;
				$AllStudentDiaries[$SearchRow->studentDiaryID]['CreateDate'] = $SearchRow->createDate;
			}
			
			return $AllStudentDiaries;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at StudentDiary::SearchStudentDiaries(). Stack Trace: ' . $e->getTraceAsString());
			return $AllStudentDiaries;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at StudentDiary::SearchStudentDiaries(). Stack Trace: ' . $e->getTraceAsString());
			return $AllStudentDiaries;
		}
	}
	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//
	private function SaveDetails()
	{
		if ($this->StudentDiaryID == 0)
		{
			$RSSave = $this->DBObject->Prepare('INSERT INTO aas_student_diaries (classSectionID, heading, details, isActive, createUserID, createDate)
														VALUES (:|1, :|2, :|3, :|4, :|5, NOW());');
		
			$RSSave->Execute($this->ClassSectionID, $this->Heading, $this->Details, $this->IsActive, $this->CreateUserID);
			
			$this->StudentDiaryID = $RSSave->LastID;
		}
		else
		{

			$RSUpdate = $this->DBObject->Prepare('UPDATE aas_student_diaries
													SET	classSectionID = :|1,
														heading = :|2,
														details = :|3,
														isActive = :|4
													WHERE studentDiaryID = :|5;');
													
			$RSUpdate->Execute($this->ClassSectionID, $this->Heading, $this->Details, $this->IsActive, $this->StudentDiaryID);
		}
		
		return true;
	}

	private function RemoveStudentDiary()
    {
        if(!isset($this->StudentDiaryID)) 
        {
            throw new ApplicationARException('', APP_AR_ERROR_DELETE_WITHOUT_ID);
        }
        
        $RSDeleteStudentDiary = $this->DBObject->Prepare('DELETE FROM aas_student_diaries WHERE studentDiaryID = :|1 LIMIT 1;');
        $RSDeleteStudentDiary->Execute($this->StudentDiaryID);  

        return true;              
    }
	
	private function GetStudentDiaryByID()
	{
		$RSStudentDiary = $this->DBObject->Prepare('SELECT * FROM aas_student_diaries WHERE studentDiaryID = :|1 LIMIT 1;');
		$RSStudentDiary->Execute($this->StudentDiaryID);
		
		$StudentDiaryRow = $RSStudentDiary->FetchRow();
		
		$this->SetAttributesFromDB($StudentDiaryRow);				
	}
	
	private function SetAttributesFromDB($StudentDiaryRow)
	{
		$this->StudentDiaryID = $StudentDiaryRow->studentDiaryID;
		$this->ClassSectionID = $StudentDiaryRow->classSectionID;
		
		$this->Heading = $StudentDiaryRow->heading;
		$this->Details = $StudentDiaryRow->details;

		$this->IsActive = $StudentDiaryRow->isActive;

		$this->CreateUserID = $StudentDiaryRow->createUserID;
		$this->CreateDate = $StudentDiaryRow->createDate;
	}	
}
?>
<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.db_connect.php');
error_reporting(E_ALL);

class BranchStaff
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	
	private $BranchStaffID;

	private $FirstName;
	private $LastName;
	private $StaffPhoto;
	private $Gender;

	private $Address1;
	private $Address2;

	private $CityID;
	private $DistrictID;
	private $StateID;
	private $CountryID;
	private $PinCode;

	private $PhoneNumber;
	private $MobileNumber1;
	private $MobileNumber2;

	private $Email;
	private $AadharNumber;
	private $DOB;

	private $StaffCategory;

	private $HighestQualification;
	private $SpecialitySubjectID;
	private $JoiningDate;

	private $UserName;
	
	private $IsActive;
	private $CreateUserID;
	private $CreateDate;

	private $BranchStaffSubjectList = array();
	private $AssignedClasses = array();
	private $AssignedClassTeacherToClass = 0;
	
	// PUBLIC METHODS START HERE	//
	public function __construct($BranchStaffID = 0, $UserName = '')
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;
		
		if($BranchStaffID != 0)
		{
			$this->BranchStaffID = $BranchStaffID;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetBranchStaffByID();
		}
		else if($UserName != '')
		{
			$this->UserName = $UserName;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetBranchStaffByUserName();
		}
		else
		{
			//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
			$this->BranchStaffID = 0;
			$this->FirstName = '';
			$this->LastName = '';
			$this->StaffPhoto = '';
			$this->Gender =  'Female';

			$this->Address1 = '';
			$this->Address2 = '';

			$this->CityID = 0;
			$this->DistrictID = 0;
			$this->StateID = 0;
			$this->CountryID = 0;
			$this->PinCode = 0;

			$this->PhoneNumber = 0;
			$this->MobileNumber1 = 0;
			$this->MobileNumber2 = 0;

			$this->Email = '';
			$this->AadharNumber = 0;
			$this->DOB = '0000-00-00';

			$this->StaffCategory = 'Teaching';

			$this->HighestQualification = '';
			$this->SpecialitySubjectID = 0;
			$this->JoiningDate = '0000-00-00';

			$this->UserName = '';
			
			$this->IsActive = 0;
			$this->CreateUserID = 0;
			$this->CreateDate = '0000-00-00 00:00:00';

			$this->BranchStaffSubjectList = array();
			$this->AssignedClasses = array();
			$this->AssignedClassTeacherToClass = 0;
		}
	}
	
	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetBranchStaffID()
	{
		return $this->BranchStaffID;
	}
	
	public function GetFirstName()
	{
		return $this->FirstName;
	}
	public function SetFirstName($FirstName)
	{
		$this->FirstName = $FirstName;
	}

	public function GetLastName()
	{
		return $this->LastName;
	}
	public function SetLastName($LastName)
	{
		$this->LastName = $LastName;
	}
	
	public function GetStaffPhoto()
	{
		return $this->StaffPhoto;
	}
	public function SetStaffPhoto($StaffPhoto)
	{
		$this->StaffPhoto = $StaffPhoto;
	}

	public function GetGender()
	{
		return $this->Gender;
	}
	public function SetGender($Gender)
	{
		$this->Gender = $Gender;
	}

	public function GetAddress1()
	{
		return $this->Address1;
	}
	public function SetAddress1($Address1)
	{
		$this->Address1 = $Address1;
	}

	public function GetAddress2()
	{
		return $this->Address2;
	}
	public function SetAddress2($Address2)
	{
		$this->Address2 = $Address2;
	}

	public function GetCityID()
	{
		return $this->CityID;
	}
	public function SetCityID($CityID)
	{
		$this->CityID = $CityID;
	}

	public function GetDistrictID()
	{
		return $this->DistrictID;
	}
	public function SetDistrictID($DistrictID)
	{
		$this->DistrictID = $DistrictID;
	}

	public function GetStateID()
	{
		return $this->StateID;
	}
	public function SetStateID($StateID)
	{
		$this->StateID = $StateID;
	}

	public function GetCountryID()
	{
		return $this->CountryID;
	}
	public function SetCountryID($CountryID)
	{
		$this->CountryID = $CountryID;
	}

	public function GetPinCode()
	{
		return $this->PinCode;
	}
	public function SetPinCode($PinCode)
	{
		$this->PinCode = $PinCode;
	}

	public function GetPhoneNumber()
	{
		return $this->PhoneNumber;
	}
	public function SetPhoneNumber($PhoneNumber)
	{
		$this->PhoneNumber = $PhoneNumber;
	}

	public function GetMobileNumber1()
	{
		return $this->MobileNumber1;
	}
	public function SetMobileNumber1($MobileNumber1)
	{
		$this->MobileNumber1 = $MobileNumber1;
	}

	public function GetMobileNumber2()
	{
		return $this->MobileNumber2;
	}
	public function SetMobileNumber2($MobileNumber2)
	{
		$this->MobileNumber2 = $MobileNumber2;
	}

	public function GetEmail()
	{
		return $this->Email;
	}
	public function SetEmail($Email)
	{
		$this->Email = $Email;
	}

	public function GetAadharNumber()
	{
		return $this->AadharNumber;
	}
	public function SetAadharNumber($AadharNumber)
	{
		$this->AadharNumber = $AadharNumber;
	}

	public function GetDOB()
	{
		return $this->DOB;
	}
	public function SetDOB($DOB)
	{
		$this->DOB = $DOB;
	}

	public function GetStaffCategory()
	{
		return $this->StaffCategory;
	}
	public function SetStaffCategory($StaffCategory)
	{
		$this->StaffCategory = $StaffCategory;
	}

	public function GetHighestQualification()
	{
		return $this->HighestQualification;
	}
	public function SetHighestQualification($HighestQualification)
	{
		$this->HighestQualification = $HighestQualification;
	}

	public function GetSpecialitySubjectID()
	{
		return $this->SpecialitySubjectID;
	}
	public function SetSpecialitySubjectID($SpecialitySubjectID)
	{
		$this->SpecialitySubjectID = $SpecialitySubjectID;
	}

	public function GetJoiningDate()
	{
		return $this->JoiningDate;
	}
	public function SetJoiningDate($JoiningDate)
	{
		$this->JoiningDate = $JoiningDate;
	}

	public function GetUserName()
	{
		return $this->UserName;
	}
	public function SetUserName($UserName)
	{
		$this->UserName = $UserName;
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

	public function GetBranchStaffSubjectList()
	{
		return $this->BranchStaffSubjectList;
	}
	public function SetBranchStaffSubjectList($BranchStaffSubjectList)
	{
		$this->BranchStaffSubjectList = $BranchStaffSubjectList;
	}
	
	public function GetAssignedClasses()
	{
		return $this->AssignedClasses;
	}
	public function SetAssignedClasses($AssignedClasses)
	{
		$this->AssignedClasses = $AssignedClasses;
	}
	
	public function GetAssignedClassTeacherToClass()
	{
		return $this->AssignedClassTeacherToClass;
	}
	public function SetAssignedClassTeacherToClass($AssignedClassTeacherToClass)
	{
		$this->AssignedClassTeacherToClass = $AssignedClassTeacherToClass;
	}

	public function GetLastErrorCode()
	{
		return $this->LastErrorCode;
	}
	
	public function InActiveBranchStaff()
	{
		try
        {
	        $RSInActive = $this->DBObject->Prepare('UPDATE asa_branch_staff SET isActive = !(isActive) WHERE branchStaffID = :|1 LIMIT 1;');
			$RSInActive->Execute($this->BranchStaffID);

            return true;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at BranchStaff::InActiveBranchStaff(). Stack Trace: ' . $e->getTraceAsString());
            return false;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception at BranchStaff::InActiveBranchStaff(). Stack Trace: ' . $e->getTraceAsString());
            return false;
        }
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
            $this->RemoveBranchStaff();
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
            $RSClassClassteachersCount = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM asa_class_classteachers WHERE branchStaffID = :|1;');
            $RSClassClassteachersCount->Execute($this->BranchStaffID);
           
            if ($RSClassClassteachersCount->FetchRow()->totalRecords > 0) 
            {
                return true;
            }
			
			$RSTeacherClassesCount = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM asa_teacher_classes WHERE branchStaffID = :|1;');
            $RSTeacherClassesCount->Execute($this->BranchStaffID);
           
            if ($RSTeacherClassesCount->FetchRow()->totalRecords > 0) 
            {
                return true;
            }

            return false;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException: BranchStaff::CheckDependencies . Stack Trace: ' . $e->getTraceAsString());
            return false;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception: BranchStaff::CheckDependencies . Stack Trace: ' . $e->getTraceAsString());
            return false;
        }       
    }

	public function AadharExist()
	{
		try
		{	
			$Condition = '';
			if ($this->BranchStaffID > 0)
			{
				$Condition = ' AND branchStaffID != ' . $this->BranchStaffID;
			}

			$RSTotal = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM asa_branch_staff WHERE aadharNumber = :|1'. $Condition .';');
			$RSTotal->Execute($this->AadharNumber);
			
			if ($RSTotal->FetchRow()->totalRecords > 0)
			{
				return true;
			}
			
			return false;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at BranchStaff::AadharExist(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at BranchStaff::AadharExist(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
	}

	public function UserNameExist()
	{
		try
		{
			$RSTotal = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM asa_branch_staff WHERE userName = :|1;');
			$RSTotal->Execute($this->UserName);
			
			if ($RSTotal->FetchRow()->totalRecords > 0)
			{
				return true;
			}
			
			return false;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at BranchStaff::UserNameExist(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at BranchStaff::UserNameExist(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
	}

    public function CheckBranchStaffClassesDependencies($BranchStaffClassesToBeRemoved)
    {
    	$BranchStaffClassesToBeRemoved = '';

        try
        {
			$BranchStaffClassesToBeRemoved = implode(',', array_keys($BranchStaffClassesToBeRemoved));

            $RSCount = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM asa_class_time_table_details WHERE teacherClassID IN (' . $BranchStaffClassesToBeRemoved . ');');
            $RSCount->Execute();
         
            if ($RSCount->FetchRow()->totalRecords > 0) 
            {
                return true;
            }
            
            return false;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException: BranchStaff::CheckBranchStaffClassesDependencies . Stack Trace: ' . $e->getTraceAsString());
            return false;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception: BranchStaff::CheckBranchStaffClassesDependencies . Stack Trace: ' . $e->getTraceAsString());
            return false;
        }       
    }

    public function SaveBranchStaffSubjects()
    {	
    	try
        {	
        	$this->DBObject->BeginTransaction();

            $RSCurrentBranchStaffSubjectsDelete = $this->DBObject->Prepare('DELETE FROM asa_teacher_subjects WHERE branchStaffID = :|1;');
			$RSCurrentBranchStaffSubjectsDelete->Execute($this->BranchStaffID);

			foreach ($this->BranchStaffSubjectList as $SubjectID => $Value)
			{
				$SaveBranchStaffSubject = $this->DBObject->Prepare('INSERT INTO asa_teacher_subjects (branchStaffID, subjectID, createUserID, createDate)
																			VALUES (:|1, :|2, :|3, NOW());');
				$SaveBranchStaffSubject->Execute($this->BranchStaffID, $SubjectID, $this->CreateUserID);
			}

			$this->DBObject->CommitTransaction();
            return true;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException: BranchStaff::SaveBranchStaffSubjects. Stack Trace: ' . $e->getTraceAsString());
            $this->DBObject->RollBackTransaction();
            return false;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception: BranchStaff::SaveBranchStaffSubjects . Stack Trace: ' . $e->getTraceAsString());
            $this->DBObject->RollBackTransaction();
            return false;
        }  	
    }
    
    public function SaveAssignedClassesToTeacher($BranchStaffClassesToBeRemoved)
    {
    	$ClassIDs = '';

    	try
        {	
        	if (count($BranchStaffClassesToBeRemoved) > 0) 
        	{
        		$ClassIDs = implode(',', array_keys($BranchStaffClassesToBeRemoved));

	            $RSDelete = $this->DBObject->Prepare('DELETE FROM asa_teacher_classes WHERE classID IN (' . $ClassIDs . ') AND branchStaffID = :|1;');
	            $RSDelete->Execute($this->BranchStaffID);
        	}

        	foreach ($this->AssignedClasses as $ClassID => $Value)
			{
				$RSSearch = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM asa_teacher_classes WHERE branchStaffID = :|1 AND classID = :|2;');
            	$RSSearch->Execute($this->BranchStaffID, $ClassID);

            	if ($RSSearch->FetchRow()->totalRecords > 0) 
            	{
            		continue;
            	}

				$SaveBranchStaffSubject = $this->DBObject->Prepare('INSERT INTO asa_teacher_classes (branchStaffID, classID, createUserID, createDate)
																			VALUES (:|1, :|2, :|3, NOW());');
				$SaveBranchStaffSubject->Execute($this->BranchStaffID, $ClassID, $this->CreateUserID);
			}

            return true;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException: BranchStaff::SaveAssignedClassesToTeacher. Stack Trace: ' . $e->getTraceAsString());
            return false;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception: BranchStaff::SaveAssignedClassesToTeacher . Stack Trace: ' . $e->getTraceAsString());
            return false;
        }
    }

    public function FillAssignedClasses()
    {
        try
        {
	        $RSSearch = $this->DBObject->Prepare('SELECT atc.classID, ac.className FROM asa_teacher_classes atc
														INNER JOIN asa_classes ac ON  ac.classID = atc.classID
														WHERE atc.branchStaffID = :|1;');
            $RSSearch->Execute($this->BranchStaffID);

            if ($RSSearch->Result->num_rows <= 0)
            {
                return $this->AssignedClasses;
            }

            while($SearchRow = $RSSearch->FetchRow())
            {
                $this->AssignedClasses[$SearchRow->classID] = $SearchRow->className;
            }

            return $this->AssignedClasses;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at BranchStaff::FillAssignedClasses(). Stack Trace: ' . $e->getTraceAsString());
            return $this->AssignedClasses;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception at BranchStaff::FillAssignedClasses(). Stack Trace: ' . $e->getTraceAsString());
            return $AssignedTecherClasses;
        }
    }

    public function FillBranchStaffSubjects()
    {
        try
        {
	        $RSTeacherSubjectsSearch = $this->DBObject->Prepare('SELECT teacherSubjectID, subjectID FROM asa_teacher_subjects WHERE branchStaffID = :|1;');
			$RSTeacherSubjectsSearch->Execute($this->BranchStaffID);

			if($RSTeacherSubjectsSearch->Result->num_rows <= 0)
			{
				return true;	
			}

			while($SearchRow = $RSTeacherSubjectsSearch->FetchRow())
	        {
	           $this->BranchStaffSubjectList[$SearchRow->teacherSubjectID] = $SearchRow->subjectID;
	        }

            return true;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at BranchStaff::FillBranchStaffSubjects(). Stack Trace: ' . $e->getTraceAsString());
            return false;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception at BranchStaff::FillBranchStaffSubjects(). Stack Trace: ' . $e->getTraceAsString());
            return false;
        }
    }

    public function GetTeacherApplicableClasses($GetOnlyName = true)
    {
    	$TeacherApplicableClasses = array();

        try
        {
	        $RSSearch = $this->DBObject->Prepare('SELECT DISTINCT(acs.classID), ac.className, ac.classSymbol FROM asa_class_subjects acs	
														INNER JOIN asa_classes ac ON ac.classID = acs.classID
														WHERE subjectID IN (SELECT subjectID FROM asa_teacher_subjects WHERE branchStaffID = :|1);');
            $RSSearch->Execute($this->BranchStaffID);

            if ($RSSearch->Result->num_rows <= 0)
            {
                return $TeacherApplicableClasses;
            }

            while($SearchRow = $RSSearch->FetchRow())
            {	

            	if ($GetOnlyName) 
            	{
            		$TeacherApplicableClasses[$SearchRow->classID] = $SearchRow->className;
            		continue;
            	}

            	$TeacherApplicableClasses[$SearchRow->classID]['ClassName'] = $SearchRow->className;
            	$TeacherApplicableClasses[$SearchRow->classID]['ClassSymbol'] = $SearchRow->classSymbol;
            }

            return $TeacherApplicableClasses;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at BranchStaff::GetTeacherApplicableClasses(). Stack Trace: ' . $e->getTraceAsString());
            return $TeacherApplicableClasses;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception at BranchStaff::GetTeacherApplicableClasses(). Stack Trace: ' . $e->getTraceAsString());
            return $TeacherApplicableClasses;
        }
    }

    public function GetCurrentDayTimeTable($DayID)
	{
		$AllPeriodsDetails = array();

		try
        {
	        $RSSearch = $this->DBObject->Prepare('SELECT acttd.classTimeTableDetailID, asscdt.dayID, asm.subject, actt.daywiseTimingsID, astpm.timingPart, asscpt.periodStartTime, asscpt.periodEndTime, 
	        										ac.classID, ac.className, ac.classSymbol, asecm.sectionName, acs.classSubjectID 
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
													WHERE atc.branchStaffID = :|1 AND asscdt.dayID = :|2;');

            $RSSearch->Execute($this->BranchStaffID, $DayID);
			
			if ($RSSearch->Result->num_rows > 0)
            {
				while ($SearchRow = $RSSearch->FetchRow())
				{
					$AllPeriodsDetails[$SearchRow->classTimeTableDetailID]['ClassID'] = $SearchRow->classID;
					$AllPeriodsDetails[$SearchRow->classTimeTableDetailID]['ClassName'] = $SearchRow->className;
					$AllPeriodsDetails[$SearchRow->classTimeTableDetailID]['ClassSymbol'] = $SearchRow->classSymbol;
					$AllPeriodsDetails[$SearchRow->classTimeTableDetailID]['SectionName'] = $SearchRow->sectionName;
					
					$AllPeriodsDetails[$SearchRow->classTimeTableDetailID]['ClassSubjectID'] = $SearchRow->classSubjectID;

					$AllPeriodsDetails[$SearchRow->classTimeTableDetailID]['Subject'] = $SearchRow->subject;
					$AllPeriodsDetails[$SearchRow->classTimeTableDetailID]['TimingPart'] = $SearchRow->timingPart;
					$AllPeriodsDetails[$SearchRow->classTimeTableDetailID]['PeriodStartTime'] = $SearchRow->periodStartTime;
					$AllPeriodsDetails[$SearchRow->classTimeTableDetailID]['PeriodEndTime'] = $SearchRow->periodEndTime;
					$AllPeriodsDetails[$SearchRow->classTimeTableDetailID]['DayID'] = $SearchRow->dayID;
				}	
            }

            return $AllPeriodsDetails;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at BranchStaff::GetCurrentDayTimeTable(). Stack Trace: ' . $e->getTraceAsString());

            return $AllPeriodsDetails;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception at BranchStaff::GetCurrentDayTimeTable(). Stack Trace: ' . $e->getTraceAsString());

            return $AllPeriodsDetails;
        }
	}
	
	public function GetClassSectionID(&$ClassSectionID)
	{
	    # Get Class Section Of The Teacher (On Which class the teacher is class teacher)
	    $ClassSectionID = 0;
	    
	    try
	    {
	        $RSGetClassSectionID = $this->DBObject->Prepare('SELECT classSectionID FROM asa_class_classteachers WHERE branchStaffID = :|1 LIMIT 1;');
			$RSGetClassSectionID->Execute($this->BranchStaffID);
			
			if ($RSGetClassSectionID->Result->num_rows > 0)
			{
			    $ClassSectionID = $RSGetClassSectionID->FetchRow()->classSectionID;
			    
			    return true;
			}
			
			return false;
	    }
	    catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at BranchStaff->GetClassSectionID(). Stack Trace: ' . $e->getTraceAsString());
            return false;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception at BranchStaff->GetClassSectionID(). Stack Trace: ' . $e->getTraceAsString());
            return false;
        }
	}

	// END OF PUBLIC METHODS	//
	
	// START OF STATIC METHODS	//
	static function GetAllBranchStaff($StaffCategory = '')
    {
        $AllBranchStaff = array();

        $DBConnObject = new DBConnect();

        $Query = '';

        if(!empty($StaffCategory))
        {
        	$Query = " AND abs.staffCategory =" . $DBConnObject->RealEscapeVariable($StaffCategory);
        }

        try
        {
	        $RSSearch = $DBConnObject->Prepare('SELECT abs.*, u.userName AS createUserName FROM asa_branch_staff abs 
													INNER JOIN users u ON abs.createUserID = u.userID
													WHERE abs.isActive = 1' . $Query . '
													ORDER BY abs.firstName;');
            $RSSearch->Execute();

            if ($RSSearch->Result->num_rows <= 0)
            {
                return $AllBranchStaff;
            }

            while($SearchRow = $RSSearch->FetchRow())
            {
                $AllBranchStaff[$SearchRow->branchStaffID]['FirstName'] = $SearchRow->firstName;
                $AllBranchStaff[$SearchRow->branchStaffID]['LastName'] = $SearchRow->lastName;

                $AllBranchStaff[$SearchRow->branchStaffID]['Gender'] = $SearchRow->gender;
		        $AllBranchStaff[$SearchRow->branchStaffID]['AadharNumber'] = $SearchRow->aadharNumber;

		        $AllBranchStaff[$SearchRow->branchStaffID]['DOB'] = $SearchRow->dob;
		        $AllBranchStaff[$SearchRow->branchStaffID]['StaffCategory'] = $SearchRow->staffCategory;
		        $AllBranchStaff[$SearchRow->branchStaffID]['JoiningDate'] = $SearchRow->joiningDate;
		        $AllBranchStaff[$SearchRow->branchStaffID]['UserName'] = $SearchRow->userName;

		        $AllBranchStaff[$SearchRow->branchStaffID]['IsActive'] = $SearchRow->isActive;

		        $AllBranchStaff[$SearchRow->branchStaffID]['CreateUserID'] = $SearchRow->createUserID;
		        $AllBranchStaff[$SearchRow->branchStaffID]['CreateUserName'] = $SearchRow->createUserName;
				$AllBranchStaff[$SearchRow->branchStaffID]['CreateDate'] = $SearchRow->createDate;
            }

            return $AllBranchStaff;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at BranchStaff::GetAllBranchStaff(). Stack Trace: ' . $e->getTraceAsString());
            return $AllBranchStaff;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception at BranchStaff::GetAllBranchStaff(). Stack Trace: ' . $e->getTraceAsString());
            return $AllBranchStaff;
        }
    }

    static function GetActiveBranchStaff($StaffCategory)
	{
		$AllActiveBranchStaff = array();

        if(empty($StaffCategory))
        {
			return $AllActiveBranchStaff;        	
        }
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare("SELECT branchStaffID, firstName, lastName, mobileNumber1, email, userName FROM asa_branch_staff WHERE 
														staffCategory = :|1 AND isActive = 1;");
			$RSSearch->Execute($StaffCategory);
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllActiveBranchStaff;
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$AllActiveBranchStaff[$SearchRow->branchStaffID]['FirstName'] = $SearchRow->firstName;
            	$AllActiveBranchStaff[$SearchRow->branchStaffID]['LastName'] = $SearchRow->lastName;

            	$AllActiveBranchStaff[$SearchRow->branchStaffID]['MobileNumber1'] = $SearchRow->mobileNumber1;
            	$AllActiveBranchStaff[$SearchRow->branchStaffID]['Email'] = $SearchRow->email;
				$AllActiveBranchStaff[$SearchRow->branchStaffID]['UserName'] = $SearchRow->userName;
			}
			
			return $AllActiveBranchStaff;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at BranchStaff::GetActiveBranchStaff(). Stack Trace: ' . $e->getTraceAsString());
			return $AllActiveBranchStaff;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at BranchStaff::GetActiveBranchStaff(). Stack Trace: ' . $e->getTraceAsString());
			return $AllActiveBranchStaff;
		}		
	}

    static function SearchBranchStaff(&$TotalRecords = 0, $GetTotalsOnly = false, $Filters = array(), $Start = 0, $Limit = 100)
    {
    	$AllBranchStaff = array();

    	try
		{
			$DBConnObject = new DBConnect();
			
			$Conditions = array();
			
			if (count($Filters) > 0)
			{
				if (!empty($Filters['Genders']))
				{
					$Conditions[] = 'abs.gender IN (' . $DBConnObject->RealEscapeVariable($Filters['Genders']) . ')';
				}
				
				if (!empty($Filters['StaffCategories']))
				{
					$Conditions[] = 'abs.staffCategory IN (' . $DBConnObject->RealEscapeVariable($Filters['StaffCategories']) . ')';
				}
				
				if (!empty($Filters['TeacherName']))
				{
					$Conditions[] = 'abs.firstName LIKE ' . $DBConnObject->RealEscapeVariable("%" . $Filters['TeacherName'] . "%") . ' OR abs.lastName LIKE ' . 
										$DBConnObject->RealEscapeVariable("%" . $Filters['TeacherName'] . "%");
				}

				if($Filters['ActiveStatus'] == 1)
				{
					$Conditions[] = 'abs.isActive = 1';
				}
				else
				{
					$Conditions[] = 'abs.isActive = 0';
				}
			}

			$QueryString = '';

			if (count($Conditions) > 0)
			{
				$QueryString = implode(' AND ',$Conditions);
				
				$QueryString = ' WHERE ' . $QueryString;
			}
			
			if ($GetTotalsOnly)
			{
			 
				$RSTotal = $DBConnObject->Prepare('SELECT COUNT(*) AS totalRecords 
													FROM asa_branch_staff abs 
													INNER JOIN users u ON abs.createUserID = u.userID' . $QueryString . ';');
				$RSTotal->Execute();
				
				$TotalRecords = $RSTotal->FetchRow()->totalRecords;
				return;
			}
			
			$RSAllBranchStaff = $DBConnObject->Prepare('SELECT abs.*, u.userName AS createUserName FROM asa_branch_staff abs 
														INNER JOIN users u ON abs.createUserID = u.userID' . $QueryString . ' 
														ORDER BY abs.branchStaffID 
														LIMIT ' . (int) $Start . ', ' . (int) $Limit . ';');
			$RSAllBranchStaff->Execute();

			while($RSAllBranchStaffRow = $RSAllBranchStaff->FetchRow())
			{
				$AllBranchStaff[$RSAllBranchStaffRow->branchStaffID]['FirstName'] = $RSAllBranchStaffRow->firstName;
				$AllBranchStaff[$RSAllBranchStaffRow->branchStaffID]['LastName'] = $RSAllBranchStaffRow->lastName;
				
				$AllBranchStaff[$RSAllBranchStaffRow->branchStaffID]['StaffCategory'] = $RSAllBranchStaffRow->staffCategory;
				
				$AllBranchStaff[$RSAllBranchStaffRow->branchStaffID]['JoiningDate'] = $RSAllBranchStaffRow->joiningDate;
				$AllBranchStaff[$RSAllBranchStaffRow->branchStaffID]['DOB'] = $RSAllBranchStaffRow->dob;
				$AllBranchStaff[$RSAllBranchStaffRow->branchStaffID]['MobileNumber1'] = $RSAllBranchStaffRow->mobileNumber1;
				$AllBranchStaff[$RSAllBranchStaffRow->branchStaffID]['MobileNumber2'] = $RSAllBranchStaffRow->mobileNumber2;
				$AllBranchStaff[$RSAllBranchStaffRow->branchStaffID]['UserName'] = $RSAllBranchStaffRow->userName;

				$AllBranchStaff[$RSAllBranchStaffRow->branchStaffID]['IsActive'] = $RSAllBranchStaffRow->isActive;

				$AllBranchStaff[$RSAllBranchStaffRow->branchStaffID]['CreateUserName'] = $RSAllBranchStaffRow->createUserName;
				$AllBranchStaff[$RSAllBranchStaffRow->branchStaffID]['CreateUserID'] = $RSAllBranchStaffRow->createUserID;
				$AllBranchStaff[$RSAllBranchStaffRow->branchStaffID]['CreateDate'] = $RSAllBranchStaffRow->createDate;
			}
			
			return $AllBranchStaff;	
				
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at BranchStaff::SearchBranchStaff(). Stack Trace: ' . $e->getTraceAsString());
			return $AllBranchStaff;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at BranchStaff::SearchBranchStaff(). Stack Trace: ' . $e->getTraceAsString());
			return $AllBranchStaff;
		}	
    }
    				
    static function GetBranchStaffDetailsByBranchStaffID($BranchStaffID)
    {
    	$BranchStaffDetials = array();

    	try
		{
			$DBConnObject = new DBConnect();

			$RSBranchStaff = $DBConnObject->Prepare('SELECT branchStaffID, firstName, lastName, staffCategory, email, joiningDate, mobileNumber1 FROM asa_branch_staff WHERE branchStaffID = :|1 LIMIT 1;');
			$RSBranchStaff->Execute($BranchStaffID);

			$RSBranchStaffRow = $RSBranchStaff->FetchRow();

			$BranchStaffDetials[$RSBranchStaffRow->branchStaffID]['FirstName'] = $RSBranchStaffRow->firstName;
			$BranchStaffDetials[$RSBranchStaffRow->branchStaffID]['LastName'] = $RSBranchStaffRow->lastName;
			
			$BranchStaffDetials[$RSBranchStaffRow->branchStaffID]['StaffCategory'] = $RSBranchStaffRow->staffCategory;
			$BranchStaffDetials[$RSBranchStaffRow->branchStaffID]['Email'] = $RSBranchStaffRow->email;
			$BranchStaffDetials[$RSBranchStaffRow->branchStaffID]['JoiningDate'] = $RSBranchStaffRow->joiningDate;
			$BranchStaffDetials[$RSBranchStaffRow->branchStaffID]['MobileNumber1'] = $RSBranchStaffRow->mobileNumber1;

			return $BranchStaffDetials;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at BranchStaff::GetBranchStaffDetailsByBranchStaffID(). Stack Trace: ' . $e->getTraceAsString());
			return $BranchStaffDetials; 
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at BranchStaff::GetBranchStaffDetailsByBranchStaffID(). Stack Trace: ' . $e->getTraceAsString());
			return $BranchStaffDetials;
		}
    }

	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//
	private function SaveDetails()
	{
		if ($this->BranchStaffID == 0)
		{
			$RSSave = $this->DBObject->Prepare('INSERT INTO asa_branch_staff (firstName, lastName, staffPhoto, gender, address1, address2, cityID, districtID, 
													stateID, countryID, pinCode, phoneNumber, mobileNumber1, mobileNumber2, 
													email, aadharNumber, dob, staffCategory, highestQualification, 
													specialitySubjectID, joiningDate, userName, isActive, createUserID, createDate)
														VALUES (:|1, :|2, :|3, :|4, :|5, :|6, :|7, :|8, :|9, :|10, :|11, :|12, :|13, :|14, :|15, :|16, :|17, :|18, :|19, :|20, :|21, :|22, :|23, :|24, NOW());');
			$RSSave->Execute($this->FirstName, $this->LastName, $this->StaffPhoto, $this->Gender, $this->Address1, $this->Address2, $this->CityID, $this->DistrictID, 
								$this->StateID, $this->CountryID, $this->PinCode, $this->PhoneNumber, $this->MobileNumber1, $this->MobileNumber2, 
								$this->Email, $this->AadharNumber, $this->DOB, $this->StaffCategory, $this->HighestQualification, 
								$this->SpecialitySubjectID, $this->JoiningDate, $this->UserName, $this->IsActive, $this->CreateUserID);

			$this->BranchStaffID = $RSSave->LastID;
		}
		else
		{
			$RSUpdate = $this->DBObject->Prepare('UPDATE asa_branch_staff
													SET	firstName = :|1,
														lastName = :|2,
														staffPhoto = :|3,
														gender = :|4,
														address1 = :|5,
														address2 = :|6,
														cityID = :|7,
														districtID = :|8,
														stateID = :|9,
														countryID = :|10,
														pinCode = :|11,
														phoneNumber = :|12,
														mobileNumber1 = :|13,
														mobileNumber2 = :|14,
														email = :|15,
														aadharNumber = :|16,
														dob = :|17,
														staffCategory = :|18,
														highestQualification = :|19,
														specialitySubjectID = :|20,
														joiningDate = :|21,
														userName = :|22, 
														isActive = :|23
													WHERE branchStaffID = :|24;');

			$RSUpdate->Execute($this->FirstName, $this->LastName, $this->StaffPhoto, $this->Gender, $this->Address1, $this->Address2, $this->CityID, $this->DistrictID, $this->StateID, $this->CountryID, $this->PinCode, $this->PhoneNumber, $this->MobileNumber1, $this->MobileNumber2, $this->Email, $this->AadharNumber, $this->DOB, $this->StaffCategory, $this->HighestQualification, $this->SpecialitySubjectID, $this->JoiningDate, $this->UserName, $this->IsActive, $this->BranchStaffID);

			$RSSearchUser = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM users WHERE userName = :|1;');
			$RSSearchUser->Execute($this->UserName);

			if ($RSSearchUser->FetchRow()->totalRecords <= 0)
			{
				//	Default password for fauculty will be starting 4 characters of firstname + lastname + birth year
				//	For Ex. FirstName = Jai & LastName = Singh & DOB = 22/10/1995 
				//	Your password will be: JAIS1995
				$Password = strtoupper( substr($this->FirstName . $this->LastName, 0, 4) . date('Y', strtotime($this->DOB)) );

				$RSCreateStaffLogin = $this->DBObject->Prepare('INSERT INTO users (userName, password, roleID, isActive, createUserID, createDate) VALUES (:|1, :|2, :|3, :|4, :|5, NOW());');
				$RSCreateStaffLogin->Execute($this->UserName, SHA1($Password), ROLE_SITE_FACULTY, 1, $this->CreateUserID);
			}
			else
			{
				error_log('Criticle Error: Generated Username already exists in the records.');
			}
		}

		return true;
	}

	private function RemoveBranchStaff()
	{
		if(!isset($this->BranchStaffID)) 
        {
            throw new ApplicationARException('', APP_AR_ERROR_DELETE_WITHOUT_ID);
        }
        
        $RSDeleteBranchStaff = $this->DBObject->Prepare('DELETE FROM asa_branch_staff WHERE branchStaffID = :|1	LIMIT 1;');
        $RSDeleteBranchStaff->Execute($this->BranchStaffID);
	}
	
	private function GetBranchStaffByID()
	{
		$RSBranchStaff = $this->DBObject->Prepare('SELECT * FROM asa_branch_staff WHERE branchStaffID = :|1 LIMIT 1;');
		$RSBranchStaff->Execute($this->BranchStaffID);
		
		$BranchStaffRow = $RSBranchStaff->FetchRow();
		
		$this->SetAttributesFromDB($BranchStaffRow);				
	}
	
	private function GetBranchStaffByUserName()
	{
		$RSBranchStaff = $this->DBObject->Prepare('SELECT * FROM asa_branch_staff WHERE userName = :|1 LIMIT 1;');
		$RSBranchStaff->Execute($this->UserName);
		
		$BranchStaffRow = $RSBranchStaff->FetchRow();
		
		$this->SetAttributesFromDB($BranchStaffRow);				
	}
	
	private function SetAttributesFromDB($BranchStaffRow)
	{
		$this->BranchStaffID = $BranchStaffRow->branchStaffID;
		$this->FirstName = $BranchStaffRow->firstName;
		$this->LastName = $BranchStaffRow->lastName;
		$this->StaffPhoto = $BranchStaffRow->staffPhoto;
		$this->Gender =  $BranchStaffRow->gender;

		$this->Address1 = $BranchStaffRow->address1;
		$this->Address2 = $BranchStaffRow->address2;

		$this->CityID = $BranchStaffRow->cityID;
		$this->DistrictID = $BranchStaffRow->districtID;
		$this->StateID = $BranchStaffRow->stateID;
		$this->CountryID = $BranchStaffRow->countryID;
		$this->PinCode = $BranchStaffRow->pinCode;

		$this->PhoneNumber = $BranchStaffRow->phoneNumber;
		$this->MobileNumber1 = $BranchStaffRow->mobileNumber1;
		$this->MobileNumber2 = $BranchStaffRow->mobileNumber2;

		$this->Email = $BranchStaffRow->email;
		$this->AadharNumber = $BranchStaffRow->aadharNumber;
		$this->DOB = $BranchStaffRow->dob;

		$this->StaffCategory = $BranchStaffRow->staffCategory;

		$this->HighestQualification = $BranchStaffRow->highestQualification;
		$this->SpecialitySubjectID = $BranchStaffRow->specialitySubjectID;
		$this->JoiningDate = $BranchStaffRow->joiningDate;

		$this->UserName = $BranchStaffRow->userName;
		
		$this->IsActive = $BranchStaffRow->isActive;
		$this->CreateUserID = $BranchStaffRow->createUserID;
		$this->CreateDate = $BranchStaffRow->createDate;
	}	
}
?>
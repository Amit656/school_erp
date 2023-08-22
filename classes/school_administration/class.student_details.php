<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.db_connect.php');
error_reporting(E_ALL);

class StudentDetail extends Student
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	
	private $FirstName;
	private $LastName;
	private $StudentPhoto;

	private $DOB;
	private $Gender;
	private $BloodGroup;
	private $Religion;
	private $Category;

	private $IsEWS;
	private $HasDisability;
	private $IsSingleGirl;

	private $Address1;
	private $Address2;

	private $CityID;
	private $DistrictID;
	private $StateID;
	private $CountryID;
	private $PinCode;

	private $MobileNumber;
	private $Email;

	private $LastClass;
	private $LastSchool;
	private $LastSchoolBoard;

	private $TCReceived;
	private $TCDate;

	private $SubjectsProposed;
	private $MotherTongue;
	private $HomeTown;

	private $LastExamStatus;
	private $LastExamPercentage;

	private $DateFrom;
	
	// PUBLIC METHODS START HERE	//
	public function __construct($StudentID = 0)
	{
		parent::__construct($StudentID);

		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;
		
		if($StudentID != 0)
		{
			$this->StudentID = $StudentID;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetStudentDetailByID();
		}
		else
		{
			//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
			$this->StudentID = 0;
			$this->FirstName = '';
			$this->LastName = '';
			$this->StudentPhoto = '';

			$this->DOB = '0000-00-00';
			$this->Gender = '';
			$this->BloodGroup = '';
			$this->Religion = '';
			$this->Category = '';

			$this->IsEWS = 0;
			$this->HasDisability = 0;
			$this->IsSingleGirl = 0;

			$this->Address1 = '';
			$this->Address2 = '';

			$this->CityID = 0;
			$this->DistrictID = 0;
			$this->StateID = 0;
			$this->CountryID = 0;
			$this->PinCode = '';

			$this->MobileNumber = '';
			$this->Email = '';

			$this->LastClass = '';
			$this->LastSchool = '';
			$this->LastSchoolBoard = '';

			$this->TCReceived = 0;
			$this->TCDate = '0000-00-00';

			$this->SubjectsProposed = '';
			$this->MotherTongue = '';
			$this->HomeTown = '';

			$this->LastExamStatus = '';
			$this->LastExamPercentage = 0;

			$this->DateFrom = '0000-00-00';
		}
	}
	
	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetStudentID()
	{
		return $this->StudentID;
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
	
	public function GetStudentPhoto()
	{
		return $this->StudentPhoto;
	}
	public function SetStudentPhoto($StudentPhoto)
	{
		$this->StudentPhoto = $StudentPhoto;
	}
	
	public function GetDOB()
	{
		return $this->DOB;
	}
	public function SetDOB($DOB)
	{
		$this->DOB = $DOB;
	}

	public function GetGender()
	{
		return $this->Gender;
	}
	public function SetGender($Gender)
	{
		$this->Gender = $Gender;
	}

	public function GetBloodGroup()
	{
		return $this->BloodGroup;
	}
	public function SetBloodGroup($BloodGroup)
	{
		$this->BloodGroup = $BloodGroup;
	}
	
	public function GetReligion()
	{
		return $this->Religion;
	}
	public function SetReligion($Religion)
	{
		$this->Religion = $Religion;
	}

	public function GetCategory()
	{
		return $this->Category;
	}
	public function SetCategory($Category)
	{
		$this->Category = $Category;
	}

	public function GetIsEWS()
	{
		return $this->IsEWS;
	}
	public function SetIsEWS($IsEWS)
	{
		$this->IsEWS = $IsEWS;
	}

	public function GetHasDisability()
	{
		return $this->HasDisability;
	}
	public function SetHasDisability($HasDisability)
	{
		$this->HasDisability = $HasDisability;
	}

	public function GetIsSingleGirl()
	{
		return $this->IsSingleGirl;
	}
	public function SetIsSingleGirl($IsSingleGirl)
	{
		$this->IsSingleGirl = $IsSingleGirl;
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

	public function GetMobileNumber()
	{
		return $this->MobileNumber;
	}
	public function SetMobileNumber($MobileNumber)
	{
		$this->MobileNumber = $MobileNumber;
	}

	public function GetEmail()
	{
		return $this->Email;
	}
	public function SetEmail($Email)
	{
		$this->Email = $Email;
	}

	public function GetLastClass()
	{
		return $this->LastClass;
	}
	public function SetLastClass($LastClass)
	{
		$this->LastClass = $LastClass;
	}

	public function GetLastSchool()
	{
		return $this->LastSchool;
	}
	public function SetLastSchool($LastSchool)
	{
		$this->LastSchool = $LastSchool;
	}

	public function GetLastSchoolBoard()
	{
		return $this->LastSchoolBoard;
	}
	public function SetLastSchoolBoard($LastSchoolBoard)
	{
		$this->LastSchoolBoard = $LastSchoolBoard;
	}

	public function GetTCReceived()
	{
		return $this->TCReceived;
	}
	public function SetTCReceived($TCReceived)
	{
		$this->TCReceived = $TCReceived;
	}

	public function GetTCDate()
	{
		return $this->TCDate;
	}
	public function SetTCDate($TCDate)
	{
		$this->TCDate = $TCDate;
	}

	public function GetSubjectsProposed()
	{
		return $this->SubjectsProposed;
	}
	public function SetSubjectsProposed($SubjectsProposed)
	{
		$this->SubjectsProposed = $SubjectsProposed;
	}

	public function GetMotherTongue()
	{
		return $this->MotherTongue;
	}
	public function SetMotherTongue($MotherTongue)
	{
		$this->MotherTongue = $MotherTongue;
	}

	public function GetHomeTown()
	{
		return $this->HomeTown;
	}
	public function SetHomeTown($HomeTown)
	{
		$this->HomeTown = $HomeTown;
	}

	public function GetLastExamStatus()
	{
		return $this->LastExamStatus;
	}
	public function SetLastExamStatus($LastExamStatus)
	{
		$this->LastExamStatus = $LastExamStatus;
	}

	public function GetLastExamPercentage()
	{
		return $this->LastExamPercentage;
	}
	public function SetLastExamPercentage($LastExamPercentage)
	{
		$this->LastExamPercentage = $LastExamPercentage;
	}

	public function SetDateFrom($DateFrom)
	{
		$this->DateFrom = $DateFrom;
	}
	
	public function GetLastErrorCode()
	{
		return $this->LastErrorCode;
	}
	
	//  END OF GETTER AND SETTER FUNCTIONS 	//
	
	public function Save($ParentDetail)
	{
		try
		{
			$this->DBObject->BeginTransaction();
			if ($this->SaveDetails($ParentDetail))
			{
				if ($this->SortRollNumber())
				{
					$this->DBObject->CommitTransaction();
					return true;
				}
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
			$this->DBObject->BeginTransaction();
			if ($this->RemoveStudent())
			{
				if ($this->SortRollNumber())
				{
					$this->DBObject->CommitTransaction();
					return true;
				}
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

	public function CheckDependencies()
    {
        try
        {
            $RSStudentIDInStudentSubjectsCount = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM asa_student_subjects WHERE studentID = :|1;');
            $RSStudentIDInStudentSubjectsCount->Execute($this->StudentID);

            if ($RSStudentIDInStudentSubjectsCount->FetchRow()->totalRecords > 0) 
            {
                return true;
            }

			$RSStudentIDInStudentYearlyDetailsCount = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM asa_student_yearly_details WHERE studentID = :|1;');
            $RSStudentIDInStudentYearlyDetailsCount->Execute($this->StudentID);

            if ($RSStudentIDInStudentYearlyDetailsCount->FetchRow()->totalRecords > 0) 
            {
                return true;
            }

            return false;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException: StudentDetail::CheckDependencies. Stack Trace: '.$e->getTraceAsString());
            return false;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception: StudentDetail::CheckDependencies. Stack Trace: '.$e->getTraceAsString());
            return false;
        }       
    }
		
	// END OF PUBLIC METHODS	//
	
	// START OF STATIC METHODS	//
	static function GetAllStudents(&$TotalRecords = 0, $GetTotalsOnly = false, $Filters = array(), $Start = 0, $Limit = 100)
	{
		$AllStudents = array();
		
		try
		{
			$DBConnObject = new DBConnect();

			$Conditions = array();

			if (count($Filters) > 0)
			{
			    if (!empty($Filters['AcademicYearID']))
				{
					if ($Filters['AcademicYearID'] < 2) 
					{
						// If current session is active then condition is applied on asa_students
						$Conditions[] = 'spyd.academicYearID = '.$DBConnObject->RealEscapeVariable($Filters['AcademicYearID']);

						if (!empty($Filters['ClassSectionID']))
						{
							$Conditions[] = 'spyd.previousClassSectionID = '.$DBConnObject->RealEscapeVariable($Filters['ClassSectionID']);
						}
					}
					else
					{
						// If current session is not active then condition is applied on student previous details
						$Conditions[] = 'ast.academicYearID = '.$DBConnObject->RealEscapeVariable($Filters['AcademicYearID']);

						if (!empty($Filters['ClassSectionID']))
						{
							$Conditions[] = 'ast.classSectionID = '.$DBConnObject->RealEscapeVariable($Filters['ClassSectionID']);
						}
					}
				}
				
				if (!empty($Filters['ClassID']))
				{
					
					$Conditions[] = 'ac.classID = '.$DBConnObject->RealEscapeVariable($Filters['ClassID']);
				}
				
				if (!empty($Filters['Status']))
				{
					$Conditions[] = 'ast.status = '.$DBConnObject->RealEscapeVariable($Filters['Status']);
				}

				if (!empty($Filters['Gender']))
				{
					$ORConditions = array();
					foreach ($Filters['Gender'] as $Gender) 
					{
						$ORConditions[] = 'asd.gender = '.$DBConnObject->RealEscapeVariable($Gender);
					}

					$QueryString2 = '';

					if (count($ORConditions) > 0)
					{
						$QueryString2 = implode(' OR ',$ORConditions);
					}

					$Conditions[] = $QueryString2;
				}

				if (!empty($Filters['Category']))
				{
					$ORConditions = array();

					foreach ($Filters['Category'] as $Category) 
					{
						$ORConditions[] = 'asd.category = '.$DBConnObject->RealEscapeVariable($Category);
					}

					$QueryString2 = '';

					if (count($ORConditions) > 0)
					{
						$QueryString2 = implode(' OR ', $ORConditions);
					}

					$Conditions[] = $QueryString2;
				}

				if (!empty($Filters['Other']))
				{
					$ORConditions = array();
					
					if (isset($Filters['Other']['CheckEWS'])) 
					{
						$ORConditions[] = 'asd.isEWS = 1';
					}

					if (isset($Filters['Other']['CheckDisability'])) 
					{
						$ORConditions[] = 'asd.hasDisability = 1';
					}

					if (isset($Filters['Other']['CheckSingleGirl'])) 
					{
						$ORConditions[] = 'asd.isSingleGirl = 1';
					}

					$QueryString2 = '';

					if (count($ORConditions) > 0)
					{
						$QueryString2 = implode(' OR ', $ORConditions);
					}
					
					$Conditions[] = $QueryString2;
				}

				if (!empty($Filters['BloodGroup']))
				{
					$ORConditions = array();

					foreach ($Filters['BloodGroup'] as $BloodGroup) 
					{
						$ORConditions[] = 'asd.bloodGroup = '.$DBConnObject->RealEscapeVariable($BloodGroup);
					}

					$QueryString2 = '';

					if (count($ORConditions) > 0)
					{
						$QueryString2 = implode(' OR ',$ORConditions);
					}

					$Conditions[] = $QueryString2;
				}
				
				if (!empty($Filters['StudentName']))
				{
					$Conditions[] = 'CONCAT(asd.firstName, " ", asd.lastName) LIKE ' . $DBConnObject->RealEscapeVariable('%' . $Filters['StudentName'] . '%');
				}
				
				if (!empty($Filters['FatherName']))
				{
					$Conditions[] = 'CONCAT(apd.fatherFirstName, " ", apd.fatherLastName) LIKE ' . $DBConnObject->RealEscapeVariable('%' . $Filters['FatherName'] . '%');
				}
			}

			$QueryString = '';

			if (count($Conditions) > 0)
			{
				$QueryString = implode(') AND (',$Conditions);
				
				$QueryString = ' WHERE ('.$QueryString.')';
			}

			if ($GetTotalsOnly)
			{
				$RSSearch = $DBConnObject->Prepare('SELECT COUNT(*) as totalRecords FROM
													(
														SELECT asd.studentID
														FROM asa_students ast 
														INNER JOIN asa_student_details asd On asd.studentID = ast.studentID
														INNER JOIN asa_parent_details apd ON apd.parentID = ast.parentID
														LEFT JOIN asa_student_previous_academic_year_details spyd ON spyd.studentID = asd.studentID
														INNER JOIN asa_class_sections acs ON acs.classSectionID = ast.classSectionID
														INNER JOIN asa_classes ac ON ac.classID = acs.classID
														INNER JOIN asa_section_master asm ON asm.sectionMasterID = acs.sectionMasterID
														INNER JOIN users u ON ast.createUserID = u.userID
														'.$QueryString .'

														UNION

														SELECT asd.studentID
														FROM asa_student_previous_academic_year_details spyd
														INNER JOIN asa_student_details asd On asd.studentID = spyd.studentID 
														INNER JOIN asa_students ast ON ast.studentID = asd.studentID
														INNER JOIN asa_parent_details apd ON apd.parentID = ast.parentID
														INNER JOIN asa_class_sections acs ON acs.classSectionID = spyd.previousClassSectionID
														INNER JOIN asa_classes ac ON ac.classID = acs.classID
														INNER JOIN asa_section_master asm ON asm.sectionMasterID = acs.sectionMasterID
														INNER JOIN users u ON ast.createUserID = u.userID
														'.$QueryString .'
													) temp
												');
				$RSSearch->Execute();
				
				$TotalRecords = $RSSearch->FetchRow()->totalRecords;
				
				return;
			}
			
			$RSSearch = $DBConnObject->Prepare('(SELECT ast.rollNumber, ast.enrollmentID, ast.status, ast.createUserID, ast.createDate, asd.address1, asd.address2, asd.studentID, asd.firstName, asd.lastName,
													asd.gender, asd.dob, asd.category, asd.studentPhoto, apd.fatherFirstName, apd.fatherLastName, apd.motherFirstName, 
													apd.motherLastName, apd.fatherMobileNumber, apd.motherMobileNumber, apd.userName, apd.feeCode, ac.className, ac.classSymbol, 
													asm.sectionName, u.userName AS createUserName, 
												(SELECT dateFrom FROM asa_student_status_change_log WHERE studentID = ast.studentID ORDER BY statusChangeLogID DESC LIMIT 1) AS dateFromInActive
												FROM asa_students ast 
												INNER JOIN users u ON ast.createUserID = u.userID
												INNER JOIN asa_student_details asd On asd.studentID = ast.studentID
												INNER JOIN asa_parent_details apd ON apd.parentID = ast.parentID
												LEFT JOIN asa_student_previous_academic_year_details spyd ON spyd.studentID = asd.studentID
												INNER JOIN asa_class_sections acs ON acs.classSectionID = ast.classSectionID
												INNER JOIN asa_classes ac ON ac.classID = acs.classID
												INNER JOIN asa_section_master asm ON asm.sectionMasterID = acs.sectionMasterID
												'.$QueryString.' 
												ORDER BY ac.classID, asm.sectionMasterID, asd.FirstName, asd.lastName
												)

												UNION

												( SELECT ast.rollNumber, ast.enrollmentID, ast.status, ast.createUserID, ast.createDate, asd.address1, asd.address2, asd.studentID, asd.firstName, asd.lastName, 
												asd.gender, asd.dob, asd.category, asd.studentPhoto, apd.fatherFirstName, apd.fatherLastName, apd.motherFirstName, apd.motherLastName, apd.fatherMobileNumber, 
												apd.motherMobileNumber, apd.userName, apd.feeCode, ac.className, ac.classSymbol, asm.sectionName, u.userName AS createUserName, 
												(SELECT dateFrom FROM asa_student_status_change_log WHERE studentID = ast.studentID ORDER BY statusChangeLogID DESC LIMIT 1) AS dateFromInActive 

												FROM asa_student_previous_academic_year_details spyd
												INNER JOIN asa_student_details asd On asd.studentID = spyd.studentID
												INNER JOIN asa_students ast ON ast.studentID = asd.studentID
												INNER JOIN asa_parent_details apd ON apd.parentID = ast.parentID 
												INNER JOIN users u ON ast.createUserID = u.userID
												INNER JOIN asa_class_sections acs ON acs.classSectionID = spyd.previousClassSectionID
												INNER JOIN asa_classes ac ON ac.classID = acs.classID 
												INNER JOIN asa_section_master asm ON asm.sectionMasterID = acs.sectionMasterID 
												'.$QueryString.'

												ORDER BY ac.classID, asm.sectionMasterID, asd.FirstName, asd.lastName)

												LIMIT '.(int) $Start.', '.(int) $Limit.';');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllStudents;
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{   
			    $AllStudents[$SearchRow->studentID]['EnrollmentID'] = $SearchRow->enrollmentID;
				$AllStudents[$SearchRow->studentID]['FirstName'] = $SearchRow->firstName;
				$AllStudents[$SearchRow->studentID]['LastName'] = $SearchRow->lastName;

				$AllStudents[$SearchRow->studentID]['Gender'] = $SearchRow->gender;
				$AllStudents[$SearchRow->studentID]['Dob'] = $SearchRow->dob;
				$AllStudents[$SearchRow->studentID]['Category'] = $SearchRow->category;

				$AllStudents[$SearchRow->studentID]['RollNumber'] = $SearchRow->rollNumber;
				$AllStudents[$SearchRow->studentID]['Status'] = $SearchRow->status;
				
				$AllStudents[$SearchRow->studentID]['Image'] = $SearchRow->studentPhoto;

				$AllStudents[$SearchRow->studentID]['FatherFirstName'] = $SearchRow->fatherFirstName;
				$AllStudents[$SearchRow->studentID]['FatherLastName'] = $SearchRow->fatherLastName;
				$AllStudents[$SearchRow->studentID]['MotherFirstName'] = $SearchRow->motherFirstName;
				$AllStudents[$SearchRow->studentID]['MotherLastName'] = $SearchRow->motherLastName;

				$AllStudents[$SearchRow->studentID]['FatherMobileNumber'] = $SearchRow->fatherMobileNumber;
				$AllStudents[$SearchRow->studentID]['MotherMobileNumber'] = $SearchRow->motherMobileNumber;
				$AllStudents[$SearchRow->studentID]['UserName'] = $SearchRow->userName;
				$AllStudents[$SearchRow->studentID]['FeeCode'] = $SearchRow->feeCode;
				$AllStudents[$SearchRow->studentID]['Address'] = $SearchRow->address1;

				$AllStudents[$SearchRow->studentID]['ClassName'] = $SearchRow->className;
				$AllStudents[$SearchRow->studentID]['ClassSymbol'] = $SearchRow->classSymbol;
				$AllStudents[$SearchRow->studentID]['SectionName'] = $SearchRow->sectionName;

				$AllStudents[$SearchRow->studentID]['CreateUserID'] = $SearchRow->createUserID;
				$AllStudents[$SearchRow->studentID]['CreateUserName'] = $SearchRow->createUserName;
				
				$AllStudents[$SearchRow->studentID]['CreateDate'] = $SearchRow->createDate;
				$AllStudents[$SearchRow->studentID]['DateFromInActive'] = $SearchRow->dateFromInActive;
			}

			return $AllStudents;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at Student::GetAllStudents(). Stack Trace: '.$e->getTraceAsString());
			return $AllStudents;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at Student::GetAllStudents(). Stack Trace: '.$e->getTraceAsString());
			return $AllStudents;
		}
	}

	static function GetStudentsByClassSectionID($ClassSectionID, $StudentType = 'Active', $AcademicYearID = 0)
	{
		$AllStudents = array();

		try
		{
			$DBConnObject = new DBConnect();

			if ($AcademicYearID <= 0)
			{
				$RSSearch = $DBConnObject->Prepare('SELECT academicYearID FROM asa_academic_years WHERE isCurrentYear = 1;');
				$RSSearch->Execute();

				$AcademicYearID = $RSSearch->FetchRow()->academicYearID;
			}
			
			$RSSearch = $DBConnObject->Prepare('SELECT ast.studentID, ast.parentID, ast.rollNumber, asd.firstName, asd.lastName, asd.mobileNumber, asd.studentPhoto, 
												apd.fatherMobileNumber, apd.motherMobileNumber, asd.mobileNumber 
												FROM asa_students ast 
												INNER JOIN asa_student_details asd On asd.studentID = ast.studentID 
												LEFT JOIN asa_parent_details apd ON apd.parentID = ast.parentID 
												WHERE ast.classSectionID = :|1 AND ast.status = :|2 AND ast.academicYearID = :|3

												UNION

												SELECT ast.studentID, ast.parentID, ast.rollNumber, asd.firstName, asd.lastName, asd.mobileNumber, asd.studentPhoto, 
												apd.fatherMobileNumber, apd.motherMobileNumber, asd.mobileNumber 
												FROM asa_student_previous_academic_year_details spyd
												INNER JOIN asa_student_details asd On asd.studentID = spyd.studentID 
												INNER JOIN asa_students ast ON ast.studentID = asd.studentID
												LEFT JOIN asa_parent_details apd ON apd.parentID = ast.parentID 
												WHERE spyd.previousClassSectionID = :|4 AND ast.status = :|5 AND spyd.academicYearID = :|6

												ORDER BY rollNumber, firstName, lastName
												;');
			
			$RSSearch->Execute($ClassSectionID, $StudentType, $AcademicYearID, $ClassSectionID, $StudentType, $AcademicYearID);

			if ($RSSearch->Result->num_rows == 0)
			{
				return $AllStudents;
			}

			while ($SearchRow = $RSSearch->FetchRow())
			{
				$AllStudents[$SearchRow->studentID]['FirstName'] = $SearchRow->firstName;
				$AllStudents[$SearchRow->studentID]['LastName'] = $SearchRow->lastName;

				$AllStudents[$SearchRow->studentID]['RollNumber'] = $SearchRow->rollNumber;
				$AllStudents[$SearchRow->studentID]['Image'] = $SearchRow->studentPhoto;
				
				$MobileNumber = '';
				
				if ($SearchRow->fatherMobileNumber != '')
				{
					$MobileNumber = $SearchRow->fatherMobileNumber;
				}
				else if ($SearchRow->motherMobileNumber != '')
				{
					$MobileNumber = $SearchRow->motherMobileNumber;
				}
				else if ($SearchRow->mobileNumber != '')
				{
					$MobileNumber = $SearchRow->mobileNumber;
				}
				
				$AllStudents[$SearchRow->studentID]['MobileNumber'] = $MobileNumber;
				
				$AllStudents[$SearchRow->studentID]['ParentID'] = $SearchRow->parentID;
			}

			return $AllStudents;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at Student::GetStudentsByClassSectionID(). Stack Trace: '.$e->getTraceAsString());
			return $AllStudents;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at Student::GetStudentsByClassSectionID(). Stack Trace: '.$e->getTraceAsString());
			return $AllStudents;
		}
	}
	
	static function GetStudentStatusChangeLogByID($StudentID = 0)
	{
		$StudentStatusChangeLog = array();
		
		try
		{
			$DBConnObject = new DBConnect();

			$RSSearch = $DBConnObject->Prepare('SELECT sscl.*, asd.firstName, asd.lastName, u.userName AS createUserName
												FROM asa_student_status_change_log sscl
												INNER JOIN asa_students s ON s.studentID = sscl.studentID
												INNER JOIN asa_student_details asd ON asd.studentID = sscl.studentID 
												INNER JOIN users u ON u.userID = sscl.createUserID
												WHERE sscl.studentID = :|1;');
			$RSSearch->Execute($StudentID);
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $StudentStatusChangeLog;
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$StudentStatusChangeLog[$SearchRow->statusChangeLogID]['FirstName'] = $SearchRow->firstName;
				$StudentStatusChangeLog[$SearchRow->statusChangeLogID]['LastName'] = $SearchRow->lastName;
				$StudentStatusChangeLog[$SearchRow->statusChangeLogID]['OldStatus'] = $SearchRow->oldStatus;
				$StudentStatusChangeLog[$SearchRow->statusChangeLogID]['NewStatus'] = $SearchRow->newStatus;
				$StudentStatusChangeLog[$SearchRow->statusChangeLogID]['DateFrom'] = $SearchRow->dateFrom ;
				$StudentStatusChangeLog[$SearchRow->statusChangeLogID]['CreateUserName'] = $SearchRow->createUserName;
				$StudentStatusChangeLog[$SearchRow->statusChangeLogID]['CreateDate'] = $SearchRow->createDate;
			}
			
			return $StudentStatusChangeLog;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at Student::SearchExistingParent(). Stack Trace: ' . $e->getTraceAsString());
			return $StudentStatusChangeLog;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at Student::SearchExistingParent(). Stack Trace: ' . $e->getTraceAsString());
			return $StudentStatusChangeLog;
		}
	}
	
	static function SearchExistingParent($Filters = array())
	{
		$ExistingParentDetails = array();
		
		try
		{
			$DBConnObject = new DBConnect();

			$Conditions = array();

			if (count($Filters) > 0)
			{
				if (!empty($Filters['ClassID']))
				{
					$Conditions[] = 'ac.classID = ' . $DBConnObject->RealEscapeVariable($Filters['ClassID']);
				}

				if (!empty($Filters['ClassSectionID']))
				{
					$Conditions[] = 'ast.classSectionID = ' . $DBConnObject->RealEscapeVariable($Filters['ClassSectionID']);
				}

				if (!empty($Filters['SiblingName']))
				{
					$Conditions[] = 'LOWER(CONCAT_WS(\' \', asd.firstName, asd.lastName)) LIKE LOWER(' . $DBConnObject->RealEscapeVariable('%' . $Filters['SiblingName'] . '%') . ')';
				}

				if (!empty($Filters['EnrollmentID']))
				{
					$Conditions[] = 'ast.enrollmentID = ' . $DBConnObject->RealEscapeVariable($Filters['EnrollmentID']);
				}

				if (!empty($Filters['FatherName']))
				{
					$Conditions[] = 'LOWER(CONCAT_WS(\' \', apd.fatherFirstName, apd.fatherLastName)) LIKE LOWER(' . $DBConnObject->RealEscapeVariable('%' . $Filters['FatherName'] . '%') . ')';
				}

				if (!empty($Filters['MotherName']))
				{
					$Conditions[] = 'LOWER(CONCAT_WS(\' \', apd.motherFirstName, apd.motherLastName)) LIKE LOWER(' . $DBConnObject->RealEscapeVariable('%' . $Filters['MotherName'] . '%') . ')';
				}

				if (!empty($Filters['ParentAadharNumber']))
				{
					$Conditions[] = 'apd.aadharNumber = ' . $DBConnObject->RealEscapeVariable($Filters['ParentAadharNumber']);
				}
			}

			$QueryString = '';

			if (count($Conditions) > 0)
			{
				$QueryString = ' WHERE ' . implode(' AND ', $Conditions);
			}

			$RSSearch = $DBConnObject->Prepare('SELECT ast.*, asd.*, apd.*, ac.className, ac.classSymbol, asm.sectionName 
												FROM asa_students ast 
												INNER JOIN asa_student_details asd ON asd.studentID = ast.studentID 
												INNER JOIN asa_parent_details apd ON apd.parentID = ast.parentID 
												INNER JOIN asa_class_sections acs ON acs.classSectionID = ast.classSectionID 
												INNER JOIN asa_classes ac ON ac.classID = acs.classID 
												INNER JOIN asa_section_master asm ON asm.sectionMasterID = acs.sectionMasterID 
												' . $QueryString . '
												ORDER BY asd.FirstName;');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $ExistingParentDetails;
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$ExistingParentDetails[$SearchRow->parentID]['FirstName'] = $SearchRow->firstName;
				$ExistingParentDetails[$SearchRow->parentID]['LastName'] = $SearchRow->lastName;
				$ExistingParentDetails[$SearchRow->parentID]['Gender'] = $SearchRow->gender;
				$ExistingParentDetails[$SearchRow->parentID]['Address1'] = $SearchRow->address1;
				$ExistingParentDetails[$SearchRow->parentID]['Address2'] = $SearchRow->address2;

				$ExistingParentDetails[$SearchRow->parentID]['EnrollmentID'] = $SearchRow->enrollmentID;

				$ExistingParentDetails[$SearchRow->parentID]['FatherFirstName'] = $SearchRow->fatherFirstName;
				$ExistingParentDetails[$SearchRow->parentID]['FatherLastName'] = $SearchRow->fatherLastName;
				$ExistingParentDetails[$SearchRow->parentID]['MotherFirstName'] = $SearchRow->motherFirstName;
				$ExistingParentDetails[$SearchRow->parentID]['MotherLastName'] = $SearchRow->motherLastName;
				$ExistingParentDetails[$SearchRow->parentID]['FatherMobileNumber'] = $SearchRow->fatherMobileNumber;
				$ExistingParentDetails[$SearchRow->parentID]['MotherMobileNumber'] = $SearchRow->motherMobileNumber;
				$ExistingParentDetails[$SearchRow->parentID]['ParentAadharNumber'] = $SearchRow->aadharNumber;

				$ExistingParentDetails[$SearchRow->parentID]['ClassName'] = $SearchRow->className;
				$ExistingParentDetails[$SearchRow->parentID]['ClassSymbol'] = $SearchRow->classSymbol;
				$ExistingParentDetails[$SearchRow->parentID]['SectionName'] = $SearchRow->sectionName;
			}
			
			return $ExistingParentDetails;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at Student::SearchExistingParent(). Stack Trace: ' . $e->getTraceAsString());
			return $ExistingParentDetails;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at Student::SearchExistingParent(). Stack Trace: ' . $e->getTraceAsString());
			return $ExistingParentDetails;
		}
	}
	
	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//
	private function SaveDetails($ParentDetail)
	{
		if ($this->StudentID == 0)
		{
			if($this->StudentFeeGroupID <= 0)
		    {
		        $this->StudentFeeGroupID = 1;
		    }
		    
		    if($this->AcademicYearID <= 0)
		    {
		        $RSSearchAcademicYearID = $this->DBObject->Prepare('SELECT academicYearID FROM asa_academic_years WHERE isCurrentYear = 1;');
			
			    $RSSearchAcademicYearID->Execute();
			    
		        $this->AcademicYearID = $RSSearchAcademicYearID->FetchRow()->academicYearID;
		    }
		    
			$RSSaveStudent = $this->DBObject->Prepare('INSERT INTO asa_students (studentRegistrationID, parentID, classSectionID, colourHouseID, userName, enrollmentID, 
															admissionDate, rollNumber, aadharNumber, studentType, status, academicYearID, createUserID, createDate)
														VALUES (:|1, :|2, :|3, :|4, :|5, :|6, :|7, :|8, :|9, :|10, :|11, :|12, :|13, NOW());');
		
			$RSSaveStudent->Execute($this->StudentRegistrationID, $this->ParentID, $this->ClassSectionID, $this->ColourHouseID, $this->UserName, $this->EnrollmentID, 
								$this->AdmissionDate, $this->RollNumber, $this->AadharNumber, $this->StudentType, $this->Status, $this->AcademicYearID, $this->CreateUserID);
								
			$this->StudentID = $RSSaveStudent->LastID;

			$RSSaveStudentDetail = $this->DBObject->Prepare('INSERT INTO asa_student_details (studentID, firstName, lastName, studentPhoto, dob, gender, bloodGroup, religion,
																	category, isEWS, hasDisability, isSingleGirl, address1, address2, 
																	cityID, districtID, stateID, countryID, pinCode, mobileNumber, 
																	email, lastClass, lastSchool, lastSchoolBoard, tcReceived, tcDate,
																	subjectsProposed, motherTongue, homeTown, lastExamStatus, lastExamPercentage)
															VALUES (:|1, :|2, :|3, :|4, :|5, :|6, :|7, :|8, :|9, :|10, :|11, :|12, :|13, :|14, :|15,
															 :|16, :|17, :|18, :|19, :|20, :|21, :|22, :|23, :|24, :|25, :|26, :|27, :|28, :|29, :|30, :|31);');
		
			$RSSaveStudentDetail->Execute($this->StudentID, $this->FirstName, $this->LastName, $this->StudentPhoto, $this->DOB, $this->Gender, $this->BloodGroup, $this->Religion, 
										$this->Category, $this->IsEWS, $this->HasDisability, $this->IsSingleGirl, $this->Address1, $this->Address2, 
										$this->CityID, $this->DistrictID, $this->StateID, $this->CountryID, $this->PinCode, $this->MobileNumber, 
										$this->Email, $this->LastClass, $this->LastSchool, $this->LastSchoolBoard, $this->TCReceived, $this->TCDate, 
										$this->SubjectsProposed, $this->MotherTongue, $this->HomeTown, $this->LastExamStatus, $this->LastExamPercentage);

			$RSSaveParentDetail = $this->DBObject->Prepare('INSERT INTO asa_parent_details (fatherFirstName, fatherLastName, motherFirstName, 
																		motherLastName, fatherOccupation, motherOccupation, 
																		fatherOfficeName, motherOfficeName, fatherOfficeAddress, 
																		motherOfficeAddress, residentailAddress, residentailCityID, 
																		residentailDistrictID, residentailStateID, residentailCountryID, 
																		residentailPinCode, permanentAddress, permanentCityID, 
																		permanentDistrictID, permanentStateID,permanentCountryID, 
																		permanentPinCode, phoneNumber, fatherMobileNumber, 
																		motherMobileNumber, fatherEmail, motherEmail, 
																		userName ,feeCode, aadharNumber, isActive)
															VALUES (:|1, :|2, :|3, :|4, :|5, :|6, :|7, :|8, :|9, :|10, :|11, :|12, :|13, :|14, :|15,
															:|16, :|17, :|18, :|19, :|20, :|21, :|22, :|23, :|24, :|25, :|26, :|27, :|28, :|29, :|30, :|31);'
														);
			
			$RSSaveParentDetail->Execute($ParentDetail->GetFatherFirstName(), $ParentDetail->GetFatherLastName(), $ParentDetail->GetMotherFirstName(),
										 $ParentDetail->GetMotherLastName(), $ParentDetail->GetFatherOccupation(), $ParentDetail->GetMotherOccupation(),
										 $ParentDetail->GetFatherOfficeName(), $ParentDetail->GetMotherOfficeName(), $ParentDetail->GetFatherOfficeAddress(), 
										 $ParentDetail->GetMotherOfficeAddress(), $ParentDetail->GetResidentailAddress(), $ParentDetail->GetResidentailCityID(), 
										 $ParentDetail->GetResidentailDistrictID(), $ParentDetail->GetResidentailStateID(),$ParentDetail->GetResidentailCountryID(), 
										 $ParentDetail->GetResidentailPinCode(), $ParentDetail->GetPermanentAddress(), $ParentDetail->GetPermanentCityID(), 
										 $ParentDetail->GetPermanentDistrictID(), $ParentDetail->GetPermanentStateID(), $ParentDetail->GetPermanentCountryID(),
										 $ParentDetail->GetPermanentPinCode(), $ParentDetail->GetPhoneNumber(), $ParentDetail->GetFatherMobileNumber(), 
										 $ParentDetail->GetMotherMobileNumber(), $ParentDetail->GetFatherEmail(), $ParentDetail->GetMotherEmail(), 
										 $ParentDetail->GetUserName(), $ParentDetail->GetFeeCode(), $ParentDetail->GetAadharNumber(), $ParentDetail->GetIsActive());

			$this->ParentID = $RSSaveParentDetail->LastID;

			$RSUpdateStudent = $this->DBObject->Prepare('UPDATE asa_students
														SET	parentID = :|1
														WHERE studentID = :|2 LIMIT 1;');
													
			$RSUpdateStudent->Execute($this->ParentID, $this->StudentID);
			
			$RSAssignFeeGroup = $this->DBObject->Prepare('INSERT INTO afm_fee_group_assigned_records (feeGroupID, recordID, createUserID, createDate)
														VALUES (:|1, :|2, :|3, NOW());');
			
			$RSAssignFeeGroup->Execute($this->StudentFeeGroupID, $this->StudentID, $this->CreateUserID);
			
			$RSSearchFeeStructureID = $this->DBObject->Prepare('SELECT feeStructureID FROM afm_fee_structure
																WHERE academicYearID = :|1 AND classID = (SELECT classID FROM asa_class_sections WHERE classSectionID = :|2) AND feeGroupID = :|3;');
			
			$RSSearchFeeStructureID->Execute($this->AcademicYearID, $this->ClassSectionID, $this->StudentFeeGroupID);
			
			if ($RSSearchFeeStructureID->Result->num_rows > 0)
			{
			    $RSSaveStudentFeeStrucutre = $this->DBObject->Prepare('INSERT INTO afm_student_fee_structure (feeStructureDetailID, studentID, amountPayable)
																		SELECT feeStructureDetailID, :|1, feeAmount FROM afm_fee_structure_details 
                                                                        WHERE feeStructureID = :|2 AND feeAmount > 0;');
			
				$RSSaveStudentFeeStrucutre->Execute($this->StudentID, $RSSearchFeeStructureID->FetchRow()->feeStructureID);
			}
            
		}
		else
		{
			$OldStatus = '';
		
			$RSSearchStudentStatusChangeLog = $this->DBObject->Prepare('SELECT status FROM asa_students WHERE studentID = :|1 LIMIT 1');
			$RSSearchStudentStatusChangeLog->Execute($this->StudentID);

			if ($RSSearchStudentStatusChangeLog->Result->num_rows > 0)
			{
				$OldStatus = $RSSearchStudentStatusChangeLog->FetchRow()->status;
			}

			if ($this->Status != $OldStatus)
			{
				$RSSaveStudentStatusChanegLog = $this->DBObject->Prepare('INSERT INTO asa_student_status_change_log (studentID, oldStatus, newStatus, dateFrom, createUserID, createDate)
																		VALUES (:|1, :|2, :|3, :|4, :|5, NOW());');

				$RSSaveStudentStatusChanegLog->Execute($this->StudentID, $OldStatus, $this->Status, $this->DateFrom,  $this->CreateUserID);
			}
			
			$RSUpdateStudents = $this->DBObject->Prepare('UPDATE asa_students
														SET	parentID = :|1,
															classSectionID = :|2,
															colourHouseID = :|3,
															enrollmentID = :|4,
															admissionDate = :|5,
															aadharNumber = :|6,
															studentType = :|7,
															status = :|8
														WHERE studentID = :|9 LIMIT 1;');
													
			$RSUpdateStudents->Execute($this->ParentID, $this->ClassSectionID, $this->ColourHouseID, $this->EnrollmentID, $this->AdmissionDate, $this->AadharNumber, $this->StudentType, 
				$this->Status, $this->StudentID);

			$RSUpdateStudentDetails = $this->DBObject->Prepare('UPDATE asa_student_details
																SET	firstName = :|1,
																	lastName = :|2,
																	studentPhoto = :|3,
																	dob = :|4,
																	gender = :|5,
																	bloodGroup = :|6,
																	category = :|7,
																	isEWS = :|8,
																	hasDisability = :|9,
																	isSingleGirl = :|10,
																	address1 = :|11,
																	address2 = :|12,
																	cityID = :|13,
																	districtID = :|14,
																	stateID = :|15,
																	countryID = :|16,
																	pinCode = :|17,
																	mobileNumber = :|18,
																	email = :|19,
																	lastClass = :|20,
																	lastSchool = :|21,
																	lastSchoolBoard = :|22,
																	tcReceived = :|23,
																	tcDate = :|24,
																	subjectsProposed = :|25,
																	motherTongue = :|26,
																	homeTown = :|27,
																	lastExamStatus = :|28,
																	lastExamPercentage = :|29
																WHERE studentID = :|30 LIMIT 1;');
													
			$RSUpdateStudentDetails->Execute($this->FirstName, $this->LastName, $this->StudentPhoto, $this->DOB, $this->Gender, $this->BloodGroup, 
										$this->Category, $this->IsEWS, $this->HasDisability, $this->IsSingleGirl, $this->Address1, $this->Address2, 
										$this->CityID, $this->DistrictID, $this->StateID, $this->CountryID, $this->PinCode, $this->MobileNumber, 
										$this->Email, $this->LastClass, $this->LastSchool, $this->LastSchoolBoard, $this->TCReceived, $this->TCDate, 
										$this->SubjectsProposed, $this->MotherTongue, $this->HomeTown, $this->LastExamStatus,
										$this->LastExamPercentage, $this->StudentID);
			
			$RSUpdateParentDetails = $this->DBObject->Prepare('UPDATE asa_parent_details
																SET	fatherFirstName = :|1,
																	fatherLastName = :|2,
																	motherFirstName = :|3,
																	motherLastName = :|4,
																	fatherOccupation = :|5,
																	motherOccupation = :|6,
																	fatherOfficeName = :|7,
																	motherOfficeName = :|8,
																	fatherOfficeAddress = :|9,
																	motherOfficeAddress = :|10,
																	residentailAddress = :|11,
																	residentailCityID = :|12,
																	residentailDistrictID = :|13,
																	residentailStateID = :|14,
																	residentailCountryID = :|15,
																	residentailPinCode = :|16,
																	permanentAddress = :|17,
																	permanentCityID = :|18,
																	permanentDistrictID = :|19,
																	permanentStateID = :|20,
																	permanentCountryID = :|21,
																	permanentPinCode = :|22,
																	phoneNumber = :|23,
																	fatherMobileNumber	 = :|24,
																	motherMobileNumber = :|25,
																	fatherEmail = :|26,
																	motherEmail = :|27,
																	feeCode = :|28,
																	userName = :|29,
																	aadharNumber = :|30,
																	isActive = :|31
																WHERE parentID = :|32 LIMIT 1;');
													
			$RSUpdateParentDetails->Execute($ParentDetail->GetFatherFirstName(), $ParentDetail->GetFatherLastName(), $ParentDetail->GetMotherFirstName(), 
											$ParentDetail->GetMotherLastName(), $ParentDetail->GetFatherOccupation(), $ParentDetail->GetMotherOccupation(), 
											$ParentDetail->GetFatherOfficeName(), $ParentDetail->GetMotherOfficeName(), $ParentDetail->GetFatherOfficeAddress(), 
											$ParentDetail->GetMotherOfficeAddress(),$ParentDetail->GetResidentailAddress(), $ParentDetail->GetResidentailCityID(), 
											$ParentDetail->GetResidentailDistrictID(), $ParentDetail->GetResidentailStateID(), $ParentDetail->GetResidentailCountryID(), 
											$ParentDetail->GetResidentailPinCode(), $ParentDetail->GetPermanentAddress(), $ParentDetail->GetPermanentCityID(), 
											$ParentDetail->GetPermanentDistrictID(), $ParentDetail->GetPermanentStateID(), $ParentDetail->GetPermanentCountryID(), 
											$ParentDetail->GetPermanentPinCode(), $ParentDetail->GetPhoneNumber(), $ParentDetail->GetFatherMobileNumber(), 
											$ParentDetail->GetMotherMobileNumber(), $ParentDetail->GetFatherEmail(), $ParentDetail->GetMotherEmail(), 
											$ParentDetail->GetFeeCode(), $ParentDetail->GetUserName(),
											$ParentDetail->GetAadharNumber(), $ParentDetail->GetIsActive(), $this->ParentID);
			
			$RSSearchUser = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM users WHERE userName = :|1;');
			$RSSearchUser->Execute($ParentDetail->GetUserName());

			if ($RSSearchUser->FetchRow()->totalRecords <= 0)
			{
				//	Default password for fauculty will be starting 4 characters of firstname + lastname + birth year
				//	For Ex. FirstName = Jai & LastName = Singh & DOB = 22/10/1995 
				//	Your password will be: JAIS1995
				$Password = strtoupper( substr($ParentDetail->GetFatherFirstName() . $ParentDetail->GetFatherLastName(), 0, 4) . date('Y', strtotime($this->DOB)) );

				$RSCreateParentLogin = $this->DBObject->Prepare('INSERT INTO users (userName, password, roleID, isActive, createUserID, createDate) VALUES (:|1, :|2, :|3, :|4, :|5, NOW());');
				$RSCreateParentLogin->Execute($ParentDetail->GetUserName(), SHA1($Password), ROLE_PARENT, 1, $this->CreateUserID);
			}
			else
			{
				error_log('Criticle Error: Generated Username already exists in the records.');
			}
		}
		
		return true;
	}

	private function SortRollNumber()
    {
        if (!isset($this->StudentID)) 
        {
            throw new ApplicationARException('', APP_ERROR_UNDEFINED_ERROR);
        } 

        $RSSearch = $this->DBObject->Prepare('SELECT ass.studentID FROM asa_students ass 
									        	INNER JOIN asa_student_details asd on asd.studentID = ass.studentID 
									        	WHERE classSectionID = :|1 AND academicYearID = :|2 AND ass.status = \'Active\'
									        	ORDER BY CONCAT(TRIM(asd.firstName)," ",TRIM(asd.lastName));');
        $RSSearch->Execute($this->ClassSectionID, $this->AcademicYearID);

        if ($RSSearch->Result->num_rows > 0)
		{
			$Counter = 0;

			while ($SearchRow = $RSSearch->FetchRow())
			{
				$RSUpdateStudent = $this->DBObject->Prepare('UPDATE asa_students
															SET	rollNumber = :|1
															WHERE studentID = :|2 LIMIT 1;');
													
				$RSUpdateStudent->Execute(++$Counter, $SearchRow->studentID);
			}
		}

		return true;
    }

    private function RemoveStudent()
    {
        if (!isset($this->StudentID)) 
        {
            throw new ApplicationARException('', APP_AR_ERROR_DELETE_WITHOUT_ID);
        }
        
        $RSDeleteStudent = $this->DBObject->Prepare('DELETE FROM asa_students WHERE studentID = :|1 LIMIT 1;');
        $RSDeleteStudent->Execute($this->StudentID);

        $RSDeleteStudentDetails = $this->DBObject->Prepare('DELETE FROM asa_student_details WHERE studentID = :|1 LIMIT 1;');
        $RSDeleteStudentDetails->Execute($this->StudentID);

        $RSParentAvailability = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM asa_students WHERE parentID = :|1;');
        $RSParentAvailability->Execute($this->ParentID);

        if ($RSParentAvailability->FetchRow()->totalRecords <= 0) 
        {
            $RSDeleteParent = $this->DBObject->Prepare('DELETE FROM asa_parent_details WHERE parentID = :|1 LIMIT 1;');
        	$RSDeleteParent->Execute($this->ParentID);  
        }

        return true;
    }

	private function GetStudentDetailByID()
	{
		$RSStudentDetail = $this->DBObject->Prepare('SELECT * FROM asa_student_details WHERE studentID = :|1 LIMIT 1;');
		$RSStudentDetail->Execute($this->StudentID);
		
		$StudentDetailRow = $RSStudentDetail->FetchRow();
		
		$this->SetAttributesFromDB($StudentDetailRow);	
		
	}
	
	private function SetAttributesFromDB($StudentDetailRow)
	{
		$this->StudentID = $StudentDetailRow->studentID;
		$this->FirstName = $StudentDetailRow->firstName;
		$this->LastName = $StudentDetailRow->lastName;
		$this->StudentPhoto = $StudentDetailRow->studentPhoto;

		$this->DOB = $StudentDetailRow->dob;
		$this->Gender = $StudentDetailRow->gender;
		$this->BloodGroup = $StudentDetailRow->bloodGroup;
		$this->Religion = $StudentDetailRow->religion;
		$this->Category = $StudentDetailRow->category;

		$this->IsEWS = $StudentDetailRow->isEWS;
		$this->HasDisability = $StudentDetailRow->hasDisability;
		$this->IsSingleGirl = $StudentDetailRow->isSingleGirl;

		$this->Address1 = $StudentDetailRow->address1;
		$this->Address2 = $StudentDetailRow->address2;

		$this->CityID = $StudentDetailRow->cityID;
		$this->DistrictID = $StudentDetailRow->districtID;
		$this->StateID = $StudentDetailRow->stateID;
		$this->CountryID = $StudentDetailRow->countryID;
		$this->PinCode = $StudentDetailRow->pinCode;

		$this->MobileNumber = $StudentDetailRow->mobileNumber;
		$this->Email = $StudentDetailRow->email;

		$this->LastClass = $StudentDetailRow->lastClass;
		$this->LastSchool = $StudentDetailRow->lastSchool;
		$this->LastSchoolBoard = $StudentDetailRow->lastSchoolBoard;

		$this->TCReceived = $StudentDetailRow->tcReceived;
		$this->TCDate = $StudentDetailRow->tcDate;

		$this->SubjectsProposed = $StudentDetailRow->subjectsProposed;
		$this->MotherTongue = $StudentDetailRow->motherTongue;
		$this->HomeTown = $StudentDetailRow->homeTown;

		$this->LastExamStatus = $StudentDetailRow->lastExamStatus;
		$this->LastExamPercentage = $StudentDetailRow->lastExamPercentage;
	}	
}
?>
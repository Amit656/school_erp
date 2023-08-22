<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.db_connect.php');
error_reporting(E_ALL);

class StudentRegistration
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	
	private $StudentRegistrationID;
	private $EnquiryID;

	private $ClassID;
	private $FirstName;
	private $LastName;

	private $DOB;
	private $Gender;
	private $BloodGroup;
	private $Category;

	private $IsEWS;
	private $HasDisability;
	private $IsSingleGirl;

	private $FatherName;
	private $MotherName;

	private $ResidentAddress;
	private $PermanentAddress;

	private $CityID;
	private $DistrictID;
	private $StateID;
	private $CountryID;
	private $PinCode;

	private $AadharNumber;
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

	private $IsAdmissionTaken;
	private $RegistrationFee;
	private $AcademicYearID;

	private $IsActive;
	private $IsOnline;
	private $CreateUserID;
	private $CreateDate;

	private $EntranceExamDetails;
	
	// PUBLIC METHODS START HERE	//
	public function __construct($StudentRegistrationID = 0)
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;
		
		if($StudentRegistrationID != 0)
		{
			$this->StudentRegistrationID = $StudentRegistrationID;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetStudentRegistrationByID();
		}
		else
		{
			//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
			$this->StudentRegistrationID = 0;
			$this->EnquiryID = 0;

			$this->ClassID = 0;
			$this->FirstName = '';
			$this->LastName = '';

			$this->DOB = '0000-00-00';
			$this->Gender = '';
			$this->BloodGroup = '';
			$this->Category = '';

			$this->IsEWS = 0;
			$this->HasDisability = 0;
			$this->IsSingleGirl = 0;

			$this->FatherName = '';
			$this->MotherName = '';

			$this->ResidentAddress = '';
			$this->PermanentAddress = '';

			$this->CityID = 0;
			$this->DistrictID = 0;
			$this->StateID = 0;
			$this->CountryID = 0;
			$this->PinCode = '';

			$this->AadharNumber = 0;
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

			$this->IsAdmissionTaken = 0;
			$this->RegistrationFee = 0.00;
			$this->AcademicYearID = 0;

			$this->IsActive = 0;
			$this->IsOnline = 0;
			$this->CreateUserID = 0;
			$this->CreateDate = '0000-00-00 00:00:00';

			$this->EntranceExamDetails = '';
		}
	}
	
	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetStudentRegistrationID()
	{
		return $this->StudentRegistrationID;
	}

	public function GetEnquiryID()
	{
		return $this->EnquiryID;
	}
	public function SetEnquiryID($EnquiryID)
	{
		$this->EnquiryID = $EnquiryID;
	}
	
	public function GetClassID()
	{
		return $this->ClassID;
	}
	public function SetClassID($ClassID)
	{
		$this->ClassID = $ClassID;
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

	public function GetFatherName()
	{
		return $this->FatherName;
	}
	public function SetFatherName($FatherName)
	{
		$this->FatherName = $FatherName;
	}

	public function GetMotherName()
	{
		return $this->MotherName;
	}
	public function SetMotherName($MotherName)
	{
		$this->MotherName = $MotherName;
	}

	public function GetResidentAddress()
	{
		return $this->ResidentAddress;
	}
	public function SetResidentAddress($ResidentAddress)
	{
		$this->ResidentAddress = $ResidentAddress;
	}

	public function GetPermanentAddress()
	{
		return $this->PermanentAddress;
	}
	public function SetPermanentAddress($PermanentAddress)
	{
		$this->PermanentAddress = $PermanentAddress;
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

	public function GetAadharNumber()
	{
		return $this->AadharNumber;
	}
	public function SetAadharNumber($AadharNumber)
	{
		$this->AadharNumber = $AadharNumber;
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

	public function GetIsAdmissionTaken()
	{
		return $this->IsAdmissionTaken;
	}
	public function SetIsAdmissionTaken($IsAdmissionTaken)
	{
		$this->IsAdmissionTaken = $IsAdmissionTaken;
	}
	
	public function GetRegistrationFee()
	{
		return $this->RegistrationFee;
	}
	public function SetRegistrationFee($RegistrationFee)
	{
		$this->RegistrationFee = $RegistrationFee;
	}
	
	public function GetAcademicYearID()
	{
		return $this->AcademicYearID;
	}
	public function SetAcademicYearID($AcademicYearID)
	{
		$this->AcademicYearID = $AcademicYearID;
	}

	public function GetIsActive()
	{
		return $this->IsActive;
	}
	public function SetIsActive($IsActive)
	{
		$this->IsActive = $IsActive;
	}

	public function GetIsOnline()
	{
		return $this->IsOnline;
	}
	public function SetIsOnline($IsOnline)
	{
		$this->IsOnline = $IsOnline;
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

	public function GetEntranceExamDetails()
	{
		return $this->EntranceExamDetails;
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
            $this->RemoveStudent();
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
            $RSCount = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM asa_student_subjects WHERE studentRegistrationID = :|1;');
            $RSCount->Execute($this->StudentRegistrationID);

            if ($RSCount->FetchRow()->totalRecords > 0) 
            {
                return true;
            }

            return false;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException: StudentRegistration::CheckDependencies. Stack Trace: ' . $e->getTraceAsString());
            return false;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception: StudentRegistration::CheckDependencies. Stack Trace: ' . $e->getTraceAsString());
            return false;
        }       
    }

    public function RecordExists()
	{
		try
		{
			$QueryString = '';
			
			if ($this->StudentRegistrationID > 0)
			{
				$QueryString = ' AND studentRegistrationID != ' . $this->DBObject->RealEscapeVariable($this->StudentRegistrationID);
			}

			$RSTotal = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM aad_student_registrations WHERE enquiryID = :|1' . $QueryString . ';');
			$RSTotal->Execute($this->EnquiryID);
			
			if ($RSTotal->FetchRow()->totalRecords > 0)
			{
				return true;
			}
			
			return false;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at StudentRegistration::RecordExists(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at StudentRegistration::RecordExists(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
	}

	public function AadharExist()
	{
		try
		{
			$RSTotal = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM aad_student_registrations WHERE aadharNumber = :|1;');
			$RSTotal->Execute($this->AadharNumber);
			
			if ($RSTotal->FetchRow()->totalRecords > 0)
			{
				return true;
			}
			
			return false;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at StudentRegistration::AadharExist(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at BranchStaff::StudentRegistration(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
	}

	public function FillEntranceExamDetails()
	{
		try
		{
			$RSSearchExamDetails = $this->DBObject->Prepare('SELECT * FROM aad_exam_registrations WHERE studentRegistrationID = :|1 LIMIT 1;');
			$RSSearchExamDetails->Execute($this->StudentRegistrationID);

			if ($RSSearchExamDetails->Result->num_rows > 0)
			{
				while($SearchRow = $RSSearchExamDetails->FetchRow())
				{
					$this->EntranceExamDetails['ExamRegistrationID'] = $SearchRow->examRegistrationID;
					$this->EntranceExamDetails['ExamID'] = $SearchRow->examID;
					$this->EntranceExamDetails['ExamDate'] = $SearchRow->examDate;
					$this->EntranceExamDetails['ExamTime'] = $SearchRow->examTime;
				}
			}
			
			return true;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at StudentRegistration::FillEntranceExamDetails(). Stack Trace: ' . $e->getTraceAsString());
			return false;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at StudentRegistration::FillEntranceExamDetails(). Stack Trace: ' . $e->getTraceAsString());
			return false;
		}
	}
		
	// END OF PUBLIC METHODS	//
	
	// START OF STATIC METHODS	//
	static function SearchStudentRegistration(&$TotalRecords = 0, $GetTotalsOnly = false, $Filters = array(), $Start = 0, $Limit = 100)
	{
		$AllStudentRegistrations = array();
		
		try
		{
			$DBConnObject = new DBConnect();

			$Conditions = array();

			if (count($Filters) > 0)
			{	
				if (count($Filters['ClassID']) > 0)
				{	
					$ClassIDs = implode(',', $Filters['ClassID']);
					$Conditions[] = 'asr.classID IN (' . $DBConnObject->RealEscapeVariable($ClassIDs) . ')';
				}

				if (!empty($Filters['StudentName']))
				{
					$Conditions[] = '(asr.firstName LIKE ' . $DBConnObject->RealEscapeVariable("%" . $Filters['StudentName'] . "%") . ' OR asr.lastName LIKE ' . 
										$DBConnObject->RealEscapeVariable("%" . $Filters['StudentName'] . "%").")";
				}

				if (count($Filters['Genders']) > 0)
				{	
					$ORConditions = array();

					foreach ($Filters['Genders'] as $Genders) 
					{
						$ORConditions[] = 'asr.gender = ' . $DBConnObject->RealEscapeVariable($Genders);
					}

					$ForGenders = '';

					if (count($ORConditions) > 0)
					{
						$ForGenders = implode(' OR ',$ORConditions);
					}

					$Conditions[] = $ForGenders;
				}

				if (!empty($Filters['MobileNumber']))
				{
					$Conditions[] = 'asr.mobileNumber LIKE ' . $DBConnObject->RealEscapeVariable("%" . $Filters['MobileNumber'] . "%");
				}

				if (count($Filters['BloodGroups']) > 0)
				{	
					$ORConditions = array();

					foreach ($Filters['BloodGroups'] as $BloodGroup) 
					{
						$ORConditions[] = 'asr.bloodGroup = ' . $DBConnObject->RealEscapeVariable($BloodGroup);
					}

					$ForBloodGroups = '';

					if (count($ORConditions) > 0)
					{
						$ForBloodGroups = implode(' OR ',$ORConditions);
					}

					$Conditions[] = $ForBloodGroups;
				}

				if (count($Filters['Categories']) > 0)
				{	
					$ORConditions = array();

					foreach ($Filters['Categories'] as $Category) 
					{
						$ORConditions[] = 'asr.category = ' . $DBConnObject->RealEscapeVariable($Category);
					}

					$ForCategories = '';

					if (count($ORConditions) > 0)
					{
						$ForCategories = implode(' OR ',$ORConditions);
					}

					$Conditions[] = $ForCategories;
				}

				if (!empty($Filters['FatherName']))
				{
					$Conditions[] = 'asr.fatherName LIKE ' . $DBConnObject->RealEscapeVariable("%" . $Filters['FatherName'] . "%");
				}

				if (!empty($Filters['MotherName']))
				{
					$Conditions[] = 'asr.motherName LIKE ' . $DBConnObject->RealEscapeVariable("%" . $Filters['MotherName'] . "%");
				}

				if (!empty($Filters['CountryID']))
				{
					$Conditions[] = 'asr.countryID = ' . $DBConnObject->RealEscapeVariable($Filters['CountryID']);
				}

				if (!empty($Filters['StateID']))
				{
					$Conditions[] = 'asr.stateID = ' . $DBConnObject->RealEscapeVariable($Filters['StateID']);
				}

				if (!empty($Filters['DistrictID']))
				{
					$Conditions[] = 'asr.districtID = ' . $DBConnObject->RealEscapeVariable($Filters['DistrictID']);
				}

				if (!empty($Filters['CityID']))
				{
					$Conditions[] = 'asr.cityID = ' . $DBConnObject->RealEscapeVariable($Filters['CityID']);
				}
				
				if($Filters['ActiveStatus'] == 1)
				{
					$Conditions[] = 'asr.isActive = 1';
				}
				else
				{
					$Conditions[] = 'asr.isActive = 0';
				}
				
				if($Filters['IsAdmissionConfirm'] == 0)
				{
					$Conditions[] = 'asr.studentRegistrationID NOT IN (SELECT studentRegistrationID FROM asa_students)';
				}
				else
				{
					$Conditions[] = 'asr.studentRegistrationID IN (SELECT studentRegistrationID FROM asa_students)';
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
													FROM aad_student_registrations asr
													INNER JOIN asa_classes ac ON asr.classID = ac.classID
													INNER JOIN users u ON asr.createUserID = u.userID 
													' . $QueryString . ';');
				$RSTotal->Execute();
				
				$TotalRecords = $RSTotal->FetchRow()->totalRecords;
				return;
			}
			
			$RSAllStudentRegistrations = $DBConnObject->Prepare('SELECT asr.* , CONCAT(asr.firstName, " ", asr.lastName) AS studentName , ac.className, 
																	u.userName AS createUserName FROM aad_student_registrations asr
																	INNER JOIN asa_classes ac ON asr.classID = ac.classID
																	INNER JOIN users u ON asr.createUserID = u.userID 
																	' . $QueryString . ' ORDER BY asr.classID
																	LIMIT ' . (int) $Start . ', ' . (int) $Limit . ';');
			$RSAllStudentRegistrations->Execute();

			if ($RSAllStudentRegistrations->Result->num_rows <= 0)
            {
                return $AllStudentRegistrations;
            }

            while($SearchRow = $RSAllStudentRegistrations->FetchRow())
            {
                $AllStudentRegistrations[$SearchRow->studentRegistrationID]['ClassName'] = $SearchRow->className;
				$AllStudentRegistrations[$SearchRow->studentRegistrationID]['StudentName'] = $SearchRow->studentName;

				$AllStudentRegistrations[$SearchRow->studentRegistrationID]['Gender'] = $SearchRow->gender;
				$AllStudentRegistrations[$SearchRow->studentRegistrationID]['Category'] = $SearchRow->category;

				$AllStudentRegistrations[$SearchRow->studentRegistrationID]['FatherName'] = $SearchRow->fatherName;
				$AllStudentRegistrations[$SearchRow->studentRegistrationID]['MotherName'] = $SearchRow->motherName;

				$AllStudentRegistrations[$SearchRow->studentRegistrationID]['AadharNumber'] = $SearchRow->aadharNumber;
				$AllStudentRegistrations[$SearchRow->studentRegistrationID]['MobileNumber'] = $SearchRow->mobileNumber;

				$AllStudentRegistrations[$SearchRow->studentRegistrationID]['IsAdmissionTaken'] = $SearchRow->isAdmissionTaken;
				$AllStudentRegistrations[$SearchRow->studentRegistrationID]['RegistrationFee'] = $SearchRow->registrationFee;
				$AllStudentRegistrations[$SearchRow->studentRegistrationID]['AcademicYearID'] = $SearchRow->academicYearID;

				$AllStudentRegistrations[$SearchRow->studentRegistrationID]['CreateUserName'] = $SearchRow->createUserName;
				$AllStudentRegistrations[$SearchRow->studentRegistrationID]['IsActive'] = $SearchRow->isActive;
				$AllStudentRegistrations[$SearchRow->studentRegistrationID]['IsOnline'] = $SearchRow->isOnline;
				
				$AllStudentRegistrations[$SearchRow->studentRegistrationID]['CreateDate'] = $SearchRow->createDate;
            }
		
			return $AllStudentRegistrations;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at StudentRegistration::SearchStudentRegistration(). Stack Trace: ' . $e->getTraceAsString());
			return $AllStudentRegistrations;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at StudentRegistration::SearchStudentRegistration(). Stack Trace: ' . $e->getTraceAsString());
			return $AllStudentRegistrations;
		}		
	}
	
	static function SearchAdmittedStudents(&$TotalRecords = 0, $GetTotalsOnly = false, $Filters = array(), $Start = 0, $Limit = 100)
	{
		$AllAdmittedStudents = array();
		
		try
		{
			$DBConnObject = new DBConnect();

			$Conditions = array();

			if (count($Filters) > 0)
			{	
				if (count($Filters['ClassID']) > 0)
				{	
					$ClassIDs = implode(',', $Filters['ClassID']);
					$Conditions[] = 'asr.classID IN (' . $DBConnObject->RealEscapeVariable($ClassIDs) . ')';
				}

				if (!empty($Filters['StudentName']))
				{
					$Conditions[] = '(asr.firstName LIKE ' . $DBConnObject->RealEscapeVariable("%" . $Filters['StudentName'] . "%") . ' OR asr.lastName LIKE ' . 
										$DBConnObject->RealEscapeVariable("%" . $Filters['StudentName'] . "%").")";
				}

				if (count($Filters['Genders']) > 0)
				{	
					$ORConditions = array();

					foreach ($Filters['Genders'] as $Genders) 
					{
						$ORConditions[] = 'asr.gender = ' . $DBConnObject->RealEscapeVariable($Genders);
					}

					$ForGenders = '';

					if (count($ORConditions) > 0)
					{
						$ForGenders = implode(' OR ',$ORConditions);
					}

					$Conditions[] = $ForGenders;
				}

				if (!empty($Filters['MobileNumber']))
				{
					$Conditions[] = 'asr.mobileNumber LIKE ' . $DBConnObject->RealEscapeVariable("%" . $Filters['MobileNumber'] . "%");
				}

				if (count($Filters['BloodGroups']) > 0)
				{	
					$ORConditions = array();

					foreach ($Filters['BloodGroups'] as $BloodGroup) 
					{
						$ORConditions[] = 'asr.bloodGroup = ' . $DBConnObject->RealEscapeVariable($BloodGroup);
					}

					$ForBloodGroups = '';

					if (count($ORConditions) > 0)
					{
						$ForBloodGroups = implode(' OR ',$ORConditions);
					}

					$Conditions[] = $ForBloodGroups;
				}

				if (count($Filters['Categories']) > 0)
				{	
					$ORConditions = array();

					foreach ($Filters['Categories'] as $Category) 
					{
						$ORConditions[] = 'asr.category = ' . $DBConnObject->RealEscapeVariable($Category);
					}

					$ForCategories = '';

					if (count($ORConditions) > 0)
					{
						$ForCategories = implode(' OR ',$ORConditions);
					}

					$Conditions[] = $ForCategories;
				}

				if (!empty($Filters['FatherName']))
				{
					$Conditions[] = 'asr.fatherName LIKE ' . $DBConnObject->RealEscapeVariable("%" . $Filters['FatherName'] . "%");
				}

				if (!empty($Filters['MotherName']))
				{
					$Conditions[] = 'asr.motherName LIKE ' . $DBConnObject->RealEscapeVariable("%" . $Filters['MotherName'] . "%");
				}

				if (!empty($Filters['CountryID']))
				{
					$Conditions[] = 'asr.countryID = ' . $DBConnObject->RealEscapeVariable($Filters['CountryID']);
				}

				if (!empty($Filters['StateID']))
				{
					$Conditions[] = 'asr.stateID = ' . $DBConnObject->RealEscapeVariable($Filters['StateID']);
				}

				if (!empty($Filters['DistrictID']))
				{
					$Conditions[] = 'asr.districtID = ' . $DBConnObject->RealEscapeVariable($Filters['DistrictID']);
				}

				if (!empty($Filters['CityID']))
				{
					$Conditions[] = 'asr.cityID = ' . $DBConnObject->RealEscapeVariable($Filters['CityID']);
				}

				if (!empty($Filters['AcademicYearID']))
				{
					$Conditions[] = 'ass.academicYearID = ' . $DBConnObject->RealEscapeVariable($Filters['AcademicYearID']);
				}
				
				if (!empty($Filters['EnrollmentID']))
				{
					$Conditions[] = 'ass.enrollmentID = ' . $DBConnObject->RealEscapeVariable($Filters['EnrollmentID']);
				}
				
				if($Filters['ActiveStatus'] == 1)
				{
					$Conditions[] = 'asr.isActive = 1';
				}
				else
				{
					$Conditions[] = 'asr.isActive = 0';
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
													FROM aad_student_registrations asr
													INNER JOIN asa_students ass ON ass.studentRegistrationID = asr.studentRegistrationID
													INNER JOIN asa_class_sections acs ON acs.classSectionID = ass.classSectionID
													INNER JOIN asa_classes ac ON ac.classID = acs.classID
													INNER JOIN users u ON asr.createUserID = u.userID 
													' . $QueryString . ';');
				$RSTotal->Execute();
				
				$TotalRecords = $RSTotal->FetchRow()->totalRecords;
				return;
			}
			
			$RSAllAdmittedStudents = $DBConnObject->Prepare('SELECT ass.studentID, ass.admissionDate, ass.enrollmentID, asr.* , CONCAT(asr.firstName, " ", asr.lastName) AS studentName , ac.className, u.userName AS createUserName 
															FROM aad_student_registrations asr
															INNER JOIN asa_students ass ON ass.studentRegistrationID = asr.studentRegistrationID
															INNER JOIN asa_class_sections acs ON acs.classSectionID = ass.classSectionID
															INNER JOIN asa_classes ac ON ac.classID = acs.classID
															INNER JOIN users u ON asr.createUserID = u.userID 
															' . $QueryString . ' 
															ORDER BY asr.classID, studentName
															LIMIT ' . (int) $Start . ', ' . (int) $Limit . ';');
			$RSAllAdmittedStudents->Execute();

			if ($RSAllAdmittedStudents->Result->num_rows <= 0)
            {
                return $AllAdmittedStudents;
            }

            while($SearchRow = $RSAllAdmittedStudents->FetchRow())
            {
                $AllAdmittedStudents[$SearchRow->studentID]['ClassName'] = $SearchRow->className;
				$AllAdmittedStudents[$SearchRow->studentID]['StudentName'] = $SearchRow->studentName;

				$AllAdmittedStudents[$SearchRow->studentID]['Gender'] = $SearchRow->gender;
				$AllAdmittedStudents[$SearchRow->studentID]['Category'] = $SearchRow->category;

				$AllAdmittedStudents[$SearchRow->studentID]['FatherName'] = $SearchRow->fatherName;
				$AllAdmittedStudents[$SearchRow->studentID]['MotherName'] = $SearchRow->motherName;

				$AllAdmittedStudents[$SearchRow->studentID]['AadharNumber'] = $SearchRow->aadharNumber;
				$AllAdmittedStudents[$SearchRow->studentID]['MobileNumber'] = $SearchRow->mobileNumber;
				$AllAdmittedStudents[$SearchRow->studentID]['EnrollmentID'] = $SearchRow->enrollmentID;

				$AllAdmittedStudents[$SearchRow->studentID]['IsAdmissionTaken'] = $SearchRow->isAdmissionTaken;
				$AllAdmittedStudents[$SearchRow->studentID]['AdmissionDate'] = $SearchRow->admissionDate;
				$AllAdmittedStudents[$SearchRow->studentID]['RegistrationFee'] = $SearchRow->registrationFee;
				$AllAdmittedStudents[$SearchRow->studentID]['AcademicYearID'] = $SearchRow->academicYearID;

				$AllAdmittedStudents[$SearchRow->studentID]['CreateUserName'] = $SearchRow->createUserName;
				$AllAdmittedStudents[$SearchRow->studentID]['IsActive'] = $SearchRow->isActive;
				$AllAdmittedStudents[$SearchRow->studentID]['IsOnline'] = $SearchRow->isOnline;
				
				$AllAdmittedStudents[$SearchRow->studentID]['CreateDate'] = $SearchRow->createDate;
            }
		
			return $AllAdmittedStudents;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at StudentRegistration::SearchAdmittedStudents(). Stack Trace: ' . $e->getTraceAsString());
			return $AllAdmittedStudents;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at StudentRegistration::SearchAdmittedStudents(). Stack Trace: ' . $e->getTraceAsString());
			return $AllAdmittedStudents;
		}		
	}

	static function GetAllStudentRegistrations()
	{
		$AllStudentRegistrations = array();
		
		try
		{
			$DBConnObject = new DBConnect();

			$RSSearch = $DBConnObject->Prepare('SELECT asr.* , CONCAT(asr.firstName, " ", asr.lastName) AS studentName , ac.className, 
												u.userName AS createUserName FROM aad_student_registrations asr
												INNER JOIN asa_classes ac ON asr.classID = ac.classID
												INNER JOIN users u ON asr.createUserID = u.userID
												ORDER BY asr.firstName;');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllStudentRegistrations;
			}

			while($SearchRow = $RSSearch->FetchRow())
			{
				$AllStudentRegistrations[$SearchRow->studentRegistrationID]['ClassName'] = $SearchRow->className;
				$AllStudentRegistrations[$SearchRow->studentRegistrationID]['StudentName'] = $SearchRow->studentName;

				$AllStudentRegistrations[$SearchRow->studentRegistrationID]['Gender'] = $SearchRow->gender;
				$AllStudentRegistrations[$SearchRow->studentRegistrationID]['Category'] = $SearchRow->category;

				$AllStudentRegistrations[$SearchRow->studentRegistrationID]['FatherName'] = $SearchRow->fatherName;
				$AllStudentRegistrations[$SearchRow->studentRegistrationID]['MotherName'] = $SearchRow->motherName;

				$AllStudentRegistrations[$SearchRow->studentRegistrationID]['AadharNumber'] = $SearchRow->aadharNumber;
				$AllStudentRegistrations[$SearchRow->studentRegistrationID]['MobileNumber'] = $SearchRow->mobileNumber;

				$AllStudentRegistrations[$SearchRow->studentRegistrationID]['IsAdmissionTaken'] = $SearchRow->isAdmissionTaken;
				$AllStudentRegistrations[$SearchRow->studentRegistrationID]['RegistrationFee'] = $SearchRow->registrationFee;

				$AllStudentRegistrations[$SearchRow->studentRegistrationID]['CreateUserName'] = $SearchRow->createUserName;
				$AllStudentRegistrations[$SearchRow->studentRegistrationID]['IsActive'] = $SearchRow->isActive;
				$AllStudentRegistrations[$SearchRow->studentRegistrationID]['IsOnline'] = $SearchRow->isOnline;
				
				$AllStudentRegistrations[$SearchRow->studentRegistrationID]['CreateDate'] = $SearchRow->createDate;
			}

			return $AllStudentRegistrations;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at StudentRegistration::GetAllStudentRegistrations(). Stack Trace: ' . $e->getTraceAsString());
			return $AllStudentRegistrations;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at StudentRegistration::GetAllStudentRegistrations(). Stack Trace: ' . $e->getTraceAsString());
			return $AllStudentRegistrations;
		}
	}

	static function GetActiveStudentRegistrations()
	{
		$AllStudentRegistrations = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT studentRegistrationID, CONCAT(firstName, " ", lastName) AS studentName FROM aad_student_registrations 
																WHERE isAdmissionTaken = 0 AND isActive = 1;');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllStudentRegistrations;
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$AllStudentRegistrations[$SearchRow->studentRegistrationID] = $SearchRow->studentName;
			}
			
			return $AllStudentRegistrations;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at StudentRegistration::GetActiveStudentRegistrations(). Stack Trace: ' . $e->getTraceAsString());
			return $AllStudentRegistrations;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at StudentRegistration::GetActiveStudentRegistrations(). Stack Trace: ' . $e->getTraceAsString());
			return $AllStudentRegistrations;
		}		
	}

	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//
	private function SaveDetails()
	{
		if ($this->StudentRegistrationID == 0)
		{
			$RSSave = $this->DBObject->Prepare('INSERT INTO aad_student_registrations (enquiryID, classID, firstName, lastName, dob, gender, bloodGroup,
																	category, isEWS, hasDisability, isSingleGirl, fatherName, motherName, 
																	residentAddress, permanentAddress, cityID, DistrictID, 
																	stateID, countryID, pinCode, aadharNumber, mobileNumber, 
																	email, lastClass, lastSchool, lastSchoolBoard, tcReceived, tcDate,
																	subjectsProposed, motherTongue, homeTown, lastExamStatus, lastExamPercentage, 
																	isAdmissionTaken, registrationFee, academicYearID, isActive, isOnline, createUserID, createDate)
														VALUES (:|1, :|2, :|3, :|4, :|5, :|6, :|7, :|8, :|9, :|10, :|11, :|12, :|13, :|14, :|15,
														 :|16, :|17, :|18, :|19, :|20, :|21, :|22, :|23, :|24, :|25, :|26, :|27, :|28, :|29, :|30, :|31, :|32, :|33, :|34, :|35, :|36, :|37, :|38, :|39, NOW());');
			$RSSave->Execute($this->EnquiryID, $this->ClassID, $this->FirstName, $this->LastName, $this->DOB, $this->Gender, $this->BloodGroup, 
								$this->Category, $this->IsEWS, $this->HasDisability, $this->IsSingleGirl, $this->FatherName, $this->MotherName, 
								$this->ResidentAddress, $this->PermanentAddress, $this->CityID, $this->DistrictID, 
								$this->StateID, $this->CountryID, $this->PinCode, $this->AadharNumber, $this->MobileNumber, 
								$this->Email, $this->LastClass, $this->LastSchool, $this->LastSchoolBoard, $this->TCReceived, $this->TCDate, 
								$this->SubjectsProposed, $this->MotherTongue, $this->HomeTown, $this->LastExamStatus, $this->LastExamPercentage, 
								$this->IsAdmissionTaken, $this->RegistrationFee, $this->AcademicYearID, $this->IsActive, $this->IsOnline, $this->CreateUserID);

			$this->StudentRegistrationID = $RSSave->LastID;

		}
		else
		{
			$RSUpdate = $this->DBObject->Prepare('UPDATE aad_student_registrations
																SET	enquiryID = :|1,
																	classID = :|2,
																	firstName = :|3,
																	lastName = :|4,
																	dob = :|5,
																	gender = :|6,
																	bloodGroup = :|7,
																	category = :|8,
																	isEWS = :|9,
																	hasDisability = :|10,
																	isSingleGirl = :|11,
																	FatherName = :|12,
																	MotherName = :|13,
																	residentAddress = :|14,
																	permanentAddress = :|15,
																	cityID = :|16,
																	districtID = :|17,
																	stateID = :|18,
																	countryID = :|19,
																	pinCode = :|20,
																	aadharNumber = :|21,
																	mobileNumber = :|22,
																	email = :|23,
																	lastClass = :|24,
																	lastSchool = :|25,
																	lastSchoolBoard = :|26,
																	tcReceived = :|27,
																	tcDate = :|28,
																	subjectsProposed = :|29,
																	motherTongue = :|30,
																	homeTown = :|31,
																	lastExamStatus = :|32,
																	lastExamPercentage = :|33,
																	isAdmissionTaken = :|34,
																	registrationFee = :|35,
																	academicYearID = :|36,
																	isActive = :|37
																WHERE studentRegistrationID = :|38 LIMIT 1;');
			$RSUpdate->Execute($this->EnquiryID, $this->ClassID, $this->FirstName, $this->LastName, $this->DOB, $this->Gender, $this->BloodGroup, 
								$this->Category, $this->IsEWS, $this->HasDisability, $this->IsSingleGirl, $this->FatherName, $this->MotherName, 
								$this->ResidentAddress, $this->PermanentAddress, $this->CityID, $this->DistrictID, 
								$this->StateID, $this->CountryID, $this->PinCode, $this->AadharNumber, $this->MobileNumber, 
								$this->Email, $this->LastClass, $this->LastSchool, $this->LastSchoolBoard, $this->TCReceived, $this->TCDate, 
								$this->SubjectsProposed, $this->MotherTongue, $this->HomeTown, $this->LastExamStatus, $this->LastExamPercentage, 
								$this->IsAdmissionTaken, $this->RegistrationFee, $this->AcademicYearID, $this->IsActive, $this->StudentRegistrationID);
		}
		
		return true;
	}

	private function RemoveStudent()
    {
        if (!isset($this->StudentRegistrationID)) 
        {
            throw new ApplicationARException('', APP_AR_ERROR_DELETE_WITHOUT_ID);
        }

        $RSDeleteStudentRegistrations = $this->DBObject->Prepare('DELETE FROM aad_student_registrations WHERE studentRegistrationID = :|1 LIMIT 1;');
        $RSDeleteStudentRegistrations->Execute($this->StudentRegistrationID);
    }

	private function GetStudentRegistrationByID()
	{
		$RSStudentRegistration = $this->DBObject->Prepare('SELECT * FROM aad_student_registrations WHERE studentRegistrationID = :|1 LIMIT 1;');
		$RSStudentRegistration->Execute($this->StudentRegistrationID);
		
		$StudentRegistrationRow = $RSStudentRegistration->FetchRow();
		
		$this->SetAttributesFromDB($StudentRegistrationRow);	
		
	}
	
	private function SetAttributesFromDB($StudentRegistrationRow)
	{
		$this->StudentRegistrationID = $StudentRegistrationRow->studentRegistrationID;
		$this->EnquiryID =  $StudentRegistrationRow->enquiryID;

		$this->ClassID = $StudentRegistrationRow->classID;
		$this->FirstName = $StudentRegistrationRow->firstName;
		$this->LastName = $StudentRegistrationRow->lastName;

		$this->DOB = $StudentRegistrationRow->dob;
		$this->Gender = $StudentRegistrationRow->gender;
		$this->BloodGroup = $StudentRegistrationRow->bloodGroup;
		$this->Category = $StudentRegistrationRow->category;

		$this->IsEWS = $StudentRegistrationRow->isEWS;
		$this->HasDisability = $StudentRegistrationRow->hasDisability;
		$this->IsSingleGirl = $StudentRegistrationRow->isSingleGirl;

		$this->FatherName = $StudentRegistrationRow->fatherName;
		$this->MotherName = $StudentRegistrationRow->motherName;

		$this->ResidentAddress = $StudentRegistrationRow->residentAddress;
		$this->PermanentAddress = $StudentRegistrationRow->permanentAddress;

		$this->CityID = $StudentRegistrationRow->cityID;
		$this->DistrictID = $StudentRegistrationRow->districtID;
		$this->StateID = $StudentRegistrationRow->stateID;
		$this->CountryID = $StudentRegistrationRow->countryID;
		$this->PinCode = $StudentRegistrationRow->pinCode;

		$this->AadharNumber = $StudentRegistrationRow->aadharNumber;
		$this->MobileNumber = $StudentRegistrationRow->mobileNumber;
		$this->Email = $StudentRegistrationRow->email;

		$this->LastClass = $StudentRegistrationRow->lastClass;
		$this->LastSchool = $StudentRegistrationRow->lastSchool;
		$this->LastSchoolBoard = $StudentRegistrationRow->lastSchoolBoard;

		$this->TCReceived = $StudentRegistrationRow->tcReceived;
		$this->TCDate = $StudentRegistrationRow->tcDate;

		$this->SubjectsProposed = $StudentRegistrationRow->subjectsProposed;
		$this->MotherTongue = $StudentRegistrationRow->motherTongue;
		$this->HomeTown = $StudentRegistrationRow->homeTown;

		$this->IsAdmissionTaken = $StudentRegistrationRow->isAdmissionTaken;
		$this->RegistrationFee = $StudentRegistrationRow->registrationFee;
		$this->AcademicYearID = $StudentRegistrationRow->academicYearID;

		$this->LastExamStatus = $StudentRegistrationRow->lastExamStatus;
		$this->LastExamPercentage = $StudentRegistrationRow->lastExamPercentage;
		$this->IsActive = $StudentRegistrationRow->isActive;
		$this->IsOnline = $StudentRegistrationRow->isOnline;
	}	
}
?>
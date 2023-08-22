<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.db_connect.php');
error_reporting(E_ALL);

class ExamRegistration
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	
	private $ExamRegistrationID;
	private $StudentRegistrationID;
	private $ExamID;
	private $ExamDate;
	private $ExamTime;
	private $RegistrationAmount;

	private $ExamStatus;
	private $ObtainedMarks;
	private $IsAdmissionConfirmed;

	private $IsActive;
	private $CreateUserID;
	private $CreateDate;
	
	// PUBLIC METHODS START HERE	//
	public function __construct($ExamRegistrationID = 0)
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;
		
		if($ExamRegistrationID != 0)
		{
			$this->ExamRegistrationID = $ExamRegistrationID;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetExamRegistrationByID();
		}
		else
		{
			//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
			$this->ExamRegistrationID = 0;
			$this->StudentRegistrationID = 0;
			$this->ExamID = 0;
			$this->ExamDate = '';
			$this->ExamTime = '';
			$this->RegistrationAmount = 0.00;

			$this->ExamStatus = 'Awaited';
			$this->ObtainedMarks = 0;
			$this->IsAdmissionConfirmed = 0;

			$this->IsActive = 0;
			$this->CreateUserID = 0;
			$this->CreateDate = '0000-00-00 00:00:00';
		}
	}
	
	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetExamRegistrationID()
	{
		return $this->ExamRegistrationID;
	}
	
	public function GetStudentRegistrationID()
	{
		return $this->StudentRegistrationID;
	}
	public function SetStudentRegistrationID($StudentRegistrationID)
	{
		$this->StudentRegistrationID = $StudentRegistrationID;
	}

	public function GetExamID()
	{
		return $this->ExamID;
	}
	public function SetExamID($ExamID)
	{
		$this->ExamID = $ExamID;
	}

	public function GetExamDate()
	{
		return $this->ExamDate;
	}
	public function SetExamDate($ExamDate)
	{
		$this->ExamDate = $ExamDate;
	}

	public function GetExamTime()
	{
		return $this->ExamTime;
	}
	public function SetExamTime($ExamTime)
	{
		$this->ExamTime = $ExamTime;
	}

	public function GetRegistrationAmount()
	{
		return $this->RegistrationAmount;
	}
	public function SetRegistrationAmount($RegistrationAmount)
	{
		$this->RegistrationAmount = $RegistrationAmount;
	}
	
	public function GetExamStatus()
	{
		return $this->ExamStatus;
	}
	public function SetExamStatus($ExamStatus)
	{
		$this->ExamStatus = $ExamStatus;
	}

	public function GetObtainedMarks()
	{
		return $this->ObtainedMarks;
	}
	public function SetObtainedMarks($ObtainedMarks)
	{
		$this->ObtainedMarks = $ObtainedMarks;
	}
	
	public function GetIsAdmissionConfirmed()
	{
		return $this->IsAdmissionConfirmed;
	}
	public function SetIsAdmissionConfirmed($IsAdmissionConfirmed)
	{
		$this->IsAdmissionConfirmed = $IsAdmissionConfirmed;
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
			$this->RemoveExamRegistration();
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
			$RSTotal = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM role_group_roles WHERE examRegistrationID = :|1;');
			$RSTotal->Execute($this->ExamRegistrationID);
	
			if ($RSTotal->FetchRow()->totalRecords > 0) 
			{
				return true;
			}
			
			return false;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException: ExamRegistration::CheckDependencies(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception: ExamRegistration::CheckDependencies(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
	}

	public function RecordExists()
	{
		try
		{
			$QueryString = '';
			
			if ($this->ExamRegistrationID > 0)
			{
				$QueryString = ' AND examRegistrationID != ' . $this->DBObject->RealEscapeVariable($this->ExamRegistrationID);
			}

			$RSTotal = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM aad_exam_registrations WHERE studentRegistrationID = :|1' . $QueryString . ';');
			$RSTotal->Execute($this->StudentRegistrationID);
			
			if ($RSTotal->FetchRow()->totalRecords > 0)
			{
				return true;
			}
			
			return false;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at ExamRegistration::RecordExists(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at ExamRegistration::RecordExists(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
	}

	public function UpdateResultDetails()
	{
		try
		{
			$RSUpdate = $this->DBObject->Prepare('UPDATE aad_exam_registrations
													SET	examStatus = :|1,
														obtainedMarks = :|2
													WHERE examRegistrationID = :|3 LIMIT 1;');
			$RSUpdate->Execute($this->ExamStatus, $this->ObtainedMarks, $this->ExamRegistrationID);	
			
			return false;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at ExamRegistration::UpdateResultDetails(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at ExamRegistration::UpdateResultDetails(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
	}

	// END OF PUBLIC METHODS	//
	
	// START OF STATIC METHODS	//
	static function GetAllExamRegistrations()
    { 
		$AllExamRegistrations = array();
		
    	try
        {	
        	$DBConnObject = new DBConnect();
        	$RSSearch = $DBConnObject->Prepare('SELECT aer.*, ae.examName, asr.firstName, asr.lastName, asr.fatherName, asr.motherName, 
        											u.userName AS createUserName FROM aad_exam_registrations aer 
        											INNER JOIN aad_exams ae ON aer.examID = ae.examID
        											INNER JOIN aad_student_registrations asr ON aer.studentRegistrationID = asr.studentRegistrationID
													INNER JOIN users u ON aer.createUserID = u.userID 
        											ORDER BY ExamID;');
            $RSSearch->Execute();

            if ($RSSearch->Result->num_rows <= 0)
            {
                return $AllExamRegistrations;
            }

            while($SearchRow = $RSSearch->FetchRow())
            {
                $AllExamRegistrations[$SearchRow->examRegistrationID]['StudentRegistrationID'] = $SearchRow->studentRegistrationID;
                $AllExamRegistrations[$SearchRow->examRegistrationID]['FirstName'] = $SearchRow->firstName;
                $AllExamRegistrations[$SearchRow->examRegistrationID]['LastName'] = $SearchRow->lastName;
                $AllExamRegistrations[$SearchRow->examRegistrationID]['FatherName'] = $SearchRow->fatherName;
                $AllExamRegistrations[$SearchRow->examRegistrationID]['MotherName'] = $SearchRow->motherName;

                $AllExamRegistrations[$SearchRow->examRegistrationID]['ExamID'] = $SearchRow->examID;
                $AllExamRegistrations[$SearchRow->examRegistrationID]['ExamDate'] = $SearchRow->examDate;
                $AllExamRegistrations[$SearchRow->examRegistrationID]['ExamTime'] = $SearchRow->examTime;
                $AllExamRegistrations[$SearchRow->examRegistrationID]['ExamName'] = $SearchRow->examName;
                
                $AllExamRegistrations[$SearchRow->examRegistrationID]['RegistrationAmount'] = $SearchRow->registrationAmount;
                $AllExamRegistrations[$SearchRow->examRegistrationID]['ExamStatus'] = $SearchRow->examStatus;
                $AllExamRegistrations[$SearchRow->examRegistrationID]['IsAdmissionConfirmed'] = $SearchRow->isAdmissionConfirmed;

				$AllExamRegistrations[$SearchRow->examRegistrationID]['IsActive'] = $SearchRow->isActive;
                $AllExamRegistrations[$SearchRow->examRegistrationID]['CreateUserID'] = $SearchRow->createUserID;
                $AllExamRegistrations[$SearchRow->examRegistrationID]['CreateUserName'] = $SearchRow->createUserName;

                $AllExamRegistrations[$SearchRow->examRegistrationID]['CreateDate'] = $SearchRow->createDate;
           }
            
            return $AllExamRegistrations;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException::ExamRegistration::GetAllExamRegistrations(). Stack Trace: '. $e->getTraceAsString());
            return $AllExamRegistrations;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception: ExamRegistration::GetAllExamRegistrations() . Stack Trace: '. $e->getTraceAsString());
            return $AllExamRegistrations;
        }
    }

	static function SearchExamRegistrations(&$TotalRecords = 0, $GetTotalsOnly = false, $Filters = array(), $Start = 0, $Limit = 100)
	{
		$AllExamRegistrations = array();
		
		try
		{
			$DBConnObject = new DBConnect();

			$Conditions = array();

			if (count($Filters) > 0)
			{	
				if (count($Filters['ClassID']) > 0)
				{	
					$ClassIDs = implode(',', $Filters['ClassID']);
					$Conditions[] = 'aenq.classID IN (' . $DBConnObject->RealEscapeVariable($ClassIDs) . ')';
				}

				if (!empty($Filters['StudentName']))
				{
					$Conditions[] = '(aenq.firstName LIKE ' . $DBConnObject->RealEscapeVariable("%" . $Filters['StudentName'] . "%") . ' OR aenq.lastName LIKE ' . 
										$DBConnObject->RealEscapeVariable("%" . $Filters['StudentName'] . "%").")";
				}

				if (count($Filters['Genders']) > 0)
				{	
					$ORConditions = array();

					foreach ($Filters['Genders'] as $Genders) 
					{
						$ORConditions[] = 'aenq.gender = '.$DBConnObject->RealEscapeVariable($Genders);
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
					$Conditions[] = 'aenq.mobileNumber LIKE ' . $DBConnObject->RealEscapeVariable("%" . $Filters['MobileNumber'] . "%");
				}

				if (!empty($Filters['FatherName']))
				{
					$Conditions[] = 'aenq.fatherName LIKE ' . $DBConnObject->RealEscapeVariable("%" . $Filters['FatherName'] . "%");
				}

				if (!empty($Filters['MotherName']))
				{
					$Conditions[] = 'aenq.motherName LIKE ' . $DBConnObject->RealEscapeVariable("%" . $Filters['MotherName'] . "%");
				}

				if (!empty($Filters['CountryID']))
				{
					$Conditions[] = 'aenq.countryID = ' . $DBConnObject->RealEscapeVariable($Filters['CountryID']);
				}

				if (!empty($Filters['StateID']))
				{
					$Conditions[] = 'aenq.stateID = ' . $DBConnObject->RealEscapeVariable($Filters['StateID']);
				}

				if (!empty($Filters['DistrictID']))
				{
					$Conditions[] = 'aenq.districtID = ' . $DBConnObject->RealEscapeVariable($Filters['DistrictID']);
				}

				if (!empty($Filters['CityID']))
				{
					$Conditions[] = 'aenq.cityID = ' . $DBConnObject->RealEscapeVariable($Filters['CityID']);
				}

				if ($Filters['IsAdmissionConfirmed'] != '')
				{
					$Conditions[] = 'aer.isAdmissionConfirmed = ' . $DBConnObject->RealEscapeVariable($Filters['IsAdmissionConfirmed']);
				}

				if ($Filters['ExamStatus'] != '')
				{
					$Conditions[] = 'aer.examStatus = ' . $DBConnObject->RealEscapeVariable($Filters['ExamStatus']);
				}

				if ($Filters['MarksFrom'] != '')
				{
					$Conditions[] = 'aer.obtainedMarks >= ' . $DBConnObject->RealEscapeVariable($Filters['MarksFrom']);
				}

				if ($Filters['MarksTo'] != '')
				{
					$Conditions[] = 'aer.obtainedMarks <= ' . $DBConnObject->RealEscapeVariable($Filters['MarksTo']);
				}
				
				if($Filters['ActiveStatus'] == 1)
				{
					$Conditions[] = 'aer.isActive = 1';
				}
				else
				{
					$Conditions[] = 'aer.isActive = 0';
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
			 
				$RSTotal = $DBConnObject->Prepare('SELECT COUNT(*) AS totalRecords FROM aad_exam_registrations aer 
        											INNER JOIN aad_exams ae ON aer.examID = ae.examID
        											INNER JOIN aad_student_registrations asr ON aer.studentRegistrationID = asr.studentRegistrationID
        											INNER JOIN asa_classes ac ON asr.classID = ac.classID
													INNER JOIN users u ON aer.createUserID = u.userID
													' . $QueryString . ';');
				$RSTotal->Execute();
				
				$TotalRecords = $RSTotal->FetchRow()->totalRecords;
				return;
			}
			
			$RSAllExamRegistrations = $DBConnObject->Prepare('SELECT aer.*, ac.className, ae.examName, asr.firstName, asr.lastName, 
																asr.fatherName, asr.motherName, u.userName AS createUserName 
																FROM aad_exam_registrations aer 
			        											INNER JOIN aad_exams ae ON aer.examID = ae.examID
			        											INNER JOIN aad_student_registrations asr ON aer.studentRegistrationID = asr.studentRegistrationID
			        											INNER JOIN asa_classes ac ON asr.classID = ac.classID
																INNER JOIN users u ON aer.createUserID = u.userID 
																	' . $QueryString . ' ORDER BY asr.ClassID
																	LIMIT ' . (int) $Start . ', ' . (int) $Limit . ';');
			$RSAllExamRegistrations->Execute();

			if ($RSAllExamRegistrations->Result->num_rows <= 0)
            {
                return $AllExamRegistrations;
            }

            while($SearchRow = $RSAllExamRegistrations->FetchRow())
            {
                $AllExamRegistrations[$SearchRow->examRegistrationID]['StudentRegistrationID'] = $SearchRow->studentRegistrationID;
                $AllExamRegistrations[$SearchRow->examRegistrationID]['ClassName'] = $SearchRow->className;
                $AllExamRegistrations[$SearchRow->examRegistrationID]['FirstName'] = $SearchRow->firstName;
                $AllExamRegistrations[$SearchRow->examRegistrationID]['LastName'] = $SearchRow->lastName;
                $AllExamRegistrations[$SearchRow->examRegistrationID]['FatherName'] = $SearchRow->fatherName;
                $AllExamRegistrations[$SearchRow->examRegistrationID]['MotherName'] = $SearchRow->motherName;

                $AllExamRegistrations[$SearchRow->examRegistrationID]['ExamID'] = $SearchRow->examID;
                $AllExamRegistrations[$SearchRow->examRegistrationID]['ExamDate'] = $SearchRow->examDate;
                $AllExamRegistrations[$SearchRow->examRegistrationID]['ExamTime'] = $SearchRow->examTime;
                $AllExamRegistrations[$SearchRow->examRegistrationID]['ExamName'] = $SearchRow->examName;
                $AllExamRegistrations[$SearchRow->examRegistrationID]['ObtainedMarks'] = $SearchRow->obtainedMarks;
                
                $AllExamRegistrations[$SearchRow->examRegistrationID]['RegistrationAmount'] = $SearchRow->registrationAmount;
                $AllExamRegistrations[$SearchRow->examRegistrationID]['ExamStatus'] = $SearchRow->examStatus;
                $AllExamRegistrations[$SearchRow->examRegistrationID]['IsAdmissionConfirmed'] = $SearchRow->isAdmissionConfirmed;

				$AllExamRegistrations[$SearchRow->examRegistrationID]['IsActive'] = $SearchRow->isActive;
                $AllExamRegistrations[$SearchRow->examRegistrationID]['CreateUserID'] = $SearchRow->createUserID;
                $AllExamRegistrations[$SearchRow->examRegistrationID]['CreateUserName'] = $SearchRow->createUserName;

                $AllExamRegistrations[$SearchRow->examRegistrationID]['CreateDate'] = $SearchRow->createDate;
           }
		
			return $AllExamRegistrations;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at ExamRegistration::SearchExamRegistrations(). Stack Trace: ' . $e->getTraceAsString());
			return $AllExamRegistrations;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at ExamRegistration::SearchExamRegistrations(). Stack Trace: ' . $e->getTraceAsString());
			return $AllExamRegistrations;
		}		
	}

	static function GetStudentRegistrationsDetailsByStudentRegistrationID($StudentRegistrationID)
	{
		$StudentRegistrationsDetails = array();

		try
		{
			$DBConnObject = new DBConnect();

			$RSStudentRegistrationsDetails = $DBConnObject->Prepare('SELECT asr.classID, CONCAT(asr.firstName, " ", asr.lastName) AS studentName, asr.dob, 
																		asr.fatherName, asr.motherName FROM aad_exam_registrations aer 
																		INNER JOIN aad_student_registrations asr ON aer.studentRegistrationID = asr.studentRegistrationID
																		WHERE asr.studentRegistrationID = :|1;');
			$RSStudentRegistrationsDetails->Execute($StudentRegistrationID);

			if ($RSStudentRegistrationsDetails->Result->num_rows <= 0)
            {
                return $StudentRegistrationsDetails;
            }

            while($SearchRow = $RSStudentRegistrationsDetails->FetchRow())
            {
                $StudentRegistrationsDetails['ClassID'] = $SearchRow->classID;
                $StudentRegistrationsDetails['StudentName'] = $SearchRow->studentName;
                $StudentRegistrationsDetails['DOB'] = $SearchRow->dob;
                $StudentRegistrationsDetails['FatherName'] = $SearchRow->fatherName;
                $StudentRegistrationsDetails['MotherName'] = $SearchRow->motherName;
            }

            return $StudentRegistrationsDetails;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at ExamRegistration::GetActiveExamRegistrations(). Stack Trace: ' . $e->getTraceAsString());
			return $StudentRegistrationsDetails;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at ExamRegistration::GetActiveExamRegistrations(). Stack Trace: ' . $e->getTraceAsString());
			return $StudentRegistrationsDetails;
		}
	}

    static function GetActiveExamRegistrations()
	{
		$AllExamRegistrations = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT examRegistrationID, ExamID FROM aad_exam_registrations WHERE isActive = 1;');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllExamRegistrations;
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$AllExamRegistrations[$SearchRow->examRegistrationID] = $SearchRow->ExamID;
			}
			
			return $AllExamRegistrations;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at ExamRegistration::GetActiveExamRegistrations(). Stack Trace: ' . $e->getTraceAsString());
			return $AllExamRegistrations;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at ExamRegistration::GetActiveExamRegistrations(). Stack Trace: ' . $e->getTraceAsString());
			return $AllExamRegistrations;
		}		
	}
    
	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//
	private function SaveDetails()
	{
		if ($this->ExamRegistrationID == 0)
		{
			$RSSave = $this->DBObject->Prepare('INSERT INTO aad_exam_registrations (studentRegistrationID, examID, examDate, examTime, registrationAmount, 
																						isActive, createUserID, createDate)
																VALUES (:|1, :|2, :|3, :|4, :|5, :|6, :|7, NOW());');
			$RSSave->Execute($this->StudentRegistrationID, $this->ExamID, $this->ExamDate, $this->ExamTime, $this->RegistrationAmount, $this->IsActive, $this->CreateUserID);

			$this->ExamRegistrationID = $RSSave->LastID;
		}
		else
		{
			$RSUpdate = $this->DBObject->Prepare('UPDATE aad_exam_registrations
													SET	studentRegistrationID = :|1,
														examID = :|2,
														examDate = :|3,
														examTime = :|4,
														registrationAmount = :|5,
														isActive = :|6
													WHERE examRegistrationID = :|7 LIMIT 1;');
			$RSUpdate->Execute($this->StudentRegistrationID, $this->ExamID, $this->ExamDate, $this->ExamTime, $this->RegistrationAmount, $this->IsActive, $this->ExamRegistrationID);
		}
		
		return true;
	}

	private function RemoveExamRegistration()
	{
		if(!isset($this->ExamRegistrationID)) 
		{
    		throw new ApplicationARException('', APP_AR_ERROR_DELETE_WITHOUT_ID);
		}
		
		$RSDeleteExamRegistration = $this->DBObject->Prepare('DELETE FROM aad_exam_registrations WHERE examRegistrationID = :|1 LIMIT 1;');
		$RSDeleteExamRegistration->Execute($this->ExamRegistrationID);		
	}
	
	private function GetExamRegistrationByID()
	{
		$RSExamRegistration = $this->DBObject->Prepare('SELECT * FROM aad_exam_registrations WHERE examRegistrationID = :|1 LIMIT 1;');
		$RSExamRegistration->Execute($this->ExamRegistrationID);
		
		$ExamRegistrationRow = $RSExamRegistration->FetchRow();
		
		$this->SetAttributesFromDB($ExamRegistrationRow);				
	}
	
	private function SetAttributesFromDB($ExamRegistrationRow)
	{
		$this->ExamRegistrationID = $ExamRegistrationRow->examRegistrationID;
		$this->StudentRegistrationID = $ExamRegistrationRow->studentRegistrationID;
		$this->ExamID = $ExamRegistrationRow->examID;
		$this->ExamDate = $ExamRegistrationRow->examDate;
		$this->ExamTime = $ExamRegistrationRow->examTime;
		$this->RegistrationAmount = $ExamRegistrationRow->registrationAmount;

		$this->ExamStatus = $ExamRegistrationRow->examStatus;
		$this->ObtainedMarks = $ExamRegistrationRow->obtainedMarks;
		$this->IsAdmissionConfirmed = $ExamRegistrationRow->isAdmissionConfirmed;

		$this->IsActive = $ExamRegistrationRow->isActive;
		$this->CreateUserID = $ExamRegistrationRow->createUserID;
		$this->CreateDate = $ExamRegistrationRow->createDate;
	}	
}
?>
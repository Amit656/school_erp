<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.db_connect.php');
error_reporting(E_ALL);

class Enquiry
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	
	private $EnquiryID;
	private $ClassID;

	private $FirstName;
	private $LastName;
	private $DOB;
	private $Gender;

	private $FatherName;
	private $MotherName;
	private $MobileNumber;
	private $Address;

	private $CityID;
	private $DistrictID;
	private $StateID;
	private $CountryID;
	private $PinCode;
	
	private $LastSchool;
	private $LastClass;
	private $LastClassStatus;
	
	private $SourceOfInformation;
	private $Description;
	private $IsAdmissionTaken;
	private $FormFee;
	private $AcademicYearID;

	private $IsActive;
	private $CreateUserID;
	private $CreateDate;
	
	// PUBLIC METHODS START HERE	//
	public function __construct($EnquiryID = 0)
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;
		
		if($EnquiryID != 0)
		{
			$this->EnquiryID = $EnquiryID;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetEnquiryByID();
		}
		else
		{
			//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
			$this->EnquiryID = 0;
			$this->ClassID = 0;

			$this->FirstName = '';
			$this->LastName = '';
			$this->DOB = '0000-00-00';
			$this->Gender = 'Male';

			$this->FatherName = '';
			$this->MotherName = '';
			$this->MobileNumber = '';
			$this->Address = '';

			$this->CityID = 0;
			$this->DistrictID = 0;
			$this->StateID = 0;
			$this->CountryID = 0;
			$this->PinCode = 0;
			
			$this->LastSchool = '';
			$this->LastClass = '';
			$this->LastClassStatus = 'Passed';
			
			$this->SourceOfInformation = 'Website';
			$this->Description = '';
			$this->IsAdmissionTaken = 0;
			$this->FormFee = 0.00;
			$this->AcademicYearID = 0;

			$this->IsActive = 0;
			$this->CreateUserID = 0;
			$this->CreateDate = '0000-00-00 00:00:00';
		}
	}
	
	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetEnquiryID()
	{
		return $this->EnquiryID;
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
	
	public function GetMobileNumber()
	{
		return $this->MobileNumber;
	}
	public function SetMobileNumber($MobileNumber)
	{
		$this->MobileNumber = $MobileNumber;
	}
	
	public function GetAddress()
	{
		return $this->Address;
	}
	public function SetAddress($Address)
	{
		$this->Address = $Address;
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
	
	public function GetLastSchool()
	{
		return $this->LastSchool;
	}
	public function SetLastSchool($LastSchool)
	{
		$this->LastSchool = $LastSchool;
	}
	
	public function GetLastClass()
	{
		return $this->LastClass;
	}
	public function SetLastClass($LastClass)
	{
		$this->LastClass = $LastClass;
	}	
	
	public function GetLastClassStatus()
	{
		return $this->LastClassStatus;
	}
	public function SetLastClassStatus($LastClassStatus)
	{
		$this->LastClassStatus = $LastClassStatus;
	}
	
	public function GetSourceOfInformation()
	{
		return $this->SourceOfInformation;
	}
	public function SetSourceOfInformation($SourceOfInformation)
	{
		$this->SourceOfInformation = $SourceOfInformation;
	}
	
	public function GetDescription()
	{
		return $this->Description;
	}
	public function SetDescription($Description)
	{
		$this->Description = $Description;
	}

	public function GetIsAdmissionTaken()
	{
		return $this->IsAdmissionTaken;
	}
	public function SetIsAdmissionTaken($IsAdmissionTaken)
	{
		$this->IsAdmissionTaken = $IsAdmissionTaken;
	}
	
	public function GetFormFee()
	{
		return $this->FormFee;
	}
	public function SetFormFee($FormFee)
	{
		$this->FormFee = $FormFee;
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
			$this->RemoveEnquiry();
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
			$RSTotal = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM aad_exam_registrations WHERE enquiryID = :|1;');
			$RSTotal->Execute($this->EnquiryID);
	
			if ($RSTotal->FetchRow()->totalRecords > 0) 
			{
				return true;
			}
			
			return false;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException: Enquiry::CheckDependencies(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception: Enquiry::CheckDependencies(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
	}

	public function RecordExists()
	{
		try
		{
			$QueryString = '';
			
			if ($this->EnquiryID > 0)
			{
				$QueryString = ' AND enquiryID != ' . $this->DBObject->RealEscapeVariable($this->EnquiryID);
			}

			$RSTotal = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM aad_enquiries WHERE ClassID = :|1' . $QueryString . ';');
			$RSTotal->Execute($this->ClassID);
			
			if ($RSTotal->FetchRow()->totalRecords > 0)
			{
				return true;
			}
			
			return false;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at Enquiry::RecordExists(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at Enquiry::RecordExists(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
	}

	// END OF PUBLIC METHODS	//
	
	// START OF STATIC METHODS	//
	static function GetAllEnquiries()
    { 
		$AllEnquiries = array();
		
    	try
        {	
        	$DBConnObject = new DBConnect();
        	$RSSearch = $DBConnObject->Prepare('SELECT ae.*, ac.className, u.userName AS createUserName 
												FROM aad_enquiries ae
												INNER JOIN asa_classes ac ON ae.classID = ac.classID 
												INNER JOIN users u ON ae.createUserID = u.userID 
												ORDER BY ae.ClassID;');
            $RSSearch->Execute();

            if ($RSSearch->Result->num_rows <= 0)
            {
                return $AllEnquiries;
            }

            while($SearchRow = $RSSearch->FetchRow())
            {
                $AllEnquiries[$SearchRow->enquiryID]['ClassName'] = $SearchRow->className;
                $AllEnquiries[$SearchRow->enquiryID]['FirstName'] = $SearchRow->firstName;
                $AllEnquiries[$SearchRow->enquiryID]['LastName'] = $SearchRow->lastName;
                $AllEnquiries[$SearchRow->enquiryID]['DOB'] = $SearchRow->dob;
                $AllEnquiries[$SearchRow->enquiryID]['Gender'] = $SearchRow->gender;
                $AllEnquiries[$SearchRow->enquiryID]['FatherName'] = $SearchRow->fatherName;
                $AllEnquiries[$SearchRow->enquiryID]['MotherName'] = $SearchRow->motherName;
                $AllEnquiries[$SearchRow->enquiryID]['MobileNumber'] = $SearchRow->mobileNumber;

				$AllEnquiries[$SearchRow->enquiryID]['IsActive'] = $SearchRow->isActive;
                $AllEnquiries[$SearchRow->enquiryID]['CreateUserID'] = $SearchRow->createUserID;
                $AllEnquiries[$SearchRow->enquiryID]['CreateUserName'] = $SearchRow->createUserName;

                $AllEnquiries[$SearchRow->enquiryID]['CreateDate'] = $SearchRow->createDate;
           }
            
            return $AllEnquiries;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException::Enquiry::GetAllEnquiries(). Stack Trace: '. $e->getTraceAsString());
            return $AllEnquiries;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception: Enquiry::GetAllEnquiries() . Stack Trace: '. $e->getTraceAsString());
            return $AllEnquiries;
        }
    }

    static function GetActiveEnquiries()
	{
		$AllEnquiries = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT enquiryID, CONCAT(firstName, " ", lastName) AS studentName FROM aad_enquiries WHERE isAdmissionTaken = 0 AND isActive = 1;');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllEnquiries;
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$AllEnquiries[$SearchRow->enquiryID] = $SearchRow->studentName;
			}
			
			return $AllEnquiries;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at Enquiry::GetActiveEnquiries(). Stack Trace: ' . $e->getTraceAsString());
			return $AllEnquiries;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at Enquiry::GetActiveEnquiries(). Stack Trace: ' . $e->getTraceAsString());
			return $AllEnquiries;
		}		
	}

	static function SearchEnquiries(&$TotalRecords = 0, $GetTotalsOnly = false, $Filters = array(), $Start = 0, $Limit = 100)
	{
		$AllEnquiries = array();
		
		try
		{
			$DBConnObject = new DBConnect();

			$Conditions = array();

			if (count($Filters) > 0)
			{	
				if (count($Filters['ClassID']) > 0)
				{	
					$ClassIDs = implode(',', $Filters['ClassID']);
					$Conditions[] = 'ae.classID IN (' . $DBConnObject->RealEscapeVariable($ClassIDs) . ')';
				}

				if (!empty($Filters['StudentName']))
				{
					$Conditions[] = '(ae.firstName LIKE ' . $DBConnObject->RealEscapeVariable("%" . $Filters['StudentName'] . "%") . ' OR ae.lastName LIKE ' . 
										$DBConnObject->RealEscapeVariable("%" . $Filters['StudentName'] . "%").")";
				}

				if (count($Filters['Genders']) > 0)
				{	
					$ORConditions = array();

					foreach ($Filters['Genders'] as $Gender) 
					{
						$ORConditions[] = 'ae.gender = ' . $DBConnObject->RealEscapeVariable($Gender);
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
					$Conditions[] = 'ae.mobileNumber LIKE ' . $DBConnObject->RealEscapeVariable("%" . $Filters['MobileNumber'] . "%");
				}

				if (!empty($Filters['FatherName']))
				{
					$Conditions[] = 'ae.fatherName LIKE ' . $DBConnObject->RealEscapeVariable("%" . $Filters['FatherName'] . "%");
				}

				if (!empty($Filters['MotherName']))
				{
					$Conditions[] = 'ae.motherName LIKE ' . $DBConnObject->RealEscapeVariable("%" . $Filters['MotherName'] . "%");
				}

				if (!empty($Filters['CountryID']))
				{
					$Conditions[] = 'ae.countryID = ' . $DBConnObject->RealEscapeVariable($Filters['CountryID']);
				}

				if (!empty($Filters['StateID']))
				{
					$Conditions[] = 'ae.stateID = ' . $DBConnObject->RealEscapeVariable($Filters['StateID']);
				}

				if (!empty($Filters['DistrictID']))
				{
					$Conditions[] = 'ae.districtID = ' . $DBConnObject->RealEscapeVariable($Filters['DistrictID']);
				}

				if (!empty($Filters['CityID']))
				{
					$Conditions[] = 'ae.cityID = ' . $DBConnObject->RealEscapeVariable($Filters['CityID']);
				}
				
				if($Filters['ActiveStatus'] == 1)
				{
					$Conditions[] = 'ae.isActive = 1';
				}
				else
				{
					$Conditions[] = 'ae.isActive = 0';
				}
				
				$Conditions[] = 'ae.enquiryID NOT IN (SELECT enquiryID FROM aad_student_registrations)';
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
													FROM aad_enquiries ae
													INNER JOIN asa_classes ac ON ae.classID = ac.classID 
													INNER JOIN users u ON ae.createUserID = u.userID ' 
													. $QueryString . ';');
				$RSTotal->Execute();
				
				$TotalRecords = $RSTotal->FetchRow()->totalRecords;
				return;
			}
			
			$RSAllEnquiries = $DBConnObject->Prepare('SELECT ae.*, ac.className, u.userName AS createUserName 
														FROM aad_enquiries ae
														INNER JOIN asa_classes ac ON ae.classID = ac.classID 
														INNER JOIN users u ON ae.createUserID = u.userID 
														' . $QueryString . ' ORDER BY ae.ClassID
														LIMIT ' . (int) $Start . ', ' . (int) $Limit . ';');
			$RSAllEnquiries->Execute();

			if ($RSAllEnquiries->Result->num_rows <= 0)
            {
                return $AllEnquiries;
            }

            while($SearchRow = $RSAllEnquiries->FetchRow())
            {
                $AllEnquiries[$SearchRow->enquiryID]['ClassName'] = $SearchRow->className;
                $AllEnquiries[$SearchRow->enquiryID]['FirstName'] = $SearchRow->firstName;
                $AllEnquiries[$SearchRow->enquiryID]['LastName'] = $SearchRow->lastName;

                $AllEnquiries[$SearchRow->enquiryID]['DOB'] = $SearchRow->dob;
                $AllEnquiries[$SearchRow->enquiryID]['Gender'] = $SearchRow->gender;
                
                $AllEnquiries[$SearchRow->enquiryID]['FatherName'] = $SearchRow->fatherName;
                $AllEnquiries[$SearchRow->enquiryID]['MotherName'] = $SearchRow->motherName;
                $AllEnquiries[$SearchRow->enquiryID]['MobileNumber'] = $SearchRow->mobileNumber;

                $AllEnquiries[$SearchRow->enquiryID]['IsAdmissionTaken'] = $SearchRow->isAdmissionTaken;
                $AllEnquiries[$SearchRow->enquiryID]['FormFee'] = $SearchRow->formFee;
                $AllEnquiries[$SearchRow->enquiryID]['AcademicYearID'] = $SearchRow->academicYearID;

				$AllEnquiries[$SearchRow->enquiryID]['IsActive'] = $SearchRow->isActive;
                $AllEnquiries[$SearchRow->enquiryID]['CreateUserID'] = $SearchRow->createUserID;
                $AllEnquiries[$SearchRow->enquiryID]['CreateUserName'] = $SearchRow->createUserName;

                $AllEnquiries[$SearchRow->enquiryID]['CreateDate'] = $SearchRow->createDate;
            }
		
			return $AllEnquiries;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at Enquiry::SearchEnquiries(). Stack Trace: ' . $e->getTraceAsString());
			return $AllEnquiries;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at Enquiry::SearchEnquiries(). Stack Trace: ' . $e->getTraceAsString());
			return $AllEnquiries;
		}		
	}
    
	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//
	private function SaveDetails()
	{
		if ($this->EnquiryID == 0)
		{
			$RSSave = $this->DBObject->Prepare('INSERT INTO aad_enquiries (ClassID, firstName, lastName, dob, gender, 
																					fatherName, motherName, mobileNumber, address,
																					cityID, districtID, stateID, countryID, pinCode, 
																					lastSchool, lastClass, lastClassStatus, sourceOfInformation, 
																					description, isAdmissionTaken, formFee,  
																					academicYearID, isActive, createUserID, createDate)
													VALUES (:|1, :|2, :|3, :|4, :|5, :|6, :|7, :|8, :|9, :|10, :|11, :|12, :|13, :|14, :|15, :|16, :|17, :|18, :|19, :|20, :|21, :|22, :|23, :|24, NOW());');
			$RSSave->Execute($this->ClassID, $this->FirstName, $this->LastName, $this->DOB, $this->Gender, 
							 $this->FatherName, $this->MotherName, $this->MobileNumber, $this->Address, 
						 	 $this->CityID, $this->DistrictID, $this->StateID, $this->CountryID, $this->PinCode, 
					 		 $this->LastSchool, $this->LastClass, $this->LastClassStatus, $this->SourceOfInformation, 
					 		 $this->Description, $this->IsAdmissionTaken, $this->FormFee, $this->AcademicYearID, 
					 		 $this->IsActive, $this->CreateUserID);

			$this->EnquiryID = $RSSave->LastID;
		}
		else
		{
			$RSUpdate = $this->DBObject->Prepare('UPDATE aad_enquiries
													SET	ClassID = :|1,
														firstName = :|2,
														lastName = :|3,
														dob = :|4,
														gender = :|5,
														fatherName = :|6,
														motherName = :|7,
														mobileNumber = :|8,
														address = :|9,
														cityID = :|10,
														districtID = :|11,
														stateID = :|12,
														countryID = :|13,
														pinCode = :|14,
														lastSchool = :|15,
														lastClass = :|16,
														lastClassStatus = :|17,
														sourceOfInformation = :|18,
														description = :|19,
														isAdmissionTaken = :|20,
														formFee = :|21,
														academicYearID = :|22,
														isActive = :|23
													WHERE enquiryID = :|24 LIMIT 1;');
			$RSUpdate->Execute($this->ClassID, $this->FirstName, $this->LastName, $this->DOB, $this->Gender, 
							   $this->FatherName, $this->MotherName, $this->MobileNumber, $this->Address, 
							   $this->CityID, $this->DistrictID, $this->StateID, $this->CountryID, $this->PinCode, 
						 	   $this->LastSchool, $this->LastClass, $this->LastClassStatus, $this->SourceOfInformation, 
						 	   $this->Description, $this->IsAdmissionTaken, $this->FormFee, $this->AcademicYearID, 
						 	   $this->IsActive, $this->EnquiryID);
		}
		
		return true;
	}

	private function RemoveEnquiry()
	{
		if(!isset($this->EnquiryID)) 
		{
    		throw new ApplicationARException('', APP_AR_ERROR_DELETE_WITHOUT_ID);
		}
		
		$RSDeleteEnquiry = $this->DBObject->Prepare('DELETE FROM aad_enquiries WHERE enquiryID = :|1 LIMIT 1;');
		$RSDeleteEnquiry->Execute($this->EnquiryID);
	}
	
	private function GetEnquiryByID()
	{
		$RSEnquiry = $this->DBObject->Prepare('SELECT * FROM aad_enquiries WHERE enquiryID = :|1 LIMIT 1;');
		$RSEnquiry->Execute($this->EnquiryID);
		
		$EnquiryRow = $RSEnquiry->FetchRow();
		
		$this->SetAttributesFromDB($EnquiryRow);				
	}
	
	private function SetAttributesFromDB($EnquiryRow)
	{
		$this->EnquiryID = $EnquiryRow->enquiryID;
		$this->ClassID = $EnquiryRow->classID;

		$this->FirstName = $EnquiryRow->firstName;
		$this->LastName = $EnquiryRow->lastName;
		$this->DOB = $EnquiryRow->dob;
		$this->Gender = $EnquiryRow->gender;

		$this->FatherName = $EnquiryRow->fatherName;
		$this->MotherName = $EnquiryRow->motherName;
		$this->MobileNumber = $EnquiryRow->mobileNumber;
		$this->Address = $EnquiryRow->address;

		$this->CityID = $EnquiryRow->cityID;
		$this->DistrictID = $EnquiryRow->districtID;
		$this->StateID = $EnquiryRow->stateID;
		$this->CountryID = $EnquiryRow->countryID;
		$this->PinCode = $EnquiryRow->pinCode;
		
		$this->LastSchool = $EnquiryRow->lastSchool;
		$this->LastClass = $EnquiryRow->lastClass;
		$this->LastClassStatus = $EnquiryRow->lastClassStatus;
		
		$this->SourceOfInformation = $EnquiryRow->sourceOfInformation;
		$this->Description = $EnquiryRow->description;
		$this->IsAdmissionTaken = $EnquiryRow->isAdmissionTaken;
		$this->FormFee = $EnquiryRow->formFee;
		$this->AcademicYearID = $EnquiryRow->academicYearID;

		$this->IsActive = $EnquiryRow->isActive;
		$this->CreateUserID = $EnquiryRow->createUserID;
		$this->CreateDate = $EnquiryRow->createDate;
	}	
}
?>
<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.db_connect.php');
error_reporting(E_ALL);

class Student
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	protected $LastErrorCode;
	protected $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	
	protected $StudentID;
	protected $StudentRegistrationID;
	protected $ParentID;
	protected $ClassSectionID;
	protected $ColourHouseID;

	protected $UserName;
	protected $EnrollmentID;
	protected $AdmissionDate;
	protected $RollNumber;
	protected $AadharNumber;
	protected $StudentType;
	protected $Status;
	protected $AcademicYearID;

	protected $CreateUserID;
	protected $CreateDate;

	//Additional variable
	protected $ClassFeeGroupID;
	protected $StudentFeeGroupID;

	// PUBLIC METHODS START HERE	//
	public function __construct($StudentID = 0)
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;
		
		if($StudentID != 0)
		{
			$this->StudentID = $StudentID;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetStudentByID();
		}
		else
		{
			//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
			$this->StudentID = 0;
			$this->StudentRegistrationID = 0;
			$this->ParentID = 0;
			$this->ClassSectionID = 0;
			$this->ColourHouseID = 0;

			$this->UserName = '';
			$this->EnrollmentID = '';
			$this->AdmissionDate = '0000-00-00';
			$this->RollNumber = 0;
			$this->AadharNumber = 0;
			$this->StudentType = '';
			$this->Status = '';
			$this->AcademicYearID = '';
			
			$this->CreateUserID = 0;
			$this->CreateDate = '0000-00-00 00:00:00';

			$this->ClassFeeGroupID = 0;
			$this->StudentFeeGroupID = 0;
		}
	}
	
	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetStudentID()
	{
		return $this->StudentID;
	}
	
	public function GetParentID()
	{
		return $this->ParentID;
	}
	public function SetParentID($ParentID)
	{
		$this->ParentID = $ParentID;
	}
	
	public function GetStudentRegistrationID()
	{
		return $this->StudentRegistrationID;
	}
	public function SetStudentRegistrationID($StudentRegistrationID)
	{
		$this->StudentRegistrationID = $StudentRegistrationID;
	}

	public function GetClassSectionID()
	{
		return $this->ClassSectionID;
	}
	public function SetClassSectionID($ClassSectionID)
	{
		$this->ClassSectionID = $ClassSectionID;
	}

	public function GetColourHouseID()
	{
		return $this->ColourHouseID;
	}
	public function SetColourHouseID($ColourHouseID)
	{
		$this->ColourHouseID = $ColourHouseID;
	}

	public function GetUserName()
	{
		return $this->UserName;
	}
	public function SetUserName($UserName)
	{
		$this->UserName = $UserName;
	}

	public function GetEnrollmentID()
	{
		return $this->EnrollmentID;
	}
	public function SetEnrollmentID($EnrollmentID)
	{
		$this->EnrollmentID = $EnrollmentID;
	}

	public function GetAdmissionDate()
	{
		return $this->AdmissionDate;
	}
	public function SetAdmissionDate($AdmissionDate)
	{
		$this->AdmissionDate = $AdmissionDate;
	}

	public function GetRollNumber()
	{
		return $this->RollNumber;
	}
	public function SetRollNumber($RollNumber)
	{
		$this->RollNumber = $RollNumber;
	}

	public function GetAadharNumber()
	{
		return $this->AadharNumber;
	}
	public function SetAadharNumber($AadharNumber)
	{
		$this->AadharNumber = $AadharNumber;
	}

	public function GetStudentType()
	{
		return $this->StudentType;
	}
	public function SetStudentType($StudentType)
	{
		$this->StudentType = $StudentType;
	}

	public function GetStatus()
	{
		return $this->Status;
	}
	public function SetStatus($Status)
	{
		$this->Status = $Status;
	}
	
	public function GetAcademicYearID()
	{
		return $this->AcademicYearID;
	}
	public function SetAcademicYearID($AcademicYearID)
	{
		$this->AcademicYearID = $AcademicYearID;
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

	public function GetClassFeeGroupID()
	{
		return $this->ClassFeeGroupID;
	}

	public function GetStudentFeeGroupID()
	{
		return $this->StudentFeeGroupID;
	}
	public function SetStudentFeeGroupID($StudentFeeGroupID)
	{
		$this->StudentFeeGroupID = $StudentFeeGroupID;
	}
	
	public function GetLastErrorCode()
	{
		return $this->LastErrorCode;
	}
	
	//  END OF GETTER AND SETTER FUNCTIONS 	//
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
            $RSStudentINStudentDetailsCount = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM asa_student_details WHERE studentID = :|1;');
            $RSStudentINStudentDetailsCount->Execute($this->StudentID);

            $StudentINStudentDetailsCountRow = $RSStudentINStudentDetailsCount->FetchRow();

            if ($StudentINStudentDetailsCountRow->totalRecords > 0) 
            {
                return true;
            }

            $RSStudentINStudentSubjectsCount = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM asa_student_subjects WHERE studentID = :|1;');
            $RSStudentINStudentSubjectsCount->Execute($this->StudentID);

            $StudentINStudentSubjectsCountRow = $RSStudentINStudentSubjectsCount->FetchRow();

            if ($StudentINStudentSubjectsCountRow->totalRecords > 0) 
            {
                return true;
            }

            $RSStudentINStudentYearlyDetailsCount = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM asa_student_yearly_details WHERE studentID = :|1;');
            $RSStudentINStudentYearlyDetailsCount->Execute($this->StudentID);

            $StudentINStudentYearlyDetailsCountRow = $RSStudentINStudentYearlyDetailsCount->FetchRow();

            if ($StudentINStudentYearlyDetailsCountRow->totalRecords > 0) 
            {
                return true;
            }
            return false;
        }
        catch (ApplicationDBException $e)
        {
            error_log("DEBUG: ApplicationDBException: Student::CheckDependencies");
            return false;
        }
        catch (Exception $e)
        {
            error_log("DEBUG: Exception: Student::CheckDependencies");
            return false;
        }       
    }
			
	public function GetStudentWiseFeeGroupID()
    {
		try
        {	
        	$RSSearchStudentFeeGroupID = $this->DBObject->Prepare('SELECT fg.feeGroupID FROM afm_fee_group_assigned_records fgar 
					        										INNER JOIN afm_fee_groups fg ON fg.feeGroupID = fgar.feeGroupID
					        										WHERE fgar.recordID = :|1;');
			$RSSearchStudentFeeGroupID->Execute($this->StudentID);

			if($RSSearchStudentFeeGroupID->Result->num_rows  > 0)
			{
				while($SearchRow = $RSSearchStudentFeeGroupID->FetchRow())
				{
					$this->StudentFeeGroupID = $SearchRow->feeGroupID;
				}
			}
			return $this->StudentFeeGroupID;   
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException: Student::GetStudentWiseFeeGroupID(). Stack Trace: ' . $e->getTraceAsString());
            return false;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception: Student::GetStudentWiseFeeGroupID(). Stack Trace: ' . $e->getTraceAsString());
            return false;
        }
    }

    public function GetStudentMonthlyFeeDetails($MonthID, &$StudentMonthlyFeeDetails = array(), $AcademicYearID = 0, $IsRefund = false)
    {
    	$TotalMonthlyFeeAmount = 0;

		try
        {	
            if ($AcademicYearID == 0) 
        	{
        		$AcademicYearID = $this->AcademicYearID;
        	}
        	
        	$RSSearchStudentMonthlyFeeDetails = $this->DBObject->Prepare('SELECT sfs.studentFeeStructureID, sfs.studentID, sfs.amountPayable, SUM(fcd.amountPaid) AS amountPaid, fh.feeHead, fd.discountType AS firstDiscountType, fd.discountValue AS firstDiscountValue, fd.waveOffAmount AS firstWaveOffAmount, fd.concessionAmount AS firstConcessionAmount, fd1.discountType AS secondDiscountType, fd1.discountValue AS secondDiscountValue, fd1.waveOffAmount AS secondWaveOffAmount, fd1.concessionAmount AS secondConcessionAmount 
	        															FROM afm_student_fee_structure sfs
	        															INNER JOIN afm_fee_structure_details fsd On fsd.feeStructureDetailID = sfs.feeStructureDetailID
	        															INNER JOIN afm_fee_heads fh ON fh.feeHeadID = fsd.feeHeadID
	        															INNER JOIN afm_fee_structure fs ON fs.feeStructureID = fsd.feeStructureID
	        															INNER JOIN asa_academic_years ay ON ay.academicYearID = fs.academicYearID
	        															LEFT JOIN afm_fee_collection_details fcd On fcd.studentFeeStructureID = sfs.studentFeeStructureID
	        															LEFT JOIN afm_fee_discounts fd ON fd.feeGroupID = fs.feeGroupID AND fd.feeStructureDetailID = fsd.feeStructureDetailID AND fd.feeDiscountType = \'Group\' 
							 											LEFT JOIN afm_fee_discounts fd1 ON fd1.studentID = sfs.studentID AND fd1.feeStructureDetailID = fsd.feeStructureDetailID AND fd1.feeDiscountType = \'Student\' 
	        															WHERE sfs.studentID = :|1 AND fsd.academicYearMonthID = :|2 AND ay.academicYearID = :|3 AND sfs.studentFeeStructureID NOT IN (SELECT studentFeeStructureID FROM afm_refund_fee_details)
	        															GROUP BY sfs.studentFeeStructureID 
	        															ORDER By fh.priority;');

			$RSSearchStudentMonthlyFeeDetails->Execute($this->StudentID, $MonthID, $AcademicYearID);

			if($RSSearchStudentMonthlyFeeDetails->Result->num_rows <= 0)
			{
				return $TotalMonthlyFeeAmount;
			}

			while($SearchRow = $RSSearchStudentMonthlyFeeDetails->FetchRow())
			{
				$FeeAmount = 0; 
				$FeeHeadDiscountAmount = 0;
				$FirstDiscountAmount = 0;
				$SecondDiscountAmount = 0;

				$FeeAmount = $SearchRow->amountPayable;

				if ($SearchRow->firstDiscountType == 'Percentage') 
				{
					$FirstDiscountAmount = ($FeeAmount * $SearchRow->firstDiscountValue) / 100;
				}
				else if ($SearchRow->firstDiscountType == 'Absolute')
				{
					$FirstDiscountAmount = $SearchRow->firstDiscountValue;
				}

				if ($SearchRow->secondDiscountType == 'Percentage') 
				{
					$SecondDiscountAmount = ($FeeAmount * $SearchRow->secondDiscountValue) / 100;
				}
				else if ($SearchRow->secondDiscountType == 'Absolute')
				{
					$SecondDiscountAmount = $SearchRow->secondDiscountValue;
				}

				if ($SecondDiscountAmount > 0) 
				{
					$FeeHeadDiscountAmount = $SecondDiscountAmount;

					$StudentMonthlyFeeDetails[$SearchRow->studentFeeStructureID]['DiscountType'] = $SearchRow->secondDiscountType;
					$StudentMonthlyFeeDetails[$SearchRow->studentFeeStructureID]['DiscountValue'] = $SearchRow->secondDiscountValue;
					$StudentMonthlyFeeDetails[$SearchRow->studentFeeStructureID]['DiscountAmount'] = $SecondDiscountAmount;
				}
				else
				{
					$FeeHeadDiscountAmount = $FirstDiscountAmount;

					$StudentMonthlyFeeDetails[$SearchRow->studentFeeStructureID]['DiscountType'] = $SearchRow->firstDiscountType;
					$StudentMonthlyFeeDetails[$SearchRow->studentFeeStructureID]['DiscountValue'] = $SearchRow->firstDiscountValue;
					$StudentMonthlyFeeDetails[$SearchRow->studentFeeStructureID]['DiscountAmount'] = $FirstDiscountAmount;
				}
				
				if ($IsRefund) 
				{
				    $StudentMonthlyFeeDetails[$SearchRow->studentFeeStructureID]['FeeHead'] = $SearchRow->feeHead;
					$StudentMonthlyFeeDetails[$SearchRow->studentFeeStructureID]['FeeAmount'] = $FeeAmount;
					$StudentMonthlyFeeDetails[$SearchRow->studentFeeStructureID]['AmountPaid'] = $SearchRow->amountPaid;

					$TotalMonthlyFeeAmount += $FeeAmount - $FeeHeadDiscountAmount - $SearchRow->amountPaid;	
			
					$StudentMonthlyFeeDetails[$SearchRow->studentFeeStructureID]['TotalMonthlyFeeAmount'] = $TotalMonthlyFeeAmount;
				}
				else
				{
				    if ($SearchRow->secondWaveOffAmount > 0 || $SearchRow->firstWaveOffAmount > 0) 
    				{
    					unset($StudentMonthlyFeeDetails[$SearchRow->studentFeeStructureID]);
    				}
    				else if (($FeeAmount - $FeeHeadDiscountAmount) == $SearchRow->amountPaid) 
    				{
    					unset($StudentMonthlyFeeDetails[$SearchRow->studentFeeStructureID]);
    				}
    				else
    				{
    					$StudentMonthlyFeeDetails[$SearchRow->studentFeeStructureID]['FeeHead'] = $SearchRow->feeHead;
    					$StudentMonthlyFeeDetails[$SearchRow->studentFeeStructureID]['FeeAmount'] = $FeeAmount;
    					$StudentMonthlyFeeDetails[$SearchRow->studentFeeStructureID]['AmountPaid'] = $SearchRow->amountPaid;
    
    					// $FeeHeadAmount += $FeeAmount - $FeeHeadDiscountAmount - $SearchRow->amountPaid;	
    			
    					// $StudentMonthlyFeeDetails[$SearchRow->studentFeeStructureID]['FeeHeadAmount'] = $FeeHeadAmount;	
    					$TotalMonthlyFeeAmount += $FeeAmount - $FeeHeadDiscountAmount - $SearchRow->amountPaid;	
    			
    					$StudentMonthlyFeeDetails[$SearchRow->studentFeeStructureID]['TotalMonthlyFeeAmount'] = $TotalMonthlyFeeAmount;
    				}
				}
			}

			return $TotalMonthlyFeeAmount;   
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException: Student::GetStudentMonthlyFeeDetails(). Stack Trace: ' . $e->getTraceAsString());
            return $TotalMonthlyFeeAmount;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception: Student::GetStudentMonthlyFeeDetails(). Stack Trace: ' . $e->getTraceAsString());
            return $TotalMonthlyFeeAmount;
        }
	}
	
	public function GetStudentFeeMonths()
    {
    	$FeeMonths = array();

		try
        {	
			$RSSearchStudentFeeMonths = $this->DBObject->Prepare('SELECT * FROM asa_academic_year_months 
																  WHERE academicYearMonthID NOT IN 
																  (
																	  SELECT DISTINCT afsd.academicYearMonthID 
																	  FROM afm_fee_structure_details afsd 
																	  INNER JOIN afm_fee_structure afs ON afs.feeStructureID = afsd.feeStructureID 
																	  INNER JOIN afm_student_fee_structure asfs ON asfs.feeStructureDetailID = afsd.feeStructureDetailID 
																	  INNER JOIN afm_fee_collection_details afcd ON afcd.studentFeeStructureID = asfs.studentFeeStructureID 
																	  INNER JOIN afm_fee_collection afc ON afc.feeCollectionID = afcd.feeCollectionID 
																	  WHERE afc.studentID = :|1 AND afs.academicYearID = :|2 AND afcd.amountPaid > 0
																  ) 
																  AND academicYearMonthID NOT IN
																(
																	SELECT DISTINCT temp.academicYearMonthID FROM
																	(
																        SELECT fsd.academicYearMonthID,
																        SUM(sfs.amountPayable) amountPayable, 
																        sum(CASE WHEN afd.discountType = "Absolute" THEN (afd.discountValue + afd.concessionAmount + afd.waveOffAmount) ELSE (((sfs.amountPayable * afd.discountValue) / 100) + afd.concessionAmount + afd.waveOffAmount) END) AS firstDiscountValue,
																        sum(CASE WHEN afd1.discountType = "Absolute" THEN (afd1.discountValue + afd1.concessionAmount+ afd1.waveOffAmount) ELSE (((sfs.amountPayable * afd1.discountValue) / 100) + afd1.concessionAmount + afd1.waveOffAmount) END) AS secondDiscountValue

																        FROM afm_fee_structure_details fsd 
																        INNER JOIN afm_fee_structure fs ON fs.feeStructureID = fsd.feeStructureID 
																        INNER JOIN afm_student_fee_structure sfs ON sfs.feeStructureDetailID = fsd.feeStructureDetailID
																        LEFT JOIN afm_fee_discounts afd ON afd.feeStructureDetailID = fsd.feeStructureDetailID AND afd.feeDiscountType = \'Group\' 
																        LEFT JOIN afm_fee_discounts afd1 ON afd1.studentID = sfs.studentID AND afd1.feeStructureDetailID = fsd.feeStructureDetailID AND afd1.feeDiscountType = \'Student\' 

																        WHERE sfs.studentID = :|3 AND fs.academicYearID = :|4
																        GROUP BY fsd.academicYearMonthID
																        HAVING (amountPayable - secondDiscountValue) = 0
																    ) AS temp
																)
																ORDER BY feePriority ASC;');

			$RSSearchStudentFeeMonths->Execute($this->StudentID, $this->AcademicYearID, $this->StudentID, $this->AcademicYearID);

			if ($RSSearchStudentFeeMonths->Result->num_rows <= 0)
			{
				return $FeeMonths;
			}

			while($SearchFeeMonthRow = $RSSearchStudentFeeMonths->FetchRow())
			{
				$FeeMonths[$SearchFeeMonthRow->academicYearMonthID]['MonthName'] = $SearchFeeMonthRow->monthName;
				$FeeMonths[$SearchFeeMonthRow->academicYearMonthID]['MonthShortName'] = $SearchFeeMonthRow->monthShortName;
				$FeeMonths[$SearchFeeMonthRow->academicYearMonthID]['FeePriority'] = $SearchFeeMonthRow->feePriority;
			}

			return $FeeMonths;   
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException: Student::GetStudentFeeMonths(). Stack Trace: ' . $e->getTraceAsString());
            return $FeeMonths;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception: Student::GetStudentFeeMonths(). Stack Trace: ' . $e->getTraceAsString());
            return $FeeMonths;
        }
	}

	public function GetStudentDueFeeMonths()
    {
    	$DueFeeMonths = array();

		try
        {	
        	$RSSearchStudentFeeMonths = $this->DBObject->Prepare('SELECT aaym.*, afs.academicYearID, afh.feeHead, sum(afcd.amountPaid) AS amountPaid, 
        															SUM(asfs.amountPayable) amountPayable, 
																	sum(CASE WHEN afd.discountType = "Absolute" THEN (afd.discountValue + afd.concessionAmount + afd.waveOffAmount) ELSE (((asfs.amountPayable * afd.discountValue) / 100) + afd.concessionAmount + afd.waveOffAmount) END) AS firstDiscountValue,
																	sum(CASE WHEN afd1.discountType = "Absolute" THEN (afd1.discountValue + afd1.concessionAmount+ afd1.waveOffAmount) ELSE (((asfs.amountPayable * afd1.discountValue) / 100) + afd1.concessionAmount + afd1.waveOffAmount) END) AS secondDiscountValue
																	FROM asa_academic_year_months aaym
																	INNER JOIN afm_fee_structure_details afsd ON afsd.academicYearMonthID = aaym.academicYearMonthID
																	INNER JOIN afm_fee_structure afs ON afs.feeStructureID = afsd.feeStructureID
																	INNER JOIN afm_fee_heads afh ON afh.feeHeadID = afsd.feeHeadID
																	INNER JOIN afm_student_fee_structure asfs ON asfs.feeStructureDetailID = afsd.feeStructureDetailID
																	LEFT JOIN (SELECT feeCollectionDetailID, feeCollectionID, studentFeeStructureID, SUM(amountPaid) AS amountPaid FROM afm_fee_collection_details GROUP BY studentFeeStructureID) afcd ON afcd.studentFeeStructureID = asfs.studentFeeStructureID
																	LEFT JOIN afm_fee_collection afc ON afc.feeCollectionID = afcd.feeCollectionID 
																	LEFT JOIN afm_fee_discounts afd ON afd.feeGroupID = afs.feeGroupID AND afd.feeStructureDetailID = afsd.feeStructureDetailID AND afd.feeDiscountType = \'Group\' 
																	LEFT JOIN afm_fee_discounts afd1 ON afd1.studentID = asfs.studentID AND afd1.feeStructureDetailID = afsd.feeStructureDetailID AND afd1.feeDiscountType = \'Student\' 
																	WHERE asfs.studentID = :|1
																	Group BY afs.academicYearID, aaym.academicYearMonthID
																	ORDER BY aaym.feePriority ASC;');
			$RSSearchStudentFeeMonths->Execute($this->StudentID);

			if ($RSSearchStudentFeeMonths->Result->num_rows <= 0)
			{
				return $DueFeeMonths;
			}

			$DueAmount = 0;
			while($SearchFeeMonthRow = $RSSearchStudentFeeMonths->FetchRow())
			{
				$AmountPayable = 0;
				
				// if ($SearchFeeMonthRow->amountPaid > 0)
				// {
				//     if ($SearchFeeMonthRow->secondDiscountValue > 0) 
    // 				{
    // 					$AmountPayable = $SearchFeeMonthRow->amountPayable - $SearchFeeMonthRow->secondDiscountValue;
    // 				}
    // 				else
    // 				{
    // 					$AmountPayable = $SearchFeeMonthRow->amountPayable - $SearchFeeMonthRow->firstDiscountValue;
    // 				}
    
    // 				if ($AmountPayable > $SearchFeeMonthRow->amountPaid) 
    // 				{
    // 					$DueFeeMonths[$SearchFeeMonthRow->academicYearMonthID]['MonthName'] = $SearchFeeMonthRow->monthName;
    // 					$DueFeeMonths[$SearchFeeMonthRow->academicYearMonthID]['MonthShortName'] = $SearchFeeMonthRow->monthShortName;
    // 					$DueFeeMonths[$SearchFeeMonthRow->academicYearMonthID]['FeePriority'] = $SearchFeeMonthRow->feePriority;
    // 					$DueFeeMonths[$SearchFeeMonthRow->academicYearMonthID]['FeeHead'] = $SearchFeeMonthRow->feeHead;
    // 					$DueFeeMonths[$SearchFeeMonthRow->academicYearMonthID]['DueAmount'] = $AmountPayable - $SearchFeeMonthRow->amountPaid;
    // 				}   
				// }
				
				if ($SearchFeeMonthRow->academicYearID < $this->AcademicYearID) 
				{
					if ($SearchFeeMonthRow->secondDiscountValue > 0) 
					{
						$AmountPayable = $SearchFeeMonthRow->amountPayable - $SearchFeeMonthRow->secondDiscountValue;
					}
					else
					{
						$AmountPayable = $SearchFeeMonthRow->amountPayable - $SearchFeeMonthRow->firstDiscountValue;
					}

					if ($AmountPayable > $SearchFeeMonthRow->amountPaid) 
					{
						$DueFeeMonths[$SearchFeeMonthRow->academicYearID][$SearchFeeMonthRow->academicYearMonthID]['MonthName'] = $SearchFeeMonthRow->monthName;
						$DueFeeMonths[$SearchFeeMonthRow->academicYearID][$SearchFeeMonthRow->academicYearMonthID]['MonthShortName'] = $SearchFeeMonthRow->monthShortName;
						$DueFeeMonths[$SearchFeeMonthRow->academicYearID][$SearchFeeMonthRow->academicYearMonthID]['FeePriority'] = $SearchFeeMonthRow->feePriority;
						$DueFeeMonths[$SearchFeeMonthRow->academicYearID][$SearchFeeMonthRow->academicYearMonthID]['DueAmount'] = $AmountPayable - $SearchFeeMonthRow->amountPaid;
					}	
				}
				else if ($SearchFeeMonthRow->amountPaid != NULL) 
				{
					if ($SearchFeeMonthRow->secondDiscountValue > 0) 
					{
						$AmountPayable = $SearchFeeMonthRow->amountPayable - $SearchFeeMonthRow->secondDiscountValue;
					}
					else
					{
						$AmountPayable = $SearchFeeMonthRow->amountPayable - $SearchFeeMonthRow->firstDiscountValue;
					}

					if ($AmountPayable > $SearchFeeMonthRow->amountPaid) 
					{
						$DueFeeMonths[$SearchFeeMonthRow->academicYearID][$SearchFeeMonthRow->academicYearMonthID]['MonthName'] = $SearchFeeMonthRow->monthName;
						$DueFeeMonths[$SearchFeeMonthRow->academicYearID][$SearchFeeMonthRow->academicYearMonthID]['MonthShortName'] = $SearchFeeMonthRow->monthShortName;
						$DueFeeMonths[$SearchFeeMonthRow->academicYearID][$SearchFeeMonthRow->academicYearMonthID]['FeePriority'] = $SearchFeeMonthRow->feePriority;
						$DueFeeMonths[$SearchFeeMonthRow->academicYearID][$SearchFeeMonthRow->academicYearMonthID]['DueAmount'] = $AmountPayable - $SearchFeeMonthRow->amountPaid;
					}
				}
			}

			return $DueFeeMonths;   
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException: Student::GetStudentDueFeeMonths(). Stack Trace: ' . $e->getTraceAsString());
            return $DueFeeMonths;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception: Student::GetStudentDueFeeMonths(). Stack Trace: ' . $e->getTraceAsString());
            return $DueFeeMonths;
        }
	}
	
	public function GetStudentAdvanceFee()
    {
    	$StudentAdvanceFee = 0;

		try
        {	
        	$RSSearch = $this->DBObject->Prepare('SELECT walletAmount FROM asa_parent_details 
												WHERE parentID = :|1;');
			$RSSearch->Execute($this->ParentID);

			if ($RSSearch->Result->num_rows <= 0)
			{
				return $StudentAdvanceFee;
			}

			while($SearchRow = $RSSearch->FetchRow())
			{
				$StudentAdvanceFee = $SearchRow->walletAmount;
			}

			return $StudentAdvanceFee;   
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException: Student::GetStudentAdvanceFee(). Stack Trace: ' . $e->getTraceAsString());
            return $StudentAdvanceFee;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception: Student::GetStudentAdvanceFee(). Stack Trace: ' . $e->getTraceAsString());
            return $StudentAdvanceFee;
        }
	}

	public function GetStudentPreviousYearDue()
    {
    	$StudentPreviousYearDue = 0;

		try
        {	
        	$RSSearch = $this->DBObject->Prepare('SELECT * FROM afm_previous_year_fee_details 
												WHERE studentID = :|1 LIMIT 1;');
			$RSSearch->Execute($this->StudentID);

			if ($RSSearch->Result->num_rows <= 0)
			{
				return $StudentPreviousYearDue;
			}

			while($SearchRow = $RSSearch->FetchRow())
			{
				$AmountPayable = 0;
				$AmountPayable = $SearchRow->payableAmount - $SearchRow->paidAmount - $SearchRow->waveOffDue;
				
				if ($AmountPayable > 0) 
				{
					$StudentPreviousYearDue = $AmountPayable;
				}
			}

			return $StudentPreviousYearDue;   
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException: Student::GetStudentPreviousYearDue(). Stack Trace: ' . $e->getTraceAsString());
            return $StudentPreviousYearDue;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception: Student::GetStudentPreviousYearDue(). Stack Trace: ' . $e->getTraceAsString());
            return $StudentPreviousYearDue;
        }
	}
	// END OF PUBLIC METHODS	//
	
	// START OF STATIC METHODS	//
	static function GetStudentsByFeeCode($FeeCode)
	{
		$StudentDetails = array();
		try
		{

			$DBConnObject = new DBConnect();
			
			$RSStudentDetails = $DBConnObject->Prepare('SELECT ast.studentID, ast.rollNumber, asd.firstName, asd.lastName, ac.className, asm.sectionName, apd.fatherFirstName, apd.fatherLastName, apd.fatherMobileNumber FROM asa_students ast
														INNER JOIN asa_student_details asd ON asd.studentID = ast.studentID
														INNER JOIN asa_class_sections acs ON acs.classSectionID = ast.classSectionID
														INNER JOIN asa_classes ac ON ac.classID = acs.classID
														INNER JOIN asa_section_master asm ON asm.sectionMasterID = acs.sectionMasterID
														INNER JOIN asa_parent_details apd ON apd.parentID = ast.parentID
														WHERE apd.feeCode = :|1;');
			$RSStudentDetails->Execute($FeeCode);

			if ($RSStudentDetails->Result->num_rows <= 0)
			{
				return $StudentDetails;
			}

			while($SearchRow = $RSStudentDetails->FetchRow())
			{
				$StudentDetails[$SearchRow->studentID]['StudentName'] = $SearchRow->firstName .' '. $SearchRow->lastName;		
				$StudentDetails[$SearchRow->studentID]['Class'] = $SearchRow->className .' '. $SearchRow->sectionName;		
				$StudentDetails[$SearchRow->studentID]['RollNumber'] = $SearchRow->rollNumber;		
				$StudentDetails[$SearchRow->studentID]['FatherName'] = $SearchRow->fatherFirstName .' '. $SearchRow->fatherLastName;		
				$StudentDetails[$SearchRow->studentID]['Contact'] = $SearchRow->fatherMobileNumber;
			}

			return $StudentDetails;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at Student::GetStudentsByFeeCode(). Stack Trace: ' . $e->getTraceAsString());
			return false;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at Student::GetStudentsByFeeCode(). Stack Trace: ' . $e->getTraceAsString());
			return false;
		}
	}

	static function GetStudentsByContactNumber($ContactNumber)
	{
		$StudentDetails = array();
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSStudentDetails = $DBConnObject->Prepare('SELECT ast.studentID, ast.rollNumber, asd.firstName, asd.lastName, ac.className, asm.sectionName, apd.fatherFirstName, apd.fatherLastName, apd.fatherMobileNumber FROM asa_students ast
														INNER JOIN asa_student_details asd ON asd.studentID = ast.studentID
														INNER JOIN asa_class_sections acs ON acs.classSectionID = ast.classSectionID
														INNER JOIN asa_classes ac ON ac.classID = acs.classID
														INNER JOIN asa_section_master asm ON asm.sectionMasterID = acs.sectionMasterID
														INNER JOIN asa_parent_details apd ON apd.parentID = ast.parentID
														WHERE apd.fatherMobileNumber = :|1 OR apd.motherMobileNumber = :|2;');
			$RSStudentDetails->Execute($ContactNumber, $ContactNumber);

			if ($RSStudentDetails->Result->num_rows <= 0)
			{
				return $StudentDetails;
			}

			while($SearchRow = $RSStudentDetails->FetchRow())
			{
				$StudentDetails[$SearchRow->studentID]['StudentName'] = $SearchRow->firstName .' '. $SearchRow->lastName;		
				$StudentDetails[$SearchRow->studentID]['Class'] = $SearchRow->className .' '. $SearchRow->sectionName;		
				$StudentDetails[$SearchRow->studentID]['RollNumber'] = $SearchRow->rollNumber;		
				$StudentDetails[$SearchRow->studentID]['FatherName'] = $SearchRow->fatherFirstName .' '. $SearchRow->fatherLastName;		
				$StudentDetails[$SearchRow->studentID]['Contact'] = $SearchRow->fatherMobileNumber;
			}

			return $StudentDetails;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at Student::GetStudentsByContactNumber(). Stack Trace: ' . $e->getTraceAsString());
			return false;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at Student::GetStudentsByContactNumber(). Stack Trace: ' . $e->getTraceAsString());
			return false;
		}
	}
	
	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//
	private function RemoveStudent()
    {
        if(!isset($this->StudentID)) 
        {
            throw new ApplicationARException('', APP_AR_ERROR_DELETE_WITHOUT_ID);
        }
        
        $RSDeleteColourHouse = $this->DBObject->Prepare('DELETE FROM asa_students WHERE studentID = :|1 LIMIT 1;');
        $RSDeleteColourHouse->Execute($this->StudentID);                
    }
	
	private function GetStudentByID()
	{
		$RSStudent = $this->DBObject->Prepare('SELECT * FROM asa_students WHERE studentID = :|1 LIMIT 1');
		$RSStudent->Execute($this->StudentID);
		
		$StudentRow = $RSStudent->FetchRow();
		
		$this->SetAttributesFromDB($StudentRow);				
	}
	
	private function SetAttributesFromDB($StudentRow)
	{
		$this->StudentID = $StudentRow->studentID;
		$this->StudentRegistrationID = $StudentRow->studentRegistrationID;
		$this->ParentID = $StudentRow->parentID;
		$this->ClassSectionID = $StudentRow->classSectionID;
		$this->ColourHouseID = $StudentRow->colourHouseID;

		$this->UserName = $StudentRow->userName;
		$this->EnrollmentID = $StudentRow->enrollmentID;
		$this->AdmissionDate = $StudentRow->admissionDate;
		$this->RollNumber = $StudentRow->rollNumber;
		$this->AadharNumber = $StudentRow->aadharNumber;
		$this->StudentType = $StudentRow->studentType;
		$this->Status = $StudentRow->status;
		$this->AcademicYearID = $StudentRow->academicYearID;
		
		$this->CreateUserID = $StudentRow->createUserID;
		$this->CreateDate = $StudentRow->createDate;
	}	
}
?>
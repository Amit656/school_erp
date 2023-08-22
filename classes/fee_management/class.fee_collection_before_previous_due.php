<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.db_connect.php');
error_reporting(E_ALL);

class FeeCollection
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	
	private $FeeCollectionID;
	private $StudentID;
	private $FeeDate;

	private $TotalAmount;
	private $TotalDiscount;
	private $AmountPaid;
	private $PaymentMode;
	private $ChequeReferenceNo;

	private $CreateUserID;
	private $CreateDate;

	private $FeeCollectionDetails = array();
	private $OtherChargesDetails = array();
	
	private $CurrentTransactionIDs = array();
	
	// PUBLIC METHODS START HERE	//
	public function __construct($FeeCollectionID = 0)
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;
		
		if($FeeCollectionID != 0)
		{
			$this->FeeCollectionID = $FeeCollectionID;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetFeeCollectionByID();
		}
		else
		{
			//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
			$this->FeeCollectionID = 0;
			$this->StudentID = 0;
			$this->FeeDate = '0000-00-00';

			$this->TotalAmount = 0;
			$this->TotalDiscount = 0;
			$this->AmountPaid = 0;
			$this->PaymentMode = 0;
			$this->ChequeReferenceNo = '';

			$this->CreateUserID = 0;
			$this->CreateDate = '0000-00-00 00:00:00';

			$this->FeeCollectionDetails = array();
			$this->OtherChargesDetails = array();

			$this->CurrentTransactionIDs = array();
		}
	}
	
	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetFeeCollectionID()
	{
		return $this->FeeCollectionID;
	}
	
	public function GetStudentID()
	{
		return $this->StudentID;
	}
	public function SetStudentID($StudentID)
	{
		$this->StudentID = $StudentID;
	}

	public function GetFeeDate()
	{
		return $this->FeeDate;
	}
	public function SetFeeDate($FeeDate)
	{
		$this->FeeDate = $FeeDate;
	}

	public function GetTotalAmount()
	{
		return $this->TotalAmount;
	}
	public function SetTotalAmount($TotalAmount)
	{
		$this->TotalAmount = $TotalAmount;
	}
	
	public function GetTotalDiscount()
	{
		return $this->TotalDiscount;
	}
	public function SetTotalDiscount($TotalDiscount)
	{
		$this->TotalDiscount = $TotalDiscount;
	}
	
	public function GetAmountPaid()
	{
		return $this->AmountPaid;
	}
	public function SetAmountPaid($AmountPaid)
	{
		$this->AmountPaid = $AmountPaid;
	}
	
	public function GetPaymentMode()
	{
		return $this->PaymentMode;
	}
	public function SetPaymentMode($PaymentMode)
	{
		$this->PaymentMode = $PaymentMode;
	}
	
	public function GetChequeReferenceNo()
	{
		return $this->ChequeReferenceNo;
	}
	public function SetChequeReferenceNo($ChequeReferenceNo)
	{
		$this->ChequeReferenceNo = $ChequeReferenceNo;
	}

	public function GetCreateUserID()
	{
		return $this->CreateUserID;
	}
	public function SetCreateUserID($CreateUserID)
	{
		$this->CreateUserID = $CreateUserID;
	}

	public function GetFeeCollectionDetails()
	{
		return $this->FeeCollectionDetails;
	}
	public function SetFeeCollectionDetails($FeeCollectionDetails)
	{
		$this->FeeCollectionDetails = $FeeCollectionDetails;
	}

	public function GetOtherChargesDetails()
	{
		return $this->OtherChargesDetails;
	}
	public function SetOtherChargesDetails($OtherChargesDetails)
	{
		$this->OtherChargesDetails = $OtherChargesDetails;
	}

	public function GetCurrentTransactionIDs()
	{
		return $this->CurrentTransactionIDs;
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
            $this->RemoveFeeCollection();
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
	static function SearchFeeCollectionDetails(&$TotalRecords = 0, $GetTotalsOnly = false, $Filters = array(), $Start = 0, $Limit = 100)
	{
		$FeeCollectionDetails = array();
		
		try
		{
			$DBConnObject = new DBConnect();

			$Conditions = array();
			
			if (count($Filters) > 0)
			{
				if (!empty($Filters['TransactionDate']))
				{
					$Conditions[] = 'afc.feeDate = '. $DBConnObject->RealEscapeVariable($Filters['TransactionDate']);
				}
				
				if (!empty($Filters['TransactionFromDate']))
				{
					$Conditions[] = 'afc.feeDate BETWEEN '. $DBConnObject->RealEscapeVariable($Filters['TransactionFromDate']) .'AND'. $DBConnObject->RealEscapeVariable($Filters['TransactionToDate']);
				}

				if (!empty($Filters['StudentName']))
				{
					$Conditions[] = 'CONCAT(asd.firstName, " ", asd.lastName) LIKE ' . $DBConnObject->RealEscapeVariable('%' . $Filters['StudentName'] . '%');
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
				$RSTotal = $DBConnObject->Prepare('SELECT COUNT(Distinct afc.feeCollectionID) AS totalRecords 
													FROM afm_fee_collection afc
													INNER JOIN afm_fee_collection_details afcd ON afcd.feeCollectionID = afc.feeCollectionID 
													INNER JOIN afm_student_fee_structure asfs ON asfs.studentFeeStructureID = afcd.studentFeeStructureID 
													INNER JOIN afm_fee_structure_details afsd ON afsd.feeStructureDetailID = asfs.feeStructureDetailID 
													INNER JOIN afm_fee_structure afs ON afs.feeStructureID = afsd.feeStructureID 
													INNER JOIN asa_academic_years ay ON ay.academicYearID = afs.academicYearID 
													INNER JOIN asa_student_details asd ON asd.studentID = afc.studentID 
													INNER JOIN asa_students ass ON ass.studentID = asd.studentID 
													INNER JOIN asa_class_sections acs ON acs.classSectionID = ass.classSectionID 
													INNER JOIN asa_classes ac ON ac.classID = acs.classID 
													INNER JOIN asa_section_master asm ON asm.sectionMasterID = acs.sectionMasterID 
													INNER JOIN users u ON afc.createUserID = u.userID
													'. $QueryString .';');
				$RSTotal->Execute();
				
				$TotalRecords = $RSTotal->FetchRow()->totalRecords;
				return;
			}
			
			$RSSearch = $DBConnObject->Prepare('SELECT afc.*, CONCAT( YEAR(ay.startDate), \'-\', DATE_FORMAT(ay.endDate, \'%y\')) AS academicYearName, u.userName AS createUserName, 
												asd.firstName, asd.lastName, ac.className, asm.sectionName
												FROM afm_fee_collection afc
												INNER JOIN afm_fee_collection_details afcd ON afcd.feeCollectionID = afc.feeCollectionID 
												INNER JOIN afm_student_fee_structure asfs ON asfs.studentFeeStructureID = afcd.studentFeeStructureID 
												INNER JOIN afm_fee_structure_details afsd ON afsd.feeStructureDetailID = asfs.feeStructureDetailID 
												INNER JOIN afm_fee_structure afs ON afs.feeStructureID = afsd.feeStructureID 
												INNER JOIN asa_academic_years ay ON ay.academicYearID = afs.academicYearID 
												INNER JOIN asa_student_details asd ON asd.studentID = afc.studentID 
												INNER JOIN asa_students ass ON ass.studentID = asd.studentID 
												INNER JOIN asa_class_sections acs ON acs.classSectionID = ass.classSectionID 
												INNER JOIN asa_classes ac ON ac.classID = acs.classID 
												INNER JOIN asa_section_master asm ON asm.sectionMasterID = acs.sectionMasterID 
												INNER JOIN users u ON afc.createUserID = u.userID
												'. $QueryString .'
												GROUP BY afc.feeCollectionID
												ORDER BY afc.feeCollectionID LIMIT ' . (int) $Start . ', ' . (int) $Limit . ';');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $FeeCollectionDetails; 
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$FeeCollectionDetails['AcademicYearName'] = $SearchRow->academicYearName;
				$FeeCollectionDetails[$SearchRow->feeCollectionID]['StudentName'] = $SearchRow->firstName .' '. $SearchRow->lastName;

				$FeeCollectionDetails[$SearchRow->feeCollectionID]['ClassName'] = $SearchRow->className;
				$FeeCollectionDetails[$SearchRow->feeCollectionID]['SectionName'] = $SearchRow->sectionName;

				$FeeCollectionDetails[$SearchRow->feeCollectionID]['FeeDate'] = $SearchRow->feeDate;

				$FeeCollectionDetails[$SearchRow->feeCollectionID]['TotalAmount'] = $SearchRow->totalAmount;
				$FeeCollectionDetails[$SearchRow->feeCollectionID]['TotalDiscount'] = $SearchRow->totalDiscount;
				$FeeCollectionDetails[$SearchRow->feeCollectionID]['AmountPaid'] = $SearchRow->amountPaid;
				$FeeCollectionDetails[$SearchRow->feeCollectionID]['PaymentMode'] = $SearchRow->paymentMode;
				 			
				$FeeCollectionDetails[$SearchRow->feeCollectionID]['CreateUserName'] = $SearchRow->createUserName;
				$FeeCollectionDetails[$SearchRow->feeCollectionID]['CreateDate'] = $SearchRow->createDate;
			}
			
			return $FeeCollectionDetails;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at FeeCollection::SearchFeeCollectionDetails(). Stack Trace: ' . $e->getTraceAsString());
			return $FeeCollectionDetails;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at FeeCollection::SearchFeeCollectionDetails(). Stack Trace: ' . $e->getTraceAsString());
			return $FeeCollectionDetails;
		}
	}

	static function GetFeeTransactionDetails($FeeCollectionID)
	{
		$FeeCollectionDetails = array();
		try
		{

			$DBConnObject = new DBConnect();
			
			$RSFeeTransactionDetails = $DBConnObject->Prepare('SELECT afcd.*, afh.feeHead, afh.feeHeadID, asfs.amountPayable, aaym.monthName, afd.discountType AS firstDiscountType, afd.discountValue AS firstDiscountValue, afd1.discountType AS secondDiscountType, afd1.discountValue AS secondDiscountValue  
															FROM afm_fee_collection_details afcd
															INNER JOIN afm_student_fee_structure asfs ON asfs.studentFeeStructureID = afcd.studentFeeStructureID
															INNER JOIN afm_fee_structure_details afsd ON afsd.feeStructureDetailID = asfs.feeStructureDetailID
															INNER JOIN afm_fee_structure afs ON afs.feeStructureID = afsd.feeStructureID
															INNER JOIN afm_fee_heads afh ON afh.feeHeadID = afsd.feeHeadID
															INNER JOIN asa_academic_year_months aaym ON aaym.academicYearMonthID = afsd.academicYearMonthID
															LEFT JOIN afm_fee_discounts afd ON afd.feeGroupID = afs.feeGroupID AND afd.feeStructureDetailID = afsd.feeStructureDetailID AND afd.feeDiscountType = \'Group\' 
							 								LEFT JOIN afm_fee_discounts afd1 ON afd1.studentID = asfs.studentID AND afd1.feeStructureDetailID = afsd.feeStructureDetailID AND afd1.feeDiscountType = \'Student\' 
															WHERE afcd.feeCollectionID = :|1;');
			$RSFeeTransactionDetails->Execute($FeeCollectionID);

			if ($RSFeeTransactionDetails->Result->num_rows <= 0)
			{
				return $FeeCollectionDetails;
			}

			$TotalMonthlyFeeAmount = 0;

			while($SearchRow = $RSFeeTransactionDetails->FetchRow())
			{

				$FeeAmount = 0; 
				$FeeHeadDiscountAmount = 0;
				$FirstDiscountAmount = 0;
				$SecondDiscountAmount = 0;

				$FeeAmount = $SearchRow->amountPayable;

				$FeeCollectionDetails[$SearchRow->monthName][$SearchRow->feeHeadID]['FeeHead'] = $SearchRow->feeHead;		
				$FeeCollectionDetails[$SearchRow->monthName][$SearchRow->feeHeadID]['FeeAmount'] = $FeeAmount;

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

					$FeeCollectionDetails[$SearchRow->monthName][$SearchRow->feeHeadID]['DiscountType'] = $SearchRow->secondDiscountType;
					$FeeCollectionDetails[$SearchRow->monthName][$SearchRow->feeHeadID]['DiscountValue'] = $SearchRow->secondDiscountValue;
					$FeeCollectionDetails[$SearchRow->monthName][$SearchRow->feeHeadID]['DiscountAmount'] = $SecondDiscountAmount;
				}
				else
				{
					$FeeHeadDiscountAmount = $FirstDiscountAmount;

					$FeeCollectionDetails[$SearchRow->monthName][$SearchRow->feeHeadID]['DiscountType'] = $SearchRow->firstDiscountType;
					$FeeCollectionDetails[$SearchRow->monthName][$SearchRow->feeHeadID]['DiscountValue'] = $SearchRow->firstDiscountValue;
					$FeeCollectionDetails[$SearchRow->monthName][$SearchRow->feeHeadID]['DiscountAmount'] = $FirstDiscountAmount;
				}

				$TotalMonthlyFeeAmount = $TotalMonthlyFeeAmount + $FeeAmount - $FeeHeadDiscountAmount;	
			
				$FeeCollectionDetails[$SearchRow->monthName][$SearchRow->feeHeadID]['PaidAmount'] = $SearchRow->amountPaid;
				$FeeCollectionDetails[$SearchRow->monthName][$SearchRow->feeHeadID]['RestAmount'] = $FeeAmount - $FeeHeadDiscountAmount - $SearchRow->amountPaid;
			}

			return $FeeCollectionDetails;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at FeeCollection::GetFeeTransactionDetails(). Stack Trace: ' . $e->getTraceAsString());
			return false;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at FeeCollection::GetFeeTransactionDetails(). Stack Trace: ' . $e->getTraceAsString());
			return false;
		}
	}

	static function SearchFeeDefaulters(&$TotalRecords = 0, $GetTotalsOnly = false, $Filters = array(), $FeePriority = 0, $Start = 0, $Limit = 100)
	{
		$DefaulterList = array();
		
		try
		{
			$DBConnObject = new DBConnect();

			$Conditions = array();
			
			if (count($Filters) > 0)
			{
				if (!empty($Filters['ClassID']))
				{
					$Conditions[] = 'ac.ClassID = '. $DBConnObject->RealEscapeVariable($Filters['ClassID']);
				}

				if (!empty($Filters['ClassSectionID']))
				{
					$Conditions[] = 'acs.ClassSectionID = '. $DBConnObject->RealEscapeVariable($Filters['ClassSectionID']);
				}			

				if (!empty($Filters['StudentName']))
				{
					$Conditions[] = 'CONCAT(asd.firstName, " ", asd.lastName) LIKE ' . $DBConnObject->RealEscapeVariable('%' . $Filters['StudentName'] . '%');
				}
			}
			
			$QueryString = '';

			if (count($Conditions) > 0)
			{
				$QueryString = implode(') AND (', $Conditions);
				
				$QueryString = ' AND (' . $QueryString . ')';
			}

			if ($GetTotalsOnly)
			{
				$RSTotal = $DBConnObject->Prepare('SELECT COUNT(Distinct sfs.studentID) AS totalRecords 
													FROM afm_fee_structure_details fsd 
													INNER JOIN afm_student_fee_structure sfs ON sfs.feeStructureDetailID = fsd.feeStructureDetailID 
													LEFT JOIN afm_fee_collection_details fcd ON fcd.studentFeeStructureID = sfs.studentFeeStructureID 
													LEFT JOIN afm_fee_collection fc ON fc.feeCollectionID = fcd.feeCollectionID 
													INNER JOIN asa_student_details asd ON asd.studentID = sfs.studentID 
													INNER JOIN asa_students ass ON ass.studentID = asd.studentID 
													INNER JOIN asa_class_sections acs ON acs.classSectionID = ass.classSectionID 
													INNER JOIN asa_classes ac ON ac.classID = acs.classID 
													INNER JOIN asa_section_master asm ON asm.sectionMasterID = acs.sectionMasterID 
													WHERE fsd.academicYearMonthID IN (SELECT academicYearMonthID FROM asa_academic_year_months WHERE feePriority <= :|1) AND (fc.amountPaid IS NULL OR fc.amountPaid < (fc.totalAmount-fc.totalDiscount))
													'. $QueryString .';');
				$RSTotal->Execute($FeePriority);
				
				$TotalRecords = $RSTotal->FetchRow()->totalRecords;
				return;
			}
			
			$RSSearch = $DBConnObject->Prepare('SELECT asd.firstName, asd.lastName, ac.className, asm.sectionName, sfs.studentID, 
												SUM(sfs.amountPayable) AS totalAmountPayable, 
												SUM(fcd.amountPaid) As totalAmountPaid,
												SUM(CASE WHEN afd.discountType = "Absolute" THEN afd.discountValue ELSE (sfs.amountPayable * afd.discountValue) / 100 END) AS firstDiscountValue, 
												SUM(CASE WHEN afd1.discountType = "Absolute" THEN afd1.discountValue ELSE (sfs.amountPayable * afd1.discountValue) / 100 END) AS secondDiscountValue
												-- (SELECT SUM(fcoc.amount) FROM afm_fee_collection_other_charges fcoc WHERE fcoc.feeCollectionID = fc.feeCollectionID) AS totalOtherAmountPaid

												FROM afm_fee_structure_details fsd 
												INNER JOIN afm_student_fee_structure sfs ON sfs.feeStructureDetailID = fsd.feeStructureDetailID 
												INNER JOIN afm_fee_structure fs ON fs.feeStructureID = fsd.feeStructureID

												LEFT JOIN (SELECT feeCollectionDetailID, feeCollectionID, studentFeeStructureID, SUM(amountPaid) AS amountPaid FROM afm_fee_collection_details GROUP BY studentFeeStructureID) AS fcd ON fcd.studentFeeStructureID = sfs.studentFeeStructureID
												LEFT JOIN afm_fee_collection fc ON fc.feeCollectionID = fcd.feeCollectionID 
												LEFT JOIN afm_fee_discounts afd ON afd.feeGroupID = fs.feeGroupID AND afd.feeStructureDetailID = fsd.feeStructureDetailID AND afd.feeDiscountType = \'Group\' 
												LEFT JOIN afm_fee_discounts afd1 ON afd1.studentID = sfs.studentID AND afd1.feeStructureDetailID = fsd.feeStructureDetailID AND afd1.feeDiscountType = \'Student\' 

												INNER JOIN asa_student_details asd ON asd.studentID = sfs.studentID 
												INNER JOIN asa_students ass ON ass.studentID = asd.studentID 
												INNER JOIN asa_class_sections acs ON acs.classSectionID = ass.classSectionID 
												INNER JOIN asa_classes ac ON ac.classID = acs.classID 
												INNER JOIN asa_section_master asm ON asm.sectionMasterID = acs.sectionMasterID 

												WHERE fsd.academicYearMonthID IN (SELECT academicYearMonthID FROM asa_academic_year_months WHERE feePriority <= :|1)
												'. $QueryString .'

												GROUP BY sfs.studentID
												HAVING totalAmountPaid IS NULL OR (totalAmountPaid < (CASE WHEN secondDiscountValue > 0 THEN (totalAmountPayable - secondDiscountValue) ELSE (CASE WHEN firstDiscountValue > 0 THEN (totalAmountPayable - firstDiscountValue) ELSE totalAmountPayable END) END))
												ORDER BY asd.firstName ASC LIMIT '.(int) $Start.', '.(int) $Limit.';');
			$RSSearch->Execute($FeePriority);
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $DefaulterList; 
			}
			
			$DiscountValue = 0;

			while($SearchRow = $RSSearch->FetchRow())
			{
				
				$DefaulterList[$SearchRow->studentID]['StudentName'] = $SearchRow->firstName .' '. $SearchRow->lastName;
				$DefaulterList[$SearchRow->studentID]['Class'] = $SearchRow->className .' ( '. $SearchRow->sectionName .' )';

				if ($SearchRow->secondDiscountValue > 0) 
				{
					$DiscountValue = $SearchRow->secondDiscountValue;
				}
				else
				{
					$DiscountValue = $SearchRow->firstDiscountValue;
				}

				// $DefaulterList[$SearchRow->studentID]['TotalAmountPayable'] = $SearchRow->totalAmountPayable - $DiscountValue;
				// $DefaulterList[$SearchRow->studentID]['TotalAmountPaid'] = $SearchRow->totalAmountPaid - $SearchRow->totalOtherAmountPaid;
				$DefaulterList[$SearchRow->studentID]['TotalDue'] = $SearchRow->totalAmountPayable - $SearchRow->totalAmountPaid - $DiscountValue;
			}
			
			return $DefaulterList;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at FeeCollection::SearchFeeDefaulters(). Stack Trace: ' . $e->getTraceAsString());
			return $DefaulterList;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at FeeCollection::SearchFeeDefaulters(). Stack Trace: ' . $e->getTraceAsString());
			return $DefaulterList;
		}
	}

	static function GetFeeDefaulterDues($StudentID, $FeePriority)
	{
		$FeeDefaulterDues = array();
		try
		{

			$DBConnObject = new DBConnect();
			
			$RSFeeDefaulterDues = $DBConnObject->Prepare('SELECT afh.feeHead, afh.feeHeadID, afsd.feeAmount, aaym.monthName, SUM(afcd.amountPaid) AS amountPaid, asfs.amountPayable, afd.discountType AS firstDiscountType, afd.discountValue AS firstDiscountValue, afd1.discountType AS secondDiscountType, afd1.discountValue AS secondDiscountValue  
															FROM afm_student_fee_structure asfs
															INNER JOIN afm_fee_structure_details afsd ON afsd.feeStructureDetailID = asfs.feeStructureDetailID
															LEFT JOIN afm_fee_collection_details afcd ON afcd.studentFeeStructureID = asfs.studentFeeStructureID
															INNER JOIN afm_fee_structure afs ON afs.feeStructureID = afsd.feeStructureID
															INNER JOIN afm_fee_heads afh ON afh.feeHeadID = afsd.feeHeadID
															INNER JOIN asa_academic_year_months aaym ON aaym.academicYearMonthID = afsd.academicYearMonthID
															LEFT JOIN afm_fee_discounts afd ON afd.feeGroupID = afs.feeGroupID AND afd.feeStructureDetailID = afsd.feeStructureDetailID AND afd.feeDiscountType = \'Group\' 
							 								LEFT JOIN afm_fee_discounts afd1 ON afd1.studentID = asfs.studentID AND afd1.feeStructureDetailID = afsd.feeStructureDetailID AND afd1.feeDiscountType = \'Student\' 
															WHERE asfs.studentID = :|1 AND afsd.academicYearMonthID IN (SELECT academicYearMonthID FROM asa_academic_year_months WHERE feePriority <= :|2)
															GROUP BY afsd.feeHeadID, afsd.academicYearMonthID
															ORDER BY aaym.feePriority;');
			$RSFeeDefaulterDues->Execute($StudentID, $FeePriority);

			if ($RSFeeDefaulterDues->Result->num_rows <= 0)
			{
				return $FeeDefaulterDues;
			}

			while($SearchRow = $RSFeeDefaulterDues->FetchRow())
			{
				$FeeHeadAmount = 0;
				$FeeAmount = 0; 
				$FeeHeadDiscountAmount = 0;
				$FirstDiscountAmount = 0;
				$SecondDiscountAmount = 0;

				$FeeAmount = $SearchRow->feeAmount;

				$FeeDefaulterDues[$SearchRow->monthName][$SearchRow->feeHeadID]['FeeHead'] = $SearchRow->feeHead;		

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

					$FeeDefaulterDues[$SearchRow->monthName][$SearchRow->feeHeadID]['DiscountType'] = $SearchRow->secondDiscountType;
					$FeeDefaulterDues[$SearchRow->monthName][$SearchRow->feeHeadID]['DiscountValue'] = $SearchRow->secondDiscountValue;
					$FeeDefaulterDues[$SearchRow->monthName][$SearchRow->feeHeadID]['DiscountAmount'] = $SecondDiscountAmount;
				}
				else
				{
					$FeeHeadDiscountAmount = $FirstDiscountAmount;

					$FeeDefaulterDues[$SearchRow->monthName][$SearchRow->feeHeadID]['DiscountType'] = $SearchRow->firstDiscountType;
					$FeeDefaulterDues[$SearchRow->monthName][$SearchRow->feeHeadID]['DiscountValue'] = $SearchRow->firstDiscountValue;
					$FeeDefaulterDues[$SearchRow->monthName][$SearchRow->feeHeadID]['DiscountAmount'] = $FirstDiscountAmount;
				}

				if (($SearchRow->amountPayable - $FeeHeadDiscountAmount) == $SearchRow->amountPaid) 
				{
					unset($FeeDefaulterDues[$SearchRow->monthName][$SearchRow->feeHeadID]);
				}
				else
				{
					$FeeDefaulterDues[$SearchRow->monthName][$SearchRow->feeHeadID]['FeeAmount'] = $FeeAmount;
					$FeeDefaulterDues[$SearchRow->monthName][$SearchRow->feeHeadID]['AmountPaid'] = $SearchRow->amountPaid;

					$FeeHeadAmount += $FeeAmount - $FeeHeadDiscountAmount - $SearchRow->amountPaid;	
			
					$FeeDefaulterDues[$SearchRow->monthName][$SearchRow->feeHeadID]['FeeHeadAmount'] = $FeeHeadAmount;	
				}
			}

			return $FeeDefaulterDues;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at FeeCollection::GetFeeDefaulterDues(). Stack Trace: ' . $e->getTraceAsString());
			return false;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at FeeCollection::GetFeeDefaulterDues(). Stack Trace: ' . $e->getTraceAsString());
			return false;
		}
	}

	static function GetDefaultedFeeMonths($StudentID, $FeePriority)
	{
		$DefaultedFeeMonths = array();
		try
		{

			$DBConnObject = new DBConnect();
			
			$RSDefaultedFeeMonths = $DBConnObject->Prepare('SELECT aaym.academicYearMonthID, aaym.feePriority FROM asa_academic_year_months aaym 
														WHERE aaym.academicYearMonthID NOT IN 
														(SELECT afsd.academicYearMonthID FROM afm_student_fee_structure asfs 
														INNER JOIN afm_fee_structure_details afsd ON afsd.feeStructureDetailID = asfs.feeStructureDetailID 
														INNER JOIN afm_fee_collection_details afcd ON afcd.studentFeeStructureID = asfs.studentFeeStructureID
														WHERE asfs.studentID = :|1) 
														AND aaym.feePriority <= :|2
														ORDER BY aaym.feePriority;');
			$RSDefaultedFeeMonths->Execute($StudentID, $FeePriority);

			if ($RSDefaultedFeeMonths->Result->num_rows <= 0)
			{
				return $DefaultedFeeMonths;
			}

			while($SearchRow = $RSDefaultedFeeMonths->FetchRow())
			{
				$DefaultedFeeMonths[$SearchRow->academicYearMonthID] = $SearchRow->feePriority;
			}

			return $DefaultedFeeMonths;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at FeeCollection::GetDefaultedFeeMonths(). Stack Trace: ' . $e->getTraceAsString());
			return false;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at FeeCollection::GetDefaultedFeeMonths(). Stack Trace: ' . $e->getTraceAsString());
			return false;
		}
	}

	static function GetFeeTransactionOtherChargesDetails($FeeCollectionID)
	{
		$OtherChargesDetails = array();
		try
		{

			$DBConnObject = new DBConnect();
			
			$RSOtherChargesDetails = $DBConnObject->Prepare('SELECT * FROM afm_fee_collection_other_charges
															WHERE feeCollectionID = :|1;');
			$RSOtherChargesDetails->Execute($FeeCollectionID);

			if ($RSOtherChargesDetails->Result->num_rows <= 0)
			{
				return $OtherChargesDetails;
			}

			while($SearchRow = $RSOtherChargesDetails->FetchRow())
			{

				$OtherChargesDetails[$SearchRow->feeCollectionOtherChargeID]['FeeType'] = $SearchRow->feeType;		
				$OtherChargesDetails[$SearchRow->feeCollectionOtherChargeID]['FeeDescription'] = $SearchRow->feeDescription;		
				$OtherChargesDetails[$SearchRow->feeCollectionOtherChargeID]['Amount'] = $SearchRow->amount;		
				
			}

			return $OtherChargesDetails;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at FeeCollection::GetFeeTransactionOtherChargesDetails(). Stack Trace: ' . $e->getTraceAsString());
			return false;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at FeeCollection::GetFeeTransactionOtherChargesDetails(). Stack Trace: ' . $e->getTraceAsString());
			return false;
		}
	}
	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//

	private function SaveDetails()
	{
		if ($this->FeeCollectionID == 0)
		{
			foreach ($this->FeeCollectionDetails as $StudentID => $Details) 
			{
				$RSSaveFeeCollection = $this->DBObject->Prepare('INSERT INTO afm_fee_collection (studentID, feeDate, totalAmount, totalDiscount, amountPaid, paymentMode, chequeReferenceNo, createUserID, createDate)
																VALUES (:|1, :|2, :|3, :|4, :|5, :|6, :|7, :|8, NOW());');
			
				$RSSaveFeeCollection->Execute($StudentID, $this->FeeDate, $Details['TotalAmount'], $Details['TotalDiscount'], $Details['StudentAmountPaid'], $this->PaymentMode, $this->ChequeReferenceNo, $this->CreateUserID);
				
				$this->FeeCollectionID = $RSSaveFeeCollection->LastID;

				$this->CurrentTransactionIDs[$this->FeeCollectionID] = $this->FeeCollectionID;

				foreach ($Details['StudentFeeCollectionDetails'] as $StudentFeeStructureID => $AmountPaid) 
				{
					$RSSaveFeeCollectionDetails = $this->DBObject->Prepare('INSERT INTO afm_fee_collection_details (feeCollectionID, studentFeeStructureID, amountPaid)
														VALUES (:|1, :|2, :|3);');
				
					$RSSaveFeeCollectionDetails->Execute($this->FeeCollectionID, $StudentFeeStructureID, $AmountPaid);	
				}
			}	

			foreach ($this->OtherChargesDetails as $Key => $Details) 
			{
				$RSSaveOtherChargesDetails = $this->DBObject->Prepare('INSERT INTO afm_fee_collection_other_charges (feeCollectionID, feeType, feeDescription, amount)
													VALUES (:|1, :|2, :|3, :|4);');
			
				$RSSaveOtherChargesDetails->Execute($this->FeeCollectionID, $Details['FeeType'], $Details['FeeDescription'], $Details['Amount']);			
			}		
		}
		else
		{
		    $RSUpdate = $this->DBObject->Prepare('UPDATE afm_fee_collection
													SET	studentID = :|1,
														feeDate = :|2
													WHERE feeCollectionID = :|3 LIMIT 1;');
													
			$RSUpdate->Execute($this->StudentID, $this->FeeDate, $this->FeeCollectionID);
		}
		
		return true;
	}
	
	private function RemoveFeeCollection()
    {
        if(!isset($this->FeeCollectionID)) 
        {
            throw new ApplicationARException('', APP_AR_ERROR_DELETE_WITHOUT_ID);
        }
        
        $RSDeleteFeeCollectionDetails = $this->DBObject->Prepare('DELETE FROM afm_fee_collection_details WHERE feeCollectionID = :|1;');
        $RSDeleteFeeCollectionDetails->Execute($this->FeeCollectionID); 

		if(!isset($this->FeeCollectionID)) 
		{
		    throw new ApplicationARException('', APP_AR_ERROR_DELETE_WITHOUT_ID);
		}
		    
        $RSDeleteFeeCollection = $this->DBObject->Prepare('DELETE FROM afm_fee_collection WHERE feeCollectionID = :|1;');
        $RSDeleteFeeCollection->Execute($this->FeeCollectionID); 

        return true;               
    }
		
	private function GetFeeCollectionByID()
	{
		$RSFeeCollection = $this->DBObject->Prepare('SELECT * FROM afm_fee_collection WHERE feeCollectionID = :|1;');
		$RSFeeCollection->Execute($this->FeeCollectionID);
		
		$FeeCollectionRow = $RSFeeCollection->FetchRow();
		
		$this->SetAttributesFromDB($FeeCollectionRow);				
	}
	
	private function SetAttributesFromDB($FeeCollectionRow)
	{
		$this->FeeCollectionID = $FeeCollectionRow->feeCollectionID;
		$this->StudentID = $FeeCollectionRow->studentID;
		$this->FeeDate = $FeeCollectionRow->feeDate;

		$this->TotalAmount = $FeeCollectionRow->totalAmount;
		$this->TotalDiscount = $FeeCollectionRow->totalDiscount;
		$this->AmountPaid = $FeeCollectionRow->amountPaid;
		$this->PaymentMode = $FeeCollectionRow->paymentMode;
		$this->ChequeReferenceNo = $FeeCollectionRow->chequeReferenceNo;

		$this->CreateUserID = $FeeCollectionRow->createUserID;
		$this->CreateDate = $FeeCollectionRow->createDate;
	}	
}
?>
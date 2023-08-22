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

	private $TransactionAmount;
	private $TotalAmount;
	private $TotalDiscount;
	private $AmountPaid;
	private $PaymentMode;
	private $ChequeReferenceNo;

	private $CreateUserID;
	private $CreateDate;

	private $ParentID;
	private $Description;

	private $PaymentModeDetails = array();
	private $FeeCollectionDetails = array();
	private $OtherChargesDetails = array();
	private $AdvanceFeeDetails = array();
	
	private $CurrentTransactionID;
	
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

			$this->TransactionAmount = 0;
			$this->TotalAmount = 0;
			$this->TotalDiscount = 0;
			$this->AmountPaid = 0;
			$this->PaymentMode = 0;
			$this->ChequeReferenceNo = '';

			$this->CreateUserID = 0;
			$this->CreateDate = '0000-00-00 00:00:00';

			$this->PaymentModeDetails = array();

			$this->ParentID;
			$this->Description;

			$this->FeeCollectionDetails = array();
			$this->OtherChargesDetails = array();
			$this->AdvanceFeeDetails = array();

			$this->CurrentTransactionID = 0;
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

	public function GetTransactionAmount()
	{
		return $this->TransactionAmount;
	}
	public function SetTransactionAmount($TransactionAmount)
	{
		$this->TransactionAmount = $TransactionAmount;
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

	public function GetPaymentModeDetails()
	{
		return $this->PaymentModeDetails;
	}
	public function SetPaymentModeDetails($PaymentModeDetails)
	{
		$this->PaymentModeDetails = $PaymentModeDetails;
	}

	public function GetParentID()
	{
		return $this->ParentID;
	}
	public function SetParentID($ParentID)
	{
		$this->ParentID = $ParentID;
	}

	public function GetDescription()
	{
		return $this->Description;
	}
	public function SetDescription($Description)
	{
		$this->Description = $Description;
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

	public function GetAdvanceFeeDetails()
	{
		return $this->AdvanceFeeDetails;
	}
	public function SetAdvanceFeeDetails($AdvanceFeeDetails)
	{
		$this->AdvanceFeeDetails = $AdvanceFeeDetails;
	}

	public function GetCurrentTransactionID()
	{
		return $this->CurrentTransactionID;
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
	static function SearchChequeTransactionDetails(&$TotalRecords = 0, $GetTotalsOnly = false, $Filters = array(), $Start = 0, $Limit = 100)
	{
		$ChequeTransactionDetails = array();
		
		try
		{
			$DBConnObject = new DBConnect();

			$Conditions = array();
			
			$Conditions[] = 'afpmd.paymentMode = 2';

			$JoinClassSectionTable = ' INNER JOIN asa_class_sections acs ON acs.classSectionID = ass.classSectionID ';

			if (count($Filters) > 0)
			{
				if (!empty($Filters['AcademicYearID']))
				{
					if ($Filters['AcademicYearID'] > 0 && $Filters['AcademicYearID'] < 2) 
					{
						$JoinClassSectionTable = ' INNER JOIN asa_class_sections acs ON acs.classSectionID = spyd.previousClassSectionID ';
					}

					$Conditions[] = 'afs.academicYearID = '. $DBConnObject->RealEscapeVariable($Filters['AcademicYearID']);
				}
				
				if (!empty($Filters['TransactionDate']))
				{
					$Conditions[] = 'afc.feeDate = '. $DBConnObject->RealEscapeVariable($Filters['TransactionDate']);
				}
				
				if (!empty($Filters['TransactionFromDate']))
				{
					$Conditions[] = 'afc.feeDate BETWEEN '. $DBConnObject->RealEscapeVariable($Filters['TransactionFromDate']) .'AND'. $DBConnObject->RealEscapeVariable($Filters['TransactionToDate']);
				}
                
                if (!empty($Filters['ClassID']))
				{
					$Conditions[] = 'ac.classID = '. $DBConnObject->RealEscapeVariable($Filters['ClassID']);
				}

				if (!empty($Filters['ClassSectionID']))
				{
					$Conditions[] = 'ass.classSectionID =  '. $DBConnObject->RealEscapeVariable($Filters['ClassSectionID']) . ' OR spyd.previousClassSectionID = '. $DBConnObject->RealEscapeVariable($Filters['ClassSectionID']);
				}
				
				if (!empty($Filters['StudentID']))
				{
					$Conditions[] = 'asd.studentID = '. $DBConnObject->RealEscapeVariable($Filters['StudentID']);
				}
				
				if (!empty($Filters['StudentName']))
				{
					$Conditions[] = 'CONCAT(asd.firstName, " ", asd.lastName) LIKE ' . $DBConnObject->RealEscapeVariable('%' . $Filters['StudentName'] . '%');
				}

				if (!empty($Filters['ChequeReferenceNo']))
				{
					$Conditions[] = 'afpmd.chequeReferenceNo = '. $DBConnObject->RealEscapeVariable($Filters['ChequeReferenceNo']);
				}

				if (!empty($Filters['ChequeStatus']))
				{
					$Conditions[] = 'afpmd.chequeStatus = '. $DBConnObject->RealEscapeVariable($Filters['ChequeStatus']);
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
				$RSTotal = $DBConnObject->Prepare('SELECT COUNT(Distinct afpmd.feePaymentModeDetailID) AS totalRecords 
													FROM afm_fee_payment_mode_details afpmd
													INNER JOIN afm_fee_transactions aft ON aft.feeTransactionID = afpmd.feeTransactionID
													INNER JOIN afm_fee_collection afc ON afc.feeTransactionID = aft.feeTransactionID

													LEFT JOIN afm_fee_collection_details afcd ON afcd.feeCollectionID = afc.feeCollectionID
                                                    LEFT JOIN afm_fee_collection_other_charges afcoc ON afcoc.feeCollectionID = afc.feeCollectionID
                                                    
                                                    LEFT JOIN afm_student_fee_structure asfs ON asfs.studentFeeStructureID = afcd.studentFeeStructureID 
                                                    
                                                    LEFT JOIN afm_fee_structure_details afsd ON afsd.feeStructureDetailID = asfs.feeStructureDetailID 
                                                    LEFT JOIN afm_fee_structure afs ON afs.feeStructureID = afsd.feeStructureID 
                                                    
                                                    LEFT JOIN asa_academic_years ay ON ay.academicYearID = afs.academicYearID 
                                                    
                                                    INNER JOIN asa_student_details asd ON asd.studentID = afc.studentID 
                                                    LEFT JOIN asa_student_previous_academic_year_details spyd ON spyd.studentID = asd.studentID 
                                                    INNER JOIN asa_students ass ON ass.studentID = asd.studentID 
                                                    '. $JoinClassSectionTable .' 
                                                    INNER JOIN asa_classes ac ON ac.classID = acs.classID 
                                                    INNER JOIN asa_section_master asm ON asm.sectionMasterID = acs.sectionMasterID 
                                                    LEFT JOIN users u ON afpmd.statusChangedBy = u.userID
													'. $QueryString .';');
				$RSTotal->Execute();
				
				$TotalRecords = $RSTotal->FetchRow()->totalRecords;
				return;
			}
			
			$RSSearch = $DBConnObject->Prepare('SELECT afpmd.*, afc.feeDate, u.userName AS statusChangedBy, 
												asd.firstName, asd.lastName, ac.className, asm.sectionName
												FROM afm_fee_payment_mode_details afpmd
												INNER JOIN afm_fee_transactions aft ON aft.feeTransactionID = afpmd.feeTransactionID
												INNER JOIN afm_fee_collection afc ON afc.feeTransactionID = aft.feeTransactionID

												LEFT JOIN afm_fee_collection_details afcd ON afcd.feeCollectionID = afc.feeCollectionID 
												LEFT JOIN afm_fee_collection_other_charges afcoc ON afcoc.feeCollectionID = afc.feeCollectionID
												
												LEFT JOIN afm_student_fee_structure asfs ON asfs.studentFeeStructureID = afcd.studentFeeStructureID 
												LEFT JOIN afm_fee_structure_details afsd ON afsd.feeStructureDetailID = asfs.feeStructureDetailID 
												LEFT JOIN afm_fee_structure afs ON afs.feeStructureID = afsd.feeStructureID 
												
												LEFT JOIN asa_academic_years ay ON ay.academicYearID = afs.academicYearID 
												
												INNER JOIN asa_student_details asd ON asd.studentID = afc.studentID 
												LEFT JOIN asa_student_previous_academic_year_details spyd ON spyd.studentID = asd.studentID 
												INNER JOIN asa_students ass ON ass.studentID = asd.studentID 
												'. $JoinClassSectionTable .' 
												INNER JOIN asa_classes ac ON ac.classID = acs.classID 
												INNER JOIN asa_section_master asm ON asm.sectionMasterID = acs.sectionMasterID 
												LEFT JOIN users u ON afpmd.statusChangedBy = u.userID
												'. $QueryString .'
												GROUP BY afpmd.feePaymentModeDetailID
												ORDER BY afpmd.feePaymentModeDetailID LIMIT ' . (int) $Start . ', ' . (int) $Limit . ';');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $ChequeTransactionDetails; 
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$ChequeTransactionDetails[$SearchRow->feePaymentModeDetailID]['FeeTransactionID'] = $SearchRow->feeTransactionID;
				$ChequeTransactionDetails[$SearchRow->feePaymentModeDetailID]['StudentName'] = $SearchRow->firstName .' '. $SearchRow->lastName;

				$ChequeTransactionDetails[$SearchRow->feePaymentModeDetailID]['ClassName'] = $SearchRow->className;
				$ChequeTransactionDetails[$SearchRow->feePaymentModeDetailID]['SectionName'] = $SearchRow->sectionName;

				$ChequeTransactionDetails[$SearchRow->feePaymentModeDetailID]['FeeDate'] = $SearchRow->feeDate;

				$ChequeTransactionDetails[$SearchRow->feePaymentModeDetailID]['Amount'] = $SearchRow->amount;
				$ChequeTransactionDetails[$SearchRow->feePaymentModeDetailID]['PaymentMode'] = $SearchRow->paymentMode;
				$ChequeTransactionDetails[$SearchRow->feePaymentModeDetailID]['ChequeReferenceNo'] = $SearchRow->chequeReferenceNo;
				$ChequeTransactionDetails[$SearchRow->feePaymentModeDetailID]['ChequeStatus'] = $SearchRow->chequeStatus;
				 			
				$ChequeTransactionDetails[$SearchRow->feePaymentModeDetailID]['StatusChangedBy'] = $SearchRow->statusChangedBy;
				$ChequeTransactionDetails[$SearchRow->feePaymentModeDetailID]['StatusChangedDate'] = $SearchRow->statusChangedDate;
			}
			
			return $ChequeTransactionDetails;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at FeeCollection::SearchChequeTransactionDetails(). Stack Trace: ' . $e->getTraceAsString());
			return $ChequeTransactionDetails;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at FeeCollection::SearchChequeTransactionDetails(). Stack Trace: ' . $e->getTraceAsString());
			return $ChequeTransactionDetails;
		}
	}

	static function UpdateChequeStatus($FeePaymentModeDetailID, $ChequeStatus, $StatusChangedBy, $ChequeBouncedDescription = '')
	{
		try
		{
			$DBConnObject = new DBConnect();
			$DBConnObject->BeginTransaction();

			if ($ChequeStatus == 'Bounced') 
			{
				$RSSearch = $DBConnObject->Prepare('SELECT afpmd.amount, afc.feeCollectionID, afcd.feeCollectionDetailID, afcd.studentFeeStructureID, afcd.amountPaid, afcoc.amount AS otherAmount
													FROM afm_fee_payment_mode_details afpmd
													INNER JOIN afm_fee_transactions aft ON aft.feeTransactionID = afpmd.feeTransactionID 
													INNER JOIN afm_fee_collection afc ON afc.feeTransactionID = aft.feeTransactionID 

													LEFT JOIN afm_fee_collection_details afcd ON afcd.feeCollectionID = afc.feeCollectionID 
													LEFT JOIN afm_fee_collection_other_charges afcoc ON afcoc.feeCollectionID = afc.feeCollectionID

													WHERE afpmd.feePaymentModeDetailID = :|1
													ORDER BY afcd.feeCollectionDetailID DESC;');
				$RSSearch->Execute($FeePaymentModeDetailID);

				if ($RSSearch->Result->num_rows > 0)
				{
					$ChequeAmount = 0;
					$AdjustedAmount = 0;

					while($SearchRow = $RSSearch->FetchRow())
					{
						$UpdateCollectionAmount = 0;

						$ChequeAmount = $SearchRow->amount - $AdjustedAmount;

						if ($SearchRow->amountPaid > $ChequeAmount) 
						{
							$UpdateCollectionAmount = $SearchRow->amountPaid - $ChequeAmount;
						}
						
						$AdjustedAmount += $SearchRow->amountPaid;

						if ($ChequeAmount > 0) 
						{
							$RSUpdateCollectionAmount = $DBConnObject->Prepare('UPDATE afm_fee_collection_details
																SET	amountPaid = :|1
																WHERE feeCollectionDetailID = :|2 LIMIT 1;');
							$RSUpdateCollectionAmount->Execute($UpdateCollectionAmount, $SearchRow->feeCollectionDetailID);
						}
					}
				}
			}

			$RSUpdate = $DBConnObject->Prepare('UPDATE afm_fee_payment_mode_details
												SET	chequeStatus = :|1,
													statusChangedBy = :|2, 
													statusChangedDate = NOW(),
													chequeBouncedDescription = :|3
												WHERE feePaymentModeDetailID = :|4 LIMIT 1;');
			$RSUpdate->Execute($ChequeStatus, $StatusChangedBy, $ChequeBouncedDescription, $FeePaymentModeDetailID);

			$DBConnObject->CommitTransaction();

			return true;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at FeeCollection::UpdateChequeStatus(). Stack Trace: ' . $e->getTraceAsString());
			$DBConnObject->RollBackTransaction();
			return false;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at FeeCollection::UpdateChequeStatus(). Stack Trace: ' . $e->getTraceAsString());
			$DBConnObject->RollBackTransaction();
			return false;
		}
	}

	static function SearchFeeTransactions(&$TotalRecords = 0, $GetTotalsOnly = false, $Filters = array(), $Start = 0, $Limit = 100)
	{
		$FeeTransactionDetails = array();
		
		try
		{
			$DBConnObject = new DBConnect();

			$Conditions = array();
			
			if (count($Filters) > 0)
			{
				if (!empty($Filters['AcademicYearID']))
				{
					$Conditions[] = 'afs.academicYearID = '. $DBConnObject->RealEscapeVariable($Filters['AcademicYearID']);
				}
				
				if (!empty($Filters['TransactionDate']))
				{
					$Conditions[] = 'afc.feeDate = '. $DBConnObject->RealEscapeVariable($Filters['TransactionDate']);
				}
				
				if (!empty($Filters['TransactionFromDate']))
				{
					$Conditions[] = 'afc.feeDate BETWEEN '. $DBConnObject->RealEscapeVariable($Filters['TransactionFromDate']) .'AND'. $DBConnObject->RealEscapeVariable($Filters['TransactionToDate']);
				}
				
				// if (!empty($Filters['FeeHeadID']))
				// {
				// 	$Conditions[] = 'fsd.feeHeadID = '. $DBConnObject->RealEscapeVariable($Filters['FeeHeadID']);
				// }
				
				if (!empty($Filters['MobileNumber']))
				{
					$Conditions[] = 'asd.mobileNumber = '.$DBConnObject->RealEscapeVariable($Filters['MobileNumber']) .' OR apd.fatherMobileNumber = '.$DBConnObject->RealEscapeVariable($Filters['MobileNumber']) . ' OR apd.motherMobileNumber = '.$DBConnObject->RealEscapeVariable($Filters['MobileNumber']);
				}

				if (!empty($Filters['PaymentMode']))
				{
					$Conditions[] = 'afpmd.paymentMode = '. $DBConnObject->RealEscapeVariable($Filters['PaymentMode']);
				}
				
				if (!empty($Filters['Description']))
				{
					$Conditions[] = 'aft.description != "" ';
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
				$RSTotal = $DBConnObject->Prepare('SELECT COUNT(DISTINCT aft.feeTransactionID) AS totalRecords 
													FROM afm_fee_transactions aft
													INNER JOIN afm_fee_payment_mode_details afpmd ON afpmd.feeTransactionID = aft.feeTransactionID
													INNER JOIN afm_fee_collection afc ON afc.feeTransactionID = aft.feeTransactionID

													LEFT JOIN afm_fee_collection_details afcd ON afcd.feeCollectionID = afc.feeCollectionID
	                                                LEFT JOIN afm_student_fee_structure asfs ON asfs.studentFeeStructureID = afcd.studentFeeStructureID 
	                                                LEFT JOIN afm_fee_structure_details afsd ON afsd.feeStructureDetailID = asfs.feeStructureDetailID 
	                                                LEFT JOIN afm_fee_structure afs ON afs.feeStructureID = afsd.feeStructureID 
                                                    
                                                    INNER JOIN asa_student_details asd ON asd.studentID = afc.studentID     
													INNER JOIN asa_students ass ON ass.studentID = afc.studentID
													INNER JOIN asa_parent_details apd ON apd.parentID = ass.parentID
													
                                                    INNER JOIN users u ON aft.createUserID = u.userID
													'. $QueryString .';');
				$RSTotal->Execute();
				
				$TotalRecords = $RSTotal->FetchRow()->totalRecords;
				return;
			}
			
			$RSSearch = $DBConnObject->Prepare('SELECT aft.feeTransactionID, aft.description, aft.createDate, aft.transactionAmount AS totalTransactionAmount, afc.feeDate, apd.fatherFirstName, apd.fatherLastName, u.userName AS createUserName, apd.fatherMobileNumber, apd.motherMobileNumber, asd.mobileNumber 
												FROM afm_fee_transactions aft
												INNER JOIN afm_fee_payment_mode_details afpmd ON afpmd.feeTransactionID = aft.feeTransactionID
												INNER JOIN afm_fee_collection afc ON afc.feeTransactionID = aft.feeTransactionID

												LEFT JOIN afm_fee_collection_details afcd ON afcd.feeCollectionID = afc.feeCollectionID
                                                LEFT JOIN afm_student_fee_structure asfs ON asfs.studentFeeStructureID = afcd.studentFeeStructureID 
                                                LEFT JOIN afm_fee_structure_details afsd ON afsd.feeStructureDetailID = asfs.feeStructureDetailID 
                                                LEFT JOIN afm_fee_structure afs ON afs.feeStructureID = afsd.feeStructureID 
                                                
                                                INNER JOIN asa_student_details asd ON asd.studentID = afc.studentID 
												INNER JOIN asa_students ass ON ass.studentID = afc.studentID
												INNER JOIN asa_parent_details apd ON apd.parentID = ass.parentID
												
                                                INNER JOIN users u ON aft.createUserID = u.userID
												'. $QueryString .'

												GROUP BY aft.feeTransactionID
												ORDER BY afc.feeDate LIMIT ' . (int) $Start . ', ' . (int) $Limit . ';');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $FeeTransactionDetails; 
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$FeeTransactionDetails[$SearchRow->feeTransactionID]['FatherName'] = $SearchRow->fatherFirstName .' '. $SearchRow->fatherLastName;
				$FeeTransactionDetails[$SearchRow->feeTransactionID]['FatherMobileNumber'] = $SearchRow->fatherMobileNumber;
				$FeeTransactionDetails[$SearchRow->feeTransactionID]['FeeDate'] = $SearchRow->feeDate;

				$FeeTransactionDetails[$SearchRow->feeTransactionID]['TransactionAmount'] = $SearchRow->totalTransactionAmount;
				$FeeTransactionDetails[$SearchRow->feeTransactionID]['Description'] = $SearchRow->description;
				 			
				$FeeTransactionDetails[$SearchRow->feeTransactionID]['CreateUserName'] = $SearchRow->createUserName;
				$FeeTransactionDetails[$SearchRow->feeTransactionID]['CreateDate'] = $SearchRow->createDate;
				$FeeTransactionDetails[$SearchRow->feeTransactionID]['PaymentModeDetails'] = array();
				$FeeTransactionDetails[$SearchRow->feeTransactionID]['StudentDetails'] = array();

				$RSSearchStudents = $DBConnObject->Prepare('SELECT sd.studentID, sd.firstName, sd.lastName FROM asa_student_details sd
				                                            INNER JOIN afm_fee_collection fc ON fc.studentID = sd.studentID
				                                            WHERE fc.feeTransactionID = :|1;');
				$RSSearchStudents->Execute($SearchRow->feeTransactionID);

				if ($RSSearchStudents->Result->num_rows > 0)
				{
					while($SearchStudentRow = $RSSearchStudents->FetchRow())
					{
						$FeeTransactionDetails[$SearchRow->feeTransactionID]['StudentDetails'][$SearchStudentRow->studentID]['FirstName'] = $SearchStudentRow->firstName;
						$FeeTransactionDetails[$SearchRow->feeTransactionID]['StudentDetails'][$SearchStudentRow->studentID]['LastName'] = $SearchStudentRow->lastName;
					}
				}
				
				$RSSearchPaymentMode = $DBConnObject->Prepare('SELECT * FROM afm_fee_payment_mode_details WHERE feeTransactionID = :|1;');
				$RSSearchPaymentMode->Execute($SearchRow->feeTransactionID);

				if ($RSSearchPaymentMode->Result->num_rows > 0)
				{
					while($SearchPaymentModeRow = $RSSearchPaymentMode->FetchRow())
					{
						$FeeTransactionDetails[$SearchRow->feeTransactionID]['PaymentModeDetails'][$SearchPaymentModeRow->paymentMode] = $SearchPaymentModeRow->amount;
					}
				}
			}
			
			return $FeeTransactionDetails;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at FeeCollection::SearchFeeTransactions(). Stack Trace: ' . $e->getTraceAsString());
			return $FeeTransactionDetails;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at FeeCollection::SearchFeeTransactions(). Stack Trace: ' . $e->getTraceAsString());
			return $FeeTransactionDetails;
		}
	}

	static function MonthlyFeeDueDetails(&$TotalRecords = 0, $GetTotalsOnly = false, $Filters = array(), &$OverAllSummary = array(), $Start = 0, $Limit = 100)
	{
		$MonthlyFeeDueDetails = array();
		$OverAllSummary = array();
		
		try
		{
			$DBConnObject = new DBConnect();

			$Conditions = array();
			$HavingCondition = '';

			$JoinClassSectionTable = ' INNER JOIN asa_class_sections acs ON acs.classSectionID = ass.classSectionID ';
			
			if (count($Filters) > 0)
			{
				if (!empty($Filters['AcademicYearID']))
				{
					if ($Filters['AcademicYearID'] > 0 && $Filters['AcademicYearID'] < 2 && !empty($Filters['Status']) && $Filters['Status'] == 'Active') 
					{
						$JoinClassSectionTable = ' INNER JOIN asa_class_sections acs ON acs.classSectionID = spyd.previousClassSectionID ';
					}

					$Conditions[] = 'fs.academicYearID = '. $DBConnObject->RealEscapeVariable($Filters['AcademicYearID']);
				}
				
				if (!empty($Filters['ClassID']))
				{
					$Conditions[] = 'ac.classID = '. $DBConnObject->RealEscapeVariable($Filters['ClassID']);
				}

				if (!empty($Filters['ClassSectionID']))
				{
					$Conditions[] = 'ass.classSectionID = '. $DBConnObject->RealEscapeVariable($Filters['ClassSectionID']) . ' OR spyd.previousClassSectionID = '. $DBConnObject->RealEscapeVariable($Filters['ClassSectionID']);
				}

				if (!empty($Filters['FeeHeadID']))
				{
					$Conditions[] = 'fsd.feeHeadID = '. $DBConnObject->RealEscapeVariable($Filters['FeeHeadID']);
				}
				
				if (!empty($Filters['TransactionDate']))
				{
					$Conditions[] = 'fc.feeDate = '. $DBConnObject->RealEscapeVariable($Filters['TransactionDate']);
				}
				
				if (!empty($Filters['TransactionFromDate']))
				{
					$Conditions[] = 'fc.feeDate BETWEEN '. $DBConnObject->RealEscapeVariable($Filters['TransactionFromDate']) .'AND'. $DBConnObject->RealEscapeVariable($Filters['TransactionToDate']);
				}

				if (count($Filters['MonthList']) > 0)
				{
					$Conditions[] = 'fsd.academicYearMonthID IN ('. implode(', ', $Filters['MonthList']) .')';
				}
				
				if (!empty($Filters['Status']) && $Filters['Status'] == 'Active')
				{
					$Conditions[] = 'ass.status = \'Active\'';
				}
				
				if (!empty($Filters['Status']) && $Filters['Status'] == 'InActive')
				{
					$Conditions[] = 'ass.status != \'Active\'';
				}
				
				if ($Filters['ReportBy'] == 1)
				{
					$HavingCondition = 'HAVING (secondDiscountValue IS NULL AND firstDiscountValue IS NULL AND totalConcession IS NULL AND totalWaveOff IS NULL AND totalAmountPaid > 0) OR (CASE WHEN secondDiscountValue > 0 THEN ((totalAmountPayable - (secondDiscountValue + totalConcession + totalWaveOff)) >= 0) ELSE (CASE WHEN firstDiscountValue > 0 THEN ((totalAmountPayable - (firstDiscountValue + totalConcession + totalWaveOff)) >= 0) ELSE (totalAmountPayable - (totalConcession + totalWaveOff) >= 0) END) END)
                                        AND ( (totalAmountPaid = (CASE WHEN secondDiscountValue > 0 THEN (totalAmountPayable - (secondDiscountValue + totalConcession + totalWaveOff)) ELSE (CASE WHEN firstDiscountValue > 0 THEN (totalAmountPayable - (firstDiscountValue + totalConcession + totalWaveOff)) ELSE (totalAmountPayable - (totalConcession + totalWaveOff)) END) END))) AND totalAmountPaid > 0';
				}
				
				if ($Filters['ReportBy'] == 2)
				{
					$HavingCondition = 'HAVING totalAmountPaid > 0';
				}
				
				if ($Filters['ReportBy'] == 3)
				{
				// 	$HavingCondition = 'HAVING (CASE WHEN secondDiscountValue > 0 THEN ((totalAmountPayable - (secondDiscountValue)) > 0) ELSE (CASE WHEN firstDiscountValue > 0 THEN ((totalAmountPayable - (firstDiscountValue)) > 0) ELSE (totalAmountPayable > 0) END) END)
                                        // AND (totalAmountPaid IS NULL OR (totalAmountPaid < (CASE WHEN secondDiscountValue > 0 THEN (totalAmountPayable - (secondDiscountValue )) ELSE (CASE WHEN firstDiscountValue > 0 THEN (totalAmountPayable - (firstDiscountValue)) ELSE (totalAmountPayable) END) END)))';
                                        
                    $HavingCondition = 'HAVING (secondDiscountValue IS NULL AND firstDiscountValue IS NULL AND totalConcession IS NULL AND totalWaveOff IS NULL AND totalAmountPaid IS NULL) OR (CASE WHEN secondDiscountValue > 0 THEN ((totalAmountPayable - (secondDiscountValue + totalConcession + totalWaveOff)) > 0) ELSE (CASE WHEN firstDiscountValue > 0 THEN ((totalAmountPayable - (firstDiscountValue + totalConcession + totalWaveOff)) > 0) ELSE (totalAmountPayable - (totalConcession + totalWaveOff) > 0) END) END)
                                        AND (totalAmountPaid IS NULL OR (totalAmountPaid < (CASE WHEN secondDiscountValue > 0 THEN (totalAmountPayable - (secondDiscountValue + totalConcession + totalWaveOff)) ELSE (CASE WHEN firstDiscountValue > 0 THEN (totalAmountPayable - (firstDiscountValue + totalConcession + totalWaveOff)) ELSE (totalAmountPayable - (totalConcession + totalWaveOff)) END) END)))';
				}
				
				if ($Filters['ReportBy'] == 4)
				{
					$HavingCondition = 'HAVING secondDiscountValue > 0 OR firstDiscountValue > 0';
				}
				
				if ($Filters['ReportBy'] == 5)
				{
					$HavingCondition = 'HAVING totalConcession > 0';
				}
				
				if ($Filters['ReportBy'] == 6)
				{
					$HavingCondition = 'HAVING totalWaveOff > 0';
				}
				
				if (!empty($Filters['MobileNumber']))
				{
					$Conditions[] = 'asd.mobileNumber = '.$DBConnObject->RealEscapeVariable($Filters['MobileNumber']) .' OR apd.fatherMobileNumber = '.$DBConnObject->RealEscapeVariable($Filters['MobileNumber']) . ' OR apd.motherMobileNumber = '.$DBConnObject->RealEscapeVariable($Filters['MobileNumber']);
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
				$RSTotal = $DBConnObject->Prepare('SELECT COUNT(asd.studentID) AS totalRecords,
				                                    SUM(sfs.amountPayable) AS totalAmountPayable, 
													SUM(fcd.amountPaid) As totalAmountPaid,
													SUM(CASE WHEN afd.discountType = "Absolute" THEN afd.discountValue ELSE (sfs.amountPayable * afd.discountValue) / 100 END) AS firstDiscountValue, 
													SUM(CASE WHEN afd1.discountType = "Absolute" THEN afd1.discountValue ELSE (sfs.amountPayable * afd1.discountValue) / 100 END) AS secondDiscountValue,
													SUM(afd1.concessionAmount) AS totalConcession,
													SUM(afd1.waveOffAmount) AS totalWaveOff

													FROM afm_student_fee_structure sfs

													INNER JOIN afm_fee_structure_details fsd ON fsd.feeStructureDetailID = sfs.feeStructureDetailID 
													INNER JOIN afm_fee_structure fs ON fs.feeStructureID = fsd.feeStructureID 
													INNER JOIN asa_academic_years ay ON ay.academicYearID = fs.academicYearID 

													LEFT JOIN (SELECT feeCollectionDetailID, feeCollectionID, studentFeeStructureID, SUM(amountPaid) AS amountPaid FROM afm_fee_collection_details GROUP BY studentFeeStructureID) AS fcd ON fcd.studentFeeStructureID = sfs.studentFeeStructureID
													LEFT JOIN afm_fee_collection fc ON fc.feeCollectionID = fcd.feeCollectionID 

													LEFT JOIN afm_fee_discounts afd ON afd.feeGroupID = fs.feeGroupID AND afd.feeStructureDetailID = fsd.feeStructureDetailID AND afd.feeDiscountType = \'Group\' 
													LEFT JOIN afm_fee_discounts afd1 ON afd1.studentID = sfs.studentID AND afd1.feeStructureDetailID = fsd.feeStructureDetailID AND afd1.feeDiscountType = \'Student\' 

													INNER JOIN asa_student_details asd ON asd.studentID = sfs.studentID 
													LEFT JOIN asa_student_previous_academic_year_details spyd ON spyd.studentID = asd.studentID
													INNER JOIN asa_students ass ON ass.studentID = asd.studentID 
													
													INNER JOIN asa_parent_details apd ON apd.parentID = ass.parentID 
													'. $JoinClassSectionTable .' 
													INNER JOIN asa_classes ac ON ac.classID = acs.classID 
													INNER JOIN asa_section_master asm ON asm.sectionMasterID = acs.sectionMasterID
													'. $QueryString .'
													GROUP BY asd.studentID
													'. $HavingCondition .';');
				$RSTotal->Execute();
				
				$TotalRecords = $RSTotal->Result->num_rows;
				return;
			}


			$RSSearchSummary = $DBConnObject->Prepare('SELECT fh.feeHeadID, fh.feeHead,
																SUM(sfs.amountPayable) AS totalAmountPayable, 
																SUM(fcd.amountPaid) As totalAmountPaid,
																SUM(CASE WHEN afd.discountType = "Absolute" THEN afd.discountValue ELSE (sfs.amountPayable * afd.discountValue) / 100 END) AS firstDiscountValue, 
																SUM(CASE WHEN afd1.discountType = "Absolute" THEN afd1.discountValue ELSE (sfs.amountPayable * afd1.discountValue) / 100 END) AS secondDiscountValue,
																SUM(afd1.concessionAmount) AS totalConcession,
																SUM(afd1.waveOffAmount) AS totalWaveOff
																FROM afm_student_fee_structure sfs

																INNER JOIN afm_fee_structure_details fsd ON fsd.feeStructureDetailID = sfs.feeStructureDetailID 
																INNER JOIN afm_fee_structure fs ON fs.feeStructureID = fsd.feeStructureID 
																INNER JOIN afm_fee_heads fh ON fh.feeHeadID = fsd.feeHeadID
																INNER JOIN asa_academic_years ay ON ay.academicYearID = fs.academicYearID 

																LEFT JOIN (SELECT feeCollectionDetailID, feeCollectionID, studentFeeStructureID, SUM(amountPaid) AS amountPaid FROM afm_fee_collection_details GROUP BY studentFeeStructureID) AS fcd ON fcd.studentFeeStructureID = sfs.studentFeeStructureID
																LEFT JOIN afm_fee_collection fc ON fc.feeCollectionID = fcd.feeCollectionID 

																LEFT JOIN afm_fee_discounts afd ON afd.feeGroupID = fs.feeGroupID AND afd.feeStructureDetailID = fsd.feeStructureDetailID AND afd.feeDiscountType = \'Group\' 
																LEFT JOIN afm_fee_discounts afd1 ON afd1.studentID = sfs.studentID AND afd1.feeStructureDetailID = fsd.feeStructureDetailID AND afd1.feeDiscountType = \'Student\' 

																INNER JOIN asa_student_details asd ON asd.studentID = sfs.studentID 
																LEFT JOIN asa_student_previous_academic_year_details spyd ON spyd.studentID = asd.studentID
																INNER JOIN asa_students ass ON ass.studentID = asd.studentID 
																INNER JOIN asa_parent_details apd ON apd.parentID = ass.parentID 
																'. $JoinClassSectionTable .' 
																INNER JOIN asa_classes ac ON ac.classID = acs.classID 
																INNER JOIN asa_section_master asm ON asm.sectionMasterID = acs.sectionMasterID
																'. $QueryString .'
																GROUP BY fh.feeHeadID
																ORDER BY fh.priority;');
			$RSSearchSummary->Execute();

			if ($RSSearchSummary->Result->num_rows > 0)
			{
				while($SearchSummaryRow = $RSSearchSummary->FetchRow())
				{
					$DiscountValue = 0;

					if ($SearchSummaryRow->secondDiscountValue > 0) 
					{
						$DiscountValue = $SearchSummaryRow->secondDiscountValue;
					}
					else
					{
						$DiscountValue = $SearchSummaryRow->firstDiscountValue;
					}

					$OverAllSummary[$SearchSummaryRow->feeHeadID]['FeeHead'] = $SearchSummaryRow->feeHead;
					$OverAllSummary[$SearchSummaryRow->feeHeadID]['TotalAmount'] = $SearchSummaryRow->totalAmountPayable;

					$OverAllSummary[$SearchSummaryRow->feeHeadID]['TotalDiscount'] = $DiscountValue;
					$OverAllSummary[$SearchSummaryRow->feeHeadID]['TotalConcessionAmount'] = $SearchSummaryRow->totalConcession;
					$OverAllSummary[$SearchSummaryRow->feeHeadID]['TotalWaveOffAmount'] = $SearchSummaryRow->totalWaveOff;

					$OverAllSummary[$SearchSummaryRow->feeHeadID]['TotalPaidAmount'] = $SearchSummaryRow->totalAmountPaid;
					$OverAllSummary[$SearchSummaryRow->feeHeadID]['TotalDueAmount'] = $SearchSummaryRow->totalAmountPayable - $SearchSummaryRow->totalAmountPaid - $DiscountValue - $SearchSummaryRow->totalConcession - $SearchSummaryRow->totalWaveOff;
				}
			}

			$RSSearchStudentFeeDetails = $DBConnObject->Prepare('SELECT apd.fatherMobileNumber, asd.studentID, asd.firstName, asd.lastName, ac.classID, ac.className, asm.sectionName, CONCAT( YEAR(ay.startDate), \'-\', DATE_FORMAT(ay.endDate, \'%y\')) AS academicYearName,
																SUM(sfs.amountPayable) AS totalAmountPayable, 
																SUM(fcd.amountPaid) As totalAmountPaid,
																SUM(CASE WHEN afd.discountType = "Absolute" THEN afd.discountValue ELSE (sfs.amountPayable * afd.discountValue) / 100 END) AS firstDiscountValue, 
																SUM(CASE WHEN afd1.discountType = "Absolute" THEN afd1.discountValue ELSE (sfs.amountPayable * afd1.discountValue) / 100 END) AS secondDiscountValue,
																SUM(afd1.concessionAmount) AS totalConcession,
																SUM(afd1.waveOffAmount) AS totalWaveOff

																FROM afm_student_fee_structure sfs

																INNER JOIN afm_fee_structure_details fsd ON fsd.feeStructureDetailID = sfs.feeStructureDetailID 
																INNER JOIN afm_fee_structure fs ON fs.feeStructureID = fsd.feeStructureID 
																INNER JOIN asa_academic_years ay ON ay.academicYearID = fs.academicYearID 

																LEFT JOIN (SELECT feeCollectionDetailID, feeCollectionID, studentFeeStructureID, SUM(amountPaid) AS amountPaid FROM afm_fee_collection_details GROUP BY studentFeeStructureID) AS fcd ON fcd.studentFeeStructureID = sfs.studentFeeStructureID
																LEFT JOIN afm_fee_collection fc ON fc.feeCollectionID = fcd.feeCollectionID 

																LEFT JOIN afm_fee_discounts afd ON afd.feeGroupID = fs.feeGroupID AND afd.feeStructureDetailID = fsd.feeStructureDetailID AND afd.feeDiscountType = \'Group\' 
																LEFT JOIN afm_fee_discounts afd1 ON afd1.studentID = sfs.studentID AND afd1.feeStructureDetailID = fsd.feeStructureDetailID AND afd1.feeDiscountType = \'Student\' 

																INNER JOIN asa_student_details asd ON asd.studentID = sfs.studentID 
																LEFT JOIN asa_student_previous_academic_year_details spyd ON spyd.studentID = asd.studentID
																INNER JOIN asa_students ass ON ass.studentID = asd.studentID 
																INNER JOIN asa_parent_details apd ON apd.parentID = ass.parentID
																'. $JoinClassSectionTable .' 
																INNER JOIN asa_classes ac ON ac.classID = acs.classID 
																INNER JOIN asa_section_master asm ON asm.sectionMasterID = acs.sectionMasterID
																'. $QueryString .'
																GROUP BY asd.studentID
																'. $HavingCondition .'
																ORDER BY ac.priority, acs.priority, asd.firstName, asd.lastName;');
			$RSSearchStudentFeeDetails->Execute();

			if ($RSSearchStudentFeeDetails->Result->num_rows > 0)
			{
				while($SearchRow = $RSSearchStudentFeeDetails->FetchRow())
				{
					$DiscountValue = 0;

					if ($SearchRow->secondDiscountValue > 0) 
					{
						$DiscountValue = $SearchRow->secondDiscountValue;
					}
					else
					{
						$DiscountValue = $SearchRow->firstDiscountValue;
					}
					
					// $MonthlyFeeDueDetails['AcademicYearName'] = $SearchRow->academicYearName;
					$MonthlyFeeDueDetails[$SearchRow->studentID]['StudentName'] = $SearchRow->firstName .' '. $SearchRow->lastName;
					$MonthlyFeeDueDetails[$SearchRow->studentID]['ClassID'] = $SearchRow->classID;
					$MonthlyFeeDueDetails[$SearchRow->studentID]['ClassName'] = $SearchRow->className;
					$MonthlyFeeDueDetails[$SearchRow->studentID]['SectionName'] = $SearchRow->sectionName;
					$MonthlyFeeDueDetails[$SearchRow->studentID]['FatherMobileNumber'] = $SearchRow->fatherMobileNumber;

					$MonthlyFeeDueDetails[$SearchRow->studentID]['TotalAmount'] = $SearchRow->totalAmountPayable;
					$MonthlyFeeDueDetails[$SearchRow->studentID]['DiscountAmount'] = $DiscountValue;
					$MonthlyFeeDueDetails[$SearchRow->studentID]['TotalConcession'] = $SearchRow->totalConcession;
					$MonthlyFeeDueDetails[$SearchRow->studentID]['TotalWaveOff'] = $SearchRow->totalWaveOff;

					$MonthlyFeeDueDetails[$SearchRow->studentID]['PaidAmount'] = $SearchRow->totalAmountPaid;
					$MonthlyFeeDueDetails[$SearchRow->studentID]['DueAmount'] = $SearchRow->totalAmountPayable - $SearchRow->totalAmountPaid - $DiscountValue - $SearchRow->totalConcession - $SearchRow->totalWaveOff;
				}
			}
			
			return $MonthlyFeeDueDetails;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at FeeCollection::MonthlyFeeDueDetails(). Stack Trace: ' . $e->getTraceAsString());
			return $MonthlyFeeDueDetails;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at FeeCollection::MonthlyFeeDueDetails(). Stack Trace: ' . $e->getTraceAsString());
			return $MonthlyFeeDueDetails;
		}
	}

	static function GetFeeDetailsByStudent($StudentID, $AcademicYearID, $MonthList = array())
	{
		$FeeDetails = array();
		try
		{
			$DBConnObject = new DBConnect();

			$MonthString = '';

			if (count($MonthList) > 0)
			{
				$MonthString = ' AND fsd.academicYearMonthID IN ('. implode(', ', $MonthList) .')';
			}

			$RSFeeDetails = $DBConnObject->Prepare('SELECT fh.feeHead, fh.feeHeadID, aym.monthName, 
													SUM(sfs.amountPayable) AS totalAmountPayable, 
													SUM(fcd.amountPaid) As totalAmountPaid,
													SUM(CASE WHEN afd.discountType = "Absolute" THEN afd.discountValue ELSE (sfs.amountPayable * afd.discountValue) / 100 END) AS firstDiscountValue, 
													SUM(CASE WHEN afd1.discountType = "Absolute" THEN afd1.discountValue ELSE (sfs.amountPayable * afd1.discountValue) / 100 END) AS secondDiscountValue,
													SUM(afd1.concessionAmount) AS totalConcession,
													SUM(afd1.waveOffAmount) AS totalWaveOff

													FROM afm_student_fee_structure sfs

													INNER JOIN afm_fee_structure_details fsd ON fsd.feeStructureDetailID = sfs.feeStructureDetailID 
													INNER JOIN afm_fee_structure fs ON fs.feeStructureID = fsd.feeStructureID 
													INNER JOIN afm_fee_heads fh ON fh.feeHeadID = fsd.feeHeadID
													INNER JOIN asa_academic_year_months aym ON aym.academicYearMonthID = fsd.academicYearMonthID
													INNER JOIN asa_academic_years ay ON ay.academicYearID = fs.academicYearID 

													LEFT JOIN (SELECT feeCollectionDetailID, feeCollectionID, studentFeeStructureID, SUM(amountPaid) AS amountPaid FROM afm_fee_collection_details GROUP BY studentFeeStructureID) AS fcd ON fcd.studentFeeStructureID = sfs.studentFeeStructureID
													LEFT JOIN afm_fee_collection fc ON fc.feeCollectionID = fcd.feeCollectionID 

													LEFT JOIN afm_fee_discounts afd ON afd.feeGroupID = fs.feeGroupID AND afd.feeStructureDetailID = fsd.feeStructureDetailID AND afd.feeDiscountType = \'Group\' 
													LEFT JOIN afm_fee_discounts afd1 ON afd1.studentID = sfs.studentID AND afd1.feeStructureDetailID = fsd.feeStructureDetailID AND afd1.feeDiscountType = \'Student\' 

													WHERE sfs.studentID = :|1 AND fs.academicYearID = :|2 '. $MonthString .'
													GROUP BY fh.feeHeadID, fsd.academicYearMonthID
													ORDER BY aym.feePriority, fh.priority;');
			$RSFeeDetails->Execute($StudentID, $AcademicYearID);

			if ($RSFeeDetails->Result->num_rows <= 0)
			{
				return $FeeDetails;
			}

			while($SearchRow = $RSFeeDetails->FetchRow())
			{
				$DiscountValue = 0;

				if ($SearchRow->secondDiscountValue > 0) 
				{
					$DiscountValue = $SearchRow->secondDiscountValue;
				}
				else
				{
					$DiscountValue = $SearchRow->firstDiscountValue;
				}

				$FeeDetails[$SearchRow->monthName][$SearchRow->feeHeadID]['FeeHead'] = $SearchRow->feeHead;
				$FeeDetails[$SearchRow->monthName][$SearchRow->feeHeadID]['TotalAmount'] = $SearchRow->totalAmountPayable;
				$FeeDetails[$SearchRow->monthName][$SearchRow->feeHeadID]['DiscountAmount'] = $DiscountValue;
				$FeeDetails[$SearchRow->monthName][$SearchRow->feeHeadID]['TotalConcession'] = $SearchRow->totalConcession;
				$FeeDetails[$SearchRow->monthName][$SearchRow->feeHeadID]['TotalWaveOff'] = $SearchRow->totalWaveOff;

				$FeeDetails[$SearchRow->monthName][$SearchRow->feeHeadID]['PaidAmount'] = $SearchRow->totalAmountPaid;
				$FeeDetails[$SearchRow->monthName][$SearchRow->feeHeadID]['DueAmount'] = $SearchRow->totalAmountPayable - $SearchRow->totalAmountPaid - $DiscountValue - $SearchRow->totalConcession - $SearchRow->totalWaveOff;
			}

			return $FeeDetails;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at FeeCollection::GetFeeDetailsByStudent(). Stack Trace: ' . $e->getTraceAsString());
			return $FeeDetails;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at FeeCollection::GetFeeDetailsByStudent(). Stack Trace: ' . $e->getTraceAsString());
			return $FeeDetails;
		}
	}

	static function SearchFeeCollectionDetails(&$TotalRecords = 0, $GetTotalsOnly = false, $Filters = array(), $Start = 0, $Limit = 100)
	{
		$FeeCollectionDetails = array();
		
		try
		{
			$DBConnObject = new DBConnect();

			$Conditions = array();

			$JoinClassSectionTable = ' INNER JOIN asa_class_sections acs ON acs.classSectionID = ass.classSectionID ';
			
			if (count($Filters) > 0)
			{
				if (!empty($Filters['AcademicYearID']))
				{
					if ($Filters['AcademicYearID'] > 0 && $Filters['AcademicYearID'] < 2 && !empty($Filters['Status']) && $Filters['Status'] == 'Active') 
					{
						$JoinClassSectionTable = ' INNER JOIN asa_class_sections acs ON acs.classSectionID = spyd.previousClassSectionID ';
					}

					$Conditions[] = 'afs.academicYearID = '. $DBConnObject->RealEscapeVariable($Filters['AcademicYearID']);
				}
				
				if (!empty($Filters['TransactionDate']))
				{
					$Conditions[] = 'afc.feeDate = '. $DBConnObject->RealEscapeVariable($Filters['TransactionDate']);
				}
				
				if (!empty($Filters['TransactionFromDate']))
				{
					$Conditions[] = 'afc.feeDate BETWEEN '. $DBConnObject->RealEscapeVariable($Filters['TransactionFromDate']) .'AND'. $DBConnObject->RealEscapeVariable($Filters['TransactionToDate']);
				}
                
                if (!empty($Filters['ClassID']))
				{
					$Conditions[] = 'ac.classID = '. $DBConnObject->RealEscapeVariable($Filters['ClassID']);
				}

				if (!empty($Filters['ClassSectionID']))
				{
					$Conditions[] = 'ass.classSectionID =  '. $DBConnObject->RealEscapeVariable($Filters['ClassSectionID']) . ' OR spyd.previousClassSectionID = '. $DBConnObject->RealEscapeVariable($Filters['ClassSectionID']);
				}
				
				if (!empty($Filters['StudentID']))
				{
					$Conditions[] = 'asd.studentID = '. $DBConnObject->RealEscapeVariable($Filters['StudentID']);
				}
				
				if (!empty($Filters['FeeHeadID']))
				{
					$Conditions[] = 'afsd.feeHeadID = '. $DBConnObject->RealEscapeVariable($Filters['FeeHeadID']);
				}
				
				if (!empty($Filters['Status']) && $Filters['Status'] == 'Active')
				{
					$Conditions[] = 'ass.status = \'Active\'';
				}
				
				if (!empty($Filters['Status']) && $Filters['Status'] == 'InActive')
				{
					$Conditions[] = 'ass.status != \'Active\'';
				}
				
				if (!empty($Filters['StudentName']))
				{
					$Conditions[] = 'CONCAT(asd.firstName, " ", asd.lastName) LIKE ' . $DBConnObject->RealEscapeVariable('%' . $Filters['StudentName'] . '%');
				}
				
				if (!empty($Filters['MobileNumber']))
				{
					$Conditions[] = 'asd.mobileNumber = '.$DBConnObject->RealEscapeVariable($Filters['MobileNumber']) .' OR apd.fatherMobileNumber = '.$DBConnObject->RealEscapeVariable($Filters['MobileNumber']) . ' OR apd.motherMobileNumber = '.$DBConnObject->RealEscapeVariable($Filters['MobileNumber']);
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
													LEFT JOIN afm_fee_collection_details afcd ON afcd.feeCollectionID = afc.feeCollectionID
                                                    LEFT JOIN afm_fee_collection_other_charges afcoc ON afcoc.feeCollectionID = afc.feeCollectionID
                                                    
                                                    LEFT JOIN afm_student_fee_structure asfs ON asfs.studentFeeStructureID = afcd.studentFeeStructureID 
                                                    
                                                    LEFT JOIN afm_fee_structure_details afsd ON afsd.feeStructureDetailID = asfs.feeStructureDetailID 
                                                    LEFT JOIN afm_fee_structure afs ON afs.feeStructureID = afsd.feeStructureID 
                                                    
                                                    LEFT JOIN asa_academic_years ay ON ay.academicYearID = afs.academicYearID 
                                                    
                                                    INNER JOIN asa_student_details asd ON asd.studentID = afc.studentID 
                                                    LEFT JOIN asa_student_previous_academic_year_details spyd ON spyd.studentID = asd.studentID 
                                                    INNER JOIN asa_students ass ON ass.studentID = asd.studentID 
                                                    INNER JOIN asa_parent_details apd ON apd.parentID = ass.parentID 
                                                    
                                                    '. $JoinClassSectionTable .' 
                                                    INNER JOIN asa_classes ac ON ac.classID = acs.classID 
                                                    INNER JOIN asa_section_master asm ON asm.sectionMasterID = acs.sectionMasterID 
                                                    INNER JOIN users u ON afc.createUserID = u.userID
													'. $QueryString .';');
				$RSTotal->Execute();
				
				$TotalRecords = $RSTotal->FetchRow()->totalRecords;
				return;
			}
			
			$RSSearch = $DBConnObject->Prepare('SELECT afc.feeTransactionID, afc.feeCollectionID, afc.feeDate, afc.totalAmount, afc.totalDiscount, afc.amountPaid, afc.createDate, afpmd.paymentMode, CONCAT( YEAR(ay.startDate), \'-\', DATE_FORMAT(ay.endDate, \'%y\')) AS academicYearName, u.userName AS createUserName, 
												asd.firstName, asd.lastName, ac.className, asm.sectionName, afcoc.amount, aft.description, apd.fatherMobileNumber, apd.motherMobileNumber, asd.mobileNumber 
												FROM afm_fee_collection afc
                                                
                                                INNER JOIN afm_fee_transactions aft ON aft.feeTransactionID = afc.feeTransactionID
												INNER JOIN afm_fee_payment_mode_details afpmd ON afpmd.feeTransactionID = afc.feeTransactionID
												LEFT JOIN afm_fee_collection_details afcd ON afcd.feeCollectionID = afc.feeCollectionID 
												
												LEFT JOIN afm_fee_collection_other_charges afcoc ON afcoc.feeCollectionID = afc.feeCollectionID
												
												LEFT JOIN afm_student_fee_structure asfs ON asfs.studentFeeStructureID = afcd.studentFeeStructureID 
												LEFT JOIN afm_fee_structure_details afsd ON afsd.feeStructureDetailID = asfs.feeStructureDetailID 
												LEFT JOIN afm_fee_structure afs ON afs.feeStructureID = afsd.feeStructureID 
												
												LEFT JOIN asa_academic_years ay ON ay.academicYearID = afs.academicYearID 
												
												INNER JOIN asa_student_details asd ON asd.studentID = afc.studentID 
												LEFT JOIN asa_student_previous_academic_year_details spyd ON spyd.studentID = asd.studentID 
												INNER JOIN asa_students ass ON ass.studentID = asd.studentID 
												INNER JOIN asa_parent_details apd ON apd.parentID = ass.parentID 
												
												'. $JoinClassSectionTable .' 
												INNER JOIN asa_classes ac ON ac.classID = acs.classID 
												INNER JOIN asa_section_master asm ON asm.sectionMasterID = acs.sectionMasterID 
												INNER JOIN users u ON afc.createUserID = u.userID
												'. $QueryString .'
												GROUP BY afc.feeCollectionID
												ORDER BY afc.feeDate LIMIT ' . (int) $Start . ', ' . (int) $Limit . ';');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $FeeCollectionDetails; 
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$FeeCollectionDetails['AcademicYearName'] = $SearchRow->academicYearName;
				$FeeCollectionDetails[$SearchRow->feeCollectionID]['FeeTransactionID'] = $SearchRow->feeTransactionID;
				$FeeCollectionDetails[$SearchRow->feeCollectionID]['StudentName'] = $SearchRow->firstName .' '. $SearchRow->lastName;

				$FeeCollectionDetails[$SearchRow->feeCollectionID]['ClassName'] = $SearchRow->className;
				$FeeCollectionDetails[$SearchRow->feeCollectionID]['SectionName'] = $SearchRow->sectionName;

				$FeeCollectionDetails[$SearchRow->feeCollectionID]['FeeDate'] = $SearchRow->feeDate;

				$FeeCollectionDetails[$SearchRow->feeCollectionID]['TotalAmount'] = $SearchRow->totalAmount;
				$FeeCollectionDetails[$SearchRow->feeCollectionID]['TotalDiscount'] = $SearchRow->totalDiscount;
				
				// $FeeCollectionDetails[$SearchRow->feeCollectionID]['AmountPaid'] = $SearchRow->amountPaid + $SearchRow->amount;
				
				$FeeCollectionDetails[$SearchRow->feeCollectionID]['AmountPaid'] = $SearchRow->amountPaid;
				$FeeCollectionDetails[$SearchRow->feeCollectionID]['PaymentMode'] = $SearchRow->paymentMode;
				
				$FeeCollectionDetails[$SearchRow->feeCollectionID]['Description'] = $SearchRow->description;
				 			
				$FeeCollectionDetails[$SearchRow->feeCollectionID]['CreateUserName'] = $SearchRow->createUserName;
				$FeeCollectionDetails[$SearchRow->feeCollectionID]['CreateDate'] = $SearchRow->createDate;
				
				$FeeCollectionDetails[$SearchRow->feeCollectionID]['PaymentModeDetails'] = array();

				$RSSearchPaymentMode = $DBConnObject->Prepare('SELECT * FROM afm_fee_payment_mode_details WHERE feeTransactionID = :|1;');
				$RSSearchPaymentMode->Execute($SearchRow->feeTransactionID);

				if ($RSSearchPaymentMode->Result->num_rows > 0)
				{
					while($SearchPaymentModeRow = $RSSearchPaymentMode->FetchRow())
					{
						$FeeCollectionDetails[$SearchRow->feeCollectionID]['PaymentModeDetails'][$SearchPaymentModeRow->paymentMode]['Amount'] = $SearchPaymentModeRow->amount;
						$FeeCollectionDetails[$SearchRow->feeCollectionID]['PaymentModeDetails'][$SearchPaymentModeRow->paymentMode]['ChequeReferenceNo'] = $SearchPaymentModeRow->chequeReferenceNo;
						
						$FeeCollectionDetails[$SearchRow->feeCollectionID]['PaymentModeDetails'][$SearchPaymentModeRow->paymentMode]['PaymentMode'] = $SearchPaymentModeRow->paymentMode;
    					$FeeCollectionDetails[$SearchRow->feeCollectionID]['PaymentModeDetails'][$SearchPaymentModeRow->paymentMode]['ChequeStatus'] = $SearchPaymentModeRow->chequeStatus;
					}
				}
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
			
			$RSFeeTransactionDetails = $DBConnObject->Prepare('SELECT afcd.*, afs.academicYearID, afh.feeHead, afh.feeHeadID, asfs.amountPayable, aaym.monthName, afd.discountType AS firstDiscountType, afd.discountValue AS firstDiscountValue, afd.concessionAmount AS firstConcessionAmount, afd.waveOffAmount AS firstWaveOffAmount, afd1.discountType AS secondDiscountType, afd1.discountValue AS secondDiscountValue, afd1.concessionAmount AS secondConcessionAmount, afd1.waveOffAmount AS secondWaveOffAmount  
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

				$FeeCollectionDetails[$SearchRow->academicYearID][$SearchRow->monthName][$SearchRow->feeHeadID]['FeeHead'] = $SearchRow->feeHead;		
				$FeeCollectionDetails[$SearchRow->academicYearID][$SearchRow->monthName][$SearchRow->feeHeadID]['FeeAmount'] = $FeeAmount;

				if ($SearchRow->firstDiscountType == 'Percentage') 
				{
					$FirstDiscountAmount = (($FeeAmount * $SearchRow->firstDiscountValue) / 100) + $SearchRow->firstConcessionAmount + $SearchRow->firstWaveOffAmount;
				}
				else if ($SearchRow->firstDiscountType == 'Absolute')
				{
					$FirstDiscountAmount = $SearchRow->firstDiscountValue + $SearchRow->firstConcessionAmount + $SearchRow->firstWaveOffAmount;
				}

				if ($SearchRow->secondDiscountType == 'Percentage') 
				{
					$SecondDiscountAmount = (($FeeAmount * $SearchRow->secondDiscountValue) / 100) + $SearchRow->secondConcessionAmount + $SearchRow->firstWaveOffAmount;
				}
				else if ($SearchRow->secondDiscountType == 'Absolute')
				{
					$SecondDiscountAmount = $SearchRow->secondDiscountValue + $SearchRow->secondConcessionAmount + $SearchRow->secondWaveOffAmount;
				}

				if ($SecondDiscountAmount > 0) 
				{
					$FeeHeadDiscountAmount = $SecondDiscountAmount;

					$FeeCollectionDetails[$SearchRow->academicYearID][$SearchRow->monthName][$SearchRow->feeHeadID]['DiscountType'] = $SearchRow->secondDiscountType;
					$FeeCollectionDetails[$SearchRow->academicYearID][$SearchRow->monthName][$SearchRow->feeHeadID]['DiscountValue'] = $SearchRow->secondDiscountValue;
					$FeeCollectionDetails[$SearchRow->academicYearID][$SearchRow->monthName][$SearchRow->feeHeadID]['DiscountAmount'] = $SecondDiscountAmount;
				}
				else
				{
					$FeeHeadDiscountAmount = $FirstDiscountAmount;

					$FeeCollectionDetails[$SearchRow->academicYearID][$SearchRow->monthName][$SearchRow->feeHeadID]['DiscountType'] = $SearchRow->firstDiscountType;
					$FeeCollectionDetails[$SearchRow->academicYearID][$SearchRow->monthName][$SearchRow->feeHeadID]['DiscountValue'] = $SearchRow->firstDiscountValue;
					$FeeCollectionDetails[$SearchRow->academicYearID][$SearchRow->monthName][$SearchRow->feeHeadID]['DiscountAmount'] = $FirstDiscountAmount;
				}

				$TotalMonthlyFeeAmount = $TotalMonthlyFeeAmount + $FeeAmount - $FeeHeadDiscountAmount;	
			    
			    $RSPreviousSubmitedFeeOfMonth = $DBConnObject->Prepare('SELECT SUM(amountPaid) AS totalSubmittedFee FROM afm_fee_collection_details afcd
    														        	WHERE studentFeeStructureID = :|1 AND feeCollectionID < :|2;');
    			$RSPreviousSubmitedFeeOfMonth->Execute($SearchRow->studentFeeStructureID, $FeeCollectionID);
    			
    			$TotalSubmittedFee = 0;
    			if ($RSPreviousSubmitedFeeOfMonth->Result->num_rows > 0)
    			{
    			    $TotalSubmittedFee = $RSPreviousSubmitedFeeOfMonth->FetchRow()->totalSubmittedFee;
    			    $FeeCollectionDetails[$SearchRow->academicYearID][$SearchRow->monthName][$SearchRow->feeHeadID]['FeeAmount'] = $FeeAmount - $TotalSubmittedFee;
    			}
    			
				$FeeCollectionDetails[$SearchRow->academicYearID][$SearchRow->monthName][$SearchRow->feeHeadID]['PaidAmount'] = $SearchRow->amountPaid;
				$FeeCollectionDetails[$SearchRow->academicYearID][$SearchRow->monthName][$SearchRow->feeHeadID]['RestAmount'] = $FeeAmount - $FeeHeadDiscountAmount - $SearchRow->amountPaid -$TotalSubmittedFee;
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
			$InActiveStatusCondition = '';

			$JoinClassSectionTable = ' INNER JOIN asa_class_sections acs ON acs.classSectionID = ass.classSectionID ';
			
			if (count($Filters) > 0)
			{
				if (!empty($Filters['AcademicYearID']))
				{
					if ($Filters['AcademicYearID'] > 0 && $Filters['AcademicYearID'] < 2 && !empty($Filters['Status']) && $Filters['Status'] == 'Active') 
					{
						$JoinClassSectionTable = ' INNER JOIN asa_class_sections acs ON acs.classSectionID = spyd.previousClassSectionID ';
					}

					$Conditions[] = 'fs.academicYearID = '. $DBConnObject->RealEscapeVariable($Filters['AcademicYearID']);
				}
				
				if (!empty($Filters['ClassID']))
				{
					$Conditions[] = 'ac.classID = '. $DBConnObject->RealEscapeVariable($Filters['ClassID']);
				}

				if (!empty($Filters['ClassSectionID']))
				{
					$Conditions[] = 'ass.classSectionID =  '. $DBConnObject->RealEscapeVariable($Filters['ClassSectionID']) . ' OR spyd.previousClassSectionID = '. $DBConnObject->RealEscapeVariable($Filters['ClassSectionID']);
				}
				
				if (!empty($Filters['StudentID']))
				{
					$Conditions[] = 'asd.studentID = '. $DBConnObject->RealEscapeVariable($Filters['StudentID']);
				}
				
				if (!empty($Filters['FeeHeadID']))
				{
					$Conditions[] = 'fsd.feeHeadID = '. $DBConnObject->RealEscapeVariable($Filters['FeeHeadID']);
				}
				
				if (!empty($Filters['Status']) && $Filters['Status'] == 'Active')
				{
					$Conditions[] = 'ass.status = \'Active\'';
				}
				
				if (!empty($Filters['Status']) && $Filters['Status'] == 'InActive')
				{
					$Conditions[] = 'ass.status != \'Active\'';
					
					$InActiveStatusCondition = ' AND (feePriority <= (SELECT aym.feePriority FROM asa_students ast
                                                INNER JOIN (SELECT studentID,  MAX(statusChangeLogID) AS statusChangeLogID, MAX(dateFrom) AS dateFrom, MONTHNAME(MAX(dateFrom)) AS inActiveMonthName FROM asa_student_status_change_log GROUP BY studentID) sscl ON sscl.studentID = ast.studentID
                                                INNER JOIN asa_academic_year_months aym ON aym.monthName = sscl.inActiveMonthName
                                                WHERE ast.status = \'InActive\' AND sscl.studentID = ass.studentID))';   
				}

				if (!empty($Filters['StudentName']))
				{
					$Conditions[] = 'CONCAT(asd.firstName, " ", asd.lastName) LIKE ' . $DBConnObject->RealEscapeVariable('%' . $Filters['StudentName'] . '%');
				}
				
				if (!empty($Filters['MobileNumber']))
				{
					$Conditions[] = 'asd.mobileNumber = '.$DBConnObject->RealEscapeVariable($Filters['MobileNumber']) .' OR apd.fatherMobileNumber = '.$DBConnObject->RealEscapeVariable($Filters['MobileNumber']) . ' OR apd.motherMobileNumber = '.$DBConnObject->RealEscapeVariable($Filters['MobileNumber']);
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
				$RSTotal = $DBConnObject->Prepare('SELECT asd.firstName, asd.lastName, ac.className, asm.sectionName, sfs.studentID, 
    												SUM(sfs.amountPayable) AS totalAmountPayable, 
    												SUM(fcd.amountPaid) As totalAmountPaid,
    												sum(CASE WHEN afd.discountType = "Absolute" THEN (afd.discountValue + afd.concessionAmount + afd.waveOffAmount) ELSE (((sfs.amountPayable * afd.discountValue) / 100) + afd.concessionAmount + afd.waveOffAmount) END) AS firstDiscountValue,
													sum(CASE WHEN afd1.discountType = "Absolute" THEN (afd1.discountValue + afd1.concessionAmount + afd1.waveOffAmount) ELSE (((sfs.amountPayable * afd1.discountValue) / 100) + afd1.concessionAmount + afd1.waveOffAmount) END) AS secondDiscountValue, 
    												(pyfd.payableAmount - pyfd.paidAmount - pyfd.waveOffDue) AS previousYearDue 
    
    												FROM afm_fee_structure_details fsd 
    												INNER JOIN afm_student_fee_structure sfs ON sfs.feeStructureDetailID = fsd.feeStructureDetailID 
    												INNER JOIN afm_fee_structure fs ON fs.feeStructureID = fsd.feeStructureID
    
    												LEFT JOIN (SELECT feeCollectionDetailID, feeCollectionID, studentFeeStructureID, SUM(amountPaid) AS amountPaid FROM afm_fee_collection_details GROUP BY studentFeeStructureID) AS fcd ON fcd.studentFeeStructureID = sfs.studentFeeStructureID
    												LEFT JOIN afm_fee_collection fc ON fc.feeCollectionID = fcd.feeCollectionID 
    												LEFT JOIN afm_fee_discounts afd ON afd.feeGroupID = fs.feeGroupID AND afd.feeStructureDetailID = fsd.feeStructureDetailID AND afd.feeDiscountType = \'Group\' 
    												LEFT JOIN afm_fee_discounts afd1 ON afd1.studentID = sfs.studentID AND afd1.feeStructureDetailID = fsd.feeStructureDetailID AND afd1.feeDiscountType = \'Student\' 
    												LEFT JOIN afm_previous_year_fee_details pyfd ON pyfd.studentID = sfs.studentID 
    
    												INNER JOIN asa_student_details asd ON asd.studentID = sfs.studentID 
    												LEFT JOIN asa_student_previous_academic_year_details spyd ON spyd.studentID = asd.studentID
    												INNER JOIN asa_students ass ON ass.studentID = asd.studentID 
    												INNER JOIN asa_parent_details apd ON apd.parentID = ass.parentID 
    												'. $JoinClassSectionTable .' 
    												INNER JOIN asa_classes ac ON ac.classID = acs.classID 
    												INNER JOIN asa_section_master asm ON asm.sectionMasterID = acs.sectionMasterID 
    
    												WHERE fsd.academicYearMonthID IN (SELECT academicYearMonthID FROM asa_academic_year_months WHERE feePriority <= :|1 '. $InActiveStatusCondition .')
    												'. $QueryString .'
    
    												GROUP BY sfs.studentID
    												HAVING (CASE WHEN secondDiscountValue > 0 THEN ((totalAmountPayable - secondDiscountValue) > 0) ELSE (CASE WHEN firstDiscountValue > 0 THEN ((totalAmountPayable - firstDiscountValue) > 0) ELSE (totalAmountPayable > 0) END) END)
    												    AND (totalAmountPaid IS NULL OR (totalAmountPaid < (CASE WHEN secondDiscountValue > 0 THEN (totalAmountPayable - secondDiscountValue) ELSE (CASE WHEN firstDiscountValue > 0 THEN (totalAmountPayable - firstDiscountValue) ELSE totalAmountPayable END) END)))
    												OR previousYearDue > 0 
    												ORDER BY asd.firstName;');
				$RSTotal->Execute($FeePriority);
				
				$TotalRecords = $RSTotal->Result->num_rows;
				return;
			}

			$RSSearch = $DBConnObject->Prepare('SELECT asd.firstName, asd.lastName, ac.className, asm.sectionName, sfs.studentID, apd.fatherMobileNumber, apd.motherMobileNumber, 
												SUM(sfs.amountPayable) AS totalAmountPayable, 
												SUM(fcd.amountPaid) As totalAmountPaid,
												sum(CASE WHEN afd.discountType = "Absolute" THEN (afd.discountValue + afd.concessionAmount + afd.waveOffAmount) ELSE (((sfs.amountPayable * afd.discountValue) / 100) + afd.concessionAmount + afd.waveOffAmount) END) AS firstDiscountValue,
												sum(CASE WHEN afd1.discountType = "Absolute" THEN (afd1.discountValue + afd1.concessionAmount + afd1.waveOffAmount) ELSE (((sfs.amountPayable * afd1.discountValue) / 100) + afd1.concessionAmount + afd1.waveOffAmount) END) AS secondDiscountValue, 
												(pyfd.payableAmount - pyfd.paidAmount - pyfd.waveOffDue) AS previousYearDue 

												FROM afm_fee_structure_details fsd 
												INNER JOIN afm_student_fee_structure sfs ON sfs.feeStructureDetailID = fsd.feeStructureDetailID 
												INNER JOIN afm_fee_structure fs ON fs.feeStructureID = fsd.feeStructureID

												LEFT JOIN (SELECT feeCollectionDetailID, feeCollectionID, studentFeeStructureID, SUM(amountPaid) AS amountPaid FROM afm_fee_collection_details GROUP BY studentFeeStructureID) AS fcd ON fcd.studentFeeStructureID = sfs.studentFeeStructureID
												LEFT JOIN afm_fee_collection fc ON fc.feeCollectionID = fcd.feeCollectionID 
												LEFT JOIN afm_fee_discounts afd ON afd.feeGroupID = fs.feeGroupID AND afd.feeStructureDetailID = fsd.feeStructureDetailID AND afd.feeDiscountType = \'Group\' 
												LEFT JOIN afm_fee_discounts afd1 ON afd1.studentID = sfs.studentID AND afd1.feeStructureDetailID = fsd.feeStructureDetailID AND afd1.feeDiscountType = \'Student\' 
												LEFT JOIN afm_previous_year_fee_details pyfd ON pyfd.studentID = sfs.studentID 

												INNER JOIN asa_student_details asd ON asd.studentID = sfs.studentID 
												LEFT JOIN asa_student_previous_academic_year_details spyd ON spyd.studentID = asd.studentID
												INNER JOIN asa_students ass ON ass.studentID = asd.studentID 
												INNER JOIN asa_parent_details apd ON apd.parentID = ass.parentID
												'. $JoinClassSectionTable .' 
												INNER JOIN asa_classes ac ON ac.classID = acs.classID 
												INNER JOIN asa_section_master asm ON asm.sectionMasterID = acs.sectionMasterID 

												WHERE fsd.academicYearMonthID IN (SELECT academicYearMonthID FROM asa_academic_year_months WHERE feePriority <= :|1 '. $InActiveStatusCondition .')
												'. $QueryString .'

												GROUP BY sfs.studentID
												HAVING (CASE WHEN secondDiscountValue > 0 THEN ((totalAmountPayable - secondDiscountValue) > 0) ELSE (CASE WHEN firstDiscountValue > 0 THEN ((totalAmountPayable - firstDiscountValue) > 0) ELSE (totalAmountPayable > 0) END) END)
    												    AND (totalAmountPaid IS NULL OR (totalAmountPaid < (CASE WHEN secondDiscountValue > 0 THEN (totalAmountPayable - secondDiscountValue) ELSE (CASE WHEN firstDiscountValue > 0 THEN (totalAmountPayable - firstDiscountValue) ELSE totalAmountPayable END) END)))
    												OR previousYearDue > 0 
												ORDER BY asd.firstName, asd.lastName ASC LIMIT '.(int) $Start.', '.(int) $Limit.';');
			$RSSearch->Execute($FeePriority);
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $DefaulterList; 
			}
			
			$DiscountValue = 0;

			while($SearchRow = $RSSearch->FetchRow())
			{
			    $DueMonths = 0;
			    
				$RSDueMonthsByStudent = $DBConnObject->Prepare('SELECT afsd.academicYearMonthID,
    			                                                    SUM(asfs.amountPayable) AS totalAmountPayable, 
                                                                    SUM(afcd.amountPaid) As totalAmountPaid,
                                                                    sum(CASE WHEN afd.discountType = "Absolute" THEN (afd.discountValue + afd.concessionAmount + afd.waveOffAmount) ELSE (((asfs.amountPayable * afd.discountValue) / 100) + afd.concessionAmount + afd.waveOffAmount) END) AS firstDiscountValue,
                                                                    sum(CASE WHEN afd1.discountType = "Absolute" THEN (afd1.discountValue + afd1.concessionAmount + afd1.waveOffAmount) ELSE (((asfs.amountPayable * afd1.discountValue) / 100) + afd1.concessionAmount + afd1.waveOffAmount) END) AS secondDiscountValue, 
                                                                    (pyfd.payableAmount - pyfd.paidAmount - pyfd.waveOffDue) AS previousYearDue 
                                                                    
    															FROM afm_student_fee_structure asfs
    															INNER JOIN afm_fee_structure_details afsd ON afsd.feeStructureDetailID = asfs.feeStructureDetailID
    															LEFT JOIN (SELECT feeCollectionDetailID, feeCollectionID, studentFeeStructureID, SUM(amountPaid) AS amountPaid FROM afm_fee_collection_details GROUP BY studentFeeStructureID) afcd ON afcd.studentFeeStructureID = asfs.studentFeeStructureID
    															INNER JOIN afm_fee_structure afs ON afs.feeStructureID = afsd.feeStructureID
    															INNER JOIN afm_fee_heads afh ON afh.feeHeadID = afsd.feeHeadID
    															INNER JOIN asa_academic_year_months aaym ON aaym.academicYearMonthID = afsd.academicYearMonthID
    															INNER JOIN asa_students ass ON ass.studentID = asfs.studentID
    															
    															LEFT JOIN afm_fee_discounts afd ON afd.feeGroupID = afs.feeGroupID AND afd.feeStructureDetailID = afsd.feeStructureDetailID AND afd.feeDiscountType = \'Group\' 
    							 								LEFT JOIN afm_fee_discounts afd1 ON afd1.studentID = asfs.studentID AND afd1.feeStructureDetailID = afsd.feeStructureDetailID AND afd1.feeDiscountType = \'Student\' 
    							 								LEFT JOIN afm_previous_year_fee_details pyfd ON pyfd.studentID = asfs.studentID 
    															
    															WHERE asfs.studentID = :|1 AND afs.academicYearID = :|2 AND afsd.academicYearMonthID IN (SELECT academicYearMonthID FROM asa_academic_year_months WHERE feePriority <= :|3 '. $InActiveStatusCondition .')
    															GROUP BY afsd.academicYearMonthID
    															HAVING (CASE WHEN secondDiscountValue > 0 THEN ((totalAmountPayable - secondDiscountValue) > 0) ELSE (CASE WHEN firstDiscountValue > 0 THEN ((totalAmountPayable - firstDiscountValue) > 0) ELSE (totalAmountPayable > 0) END) END)
                                                                    AND (totalAmountPaid IS NULL OR (totalAmountPaid < (CASE WHEN secondDiscountValue > 0 THEN (totalAmountPayable - secondDiscountValue) ELSE (CASE WHEN firstDiscountValue > 0 THEN (totalAmountPayable - firstDiscountValue) ELSE totalAmountPayable END) END)))
    															ORDER BY aaym.feePriority;');
    															
    			$RSDueMonthsByStudent->Execute($SearchRow->studentID, $Filters['AcademicYearID'], $FeePriority);
    			
    			if ($RSDueMonthsByStudent->Result->num_rows > 0)
    			{
    			    $DueMonths = $RSDueMonthsByStudent->Result->num_rows;
    			}
    			
    			if (!empty($Filters['DueMonths']) && ($DueMonths >= $Filters['DueMonths']))
    			{
    			    $DefaulterList[$SearchRow->studentID]['StudentName'] = $SearchRow->firstName .' '. $SearchRow->lastName;
    				$DefaulterList[$SearchRow->studentID]['Class'] = $SearchRow->className .' ( '. $SearchRow->sectionName .' )';
    				$DefaulterList[$SearchRow->studentID]['FatherMobileNumber'] = $SearchRow->fatherMobileNumber;
    				$DefaulterList[$SearchRow->studentID]['MotherMobileNumber'] = $SearchRow->motherMobileNumber;
    
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
    				$DefaulterList[$SearchRow->studentID]['TotalDue'] = $SearchRow->totalAmountPayable - $SearchRow->totalAmountPaid - $DiscountValue + $SearchRow->previousYearDue;
    				$DefaulterList[$SearchRow->studentID]['DueMonths'] = $DueMonths;   
    			}
    			else
    			{
    				$DefaulterList[$SearchRow->studentID]['StudentName'] = $SearchRow->firstName .' '. $SearchRow->lastName;
    				$DefaulterList[$SearchRow->studentID]['Class'] = $SearchRow->className .' ( '. $SearchRow->sectionName .' )';
    				$DefaulterList[$SearchRow->studentID]['FatherMobileNumber'] = $SearchRow->fatherMobileNumber;
    				$DefaulterList[$SearchRow->studentID]['MotherMobileNumber'] = $SearchRow->motherMobileNumber;
    
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
    				$DefaulterList[$SearchRow->studentID]['TotalDue'] = $SearchRow->totalAmountPayable - $SearchRow->totalAmountPaid - $DiscountValue + $SearchRow->previousYearDue;
    				$DefaulterList[$SearchRow->studentID]['DueMonths'] = $DueMonths;
    			}
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

	static function GetFeeDefaulterDues($StudentID, $FeePriority, $AcademicYearID, &$PreviousYearDue = 0)
	{
	    $PreviousYearDue = 0;
	    
		$FeeDefaulterDues = array();
		
		try
		{

			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT aym.feePriority FROM asa_students ast 
                                    		    INNER JOIN (SELECT studentID, MAX(statusChangeLogID) AS statusChangeLogID, MAX(dateFrom) AS dateFrom, MONTHNAME(MAX(dateFrom)) AS inActiveMonthName FROM asa_student_status_change_log GROUP BY studentID) sscl ON sscl.studentID = ast.studentID 
                                        			INNER JOIN asa_academic_year_months aym ON aym.monthName = sscl.inActiveMonthName 
                                                    WHERE ast.status = \'InActive\' AND sscl.studentID = :|1
                                                ;');
			$RSSearch->Execute($StudentID);
			
			if ($RSSearch->Result->num_rows > 0)
			{
				$FeePriority = $RSSearch->FetchRow()->feePriority;
			}
			
			$RSFeeDefaulterDues = $DBConnObject->Prepare('SELECT afh.feeHead, afh.feeHeadID, afsd.feeAmount, aaym.monthName, SUM(afcd.amountPaid) AS amountPaid, asfs.amountPayable, afd.discountType AS firstDiscountType, afd.discountValue AS firstDiscountValue, afd.concessionAmount AS firstConcessionAmount, afd.waveOffAmount AS firstWaveOffAmount, afd1.discountType AS secondDiscountType, afd1.discountValue AS secondDiscountValue, afd1.concessionAmount AS secondConcessionAmount, afd1.waveOffAmount AS secondWaveOffAmount, 
			                                                (pyfd.payableAmount - pyfd.paidAmount - pyfd.waveOffDue) AS previousYearDue 
															FROM afm_student_fee_structure asfs
															INNER JOIN afm_fee_structure_details afsd ON afsd.feeStructureDetailID = asfs.feeStructureDetailID
															LEFT JOIN afm_fee_collection_details afcd ON afcd.studentFeeStructureID = asfs.studentFeeStructureID
															INNER JOIN afm_fee_structure afs ON afs.feeStructureID = afsd.feeStructureID
															INNER JOIN afm_fee_heads afh ON afh.feeHeadID = afsd.feeHeadID
															INNER JOIN asa_academic_year_months aaym ON aaym.academicYearMonthID = afsd.academicYearMonthID
															LEFT JOIN afm_fee_discounts afd ON afd.feeGroupID = afs.feeGroupID AND afd.feeStructureDetailID = afsd.feeStructureDetailID AND afd.feeDiscountType = \'Group\' 
							 								LEFT JOIN afm_fee_discounts afd1 ON afd1.studentID = asfs.studentID AND afd1.feeStructureDetailID = afsd.feeStructureDetailID AND afd1.feeDiscountType = \'Student\' 
							 								LEFT JOIN afm_previous_year_fee_details pyfd ON pyfd.studentID = asfs.studentID 
															WHERE asfs.studentID = :|1 AND afs.academicYearID = :|2 AND afsd.academicYearMonthID IN (SELECT academicYearMonthID FROM asa_academic_year_months WHERE feePriority <= :|3)
															GROUP BY afsd.feeHeadID, afsd.academicYearMonthID
															ORDER BY aaym.feePriority;');
			$RSFeeDefaulterDues->Execute($StudentID, $AcademicYearID, $FeePriority);

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

				// $FeeAmount = $SearchRow->feeAmount;
				$FeeAmount = $SearchRow->amountPayable;

				$FeeDefaulterDues[$SearchRow->monthName][$SearchRow->feeHeadID]['FeeHead'] = $SearchRow->feeHead;		

				if ($SearchRow->firstDiscountType == 'Percentage') 
				{
					$FirstDiscountAmount = (($FeeAmount * $SearchRow->firstDiscountValue) / 100) + $SearchRow->firstConcessionAmount + $SearchRow->firstWaveOffAmount;
				}
				else if ($SearchRow->firstDiscountType == 'Absolute')
				{
					$FirstDiscountAmount = $SearchRow->firstDiscountValue + $SearchRow->firstConcessionAmount + $SearchRow->firstWaveOffAmount;
				}

				if ($SearchRow->secondDiscountType == 'Percentage') 
				{
					$SecondDiscountAmount = (($FeeAmount * $SearchRow->secondDiscountValue) / 100) + $SearchRow->secondConcessionAmount + $SearchRow->secondWaveOffAmount;
				}
				else if ($SearchRow->secondDiscountType == 'Absolute')
				{
					$SecondDiscountAmount = $SearchRow->secondDiscountValue + $SearchRow->secondConcessionAmount + $SearchRow->secondWaveOffAmount;
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
				
				$PreviousYearDue = $SearchRow->previousYearDue;
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

	static function GetFeeCollectionIDsByTransactionID($FeeTransactionID)
	{
		$FeeCollectionIDs = array();
		try
		{

			$DBConnObject = new DBConnect();
			
			$RSFeeCollectionIDs = $DBConnObject->Prepare('SELECT feeCollectionID FROM afm_fee_collection
															WHERE feeTransactionID = :|1;');
			$RSFeeCollectionIDs->Execute($FeeTransactionID);

			if ($RSFeeCollectionIDs->Result->num_rows <= 0)
			{
				return $FeeCollectionIDs;
			}

			while($SearchRow = $RSFeeCollectionIDs->FetchRow())
			{
				$FeeCollectionIDs[$SearchRow->feeCollectionID] = $SearchRow->feeCollectionID;		
			}

			return $FeeCollectionIDs;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at FeeCollection::GetFeeCollectionIDsByTransactionID(). Stack Trace: ' . $e->getTraceAsString());
			return $FeeCollectionIDs;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at FeeCollection::GetFeeCollectionIDsByTransactionID(). Stack Trace: ' . $e->getTraceAsString());
			return $FeeCollectionIDs;
		}
	}
	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//

	private function SaveDetails()
	{
		if ($this->FeeCollectionID == 0)
		{
			$TransactionCode = '';

			$RSSaveFeeTransaction = $this->DBObject->Prepare('INSERT INTO afm_fee_transactions (transactionCode, transactionAmount, description, createUserID, createDate)
															VALUES (:|1, :|2, :|3, :|4, NOW());');
		
			$RSSaveFeeTransaction->Execute($TransactionCode, $this->TransactionAmount, $this->Description, $this->CreateUserID);
			
			$this->FeeTransactionID = $RSSaveFeeTransaction->LastID;

			// $TransactionCode = 'TRA';
			// $RSUpdateTransactionCode = $this->DBObject->Prepare('UPDATE afm_fee_collection
			// 										SET	transactionCode = :|1
			// 										WHERE feeTransactionID = :|2 LIMIT 1;');
													
			// $RSUpdateTransactionCode->Execute($TransactionCode, $this->FeeTransactionID);

			$this->CurrentTransactionID = $this->FeeTransactionID;

			foreach ($this->PaymentModeDetails as $Counter => $PaymentModeDetails) 
			{
				$RSSavePaymentMode = $this->DBObject->Prepare('INSERT INTO afm_fee_payment_mode_details (feeTransactionID, amount, paymentMode, chequeReferenceNo, chequeStatus)
																VALUES (:|1, :|2, :|3, :|4, :|5);');
			
				$RSSavePaymentMode->Execute($this->FeeTransactionID, $PaymentModeDetails['Amount'], $PaymentModeDetails['PaymentMode'], $PaymentModeDetails['ChequeReferenceNo'], 'Pending');

				if ($PaymentModeDetails['PaymentMode'] == 6) 
				{
					$RSUpdate = $this->DBObject->Prepare('UPDATE asa_parent_details
															SET	walletAmount = walletAmount - :|1
															WHERE parentID = :|2 LIMIT 1;');
															
					$RSUpdate->Execute($PaymentModeDetails['Amount'], $this->ParentID);
				}
			}

			foreach ($this->FeeCollectionDetails as $StudentID => $Details) 
			{
				$RSSaveFeeCollection = $this->DBObject->Prepare('INSERT INTO afm_fee_collection (feeTransactionID, studentID, feeDate, totalAmount, totalDiscount, amountPaid, paymentMode, chequeReferenceNo, createUserID, createDate)
																VALUES (:|1, :|2, :|3, :|4, :|5, :|6, :|7, :|8, :|9, NOW());');
			
				$RSSaveFeeCollection->Execute($this->FeeTransactionID, $StudentID, $this->FeeDate, $Details['StudentAmountPayable'], $Details['TotalDiscount'], $Details['StudentAmountPaid'], $this->PaymentMode, $this->ChequeReferenceNo, $this->CreateUserID);
				
				$this->FeeCollectionID = $RSSaveFeeCollection->LastID;
                
                if (array_key_exists('StudentFeeCollectionDetails', $Details))
                {
                    foreach ($Details['StudentFeeCollectionDetails'] as $StudentFeeStructureID => $AmountPaid) 
    				{
    					$RSSaveFeeCollectionDetails = $this->DBObject->Prepare('INSERT INTO afm_fee_collection_details (feeCollectionID, studentFeeStructureID, amountPaid)
    														VALUES (:|1, :|2, :|3);');
    				
    					$RSSaveFeeCollectionDetails->Execute($this->FeeCollectionID, $StudentFeeStructureID, $AmountPaid);	
    				}   
                }
			}

			foreach ($this->OtherChargesDetails as $StudentID => $OtherDetails) 
			{
				$RSSearchFeeCollectionID = $this->DBObject->Prepare('SELECT feeCollectionID FROM afm_fee_collection WHERE studentID = :|1 AND feeDate = :|2 AND createDate = NOW();');
			
				$RSSearchFeeCollectionID->Execute($StudentID, $this->FeeDate);

			    if ($RSSearchFeeCollectionID->Result->num_rows <= 0) 
				{
					$RSSaveFeeCollection = $this->DBObject->Prepare('INSERT INTO afm_fee_collection (feeTransactionID, studentID, feeDate, totalAmount, totalDiscount, amountPaid, paymentMode, chequeReferenceNo, createUserID, createDate)
																	VALUES (:|1, :|2, :|3, :|4, :|5, :|6, :|7, :|8, :|9, NOW());');
				
					$RSSaveFeeCollection->Execute($this->FeeTransactionID, $StudentID, $this->FeeDate, $this->TotalAmount, $this->TotalDiscount, $this->AmountPaid, $this->PaymentMode, $this->ChequeReferenceNo, $this->CreateUserID);

					$FeeCollectionID = $RSSaveFeeCollection->LastID;
				}
				else
				{
					$FeeCollectionID = $RSSearchFeeCollectionID->FetchRow()->feeCollectionID;
				}

				foreach ($OtherDetails as $key => $Details) 
				{
					$RSSaveOtherChargesDetails = $this->DBObject->Prepare('INSERT INTO afm_fee_collection_other_charges (feeCollectionID, feeType, feeDescription, amount)
														VALUES (:|1, :|2, :|3, :|4);');
				
					$RSSaveOtherChargesDetails->Execute($FeeCollectionID, $Details['FeeType'], $Details['FeeDescription'], $Details['Amount']);
					
					if ($Details['FeeType'] == 'PreviousYearDue') 
					{
						$RSUpdate = $this->DBObject->Prepare('UPDATE afm_previous_year_fee_details
																SET	paidAmount = paidAmount + :|1,
																	waveOffDue = waveOffDue + :|2
																WHERE studentID = :|3 LIMIT 1;');
																
						$RSUpdate->Execute($Details['Amount'], $Details['WaveOffAmount'], $StudentID);
					}
				}
			}	

			if (count($this->AdvanceFeeDetails) > 0) 
			{
				$RSSaveAdvance = $this->DBObject->Prepare('INSERT INTO afm_advance_fee (parentID, feeTransactionID, advanceAmount)
															VALUES (:|1, :|2, :|3);');
			
				$RSSaveAdvance->Execute($this->AdvanceFeeDetails['ParentID'], $this->FeeTransactionID, $this->AdvanceFeeDetails['AdvanceFee']);

				$RSUpdate = $this->DBObject->Prepare('UPDATE asa_parent_details
														SET	walletAmount = walletAmount + :|1
														WHERE parentID = :|2 LIMIT 1;');
														
				$RSUpdate->Execute($this->AdvanceFeeDetails['AdvanceFee'], $this->ParentID);
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
    
        $RSSelectOtherFeeType = $this->DBObject->Prepare('SELECT feeCollectionOtherChargeID, feeType, amount FROM afm_fee_collection_other_charges WHERE feeCollectionID = :|1;');
        $RSSelectOtherFeeType->Execute($this->FeeCollectionID);
        
        if ($RSSelectOtherFeeType->Result->num_rows > 0)
        {
            while ($SearchRow = $RSSelectOtherFeeType->FetchRow())
            {
                if ($SearchRow->feeType == 'PreviousYearDue')
                {
                    $RSUpdate = $this->DBObject->Prepare('UPDATE afm_previous_year_fee_details
    														SET	paidAmount = paidAmount - :|1
    														WHERE studentID = :|2 LIMIT 1;');
    														
    				$RSUpdate->Execute($SearchRow->amount, $this->StudentID);
                }   
            }
        }
        
        $RSDeleteFeeCollectionOtherCharges = $this->DBObject->Prepare('DELETE FROM afm_fee_collection_other_charges WHERE feeCollectionID = :|1;');
        $RSDeleteFeeCollectionOtherCharges->Execute($this->FeeCollectionID); 
        
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
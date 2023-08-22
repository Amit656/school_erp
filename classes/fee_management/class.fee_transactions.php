<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.db_connect.php');
error_reporting(E_ALL);

class FeeTransaction
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	
	private $FeeTransactionID;
	private $TransactionCode;
	private $TransactionAmount;

	private $CreateUserID;
	private $CreateDate;
	
	// PUBLIC METHODS START HERE	//
	public function __construct($FeeTransactionID = 0)
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;
		
		if($FeeTransactionID != 0)
		{
			$this->FeeTransactionID = $FeeTransactionID;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetFeeTransactionByID();
		}
		else
		{
			//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
			$this->FeeTransactionID = 0;
			$this->TransactionCode = '';
			$this->TransactionAmount = 0;
			
			$this->CreateUserID = 0;
			$this->CreateDate = '0000-00-00 00:00:00';
		}
	}
	
	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetFeeTransactionID()
	{
		return $this->FeeTransactionID;
	}
	
	public function GetTransactionCode()
	{
		return $this->TransactionCode;
	}
	public function SetTransactionCode($TransactionCode)
	{
		$this->TransactionCode = $TransactionCode;
	}

	public function GetTransactionAmount()
	{
		return $this->TransactionAmount;
	}
	public function SetTransactionAmount($TransactionAmount)
	{
		$this->TransactionAmount = $TransactionAmount;
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
			$this->DBObject->BeginTransaction();
			if ($this->RemoveFeeTransaction())
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
		
	// END OF PUBLIC METHODS	//
	
	// START OF STATIC METHODS	//
	
// 	static function SearchRefundFeeReport(&$TotalRecords = 0, $GetTotalsOnly = false, $Filters = array(), $Start = 0, $Limit = 100)
// 	{
// 		$RefundFeeReport = array();
		
// 		try
// 		{
// 			$DBConnObject = new DBConnect();

// 			$Conditions = array();
			
// 			if (count($Filters) > 0)
// 			{
//                 if (!empty($Filters['ClassID']))
// 				{
// 					$Conditions[] = 'ac.classID = '. $DBConnObject->RealEscapeVariable($Filters['ClassID']);
// 				}

// 				if (!empty($Filters['ClassSectionID']))
// 				{
// 					$Conditions[] = 'acs.classSectionID = '. $DBConnObject->RealEscapeVariable($Filters['ClassSectionID']);
// 				}
				
// 				if (!empty($Filters['StudentID']))
// 				{
// 					$Conditions[] = 'asd.studentID = '. $DBConnObject->RealEscapeVariable($Filters['StudentID']);
// 				}
				
// 				if (!empty($Filters['FeeHeadID']))
// 				{
// 					$Conditions[] = 'afsd.feeHeadID = '. $DBConnObject->RealEscapeVariable($Filters['FeeHeadID']);
// 				}
				
// 				if (count($Filters['MonthList']) > 0)
// 				{
// 					$Conditions[] = 'afsd.academicYearMonthID IN ('. implode(', ', $Filters['MonthList']) .')';
// 				}
				
// 			}
			
// 			$QueryString = '';

// 			if (count($Conditions) > 0)
// 			{
// 				$QueryString = implode(') AND (', $Conditions);
				
// 				$QueryString = ' WHERE (' . $QueryString . ')';
// 			}

// 			if ($GetTotalsOnly)
// 			{
// 				$RSTotal = $DBConnObject->Prepare('SELECT COUNT(Distinct afr.refundFeeID) AS totalRecords 
// 													FROM afm_refund_fee afr
// 													INNER JOIN afm_refund_fee_details afrd ON afrd.refundFeeID = afr.refundFeeID
// 													INNER JOIN afm_student_fee_structure asfs ON asfs.studentFeeStructureID = afrd.studentFeeStructureID
													
//                                                     INNER JOIN afm_fee_structure_details afsd ON afsd.feeStructureDetailID = asfs.feeStructureDetailID 
//                                                     INNER JOIN afm_fee_structure afs ON afs.feeStructureID = afsd.feeStructureID 
                                                    
//                                                     INNER JOIN asa_academic_years ay ON ay.academicYearID = afs.academicYearID 
                                                    
//                                                     INNER JOIN asa_student_details asd ON asd.studentID = afc.studentID 
//                                                     INNER JOIN asa_students ass ON ass.studentID = asd.studentID 
//                                                     INNER JOIN asa_class_sections acs ON acs.classSectionID = ass.classSectionID 
//                                                     INNER JOIN asa_classes ac ON ac.classID = acs.classID 
//                                                     INNER JOIN asa_section_master asm ON asm.sectionMasterID = acs.sectionMasterID 
//                                                     INNER JOIN users u ON afr.createUserID = u.userID
// 													'. $QueryString .';');
// 				$RSTotal->Execute();
				
// 				$TotalRecords = $RSTotal->FetchRow()->totalRecords;
// 				return;
// 			}
			
// 			$RSSearch = $DBConnObject->Prepare('SELECT afr.*, asd.firstName, asd.lastName, ac.className, asm.sectionName, u.userName AS createUserName 
												
// 												FROM afm_refund_fee afr
// 												INNER JOIN afm_refund_fee_details afrd ON afrd.refundFeeID = afr.refundFeeID
// 												INNER JOIN afm_student_fee_structure asfs ON asfs.studentFeeStructureID = afrd.studentFeeStructureID
												
//                                                 INNER JOIN afm_fee_structure_details afsd ON afsd.feeStructureDetailID = asfs.feeStructureDetailID 
//                                                 INNER JOIN afm_fee_structure afs ON afs.feeStructureID = afsd.feeStructureID 
                                                
//                                                 INNER JOIN asa_academic_years ay ON ay.academicYearID = afs.academicYearID 
                                                
//                                                 INNER JOIN asa_student_details asd ON asd.studentID = afc.studentID 
//                                                 INNER JOIN asa_students ass ON ass.studentID = asd.studentID 
//                                                 INNER JOIN asa_class_sections acs ON acs.classSectionID = ass.classSectionID 
//                                                 INNER JOIN asa_classes ac ON ac.classID = acs.classID 
//                                                 INNER JOIN asa_section_master asm ON asm.sectionMasterID = acs.sectionMasterID 
//                                                 INNER JOIN users u ON afr.createUserID = u.userID
// 												'. $QueryString .'
// 												GROUP BY afr.refundFeeID
// 												ORDER BY afr.refundFeeID LIMIT ' . (int) $Start . ', ' . (int) $Limit . ';');
// 			$RSSearch->Execute();
			
// 			if ($RSSearch->Result->num_rows <= 0)
// 			{
// 				return $RefundFeeReport; 
// 			}
			
// 			while($SearchRow = $RSSearch->FetchRow())
// 			{
// 				$RefundFeeReport[$SearchRow->refundFeeID]['StudentID'] = $SearchRow->studentID;
// 				$RefundFeeReport[$SearchRow->refundFeeID]['StudentName'] = $SearchRow->firstName .' '. $SearchRow->lastName;

// 				$RefundFeeReport[$SearchRow->refundFeeID]['ClassName'] = $SearchRow->className;
// 				$RefundFeeReport[$SearchRow->refundFeeID]['SectionName'] = $SearchRow->sectionName;

// 				$RefundFeeReport[$SearchRow->refundFeeID]['TotalRefundAmount'] = $SearchRow->totalRefundAmount;
				
// 				$RefundFeeReport[$SearchRow->refundFeeID]['RefundMode'] = $SearchRow->refundMode;
// 				$RefundFeeReport[$SearchRow->refundFeeID]['ChequeReferenceNo'] = $SearchRow->chequeReferenceNo;
// 				$RefundFeeReport[$SearchRow->refundFeeID]['ChequeDate'] = $SearchRow->chequeDate;
// 				$RefundFeeReport[$SearchRow->refundFeeID]['ChequeStatus'] = $SearchRow->chequeStatus;
				 			
// 				$RefundFeeReport[$SearchRow->refundFeeID]['CreateUserName'] = $SearchRow->createUserName;
// 				$RefundFeeReport[$SearchRow->refundFeeID]['CreateDate'] = $SearchRow->createDate;
				
// 				$RefundFeeReport[$SearchRow->refundFeeID]['RefundFeeDetails'] = array();
				
// 				$RSSearchRefundFeeDetails = $DBConnObject->Prepare('SELECT * FROM afm_refund_fee_details WHERE refundFeeID = :|1;');
// 				$RSSearchRefundFeeDetails->Execute($SearchRow->refundFeeID);

// 				if ($RSSearchRefundFeeDetails->Result->num_rows > 0)
// 				{
// 					while($SearchRefundFeeDetailRow = $RSSearchRefundFeeDetails->FetchRow())
// 					{
// 						$RefundFeeReport[$SearchRow->refundFeeID]['RefundFeeDetails'][$SearchRefundFeeDetailRow->refundFeeDetailID] = $SearchRefundFeeDetailRow->submittedHeadAmount;
// 						$RefundFeeReport[$SearchRow->refundFeeID]['RefundFeeDetails'][$SearchRefundFeeDetailRow->refundFeeDetailID] = $SearchRefundFeeDetailRow->refundHeadAmount;
// 					}
// 				}
// 			}
			
// 			return $RefundFeeReport;	
// 		}
// 		catch (ApplicationDBException $e)
// 		{
// 			error_log('DEBUG: ApplicationDBException at FeeCollection::SearchRefundFeeReport(). Stack Trace: ' . $e->getTraceAsString());
// 			return $RefundFeeReport;
// 		}
// 		catch (Exception $e)
// 		{
// 			error_log('DEBUG: Exception at FeeCollection::SearchRefundFeeReport(). Stack Trace: ' . $e->getTraceAsString());
// 			return $RefundFeeReport;
// 		}
// 	}
	
	static function SearchRefundFeeReport(&$TotalRecords = 0, $GetTotalsOnly = false, $Filters = array(), $Start = 0, $Limit = 100)
	{
		$RefundFeeReport = array();
		
		try
		{
			$DBConnObject = new DBConnect();

			$Conditions = array();
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

                if (!empty($Filters['ClassID']))
				{
					$Conditions[] = 'ac.classID = '. $DBConnObject->RealEscapeVariable($Filters['ClassID']);
				}

				if (!empty($Filters['ClassSectionID']))
				{
					$Conditions[] = 'ass.classSectionID = '. $DBConnObject->RealEscapeVariable($Filters['ClassSectionID']) . ' OR spyd.previousClassSectionID = '. $DBConnObject->RealEscapeVariable($Filters['ClassSectionID']);
				}
				
				if (!empty($Filters['StudentID']))
				{
					$Conditions[] = 'asd.studentID = '. $DBConnObject->RealEscapeVariable($Filters['StudentID']);
				}
				
				if (!empty($Filters['FeeHeadID']))
				{
					$Conditions[] = 'afsd.feeHeadID = '. $DBConnObject->RealEscapeVariable($Filters['FeeHeadID']);
				}
				
				if (count($Filters['MonthList']) > 0)
				{
					$Conditions[] = 'afsd.academicYearMonthID IN ('. implode(', ', $Filters['MonthList']) .')';
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
				$RSTotal = $DBConnObject->Prepare('SELECT COUNT(DISTINCT afrd.refundFeeDetailID) AS totalRecords 
														FROM afm_refund_fee afr
														INNER JOIN afm_refund_fee_details afrd ON afrd.refundFeeID = afr.refundFeeID
														INNER JOIN afm_student_fee_structure asfs ON asfs.studentFeeStructureID = afrd.studentFeeStructureID
														INNER JOIN afm_fee_structure_details afsd ON afsd.feeStructureDetailID = asfs.feeStructureDetailID 
														INNER JOIN afm_fee_structure afs ON afs.feeStructureID = afsd.feeStructureID 
														INNER JOIN asa_academic_years aay ON aay.academicYearID = afs.academicYearID 

														INNER JOIN asa_student_details asd ON asd.studentID = afr.studentID
														LEFT JOIN asa_student_previous_academic_year_details spyd ON spyd.studentID = asd.studentID
														INNER JOIN asa_students ass ON ass.studentID = asd.studentID 
														'. $JoinClassSectionTable .' 
														
														INNER JOIN asa_classes ac ON ac.classID = acs.classID
														INNER JOIN asa_section_master asm ON asm.sectionMasterID = acs.sectionMasterID
														INNER JOIN users u ON afr.createUserID = u.userID
													'. $QueryString .';');
				$RSTotal->Execute();
				
				$TotalRecords = $RSTotal->FetchRow()->totalRecords;
				return;
			}
			
			$RSSearch = $DBConnObject->Prepare('SELECT afr.*, afrd.submittedHeadAmount, afrd.refundFeeDetailID, afrd.refundHeadAmount,  acym.monthName, afh.feeHead, asd.firstName, asd.lastName, ac.className, asm.sectionName                                , u.userName AS createUserName 
												FROM afm_refund_fee afr
												INNER JOIN afm_refund_fee_details afrd ON afrd.refundFeeID = afr.refundFeeID
												INNER JOIN afm_student_fee_structure asfs ON asfs.studentFeeStructureID = afrd.studentFeeStructureID
												INNER JOIN afm_fee_structure_details afsd ON afsd.feeStructureDetailID = asfs.feeStructureDetailID 
												INNER JOIN afm_fee_structure afs ON afs.feeStructureID = afsd.feeStructureID 
												INNER JOIN asa_academic_year_months acym ON acym.academicYearMonthID = afsd.academicYearMonthID
												INNER JOIN afm_fee_heads afh ON afh.feeHeadID = afsd.feeHeadID
												INNER JOIN asa_academic_years aay ON aay.academicYearID = afs.academicYearID 

												INNER JOIN asa_student_details asd ON asd.studentID = afr.studentID
												LEFT JOIN asa_student_previous_academic_year_details spyd ON spyd.studentID = asd.studentID
												INNER JOIN asa_students ass ON ass.studentID = asd.studentID 
												'. $JoinClassSectionTable .' 

												INNER JOIN asa_classes ac ON ac.classID = acs.classID
												INNER JOIN asa_section_master asm ON asm.sectionMasterID = acs.sectionMasterID
                                                INNER JOIN users u ON afr.createUserID = u.userID
												'. $QueryString .'
												GROUP BY afr.refundFeeID
												ORDER BY afr.refundFeeID LIMIT ' . (int) $Start . ', ' . (int) $Limit . ';');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $RefundFeeReport; 
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$RefundFeeReport[$SearchRow->refundFeeDetailID]['StudentID'] = $SearchRow->studentID;
				$RefundFeeReport[$SearchRow->refundFeeDetailID]['StudentName'] = $SearchRow->firstName .' '. $SearchRow->lastName;

				$RefundFeeReport[$SearchRow->refundFeeDetailID]['ClassName'] = $SearchRow->className;
				$RefundFeeReport[$SearchRow->refundFeeDetailID]['SectionName'] = $SearchRow->sectionName;
				$RefundFeeReport[$SearchRow->refundFeeDetailID]['FeeHead'] = $SearchRow->feeHead;
				$RefundFeeReport[$SearchRow->refundFeeDetailID]['MonthName'] = $SearchRow->monthName;

				$RefundFeeReport[$SearchRow->refundFeeDetailID]['TotalRefundAmount'] = $SearchRow->totalRefundAmount;
				$RefundFeeReport[$SearchRow->refundFeeDetailID]['SubmittedHeadAmount'] = $SearchRow->totalRefundAmount;
				
				$RefundFeeReport[$SearchRow->refundFeeDetailID]['RefundMode'] = $SearchRow->refundMode;
				$RefundFeeReport[$SearchRow->refundFeeDetailID]['ChequeReferenceNo'] = $SearchRow->chequeReferenceNo;
				$RefundFeeReport[$SearchRow->refundFeeDetailID]['ChequeDate'] = $SearchRow->chequeDate;
				$RefundFeeReport[$SearchRow->refundFeeDetailID]['ChequeStatus'] = $SearchRow->chequeStatus;
				 			
				$RefundFeeReport[$SearchRow->refundFeeDetailID]['CreateUserName'] = $SearchRow->createUserName;
				$RefundFeeReport[$SearchRow->refundFeeDetailID]['CreateDate'] = $SearchRow->createDate;
			}
			
			return $RefundFeeReport;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at FeeTransaction::SearchRefundFeeReport(). Stack Trace: ' . $e->getTraceAsString());
			return $RefundFeeReport;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at FeeTransaction::SearchRefundFeeReport(). Stack Trace: ' . $e->getTraceAsString());
			return $RefundFeeReport;
		}
	}
	
	static function RefundStudentFee($RefundFeeDetails)
	{
		try
		{
			$DBConnObject = new DBConnect();
			
			foreach ($RefundFeeDetails as $StudentID => $Details)
			{
			    $RefundStudentFee = $DBConnObject->Prepare('INSERT INTO afm_refund_fee (studentID, totalRefundAmount, refundMode, chequeReferenceNo, createUserID, createDate)
															VALUES(:|1, :|2, :|3, :|4, :|5, NOW());');
			
				$RefundStudentFee->Execute($StudentID, $Details['TotalRefundFeeHeadAmount'], $Details['PaymentMode'], $Details['ChequeNumber'], $Details['CreateUserID']);
				
				$RefundFeeID = $RefundStudentFee->LastID;
				
				foreach ($Details['RefundFeeHeadAmountList'] as $StudentFeeStructureID => $RefundAmountDetails)
    			{
    			    $RefundStudentFeeDetails = $DBConnObject->Prepare('INSERT INTO afm_refund_fee_details (refundFeeID, studentFeeStructureID, submittedHeadAmount, refundHeadAmount)
    															VALUES(:|1, :|2, :|3, :|4);');
    			
    				$RefundStudentFeeDetails->Execute($RefundFeeID, $StudentFeeStructureID, $RefundAmountDetails['SubmittedAmount'], $RefundAmountDetails['RefundAmount']);
    			}
			}
			return true;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at FeeTransaction::RefundStudentFee(). Stack Trace: ' . $e->getTraceAsString());
			return false;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at FeeTransaction::RefundStudentFee(). Stack Trace: ' . $e->getTraceAsString());
			return false;
		}
	}
	
	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//
	
	private function RemoveFeeTransaction()
    {
        if(!isset($this->FeeTransactionID)) 
        {
            throw new ApplicationARException('', APP_AR_ERROR_DELETE_WITHOUT_ID);
        }
        
        $RSSearchFeeCollection = $this->DBObject->Prepare('SELECT feeCollectionID, studentID FROM afm_fee_collection WHERE feeTransactionID = :|1;');
        $RSSearchFeeCollection->Execute($this->FeeTransactionID);

        if ($RSSearchFeeCollection->Result->num_rows > 0)
        {
            while ($SearchFeeCollectionRow = $RSSearchFeeCollection->FetchRow())
            {	
                $StudentID = $SearchFeeCollectionRow->studentID;
                
                $RSDeleteFeeCollectionDetails = $this->DBObject->Prepare('DELETE FROM afm_fee_collection_details WHERE feeCollectionID = :|1;');
		        $RSDeleteFeeCollectionDetails->Execute($SearchFeeCollectionRow->feeCollectionID); 
		    
		        $RSSelectOtherFeeType = $this->DBObject->Prepare('SELECT feeCollectionOtherChargeID, feeType, amount FROM afm_fee_collection_other_charges WHERE feeCollectionID = :|1;');
		        $RSSelectOtherFeeType->Execute($SearchFeeCollectionRow->feeCollectionID);
		        
		        if ($RSSelectOtherFeeType->Result->num_rows > 0)
		        {
		            while ($SearchRow = $RSSelectOtherFeeType->FetchRow())
		            {	
		                if ($SearchRow->feeType == 'PreviousYearDue')
		                {
		                    $RSUpdate = $this->DBObject->Prepare('UPDATE afm_previous_year_fee_details
		    														SET	paidAmount = paidAmount - :|1
		    														WHERE studentID = :|2 LIMIT 1;');
		    														
		    				$RSUpdate->Execute($SearchRow->amount, $StudentID);
		                }   
		            }
		        }
		        
		        $RSDeleteFeeCollectionOtherCharges = $this->DBObject->Prepare('DELETE FROM afm_fee_collection_other_charges WHERE feeCollectionID = :|1;');
		        $RSDeleteFeeCollectionOtherCharges->Execute($SearchFeeCollectionRow->feeCollectionID); 
            }
        }

        $RSDeleteFeeCollection = $this->DBObject->Prepare('DELETE FROM afm_fee_collection WHERE feeTransactionID = :|1;');
        $RSDeleteFeeCollection->Execute($this->FeeTransactionID); 

        $RSDeleteFeeTransaction = $this->DBObject->Prepare('DELETE FROM afm_fee_transactions WHERE feeTransactionID = :|1 LIMIT 1;');
        $RSDeleteFeeTransaction->Execute($this->FeeTransactionID);
        
        $RSSearchAdvanceAmount = $this->DBObject->Prepare('SELECT advanceAmount FROM afm_advance_fee WHERE feeTransactionID = :|1;');
        $RSSearchAdvanceAmount->Execute($this->FeeTransactionID);
        
        if ($RSSearchAdvanceAmount->Result->num_rows > 0)
        {
            $RSUpdate = $this->DBObject->Prepare('UPDATE asa_parent_details
        												SET	walletAmount = walletAmount - :|1
        												WHERE parentID = (SELECT parentID FROM asa_students WHERE studentID = :|2) LIMIT 1;');
    												
    		$RSUpdate->Execute($RSSearchAdvanceAmount->FetchRow()->advanceAmount, $StudentID);
    		
            $RSDeleteAdvanceAmount = $this->DBObject->Prepare('DELETE FROM afm_advance_fee WHERE feeTransactionID = :|1 LIMIT 1;');
            $RSDeleteAdvanceAmount->Execute($this->FeeTransactionID);   
        }

        return true;              
    }
    
	private function GetFeeTransactionByID()
	{
		$RSFeeTransaction = $this->DBObject->Prepare('SELECT * FROM afm_fee_transactions WHERE feeTransactionID = :|1;');
		$RSFeeTransaction->Execute($this->FeeTransactionID);
		
		$FeeTransactionRow = $RSFeeTransaction->FetchRow();
		
		$this->SetAttributesFromDB($FeeTransactionRow);				
	}
	
	private function SetAttributesFromDB($FeeTransactionRow)
	{
		$this->FeeTransactionID = $FeeTransactionRow->feeTransactionID;
		$this->TransactionCode = $FeeTransactionRow->transactionCode;
		$this->TransactionAmount = $FeeTransactionRow->transactionAmount;

		$this->CreateUserID = $FeeTransactionRow->createUserID;
		$this->CreateDate = $FeeTransactionRow->createDate;
	}	
}
?>
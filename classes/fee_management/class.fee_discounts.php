<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.db_connect.php');
error_reporting(E_ALL);

class FeeDiscount
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	
	private $FeeDiscountID;
	private $FeeDiscountType;

	private $FeeGroupID;
	private $StudentID;
	private $FeeStructureDetailID;

	private $DiscountType;
	private $DiscountValue;

	private $DiscountDetails = array();
	
	// PUBLIC METHODS START HERE	//
	public function __construct($FeeDiscountID = 0)
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;
		
		if($FeeDiscountID != 0)
		{
			$this->FeeDiscountID = $FeeDiscountID;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetFeeDiscountByID();
		}
		else
		{
			//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
			$this->FeeDiscountID = 0;
			$this->FeeDiscountType = '';

			$this->FeeGroupID = 0;
			$this->StudentID = 0;
			$this->FeeStructureDetailID = 0;

			$this->DiscountType = '';
			$this->DiscountValue = 0;

			$this->DiscountDetails = array();
		}
	}
	
	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetFeeDiscountID()
	{
		return $this->FeeDiscountID;
	}
	
	public function GetFeeDiscountType()
	{
		return $this->FeeDiscountType;
	}
	public function SetFeeDiscountType($FeeDiscountType)
	{
		$this->FeeDiscountType = $FeeDiscountType;
	}

	public function GetFeeGroupID()
	{
		return $this->FeeGroupID;
	}
	public function SetFeeGroupID($FeeGroupID)
	{
		$this->FeeGroupID = $FeeGroupID;
	}
	
	public function GetStudentID()
	{
		return $this->StudentID;
	}
	public function SetStudentID($StudentID)
	{
		$this->StudentID = $StudentID;
	}
	
	public function GetFeeStructureDetailID()
	{
		return $this->FeeStructureDetailID;
	}
	public function SetFeeStructureDetailID($FeeStructureDetailID)
	{
		$this->FeeStructureDetailID = $FeeStructureDetailID;
	}

	public function GetDiscountType()
	{
		return $this->DiscountType;
	}
	public function SetDiscountType($DiscountType)
	{
		$this->DiscountType = $DiscountType;
	}
	
	public function GetDiscountValue()
	{
		return $this->DiscountValue;
	}
	public function SetDiscountValue($DiscountValue)
	{
		$this->DiscountValue = $DiscountValue;
	}

	public function GetDiscountDetails()
	{
		return $this->DiscountDetails;
	}
	public function SetDiscountDetails($DiscountDetails)
	{
		$this->DiscountDetails = $DiscountDetails;
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

	public function SetFeeStructureDiscount()
	{
		try
		{
			$DBConnObject = new DBConnect();

			if (count($this->DiscountDetails) > 0) 
			{
				foreach ($this->DiscountDetails as $FeeHeadID => $Details) 
				{
					foreach ($Details['FeeStructureDetailID'] as $FeeStructureDetailID) 
					{
						$RSSearchFeeDiscountID = $this->DBObject->Prepare('SELECT feeDiscountID FROM afm_fee_discounts WHERE feeGroupID = :|1 AND feeStructureDetailID = :|2 AND studentID = :|3;');
						$RSSearchFeeDiscountID->Execute($this->FeeGroupID, $FeeStructureDetailID, $this->StudentID);

						if ($RSSearchFeeDiscountID->Result->num_rows > 0)
						{
							$RSUpdate = $DBConnObject->Prepare('UPDATE afm_fee_discounts
																SET	feeDiscountType = :|1,
																	feeGroupID = :|2,
																	studentID = :|3,
																	feeStructureDetailID = :|4,
																	discountType = :|5,
																	discountValue = :|6
																WHERE feeDiscountID = :|7;');
					
							$RSUpdate->Execute($this->FeeDiscountType, $this->FeeGroupID, $this->StudentID, $FeeStructureDetailID, $Details['DiscountType'], $Details['DiscountValue'], $RSSearchFeeDiscountID->FetchRow()->feeDiscountID);
						}
						else
						{
							$RSSave = $DBConnObject->Prepare('INSERT INTO afm_fee_discounts (feeDiscountType, feeGroupID, studentID, feeStructureDetailID, discountType, discountValue, concessionAmount)
															VALUES (:|1, :|2, :|3, :|4, :|5, :|6, :|7);');
				
							$RSSave->Execute($this->FeeDiscountType, $this->FeeGroupID, $this->StudentID, $FeeStructureDetailID, $Details['DiscountType'], $Details['DiscountValue'], 0);
						}
					}
					
					if (array_key_exists('RemoveDiscount', $Details)) 
					{
						foreach ($Details['RemoveDiscount'] as $FeeStructureDetailID) 
						{
							$RSSearchFeeDiscountID = $this->DBObject->Prepare('SELECT feeDiscountID, concessionAmount, waveOffAmount FROM afm_fee_discounts WHERE feeGroupID = :|1 AND feeStructureDetailID = :|2 AND studentID = :|3;');
							$RSSearchFeeDiscountID->Execute($this->FeeGroupID, $FeeStructureDetailID, $this->StudentID);

							if ($RSSearchFeeDiscountID->Result->num_rows > 0)
							{
								$SearchFeeDiscountIDRow = $RSSearchFeeDiscountID->FetchRow();

								if ($SearchFeeDiscountIDRow->concessionAmount > 0 || $SearchFeeDiscountIDRow->waveOffAmount > 0) 
								{
									$RSUpdate = $DBConnObject->Prepare('UPDATE afm_fee_discounts
																		SET	discountValue = :|1
																		WHERE feeDiscountID = :|2;');
							
									$RSUpdate->Execute(0, $SearchFeeDiscountIDRow->feeDiscountID);
								}
								else
								{
									$RSDelete = $this->DBObject->Prepare('DELETE FROM afm_fee_discounts WHERE feeGroupID = :|1 AND feeStructureDetailID = :|2 AND studentID = :|3;');
									$RSDelete->Execute($this->FeeGroupID, $FeeStructureDetailID, $this->StudentID);
								}
							}
						}
					}
				}
			}
			else if ($this->StudentID > 0)
			{
			    $RSDelete = $this->DBObject->Prepare('DELETE FROM afm_fee_discounts WHERE studentID = :|1 AND concessionAmount = 0;');
				$RSDelete->Execute($this->StudentID);

				$RSUpdate = $DBConnObject->Prepare('UPDATE afm_fee_discounts
													SET	discountValue = :|1
													WHERE studentID = :|2;');
		
				$RSUpdate->Execute(0, $this->StudentID);
			}

			return true;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at FeeDiscount::SetFeeStructureDiscount(). Stack Trace: ' . $e->getTraceAsString());
			return false;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at FeeDiscount::SetFeeStructureDiscount(). Stack Trace: ' . $e->getTraceAsString());
			return false;
		}
	}
		
	// END OF PUBLIC METHODS	//
	
	// START OF STATIC METHODS	//
	
	static function GetFeeStructureDiscount($FeeGroupID, $ClassID, $StudentID)
	{
		$AllFeeDiscountDetails = array();
		try
		{

			$DBConnObject = new DBConnect();
			
			$RSFeeDiscountDetails = $DBConnObject->Prepare('SELECT afd.*, afsd.feeHeadID, afsd.feeAmount, afsd.academicYearMonthID FROM afm_fee_discounts afd 
																INNER JOIN afm_fee_structure_details afsd ON afsd.feeStructureDetailID = afd.feeStructureDetailID
																INNER JOIN afm_fee_structure afs ON afs.feeStructureID = afsd.feeStructureID
																WHERE afd.feeGroupID = :|1 AND afs.classID = :|2 AND afd.studentID = :|3;');
			$RSFeeDiscountDetails->Execute($FeeGroupID, $ClassID, $StudentID);

			if ($RSFeeDiscountDetails->Result->num_rows <= 0)
			{
				return $AllFeeDiscountDetails;
			}

			while($SearchRow = $RSFeeDiscountDetails->FetchRow())
			{
				$AllFeeDiscountDetails[$SearchRow->feeHeadID][$SearchRow->feeStructureDetailID]['AcademicYearMonthID'] = $SearchRow->academicYearMonthID;		
				$AllFeeDiscountDetails[$SearchRow->feeHeadID][$SearchRow->feeStructureDetailID]['DiscountType'] = $SearchRow->discountType;
				$AllFeeDiscountDetails[$SearchRow->feeHeadID][$SearchRow->feeStructureDetailID]['DiscountValue'] = $SearchRow->discountValue;
				$AllFeeDiscountDetails[$SearchRow->feeHeadID][$SearchRow->feeStructureDetailID]['ConcessionAmount'] = $SearchRow->concessionAmount;
				$AllFeeDiscountDetails[$SearchRow->feeHeadID][$SearchRow->feeStructureDetailID]['WaveOffAmount'] = $SearchRow->waveOffAmount;
			}

			return $AllFeeDiscountDetails;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at FeeDiscount::GetFeeStructureDiscount(). Stack Trace: ' . $e->getTraceAsString());
			return false;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at FeeDiscount::GetFeeStructureDiscount(). Stack Trace: ' . $e->getTraceAsString());
			return false;
		}
	}

	static function SetFeeConcession($StudentID, $ConcessionDetails)
	{
		$AllFeeDiscountDetails = array();
		try
		{
			$DBConnObject = new DBConnect();
			
			if (count($ConcessionDetails) > 0) 
			{
				$RSSearchFeeGroupID = $DBConnObject->Prepare('SELECT feeGroupID FROM afm_fee_group_assigned_records WHERE recordID = :|1;');
				$RSSearchFeeGroupID->Execute($StudentID);

				$FeeGroupID = 0;
				if ($RSSearchFeeGroupID->Result->num_rows > 0) 
				{
					$FeeGroupID = $RSSearchFeeGroupID->FetchRow()->feeGroupID;
				}

				foreach ($ConcessionDetails as $StudentFeeStructureID => $ConcessionAmount) 
				{
					if ($ConcessionAmount) 
					{
						$RSSearchFeeStructureDetailID = $DBConnObject->Prepare('SELECT feeStructureDetailID FROM afm_student_fee_structure WHERE studentFeeStructureID = :|1 AND studentID = :|2;');
						$RSSearchFeeStructureDetailID->Execute($StudentFeeStructureID, $StudentID);

						$FeeStructureDetailID = $RSSearchFeeStructureDetailID->FetchRow()->feeStructureDetailID;

						$RSSearchFeeDiscountID = $DBConnObject->Prepare('SELECT feeDiscountID FROM afm_fee_discounts WHERE studentID = :|1 AND feeStructureDetailID = :|2;');
						$RSSearchFeeDiscountID->Execute($StudentID, $FeeStructureDetailID);

						if ($RSSearchFeeDiscountID->Result->num_rows > 0)
						{
							$RSUpdate = $DBConnObject->Prepare('UPDATE afm_fee_discounts
																SET concessionAmount = :|1
																WHERE feeDiscountID = :|2;');
					
							$RSUpdate->Execute($ConcessionAmount, $RSSearchFeeDiscountID->FetchRow()->feeDiscountID);
						}
						else
						{
							$RSSave = $DBConnObject->Prepare('INSERT INTO afm_fee_discounts (feeDiscountType, feeGroupID, studentID, feeStructureDetailID, discountType, discountValue, concessionAmount)
															VALUES (:|1, :|2, :|3, :|4, :|5, :|6, :|7);');
				
							$RSSave->Execute('Student', $FeeGroupID, $StudentID, $FeeStructureDetailID, 'Absolute', 0, $ConcessionAmount);
						}
					}
				}
			}

			return true;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at FeeDiscount::SetFeeConcession(). Stack Trace: ' . $e->getTraceAsString());
			return false;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at FeeDiscount::SetFeeConcession(). Stack Trace: ' . $e->getTraceAsString());
			return false;
		}
	}

	static function SetWaveOffFee($StudentID, $WaveOffList)
	{
		$AllFeeDiscountDetails = array();
		try
		{
			$DBConnObject = new DBConnect();
			
			if (count($WaveOffList) > 0) 
			{
				$RSSearchFeeGroupID = $DBConnObject->Prepare('SELECT feeGroupID FROM afm_fee_group_assigned_records WHERE recordID = :|1;');
				$RSSearchFeeGroupID->Execute($StudentID);

				$FeeGroupID = 0;
				if ($RSSearchFeeGroupID->Result->num_rows > 0) 
				{
					$FeeGroupID = $RSSearchFeeGroupID->FetchRow()->feeGroupID;
				}

				foreach ($WaveOffList as $StudentFeeStructureID => $WaveOffAmount) 
				{
					if ($StudentFeeStructureID) 
					{
						$RSSearchFeeStructureDetailID = $DBConnObject->Prepare('SELECT feeStructureDetailID, amountPayable FROM afm_student_fee_structure WHERE studentFeeStructureID = :|1 AND studentID = :|2;');
						$RSSearchFeeStructureDetailID->Execute($StudentFeeStructureID, $StudentID);

						$SearchRow = $RSSearchFeeStructureDetailID->FetchRow();

						$FeeStructureDetailID = $SearchRow->feeStructureDetailID;
						// $WaveOffAmount = $SearchRow->amountPayable;

						$RSSearchFeeDiscountID = $DBConnObject->Prepare('SELECT feeDiscountID FROM afm_fee_discounts WHERE studentID = :|1 AND feeStructureDetailID = :|2;');
						$RSSearchFeeDiscountID->Execute($StudentID, $FeeStructureDetailID);

						if ($RSSearchFeeDiscountID->Result->num_rows > 0)
						{
							$RSUpdate = $DBConnObject->Prepare('UPDATE afm_fee_discounts
																SET waveOffAmount = :|1
																WHERE feeDiscountID = :|2;');
					
							$RSUpdate->Execute($WaveOffAmount, $RSSearchFeeDiscountID->FetchRow()->feeDiscountID);
						}
						else
						{
							$RSSave = $DBConnObject->Prepare('INSERT INTO afm_fee_discounts (feeDiscountType, feeGroupID, studentID, feeStructureDetailID, discountType, discountValue, waveOffAmount)
															VALUES (:|1, :|2, :|3, :|4, :|5, :|6, :|7);');
				
							$RSSave->Execute('Student', $FeeGroupID, $StudentID, $FeeStructureDetailID, 'Absolute', 0, $WaveOffAmount);
						}
					}
				}
			}

			return true;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at FeeDiscount::SetWaveOffFee(). Stack Trace: ' . $e->getTraceAsString());
			return false;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at FeeDiscount::SetWaveOffFee(). Stack Trace: ' . $e->getTraceAsString());
			return false;
		}
	}

	static function SearchStudentDiscountDetails(&$TotalRecords = 0, $GetTotalsOnly = false, $Filters = array(), $Start = 0, $Limit = 100)
	{
		$StudentDiscountDetails = array();
		
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
				
				if (!empty($Filters['StudentID']))
				{
					$Conditions[] = 'asd.studentID = '. $DBConnObject->RealEscapeVariable($Filters['StudentID']);
				}
				
				if (!empty($Filters['StudentName']))
				{
					$Conditions[] = 'CONCAT(asd.firstName, " ", asd.lastName) LIKE ' . $DBConnObject->RealEscapeVariable('%' . $Filters['StudentName'] . '%');
				}
				
				if (!empty($Filters['MobileNumber']))
				{
					$Conditions[] = 'asd.mobileNumber = '.$DBConnObject->RealEscapeVariable($Filters['MobileNumber']) .' OR apd.fatherMobileNumber = '.$DBConnObject->RealEscapeVariable($Filters['MobileNumber']) . ' OR apd.motherMobileNumber = '.$DBConnObject->RealEscapeVariable($Filters['MobileNumber']);
				}

				if (count($Filters['MonthList']) > 0)
				{
					$Conditions[] = 'fsd.academicYearMonthID IN ('. implode(', ', $Filters['MonthList']) .')';
				}

				if (!empty($Filters['Discount']))
				{
					$Conditions[] = 'afd1.discountValue > 0';
				}

				if (!empty($Filters['Concession']))
				{
					$Conditions[] = 'afd1.concessionAmount > 0 ';
				}

				if (!empty($Filters['WaveOff']))
				{
					$Conditions[] = 'afd1.waveOffAmount > 0 ';
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
				$RSTotal = $DBConnObject->Prepare('SELECT COUNT(asd.studentID) AS totalRecords 
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
													GROUP BY afd1.feeDiscountID;');
				$RSTotal->Execute();
				
				$TotalRecords = $RSTotal->Result->num_rows;
				return;
			}
			
			$RSSearch = $DBConnObject->Prepare('SELECT asd.studentID, asd.firstName, asd.lastName, ac.classID, ac.className, asm.sectionName, aym.monthName, fh.feeHeadID, fh.feeHead, afd1.feeDiscountID,
												SUM(sfs.amountPayable) AS totalAmountPayable, apd.fatherMobileNumber, apd.motherMobileNumber, asd.mobileNumber, 
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

												INNER JOIN asa_student_details asd ON asd.studentID = sfs.studentID 
												LEFT JOIN asa_student_previous_academic_year_details spyd ON spyd.studentID = asd.studentID
												INNER JOIN asa_students ass ON ass.studentID = asd.studentID 
												INNER JOIN asa_parent_details apd ON apd.parentID = ass.parentID
												'. $JoinClassSectionTable .' 
												INNER JOIN asa_classes ac ON ac.classID = acs.classID 
												INNER JOIN asa_section_master asm ON asm.sectionMasterID = acs.sectionMasterID
												'. $QueryString .'
												GROUP BY asd.studentID, fsd.academicYearMonthID, fh.feeHeadID
												ORDER BY ac.priority, acs.priority, asd.firstName, asd.lastName LIMIT ' . (int) $Start . ', ' . (int) $Limit . ';');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $StudentDiscountDetails; 
			}
			
			while($SearchRow = $RSSearch->FetchRow())
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

				$StudentDiscountDetails[$SearchRow->feeDiscountID]['StudentName'] = $SearchRow->firstName .' '. $SearchRow->lastName;
				$StudentDiscountDetails[$SearchRow->feeDiscountID]['ClassID'] = $SearchRow->classID;
				$StudentDiscountDetails[$SearchRow->feeDiscountID]['ClassName'] = $SearchRow->className;
				$StudentDiscountDetails[$SearchRow->feeDiscountID]['SectionName'] = $SearchRow->sectionName;

				$StudentDiscountDetails[$SearchRow->feeDiscountID]['MonthName'] = $SearchRow->monthName;
				$StudentDiscountDetails[$SearchRow->feeDiscountID]['FeeHead'] = $SearchRow->feeHead;

				$StudentDiscountDetails[$SearchRow->feeDiscountID]['TotalAmount'] = $SearchRow->totalAmountPayable;
				$StudentDiscountDetails[$SearchRow->feeDiscountID]['DiscountAmount'] = $DiscountValue;
				$StudentDiscountDetails[$SearchRow->feeDiscountID]['TotalConcession'] = $SearchRow->totalConcession;
				$StudentDiscountDetails[$SearchRow->feeDiscountID]['TotalWaveOff'] = $SearchRow->totalWaveOff;
			}
			
			return $StudentDiscountDetails;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at FeeDiscount::SearchStudentDiscountDetails(). Stack Trace: ' . $e->getTraceAsString());
			return $StudentDiscountDetails;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at FeeDiscount::SearchStudentDiscountDetails(). Stack Trace: ' . $e->getTraceAsString());
			return $StudentDiscountDetails;
		}
	}

	static function RemoveDiscount($DiscountType, $FeeDiscountID)
	{
		try
		{
			$DBConnObject = new DBConnect();
			
			if ($DiscountType == 'WaveOff')
			{
				$RSUpdate = $DBConnObject->Prepare('UPDATE afm_fee_discounts
													SET waveOffAmount = 0
													WHERE feeDiscountID = :|1;');
		
				$RSUpdate->Execute($FeeDiscountID);
			}
			else if ($DiscountType == 'Concession')
			{
				$RSUpdate = $DBConnObject->Prepare('UPDATE afm_fee_discounts
													SET concessionAmount = 0
													WHERE feeDiscountID = :|1;');
		
				$RSUpdate->Execute($FeeDiscountID);
			}
			else if ($DiscountType == 'Discount')
			{
				$RSUpdate = $DBConnObject->Prepare('UPDATE afm_fee_discounts
													SET discountValue = 0
													WHERE feeDiscountID = :|1;');
		
				$RSUpdate->Execute($FeeDiscountID);
			}

			return true;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at FeeDiscount::RemoveDiscount(). Stack Trace: ' . $e->getTraceAsString());
			return false;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at FeeDiscount::RemoveDiscount(). Stack Trace: ' . $e->getTraceAsString());
			return false;
		}
	}
	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//
	
	private function GetFeeDiscountByID()
	{
		$RSFeeDiscount = $this->DBObject->Prepare('SELECT * FROM afm_fee_discounts WHERE feeDiscountID = :|1 LIMIT 1;');
		$RSFeeDiscount->Execute($this->FeeDiscountID);
		
		$FeeDiscountRow = $RSFeeDiscount->FetchRow();
		
		$this->SetAttributesFromDB($FeeDiscountRow);				
	}
	
	private function SetAttributesFromDB($FeeDiscountRow)
	{
		$this->FeeDiscountID = $FeeDiscountRow->feeDiscountID;
		$this->FeeDiscountType = $FeeDiscountRow->feeDiscountType;

		$this->StudentID = $FeeDiscountRow->studentID;
		$this->FeeStructureDetailID = $FeeDiscountRow->feeStructureDetailID;

		$this->DiscountType = $FeeDiscountRow->discountType;
		$this->DiscountValue = $FeeDiscountRow->discountValue;
	}	
}
?>
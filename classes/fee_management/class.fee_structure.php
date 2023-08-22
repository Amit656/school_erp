<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.db_connect.php');
error_reporting(E_ALL);

class FeeStructure
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	
	private $FeeStructureID;
	private $AcademicYearID;

	private $ClassID;
	
	private $IsActive;
	private $CreateUserID;
	private $CreateDate;

	private $FeeStructureDetails = array();
	
	// PUBLIC METHODS START HERE	//
	public function __construct($FeeStructureID = 0)
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;
		
		if($FeeStructureID != 0)
		{
			$this->FeeStructureID = $FeeStructureID;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetFeeStructureByID();
		}
		else
		{
			//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
			$this->FeeStructureID = 0;
			$this->AcademicYearID = 0;

			$this->ClassID = 0;
			
			$this->IsActive = 0;			
			$this->CreateUserID = 0;
			$this->CreateDate = '0000-00-00 00:00:00';

			$this->FeeStructureDetails = array();
		}
	}
	
	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetFeeStructureID()
	{
		return $this->FeeStructureID;
	}
	
	public function GetAcademicYearID()
	{
		return $this->AcademicYearID;
	}
	public function SetAcademicYearID($AcademicYearID)
	{
		$this->AcademicYearID = $AcademicYearID;
	}

	public function GetClassID()
	{
		return $this->ClassID;
	}
	public function SetClassID($ClassID)
	{
		$this->ClassID = $ClassID;
	}

	public function GetIsActive()
	{
		return $this->IsActive;
	}
	public function SetIsActive($IsActive)
	{
		$this->IsActive = $IsActive;
	}

	public function GetFeeStructureDetails()
	{
		return $this->FeeStructureDetails;
	}
	public function SetFeeStructureDetails($FeeStructureDetails)
	{
		$this->FeeStructureDetails = $FeeStructureDetails;
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
	// END OF PUBLIC METHODS	//
	
	// START OF STATIC METHODS	//
	static function FeeStructureExists($AcademicYearID, $ClassID = 0, $FeeGroupID = 0, &$FeeStructureDetails = array(), $StudentID = 0)
	{
		try
		{
			$Conditions = ''; 

			$DBConnObject = new DBConnect();
			
			if ($FeeGroupID > 0) 
			{
				$Conditions = 'AND afs.feeGroupID = '.$DBConnObject->RealEscapeVariable($FeeGroupID);
			}

			if ($ClassID > 0) 
			{
				$Conditions = $Conditions .'AND afs.classID = '.$DBConnObject->RealEscapeVariable($ClassID);
			}
			
            if ($StudentID > 0)
            {
                $RSSearch = $DBConnObject->Prepare('SELECT afs.feeStructureID, afs.feeGroupID, afsd.*, asfs.amountPayable, asfs.studentID
                                                    FROM afm_student_fee_structure asfs
                                                    INNER JOIN afm_fee_structure_details afsd ON afsd.feeStructureDetailID = asfs.feeStructureDetailID
                                                    INNER JOIN afm_fee_structure afs ON afs.feeStructureID = afsd.feeStructureID
    												WHERE afs.academicYearID = :|1 AND asfs.studentID = :|2 '. $Conditions .';');
    			$RSSearch->Execute($AcademicYearID, $StudentID);
    			
    			if ($RSSearch->Result->num_rows <= 0)
    			{
    				return false;
    			}
    			while ($SearchRow = $RSSearch->FetchRow()) 
    			{
    				$FeeStructureDetails[$SearchRow->feeGroupID][$SearchRow->feeHeadID]['FeeStructureDetailID'] = $SearchRow->feeStructureDetailID;	
    				$FeeStructureDetails[$SearchRow->feeGroupID][$SearchRow->feeHeadID]['FeeHeadAmount'] = $SearchRow->amountPayable;	
    				$FeeStructureDetails[$SearchRow->feeGroupID][$SearchRow->feeHeadID]['FeeHeadApplicableMonths'][$SearchRow->academicYearMonthID] = $SearchRow->feeStructureDetailID;	
    			}		
            }
            else
            {
                $RSSearch = $DBConnObject->Prepare('SELECT afs.feeStructureID, afs.feeGroupID, afsd.* 
    												FROM afm_fee_structure afs
    												INNER JOIN afm_fee_structure_details afsd ON afsd.feeStructureID = afs.feeStructureID
    												WHERE afs.academicYearID = :|1 '. $Conditions .';');
    			$RSSearch->Execute($AcademicYearID);
    			
    			if ($RSSearch->Result->num_rows <= 0)
    			{
    				return false;
    			}
    			while ($SearchRow = $RSSearch->FetchRow()) 
    			{
    				$FeeStructureDetails[$SearchRow->feeGroupID][$SearchRow->feeHeadID]['FeeStructureDetailID'] = $SearchRow->feeStructureDetailID;	
    				$FeeStructureDetails[$SearchRow->feeGroupID][$SearchRow->feeHeadID]['FeeHeadAmount'] = $SearchRow->feeAmount;	
    				$FeeStructureDetails[$SearchRow->feeGroupID][$SearchRow->feeHeadID]['FeeHeadApplicableMonths'][$SearchRow->academicYearMonthID] = $SearchRow->feeStructureDetailID;	
    			}		
            }
			
			return true;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at FeeStructure::FeeStructureExists(). Stack Trace: ' . $e->getTraceAsString());
			return false;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at FeeStructure::FeeStructureExists(). Stack Trace: ' . $e->getTraceAsString());
			return false;
		}
	}

	static function FeeStructureByStudent($studentID)
	{
		$AllFeeStructureDetails = array();
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSFeeStructureDetails = $DBConnObject->Prepare('SELECT afsd.* FROM afm_fee_structure_details afsd 
																INNER JOIN afm_student_fee_structure asfs ON asfs.feeStructureDetailID = afsd.feeStructureDetailID
																WHERE asfs.studentID = :|1;');
			$RSFeeStructureDetails->Execute($studentID);

			if ($RSFeeStructureDetails->Result->num_rows <= 0)
			{
				return $AllFeeStructureDetails;
			}

			while($SearchRow = $RSFeeStructureDetails->FetchRow())
			{
				$AllFeeStructureDetails[$SearchRow->feeHeadID][$SearchRow->feeStructureDetailID]['AcademicYearMonthID'] = $SearchRow->academicYearMonthID;
				$AllFeeStructureDetails[$SearchRow->feeHeadID][$SearchRow->feeStructureDetailID]['FeeAmount'] = $SearchRow->feeAmount;
			}

			return $AllFeeStructureDetails;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at FeeStructure::FeeStructureByStudent(). Stack Trace: ' . $e->getTraceAsString());
			return false;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at FeeStructure::FeeStructureByStudent(). Stack Trace: ' . $e->getTraceAsString());
			return false;
		}
	}

	static function GetApplicableClassesForFeeGroup($FeeGroupID)
	{
		$FeeGroupClasses = array();
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSFeeGroupClasses = $DBConnObject->Prepare('SELECT afs.classID, ac.className FROM afm_fee_structure afs
														INNER JOIN asa_classes ac ON ac.classID = afs.classID
														WHERE afs.feeGroupID = :|1;');
			$RSFeeGroupClasses->Execute($FeeGroupID);

			if ($RSFeeGroupClasses->Result->num_rows <= 0)
			{
				return $FeeGroupClasses;
			}

			while($SearchRow = $RSFeeGroupClasses->FetchRow())
			{
				$FeeGroupClasses[$SearchRow->classID] = $SearchRow->className;
			}

			return $FeeGroupClasses;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at FeeStructure::GetApplicableClassesForFeeGroup(). Stack Trace: ' . $e->getTraceAsString());
			return false;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at FeeStructure::GetApplicableClassesForFeeGroup(). Stack Trace: ' . $e->getTraceAsString());
			return false;
		}
	}
	
	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//
	private function SaveDetails()
	{
		if (!FeeStructure::FeeStructureExists($this->AcademicYearID, $this->ClassID))
		{			
			foreach ($this->FeeStructureDetails as $FeeGroupID => $FeeStructureDetails) 
			{
				$RSSaveFeeStructure = $this->DBObject->Prepare('INSERT INTO afm_fee_structure (academicYearID, classID, feeGroupID, isActive, createUserID, createDate)
																	VALUES (:|1, :|2, :|3, :|4, :|5, NOW());');
			
				$RSSaveFeeStructure->Execute($this->AcademicYearID, $this->ClassID, $FeeGroupID, $this->IsActive, $this->CreateUserID);
				
				$this->FeeStructureID = $RSSaveFeeStructure->LastID;

				foreach ($FeeStructureDetails as $FeeHeadID => $Details) 
				{
				    $RSSearchFeeHead = $this->DBObject->Prepare('SELECT feeHead FROM afm_fee_heads  
																			WHERE feeHeadID = :|1;');
				
					$RSSearchFeeHead->Execute($FeeHeadID);

					$FeeHead = '';
					$FeeHead = $RSSearchFeeHead->FetchRow()->feeHead;
					
					foreach ($Details['AcademicYearMonthID'] as $AcademicYearMonthID => $Value) 
					{
						$RSSave = $this->DBObject->Prepare('INSERT INTO afm_fee_structure_details (feeStructureID, feeHeadID, academicYearMonthID, feeAmount)
																VALUES (:|1, :|2, :|3, :|4);');
					
						$RSSave->Execute($this->FeeStructureID, $FeeHeadID, $AcademicYearMonthID, $Details['FeeAmount']);

						$FeeStructureDetailID = $RSSave->LastID;

						$RSRecordID = $this->DBObject->Prepare('SELECT DISTINCT(afgar.recordID) FROM afm_fee_group_assigned_records afgar 
                                        						INNER JOIN afm_fee_structure afs ON afgar.feeGroupID = afs.feeGroupID 
                                        						WHERE afgar.feeGroupID = :|1  AND afs.classID = :|2
                                        						AND afgar.recordID IN (SELECT studentID FROM asa_students ass INNER JOIN asa_class_sections acs ON acs.classSectionID = ass.classSectionID WHERE acs.classID = :|3 AND ass.academicYearID = :|4);');
					
						$RSRecordID->Execute($FeeGroupID, $this->ClassID, $this->ClassID, $this->AcademicYearID);
                        
                        if ($RSRecordID->Result->num_rows > 0)
                        {
                            while($SearchRow = $RSRecordID->FetchRow())
    						{
    							$AmountPayable = $Details['FeeAmount'];

    							if ($FeeHead == 'Transport') 
								{
									$RSSearchTransportAmount = $this->DBObject->Prepare('SELECT aawf.amount FROM atm_student_vehicle asv  
																						INNER JOIN atm_area_wise_fee aawf ON aawf.areaWiseFeeID = asv.areaWiseFeeID
																						WHERE asv.studentID = :|1;');
								
									$RSSearchTransportAmount->Execute($SearchRow->recordID);

									if ($RSSearchTransportAmount->Result->num_rows > 0) 
									{
										$AmountPayable = $RSSearchTransportAmount->FetchRow()->amount;
									}

								}

    							if ($AmountPayable > 0) 
								{
									$RSSaveStudentFeeStrucutre = $this->DBObject->Prepare('INSERT INTO afm_student_fee_structure (feeStructureDetailID, studentID, amountPayable)
    																						VALUES(:|1, :|2, :|3);');
    							
    								$RSSaveStudentFeeStrucutre->Execute($FeeStructureDetailID, $SearchRow->recordID, $AmountPayable);
								}
    						}
                        }
					}
				}
			}
		}
		else
		{		
			foreach ($this->FeeStructureDetails as $FeeGroupID => $FeeStructureDetails) 
			{
				$RSSearchFeeStructureID = $this->DBObject->Prepare('SELECT feeStructureID FROM afm_fee_structure
																	WHERE academicYearID = :|1 AND classID = :|2 AND feeGroupID = :|3;');
			
				$RSSearchFeeStructureID->Execute($this->AcademicYearID, $this->ClassID, $FeeGroupID);

				if ($RSSearchFeeStructureID->Result->num_rows <= 0)
				{
					$RSSaveFeeStructure = $this->DBObject->Prepare('INSERT INTO afm_fee_structure (academicYearID, classID, feeGroupID, isActive, createUserID, createDate)
																	VALUES (:|1, :|2, :|3, :|4, :|5, NOW());');
			
					$RSSaveFeeStructure->Execute($this->AcademicYearID, $this->ClassID, $FeeGroupID, $this->IsActive, $this->CreateUserID);
					
					$FeeStructureID = $RSSaveFeeStructure->LastID;
				}
				else
				{
					$FeeStructureID = $RSSearchFeeStructureID->FetchRow()->feeStructureID;
				}

				foreach ($FeeStructureDetails as $FeeHeadID => $Details) 
				{
				    $RSSearchFeeHead = $this->DBObject->Prepare('SELECT feeHead FROM afm_fee_heads  
																			WHERE feeHeadID = :|1;');
				
					$RSSearchFeeHead->Execute($FeeHeadID);

					$FeeHead = '';
					$FeeHead = $RSSearchFeeHead->FetchRow()->feeHead;
					
					if (array_key_exists('AddMonths', $Details) && count($Details['AddMonths']) > 0) 
					{
						foreach ($Details['AddMonths'] as $AcademicYearMonthID => $Value) 
						{
							$RSSave = $this->DBObject->Prepare('INSERT INTO afm_fee_structure_details (feeStructureID, feeHeadID, academicYearMonthID, feeAmount)
																	VALUES (:|1, :|2, :|3, :|4);');
						
							$RSSave->Execute($FeeStructureID, $FeeHeadID, $AcademicYearMonthID, $Details['FeeAmount']);

							$FeeStructureDetailID = $RSSave->LastID;

							$RSRecordID = $this->DBObject->Prepare('SELECT DISTINCT(afgar.recordID) FROM afm_fee_group_assigned_records afgar 
                                            						INNER JOIN afm_fee_structure afs ON afgar.feeGroupID = afs.feeGroupID 
                                            						WHERE afgar.feeGroupID = :|1  AND afs.classID = :|2
                                            						AND afgar.recordID IN (SELECT studentID FROM asa_students ass INNER JOIN asa_class_sections acs ON acs.classSectionID = ass.classSectionID WHERE acs.classID = :|3 AND ass.academicYearID = :|4);');
						
							$RSRecordID->Execute($FeeGroupID, $this->ClassID, $this->ClassID, $this->AcademicYearID);
                            
                            if ($RSRecordID->Result->num_rows > 0)
                            {
                               while($SearchRow = $RSRecordID->FetchRow())
    							{
    								$AmountPayable = $Details['FeeAmount'];

	    							if ($FeeHead == 'Transport') 
									{
										$RSSearchTransportAmount = $this->DBObject->Prepare('SELECT aawf.amount FROM atm_student_vehicle asv  
																							INNER JOIN atm_area_wise_fee aawf ON aawf.areaWiseFeeID = asv.areaWiseFeeID
																							WHERE asv.studentID = :|1;');
									
										$RSSearchTransportAmount->Execute($SearchRow->recordID);

										if ($RSSearchTransportAmount->Result->num_rows > 0) 
										{
											$AmountPayable = $RSSearchTransportAmount->FetchRow()->amount;
										}
									}

									if ($AmountPayable > 0) 
									{
										$RSSaveStudentFeeStrucutre = $this->DBObject->Prepare('INSERT INTO afm_student_fee_structure (feeStructureDetailID, studentID, amountPayable)
	    																						VALUES(:|1, :|2, :|3);');
	    							
	    								$RSSaveStudentFeeStrucutre->Execute($FeeStructureDetailID, $SearchRow->recordID, $AmountPayable);
									}
    							} 
                            }
						}
					}
					
					foreach ($Details['AcademicYearMonthID'] as $AcademicYearMonthID => $Value) 
					{
						$RSSearch = $this->DBObject->Prepare('SELECT COUNT(*) as totalRecords FROM afm_fee_structure_details afsd
															INNER JOIN afm_student_fee_structure asfs ON asfs.feeStructureDetailID = afsd.feeStructureDetailID
															WHERE afsd.feeStructureID = :|1 AND afsd.feeHeadID = :|2 AND afsd.academicYearMonthID = :|3 
															AND asfs.studentFeeStructureID IN (SELECT studentFeeStructureID FROM afm_fee_collection_details);');
					
						$RSSearch->Execute($FeeStructureID, $FeeHeadID, $AcademicYearMonthID);
    
						if ($RSSearch->FetchRow()->totalRecords <= 0) 
						{
							$RSSearchFeeStructureDetailID = $this->DBObject->Prepare('SELECT feeStructureDetailID FROM afm_fee_structure_details
																                        WHERE feeStructureID = :|1 AND feeHeadID = :|2 AND academicYearMonthID = :|3;');
							
							$RSSearchFeeStructureDetailID->Execute($FeeStructureID, $FeeHeadID, $AcademicYearMonthID);

							if ($RSSearchFeeStructureDetailID->Result->num_rows <= 0)
							{
							    $RSSave = $this->DBObject->Prepare('INSERT INTO afm_fee_structure_details (feeStructureID, feeHeadID, academicYearMonthID, feeAmount)
																	VALUES (:|1, :|2, :|3, :|4);');
						
    							$RSSave->Execute($FeeStructureID, $FeeHeadID, $AcademicYearMonthID, $Details['FeeAmount']);
    
    							$FeeStructureDetailID = $RSSave->LastID;
    							
    							$RSRecordID = $this->DBObject->Prepare('SELECT DISTINCT(afgar.recordID) FROM afm_fee_group_assigned_records afgar 
                                                						INNER JOIN afm_fee_structure afs ON afgar.feeGroupID = afs.feeGroupID 
                                                						WHERE afgar.feeGroupID = :|1  AND afs.classID = :|2
                                                						AND afgar.recordID IN (SELECT studentID FROM asa_students ass INNER JOIN asa_class_sections acs ON acs.classSectionID = ass.classSectionID WHERE acs.classID = :|3 AND ass.academicYearID = :|4);');
    						
    							$RSRecordID->Execute($FeeGroupID, $this->ClassID, $this->ClassID, $this->AcademicYearID);
                                
                                if ($RSRecordID->Result->num_rows > 0)
                                {
                                    while($SearchRow = $RSRecordID->FetchRow())
        							{
        								$AmountPayable = $Details['FeeAmount'];

		    							if ($FeeHead == 'Transport') 
										{
											$RSSearchTransportAmount = $this->DBObject->Prepare('SELECT aawf.amount FROM atm_student_vehicle asv  
																								INNER JOIN atm_area_wise_fee aawf ON aawf.areaWiseFeeID = asv.areaWiseFeeID
																								WHERE asv.studentID = :|1;');
										
											$RSSearchTransportAmount->Execute($SearchRow->recordID);

											if ($RSSearchTransportAmount->Result->num_rows > 0) 
											{
												$AmountPayable = $RSSearchTransportAmount->FetchRow()->amount;
											}

										}

        								if ($AmountPayable > 0) 
										{
											$RSSaveStudentFeeStrucutre = $this->DBObject->Prepare('INSERT INTO afm_student_fee_structure (feeStructureDetailID, studentID, amountPayable)
		    																						VALUES(:|1, :|2, :|3);');
		    							
		    								$RSSaveStudentFeeStrucutre->Execute($FeeStructureDetailID, $SearchRow->recordID, $AmountPayable);
										}
        							}   
                                }
							}
							else
							{
							    $FeeStructureDetailID = $RSSearchFeeStructureDetailID->FetchRow()->feeStructureDetailID;
								
								if ($FeeHead != 'Transport') 
								{
									$RSUpdateFeeStructureDetails = $this->DBObject->Prepare('UPDATE afm_fee_structure_details
																							SET	feeAmount = :|1								
																							WHERE feeStructureDetailID = :|2 LIMIT 1;');
	    
	    							$RSUpdateFeeStructureDetails->Execute($Details['FeeAmount'], $FeeStructureDetailID);

	    							$RSUpdateStudentFeeStructure = $this->DBObject->Prepare('UPDATE afm_student_fee_structure
																							SET	amountPayable = :|1								
																							WHERE feeStructureDetailID = :|2;');
	    
	    							$RSUpdateStudentFeeStructure->Execute($Details['FeeAmount'], $FeeStructureDetailID);
								}
							}
						}
					}
					
					if (array_key_exists('RemoveMonths', $Details) && count($Details['RemoveMonths']) > 0) 
					{
						foreach ($Details['RemoveMonths'] as $AcademicYearMonthID => $Value) 
						{
							$RSSearch = $this->DBObject->Prepare('SELECT COUNT(*) as totalRecords FROM afm_fee_structure_details afsd
																INNER JOIN afm_student_fee_structure asfs ON asfs.feeStructureDetailID = afsd.feeStructureDetailID
																WHERE afsd.feeStructureID = :|1 AND afsd.feeHeadID = :|2 AND afsd.academicYearMonthID = :|3 
																AND asfs.studentFeeStructureID IN (SELECT studentFeeStructureID FROM afm_fee_collection_details);');
						
							$RSSearch->Execute($FeeStructureID, $FeeHeadID, $AcademicYearMonthID);

							if ($RSSearch->FetchRow()->totalRecords <= 0) 
							{
								$RSSearchFeeStructureDetailID = $this->DBObject->Prepare('SELECT feeStructureDetailID FROM afm_fee_structure_details
																                        	WHERE feeStructureID = :|1 AND feeHeadID = :|2 AND academicYearMonthID = :|3;');
								
								$RSSearchFeeStructureDetailID->Execute($FeeStructureID, $FeeHeadID, $AcademicYearMonthID);

								$FeeStructureDetailID = $RSSearchFeeStructureDetailID->FetchRow()->feeStructureDetailID;
                                
								$RSDeleteFeeStructureDetail = $this->DBObject->Prepare('DELETE FROM afm_fee_structure_details WHERE feeStructureDetailID = :|1 LIMIT 1;');
								$RSDeleteFeeStructureDetail->Execute($FeeStructureDetailID);

								$RSDeleteStudentFeeStructure = $this->DBObject->Prepare('DELETE FROM afm_student_fee_structure WHERE feeStructureDetailID = :|1;');
								$RSDeleteStudentFeeStructure->Execute($FeeStructureDetailID);							
							}
						}
					}
				}
			}
		}

		return true;
	}
	
	private function GetFeeStructureByID()
	{
		$RSFeeStructure = $this->DBObject->Prepare('SELECT * FROM afm_fee_structure WHERE feeStructureID = :|1 LIMIT 1;');
		$RSFeeStructure->Execute($this->FeeStructureID);
		
		$FeeStructureRow = $RSFeeStructure->FetchRow();
		
		$this->SetAttributesFromDB($FeeStructureRow);				
	}
	
	private function SetAttributesFromDB($FeeStructureRow)
	{
		$this->FeeStructureID = $FeeStructureRow->feeStructureID;
		$this->AcademicYearID = $FeeStructureRow->academicYearID;

		$this->ClassID = $FeeStructureRow->classID;
		
		$this->IsActive = $FeeStructureRow->isActive;
		$this->CreateUserID = $FeeStructureRow->createUserID;
		$this->CreateDate = $FeeStructureRow->createDate;

		$RSFeeStructureDetails = $this->DBObject->Prepare('SELECT * FROM afm_fee_structure_details WHERE feeStructureID = :|1;');
		$RSFeeStructureDetails->Execute($this->FeeStructureID);

		if ($RSFeeStructureDetails->Result->num_rows > 0)
		{
			while($SearchRow = $RSFeeStructureDetails->FetchRow())
			{
				$this->FeeStructureDetails[$SearchRow->feeHeadID][$SearchRow->feeStructureDetailID]['AcademicYearMonthID'] = $SearchRow->academicYearMonthID;
				$this->FeeStructureDetails[$SearchRow->feeHeadID][$SearchRow->feeStructureDetailID]['FeeAmount'] = $SearchRow->feeAmount;
			}
		}

	}	
}
?>
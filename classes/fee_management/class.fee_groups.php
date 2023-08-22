<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.db_connect.php');
error_reporting(E_ALL);

class FeeGroup
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	
	private $FeeGroupID;	
	private $FeeGroup;

	private $IsActive;
	private $CreateUserID;
	private $CreateDate;

	private $RecordIDList = array();
	
	// PUBLIC METHODS START HERE	//
	public function __construct($FeeGroupID = 0)
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;
		
		if($FeeGroupID != 0)
		{
			$this->FeeGroupID = $FeeGroupID;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetFeeGroupByID();
		}
		else
		{
			//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
			$this->FeeGroupID = 0;			
			$this->FeeGroup = '';
	
			$this->IsActive = 0;
			$this->CreateUserID = 0;
			$this->CreateDate = '0000-00-00 00:00:00';

			$this->RecordIDList = array();
		}
	}
	
	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetFeeGroupID()
	{
		return $this->FeeGroupID;
	}
	
	public function GetFeeGroup()
	{
		return $this->FeeGroup;
	}
	public function SetFeeGroup($FeeGroup)
	{
		$this->FeeGroup = $FeeGroup;
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

	public function GetRecordIDList()
	{
		return $this->RecordIDList;
	}
	public function SetRecordIDList($RecordIDList)
	{
		$this->RecordIDList = $RecordIDList;
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
            $this->RemoveFeeGroup();
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
            $RSFeeGroupCount = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM afm_fee_group_assigned_records WHERE feeGroupID = :|1;');
            $RSFeeGroupCount->Execute($this->FeeGroupID);           

            if ($RSFeeGroupCount->FetchRow()->totalRecords > 0) 
            {
                return true;
            }

            return false;
        }
        catch (ApplicationDBException $e)
        {            
            error_log('DEBUG: ApplicationDBException at FeeGroup::CheckDependencies(). Stack Trace: ' . $e->getTraceAsString());
            return true;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: ApplicationDBException at FeeGroup::CheckDependencies(). Stack Trace: ' . $e->getTraceAsString());
            return true;
        }       
    }

    public function AssignFeeGroup($RecordIDList = array(), $ClassID, $AcademicYearID, $CreateUserID)
    {	
    	try
        {
        	$this->DBObject->BeginTransaction();
        	
			foreach ($RecordIDList as $FeeGroupID => $RecordList) 
			{
				foreach ($RecordList as $StudentID => $PreviousFeeGroupID) 
				{
					if ($PreviousFeeGroupID != $FeeGroupID) 
					{
					    
						$RSSave = $this->DBObject->Prepare('UPDATE afm_fee_group_assigned_records SET feeGroupID = :|1 WHERE recordID = :|2;');
		
						$RSSave->Execute($FeeGroupID, $StudentID);

						$RSSearchPreviousFeeStructureID = $this->DBObject->Prepare('SELECT feeStructureID FROM afm_fee_structure
																					WHERE academicYearID = :|1 AND classID = :|2 AND feeGroupID = :|3;');
					
						$RSSearchPreviousFeeStructureID->Execute($AcademicYearID, $ClassID, $PreviousFeeGroupID);
						
                        // var_dump($ClassID);exit;
						if($RSSearchPreviousFeeStructureID->Result->num_rows > 0)
						{
							$RSDeleteStudentFeeStructure = $this->DBObject->Prepare('DELETE sfs FROM afm_student_fee_structure sfs
																					INNER JOIN afm_fee_structure_details fsd ON sfs.feeStructureDetailID = fsd.feeStructureDetailID
																					WHERE fsd.feeStructureID = :|1 AND sfs.studentID = :|2 AND sfs.studentFeeStructureID NOT IN (SELECT studentFeeStructureID FROM afm_fee_collection_details);');
							$RSDeleteStudentFeeStructure->Execute($RSSearchPreviousFeeStructureID->FetchRow()->feeStructureID, $StudentID);
						}

						$RSSearchCurrentFeeStructureID = $this->DBObject->Prepare('SELECT feeStructureID FROM afm_fee_structure
																					WHERE academicYearID = :|1 AND classID = :|2 AND feeGroupID = :|3;');
					
						$RSSearchCurrentFeeStructureID->Execute($AcademicYearID, $ClassID, $FeeGroupID);

						if($RSSearchCurrentFeeStructureID->Result->num_rows > 0)
						{
							$RSSearchFeeStructureDetails = $this->DBObject->Prepare('SELECT fsd.* FROM afm_fee_structure_details fsd 
																					WHERE fsd.feeStructureID = :|1 
																					AND fsd.academicYearMonthID NOT IN (SELECT fsd.academicYearMonthID FROM afm_fee_structure_details fsd 
																					INNER JOIN afm_student_fee_structure sfs on sfs.feeStructureDetailID = fsd.feeStructureDetailID 
																					WHERE sfs.studentID = :|2);');
							
							$RSSearchFeeStructureDetails->Execute($RSSearchCurrentFeeStructureID->FetchRow()->feeStructureID, $StudentID);

							if($RSSearchFeeStructureDetails->Result->num_rows > 0)
							{
								while($SearchRow = $RSSearchFeeStructureDetails->FetchRow())
								{
								    $RSSearchFeeHead = $this->DBObject->Prepare('SELECT feeHead FROM afm_fee_heads  
    																			WHERE feeHeadID = :|1;');
                				
                					$RSSearchFeeHead->Execute($SearchRow->feeHeadID);
                
                					$FeeHead = '';
                					$FeeHead = $RSSearchFeeHead->FetchRow()->feeHead;
                					
                					$AmountPayable = $SearchRow->feeAmount;

        							if ($FeeHead == 'Transport') 
    								{
    									$RSSearchTransportAmount = $this->DBObject->Prepare('SELECT aawf.amount FROM atm_student_vehicle asv  
    																						INNER JOIN atm_area_wise_fee aawf ON aawf.areaWiseFeeID = asv.areaWiseFeeID
    																						WHERE asv.studentID = :|1;');
    								
    									$RSSearchTransportAmount->Execute($StudentID);
    
    									if ($RSSearchTransportAmount->Result->num_rows > 0) 
    									{
    										$AmountPayable = $RSSearchTransportAmount->FetchRow()->amount;
    									}
    
    								}   
                					
									$RSSaveStudentFeeStrucutre = $this->DBObject->Prepare('INSERT INTO afm_student_fee_structure (feeStructureDetailID, studentID, amountPayable)
																							VALUES(:|1, :|2, :|3);');
								
									$RSSaveStudentFeeStrucutre->Execute($SearchRow->feeStructureDetailID, $StudentID, $AmountPayable);
								}
							}
						}
					}
				}
			}

            $this->DBObject->CommitTransaction();
            return true;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException: FeeGroup::AssignFeeGroup(). Stack Trace: ' . $e->getTraceAsString());
            $this->DBObject->RollBackTransaction();
            return false;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception: FeeGroup::AssignFeeGroup(). Stack Trace: ' . $e->getTraceAsString());
            $this->DBObject->RollBackTransaction();
            return false;
        }  	
    }

    public function FillAssignedRecordsToFeeGroup()
    {
		try
        {	
        	$RSSearch = $this->DBObject->Prepare('SELECT recordID, feeGroupAssignedRecordID FROM afm_fee_group_assigned_records WHERE feeGroupID = :|1;');
			$RSSearch->Execute($this->FeeGroupID);

			if($RSSearch->Result->num_rows  > 0)
			{
				while($SearchRow = $RSSearch->FetchRow())
				{
					$this->RecordIDList[$SearchRow->feeGroupAssignedRecordID] = $SearchRow->recordID;
				}
			}

			return true;   
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException: FeeGroup::FillAssignedRecordsToFeeGroup(). Stack Trace: ' . $e->getTraceAsString());
            return false;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception: FeeGroup::FillAssignedRecordsToFeeGroup(). Stack Trace: ' . $e->getTraceAsString());
            return false;
        }
    }

	// END OF PUBLIC METHODS	//
	
	// START OF STATIC METHODS	//
	static function GetAllFeeGroups()
	{
		$AllFeeGroups = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT afg.*, u.userName AS createUserName FROM afm_fee_groups afg
												INNER JOIN users u ON afg.createUserID = u.userID 
												ORDER BY afg.feeGroupID;');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllFeeGroups;
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{				
				$AllFeeGroups[$SearchRow->feeGroupID]['FeeGroup'] = $SearchRow->feeGroup;

				$AllFeeGroups[$SearchRow->feeGroupID]['TotalRecords'] = 0;

				$RSCount = $DBConnObject->Prepare('SELECT COUNT(*) AS totalRecords FROM afm_fee_group_assigned_records
													WHERE feeGroupID = :|1;');
				$RSCount->Execute($SearchRow->feeGroupID);

				if ($RSCount->Result->num_rows > 0)
				{
					$AllFeeGroups[$SearchRow->feeGroupID]['TotalRecords'] = $RSCount->FetchRow()->totalRecords;
				}

				$AllFeeGroups[$SearchRow->feeGroupID]['IsActive'] = $SearchRow->isActive;
				$AllFeeGroups[$SearchRow->feeGroupID]['CreateUserID'] = $SearchRow->createUserID;
				$AllFeeGroups[$SearchRow->feeGroupID]['CreateUserName'] = $SearchRow->createUserName;
				
				$AllFeeGroups[$SearchRow->feeGroupID]['CreateDate'] = $SearchRow->createDate;
			}
			
			return $AllFeeGroups;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at FeeGroup::GetAllFeeGroups(). Stack Trace: ' . $e->getTraceAsString());
			return $AllFeeGroups;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at FeeGroup::GetAllFeeGroups(). Stack Trace: ' . $e->getTraceAsString());
			return $AllFeeGroups;
		}
	}

	static function GetActiveFeeGroups()
	{
		$AllFeeGroups = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT * FROM afm_fee_groups WHERE isActive = 1;');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllFeeGroups;
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$AllFeeGroups[$SearchRow->feeGroupID] = $SearchRow->feeGroup;
			}
			
			return $AllFeeGroups;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at FeeGroup::GetActiveFeeGroups(). Stack Trace: ' . $e->getTraceAsString());
			return $AllFeeGroups;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at FeeGroup::GetActiveFeeGroups(). Stack Trace: ' . $e->getTraceAsString());
			return $AllFeeGroups;
		}		
	}

	static function FeeGroupTypeWiseReports($FeeGroupID)
	{
		$AllRecords = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearchAssignedDetails = $DBConnObject->Prepare('SELECT afgar.*, ass.rollNumber, asd.studentID, asd.firstName, asd.lastName, ac.className, ac.classID, 
																acs.classSectionID, asm.sectionName, u.userName AS createUserName 
																FROM afm_fee_group_assigned_records afgar
																INNER JOIN asa_student_details asd ON afgar.recordID = asd.studentID
																INNER JOIN asa_students ass ON afgar.recordID = ass.studentID
																INNER JOIN asa_class_sections acs ON ass.classSectionID = acs.classSectionID
																INNER JOIN asa_section_master asm ON acs.sectionMasterID = asm.sectionMasterID
																INNER JOIN asa_classes ac ON acs.classID = ac.classID
																INNER JOIN users u ON afgar.createUserID = u.userID
																WHERE afgar.feeGroupID = :|1
																ORDER BY ac.priority, asd.firstName, acs.priority;');
			$RSSearchAssignedDetails->Execute($FeeGroupID);
			
			if ($RSSearchAssignedDetails->Result->num_rows <= 0)
			{
				return $AllRecords;
			}
			
			while ($SearchAssignedDetailsRow = $RSSearchAssignedDetails->FetchRow()) 
			{
				$AllRecords[$SearchAssignedDetailsRow->classID][$SearchAssignedDetailsRow->studentID]['RollNumber'] = $SearchAssignedDetailsRow->rollNumber;
				$AllRecords[$SearchAssignedDetailsRow->classID][$SearchAssignedDetailsRow->studentID]['FirstName'] = $SearchAssignedDetailsRow->firstName;
				$AllRecords[$SearchAssignedDetailsRow->classID][$SearchAssignedDetailsRow->studentID]['LastName'] = $SearchAssignedDetailsRow->lastName;

				$AllRecords[$SearchAssignedDetailsRow->classID][$SearchAssignedDetailsRow->studentID]['ClassName'] = $SearchAssignedDetailsRow->className;
				$AllRecords[$SearchAssignedDetailsRow->classID][$SearchAssignedDetailsRow->studentID]['ClassSectionID'] = $SearchAssignedDetailsRow->classSectionID;
				$AllRecords[$SearchAssignedDetailsRow->classID][$SearchAssignedDetailsRow->studentID]['SectionName'] = $SearchAssignedDetailsRow->sectionName;

				$AllRecords[$SearchAssignedDetailsRow->classID][$SearchAssignedDetailsRow->studentID]['CreateUserID'] = $SearchAssignedDetailsRow->createUserID;
				$AllRecords[$SearchAssignedDetailsRow->classID][$SearchAssignedDetailsRow->studentID]['CreateUserName'] = $SearchAssignedDetailsRow->createUserName;

				$AllRecords[$SearchAssignedDetailsRow->classID][$SearchAssignedDetailsRow->studentID]['CreateDate'] = $SearchAssignedDetailsRow->createDate;
			}
			
			return $AllRecords;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at FeeGroup::FeeGroupTypeWiseReports(). Stack Trace: ' . $e->getTraceAsString());
			return $AllRecords;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at FeeGroup::FeeGroupTypeWiseReports(). Stack Trace: ' . $e->getTraceAsString());
			return $AllRecords;
		}		
	}
	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//
	private function SaveDetails()
	{
		if ($this->FeeGroupID == 0)
		{
			$RSSave = $this->DBObject->Prepare('INSERT INTO afm_fee_groups (feeGroup, isActive, createUserID, createDate)
														VALUES (:|1, :|2, :|3, NOW());');
		
			$RSSave->Execute($this->FeeGroup, $this->IsActive, $this->CreateUserID);
			
			$this->FeeGroupID = $RSSave->LastID;
		}
		else
		{
			$RSUpdate = $this->DBObject->Prepare('UPDATE afm_fee_groups
													SET	feeGroup = :|1,														
														isActive = :|2
													WHERE feeGroupID = :|3 LIMIT 1;');
													
			$RSUpdate->Execute($this->FeeGroup, $this->IsActive, $this->FeeGroupID);
		}
		
		return true;
	}
	
	private function RemoveFeeGroup()
    {
        if(!isset($this->FeeGroupID)) 
        {
            throw new ApplicationARException('', APP_AR_ERROR_DELETE_WITHOUT_ID);
        }
        
        $RSDeleteFeeGroup = $this->DBObject->Prepare('DELETE FROM afm_fee_groups WHERE feeGroupID = :|1 LIMIT 1;');
        $RSDeleteFeeGroup->Execute($this->FeeGroupID); 

        return true;               
    }

	private function GetFeeGroupByID()
	{
		$RSFeeGroup = $this->DBObject->Prepare('SELECT * FROM afm_fee_groups WHERE feeGroupID = :|1 LIMIT 1;');
		$RSFeeGroup->Execute($this->FeeGroupID);
		
		$FeeGroupRow = $RSFeeGroup->FetchRow();
		
		$this->SetAttributesFromDB($FeeGroupRow);				
	}
	
	private function SetAttributesFromDB($FeeGroupRow)
	{
		$this->FeeGroupID = $FeeGroupRow->feeGroupID;		
		$this->FeeGroup = $FeeGroupRow->feeGroup;

		$this->IsActive = $FeeGroupRow->isActive;		
		$this->CreateUserID = $FeeGroupRow->createUserID;
		$this->CreateDate = $FeeGroupRow->createDate;
	}	
}
?>
<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.db_connect.php');
error_reporting(E_ALL);

class StudentVehicle
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	
	private $StudentVehicleID;
	private $AreaWiseFeeID;
	private $VehicleID;
	private $StudentID;
	private $ClassID;
	private $AcademicYearID;

	private $IsActive;
	private $CreateUserID;
	private $CreateDate;

	// PUBLIC METHODS START HERE	//
	public function __construct($StudentVehicleID = 0)
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;
		
		if($StudentVehicleID != 0)
		{
			$this->StudentVehicleID = $StudentVehicleID;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetStudentVehicleByID();
		}
		else
		{
			//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
			$this->StudentVehicleID = 0;
			$this->AreaWiseFeeID = 0;
			$this->VehicleID = 0;
			$this->StudentID = 0;
			$this->ClassID = 0;
			$this->AcademicYearID = 0;
			
			$this->IsActive = 0;
			$this->CreateUserID = 0;
			$this->CreateDate = '0000-00-00 00:00:00';
		}
	}
	
	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetStudentVehicleID()
	{
		return $this->StudentVehicleID;
	}
	
	public function GetAreaWiseFeeID()
	{
		return $this->AreaWiseFeeID;
	}
	public function SetAreaWiseFeeID($AreaWiseFeeID)
	{
		$this->AreaWiseFeeID = $AreaWiseFeeID;
	}

	public function GetVehicleID()
	{
		return $this->VehicleID;
	}
	public function SetVehicleID($VehicleID)
	{
		$this->VehicleID = $VehicleID;
	}

	public function GetStudentID()
	{
		return $this->StudentID;
	}
	public function SetStudentID($StudentID)
	{
		$this->StudentID = $StudentID;
	}

	public function GetClassID()
	{
		return $this->ClassID;
	}
	public function SetClassID($ClassID)
	{
		$this->ClassID = $ClassID;
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
            $this->RemoveStudentVehicle();
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
           $RSStudentVehicleCount = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM atm_routes WHERE routeID = :|1;');
            $RSStudentVehicleCount->Execute($this->RouteID);

            if ($RSStudentVehicleCount->FetchRow()->totalRecords > 0) 
            {
                return true;
            }

            return false;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at StudentVehicle::CheckDependencies(). Stack Trace: ' . $e->getTraceAsString());
            return true;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: ApplicationDBException at StudentVehicle::CheckDependencies(). Stack Trace: ' . $e->getTraceAsString());
            return true;
        }       
    }

    public function RecordExists()
	{
		try
		{
			$QueryString = '';
			
			if ($this->StudentVehicleID > 0)
			{
				$QueryString = ' AND studentVehicleID != ' . $this->DBObject->RealEscapeVariable($this->StudentVehicleID);
			}

			$RSTotal = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM atm_student_vehicle WHERE studentID = :|1  AND academicYearID = :|2 ' . $QueryString . ';');
			$RSTotal->Execute($this->StudentID, $this->AcademicYearID);
			
			if ($RSTotal->FetchRow()->totalRecords > 0)
			{
				return true;
			}
			
			return false;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at StudentVehicle::RecordExists(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at StudentVehicle::RecordExists(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
	}
    
    // public function CheckDependencies()
    // {
    //     try
    //     {
    //        $RSStudentVehicleCount = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM atm_area WHERE areaID = :|1;');
    //         $RSStudentVehicleCount->Execute($this->AreaID);

    //         if ($RSStudentVehicleCount->FetchRow()->totalRecords > 0) 
    //         {
    //             return true;
    //         }

    //         return false;
    //     }
    //     catch (ApplicationDBException $e)
    //     {
    //         error_log('DEBUG: ApplicationDBException at VehicleDrivers::CheckDependencies(). Stack Trace: ' . $e->getTraceAsString());
    //         return true;
    //     }
    //     catch (Exception $e)
    //     {
    //         error_log('DEBUG: ApplicationDBException at VehicleDrivers::CheckDependencies(). Stack Trace: ' . $e->getTraceAsString());
    //         return true;
    //     }       
    // }

	// END OF PUBLIC METHODS	//
	
	// START OF STATIC METHODS	//
	static function GetActiveStudentVehicle()
	 {
		$ActiveStudentVehicle = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT * FROM atm_student_vehicle WHERE isActive = 1;');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $ActiveStudentVehicle;
			}	

			while($SearchRow = $RSSearch->FetchRow())
			{
				$ActiveStudentVehicle[$SearchRow->studentVehicleID] = $SearchRow->studentVehicleID;
			}
			
			return $ActiveStudentVehicle;	
		}	
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at StudentVehicle::GetActiveStudentVehicle(). Stack Trace: ' . $e->getTraceAsString());
			return $ActiveStudentVehicle;
		}
		catch (Exception $e) 
		{
			error_log('DEBUG: Exception at StudentVehicle::GetActiveStudentVehicle(). Stack Trace: ' . $e->getTraceAsString());
			return $ActiveStudentVehicle;
		}		
	}
	
	static function SearchStudentVehicles(&$TotalRecords = 0, $GetTotalsOnly = false, $Filters = array(), $Start = 0, $Limit = 100)
	{
		$AllStudentVehicles = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$Conditions = array();
			
			if (count($Filters) > 0)
			{				
               
                if (!empty($Filters['RouteID']))
				{
					$Conditions[] = 'ar.RouteID = '.$DBConnObject->RealEscapeVariable($Filters['RouteID']);
				}
				if (!empty($Filters['AreaWiseFeeID']))
				{
					$Conditions[] = 'asv.AreaWiseFeeID = '.$DBConnObject->RealEscapeVariable($Filters['AreaWiseFeeID']);
				}
				if (!empty($Filters['ClassID']))
				{
					$Conditions[] = 'acl.classID = '.$DBConnObject->RealEscapeVariable($Filters['ClassID']);
				}
				if (!empty($Filters['AcademicYearID']))
				{
					$Conditions[] = 'asv.academicYearID = '.$DBConnObject->RealEscapeVariable($Filters['AcademicYearID']);
				}
				if (!empty($Filters['ClassSectionID']))
				{
					$Conditions[] = 'ass.classSectionID = '.$DBConnObject->RealEscapeVariable($Filters['ClassSectionID']);
				}
				if (!empty($Filters['StudentID']))
				{
					$Conditions[] = 'asv.studentID = '.$DBConnObject->RealEscapeVariable($Filters['StudentID']);
				}
				
				if (!empty($Filters['ActiveStatus']))
				{
					if ($Filters['ActiveStatus'] == 1) //active records
					{
						$Conditions[] = 'asv.isActive = 1';
					}
					else if ($Filters['ActiveStatus'] == 2) //non active records
					{
						$Conditions[] = 'asv.isActive = 0';
					}
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
				$RSTotal = $DBConnObject->Prepare('SELECT COUNT(*) AS totalRecords 
													FROM atm_student_vehicle asv
													INNER JOIN atm_area_wise_fee aawf ON aawf.areaWiseFeeID = asv.areaWiseFeeID
													INNER JOIN atm_area_master aam ON aawf.areaID = aam.areaID
													INNER JOIN atm_routes ar ON ar.routeID = aawf.routeID
													INNER JOIN asa_students ass ON ass.studentID = asv.studentID 
													INNER JOIN asa_student_details asd ON asd.studentID = asv.studentID 
													INNER JOIN asa_class_sections acs ON acs.classSectionID = ass.classSectionID 
													INNER JOIN asa_classes acl ON acl.classID = acs.classID 
													INNER JOIN asa_section_master asm ON asm.sectionMasterID = acs.sectionMasterID 
													INNER JOIN users u ON asv.createUserID = u.userID 
													'. $QueryString .';');
				$RSTotal->Execute();
				
				$TotalRecords = $RSTotal->FetchRow()->totalRecords;
				return;
			}
			
			$RSSearch = $DBConnObject->Prepare('SELECT asv.*, ar.routeNumber, ar.routeName, aam.areaName, asd.firstName, asd.lastName, asd.dob, asd.address1, asd.address2, acl.className, asm.sectionName, apd.fatherFirstName, apd.fatherLastName, apd.fatherMobileNumber, 
			                                    apd.motherMobileNumber, aawf.amount, u.userName AS createUserName 
												FROM atm_student_vehicle asv
												INNER JOIN atm_area_wise_fee aawf ON aawf.areaWiseFeeID = asv.areaWiseFeeID
												INNER JOIN atm_area_master aam ON aawf.areaID = aam.areaID
												INNER JOIN atm_routes ar ON ar.routeID = aawf.routeID
												INNER JOIN asa_students ass ON ass.studentID = asv.studentID 
												INNER JOIN asa_student_details asd ON asd.studentID = asv.studentID 
												INNER JOIN asa_parent_details apd ON apd.parentID = ass.parentID
												INNER JOIN asa_class_sections acs ON acs.classSectionID = ass.classSectionID 
												INNER JOIN asa_classes acl ON acl.classID = acs.classID 
												INNER JOIN asa_section_master asm ON asm.sectionMasterID = acs.sectionMasterID 
												INNER JOIN users u ON asv.createUserID = u.userID 
												'. $QueryString .' 
												ORDER BY asv.studentVehicleID LIMIT ' . (int) $Start . ', ' . (int) $Limit . ';');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllStudentVehicles; 
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$AllStudentVehicles[$SearchRow->studentVehicleID]['RouteNumber'] = $SearchRow->routeNumber;  
                $AllStudentVehicles[$SearchRow->studentVehicleID]['RouteName'] = $SearchRow->routeName;  
                $AllStudentVehicles[$SearchRow->studentVehicleID]['AreaName'] = $SearchRow->areaName; 
                $AllStudentVehicles[$SearchRow->studentVehicleID]['Amount'] = $SearchRow->amount; 
				$AllStudentVehicles[$SearchRow->studentVehicleID]['StudentName'] = $SearchRow->firstName . ' ' . $SearchRow->lastName;
				$AllStudentVehicles[$SearchRow->studentVehicleID]['Class'] = $SearchRow->className .' ( '. $SearchRow->sectionName .' )';
				$AllStudentVehicles[$SearchRow->studentVehicleID]['Section'] = $SearchRow->sectionName;
				$AllStudentVehicles[$SearchRow->studentVehicleID]['Dob'] = $SearchRow->dob;
				$AllStudentVehicles[$SearchRow->studentVehicleID]['Address1'] = $SearchRow->address1;
				$AllStudentVehicles[$SearchRow->studentVehicleID]['Address2'] = $SearchRow->address2;
				$AllStudentVehicles[$SearchRow->studentVehicleID]['FatherFirstName'] = $SearchRow->fatherFirstName;
				$AllStudentVehicles[$SearchRow->studentVehicleID]['FatherLastName'] = $SearchRow->fatherLastName;
				$AllStudentVehicles[$SearchRow->studentVehicleID]['FatherMobileNumber'] = $SearchRow->fatherMobileNumber;
				$AllStudentVehicles[$SearchRow->studentVehicleID]['MotherMobileNumber'] = $SearchRow->motherMobileNumber;
				$AllStudentVehicles[$SearchRow->studentVehicleID]['IsActive'] = $SearchRow->isActive;
				$AllStudentVehicles[$SearchRow->studentVehicleID]['CreateUserID'] = $SearchRow->createUserID;
				$AllStudentVehicles[$SearchRow->studentVehicleID]['CreateUserName'] = $SearchRow->createUserName;
				$AllStudentVehicles[$SearchRow->studentVehicleID]['CreateDate'] = $SearchRow->createDate;
			}
			return $AllStudentVehicles;	
		}	
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at StudentVehicle::SearchStudentVehicles(). Stack Trace: ' . $e->getTraceAsString());
			return $AllStudentVehicles;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at StudentVehicle::SearchStudentVehicles(). Stack Trace: ' . $e->getTraceAsString());
			return $AllStudentVehicles;
		}
	}

	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//
	private function SaveDetails()
	{
		if ($this->StudentVehicleID == 0)
		{
			$RSSave = $this->DBObject->Prepare('INSERT INTO atm_student_vehicle (areaWiseFeeID, vehicleID, StudentID, academicYearID, isActive, createUserID, createDate)
												VALUES (:|1, :|2, :|3, :|4, :|5, :|6, Now());');
		
			$RSSave->Execute($this->AreaWiseFeeID, $this->VehicleID, $this->StudentID, $this->AcademicYearID, $this->IsActive, $this->CreateUserID);
			
			$this->StudentVehicleID = $RSSave->LastID;

			$RSSearchFeeHeadID = $this->DBObject->Prepare('SELECT feeHeadID FROM afm_fee_heads WHERE feeHead = :|1;');
			$RSSearchFeeHeadID->Execute('Transport');
			
			if ($RSSearchFeeHeadID->Result->num_rows > 0)
			{
				$RSSearchFeeGroupID = $this->DBObject->Prepare('SELECT feeGroupID FROM afm_fee_group_assigned_records WHERE recordID = :|1;');
				$RSSearchFeeGroupID->Execute($this->StudentID);

				$RSSearchFeeStructureID = $this->DBObject->Prepare('SELECT feeStructureID FROM afm_fee_structure 
																	WHERE classID = :|1 AND feeGroupID = :|2 AND academicYearID = :|3;');
				$RSSearchFeeStructureID->Execute($this->ClassID, $RSSearchFeeGroupID->FetchRow()->feeGroupID, $this->AcademicYearID);

				$RSSearchAreaAmount = $this->DBObject->Prepare('SELECT amount FROM atm_area_wise_fee  
																WHERE areaWiseFeeID = :|1;');
				$RSSearchAreaAmount->Execute($this->AreaWiseFeeID);

				$AreaAmount = $RSSearchAreaAmount->FetchRow()->amount;

				$RSSearchFeeStructureDetails = $this->DBObject->Prepare('SELECT feeStructureDetailID FROM afm_fee_structure_details WHERE feeStructureID = :|1 AND feeHeadID = :|2;');
				$RSSearchFeeStructureDetails->Execute($RSSearchFeeStructureID->FetchRow()->feeStructureID, $RSSearchFeeHeadID->FetchRow()->feeHeadID);

				if ($RSSearchFeeStructureDetails->Result->num_rows > 0)
				{
					while($SearchFeeStructureDetailsRow = $RSSearchFeeStructureDetails->FetchRow())
					{
						$RSSaveTransportFee = $this->DBObject->Prepare('INSERT INTO afm_student_fee_structure (feeStructureDetailID, StudentID, amountPayable)
																		VALUES (:|1, :|2, :|3);');
					
						$RSSaveTransportFee->Execute($SearchFeeStructureDetailsRow->feeStructureDetailID, $this->StudentID, $AreaAmount);
					}
				}
			}
		}
		else
		{
			$RSUpdate = $this->DBObject->Prepare('UPDATE atm_student_vehicle
													SET	areaWiseFeeID = :|1,
												        vehicleID = :|2,
												        studentID = :|3,
												        academicYearID = :|4,
												        isActive = :|5
													WHERE studentVehicleID = :|6;');
													
			$RSUpdate->Execute($this->AreaWiseFeeID, $this->VehicleID, $this->StudentID, $this->AcademicYearID, $this->IsActive, $this->StudentVehicleID);

			$RSSearchFeeHeadID = $this->DBObject->Prepare('SELECT feeHeadID FROM afm_fee_heads WHERE feeHead = :|1;');
			$RSSearchFeeHeadID->Execute('Transport');
			
			if ($RSSearchFeeHeadID->Result->num_rows > 0)
			{
				$RSSearchFeeStructureDetails = $this->DBObject->Prepare('SELECT feeStructureDetailID FROM afm_fee_structure_details WHERE feeHeadID = :|1;');
				$RSSearchFeeStructureDetails->Execute($RSSearchFeeHeadID->FetchRow()->feeHeadID);

				$RSSearchAreaAmount = $this->DBObject->Prepare('SELECT amount FROM atm_area_wise_fee  
																WHERE areaWiseFeeID = :|1;');
				$RSSearchAreaAmount->Execute($this->AreaWiseFeeID);

				$AreaAmount = $RSSearchAreaAmount->FetchRow()->amount;

				if ($RSSearchFeeStructureDetails->Result->num_rows > 0)
				{
					while($SearchFeeStructureDetailsRow = $RSSearchFeeStructureDetails->FetchRow())
					{
						$RSUpdate = $this->DBObject->Prepare('UPDATE afm_student_fee_structure
																SET	amountPayable = :|1
																WHERE feeStructureDetailID = :|2 AND studentID = :|3 AND studentFeeStructureID NOT IN (SELECT studentFeeStructureID FROM afm_fee_collection_details);');
																
						$RSUpdate->Execute($AreaAmount, $SearchFeeStructureDetailsRow->feeStructureDetailID, $this->StudentID);
					}
				}
			}
		}
		
		return true;
	}

	private function RemoveStudentVehicle()
    {
        if(!isset($this->StudentVehicleID)) 
        {
            throw new ApplicationARException('', APP_AR_ERROR_DELETE_WITHOUT_ID);
        }
        
        $RSDeleteStudentVehicle = $this->DBObject->Prepare('DELETE FROM atm_student_vehicle WHERE studentVehicleID = :|1 LIMIT 1;');
        $RSDeleteStudentVehicle->Execute($this->StudentVehicleID); 

        $RSDeleteStudentFeeStructure = $this->DBObject->Prepare('DELETE sfs.* FROM afm_student_fee_structure sfs
        														INNER JOIN afm_fee_structure_details fsd ON fsd.feeStructureDetailID = sfs.feeStructureDetailID
        														WHERE sfs.studentID = :|1 AND sfs.studentFeeStructureID NOT IN (SELECT studentFeeStructureID FROM afm_fee_collection_details) AND fsd.feeHeadID IN (SELECT feeHeadID FROM afm_fee_heads WHERE feeHead = :|2);');
		$RSDeleteStudentFeeStructure->Execute($this->StudentID, 'Transport');

        return true;               
    }
	
	private function GetStudentVehicleByID()
	{
		$RSStudentVehicle = $this->DBObject->Prepare('SELECT * FROM atm_student_vehicle WHERE studentVehicleID = :|1;');
		$RSStudentVehicle->Execute($this->StudentVehicleID);
		
		$StudentVehicleRow = $RSStudentVehicle->FetchRow();
		
		$this->SetAttributesFromDB($StudentVehicleRow);				
	}
	
	private function SetAttributesFromDB($StudentVehicleRow)
	{
		$this->StudentVehicleID = $StudentVehicleRow->studentVehicleID;
		$this->AreaWiseFeeID = $StudentVehicleRow->areaWiseFeeID;
		$this->VehicleID = $StudentVehicleRow->vehicleID;
		$this->StudentID = $StudentVehicleRow->studentID;
		$this->AcademicYearID = $StudentVehicleRow->academicYearID;

		$this->IsActive = $StudentVehicleRow->isActive;
		$this->CreateUserID = $StudentVehicleRow->createUserID;
		$this->CreateDate = $StudentVehicleRow->createDate;
	}	
}
?>
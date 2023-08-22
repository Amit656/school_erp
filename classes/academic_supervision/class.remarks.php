<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.db_connect.php');
error_reporting(E_ALL);

class Remark
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	
	private $RemarkID;
	private $StudentID;

	private $RemarkType;
	private $Remark;

	private $IsActive;
	private $CreateUserID;
	private $CreateDate;
	
	// PUBLIC METHODS START HERE	//
	public function __construct($RemarkID = 0)
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;
		
		if($RemarkID != 0)
		{
			$this->RemarkID = $RemarkID;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetRemarkByID();
		}
		else
		{
			//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
			$this->RemarkID = 0;
			$this->StudentID = 0;

			$this->RemarkType = '';
			$this->Remark = '';

			$this->IsActive = 0;
			$this->CreateUserID = 0;
			$this->CreateDate = '0000-00-00 00:00:00';
		}
	}
	
	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetRemarkID()
	{
		return $this->RemarkID;
	}
	
	public function GetStudentID()
	{
		return $this->StudentID;
	}
	public function SetStudentID($StudentID)
	{
		$this->StudentID = $StudentID;
	}

	public function GetRemarkType()
	{
		return $this->RemarkType;
	}
	public function SetRemarkType($RemarkType)
	{
		$this->RemarkType = $RemarkType;
	}

	public function GetRemark()
	{
		return $this->Remark;
	}
	public function SetRemark($Remark)
	{
		$this->Remark = $Remark;
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
            $this->RemoveRemark();
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
            /*$RSRemarkCount = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM ahm_rooms WHERE remarkID = :|1;');
            $RSRemarkCount->Execute($this->RemarkID);

            if ($RSRemarkCount->FetchRow()->totalRecords > 0) 
            {
                return true;
            }*/

            return false;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at Remark::CheckDependencies(). Stack Trace: ' . $e->getTraceAsString());
            return true;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: ApplicationDBException at Remark::CheckDependencies(). Stack Trace: ' . $e->getTraceAsString());
            return true;
        }       
    }
		
	// END OF PUBLIC METHODS	//
	
	// START OF STATIC METHODS	//
	
	static function SearchRemarks(&$TotalRecords = 0, $GetTotalsOnly = false, $Filters = array(), $Start = 0, $Limit = 100)
	{
		$AllRemarks = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$Conditions = array();
			
			if (count($Filters) > 0)
			{				
				if (!empty($Filters['ClassID']))
				{
					$Conditions[] = 'acl.classID = '.$DBConnObject->RealEscapeVariable($Filters['ClassID']);
				}
				if (!empty($Filters['ClassSectionID']))
				{
					$Conditions[] = 'ass.classSectionID = '.$DBConnObject->RealEscapeVariable($Filters['ClassSectionID']);
				}
				if (!empty($Filters['StudentID']))
				{
					$Conditions[] = 'ar.studentID = '.$DBConnObject->RealEscapeVariable($Filters['StudentID']);
				}
				if (!empty($Filters['RemarkType']))
				{
					$Conditions[] = 'ar.remarkType = '.$DBConnObject->RealEscapeVariable($Filters['RemarkType']);
				}
				
				if (!empty($Filters['ActiveStatus']))
				{
					if ($Filters['ActiveStatus'] == 1) //active records
					{
						$Conditions[] = 'ar.isActive = 1';
					}
					else if ($Filters['ActiveStatus'] == 2) //non active records
					{
						$Conditions[] = 'ar.isActive = 0';
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
													FROM aas_remarks ar
													INNER JOIN asa_students ass ON ass.studentID = ar.studentID 
													INNER JOIN asa_student_details asd ON asd.studentID = ar.studentID 
													INNER JOIN asa_class_sections acs ON acs.classSectionID = ass.classSectionID 
													INNER JOIN asa_classes acl ON acl.classID = acs.classID 
													INNER JOIN asa_section_master asm ON asm.sectionMasterID = acs.sectionMasterID 
													INNER JOIN users u ON ar.createUserID = u.userID 
													'. $QueryString .';');
				$RSTotal->Execute();
				
				$TotalRecords = $RSTotal->FetchRow()->totalRecords;
				return;
			}
			
			$RSSearch = $DBConnObject->Prepare('SELECT ar.*, asd.firstName, asd.lastName, acl.className, asm.sectionName, u.userName AS createUserName 
												FROM aas_remarks ar
												INNER JOIN asa_students ass ON ass.studentID = ar.studentID 
												INNER JOIN asa_student_details asd ON asd.studentID = ar.studentID 
												INNER JOIN asa_class_sections acs ON acs.classSectionID = ass.classSectionID 
												INNER JOIN asa_classes acl ON acl.classID = acs.classID 
												INNER JOIN asa_section_master asm ON asm.sectionMasterID = acs.sectionMasterID 
												INNER JOIN users u ON ar.createUserID = u.userID 
												'. $QueryString .' 
												ORDER BY ar.remarkID LIMIT ' . (int) $Start . ', ' . (int) $Limit . ';');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllRemarks; 
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$AllRemarks[$SearchRow->remarkID]['StudentName'] = $SearchRow->firstName . ' ' . $SearchRow->lastName . ' ( ' . $SearchRow->className . ' ' . $SearchRow->sectionName . ' )';
				$AllRemarks[$SearchRow->remarkID]['RemarkType'] = $SearchRow->remarkType;
				$AllRemarks[$SearchRow->remarkID]['Remark'] = $SearchRow->remark;

				$AllRemarks[$SearchRow->remarkID]['IsActive'] = $SearchRow->isActive;
				$AllRemarks[$SearchRow->remarkID]['CreateUserID'] = $SearchRow->createUserID;
				$AllRemarks[$SearchRow->remarkID]['CreateUserName'] = $SearchRow->createUserName;
				$AllRemarks[$SearchRow->remarkID]['CreateDate'] = $SearchRow->createDate;
			}
			
			return $AllRemarks;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at Remark::SearchRemarks(). Stack Trace: ' . $e->getTraceAsString());
			return $AllRemarks;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at Remark::SearchRemarks(). Stack Trace: ' . $e->getTraceAsString());
			return $AllRemarks;
		}
	}
	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//
	private function SaveDetails()
	{
		if ($this->RemarkID == 0)
		{
			$RSSave = $this->DBObject->Prepare('INSERT INTO aas_remarks (studentID, remarkType, remark, isActive, createUserID, createDate)
														VALUES (:|1, :|2, :|3, :|4, :|5, NOW());');
		
			$RSSave->Execute($this->StudentID, $this->RemarkType, $this->Remark, $this->IsActive, $this->CreateUserID);
			
			$this->RemarkID = $RSSave->LastID;
		}
		else
		{
			$RSUpdate = $this->DBObject->Prepare('UPDATE aas_remarks
													SET	studentID = :|1,
														remarkType = :|2,
														remark = :|3,
														isActive = :|4
													WHERE remarkID = :|5;');
													
			$RSUpdate->Execute($this->StudentID, $this->RemarkType, $this->Remark, $this->IsActive, $this->RemarkID);
		}
		
		return true;
	}

	private function RemoveRemark()
    {
        if(!isset($this->RemarkID)) 
        {
            throw new ApplicationARException('', APP_AR_ERROR_DELETE_WITHOUT_ID);
        }
        
        $RSDeleteRemark = $this->DBObject->Prepare('DELETE FROM aas_remarks WHERE remarkID = :|1 LIMIT 1;');
        $RSDeleteRemark->Execute($this->RemarkID);  

        return true;              
    }
	
	private function GetRemarkByID()
	{
		$RSRemark = $this->DBObject->Prepare('SELECT * FROM aas_remarks WHERE remarkID = :|1 LIMIT 1;');
		$RSRemark->Execute($this->RemarkID);
		
		$RemarkRow = $RSRemark->FetchRow();
		
		$this->SetAttributesFromDB($RemarkRow);				
	}
	
	private function SetAttributesFromDB($RemarkRow)
	{
		$this->RemarkID = $RemarkRow->remarkID;
		$this->StudentID = $RemarkRow->studentID;

		$this->RemarkType = $RemarkRow->remarkType;
		$this->Remark = $RemarkRow->remark;

		$this->IsActive = $RemarkRow->isActive;
		$this->CreateUserID = $RemarkRow->createUserID;
		$this->CreateDate = $RemarkRow->createDate;
	}	
}
?>
<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.db_connect.php');
error_reporting(E_ALL);

class ClassClassteacher
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	
	private $ClassClassTeacherID;
	private $ClassSectionID;
	private $BranchStaffID;

	private $CreateUserID;
	private $CreateDate;

	private $ClassID;
	
	// PUBLIC METHODS START HERE	//
	public function __construct($ClassClassTeacherID = 0)
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;
		
		if($ClassClassTeacherID != 0)
		{
			$this->ClassClassTeacherID = $ClassClassTeacherID;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetClassClassteacherByID();
		}
		else
		{
			//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
			$this->ClassClassTeacherID = 0;
			$this->ClassSectionID = '';
			$this->BranchStaffID = 0;
			$this->ClassID = 0;

			$this->CreateUserID = 0;
			$this->CreateDate = '0000-00-00 00:00:00';
		}
	}
	
	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetClassClassTeacherID()
	{
		return $this->ClassClassTeacherID;
	}
	
	public function GetClassSectionID()
	{
		return $this->ClassSectionID;
	}
	public function SetClassSectionID($ClassSectionID)
	{
		$this->ClassSectionID = $ClassSectionID;
	}

	public function GetBranchStaffID()
	{
		return $this->BranchStaffID;
	}
	public function SetBranchStaffID($BranchStaffID)
	{
		$this->BranchStaffID = $BranchStaffID;
	}

	public function GetClassID()
	{
		return $this->ClassID;
	}
	public function SetClassID($ClassID)
	{
		$this->ClassID = $ClassID;
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
			$this->RemoveClassClassteacher();
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

	public function RecordExists()
	{
		try
		{
			$QueryString = '';
			
			if ($this->ClassClassTeacherID > 0)
			{
				$QueryString = ' AND classClassTeacherID != ' . $this->DBObject->RealEscapeVariable($this->ClassClassTeacherID);
			}

			$RSTotal = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM asa_class_classteachers WHERE classSectionID = :|1 || branchStaffID = :|2' . $QueryString . ';');
			$RSTotal->Execute($this->ClassSectionID, $this->BranchStaffID);
			
			if ($RSTotal->FetchRow()->totalRecords > 0)
			{
				return true;
			}
			
			return false;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at ClassClassteacher::RecordExists(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at ClassClassteacher::RecordExists(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
	}

	// END OF PUBLIC METHODS	//
	
	// START OF STATIC METHODS	//
	static function GetAllClassClassteachers()
    { 
		$AllClassClassteachers = array();
		
    	try
        {	
        	$DBConnObject = new DBConnect();

        	$RSSearch = $DBConnObject->Prepare('SELECT acc.*, ac.className, asm.sectionName, abs.firstName, abs.lastName, u.userName AS createUserName 
												FROM asa_class_classteachers acc
												INNER JOIN asa_branch_staff abs ON acc.branchStaffID = abs.branchStaffID
												INNER JOIN asa_class_sections acs ON acc.classSectionID = acs.classSectionID
												INNER JOIN asa_classes ac ON acs.classID = ac.classID
												INNER JOIN asa_section_master asm ON acs.sectionMasterID = asm.sectionMasterID
												INNER JOIN users u ON abs.createUserID = u.userID
												ORDER BY ac.classID, acs.classSectionID
												;');
			$RSSearch->Execute();

			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllClassClassteachers;
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$AllClassClassteachers[$SearchRow->classClassTeacherID]['ClassName'] = $SearchRow->className;
				$AllClassClassteachers[$SearchRow->classClassTeacherID]['SectionName'] = $SearchRow->sectionName;

				$AllClassClassteachers[$SearchRow->classClassTeacherID]['FirstName'] = $SearchRow->firstName;
				$AllClassClassteachers[$SearchRow->classClassTeacherID]['LastName'] = $SearchRow->lastName;
				
				$AllClassClassteachers[$SearchRow->classClassTeacherID]['CreateUserName'] = $SearchRow->createUserName;
				$AllClassClassteachers[$SearchRow->classClassTeacherID]['CreateDate'] = $SearchRow->createDate;
			}
            
            return $AllClassClassteachers;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException::ClassClassteacher::GetAllClassClassteachers(). Stack Trace: '. $e->getTraceAsString());
            return $AllClassClassteachers;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception: ClassClassteacher::GetAllClassClassteachers() . Stack Trace: '. $e->getTraceAsString());
            return $AllClassClassteachers;
        }
    }
    
	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//
	private function SaveDetails()
	{
		if ($this->ClassClassTeacherID == 0)
		{
			$RSSave = $this->DBObject->Prepare('INSERT INTO asa_class_classteachers (classSectionID, branchStaffID, createUserID, createDate)
														VALUES (:|1, :|2, :|3, NOW());');
			$RSSave->Execute($this->ClassSectionID, $this->BranchStaffID, $this->CreateUserID);

			$this->ClassClassTeacherID = $RSSave->LastID;
		}
		else
		{
			$RSUpdate = $this->DBObject->Prepare('UPDATE asa_class_classteachers
													SET	classSectionID = :|1,
														branchStaffID = :|2
													WHERE classClassTeacherID = :|3 LIMIT 1;');
			$RSUpdate->Execute($this->ClassSectionID,  $this->BranchStaffID, $this->ClassClassTeacherID);
		}
		
		return true;
	}

	private function RemoveClassClassteacher()
	{
		if (!isset($this->ClassClassTeacherID)) 
		{
    		throw new ApplicationARException('', APP_AR_ERROR_DELETE_WITHOUT_ID);
		}
		
		$RSDeleteClassClassteacher = $this->DBObject->Prepare('DELETE FROM asa_class_classteachers WHERE classClassTeacherID = :|1 LIMIT 1;');
		$RSDeleteClassClassteacher->Execute($this->ClassClassTeacherID);				
	}
	
	private function GetClassClassteacherByID()
	{
		$RSClassClassteacher = $this->DBObject->Prepare('SELECT * FROM asa_class_classteachers WHERE classClassTeacherID = :|1 LIMIT 1;');
		$RSClassClassteacher->Execute($this->ClassClassTeacherID);
		
		$ClassClassteacherRow = $RSClassClassteacher->FetchRow();
		
		$this->SetAttributesFromDB($ClassClassteacherRow);				
	}
	
	private function SetAttributesFromDB($ClassClassteacherRow)
	{
		$this->ClassClassTeacherID = $ClassClassteacherRow->classClassTeacherID;
		$this->ClassSectionID = $ClassClassteacherRow->classSectionID;
		$this->BranchStaffID = $ClassClassteacherRow->branchStaffID;

		$this->CreateUserID = $ClassClassteacherRow->createUserID;
		$this->CreateDate = $ClassClassteacherRow->createDate;

		$RSSearch = $this->DBObject->Prepare('SELECT acs.classID FROM asa_class_classteachers acc
												INNER JOIN asa_class_sections acs ON acc.classSectionID = acs.classSectionID
												WHERE acc.classClassTeacherID = :|1 LIMIT 1;');
		$RSSearch->Execute($this->ClassClassTeacherID);

		if ($RSSearch->Result->num_rows > 0) 
		{	
			$SearchRow = $RSSearch->FetchRow();
			$this->ClassID = $SearchRow->classID;
		}
	}	
}
?>

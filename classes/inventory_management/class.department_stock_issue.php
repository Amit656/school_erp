<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.db_connect.php');
error_reporting(E_ALL);

class DepartmentStockIssue
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	
	private $DepartmentStockIssueID;
	private $DepartmentID;
	private $ProductID;

	private $IssuedToType;
	private $IssuedToID;
	private $IssuedQuantity;

	private $IssueDate;
	private $ReturnDate;

	private $CreateUserID;
	private $CreateDate;
	
	// PUBLIC METHODS START HERE	//
	public function __construct($DepartmentStockIssueID = 0)
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;
		
		if($DepartmentStockIssueID != 0)
		{
			$this->DepartmentStockIssueID = $DepartmentStockIssueID;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetDepartmentStockIssueByID();
		}
		else
		{
			//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
			$this->DepartmentStockIssueID = 0;
			$this->DepartmentID = 0;
			$this->ProductID = 0;

			$this->IssuedToType = 'Student';
			$this->IssuedToID = 0;
			$this->IssuedQuantity = 0;
			
			$this->IssueDate = '0000-00-00';
			$this->ReturnDate = '0000-00-00';

			$this->CreateUserID = 0;
			$this->CreateDate = '0000-00-00 00:00:00';
		}
	}
	
	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetDepartmentStockIssueID()
	{
		return $this->DepartmentStockIssueID;
	}
	
	public function GetDepartmentID()
	{
		return $this->DepartmentID;
	}
	public function SetDepartmentID($DepartmentID)
	{
		$this->DepartmentID = $DepartmentID;
	}

	public function GetProductID()
	{
		return $this->ProductID;
	}
	public function SetProductID($ProductID)
	{
		$this->ProductID = $ProductID;
	}
	
	public function GetIssuedToType()
	{
		return $this->IssuedToType;
	}
	public function SetIssuedToType($IssuedToType)
	{
		$this->IssuedToType = $IssuedToType;
	}
	
	public function GetIssuedToID()
	{
		return $this->IssuedToID;
	}
	public function SetIssuedToID($IssuedToID)
	{
		$this->IssuedToID = $IssuedToID;
	}
	
	public function GetIssuedQuantity()
	{
		return $this->IssuedQuantity;
	}
	public function SetIssuedQuantity($IssuedQuantity)
	{
		$this->IssuedQuantity = $IssuedQuantity;
	}
	
	public function GetIssueDate()
	{
		return $this->IssueDate;
	}
	public function SetIssueDate($IssueDate)
	{
		$this->IssueDate = $IssueDate;
	}
	
	public function GetReturnDate()
	{
		return $this->ReturnDate;
	}
	public function SetReturnDate($ReturnDate)
	{
		$this->ReturnDate = $ReturnDate;
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
			$this->RemoveDepartmentStockIssue();
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
			$RSTotal = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM role_group_roles WHERE departmentStockIssueID = :|1;');
			$RSTotal->Execute($this->DepartmentStockIssueID);
	
			if ($RSTotal->FetchRow()->totalRecords > 0) 
			{
				return true;
			}
			
			return false;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException: DepartmentStockIssue::CheckDependencies(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception: DepartmentStockIssue::CheckDependencies(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
	}

	public function RecordExists()
	{
		try
		{
			$QueryString = '';
			
			if ($this->DepartmentStockIssueID > 0)
			{
				$QueryString = ' AND departmentStockIssueID != ' . $this->DBObject->RealEscapeVariable($this->DepartmentStockIssueID);
			}

			$RSTotal = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM aim_department_stock_issue WHERE departmentID = :|1' . $QueryString . ';');
			$RSTotal->Execute($this->DepartmentID);
			
			if ($RSTotal->FetchRow()->totalRecords > 0)
			{
				return true;
			}
			
			return false;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at DepartmentStockIssue::RecordExists(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at DepartmentStockIssue::RecordExists(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
	}

	// END OF PUBLIC METHODS	//
	
	// START OF STATIC METHODS	//
	static function GetAllDepartmentStockIssues()
    { 
		$AllDepartmentStockIssues = array();
		
    	try
        {	
        	$DBConnObject = new DBConnect();
        	$RSSearch = $DBConnObject->Prepare('SELECT adsi.*, ad.departmentName, ap.productName, apc.productCategoryName, 
        											CONCAT(abs.firstName, " ", abs.lastName) AS branchStaffName, CONCAT(astdtls.firstName, " ", astdtls.lastName) AS studentName, ac.className, asm.sectionName, 
        											u.userName AS createUserName FROM aim_department_stock_issue adsi 
													LEFT JOIN asa_branch_staff abs ON adsi.issuedToID = abs.branchStaffID 
													LEFT JOIN asa_students astd ON adsi.issuedToID = astd.studentID 
													LEFT JOIN asa_student_details astdtls ON astd.studentID = astdtls.studentID
													LEFT JOIN asa_class_sections acs ON astd.classSectionID = acs.classSectionID
													LEFT JOIN asa_classes ac ON ac.classID = acs.classID
													LEFT JOIN asa_section_master asm ON acs.sectionMasterID = asm.sectionMasterID
													INNER JOIN aim_departments ad ON adsi.departmentID = ad.departmentID 
													INNER JOIN aim_products ap ON adsi.productID = ap.productID 
													INNER JOIN aim_product_categories apc ON ap.productCategoryID = apc.productCategoryID 
													INNER JOIN users u ON adsi.createUserID = u.userID 
        											ORDER BY adsi.departmentID;');
            $RSSearch->Execute();

            if ($RSSearch->Result->num_rows <= 0)
            {
                return $AllDepartmentStockIssues;
            }

            while($SearchRow = $RSSearch->FetchRow())
            {
                $AllDepartmentStockIssues[$SearchRow->departmentStockIssueID]['DepartmentID'] = $SearchRow->departmentID;
                $AllDepartmentStockIssues[$SearchRow->departmentStockIssueID]['DepartmentName'] = $SearchRow->departmentName;

				$AllDepartmentStockIssues[$SearchRow->departmentStockIssueID]['ProductID'] = $SearchRow->productID;
				$AllDepartmentStockIssues[$SearchRow->departmentStockIssueID]['ProductName'] = $SearchRow->productName;
				$AllDepartmentStockIssues[$SearchRow->departmentStockIssueID]['ProductCategoryName'] = $SearchRow->productCategoryName;

				$AllDepartmentStockIssues[$SearchRow->departmentStockIssueID]['IssuedToType'] = $SearchRow->issuedToType;

				$AllDepartmentStockIssues[$SearchRow->departmentStockIssueID]['BranchStaffName'] = $SearchRow->branchStaffName;
				$AllDepartmentStockIssues[$SearchRow->departmentStockIssueID]['StudentName'] = $SearchRow->studentName;
				$AllDepartmentStockIssues[$SearchRow->departmentStockIssueID]['ClassName'] = $SearchRow->className;
				$AllDepartmentStockIssues[$SearchRow->departmentStockIssueID]['SectionName'] = $SearchRow->sectionName;
				
				$AllDepartmentStockIssues[$SearchRow->departmentStockIssueID]['IssuedQuantity'] = $SearchRow->issuedQuantity;
				$AllDepartmentStockIssues[$SearchRow->departmentStockIssueID]['IssueDate'] = $SearchRow->issueDate;
				$AllDepartmentStockIssues[$SearchRow->departmentStockIssueID]['ReturnDate'] = $SearchRow->returnDate;

                $AllDepartmentStockIssues[$SearchRow->departmentStockIssueID]['CreateUserID'] = $SearchRow->createUserID;
                $AllDepartmentStockIssues[$SearchRow->departmentStockIssueID]['CreateUserName'] = $SearchRow->createUserName;

                $AllDepartmentStockIssues[$SearchRow->departmentStockIssueID]['CreateDate'] = $SearchRow->createDate;
           }
            
            return $AllDepartmentStockIssues;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException::DepartmentStockIssue::GetAllDepartmentStockIssues(). Stack Trace: '. $e->getTraceAsString());
            return $AllDepartmentStockIssues;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception: DepartmentStockIssue::GetAllDepartmentStockIssues() . Stack Trace: '. $e->getTraceAsString());
            return $AllDepartmentStockIssues;
        }
    }

    static function SearchDepartmentStockIssues(&$TotalRecords = 0, $GetTotalsOnly = false, $Filters = array(), $Start = 0, $Limit = 100)
    {
    	$AllDepartmentStockIssues = array();

    	try
		{
			$DBConnObject = new DBConnect();
			
			$Conditions = array();
			
			if (count($Filters) > 0)
			{			
				if (!empty($Filters['DepartmentID']))
				{
					$Conditions[] = 'adsi.departmentID = ' . $DBConnObject->RealEscapeVariable($Filters['DepartmentID']);
				}

				if (!empty($Filters['ProductID']))
				{
					$Conditions[] = 'adsi.productID = ' . $DBConnObject->RealEscapeVariable($Filters['ProductID']);
				}

				if (!empty($Filters['IssuedToType']))
				{
					$Conditions[] = 'adsi.issuedToType = ' . $DBConnObject->RealEscapeVariable($Filters['IssuedToType']);
				}

				if (!empty($Filters['IssueDate']))
				{
					$Conditions[] = 'adsi.issueDate = ' . $DBConnObject->RealEscapeVariable($Filters['IssueDate']);
				}

				if (!empty($Filters['ReturnDate']))
				{
					$Conditions[] = 'adsi.returnDate = ' . $DBConnObject->RealEscapeVariable($Filters['ReturnDate']);
				}
			}

			$QueryString = '';

			if (count($Conditions) > 0)
			{
				$QueryString = implode(' AND ',$Conditions);
				
				$QueryString = ' WHERE ' . $QueryString;
			}
			
			if ($GetTotalsOnly)
			{
			 
				$RSTotal = $DBConnObject->Prepare('SELECT COUNT(*) AS totalRecords 
													FROM aim_department_stock_issue adsi 
													LEFT JOIN asa_branch_staff abs ON adsi.issuedToID = abs.branchStaffID 
													LEFT JOIN asa_students astd ON adsi.issuedToID = astd.studentID 
													LEFT JOIN asa_student_details astdtls ON astd.studentID = astdtls.studentID
													LEFT JOIN asa_class_sections acs ON astd.classSectionID = acs.classSectionID
													LEFT JOIN asa_classes ac ON ac.classID = acs.classID
													LEFT JOIN asa_section_master asm ON acs.sectionMasterID = asm.sectionMasterID
													INNER JOIN aim_departments ad ON adsi.departmentID = ad.departmentID 
													INNER JOIN aim_products ap ON adsi.productID = ap.productID 
													INNER JOIN aim_product_categories apc ON ap.productCategoryID = apc.productCategoryID 
													INNER JOIN users u ON adsi.createUserID = u.userID' . $QueryString . ' ORDER BY adsi.departmentID;');
				$RSTotal->Execute();
				
				$TotalRecords = $RSTotal->FetchRow()->totalRecords;
				return;
			}
			
			$RSSearch = $DBConnObject->Prepare('SELECT adsi.*, ad.departmentName, ap.productName, apc.productCategoryName, 
        											CONCAT(abs.firstName, " ", abs.lastName) AS branchStaffName, CONCAT(astdtls.firstName, " ", astdtls.lastName) AS studentName, ac.className, asm.sectionName, 
        											u.userName AS createUserName FROM aim_department_stock_issue adsi 
													LEFT JOIN asa_branch_staff abs ON adsi.issuedToID = abs.branchStaffID 
													LEFT JOIN asa_students astd ON adsi.issuedToID = astd.studentID 
													LEFT JOIN asa_student_details astdtls ON astd.studentID = astdtls.studentID
													LEFT JOIN asa_class_sections acs ON astd.classSectionID = acs.classSectionID
													LEFT JOIN asa_classes ac ON ac.classID = acs.classID
													LEFT JOIN asa_section_master asm ON acs.sectionMasterID = asm.sectionMasterID
													INNER JOIN aim_departments ad ON adsi.departmentID = ad.departmentID 
													INNER JOIN aim_products ap ON adsi.productID = ap.productID 
													INNER JOIN aim_product_categories apc ON ap.productCategoryID = apc.productCategoryID 
													INNER JOIN users u ON adsi.createUserID = u.userID' . $QueryString . ' 
													ORDER BY adsi.departmentID 
													LIMIT ' . (int) $Start . ', ' . (int) $Limit . ';');
			$RSSearch->Execute();

			while($SearchRow = $RSSearch->FetchRow())
			{
				$AllDepartmentStockIssues[$SearchRow->departmentStockIssueID]['DepartmentID'] = $SearchRow->departmentID;
                $AllDepartmentStockIssues[$SearchRow->departmentStockIssueID]['DepartmentName'] = $SearchRow->departmentName;

				$AllDepartmentStockIssues[$SearchRow->departmentStockIssueID]['ProductID'] = $SearchRow->productID;
				$AllDepartmentStockIssues[$SearchRow->departmentStockIssueID]['ProductName'] = $SearchRow->productName;
				$AllDepartmentStockIssues[$SearchRow->departmentStockIssueID]['ProductCategoryName'] = $SearchRow->productCategoryName;

				$AllDepartmentStockIssues[$SearchRow->departmentStockIssueID]['IssuedToType'] = $SearchRow->issuedToType;

				$AllDepartmentStockIssues[$SearchRow->departmentStockIssueID]['BranchStaffName'] = $SearchRow->branchStaffName;
				$AllDepartmentStockIssues[$SearchRow->departmentStockIssueID]['StudentName'] = $SearchRow->studentName;
				$AllDepartmentStockIssues[$SearchRow->departmentStockIssueID]['ClassName'] = $SearchRow->className;
				$AllDepartmentStockIssues[$SearchRow->departmentStockIssueID]['SectionName'] = $SearchRow->sectionName;
				
				$AllDepartmentStockIssues[$SearchRow->departmentStockIssueID]['IssuedQuantity'] = $SearchRow->issuedQuantity;
				$AllDepartmentStockIssues[$SearchRow->departmentStockIssueID]['IssueDate'] = $SearchRow->issueDate;
				$AllDepartmentStockIssues[$SearchRow->departmentStockIssueID]['ReturnDate'] = $SearchRow->returnDate;

                $AllDepartmentStockIssues[$SearchRow->departmentStockIssueID]['CreateUserID'] = $SearchRow->createUserID;
                $AllDepartmentStockIssues[$SearchRow->departmentStockIssueID]['CreateUserName'] = $SearchRow->createUserName;

                $AllDepartmentStockIssues[$SearchRow->departmentStockIssueID]['CreateDate'] = $SearchRow->createDate;
			}
			
			return $AllDepartmentStockIssues;	
				
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at DepartmentStockIssue::SearchDepartmentStockIssues(). Stack Trace: ' . $e->getTraceAsString());
			return $AllDepartmentStockIssues;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at DepartmentStockIssue::SearchDepartmentStockIssues(). Stack Trace: ' . $e->getTraceAsString());
			return $AllDepartmentStockIssues;
		}	
    }

    static function GetActiveDepartmentStockIssues()
	{
		$AllDepartmentStockIssues = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT * FROM aim_department_stock_issue WHERE productID = 1;');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllDepartmentStockIssues;
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$AllDepartmentStockIssues[$SearchRow->departmentStockIssueID] = $SearchRow->departmentID;
			}
			
			return $AllDepartmentStockIssues;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at DepartmentStockIssue::GetActiveDepartmentStockIssues(). Stack Trace: ' . $e->getTraceAsString());
			return $AllDepartmentStockIssues;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at DepartmentStockIssue::GetActiveDepartmentStockIssues(). Stack Trace: ' . $e->getTraceAsString());
			return $AllDepartmentStockIssues;
		}		
	}
    
	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//
	private function SaveDetails()
	{
		if ($this->DepartmentStockIssueID == 0)
		{
			$RSSave = $this->DBObject->Prepare('INSERT INTO aim_department_stock_issue (departmentID, productID, issuedToType, issuedToID, issuedQuantity,
																							 issueDate, returnDate, createUserID, createDate)
														VALUES (:|1, :|2, :|3, :|4, :|5, :|6, :|7, :|8, NOW());');
			$RSSave->Execute($this->DepartmentID, $this->ProductID,  $this->IssuedToType,  $this->IssuedToID,  $this->IssuedQuantity,  
									$this->IssueDate,  $this->ReturnDate, $this->CreateUserID);

			$this->DepartmentStockIssueID = $RSSave->LastID;
		}
		else
		{
			$RSUpdate = $this->DBObject->Prepare('UPDATE aim_department_stock_issue
													SET	departmentID = :|1,
														productID = :|2,
														issuedToType = :|3,
														issuedToID = :|4,
														issuedQuantity = :|5,
														issueDate = :|6,
														returnDate = :|7
													WHERE departmentStockIssueID = :|8 LIMIT 1;');
			$RSUpdate->Execute($this->DepartmentID, $this->ProductID, $this->IssuedToType,  $this->IssuedToID,  $this->IssuedQuantity,  
									$this->IssueDate,  $this->ReturnDate, $this->DepartmentStockIssueID);
		}
		
		return true;
	}

	private function RemoveDepartmentStockIssue()
	{
		if(!isset($this->DepartmentStockIssueID)) 
		{
    		throw new ApplicationARException('', APP_AR_ERROR_DELETE_WITHOUT_ID);
		}
		
		$RSDeleteDepartmentStockIssue = $this->DBObject->Prepare('DELETE FROM aim_department_stock_issue WHERE departmentStockIssueID = :|1 LIMIT 1;');
		$RSDeleteDepartmentStockIssue->Execute($this->DepartmentStockIssueID);				
	}
	
	private function GetDepartmentStockIssueByID()
	{
		$RSDepartmentStockIssue = $this->DBObject->Prepare('SELECT * FROM aim_department_stock_issue WHERE departmentStockIssueID = :|1 LIMIT 1;');
		$RSDepartmentStockIssue->Execute($this->DepartmentStockIssueID);
		
		$DepartmentStockIssueRow = $RSDepartmentStockIssue->FetchRow();
		
		$this->SetAttributesFromDB($DepartmentStockIssueRow);				
	}
	
	private function SetAttributesFromDB($DepartmentStockIssueRow)
	{
		$this->DepartmentStockIssueID = $DepartmentStockIssueRow->departmentStockIssueID;
		$this->DepartmentID = $DepartmentStockIssueRow->departmentID;
		$this->ProductID = $DepartmentStockIssueRow->productID;

		$this->IssuedToType = $DepartmentStockIssueRow->issuedToType;
		$this->IssuedToID = $DepartmentStockIssueRow->issuedToID;
		$this->IssuedQuantity = $DepartmentStockIssueRow->issuedQuantity;
		
		$this->IssueDate = $DepartmentStockIssueRow->issueDate;
		$this->ReturnDate = $DepartmentStockIssueRow->returnDate;

		$this->CreateUserID = $DepartmentStockIssueRow->createUserID;
		$this->CreateDate = $DepartmentStockIssueRow->createDate;
	}	
}
?>
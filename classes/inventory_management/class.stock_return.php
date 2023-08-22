<?php

require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.db_connect.php');
error_reporting(E_ALL);

class StockReturn
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	private $StockReturnID;
	private $IssueType;
	private $DepartmentID;
	private $BranchStaffID;
	private $ReturnDate;
	private $CreateUserID;
	private $CreateDate;
	private $StockReturnDetails = array();

	// PUBLIC METHODS START HERE	//
	public function __construct($StockReturnID = 0)
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;

		if ($StockReturnID != 0)
		{
			$this->StockReturnID = $StockReturnID;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetStockReturnByID();
		}
		else
		{
			//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
			$this->StockReturnID = 0;
			$this->StockIssueID = 0;

			$this->IssueType = 'Staff';
			$this->DepartmentID = 0;
			$this->BranchStaffID = 0;
			$this->ReturnDate = '0000-00-00';

			$this->CreateUserID = 0;
			$this->CreateDate = '0000-00-00 00:00:00';
			$this->StockReturnDetails = array();
		}
	}

	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetStockReturnID()
	{
		return $this->StockReturnID;
	}

	public function GetStockIssueID()
	{
		return $this->StockIssueID;
	}

	public function SetStockIssueID($StockIssueID)
	{
		$this->StockIssueID = $StockIssueID;
	}

	public function GetIssueType()
	{
		return $this->IssueType;
	}

	public function SetIssueType($IssueType)
	{
		$this->IssueType = $IssueType;
	}

	public function GetDepartmentID()
	{
		return $this->DepartmentID;
	}

	public function SetDepartmentID($DepartmentID)
	{
		$this->DepartmentID = $DepartmentID;
	}

	public function GetBranchStaffID()
	{
		return $this->BranchStaffID;
	}

	public function SetBranchStaffID($BranchStaffID)
	{
		$this->BranchStaffID = $BranchStaffID;
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

	public function GetStockReturnDetails()
	{
		return $this->StockReturnDetails;
	}

	public function SetStockReturnDetails($StockReturnDetails)
	{
		$this->StockReturnDetails = $StockReturnDetails;
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
			$this->RemoveStockIssue();
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
			$RSTotal = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM role_group_roles WHERE stockReturnID = :|1;');
			$RSTotal->Execute($this->StockReturnID);

			if ($RSTotal->FetchRow()->totalRecords > 0)
			{
				return true;
			}

			return false;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException: StockReturn::CheckDependencies(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception: StockReturn::CheckDependencies(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
	}

	/* public function RecordExists()
	  {
	  try
	  {
	  $QueryString = '';

	  if ($this->StockReturnID > 0)
	  {
	  $QueryString = ' AND stockReturnID != ' . $this->DBObject->RealEscapeVariable($this->StockReturnID);
	  }

	  $RSTotal = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM aim_stock_return WHERE productID = :|1' . $QueryString . ';');
	  $RSTotal->Execute($this->ProductID);

	  if ($RSTotal->FetchRow()->totalRecords > 0)
	  {
	  return true;
	  }

	  return false;
	  }
	  catch (ApplicationDBException $e)
	  {
	  error_log('DEBUG: ApplicationDBException at StockReturn::RecordExists(). Stack Trace: ' . $e->getTraceAsString());
	  return true;
	  }
	  catch (Exception $e)
	  {
	  error_log('DEBUG: Exception at StockReturn::RecordExists(). Stack Trace: ' . $e->getTraceAsString());
	  return true;
	  }
	  } */

	public function FillStockReturnDetails()
	{
		try
		{
			$RSSearchStockReturnDetails = $this->DBObject->Prepare('SELECT asid.*, ap.productCategoryID 
																	FROM aim_stock_return_details asid
																	INNER JOIN aim_products ap ON asid.productID = ap.productID
																	WHERE stockReturnID = :|1;');
			$RSSearchStockReturnDetails->Execute($this->StockReturnID);

			$Counter = 1;

			while ($SearchRow = $RSSearchStockReturnDetails->FetchRow())
			{
				$this->StockReturnDetails[$Counter]['StockReturnID'] = $SearchRow->stockReturnID;
				$this->StockReturnDetails[$Counter]['ProductID'] = $SearchRow->productID;
				$this->StockReturnDetails[$Counter]['ProductCategoryID'] = $SearchRow->productCategoryID;

				$this->StockReturnDetails[$Counter]['Quantity'] = $SearchRow->returnedQuantity;

				$Counter++;
			}

			return false;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException: StockReturn::FillStockReturnDetails(). Stack Trace: ' . $e->getTraceAsString());
			return false;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception: StockReturn::FillStockReturnDetails(). Stack Trace: ' . $e->getTraceAsString());
			return false;
		}
	}

	// END OF PUBLIC METHODS	//
	// START OF STATIC METHODS	//
	
	static function GetAllStockReturn(&$TotalRecords = 0, $GetTotalsOnly = false, $Filters = array(), $Start = 0, $Limit = 100)
	{
		$AllStockReturn = array();

		try
		{
			$DBConnObject = new DBConnect();

			$Conditions = array();

			if (count($Filters) > 0)
			{
				if (isset($Filters['IssueType']) && !empty($Filters['IssueType']))
				{
					$Conditions[] = 'asr.issueType = ' . $DBConnObject->RealEscapeVariable($Filters['IssueType']);
				}

				if (isset($Filters['DepartmentID']) && !empty($Filters['DepartmentID']))
				{
					$Conditions[] = 'asr.departmentID = ' . $DBConnObject->RealEscapeVariable($Filters['DepartmentID']);
				}

				if (isset($Filters['StaffCategory']) && !empty($Filters['StaffCategory']))
				{
					$Conditions[] = 'asr.branchStaffID IN (SELECT branchStaffID FROM asa_branch_staff WHERE staffCategory = ' . $DBConnObject->RealEscapeVariable($Filters['StaffCategory']) . ')';
				}

				if (isset($Filters['BranchStaffID']) && !empty($Filters['BranchStaffID']))
				{
					$Conditions[] = 'asr.branchStaffID = ' . $DBConnObject->RealEscapeVariable($Filters['BranchStaffID']);
				}

				if (isset($Filters['ProductCategoryID']) && !empty($Filters['ProductCategoryID']))
				{
					$Conditions[] = 'asr.stockReturnID IN (SELECT stockReturnID FROM aim_stock_return_details WHERE productID IN (SELECT productID FROM aim_products WHERE productCategoryID = ' . $DBConnObject->RealEscapeVariable($Filters['ProductCategoryID']) . '))';
				}

				if (isset($Filters['ProductID']) && !empty($Filters['ProductID']))
				{
					$Conditions[] = 'asr.stockReturnID IN (SELECT stockReturnID FROM aim_stock_return_details WHERE productID = ' . $DBConnObject->RealEscapeVariable($Filters['ProductID']) . ')';
				}
				
				if (isset($Filters['VoucherNumber']) && !empty($Filters['VoucherNumber']))
				{
					$Conditions[] = 'asr.voucherNumber LIKE ' . $DBConnObject->RealEscapeVariable( '%' .$Filters['VoucherNumber']. '%');
				}

				if (isset($Filters['Description']) && !empty($Filters['Description']))
				{
					$Conditions[] = 'asr.description LIKE ' . $DBConnObject->RealEscapeVariable('%' . $Filters['Description']. '%');
				}
				
				if (!empty($Filters['FromDate']) && !empty($Filters['ToDate']))
				{
					$Conditions[] = 'asr.returnDate BETWEEN ' . $DBConnObject->RealEscapeVariable($Filters['FromDate']) . ' AND ' . $DBConnObject->RealEscapeVariable($Filters['ToDate']);
				}
				
//				if (!empty($Filters['FromDate']) && !empty($Filters['ToDate']))
//				{
//					$Conditions[] = 'asr.stockReturnID IN (SELECT stockReturnID 
//															FROM aim_stock_return_details asid 
//															WHERE asid.issueDate 
//															BETWEEN ' . $DBConnObject->RealEscapeVariable($Filters['FromDate']) 
//															. ' AND ' . $DBConnObject->RealEscapeVariable($Filters['ToDate']);
//				}
			}

			$QueryString = '';

			if (count($Conditions) > 0)
			{
				$QueryString = implode(' AND ', $Conditions);

				$QueryString = ' WHERE ' . $QueryString;
			}

			if ($GetTotalsOnly)
			{

				$RSTotal = $DBConnObject->Prepare('SELECT COUNT(*) AS totalRecords 
													FROM aim_stock_return asr 
													INNER JOIN asa_branch_staff abs ON asr.branchStaffID = abs.branchStaffID
													INNER JOIN users u ON asr.createUserID = u.userID 
													' . $QueryString . ';');
				$RSTotal->Execute();

				$TotalRecords = $RSTotal->FetchRow()->totalRecords;
				return;
			}

			$RSSearch = $DBConnObject->Prepare('SELECT asr.*, CONCAT(abs.firstName, " ", abs.lastName) AS branchStaffName, abs.staffCategory, u.userName AS createUserName 
												FROM aim_stock_return asr 
												INNER JOIN asa_branch_staff abs ON asr.branchStaffID = abs.branchStaffID
												INNER JOIN users u ON asr.createUserID = u.userID 
												
												' . $QueryString . ' 
												ORDER BY asr.stockReturnID DESC
												LIMIT ' . (int) $Start . ', ' . (int) $Limit . ';');
			$RSSearch->Execute();

			while ($SearchRow = $RSSearch->FetchRow())
			{
				$AllStockReturn[$SearchRow->stockReturnID]['StaffCategory'] = $SearchRow->staffCategory;
				$AllStockReturn[$SearchRow->stockReturnID]['IssueType'] = $SearchRow->issueType;
				$AllStockReturn[$SearchRow->stockReturnID]['BranchStaffName'] = $SearchRow->branchStaffName;

				$AllStockReturn[$SearchRow->stockReturnID]['CreateUserID'] = $SearchRow->createUserID;
				$AllStockReturn[$SearchRow->stockReturnID]['CreateUserName'] = $SearchRow->createUserName;

				$AllStockReturn[$SearchRow->stockReturnID]['CreateDate'] = $SearchRow->createDate;
			}

			return $AllStockReturn;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at StockReturn::GetAllStockReturn(). Stack Trace: ' . $e->getTraceAsString());
			return $AllStockReturn;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at StockReturn::GetAllStockReturn(). Stack Trace: ' . $e->getTraceAsString());
			return $AllStockReturn;
		}
	}

	static function GetAllStockReturn1()
	{
		$AllStockReturn = array();

		try
		{
			$DBConnObject = new DBConnect();
			$RSSearch = $DBConnObject->Prepare('SELECT asr.*, asrd.*, ap.productName, apc.productCategoryName, ad.departmentName, 
													CONCAT(abs.firstName, " ", abs.lastName) AS branchStaffName, u.userName AS createUserName
													FROM aim_stock_return asr
													INNER JOIN aim_stock_return_details asrd ON asrd.stockReturnID = asr.stockReturnID
													INNER JOIN aim_products ap ON asrd.productID = ap.productID
													INNER JOIN aim_product_categories apc ON ap.productCategoryID = apc.productCategoryID
													LEFT JOIN aim_departments ad ON asr.departmentID = ad.departmentID
													INNER JOIN asa_branch_staff abs ON asr.branchStaffID = abs.branchStaffID
													INNER JOIN users u ON asr.createUserID = u.userID
        											ORDER BY asr.stockReturnID;');
			$RSSearch->Execute();

			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllStockReturn;
			}

			while ($SearchRow = $RSSearch->FetchRow())
			{
				$AllStockReturn[$SearchRow->stockReturnDetailID]['ProductID'] = $SearchRow->productID;
				$AllStockReturn[$SearchRow->stockReturnDetailID]['ProductCategoryName'] = $SearchRow->productCategoryName;
				$AllStockReturn[$SearchRow->stockReturnDetailID]['ProductName'] = $SearchRow->productName;

				$AllStockReturn[$SearchRow->stockReturnDetailID]['IssueType'] = $SearchRow->issueType;
				$AllStockReturn[$SearchRow->stockReturnDetailID]['DepartmentName'] = $SearchRow->departmentName;
				$AllStockReturn[$SearchRow->stockReturnDetailID]['BranchStaffName'] = $SearchRow->branchStaffName;

				$AllStockReturn[$SearchRow->stockReturnDetailID]['ReturnQuantity'] = $SearchRow->returnedQuantity;

				$AllStockReturn[$SearchRow->stockReturnDetailID]['ReturnDate'] = $SearchRow->returnDate;

				$AllStockReturn[$SearchRow->stockReturnDetailID]['CreateUserID'] = $SearchRow->createUserID;
				$AllStockReturn[$SearchRow->stockReturnDetailID]['CreateUserName'] = $SearchRow->createUserName;

				$AllStockReturn[$SearchRow->stockReturnDetailID]['CreateDate'] = $SearchRow->createDate;
			}

			return $AllStockReturn;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException::StockReturn::GetAllStockReturn(). Stack Trace: ' . $e->getTraceAsString());
			return $AllStockReturn;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception: StockReturn::GetAllStockReturn() . Stack Trace: ' . $e->getTraceAsString());
			return $AllStockReturn;
		}
	}

	static function GetStockIssued(&$TotalRecords = 0, $GetTotalsOnly = false, $Filters = array(), $Start = 0, $Limit = 100)
	{
		$AllStockIssues = array();

		try
		{
			$DBConnObject = new DBConnect();

			$Conditions = array();

			if (count($Filters) > 0)
			{

				if (isset($Filters['IssueType']) && !empty($Filters['IssueType']))
				{
					$Conditions[] = 'asi.issueType = ' . $DBConnObject->RealEscapeVariable($Filters['IssueType']);
				}

				if (isset($Filters['DepartmentID']) && !empty($Filters['DepartmentID']))
				{
					$Conditions[] = 'asi.departmentID = ' . $DBConnObject->RealEscapeVariable($Filters['DepartmentID']);
				}

				if (isset($Filters['StaffCategory']) && !empty($Filters['StaffCategory']))
				{
					$Conditions[] = 'asi.staffCategory = ' . $DBConnObject->RealEscapeVariable($Filters['StaffCategory']);
				}

				if (isset($Filters['BranchStaffID']) && !empty($Filters['BranchStaffID']))
				{
					$Conditions[] = 'asi.branchStaffID = ' . $DBConnObject->RealEscapeVariable($Filters['BranchStaffID']);
				}

				if (isset($Filters['ProductCategoryID']) && !empty($Filters['ProductCategoryID']))
				{
					$Conditions[] = 'asi.productCategoryID = ' . $DBConnObject->RealEscapeVariable($Filters['ProductCategoryID']);
				}

				if (isset($Filters['ProductID']) && !empty($Filters['ProductID']))
				{
					$Conditions[] = 'asid.productID = ' . $DBConnObject->RealEscapeVariable($Filters['ProductID']);
				}

				if (isset($Filters['IssueDate']) && !empty($Filters['IssueDate']))
				{
					$Conditions[] = 'asid.issueDate >= ' . $DBConnObject->RealEscapeVariable($Filters['IssueDate']);
				}

				if (isset($Filters['ReturnDate']) && !empty($Filters['ReturnDate']))
				{
					$Conditions[] = 'asid.returnDate <= ' . $DBConnObject->RealEscapeVariable($Filters['ReturnDate']);
				}
			}

			$QueryString = '';
			if (count($Conditions) > 0)
			{
				$QueryString = ' WHERE ' . implode(' AND ', $Conditions);
			}

			$RSSearch = $DBConnObject->Prepare('SELECT ap.productID, apcparent.productCategoryName AS parentCatetory, apcsub.productCategoryName AS subCategory, ap.productName, SUM(asid.quantity) AS totalIssuedQuantity, SUM(asrd.returnedQuantity) AS totalReturnedQuantity
												FROM aim_stock_issue asi
												INNER JOIN aim_stock_issue_details asid ON asi.stockIssueID = asid.stockIssueID

												INNER JOIN aim_products ap ON ap.productID = asid.productID
												LEFT JOIN aim_product_categories apcparent ON apcparent.parentCategoryID = ap.productCategoryID
												LEFT JOIN aim_product_categories apcsub ON apcsub.productCategoryID = ap.productCategoryID

												LEFT JOIN aim_stock_return_details asrD ON asrD.productID = asid.productID
    											' . $QueryString . '
    											GROUP BY asid.productID;');
			$RSSearch->Execute();

			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllStockIssues;
			}

			while ($SearchRow = $RSSearch->FetchRow())
			{
				if ($SearchRow->totalIssuedQuantity > $SearchRow->totalReturnedQuantity)
				{
					$AllStockIssues[$SearchRow->productID]['ParentCatetory'] = $SearchRow->parentCatetory;
					$AllStockIssues[$SearchRow->productID]['SubCategory'] = $SearchRow->subCategory;
					$AllStockIssues[$SearchRow->productID]['ProductName'] = $SearchRow->productName;
					$AllStockIssues[$SearchRow->productID]['RemainingQuantity'] = $SearchRow->totalIssuedQuantity - $SearchRow->totalReturnedQuantity;
				}
			}

			return $AllStockIssues;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException::StockReturn::GetStockIssued(). Stack Trace: ' . $e->getTraceAsString());
			return $AllStockIssues;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception: StockReturn::GetStockIssued() . Stack Trace: ' . $e->getTraceAsString());
			return $AllStockIssues;
		}
	}

	static function GetAllStockIssues()
	{
		$AllStockIssues = array();

		try
		{
			$DBConnObject = new DBConnect();
			$RSSearch = $DBConnObject->Prepare('SELECT asi.*, CONCAT(abs.firstName, " ", abs.lastName) AS branchStaffName, u.userName AS createUserName 
        											FROM aim_stock_return asi 
        											INNER JOIN asa_branch_staff abs ON asi.branchStaffID = abs.branchStaffID
													INNER JOIN users u ON asi.createUserID = u.userID 
        											ORDER BY asi.stockReturnID DESC;');
			$RSSearch->Execute();

			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllStockIssues;
			}

			while ($SearchRow = $RSSearch->FetchRow())
			{
				$AllStockIssues[$SearchRow->stockReturnID]['IssueType'] = $SearchRow->issueType;
				$AllStockIssues[$SearchRow->stockReturnID]['BranchStaffName'] = $SearchRow->branchStaffName;

				$AllStockIssues[$SearchRow->stockReturnID]['CreateUserID'] = $SearchRow->createUserID;
				$AllStockIssues[$SearchRow->stockReturnID]['CreateUserName'] = $SearchRow->createUserName;

				$AllStockIssues[$SearchRow->stockReturnID]['CreateDate'] = $SearchRow->createDate;
			}

			return $AllStockIssues;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException::StockReturn::GetAllStockIssues(). Stack Trace: ' . $e->getTraceAsString());
			return $AllStockIssues;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception: StockReturn::GetAllStockIssues() . Stack Trace: ' . $e->getTraceAsString());
			return $AllStockIssues;
		}
	}

	static function GetActiveStockIssues()
	{
		$AllStockIssues = array();

		try
		{
			$DBConnObject = new DBConnect();

			$RSSearch = $DBConnObject->Prepare('SELECT * FROM aim_stock_return WHERE issueType = 1;');
			$RSSearch->Execute();

			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllStockIssues;
			}

			while ($SearchRow = $RSSearch->FetchRow())
			{
				$AllStockIssues[$SearchRow->stockReturnID] = $SearchRow->productID;
			}

			return $AllStockIssues;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at StockReturn::GetActiveStockIssues(). Stack Trace: ' . $e->getTraceAsString());
			return $AllStockIssues;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at StockReturn::GetActiveStockIssues(). Stack Trace: ' . $e->getTraceAsString());
			return $AllStockIssues;
		}
	}

	static function GetStockQunatity($ProductID)
	{
		$StockQuantity = 0;
		$TotalIssuedQunatityToDepartment = 0;
		$TotalQuantityIssuedByDepartment = 0;

		try
		{
			$DBConnObject = new DBConnect();

			$RSSearchIssuedQunatityToDepartment = $DBConnObject->Prepare('SELECT sum(issuedQuantity) as totalIssuedQunatityToDepartment FROM aim_stock_return
														  					WHERE productID = :|1 AND issueType = "Department" LIMIT 1;');
			$RSSearchIssuedQunatityToDepartment->Execute($ProductID);

			$RSSearchRowIssuedQunatityToDepartment = $RSSearchIssuedQunatityToDepartment->FetchRow();

			$RSSearchQuantityIssuedByDepartment = $DBConnObject->Prepare('SELECT sum(issuedQuantity) AS totalQuantityIssuedByDepartment 
																			FROM aim_department_stock_issue WHERE productID = :|1 LIMIT 1;');
			$RSSearchQuantityIssuedByDepartment->Execute($ProductID);

			$RSSearchRowQuantityIssuedByDepartment = $RSSearchQuantityIssuedByDepartment->FetchRow();

			if (count($RSSearchRowIssuedQunatityToDepartment) > 0)
			{
				$TotalIssuedQunatityToDepartment = $RSSearchRowIssuedQunatityToDepartment->totalIssuedQunatityToDepartment;
			}

			if (count($RSSearchRowQuantityIssuedByDepartment) > 0)
			{
				$TotalQuantityIssuedByDepartment = $RSSearchRowQuantityIssuedByDepartment->totalQuantityIssuedByDepartment;
			}

			$StockQuantity = $TotalIssuedQunatityToDepartment - $TotalQuantityIssuedByDepartment;

			return $StockQuantity;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at StockReturn::GetStockQunatity(). Stack Trace: ' . $e->getTraceAsString());
			return $StockQuantity;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at StockReturn::GetStockQunatity(). Stack Trace: ' . $e->getTraceAsString());
			return $StockQuantity;
		}
	}

	static function GetProductsByDepartmentID($DepartmentID)
	{
		$AllProducts = array();

		try
		{
			$DBConnObject = new DBConnect();

			$RSSearch = $DBConnObject->Prepare('SELECT asi.productID, ap.productName 
													FROM aim_stock_return asi 
													INNER JOIN aim_products ap ON asi.productID = ap.productID
													WHERE asi.departmentID = :|1 
													AND asi.issueType = "Department";');
			$RSSearch->Execute($DepartmentID);

			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllProducts;
			}

			while ($SearchRow = $RSSearch->FetchRow())
			{
				$AllProducts[$SearchRow->productID] = $SearchRow->productName;
			}

			return $AllProducts;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at StockReturn::GetProductsByDepartmentID(). Stack Trace: ' . $e->getTraceAsString());
			return $AllProducts;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at StockReturn::GetProductsByDepartmentID(). Stack Trace: ' . $e->getTraceAsString());
			return $AllProducts;
		}
	}

	// END OF STATIC METHODS	//
	// START OF PRIVATE METHODS	//
	private function SaveDetails()
	{
		$StockQuantity = 0;

		if ($this->StockReturnID == 0)
		{
			$RSSave = $this->DBObject->Prepare('INSERT INTO aim_stock_return (issueType, departmentID, branchStaffID, returnDate, createUserID, createDate)
														VALUES (:|1, :|2, :|3, CURDATE(), :|4, NOW());');
			$RSSave->Execute($this->IssueType, $this->DepartmentID, $this->BranchStaffID, $this->CreateUserID);

			$this->StockReturnID = $RSSave->LastID;

			if (is_array($this->StockReturnDetails) && !empty($this->StockReturnDetails))
			{
				foreach ($this->StockReturnDetails as $StockIssueDetailID => $Details)
				{
					$RSSave = $this->DBObject->Prepare('INSERT INTO aim_stock_return_details (stockReturnID, productID, returnedQuantity)
														VALUES (:|1, :|2, :|3);');
					$RSSave->Execute($this->StockReturnID, $Details['ProductID'], $Details['ReturnQuantity']);

					$StockReturnDetailID = $RSSave->LastID;

					$RSUpdate = $this->DBObject->Prepare('UPDATE aim_stock_issue_details
															SET	returnedQuantity = (returnedQuantity + :|1)
															WHERE stockIssueDetailID = :|2 AND productID = :|3 LIMIT 1;');
					$RSUpdate->Execute($Details['ReturnQuantity'], $StockIssueDetailID, $Details['ProductID']);


					$RSUpdateProductStock = $this->DBObject->Prepare('UPDATE aim_products
																		SET	stockQuantity = (stockQuantity + :|1)
																		WHERE productID = :|2 LIMIT 1;');
					$RSUpdateProductStock->Execute($Details['ReturnQuantity'], $Details['ProductID']);
				}
			}
		}
		else
		{
			$RSUpdate = $this->DBObject->Prepare('UPDATE aim_stock_return
													SET	issueType = :|1,
														departmentID = :|2,
														branchStaffID = :|3,
														voucherNumber = :|4
													WHERE stockReturnID = :|5 LIMIT 1;');
			$RSUpdate->Execute($this->IssueType, $this->DepartmentID, $this->BranchStaffID, $this->VoucherNumber, $this->StockReturnID);

			$RSSearchStockIssueDetails = $this->DBObject->Prepare('SELECT * FROM aim_stock_return_details WHERE stockReturnID = :|1;');
			$RSSearchStockIssueDetails->Execute($this->StockReturnID);

			if ($RSSearchStockIssueDetails->Result->num_rows > 0)
			{
				while ($SearchRow = $RSSearchStockIssueDetails->FetchRow())
				{
					$ProductID = $SearchRow->productID;
					$ReturnedQuantity = $SearchRow->returnedQuantity;

					$RSUpdateProductStock = $this->DBObject->Prepare('UPDATE aim_products
																		SET	stockQuantity = (stockQuantity + :|1)
																		WHERE productID = :|2 
																		LIMIT 1;');
					$RSUpdateProductStock->Execute($ReturnedQuantity, $ProductID);
				}
			}

			$RSDeleteAllStockIssueDetails = $this->DBObject->Prepare('DELETE FROM aim_stock_return_details WHERE stockReturnID = :|1;');
			$RSDeleteAllStockIssueDetails->Execute($this->StockReturnID);

			if (is_array($this->StockReturnDetails) && !empty($this->StockReturnDetails))
			{
				foreach ($this->StockReturnDetails as $Details)
				{
					$RSSave = $this->DBObject->Prepare('INSERT INTO aim_stock_return_details (stockReturnID, productID, returnedQuantity, issueDate, returnDate)
														VALUES (:|1, :|2, :|3, :|4, :|5);');
					$RSSave->Execute($this->StockReturnID, $Details['ProductID'], $Details['Quantity'], $Details['IssueDate'], $Details['ReturnDate']);

					$StockIssueDetailID = $RSSave->LastID;

					$RSSearchProducts = $this->DBObject->Prepare('SELECT stockQuantity FROM aim_products WHERE productID = :|1 LIMIT 1;');
					$RSSearchProducts->Execute($Details['ProductID']);

					$StockQuantity = $RSSearchProducts->FetchRow()->stockQuantity - $Details['Quantity'];

					$RSUpdateProductStock = $this->DBObject->Prepare('UPDATE aim_products
																SET	stockQuantity = :|1
																WHERE productID = :|2 LIMIT 1;');
					$RSUpdateProductStock->Execute($StockQuantity, $Details['ProductID']);
				}
			}
		}

		return true;
	}

	private function RemoveStockIssue()
	{
		if (!isset($this->StockReturnID))
		{
			throw new ApplicationARException('', APP_AR_ERROR_DELETE_WITHOUT_ID);
		}
		
		$this->FillStockReturnDetails();
		
		foreach($this->StockReturnDetails as $StockReturnDetails)
		{
			$CurrentProductID = $StockReturnDetails['ProductID'];
			$CurrentProductReturnQuantity = $StockReturnDetails['Quantity'];
			$ProductStockUpdate = $StockReturnDetails['Quantity'];
			
			# Get issued products by productid and branchstaff id
			$RSGetIssuedProducts = $this->DBObject->Prepare('SELECT stockIssueDetailID, quantity, returnedQuantity 
															FROM aim_stock_issue_details asid
															INNER JOIN aim_stock_issue asi ON asid.stockIssueID = asi.stockIssueID 
															WHERE asi.branchStaffID = :|1 AND asid.productID = :|2 
															ORDER BY stockIssueDetailID DESC;');
			
			$RSGetIssuedProducts->Execute($this->BranchStaffID, $CurrentProductID);
			
			while ($SearchProductRow = $RSGetIssuedProducts->FetchRow())
			{
				$CurrentIssuedProductID = $SearchProductRow->stockIssueDetailID;
				
				$ReturnQuantityToUpdate = 0;
				
				$CurrentIssuedProductReturnQuantity = $SearchProductRow->returnedQuantity;
				
				if ($CurrentProductReturnQuantity >= $CurrentIssuedProductReturnQuantity)
				{
					$CurrentProductReturnQuantity = $CurrentProductReturnQuantity - $CurrentIssuedProductReturnQuantity;
					
					$ReturnQuantityToUpdate = 0;
				}
				else
				{
					$ReturnQuantityToUpdate = $CurrentIssuedProductReturnQuantity - $CurrentProductReturnQuantity;
					
					$CurrentIssuedProductReturnQuantity = 0;
				}
				
				$RSUpdateReturnProductQuantity = $this->DBObject->Prepare('UPDATE aim_stock_issue_details SET returnedQuantity = :|1 WHERE stockIssueDetailID = :|2;');
				$RSUpdateReturnProductQuantity->Execute($ReturnQuantityToUpdate, $CurrentIssuedProductID);
			}
			
			$RSDeleteProductReturnDetails = $this->DBObject->Prepare('DELETE FROM aim_stock_return_details WHERE stockReturnID = :|1 AND productID = :|2 LIMIT 1;');
			$RSDeleteProductReturnDetails->Execute($this->StockReturnID, $StockReturnDetails['ProductID']);

			$RSUpdateProductStock = $this->DBObject->Prepare('UPDATE aim_products
															SET	stockQuantity = (stockQuantity - :|1)
															WHERE productID = :|2 LIMIT 1;');			
			$RSUpdateProductStock->Execute($ProductStockUpdate, $StockReturnDetails['ProductID']);
		}
		
		$RSDeleteStockIssue = $this->DBObject->Prepare('DELETE  FROM aim_stock_return WHERE stockReturnID = :|1 LIMIT 1;');
		$RSDeleteStockIssue->Execute($this->StockReturnID);
	}

	private function GetStockReturnByID()
	{
		$RSStockIssue = $this->DBObject->Prepare('SELECT * FROM aim_stock_return WHERE stockReturnID = :|1 LIMIT 1;');
		$RSStockIssue->Execute($this->StockReturnID);

		$StockIssueRow = $RSStockIssue->FetchRow();

		$this->SetAttributesFromDB($StockIssueRow);
	}

	private function SetAttributesFromDB($StockIssueRow)
	{
		$this->StockReturnID = $StockIssueRow->stockReturnID;

		$this->IssueType = $StockIssueRow->issueType;
		$this->DepartmentID = $StockIssueRow->departmentID;
		$this->BranchStaffID = $StockIssueRow->branchStaffID;
		$this->ReturnDate = $StockIssueRow->returnDate;

		$this->CreateUserID = $StockIssueRow->createUserID;
		$this->CreateDate = $StockIssueRow->createDate;
	}

}

?>
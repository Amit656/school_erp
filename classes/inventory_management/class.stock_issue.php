<?php

require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.db_connect.php');
error_reporting(E_ALL);

class StockIssue
{

	// CLASS MEMBERS ARE DEFINED HERE	//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	private $StockIssueID;
	private $IssueType;
	private $DepartmentID;
	private $BranchStaffID;
	private $VoucherNumber;
	private $Description;
	private $CreateUserID;
	private $CreateDate;
	private $StockIssuedDetails = array();

	// PUBLIC METHODS START HERE	//
	public function __construct($StockIssueID = 0)
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;

		if ($StockIssueID != 0)
		{
			$this->StockIssueID = $StockIssueID;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetStockIssueByID();
		}
		else
		{
			//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
			$this->StockIssueID = 0;

			$this->IssueType = 'Staff';
			$this->DepartmentID = 0;
			$this->BranchStaffID = 0;
			$this->VoucherNumber = 0;
			$this->Description = '';

			$this->CreateUserID = 0;
			$this->CreateDate = '0000-00-00 00:00:00';
			$this->StockIssuedDetails = array();
		}
	}

	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetStockIssueID()
	{
		return $this->StockIssueID;
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

	public function GetVoucherNumber()
	{
		return $this->VoucherNumber;
	}

	public function SetVoucherNumber($VoucherNumber)
	{
		$this->VoucherNumber = $VoucherNumber;
	}

	public function GetDescription()
	{
		return $this->Description;
	}

	public function SetDescription($Description)
	{
		$this->Description = $Description;
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

	public function GetStockIssuedDetails()
	{
		return $this->StockIssuedDetails;
	}

	public function SetStockIssuedDetails($StockIssuedDetails)
	{
		$this->StockIssuedDetails = $StockIssuedDetails;
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
			$RSTotal = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM role_group_roles WHERE stockIssueID = :|1;');
			$RSTotal->Execute($this->StockIssueID);

			if ($RSTotal->FetchRow()->totalRecords > 0)
			{
				return true;
			}

			return false;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException: StockIssue::CheckDependencies(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception: StockIssue::CheckDependencies(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
	}

	public function RecordExists()
	{
		try
		{
			$QueryString = '';

			if ($this->StockIssueID > 0)
			{
				$QueryString = ' AND stockIssueID != ' . $this->DBObject->RealEscapeVariable($this->StockIssueID);
			}

			$RSTotal = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM aim_stock_issue WHERE productID = :|1' . $QueryString . ';');
			$RSTotal->Execute($this->ProductID);

			if ($RSTotal->FetchRow()->totalRecords > 0)
			{
				return true;
			}

			return false;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at StockIssue::RecordExists(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at StockIssue::RecordExists(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
	}
	
	public function ProductExists($ProductID)
	{
		try
		{
			$RSTotal = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM aim_stock_issue_details WHERE stockIssueID = :|1 AND productID = :|2');
			$RSTotal->Execute($this->StockIssueID, $ProductID);

			if ($RSTotal->FetchRow()->totalRecords > 0)
			{
				return true;
			}

			return false;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at StockIssue::ProductExists(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at StockIssue::ProductExists(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
	}
	
	public function DeleteProductFromIssueDetails($ProductID)
	{
		try
		{
			$RSSearchProduct = $this->DBObject->Prepare('SELECT quantity FROM aim_stock_issue_details WHERE stockIssueID = :|1 AND productID = :|2 LIMIT 1;');
			$RSSearchProduct->Execute($this->StockIssueID, $ProductID);
			
			$RSUpdateProductStock = $this->DBObject->Prepare('UPDATE aim_products
																SET	stockQuantity = (stockQuantity + :|1)
																WHERE productID = :|2 LIMIT 1;');
			$RSUpdateProductStock->Execute($RSSearchProduct->FetchRow()->quantity, $ProductID);
			
			$RSDeleteProductIssueDetails = $this->DBObject->Prepare('DELETE FROM aim_stock_issue_details WHERE stockIssueID = :|1 AND productID = :|2 LIMIT 1;');
			$RSDeleteProductIssueDetails->Execute($this->StockIssueID, $ProductID);
			
			return true;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException: StockIssue::DeleteProductFromIssueDetails(). Stack Trace: ' . $e->getTraceAsString());
			return false;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception: StockIssue::DeleteProductFromIssueDetails(). Stack Trace: ' . $e->getTraceAsString());
			return false;
		}
	}

	public function FillStockIssuedDetails()
	{
		try
		{
			$RSSearchStockIssuedDetails = $this->DBObject->Prepare('SELECT asid.*, ap.productCategoryID 
																	FROM aim_stock_issue_details asid
																	INNER JOIN aim_products ap ON asid.productID = ap.productID
																	WHERE stockIssueID = :|1;');
			$RSSearchStockIssuedDetails->Execute($this->StockIssueID);

			while ($SearchRow = $RSSearchStockIssuedDetails->FetchRow())
			{
				$this->StockIssuedDetails[$SearchRow->stockIssueDetailID]['StockIssueID'] = $SearchRow->stockIssueID;
				$this->StockIssuedDetails[$SearchRow->stockIssueDetailID]['ProductID'] = $SearchRow->productID;
				$this->StockIssuedDetails[$SearchRow->stockIssueDetailID]['ProductCategoryID'] = $SearchRow->productCategoryID;

				$this->StockIssuedDetails[$SearchRow->stockIssueDetailID]['Quantity'] = $SearchRow->quantity;
				$this->StockIssuedDetails[$SearchRow->stockIssueDetailID]['ReturnedQuantity'] = $SearchRow->returnedQuantity;
				$this->StockIssuedDetails[$SearchRow->stockIssueDetailID]['IssueDate'] = $SearchRow->issueDate;
				$this->StockIssuedDetails[$SearchRow->stockIssueDetailID]['ReturnDate'] = $SearchRow->returnDate;
			}

			return false;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException: StockIssue::FillStockIssuedDetails(). Stack Trace: ' . $e->getTraceAsString());
			return false;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception: StockIssue::FillStockIssuedDetails(). Stack Trace: ' . $e->getTraceAsString());
			return false;
		}
	}
	
	public function UpdateIssueDetails($ProductID, $Quantity, $IssueDate, $ReturnDate,  $StockIssueDetailID = 0)
	{
		try
		{
			if ($StockIssueDetailID <= 0)
			{
				$RSSaveIssueDetails = $this->DBObject->Prepare('INSERT INTO aim_stock_issue_details (stockIssueID, productID, quantity, issueDate, returnDate)
																				VALUES (:|1, :|2, :|3, :|4, :|5);');
				$RSSaveIssueDetails->Execute($this->StockIssueID, $ProductID, $Quantity, $IssueDate, $ReturnDate);

				$RSUpdateProductStock = $this->DBObject->Prepare('UPDATE aim_products
																		SET	stockQuantity =  (stockQuantity - :|1)
																		WHERE productID = :|2 LIMIT 1;');
				$RSUpdateProductStock->Execute($Quantity, $ProductID);
			}
			else
			{
				$RSSearchProduct = $this->DBObject->Prepare('SELECT stockIssueDetailID, quantity FROM aim_stock_issue_details WHERE stockIssueID = :|1 AND productID = :|2 LIMIT 1;');
				$RSSearchProduct->Execute($this->StockIssueID, $ProductID);
				
				$ProductDetails = $RSSearchProduct->FetchRow();
				
				$NewStockQuantity = $Quantity - $ProductDetails->quantity;
				$StockIssueDetailID = $ProductDetails->stockIssueDetailID;
				

				$RSUpdateProductStock = $this->DBObject->Prepare('UPDATE aim_products
																	SET	stockQuantity = (stockQuantity + :|1)
																	WHERE productID = :|2 LIMIT 1;');
				$RSUpdateProductStock->Execute($NewStockQuantity, $ProductID);

				$RSUpdateIssueDetails = $this->DBObject->Prepare('UPDATE aim_stock_issue_details
																		SET	quantity = :|1,
																			issueDate = :|2,
																			returnDate = :|3
																		WHERE stockIssueDetailID = :|4 LIMIT 1;');
				$RSUpdateIssueDetails->Execute($Quantity, $IssueDate, $ReturnDate, $StockIssueDetailID);
			}
			
			return true;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException: StockIssue::UpdateIssueDetails(). Stack Trace: ' . $e->getTraceAsString());
			return false;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception: StockIssue::UpdateIssueDetails(). Stack Trace: ' . $e->getTraceAsString());
			return false;
		}
	}

	// END OF PUBLIC METHODS	//
	// START OF STATIC METHODS	//
	
	static function GetAllTransaction(&$TotalRecords = 0, $GetTotalsOnly = false, $Filters = array(), $Start = 0, $Limit = 100)
	{
		$AllStock = array();
		$AllStock['Purchase'] = array();
		$AllStock['Issue'] = array();
		$AllStock['Return'] = array();
		$AllStock['PurchaseBetween'] = array();
		$AllStock['IssueBetween'] = array();
		$AllStock['ReturnBetween'] = array();

		try
		{
			$DBConnObject = new DBConnect();

			$ConditionsPurchase = array();
			$ConditionsIssue = array();
			$ConditionsReturn = array();
			
			$ConditionsPurchaseBetween = array();
			$ConditionsIssueBetween = array();
			$ConditionsReturnBetween = array();
			
			//var_dump($Filters);exit;
			
			if (count($Filters) > 0)
			{
				if (isset($Filters['ProductCategoryID']) && !empty($Filters['ProductCategoryID']))
				{
					$ConditionsPurchase[] = 'apc.productCategoryID = ' . $DBConnObject->RealEscapeVariable($Filters['ProductCategoryID']);
					$ConditionsIssue[] = 'apc.productCategoryID = ' . $DBConnObject->RealEscapeVariable($Filters['ProductCategoryID']);
					$ConditionsReturn[] = 'apc.productCategoryID = ' . $DBConnObject->RealEscapeVariable($Filters['ProductCategoryID']);
					
					$ConditionsPurchaseBetween[] = 'apc.productCategoryID = ' . $DBConnObject->RealEscapeVariable($Filters['ProductCategoryID']);
					$ConditionsIssueBetween[] = 'apc.productCategoryID = ' . $DBConnObject->RealEscapeVariable($Filters['ProductCategoryID']);
					$ConditionsReturnBetween[] = 'apc.productCategoryID = ' . $DBConnObject->RealEscapeVariable($Filters['ProductCategoryID']);
				}

				if (isset($Filters['ProductID']) && !empty($Filters['ProductID']))
				{
					$ConditionsPurchase[] = 'ap.productID = ' . $DBConnObject->RealEscapeVariable($Filters['ProductID']);
					$ConditionsIssue[] = 'ap.productID = ' . $DBConnObject->RealEscapeVariable($Filters['ProductID']);
					$ConditionsReturn[] = 'ap.productID = ' . $DBConnObject->RealEscapeVariable($Filters['ProductID']);
					
					$ConditionsPurchaseBetween[] = 'ap.productID = ' . $DBConnObject->RealEscapeVariable($Filters['ProductID']);
					$ConditionsIssueBetween[] = 'ap.productID = ' . $DBConnObject->RealEscapeVariable($Filters['ProductID']);
					$ConditionsReturnBetween[] = 'ap.productID = ' . $DBConnObject->RealEscapeVariable($Filters['ProductID']);
				}

				if (isset($Filters['IssueDate']) && !empty($Filters['IssueDate']))
				{
					$ConditionsPurchase[] = 'app.purchaseDate < ' . $DBConnObject->RealEscapeVariable($Filters['IssueDate']);
					$ConditionsIssue[] = 'asid.issueDate < ' . $DBConnObject->RealEscapeVariable($Filters['IssueDate']);
					$ConditionsReturn[] = 'asr.returnDate <' . $DBConnObject->RealEscapeVariable($Filters['IssueDate']);
					
					$ConditionsPurchaseBetween[] = 'app.purchaseDate BETWEEN ' . $DBConnObject->RealEscapeVariable($Filters['IssueDate']) . ' AND ' . $DBConnObject->RealEscapeVariable($Filters['ReturnDate']);
					$ConditionsIssueBetween[] = 'asid.issueDate BETWEEN ' . $DBConnObject->RealEscapeVariable($Filters['IssueDate']) . ' AND ' . $DBConnObject->RealEscapeVariable($Filters['ReturnDate']);
					$ConditionsReturnBetween[] = 'asr.returnDate BETWEEN ' . $DBConnObject->RealEscapeVariable($Filters['IssueDate']) . ' AND ' . $DBConnObject->RealEscapeVariable($Filters['ReturnDate']);
				}
			}

			$QueryStringPurchase = '';
			$QueryStringIssue = '';
			$QueryStringReturn = '';
			
			$QueryStringPurchaseBetween = '';
			$QueryStringIssueBetween = '';
			$QueryStringReturnBetween = '';
			if (count($Filters) > 0)
			{
				$QueryStringPurchase = ' WHERE ' . implode(' AND ', $ConditionsPurchase);
				$QueryStringIssue = ' WHERE ' . implode(' AND ', $ConditionsIssue);
				$QueryStringReturn = ' WHERE ' . implode(' AND ', $ConditionsReturn);
				
				$QueryStringPurchaseBetween = ' WHERE ' . implode(' AND ', $ConditionsPurchaseBetween);
				$QueryStringIssueBetween = ' WHERE ' . implode(' AND ', $ConditionsIssueBetween);
				$QueryStringReturnBetween = ' WHERE ' . implode(' AND ', $ConditionsReturnBetween);
			}

			// Purchase Opening Balance
			$RSSearchtPurchase = $DBConnObject->Prepare('SELECT ap.productID, SUM(appd.quantity) AS totalPurchase
															FROM aim_product_purchases app
															INNER JOIN aim_product_purchases_details appd ON appd.purchaseID = app.purchaseID
															INNER JOIN aim_products ap ON ap.productID = appd.productID
															INNER JOIN aim_product_categories apc ON apc.productCategoryID = ap.productCategoryID
															' . $QueryStringPurchase . '
															GROUP BY appd.productID');
			$RSSearchtPurchase->Execute();
			
			if ($RSSearchtPurchase->Result->num_rows > 0)
			{
				while ($SearchPurchaseRow = $RSSearchtPurchase->FetchRow())
				{
					$AllStock['Purchase'][$SearchPurchaseRow->productID] = $SearchPurchaseRow->totalPurchase;
				}
			}
			
			// Issue Opening Balance			
			$RSSearchtIssue = $DBConnObject->Prepare('SELECT ap.productID, SUM(asid.quantity) AS totalIssue
														FROM aim_stock_issue asi
														INNER JOIN aim_stock_issue_details asid ON asid.stockIssueID = asi.stockIssueID
														INNER JOIN aim_products ap ON ap.productID = asid.productID
														INNER JOIN aim_product_categories apc ON apc.productCategoryID = ap.productCategoryID

														' . $QueryStringIssue . '
														GROUP BY asid.productID');
			$RSSearchtIssue->Execute();
			
			if ($RSSearchtIssue->Result->num_rows > 0)
			{
				while ($RSSearchtIssueRow = $RSSearchtIssue->FetchRow())
				{
					$AllStock['Issue'][$RSSearchtIssueRow->productID] = $RSSearchtIssueRow->totalIssue;
				}
			}
			
			// Return Opening Balance
			
			$RSSearchtReturn = $DBConnObject->Prepare('SELECT ap.productID, SUM(asrd.returnedQuantity) AS totalReturn
														FROM aim_stock_return asr
														INNER JOIN aim_stock_return_details asrd ON asrd.stockReturnID = asr.stockReturnID
														INNER JOIN aim_products ap ON ap.productID = asrd.productID
														INNER JOIN aim_product_categories apc ON apc.productCategoryID = ap.productCategoryID
														' . $QueryStringReturn . '
														GROUP BY asrd.productID');
			$RSSearchtReturn->Execute();
			
			if ($RSSearchtReturn->Result->num_rows > 0)
			{
				while ($RSSearchtReturnRow = $RSSearchtReturn->FetchRow())
				{
					$AllStock['Return'][$RSSearchtReturnRow->productID] = $RSSearchtReturnRow->totalReturn;
				}
			}
			
			// Purchase Quantity Between Given Date
			
			$RSSearchtPurchaseBetween = $DBConnObject->Prepare('SELECT ap.productID, SUM(appd.quantity) AS totalPurchase
																FROM aim_product_purchases app
																INNER JOIN aim_product_purchases_details appd ON appd.purchaseID = app.purchaseID
																INNER JOIN aim_products ap ON ap.productID = appd.productID
																INNER JOIN aim_product_categories apc ON apc.productCategoryID = ap.productCategoryID
																' . $QueryStringPurchaseBetween . '
																GROUP BY appd.productID');
			$RSSearchtPurchaseBetween->Execute();
			
			if ($RSSearchtPurchaseBetween->Result->num_rows > 0)
			{
				while ($PurchaseBetweenRow = $RSSearchtPurchaseBetween->FetchRow())
				{
					$AllStock['PurchaseBetween'][$PurchaseBetweenRow->productID] = $PurchaseBetweenRow->totalPurchase;
				}
			}
			
			// Issue Quantity Between Given Date
			
			$RSSearchtIssueBetween = $DBConnObject->Prepare('SELECT ap.productID, SUM(asid.quantity) AS totalIssue
																FROM aim_stock_issue asi
																INNER JOIN aim_stock_issue_details asid ON asid.stockIssueID = asi.stockIssueID
																INNER JOIN aim_products ap ON ap.productID = asid.productID
																INNER JOIN aim_product_categories apc ON apc.productCategoryID = ap.productCategoryID

																' . $QueryStringIssueBetween . '
																GROUP BY asid.productID');
			$RSSearchtIssueBetween->Execute();
			
			if ($RSSearchtIssueBetween->Result->num_rows > 0)
			{
				while ($IssueBetweenRow = $RSSearchtIssueBetween->FetchRow())
				{
					$AllStock['IssueBetween'][$IssueBetweenRow->productID] = $IssueBetweenRow->totalIssue;
				}
			}
			
			// Return Quantity Between Given Date
			
			$RSSearchtReturnBetween = $DBConnObject->Prepare('SELECT ap.productID, SUM(asrd.returnedQuantity) AS totalReturn
															FROM aim_stock_return asr
															INNER JOIN aim_stock_return_details asrd ON asrd.stockReturnID = asr.stockReturnID
															INNER JOIN aim_products ap ON ap.productID = asrd.productID
															INNER JOIN aim_product_categories apc ON apc.productCategoryID = ap.productCategoryID
															' . $QueryStringReturnBetween . '
															GROUP BY asrd.productID');
			$RSSearchtReturnBetween->Execute();
			
			if ($RSSearchtReturnBetween->Result->num_rows > 0)
			{
				while ($ReturnBetweenRow = $RSSearchtReturnBetween->FetchRow())
				{
					$AllStock['ReturnBetween'][$ReturnBetweenRow->productID] = $ReturnBetweenRow->totalReturn;
				}
			}
			
			return $AllStock;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException::StockIssue::GetAllTransaction(). Stack Trace: ' . $e->getTraceAsString());
			return $AllStock;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception: StockIssue::GetAllTransaction() . Stack Trace: ' . $e->getTraceAsString());
			return $AllStock;
		}
	}
	
	static function GetAllStockIssue(&$TotalRecords = 0, $GetTotalsOnly = false, $Filters = array(), $Start = 0, $Limit = 100)
	{
		$AllStockIssue = array();

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
					$Conditions[] = 'asi.branchStaffID IN (SELECT branchStaffID FROM asa_branch_staff WHERE staffCategory = ' . $DBConnObject->RealEscapeVariable($Filters['StaffCategory']) . ')';
				}

				if (isset($Filters['BranchStaffID']) && !empty($Filters['BranchStaffID']))
				{
					$Conditions[] = 'asi.branchStaffID = ' . $DBConnObject->RealEscapeVariable($Filters['BranchStaffID']);
				}

				if (isset($Filters['ProductCategoryID']) && !empty($Filters['ProductCategoryID']))
				{
					$Conditions[] = 'asi.stockIssueID IN (SELECT stockIssueID FROM aim_stock_issue_details WHERE productID IN (SELECT productID FROM aim_products WHERE productCategoryID = ' . $DBConnObject->RealEscapeVariable($Filters['ProductCategoryID']) . '))';
				}

				if (isset($Filters['ProductID']) && !empty($Filters['ProductID']))
				{
					$Conditions[] = 'asi.stockIssueID IN (SELECT stockIssueID FROM aim_stock_issue_details WHERE productID = ' . $DBConnObject->RealEscapeVariable($Filters['ProductID']) . ')';
				}
				
				if (isset($Filters['VoucherNumber']) && !empty($Filters['VoucherNumber']))
				{
					$Conditions[] = 'asi.voucherNumber LIKE ' . $DBConnObject->RealEscapeVariable( '%' .$Filters['VoucherNumber']. '%');
				}

				if (isset($Filters['Description']) && !empty($Filters['Description']))
				{
					$Conditions[] = 'asi.description LIKE ' . $DBConnObject->RealEscapeVariable('%' . $Filters['Description']. '%');
				}
				
				if (!empty($Filters['FromDate']) && !empty($Filters['ToDate']))
				{
					$Conditions[] = 'asi.stockIssueID IN (SELECT stockIssueID FROM aim_stock_issue_details asid WHERE asid.issueDate 
																												BETWEEN ' . $DBConnObject->RealEscapeVariable($Filters['FromDate']) . ' 
																													AND ' . $DBConnObject->RealEscapeVariable($Filters['ToDate']) . '
																												OR asid.returnDate
																												BETWEEN ' . $DBConnObject->RealEscapeVariable($Filters['FromDate']) . ' 
																													AND ' . $DBConnObject->RealEscapeVariable($Filters['ToDate']). ')';
				}
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
													FROM aim_stock_issue asi 
													INNER JOIN asa_branch_staff abs ON asi.branchStaffID = abs.branchStaffID
													INNER JOIN users u ON asi.createUserID = u.userID 
													' . $QueryString . ';');
				$RSTotal->Execute();

				$TotalRecords = $RSTotal->FetchRow()->totalRecords;
				return;
			}

			$RSSearch = $DBConnObject->Prepare('SELECT asi.*, CONCAT(abs.firstName, " ", abs.lastName) AS branchStaffName, abs.staffCategory, u.userName AS createUserName 
												FROM aim_stock_issue asi 
												INNER JOIN asa_branch_staff abs ON asi.branchStaffID = abs.branchStaffID
												INNER JOIN users u ON asi.createUserID = u.userID 
												
												' . $QueryString . ' 
												ORDER BY asi.stockIssueID DESC
												LIMIT ' . (int) $Start . ', ' . (int) $Limit . ';');
			$RSSearch->Execute();

			while ($SearchRow = $RSSearch->FetchRow())
			{
				$AllStockIssue[$SearchRow->stockIssueID]['StaffCategory'] = $SearchRow->staffCategory;
				$AllStockIssue[$SearchRow->stockIssueID]['IssueType'] = $SearchRow->issueType;
				$AllStockIssue[$SearchRow->stockIssueID]['BranchStaffName'] = $SearchRow->branchStaffName;
				$AllStockIssue[$SearchRow->stockIssueID]['VoucherNumber'] = $SearchRow->voucherNumber;
				$AllStockIssue[$SearchRow->stockIssueID]['Description'] = $SearchRow->description;

				$AllStockIssue[$SearchRow->stockIssueID]['CreateUserID'] = $SearchRow->createUserID;
				$AllStockIssue[$SearchRow->stockIssueID]['CreateUserName'] = $SearchRow->createUserName;

				$AllStockIssue[$SearchRow->stockIssueID]['CreateDate'] = $SearchRow->createDate;
			}

			return $AllStockIssue;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at StockIssue::GetAllStockIssue(). Stack Trace: ' . $e->getTraceAsString());
			return $AllStockIssue;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at StockIssue::GetAllStockIssue(). Stack Trace: ' . $e->getTraceAsString());
			return $AllStockIssue;
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
					$Conditions[] = 'asi.productID = ' . $DBConnObject->RealEscapeVariable($Filters['ProductID']);
				}

				if (isset($Filters['IssueDate']) && !empty($Filters['IssueDate']))
				{
					$Conditions[] = 'asi.issueDate >= ' . $DBConnObject->RealEscapeVariable($Filters['IssueDate']);
				}

				if (isset($Filters['ReturnDate']) && !empty($Filters['ReturnDate']))
				{
					$Conditions[] = 'asi.returnDate <= ' . $DBConnObject->RealEscapeVariable($Filters['ReturnDate']);
				}

				if (isset($Filters['StockReturn']) && !empty($Filters['StockReturn']))
				{
					$Conditions[] = 'asi.issuedQuantity > asi.returnedQuantity';
				}
			}

			$QueryString = '';
			if (count($Conditions) > 0)
			{
				$QueryString = ' WHERE ' . implode(' AND ', $Conditions);
			}

			$RSSearch = $DBConnObject->Prepare('SELECT asi.*, (asi.issuedQuantity - asi.returnedQuantity) AS remaingQuantity, ap.productName, apc.productCategoryName, ad.departmentName, CONCAT(abs.firstName, " ", abs.lastName) AS branchStaffName, u.userName AS createUserName 
        											FROM aim_stock_issue asi 
        											INNER JOIN aim_products ap ON asi.productID = ap.productID
        											INNER JOIN aim_product_categories apc ON ap.productCategoryID = apc.productCategoryID
        											LEFT JOIN aim_departments ad ON asi.departmentID = ad.departmentID
        											LEFT JOIN asa_branch_staff abs ON asi.branchStaffID = abs.branchStaffID
													INNER JOIN users u ON asi.createUserID = u.userID         											
        											' . $QueryString . '
        											ORDER BY asi.stockIssueID;');
			$RSSearch->Execute();

			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllStockIssues;
			}

			while ($SearchRow = $RSSearch->FetchRow())
			{
				$AllStockIssues[$SearchRow->stockIssueID]['ProductCategoryName'] = $SearchRow->productCategoryName;
				$AllStockIssues[$SearchRow->stockIssueID]['ProductName'] = $SearchRow->productName;
				$AllStockIssues[$SearchRow->stockIssueID]['ProductID'] = $SearchRow->productID;

				$AllStockIssues[$SearchRow->stockIssueID]['IssueType'] = $SearchRow->issueType;
				$AllStockIssues[$SearchRow->stockIssueID]['DepartmentName'] = $SearchRow->departmentName;
				$AllStockIssues[$SearchRow->stockIssueID]['BranchStaffName'] = $SearchRow->branchStaffName;

				$AllStockIssues[$SearchRow->stockIssueID]['RemaingQuantity'] = $SearchRow->remaingQuantity;
				$AllStockIssues[$SearchRow->stockIssueID]['VoucherNumber'] = $SearchRow->voucherNumber;
				$AllStockIssues[$SearchRow->stockIssueID]['Description'] = $SearchRow->description;
				$AllStockIssues[$SearchRow->stockIssueID]['IssueDate'] = $SearchRow->issueDate;
				$AllStockIssues[$SearchRow->stockIssueID]['ReturnDate'] = $SearchRow->returnDate;

				$AllStockIssues[$SearchRow->stockIssueID]['CreateUserID'] = $SearchRow->createUserID;
				$AllStockIssues[$SearchRow->stockIssueID]['CreateUserName'] = $SearchRow->createUserName;

				$AllStockIssues[$SearchRow->stockIssueID]['CreateDate'] = $SearchRow->createDate;
			}

			return $AllStockIssues;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException::StockIssue::GetStockIssued(). Stack Trace: ' . $e->getTraceAsString());
			return $AllStockIssues;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception: StockIssue::GetStockIssued() . Stack Trace: ' . $e->getTraceAsString());
			return $AllStockIssues;
		}
	}

	static function GetAllStockIssues1()
	{
		$AllStockIssues = array();

		try
		{
			$DBConnObject = new DBConnect();
			$RSSearch = $DBConnObject->Prepare('SELECT asi.*, CONCAT(abs.firstName, " ", abs.lastName) AS branchStaffName, u.userName AS createUserName 
        											FROM aim_stock_issue asi 
        											INNER JOIN asa_branch_staff abs ON asi.branchStaffID = abs.branchStaffID
													INNER JOIN users u ON asi.createUserID = u.userID 
        											ORDER BY asi.stockIssueID DESC;');
			$RSSearch->Execute();

			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllStockIssues;
			}

			while ($SearchRow = $RSSearch->FetchRow())
			{
				$AllStockIssues[$SearchRow->stockIssueID]['IssueType'] = $SearchRow->issueType;
				$AllStockIssues[$SearchRow->stockIssueID]['BranchStaffName'] = $SearchRow->branchStaffName;
				$AllStockIssues[$SearchRow->stockIssueID]['VoucherNumber'] = $SearchRow->voucherNumber;
				$AllStockIssues[$SearchRow->stockIssueID]['Description'] = $SearchRow->description;

				$AllStockIssues[$SearchRow->stockIssueID]['CreateUserID'] = $SearchRow->createUserID;
				$AllStockIssues[$SearchRow->stockIssueID]['CreateUserName'] = $SearchRow->createUserName;

				$AllStockIssues[$SearchRow->stockIssueID]['CreateDate'] = $SearchRow->createDate;
			}

			return $AllStockIssues;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException::StockIssue::GetAllStockIssues(). Stack Trace: ' . $e->getTraceAsString());
			return $AllStockIssues;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception: StockIssue::GetAllStockIssues() . Stack Trace: ' . $e->getTraceAsString());
			return $AllStockIssues;
		}
	}

	static function GetActiveStockIssues()
	{
		$AllStockIssues = array();

		try
		{
			$DBConnObject = new DBConnect();

			$RSSearch = $DBConnObject->Prepare('SELECT * FROM aim_stock_issue WHERE issueType = 1;');
			$RSSearch->Execute();

			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllStockIssues;
			}

			while ($SearchRow = $RSSearch->FetchRow())
			{
				$AllStockIssues[$SearchRow->stockIssueID] = $SearchRow->productID;
			}

			return $AllStockIssues;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at StockIssue::GetActiveStockIssues(). Stack Trace: ' . $e->getTraceAsString());
			return $AllStockIssues;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at StockIssue::GetActiveStockIssues(). Stack Trace: ' . $e->getTraceAsString());
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

			$RSSearchIssuedQunatityToDepartment = $DBConnObject->Prepare('SELECT sum(issuedQuantity) as totalIssuedQunatityToDepartment FROM aim_stock_issue
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
			error_log('DEBUG: ApplicationDBException at StockIssue::GetStockQunatity(). Stack Trace: ' . $e->getTraceAsString());
			return $StockQuantity;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at StockIssue::GetStockQunatity(). Stack Trace: ' . $e->getTraceAsString());
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
													FROM aim_stock_issue asi 
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
			error_log('DEBUG: ApplicationDBException at StockIssue::GetProductsByDepartmentID(). Stack Trace: ' . $e->getTraceAsString());
			return $AllProducts;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at StockIssue::GetProductsByDepartmentID(). Stack Trace: ' . $e->getTraceAsString());
			return $AllProducts;
		}
	}

	// END OF STATIC METHODS	//
	// START OF PRIVATE METHODS	//
	private function SaveDetails()
	{
		$StockQuantity = 0;

		if ($this->StockIssueID == 0)
		{

			$RSSave = $this->DBObject->Prepare('INSERT INTO aim_stock_issue (issueType, departmentID, branchStaffID, voucherNumber, description, createUserID, createDate)
														VALUES (:|1, :|2, :|3, :|4, :|5, :|6, NOW());');
			$RSSave->Execute($this->IssueType, $this->DepartmentID, $this->BranchStaffID, $this->VoucherNumber, $this->Description, $this->CreateUserID);

			$this->StockIssueID = $RSSave->LastID;

			if (is_array($this->StockIssuedDetails) && !empty($this->StockIssuedDetails))
			{
				foreach ($this->StockIssuedDetails as $Details)
				{
					$RSSave = $this->DBObject->Prepare('INSERT INTO aim_stock_issue_details (stockIssueID, productID, quantity, issueDate, returnDate)
														VALUES (:|1, :|2, :|3, :|4, :|5);');
					$RSSave->Execute($this->StockIssueID, $Details['ProductID'], $Details['IssuedQuantity'], $Details['IssueDate'], $Details['ReturnDate']);

					$RSUpdateProductStock = $this->DBObject->Prepare('UPDATE aim_products
																		SET	stockQuantity = (stockQuantity - :|1)
																		WHERE productID = :|2 LIMIT 1;');
					$RSUpdateProductStock->Execute($StockQuantity, $Details['ProductID']);
				}
			}
		}

		return true;
	}

	private function RemoveStockIssue()
	{
		if (!isset($this->StockIssueID))
		{
			throw new ApplicationARException('', APP_AR_ERROR_DELETE_WITHOUT_ID);
		}

		$RSDeleteStockIssue = $this->DBObject->Prepare('DELETE FROM aim_stock_issue WHERE stockIssueID = :|1 LIMIT 1;');
		$RSDeleteStockIssue->Execute($this->StockIssueID);
		
		$this->FillStockIssuedDetails();
		
		foreach($this->StockIssuedDetails as $StockIssueDetails)
		{
			$RSSearchProduct = $this->DBObject->Prepare('SELECT quantity FROM aim_stock_issue_details WHERE stockIssueID = :|1 AND productID = :|2 LIMIT 1;');
			$RSSearchProduct->Execute($this->StockIssueID, $StockIssueDetails['ProductID']);

			$RSUpdateProductStock = $this->DBObject->Prepare('UPDATE aim_products
																SET	stockQuantity = (stockQuantity + :|1)
																WHERE productID = :|2 LIMIT 1;');
			$RSUpdateProductStock->Execute($RSSearchProduct->FetchRow()->quantity, $StockIssueDetails['ProductID']);

			$RSDeleteProductIssueDetails = $this->DBObject->Prepare('DELETE FROM aim_stock_issue_details WHERE stockIssueID = :|1 AND productID = :|2 LIMIT 1;');
			$RSDeleteProductIssueDetails->Execute($this->StockIssueID, $StockIssueDetails['ProductID']);
		}
	}

	private function GetStockIssueByID()
	{
		$RSStockIssue = $this->DBObject->Prepare('SELECT * FROM aim_stock_issue WHERE stockIssueID = :|1 LIMIT 1;');
		$RSStockIssue->Execute($this->StockIssueID);

		$StockIssueRow = $RSStockIssue->FetchRow();

		$this->SetAttributesFromDB($StockIssueRow);
	}

	private function SetAttributesFromDB($StockIssueRow)
	{
		$this->StockIssueID = $StockIssueRow->stockIssueID;

		$this->IssueType = $StockIssueRow->issueType;
		$this->DepartmentID = $StockIssueRow->departmentID;
		$this->BranchStaffID = $StockIssueRow->branchStaffID;
		$this->VoucherNumber = $StockIssueRow->voucherNumber;
		$this->Description = $StockIssueRow->description;

		$this->CreateUserID = $StockIssueRow->createUserID;
		$this->CreateDate = $StockIssueRow->createDate;
	}

}

?>
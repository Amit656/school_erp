<?php

require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.db_connect.php');
error_reporting(E_ALL);

class ProductPurchase
{

	// CLASS MEMBERS ARE DEFINED HERE	//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	private $PurchaseID;
	private $ProductVendorID;
	private $VoucherNumber;
	private $PurchaseDate;
	private $Description;
	private $IsActive;
	private $CreateUserID;
	private $CreateDate;
	private $PurchasedProductDetails = array();

	// PUBLIC METHODS START HERE	//
	public function __construct($PurchaseID = 0)
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;

		if ($PurchaseID != 0)
		{
			$this->PurchaseID = $PurchaseID;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetProductPurchaseByID();
		}
		else
		{
			//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
			$this->PurchaseID = 0;
			$this->ProductVendorID = '';
			$this->VoucherNumber = '';
			$this->PurchaseDate = '0000-00-00';
			$this->Description = '';

			$this->IsActive = 0;
			$this->CreateUserID = 0;
			$this->CreateDate = '0000-00-00 00:00:00';

			$this->PurchasedProductDetails = array();
		}
	}

	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetPurchaseID()
	{
		return $this->PurchaseID;
	}

	public function GetProductVendorID()
	{
		return $this->ProductVendorID;
	}

	public function SetProductVendorID($ProductVendorID)
	{
		$this->ProductVendorID = $ProductVendorID;
	}

	public function GetVoucherNumber()
	{
		return $this->VoucherNumber;
	}

	public function SetVoucherNumber($VoucherNumber)
	{
		$this->VoucherNumber = $VoucherNumber;
	}

	public function GetPurchaseDate()
	{
		return $this->PurchaseDate;
	}

	public function SetPurchaseDate($PurchaseDate)
	{
		$this->PurchaseDate = $PurchaseDate;
	}

	public function GetDescription()
	{
		return $this->Description;
	}

	public function SetDescription($Description)
	{
		$this->Description = $Description;
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

	public function GetPurchasedProductDetails()
	{
		return $this->PurchasedProductDetails;
	}

	public function SetPurchasedProductDetails($PurchasedProductDetails)
	{
		$this->PurchasedProductDetails = $PurchasedProductDetails;
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
			$this->RemoveProductPurchase();
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
			$RSTotal = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM role_group_roles WHERE purchaseID = :|1;');
			$RSTotal->Execute($this->PurchaseID);

			if ($RSTotal->FetchRow()->totalRecords > 0)
			{
				return true;
			}

			return false;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException: ProductPurchase::CheckDependencies(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception: ProductPurchase::CheckDependencies(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
	}

	public function RecordExists()
	{
		try
		{
			$QueryString = '';

			if ($this->PurchaseID > 0)
			{
				$QueryString = ' AND purchaseID != ' . $this->DBObject->RealEscapeVariable($this->PurchaseID);
			}

			$RSTotal = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM aim_product_purchases WHERE productVendorID = :|1' . $QueryString . ';');
			$RSTotal->Execute($this->ProductVendorID);

			if ($RSTotal->FetchRow()->totalRecords > 0)
			{
				return true;
			}

			return false;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at ProductPurchase::RecordExists(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at ProductPurchase::RecordExists(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
	}
	
	public function ProductExists($ProductID)
	{
		try
		{
			$RSTotal = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM aim_product_purchases_details WHERE purchaseID = :|1 AND productID = :|2');
			$RSTotal->Execute($this->PurchaseID, $ProductID);

			if ($RSTotal->FetchRow()->totalRecords > 0)
			{
				return true;
			}

			return false;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at ProductPurchase::ProductExists(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at ProductPurchase::ProductExists(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
	}

	public function FillPurchasedProductDetails()
	{
		try
		{
			$RSSearchPurchasesDetails = $this->DBObject->Prepare('SELECT appd.*, ap.productCategoryID FROM aim_product_purchases_details appd
																	INNER JOIN aim_products ap ON appd.productID = ap.productID
																	WHERE purchaseID = :|1;');
			$RSSearchPurchasesDetails->Execute($this->PurchaseID);

			while ($SearchRow = $RSSearchPurchasesDetails->FetchRow())
			{
				$this->PurchasedProductDetails[$SearchRow->purchaseDetailID]['ProductID'] = $SearchRow->productID;
				$this->PurchasedProductDetails[$SearchRow->purchaseDetailID]['ProductCategoryID'] = $SearchRow->productCategoryID;

				$this->PurchasedProductDetails[$SearchRow->purchaseDetailID]['Rate'] = $SearchRow->rate;
				$this->PurchasedProductDetails[$SearchRow->purchaseDetailID]['Quantity'] = $SearchRow->quantity;
				$this->PurchasedProductDetails[$SearchRow->purchaseDetailID]['Amount'] = $SearchRow->amount;
			}

			return false;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException: ProductPurchase::FillPurchasedProductDetails(). Stack Trace: ' . $e->getTraceAsString());
			return false;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception: ProductPurchase::FillPurchasedProductDetails(). Stack Trace: ' . $e->getTraceAsString());
			return false;
		}
	}
	
	public function UpdatePurchasesDetails($ProductID, $Rate, $Quantity, $PurchaseDetailID = 0)
	{
		try
		{
			$Amount = $Rate * $Quantity;
			if ($PurchaseDetailID <= 0)
			{
				$RSSavePurchaseDetails = $this->DBObject->Prepare('INSERT INTO aim_product_purchases_details (purchaseID, productID, rate, quantity, amount)
																				VALUES (:|1, :|2, :|3, :|4, :|5);');
				$RSSavePurchaseDetails->Execute($this->PurchaseID, $ProductID, $Rate, $Quantity, $Amount);

				$RSUpdateProductStock = $this->DBObject->Prepare('UPDATE aim_products
																		SET	stockQuantity =  (stockQuantity + :|1)
																		WHERE productID = :|2 LIMIT 1;');
				$RSUpdateProductStock->Execute($Quantity, $ProductID);
			}
			else
			{
				$RSSearchProduct = $this->DBObject->Prepare('SELECT purchaseDetailID, quantity FROM aim_product_purchases_details WHERE purchaseID = :|1 AND productID = :|2 LIMIT 1;');
				$RSSearchProduct->Execute($this->PurchaseID, $ProductID);
				
				$ProductDetails = $RSSearchProduct->FetchRow();
				
				$NewStockQuantity = $Quantity - $ProductDetails->quantity;
				$PurchaseDetailID = $ProductDetails->purchaseDetailID;
				

				$RSUpdateProductStock = $this->DBObject->Prepare('UPDATE aim_products
																	SET	stockQuantity = (stockQuantity + :|1)
																	WHERE productID = :|2 LIMIT 1;');
				$RSUpdateProductStock->Execute($NewStockQuantity, $ProductID);

				$RSUpdatePurchasesDetails = $this->DBObject->Prepare('UPDATE aim_product_purchases_details
																		SET	rate = :|1,
																			quantity = :|2,
																			amount = :|3
																		WHERE purchaseDetailID = :|4 LIMIT 1;');
				$RSUpdatePurchasesDetails->Execute($Rate, $Quantity, $Amount, $PurchaseDetailID);
			}
			
			return true;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException: ProductPurchase::UpdatePurchasesDetails(). Stack Trace: ' . $e->getTraceAsString());
			return false;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception: ProductPurchase::UpdatePurchasesDetails(). Stack Trace: ' . $e->getTraceAsString());
			return false;
		}
	}

	public function DeleteProductFromPurchaseDetails($ProductID)
	{
		try
		{
			$RSSearchProduct = $this->DBObject->Prepare('SELECT quantity FROM aim_product_purchases_details WHERE purchaseID = :|1 AND productID = :|2 LIMIT 1;');
			$RSSearchProduct->Execute($this->PurchaseID, $ProductID);
			
			$RSUpdateProductStock = $this->DBObject->Prepare('UPDATE aim_products
																SET	stockQuantity = (stockQuantity - :|1)
																WHERE productID = :|2 LIMIT 1;');
			$RSUpdateProductStock->Execute($RSSearchProduct->FetchRow()->quantity, $ProductID);
			
			$RSDeleteProductPurchasesDetails = $this->DBObject->Prepare('DELETE FROM aim_product_purchases_details WHERE purchaseID = :|1 AND productID = :|2 LIMIT 1;');
			$RSDeleteProductPurchasesDetails->Execute($this->PurchaseID, $ProductID);
			
			return true;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException: ProductPurchase::DeleteProductFromPurchasesDetails(). Stack Trace: ' . $e->getTraceAsString());
			return false;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception: ProductPurchase::DeleteProductFromPurchasesDetails(). Stack Trace: ' . $e->getTraceAsString());
			return false;
		}
	}

	// END OF PUBLIC METHODS	//
	// START OF STATIC METHODS	//
	
	static function GetAllProductPurchases(&$TotalRecords = 0, $GetTotalsOnly = false, $Filters = array(), $Start = 0, $Limit = 100)
	{
		$AllProductPurchases = array();

		try
		{
			$DBConnObject = new DBConnect();

			$Conditions = array();

			if (count($Filters) > 0)
			{
				if (!empty($Filters['VendorID']))
				{
					$Conditions[] = 'apv.productVendorID = ' . $DBConnObject->RealEscapeVariable($Filters['VendorID']);
				}

				if (!empty($Filters['VoucherNumber']))
				{
					$Conditions[] = 'app.voucherNumber = ' . $DBConnObject->RealEscapeVariable($Filters['VoucherNumber']);
				}

				if (!empty($Filters['ProductCategoryID']))
				{
					$Conditions[] = 'app.purchaseID IN (SELECT purchaseID FROM aim_product_purchases_details WHERE productID IN (SELECT productID FROM aim_products WHERE productCategoryID = ' . $DBConnObject->RealEscapeVariable($Filters['ProductCategoryID']) . '))';
				}

				if (!empty($Filters['ProductID']))
				{
					$Conditions[] = 'app.purchaseID IN (SELECT purchaseID FROM aim_product_purchases_details WHERE productID = ' . $DBConnObject->RealEscapeVariable($Filters['ProductID']) . ')';
				}

				if (!empty($Filters['FromDate']) && !empty($Filters['ToDate']))
				{
					$Conditions[] = 'app.purchaseDate BETWEEN ' . $DBConnObject->RealEscapeVariable( $Filters['FromDate']) . 'AND' . $DBConnObject->RealEscapeVariable( $Filters['ToDate']);
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
													FROM aim_product_purchases app
													INNER JOIN aim_product_vendors apv ON app.productVendorID = apv.productVendorID
													INNER JOIN users u ON app.createUserID = u.userID
													' . $QueryString . ';');
				$RSTotal->Execute();

				$TotalRecords = $RSTotal->FetchRow()->totalRecords;
				return;
			}

			$RSSearch = $DBConnObject->Prepare('SELECT app.*, apv.vendorName, u.userName AS createUserName 
        										FROM aim_product_purchases app
												INNER JOIN aim_product_vendors apv ON app.productVendorID = apv.productVendorID
												INNER JOIN users u ON app.createUserID = u.userID
												
												' . $QueryString . ' 
												GROUP BY app.purchaseID
												ORDER BY app.purchaseDate DESC
												LIMIT ' . (int) $Start . ', ' . (int) $Limit . ';');
			$RSSearch->Execute();

			while ($SearchRow = $RSSearch->FetchRow())
			{
				$AllProductPurchases[$SearchRow->purchaseID]['VendorName'] = $SearchRow->vendorName;
				$AllProductPurchases[$SearchRow->purchaseID]['VoucherNumber'] = $SearchRow->voucherNumber;
				$AllProductPurchases[$SearchRow->purchaseID]['PurchaseDate'] = $SearchRow->purchaseDate;

				$AllProductPurchases[$SearchRow->purchaseID]['IsActive'] = $SearchRow->isActive;
				$AllProductPurchases[$SearchRow->purchaseID]['CreateUserID'] = $SearchRow->createUserID;
				$AllProductPurchases[$SearchRow->purchaseID]['CreateUserName'] = $SearchRow->createUserName;

				$AllProductPurchases[$SearchRow->purchaseID]['CreateDate'] = $SearchRow->createDate;
			}

			return $AllProductPurchases;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at ProductPurchase::GetAllProductPurchases(). Stack Trace: ' . $e->getTraceAsString());
			return $AllProductPurchases;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at ProductPurchase::GetAllProductPurchases(). Stack Trace: ' . $e->getTraceAsString());
			return $AllProductPurchases;
		}
	}
	
	static function GetAllProductPurchases1()
	{
		$AllProductPurchases = array();

		try
		{
			$DBConnObject = new DBConnect();
			$RSSearch = $DBConnObject->Prepare('SELECT app.*, apv.vendorName, u.userName AS createUserName 
        										FROM aim_product_purchases app 
												INNER JOIN aim_product_vendors apv ON app.productVendorID	 = apv.productVendorID	 
												INNER JOIN users u ON app.createUserID = u.userID 
    											ORDER BY app.createDate;');
			$RSSearch->Execute();

			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllProductPurchases;
			}

			while ($SearchRow = $RSSearch->FetchRow())
			{
				$AllProductPurchases[$SearchRow->purchaseID]['VendorName'] = $SearchRow->vendorName;
				$AllProductPurchases[$SearchRow->purchaseID]['VoucherNumber'] = $SearchRow->voucherNumber;
				$AllProductPurchases[$SearchRow->purchaseID]['PurchaseDate'] = $SearchRow->purchaseDate;

				$AllProductPurchases[$SearchRow->purchaseID]['IsActive'] = $SearchRow->isActive;
				$AllProductPurchases[$SearchRow->purchaseID]['CreateUserID'] = $SearchRow->createUserID;
				$AllProductPurchases[$SearchRow->purchaseID]['CreateUserName'] = $SearchRow->createUserName;

				$AllProductPurchases[$SearchRow->purchaseID]['CreateDate'] = $SearchRow->createDate;
			}

			return $AllProductPurchases;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException::ProductPurchase::GetAllProductPurchases(). Stack Trace: ' . $e->getTraceAsString());
			return $AllProductPurchases;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception: ProductPurchase::GetAllProductPurchases() . Stack Trace: ' . $e->getTraceAsString());
			return $AllProductPurchases;
		}
	}

	static function GetActiveProductPurchases()
	{
		$AllProductPurchases = array();

		try
		{
			$DBConnObject = new DBConnect();

			$RSSearch = $DBConnObject->Prepare('SELECT * FROM aim_product_purchases WHERE isActive = 1;');
			$RSSearch->Execute();

			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllProductPurchases;
			}

			while ($SearchRow = $RSSearch->FetchRow())
			{
				$AllProductPurchases[$SearchRow->purchaseID] = $SearchRow->productVendorID;
			}

			return $AllProductPurchases;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at ProductPurchase::GetActiveProductPurchases(). Stack Trace: ' . $e->getTraceAsString());
			return $AllProductPurchases;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at ProductPurchase::GetActiveProductPurchases(). Stack Trace: ' . $e->getTraceAsString());
			return $AllProductPurchases;
		}
	}

	// END OF STATIC METHODS	//
	// START OF PRIVATE METHODS	//
	private function SaveDetails()
	{
		if ($this->PurchaseID == 0)
		{
			$RSSaveProductPurchase = $this->DBObject->Prepare('INSERT INTO aim_product_purchases (productVendorID, voucherNumber, purchaseDate, description, isActive, createUserID, createDate)
														VALUES (:|1, :|2, :|3, :|4, :|5, :|6, NOW());');
			$RSSaveProductPurchase->Execute($this->ProductVendorID, $this->VoucherNumber, $this->PurchaseDate, $this->Description, $this->IsActive, $this->CreateUserID);

			$this->PurchaseID = $RSSaveProductPurchase->LastID;

			foreach ($this->PurchasedProductDetails as $PurchasedProductValue)
			{
				$RSSavePurchaseDetails = $this->DBObject->Prepare('INSERT INTO aim_product_purchases_details (purchaseID, productID, rate, quantity, amount)
																				VALUES (:|1, :|2, :|3, :|4, :|5);');
				$RSSavePurchaseDetails->Execute($this->PurchaseID, $PurchasedProductValue['ProductID'], $PurchasedProductValue['Rate'], $PurchasedProductValue['Quantity'], $PurchasedProductValue['Amount']);

				$RSUpdateProductStock = $this->DBObject->Prepare('UPDATE aim_products
																		SET	stockQuantity =  (stockQuantity + :|1)
																		WHERE productID = :|2 LIMIT 1;');
				$RSUpdateProductStock->Execute($PurchasedProductValue['Quantity'], $PurchasedProductValue['ProductID']);
			}
		}
		return true;
	}

	private function RemoveProductPurchase()
	{
		if (!isset($this->PurchaseID))
		{
			throw new ApplicationARException('', APP_AR_ERROR_DELETE_WITHOUT_ID);
		}

		$RSDeleteProductPurchasesDetails = $this->DBObject->Prepare('DELETE FROM aim_product_purchases_details WHERE purchaseID = :|1;');
		$RSDeleteProductPurchasesDetails->Execute($this->PurchaseID);

		$RSDeleteProductPurchase = $this->DBObject->Prepare('DELETE FROM aim_product_purchases WHERE purchaseID = :|1 LIMIT 1;');
		$RSDeleteProductPurchase->Execute($this->PurchaseID);
	}
	
	private function RemoveProductPurchaseDetails()
	{
		if (!isset($this->PurchaseID))
		{
			throw new ApplicationARException('', APP_AR_ERROR_DELETE_WITHOUT_ID);
		}
		
		$this->FillPurchasedProductDetails();
		
		foreach ($this->PurchasedProductDetails as $PurchasedProductValue)
		{
			//$this->DeleteProductFromPurchasesDetails($PurchaseDetailID, $PurchasedProductValue['ProductID']);
			$RSSearchProduct = $this->DBObject->Prepare('SELECT quantity FROM aim_product_purchases_details WHERE purchaseID = :|1 AND productID = :|2 LIMIT 1;');
			$RSSearchProduct->Execute($this->PurchaseID, $ProductID);
			
			$RSUpdateProductStock = $this->DBObject->Prepare('UPDATE aim_products
																SET	stockQuantity = (stockQuantity - :|1)
																WHERE productID = :|2 LIMIT 1;');
			$RSUpdateProductStock->Execute($RSSearchProduct->FetchRow()->quantity, $PurchasedProductValue['ProductID']);
			
			$RSDeleteProductPurchasesDetails = $this->DBObject->Prepare('DELETE FROM aim_product_purchases_details WHERE purchaseID = :|1 AND productID = :|2 LIMIT 1;');
			$RSDeleteProductPurchasesDetails->Execute($this->PurchaseID, $PurchasedProductValue['ProductID']);
		}

		$RSDeleteProductPurchase = $this->DBObject->Prepare('DELETE FROM aim_product_purchases WHERE purchaseID = :|1 LIMIT 1;');
		$RSDeleteProductPurchase->Execute($this->PurchaseID);
	}

	private function GetProductPurchaseByID()
	{
		$RSProductPurchase = $this->DBObject->Prepare('SELECT * FROM aim_product_purchases WHERE purchaseID = :|1 LIMIT 1;');
		$RSProductPurchase->Execute($this->PurchaseID);

		$ProductPurchaseRow = $RSProductPurchase->FetchRow();

		$this->SetAttributesFromDB($ProductPurchaseRow);
	}

	private function SetAttributesFromDB($ProductPurchaseRow)
	{
		$this->PurchaseID = $ProductPurchaseRow->purchaseID;
		$this->ProductVendorID = $ProductPurchaseRow->productVendorID;
		$this->VoucherNumber = $ProductPurchaseRow->voucherNumber;
		$this->PurchaseDate = $ProductPurchaseRow->purchaseDate;
		$this->Description = $ProductPurchaseRow->description;

		$this->IsActive = $ProductPurchaseRow->isActive;
		$this->CreateUserID = $ProductPurchaseRow->createUserID;
		$this->CreateDate = $ProductPurchaseRow->createDate;
	}

}

?>
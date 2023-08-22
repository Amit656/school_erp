<?php

require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.db_connect.php');
error_reporting(E_ALL);

class Product
{

	// CLASS MEMBERS ARE DEFINED HERE	//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	private $ProductID;
	private $ProductCategoryID;
	private $ProductName;
	private $ProductType;
	private $ProductUnitID;
	private $productUnitValue;
	private $ProductDescription;
	private $StockQuantity;
	private $IsActive;
	private $CreateUserID;
	private $CreateDate;

	// PUBLIC METHODS START HERE	//
	public function __construct($ProductID = 0)
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;

		if ($ProductID != 0)
		{
			$this->ProductID = $ProductID;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetProductByID();
		}
		else
		{
			//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
			$this->ProductID = 0;
			$this->ProductCategoryID = 0;

			$this->ProductName = '';
			$this->ProductType = 'Perishable';
			$this->ProductUnitID = 0;

			$this->ProductUnitValue = '';
			$this->ProductDescription = '';
			$this->StockQuantity = 0;

			$this->IsActive = 0;
			$this->CreateUserID = 0;
			$this->CreateDate = '0000-00-00 00:00:00';
		}
	}

	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetProductID()
	{
		return $this->ProductID;
	}

	public function GetProductCategoryID()
	{
		return $this->ProductCategoryID;
	}

	public function SetProductCategoryID($ProductCategoryID)
	{
		$this->ProductCategoryID = $ProductCategoryID;
	}

	public function GetProductName()
	{
		return $this->ProductName;
	}

	public function SetProductName($ProductName)
	{
		$this->ProductName = $ProductName;
	}

	public function GetProductType()
	{
		return $this->ProductType;
	}

	public function SetProductType($ProductType)
	{
		$this->ProductType = $ProductType;
	}

	public function GetProductUnitID()
	{
		return $this->ProductUnitID;
	}

	public function SetProductUnitID($ProductUnitID)
	{
		$this->ProductUnitID = $ProductUnitID;
	}

	public function GetProductUnitValue()
	{
		return $this->ProductUnitValue;
	}

	public function SetProductUnitValue($ProductUnitValue)
	{
		$this->ProductUnitValue = $ProductUnitValue;
	}

	public function GetProductDescription()
	{
		return $this->ProductDescription;
	}

	public function SetProductDescription($ProductDescription)
	{
		$this->ProductDescription = $ProductDescription;
	}

	public function GetStockQuantity()
	{
		return $this->StockQuantity;
	}

	public function SetStockQuantity($StockQuantity)
	{
		$this->StockQuantity = $StockQuantity;
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
			$this->RemoveProduct();
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
			$RSTotal = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM aim_product_purchases_details WHERE productID = :|1;');
			$RSTotal->Execute($this->ProductID);

			if ($RSTotal->FetchRow()->totalRecords > 0)
			{
				return true;
			}

			return false;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException: Product::CheckDependencies(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception: Product::CheckDependencies(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
	}

	public function RecordExists()
	{
		try
		{
			$QueryString = '';

			if ($this->ProductID > 0)
			{
				$QueryString = ' AND productID != ' . $this->DBObject->RealEscapeVariable($this->ProductID);
			}

			$RSTotal = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM aim_products WHERE productName = :|1' . $QueryString . ';');
			$RSTotal->Execute($this->ProductName);

			if ($RSTotal->FetchRow()->totalRecords > 0)
			{
				return true;
			}

			return false;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at Product::RecordExists(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at Product::RecordExists(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
	}

	// END OF PUBLIC METHODS	//
	// START OF STATIC METHODS	//

	static function GetAllProducts(&$TotalRecords = 0, $GetTotalsOnly = false, $Filters = array(), $Start = 0, $Limit = 100)
	{
		$AllProducts = array();

		try
		{
			$DBConnObject = new DBConnect();

			$Conditions = array();

			if (count($Filters) > 0)
			{
				if (!empty($Filters['ProductCategoryID']))
				{
					$Conditions[] = 'apc.productCategoryID = ' . $DBConnObject->RealEscapeVariable($Filters['ProductCategoryID']);
				}
				
				if (isset($Filters['ProductID']) && !empty($Filters['ProductID']))
				{
					$Conditions[] = 'ap.productID = ' . $DBConnObject->RealEscapeVariable($Filters['ProductID']);
				}				
				if (isset($Filters['ProductName']) && !empty($Filters['ProductName']))
				{
					$Conditions[] = 'ap.productName LIKE ' . $DBConnObject->RealEscapeVariable("%" . $Filters['ProductName'] . "%");
				}
				
				if (isset($Filters['ProductType']) && !empty($Filters['ProductType']))
				{
					$Conditions[] = 'ap.productType = ' . $DBConnObject->RealEscapeVariable($Filters['ProductType']);
				}
				
				if (isset($Filters['ProductUnitID']) && !empty($Filters['ProductUnitID']))
				{
					$Conditions[] = 'ap.productUnitID = ' . $DBConnObject->RealEscapeVariable($Filters['ProductUnitID']);
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
													FROM aim_products ap 
													INNER JOIN aim_product_categories apc ON ap.productCategoryID = apc.productCategoryID
													INNER JOIN aim_master_product_units ampu ON ap.productUnitID = ampu.productUnitID
													INNER JOIN users u ON ap.createUserID = u.userID 
													' . $QueryString . ';');
				$RSTotal->Execute();

				$TotalRecords = $RSTotal->FetchRow()->totalRecords;
				return;
			}

			$RSSearch = $DBConnObject->Prepare('SELECT ap.*, apc.productCategoryName, ampu.productUnitName, u.userName AS createUserName 
												FROM aim_products ap 
												INNER JOIN aim_product_categories apc ON ap.productCategoryID = apc.productCategoryID
												INNER JOIN aim_master_product_units ampu ON ap.productUnitID = ampu.productUnitID
												INNER JOIN users u ON ap.createUserID = u.userID 
												
												' . $QueryString . ' 
												ORDER BY ap.productID
												LIMIT ' . (int) $Start . ', ' . (int) $Limit . ';');
			$RSSearch->Execute();

			while ($SearchRow = $RSSearch->FetchRow())
			{
				$AllProducts[$SearchRow->productID]['ProductCategoryName'] = $SearchRow->productCategoryName;
				$AllProducts[$SearchRow->productID]['ProductName'] = $SearchRow->productName;
				$AllProducts[$SearchRow->productID]['ProductType'] = $SearchRow->productType;

				$AllProducts[$SearchRow->productID]['ProductUnitName'] = $SearchRow->productUnitName;
				$AllProducts[$SearchRow->productID]['ProductUnitValue'] = $SearchRow->productUnitValue;
				$AllProducts[$SearchRow->productID]['ProductDescription'] = $SearchRow->productDescription;
				$AllProducts[$SearchRow->productID]['StockQuantity'] = $SearchRow->stockQuantity;

				$AllProducts[$SearchRow->productID]['IsActive'] = $SearchRow->isActive;
				$AllProducts[$SearchRow->productID]['CreateUserID'] = $SearchRow->createUserID;
				$AllProducts[$SearchRow->productID]['CreateUserName'] = $SearchRow->createUserName;

				$AllProducts[$SearchRow->productID]['CreateDate'] = $SearchRow->createDate;
			}

			return $AllProducts;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at Product::GetAllProducts(). Stack Trace: ' . $e->getTraceAsString());
			return $AllProducts;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at Product::GetAllProducts(). Stack Trace: ' . $e->getTraceAsString());
			return $AllProducts;
		}
	}

	static function GetAllProducts1($Filters = array())
	{
		$AllProducts = array();

		try
		{
			$DBConnObject = new DBConnect();
			$Conditions = array();

			if (count($Filters) > 0)
			{

				if (isset($Filters['ProductCategoryID']) && !empty($Filters['ProductCategoryID']))
				{
					
				}

				
			}

			$QueryString = '';
			if (count($Conditions) > 0)
			{
				$QueryString = ' WHERE ' . implode(' AND ', $Conditions);
			}

			$RSSearch = $DBConnObject->Prepare('
													' . $QueryString . '
        											;');
			$RSSearch->Execute();

			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllProducts;
			}

			while ($SearchRow = $RSSearch->FetchRow())
			{
				
			}

			return $AllProducts;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException::Product::GetAllProducts(). Stack Trace: ' . $e->getTraceAsString());
			return $AllProducts;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception: Product::GetAllProducts() . Stack Trace: ' . $e->getTraceAsString());
			return $AllProducts;
		}
	}

	static function GetActiveProducts()
	{
		$AllProducts = array();

		try
		{
			$DBConnObject = new DBConnect();

			$RSSearch = $DBConnObject->Prepare('SELECT * FROM aim_products WHERE isActive = 1;');
			$RSSearch->Execute();

			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllProducts;
			}

			while ($SearchRow = $RSSearch->FetchRow())
			{
				$AllProducts[$SearchRow->productID] = $SearchRow->productCategoryID;
			}

			return $AllProducts;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at Product::GetActiveProducts(). Stack Trace: ' . $e->getTraceAsString());
			return $AllProducts;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at Product::GetActiveProducts(). Stack Trace: ' . $e->getTraceAsString());
			return $AllProducts;
		}
	}

	static function GetProductsByProductCategoryID($ProductCategoryID)
	{
		$AllProducts = array();

		try
		{
			$DBConnObject = new DBConnect();

			$RSSearch = $DBConnObject->Prepare('SELECT * FROM aim_products WHERE productCategoryID = :|1;');
			$RSSearch->Execute($ProductCategoryID);

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
			error_log('DEBUG: ApplicationDBException at Product::GetProductsByProductCategoryID(). Stack Trace: ' . $e->getTraceAsString());
			return $AllProducts;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at Product::GetProductsByProductCategoryID(). Stack Trace: ' . $e->getTraceAsString());
			return $AllProducts;
		}
	}

	static function GetAvailabileStockQuantity($ProductID)
	{
		$StockQuantity = 0;

		try
		{
			$DBConnObject = new DBConnect();

			if ($ProductID > 0)
			{
				$RSSearch = $DBConnObject->Prepare('SELECT stockQuantity FROM aim_products WHERE productID = :|1 LIMIT 1;');
				$RSSearch->Execute($ProductID);

				$StockQuantity = $RSSearch->FetchRow()->stockQuantity;

				return $StockQuantity;
			}

			return $StockQuantity;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at Product::GetAvailabileStockQuantity(). Stack Trace: ' . $e->getTraceAsString());
			return $StockQuantity;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at Product::GetAvailabileStockQuantity(). Stack Trace: ' . $e->getTraceAsString());
			return $StockQuantity;
		}
	}

	// END OF STATIC METHODS	//
	// START OF PRIVATE METHODS	//
	private function SaveDetails()
	{
		if ($this->ProductID == 0)
		{
			$RSSave = $this->DBObject->Prepare('INSERT INTO aim_products (productCategoryID, productName, productType, productUnitID, productUnitValue, 
																			productDescription, stockQuantity, isActive, createUserID, createDate)
														VALUES (:|1, :|2, :|3, :|4, :|5, :|6, :|7, :|8, :|9, NOW());');
			$RSSave->Execute($this->ProductCategoryID, $this->ProductName, $this->ProductType, $this->ProductUnitID, $this->ProductUnitValue, $this->ProductDescription, $this->StockQuantity, $this->IsActive, $this->CreateUserID);

			$this->ProductID = $RSSave->LastID;
		}
		else
		{
			$RSUpdate = $this->DBObject->Prepare('UPDATE aim_products
													SET	productCategoryID = :|1,
														productName = :|2,
														productType = :|3,
														productUnitID = :|4,
														productUnitValue = :|5,
														productDescription = :|6,
														stockQuantity = :|7,
														isActive = :|8
													WHERE productID = :|9 LIMIT 1;');
			$RSUpdate->Execute($this->ProductCategoryID, $this->ProductName, $this->ProductType, $this->ProductUnitID, $this->ProductUnitValue, $this->ProductDescription, $this->StockQuantity, $this->IsActive, $this->ProductID);
		}

		return true;
	}

	private function RemoveProduct()
	{
		if (!isset($this->ProductID))
		{
			throw new ApplicationARException('', APP_AR_ERROR_DELETE_WITHOUT_ID);
		}

		$RSDeleteProduct = $this->DBObject->Prepare('DELETE FROM aim_products WHERE productID = :|1 LIMIT 1;');
		$RSDeleteProduct->Execute($this->ProductID);
	}

	private function GetProductByID()
	{
		$RSProduct = $this->DBObject->Prepare('SELECT * FROM aim_products WHERE productID = :|1 LIMIT 1;');
		$RSProduct->Execute($this->ProductID);

		$ProductRow = $RSProduct->FetchRow();

		$this->SetAttributesFromDB($ProductRow);
	}

	private function SetAttributesFromDB($ProductRow)
	{
		$this->ProductID = $ProductRow->productID;
		$this->ProductCategoryID = $ProductRow->productCategoryID;

		$this->ProductName = $ProductRow->productName;
		$this->ProductType = $ProductRow->productType;
		$this->ProductUnitID = $ProductRow->productUnitID;

		$this->ProductUnitValue = $ProductRow->productUnitValue;
		$this->ProductDescription = $ProductRow->productDescription;
		$this->StockQuantity = $ProductRow->stockQuantity;

		$this->IsActive = $ProductRow->isActive;
		$this->CreateUserID = $ProductRow->createUserID;
		$this->CreateDate = $ProductRow->createDate;
	}

}

?>
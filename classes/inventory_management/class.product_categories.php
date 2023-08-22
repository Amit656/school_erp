<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.db_connect.php');
error_reporting(E_ALL);

class ProductCategory
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	
	private $ProductCategoryID;
	private $ParentCategoryID;
	private $ProductCategoryName;

	private $IsActive;
	private $CreateUserID;
	private $CreateDate;
	
	// PUBLIC METHODS START HERE	//
	public function __construct($ProductCategoryID = 0)
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;
		
		if($ProductCategoryID != 0)
		{
			$this->ProductCategoryID = $ProductCategoryID;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetProductCategoryByID();
		}
		else
		{
			//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
			$this->ProductCategoryID = 0;
			$this->ParentCategoryID = 0;
			$this->ProductCategoryName = '';

			$this->IsActive = 0;
			$this->CreateUserID = 0;
			$this->CreateDate = '0000-00-00 00:00:00';
		}
	}
	
	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetProductCategoryID()
	{
		return $this->ProductCategoryID;
	}
	
	public function GetParentCategoryID()
	{
		return $this->ParentCategoryID;
	}
	public function SetParentCategoryID($ParentCategoryID)
	{
		$this->ParentCategoryID = $ParentCategoryID;
	}
	
	public function GetProductCategoryName()
	{
		return $this->ProductCategoryName;
	}
	public function SetProductCategoryName($ProductCategoryName)
	{
		$this->ProductCategoryName = $ProductCategoryName;
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
			$this->RemoveProductCategory();
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
			$RSTotal = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM role_group_roles WHERE productCategoryID = :|1;');
			$RSTotal->Execute($this->ProductCategoryID);
	
			if ($RSTotal->FetchRow()->totalRecords > 0) 
			{
				return true;
			}
			
			return false;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException: ProductCategory::CheckDependencies(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception: ProductCategory::CheckDependencies(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
	}

	public function RecordExists()
	{
		try
		{
			$QueryString = '';
			
			if ($this->ProductCategoryID > 0)
			{
				$QueryString = ' AND productCategoryID != ' . $this->DBObject->RealEscapeVariable($this->ProductCategoryID);
			}

			$RSTotal = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM aim_product_categories WHERE productCategoryName = :|1' . $QueryString . ';');
			$RSTotal->Execute($this->ProductCategoryName);
			
			if ($RSTotal->FetchRow()->totalRecords > 0)
			{
				return true;
			}
			
			return false;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at ProductCategory::RecordExists(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at ProductCategory::RecordExists(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
	}

	// END OF PUBLIC METHODS	//
	
	// START OF STATIC METHODS	//
	static function GetAllProductCategories($ParentCategoryID)
    { 
		$AllProductCategories = array();
		
    	try
        {	
        	$DBConnObject = new DBConnect();

        	$QueryString = '';
        	if ($ParentCategoryID > 0)
        	{
        		$QueryString = ' WHERE apcat.productCategoryID = ' . $DBConnObject->RealEscapeVariable($ParentCategoryID);
        	}
        	$RSSearch = $DBConnObject->Prepare('SELECT apc.*, apcat.productCategoryName AS parentProductCategoryName, u.userName AS createUserName 
    											FROM aim_product_categories apc 
												LEFT JOIN aim_product_categories apcat ON apc.parentCategoryID = apcat.productCategoryID 
												INNER JOIN users u ON apc.createUserID = u.userID 
												' . $QueryString . '
    											ORDER BY apc.productCategoryName;');
            $RSSearch->Execute();

            if ($RSSearch->Result->num_rows <= 0)
            {
                return $AllProductCategories;
            }

            while($SearchRow = $RSSearch->FetchRow())
            {
                $AllProductCategories[$SearchRow->productCategoryID]['ProductCategoryID'] = $SearchRow->productCategoryID;
                $AllProductCategories[$SearchRow->productCategoryID]['ParentCategoryID'] = $SearchRow->parentCategoryID;
                $AllProductCategories[$SearchRow->productCategoryID]['ParentProductCategoryName'] = $SearchRow->parentProductCategoryName;
                $AllProductCategories[$SearchRow->productCategoryID]['ProductCategoryName'] = $SearchRow->productCategoryName;

				$AllProductCategories[$SearchRow->productCategoryID]['IsActive'] = $SearchRow->isActive;
                $AllProductCategories[$SearchRow->productCategoryID]['CreateUserID'] = $SearchRow->createUserID;
                $AllProductCategories[$SearchRow->productCategoryID]['CreateUserName'] = $SearchRow->createUserName;

                $AllProductCategories[$SearchRow->productCategoryID]['CreateDate'] = $SearchRow->createDate;
           }
            
            return $AllProductCategories;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException::ProductCategory::GetAllProductCategories(). Stack Trace: '. $e->getTraceAsString());
            return $AllProductCategories;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception: ProductCategory::GetAllProductCategories() . Stack Trace: '. $e->getTraceAsString());
            return $AllProductCategories;
        }
    }

    static function GetActiveProductCategories()
	{
		$AllProductCategories = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT * FROM aim_product_categories WHERE isActive = 1;');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllProductCategories;
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$AllProductCategories[$SearchRow->productCategoryID] = $SearchRow->productCategoryName;
			}
			
			return $AllProductCategories;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at ProductCategory::GetActiveProductCategories(). Stack Trace: ' . $e->getTraceAsString());
			return $AllProductCategories;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at ProductCategory::GetActiveProductCategories(). Stack Trace: ' . $e->getTraceAsString());
			return $AllProductCategories;
		}		
	}

	static function GetActiveFirstLevelProductCategory()
	{
		$AllProductCategories = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT * FROM aim_product_categories WHERE parentCategoryID = 0 AND isActive = 1;');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllProductCategories;
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$AllProductCategories[$SearchRow->productCategoryID] = $SearchRow->productCategoryName;
			}
			
			return $AllProductCategories;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at ProductCategory::GetActiveFirstLevelProductCategory(). Stack Trace: ' . $e->getTraceAsString());
			return $AllProductCategories;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at ProductCategory::GetActiveFirstLevelProductCategory(). Stack Trace: ' . $e->getTraceAsString());
			return $AllProductCategories;
		}		
	}
    
	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//
	private function SaveDetails()
	{
		if ($this->ProductCategoryID == 0)
		{
			$RSSave = $this->DBObject->Prepare('INSERT INTO aim_product_categories (parentCategoryID, productCategoryName, isActive, createUserID, createDate)
														VALUES (:|1, :|2, :|3, :|4, NOW());');
			$RSSave->Execute($this->ParentCategoryID, $this->ProductCategoryName, $this->IsActive, $this->CreateUserID);

			$this->ProductCategoryID = $RSSave->LastID;
		}
		else
		{
			$RSUpdate = $this->DBObject->Prepare('UPDATE aim_product_categories
													SET	parentCategoryID = :|1,
														productCategoryName = :|2,
														isActive = :|3
													WHERE productCategoryID = :|4 LIMIT 1;');
			$RSUpdate->Execute($this->ParentCategoryID, $this->ProductCategoryName, $this->IsActive, $this->ProductCategoryID);
		}
		
		return true;
	}

	private function RemoveProductCategory()
	{
		if(!isset($this->ProductCategoryID)) 
		{
    		throw new ApplicationARException('', APP_AR_ERROR_DELETE_WITHOUT_ID);
		}
		
		$RSDeleteProductCategory = $this->DBObject->Prepare('DELETE FROM aim_product_categories WHERE productCategoryID = :|1 LIMIT 1;');
		$RSDeleteProductCategory->Execute($this->ProductCategoryID);				
	}
	
	private function GetProductCategoryByID()
	{
		$RSProductCategory = $this->DBObject->Prepare('SELECT * FROM aim_product_categories WHERE productCategoryID = :|1 LIMIT 1;');
		$RSProductCategory->Execute($this->ProductCategoryID);
		
		$ProductCategoryRow = $RSProductCategory->FetchRow();
		
		$this->SetAttributesFromDB($ProductCategoryRow);				
	}
	
	private function SetAttributesFromDB($ProductCategoryRow)
	{
		$this->ProductCategoryID = $ProductCategoryRow->productCategoryID;
		$this->ParentCategoryID = $ProductCategoryRow->parentCategoryID;
		$this->ProductCategoryName = $ProductCategoryRow->productCategoryName;

		$this->IsActive = $ProductCategoryRow->isActive;
		$this->CreateUserID = $ProductCategoryRow->createUserID;
		$this->CreateDate = $ProductCategoryRow->createDate;
	}	
}
?>
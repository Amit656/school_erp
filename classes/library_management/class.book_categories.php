<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.db_connect.php');
error_reporting(E_ALL);

class BookCategory
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	
	private $BookCategoryID;
	private $ParentCategoryID;
	private $BookCategoryName;

	private $IsActive;
	private $CreateUserID;
	private $CreateDate;
	
	// PUBLIC METHODS START HERE	//
	public function __construct($BookCategoryID = 0)
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;
		
		if($BookCategoryID != 0)
		{
			$this->BookCategoryID = $BookCategoryID;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetBookCategoryByID();
		}
		else
		{
			//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
			$this->BookCategoryID = 0;
			$this->ParentCategoryID = 0;
			$this->BookCategoryName = '';

			$this->IsActive = 0;
			$this->CreateUserID = 0;
			$this->CreateDate = '0000-00-00 00:00:00';
		}
	}
	
	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetBookCategoryID()
	{
		return $this->BookCategoryID;
	}
	
	public function GetParentCategoryID()
	{
		return $this->ParentCategoryID;
	}
	public function SetParentCategoryID($ParentCategoryID)
	{
		$this->ParentCategoryID = $ParentCategoryID;
	}

	public function GetBookCategoryName()
	{
		return $this->BookCategoryName;
	}
	public function SetBookCategoryName($BookCategoryName)
	{
		$this->BookCategoryName = $BookCategoryName;
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
            $this->RemoveBookCategory();
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
            $RSBookCategoryCount = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM alm_books WHERE bookCategoryID = :|1;');
            $RSBookCategoryCount->Execute($this->BookCategoryID);

            if ($RSBookCategoryCount->FetchRow()->totalRecords > 0) 
            {
                return true;
            }

            return false;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at BookCategory::CheckDependencies(). Stack Trace: ' . $e->getTraceAsString());
            return true;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: ApplicationDBException at BookCategory::CheckDependencies(). Stack Trace: ' . $e->getTraceAsString());
            return true;
        }       
    }
		
	// END OF PUBLIC METHODS	//
	
	// START OF STATIC METHODS	//
	
	static function GetActiveParentCategories()
	{
		$ActiveParentBookCategories = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT * FROM alm_book_categories WHERE isActive = 1 AND parentCategoryID = 0;');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $ActiveParentBookCategories;
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$ActiveParentBookCategories[$SearchRow->bookCategoryID] = $SearchRow->bookCategoryName;
			}
			
			return $ActiveParentBookCategories;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at BookCategory::GetActiveParentCategories(). Stack Trace: ' . $e->getTraceAsString());
			return $ActiveParentBookCategories;
		}
		catch (Exception $e) 
		{
			error_log('DEBUG: Exception at BookCategory::GetActiveParentCategories(). Stack Trace: ' . $e->getTraceAsString());
			return $ActiveParentBookCategories;
		}		
	}

	static function GetActiveSubCategories($ParentCategoryID)
	{
		$ActiveSubBookCategories = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT * FROM alm_book_categories WHERE isActive = 1 AND parentCategoryID = :|1;');
			$RSSearch->Execute($ParentCategoryID);
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $ActiveSubBookCategories;
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$ActiveSubBookCategories[$SearchRow->bookCategoryID] = $SearchRow->bookCategoryName;
			}
			
			return $ActiveSubBookCategories;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at BookCategory::GetActiveSubCategories(). Stack Trace: ' . $e->getTraceAsString());
			return $ActiveSubBookCategories;
		}
		catch (Exception $e) 
		{
			error_log('DEBUG: Exception at BookCategory::GetActiveSubCategories(). Stack Trace: ' . $e->getTraceAsString());
			return $ActiveSubBookCategories;
		}		
	}

	static function SearchBookCategories(&$TotalRecords = 0, $GetTotalsOnly = false, $Filters = array(), $Start = 0, $Limit = 100)
	{
		$AllBookCategories = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$Conditions = array();
			
			if (count($Filters) > 0)
			{	
				if (!empty($Filters['CategoryID']))
				{
					if ($Filters['CategoryID'] == 1) //active records
					{
						$Conditions[] = 'abc.parentCategoryID = 0';
					}
					else if ($Filters['CategoryID'] == 2) //non active records
					{
						$Conditions[] = 'abc.parentCategoryID = 1';
					}

				}			
				if (!empty($Filters['ParentCategoryID']))
				{
					$Conditions[] = 'abc.parentCategoryID = '.$DBConnObject->RealEscapeVariable($Filters['ParentCategoryID']);
				}
				
				if (!empty($Filters['ActiveStatus']))
				{
					if ($Filters['ActiveStatus'] == 1) //active records
					{
						$Conditions[] = 'abc.isActive = 1';
					}
					else if ($Filters['ActiveStatus'] == 2) //non active records
					{
						$Conditions[] = 'abc.isActive = 0';
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
													FROM alm_book_categories abc
													INNER JOIN users u ON abc.createUserID = u.userID 
													'. $QueryString .';');
				$RSTotal->Execute();
				
				$TotalRecords = $RSTotal->FetchRow()->totalRecords;
				return;
			}
			
			$RSSearch = $DBConnObject->Prepare('SELECT abc.*, u.userName AS createUserName 
												FROM alm_book_categories abc
												INNER JOIN users u ON abc.createUserID = u.userID 
												'. $QueryString .' 
												ORDER BY abc.bookCategoryName LIMIT ' . (int) $Start . ', ' . (int) $Limit . ';');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllBookCategories; 
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$AllBookCategories[$SearchRow->bookCategoryID]['ParentCategoryID'] = $SearchRow->parentCategoryID;
				$AllBookCategories[$SearchRow->bookCategoryID]['BookCategoryName'] = $SearchRow->bookCategoryName;
				
				$AllBookCategories[$SearchRow->bookCategoryID]['IsActive'] = $SearchRow->isActive;
				$AllBookCategories[$SearchRow->bookCategoryID]['CreateUserID'] = $SearchRow->createUserID;
				$AllBookCategories[$SearchRow->bookCategoryID]['CreateUserName'] = $SearchRow->createUserName;
				$AllBookCategories[$SearchRow->bookCategoryID]['CreateDate'] = $SearchRow->createDate;
			}
			
			return $AllBookCategories;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at BookCategory::SearchBookCategories(). Stack Trace: ' . $e->getTraceAsString());
			return $AllBookCategories;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at BookCategory::SearchBookCategories(). Stack Trace: ' . $e->getTraceAsString());
			return $AllBookCategories;
		}
	}
	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//
	private function SaveDetails()
	{
		if ($this->BookCategoryID == 0)
		{
			$RSSave = $this->DBObject->Prepare('INSERT INTO alm_book_categories (parentCategoryID, bookCategoryName, isActive, createUserID, createDate)
														VALUES (:|1, :|2, :|3, :|4, NOW());');
		
			$RSSave->Execute($this->ParentCategoryID, $this->BookCategoryName, $this->IsActive, $this->CreateUserID);
			
			$this->BookCategoryID = $RSSave->LastID;
		}
		else
		{
			$RSUpdate = $this->DBObject->Prepare('UPDATE alm_book_categories
													SET	parentCategoryID = :|1,
														bookCategoryName = :|2,
														isActive = :|3
													WHERE bookCategoryID = :|4;');
													
			$RSUpdate->Execute($this->ParentCategoryID, $this->BookCategoryName, $this->IsActive, $this->BookCategoryID);
		}
		
		return true;
	}
	
	private function RemoveBookCategory()
    {
        if(!isset($this->BookCategoryID)) 
        {
            throw new ApplicationARException('', APP_AR_ERROR_DELETE_WITHOUT_ID);
        }
        
        $RSDeleteBookCategory = $this->DBObject->Prepare('DELETE FROM alm_book_categories WHERE bookCategoryID = :|1 LIMIT 1;');
        $RSDeleteBookCategory->Execute($this->BookCategoryID);  

        return true;              
    }

	private function GetBookCategoryByID()
	{
		$RSBookCategory = $this->DBObject->Prepare('SELECT * FROM alm_book_categories WHERE bookCategoryID = :|1 LIMIT 1;');
		$RSBookCategory->Execute($this->BookCategoryID);
		
		$BookCategoryRow = $RSBookCategory->FetchRow();
		
		$this->SetAttributesFromDB($BookCategoryRow);				
	}
	
	private function SetAttributesFromDB($BookCategoryRow)
	{
		$this->BookCategoryID = $BookCategoryRow->bookCategoryID;
		$this->ParentCategoryID = $BookCategoryRow->parentCategoryID;
		$this->BookCategoryName = $BookCategoryRow->bookCategoryName;

		$this->IsActive = $BookCategoryRow->isActive;
		$this->CreateUserID = $BookCategoryRow->createUserID;
		$this->CreateDate = $BookCategoryRow->createDate;
	}	
}
?>
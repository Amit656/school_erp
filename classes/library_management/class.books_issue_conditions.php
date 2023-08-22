<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.db_connect.php');
error_reporting(E_ALL);

class BooksIssueCondition
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	
	private $BooksIssueConditionID;
	private $ConditionFor;
	private $Quota;
	private $DefaultDuration;

	private $LateFineType;
	private $FineAmount;

	private $CreateUserID;
	private $CreateDate;
	
	// PUBLIC METHODS START HERE	//
	public function __construct($BooksIssueConditionID = 0)
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;
		
		if($BooksIssueConditionID != 0)
		{
			$this->BooksIssueConditionID = $BooksIssueConditionID;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetBooksIssueConditionByID();
		}
		else
		{
			//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
			$this->BooksIssueConditionID = 0;
			$this->ConditionFor = '';
			$this->Quota = 0;
			$this->DefaultDuration = 0;

			$this->LateFineType = '';
			$this->FineAmount = 0;

			$this->CreateUserID = 0;
			$this->CreateDate = '0000-00-00 00:00:00';
		}
	}
	
	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetBooksIssueConditionID()
	{
		return $this->BooksIssueConditionID;
	}
	
	public function GetConditionFor()
	{
		return $this->ConditionFor;
	}
	public function SetConditionFor($ConditionFor)
	{
		$this->ConditionFor = $ConditionFor;
	}

	public function GetQuota()
	{
		return $this->Quota;
	}
	public function SetQuota($Quota)
	{
		$this->Quota = $Quota;
	}

	public function GetDefaultDuration()
	{
		return $this->DefaultDuration;
	}
	public function SetDefaultDuration($DefaultDuration)
	{
		$this->DefaultDuration = $DefaultDuration;
	}

	public function GetLateFineType()
	{
		return $this->LateFineType;
	}
	public function SetLateFineType($LateFineType)
	{
		$this->LateFineType = $LateFineType;
	}

	public function GetFineAmount()
	{
		return $this->FineAmount;
	}
	public function SetFineAmount($FineAmount)
	{
		$this->FineAmount = $FineAmount;
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
            $this->RemoveBooksIssueCondition();
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
            /*$RSBooksIssueConditionCount = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM ahm_rooms WHERE booksIssueConditionID = :|1;');
            $RSBooksIssueConditionCount->Execute($this->BooksIssueConditionID);

            if ($RSBooksIssueConditionCount->FetchRow()->totalRecords > 0) 
            {
                return true;
            }*/

            return false;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at BooksIssueCondition::CheckDependencies(). Stack Trace: ' . $e->getTraceAsString());
            return true;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: ApplicationDBException at BooksIssueCondition::CheckDependencies(). Stack Trace: ' . $e->getTraceAsString());
            return true;
        }       
    }
	
	public function RecordExists()
	{
		try
		{
			$QueryString = '';
			if ($this->BooksIssueConditionID > 0)
			{
				$QueryString = ' AND booksIssueConditionID != ' . $this->DBObject->RealEscapeVariable($this->BooksIssueConditionID);
			}

			$RSTotal = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM alm_books_issue_conditions WHERE conditionFor = :|1' . $QueryString . ';');
			$RSTotal->Execute($this->ConditionFor);
			
			if ($RSTotal->FetchRow()->totalRecords > 0)
			{
				return true;
			}
			
			return false;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at BooksIssueCondition::RecordExists(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at BooksIssueCondition::RecordExists(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
	}	
	// END OF PUBLIC METHODS	//
	
	// START OF STATIC METHODS	//
	
	static function GetAllBooksIssueConditions()
	{
		$AllBooksIssueConditions = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT abic.*, u.userName AS createUserName FROM alm_books_issue_conditions abic
												INNER JOIN users u ON abic.createUserID = u.userID 
												ORDER BY abic.conditionFor;');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllBooksIssueConditions; 
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$AllBooksIssueConditions[$SearchRow->booksIssueConditionID]['ConditionFor'] = $SearchRow->conditionFor;
				$AllBooksIssueConditions[$SearchRow->booksIssueConditionID]['Quota'] = $SearchRow->quota;				
				$AllBooksIssueConditions[$SearchRow->booksIssueConditionID]['DefaultDuration'] = $SearchRow->defaultDuration;

				$AllBooksIssueConditions[$SearchRow->booksIssueConditionID]['LateFineType'] = $SearchRow->lateFineType;				
				$AllBooksIssueConditions[$SearchRow->booksIssueConditionID]['FineAmount'] = $SearchRow->fineAmount;				

				$AllBooksIssueConditions[$SearchRow->booksIssueConditionID]['CreateUserID'] = $SearchRow->createUserID;
				$AllBooksIssueConditions[$SearchRow->booksIssueConditionID]['CreateUserName'] = $SearchRow->createUserName;
				$AllBooksIssueConditions[$SearchRow->booksIssueConditionID]['CreateDate'] = $SearchRow->createDate;
			}
			
			return $AllBooksIssueConditions;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at BooksIssueCondition::GetAllBooksIssueConditions(). Stack Trace: ' . $e->getTraceAsString());
			return $AllBooksIssueConditions;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at BooksIssueCondition::GetAllBooksIssueConditions(). Stack Trace: ' . $e->getTraceAsString());
			return $AllBooksIssueConditions;
		}
	}

	static function GetBooksIssueConditions($UserType)
	{
		$BooksIssueConditions = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT * FROM alm_books_issue_conditions WHERE conditionFor = :|1;');
			$RSSearch->Execute($UserType);
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $BooksIssueConditions;
			}

			$SearchRow = $RSSearch->FetchRow();

			$BooksIssueConditions[$SearchRow->booksIssueConditionID]['Quota'] = $SearchRow->quota;
			$BooksIssueConditions[$SearchRow->booksIssueConditionID]['DefaultDuration'] = $SearchRow->defaultDuration;
			$BooksIssueConditions[$SearchRow->booksIssueConditionID]['LateFineType'] = $SearchRow->lateFineType;
			$BooksIssueConditions[$SearchRow->booksIssueConditionID]['FineAmount'] = $SearchRow->fineAmount;				
			
			return $BooksIssueConditions;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at BooksIssueCondition::GetBooksIssueConditions(). Stack Trace: ' . $e->getTraceAsString());
			return $BooksIssueConditions;
		}
		catch (Exception $e) 
		{
			error_log('DEBUG: Exception at BooksIssueCondition::GetBooksIssueConditions(). Stack Trace: ' . $e->getTraceAsString());
			return $BooksIssueConditions;
		}		
	}
	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//
	private function SaveDetails()
	{
		if ($this->BooksIssueConditionID == 0)
		{
			$RSSave = $this->DBObject->Prepare('INSERT INTO alm_books_issue_conditions (conditionFor, quota, defaultDuration, lateFineType, fineAmount, createUserID, createDate)
														VALUES (:|1, :|2, :|3, :|4, :|5, :|6, NOW());');
		
			$RSSave->Execute($this->ConditionFor, $this->Quota, $this->DefaultDuration, $this->LateFineType, $this->FineAmount, $this->CreateUserID);
			
			$this->BooksIssueConditionID = $RSSave->LastID;
		}
		else
		{
			$RSUpdate = $this->DBObject->Prepare('UPDATE alm_books_issue_conditions
													SET	conditionFor = :|1,
														quota = :|2,
														defaultDuration = :|3,
														lateFineType = :|4,
														fineAmount = :|5
													WHERE booksIssueConditionID = :|6;');
													
			$RSUpdate->Execute($this->ConditionFor, $this->Quota, $this->DefaultDuration, $this->LateFineType, $this->FineAmount, $this->BooksIssueConditionID);
		}
		
		return true;
	}
	
	private function RemoveBooksIssueCondition()
    {
        if(!isset($this->BooksIssueConditionID)) 
        {
            throw new ApplicationARException('', APP_AR_ERROR_DELETE_WITHOUT_ID);
        }
        
        $RSDeleteBooksIssueCondition = $this->DBObject->Prepare('DELETE FROM alm_books_issue_conditions WHERE booksIssueConditionID = :|1 LIMIT 1;');
        $RSDeleteBooksIssueCondition->Execute($this->BooksIssueConditionID);  

        return true;              
    }

	private function GetBooksIssueConditionByID()
	{
		$RSBooksIssueCondition = $this->DBObject->Prepare('SELECT * FROM alm_books_issue_conditions WHERE booksIssueConditionID = :|1 LIMIT 1;');
		$RSBooksIssueCondition->Execute($this->BooksIssueConditionID);
		
		$BooksIssueConditionRow = $RSBooksIssueCondition->FetchRow();
		
		$this->SetAttributesFromDB($BooksIssueConditionRow);				
	}
	
	private function SetAttributesFromDB($BooksIssueConditionRow)
	{
		$this->BooksIssueConditionID = $BooksIssueConditionRow->booksIssueConditionID;
		$this->ConditionFor = $BooksIssueConditionRow->conditionFor;
		$this->Quota = $BooksIssueConditionRow->quota;
		$this->DefaultDuration = $BooksIssueConditionRow->defaultDuration;

		$this->LateFineType = $BooksIssueConditionRow->lateFineType;
		$this->FineAmount = $BooksIssueConditionRow->fineAmount;

		$this->CreateUserID = $BooksIssueConditionRow->createUserID;
		$this->CreateDate = $BooksIssueConditionRow->createDate;
	}	
}
?>
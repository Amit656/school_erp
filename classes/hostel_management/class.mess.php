<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.db_connect.php');
error_reporting(E_ALL);

class Mess
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	
	private $MessID;
	private $MessName;
	private $MessType;

	private $MonthlyFee;
	private $QuarterlyFee;
	private $SemiAnnualFee;
	private $AnnualFee;

	private $IsActive;
	private $CreateUserID;
	private $CreateDate;
	
	// PUBLIC METHODS START HERE	//
	public function __construct($MessID = 0)
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;
		
		if($MessID != 0)
		{
			$this->MessID = $MessID;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetMessByID();
		}
		else
		{
			//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
			$this->MessID = 0;
			$this->MessName = '';
			$this->MessType = '';

			$this->MonthlyFee = 0;
			$this->QuarterlyFee = 0;
			$this->SemiAnnualFee = 0;
			$this->AnnualFee = 0;

			$this->IsActive = 0;
			$this->CreateUserID = 0;
			$this->CreateDate = '0000-00-00 00:00:00';
		}
	}
	
	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetMessID()
	{
		return $this->MessID;
	}
	
	public function GetMessName()
	{
		return $this->MessName;
	}
	public function SetMessName($MessName)
	{
		$this->MessName = $MessName;
	}

	public function GetMessType()
	{
		return $this->MessType;
	}
	public function SetMessType($MessType)
	{
		$this->MessType = $MessType;
	}
	
	public function GetMonthlyFee()
	{
		return $this->MonthlyFee;
	}
	public function SetMonthlyFee($MonthlyFee)
	{
		$this->MonthlyFee = $MonthlyFee;
	}
	
	public function GetQuarterlyFee()
	{
		return $this->QuarterlyFee;
	}
	public function SetQuarterlyFee($QuarterlyFee)
	{
		$this->QuarterlyFee = $QuarterlyFee;
	}
	
	public function GetSemiAnnualFee()
	{
		return $this->SemiAnnualFee;
	}
	public function SetSemiAnnualFee($SemiAnnualFee)
	{
		$this->SemiAnnualFee = $SemiAnnualFee;
	}
	
	public function GetAnnualFee()
	{
		return $this->AnnualFee;
	}
	public function SetAnnualFee($AnnualFee)
	{
		$this->AnnualFee = $AnnualFee;
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
            $this->RemoveMess();
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
            $RSMessCount = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM ahm_student_hostel_allotment WHERE messID = :|1;');
            $RSMessCount->Execute($this->MessID);

            if ($RSMessCount->FetchRow()->totalRecords > 0) 
            {
                return true;
            }

            return false;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at Mess::CheckDependencies(). Stack Trace: ' . $e->getTraceAsString());
            return true;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: ApplicationDBException at Mess::CheckDependencies(). Stack Trace: ' . $e->getTraceAsString());
            return true;
        }       
    }
		
	// END OF PUBLIC METHODS	//
	
	// START OF STATIC METHODS	//
	
	static function GetAllMess()
	{
		$AllMess = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT am.*, u.userName AS createUserName FROM ahm_mess am
												INNER JOIN users u ON am.createUserID = u.userID 
												ORDER BY am.messName;');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllMess; 
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$AllMess[$SearchRow->messID]['MessName'] = $SearchRow->messName;
				$AllMess[$SearchRow->messID]['MessType'] = $SearchRow->messType;

				$AllMess[$SearchRow->messID]['MonthlyFee'] = $SearchRow->monthlyFee;
				$AllMess[$SearchRow->messID]['QuarterlyFee'] = $SearchRow->quarterlyFee;
				$AllMess[$SearchRow->messID]['SemiAnnualFee'] = $SearchRow->semiAnnualFee;
				$AllMess[$SearchRow->messID]['AnnualFee'] = $SearchRow->annualFee;

				$AllMess[$SearchRow->messID]['IsActive'] = $SearchRow->isActive;
				$AllMess[$SearchRow->messID]['CreateUserID'] = $SearchRow->createUserID;
				$AllMess[$SearchRow->messID]['CreateUserName'] = $SearchRow->createUserName;
				$AllMess[$SearchRow->messID]['CreateDate'] = $SearchRow->createDate;
			}
			
			return $AllMess;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at Mess::GetAllMess(). Stack Trace: ' . $e->getTraceAsString());
			return $AllMess;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at Mess::GetAllMess(). Stack Trace: ' . $e->getTraceAsString());
			return $AllMess;
		}
	}

	static function GetMessByType($MessType)
	{
		$AllMess = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT * FROM ahm_mess WHERE messType = :|1 AND isActive = 1;');
			$RSSearch->Execute($MessType);
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllMess;
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$AllMess[$SearchRow->messID] = $SearchRow->messName;
			}
			
			return $AllMess;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at Mess::GetMessByType(). Stack Trace: ' . $e->getTraceAsString());
			return $AllMess;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at Mess::GetMessByType(). Stack Trace: ' . $e->getTraceAsString());
			return $AllMess;
		}		
	}
	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//
	private function SaveDetails()
	{
		if ($this->MessID == 0)
		{
			$RSSave = $this->DBObject->Prepare('INSERT INTO ahm_mess (messName, messType, monthlyFee, quarterlyFee, semiAnnualFee, annualFee, isActive, createUserID, createDate)
														VALUES (:|1, :|2, :|3, :|4, :|5, :|6, :|7, :|8, NOW());');
		
			$RSSave->Execute($this->MessName, $this->MessType, $this->MonthlyFee, $this->QuarterlyFee, $this->SemiAnnualFee, $this->AnnualFee, $this->IsActive, $this->CreateUserID);
			
			$this->MessID = $RSSave->LastID;
		}
		else
		{
			$RSUpdate = $this->DBObject->Prepare('UPDATE ahm_mess
													SET	messName = :|1,
														messType = :|2,
														monthlyFee = :|3,
														quarterlyFee = :|4,
														semiAnnualFee = :|5,
														annualFee = :|6,
														isActive = :|7
													WHERE messID = :|8;');
													
			$RSUpdate->Execute($this->MessName, $this->MessType, $this->MonthlyFee, $this->QuarterlyFee, $this->SemiAnnualFee, $this->AnnualFee, $this->IsActive, $this->MessID);
		}
		
		return true;
	}

	private function RemoveMess()
    {
        if(!isset($this->MessID)) 
        {
            throw new ApplicationARException('', APP_AR_ERROR_DELETE_WITHOUT_ID);
        }
        
        $RSDeleteMess = $this->DBObject->Prepare('DELETE FROM ahm_mess WHERE messID = :|1 LIMIT 1;');
        $RSDeleteMess->Execute($this->MessID);  

        return true;              
    }
	
	private function GetMessByID()
	{
		$RSMess = $this->DBObject->Prepare('SELECT * FROM ahm_mess WHERE messID = :|1 LIMIT 1;');
		$RSMess->Execute($this->MessID);
		
		$MessRow = $RSMess->FetchRow();
		
		$this->SetAttributesFromDB($MessRow);				
	}
	
	private function SetAttributesFromDB($MessRow)
	{
		$this->MessID = $MessRow->messID;
		$this->MessName = $MessRow->messName;
		$this->MessType = $MessRow->messType;

		$this->MonthlyFee = $MessRow->monthlyFee;
		$this->QuarterlyFee = $MessRow->quarterlyFee;
		$this->SemiAnnualFee = $MessRow->semiAnnualFee;
		$this->AnnualFee = $MessRow->annualFee;

		$this->IsActive = $MessRow->isActive;
		$this->CreateUserID = $MessRow->createUserID;
		$this->CreateDate = $MessRow->createDate;
	}	
}
?>
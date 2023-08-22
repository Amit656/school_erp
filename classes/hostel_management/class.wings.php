<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.db_connect.php');
error_reporting(E_ALL);

class Wing
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	
	private $WingID;
	private $WingName;

	private $WingFor;
	private $IsActive;

	private $CreateUserID;
	private $CreateDate;
	
	// PUBLIC METHODS START HERE	//
	public function __construct($WingID = 0)
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;
		
		if($WingID != 0)
		{
			$this->WingID = $WingID;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetWingByID();
		}
		else
		{
			//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
			$this->WingID = 0;
			$this->WingName = '';

			$this->WingFor = '';
			$this->IsActive = 0;

			$this->CreateUserID = 0;
			$this->CreateDate = '0000-00-00 00:00:00';
		}
	}
	
	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetWingID()
	{
		return $this->WingID;
	}
	
	public function GetWingName()
	{
		return $this->WingName;
	}
	public function SetWingName($WingName)
	{
		$this->WingName = $WingName;
	}

	public function GetWingFor()
	{
		return $this->WingFor;
	}
	public function SetWingFor($WingFor)
	{
		$this->WingFor = $WingFor;
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
            $this->RemoveWing();
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
            $RSWingCount = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM ahm_rooms WHERE wingID = :|1;');
            $RSWingCount->Execute($this->WingID);

            if ($RSWingCount->FetchRow()->totalRecords > 0) 
            {
                return true;
            }

            return false;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at Wing::CheckDependencies(). Stack Trace: ' . $e->getTraceAsString());
            return true;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: ApplicationDBException at Wing::CheckDependencies(). Stack Trace: ' . $e->getTraceAsString());
            return true;
        }       
    }
		
	// END OF PUBLIC METHODS	//
		
	// START OF STATIC METHODS	//
	static function GetAllWings()
	{
		$AllWings = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT aw.*, u.userName AS createUserName FROM ahm_wings aw
												INNER JOIN users u ON aw.createUserID = u.userID 
												ORDER BY aw.wingName;');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllWings; 
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$AllWings[$SearchRow->wingID]['WingName'] = $SearchRow->wingName;

				$AllWings[$SearchRow->wingID]['WingFor'] = $SearchRow->wingFor;
				$AllWings[$SearchRow->wingID]['IsActive'] = $SearchRow->isActive;

				$AllWings[$SearchRow->wingID]['CreateUserID'] = $SearchRow->createUserID;
				$AllWings[$SearchRow->wingID]['CreateUserName'] = $SearchRow->createUserName;
				$AllWings[$SearchRow->wingID]['CreateDate'] = $SearchRow->createDate;
			}
			
			return $AllWings;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at Wing::GetAllWings(). Stack Trace: ' . $e->getTraceAsString());
			return $AllWings;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at Wing::GetAllWings(). Stack Trace: ' . $e->getTraceAsString());
			return $AllWings;
		}
	}

	static function GetActiveWings()
	{
		$ActiveWings = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT * FROM ahm_wings WHERE isActive = 1;');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $ActiveWings;
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$ActiveWings[$SearchRow->wingID] = $SearchRow->wingName . ' ( For ' . $SearchRow->wingFor . ' )';
			}
			
			return $ActiveWings;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at Wing::GetActiveWings(). Stack Trace: ' . $e->getTraceAsString());
			return $ActiveWings;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at Wing::GetActiveWings(). Stack Trace: ' . $e->getTraceAsString());
			return $ActiveWings;
		}		
	}
	
	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//
	private function SaveDetails()
	{
		if ($this->WingID == 0)
		{
			$RSSave = $this->DBObject->Prepare('INSERT INTO ahm_wings (wingName, wingFor, isActive, createUserID, createDate)
														VALUES (:|1, :|2, :|3, :|4, NOW());');
		
			$RSSave->Execute($this->WingName, $this->WingFor, $this->IsActive, $this->CreateUserID);
			
			$this->WingID = $RSSave->LastID;
		}
		else
		{
			$RSUpdate = $this->DBObject->Prepare('UPDATE ahm_wings
													SET	wingName = :|1,
														wingFor = :|2,
														isActive = :|3
													WHERE wingID = :|4;');
													
			$RSUpdate->Execute($this->WingName, $this->WingFor, $this->IsActive, $this->WingID);
		}
		
		return true;
	}

	private function RemoveWing()
    {
        if(!isset($this->WingID)) 
        {
            throw new ApplicationARException('', APP_AR_ERROR_DELETE_WITHOUT_ID);
        }
        
        $RSDeleteWing = $this->DBObject->Prepare('DELETE FROM ahm_wings WHERE wingID = :|1 LIMIT 1;');
        $RSDeleteWing->Execute($this->WingID);  

        return true;              
    }
	
	private function GetWingByID()
	{
		$RSWing = $this->DBObject->Prepare('SELECT * FROM ahm_wings WHERE wingID = :|1 LIMIT 1;');
		$RSWing->Execute($this->WingID);
		
		$WingRow = $RSWing->FetchRow();
		
		$this->SetAttributesFromDB($WingRow);				
	}
	
	private function SetAttributesFromDB($WingRow)
	{
		$this->WingID = $WingRow->wingID;
		$this->WingName = $WingRow->wingName;

		$this->WingFor = $WingRow->wingFor;
		$this->IsActive = $WingRow->isActive;

		$this->CreateUserID = $WingRow->createUserID;
		$this->CreateDate = $WingRow->createDate;
	}	
}
?>
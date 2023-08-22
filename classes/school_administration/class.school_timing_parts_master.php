<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.db_connect.php');
error_reporting(E_ALL);

class SchoolTimingPartsMaster
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	
	private $SchoolTimingPartID;
	private $TimingPart;
	private $PartType;

	private $DefaultDuration;
	private $Priority;

	private $IsActive;
	private $CreateUserID;
	private $CreateDate;
	
	// PUBLIC METHODS START HERE	//
	public function __construct($SchoolTimingPartID = 0)
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;
		
		if($SchoolTimingPartID != 0)
		{
			$this->SchoolTimingPartID = $SchoolTimingPartID;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetSchoolTimingPartsMasterByID();
		}
		else
		{
			//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
			$this->SchoolTimingPartID = 0;
			$this->TimingPart = '';
			$this->PartType = '';
	
			$this->DefaultDuration = 0;
			$this->Priority = 0;

			$this->IsActive = 0;
			$this->CreateUserID = 0;
			$this->CreateDate = '0000-00-00 00:00:00';
		}
	}
	
	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetSchoolTimingPartID()
	{
		return $this->SchoolTimingPartID;
	}
	
	public function GetTimingPart()
	{
		return $this->TimingPart;
	}
	public function SetTimingPart($TimingPart)
	{
		$this->TimingPart = $TimingPart;
	}
		
	public function GetPartType()
	{
		return $this->PartType;
	}
	public function SetPartType($PartType)
	{
		$this->PartType = $PartType;
	}

	public function GetDefaultDuration()
	{
		return $this->DefaultDuration;
	}
	public function SetDefaultDuration($DefaultDuration)
	{
		$this->DefaultDuration = $DefaultDuration;
	}

	public function GetPriority()
	{
		return $this->Priority;
	}
	public function SetPriority($Priority)
	{
		$this->Priority = $Priority;
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
            $this->RemoveSchoolTimingPart();
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
            $RSCount = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM asa_school_session_class_period_timings WHERE schoolTimingPartID = :|1;');
            $RSCount->Execute($this->SchoolTimingPartID);

            if ($RSCount->FetchRow()->totalRecords > 0) 
            {
                return true;
            }

            return false;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException: SchoolTimingPartsMaster::CheckDependencies. Stack Trace: ' . $e->getTraceAsString());
            return false;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception: SchoolTimingPartsMaster::CheckDependencies. Stack Trace: ' . $e->getTraceAsString());
            return false;
        }       
    }

    public function RecordExists()
	{
		try
		{
			$QueryString = '';
			if ($this->SchoolTimingPartID > 0)
			{
				$QueryString = ' AND schoolTimingPartID != ' . $this->DBObject->RealEscapeVariable($this->SchoolTimingPartID);
			}

			$RSTotal = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM asa_school_timing_parts_master WHERE timingPart = :|1' . $QueryString . ';');
			$RSTotal->Execute($this->TimingPart);
			
			if ($RSTotal->FetchRow()->totalRecords > 0)
			{
				return true;
			}
			
			return false;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at SchoolTimingPartsMaster::RecordExists(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at SchoolTimingPartsMaster::RecordExists(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
	}
		
	// END OF PUBLIC METHODS	//
	
	// START OF STATIC METHODS	//
	static function GetPeriodList()
	{
		$PeriodList = array();

        try
        {
            $DBConnObject = new DBConnect();

            $RSSearch = $DBConnObject->Prepare('SELECT * FROM asa_school_timing_parts_master WHERE partType="Class" ORDER BY priority LIMIT 1, 10;');
            $RSSearch->Execute();

            if ($RSSearch->Result->num_rows <= 0)
            {
                return $PeriodList;
            }

            while($SearchRow = $RSSearch->FetchRow())
            {
            	$PeriodList[$SearchRow->schoolTimingPartID]['TimingPart'] = $SearchRow->timingPart;
            	$PeriodList[$SearchRow->schoolTimingPartID]['PartType'] = $SearchRow->partType;
            	$PeriodList[$SearchRow->schoolTimingPartID]['DefaultDuration'] = $SearchRow->defaultDuration;
            	$PeriodList[$SearchRow->schoolTimingPartID]['Priority'] = $SearchRow->priority;
            }

            return $PeriodList;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at SchoolTimingPartsMaster::GetPeriodList(). Stack Trace: '.$e->getTraceAsString());
            return $PeriodList;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception at SchoolTimingPartsMaster::GetPeriodList(). Stack Trace: '.$e->getTraceAsString());
            return $PeriodList;
        }
	}

	static function GetTimingPartDetails()
    {
        $TimingPartDetails = array();

        try
        {
            $DBConnObject = new DBConnect();

            $RSSearch = $DBConnObject->Prepare('SELECT astpm.*, u.userName AS createUserName 
												FROM asa_school_timing_parts_master astpm 
            									INNER JOIN users u ON astpm.createUserID = u.userID 
												ORDER BY astpm.priority;');
            $RSSearch->Execute();

            if ($RSSearch->Result->num_rows <= 0)
            {
                return $TimingPartDetails;
            }

            while($SearchRow = $RSSearch->FetchRow())
            {
            	$TimingPartDetails[$SearchRow->schoolTimingPartID]['TimingPart'] = $SearchRow->timingPart;
            	$TimingPartDetails[$SearchRow->schoolTimingPartID]['PartType'] = $SearchRow->partType;
            	$TimingPartDetails[$SearchRow->schoolTimingPartID]['DefaultDuration'] = $SearchRow->defaultDuration;
            	$TimingPartDetails[$SearchRow->schoolTimingPartID]['Priority'] = $SearchRow->priority;
            	
            	$TimingPartDetails[$SearchRow->schoolTimingPartID]['CreateUserID'] = $SearchRow->createUserID;
            	$TimingPartDetails[$SearchRow->schoolTimingPartID]['CreateUserName'] = $SearchRow->createUserName;
            	$TimingPartDetails[$SearchRow->schoolTimingPartID]['CreateDate'] = $SearchRow->createDate;
            }

            return $TimingPartDetails;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at SchoolTimingPartsMaster::GetTimingPartDetails(). Stack Trace: '.$e->getTraceAsString());
            return $TimingPartDetails;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception at SchoolTimingPartsMaster::GetTimingPartDetails(). Stack Trace: '.$e->getTraceAsString());
            return $TimingPartDetails;
        }
    }
	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//
	private function SaveDetails()
	{
		if ($this->SchoolTimingPartID == 0)
		{
			$RSSave = $this->DBObject->Prepare('INSERT INTO asa_school_timing_parts_master (timingPart, partType, defaultDuration, priority, 
																							isActive, createUserID, createDate)
														VALUES (:|1, :|2, :|3, :|4, :|5, :|6, NOW());');
		
			$RSSave->Execute($this->TimingPart, $this->PartType, $this->DefaultDuration, $this->Priority, 
								$this->IsActive, $this->CreateUserID);
			
			$this->SchoolTimingPartID = $RSSave->LastID;
		}
		else
		{
			$RSUpdate = $this->DBObject->Prepare('UPDATE asa_school_timing_parts_master
													SET	timingPart = :|1,
														partType = :|2,
														defaultDuration = :|3,
														priority = :|4,
														isActive = :|5
													WHERE schoolTimingPartID = :|6 LIMIT 1;');
													
			$RSUpdate->Execute($this->TimingPart, $this->PartType, $this->DefaultDuration, $this->Priority, $this->IsActive, $this->SchoolTimingPartID);
		}
		
		return true;
	}
	
	private function RemoveSchoolTimingPart()
    {
        if (!isset($this->SchoolTimingPartID)) 
        {
            throw new ApplicationARException('', APP_AR_ERROR_DELETE_WITHOUT_ID);
        }
        
        $RSDeleteSchoolTimingPart = $this->DBObject->Prepare('DELETE FROM asa_school_timing_parts_master WHERE schoolTimingPartID = :|1 LIMIT 1;');
        $RSDeleteSchoolTimingPart->Execute($this->SchoolTimingPartID);
    }

	private function GetSchoolTimingPartsMasterByID()
	{
		$RSSchoolTimingPartsMaster = $this->DBObject->Prepare('SELECT * FROM asa_school_timing_parts_master WHERE schoolTimingPartID = :|1 LIMIT 1;');
		$RSSchoolTimingPartsMaster->Execute($this->SchoolTimingPartID);
		
		$SchoolTimingPartsMasterRow = $RSSchoolTimingPartsMaster->FetchRow();
		
		$this->SetAttributesFromDB($SchoolTimingPartsMasterRow);				
	}
	
	private function SetAttributesFromDB($SchoolTimingPartsMasterRow)
	{
		$this->SchoolTimingPartID = $SchoolTimingPartsMasterRow->schoolTimingPartID;
		$this->TimingPart = $SchoolTimingPartsMasterRow->timingPart;
		$this->PartType = $SchoolTimingPartsMasterRow->partType;

		$this->DefaultDuration = $SchoolTimingPartsMasterRow->defaultDuration;
		$this->Priority = $SchoolTimingPartsMasterRow->priority;
		
		$this->IsActive = $SchoolTimingPartsMasterRow->isActive;
		$this->CreateUserID = $SchoolTimingPartsMasterRow->createUserID;
		$this->CreateDate = $SchoolTimingPartsMasterRow->createDate;
	}	
}
?>
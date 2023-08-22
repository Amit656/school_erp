<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.db_connect.php');
error_reporting(E_ALL);

class SchoolSessionClassDaywiseTiming
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	
	private $DaywiseTimingsID;
	private $SchoolSessionID;
	private $ClassID;
	private $DayID;

	private $StartTime;
	private $EndTime;

	private $CreateUserID;
	private $CreateDate;

	private $DayList = array();
	private $ClassList = array();
	private $AllPeriods = array();
	
	// PUBLIC METHODS START HERE	//
	public function __construct($DaywiseTimingsID = 0)
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;
		
		if($DaywiseTimingsID != 0)
		{
			$this->DaywiseTimingsID = $DaywiseTimingsID;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetSchoolSessionClassDaywiseTimingByID();
		}
		else
		{
			//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
			$this->DaywiseTimingsID = 0;
			$this->SchoolSessionID = 0;
			$this->ClassID = 0;
			$this->DayID = 0;

			$this->StartTime = '00:00:00';
			$this->EndTime = '00:00:00';;

			$this->CreateUserID = 0;
			$this->CreateDate = '0000-00-00 00:00:00';

			$this->DayList = array();
			$this->ClassList = array();
			$this->AllPeriods = array();
		}
	}
	
	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetDaywiseTimingsID()
	{
		return $this->DaywiseTimingsID;
	}
	
	public function GetSchoolSessionID()
	{
		return $this->SchoolSessionID;
	}
	public function SetSchoolSessionID($SchoolSessionID)
	{
		$this->SchoolSessionID = $SchoolSessionID;
	}
		
	public function GetClassID()
	{
		return $this->ClassID;
	}
	public function SetClassID($ClassID)
	{
		$this->ClassID = $ClassID;
	}

	public function GetDayID()
	{
		return $this->DayID;
	}
	public function SetDayID($DayID)
	{
		$this->DayID = $DayID;
	}

	public function GetStartTime()
	{
		return $this->StartTime;
	}
	public function SetStartTime($StartTime)
	{
		$this->StartTime = $StartTime;
	}

	public function GetEndTime()
	{
		return $this->EndTime;
	}
	public function SetEndTime($EndTime)
	{
		$this->EndTime = $EndTime;
	}

	public function GetCreateUserID()
	{
		return $this->CreateUserID;
	}
	public function SetCreateUserID($CreateUserID)
	{
		$this->CreateUserID = $CreateUserID;
	}

	public function SetDayList($DayList)
	{
		$this->DayList = $DayList;
	}
	public function SetClassList($ClassList)
	{
		$this->ClassList = $ClassList;
	}
	public function SetAllPeriods($AllPeriods)
	{
		$this->AllPeriods = $AllPeriods;
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
            $this->RemoveDaywiseTiming();
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
            $RSCount = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM asa_school_session_class_period_timings WHERE daywiseTimingsID = :|1;');
            $RSCount->Execute($this->DaywiseTimingsID);

            if ($RSCount->FetchRow()->totalRecords > 0) 
            {
                return true;
            }

            return false;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException: SchoolSessionClassDaywiseTiming::CheckDependencies. Stack Trace: '.$e->getTraceAsString());
            return false;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception: SchoolSessionClassDaywiseTiming::CheckDependencies. Stack Trace: '.$e->getTraceAsString());
            return false;
        }       
    }
		
	// END OF PUBLIC METHODS	//
	
	// START OF STATIC METHODS	//
	static function GetDaywiseTimingDetails()
    {
        $DaywiseTimingDetails = array();

        try
        {
            $DBConnObject = new DBConnect();

            $RSSearch = $DBConnObject->Prepare('SELECT ascdt.*, u.userName AS createUserName  FROM asa_school_session_class_daywise_timings ascdt 
            									INNER JOIN users u ON ascdt.createUserID = u.userID;');
            $RSSearch->Execute();

            if ($RSSearch->Result->num_rows <= 0)
            {
                return $DaywiseTimingDetails;
            }

            while($SearchRow = $RSSearch->FetchRow())
            {
            	$DaywiseTimingDetails[$SearchRow->daywiseTimingsID]['SchoolSessionID'] = $SearchRow->schoolSessionID;
            	$DaywiseTimingDetails[$SearchRow->daywiseTimingsID]['ClassID'] = $SearchRow->classID;
            	$DaywiseTimingDetails[$SearchRow->daywiseTimingsID]['DayID'] = $SearchRow->dayID;
            	
            	$DaywiseTimingDetails[$SearchRow->daywiseTimingsID]['StartTime'] = $SearchRow->startTime;
            	$DaywiseTimingDetails[$SearchRow->daywiseTimingsID]['EndTime'] = $SearchRow->endTime;
            	
            	$DaywiseTimingDetails[$SearchRow->daywiseTimingsID]['CreateUserID'] = $SearchRow->createUserID;
            	$DaywiseTimingDetails[$SearchRow->daywiseTimingsID]['CreateUserName'] = $SearchRow->createUserName;
            	$DaywiseTimingDetails[$SearchRow->daywiseTimingsID]['CreateDate'] = $SearchRow->createDate;
            }

            return $DaywiseTimingDetails;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at SchoolSessionClassDaywiseTiming::GetDaywiseTimingDetails(). Stack Trace: '.$e->getTraceAsString());
            return $DaywiseTimingDetails;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception at SchoolSessionClassDaywiseTiming::GetDaywiseTimingDetails(). Stack Trace: '.$e->getTraceAsString());
            return $DaywiseTimingDetails;
        }
	}
	
	static function GetClassAllPeriodDetails($ClassID)
	{
		$ClassPeriodList = array();

		try
		{
			$DBConnObject = new DBConnect();

            $RSSearch = $DBConnObject->Prepare('SELECT DISTINCT(asscpt.schoolTimingPartID), astpm.timingPart, astpm.partType 
												FROM asa_school_session_class_period_timings asscpt 
												INNER JOIN asa_school_session_class_daywise_timings asscdt ON asscdt.daywiseTimingsID = asscpt.daywiseTimingsID 
												INNER JOIN asa_school_timing_parts_master astpm ON astpm.schoolTimingPartID = asscpt.schoolTimingPartID
												WHERE asscdt.schoolSessionID = (SELECT schoolSessionID FROM asa_session_activation_details WHERE isDecativated = 0 LIMIT 1)
												AND asscdt.classID = :|1;');
            $RSSearch->Execute($ClassID);

            if ($RSSearch->Result->num_rows <= 0)
            {
                return $ClassPeriodList;
            }

            while($SearchRow = $RSSearch->FetchRow())
            {
				$ClassPeriodList[$SearchRow->schoolTimingPartID]['TimingPart'] = $SearchRow->timingPart;
				$ClassPeriodList[$SearchRow->schoolTimingPartID]['PartType'] = $SearchRow->partType;
            }

            return $ClassPeriodList;
		}
		catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at SchoolSessionClassDaywiseTiming::GetClassAllPeriodDetails(). Stack Trace: '.$e->getTraceAsString());
            return $ClassPeriodList;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception at SchoolSessionClassDaywiseTiming::GetClassAllPeriodDetails(). Stack Trace: '.$e->getTraceAsString());
            return $ClassPeriodList;
        }
	}

	static function IsPeriodApplicableForClass($ClassID, $SchoolTimingPartID, $DayID, &$DaywiseTimingsID = 0, &$PeriodStartTime = '', &$PeriodEndTime = '', &$PeriodTimingID = 0)
	{
		$DaywiseTimingsID = 0;

		$PeriodStartTime = '';
		$PeriodEndTime = '';

		$PeriodTimingID = 0;

		try
		{
			$DBConnObject = new DBConnect();

            $RSSearch = $DBConnObject->Prepare('SELECT asscpt.periodTimingID, asscpt.daywiseTimingsID, asscpt.periodStartTime, asscpt.periodEndTime 
												FROM asa_school_session_class_period_timings asscpt 
												INNER JOIN asa_school_session_class_daywise_timings asscdt ON asscdt.daywiseTimingsID = asscpt.daywiseTimingsID 
												INNER JOIN asa_school_timing_parts_master astpm ON astpm.schoolTimingPartID = asscpt.schoolTimingPartID
												WHERE asscdt.schoolSessionID = (SELECT schoolSessionID FROM asa_session_activation_details WHERE isDecativated = 0 LIMIT 1)
												AND asscdt.classID = :|1  AND asscpt.schoolTimingPartID = :|2 AND asscdt.dayID = :|3;');

            $RSSearch->Execute($ClassID, $SchoolTimingPartID, $DayID);

			if ($RSSearch->Result->num_rows <= 0)
			{
				return false;
			}

			$SearchRow = $RSSearch->FetchRow();

			$DaywiseTimingsID = $SearchRow->daywiseTimingsID;
			$PeriodStartTime = $SearchRow->periodStartTime;
			$PeriodEndTime = $SearchRow->periodEndTime;
			$PeriodTimingID = $SearchRow->periodTimingID;
			
			return true;
		}
		catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at SchoolSessionClassDaywiseTiming::IsPeriodApplicableForClass(). Stack Trace: '.$e->getTraceAsString());
            return false;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception at SchoolSessionClassDaywiseTiming::IsPeriodApplicableForClass(). Stack Trace: '.$e->getTraceAsString());
            return false;
        }
	}
	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//
	private function SaveDetails()
	{
		if ($this->DaywiseTimingsID == 0)
		{
			foreach ($this->ClassList as $ClassID) 
			{
				foreach ($this->DayList as $DayID) 
				{
					$RSSave = $this->DBObject->Prepare('INSERT INTO asa_school_session_class_daywise_timings (schoolSessionID, classID, dayID,
																startTime, endTime, createUserID, createDate)
															VALUES (:|1, :|2, :|3, :|4, :|5, :|6, NOW());');
				
					$RSSave->Execute($this->SchoolSessionID, $ClassID, $DayID,
									$this->StartTime, $this->EndTime, $this->CreateUserID);
					
					$this->DaywiseTimingsID = $RSSave->LastID;

					foreach ($this->AllPeriods as $PeriodDetails) 
					{
						$RSSave = $this->DBObject->Prepare('INSERT INTO asa_school_session_class_period_timings (daywiseTimingsID, schoolTimingPartID, 
																	periodStartTime, periodEndTime)
																VALUES (:|1, :|2, :|3, :|4);');

						$RSSave->Execute($this->DaywiseTimingsID, $PeriodDetails['TimingPart'], 
										$PeriodDetails['PeriodStartTime'], $PeriodDetails['PeriodEndTime']);
					}
				}
			}
			
		}
		else
		{
			$RSUpdate = $this->DBObject->Prepare('UPDATE asa_school_session_class_daywise_timings
													SET	schoolSessionID = :|1,
														classID = :|2,
														dayID = :|3,
														startTime = :|4,
														endTime = :|5
													WHERE daywiseTimingsID = :|6;');
													
			$RSUpdate->Execute($this->SchoolSessionID, $this->ClassID, $this->DayID, $this->StartTime, $this->EndTime, $this->DaywiseTimingsID);
		}
		
		return true;
	}
	
	private function RemoveDaywiseTiming()
    {
        if (!isset($this->DaywiseTimingsID)) 
        {
            throw new ApplicationARException('', APP_AR_ERROR_DELETE_WITHOUT_ID);
        }
        
        $RSDeleteDaywiseTiming = $this->DBObject->Prepare('DELETE FROM asa_school_session_class_daywise_timings WHERE daywiseTimingsID = :|1 LIMIT 1;');
        $RSDeleteDaywiseTiming->Execute($this->DaywiseTimingsID);
    }

	private function GetSchoolSessionClassDaywiseTimingByID()
	{
		$RSSchoolSessionClassDaywiseTiming = $this->DBObject->Prepare('SELECT * FROM asa_school_session_class_daywise_timings WHERE daywiseTimingsID = :|1 LIMIT 1;');
		$RSSchoolSessionClassDaywiseTiming->Execute($this->DaywiseTimingsID);
		
		$SchoolSessionClassDaywiseTimingRow = $RSSchoolSessionClassDaywiseTiming->FetchRow();
		
		$this->SetAttributesFromDB($SchoolSessionClassDaywiseTimingRow);				
	}
	
	private function SetAttributesFromDB($SchoolSessionClassDaywiseTimingRow)
	{
		$this->DaywiseTimingsID = $SchoolSessionClassDaywiseTimingRow->daywiseTimingsID;
		$this->SchoolSessionID = $SchoolSessionClassDaywiseTimingRow->schoolSessionID;
		$this->ClassID = $SchoolSessionClassDaywiseTimingRow->classID;
		$this->DayID = $SchoolSessionClassDaywiseTimingRow->dayID;

		$this->StartTime = $SchoolSessionClassDaywiseTimingRow->startTime;
		$this->EndTime = $SchoolSessionClassDaywiseTimingRow->endTime;

		$this->CreateUserID = $SchoolSessionClassDaywiseTimingRow->createUserID;
		$this->CreateDate = $SchoolSessionClassDaywiseTimingRow->createDate;
	}	
}
?>
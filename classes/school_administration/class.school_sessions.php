<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.db_connect.php');
error_reporting(E_ALL);

class SchoolSessions
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	
	private $SchoolSessionID;

	private $SessionName;
	private $SessionDesciption;
	private $Priority;

	private $IsActive;
	private $CreateUserID;
	private $CreateDate;
	
	// PUBLIC METHODS START HERE	//
	public function __construct($SchoolSessionID = 0)
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;
		
		if($SchoolSessionID != 0)
		{
			$this->SchoolSessionID = $SchoolSessionID;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetSchoolSessionsByID();
		}
		else
		{
			//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
			$this->SchoolSessionID = 0;
			$this->SessionName = '';
			$this->SessionDesciption = '';
			$this->Priority = 0;

			$this->IsActive = 0;
			$this->CreateUserID = 0;
			$this->CreateDate = '0000-00-00 00:00:00';
		}
	}
	
	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetSchoolSessionID()
	{
		return $this->SchoolSessionID;
	}
	
	public function GetSessionName()
	{
		return $this->SessionName;
	}
	public function SetSessionName($SessionName)
	{
		$this->SessionName = $SessionName;
	}
	
	public function GetSessionDesciption()
	{
		return $this->SessionDesciption;
	}
	public function SetSessionDesciption($SessionDesciption)
	{
		$this->SessionDesciption = $SessionDesciption;
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
            $this->RemoveSchoolSession();
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
            $RSSchoolSessionClassDaywiseTimingsCount = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM asa_school_session_class_daywise_timings WHERE schoolSessionID = :|1;');
            $RSSchoolSessionClassDaywiseTimingsCount->Execute($this->SchoolSessionID);

            if ($RSSchoolSessionClassDaywiseTimingsCount->FetchRow()->totalRecords > 0) 
            {
                return true;
            }

            $RSSessionActivationDetailsCount = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM asa_session_activation_details WHERE schoolSessionID = :|1;');
            $RSSessionActivationDetailsCount->Execute($this->SchoolSessionID);
        
            if ($RSSessionActivationDetailsCount->FetchRow()->totalRecords > 0)
            {
                return true;
            }
            
            return false;
        }
        catch (ApplicationDBException $e)
        {
            error_log("DEBUG: ApplicationDBException: SchoolSessions::CheckDependencies()");
            return false;
        }
        catch (Exception $e)
        {
            error_log("DEBUG: Exception: SchoolSessions::CheckDependencies()");
            return false;
        }       
    }	
	// END OF PUBLIC METHODS	//
	
	// START OF STATIC METHODS	//
	static function GetAllSchoolSessions($GetOnlyName = false)
    {
        $AllSchoolSessions = array();

        try
        {
            $DBConnObject = new DBConnect();

            $RSSearch = $DBConnObject->Prepare('SELECT ass.*, u.userName AS createUserName 
												FROM asa_school_sessions ass 
												INNER JOIN users u ON ass.createUserID = u.userID 
												ORDER BY ass.priority;');
            $RSSearch->Execute();

            if ($RSSearch->Result->num_rows <= 0)
            {
                return $AllSchoolSessions;
            }

            while($SearchRow = $RSSearch->FetchRow())
            {
				if ($GetOnlyName)
				{
					$AllSchoolSessions[$SearchRow->schoolSessionID] = $SearchRow->sessionName;
					continue;
				}

                $AllSchoolSessions[$SearchRow->schoolSessionID]['SessionName'] = $SearchRow->sessionName;
                
                $AllSchoolSessions[$SearchRow->schoolSessionID]['SessionDesciption'] = $SearchRow->sessionDesciption;
		        $AllSchoolSessions[$SearchRow->schoolSessionID]['Priority'] = $SearchRow->priority;

		        $AllSchoolSessions[$SearchRow->schoolSessionID]['IsActive'] = $SearchRow->isActive;
		        $AllSchoolSessions[$SearchRow->schoolSessionID]['CreateUserID'] = $SearchRow->createUserID;
		        $AllSchoolSessions[$SearchRow->schoolSessionID]['CreateUserName'] = $SearchRow->createUserName;
		        $AllSchoolSessions[$SearchRow->schoolSessionID]['CreateDate'] = $SearchRow->createDate;
            }

            return $AllSchoolSessions;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at SchoolSessions::GetAllSchoolSessions(). Stack Trace: '.$e->getTraceAsString());
            return $AllSchoolSessions;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception at SchoolSessions::GetAllv(). Stack Trace: '.$e->getTraceAsString());
            return $AllSchoolSessions;
        }
    }

    static function UpdatePriorities($AllPriorityArray)
    {
    	try
		{
			$DBConnObject = new DBConnect();
			
			if (is_array($AllPriorityArray) && count($AllPriorityArray) > 0)
			{
				foreach ($AllPriorityArray as $SchoolSessionID=>$Priority)
				{
					$RSUpdate = $DBConnObject->Prepare('UPDATE asa_school_sessions SET priority = :|1 WHERE schoolSessionID = :|2 LIMIT 1;');
					$RSUpdate->Execute($Priority, $SchoolSessionID);
				}
			}
			
			return true;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException: SchoolSessions::UpdatePriorities(). Stack Trace: '.$e->getTraceAsString());
			return false;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception: SchoolSessions::UpdatePriorities(). Stack Trace: '.$e->getTraceAsString());
			return false;
		}
    }

    static function DeactivateSession($SessionID = 0, $LoggedUserID = '')
    {
    	try
		{
			$DBConnObject = new DBConnect();
			
			$DeactivateSession = $DBConnObject->Prepare('UPDATE asa_session_activation_details
															SET isDecativated = 1,
																sessionDeactivatedByUserID = :|1,
																sessionDeactivatedDate = NOW() 
															WHERE isDecativated = 0;');
            $DeactivateSession->Execute($LoggedUserID);

            $RSSave = $DBConnObject->Prepare('INSERT INTO asa_session_activation_details (schoolSessionID,
														sessionActivatedByUserID, sessionActivatedDate)
														VALUES (:|1, :|2, NOW());');
		
			$RSSave->Execute($SessionID, $LoggedUserID);

		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException: SchoolSessions::DeactivateSession(). Stack Trace: '.$e->getTraceAsString());
			return false;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception: SchoolSessions::DeactivateSession(). Stack Trace: '.$e->getTraceAsString());
			return false;
		}

    }
    static function GetActivatedSession()
    {
    	$ActivatedSession = array();
    	try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT * FROM asa_session_activation_details WHERE isDecativated = 0 Order By 
												sessionDeactivatedDate DESC LIMIT 1;');
            $RSSearch->Execute();

            if($RSSearch->Result->num_rows == 0)
            {
            	return $ActivatedSession;
            }

            $SearchRow = $RSSearch->FetchRow();
           
            $ActivatedSession[$SearchRow->schoolSessionID]['SessionActivatedByUserID'] = $SearchRow->sessionActivatedByUserID;
            $ActivatedSession[$SearchRow->schoolSessionID]['SessionActivatedDate'] = $SearchRow->sessionActivatedDate;

            return $ActivatedSession;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException: SchoolSessions::GetActivatedSession(). Stack Trace: '.$e->getTraceAsString());
			return false;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception: SchoolSessions::GetActivatedSession(). Stack Trace: '.$e->getTraceAsString());
			return false;
		}
    }
	
	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//
	private function SaveDetails()
	{

		if ($this->SchoolSessionID == 0)
		{
			$RSSave = $this->DBObject->Prepare('INSERT INTO asa_school_sessions (sessionName, sessionDesciption, priority, isActive, 
													createUserID, createDate)
														VALUES (:|1, :|2, :|3, :|4, :|5, NOW());');
		
			$RSSave->Execute($this->SessionName, $this->SessionDesciption, $this->Priority, $this->IsActive, $this->CreateUserID);
								$this->SchoolSessionID = $RSSave->LastID;

			$RSCountRow = $this->DBObject->Prepare('SELECT * FROM asa_school_sessions;');
		
			$RSCountRow->Execute();

			if($RSCountRow->Result->num_rows == 1)
			{
				$RSSave = $this->DBObject->Prepare('INSERT INTO asa_session_activation_details (schoolSessionID,
														sessionActivatedByUserID, sessionActivatedDate)
														VALUES (:|1, :|2, NOW());');
		
				$RSSave->Execute($this->SchoolSessionID, $this->CreateUserID);
			}
			
		
		}
		else
		{
			$RSUpdate = $this->DBObject->Prepare('UPDATE asa_school_sessions
													SET	sessionName = :|1,
														sessionDesciption = :|2,
														priority = :|3,
														isActive = :|4
													WHERE schoolSessionID = :|5;');
													
			$RSUpdate->Execute($this->SessionName, $this->SessionDesciption, $this->Priority, $this->IsActive, $this->SchoolSessionID);
		}
		
		return true;
	}

	private function RemoveSchoolSession()
    {
        if(!isset($this->SchoolSessionID)) 
        {
            throw new ApplicationARException('', APP_AR_ERROR_DELETE_WITHOUT_ID);
        }
        
        $RSDeleteSchoolSession = $this->DBObject->Prepare('DELETE FROM asa_school_sessions WHERE schoolSessionID = :|1 LIMIT 1;');
        $RSDeleteSchoolSession->Execute($this->SchoolSessionID);                
    }
	
	private function GetSchoolSessionsByID()
	{
		$RSSchoolSessions = $this->DBObject->Prepare('SELECT * FROM asa_school_sessions WHERE schoolSessionID = :|1;');
		$RSSchoolSessions->Execute($this->SchoolSessionID);
		
		$SchoolSessionsRow = $RSSchoolSessions->FetchRow();
		
		$this->SetAttributesFromDB($SchoolSessionsRow);				
	}
	
	private function SetAttributesFromDB($SchoolSessionsRow)
	{
		$this->SchoolSessionID = $SchoolSessionsRow->schoolSessionID;
		$this->SessionName = $SchoolSessionsRow->sessionName;
		$this->SessionDesciption = $SchoolSessionsRow->sessionDesciption;
		$this->Priority = $SchoolSessionsRow->priority;

		$this->IsActive = $SchoolSessionsRow->isActive;
		$this->CreateUserID = $SchoolSessionsRow->createUserID;
		$this->CreateDate = $SchoolSessionsRow->createDate;
	}	
}
?>
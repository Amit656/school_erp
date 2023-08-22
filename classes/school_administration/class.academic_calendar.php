<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.db_connect.php');
error_reporting(E_ALL);

class AcademicCalendar
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	
	private $AcademicCalendarID;
	
	private $EventStartDate;
    private $EventEndDate;

	private $EventName;
	private $EventDetails;

	private $IsHoliday;
	private $ToNotify;
	private $NotificationMessage;
	private $NotificationDate;

	private $HolidayForUsers = array();
	private $HolidayForClasses = array();
	private $NotificationForUsers = array();
	private $NotificationForClasses = array();

	private $AllEventsDates = array();

	private $CreateUserID;
	private $CreateDate;
	
	// PUBLIC METHODS START HERE	//
	public function __construct($AcademicCalendarID = 0)
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;
		
		if($AcademicCalendarID != 0)
		{
			$this->AcademicCalendarID = $AcademicCalendarID;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetAcademicCalendarByID();
		}
		else
		{
			//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
			$this->AcademicCalendarID = 0;
			$this->EventStartDate = '0000-00-00';
			$this->EventEndDate = '0000-00-00';

			$this->EventName = '';
			$this->EventDetails = '';

			$this->IsHoliday = 0;
			$this->ToNotify = 0;
			$this->NotificationMessage = '';
			$this->NotificationDate = '0000-00-00';
			$this->HolidayForUsers = array();
			$this->HolidayForClasses = array();
			$this->NotificationForUsers = array();
			$this->NotificationForClasses = array();

			$this->AllEventsDates = array();

			$this->CreateUserID = 0;
			$this->CreateDate = '0000-00-00 00:00:00	';
		}
	}
	
	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetAcademicCalendarID()
	{
		return $this->AcademicCalendarID;
	}
	
	public function GetEventStartDate()
	{
		return $this->EventStartDate;
	}
	public function SetEventStartDate($EventStartDate)
	{
		$this->EventStartDate = date('Y-m-d', strtotime($EventStartDate));
	}

	public function GetEventEndDate()
	{
		return $this->EventEndDate;
	}
	public function SetEventEndDate($EventEndDate)
	{
		$this->EventEndDate = date('Y-m-d', strtotime($EventEndDate));
	}

	public function GetEventName()
	{
		return $this->EventName;
	}
	public function SetEventName($EventName)
	{
		$this->EventName = $EventName;
	}

	public function GetEventDetails()
	{
		return $this->EventDetails;
	}
	public function SetEventDetails($EventDetails)
	{
		$this->EventDetails = $EventDetails;
	}

	public function GetIsHoliday()
	{
		return $this->IsHoliday;
	}
	public function SetIsHoliday($IsHoliday)
	{
		$this->IsHoliday = $IsHoliday;
	}

	public function GetToNotify()
	{
		return $this->ToNotify;
	}
	public function SetToNotify($ToNotify)
	{
		$this->ToNotify = $ToNotify;
	}

	public function GetNotificationMessage()
	{
		return $this->NotificationMessage;
	}
	public function SetNotificationMessage($NotificationMessage)
	{
		$this->NotificationMessage = $NotificationMessage;
	}

	public function GetNotificationDate()
	{
		return $this->NotificationDate;
	}
	public function SetNotificationDate($NotificationDate)
	{
		$this->NotificationDate = date('Y-m-d', strtotime($NotificationDate));
	}
	
	public function GetHolidayForUsers()
	{
		return $this->HolidayForUsers;
	}
	public function SetHolidayForUsers($HolidayForUsers)
	{
		$this->HolidayForUsers = $HolidayForUsers;
	}
	
	public function GetHolidayForClasses()
	{
		return $this->HolidayForClasses;
	}
	public function SetHolidayForClasses($HolidayForClasses)
	{
		$this->HolidayForClasses = $HolidayForClasses;
	}
	
	public function GetNotificationForUsers()
	{
		return $this->NotificationForUsers;
	}
	public function SetNotificationForUsers($NotificationForUsers)
	{
		$this->NotificationForUsers = $NotificationForUsers;
	}
		
	public function GetNotificationForClasses()
	{
		return $this->NotificationForClasses;
	}
	public function SetNotificationForClasses($NotificationForClasses)
	{
		$this->NotificationForClasses = $NotificationForClasses;
	}

	public function GetCreateUserID()
	{
		return $this->CreateUserID;
	}
	public function SetCreateUserID($CreateUserID)
	{
		$this->CreateUserID = $CreateUserID;
	}
	
	public function GetAllEventsDates()
	{
		return $this->AllEventsDates;
	}
	public function SetAllEventsDates($AllEventsDates)
	{
		$this->AllEventsDates = $AllEventsDates;
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
			$this->DBObject->BeginTransaction();	// BEGIN TRANSACTION			
			if ($this->SaveDetails())
			{			
				$this->DBObject->CommitTransaction();	// COMMIT TRANSACTION //
				return true;
			}
		}
		catch (ApplicationDBException $e)
		{
			$this->LastErrorCode = $e->getCode();
			$this->DBObject->RollBackTransaction();
			return false;
		}
		catch (ApplicationARException $e)
		{
			$this->LastErrorCode = $e->getCode();
			$this->DBObject->RollBackTransaction();
			return false;
		}
		catch (Exception $e)
		{
			$this->LastErrorCode = APP_ERROR_UNDEFINED_ERROR;
			$this->DBObject->RollBackTransaction();
			return false;
		}
	}

	public function Remove()
    {
        try
		{
			$this->DBObject->BeginTransaction();	// BEGIN TRANSACTION			
			if ($this->RemoveEvent())
			{			
				$this->DBObject->CommitTransaction();	// COMMIT TRANSACTION //
				return true;
			}
		}
		catch (ApplicationDBException $e)
		{
			$this->LastErrorCode = $e->getCode();
			$this->DBObject->RollBackTransaction();
			return false;
		}
		catch (ApplicationARException $e)
		{
			$this->LastErrorCode = $e->getCode();
			$this->DBObject->RollBackTransaction();
			return false;
		}
		catch (Exception $e)
		{
			$this->LastErrorCode = APP_ERROR_UNDEFINED_ERROR;
			$this->DBObject->RollBackTransaction();
			return false;
		}
    }
		
	// END OF PUBLIC METHODS	//
	
	// START OF STATIC METHODS	//

	static function GetAllEvents()
    {
        $AllEvents = array();

        try
        {
            $DBConnObject = new DBConnect();

            $RSSearch = $DBConnObject->Prepare('SELECT aac.*, u.userName AS createUserName 
            									FROM asa_academic_calendar aac 
            									INNER JOIN users u ON aac.createUserID = u.userID ORDER BY aac.eventStartDate;');
            $RSSearch->Execute();

            if ($RSSearch->Result->num_rows <= 0)
            {
                return $AllEvents;
            }

            while($SearchRow = $RSSearch->FetchRow())
            {
                $AllEvents[$SearchRow->academicCalendarID]['academicCalendarID'] = $SearchRow->academicCalendarID;
               
                $AllEvents[$SearchRow->academicCalendarID]['EventStartDate'] = $SearchRow->eventStartDate;
		        $AllEvents[$SearchRow->academicCalendarID]['EventEndDate'] = $SearchRow->eventEndDate;
		        $AllEvents[$SearchRow->academicCalendarID]['EventName'] = $SearchRow->eventName;

		        $AllEvents[$SearchRow->academicCalendarID]['IsHoliday'] = $SearchRow->isHoliday;
		        $AllEvents[$SearchRow->academicCalendarID]['ToNotify'] = $SearchRow->toNotify;
		        $AllEvents[$SearchRow->academicCalendarID]['CreateUserName'] = $SearchRow->createUserName;
		        $AllEvents[$SearchRow->academicCalendarID]['CreateDate'] = $SearchRow->createDate;
            }

            return $AllEvents;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at AcademicCalendar::GetAllEvents(). Stack Trace: '.$e->getTraceAsString());
            return $AllEvents;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception at AcademicCalendar::GetAllEvents(). Stack Trace: '.$e->getTraceAsString());
            return $AllEvents;
        }
    }

	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//
	private function SaveDetails()
	{

		if ($this->AcademicCalendarID == 0)
		{
			$RSSave = $this->DBObject->Prepare('INSERT INTO asa_academic_calendar (eventStartDate, eventEndDate, eventName,  eventDetails, 	
																isHoliday, toNotify, notificationMessage, notificationDate, createUserID, createDate)
														VALUES (:|1, :|2, :|3, :|4, :|5, :|6, :|7, :|8, :|9, NOW());');
		
			$RSSave->Execute($this->EventStartDate, $this->EventEndDate, $this->EventName, $this->EventDetails, $this->IsHoliday, $this->ToNotify, $this->NotificationMessage, $this->NotificationDate, $this->CreateUserID);
			
			$this->AcademicCalendarID = $RSSave->LastID;

			$EventStartDate = $this->EventStartDate;

			foreach($this->AllEventsDates as $key => $AllEventsDatesDetails)
			{
				$RSAcademicCalendarEventDates = $this->DBObject->Prepare('INSERT INTO asa_academic_calendar_event_dates (
																			academicCalendarID, eventDate)
																			VALUES (:|1, :|2)');
				$RSAcademicCalendarEventDates->Execute($this->AcademicCalendarID, $AllEventsDatesDetails);
			}

			if($this->IsHoliday == 1)
			{

   				foreach ($this->HolidayForUsers as $key => $HolidayForUsersDatails) 
   				{
   					if($HolidayForUsersDatails == 'Students')
   					{
   						foreach ($this->HolidayForClasses as $ClassSectionID => $Value) 
   						{
   							$RSAcademicCalendarRulesOn = $this->DBObject->Prepare('INSERT INTO asa_academic_calendar_rules_on(
																			academicCalendarID, ruleType, ruleOn, ruleOnClassSectionID)
														VALUES (:|1, "Holiday", :|2, :|3 )');
							$RSAcademicCalendarRulesOn->Execute($this->AcademicCalendarID, $HolidayForUsersDatails, $ClassSectionID);
   						}
   					}
   					else
   					{
   						
   						$RSAcademicCalendarRulesOn = $this->DBObject->Prepare('INSERT INTO asa_academic_calendar_rules_on (
																			academicCalendarID, ruleType, ruleOn)
														VALUES (:|1, "Holiday", :|2)');
						$RSAcademicCalendarRulesOn->Execute($this->AcademicCalendarID, $HolidayForUsersDatails);
   					}			
   				}
			}

			if($this->ToNotify == 1)
			{
   				foreach ($this->NotificationForUsers as $key => $NotificationForUsersDatails) 
   				{

   					if($NotificationForUsersDatails == 'Students')
   					{
   						foreach ($this->NotificationForClasses as $ClassSectionID => $Value) 
   						{
   							$RSAcademicCalendarRulesOn = $this->DBObject->Prepare('INSERT INTO asa_academic_calendar_rules_on (
																			academicCalendarID, ruleType, ruleOn, ruleOnClassSectionID)
														VALUES (:|1, "Notification", :|2, :|3 )');
							$RSAcademicCalendarRulesOn->Execute($this->AcademicCalendarID, $NotificationForUsersDatails, $ClassSectionID);
   						}
   					}
   					else
   					{
   						
   						$RSAcademicCalendarRulesOn = $this->DBObject->Prepare('INSERT INTO asa_academic_calendar_rules_on (
																			academicCalendarID, ruleType, ruleOn)
														VALUES (:|1, "Notification", :|2 )');
						$RSAcademicCalendarRulesOn->Execute($this->AcademicCalendarID, $NotificationForUsersDatails);
   					}			
   				}
			}
		}
		else
		{
			$RSUpdate = $this->DBObject->Prepare('UPDATE asa_academic_calendar
													SET	eventStartDate = :|1,
														eventEndDate = :|2,
														eventName = :|3,
														eventDetails = :|4,
														isHoliday = :|5,
														toNotify = :|6,
														notificationMessage = :|7,
														notificationDate = :|8
													WHERE academicCalendarID = :|9;'
												);

			$RSUpdate->Execute($this->EventStartDate, $this->EventEndDate, $this->EventName, $this->EventDetails, $this->IsHoliday, $this->ToNotify, 
									$this->NotificationMessage, $this->NotificationDate, $this->AcademicCalendarID);

	        $RSDeleteEventDates = $this->DBObject->Prepare('DELETE FROM asa_academic_calendar_event_dates WHERE academicCalendarID = :|1;');
	        $RSDeleteEventDates->Execute($this->AcademicCalendarID);

	        $RSDeleteEventDates = $this->DBObject->Prepare('DELETE FROM asa_academic_calendar_rules_on WHERE academicCalendarID = :|1;');
	        $RSDeleteEventDates->Execute($this->AcademicCalendarID);
	        	
	        foreach($this->AllEventsDates as $key => $AllEventsDatesDetails)
			{
				$RSAcademicCalendarEventDates = $this->DBObject->Prepare('INSERT INTO asa_academic_calendar_event_dates (
																			academicCalendarID, eventDate)
														VALUES (:|1, :|2)');
				$RSAcademicCalendarEventDates->Execute($this->AcademicCalendarID, $AllEventsDatesDetails);
			}

			if($this->IsHoliday == 1)
			{

   				foreach ($this->HolidayForUsers as $key => $HolidayForUsersDatails) 
   				{

   					if($HolidayForUsersDatails == 'Students')
   					{
   						foreach ($this->HolidayForClasses as $ClassSectionID => $Value) 
   						{
   							$RSAcademicCalendarRulesOn = $this->DBObject->Prepare('INSERT INTO asa_academic_calendar_rules_on (
																			academicCalendarID, ruleType, ruleOn, ruleOnClassSectionID)
														VALUES (:|1, "Holiday",:|2, :|3 )');
							$RSAcademicCalendarRulesOn->Execute($this->AcademicCalendarID, $HolidayForUsersDatails, $ClassSectionID);
   						}
   					}
   					else
   					{
   						
   						$RSAcademicCalendarRulesOn = $this->DBObject->Prepare('INSERT INTO asa_academic_calendar_rules_on (
																			academicCalendarID, ruleType, ruleOn)
														VALUES (:|1, "Holiday", :|2)');
						$RSAcademicCalendarRulesOn->Execute($this->AcademicCalendarID, $HolidayForUsersDatails);
   					}			
   				}
			}

			if($this->ToNotify == 1)
			{
   				foreach ($this->NotificationForUsers as $key => $NotificationForUsersDatails) 
   				{

   					if($NotificationForUsersDatails == 'Students')
   					{
   						foreach ($this->NotificationForClasses as $ClassSectionID => $Value) 
   						{
   							$RSAcademicCalendarRulesOn = $this->DBObject->Prepare('INSERT INTO asa_academic_calendar_rules_on (
																					academicCalendarID, ruleType, ruleOn, ruleOnClassSectionID)
																					VALUES (:|1, "Notification",:|2, :|3 )');
							$RSAcademicCalendarRulesOn->Execute($this->AcademicCalendarID, $NotificationForUsersDatails, $ClassSectionID);
   						}
   					}
   					else
   					{
   						
   						$RSAcademicCalendarRulesOn = $this->DBObject->Prepare('INSERT INTO asa_academic_calendar_rules_on (
																				academicCalendarID, ruleType, ruleOn)
																				VALUES (:|1, "Notification", :|2 )');
						$RSAcademicCalendarRulesOn->Execute($this->AcademicCalendarID, $NotificationForUsersDatails);
   					}			
   				}
			}
    	}
		return true;
	}

	private function RemoveEvent()
    {
        if(!isset($this->AcademicCalendarID)) 
        {
            throw new ApplicationARException('', APP_AR_ERROR_DELETE_WITHOUT_ID);
        }
        
        $RSDeleteEvent = $this->DBObject->Prepare('DELETE FROM asa_academic_calendar WHERE academicCalendarID = :|1;');
        $RSDeleteEvent->Execute($this->AcademicCalendarID);

        $RSDeleteEventDates = $this->DBObject->Prepare('DELETE FROM asa_academic_calendar_event_dates WHERE academicCalendarID = :|1;');
        $RSDeleteEventDates->Execute($this->AcademicCalendarID);

        $RSDeleteEventDates = $this->DBObject->Prepare('DELETE FROM asa_academic_calendar_rules_on WHERE academicCalendarID = :|1;');
        $RSDeleteEventDates->Execute($this->AcademicCalendarID);                

        return true;
    }

	private function GetAcademicCalendarByID()
	{
		$RSEvent = $this->DBObject->Prepare('SELECT * FROM asa_academic_calendar WHERE academicCalendarID = :|1;');
		$RSEvent->Execute($this->AcademicCalendarID);
		
		$RSEventRow = $RSEvent->FetchRow();

		$this->SetAttributesFromDB($RSEventRow);				
	}
	
	private function SetAttributesFromDB($RSEventRow)
	{
		$this->AcademicCalendarID = $RSEventRow->academicCalendarID;
		$this->EventStartDate = $RSEventRow->eventStartDate;
		$this->EventEndDate = $RSEventRow->eventEndDate;

		$this->EventName = $RSEventRow->eventName;
		$this->EventDetails = $RSEventRow->eventDetails;

		$this->IsHoliday = $RSEventRow->isHoliday;
		$this->ToNotify = $RSEventRow->toNotify;

		$this->NotificationMessage = $RSEventRow->notificationMessage;
		$this->NotificationDate = $RSEventRow->notificationDate;

		$this->CreateUserID = $RSEventRow->createUserID;
		$this->CreateDate = $RSEventRow->createDate;

		if($this->IsHoliday == 1)
		{
			$RSEventHolidayUsers = $this->DBObject->Prepare('SELECT distinct aaco.ruleOn FROM asa_academic_calendar_rules_on aaco WHERE aaco.academicCalendarID = :|1  AND aaco.ruleType = "Holiday";');
				$RSEventHolidayUsers->Execute($this->AcademicCalendarID);
				
				while($RSEventHolidayUsersRow = $RSEventHolidayUsers->FetchRow())
				{
					$this->HolidayForUsers[] = $RSEventHolidayUsersRow->ruleOn;

				}

			if(in_array("Students", $this->HolidayForUsers))
			{
				$RSEventHolidayClasses = $this->DBObject->Prepare('SELECT aaco.ruleOnClassSectionID FROM asa_academic_calendar_rules_on aaco WHERE aaco.academicCalendarID = :|1 AND ruleOnClassSectionID != 0 AND aaco.ruleType= "Holiday";');
				$RSEventHolidayClasses->Execute($this->AcademicCalendarID);
				while($RSEventHolidayClassesRow = $RSEventHolidayClasses->FetchRow())
				{
					$this->HolidayForClasses[$RSEventHolidayClassesRow->ruleOnClassSectionID] = 1;
				}
			}

		}

		if($this->ToNotify == 1)
		{
			$RSEventNotificationUsers = $this->DBObject->Prepare('SELECT distinct aaco.ruleOn FROM asa_academic_calendar_rules_on aaco WHERE aaco.academicCalendarID = :|1 AND aaco.ruleType= "Notification";');
			$RSEventNotificationUsers->Execute($this->AcademicCalendarID);
			
			while($RSEventNotificationUsersRow = $RSEventNotificationUsers->FetchRow())
			{
				$this->NotificationForUsers[] = $RSEventNotificationUsersRow->ruleOn;
			}

			if(in_array("Students", $this->NotificationForUsers))
			{
				$RSEventNotificatonClasses = $this->DBObject->Prepare('SELECT aaco.ruleOnClassSectionID FROM asa_academic_calendar_rules_on aaco WHERE aaco.academicCalendarID = :|1 AND ruleOnClassSectionID != 0 AND aaco.ruleType= "Notification";');
				$RSEventNotificatonClasses->Execute($this->AcademicCalendarID);
				
				while($RSEventNotificatonClassesRow = $RSEventNotificatonClasses->FetchRow())
				{
					$this->NotificationForClasses[$RSEventNotificatonClassesRow->ruleOnClassSectionID] = 1;
				}
			}
		}
	}	
}
?>
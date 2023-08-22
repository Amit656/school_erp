<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.db_connect.php');
error_reporting(E_ALL);

class AppAcademicCalendar extends AcademicCalendar
{
	// CLASS MEMBERS ARE DEFINED HERE	//

	
	// END OF PUBLIC METHODS	//
	
	// START OF STATIC METHODS	//

	static function GetAcademicCalenderHolidayEventDates()
	{
		$AllEventDates = array();
        
		try
        {
            $DBConnObject = new DBConnect();

            /*
            $RSSearch = $DBConnObject->Prepare('SELECT aaced.eventDateID, aac.eventName, aac.eventDetails, aaced.eventDate
												FROM asa_academic_calendar aac
												INNER JOIN asa_academic_calendar_event_dates aaced ON aac.academicCalendarID = aaced.academicCalendarID
												INNER JOIN asa_academic_calendar_rules_on aacro ON aac.academicCalendarID =  aacro.academicCalendarID
												WHERE aac.isHoliday = 1 AND aacro.ruleOn = \'TeachingStaff\' AND aacro.ruleType = \'Holiday\' ORDER BY aaced.eventDate;');
            */
            $RSSearch = $DBConnObject->Prepare('SELECT aaced.eventDateID, aac.eventName, aac.eventDetails, aaced.eventDate
												FROM asa_academic_calendar aac
												INNER JOIN asa_academic_calendar_event_dates aaced ON aac.academicCalendarID = aaced.academicCalendarID
												INNER JOIN asa_academic_calendar_rules_on aacro ON aac.academicCalendarID =  aacro.academicCalendarID
												WHERE aacro.ruleOn = \'TeachingStaff\' ORDER BY aaced.eventDate;');
												
            $RSSearch->Execute();

            if ($RSSearch->Result->num_rows <= 0)
            {
                return $AllEventDates;
            }

            while($SearchRow = $RSSearch->FetchRow())
            {               
		        $AllEventDates[$SearchRow->eventDateID]['EventName'] = $SearchRow->eventName;
		        $AllEventDates[$SearchRow->eventDateID]['EventDetails'] = $SearchRow->eventDetails;
		        $AllEventDates[$SearchRow->eventDateID]['EventDate'] = $SearchRow->eventDate;
            }

            return $AllEventDates;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at AppAcademicCalendar::GetAcademicCalenderHolidayEventDates(). Stack Trace: '.$e->getTraceAsString());
            return $AllEventDates;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception at AppAcademicCalendar::GetAcademicCalenderHolidayEventDates(). Stack Trace: '.$e->getTraceAsString());
            return $AllEventDates;
        }
	}
	
	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//
}
?>
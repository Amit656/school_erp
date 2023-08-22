<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.db_connect.php');
error_reporting(E_ALL);

class ClassHelpers
{
	static function SaveClassSectionTimeTable($ClassSectionID, $LoggedUserID, $TimeTableDetails)
	{
		try
		{
			$DBConnObject = new DBConnect();

			$DBConnObject->BeginTransaction();

			$RSDeleteTimeTableDetails = $DBConnObject->Prepare('DELETE FROM asa_class_time_table_details WHERE classTimeTableID IN (SELECT classTimeTableID FROM asa_class_time_table WHERE classSectionID = :|1);');
			$RSDeleteTimeTableDetails->Execute($ClassSectionID);
			
			$RSDeleteTimeTable = $DBConnObject->Prepare('DELETE FROM asa_class_time_table WHERE classSectionID = :|1;');
			$RSDeleteTimeTable->Execute($ClassSectionID);
			
			foreach ($TimeTableDetails as $DaywiseTimingsID => $DaywiseTimingDetails)
			{
				$RSSaveTimeTable = $DBConnObject->Prepare('INSERT INTO asa_class_time_table (classSectionID, daywiseTimingsID, isActive, createUserID, createDate) 
																VALUES (:|1, :|2, :|3, :|4, NOW());');

				$RSSaveTimeTable->Execute($ClassSectionID, $DaywiseTimingsID, 1, $LoggedUserID);

				$ClassTimeTableID = $RSSaveTimeTable->LastID;

				foreach ($DaywiseTimingDetails as $PeriodTimingID => $PeriodTimingDetails)
				{
					$RSSaveTimeTableDetails = $DBConnObject->Prepare('INSERT INTO asa_class_time_table_details (classTimeTableID, periodTimingID, classSubjectID, teacherClassID) 
																			VALUES (:|1, :|2, :|3, :|4);');

					$RSSaveTimeTableDetails->Execute($ClassTimeTableID, $PeriodTimingID, $PeriodTimingDetails['ClassSubjectID'], $PeriodTimingDetails['TeacherClassID']);
				}
			}

			$DBConnObject->CommitTransaction();

            return true;
		}
		catch (ApplicationDBException $e)
        {
			error_log('DEBUG: ApplicationDBException at ClassHelpers::SaveClassSectionTimeTable(). Stack Trace: '.$e->getTraceAsString());
			$DBConnObject->RollBackTransaction();
            return false;
        }
        catch (Exception $e)
        {
			error_log('DEBUG: Exception at ClassHelpers::SaveClassSectionTimeTable(). Stack Trace: '.$e->getTraceAsString());
			$DBConnObject->RollBackTransaction();
            return false;
        }
	}

	static function GetClassSectionTimeTable($ClassSectionID, $GetOnlyActiveRecords = true)
	{
		$ClassTimeTable = array();
		
		try
		{
			$DBConnObject = new DBConnect();

			$QueryString = '';
			
			if ($GetOnlyActiveRecords)
			{
				$QueryString = ' AND actt.isActive = 1';
			}

			$RSGetClassSectionTimeTable = $DBConnObject->Prepare('SELECT actt.daywiseTimingsID, acttd.periodTimingID, acttd.classSubjectID, acttd.teacherClassID, asm.subject, abs.firstName, abs.lastName 
																  FROM asa_class_time_table actt 
																  INNER JOIN asa_class_time_table_details acttd ON acttd.classTimeTableID = actt.classTimeTableID 
																  INNER JOIN asa_class_subjects acs ON acs.classSubjectID = acttd.classSubjectID 
																  INNER JOIN asa_subject_master asm ON asm.subjectID = acs.subjectID 
																  INNER JOIN asa_teacher_classes atc ON atc.teacherClassID = acttd.teacherClassID 
																  INNER JOIN asa_branch_staff abs ON abs.branchStaffID = atc.branchStaffID 
																  WHERE actt.classSectionID = :|1'.$QueryString.';');

			$RSGetClassSectionTimeTable->Execute($ClassSectionID);

			if ($RSGetClassSectionTimeTable->Result->num_rows <= 0)
			{
				return $ClassTimeTable;
			}

			while ($SearchClassSectionTimeTableRow = $RSGetClassSectionTimeTable->FetchRow())
			{
				$ClassTimeTable[$SearchClassSectionTimeTableRow->daywiseTimingsID][$SearchClassSectionTimeTableRow->periodTimingID]['ClassSubjectID'] = $SearchClassSectionTimeTableRow->classSubjectID;
				$ClassTimeTable[$SearchClassSectionTimeTableRow->daywiseTimingsID][$SearchClassSectionTimeTableRow->periodTimingID]['TeacherClassID'] = $SearchClassSectionTimeTableRow->teacherClassID;

				$ClassTimeTable[$SearchClassSectionTimeTableRow->daywiseTimingsID][$SearchClassSectionTimeTableRow->periodTimingID]['ClassSubjectName'] = $SearchClassSectionTimeTableRow->subject;
				$ClassTimeTable[$SearchClassSectionTimeTableRow->daywiseTimingsID][$SearchClassSectionTimeTableRow->periodTimingID]['TeacherClassName'] = $SearchClassSectionTimeTableRow->firstName.' '.$SearchClassSectionTimeTableRow->lastName;
			}

            return $ClassTimeTable;
		}
		catch (ApplicationDBException $e)
        {
			error_log('DEBUG: ApplicationDBException at ClassHelpers::GetClassSectionTimeTable(). Stack Trace: '.$e->getTraceAsString());
            return $ClassTimeTable;
        }
        catch (Exception $e)
        {
			error_log('DEBUG: Exception at ClassHelpers::GetClassSectionTimeTable(). Stack Trace: '.$e->getTraceAsString());
            return $ClassTimeTable;
        }
	}
}
?>
<?php
require_once('class.db_connect.php');
require_once('class.date_processing.php');
error_reporting(E_ALL);

class Helpers
{
    static function GetApplicableModules($UserID)
    {
        $ModuleList = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT * FROM sa_modules WHERE isDefault = 0 AND moduleID IN 
													(
														SELECT DISTINCT moduleID FROM tasks WHERE taskID IN 
														(SELECT taskID FROM user_tasks WHERE userID = :|1 AND isRevoked = 0)
													)
												;');
			$RSSearch->Execute($UserID);
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $ModuleList;
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$ModuleList[$SearchRow->moduleID] = $SearchRow->moduleName;
			}
			
			return $ModuleList;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at Helpers::GetActiveModules(). Stack Trace: ' . $e->getTraceAsString());
			return $ModuleList;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at Helpers::GetActiveModules(). Stack Trace: ' . $e->getTraceAsString());
			return $ModuleList;
		}
	}
	
	static function GetSchoolSummary()
    {
		$SchoolSummary = array();

		$SchoolSummary['TotalStudents'] = 0;
		$SchoolSummary['TotalFaculty'] = 0;
		$SchoolSummary['TotalNonTeachingStaff'] = 0;
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSCountStudents = $DBConnObject->Prepare('SELECT COUNT(*) AS totalRecords FROM asa_students;');
			$RSCountStudents->Execute();
			
			$SchoolSummary['TotalStudents'] = $RSCountStudents->FetchRow()->totalRecords;

			$RSCountFaculties = $DBConnObject->Prepare('SELECT COUNT(*) AS totalRecords FROM asa_branch_staff WHERE staffCategory = \'Teaching\';');
			$RSCountFaculties->Execute();
			
			$SchoolSummary['TotalFaculty'] = $RSCountFaculties->FetchRow()->totalRecords;

			$RSCountNonTeachingStaff = $DBConnObject->Prepare('SELECT COUNT(*) AS totalRecords FROM asa_branch_staff WHERE staffCategory = \'NonTeaching\';');
			$RSCountNonTeachingStaff->Execute();
			
			$SchoolSummary['TotalNonTeachingStaff'] = $RSCountNonTeachingStaff->FetchRow()->totalRecords;
			
			return $SchoolSummary;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at Helpers::GetSchoolSummary(). Stack Trace: ' . $e->getTraceAsString());
			return $SchoolSummary;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at Helpers::GetSchoolSummary(). Stack Trace: ' . $e->getTraceAsString());
			return $SchoolSummary;
		}
	}
	
	static function GenerateUniqueAddedID($UserName, $DOB = '', $AttemptNumber = 0)
	{
		$AddedDomain = '@added';

		try
		{
			$DBConnObject = new DBConnect();
			
			$UniqueID = str_replace(' ', '', strtolower(trim($UserName)));

			$UniqueID = preg_replace('/[^A-Za-z0-9\-]/', '', $UniqueID);

			if (!self::UniqueIDExists($UniqueID . $AddedDomain))
			{
				return $UniqueID . $AddedDomain;
			}

			if ($DOB != '')
			{
				$DOB = date('j M Y', strtotime($DOB));

				$DOBPieces = explode(' ', $DOB);

				if ($AttemptNumber == 1)
				{
					$UniqueID .= $DOBPieces[2];
				}
				else if ($AttemptNumber == 2)
				{
					$UniqueID .= $DOBPieces[0] . $DOBPieces[1];
				}
			}

			if ($AttemptNumber > 2)
			{
				$UniqueID .= $AttemptNumber;
			}
			
			if (self::UniqueIDExists($UniqueID . $AddedDomain))
			{
				$AttemptNumber++;

				return self::GenerateUniqueAddedID($UserName, $DOB, $AttemptNumber);
			}
			
			return $UniqueID . $AddedDomain;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at Helpers::GenerateUniqueAddedID(). Stack Trace: ' . $e->getTraceAsString());
			return false;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at Helpers::GenerateUniqueAddedID(). Stack Trace: ' . $e->getTraceAsString());
			return false;
		}
	}

	static function UniqueIDExists($UniqueID)
	{
		try
		{
			$DBConnObject = new DBConnect(1);
			
			$RSSearch = $DBConnObject->Prepare('SELECT COUNT(*) AS totalRecords FROM addedsch_central.all_user_accounts WHERE userName = :|1;');
			$RSSearch->Execute($UniqueID);
			
			if ($RSSearch->FetchRow()->totalRecords > 0)
			{
				return true;
			}

			return false;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at Helpers::UniqueIDExists(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at Helpers::UniqueIDExists(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
	}

	static function SaveUniqueID($UniqueID, $AadharNumber)
	{
		try
		{
			$DBConnObject = new DBConnect(1);
			
			$RSSave = $DBConnObject->Prepare('INSERT INTO addedsch_central.all_user_accounts (userName, aadharNumber) VALUES (:|1, :|2);');
			$RSSave->Execute($UniqueID, $AadharNumber);
			
			$UserAccountID = $RSSave->LastID;
			
			$RSSaveAccountSchool = $DBConnObject->Prepare('INSERT INTO user_account_school_branches (userAccountID, schoolBranchID) VALUES (:|1, :|2);');
			$RSSaveAccountSchool->Execute($UserAccountID, SCHOOL_BRANCH_ID);

			return true;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at Helpers::SaveUniqueID(). Stack Trace: ' . $e->getTraceAsString());
			return false;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at Helpers::SaveUniqueID(). Stack Trace: ' . $e->getTraceAsString());
			return false;
		}
	}
	
	static function MarkTeacherSubstitution($SubstitutionsList, $LoggedUserID)
	{
		try
		{
			$DBConnObject = new DBConnect();

			foreach ($SubstitutionsList as $ClassTimeTableDetailID => $TeacherClassID)
			{
				$RSSearchPeriodSubstitution = $DBConnObject->Prepare('SELECT classSubstitutionID FROM asa_class_substitution WHERE substitutionDate = CURRENT_DATE AND classTimeTableDetailID = :|1 LIMIT 1;');
				$RSSearchPeriodSubstitution->Execute($ClassTimeTableDetailID);
				
				if ($RSSearchPeriodSubstitution->Result->num_rows > 0)
				{
					$RSDeleteSubstitution = $DBConnObject->Prepare('DELETE FROM asa_class_substitution WHERE classSubstitutionID = :|1 LIMIT 1;');
					$RSDeleteSubstitution->Execute($RSSearchPeriodSubstitution->FetchRow()->classSubstitutionID);
				}
				
				if ($TeacherClassID > 0)
				{
					$RSMarkClassSubstitution = $DBConnObject->Prepare('INSERT INTO asa_class_substitution (substitutionDate, classTimeTableDetailID, teacherClassID, createUserID, createDate) 
																			VALUES (CURRENT_DATE, :|1, :|2, :|3, NOW());');

					$RSMarkClassSubstitution->Execute($ClassTimeTableDetailID, $TeacherClassID, $LoggedUserID);
				}
			}

			return true;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at Helpers::MarkTeacherSubstitution(). Stack Trace: ' . $e->getTraceAsString());
			return false;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at Helpers::MarkTeacherSubstitution(). Stack Trace: ' . $e->getTraceAsString());
			return false;
		}
	}
	
	static function GetCurrentDaySubstitutions()
	{
		$SubstitutionsList = array();
		
		try
		{
			$DBConnObject = new DBConnect();

			$RSSearchSubstitutions = $DBConnObject->Prepare('SELECT classTimeTableDetailID, teacherClassID, substitutionStatus FROM asa_class_substitution WHERE substitutionDate = CURRENT_DATE;');
			$RSSearchSubstitutions->Execute();
			
			if ($RSSearchSubstitutions->Result->num_rows <= 0)
			{
				return $SubstitutionsList;
			}
			
			while ($SearchRow = $RSSearchSubstitutions->FetchRow())
			{
				$SubstitutionsList[$SearchRow->classTimeTableDetailID]['TeacherClassID'] = $SearchRow->teacherClassID;
				$SubstitutionsList[$SearchRow->classTimeTableDetailID]['SubstitutionStatus'] = $SearchRow->substitutionStatus;
			}

			return $SubstitutionsList;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at Helpers::GetCurrentDaySubstitutions(). Stack Trace: ' . $e->getTraceAsString());
			return $SubstitutionsList;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at Helpers::GetCurrentDaySubstitutions(). Stack Trace: ' . $e->getTraceAsString());
			return $SubstitutionsList;
		}
	}

	static function GetBranchStaffWorkingDays($StartDate, $EndDate, $UserType = 'Teaching')
	{	
		$AllWorkingDays = array();
        
		// 0 => 'Second Saturday OFF', 1 => 'Month End OFF', 2 => 'Last Saturday Of Month OFF'
		// Variable for Apllicable Off Type
		$SecondSaturday = 0;
		$MonthEndOFF = 0;
		$LastSaturdayOfMonthOFF = 0;

		// Variable for Apllicable Off Type Date
		$SecondSaturdayDate = date('Y-m-d', strtotime(date('F Y', strtotime($StartDate)) . ' second saturday of this month'));
		$MonthEndOFFDate = $EndDate;
		$LastSaturdayOfMonthOFFDate = date('Y-m-d', strtotime(date('F Y', strtotime($StartDate)) . ' last saturday of this month'));

		//  variables for school off rules
		$SchoolOffRuleID = 0;
		$isOffTypeApplicableApplicable = 0;
		$IsWeeklyOff = 0;
		$AppliesToSpecificClasses = 0;
		$IsTeachingStaffApplicable = 0;
		$IsNonTeachingStaffApplicable = 0;

		try
		{	
			$DBConnObject = new DBConnect();

			// Processiong variable
			$Date = $StartDate;

			while (strtotime($Date) <= strtotime($EndDate)) 
			{	
				$RSSearchAcademicCalenderEvent = $DBConnObject->Prepare('SELECT COUNT(*) AS totalRecords 
																		FROM asa_academic_calendar_rules_on aacro 
																		WHERE aacro.ruleOn = :|1 AND aacro.academicCalendarID = (SELECT academicCalendarID FROM asa_academic_calendar WHERE :|2 BETWEEN eventStartDate AND eventEndDate AND isHoliday = 1 LIMIT 1);');
				$RSSearchAcademicCalenderEvent->Execute($UserType, date('Y-m-d', strtotime($Date)));

				if ($RSSearchAcademicCalenderEvent->FetchRow()->totalRecords <= 0) 
				{
					$AllWorkingDays[date('Y-m-d', strtotime($Date))] = 1;
				}
				
				$Date = date('Y-m-d', strtotime('+1 Day', strtotime($Date)));
			}

			$Query = '';

			if ($UserType = 'TeachingStaff') 
			{
				$Query = 'WHERE isTeachingStaffApplicable = 1';
			}
			elseif ($UserType = 'NonTeachingStaff') 
			{
				$Query = 'WHERE isNonTeachingStaffApplicable = 1';
			}

			$RSSearchSchoolTypeoffRule = $DBConnObject->Prepare('SELECT * FROM asa_school_off_rules ' . $Query . ' ORDER BY schoolOffRuleID DESC LIMIT 1;');
			$RSSearchSchoolTypeoffRule->Execute();
            
			if ($RSSearchSchoolTypeoffRule->Result->num_rows > 0) 
			{
				$SearchRowSchoolTypeoffRule = $RSSearchSchoolTypeoffRule->FetchRow();

				// initailizing variables of school off rules
				$SchoolOffRuleID = $SearchRowSchoolTypeoffRule->schoolOffRuleID;
				$IsOffTypeApplicable = $SearchRowSchoolTypeoffRule->isOffTypeApplicable;
				$IsWeeklyOff = $SearchRowSchoolTypeoffRule->isWeeklyOff;
				$AppliesToSpecificClasses = $SearchRowSchoolTypeoffRule->appliesToSpecificClasses;
				$IsTeachingStaffApplicable = $SearchRowSchoolTypeoffRule->isTeachingStaffApplicable;
				$IsNonTeachingStaffApplicable = $SearchRowSchoolTypeoffRule->isNonTeachingStaffApplicable;

				if ($IsOffTypeApplicable) 
				{
					$RSSearchApplicableOffType = $DBConnObject->Prepare('SELECT offType FROM asa_applicable_off_type WHERE schoolOffRuleID = :|1;');
					$RSSearchApplicableOffType->Execute($SchoolOffRuleID);

					if ($RSSearchApplicableOffType->Result->num_rows > 0) 
					{
						while ($SearchRowApplicableOffType = $RSSearchApplicableOffType->FetchRow()) 
						{
							switch ($SearchRowApplicableOffType->offType) 
							{
								case 0:
									$SecondSaturday = 1;
								break;

								case 1:
									$MonthEndOFF = 1;
								break;

								case 2:
									$LastSaturdayOfMonthOFF = 1;
								break;
							}
						}
					}
				}

				if ($IsTeachingStaffApplicable == 1 || $IsNonTeachingStaffApplicable == 1) 
				{
					// Variable for Apllicable Off Type Date
					// Variable for Apllicable Off Type Date
					$SecondSaturdayDate = '';
					$MonthEndOFFDate = '';
					$LastSaturdayOfMonthOFFDate = '';

					$Date = $StartDate;

					while (strtotime($Date) <= strtotime($EndDate)) 
					{							
						$SecondSaturdayDate = date('Y-m-d', strtotime(date('F Y', strtotime($Date)) . ' second saturday of this month'));
						$MonthEndOFFDate = date('Y-m-t', strtotime($Date));
						$LastSaturdayOfMonthOFFDate = date('Y-m-d', strtotime(date('F Y', strtotime($Date)) . ' last saturday of this month'));

						$Date = date('Y-m-d', strtotime('+1 Month', strtotime($Date)));

						if ($SecondSaturday && isset($AllWorkingDays[$SecondSaturdayDate])) 
						{
							unset($AllWorkingDays[$SecondSaturdayDate]);
						}

						if ($MonthEndOFF && isset($AllWorkingDays[$MonthEndOFFDate])) 
						{
							unset($AllWorkingDays[$MonthEndOFFDate]);
						}

						if ($LastSaturdayOfMonthOFF && isset($AllWorkingDays[$LastSaturdayOfMonthOFFDate])) 
						{
							unset($AllWorkingDays[$LastSaturdayOfMonthOFFDate]);
						}
					}
				}

				if ($IsWeeklyOff == 1) 
				{
					$SchoolOffRuleDetailType = '';
					$Weekdays = array();

					$RSSearchApplicableWeekdays = $DBConnObject->Prepare('SELECT schoolOffRuleDetailType, weekdays FROM asa_school_off_rules_weekly_details WHERE schoolOffRuleID = :|1;');
					$RSSearchApplicableWeekdays->Execute($SchoolOffRuleID);

					while ($SearchRowApplicableWeekdays = $RSSearchApplicableWeekdays->FetchRow()) 
					{
						$SchoolOffRuleDetailType = $SearchRowApplicableWeekdays->schoolOffRuleDetailType;
						$Weekdays = explode(',', $SearchRowApplicableWeekdays->weekdays);

						if ($SchoolOffRuleDetailType == 'Every') 
						{	
							foreach ($Weekdays as $WeekdayName) 
							{	
								foreach ($AllWorkingDays as $Date => $Value) 
								{	

									if (date('l', strtotime($Date)) == $WeekdayName) 
									{									
										unset($AllWorkingDays[$Date]);
									}
								}
							}	
						}

						if ($SchoolOffRuleDetailType == 'EveryEven') 
						{	
							$SecondWeekDay = '';
							$FourthWeekDay = '';

							$Date = $StartDate;

							while (strtotime($Date) <= strtotime($EndDate)) 
							{	
								foreach ($Weekdays as $WeekdayName) 
								{
									$WeekdayName = strtolower($WeekdayName);

									$SecondWeekDay = date('Y-m-d', strtotime(date('F Y', strtotime($Date)) . ' second ' . $WeekdayName . ' of this month'));
									$FourthWeekDay = date('Y-m-d', strtotime(date('F Y', strtotime($Date)) . ' fourth ' . $WeekdayName . ' of this month'));

									unset($AllWorkingDays[$SecondWeekDay]);
									unset($AllWorkingDays[$FourthWeekDay]);
								}						

								$Date = date('Y-m-d', strtotime('+1 Month', strtotime($Date)));
							}

						}
						
						if ($SchoolOffRuleDetailType == 'EveryOdd') 
						{
							$Date = $StartDate;

							while (strtotime($Date) <= strtotime($EndDate)) 
							{	
								foreach ($Weekdays as $WeekdayName) 
								{
									$WeekdayName = strtolower($WeekdayName);

									$FirstWeekDay = date('Y-m-d', strtotime(date('F Y', strtotime($Date)) . ' first ' . $WeekdayName . ' of this month'));
									$ThirdWeekDay = date('Y-m-d', strtotime(date('F Y', strtotime($Date)) . ' third ' . $WeekdayName . ' of this month'));
									$FifthWeekDay = date('Y-m-d', strtotime(date('F Y', strtotime($Date)) . ' fifth ' . $WeekdayName . ' of this month'));

									unset($AllWorkingDays[$FirstWeekDay]);
									unset($AllWorkingDays[$ThirdWeekDay]);
									unset($AllWorkingDays[$FifthWeekDay]);				
								}

								$Date = date('Y-m-d', strtotime('+1 Month', strtotime($Date)));
							}
						}
					}
				}
			}
			
			return $AllWorkingDays;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at Helpers::GetBranchStaffWorkingDays(). Stack Trace: ' . $e->getTraceAsString());
			return $AllWorkingDays;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at Helpers::GetBranchStaffWorkingDays(). Stack Trace: ' . $e->getTraceAsString());
			return $AllWorkingDays;
		}
	}

	static function GetIsBranchStaffAttendanceDateIsWorkingDay($StartDate, $EndDate, $UserType = 'Teaching', $AttedanceDate)
	{	
		$AllWorkingDays = array();

		// 0 => 'Second Saturday OFF', 1 => 'Month End OFF', 2 => 'Last Saturday Of Month OFF'
		// Variable for Apllicable Off Type
		$SecondSaturday = 0;
		$MonthEndOFF = 0;
		$LastSaturdayOfMonthOFF = 0;

		// Variable for Apllicable Off Type Date
		$SecondSaturdayDate = date('Y-m-d', strtotime(date('F Y', strtotime($StartDate)) . ' second saturday of this month'));
		$MonthEndOFFDate = $EndDate;
		$LastSaturdayOfMonthOFFDate = date('Y-m-d', strtotime(date('F Y', strtotime($StartDate)) . ' last saturday of this month'));

		//  variables for school off rules
		$SchoolOffRuleID = 0;
		$isOffTypeApplicableApplicable = 0;
		$IsWeeklyOff = 0;
		$AppliesToSpecificClasses = 0;
		$IsTeachingStaffApplicable = 0;
		$IsNonTeachingStaffApplicable = 0;

		try
		{	
			$DBConnObject = new DBConnect();

			// Processiong variable
			$Date = $StartDate;

			while (strtotime($Date) <= strtotime($EndDate)) 
			{	
				$RSSearchAcademicCalenderEvent = $DBConnObject->Prepare('SELECT COUNT(*) AS totalRecords 
																		FROM asa_academic_calendar_rules_on aacro 
																		WHERE aacro.ruleOn = :|1 AND aacro.academicCalendarID = (SELECT academicCalendarID FROM asa_academic_calendar WHERE :|2 BETWEEN eventStartDate AND eventEndDate AND isHoliday = 1 LIMIT 1);');
				$RSSearchAcademicCalenderEvent->Execute($UserType == 'Teaching' ? 'TeachingStaff' : 'NonTeachingStaff', date('Y-m-d', strtotime($Date)));

				if ($RSSearchAcademicCalenderEvent->FetchRow()->totalRecords <= 0) 
				{
					$AllWorkingDays[date('Y-m-d', strtotime($Date))] = 1;
				}
				
				$Date = date('Y-m-d', strtotime('+1 Day', strtotime($Date)));
			}

			$Query = '';

			if ($UserType = 'TeachingStaff') 
			{
				$Query = 'WHERE isTeachingStaffApplicable = 1';
			}
			elseif ($UserType = 'NonTeachingStaff') 
			{
				$Query = 'WHERE isNonTeachingStaffApplicable = 1';
			}

			$RSSearchSchoolTypeoffRule = $DBConnObject->Prepare('SELECT * FROM asa_school_off_rules ' . $Query . ' ORDER BY schoolOffRuleID DESC LIMIT 1;');
			$RSSearchSchoolTypeoffRule->Execute();

			if ($RSSearchSchoolTypeoffRule->Result->num_rows > 0) 
			{
				$SearchRowSchoolTypeoffRule = $RSSearchSchoolTypeoffRule->FetchRow();

				// initailizing variables of school off rules
				$SchoolOffRuleID = $SearchRowSchoolTypeoffRule->schoolOffRuleID;
				$IsOffTypeApplicable = $SearchRowSchoolTypeoffRule->isOffTypeApplicable;
				$IsWeeklyOff = $SearchRowSchoolTypeoffRule->isWeeklyOff;
				$AppliesToSpecificClasses = $SearchRowSchoolTypeoffRule->appliesToSpecificClasses;
				$IsTeachingStaffApplicable = $SearchRowSchoolTypeoffRule->isTeachingStaffApplicable;
				$IsNonTeachingStaffApplicable = $SearchRowSchoolTypeoffRule->isNonTeachingStaffApplicable;

				if ($IsOffTypeApplicable) 
				{
					$RSSearchApplicableOffType = $DBConnObject->Prepare('SELECT offType FROM asa_applicable_off_type WHERE schoolOffRuleID = :|1;');
					$RSSearchApplicableOffType->Execute($SchoolOffRuleID);

					if ($RSSearchApplicableOffType->Result->num_rows > 0) 
					{
						while ($SearchRowApplicableOffType = $RSSearchApplicableOffType->FetchRow()) 
						{
							switch ($SearchRowApplicableOffType->offType) 
							{
								case 0:
									$SecondSaturday = 1;
								break;

								case 1:
									$MonthEndOFF = 1;
								break;

								case 2:
									$LastSaturdayOfMonthOFF = 1;
								break;
							}
						}
					}
				}

				if ($IsTeachingStaffApplicable == 1 || $IsNonTeachingStaffApplicable == 1) 
				{
					// Variable for Apllicable Off Type Date
					// Variable for Apllicable Off Type Date
					$SecondSaturdayDate = '';
					$MonthEndOFFDate = '';
					$LastSaturdayOfMonthOFFDate = '';

					$Date = $StartDate;

					while (strtotime($Date) <= strtotime($EndDate)) 
					{							
						$SecondSaturdayDate = date('Y-m-d', strtotime(date('F Y', strtotime($Date)) . ' second saturday of this month'));
						$MonthEndOFFDate = date('Y-m-t', strtotime($Date));
						$LastSaturdayOfMonthOFFDate = date('Y-m-d', strtotime(date('F Y', strtotime($Date)) . ' last saturday of this month'));

						$Date = date('Y-m-d', strtotime('+1 Month', strtotime($Date)));

						if ($SecondSaturday && isset($AllWorkingDays[$SecondSaturdayDate])) 
						{
							unset($AllWorkingDays[$SecondSaturdayDate]);
						}

						if ($MonthEndOFF && isset($AllWorkingDays[$MonthEndOFFDate])) 
						{
							unset($AllWorkingDays[$MonthEndOFFDate]);
						}

						if ($LastSaturdayOfMonthOFF && isset($AllWorkingDays[$LastSaturdayOfMonthOFFDate])) 
						{
							unset($AllWorkingDays[$LastSaturdayOfMonthOFFDate]);
						}
					}
				}

				if ($IsWeeklyOff == 1) 
				{
					$SchoolOffRuleDetailType = '';
					$Weekdays = array();

					$RSSearchApplicableWeekdays = $DBConnObject->Prepare('SELECT schoolOffRuleDetailType, weekdays FROM asa_school_off_rules_weekly_details WHERE schoolOffRuleID = :|1;');
					$RSSearchApplicableWeekdays->Execute($SchoolOffRuleID);

					while ($SearchRowApplicableWeekdays = $RSSearchApplicableWeekdays->FetchRow()) 
					{
						$SchoolOffRuleDetailType = $SearchRowApplicableWeekdays->schoolOffRuleDetailType;
						$Weekdays = explode(',', $SearchRowApplicableWeekdays->weekdays);

						if ($SchoolOffRuleDetailType == 'Every') 
						{	
							foreach ($Weekdays as $WeekdayName) 
							{	
								foreach ($AllWorkingDays as $Date => $Value) 
								{	

									if (date('l', strtotime($Date)) == $WeekdayName) 
									{									
										unset($AllWorkingDays[$Date]);
									}
								}
							}	
						}

						if ($SchoolOffRuleDetailType == 'EveryEven') 
						{	
							$SecondWeekDay = '';
							$FourthWeekDay = '';

							$Date = $StartDate;

							while (strtotime($Date) <= strtotime($EndDate)) 
							{	
								foreach ($Weekdays as $WeekdayName) 
								{
									$WeekdayName = strtolower($WeekdayName);

									$SecondWeekDay = date('Y-m-d', strtotime(date('F Y', strtotime($Date)) . ' second ' . $WeekdayName . ' of this month'));
									$FourthWeekDay = date('Y-m-d', strtotime(date('F Y', strtotime($Date)) . ' fourth ' . $WeekdayName . ' of this month'));

									unset($AllWorkingDays[$SecondWeekDay]);
									unset($AllWorkingDays[$FourthWeekDay]);
								}						

								$Date = date('Y-m-d', strtotime('+1 Month', strtotime($Date)));
							}

						}
						
						if ($SchoolOffRuleDetailType == 'EveryOdd') 
						{
							$Date = $StartDate;

							while (strtotime($Date) <= strtotime($EndDate)) 
							{	
								foreach ($Weekdays as $WeekdayName) 
								{
									$WeekdayName = strtolower($WeekdayName);

									$FirstWeekDay = date('Y-m-d', strtotime(date('F Y', strtotime($Date)) . ' first ' . $WeekdayName . ' of this month'));
									$ThirdWeekDay = date('Y-m-d', strtotime(date('F Y', strtotime($Date)) . ' third ' . $WeekdayName . ' of this month'));
									$FifthWeekDay = date('Y-m-d', strtotime(date('F Y', strtotime($Date)) . ' fifth ' . $WeekdayName . ' of this month'));

									unset($AllWorkingDays[$FirstWeekDay]);
									unset($AllWorkingDays[$ThirdWeekDay]);
									unset($AllWorkingDays[$FifthWeekDay]);				
								}

								$Date = date('Y-m-d', strtotime('+1 Month', strtotime($Date)));
							}
						}
					}
				}
			}
			
			$IsWorkingDay = 0;

			if (array_key_exists($AttedanceDate, $AllWorkingDays)) 
			{
				return $IsWorkingDay = 1;
			}

			return $IsWorkingDay;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at Helpers::GetIsBranchStaffAttendanceDateIsWorkingDay(). Stack Trace: ' . $e->getTraceAsString());
			return $IsWorkingDay;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at Helpers::GetIsBranchStaffAttendanceDateIsWorkingDay(). Stack Trace: ' . $e->getTraceAsString());
			return $IsWorkingDay;
		}
	}

	static function GetClassWorkingDays($StartDate, $EndDate, $ClassSectionID)
	{	
		$AllWorkingDays = array();

		// 0 => 'Second Saturday OFF', 1 => 'Month End OFF', 2 => 'Last Saturday Of Month OFF'
		// Variable for Apllicable Off Type
		$SecondSaturday = 0;
		$MonthEndOFF = 0;
		$LastSaturdayOfMonthOFF = 0;

		//  variables for school off rules
		$SchoolOffRuleID = 0;
		$isOffTypeApplicableApplicable = 0;
		$IsWeeklyOff = 0;
		$AppliesToSpecificClasses = 0;
		$IsTeachingStaffApplicable = 0;
		$IsNonTeachingStaffApplicable = 0;

		try
		{	
			$DBConnObject = new DBConnect();

			// Processiong variable

			$Date = $StartDate;

			while (strtotime($Date) <= strtotime($EndDate)) 
			{	
				$RSSearchAcademicCalenderEvent = $DBConnObject->Prepare('SELECT COUNT(*) AS totalRecords 
																			FROM asa_academic_calendar_rules_on aacro 
																			WHERE aacro.ruleOn = \'Students\' AND aacro.ruleOnClassSectionID = :|1 
																			AND aacro.academicCalendarID = (SELECT academicCalendarID FROM asa_academic_calendar WHERE (:|2 BETWEEN eventStartDate AND eventEndDate) AND isHoliday = 1 LIMIT 1);');
				$RSSearchAcademicCalenderEvent->Execute($ClassSectionID, date('Y-m-d', strtotime($Date)));

				if ($RSSearchAcademicCalenderEvent->FetchRow()->totalRecords <= 0) 
				{
					$AllWorkingDays[date('Y-m-d', strtotime($Date))] = 1;
				}
				
				$Date = date('Y-m-d', strtotime('+1 Day', strtotime($Date)));
			}

			$RSSearchSchoolTypeoffRule = $DBConnObject->Prepare('SELECT * FROM asa_school_off_rules WHERE appliesToSpecificClasses = 1 ORDER BY schoolOffRuleID DESC LIMIT 1;');
			$RSSearchSchoolTypeoffRule->Execute();

			if ($RSSearchSchoolTypeoffRule->Result->num_rows > 0) 
			{
				$SearchRowSchoolTypeoffRule = $RSSearchSchoolTypeoffRule->FetchRow();

				// initailizing variables of school off rules
				$SchoolOffRuleID = $SearchRowSchoolTypeoffRule->schoolOffRuleID;
				$IsOffTypeApplicable = $SearchRowSchoolTypeoffRule->isOffTypeApplicable;
				$IsWeeklyOff = $SearchRowSchoolTypeoffRule->isWeeklyOff;
				$AppliesToSpecificClasses = $SearchRowSchoolTypeoffRule->appliesToSpecificClasses;
				$IsTeachingStaffApplicable = $SearchRowSchoolTypeoffRule->isTeachingStaffApplicable;
				$IsNonTeachingStaffApplicable = $SearchRowSchoolTypeoffRule->isNonTeachingStaffApplicable;

				if ($IsOffTypeApplicable) 
				{
					$RSSearchApplicableOffType = $DBConnObject->Prepare('SELECT offType FROM asa_applicable_off_type WHERE schoolOffRuleID = :|1;');
					$RSSearchApplicableOffType->Execute($SchoolOffRuleID);

					if ($RSSearchApplicableOffType->Result->num_rows > 0) 
					{
						while ($SearchRowApplicableOffType = $RSSearchApplicableOffType->FetchRow()) 
						{
							switch ($SearchRowApplicableOffType->offType) 
							{
								case 0:
									$SecondSaturday = 1;
								break;

								case 1:
									$MonthEndOFF = 1;
								break;

								case 2:
									$LastSaturdayOfMonthOFF = 1;
								break;
							}
						}
					}
				}

				if ($AppliesToSpecificClasses) 
				{	
					// Variable for Apllicable Off Type Date
					// Variable for Apllicable Off Type Date
					$SecondSaturdayDate = '';
					$MonthEndOFFDate = '';
					$LastSaturdayOfMonthOFFDate = '';

					$Date = $StartDate;

					while (strtotime($Date) <= strtotime($EndDate)) 
					{							
						$SecondSaturdayDate = date('Y-m-d', strtotime(date('F Y', strtotime($Date)) . ' second saturday of this month'));
						$MonthEndOFFDate = date('Y-m-t', strtotime($Date));
						$LastSaturdayOfMonthOFFDate = date('Y-m-d', strtotime(date('F Y', strtotime($Date)) . ' last saturday of this month'));

						$Date = date('Y-m-d', strtotime('+1 Month', strtotime($Date)));

						if ($SecondSaturday && isset($AllWorkingDays[$SecondSaturdayDate])) 
						{
							unset($AllWorkingDays[$SecondSaturdayDate]);
						}

						if ($MonthEndOFF && isset($AllWorkingDays[$MonthEndOFFDate])) 
						{
							unset($AllWorkingDays[$MonthEndOFFDate]);
						}

						if ($LastSaturdayOfMonthOFF && isset($AllWorkingDays[$LastSaturdayOfMonthOFFDate])) 
						{
							unset($AllWorkingDays[$LastSaturdayOfMonthOFFDate]);
						}
					}
				}

				if ($IsWeeklyOff == 1 && $AppliesToSpecificClasses == 1) 
				{
					$SchoolOffRuleDetailType = '';
					$Weekdays = array();

					$RSSearchApplicableWeekdays = $DBConnObject->Prepare('SELECT schoolOffRuleDetailType, weekdays FROM asa_school_off_rules_weekly_details WHERE schoolOffRuleID = :|1;');
					$RSSearchApplicableWeekdays->Execute($SchoolOffRuleID);

					while ($SearchRowApplicableWeekdays = $RSSearchApplicableWeekdays->FetchRow()) 
					{
						$SchoolOffRuleDetailType = $SearchRowApplicableWeekdays->schoolOffRuleDetailType;
						$Weekdays = explode(',', $SearchRowApplicableWeekdays->weekdays);

						if ($SchoolOffRuleDetailType == 'Every') 
						{	
							foreach ($Weekdays as $WeekdayName) 
							{	
								foreach ($AllWorkingDays as $Date => $Value) 
								{
									if (date('l', strtotime($Date)) == $WeekdayName) 
									{
										unset($AllWorkingDays[$Date]);
									}
								}
							}	
						}

						if ($SchoolOffRuleDetailType == 'EveryEven') 
						{
							$SecondWeekDay = '';
							$FourthWeekDay = '';

							$Date = $StartDate;

							while (strtotime($Date) <= strtotime($EndDate)) 
							{	
								foreach ($Weekdays as $WeekdayName) 
								{
									$WeekdayName = strtolower($WeekdayName);

									$SecondWeekDay = date('Y-m-d', strtotime(date('F Y', strtotime($Date)) . ' second ' . $WeekdayName . ' of this month'));
									$FourthWeekDay = date('Y-m-d', strtotime(date('F Y', strtotime($Date)) . ' fourth ' . $WeekdayName . ' of this month'));

									unset($AllWorkingDays[$SecondWeekDay]);
									unset($AllWorkingDays[$FourthWeekDay]);
								}						

								$Date = date('Y-m-d', strtotime('+1 Month', strtotime($Date)));
							}

						}
						
						if ($SchoolOffRuleDetailType == 'EveryOdd') 
						{
							$FirstWeekDay = '';
							$ThirdWeekDay = '';
							$FifthWeekDay = '';

							$Date = $StartDate;
							
							while (strtotime($Date) <= strtotime($EndDate)) 
							{	
								foreach ($Weekdays as $WeekdayName) 
								{
									$WeekdayName = strtolower($WeekdayName);

									$FirstWeekDay = date('Y-m-d', strtotime(date('F Y', strtotime($Date)) . ' first ' . $WeekdayName . ' of this month'));
									$ThirdWeekDay = date('Y-m-d', strtotime(date('F Y', strtotime($Date)) . ' third ' . $WeekdayName . ' of this month'));
									$FifthWeekDay = date('Y-m-d', strtotime(date('F Y', strtotime($Date)) . ' fifth ' . $WeekdayName . ' of this month'));

									unset($AllWorkingDays[$FirstWeekDay]);
									unset($AllWorkingDays[$ThirdWeekDay]);
									unset($AllWorkingDays[$FifthWeekDay]);				
								}

								$Date = date('Y-m-d', strtotime('+1 Month', strtotime($Date)));
							}
						}
					}
				}
			}
			
			return $AllWorkingDays;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at Helpers::GetClassWorkingDays(). Stack Trace: ' . $e->getTraceAsString());
			return $AllWorkingDays;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at Helpers::GetClassWorkingDays(). Stack Trace: ' . $e->getTraceAsString());
			return $AllWorkingDays;
		}
	}

	static function GetIsClassAttendanceDateIsWorkingDate($StartDate, $EndDate, $ClassSectionID, $AttedanceDate)
	{	
		$AllWorkingDays = array();

		// 0 => 'Second Saturday OFF', 1 => 'Month End OFF', 2 => 'Last Saturday Of Month OFF'
		// Variable for Apllicable Off Type
		$SecondSaturday = 0;
		$MonthEndOFF = 0;
		$LastSaturdayOfMonthOFF = 0;

		//  variables for school off rules
		$SchoolOffRuleID = 0;
		$isOffTypeApplicableApplicable = 0;
		$IsWeeklyOff = 0;
		$AppliesToSpecificClasses = 0;
		$IsTeachingStaffApplicable = 0;
		$IsNonTeachingStaffApplicable = 0;

		try
		{	
			$DBConnObject = new DBConnect();

			// Processiong variable

			$Date = $StartDate;

			while (strtotime($Date) <= strtotime($EndDate)) 
			{	
				$RSSearchAcademicCalenderEvent = $DBConnObject->Prepare('SELECT COUNT(*) AS totalRecords 
																			FROM asa_academic_calendar_rules_on aacro 
																			WHERE aacro.ruleOn = \'Students\' AND aacro.ruleOnClassSectionID = :|1 
																			AND aacro.academicCalendarID = (SELECT academicCalendarID FROM asa_academic_calendar WHERE (:|2 BETWEEN eventStartDate AND eventEndDate) AND isHoliday = 1 LIMIT 1);');
				$RSSearchAcademicCalenderEvent->Execute($ClassSectionID, date('Y-m-d', strtotime($Date)));

				if ($RSSearchAcademicCalenderEvent->FetchRow()->totalRecords <= 0) 
				{
					$AllWorkingDays[date('Y-m-d', strtotime($Date))] = 1;
				}
				
				$Date = date('Y-m-d', strtotime('+1 Day', strtotime($Date)));
			}

			$RSSearchSchoolTypeoffRule = $DBConnObject->Prepare('SELECT * FROM asa_school_off_rules WHERE appliesToSpecificClasses = 1 ORDER BY schoolOffRuleID DESC LIMIT 1;');
			$RSSearchSchoolTypeoffRule->Execute();

			if ($RSSearchSchoolTypeoffRule->Result->num_rows > 0) 
			{
				$SearchRowSchoolTypeoffRule = $RSSearchSchoolTypeoffRule->FetchRow();

				// initailizing variables of school off rules
				$SchoolOffRuleID = $SearchRowSchoolTypeoffRule->schoolOffRuleID;
				$IsOffTypeApplicable = $SearchRowSchoolTypeoffRule->isOffTypeApplicable;
				$IsWeeklyOff = $SearchRowSchoolTypeoffRule->isWeeklyOff;
				$AppliesToSpecificClasses = $SearchRowSchoolTypeoffRule->appliesToSpecificClasses;
				$IsTeachingStaffApplicable = $SearchRowSchoolTypeoffRule->isTeachingStaffApplicable;
				$IsNonTeachingStaffApplicable = $SearchRowSchoolTypeoffRule->isNonTeachingStaffApplicable;

				if ($IsOffTypeApplicable) 
				{
					$RSSearchApplicableOffType = $DBConnObject->Prepare('SELECT offType FROM asa_applicable_off_type WHERE schoolOffRuleID = :|1;');
					$RSSearchApplicableOffType->Execute($SchoolOffRuleID);

					if ($RSSearchApplicableOffType->Result->num_rows > 0) 
					{
						while ($SearchRowApplicableOffType = $RSSearchApplicableOffType->FetchRow()) 
						{
							switch ($SearchRowApplicableOffType->offType) 
							{
								case 0:
									$SecondSaturday = 1;
								break;

								case 1:
									$MonthEndOFF = 1;
								break;

								case 2:
									$LastSaturdayOfMonthOFF = 1;
								break;
							}
						}
					}
				}

				if ($AppliesToSpecificClasses) 
				{	
					// Variable for Apllicable Off Type Date
					// Variable for Apllicable Off Type Date
					$SecondSaturdayDate = '';
					$MonthEndOFFDate = '';
					$LastSaturdayOfMonthOFFDate = '';

					$Date = $StartDate;

					while (strtotime($Date) <= strtotime($EndDate)) 
					{							
						$SecondSaturdayDate = date('Y-m-d', strtotime(date('F Y', strtotime($Date)) . ' second saturday of this month'));
						$MonthEndOFFDate = date('Y-m-t', strtotime($Date));
						$LastSaturdayOfMonthOFFDate = date('Y-m-d', strtotime(date('F Y', strtotime($Date)) . ' last saturday of this month'));

						$Date = date('Y-m-d', strtotime('+1 Month', strtotime($Date)));

						if ($SecondSaturday && isset($AllWorkingDays[$SecondSaturdayDate])) 
						{
							unset($AllWorkingDays[$SecondSaturdayDate]);
						}

						if ($MonthEndOFF && isset($AllWorkingDays[$MonthEndOFFDate])) 
						{
							unset($AllWorkingDays[$MonthEndOFFDate]);
						}

						if ($LastSaturdayOfMonthOFF && isset($AllWorkingDays[$LastSaturdayOfMonthOFFDate])) 
						{
							unset($AllWorkingDays[$LastSaturdayOfMonthOFFDate]);
						}
					}
				}

				if ($IsWeeklyOff == 1 && $AppliesToSpecificClasses == 1) 
				{
					$SchoolOffRuleDetailType = '';
					$Weekdays = array();

					$RSSearchApplicableWeekdays = $DBConnObject->Prepare('SELECT schoolOffRuleDetailType, weekdays FROM asa_school_off_rules_weekly_details WHERE schoolOffRuleID = :|1;');
					$RSSearchApplicableWeekdays->Execute($SchoolOffRuleID);

					while ($SearchRowApplicableWeekdays = $RSSearchApplicableWeekdays->FetchRow()) 
					{
						$SchoolOffRuleDetailType = $SearchRowApplicableWeekdays->schoolOffRuleDetailType;
						$Weekdays = explode(',', $SearchRowApplicableWeekdays->weekdays);

						if ($SchoolOffRuleDetailType == 'Every') 
						{	
							foreach ($Weekdays as $WeekdayName) 
							{	
								foreach ($AllWorkingDays as $Date => $Value) 
								{
									if (date('l', strtotime($Date)) == $WeekdayName) 
									{
										unset($AllWorkingDays[$Date]);
									}
								}
							}	
						}

						if ($SchoolOffRuleDetailType == 'EveryEven') 
						{
							$SecondWeekDay = '';
							$FourthWeekDay = '';

							$Date = $StartDate;

							while (strtotime($Date) <= strtotime($EndDate)) 
							{	
								foreach ($Weekdays as $WeekdayName) 
								{
									$WeekdayName = strtolower($WeekdayName);

									$SecondWeekDay = date('Y-m-d', strtotime(date('F Y', strtotime($Date)) . ' second ' . $WeekdayName . ' of this month'));
									$FourthWeekDay = date('Y-m-d', strtotime(date('F Y', strtotime($Date)) . ' fourth ' . $WeekdayName . ' of this month'));

									unset($AllWorkingDays[$SecondWeekDay]);
									unset($AllWorkingDays[$FourthWeekDay]);
								}						

								$Date = date('Y-m-d', strtotime('+1 Month', strtotime($Date)));
							}

						}
						
						if ($SchoolOffRuleDetailType == 'EveryOdd') 
						{
							$FirstWeekDay = '';
							$ThirdWeekDay = '';
							$FifthWeekDay = '';

							$Date = $StartDate;
							
							while (strtotime($Date) <= strtotime($EndDate)) 
							{	
								foreach ($Weekdays as $WeekdayName) 
								{
									$WeekdayName = strtolower($WeekdayName);

									$FirstWeekDay = date('Y-m-d', strtotime(date('F Y', strtotime($Date)) . ' first ' . $WeekdayName . ' of this month'));
									$ThirdWeekDay = date('Y-m-d', strtotime(date('F Y', strtotime($Date)) . ' third ' . $WeekdayName . ' of this month'));
									$FifthWeekDay = date('Y-m-d', strtotime(date('F Y', strtotime($Date)) . ' fifth ' . $WeekdayName . ' of this month'));

									unset($AllWorkingDays[$FirstWeekDay]);
									unset($AllWorkingDays[$ThirdWeekDay]);
									unset($AllWorkingDays[$FifthWeekDay]);				
								}

								$Date = date('Y-m-d', strtotime('+1 Month', strtotime($Date)));
							}
						}
					}
				}
			}
			
			$IsWorkingDay = 0;

			if (array_key_exists($AttedanceDate, $AllWorkingDays)) 
			{
				return $IsWorkingDay = 1;
			}
			
			return $IsWorkingDay;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at Helpers::GetIsClassAttendanceDateIsWorkingDate(). Stack Trace: ' . $e->getTraceAsString());
			return $IsWorkingDay;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at Helpers::GetIsClassAttendanceDateIsWorkingDate(). Stack Trace: ' . $e->getTraceAsString());
			return $IsWorkingDay;
		}
	}

	static function GetStartDateOfTheMonth($RequestingMonthName)
	{
		$StartDateOfTheMonth = '';

		try
		{
			$CurentMonth = date('M');
        	$Year = date('Y');

        	if ($CurentMonth == 'Jan' && ($RequestingMonthName != 'Jan' && $RequestingMonthName != 'Feb' && $RequestingMonthName != 'Mar')) 
        	{
        		$Year = $Year - 1;
        	}
        	else if ($CurentMonth == 'Feb' && ($RequestingMonthName != 'Jan' && $RequestingMonthName != 'Feb' && $RequestingMonthName != 'Mar')) 
        	{
        		$Year = $Year - 1;
        	}
        	else if ($CurentMonth == 'Mar' && ($RequestingMonthName != 'Jan' && $RequestingMonthName != 'Feb' && $RequestingMonthName != 'Mar'))
        	{
        		$Year = $Year - 1;
        	}
        	else if ($RequestingMonthName == 'Jan' && ($CurentMonth != 'Jan' && $CurentMonth != 'Feb' && $CurentMonth != 'Mar'))
        	{
        		$Year = $Year + 1;
        	}
        	else if ($RequestingMonthName == 'Feb' && ($CurentMonth != 'Jan' && $CurentMonth != 'Feb' && $CurentMonth != 'Mar'))
        	{
        		$Year = $Year + 1;
        	}
        	else if ($RequestingMonthName == 'Mar' && ($CurentMonth != 'Jan' && $CurentMonth != 'Feb' && $CurentMonth != 'Mar'))
        	{
        		$Year = $Year + 1;
        	}

        	return $StartDateOfTheMonth = $Year . '-' . $RequestingMonthName . '-01';
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at Helpers::GetStartDateOfTheMonth(). Stack Trace: ' . $e->getTraceAsString());
			return $StartDateOfTheMonth;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at Helpers::GetStartDateOfTheMonth(). Stack Trace: ' . $e->getTraceAsString());
			return $StartDateOfTheMonth;
		}

	}	
	
	// Teacher App log summary
	
	static function GetTeacherAppLog($BranchStaffID, $SelectedDate)
	{
		$AppLogDetails = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			if ($SelectedDate == date('Y-m-d'))
			{
			    $RSSearchTotalAppCall = $DBConnObject->Prepare('SELECT SUM(total) AS totalAppCall FROM (SELECT COUNT(appName) AS total FROM asa_teacher_current_day_app_log 
			                                                WHERE branchStaffID = :|1 GROUP BY appName) AS tempTable;');
			    $RSSearchTotalAppCall->Execute($BranchStaffID);
			    
			    $TotalAppCall = $RSSearchTotalAppCall->FetchRow()->totalAppCall;
			    
			    $RSSearchAppLog = $DBConnObject->Prepare('SELECT *, COUNT(appName) AS totalAppUse FROM asa_teacher_current_day_app_log WHERE branchStaffID = :|1 GROUP BY appName;');
			    $RSSearchAppLog->Execute($BranchStaffID);
			    
			    if ($RSSearchAppLog->Result->num_rows <= 0)
    			{
    				return $AppLogDetails;
    			}
    			
    			$Counter = 0;
    			while ($SearchTeacherAppLogRow = $RSSearchAppLog->FetchRow())
    			{
    			    $UsagePercentage = (($SearchTeacherAppLogRow->totalAppUse / $TotalAppCall) * 100);
    			    
    				$AppLogDetails[$Counter]['label'] = $SearchTeacherAppLogRow->appName;
    				$AppLogDetails[$Counter]['y'] = $UsagePercentage;
    				
    				$Counter++;
    			}
			}
			else
			{
			    $RSSearchAppLog = $DBConnObject->Prepare('SELECT * FROM asa_teacher_app_log WHERE branchStaffID = :|1 AND Date = :|2;');
			    $RSSearchAppLog->Execute($BranchStaffID, $SelectedDate);   
			    
			    if ($RSSearchAppLog->Result->num_rows <= 0)
    			{
    				return $AppLogDetails;
    			}
    			
    			$Counter = 0;
    			while ($SearchRow = $RSSearchAppLog->FetchRow())
    			{
    				$AppLogDetails[$Counter]['label'] = $SearchRow->appName;
    				$AppLogDetails[$Counter]['y'] = $SearchRow->usePercentage;
    				
    				$Counter++;
    			}
			}

			return $AppLogDetails;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at Helpers::GetTeacherAppLog(). Stack Trace: ' . $e->getTraceAsString());
			return $AppLogDetails;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at Helpers::GetTeacherAppLog(). Stack Trace: ' . $e->getTraceAsString());
			return $AppLogDetails;
		}
	}
	
	// Promote Student
	static function PromoteStudents($StudentList, $NextAcademicYearID, $LoggedUserID)
	{
		try
		{
			$DBConnObject = new DBConnect();

			$DBConnObject->BeginTransaction();

			foreach ($StudentList as $StudentID => $Details) 
			{
				// Store necessary information of Student
				$RSSavePreviousDetails = $DBConnObject->Prepare('INSERT INTO asa_student_previous_academic_year_details (academicYearID, studentID,
															 previousClassSectionID, previousColourHouseID, previousRollNumber, createUserID, createDate) 
																SELECT academicYearID, studentID, classSectionID, colourHouseID, rollNumber, :|1, NOW() FROM asa_students WHERE studentID = :|2 ;');

				$RSSavePreviousDetails->Execute($LoggedUserID, $StudentID);	

				//Update Student for new session
				$RSUpdateStudents = $DBConnObject->Prepare('UPDATE asa_students
															SET	classSectionID = :|1,
																studentType = :|2,
																academicYearID = :|3
															WHERE studentID = :|4 LIMIT 1;');
														
				$RSUpdateStudents->Execute($Details['NextClassSectionID'], 'Old', $NextAcademicYearID, $StudentID);

				//Assign new transport fee
				$RSSearchStudentTransport = $DBConnObject->Prepare('SELECT sv.vehicleID, awf.routeID, awf.areaID FROM atm_student_vehicle sv
																	INNER JOIN atm_area_wise_fee awf ON awf.areaWiseFeeID = sv.areaWiseFeeID
						        									WHERE sv.studentID = :|1 AND sv.academicYearID != :|2 AND sv.isActive = 1
						        									ORDER BY sv.studentVehicleID DESC LIMIT 1;');
				$RSSearchStudentTransport->Execute($StudentID, $NextAcademicYearID);

				if($RSSearchStudentTransport->Result->num_rows > 0)
				{
					$SearchRow = $RSSearchStudentTransport->FetchRow();

					$RSSaveNewTransport = $DBConnObject->Prepare('INSERT INTO atm_student_vehicle (areaWiseFeeID, vehicleID, studentID, academicYearID, isActive, createUserID, createDate)
																	SELECT areaWiseFeeID, :|1, :|2, :|3, 1, :|4, NOW() FROM atm_area_wise_fee 
    																WHERE routeID = :|5 AND areaID = :|6 AND academicYearID = :|7;');
					
					$RSSaveNewTransport->Execute($SearchRow->vehicleID, $StudentID, $NextAcademicYearID, $LoggedUserID, $SearchRow->routeID, $SearchRow->areaID, $NextAcademicYearID);
				}

				//Assign new fee structure
				$RSSearchStudentFeeGroupID = $DBConnObject->Prepare('SELECT feeGroupID FROM afm_fee_group_assigned_records 
						        										WHERE recordID = :|1 LIMIT 1;');
				$RSSearchStudentFeeGroupID->Execute($StudentID);

				if($RSSearchStudentFeeGroupID->Result->num_rows > 0)
				{
					$StudentFeeGroupID = $RSSearchStudentFeeGroupID->FetchRow()->feeGroupID;

					$RSSearchFeeStructureID = $DBConnObject->Prepare('SELECT feeStructureID FROM afm_fee_structure
																		WHERE academicYearID = :|1 AND classID = (SELECT classID FROM asa_class_sections WHERE classSectionID = :|2) AND feeGroupID = :|3 LIMIT 1;');
					
					$RSSearchFeeStructureID->Execute($NextAcademicYearID, $Details['NextClassSectionID'], $StudentFeeGroupID);
					
					if ($RSSearchFeeStructureID->Result->num_rows > 0)
					{
						$FeeStructureID = $RSSearchFeeStructureID->FetchRow()->feeStructureID;

						$RSSearchFeeStructureDetails = $DBConnObject->Prepare('SELECT * FROM afm_fee_structure_details 
																				WHERE feeStructureID = :|1;');
						
						$RSSearchFeeStructureDetails->Execute($FeeStructureID);

						if($RSSearchFeeStructureDetails->Result->num_rows > 0)
						{
							while($SearchRow = $RSSearchFeeStructureDetails->FetchRow())
							{
							    $RSSearchFeeHead = $DBConnObject->Prepare('SELECT feeHead FROM afm_fee_heads  
																			WHERE feeHeadID = :|1;');
            				
            					$RSSearchFeeHead->Execute($SearchRow->feeHeadID);
            
            					$FeeHead = '';
            					$FeeHead = $RSSearchFeeHead->FetchRow()->feeHead;
            					
            					$AmountPayable = $SearchRow->feeAmount;

    							if ($FeeHead == 'Transport') 
								{
									$RSSearchTransportAmount = $DBConnObject->Prepare('SELECT aawf.amount FROM atm_student_vehicle asv  
																						INNER JOIN atm_area_wise_fee aawf ON aawf.areaWiseFeeID = asv.areaWiseFeeID
																						WHERE asv.studentID = :|1 AND asv.academicYearID = :|2;');
								
									$RSSearchTransportAmount->Execute($StudentID, $NextAcademicYearID);

									if ($RSSearchTransportAmount->Result->num_rows > 0) 
									{
										$AmountPayable = $RSSearchTransportAmount->FetchRow()->amount;
									}
								}   

								if ($AmountPayable > 0) 
								{
									$RSSaveStudentFeeStrucutre = $DBConnObject->Prepare('INSERT INTO afm_student_fee_structure (feeStructureDetailID, studentID, amountPayable)
																							VALUES(:|1, :|2, :|3);');
								
									$RSSaveStudentFeeStrucutre->Execute($SearchRow->feeStructureDetailID, $StudentID, $AmountPayable);
								}
							}
						}
					}
				}
			}
			
			$DBConnObject->CommitTransaction();
			return true;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at Helpers::PromoteStudents(). Stack Trace: ' . $e->getTraceAsString());
			$DBConnObject->RollBackTransaction();
			return false;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at Helpers::PromoteStudents(). Stack Trace: ' . $e->getTraceAsString());
			$DBConnObject->RollBackTransaction();
			return false;
		}
	}
}
?>
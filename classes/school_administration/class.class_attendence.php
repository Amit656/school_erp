<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.db_connect.php');
error_reporting(E_ALL);

class ClassAttendence
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	
	private $ClassAttendenceID;
	private $ClassSectionID;
	private $AttendenceDate;

	private $CreateUserID;
	private $CreateDate;

	private $AttendenceStatusPresentStudentsList = array();
	private $AttendenceStatusAbsentStudentsList = array();
	
	// PUBLIC METHODS START HERE	//
	public function __construct($ClassAttendenceID = 0)
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;
		
		if($ClassAttendenceID != 0)
		{
			$this->ClassAttendenceID = $ClassAttendenceID;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetClassAttendenceByID();
		}
		else
		{
			//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
			$this->ClassAttendenceID = 0;
			$this->ClassSectionID = 0;
			$this->AttendenceDate = '0000-00-00';

			$this->CreateUserID = 0;
			$this->CreateDate = '0000-00-00 00:00:00';

			$this->AttendenceStatusPresentStudentsList = array();
			$this->AttendenceStatusAbsentStudentsList = array();
		}
	}
	
	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetClassAttendenceID()
	{
		return $this->ClassAttendenceID;
	}
	
	public function GetClassSectionID()
	{
		return $this->ClassSectionID;
	}
	public function SetClassSectionID($ClassSectionID)
	{
		$this->ClassSectionID = $ClassSectionID;
	}
	
	public function GetAttendenceDate()
	{
		return $this->AttendenceDate;
	}
	public function SetAttendenceDate($AttendenceDate)
	{
		$this->AttendenceDate = $AttendenceDate;
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
	
	public function GetAttendenceStatusPresentStudentsList()
	{
		return $this->AttendenceStatusPresentStudentsList;
	}
	public function SetAttendenceStatusPresentStudentsList($AttendenceStatusPresentStudentsList)
	{
		$this->AttendenceStatusPresentStudentsList = $AttendenceStatusPresentStudentsList;
	}

	public function GetAttendenceStatusAbsentStudentsList()
	{
		return $this->AttendenceStatusAbsentStudentsList;
	}
	public function SetAttendenceStatusAbsentStudentsList($AttendenceStatusAbsentStudentsList)
	{
		$this->AttendenceStatusAbsentStudentsList = $AttendenceStatusAbsentStudentsList;
	}

	public function GetLastErrorCode()
	{
		return $this->LastErrorCode;
	}
	
	//  END OF GETTER AND SETTER FUNCTIONS 	//

	// START OF PUBLIC METHODS	//
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

	public function FillAttendenceStatus()
	{
		try
        {
			$RSPresentStudentsSearch = $this->DBObject->Prepare('SELECT acad.studentID FROM asa_class_attendence asd
																INNER JOIN asa_class_attendence_details acad ON asd.classAttendenceID = acad.classAttendenceID
																WHERE classSectionID = :|1 AND attendenceDate = :|2 AND acad.attendenceStatus = "Present";');
			$RSPresentStudentsSearch->Execute($this->ClassSectionID, $this->AttendenceDate);

			if ($RSPresentStudentsSearch->Result->num_rows > 0)
			{
				while($PresentStudentsSearchRow = $RSPresentStudentsSearch->FetchRow())
		        {
		           $this->AttendenceStatusPresentStudentsList[$PresentStudentsSearchRow->studentID] = 1;
		        }
			}

			$RSAbsentStudentsSearch = $this->DBObject->Prepare('SELECT acad.studentID FROM asa_class_attendence asd
																INNER JOIN asa_class_attendence_details acad ON asd.classAttendenceID = acad.classAttendenceID
																WHERE classSectionID = :|1 AND attendenceDate = :|2 AND acad.attendenceStatus = "Absent";');
			$RSAbsentStudentsSearch->Execute($this->ClassSectionID, $this->AttendenceDate);

			if ($RSAbsentStudentsSearch->Result->num_rows > 0)
			{
				while($AbsentStudentsSearchRow = $RSAbsentStudentsSearch->FetchRow())
		        {
		           $this->AttendenceStatusAbsentStudentsList[$AbsentStudentsSearchRow->studentID] = 1;
		        }
			}

	        return true;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at ClassAttendence::FillAttendenceStatus(). Stack Trace: ' . $e->getTraceAsString());
            return false;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception at ClassAttendence::FillAttendenceStatus(). Stack Trace: ' . $e->getTraceAsString());
            return false;
        }
	}
	
	public function ViewstudentAttendenceStatus()
	{
		try
        {
			$RSPresentStudentsSearch = $this->DBObject->Prepare('SELECT acad.studentID FROM asa_class_attendence asd
																INNER JOIN asa_class_attendence_details acad ON asd.classAttendenceID = acad.classAttendenceID
																WHERE classSectionID = :|1 AND attendenceDate = :|2 AND acad.attendenceStatus = "Present";');
			$RSPresentStudentsSearch->Execute($this->ClassSectionID, $this->AttendenceDate);

			if ($RSPresentStudentsSearch->Result->num_rows > 0)
			{
				while($PresentStudentsSearchRow = $RSPresentStudentsSearch->FetchRow())
		        {
		           $this->AttendenceStatusPresentStudentsList[$PresentStudentsSearchRow->studentID] = 1;
		        }
			}

			$RSAbsentStudentsSearch = $this->DBObject->Prepare('SELECT acad.studentID FROM asa_class_attendence asd
																INNER JOIN asa_class_attendence_details acad ON asd.classAttendenceID = acad.classAttendenceID
																WHERE classSectionID = :|1 AND attendenceDate = :|2 AND acad.attendenceStatus = "Absent";');
			$RSAbsentStudentsSearch->Execute($this->ClassSectionID, $this->AttendenceDate);

			if ($RSAbsentStudentsSearch->Result->num_rows > 0)
			{
				while($AbsentStudentsSearchRow = $RSAbsentStudentsSearch->FetchRow())
		        {
		           $this->AttendenceStatusAbsentStudentsList[$AbsentStudentsSearchRow->studentID] = 1;
		        }
			}

	        return true;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at ClassAttendence::ViewstudentAttendenceStatus(). Stack Trace: ' . $e->getTraceAsString());
            return false;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception at ClassAttendence::ViewstudentAttendenceStatus(). Stack Trace: ' . $e->getTraceAsString());
            return false;
        }
	}		
	// END OF PUBLIC METHODS	//
	
	// START OF STATIC METHODS	//
	static function GetStudentAttendance($StudentID)
	{
		$StudentAttendanceList = array();
		
		try
        {
            $DBConnObject = new DBConnect();

            $RSClassAttendence = $DBConnObject->Prepare('SELECT attendenceDate, acad.attendenceStatus FROM asa_class_attendence acs 
														 INNER JOIN asa_class_attendence_details acad ON acad.classAttendenceID = acs.classAttendenceID 
														 WHERE acad.studentID = :|1;');
			
            $RSClassAttendence->Execute($StudentID);
         
            if ($RSClassAttendence->Result->num_rows > 0) 
            {
                $Counter = 0;
                
				while ($SearchRow = $RSClassAttendence->FetchRow())
				{
					$StudentAttendanceList[$Counter]['Date'] = $SearchRow->attendenceDate;
					$StudentAttendanceList[$Counter]['Status'] = $SearchRow->attendenceStatus;
					$Counter++;
				}
            }

            return $StudentAttendanceList;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at ClassAttendence::GetStudentAttendance(). Stack Trace: ' . $e->getTraceAsString());
            return $StudentAttendanceList;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception at ClassAttendence::GetStudentAttendance(). Stack Trace: ' . $e->getTraceAsString());
            return $StudentAttendanceList;
        }
	}
	
	static function IsAttendenceTaken($ClassSectionID, $AttendenceDate, &$ClassAttendenceID = 0)
	{
		$ClassAttendenceID = 0;

		try
        {
            $DBConnObject = new DBConnect();

            $RSClassAttendence = $DBConnObject->Prepare('SELECT classAttendenceID FROM asa_class_attendence WHERE classSectionID = :|1 AND attendenceDate = :|2;');
            $RSClassAttendence->Execute($ClassSectionID, $AttendenceDate);
         
            if ($RSClassAttendence->Result->num_rows > 0) 
            {
            	$ClassAttendenceID = $RSClassAttendence->FetchRow()->classAttendenceID;
            	return true;
            }

            return false;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at ClassAttendence::IsAttendenceTaken(). Stack Trace: ' . $e->getTraceAsString());
            return false;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception at ClassAttendence::IsAttendenceTaken(). Stack Trace: ' . $e->getTraceAsString());
            return false;
        }
	}

	//this function using in app
	static function GetClassAttendence($ClassSectionID, $AttendenceDate)
	{
		$AllAttedanceDeatils = array();

		try
        {
            $DBConnObject = new DBConnect();

            $RSSearch = $DBConnObject->Prepare('SELECT ast.studentID, astd.firstName, astd.lastName, ast.rollNumber, astd.studentPhoto, acad.attendenceStatus, aca.classAttendenceID 
												FROM asa_students ast 
												INNER JOIN asa_student_details astd ON ast.studentID = astd.studentID
												LEFT JOIN asa_class_attendence_details acad ON ast.studentID = acad.studentID
												LEFT JOIN asa_class_attendence aca ON acad.classAttendenceID = aca.classAttendenceID
												WHERE ast.classSectionID = :|1 AND aca.attendenceDate = :|2 AND ast.status = \'Active\' ORDER BY ast.rollNumber;');
            $RSSearch->Execute($ClassSectionID, $AttendenceDate);
         
            if ($RSSearch->Result->num_rows <= 0) 
            {
            	return $AllAttedanceDeatils;
            }

            while ($SearchRow = $RSSearch->FetchRow()) 
            {
            	$AllAttedanceDeatils[$SearchRow->studentID]['FirstName'] = $SearchRow->firstName;
            	$AllAttedanceDeatils[$SearchRow->studentID]['LastName'] = $SearchRow->lastName;
            	$AllAttedanceDeatils[$SearchRow->studentID]['RollNumber'] = $SearchRow->rollNumber;
            	$AllAttedanceDeatils[$SearchRow->studentID]['Image'] = $SearchRow->studentPhoto;
            	$AllAttedanceDeatils[$SearchRow->studentID]['AttendenceStatus'] = $SearchRow->attendenceStatus;
            	$AllAttedanceDeatils[$SearchRow->studentID]['ClassAttendenceID'] = $SearchRow->classAttendenceID;
        	}

            return $AllAttedanceDeatils;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at ClassAttendence::GetClassAttendence(). Stack Trace: ' . $e->getTraceAsString());
            return $AllAttedanceDeatils;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception at ClassAttendence::GetClassAttendence(). Stack Trace: ' . $e->getTraceAsString());
            return $AllAttedanceDeatils;
        }
	}

	static function GetDayWiseClassAttendance($AttendanceDate, $AcademicYearID = 1)
	{
		$DayWiseClassAttendenceList = array();

		try
        {
            $DBConnObject = new DBConnect();

            $RSSearch = $DBConnObject->Prepare('SELECT DISTINCT(acs.classSectionID), ac.className, asm.sectionName,
												(
												 	SELECT COUNT(*) FROM asa_students where classSectionID = acs.classSectionID AND status = \'Active\' AND academicYearID = :|1
												) AS totalStudentsInClass,
												(
													SELECT COUNT(*) FROM asa_class_attendence act1
												    INNER JOIN asa_class_attendence_details actd1 ON act1.classAttendenceID = actd1.classAttendenceID
												    INNER JOIN asa_students ass ON ass.studentID = actd1.studentID
												    WHERE act1.classSectionID = acs.classSectionID AND act1.attendenceDate = :|2 AND ass.academicYearID = :|3
												     AND actd1.attendenceStatus = \'Present\' AND ass.status = \'Active\'
												) AS totalPresentStudent,
												(
													SELECT COUNT(*) FROM asa_class_attendence act1
												    INNER JOIN asa_class_attendence_details actd1 ON act1.classAttendenceID = actd1.classAttendenceID
												    INNER JOIN asa_students ass ON ass.studentID = actd1.studentID
												    WHERE act1.classSectionID = acs.classSectionID AND act1.attendenceDate = :|4 AND ass.academicYearID = :|5 AND actd1.attendenceStatus = \'Absent\' AND ass.status = \'Active\'
												) AS totalAbsentStudent,
												(
													SELECT CONCAT(abs.firstName , " ", abs.lastName) FROM asa_class_classteachers acct 
												    INNER JOIN asa_branch_staff abs ON acct.branchStaffID = abs.branchStaffID
												    where acct.classSectionID = acs.classSectionID
												) AS classTeacherName

												FROM asa_class_sections acs
												INNER JOIN asa_classes ac ON acs.classID = ac.classID
												INNER JOIN asa_section_master asm ON acs.sectionMasterID = asm.sectionMasterID
												ORDER BY ac.className, asm.sectionName;');
            $RSSearch->Execute($AcademicYearID, $AttendanceDate, $AcademicYearID, $AttendanceDate, $AcademicYearID);
         
            if ($RSSearch->Result->num_rows <= 0) 
            {
            	return $DayWiseClassAttendenceList;
            }

            while ($SearchRow = $RSSearch->FetchRow()) 
            {
            	$DayWiseClassAttendenceList[$SearchRow->classSectionID]['ClassName'] = $SearchRow->className;
            	$DayWiseClassAttendenceList[$SearchRow->classSectionID]['SectionName'] = $SearchRow->sectionName;
            	$DayWiseClassAttendenceList[$SearchRow->classSectionID]['TotalStudentsInClass'] = $SearchRow->totalStudentsInClass;
            	$DayWiseClassAttendenceList[$SearchRow->classSectionID]['TotalPresentStudent'] = $SearchRow->totalPresentStudent;
            	$DayWiseClassAttendenceList[$SearchRow->classSectionID]['TotalAbsentStudent'] = $SearchRow->totalAbsentStudent;
            	$DayWiseClassAttendenceList[$SearchRow->classSectionID]['ClassTeacherName'] = $SearchRow->classTeacherName;
            }

            return $DayWiseClassAttendenceList;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at ClassAttendence::GetDayWiseClassAttendance(). Stack Trace: ' . $e->getTraceAsString());
            return $DayWiseClassAttendenceList;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception at ClassAttendence::GetDayWiseClassAttendance(). Stack Trace: ' . $e->getTraceAsString());
            return $DayWiseClassAttendenceList;
        }
	}

	static function GetClassWiseMonthlyAttendance($AttendanceStartDate, $AttendanceEndDate, $ClassSectionID, $StudentID)
	{
		$MonthlyAttendanceList = array();

		try
        {
            $DBConnObject = new DBConnect();

            $RSSearch = $DBConnObject->Prepare('SELECT acad.classAttendenceDetailID, aca.attendenceDate, acad.attendenceStatus, acad.studentID
            									FROM asa_class_attendence aca 
            									INNER JOIN asa_class_attendence_details acad ON aca.classAttendenceID = acad.classAttendenceID 
            									WHERE (aca.attendenceDate BETWEEN :|1 AND :|2) AND aca.classSectionID = :|3 AND acad.studentID = :|4
            									ORDER By aca.attendenceDate;');
            									
            $RSSearch->Execute($AttendanceStartDate, $AttendanceEndDate, $ClassSectionID, $StudentID);
         
            if ($RSSearch->Result->num_rows <= 0) 
            {
            	return $MonthlyAttendanceList;
            }

            while ($SearchRow = $RSSearch->FetchRow()) 
            {
            	$MonthlyAttendanceList[$SearchRow->classAttendenceDetailID]['AttendenceDate'] = $SearchRow->attendenceDate;
            	$MonthlyAttendanceList[$SearchRow->classAttendenceDetailID]['StudentID'] = $SearchRow->studentID;
            	$MonthlyAttendanceList[$SearchRow->classAttendenceDetailID]['Status'] = $SearchRow->attendenceStatus;
            }

            return $MonthlyAttendanceList;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at ClassAttendence::GetClassWiseMonthlyAttendance(). Stack Trace: ' . $e->getTraceAsString());
            return $MonthlyAttendanceList;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception at ClassAttendence::GetClassWiseMonthlyAttendance(). Stack Trace: ' . $e->getTraceAsString());
            return $MonthlyAttendanceList;
        }
	}

	static function GetOverallClassSectionAttendance($ClassSectionID)
	{
		$TotolStudentsAttendance = 0;

		try
        {
        	$DBConnObject = new DBConnect();

            $RSSearch = $DBConnObject->Prepare('SELECT COUNT(*) AS totalRecords FROM asa_class_attendence aca 
												INNER JOIN asa_class_attendence_details acad ON aca.classAttendenceID = acad.classAttendenceID
												WHERE aca.classSectionID = :|1;');
            $RSSearch->Execute($ClassSectionID);

        	return $TotolStudentsAttendance = $RSSearch->FetchRow()->totalRecords;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at ClassAttendence::GetOverallClassSectionAttendance(). Stack Trace: ' . $e->getTraceAsString());
            return $TotolStudentsAttendance;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception at ClassAttendence::GetOverallClassSectionAttendance(). Stack Trace: ' . $e->getTraceAsString());
            return $TotolStudentsAttendance;
        }
	}

	static function SearchClassAttendence(&$TotalRecords = 0, $GetTotalsOnly = false, $Filters = array(), $Start = 0, $Limit = 100, $TotalWorkingDays = 0)
    {
    	$AllClassAttendence = array();

    	try
		{
			$DBConnObject = new DBConnect();
			
			$Conditions = array();
			
			if (count($Filters) > 0)
			{
				if (!empty($Filters['ClassID']))
				{
					$Conditions[] = 'acs.classID = ' . $DBConnObject->RealEscapeVariable($Filters['ClassID']);
				}

				if (!empty($Filters['ClassSectionID']))
				{
					$Conditions[] = 'ast.classSectionID = ' . $DBConnObject->RealEscapeVariable($Filters['ClassSectionID']);
				}

				if (!empty($Filters['StudentID']))
				{
					$Conditions[] = 'ast.studentID = ' . $DBConnObject->RealEscapeVariable($Filters['StudentID']);
				}
				
				if (!empty($Filters['AcademicYearID']))
				{
					$Conditions[] = 'ast.academicYearID = ' . $DBConnObject->RealEscapeVariable($Filters['AcademicYearID']);
				}
				
				$Conditions[] = 'ast.status = \'Active\'';
			}

			$QueryString = '';

			if (count($Conditions) > 0)
			{
				$QueryString = implode(' AND ',$Conditions);
				
				$QueryString = ' WHERE ' . $QueryString;
			}
			
			if ($GetTotalsOnly)
			{
			 
				$RSTotal = $DBConnObject->Prepare('SELECT COUNT(*) AS totalRecords 
													FROM asa_students ast 
													INNER JOIN asa_student_details astd on ast.studentID =  astd.studentID
													INNER JOIN asa_class_sections acs ON ast.classSectionID = acs.classSectionID
													INNER JOIN asa_classes ac ON acs.classID = ac.classID
													INNER JOIN asa_section_master asm ON acs.sectionMasterID = asm.sectionMasterID
													' . $QueryString . ';');
				$RSTotal->Execute();
				
				$TotalRecords = $RSTotal->FetchRow()->totalRecords;
				return;
			}
			
			$RSSearch = $DBConnObject->Prepare('SELECT ast.studentID, astd.firstName, astd.lastName , ac.className, asm.sectionName,
												(
													SELECT COUNT(*) 
												    FROM asa_class_attendence aca 
												    INNER JOIN asa_class_attendence_details acad ON aca.classAttendenceID = acad.classAttendenceID 
												    WHERE acad.studentID = ast.studentID AND acad.attendenceStatus = \'Present\' 
												) AS totalPresentDays
												FROM asa_students ast 
												INNER JOIN asa_student_details astd on ast.studentID =  astd.studentID
												INNER JOIN asa_class_sections acs ON ast.classSectionID = acs.classSectionID
												INNER JOIN asa_classes ac ON acs.classID = ac.classID
												INNER JOIN asa_section_master asm ON acs.sectionMasterID = asm.sectionMasterID 
												'. $QueryString . ' 
												ORDER BY ast.classSectionID 
												LIMIT ' . (int) $Start . ', ' . (int) $Limit . ';');
			$RSSearch->Execute();

			while($RSSearchRow = $RSSearch->FetchRow())
			{
				$IsPercentInRange = 0;
				$PresentPercentage = ($RSSearchRow->totalPresentDays / $TotalWorkingDays) * 100;

				if (!empty($Filters['AttendanePercentRangeFrom']) || !empty($Filters['AttendanePercentRangeTo'])) 
				{
					if (!empty($Filters['AttendanePercentRangeFrom']) && !($PresentPercentage >= $Filters['AttendanePercentRangeFrom'])) 
					{
						$IsPercentInRange = 1;
					}

					if (!empty($Filters['AttendanePercentRangeTo']) && !($PresentPercentage <= $Filters['AttendanePercentRangeTo'])) 
					{
						$IsPercentInRange = 1;
					}
				}

				if ($IsPercentInRange) 
				{
					continue;
				}

				$AllClassAttendence[$RSSearchRow->studentID]['FirstName'] = $RSSearchRow->firstName;
				$AllClassAttendence[$RSSearchRow->studentID]['LastName'] = $RSSearchRow->lastName;
				$AllClassAttendence[$RSSearchRow->studentID]['ClassName'] = $RSSearchRow->className;
				$AllClassAttendence[$RSSearchRow->studentID]['SectionName'] = $RSSearchRow->sectionName;
				$AllClassAttendence[$RSSearchRow->studentID]['TotalPresentDays'] = $RSSearchRow->totalPresentDays;
			}
			
			return $AllClassAttendence;	
				
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at ClassAttendence::SearchClassAttendence(). Stack Trace: ' . $e->getTraceAsString());
			return $AllClassAttendence;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at ClassAttendence::SearchClassAttendence(). Stack Trace: ' . $e->getTraceAsString());
			return $AllClassAttendence;
		}	
    }

	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//
	private function SaveDetails()
	{
		$AbsentStudentIDs = '';

		if (count($this->AttendenceStatusAbsentStudentsList) > 0)
		{	
			$AbsentStudentIDs = implode(',', array_keys($this->AttendenceStatusAbsentStudentsList));
		}
		
		if ($this->ClassAttendenceID == 0)
		{
			$RSSaveClassAttendence = $this->DBObject->Prepare('INSERT INTO asa_class_attendence (classSectionID, attendenceDate, createUserID, createDate)
																	VALUES (:|1, :|2, :|3, NOW());');
			$RSSaveClassAttendence->Execute($this->ClassSectionID, $this->AttendenceDate, $this->CreateUserID);
			
			$this->ClassAttendenceID = $RSSaveClassAttendence->LastID;
		}
		else
		{
			$RSDeleteRecords = $this->DBObject->Prepare('DELETE FROM asa_class_attendence_details WHERE classAttendenceID = :|1;');
			$RSDeleteRecords->Execute($this->ClassAttendenceID);
		}

		if($AbsentStudentIDs != '')
		{
			$RSSavePresentStudents = $this->DBObject->Prepare('INSERT INTO asa_class_attendence_details (classAttendenceID, studentID, attendenceStatus)
	       															SELECT :|1, studentID, "Present" FROM asa_students WHERE classSectionID = :|2 AND studentID NOT IN (' . $AbsentStudentIDs . ');');
			$RSSavePresentStudents->Execute($this->ClassAttendenceID, $this->ClassSectionID);

			$RSSaveAbsentStudents = $this->DBObject->Prepare('INSERT INTO asa_class_attendence_details (classAttendenceID, studentID, attendenceStatus)
           															SELECT :|1, studentID, "Absent" FROM asa_students WHERE classSectionID = :|2 AND studentID IN (' . $AbsentStudentIDs . ');');
			$RSSaveAbsentStudents->Execute($this->ClassAttendenceID, $this->ClassSectionID);
		}
		else
		{
			$RSSavePresentStudents = $this->DBObject->Prepare('INSERT INTO asa_class_attendence_details (classAttendenceID, studentID, attendenceStatus)
		       														SELECT :|1, studentID, "Present" FROM asa_students WHERE classSectionID = :|2;');
			$RSSavePresentStudents->Execute($this->ClassAttendenceID, $this->ClassSectionID);
		}

		return true;
	}
	
	private function GetClassAttendenceByID()
	{
		$RSClassAttendence = $this->DBObject->Prepare('SELECT * FROM asa_class_attendence WHERE classAttendenceID = :|1 LIMIT 1;');
		$RSClassAttendence->Execute($this->ClassAttendenceID);
		
		$ClassAttendenceRow = $RSClassAttendence->FetchRow();
		
		$this->SetAttributesFromDB($ClassAttendenceRow);				
	}
	
	private function SetAttributesFromDB($ClassAttendenceRow) 
	{
		$this->ClassAttendenceID = $ClassAttendenceRow->classAttendenceID;
		$this->ClassSectionID = $ClassAttendenceRow->classSectionID;
		$this->AttendenceDate = $ClassAttendenceRow->attendenceDate;

		$this->CreateUserID = $ClassAttendenceRow->createUserID;
		$this->CreateDate = $ClassAttendenceRow->createDate;
	}	
}
?>
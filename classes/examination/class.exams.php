<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.db_connect.php');
error_reporting(E_ALL);

class Exam
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	
	private $ExamID;
	private $ExamTypeID;
	private $ClassSectionID	;
	private $ClassSubjectID;

	private $ExamName;
	private $MaximumMarks;
	private $IsOffline;
	private $ExamClosed;

	private $CreateUserID;
	private $CreateDate;
	
	private $ClassSectionList = array();
	private $MaximumMarkDetails = array();
	
	// PUBLIC METHODS START HERE	//
	public function __construct($ExamID = 0)
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;
		
		if($ExamID != 0)
		{
			$this->ExamID = $ExamID;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetExamByID();
		}
		else
		{
			//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
			$this->ExamID = 0;
			$this->ExamTypeID = 0;
			$this->ClassSectionID = 0;
			$this->ClassSubjectID = 0;

			$this->ExamName = '';
			$this->MaximumMarks = '';
			$this->IsOffline = 0;
			$this->ExamClosed = '';
			
			$this->CreateUserID = 0;
			$this->CreateDate = '0000-00-00 00:00:00';
			
			$this->ClassSectionList = array();
			$this->MaximumMarkDetails = array();
		}
	}
	
	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetExamID()
	{
		return $this->ExamID;
	}

	public function GetExamTypeID()
	{
		return $this->ExamTypeID;
	}
	public function SetExamTypeID($ExamTypeID)
	{
		$this->ExamTypeID = $ExamTypeID;
	}

	public function GetClassSectionID()
	{
		return $this->ClassSectionID;
	}
	public function SetClassSectionID($ClassSectionID)
	{
		$this->ClassSectionID = $ClassSectionID;
	}

	public function GetClassSubjectID()
	{
		return $this->ClassSubjectID;
	}
	public function SetClassSubjectID($ClassSubjectID)
	{
		$this->ClassSubjectID = $ClassSubjectID;
	}

	public function GetExamName()
	{
		return $this->ExamName;
	}
	public function SetExamName($ExamName)
	{
		$this->ExamName = $ExamName;
	}
	
	public function GetMaximumMarks()
	{
		return $this->MaximumMarks;
	}
	public function SetMaximumMarks($MaximumMarks)
	{
		$this->MaximumMarks = $MaximumMarks;
	}
	
	public function GetIsOffline()
	{
		return $this->IsOffline;
	}
	public function SetIsOffline($IsOffline)
	{
		$this->IsOffline = $IsOffline;
	}
	
	public function GetExamClosed()
	{
		return $this->ExamClosed;
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
	
	public function GetClassSectionList()
	{
		return $this->ClassSectionList;
	}
	public function SetClassSectionList($ClassSectionList)
	{
		$this->ClassSectionList = $ClassSectionList;
	}
	
	public function GetMaximumMarkDetails()
	{
		return $this->MaximumMarkDetails;
	}
	public function SetMaximumMarkDetails($MaximumMarkDetails)
	{
		$this->MaximumMarkDetails = $MaximumMarkDetails;
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
            $this->RemoveExam();
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
            $RSExamCount = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM aem_student_exam_marks WHERE examID = :|1;');
            $RSExamCount->Execute($this->ExamID);

            if ($RSExamCount->FetchRow()->totalRecords > 0) 
            {
                return true;
            }

            return false;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at Exam::CheckDependencies(). Stack Trace: ' . $e->getTraceAsString());
            return true;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: ApplicationDBException at Exam::CheckDependencies(). Stack Trace: ' . $e->getTraceAsString());
            return true;
        }       
    }

    public function CheckMarksFeed()
    {
        try
        {
            $RSExamCount = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM aem_student_exam_marks WHERE examID = :|1;');
            $RSExamCount->Execute($this->ExamID);

            if ($RSExamCount->FetchRow()->totalRecords > 0) 
            {
                return true;
            }

            return false;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at Exam::CheckMarksFeed(). Stack Trace: ' . $e->getTraceAsString());
            return true;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: ApplicationDBException at Exam::CheckMarksFeed(). Stack Trace: ' . $e->getTraceAsString());
            return true;
        }       
    }
	
	public function MarkExamAsClosed()
    {
        try
        {
            $RSUpdate = $this->DBObject->Prepare('UPDATE aem_exams SET examClosed = 1 WHERE examID = :|1 LIMIT 1;');
            $RSUpdate->Execute($this->ExamID);
			
            return true;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at Exam::MarkExamAsClosed(). Stack Trace: ' . $e->getTraceAsString());
            return false;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: ApplicationDBException at Exam::MarkExamAsClosed(). Stack Trace: ' . $e->getTraceAsString());
            return false;
        }       
    }

	// END OF PUBLIC METHODS	//
	
	// START OF STATIC METHODS	//
	static function GetAllExamsForReportCard($ClassSectionID, $GetNameOnly = true, $SelectedExams = '')
    { 
		$AllExams = array();

		$QueryString = '';
		
    	try
        {	
        	$DBConnObject = new DBConnect();

        	if ($SelectedExams != '') 
			{
				$QueryString = ' AND ae.examTypeID IN (' . $SelectedExams . ')';
			}

        	$RSSearch = $DBConnObject->Prepare('SELECT DISTINCT(ae.examTypeID), ae.examName, ae.examID, aet.examType
												FROM aem_exams ae
												INNER JOIN aem_exam_types aet ON ae.examTypeID = aet.examTypeID
												WHERE ae.classSectionID = :|1 '.$QueryString.' GROUP BY (ae.examTypeID);');
            $RSSearch->Execute($ClassSectionID);

            if ($RSSearch->Result->num_rows <= 0)
            {
                return $AllExams;
            }

            while($SearchRow = $RSSearch->FetchRow())
            {	
            	if ($GetNameOnly) 
            	{
            		$AllExams[$SearchRow->examID] = $SearchRow->examName;
            		continue;
            	}

            	$AllExams[$SearchRow->examTypeID]['ExamName'] = $SearchRow->examName;
            	$AllExams[$SearchRow->examTypeID]['ExamID'] = $SearchRow->examID;
            	$AllExams[$SearchRow->examTypeID]['ExamType'] = $SearchRow->examType;
           }
            
            return $AllExams;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException::Exam::GetAllExamsForReportCard(). Stack Trace: '. $e->getTraceAsString());
            return $AllExams;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception: Exam::GetAllExamsForReportCard() . Stack Trace: '. $e->getTraceAsString());
            return $AllExams;
        }
    }

    static function GetAllExamSubjectNumbers($StudentID, $ClassSubjectID)
    { 
		$AllExamSubjectNumbers = array();
		
    	try
        {	
        	$DBConnObject = new DBConnect();
        	$RSSearch = $DBConnObject->Prepare('SELECT astem.*, ae.maximumMarks
												FROM asa_students ast
												INNER JOIN asa_student_details astd ON ast.studentID = astd.studentID
												LEFT JOIN aem_student_exam_marks astem ON ast.studentID = astem.studentID
												LEFT JOIN aem_exams ae ON astem.examID = ae.examID
												LEFT JOIN aem_exam_types aet ON ae.examTypeID = aet.examTypeID
												WHERE ast.studentID = :|1 AND ae.classSubjectID = :|2 ORDER BY ae.examTypeID;');
            $RSSearch->Execute($StudentID, $ClassSubjectID);

            if ($RSSearch->Result->num_rows <= 0)
            {
                return $AllExamSubjectNumbers;
            }

            while($SearchRow = $RSSearch->FetchRow())
            {
                $AllExamSubjectNumbers[$SearchRow->studentExamMarkID]['MaximumMarks'] = $SearchRow->maximumMarks;
                $AllExamSubjectNumbers[$SearchRow->studentExamMarkID]['Marks'] = $SearchRow->marks;
           }
            
            return $AllExamSubjectNumbers;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException::Exam::GetAllExamSubjectNumbers(). Stack Trace: '. $e->getTraceAsString());
            return $AllExamSubjectNumbers;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception: Exam::GetAllExamSubjectNumbers() . Stack Trace: '. $e->getTraceAsString());
            return $AllExamSubjectNumbers;
        }
    }

    static function GetAllExamSubjectGrandTotals($StudentID)
    { 
		$AllExamSubjectGrandTotals = array();
		
    	try
        {	
        	$DBConnObject = new DBConnect();
        	$RSSearch = $DBConnObject->Prepare('SELECT astem.studentExamMarkID, SUM(astem.marks) AS grandTotalOfMarks, SUM(ae.maximumMarks)  AS grandTotalOfMaximumMarks
												FROM asa_students ast
												INNER JOIN asa_student_details astd ON ast.studentID = astd.studentID
												LEFT JOIN aem_student_exam_marks astem ON ast.studentID = astem.studentID
												LEFT JOIN aem_exams ae ON astem.examID = ae.examID
												LEFT JOIN aem_exam_types aet ON ae.examTypeID = aet.examTypeID
												WHERE ast.studentID = :|1 GROUP BY ae.examTypeID ORDER BY ae.examTypeID;');
            $RSSearch->Execute($StudentID);

            if ($RSSearch->Result->num_rows <= 0)
            {
                return $AllExamSubjectGrandTotals;
            }

            while($SearchRow = $RSSearch->FetchRow())
            {
                $AllExamSubjectGrandTotals[$SearchRow->studentExamMarkID]['MaximumMarks'] = $SearchRow->grandTotalOfMaximumMarks;
                $AllExamSubjectGrandTotals[$SearchRow->studentExamMarkID]['Marks'] = $SearchRow->grandTotalOfMarks;
           }
            
            return $AllExamSubjectGrandTotals;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException::Exam::GetAllExamSubjectGrandTotals(). Stack Trace: '. $e->getTraceAsString());
            return $AllExamSubjectGrandTotals;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception: Exam::GetAllExamSubjectGrandTotals() . Stack Trace: '. $e->getTraceAsString());
            return $AllExamSubjectGrandTotals;
        }
    }
    
    static function GetTotalMarksOfExam($ExamTypeID, $ClassSectionID)
    { 
		$ExamTotal = 0;
		
    	try
        {	
        	$DBConnObject = new DBConnect();
        	$RSSearch = $DBConnObject->Prepare('SELECT SUM(maximumMarks)  AS examTotal
												FROM aem_exams
												WHERE examTypeID = :|1 AND classSectionID = :|2;');
            $RSSearch->Execute($ExamTypeID, $ClassSectionID);

            if ($RSSearch->Result->num_rows <= 0)
            {
                return $ExamTotal;
            }

            while($SearchRow = $RSSearch->FetchRow())
            {
                $ExamTotal = $SearchRow->examTotal;
           }
            
            return $ExamTotal;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException::Exam::GetTotalMarksOfExam(). Stack Trace: '. $e->getTraceAsString());
            return $ExamTotal;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception: Exam::GetTotalMarksOfExam() . Stack Trace: '. $e->getTraceAsString());
            return $ExamTotal;
        }
    }
	
	static function SearchExams(&$TotalRecords = 0, $GetTotalsOnly = false, $Filters = array(), $Start = 0, $Limit = 100)
	{
		$AllExams = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$Conditions = array();
			
			if (count($Filters) > 0)
			{
				if (!empty($Filters['ClassID']))
				{
					$Conditions[] = 'ac.classID = ' . $DBConnObject->RealEscapeVariable($Filters['ClassID']);
				}
				
				if (!empty($Filters['ClassSectionID']))
				{
					$Conditions[] = 'ae.classSectionID = ' . $DBConnObject->RealEscapeVariable($Filters['ClassSectionID']);
				}
				
				if (!empty($Filters['ClassSubjectID']))
				{
					$Conditions[] = 'ae.classSubjectID = ' . $DBConnObject->RealEscapeVariable($Filters['ClassSubjectID']);
				}
				
				if (!empty($Filters['ExamTypeID']))
				{
					$Conditions[] = 'aet.examTypeID = ' . $DBConnObject->RealEscapeVariable($Filters['ExamTypeID']);
				}
			}
			
			$QueryString = '';
			if (count($Conditions) > 0)
			{
				$QueryString = ' WHERE ' . implode(" AND ", $Conditions);
			}
			
			if ($GetTotalsOnly)
			{
				$RSTotal = $DBConnObject->Prepare('SELECT COUNT(DISTINCT(ae.examID)) AS totalRecords
													FROM aem_exams ae
													INNER JOIN aem_exam_types aet ON aet.examTypeID = ae.examTypeID 
													INNER JOIN asa_class_sections acs ON acs.classSectionID = ae.classSectionID 
													INNER JOIN asa_classes ac ON ac.classID = acs.classID 
													INNER JOIN asa_section_master asm ON asm.sectionMasterID = acs.sectionMasterID 
													INNER JOIN asa_class_subjects cs ON cs.classSubjectID = ae.classSubjectID 
													INNER JOIN asa_subject_master sm ON sm.subjectID = cs.subjectID 
													INNER JOIN users u ON ae.createUserID = u.userID 
													' . $QueryString . ';');
				$RSTotal->Execute();
				
				$TotalRecords = $RSTotal->FetchRow()->totalRecords;
				return;
			}
			
			$RSExamDetail = $DBConnObject->Prepare('SELECT ae.*, aet.examType, ac.className, asm.sectionName, sm.subject, cs.subjectMarksType, u.userName AS createUserName 
													FROM aem_exams ae
													INNER JOIN aem_exam_types aet ON aet.examTypeID = ae.examTypeID 
													INNER JOIN asa_class_sections acs ON acs.classSectionID = ae.classSectionID 
													INNER JOIN asa_classes ac ON ac.classID = acs.classID 
													INNER JOIN asa_section_master asm ON asm.sectionMasterID = acs.sectionMasterID 
													INNER JOIN asa_class_subjects cs ON cs.classSubjectID = ae.classSubjectID 
													INNER JOIN asa_subject_master sm ON sm.subjectID = cs.subjectID 
													INNER JOIN users u ON ae.createUserID = u.userID 
													' . $QueryString . ' 
													ORDER BY ae.examID LIMIT '.(int) $Start.', '.(int) $Limit.';');
			$RSExamDetail->Execute();
			
			while($ExamDetailRow = $RSExamDetail->FetchRow())
			{
				$AllExams[$ExamDetailRow->examID]['ExamName'] = $ExamDetailRow->examName;
				$AllExams[$ExamDetailRow->examID]['ExamType'] = $ExamDetailRow->examType;

				$AllExams[$ExamDetailRow->examID]['Class'] = $ExamDetailRow->className .' '. $ExamDetailRow->sectionName;
				$AllExams[$ExamDetailRow->examID]['Subject'] = $ExamDetailRow->subject;
				$AllExams[$ExamDetailRow->examID]['MaximumMarks'] = $ExamDetailRow->maximumMarks;

				$AllExams[$ExamDetailRow->examID]['IsOffline'] = $ExamDetailRow->isOffline;
				$AllExams[$ExamDetailRow->examID]['ExamClosed'] = $ExamDetailRow->examClosed;
				$AllExams[$ExamDetailRow->examID]['SubjectMarksType'] = $ExamDetailRow->subjectMarksType;

				$AllExams[$ExamDetailRow->examID]['CreateUserID'] = $ExamDetailRow->createUserID;
				$AllExams[$ExamDetailRow->examID]['CreateUserName'] = $ExamDetailRow->createUserName;
				$AllExams[$ExamDetailRow->examID]['CreateDate'] = $ExamDetailRow->createDate;
			}
			
			return $AllExams;	
		}
		
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at Exam::SearchExams(). Stack Trace: ' . $e->getTraceAsString());
			return $AllExams;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at Exam::SearchExams(). Stack Trace: ' . $e->getTraceAsString());
			return $AllExams;
		}
	}
	
	static function SearchExamReport(&$TotalRecords = 0, $GetTotalsOnly = false, $Filters = array(), $Start = 0, $Limit = 100)
	{
		$StudentList = array();

		try
		{
			$DBConnObject = new DBConnect();
			
			$Conditions = array();
			
			if (count($Filters) > 0)
			{
				if (!empty($Filters['ClassID']))
				{
					if (!empty($Filters['ClassSectionID']))
					{
						$Conditions[] = 'ae.classSectionID = ' . $DBConnObject->RealEscapeVariable($Filters['ClassSectionID']);
					}
					else
					{
						$Conditions[] = 'ac.classID = ' . $DBConnObject->RealEscapeVariable($Filters['ClassID']);
					}
				}
				
				if (!empty($Filters['ExamTypeID']))
				{
					$Conditions[] = 'aet.examTypeID = ' . $DBConnObject->RealEscapeVariable($Filters['ExamTypeID']);
				}
			}
			
			$QueryString = '';
			if (count($Conditions) > 0)
			{
				$QueryString = ' WHERE ' . implode(" AND ", $Conditions);
			}
			
			if ($GetTotalsOnly)
			{
				$RSTotal = $DBConnObject->Prepare('SELECT COUNT(DISTINCT(ast.studentID)) AS totalRecords 
													FROM aem_exams ae 
													INNER JOIN aem_exam_types aet ON aet.examTypeID = ae.examTypeID 
													INNER JOIN asa_class_sections cs ON cs.classSectionID = ae.classSectionID 
													INNER JOIN asa_classes ac ON ac.classID = cs.classID 
													INNER JOIN asa_section_master asm ON asm.sectionMasterID = cs.sectionMasterID 
													INNER JOIN asa_class_subjects acs ON acs.classSubjectID = ae.classSubjectID 
													INNER JOIN asa_subject_master asubm ON asubm.subjectID = acs.subjectID 
													INNER JOIN asa_students ast ON ast.classSectionID = cs.classSectionID 
													INNER JOIN asa_student_details asd ON asd.studentID = ast.studentID 
													INNER JOIN aem_student_exam_marks asem ON asem.examID = ae.examID AND asem.studentID = ast.studentID 
													' . $QueryString . ';');
				$RSTotal->Execute();
				
				$TotalRecords = $RSTotal->FetchRow()->totalRecords;
				return;
			}
			
			$RSExamDetail = $DBConnObject->Prepare('SELECT ae.examID, ae.examName, aet.examType, ac.className, asm.sectionName, asubm.subject, ae.maximumMarks, ae.isOffline, ae.examClosed, 
													ast.studentID, ast.rollNumber, asd.firstName, asd.lastName, 
													asem.marks 
													FROM aem_exams ae 
													INNER JOIN aem_exam_types aet ON aet.examTypeID = ae.examTypeID 
													INNER JOIN asa_class_sections cs ON cs.classSectionID = ae.classSectionID 
													INNER JOIN asa_classes ac ON ac.classID = cs.classID 
													INNER JOIN asa_section_master asm ON asm.sectionMasterID = cs.sectionMasterID 
													INNER JOIN asa_class_subjects acs ON acs.classSubjectID = ae.classSubjectID 
													INNER JOIN asa_subject_master asubm ON asubm.subjectID = acs.subjectID 
													INNER JOIN asa_students ast ON ast.classSectionID = cs.classSectionID 
													INNER JOIN asa_student_details asd ON asd.studentID = ast.studentID 
													INNER JOIN aem_student_exam_marks asem ON asem.examID = ae.examID AND asem.studentID = ast.studentID 
													' . $QueryString . ' 
													ORDER BY ae.examID, cs.classSectionID, asd.firstName LIMIT '.(int) $Start.', '.(int) $Limit.';');
			$RSExamDetail->Execute();
			
			while($ExamDetailRow = $RSExamDetail->FetchRow())
			{
				$StudentList[$ExamDetailRow->studentID]['ClassName'] = $ExamDetailRow->className;
				$StudentList[$ExamDetailRow->studentID]['SectionName'] = $ExamDetailRow->sectionName;
				
				$StudentList[$ExamDetailRow->studentID]['StudentID'] = $ExamDetailRow->studentID;
				$StudentList[$ExamDetailRow->studentID]['FirstName'] = $ExamDetailRow->firstName;
				$StudentList[$ExamDetailRow->studentID]['LastName'] = $ExamDetailRow->lastName;
				$StudentList[$ExamDetailRow->studentID]['RollNumber'] = $ExamDetailRow->rollNumber;
				
				$StudentList[$ExamDetailRow->studentID]['SubjectMarksList'][$ExamDetailRow->examID]['Subject'] = $ExamDetailRow->subject;
				$StudentList[$ExamDetailRow->studentID]['SubjectMarksList'][$ExamDetailRow->examID]['MaximumMarks'] = $ExamDetailRow->maximumMarks;
				$StudentList[$ExamDetailRow->studentID]['SubjectMarksList'][$ExamDetailRow->examID]['Marks'] = $ExamDetailRow->marks;
			}
			
			return $StudentList;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at Exam::SearchExamReport(). Stack Trace: '.$e->getTraceAsString());
			return $StudentList;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at Exam::SearchExamReport(). Stack Trace: '.$e->getTraceAsString());
			return $StudentList;
		}
	}

	static function GetExamApplicableClasses($ExamTypeID)
	{
		$AllClasses = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT DISTINCT(ae.classSectionID), acs.classID, ac.className 
												FROM aem_exams ae 
												INNER JOIN asa_class_sections acs ON ae.classSectionID = acs.classSectionID 
												INNER JOIN asa_classes ac ON acs.classID = ac.classID 
												WHERE ae.examTypeID = :|1 ORDER BY ac.classID;');
			$RSSearch->Execute($ExamTypeID);
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllClasses;
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$AllClasses[$SearchRow->classID] = $SearchRow->className;
			}
			
			return $AllClasses;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at Exam::GetExamApplicableClasses(). Stack Trace: ' . $e->getTraceAsString());
			return $AllClasses;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at Exam::GetExamApplicableClasses(). Stack Trace: ' . $e->getTraceAsString());
			return $AllClasses;
		}		
	}

	static function GetExamApplicableClassSections($ExamTypeID, $ClassID)
	{
		$AllClassSections = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT acs.classSectionID, asm.sectionName
												FROM aem_exams ae 
												INNER JOIN asa_class_sections acs ON ae.classSectionID = acs.classSectionID 
												INNER JOIN asa_section_master asm ON acs.sectionMasterID = asm.sectionMasterID 
												WHERE ae.examTypeID = :|1 AND acs.classID = :|2 GROUP BY asm.sectionMasterID, ae.examTypeID;');
			$RSSearch->Execute($ExamTypeID, $ClassID);
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllClassSections;
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$AllClassSections[$SearchRow->classSectionID] = $SearchRow->sectionName;
			}
			
			return $AllClassSections;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at Exam::GetExamApplicableClassSections(). Stack Trace: ' . $e->getTraceAsString());
			return $AllClassSections;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at Exam::GetExamApplicableClassSections(). Stack Trace: ' . $e->getTraceAsString());
			return $AllClassSections;
		}		
	}

	static function GetExamSubjects($ExamTypeID, $ClassSectionID, $StudentID = 0, $GetNameOnly = false)
	{
		$AllSubjects = array();
		
		try
		{
			$DBConnObject = new DBConnect();

			if ($GetNameOnly) 
			{
				$RSSearch = $DBConnObject->Prepare('SELECT DISTINCT(ae.classSubjectID), asm.subject, ae.maximumMarks, 
													ae.examID, acs.subjectMarksType
													FROM aem_exams ae
													INNER JOIN asa_class_subjects acs ON ae.classSubjectID = acs.classSubjectID
													INNER JOIN asa_subject_master asm ON acs.subjectID = asm.subjectID
													LEFT JOIN aem_student_exam_marks asem ON ae.examID = asem.examID
													WHERE ae.examTypeID = :|1 AND ae.classSectionID = :|2 ORDER BY asm.subject;');
				$RSSearch->Execute($ExamTypeID, $ClassSectionID);

				if ($RSSearch->Result->num_rows <= 0)
				{
					return $AllSubjects;
				}
				
				while($SearchRow = $RSSearch->FetchRow())
				{
					$AllSubjects[$SearchRow->classSubjectID]['SubjectName'] = $SearchRow->subject;
					$AllSubjects[$SearchRow->classSubjectID]['MaximumMarks'] = $SearchRow->maximumMarks;
					$AllSubjects[$SearchRow->classSubjectID]['ExamID'] = $SearchRow->examID;
					$AllSubjects[$SearchRow->classSubjectID]['SubjectMarksType'] = $SearchRow->subjectMarksType;
				}

				return $AllSubjects;
			}
			
			$RSSearch = $DBConnObject->Prepare('SELECT DISTINCT(ae.classSectionID), ae.examID, ae.maximumMarks, ae.classSubjectID, 
												asm.subject, asem.marks, asem.gradeID, asem.status, acs.subjectMarksType
												FROM aem_exams ae
												INNER JOIN asa_class_subjects acs ON ae.classSubjectID = acs.classSubjectID
												INNER JOIN asa_subject_master asm ON acs.subjectID = asm.subjectID
												LEFT JOIN aem_student_exam_marks asem ON ae.examID = asem.examID
												WHERE ae.examTypeID = :|1 AND ae.classSectionID = :|2 AND asem.studentID = :|3;');
			$RSSearch->Execute($ExamTypeID, $ClassSectionID, $StudentID);
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllSubjects;
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{	
				$AllSubjects[$SearchRow->classSubjectID]['SubjectMarksType'] = $SearchRow->subjectMarksType;
				$AllSubjects[$SearchRow->classSubjectID]['SubjectName'] = $SearchRow->subject;
				$AllSubjects[$SearchRow->classSubjectID]['Marks'] = $SearchRow->marks;
				$AllSubjects[$SearchRow->classSubjectID]['GradeID'] = $SearchRow->gradeID;
				$AllSubjects[$SearchRow->classSubjectID]['Status'] = $SearchRow->status;
			}
			
			return $AllSubjects;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at Exam::GetExamSubjects(). Stack Trace: ' . $e->getTraceAsString());
			return $AllSubjects;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at Exam::GetExamSubjects(). Stack Trace: ' . $e->getTraceAsString());
			return $AllSubjects;
		}		
	}
    
    static function GetReportCardSubject($ClassSectionID)
	{
		$AllSubjects = array();
		
		try
		{
			$DBConnObject = new DBConnect();

				$RSSearch = $DBConnObject->Prepare('SELECT DISTINCT(ae.classSubjectID)
													FROM aem_exams ae
													WHERE ae.classSectionID = :|1;');
				$RSSearch->Execute($ClassSectionID);

				if ($RSSearch->Result->num_rows <= 0)
				{
					return $AllSubjects;
				}
				
				while($SearchRow = $RSSearch->FetchRow())
				{
					$AllSubjects[$SearchRow->classSubjectID] = $SearchRow->classSubjectID;
				}
				return $AllSubjects;
				
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at Exam::GetReportCardSubject(). Stack Trace: ' . $e->getTraceAsString());
			return $AllSubjects;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at Exam::GetReportCardSubject(). Stack Trace: ' . $e->getTraceAsString());
			return $AllSubjects;
		}		
	}
	
	static function GetOfflineExams()
	{
		$OfflineExams = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT examID, examName FROM aem_exams WHERE isOffline = 1;');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $OfflineExams;
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$OfflineExams[$SearchRow->examID] = $SearchRow->examName;				
			}
			
			return $OfflineExams;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at Exam::GetOfflineExams(). Stack Trace: ' . $e->getTraceAsString());
			return $OfflineExams;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at Exam::GetOfflineExams(). Stack Trace: ' . $e->getTraceAsString());
			return $OfflineExams;
		}		
	}
	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//

	private function SaveDetails()
	{
		if ($this->ExamID == 0)
		{
			foreach ($this->ClassSectionList as $ClassSectionID => $ClassSectionID) 
			{
				foreach ($this->MaximumMarkDetails as $ClassSubjectID => $MaximumMarks) 
				{
					$RSSave = $this->DBObject->Prepare('INSERT INTO aem_exams (examTypeID, classSectionID, classSubjectID, examName, maximumMarks, isOffline, createUserID, createDate)
																VALUES (:|1, :|2, :|3, :|4, :|5, :|6, :|7, NOW());');
				
					$RSSave->Execute($this->ExamTypeID, $ClassSectionID, $ClassSubjectID, $this->ExamName, $MaximumMarks, $this->IsOffline, $this->CreateUserID);
				}
			}
		}
		else
		{
			$RSUpdate = $this->DBObject->Prepare('UPDATE aem_exams
													SET	examTypeID = :|1,
														classSectionID = :|2,
														classSubjectID = :|3,
														examName = :|4,
														maximumMarks = :|5,
														isOffline = :|6
													WHERE examID = :|7 LIMIT 1;');
													
			$RSUpdate->Execute($this->ExamTypeID, $this->ClassSectionID, $this->ClassSubjectID, $this->ExamName, $this->MaximumMarks, $this->IsOffline, $this->ExamID);
		}
		
		return true;
	}
	
	private function RemoveExam()
    {
        if(!isset($this->ExamID)) 
        {
            throw new ApplicationARException('', APP_AR_ERROR_DELETE_WITHOUT_ID);
        }
        
        $RSDeleteExam = $this->DBObject->Prepare('DELETE FROM aem_exams WHERE examID = :|1 LIMIT 1;');
        $RSDeleteExam->Execute($this->ExamID);  

        return true;              
    }

	private function GetExamByID()
	{
		$RSExam = $this->DBObject->Prepare('SELECT * FROM aem_exams WHERE examID = :|1 LIMIT 1;');
		$RSExam->Execute($this->ExamID);
		
		$ExamRow = $RSExam->FetchRow();
		
		$this->SetAttributesFromDB($ExamRow);				
	}
	
	private function SetAttributesFromDB($ExamRow)
	{
		$this->ExamID = $ExamRow->examID;
		$this->ExamTypeID = $ExamRow->examTypeID;
		$this->ClassSectionID = $ExamRow->classSectionID;
		$this->ClassSubjectID = $ExamRow->classSubjectID;

		$this->ExamName = $ExamRow->examName;
		$this->MaximumMarks = $ExamRow->maximumMarks;
		$this->IsOffline = $ExamRow->isOffline;
		$this->ExamClosed = $ExamRow->examClosed;

		$this->CreateUserID = $ExamRow->createUserID;
		$this->CreateDate = $ExamRow->createDate;
	}	
}
?>
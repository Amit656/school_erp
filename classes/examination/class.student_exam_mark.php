<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.db_connect.php');
error_reporting(E_ALL);

class StudentExamMark
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	
	private $StudentExamMarkID;
	private $ExamID;

	private $CreateUserID;
	private $CreateDate;

	private $Obtainedmarks = array();
	
	// PUBLIC METHODS START HERE	//
	public function __construct($StudentExamMarkID = 0)
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;
		
		if($StudentExamMarkID != 0)
		{
			$this->StudentExamMarkID = $StudentExamMarkID;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetStudentExamMarkByID();
		}
		else
		{
			//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
			$this->StudentExamMarkID = 0;
			$this->ExamID = 0;
			$this->CreateUserID = 0;
			$this->CreateDate = '0000-00-00 00:00:00';

			$this->Obtainedmarks = array();
		}
	}
	
	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetStudentExamMarkID()
	{
		return $this->StudentExamMarkID;
	}

	public function GetExamID()
	{
		return $this->ExamID;
	}
	public function SetExamID($ExamID)
	{
		$this->ExamID = $ExamID;
	}

	public function GetObtainedmarks()
	{
		return $this->Obtainedmarks;
	}
	public function SetObtainedmarks($Obtainedmarks)
	{
		$this->Obtainedmarks = $Obtainedmarks;
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

	static function FillObtainedMarks($ExamID)
	{
		$Obtainedmarks = array();

		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT * FROM aem_student_exam_marks WHERE examID = :|1;');
			
			$RSSearch->Execute($ExamID);

			if ($RSSearch->Result->num_rows <= 0) 
			{
				return $Obtainedmarks;	
			}

			while ($SearchRow = $RSSearch->FetchRow()) 
			{
				$Obtainedmarks[$SearchRow->studentID]['Marks'] = $SearchRow->marks;
                $Obtainedmarks[$SearchRow->studentID]['Status'] = $SearchRow->status;
			}

			return $Obtainedmarks;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at StudentExamMark::FillObtainedMarks(). Stack Trace: '.$e->getTraceAsString());
			return $Obtainedmarks;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at StudentExamMark::FillObtainedMarks(). Stack Trace: '.$e->getTraceAsString());
			return $Obtainedmarks;
		}
	}

	static function GetStudentAllSubjectMarks($ExamTypeID, $ClassSectionID, $StudentID = 0, $ClassSubjectID = 0)
	{
		$AllSubjectsMarks = array();

		$QueryString = '';

		try
		{
			$DBConnObject = new DBConnect();

			if ($StudentID > 0)
			{
				$QueryString = ' AND asem.studentID = ' . $DBConnObject->RealEscapeVariable($StudentID);
			}

			if ($ClassSubjectID > 0)
			{
				$QueryString = ' AND ae.classSubjectID = ' . $DBConnObject->RealEscapeVariable($ClassSubjectID);
			}

			$RSSearch = $DBConnObject->Prepare('SELECT DISTINCT(ae.classSectionID), ae.examID, ae.maximumMarks, ae.classSubjectID, 
												asm.subject, asem.marks, asem.gradeID, asem.status, acs.subjectMarksType, ag.grade
												FROM aem_exams ae
												INNER JOIN asa_class_subjects acs ON ae.classSubjectID = acs.classSubjectID
												INNER JOIN asa_subject_master asm ON acs.subjectID = asm.subjectID
												LEFT JOIN aem_student_exam_marks asem ON ae.examID = asem.examID
												LEFT JOIN asa_grades ag ON asem.gradeID = ag.gradeID
												WHERE ae.examTypeID = :|1 AND ae.classSectionID = :|2 ' . $QueryString . ';');
			$RSSearch->Execute($ExamTypeID, $ClassSectionID);
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllSubjectsMarks;
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{	
				$AllSubjectsMarks[$SearchRow->classSubjectID]['SubjectMarksType'] = $SearchRow->subjectMarksType;
				$AllSubjectsMarks[$SearchRow->classSubjectID]['Marks'] = $SearchRow->marks;
				$AllSubjectsMarks[$SearchRow->classSubjectID]['GradeID'] = $SearchRow->gradeID;
				$AllSubjectsMarks[$SearchRow->classSubjectID]['Status'] = $SearchRow->status;
				$AllSubjectsMarks[$SearchRow->classSubjectID]['Grade'] = $SearchRow->grade;
			}
			
			return $AllSubjectsMarks;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at StudentExamMark::GetStudentAllSubjectMarks(). Stack Trace: ' . $e->getTraceAsString());
			return $AllSubjectsMarks;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at StudentExamMark::GetStudentAllSubjectMarks(). Stack Trace: ' . $e->getTraceAsString());
			return $AllSubjectsMarks;
		}
	}

	static function GetStudentSubjectMarks($ExamTypeID, $ClassSubjectID, $StudentID)
	{
		$StudentSubjectMarks = array();

		try
		{
			$DBConnObject = new DBConnect();

			$RSSearch = $DBConnObject->Prepare('SELECT asem.marks, asem.gradeID, asem.status, ag.grade, ae.classSubjectID, ae.maximumMarks
												FROM aem_student_exam_marks asem
												INNER JOIN aem_exams ae ON asem.examID = ae.examID
												LEFT JOIN asa_grades ag ON asem.gradeID = ag.gradeID
												WHERE ae.examTypeID = :|1 AND ae.classSubjectID = :|2 AND asem.studentID = :|3;');
			$RSSearch->Execute($ExamTypeID, $ClassSubjectID, $StudentID);
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $StudentSubjectMarks;
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{	
				$StudentSubjectMarks[$SearchRow->classSubjectID]['Marks'] = $SearchRow->marks;
				$StudentSubjectMarks[$SearchRow->classSubjectID]['GradeID'] = $SearchRow->gradeID;
				$StudentSubjectMarks[$SearchRow->classSubjectID]['Status'] = $SearchRow->status;
				$StudentSubjectMarks[$SearchRow->classSubjectID]['Grade'] = $SearchRow->grade;
				$StudentSubjectMarks[$SearchRow->classSubjectID]['MaximumMarks'] = $SearchRow->maximumMarks;
			}

			return $StudentSubjectMarks;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at StudentExamMark::GetStudentSubjectMarks(). Stack Trace: '.$e->getTraceAsString());
			return $StudentSubjectMarks;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at StudentExamMark::GetStudentSubjectMarks(). Stack Trace: '.$e->getTraceAsString());
			return $StudentSubjectMarks;
		}
	}

	static function GetStudentRankInClass($ExamTypeID, $ClassSectionID, $MininumMarks, $StudentID)
	{
		$Rank =  1;
		try
		{
			$DBConnObject = new DBConnect();

			$RSSearch = $DBConnObject->Prepare('SELECT SUM(asem.marks) AS totalMarks, asem.studentID
												FROM aem_student_exam_marks asem
												INNER JOIN aem_exams ae ON asem.examID = ae.examID
												WHERE ae.examTypeID = :|1 AND ae.classSectionID = :|2 
												AND asem.studentID  NOT IN (SELECT sem.studentID FROM aem_student_exam_marks sem INNER JOIN aem_exams e ON sem.examID = e.examID WHERE examTypeID = :|3  AND marks < :|4 AND gradeID = 0) 
												GROUP BY asem.studentID
												ORDER BY totalMarks DESC;');
			$RSSearch->Execute($ExamTypeID, $ClassSectionID, $ExamTypeID, $MininumMarks);
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $Rank;
			}
			
			$TotalMarks = 0;
			while($SearchRow = $RSSearch->FetchRow())
			{	
			    if ($TotalMarks == $SearchRow->totalMarks)
			    {
			        $Rank--;
			    }
				
				if ($SearchRow->studentID == $StudentID) 
				{
					return $Rank;
				}
				
				$TotalMarks = $SearchRow->totalMarks;

				$Rank++;
			}
			return false;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at StudentExamMark::GetStudentRankInClass(). Stack Trace: '.$e->getTraceAsString());
			return false;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at StudentExamMark::GetStudentRankInClass(). Stack Trace: '.$e->getTraceAsString());
			return false;
		}
	}

	static function SaveStudentSubjectMarks($StudentSubjectMarks, $CreateUserID)
	{
		try
		{
			$DBConnObject = new DBConnect();

			$DBConnObject->BeginTransaction();

			foreach ($StudentSubjectMarks as $StudentID => $Details) 
			{
				foreach ($Details as $ClassSubjectID => $SubjectMarkDetails) 
				{
					$RSDelete = $DBConnObject->Prepare('DELETE FROM aem_student_exam_marks WHERE examID = :|1 AND studentID = :|2;');
			
					$RSDelete->Execute($SubjectMarkDetails['ExamID'], $StudentID);

					$RSSave = $DBConnObject->Prepare('INSERT INTO aem_student_exam_marks (examID, studentID, marks, gradeID, status, createUserID, createDate)
																						VALUES (:|1, :|2, :|3, :|4, :|5, :|6, NOW());');
			
					$RSSave->Execute($SubjectMarkDetails['ExamID'], $StudentID, $SubjectMarkDetails['SubjectMarks'], $SubjectMarkDetails['GradeID'], $SubjectMarkDetails['ClassSubjectStudentStatus'], $CreateUserID);
				}
			}

			$DBConnObject->CommitTransaction();

			return true;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at StudentExamMark::SaveStudentSubjectMarks(). Stack Trace: '.$e->getTraceAsString());
			$DBConnObject->RollBackTransaction();
			return false;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at StudentExamMark::SaveStudentSubjectMarks(). Stack Trace: '.$e->getTraceAsString());
			$DBConnObject->RollBackTransaction();
			return false;
		}
	}

	// END OF PUBLIC METHODS	//
	
	// START OF STATIC METHODS	//
	
	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//
	private function SaveDetails()
	{
		if ($this->StudentExamMarkID == 0)
		{	
			$RSDelete = $this->DBObject->Prepare('DELETE FROM aem_student_exam_marks WHERE examID = :|1;');
			$RSDelete->Execute($this->ExamID);
			
			foreach ($this->Obtainedmarks as $StudentID => $Marks) 
			{
				$RSSave = $this->DBObject->Prepare('INSERT INTO aem_student_exam_marks (examID, studentID, marks, createUserID, createDate)
														VALUES (:|1, :|2, :|3, :|4, NOW());');
		
				$RSSave->Execute($this->ExamID, $StudentID, $Marks, $this->CreateUserID);
				
				$this->StudentExamMarkID = $RSSave->LastID;
			}
		}
		else
		{
			$RSUpdate = $this->DBObject->Prepare('UPDATE aem_student_exam_marks
													SET	examID = :|1
														studentID = :|2,
														marks = :|3
													WHERE studentExamMarkID = :|4 LIMIT 1;');
													
			$RSUpdate->Execute($this->ExamID, $this->StudentID, $this->ExamID, $this->StudentExamMarkID);
		}
		
		return true;
	}
	
	private function GetStudentExamMarkByID()
	{
		$RSStudentExamMark = $this->DBObject->Prepare('SELECT * FROM aem_student_exam_marks WHERE studentExamMarkID = :|1 LIMIT 1;');
		$RSStudentExamMark->Execute($this->StudentExamMarkID);
		
		$StudentExamMarkRow = $RSStudentExamMark->FetchRow();
		
		$this->SetAttributesFromDB($StudentExamMarkRow);				
	}
	
	private function SetAttributesFromDB($StudentExamMarkRow)
	{   
		$this->StudentExamMarkID = $StudentExamMarkRow->studentExamMarkID;
		$this->ExamID = $StudentExamMarkRow->examID;

		$this->CreateUserID = $ExamRow->createUserID;
		$this->CreateDate = $ExamRow->createDate;
	}	
}
?>
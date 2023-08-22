<?php
require_once("PHPExcel/PHPExcel.php");

set_time_limit(7200);

try
{
    $ExamTypeObject = new ExamType($Clean['ExamTypeID']);
}
catch (ApplicationDBException $e)
{
    header('location:/admin/error.php');
    exit;
}
catch (Exception $e)
{
    header('location:/admin/error.php');
    exit;
}

try
{
    $CurrentClassSections = new ClassSections($Clean['ClassSectionID']);
    $CurrentClass = new AddedClass($CurrentClassSections->GetClassID());
}
catch (ApplicationDBException $e)
{
    header('location:admin/error.php');
    exit;
}
catch (Exception $e)
{
    header('location:admin/error.php');
    exit;
}

try
{
    $CurrentSectionMaster = new SectionMaster($CurrentClassSections->GetSectionMasterID());
}
catch (ApplicationDBException $e)
{
    header('location:admin/error.php');
    exit;
}
catch (Exception $e)
{
    header('location:admin/error.php');
    exit;
}

$excelWriter = new PHPExcel();
$excelWriter->getProperties()->setCreator("Added")
        ->setLastModifiedBy("Added")
        ->setTitle($ExamTypeObject->GetExamType(). '-'. $CurrentClass->GetClassName() .' - ' . $CurrentSectionMaster->GetSectionName())
        ->setSubject($ExamTypeObject->GetExamType(). '-'. $CurrentClass->GetClassName() .' - ' . $CurrentSectionMaster->GetSectionName())
        ->setDescription('');

// Set the active Excel worksheet to sheet 0 
$excelWriter->setActiveSheetIndex(0);  

// Initialise the Excel row number 
$rowCount = 1;  

//start of printing column names as names of MySQL fields  
$column = 'A';

$excelWriter->getActiveSheet()->setCellValue($column.$rowCount, 'Student Name');
$column++;

foreach ($AllSubjects as $Details)
{
    $excelWriter->getActiveSheet()->setCellValue($column.$rowCount, $Details['SubjectName'] . " MM. " . $Details['MaximumMarks']);
    $column++;
}

//start while loop to get data  
$rowCount = 2;  
 
foreach ($AllStudentList as $StudentID => $StudentDetails)
{
    $column = 'A';

    $excelWriter->getActiveSheet()->setCellValue($column.$rowCount, $StudentDetails['FirstName'] . ' ' . $StudentDetails['LastName'] .' ('.$StudentDetails['RollNumber'].')');
    $column++;

    $SubjectMarks = StudentExamMark::GetStudentAllSubjectMarks($Clean['ExamTypeID'], $Clean['ClassSectionID'], $StudentID);
    
    foreach ($AllSubjects as $KeyClassSubjectID => $ClassSubjectName) 
    {   
        $SubjectCounter = 0;
        
        foreach ($SubjectMarks as $ClassSubjectID => $SubjectMarksDetails) 
        {
            if ($KeyClassSubjectID == $ClassSubjectID) 
			{	
				$SubjectCounter = 1;
				
				if ($SubjectMarksDetails['Status'] == 'Absent' || $SubjectMarksDetails['Status'] == 'Medical') 
                {
                    if ($ClassSubjectID == $KeyClassSubjectID) 
                    {
                        $excelWriter->getActiveSheet()->setCellValue($column.$rowCount, $SubjectMarksDetails['Status']);
                        $column++;
                    }                                       
                }
                elseif ($SubjectMarksDetails['SubjectMarksType'] == 'Grade') 
                {
                    if ($ClassSubjectID == $KeyClassSubjectID) 
                    {
                        $excelWriter->getActiveSheet()->setCellValue($column.$rowCount, $SubjectMarksDetails['Grade']);
                        $column++;
                    }
                }
                elseif ($SubjectMarksDetails['SubjectMarksType'] == 'Number') 
                {
                    if ($ClassSubjectID == $KeyClassSubjectID) 
                    {
                        $excelWriter->getActiveSheet()->setCellValue($column.$rowCount, $SubjectMarksDetails['Marks']);
                        $column++;
                    }
                }
			}
        } 
        
        if ($SubjectCounter == 0) 
	    {
	        $excelWriter->getActiveSheet()->setCellValue($column.$rowCount, '-');
            $column++;
	    }
    }

    $rowCount++;
}

$excelWriter->getActiveSheet()->setTitle('students_subject_marks');
// Set active sheet index to the first sheet, so Excel opens this as the first sheet
$excelWriter->setActiveSheetIndex(0);

// Redirect output to a clientâ€™s web browser (Excel5)
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename='.$ExamTypeObject->GetExamType(). '-'. $CurrentClass->GetClassName() .' - ' . $CurrentSectionMaster->GetSectionName().'.xls');
header('Cache-Control: max-age=0');
// If you're serving to IE 9, then the following may be needed
header('Cache-Control: max-age=1');

// If you're serving to IE over SSL, then the following may be needed
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
header('Pragma: public'); // HTTP/1.0

$objWriter = PHPExcel_IOFactory::createWriter($excelWriter, 'Excel5');
$objWriter->save('php://output');
exit;
?>
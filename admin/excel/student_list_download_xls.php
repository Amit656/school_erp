<?php
require_once("PHPExcel/PHPExcel.php");

set_time_limit(7200);

if (!isset($TotalRecords) && $TotalRecords <= 0)
{
    die('No Data Found');
}

$AllStudents = array();

$AllStudents = StudentDetail::GetAllStudents($TotalRecords, false, $Filters, $Start, $TotalRecords);

$excelWriter = new PHPExcel();
$excelWriter->getProperties()->setCreator("Added")
        ->setLastModifiedBy("Added")
        ->setTitle('Students List')
        ->setSubject('Students List')
        ->setDescription('');
$excelWriter->getActiveSheet()
        ->getStyle('A1:M1')
        ->getFont()->setBold(true)
        ->setSize(16);

$excelWriter->setActiveSheetIndex(0)
        ->setCellValue('A1', 'S. No.')
        ->setCellValue('B1', 'Sr. No.')
		->setCellValue('C1', 'Student Number')
        ->setCellValue('D1', 'Roll Number')
        ->setCellValue('E1', 'Class Section')
        ->setCellValue('F1', 'Father Name')
        ->setCellValue('G1', 'Mother Name')
        ->setCellValue('H1', 'Gender')
        ->setCellValue('I1', 'DOB')
        ->setCellValue('J1', 'FContactNo')
        ->setCellValue('K1', 'MContactNo')
        ->setCellValue('L1', 'Fee Code')
        ->setCellValue('M1', 'Status')
        ->setCellValue('N1', 'Address');

$excelWriter->getActiveSheet()->getStyle('A1:O1')->getFont()->setBold(true);
$excelWriter->getActiveSheet()->getStyle('A1:O1')->getFont()->getColor()->setARGB('FFFFFFFF');
$excelWriter->getActiveSheet()->getStyle('A1:O1')->applyFromArray(
        array('fill' => array(
                'type' => PHPExcel_Style_Fill::FILL_SOLID,
                'color' => array('argb' => 'FF2F88F0')
            )
        )
);

for ($col = 'A'; $col !== 'N'; $col++)
{
    $excelWriter->getActiveSheet()
            ->getColumnDimension($col)
            ->setAutoSize(true);
}

$index = 2;
$TotalValue = 0;
$TotalPlotSize = 0;

foreach($AllStudents as $StudentID => $StudentDetails)
{
    ++$index;

    $excelWriter->setActiveSheetIndex(0)
            ->setCellValueExplicit('A' . $index, $index-2, PHPExcel_Cell_DataType::TYPE_STRING)
            ->setCellValueExplicit('B' . $index, $StudentDetails['EnrollmentID'], PHPExcel_Cell_DataType::TYPE_STRING)
			->setCellValueExplicit('C' . $index, $StudentDetails['FirstName'] . ' ' . $StudentDetails['LastName'], PHPExcel_Cell_DataType::TYPE_STRING)
			->setCellValueExplicit('D' . $index, $StudentDetails['RollNumber'], PHPExcel_Cell_DataType::TYPE_STRING)
			->setCellValueExplicit('E' . $index, $StudentDetails['ClassSymbol'] . ' ' . $StudentDetails['SectionName'], PHPExcel_Cell_DataType::TYPE_STRING)
			->setCellValueExplicit('F' . $index, $StudentDetails['FatherFirstName'].' '.$StudentDetails['FatherLastName'], PHPExcel_Cell_DataType::TYPE_STRING)
			->setCellValueExplicit('G' . $index, $StudentDetails['MotherFirstName'].' '.$StudentDetails['MotherLastName'], PHPExcel_Cell_DataType::TYPE_STRING)
			->setCellValueExplicit('H' . $index, $StudentDetails['Gender'], PHPExcel_Cell_DataType::TYPE_STRING)
			->setCellValueExplicit('I' . $index, date('d/m/Y', strtotime($StudentDetails['Dob'])), PHPExcel_Cell_DataType::TYPE_STRING)
            ->setCellValueExplicit('J' . $index, $StudentDetails['FatherMobileNumber'], PHPExcel_Cell_DataType::TYPE_STRING)
            ->setCellValueExplicit('K' . $index, $StudentDetails['MotherMobileNumber'], PHPExcel_Cell_DataType::TYPE_STRING)
            ->setCellValueExplicit('L' . $index, $StudentDetails['FeeCode'], PHPExcel_Cell_DataType::TYPE_STRING)
            ->setCellValueExplicit('M' . $index, $StudentDetails['Status'], PHPExcel_Cell_DataType::TYPE_STRING)
            ->setCellValueExplicit('N' . $index, $StudentDetails['Address'], PHPExcel_Cell_DataType::TYPE_STRING);

    if ($index % 2 == 0)
    {
        $excelWriter->getActiveSheet()->getStyle('A' . $index . ':O' . $index)->applyFromArray(
                array('fill' => array(
                        'type' => PHPExcel_Style_Fill::FILL_SOLID,
                        'color' => array('argb' => 'FFE5ECF5')
                    )
                )
        );
    }
}

++$index;

$excelWriter->getActiveSheet()->getStyle('A1:O' . $index)->applyFromArray(
        array(
            'borders' => array(
                'bottom' => array('style' => PHPExcel_Style_Border::BORDER_THIN),
                'right' => array('style' => PHPExcel_Style_Border::BORDER_MEDIUM)
            )
        )
);

$excelWriter->getActiveSheet()->setTitle('student_list');
// Set active sheet index to the first sheet, so Excel opens this as the first sheet
$excelWriter->setActiveSheetIndex(0);

// Redirect output to a clientâ€™s web browser (Excel5)
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename=student_list.xls');
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
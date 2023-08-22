<?php
require_once("PHPExcel/PHPExcel.php");

set_time_limit(7200);

$StartDate = Helpers::GetStartDateOfTheMonth($AllAcademicYearMonths[$Clean['MonthlyAttedanceRegisterMonth']]['MonthShortName']);

$WorkingDayStartDate = date('Y-m-d', strtotime($StartDate));
$WorkingDaysEndDate = date('Y-m-t', strtotime($StartDate));

$AlphabetsList = array();
$AlphabetsList = range('E', 'Z');

$ExcelColumns = array();
$ExcelColumns['A'] = 'S. No.';
$ExcelColumns['B'] = 'Student Name';
$ExcelColumns['C'] = 'Student Class';
$ExcelColumns['D'] = 'Student Section';

$Date = $WorkingDayStartDate;
while (strtotime($Date) <= strtotime($WorkingDaysEndDate))
{
	if (empty($AlphabetsList))
	{
		if (!isset($NewAlphabetsList))
		{
			$NewAlphabetsList = range('A', 'Z');
		}
		
		$Key = 'A' . array_shift($NewAlphabetsList);
	}
	else
	{
		$Key = array_shift($AlphabetsList);
	}
	
	$ExcelColumns[$Key] = date('d', strtotime($Date));
	$Date = date('Y-m-d', strtotime('+1 Day', strtotime($Date)));
}

$Key = 'A' . array_shift($NewAlphabetsList);
$ExcelColumns[$Key] = 'WD';

$Key = 'A' . array_shift($NewAlphabetsList);
$ExcelColumns[$Key] = 'PD';

$ExcelLastColumnName = array_search(end($ExcelColumns), $ExcelColumns);

/*if (count($StudentsList) <= 0)
{
    die('No Data Found');
}*/

$MonthlyAttendanceList = array();

$excelWriter = new PHPExcel();

$excelWriter->getProperties()->setCreator("Added")
        ->setLastModifiedBy("Added")
        ->setTitle('Monthly Attendance Register')
        ->setSubject('Monthly Attendance Register')
        ->setDescription('');

$excelWriter->getActiveSheet()
        ->getStyle('A1:' . $ExcelLastColumnName . '1')
        ->getFont()->setBold(true)
        ->setSize(16);

foreach ($ExcelColumns as $ColumnName => $ColumnValue)
{
	$excelWriter->setActiveSheetIndex(0)->setCellValue($ColumnName . '1', $ColumnValue);
}

$excelWriter->getActiveSheet()->getStyle('A1:' . $ExcelLastColumnName . '1')->getFont()->setBold(true);
$excelWriter->getActiveSheet()->getStyle('A1:' . $ExcelLastColumnName . '1')->getFont()->getColor()->setARGB('FFFFFFFF');
$excelWriter->getActiveSheet()->getStyle('A1:' . $ExcelLastColumnName . '1')->applyFromArray(
        array('fill' => array(
                'type' => PHPExcel_Style_Fill::FILL_SOLID,
                'color' => array('argb' => 'FF2F88F0')
            )
        )
);

for ($col = 'A'; $col !== $ExcelLastColumnName; $col++)
{
    $excelWriter->getActiveSheet()
            ->getColumnDimension($col)
            ->setAutoSize(true);
}

$index = 2;

foreach ($AllClasses as $ClassID => $ClassName)
{
	$ClassSectionsList = AddedClass::GetClassSections($ClassID);
	foreach ($ClassSectionsList as $ClassSectionID => $ClassSectionName)
	{
		$AllWorkingDays = Helpers::GetClassWorkingDays($WorkingDayStartDate, $WorkingDaysEndDate, $ClassSectionID);

		$StudentsList = StudentDetail::GetStudentsByClassSectionID($ClassSectionID, 'Active', $Clean['AcademicYearID']);
		foreach($StudentsList as $StudentID => $StudentDetails)
		{
		    ++$index;

			$excelWriter->setActiveSheetIndex(0)->setCellValueExplicit('A' . $index, $index-2, PHPExcel_Cell_DataType::TYPE_STRING);
			$excelWriter->setActiveSheetIndex(0)->setCellValueExplicit('B' . $index, $StudentDetails['FirstName'] . ' ' . $StudentDetails['LastName'], PHPExcel_Cell_DataType::TYPE_STRING);
			$excelWriter->setActiveSheetIndex(0)->setCellValueExplicit('C' . $index, $ClassName, PHPExcel_Cell_DataType::TYPE_STRING);
			$excelWriter->setActiveSheetIndex(0)->setCellValueExplicit('D' . $index, $ClassSectionName, PHPExcel_Cell_DataType::TYPE_STRING);
			
			$TotalWorkingDays = 0;
		    $StudentTotalMonthlyPresent = 0;
		    
			$Date = $WorkingDayStartDate;
			while (strtotime($Date) <= strtotime($WorkingDaysEndDate)) 
			{
				$CellName = array_search(date('d', strtotime($Date)), $ExcelColumns);
				$CellValue = '';
				
				if (date('N', strtotime($Date)) > 6)
				{
					$Date = date('Y-m-d', strtotime('+1 Day', strtotime($Date)));
					$CellValue = 'S';
					$excelWriter->setActiveSheetIndex(0)->setCellValueExplicit($CellName . $index, $CellValue, PHPExcel_Cell_DataType::TYPE_STRING);
					continue;
				}
				else if (strtotime($Date) > time())
				{
					$Date = date('Y-m-d', strtotime('+1 Day', strtotime($Date)));
					$CellValue = '';
					$excelWriter->setActiveSheetIndex(0)->setCellValueExplicit($CellName . $index, $CellValue, PHPExcel_Cell_DataType::TYPE_STRING);
					continue;
				}
				
				$TotalWorkingDays++;

				$MonthlyAttendanceList = ClassAttendence::GetClassWiseMonthlyAttendance($WorkingDayStartDate, $WorkingDaysEndDate, $ClassSectionID, $StudentID);
				$Counter = 0;

				$CellValue = 'A';
				
				foreach ($MonthlyAttendanceList as $ClassAttendenceDetailID => $MonthlyAttendanceDetails)
				{          
					if ($Date == $MonthlyAttendanceDetails['AttendenceDate'] && $MonthlyAttendanceDetails['Status'] == 'Present') 
					{
					    $StudentTotalMonthlyPresent++;
					    
						$Counter = 1;

						$CellValue = 'P';
					}
				}

				if ($Counter == 0) 
				{
					if (!Helpers::GetIsClassAttendanceDateIsWorkingDate($WorkingDayStartDate, $WorkingDaysEndDate, $ClassSectionID, $Date))
					{
					    $TotalWorkingDays--;
						$CellValue = 'H';
					}
				}
				
				$excelWriter->setActiveSheetIndex(0)->setCellValueExplicit($CellName . $index, $CellValue, PHPExcel_Cell_DataType::TYPE_STRING);

				$Date = date('Y-m-d', strtotime('+1 Day', strtotime($Date)));
			}
			
			$CellName = array_search('WD', $ExcelColumns);
			$excelWriter->setActiveSheetIndex(0)->setCellValueExplicit($CellName . $index, $TotalWorkingDays, PHPExcel_Cell_DataType::TYPE_STRING);
			
			$CellName = array_search('PD', $ExcelColumns);
			$excelWriter->setActiveSheetIndex(0)->setCellValueExplicit($CellName . $index, $StudentTotalMonthlyPresent, PHPExcel_Cell_DataType::TYPE_STRING);
			
		    if ($index % 2 == 0)
		    {
		        $excelWriter->getActiveSheet()->getStyle('A' . $index . ':' . $ExcelLastColumnName . $index)->applyFromArray(
		                array('fill' => array(
		                        'type' => PHPExcel_Style_Fill::FILL_SOLID,
		                        'color' => array('argb' => 'FFE5ECF5')
		                    )
		                )
		        );
		    }
		}
	}
}

++$index;

$excelWriter->getActiveSheet()->getStyle('A1:' . $ExcelLastColumnName . ($index - 1))->applyFromArray(
        array(
            'borders' => array(
                'bottom' => array('style' => PHPExcel_Style_Border::BORDER_THIN),
                'right' => array('style' => PHPExcel_Style_Border::BORDER_MEDIUM)
            )
        )
);

$excelWriter->getActiveSheet()->setTitle('monthly_attendance_register');


// Set active sheet index to the first sheet, so Excel opens this as the first sheet
$excelWriter->setActiveSheetIndex(0);

// Redirect output to a clientâ€™s web browser (Excel5)
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename=monthly_attendance_register.xls');
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
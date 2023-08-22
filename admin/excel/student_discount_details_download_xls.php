<?php
require_once("PHPExcel/PHPExcel.php");

set_time_limit(7200);

if (!isset($TotalRecords) && $TotalRecords <= 0)
{
    die('No Data Found');
}

$StudentDiscountDetails = array();
$StudentDiscountDetails = FeeDiscount::SearchStudentDiscountDetails($TotalRecords, false, $Filters, $Start, $TotalRecords);

$excelWriter = new PHPExcel();
$excelWriter->getProperties()->setCreator("Added")
        ->setLastModifiedBy("Added")
        ->setTitle('Student Discount Details')
        ->setSubject('Student Discount Details')
        ->setDescription('');
$excelWriter->getActiveSheet()
        ->getStyle('A1:G1')
        ->getFont()->setBold(true)
        ->setSize(16);

$excelWriter->setActiveSheetIndex(0)
        ->setCellValue('A1', 'S. No.')
        ->setCellValue('B1', 'Student Name')
        ->setCellValue('C1', 'Class')
		->setCellValue('D1', 'Month')
        ->setCellValue('E1', 'Fee Head')
        ->setCellValue('F1', 'Total Amt')
        ->setCellValue('G1', $Clean['DiscountType'],'Amt');
        
        $RowCounter = 'H';
        

$excelWriter->getActiveSheet()->getStyle('A1:'.$RowCounter.'1')->getFont()->setBold(true);
$excelWriter->getActiveSheet()->getStyle('A1:'.$RowCounter.'1')->getFont()->getColor()->setARGB('FFFFFFFF');
$excelWriter->getActiveSheet()->getStyle('A1:'.$RowCounter.'1')->applyFromArray(
        array('fill' => array(
                'type' => PHPExcel_Style_Fill::FILL_SOLID,
                'color' => array('argb' => 'FF2F88F0')
            )
        )
);

for ($col = 'A'; $col !== $RowCounter; $col++)
{
    $excelWriter->getActiveSheet()
            ->getColumnDimension($col)
            ->setAutoSize(true);
}

$index = 2;

$TotalValue = 0;
$TotalPlotSize = 0;

$TotalDiscountAmount = 0;
$TotalAmount = 0;
        
    foreach($StudentDiscountDetails as $FeeDiscountID => $Details)
    {   
        if ($FeeDiscountID > 0)
        {
            ++$index;  
            
                $TotalAmount += $Details['TotalAmount'];

                if ($Clean['DiscountType'] == 'Discount') 
                {
                    $TotalDiscountAmount += $Details['DiscountAmount'];
                    $DiscountTypeAmount =  $Details['DiscountAmount'];  
                    
                }
                else if ($Clean['DiscountType'] == 'Concession') 
                {
                    $TotalDiscountAmount += $Details['TotalConcession'];
                    $DiscountTypeAmount =  $Details['TotalConcession'];  
                }
                else if ($Clean['DiscountType'] == 'WaveOff') 
                {
                    $TotalDiscountAmount += $Details['TotalWaveOff'];
                    $DiscountTypeAmount =  $Details['TotalWaveOff'];  
                }
    
            $excelWriter->setActiveSheetIndex(0)
                ->setCellValueExplicit('A' . $index, $index-2, PHPExcel_Cell_DataType::TYPE_STRING)
                ->setCellValueExplicit('B' . $index, $Details['StudentName'], PHPExcel_Cell_DataType::TYPE_STRING)
                ->setCellValueExplicit('C' . $index, $Details['ClassName'] .'('. $Details['SectionName'] .')', PHPExcel_Cell_DataType::TYPE_STRING)
                ->setCellValueExplicit('D' . $index, $Details['MonthName'], PHPExcel_Cell_DataType::TYPE_STRING)
                ->setCellValueExplicit('E' . $index, $Details['FeeHead'], PHPExcel_Cell_DataType::TYPE_STRING)
                ->setCellValueExplicit('F' . $index, number_format($Details['TotalAmount'], 2) , PHPExcel_Cell_DataType::TYPE_STRING)
                ->setCellValueExplicit('G' . $index, number_format($DiscountTypeAmount, 2) , PHPExcel_Cell_DataType::TYPE_STRING);
    			
        }
    			
        if ($index % 2 == 0)
        {
            $excelWriter->getActiveSheet()->getStyle('A' . $index . ':'.$RowCounter . $index)->applyFromArray(
                    array('fill' => array(
                            'type' => PHPExcel_Style_Fill::FILL_SOLID,
                            'color' => array('argb' => 'FFE5ECF5')
                        )
                    )
            );
        }
    }

++$index;

$index++;

$excelWriter->setActiveSheetIndex(0)
            ->setCellValueExplicit('A' . $index, '', PHPExcel_Cell_DataType::TYPE_STRING)
            ->setCellValueExplicit('B' . $index, '', PHPExcel_Cell_DataType::TYPE_STRING)
            ->setCellValueExplicit('C' . $index, '', PHPExcel_Cell_DataType::TYPE_STRING)
            ->setCellValueExplicit('D' . $index, '', PHPExcel_Cell_DataType::TYPE_STRING)
			->setCellValueExplicit('E' . $index, 'Grand Total', PHPExcel_Cell_DataType::TYPE_STRING)
			->setCellValueExplicit('F' . $index, number_format($TotalAmount, 2), PHPExcel_Cell_DataType::TYPE_STRING)
			->setCellValueExplicit('G' . $index, number_format($TotalDiscountAmount, 2), PHPExcel_Cell_DataType::TYPE_STRING);
			
    $excelWriter->getActiveSheet()->getStyle('A' . $index .  ':G' . $index)->getFont()->setBold(true);
            $excelWriter->getActiveSheet()->getStyle('A' . $index . ':G' . $index)->getFont()->getColor()->setARGB('FFFFFFFF');
            $excelWriter->getActiveSheet()->getStyle('A' . $index. ':G' . $index)->applyFromArray(
                    array('fill' => array(
                            'type' => PHPExcel_Style_Fill::FILL_SOLID,
                            'color' => array('argb' => 'FF2F88F0')
                        )
                    )
            );

$excelWriter->getActiveSheet()->getStyle('A1:G' . $index)->applyFromArray(
        array(
            'borders' => array(
                'bottom' => array('style' => PHPExcel_Style_Border::BORDER_THIN),
                'right' => array('style' => PHPExcel_Style_Border::BORDER_MEDIUM)
            )
        )
);

$excelWriter->getActiveSheet()->setTitle('student_discount_details');
// Set active sheet index to the first sheet, so Excel opens this as the first sheet
$excelWriter->setActiveSheetIndex(0);

// Redirect output to a clientâ€™s web browser (Excel5)
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename=student_discount_details.xls');
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
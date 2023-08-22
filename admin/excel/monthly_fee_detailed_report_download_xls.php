<?php
require_once("PHPExcel/PHPExcel.php");

set_time_limit(7200);

if (!isset($TotalRecords) && $TotalRecords <= 0)
{
    die('No Data Found');
}

$MonthlyFeeDueDetails = array();
$MonthlyFeeDueDetails = FeeCollection::MonthlyFeeDueDetails($TotalRecords, false, $Filters, $OverAllSummary, $Start, $TotalRecords);

$excelWriter = new PHPExcel();
$excelWriter->getProperties()->setCreator("Added")
        ->setLastModifiedBy("Added")
        ->setTitle('Monthly Fee Detailed Report')
        ->setSubject('Monthly Fee Detailed Report')
        ->setDescription('');
$excelWriter->getActiveSheet()
        ->getStyle('A1:J1')
        ->getFont()->setBold(true)
        ->setSize(16);

$excelWriter->setActiveSheetIndex(0)
        ->setCellValue('A1', 'S. No.')
        ->setCellValue('B1', 'Student Name')
        ->setCellValue('C1', 'Class')
		->setCellValue('D1', 'Total Amt')
        ->setCellValue('E1', 'Discount')
        ->setCellValue('F1', 'Concession')
        ->setCellValue('G1', 'Wave-Off')
        ->setCellValue('H1', 'Paid Amt')
        ->setCellValue('I1', 'Due Amt');
        
        $RowCounter = 'J';
        

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

    if (is_array($MonthlyFeeDueDetails) && count($MonthlyFeeDueDetails) > 0)
    {
        $TotalAmount = 0;
        $TotalDiscount = 0;
        $TotalConcession = 0;
        $TotalWaveOff = 0;
        $TotalAmountPaid = 0;
        $TotalDueAmount = 0;
        
    foreach($MonthlyFeeDueDetails as $StudentID => $Details)
    {   
        if ($Details['TotalAmount'] > 0)
        {
            $TotalAmount += $Details['TotalAmount'];
            $TotalDiscount += $Details['DiscountAmount'];
            $TotalConcession += $Details['TotalConcession'];
            $TotalWaveOff += $Details['TotalWaveOff'];
            $TotalAmountPaid += $Details['PaidAmount'];
            $TotalDueAmount += $Details['DueAmount'];
            
            ++$index;       
    
            $excelWriter->setActiveSheetIndex(0)
                ->setCellValueExplicit('A' . $index, $index-2, PHPExcel_Cell_DataType::TYPE_STRING)
                ->setCellValueExplicit('B' . $index, $Details['StudentName'], PHPExcel_Cell_DataType::TYPE_STRING)
                ->setCellValueExplicit('C' . $index, $Details['ClassName'] .'('. $Details['SectionName'] .')', PHPExcel_Cell_DataType::TYPE_STRING)
                ->setCellValueExplicit('D' . $index, number_format($Details['TotalAmount'], 2) , PHPExcel_Cell_DataType::TYPE_STRING)
                ->setCellValueExplicit('E' . $index, ($Details['DiscountAmount']) ? number_format($Details['DiscountAmount'], 2) : '--', PHPExcel_Cell_DataType::TYPE_STRING)
                ->setCellValueExplicit('F' . $index, ($Details['TotalConcession'] > 0) ? number_format($Details['TotalConcession'], 2) : '--', PHPExcel_Cell_DataType::TYPE_STRING)
                ->setCellValueExplicit('G' . $index, ($Details['TotalWaveOff'] > 0) ? number_format($Details['TotalWaveOff'], 2) : '--', PHPExcel_Cell_DataType::TYPE_STRING)
                ->setCellValueExplicit('H' . $index, ($Details['PaidAmount']) ? number_format($Details['PaidAmount'], 2) : '--', PHPExcel_Cell_DataType::TYPE_STRING)
                ->setCellValueExplicit('I' . $index, ($Details['DueAmount']) ? number_format($Details['DueAmount'], 2) : '--', PHPExcel_Cell_DataType::TYPE_STRING);
    			
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
}

++$index;

$index++;

$excelWriter->setActiveSheetIndex(0)
            ->setCellValueExplicit('A' . $index, '', PHPExcel_Cell_DataType::TYPE_STRING)
            ->setCellValueExplicit('B' . $index, '', PHPExcel_Cell_DataType::TYPE_STRING)
            ->setCellValueExplicit('C' . $index, 'Grand Total', PHPExcel_Cell_DataType::TYPE_STRING)
            ->setCellValueExplicit('D' . $index, number_format($TotalAmount, 2), PHPExcel_Cell_DataType::TYPE_STRING)
			->setCellValueExplicit('E' . $index, number_format($TotalDiscount, 2), PHPExcel_Cell_DataType::TYPE_STRING)
			->setCellValueExplicit('F' . $index, number_format($TotalConcession, 2), PHPExcel_Cell_DataType::TYPE_STRING)
			->setCellValueExplicit('G' . $index, number_format($TotalWaveOff, 2), PHPExcel_Cell_DataType::TYPE_STRING)
			->setCellValueExplicit('H' . $index, number_format($TotalAmountPaid, 2), PHPExcel_Cell_DataType::TYPE_STRING)
			->setCellValueExplicit('I' . $index, number_format($TotalDueAmount, 2), PHPExcel_Cell_DataType::TYPE_STRING);
			
    $excelWriter->getActiveSheet()->getStyle('A' . $index .  ':I' . $index)->getFont()->setBold(true);
            $excelWriter->getActiveSheet()->getStyle('A' . $index . ':I' . $index)->getFont()->getColor()->setARGB('FFFFFFFF');
            $excelWriter->getActiveSheet()->getStyle('A' . $index. ':I' . $index)->applyFromArray(
                    array('fill' => array(
                            'type' => PHPExcel_Style_Fill::FILL_SOLID,
                            'color' => array('argb' => 'FF2F88F0')
                        )
                    )
            );

$excelWriter->getActiveSheet()->getStyle('A1:I' . $index)->applyFromArray(
        array(
            'borders' => array(
                'bottom' => array('style' => PHPExcel_Style_Border::BORDER_THIN),
                'right' => array('style' => PHPExcel_Style_Border::BORDER_MEDIUM)
            )
        )
);

$excelWriter->getActiveSheet()->setTitle('monthly_fee_detailed_report');
// Set active sheet index to the first sheet, so Excel opens this as the first sheet
$excelWriter->setActiveSheetIndex(0);

// Redirect output to a clientâ€™s web browser (Excel5)
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename=monthly_fee_detailed_report.xls');
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
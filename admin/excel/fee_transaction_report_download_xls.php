<?php
require_once("PHPExcel/PHPExcel.php");

set_time_limit(7200);

if (!isset($TotalRecords) && $TotalRecords <= 0)
{
    die('No Data Found');
}

$FeeTransactionDetails = array();
$FeeTransactionDetails = FeeCollection::SearchFeeTransactions($TotalRecords, false, $Filters, $Start, $TotalRecords);

$excelWriter = new PHPExcel();
$excelWriter->getProperties()->setCreator("Added")
        ->setLastModifiedBy("Added")
        ->setTitle('Fee Transaction Report')
        ->setSubject('Fee Transaction Report')
        ->setDescription('');
$excelWriter->getActiveSheet()
        ->getStyle('A1:I1')
        ->getFont()->setBold(true)
        ->setSize(16);

$excelWriter->setActiveSheetIndex(0)
        ->setCellValue('A1', 'S. No.')
        ->setCellValue('B1', 'Tr. ID')
        ->setCellValue('C1', 'Student Name')
		->setCellValue('D1', 'Father Name')
        ->setCellValue('E1', 'Paid Amt')
        ->setCellValue('F1', 'Fee Date')
        ->setCellValue('G1', 'Pmt. Mode')
        ->setCellValue('H1', 'Description');
        
        $RowCounter = 'I';
        

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

$TotalAmount = 0;
$TotalDiscount = 0;
$TotalAmountPaid = 0;

foreach($FeeTransactionDetails as $FeeTransactionID => $Details)
{   
    if ($FeeTransactionID > 0)
    {
        $TotalAmountPaid += $Details['TransactionAmount'];
        
        foreach ($Details['StudentDetails'] as $StudentID => $StudentDetails) 
        {
            $StudentName = $StudentDetails['FirstName'] .' '. $StudentDetails['LastName']; 
        }
        
        foreach ($Details['PaymentModeDetails'] as $PaymentMode => $ModeAmount) 
        {
            $PaymentMode = $PaymentModeList[$PaymentMode] .' ('. $ModeAmount .')'; 
        }

        ++$index;       

        $excelWriter->setActiveSheetIndex(0)
            ->setCellValueExplicit('A' . $index, $index-2, PHPExcel_Cell_DataType::TYPE_STRING)
            ->setCellValueExplicit('B' . $index, $FeeTransactionID, PHPExcel_Cell_DataType::TYPE_STRING)
            ->setCellValueExplicit('C' . $index, $StudentName, PHPExcel_Cell_DataType::TYPE_STRING)
            ->setCellValueExplicit('D' . $index, $Details['FatherName'], PHPExcel_Cell_DataType::TYPE_STRING)
            ->setCellValueExplicit('E' . $index, $Details['TransactionAmount'], PHPExcel_Cell_DataType::TYPE_STRING)
            ->setCellValueExplicit('F' . $index, date('d/m/Y', strtotime($Details['FeeDate'])), PHPExcel_Cell_DataType::TYPE_STRING)
			->setCellValueExplicit('G' . $index, $PaymentMode, PHPExcel_Cell_DataType::TYPE_STRING)
			->setCellValueExplicit('H' . $index, $Details['Description'], PHPExcel_Cell_DataType::TYPE_STRING);
			
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
            ->setCellValueExplicit('B' . $index, 'Grand Total', PHPExcel_Cell_DataType::TYPE_STRING)
            ->setCellValueExplicit('C' . $index, '', PHPExcel_Cell_DataType::TYPE_STRING)
            ->setCellValueExplicit('D' . $index, '', PHPExcel_Cell_DataType::TYPE_STRING)
			->setCellValueExplicit('E' . $index, number_format($TotalAmountPaid, 2), PHPExcel_Cell_DataType::TYPE_STRING)
			->setCellValueExplicit('F' . $index, '', PHPExcel_Cell_DataType::TYPE_STRING)
			->setCellValueExplicit('G' . $index, '', PHPExcel_Cell_DataType::TYPE_STRING)
			->setCellValueExplicit('H' . $index, '', PHPExcel_Cell_DataType::TYPE_STRING)
			->setCellValueExplicit('I' . $index, '', PHPExcel_Cell_DataType::TYPE_STRING);
			
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

$excelWriter->getActiveSheet()->setTitle('fee_transaction_report');
// Set active sheet index to the first sheet, so Excel opens this as the first sheet
$excelWriter->setActiveSheetIndex(0);

// Redirect output to a clientâ€™s web browser (Excel5)
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename=fee_transaction_report.xls');
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
<?php
require_once("PHPExcel/PHPExcel.php");

set_time_limit(7200);

if (!isset($TotalRecords) && $TotalRecords <= 0)
{
    die('No Data Found');
}

$ChequeTransactionDetails = array();

$ChequeTransactionDetails = FeeCollection::SearchChequeTransactionDetails($TotalRecords, false, $Filters, $Start, $TotalRecords);

$excelWriter = new PHPExcel();
$excelWriter->getProperties()->setCreator("Added")
        ->setLastModifiedBy("Added")
        ->setTitle('Cheque Transaction Report')
        ->setSubject('Cheque Transaction Report')
        ->setDescription('');
$excelWriter->getActiveSheet()
        ->getStyle('A1:I1')
        ->getFont()->setBold(true)
        ->setSize(16);

$excelWriter->setActiveSheetIndex(0)
        ->setCellValue('A1', 'S. No.')
        ->setCellValue('B1', 'Tr. ID')
        ->setCellValue('C1', 'Student Name')
		->setCellValue('D1', 'Class')
        ->setCellValue('E1', 'Section')
        ->setCellValue('F1', 'Cheque Amt')
        ->setCellValue('G1', 'Fee Date')
        ->setCellValue('H1', 'Cheque No');
        
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

foreach($ChequeTransactionDetails as $FeePaymentModeDetailID => $Details)
{   
    if ($FeePaymentModeDetailID > 0)
    {
        $TotalAmount += $Details['Amount'];
        
        ++$index;       

        $excelWriter->setActiveSheetIndex(0)
            ->setCellValueExplicit('A' . $index, $index-2, PHPExcel_Cell_DataType::TYPE_STRING)
            ->setCellValueExplicit('B' . $index, $Details['FeeTransactionID'], PHPExcel_Cell_DataType::TYPE_STRING)
            ->setCellValueExplicit('C' . $index, $Details['StudentName'], PHPExcel_Cell_DataType::TYPE_STRING)
            ->setCellValueExplicit('D' . $index, $Details['ClassName'], PHPExcel_Cell_DataType::TYPE_STRING)
            ->setCellValueExplicit('E' . $index, $Details['SectionName'], PHPExcel_Cell_DataType::TYPE_STRING)
			->setCellValueExplicit('F' . $index, $Details['Amount'], PHPExcel_Cell_DataType::TYPE_STRING)
			 ->setCellValueExplicit('G' . $index, date('d/m/Y', strtotime($Details['FeeDate'])), PHPExcel_Cell_DataType::TYPE_STRING)
			->setCellValueExplicit('H' . $index, $Details['ChequeReferenceNo'], PHPExcel_Cell_DataType::TYPE_STRING);
			
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

			
		$LastRowCount = 'I';
			
    $excelWriter->getActiveSheet()->getStyle('A' . $index .  ':'.$LastRowCount . $index)->getFont()->setBold(true);
            $excelWriter->getActiveSheet()->getStyle('A' . $index . ':'.$LastRowCount . $index)->getFont()->getColor()->setARGB('FFFFFFFF');
            $excelWriter->getActiveSheet()->getStyle('A' . $index. ':'.$LastRowCount . $index)->applyFromArray(
                    array('fill' => array(
                            'type' => PHPExcel_Style_Fill::FILL_SOLID,
                            'color' => array('argb' => 'FF2F88F0')
                        )
                    )
            );

$excelWriter->getActiveSheet()->getStyle('A1:'.$LastRowCount . $index)->applyFromArray(
        array(
            'borders' => array(
                'bottom' => array('style' => PHPExcel_Style_Border::BORDER_THIN),
                'right' => array('style' => PHPExcel_Style_Border::BORDER_MEDIUM)
            )
        )
);

$excelWriter->getActiveSheet()->setTitle('cheque_transaction_report');
// Set active sheet index to the first sheet, so Excel opens this as the first sheet
$excelWriter->setActiveSheetIndex(0);

// Redirect output to a clientâ€™s web browser (Excel5)
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename=cheque_transaction_report.xls');
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
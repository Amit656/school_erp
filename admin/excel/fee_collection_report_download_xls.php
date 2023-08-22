<?php
require_once("PHPExcel/PHPExcel.php");

set_time_limit(7200);

if (!isset($TotalRecords) && $TotalRecords <= 0)
{
    die('No Data Found');
}

$BankList = array(1 => 'SBI', 2 => 'BOI', 3 => 'PNB', 4 => 'HDFC');
$FeeCollectionDetails = array();
$FeeHeadPaidAmountTotal = array();

$FeeCollectionDetails = FeeCollection::SearchFeeCollectionDetails($TotalRecords, false, $Filters, $Start, $TotalRecords);

$excelWriter = new PHPExcel();
$excelWriter->getProperties()->setCreator("Added")
        ->setLastModifiedBy("Added")
        ->setTitle('Fee Collection Report')
        ->setSubject('Fee Collection Report')
        ->setDescription('');
$excelWriter->getActiveSheet()
        ->getStyle('A1:T1')
        ->getFont()->setBold(true)
        ->setSize(16);

$excelWriter->setActiveSheetIndex(0)
        ->setCellValue('A1', 'S. No.')
        ->setCellValue('B1', 'Receipt Date')
        ->setCellValue('C1', 'Receipt No.')
        // ->setCellValue('D1', 'Admission No.')
		->setCellValue('D1', 'Student Name')
        ->setCellValue('E1', 'Class')
        ->setCellValue('F1', 'Section')
        ->setCellValue('G1', 'Fee Months');
        
        $RowCounter = 'H';
        foreach ($ActiveFeeHeads as $FeeHeadID => $FeeHeadDetails) 
        {
            $FeeHeadPaidAmountTotal[$FeeHeadID] = 0;
            
            $excelWriter->setActiveSheetIndex(0)->setCellValue($RowCounter++.'1', $FeeHeadDetails['FeeHead']);
        }
        
$excelWriter->setActiveSheetIndex(0)
        // ->setCellValue($RowCounter++.'1', 'Late Fee')
        ->setCellValue($RowCounter++.'1', 'Payment Mode')
        ->setCellValue($RowCounter++.'1', 'Cash Amt')
        ->setCellValue($RowCounter++.'1', 'Cheque Date')
        ->setCellValue($RowCounter++.'1', 'Cheque No.')
        // ->setCellValue($RowCounter++.'1', 'Bank Name')
        ->setCellValue($RowCounter++.'1', 'Cheque Amt')
        
        ->setCellValue($RowCounter++.'1', 'Total Payable')
        ->setCellValue($RowCounter++.'1', 'Discount')
        ->setCellValue($RowCounter++.'1', 'Total Paid')
        ->setCellValue($RowCounter++.'1', 'Description')
        ->setCellValue($RowCounter++.'1', 'Created By');

$excelWriter->getActiveSheet()->getStyle('A1:'.$RowCounter.'1')->getFont()->setBold(true);
$excelWriter->getActiveSheet()->getStyle('A1:'.$RowCounter.'1')->getFont()->getColor()->setARGB('FFFFFFFF');
$excelWriter->getActiveSheet()->getStyle('A1:'.$RowCounter++.'1')->applyFromArray(
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

$TotalLateFee = 0;
$TotalPayableAmount = 0;
$TotalDiscountAmount = 0;
$TotalAmountPaid = 0;

$TotalCashAmount = 0;
$TotalChequeAmount = 0;

foreach($FeeCollectionDetails as $FeeCollectionID => $Details)
{   
    if ($FeeCollectionID > 0)
    {
        $FeeCollectionBreakUps = array();
        $FeeCollectionBreakUps = FeeCollection::GetFeeTransactionDetails($FeeCollectionID);
        
        $CollectedFeeMonths = '';
    
        foreach ($FeeCollectionBreakUps as $Month => $BreakUpDetails) 
        {
            $CollectedFeeMonths .= $Month . ', ';
        }
        
        $OtherChargesDetails = array();
        $OtherChargesDetails = FeeCollection::GetFeeTransactionOtherChargesDetails($FeeCollectionID);
        
        ++$index;

        $excelWriter->setActiveSheetIndex(0)
            ->setCellValueExplicit('A' . $index, $index-2, PHPExcel_Cell_DataType::TYPE_STRING)
            ->setCellValueExplicit('B' . $index, date('d/m/Y', strtotime($Details['FeeDate'])), PHPExcel_Cell_DataType::TYPE_STRING)
            ->setCellValueExplicit('C' . $index, $Details['FeeTransactionID'], PHPExcel_Cell_DataType::TYPE_STRING)
            // ->setCellValueExplicit('D' . $index, $Details['EnrollmentID'], PHPExcel_Cell_DataType::TYPE_STRING)
			->setCellValueExplicit('D' . $index, $Details['StudentName'], PHPExcel_Cell_DataType::TYPE_STRING)
			->setCellValueExplicit('E' . $index, $Details['ClassName'], PHPExcel_Cell_DataType::TYPE_STRING)
			->setCellValueExplicit('F' . $index, $Details['SectionName'], PHPExcel_Cell_DataType::TYPE_STRING)
			->setCellValueExplicit('G' . $index, $CollectedFeeMonths, PHPExcel_Cell_DataType::TYPE_STRING);
			
			$RowCount = 'H';
            foreach ($ActiveFeeHeads as $FeeHeadID => $FeeHeadDetails) 
            {
                $FeeHeadPaidAmount = 0;
                foreach ($FeeCollectionBreakUps as $Month => $BreakUpDetails) 
                {
                    if (array_key_exists($FeeHeadID, $BreakUpDetails)) 
                    {
                        $FeeHeadPaidAmount += $BreakUpDetails[$FeeHeadID]['PaidAmount'];
                    }
                }
                
                $FeeHeadPaidAmountTotal[$FeeHeadID] += $FeeHeadPaidAmount;
                
                $excelWriter->setActiveSheetIndex(0)->setCellValueExplicit($RowCount++ . $index, number_format($FeeHeadPaidAmount, 2), PHPExcel_Cell_DataType::TYPE_STRING);
            }
            
            $LateFee = 0;
            $CashAmount = 0;
            $ChequeAmount = 0;
            $ChequeNumber = '';
            $ChequeDate = '';
            $Bank = '';
            
            $PaymentModes = '';
            
            if (count($Details['PaymentModeDetails']))
            {
                foreach ($Details['PaymentModeDetails'] as $PaymentMode => $PaymentModeDetails)
                {
                    if ($PaymentMode == 1)
                    {
                        $CashAmount = $PaymentModeDetails['Amount'];
                        $TotalCashAmount += $PaymentModeDetails['Amount'];
                        $PaymentModes .= 'Cash,';
                    }
                    
                    if ($PaymentMode == 2)
                    {
                        $ChequeAmount = $PaymentModeDetails['Amount'];
                        $TotalChequeAmount += $PaymentModeDetails['Amount'];
                        $ChequeNumber = $PaymentModeDetails['ChequeReferenceNo'];
                        
                        if ($PaymentModeDetails['ChequeDate'] != '0000-00-00')
                        {
                            $ChequeDate = date('d/m/Y', strtotime($PaymentModeDetails['ChequeDate']));
                        }
                        
                        // $Bank = $BankList[$PaymentModeDetails['BankID']];
                        
                        $PaymentModes .= ' Cheque';
                    }
                }
            }
            
        $excelWriter->setActiveSheetIndex(0)
            // ->setCellValueExplicit($RowCount++ . $index, number_format($LateFee, 2), PHPExcel_Cell_DataType::TYPE_STRING)
            ->setCellValueExplicit($RowCount++ . $index, $PaymentModes, PHPExcel_Cell_DataType::TYPE_STRING)
            ->setCellValueExplicit($RowCount++ . $index, number_format($CashAmount, 2), PHPExcel_Cell_DataType::TYPE_STRING)
            ->setCellValueExplicit($RowCount++ . $index, $ChequeDate, PHPExcel_Cell_DataType::TYPE_STRING)
            ->setCellValueExplicit($RowCount++ . $index, $ChequeNumber, PHPExcel_Cell_DataType::TYPE_STRING)
            // ->setCellValueExplicit($RowCount++ . $index, $Bank, PHPExcel_Cell_DataType::TYPE_STRING)
            ->setCellValueExplicit($RowCount++ . $index, number_format($ChequeAmount, 2), PHPExcel_Cell_DataType::TYPE_STRING)
            
			->setCellValueExplicit($RowCount++ . $index, number_format($Details['TotalAmount'], 2), PHPExcel_Cell_DataType::TYPE_STRING)
			->setCellValueExplicit($RowCount++ . $index, number_format($Details['TotalDiscount'], 2), PHPExcel_Cell_DataType::TYPE_STRING)
			->setCellValueExplicit($RowCount++ . $index, number_format($Details['AmountPaid'], 2), PHPExcel_Cell_DataType::TYPE_STRING)
			->setCellValueExplicit($RowCount++ . $index, $Details['Description'], PHPExcel_Cell_DataType::TYPE_STRING)
			->setCellValueExplicit($RowCount++ . $index, $Details['CreateUserName'], PHPExcel_Cell_DataType::TYPE_STRING);
			
			$TotalPayableAmount += $Details['TotalAmount'];
			$TotalDiscountAmount += $Details['TotalDiscount'];
			$TotalAmountPaid += $Details['AmountPaid'];
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
			->setCellValueExplicit('E' . $index, '', PHPExcel_Cell_DataType::TYPE_STRING)
			->setCellValueExplicit('F' . $index, '', PHPExcel_Cell_DataType::TYPE_STRING)
			->setCellValueExplicit('G' . $index, '', PHPExcel_Cell_DataType::TYPE_STRING);
			
		$LastRowCount = 'H';
        foreach ($ActiveFeeHeads as $FeeHeadID => $FeeHeadDetails) 
        {
           $excelWriter->setActiveSheetIndex(0)->setCellValueExplicit($LastRowCount++. $index, number_format($FeeHeadPaidAmountTotal[$FeeHeadID], 2), PHPExcel_Cell_DataType::TYPE_STRING);
        }
			
    $excelWriter->setActiveSheetIndex(0)
            // ->setCellValueExplicit($LastRowCount++ . $index, number_format($TotalLateFee, 2), PHPExcel_Cell_DataType::TYPE_STRING)
            ->setCellValueExplicit($LastRowCount++ . $index, '', PHPExcel_Cell_DataType::TYPE_STRING)
            ->setCellValueExplicit($LastRowCount++ . $index, number_format($TotalCashAmount, 2), PHPExcel_Cell_DataType::TYPE_STRING)
            ->setCellValueExplicit($LastRowCount++ . $index, '', PHPExcel_Cell_DataType::TYPE_STRING)
            ->setCellValueExplicit($LastRowCount++ . $index, '', PHPExcel_Cell_DataType::TYPE_STRING)
            // ->setCellValueExplicit($LastRowCount++ . $index, '', PHPExcel_Cell_DataType::TYPE_STRING)
            ->setCellValueExplicit($LastRowCount++ . $index, number_format($TotalChequeAmount, 2), PHPExcel_Cell_DataType::TYPE_STRING)
            
			->setCellValueExplicit($LastRowCount++ . $index, number_format($TotalPayableAmount, 2), PHPExcel_Cell_DataType::TYPE_STRING)
			->setCellValueExplicit($LastRowCount++ . $index, number_format($TotalDiscountAmount, 2), PHPExcel_Cell_DataType::TYPE_STRING)
			->setCellValueExplicit($LastRowCount++ . $index, number_format($TotalAmountPaid, 2), PHPExcel_Cell_DataType::TYPE_STRING)
			->setCellValueExplicit($LastRowCount++ . $index, '', PHPExcel_Cell_DataType::TYPE_STRING)
			->setCellValueExplicit($LastRowCount++ . $index, '', PHPExcel_Cell_DataType::TYPE_STRING);
			
    $excelWriter->getActiveSheet()->getStyle('A' . $index .  ':'.$LastRowCount . $index)->getFont()->setBold(true);
            $excelWriter->getActiveSheet()->getStyle('A' . $index . ':'.$LastRowCount . $index)->getFont()->getColor()->setARGB('FFFFFFFF');
            $excelWriter->getActiveSheet()->getStyle('A' . $index. ':'.$LastRowCount . $index)->applyFromArray(
                    array('fill' => array(
                            'type' => PHPExcel_Style_Fill::FILL_SOLID,
                            'color' => array('argb' => 'FF2F88F0')
                        )
                    )
            );

$excelWriter->getActiveSheet()->getStyle('A1:'.$RowCounter . $index)->applyFromArray(
        array(
            'borders' => array(
                'bottom' => array('style' => PHPExcel_Style_Border::BORDER_THIN),
                'right' => array('style' => PHPExcel_Style_Border::BORDER_MEDIUM)
            )
        )
);

$excelWriter->getActiveSheet()->setTitle('fee_collection_report');
// Set active sheet index to the first sheet, so Excel opens this as the first sheet
$excelWriter->setActiveSheetIndex(0);

// Redirect output to a clientâ€™s web browser (Excel5)
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename=fee_collection_report.xls');
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
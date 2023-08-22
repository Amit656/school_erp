<?php
require_once("PHPExcel/PHPExcel.php");

set_time_limit(7200);

if (!isset($TotalRecords) && $TotalRecords <= 0)
{
    die('No Data Found');
}

$FeeHeadList =  array();
$FeeHeadList = FeeHead::GetActiveFeeHeads();
$FeeHeadPaidAmountTotal = array();

$DefaulterList = array();
$DefaulterList = FeeCollection::SearchFeeDefaulters($TotalRecords, false, $Filters, $FeePriority, $Start, $TotalRecords);

$ReportHeaderText = '';
    
    if ($Clean['AcademicYearID'] != 0)
    {
        $ReportHeaderText .= ' Session: ' . date('Y', strtotime($AcademicYears[$Clean['AcademicYearID']]['StartDate'])) .' - '. date('Y', strtotime($AcademicYears[$Clean['AcademicYearID']]['EndDate'])) . ',';
    }
    
    if ($Clean['ClassID'] > 0)
    {
        $ReportHeaderText .= ' Class : ' . $ClassList[$Clean['ClassID']] . ',';
    }
    
    if ($Clean['ClassSectionID'] > 0)
    {
        $ReportHeaderText .= ' Section : ' . $ClassSectionsList[$Clean['ClassSectionID']] . ',';
    }
    
    if ($Clean['StudentID'] > 0)
    {
        $ReportHeaderText .= ' Student : ' . $StudentsList[$Clean['StudentID']]['FirstName'] . ',';
    }
    
    if ($Clean['FeeHeadID'] > 0)
    {
        $ReportHeaderText .= ' Fee Head : ' . $ActiveFeeHeads[$Clean['FeeHeadID']]['FeeHead'] . ',';
    }
    
    if ($Clean['StudentName'] != '')
    {
        $ReportHeaderText .= ' Student Name : ' . $Clean['StudentName'] . ',';
    }
    
    if ($Clean['Status'] != '')
    {
        $ReportHeaderText .= ' Status: ' . $Clean['Status'] . ' students,';
    }
    
    if (count($Clean['MonthList']) > 1)
    {
        $ReportHeaderText .= ' Months ';
    }
    
    foreach ($Clean['MonthList'] as $Key => $MonthID) 
    {
        $ReportHeaderText .= ' '. $AcademicYearMonths[$MonthID]['MonthName'] . ', ';
    }
    
    if ($ReportHeaderText != '')
    {
        $ReportHeaderText = 'Defaulter List Report For ' . rtrim($ReportHeaderText, ',');
    }

$excelWriter = new PHPExcel();
$excelWriter->getProperties()->setCreator("Added")
        ->setLastModifiedBy("Added")
        ->setTitle('Fee Defaulter List')
        ->setSubject('Fee Defaulter List')
        ->setDescription('');
$excelWriter->getActiveSheet()
        ->getStyle('A1:L1')
        ->getFont()->setBold(false)
        ->setSize(16);

$excelWriter->setActiveSheetIndex(0)
        ->setCellValue('A1', $ReportHeaderText, PHPExcel_Cell_DataType::TYPE_STRING);
        
$excelWriter->getActiveSheet()->mergeCells('A1:L1');
        
        $RowCounter = 'E';
        foreach ($FeeHeadList as $FeeHeadID => $FeeHeadDetails) 
        {
            $FeeHeadPaidAmountTotal[$FeeHeadID] = 0;
           $excelWriter->setActiveSheetIndex(0)->setCellValue($RowCounter++.'1', $FeeHeadDetails['FeeHead']);
        }
        
		$excelWriter->setActiveSheetIndex(0)->setCellValue($RowCounter.'1', 'Total Due Amount');
        
$excelWriter->getActiveSheet()->getStyle('A1:'.$RowCounter.'1')->getFont()->setBold(true);
$excelWriter->getActiveSheet()->getStyle('A1:'.$RowCounter.'1')->getFont()->getColor()->setARGB('FFFFFFFF');
$excelWriter->getActiveSheet()->getStyle('A1:'.$RowCounter++.'1')->applyFromArray(
        array('fill' => array(
                'type' => PHPExcel_Style_Fill::FILL_SOLID,
                'color' => array('argb' => 'FF2F88F0')
            )
        )
);

$excelWriter->setActiveSheetIndex(0)
        ->setCellValue('A2', 'S.No')
        ->setCellValue('B2', 'Student Name')
        ->setCellValue('C2', 'Class')
        ->setCellValue('D2', 'Previous Due');
        
        $RowCounter = 'E';
        foreach ($FeeHeadList as $FeeHeadID => $FeeHeadDetails) 
        {
            $FeeHeadPaidAmountTotal[$FeeHeadID] = 0;
           $excelWriter->setActiveSheetIndex(0)->setCellValue($RowCounter++.'2', $FeeHeadDetails['FeeHead']);
        }
        
		$excelWriter->setActiveSheetIndex(0)->setCellValue($RowCounter.'2', 'Total Due Amount');
        
$excelWriter->getActiveSheet()->getStyle('A1:'.$RowCounter.'2')->getFont()->setBold(true);
$excelWriter->getActiveSheet()->getStyle('A1:'.$RowCounter.'2')->getFont()->getColor()->setARGB('FFFFFFFF');
$excelWriter->getActiveSheet()->getStyle('A1:'.$RowCounter++.'2')->applyFromArray(
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
$TotalPreviousYearDue = 0;
$TotalDueAmount = 0;

foreach($DefaulterList as $StudentID => $Details)
{   
    $PreviousDefaultedFees = array();
    $PreviousDueAmount = 0;

    if ($Clean['AcademicYearID'] == 2) 
    {
        $PreviousDefaultedFees = FeeCollection::GetFeeDefaulterDues($StudentID, 120, 1, $PreviousYearDue);
        
        foreach ($PreviousDefaultedFees as $Month => $FeeDetails) 
        {
            $PreviousDueAmount += array_sum(array_column($FeeDetails,'FeeHeadAmount'));
        }
    }

    $FeeDefaulterDues = array();
    $FeeDefaulterDues = FeeCollection::GetFeeDefaulterDues($StudentID, $FeePriority, $Clean['AcademicYearID'], $PreviousYearDue);
    
    $TotalPreviousYearDue += $PreviousYearDue + $PreviousDueAmount;
    $TotalDueAmount += $Details['TotalDue'] + $PreviousDueAmount;
    
    ++$index;

    $excelWriter->setActiveSheetIndex(0)
            ->setCellValueExplicit('A' . $index, $index-2, PHPExcel_Cell_DataType::TYPE_STRING)
            ->setCellValueExplicit('B' . $index, $Details['StudentName'], PHPExcel_Cell_DataType::TYPE_STRING)
            ->setCellValueExplicit('C' . $index, $Details['Class'], PHPExcel_Cell_DataType::TYPE_STRING)
            ->setCellValueExplicit('D' . $index, $PreviousYearDue, PHPExcel_Cell_DataType::TYPE_STRING);
            
            $RowCount = 'E';
                foreach ($FeeHeadList as $FeeHeadID => $FeeHeadDetails) 
                {
                    $FeeHeadDueAmount = 0;
                    foreach ($FeeDefaulterDues as $Month => $DefaulterDetails) 
                    {
                        if ($Clean['FeeHeadID'] > 0)
                        {
                            if ($Clean['FeeHeadID'] == $FeeHeadID  && array_key_exists($Clean['FeeHeadID'], $DefaulterDetails))
                            {
                                $FeeHeadDueAmount += $DefaulterDetails[$Clean['FeeHeadID']]['FeeHeadAmount'];   
                            }
                        }
                        else 
                        {
                            if (array_key_exists($FeeHeadID, $DefaulterDetails)) 
                            {
                                $FeeHeadDueAmount += $DefaulterDetails[$FeeHeadID]['FeeHeadAmount'];   
                            }
                        }
                    }
                    
                    $FeeHeadPaidAmountTotal[$FeeHeadID] += $FeeHeadDueAmount;
                    
                    $excelWriter->setActiveSheetIndex(0)->setCellValueExplicit($RowCount++ . $index, number_format($FeeHeadDueAmount, 2), PHPExcel_Cell_DataType::TYPE_STRING);
                }
                
                $excelWriter->setActiveSheetIndex(0)->setCellValueExplicit($RowCount . $index, number_format($Details['TotalDue'], 2), PHPExcel_Cell_DataType::TYPE_STRING);

    if ($index % 2 == 0)
    {
        $excelWriter->getActiveSheet()->getStyle('A' . $index . ':'.$RowCount . $index)->applyFromArray(
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
            ->setCellValueExplicit('C' . $index, 'Grand Total :', PHPExcel_Cell_DataType::TYPE_STRING)
            ->setCellValueExplicit('D' . $index, number_format($TotalPreviousYearDue, 2), PHPExcel_Cell_DataType::TYPE_STRING);
            
            $LastRowCount = 'E';
            foreach ($FeeHeadList as $FeeHeadID => $FeeHeadDetails) 
            {
               $excelWriter->setActiveSheetIndex(0)->setCellValueExplicit($LastRowCount++. $index, number_format($FeeHeadPaidAmountTotal[$FeeHeadID], 2), PHPExcel_Cell_DataType::TYPE_STRING);
            }
            
	$excelWriter->setActiveSheetIndex(0)->setCellValueExplicit($LastRowCount++ . $index, number_format($TotalDueAmount, 2), PHPExcel_Cell_DataType::TYPE_STRING);
			
    $excelWriter->getActiveSheet()->getStyle('A' . $index .  ':'.$LastRowCount . $index)->getFont()->setBold(true);
            $excelWriter->getActiveSheet()->getStyle('A' . $index . ':'.$LastRowCount . $index)->getFont()->getColor()->setARGB('FFFFFFFF');
            $excelWriter->getActiveSheet()->getStyle('A' . $index. ':'.$LastRowCount . $index)->applyFromArray(
                    array('fill' => array(
                            'type' => PHPExcel_Style_Fill::FILL_SOLID,
                            'color' => array('argb' => 'FF2F88F0')
                        )
                    )
            );


$excelWriter->getActiveSheet()->getStyle('A1:'.$RowCount . $index)->applyFromArray(
        array(
            'borders' => array(
                'bottom' => array('style' => PHPExcel_Style_Border::BORDER_THIN),
                'right' => array('style' => PHPExcel_Style_Border::BORDER_MEDIUM)
            )
        )
);

$excelWriter->getActiveSheet()->setTitle('fee_defaulter_list');
// Set active sheet index to the first sheet, so Excel opens this as the first sheet
$excelWriter->setActiveSheetIndex(0);

// Redirect output to a clientâ€™s web browser (Excel5)
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename=fee_defaulter_list.xls');
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
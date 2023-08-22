<?php
require_once('../../classes/class.users.php');
require_once('../../classes/class.validation.php');

require_once('../../classes/class.date_processing.php');
require_once('../../classes/class.ui_helpers.php');

require_once('../../classes/excel_reader/class.upload_helpers.php');

$AllRows = array();
$ErrorRows = array();

$HasErrors = false;

$Clean = array();

$Clean['StartFromRow'] = 7;

$Clean['Process'] = 0;

if (isset($_POST['hdnProcess']))
{
    $Clean['Process'] = (int) $_POST['hdnProcess'];
}
else if (isset($_GET['Process']))
{
    $Clean['Process'] = (int) $_GET['Process'];
}
switch ($Clean['Process'])
{
    case 1:
        if (isset($_POST['txtStartFromRow']))
        {
            $Clean['StartFromRow'] = (int) $_POST['txtStartFromRow'];
        }
        
        $NewRecordValidator = new Validator();
		
        $UploadedFile = array();

        if (isset($_FILES['fileExcel']))
        {
            $UploadedFile = $_FILES['fileExcel'];
        }

        if ($UploadedFile['error'] != 0)
        {
            $NewRecordValidator->AttachTextError('Error in uploaded file, please try again.');
        }

        if ($NewRecordValidator->HasNotifications())
        {
            $HasErrors = true;
            break;
        }

        require_once("../../admin/excel/PHPExcel/PHPExcel/IOFactory.php");

        try
        {
            $inputFileType = PHPExcel_IOFactory::identify($UploadedFile['tmp_name']);
            $objReader = PHPExcel_IOFactory::createReader($inputFileType);
            $objPHPExcel = $objReader->load($UploadedFile['tmp_name']);
        }
        catch (Exception $e)
        {
            die('Error loading file "' . pathinfo($UploadedFile['tmp_name'], PATHINFO_BASENAME) . '": ' . $e->getMessage());
        }

        //  Get worksheet dimensions
        $CurrentSheet = $objPHPExcel->getSheet(1);
        $highestRow = $CurrentSheet->getHighestRow();
        $highestColumn = $CurrentSheet->getHighestColumn();

        //  Loop through each row of the worksheet in turn
        for ($row = $Clean['StartFromRow']; $row <= $highestRow - 1; $row++)
        {
			# SKIP IF FOUND HEADING IN PARTICULARS
			if ($CurrentSheet->getCell('B' . $row)->getStyle()->getFont()->getBold())
			{
				# READ ALL THE COLUMNS ON CURRENT ROW FROM A - J
				$rowData = $CurrentSheet->rangeToArray('A' . $row . ':' . 'J' . $row, NULL, TRUE, FALSE);

				# GET THESE THREE VALUES FOR MASTER TABLE AND COUNTINUE
				$Date = '';
				$Buyer = '';
				$VoucherNumber = 0;

				foreach ($rowData as $key => $Details)
				{
					if (isset($Details[5]))
					{
						$VoucherNumber = strip_tags(trim($Details[5]));
					}
					if (isset($Details[2]))
					{
						$Buyer = $Details[2];
					}
					if (isset($Details[0]))
					{
						$Date = $Details[0];
					}
				}

				# SKIP IF FOUND HEADING IN PARTICULARS
				continue;
			}

            # READ ALL THE COLUMNS ON CURRENT ROW FROM A - J
            $rowData = $CurrentSheet->rangeToArray('A' . $row . ':' . 'J' . $row, NULL, TRUE, FALSE);            
			
            foreach ($rowData as $key => $Details)
            {
            	$QuantityAndUnitArray = array();
            	$QuantityAndUnit = '';

            	$QuantityAndUnit = $CurrentSheet->getCell('H' . $row)->getFormattedValue();

            	$QuantityAndUnitArray = explode(' ', $QuantityAndUnit);

				$RowData = array();
				
				$RowData['Date'] = '';
				$RowData['Particulars'] = '';
				$RowData['Buyer'] = '';
				$RowData['SupplierAddress'] = '';
				$RowData['VoucherNumber'] = '';
				$RowData['SupplierInvoiceNumber'] = '';
				$RowData['Quantity'] = 0;
				$RowData['ProductUnitName'] = '';
				$RowData['Amount'] = 0;
				$RowData['AdditionalCost'] = 0;
				
				if (isset($Date))
				{
					$RowData['Date'] = date('d/m/Y', PHPExcel_Shared_Date::ExcelToPHP($Date));
				}
				if (isset($Details[1]))
				{
					$RowData['Particulars'] = strip_tags(trim($Details[1]));
				}
				if (isset($Buyer))
				{
					$RowData['Buyer'] = strip_tags(trim($Buyer));
				}
				if (isset($Details[3]))
				{
					$RowData['SupplierAddress'] = strip_tags(trim($Details[3]));
				}

				$RowData['VoucherNumber'] = strip_tags(trim($VoucherNumber));

				if (isset($Details[6]))
				{
					$RowData['SupplierInvoiceNumber'] = strip_tags(trim($Details[6]));
				}

				$RowData['Quantity'] = strip_tags(trim($QuantityAndUnitArray[0]));
				$RowData['ProductUnitName'] = strip_tags(trim($QuantityAndUnitArray[1]));
				
				if (isset($Details[8]))
				{
					$RowData['Amount'] = strip_tags(trim($Details[8]));
				}
				if (isset($Details[9]))
				{
					$RowData['AdditionalCost'] = strip_tags(trim($Details[9]));
				}
				
				$RowValidator = new Validator();
				
				if (!$RowValidator->ValidateDate($RowData['Date'], ''))
				{
					$ErrorRows[$row][] = 'Invalid Date.';
				}

				if ($RowData['Date'] != '' && $RowData['Date'] != '')
				{
				$RowData['Date'] = date('Y-m-d', strtotime(DateProcessing::ToggleDateDayAndMonth(($RowData['Date']))));
				}
				
				if (!$RowValidator->ValidateStrings($RowData['Particulars'], '', 2, 100))
				{
					$ErrorRows[$row][] = 'Invalid particulars name. (Allowed 2 to 100 chars)';
				}
				
				if (!$RowValidator->ValidateStrings($RowData['Buyer'], '', 2, 250))
				{
					$ErrorRows[$row][] = 'Invalid Buyer name. (Allowed 2 to 250 chars)';
				}

				if (!UploadHelpers::IsMultipleStaffExist($RowData['Buyer']))
				{
					$ErrorRows[$row][] = 'Multiple Buyer with same name.';
				}
				
				if ($RowData['SupplierAddress'] != '' && !$RowValidator->ValidateStrings($RowData['SupplierAddress'], '', 2, 150))
				{
					$ErrorRows[$row][] = 'Invalid Buyer address. (Allowed 2 to 150 chars)';
				}
				
				if ($RowData['VoucherNumber'] != '' && !$RowValidator->ValidateStrings($RowData['VoucherNumber'], '', 1, 50))
				{
					$ErrorRows[$row][] = 'Invalid voucher number. (Allowed 1 to 50 chars)';
				}
				
				if ($RowData['SupplierInvoiceNumber'] != '' && !$RowValidator->ValidateStrings($RowData['SupplierInvoiceNumber'], '', 2, 50))
				{
					$ErrorRows[$row][] = 'Invalid Buyer invoice number. (Allowed 2 to 50 chars)';
				}
				
				if ($RowData['Quantity'] <= 0 || !$RowValidator->ValidateNumeric($RowData['Quantity'], ''))
				{
					$ErrorRows[$row][] = 'Invalid quantity.';
				}
				
				if ($RowData['Amount'] <= 0 || !$RowValidator->ValidateNumeric($RowData['Amount'], ''))
				{
					$ErrorRows[$row][] = 'Invalid amount.';
				}
				
				/*if ($RowData['AdditionalCost'] <= 0 || !$RowValidator->ValidateNumeric($RowData['AdditionalCost'], ''))
				{
					$ErrorRows[$row][] = 'Invalid additional cost value.';
				}*/
				
				$AllRows[$row] = $RowData;
            }
        }
		
		/*if (count($ErrorRows) > 0)
		{
			break;
		}*/
		
		# NOW SAVING THE DATA INTO DATABASE

		//var_dump($AllRows);exit;
		
		if (!UploadHelpers::SaveStockIssueFromExcel($AllRows))
		{
			$NewRecordValidator->AttachTextError('Error in uploaded file, please try again.');
			$HasErrors = true;
			break;
		}
		
		//header('location:upload_sales_data.php?POMode=RA');
		//exit;
    break;
}

require_once('../html_header.php');
?>
<title>Upload Inventory Data</title>
<link href="/admin/vendor/jquery-ui/jquery-ui.min.css" rel="stylesheet">
<style type="text/css">
	.error-row td {
		//color: red;
	}
</style>
</head>
<body>

    <div id="wrapper">
        <!-- Navigation -->
<!--        <nav class="navbar navbar-default navbar-static-top" role="navigation" style="margin-bottom: 0">
<?php 
            //require_once('../site_header.php');
            //require_once('../left_navigation_menu.php');
?>                    
             /.navbar-static-side 
        </nav>-->

        <div class="col-lg-12">
            <div class="row">
                <div class="col-lg-12">
                    <h3 class="page-header">Upload Inventory Data(Sales)</h3>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="AddStudent" action="upload_sales_data.php" method="post" enctype="multipart/form-data">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        Choose File
                    </div>
                    <div class="panel-body">
<?php
                        if ($HasErrors == true)
                        {
                            echo $NewRecordValidator->DisplayErrors();
                        }
?>
                        <div class="form-group">
                            <label for="StartFromRow" class="col-lg-2 control-label">Start From Row: </label>
                            <div class="col-lg-5">
                                <input type="number" class="form-control" id="StartFromRow" name="txtStartFromRow" value="<?php echo $Clean['StartFromRow']; ?>" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="UploadExcel" class="col-lg-2 control-label">Upload Excel: </label>
                            <div class="col-lg-5">
                                <input type="file" name="fileExcel" />
                            </div>
                        </div>
                        <div class="form-group">
                        <div class="col-sm-offset-2 col-lg-10">
                            <input type="hidden" name="hdnProcess" value="1"/>
                            <button type="submit" class="btn btn-primary">Upload</button>
                        </div>
                      </div>
                    </div>
                </div>
            </form>
<?php
		if (count($ErrorRows) > 0) 
        {
			echo '<div class="alert alert-success" role="alert">Total records uploaded: <strong>'. count($AllRows) .'</strong></div>';
            echo '<div class="alert alert-danger" role="alert">There were some errors in the records (<strong>'. count($ErrorRows) .'</strong>), please scroll down to see indivisual errors.</div>';
?>
            <table class="table table-bordered table-hover table-striped">
                <thead>
                    <tr>
						<th>S. No.</th>
                        <th>Excel Row No.</th>
                        <th>Date</th>
                        <th>Particulars</th>
                        <th>Buyer</th>
                        <th>Buyer Address</th>
                        <th>Voucher No.</th>
                        <th>Buyer Invoice No.</th>
						<th>Quantity</th>
						<th>Unit Name</th>
						<th>Amount</th>
						<th>Add. Cost</th>
						<th>Errors</th>
                    </tr>
                </thead>
                <tbody>
<?php
                $Counter = 0;
                foreach ($AllRows as $RowNumber => $Details)
                {
					$RowErrorString = '';
					
					if (array_key_exists($RowNumber, $ErrorRows))
					{
						$RowErrorString = implode('<br />', $ErrorRows[$RowNumber]);
					}

					if (!isset($ErrorRows[$RowNumber]))
					{
						continue;
					}
?>
                    <tr class="<?php echo $RowErrorString != '' ? 'error-row' : ''; ?>">
						<td><?php echo ++$Counter; ?></td>
                        <td><?php echo $RowNumber; ?></td>
                        <td><?php echo $Details['Date']; ?></td>
                        <td><?php echo $Details['Particulars']; ?></td>
                        <td><?php echo $Details['Buyer']; ?></td>
                        <td><?php echo $Details['SupplierAddress']; ?></td>
                        <td><?php echo $Details['VoucherNumber']; ?></td>
                        <td><?php echo $Details['SupplierInvoiceNumber']; ?></td>
						<td align="right"><?php echo number_format($Details['Quantity'], 2); ?></td>
                        <td><?php echo $Details['ProductUnitName']; ?></td>
						<td align="right"><?php echo number_format($Details['Amount'], 2); ?></td>
						<td align="right"><?php echo number_format($Details['AdditionalCost'], 2); ?></td>
						
						<td>
<?php
					if ($RowErrorString)
					{
?>
							<a data-html="true" data-toggle="tooltip" data-placement="left" title="<?php echo $RowErrorString; ?>" class="btn btn-xs btn-danger"><i class="fa fa-exclamation-triangle"></i></a>
<?php
					}
					else
					{
?>
							<a class="btn btn-xs btn-success"><i class="fa fa-check" aria-hidden="true"></i></a>
<?php
					}
?>
						</td>
                    </tr>
<?php
                }
?>
                </tbody>    
            </table>
<?php
        }

        else if ($Clean['Process'] == 1)
        {
        	echo '<div class="alert alert-success" role="alert">Total records uploaded: <strong>'. count($AllRows) .'</strong></div>';
        	//header('location:upload_purchase_data.php?POMode=RA');
        }
?>
        </div>
        <!-- /#page-wrapper -->

    </div>
    <!-- /#wrapper -->
<?php
require_once('../footer.php');
?>
<script>
$(document).ready(function(){
	$('[data-toggle="tooltip"]').tooltip();   
});
</script>
<script type="text/javascript">
<?php
    if (isset($_GET['POMode']))
    {
        $PageOperationResultMessage = '';
        $PageOperationResultMessage = UIHelpers::GetPageOperationResultMessage($_GET['POMode']);

        if ($PageOperationResultMessage != '')
        {
            echo 'alert("' . $PageOperationResultMessage . '");';
        }
    }
?>
</script>
</body>
</html>
<?php
//header('Content-Type: application/json');

require_once('../classes/class.users.php');
require_once('../classes/class.authentication.php');

try
{
	$AuthObject = new ApplicationAuthentication;
	$LoggedUser = new User(0, $AuthObject->CheckValidUser());
}

// THIS CATCH BLOCK BUBBLES THE EXCEPTION TO THE BUILT IN 'Exception' CLASS IF THERE ARE ANY UNCAUGHT ERRORS //
catch (ApplicationAuthException $e)
{
	echo 'error|*****|Unknown error, please try again.';
	exit;
}
catch (Exception $e)
{
	echo 'error|*****|Unknown error, please try again.';
	exit;
}

require_once('../classes/school_administration/class.academic_years.php');
require_once('../classes/fee_management/class.fee_collection.php');

$AcademicYears =  array();
$AcademicYears = AcademicYear::GetAllAcademicYears();

$FeeCollectionID = 0;

$TotalFeeAmount = 0;
$TotalDiscountAmount = 0;
$TotalMonthlyFeeAmount = 0;

if (isset($_POST['SelectedFeeCollectionID']))
{
	$FeeCollectionID = (int) $_POST['SelectedFeeCollectionID'];
}

if ($FeeCollectionID <= 0)
{
	echo 'error|*****|Unknown error, please try again.';
	exit;
}

$FeeCollectionDetails = array();
$FeeCollectionDetails = FeeCollection::GetFeeTransactionDetails($FeeCollectionID);

$OtherChargesDetails = array();
$OtherChargesDetails = FeeCollection::GetFeeTransactionOtherChargesDetails($FeeCollectionID);

if (count($FeeCollectionDetails) < 0)
{
	echo 'error|*****|No record found.';
	exit;
}

echo 'success|*****|';

?>

<style type="text/css">
	.table tr.primary {
  background-color: #337ab7 !important;
  color: white;
}
</style>
<?php
foreach ($FeeCollectionDetails as $AcademicYearID => $FeeDetails)
{
    $Year = '';

	foreach ($FeeDetails as $Month => $Details) 
	{
	    if (count($FeeCollectionDetails) > 1) 
        {   
            if ($MonthName == 'January' || $MonthName == 'February' || $MonthName == 'March') 
            {
                $Year = '['. date('y', strtotime($AcademicYears[$AcademicYearID]['EndDate'])) .']';
            }
            else
            {
                $Year = '['. date('y', strtotime($AcademicYears[$AcademicYearID]['StartDate'])) .']';
            }
        }
?>
		<div class="row" id="<?php echo 'RecordTable' . $Month; ?>">
		    <div class="col-lg-12">
		        <table width="100%" class="table table-striped table-bordered">
		        	<thead>
		        		<tr class="primary"><th colspan="6"><?php echo $Month . $Year; ?>:</th></tr>
		        		<tr>
		        			<th>Sr.No.</th>
		        			<th>Fee Head</th>
		        			<th>Amount</th>
		        			<th>Discount</th>
		        			<th>Paid Amount</th>
		        			<th>Due</th>
						</tr>
		            </thead>
		            <tbody>
		            	
		<?php 
				$Counter = 0;
				$TotalFeeAmount = 0;
				$TotalDiscountAmount = 0;
				$TotalPaidAmount = 0;
				$TotalDueAmount = 0;

				foreach ($Details as $FeeHeadID => $FeeDetail) 
				{
					$FeeAmount = $FeeDetail['FeeAmount'];

					$DiscountType = $FeeDetail['DiscountType'];
					$DiscountValue = $FeeDetail['DiscountValue'];
					$DiscountAmount = $FeeDetail['DiscountAmount'];

					$TotalPaidAmount += $FeeDetail['PaidAmount'];
					$TotalDueAmount += $FeeDetail['RestAmount'];

					$TotalFeeAmount += $FeeAmount;

					$TotalDiscountAmount += $DiscountAmount;
					
					echo '<tr class="text-right">';				
						echo '<th>'. ++$Counter .'.</th>';
						echo '<td>'. $FeeDetail['FeeHead'] .'</td>';
						echo '<td>'. $FeeDetail['FeeAmount'] .'&nbsp;<i class="fa fa-inr"></i></td>';

						echo '<td>' . (($DiscountValue) ? "( " . $DiscountValue . (($DiscountType == 'Percentage') ? ' %' : ' <i class="fa fa-inr"></i>') . ") " : '')  . number_format($DiscountAmount, 2) . ' <i class="fa fa-inr"></i></td>';

						echo '<td>'. number_format($FeeDetail['PaidAmount'], 2) .' <i class="fa fa-inr"></i></td>';
						echo '<td class="text-danger">'. number_format($FeeDetail['RestAmount'], 2) .' <i class="fa fa-inr"></i></td>';

					echo '</tr>';
					
				}

				echo '<tr class="primary text-right">';
					echo '<td colspan="2"><strong>Total :</strong></td>';
					echo '<td>'. number_format($TotalFeeAmount, 2) .'&nbsp;&nbsp;<i class="fa fa-inr"></i></td>';
					echo '<td>'. number_format($TotalDiscountAmount, 2) .'&nbsp;&nbsp;<i class="fa fa-inr"></i></td>';
					echo '<td>'. number_format($TotalPaidAmount, 2) .'&nbsp;&nbsp;<i class="fa fa-inr"></i></td>';
					echo '<td>'. number_format($TotalDueAmount, 2) .'&nbsp;&nbsp;<i class="fa fa-inr"></i></td>';
				echo '</tr>';
		?>
						
		            </tbody>
		        </table>
		    </div> 
		</div>
<?php			
	}
}

	if (count($OtherChargesDetails) > 0) 
	{
?>
		<div class="row">
		    <div class="col-lg-12">
		        <table width="100%" class="table table-striped table-bordered">
		        	<thead>
		        		<tr class="primary"><th colspan="4">Other Charges:</th></tr>
		        		<tr>
		        			<th>Sr.No.</th>
		        			<th>Fee Type</th>
		        			<th>Description</th>
		        			<th>Amount</th>
						</tr>
		            </thead>
		            <tbody>
		            	
		<?php 
				$Counter = 0;
				$TotalOtherChargeAmount = 0;

				foreach ($OtherChargesDetails as $FeeCollectionOtherChargeID => $Detail) 
				{
					
					echo '<tr>';				
						echo '<th>'. ++$Counter .'.</th>';
						echo '<td>'. $Detail['FeeType'] .'</td>';
						echo '<td>'. $Detail['FeeDescription'] .'</td>';
						echo '<td class="text-right">'. number_format($Detail['Amount'], 2) .' <i class="fa fa-inr"></i></td>';
					echo '</tr>';
					
					$TotalOtherChargeAmount += $Detail['Amount'];
				}

				echo '<tr class="primary text-right">';
					echo '<td colspan="3"><strong>Total :</strong></td>';
					echo '<td>'. number_format($TotalOtherChargeAmount, 2) .'&nbsp;&nbsp;<i class="fa fa-inr"></i></td>';
				echo '</tr>';

		?>
						
		            </tbody>
		        </table>
		    </div> 
		</div>
<?php		
	}
?>	

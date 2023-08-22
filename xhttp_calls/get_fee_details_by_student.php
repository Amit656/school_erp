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

require_once('../classes/fee_management/class.fee_collection.php');

$AcademicYearID = 0;
$StudentID = 0;
$TotalDueAmount = 0;
$MonthList = array();

if (isset($_POST['SelectedStudentID']))
{
	$StudentID = (int) $_POST['SelectedStudentID'];
}

if (isset($_POST['SelectedAcademicYearID']))
{
	$AcademicYearID = (int) $_POST['SelectedAcademicYearID'];
}

if (isset($_POST['MonthList']))
{
	$MonthList = $_POST['MonthList'];
}

if ($StudentID <= 0 || count($MonthList) <= 0)
{
	echo 'error|*****|Unknown error, please try again.';
	exit;
}

$FeeDetails = array();
$FeeDetails = FeeCollection::GetFeeDetailsByStudent($StudentID, $AcademicYearID, $MonthList);

if (count($FeeDetails) < 0)
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
	foreach ($FeeDetails as $Month => $Details) 
	{
		if (count($Details) > 0) 
		{
?>
		<div class="row" id="<?php echo 'RecordTable' . $Month; ?>">
		    <div class="col-lg-12">
		        <table width="100%" class="table table-striped table-bordered">
		        	<thead>
		        		<tr class="primary"><th colspan="8"><?php echo $Month; ?>:</th></tr>
		        		<tr>
		        			<th>Sr.No.</th>
		        			<th>Fee Head</th>
		        			<th>Total Fee</th>
		        			<th>Disscount</th>
		        			<th>Concession</th>
		        			<th>Wave-Off</th>
		        			<th>Paid</th>
		        			<th>Due Amount</th>
						</tr>
		            </thead>
		            <tbody>
		            	
		<?php 
				$Counter = 0;
				$TotalFee = 0;
				$TotalDiscount = 0;
				$TotalConcession = 0;
				$TotalWaveOff = 0;	
				$TotalPaidAmount = 0;
				$TotalDue = 0;

				foreach ($Details as $FeeHeadID => $FeeDetail) 
				{
					$TotalFee += $FeeDetail['TotalAmount'];
					$TotalDiscount += $FeeDetail['DiscountAmount'];
					$TotalConcession += $FeeDetail['TotalConcession'];
					$TotalWaveOff += $FeeDetail['TotalWaveOff'];
					$TotalPaidAmount += $FeeDetail['PaidAmount'];
					$TotalDue += $FeeDetail['DueAmount'];
					
					echo '<tr class="text-right">';				
						echo '<th>'. ++$Counter .'.</th>';
						echo '<td>'. $FeeDetail['FeeHead'] .'</td>';
						echo '<td>'. (($FeeDetail['TotalAmount'] > 0) ? number_format($FeeDetail['TotalAmount'], 2) : '--') .'</td>';
						echo '<td>'. (($FeeDetail['DiscountAmount'] > 0) ? number_format($FeeDetail['DiscountAmount'], 2) : '--') .'</td>';
						echo '<td>'. (($FeeDetail['TotalConcession'] > 0) ? number_format($FeeDetail['TotalConcession'], 2) : '--') .'</td>';
						echo '<td>'. (($FeeDetail['TotalWaveOff'] > 0) ? number_format($FeeDetail['TotalWaveOff'], 2) : '--') .'</td>';
						echo '<td>'. (($FeeDetail['PaidAmount'] > 0) ? number_format($FeeDetail['PaidAmount'], 2) : '--') .'</td>';
						echo '<td>'. (($FeeDetail['DueAmount'] > 0) ? number_format($FeeDetail['DueAmount'], 2) : '--') .'</td>';

					echo '</tr>';
				}

				echo '<tr class="primary text-right">';
					echo '<td colspan="2"><strong>Total :</strong></td>';
					echo '<td>'. number_format($TotalFee, 2) .'</td>';
					echo '<td>'. number_format($TotalDiscount, 2) .'</td>';
					echo '<td>'. number_format($TotalConcession, 2) .'</td>';
					echo '<td>'. number_format($TotalWaveOff, 2) .'</td>';
					echo '<td>'. number_format($TotalPaidAmount, 2) .'</td>';
					echo '<td>'. number_format($TotalDue, 2) .'</td>';
				echo '</tr>';

		?>
						
		            </tbody>
		        </table>
		    </div> 
		</div>
<?php	
		}		
	}
?>	

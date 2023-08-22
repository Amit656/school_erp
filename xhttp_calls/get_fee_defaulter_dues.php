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
require_once('../classes/school_administration/class.academic_years.php');

$StudentID = 0;
$TotalDueAmount = 0;
$AcademicYearID = 0;
$FeePriority = 0;

if (isset($_POST['SelectedStudentID']))
{
	$StudentID = (int) $_POST['SelectedStudentID'];
}

if (isset($_POST['AcademicYearID']))
{
	$AcademicYearID = (int) $_POST['AcademicYearID'];
}

if (isset($_POST['FeePriority']))
{
	$FeePriority = (int) $_POST['FeePriority'];
}

if ($StudentID <= 0 || $FeePriority <= 0 || $AcademicYearID <= 0)
{
	echo 'error|*****|Unknown error, please try again.';
	exit;
}

$AcademicYears =  array();
$AcademicYears = AcademicYear::GetAllAcademicYears();

$PreviousYearDue = 0;

$FeeDefaulterDues = array();
$FeeDefaulterDues[$AcademicYearID] = FeeCollection::GetFeeDefaulterDues($StudentID, $FeePriority, $AcademicYearID, $PreviousYearDue);

if ($AcademicYearID == 2) 
{
	//For Previous Year Dues Fees
	$FeeDefaulterDues[1] = FeeCollection::GetFeeDefaulterDues($StudentID, 120, 1, $PreviousYearDue);
}

if (count($FeeDefaulterDues) < 0)
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
    if ($PreviousYearDue > 0)
    {
        echo '<div class="alert alert-info">Previous Due: <strong>' . $PreviousYearDue . '</strong></div>';
    }
    
foreach ($FeeDefaulterDues as $AcademicYearID => $FeeDefaulterDuesDetails) 
{
	$Year = ' ['. date('Y', strtotime($AcademicYears[$AcademicYearID]['StartDate'])) .'-' . date('y', strtotime($AcademicYears[$AcademicYearID]['EndDate'])) .']';

	foreach ($FeeDefaulterDuesDetails as $Month => $Details) 
	{
		if (count($Details) > 0) 
		{
?>
		<div class="row" id="<?php echo 'RecordTable' . $Month . $Year; ?>">
		    <div class="col-lg-12">
		        <table width="100%" class="table table-striped table-bordered">
		        	<thead>
		        		<tr class="primary"><th colspan="6"><?php echo $Month . $Year; ?>:</th></tr>
		        		<tr>
		        			<th>Sr.No.</th>
		        			<th>Fee Head</th>
		        			<th>Amount</th>
		        			<th>Disscount</th>
		        			<th>Paid</th>
		        			<th>Due Amount</th>
						</tr>
		            </thead>
		            <tbody>
		            	
		<?php 
				$Counter = 0;
				$TotalFeeAmount = 0;
				$TotalDiscountAmount = 0;
				$TotalPaidAmount = 0;
				$TotalMonthlyFeeAmount = 0;

				foreach ($Details as $FeeHeadID => $FeeDetail) 
				{
					$FeeAmount = $FeeDetail['FeeAmount'];

					$DiscountType = $FeeDetail['DiscountType'];
					$DiscountValue = $FeeDetail['DiscountValue'];
					$DiscountAmount = $FeeDetail['DiscountAmount'];

					$TotalMonthlyFeeAmount += $FeeDetail['FeeHeadAmount'];
					$TotalDueAmount += $FeeDetail['FeeHeadAmount'];

					$TotalFeeAmount += $FeeAmount;

					$TotalDiscountAmount += $DiscountAmount;
					$TotalPaidAmount += $FeeDetail['AmountPaid'];
					
					echo '<tr class="text-right">';				
						echo '<th>'. ++$Counter .'.</th>';
						echo '<td>'. $FeeDetail['FeeHead'] .'</td>';
						echo '<td>'. number_format($FeeDetail['FeeAmount'], 2) .'&nbsp;<i class="fa fa-inr"></i></td>';

						// echo '<td>' . (($DiscountValue) ? "( " . $DiscountValue . (($DiscountType == 'Percentage') ? ' %' : ' <i class="fa fa-inr"></i>') . ") " : '')  . number_format($DiscountAmount, 2) . ' <i class="fa fa-inr"></i></td>';

						echo '<td>'. number_format(($DiscountAmount), 2) .' <i class="fa fa-inr"></i></td>';
						echo '<td>'. number_format(($FeeDetail['AmountPaid']), 2) .' <i class="fa fa-inr"></i></td>';
						echo '<td>'. number_format(($FeeAmount - $DiscountAmount - $FeeDetail['AmountPaid']), 2) .' <i class="fa fa-inr"></i></td>';

					echo '</tr>';
				}

				echo '<tr class="primary text-right">';
					echo '<td colspan="2"><strong>Total :</strong></td>';
					echo '<td>'. number_format($TotalFeeAmount, 2) .'&nbsp;&nbsp;<i class="fa fa-inr"></i></td>';
					echo '<td>'. number_format($TotalDiscountAmount, 2) .'&nbsp;&nbsp;<i class="fa fa-inr"></i></td>';
					echo '<td>'. number_format($TotalPaidAmount, 2) .'&nbsp;&nbsp;<i class="fa fa-inr"></i></td>';
					echo '<td>'. number_format($TotalMonthlyFeeAmount, 2) .'&nbsp;&nbsp;<i class="fa fa-inr"></i></td>';
				echo '</tr>';

		?>
						
		            </tbody>
		        </table>
		    </div> 
		</div>
<?php	
		}		
	}
}

?>
	<table width="100%" class="table table-striped table-bordered">
    	<thead>
    		<tr class="bg-success text-right" style="color: red;">
    			<th class="text-right">Total Due :</th>
    			<th class="text-right"><?php echo number_format($TotalDueAmount + $PreviousYearDue, 2)?>&nbsp;&nbsp;<i class="fa fa-inr"></i></th>
			</tr>
        </thead>
    </table>
<?php	
?>	

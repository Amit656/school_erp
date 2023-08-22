<?php
//header('Content-Type: application/json');

require_once("../classes/class.validation.php");
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

$NoOfInstalments = 0;
$AdvanceSalaryAmount = 0.0;

$AdvanceSalaryDedutionMonths = array();

if (isset($_GET['txtNoOfInstalments']))
{
	$NoOfInstalments = (int) $_GET['txtNoOfInstalments'];
}

if (isset($_GET['txtAdvanceAmount']))
{
	$AdvanceSalaryAmount = strip_tags(trim($_GET['txtAdvanceAmount']));
}

$RecordValidator = new Validator();

if (!$RecordValidator->ValidateInteger($NoOfInstalments, 'No Of instalments should be interger.', 1)) 
{
	echo 'error|*****|Advance salary amount should be interger.';
	exit;
}

if (!$RecordValidator->ValidateNumeric($AdvanceSalaryAmount, 'Advance salary amount should be numeric.')) 
{
	echo 'error|*****|Advance salary amount should be numeric.';
	exit;
}

echo 'success|*****|';

$Amount = 0;
$RemainingAmount = 0;

$RemainingAmount = CalculateInstalmentAmount($AdvanceSalaryAmount, $NoOfInstalments);
$Amount = (int) ($AdvanceSalaryAmount / $NoOfInstalments);
?>

<label for="AdvanceType" class="col-lg-2 control-label">Deduction Month</label>
<div class="col-lg-8">
	<input type="hidden" name="txtTotalNumberOfMonth" id="TotalNumberOfMonthRows" value="<?php echo $NoOfInstalments; ?>">
	<table width="100%" class="table table-striped table-bordered table-hover" id="DataTableRecords">
		<thead>
            <tr>
                <th>Deduction Months</th>
                <th>Deduction Amount</th>
            </tr>
        </thead>
        <tbody>

<?php

// Getting TimeStamp Of current month of First Date of month
$CurrentMonthDate  = '01-' . date('m-Y');

for ($BreakupStartingMonth = 1; $BreakupStartingMonth <= $NoOfInstalments; $BreakupStartingMonth++) 
{
	if ($BreakupStartingMonth == $NoOfInstalments) 
	{
		$Amount = $Amount + $RemainingAmount;
	}
?>	
				<tr>
					<td>
						<label style="font-weight: normal;">
							<input class="GetNextMonthForDeduction" time-stamp="<?php echo strtotime($CurrentMonthDate); ?>" type="checkbox" name="AdvanceSalaryDedutionMonths[<?php echo strtotime($CurrentMonthDate); ?>][Amount]" value="<?php echo $Amount; ?>" checked="checked=checked"/>&nbsp;<?php echo date('M Y', strtotime($CurrentMonthDate)); ?>
						</label>
					</td>
					<td>
						<input class="form-control" style="width:100%;" type="text" name="txtAdvanceSalaryInstalmentAmount[<?php echo strtotime($CurrentMonthDate); ?>]" value="<?php echo $Amount?>" readonly="readonly"/>
					</td>
				</tr>
<?php
		$TimeStampCurrentMonthDate = strtotime($CurrentMonthDate);
		$CurrentMonthDate = date('d-m-Y', strtotime('+1 month', $TimeStampCurrentMonthDate));
}
?>
		</tbody>
	</table>
</div>
<?php

exit;

function CalculateInstalmentAmount($AdvanceSalaryAmount, $NoOfInstalments)
{	
	$Amount = 0;
	$RemainingAmount = 0;

	$Amount = (int) ($AdvanceSalaryAmount / $NoOfInstalments);
	$RemainingAmount = $AdvanceSalaryAmount - ($NoOfInstalments * $Amount);

	return $RemainingAmount;
}
?>

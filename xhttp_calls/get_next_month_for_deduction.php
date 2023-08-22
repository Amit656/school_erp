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

$TimeStamp = 0;
$Amount = 0;
$TotalNumberOfMonthRows = 0;

if (isset($_GET['txtTimeStamp']))
{
	$TimeStamp = strip_tags(trim($_GET['txtTimeStamp']));
}

if (isset($_GET['txtAmount']))
{
	$Amount = strip_tags(trim($_GET['txtAmount']));
}

$RecordValidator = new Validator();

if (!$RecordValidator->ValidateInteger($TimeStamp, 'Unknown error, please try again.', 1)) 
{
	echo 'error|*****|Unknown error, please try again.';
	exit;
}

if (!$RecordValidator->ValidateNumeric($Amount, 'Unknown error, please try again.', 1)) 
{
	echo 'error|*****|Unknown error, please try again.';
	exit;
}

if (isset($_GET['txtTotalNumberOfMonthRows']))
{
	$TotalNumberOfMonthRows = (int) $_GET['txtTotalNumberOfMonthRows'];
}



echo 'success|*****|';


// Getting TimeStamp Of current month of First Date of month
$CurrentMonthDate  = strtotime('01-' . date('m-Y'));

$String = '+' . $TotalNumberOfMonthRows . ' month';

$CurrentMonthDate = date('d-m-Y', strtotime($String, $CurrentMonthDate));

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
exit;
?>

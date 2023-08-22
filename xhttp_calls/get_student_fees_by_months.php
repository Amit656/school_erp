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

require_once('../classes/school_administration/class.academic_year_months.php');
require_once('../classes/school_administration/class.class_sections.php');
require_once('../classes/school_administration/class.students.php');
require_once('../classes/school_administration/class.student_details.php');
require_once('../classes/fee_management/class.fee_collection.php');

$MonthID = 0;
$StudentID = 0;

$TotalFeeAmount = 0;
$TotalDiscountAmount = 0;

$StudentMonthlyFeeDetails = array();
$StudentFeeStructureDetails = array();

if (isset($_POST['SelectedMonthID']))
{
	$MonthID = (int) $_POST['SelectedMonthID'];
}
if (isset($_POST['SelectedStudentID']))
{
	$StudentID = (int) $_POST['SelectedStudentID'];
}

if ($MonthID <= 0 || $StudentID <= 0)
{
	echo 'error|*****|No fee structure is assigned to this student, please assign first.';
	exit;
}

try
{
	$StudentDetailObject = new StudentDetail($StudentID);    
}
catch (ApplicationDBException $e)
{
	header('location:/admin/error.php');
	exit;
}
catch (Exception $e)
{
	header('location:/admin/error.php');
	exit;
}

try
{
	$ClassSectionObject = new ClassSections($StudentDetailObject->GetClassSectionID());    
}
catch (ApplicationDBException $e)
{
	header('location:/admin/error.php');
	exit;
}
catch (Exception $e)
{
	header('location:/admin/error.php');
	exit;
}

$ClassID = $ClassSectionObject->GetClassID();

$FeeMonths = array();
$FeeMonths = $StudentDetailObject->GetStudentFeeMonths();

if (!array_key_exists($MonthID, $FeeMonths))
{
	echo 'error|*****|Unknown error, please try again.';
	exit;
}

$StudentFeeGroupID = $StudentDetailObject->GetStudentWiseFeeGroupID();

if ($StudentFeeGroupID <= 0)
{
	echo 'error|*****|This student is not in any student group, please assign student in a group.';
	exit;
}

$TotalMonthlyFeeAmount = 0;

$TotalMonthlyFeeAmount = $StudentDetailObject->GetStudentMonthlyFeeDetails($MonthID, $StudentMonthlyFeeDetails);

$LastYearSubmitedFee = 0;

/*if ($ClassID == 9) 
{
	if ($MonthID == 104 || $MonthID == 105 || $MonthID == 106) 
	{
		if (FeeCollection::IsStudentFeeCollected($StudentID, $MonthID, $LastYearSubmitedFee, $StudentFeeStructureID)) 
		{
			$TotalMonthlyFeeAmount = $TotalMonthlyFeeAmount - $LastYearSubmitedFee;
		}	
	}
}*/

if ($TotalMonthlyFeeAmount <= 0)
{
	echo 'error|*****|No records found.';
	exit;
}

echo 'success|*****|';

echo $TotalMonthlyFeeAmount;
echo '|*****|';
?>
	
<div class="row" id="<?php echo 'RecordTable' . $MonthID; ?>">
    <div class="col-lg-12">
        <table width="100%" class="table table-striped table-bordered">
        	<thead>
        		<tr><th colspan="7"><?php echo $FeeMonths[$MonthID]['MonthName'] .' ( <small>'. $StudentDetailObject->GetFirstName() .' '. $StudentDetailObject->GetLastName() .'</small> ) '; ?>:</th></tr>
        		<tr>
        			<th>Sr.No.</th>
        			<th>Fee Head</th>
        			<th>Amount</th>
        			<th>Disscount</th>
        			<th>Final Amount</th>
        			<th>Concession</th>
        			<th>Wave OFF</th>
				</tr>
            </thead>
            <tbody>
            	
<?php 
		$Counter = 0;

		foreach ($StudentMonthlyFeeDetails as $StudentFeeStructureID => $MonthlyFeeDetail) 
		{
			$FeeAmount = $MonthlyFeeDetail['FeeAmount'];

			$DiscountType = $MonthlyFeeDetail['DiscountType'];
			$DiscountValue = $MonthlyFeeDetail['DiscountValue'];
			$DiscountAmount = $MonthlyFeeDetail['DiscountAmount'];

			$TotalMonthlyFeeAmount = $MonthlyFeeDetail['TotalMonthlyFeeAmount'];

			$TotalFeeAmount += $FeeAmount;

			$TotalDiscountAmount += $DiscountAmount;
			
			echo '<tr class="text-right">';				
				echo '<td><label class="checkbox-inline"><input class="custom-radio StudentFeeStructureID" type="checkbox" id="'. $StudentFeeStructureID .'" name="chkStudentFeeStructureID[]" value="'. $StudentFeeStructureID .'" checked="checked" />'. ++$Counter .'.</td>';
				echo '<td>'. $MonthlyFeeDetail['FeeHead'] .'</td>';
				echo '<td>'. $MonthlyFeeDetail['FeeAmount'] .'</td>';

				echo '<td>' . (($DiscountValue) ? "( " . $DiscountValue . (($DiscountType == 'Percentage') ? ' %' : ' <i class="fa fa-inr"></i>') . ") " : '')  . number_format($DiscountAmount, 2) . ' <i class="fa fa-inr"></i></td>';

				echo '<td><input type="text" class="form-control StudentFeeHeadAmount CheckedFeeHead'. $MonthID .'" id="HeadAmount'. $StudentFeeStructureID .'" name="txtStudentFeeHeadAmount['. $StudentFeeStructureID .']" value="'. ($FeeAmount - $DiscountAmount) .'"> </i>
					</td>';
				echo '<td><input type="text" class="form-control ConcessionAmount ConcessionAmount'. $MonthID .'" id="ConcessionAmount'. $StudentFeeStructureID .'" name="txtConcessionAmount['. $StudentFeeStructureID .']" value=""> </i>
				</td>';
				echo '<td><label class="checkbox-inline"><input class="custom-radio WaveOff" type="checkbox" id="WaveOff'. $StudentFeeStructureID .'" name="chkWaveOff['. $StudentFeeStructureID .']" value="'. ($FeeAmount - $DiscountAmount) .'" /> OFF</td>';
			echo '</tr>';
			
		}

		if ($LastYearSubmitedFee > 0) 
		{
			echo '<tr class="text-right">';
				echo '<td colspan="4"><strong>Last Year Submited Fee :</strong></td>';
				echo '<td>'. number_format($LastYearSubmitedFee, 2) .'&nbsp;</td>';
			echo '</tr>';
		}

		echo '<tr class="text-right">';
			echo '<td colspan="2"><strong>Total :</strong></td>';
			echo '<td>'. number_format($TotalFeeAmount, 2) .'&nbsp;</td>';
			echo '<td>'. number_format($TotalDiscountAmount, 2) .'&nbsp;</td>';
			echo '<td>'. number_format($TotalMonthlyFeeAmount - $LastYearSubmitedFee, 2) .'&nbsp;</td>';
			echo '<td></td>';
			echo '<td></td>';
		echo '</tr>';
?>
				
            </tbody>
        </table>
    </div> 
</div>
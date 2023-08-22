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
require_once('../classes/school_administration/class.academic_year_months.php');
require_once('../classes/school_administration/class.class_sections.php');
require_once('../classes/school_administration/class.students.php');
require_once('../classes/school_administration/class.student_details.php');
require_once('../classes/fee_management/class.fee_collection.php');

$MonthID = 0;
$StudentID = 0;
$AcademicYearID = 0;

$TotalFeeAmount = 0;
$TotalDiscountAmount = 0;
$TotalPaidAmount = 0;

$StudentMonthlyFeeDetails = array();
$StudentFeeStructureDetails = array();

if (isset($_POST['SelectedMonthID']))
{
	$MonthID = (int) $_POST['SelectedMonthID'];
}

if (isset($_POST['SelectedAcademicYearID']))
{
	$AcademicYearID = (int) $_POST['SelectedAcademicYearID'];
}

if (isset($_POST['SelectedStudentID']))
{
	$StudentID = (int) $_POST['SelectedStudentID'];
}

if ($MonthID <= 0 || $StudentID <= 0 || $AcademicYearID <= 0)
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
	echo 'error|*****|Unknown error, please try again.';
	exit;
}
catch (Exception $e)
{
	echo 'error|*****|Unknown error, please try again.';
	exit;
}

try
{
	$ClassSectionObject = new ClassSections($StudentDetailObject->GetClassSectionID());    
}
catch (ApplicationDBException $e)
{
	echo 'error|*****|Unknown error, please try again.';
	exit;
}
catch (Exception $e)
{
	echo 'error|*****|Unknown error, please try again.';
	exit;
}

$ClassID = $ClassSectionObject->GetClassID();

$DueFeeMonths = array();
$DueFeeMonths = $StudentDetailObject->GetStudentDueFeeMonths();;

if (!array_key_exists($MonthID, $DueFeeMonths[$AcademicYearID]))
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
$TotalMonthlyFeeAmount = $StudentDetailObject->GetStudentMonthlyFeeDetails($MonthID, $StudentMonthlyFeeDetails, $AcademicYearID);

if ($TotalMonthlyFeeAmount <= 0)
{
	echo 'error|*****|No records found.';
	exit;
}

$AcademicYears =  array();
$AcademicYears = AcademicYear::GetAllAcademicYears();

$Year = '';

if ($MonthID == 101 || $MonthID == 102 || $MonthID == 103) 
{
	$Year = '['. date('Y', strtotime($AcademicYears[$AcademicYearID]['EndDate'])) .']';
}
else
{
	$Year = '['. date('Y', strtotime($AcademicYears[$AcademicYearID]['StartDate'])) .']';
}

echo 'success|*****|';

echo $TotalMonthlyFeeAmount;
echo '|*****|';
?>
	
<div class="row" id="<?php echo 'RecordTable' . $AcademicYearID.$MonthID; ?>">
    <div class="col-lg-12">
        <table width="100%" class="table table-striped table-bordered">
        	<thead>
        		<tr><th colspan="8"><?php echo $DueFeeMonths[$AcademicYearID][$MonthID]['MonthName'] . $Year .' ( <small>'. $StudentDetailObject->GetFirstName() .' '. $StudentDetailObject->GetLastName() .'</small> ) '; ?>:</th></tr>
        		<tr>
        			<th>Sr.No.</th>
        			<th>Fee Head</th>
        			<th>Amount</th>
        			<th>Disscount</th>
        			<th>Paid</th>
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
			$TotalPaidAmount += $MonthlyFeeDetail['AmountPaid'];

			$TotalFeeAmount += $FeeAmount;

			$TotalDiscountAmount += $DiscountAmount;
			
			echo '<tr class="text-right">';				
				echo '<td><label class="checkbox-inline"><input class="custom-radio StudentFeeStructureID" type="checkbox" id="'. $StudentFeeStructureID .'" name="chkStudentFeeStructureID[]" value="'. $StudentFeeStructureID .'" checked="checked" />'. ++$Counter .'.</td>';
				echo '<td>'. $MonthlyFeeDetail['FeeHead'] .'</td>';
				echo '<td>'. $MonthlyFeeDetail['FeeAmount'] .'</td>';

				echo '<td>' . (($DiscountValue) ? "( " . $DiscountValue . (($DiscountType == 'Percentage') ? ' %' : ' <i class="fa fa-inr"></i>') . ") " : '')  . number_format($DiscountAmount, 2) . ' <i class="fa fa-inr"></i></td>';

				echo '<td>'. $MonthlyFeeDetail['AmountPaid'] .'</td>';
				echo '<td><input type="text" class="form-control StudentFeeHeadAmount CheckedFeeHead'. $MonthID .'" id="HeadAmount'. $StudentFeeStructureID .'" name="txtStudentDueFeeHeadAmount['. $StudentFeeStructureID .']" value="'. ($FeeAmount - $DiscountAmount - $MonthlyFeeDetail['AmountPaid']) .'"> </i>
					</td>';
				echo '<td><input type="text" class="form-control ConcessionAmount ConcessionAmount'. $MonthID .'" id="ConcessionAmount'. $StudentFeeStructureID .'" name="txtConcessionAmount['. $StudentFeeStructureID .']" value=""> </i>
					</td>';
				echo '<td><label class="checkbox-inline"><input class="custom-radio WaveOff" type="checkbox" id="WaveOff'. $StudentFeeStructureID .'" name="chkWaveOff['. $StudentFeeStructureID .']" value="'. ($FeeAmount - $DiscountAmount - $MonthlyFeeDetail['AmountPaid']) .'" /> OFF</td>';

			echo '</tr>';
			
		}

		echo '<tr class="text-right">';
			echo '<td colspan="2"><strong>Total :</strong></td>';
			echo '<td>'. number_format($TotalFeeAmount, 2) .'&nbsp;</td>';
			echo '<td>'. number_format($TotalDiscountAmount, 2) .'&nbsp;</td>';
			echo '<td>'. number_format($TotalPaidAmount, 2) .'&nbsp;</td>';
			echo '<td>'. number_format($TotalMonthlyFeeAmount, 2) .'&nbsp;</td>';
			echo '<td></td>';
			echo '<td></td>';
		echo '</tr>';
?>
				
            </tbody>
        </table>
    </div> 
</div>
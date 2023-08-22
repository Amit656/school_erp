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

require_once("../classes/school_administration/class.academic_years.php");
require_once("../classes/school_administration/class.academic_year_months.php");

require_once("../classes/fee_management/class.fee_heads.php");
require_once("../classes/fee_management/class.fee_groups.php");
require_once("../classes/fee_management/class.fee_structure.php");
require_once("../classes/fee_management/class.fee_discounts.php");

$ClassID = 0;
$FeeGroupID = 0;

if (isset($_POST['SelectedClassID']))
{
	$ClassID = (int) $_POST['SelectedClassID'];
}
if (isset($_POST['SelectedFeeGroupID']))
{
	$FeeGroupID = (int) $_POST['SelectedFeeGroupID'];
}

if ($ClassID <= 0)
{
	echo 'error|*****|Unknown error, please try again.';
	exit;
}

$Clean = array();

$Clean['FeeDiscountType'] = 'Group';
$Clean['DiscountTypeList'] = array('Absolute' => 'Absolute');
$Clean['DiscountValueList'] = array();
$Clean['AfterDiscountList'] = array();
$Clean['FeeStructureDetailIDList'] = array();
$Clean['FeeHeadIDList'] = array();
$Clean['FeeAmountList'] = array();

$AllMonths = array();
$AllMonths = AcademicYearMonth::GetMonthsByFeePriority();

$FeeGroupList = array();
$FeeGroupList = FeeGroup::GetActiveFeeGroups();

$AllFeeHeads = array();
$AllFeeHeads = FeeHead::GetActiveFeeHeads();

$FeeStructureDetails = array();
$AcademicYearID = AcademicYear::GetCurrentAcademicYear($AcademicYearName);

if (!FeeStructure::FeeStructureExists($AcademicYearID, $ClassID, $FeeGroupID, $FeeStructureDetails))
{
	echo 'error|*****|No fee structure is assigned for this groups! Please assign first.';
	exit;
}

$AllotedDiscount = FeeDiscount::GetFeeStructureDiscount($FeeGroupID, $ClassID, 0);
$Clean['DiscountTypeList'] = array();

foreach ($AllotedDiscount as $FeeHeadID => $DiscountDetails) 
{                
    foreach ($DiscountDetails as $FeeStructureDetailID => $Details) 
    {
        $Clean['FeeStructureDetailIDList'][$FeeHeadID][$FeeStructureDetailID] = 1;
        $Clean['DiscountTypeList'][$FeeHeadID][$Details['DiscountType']] = $Details['DiscountType'];
        $Clean['DiscountValueList'][$FeeHeadID] = $Details['DiscountValue'];   
    }
}

echo 'success|*****|';
?>
<table width="100%" class="table table-striped table-bordered table-hover">
    <thead>
        <tr>
            <th>Fee Head</th>
            <th>Amount</th>
            <th>Discount Value</th>
            <th>Discount Type</th>
            <th>After Discount</th>
            <th>Months</th>
        </tr>
    </thead>
    <tbody>
<?php
if (is_array($FeeStructureDetails) && count($FeeStructureDetails) > 0)
{
    $Counter = 0;

    foreach ($FeeStructureDetails as $FeeGroupID => $FeeStructureDetails)
    {
        foreach ($FeeStructureDetails as $FeeHeadID => $StructureDetails)
        {
            echo '<tr>';
            echo '<label class="checkbox-inline"><input class="custom-radio discount-type" type="hidden" id="' . $FeeHeadID . '" name="chkFeeHeadID[]" value="' . $FeeHeadID . '" />
                </label>';
            echo '<td>' . $AllFeeHeads[$FeeHeadID]['FeeHead'] . '</td>';

?>
            <td class="col-lg-2 <?php echo isset($Errors[$FeeHeadID]['FeeAmount']) ? ' has-error' : ''; ?>">
                <?php echo (($AllFeeHeads[$FeeHeadID]['IsSystemGenerated']) ? '<input type="text" class="form-control" id="FeeAmount' . $FeeHeadID . '" readonly>' : '<input type="text" class="form-control" name="txtFeeAmount[' . $FeeHeadID . ']" id="FeeAmount' . $FeeHeadID . '" value="' . $StructureDetails['FeeHeadAmount'] . '"  readonly>' ); ?>      
            </td>
            <td class="col-lg-2 <?php echo isset($Errors[$FeeHeadID]['DiscountValue']) ? ' has-error' : ''; ?>" id="DiscountValueTD<?php echo $FeeHeadID; ?>">
                <?php echo '<input type="text" class="form-control" name="txtDiscountValue[' . $FeeHeadID . ']" id="DiscountValue' . $FeeHeadID . '" value="' . ((array_key_exists($FeeHeadID, $Clean['DiscountValueList'])) ? $Clean['DiscountValueList'][$FeeHeadID] : '') . '" onfocusout="return CalculateDiscount(' . $FeeHeadID . ');" >'; ?>      
            </td>
            <td>
                <label><input class="custom-radio" type="radio"  name="optDiscountTypeList[<?php echo $FeeHeadID; ?>]" value="Absolute" <?php echo (!empty($Clean['DiscountTypeList'][$FeeHeadID]) && array_key_exists('Absolute', $Clean['DiscountTypeList'][$FeeHeadID]) || !isset($Clean['DiscountTypeList'][$FeeHeadID])? 'checked="checked"' : ''); ?> onChange="return CalculateDiscount(<?php echo $FeeHeadID;?>);" >&nbsp;&nbsp;<i class="fa fa-inr"></i> &nbsp;&nbsp;</label>
                <label>
                    <input class="custom-radio" type="radio" name="optDiscountTypeList[<?php echo $FeeHeadID; ?>]" value="Percentage" <?php echo (!empty($Clean['DiscountTypeList'][$FeeHeadID]) && array_key_exists('Percentage', $Clean['DiscountTypeList'][$FeeHeadID]) ? 'checked="checked"' : ''); ?> onChange="return CalculateDiscount(<?php echo $FeeHeadID;?>);" >&nbsp;&nbsp;%
                </label>
            </td>
           <td class="col-lg-2 <?php echo isset($Errors[$FeeHeadID]['AfterDiscount']) ? ' has-error' : ''; ?>">
                <?php echo '<input type="text" class="form-control" name="txtAfterDiscount[' . $FeeHeadID . ']" id="AfterDiscount' . $FeeHeadID . '" value="' . ((array_key_exists($FeeHeadID, $Clean['AfterDiscountList'])) ? $Clean['AfterDiscountList'][$FeeHeadID] : '') . '" readonly >'; ?>
            </td>
            <td class="<?php echo isset($Errors[$FeeHeadID]['FeeStructureDetailID']) ? ' has-error' : ''; ?>"> 
          
<?php
            foreach ($StructureDetails['FeeHeadApplicableMonths'] as $AcademicYearMonthID => $FeeStructureDetailID) 
            {
                echo (!empty($AllMonths) ? (array_key_exists($AcademicYearMonthID, $AllMonths) ? '<label class="checkbox-inline"><input class="custom-radio" type="checkbox" id="' . $FeeStructureDetailID . '" name="chkFeeStructureDetailID[' . $FeeHeadID . '][]" ' . (!empty($Clean['FeeStructureDetailIDList'][$FeeHeadID]) ? (array_key_exists($FeeStructureDetailID, $Clean['FeeStructureDetailIDList'][$FeeHeadID]) ? 'checked="checked"' : '') : '') . ' value="' . $FeeStructureDetailID . '" />
                ' . $AllMonths[$AcademicYearMonthID]['MonthShortName'] . '</label>' : '' ) : '' );
?>
<?php                                             
            }
?>
            </td>                             
<?php
        echo '</tr>';
        }
    }
}
?>
	</tbody>
</table>
<?php
exit;
?>
<?php
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

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

require_once("../classes/school_administration/class.school_timing_parts_master.php");

$Clean['SchoolTimingPartID'] = 0;

$Clean['MeximumNumberOfPeriod'] = 0;
$Clean['NextPeriodStartTime'] = '';

if (isset($_POST['SchoolTimingPartID']))
{
    $Clean['SchoolTimingPartID'] = (int) $_POST['SchoolTimingPartID'];
}

if (isset($_POST['MeximumNumberOfPeriod']))
{
    $Clean['MeximumNumberOfPeriod'] = (int) $_POST['MeximumNumberOfPeriod'];
}

if (isset($_POST['NextPeriodStartTime']))
{
    $Clean['NextPeriodStartTime'] = strip_tags(trim($_POST['NextPeriodStartTime']));
}

// Check if the selected period is first period
if ($Clean['SchoolTimingPartID'] != SchoolTimingPartsMaster::GetFirstPeriodID())
{
    exit;
}



$TimingPartList = array();
$TimingPartList = SchoolTimingPartsMaster::GetTimingPartDetails();
?>
<div class="form-group">
    <label for="SchoolTimingPartID" class="col-lg-2 control-label">Period Detail</label>
    <div class="col-lg-3<?php echo isset($TimingPartsErrors[$Counter]['TimingPart']) && $TimingPartsErrors[$Counter]['TimingPart'] == 1 ? ' has-error' : ''; ?>">
        <select class="form-control" name="drdSchoolTimingPartID[<?php echo $Counter; ?>]" id="SchoolTimingPartID">
            <option value="0">Select Period</option>
<?php
        foreach ($TimingPartList as $TimingPartID => $TimingPart) 
        {
            echo '<option '.($Clean['SchoolTimingPartID'][$Counter] == $TimingPartID ? 'selected="selected"' : '').' value="'.$TimingPartID.'">'.$TimingPart['TimingPart'].'</option>';
        }
?>
        </select>
    </div>
    <div class="col-lg-3<?php echo isset($TimingPartsErrors[$Counter]['PeriodStartTime']) && $TimingPartsErrors[$Counter]['PeriodStartTime'] == 1 ? ' has-error' : ''; ?>">
        <input class="form-control" type="time"  id="PeriodStartTime" name="txtPeriodStartTime[<?php echo $Counter; ?>]" value="<?php echo (($Clean['PeriodStartTime'][$Counter] == $PeriodDetails['PeriodStartTime']) ? $Clean['PeriodStartTime'][$Counter] : ''); ?>" />
    </div>
    <div class="col-lg-3<?php echo isset($TimingPartsErrors[$Counter]['PeriodEndTime']) && $TimingPartsErrors[$Counter]['PeriodEndTime'] == 1 ? ' has-error' : ''; ?>">
        <input class="form-control" type="time"  id="PeriodEndTime" name="txtPeriodEndTime[<?php echo $Counter; ?>]" value="<?php echo (($Clean['PeriodEndTime'][$Counter] == $PeriodDetails['PeriodEndTime']) ? $Clean['PeriodEndTime'][$Counter] : ''); ?>" />
    </div>
    </div>
<?php
exit;
?>
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

require_once('../classes/school_administration/class.staff_attendence.php');
require_once("../classes/class.date_processing.php");

$BranchStaffID = 0;
$AttendanceDate = '';

if (isset($_POST['SelectedBranchStaffID']))
{
	$BranchStaffID = (int) $_POST['SelectedBranchStaffID'];
}

if (isset($_POST['AttendanceDate']))
{
	$AttendanceDate = (string) $_POST['AttendanceDate'];
}

if ($BranchStaffID <= 0 || $AttendanceDate == '')
{
	echo 'error|*****|Unknown error, please try again.';
	exit;
}

$ClockInOutDetails = array();
$ClockInOutDetails = StaffAttendence::GetClockInOutDetails($BranchStaffID, date('Y-m-d', strtotime(DateProcessing::ToggleDateDayAndMonth($AttendanceDate))));

if (count($ClockInOutDetails) < 0)
{
	echo 'error|*****|No record found.';
	exit;
}

echo 'success|*****|';

?>

<div class="row">
    <div class="col-lg-12">
        <table width="100%" class="table table-striped table-bordered">
        	<thead>
        		<tr>
        			<th>Sr.No.</th>
        			<th>Clock Type</th>
        			<th>Time</th>
				</tr>
            </thead>
            <tbody>
<?php
			$Counter = 0;
			foreach ($ClockInOutDetails as $LogID => $Details) 
			{
?>
				<tr>
					<td><?php echo ++$Counter;?></td>
					<td><?php echo $Details['ClockInOutType'];?></td>
					<td><?php echo date('h:i A', strtotime($Details['ClockInOutTime']));?></td>
				</tr>
<?php
			}
?>				
            </tbody>
        </table>
    </div> 
</div>

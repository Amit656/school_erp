<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once("../../classes/school_administration/class.branch_staff.php");
require_once("../../classes/school_administration/class.staff_attendence.php");

require_once("../../classes/class.date_processing.php");

require_once("../../includes/global_defaults.inc.php");
require_once("../../includes/helpers.inc.php");

//1. RECHECK IF THE USER IS VALID //
try
{
	$AuthObject = new ApplicationAuthentication;
	$LoggedUser = new User(0, $AuthObject->CheckValidUser());
}

// THIS CATCH BLOCK BUBBLES THE EXCEPTION TO THE BUILT IN 'Exception' CLASS IF THERE ARE ANY UNCAUGHT ERRORS //
catch (ApplicationAuthException $e)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}
catch (Exception $e)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}
// END OF 1. //

if ($LoggedUser->HasPermissionForTask(TASK_VIEW_FACULTY_ATTENDANCE) !== true)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}

$StaffCategory = array("Teaching" => "Teaching Staff", "NonTeaching" => "Non Teaching Staff");

$BranchStaffList = array();

$StaffAttendenceID = 0;

$HasErrors = false;

$Clean = array();

$Clean['Process'] = 0;

$Clean['AttendanceDate'] = date('d/m/Y');

$Clean['StaffCategory'] = 'Teaching';

$Clean['PresentStaffList'] = array();

if (isset($_POST['hdnProcess']))
{
	$Clean['Process'] = (int) $_POST['hdnProcess'];
}
elseif (isset($_GET['Process']))
{
	$Clean['Process'] = (int) $_GET['Process'];
}
switch ($Clean['Process'])
{
	case 7:
		if (isset($_POST['txtAttendanceDate']))
		{
			$Clean['AttendanceDate'] =  strip_tags(trim($_POST['txtAttendanceDate']));
		}

		if (isset($_POST['drdStaffCategory']))
		{
			$Clean['StaffCategory'] = strip_tags(trim($_POST['drdStaffCategory']));
		}
		
		$NewRecordValidator = new Validator();

		$NewRecordValidator->ValidateDate($Clean['AttendanceDate'], 'Please enter a valid attendance date.');
		$NewRecordValidator->ValidateInSelect($Clean['StaffCategory'], $StaffCategory, 'Unknown error, please try again.');

		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}

		$BranchStaffList = BranchStaff::GetActiveBranchStaff($Clean['StaffCategory']);

		if(!StaffAttendence::IsAttendenceTaken($Clean['StaffCategory'], date('Y-m-d', strtotime(DateProcessing::ToggleDateDayAndMonth(($Clean['AttendanceDate'])))), $StaffAttendenceID))
		{
			$NewRecordValidator->AttachTextError('Attendance is not taken of this branch staff on date');
			$HasErrors = true;
		}
		else
		{
			$CurrentBranchStaffAttendence = new StaffAttendence($StaffAttendenceID);
			$CurrentBranchStaffAttendence->ViewStaffAttendenceStatus();

			$Clean['PresentStaffList'] = $CurrentBranchStaffAttendence->GetAttendenceStatusPresentStaffList();
		}
		
	break;

	default:
		if(isset($_GET['StaffCategory']))
		{
			$Clean['StaffCategory'] = $_GET['StaffCategory'];

			if(!array_key_exists($Clean['StaffCategory'], $StaffCategory))
			{
				$Clean['StaffCategory'] = key($StaffCategory);
			}
		}
	break;
}

require_once('../html_header.php');
?>
<title>View Staff Attendance</title>
<link href="../vendor/jquery-ui/jquery-ui.min.css" rel="stylesheet">
</head>

<body>

    <div id="wrapper">
    	<!-- Navigation -->
        <nav class="navbar navbar-default navbar-static-top" role="navigation" style="margin-bottom: 0">
<?php 
			require_once('../site_header.php');
			require_once('../left_navigation_menu.php');
?>                    
            <!-- /.navbar-static-side -->
        </nav>

        <div id="page-wrapper">
            <div class="row">
                <div class="col-lg-12">
                    <h1 class="page-header">View Staff Attendance</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="SearchStaffAttendance" action="staff_attendenceview.php" method="post">
            	<div class="panel panel-default">
                    <div class="panel-heading">
                       Staff Attendance Details
                    </div>
                    <div class="panel-body">
<?php
						if ($HasErrors == true)
						{
							echo $NewRecordValidator->DisplayErrors();
						}
?>                    
                        <div class="form-group">
                            <label for="AttendanceDate" class="col-lg-2 control-label">Attendance Date</label>
                            <div class="col-lg-3">
                            	<input type="text" class="form-control dtepicker" maxlength="10" id="AttendanceDate" name="txtAttendanceDate" value="<?php echo $Clean['AttendanceDate']; ?>"/>
                            </div>
                        </div>
                    	<div class="form-group">
                            <label for="StaffCategory" class="col-lg-2 control-label">Select Staff</label>
                            <div class="col-lg-3">
                            	<select class="form-control" id="StaffCategory" name="drdStaffCategory">
<?php
									foreach ($StaffCategory as $Key => $StaffCategoryName)
									{
?>
										<option <?php echo ($Key == $Clean['StaffCategory'] ? 'selected="selected"' : ''); ?> value="<?php echo $Key; ?>"><?php echo $StaffCategoryName; ?></option>
<?php
									}
?>
                            	</select>
                            </div>
                        </div> 
                        <div class="form-group">
						    <div class="col-sm-offset-2 col-lg-10">
						    	<input type="hidden" name="hdnProcess" value="7"/>
						        <button type="submit" class="btn btn-primary">Search</button>
						    </div>
						</div>
                    </div>
                </div>
            </form>
<?php
			if ($Clean['Process'] == 7 && count($Clean['PresentStaffList']) > 0)
			{	
?>
			 <form class="form-horizontal" name="AddStaffAttendance" action="staff_attendenceview.php" method="post">
            	<div class="panel panel-default">
                    <div class="panel-heading">
                      Branch Staff List
                    </div>
                    <div class="panel-body">
                    	<div class="row">
                        </div>
                            <div class="row" id="RecordTableHeading">
                            </div>
                            <div class="row" id="RecordTable">
                                <div class="col-lg-12">
                                    <table width="100%" class="table table-striped table-bordered table-hover">
                                        <thead>
                                            <tr>
                                                <th>S. No.</th>
                                                <th>Branch Staff Name</th>
                                                <th>Email</th>
                                                <th>Attendance Status</th>
                                                <th>Clock IN/Out Report</th>
                                            </tr>
                                        </thead>
                                        <tbody>
<?php
			                                if (is_array($BranchStaffList) && count($BranchStaffList) > 0)
			                                {
			                                    $Counter = 0;

			                                    foreach ($BranchStaffList as $BranchStaffID => $BranchStaffDetails)
			                                    {
?>
		                                            <tr>
		                                            	<td><?php echo ++$Counter; ?></td>
		                                                <td>
		                                                	<?php echo $BranchStaffDetails['FirstName'] . ' ' . $BranchStaffDetails['LastName'] . ' (' . $BranchStaffDetails['MobileNumber1'] . ') ';?>
		                                                </td>
		                                                <td>
		                                                	<?php echo $BranchStaffDetails['Email'];?>
		                                                </td>
		                                                <td>
<?php
														if (array_key_exists($BranchStaffID, $Clean['PresentStaffList']))
														{
															echo '<a class="btn btn-success">P</a>';
														}
														else
														{
															echo '<a class="btn btn-danger">A</a>';
															
															if ($Clean['AttendanceDate'] == date('d/m/Y'))
															{
																echo '&nbsp;&nbsp;<a href="mark_teacher_substitution.php?Process=2&amp;BranchStaffID=' . $BranchStaffID . '" target="_blank"  class="btn btn-primary">Mark Substitution <i class="fa fa-angle-double-right" aria-hidden="true"></i></a>';
															}
														}
?>
		                                                </td>
		                                                <td>
		                                                	<?php
		                                                		if (array_key_exists($BranchStaffID, $Clean['PresentStaffList'])) 
		                                                		{
		                                                			?>
		                                                			<button type="button" class="btn btn-info btn-sm ClockInOutDetails" data-toggle="modal" data-target="#ClockInOutDetails" value="<?php echo $BranchStaffID; ?>">Details &nbsp;<i class="fa fa-angle-double-right"></i></button>
		                                                			<?php
		                                                		}
		                                                		else
		                                                		{
		                                                			echo 'Absent';
		                                                		}
		                                                	?>
		                                                </td>
		                                            </tr>
<?php
			                                    }
			                                }
?>											
                                        </tbody>
                                    </table>
                                </div>
                            </div>
<?php
		    }
?>
				</div>
                   		
			</form>
        </div>
        <!-- /#page-wrapper -->
    </div>
    <!-- /#wrapper -->
<div id="ClockInOutDetails" class="modal fade" role="dialog">
  <div class="modal-dialog">

    <!-- Modal content-->
    <div class="modal-content">
      <div class="modal-header btn-info">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title">Clock In/Out Details</h4>
      </div>
      <div class="modal-body">
        <div class="row">
            <div class="col-lg-12" id="Details"></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>     
<?php
require_once('../footer.php');
if (PrintMessage($_GET, $Message))
{
?>
    <script type="text/javascript">
        alert('<?php echo $Message; ?>');
    </script>
<?php
}
?>	
<script src="/admin/vendor/jquery-ui/jquery-ui.min.js"></script>
<script type="text/javascript">
$(document).ready(function() {
    $(".dtepicker").datepicker({
        changeMonth: true,
        changeYear: true,
        dateFormat: 'dd/mm/yy'
	});

    $('.ClockInOutDetails').click(function(){

        var BranchStaffID = 0;
        BranchStaffID = parseInt($(this).val());

        var AttendanceDate = '<?php echo $Clean['AttendanceDate'];?>';

        if (BranchStaffID <= 0 || AttendanceDate == '')
        {
            alert('Error! No record found.');
            return;
        }
        
        $.post("/xhttp_calls/get_clock_in_out_details.php", {SelectedBranchStaffID:BranchStaffID, AttendanceDate:AttendanceDate}, function(data)
        {
            ResultArray = data.split("|*****|");
            
            if (ResultArray[0] == 'error')
            {
                alert (ResultArray[1]);
                return false;
            }
            else
            {
                $('#Details').html(ResultArray[1]);
            }
        });
    });
});
</script>
</body>
</html>
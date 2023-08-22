<?php
ob_start();
require_once('../../classes/class.users.php');
require_once('../../classes/class.validation.php');
require_once('../../classes/class.authentication.php');

require_once('../../classes/school_administration/class.branch_staff.php');
require_once('../../classes/school_administration/class.staff_attendence.php');
require_once('../../classes/school_administration/class.classes.php');

require_once('../../classes/class.helpers.php');

// fcm classes
require_once('../../classes/class.fcm_push.php');
require_once('../../classes/class.fcm_firebase.php');
require_once('../../classes/class.fcm_send_notification.php');

require_once('../../classes/class.date_processing.php');

require_once('../../includes/global_defaults.inc.php');
require_once('../../includes/helpers.inc.php');

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

$HasErrors = false;

$Clean = array();

$Clean['Process'] = 0;
$Clean['BranchStaffID'] = 0;

if (isset($_GET['BranchStaffID']))
{
    $Clean['BranchStaffID'] = (int) $_GET['BranchStaffID'];
}
elseif (isset($_POST['hdnBranchStaffID']))
{
    $Clean['BranchStaffID'] = (int) $_POST['hdnBranchStaffID'];
}

if ($Clean['BranchStaffID'] <= 0)
{
    header('location:/admin/error.php');
    exit;
}   

try
{
    $BranchStaffObject = new BranchStaff($Clean['BranchStaffID']);
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

$Clean['StaffCategory'] = 'Teaching';
$Clean['PeriodSubstitutionDetails'] = array();

$BranchStaffList = array();
$BranchStaffList = BranchStaff::GetActiveBranchStaff($Clean['StaffCategory']);

$Clean['DayID'] = Date('N'); // gives 1 for monday through 7 for sunday

$AllPeriodsDetails = array();
$AllPeriodsDetails = $BranchStaffObject->GetCurrentDayTimeTable($Clean['DayID']);

$CurrentDaySubstitutions = array();
$CurrentDaySubstitutions = Helpers::GetCurrentDaySubstitutions();

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
	case 3:
		if (isset($_POST['drdPeriodSubstitutionDetails']))
		{
			$Clean['PeriodSubstitutionDetails'] =  $_POST['drdPeriodSubstitutionDetails'];
		}
		
		$NewRecordValidator = new Validator();

		foreach ($Clean['PeriodSubstitutionDetails'] as $ClassTimeTableDetailID => $TeacherClassID)
		{
			if (!array_key_exists($ClassTimeTableDetailID, $AllPeriodsDetails))
			{
				header('location:/admin/error.php');
				exit;
			}
			
			$TimeTableDetails = array();
			$TimeTableDetails = $AllPeriodsDetails[$ClassTimeTableDetailID];
			
			$AvailableTeacherList = array();
			$AvailableTeacherList = AddedClass::GetAvailableTeachersForSubstitution($Clean['DayID'], $TimeTableDetails['ClassID'], $TimeTableDetails['ClassSubjectID'], $TimeTableDetails['PeriodStartTime'], $TimeTableDetails['PeriodEndTime']);
			
			if ($TeacherClassID > 0)
			{
				if (!array_key_exists($ClassTimeTableDetailID, $AllPeriodsDetails))
				{
					header('location:/admin/error.php');
					exit;
				}
			}
		}
		
		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}
		
		if (!Helpers::MarkTeacherSubstitution($Clean['PeriodSubstitutionDetails'], $LoggedUser->GetUserID()))
		{
			$RecordValidator->AttachTextError('There was an error in saving the data.');
			$HasErrors = true;
			break;
		}
		
		FcmSendNotification::SendSubstitutionNotification($Clean['PeriodSubstitutionDetails']);
		
// 		if (!FcmSendNotification::SendSubstitutionNotification($Clean['PeriodSubstitutionDetails']))
// 		{
// 			$RecordValidator->AttachTextError('There was an error in sending the notification.');
// 			$HasErrors = true;
// 			break;
// 		}

		header('location:mark_teacher_substitution.php?BranchStaffID='. $Clean['BranchStaffID'] .'&Mode=ED');
		exit;
	break;
} 

require_once('../html_header.php');
?>
<title>Mark Substitution</title>
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
                    <h1 class="page-header">Teacher Time Table</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="MarkTeacherSubstitution" action="mark_teacher_substitution.php" method="post">
            	
            	<div class="panel panel-default">
                    <div class="panel-heading">
                      Teacher's Time Table
                    </div>
                    <div class="panel-body">
                    	<div class="form-group">
                            <label for="BranchStaff" class="col-lg-2 control-label">Teacher</label>
                            <div class="col-lg-5">
                            	<select class="form-control" id="BranchStaff" name="drdBranchStaff" disabled="disabled">
<?php
									foreach ($BranchStaffList as $BranchStaffID => $BranchStaffDetails)
									{
?>
										<option <?php echo ($BranchStaffID == $Clean['BranchStaffID'] ? 'selected="selected"' : ''); ?> value="<?php echo $BranchStaffID; ?>"><?php echo $BranchStaffDetails['FirstName'] . ' ' . $BranchStaffDetails['LastName'] . ' (' . $BranchStaffDetails['MobileNumber1'] . ') '; ?></option>
<?php
									}
?>
                            	</select>
                            </div>
                        </div> 
                        <div class="row" id="RecordTable">
                            <div class="col-lg-12">
                                <table width="100%" class="table table-striped table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th>S. No.</th>
                                            <th>Period</th>
                                            <th>Class</th>
                                            <th>Subject</th>
                                            <th>Mark Substitution</th>
                                        </tr>
                                    </thead>
                                    <tbody>
<?php
		                                if (is_array($AllPeriodsDetails) && count($AllPeriodsDetails) > 0)
		                                {
		                                    $Counter = 0;

		                                    foreach ($AllPeriodsDetails as $ClassTimeTableDetailID => $TimeTableDetails)
		                                    {
												$AvailableTeacherList = array();
												$AvailableTeacherList = AddedClass::GetAvailableTeachersForSubstitution($Clean['DayID'], $TimeTableDetails['ClassID'], $TimeTableDetails['ClassSubjectID'], $TimeTableDetails['PeriodStartTime'], $TimeTableDetails['PeriodEndTime']);
												
												$PeriodSubstitutionDetails = array();
												
												if (isset($CurrentDaySubstitutions[$ClassTimeTableDetailID]))
												{
													$PeriodSubstitutionDetails = $CurrentDaySubstitutions[$ClassTimeTableDetailID];
												}
?>
	                                            <tr>
	                                            	<td><?php echo ++$Counter; ?></td>
	                                                <td><?php echo $TimeTableDetails['TimingPart'] .'<br>( '. date('h:i A', strtotime($TimeTableDetails['PeriodStartTime'])) . ' to ' . date('h:i A', strtotime($TimeTableDetails['PeriodEndTime'])) .' )';?></td>
	                                                <td><?php echo $TimeTableDetails['ClassName'] .' '. $TimeTableDetails['SectionName'];?></td>
	                                                <td><?php echo $TimeTableDetails['Subject']; ?></td>
													<td>
														<select name="drdPeriodSubstitutionDetails[<?php echo $ClassTimeTableDetailID; ?>]" class="form-control">
															<option value="0">-- Select --</option>
<?php
														foreach ($AvailableTeacherList as $TeacherID => $TeacherDetails)
														{
															if (count($PeriodSubstitutionDetails) > 0 && $PeriodSubstitutionDetails['TeacherClassID'] == $TeacherID)
															{
																echo '<option selected="selected" value="'. $TeacherID .'">'. $TeacherDetails['FirstName'] .' '. $TeacherDetails['LastName'] .'</option>';
																continue;
															}
															else if ($TeacherDetails['IsBusy'] == 'Class')
															{
																echo '<option disabled="disabled" value="'. $TeacherID .'">'. $TeacherDetails['FirstName'] .' '. $TeacherDetails['LastName'] .' (Already Assigned Class Section )</option>';
																continue;
															}
															else if ($TeacherDetails['IsBusy'] == 'Substitution')
															{
																echo '<option disabled="disabled" value="'. $TeacherID .'">'. $TeacherDetails['FirstName'] .' '. $TeacherDetails['LastName'] .' (Already Substituted to Class Section )</option>';
																continue;
															}
															
															echo '<option value="'. $TeacherID .'">'. $TeacherDetails['FirstName'] .' '. $TeacherDetails['LastName'] .'</option>';
														}
?>
														</select>
													</td>                                     
	                                            </tr>
<?php
		                                    }
		                                }
?>											
                                    </tbody>
									
									<thead>
										<tr>
											<td colspan="5" align="right">
												<input type="hidden" name="hdnProcess" value="3"/>
												<input type="hidden" name="hdnBranchStaffID" value="<?php echo $Clean['BranchStaffID']; ?>" />
												<input type="submit" class="btn btn-primary" value="Allot Substitution" />
											</td>
										</tr>
									</thead>
                                </table>
                            </div>
                        </div>
					</div>
				</div>
			</form>
        </div>
        <!-- /#page-wrapper -->
    </div>
    <!-- /#wrapper -->
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
</body>
</html>
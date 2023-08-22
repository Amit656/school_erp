<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once("../../classes/school_administration/class.branch_staff.php");
require_once("../../classes/school_administration/class.staff_attendence.php");

require_once("../../classes/class.date_processing.php");
require_once('../../classes/class.helpers.php');

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
	header('location:../unauthorized_login_admin.php');
	exit;
}
catch (Exception $e)
{
	header('location:../unauthorized_login_admin.php');
	exit;
}
// END OF 1. //

$UserMenusArray = array();
$UserMenusArray = $LoggedUser->GetUserMenus();

if (!is_array($UserMenusArray) || count($UserMenusArray) <= 0)
{
	//header('location:../logout.php');
	//exit;
}

$StaffCategory = array("Teaching" => "Teaching Staff", "NonTeaching" => "Non Teaching Staff");

$BranchStaffList = array();

$StaffAttendenceID = 0;

$HasErrors = false;

$Clean = array();
$Clean['Process'] = 0;

$Clean['AttendanceDate'] = '';
$Clean['AttendanceDate'] = date('d/m/Y');

$Clean['StaffCategory'] = '';
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
	case 1:
		if (isset($_POST['hdnAttendanceDate']))
		{
			$Clean['AttendanceDate'] = strip_tags(trim($_POST['hdnAttendanceDate']));
		}

		if (isset($_POST['hdnStaffCategory']))
		{
			$Clean['StaffCategory'] = strip_tags(trim($_POST['hdnStaffCategory']));
		}

		if (isset($_POST['chkPresentStaffList']) && is_array($_POST['chkPresentStaffList']))
		{
			$Clean['PresentStaffList'] = $_POST['chkPresentStaffList'];
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

		if(count($Clean['PresentStaffList']) > 0)
		{
			foreach ($Clean['PresentStaffList'] as $BranchStaffID => $Value)
			{
				$NewRecordValidator->ValidateInSelect($BranchStaffID, $BranchStaffList, 'Unknown error, please try again.');
			}
		}

		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}

		$AbsentStaffList = array();

		$AbsentStaffList = array_diff_key($BranchStaffList, $Clean['PresentStaffList']);

		if (StaffAttendence::IsAttendenceTaken($Clean['StaffCategory'], date('Y-m-d', strtotime(DateProcessing::ToggleDateDayAndMonth(($Clean['AttendanceDate'])))), $StaffAttendenceID))
		{
			$CurrentBranchStaffAttendence = new StaffAttendence($StaffAttendenceID);
		}
		else
		{
			$CurrentBranchStaffAttendence = new StaffAttendence();

			$CurrentBranchStaffAttendence->SetCreateUserID($LoggedUser->GetUserID());			
		}

		$CurrentBranchStaffAttendence->SetAttendenceDate(date('Y-m-d', strtotime(DateProcessing::ToggleDateDayAndMonth(($Clean['AttendanceDate'])))));
		$CurrentBranchStaffAttendence->SetStaffCategory($Clean['StaffCategory']);
		$CurrentBranchStaffAttendence->SetAttendenceStatusAbsentStaffList($AbsentStaffList);

		if (!$CurrentBranchStaffAttendence->Save())
		{
			$NewRecordValidator->AttachTextError(ProcessErrors($CurrentBranchStaffAttendence->GetLastErrorCode()));
			$HasErrors = true;
			break;
		}

		if($Clean['StaffCategory'] == 'Teaching')
		{
			header('location:add_staff_attendence.php?Mode=ED&AttendanceDate=' . $Clean['AttendanceDate'] . '&StaffCategory=NonTeaching');	
			exit;
		}
		else
		{
			header('location:add_staff_attendence.php?Mode=ED&AttendanceDate=' . $Clean['AttendanceDate'] . '&StaffCategory=Teaching');
			exit;
		}
		break;

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

		$AttendenceDate = date('Y-m-d', strtotime(DateProcessing::ToggleDateDayAndMonth(($Clean['AttendanceDate']))));
        $StartDate = '';

        $StartDate = Helpers::GetStartDateOfTheMonth(date('M', strtotime($AttendenceDate)));

        $WorkingDayStartDate = $StartDate;
        $WorkingDayEndDate = date('Y-m-t', strtotime($AttendenceDate));

		if (!Helpers::GetIsBranchStaffAttendanceDateIsWorkingDay($WorkingDayStartDate, $WorkingDayEndDate, $Clean['StaffCategory'], $AttendenceDate))
		{
		   
			$NewRecordValidator->AttachTextError('this attendance date is not a working day, accordingly to global setting or academic event.');
			$HasErrors = true;
			break;
		}
        
		$BranchStaffList = BranchStaff::GetActiveBranchStaff($Clean['StaffCategory']);

		if(!StaffAttendence::IsAttendenceTaken($Clean['StaffCategory'], $AttendenceDate, $StaffAttendenceID))
		{
			$Clean['PresentStaffList'] = $BranchStaffList;
		}
		else
		{
			$NewRecordValidator->AttachTextError('Attendence is already taken of this branch staff on date');

			$CurrentBranchStaffAttendence = new StaffAttendence($StaffAttendenceID);
			$CurrentBranchStaffAttendence->FillAttendenceStatus();

			$Clean['PresentStaffList'] = $CurrentBranchStaffAttendence->GetAttendenceStatusPresentStaffList();

			$HasErrors = true;
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
<title>Mark Staff Attendence</title>
<link href="../vendor/jquery-ui/jquery-ui.min.css" rel="stylesheet">
<style type="text/css">
.col-md-4:not(:empty)
{
    border: 1px solid #ccc;
}
</style>
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
                    <h1 class="page-header">Mark Staff Attendence</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="SearchStaffAttendence" action="add_staff_attendence.php" method="post">
            	<div class="panel panel-default">
                    <div class="panel-heading">
                       Class Attendence Details
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
						        <button type="submit" class="btn btn-primary">View Staff</button>
						    </div>
						</div>
                    </div>
                </div>
            </form>
<?php
		if($Clean['Process'] == 7)
		{	
?>
			 <form class="form-horizontal" name="AddStaffAttendence" action="add_staff_attendence.php" method="post">
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
                                                <th>
                                                	<label><input type="checkbox" id="SelectAll" name="chkSelectAll" <?php echo(count($BranchStaffList) == count($Clean['PresentStaffList'])) ? 'checked="checked"' : ''  ?> value="1"/>Select All</label>
                                                </th>
                                                <th>User Name</th>
                                                <th>Email</th>
                                                <th>
                                                	<label><input type="checkbox" id="ClearAll" name="chkClearAll" value="1" />Clear All</label>
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
<?php
			                                if (is_array($BranchStaffList) && count($BranchStaffList) > 0)
			                                {
			                                    $Counter = 0;

			                                    foreach ($BranchStaffList as $BranchStaffID => $BranchStaffDetails)
			                                    {
			                                        $TeacherDetailsString = $BranchStaffDetails['FirstName'] . ' ' . $BranchStaffDetails['LastName'];
			                                        
			                                        if ($BranchStaffDetails['MobileNumber1'])
			                                        {
			                                            $TeacherDetailsString .= ' (' . $BranchStaffDetails['MobileNumber1'] . ')';
			                                        }
?>
		                                            <tr>
		                                                <td>
		                                                	<?php echo $TeacherDetailsString; ?>
		                                                </td>
		                                                 <td>
		                                                	<?php echo $BranchStaffDetails['UserName'];?>
		                                                </td>
		                                                <td>
		                                                	<?php echo $BranchStaffDetails['Email'];?>
		                                                </td>
		                                                <td>
<?php
															if (array_key_exists($BranchStaffID, $Clean['PresentStaffList']))
						                                    {
						                                        echo '<label style="font-size:14px; font-weight:400;">
						                                        <input style="vertical-align:top;" type="checkbox" class="StaffList" checked="checked" id="PresentStaffList'.$BranchStaffID.'" name="chkPresentStaffList[' . $BranchStaffID . ']" value="' . $BranchStaffID . '" /></label>';
						                                    }
						                                    else
						                                    {
						                                        echo '<label style="font-size:14px; font-weight:400;"><input style="vertical-align:top;" type="checkbox" class="StaffList" id="PresentStaffList' . $BranchStaffID . '" name="chkPresentStaffList[' . $BranchStaffID . ']" value="' . $BranchStaffID . '" /></label>';
						                                    }
?>                                                  	</td>                                                  
		                                            </tr>
<?php
			                                    }
			                                }
?>											
                                        </tbody>
                                    </table>
                                    <div class="row">
										<div class="col-lg-12">							
											<div class="form-group">
											    <div class="col-sm-offset-2 col-lg-10">
											    	<input type="hidden" name="hdnAttendanceDate" value="<?php echo $Clean['AttendanceDate']; ?>" />
											    	<input type="hidden" name="hdnStaffCategory" value="<?php echo $Clean['StaffCategory']; ?>" />
											    	<input type="hidden" name="hdnProcess" value="1"/>
											        <button type="submit" class="btn btn-primary">Save</button>
											    </div>
											</div>
										</div>
									</div>
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
	$(function()
	{
		$(".dtepicker").datepicker({
            changeMonth: true,
            changeYear: true,
            dateFormat: 'dd/mm/yy'
    	});

		$('#SelectAll').click(function()
		{
			if($('#SelectAll').prop("checked"))
			{
				$('#ClearAll').prop("checked", "");
				$('.StaffList').prop("checked", "checked");
			}
			else
			{
				$('.StaffList').prop("checked", false);
			}
		});
	
		$('#ClearAll').click(function()
		{
			if($('#ClearAll').prop("checked"))
			{
				$('#SelectAll').prop("checked", "");
				$('.StaffList').prop("checked", "");
			}
		});
		
		$('.StaffList').click(function()
		{
			$('#SelectAll').prop("checked", "");
			$('#ClearAll').prop("checked", "");		
		});
	});
</script>
</body>
</html>
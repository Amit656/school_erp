<?php
ob_start();

require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once("../../classes/school_administration/class.class_helpers.php");

require_once("../../classes/school_administration/class.branch_staff.php");
require_once("../../classes/school_administration/class.class_time_table.php");

require_once("../../includes/global_defaults.inc.php");

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

if ($LoggedUser->HasPermissionForTask(TASK_VIEW_TEACHER_TIMETABLE) !== true)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}

$HasSearchErrors = false;
$HasErrors = false;

$StaffCategory = array();
$StaffCategory = array('Teaching' => 'Teaching Staff', 'NonTeaching' => 'Non Teaching Staff');

$AllBranchStaffList = array();

$Clean = array();

$Clean['BranchStaffID'] = 0;

$Clean['StaffCategory'] = 'Teaching';

$Clean['Process'] = 0;

$AllBranchStaffList = BranchStaff::GetActiveBranchStaff($Clean['StaffCategory']);

if (isset($_POST['hdnProcess']))
{
	$Clean['Process'] = (int) $_POST['hdnProcess'];
}
switch ($Clean['Process'])
{	
	case 7:
		if (isset($_POST['drdStaffCategory']))
		{
			$Clean['StaffCategory'] = strip_tags(trim($_POST['drdStaffCategory']));
		}
			
		if (isset($_POST['drdBranchStaff']))
		{
			$Clean['BranchStaffID'] = (int) $_POST['drdBranchStaff'];
		}

		$SearchRecordValidator = new Validator();

		$SearchRecordValidator->ValidateInSelect($Clean['StaffCategory'], $StaffCategory, 'Unknown error, please try again.');
		
		if ($SearchRecordValidator->HasNotifications())
        {
            $HasSearchErrors = true;
            break;
        }

        $AllBranchStaffList = BranchStaff::GetActiveBranchStaff($Clean['StaffCategory']);
		$SearchRecordValidator->ValidateInSelect($Clean['BranchStaffID'], $AllBranchStaffList, 'Unknown error, please try again.');

		if ($SearchRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}

		try
		{
			$CurrentClassTeacher = new BranchStaff($Clean['BranchStaffID']);
			
			$Clean['ClassTeacherName'] = $CurrentClassTeacher->GetFirstName() . ' ' . $CurrentClassTeacher->GetLastName();
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

        $ClassTimeTable = array();
		$ClassTimeTable = ClassTimeTable::GetTeacherTimeTable($Clean['BranchStaffID']);

		$DayList = array();
		$Counter = 0;
		for ($i = 0; $i < 7; $i++) {
		$DayList[++$Counter] = jddayofweek($i, 1);
		}

		if ($SearchRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}

  }

require_once('../html_header.php');
?>
<title>Class Timetable</title>
<style type="text/css">
.TimeTableParentTable tr td {text-align:center;}
.TimeTableParentTable tr th {text-align:center;}
</style>
<link href="../vendor/jquery-ui/jquery-ui.min.css" rel="stylesheet">
</head>

<body>

    <div id="">
    	<!-- Navigation -->
        <nav class="navbar navbar-default navbar-static-top" role="navigation" style="margin-bottom: 0">
<?php 
			require_once('../site_header.php');
			//require_once('../left_navigation_menu.php');
?>                    
            <!-- /.navbar-static-side -->
        </nav>

        <div id="page-wrapper" style="margin-left:0px;">
            <div class="row">
                <div class="col-lg-12">
                    <h1 class="page-header">Class Teacherwise Timetable</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>

			<div class="panel panel-default">
				<div class="panel-heading">
					 Class Teacherwise Timetable
				</div>
				<div class="panel-body">
					<form class="form-horizontal" action="teacher_timetable.php" method="post">
<?php
					if ($HasSearchErrors == true)
					{
						echo $SearchRecordValidator->DisplayErrors();

					}
?>
						<div class="form-group">
							 <label for="StaffCategory" class="col-lg-2 control-label">Select Category</label>
	                        <div class="col-lg-3">
	                            <select class="form-control" name="drdStaffCategory" id="StaffCategory">
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
							
							 <label for="BranchStaffID" class="col-lg-2 control-label">Select BranchStaff</label>
                            <div class="col-lg-3">
                            	<select class="form-control" name="drdBranchStaff" id="BranchStaffID">
<?php
								foreach ($AllBranchStaffList as $BranchStaffID => $BranchStaffDetails)
								{
?>
									<option <?php echo ($BranchStaffID == $Clean['BranchStaffID'] ? 'selected="selected"' : ''); ?> value="<?php echo $BranchStaffID; ?>"><?php echo $BranchStaffDetails['FirstName'] .' ' . $BranchStaffDetails['LastName']; ?></option>
<?php
								}
?>
                            	</select>
                            </div>

							<div class="col-lg-2">
								<input type="hidden" name="hdnProcess" value="7"/>
								<button type="submit" class="btn btn-primary">Search</button>
							</div>
						</div>
					</form>
				</div>
			</div>
<?php
			if ($Clean['Process'] == 7 && $HasSearchErrors == false)
			{
	?>
				<div class="panel panel-default">
					<div class="panel-heading">
						Class Teacherwise Timetable
					</div>
					<div class="panel-body">
						  <div class="row" id="RecordTableHeading">
	                             <div class="col-lg-5">
	                                <div class="report-heading-container"><strong>Class Teacher: <?php echo $Clean['ClassTeacherName']; ?></strong></div>
	                            </div>
                                <div class="col-lg-7">
                                    <div class="print-btn-container"><button id="PrintButton" type="submit" class="btn btn-primary">Print</button></div>
                                </div>
                            </div>
						<form action="teacher_timetable.php" method="post">
	<?php
						if ($HasErrors == true)
						{
							echo $SearchRecordValidator->DisplayErrors();
						}
	?>
						<div class="col-lg-12">&nbsp;</div>
						<div class="row" id="RecordTable">
							<div class="col-lg-12">
								<table class="table table-striped table-bordered table-hover TimeTableParentTable" cellpadding="20px;" cellspacing="2px;" border="1px;" width="100%" id="DataTableRecords">
									<tr>
										<th>Period</th>
			<?php
									foreach ($DayList as $DayName) 
									{
										echo '<th>'.$DayName.'</th>';
									}
			?>
									</tr>

			<?php
									foreach ($ClassTimeTable as $PeriodName => $Details) 
									{
		     ?>
										<tr>
											<td style ="background-color:#F5FFFA;"><?php echo $PeriodName;?></td>
		<?php
											foreach ($DayList as $DayID => $DayName) 
											{
												echo '<td style ="background-color:#F5FFFA;">';
												foreach ($Details as $TimetableDay => $TimetableDetails) 
												{
													if ($TimetableDay == $DayID) 
													{
														echo date('h:i A', strtotime($TimetableDetails['PeriodStartTime'])) .' To '. date('h:i A', strtotime($TimetableDetails['PeriodEndTime']));
														
														if (count($TimetableDetails['ClassDetails']) > 1)
														{															
															foreach ($TimetableDetails['ClassDetails'] as $Key => $ClassDetails)
															{
																echo '<span style="color: red;"><br>Class : '. $ClassDetails['ClassName'] .' '. $ClassDetails['SectionName'] . '</span>';
															}
														}
														else
														{
															foreach ($TimetableDetails['ClassDetails'] as $Key => $ClassDetails)
															{
																echo '<br>Class : '. $ClassDetails['ClassName'] .' '. $ClassDetails['SectionName'];
															}
														}

														echo '<br>Subject :'. $TimetableDetails['Subject'];
													}
												}												
												echo '</td>';
											}
		?>
										</tr>
		<?php
									}
		 ?>
								</table>
							</div>
							</div>
						</form>
					</div>
				</div>
	<?php
					}
	?>

	        </div>
	        <!-- /#page-wrapper -->

	        </div>

	    </div>

<?php
require_once('../footer.php');
?>
<!-- JavaScript To Print A Report -->
<script src="/admin/js/print-report.js"></script>
<script type="text/javascript">
$(function()
	{	
		$('#StaffCategory').change(function()
		{
			StaffCategory = $(this).val();
			
			if (StaffCategory <= 0)
			{
				$('#BranchStaffID').html('<option value="0">Select Section</option>');
				return false;
			}
			
			$.post("/xhttp_calls/get_branch_staff_by_staff_category.php", {SelectedStaffCategory:StaffCategory}, function(data)
			{
				ResultArray = data.split("|*****|");
				
				if (ResultArray[0] == 'error')
				{
					alert(ResultArray[1]);
				}
				else
				{
					$('#BranchStaffID').html(ResultArray[1]);
				}
			});
		});
	});
</script>
</body>
</html>
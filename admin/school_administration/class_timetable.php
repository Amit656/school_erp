<?php
ob_start();

require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once("../../classes/school_administration/class.classes.php");
require_once("../../classes/school_administration/class.section_master.php");
require_once("../../classes/school_administration/class.branch_staff.php");
require_once("../../classes/school_administration/class.class_sections.php");
require_once("../../classes/school_administration/class.class_helpers.php");
require_once("../../classes/school_administration/class.school_session_class_daywise_timings.php");

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

if ($LoggedUser->HasPermissionForTask(TASK_ADD_CLASS_TIMETABLE) !== true)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}

$HasSearchErrors = false;
$HasErrors = false;

$ClassList =  array();
$ClassList = AddedClass::GetAllClasses(true);

$SectionList =  array();
$SectionList = SectionMaster::GetAllSectionMasters(true);

$ClassSectionsList =  array();

$Clean = array();

$Clean['ClassID'] = 0;
$Clean['ClassSectionID'] = 0;
$Clean['ClassSectionClassTeacherName'] = 'N/A';

$Clean['Process'] = 0;

if (isset($_POST['hdnProcess']))
{
	$Clean['Process'] = (int) $_POST['hdnProcess'];
}
else if (isset($_GET['Process']))
{
	$Clean['Process'] = (int) $_GET['Process'];
}
switch ($Clean['Process'])
{
	case 7:
		if (isset($_POST['drdClassID']))
		{
			$Clean['ClassID'] = (int) $_POST['drdClassID'];
		}
		else if (isset($_GET['ClassID']))
		{
			$Clean['ClassID'] = (int) $_GET['ClassID'];
		}

		if (isset($_POST['drdClassSectionID']))
		{
			$Clean['ClassSectionID'] = (int) $_POST['drdClassSectionID'];
		}
		else if (isset($_GET['ClassSectionID']))
		{
			$Clean['ClassSectionID'] = (int) $_GET['ClassSectionID'];
		}

		$SearchRecordValidator = new Validator();

		if ($Clean['ClassID'] != 0)
        {
            if ($SearchRecordValidator->ValidateInSelect($Clean['ClassID'], $ClassList, 'Unknown error, please try again.'))
            {
                $ClassSectionsList = ClassSections::GetClassSections($Clean['ClassID']);
                
                if ($Clean['ClassSectionID'] > 0) 
                {
                    $SearchRecordValidator->ValidateInSelect($Clean['ClassSectionID'], $ClassSectionsList, 'Unknown error, please try again.');
                }
            }
		}
		
		if ($SearchRecordValidator->HasNotifications())
        {
            $HasSearchErrors = true;
            break;
        }

		try
		{
			$CurrentClassSection = new ClassSections($Clean['ClassSectionID']);
			
			if ($CurrentClassSection->GetClassTeacherID() > 0)
			{
				$CurrentClassTeacher = new BranchStaff($CurrentClassSection->GetClassTeacherID());
				
				$Clean['ClassSectionClassTeacherName'] = $CurrentClassTeacher->GetFirstName() . ' ' . $CurrentClassTeacher->GetLastName();
			}
			
			$CurrentClass = new AddedClass($CurrentClassSection->GetClassID());
			$CurrentSection = new SectionMaster($CurrentClassSection->GetSectionMasterID());

			$CurrentClass->FillAssignedSubjects();

			$ClassTimeTable = array();
			$ClassTimeTable = ClassHelpers::GetClassSectionTimeTable($Clean['ClassSectionID']);
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

		$ClassPeriodList = array();
		$ClassPeriodList = SchoolSessionClassDaywiseTiming::GetClassAllPeriodDetails($CurrentClassSection->GetClassID());

		$ClassSubjectList = array();
		$ClassSubjectList = $CurrentClass->GetAssignedSubjects();

		$DayList = array();

		$Counter = 0;
		for ($i = 0; $i < 7; $i++) {
			$DayList[++$Counter] = jddayofweek($i, 1);
		}
	break;
	case 1:
		if (isset($_POST['hdnClassSectionID']))
		{
			$Clean['ClassSectionID'] = (int) $_POST['hdnClassSectionID'];
		}

		if ($Clean['ClassSectionID'] <= 0)
		{
			header('location:/admin/error.php');
			exit;
		}

		try
		{
			$CurrentClassSection = new ClassSections($Clean['ClassSectionID']);
			$Clean['ClassID'] = $CurrentClassSection->GetClassID();
			$ClassSectionsList = ClassSections::GetClassSections($Clean['ClassID']);

			$CurrentClass = new AddedClass($CurrentClassSection->GetClassID());
			$CurrentSection = new SectionMaster($CurrentClassSection->GetSectionMasterID());

			$CurrentClass->FillAssignedSubjects();

			$ClassTimeTable = array();
			$ClassTimeTable = ClassHelpers::GetClassSectionTimeTable($Clean['ClassSectionID']);
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

		$ClassSubjectList = array();
		$ClassSubjectList = $CurrentClass->GetAssignedSubjects();

		$DayList = array();

		$Counter = 0;
		for ($i = 0; $i < 7; $i++) {
			$DayList[++$Counter] = jddayofweek($i, 1);
		}

		$SubjectIDList = array();
		$TeacherIDList = array();

		$TimeTableToSave = array();

		if (isset($_POST['hdnSubjectID']) && is_array($_POST['hdnSubjectID']) && count($_POST['hdnSubjectID']) > 0)
		{
			$SubjectIDList = $_POST['hdnSubjectID'];
		}

		if (isset($_POST['hdnTeacherID']) && is_array($_POST['hdnTeacherID']))
		{
			$TeacherIDList = $_POST['hdnTeacherID'];
		}
		
		foreach ($SubjectIDList as $DayID=>$DayDetails)
		{
			if (empty($DayDetails) || !array_key_exists($DayID, $DayList))
			{
				header('location:/admin/error.php');
    			exit;
			}

			foreach ($DayDetails as $SchoolTimingPartID=>$ClassSubjectID)
			{
				if (!array_key_exists($ClassSubjectID, $ClassSubjectList))
				{
					header('location:/admin/error.php');
    				exit;
				}

				$ClassDayPeriodDetails = false;

				$PeriodTimingID = 0;
				$DaywiseTimingsID = 0;
				$PeriodStartTime = '';
				$PeriodEndTime = '';

				$ClassDayPeriodDetails = SchoolSessionClassDaywiseTiming::IsPeriodApplicableForClass($CurrentClassSection->GetClassID(), $SchoolTimingPartID, $DayID, $DaywiseTimingsID, $PeriodStartTime, $PeriodEndTime, $PeriodTimingID);
				
				if ($ClassDayPeriodDetails == false)
				{
					header('location:/admin/error.php');
    				exit;
				}

				$ClassSubjectTeacherList = array();
				$ClassSubjectTeacherList = AddedClass::GetClassSubjectTeachers($DayID, $CurrentClassSection->GetClassID(), $ClassSubjectID, $PeriodStartTime, $PeriodEndTime);

				if (!isset($TeacherIDList[$DayID][$SchoolTimingPartID]))
				{
					header('location:/admin/error.php');
    				exit;
				}

				$TeacherClassID = 0;
				
				if (isset($TeacherIDList[$DayID][$SchoolTimingPartID]))
				{
				    $TeacherClassID = $TeacherIDList[$DayID][$SchoolTimingPartID];
				}

				if ($TeacherClassID > 0 && !array_key_exists($TeacherClassID, $ClassSubjectTeacherList))
				{
					header('location:/admin/error.php');
    				exit;
				}
				/*else if ($ClassSubjectTeacherList[$TeacherClassID]['IsBusy'])
				{
					header('location:/admin/error.php');
    				exit;
				}*/

				$TimeTableToSave[$DaywiseTimingsID][$PeriodTimingID]['ClassSubjectID'] = $ClassSubjectID;
				$TimeTableToSave[$DaywiseTimingsID][$PeriodTimingID]['TeacherClassID'] = $TeacherClassID;
			}
		}

		/*if (count($TimeTableToSave) <= 0)
		{
			header('location:class_timetable.php?Process=7&ClassID='.$Clean['ClassID'].'&ClassSectionID='.$Clean['ClassSectionID']);
			exit;
		}*/

		if (!ClassHelpers::SaveClassSectionTimeTable($Clean['ClassSectionID'], $LoggedUser->GetUserID(), $TimeTableToSave))
		{
			header('location:/admin/error.php');
			exit;
		}
		
		header('location:class_timetable.php?Mode=ED&Process=7&ClassSectionID='.$Clean['ClassSectionID']);
		exit;
	break;
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
                    <h1 class="page-header">Class Timetable</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>

			<div class="panel panel-default">
				<div class="panel-heading">
					Create Class Timetable
				</div>
				<div class="panel-body">
					<form class="form-horizontal" action="class_timetable.php" method="post">
<?php
					if ($HasSearchErrors == true)
					{
						echo $SearchRecordValidator->DisplayErrors();
					}
?>
						<div class="form-group">
							<label class="col-lg-2 control-label">Select Class:</label>
							<div class="col-lg-3">
								<select class="form-control" name="drdClassID" id="ClassID">
									<option value="0">Select Class</option>
<?php
								if (is_array($ClassList) && count($ClassList) > 0)
								{
									foreach ($ClassList as $ClassID => $ClassName) {
										echo '<option '.($Clean['ClassID'] == $ClassID ? 'selected="selected"' : '').' value="'.$ClassID.'">'.$ClassName.'</option>';
									}
								}
?>
								</select>
							</div>
							
							<label class="col-lg-2 control-label">Select Class:</label>
							<div class="col-lg-3">
								<select class="form-control" name="drdClassSectionID" id="ClassSectionID">
									<option value="0">Select Section</option>
<?php
								if (is_array($ClassSectionsList) && count($ClassSectionsList) > 0)
								{
									foreach ($ClassSectionsList as $ClassSectionID => $SectionName) 
									{
										echo '<option '.($Clean['ClassSectionID'] == $ClassSectionID ? 'selected="selected"' : '').' value="'.$ClassSectionID.'">'.$SectionName.'</option>';
									}
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
					Create Class Timetable
				</div>
				<div class="panel-body">
					<form action="class_timetable.php" method="post">
<?php
					if ($HasErrors == true)
					{
						echo $SearchRecordValidator->DisplayErrors();
					}
?>
					<div class="row">
                        <div class="col-lg-6">
                        </div>
                        <div class="col-lg-6">
                            <div class="print-btn-container"><button id="PrintButton" type="submit" class="btn btn-primary">Print</button></div>
                        </div>
                    </div>
                    <div class="row" id="RecordTableHeading" style="display: none;">
                    	<div class="col-lg-12 report-heading-container">
                    		<?php echo $CurrentClass->GetClassName().' '.$CurrentSection->GetSectionName().' ( '.$Clean['ClassSectionClassTeacherName'].' )'; ?>
                    	</div>
                    </div>
					<div class="col-lg-12">
						<div class="form-group">
							<label class="col-lg-2 control-label">Class Name:</label>
							<div class="col-lg-4"><span><?php echo $CurrentClass->GetClassName().' ('.$CurrentClass->GetClassSymbol().')'; ?></span></div>
							
							<label class="col-lg-2 control-label">Section Name:</label>
							<div class="col-lg-4"><span><?php echo $CurrentSection->GetSectionName(); ?></span></div>
						</div>
					</div>
					<div class="col-lg-12">
						<div class="form-group">
							<label class="col-lg-2 control-label">Class Teacher:</label>
							<div class="col-lg-4"><span><?php echo $Clean['ClassSectionClassTeacherName']; ?></span></div>

							<div class="col-lg-2"><input type="submit" name="btnSave" value="Save" class="btn btn-primary btn-block" /></div>
						</div>
					</div>
					<div class="col-lg-12">&nbsp;</div>
			<div class="row" id="RecordTable">
                <div class="col-lg-12">
					<table class="table table-striped table-bordered table-hover TimeTableParentTable" cellpadding="20px;" cellspacing="2px;" border="1px;" width="100%">
						<tr>
							<th>Period</th>
<?php
						foreach ($DayList as $DayName) 
						{
							if ($DayName == 'Sunday') 
							{
								echo '<th class="print-hidden">'.$DayName.'</th>';
							}
							else
							{
								echo '<th>'.$DayName.'</th>';
							}
						}
?>
						</tr>

<?php
						foreach ($ClassPeriodList as $SchoolTimingPartID=>$TimingPartDetails) 
						{
?>
							<tr <?php echo ($TimingPartDetails['PartType'] != 'Class') ? 'class="print-hidden"' : '';?>>
								<td><?php echo $TimingPartDetails['TimingPart']; ?></td>
<?php
							if ($TimingPartDetails['PartType'] != 'Class')
							{
								foreach ($DayList as $DayID=>$DayName) 
								{
									$ClassDayPeriodDetails = false;

									$PeriodTimingID = 0;
									$DaywiseTimingsID = 0;

									$PeriodStartTime = '';
									$PeriodEndTime = '';

									$ClassDayPeriodDetails = SchoolSessionClassDaywiseTiming::IsPeriodApplicableForClass($CurrentClassSection->GetClassID(), $SchoolTimingPartID, $DayID, $DaywiseTimingsID, $PeriodStartTime, $PeriodEndTime, $PeriodTimingID);

									if ($ClassDayPeriodDetails)
									{
?>
										<td>
											<div class="print-hidden" style="margin-bottom:5px;">(<?php echo date('h:i A', strtotime($PeriodStartTime)); ?> to <?php echo date('h:i A', strtotime($PeriodEndTime)); ?>)</div>
											<strong class="print-hidden" style="color:red;"><?php echo $TimingPartDetails['PartType']; ?></strong>
										</td>
<?php
									}
									else
									{
										echo '<td class="print-hidden">--</td>';
									}
								}
							}
							else
							{
								foreach ($DayList as $DayID=>$DayName) 
								{
									$ClassDayPeriodDetails = false;
									
									$PeriodTimingID = 0;
									$DaywiseTimingsID = 0;

									$PeriodStartTime = '';
									$PeriodEndTime = '';

									$ClassDayPeriodDetails = SchoolSessionClassDaywiseTiming::IsPeriodApplicableForClass($CurrentClassSection->GetClassID(), $SchoolTimingPartID, $DayID, $DaywiseTimingsID, $PeriodStartTime, $PeriodEndTime, $PeriodTimingID);

									if ($ClassDayPeriodDetails)
									{
?>
										<td id="TDTimingPart<?php echo $SchoolTimingPartID; ?>Day<?php echo $DayID; ?>">
											<div style="margin-bottom:5px;">(<?php echo date('h:i A', strtotime($PeriodStartTime)); ?> to <?php echo date('h:i A', strtotime($PeriodEndTime)); ?>)</div>
<?php
										if (isset($ClassTimeTable[$DaywiseTimingsID][$PeriodTimingID]))
										{
?>
											<div class="AllotmentDetails" style="display:block;">
												<div class="alert alert-info alert-dismissible" role="alert" style="margin-bottom: 8px; padding:5px;">
													<button style="left:0px;" type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
													<strong><?php echo $ClassTimeTable[$DaywiseTimingsID][$PeriodTimingID]['ClassSubjectName']; ?></strong><br /><?php echo $ClassTimeTable[$DaywiseTimingsID][$PeriodTimingID]['TeacherClassName']; ?>
													<input type="hidden" name="hdnSubjectID[<?php echo $DayID; ?>][<?php echo $SchoolTimingPartID; ?>]" value="<?php echo $ClassTimeTable[$DaywiseTimingsID][$PeriodTimingID]['ClassSubjectID']; ?>" />
													<input type="hidden" name="hdnTeacherID[<?php echo $DayID; ?>][<?php echo $SchoolTimingPartID; ?>]" value="<?php echo $ClassTimeTable[$DaywiseTimingsID][$PeriodTimingID]['TeacherClassID']; ?>" />
												</div>
											</div>
<?php
										}
										else
										{
?>
											<div class="AllotmentDetails" style="display:none;">&nbsp;</div>
<?php
										}
?>
											<a class="btn btn-xs btn-info print-hidden" onClick="return OnModelOpen(<?php echo $SchoolTimingPartID; ?>, <?php echo $DayID; ?>, '<?php echo $PeriodStartTime; ?>', '<?php echo $PeriodEndTime; ?>', '<?php echo $PeriodTimingID; ?>');" data-toggle="modal" data-target="#exampleModal">Allot Subject & Faculty</a>
										</td>
<?php
									}
									else
									{
										echo '<td class="print-hidden">--</td>';
									}
								}
							}
?>
							</tr>
<?php
						}
?>
					</table>
				</div>
         	</div>
					<div class="col-lg-12 text-center">
						<input type="hidden" name="hdnClassSectionID" value="<?php echo $Clean['ClassSectionID']; ?>" />
						<input type="hidden" name="hdnProcess" value="1" />
						<input type="submit" name="btnSave" value="Save" class="btn btn-primary" />
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

	<!-- /#page-model-begin -->
	<div class="modal fade model" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" data-timing-part-id="" data-day="" aria-hidden="true">
		<div class="modal-dialog" role="document">
			<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title" id="exampleModalLabel">Allot Subject & Faculty
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
					</button>
				</h4>
			</div>
			<div class="modal-body">
				<form>
				<div class="form-group">
					<label for="Subject" class="col-form-label">Select Subject:</label>
					<select name="drdSubject" class="form-control" id="Subject">
						<option value="0">Select Subject</option>
<?php
						if (!empty($ClassSubjectList))
						{
							foreach ($ClassSubjectList as $ClassSubjectID=>$SubjectDetails)
							{
								echo '<option value="'.$ClassSubjectID.'">'.$SubjectDetails['Subject'].'</option>';
							}
						}
?>
					</select>
				</div>
				<div class="form-group">
					<label for="Teacher" class="col-form-label">Select Faculty:</label>
					<select name="drdTeacher" class="form-control select-teacher" id="Teacher">
						<option value="0">Select Faculty</option>
					</select>
				</div>
				</form>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
				<button type="button" id="AllotSchedule" class="btn btn-primary">Allot / Schedule</button>
			</div>
			</div>
		</div>
	</div>
    <!-- /#wrapper -->
<?php
require_once('../footer.php');
?>
<script type="text/javascript">

<?php
if (isset($_GET['Mode']) && $_GET['Mode'] == 'ED')
{
?>
	alert('Timetable for Class <?php echo $CurrentClass->GetClassSymbol(); ?> Section <?php echo $CurrentSection->GetSectionName(); ?> has been saved successfully.');
<?php
}
?>

var GlobalSchoolTimingPartID = 0;
var GlobalDayID = 0;

var GlobalPeriodStartTime = '';
var GlobalPeriodEndTime = '';
var GlobalPeriodTimingID = '';

$(function(){
<?php
	if ($Clean['Process'] == 7 || ($Clean['Process'] == 1 && $HasSearchErrors == false))
	{
?>
		$('#exampleModal').on('hidden.bs.modal', function () {
			OnModelClose();
		});

		$('#Subject').change(function(){
			var SubjectID = parseInt($(this).val());
			
			if (SubjectID <= 0)
			{
				$('#Teacher').html('<option value="0">Select Faculty</option>');
				return;
			}
			
			$.post("/xhttp_calls/get_teachers_by_class_and_subject.php", {SelectedDayID:GlobalDayID, SelectedSubjectID:SubjectID, SelectedClassID:'<?php echo $CurrentClassSection->GetClassID(); ?>', SelectedPeriodStartTime:GlobalPeriodStartTime, SelectedPeriodEndTime:GlobalPeriodEndTime, SelectedPeriodTimingID:GlobalPeriodTimingID}, function(data){
			
				ResultArray = data.split("|*****|");
				
				if (ResultArray[0] == 'error')
				{
					alert (ResultArray[1]);
					return false;
				}

				$('#Teacher').html('<option value="0">Select Faculty</option>'+ResultArray[1]);
			});
		});
		
		$('.select-teacher').change(function () {
		    current_element = $(this);
		    SelectedValue = $(this).children("option:selected").text();
		    
		    if (SelectedValue.includes('Assigned'))
		    {
		        if (!confirm('This faculty is assigned to some other class at the same time, do you still want to assign?'))
		        {
		            current_element.val(0);
		        }
		    }
		});
<?php
	}
?>
	$('#ClassID').change(function(){
        var ClassID = parseInt($(this).val());
        
        if (ClassID <= 0)
        {
            $('#ClassSectionID').html('<option value="0">Select Section</option>');
            return;
        }
        
        $.post("/xhttp_calls/get_sections_by_classs.php", {SelectedClassID:ClassID}, function(data){
        
            ResultArray = data.split("|*****|");
            
            if (ResultArray[0] == 'error')
            {
                alert (ResultArray[1]);
                return false;
            }
            else
            {
                $('#ClassSectionID').html(ResultArray[1]);
            }
         });
    });
});

// On Allot Button's Click Event (Model Open)
function OnModelOpen(SchoolTimingPartID, DayID, PeriodStartTime, PeriodEndTime, PeriodTimingID)
{
	$('#AllotSchedule').attr('onClick', 'return AllotSubjectAndFaculty();');

	GlobalSchoolTimingPartID = SchoolTimingPartID;
	GlobalDayID = DayID;

	GlobalPeriodStartTime = PeriodStartTime;
	GlobalPeriodEndTime = PeriodEndTime;
	GlobalPeriodTimingID = PeriodTimingID;
}

// On Allot Button's Click Event (Allot Subject & Faculty)
function AllotSubjectAndFaculty()
{
	SelectedSubject = $('#Subject option:selected').html();
	
	if ($('#Teacher option:selected').html().includes('('))
	{
	    SelectedTeacher = $('#Teacher option:selected').html().substr(0, $('#Teacher option:selected').html().indexOf('('));
	}
	else
	{
	    SelectedTeacher = $('#Teacher option:selected').html();
	}

	SelectedSubjectID = parseInt($('#Subject').val());
	SelectedTeacherID = parseInt($('#Teacher').val());

	if (SelectedSubjectID <= 0)
	{
		alert('Please select a valid subject.');
		return false;
	}
	/*else if (SelectedTeacherID <= 0)
	{
		alert('Please select a valid teacher.');
		return false;
	}*/

	var AllotmentDetailBoxContent = '<div class="alert alert-info alert-dismissible" role="alert" style="margin-bottom: 8px; padding:5px;">';
	AllotmentDetailBoxContent += '<button style="left:0px;" type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>';
	AllotmentDetailBoxContent += '<strong>'+SelectedSubject+'</strong><br />'+SelectedTeacher;
	AllotmentDetailBoxContent += '<input type="hidden" name="hdnSubjectID['+ GlobalDayID +']['+ GlobalSchoolTimingPartID +']" value="'+ SelectedSubjectID +'" />';
	AllotmentDetailBoxContent += '<input type="hidden" name="hdnTeacherID['+ GlobalDayID +']['+ GlobalSchoolTimingPartID +']" value="'+ SelectedTeacherID +'" />';
	AllotmentDetailBoxContent += '</div>';


	AllotmentDetailsBox = $('#TDTimingPart'+GlobalSchoolTimingPartID+'Day'+GlobalDayID).find('.AllotmentDetails');
	AllotmentDetailsBox.css('display', 'block');
	AllotmentDetailsBox.html(AllotmentDetailBoxContent);

	OnModelClose();
	$('#exampleModal').modal('toggle');
}

function OnModelClose()														// On Model Hidden Event
{
	$('#Subject').val(0);
	$('#Teacher').html('<option value="0">Select Faculty</option>');
	$('#AllotSchedule').removeAttr('onClick');

	GlobalSchoolTimingPartID = 0;
	GlobalDayID = 0;

	GlobalPeriodStartTime = '';
	GlobalPeriodEndTime = '';
	GlobalPeriodTimingID = '';
}
</script>
<script src="js/print-report.js"></script>
<script src="/admin/js/print-report.js"></script>
</body>
</html>
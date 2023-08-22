<?php
header ("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");

require_once("../classes/school_administration/class.classes.php");
require_once("../classes/school_administration/class.section_master.php");
require_once("../classes/school_administration/class.branch_staff.php");
require_once("../classes/school_administration/class.class_sections.php");
require_once("../classes/school_administration/class.class_helpers.php");
require_once("../classes/school_administration/class.school_session_class_daywise_timings.php");

$Clean = array();

$Clean['ClassID'] = 0;
$Clean['SectionID'] = 0;

$Clean['ClassSectionClassTeacherName'] = '';

if (isset($_REQUEST['ClassID']))
{
	$Clean['ClassID'] = (int) $_REQUEST['ClassID'];
}

if (isset($_GET['SectionID']))
{
	$Clean['SectionID'] = (int) $_GET['SectionID'];
}

if ($Clean['ClassID'] < 1 || $Clean['SectionID'] < 1)
{
    echo json_encode(array('error' => 'Unknown error, please try again.'));
    exit;
}

try
{
	$CurrentClassSection = new ClassSections(0, $Clean['ClassID'], $Clean['SectionID']);
	
	if ($CurrentClassSection->GetClassTeacherID() > 0)
	{
		$CurrentClassTeacher = new BranchStaff($CurrentClassSection->GetClassTeacherID());
		
		$Clean['ClassSectionClassTeacherName'] = $CurrentClassTeacher->GetFirstName() . ' ' . $CurrentClassTeacher->GetLastName();
	}
	
	$CurrentClass = new AddedClass($CurrentClassSection->GetClassID());
	$CurrentSection = new SectionMaster($CurrentClassSection->GetSectionMasterID());

	$CurrentClass->FillAssignedSubjects();

	$ClassTimeTable = array();
	$ClassTimeTable = ClassHelpers::GetClassSectionTimeTable($CurrentClassSection->GetClassSectionID());
}
catch (ApplicationDBException $e)
{
	echo json_encode(array('error' => 'Unknown error, please try again.'));
    exit;
}
catch (Exception $e)
{
	echo json_encode(array('error' => 'Unknown error, please try again.'));
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
?>
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
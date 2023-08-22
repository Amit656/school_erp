<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");
require_once("../../classes/class.date_processing.php");

require_once("../../classes/school_administration/class.classes.php");
require_once('../../classes/school_administration/class.class_sections.php');
require_once('../../classes/school_administration/class.section_master.php');

require_once("../../classes/academic_supervision/class.chapters.php");
require_once("../../classes/academic_supervision/class.chapter_topics.php");
require_once("../../classes/academic_supervision/class.student_assignment.php");
require_once("../../classes/academic_supervision/class.topic_schedules.php");

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

if ($LoggedUser->HasPermissionForTask(TASK_SCHEDULE_LESSON_PLAN) !== true)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}

$ScheduleTypeList = array('Weekly' => 'Weekly', 'Monthly' => 'Monthly');

$ClassList =  array();
$ClassList = AddedClass::GetActiveClasses();

$ClassSectionsList =  array();

$ClassSubjectsList = array();
$UnScheduledChaptersList = array();

$TopicDetailList = array();

$HasErrors = false;
$SearchHasErrors = false;

$Clean = array();
$Clean['Process'] = 0;

$Clean['ClassID'] = 0;
$Clean['ClassSectionID'] = 0;
$Clean['ClassSubjectID'] = 0;

$Clean['ScheduleType'] = 'Weekly';
$Clean['ScheduleStartDate'] = '';
$Clean['ScheduleEndDate'] = '';
$Clean['Status'] = 'Pending';

$Clean['ChapterIDList'] = array();
$Clean['ChapterTopicIDList'] = array();
$Clean['ExpectedClassesList'] = array();
$Clean['StartDateList'] = array();

$TopicScheduleDetails = array();
$Errors = array();

$TotalExpectedClasses = 0;
$AvailableExpectedClasses = 0;

if (isset($_POST['hdnProcess']))
{
	$Clean['Process'] = (int) $_POST['hdnProcess'];
}
switch ($Clean['Process'])
{
	case 1:
		if (isset($_POST['hdnClassID'])) 
		{
			$Clean['ClassID'] = (int) $_POST['hdnClassID'];
		}
		if (isset($_POST['hdnClassSectionID'])) 
		{
			$Clean['ClassSectionID'] = (int) $_POST['hdnClassSectionID'];
		}
		if (isset($_POST['hdnClassSubjectID'])) 
        {
            $Clean['ClassSubjectID'] = (int) $_POST['hdnClassSubjectID'];
        }
        if (isset($_POST['hdnScheduleStartDate'])) 
        {
            $Clean['ScheduleStartDate'] = strip_tags(trim($_POST['hdnScheduleStartDate']));
        }
        if (isset($_POST['hdnScheduleType']))
        {
            $Clean['ScheduleType'] = strip_tags(trim($_POST['hdnScheduleType']));
        }

        if (isset($_POST['drdChapter']) && is_array($_POST['drdChapter'])) 
		{
			$Clean['ChapterIDList'] = $_POST['drdChapter'];
		}

		if (isset($_POST['chkChapterTopicIDList']) && is_array($_POST['chkChapterTopicIDList'])) 
		{
			$Clean['ChapterTopicIDList'] = $_POST['chkChapterTopicIDList'];
		}

		if (isset($_POST['txtExpectedClasses']) && is_array($_POST['txtExpectedClasses'])) 
		{
			$Clean['ExpectedClassesList'] = $_POST['txtExpectedClasses'];
		}

		if (isset($_POST['txtStartDate']) && is_array($_POST['txtStartDate'])) 
		{
			$Clean['StartDateList'] = $_POST['txtStartDate'];
		}

		if ($Clean['ScheduleType'] == 'Weekly') 
		{
			$AvailableExpectedClasses = 6;
		}
		else if ($Clean['ScheduleType'] == 'Monthly') 
		{
			$AvailableExpectedClasses = 30;
		}

		$NewRecordValidator = new Validator();
		$RecordValidator = new Validator();
		$SearchValidator = new Validator();

		if ($SearchValidator->ValidateInSelect($Clean['ClassID'], $ClassList, 'Unknown error, please try again.')) 
		{
			$ClassSectionsList = ClassSections::GetClassSections($Clean['ClassID']);

            $SearchValidator->ValidateInSelect($Clean['ClassSectionID'], $ClassSectionsList, 'Unknown error, please try again.');

            $SelectedAddedClass = new AddedClass($Clean['ClassID']);
            $SelectedAddedClass->FillAssignedSubjects();

            $ClassSubjectsList = $SelectedAddedClass->GetAssignedSubjects();
            $SearchValidator->ValidateInSelect($Clean['ClassSubjectID'], $ClassSubjectsList, 'Unknown error, please try again.');

            $UnScheduledChaptersList = Chapter::GetUnScheduledChapters($Clean['ClassSubjectID']);
		}

		foreach ($Clean['ChapterIDList'] as $Counter => $ChapterID) 
		{
			if (!$NewRecordValidator->ValidateInSelect($ChapterID, $UnScheduledChaptersList, 'Unknown error, please try again.')) 
			{
				$Errors[$Counter] = 'Error in chapter.';
				$HasErrors = true;
				break; 
			}

			$TopicDetailList[$ChapterID] = ChapterTopic::GetChapterTopicsByChapter($ChapterID);

			if (count($Clean['ChapterTopicIDList']) <= 0)
			{
				$RecordValidator->AttachTextError('Please select atleast one topic for schedule <u><i>' . $UnScheduledChaptersList[$ChapterID] . '</i></u>.');
				$HasErrors = true;				
			}

			if (count($Clean['ChapterTopicIDList']) > 0)
			{
				if (!isset($Clean['ChapterTopicIDList'][$Counter])) 
				{
					continue;
				}

				foreach ($Clean['ChapterTopicIDList'][$Counter] as $ChapterTopicKey => $ChapterTopicID) 
				{
					$NewRecordValidator->ValidateInSelect($ChapterTopicID, $TopicDetailList[$ChapterID], 'Unknown error, please try again.');
					
					if (!$NewRecordValidator->ValidateInteger($Clean['ExpectedClassesList'][$ChapterTopicID], 'Please enter numeric value for expected classes.', 1)) 
					{
						$Errors[$ChapterTopicID]['ExpectedClassesList'] = 'Error in expected class.';
					}

					$TotalExpectedClasses += $Clean['ExpectedClassesList'][$ChapterTopicID];

					if ($TotalExpectedClasses > $AvailableExpectedClasses) 
					{
						$RecordValidator->AttachTextError('Total expected classes are greater than available classes.');
						$HasErrors = true;	
					}

					$TopicScheduleDetails[$ChapterTopicID]['ExpectedClasses'] = $Clean['ExpectedClassesList'][$ChapterTopicID];

					if ($Clean['StartDateList'][$ChapterTopicID] != '') 
					{
						if (!$NewRecordValidator->ValidateDate($Clean['StartDateList'][$ChapterTopicID], "Please enter a valid start date.")) 
						{
							$Errors[$ChapterTopicID]['StartDateList'] = 'Error in start date.';
						}

						$TopicScheduleDetails[$ChapterTopicID]['StartDate'] = date('Y-m-d', strtotime(DateProcessing::ToggleDateDayAndMonth(($Clean['StartDateList'][$ChapterTopicID]))));
					}
					else
					{
						$Errors[$ChapterTopicID]['StartDateList'] = 'Error in start date.';
					}

					if (isset($Errors[$ChapterTopicID]['ExpectedClassesList']))
					{
						$NewRecordValidator->AttachTextError('Please enter a numeric value for expected Classes.');
					}

					if (isset($Errors[$ChapterTopicID]['StartDateList']))
					{
						$NewRecordValidator->AttachTextError('Please enter a valid start date.');
					}
				}
			}
		}

		if ($NewRecordValidator->HasNotifications())
        {
            $RecordValidator->AttachTextError('Errors found in your schedule, please scroll down to see errors.');
        }

		if ($RecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}

		$NewTopicSchedule = new TopicSchedule();
				
		$NewTopicSchedule->SetBranchStaffID($LoggedUser->GetUserID());

		$NewTopicSchedule->SetClassSectionID($Clean['ClassSectionID']);	
		$NewTopicSchedule->SetScheduleType($Clean['ScheduleType']);	
        $NewTopicSchedule->SetStartDate(date('Y-m-d', strtotime(DateProcessing::ToggleDateDayAndMonth(($Clean['ScheduleStartDate'])))));
        $NewTopicSchedule->SetStatus($Clean['Status']);

		$NewTopicSchedule->SetIsActive(1);
		$NewTopicSchedule->SetCreateUserID($LoggedUser->GetUserID());

		$NewTopicSchedule->SetTopicScheduleDetails($TopicScheduleDetails);

		if (!$NewTopicSchedule->Save())
		{
			$NewRecordValidator->AttachTextError(ProcessErrors($NewTopicSchedule->GetLastErrorCode()));
			$HasErrors = true;
			break;
		}
		
		header('location:topic_schedules.php?Mode=AS');
		exit;
	break;

	case 7:
		if (isset($_POST['drdClass'])) 
        {
            $Clean['ClassID'] = (int) $_POST['drdClass'];
        }
        if (isset($_POST['drdClassSection']))
        {
            $Clean['ClassSectionID'] = (int) $_POST['drdClassSection'];
        }
        if (isset($_POST['drdClassSubject']))
        {
            $Clean['ClassSubjectID'] = (int) $_POST['drdClassSubject'];
        }
        if (isset($_POST['txtScheduleStartDate']))
        {
            $Clean['ScheduleStartDate'] = strip_tags(trim($_POST['txtScheduleStartDate']));
        }
        if (isset($_POST['optScheduleType']))
        {
            $Clean['ScheduleType'] = strip_tags(trim($_POST['optScheduleType']));
        }

        $SearchValidator = new Validator();

        if ($SearchValidator->ValidateInSelect($Clean['ClassID'], $ClassList, 'Unknown error, please try again.')) 
        {
        	$ClassSectionsList = ClassSections::GetClassSections($Clean['ClassID']);

            $SelectedAddedClass = New AddedClass($Clean['ClassID']);
            $SelectedAddedClass->FillAssignedSubjects();

            $ClassSubjectsList = $SelectedAddedClass->GetAssignedSubjects();

            $SearchValidator->ValidateInSelect($Clean['ClassSectionID'], $ClassSectionsList, 'Unknown error, please select a valid section.');
            $SearchValidator->ValidateInSelect($Clean['ClassSubjectID'], $ClassSubjectsList, 'Unknown error, please select a subject.');
        }
        
        if ($SearchValidator->ValidateDate($Clean['ScheduleStartDate'], "Please enter a valid schedule start date.")) 
        {
        	if (strtotime(DateProcessing::ToggleDateDayAndMonth($Clean['ScheduleStartDate'])) < strtotime(DateProcessing::ToggleDateDayAndMonth(date('d/m/Y'))))
			{
			   $SearchValidator->AttachTextError('Schedule start date should not be less than current date.');
			}

	        $SearchValidator->ValidateInSelect($Clean['ScheduleType'], $ScheduleTypeList, 'Unknown error, please try again.');

	        if ($Clean['ScheduleType'] == 'Weekly') 
	        {
	        	$Day = date('l', strtotime(DateProcessing::ToggleDateDayAndMonth($Clean['ScheduleStartDate'])));

	        	if ($Day != 'Monday') 
	        	{
	        		$SearchValidator->AttachTextError('Weekly schedule start day must be monday, please try again.');
	        		$HasErrors = true;
	        		break;
	        	}

	        	$Clean['ScheduleEndDate'] = date('d/m/Y', strtotime(DateProcessing::ToggleDateDayAndMonth(($Clean['ScheduleStartDate']. '+ 5 days'))));
	        }

	        if ($Clean['ScheduleType'] == 'Monthly') 
	        {
	        	$Day = date('l', strtotime(DateProcessing::ToggleDateDayAndMonth($Clean['ScheduleStartDate'])));
	        	$Date = date('d', strtotime(DateProcessing::ToggleDateDayAndMonth($Clean['ScheduleStartDate'])));
	        	$LastDateOfMonth = date('t', strtotime(DateProcessing::ToggleDateDayAndMonth(($Clean['ScheduleStartDate']))));

	        	if ($Date == 1 && $Day == 'Sunday') 
	        	{
	        		$Clean['ScheduleStartDate'] = date('Y-m-d', strtotime(DateProcessing::ToggleDateDayAndMonth(($Clean['ScheduleStartDate'] + ' 1 days'))));

	        		$Clean['ScheduleEndDate'] = date('d/m/Y', strtotime(date('Y-m-d', strtotime(DateProcessing::ToggleDateDayAndMonth(($Clean['ScheduleStartDate'] . ' + ' .($LastDateOfMonth - 2). ' days'))))));
	        	}
	        	else if($Date != 1 && $Day == 'Sunday')
	        	{
	        		$SearchValidator->AttachTextError('Monthly schedule start date must be 1st and not be holiday, please try again.');
	        	}
	        	else
	        	{
	        		$Clean['ScheduleEndDate'] = date('d/m/Y', strtotime(date('Y-m-d', strtotime(DateProcessing::ToggleDateDayAndMonth(($Clean['ScheduleStartDate'] . ' + ' .($LastDateOfMonth - 1). ' days'))))));
	        	}
	        }
        }
        
        if ($SearchValidator->HasNotifications())
		{
			$SearchHasErrors = true;
			break;
		}

        $UnScheduledChaptersList = Chapter::GetUnScheduledChapters($Clean['ClassSubjectID']);
        $ChapterID = key($UnScheduledChaptersList);

        $TopicDetailList[$ChapterID] = ChapterTopic::GetChapterTopicsByChapter(key($UnScheduledChaptersList));

        $Clean['ChapterIDList'] = array(1 => $ChapterID);

	break;
}

$LandingPageMode = '';
if (isset($_GET['Mode']))
{
    $LandingPageMode = $_GET['Mode'];
}

require_once('../html_header.php');
?>
<title>Lesson Plan Schedule</title>
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
                    <h1 class="page-header">Lesson Plan Schedule</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
        	<div class="panel panel-default">
                <div class="panel-heading">
                    <strong>Enter Lesson Plan Schedule Details</strong>
                </div>
                <div class="panel-body">
<?php
					if ($SearchHasErrors == true)
					{
						echo $SearchValidator->DisplayErrors();
					}
					else if ($LandingPageMode == 'AS')
                    {
                        echo '<div class="alert alert-success">Record saved successfully.</div>';
                    }
?>                    
					<form class="form-horizontal" name="AddTopicSchedule" id="TopicScheduleForm" action="topic_schedules.php" method="post">
                    	<div class="form-group">
                            <label for="Class" class="col-lg-2 control-label">Class</label>
                            <div class="col-lg-3">
                                <select class="form-control"  name="drdClass" id="Class">
                                    <option  value="0" >-- Select Class --</option>
<?php
                                    if (is_array($ClassList) && count($ClassList) > 0)
                                    {
                                        foreach ($ClassList as $ClassID => $ClassName)
	                                    {
	?>
	                                        <option <?php echo ($ClassID == $Clean['ClassID'] ? 'selected="selected"' : ''); ?> value="<?php echo $ClassID; ?>"><?php echo $ClassName; ?></option>
	<?php
	                                    }
                                    }
?>
                                </select>
                            </div>
                            <label for="ClassSection" class="col-lg-2 control-label">Section</label>
                            <div class="col-lg-3">
                                <select class="form-control" name="drdClassSection" id="ClassSection">
                                    <option value="0">-- Select Section --</option>
<?php
                                        if (is_array($ClassSectionsList) && count($ClassSectionsList) > 0)
                                        {
                                            foreach ($ClassSectionsList as $ClassSectionID => $SectionName) 
                                            {
                                                echo '<option ' . ($Clean['ClassSectionID'] == $ClassSectionID ? 'selected="selected"' : '') . ' value="' . $ClassSectionID . '">' . $SectionName . '</option>' ;
                                            }
                                        }
?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                        	<label for="ClassSubject" class="col-lg-2 control-label">Subject</label>
                            <div class="col-lg-3">
                                <select class="form-control" name="drdClassSubject" id="ClassSubject">
                                        <option value="0" >-- Select Subject --</option>
<?php
                                        if (is_array($ClassSubjectsList) && count($ClassSubjectsList) > 0)
                                        {
                                            foreach ($ClassSubjectsList as $ClassSubjectID => $ClassSubjectDetail) 
                                            {
                                                echo '<option ' . ($Clean['ClassSubjectID'] == $ClassSubjectID ? 'selected="selected"' : '') . ' value="' . $ClassSubjectID . '">' . $ClassSubjectDetail['Subject'] . '</option>' ;
                                            }
                                        }
?>
                                </select>
                            </div>
                            <label for="ScheduleType" class="col-lg-2 control-label">Schedule Type</label>
                            <div class="col-sm-4">
<?php
                            foreach($ScheduleTypeList as $ScheduleTypeID => $ScheduleType)
                            {
?>                              
                                <label class="col-sm-5"><input class="custom-radio" type="radio" id="<?php echo $ScheduleTypeID; ?>" name="optScheduleType" value="<?php echo $ScheduleTypeID; ?>" <?php echo ($Clean['ScheduleType'] == $ScheduleTypeID ? 'checked="checked"' : ''); ?> >&nbsp;&nbsp;<?php echo $ScheduleType; ?></label>            
<?php                                       
                            }
?>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="ScheduleStartDate" class="col-lg-2 control-label">Schedule Start Date</label>
                            <div class="col-lg-3">
                                <input class="form-control select-date noBrowserAutofill" autocomplete="off" type="text" maxlength="10" id="ScheduleStartDate" name="txtScheduleStartDate" value="<?php echo $Clean['ScheduleStartDate']; ?>" />
                            </div> 
                            <label for="ScheduleEndDate" class="col-lg-2 control-label">Schedule End Date</label>
                            <div class="col-lg-3">
                                <input class="form-control select-date" type="text" maxlength="10" id="ScheduleEndDate" name="txtScheduleEndDate"  value="<?php echo $Clean['ScheduleEndDate']; ?>" disabled="disabled" />
                            </div>
                        </div>                        
                        <div class="form-group">
	                        <div class="col-sm-offset-2 col-lg-10">
                                <input type="hidden" name="hdnProcess" value="7" />
                                <button type="submit" class="btn btn-primary"><i class="fa fa-search"></i>&nbsp;Search</button>
	                        </div>
                      	</div>
            		</form>
            		<br>
<?php
		if (count($UnScheduledChaptersList) > 0) 
		{
?>
					<form class="form-horizontal" name="AddTopicSchedule" id="TopicScheduleForm" action="topic_schedules.php" method="post">
		            	<div class="panel-group">
		            	<div id="ChapterDetailsContainer">
<?php
						if ($HasErrors == true)
						{
							echo $SearchValidator->DisplayErrors();
							echo $RecordValidator->DisplayErrors();
						}
?>  
<?php 				
						foreach ($Clean['ChapterIDList'] as $Counter => $ListedChapterID) 
						{
?>
							<div class="panel panel-default ChapterDetailsPanel">
			                	<div class="panel-heading">
			                        <strong>Chapter: <?php echo $UnScheduledChaptersList[$ListedChapterID]; ?> Details</strong>
			                        <strong class="pull-right"><a data-toggle="collapse" data-parent="#ChapterDetailsContainer" href="#<?php echo $Counter ;?>"><i class="fa fa-minus"></i></a></strong>
			                    </div>
			                    <div id="<?php echo $Counter;?>" class="panel-collapse collapse in">
				                	<div class="panel-body">
				                    	<div class="form-group">
				                            <label for="Chapter" class="col-lg-2 control-label">Chapter</label>
				                            <div class="col-lg-8">
				                                <select class="form-control ElementChapterClass" name="drdChapter[<?php echo $Counter;?>]" id="Chapter<?php echo $Counter;?>">
<?php
			                                        if (is_array($UnScheduledChaptersList) && count($UnScheduledChaptersList) > 0)
			                                        {
			                                            foreach ($UnScheduledChaptersList as $ChapterID => $ChapterName) 
			                                            {
			                                                echo '<option ' . (($ChapterID == $Clean['ChapterIDList'][$Counter]) ? 'selected="selected"' : '') . ' value="' . $ChapterID . '">' . $ChapterName . '</option>' ;
			                                            }
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
				                                            <th>Topic Name</th>                                        
				                                            <th>Expected Classes</th>
				                                            <th>Start Date</th>
				                                        </tr>
				                                    </thead>
				                                    <tbody>
<?php
				                                if (is_array($TopicDetailList) && count($TopicDetailList) > 0)
				                                {
				                                    $Count = 0;
				                                    
				                                    foreach ($TopicDetailList[$ListedChapterID] as $ChapterTopicID => $TopicName)
				                                    {
?>
				                                        <tr>
				                                            <td><?php echo ++$Count; ?></td>
				                                            <td>
				                                                <label class="checkbox-inline"><input class="custom-radio" type="checkbox" id="ChapterTopicID<?php echo $ChapterTopicID; ?>" name="chkChapterTopicIDList[<?php echo $Counter;?>][<?php echo $ChapterTopicID; ?>]" <?php echo (array_key_exists($Counter, $Clean['ChapterTopicIDList']) ? in_array($ChapterTopicID, $Clean['ChapterTopicIDList'][$Counter]) ? 'checked="checked"' : '' : ''); ?> value="<?php echo $ChapterTopicID; ?>" /><?php echo $TopicName; ?>
				                                                </label>
				                                            <td class="<?php echo isset($Errors[$ChapterTopicID]['ExpectedClassesList']) ? ' has-error' : ''; ?>">
				                                            	<input class="form-control ExpectedClasses" type="text" maxlength="5" id="ExpectedClasses<?php echo $ChapterTopicID; ?>" name="txtExpectedClasses[<?php echo $ChapterTopicID;?>]" value="<?php echo ((array_key_exists($ChapterTopicID, $Clean['ExpectedClassesList'])) ? $Clean['ExpectedClassesList'][$ChapterTopicID] : ''); ?>" />

				                                            	<?php echo isset($Errors[$ChapterTopicID]['ExpectedClassesList']) ? '<small class="error text-danger">( Enter numeric value.)</small>' : ''; ?>
				                                            </td>
				                                            <td class="<?php echo isset($Errors[$ChapterTopicID]['StartDateList']) ? ' has-error' : ''; ?>">
				                                                <input class="form-control select-date" type="text" maxlength="10" id="StartDate<?php echo $ChapterTopicID; ?>" name="txtStartDate[<?php echo $ChapterTopicID;?>]" value="<?php echo ((array_key_exists($ChapterTopicID, $Clean['StartDateList'])) ? $Clean['StartDateList'][$ChapterTopicID] : ''); ?>" />
				                                                
				                                                <?php echo isset($Errors[$ChapterTopicID]['StartDateList']) ? '<small class="error text-danger">( Enter valid date.)</small>' : ''; ?>
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
				                    </div>
				                </div>
				            </div>                        				        
<?php
						}						
?>				
							</div>
							<br>
							<div class="form-group">
			                    <div class="col-sm-offset-2 col-lg-4">
			                        <input type="hidden" name="hdnProcess" value="1" />
			                        <input type="hidden" name="hdnClassID" value="<?php echo $Clean['ClassID']; ?>" />
			                        <input type="hidden" name="hdnClassSectionID" value="<?php echo $Clean['ClassSectionID']; ?>" />
			                        <input type="hidden" name="hdnClassSubjectID" value="<?php echo $Clean['ClassSubjectID']; ?>" />
			                        <input type="hidden" name="hdnScheduleStartDate" value="<?php echo $Clean['ScheduleStartDate']; ?>" />
			                        <input type="hidden" name="hdnScheduleType" value="<?php echo $Clean['ScheduleType']; ?>" />
			                        <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i>&nbsp;Save</button>
			                    </div>
			                    <div class="col-lg-2 pull-right">
			                        <button type="button" class="btn btn-primary" id="AddMore">Add More&nbsp;<i class="fa fa-plus"></i></button>
			                    </div>
			              	</div>
						</div>
	            	</form>
<?php					
		}
?>
	            </div>
	        </div>
        </div>
        <!-- /#page-wrapper -->

    </div>
    <!-- /#wrapper -->
<?php
require_once('../footer.php');
?>
<style>
.ElementChapterClass {
    pointer-events:none;
}
</style>
<script src="../vendor/jquery-ui/jquery-ui.min.js"></script>
<script type="text/javascript">
$(document).ready(function(){

    $(".select-date").datepicker({
                changeMonth: true,
                changeYear: true,
                dateFormat: 'dd/mm/yy'
        });

    $('#ScheduleStartDate').change(function(){

    	var weekday = ["Sunday","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"];
		var ScheduleStartDate = $('#ScheduleStartDate').datepicker('getDate');
	    var date = new Date(Date.parse(ScheduleStartDate)); 

    	if ($("input[name='optScheduleType']:checked").val() == 'Weekly') 
    	{
		    if (weekday[date.getDay()] != 'Monday') 
		    {
		    	alert('Day must be Monday for weekly schedule.');
		    	$('#ScheduleStartDate').val('');
		    	return false;
		    }

		    date.setDate(date.getDate() + 5);
		    
		    var ScheduleEndDate = date.toDateString(); 
		    ScheduleEndDate = new Date(Date.parse( ScheduleEndDate));
		    
		    $('#ScheduleEndDate').datepicker('setDate', ScheduleEndDate);
    	}
    	else if ($("input[name='optScheduleType']:checked").val() == 'Monthly')
    	{
    		var DaysInMonth = new Date(date.getFullYear(), date.getMonth() + 1, 0).getDate();
    		if (date.getDate() != 1) 
    		{
    			alert('For monthly schedule, date must be 1st and not be holiday.');
		    	$('#ScheduleStartDate').val('');
		    	return false;
    		}

    		if (date.getDate() == 1 && weekday[date.getDay()] == 'Sunday') 
		    {
		    	date.setDate(date.getDate() + 1);
		    
			    var ScheduleStartDate = date.toDateString(); 
			    ScheduleStartDate = new Date(Date.parse( ScheduleStartDate));

		    	alert('1st day of month is sunday, so schedule start date is 2nd.');
		    	$('#ScheduleStartDate').datepicker('setDate', ScheduleStartDate);

		    	var date = new Date(Date.parse(ScheduleStartDate));

		    	date.setDate(date.getDate() + DaysInMonth - 2);
		    	var ScheduleEndDate = date.toDateString(); 
			    ScheduleEndDate = new Date(Date.parse( ScheduleEndDate));
			    
			    $('#ScheduleEndDate').datepicker('setDate', ScheduleEndDate);
		    }
		    else
		    {
		    	date.setDate(date.getDate() + DaysInMonth - 1);
		    
			    var ScheduleEndDate = date.toDateString(); 
			    ScheduleEndDate = new Date(Date.parse( ScheduleEndDate));
			    
			    $('#ScheduleEndDate').datepicker('setDate', ScheduleEndDate);
		    }
    	}
    });

    $('.ExpectedClasses').change(function(){

    	var TotalExpectedClasses = 0;

    	$('.ExpectedClasses').each(function(){
    		ExpectedClasses = $(this).val();
    		if (ExpectedClasses != '') 
    		{
    			TotalExpectedClasses += parseInt(ExpectedClasses);
    		}
    	});

    	if ($("input[name='optScheduleType']:checked").val() == 'Weekly')
    	{
    		if (TotalExpectedClasses > 6) 
    		{
    			alert('Total expected classes is not greater than available classes');
    			return false;
    		}
    	}
    	else if ($("input[name='optScheduleType']:checked").val() == 'Monthly')
    	{
    		if (TotalExpectedClasses > 30) 
    		{
    			alert('Total expected classes is not greater than available classes');
    			return false;
    		}
    	}
    });

	$('#Class').change(function(){

        var ClassID = parseInt($('#Class').val());
        $('#ClassSubject').html('<option value="0">-- Select Subject --</option>');
        $('#Chapter').html('<option value="0">-- Select Chapter --</option>');
        
        if (ClassID <= 0)
        {
            $('#ClassSection').html('<option value="0">-- Select Section --</option>');
            return;
        }
        
        $.post("/xhttp_calls/get_sections_by_classs.php", {SelectedClassID:ClassID}, function(data)
        {
            ResultArray = data.split("|*****|");
            
            if (ResultArray[0] == 'error')
            {
                alert (ResultArray[1]);
                return false;
            }
            else
            {
                $('#ClassSection').html('<option value="0">-- Select Section --</option>' + ResultArray[1]);
            }
        });
    });

    $('#ClassSection').change(function(){

        var ClassID = parseInt($('#Class').val());
        $('#ClassSubject').html('<option value="0">-- Select Subject --</option>');
        $('#Chapter').html('<option value="0">-- Select Chapter --</option>');
        
        if (ClassID <= 0)
        {
            $('#ClassSection').html('<option value="0">-- Select Section --</option>');
            return;
        }
        
        $.post("/xhttp_calls/get_subjects_by_class.php", {SelectedClassID:ClassID}, function(data)
        {
            ResultArray = data.split("|*****|");
            
            if (ResultArray[0] == 'error')
            {
                alert (ResultArray[1]);
                return false;
            }
            else
            {
                $('#ClassSubject').html('<option value="0">-- Select Subject --</option>' + ResultArray[1]);
            }
        });
    });
    
    //$('.ElementChapterClass').css("pointer-events", "none");

    $('#AddMore').click(function(){
        
        var Counter = 0;
        Counter = parseInt($('.ChapterDetailsPanel').length) + 1;
        var PreviousChapterID = $('#Chapter'+ (Counter-1)).val();
        var NextChapterID = $('#Chapter'+ (Counter-1)).find(':selected').next().val();
        var NextChapterName = $('#Chapter'+ (Counter-1)).find(':selected').next().text();

        if (NextChapterID == undefined) 
        {
        	alert('No chapter available for schedule.');
        	return false;
        }
        
        var Data = '<div class="panel panel-default ChapterDetailsPanel">'
            Data += '<div class="panel-heading">'
            Data += '<strong>Chapter: '+ NextChapterName +' Details</strong>'
            Data += '<strong class="pull-right"><a data-toggle="collapse" data-parent="#ChapterDetailsContainer" href="#' + Counter + '"><i class="fa fa-minus"></i></a></strong>'
            Data += '</div>'
            Data += '<div id="' + Counter + '" class="panel-collapse collapse in">'
            Data += '<div class="panel-body">'
            Data += '<div class="form-group">'
            Data += '<label for="Chapter" class="col-lg-2 control-label">Chapter</label>'
            Data += '<div class="col-lg-8">'
            Data += '<select class="form-control ElementChapterClass" name="drdChapter['+ Counter +']" id="Chapter'+ Counter +'">'

<?php
	if (is_array($UnScheduledChaptersList) && count($UnScheduledChaptersList) > 0)
	{
        foreach ($UnScheduledChaptersList as $ChapterID => $ChapterName) 
        {
?>
            Data += '<option value="<?php echo $ChapterID; ?>" <?php echo (in_array($ChapterID, $Clean['ChapterIDList']) ? 'selected="selected"' : ''); ?>> <?php echo str_replace('\'', '', $ChapterName); ?></option>'
<?php
        }
	}        
?>
            Data += '</select>'
            Data += '</div>'
            Data += '</div>'
            Data += '<div class="row" id="RecordTable">'
            Data += '<div class="col-lg-12">'
            Data += '<table width="100%" class="table table-striped table-bordered table-hover" id="Table'+ Counter +'">'

            Data += '</table>'
            Data += '</div>'
            Data += '</div>'
            Data += '</div>'
            Data += '</div>'
            Data += '</div>';

            $('#ChapterDetailsContainer').append(Data);
         	$('#Chapter'+ Counter).val(NextChapterID).prop('selected', true);

         	$.post("/xhttp_calls/get_topic_details_by_chapter.php", {SelectedChapterID:NextChapterID, Counter:Counter}, function(data)
	        {
	            ResultArray = data.split("|*****|");
	            
	            if (ResultArray[0] == 'error')
	            {
	                alert (ResultArray[1]);
	                return false;
	            }
	            else
	            {
	                $('#Table'+ Counter).html(ResultArray[1]);
	            }
	        });
    });
    
});

</script>
</body>
</html>
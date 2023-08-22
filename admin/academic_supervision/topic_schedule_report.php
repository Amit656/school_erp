<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");
require_once("../../classes/class.date_processing.php");

require_once("../../classes/class.ui_helpers.php");

require_once("../../classes/school_administration/class.classes.php");
require_once('../../classes/school_administration/class.class_sections.php');
require_once('../../classes/school_administration/class.section_master.php');

require_once("../../classes/academic_supervision/class.chapters.php");
require_once("../../classes/academic_supervision/class.chapter_topics.php");
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

if ($LoggedUser->HasPermissionForTask(TASK_LESSON_PLAN_SCHEDULE_REPORT) !== true)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}

$ScheduleTypeList = array('Weekly' => 'Weekly', 'Monthly' => 'Monthly');

$ClassList =  array();
$ClassList = AddedClass::GetActiveClasses();

$ClassSectionsList =  array();

$ClassSubjectsList = array();
$TopicScheduleList = array();

$HasErrors = false;
$SearchHasErrors = false;
$RecordDeletedSuccessfully = false;

$Clean = array();

$Clean['Process'] = 0;

$Clean['ClassID'] = 0;
$Clean['ClassSectionID'] = 0;
$Clean['ClassSubjectID'] = 0;
$Clean['ActiveStatus'] = 0;

$Clean['ScheduleType'] = 'Weekly';
$Clean['ScheduleStartDate'] = '';
$Clean['ScheduleEndDate'] = '';
$Clean['Status'] = 'Pending';

$Clean['ChapterIDList'] = array();
$Clean['ChapterTopicIDList'] = array();
$Clean['ExpectedClassesList'] = array();
$Clean['StartDateList'] = array();

// paging and sorting variables start here  //
$Clean['CurrentPage'] = 1;
$TotalPages = 0;

$Start = 0;
$Limit = GLOBAL_SITE_PAGGING;
// end of paging variables//

$Filters = array();

if (isset($_GET['hdnProcess']))
{
	$Clean['Process'] = (int) $_GET['hdnProcess'];
}
else if (isset($_GET['Process']))
{
	$Clean['Process'] = (int) $_GET['Process'];	
}
switch ($Clean['Process'])
{
    case 5:
        if (isset($_GET['TopicScheduleID'])) 
        {
            $Clean['TopicScheduleID'] = (int) $_GET['TopicScheduleID'];
        }
        
        // if ($LoggedUser->HasPermissionForTask(TASK_DELETE_STUDENT_REMARK) !== true)
        // {
        //     header('location:/admin/unauthorized_login_admin.php');
        //     exit;
        // }
        
        if ($Clean['TopicScheduleID'] <= 0)
        {
            header('location:../error_page.php');
            exit;
        }                       
            
        try
        {
            $TopicScheduleToDelete = new TopicSchedule($Clean['TopicScheduleID']);
        }
        catch (ApplicationDBException $e)
        {
            header('location:../error_page.php');
            exit;
        }
        catch (Exception $e)
        {
            header('location:../error_page.php');
            exit;
        }
        
        $SearchValidator = new Validator();
        
        if ($TopicScheduleToDelete->CheckDependencies())
        {
            $SearchValidator->AttachTextError('This schedule cannot be deleted. There are dependent records for this schedule.');
            $SearchHasErrors = true;
            break;
        }
        
        if (!$TopicScheduleToDelete->Remove())
        {
            $SearchValidator->AttachTextError(ProcessErrors($TopicScheduleToDelete->GetLastErrorCode()));
            $SearchHasErrors = true;
            break;
        }
        
        $RecordDeletedSuccessfully = true;
        break;
        
	case 7:
		if (isset($_GET['drdClass'])) 
        {
            $Clean['ClassID'] = (int) $_GET['drdClass'];
        }
        elseif (isset($_GET['ClassID']))
        {
            $Clean['ClassID'] = (int) $_GET['ClassID'];
        }

        if (isset($_GET['drdClassSection']))
        {
            $Clean['ClassSectionID'] = (int) $_GET['drdClassSection'];
        }
        elseif (isset($_GET['ClassSectionID']))
        {
            $Clean['ClassSectionID'] = (int) $_GET['ClassSectionID'];
        }

        if (isset($_GET['drdClassSubject']))
        {
            $Clean['ClassSubjectID'] = (int) $_GET['drdClassSubject'];
        }
        elseif (isset($_GET['ClassSubjectID']))
        {
            $Clean['ClassSubjectID'] = (int) $_GET['ClassSubjectID'];
        }

        if (isset($_GET['txtScheduleStartDate']))
        {
            $Clean['ScheduleStartDate'] = strip_tags(trim($_GET['txtScheduleStartDate']));
        }
        elseif (isset($_GET['ScheduleStartDate']))
        {
            $Clean['ScheduleStartDate'] = (int) $_GET['ScheduleStartDate'];
        }
        
        $SearchValidator = new Validator();

        if ($Clean['ClassID'] > 0) 
        {
        	if ($SearchValidator->ValidateInSelect($Clean['ClassID'], $ClassList, 'Unknown error, please try again.')) 
	        {
	        	$ClassSectionsList = ClassSections::GetClassSections($Clean['ClassID']);

	            $SelectedAddedClass = New AddedClass($Clean['ClassID']);
	            $SelectedAddedClass->FillAssignedSubjects();

	            $ClassSubjectsList = $SelectedAddedClass->GetAssignedSubjects();

	            if ($Clean['ClassSectionID'] > 0) 
	            {
	            	$SearchValidator->ValidateInSelect($Clean['ClassSectionID'], $ClassSectionsList, 'Unknown error, please select a valid section.');
	            }
	            
	            if ($Clean['ClassSubjectID'] > 0)
	            {
	            	$SearchValidator->ValidateInSelect($Clean['ClassSubjectID'], $ClassSubjectsList, 'Unknown error, please select a valid subject.');
	            }
	        }
        }
        
        if ($Clean['ScheduleStartDate'] != '') 
        {
         	$SearchValidator->ValidateDate($Clean['ScheduleStartDate'], "Please enter a valid schedule start date.");
		}        

        if ($SearchValidator->HasNotifications())
		{
			$SearchHasErrors = true;
			break;
        }
        
		$Filters['ClassID'] = $Clean['ClassID'];
        $Filters['ClassSubjectID'] = $Clean['ClassSubjectID'];
        
		if ($Clean['ScheduleStartDate'] != '') 
        {
         	$Filters['ScheduleStartDate'] = date('Y-m-d', strtotime(DateProcessing::ToggleDateDayAndMonth(($Clean['ScheduleStartDate']))));
		} 
		
		$Filters['BranchStaffID'] = $LoggedUser->GetUserID();
		// $Filters['BranchStaffID'] = 1000001;

		TopicSchedule::SearchTopicSchedules($TotalRecords, true, $Filters);

        if ($TotalRecords > 0)
        {
            // Paging and sorting calculations start here.
            $TotalPages = (($TotalRecords % $Limit) == 0) ? $TotalRecords / $Limit : floor($TotalRecords / $Limit) + 1;

            if (isset($_GET['CurrentPage']))
            {
                $Clean['CurrentPage'] = (int) $_GET['CurrentPage'];
            }

            if ($Clean['CurrentPage'] <= 0)
            {
                $Clean['CurrentPage'] = 1;
            }
            elseif ($Clean['CurrentPage'] > $TotalPages)
            {
                $Clean['CurrentPage'] = $TotalPages;
            }

            if ($Clean['CurrentPage'] > 1)
            {
                $Start = ($Clean['CurrentPage'] - 1) * $Limit;
            }
            // end of Paging and sorting calculations.
            // now get the actual  records

            $TopicScheduleList = TopicSchedule::SearchTopicSchedules($TotalRecords, false, $Filters, $Start, $Limit);
        }        

	break;
}

$LandingPageMode = '';
if (isset($_GET['Mode']))
{
    $LandingPageMode = $_GET['Mode'];
}

require_once('../html_header.php');
?>
<title>Lesson Plan Report</title>
<link href="../vendor/jquery-ui/jquery-ui.min.css" rel="stylesheet">
<!-- DataTables CSS -->
<link href="../vendor/datatables-plugins/dataTables.bootstrap.css" rel="stylesheet">

<!-- DataTables Responsive CSS -->
<link href="../vendor/datatables-responsive/dataTables.responsive.css" rel="stylesheet">
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
                    <h1 class="page-header">Lesson Plan Report</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
        	<div class="panel panel-default">
                <div class="panel-heading">
                    <strong><a data-toggle="collapse" data-parent="#accordion" href="#collapseOne">Apply Filters :</a></strong>
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
                    else if ($RecordDeletedSuccessfully == true)
                    {
                        echo '<div class="alert alert-danger">Record deleted successfully.</div>';
                    }
?>                    
					<form class="form-horizontal" name="AddTopicSchedule" id="TopicScheduleForm" action="topic_schedule_report.php" method="get">
                    	<div class="form-group">
                            <label for="Class" class="col-lg-2 control-label">Class</label>
                            <div class="col-lg-3">
                                <select class="form-control"  name="drdClass" id="Class">
                                    <option  value="0" >-- All Class --</option>
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
                                    <option value="0">-- All Section --</option>
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
                                        <option  value="0" >-- All Subject --</option>
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
                            <label for="ScheduleStartDate" class="col-lg-2 control-label">Schedule Start Date</label>
                            <div class="col-lg-3">
                                <input class="form-control select-date" type="text" maxlength="10" id="ScheduleStartDate" name="txtScheduleStartDate" value="<?php echo $Clean['ScheduleStartDate']; ?>" />
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
        if ($Clean['Process'] == 7 && $HasErrors == false)
        {
            $ReportHeaderText = '';

            if ($Clean['ClassID'] != 0)
            {
                $ReportHeaderText .= ' Class: ' . $ClassList[$Clean['ClassID']] . ',';
            }

            if ($Clean['ClassSectionID'] != 0)
            {
                $ReportHeaderText .= ' Section: ' . $ClassSectionsList[$Clean['ClassSectionID']] . ',';
            }

            if ($Clean['ClassSubjectID'] != 0)
            {
                $ReportHeaderText .= ' Subject: ' . $ClassSubjectsList[$Clean['ClassSubjectID']]['Subject'] . ',';
            }

            if ($Clean['ActiveStatus'] == 1)
            {
                $ReportHeaderText .= ' Status: Active,';
            }
            else if ($Clean['ActiveStatus'] == 2)
            {
                $ReportHeaderText .= ' Status: In-Active,';
            }

            if ($ReportHeaderText != '')
            {
                $ReportHeaderText = ' for' . rtrim($ReportHeaderText, ',');
            }
?>
            <div class="row">
                <div class="col-lg-12">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <strong>Total Records Returned: <?php echo $TotalRecords; ?></strong>
                        </div>
                        <!-- /.panel-heading -->
                        <div class="panel-body">
                            <div>
                                <div class="row">
                                    <div class="col-lg-6">
<?php
                                    if ($TotalPages > 1)
                                    {
                                        $AllParameters = array('Process' => '7', 'ClassID' => $Clean['ClassID'], 'ClassSectionID' => $Clean['ClassSectionID'], 'ClassSubjectID' => $Clean['ClassSubjectID'], 'ScheduleStartDate' => $Clean['ScheduleStartDate']);
                                        echo UIHelpers::GetPager('topic_schedule_report.php', $TotalPages, $Clean['CurrentPage'], $AllParameters);
                                    }
?>                                        
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="print-btn-container"><button id="PrintButton" type="submit" class="btn btn-primary">Print</button></div>
                                    </div>
                                </div>
                                <div class="row" id="RecordTableHeading">
                                    <div class="col-lg-12">
                                        <div class="report-heading-container"><strong>Lesson Plan Schedule Report on <?php echo date('d-m-Y h:i A') . $ReportHeaderText; ?></strong></div>
                                    </div>
                                </div>
                                <div class="row" id="RecordTable">
                                    <div class="col-lg-12">
                                        <table width="100%" class="table table-striped table-bordered table-hover" id="DataTableRecords">
                                            <thead>
                                                <tr>
                                                    <th>S.No</th>
                                                    <th>Start Date</th>
                                                    <th>Class</th>
                                                    <th>Subject</th>
                                                    <th>Schedule Type</th>                                                    
                                                    <th>Status</th>
                                                    <th>Status Updated On</th>
                                                    <th>Is Active</th>
                                                    <th>Create User</th>
                                                    <th>Create Date</th>
                                                    <th class="print-hidden">Operations</th>
                                                </tr>
                                            </thead>
                                            <tbody>
<?php
                                    if (is_array($TopicScheduleList) && count($TopicScheduleList) > 0)
                                    {
                                        $Counter = $Start;
                                        foreach ($TopicScheduleList as $TopicScheduleID => $TopicScheduleDetails)
                                        {
?>
                                            <tr>
                                                <td><?php echo ++$Counter; ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($TopicScheduleDetails['StartDate'])); ?></td>
                                                <td><?php echo $TopicScheduleDetails['Class']; ?></td>
                                                <td><?php echo $TopicScheduleDetails['Subject']; ?></td>
                                                <td><?php echo $TopicScheduleDetails['ScheduleType']; ?></td>
                                                <td><?php echo $TopicScheduleDetails['Status']; ?></td>
                                                <td><?php echo (($TopicScheduleDetails['StatusUpdatedOn'] > 0) ? $TopicScheduleDetails['StatusUpdatedOn'] : 'Not Updated'); ?></td>
                                                <td><?php echo (($TopicScheduleDetails['IsActive']) ? 'Yes' : 'No'); ?></td>
                                                <td><?php echo $TopicScheduleDetails['CreateUserName']; ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($TopicScheduleDetails['CreateDate'])); ?></td>
                                                <td class="print-hidden">
<?php
                                                if ($LoggedUser->HasPermissionForTask(TASK_UPDATE_SCHEDULED_LESSON_PLAN) === true)
                                                {
                                                    echo '<a href="update_topic_schedules.php?Process=7&amp;TopicScheduleID=' . $TopicScheduleID . '">Update</a>' ;
                                                }
                                                else
                                                {
                                                    echo 'Update';
                                                }
                                                
                                                if ($LoggedUser->HasPermissionForTask(TASK_UPDATE_SCHEDULED_LESSON_PLAN) === true)
                                                {
                                                    echo '&nbsp|&nbsp;';
                                                    echo '<a href="topic_schedule_report.php?Process=5&amp;TopicScheduleID=' . $TopicScheduleID . '">Delete</a>' ;
                                                }
                                                else
                                                {
                                                    echo 'Delete';
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
                                </div>
                            <!-- /.table-responsive -->
                        </div>
                        <!-- /.panel-body -->
                    </div>
                    <!-- /.panel -->
                </div>
                <!-- /.col-lg-12 -->
            </div>
<?php
        }
?>            
        </div>
        <!-- /#page-wrapper -->

    </div>
    <!-- /#wrapper -->
<?php
require_once('../footer.php');
?>
<script src="/admin/js/print-report.js"></script>
<script src="../vendor/jquery-ui/jquery-ui.min.js"></script>
<script src="../vendor/datatables/js/jquery.dataTables.min.js"></script>
<script src="../vendor/datatables-plugins/dataTables.bootstrap.min.js"></script>
<script src="../vendor/datatables-responsive/dataTables.responsive.js"></script>
<script type="text/javascript">
$(document).ready(function(){

    $(".select-date").datepicker({
        changeMonth: true,
        changeYear: true,
        dateFormat: 'dd/mm/yy'
    });

    $('#DataTableRecords').DataTable({
        responsive: true,
        bPaginate: false,
        bSort: false,
        searching: false, 
        info: false
    });

	$('#Class').change(function(){

        var ClassID = parseInt($('#Class').val());
        $('#ClassSection').html('<option value="0">-- All Section --</option>');
        $('#ClassSubject').html('<option value="0">-- All Subject --</option>');
        
        if (ClassID <= 0)
        {
            $('#ClassSection').html('<option value="0">-- All Section --</option>');
            $('#ClassSubject').html('<option value="0">-- All Subject --</option>');
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
                $('#ClassSection').html('<option value="0">-- All Section --</option>' + ResultArray[1]);
            }
        });
    });

    $('#ClassSection').change(function(){

        var ClassID = parseInt($('#Class').val());
        $('#ClassSubject').html('<option value="0">-- All Subject --</option>');
        $('#Chapter').html('<option value="0">-- All Chapter --</option>');
        
        if (ClassID <= 0)
        {
            $('#ClassSection').html('<option value="0">-- All Section --</option>');
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
                $('#ClassSubject').html('<option value="0">-- All Subject --</option>' + ResultArray[1]);
            }
        });
    });
});

</script>
</body>
</html>
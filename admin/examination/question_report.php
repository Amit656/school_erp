<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once("../../classes/class.ui_helpers.php");

require_once('../../classes/school_administration/class.classes.php');
require_once("../../classes/school_administration/class.branch_staff.php");

require_once("../../classes/academic_supervision/class.chapters.php");
require_once("../../classes/academic_supervision/class.chapter_topics.php");

require_once("../../classes/examination/class.difficulty_levels.php");
require_once("../../classes/examination/class.questions.php");

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
    header('location:/admin/unauthorized_login_admin.php');
    exit;
}
catch (Exception $e)
{
    header('location:/admin/unauthorized_login_admin.php');
    exit;
}
// END OF 1. //

if ($LoggedUser->HasPermissionForTask(TASK_LIST_QUESTION) !== true)
{
    header('location:/admin/unauthorized_login_admin.php');
    exit;
}

$Filters = array();

$TeacherApplicableClasses = array();

if ($LoggedUser->GetRoleID() == ROLE_SITE_FACULTY)
{
    $CurrentBranchStaffClasses = new BranchStaff($LoggedUser->GetUserName());

    $TeacherApplicableClasses = $CurrentBranchStaffClasses->GetTeacherApplicableClasses();
}
else
{
    $TeacherApplicableClasses = AddedClass::GetActiveClasses();
}

$ClassSubjects = array();
$ChaptersList = array();
$ChapterTopics = array(); 
$QuestionsList = array();  

$DifficultyLevelList = array();
$DifficultyLevelList = DifficultyLevel::GetActiveDifficultyLevel();

$RecordDeletedSuccessfully = false;
$HasErrors = false;
$TotalRecords = 0;

$Clean = array();
$Clean['Process'] = 0;

$Clean['ClassID'] = 0;
$Clean['ClassSubjectID'] = 0;
$Clean['ChapterID'] = 0;

$Clean['ChapterTopicID'] = 0;
$Clean['DifficultyLevelID'] = 0;

$Clean['ActiveStatus'] = 0;

// paging and sorting variables start here  //
$Clean['CurrentPage'] = 1;
$TotalPages = 0;

$Start = 0;
$Limit = GLOBAL_SITE_PAGGING;
// end of paging variables//

if (isset($_GET['hdnProcess']))
{
    $Clean['Process'] = (int) $_GET['hdnProcess'];
}
elseif (isset($_GET['Process']))
{
    $Clean['Process'] = (int) $_GET['Process'];
}
switch ($Clean['Process'])
{
    case 5:
         if ($LoggedUser->HasPermissionForTask(TASK_DELETE_QUESTION) !== true)
        {
            header('location:/admin/unauthorized_login_admin.php');
            exit;
        }
        
        if (isset($_GET['QuestionID']))
        {
            $Clean['QuestionID'] = (int) $_GET['QuestionID'];           
        }
        
        if ($Clean['QuestionID'] <= 0)
        {
            header('location:../error_page.php');
            exit;
        }                       
            
        try
        {
            $QuestionToDelete = new Question($Clean['QuestionID']);
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
        
        // if ($QuestionToDelete->CheckDependencies())
        // {
        //     $SearchValidator->AttachTextError('This topic cannot be deleted. There are dependent records for this topic.');
        //     $HasErrors = true;
        //     break;
        // }
                
        if (!$QuestionToDelete->Remove())
        {
            $SearchValidator->AttachTextError(ProcessErrors($QuestionToDelete->GetLastErrorCode()));
            $HasErrors = true;
            break;
        }
        
        $RecordDeletedSuccessfully = true;
    break;

    case 7:        
        if (isset($_GET['drdClassID']))
        {
            $Clean['ClassID'] = (int) $_GET['drdClassID'];
        }
        elseif (isset($_GET['ClassID']))
        {
            $Clean['ClassID'] = (int) $_GET['ClassID'];
        }

        if (isset($_GET['drdClassSubjectID']))
        {
            $Clean['ClassSubjectID'] = (int) $_GET['drdClassSubjectID'];
        }
        elseif (isset($_GET['ClassSubjectID']))
        {
            $Clean['ClassSubjectID'] = (int) $_GET['ClassSubjectID'];
        }

        if (isset($_GET['drdChapterID']))
        {
            $Clean['ChapterID'] = (int) $_GET['drdChapterID'];
        }
        elseif (isset($_GET['ChapterID']))
        {
            $Clean['ChapterID'] = (int) $_GET['ChapterID'];
        }

        if (isset($_GET['drdChapterTopicID']))
        {
            $Clean['ChapterTopicID'] = (int) $_GET['drdChapterTopicID'];
        }
        elseif (isset($_GET['ChapterTopicID']))
        {
            $Clean['ChapterTopicID'] = (int) $_GET['ChapterTopicID'];
        }

        if (isset($_GET['drdDifficultyLevelID']))
        {
            $Clean['DifficultyLevelID'] = (int) $_GET['drdDifficultyLevelID'];
        }
        elseif (isset($_GET['DifficultyLevelID']))
        {
            $Clean['DifficultyLevelID'] = (int) $_GET['DifficultyLevelID'];
        }

        if (isset($_GET['optActiveStatus']))
        {
            $Clean['ActiveStatus'] = (int) $_GET['optActiveStatus'];
        }
        elseif (isset($_GET['ActiveStatus']))
        {
            $Clean['ActiveStatus'] = (int) $_GET['ActiveStatus'];
        }

        $SearchValidator = new Validator();

       if ($Clean['ClassID'] > 0 && $SearchValidator->ValidateInSelect($Clean['ClassID'], $TeacherApplicableClasses, 'Please select a class.'))
        {
            $ClassSubjects = AddedClass::GetClassSubjects($Clean['ClassID']);

            if ($Clean['ClassSubjectID'] > 0 && $SearchValidator->ValidateInSelect($Clean['ClassSubjectID'], $ClassSubjects, 'Please select a subject.'))
            {
                $ChaptersList = Chapter::GetChapterByClassSubject($Clean['ClassSubjectID']);
                
            if ($Clean['ChapterID'] > 0 && $SearchValidator->ValidateInSelect($Clean['ChapterID'], $ChaptersList, 'Please selecta a chapter.'))
                {   
                    $ChapterTopics = ChapterTopic::GetTopicByChapter($Clean['ChapterID']);

                 if ($Clean['ChapterTopicID'] != 0)
                    {
                    $SearchValidator->ValidateInSelect($Clean['ChapterTopicID'], $ChapterTopics, 'Please select a topic.');
                    }
                }
            }
        }

        if ($Clean['DifficultyLevelID'] != 0)
         {
        $SearchValidator->ValidateInSelect($Clean['DifficultyLevelID'], $DifficultyLevelList, 'Please select a difficulty level.');
         }
        
        if ($Clean['ActiveStatus'] < 0 || $Clean['ActiveStatus'] > 2)
        {
            $SearchValidator->AttachTextError('Unknown error, please try again.');
        }
        
        if ($SearchValidator->HasNotifications())
        {
            $HasErrors = true;
            break;
        }

        //set record filters
        $Filters['ClassID'] = $Clean['ClassID'];
        $Filters['ClassSubjectID'] = $Clean['ClassSubjectID'];
        $Filters['ChapterID'] = $Clean['ChapterID'];
        $Filters['ChapterTopicID'] = $Clean['ChapterTopicID'];
        $Filters['DifficultyLevelID'] = $Clean['DifficultyLevelID'];
        $Filters['ActiveStatus'] = $Clean['ActiveStatus'];

        //get records count
        Question::SearchQuestion($TotalRecords, true, $Filters);

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
            $QuestionsList = Question::SearchQuestion($TotalRecords, false, $Filters, $Start, $Limit);
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
<title>Question Report</title>
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
                    <h1 class="page-header">Question Report</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
            <form class="form-horizontal" name="frmQuestionReport" action="question_report.php" method="get">
                <div class="panel panel-default" id="accordion">
                    <div class="panel-heading">
                        <strong><a data-toggle="collapse" data-parent="#accordion" href="#collapseOne">Filters</a></strong>
                    </div>
                    <div id="collapseOne" class="panel-collapse collapse in">
                        <div class="panel-body">
<?php
                            if ($HasErrors == true)
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
                            else if ($LandingPageMode == 'UD')
                            {
                                echo '<div class="alert alert-success">Record updated successfully.</div>';
                            }
?>
                        <div class="form-group">
                               <label for="ClassList" class="col-lg-2 control-label">Select Class</label>
                           <div class="col-lg-4">
                               <select class="form-control"  name="drdClassID" id="Class">
                                   <option  value="0" >-- Select Class --</option>
<?php
                                   foreach ($TeacherApplicableClasses as $ClassID => $ClassName)
                                   {
?>
                                       <option <?php echo ($ClassID == $Clean['ClassID'] ? 'selected="selected"' : ''); ?> value="<?php echo $ClassID; ?>"><?php echo $ClassName;?></option>
<?php
                                   }

?>
                               </select>
                           </div>
                                 <label for="ClassSubjectID" class="col-lg-2 control-label">Select Subject</label>
                           <div class="col-lg-4">
                               <select class="form-control"  name="drdClassSubjectID" id="ClassSubject">
                                   <option  value="0" >-- Select Subject--</option>
<?php
                                   foreach ($ClassSubjects as $ClassSubjectID => $SubjectName)
                                   {
?>
                                       <option <?php echo ($ClassSubjectID == $Clean['ClassSubjectID'] ? 'selected="selected"' : ''); ?> value="<?php echo $ClassSubjectID; ?>"><?php echo $SubjectName;?></option>
<?php
                                   }

?>
                               </select>
                           </div>
                            </div>

                            <div class="form-group">
                                <label for="Chapter" class="col-lg-2 control-label">Select Chapter</label>
                                <div class="col-lg-4">
                                    <select class="form-control" name="drdChapterID" id="Chapter">
                                            <option value="0" >-- All Chapter --</option>
<?php
                                            if (is_array($ChaptersList) && count($ChaptersList) > 0)
                                            {
                                                foreach ($ChaptersList as $ChapterID => $ChapterName) 
                                                {
                                                    echo '<option ' . ($Clean['ChapterID'] == $ChapterID ? 'selected="selected"' : '') . ' value="' . $ChapterID . '">' . $ChapterName . '</option>' ;
                                                }
                                            }
?>
                                    </select>
                                </div>
                                 <label for="ChapterTopicID" class="col-lg-2 control-label">Select Topic</label>
                               <div class="col-lg-4">
                                   <select class="form-control"  name="drdChapterTopicID" id="ChapterTopic">
                                       <option  value="0" >-- Select Topic --</option>
    <?php
                                       foreach ($ChapterTopics as $ChapterTopicID => $ChapterTopicName)
                                       {
    ?>
                                           <option <?php echo ($ChapterTopicID == $Clean['ChapterTopicID'] ? 'selected="selected"' : ''); ?> value="<?php echo $ChapterTopicID; ?>"><?php echo $ChapterTopicName;?></option>
    <?php
                                       }

    ?>
                                   </select>
                               </div>
                            </div>

                        <div class="form-group">
                             <label for="ClassSubjectID" class="col-lg-2 control-label">Select Difficulty Level</label>
                               <div class="col-lg-4">
                                   <select class="form-control" name="drdDifficultyLevelID">
                                       <option  value="0" >-- Select Difficulty Level --</option>
    <?php
                                       foreach ($DifficultyLevelList as $DifficultyLevelID => $DifficultyLevel)
                                       {
    ?>
                                           <option <?php echo ($DifficultyLevelID == $Clean['DifficultyLevelID'] ? 'selected="selected"' : ''); ?> value="<?php echo $DifficultyLevelID; ?>"><?php echo $DifficultyLevel;?></option>
    <?php
                                       }

    ?>
                                   </select>
                               </div>
                       </div>
                            <div class="form-group">
                                <label for="ActiveStatus" class="col-lg-2 control-label">Status</label>
                                <div class="col-lg-6">
                                    <label class="radio-inline">
                                        <input type="radio" <?php echo (($Clean['ActiveStatus'] == 0) ? 'checked="checked"' : ''); ?> name="optActiveStatus" id="ActiveStatus" value="0" checked>All Question
                                    </label>
                                    <label class="radio-inline">
                                        <input type="radio" <?php echo (($Clean['ActiveStatus'] == 1) ? 'checked="checked"' : ''); ?> name="optActiveStatus" id="ActiveStatus" value="1">Active Question
                                    </label>
                                    <label class="radio-inline">
                                        <input type="radio" <?php echo (($Clean['ActiveStatus'] == 2) ? 'checked="checked"' : ''); ?> name="optActiveStatus" id="ActiveStatus" value="2">In-Active Questions
                                    </label>
                                </div>
                            </div>                    
                            <div class="form-group">
                                <div class="col-lg-offset-2 col-lg-10">
                                    <input type="hidden" name="hdnProcess" value="7" />
                                    <button type="submit" class="btn btn-primary"><i class="fa fa-search"></i>&nbsp;Search</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    </div>
            </form>
            <!-- /.row -->
<?php
        if ($Clean['Process'] == 7 && $HasErrors == false)
        {
            $ReportHeaderText = '';

            if ($Clean['ClassID'] != 0)
            {
                $ReportHeaderText .= ' Class: ' . $TeacherApplicableClasses[$Clean['ClassID']] . ',';
            }

            if ($Clean['ClassSubjectID'] != 0)
            {
                $ReportHeaderText .= ' Subject: ' . $ClassSubjects[$Clean['ClassSubjectID']] . ',';
            }

            if ($Clean['ChapterID'] != 0)
            {
                $ReportHeaderText .= ' Chapter: ' . $ChaptersList[$Clean['ChapterID']] . ',';

            }

            if ($Clean['ChapterTopicID'] != 0)
            {
                $ReportHeaderText .= ' ChapterTopic: ' . $ChapterTopics[$Clean['ChapterTopicID']] . ',';
            }

            if ($Clean['DifficultyLevelID'] != 0)
            {
                $ReportHeaderText .= ' DifficultyLevel: ' . $DifficultyLevelList[$Clean['DifficultyLevelID']] . ',';
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
                                        $AllParameters = array('Process' => '7', 'ClassID' => $Clean['ClassID'], 'ClassSubjectID' => $Clean['ClassSubjectID'], 'ChapterID' => $Clean['ChapterID'], 'ChapterTopicID' => $Clean['ChapterTopicID'], 'DifficultyLevelID' => $Clean['DifficultyLevelID'], 'ActiveStatus' => $Clean['ActiveStatus']);
                                        echo UIHelpers::GetPager('question_report.php', $TotalPages, $Clean['CurrentPage'], $AllParameters);
                                    }
?>                                        
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="print-btn-container"><button id="PrintButton" type="submit" class="btn btn-primary">Print</button></div>
                                    </div>
                                </div>
                                <div class="row" id="RecordTableHeading">
                                    <div class="col-lg-12">
                                        <div class="report-heading-container"><strong>Question Report on <?php echo date('d-m-Y h:i A') . $ReportHeaderText; ?></strong></div>
                                    </div>
                                </div>
                                <div class="row" id="RecordTable">
                                    <div class="col-lg-12">
                                        <table width="100%" class="table table-striped table-bordered table-hover" id="DataTableRecords">
                                            <thead>
                                                <tr>
                                                    <th>S.No</th>
                                                    <th>Class Name</th>
                                                    <th>Subject Name</th>
                                                    <th>Chapter Name</th>
                                                    <th>Topic Name</th>
                                                    <th>Difficulty Level</th>
                                                    <th>Qusetions</th>
                                                    <th>Is Active</th>
                                                    <th>Create User</th>
                                                    <th>Create Date</th>
                                                    <th class="print-hidden">Operations</th>
                                                </tr>
                                            </thead>
                                            <tbody>
<?php
                                    if (is_array($QuestionsList) && count($QuestionsList) > 0)
                                    {
                                        $Counter = $Start;
                                        foreach ($QuestionsList as $QuestionID => $QuestionsListDetails)
                                        {
?>
                                                <tr>
                                                    <td><?php echo ++$Counter; ?></td>
                                                    <td><?php echo $QuestionsListDetails['ClassName']; ?></td>
                                                    <td><?php echo $QuestionsListDetails['SubjectName']; ?></td>
                                                    <td><?php echo $QuestionsListDetails['ChapterName']; ?></td>
                                                    <td><?php echo $QuestionsListDetails['TopicName']; ?></td>
                                                    <td><?php echo $QuestionsListDetails['DifficultyLevel']; ?></td>
                                                    <td><?php echo $QuestionsListDetails['Question']; ?></td>
                                                    <td><?php echo (($QuestionsListDetails['IsActive']) ? 'Yes' : 'No'); ?></td>
                                                    <td><?php echo $QuestionsListDetails['CreateUserName']; ?></td>
                                                    <td><?php echo date('d/m/Y', strtotime($QuestionsListDetails['CreateDate'])); ?></td>
                                                    <td class="print-hidden">
<?php
                                                    if ($LoggedUser->HasPermissionForTask(TASK_EDIT_QUESTION) === true)
                                                    {
                                                        echo '<a href="edit_question.php?Process=2&amp;QuestionID=' . $QuestionID . '">Edit</a>';
                                                    }
                                                    else
                                                    {
                                                        echo 'Edit';
                                                    }

                                                    echo '&nbsp;|&nbsp;';

                                                    if ($LoggedUser->HasPermissionForTask(TASK_DELETE_QUESTION) === true)
                                                    {
                                                        echo '<a href="question_report.php?Process=5&amp;QuestionID=' . $QuestionID . '" class="delete-record">Delete</a>';
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
<!-- DataTables JavaScript -->
<script src="../vendor/datatables/js/jquery.dataTables.min.js"></script>
<script src="../vendor/datatables-plugins/dataTables.bootstrap.min.js"></script>
<script src="../vendor/datatables-responsive/dataTables.responsive.js"></script>   
<script type="text/javascript">
$(document).ready(function() {
    $("body").on('click', '.delete-record', function()
    {   
        if (!confirm("Are you sure you want to delete this record?"))
        {
            return false;
        }
    });

    $('#DataTableRecords').DataTable({
        responsive: true,
        bPaginate: false,
        bSort: false,
        searching: false, 
        info: false
    });

    $('#Class').change(function(){
         $('#ClassSubject').html('<option value="" >-- Select Subject --</option>');
		$('#Chapter').html('<option value="" >-- Select Chapter --</option>');
		$('#ChapterTopic').html('<option value="" >-- Select Topic --</option>')

        var ClassID = parseInt($(this).val());

        if (ClassID <= 0)
        {
            $('#ClassSubject').html('<option value="" >-- Select Subject --</option>');
            $('#Chapter').html('<option value="" >-- Select Chapter --</option>');
            return false;
        }

        $.post("/xhttp_calls/get_subjects_by_class.php", {SelectedClassID:ClassID}, function(data)
        {
            ResultArray = data.split("|*****|");

            if (ResultArray[0] == 'error')
            {
                alert (ResultArray[1]);
                $('#ClassSubject').html('<option value="" >-- Select Subject --</option>');
                return false;
            }
            else
            {
                $('#ClassSubject').html('<option value="" >-- Select Subject --</option>' + ResultArray[1]);
            }
        });
    });

    $('#ClassSubject').change(function(){
        
         $('#Chapter').html('<option value="" >-- Select Chapter --</option>');
		$('#ChapterTopic').html('<option value="" >-- Select Topic --</option>');

        var ClassSubjectID = parseInt($(this).val());

        if (ClassSubjectID <= 0)
        {
            return;
        }

        $.post("/xhttp_calls/get_chapter_by_class_subject.php", {SelectedClassSubjectID:ClassSubjectID}, function(data)
        {
            ResultArray = data.split("|*****|");

            if (ResultArray[0] == 'error')
            {
                alert (ResultArray[1]);
                return false;
            }
            else
            {
                $('#Chapter').html('<option value="" >-- Select Chapter --</option>' + ResultArray[1]);
            }
        });
    });

    $('#Chapter').change(function(){

        var ChapterID = parseInt($(this).val());

        if (ChapterID <= 0)
        {
            $('#ChapterTopic').html('<option value="" >-- Select Topic --</option>');
            return;
        }

        $.post("/xhttp_calls/get_chapter_topics.php", {SelectedChapterID:ChapterID}, function(data)
        {
            ResultArray = data.split("|*****|");

            if (ResultArray[0] == 'error')
            {
                alert (ResultArray[1]);
                return false;
            }
            else
            {
                $('#ChapterTopic').html('<option value="" >-- Select Topic --</option>' + ResultArray[1]);
            }
        });
    });
        
});
</script>
<!-- JavaScript To Print A Report -->
<script src="js/print-report.js"></script>
<script src="/admin/js/print-report.js"></script>
</body>
</html>
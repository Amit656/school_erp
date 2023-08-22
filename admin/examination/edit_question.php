<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

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
    header('location:../unauthorized_login_admin.php');
    exit;
}
catch (Exception $e)
{
    header('location:../unauthorized_login_admin.php');
    exit;
}
// END OF 1. //

if ($LoggedUser->HasPermissionForTask(TASK_EDIT_QUESTION) !== true)
{
    header('location:/admin/unauthorized_login_admin.php');
    exit;
}

if (isset($_POST['btnCancel']))
{
    header('location:question_report.php');
    exit;
}

$Clean = array();

$Clean['QuestionID'] = 0;

if (isset($_GET['QuestionID']))
{
    $Clean['QuestionID'] = (int) $_GET['QuestionID'];
}
else if (isset($_POST['hdnQuestionID']))
{
    $Clean['QuestionID'] = (int) $_POST['hdnQuestionID'];
}

if ($Clean['QuestionID'] <= 0)
{
    header('location:../error.php');
    exit;
}   

try
{
    $QuestionToEdit = new Question($Clean['QuestionID']);
}
catch (ApplicationDBException $e)
{
    header('location:../error.php');
    exit;
}
catch (Exception $e)
{
    header('location:../error.php');
    exit;
}

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

$DifficultyLevelList = array();
$DifficultyLevelList = DifficultyLevel::GetActiveDifficultyLevel();

$HasErrors = false;

$Clean['Process'] = 0;

$Clean['ClassID'] = 0;
$Clean['ClassSubjectID'] = 0;
$Clean['ChapterID'] = 0;

$Clean['ChapterTopicID'] = 0;
$Clean['DifficultyLevelID'] = 0;

$Clean['Question'] = '';
$Clean['IsActive'] = 0;

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
    case 3:     
        if (isset($_POST['drdClassID']))
        {
            $Clean['ClassID'] = (int) $_POST['drdClassID'];
        }
        if (isset($_POST['drdClassSubjectID']))
        {
            $Clean['ClassSubjectID'] = (int) $_POST['drdClassSubjectID'];
        }
        if (isset($_POST['drdChapterID']))
        {
            $Clean['ChapterID'] = (int) $_POST['drdChapterID'];
        }
        if (isset($_POST['drdChapterTopicID']))
        {
            $Clean['ChapterTopicID'] = (int) $_POST['drdChapterTopicID'];
        }
        if (isset($_POST['drdDifficultyLevelID']))
        {
            $Clean['DifficultyLevelID'] = (int) $_POST['drdDifficultyLevelID'];
        }
        if (isset($_POST['txtQuestion']))
        {
            $Clean['Question'] = $_POST['txtQuestion'];
        }

        if (isset($_POST['chkIsActive']))
        {
            $Clean['IsActive'] = 1;
        }

        $NewRecordValidator = new Validator();

       if ($NewRecordValidator->ValidateInSelect($Clean['ClassID'], $TeacherApplicableClasses, 'Unknown error, please try again.'))
        {
            $ClassSubjects = AddedClass::GetClassSubjects($Clean['ClassID']);

            if ($NewRecordValidator->ValidateInSelect($Clean['ClassSubjectID'], $ClassSubjects, 'Unknown error, please try again.'))
            {
                $ChaptersList = Chapter::GetChapterByClassSubject($Clean['ClassSubjectID']);
                
                if ($NewRecordValidator->ValidateInSelect($Clean['ChapterID'], $ChaptersList, 'Unknown error, please try again.'))
                {   
                    $ChapterTopics = ChapterTopic::GetTopicByChapter($Clean['ChapterID']);

                    $NewRecordValidator->ValidateInSelect($Clean['ChapterTopicID'], $ChapterTopics, 'Unknown error, please try again.');
                }
            }
        }

        $NewRecordValidator->ValidateInSelect($Clean['DifficultyLevelID'], $DifficultyLevelList, 'Please select a difficulty level.');
        
        $NewRecordValidator->ValidateStrings($Clean['Question'], 'Please enter a valid question.', 1, 2000);
        
        if ($NewRecordValidator->HasNotifications())
        {
            $HasErrors = true;
            break;
        }
                
        $QuestionToEdit->SetChapterTopicID($Clean['ChapterTopicID']);
        $QuestionToEdit->SetDifficultyLevelID($Clean['DifficultyLevelID']);
        $QuestionToEdit->SetQuestion($Clean['Question']);
        $QuestionToEdit->SetIsActive($Clean['IsActive']);

        if (!$QuestionToEdit->Save())
        {
            $NewRecordValidator->AttachTextError(ProcessErrors($QuestionToEdit->GetLastErrorCode()));
            $HasErrors = true;
            break;
        }
        
        header('location:question_report.php?Mode=UD');
        exit;
    break;

    case 2:
        $Clean['ChapterTopicID'] = $QuestionToEdit->GetChapterTopicID();
        try
        {
            $ChapterTopicObject = new ChapterTopic($Clean['ChapterTopicID']);    
        }
        catch (ApplicationDBException $e)
        {
            header('location:../error.php');
            exit;
        }
        catch (Exception $e)
        {
            header('location:../error.php');
            exit;
        }

        $Clean['ChapterID'] = $ChapterTopicObject->GetChapterID();

        $ClassSubjectObject = new Chapter($Clean['ChapterID']); 

        $Clean['ClassSubjectID'] = $ClassSubjectObject->GetClassSubjectID();

        $ClassSubjectDetails = AddedClass::GetClassIDAndSubjectID($Clean['ClassSubjectID']);

        $Clean['ClassID'] = $ClassSubjectDetails[$Clean['ClassSubjectID']]['ClassID'];

        $SelectedAddedClass = New AddedClass($Clean['ClassID']);

        $ClassSubjects = $SelectedAddedClass->GetClassSubjects($Clean['ClassID']);

        $ChaptersList = Chapter::GetChapterByClassSubject($Clean['ClassSubjectID']);

        $ChapterTopics = ChapterTopic::GetTopicByChapter($Clean['ChapterID']);

        $Clean['DifficultyLevelID'] = $QuestionToEdit->GetDifficultyLevelID();

        $Clean['Question'] = $QuestionToEdit->GetQuestion();

        $Clean['IsActive'] = $QuestionToEdit->GetIsActive();

    break;
}

require_once('../html_header.php');
?>
<title>Edit Question</title>
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
                    <h1 class="page-header">Edit Question</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="EditChapter" action="edit_question.php" method="post">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        Enter Question Details
                    </div>
                    <div class="panel-body">
<?php
                        if ($HasErrors == true)
                        {
                            echo $NewRecordValidator->DisplayErrors();
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
                                   <option  value="0" >-- Select Subject --</option>
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
                            <label for="ClassChapterID" class="col-lg-2 control-label">Select Chapter</label>
                           <div class="col-lg-4">
                               <select class="form-control"  name="drdChapterID" id="Chapter">
                                   <option  value="0">-- Select Chapter --</option>
<?php
                                   foreach ($ChaptersList as $ChapterID => $ChapterName)
                                   {
?>
                                       <option <?php echo ($ChapterID == $Clean['ChapterID'] ? 'selected="selected"' : ''); ?> value="<?php echo $ChapterID; ?>"><?php echo $ChapterName;?></option>
<?php
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
                            <label for="IsActive" class="col-lg-2 control-label">Is Active</label>
                            <div class="col-lg-4">
                                <input type="checkbox" id="IsActive" name="chkIsActive" <?php echo ($Clean['IsActive'] == 1) ? 'checked="checked"' : ''; ?> value="1" />
                            </div>
                        </div>

                      <div class="panel panel-default">
                        <div class="panel-heading">
                            Enter Question
                        </div>
                        
                        <div class="panel-body">
                            <div class="form-group">
                                <div class="col-lg-12">
                                    <textarea name="txtQuestion" id="QuestionEditer" class="form-control"><?php echo $Clean['Question']; ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                        <div class="form-group">
                        <div class="col-sm-offset-2 col-lg-10">
                            <input type="hidden" name="hdnProcess" value="3"/>
                            <input type="hidden" name="hdnQuestionID" value="<?php echo $Clean['QuestionID']; ?>" />
                            <button type="submit" class="btn btn-primary"><i class="fa fa-upload"></i>&nbsp;Update</button>
                            <button type="submit" class="btn btn-primary" name="btnCancel">Cancel</button>
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
?>
   <script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/4.5.6/tinymce.min.js"></script>

    <script src="../vendor/jquery-ui/jquery-ui.min.js"></script>

<script src="http://cdnjs.cloudflare.com/ajax/libs/tinymce/4.5.6/jquery.tinymce.min.js"></script>
   <script type="text/javascript">
$(document).ready(function(){
	tinymce.init({
		selector: 'textarea#QuestionEditer', 
		width: "500",
		height: "200"
	});

	$('#Class').change(function(){
	    
	     $('#ClassSubject').html('<option value="" >-- Select Subject --</option>');
		$('#Chapter').html('<option value="" >-- Select Chapter --</option>');
		$('#ChapterTopic').html('<option value="" >-- Select Topic --</option>');

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
</body>
</html>
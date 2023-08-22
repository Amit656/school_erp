<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once("../../classes/school_administration/class.classes.php");

require_once("../../classes/academic_supervision/class.chapters.php");
require_once("../../classes/academic_supervision/class.chapter_topics.php");

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

if ($LoggedUser->HasPermissionForTask(TASK_EDIT_CHAPTER_TOPIC) !== true)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}

if (isset($_POST['btnCancel']))
{
    header('location:chapter_topics_report.php');
    exit;
}

$Clean = array();

$Clean['ChapterTopicID'] = 0;

if (isset($_GET['ChapterTopicID']))
{
    $Clean['ChapterTopicID'] = (int) $_GET['ChapterTopicID'];
}
else if (isset($_POST['hdnChapterTopicID']))
{
    $Clean['ChapterTopicID'] = (int) $_POST['hdnChapterTopicID'];
}

if ($Clean['ChapterTopicID'] <= 0)
{
    header('location:../error.php');
    exit;
}   

try
{
    $ChapterTopicToEdit = new ChapterTopic($Clean['ChapterTopicID']);
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

$ClassList =  array();
$ClassList = AddedClass::GetActiveClasses();

$ClassSubjectsList = array();
$ChaptersList = array();

$HasErrors = false;

$Clean['Process'] = 0;

$Clean['ClassID'] = 0;
$Clean['ClassSubjectID'] = 0;
$Clean['ChapterID'] = 0;

$Clean['TopicName'] = '';
$Clean['ExpectedClasses'] = 0;

$Clean['Priority'] = 0;
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
        if (isset($_POST['drdClass']))
        {
            $Clean['ClassID'] = (int) $_POST['drdClass'];
        }
        if (isset($_POST['drdClassSubject']))
        {
            $Clean['ClassSubjectID'] = (int) $_POST['drdClassSubject'];
        }
        if (isset($_POST['drdChapter']))
        {
            $Clean['ChapterID'] = (int) $_POST['drdChapter'];
        }
        if (isset($_POST['txtTopicName']))
        {
            $Clean['TopicName'] = strip_tags(trim($_POST['txtTopicName']));
        }
        if (isset($_POST['txtExpectedClasses']))
        {
            $Clean['ExpectedClasses'] = (int) $_POST['txtExpectedClasses'];
        }
        if (isset($_POST['txtPriority']))
        {
            $Clean['Priority'] = (int) $_POST['txtPriority'];
        }

        if (isset($_POST['chkIsActive']))
        {
            $Clean['IsActive'] = 1;
        }

        $NewRecordValidator = new Validator();

        if ($NewRecordValidator->ValidateInSelect($Clean['ClassID'], $ClassList, 'Unknown error, please try again.')) 
        {
            $SelectedAddedClass = New AddedClass($Clean['ClassID']);
            $SelectedAddedClass->FillAssignedSubjects();

            $ClassSubjectsList = $SelectedAddedClass->GetAssignedSubjects();

            if ($NewRecordValidator->ValidateInSelect($Clean['ClassSubjectID'], $ClassSubjectsList, 'Unknown error, please try again.')) 
            {
                $ChaptersList = Chapter::GetChapterByClassSubject($Clean['ClassSubjectID']);

                $NewRecordValidator->ValidateInSelect($Clean['ChapterID'], $ChaptersList, 'Unknown error, please try again.');
            }
        }

        $NewRecordValidator->ValidateStrings($Clean['TopicName'], 'Topic name is required and should be between 3 and 100 characters.', 3, 100);
        $NewRecordValidator->ValidateInteger($Clean['ExpectedClasses'], 'Please enter numeric value for expected classes.', 1);
        
        $NewRecordValidator->ValidateInteger($Clean['Priority'], 'Please enter valid priority.', 1);
        
        if ($NewRecordValidator->HasNotifications())
        {
            $HasErrors = true;
            break;
        }
                
        $ChapterTopicToEdit->SetChapterID($Clean['ChapterID']);
        $ChapterTopicToEdit->SetTopicName($Clean['TopicName']);
        $ChapterTopicToEdit->SetExpectedClasses($Clean['ExpectedClasses']);

        $ChapterTopicToEdit->SetPriority($Clean['Priority']);
        $ChapterTopicToEdit->SetIsActive($Clean['IsActive']);

        if (!$ChapterTopicToEdit->Save())
        {
            $NewRecordValidator->AttachTextError(ProcessErrors($ChapterTopicToEdit->GetLastErrorCode()));
            $HasErrors = true;

            break;
        }
        
        header('location:chapter_topics_report.php?Mode=UD');
        exit;
    break;

    case 2:
        $Clean['ChapterID'] = $ChapterTopicToEdit->GetChapterID();
        try
        {
            $ChapterObject = new Chapter($Clean['ChapterID']);    
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

        $Clean['ClassSubjectID'] = $ChapterObject->GetClassSubjectID();

        $ClassSubjectDetails = AddedClass::GetClassIDAndSubjectID($Clean['ClassSubjectID']);

        $Clean['ClassID'] = $ClassSubjectDetails[$Clean['ClassSubjectID']]['ClassID'];

        $SelectedAddedClass = New AddedClass($Clean['ClassID']);
        $SelectedAddedClass->FillAssignedSubjects();

        $ClassSubjectsList = $SelectedAddedClass->GetAssignedSubjects();

        $ChaptersList = Chapter::GetChapterByClassSubject($Clean['ClassSubjectID']);

        $Clean['TopicName'] = $ChapterTopicToEdit->GetTopicName();
        $Clean['ExpectedClasses'] = $ChapterTopicToEdit->GetExpectedClasses();

        $Clean['Priority'] = $ChapterTopicToEdit->GetPriority();
        $Clean['IsActive'] = $ChapterTopicToEdit->GetIsActive();

    break;
}

require_once('../html_header.php');
?>
<title>Edit Chapter Topic</title>
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
                    <h1 class="page-header">Edit Chapter Topic</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="EditChapter" action="edit_chapter_topic.php" method="post">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        Enter Chapter Details
                    </div>
                    <div class="panel-body">
<?php
                        if ($HasErrors == true)
                        {
                            echo $NewRecordValidator->DisplayErrors();
                        }
?>                    
                        <div class="form-group">
                            <label for="Class" class="col-lg-2 control-label">Class</label>
                            <div class="col-lg-4">
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
                        </div>
                        <div class="form-group">
                            <label for="ClassSubject" class="col-lg-2 control-label">Subject</label>
                            <div class="col-lg-4">
                                <select class="form-control" name="drdClassSubject" id="ClassSubject">
                                        <option  value="0" >-- Select Subject --</option>
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
                        </div>
                        <div class="form-group">
                            <label for="Chapter" class="col-lg-2 control-label">Chapter</label>
                            <div class="col-lg-4">
                                <select class="form-control" name="drdChapter" id="Chapter">
                                        <option  value="0" >-- Select Chapter --</option>
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
                        </div>
                        <div class="form-group">
                            <label for="TopicName" class="col-lg-2 control-label">Topic Name</label>
                            <div class="col-lg-4">
                                <input class="form-control" type="text" maxlength="100" id="TopicName" name="txtTopicName" value="<?php echo $Clean['TopicName']; ?>" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="ExpectedClasses" class="col-lg-2 control-label">Expected Classes</label>
                            <div class="col-lg-4">
                                <input class="form-control" type="text" maxlength="5" id="ExpectedClasses" name="txtExpectedClasses" value="<?php echo ($Clean['ExpectedClasses'] ? $Clean['ExpectedClasses'] : ''); ?>" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="Priority" class="col-lg-2 control-label">Priority</label>
                            <div class="col-lg-4">
                                <input class="form-control" type="text" maxlength="5" id="Priority" name="txtPriority" value="<?php echo ($Clean['Priority'] ? $Clean['Priority'] : ''); ?>" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="IsActive" class="col-lg-2 control-label">Is Active</label>
                            <div class="col-lg-4">
                                <input type="checkbox" id="IsActive" name="chkIsActive" <?php echo ($Clean['IsActive'] == 1) ? 'checked="checked"' : ''; ?> value="1" />
                            </div>
                        </div>
                        <div class="form-group">
                        <div class="col-sm-offset-2 col-lg-10">
                            <input type="hidden" name="hdnProcess" value="3"/>
                            <input type="hidden" name="hdnChapterTopicID" value="<?php echo $Clean['ChapterTopicID']; ?>" />
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
</body>
</html>
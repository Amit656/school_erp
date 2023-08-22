<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once("../../classes/school_administration/class.academic_years.php");
require_once("../../classes/school_administration/class.classes.php");

require_once("../../classes/academic_supervision/class.chapters.php");

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

if ($LoggedUser->HasPermissionForTask(TASK_EDIT_CHAPTER) !== true)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}

if (isset($_POST['btnCancel']))
{
    header('location:chapters_report.php');
    exit;
}

$Clean = array();

$Clean['ChapterID'] = 0;

if (isset($_GET['ChapterID']))
{
    $Clean['ChapterID'] = (int) $_GET['ChapterID'];
}
else if (isset($_POST['hdnChapterID']))
{
    $Clean['ChapterID'] = (int) $_POST['hdnChapterID'];
}

if ($Clean['ChapterID'] <= 0)
{
    header('location:../error.php');
    exit;
}   

try
{
    $ChapterToEdit = new Chapter($Clean['ChapterID']);
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

$AcademicYearID = AcademicYear::GetCurrentAcademicYear();

$ClassList =  array();
$ClassList = AddedClass::GetActiveClasses();

$ClassSubjectsList = array();

$HasErrors = false;

$Clean['Process'] = 0;

$Clean['ClassID'] = 0;
$Clean['AcademicYearID'] = 0;
$Clean['ClassSubjectID'] = 0;

$Clean['ChapterName'] = '';

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
        if (isset($_POST['txtChapterName']))
        {
            $Clean['ChapterName'] = strip_tags(trim($_POST['txtChapterName']));
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

            $NewRecordValidator->ValidateInSelect($Clean['ClassSubjectID'], $ClassSubjectsList, 'Unknown error, please try again.');

        }
        $NewRecordValidator->ValidateStrings($Clean['ChapterName'], 'Chapter name is required and should be between 3 and 150 characters.', 3, 150);
        $NewRecordValidator->ValidateInteger($Clean['Priority'], 'Please enter valid priority.', 1);
        
        if ($NewRecordValidator->HasNotifications())
        {
            $HasErrors = true;
            break;
        }
        
        $ChapterToEdit->SetAcademicYearID($AcademicYearID);

        $ChapterToEdit->SetClassSubjectID($Clean['ClassSubjectID']);
        $ChapterToEdit->SetChapterName($Clean['ChapterName']);

        $ChapterToEdit->SetPriority($Clean['Priority']);
        $ChapterToEdit->SetIsActive($Clean['IsActive']);

        if (!$ChapterToEdit->Save())
        {
            $NewRecordValidator->AttachTextError(ProcessErrors($ChapterToEdit->GetLastErrorCode()));
            $HasErrors = true;

            break;
        }
        
        header('location:chapters_report.php?Mode=UD');
        exit;
    break;

    case 2:
        $AcademicYearID = $ChapterToEdit->GetAcademicYearID();

        $Clean['ClassSubjectID'] = $ChapterToEdit->GetClassSubjectID();

        $ClassSubjectDetails = AddedClass::GetClassIDAndSubjectID($Clean['ClassSubjectID']);

        $Clean['ClassID'] = $ClassSubjectDetails[$Clean['ClassSubjectID']]['ClassID'];

        $SelectedAddedClass = New AddedClass($Clean['ClassID']);
        $SelectedAddedClass->FillAssignedSubjects();

        $ClassSubjectsList = $SelectedAddedClass->GetAssignedSubjects();

        $Clean['ChapterName'] = $ChapterToEdit->GetChapterName();
        
        $Clean['Priority'] = $ChapterToEdit->GetPriority();
        $Clean['IsActive'] = $ChapterToEdit->GetIsActive();

    break;
}

require_once('../html_header.php');
?>
<title>Edit Chapter</title>
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
                    <h1 class="page-header">Edit Chapter</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="EditChapter" action="edit_chapter.php" method="post">
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
                            <label for="ClassSubject" class="col-lg-1 control-label">Subject</label>
                            <div class="col-lg-3">
                                <select class="form-control" name="drdClassSubject" id="ClassSubject">
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
                            <label for="ChapterName" class="col-lg-2 control-label">Chapter Name</label>
                            <div class="col-lg-7">
                                <input class="form-control" type="text" maxlength="150" id="ChapterName" name="txtChapterName" value="<?php echo $Clean['ChapterName']; ?>" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="Priority" class="col-lg-2 control-label">Priority</label>
                            <div class="col-lg-3">
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
                            <input type="hidden" name="hdnChapterID" value="<?php echo $Clean['ChapterID']; ?>" />
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
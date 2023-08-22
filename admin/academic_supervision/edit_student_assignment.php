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

if ($LoggedUser->HasPermissionForTask(TASK_EDIT_STUDENT_ASSIGNMENT) !== true)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}

$acceptable_extensions = array('jpeg', 'jpg', 'png', 'gif');

$acceptable_mime_types = array(
    'image/jpeg',
    'image/jpg', 
    'image/png', 
    'image/gif' 
);

if (isset($_POST['btnCancel']))
{
    header('location:student_assignment_report.php');
    exit;
}

$Clean = array();

$Clean['AssignmentID'] = 0;

if (isset($_GET['AssignmentID']))
{
    $Clean['AssignmentID'] = (int) $_GET['AssignmentID'];
}
else if (isset($_POST['hdnAssignmentID']))
{
    $Clean['AssignmentID'] = (int) $_POST['hdnAssignmentID'];
}

if ($Clean['AssignmentID'] <= 0)
{
    header('location:../error.php');
    exit;
}   

try
{
    $StudentAssignmentToEdit = new StudentAssignment($Clean['AssignmentID']);
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

$AllAssignmentImages = array();

$ClassList =  array();
$ClassList = AddedClass::GetActiveClasses();

$AllSectionMasters = array();
$AllSectionMasters = SectionMaster::GetActiveSectionMasters();

$ClassSectionsList =  array();

$ClassSubjectsList = array();
$ChaptersList = array();

$HasErrors = false;

$Clean['Process'] = 0;

$Clean['ClassID'] = 0;
$Clean['ClassSectionID'] = 0;
$Clean['ClassSubjectID'] = 0;

$Clean['ChapterID'] = 0;
$Clean['ChapterTopicID'] = 0;

$Clean['AssignmentHeading'] = '';
$Clean['Assignment'] = '';

$Clean['IssueDate'] = '';
$Clean['EndDate'] = '';

$Clean['IsDraft'] = 0;
$Clean['IsActive'] = 0;

$Clean['ChapterTopicName'] = '';

$Clean['UploadFile'] = array();

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
        if (isset($_POST['drdClassSection'])) 
        {
            $Clean['ClassSectionID'] = (int) $_POST['drdClassSection'];
        }
        if (isset($_POST['drdClassSubject'])) 
        {
            $Clean['ClassSubjectID'] = (int) $_POST['drdClassSubject'];
        }
        if (isset($_POST['drdChapter'])) 
        {
            $Clean['ChapterID'] = (int) $_POST['drdChapter'];
        }

        if (isset($_POST['txtChapterTopicName']))
        {
            $Clean['ChapterTopicName'] = strip_tags(trim($_POST['txtChapterTopicName']));
        }

        if (isset($_POST['txtAssignmentHeading']))
        {
            $Clean['AssignmentHeading'] = strip_tags(trim($_POST['txtAssignmentHeading']));
        }
        if (isset($_POST['txtAssignment']))
        {
            $Clean['Assignment'] = strip_tags(trim($_POST['txtAssignment']));
        }

        if (isset($_POST['txtIssueDate']))
        {
            $Clean['IssueDate'] = strip_tags(trim($_POST['txtIssueDate']));
        }
        if (isset($_POST['txtEndDate']))
        {
            $Clean['EndDate'] = strip_tags(trim($_POST['txtEndDate']));
        }

        if (isset($_POST['hdnIsDraft']) && $_POST['hdnIsDraft'] == 1) 
        {
            $Clean['IsDraft'] = 1;
        }

        if (isset($_POST['chkIsActive']))
        {
            $Clean['IsActive'] = 1;
        }

        if (isset($_FILES['fleAssignmentImage']) && is_array($_FILES['fleAssignmentImage']))
        {
            $Clean['UploadFile'] = $_FILES['fleAssignmentImage'];
        }

        $NewRecordValidator = new Validator();

        if ($NewRecordValidator->ValidateInSelect($Clean['ClassID'], $ClassList, 'Please select a valid class.')) 
        {
            $ClassSectionsList = ClassSections::GetClassSections($Clean['ClassID']);

            $NewRecordValidator->ValidateInSelect($Clean['ClassSectionID'], $ClassSectionsList, 'Please select a valid section.');

            $SelectedAddedClass = new AddedClass($Clean['ClassID']);
            $SelectedAddedClass->FillAssignedSubjects();

            $ClassSubjectsList = $SelectedAddedClass->GetAssignedSubjects();
            
            if ($Clean['ClassSubjectID'] > 0)
            {
                if ($NewRecordValidator->ValidateInSelect($Clean['ClassSubjectID'], $ClassSubjectsList, 'Please select a valid subject.')) 
                {
                    $ChaptersList = Chapter::GetChapterByClassSubject($Clean['ClassSubjectID']);
                    
                    if ($Clean['ChapterID'] > 0)
                    {
                        $NewRecordValidator->ValidateInSelect($Clean['ChapterID'], $ChaptersList, 'Unknown Error, Please try again.');      
                    }
                }   
            }
        }

        $Filters['ChapterTopicName'] = $Clean['ChapterTopicName'];
        $Filters['ChapterID'] = $Clean['ChapterID'];
        $Filters['ActiveStatus'] = 1;
        
        $ChapterTopicList = ChapterTopic::SearchChapterTopics($TotalRecords, false, $Filters, 0, 1);

        if (count($ChapterTopicList) > 0) 
        {
            $Clean['ChapterTopicID'] = key($ChapterTopicList);
        }

        $NewRecordValidator->ValidateStrings($Clean['AssignmentHeading'], 'Assignment heading is required and should be between 1 and 150 characters.', 1, 150);
        // $NewRecordValidator->ValidateStrings($Clean['Assignment'], 'Assignment is required and should be between 5 and 2000 characters.', 5, 2000);
        
        $NewRecordValidator->ValidateDate($Clean['IssueDate'], "Please enter a valid issue date.");
        $NewRecordValidator->ValidateDate($Clean['EndDate'], "Please enter a valid end date.");

        if (strtotime(DateProcessing::ToggleDateDayAndMonth($Clean['IssueDate'])) > strtotime(DateProcessing::ToggleDateDayAndMonth($Clean['EndDate'])))
        {
           $NewRecordValidator->AttachTextError('Issue date should be less than end date.');
        }

        $FileName = '';
        $FileExtension = '';

        if ($Clean['UploadFile']['error'] != 4) 
        {
            if ($Clean['UploadFile']['size'] > MAX_UPLOADED_FILE_SIZE || $Clean['UploadFile']['size'] <= 0) 
            {
                $NewRecordValidator->AttachTextError('File size cannot be greater than ' . (MAX_UPLOADED_FILE_SIZE / 1024 /1024) . ' MB.');
            }

            $FileExtension = strtolower(pathinfo($Clean['UploadFile']['name'], PATHINFO_EXTENSION));

            if ($FileExtension != 'pdf')
            {
                if (!in_array($Clean['UploadFile']['type'], $acceptable_mime_types) || !in_array($FileExtension, $acceptable_extensions))
                {
                   $NewRecordValidator->AttachTextError('Only ' . implode(', ', $acceptable_extensions) . ' files are allowed.');
                }   
            }

            if (strlen($Clean['UploadFile']['name']) > MAX_UPLOADED_FILE_NAME_LENGTH)
            {
                // $NewRecordValidator->AttachTextError('Uploaded file name cannot be greater than ' . MAX_UPLOADED_FILE_NAME_LENGTH . ' chars.');
            }

            $FileName = $Clean['UploadFile']['name'];
        }

        if ($NewRecordValidator->HasNotifications())
        {
            $HasErrors = true;
            break;
        }
                
        $StudentAssignmentToEdit->SetClassSectionID($Clean['ClassSectionID']);
        $StudentAssignmentToEdit->SetChapterTopicID($Clean['ChapterTopicID']);

        if ($Clean['ChapterTopicID'] <= 0) 
        {
            $StudentAssignmentToEdit->SetChapterTopicName($Clean['ChapterTopicName']);
            $StudentAssignmentToEdit->SetChapterID($Clean['ChapterID']);
        }

        $StudentAssignmentToEdit->SetAssignmentHeading($Clean['AssignmentHeading']);
        $StudentAssignmentToEdit->SetAssignment($Clean['Assignment']);

        $StudentAssignmentToEdit->SetIssueDate(date('Y-m-d', strtotime(DateProcessing::ToggleDateDayAndMonth(($Clean['IssueDate'])))));
        $StudentAssignmentToEdit->SetEndDate(date('Y-m-d', strtotime(DateProcessing::ToggleDateDayAndMonth(($Clean['EndDate'])))));

        $StudentAssignmentToEdit->SetIsDraft($Clean['IsDraft']);
        $StudentAssignmentToEdit->SetIsActive($Clean['IsActive']);

        if (!$StudentAssignmentToEdit->Save())
        {
            $NewRecordValidator->AttachTextError(ProcessErrors($StudentAssignmentToEdit->GetLastErrorCode()));
            $HasErrors = true;

            break;
        }

        if ($FileName != '') 
        {
            if (!is_dir(SITE_FS_PATH . '/site_images/student_assignment'))
            {
                mkdir(SITE_FS_PATH . '/site_images/student_assignment');
            }

            $UniqueUserFileUploadDirectory = SITE_FS_PATH . '/site_images/student_assignment/' . $StudentAssignmentToEdit->GetAssignmentID().'/';

            if (!is_dir($UniqueUserFileUploadDirectory))
            {
                mkdir($UniqueUserFileUploadDirectory);
            }

            // variable for to get last inserted id
            $AssignmentImageID = 0;

            //insert image name into to the table
            $StudentAssignmentToEdit->SaveAssignmentImage($FileName, $AssignmentImageID);
            // Generate a Unique Name for the uploaded document
            $FileName = md5(uniqid(rand(), true) . $AssignmentImageID) . '.' . $FileExtension;
            
            //updating unique image name into to the table
            $StudentAssignmentToEdit->SaveAssignmentImage($FileName, $AssignmentImageID);

            move_uploaded_file($Clean['UploadFile']['tmp_name'], $UniqueUserFileUploadDirectory . $FileName);
        }

        header('location:student_assignment_report.php?Mode=UD');
        exit;
    break;

    case 2:
        $Clean['ClassSectionID'] = $StudentAssignmentToEdit->GetClassSectionID();
        $Clean['ChapterTopicID'] = $StudentAssignmentToEdit->GetChapterTopicID();

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

        $Clean['ChapterTopicName'] = $ChapterTopicObject->GetTopicName();
        $Clean['ChapterID'] = $ChapterTopicObject->GetChapterID();

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
        
        $ClassSectionObj = New ClassSections($Clean['ClassSectionID']);
        
        $Clean['ClassID'] = $ClassSectionObj->GetClassID();

        $ClassSectionsList = ClassSections::GetClassSections($Clean['ClassID']);

        $SelectedAddedClass = New AddedClass($Clean['ClassID']);
        $SelectedAddedClass->FillAssignedSubjects();

        $ClassSubjectsList = $SelectedAddedClass->GetAssignedSubjects();

        $ChaptersList = Chapter::GetChapterByClassSubject($Clean['ClassSubjectID']);

        $Clean['AssignmentHeading'] = $StudentAssignmentToEdit->GetAssignmentHeading();
        $Clean['Assignment'] = $StudentAssignmentToEdit->GetAssignment();

        $Clean['IssueDate'] = date('d/m/Y', strtotime($StudentAssignmentToEdit->GetIssueDate()));
        $Clean['EndDate'] = date('d/m/Y', strtotime($StudentAssignmentToEdit->GetEndDate()));

        $Clean['IsDraft'] = $StudentAssignmentToEdit->GetIsDraft();
        $Clean['IsActive'] = $StudentAssignmentToEdit->GetIsActive();

        $AllAssignmentImages = $StudentAssignmentToEdit->GetAllAssignmentImages();            
    break;

    case 5:
        $AssignmentImageID = 0;

        if (isset($_GET['AssignmentImageID']))
        {
            $AssignmentImageID = (int) $_GET['AssignmentImageID'];
        }

        if ($AssignmentImageID <= 0)
        {
            header('location:../error.php');
            exit;
        }

        $AllAssignmentImages = $StudentAssignmentToEdit->GetAllAssignmentImages();

        foreach ($AllAssignmentImages as $Key => $ImageDetails)
        {   
            if ($AssignmentImageID == $Key) 
            {
                if (file_exists(SITE_FS_PATH . '/site_images/student_assignment/' . $Clean['AssignmentID'] . '/' . $ImageDetails['ImageName'])) 
                {   
                    unlink(SITE_FS_PATH . '/site_images/student_assignment/' . $Clean['AssignmentID'] . '/' . $ImageDetails['ImageName']);
                }
            }
        }

        $StudentAssignmentToEdit->RemoveAssignmentImage($AssignmentImageID);
        header('location:student_assignment_report.php?Mode=DD');
        exit;
    break;
}

require_once('../html_header.php');
?>
<title>Edit Student Assignment</title>
<link href="../vendor/jquery-ui/jquery-ui.min.css" rel="stylesheet">
<link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.5.2/jquery.fancybox.min.css">
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
                    <h1 class="page-header">Edit Student Assignment</h1>
                </div> 
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="EditChapter" action="edit_student_assignment.php" method="post" enctype="multipart/form-data">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        Enter Assignment Details
                    </div>
                    <div class="panel-body">
<?php
                        if ($HasErrors == true)
                        {
                            echo $NewRecordValidator->DisplayErrors();
                        }
?>                    
                        <div class="form-group">
                            <label for="Class" class="col-lg-2 control-label">Class List</label>
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
                            <label for="Chapter" class="col-lg-2 control-label">Chapter</label>
                            <div class="col-lg-3">
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
                            <label for="ChapterTopic" class="col-lg-2 control-label">Topic</label>
                            <div class="col-lg-8">
                                <input class="form-control" list="ChapterTopicList" maxlength="100" id="ChapterTopicName" name="txtChapterTopicName" placeholder="Start typing...." value="<?php echo ($Clean['ChapterTopicName']); ?>" />
                                <datalist id="ChapterTopicList">
                                </datalist>
                                <input type="hidden" name="txtChapterTopicID" id="ChapterTopicID" value="<?php echo $Clean['ChapterTopicID'] ? $Clean['ChapterTopicID'] : '' ; ?>">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="AssignmentHeading" class="col-lg-2 control-label">Assignment Heading</label>
                            <div class="col-lg-8">
                                <input class="form-control" type="text" id="AssignmentHeading" name="txtAssignmentHeading" value="<?php echo $Clean['AssignmentHeading']; ?>" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="Assignment" class="col-lg-2 control-label">Assignment</label>
                            <div class="col-lg-8">
                                <textarea class="form-control"  id="Assignment" name="txtAssignment"><?php echo $Clean['Assignment']; ?></textarea>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="IssueDate" class="col-lg-2 control-label">IssueDate</label>
                            <div class="col-lg-3">
                                <input class="form-control select-date" type="text" maxlength="10" id="IssueDate" name="txtIssueDate" value="<?php echo $Clean['IssueDate']; ?>" />
                            </div>
                            <label for="EndDate" class="col-lg-2 control-label">End Date</label>
                            <div class="col-lg-3">
                                <input class="form-control select-date" type="text" maxlength="10" id="EndDate" name="txtEndDate" value="<?php echo $Clean['EndDate']; ?>" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="IsActive" class="col-lg-2 control-label">Is Active</label>
                            <div class="col-lg-4">
                                <input type="checkbox" id="IsActive" name="chkIsActive" <?php echo ($Clean['IsActive'] == 1) ? 'checked="checked"' : ''; ?> value="1" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="Upload" class="col-lg-2 control-label">Upload Image</label>
                            <div class="col-lg-4">
                                <input type="file" name="fleAssignmentImage" onchange="readURL(this);"/>
                            </div>
                            <div class="col-lg-6 AssignmentImage" style="display: none;">
                                <img class="img-responsive center-block img-thumbnail" src="" style="height: 160px; width: 160px; float: left;" />
                            </div> 
                        </div> 
                        <div class="form-group">
                            <div class="col-lg-2"></div>
<?php
                        if (count($AllAssignmentImages) > 0) 
                        {   
                            foreach ($AllAssignmentImages as $AssignmentImageID => $ImageDetails) 
                            {
                                $ImageName = explode('.', $ImageDetails['ImageName']);
                                $ImageExtension = $ImageName[1];
?>
                            <div class="col-lg-3">
<?php
                                if ($ImageExtension == 'pdf')
                                {
                                    echo '<a class="img-thumbnail" style="display: block;width:100%;height:150px;" target="_blank" href="'. SITE_HTTP_PATH .'/site_images/student_assignment/'. $Clean['AssignmentID'] .'/'. $ImageDetails['ImageName'] .'"><i class="fa fa-file-pdf-o" style="text-align: center; padding-top:30px; width:100%;height:150px;font-size:78px;color:red;"></i></a>';
                                }
                                else
                                {
?> 
                                    <a class="img-thumbnail" href="<?php echo SITE_HTTP_PATH;?>/site_images/student_assignment/<?php echo $Clean['AssignmentID'];?>/<?php echo $ImageDetails['ImageName'];?>" data-fancybox="images" data-caption="<?php echo $ImageDetails['ImageName']; ?>">
                                        <img src="<?php echo SITE_HTTP_PATH;?>/site_images/student_assignment/<?php echo $Clean['AssignmentID'];?>/<?php echo $ImageDetails['ImageName'];?>" style="-webkit-user-select: none;background-position: 0px 0px, 10px 10px;background-size: 20px 20px;background-image:linear-gradient(45deg, #eee 25%, transparent 25%, transparent 75%, #eee 75%, #eee 100%),linear-gradient(45deg, #eee 25%, white 25%, white 75%, #eee 75%, #eee 100%);cursor: pointer;"  width="100%" height="150" />
                                    </a>
<?php
                                }
?>
                                <br>        
                                <a href="edit_student_assignment.php?AssignmentImageID=<?php echo $AssignmentImageID;?>&amp;AssignmentID=<?php echo $Clean['AssignmentID'];?>&amp;Process=5" class="btn btn-danger<?php ?>" role="button" style="width: 100%;">Delete</a>
                            </div>
<?php
                            }
                        }
?>
                        </div>
                        <div class="form-group">
                        <div class="col-sm-offset-2 col-lg-10">
                            <input type="hidden" name="hdnProcess" value="3"/>
                            <input type="hidden" name="hdnIsDraft" id="IsDraft" value="0" />
                            <input type="hidden" name="hdnAssignmentID" value="<?php echo $Clean['AssignmentID']; ?>" />
                            <button type="submit" class="btn btn-primary"><i class="fa fa-upload"></i>&nbsp;Update</button>
<?php 
                        if ($Clean['IsDraft'] == 1) 
                        {
?>
                            <button type="submit" class="btn btn-info" id="IsDraftButton"><i class="fa fa-save"></i>&nbsp;Save As Draft</button>
<?php
                        }
?>
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
<script src="../vendor/jquery-ui/jquery-ui.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.5.2/jquery.fancybox.min.js"></script>
<script type="text/javascript">
$(document).ready(function(){

    $(".select-date").datepicker({
                changeMonth: true,
                changeYear: true,
                dateFormat: 'dd/mm/yy'
        });

    $('#IsDraftButton').click(function(){
        $('#IsDraft').val(1);
        $("#StudentAssignmentForm").submit();
    });

    $('#SubmitButton').click(function(){
        $('#IsDraft').val(0);
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

    $('#ClassSubject').change(function(){

        var ClassSubjectID = parseInt($(this).val());
        
        if (ClassSubjectID <= 0)
        {
            $('#Chapter').html('<option value="0">-- Select Chapter --</option>');
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
                $('#Chapter').html('<option value="0">-- Select Chapter --</option>' + ResultArray[1]);
            }
        });
    });

    $('#ChapterTopicName').keyup(function(){

        var ChapterID = $('#Chapter').val();

        if (ChapterID <= 0) 
        {
            alert('Please select a chapter.');
            $('#ChapterTopicName').val('');
            return false;
        }
        var ChapterTopicName = $(this).val();

        if (ChapterTopicName == '') 
        {
            $('#ChapterTopicList').html('');
            return false;
        }
               
        $.post("/xhttp_calls/get_topics_by_chapter.php", {SelectedChapterTopicName:ChapterTopicName, SelectedChapterID:ChapterID}, function(data)
        {
            ResultArray = data.split("|*****|");
            
            if (ResultArray[0] == 'error')
            {
                alert (ResultArray[1]);
                $('#ChapterTopicID').val('');
                return false;
            }
            else
            {
                $('#ChapterTopicList').html(ResultArray[1]);
                
                var ChapterTopicName = $('#ChapterTopicName').val();
                var ChapterTopicID = $('#ChapterTopicList').find('option[value="' + ChapterTopicName + '"]').attr('id');

                if (ChapterTopicID != undefined) 
                {
                    $('#ChapterTopicID').val(ChapterTopicID);
                }
            }
        });
    });

    $('#ChapterTopicName').change(function(){

        var ChapterTopicName = $('#ChapterTopicName').val();
        var ChapterTopicID = $('#ChapterTopicList').find('option[value="' + ChapterTopicName + '"]').attr('id');

        if (ChapterTopicID != undefined) 
        {
            $('#ChapterTopicID').val(ChapterTopicID);
        }
    });
});
</script>
<script type="text/javascript">
$(document).ready(function()
{
    $('[data-fancybox="images"]').fancybox({
      buttons : [ 
        'slideShow',
        'zoom',
        'fullScreen',
        'close'
      ],
      thumbs : {
        autoStart : true
      }
    });
});
function readURL(input) 
{
    if (input.files && input.files[0]) {
        var reader = new FileReader();

        $('.AssignmentImage').removeAttr('style');
        reader.onload = function (e) {
            $('.img-responsive').attr('src', e.target.result);
        }

        reader.readAsDataURL(input.files[0]);
    }
}
</script>
</body>
</html>
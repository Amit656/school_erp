<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once("../../classes/school_administration/class.classes.php");
require_once('../../classes/school_administration/class.class_sections.php');

require_once("../../classes/examination/class.exam_types.php");
require_once("../../classes/examination/class.exams.php");

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

if ($LoggedUser->HasPermissionForTask(TASK_EDIT_EXAM) !== true)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}

if (isset($_POST['btnCancel']))
{
    header('location:exam_list.php');
    exit;
}

$Clean = array();

$Clean['ExamID'] = 0;

if (isset($_GET['ExamID']))
{
    $Clean['ExamID'] = (int) $_GET['ExamID'];
}
else if (isset($_POST['hdnExamID']))
{
    $Clean['ExamID'] = (int) $_POST['hdnExamID'];
}

if ($Clean['ExamID'] <= 0)
{
    header('location:/admin/error.php');
    exit;
}   

try
{
    $ExamToEdit = new Exam($Clean['ExamID']);
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

$ExamTypeList =  array();
$ExamTypeList = ExamType::GetActiveExamTypes();

$ClassList =  array();
$ClassList = AddedClass::GetActiveClasses();

$ClassSectionsList =  array();
$ClassSubjectsList = array();

$HasErrors = false;

$Clean['Process'] = 0;

$Clean['ExamTypeID'] = 0;
$Clean['ClassID'] = 0;
$Clean['ClassSectionID'] = 0;
$Clean['ClassSubjectID'] = 0;

$Clean['ExamName'] = '';
$Clean['MaximumMarks'] = 0;
$Clean['IsOffline'] = 0;

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
        if (isset($_POST['drdExamType'])) 
        {
            $Clean['ExamTypeID'] = (int) $_POST['drdExamType'];
        }
        if (isset($_POST['txtExamName']))
        {
            $Clean['ExamName'] = strip_tags(trim($_POST['txtExamName']));
        }
        if (isset($_POST['txtMaximumMarks']))
        {
            $Clean['MaximumMarks'] = strip_tags(trim($_POST['txtMaximumMarks']));
        }
        if (isset($_POST['chkIsOffline']))
        {
            $Clean['IsOffline'] = 1;
        }

        $NewRecordValidator = new Validator();

        if ($NewRecordValidator->ValidateInSelect($Clean['ClassID'], $ClassList, 'Please select a valid class.')) 
        {
            $ClassSectionsList = ClassSections::GetClassSections($Clean['ClassID']);

            $NewRecordValidator->ValidateInSelect($Clean['ClassSectionID'], $ClassSectionsList, 'Please select a valid section.');

            $SelectedAddedClass = new AddedClass($Clean['ClassID']);
            $SelectedAddedClass->FillAssignedSubjects();

            $ClassSubjectsList = $SelectedAddedClass->GetAssignedSubjects();

            $NewRecordValidator->ValidateInSelect($Clean['ClassSubjectID'], $ClassSubjectsList, 'Please select a valid subject.');  
        }

        $NewRecordValidator->ValidateInSelect($Clean['ExamTypeID'], $ExamTypeList, 'Please select a valid exam type.'); 

        $NewRecordValidator->ValidateStrings($Clean['ExamName'], 'Exam name is required and should be between 3 and 100 characters.', 3, 100);
        
        if($Clean['MaximumMarks'] > 0)
        {
            $NewRecordValidator->ValidateInteger($Clean['MaximumMarks'], 'Please enter valid maximum marks of this exam.', 1);
        }
        
        if ($NewRecordValidator->HasNotifications())
        {
            $HasErrors = true;
            break;
        }
                        
        $ExamToEdit->SetExamTypeID($Clean['ExamTypeID']);
        $ExamToEdit->SetClassSectionID($Clean['ClassSectionID']);
        $ExamToEdit->SetClassSubjectID($Clean['ClassSubjectID']);

        $ExamToEdit->SetExamName($Clean['ExamName']);
        $ExamToEdit->SetMaximumMarks($Clean['MaximumMarks']);
        $ExamToEdit->SetIsOffline($Clean['IsOffline']);

        if (!$ExamToEdit->Save())
        {
            $NewRecordValidator->AttachTextError(ProcessErrors($ExamToEdit->GetLastErrorCode()));
            $HasErrors = true;

            break;
        }
        
        header('location:exam_list.php?Mode=UD');
        exit;
    break;

    case 2:
        $Clean['ExamTypeID'] = $ExamToEdit->GetExamTypeID();
        $Clean['ClassSectionID'] = $ExamToEdit->GetClassSectionID();

        $ClassSectionDetails = new ClassSections($Clean['ClassSectionID']);
        $Clean['ClassID'] = $ClassSectionDetails->GetClassID();
        $ClassSectionsList = ClassSections::GetClassSections($Clean['ClassID']);

        $SelectedAddedClass = New AddedClass($Clean['ClassID']);
        $SelectedAddedClass->FillAssignedSubjects();

        $ClassSubjectsList = $SelectedAddedClass->GetAssignedSubjects();

        $Clean['ClassSubjectID'] = $ExamToEdit->GetClassSubjectID();

        $Clean['ExamName'] = $ExamToEdit->GetExamName();
        $Clean['MaximumMarks'] = $ExamToEdit->GetMaximumMarks();
        $Clean['IsOffline'] = $ExamToEdit->GetIsOffline();
        

    break;
}

require_once('../html_header.php');
?>
<title>Edit Exam</title>
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
                    <h1 class="page-header">Edit Exam</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="EditExamType" action="edit_exam.php" method="post">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        Edit Exam Details
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
                            <label for="ClassSection" class="col-lg-2 control-label">Section</label>
                            <div class="col-lg-4">
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
                            <label for="ExamType" class="col-lg-2 control-label">Exam Type</label>
                            <div class="col-lg-4">
                                <select class="form-control" name="drdExamType" id="ExamType">
                                        <option  value="0" >-- Select Exam Type --</option>
<?php
                                        if (is_array($ExamTypeList) && count($ExamTypeList) > 0)
                                        {
                                            foreach ($ExamTypeList as $ExamTypeID => $ExamType) 
                                            {
                                                echo '<option ' . ($Clean['ExamTypeID'] == $ExamTypeID ? 'selected="selected"' : '') . ' value="' . $ExamTypeID . '">' . $ExamType . '</option>' ;
                                            }
                                        }
?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="ExamName" class="col-lg-2 control-label">Exam Name</label>
                            <div class="col-lg-4">
                                <input class="form-control" type="text" maxlength="100" id="ExamName" name="txtExamName" value="<?php echo $Clean['ExamName']; ?>" />
                            </div>
                            <label for="MaximumMarks" class="col-lg-2 control-label">Maximum Marks</label>
                            <div class="col-lg-4">
                                <input class="form-control" type="text" maxlength="10" id="MaximumMarks" name="txtMaximumMarks" value="<?php echo ($Clean['MaximumMarks']) ? $Clean['MaximumMarks'] : ''; ?>" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="IsOffline" class="col-lg-2 control-label">Is Offline</label>
                            <div class="col-lg-4" style="margin-top: 5px;">
                                <input type="checkbox" id="IsOffline" name="chkIsOffline" <?php echo ($Clean['IsOffline'] == 1) ? 'checked="checked"' : ''; ?> value="1" /> <label for="IsOffline" class="small" > &nbsp;Yes</label>
                            </div>
                        </div>
                        <div class="form-group">
                        <div class="col-sm-offset-2 col-lg-10">
                            <input type="hidden" name="hdnProcess" value="3"/>
                            <input type="hidden" name="hdnExamID" value="<?php echo $Clean['ExamID']; ?>" />
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
<script src="../vendor/jquery-ui/jquery-ui.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.5.2/jquery.fancybox.min.js"></script>
<script type="text/javascript">
$(document).ready(function(){

    $('#Class').change(function(){

        var ClassID = parseInt($('#Class').val());
        $('#ClassSubject').html('<option value="0">-- Select Subject --</option>');
        
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
                $('#ClassSubject').html(ResultArray[1]);
            }
        });
    });
});
</script>
</body>
</html>
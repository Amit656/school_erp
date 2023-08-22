<?php

require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once("../../classes/school_administration/class.classes.php");
require_once('../../classes/school_administration/class.class_sections.php');
require_once('../../classes/school_administration/class.students.php');
require_once('../../classes/school_administration/class.student_details.php');

require_once("../../classes/examination/class.exams.php");
require_once("../../classes/examination/class.exam_types.php");
require_once("../../classes/examination/class.student_exam_mark.php");

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

if ($LoggedUser->HasPermissionForTask(TASK_ADD_STUDENT_MARKS_FEEDING) !== true)
{
    header('location:/admin/unauthorized_login_admin.php');
    exit;
}
// END OF 1. //

$HasErrors = false;

$OfflineExams = array();
$OfflineExams = Exam::GetOfflineExams();

$ClassList =  array();
$ClassList = AddedClass::GetActiveClasses();

$ClassSectionsList =  array();
$ClassSubjectsList =  array();

$StudentsList = array();

$Clean = array();

$Clean['ExamID'] = 0;
$Clean['ExamTypeID'] = 0;

$Clean['ClassID'] = 0;
$Clean['ClassSectionID'] = 0;
$Clean['ClassSubjectID'] = 0;

$Clean['ExamName'] = 0;
$Clean['MaximumMarks'] = 0;
$Clean['ExamClosed'] = 0;

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
    $CurrentExam = new Exam($Clean['ExamID']);
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

$Clean['ExamTypeID'] = $CurrentExam->GetExamTypeID();
$Clean['ClassSectionID'] = $CurrentExam->GetClassSectionID();
$Clean['ClassSubjectID'] = $CurrentExam->GetClassSubjectID();
$Clean['ExamName'] = $CurrentExam->GetExamName();
$Clean['MaximumMarks'] = $CurrentExam->GetMaximumMarks();
$Clean['ExamClosed'] = $CurrentExam->GetExamClosed();

try
{
    $CurrentExamType = new ExamType($Clean['ExamTypeID']);
	$CurrentClassSections = new ClassSections($Clean['ClassSectionID']);
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

$Clean['ClassID'] = $CurrentClassSections->GetClassID();

$CurrentClass = new AddedClass($Clean['ClassID']);
$CurrentClass->FillAssignedSubjects();

$ClassSubjectsList = $CurrentClass->GetAssignedSubjects();

$ClassSectionsList = ClassSections::GetClassSections($Clean['ClassID']);
    
$StudentsList = StudentDetail::GetStudentsByClassSectionID($Clean['ClassSectionID'], 'Active');

$Clean['Obtainedmarks'] = array();

$Clean['Obtainedmarks'] = StudentExamMark::FillObtainedMarks($Clean['ExamID']);

$Clean['Process'] = 0;

if (isset($_POST['hdnProcess']))
{
	$Clean['Process'] = (int) $_POST['hdnProcess'];
}
switch ($Clean['Process'])
{
	case 1:
		if (isset($_POST['txtObtainedmarks']) && is_array($Clean['Obtainedmarks'])) 
		{
			$Clean['Obtainedmarks'] = $_POST['txtObtainedmarks'];
		}

		$NewRecordValidator = new Validator();

        if (count($Clean['Obtainedmarks']) <= 0) 
        {
            $NewRecordValidator->AttachTextError('Please feed student marks.');
            $HasErrors = true;
            break;
        }

        $MarksCounter = 1;

        foreach ($Clean['Obtainedmarks'] as $StudentID => $Marks) 
        {
			if (!array_key_exists($StudentID, $StudentsList))
			{
				header('location:/admin/error.php');
				exit;
			}
			
			$Marks = (int) $Marks;
			
            if ($NewRecordValidator->ValidateInteger($Marks, 'Marks is required and should be integer '. $MarksCounter, 0)) 
			{
				$MarksCounter++;
			}
        }
		
		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}
				
		$NewStudentExamMark = new StudentExamMark();
				
		$NewStudentExamMark->SetExamID($Clean['ExamID']);
		$NewStudentExamMark->SetObtainedmarks($Clean['Obtainedmarks']);

		$NewStudentExamMark->SetCreateUserID($LoggedUser->GetUserID());

		if (!$NewStudentExamMark->Save())
		{
			$NewRecordValidator->AttachTextError(ProcessErrors($NewStudentExamMark->GetLastErrorCode()));
			$HasErrors = true;
			break;
		}
		
		header('location:exam_list.php?Mode=AS');
		exit;
	break;
}

require_once('../html_header.php');
?>
<title>Student Exam Marks feed</title>
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
                    <h1 class="page-header">Add Student Exam Mark</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="AddFeedStudentmarks" action="feed_student_marks.php" method="post">
            	<div class="panel panel-default">
                    <div class="panel-heading">
                        Enter Exam Details
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
                                <select class="form-control"  name="drdClass" id="Class" disabled="disabled">
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
                                <select class="form-control" name="drdClassSection" id="ClassSection" disabled="disabled">
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
                                <select class="form-control" name="drdClassSubject" id="ClassSubject" disabled="disabled">
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
                            <label for="ExamName" class="col-lg-2 control-label">Exam Name</label>
                            <div class="col-lg-4">
                                <select class="form-control" name="drdExamName" id="ExamName" disabled="disabled">
                                    <option  value="0" >-- Select Exam --</option>
<?php
                                    if (is_array($OfflineExams) && count($OfflineExams) > 0)
                                    {
                                        foreach ($OfflineExams as $ExamID => $ExamName) 
                                        {
                                            echo '<option ' . ($Clean['ExamID'] == $ExamID ? 'selected="selected"' : '') . ' value="' . $ExamID . '">' . $ExamName . '</option>' ;
                                        }
                                    }
?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="ExamType" class="col-lg-2 control-label">Exam Type</label>
                            <div class="col-lg-4">
                                <input type="text" class="form-control" name="txtExamType" value="<?php echo $CurrentExamType->GetExamType();?>" disabled="disabled"/>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
            <div class="panel panel-default">
                <div class="panel-heading">
                    Enter Student Marks Details
                </div>
                <div class="panel-body">
                    <form class="form-horizontal" name="AddFeedStudentmarks" action="feed_student_marks.php" method="post">
                        <div class="row" id="RecordTable">
                            <div class="col-lg-12">
                                <table width="100%" class="table table-striped table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th>S. No</th>          
                                            <th>Student Name</th>
                                            <th>Maximum Marks</th>
                                            <th>Obtained Marks</th>
                                        </tr>
									</thead>
									<tbody>
<?php
                                        $RowCounter = 1;
                                        if (count($StudentsList) > 0) 
                                        {
                                            foreach ($StudentsList as $StudentID => $StudentDetails) 
                                            {
?>
                                           <tr>
                                                <td><?php echo $RowCounter++; ?></td>
                                                <td><?php echo $StudentDetails['FirstName'] . $StudentDetails['LastName'] .'('. $StudentDetails['RollNumber']. ')'; ?></td>
                                                <td><input type="text" class="form-control" value="<?php echo $Clean['MaximumMarks']; ?>" disabled="disabled"/></td>
                                                <td>
                                                    <?php
                                                        $Marks = '';
                                                        if (isset($Clean['Obtainedmarks'][$StudentID])) 
                                                        {
                                                            $Marks = $Clean['Obtainedmarks'][$StudentID];
                                                        }
                                                    ?>
                                                    <input type="text" class="form-control marks" studentID="<?php echo $StudentID; ?>" maxlength="4" name="txtObtainedmarks[<?php echo $StudentID ;?>]" value="<?php echo $Marks; ?>" <?php echo ($Clean['ExamClosed']) ? 'disabled="disabled"' : '' ;?>/>
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
<?php
                    if ($Clean['ExamClosed'] != 1)
                    {
?>
                        <div class="form-group">
                            <div class="col-sm-offset-2 col-lg-10">
                                <input type="hidden" name="hdnProcess" value="1" />
                                <input type="hidden" name="hdnExamID" value="<?php echo $Clean['ExamID']; ?>" />
                                <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i>&nbsp;Save</button>
                            </div>
                        </div>
<?php
                    }
?>
                    </form>
                </div>
            </div>
        </div>
        <!-- /#page-wrapper -->

    </div>
    <!-- /#wrapper -->
<?php
require_once('../footer.php');
?>
<script src="../vendor/jquery-ui/jquery-ui.min.js"></script>
<script type="text/javascript">
    $(document).ready(function() 
    {
        $('.marks').keyup(function()
        {   
           if ($(this).val().length >= 3) 
           {
                $(this).closest('tr').next('tr').find('.marks').focus().select();
           }
        });
    });
</script>
</body>
</html>
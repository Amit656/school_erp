<?php
ob_start();

require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once('../../classes/school_administration/class.classes.php');
require_once('../../classes/school_administration/class.class_sections.php');

require_once('../../classes/examination/class.exam_types.php');
require_once('../../classes/examination/class.exams.php');
require_once("../../classes/school_administration/class.classes.php");
require_once('../../classes/school_administration/class.class_sections.php');
require_once("../../classes/school_administration/class.section_master.php");
require_once("../../classes/school_administration/class.students.php");
require_once("../../classes/school_administration/class.student_details.php");

require_once("../../classes/school_administration/class.grades.php");

require_once("../../classes/examination/class.student_exam_mark.php");

require_once("../../includes/global_defaults.inc.php");

require_once("../../includes/helpers.inc.php");

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

if ($LoggedUser->HasPermissionForTask(TASK_LIST_STUDENT_MARKS) !== true)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}

$HasSearchErrors = false;
$HasErrors = false;

$ExamTypeList = array();
$ExamTypeList = ExamType::GetActiveExamTypes();

$AllClasses = array();
$AllClassSections = array();

$AllStudentList = array();
$AllSubjects = array();

$Clean = array();

$Clean['ExamTypeID'] = 0;
$Clean['ClassID'] = 0;
$Clean['ClassSectionID'] = 0;

$Clean['Process'] = 0;

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
	case 7:
		if (isset($_POST['drdExamType'])) 
		{
			$Clean['ExamTypeID'] = (Int) $_POST['drdExamType'];
		}

		if (isset($_POST['drdClass'])) 
		{
			$Clean['ClassID'] = (Int) $_POST['drdClass'];
		}

		if (isset($_POST['drdClassSection'])) 
		{
			$Clean['ClassSectionID'] = (Int) $_POST['drdClassSection'];
		}

		$SearchRecordValidator = new Validator();

		$SearchRecordValidator->ValidateInSelect($Clean['ExamTypeID'], $ExamTypeList, 'Unknown error, please try again.');

		if ($SearchRecordValidator->HasNotifications())
        {
            $HasSearchErrors = true;
            break;
        }

        $AllClasses = Exam::GetExamApplicableClasses($Clean['ExamTypeID']);

        $SearchRecordValidator->ValidateInSelect($Clean['ClassID'], $AllClasses, 'Unknown error, please try again.');

		if ($SearchRecordValidator->HasNotifications())
        {
            $HasSearchErrors = true;
            break;
        }

        $AllClassSections = Exam::GetExamApplicableClassSections($Clean['ExamTypeID'], $Clean['ClassID']);

        $SearchRecordValidator->ValidateInSelect($Clean['ClassSectionID'], $AllClassSections, 'Unknown error, please try again.');

		if ($SearchRecordValidator->HasNotifications())
        {
            $HasSearchErrors = true;
            break;
        }

        $AllSubjects = Exam::GetExamSubjects($Clean['ExamTypeID'], $Clean['ClassSectionID'], 0, true);

        $AllStudentList = StudentDetail::GetStudentsByClassSectionID($Clean['ClassSectionID']);

        if (isset($_POST['report_submit']) && $_POST['report_submit'] == 2)
        {
            require_once('../excel/class_student_subject_marks_xls.php');
        }
	break;
}

require_once('../html_header.php');
?>
<title>View Student Marks</title>
<style type="text/css">
td {text-align: center !important;}
</style>
<link href="../vendor/jquery-ui/jquery-ui.min.css" rel="stylesheet">
</head>

<body>

    <div id="">
    	<!-- Navigation -->
        <nav class="navbar navbar-default navbar-static-top" role="navigation" style="margin-bottom: 0">
<?php 
			require_once('../site_header.php');
			//require_once('../left_navigation_menu.php');
?>                    
            <!-- /.navbar-static-side -->
        </nav>

        <div id="page-wrapper" style="margin-left:0px;">
            <div class="row">
            	<div class="col-lg-12" style="text-align: right; margin-top: 2%;">
					<input type="button" class="btn btn-primary" name="GoBack" onclick="GoBack()" value="Go To Previous Page">
				</div>
                <div class="col-lg-12">
                    <h1 class="page-header">View Student Marks</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
			<div class="panel panel-default">
				<div class="panel-heading">
					View Student Marks
				</div>
				<div class="panel-body">
					<form class="form-horizontal" action="students_marks_report.php" method="post">
<?php
					if ($HasSearchErrors == true)
					{
						echo $SearchRecordValidator->DisplayErrors();
					}
?>
						<div class="form-group">
							<label for="ExamTypeID" class="col-lg-3 control-label">Exam Type</label>
                            <div class="col-lg-4">
                                <select class="form-control" id="ExamType" name="drdExamType">
                                    <option value="0">-- All Exam Type --</option>
<?php
                                foreach ($ExamTypeList as $ExamTypeID => $ExamTypeName)
                                {
                                    echo '<option' . (($Clean['ExamTypeID'] == $ExamTypeID) ? ' selected="selected"' : '') . ' value="' . $ExamTypeID . '">' . $ExamTypeName . '</option>';
                                }
?>
                                </select>
                            </div>
						</div>
						<div class="form-group">
							<label for="Classes" class="col-lg-3 control-label">Classes</label>
                            <div class="col-lg-4">
                                <select class="form-control" id="Class" name=drdClass>
                                    <option value="0">-- All Classes --</option>
<?php
                                foreach ($AllClasses as $ClassID => $ClassName)
                                {
                                    echo '<option' . (($Clean['ClassID'] == $ClassID) ? ' selected="selected"' : '') . ' value="' . $ClassID . '">' . $ClassName . '</option>';
                                }
?>
                                </select>
                            </div>
						</div>
						<div class="form-group">
							<label for="SectionID" class="col-lg-3 control-label">Section</label>
                            <div class="col-lg-4">
                                <select class="form-control" id="Section" name="drdClassSection">
                                    <option value="0">-- All Sections --</option>
<?php
                                foreach ($AllClassSections as $SectionID => $SectionName)
                                {
                                    echo '<option' . (($Clean['ClassSectionID'] == $SectionID) ? ' selected="selected"' : '') . ' value="' . $SectionID . '">' . $SectionName . '</option>';
                                }
?>
                                </select>
                            </div>
						</div>
						<div class="form-group">
							<div class="col-sm-offset-3 col-lg-10">
								<input type="hidden" name="hdnProcess" value="7"/>
								<input type="hidden" name="report_submit" id="get_excel" value="0" />
								<button type="submit" class="btn btn-primary" id="SubmitSearch">Search</button>
							</div>
						</div>
					</form>
				</div>
			</div>
<?php
			if (($Clean['Process'] == 7 &&  $HasSearchErrors == false))
			{
?>
			<div class="panel panel-default">
				<div class="panel-heading">
					Students Marks
				</div>
				<div class="panel-body">
					<div class="row">
                        <div class="col-lg-12" style="text-align: right;">
                            <div class="print-btn-container">
                            	<button id="PrintButton" type="submit" class="btn btn-primary">Print</button>
                            	<button id="" onclick="$('#get_excel').val(2); $('#SubmitSearch').click();$('#get_excel').val(0);" type="submit" class="btn btn-primary">Export to Excel</button>
                            </div>
                        </div>
                    </div>
					<form action="feed_student_marks_all_subjects.php" method="post">
<?php
					if ($HasErrors == true)
					{
						echo $RecordValidator->DisplayErrors();
					}
?>
					<div class="col-lg-12">&nbsp;</div>
					<div class="row" id="RecordTable">
                        <div class="col-lg-12">
							<table class="table table-striped table-bordered table-hover" width="100%">
								<tr>
									<th>Subjects</th>
<?php
								foreach ($AllSubjects as $Details) 
								{
									echo '<th width="60px">' . $Details['SubjectName'] . '<br/>MM. ' . $Details['MaximumMarks'] . '</th>';
								}
?>
								</tr>

<?php
								foreach ($AllStudentList as $StudentID => $StudentDetails) 
								{
?>
									<tr>
										<td width="100px"><?php echo $StudentDetails['FirstName'] . ' ' . $StudentDetails['LastName'] .' ('.$StudentDetails['RollNumber'].')';?></td>
<?php
											$SubjectMarks = StudentExamMark::GetStudentAllSubjectMarks($Clean['ExamTypeID'], $Clean['ClassSectionID'], $StudentID);
											foreach ($AllSubjects as $KeyClassSubjectID => $ClassSubjectDetails) 
											{	
												$SubjectCounter = 0;
												foreach ($SubjectMarks as $ClassSubjectID => $SubjectMarksDetails) 
												{	
													if ($KeyClassSubjectID == $ClassSubjectID) 
													{
														$SubjectCounter = 1;

														if ($SubjectMarksDetails['Status'] == 'Absent' || $SubjectMarksDetails['Status'] == 'Medical') 
														{
															if ($ClassSubjectID == $KeyClassSubjectID) 
															{
?>
																<td><?php echo $SubjectMarksDetails['Status']; ?></td>
<?php
															}									
														}
														elseif ($SubjectMarksDetails['SubjectMarksType'] == 'Grade') 
														{
															if ($ClassSubjectID == $KeyClassSubjectID) 
															{
?>
																<td><?php echo $SubjectMarksDetails['Grade']; ?></td>
<?php
															}
														}
														elseif ($SubjectMarksDetails['SubjectMarksType'] == 'Number') 
														{
															if ($ClassSubjectID == $KeyClassSubjectID) 
															{
?>
																<td class="ClassSubjectMarks">
																	<?php echo $SubjectMarksDetails['Marks'];?>
																</td>
<?php
															}
														}
													}
												}

												if ($SubjectCounter == 0) 
												{
													echo '<td>-</td>';
												}
											}
?>
									</tr>						
<?php
								}
?>
							</table>
						</div>
					</div>
					</form>
				</div>
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
if (PrintMessage($_GET, $Message))
{
?>
    <script type="text/javascript">
        alert('<?php echo $Message; ?>');
    </script>
<?php
}
?>
<!-- JavaScript To Print A Report -->
<script src="js/print-report.js"></script>
<script src="/admin/js/print-report.js"></script>
<script type="text/javascript">
$(document).ready(function()
{	
	$('#ExamType').change(function()
	{	
		var ExamTypeID = parseInt($(this).val());
		
		if (ExamTypeID <= 0)
		{
			$('#ExamType').html('<option value="0">Select Exam Type</option>');
			return;
		}
		
		$.post("/xhttp_calls/get_exam_applicable_classes.php", {SelectedExamType:ExamTypeID}, function(data)
		{
			ResultArray = data.split("|*****|");
			
			if (ResultArray[0] == 'error')
			{
				alert (ResultArray[1]);
				return false;
			}

			$('#Class').html('<option value="0">Select Class</option>' + ResultArray[1]);
		});
	});

	$('#Class').change(function()
	{
		var ClassID = parseInt($(this).val());
		var ExamTypeID = parseInt($('#ExamType').val());

		if (ExamTypeID <= 0)
		{
			$('#Class').html('<option value="0">Select Class</option>');
			$('#Section').html('<option value="0">Select Section</option>');
			return;
		}
		
		if (ClassID <= 0)
		{
			$('#Section').html('<option value="0">Select Section</option>');
			return;
		}
		
		$.post("/xhttp_calls/get_exam_applicable_class_sections.php", {SelectedExamType:ExamTypeID, SelectedClass:ClassID}, function(data){
		
			ResultArray = data.split("|*****|");
			
			if (ResultArray[0] == 'error')
			{
				alert (ResultArray[1]);
				$('#Section').html('<option value="0">Select Section</option>');
				return false;
			}

			$('#Section').html('<option value="0">Select Section</option>' + ResultArray[1]);
		});
	});
});

function GoBack() 
{
  window.history.go(-2);
}
</script>
</body>
</html>
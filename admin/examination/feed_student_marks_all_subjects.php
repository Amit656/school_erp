<?php
ob_start();

require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once('../../classes/school_administration/class.classes.php');
require_once('../../classes/school_administration/class.class_sections.php');

require_once('../../classes/examination/class.exam_types.php');
require_once('../../classes/examination/class.exams.php');

require_once("../../classes/school_administration/class.students.php");
require_once("../../classes/school_administration/class.student_details.php");

require_once("../../classes/school_administration/class.grades.php");

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
// END OF 1. //

if ($LoggedUser->HasPermissionForTask(TASK_ADD_STUDENT_MARKS_FEEDING) !== true)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}

$HasSearchErrors = false;
$HasErrors = false;

$AllGradesList = array();
$AllGradesList = Grade::GetActiveGrades();

$StudentExamStatus = array('Absent' => 'AB', 'Medical' =>'MD');

$ExamTypeList = array();
$ExamTypeList = ExamType::GetActiveExamTypes();

$AllClasses = array();
$AllClassSections = array();


$AllStudentList = array();
$AllSubjects = array();

$SubjectMarksError = array();

$Clean = array();

$Clean['ExamTypeID'] = 0;
$Clean['ClassID'] = 0;
$Clean['ClassSectionID'] = 0;

$Clean['SubjectMarks'] = array();
$Clean['SubjectGrade'] = array();
$Clean['SubjectStudentStatus'] = array();

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
	case 1:
		if (isset($_POST['hdnExamType'])) 
		{
			$Clean['ExamTypeID'] = (Int) $_POST['hdnExamType'];
		}

		if (isset($_POST['hdnClass'])) 
		{
			$Clean['ClassID'] = (Int) $_POST['hdnClass'];
		}

		if (isset($_POST['hdnClassSection'])) 
		{
			$Clean['ClassSectionID'] = (Int) $_POST['hdnClassSection'];
		}

		if (isset($_POST['txtSubjectMarks']) && is_array($_POST['txtSubjectMarks'])) 
		{
			$Clean['SubjectMarks'] = $_POST['txtSubjectMarks'];
		}

		if (isset($_POST['drdSubjectGrade']) && is_array($_POST['drdSubjectGrade'])) 
		{
			$Clean['SubjectGrade'] = $_POST['drdSubjectGrade'];
		}

		if (isset($_POST['drdClassSubjectStudentStatus']) && is_array($_POST['drdClassSubjectStudentStatus'])) 
		{
			$Clean['SubjectStudentStatus'] = $_POST['drdClassSubjectStudentStatus'];
		}


		if (count($Clean['SubjectMarks']) == 0 && count($_POST['SubjectGrade']) == 0 && count($_POST['SubjectStudentStatus']) == 0) 
		{
			header('location:/admin/error.php');
			exit;
		}

		$RecordValidator = new Validator();

		$RecordValidator->ValidateInSelect($Clean['ExamTypeID'], $ExamTypeList, 'Unknown error, please try again.');

		if ($RecordValidator->HasNotifications())
        {
            $HasErrors = true;
            break;
        }

        $AllClasses = Exam::GetExamApplicableClasses($Clean['ExamTypeID']);

        $RecordValidator->ValidateInSelect($Clean['ClassID'], $AllClasses, 'Unknown error, please try again.');

		if ($RecordValidator->HasNotifications())
        {
            $HasErrors = true;
            break;
        }

        $AllClassSections = Exam::GetExamApplicableClassSections($Clean['ExamTypeID'], $Clean['ClassID']);
		
        $RecordValidator->ValidateInSelect($Clean['ClassSectionID'], $AllClassSections, 'Unknown error, please try again.');

		if ($RecordValidator->HasNotifications())
        {
            $HasErrors = true;
            break;
        }

        $SubjectMarksToBeSave = array();
        $AllStudentList = StudentDetail::GetStudentsByClassSectionID($Clean['ClassSectionID']);

        $AllSubjects = Exam::GetExamSubjects($Clean['ExamTypeID'], $Clean['ClassSectionID'], 0, true);

        $RowCounter = 1;   

        foreach ($AllStudentList as $StudentID => $StudentDetails) 
        {	
        	try
			{
			    $StudentObj = new StudentDetail($StudentID);    
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

        	foreach ($AllSubjects as $ClassSubjectID => $ClassSubjectDetails) 
        	{
        		if (!isset($Clean['SubjectMarks'][$StudentID][$ClassSubjectID]) && !isset($Clean['SubjectGrade'][$StudentID][$ClassSubjectID]) && !isset($Clean['SubjectStudentStatus'][$StudentID][$ClassSubjectID])) 
        		{
        			header('location:../error.php');
			    	exit;
        		}

        		$SubjectMarks = 0;
        		$GradeID = 0;
        		$ClassSubjectStudentStatus = '';

        		if (isset($Clean['SubjectMarks'][$StudentID][$ClassSubjectID])) 
        		{
        			$SubjectMarks = (float) $Clean['SubjectMarks'][$StudentID][$ClassSubjectID];

        			/*if ($ClassSubjectDetails['SubjectMarksType'] == 'Number' && $SubjectMarks == 0) 
	        		{
	        			continue 1;
	        		}*/

        			$RecordValidator->ValidateNumeric($SubjectMarks, 'Subject Marks should be numeric for student '. $StudentDetails['FirstName']. ' ' . $StudentDetails['LastName']. ' at row '. $RowCounter);

        			if ($SubjectMarks > $ClassSubjectDetails['MaximumMarks']) 
        			{
        				$RecordValidator->AttachTextError('Subject marks can not br greater than maximum marks for student '. $StudentDetails['FirstName']. ' ' . $StudentDetails['LastName']. ' at row '. $RowCounter);
        			}
        		}
				elseif (isset($Clean['SubjectGrade'][$StudentID][$ClassSubjectID])) 
        		{
        			$GradeID = $Clean['SubjectGrade'][$StudentID][$ClassSubjectID];

        			$RecordValidator->ValidateInSelect($GradeID, $AllGradesList, 'Unknown error, please try again.');
        		}
				elseif (isset($Clean['SubjectStudentStatus'][$StudentID][$ClassSubjectID])) 
        		{
        			$ClassSubjectStudentStatus = $Clean['SubjectStudentStatus'][$StudentID][$ClassSubjectID];

        			$RecordValidator->ValidateInSelect($ClassSubjectStudentStatus, $StudentExamStatus, 'Unknown error, please try again.');
        		}

        		$SubjectMarksToBeSave[$StudentID][$ClassSubjectID]['SubjectMarks'] = $SubjectMarks;
        		$SubjectMarksToBeSave[$StudentID][$ClassSubjectID]['GradeID'] = $GradeID;
        		$SubjectMarksToBeSave[$StudentID][$ClassSubjectID]['ClassSubjectStudentStatus'] = $ClassSubjectStudentStatus;
        		$SubjectMarksToBeSave[$StudentID][$ClassSubjectID]['ExamID'] = $AllSubjects[$ClassSubjectID]['ExamID'];
        	}

        	$RowCounter++;
        }

        if ($RecordValidator->HasNotifications())
        {
            $HasErrors = true;
            break;
        }

        if (!StudentExamMark::SaveStudentSubjectMarks($SubjectMarksToBeSave, $LoggedUser->GetUserID())) 
        {
        	$RecordValidator->AttachTextError('There was a problem in saving records.');
			$HasErrors = true;
			break;
        }

		header('location:students_marks_report.php?Mode=ED');
		exit;
	break;

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

	break;
}

require_once('../html_header.php');
?>
<title>Feed Student Marks</title>
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
                    <h1 class="page-header">Feed Student Marks</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
			<div class="panel panel-default">
				<div class="panel-heading">
					Feed Student Marks
				</div>
				<div class="panel-body">
					<form class="form-horizontal" action="feed_student_marks_all_subjects.php" method="post">
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
								<button type="submit" class="btn btn-primary">Search</button>
							</div>
						</div>
					</form>
				</div>
			</div>
<?php
			if (($Clean['Process'] == 7 &&  $HasSearchErrors == false) || $Clean['Process'] == 1)
			{
?>
			<div class="panel panel-default">
				<div class="panel-heading">
					Feed Marks
				</div>
				<div class="panel-body">
					<form action="feed_student_marks_all_subjects.php" method="post">
<?php
					if ($HasErrors == true)
					{
						echo $RecordValidator->DisplayErrors();
					}
?>
					<div class="col-lg-12">&nbsp;</div>

					<table class="table table-striped table-bordered table-hover TimeTableParentTable" width="100%">
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
								if (count($Clean['SubjectMarks']) > 0 || count($Clean['SubjectGrade']) > 0 || count($Clean['SubjectStudentStatus']) > 0) 
								{
									foreach ($AllSubjects as $ClassSubjectID => $ClassSubjectDetails) 
									{
						        		$SubjectMarks = 0;
						        		$GradeID = 0;
						        		$ClassSubjectStudentStatus = '';

						        		if (isset($Clean['SubjectMarks'][$StudentID][$ClassSubjectID])) 
						        		{
						        			$SubjectMarks = $Clean['SubjectMarks'][$StudentID][$ClassSubjectID];
						        		}
										elseif (isset($Clean['SubjectGrade'][$StudentID][$ClassSubjectID])) 
						        		{
						        			$GradeID = $Clean['SubjectGrade'][$StudentID][$ClassSubjectID];
						        		}
										elseif (isset($Clean['SubjectStudentStatus'][$StudentID][$ClassSubjectID])) 
						        		{
						        			$ClassSubjectStudentStatus = $Clean['SubjectStudentStatus'][$StudentID][$ClassSubjectID];
						        		}

						        		if ($ClassSubjectDetails['SubjectMarksType'] == 'Number') 
						        		{
?>
											<td class="ClassSubjectMarks">
												<input class="form-control center-block" style="width: 55px;" maxlength="3" type="text" name="txtSubjectMarks[<?php echo $StudentID;?>][<?php echo $ClassSubjectID;?>]" value="<?php echo $SubjectMarks;?>"/>
												<br/><a student="<?php echo $StudentID;?>" style="cursor: pointer;" classSubject="<?php echo $ClassSubjectID;?>" status="Present" subjectMarksType="<?php echo $ClassSubjectDetails['SubjectMarksType']; ?>" class="ChangeStatus">Change Status</a>
											</td>
<?php
						        		}
						        		elseif ($ClassSubjectDetails['SubjectMarksType'] == 'Grade') 
						        		{
?>
											<td>
												<select class="form-control center-block" name="drdSubjectGrade[<?php echo $StudentID;?>][<?php echo $ClassSubjectID;?>]" style="width: 90px">
<?php
												foreach ($AllGradesList as $KeyGradeID => $GradeName) 
												{
									 				echo '<option value="' . $KeyGradeID . '"'.($KeyGradeID == $GradeID ? 'selected="selected"' : '') .'>' . $GradeName . '</option>';
												}
?>
												</select>
												<a student="<?php echo $StudentID;?>" style="cursor: pointer;" classSubject="<?php echo $ClassSubjectID;?>" status="Present" subjectMarksType="<?php echo $ClassSubjectDetails['SubjectMarksType']; ?>" class="ChangeStatus">Change Status</a>
											</td>
<?php
						        		}
						        		elseif ($ClassSubjectStudentStatus != '') 
						        		{
?>	
						        			<td>
												<select class="form-control center-block" name="drdClassSubjectStudentStatus[<?php echo $StudentID;?>][<?php echo $ClassSubjectID;?>]" style="width: 90px">
<?php
												foreach ($StudentExamStatus as $ExamStatus => $ExamStatusName) 
												{
?>
													<option <?php echo ($ExamStatus == $ClassSubjectStudentStatus ? 'selected="selected"' : '') ?> value="<?php echo $ExamStatus; ?>"><?php echo $ExamStatusName; ?></option>
<?php
												}
?>
												</select>
													<br/><a student="<?php echo $StudentID;?>" style="cursor: pointer;" classSubject="<?php echo $ClassSubjectID;?>" status="Absent" subjectMarksType="<?php echo $ClassSubjectDetails['SubjectMarksType']; ?>" class="ChangeStatus">Change Status</a>
											</td>
<?php
						        		}
					        		}
								}
								else
								{
									$SubjectMarks = Exam::GetExamSubjects($Clean['ExamTypeID'], $Clean['ClassSectionID'], $StudentID);	
									
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
														<td>
														<select class="form-control center-block" name="drdClassSubjectStudentStatus[<?php echo $StudentID;?>][<?php echo $ClassSubjectID;?>]" style="width: 90px">
<?php
														foreach ($StudentExamStatus as $ExamStatus => $ExamStatusName) 
														{
?>
															<option <?php echo ($ExamStatus == $SubjectMarksDetails['Status'] ? 'selected="selected"' : '') ?> value="<?php echo $ExamStatus; ?>"><?php echo $ExamStatusName; ?></option>
<?php
													}
?>
														</select>
															<br/><a student="<?php echo $StudentID;?>" style="cursor: pointer;" classSubject="<?php echo $ClassSubjectID;?>" status="Absent" subjectMarksType="<?php echo $SubjectMarksDetails	['SubjectMarksType']; ?>" class="ChangeStatus">Change Status</a>
														</td>
<?php
													}										
												}
												elseif ($SubjectMarksDetails['SubjectMarksType'] == 'Grade') 
												{
													if ($ClassSubjectID == $KeyClassSubjectID) 
													{
?>
														<td>
															<select class="form-control center-block" name="drdSubjectGrade[<?php echo $StudentID;?>][<?php echo $ClassSubjectID;?>]" style="width: 90px">
<?php
															foreach ($AllGradesList as $GradeID => $GradeName) 
															{
												 				echo '<option value="' . $GradeID . '"'.($GradeID == $SubjectMarksDetails['GradeID'] ? 'selected="selected"' : '') .'>' . $GradeName . '</option>';
															}
?>
															</select>
															<a student="<?php echo $StudentID;?>" style="cursor: pointer;" classSubject="<?php echo $ClassSubjectID;?>" status="Present" subjectMarksType="<?php echo $SubjectMarksDetails['SubjectMarksType']; ?>" class="ChangeStatus">Change Status</a>
														</td>
<?php
													}
												}
												elseif ($SubjectMarksDetails['SubjectMarksType'] == 'Number') 
												{
													if ($ClassSubjectID == $KeyClassSubjectID) 
													{
?>
														<td class="ClassSubjectMarks">
															<input class="form-control center-block" style="width: 55px;" maxlength="3" type="text" name="txtSubjectMarks[<?php echo $StudentID;?>][<?php echo $ClassSubjectID;?>]" value="<?php echo $SubjectMarksDetails['Marks'];?>"/>
															<br/><a student="<?php echo $StudentID;?>" style="cursor: pointer;" classSubject="<?php echo $ClassSubjectID;?>" status="Present" subjectMarksType="<?php echo $SubjectMarksDetails['SubjectMarksType']; ?>" class="ChangeStatus">Change Status</a>
														</td>
<?php
													}
												}
											}
										}

										if ($SubjectCounter == 0) 
										{
											switch ($ClassSubjectDetails['SubjectMarksType']) 
											{
												case 'Number':
?>
													<td class="ClassSubjectMarks">
														<input class="form-control center-block" style="width: 55px;" maxlength="3" type="text" name="txtSubjectMarks[<?php echo $StudentID;?>][<?php echo $KeyClassSubjectID;?>]"/>
														<br/><a student="<?php echo $StudentID;?>" style="cursor: pointer;" classSubject="<?php echo $KeyClassSubjectID;?>" status="Present" subjectMarksType="<?php echo $ClassSubjectDetails['SubjectMarksType']; ?>" class="ChangeStatus">Change Status</a>
													</td>
<?php
												break;

												case 'Grade':
?>
												<td>
													<select class="form-control center-block" name="drdSubjectGrade[<?php echo $StudentID;?>][<?php echo $KeyClassSubjectID;?>]" style="width: 90px">
<?php
													foreach ($AllGradesList as $GradeID => $GradeName) 
													{
										 				echo '<option value="' . $GradeID . '">' . $GradeName . '</option>';
													}
?>
													</select>
													<a student="<?php echo $StudentID;?>" style="cursor: pointer;" classSubject="<?php echo $KeyClassSubjectID;?>" status="Present" subjectMarksType="<?php echo $ClassSubjectDetails['SubjectMarksType']; ?>" class="ChangeStatus">Change Status</a>
												</td>
<?php
												break;
											}
										}
									}
								}
?>
							</tr>						
<?php
						}
?>
					</table>
					<div class="col-lg-12 text-center">
						<input type="hidden" name="hdnExamType" value="<?php echo $Clean['ExamTypeID']; ?>" />
						<input type="hidden" name="hdnClass" value="<?php echo $Clean['ClassID']; ?>" />
						<input type="hidden" name="hdnClassSection" value="<?php echo $Clean['ClassSectionID']; ?>" />
						<input type="hidden" name="hdnProcess" value="1" />
						<input type="submit" name="btnSave" value="Save" class="btn col-sm-offset-3 col-lg-5 btn-primary" />
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
?>
<script type="text/javascript">
$(document).ready(function()
{	
	$('body').on('click', '.ChangeStatus', function()
    {	
    	var StudentID = $(this).attr('student');
		var ClassSubjectID = $(this).attr('classSubject');
		var Status = $(this).attr('status');
		var Subjectmarkstype = $(this).attr('subjectmarkstype');

		var Data = '';

		if (Status == 'Absent' && Subjectmarkstype == 'Number') 
		{
			Data = '<input class="form-control center-block" style="width: 55px;" maxlength="3" type="text" name="txtSubjectMarks[' + StudentID + '][' + ClassSubjectID + ']">';
			Data += '<br/><a student="' + StudentID + '" style="cursor: pointer;" classSubject="' + ClassSubjectID + '" status="Present" subjectMarksType="'+Subjectmarkstype+'" class="ChangeStatus">Change Status</a>';
		}
		else if(Status == 'Absent' && Subjectmarkstype == 'Grade')
		{
			Data = '<Select class="form-control center-block" name="drdSubjectGrade[' + StudentID + '][' + ClassSubjectID + ']" style="width: 90px">';
<?php
			foreach ($AllGradesList as $GradeID => $GradeName) 
			{
?>
				Data += '<option value="<?php echo $GradeID; ?>"><?php echo $GradeName; ?></option>';
<?php
			}
?>
			Data += '</Select>';
			Data += '<br/><a student="' + StudentID + '" style="cursor: pointer;" classSubject="' + ClassSubjectID + '" status="Present" subjectMarksType="'+Subjectmarkstype+'" class="ChangeStatus">Change Status</a>';
		}
		else if(Status == 'Present')
		{
			Data = '<Select class="form-control center-block" name="drdClassSubjectStudentStatus[' + StudentID + '][' + ClassSubjectID + ']" style="width: 90px">';
<?php
			foreach ($StudentExamStatus as $Status => $Name) 
			{
?>
				Data += '<option value="<?php echo $Status; ?>"><?php echo $Name; ?></option>';
<?php
			}
?>
			Data += '</Select>';
			Data += '<br/><a student="' + StudentID + '" style="cursor: pointer;" classSubject="' + ClassSubjectID + '" status="Absent" subjectMarksType="'+Subjectmarkstype+'" class="ChangeStatus">Change Status</a>';
		}
		$($(this).closest('td')).html(Data);
    });

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
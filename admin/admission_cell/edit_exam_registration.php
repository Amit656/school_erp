<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once("../../classes/school_administration/class.classes.php");
require_once("../../classes/admission_cell/class.student_registrations.php");
require_once("../../classes/admission_cell/class.exams.php");
require_once("../../classes/admission_cell/class.exam_registrations.php");

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
	header('location:unauthorized_login_admin.php');
	exit;
}
catch (Exception $e)
{
	header('location:unauthorized_login_admin.php');
	exit;
}
// END OF 1. //

if ($LoggedUser->HasPermissionForTask(TASK_EDIT_EXAM_REGISTRATION) !== true)
{
    header('location:/admin/unauthorized_login_admin.php');
    exit;
}

$Clean = array();

$Clean['ExamRegistrationID'] = 0;

if (isset($_GET['ExamRegistrationID']))
{
    $Clean['ExamRegistrationID'] = (int) $_GET['ExamRegistrationID'];
}
else if (isset($_POST['hdnExamRegistrationID']))
{
    $Clean['ExamRegistrationID'] = (int) $_POST['hdnExamRegistrationID'];
}

if ($Clean['ExamRegistrationID'] <= 0)
{
    header('location:../error.php');
    exit;
}   

try
{
    $ExamRegistrationToEdit = new ExamRegistration($Clean['ExamRegistrationID']);
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

$ExamStatusList = array('Awaited' => 'Awaited', 'Passed' => 'Passed', 'Failed' => 'Failed');

$ClassList =  array();
$ClassList = AddedClass::GetActiveClasses();

$AllExamsList = array();
$AllExamsList = Exam::GetActiveExams();

$AllStudentRegistrationsList = array();
$AllStudentRegistrationsList = StudentRegistration::GetActiveStudentRegistrations();

$AllExams = array();
$AllExams = Exam::GetAllExams();

$StudentClassID = 0;
$StudentName = '';
$FatherName = '';
$MotherName = '';
$DOB = '';

$HasErrors = false;

$Clean['Process'] = 0;

$Clean['StudentRegistrationID'] = 0;
$Clean['ExamID'] = 0;
$Clean['ExamID'] = key($AllExams);
$Clean['RegistrationAmount'] = 0;
$Clean['ExamStatus'] = 'Awaited';
$Clean['ObtainedMarks'] = 0.00;

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
		if (isset($_POST['drdStudentRegistration']))
		{
			$Clean['StudentRegistrationID'] = (int) $_POST['drdStudentRegistration'];
		}

		if (isset($_POST['drdExam']))
		{
			$Clean['ExamID'] = (int) $_POST['drdExam'];
		}

		if (isset($_POST['txtRegistrationAmount']))
		{
			$Clean['RegistrationAmount'] = strip_tags(trim($_POST['txtRegistrationAmount']));
		}

		if (isset($_POST['chkIsActive']))
        {
            $Clean['IsActive'] = 1;
        }
		
		$NewRecordValidator = new Validator();

		$NewRecordValidator->ValidateInSelect($Clean['StudentRegistrationID'], $AllStudentRegistrationsList, 'Unknown error, please try again.');
		$NewRecordValidator->ValidateInSelect($Clean['ExamID'], $AllExamsList, 'Unknown error, please try again.');
		$NewRecordValidator->ValidateNumeric($Clean['RegistrationAmount'], 'Registration amount should be integer.', 0);
		
		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}
					
		$ExamRegistrationToEdit->SetStudentRegistrationID($Clean['StudentRegistrationID']);
		$ExamRegistrationToEdit->SetExamID($Clean['ExamID']);

		$ExamRegistrationToEdit->SetRegistrationAmount($Clean['RegistrationAmount']);
		$ExamRegistrationToEdit->SetExamStatus($ExamRegistrationToEdit->GetExamStatus());
		$ExamRegistrationToEdit->SetIsActive($Clean['IsActive']);
		
		$ExamRegistrationToEdit->SetCreateUserID($LoggedUser->GetUserID());

		if ($ExamRegistrationToEdit->RecordExists())
		{
			$NewRecordValidator->AttachTextError('Exam registration you have added already exists');
			$HasErrors = true;
			break;
		}

		if (!$ExamRegistrationToEdit->Save())
		{
			$NewRecordValidator->AttachTextError(ProcessErrors($ExamRegistrationToEdit->GetLastErrorCode()));
			$HasErrors = true;
			break;
		}
		
		header('location:exam_registrations_list.php?Mode=UD&Process=7&IsActive=1');
		exit;
		break;

	// For update examStatus
	case 12:
		if (isset($_POST['rdbExamStatus']))
		{
			$Clean['ExamStatus'] = strip_tags(trim($_POST['rdbExamStatus']));
		}

		if (isset($_POST['txtObtainedMarks']))
		{
			$Clean['ObtainedMarks'] = strip_tags(trim($_POST['txtObtainedMarks']));
		}

		$NewRecordValidator = new Validator();

		$NewRecordValidator->ValidateInSelect($Clean['ExamStatus'], $ExamStatusList, 'Unknown error, please try again.');

		if ($Clean['ObtainedMarks'] != '') 
		{
			$NewRecordValidator->ValidateNumeric($Clean['ObtainedMarks'], 'Obtained marks should be numeric.');
		}

		$ExamRegistrationDetails = array();
        $ExamRegistrationDetails = ExamRegistration::GetStudentRegistrationsDetailsByStudentRegistrationID($ExamRegistrationToEdit->GetStudentRegistrationID());

        $StudentClassID = $ExamRegistrationDetails['ClassID'];
		$StudentName = $ExamRegistrationDetails['StudentName'];
		$FatherName = $ExamRegistrationDetails['FatherName'];
		$MotherName = $ExamRegistrationDetails['MotherName'];
		$DOB = $ExamRegistrationDetails['DOB'];

		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}

		$ExamRegistrationToEdit->SetExamStatus($Clean['ExamStatus']);
		$ExamRegistrationToEdit->SetObtainedMarks($Clean['ObtainedMarks']);

		if ($ExamRegistrationToEdit->UpdateResultDetails())
		{
			$NewRecordValidator->AttachTextError(ProcessErrors($ExamRegistrationToEdit->GetLastErrorCode()));
			$HasErrors = true;
			break;
		}
		
		header('location:exam_registrations_list.php?Mode=UD&Process=7&IsActive=1');
		exit;
		break;

	case 2:
        $Clean['StudentRegistrationID'] = $ExamRegistrationToEdit->GetStudentRegistrationID();
        $Clean['ExamID'] = $ExamRegistrationToEdit->GetExamID();
        $Clean['RegistrationAmount'] = $ExamRegistrationToEdit->GetRegistrationAmount();
        $Clean['IsActive'] = $ExamRegistrationToEdit->GetIsActive();
		break; 

	case 11:
		$Clean['ExamStatus'] = $ExamRegistrationToEdit->GetExamStatus();
		$Clean['StudentRegistrationID'] = $ExamRegistrationToEdit->GetStudentRegistrationID();

		$ExamRegistrationDetails = array();
        $ExamRegistrationDetails = ExamRegistration::GetStudentRegistrationsDetailsByStudentRegistrationID($Clean['StudentRegistrationID']);

        $StudentClassID = $ExamRegistrationDetails['ClassID'];
		$StudentName = $ExamRegistrationDetails['StudentName'];
		$FatherName = $ExamRegistrationDetails['FatherName'];
		$MotherName = $ExamRegistrationDetails['MotherName'];
		$DOB = $ExamRegistrationDetails['DOB'];
		break;
}

require_once('../html_header.php');
?>
<title>Edit Exam Registration</title>
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
                    <h1 class="page-header">Edit Exam Registration</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
<?php
		if ($Clean['Process'] == 11 || $Clean['Process'] == 12) 
		{
?>
			<form class="form-horizontal" name="EditExamRegistration" action="edit_exam_registration.php" method="post">
	            <div class="panel panel-default">
	                <div class="panel-heading">
	                    Applicant Details
	                </div>
<?php
						if ($HasErrors == true)
						{
							echo $NewRecordValidator->DisplayErrors();
						}
?>
	                <div class="panel-body">
	                	<div class="form-group">
                            <label for="ClassID" class="col-lg-2 control-label">Class</label>
                            <div class="col-lg-4">
                            	<select class="form-control" name="drdClass" id="ClassID" disabled="disabled">
									<option value="0">Select Class</option>
<?php
                                	foreach ($ClassList as $ClassID => $ClassName)
                                    {
                                        echo '<option ' . ($StudentClassID == $ClassID ? 'selected="selected"' : '') . ' value="' . $ClassID . '">' . $ClassName . '</option>';
                                    }
?>
								</select>
                            </div>
                        </div>
	                	<div class="form-group">
		                	<label for="StudentRegistrationID" class="col-lg-2 control-label">Student Name</label>
	                        <div class="col-lg-4">
	                    		<input class="form-control" type="text" id="StudentRegistrationID" value="<?php echo $StudentName; ?>" disabled="disabled">
	                        </div>
	                        <label for="DOB" class="col-lg-2 control-label">Date Of Birth</label>
	                        <div class="col-lg-4">
	                        	<input class="form-control select-date dtepicker" type="text" maxlength="10" id="DOB" value="<?php echo date('d/m/Y', strtotime($DOB)); ?>" disabled="disabled" />
	                        </div>
                        </div>
                        <div class="form-group">
                            <label for="FatherName" class="col-lg-2 control-label">Father's Name</label>
                            <div class="col-lg-4">
                                <input class="form-control" type="text" maxlength="50" id="FatherName" name="txtFatherName" value="<?php echo $FatherName; ?>" disabled="disabled"/>
                            </div>
                            <label for="MotherName" class="col-lg-2 control-label">Mother's Name</label>
                            <div class="col-lg-4">
                                <input class="form-control" type="text" maxlength="50" id="MotherName" name="txtMotherName" value="<?php echo $MotherName; ?>" disabled="disabled"/>
                            </div>
                        </div>
	                </div>
	            </div>
	        </form>
<?php
		}

		if ($Clean['Process'] == 2 || $Clean['Process'] == 3) 
		{
?>
			<form class="form-horizontal" name="EditExamRegistration" action="edit_exam_registration.php" method="post">
            	<div class="panel panel-default">
                    <div class="panel-heading">
                        Enter Exam Registration Details
                    </div>
<?php
					if ($HasErrors == true)
					{
						echo $NewRecordValidator->DisplayErrors();
					}
?>
                    <div class="panel-body">                    
						<div class="form-group">
                            <label for="StudentRegistrationID" class="col-lg-2 control-label">Student Name</label>
                            <div class="col-lg-4">
	                    		<select class="form-control" id="StudentRegistrationID" name="drdStudentRegistration">
<?php
								foreach ($AllStudentRegistrationsList as $StudentRegistrationID => $StudentName) 
								{
?>
								<option value="<?php echo $StudentRegistrationID;?>" <?php echo($StudentRegistrationID == $Clean['StudentRegistrationID']) ? 'selected="selected"' : '';?> ><?php echo $StudentName; ?></option>
<?php
								}
?>							
								</select>
                            </div>
                            <label for="ExamID" class="col-lg-2 control-label">Exam</label>
                            <div class="col-lg-4">
                        	<select class="form-control Registration" id="ExamID" name="drdExam">
<?php
                            $Key  = Key($AllExamsList);
                            foreach ($AllExamsList as $ExamID => $ExamsName) 
                            {
?>
                                <option value="<?php echo $ExamID;?>" exam-date="<?php echo date('d/m/Y', strtotime($AllExams[$ExamID]['ExamDate']));?>" exam-time="<?php echo $AllExams[$ExamID]['ExamTime'];?>" exam-duration="<?php echo $AllExams[$ExamID]['ExamDuration'];?>" <?php echo($ExamID == $Clean['ExamID']) ? 'selected="selected"' : '';?> ><?php echo $ExamsName; ?></option>
<?php
                            }
?>                          
                            </select>                             	
                            </div>
                        </div>
                        <div class="form-group">
                        	<label for="RegistrationAmount" class="col-lg-2 control-label">Registration Fee</label>
                            <div class="col-lg-4">
                            	<input class="form-control" type="text" maxlength="7" id="RegistrationAmount" name="txtRegistrationAmount" value="<?php echo ($Clean['RegistrationAmount']) ? $Clean['RegistrationAmount'] : ''; ?>" />
                            </div>
                            <label for="ExamDate" class="col-lg-2 control-label">Exam Date</label>
                            <div class="col-lg-4">
                            	<input class="form-control" type="text" maxlength="10" id="ExamDate" name="txtExamDate" value="<?php echo date('d/m/Y', strtotime($AllExams[$Key]['ExamDate']));?>" disabled="disabled" />
                            </div>
                        </div>	
                    	<div class="form-group">
                    		<label for="ExamTime" class="col-lg-2 control-label">Exam Time</label>
                            <div class="col-lg-4">
                                <input class="form-control" type="text" maxlength="8" id="ExamTime" name="txtExamTime" value="<?php echo $AllExams[$Key]['ExamTime'];?>" disabled="disabled" />
                            </div>
                            <label for="ExamDuration" class="col-lg-2 control-label">Exam Duration (In Minutes)</label>
                            <div class="col-lg-4">
                                <input class="form-control" type="text" maxlength="3" id="ExamDuration" name="txtExamDuration" value="<?php echo $AllExams[$Key]['ExamDuration'];?>" disabled="disabled" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="IsActive" class="col-lg-2 control-label">Is Active</label>
                            <div class="col-lg-4">
                                <input type="checkbox" id="IsActive" name="chkIsActive" value="1" <?php echo ($Clean['IsActive'] == 1) ? 'checked="checked"' : ''; ?>/>
                            </div>
                        </div>
                        <div class="form-group">
		                    <div class="col-sm-offset-2 col-lg-10">
		                    	<input type="hidden" name="hdnProcess" value="3" />
		                    	<input type="hidden" name="hdnExamRegistrationID" value="<?php echo $Clean['ExamRegistrationID'];?>" />
								<button type="submit" class="btn btn-primary">Save</button>
		                    </div>
                      </div>
                    </div>
                </div>
            </form>
<?php
		}

		if ($Clean['Process'] == 11 || $Clean['Process'] == 12) 
		{
?>
			<form class="form-horizontal" name="EditExamRegistration" action="edit_exam_registration.php" method="post">
	            <div class="panel panel-default">
	                <div class="panel-heading">
	                    Applicant Exam Status Details
	                </div>
	                <div class="panel-body">
		                <div class="form-group">
	                        <label for="ExamStatus" class="col-lg-2 control-label">Exam Status</label>
	                        <div class="col-lg-4">
<?php
							foreach ($ExamStatusList as $ExamStatus => $ExamStatusName) 
							{
?>
								<label style="font-weight: normal;">
									<input type="radio" name="rdbExamStatus" value="<?php echo $ExamStatus;?>" <?php echo($ExamStatus == $Clean['ExamStatus']) ? 'checked="checked"' : ''?> />&nbsp;<?php echo $ExamStatusName;?>&nbsp;
								</label>
<?php
							}
?>
	                        </div>
	                    </div>
	                    <div class="form-group">
	                        <label for="ObtainedMarks" class="col-lg-2 control-label">Obtained Marks</label>
	                        <div class="col-lg-4">
	                        	<input class="form-control" type="text" name="txtObtainedMarks" value="<?php echo ($Clean['ObtainedMarks']) ? $Clean['ObtainedMarks'] : '';?>"/>
	                        </div>
	                    </div>
		                <div class="panel-body">
		                	<div class="form-group">
			                    <div class="col-sm-offset-2 col-lg-10">
			                    	<input type="hidden" name="hdnProcess" value="12" />
			                    	<input type="hidden" name="hdnExamRegistrationID" value="<?php echo $Clean['ExamRegistrationID'];?>" />
									<button type="submit" class="btn btn-primary">Save</button>
			                    </div>
	                      	</div>
		                </div>
	            	</div>
	            </div>
	        </form>
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
var AllExams = <?php echo json_encode($AllExams); ?>;
$(document).ready(function(){
	$('#ExamID').change(function()
	{
		var ExamDate =  $('option:selected', this).attr('exam-date');
        var ExamTime =  $('option:selected', this).attr('exam-time');
        var ExamDuration =  $('option:selected', this).attr('exam-duration');

        $('#ExamDate').val(ExamDate);
        $('#ExamTime').val(ExamTime);
        $('#ExamDuration').val(ExamDuration);
	});
});
</script>
</body>
</html>
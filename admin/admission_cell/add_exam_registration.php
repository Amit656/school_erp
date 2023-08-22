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

if ($LoggedUser->HasPermissionForTask(TASK_ADD_EXAM_REGISTRATION) !== true)
{
    header('location:/admin/unauthorized_login_admin.php');
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

$HasErrors = false;

$Clean = array();

$Clean['ExamRegistrationID'] = 0;
$Clean['StudentRegistrationID'] = 0;
$Clean['Process'] = 0;

$Clean['ExamID'] = 0;
$Clean['ExamID'] = key($AllExams);
$Clean['RegistrationAmount'] = 0;
$Clean['ExamStatus'] = 'Awaited';

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
		
		$NewRecordValidator = new Validator();

		$NewRecordValidator->ValidateInSelect($Clean['StudentRegistrationID'], $AllStudentRegistrationsList, 'Unknown error, please try again.');
		$NewRecordValidator->ValidateInSelect($Clean['ExamID'], $AllExamsList, 'Unknown error, please try again.');
		$NewRecordValidator->ValidateNumeric($Clean['RegistrationAmount'], 'Registration amount should be integer.', 0);
		
		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}

		$StudentRegistrationDetials = new StudentRegistration($Clean['StudentRegistrationID']);

		$NewExamRegistration = new ExamRegistration();
				
		$NewExamRegistration->SetStudentRegistrationID($Clean['StudentRegistrationID']);
		$NewExamRegistration->SetExamID($Clean['ExamID']);
		$NewExamRegistration->SetRegistrationAmount($Clean['RegistrationAmount']);
		$NewExamRegistration->SetIsActive(1);
		
		$NewExamRegistration->SetCreateUserID($LoggedUser->GetUserID());

		if ($NewExamRegistration->RecordExists())
		{
			$NewRecordValidator->AttachTextError('Exam registration you have added already exists');
			$HasErrors = true;
			break;
		}

		if (!$NewExamRegistration->Save())
		{
			$NewRecordValidator->AttachTextError(ProcessErrors($NewExamRegistration->GetLastErrorCode()));
			$HasErrors = true;
			break;
		}
		
		header('location:exam_registrations_list.php?Mode=ED&Process=7&IsActive=1');
		exit;
		break;
}

require_once('../html_header.php');
?>
<title>Add Exam Registration</title>
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
                    <h1 class="page-header">Add Exam Registration</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
<?php
		if ($Clean['Process'] == 2) 
		{
?>
			<form class="form-horizontal" name="AddExamRegistration" action="add_exam_registration.php" method="post">
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
                                if (is_array($ClassList) && count($ClassList) > 0)
                                {
                                    foreach ($ClassList as $ClassID => $ClassName)
                                    {
                                        echo '<option ' . ($StudentRegistrationDetials->GetClassID() == $ClassID ? 'selected="selected"' : '') . ' value="' . $ClassID . '">' . $ClassName . '</option>';
                                    }
                                }
?>
								</select>
                            </div>
                        </div>
	                	<div class="form-group">
		                	<label for="StudentRegistrationID" class="col-lg-2 control-label">Student Name</label>
	                        <div class="col-lg-4">
	                    		<input class="form-control" type="text" id="StudentRegistrationID" value="<?php echo $StudentRegistrationDetials->GetFirstName() . " " . $StudentRegistrationDetials->GetLastName(); ?>" disabled="disabled">
	                        </div>
	                        <label for="DOB" class="col-lg-2 control-label">Date Of Birth</label>
	                        <div class="col-lg-4">
	                        	<input class="form-control select-date dtepicker" type="text" maxlength="10" id="DOB" value="<?php echo date('d/m/Y', strtotime($StudentRegistrationDetials->GetDOB())); ?>" disabled="disabled" />
	                        </div>
                        </div>
                        <div class="form-group">
                            <label for="FatherName" class="col-lg-2 control-label">Father's Name</label>
                            <div class="col-lg-4">
                                <input class="form-control" type="text" maxlength="50" id="FatherName" name="txtFatherName" value="<?php echo $StudentRegistrationDetials->GetFatherName(); ?>" disabled="disabled"/>
                            </div>
                            <label for="MotherName" class="col-lg-2 control-label">Mother's Name</label>
                            <div class="col-lg-4">
                                <input class="form-control" type="text" maxlength="50" id="MotherName" name="txtMotherName" value="<?php echo $StudentRegistrationDetials->GetMotherName(); ?>" disabled="disabled"/>
                            </div>
                        </div>
	                </div>
	            </div>
	        </form>
<?php
		}
?>
			<form class="form-horizontal" name="AddExamRegistration" action="add_exam_registration.php" method="post">
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
									<option value="<?php echo $StudentRegistrationID;?>" <?php echo($StudentRegistrationID == $Clean['ExamRegistrationID']) ? 'selected="selected"' : '';?> ><?php echo $StudentName; ?></option>
<?php
								}
?>							
								</select>
                            </div>
                            <label for="ExamID" class="col-lg-2 control-label">Exam</label>
                            <div class="col-lg-4">
                        	<select class="form-control Registration" id="ExamID" name="drdExam" >
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
		                    <div class="col-sm-offset-2 col-lg-10">
		                    	<input type="hidden" name="hdnProcess" value="1" />
								<button type="submit" class="btn btn-primary">Save</button>
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
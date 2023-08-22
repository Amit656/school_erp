<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once("../../classes/admission_cell/class.exams.php");

require_once("../../classes/class.date_processing.php");
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

if ($LoggedUser->HasPermissionForTask(TASK_EDIT_ENTRANCE_EXAM) !== true)
{
    header('location:/admin/unauthorized_login_admin.php');
    exit;
}

$Clean = array();

$Clean['ExamID'] = 0;

if (isset($_GET['ExamID']))
{
    $Clean['ExamID'] = (int) $_GET['ExamID'];
}
elseif (isset($_POST['hdnExamID']))
{
    $Clean['ExamID'] = (int) $_POST['hdnExamID'];
}

if ($Clean['ExamID'] <= 0)
{
    header('location:../error.php');
    exit;
}   

try
{
    $ExamToEdit = new Exam($Clean['ExamID']);
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

$HasErrors = false;

$Clean['Process'] = 0;

$Clean['ExamName'] = '';
$Clean['ExamDate'] = '';
$Clean['ExamTime'] = '';
$Clean['ExamDuration'] = 0;

$Clean['MaximumMarks'] = 0;
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
		if (isset($_POST['txtExamName']))
		{
			$Clean['ExamName'] = strip_tags(trim($_POST['txtExamName']));
		}

		if (isset($_POST['txtExamDate']))
		{
			$Clean['ExamDate'] = strip_tags(trim($_POST['txtExamDate']));
		}

		if (isset($_POST['txtExamTime']))
		{
			$Clean['ExamTime'] = strip_tags(trim($_POST['txtExamTime']));
		}

        if (isset($_POST['txtExamDuration']))
        {
            $Clean['ExamDuration'] = strip_tags(trim($_POST['txtExamDuration']));
        }

		if (isset($_POST['txtMaximumMarks']))
		{
			$Clean['MaximumMarks'] = strip_tags(trim($_POST['txtMaximumMarks']));
		}

		if (isset($_POST['chkIsActive']))
        {
            $Clean['IsActive'] = 1;
        }
		
		$NewRecordValidator = new Validator();
		$NewRecordValidator->ValidateStrings($Clean['ExamName'], 'exam name is required and should be between 4 and 30 characters.', 4, 30);
// 		$NewRecordValidator->ValidateDate($Clean['ExamDate'], 'Please enter a valid exam date.');
        // $NewRecordValidator->ValidateStrings($Clean['ExamTime'], 'Please enter valid time.', 1, 8);

        $NewRecordValidator->ValidateInteger($Clean['ExamDuration'], 'Exam duration marks should be integer.', 0);
		$NewRecordValidator->ValidateInteger($Clean['MaximumMarks'], 'Maximum marks should be integer.', 0);
		
		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}
				
		$ExamToEdit->SetExamName($Clean['ExamName']);
// 		$ExamToEdit->SetExamDate(date('Y-m-d', strtotime(DateProcessing::ToggleDateDayAndMonth(($Clean['ExamDate'])))));
// 		$ExamToEdit->SetExamTime($Clean['ExamTime']);
        $ExamToEdit->SetExamDuration($Clean['ExamDuration']);

		$ExamToEdit->SetMaximumMarks($Clean['MaximumMarks']);
		$ExamToEdit->SetIsActive($Clean['IsActive']);
		
		$ExamToEdit->SetCreateUserID($LoggedUser->GetUserID());

        if ($ExamToEdit->RecordExists())
        {
            $NewRecordValidator->AttachTextError('Exam name you have added already exists');
            $HasErrors = true;
            break;
        }

		if (!$ExamToEdit->Save())
		{
			$NewRecordValidator->AttachTextError(ProcessErrors($ExamToEdit->GetLastErrorCode()));
			$HasErrors = true;

			break;
		}
		
		header('location:exams_list.php?Mode=UD');
		exit;
		break;

	case 2:
        $Clean['ExamName'] = $ExamToEdit->GetExamName();
        $Clean['ExamDate'] = $ExamToEdit->GetExamDate();
        $Clean['ExamTime'] = $ExamToEdit->GetExamTime();
        $Clean['ExamDuration'] = $ExamToEdit->GetExamDuration();
        $Clean['MaximumMarks'] = $ExamToEdit->GetMaximumMarks();
        $Clean['IsActive'] = $ExamToEdit->GetIsActive();
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
             <form class="form-horizontal" name="EditExam" action="Edit_exam.php" method="post">
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
                            <label for="ExamName" class="col-lg-3 control-label">Exam Name</label>
                            <div class="col-lg-4">
                            	<input class="form-control" type="text" maxlength="30" id="ExamName" name="txtExamName" value="<?php echo $Clean['ExamName']; ?>" />
                            </div>
                        </div>
                        <!--<div class="form-group">-->
                        <!--    <label for="ExamDate" class="col-lg-3 control-label">Exam Date</label>-->
                        <!--    <div class="col-lg-4">-->
                        <!--    	<input class="form-control dtepicker" type="text" maxlength="10" id="ExamDate" name="txtExamDate" value="<?php echo date('d/m/Y', strtotime($Clean['ExamDate'])); ?>" />-->
                        <!--    </div>-->
                        <!--</div>-->
                        <!--<div class="form-group">-->
                        <!--    <label for="ExamTime" class="col-lg-3 control-label">Exam Time</label>-->
                        <!--    <div class="col-lg-4">-->
                        <!--    	<input class="form-control" type="Time" maxlength="6" id="ExamTime" name="txtExamTime" value="<?php echo $Clean['ExamTime']; ?>" />-->
                        <!--    </div>-->
                        <!--</div>-->
                        <div class="form-group">
                            <label for="ExamDuration" class="col-lg-3 control-label">Exam Duration (In Minutes)</label>
                            <div class="col-lg-4">
                                <input class="form-control" type="text" maxlength="3" id="ExamDuration" name="txtExamDuration" value="<?php echo($Clean['ExamDuration']) ? $Clean['ExamDuration'] : ''; ?>" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="MaximumMarks" class="col-lg-3 control-label">Maximum Marks</label>
                            <div class="col-lg-4">
                            	<input class="form-control" type="text" maxlength="3" id="MaximumMarks" name="txtMaximumMarks" value="<?php echo ($Clean['MaximumMarks']) ? $Clean['MaximumMarks'] : ''; ?>" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="IsActive" class="col-lg-3 control-label">Is Active</label>
                            <div class="col-lg-4">
                                <input type="checkbox" id="IsActive" name="chkIsActive" value="1" <?php echo ($Clean['IsActive'] == 1) ? 'checked="checked"' : ''; ?>/>
                            </div>
                        </div>
                        <div class="form-group">
                        <div class="col-sm-offset-3 col-lg-10">
                        	<input type="hidden" name="hdnProcess" value="3" />
                        	<input type="hidden" name="hdnExamID" value="<?php echo $Clean['ExamID'];?>" />
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
<script src="../vendor/jquery-ui/jquery-ui.min.js"></script>
<script type="text/javascript">
$(document).ready(function()
{	
	$(".dtepicker").datepicker({
            changeMonth: true,
            changeYear: true,
            dateFormat: 'dd/mm/yy'
    });
});
</script>
</body>
</html>
<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once("../../classes/school_administration/class.grades.php");

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

if ($LoggedUser->HasPermissionForTask(TASK_ADD_GRADE) !== true)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}

$HasErrors = false;

$Clean = array();

$Clean['Grade'] = '';
$Clean['FromPercentage'] = '';
$Clean['ToPercentage'] = '';

$Clean['Process'] = 0;

if (isset($_POST['hdnProcess']))
{
	$Clean['Process'] = (int) $_POST['hdnProcess'];
}
switch ($Clean['Process'])
{
	case 1:
		if (isset($_POST['txtGrade']))
		{
			$Clean['Grade'] = strip_tags(trim($_POST['txtGrade']));
		}

		if (isset($_POST['txtFromPercentage']))
		{
			$Clean['FromPercentage'] = strip_tags(trim($_POST['txtFromPercentage']));
		}

		if (isset($_POST['txtToPercentage']))
		{
			$Clean['ToPercentage'] = strip_tags(trim($_POST['txtToPercentage']));
		}
		
		$NewRecordValidator = new Validator();
		
		$NewRecordValidator->ValidateStrings($Clean['Grade'], 'Grade Name is required and should be between 1 and 5 characters.', 1, 5);
		
		$NewRecordValidator->ValidateNumeric($Clean['FromPercentage'], 'From percentage is should be numeric.');
		$NewRecordValidator->ValidateNumeric($Clean['ToPercentage'], 'To percentage is should be numeric.');

		if ($Clean['FromPercentage'] < 0 || $Clean['ToPercentage'] > 100) 
		{
			$NewRecordValidator->AttachTextError('Grade percentage range should be between 0 to 100%.');
		}

		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}
				
		$NewGrade = new Grade();
				
		$NewGrade->SetGrade($Clean['Grade']);
		$NewGrade->SetFromPercentage($Clean['FromPercentage']);
		$NewGrade->SetToPercentage($Clean['ToPercentage']);	

		$NewGrade->SetIsActive(1);
		$NewGrade->SetCreateUserID($LoggedUser->GetUserID());

		if ($NewGrade->RecordExists())
		{
			$NewRecordValidator->AttachTextError('The Grade Name you have added already exists.');
			$HasErrors = true;
			break;
		}

		if (!$NewGrade->Save())
		{
			$NewRecordValidator->AttachTextError(ProcessErrors($NewGrade->GetLastErrorCode()));
			$HasErrors = true;
			break;
		}
		
		header('location:grades_list.php?Mode=ED');
		exit;
	break;
}

require_once('../html_header.php');
?>
<title>Add Grade</title>
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
                    <h1 class="page-header">Add Grade</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="AddGrade" action="add_grade.php" method="post">
            	<div class="panel panel-default">
                    <div class="panel-heading">
                        Enter Grade Details
                    </div>
                    <div class="panel-body">
<?php
						if ($HasErrors == true)
						{
							echo $NewRecordValidator->DisplayErrors();
						}
?>                    
                    	<div class="form-group">
                            <label for="Grade" class="col-lg-3 control-label">Grade</label>
                            <div class="col-lg-4">
                            	<input class="form-control" type="text" maxlength="5" id="Grade" name="txtGrade" value="<?php echo $Clean['Grade']; ?>" />
                            </div>
                        </div>
                        <div class="form-group">
                        	<label for="FromPercentage" class="col-lg-3 control-label">From Percentage</label>
                            <div class="col-lg-4">
                            	<div class="input-group">
	                        		<input class="form-control" type="text" maxlength="5" id="FromPercentage" name="txtFromPercentage" value="<?php echo $Clean['FromPercentage']; ?>" />
	                        		<span class="input-group-addon">%</span>
	                        	</div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="ToPercentage" class="col-lg-3 control-label">To Percentage</label>
                            <div class="col-lg-4">
                            	<div class="input-group">
	                        		<input class="form-control" type="text" maxlength="5" id="ToPercentage" name="txtToPercentage" value="<?php echo $Clean['ToPercentage']; ?>" />
	                        		<span class="input-group-addon">%</span>
	                        	</div>
                            </div>
                        </div>
                        <div class="form-group">
                        <div class="col-sm-offset-3 col-lg-10">
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
</body>
</html>
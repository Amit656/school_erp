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

if ($LoggedUser->HasPermissionForTask(TASK_EDIT_GRADE) !== true)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}

$UserMenusArray = array();
$UserMenusArray = $LoggedUser->GetUserMenus();

if (!is_array($UserMenusArray) || count($UserMenusArray) <= 0)
{
	//header('location:../logout.php');
	//exit;
}

$Clean = array();

$Clean['GradeID'] = 0;

if (isset($_GET['GradeID']))
{
    $Clean['GradeID'] = (int) $_GET['GradeID'];
}
elseif (isset($_POST['hdnGradeID']))
{
    $Clean['GradeID'] = (int) $_POST['hdnGradeID'];
}

if ($Clean['GradeID'] <= 0)
{
    header('location:../error.php');
    exit;
} 

try
{
   $GradeToEdit = new Grade($Clean['GradeID']);
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

$Clean['Grade'] = '';
$Clean['FromPercentage'] = '';
$Clean['ToPercentage'] = '';

$Clean['IsActive'] = 0;

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
	case 3:
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

		if (isset($_POST['chkIsActive']))
        {
            $Clean['IsActive'] = 1;
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
				
		$GradeToEdit->SetGrade($Clean['Grade']);
		$GradeToEdit->SetFromPercentage($Clean['FromPercentage']);
		$GradeToEdit->SetToPercentage($Clean['ToPercentage']);	
		$GradeToEdit->SetIsActive($Clean['IsActive']);

		$GradeToEdit->SetIsActive(1);
		$GradeToEdit->SetCreateUserID($LoggedUser->GetUserID());

		if ($GradeToEdit->RecordExists())
		{
			$NewRecordValidator->AttachTextError('The Grade Name you have added already exists.');
			$HasErrors = true;
			break;
		}

		if (!$GradeToEdit->Save())
		{
			$NewRecordValidator->AttachTextError(ProcessErrors($GradeToEdit->GetLastErrorCode()));
			$HasErrors = true;
			break;
		}
		
		header('location:grades_list.php?Mode=UD');
		exit;
	break;

	case 2:
		$Clean['Grade'] = $GradeToEdit->GetGrade();
		$Clean['FromPercentage'] = $GradeToEdit->GetFromPercentage();
		$Clean['ToPercentage'] = $GradeToEdit->GetToPercentage();
        $Clean['IsActive'] = $GradeToEdit->GetIsActive();
	break;
}

require_once('../html_header.php');
?>
<title>Edit Grade</title>
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
                    <h1 class="page-header">Edit Grade</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="EditGrade" action="edit_grade.php" method="post">
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
                            <label for="IsActive" class="col-lg-3 control-label">Is Active</label>
                            <div class="col-lg-4">
                                <input type="checkbox" id="IsActive" name="chkIsActive" value="1" <?php echo ($Clean['IsActive'] == 1) ? 'checked="checked"' : ''; ?>/>
                            </div>
                        </div>
                        <div class="form-group">
                        <div class="col-sm-offset-3 col-lg-10">
                        	<input type="hidden" name="hdnProcess" value="3" />
                        	<input type="hidden" name="hdnGradeID" value="<?php echo $Clean['GradeID']; ?>" />
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
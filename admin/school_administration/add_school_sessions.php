<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once("../../classes/school_administration/class.school_sessions.php");

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

if ($LoggedUser->HasPermissionForTask(TASK_ADD_SCHOOL_SESSION) !== true)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}

$HasErrors = false;

$Clean = array();
$Clean['Process'] = 0;

$Clean['SessionName'] = '';
$Clean['SessionDesciption'] = '';
$Clean['Priority'] = '';

if (isset($_POST['hdnProcess']))
{
	$Clean['Process'] = (int) $_POST['hdnProcess'];
}
switch ($Clean['Process'])
{
	case 1:		
		if (isset($_POST['txtSessionName']))
		{
			$Clean['SessionName'] = strip_tags(trim($_POST['txtSessionName']));
		}				
		if (isset($_POST['txtSessionDesciption']))
		{
			$Clean['SessionDesciption'] = strip_tags(trim($_POST['txtSessionDesciption']));
		}

		if (isset($_POST['txtSessionPriority']))
		{
			$Clean['Priority'] = strip_tags(trim($_POST['txtSessionPriority']));
		}

		$NewRecordValidator = new Validator();
		$NewRecordValidator->ValidateStrings($Clean['SessionName'], "Session Name is required and should be between 1 and 50 characters.", 1, 50);
		
		$NewRecordValidator->ValidateStrings($Clean['SessionDesciption'], "Session Desciption is required and should be between 1 and 500 characters.", 1, 500);
		if(!empty($Clean['Priority']))
		{
			$NewRecordValidator->ValidateInteger($Clean['Priority'], "Priority is required And should greater than 0 .", 1);
		}

		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}
				
		$NewSchoolSessions = new SchoolSessions();

		$NewSchoolSessions->SetSessionName($Clean['SessionName']);
				
		$NewSchoolSessions->SetSessionDesciption($Clean['SessionDesciption']);
		$NewSchoolSessions->SetPriority($Clean['Priority']);

		$NewSchoolSessions->SetIsActive(1);
		
		$NewSchoolSessions->SetCreateUserID($LoggedUser->GetUserID());

		if (!$NewSchoolSessions->Save())
		{
			$NewRecordValidator->AttachTextError(ProcessErrors($NewSchoolSessions->GetLastErrorCode()));
			$HasErrors = true;

			break;
		}
		
		header('location:school_sessions_list.php?Mode=ED');
		exit;
	break;
}

require_once('../html_header.php');
?>
<title>Add School Sessions</title>
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
                    <h1 class="page-header">Add School Sessions</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="AddSchoolSessions" action="add_school_sessions.php" method="post">
            	<div class="panel panel-default">
                    <div class="panel-heading">
                        Enter School Sessions Details
                    </div>
                    <div class="panel-body">
<?php
						if ($HasErrors == true)
						{
							echo $NewRecordValidator->DisplayErrors();
						}
?>                    
                    	<div class="form-group">
                            <label for="AcademicYear" class="col-lg-2 control-label">Session Name</label>
                            <div class="col-lg-4">
                            	<input class="form-control" type="text" maxlength="50" id="SessionName" name="txtSessionName" value="<?php echo $Clean['SessionName']; ?>" />
                            </div>
                        </div>
                    	<div class="form-group">
                            <label for="ClassName" class="col-lg-2 control-label">Session Desciption</label>
                            <div class="col-lg-4">
                            	<textarea class="form-control" rows="4" cols="10" id="SessionDesciption" name="txtSessionDesciption"><?php echo $Clean['SessionDesciption']; ?></textarea>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="Priority" class="col-lg-2 control-label">Session Priority</label>
                            <div class="col-lg-4">
                            	<input class="form-control" type="text" maxlength="3" id="SessionPriority" name="txtSessionPriority" value="<?php echo $Clean['Priority']; ?>" />
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
</body>
</html>
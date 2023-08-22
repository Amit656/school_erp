<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once("../../classes/school_administration/class.academic_years.php");

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
	header('location:../unauthorized_login_admin.php');
	exit;
}
catch (Exception $e)
{
	header('location:../unauthorized_login_admin.php');
	exit;
}
// END OF 1. //

$UserMenusArray = array();
$UserMenusArray = $LoggedUser->GetUserMenus();

if (!is_array($UserMenusArray) || count($UserMenusArray) <= 0)
{
	//header('location:../logout.php');
	//exit;
}

$HasErrors = false;

$Clean = array();
$Clean['Process'] = 0;

$Clean['StartDate'] = '';
$Clean['EndDate'] = '';

if (isset($_POST['hdnProcess']))
{
	$Clean['Process'] = (int) $_POST['hdnProcess'];
}
switch ($Clean['Process'])
{
	case 1:						
		if (isset($_POST['txtStartDate']))
		{
			$Clean['StartDate'] = strip_tags(trim($_POST['txtStartDate']));
		}

		if (isset($_POST['txtEndDate']))
		{
			$Clean['EndDate'] = strip_tags(trim($_POST['txtEndDate']));
		}

		$NewRecordValidator = new Validator();
		$NewRecordValidator->ValidateDate($Clean['StartDate'], "Please enter a valid start date.");
		$NewRecordValidator->ValidateDate($Clean['EndDate'], "Please enter a valid end date.");
		
		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}
				
		$NewAcademicYear = new AcademicYear();
				
		$NewAcademicYear->SetStartDate(date('Y-m-d', strtotime(DateProcessing::ToggleDateDayAndMonth(($Clean['StartDate'])))));
		$NewAcademicYear->SetEndDate(date('Y-m-d', strtotime(DateProcessing::ToggleDateDayAndMonth(($Clean['EndDate'])))));	
		
		$NewAcademicYear->SetCreateUserID($LoggedUser->GetUserID());

		if ($NewAcademicYear->RecordExists())
		{
			$NewRecordValidator->AttachTextError('The academic year you have added already exists.');
			$HasErrors = true;
			break;
		}

		if (!$NewAcademicYear->Save())
		{
			$NewRecordValidator->AttachTextError(ProcessErrors($NewAcademicYear->GetLastErrorCode()));
			$HasErrors = true;

			break;
		}
		
		header('location:academic_years_list.php?Mode=AS');
		exit;
	break;
}

require_once('../html_header.php');
?>
<title>Add Academic Year</title>
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
                    <h1 class="page-header">Add Academic Year</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="AddAcademicYear" action="add_academic_year.php" method="post">
            	<div class="panel panel-default">
                    <div class="panel-heading">
                        Enter Academic Year Details
                    </div>
                    <div class="panel-body">
<?php
						if ($HasErrors == true)
						{
							echo $NewRecordValidator->DisplayErrors();
						}
?>                    
                    	<div class="form-group">
                            <label for="StartDate" class="col-lg-2 control-label">Start Date</label>
                            <div class="col-lg-4">
                            	<input class="form-control select-date" type="text" maxlength="10" id="StartDate" name="txtStartDate" value="<?php echo $Clean['StartDate']; ?>" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="EndDate" class="col-lg-2 control-label">End Date</label>
                            <div class="col-lg-4">
                            	<input class="form-control select-date" type="text" maxlength="10" id="EndDate" name="txtEndDate" value="<?php echo $Clean['EndDate']; ?>" />
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
	<script src="../vendor/jquery-ui/jquery-ui.min.js"></script>
	<script type="text/javascript">
	$(document).ready(function() {
		$(".select-date").datepicker({
				changeMonth: true,
				changeYear: true,
				dateFormat: 'dd/mm/yy'
		});
	});
    </script>
</body>
</html>
<?php
// ob_start();
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once("../../classes/examination/class.difficulty_levels.php");

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

if ($LoggedUser->HasPermissionForTask(TASK_ADD_DIFFICULTY_LEVEL) !== true)
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

$HasErrors = false;

$Clean = array();
$Clean['Process'] = 0;

$Clean['DifficultyLevel'] = '';

if (isset($_POST['hdnProcess']))
{	
	$Clean['Process'] = (int) $_POST['hdnProcess'];
}
switch ($Clean['Process'])
{
	case 1:
		if (isset($_POST['txtDifficultyLevel']))
		{
			$Clean['DifficultyLevel'] = strip_tags(trim($_POST['txtDifficultyLevel']));
		}

		$NewRecordValidator = new Validator();

		$NewRecordValidator->ValidateStrings($Clean['DifficultyLevel'], 'Difficulty level is required and should be between 1 and 100 characters.', 1, 100);
		
		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}
				
		$NewDifficultyLevel = new DifficultyLevel();
		$NewDifficultyLevel->SetDifficultyLevel($Clean['DifficultyLevel']);

		$NewDifficultyLevel->SetIsActive(1);
		
		// if ($NewDifficultyLevel->RecordExists())
		// {
		// 	$NewRecordValidator->AttachTextError('The difficulty level you have added already exists.');
		// 	$HasErrors = true;
		// 	break;
		// }
		
		if (!$NewDifficultyLevel->Save())
		{
			$NewRecordValidator->AttachTextError(ProcessErrors($NewDifficultyLevel->GetLastErrorCode()));
			$HasErrors = true;
			break;
		}
		
		header('location:difficulty_level_list.php?Mode=AS');
		exit;
	break;
}

require_once('../html_header.php');
?>
<title>Add Difficulty Level</title>
<link href="vendor/jquery-ui/jquery-ui.min.css" rel="stylesheet">
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
                    <h1 class="page-header">Add Difficulty Level</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="AddDifficultyLevel" action="add_difficulty_level.php" method="post">
            	<div class="panel panel-default">
                    <div class="panel-heading">
                        <strong>Enter Difficulty Level Details</strong>
                    </div>
                    <div class="panel-body">
<?php
						if ($HasErrors == true)
						{
							echo $NewRecordValidator->DisplayErrors();
						}
?>       

						<div class="form-group">
                            <label for="DifficultyLevel" class="col-lg-2 control-label">Difficulty Level</label>
                            <div class="col-lg-4">
                            	<input class="form-control" type="text" maxlength="100" id="DifficultyLevel" name="txtDifficultyLevel" value="<?php echo $Clean['DifficultyLevel']; ?>" />
                            </div>
                        </div>
                        <div class="form-group">
                        <div class="col-sm-offset-2 col-lg-10">
                        	<input type="hidden" name="hdnProcess" value="1" />
							<button type="submit" class="btn btn-primary"><i class="fa fa-save"></i>&nbsp;Save</button>
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
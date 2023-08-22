<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once("../../classes/hostel_management/class.wings.php");

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

$WingForlist = array('Boys' => 'Boys', 'Girls' => 'Girls', 'Both' => 'Both');

$HasErrors = false;

$Clean = array();
$Clean['Process'] = 0;

$Clean['WingFor'] = '';
$Clean['WingName'] = '';

if (isset($_POST['hdnProcess']))
{
	$Clean['Process'] = (int) $_POST['hdnProcess'];
}
switch ($Clean['Process'])
{
	case 1:
		if (isset($_POST['drdWingFor']))
		{
			$Clean['WingFor'] = strip_tags(trim($_POST['drdWingFor']));
		}

		if (isset($_POST['txtWingName']))
		{
			$Clean['WingName'] = strip_tags(trim($_POST['txtWingName']));
		}

		$NewRecordValidator = new Validator();

		$NewRecordValidator->ValidateInSelect($Clean['WingFor'], $WingForlist, 'Unknown Error, Please try again.');
		$NewRecordValidator->ValidateStrings($Clean['WingName'], 'Wing name is required and should be between 1 and 25 characters.', 1, 25);
		
		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}
				
		$NewWing = new Wing();
				
		$NewWing->SetWingFor($Clean['WingFor']);
		$NewWing->SetWingName($Clean['WingName']);

		$NewWing->SetIsActive(1);
		$NewWing->SetCreateUserID($LoggedUser->GetUserID());

		if (!$NewWing->Save())
		{
			$NewRecordValidator->AttachTextError(ProcessErrors($NewWing->GetLastErrorCode()));
			$HasErrors = true;
			break;
		}
		
		header('location:wings_list.php?Mode=AS');
		exit;
	break;
}

require_once('../html_header.php');
?>
<title>Add Wing</title>
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
                    <h1 class="page-header">Add Wing</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="AddWing" action="add_wing.php" method="post">
            	<div class="panel panel-default">
                    <div class="panel-heading">
                        Enter Wing Details
                    </div>
                    <div class="panel-body">
<?php
						if ($HasErrors == true)
						{
							echo $NewRecordValidator->DisplayErrors();
						}
?>                    
                    	<div class="form-group">
						<label for="WingFor" class="col-lg-2 control-label">Wing For</label>
                            <div class="col-lg-4">
                            	<select class="form-control" name="drdWingFor" id="WingFor">
<?php
								if (is_array($WingForlist) && count($WingForlist) > 0)
								{
									foreach($WingForlist as $WingForID => $WingForName)
									{
										echo '<option ' . (($Clean['WingFor'] == $WingForID) ? 'selected="selected"' : '' ) . ' value="' . $WingForID . '">' . $WingForName . '</option>';
									}
								}
?>
								</select>
                            </div>
                        </div>
						<div class="form-group">
                            <label for="WingName" class="col-lg-2 control-label">Wing Name</label>
                            <div class="col-lg-4">
                            	<input class="form-control" type="text" maxlength="25" id="WingName" name="txtWingName" value="<?php echo $Clean['WingName']; ?>" />
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
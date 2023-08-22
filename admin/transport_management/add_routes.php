<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once("../../classes/transport_management/class.routes.php");

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

if ($LoggedUser->HasPermissionForTask(TASK_ADD_ROUTES) !== true)
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

$Clean['RouteNumber'] = '';

$Clean['RouteName'] = '';

if (isset($_POST['hdnProcess']))
{
	$Clean['Process'] = (int) $_POST['hdnProcess'];
}
switch ($Clean['Process'])
{
	case 1:
		if (isset($_POST['txtRouteNumber']))
		{
			$Clean['RouteNumber'] = strip_tags(trim($_POST['txtRouteNumber']));
		}

		if (isset($_POST['txtRouteName']))
		{
			$Clean['RouteName'] = strip_tags(trim($_POST['txtRouteName']));
		}
		
		$NewRecordValidator = new Validator();
		$NewRecordValidator->ValidateStrings($Clean['RouteNumber'], 'Route number is required and should be between 1 and 50 characters.', 1, 50);

		$NewRecordValidator->ValidateStrings($Clean['RouteName'], 'Route name is required and should be between 1 and 250 characters.', 1, 250);
		
		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}
				
		$NewRoute = new Route();

		$NewRoute->SetRouteNumber($Clean['RouteNumber']);
		$NewRoute->SetRouteName($Clean['RouteName']);
		$NewRoute->SetIsActive(1);
		$NewRoute->SetCreateUserID($LoggedUser->GetUserID());

		if ($NewRoute->RecordExists())
        {
            $NewRecordValidator->AttachTextError('The route name you have added already exists.');
            $NewRecordValidator->AttachTextError('The route number you have added already exists.');
            var_dump($NewRecordValidator);exit;
            $HasErrors = true;
            break;
        }
		
		if (!$NewRoute->Save())
		{
			$NewRecordValidator->AttachTextError(ProcessErrors($NewRoute->GetLastErrorCode()));
			$HasErrors = true;
			break;
		}
		
		header('location:route_list.php?Mode=AS');
		exit;
	break;
}

require_once('../html_header.php');
?>
<title>Add Routes</title>
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
                    <h1 class="page-header">Add Route</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="AddRoutes" action="add_routes.php" method="post">
            	<div class="panel panel-default">
                    <div class="panel-heading">
                        Enter Root Details
                    </div>
                    <div class="panel-body">
<?php
						if ($HasErrors == true)
						{
							echo $NewRecordValidator->DisplayErrors();
						}
?>                    
						<div class="form-group">
                            <label for="RouteNumber" class="col-lg-2 control-label">Add Root Number</label>
                            <div class="col-lg-4">
                            	<input class="form-control" type="text" maxlength="50" id="RouteNumber" name="txtRouteNumber" value="<?php echo $Clean['RouteNumber']; ?>" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="RouteName" class="col-lg-2 control-label">Add Root Name</label>
                            <div class="col-lg-4">
                                <input class="form-control" type="text" maxlength="250" id="RouteName" name="txtRouteName" value="<?php echo ($Clean['RouteName'] ? $Clean['RouteName'] : ''); ?>" />
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
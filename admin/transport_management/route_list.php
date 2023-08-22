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

if ($LoggedUser->HasPermissionForTask(TASK_LIST_ROUTES) !== true)
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
$RecordDeletedSuccessfully = false;

// $VehicleRoutesListForGraph = array();
// $VehicleRoutesListForGraph = Route::GetAllVehicleRoutesForGraphs();

$Clean = array();
$Clean['Process'] = 0;

$Clean['RouteID'] = 0;

if (isset($_GET['Process']))
{
	$Clean['Process'] = (int) $_GET['Process'];
}
switch ($Clean['Process'])
{
	case 5:
		if ($LoggedUser->HasPermissionForTask(TASK_DELETE_ROUTES) !== true)
		{
			header('location:unauthorized_login_admin.php');
			exit;
		}
		
		if (isset($_GET['RouteID']))
		{
			$Clean['RouteID'] = (int) $_GET['RouteID'];			
		}
		
		if ($Clean['RouteID'] <= 0)
		{
			header('location:../error_page.php');
			exit;
		}						
			
		try
		{
			$RouteToDelete = new Route($Clean['RouteID']);
		}
		catch (ApplicationDBException $e)
		{
			header('location:../error_page.php');
			exit;
		}
		catch (Exception $e)
		{
			header('location:../error_page.php');
			exit;
		}
		
		$RecordValidator = new Validator();
		
		// if ($RouteToDelete->CheckDependencies())
		// {
		// 	$RecordValidator->AttachTextError('This ruote cannot be deleted. There are dependent records for this head.');
		// 	$HasErrors = true;
		// 	break;
		// }
				
		if (!$RouteToDelete->Remove())
		{
			$RecordValidator->AttachTextError(ProcessErrors($RouteToDelete->GetLastErrorCode()));
			$HasErrors = true;
			break;
		}
		
		$RecordDeletedSuccessfully = true;
	break;
}

$AllRoutes = array();
$AllRoutes = Route::GetAllRoutes();

$LandingPageMode = '';
if (isset($_GET['Mode']))
{
    $LandingPageMode = $_GET['Mode'];
}

require_once('../html_header.php');
?>
<title>Route List</title>
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
                    <h1 class="page-header">Route List</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
            <!-- /.row -->
            <div class="row">
                <div class="col-lg-12">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <strong>Total Records Returned: <?php echo count($AllRoutes); ?></strong>
                        </div>
                        <!-- /.panel-heading -->
                        <div class="panel-body">
                            <div>
                                <div class="row">
                                    <div class="col-lg-6">
                                    <div class="add-new-btn-container"><a href="add_routes.php" class="btn btn-primary<?php //echo $LoggedUser->HasPermissionForTask(TASK_ADD_MENU) === true ? '' : ' disabled'; ?>" role="button">Add New Route</a></div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="print-btn-container"><button id="PrintButton" type="submit" class="btn btn-primary">Print</button></div>
                                    </div>
                                </div>
<?php
                            if ($HasErrors == true)
                            {
                                echo $RecordValidator->DisplayErrorsInTable();
                            }
                            else if ($LandingPageMode == 'AS')
                            {
                                echo '<div class="alert alert-success alert-top-margin">Record saved successfully.</div>';
                            }
                            else if ($RecordDeletedSuccessfully == true)
                            {
                                echo '<div class="alert alert-danger alert-top-margin">Record deleted successfully.</div>';
                            }
                            else if ($LandingPageMode == 'UD')
                            {
                                echo '<div class="alert alert-success alert-top-margin">Record updated successfully.</div>';
                            }
?>
                                <div class="row" id="RecordTableHeading">
                                    <div class="col-lg-12">
                                    	<div class="report-heading-container"><strong>Route Details on <?php echo date('d-m-Y h:i A'); ?></strong></div>
                                    </div>
								</div>
                                <div class="row" id="RecordTable">
                                    <div class="col-lg-12">
                                        <table width="100%" class="table table-striped table-bordered table-hover">
                                            <thead>
                                                <tr>
                                                    <th>S. No</th>
                                                    <th>Route Number</th>
                                                    <th>Route Name</th>
                                                    <th>Is Active</th>
                                                    <th>Create User</th>
                                                    <th>Create Date</th>
                                                    <th class="print-hidden">Operations</th>
                                                </tr>
                                            </thead>
                                            <tbody>
<?php
                                    if (is_array($AllRoutes) && count($AllRoutes) > 0)
                                    {
                                        $Counter = 0;
                                        foreach ($AllRoutes as $RouteID => $RouteDetails)
                                        {
?>
                                                <tr>
                                                    <td><?php echo ++$Counter; ?></td>
                                                    <td><?php echo $RouteDetails['RouteNumber']; ?></td>
                                                    <td><?php echo $RouteDetails['RouteName']; ?></td>
                                                    <td><?php echo (($RouteDetails['IsActive']) ? 'Yes' : 'No'); ?></td>
                                                    <td><?php echo $RouteDetails['CreateUserName']; ?></td>
                                                    <td><?php echo date('d/m/Y', strtotime($RouteDetails['CreateDate'])); ?></td>
                                                    <td class="print-hidden">
<?php
                                                    // echo ('<a href="edit_routes.php?Process=2&amp;RouteID=' . $RouteID . '">Edit</a>');
                                                    // echo '&nbsp;|&nbsp;';
                                                    // echo ('<a href="route_list.php?Process=5&amp;RouteID=' . $RouteID . '" class="delete-record">Delete</a>'); 
                                                    
                                                    if ($LoggedUser->HasPermissionForTask(TASK_EDIT_ROUTES) === true)
                                                    {
                                                        echo '<a href="edit_routes.php?Process=2&amp;RouteID=' . $RouteID . '">Edit</a>';
                                                    }
                                                    else
                                                    {
                                                        echo 'Edit';
                                                    }

                                                    echo '&nbsp;|&nbsp;';

                                                    if ($LoggedUser->HasPermissionForTask(TASK_DELETE_ROUTES) === true)
                                                    {
                                                        echo '<a href="route_list.php?Process=5&amp;RouteID=' . $RouteID . '" class="delete-record">Delete</a>'; 
                                                    }
                                                    else
                                                    {
                                                        echo 'Delete';
                                                    }
?>
                                                    </td>
                                                </tr>
    <?php
                                        }
                                    }
                                    else
                                    {
    ?>
                                                <tr>
                                                    <td colspan="9">No Records</td>
                                                </tr>
    <?php
                                    }
    ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <!-- /.table-responsive -->
                        </div>
                        <!-- /.panel-body -->
                    </div>
                    <!-- /.panel -->
                </div>
                <!-- /.col-lg-12 -->
                 <!--<center> <div class="col-md-6" id="Piechart" style="width:900px; height:600px;"></div></center> -->
            </div>
        </div>
        <!-- /#page-wrapper -->

    </div>
    <!-- /#wrapper -->
<?php
require_once('../footer.php');
?>
<script type="text/javascript">
$(document).ready(function() {
	$(".delete-record").click(function()
    {	
        if (!confirm("Are you sure you want to delete this Route?")) 
        {
            return false;
        }
    });
});
</script>
<!-- <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script> -->
<!-- JavaScript To Print A Report -->
<script src="/admin/js/print-report.js"></script>
</body>
</html>
<?php
require_once('../classes/class.users.php');
require_once('../classes/class.validation.php');
require_once('../classes/class.authentication.php');

require_once('../classes/class.roles.php');

require_once('../classes/class.ui_helpers.php');

require_once('../includes/global_defaults.inc.php');

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

if ($LoggedUser->HasPermissionForTask(TASK_USER_REPORT) !== true)
{
	header('location:unauthorized_login_admin.php');
	exit;
}

$Filters = array();

$AllRoles = array();
$AllRoles = Role::GetActiveRoles();

$UserList = array();

$HasErrors = false;
$TotalRecords = 0;

$Clean = array();
$Clean['Process'] = 0;

$Clean['UserName'] = '';
$Clean['RoleID'] = 0;
$Clean['ActiveStatus'] = 0;

// paging and sorting variables start here 	//
$Clean['CurrentPage'] = 1;
$TotalPages = 0;

$Start = 0;
$Limit = 3;
// end of paging variables		//

if (isset($_GET['hdnProcess']))
{
	$Clean['Process'] = (int) $_GET['hdnProcess'];
}
elseif (isset($_GET['Process']))
{
	$Clean['Process'] = (int) $_GET['Process'];
}
switch ($Clean['Process'])
{
	case 7:
		if (isset($_GET['txtUserName']))
		{
			$Clean['UserName'] = strip_tags(trim($_GET['txtUserName']));
		}
		elseif (isset($_GET['UserName']))
		{
			$Clean['UserName'] = strip_tags(trim( (string) $_GET['UserName']));
		}
		
		if (isset($_GET['drdRole']))
		{
			$Clean['RoleID'] = (int) $_GET['drdRole'];
		}
		elseif (isset($_GET['RoleID']))
		{
			$Clean['RoleID'] = (int) $_GET['RoleID'];
		}

		if (isset($_GET['optActiveStatus']))
		{
			$Clean['ActiveStatus'] = (int) $_GET['optActiveStatus'];
		}
		elseif (isset($_GET['ActiveStatus']))
		{
			$Clean['ActiveStatus'] = (int) $_GET['ActiveStatus'];
		}

		$SearchValidator = new Validator();

		if ($Clean['UserName'] != '')
		{
			$SearchValidator->ValidateStrings($Clean['UserName'], 'User name should be between 4 and 12 characters.', 4, 12);
		}

		if ($Clean['RoleID'] != 0)
		{
			$SearchValidator->ValidateInSelect($Clean['RoleID'], $AllRoles, 'Unknown Error, Please try again.');
		}

		if ($Clean['ActiveStatus'] < 0 || $Clean['ActiveStatus'] > 2)
		{
			$SearchValidator->AttachTextError('Unknown Error, Please try again.');
		}
		
		if ($SearchValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}

		//set record filters
		$Filters['UserName'] = $Clean['UserName'];
		$Filters['RoleID'] = $Clean['RoleID'];
		$Filters['ActiveStatus'] = $Clean['ActiveStatus'];

		//get records count
		User::SearchSystemAdminUsers($TotalRecords, true, $Filters);

		if ($TotalRecords > 0)
		{
			// Paging and sorting calculations start here.
			$TotalPages = (($TotalRecords % $Limit) == 0) ? $TotalRecords / $Limit : floor($TotalRecords / $Limit) + 1;

			if (isset($_GET['CurrentPage']))
			{
				$Clean['CurrentPage'] = (int) $_GET['CurrentPage'];
			}

			if ($Clean['CurrentPage'] <= 0)
			{
				$Clean['CurrentPage'] = 1;
			}
			elseif ($Clean['CurrentPage'] > $TotalPages)
			{
				$Clean['CurrentPage'] = $TotalPages;
			}

			if ($Clean['CurrentPage'] > 1)
			{
				$Start = ($Clean['CurrentPage'] - 1) * $Limit;
			}
			// end of Paging and sorting calculations.
			// now get the actual  records
			$UserList = User::SearchSystemAdminUsers($TotalRecords, false, $Filters, $Start, $Limit);
		}
		break;
}

require_once('html_header.php');
?>
<title>User Report</title>
<!-- DataTables CSS -->
<link href="vendor/datatables-plugins/dataTables.bootstrap.css" rel="stylesheet">

<!-- DataTables Responsive CSS -->
<link href="vendor/datatables-responsive/dataTables.responsive.css" rel="stylesheet">
</head>

<body>

    <div id="wrapper">
    	<!-- Navigation -->
        <nav class="navbar navbar-default navbar-static-top" role="navigation" style="margin-bottom: 0">
<?php 
			require_once('site_header.php');
			require_once('left_navigation_menu.php');
?>                    
            <!-- /.navbar-static-side -->
        </nav>

        <div id="page-wrapper">
            <div class="row">
                <div class="col-lg-12">
                    <h1 class="page-header">User Report</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
            <form class="form-horizontal" name="frmUserReport" action="users_report.php" method="get">
                <div class="panel panel-default" id="accordion">
                    <div class="panel-heading">
						<strong><a data-toggle="collapse" data-parent="#accordion" href="#collapseOne">Filters</a></strong>
                    </div>
                    <div id="collapseOne" class="panel-collapse collapse in">
                        <div class="panel-body">
<?php
                            if ($HasErrors == true)
                            {
                                echo $SearchValidator->DisplayErrors();
                            }
?>
                            <div class="form-group">
                                <label for="UserName" class="col-lg-2 control-label">By User Name</label>
                                <div class="col-lg-4">
									<input class="form-control" type="text" maxlength="12" id="UserName" name="txtUserName" value="<?php echo $Clean['UserName']; ?>" />
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="Role" class="col-lg-2 control-label">By User Role</label>
                                <div class="col-lg-4">
									<select class="form-control" name="drdRole" id="Role">
										<option<?php echo (($Clean['RoleID'] == 0) ? ' selected="selected"' : ''); ?> value="0">All Rolls</option>
<?php
							if (is_array($AllRoles) && count($AllRoles) > 0)
							{
								foreach($AllRoles as $RoleID => $RoleName)
								{
									if ($Clean['RoleID'] != $RoleID)
									{
?>
										<option value="<?php echo $RoleID; ?>"><?php echo $RoleName; ?></option>
<?php
									}
									else
									{
?>
										<option selected="selected" value="<?php echo $RoleID; ?>"><?php echo $RoleName; ?></option>
<?php
									}
								}
							}
?>
									</select>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="ActiveStatus" class="col-lg-2 control-label">By Status</label>
                                <div class="col-lg-6">
                                    <label class="radio-inline">
                                        <input type="radio" <?php echo (($Clean['ActiveStatus'] == 0) ? 'checked="checked"' : ''); ?> name="optActiveStatus" id="ActiveStatus" value="0" checked>All Records
                                    </label>
                                    <label class="radio-inline">
                                        <input type="radio" <?php echo (($Clean['ActiveStatus'] == 1) ? 'checked="checked"' : ''); ?> name="optActiveStatus" id="ActiveStatus" value="1">Active Records
                                    </label>
                                    <label class="radio-inline">
                                        <input type="radio" <?php echo (($Clean['ActiveStatus'] == 2) ? 'checked="checked"' : ''); ?> name="optActiveStatus" id="ActiveStatus" value="2">In-Active Records
                                    </label>
                                </div>
                            </div>                    
                            <div class="form-group">
                                <div class="col-lg-offset-2 col-lg-10">
                                    <input type="hidden" name="hdnProcess" value="7" />
                                    <button type="submit" class="btn btn-primary">Search</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    </div>
            </form>
            <!-- /.row -->
<?php
		if ($Clean['Process'] == 7 && $HasErrors == false)
		{
			$ReportHeaderText = '';

			if ($Clean['UserName'] != '')
			{
				$ReportHeaderText .= ' User Name: ' . $Clean['UserName'] . ',';
			}

			if ($Clean['RoleID'] != 0)
			{
				$ReportHeaderText .= ' Role: ' . $AllRoles[$Clean['RoleID']] . ',';
			}

			if ($Clean['ActiveStatus'] == 1)
			{
				$ReportHeaderText .= ' Status: Active,';
			}
			else if ($Clean['ActiveStatus'] == 2)
			{
				$ReportHeaderText .= ' Status: In-Active,';
			}

			if ($ReportHeaderText != '')
			{
				$ReportHeaderText = ' for' . rtrim($ReportHeaderText, ',');
			}
?>
            <div class="row">
                <div class="col-lg-12">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <strong>Total Records Returned: <?php echo $TotalRecords; ?></strong>
                        </div>
                        <!-- /.panel-heading -->
                        <div class="panel-body">
                            <div>
                            	<div class="row">
                                    <div class="col-lg-6">
<?php
									if ($TotalPages > 1)
									{
										$AllParameters = array('Process' => '7', 'UserName' => $Clean['UserName'], 'RoleID' => $Clean['RoleID'], 'ActiveStatus' => $Clean['ActiveStatus']);
										echo UIHelpers::GetPager('users_report.php', $TotalPages, $Clean['CurrentPage'], $AllParameters);
									}
?>                                        
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="print-btn-container"><button id="PrintButton" type="submit" class="btn btn-primary">Print</button></div>
                                    </div>
                                </div>
                                <div class="row" id="RecordTableHeading">
                                    <div class="col-lg-12">
                                    	<div class="report-heading-container"><strong>User Report on <?php echo date('d-m-Y h:i A') . $ReportHeaderText; ?></strong></div>
                                    </div>
								</div>
                                <div class="row" id="RecordTable">
                                    <div class="col-lg-12">
                                        <table width="100%" class="table table-striped table-bordered table-hover" id="DataTableRecords">
                                            <thead>
                                                <tr>
                                                    <th>S.No</th>
                                                    <th>User Name</th>
                                                    <th>Role</th>
                                                    <th>Time Limit</th>
                                                    <th>Login Time</th>
													<th>Is Active</th>
													<th class="print-hidden">User Tasks</th>
													<th class="print-hidden">Operations</th>
                                                </tr>
                                            </thead>
                                            <tbody>
<?php
									if (is_array($UserList) && count($UserList) > 0)
									{
										$Counter = $Start;
										foreach ($UserList as $UserID => $UserDetails)
										{
?>
                                                <tr>
                                                    <td><?php echo ++$Counter; ?></td>
                                                    <td><?php echo $UserDetails['UserName']; ?></td>
                                                    <td><?php echo $UserDetails['Role']; ?></td>
                                                    <td><?php echo (($UserDetails['HasLoginTimeLimit']) ? 'Yes' : 'No'); ?></td>
													<td><?php echo (($UserDetails['HasLoginTimeLimit']) ? date('h:i A', strtotime($UserDetails['LoginStartTime'])) . '-' . date('h:i A', strtotime($UserDetails['LoginEndTime'])) : '-'); ?></td>
                                                    <td><?php echo (($UserDetails['IsActive']) ? 'Yes' : 'No'); ?></td>
													<td class="print-hidden">
<?php
                                                    if ($LoggedUser->HasPermissionForTask(TASK_USER_TASKS) === true)
                                                    {
                                                        echo '<a href="users_tasks.php?Process=7&amp;UserName=' . $UserDetails['UserName'] . '">User Tasks</a>';
                                                    }
                                                    else
                                                    {
                                                        echo 'User Tasks';
                                                    }
?>
                                                    </td>
													<td class="print-hidden">
<?php
													if ($LoggedUser->HasPermissionForTask(TASK_CHANGE_USER_PASSWORD) === true)
                                                    {
                                                        echo '<a href="change_user_password.php?Process=2&amp;UserName=' . $UserDetails['UserName'] . '">Set Password</a>';
                                                    }
                                                    else
                                                    {
                                                        echo 'Set Password';
													}
													
													echo '&nbsp;|&nbsp;';

                                                    if ($LoggedUser->HasPermissionForTask(TASK_EDIT_USER) === true)
                                                    {
                                                        echo '<a href="edit_user.php?Process=2&amp;UserID=' . $UserID . '">Edit</a>';
                                                    }
                                                    else
                                                    {
                                                        echo 'Edit';
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
                                                    <td colspan="8">No Records</td>
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
            </div>
<?php
		}
?>            
        </div>
        <!-- /#page-wrapper -->

    </div>
    <!-- /#wrapper -->
<?php
require_once('footer.php');
?>
	<!-- DataTables JavaScript -->
    <script src="vendor/datatables/js/jquery.dataTables.min.js"></script>
    <script src="vendor/datatables-plugins/dataTables.bootstrap.min.js"></script>
	<script src="vendor/datatables-responsive/dataTables.responsive.js"></script>
	
	<!-- JavaScript To Print A Report -->
    <script src="js/print-report.js"></script>

	<script type="text/javascript">
<?php
    if (isset($_GET['POMode']))
    {
        $PageOperationResultMessage = '';
        $PageOperationResultMessage = UIHelpers::GetPageOperationResultMessage($_GET['POMode']);

        if ($PageOperationResultMessage != '')
        {
            echo 'alert("' . $PageOperationResultMessage . '");';
        }
    }
?>

    $(document).ready(function() {
        $('#DataTableRecords').DataTable({
            responsive: true,
            bPaginate: false,
            bSort: false,
            searching: false, 
            info: false
        });
    });
    </script>
</body>
</html>
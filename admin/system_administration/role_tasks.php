<?php
require_once('../../classes/class.users.php');
require_once('../../classes/class.validation.php');
require_once('../../classes/class.authentication.php');

require_once('../../classes/class.tasks.php');
require_once('../../classes/class.roles.php');

require_once('../../classes/class.ui_helpers.php');

require_once('../../includes/global_defaults.inc.php');

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

if ($LoggedUser->HasPermissionForTask(TASK_ROLE_TASKS) !== true)
{
	header('location:../unauthorized_login_admin.php');
	exit;
}

$AllRoles = array();
$AllRoles = Role::GetActiveRoles();

$AllTasks = array();
$AllTasks = Task::GetActiveTasks();

$SelectedRoleTasks = array();

$HasErrors = false;

$Clean = array();
$Clean['Process'] = 0;

$Clean['RoleID'] = 0;

if (isset($_POST['hdnProcess']))
{
	$Clean['Process'] = (int) $_POST['hdnProcess'];
}
elseif (isset($_GET['Process']))
{
	$Clean['Process'] = (int) $_GET['Process'];
}
switch ($Clean['Process'])
{
	case 1:
		if (isset($_POST['hdnRoleID']))
		{
			$Clean['RoleID'] = (int) $_POST['hdnRoleID'];
		}
		
		try
		{
			$SelectedRole = new Role($Clean['RoleID']);
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

		$RecordValidator->ValidateInSelect($Clean['RoleID'], $AllRoles, 'Unknown Error, Please try again.');
		
		if (isset($_POST['chkRoleTask']) && is_array($_POST['chkRoleTask']))
		{
			$SelectedRoleTasks = $_POST['chkRoleTask'];
		}
		
		if (!$SelectedRole->SetRoleTasks($SelectedRoleTasks, $LoggedUser->GetUserID()))
		{
			$RecordValidator->AttachTextError('There was an error.');
			$HasErrors = true;
			break;
		}
			
		header('location:role_tasks.php?POMode=RTU');
		exit;
	break;

	case 7:
		if (isset($_POST['drdRole']))
		{
			$Clean['RoleID'] = (int) $_POST['drdRole'];
		}
		else if (isset($_GET['RoleID']))
		{
			$Clean['RoleID'] = (int) $_GET['RoleID'];
		}

		$RecordValidator = new Validator();

		$RecordValidator->ValidateInSelect($Clean['RoleID'], $AllRoles, 'Unknown Error, Please try again.');
		
		if ($RecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}

		try
		{
			$SelectedRole = new Role($Clean['RoleID']);
		}
		catch (Exception $e)
		{
			$RecordValidator->AttachTextError('This role is not present.');
			$HasErrors = true;
			break;
		}
		
		$SelectedRoleTasks = $SelectedRole->GetRoleTasks();

		break;
}

require_once('../html_header.php');
?>
<title>Role Tasks</title>
<!-- DataTables CSS -->
<link href="/admin/vendor/datatables-plugins/dataTables.bootstrap.css" rel="stylesheet">

<!-- DataTables Responsive CSS -->
<link href="/admin/vendor/datatables-responsive/dataTables.responsive.css" rel="stylesheet">
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
                    <h1 class="page-header">Role Tasks</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
            <form class="form-horizontal" name="frmSearch" action="role_tasks.php" method="post">
                <div class="panel panel-default" id="accordion">
                    <div class="panel-heading">
						<strong><a data-toggle="collapse" data-parent="#accordion" href="#collapseOne">Search</a></strong>
                    </div>
                    <div id="collapseOne" class="panel-collapse collapse in">
                        <div class="panel-body">
<?php
                            if ($HasErrors == true)
                            {
                                echo $RecordValidator->DisplayErrors();
                            }
?>
                            <div class="form-group">
                                <label for="Role" class="col-lg-2 control-label">Role</label>
                                <div class="col-lg-4">
									<select class="form-control" name="drdRole" id="Role">
<?php
									if (is_array($AllRoles) && count($AllRoles) > 0)
									{
										foreach($AllRoles as $RoleID => $RoleName)
										{
											if ($Clean['RoleID'] != $RoleID)
											{
												echo '<option value="' . $RoleID . '">' . $RoleName . '</option>';
											}
											else
											{
												echo '<option selected="selected" value="' . $RoleID . '">' . $RoleName . '</option>';
											}
										}
									}
?>
									</select>
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
?>
            <div class="row">
                <div class="col-lg-12">
                    <div class="panel panel-default">
                        <!-- /.panel-heading -->
                        <div class="panel-body">
                            <div>
                                <div class="row" id="RecordTable">
									<form name="frmSetRoleTasks" id="SetRoleTasks" action="role_tasks.php" method="post">
                                    	<div class="col-lg-12">
											<table width="100%" class="table table-striped table-bordered table-hover" id="DataTableRecords">
												<tbody>
	<?php
							if (is_array($AllTasks))
							{
								$TotalTasks = count($AllTasks);
								if ($TotalTasks > 0)
								{
									$CurrentTaskCounter = 1;
									$CurrentRowTDCounter = 1;
									
									foreach($AllTasks as $TaskID => $TaskName)
									{
										if ($CurrentTaskCounter == 1 || ($CurrentTaskCounter - 1) % 3 == 0)
										{
											echo '<tr>';
										}
										
										if (in_array($TaskID, $SelectedRoleTasks))
										{
											echo '<td><label><input type="checkbox" class="RoleTasks" checked="checked" id="RoleTask' . $TaskID . '" name="chkRoleTask[' . $TaskID . ']" value="' . $TaskID . '" /> ' . $TaskName . '</label></td>';
										}
										else
										{
											echo '<td><label><input type="checkbox" class="RoleTasks" id="RoleTask' . $TaskID . '" name="chkRoleTask[' . $TaskID . ']" value="' . $TaskID . '" /> ' . $TaskName . '</label></td>';
										}
										
										$CurrentRowTDCounter++;
										
										if ($CurrentTaskCounter % 3 == 0)
										{
											$CurrentRowTDCounter = 1;
											echo '</tr>';
										}
										
										$CurrentTaskCounter++;																		
									}
								}
							}
							
							if ($CurrentRowTDCounter > 1)
							{
								for ($i = 1; $i <= (4 - $CurrentRowTDCounter); $i++)
								{ 
									echo '<td>&nbsp;</td>';
								}
								echo '</tr>';														
							}
	?>
												</tbody>
											</table>
											<div class="form-group">
												<div class="col-lg-offset-4 col-lg-4">
													<input type="hidden" name="hdnProcess" value="1" />
													<input type="hidden" name="hdnRoleID" value="<?php echo $Clean['RoleID']; ?>" />
													<button type="submit" name="btnSaveNext" class="btn btn-primary btn-block">Save</button>
												</div>
											</div>
                                    	</div>
									</form>
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
require_once('../footer.php');
?>
	<!-- DataTables JavaScript -->
    <script src="/admin/vendor/datatables/js/jquery.dataTables.min.js"></script>
    <script src="/admin/vendor/datatables-plugins/dataTables.bootstrap.min.js"></script>
	<script src="/admin/vendor/datatables-responsive/dataTables.responsive.js"></script>
	
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
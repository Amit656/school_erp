<?php
require_once('../../classes/class.users.php');
require_once('../../classes/class.validation.php');
require_once('../../classes/class.authentication.php');

require_once('../../classes/class.roles.php');
require_once('../../classes/class.role_groups.php');

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

if ($LoggedUser->HasPermissionForTask(TASK_ROLE_GROUP_ROLES) !== true)
{
	header('location:../unauthorized_login_admin.php');
	exit;
}

$AllRoleGroups = array();
$AllRoleGroups = RoleGroup::GetActiveRoleGroups();

$AllRoles = array();
$AllRoles = Role::GetActiveRoles();

$SelectedRoleGroupGroupRoles = array();

$HasErrors = false;

$Clean = array();
$Clean['Process'] = 0;

$Clean['RoleGroupID'] = 0;

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
		if (isset($_POST['hdnRoleGroupID']))
		{
			$Clean['RoleGroupID'] = (int) $_POST['hdnRoleGroupID'];
		}
		
		try
		{
			$SelectedRoleGroup = new RoleGroup($Clean['RoleGroupID']);
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

		$RecordValidator->ValidateInSelect($Clean['RoleGroupID'], $AllRoleGroups, 'Unknown Error, Please try again.');
		
		if (isset($_POST['chkRoleGroupRole']) && is_array($_POST['chkRoleGroupRole']))
		{
			$SelectedRoleGroupGroupRoles = $_POST['chkRoleGroupRole'];
		}
		
		if (!$SelectedRoleGroup->SetRoleGroupRoles($SelectedRoleGroupGroupRoles, $LoggedUser->GetUserID()))
		{
			$RecordValidator->AttachTextError('There was an error.');
			$HasErrors = true;
			break;
		}
			
		header('location:role_group_roles.php?POMode=RGU');
		exit;
	break;

	case 7:
		if (isset($_POST['drdRoleGroup']))
		{
			$Clean['RoleGroupID'] = (int) $_POST['drdRoleGroup'];
		}
		else if (isset($_GET['RoleGroupID']))
		{
			$Clean['RoleGroupID'] = (int) $_GET['RoleGroupID'];
		}

		$RecordValidator = new Validator();

		$RecordValidator->ValidateInSelect($Clean['RoleGroupID'], $AllRoleGroups, 'Unknown Error, Please try again.');
		
		if ($RecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}

		try
		{
			$SelectedRoleGroup = new RoleGroup($Clean['RoleGroupID']);
		}
		catch (Exception $e)
		{
			$RecordValidator->AttachTextError('This role group is not present.');
			$HasErrors = true;
			break;
		}
		
		$SelectedRoleGroupGroupRoles = $SelectedRoleGroup->GetRoleGroupRoles();

		break;
}

require_once('../html_header.php');
?>
<title>Role Group Roles</title>
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
                    <h1 class="page-header">Role Group Roles</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
            <form class="form-horizontal" name="frmSearch" action="role_group_roles.php" method="post">
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
                                <label for="RoleGroup" class="col-lg-2 control-label">Role Group</label>
                                <div class="col-lg-4">
									<select class="form-control" name="drdRoleGroup" id="RoleGroup">
<?php
									if (is_array($AllRoleGroups) && count($AllRoleGroups) > 0)
									{
										foreach($AllRoleGroups as $RoleGroupID => $RoleGroupName)
										{
											if ($Clean['RoleGroupID'] != $RoleGroupID)
											{
												echo '<option value="' . $RoleGroupID . '">' . $RoleGroupName . '</option>';
											}
											else
											{
												echo '<option selected="selected" value="' . $RoleGroupID . '">' . $RoleGroupName . '</option>';
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
									<form name="frmSetRoleGroupRoles" id="SetRoleGroupRoles" action="role_group_roles.php" method="post">
                                    	<div class="col-lg-12">
											<table width="100%" class="table table-striped table-bordered table-hover" id="DataTableRecords">
												<tbody>
	<?php
							if (is_array($AllRoles))
							{
								$TotalRoles = count($AllRoles);
								if ($TotalRoles > 0)
								{
									$CurrentRoleCounter = 1;
									$CurrentRowTDCounter = 1;
									
									foreach($AllRoles as $RoleID => $RoleName)
									{
										if ($CurrentRoleCounter == 1 || ($CurrentRoleCounter - 1) % 3 == 0)
										{
											echo '<tr>';
										}
										
										if (in_array($RoleID, $SelectedRoleGroupGroupRoles))
										{
											echo '<td><label><input type="checkbox" class="RoleGroupRoles" checked="checked" id="RoleGroupRole' . $RoleID . '" name="chkRoleGroupRole[' . $RoleID . ']" value="' . $RoleID . '" /> ' . $RoleName . '</label></td>';
										}
										else
										{
											echo '<td><label><input type="checkbox" class="RoleGroupRoles" id="RoleGroupRole' . $RoleID . '" name="chkRoleGroupRole[' . $RoleID . ']" value="' . $RoleID . '" /> ' . $RoleName . '</label></td>';
										}
										
										$CurrentRowTDCounter++;
										
										if ($CurrentRoleCounter % 3 == 0)
										{
											$CurrentRowTDCounter = 1;
											echo '</tr>';
										}
										
										$CurrentRoleCounter++;																		
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
													<input type="hidden" name="hdnRoleGroupID" value="<?php echo $Clean['RoleGroupID']; ?>" />
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
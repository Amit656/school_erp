<?php
require_once('../classes/class.users.php');
require_once('../classes/class.validation.php');
require_once('../classes/class.authentication.php');

require_once('../classes/class.tasks.php');
require_once('../classes/class.task_groups.php');

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

if ($LoggedUser->HasPermissionForTask(TASK_USER_TASKS) !== true)
{
	header('location:unauthorized_login_admin.php');
	exit;
}

$AllTaskGroups = array();
$AllTaskGroups = TaskGroup::GetActiveTaskGroups();

$AllTasks = array();

$UserTasksArray = array();

$HasErrors = false;

$Clean = array();
$Clean['Process'] = 0;

$Clean['UserID'] = 0;
$Clean['UserName'] = '';

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
		if (isset($_POST['hdnUserID']))
		{
			$Clean['UserID'] = (int) $_POST['hdnUserID'];
		}
		
		if ($Clean['UserID'] <= 0)
		{
			header('location:/error.php');
			exit;
		}
		
		try
		{
			$UserToEdit = new User($Clean['UserID']);
		}
		catch (ApplicationDBException $e)
		{
			header('location:/error.php');
			exit;
		}
		catch (Exception $e)
		{
			header('location:/error.php');
			exit;
		}
		
		$AllTasks = Task::GetActiveTasksTaskGroupWise();
		
		$RecordValidator = new Validator();		
		
		if (isset($_POST['chkUserTask']) && is_array($_POST['chkUserTask']))
		{
			$UserTasksArray = $_POST['chkUserTask'];
		}
		
		if (count($UserTasksArray) > 0)
		{
			$TaskList = array();
			$TaskList = Task::GetActiveTasks();

			foreach ($UserTasksArray as $TaskID => $Value)
			{
				if (!array_key_exists($TaskID, $TaskList))
				{
					header('location:/error.php');
					exit;
				}
			}
		}
		
		if (!$UserToEdit->SetUserTasks($UserTasksArray, $LoggedUser->GetUserID()))
		{
			$RecordValidator->AttachTextError('There was an error.');
			$HasErrors = true;
			break;
		}
			
		header('location:users_report.php?POMode=UTU&Process=7&RoleID=' . $UserToEdit->GetRoleID() . '&UserName=' . $UserToEdit->GetUserName());
		exit;
	break;

	case 7:
		if (isset($_POST['txtUserName']))
		{
			$Clean['UserName'] = strip_tags(trim($_POST['txtUserName']));
		}
		elseif (isset($_GET['UserName']))
		{
			$Clean['UserName'] = strip_tags(trim( (string) $_GET['UserName']));
		}

		$RecordValidator = new Validator();

		$RecordValidator->ValidateStrings($Clean['UserName'], 'User name should be between 4 and 50 characters.', 4, 50);
		
		if ($RecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}

		try
		{
			$UserToDisplay = new User(0, $Clean['UserName']);
			$Clean['UserID'] = $UserToDisplay->GetUserID();
		}
		catch (Exception $e)
		{
			$RecordValidator->AttachTextError('This username is not present.');
			$HasErrors = true;
			break;
		}
		
		$AllTasks = Task::GetActiveTasksTaskGroupWise();
		$UserTasksArray = $UserToDisplay->GetTaskGroupWiseUserTasks();

		break;
}

require_once('html_header.php');
?>
<title>User Tasks</title>
<style type="text/css">
#DataTableRecords label { font-weight:normal; }
#DataTableRecords th { text-align:center; }
</style>
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
                    <h1 class="page-header">User Tasks</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
            <form class="form-horizontal" name="frmUserReport" action="users_tasks.php" method="post">
                <div class="panel panel-default" id="accordion">
                    <div class="panel-heading">
						<strong><a data-toggle="collapse" data-parent="#accordion" href="#collapseOne">Filters</a></strong>
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
                                <label for="UserName" class="col-lg-2 control-label">User Name</label>
                                <div class="col-lg-4">
									<input class="form-control" type="text" maxlength="50" id="UserName" name="txtUserName" value="<?php echo $Clean['UserName']; ?>" />
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
									<form name="frmUserTasks" id="UserTasks" action="users_tasks.php" method="post">
                                    	<div class="col-lg-12">
											<table width="100%" class="table table-striped table-bordered table-hover" id="DataTableRecords">
												<tbody>
<?php
							if (is_array($AllTasks))
							{
								$TotalTasks = count($AllTasks);
								if ($TotalTasks > 0)
								{
									foreach($AllTasks as $TaskGroupID => $TaskGroupDetails)
									{
										$CurrentTaskCounter = 1;
										$CurrentRowTDCounter = 1;
									
										$TotalTaskGroupTasks = 0;
										$TotalAllotedTaskGroupTasks = 0;

										$TotalTaskGroupTasks = count($TaskGroupDetails);

										if (isset($UserTasksArray[$TaskGroupID]))
										{
											$TotalAllotedTaskGroupTasks = count($UserTasksArray[$TaskGroupID]);
										}

										$Percentage = round(($TotalAllotedTaskGroupTasks * 100) / $TotalTaskGroupTasks);
?>
										<tr class="task-group">
											<th colspan="3"><?php echo $AllTaskGroups[$TaskGroupID]; ?></th>
										</tr>

										<tr>
											<td colspan="3">
												<p>
												<strong><span id="TotalAssignedTask<?php echo $TaskGroupID; ?>"><?php echo $TotalAllotedTaskGroupTasks; ?></span>/<?php echo $TotalTaskGroupTasks; ?> Tasks Assigned</strong>
												<span class="pull-right text-muted"><?php echo $Percentage; ?>% Tasks Assigned</span>
												<div class="progress progress-striped active" style="margin-bottom:0px;">
													<div class="progress-bar progress-bar-danger" role="progressbar" aria-valuenow="<?php echo $Percentage; ?>" aria-valuemin="0" aria-valuemax="100" style="width: <?php echo $Percentage; ?>%">
														<span class="sr-only"><?php echo $Percentage; ?>% Complete (danger)</span>
													</div>
												</div>
												</p>

												<div class="row" style="text-align:center;">
													<div class="col-lg-6"><label><input type="checkbox" class="ClearAll" name="chkClearAll" task-group="<?php echo $TaskGroupID; ?>" />&nbsp;<strong>Clear All</strong></label></div>
													<div class="col-lg-6"><label><input type="checkbox" class="CheckAll" name="chkCheckAll" task-group="<?php echo $TaskGroupID; ?>" />&nbsp;<strong>Check All</strong></label></div>
												</div>
											</td>
										</tr>
<?php
										foreach ($TaskGroupDetails as $TaskID => $TaskName)
										{
											if ($CurrentTaskCounter == 1 || ($CurrentTaskCounter - 1) % 3 == 0)
											{
												echo '<tr>';
											}
										
											if (isset($UserTasksArray[$TaskGroupID]) && in_array($TaskID, $UserTasksArray[$TaskGroupID]))
											{
													echo '<td><label><input type="checkbox" class="UserTasks TaskTaskGroup' . $TaskGroupID . '" checked="checked" name="chkUserTask[' . $TaskID . ']" value="' . $TaskID . '" task-group="' . $TaskGroupID . '" /> ' . $TaskName . '</label></td>';
											}
											else
											{
													echo '<td><label><input type="checkbox" class="UserTasks TaskTaskGroup' . $TaskGroupID . '" name="chkUserTask[' . $TaskID . ']" value="' . $TaskID . '" task-group="' . $TaskGroupID . '" /> ' . $TaskName . '</label></td>';
											}
										
											$CurrentRowTDCounter++;
											
											if ($CurrentTaskCounter % 3 == 0)
											{
												$CurrentRowTDCounter = 1;
												echo '</tr>';
											}
											
											$CurrentTaskCounter++;																		
										}
							
										if ($CurrentRowTDCounter > 1)
										{
											for ($i = 1; $i <= (4 - $CurrentRowTDCounter); $i++)
											{ 
												echo '<td>&nbsp;</td>';
											}
											echo '</tr>';														
										}
									}
								}
							}
?>
												</tbody>
											</table>
											<div class="form-group">
												<div class="col-lg-offset-4 col-lg-4">
													<input type="hidden" name="hdnProcess" value="1" />
													<input type="hidden" name="hdnUserID" value="<?php echo $Clean['UserID']; ?>" />
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
require_once('footer.php');
?>
	<script type="text/javascript">
    $(document).ready(function() {
		$('.CheckAll').click(function() {
			var TaskGroupID = parseInt($(this).attr('task-group'));

			if ($(this).is(':checked'))
			{
				$('.TaskTaskGroup' + TaskGroupID).prop('checked', true);
			}

			CountTotalTaskAsigned(TaskGroupID);
		});

		$('.ClearAll').click(function() {
			var TaskGroupID = parseInt($(this).attr('task-group'));

			if ($(this).is(':checked'))
			{
				$('.TaskTaskGroup' + TaskGroupID).prop('checked', false);
			}

			CountTotalTaskAsigned(TaskGroupID);
		});

		$('.UserTasks').change(function() {
			var TaskGroupID = $(this).attr('task-group');

			if ($(this).is(':checked'))
			{
				$('.ClearAll').prop('checked', false);
			}
			else
			{
				$('.CheckAll').prop('checked', false);
			}

			CountTotalTaskAsigned(TaskGroupID);
		});
    });

	function CountTotalTaskAsigned(TaskGroupID)
	{
		var TotalTaskAsigned = $('.TaskTaskGroup' + TaskGroupID + ':checked').length;
		$('#TotalAssignedTask' + TaskGroupID).text(TotalTaskAsigned);

		return true;
	}
    </script>
</body>
</html>
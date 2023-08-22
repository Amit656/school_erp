<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once("../../classes/academic_supervision/class.achievements_master.php");

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

if ($LoggedUser->HasPermissionForTask(TASK_LIST_ACHIEVEMENT) !== true)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}

$HasErrors = false;
$RecordDeletedSuccessfully = false;

$Clean = array();
$Clean['Process'] = 0;

$Clean['AchievementMasterID'] = 0;

if (isset($_GET['Process']))
{
	$Clean['Process'] = (int) $_GET['Process'];
}
switch ($Clean['Process'])
{
	case 5:
		if ($LoggedUser->HasPermissionForTask(TASK_DELETE_ACHIEVEMENT) !== true)
		{
			header('location:/admin/unauthorized_login_admin.php');
			exit;
		}
		
		if (isset($_GET['AchievementMasterID']))
		{
			$Clean['AchievementMasterID'] = (int) $_GET['AchievementMasterID'];			
		}
		
		if ($Clean['AchievementMasterID'] <= 0)
		{
			header('location:/admin/error_page.php');
			exit;
		}						
			
		try
		{
			$AchievementMasterToDelete = new AchievementMaster($Clean['AchievementMasterID']);
		}
		catch (ApplicationDBException $e)
		{
			header('location:/admin/error_page.php');
			exit;
		}
		catch (Exception $e)
		{
			header('location:/admin/error_page.php');
			exit;
		}
		
		$RecordValidator = new Validator();
		
		if ($AchievementMasterToDelete->CheckDependencies())
		{
			$RecordValidator->AttachTextError('This achievement cannot be deleted. There are dependent records for this achievement.');
			$HasErrors = true;
			break;
		}
				
		if (!$AchievementMasterToDelete->Remove())
		{
			$RecordValidator->AttachTextError(ProcessErrors($AchievementMasterToDelete->GetLastErrorCode()));
			$HasErrors = true;
			break;
		}
		
		$RecordDeletedSuccessfully = true;
	break;
}

$AllAchievementMasters = array();
$AllAchievementMasters = AchievementMaster::GetAllAchievementMasters();

$LandingPageMode = '';
if (isset($_GET['Mode']))
{
    $LandingPageMode = $_GET['Mode'];
}

require_once('../html_header.php');
?>
<title>Achievement Master List</title>
<!-- DataTables CSS -->
<link href="../vendor/datatables-plugins/dataTables.bootstrap.css" rel="stylesheet">

<!-- DataTables Responsive CSS -->
<link href="../vendor/datatables-responsive/dataTables.responsive.css" rel="stylesheet">
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
                    <h1 class="page-header">Achievement Master List</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
            <!-- /.row -->
            <div class="row">
                <div class="col-lg-12">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <strong>Total Records Returned: <?php echo count($AllAchievementMasters); ?></strong>
                        </div>
                        <!-- /.panel-heading -->
                        <div class="panel-body">
                            <div>
                                <div class="row">
                                    <div class="col-lg-6">
                                    <div class="add-new-btn-container"><a href="add_achievements_master.php" class="btn btn-primary<?php echo $LoggedUser->HasPermissionForTask(TASK_ADD_ACHIEVEMENT) === true ? '' : ' disabled'; ?>" role="button">Add New Achievement Master</a></div>
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
                                    	<div class="report-heading-container"><strong>Achievement Master Details on <?php echo date('d-m-Y h:i A'); ?></strong></div>
                                    </div>
								</div>
                                <div class="row" id="RecordTable">
                                    <div class="col-lg-12">
                                        <table width="100%" class="table table-striped table-bordered table-hover" id="DataTableRecords">
                                            <thead>
                                                <tr>
                                                    <th>S. No</th>
                                                    <th>Achievement</th>
                                                    <th>Achievement Details</th>
                                                    <th>Is Active</th>
                                                    <th>Create User</th>
                                                    <th>Create Date</th>
                                                    <th class="print-hidden">Operations</th>
                                                </tr>
                                            </thead>
                                            <tbody>
<?php
                                    if (is_array($AllAchievementMasters) && count($AllAchievementMasters) > 0)
                                    {
                                        $Counter = 0;
                                        foreach ($AllAchievementMasters as $AchievementMasterID => $AchievementMasterDetails)
                                        {
?>
                                            <tr>
                                                <td><?php echo ++$Counter; ?></td>
                                                <td><?php echo $AchievementMasterDetails['Achievement']; ?></td>
                                                <td><?php echo $AchievementMasterDetails['AchievementDetails']; ?></td>
                                                <td><?php echo (($AchievementMasterDetails['IsActive']) ? 'Yes' : 'No'); ?></td>
                                                <td><?php echo $AchievementMasterDetails['CreateUserName']; ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($AchievementMasterDetails['CreateDate'])); ?></td>
                                                <td class="print-hidden">
<?php
                                                if ($LoggedUser->HasPermissionForTask(TASK_EDIT_ACHIEVEMENT) === true)
                                                {
                                                    echo '<a href="edit_achievements_master.php?Process=2&amp;AchievementMasterID=' . $AchievementMasterID . '">Edit</a>';
                                                }
                                                else
                                                {
                                                    echo 'Edit';
                                                }

                                                echo '&nbsp;|&nbsp;';

                                                if ($LoggedUser->HasPermissionForTask(TASK_DELETE_ACHIEVEMENT) === true)
                                                {
                                                    echo '<a href="achievements_master_list.php?Process=5&amp;AchievementMasterID=' . $AchievementMasterID . '" class="delete-record">Delete</a>';
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
                                                <td colspan="7">No Records</td>
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
        </div>
        <!-- /#page-wrapper -->

    </div>
    <!-- /#wrapper -->
<?php
require_once('../footer.php');
?>
<!-- DataTables JavaScript -->
<script src="../vendor/datatables/js/jquery.dataTables.min.js"></script>
<script src="../vendor/datatables-plugins/dataTables.bootstrap.min.js"></script>
<script src="../vendor/datatables-responsive/dataTables.responsive.js"></script>   
<script type="text/javascript">
$(document).ready(function() {
    $("body").on('click', '.delete-record', function()
    {   
        if (!confirm("Are you sure you want to delete this achievement?"))
        {
            return false;
        }
    });

    $('#DataTableRecords').DataTable({
        responsive: true,
        bPaginate: false,
        bSort: false,
        searching: false, 
        info: false
    });
});
</script>
<!-- JavaScript To Print A Report -->
<script src="js/print-report.js"></script>
<script src="/admin/js/print-report.js"></script>
</body>
</html>
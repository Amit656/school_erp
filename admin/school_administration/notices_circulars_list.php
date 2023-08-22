<?php
require_once("../../classes/class.users.php");
require_once('../../classes/class.validation.php');
require_once("../../classes/class.authentication.php");

require_once("../../classes/school_administration/class.notices_circulars.php");
require_once("../../classes/school_administration/class.branch_staff.php");
require_once("../../classes/school_administration/class.classes.php");

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

$HasErrors = false;
$RecordDeletedSuccessfully = false;

$Clean = array();
$Clean['Process'] = 0;

$Clean['NoticeCircularID'] = 0;

if (isset($_GET['Process']))
{
	$Clean['Process'] = (int) $_GET['Process'];
}
switch ($Clean['Process'])
{
	case 5:
		/*if ($LoggedUser->HasPermissionForTask(TASK_ADD_EDIT_TASK) !== true)
		{
			header('location:unauthorized_login_admin.php');
			exit;
		}*/
		
		if (isset($_GET['NoticeCircularID']))
		{
			$Clean['NoticeCircularID'] = (int) $_GET['NoticeCircularID'];
		}
		
		if ($Clean['NoticeCircularID'] <= 0)
		{
			header('location:../error_page.php');
			exit;
		}						
			
		try
		{
			$NoticeCircularToDelete = new NoticeCircular($Clean['NoticeCircularID']);
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
		
		/*if ($NoticeCircularToDelete->CheckDependencies())
		{
			$RecordValidator->AttachTextError('This timing part cannot be deleted. There are dependent records for this house.');
			$HasErrors = true;
			break;
		}
				*/
		if (!$NoticeCircularToDelete->Remove())
		{
			$RecordValidator->AttachTextError(ProcessErrors($NoticeCircularToDelete->GetLastErrorCode()));
			$HasErrors = true;
			break;
		}
		
		$RecordDeletedSuccessfully = true;
	break;
}

$AllNoticeCirculars = array();
$AllNoticeCirculars = NoticeCircular::NoticeCircularReports();

$LandingPageMode = '';
if (isset($_GET['Mode']))
{
    $LandingPageMode = $_GET['Mode'];
}

require_once('../html_header.php');
?>
<title>Notice/Circular List</title>
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
                    <h1 class="page-header">Notice/Circular List</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
            <!-- /.row -->
            <div class="row">
                <div class="col-lg-12">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <strong>Total Records Returned: <?php echo count($AllNoticeCirculars); ?></strong>
                        </div>
                        <!-- /.panel-heading -->
                        <div class="panel-body">
                            <div>
                                <div class="row">
                                    <div class="col-lg-6">
                                    <div class="add-new-btn-container"><a href="add_notices_circulars.php" class="btn btn-primary<?php //echo $LoggedUser->HasPermissionForTask(TASK_ADD_MENU) === true ? '' : ' disabled'; ?>" role="button">Add New </a></div>
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
                                    	<div class="report-heading-container"><strong>Details on <?php echo date('d-m-Y h:i A'); ?></strong></div>
                                    </div>
								</div>
                                <div class="row" id="RecordTable">
                                    <div class="col-lg-12">
                                        <table width="100%" class="table table-striped table-bordered table-hover">
                                            <thead>
                                                <tr>
                                                    <th>S. No</th>
                                                    <th>Title</th>
                                                    <th>Date</th>
                                                    <th>For Class </th>
                                                    <th>For Staff</th>
                                                    <th>Is Active</th>
                                                    <th>Created By</th>
                                                    <th>Create Date</th>
                                                    <th class="print-hidden">Operations</th>
                                                </tr>
                                            </thead>
                                            <tbody>
    <?php
                                    if (is_array($AllNoticeCirculars) && count($AllNoticeCirculars) > 0)
                                    {
                                        $Counter = 0;
                                        foreach ($AllNoticeCirculars as $NoticeCircularID => $NoticeCircularDetails)
                                        {
    ?>
                                                <tr>
                                                    <td><?php echo ++$Counter; ?></td>
                                                    <td><?php echo $NoticeCircularDetails['NoticeCircularSubject']; ?></td>
                                                    <td><?php echo date('d/m/Y', strtotime($NoticeCircularDetails['NoticeCircularDate'])); ?></td>
                                                    <td><?php echo (($NoticeCircularDetails['TotalClassApplicable'] > 0) ? 'Yes' : 'No'); ?></td>
                                                    <td><?php echo (($NoticeCircularDetails['TotalStaffApplicable'] > 0) ? 'Yes' : 'No'); ?></td>
                                                    <td><?php echo (($NoticeCircularDetails['IsActive'] == 1) ? 'Yes' : 'No'); ?></td>
                                                    <td><?php echo $NoticeCircularDetails['CreateUserName']; ?></td>
                                                    <td><?php echo date('d/m/Y', strtotime($NoticeCircularDetails['CreateDate'])); ?></td>
                                                    <td class="print-hidden">
<?php
                                                    echo '<a href="details_for_notice_circular.php?Process=7&amp;NoticeCircularID=' . $NoticeCircularID . '">More Details</a>';
                                                    echo '&nbsp;|&nbsp;';
                                                    echo '<a href="edit_notice_circular.php?Process=2&amp;NoticeCircularID=' . $NoticeCircularID . '">Edit</a>';
                                                    echo '&nbsp;|&nbsp;';
                                                    echo '<a class="delete-record" href="notices_circulars_list.php?Process=5&amp;NoticeCircularID=' . $NoticeCircularID . '">Delete</a>';
                                                    /*if ($LoggedUser->HasPermissionForTask(TASK_EDIT_MENU) === true)
                                                    {
                                                        echo '<a href="edit_notice_circular.php?Process=2&amp;NoticeCircularID=' . $NoticeCircularID . '">Edit</a>';
                                                    }
                                                    else
                                                    {
                                                        echo 'Edit';
                                                    }

                                                    echo '&nbsp;|&nbsp;';

                                                    if ($LoggedUser->HasPermissionForTask(TASK_DELETE_MENU) === true)
                                                    {
                                                        echo '<a class="delete-record" href="notices_circulars_list.php?Process=5&amp;NoticeCircularID=' . $NoticeCircularID . '">Delete</a>';
                                                    }
                                                    else
                                                    {
                                                        echo 'Delete';
                                                    }*/
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
        if (!confirm("Are you sure you want to delete this notice/circular ?"))
        {
            return false;
        }
    });
});
</script>
<!-- JavaScript To Print A Report -->
<script src="/admin/js/print-report.js"></script>
</body>
</html>
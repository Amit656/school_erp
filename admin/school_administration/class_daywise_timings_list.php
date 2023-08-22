<?php
require_once("../../classes/class.users.php");
require_once('../../classes/class.validation.php');
require_once("../../classes/class.authentication.php");

require_once("../../classes/school_administration/class.school_timing_parts_master.php");

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

$HasErrors = false;
$RecordDeletedSuccessfully = false;

$Clean = array();
$Clean['Process'] = 0;

$Clean['SchoolTimingPartID'] = 0;

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
		
		if (isset($_GET['SchoolTimingPartID']))
		{
			$Clean['SchoolTimingPartID'] = (int) $_GET['SchoolTimingPartID'];
		}
		
		if ($Clean['SchoolTimingPartID'] <= 0)
		{
			header('location:../error_page.php');
			exit;
		}						
			
		try
		{
			$TimingPartToDelete = new SchoolTimingPartsMaster($Clean['SchoolTimingPartID']);
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
		
		if ($TimingPartToDelete->CheckDependencies())
		{
			$RecordValidator->AttachTextError('This timing part cannot be deleted. There are dependent records for this house.');
			$HasErrors = true;
			break;
		}
				
		if (!$TimingPartToDelete->Remove())
		{
			$RecordValidator->AttachTextError(ProcessErrors($TimingPartToDelete->GetLastErrorCode()));
			$HasErrors = true;
			break;
		}
		
		$RecordDeletedSuccessfully = true;
	break;
}

$AllTimingParts = array();
$AllTimingParts = SchoolTimingPartsMaster::GetTimingPartDetails();

$LandingPageMode = '';
if (isset($_GET['Mode']))
{
    $LandingPageMode = $_GET['Mode'];
}

require_once('../html_header.php');
?>
<title>School Timing</title>
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
                    <h1 class="page-header">School Timing Parts</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
            <!-- /.row -->
            <div class="row">
                <div class="col-lg-12">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <strong>Total Records Returned: <?php echo count($AllTimingParts); ?></strong>
                        </div>
                        <!-- /.panel-heading -->
                        <div class="panel-body">
                            <div>
                                <div class="row">
                                    <div class="col-lg-6">
                                    <div class="add-new-btn-container"><a href="add_school_timing_parts.php" class="btn btn-primary" role="button">Add New Part</a></div>
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
                            else if ($LandingPageMode == 'DD')
                            {
                                echo '<div class="alert alert-success alert-top-margin">Record deleted successfully.</div>';
                            }
                            else if ($LandingPageMode == 'UD')
                            {
                                echo '<div class="alert alert-success alert-top-margin">Record Updated successfully.</div>';
                            }
?>
                                <div class="row" id="RecordTableHeading">
                                    <div class="col-lg-12">
                                    	<div class="report-heading-container"><strong>Timimg Details on <?php echo date('d-m-Y h:i A'); ?></strong></div>
                                    </div>
								</div>
                                <div class="row" id="RecordTable">
                                    <div class="col-lg-12">
                                        <table width="100%" class="table table-striped table-bordered table-hover">
                                            <thead>
                                                <tr>
                                                    <th>S. No</th>
                                                    <th>Timing Part Name</th>
                                                    <th>Part Type</th>
                                                    <th>Default Duration</th>
                                                    <th>Priority</th>
                                                    <th>Create User</th>
                                                    <th>Create Date</th>
                                                    <th class="print-hidden">Edit</th>
                                                    <th class="print-hidden">Delete</th>
                                                </tr>
                                            </thead>
                                            <tbody>
    <?php
                                    if (is_array($AllTimingParts) && count($AllTimingParts) > 0)
                                    {
                                        $Counter = 0;
                                        foreach ($AllTimingParts as $TimingPartID => $TimingPartDetails)
                                        {
    ?>
                                                <tr>
                                                    <td><?php echo ++$Counter; ?></td>
                                                    <td><?php echo $TimingPartDetails['TimingPart']; ?></td>
                                                    <td><?php echo $TimingPartDetails['PartType']; ?></td>
                                                    <td><?php echo $TimingPartDetails['DefaultDuration']; ?></td>
                                                    <td><?php echo $TimingPartDetails['Priority']; ?></td>
                                                    <td><?php echo $TimingPartDetails['CreateUserName']; ?></td>
                                                    <td><?php echo date('d/m/Y', strtotime($TimingPartDetails['CreateDate'])); ?></td>
                                                    <td class="print-hidden"><?php echo '<a href="edit_school_timing_part.php?Process=2&amp;SchoolTimingPartID='.$TimingPartID.'">Edit</a>'; ?></td>
                                                    <td class="print-hidden delete-task"><?php echo '<a href="school_timing_parts_list.php?Process=5&amp;SchoolTimingPartID='.$TimingPartID.'">Delete</a>'; ?></td>
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
		$(".delete-task").click(function()
        {	
            if (!confirm("Are you sure you want to delete this timing Part ?"))
            {
                return false;
            }
        });
	});
    </script>
	<!-- JavaScript To Print A Report -->
    <script src="js/print-report.js"></script>
</body>
</html>
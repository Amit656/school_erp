<?php
require_once("../../classes/class.users.php");
require_once('../../classes/class.validation.php');
require_once("../../classes/class.authentication.php");

require_once("../../classes/school_administration/class.academic_years.php");

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

$Clean['AcademicYearID'] = 0;

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
		
		if (isset($_GET['AcademicYearID']))
		{
			$Clean['AcademicYearID'] = (int) $_GET['AcademicYearID'];
		}
		
		if ($Clean['AcademicYearID'] <= 0)
		{
			header('location:../error_page.php');
			exit;
		}						
			
		try
		{
			$AcademicYearToDelete = new AcademicYear($Clean['AcademicYearID']);
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
		
		if ($AcademicYearToDelete->CheckDependencies())
		{
			$RecordValidator->AttachTextError('This academic year cannot be deleted. There are dependent records for this academic year.');
			$HasErrors = true;
			break;
		}
				
		if (!$AcademicYearToDelete->Remove())
		{
			$RecordValidator->AttachTextError(ProcessErrors($AcademicYearToDelete->GetLastErrorCode()));
			$HasErrors = true;
			break;
		}
		
		$RecordDeletedSuccessfully = true;
	break;
}

$AllAcademicYears = array();
$AllAcademicYears = AcademicYear::GetAllAcademicYears();

$LandingPageMode = '';
if (isset($_GET['Mode']))
{
    $LandingPageMode = $_GET['Mode'];
}

require_once('../html_header.php');
?>
<title>Academic Years List</title>
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
                    <h1 class="page-header">Academic Years List</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
            <!-- /.row -->
            <div class="row">
                <div class="col-lg-12">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <strong>Total Records Returned: <?php echo count($AllAcademicYears); ?></strong>
                        </div>
                        <!-- /.panel-heading -->
                        <div class="panel-body">
                            <div>
                                <div class="row">
                                    <div class="col-lg-6">
                                    <div class="add-new-btn-container"><a href="add_academic_year.php" class="btn btn-primary<?php //echo $LoggedUser->HasPermissionForTask(TASK_ADD_MENU) === true ? '' : ' disabled'; ?>" role="button">Add New Academic Year</a></div>
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
                            elseif ($RecordDeletedSuccessfully == true)
                            {
                                echo '<div class="alert alert-danger alert-top-margin">The record was deleted successfully.</div>';
                            }
                            else if ($LandingPageMode == 'UD')
                            {
                                echo '<div class="alert alert-success alert-top-margin">Record updated successfully.</div>';
                            }
?>
                                <div class="row" id="RecordTableHeading">
                                    <div class="col-lg-12">
                                    	<div class="report-heading-container"><strong>Academic Years List on <?php echo date('d-m-Y h:i A'); ?></strong></div>
                                    </div>
								</div>
                                <div class="row" id="RecordTable">
                                    <div class="col-lg-12">
                                        <table width="100%" class="table table-striped table-bordered table-hover">
                                            <thead>
                                                <tr>
                                                    <th>S. No</th>
                                                    <th>Start Date</th>
                                                    <th>End Date</th>
                                                    <th>Is CurrentYear</th>
                                                    <th>Create User</th>
                                                    <th>Create Date</th>
                                                    <th class="print-hidden">Operations</th>
                                                </tr>
                                            </thead>
                                            <tbody>
    <?php
                                    if (is_array($AllAcademicYears) && count($AllAcademicYears) > 0)
                                    {
                                        $Counter = 0;
                                        foreach ($AllAcademicYears as $AcademicYearID => $AcademicYearDetails)
                                        {
    ?>
                                                <tr>
                                                    <td><?php echo ++$Counter; ?></td>
                                                    <td><?php echo date('d/m/Y', strtotime($AcademicYearDetails['StartDate'])); ?></td>
                                                    <td><?php echo date('d/m/Y', strtotime($AcademicYearDetails['EndDate'])); ?></td>
                                                    <td><?php echo (($AcademicYearDetails['IsCurrentYear']) ? 'Yes' : 'No'); ?></td>
                                                    <td><?php echo $AcademicYearDetails['CreateUserName']; ?></td>
                                                    <td><?php echo date('d/m/Y', strtotime($AcademicYearDetails['CreateDate'])); ?></td>
                                                    <td class="print-hidden">

<?php
                                                    echo '<a href="edit_academic_year.php?Process=2&amp;AcademicYearID='.$AcademicYearID.'">Edit</a>';
                                                    echo '&nbsp;|&nbsp;';
                                                    echo '<a class="delete-record" href="academic_years_list.php?Process=5&amp;AcademicYearID='.$AcademicYearID.'">Delete</a>'; 
                                                    /*if ($LoggedUser->HasPermissionForTask(TASK_EDIT_MENU) === true)
                                                    {
                                                        echo '<a href="edit_academic_year.php?Process=2&amp;AcademicYearID='.$AcademicYearID.'">Edit</a>';
                                                    }
                                                    else
                                                    {
                                                        echo 'Edit';
                                                    }

                                                    echo '&nbsp;|&nbsp;';

                                                    if ($LoggedUser->HasPermissionForTask(TASK_DELETE_MENU) === true)
                                                    {
                                                        echo '<a class="delete-record" href="academic_years_list.php?Process=5&amp;AcademicYearID='.$AcademicYearID.'">Delete</a>'; 
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
	
	<script type="text/javascript">
	$(document).ready(function() {
		$(".delete-record").click(function()
        {	
            if (!confirm("Are you sure you want to delete this Academic Year?"))
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
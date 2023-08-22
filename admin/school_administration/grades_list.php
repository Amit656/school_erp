<?php
require_once("../../classes/class.users.php");
require_once('../../classes/class.validation.php');
require_once("../../classes/class.authentication.php");

require_once("../../classes/school_administration/class.grades.php");

require_once("../../includes/global_defaults.inc.php");

require_once("../../includes/helpers.inc.php");

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

if ($LoggedUser->HasPermissionForTask(TASK_LIST_GRADE) !== true)
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

$AllGrades = array();
$AllGrades = Grade::GetAllGrades();

$HasErrors = false;

$Clean = array();
$Clean['Process'] = 0;

$Clean['GradeID'] = 0;

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
	case 5:
		if ($LoggedUser->HasPermissionForTask(TASK_DELETE_GRADE) !== true)
		{
			header('location:/admin/unauthorized_login_admin.php');
			exit;
		}
		
		if (isset($_GET['GradeID']))
		{
			$Clean['GradeID'] = (int) $_GET['GradeID'];
		}
		
		if ($Clean['GradeID'] <= 0)
		{
			header('location:../error_page.php');
			exit;
		}						
			
		try
		{
			$GradeToDelete = new Grade($Clean['GradeID']);
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
		
		if ($GradeToDelete->CheckDependencies())
		{
			$RecordValidator->AttachTextError('This Grade cannot be deleted. There are dependent records for this grade.');
			$HasErrors = true;
			break;
		}
				
		if (!$GradeToDelete->Remove())
		{
			$RecordValidator->AttachTextError(ProcessErrors($GradeToDelete->GetLastErrorCode()));
			$HasErrors = true;
			break;
		}
		
		header('location:grades_list.php?Mode=DD');
        exit;
	break;
}
require_once('../html_header.php');
?>
<title>Grades List</title>
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
                    <h1 class="page-header">Grades List</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
            <!-- /.row -->
            <div class="row">
                <div class="col-lg-12">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <strong>Total Records Returned: <?php echo count($AllGrades); ?></strong>
                        </div>
                        <!-- /.panel-heading -->
                        <div class="panel-body">
                            <div>
                                <div class="row">
                                    <div class="col-lg-6">
                                    <div class="add-new-btn-container"><a href="add_grade.php" class="btn btn-primary<?php echo $LoggedUser->HasPermissionForTask(TASK_ADD_GRADE) === true ? '' : ' disabled'; ?>" role="button">Add New Grade</a></div>
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
?>
                                <div class="row" id="RecordTableHeading">
                                    <div class="col-lg-12">
                                    	<div class="report-heading-container"><strong>Grades List on <?php echo date('d-m-Y h:i A'); ?></strong></div>
                                    </div>
								</div>
                                <div class="row" id="RecordTable">
                                    <div class="col-lg-12">
                                        <table width="100%" class="table table-striped table-bordered table-hover">
                                            <thead>
                                                <tr>
                                                    <th>S. No</th>
                                                    <th>Grade</th>
                                                    <th>From Percentage</th>
                                                    <th>To Percentage</th>
                                                    <th>Is Active</th>
                                                    <th>Create User</th>
                                                    <th>Create Date</th>
                                                    <th class="print-hidden">Operations</th>
                                                </tr>
                                            </thead>
                                            <tbody>
<?php
                                    if (is_array($AllGrades) && count($AllGrades) > 0)
                                    {
                                        $Counter = 0;
                                        foreach ($AllGrades as $GradeID => $Details)
                                        {
?>
                                        <tr>
                                            <td><?php echo ++$Counter; ?></td>
                                            <td><?php echo $Details['Grade']; ?></td>
                                            <td><?php echo $Details['FromPercentage']; ?></td>
                                            <td><?php echo $Details['ToPercentage']; ?></td>
                                            <td><?php echo ($Details['IsActive'] ? 'Yes' : 'No');?></td>
                                            <td><?php echo $Details['CreateUserName']; ?></td>
                                            <td><?php echo date('d/m/y',strtotime($Details['CreateDate'])); ?></td>
                                            <td class="print-hidden">
<?php
                                            if ($LoggedUser->HasPermissionForTask(TASK_EDIT_GRADE) === true)
                                            {
                                                echo '<a href="edit_grade.php?Process=2&amp;GradeID=' . $GradeID . '">Edit</a>';
                                            }
                                            else
                                            {
                                                echo 'Edit';
                                            }

                                            echo '&nbsp;|&nbsp;';

                                            if ($LoggedUser->HasPermissionForTask(TASK_DELETE_GRADE) === true)
                                            {
                                                echo '<a class="delete-record" href="grades_list.php?Process=5&amp;GradeID=' . $GradeID . '">Delete</a>'; 
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
?>                                                </tbody>
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
if (PrintMessage($_GET, $Message))
{
?>
    <script type="text/javascript">
        alert('<?php echo $Message; ?>');
    </script>
<?php
}
?>

	<script type="text/javascript">
	$(document).ready(function() 
    {
		$(".delete-record").click(function()
        {	
            if (!confirm("Are you sure you want to delete this grade ?"))
            {
                return false;
            }
        });
	});
    </script>
	<!-- JavaScript To Print A Report -->
    <script src="js/print-report.js"></script>
    <script src="/admin/js/print-report.js"></script>
</body>
</html>
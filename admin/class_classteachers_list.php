<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once("../../classes/school_administration/class.class_classteachers.php");

require_once("../../includes/helpers.inc.php");
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
	header('location:unauthorized_login_admin.php');
	exit;
}
catch (Exception $e)
{
	header('location:unauthorized_login_admin.php');
	exit;
}
// END OF 1. //
if ($LoggedUser->HasPermissionForTask(TASK_LIST_CLASS_TEACHER) !== true)
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

$Clean = array();
$Clean['Process'] = 0;

$Clean['ClassClassTeacherID'] = 0;

if (isset($_GET['Process']))
{
	$Clean['Process'] = (int) $_GET['Process'];
}
switch ($Clean['Process'])
{
	case 5:
		if ($LoggedUser->HasPermissionForTask(TASK_DELETE_CLASS_TEACHER) !== true)
		{
			header('location:unauthorized_login_admin.php');
			exit;
		}
		
		if (isset($_GET['ClassClassTeacherID']))
		{
			$Clean['ClassClassTeacherID'] = (int) $_GET['ClassClassTeacherID'];			
		}
		
		if ($Clean['ClassClassTeacherID'] <= 0)
		{
			header('location:../error.php');
			exit;
		}						
		
        try
        {
            $ClassClassteacherToDelete = new ClassClassteacher($Clean['ClassClassTeacherID']);
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

        if (!$ClassClassteacherToDelete->Remove()) 
        {
            $RecordValidator->AttachTextError(ProcessErrors($ClassClassteacherToDelete->GetLastErrorCode()));
            $HasErrors = true;
            break;
        }	

	    break;
}

$AllClassTeachersList = array();
$AllClassTeachersList = ClassClassteacher::GetAllClassClassteachers();

require_once('../html_header.php');
?>
<title>Class Teachers List</title>
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
                    <h1 class="page-header">Class Teachers List</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
            <!-- /.row -->
            <div class="row">
                <div class="col-lg-12">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <strong>Total Records Returned: <?php echo count($AllClassTeachersList); ?></strong>
                        </div>
                        <!-- /.panel-heading -->
                        <div class="panel-body">
                            <div>
                                <div class="row">
                                    <div class="col-lg-6">
                                    <div class="add-new-btn-container"><a href="add_class_classteacher.php" class="btn btn-primary<?php echo $LoggedUser->HasPermissionForTask(TASK_ADD_CLASS_TEACHER) === true ? '' : ' disabled' ; ?>" role="button">Add Class Teacher</a></div>
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
                                    	<div class="report-heading-container"><strong>Class Teachers on <?php echo date('d-m-Y h:i A'); ?></strong></div>
                                    </div>
								</div>
                                <div class="row" id="RecordTable">
                                    <div class="col-lg-12">
                                        <table width="100%" class="table table-striped table-bordered table-hover" id="DataTableRecords">
                                            <thead>
                                                <tr>
                                                    <th>S. No</th>
                                                    <th>Class Name</th>
                                                    <th>Section Name</th>
                                                    <th>Teacher Name</th>
                                                    <th>Create User</th>
                                                    <th>Create Date</th>
                                                    <th class="print-hidden">Operations</th>
                                                </tr>
                                            </thead>
                                            <tbody>
    <?php
                                            if (is_array($AllClassTeachersList) && count($AllClassTeachersList) > 0)
                                            {
                                                $Counter = 0;
                                                foreach ($AllClassTeachersList as $ClassClassTeacherID => $ClassTeacherDetails)
                                                {
?>
                                                    <tr>
                                                        <td><?php echo ++$Counter; ?></td>
                                                        <td><?php echo $ClassTeacherDetails['ClassName']; ?></td>
                                                        <td><?php echo $ClassTeacherDetails['SectionName']; ?></td>
                                                        <td><?php echo $ClassTeacherDetails['FirstName'] . " " . $ClassTeacherDetails['LastName']; ?></td>
                                                        <td><?php echo $ClassTeacherDetails['CreateUserName']; ?></td>
                                                        <td><?php echo date('d/m/Y', strtotime($ClassTeacherDetails['CreateDate'])); ?></td>
                                                        <td class="print-hidden">
<?php
                                                            if ($LoggedUser->HasPermissionForTask(TASK_EDIT_CLASS_TEACHER) === true)
                                                            {
                                                                echo '<a href="edit_class_classteacher.php?Process=2&amp;ClassClassTeacherID='. $ClassClassTeacherID .'">Edit</a>';
                                                            }
                                                            else
                                                            {
                                                                echo 'Edit';
                                                            }

                                                            echo '&nbsp;|&nbsp;';

                                                            if ($LoggedUser->HasPermissionForTask(TASK_DELETE_CLASS_TEACHER) === true)
                                                            {
                                                                echo '<a class="delete-record" href="class_classteachers_list.php?Process=5&amp;ClassClassTeacherID=' . $ClassClassTeacherID . '">Delete</a>'; 
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
if (PrintMessage($_GET, $Message))
{
?>
<script type="text/javascript">
    alert('<?php echo $Message; ?>');
</script>
<?php
}
?>
<!-- DataTables JavaScript -->
<script src="/admin/vendor/datatables/js/jquery.dataTables.min.js"></script>
<script src="/admin/vendor/datatables-plugins/dataTables.bootstrap.min.js"></script>
<script src="/admin/vendor/datatables-responsive/dataTables.responsive.js"></script>	
<script type="text/javascript">
$(document).ready(function() {
    $("body").on('click', '.delete-record', function()
    {   
        if (!confirm("Are you sure you want to delete this class ClassTeacher?"))
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
<script src="/admin/js/print-report.js"></script>
</body>
</html>
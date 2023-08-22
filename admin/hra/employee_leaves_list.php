<?php
require_once("../../classes/class.users.php");
require_once('../../classes/class.validation.php');
require_once("../../classes/class.authentication.php");

require_once("../../classes/school_administration/class.branch_staff.php");
require_once("../../classes/hra/class.employee_leaves.php");

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

if ($LoggedUser->HasPermissionForTask(TASK_LIST_EMPLOYEE_LEAVE) !== true)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}

$StaffCategoryList = array();
$StaffCategoryList = array('Teaching' => 'Teaching Staff', 'NonTeaching' => 'Non Teaching Staff');

$AllBranchStaffList = array();
$AllEmployeeLeaves = array();

$HasErrors = false;

$Clean = array();
$Clean['Process'] = 0;

$Clean['StaffCategory'] = 'Teaching';

if (isset($_GET['StaffCategory']))
{
    $Clean['StaffCategory'] = strip_tags(trim($_GET['StaffCategory']));
}

if (!array_key_exists($Clean['StaffCategory'], $StaffCategoryList))
{
    header('location:../error.php');
    exit;
}

$AllBranchStaffList = BranchStaff::GetActiveBranchStaff($Clean['StaffCategory']);

$Clean['BranchStaffID'] = key($AllBranchStaffList);

$Clean['EmployeeLeaveID'] = 0;

if (isset($_GET['Process']))
{
	$Clean['Process'] = (int) $_GET['Process'];
}
else if (isset($_POST['hdnProcess']))
{
    $Clean['Process'] = (int) $_POST['hdnProcess'];
}
switch ($Clean['Process'])
{
	case 5:
		if ($LoggedUser->HasPermissionForTask(TASK_DELETE_EMPLOYEE_LEAVE) !== true)
		{
			header('location:unauthorized_login_admin.php');
			exit;
		}
		
		if (isset($_GET['EmployeeLeaveID']))
		{
			$Clean['EmployeeLeaveID'] = (int) $_GET['EmployeeLeaveID'];			
		}
		
		if ($Clean['EmployeeLeaveID'] <= 0)
		{
			header('location:../error.php');
			exit;
		}						
			
		try
		{
			$EmployeeLeaveToDelete = new EmployeeLeave($Clean['EmployeeLeaveID']);
		}
		catch (ApplicationDBException $e)
		{
			header('location:../error.php');
			exit;
		}
		catch (Exception $e)
		{
			header('location:../error.php');
			exit;
		}
		
        $AllEmployeeLeaves = EmployeeLeave::GetAllEmployeeLeaves($Clean['StaffCategory']);

		$RecordValidator = new Validator();
		
		if ($EmployeeLeaveToDelete->CheckDependencies())
		{
			$RecordValidator->AttachTextError('This employee leave cannot be deleted. leave already taken by employee.');
			$HasErrors = true;
			break;
		}
				
		if (!$EmployeeLeaveToDelete->Remove())
		{
			$RecordValidator->AttachTextError(ProcessErrors($EmployeeLeaveToDelete->GetLastErrorCode()));
			$HasErrors = true;
			break;
		}
		
        header('location:employee_leaves_list.php?Mode=DD&Process=7&StaffCategory=' . $Clean['StaffCategory']);
	    break;

    case 7:
        if (isset($_POST['drdStaffCategory']))
        {
            $Clean['StaffCategory'] = strip_tags(trim($_POST['drdStaffCategory']));
        }

        $NewRecordValidator = new Validator();

        $NewRecordValidator->ValidateInSelect($Clean['StaffCategory'], $StaffCategoryList, 'Unknown error, please try again.');

        if ($NewRecordValidator->HasNotifications())
        {
            $HasErrors = true;
            break;
        }

        $AllBranchStaffList = BranchStaff::GetActiveBranchStaff($Clean['StaffCategory']);

        if ($NewRecordValidator->HasNotifications())
        {
            $HasErrors = true;
            break;
        }

        $AllEmployeeLeaves = EmployeeLeave::GetAllEmployeeLeaves($Clean['StaffCategory']);
        break;
}

require_once('../html_header.php');
?>
<title>Employee Leaves List</title>
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
                    <h1 class="page-header">Employee Leaves List</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
            <div class="panel panel-default">
                <div class="panel-heading">
                    Enter Employee Details
                </div>
                <div class="panel-body">
<?php
                    if ($HasErrors == true)
                    {
                        echo $RecordValidator->DisplayErrors();
                    }
?>                    
                    <form class="form-horizontal" name="SearchEmployeeLeaves" action="employee_leaves_list.php" method="post">
                        <div class="form-group">
                            <label for="StaffCategory" class="col-lg-2 control-label">Staff Category</label>
                            <div class="col-lg-4">
                                <select class="form-control" name="drdStaffCategory" id="StaffCategory">
<?php
                                foreach ($StaffCategoryList as $StaffCategory => $StaffCategoryName)
                                {
?>
                                    <option <?php echo ($StaffCategory == $Clean['StaffCategory'] ? 'selected="selected"' : ''); ?> value="<?php echo $StaffCategory; ?>"><?php echo $StaffCategoryName; ?></option>
<?php
                                }
?>
                                </select>
                            </div>
                        </div> 
                        <div class="form-group">
                            <div class="col-sm-offset-2 col-lg-10">
                                <input type="hidden" name="hdnProcess" value="7"/>
                                <button type="submit" class="btn btn-primary">View</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
<?php
        if($Clean['Process'] == 7 || $Clean['Process'] == 5)
        {
?>
            <!-- /.row -->
            <div class="row">
                <div class="col-lg-12">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <strong>Total Records Returned: <?php echo count($AllEmployeeLeaves); ?></strong>
                        </div>
                        <!-- /.panel-heading -->
                        <div class="panel-body">
                            <div>
                                <div class="row">
                                    <div class="col-lg-6">
                                    <div class="add-new-btn-container"><a href="add_employee_leaves.php?Process=7&StaffCategory=<?php echo $Clean['StaffCategory']; ?>" class="btn btn-primary<?php echo $LoggedUser->HasPermissionForTask(TASK_ADD_EMPLOYEE_LEAVE) === true ? '' : ' disabled'; ?>" role="button">Add Employee Leaves</a></div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="print-btn-container"><button id="PrintButton" type="submit" class="btn btn-primary">Print</button></div>
                                    </div>
                                </div>
                                <div class="row" id="RecordTableHeading">
                                    <div class="col-lg-12">
                                        <div class="report-heading-container"><strong>Employee Leaves on <?php echo date('d-m-Y h:i A'); ?></strong></div>
                                    </div>
                                </div>
                                <div class="row" id="RecordTable">
                                    <div class="col-lg-12">
                                        <table width="100%" class="table table-striped table-bordered table-hover" id="DataTableRecords">
                                            <thead>
                                                <tr>
                                                    <th>S. No</th>
                                                    <th>Employee Name</th>
                                                    <th>From Date</th>
                                                    <th>To Date</th>
                                                    <th>Create User</th>
                                                    <th>Create Date</th>
                                                    <th class="print-hidden">Operations</th>
                                                </tr>
                                            </thead>
                                            <tbody>
<?php
                                    if (is_array($AllEmployeeLeaves) && count($AllEmployeeLeaves) > 0)
                                    {
                                        $Counter = 0;
                                        foreach ($AllEmployeeLeaves as $EmployeeLeaveID => $EmployeeLeaveDetails)
                                        {
?>
                                        <tr>
                                            <td><?php echo ++$Counter; ?></td>
                                            <td><?php echo $EmployeeLeaveDetails['FirstName'] . " " . $EmployeeLeaveDetails['LastName'] ; ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($EmployeeLeaveDetails['LeaveStartDate'])); ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($EmployeeLeaveDetails['LeaveEndDate'])); ?></td>
                                            <td><?php echo $EmployeeLeaveDetails['CreateUserName']; ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($EmployeeLeaveDetails['CreateDate'])); ?></td>
                                            <td class="print-hidden">
<?php
                                            if ($LoggedUser->HasPermissionForTask(TASK_EDIT_EMPLOYEE_LEAVE) === true)
                                            {
                                                echo '<a href="edit_employee_leaves.php?Process=2&amp;EmployeeLeaveID='. $EmployeeLeaveID .'">Edit</a>';
                                            }
                                            else
                                            {
                                                echo 'Edit';
                                            }

                                            echo '&nbsp;|&nbsp;';

                                            if ($LoggedUser->HasPermissionForTask(TASK_DELETE_EMPLOYEE_LEAVE) === true)
                                            {
                                                echo '<a class="delete-record" href="employee_leaves_list.php?Process=5&amp;EmployeeLeaveID=' . $EmployeeLeaveID . '">Delete</a>';
                                            }
                                            else
                                            {
                                                echo 'Delete';
                                            }

                                            echo '&nbsp;|&nbsp;';

                                            if ($LoggedUser->HasPermissionForTask(TASK_VIEW_EMPLOYEE_LEAVE) === true)
                                            {
                                                echo '<a href="edit_employee_leaves.php?Process=2&amp;StaffCategory=' . $Clean['StaffCategory'] .'&amp;EmployeeLeaveID=' . $EmployeeLeaveID . '&amp;ViewOnly=true" target="_blank">View</a>';
                                            }
                                            else
                                            {
                                                echo 'View';
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
<?php
        }
?>
            
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
        if (!confirm("Are you sure you want to delete this employee leave?"))
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
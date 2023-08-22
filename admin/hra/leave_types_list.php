<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once("../../classes/hra/class.leave_types.php");

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

if ($LoggedUser->HasPermissionForTask(TASK_LIST_LEAVE_TYPE) !== true)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}

$HasErrors = false;
$RecordDeletedSuccessfully = false;
    
$StaffCategoryList = array();
$StaffCategoryList = array('Teaching' => 'Teaching Staff', 'NonTeaching' => 'Non Teaching Staff');

$AllLeaveTypeList = array();

$Priorities = array();

$Clean = array();

$Clean['StaffCategory'] = 'Teaching';

if (isset($_GET['StaffCategory']))
{
    $Clean['StaffCategory'] = strip_tags(trim($_GET['StaffCategory']));

    if (!array_key_exists($Clean['StaffCategory'], $StaffCategoryList))
    {
        header('location:../error.php');
        exit;
    }
}

$Clean['Process'] = 0;
$Clean['LeaveTypeID'] = 0;

if (isset($_GET['Process']))
{
	$Clean['Process'] = (int) $_GET['Process'];
}
elseif (isset($_POST['hdnProcess']))
{
    $Clean['Process'] = (int) $_POST['hdnProcess'];
}
switch ($Clean['Process'])
{
    case 3:
        if (isset($_POST['txtPriority']) && is_array($_POST['txtPriority']))
        {           
            $Priorities = $_POST['txtPriority'];
        }

        if (isset($_POST['hdnStaffCategory'])) 
        {
            $Clean['StaffCategory'] = strip_tags(trim($_POST['hdnStaffCategory']));
        }

        $RecordValidator = new Validator();

        $RecordValidator->ValidateInSelect($Clean['StaffCategory'], $StaffCategoryList, 'Unknown error, please try again.');
        $AllLeaveTypeList = LeaveType::GetAllLeaveTypes($Clean['StaffCategory']);

        $Counter = 1;

        foreach($Priorities as $LeaveTypeID => $Priority )
        { 
            if (!array_key_exists($LeaveTypeID, $AllLeaveTypeList))
            {
                header('location:../error.php');
                exit;
            }

            $RecordValidator->ValidateInteger($Priority, 'Invalid Priority value in row: ' . $Counter . '.', 0);
            $Counter++;
         }

        if ($RecordValidator->HasNotifications())
        {
            $HasErrors = true;
            break;
        }

        if (!LeaveType::UpdateLeaveTypePriorities($Priorities))
        {
            $RecordValidator->AttachTextError('There was an error in updating record.');
            $HasErrors = true;
            break;
        }

        header('location:leave_types_list.php?Mode=UD&Process=7&StaffCategory=' . $Clean['StaffCategory']);
        exit;
        break;

    case 5:
    	if ($LoggedUser->HasPermissionForTask(TASK_DELETE_LEAVE_TYPE) !== true)
    	{
    		header('location:unauthorized_login_admin.php');
    		exit;
    	}
    	
    	if (isset($_GET['LeaveTypeID']))
    	{
    		$Clean['LeaveTypeID'] = (int) $_GET['LeaveTypeID'];			
    	}

    	if ($Clean['LeaveTypeID'] <= 0)
    	{
    		header('location:../error.php');
    		exit;
    	}						
    		
    	try
    	{
    		$LeaveTypeToDelete = new LeaveType($Clean['LeaveTypeID']);
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
    	
    	$RecordValidator = new Validator();
    	
    	if ($LeaveTypeToDelete->CheckDependencies())
    	{
    		$RecordValidator->AttachTextError('This leave type cannot be deleted. There are dependent records for this leave type.');
    		$HasErrors = true;
    		break;
    	}
    			
    	if (!$LeaveTypeToDelete->Remove())
    	{
    		$RecordValidator->AttachTextError(ProcessErrors($LeaveTypeToDelete->GetLastErrorCode()));
    		$HasErrors = true;
    		break;
    	}
    	
    	header('location:leave_types_list.php?Mode=DD&Process=7&StaffCategory=' . $Clean['StaffCategory']);
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

        $AllLeaveTypeList = LeaveType::GetAllLeaveTypes($Clean['StaffCategory']);
        break;  
}
    
require_once('../html_header.php');
?>
<title>Leave Types List</title>
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
                    <h1 class="page-header">Leave Types List</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
            <!-- /.row -->

            <div class="row">
                <div class="col-lg-12">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <strong>Staff Category</strong>
                        </div>
<?php
                        if ($HasErrors == true)
                        {
                            echo $RecordValidator->DisplayErrorsInTable();
                        }
?>
                        <div class="panel-body">
                            <form class="form-horizontal" name="FilterLeaveTypes" action="leave_types_list.php" method="post">
                                <div class="form-group">
                                    <label for="StaffCategory" class="col-lg-2 control-label">Select Staff Category</label>
                                    <div class="col-lg-4">
                                        <select class="form-control" name="drdStaffCategory">
<?php
                                        foreach ($StaffCategoryList as $StaffCategory => $StaffCategoryName)
                                        {
?>
                                            <option value="<?php echo $StaffCategory; ?>" <?php echo ($StaffCategory == $Clean['StaffCategory'] ? 'selected="selected"' : ''); ?> ><?php echo $StaffCategoryName; ?></option>
<?php
                                        }
?>
                                        </select>
                                    </div>
                                </div>  
                                <div class="form-group">
                                    <div class="col-sm-offset-2 col-lg-10">
                                        <input type="hidden" name="hdnProcess" value="7"/>
                                        <button type="submit" class="btn btn-primary">View List</button>
                                    </div>
                                </div>
                        </form>                          
                        </div>
                    </div>
                </div>    
            </div>
<?php
        if($Clean['Process'] == 7)
        {
?>
            <div class="row">
                <div class="col-lg-12">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <strong>Total Records Returned: <?php echo(count($AllLeaveTypeList) > 0) ? count($AllLeaveTypeList) : '0'; ?></strong>
                        </div>

                        <!-- /.panel-heading -->
                        <div class="panel-body">
                            <div>
                                <div class="row">
                                    <div class="col-lg-6">
                                    <div class="add-new-btn-container"><a href="add_leave_type.php?StaffCategory=<?php echo $Clean['StaffCategory'];?>" class="btn btn-primary<?php echo $LoggedUser->HasPermissionForTask(TASK_ADD_LEAVE_TYPE) === true ? '' : ' disabled'; ?>" role="button">Add New Leave Type</a></div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="print-btn-container"><button id="PrintButton" type="submit" class="btn btn-primary">Print</button></div>
                                    </div>
                                </div>

                                <div class="row" id="RecordTableHeading">
                                    <div class="col-lg-12">
                                        <div class="report-heading-container"><strong>Leave Types on <?php echo date('d-m-Y h:i A'); ?></strong></div>
                                    </div>
                                </div>
                                <form action="leave_types_list.php" method="post">
                                    <div class="row" id="RecordTable">
                                        <div class="col-lg-12">
                                            <table width="100%" class="table table-striped table-bordered table-hover" id="DataTableRecords">
                                                <thead>
                                                    <tr>
                                                        <th>S. No</th>
                                                        <th>leave Type</th>
                                                        <th>No Of Leaves Allowed</th>
                                                        <th>Leave Mode</th>
                                                        <th>Leave Pay Type</th>
                                                        <th>Monthly Carry Forward</th>
                                                        <th>Priority</th>
                                                        <th>Create User</th>
                                                        <th>Create Date</th>
                                                        <th class="print-hidden">Edit</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
<?php
                                                if (is_array($AllLeaveTypeList) && count($AllLeaveTypeList) > 0)
                                                {
                                                    $Counter = 0;

                                                    foreach ($AllLeaveTypeList as $LeaveTypeID => $LeaveTypeDetails)
                                                    {
?>
                                                    <tr>
                                                        <td><?php echo ++$Counter; ?></td>
                                                        <td><?php echo $LeaveTypeDetails['LeaveType']; ?></td>
                                                        <td><?php echo $LeaveTypeDetails['NoOfLeaves']; ?></td>

                                                        <td><?php echo $LeaveTypeDetails['LeaveMode']; ?></td>
                                                        <td><?php echo ($LeaveTypeDetails['LeavePayType'] == 'WithoutPay') ? 'Without Pay': 'WithPay'; ?></td>
                                                        <td><?php echo (($LeaveTypeDetails['MonthlyCarryForward']) ? 'Yes' : 'No'); ?></td>

                                                        <td><input type="text" class="form-control" name="txtPriority[<?php echo $LeaveTypeID; ?>]" style="width: 50px;" value="<?php echo $LeaveTypeDetails['Priority']; ?>"></td>
                                                        <td><?php echo $LeaveTypeDetails['CreateUserName']; ?></td>
                                                        <td><?php echo date('d/m/Y', strtotime($LeaveTypeDetails['CreateDate'])); ?></td>
                                                        <td class="print-hidden">
<?php
                                                        if ($LoggedUser->HasPermissionForTask(TASK_EDIT_LEAVE_TYPE) === true)
                                                        {
                                                            echo '<a href="edit_leave_type.php?Process=2&amp;LeaveTypeID='.$LeaveTypeID.'">Edit</a>';
                                                        }
                                                        else
                                                        {
                                                            echo 'Edit';
                                                        }

                                                        echo '&nbsp;|&nbsp;';

                                                        if ($LoggedUser->HasPermissionForTask(TASK_DELETE_LEAVE_TYPE) === true)
                                                        {
                                                            echo '<a class="delete-record" href="leave_types_list.php?Process=5&amp;LeaveTypeID='.$LeaveTypeID.'">Delete</a>';
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
?>
                                                    <tr><td colspan="11" style="text-align:right;">
                                                    <input type="hidden" value="3" name="hdnProcess" />
                                                    <input type="hidden" name="hdnStaffCategory" value="<?php echo $Clean['StaffCategory']; ?>"/>
                                                    <button type="submit" class="btn btn-success">Update</button>
                                                    </td></tr>
<?php                                            
                                                }
                                                else
                                                {
?>
                                                    <tr>
                                                        <td colspan="10">No Records</td>
                                                    </tr>
<?php
                                                }
?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </form>
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

    $('#DataTableRecords').DataTable({
        responsive: true,
        bPaginate: false,
        bSort: false,
        searching: false, 
        info: false
    });
    
	$(".delete-record").click(function()
    {	
        if (!confirm("Are you sure you want to delete this Leave Type?"))
        {
            return false;
        }
    });
});
</script>
<!-- JavaScript To Print A 	 -->
<script src="/admin/js/print-report.js"></script>
</body>
</html>
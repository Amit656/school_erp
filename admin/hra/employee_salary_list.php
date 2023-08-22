<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once("../../classes/school_administration/class.branch_staff.php");
require_once("../../classes/hra/class.employee_salary.php");

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

if ($LoggedUser->HasPermissionForTask(TASK_LIST_EMPLOYEE_SALARY) !== true)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}

$Clean = array();
$Clean['StaffCategory'] = 'Teaching';

    
$StaffCategoryList = array();
$StaffCategoryList = array('Teaching' => 'Teaching Staff', 'NonTeaching' => 'Non Teaching Staff');

if (isset($_GET['StaffCategory']))
{
    $Clean['StaffCategory'] = strip_tags(trim($_GET['StaffCategory']));

    if (!array_key_exists($Clean['StaffCategory'], $StaffCategoryList))
    {
        header('location:../error.php');
    }
}

$HasErrors = false;
$RecordDeletedSuccessfully = false;

$AllEmployeeSalaryList = array();
$SalaryDetails = array();

$Clean['Process'] = 0;
$Clean['EmployeeSalaryID'] = 0;

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
    case 5:
    	if ($LoggedUser->HasPermissionForTask(TASK_DELETE_EMPLOYEE_SALARY) !== true)
    	{
    		header('location:unauthorized_login_admin.php');
    		exit;
    	}
    	
    	if (isset($_GET['EmployeeSalaryID']))
    	{
    		$Clean['EmployeeSalaryID'] = (int) $_GET['EmployeeSalaryID'];			
    	}
        
    	if ($Clean['EmployeeSalaryID'] <= 0)
    	{
    		header('location:../error.php');
    		exit;
    	}						
    		
    	try
    	{
    		$EmployeeSalaryToDelete = new EmployeeSalary($Clean['EmployeeSalaryID']);
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
     			
    	if (!$EmployeeSalaryToDelete->Remove())
    	{
    		$RecordValidator->AttachTextError(ProcessErrors($EmployeeSalaryToDelete->GetLastErrorCode()));
    		$HasErrors = true;
    		break;
    	}
    	
    	header('location:employee_salary_list.php?Mode=ED&Process=7&StaffCategory=' . $Clean['StaffCategory']);
        break;

    case 7:
        if (isset($_POST['drdStaffCategory']))
        {
            $Clean['StaffCategory'] = strip_tags(trim($_POST['drdStaffCategory']));           
        }

        $NewRecordValidator = new Validator();

        $NewRecordValidator->ValidateInSelect($Clean['StaffCategory'], $StaffCategoryList, 'Unknown Error, Please try again.');

        if ($NewRecordValidator->HasNotifications())
        {
            $HasErrors = true;
            break;
        }

        $AllEmployeeSalaryList = EmployeeSalary::GetAllEmployeeSalaries($Clean['StaffCategory']);
        $SalaryDetails = EmployeeSalary::GetAllEmployeeSalaryDetails($Clean['StaffCategory']);
        break;  
}
  
require_once('../html_header.php');
?>
<title>Employees Salary List</title>
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
                    <h1 class="page-header">Employees Salary List</h1>
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
                            <form class="form-horizontal" name="FilterLeaveTypes" action="employee_salary_list.php" method="post">
                                <div class="form-group">
                                    <label for="StaffCategory" class="col-lg-2 control-label">Select Staff Category</label>
                                    <div class="col-lg-4">
                                        <select class="form-control"  name="drdStaffCategory">
<?php
                                        foreach ($StaffCategoryList as $Key => $StaffCategoryDetails)
                                        {
?>
                                            <option <?php echo ($Key == $Clean['StaffCategory'] ? 'selected="selected"' : ''); ?> value="<?php echo $Key; ?>"><?php echo $StaffCategoryDetails; ?></option>
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
                            <strong>Total Records Returned: <?php echo(count($AllEmployeeSalaryList) > 0) ? count($AllEmployeeSalaryList) : '0'; ?></strong>
                        </div>

                        <!-- /.panel-heading -->
                        <div class="panel-body">
                            <div>
                                <div class="row">
                                    <div class="col-lg-6">
                                    <div class="add-new-btn-container"><a href="add_employee_salary.php?StaffCategory=<?php echo $Clean['StaffCategory'];?>" class="btn btn-primary<?php echo $LoggedUser->HasPermissionForTask(TASK_ADD_EMPLOYEE_SALARY) === true ? '' : ' disabled'; ?>" role="button">Add Employee Salary</a></div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="print-btn-container"><button id="PrintButton" type="submit" class="btn btn-primary">Print</button></div>
                                    </div>
                                </div>
                                <div class="row" id="RecordTableHeading">
                                    <div class="col-lg-12">
                                        <div class="report-heading-container"><strong>Employees Salary on <?php echo date('d-m-Y h:i A'); ?></strong></div>
                                    </div>
                                </div>
                                <form  method="post">
                                    <div class="row" id="RecordTable">
                                        <div class="col-lg-12">
                                            <table width="100%" class="table table-striped table-bordered table-hover" id="DataTableRecords">
                                                <thead>
                                                    <tr>
                                                        <th>S. No</th>
                                                        <th>Employee Name</th>
                                                        <th>Salary Type</th>
                                                        <th>Basic Salary(in ₹ )</th>
                                                        <th>Allowances(in ₹ )</th>
                                                        <th>Deductions(in ₹ )</th>
                                                        <th>Is Active</th>
                                                        <th>Create User</th>
                                                        <th>Create Date</th>
                                                        <th class="print-hidden">Operation</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
<?php
                                                if (is_array($AllEmployeeSalaryList) && count($AllEmployeeSalaryList) > 0)
                                                {
                                                    $Counter = 0;

                                                    foreach ($AllEmployeeSalaryList as $EmployeeSalaryID => $EmployeeSalaryDetails)
                                                    {
?>
                                                        <tr>
                                                            <td><?php echo ++$Counter; ?></td>
                                                            <td><?php echo $EmployeeSalaryDetails['FirstName'] . " " . $EmployeeSalaryDetails['LastName'] ; ?></td>
                                                            <td><?php echo$EmployeeSalaryDetails['SalaryType'];?></td>
                                                            <td><?php echo $EmployeeSalaryDetails['BasicSalary']; ?></td>
                                                            <td>
<?php
                                                            $Allowances = 0;

                                                            foreach ($SalaryDetails as $EmployeeSalaryDetailID => $SalaryDetailsValue) 
                                                            {
                                                                if ($SalaryDetailsValue['EmployeeSalaryID'] == $EmployeeSalaryID && $SalaryDetailsValue['SalaryPartType'] == 'Allowance')
                                                                {
                                                                     $Allowances += $SalaryDetailsValue['Amount'];
                                                                }
                                                            }

                                                            echo $Allowances;
?>
                                                            </td>
                                                            <td>
<?php
                                                            $Deductions = 0;
                                                            foreach ($SalaryDetails as $EmployeeSalaryDetailID => $SalaryDetailsValue) 
                                                            {
                                                                if ($SalaryDetailsValue['EmployeeSalaryID'] == $EmployeeSalaryID && $SalaryDetailsValue['SalaryPartType'] == 'Deduction')
                                                                {
                                                                     $Deductions += $SalaryDetailsValue['Amount'];
                                                                }
                                                            }

                                                            echo $Deductions;
?>
                                                            </td>
                                                            <td><?php echo ($EmployeeSalaryDetails['IsActive']) ? 'Yes' : 'No'; ?></td>
                                                            <td><?php echo $EmployeeSalaryDetails['CreateUserName']; ?></td>
                                                            <td><?php echo date('d/m/Y',strtotime($EmployeeSalaryDetails['CreateDate'])); ?></td>
                                                            <td class="print-hidden">
<?php
                                                                if ($LoggedUser->HasPermissionForTask(TASK_EDIT_EMPLOYEE_SALARY) === true)
                                                                {
                                                                    echo '<a href="edit_employee_salary.php?Process=2&amp;EmployeeSalaryID='.$EmployeeSalaryID.'">Edit</a>';
                                                                }
                                                                else
                                                                {
                                                                    echo 'Edit';
                                                                }

                                                                echo '&nbsp;|&nbsp;';

                                                                if ($LoggedUser->HasPermissionForTask(TASK_DELETE_EMPLOYEE_SALARY) === true)
                                                                {
                                                                    echo '<a class="delete-record" href="employee_salary_list.php?Process=5&amp;EmployeeSalaryID='.$EmployeeSalaryID.'">Delete</a>';
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
                                                            <td colspan="9">No Records</td>
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
        if (!confirm("Are you sure you want to delete this Employees Salary?"))
        {
            return false;
        }
    });
});
</script>
<!-- JavaScript To Print A 	 -->
<script src="../js/print-report.js"></script>
</body>
</html>
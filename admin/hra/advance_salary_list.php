<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once("../../classes/hra/class.advance_salary.php");

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

if ($LoggedUser->HasPermissionForTask(TASK_LIST_ADVANCE_SALARY) !== true)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}

$AllAdvanceTypeList = array('GeneralAdvance' => 'General Advance', 'InterestFreeLoan' => 'Interest Free Loan');

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

$AllAdvanceSalaryList = array();

$Clean['Process'] = 0;
$Clean['AdvanceSalaryID'] = 0;

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
    	if ($LoggedUser->HasPermissionForTask(TASK_DELETE_ADVANCE_SALARY) !== true)
    	{
    		header('location:unauthorized_login_admin.php');
    		exit;
    	}
    	
    	if (isset($_GET['AdvanceSalaryID']))
    	{
    		$Clean['AdvanceSalaryID'] = (int) $_GET['AdvanceSalaryID'];			
    	}
        
    	if ($Clean['AdvanceSalaryID'] <= 0)
    	{
    		header('location:../error.php');
    		exit;
    	}						
    		
    	try
    	{
    		$AdvanceSalaryToDelete = new AdvanceSalary($Clean['AdvanceSalaryID']);
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

        if ($AdvanceSalaryToDelete->CheckDependencies())
        {
            $RecordValidator->AttachTextError('This advance salary cannot be deleted. There are dependent records for this advance salary.');
            $HasErrors = true;
            break;
        }
     			
    	if (!$AdvanceSalaryToDelete->Remove())
    	{
    		$RecordValidator->AttachTextError(ProcessErrors($AdvanceSalaryToDelete->GetLastErrorCode()));
    		$HasErrors = true;
    		break;
    	}
    	
    	header('location:advance_salary_list.php?Mode=ED&Process=7&StaffCategory=' . $Clean['StaffCategory']);
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

        $AllAdvanceSalaryList = AdvanceSalary::GetAllAdvanceSalaries($Clean['StaffCategory']);
        break;  
}
  
require_once('../html_header.php');
?>
<title>Advance Salary List</title>
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
                    <h1 class="page-header">Advance Salary List</h1>
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
                            <form class="form-horizontal" name="FilterAdvanceSalary" action="advance_salary_list.php" method="post">
                                <div class="form-group">
                                    <label for="StaffCategory" class="col-lg-2 control-label">Staff Category</label>
                                    <div class="col-lg-4">
                                        <select class="form-control"  name="drdStaffCategory">
<?php
                                        foreach ($StaffCategoryList as $StaffCategory => $StaffCategoryname)
                                        {
?>
                                            <option <?php echo ($StaffCategory == $Clean['StaffCategory'] ? 'selected="selected"' : ''); ?> value="<?php echo $StaffCategory; ?>"><?php echo $StaffCategoryname; ?></option>
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
                            <strong>Total Records Returned: <?php echo(count($AllAdvanceSalaryList) > 0) ? count($AllAdvanceSalaryList) : '0'; ?></strong>
                        </div>
                        <!-- /.panel-heading -->
                        <div class="panel-body">
                            <div>
                                <div class="row">
                                    <div class="col-lg-6">
                                    <div class="add-new-btn-container"><a href="add_advance_salary.php?StaffCategory=<?php echo $Clean['StaffCategory'];?>" class="btn btn-primary<?php echo $LoggedUser->HasPermissionForTask(TASK_ADD_ADVANCE_SALARY) === true ? '' : ' disabled'; ?>" role="button">Advance Salary</a></div>
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
                                                        <th>Advance Amount</th>
                                                        <th>Remaining Instalment Amount</th>
                                                        <th>Payment Mode</th>
                                                        <th>Advance Type</th>
                                                        <th>No Of Installments</th>
                                                        <th>Create User</th>
                                                        <th>Create Date</th>
                                                        <th class="print-hidden">Operation</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
<?php
                                                if (is_array($AllAdvanceSalaryList) && count($AllAdvanceSalaryList) > 0)
                                                {
                                                    $Counter = 0;
                                                    $FormateCurrencyObj = new NumberFormatter($locale = 'en_IN', NumberFormatter::DECIMAL);

                                                    foreach ($AllAdvanceSalaryList as $AdvanceSalaryID => $AdvanceSalaryDetails)
                                                    {
                                                        $TotalPaidAmount = 0;

                                                        if ($AdvanceSalaryDetails['AdvanceType'] == 'InterestFreeLoan') 
                                                        {
                                                            $TotalPaidAmount = $AdvanceSalaryDetails['TotalPaidAmount'];
                                                        }
?>
                                                    <tr>
                                                        <td><?php echo ++$Counter; ?></td>
                                                        <td><?php echo $AdvanceSalaryDetails['FirstName'] . " " . $AdvanceSalaryDetails['LastName'] ; ?></td>
                                                        <td class="text-center"><?php echo $FormateCurrencyObj->format($AdvanceSalaryDetails['AdvanceAmount']);?></td>
                                                        <td class="text-center"><?php echo (is_null($TotalPaidAmount)) ? $FormateCurrencyObj->format($AdvanceSalaryDetails['AdvanceAmount']) : ($FormateCurrencyObj->format($AdvanceSalaryDetails['AdvanceAmount'] - $TotalPaidAmount)) ;?></td>
                                                        <td class="text-center"><?php echo $AdvanceSalaryDetails['PaymentMode']; ?></td>
                                                        <td><?php echo $AllAdvanceTypeList[$AdvanceSalaryDetails['AdvanceType']]; ?></td>
                                                        <td class="text-center"><?php echo $AdvanceSalaryDetails['NoOfInstallments']; ?></td>
                                                        <td><?php echo $AdvanceSalaryDetails['CreateUserName']; ?></td>
                                                        <td><?php echo date('d/m/Y',strtotime($AdvanceSalaryDetails['CreateDate'])); ?></td>
                                                        <td class="print-hidden">
<?php
                                                            if ($LoggedUser->HasPermissionForTask(TASK_VIEW_ADVANCE_SALARY) === true)
                                                            {
                                                                echo '<a href="edit_advance_salary.php?Process=2&amp;ViewOnly=true&amp;AdvanceSalaryID=' . $AdvanceSalaryID . '&StaffCategory=' . $Clean['StaffCategory'] . '" target="_blank">View</a>';
                                                            }
                                                            else
                                                            {
                                                                echo 'View';
                                                            }

                                                            echo '&nbsp;|&nbsp;';

                                                            if ($LoggedUser->HasPermissionForTask(TASK_EDIT_ADVANCE_SALARY) === true)
                                                            {
                                                                echo '<a href="edit_advance_salary.php?Process=2&amp;AdvanceSalaryID=' . $AdvanceSalaryID . '">Edit</a>';
                                                            }
                                                            else
                                                            {
                                                                echo 'Edit';
                                                            }

                                                            echo '&nbsp;|&nbsp;';

                                                            if ($LoggedUser->HasPermissionForTask(TASK_DELETE_ADVANCE_SALARY) === true)
                                                            {
                                                                echo '<a class="delete-record" href="advance_salary_list.php?Process=5&amp;StaffCategory=' . $Clean['StaffCategory'] . '&AdvanceSalaryID=' . $AdvanceSalaryID . '">Delete</a>'; 
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
        if (!confirm("Are you sure you want to delete this advance salary?"))
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
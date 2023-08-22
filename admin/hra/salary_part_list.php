<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once("../../classes/hra/class.salary_parts.php");

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

if ($LoggedUser->HasPermissionForTask(TASK_LIST_SALARY_PART) !== true)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}

$HasErrors = false;
    
$SalaryPartList = array();
$SalaryPartList = array('Allowance' => 'Allowance', 'Deduction' => 'Deduction');

$AllSalaryPartList = array();

$Priorities = array();

$Clean = array();
$Clean['SalaryPartType'] = '';

if (isset($_GET['SalaryPartType']))
{
    $Clean['SalaryPartType'] = strip_tags(trim($_GET['SalaryPartType']));

    if (!array_key_exists($Clean['SalaryPartType'], $SalaryPartList))
    {   
        header('location:../error.php');
        exit;
    }
}

$Clean['Process'] = 0;
$Clean['SalaryPartID'] = 0;

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

        if (isset($_POST['hdnSalaryPartType']))
        {           
            $Clean['SalaryPartType'] = strip_tags(trim($_POST['hdnSalaryPartType']));
        }

        $RecordValidator = new Validator();

        $Counter = 1;

        foreach($Priorities as $SalaryPartID => $Priority )
        { 
            
            if (!array_key_exists($SalaryPartID, $Priorities))
            {
                header('location:../error.php');
                exit;
            }

            $RecordValidator->ValidateInteger($Priority, 'Invalid Priority value in row: ' . $Counter . '.', 0);
            $Counter++;
        }

        $RecordValidator->ValidateInSelect($Clean['SalaryPartType'], $SalaryPartList, 'Unknown error, please try again.');

        if ($RecordValidator->HasNotifications())
        {
            $HasErrors = true;
            break;
        }

        if (!SalaryPart::UpdateSalaryPartPriorities($Priorities))
        {
            $RecordValidator->AttachTextError('There was an error in updating record.');
            $HasErrors = true;
            break;
        }
               
        header('location:salary_part_list.php?Mode=UD&Process=7&SalaryPartType=' . $Clean['SalaryPartType']);
        exit;
        break;

    case 5:
    	if ($LoggedUser->HasPermissionForTask(TASK_DELETE_SALARY_PART) !== true)
    	{
    		header('location:unauthorized_login_admin.php');
    		exit;
    	}
    	
    	if (isset($_GET['SalaryPartID']))
    	{
    		$Clean['SalaryPartID'] = (int) $_GET['SalaryPartID'];			
    	}
    	
    	if ($Clean['SalaryPartID'] <= 0)
    	{
    		header('location:../error.php');
    		exit;
    	}						
    		
    	try
    	{
    		$SalaryPartToDelete = new SalaryPart($Clean['SalaryPartID']);
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
    	
    	if ($SalaryPartToDelete->CheckDependencies())
    	{
    		$RecordValidator->AttachTextError('This salary part cannot be deleted. There are dependent records for this salary part.');
    		$HasErrors = true;
    		break;
    	}
    			
    	if (!$SalaryPartToDelete->Remove())
    	{
    		$RecordValidator->AttachTextError(ProcessErrors($SalaryPartToDelete->GetLastErrorCode()));
    		$HasErrors = true;
    		break;
    	}
    	
    	header('location:salary_part_list.php?Mode=DD&Process=7&SalaryPartType=' . $Clean['SalaryPartType']);
        break;

    case 7:
        if (isset($_POST['drdSalaryPartType']))
        {
            $Clean['SalaryPartType'] = strip_tags(trim($_POST['drdSalaryPartType']));           
        }

        $NewRecordValidator = new Validator();

        $NewRecordValidator->ValidateInSelect($Clean['SalaryPartType'], $SalaryPartList, 'Unknown Error, Please try again.');

        if ($NewRecordValidator->HasNotifications())
        {
            $HasErrors = true;
            break;
        }

        $AllSalaryPartList = SalaryPart::GetAllSalaryParts($Clean['SalaryPartType']);
        break;  
}
    
require_once('../html_header.php');
?>
<title>Salary Part List</title>
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
                    <h1 class="page-header">Salary Part List</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
            <!-- /.row -->

            <div class="row">
                <div class="col-lg-12">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <strong>Salary Part Category</strong>
                        </div>
<?php
                            if ($HasErrors == true)
                            {
                                echo $RecordValidator->DisplayErrorsInTable();
                            }
?>
                        <div class="panel-body">
                            <form class="form-horizontal" name="FilterSalaryPart" action="salary_part_list.php" method="post">
                                <div class="form-group">
                                    <label for="SalaryPartType" class="col-lg-2 control-label">Salary Part Type</label>
                                    <div class="col-lg-4">
                                        <select class="form-control" name="drdSalaryPartType">
<?php
                                        foreach ($SalaryPartList as $SalaryPartID => $SalaryPartTypeDetails)
                                        {
?>
                                            <option <?php echo ($SalaryPartID == $Clean['SalaryPartType'] ? 'selected="selected"' : ''); ?> value="<?php echo $SalaryPartID; ?>"><?php echo $SalaryPartTypeDetails; ?></option>
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
                            <strong>Total Records Returned: <?php echo(count($AllSalaryPartList) > 0) ? count($AllSalaryPartList) : '0'; ?></strong>
                        </div>

                        <!-- /.panel-heading -->
                        <div class="panel-body">
                            <div>
                                <div class="row">
                                    <div class="col-lg-6">
                                    <div class="add-new-btn-container"><a href="add_salary_part.php?SalaryPartType=<?php echo$Clean['SalaryPartType'];?> " class="btn btn-primary<?php echo $LoggedUser->HasPermissionForTask(TASK_ADD_SALARY_PART) === true ? '' : ' disabled'; ?>" role="button">Add New Salary Part</a></div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="print-btn-container"><button id="PrintButton" type="submit" class="btn btn-primary">Print</button></div>
                                    </div>
                                </div>

                                <div class="row" id="RecordTableHeading">
                                    <div class="col-lg-12">
                                        <div class="report-heading-container"><strong>Salary Part on<?php echo date('d-m-Y h:i A'); ?></strong></div>
                                    </div>
                                </div>
                                <form action="salary_part_list.php" method="post">
                                    <div class="row" id="RecordTable">
                                        <div class="col-lg-12">
                                            <table width="100%" class="table table-striped table-bordered table-hover" id="DataTableRecords">
                                                <thead>
                                                    <tr>
                                                        <th>S. No</th>
                                                        <th>Salary Part Type</th>
                                                        <th>Salary Part Name</th>
                                                        <th>Priority</th>
                                                        <th>Is Active</th>
                                                        <th>Create User</th>
                                                        <th>Create Date</th>
                                                        <th class="print-hidden">Operation</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
<?php
                                                if (is_array($AllSalaryPartList) && count($AllSalaryPartList) > 0)
                                                {
                                                    $Counter = 0;
                                                    foreach ($AllSalaryPartList as $SalaryPartID => $SalaryPartDetails)
                                                    {
?>
                                                    <tr>
                                                        <td><?php echo ++$Counter; ?></td>
                                                        <td><?php echo $SalaryPartDetails['SalaryPartType']; ?></td>
                                                        <td><?php echo $SalaryPartDetails['SalaryPartName']; ?></td>
                                                        <td><input type="text" class="form-control" name="txtPriority[<?php echo $SalaryPartID; ?>]" style="width: 50px;" value="<?php echo $SalaryPartDetails['Priority']; ?>"></td>
                                                        <td><?php echo (($SalaryPartDetails['IsActive']) ? 'Yes' : 'No'); ?></td>
                                                        <td><?php echo $SalaryPartDetails['CreateUserName']; ?></td>
                                                        <td><?php echo date('d/m/Y', strtotime($SalaryPartDetails['CreateDate'])); ?></td>
                                                        <td class="print-hidden">
<?php
                                                            if ($LoggedUser->HasPermissionForTask(TASK_EDIT_SALARY_PART) === true)
                                                            {
                                                                echo '<a href="edit_salary_part.php?Process=2&amp;SalaryPartID='.$SalaryPartID.'">Edit</a>';
                                                            }
                                                            else
                                                            {
                                                                echo 'Edit';
                                                            }

                                                            echo '&nbsp;|&nbsp;';

                                                            if ($LoggedUser->HasPermissionForTask(TASK_DELETE_SALARY_PART) === true)
                                                            {
                                                                echo '<a class="delete-record" href="salary_part_list.php?Process=5&amp;&amp;SalaryPartType=' . $SalaryPartDetails['SalaryPartType'] . 'SalaryPartID='.$SalaryPartID.'">Delete</a>';
                                                            }
                                                            else
                                                            {
                                                                echo 'Delete';
                                                            }
?>
                                                    </tr>
<?php
                                                    }
?>
                                                    <tr><td colspan="8" style="text-align:right;">
                                                    <input type="hidden" name="hdnProcess" value="3" />
                                                    <input type="hidden" name="hdnSalaryPartType" value="<?php echo $Clean['SalaryPartType']; ?>" />
                                                    <button type="submit" class="btn btn-success">Update</button>
                                                    </td></tr>
<?php                                            
                                                }
                                                else
                                                {
?>
                                                    <tr>
                                                        <td colspan="8">No Records</td>
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
        if (!confirm("Are you sure you want to delete this Salary Part?"))
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
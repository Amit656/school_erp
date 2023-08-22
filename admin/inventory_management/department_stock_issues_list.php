<?php
require_once("../../classes/class.users.php");
require_once('../../classes/class.validation.php');
require_once("../../classes/class.authentication.php");

require_once("../../classes/inventory_management/class.products.php");
require_once("../../classes/inventory_management/class.departments.php");
require_once("../../classes/inventory_management/class.stock_issue.php");
require_once("../../classes/inventory_management/class.department_stock_issue.php");

require_once("../../classes/class.ui_helpers.php");

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

if ($LoggedUser->HasPermissionForTask(TASK_LIST_STOCK_ISSUE_TO_DEPARTMENT) !== true)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}

$IssuedToTypeList = array('Student' => 'Student', 'Staff' => 'Staff');

$AllDepartmentList = array();
$AllDepartmentList = Department::GetActiveDepartments();

$AllProductList = array();
$AllDepartmentStockIssues = array();

$HasErrors = false;

$Clean = array();
$Clean['Process'] = 0;

$Clean['DepartmentStockIssueID'] = 0;

$Clean['DepartmentID'] = 0;
$Clean['ProductID'] = 0;
$Clean['IssuedToType'] = '';
$Clean['IssueDate'] = '';
$Clean['ReturnDate'] = '';

// paging and sorting variables start here  //
$Clean['CurrentPage'] = 1;
$TotalPages = 0;

$Start = 0;
$Limit = 1;
// end of paging variables //

if (isset($_GET['Process']))
{
	$Clean['Process'] = (int) $_GET['Process'];
}
elseif (isset($_GET['hdnProcess']))
{
    $Clean['Process'] = (int) $_GET['hdnProcess'];
}
switch ($Clean['Process'])
{
	case 5:
		if ($LoggedUser->HasPermissionForTask(TASK_DELETE_STOCK_ISSUE_TO_DEPARTMENT) !== true)
		{
			header('location:unauthorized_login_admin.php');
			exit;
		}
		
		if (isset($_GET['DepartmentStockIssueID']))
		{
			$Clean['DepartmentStockIssueID'] = (int) $_GET['DepartmentStockIssueID'];			
		}
		
		if ($Clean['DepartmentStockIssueID'] <= 0)
		{
			header('location:../error.php');
			exit;
		}						
			
		try
		{
			$DepartmentStockIssueToDelete = new DepartmentStockIssue($Clean['DepartmentStockIssueID']);
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
		
		// if ($DepartmentStockIssueToDelete->CheckDependencies())
		// {
		// 	$RecordValidator->AttachTextError('This master product unit cannot be deleted. There are dependent records for this master product unit.');
		// 	$HasErrors = true;
		// 	break;
		// }
				
		if (!$DepartmentStockIssueToDelete->Remove())
		{
			$RecordValidator->AttachTextError(ProcessErrors($DepartmentStockIssueToDelete->GetLastErrorCode()));
			$HasErrors = true;
			break;
		}
		
		header('location:department_stock_issues_list.php?Mode=DD');
	    break;

    case 7:
        if (isset($_GET['drdDepartment']))
        {
            $Clean['DepartmentID'] = (int) $_GET['drdDepartment'];
        }
        elseif (isset($_GET['DepartmentID']))
        {
            $Clean['DepartmentID'] = (int) $_GET['DepartmentID'];
        }

        if (isset($_GET['drdProduct']))
        {
            $Clean['ProductID'] = (int) $_GET['drdProduct'];
        }
        elseif (isset($_GET['ProductID']))
        {
            $Clean['ProductID'] = (int) $_GET['ProductID'];
        }

        if (isset($_GET['drdIssuedToType']))
        {
            $Clean['IssuedToType'] = strip_tags(trim($_GET['drdIssuedToType']));
        }
        elseif (isset($_GET['IssuedToType']))
        {
            $Clean['IssuedToType'] = strip_tags(trim($_GET['IssuedToType']));
        }

        if (isset($_GET['txtIssueDate']))
        {
            $Clean['IssueDate'] = strip_tags(trim($_GET['txtIssueDate']));
        }
        elseif (isset($_GET['IssueDate']))
        {
            $Clean['IssueDate'] = strip_tags(trim($_GET['IssueDate']));
        }

        if (isset($_GET['txtReturnDate']))
        {
            $Clean['ReturnDate'] = strip_tags(trim($_GET['txtReturnDate']));
        }
        elseif (isset($_GET['ReturnDate']))
        {
            $Clean['ReturnDate'] = strip_tags(trim($_GET['ReturnDate']));
        }
        
        $RecordValidator = new Validator();

        if ($Clean['DepartmentID'] != 0) 
        {
            $RecordValidator->ValidateInSelect($Clean['DepartmentID'], $AllDepartmentList, 'Unknown error, please try again.');

             if ($RecordValidator->HasNotifications())
            {
                $HasErrors = true;
                break;
            }

            $AllProductList = StockIssue::GetProductsByDepartmentID($Clean['DepartmentID']);
        }
        
        if ($Clean['ProductID'] != 0) 
        {
            $RecordValidator->ValidateInSelect($Clean['ProductID'], $AllProductList, 'Unknown error, please try again.');
        }
        
        if (!empty($Clean['IssuedToType'])) 
        {
            $RecordValidator->ValidateInSelect($Clean['IssuedToType'], $IssuedToTypeList, 'Unknown error, please try again.');
        }

        if ($Clean['IssueDate'] != '') 
        {
            $RecordValidator->ValidateDate($Clean['IssueDate'], 'Please enter valid issue date.');
        }

        if ($Clean['ReturnDate'] != '') 
        {
            $RecordValidator->ValidateDate($Clean['ReturnDate'], 'Please enter valid return  date.');
        }

        if ($RecordValidator->HasNotifications())
        {
            $HasErrors = true;
            break;
        }

        //set record filters
        $Filters['DepartmentID'] = $Clean['DepartmentID'];
        $Filters['ProductID'] = $Clean['ProductID'];
        $Filters['IssuedToType'] = $Clean['IssuedToType'];
        $Filters['IssueDate'] = $Clean['IssueDate'];
        $Filters['ReturnDate'] = $Clean['ReturnDate'];
  
        //get records count
        DepartmentStockIssue::SearchDepartmentStockIssues($TotalRecords, true, $Filters);
  
        if ($TotalRecords > 0)
        {
            // Paging and sorting calculations start here.
            $TotalPages = (($TotalRecords % $Limit) == 0) ? $TotalRecords / $Limit : floor($TotalRecords / $Limit) + 1;

            if (isset($_GET['CurrentPage']))
            {
                $Clean['CurrentPage'] = (int) $_GET['CurrentPage'];
            }

            if ($Clean['CurrentPage'] <= 0)
            {
                $Clean['CurrentPage'] = 1;
            }
            elseif ($Clean['CurrentPage'] > $TotalPages)
            {
                $Clean['CurrentPage'] = $TotalPages;
            }

            if ($Clean['CurrentPage'] > 1)
            {
                $Start = ($Clean['CurrentPage'] - 1) * $Limit;
            }
            
            // end of Paging and sorting calculations.
            // now get the actual  records
            $AllDepartmentStockIssues = DepartmentStockIssue::SearchDepartmentStockIssues($TotalRecords, false, $Filters, $Start, $Limit);
        }

        break;
}       

require_once('../html_header.php');
?>
<title>Department Stock Issue List</title>
<link href="../vendor/jquery-ui/jquery-ui.min.css" rel="stylesheet">
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
                    <h1 class="page-header">Department Stock Issue List</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
            <!-- /.row -->
            <form class="form-horizontal" name="SearchDepartmentStockIssues" action="department_stock_issues_list.php" method="get">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        Filter
                    </div>
                    <div class="panel-body">
<?php
                        if ($HasErrors == true)
                        {
                            echo $RecordValidator->DisplayErrors();
                        }
?>                    
                        <div class="form-group">
                            <label for="Department" class="col-lg-2 control-label">Department</label>
                            <div class="col-lg-4">
                                <select class="form-control" name="drdDepartment" id="Department">
                                    <option value="0">Select-</option>
<?php
                                foreach ($AllDepartmentList as $DepartmentID => $DepartmentName)
                                {
?>
                                    <option <?php echo ($Clean['DepartmentID'] == $DepartmentID) ? 'selected="selected"' : ''; ?> value="<?php echo $DepartmentID; ?>"><?php echo $DepartmentName; ?></option>
<?php
                                }
?>
                                </select>
                            </div>
                            <label for="Product" class="col-lg-2 control-label">Product</label>
                            <div class="col-lg-4">
                                <select class="form-control" name="drdProduct" id="Product">
                                    <option value="0">Select-</option>
<?php
                                foreach ($AllProductList as $ProductID => $ProductName)
                                {
?>
                                    <option <?php echo ($Clean['ProductID'] == $ProductID) ? 'selected="selected"' : ''; ?> value="<?php echo $ProductID; ?>"><?php echo $ProductName; ?></option>
<?php
                                }
?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="IssuedToType" class="col-lg-2 control-label">Issued To Type</label>
                            <div class="col-lg-4">
                                <select class="form-control" name="drdIssuedToType" id="IssuedToType">
                                    <option value="0">Select-</option>
<?php
                                foreach ($IssuedToTypeList as $IssuedToType => $IssueTypeName)
                                {
?>
                                    <option <?php echo ($Clean['IssuedToType'] == $IssuedToType) ? 'selected="selected"' : ''; ?> value="<?php echo $IssuedToType; ?>"><?php echo $IssueTypeName; ?></option>
<?php
                                }
?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="IssueDate" class="col-lg-2 control-label">Issue Date</label>
                            <div class="col-lg-4">
                                <input class="form-control dtepicker" type="text" name="txtIssueDate" value="<?php echo $Clean['IssueDate']; ?>"/>  
                            </div>
                            <label for="ReturnDate" class="col-lg-2 control-label">Return Date</label>
                            <div class="col-lg-4">
                                <input class="form-control dtepicker" type="text" name="txtReturnDate" value="<?php echo $Clean['ReturnDate']; ?>"/>    
                            </div>
                        </div>
                        <div class="form-group">
                        <div class="col-sm-offset-2 col-lg-10">
                            <input type="hidden" name="hdnProcess" value="7" />
                            <button type="submit" class="btn btn-primary">Search</button>
                        </div>
                      </div>
                    </div>
                </div>
            </form>
<?php
        if ($Clean['Process'] == 7 && $HasErrors == false)
        {
?>
            <div class="row">
                <div class="col-lg-12">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <strong>Total Records Returned: <?php echo $TotalRecords; ?></strong>
                        </div>
                        <!-- /.panel-heading -->
                        <div class="panel-body">
                            <div>
                                <div class="row">
                                    <div class="col-lg-6">
                                    <div class="add-new-btn-container"><a href="add_department_stock_issue.php" class="btn btn-primary<?php echo $LoggedUser->HasPermissionForTask(TASK_ADD_STOCK_ISSUE_TO_DEPARTMENT) === true ? '' : ' disabled'; ?>" role="button">Add New Department Stock Issue</a></div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="print-btn-container"><button id="PrintButton" type="submit" class="btn btn-primary">Print</button></div>
                                    </div>
                                </div>
                                <br>
                                <div class="row">
                                   <div class="col-lg-6">
<?php
                                    if ($TotalPages > 1)
                                    {
                                        $AllParameters = $Filters;
                                        $AllParameters['Process'] = '7';

                                        echo UIHelpers::GetPager('department_stock_issues_list.php', $TotalPages, $Clean['CurrentPage'], $AllParameters);
                                    }
?>                                        
                                    </div>
                                    <div class="col-lg-6"></div>  
                                </div>
                                <div class="row" id="RecordTableHeading">
                                    <div class="col-lg-12">
                                        <div class="report-heading-container"><strong>Department Stock Issues on <?php echo date('d-m-Y h:i A'); ?></strong></div>
                                    </div>
                                </div>
                                <div class="row" id="RecordTable">
                                    <div class="col-lg-12">
                                        <table width="100%" class="table table-striped table-bordered table-hover" id="DataTableRecords">
                                            <thead>
                                                <tr>
                                                    <th>S. No</th>
                                                    <th>Department Name</th>
                                                    <th>Product Category Name</th>
                                                    <th>Product Name</th>
                                                    <th>Issued Type</th>
                                                    <th>Issued To</th>
                                                    <th>Issued Quantity</th>
                                                    <th>Issue Date</th>
                                                    <th>Return Date</th>
                                                    <th>Create User</th>
                                                    <th>Create Date</th>
                                                    <th class="print-hidden">Operations</th>
                                                </tr>
                                            </thead>
                                            <tbody>
<?php
                                    if (is_array($AllDepartmentStockIssues) && count($AllDepartmentStockIssues) > 0)
                                    {
                                        $Counter = 0;
                                        foreach ($AllDepartmentStockIssues as $DepartmentStockIssueID => $DepartmentStockIssueDetails)
                                        {
?>
                                            <tr>
                                                <td><?php echo ++$Counter; ?></td>
                                                <td><?php echo $DepartmentStockIssueDetails['DepartmentName']; ?></td>
                                                <td><?php echo $DepartmentStockIssueDetails['ProductCategoryName']; ?></td>
                                                <td><?php echo $DepartmentStockIssueDetails['ProductName']; ?></td>
                                                <td><?php echo $DepartmentStockIssueDetails['IssuedToType']; ?></td>
                                                <td>
<?php
                                                if ($DepartmentStockIssueDetails['IssuedToType'] == 'Student') 
                                                {
                                                    echo $DepartmentStockIssueDetails['StudentName'] . '<br>(' . $DepartmentStockIssueDetails['ClassName'] . ' -' . 
                                                                    $DepartmentStockIssueDetails['SectionName'].')';
                                                }
                                                else
                                                {
                                                    echo $DepartmentStockIssueDetails['BranchStaffName'];
                                                }
?>
                                                </td>
                                                <td><?php echo $DepartmentStockIssueDetails['IssuedQuantity']; ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($DepartmentStockIssueDetails['IssueDate'])); ?></td>
                                                <td><?php echo ($DepartmentStockIssueDetails['ReturnDate'] != '0000-00-00') ? date('d/m/Y', strtotime($DepartmentStockIssueDetails['ReturnDate'])) : ''; ?></td>
                                                <td><?php echo $DepartmentStockIssueDetails['CreateUserName']; ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($DepartmentStockIssueDetails['CreateDate'])); ?></td>
                                                <td class="print-hidden">
<?php
                                                if ($LoggedUser->HasPermissionForTask(TASK_EDIT_STOCK_ISSUE_TO_DEPARTMENT) === true)
                                                {
                                                    echo '<a href="edit_department_stock_issue.php?Process=2&amp;DepartmentStockIssueID='. $DepartmentStockIssueID .'">Edit</a>';
                                                }
                                                else
                                                {
                                                    echo 'Edit';
                                                }

                                                echo '&nbsp;|&nbsp;';

                                                if ($LoggedUser->HasPermissionForTask(TASK_DELETE_STOCK_ISSUE_TO_DEPARTMENT) === true)
                                                {
                                                    echo '<a class="delete-record" href="department_stock_issues_list.php?Process=5&amp;DepartmentStockIssueID=' . $DepartmentStockIssueID . '">Delete</a>'; 
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

<script src="/admin/vendor/jquery-ui/jquery-ui.min.js"></script>
<!-- DataTables JavaScript -->
<script src="/admin/vendor/datatables/js/jquery.dataTables.min.js"></script>
<script src="/admin/vendor/datatables-plugins/dataTables.bootstrap.min.js"></script>
<script src="/admin/vendor/datatables-responsive/dataTables.responsive.js"></script>	
<script type="text/javascript">
$(document).ready(function() {
    $("body").on('click', '.delete-record', function()
    {   
        if (!confirm("Are you sure you want to delete this department stock issue?"))
        {
            return false;
        }
    });

    $(".dtepicker").datepicker({
            changeMonth: true,
            changeYear: true,
            dateFormat: 'dd/mm/yy'
    });

    $('#DataTableRecords').DataTable({
        responsive: true,
        bPaginate: false,
        bSort: false,
        searching: false, 
        info: false
    });

    $('#Department').change(function() 
    {
        var DepartmentID = parseInt($(this).val());

        if (DepartmentID <= 0)
        {
            alert("Please Select Product Category");
            return false;
        }

        $.get("/xhttp_calls/get_products_by_department.php", {SelectedDepartmentID: DepartmentID}, function(data)
        {   
            ResultArray = data.split("|*****|");
    
            if (ResultArray[0] == 'error')
            {
                alert (ResultArray[1]);
                $('#Product').html('<option value="0">Select</option>');
                return false;
            }
            else
            {   
                $('#Product').html('<option value="0">Select-</option>' +ResultArray[1]);
            }           
        });
    });
});
</script>
<!-- JavaScript To Print A Report -->
<script src="/admin/js/print-report.js"></script>
</body>
</html>
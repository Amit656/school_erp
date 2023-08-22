<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once("../../classes/library_management/class.books_issue_conditions.php");

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
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}
catch (Exception $e)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}
// END OF 1. //

if ($LoggedUser->HasPermissionForTask(TASK_LIST_BOOK_ISSUE_CONDITION) !== true)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}

$HasErrors = false;
$RecordDeletedSuccessfully = false;

$Clean = array();
$Clean['Process'] = 0;

$Clean['BooksIssueConditionID'] = 0;

if (isset($_GET['Process']))
{
	$Clean['Process'] = (int) $_GET['Process'];
}
switch ($Clean['Process'])
{
	case 5:
		if ($LoggedUser->HasPermissionForTask(TASK_DELETE_BOOK_ISSUE_CONDITION) !== true)
		{
			header('location:/admin/unauthorized_login_admin.php');
			exit;
		}
		
		if (isset($_GET['BooksIssueConditionID']))
		{
			$Clean['BooksIssueConditionID'] = (int) $_GET['BooksIssueConditionID'];			
		}
		
		if ($Clean['BooksIssueConditionID'] <= 0)
		{
			header('location:/admin/error_page.php');
			exit;
		}						
			
		try
		{
			$BooksIssueConditionToDelete = new BooksIssueCondition($Clean['BooksIssueConditionID']);
		}
		catch (ApplicationDBException $e)
		{
			header('location:/admin/error_page.php');
			exit;
		}
		catch (Exception $e)
		{
			header('location:/admin/error_page.php');
			exit;
		}
		
		$RecordValidator = new Validator();
		
		if ($BooksIssueConditionToDelete->CheckDependencies())
		{
			$RecordValidator->AttachTextError('This condition cannot be deleted. There are dependent records for this condition.');
			$HasErrors = true;
			break;
		}
				
		if (!$BooksIssueConditionToDelete->Remove())
		{
			$RecordValidator->AttachTextError(ProcessErrors($BooksIssueConditionToDelete->GetLastErrorCode()));
			$HasErrors = true;
			break;
		}
		
		$RecordDeletedSuccessfully = true;
	break;
}

$AllBooksIssueConditions = array();
$AllBooksIssueConditions = BooksIssueCondition::GetAllBooksIssueConditions();

$LandingPageMode = '';
if (isset($_GET['Mode']))
{
    $LandingPageMode = $_GET['Mode'];
}

require_once('../html_header.php');
?>
<title>Books Conditions List</title>
<!-- DataTables CSS -->
<link href="../vendor/datatables-plugins/dataTables.bootstrap.css" rel="stylesheet">

<!-- DataTables Responsive CSS -->
<link href="../vendor/datatables-responsive/dataTables.responsive.css" rel="stylesheet">
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
                    <h1 class="page-header">Books Conditions List</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
            <!-- /.row -->
            <div class="row">
                <div class="col-lg-12">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <strong>Total Records Returned: <?php echo count($AllBooksIssueConditions); ?></strong>
                        </div>
                        <!-- /.panel-heading -->
                        <div class="panel-body">
                            <div>
                                <div class="row">
                                    <div class="col-lg-6">
                                    <div class="add-new-btn-container"><a href="add_books_issue_conditions.php" class="btn btn-primary<?php echo $LoggedUser->HasPermissionForTask(TASK_ADD_BOOK_ISSUE_CONDITION) === true ? '' : ' disabled'; ?>" role="button">Add New Books Conditions</a></div>
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
                            else if ($RecordDeletedSuccessfully == true)
                            {
                                echo '<div class="alert alert-danger alert-top-margin">Record deleted successfully.</div>';
                            }
                            else if ($LandingPageMode == 'UD')
                            {
                                echo '<div class="alert alert-success alert-top-margin">Record updated successfully.</div>';
                            }
?>
                                <div class="row" id="RecordTableHeading">
                                    <div class="col-lg-12">
                                    	<div class="report-heading-container"><strong>Books Conditions Details on <?php echo date('d-m-Y h:i A'); ?></strong></div>
                                    </div>
								</div>
                                <div class="row" id="RecordTable">
                                    <div class="col-lg-12">
                                        <table width="100%" class="table table-striped table-bordered table-hover" id="DataTableRecords">
                                            <thead>
                                                <tr>
                                                    <th>S. No</th>
                                                    <th>Condition For</th>
                                                    <th>Quota</th>
                                                    <th>Default Duration</th>
                                                    <th>Fine Detail</th>
                                                    <th>Create User</th>
                                                    <th>Create Date</th>
                                                    <th class="print-hidden">Operations</th>
                                                </tr>
                                            </thead>
                                            <tbody>
<?php
                                    if (is_array($AllBooksIssueConditions) && count($AllBooksIssueConditions) > 0)
                                    {
                                        $Counter = 0;
                                        foreach ($AllBooksIssueConditions as $BooksIssueConditionID => $BooksIssueConditionDetails)
                                        {
?>
                                                <tr>
                                                    <td><?php echo ++$Counter; ?></td>
                                                    <td><?php echo $BooksIssueConditionDetails['ConditionFor']; ?></td>
                                                    <td><?php echo $BooksIssueConditionDetails['Quota']; ?></td>
                                                    <td><?php echo $BooksIssueConditionDetails['DefaultDuration'] . ' days' ; ?></td>
                                                    <td><?php echo $BooksIssueConditionDetails['FineAmount'] . ' Rs '. $BooksIssueConditionDetails['LateFineType'] ; ?></td>
                                                    <td><?php echo $BooksIssueConditionDetails['CreateUserName']; ?></td>
                                                    <td><?php echo date('d/m/Y', strtotime($BooksIssueConditionDetails['CreateDate'])); ?></td>
                                                    <td class="print-hidden">
<?php
                                                    if ($LoggedUser->HasPermissionForTask(TASK_EDIT_BOOK_ISSUE_CONDITION) === true)
                                                    {
                                                        echo '<a href="edit_books_issue_conditions.php?Process=2&amp;BooksIssueConditionID=' . $BooksIssueConditionID . '">Edit</a>';
                                                    }
                                                    else
                                                    {
                                                        echo 'Edit';
                                                    }

                                                    echo '&nbsp;|&nbsp;';

                                                    if ($LoggedUser->HasPermissionForTask(TASK_DELETE_BOOK_ISSUE_CONDITION) === true)
                                                    {
                                                        echo '<a href="books_issue_conditions_list.php?Process=5&amp;BooksIssueConditionID=' . $BooksIssueConditionID . '" class="delete-record">Delete</a>';
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
        </div>
        <!-- /#page-wrapper -->

    </div>
    <!-- /#wrapper -->
<?php
require_once('../footer.php');
?>
<!-- DataTables JavaScript -->
<script src="../vendor/datatables/js/jquery.dataTables.min.js"></script>
<script src="../vendor/datatables-plugins/dataTables.bootstrap.min.js"></script>
<script src="../vendor/datatables-responsive/dataTables.responsive.js"></script>   
<script type="text/javascript">
$(document).ready(function() {
    $("body").on('click', '.delete-record', function()
    {   
        if (!confirm("Are you sure you want to delete this condition?"))
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
<script src="js/print-report.js"></script>
<script src="/admin/js/print-report.js"></script>
</body>
</html>
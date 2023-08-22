<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once("../../classes/hostel_management/class.mess.php");

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

$Clean['MessID'] = 0;

if (isset($_GET['Process']))
{
    $Clean['Process'] = (int) $_GET['Process'];
}
switch ($Clean['Process'])
{
    case 5:
        /*if ($LoggedUser->HasPermissionForTask(TASK_ADD_EDIT_TASK) !== true)
        {
            header('location:/admin/unauthorized_login_admin.php');
            exit;
        }*/
        
        if (isset($_GET['MessID']))
        {
            $Clean['MessID'] = (int) $_GET['MessID'];           
        }
        
        if ($Clean['MessID'] <= 0)
        {
            header('location:../error_page.php');
            exit;
        }                       
            
        try
        {
            $MessToDelete = new Mess($Clean['MessID']);
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
        
        if ($MessToDelete->CheckDependencies())
        {
            $RecordValidator->AttachTextError('This mess cannot be deleted. There are dependent records for this mess.');
            $HasErrors = true;
            break;
        }
                
        if (!$MessToDelete->Remove())
        {
            $RecordValidator->AttachTextError(ProcessErrors($MessToDelete->GetLastErrorCode()));
            $HasErrors = true;
            break;
        }
        
        $RecordDeletedSuccessfully = true;
    break;
}

$AllMess = array();
$AllMess = Mess::GetAllMess();

$LandingPageMode = '';
if (isset($_GET['Mode']))
{
    $LandingPageMode = $_GET['Mode'];
}

require_once('../html_header.php');
?>
<title>Mess List</title>
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
                    <h1 class="page-header">Mess List</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
            <!-- /.row -->
            <div class="row">
                <div class="col-lg-12">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <strong>Total Records Returned: <?php echo count($AllMess); ?></strong>
                        </div>
                        <!-- /.panel-heading -->
                        <div class="panel-body">
                            <div>
                                <div class="row">
                                    <div class="col-lg-6">
                                    <div class="add-new-btn-container"><a href="add_mess.php" class="btn btn-primary<?php //echo $LoggedUser->HasPermissionForTask(TASK_ADD_MENU) === true ? '' : ' disabled'; ?>" role="button">Add New Mess</a></div>
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
                                        <div class="report-heading-container"><strong>Mess Details on <?php echo date('d-m-Y h:i A'); ?></strong></div>
                                    </div>
                                </div>
                                <div class="row" id="RecordTable">
                                    <div class="col-lg-12">
                                        <table width="100%" class="table table-striped table-bordered table-hover" id="DataTableRecords">
                                            <thead>
                                                <tr>
                                                    <th>S. No</th>
                                                    <th>Mess Name</th>
                                                    <th>Mess Type</th>
                                                    <th>Monthely Fee</th>
                                                    <th>Quaterly Fee</th>
                                                    <th>Semi-Annual Fee</th>
                                                    <th>Annual Fee</th>
                                                    <th>Is Active</th>
                                                    <th>Create User</th>
                                                    <th>Create Date</th>
                                                    <th class="print-hidden">Operations</th>
                                                </tr>
                                            </thead>
                                            <tbody>
<?php
                                    if (is_array($AllMess) && count($AllMess) > 0)
                                    {
                                        $Counter = 0;
                                        foreach ($AllMess as $MessID => $MessDetails)
                                        {
?>
                                                <tr>
                                                    <td><?php echo ++$Counter; ?></td>
                                                    <td><?php echo $MessDetails['MessName']; ?></td>
                                                    <td><?php echo $MessDetails['MessType']; ?></td>
                                                    <td><?php echo $MessDetails['MonthlyFee']; ?></td>
                                                    <td><?php echo $MessDetails['QuarterlyFee']; ?></td>
                                                    <td><?php echo $MessDetails['SemiAnnualFee']; ?></td>
                                                    <td><?php echo $MessDetails['AnnualFee']; ?></td>
                                                    <td><?php echo (($MessDetails['IsActive']) ? 'Yes' : 'No'); ?></td>
                                                    <td><?php echo $MessDetails['CreateUserName']; ?></td>
                                                    <td><?php echo date('d/m/Y', strtotime($MessDetails['CreateDate'])); ?></td>
                                                    <td class="print-hidden">
<?php
                                                    echo '<a href="edit_mess.php?Process=2&amp;MessID=' . $MessID . '">Edit</a>';
                                                    echo '&nbsp;|&nbsp;';
                                                    echo '<a href="mess_list.php?Process=5&amp;MessID=' . $MessID . '" class="delete-record">Delete</a>'; 
                                                    /*if ($LoggedUser->HasPermissionForTask(TASK_EDIT_MENU) === true)
                                                    {
                                                        echo '<a href="edit_mess.php?Process=2&amp;MessID=' . $MessID . '">Edit</a>';
                                                    }
                                                    else
                                                    {
                                                        echo 'Edit';
                                                    }

                                                    echo '&nbsp;|&nbsp;';

                                                    if ($LoggedUser->HasPermissionForTask(TASK_DELETE_MENU) === true)
                                                    {
                                                        echo '<a href="mess_list.php?Process=5&amp;MessID=' . $MessID . '" class="delete-record">Delete</a>' 
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
<!-- DataTables JavaScript -->
<script src="../vendor/datatables/js/jquery.dataTables.min.js"></script>
<script src="../vendor/datatables-plugins/dataTables.bootstrap.min.js"></script>
<script src="../vendor/datatables-responsive/dataTables.responsive.js"></script>   
<script type="text/javascript">
$(document).ready(function() {
    $("body").on('click', '.delete-record', function()
    {   
        if (!confirm("Are you sure you want to delete this mess?"))
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
<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once("../../classes/class.ui_helpers.php");

require_once("../../classes/library_management/class.book_categories.php");

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

if ($LoggedUser->HasPermissionForTask(TASK_LIST_BOOK_CATEGORY) !== true)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}

$Filters = array();

$Categorylist = array(0 => 'All Category', 1 => 'Parent Category', 2 => 'Sub Category');

$ParentCategorylist = array();
$ParentCategorylist = BookCategory::GetActiveParentCategories();

$BookCategoryList = array();

$RecordDeletedSuccessfully = false;
$HasErrors = false;
$TotalRecords = 0;

$Clean = array();
$Clean['Process'] = 0;

$Clean['CategoryID'] = 0;

$Clean['CategoryType'] = 0;
$Clean['ParentCategoryID'] = 0;
$Clean['ActiveStatus'] = 0;

// paging and sorting variables start here  //
$Clean['CurrentPage'] = 1;
$TotalPages = 0;

$Start = 0;
$Limit = 3;
// end of paging variables//

if (isset($_GET['hdnProcess']))
{
    $Clean['Process'] = (int) $_GET['hdnProcess'];
}
elseif (isset($_GET['Process']))
{
    $Clean['Process'] = (int) $_GET['Process'];
}
switch ($Clean['Process'])
{
    case 5:
        if ($LoggedUser->HasPermissionForTask(TASK_DELETE_BOOK_CATEGORY) !== true)
        {
            header('location:/admin/unauthorized_login_admin.php');
            exit;
        }
        
        if (isset($_GET['BookCategoryID']))
        {
            $Clean['BookCategoryID'] = (int) $_GET['BookCategoryID'];           
        }
        
        if ($Clean['BookCategoryID'] <= 0)
        {
            header('location:/admin/error.php');
            exit;
        }                       
            
        try
        {
            $BookCategoryToDelete = new BookCategory($Clean['BookCategoryID']);
        }
        catch (ApplicationDBException $e)
        {
            header('location:/admin/error.php');
            exit;
        }
        catch (Exception $e)
        {
            header('location:/admin/error.php');
            exit;
        }
        
        $SearchValidator = new Validator();
        
        if ($BookCategoryToDelete->CheckDependencies())
        {
            $SearchValidator->AttachTextError('This book category cannot be deleted. There are dependent records for this book category.');
            $HasErrors = true;
            break;
        }
                
        if (!$BookCategoryToDelete->Remove())
        {
            $SearchValidator->AttachTextError(ProcessErrors($BookCategoryToDelete->GetLastErrorCode()));
            $HasErrors = true;
            break;
        }
        
        $RecordDeletedSuccessfully = true;
    break;

    case 7:        
        if (isset($_GET['drdCategory']))
        {
            $Clean['CategoryID'] = (int) $_GET['drdCategory'];
        }
        elseif (isset($_GET['CategoryID']))
        {
            $Clean['CategoryID'] = (int) $_GET['CategoryID'];
        }

        if (isset($_GET['drdParentCategory']))
        {
            $Clean['ParentCategoryID'] = (int) $_GET['drdParentCategory'];
        }
        elseif (isset($_GET['ParentCategoryID']))
        {
            $Clean['ParentCategoryID'] = (int) $_GET['ParentCategoryID'];
        }

        if (isset($_GET['optActiveStatus']))
        {
            $Clean['ActiveStatus'] = (int) $_GET['optActiveStatus'];
        }
        elseif (isset($_GET['ActiveStatus']))
        {
            $Clean['ActiveStatus'] = (int) $_GET['ActiveStatus'];
        }

        $SearchValidator = new Validator();

        if ($Clean['CategoryID'] != 0)
        {
            $SearchValidator->ValidateInSelect($Clean['CategoryID'], $Categorylist, 'Unknown Error, Please try again.');
        }

        if ($Clean['ParentCategoryID'] != 0)
        {
            $SearchValidator->ValidateInSelect($Clean['ParentCategoryID'], $ParentCategorylist, 'Unknown Error, Please try again.');
        }

        if ($Clean['ActiveStatus'] < 0 || $Clean['ActiveStatus'] > 2)
        {
            $SearchValidator->AttachTextError('Unknown Error, Please try again.');
        }
        
        if ($SearchValidator->HasNotifications())
        {
            $HasErrors = true;
            break;
        }

        //set record filters
        $Filters['CategoryID'] = $Clean['CategoryID'];
        $Filters['ParentCategoryID'] = $Clean['ParentCategoryID'];
        $Filters['ActiveStatus'] = $Clean['ActiveStatus'];

        //get records count
        BookCategory::SearchBookCategories($TotalRecords, true, $Filters);

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
            $BookCategoryList = BookCategory::SearchBookCategories($TotalRecords, false, $Filters, $Start, $Limit);
        }
        break;
}

$LandingPageMode = '';
if (isset($_GET['Mode']))
{
    $LandingPageMode = $_GET['Mode'];
}

require_once('../html_header.php');
?>
<title>Book Category Report</title>
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
                    <h1 class="page-header">Book Category Report</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
            <form class="form-horizontal" name="frmBookCategoryReport" action="book_category_report.php" method="get">
                <div class="panel panel-default" id="accordion">
                    <div class="panel-heading">
                        <strong><a data-toggle="collapse" data-parent="#accordion" href="#collapseOne">Filters</a></strong>
                    </div>
                    <div id="collapseOne" class="panel-collapse collapse in">
                        <div class="panel-body">
<?php
                            if ($HasErrors == true)
                            {
                                echo $SearchValidator->DisplayErrors();
                            }
                            else if ($LandingPageMode == 'AS')
                            {
                                echo '<div class="alert alert-success">Record saved successfully.</div>';
                            }
                            else if ($RecordDeletedSuccessfully == true)
                            {
                                echo '<div class="alert alert-danger">Record deleted successfully.</div>';
                            }
                            else if ($LandingPageMode == 'UD')
                            {
                                echo '<div class="alert alert-success">Record updated successfully.</div>';
                            }
?>
                            <div class="form-group">
                                <label for="Category" class="col-lg-2 control-label">Category</label>
                                <div class="col-lg-4">
                                    <select class="form-control" name="drdCategory" id="Category">
<?php
                            if (is_array($Categorylist) && count($Categorylist) > 0)
                            {
                                foreach($Categorylist as $CategoryID => $CategoryName)
                                {
                                    echo '<option ' . (($Clean['CategoryID'] == $CategoryID) ? 'selected="selected"' : '' ) . ' value="' . $CategoryID . '">' . $CategoryName . '</option>';
                                }
                            }
?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">                            
                                <label for="ParentCategory" class="col-lg-2 control-label">Parent Category</label>
                                <div class="col-lg-4">
                                    <select class="form-control" name="drdParentCategory" id="ParentCategory">
                                        <option<?php echo (($Clean['ParentCategoryID'] == 0) ? ' selected="selected"' : ''); ?> value="0">All Parent Category</option>
<?php
                            if (is_array($ParentCategorylist) && count($ParentCategorylist) > 0)
                            {
                                foreach($ParentCategorylist as $ParentCategoryID => $ParentCategory)
                                {
                                    echo '<option ' . (($Clean['ParentCategoryID'] == $ParentCategoryID) ? 'selected="selected"' : '' ) . ' value="' . $ParentCategoryID . '">' . $ParentCategory . '</option>';
                                }
                            }
?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="ActiveStatus" class="col-lg-2 control-label">Status</label>
                                <div class="col-lg-6">
                                    <label class="radio-inline">
                                        <input type="radio" <?php echo (($Clean['ActiveStatus'] == 0) ? 'checked="checked"' : ''); ?> name="optActiveStatus" id="ActiveStatus" value="0" checked>All Category
                                    </label>
                                    <label class="radio-inline">
                                        <input type="radio" <?php echo (($Clean['ActiveStatus'] == 1) ? 'checked="checked"' : ''); ?> name="optActiveStatus" id="ActiveStatus" value="1">Active Category
                                    </label>
                                    <label class="radio-inline">
                                        <input type="radio" <?php echo (($Clean['ActiveStatus'] == 2) ? 'checked="checked"' : ''); ?> name="optActiveStatus" id="ActiveStatus" value="2">In-Active Category
                                    </label>
                                </div>
                            </div>                    
                            <div class="form-group">
                                <div class="col-lg-offset-2 col-lg-10">
                                    <input type="hidden" name="hdnProcess" value="7" />
                                    <button type="submit" class="btn btn-primary">Search</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    </div>
            </form>
            <!-- /.row -->
<?php
        if ($Clean['Process'] == 7 && $HasErrors == false)
        {
            $ReportHeaderText = '';

            if ($Clean['CategoryID'] != 0)
            {
                $ReportHeaderText .= ' Category: ' . $Categorylist[$Clean['CategoryID']] . ',';
            }

            if ($Clean['ParentCategoryID'] != 0)
            {
                $ReportHeaderText .= ' Parent Category: ' . $ParentCategorylist[$Clean['ParentCategoryID']] . ',';
            }

            if ($Clean['ActiveStatus'] == 1)
            {
                $ReportHeaderText .= ' Status: Active,';
            }
            else if ($Clean['ActiveStatus'] == 2)
            {
                $ReportHeaderText .= ' Status: In-Active,';
            }

            if ($ReportHeaderText != '')
            {
                $ReportHeaderText = ' for' . rtrim($ReportHeaderText, ',');
            }
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
<?php
                                    if ($TotalPages > 1)
                                    {
                                        $AllParameters = array('Process' => '7', 'CategoryID' => $Clean['CategoryID'], 'ParentCategoryID' => $Clean['ParentCategoryID'], 'ActiveStatus' => $Clean['ActiveStatus']);
                                        echo UIHelpers::GetPager('book_category_report.php', $TotalPages, $Clean['CurrentPage'], $AllParameters);
                                    }
?>                                        
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="print-btn-container"><button id="PrintButton" type="submit" class="btn btn-primary">Print</button></div>
                                    </div>
                                </div>
                                <div class="row" id="RecordTableHeading">
                                    <div class="col-lg-12">
                                        <div class="report-heading-container"><strong>Book Category Report on <?php echo date('d-m-Y h:i A') . $ReportHeaderText; ?></strong></div>
                                    </div>
                                </div>
                                <div class="row" id="RecordTable">
                                    <div class="col-lg-12">
                                        <table width="100%" class="table table-striped table-bordered table-hover" id="DataTableRecords">
                                            <thead>
                                                <tr>
                                                    <th>S.No</th>
                                                    <th>Category Name</th>
                                                    <th>Category Type</th>
                                                    <th>Is Active</th>
                                                    <th>Create User</th>
                                                    <th>Create Date</th>
                                                    <th class="print-hidden">Operations</th>
                                                </tr>
                                            </thead>
                                            <tbody>
<?php
                                    if (is_array($BookCategoryList) && count($BookCategoryList) > 0)
                                    {
                                        $Counter = $Start;
                                        foreach ($BookCategoryList as $BookCategoryID => $BookCategoryDetails)
                                        {
?>
                                                <tr>
                                                    <td><?php echo ++$Counter; ?></td>
                                                    <td><?php echo $BookCategoryDetails['BookCategoryName']; ?></td>
                                                    <td><?php echo (($BookCategoryDetails['ParentCategoryID']) ? 'Sub Category' : 'Parent Category'); ?></td>
                                                    <td><?php echo (($BookCategoryDetails['IsActive']) ? 'Yes' : 'No'); ?></td>
                                                    <td><?php echo $BookCategoryDetails['CreateUserName']; ?></td>
                                                    <td><?php echo date('d/m/Y', strtotime($BookCategoryDetails['CreateDate'])); ?></td>
                                                    <td class="print-hidden">
<?php
                                                    if ($LoggedUser->HasPermissionForTask(TASK_EDIT_BOOK_CATEGORY) === true)
                                                    {
                                                        echo '<a href="edit_book_category.php?Process=2&amp;BookCategoryID=' . $BookCategoryID . '">Edit</a>';
                                                    }
                                                    else
                                                    {
                                                        echo 'Edit';
                                                    }

                                                    echo '&nbsp;|&nbsp;';

                                                    if ($LoggedUser->HasPermissionForTask(TASK_DELETE_BOOK_CATEGORY) === true)
                                                    {
                                                        echo '<a href="book_category_report.php?Process=5&amp;BookCategoryID=' . $BookCategoryID . '" class="delete-record">Delete</a>';
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
?>
<!-- DataTables JavaScript -->
<script src="../vendor/datatables/js/jquery.dataTables.min.js"></script>
<script src="../vendor/datatables-plugins/dataTables.bootstrap.min.js"></script>
<script src="../vendor/datatables-responsive/dataTables.responsive.js"></script>   
<script type="text/javascript">
$(document).ready(function() {
    $("body").on('click', '.delete-record', function()
    {   
        if (!confirm("Are you sure you want to delete this book category?"))
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

    $('#Category').change(function(){
        if ($(this).val() == 2) 
        {
            $('#ParentCategory').prop('disabled', false);
        }
        else
        {
            $('#ParentCategory').val(0);
            $('#ParentCategory').prop('disabled', true);
        }
    }).trigger('change');
});
</script>
<!-- JavaScript To Print A Report -->
<script src="js/print-report.js"></script>
<script src="/admin/js/print-report.js"></script>
</body>
</html>
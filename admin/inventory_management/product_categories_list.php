<?php
require_once("../../classes/class.users.php");
require_once('../../classes/class.validation.php');
require_once("../../classes/class.authentication.php");

require_once("../../classes/inventory_management/class.product_categories.php");

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

if ($LoggedUser->HasPermissionForTask(TASK_LIST_PRODUCT_CATEGORY) !== true)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}

$AllProductCategoryList = array();
$AllProductCategoryList = ProductCategory::GetActiveFirstLevelProductCategory();

$AllProductCategories = array();

$HasErrors = false;

$Clean = array();
$Clean['Process'] = 0;

$Clean['ParentCategoryID'] = 0;
$Clean['ProductCategoryID'] = 0;

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
	case 5: #Delete Case
		if ($LoggedUser->HasPermissionForTask(TASK_DELETE_PRODUCT_CATEGORY) !== true)
		{
			header('location:unauthorized_login_admin.php');
			exit;
		}
		
		if (isset($_GET['ProductCategoryID']))
		{
			$Clean['ProductCategoryID'] = (int) $_GET['ProductCategoryID'];			
		}
		
		if ($Clean['ProductCategoryID'] <= 0)
		{
			header('location:../error.php');
			exit;
		}						
			
		try
		{
			$ProductCategoryToDelete = new ProductCategory($Clean['ProductCategoryID']);
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
		
		// if ($ProductCategoryToDelete->CheckDependencies())
		// {
		// 	$RecordValidator->AttachTextError('This master product unit cannot be deleted. There are dependent records for this master product unit.');
		// 	$HasErrors = true;
		// 	break;
		// }
				
		if (!$ProductCategoryToDelete->Remove())
		{
			$RecordValidator->AttachTextError(ProcessErrors($ProductCategoryToDelete->GetLastErrorCode()));
			$HasErrors = true;
			break;
		}
		
		header('location:product_categories_list.php?Mode=DD');
	    break;

    case 7: #Search Case

        //var_dump($_POST);exit;
        if (isset($_POST['drdParentCategoryID']))
        {
            $Clean['ParentCategoryID'] = (int) $_POST['drdParentCategoryID'];
        }
        else if (isset($_GET['ParentCategory']))
        {
            $Clean['ParentCategoryID'] = (int) $_GET['ParentCategory'];
        }

        $RecordValidator = new Validator();

        if ($Clean['ParentCategoryID'] != '')
        {
            $RecordValidator->ValidateInSelect($Clean['ParentCategoryID'], $AllProductCategoryList, 'Unknown error, please try again.'); 
        }

        if ($RecordValidator->HasNotifications())
        {
            $HasErrors = true;
            break;
        }

        $AllProductCategories = ProductCategory::GetAllProductCategories($Clean['ParentCategoryID']);
        break;
}

require_once('../html_header.php');
?>
<title>Product Category List</title>
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
                    <h1 class="page-header">Product Category List</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>

            <form class="form-horizontal" name="AddProductCategory" action="product_categories_list.php" method="post">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        Parent Product Category
                    </div>
<?php
                    if ($HasErrors == true)
                    {
                        echo $RecordValidator->DisplayErrors();
                    }
?>
                    <div class="panel-body">
                        <div class="form-group">
                            <label for="ProductCategory" class="col-lg-3 control-label">Parent Product Category</label>
                            <div class="col-lg-4">
                                <select class="form-control" name="drdParentCategoryID">
                                    <option value="0">-- Select --</option>
<?php
                                    if (is_array($AllProductCategoryList) && count($AllProductCategoryList) > 0)
                                    {
                                        foreach ($AllProductCategoryList as $ProductCategoryID => $ParentProductCategoryName)
                                        {
?>
                                            <option <?php echo ($Clean['ParentCategoryID'] == $ProductCategoryID) ? 'selected="selected"' : ''; ?> value="<?php echo $ProductCategoryID;?>"><?php echo $ParentProductCategoryName; ?></option>
<?php
                                        }
                                    }
?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="col-sm-offset-3 col-lg-10">
                                <input type="hidden" name="hdnProcess" value="7" />
                                <button type="submit" class="btn btn-primary">View</button>
                            </div>
                      </div>
                    </div>
                </div>
            </form>
            <!-- /.row -->
<?php
        if ($Clean['Process'] == 7) 
        {
?>
            <div class="row">
                <div class="col-lg-12">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <strong>Total Records Returned: <?php echo count($AllProductCategories); ?></strong>
                        </div>
                        <!-- /.panel-heading -->
                        <div class="panel-body">
                            <div>
                                <div class="row">
                                    <div class="col-lg-6">
                                    <div class="add-new-btn-container"><a href="add_product_category.php?ParentCategory=<?php echo $Clean['ParentCategoryID'];?>" class="btn btn-primary<?php echo $LoggedUser->HasPermissionForTask(TASK_ADD_PRODUCT_CATEGORY) === true ? '' : ' disabled'; ?>" role="button">Add New Product Category</a></div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="print-btn-container"><button id="PrintButton" type="submit" class="btn btn-primary">Print</button></div>
                                    </div>
                                </div>
                                <div class="row" id="RecordTableHeading">
                                    <div class="col-lg-12">
                                        <div class="report-heading-container"><strong>Product Categories on <?php echo date('d-m-Y h:i A'); ?></strong></div>
                                    </div>
                                </div>
                                <div class="row" id="RecordTable">
                                    <div class="col-lg-12">
                                        <table width="100%" class="table table-striped table-bordered table-hover" id="DataTableRecords">
                                            <thead>
                                                <tr>
                                                    <th>S. No</th>
                                                    <th>Product Parent Category Name</th>
                                                    <th>Product Category Name</th>
                                                    <th>Is Active</th>
                                                    <th>Create User</th>
                                                    <th>Create Date</th>
                                                    <th class="print-hidden">Operations</th>
                                                </tr>
                                            </thead>
                                            <tbody>
<?php
                                    if (is_array($AllProductCategories) && count($AllProductCategories) > 0)
                                    {
                                        $Counter = 0;
                                        foreach ($AllProductCategories as $ProductCategoryID => $ProductCategoryDetails)
                                        {
?>
                                        <tr>
                                            <td><?php echo ++$Counter; ?></td>
                                            <td><?php echo ($ProductCategoryDetails['ParentCategoryID'] == 0 ? 'N/A' : $ProductCategoryDetails['ParentProductCategoryName']) ?></td>
                                            <td><?php echo $ProductCategoryDetails['ProductCategoryName']; ?></td>
                                            <td><?php echo ($ProductCategoryDetails['IsActive']) ? 'Yes' : 'No'?></td>
                                            <td><?php echo $ProductCategoryDetails['CreateUserName']; ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($ProductCategoryDetails['CreateDate'])); ?></td>
                                            <td class="print-hidden">
<?php
                                            if ($LoggedUser->HasPermissionForTask(TASK_EDIT_PRODUCT_CATEGORY) === true)
                                            {
                                                echo '<a href="edit_product_category.php?Process=2&amp;ProductCategoryID='. $ProductCategoryID .'">Edit</a>';
                                            }
                                            else
                                            {
                                                echo 'Edit';
                                            }

                                            echo '&nbsp;|&nbsp;';

                                            if ($LoggedUser->HasPermissionForTask(TASK_DELETE_PRODUCT_CATEGORY) === true)
                                            {
                                                echo '<a class="delete-record" href="product_categories_list.php?Process=5&amp;ProductCategoryID=' . $ProductCategoryID . '">Delete</a>'; 
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
<!-- DataTables JavaScript -->
<script src="/admin/vendor/datatables/js/jquery.dataTables.min.js"></script>
<script src="/admin/vendor/datatables-plugins/dataTables.bootstrap.min.js"></script>
<script src="/admin/vendor/datatables-responsive/dataTables.responsive.js"></script>	
<script type="text/javascript">
$(document).ready(function() {
    $("body").on('click', '.delete-record', function()
    {   
        if (!confirm("Are you sure you want to delete this master product category?"))
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
<?php
require_once('../../classes/class.users.php');
require_once('../../classes/class.validation.php');
require_once('../../classes/class.authentication.php');

require_once('../../classes/class.menus.php');
require_once('../../classes/class.submenus.php');

require_once('../../classes/class.ui_helpers.php');

require_once('../../includes/global_defaults.inc.php');

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

if ($LoggedUser->HasPermissionForTask(TASK_SUB_MENU_LIST) !== true)
{
	header('location:../unauthorized_login_admin.php');
	exit;
}

$Filters = array();

$AllMenus = array();
$AllMenus = Menu::GetActiveMenus();

$AllSubMenus = array();

$HasErrors = false;
$HasSearchErrors = false;

$Clean = array();
$Clean['Process'] = 0;

$Clean['SubMenuID'] = 0;

$Clean['MenuID'] = 0;
$Clean['MenuID'] = key($AllMenus);

if (isset($_GET['hdnProcess']))
{
	$Clean['Process'] = (int) $_GET['hdnProcess'];
}
else if (isset($_GET['Process']))
{
	$Clean['Process'] = (int) $_GET['Process'];
}
switch ($Clean['Process'])
{
    case 5:
		if ($LoggedUser->HasPermissionForTask(TASK_DELETE_SUB_MENU) !== true)
		{
			header('location:../unauthorized_login_admin.php');
			exit;
		}
		
		if (isset($_GET['SubMenuID']))
		{
			$Clean['SubMenuID'] = (int) $_GET['SubMenuID'];			
		}
		
		if ($Clean['SubMenuID'] <= 0)
		{
			header('location:../error_page.php');
			exit;
		}						
			
		try
		{
            $SubMenuToDelete = new SubMenu($Clean['SubMenuID']);
            $Clean['MenuID'] = $SubMenuToDelete->GetMenuID();
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
        
        $AllSubMenus = SubMenu::GetAllSubMenus($Clean['MenuID']);

		$RecordValidator = new Validator();
				
		if (!$SubMenuToDelete->Remove())
		{
			$RecordValidator->AttachTextError(ProcessErrors($SubMenuToDelete->GetLastErrorCode()));
			$HasErrors = true;
			break;
		}
		
		header('location:submenus_list.php?POMode=RD&Process=7&MenuID=' . $Clean['MenuID']);
		exit;
        break;
    
	case 7:
		if (isset($_GET['drdMenu']))
		{
			$Clean['MenuID'] = (int) $_GET['drdMenu'];
		}
		elseif (isset($_GET['MenuID']))
		{
			$Clean['MenuID'] = (int) $_GET['MenuID'];
		}

		$SearchValidator = new Validator();

		if ($Clean['MenuID'] != 0)
		{
			$SearchValidator->ValidateInSelect($Clean['MenuID'], $AllMenus, 'Unknown Error, Please try again.');
		}
		
		if ($SearchValidator->HasNotifications())
		{
			$HasSearchErrors = true;
			break;
		}

		$AllSubMenus = SubMenu::GetAllSubMenus($Clean['MenuID']);
        break;
        
    default:
		$AllSubMenus = SubMenu::GetAllSubMenus($Clean['MenuID']);
		break;
}

require_once('../html_header.php');
?>
<title>Sub Menu List</title>
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
                    <h1 class="page-header">Sub Menu List</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
            <form class="form-horizontal" name="frmUserReport" action="submenus_list.php" method="get">
                <div class="panel panel-default" id="accordion">
                    <div class="panel-heading">
						<strong>Filters</strong>
                    </div>
                    <div id="collapseOne" class="panel-collapse collapse in">
                        <div class="panel-body">
<?php
                            if ($HasSearchErrors == true)
                            {
                                echo $SearchValidator->DisplayErrors();
                            }
?>
                            <div class="form-group">
                                <label for="Menu" class="col-lg-2 control-label">Menu</label>
                                <div class="col-lg-4">
									<select class="form-control" name="drdMenu" id="Menu">
<?php
							if (is_array($AllMenus) && count($AllMenus) > 0)
							{
								foreach($AllMenus as $MenuID => $MenuName)
								{
									if ($Clean['MenuID'] != $MenuID)
									{
?>
										<option value="<?php echo $MenuID; ?>"><?php echo $MenuName; ?></option>
<?php
									}
									else
									{
?>
										<option selected="selected" value="<?php echo $MenuID; ?>"><?php echo $MenuName; ?></option>
<?php
									}
								}
							}
?>
									</select>
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
		if ($HasSearchErrors == false)
		{
?>
            <div class="row">
                <div class="col-lg-12">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <strong>Total Records Returned: <?php echo count($AllSubMenus); ?></strong>
                        </div>
                        <!-- /.panel-heading -->
                        <div class="panel-body">
                            <div>
                            <div class="row">
                                    <div class="col-lg-6">
                                        <div class="add-new-btn-container"><a href="add_submenu.php?MenuID=<?php echo $Clean['MenuID']; ?>" class="btn btn-primary<?php echo $LoggedUser->HasPermissionForTask(TASK_ADD_SUB_MENU) === true ? '' : ' disabled'; ?>" role="button">Add New Sub Menu</a></div>
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
?>                                
                                <div class="row" id="RecordTableHeading">
                                    <div class="col-lg-12">
                                    	<div class="report-heading-container"><strong>Sub Menus List on <?php echo date('d-m-Y h:i A'); ?></strong></div>
                                    </div>
								</div>
                                <div class="row" id="RecordTable">
                                    <div class="col-lg-12">
                                        <table width="100%" class="table table-striped table-bordered table-hover" id="DataTableRecords">
                                            <thead>
                                                <tr>
                                                    <th>S.No</th>
                                                    <th>Submenu Name</th>
                                                    <th>Task Name</th>
                                                    <th>Linked File Name</th>
                                                    <th>Priority</th>
                                                    <th>Is Active</th>
                                                    <th>Create User</th>
                                                    <th>Create Date</th>
                                                    <th class="print-hidden">Operations</th>
                                                </tr>
                                            </thead>
                                            <tbody>
<?php
									if (is_array($AllSubMenus) && count($AllSubMenus) > 0)
									{
										$Counter = 0;
										foreach ($AllSubMenus as $SubMenuID => $SubMenuDetails)
										{
?>
                                                <tr>
                                                    <td><?php echo ++$Counter; ?></td>
                                                    <td><?php echo $SubMenuDetails['SubmenuName']; ?></td>
                                                    <td><?php echo $SubMenuDetails['TaskName']; ?></td>
                                                    <td><?php echo $SubMenuDetails['LinkedFileName']; ?></td>
                                                    <td><?php echo $SubMenuDetails['SubmenuPriority']; ?></td>
                                                    <td><?php echo (($SubMenuDetails['IsActive']) ? 'Yes' : 'No'); ?></td>
                                                    <td><?php echo $SubMenuDetails['CreateUserName']; ?></td>
                                                    <td><?php echo date('d/m/Y', strtotime($SubMenuDetails['CreateDate'])); ?></td>
                                                    <td class="print-hidden">
<?php
                                                    if ($LoggedUser->HasPermissionForTask(TASK_EDIT_SUB_MENU) === true)
                                                    {
                                                        echo '<a href="edit_submenu.php?Process=2&amp;SubMenuID=' . $SubMenuID . '">Edit</a>';
                                                    }
                                                    else
                                                    {
                                                        echo 'Edit';
                                                    }

                                                    echo '&nbsp;|&nbsp;';

                                                    if ($LoggedUser->HasPermissionForTask(TASK_DELETE_SUB_MENU) === true)
                                                    {
                                                        echo '<a class="delete-record" href="submenus_list.php?Process=5&amp;SubMenuID=' . $SubMenuID . '">Delete</a>';
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
    <script src="/admin/vendor/datatables/js/jquery.dataTables.min.js"></script>
    <script src="/admin/vendor/datatables-plugins/dataTables.bootstrap.min.js"></script>
	<script src="/admin/vendor/datatables-responsive/dataTables.responsive.js"></script>
	
	<!-- JavaScript To Print A Report -->
    <script src="/admin/js/print-report.js"></script>

	<script type="text/javascript">
<?php
    if (isset($_GET['POMode']))
    {
        $PageOperationResultMessage = '';
        $PageOperationResultMessage = UIHelpers::GetPageOperationResultMessage($_GET['POMode']);

        if ($PageOperationResultMessage != '')
        {
            echo 'alert("' . $PageOperationResultMessage . '");';
        }
    }
?>
	$(document).ready(function() {
		
	});

    $(document).ready(function() {
        $("body").on('click', '.delete-record', function()
        {	
            if (!confirm("Are you sure you want to delete this sub menu?"))
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
</body>
</html>
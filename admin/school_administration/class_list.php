<?php
require_once("../../classes/class.users.php");
require_once('../../classes/class.validation.php');
require_once("../../classes/class.authentication.php");

require_once("../../classes/school_administration/class.classes.php");

require_once("../../includes/global_defaults.inc.php");

require_once("../../includes/helpers.inc.php");

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

if ($LoggedUser->HasPermissionForTask(TASK_LIST_CLASS) !== true)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}

$UserMenusArray = array();
$UserMenusArray = $LoggedUser->GetUserMenus();

if (!is_array($UserMenusArray) || count($UserMenusArray) <= 0)
{
	//header('location:../logout.php');
	//exit;
}

$AllClasses = array();
$AllClasses = AddedClass::GetAllClasses();

$HasErrors = false;

$Clean = array();
$Clean['Process'] = 0;

$Clean['ClassID'] = 0;

$Clean['ClassPriority'] = array();

if (isset($_POST['hdnProcess']))
{
    $Clean['Process'] = (int) $_POST['hdnProcess'];
}
elseif (isset($_GET['Process']))
{
	$Clean['Process'] = (int) $_GET['Process'];
}
switch ($Clean['Process'])
{
    case 3:
        if ($LoggedUser->HasPermissionForTask(TASK_ADD_EDIT_CLASS) !== true)
        {
            header('location:/admin/unauthorized_login_admin.php');
            exit;
        }

        if (isset($_POST['txtClassPriority']) && is_array($_POST['txtClassPriority']))
        {           
            $Clean['ClassPriority'] = $_POST['txtClassPriority'];
        }

        $RecordValidator = new Validator();

        $Counter = 1;
        foreach($Clean['ClassPriority'] as $ClassID => $Priority)
        {
            if (!array_key_exists($ClassID, $AllClasses))
            {
                header('location:../error.php');
                exit;
            }

            $RecordValidator->ValidateInteger($Priority, 'Invalid Priority value in row: ' . $Counter . '.', 1);
            $Counter++;
        }

        if ($RecordValidator->HasNotifications())
        {
            $HasErrors = true;
            break;
        }

        if (!AddedClass::SetClassesPriority($Clean['ClassPriority']))
        {
            $RecordValidator->AttachTextError('There was an error in updating record.');
            $HasErrors = true;
            break;
        }
                
        header('location:class_list.php?Mode=UD');
        exit;
    break;

	case 5:
		if ($LoggedUser->HasPermissionForTask(TASK_DELETE_CLASS) !== true)
		{
			header('location:/admin/unauthorized_login_admin.php');
			exit;
		}
		
		if (isset($_GET['ClassID']))
		{
			$Clean['ClassID'] = (int) $_GET['ClassID'];
		}
		
		if ($Clean['ClassID'] <= 0)
		{
			header('location:../error_page.php');
			exit;
		}						
			
		try
		{
			$ClassToDelete = new AddedClass($Clean['ClassID']);
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
		
		if ($ClassToDelete->CheckDependencies())
		{
			$RecordValidator->AttachTextError('This Class cannot be deleted. There are dependent records for this Class.');
			$HasErrors = true;
			break;
		}
				
		if (!$ClassToDelete->Remove())
		{
			$RecordValidator->AttachTextError(ProcessErrors($ClassToDelete->GetLastErrorCode()));
			$HasErrors = true;
			break;
		}
		
		header('location:class_list.php?Mode=DD');
        exit;
	break;
}
require_once('../html_header.php');
?>
<title>Class List</title>
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
                    <h1 class="page-header">Class List</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
            <!-- /.row -->
            <div class="row">
                <div class="col-lg-12">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <strong>Total Records Returned: <?php echo count($AllClasses); ?></strong>
                        </div>
                        <!-- /.panel-heading -->
                        <div class="panel-body">
                            <div>
                                <div class="row">
                                    <div class="col-lg-6">
                                    <div class="add-new-btn-container"><a href="add_class.php" class="btn btn-primary<?php echo $LoggedUser->HasPermissionForTask(TASK_ADD_EDIT_CLASS) === true ? '' : ' disabled'; ?>" role="button">Add New Class</a></div>
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
                                    	<div class="report-heading-container"><strong>Class List on <?php echo date('d-m-Y h:i A'); ?></strong></div>
                                    </div>
								</div>
                               <form action="class_list.php" method="post">
                                    <div class="row" id="RecordTable">
                                        <div class="col-lg-12">
                                            <table width="100%" class="table table-striped table-bordered table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>S. No</th>
                                                        <th>Class Name</th>
                                                        <th>Class Symbol</th>
                                                        <th>Class Priority</th>
                                                        <th>Create User</th>
                                                        <th>Create Date</th>
                                                        <th>Class Section</th>
                                                        <th class="print-hidden">Operations</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
<?php
                                        if (is_array($AllClasses) && count($AllClasses) > 0)
                                        {
                                            $Counter = 0;
                                            foreach ($AllClasses as $ClassID => $ClassDetails)
                                            {
?>
                                            <tr>
                                                <td><?php echo ++$Counter; ?></td>
                                                <td><?php echo $ClassDetails['ClassName']; ?></td>
                                                <td><?php echo $ClassDetails['ClassSymbol']; ?></td>
                                                <td><input type="text" class="form-control" maxlength="3" name="txtClassPriority[<?php echo $ClassID; ?>]" style="width: 50px;" value="<?php echo $ClassDetails['Priority']; ?>"></td>
                                                <td><?php echo $ClassDetails['CreateUserName']; ?></td>
                                                <td><?php echo date('d/m/y',strtotime($ClassDetails['CreateDate'])); ?></td>
                                                <td class="print-hidden"><?php echo'<a href="add_class_section.php?Process=7&amp;ClassID='.$ClassID.'">View</a>'; ?></td>
                                                <td class="print-hidden">
<?php
                                                if ($LoggedUser->HasPermissionForTask(TASK_ADD_EDIT_CLASS) === true)
                                                {
                                                    echo '<a href="edit_class.php?Process=2&amp;ClassID='.$ClassID.'">Edit</a>';
                                                }
                                                else
                                                {
                                                    echo 'Edit';
                                                }

                                                echo '&nbsp;|&nbsp;';

                                                if ($LoggedUser->HasPermissionForTask(TASK_DELETE_CLASS) === true)
                                                {
                                                    echo '<a class="delete-record" href="class_list.php?Process=5&amp;ClassID='.$ClassID.'">Delete</a>'; 
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
                                            <tr>
                                                <td colspan="8" style="text-align:right;">
                                                    <input type="hidden" value="3" name="hdnProcess"/>
                                                    <button type="submit" class="btn btn-success">Update</button>
                                                </td>
                                            </tr>
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

	<script type="text/javascript">
	$(document).ready(function() 
    {
		$(".delete-record").click(function()
        {	
            if (!confirm("Are you sure you want to delete this Class ?"))
            {
                return false;
            }
        });
	});
    </script>
	<!-- JavaScript To Print A Report -->
    <script src="js/print-report.js"></script>
    <script src="/admin/js/print-report.js"></script>
</body>
</html>
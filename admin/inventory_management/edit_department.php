<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once("../../classes/inventory_management/class.departments.php");

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

if ($LoggedUser->HasPermissionForTask(TASK_EDIT_DEPARTMENT) !== true)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}

$Clean = array();

$Clean['DepartmentID'] = 0;

if (isset($_GET['DepartmentID']))
{
    $Clean['DepartmentID'] = (int) $_GET['DepartmentID'];
}
elseif (isset($_POST['hdnDepartmentID']))
{
    $Clean['DepartmentID'] = (int) $_POST['hdnDepartmentID'];
}

if ($Clean['DepartmentID'] <= 0)
{
    header('location:../error.php');
    exit;
}   

try
{
    $DepartmentToEdit = new Department($Clean['DepartmentID']);
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

$HasErrors = false;

$Clean['Process'] = 0;

$Clean['DepartmentName'] = '';
$Clean['IsActive'] = 0;

if (isset($_POST['hdnProcess']))
{
	$Clean['Process'] = (int) $_POST['hdnProcess'];
}
else if (isset($_GET['Process']))
{
	$Clean['Process'] = (int) $_GET['Process'];
}
switch ($Clean['Process'])
{
	case 3:						
		if (isset($_POST['txtDepartmentName']))
		{
			$Clean['DepartmentName'] = strip_tags(trim($_POST['txtDepartmentName']));
		}

		if (isset($_POST['chkIsActive']))
        {
            $Clean['IsActive'] = 1;
        }
		
		$NewRecordValidator = new Validator();
		$NewRecordValidator->ValidateStrings($Clean['DepartmentName'], 'Department name is required and should be between 3 and 100 characters.', 3, 100);
		
		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}
				
		$DepartmentToEdit->SetDepartmentName($Clean['DepartmentName']);
		$DepartmentToEdit->SetIsActive($Clean['IsActive']);
		
		$DepartmentToEdit->SetCreateUserID($LoggedUser->GetUserID());

		if ($DepartmentToEdit->RecordExists())
        {
            $NewRecordValidator->AttachTextError('Department name you have added already exists.');
            $HasErrors = true;
            break;
        }

		if (!$DepartmentToEdit->Save())
		{
			$NewRecordValidator->AttachTextError(ProcessErrors($DepartmentToEdit->GetLastErrorCode()));
			$HasErrors = true;

			break;
		}
		
		header('location:departments_list.php?Mode=UD');
		exit;
		break;

	case 2:
		$Clean['DepartmentName'] = $DepartmentToEdit->GetDepartmentName();
		$Clean['IsActive'] = $DepartmentToEdit->GetIsActive();
		break;
}

require_once('../html_header.php');
?>
<title>Edit Department</title>
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
                    <h1 class="page-header">Edit Department</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="EditDepartment" action="edit_department.php" method="post">
            	<div class="panel panel-default">
                    <div class="panel-heading">
                        Enter Department Details
                    </div>
                    <div class="panel-body">
<?php
						if ($HasErrors == true)
						{
							echo $NewRecordValidator->DisplayErrors();
						}
?>                    
                    	<div class="form-group">
                            <label for="DepartmentName" class="col-lg-2 control-label">Department Name</label>
                            <div class="col-lg-4">
                            	<input class="form-control" type="text" maxlength="25" id="DepartmentName" name="txtDepartmentName" value="<?php echo $Clean['DepartmentName']; ?>" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="IsActive" class="col-lg-2 control-label">Is Active</label>
                            <div class="col-lg-4">
                                <input type="checkbox" id="IsActive" name="chkIsActive" value="1" <?php echo ($Clean['IsActive'] == 1) ? 'checked="checked"' : ''; ?>/>
                            </div>
                        </div>
                        <div class="form-group">
                        <div class="col-sm-offset-2 col-lg-10">
                        	<input type="hidden" name="hdnProcess" value="3" />
                        	<input type="hidden" name="hdnDepartmentID" value="<?php echo $Clean['DepartmentID'];?>" />
							<button type="submit" class="btn btn-primary">Save</button>
                        </div>
                      </div>
                    </div>
                </div>
            </form>
        </div>
        <!-- /#page-wrapper -->

    </div>
    <!-- /#wrapper -->
<?php
require_once('../footer.php');
?>
</body>
</html>
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

if ($LoggedUser->HasPermissionForTask(TASK_ADD_DEPARTMENT) !== true)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}

$HasErrors = false;

$Clean = array();
$Clean['Process'] = 0;

$Clean['DepartmentName'] = '';

if (isset($_POST['hdnProcess']))
{
	$Clean['Process'] = (int) $_POST['hdnProcess'];
}
switch ($Clean['Process'])
{
	case 1:						
		if (isset($_POST['txtDepartmentName']))
		{
			$Clean['DepartmentName'] = strip_tags(trim($_POST['txtDepartmentName']));
		}
		
		$NewRecordValidator = new Validator();
		$NewRecordValidator->ValidateStrings($Clean['DepartmentName'], 'Department name is required and should be between 3 and 100 characters.', 3, 100);
		
		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}
				
		$NewDepartment = new Department();
				
		$NewDepartment->SetDepartmentName($Clean['DepartmentName']);
		$NewDepartment->SetIsActive(1);
		
		$NewDepartment->SetCreateUserID($LoggedUser->GetUserID());

		if ($NewDepartment->RecordExists())
        {
            $NewRecordValidator->AttachTextError('Department name you have added already exists.');
            $HasErrors = true;
            break;
        }

		if (!$NewDepartment->Save())
		{
			$NewRecordValidator->AttachTextError(ProcessErrors($NewDepartment->GetLastErrorCode()));
			$HasErrors = true;

			break;
		}
		
		header('location:departments_list.php?Mode=ED');
		exit;
	break;
}

require_once('../html_header.php');
?>
<title>Add Department</title>
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
                    <h1 class="page-header">Add Department</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="AddDepartment" action="add_department.php" method="post">
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
                        <div class="col-sm-offset-2 col-lg-10">
                        	<input type="hidden" name="hdnProcess" value="1" />
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
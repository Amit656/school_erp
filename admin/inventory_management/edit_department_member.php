<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");
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

require_once("../../classes/inventory_management/class.departments.php");
require_once("../../classes/inventory_management/class.department_members.php");
require_once("../../classes/school_administration/class.branch_staff.php");

$AllDepartmentList = array();
$AllDepartmentList = Department::GetActiveDepartments();

$AllBranchStaffList = array();
$AllBranchStaffList = BranchStaff::GetAllBranchStaff();

$Clean = array();

$Clean['DepartmentMemberID'] = 0;

if (isset($_GET['DepartmentMemberID']))
{
    $Clean['DepartmentMemberID'] = (int) $_GET['DepartmentMemberID'];
}
elseif (isset($_POST['hdnDepartmentMemberID']))
{
    $Clean['DepartmentMemberID'] = (int) $_POST['hdnDepartmentMemberID'];
}

if ($Clean['DepartmentMemberID'] <= 0)
{
    header('location:../error.php');
    exit;
}   

try
{
    $DepartmentMemberToEdit = new DepartmentMember($Clean['DepartmentMemberID']);
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

$Clean['DepartmentID'] = '';
$Clean['BranchStaffID'] = 0;

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
		if (isset($_POST['drdDepartmentID']))
        {
            $Clean['DepartmentID'] = (int)($_POST['drdDepartmentID']);
        }

        if (isset($_POST['drdBranchStaffID']))
        {
            $Clean['BranchStaffID'] = (int)($_POST['drdBranchStaffID']);
        }
        
        $NewRecordValidator = new Validator();
        $NewRecordValidator->ValidateInSelect($Clean['DepartmentID'], $AllDepartmentList, 'Please select department name.');
        $NewRecordValidator->ValidateInSelect($Clean['BranchStaffID'], $AllBranchStaffList, 'Please select staff name.');
				
		$DepartmentMemberToEdit->SetDepartmentID($Clean['DepartmentID']);
		$DepartmentMemberToEdit->SetBranchStaffID($Clean['BranchStaffID']);
		
		$DepartmentMemberToEdit->SetCreateUserID($LoggedUser->GetUserID());

		if ($DepartmentMemberToEdit->RecordExists())
        {
            $NewRecordValidator->AttachTextError('Department member you have added already exists.');
            $HasErrors = true;
            break;
        }

		if (!$DepartmentMemberToEdit->Save())
		{
			$NewRecordValidator->AttachTextError(ProcessErrors($DepartmentMemberToEdit->GetLastErrorCode()));
			$HasErrors = true;

			break;
		}
		
		header('location:department_members_list.php?Mode=UD');
		exit;
		break;

	case 2:
		$Clean['DepartmentID'] = $DepartmentMemberToEdit->GetDepartmentID();
		$Clean['BranchStaffID'] = $DepartmentMemberToEdit->GetBranchStaffID();
		break;
}

require_once('../html_header.php');
?>
<title>Edit Department Member</title>
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
                    <h1 class="page-header">Edit Department Member</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="EditDepartmentMember" action="edit_department_member.php" method="post">
            	<div class="panel panel-default">
                    <div class="panel-heading">
                        Enter Department Member Details
                    </div>
                    <div class="panel-body">
<?php
						if ($HasErrors == true)
						{
							echo $NewRecordValidator->DisplayErrors();
						}
?>                    
                    	<div class="form-group">
                            <label for="Department" class="col-lg-2 control-label">Department</label>
                            <div class="col-lg-4">
                                <select class="form-control" name="drdDepartmentID" id="Department">
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
                        </div>
                        <div class="form-group">
                            <label for="BranchStaff" class="col-lg-2 control-label">Branch Staff</label>
                            <div class="col-lg-4">
                                <select class="form-control"  name="drdBranchStaffID" id="BranchStaffID">
<?php
                                foreach ($AllBranchStaffList as $BranchStaffID => $BranchStaffName)
                                {
?>
                                    <option <?php echo ($BranchStaffID == $Clean['BranchStaffID'] ? 'selected="selected"' : ''); ?> value="<?php echo $BranchStaffID; ?>"><?php echo $BranchStaffName['FirstName'] . " ". $BranchStaffName['LastName']; ?></option>
<?php
                                }
?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="col-sm-offset-2 col-lg-10">
                            	<input type="hidden" name="hdnProcess" value="3" />
                                <input type="hidden" name="hdnDepartmentMemberID" value="<?php echo $Clean['DepartmentMemberID'];?>" />
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
<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once("../../classes/hra/class.salary_parts.php");

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

if ($LoggedUser->HasPermissionForTask(TASK_EDIT_SALARY_PART) !== true)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}

$Clean = array();
$Clean['SalaryPartID'] = 0;

if (isset($_GET['SalaryPartID']))
{
    $Clean['SalaryPartID'] = (int) $_GET['SalaryPartID'];
}
else if (isset($_POST['hdnSalaryPartID']))
{
    $Clean['SalaryPartID'] = (int) $_POST['hdnSalaryPartID'];
}

if ($Clean['SalaryPartID'] <= 0)
{
    header('location:../error.php');
    exit;
}   

try
{
    $SalaryPartToEdit = new SalaryPart($Clean['SalaryPartID']);
}
catch (ApplicationDBException $e)
{
    header('location:../error.php');
    exit;
}
catch (Exception $e)
{
    header('location:../error.php');
}

$SalaryPartList = array();
$SalaryPartList = array('Allowance' => 'Allowance', 'Deduction' => 'Deduction');

$HasErrors = false;

$Clean['Process'] = 0;

$Clean['SalaryPartType'] = 'Allowance'; 
$Clean['SalaryPartName'] = '';
$Clean['Priority'] = '';
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
		if (isset($_POST['rdoSalaryPart']))
		{
			$Clean['SalaryPartType'] = strip_tags($_POST['rdoSalaryPart']);
		}

		if (isset($_POST['txtSalaryPartName']))
		{
			$Clean['SalaryPartName'] = strip_tags(trim($_POST['txtSalaryPartName']));
		}

		if (isset($_POST['txtPriority']))
		{
			$Clean['Priority'] = strip_tags(trim($_POST['txtPriority']));
		}

		if (isset($_POST['chkIsActive']))
		{
			$Clean['IsActive'] = 1;
		}
		
		$NewRecordValidator = new Validator();

		$NewRecordValidator->ValidateInSelect($Clean['SalaryPartType'], $SalaryPartList, 'Unknown error, please try again.');
		$NewRecordValidator->ValidateStrings($Clean['SalaryPartName'], 'Salary part name is required and should be between 4 and 30 characters.', 4, 30);
		$NewRecordValidator->ValidateInteger($Clean['Priority'], 'Priority is required and should be integer.', 0);

		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}
		
		$SalaryPartToEdit->SetSalaryPartType($Clean['SalaryPartType']);
		$SalaryPartToEdit->SetSalaryPartName($Clean['SalaryPartName']);
		$SalaryPartToEdit->SetPriority($Clean['Priority']);

		$SalaryPartToEdit->SetIsActive($Clean['IsActive']);

		if ($SalaryPartToEdit->RecordExists())
		{
			$NewRecordValidator->AttachTextError('The salary part Name you have added already exists');
			$HasErrors = true;
			break;
		}

		if (!$SalaryPartToEdit->Save())
		{
			$NewRecordValidator->AttachTextError(ProcessErrors($SalaryPartToEdit->GetLastErrorCode()));
			$HasErrors = true;

			break;
		}
		
		header('location:salary_part_list.php?Mode=UD&Process=7&SalaryPartType=' . $Clean['SalaryPartType']);
		exit;
		break;

    case 2:
	    $Clean['SalaryPartType'] = $SalaryPartToEdit->GetSalaryPartType();
	    $Clean['SalaryPartName'] = $SalaryPartToEdit->GetSalaryPartName();
	    $Clean['Priority'] = $SalaryPartToEdit->GetPriority();
	    $Clean['IsActive'] = $SalaryPartToEdit->GetIsActive();
    	break;
}

require_once('../html_header.php');
?>
<title>Edit Salary Part</title>
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
                    <h1 class="page-header">Edit Salary Part</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="EditSalaryPart" action="edit_salary_part.php" method="post">
            	<div class="panel panel-default">
                    <div class="panel-heading">
                        Edit Salary Parts Details
                    </div>
                    <div class="panel-body">
<?php
						if ($HasErrors == true)
						{
							echo $NewRecordValidator->DisplayErrors();
						}
?>                    
                    	<div class="form-group">
						<label for="SalaryPartType" class="col-lg-2 control-label">Salary Part Type</label>
                            <div class="col-lg-4">
<?php
								if (is_array($SalaryPartList) && count($SalaryPartList) > 0)
								{
									foreach($SalaryPartList as $SalaryPartType => $SalaryPartDetails)
									{
?>
										<label class="radio-inline">
											<input type="radio" id="SalaryPart<?php echo $SalaryPartType; ?>" name="rdoSalaryPart" <?php echo (($Clean['SalaryPartType'] == $SalaryPartType) ? 'checked="checked"' : ''); ?> value="<?php echo $SalaryPartType; ?>"> <?php echo $SalaryPartDetails; ?>
										</label>						
<?php										
									}
								}
?>
                            </div>
                        </div>
						<div class="form-group">
                            <label for="SalaryPartName" class="col-lg-2 control-label">Salary Part Name</label>
                            <div class="col-lg-4">
                            	<input class="form-control" type="text" maxlength="30" id="SalaryPartName" name="txtSalaryPartName" value="<?php echo $Clean['SalaryPartName']; ?>" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="Priority" class="col-lg-2 control-label">Priority</label>
                            <div class="col-lg-4">
                            	<input class="form-control" type="text" maxlength="5" id="Priority" name="txtPriority" value="<?php echo $Clean['Priority']; ?>" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="IsActive" class="col-lg-2 control-label">IsActive</label>
                            <div class="col-lg-4">
                            	<input type="checkbox" <?php echo($Clean['IsActive'] == 1) ? 'checked="checked"' : '' ?> id="IsActive" name="chkIsActive" value="1" />
                            </div>
                        </div>
                        <div class="form-group">
                        <div class="col-sm-offset-2 col-lg-10">
                        	<input type="hidden" name="hdnProcess" value="3"/>
                        	<input type="hidden" name="hdnSalaryPartID" value="<?php echo $Clean['SalaryPartID']; ?>" />
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
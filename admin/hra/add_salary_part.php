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

if ($LoggedUser->HasPermissionForTask(TASK_ADD_SALARY_PART) !== true)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}

$SalaryPartList = array();
$SalaryPartList = array('Allowance' => 'Allowance', 'Deduction' => 'Deduction');

$HasErrors = false;

$Clean = array();
$Clean['Process'] = 0;

$Clean['SalaryPartType'] = 'Allowance';

if (isset($_GET['SalaryPartType']))
{
	$Clean['SalaryPartType'] = strip_tags(trim($_GET['SalaryPartType']));

	if (!array_key_exists($Clean['SalaryPartType'], $SalaryPartList))
	{
		header('location:../error.php');
        exit;
	}
}

$Clean['SalaryPartName'] = '';
$Clean['Priority'] = '';

if (isset($_POST['hdnProcess']))
{
	$Clean['Process'] = (int) $_POST['hdnProcess'];
}
switch ($Clean['Process'])
{
	case 1:
		if (isset($_POST['rdoSalaryPartType']))
		{
			$Clean['SalaryPartType'] = strip_tags($_POST['rdoSalaryPartType']);
		}

		if (isset($_POST['txtSalaryPartName']))
		{
			$Clean['SalaryPartName'] = strip_tags(trim($_POST['txtSalaryPartName']));
		}

		if (isset($_POST['txtPriority']))
		{
			$Clean['Priority'] = strip_tags(trim($_POST['txtPriority']));
		}
		
		$NewRecordValidator = new Validator();

		$NewRecordValidator->ValidateInSelect($Clean['SalaryPartType'], $SalaryPartList, 'Unknown Error, Please try again.');
		$NewRecordValidator->ValidateStrings($Clean['SalaryPartName'], 'Salary Part Name is required and should be between 4 and 30 characters.', 4, 30);
		$NewRecordValidator->ValidateInteger($Clean['Priority'], 'Priority is required and should be integer.', 0);

		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}
				
		$NewSalaryPart = new SalaryPart();
				
		$NewSalaryPart->SetSalaryPartType($Clean['SalaryPartType']);
		$NewSalaryPart->SetSalaryPartName($Clean['SalaryPartName']);
		$NewSalaryPart->SetPriority($Clean['Priority']);

		$NewSalaryPart->SetIsActive(1);
		
		$NewSalaryPart->SetCreateUserID($LoggedUser->GetUserID());

		if ($NewSalaryPart->RecordExists())
		{
			$NewRecordValidator->AttachTextError('The salary part Name you have added already exists');
			$HasErrors = true;
			break;
		}

		if (!$NewSalaryPart->Save())
		{
			$NewRecordValidator->AttachTextError(ProcessErrors($NewSalaryPart->GetLastErrorCode()));
			$HasErrors = true;
			break;
		}

		header('location:salary_part_list.php?Mode=ED&Process=7&SalaryPartType=' . $Clean['SalaryPartType']);
		exit;
		break;
}

require_once('../html_header.php');
?>
<title>Add Salary Part</title>
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
                    <h1 class="page-header">Add Salary Part</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="AddSalaryPart" action="add_salary_part.php" method="post">
            	<div class="panel panel-default">
                    <div class="panel-heading">
                        Enter Salary Parts Details
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
											<input type="radio" id="SalaryPart<?php echo $SalaryPartType; ?>" name="rdoSalaryPartType" <?php echo (($Clean['SalaryPartType'] == $SalaryPartType) ? 'checked="checked"' : ''); ?> value="<?php echo $SalaryPartType; ?>"> <?php echo $SalaryPartDetails; ?>
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
                            	<input class="form-control" type="text" maxlength="3" id="Priority" name="txtPriority" value="<?php echo $Clean['Priority']; ?>" />
                            </div>
                        </div>
                        <div class="form-group">
                        <div class="col-sm-offset-2 col-lg-10">
                        	<input type="hidden" name="hdnProcess" value="1"/>
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
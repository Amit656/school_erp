<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once("../../classes/fee_management/class.fee_heads.php");

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

if ($LoggedUser->HasPermissionForTask(TASK_ADD_EDIT_FEE_HEAD) !== true)
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

$HasErrors = false;

$Clean = array();

$Clean['FeeHead'] = '';
$Clean['FeeHeadDescription'] = '';
$Clean['Priority'] = 0;
$Clean['Process'] = 0;

if (isset($_POST['hdnProcess']))
{
	$Clean['Process'] = (int) $_POST['hdnProcess'];
}
switch ($Clean['Process'])
{
	case 1:
		if (isset($_POST['txtFeeHead']))
		{
			$Clean['FeeHead'] = strip_tags(trim($_POST['txtFeeHead']));
		}

		if (isset($_POST['txtFeeHeadDescription']))
		{
			$Clean['FeeHeadDescription'] = strip_tags(trim($_POST['txtFeeHeadDescription']));
		}

		if (isset($_POST['txtPriority']))
		{
			$Clean['Priority'] = (int) $_POST['txtPriority'];
		}
		
		$NewRecordValidator = new Validator();
		$NewRecordValidator->ValidateStrings($Clean['FeeHead'], 'Fee head is required and should be between 3 and 50 characters.', 3, 50);

		$NewRecordValidator->ValidateStrings($Clean['FeeHeadDescription'], 'Fee head description is required and should be between 3 and 150 characters.', 3, 150);

		$NewRecordValidator->ValidateInteger($Clean['Priority'], 'please enter valid priority.', 1);
		
		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}
				
		$NewFeeHead = new FeeHead();
				
		$NewFeeHead->SetFeeHead($Clean['FeeHead']);

		$NewFeeHead->SetFeeHeadDescription($Clean['FeeHeadDescription']);

		$NewFeeHead->SetPriority($Clean['Priority']);
		$NewFeeHead->SetIsActive(1);
		
		$NewFeeHead->SetCreateUserID($LoggedUser->GetUserID());

		if (!$NewFeeHead->Save())
		{
			$NewRecordValidator->AttachTextError(ProcessErrors($NewFeeHead->GetLastErrorCode()));
			$HasErrors = true;
			break;
		}
		
		header('location:fee_head_list.php?Mode=AS');
		exit;
	break;
}

require_once('../html_header.php');
?>
<title>Add Fee Head</title>
<link href="vendor/jquery-ui/jquery-ui.min.css" rel="stylesheet">
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
                    <h1 class="page-header">Add Fee Head</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="AddFeeHead" action="add_fee_head.php" method="post">
            	<div class="panel panel-default">
                    <div class="panel-heading">
                        Enter Fee Head Details
                    </div>
                    <div class="panel-body">
<?php
						if ($HasErrors == true)
						{
							echo $NewRecordValidator->DisplayErrors();
						}
?>                    
						<div class="form-group">
                            <label for="FeeHead" class="col-lg-2 control-label">Fee Head</label>
                            <div class="col-lg-4">
                            	<input class="form-control" type="text" maxlength="50" id="FeeHead" name="txtFeeHead" value="<?php echo $Clean['FeeHead']; ?>" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="FeeHeadDescription" class="col-lg-2 control-label">Description</label>
                            <div class="col-lg-4">
                            	<textarea class="form-control"  id="FeeHeadDescription" name="txtFeeHeadDescription"><?php echo $Clean['FeeHeadDescription']; ?></textarea>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="Priority" class="col-lg-2 control-label">Priority</label>
                            <div class="col-lg-4">
                                <input class="form-control" type="text" maxlength="6" id="Priority" name="txtPriority" value="<?php echo ($Clean['Priority'] ? $Clean['Priority'] : ''); ?>" />
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
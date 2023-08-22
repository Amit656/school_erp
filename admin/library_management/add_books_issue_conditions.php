<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once("../../classes/library_management/class.books_issue_conditions.php");

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

if ($LoggedUser->HasPermissionForTask(TASK_ADD_BOOK_ISSUE_CONDITION) !== true)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}

$HasErrors = false;

$ConditionForList = array('Student' => 'Student', 'Teaching' => 'Teaching', 'NonTeaching' => 'Non-Teaching');
$LateFineTypeList = array('Daily' => 'Daily', 'Weakly' => 'Weakly', 'Monthly' => 'Monthly');

$Clean = array();
$Clean['Process'] = 0;

$Clean['ConditionFor'] = '';
$Clean['Quota'] = 0;
$Clean['DefaultDuration'] = 0;

$Clean['LateFineType'] = '';
$Clean['FineAmount'] = 0;

if (isset($_POST['hdnProcess']))
{
	$Clean['Process'] = (int) $_POST['hdnProcess'];
}
switch ($Clean['Process'])
{
	case 1:
		if (isset($_POST['drdConditionFor']))
		{
			$Clean['ConditionFor'] = strip_tags(trim($_POST['drdConditionFor']));
		}
		if (isset($_POST['txtQuota']))
		{
			$Clean['Quota'] = strip_tags(trim($_POST['txtQuota']));
		}
		if (isset($_POST['txtDefaultDuration']))
		{
			$Clean['DefaultDuration'] = strip_tags(trim($_POST['txtDefaultDuration']));
		}

		if (isset($_POST['drdLateFineType']))
		{
			$Clean['LateFineType'] = strip_tags(trim($_POST['drdLateFineType']));
		}
		if (isset($_POST['txtFineAmount']))
		{
			$Clean['FineAmount'] = strip_tags(trim($_POST['txtFineAmount']));
		}

		$NewRecordValidator = new Validator();

		$NewRecordValidator->ValidateInSelect($Clean['ConditionFor'], $ConditionForList, 'Unknown error, please try again.');
		$NewRecordValidator->ValidateInteger($Clean['Quota'], 'Please enter numeric value for quota.', 1);
		$NewRecordValidator->ValidateInteger($Clean['DefaultDuration'], 'Please enter numeric value for duration.', 1);
		
		$NewRecordValidator->ValidateInSelect($Clean['LateFineType'], $LateFineTypeList, 'Unknown error, please try again.');
		$NewRecordValidator->ValidateNumeric($Clean['FineAmount'], 'Please enter numeric value for amount.');

		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}
				
		$NewBooksIssueCondition = new BooksIssueCondition();
				
		$NewBooksIssueCondition->SetConditionFor($Clean['ConditionFor']);
		$NewBooksIssueCondition->SetQuota($Clean['Quota']);
		$NewBooksIssueCondition->SetDefaultDuration($Clean['DefaultDuration']);

		$NewBooksIssueCondition->SetLateFineType($Clean['LateFineType']);
		$NewBooksIssueCondition->SetFineAmount($Clean['FineAmount']);

		$NewBooksIssueCondition->SetCreateUserID($LoggedUser->GetUserID());

		if ($NewBooksIssueCondition->RecordExists())
		{
			$NewRecordValidator->AttachTextError('This condition details you have added already exists.');
			$HasErrors = true;
			break;
		}

		if (!$NewBooksIssueCondition->Save())
		{
			$NewRecordValidator->AttachTextError(ProcessErrors($NewBooksIssueCondition->GetLastErrorCode()));
			$HasErrors = true;
			break;
		}
		
		header('location:books_issue_conditions_list.php?Mode=AS');
		exit;
	break;
}

require_once('../html_header.php');
?>
<title>Add Book Conditions</title>
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
                    <h1 class="page-header">Add Book Conditions</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="AddBooksCondition" action="add_books_issue_conditions.php" method="post">
            	<div class="panel panel-default">
                    <div class="panel-heading">
                        <strong>Enter Book Condition Details</strong>
                    </div>
                    <div class="panel-body">
<?php
						if ($HasErrors == true)
						{
							echo $NewRecordValidator->DisplayErrors();
						}
?>                    
						<div class="form-group">
							<label for="ConditionFor" class="col-lg-2 control-label">Member Type</label>
                            <div class="col-lg-3">
                            	<select class="form-control" name="drdConditionFor" id="ConditionFor">
                            		<option value="">-- Select Member Type --</option>
<?php
								if (is_array($ConditionForList) && count($ConditionForList) > 0)
								{
									foreach($ConditionForList as $ConditionForID => $ConditionForName)
									{
										echo '<option ' . (($Clean['ConditionFor'] == $ConditionForID) ? 'selected="selected"' : '' ) . ' value="' . $ConditionForID . '">' . $ConditionForName . '</option>';
									}
								}
?>
								</select>
                            </div>
                        </div>
						<div class="form-group">
                            <label for="Quota" class="col-lg-2 control-label">Quota</label>
                            <div class="col-lg-3">
                            	<div class="input-group">
                            		<input class="form-control" type="text" maxlength="5" id="Quota" name="txtQuota" value="<?php echo ($Clean['Quota']) ? $Clean['Quota'] : ''; ?>" />
                            		<span class="input-group-addon">Books</span>
                            	</div>
                            </div>
                            <label for="DefaultDuration" class="col-lg-2 control-label">Default Duration</label>
                            <div class="col-lg-3">
                            	<div class="input-group">
                            		<input class="form-control" type="text" maxlength="5" id="DefaultDuration" name="txtDefaultDuration" value="<?php echo ($Clean['DefaultDuration']) ? $Clean['DefaultDuration'] : ''; ?>" />
                            		<span class="input-group-addon">Days</span>
                            	</div>
                            </div>
                        </div>
                        <div class="form-group">
                        	<label for="LateFineType" class="col-lg-2 control-label">Late Fine Type</label>
                            <div class="col-lg-3">
                            	<select class="form-control" name="drdLateFineType" id="LateFineType">                            		
<?php
								if (is_array($LateFineTypeList) && count($LateFineTypeList) > 0)
								{
									foreach($LateFineTypeList as $LateFineTypeID => $LateFineTypeName)
									{
										echo '<option ' . (($Clean['LateFineType'] == $LateFineTypeID) ? 'selected="selected"' : '' ) . ' value="' . $LateFineTypeID . '">' . $LateFineTypeName . '</option>';
									}
								}
?>
								</select>
                            </div>
                            <label for="FineAmount" class="col-lg-2 control-label">FineAmount</label>
                            <div class="col-lg-3">
                            	<div class="input-group">
                            		<input class="form-control" type="text" maxlength="10" id="FineAmount" name="txtFineAmount" value="<?php echo ($Clean['FineAmount']) ? $Clean['FineAmount'] : ''; ?>" />
                            		<span class="input-group-addon">Rs</span>
                            	</div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                        <div class="col-sm-offset-2 col-lg-10">
                        	<input type="hidden" name="hdnProcess" value="1" />
							<button type="submit" class="btn btn-primary"><i class="fa fa-save"></i>&nbsp;Save</button>
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
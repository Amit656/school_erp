<?php
require_once('../../classes/class.users.php');
require_once('../../classes/class.validation.php');
require_once('../../classes/class.authentication.php');

require_once('../../classes/school_administration/class.classes.php');
require_once('../../classes/school_administration/class.students.php');
require_once('../../classes/school_administration/class.student_details.php');
require_once('../../classes/school_administration/class.sms_templates.php');

require_once('../../classes/class.sms_queue.php');

require_once('../../classes/class.date_processing.php');

require_once('../../includes/global_defaults.inc.php');
require_once('../../includes/helpers.inc.php');

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

if ($LoggedUser->HasPermissionForTask(TASK_ADD_EDIT_SMS_TEMPLATE) !== true)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}

$SMSTypeList = array();
$SMSTypeList = array('StudentBirthday' => 'Student Birthday', 'Enquiry' => 'Enquiry', 'EnquiryFollowUp' => 'Enquiry FollowUp', 'StudentRegistration' => 'Student Registration');

$HasSearchErrors = false;
$HasError = false;

$Clean = array();

$Clean['SMSType'] = '';
$Clean['SMSTemplate'] = '';
$Clean['Description'] = '';
$Clean['IsActive'] = 0;

$Clean['Process'] = 0;

if (isset($_POST['hdnProcess']))
{
	$Clean['Process'] = (int) $_POST['hdnProcess'];
}

else if (isset($_GET['hdnProcess']))
{
	$Clean['Process'] = (int) $_GET['hdnProcess'];
}

if (isset($_GET['SMSType']))
{
	$Clean['SMSType'] = strip_tags(trim($_GET['SMSType']));
}
else if (isset($_GET['drdSMSType']))
{
	$Clean['SMSType'] = strip_tags(trim($_GET['drdSMSType']));
}

try
{
	$NewSMSTemplate = new SMSTemplate(0 ,$Clean['SMSType']);
}

// THIS CATCH BLOCK BUBBLES THE EXCEPTION TO THE BUILT IN 'Exception' CLASS IF THERE ARE ANY UNCAUGHT ERRORS //
catch (ApplicationAuthException $e)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}
catch (Exception $e)
{
	$NewSMSTemplate = new SMSTemplate();
}

switch ($Clean['Process'])
{
	case 1:	
		if (isset($_POST['drdSMSType']))
		{
			$Clean['SMSType'] = strip_tags(trim($_POST['drdSMSType']));
		}
		
		if (isset($_POST['txtSMSTemplate']))
		{
			$Clean['SMSTemplate'] = strip_tags(trim($_POST['txtSMSTemplate']));
		}
		
		if (isset($_POST['txtDescription']))
		{
			$Clean['Description'] = strip_tags(trim($_POST['txtDescription']));
		}

		if (isset($_POST['chkIsActive']))
		{
			$Clean['IsActive'] = $_POST['chkIsActive'];
		}
		
		$NewRecordValidator = new Validator();

		$NewRecordValidator->ValidateInSelect($Clean['SMSType'], $SMSTypeList, 'Unknown error, please try again.');

		$NewRecordValidator->ValidateStrings($Clean['SMSTemplate'], 'Message content (SMS Template) is required and should be between 10 and 1500 characters.', 10, 1500);
		
		if ($Clean['Description'] != '')
		{
			$NewRecordValidator->ValidateStrings($Clean['Description'], 'Description is required and should be between 10 and 1500 characters.', 10, 1500);
		}		

		if ($NewRecordValidator->HasNotifications())
		{
			$HasError = true;
			break;
		}

		try
		{
			$NewSMSTemplate = new SMSTemplate(0 ,$Clean['SMSType']);
		}

		// THIS CATCH BLOCK BUBBLES THE EXCEPTION TO THE BUILT IN 'Exception' CLASS IF THERE ARE ANY UNCAUGHT ERRORS //
		catch (ApplicationAuthException $e)
		{
			header('location:/admin/unauthorized_login_admin.php');
			exit;
		}
		catch (Exception $e)
		{
			$NewSMSTemplate = new SMSTemplate();
		}

		$NewSMSTemplate->SetSMSType($Clean['SMSType']);
		$NewSMSTemplate->SetSMSTemplate($Clean['SMSTemplate']);
		$NewSMSTemplate->SetDescription($Clean['Description']);
		$NewSMSTemplate->SetIsActive($Clean['IsActive']);
		$NewSMSTemplate->SetCreateUserID($LoggedUser->GetUserID());

		if (!$NewSMSTemplate->Save())
        {            
            $NewRecordValidator->AttachTextError(ProcessErrors($NewSMSTemplate->GetLastErrorCode()));
            $HasError = true;
            break;
        }

		header('location:sms_templates.php?SMSType='.$Clean['SMSType']. 'Mode=ED');
		exit;
	break;

	case 7:	
		if (isset($_GET['drdSMSType']))
		{
			$Clean['SMSType'] = strip_tags(trim($_GET['drdSMSType']));
		}

		$NewRecordValidator = new Validator();

		$NewRecordValidator->ValidateInSelect($Clean['SMSType'], $SMSTypeList, 'Unknown error, please try again.');
		
		if ($NewRecordValidator->HasNotifications())
		{
			$HasSearchErrors = true;
			break;
		}

		$Clean['SMSTemplateID'] = $NewSMSTemplate->GetSMSTemplateID();	

		if ($NewSMSTemplate->GetSMSType($Clean['SMSType']) != '')
		{
			$Clean['SMSType'] = $NewSMSTemplate->GetSMSType();
		}

		$Clean['SMSTemplate'] = $NewSMSTemplate->GetSMSTemplate();
		$Clean['Description'] = $NewSMSTemplate->GetDescription();
		$Clean['IsActive'] = $NewSMSTemplate->GetIsActive();
	break;
}

require_once('../html_header.php');
?>
<title>SMS Templet</title>
<link href="../vendor/jquery-ui/jquery-ui.min.css" rel="stylesheet">
<style>
.place-holder-text a {
	color: gray !important;
	cursor: pointer;
	text-decoration: none;
}
</style>
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
                    <h1 class="page-header">SMS Templete</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
			<form class="form-horizontal" name="SMSTemplete" action="sms_templates.php" method="get">
				<div class="panel panel-default">
					<div class="panel-heading">
						<strong>SMS Type</strong>
					</div>
					<div class="panel-body">
<?php
						if ($HasSearchErrors == true)
						{
							echo $NewRecordValidator->DisplayErrors();
						}
?>
						<div class="form-group">
							<label for="SMSType" class="col-lg-2 control-label">SMS Type</label>
                            <div class="col-lg-4">
                            	<select class="form-control" name="drdSMSType" id="SMSType">
<?php
                                if (is_array($SMSTypeList) && count($SMSTypeList) > 0)
                                {
                                    foreach ($SMSTypeList as $SMSType => $SMSTypeName) 
                                    {
                                        echo '<option ' . ($Clean['SMSType'] == $SMSType ? 'selected="selected"' : '') . ' value="' . $SMSType . '">' . $SMSTypeName . '</option>';
                                    }
                                }
?>
								</select>
                            </div>
						</div>
						<div class="form-group">
							<div class="col-sm-offset-2 col-lg-10">
								<input type="hidden" name="hdnProcess" value="7" />
								<button type="submit" class="btn btn-primary">Search</button>
							</div>
						</div>
					</div>
				</div>
			</form>
<?php
			if (($Clean['Process'] == 7 && $HasSearchErrors == false) || $Clean['Process'] == 1)
			{
?>
				<form class="form-horizontal" name="SaveSMSTemplete" action="sms_templates.php" method="post">
					<div class="panel panel-default">
						<div class="panel-heading">
							<strong>SMS Templet Details</strong>
						</div>
						<div id="collapseOne" class="panel-collapse collapse in">
							<div class="panel-body">
<?php
								if ($HasError == true)
								{
									echo $NewRecordValidator->DisplayErrors();
								}
?>
								<div class="form-group">
									<label for="SMSTemplate" class="col-lg-2 control-label">SMS Templet</label>
									<div class="col-lg-8">
										<textarea name="txtSMSTemplate" class="form-control" id="SMSTemplate"><?php echo $Clean['SMSTemplate']; ?></textarea>
									</div>
								</div>
								
								<div class="form-group">
									<div class="col-sm-offset-2 col-lg-10 place-holder-text">
										<a href="javascript:void(0);">{StudentFirstName}</a> | 
										<a href="javascript:void(0);">{StudentLastName}</a> | 
										<a href="javascript:void(0);">{FatherName}</a> | 
										<a href="javascript:void(0);">{MotherName}</a> | 
										<a href="javascript:void(0);">{Class}</a> | 
										<a href="javascript:void(0);">{Section}</a> | 
										<a href="javascript:void(0);">{EntranceExamDateTime}</a>
									</div>
								</div>

								<div class="form-group">
									<label for="Description" class="col-lg-2 control-label">Description</label>
									<div class="col-lg-8">
										<textarea name="txtDescription" class="form-control" id="Description"><?php echo $Clean['Description']; ?></textarea>
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
										<input type="hidden" name="drdSMSType" value="<?php echo $Clean['SMSType']; ?>" />
										<input type="hidden" name="hdnProcess" value="1" />
										<button type="submit" class="btn btn-primary">Save</button>
									</div>
								</div>
							</div>
						</div>
					</div>
				</form>
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
<script src="/admin/vendor/jquery-ui/jquery-ui.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/gasparesganga-jquery-loading-overlay@2.1.6/dist/loadingoverlay.min.js"></script>

<script type="text/javascript">
$(function(){	
	$('.place-holder-text a').click(function(){
		var SMSContent = $(this).closest('.form-group').prev().find('textarea').val();
		$(this).closest('.form-group').prev().find('textarea').val(SMSContent + $(this).text());
	});
});
</script>
</body>
</html>
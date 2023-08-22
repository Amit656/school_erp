<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once("../../classes/class.global_settings.php");

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

if ($LoggedUser->HasPermissionForTask(DEFAULTER_SETTING) !== true)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}

$GlobalSettingObject = new GlobalSetting();

$HasErrors = false;

$FeeSubmissionTypeList = array('Prior' => 'Upcoming Months Fee', 'Past' => 'Previous Months Fee');

$Clean = array();
$Clean['Process'] = 0;

$Clean['FeeSubmissionLastDate'] = 0;
$Clean['FeeSubmissionFrequency'] = 0;
$Clean['FeeSubmissionType'] = '';

$Clean['FeeSubmissionLastDate'] = $GlobalSettingObject->GetFeeSubmissionLastDate();
$Clean['FeeSubmissionFrequency'] = $GlobalSettingObject->GetFeeSubmissionFrequency();
$Clean['FeeSubmissionType'] = $GlobalSettingObject->GetFeeSubmissionType();

if (isset($_POST['hdnProcess']))
{
	$Clean['Process'] = (int) $_POST['hdnProcess'];
}
switch ($Clean['Process'])
{
	case 1:
		if (isset($_POST['drdFeeSubmissionLastDate']))
		{
			$Clean['FeeSubmissionLastDate'] = (int) $_POST['drdFeeSubmissionLastDate'];
		}

		if (isset($_POST['drdFeeSubmissionFrequency']))
		{
			$Clean['FeeSubmissionFrequency'] = (int) $_POST['drdFeeSubmissionFrequency'];
		}

		if (isset($_POST['optFeeSubmissionType']))
		{
			$Clean['FeeSubmissionType'] = strip_tags(trim($_POST['optFeeSubmissionType']));
		}

		$NewRecordValidator = new Validator();

		$NewRecordValidator->ValidateInSelect($Clean['FeeSubmissionType'], $FeeSubmissionTypeList, 'Unknown error, please try again.');
				
		if ($Clean['FeeSubmissionLastDate'] < 1 || $Clean['FeeSubmissionLastDate'] > 31) 
		{
			$NewRecordValidator->AttachTextError('Unknown error, please try again.');						
		}

		if ($Clean['FeeSubmissionFrequency'] < 1 || $Clean['FeeSubmissionFrequency'] > 12) 
		{
			$NewRecordValidator->AttachTextError('Unknown error, please try again.');					
		}			

		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}
						
		$GlobalSettingObject->SetFeeSubmissionLastDate($Clean['FeeSubmissionLastDate']);
		$GlobalSettingObject->SetFeeSubmissionFrequency($Clean['FeeSubmissionFrequency']);
		$GlobalSettingObject->SetFeeSubmissionType($Clean['FeeSubmissionType']);

		if (!$GlobalSettingObject->Save())
		{
			$NewRecordValidator->AttachTextError(ProcessErrors($GlobalSettingObject->GetLastErrorCode()));
			$HasErrors = true;
			break;
		}
		
		header('location:defaulter_settings.php?Mode=UD');
		exit;
	break;
}

$LandingPageMode = '';
if (isset($_GET['Mode']))
{
    $LandingPageMode = $_GET['Mode'];
}

require_once('../html_header.php');
?>
<title>Defaulter Settings</title>
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
                    <h1 class="page-header">Defaulter Settings</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="AddFeeGroup" action="defaulter_settings.php" method="post">
            	<div class="panel panel-default">
                    <div class="panel-heading">
                        <strong>Enter Defaulter Settings Details</strong>
                    </div>
                    <div class="panel-body">
<?php
						if ($HasErrors == true)
						{
							echo $NewRecordValidator->DisplayErrors();
						}
						else if ($LandingPageMode == 'UD')
                        {
                            echo '<div class="alert alert-success alert-top-margin">Record updated successfully.</div><br>';
                        }
?>                    
                    	<div class="form-group">
							<label for="FeeSubmissionLastDate" class="col-lg-3 control-label">Fee Submission Last Date</label>
                            <div class="col-lg-4">
                            	<select class="form-control" name="drdFeeSubmissionLastDate" id="FeeSubmissionLastDate">
<?php
								for ($Counter = 1; $Counter <= 31; $Counter++)
								{
									echo '<option ' . (($Clean['FeeSubmissionLastDate'] == $Counter) ? 'selected="selected"' : '' ) . ' value="' . $Counter . '">' . $Counter . '</option>';
								}
?>
								</select>
                            </div>
                        </div>
						<div class="form-group">
							<label for="FeeSubmissionFrequency" class="col-lg-3 control-label">Fee Submission In Every</label>
                            <div class="col-lg-4">
                            	<select class="form-control" name="drdFeeSubmissionFrequency" id="FeeSubmissionFrequency">
<?php
								for ($Counter = 1; $Counter <= 12; $Counter++)
								{
									echo '<option ' . (($Clean['FeeSubmissionFrequency'] == $Counter) ? 'selected="selected"' : '' ) . ' value="' . $Counter . '">' . $Counter .' Month'. '</option>';
								}
?>
								</select>
                            </div>
                        </div>
                        <div class="form-group">
							<label for="FeeSubmissionType" class="col-lg-3 control-label">Fee Submission Type</label>
                            <div class="col-lg-5">
<?php
                            foreach($FeeSubmissionTypeList as $FeeSubmissionTypeID => $FeeSubmissionType)
                            {
?>                              
                                <label class="col-sm-6"><input class="custom-radio" type="radio" id="<?php echo $FeeSubmissionTypeID; ?>" name="optFeeSubmissionType" value="<?php echo $FeeSubmissionTypeID; ?>" <?php echo ($Clean['FeeSubmissionType'] == $FeeSubmissionTypeID ? 'checked="checked"' : ''); ?> >&nbsp;&nbsp;<?php echo $FeeSubmissionType; ?></label>            
<?php                                       
                            }
?>
                            </div>
                        </div>
                        
                        <div class="form-group">
                        <div class="col-sm-offset-3 col-lg-9">
                        	<input type="hidden" name="hdnProcess" value="1" />
							<button type="submit" class="btn btn-primary">Update</button>
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
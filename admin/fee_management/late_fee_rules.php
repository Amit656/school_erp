<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once("../../classes/fee_management/class.late_fee_rules.php");

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

if ($LoggedUser->HasPermissionForTask(LATE_FEE_RULES) !== true)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}

$LateFeeRuleObject = new LateFeeRule();

$HasErrors = false;

$ChargeMethodList = array('PerDay' => 'Per Day', 'PerRange' => 'Per Range');

$Clean = array();
$Clean['Process'] = 0;

$Clean['ChargeMethod'] = array(1 => 'PerDay', 2 => 'PerDay', 3 => 'PerDay');
$Clean['RangeFromDay'] = array(1 => 0, 2 => 0, 3 => 0);
$Clean['RangeToDay'] = array(1 => 0, 2 => 0, 3 => 0);
$Clean['LateFeeAmount'] = array(1 => 0, 2 => 0, 3 => 0);

$Clean['CreateUserID'] = 0;

$Clean['LateFeeRules'] = array();

$LateFeeRules = LateFeeRule::GetAllLateFeeRules();

if (count($LateFeeRules) > 0) 
{
	$Counter = 1;

	foreach ($LateFeeRules as $LateFeeRuleID => $Details) 
	{
		$Clean['ChargeMethod'][$Counter] = $Details['ChargeMethod'];
		$Clean['RangeFromDay'][$Counter] = $Details['RangeFromDay'];
		$Clean['RangeToDay'][$Counter] = $Details['RangeToDay'];
		$Clean['LateFeeAmount'][$Counter] = $Details['LateFeeAmount'];

		$Counter++;	
	}
}

if (isset($_POST['hdnProcess']))
{
	$Clean['Process'] = (int) $_POST['hdnProcess'];
}
switch ($Clean['Process'])
{
	case 1:
		if (isset($_POST['txtRangeFromDay']) && is_array($_POST['txtRangeFromDay']))
		{
			$Clean['RangeFromDay'] = $_POST['txtRangeFromDay'];
		}

		if (isset($_POST['txtRangeToDay']) && is_array($_POST['txtRangeToDay']))
		{
			$Clean['RangeToDay'] = $_POST['txtRangeToDay'];
		}

		if (isset($_POST['txtLateFeeAmount']) && is_array($_POST['txtLateFeeAmount']))
		{
			$Clean['LateFeeAmount'] =  $_POST['txtLateFeeAmount'];
		}

		if (isset($_POST['optChargeMethod']) && is_array($_POST['optChargeMethod']))
		{
			$Clean['ChargeMethod'] =  $_POST['optChargeMethod'];
		}

		$NewRecordValidator = new Validator();

		if ($Clean['RangeFromDay'][1] == '' && $Clean['RangeFromDay'][2] == '' && $Clean['RangeFromDay'][3] == '') 
		{
			$NewRecordValidator->AttachTextError('Please fill atleast on range details for saving rules.');

			$HasErrors = true;
			break;					
		}

		foreach ($Clean['RangeFromDay'] as $Counter => $Value) 
		{
			if ($Clean['RangeFromDay'][$Counter] != '') 
			{
				$NewRecordValidator->ValidateInteger($Clean['RangeFromDay'][$Counter], 'Please enter valid number for range from day.', 1);
			}
			
			if ($Clean['RangeToDay'][$Counter] != '') 
			{
				$NewRecordValidator->ValidateInteger($Clean['RangeToDay'][$Counter], 'Please enter valid number for range to day.', 1);
			}

			if ($Clean['RangeFromDay'][$Counter] != '') 
			{
				$NewRecordValidator->ValidateNumeric($Clean['LateFeeAmount'][$Counter], 'Please enter valid number for late charge.', 1);
			}

			if ($Clean['RangeFromDay'][$Counter] != '') 
			{
				$NewRecordValidator->ValidateInSelect($Clean['ChargeMethod'][$Counter], $ChargeMethodList, 'Unknown error, please try again.');
			}

			$Clean['LateFeeRules'][$Counter]['RangeFromDay'] = $Clean['RangeFromDay'][$Counter];
			$Clean['LateFeeRules'][$Counter]['RangeToDay'] = $Clean['RangeToDay'][$Counter];
			$Clean['LateFeeRules'][$Counter]['LateFeeAmount'] = $Clean['LateFeeAmount'][$Counter];
			$Clean['LateFeeRules'][$Counter]['ChargeMethod'] = $Clean['ChargeMethod'][$Counter];
		}	

		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}
						
		$LateFeeRuleObject->SetLateFeeRules($Clean['LateFeeRules']);

		$LateFeeRuleObject->SetCreateUserID($LoggedUser->GetUserID());

		if (!$LateFeeRuleObject->Save())
		{
			$NewRecordValidator->AttachTextError(ProcessErrors($LateFeeRuleObject->GetLastErrorCode()));
			$HasErrors = true;
			break;
		}
		
		header('location:late_fee_rules.php?Mode=UD');
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
<title>Late Fee Rules</title>
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
                    <h1 class="page-header">Late Fee Rules</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="AddFeeGroup" action="late_fee_rules.php" method="post">
            	<div class="panel panel-default">
                    <div class="panel-heading">
                        <strong>Enter Late Fee Rules Details</strong>
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

                    for ($Counter = 1; $Counter <= 3; $Counter++) 
                    { 
?>
						<div class="form-group">
							<label for="RangeFromDay" class="col-lg-1 control-label">Range</label>
							<div class="col-lg-3">
                            	<div class="input-group">
                            		<span class="input-group-addon">From</span>
                            		<input class="form-control" type="text" maxlength="5" id="RangeFromDay" name="txtRangeFromDay[<?php echo $Counter; ?>]" value="<?php echo ($Clean['RangeFromDay'][$Counter]) ? $Clean['RangeFromDay'][$Counter] : ''; ?>" />
                            		<span class="input-group-addon">Day</span>
                            	</div>
                            </div>
                            <div class="col-lg-3">
                            	<div class="input-group">
                            		<span class="input-group-addon">To</span>
                            		<input class="form-control" type="text" maxlength="5" id="RangeToDay" name="txtRangeToDay[<?php echo $Counter; ?>]" value="<?php echo ($Clean['RangeToDay'][$Counter]) ? $Clean['RangeToDay'][$Counter] : ''; ?>" />
                            		<span class="input-group-addon">Day</span>
                            	</div>
                            </div>
                            <div class="col-lg-2">
                            	<div class="input-group">
                            		<span class="input-group-addon"><i class="fa fa-inr"></i>&nbsp;&nbsp;</span>
                            		<input class="form-control" type="text" maxlength="10" id="LateFeeAmount" name="txtLateFeeAmount[<?php echo $Counter; ?>]" placeholder="Late Charge" value="<?php echo ($Clean['LateFeeAmount'][$Counter]) ? $Clean['LateFeeAmount'][$Counter] : ''; ?>" />
                            	</div>
                            </div>
                            <div class="col-lg-3">
<?php
                            foreach($ChargeMethodList as $ChargeMethodID => $ChargeMethod)
                            {
?>                              
                                <label class="col-sm-6"><input class="custom-radio" type="radio" id="<?php echo $ChargeMethodID; ?>" name="optChargeMethod[<?php echo $Counter; ?>]" value="<?php echo $ChargeMethodID; ?>" <?php echo ($Clean['ChargeMethod'][$Counter] == $ChargeMethodID ? 'checked="checked"' : ''); ?> >&nbsp;&nbsp;<?php echo $ChargeMethod; ?></label>            
<?php                                       
                            }
?>
                            </div>
                        </div>
<?php
                    }
?>                    
                        
                        <div class="form-group">
                        <div class="col-sm-offset-2 col-lg-9">
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
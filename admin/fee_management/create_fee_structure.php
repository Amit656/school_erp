<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once("../../classes/school_administration/class.classes.php");
require_once("../../classes/school_administration/class.academic_years.php");
require_once("../../classes/school_administration/class.academic_year_months.php");

require_once("../../classes/fee_management/class.fee_heads.php");
require_once("../../classes/fee_management/class.fee_groups.php");
require_once("../../classes/fee_management/class.fee_structure.php");

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

if ($LoggedUser->HasPermissionForTask(TASK_CREATE_FEE_STRUCTURE) !== true)
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
$HasSearchErrors = false;

$AcademicYears =  array();
$AcademicYears = AcademicYear::GetAllAcademicYears();

$AcademicYearName = '';
$AcademicYearID = AcademicYear::GetCurrentAcademicYear($AcademicYearName);

$ClassList =  array();
$ClassList = AddedClass::GetActiveClasses();

$AllMonths = array();
$AllMonths = AcademicYearMonth::GetMonthsByFeePriority();

$FeeGroupList = array();
$FeeGroupList = FeeGroup::GetActiveFeeGroups();

$AllFeeHeads = array();
$AllFeeHeads = FeeHead::GetActiveFeeHeads();

$Clean = array();

$Clean['Process'] = 0;

$Clean['AcademicYearID'] = 0;
$Clean['ClassID'] = 0;

$Clean['FeeStructureDetails'] = array();
$Clean['FeeAmountList'] = array();

$FeeStructureDetails = array();

$FeeHeadErrors = array();

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
    case 1:
        
        if (isset($_POST['hdnAcademicYearID'])) 
		{
			$Clean['AcademicYearID'] = (int) $_POST['hdnAcademicYearID'];
		}
		
		if (isset($_POST['hdnClassID']))
        {
            $Clean['ClassID'] = (int) $_POST['hdnClassID'];
        }

        if (isset($_POST['FeeStructure']) && is_array($_POST['FeeStructure']))
        {
            $Clean['FeeStructureDetails'] = $_POST['FeeStructure'];
        }

        $NewRecordValidator = new Validator();

        $NewRecordValidator->ValidateInSelect($Clean['ClassID'], $ClassList, 'Unknown error, please try again.');        

        if ($NewRecordValidator->HasNotifications())
		{
            header('location:/admin/error.php');
            exit;
		}

        $FeeStructureToSave = array();
       
        foreach ($Clean['FeeStructureDetails'] as $FeeGroupID => $FeeStructureDetails)
        {
            $FeeGroupAmount = 0;
            
            foreach ($FeeStructureDetails as $FeeHeadID => $FeeHeadDetails)
            {
                $FeeHeadRecordValidator = new Validator();

                $FeeStructureToSave[$FeeGroupID][$FeeHeadID] = array();
                
                $FeeHeadAmount = 0;
                $FeeHeadApplicableMonths = array();
                
                if ($AllFeeHeads[$FeeHeadID]['IsSystemGenerated'] == 0) 
                {
                    if ($FeeHeadDetails['FeeHeadAmount'] != '')
                    {
                        $FeeHeadAmount = $FeeHeadDetails['FeeHeadAmount'];                    
                    }
                    
                    $FeeHeadRecordValidator->ValidateNumeric($FeeHeadAmount, 'error');
                }

                if ($FeeHeadRecordValidator->HasNotifications())
                {
                    $FeeHeadErrors[$FeeGroupID][$FeeHeadID]['FeeHeadAmount'] = true;
                }

                if (isset($FeeHeadDetails['FeeHeadApplicableMonths']) && is_array($FeeHeadDetails['FeeHeadApplicableMonths']))
                {
                    $FeeHeadApplicableMonths = $FeeHeadDetails['FeeHeadApplicableMonths'];
                }
                else
                {
                    $Clean['FeeStructureDetails'][$FeeGroupID][$FeeHeadID]['FeeHeadApplicableMonths'] = array();
                }

                if (array_key_exists('FeeHeadSelected', $FeeHeadDetails)) 
                {
                    if ($AllFeeHeads[$FeeHeadID]['IsSystemGenerated'] == 0) 
                    {
                        if ($FeeHeadAmount <= 0) 
                        {
                            $FeeHeadErrors[$FeeGroupID][$FeeHeadID]['FeeHeadAmount'] = true;
                        }
                    }
                    
                    if (!array_key_exists('FeeHeadApplicableMonths', $FeeHeadDetails)) 
                    {
                        $FeeHeadErrors[$FeeGroupID][$FeeHeadID]['FeeHeadApplicableMonths'] = true;
                    }
                }
                else
                {
                    unset($Clean['FeeStructureDetails'][$FeeGroupID][$FeeHeadID]);
                }

                $FeeStructureToSave[$FeeGroupID][$FeeHeadID]['FeeAmount'] = $FeeHeadAmount;
                $FeeStructureToSave[$FeeGroupID][$FeeHeadID]['AcademicYearMonthID'] = $FeeHeadApplicableMonths;
                
                $FeeGroupAmount += $FeeHeadAmount;
                
            }
            
            if ($FeeGroupAmount <= 0)
            {
                unset($FeeStructureToSave[$FeeGroupID]);
            }
        }

        if (count($FeeHeadErrors) > 0) 
        {
            $NewRecordValidator->AttachTextError('There were error in ammounts entered by you, please scroll down to see indivisual errors.');
            $HasErrors = true;
			break;
        }

        if (FeeStructure::FeeStructureExists($Clean['AcademicYearID'], $Clean['ClassID'], 0, $FeeStructureDetails))
        {
            foreach ($Clean['FeeStructureDetails'] as $FeeGroupID => $FeeStructureDetail)
            {
                if (array_key_exists($FeeGroupID, $FeeStructureDetails)) 
                {
                    foreach ($FeeStructureDetail as $FeeHeadID => $FeeHeadDetails)
                    {
                        if (array_key_exists($FeeHeadID, $FeeStructureDetails[$FeeGroupID]))
                        {
                            if (isset($FeeHeadDetails['FeeHeadApplicableMonths']) && is_array($FeeHeadDetails['FeeHeadApplicableMonths']))
                            {
                                $AdditionalMonths = array();
                                
                                foreach ($FeeHeadDetails['FeeHeadApplicableMonths'] as $MonthID => $Value) 
                                {
                                    if (!array_key_exists($MonthID, $FeeStructureDetails[$FeeGroupID][$FeeHeadID]['FeeHeadApplicableMonths'])) 
                                    {
                                        $AdditionalMonths[$MonthID] = $MonthID;    
                                    }
                                }
                            }

                            if (isset($FeeStructureDetails[$FeeGroupID][$FeeHeadID]['FeeHeadApplicableMonths']) && is_array($FeeStructureDetails[$FeeGroupID][$FeeHeadID]['FeeHeadApplicableMonths']))
                            {
                                $RemovableMonths = array();
                                
                                foreach ($FeeStructureDetails[$FeeGroupID][$FeeHeadID]['FeeHeadApplicableMonths'] as $MonthID => $FeeStructureDetailID) 
                                {
                                    if (!array_key_exists($MonthID, $FeeHeadDetails['FeeHeadApplicableMonths'])) 
                                    {
                                        $RemovableMonths[$MonthID] = $MonthID;
                                    }
                                }
                            }        
                        }
                        else
                        {
                            $AdditionalMonths = array();

                            foreach ($FeeHeadDetails['FeeHeadApplicableMonths'] as $MonthID => $Value) 
                            {
                                $AdditionalMonths[$MonthID] = $MonthID;  
                            }
                        }

                        $FeeStructureToSave[$FeeGroupID][$FeeHeadID]['AddMonths'] = $AdditionalMonths;  
                        $FeeStructureToSave[$FeeGroupID][$FeeHeadID]['RemoveMonths'] = $RemovableMonths;
                        $FeeStructureToSave[$FeeGroupID][$FeeHeadID]['AcademicYearMonthID'] = array();
                    }
                }
            }
        }
      
        $CurrentFeeStructure = new FeeStructure();

		$CurrentFeeStructure->SetAcademicYearID($Clean['AcademicYearID']);

        $CurrentFeeStructure->SetClassID($Clean['ClassID']);
        
		$CurrentFeeStructure->SetIsActive(1);
		$CurrentFeeStructure->SetCreateUserID($LoggedUser->GetUserID());

        $CurrentFeeStructure->SetFeeStructureDetails($FeeStructureToSave);

		if (!$CurrentFeeStructure->Save())
		{
			$NewRecordValidator->AttachTextError(ProcessErrors($CurrentFeeStructure->GetLastErrorCode()));
			$HasErrors = true;
			break;
		}
		
		header('location:create_fee_structure.php?Mode=AS&Process=7&ClassID='.$Clean['ClassID'].'');
		exit;
	break;

    case 7:
        if (isset($_POST['drdAcademicYear'])) 
		{
			$Clean['AcademicYearID'] = (int) $_POST['drdAcademicYear'];
		}
		else if (isset($_GET['AcademicYearID'])) 
		{
			$Clean['AcademicYearID'] = (int) $_GET['AcademicYearID'];
		}
		
        if (isset($_POST['drdClassID'])) 
        {
            $Clean['ClassID'] = (int) $_POST['drdClassID'];
        }
        if (isset($_GET['ClassID'])) 
        {
            $Clean['ClassID'] = (int) $_GET['ClassID'];
        }

        $SearchRecordValidator = new Validator();

        $SearchRecordValidator->ValidateInSelect($Clean['ClassID'], $ClassList, 'Please select a class.');
        
        if ($SearchRecordValidator->HasNotifications())
        {
            $HasSearchErrors = true;
            break;
        }
        
        if (FeeStructure::FeeStructureExists($Clean['AcademicYearID'], $Clean['ClassID'], 0, $FeeStructureDetails))
        {
            $Clean['FeeStructureDetails'] = $FeeStructureDetails;
        }
    break;
}

$LandingPageMode = '';
if (isset($_GET['Mode']))
{
    $LandingPageMode = $_GET['Mode'];
}

require_once('../html_header.php');
?>
<title>Create Fee Structure</title>
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
                    <h1 class="page-header">Create Fee Structure</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
            <div class="panel panel-default">
                <div class="panel-heading">
                    <strong>Fee Structure Details</strong>
                </div>
                <div class="panel-body">
<?php
                    if ($HasSearchErrors == true)
                    {
                        echo $SearchRecordValidator->DisplayErrors();
                    }
                    else if ($LandingPageMode == 'AS')
                    {
                        echo '<div class="alert alert-success alert-top-margin">Fee structure created successfully.</div><br>';
                    }
?>
                    <form class="form-horizontal" name="FeeStructure" action="create_fee_structure.php" method="post">
                        <div class="form-group">
                            <label for="AcademicYearID" class="col-lg-2 control-label">Academic Year</label>
                            <div class="col-lg-3">
                                <!--<input type="text" name="txtAcademicYearID" class="form-control" value="<?php echo $AcademicYearName; ?>" disabled="disabled" />-->
                                <select class="form-control" name="drdAcademicYear" id="AcademicYearID">
    <?php
                                    if (is_array($AcademicYears) && count($AcademicYears) > 0)
                                    {
                                        foreach ($AcademicYears as $AcademicYearID => $AcademicYearDetails)
                                        {
                                            if ($Clean['AcademicYearID'] == 0)
                                            {
                                                if ($AcademicYearDetails['IsCurrentYear'] == 1)
                                                {
                                                    $Clean['AcademicYearID'] = $AcademicYearID;   
                                                }
                                            }
                                            
                                            echo '<option ' . ($Clean['AcademicYearID'] == $AcademicYearID ? 'selected="selected"' : '') . ' value="' . $AcademicYearID . '">' . date('Y', strtotime($AcademicYearDetails['StartDate'])) .' - '. date('Y', strtotime($AcademicYearDetails['EndDate'])) . '</option>';
                                        }
                                    }
    ?>
    								</select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="ClassList" class="col-lg-2 control-label">Class</label>
                            <div class="col-lg-3">
                                <select class="form-control"  name="drdClassID" id="ClassID">
                                    <option  value="0" >Select Class</option>
<?php
                                    foreach ($ClassList as $ClassID => $Class)
                                    {
?>
                                        <option <?php echo ($ClassID == $Clean['ClassID'] ? 'selected="selected"' : ''); ?> value="<?php echo $ClassID; ?>"><?php echo $Class; ?></option>
<?php
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
                    </form>

<?php
                if (($Clean['Process'] == 7 && $HasSearchErrors == false) || $Clean['Process'] == 1)
                {
                    if ($HasErrors == true)
                    {
                        echo $NewRecordValidator->DisplayErrors();
                    }
?>
                    <form class="form-horizontal" name="FeeStructure" action="create_fee_structure.php" method="post">
<?php
                    foreach ($FeeGroupList as $FeeGroupID => $FeeGroup) 
                    {
?>      
                        <div class="row" id="RecordTable">
                            <div class="col-lg-12">
                                <table width="100%" class="table table-striped table-bordered table-hover">
                                    <thead>
                                        <tr><th colspan="4">Fee Structure OF: <?php echo $FeeGroup; ?></th></tr>
                                        <tr>
                                            <th>Check</th>
                                            <th>Fee Head</th>
                                            <th>Amount</th>
                                            <th>Months</th>
                                        </tr>
                                    </thead>
                                    <tbody>
<?php

                            if (is_array($AllFeeHeads) && count($AllFeeHeads) > 0)
                            {
                                $Counter = 0;
                                foreach ($AllFeeHeads as $FeeHeadID => $FeeHeadDetails)
                                {                                              
                                
?>
                                    <tr>
                                        <td>
                                            <label class="checkbox-inline"><input class="custom-radio" type="checkbox" id="<?php echo $FeeGroupID.$FeeHeadID; ?>" name="FeeStructure[<?php echo $FeeGroupID; ?>][<?php echo $FeeHeadID; ?>][FeeHeadSelected]" <?php echo (array_key_exists($FeeGroupID, $Clean['FeeStructureDetails']) && array_key_exists($FeeHeadID, $Clean['FeeStructureDetails'][$FeeGroupID]) ? 'checked="checked"' : ''); ?> value="1" /></label>
                                        </td>

                                        <td><label class="checkbox-inline" for="<?php echo $FeeGroupID.$FeeHeadID; ?>"><?php echo $FeeHeadDetails['FeeHead']; ?></label></td>

                                        <td class="<?php echo isset($FeeHeadErrors[$FeeGroupID][$FeeHeadID]['FeeHeadAmount']) ? ' has-error' : ''; ?>">
<?php
                                        if ($FeeHeadDetails['IsSystemGenerated'])
                                        {
?>
                                            <input type="text" class="form-control" id="FeeAmount<?php echo $FeeGroupID.$FeeHeadID; ?>" disabled="disabled">
<?php
                                        }
                                        else
                                        {                                            
?>
                                            <input type="text" class="form-control" name="FeeStructure[<?php echo $FeeGroupID; ?>][<?php echo $FeeHeadID; ?>][FeeHeadAmount]" id="FeeAmount" value="<?php echo (array_key_exists($FeeGroupID, $Clean['FeeStructureDetails']) && array_key_exists($FeeHeadID, $Clean['FeeStructureDetails'][$FeeGroupID]) && $Clean['FeeStructureDetails'][$FeeGroupID][$FeeHeadID]['FeeHeadAmount'] > 0) ? $Clean['FeeStructureDetails'][$FeeGroupID][$FeeHeadID]['FeeHeadAmount'] : ''; ?>" />
<?php
                                        }
?>
                                        </td>

                                        <td class="<?php echo isset($FeeHeadErrors[$FeeGroupID][$FeeHeadID]['FeeHeadApplicableMonths']) ? ' has-error' : ''; ?>"> 
<?php 
                                        $Counter = 0;
                                        
                                        foreach ($AllMonths as $MonthID => $MonthDetails) 
                                        {
                                            if ($Counter == 6) 
                                            {
                                                echo '<br>';
                                            }   
                                            
                                            $Counter++;                       
?>
                                            <label class="checkbox-inline">
                                                <input class="custom-radio checkbox<?php echo $FeeGroupID; ?><?php echo $FeeHeadID; ?>" type="checkbox" <?php echo (array_key_exists($FeeGroupID, $Clean['FeeStructureDetails']) && array_key_exists($FeeHeadID, $Clean['FeeStructureDetails'][$FeeGroupID]) && array_key_exists($MonthID, $Clean['FeeStructureDetails'][$FeeGroupID][$FeeHeadID]['FeeHeadApplicableMonths']) ? 'checked="checked"' : ''); ?> name="FeeStructure[<?php echo $FeeGroupID; ?>][<?php echo $FeeHeadID; ?>][FeeHeadApplicableMonths][<?php echo $MonthID; ?>]" value="1" onclick="CountCheckMonth('checkbox<?php echo $FeeGroupID; ?><?php echo $FeeHeadID; ?>');" />
                                                <?php echo $MonthDetails['MonthShortName']; ?>
                                            </label>
<?php
                                        }
?>
                                            <label class="checkbox-inline">
                                                <input class="custom-radio" type="checkbox" <?php echo (array_key_exists($FeeGroupID, $Clean['FeeStructureDetails']) && array_key_exists($FeeHeadID, $Clean['FeeStructureDetails'][$FeeGroupID]) && (count($Clean['FeeStructureDetails'][$FeeGroupID][$FeeHeadID]['FeeHeadApplicableMonths']) == $Counter) ? 'checked="checked"' : ''); ?> name="AllFeeStructureMonth[<?php echo $FeeGroupID; ?>][<?php echo $FeeHeadID; ?>][FeeHeadApplicableMonths]" id="checkbox<?php echo $FeeGroupID; ?><?php echo $FeeHeadID; ?>" value="1" onclick="CheckAllMonth('checkbox<?php echo $FeeGroupID; ?><?php echo $FeeHeadID; ?>');" />
                                                All
                                            </label>
                                        </td>
                                    </tr>
<?php
                                }
                            }
?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
<?php 
                    }
?>
                        <div class="form-group">
                            <div class="col-sm-offset-2 col-lg-10">
                                <input type="hidden" name="hdnProcess" value="1" />
                                <input type="hidden" name="hdnAcademicYearID" value="<?php echo $Clean['AcademicYearID']; ?>" />
                                <input type="hidden" name="hdnClassID" value="<?php echo $Clean['ClassID']; ?>" />
                                <button type="submit" class="btn btn-primary">Save</button>
                            </div>
                        </div>
                    </form>
<?php
                }
?>
            </div>
        </div>
        <!-- /#page-wrapper -->
    </div>
    <!-- /#wrapper -->
<?php
require_once('../footer.php');
?>

<script src="../vendor/jquery-ui/jquery-ui.min.js"></script>
<script type="text/javascript">
    
    function CheckAllMonth(ClassVariable)
    {
        if ($('#'+ClassVariable). prop("checked") == true) 
        {
            $('.'+ClassVariable).prop('checked',true);
        }
        else
        {
            $('.'+ClassVariable).prop('checked',false);
        }
    }

    function CountCheckMonth(ClassVariable)
    {
        var Counter = <?php echo count($AllMonths);?>;
        
        if ($('input.'+ClassVariable+':checked').length == Counter) 
        {
            $('#'+ClassVariable).prop('checked',true);
        }
        else
        {
            $('#'+ClassVariable).prop('checked',false);
        }
    }

</script>
</body>
</html>
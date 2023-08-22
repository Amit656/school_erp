<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

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

$UserMenusArray = array();
$UserMenusArray = $LoggedUser->GetUserMenus();

if (!is_array($UserMenusArray) || count($UserMenusArray) <= 0)
{
	//header('location:../logout.php');
	//exit;
}

$HasErrors = false;

$AcademicYearName = '';
$AcademicYearID = AcademicYear::GetCurrentAcademicYear($AcademicYearName);

$AcademicYearDetail = new AcademicYear($AcademicYearID);

$StartDate = $AcademicYearDetail->GetStartDate();
$EndDate = $AcademicYearDetail->GetEndDate();

$AllMonths = array();
$AllMonths = AcademicYearMonth::GetMonthsByFeePriority();

$ClassFeeGroupList = array();
$ClassFeeGroupList = FeeGroup::GetActiveFeeGroups('Class');

$StudentFeeGroupList = array();
$StudentFeeGroupList = FeeGroup::GetActiveFeeGroups('Student');

$AllFeeHeads = array();

$Clean = array();

$Clean['Process'] = 0;

$Clean['FeeStructureID'] = 0;

$Clean['ClassFeeGroupID'] = 0;
$Clean['StudentFeeGroupID'] = 0;

$Clean['FeeHeadIDList'] = array();
$Clean['AcademicYearMonthIDList'] = array();
$Clean['FeeAmountList'] = array();

$FeeStructureDetails = array();
$Errors = array();

$AllFeeStructureDetails = array();

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
		if (isset($_POST['hdnClassFeeGroupID']))
        {
            $Clean['ClassFeeGroupID'] = (int) $_POST['hdnClassFeeGroupID'];
        }

        if (isset($_POST['hdnStudentFeeGroupID']))
        {
            $Clean['StudentFeeGroupID'] = (int) $_POST['hdnStudentFeeGroupID'];
        }

		if (isset($_POST['chkFeeHeadID']) && is_array($_POST['chkFeeHeadID']))
		{
			$Clean['FeeHeadIDList'] =  $_POST['chkFeeHeadID'];
		}

        if (isset($_POST['txtFeeAmount']) && is_array($_POST['txtFeeAmount'])) 
        {
            $Clean['FeeAmountList'] = $_POST['txtFeeAmount'];
        }

        if (isset($_POST['chkAcademicYearMonthID']) && is_array($_POST['chkAcademicYearMonthID'])) 
        {
            $Clean['AcademicYearMonthIDList'] = $_POST['chkAcademicYearMonthID'];
        }

        $AllFeeHeads = FeeHead::GetActiveFeeHeads();

        $RecordValidator = new Validator();
        $NewRecordValidator = new Validator();

        if (count($Clean['FeeHeadIDList']) > 0) 
        {
            foreach ($Clean['FeeHeadIDList'] as $FeeHeadID) 
            {
                $Clean['FeeHeadIDList'][$FeeHeadID] = 1;
                if (isset($Clean['FeeAmountList'][$FeeHeadID])) 
                {          
                    if (!$RecordValidator->ValidateNumeric($Clean['FeeAmountList'][$FeeHeadID], 'Please enter valid ammount.')) 
                    {
                       $Errors[$FeeHeadID]['FeeAmount'] = 'Please enter valid ammount.';
                    }

                    $FeeStructureDetails[$FeeHeadID]['FeeAmount'] = $Clean['FeeAmountList'][$FeeHeadID];
                }
                
                if (isset($Clean['AcademicYearMonthIDList'][$FeeHeadID])) 
                {
                    $FeeStructureDetails[$FeeHeadID]['AcademicYearMonthID'] = $Clean['AcademicYearMonthIDList'][$FeeHeadID];
                }
                else
                {
                    $Errors[$FeeHeadID]['AcademicYearMonthID'] = 'Errors';
                }

                if (isset($Errors[$FeeHeadID]['AcademicYearMonthID'])) 
                {
                    $NewRecordValidator->AttachTextError('Please select atleast one month for <u><i>' . $AllFeeHeads[$FeeHeadID]['FeeHead'] . '</i></u> fee head.');
                }
            }
        }
        else
        {
            $NewRecordValidator->AttachTextError('Please select atleast one fee head.');
        }

        if ($Clean['ClassFeeGroupID'] <= 0) 
        {
            $Errors['ClassFeeGroupID'] = 'Please select both group.';
        }
        else
        {
            $NewRecordValidator->ValidateInSelect($Clean['ClassFeeGroupID'], $ClassFeeGroupList, 'Unknown error, please try again.');
        }

        if ($Clean['StudentFeeGroupID'] <= 0) 
        {
            $Errors['StudentFeeGroupID'] = 'Please select both group.';
        }
        else
        {
            $NewRecordValidator->ValidateInSelect($Clean['StudentFeeGroupID'], $StudentFeeGroupList, 'Unknown error, please try again.');
        }

        if (isset($Errors['ClassFeeGroupID']) || isset($Errors['StudentFeeGroupID'])) 
        {
            $NewRecordValidator->AttachTextError('Please select both group.');
        }

        if ($RecordValidator->HasNotifications())
        {
            $NewRecordValidator->AttachTextError('There were error in ammounts entered by you, please scroll down to see indivisual errors.');
        }

		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}
		
        if (isset($_POST['hdnFeeStructureID']))
        {

            $Clean['FeeStructureID'] = (int) $_POST['hdnFeeStructureID'];

            try
            {
                $NewFeeStructure = new FeeStructure($Clean['FeeStructureID']);
            }
            catch (ApplicationDBException $e)
            {
                header('location:../error1.php');
                exit;
            }
            catch (Exception $e)
            {
                header('location:../error2.php');
                exit;
            }
        }
        else
        {
            $NewFeeStructure = new FeeStructure();
        }			
		
		$NewFeeStructure->SetAcademicYearID($AcademicYearID);

        $NewFeeStructure->SetClassFeeGroupID($Clean['ClassFeeGroupID']);
        $NewFeeStructure->SetStudentFeeGroupID($Clean['StudentFeeGroupID']);

		$NewFeeStructure->SetIsActive(1);
		$NewFeeStructure->SetCreateUserID($LoggedUser->GetUserID());

        $NewFeeStructure->SetFeeStructureDetails($FeeStructureDetails);

		if (!$NewFeeStructure->Save())
		{
			$NewRecordValidator->AttachTextError(ProcessErrors($NewFeeStructure->GetLastErrorCode()));
			$HasErrors = true;

			break;
		}
		
		header('location:create_fee_structure.php?Mode=AS&Process=7&AcademicYearID='.$AcademicYearID.'&ClassFeeGroupID='.$Clean['ClassFeeGroupID'].'&StudentFeeGroupID='.$Clean['StudentFeeGroupID'].'');
		exit;
	break;

    case 7:
        if (isset($_POST['drdClassFeeGroupID']))
        {
            $Clean['ClassFeeGroupID'] = (int) $_POST['drdClassFeeGroupID'];
        }
        else if (isset($_GET['ClassFeeGroupID'])) 
        {
            $Clean['ClassFeeGroupID'] = (int) $_GET['ClassFeeGroupID'];
        }

        if (isset($_POST['drdStudentFeeGroupID']))
        {
            $Clean['StudentFeeGroupID'] = (int) $_POST['drdStudentFeeGroupID'];
        }
        else if (isset($_GET['StudentFeeGroupID'])) 
        {
            $Clean['StudentFeeGroupID'] = (int) $_GET['StudentFeeGroupID'];
        }

        $NewRecordValidator = new Validator();

        if (!$NewRecordValidator->ValidateInSelect($Clean['ClassFeeGroupID'], $ClassFeeGroupList, 'Please select a class wise group.') || !$NewRecordValidator->ValidateInSelect($Clean['StudentFeeGroupID'], $StudentFeeGroupList, 'Please select a student wise group.')) 
        {
            $HasErrors = true;
            break;
        }

        $AllFeeHeads = FeeHead::GetActiveFeeHeads();

        if (FeeStructure::FeeStructureExists($AcademicYearID, $Clean['ClassFeeGroupID'], $Clean['StudentFeeGroupID'], $Clean['FeeStructureID']))
        {
            try
            {
                $CurrentFeeStructure = new FeeStructure($Clean['FeeStructureID']);
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

            $AllFeeStructureDetails = $CurrentFeeStructure->GetFeeStructureDetails();
            
            foreach ($AllFeeStructureDetails as $FeeHeadID => $SructureDetails) 
            {
                $Clean['FeeHeadIDList'][$FeeHeadID] = 1;
                    
                foreach ($SructureDetails as $Key => $Details) 
                {
                    $Clean['AcademicYearMonthIDList'][$FeeHeadID][$Key] = $Details['AcademicYearMonthID'];
                }

                $Clean['FeeAmountList'][$FeeHeadID] = $Details['FeeAmount'];
            }
        }
        var_dump($Clean['AcademicYearMonthIDList']);
        break;
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
             <form class="form-horizontal" name="FeeStructure" action="create_fee_structure.php" method="post">
            	<div class="panel panel-default">
                    <div class="panel-heading">
                        <strong>Enter Fee Structure Details</strong>
                    </div>
                    <div class="panel-body">
<?php
						if ($HasErrors == true)
						{
							echo $NewRecordValidator->DisplayErrors();
						}
?>                    
                    	<div class="form-group">
                            <label for="AcademicYearID" class="col-lg-2 control-label">Academic Year</label>
                            <div class="col-lg-4">
                                <input type="text" name="txtAcademicYearID" class="form-control" value="<?php echo $AcademicYearName; ?>" disabled="disabled" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="ClassFeeGroupID" class="col-lg-2 control-label">Class Wise Group</label>
                            <div class="col-lg-4<?php echo isset($Errors['ClassFeeGroupID']) ? ' has-error' : ''; ?>">
                                <select class="form-control" name="drdClassFeeGroupID" id="ClassFeeGroupID" >
                                    <option value="0">Select Class Group</option>
<?php
                                foreach ($ClassFeeGroupList as $ClassFeeGroupID => $ClassFeeGroup) 
                                {
                                    echo '<option ' . ($Clean['ClassFeeGroupID'] == $ClassFeeGroupID ? 'selected="selected"' : '') . ' value="' . $ClassFeeGroupID . '">' . $ClassFeeGroup . '</option>';
                                }
?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="StudentFeeGroupID" class="col-lg-2 control-label">Student Wise Group</label>
                            <div class="col-lg-4<?php echo isset($Errors['StudentFeeGroupID']) ? ' has-error' : ''; ?>">
                                <select class="form-control" name="drdStudentFeeGroupID" id="StudentFeeGroupID">
                                    <option value="0">Select Student Group</option>
<?php
                                foreach ($StudentFeeGroupList as $StudentFeeGroupID => $StudentFeeGroup) 
                                {
                                    echo '<option ' . ($Clean['StudentFeeGroupID'] == $StudentFeeGroupID ? 'selected="selected"' : '') . ' value="' . $StudentFeeGroupID . '">' . $StudentFeeGroup . '</option>';
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
if (is_array($AllFeeHeads) && count($AllFeeHeads) > 0) 
{
?>
                    <form class="form-horizontal" name="FeeStructure" action="create_fee_structure.php" method="post">
                        <div class="row" id="RecordTable">
                            <div class="col-lg-12">
                                <table width="100%" class="table table-striped table-bordered table-hover">
                                    <thead>
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
                                        <label class="checkbox-inline"><input class="custom-radio" type="checkbox" id="<?php echo $FeeHeadID; ?>" name="chkFeeHeadID[]" <?php echo (isset($Clean['FeeHeadIDList'][$FeeHeadID]) ? 'checked="checked"' : ''); ?> value="<?php echo $FeeHeadID; ?>" />
                                        </label>
                                    </td>

                                    <td><?php echo $FeeHeadDetails['FeeHead']; ?></td>
                                    <td class="<?php echo isset($Errors[$FeeHeadID]['FeeAmount']) ? ' has-error' : ''; ?>">
                                        <?php echo (($FeeHeadDetails['IsSystemGenerated']) ? '<input type="text" class="form-control" id="FeeAmount" readonly>' : '<input type="text" class="form-control" name="txtFeeAmount[' . $FeeHeadID . ']" id="FeeAmount" value="' . ((array_key_exists($FeeHeadID, $Clean['FeeAmountList'])) ? $Clean['FeeAmountList'][$FeeHeadID] : '') . '"  >' ); ?></td>

                                    <td class="<?php echo isset($Errors[$FeeHeadID]['AcademicYearMonthID']) ? ' has-error' : ''; ?>"> 
<?php 
                                    $Counter = 0;
                                    foreach ($AllMonths as $MonthId => $MonthDetails) 
                                    {
                                        if ($Counter == 6) 
                                        {
                                            echo '<br>';
                                        }

                                        $Counter++;
                                        //var_dump($Clean['AcademicYearMonthIDList'][$FeeHeadID]);
?>
                                        <label class="checkbox-inline"><input class="custom-radio" type="checkbox" id="<?php echo $MonthId; ?>" name="chkAcademicYearMonthID[<?php echo $FeeHeadID; ?>][]" <?php echo (isset($Clean['AcademicYearMonthIDList'][$FeeHeadID][$MonthId])) ? 'checked="checked"' : ''; ?> value="<?php echo $MonthId; ?>" />
                                        <?php echo $MonthDetails['MonthShortName']; ?>
                                        </label>
<?php                                                
                                    }
?>
                                    </td>
                                </tr>
<?php
                                }
                            }
                            else
                            {
?>
                                <tr>
                                    <td colspan="4">No Records</td>
                                </tr>
<?php
                            }
?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="form-group">
                        <div class="col-sm-offset-2 col-lg-10">
                            <input type="hidden" name="hdnProcess" value="1" />
                            <input type="hidden" name="hdnClassFeeGroupID" value="<?php echo $Clean['ClassFeeGroupID']; ?>" />
                            <input type="hidden" name="hdnStudentFeeGroupID" value="<?php echo $Clean['StudentFeeGroupID']; ?>" />
                            <input type="hidden" name="hdnFeeStructureID" value="<?php echo isset($Clean['FeeStructureID']) ? $Clean['FeeStructureID'] : 0; ?>" />
                            <button type="submit" class="btn btn-primary">Save</button>
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
?>
</body>
</html>
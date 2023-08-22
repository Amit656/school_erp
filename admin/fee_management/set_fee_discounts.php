<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once("../../classes/school_administration/class.academic_years.php");
require_once("../../classes/school_administration/class.academic_year_months.php");

require_once("../../classes/school_administration/class.classes.php");
require_once('../../classes/school_administration/class.class_sections.php');
require_once('../../classes/school_administration/class.section_master.php');

require_once('../../classes/school_administration/class.students.php');
require_once('../../classes/school_administration/class.student_details.php');

require_once("../../classes/fee_management/class.fee_heads.php");
require_once("../../classes/fee_management/class.fee_groups.php");
require_once("../../classes/fee_management/class.fee_structure.php");
require_once("../../classes/fee_management/class.fee_discounts.php");

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

if ($LoggedUser->HasPermissionForTask(TASK_ADD_FEE_DISCOUNT) !== true)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}

$HasErrors = false;

$AcademicYears =  array();
$AcademicYears = AcademicYear::GetAllAcademicYears();

$AcademicYearName = '';
$AcademicYearID = AcademicYear::GetCurrentAcademicYear($AcademicYearName);

$ClassList =  array();
$ClassList = AddedClass::GetActiveClasses();

$AllSectionMasters = array();
$AllSectionMasters = SectionMaster::GetActiveSectionMasters();

$ClassSectionsList =  array();
$StudentsList = array();

$AllMonths = array();
$AllMonths = AcademicYearMonth::GetMonthsByFeePriority();

$FeeGroupList = array();
$FeeGroupList = FeeGroup::GetActiveFeeGroups();

$AllFeeHeads = array();
$AllFeeHeads = FeeHead::GetActiveFeeHeads();

$Clean = array();

$Clean['Process'] = 0;

$Clean['AcademicYearID'] = 0;
$Clean['FeeGroupID'] = 0;

$Clean['ClassID'] = 0;
$Clean['ClassSectionID'] = 0;
$Clean['StudentID'] = 0;

$Clean['FeeStructureDetails'] = array();

$Clean['FeeDiscountType'] = 'Group';
$Clean['DiscountTypeList'] = array('Absolute' => 'Absolute');
$Clean['DiscountValueList'] = array();
$Clean['AfterDiscountList'] = array();
$Clean['FeeStructureDetailIDList'] = array();
$Clean['FeeHeadIDList'] = array();
$Clean['FeeAmountList'] = array();

$DiscountDetails = array();
$Errors = array();

$Clean['FeeStructureDetails'] = array();
$FeeGroupClasses = array();
$AllotedDiscount = array();

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
		
		if (isset($_POST['hdnFeeDiscountType']))
        {
            $Clean['FeeDiscountType'] = strip_tags(trim($_POST['hdnFeeDiscountType']));
        }

        if ($Clean['FeeDiscountType'] == 'Group') 
        {
            if (isset($_POST['hdnFeeGroupID']))
            {
                $Clean['FeeGroupID'] = (int) $_POST['hdnFeeGroupID'];
            }
            if (isset($_POST['hdnActivatedClassID'])) 
            {
                $Clean['ClassID'] = (int) $_POST['hdnActivatedClassID'];
            }

            if (isset($_POST['chkFeeHeadID']) && is_array($_POST['chkFeeHeadID']))
            {
                $Clean['FeeHeadIDList'] = $_POST['chkFeeHeadID'];
            }

            if (isset($_POST['txtFeeAmount']) && is_array($_POST['txtFeeAmount'])) 
            {
                $Clean['FeeAmountList'] = $_POST['txtFeeAmount'];
            }

            if (isset($_POST['txtDiscountValue']) && is_array($_POST['txtDiscountValue'])) 
            {
                $Clean['DiscountValueList'] = $_POST['txtDiscountValue'];
            }

            if (isset($_POST['optDiscountTypeList']) && is_array($_POST['optDiscountTypeList'])) 
            {
                $Clean['DiscountTypeList'] = $_POST['optDiscountTypeList'];
            }

            if (isset($_POST['chkFeeStructureDetailID']) && is_array($_POST['chkFeeStructureDetailID']))
            {
                $Clean['FeeStructureDetailIDList'] = $_POST['chkFeeStructureDetailID'];
            }

            $RecordValidator = new Validator();
            $NewRecordValidator = new Validator();
            
            $StudentObject = new Student($Clean['StudentID']);
            $FeeGroupID = $StudentObject->GetStudentWiseFeeGroupID();

            $AllotedDiscount = array();
            $AllotedDiscount = FeeDiscount::GetFeeStructureDiscount($FeeGroupID, $Clean['ClassID'], $Clean['StudentID']);
            
            if (count($Clean['FeeHeadIDList']) > 0) 
            {
                foreach ($Clean['FeeHeadIDList'] as $FeeHeadID) 
                {
                    if ($Clean['DiscountValueList'][$FeeHeadID] > 0) 
                    {          
                        if (!$RecordValidator->ValidateNumeric($Clean['DiscountValueList'][$FeeHeadID], 'Please enter valid discount value.')) 
                        {
                           $Errors[$FeeHeadID]['DiscountValue'] = 'Please enter valid discount value.';
                        }

                        $DiscountDetails[$FeeHeadID]['DiscountValue'] = $Clean['DiscountValueList'][$FeeHeadID];

                        if (isset($Clean['FeeStructureDetailIDList'][$FeeHeadID])) 
                        {
                            $DiscountDetails[$FeeHeadID]['FeeStructureDetailID'] = $Clean['FeeStructureDetailIDList'][$FeeHeadID];
                        }
                        else
                        {
                            $Errors[$FeeHeadID]['FeeStructureDetailID'] = 'Errors';
                        }

                        if (isset($Clean['DiscountTypeList'][$FeeHeadID])) 
                        {
                            $DiscountDetails[$FeeHeadID]['DiscountType'] = $Clean['DiscountTypeList'][$FeeHeadID];
                        }
                    }
                    elseif (array_key_exists($FeeHeadID, $AllotedDiscount))
                    {
                        $DiscountDetails[$FeeHeadID]['RemoveDiscount'] = $Clean['FeeStructureDetailIDList'][$FeeHeadID];
                    }
                    
                    if (isset($Errors[$FeeHeadID]['FeeStructureDetailID'])) 
                    {
                        $NewRecordValidator->AttachTextError('Please select atleast one month for discount of  <u><i>'.$AllFeeHeads[$FeeHeadID]['FeeHead'].'</i></u> fee head.');
                    }
                }
            }        
        }
        else if ($Clean['FeeDiscountType'] == 'Student') 
        {
            
            if (isset($_POST['hdnClassID'])) 
            {
                $Clean['ClassID'] = (int) $_POST['hdnClassID'];
            }

            if (isset($_POST['hdnClassSectionID'])) 
            {
                $Clean['ClassSectionID'] = (int) $_POST['hdnClassSectionID'];
            }

            if (isset($_POST['hdnStudentID'])) 
            {
                $Clean['StudentID'] = (int) $_POST['hdnStudentID'];
            }

            if (isset($_POST['chkFeeHeadID']) && is_array($_POST['chkFeeHeadID']))
            {
                $Clean['FeeHeadIDList'] =  $_POST['chkFeeHeadID'];
            }

            if (isset($_POST['txtFeeAmount']) && is_array($_POST['txtFeeAmount'])) 
            {
                $Clean['FeeAmountList'] = $_POST['txtFeeAmount'];
            }

            if (isset($_POST['txtDiscountValue']) && is_array($_POST['txtDiscountValue'])) 
            {
                $Clean['DiscountValueList'] = $_POST['txtDiscountValue'];
            }

            if (isset($_POST['optDiscountTypeList']) && is_array($_POST['optDiscountTypeList'])) 
            {
                $Clean['DiscountTypeList'] = $_POST['optDiscountTypeList'];
            }

            if (isset($_POST['chkFeeStructureDetailID']) && is_array($_POST['chkFeeStructureDetailID']))
            {
                $Clean['FeeStructureDetailIDList'] = $_POST['chkFeeStructureDetailID'];
            }

            $ClassSectionsList = ClassSections::GetClassSections($Clean['ClassID']);

            if ($Clean['ClassSectionID'] > 0) 
            {
                $StudentsList = StudentDetail::GetStudentsByClassSectionID($Clean['ClassSectionID']);
            }
            
            $RecordValidator = new Validator();
            $NewRecordValidator = new Validator();
            
            $StudentObject = new Student($Clean['StudentID']);
            $FeeGroupID = $StudentObject->GetStudentWiseFeeGroupID();

            $AllotedDiscount = array();
            $AllotedDiscount = FeeDiscount::GetFeeStructureDiscount($FeeGroupID, $Clean['ClassID'], $Clean['StudentID']);

            if (count($Clean['FeeHeadIDList']) > 0) 
            {
                foreach ($Clean['FeeHeadIDList'] as $FeeHeadID) 
                {
                    if ($Clean['DiscountValueList'][$FeeHeadID] > 0) 
                    {          
                        if (!$RecordValidator->ValidateNumeric($Clean['DiscountValueList'][$FeeHeadID], 'Please enter valid discount value.')) 
                        {
                           $Errors[$FeeHeadID]['DiscountValue'] = 'Please enter valid discount value.';
                        }

                        $DiscountDetails[$FeeHeadID]['DiscountValue'] = $Clean['DiscountValueList'][$FeeHeadID];

                        if (isset($Clean['FeeStructureDetailIDList'][$FeeHeadID])) 
                        {
                            $DiscountDetails[$FeeHeadID]['FeeStructureDetailID'] = $Clean['FeeStructureDetailIDList'][$FeeHeadID];
                        }
                        else
                        {
                            $Errors[$FeeHeadID]['FeeStructureDetailID'] = 'Errors';
                        }

                        if (isset($Clean['DiscountTypeList'][$FeeHeadID])) 
                        {
                            $DiscountDetails[$FeeHeadID]['DiscountType'] = $Clean['DiscountTypeList'][$FeeHeadID];
                        }
                    }
                    elseif (array_key_exists($FeeHeadID, $AllotedDiscount))
                    {
                        $DiscountDetails[$FeeHeadID]['RemoveDiscount'] = $Clean['FeeStructureDetailIDList'][$FeeHeadID];
                    }
                    
                    if (isset($Errors[$FeeHeadID]['FeeStructureDetailID'])) 
                    {
                        $NewRecordValidator->AttachTextError('Please select atleast one month for discount of  <u><i>'.$AllFeeHeads[$FeeHeadID]['FeeHead'].'</i></u> fee head.');
                    }
                }
            }
        }

        if ($RecordValidator->HasNotifications())
        {
            $NewRecordValidator->AttachTextError('There were error in discount value entered by you, please scroll down to see indivisual errors.');
        }

        // if (count($DiscountDetails) <= 0)
        // {
        //     $NewRecordValidator->AttachTextError('Please set discount for atleast one fee head.');
        // }

        if ($Clean['StudentID'] != 0) 
        {
            // $Clean['FeeStructureDetails'] = FeeStructure::FeeStructureByStudent($Clean['StudentID']);
            $StudentObject = new Student($Clean['StudentID']);
            $Clean['FeeGroupID'] = $StudentObject->GetStudentWiseFeeGroupID();        
        }

		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;

            FeeStructure::FeeStructureExists($Clean['AcademicYearID'], $Clean['ClassID'], $Clean['FeeGroupID'], $Clean['FeeStructureDetails']);
            
            $AllotedDiscount = FeeDiscount::GetFeeStructureDiscount($Clean['FeeGroupID'], $Clean['ClassID'], $Clean['StudentID']);
            $Clean['DiscountTypeList'] = array();

            foreach ($AllotedDiscount as $FeeHeadID => $DiscountDetails) 
            {                
                foreach ($DiscountDetails as $FeeStructureDetailID => $Details) 
                {
                    $Clean['FeeStructureDetailIDList'][$FeeHeadID][$FeeStructureDetailID] = 1;
                    $Clean['DiscountTypeList'][$FeeHeadID][$Details['DiscountType']] = $Details['DiscountType'];
                    $Clean['DiscountValueList'][$FeeHeadID] = $Details['DiscountValue'];   
                }
            }
			break;
		}
		
        $NewFeeDiscount = new FeeDiscount();		
				
        $NewFeeDiscount->SetFeeDiscountType($Clean['FeeDiscountType']);
        $NewFeeDiscount->SetFeeGroupID($Clean['FeeGroupID']);        
        $NewFeeDiscount->SetStudentID($Clean['StudentID']);
		$NewFeeDiscount->SetDiscountDetails($DiscountDetails);

		if (!$NewFeeDiscount->SetFeeStructureDiscount())
		{
			$NewRecordValidator->AttachTextError(ProcessErrors($NewFeeDiscount->GetLastErrorCode()));
			$HasErrors = true;
			break;
		}
		
		header('location:set_fee_discounts.php?Mode=AS&Process=7&AcademicYearID='.$Clean['AcademicYearID'].'&FeeGroupID='.$Clean['FeeGroupID'].'&FeeDiscountType='.$Clean['FeeDiscountType'].'&StudentID='.$Clean['StudentID'].'&ClassID='.$Clean['ClassID'].'&ClassSectionID='.$Clean['ClassSectionID'].'');
		exit;
	break;

    case 7:
        $NewRecordValidator = new Validator();
        
        if (isset($_POST['drdAcademicYear'])) 
		{
			$Clean['AcademicYearID'] = (int) $_POST['drdAcademicYear'];
		}
		else if (isset($_GET['AcademicYearID'])) 
		{
			$Clean['AcademicYearID'] = (int) $_GET['AcademicYearID'];
		}
		
        if (isset($_POST['optFeeDiscountType']))
        {
            $Clean['FeeDiscountType'] = strip_tags(trim($_POST['optFeeDiscountType']));
        }
        else if (isset($_GET['FeeDiscountType'])) 
        {
            $Clean['FeeDiscountType'] = strip_tags(trim($_GET['FeeDiscountType']));
        }

        if ($Clean['FeeDiscountType'] == 'Group') 
        {
            if (isset($_POST['drdFeeGroupID']))
            {
                $Clean['FeeGroupID'] = (int) $_POST['drdFeeGroupID'];
            }
            else if (isset($_GET['FeeGroupID'])) 
            {
                $Clean['FeeGroupID'] = (int) $_GET['FeeGroupID'];
            }

            if (!$NewRecordValidator->ValidateInSelect($Clean['FeeGroupID'], $FeeGroupList, 'Please select a class wise group.')) 
            {
                $HasErrors = true;
                break;
            }
            
            $FeeGroupClasses = FeeStructure::GetApplicableClassesForFeeGroup($Clean['FeeGroupID']);

            $Clean['ClassID'] = key($FeeGroupClasses);

            if (isset($_GET['ClassID'])) 
            {
                $Clean['ClassID'] = (int) $_GET['ClassID'];
            }

            if (!FeeStructure::FeeStructureExists($Clean['AcademicYearID'], $Clean['ClassID'], $Clean['FeeGroupID'], $Clean['FeeStructureDetails']))
            {
                $NewRecordValidator->AttachTextError('No fee structure is assigned for this groups! Please assign first.');
            }

            if ($NewRecordValidator->HasNotifications())
            {
                $HasErrors = true;
                break;
            }
        }
        else if ($Clean['FeeDiscountType'] == 'Student') 
        {
            if (isset($_POST['drdClassID']))
            {
                $Clean['ClassID'] = (int) $_POST['drdClassID'];
            }
            else if (isset($_GET['ClassID'])) 
            {
                $Clean['ClassID'] = (int) $_GET['ClassID'];
            }

            if (isset($_POST['drdClassSectionID']))
            {
                $Clean['ClassSectionID'] = (int) $_POST['drdClassSectionID'];
            }
            else if (isset($_GET['ClassSectionID'])) 
            {
                $Clean['ClassSectionID'] = (int) $_GET['ClassSectionID'];
            }

            $ClassSectionsList = ClassSections::GetClassSections($Clean['ClassID']);

            if ($Clean['ClassSectionID'] > 0) 
            {
                $StudentsList = StudentDetail::GetStudentsByClassSectionID($Clean['ClassSectionID'], 'Active', $Clean['AcademicYearID']);
            }
                
            if (isset($_POST['drdStudentID']))
            {
                $Clean['StudentID'] = (int) $_POST['drdStudentID'];
            }
            else if (isset($_GET['StudentID'])) 
            {
                $Clean['StudentID'] = (int) $_GET['StudentID'];
            }

            if (!$NewRecordValidator->ValidateInSelect($Clean['StudentID'], $StudentsList, 'Please select a valid student.')) 
            {
                $HasErrors = true;
                break;
            }

            $StudentObject = new Student($Clean['StudentID']);
            $FeeGroupID = $StudentObject->GetStudentWiseFeeGroupID();
            
            if (!FeeStructure::FeeStructureExists($Clean['AcademicYearID'], $Clean['ClassID'], $FeeGroupID, $Clean['FeeStructureDetails'], $Clean['StudentID']))
            {
                $NewRecordValidator->AttachTextError('No fee structure is assigned for this student! Please assign first.');
                $HasErrors = true;
                break;
            }
        }
        else
        {
            header('location:../error.php');
            exit;
        }

        $AllotedDiscount = FeeDiscount::GetFeeStructureDiscount($FeeGroupID, $Clean['ClassID'], $Clean['StudentID']);
        $Clean['DiscountTypeList'] = array();

        foreach ($AllotedDiscount as $FeeHeadID => $DiscountDetails) 
        {                
            foreach ($DiscountDetails as $FeeStructureDetailID => $Details) 
            {
                $Clean['FeeStructureDetailIDList'][$FeeHeadID][$FeeStructureDetailID] = 1;
                $Clean['DiscountTypeList'][$FeeHeadID][$Details['DiscountType']] = $Details['DiscountType'];
                $Clean['DiscountValueList'][$FeeHeadID] = $Details['DiscountValue'];   
            }
        }

        if ($NewRecordValidator->HasNotifications())
        {
            $HasErrors = true;
            break;
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
<title>Set Fee Discount</title>
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
                    <h1 class="page-header">Set Fee Discount</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="AddTimingParts" action="set_fee_discounts.php" method="post">
            	<div class="panel panel-default">
                    <div class="panel-heading">
                        Enter Fee Discount Details
                    </div>
                    <div class="panel-body">
<?php
                            if ($HasErrors == true)
                            {
                                echo $NewRecordValidator->DisplayErrors();
                            }
                            else if ($LandingPageMode == 'AS')
                            {
                                echo '<div class="alert alert-success alert-top-margin">Discount set successfully.</div><br>';
                            }
?>                  
                    	<div class="form-group">
                            <label for="AcademicYearID" class="col-lg-2 control-label">Academic Year</label>
                            <div class="col-lg-4">
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
                            <label for="FeeDiscountType" class="col-lg-2 control-label"></label>
                            <div class="col-lg-4">
                                <label class="col-sm-6"><input class="custom-radio" type="radio"  name="optFeeDiscountType" value="Group" <?php echo ($Clean['FeeDiscountType'] == 'Group' ? 'checked="checked"' : ''); ?> >&nbsp;&nbsp;Group Wise</label>
                                <label class="col-sm-6"><input class="custom-radio" type="radio"  name="optFeeDiscountType" value="Student" <?php echo ($Clean['FeeDiscountType'] == 'Student' ? 'checked="checked"' : ''); ?> >&nbsp;&nbsp;Student Wise</label>
                            </div>
                        </div>
                        <div id="Group" class="FeeDiscountType">
                            <div class="form-group">
                                <label for="FeeGroupID" class="col-lg-2 control-label">Fee Group</label>
                                <div class="col-lg-4<?php echo isset($Errors['FeeGroupID']) ? ' has-error' : ''; ?>">
                                    <select class="form-control" name="drdFeeGroupID" id="FeeGroupID" >
                                        <option value="0">Select Fee Group</option>
        <?php
                                    foreach ($FeeGroupList as $FeeGroupID => $FeeGroup) 
                                    {
                                        echo '<option ' . ($Clean['FeeGroupID'] == $FeeGroupID ? 'selected="selected"' : '') . ' value="' . $FeeGroupID . '">' . $FeeGroup . '</option>';
                                    }
        ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div id="Student" class="FeeDiscountType" style="display: none;">
                            <div class="form-group">
                                <label for="ClassList" class="col-lg-2 control-label">Class List</label>
                                <div class="col-lg-3">
                                    <select class="form-control"  name="drdClassID" id="ClassID">
                                        <option  value="0" >Select Class</option>
    <?php
                                        foreach ($ClassList as $ClassID => $ClassName)
                                        {
    ?>
                                            <option <?php echo ($ClassID == $Clean['ClassID'] ? 'selected="selected"' : ''); ?> value="<?php echo $ClassID; ?>"><?php echo $ClassName; ?></option>
    <?php
                                        }
    ?>
                                    </select>
                                </div>
                                <label for="ClassSectionID" class="col-lg-2 control-label">Section List</label>
                                <div class="col-lg-3">
                                    <select class="form-control" name="drdClassSectionID" id="ClassSectionID">
                                        <option value="0">Select Section</option>
    <?php
                                            if (is_array($ClassSectionsList) && count($ClassSectionsList) > 0)
                                            {
                                                foreach ($ClassSectionsList as $ClassSectionID => $SectionName) 
                                                {
                                                    echo '<option ' . ($Clean['ClassSectionID'] == $ClassSectionID ? 'selected="selected"' : '') . ' value="' . $ClassSectionID . '">' . $SectionName . '</option>' ;
                                                }
                                            }
    ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="StudentID" class="col-lg-2 control-label">Student</label>
                                <div class="col-lg-8">
                                    <select class="form-control" name="drdStudentID" id="StudentID">
                                        <option value="0">Select Student</option>
    <?php
                                            if (is_array($StudentsList) && count($StudentsList) > 0)
                                            {
                                                foreach ($StudentsList as $StudentID=>$StudentDetails)
                                                {
                                                    echo '<option ' . ($Clean['StudentID'] == $StudentID ? 'selected="selected"' : '') . ' value="' . $StudentID . '">' . $StudentDetails['FirstName'] . ' ' . $StudentDetails['LastName'] . '(' . $StudentDetails['RollNumber'] . ')</option>'; 
                                                }
                                            }
    ?>
                                    </select>
                                </div>                                
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

if (is_array($Clean['FeeStructureDetails']) && count($Clean['FeeStructureDetails']) > 0) 
{
?>
                    <form class="form-horizontal" name="AddTimingParts" id="RecordForm" action="set_fee_discounts.php" method="post">
                        <div class="row">
                            <div>
                                <label>Classes:</label>
<?php
                            foreach ($FeeGroupClasses as $ClassID => $ClassName) 
                            {
                                echo '<a class="btn btn-sm '.(($Clean['ClassID'] == $ClassID) ? "btn-success":"btn-primary").' classesButton" style="margin-left:10px;" id="'. $ClassID .'" onClick="return GetNextClassFeeStructureDiscount('.$Clean['FeeGroupID'].','.$ClassID.');">'. $ClassName .'</a>';
                            }
?>                                
                            <input type="hidden" name="hdnActivatedClassID" id="ActivatedClassID" value="<?php echo $Clean['ClassID']; ?>" />
                            </div>
                        </div>
                        <div class="row" id="RecordTable">
                            <div class="col-lg-12"  id="DiscountStructure">
                                <table width="100%" class="table table-striped table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th>Fee Head</th>
                                            <th>Amount</th>
                                            <th>Discount Value</th>
                                            <th>Discount Type</th>
                                            <th>After Discount</th>
                                            <th>Months</th>
                                        </tr>
                                    </thead>
                                    <tbody>

<?php
                            if (is_array($Clean['FeeStructureDetails']) && count($Clean['FeeStructureDetails']) > 0)
                            {
                                $Counter = 0;

                                foreach ($Clean['FeeStructureDetails'] as $FeeGroupID => $FeeStructureDetails)
                                {
                                    foreach ($FeeStructureDetails as $FeeHeadID => $StructureDetails)
                                    {
                                        echo '<tr>';
                                        echo '<label class="checkbox-inline"><input class="custom-radio discount-type" type="hidden" id="' . $FeeHeadID . '" name="chkFeeHeadID[]" value="' . $FeeHeadID . '" />
                                            </label>';
                                        echo '<td>' . $AllFeeHeads[$FeeHeadID]['FeeHead'] . '</td>';

?>
                                        <td class="col-lg-2 <?php echo isset($Errors[$FeeHeadID]['FeeAmount']) ? ' has-error' : ''; ?>">
                                            <?php echo (($AllFeeHeads[$FeeHeadID]['IsSystemGenerated']) ? '<input type="text" class="form-control" id="FeeAmount' . $FeeHeadID . '" value="' . $StructureDetails['FeeHeadAmount'] . '"  readonly>' : '<input type="text" class="form-control" name="txtFeeAmount[' . $FeeHeadID . ']" id="FeeAmount' . $FeeHeadID . '" value="' . $StructureDetails['FeeHeadAmount'] . '"  readonly>' ); ?>      
                                        </td>
                                        <td class="col-lg-2 <?php echo isset($Errors[$FeeHeadID]['DiscountValue']) ? ' has-error' : ''; ?>" id="DiscountValueTD<?php echo $FeeHeadID; ?>">
                                            <?php echo '<input type="text" class="form-control" name="txtDiscountValue[' . $FeeHeadID . ']" id="DiscountValue' . $FeeHeadID . '" value="' . ((array_key_exists($FeeHeadID, $Clean['DiscountValueList'])) ? (($Clean['DiscountValueList'][$FeeHeadID] > 0) ? $Clean['DiscountValueList'][$FeeHeadID] : '') : '') . '" onfocusout="return CalculateDiscount(' . $FeeHeadID . ');" >'; ?>      
                                        </td>
                                        <td>
                                            <label><input class="custom-radio" type="radio"  name="optDiscountTypeList[<?php echo $FeeHeadID; ?>]" value="Absolute" <?php echo (!empty($Clean['DiscountTypeList'][$FeeHeadID]) && array_key_exists('Absolute', $Clean['DiscountTypeList'][$FeeHeadID]) || !isset($Clean['DiscountTypeList'][$FeeHeadID])? 'checked="checked"' : ''); ?> onChange="return CalculateDiscount(<?php echo $FeeHeadID;?>);" >&nbsp;&nbsp;<i class="fa fa-inr"></i> &nbsp;&nbsp;</label>
                                            <label>
                                                <input class="custom-radio" type="radio" name="optDiscountTypeList[<?php echo $FeeHeadID; ?>]" value="Percentage" <?php echo (!empty($Clean['DiscountTypeList'][$FeeHeadID]) && array_key_exists('Percentage', $Clean['DiscountTypeList'][$FeeHeadID]) ? 'checked="checked"' : ''); ?> onChange="return CalculateDiscount(<?php echo $FeeHeadID;?>);" >&nbsp;&nbsp;%
                                            </label>
                                        </td>
                                       <td class="col-lg-2 <?php echo isset($Errors[$FeeHeadID]['AfterDiscount']) ? ' has-error' : ''; ?>">
                                            <?php echo '<input type="text" class="form-control" name="txtAfterDiscount[' . $FeeHeadID . ']" id="AfterDiscount' . $FeeHeadID . '" value="' . ((array_key_exists($FeeHeadID, $Clean['AfterDiscountList'])) ? $Clean['AfterDiscountList'][$FeeHeadID] : '') . '" readonly >'; ?>
                                        </td>
                                        <td class="<?php echo isset($Errors[$FeeHeadID]['FeeStructureDetailID']) ? ' has-error' : ''; ?>"> 
                                      
<?php
                                        $Counter = 0;
                                        foreach ($StructureDetails['FeeHeadApplicableMonths'] as $AcademicYearMonthID => $FeeStructureDetailID) 
                                        {
                                            echo (!empty($AllMonths) ? (array_key_exists($AcademicYearMonthID, $AllMonths) ? '<label class="checkbox-inline"><input class="custom-radio checkbox'. $FeeGroupID . $FeeHeadID .'" type="checkbox" id="' . $FeeStructureDetailID . '" name="chkFeeStructureDetailID[' . $FeeHeadID . '][]" ' . (!empty($Clean['FeeStructureDetailIDList'][$FeeHeadID]) ? (array_key_exists($FeeStructureDetailID, $Clean['FeeStructureDetailIDList'][$FeeHeadID])  && ($Clean['DiscountValueList'][$FeeHeadID] > 0) ? 'checked="checked"' : '') : '') . ' value="' . $FeeStructureDetailID . '" onclick="CountCheckMonth('.$FeeGroupID. $FeeHeadID. ');" />
                                            ' . $AllMonths[$AcademicYearMonthID]['MonthShortName'] . '</label>' : '' ) : '' );
?>
<?php                                       $Counter++; 
                                        }
?>
                                            <label class="checkbox-inline">
                                                <input class="custom-radio" type="checkbox" name="AllFeeStructureMonth[<?php echo $FeeGroupID; ?>][<?php echo $FeeHeadID; ?>][FeeHeadApplicableMonths]" id="checkbox<?php echo $FeeGroupID; ?><?php echo $FeeHeadID; ?>" value="1" onclick="CheckAllMonth('checkbox<?php echo $FeeGroupID; ?><?php echo $FeeHeadID; ?>');" />
                                                All
                                            </label>
                                        </td>                               
<?php
                                    echo '</tr>';
                                    }
                                }
                            }
?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="form-group">
                        <div class="col-sm-offset-2 col-lg-10">
                            <input type="hidden" name="hdnProcess" value="1" />
                            <input type="hidden" name="hdnAcademicYearID" value="<?php echo $Clean['AcademicYearID']; ?>" />
                            <input type="hidden" name="hdnFeeGroupID" value="<?php echo $Clean['FeeGroupID']; ?>" />
                            <input type="hidden" name="hdnClassID" value="<?php echo $Clean['ClassID']; ?>" />
                            <input type="hidden" name="hdnClassSectionID" value="<?php echo $Clean['ClassSectionID']; ?>" />
                            <input type="hidden" name="hdnStudentID" value="<?php echo $Clean['StudentID']; ?>" />
                            <input type="hidden" name="hdnFeeDiscountType" value="<?php echo $Clean['FeeDiscountType']; ?>" />
                            <button type="submit" class="btn btn-primary">Save</button>
                        </div>
                        </div>
                    </div>
                </div>
            </form>
<?php
}
?>          </div>
        <!-- /#page-wrapper -->

    </div>
    <!-- /#wrapper -->
<?php
require_once('../footer.php');
?>
<script type="text/javascript">
$(document).ready(function(){

    var CheckedValue = $("input[name='optFeeDiscountType']:checked").val();

        $("div.FeeDiscountType").hide();
        $("#"+CheckedValue).show();

   /* if (CheckedValue == 'Group') 
    {
        $("div.FeeDiscountType").hide();
        $("#"+CheckedValue).show();
    }

    if (CheckedValue == 'Student') 
    {
        $("div.FeeDiscountType").hide();
        $("#"+CheckedValue).show();
    }*/

    $("input[name='optFeeDiscountType']").click(function() {
        var check = $(this).val();
        $("div.FeeDiscountType").slideUp();
        $("#RecordForm").hide();
        $("#"+check).slideDown();

        $("#ClassID").val(0);
    });

    $(".discount-type").each(function(){
        CalculateDiscount($(this).val());
    });

});
$(function()
{
    $('#ClassID').change(function()
    {
        var ClassID = parseInt($(this).val());
        
        if (ClassID <= 0)
        {
            $('#ClassSectionID').html('<option value="0">Select Section</option>');
            return;
        }
        
        $.post("/xhttp_calls/get_sections_by_classs.php", {SelectedClassID:ClassID}, function(data)
        {
            ResultArray = data.split("|*****|");
            
            if (ResultArray[0] == 'error')
            {
                alert (ResultArray[1]);
                return false;
            }
            else
            {
                $('#ClassSectionID').html('<option value="0">Select Section</option>'+ResultArray[1]);
            }
         });
    });
});

$(function()
{
    $('#ClassSectionID').change(function()
    {
        var ClassSectionID = parseInt($(this).val());
        var AcademicYearID = parseInt($('#AcademicYearID').val());
        
        if (ClassSectionID <= 0)
        {
            $('#StudentID').html('<option value="0">Select Student</option>');
            return;
        }
        
        $.post("/xhttp_calls/get_students_by_class_section.php", {SelectedClassSectionID:ClassSectionID,SelectedAcademicYearID:AcademicYearID}, function(data)
        {
            ResultArray = data.split("|*****|");
            
            if (ResultArray[0] == 'error')
            {
                alert (ResultArray[1]);
                return false;
            }
            else
            {
                $('#StudentID').html(ResultArray[1]);
            }
        });
    });
    
    $('#AcademicYearID').change(function()
    {
        $('#ClassID').val(0);
        $('#ClassSectionID').html('<option value="0">Select Section</option>');
        $('#StudentID').html('<option value="0">Select Student</option>');
        return;
    });
});

function CalculateDiscount(FeeHeadID)
{
    if ($("input[name='optDiscountTypeList["+FeeHeadID+"]']:checked").val() == 'Absolute' && $('#DiscountValue'+FeeHeadID).val() != '') 
    {
        var FeeAmount = parseFloat($('#FeeAmount'+FeeHeadID).val());
        var DiscountValue = parseFloat($('#DiscountValue'+FeeHeadID).val());

        if (FeeAmount > 0 && DiscountValue <= FeeAmount) 
        {
            var AfterDiscount = FeeAmount - DiscountValue;
            $('#AfterDiscount'+FeeHeadID).val(AfterDiscount);
        }
        else
        {
            alert('Discount value cannot be greater than total fee amount. Please enter currect value !');

            $('#DiscountValueTD'+FeeHeadID).addClass("has-error");
            $('#DiscountValue'+FeeHeadID).val('');
            $('#AfterDiscount'+FeeHeadID).val('');
            return false;
        }
    }
    else if ($("input[name='optDiscountTypeList["+FeeHeadID+"]']:checked").val() == 'Percentage' && $('#DiscountValue'+FeeHeadID).val() != '') 
    {
        var FeeAmount = parseFloat($('#FeeAmount'+FeeHeadID).val());
        var DiscountValue = parseFloat($('#DiscountValue'+FeeHeadID).val());

        if (FeeAmount > 0 && DiscountValue <= 100) 
        {
            var AfterDiscount = (FeeAmount - (FeeAmount * DiscountValue) / 100);
            $('#AfterDiscount'+FeeHeadID).val(AfterDiscount);
        }
        else
        {
            alert('Discount percentage cannot be greater than 100. Please enter currect value !');
            $('#DiscountValueTD'+FeeHeadID).addClass("has-error");
            $('#DiscountValue'+FeeHeadID).val('');
            $('#AfterDiscount'+FeeHeadID).val('');
            return false;
        }
    }
}

function GetNextClassFeeStructureDiscount(FeeGroupID, ClassID)
{
    $('.classesButton').removeClass("btn-success");
    $('.classesButton').addClass("btn-primary");
    $('#'+ClassID).addClass("btn-success");

    $('#ActivatedClassID').val(ClassID);

    if (ClassID <= 0)
    {
        return;
    }
    
    $.post("/xhttp_calls/get_next_class_fee_structure_discount.php", {SelectedClassID:ClassID, SelectedFeeGroupID:FeeGroupID}, function(data)
    {
        ResultArray = data.split("|*****|");
        
        if (ResultArray[0] == 'error')
        {
            alert (ResultArray[1]);
            return false;
        }
        else
        {
            $(this).addClass("btn-success");
            $('#DiscountStructure').html(ResultArray[1]);
            
            $(".discount-type").each(function(){
                CalculateDiscount($(this).val());
            });
        }
    });
}

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
    
    if ($('input.checkbox'+ClassVariable+':checked').length == Counter) 
    {
        $('#checkbox'+ClassVariable).prop('checked',true);
    }
    else
    {
        $('#checkbox'+ClassVariable).prop('checked',false);
    }
}
</script>
</body>
</html>
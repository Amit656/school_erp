<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once("../../classes/class.ui_helpers.php");

require_once('../../classes/fee_management/class.fee_discounts.php');
require_once('../../classes/school_administration/class.classes.php');
require_once('../../classes/school_administration/class.class_sections.php');

require_once('../../classes/school_administration/class.students.php');
require_once('../../classes/school_administration/class.student_details.php');
require_once('../../classes/school_administration/class.parent_details.php');
require_once('../../classes/school_administration/class.academic_year_months.php');
require_once('../../classes/school_administration/class.academic_years.php');

require_once('../../classes/fee_management/class.fee_collection.php');
require_once('../../classes/fee_management/class.fee_transactions.php');

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

if ($LoggedUser->HasPermissionForTask(TASK_REFUND_FEE) !== true)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}

$PaymentModeList = array(1 => 'Cash', 2 => 'Cheque');
$DiscountTypeList = array('Discount' => 'Discount', 'Concession' => 'Concession', 'WaveOff' => 'WaveOff');

$AcademicYears =  array();
$AcademicYears = AcademicYear::GetAllAcademicYears();

$AcademicYearMonths =  array();
$AcademicYearMonths =  AcademicYearMonth::GetMonthsByFeePriority();

$ClassList =  array();
$ClassList = AddedClass::GetActiveClasses();

$ClassSectionsList = array();

$Filters = array();

$FeeCollectionDetails = array();

$RecordDeletedSuccessfully = false;
$HasErrors = false;
$TotalRecords = 0;

$Clean = array();
$Clean['Process'] = 0;

$Clean['AcademicYearID'] = 0;
$Clean['ClassID'] = 0;
$Clean['ClassSectionID'] = 0;
$Clean['StudentID'] = '';

$Clean['MonthList'] = array();
$SelectedMonths = '';

$Clean['RefundFeeHeadAmountList'] = array();
$Clean['PaymentMode'] = 1;
$Clean['ChequeNumber'] = '';
$Clean['TotalRefundFeeHeadAmount'] = 0;

$TotalMonthlyFeeAmount = 0;

$SelectedMonthFeeDetails = array();
$RefundFeeHeadAmountList = array();
$RefundFeeDetails = array();

$StudentsList = array();

// paging and sorting variables start here  //
$Clean['AllRecords'] = '';
$Clean['CurrentPage'] = 1;
$TotalPages = 0;

$Start = 0;
$Limit = 100;
// end of paging variables//

if (isset($_GET['hdnProcess']))
{
    $Clean['Process'] = (int) $_GET['hdnProcess'];
}
else if (isset($_POST['hdnProcess']))
{
    $Clean['Process'] = (int) $_POST['hdnProcess'];
}
elseif (isset($_GET['Process']))
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
		
        if (isset($_POST['hdnClassSectionID'])) 
		{
			$Clean['ClassSectionID'] = (int) $_POST['hdnClassSectionID'];
		}

		if (isset($_POST['hdnStudentID'])) 
		{
			$Clean['StudentID'] = (int) $_POST['hdnStudentID'];
		}
		
		if (isset($_POST['hdnMonthList']))
        {
            $SelectedMonths = $_POST['hdnMonthList'];
        }
        
        if ($SelectedMonths != '')
        {
            $Clean['MonthList'] = explode(',', $SelectedMonths);   
        }
        
        if (isset($_POST['txtRefundFeeHeadAmount']) && is_array($_POST['txtRefundFeeHeadAmount']))
        {
            $Clean['RefundFeeHeadAmountList'] = $_POST['txtRefundFeeHeadAmount'];           
        }
        
        if (isset($_POST['txtTotalRefundFeeHeadAmount']))
        {
            $Clean['TotalRefundFeeHeadAmount'] = strip_tags(trim($_POST['txtTotalRefundFeeHeadAmount']));           
        }
        
        if (isset($_POST['drdPaymentMode']))
        {
            $Clean['PaymentMode'] = strip_tags(trim($_POST['drdPaymentMode']));           
        }
        
        if (isset($_POST['txtChequeNumber']))
        {
            $Clean['ChequeNumber'] = strip_tags(trim($_POST['txtChequeNumber']));           
        }
           
        $SearchValidator = new Validator();
        
        $SearchValidator->ValidateInSelect($Clean['ClassID'], $ClassList, 'Please select a valid class.');
        $ClassSectionsList = ClassSections::GetClassSections($Clean['ClassID']);
        
        $SearchValidator->ValidateInSelect($Clean['ClassSectionID'], $ClassSectionsList, 'Please select a valid section.');
        
        $StudentsList = StudentDetail::GetStudentsByClassSectionID($Clean['ClassSectionID'], 'Active', $Clean['AcademicYearID']);
        $SearchValidator->ValidateInSelect($Clean['StudentID'], $StudentsList, 'Please select a valid student.');
        
        $SearchValidator->ValidateNumeric($Clean['TotalRefundFeeHeadAmount'], 'Please enter valid total refund amount.');
        $SearchValidator->ValidateInSelect($Clean['PaymentMode'], $PaymentModeList, 'Unknown error, please try again.');
        
        if ($Clean['TotalRefundFeeHeadAmount'] <= 0)
        {
            $SearchValidator->AttachTextError('Total refund amount should be greater then zero.');
        }
        if ($Clean['PaymentMode'] == 2)
        {
            $SearchValidator->ValidateStrings($Clean['ChequeNumber'], 'Cheque number is required and should be between 3 and 30 characters.', 3, 30);
        }
        
        $StudentDetailObject = new StudentDetail($Clean['StudentID']);
        
        foreach ($Clean['MonthList'] as $Key => $MonthID)
        {
            $StudentMonthlyFeeDetails = array();
            $TotalMonthlyFeeAmount = $StudentDetailObject->GetStudentMonthlyFeeDetails($MonthID, $StudentMonthlyFeeDetails, $Clean['AcademicYearID'], true);
            
            $SelectedMonthFeeDetails[$MonthID] = $StudentMonthlyFeeDetails;
            
            foreach ($Clean['RefundFeeHeadAmountList'] as $StudentFeeStructureID => $RefundAmount)
            {
                if (array_key_exists($StudentFeeStructureID, $StudentMonthlyFeeDetails) && $RefundAmount > 0)
                {
                    $SearchValidator->ValidateNumeric($RefundAmount, 'Please enter valid refund amount of perticular head.');
                    
                    $RefundFeeHeadAmountList[$StudentFeeStructureID]['SubmittedAmount'] = $StudentMonthlyFeeDetails[$StudentFeeStructureID]['AmountPaid'];   
                    $RefundFeeHeadAmountList[$StudentFeeStructureID]['RefundAmount'] = $RefundAmount;   
                }
            }
        }
        
        if ($SearchValidator->HasNotifications())
        {
            $HasErrors = true;
            break;
        }
        
        $RefundFeeDetails[$Clean['StudentID']]['PaymentMode'] = $Clean['PaymentMode'];
        $RefundFeeDetails[$Clean['StudentID']]['ChequeNumber'] = $Clean['ChequeNumber'];
        $RefundFeeDetails[$Clean['StudentID']]['TotalRefundFeeHeadAmount'] = $Clean['TotalRefundFeeHeadAmount'];
        $RefundFeeDetails[$Clean['StudentID']]['RefundFeeHeadAmountList'] = $RefundFeeHeadAmountList;
        $RefundFeeDetails[$Clean['StudentID']]['CreateUserID'] = $LoggedUser->GetUserID();
        
        if (!FeeTransaction::RefundStudentFee($RefundFeeDetails))
        {
            $SearchValidator->AttachTextError('Error in executing query.');
            $HasErrors = true;
            break;
        }
        
        header('location:refund_fee.php?Mode=AS');
		exit;
		break;
    break;
    
    case 7:         
        
        if (isset($_GET['drdAcademicYear'])) 
		{
			$Clean['AcademicYearID'] = (int) $_GET['drdAcademicYear'];
		}
		else if (isset($_GET['AcademicYearID'])) 
		{
			$Clean['AcademicYearID'] = (int) $_GET['AcademicYearID'];
		}
		
        if (isset($_GET['drdClass']))
        {
            $Clean['ClassID'] = strip_tags(trim($_GET['drdClass']));
        }
        elseif (isset($_GET['ClassID']))
        {
            $Clean['ClassID'] = strip_tags(trim($_GET['ClassID']));
        }

        if (isset($_GET['drdClassSection']))
        {
            $Clean['ClassSectionID'] = strip_tags(trim($_GET['drdClassSection']));
        }
        elseif (isset($_GET['ClassSectionID']))
        {
            $Clean['ClassSectionID'] = strip_tags(trim($_GET['ClassSectionID']));
        }
        
        if (isset($_GET['drdStudent']))
        {
            $Clean['StudentID'] = (int) $_GET['drdStudent'];
        }
        elseif (isset($_GET['StudentID']))
        {
            $Clean['StudentID'] = (int) $_GET['StudentID'];
        }

        if (isset($_GET['chkMonth']) && is_array($_GET['chkMonth']))
        {
            $Clean['MonthList'] = $_GET['chkMonth'];
        }
        elseif (isset($_GET['MonthList']))
        {
            $SelectedMonths = $_GET['MonthList'];
        }
        
        if ($SelectedMonths != '')
        {
            $Clean['MonthList'] = explode(',', $SelectedMonths);   
        }

        $SearchValidator = new Validator();

        $SearchValidator->ValidateInSelect($Clean['ClassID'], $ClassList, 'Please select a valid class.');
        $ClassSectionsList = ClassSections::GetClassSections($Clean['ClassID']);
        
        $SearchValidator->ValidateInSelect($Clean['ClassSectionID'], $ClassSectionsList, 'Please select a valid section.');
        
        $StudentsList = StudentDetail::GetStudentsByClassSectionID($Clean['ClassSectionID'], 'Active', $Clean['AcademicYearID']);
        $SearchValidator->ValidateInSelect($Clean['StudentID'], $StudentsList, 'Please select a valid student.');
        
        if (count($Clean['MonthList']) <= 0)
        {
            $SearchValidator->AttachTextError('Please Select atleast one month.');
        }
        
        if ($SearchValidator->HasNotifications())
        {
            $HasErrors = true;
            break;
        }
        
        $StudentDetailObject = new StudentDetail($Clean['StudentID']);
        
        foreach ($Clean['MonthList'] as $Key => $MonthID)
        {
            $StudentMonthlyFeeDetails = array();
            $TotalMonthlyFeeAmount = $StudentDetailObject->GetStudentMonthlyFeeDetails($MonthID, $StudentMonthlyFeeDetails, $Clean['AcademicYearID'], true);
            
            $SelectedMonthFeeDetails[$MonthID] = $StudentMonthlyFeeDetails;
        }

        $SelectedMonths = implode(',', $Clean['MonthList']);   
}

$LandingPageMode = '';
if (isset($_GET['Mode']))
{
    $LandingPageMode = $_GET['Mode'];
}

require_once('../html_header.php');
?>
<title>Refund Fee</title>
<!-- DataTables CSS -->
<link href="../vendor/datatables-plugins/dataTables.bootstrap.css" rel="stylesheet">

<!-- DataTables Responsive CSS -->
<link href="../vendor/datatables-responsive/dataTables.responsive.css" rel="stylesheet">
<link href="../vendor/jquery-ui/jquery-ui.min.css" rel="stylesheet">
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
                    <h1 class="page-header">Refund Student Fee</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
            <form class="form-horizontal" name="frmRoomReport" action="refund_fee.php" method="get">
                <div class="panel panel-default" id="accordion">
                    <div class="panel-heading">
                        <strong><a data-toggle="collapse" data-parent="#accordion" href="#collapseOne">Filters</a></strong>
                    </div>
                    <div id="collapseOne" class="panel-collapse collapse in">
                        <div class="panel-body">
<?php
                            if ($HasErrors == true)
                            {
                                echo $SearchValidator->DisplayErrors();
                            }
                            else if ($LandingPageMode == 'AS')
                            {
                                echo '<div class="alert alert-success">Fee Refunded successfully.</div>';
                            }
                            else if ($RecordDeletedSuccessfully == true)
                            {
                                echo '<div class="alert alert-danger">Record deleted successfully.</div>';
                            }
                            else if ($LandingPageMode == 'UD')
                            {
                                echo '<div class="alert alert-success">Record updated successfully.</div>';
                            }
?>
                            
                            <div class="form-group">
                                <label for="AcademicYear" class="col-lg-2 control-label">Academic Year</label>
                                <div class="col-lg-3">
                                	<!--<input class="form-control" type="text" maxlength="10" id="AcademicYear" name="txtAcademicYear" readonly="readonly" value="<?php echo $AcademicYearName; ?>" />-->
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
                                            
                                            echo '<option ' . ($Clean['AcademicYearID'] == $AcademicYearID ? 'selected="selected"' : '') . ' value="' . $AcademicYearID . '" >' . date('Y', strtotime($AcademicYearDetails['StartDate'])) .' - '. date('Y', strtotime($AcademicYearDetails['EndDate'])) . '</option>';
                                        }
                                    }
    ?>
    								</select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="ClassList" class="col-lg-2 control-label">Class</label>
                                <div class="col-lg-3">
                                    <select class="form-control" name="drdClass" id="Class">
                                        <option  value="0" >-- All Class --</option>
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
                                <label for="ClassSection" class="col-lg-2 control-label">Section</label>
                                <div class="col-lg-3">
                                    <select class="form-control" name="drdClassSection" id="ClassSection">
                                        <option value="0">-- All Section --</option>
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
                                <label for="Student" class="col-lg-2 control-label">Student</label>
                                <div class="col-lg-8">
                                    <select class="form-control" name="drdStudent" id="Student">
                                        <option value="0">-- Select Student --</option>
<?php
                                            if (is_array($StudentsList) && count($StudentsList) > 0)
                                            {
                                                foreach ($StudentsList as $StudentID => $StudentDetails)
                                                {
                                                    echo '<option ' . ($Clean['StudentID'] == $StudentID ? 'selected="selected"' : '') . ' value="' . $StudentID . '">' . $StudentDetails['FirstName'] . ' ' . $StudentDetails['LastName'] . '(' . $StudentDetails['RollNumber'] . ')</option>'; 
                                                }
                                            }
?>
                                    </select>
                                </div>                                
                            </div>

                            <div class="form-group">                            
                                <label for="Month" class="col-lg-2 control-label">Month</label>
                                <div class="col-lg-8">
<?php
                            foreach ($AcademicYearMonths as $AcademicYearMonthID => $MonthDetails)
                            {
?>
                                <label class="checkbox-inline">
                                    <input class="custom-radio " type="checkbox" <?php echo (in_array($AcademicYearMonthID, $Clean['MonthList']) ? 'checked="checked"' : ''); ?> name="chkMonth[<?php echo $AcademicYearMonthID; ?>]" value="<?php echo $AcademicYearMonthID; ?>" />
                                    <?php echo $MonthDetails['MonthShortName']; ?>
                                </label>
<?php
                            }
?>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="col-lg-offset-2 col-lg-10">
                                    <input type="hidden" name="hdnProcess" value="7" />
                                    <input type="hidden" name="report_submit" id="get_excel" value="0" />
                                    <button type="submit" class="btn btn-primary" id="SubmitSearch">Search</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    </div>
            </form>
            <!-- /.row -->
<?php
        if (($Clean['Process'] == 7 || $Clean['Process'] == 1) && count($SelectedMonthFeeDetails) > 0)
        {
            $ReportHeaderText = '';
            
            if ($Clean['AcademicYearID'] != 0)
            {
                $ReportHeaderText .= ' Session: ' . date('Y', strtotime($AcademicYears[$Clean['AcademicYearID']]['StartDate'])) .' - '. date('Y', strtotime($AcademicYears[$Clean['AcademicYearID']]['EndDate'])) . ',';
            }
            
            if ($Clean['ClassID'] > 0)
            {
                $ReportHeaderText .= ' Class : ' . $ClassList[$Clean['ClassID']] . ',';
            }

            if ($Clean['ClassSectionID'] > 0)
            {
                $ReportHeaderText .= ' Section : ' . $ClassSectionsList[$Clean['ClassSectionID']] . ',';
            }
            
            if ($Clean['StudentID'] != '')
            {
                $ReportHeaderText .= ' Student Name : ' . $StudentsList[$Clean['StudentID']]['FirstName'] . ' ' . $StudentsList[$Clean['StudentID']]['LastName'] . ',';
            }

            foreach ($Clean['MonthList'] as $MonthID => $Value) 
            {
                $ReportHeaderText .= ' Months '. $AcademicYearMonths[$MonthID]['MonthName'] . ', ';
            }

            if ($ReportHeaderText != '')
            {
                $ReportHeaderText = ' for' . rtrim($ReportHeaderText, ',');
            }
?>
            <div class="row">
                <div class="col-lg-12">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <strong>Total Records Returned: <?php echo $TotalRecords; ?></strong>
                        </div>
                        <!-- /.panel-heading -->
                        <div class="panel-body">
                            <div>
                                <div class="row">
                                    <div class="col-lg-6">
<?php
                                    // if ($TotalPages > 1)
                                    // {
                                    //     $AllParameters = array('Process' => '7', 'ClassID' => $Clean['ClassID'], 'ClassSectionID' => $Clean['ClassSectionID'], 'StudentID' => $Clean['StudentID'], 'MonthList' => $SelectedMonths);
                                    //     echo UIHelpers::GetPager('refund_fee.php', $TotalPages, $Clean['CurrentPage'], $AllParameters);
                                    // }
?>                                        
                                    </div>
                                    <!--<div class="col-lg-6">-->
                                    <!--    <div class="print-btn-container"><button id="PrintButton" type="submit" class="btn btn-primary">Print</button>-->
                                        <!-- <button id="" onclick="$('#get_excel').val(2); $('#SubmitSearch').click();$('#get_excel').val(0);" type="submit" class="btn btn-primary">Export to Excel</button> -->
                                    <!--    </div>-->
                                    <!--</div>-->
                                </div>
                                <div class="row" id="RecordTableHeading">
                                    <div class="col-lg-12">
                                        <div class="report-heading-container"><strong>Student Fee Details on <?php echo date('d-m-Y h:i A') . $ReportHeaderText; ?></strong></div>
                                    </div>
                                </div>
                                <form class="form-horizontal" name="frmRoomReport" action="refund_fee.php" method="post">
                                <div class="row" id="RecordTable">
                                    <div class="col-lg-12">
                                        <table width="100%" class="table table-striped table-bordered table-hover" id="DataTableRecords">
                                            <thead>
                                                <tr>
                                                    <th>S.No</th>
                                                    <th>Month Name</th>
                                                    <th>Fee Head</th>
                                                    <th>Submitted Amount</th>
                                                    <th>Refund Amount</th>
                                                </tr>
                                            </thead>
                                            <tbody>
<?php
                                    if (is_array($SelectedMonthFeeDetails) && count($SelectedMonthFeeDetails) > 0)
                                    {
                                        $Counter = $Start;
                                        $TotalRefundAmount = 0;
                                        
                                        foreach ($SelectedMonthFeeDetails as $MonthID => $StudentMonthlyFeeDetails)
                                        {
                                            foreach ($StudentMonthlyFeeDetails as $StudentFeeStructureID => $Details)
                                            {
                                                $TotalRefundAmount += $Details['AmountPaid'];
    ?>
                                                <tr>
                                                    <td><?php echo ++$Counter; ?></td>
                                                    <td><?php echo $AcademicYearMonths[$MonthID]['MonthName']; ?></td>
                                                    <td><?php echo $Details['FeeHead']; ?></td>
                                                    <td><?php echo number_format($Details['AmountPaid'], 2); ?></td>
                                                    <td><?php echo '<input type="text" class="form-control FeeHeadAmount" id="FeeHead'. $StudentFeeStructureID .'" name="txtRefundFeeHeadAmount['. $StudentFeeStructureID .']" value="'. $Details['AmountPaid'] .'">' ; ?></td>
                                                </tr>
    <?php
                                            }
                                        }
?>
                                        <tr>
                                            <td class="text-right" colspan="4"><b>Total Refund Amount</b></td>
                                            <td><?php echo '<input type="text" class="form-control" id="TotalRefundFeeHeadAmount" name="txtTotalRefundFeeHeadAmount" value="'. $TotalRefundAmount .'" readonly="readonly">' ; ?></td>
                                        </tr>
                                        <tr>
                                            <td class="text-right" colspan="4"><b>Mode</b></td>
                                            <td class="text-right">
                                                <select class="form-control" name="drdPaymentMode" id="PaymentMode">
<?php
			                                            if (is_array($PaymentModeList) && count($PaymentModeList) > 0)
			                                            {
			                                                foreach ($PaymentModeList as $PaymentModeID => $PaymentModeName) 
			                                                {
			                                                    echo '<option ' . ($Clean['PaymentMode'] == $PaymentModeID ? 'selected="selected"' : '') . ' value="' . $PaymentModeID . '">' . $PaymentModeName . '</option>' ;
			                                                }
			                                            }
?>
				                                </select>
				                                <?php echo '<input type="text" class="form-control" id="ChequeNumber" name="txtChequeNumber" value="'. $Clean['ChequeNumber'] .'" placeholder="Enter cheque number" style="display: none;">' ; ?>
                                            </td>
                                        </tr>
<?php
                                    }
?>
                                            </tbody>
                                        </table>
                                        </div>
                                        <div class="form-group">
                                        <div class="col-lg-offset-8 col-lg-2">
                                            <input type="hidden" name="hdnProcess" value="1" />
                                            <input type="hidden" name="hdnAcademicYearID" value="<?php echo $Clean['AcademicYearID']; ?>" />
                                            <input type="hidden" name="hdnClassID" value="<?php echo $Clean['ClassID']; ?>" />
                                            <input type="hidden" name="hdnClassSectionID" value="<?php echo $Clean['ClassSectionID']; ?>" />
                                            <input type="hidden" name="hdnStudentID" value="<?php echo $Clean['StudentID']; ?>" />
                                            <input type="hidden" name="hdnMonthList" value="<?php echo $SelectedMonths; ?>" />
                                            <input type="hidden" name="report_submit" id="get_excel" value="0" />
                                            <button type="submit" class="btn btn-primary" id="SubmitSearch">Refund</button>
                                        </div>
                            </div>
                                    </div>
                                    </form>
                                </div>
                            <!-- /.table-responsive -->
                        </div>
                        <!-- /.panel-body -->
                    </div>
                    <!-- /.panel -->
                </div>
                <!-- /.col-lg-12 -->
            </div>
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
<!-- DataTables JavaScript -->
<script src="../vendor/datatables/js/jquery.dataTables.min.js"></script>
<script src="../vendor/datatables-plugins/dataTables.bootstrap.min.js"></script>
<script src="../vendor/datatables-responsive/dataTables.responsive.js"></script> 
<script src="../vendor/jquery-ui/jquery-ui.min.js"></script>  
<script type="text/javascript">
$(document).ready(function() {

    $('[data-toggle="tooltip"]').tooltip();  

    $(".select-date").datepicker({
        changeMonth: true,
        changeYear: true,
        dateFormat: 'dd/mm/yy'
    });
    
    $("body").on('click', '.delete-record', function()
    {   
        if (!confirm("Are you sure you want to delete this Record?"))
        {
            return false;
        }
    });
    
    $('#Class').change(function(){

        var ClassID = parseInt($(this).val());
        
        if (ClassID <= 0)
        {
            $('#ClassSection').html('<option value="0">-- All Section --</option>');
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
                $('#ClassSection').html('<option value="0">-- All Section --</option>' + ResultArray[1]);
            }
         });
    });
    
    $('#ClassSection').change(function(){

        var ClassSectionID = parseInt($(this).val());
        var AcademicYearID = parseInt($('#AcademicYearID').val());
        
        if (ClassSectionID <= 0)
        {
            $('#Student').html('<option value="0">-- Select Student --</option>');
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
                $('#Student').html(ResultArray[1]);
            }
        });
    });
    
    $("#PaymentMode").change(function() {
        alert($(this).val());
        if ($(this).val() == 2) 
        {
        	$("#ChequeNumber").slideDown();
        }
        else
        {
        	$("#ChequeNumber").slideUp();
        }
    });
    
    $('.FeeHeadAmount').focusin(function(){
        
        StudentFeeStructureID = $(this).attr('id').slice(7);
        
    	var TotalRefundAmount = parseInt($('#TotalRefundFeeHeadAmount').val());
    		FeeHeadAmount = parseInt($(this).val());
    		
    		if (isNaN(TotalRefundAmount)) 
	    	{
	    		TotalRefundAmount = 0;
	    	}

	    	if (isNaN(FeeHeadAmount)) 	
	    	{
	    		FeeHeadAmount = 0;
	    	}
	    	
	    	if (TotalRefundAmount <= 0)
        	{
        	    return false;
        	}
        	
	    	$('#TotalRefundFeeHeadAmount').val(TotalRefundAmount - FeeHeadAmount);
    });
    
    $('.FeeHeadAmount').focusout(function(){

    	var TotalRefundAmount = parseInt($('#TotalRefundFeeHeadAmount').val());
        
    		FeeHeadAmount = parseInt($(this).val());
    		
    		if (isNaN(TotalRefundAmount)) 
	    	{
	    		TotalRefundAmount = 0;
	    	}

	    	if (isNaN(FeeHeadAmount)) 	
	    	{
	    		FeeHeadAmount = 0;
	    	}
        	
	    	$('#TotalRefundFeeHeadAmount').val(TotalRefundAmount + FeeHeadAmount);
    });

    $('#AcademicYearID').change(function(){

        $('#Class').val(0);
        $('#ClassSection').html('<option value="0">Select Section</option>');
        $('#Student').html('<option value="0">Select Student</option>');
    });
    
});
</script>
<!-- JavaScript To Print A Report -->
<script src="../js/print-report.js"></script>
</body>
</html>
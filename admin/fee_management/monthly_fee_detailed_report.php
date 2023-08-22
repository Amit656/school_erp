<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once("../../classes/class.ui_helpers.php");

require_once('../../classes/school_administration/class.academic_years.php');
require_once('../../classes/school_administration/class.academic_year_months.php');
require_once('../../classes/school_administration/class.classes.php');
require_once('../../classes/school_administration/class.class_sections.php');

require_once("../../classes/fee_management/class.fee_heads.php");
require_once('../../classes/fee_management/class.fee_collection.php');

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

if ($LoggedUser->HasPermissionForTask(TASK_FEE_COLLECTION_REPORT) !== true)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}

$StudentStatusList = array('Active' => 'Active', 'InActive' => 'InActive');
$PaymentModeList = array(1 => 'Cash', 2 => 'Cheque', 3 => 'Net Transfer', 4 => 'Bank Transfer', 5 => 'Card Payment', 6 => 'Wallet');
$ReportByList = array(1 => 'Fully Paid', 2 => 'Only Paid', 3 => 'Only Due', 4 => 'Only Discount', 5 => 'Only Concession', 6 => 'Only Wave-Off');

$ClassList =  array();
$ClassList = AddedClass::GetActiveClasses();

$ClassSectionsList =  array();

$AcademicYears =  array();
$AcademicYears = AcademicYear::GetAllAcademicYears();

$AcademicYearMonths =  array();
$AcademicYearMonths =  AcademicYearMonth::GetMonthsByFeePriority();

$ActiveFeeHeads = array();
$ActiveFeeHeads = FeeHead::GetActiveFeeHeads();

$Filters = array();

$MonthlyFeeDueDetails = array();
$OverAllSummary = array();

$RecordDeletedSuccessfully = false;
$HasErrors = false;
$TotalRecords = 0;

$Clean = array();
$Clean['Process'] = 0;

$Clean['AcademicYearID'] = 0;
$Clean['ClassID'] = 0;
$Clean['ClassSectionID'] = 0;
$Clean['FeeHeadID'] = 0;
$Clean['MobileNumber'] = '';

$Clean['Status'] = 'Active';

$Clean['ReportBy'] = 'OnlyPaid';

$Clean['MonthList'] = array();
$SelectedMonths = '';

// paging and sorting variables start here  //
$Clean['AllRecords'] = '';
$Clean['CurrentPage'] = 1;
$TotalPages = 0;

$Start = 0;
$Limit = 50;
// end of paging variables//

if (isset($_GET['hdnProcess']))
{
    $Clean['Process'] = (int) $_GET['hdnProcess'];
}
elseif (isset($_GET['Process']))
{
    $Clean['Process'] = (int) $_GET['Process'];
}
switch ($Clean['Process'])
{
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
        
        if (isset($_GET['drdFeeHead']))
        {
            $Clean['FeeHeadID'] = strip_tags(trim($_GET['drdFeeHead']));
        }
        elseif (isset($_GET['FeeHeadID']))
        {
            $Clean['FeeHeadID'] = strip_tags(trim($_GET['FeeHeadID']));
        }
        
        if (isset($_GET['drdReportBy']))
        {
            $Clean['ReportBy'] = (int) $_GET['drdReportBy'];
        }
        elseif (isset($_GET['ReportBy']))
        {
            $Clean['ReportBy'] = (int) $_GET['ReportBy'];
        }
        
        if (isset($_GET['txtMobileNumber']))
        {
            $Clean['MobileNumber'] = strip_tags(trim($_GET['txtMobileNumber']));
        }
        else if (isset($_GET['MobileNumber']))
        {
            $Clean['MobileNumber'] = strip_tags(trim( (string) $_GET['MobileNumber']));
        }
        
        if (isset($_GET['optStatus']))
        {
            $Clean['Status'] =  strip_tags(trim( (string) $_GET['optStatus']));
        }
        elseif (isset($_GET['Status']))
        {
            $Clean['Status'] =  strip_tags(trim( (string) $_GET['Status']));
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

        if ($Clean['ClassID'] > 0)
        {
            $SearchValidator->ValidateInSelect($Clean['ClassID'], $ClassList, 'Please select a class.');
            $ClassSectionsList = ClassSections::GetClassSections($Clean['ClassID']);
        }

        if ($Clean['ClassSectionID'] > 0)
        {
            $SearchValidator->ValidateInSelect($Clean['ClassSectionID'], $ClassSectionsList, 'Please select a valid section.');
        }
        
        if ($Clean['FeeHeadID'] > 0)
        {
            $SearchValidator->ValidateInSelect($Clean['FeeHeadID'], $ActiveFeeHeads, 'Please select a valid fee head.');
        }
        
        if ($Clean['ReportBy'] > 0)
        {
            $SearchValidator->ValidateInSelect($Clean['ReportBy'], $ReportByList, 'Please select a valid report by.');
        }
        
        if ($Clean['MobileNumber'] != '')
        {
            $SearchValidator->ValidateStrings($Clean['MobileNumber'], 'Mobile number should be between 1 to 15.', 1, 15);
        }
        
        if ($Clean['Status'] != '')
        {
            $SearchValidator->ValidateInSelect($Clean['Status'], $StudentStatusList, 'Unknown Error in status, Please try again.');
        }
        
        if (count($Clean['MonthList']) <= 0)
        {
            $SearchValidator->AttachTextError('Please select at least one month.');
        }

        if ($SearchValidator->HasNotifications())
        {
            $HasErrors = true;
            break;
        }

        //set record filters    
        
        $Filters['AcademicYearID'] = $Clean['AcademicYearID'];
        $Filters['ClassID'] = $Clean['ClassID'];
        $Filters['ClassSectionID'] = $Clean['ClassSectionID'];
        $Filters['FeeHeadID'] = $Clean['FeeHeadID'];
        $Filters['MonthList'] = $Clean['MonthList'];
        
        $Filters['ReportBy'] = $Clean['ReportBy'];
        $Filters['MobileNumber'] = $Clean['MobileNumber'];
        $Filters['Status'] = $Clean['Status'];
        
        //get records count
        FeeCollection::MonthlyFeeDueDetails($TotalRecords, true, $Filters);

        if ($TotalRecords > 0)
        {
            // Paging and sorting calculations start here.
            $TotalPages = (($TotalRecords % $Limit) == 0) ? $TotalRecords / $Limit : floor($TotalRecords / $Limit) + 1;

            if (isset($_GET['CurrentPage']))
            {
                $Clean['CurrentPage'] = (int) $_GET['CurrentPage'];
            }
            
            if (isset($_GET['AllRecords']))
            {
                $Clean['AllRecords'] = (string) $_GET['AllRecords'];
            }

            if ($Clean['CurrentPage'] <= 0)
            {
                $Clean['CurrentPage'] = 1;
            }
            elseif ($Clean['CurrentPage'] > $TotalPages)
            {
                $Clean['CurrentPage'] = $TotalPages;
            }

            if ($Clean['CurrentPage'] > 1)
            {
                $Start = ($Clean['CurrentPage'] - 1) * $Limit;
            }
            // end of Paging and sorting calculations.
            // now get the actual  records
            if ($Clean['AllRecords'] == 'All') 
            {
                $MonthlyFeeDueDetails = FeeCollection::MonthlyFeeDueDetails($TotalRecords, false, $Filters, $OverAllSummary, 0, $TotalRecords);
            }
            else
            {
                $MonthlyFeeDueDetails = FeeCollection::MonthlyFeeDueDetails($TotalRecords, false, $Filters, $OverAllSummary, $Start, $Limit);
            }
            if (isset($_GET['report_submit']) && $_GET['report_submit'] == 2)
            {
                require_once('../excel/monthly_fee_detailed_report_download_xls.php');
            }

            $SelectedMonths = '';
            $SelectedMonths = implode(',', $Clean['MonthList']);
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
<title>Monthly Fee Detailed Report</title>
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
                    <h1 class="page-header">Monthly Fee Detailed Report</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
            <form class="form-horizontal" name="frmRoomReport" action="monthly_fee_detailed_report.php" method="get">
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
                                echo '<div class="alert alert-success">Record saved successfully.</div>';
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
                                <label for="FeeHeadList" class="col-lg-2 control-label">Fee Head</label>
                                <div class="col-lg-3">
                                    <select class="form-control" name="drdFeeHead" id="FeeHead">
                                        <option  value="0" >-- All Fee Head --</option>
    <?php
                                        foreach ($ActiveFeeHeads as $FeeHeadID => $FeeHeadDetails)
                                        {
    ?>
                                            <option <?php echo ($FeeHeadID == $Clean['FeeHeadID'] ? 'selected="selected"' : ''); ?> value="<?php echo $FeeHeadID; ?>"><?php echo $FeeHeadDetails['FeeHead']; ?></option>
    <?php
                                        }
    ?>
                                    </select>
                                </div>
                                <label for="ReportBy" class="col-lg-2 control-label">Search By</label>
                                <div class="col-lg-3">
                                    <select class="form-control" name="drdReportBy" id="ReportBy">
                                        <option  value="0" >-- All Type --</option>
    <?php
                                        foreach ($ReportByList as $ReportByID => $ReportBy)
                                        {
    ?>
                                            <option <?php echo ($ReportByID == $Clean['ReportBy'] ? 'selected="selected"' : ''); ?> value="<?php echo $ReportByID; ?>"><?php echo $ReportBy; ?></option>
    <?php
                                        }
    ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">                            
                                <label for="MobileNumber" class="col-lg-2 control-label">Mobile Number</label>
                                <div class="col-lg-8">
                                    <input class="form-control" type="text" maxlength="50" id="MobileNumber" name="txtMobileNumber" value="<?php echo $Clean['MobileNumber']; ?>" />
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="Status" class="col-lg-2 control-label">Student Status</label>
                                <div class="col-lg-5">
                                    <label class="col-sm-4"><input class="custom-radio" type="radio" id="All" name="optStatus" value="" <?php echo ($Clean['Status'] == '' ? 'checked="checked"' : ''); ?> >&nbsp;&nbsp;All</label>
    <?php
                                foreach ($StudentStatusList as $StatusKey => $StatusName)
                                {
    ?>
                                    <label class="col-sm-4"><input class="custom-radio" type="radio" id="<?php echo $StatusKey;?>" name="optStatus" value="<?php echo $StatusKey;?>" <?php echo (($Clean['Status'] == $StatusKey) ? 'checked="checked"' : ''); ?> >&nbsp;&nbsp;<?php echo $StatusName;?></label>
    <?php
                                }
    ?>
                                </div>
                            </div>
                            <div class="form-group">                            
                                <label for="Month" class="col-lg-2 control-label">By Month</label>
                                <div class="col-lg-9">
<?php
                            foreach ($AcademicYearMonths as $AcademicYearMonthID => $MonthDetails)
                            {
?>
                                <label class="checkbox-inline">
                                    <input class="custom-radio chkAllMonth" type="checkbox" <?php echo (in_array($AcademicYearMonthID, $Clean['MonthList']) ? 'checked="checked"' : ''); ?> name="chkMonth[<?php echo $AcademicYearMonthID; ?>]" value="<?php echo $AcademicYearMonthID; ?>" />
                                    <?php echo $MonthDetails['MonthShortName']; ?>
                                </label>
<?php
                            }
?>
                                    <label class="checkbox-inline">
                                        <input class="custom-radio " id="chkAllMonth" type="checkbox" <?php echo (count($Clean['MonthList']) == count($AcademicYearMonths) ? 'checked="checked"' : ''); ?> name="chkAllMonth" value="" />
                                        All
                                    </label>
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
        if ($Clean['Process'] == 7 && $HasErrors == false)
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
            
            if ($Clean['FeeHeadID'] > 0)
            {
                $ReportHeaderText .= ' Fee Head : ' . $ActiveFeeHeads[$Clean['FeeHeadID']]['FeeHead'] . ',';
            }
            
            if ($Clean['ReportBy'] > 0)
            {
                $ReportHeaderText .= ' Report of : ' . $ReportByList[$Clean['ReportBy']] . ',';
            }
            
            if ($Clean['MobileNumber'] != '')
            {
                $ReportHeaderText .= ' Mobile Number : ' . $Clean['MobileNumber'] . ',';
            }
            
            if ($Clean['Status'] != '')
            {
                $ReportHeaderText .= ' Status: ' . $Clean['Status'] . ' students,';
            }
            
            if (count($Clean['MonthList']) > 0)
            {
                $ReportHeaderText .= ' Months ';
                
                foreach ($Clean['MonthList'] as $MonthID => $Value) 
                {
                    $ReportHeaderText .= ''. $AcademicYearMonths[$MonthID]['MonthName'] . ', ';
                }
            }
            
            if ($ReportHeaderText != '')
            {
                $ReportHeaderText = ' for' . rtrim($ReportHeaderText, ',');
            }
?>
            <div class="row">
                <div class="col-lg-12">
                    <div class="panel panel-default" id="accordion">
                        <div class="panel-heading">
                            <strong><a data-toggle="collapse" data-parent="#accordion" href="#collapseTwo">Over All Summary <i class="fa fa-caret-down"></i></a></strong>
                        </div>
                        <div id="collapseTwo" class="panel-collapse collapse in">
                            <div class="panel-body">
                                <div class="row">
                                    <div class="col-lg-12">
                                        <table width="60%" class="table table-striped table-bordered table-hover ">
                                            <thead>
                                                <tr>
                                                    <th colspan="7" class="text-center">Summary of Months:  
<?php
                                                foreach ($Clean['MonthList'] as $MonthID => $Value) 
                                                {
                                                    echo $AcademicYearMonths[$MonthID]['MonthName'] . ', ';
                                                }
?>
                                                    </th>
                                                </tr>
                                                <tr>
                                                    <th >Fee Head</th>
                                                    <th >Total Fee</th>
                                                    <th >Total Discount</th>
                                                    <th >Total Concession</th>
                                                    <th >Total Wave-Off</th>
                                                    <th >Total Collected</th>
                                                    <th >Total Due</th>
                                                </tr>
<?php
                                            $SummaryTotalAmount = 0;
                                            $SummaryTotalDiscount = 0;
                                            $SummaryTotalConcessionAmount = 0;
                                            $SummaryTotalWaveOffAmount = 0;
                                            $SummaryTotalPaidAmount = 0;
                                            $SummaryTotalDueAmount = 0;
                                            
                                            foreach ($OverAllSummary as $FeeHeadID => $SummaryDetails) 
                                            {
                                                $SummaryTotalAmount += $SummaryDetails['TotalAmount'];
                                                $SummaryTotalDiscount += $SummaryDetails['TotalDiscount'];
                                                $SummaryTotalConcessionAmount += $SummaryDetails['TotalConcessionAmount'];
                                                $SummaryTotalWaveOffAmount += $SummaryDetails['TotalWaveOffAmount'];
                                                $SummaryTotalPaidAmount += $SummaryDetails['TotalPaidAmount'];
                                                $SummaryTotalDueAmount += $SummaryDetails['TotalDueAmount'];
?>
                                                <tr>
                                                    <th class=""><?php echo $SummaryDetails['FeeHead']; ?></th>
                                                    <th class="text-right text-danger"><?php echo number_format($SummaryDetails['TotalAmount'], 2) ;?></th>
                                                    <th class="text-right text-danger"><?php echo number_format($SummaryDetails['TotalDiscount'], 2) ;?></th>
                                                    <th class="text-right text-danger"><?php echo number_format($SummaryDetails['TotalConcessionAmount'], 2) ;?></th>
                                                    <th class="text-right text-danger"><?php echo number_format($SummaryDetails['TotalWaveOffAmount'], 2) ;?></th>
                                                    <th class="text-right text-danger"><?php echo number_format($SummaryDetails['TotalPaidAmount'], 2) ;?></th>
                                                    <th class="text-right text-danger"><?php echo number_format($SummaryDetails['TotalDueAmount'], 2) ;?></th>
                                                </tr>
<?php
                                            }
?>
                                                <tr>
                                                    <th class="">Grand Total: </th>
                                                    <th class="text-right text-success"><?php echo number_format($SummaryTotalAmount, 2) ;?></th>
                                                    <th class="text-right text-success"><?php echo number_format($SummaryTotalDiscount, 2) ;?></th>
                                                    <th class="text-right text-success"><?php echo number_format($SummaryTotalConcessionAmount, 2) ;?></th>
                                                    <th class="text-right text-success"><?php echo number_format($SummaryTotalWaveOffAmount, 2) ;?></th>
                                                    <th class="text-right text-success"><?php echo number_format($SummaryTotalPaidAmount, 2) ;?></th>
                                                    <th class="text-right text-success"><?php echo number_format($SummaryTotalDueAmount, 2) ;?></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

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
                                    //     $AllParameters = array('Process' => '7', 'AcademicYearID' => $Clean['AcademicYearID'], 'ClassID' => $Clean['ClassID'], 'ClassSectionID' => $Clean['ClassSectionID'], 'MonthList' => $SelectedMonths);
                                    //     echo UIHelpers::GetPager('monthly_fee_due_report.php', $TotalPages, $Clean['CurrentPage'], $AllParameters);
                                    // }
?>                                        
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="print-btn-container"><button id="PrintButton" type="submit" class="btn btn-primary">Print</button>
                                         <button id="" onclick="$('#get_excel').val(2); $('#SubmitSearch').click();$('#get_excel').val(0);" type="submit" class="btn btn-primary">Export to Excel</button> 
                                        </div>
                                    </div>
                                </div>
                                <div class="row" id="RecordTableHeading">
                                    <div class="col-lg-12">
                                        <div class="report-heading-container"><strong>Monthly Fee Detailed Report on <?php echo date('d-m-Y h:i A') . $ReportHeaderText; ?></strong></div>
                                    </div>
                                </div>
                                <div class="row" id="RecordTable">
                                    <div class="col-lg-12">
                                        <table width="100%" class="table table-striped table-bordered table-hover" id="DataTableRecords">
                                            <thead>
                                                <tr>
                                                    <th>S.No</th>
                                                    <th>Student Name</th>
                                                    <th>Class</th>
                                                    <th>Mobile No</th>
                                                    <th>Total Amt</th>
                                                    <th>Discount</th>
                                                    <th>Concession</th>
                                                    <th>Wave-Off</th>
                                                    <th>Paid Amt</th>
                                                    <th>Due Amt</th>
                                                    <th class="print-hidden">Details</th>
                                                </tr>
                                            </thead>
                                            <tbody>
<?php
// echo "<pre>";
// var_dump($MonthlyFeeDueDetails);
// echo "</pre>";
                                    if (is_array($MonthlyFeeDueDetails) && count($MonthlyFeeDueDetails) > 0)
                                    {
                                        $Counter = $Start;
                                        $TotalAmount = 0;
                                        $TotalDiscount = 0;
                                        $TotalConcession = 0;
                                        $TotalWaveOff = 0;
                                        $TotalAmountPaid = 0;
                                        $TotalDueAmount = 0;
                                        
                                        foreach ($MonthlyFeeDueDetails as $StudentID => $Details)
                                        {
                                            if ($Details['TotalAmount'] > 0)
                                            {
                                                $TotalAmount += $Details['TotalAmount'];
                                                $TotalDiscount += $Details['DiscountAmount'];
                                                $TotalConcession += $Details['TotalConcession'];
                                                $TotalWaveOff += $Details['TotalWaveOff'];
                                                $TotalAmountPaid += $Details['PaidAmount'];
                                                $TotalDueAmount += $Details['DueAmount'];
?>
                                                <tr>
                                                    <td><?php echo ++$Counter; ?></td>
                                                    <td><?php echo $Details['StudentName']; ?></td>
                                                    <td><?php echo $Details['ClassName'] .'('. $Details['SectionName'] .')'; ?></td>
                                                    <td><?php echo $Details['FatherMobileNumber']; ?></td>
                                                    <td class="text-right"><?php echo number_format($Details['TotalAmount'], 2); ?></td>
                                                    <td class="text-right"><?php echo ($Details['DiscountAmount']) ? number_format($Details['DiscountAmount'], 2) : '--'; ?></td>
                                                    <td class="text-right"><?php echo ($Details['TotalConcession'] > 0) ? number_format($Details['TotalConcession'], 2) : '--'; ?></td>
                                                    <td class="text-right"><?php echo ($Details['TotalWaveOff'] > 0) ? number_format($Details['TotalWaveOff'], 2) : '--'; ?></td>
                                                    <td class="text-right"><?php echo ($Details['PaidAmount']) ? number_format($Details['PaidAmount'], 2) : '--'; ?></td>
                                                    <td class="text-right"><?php echo ($Details['DueAmount']) ? number_format($Details['DueAmount'], 2) : '--'; ?></td>
                                                    <td class="print-hidden"><button type="button" class="btn btn-info btn-sm pull-right FeeDetails" data-toggle="modal" data-target="#ViewFeeDetails" value="<?php echo $StudentID; ?>" classID="<?php echo $Details['ClassID'];?>">Details &nbsp;<i class="fa fa-angle-double-right"></i></button></td>
                                                </tr>
<?php
                                            }
                                        }
?>
                                                <tr class="text-danger">
                                                    <th colspan="4" class="text-right" >Grand Total : </th>
                                                    <th class="text-right"><?php echo number_format($TotalAmount, 2); ?></th>
                                                    <th class="text-right"><?php echo number_format($TotalDiscount, 2); ?></th>
                                                    <th class="text-right"><?php echo number_format($TotalConcession, 2); ?></th>
                                                    <th class="text-right"><?php echo number_format($TotalWaveOff, 2); ?></th>
                                                    <th class="text-right"><?php echo number_format($TotalAmountPaid, 2); ?></th>
                                                    <th class="text-right"><?php echo number_format($TotalDueAmount, 2); ?></th>
                                                    <th class="print-hidden"></th>
                                                </tr>
<?php
                                    }
?>
                                            </tbody>
                                        </table>
                                        </div>
                                    </div>
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
<div id="ViewFeeDetails" class="modal fade" role="dialog">
  <div class="modal-dialog modal-lg">

    <!-- Modal content-->
    <div class="modal-content">
      <div class="modal-header btn-info">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title">Fee Details</h4>
      </div>
      <div class="modal-body">
        <div class="row">
            <div class="col-lg-12" id="FeeDetails"></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
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
    $(".select-date").datepicker({
        changeMonth: true,
        changeYear: true,
        dateFormat: 'dd/mm/yy'
    });

    $('#Class').change(function(){

        var ClassID = parseInt($(this).val());
        
        if (ClassID <= 0)
        {
            $('#ClassSection').html('<option value="0">Select Section</option>');
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
                $('#ClassSection').html('<option value="0">Select Section</option>' + ResultArray[1]);
            }
         });
    });

    $('.FeeDetails').click(function(){
        
        var StudentID = 0;
        AcademicYearID = parseInt($('#AcademicYearID').val());
        StudentID = parseInt($(this).val());
        ClassID = parseInt($(this).attr('ClassID'));

        MonthList = <?php echo json_encode($Clean['MonthList']);?>;

        if (StudentID <= 0)
        {
            alert('Error! No record found.');
            return;
        }
        
        $.post("/xhttp_calls/get_fee_details_by_student.php", {SelectedStudentID:StudentID, SelectedAcademicYearID:AcademicYearID, MonthList:MonthList, ClassID:ClassID}, function(data)
        {
            ResultArray = data.split("|*****|");
            
            if (ResultArray[0] == 'error')
            {
                alert (ResultArray[1]);
                return false;
            }
            else
            {
                $('#FeeDetails').html(ResultArray[1]);
            }
        });
    });
    
    $('#chkAllMonth').change(function(){
        
        if ($(this).prop("checked") == true)
        {
            $('.chkAllMonth').prop('checked',true);
        }
        else
        {
            $('.chkAllMonth').prop('checked',false);
        }
    });
    
    $('.chkAllMonth').change(function(){
        
        var Counter = <?php echo count($AcademicYearMonths);?>;
        
        if ($('input.chkAllMonth:checked').length == Counter) 
        {
            $('#chkAllMonth').prop('checked',true);
        }
        else
        {
            $('#chkAllMonth').prop('checked',false);
        }
    });
});
</script>
<!-- JavaScript To Print A Report -->
<script src="../js/print-report.js"></script>
</body>
</html>
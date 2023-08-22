<?php

require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once("../../classes/class.ui_helpers.php");

require_once('../../classes/fee_management/class.fee_transactions.php');
require_once('../../classes/fee_management/class.fee_collection.php');
require_once("../../classes/fee_management/class.fee_heads.php");
require_once('../../classes/school_administration/class.classes.php');
require_once('../../classes/school_administration/class.class_sections.php');
require_once('../../classes/school_administration/class.academic_years.php');

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

$PaymentModeList = array(1 => 'Cash', 2 => 'Cheque', 3 => 'Net Transfer', 4 => 'Bank Transfer', 5 => 'Card Payment', 6 => 'Wallet');

$AcademicYears =  array();
$AcademicYears = AcademicYear::GetAllAcademicYears();

$ClassList =  array();
$ClassList = AddedClass::GetActiveClasses();

$ActiveFeeHeads = array();
$ActiveFeeHeads = FeeHead::GetActiveFeeHeads();

$ClassSectionsList = array();

$Filters = array();

$FeeTransactionDetails = array();

$RecordDeletedSuccessfully = false;
$HasErrors = false;
$TotalRecords = 0;

$Clean = array();
$Clean['Process'] = 0;

$Clean['AcademicYearID'] = 0;
$Clean['TransactionDate'] = '';
$Clean['TransactionFromDate'] = '';
$Clean['TransactionToDate'] = '';
$Clean['FeeHeadID'] = 0;
$Clean['PaymentMode'] = 0;
$Clean['Description'] = 0;
$Clean['MobileNumber'] = '';

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
     case 5:
        if ($LoggedUser->HasPermissionForTask(TASK_ADD_EDIT_FEE_GROUP) !== true)
        {
            header('location:/admin/unauthorized_login_admin.php');
            exit;
        }
        
        if (isset($_GET['FeeTransactionID']))
        {
            $Clean['FeeTransactionID'] = (int) $_GET['FeeTransactionID'];           
        }
       
        if ($Clean['FeeTransactionID'] <= 0)
        {
            header('location:../error_page.php');
            exit;
        }                       
            
        try
        {
            $FeeTransactionToDelete = new FeeTransaction($Clean['FeeTransactionID']);
        }
        catch (ApplicationDBException $e)
        {
            header('location:../error_page.php');
            exit;
        }
        catch (Exception $e)
        {
            header('location:../error_page.php');
            exit;
        }
           
           $SearchValidator = new Validator();
           
        if (!$FeeTransactionToDelete->Remove())
        {
            $SearchValidator->AttachTextError(ProcessErrors($FeeTransactionToDelete->GetLastErrorCode()));
            $HasErrors = true;
            break;
        }
        
        $RecordDeletedSuccessfully = true;
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
		
        if (isset($_GET['txtTransactionDate']))
        {
            $Clean['TransactionDate'] = strip_tags(trim($_GET['txtTransactionDate']));
        }
        elseif (isset($_GET['TransactionDate']))
        {
            $Clean['TransactionDate'] = strip_tags(trim($_GET['TransactionDate']));
        }

        if (isset($_GET['txtTransactionFromDate']))
        {
            $Clean['TransactionFromDate'] = strip_tags(trim($_GET['txtTransactionFromDate']));
        }
        elseif (isset($_GET['TransactionFromDate']))
        {
            $Clean['TransactionFromDate'] = strip_tags(trim($_GET['TransactionFromDate']));
        }

        if (isset($_GET['txtTransactionToDate']))
        {
            $Clean['TransactionToDate'] = strip_tags(trim($_GET['txtTransactionToDate']));
        }
        elseif (isset($_GET['TransactionToDate']))
        {
            $Clean['TransactionToDate'] = strip_tags(trim($_GET['TransactionToDate']));
        }
        
        if (isset($_GET['drdFeeHead']))
        {
            $Clean['FeeHeadID'] = strip_tags(trim($_GET['drdFeeHead']));
        }
        elseif (isset($_GET['FeeHeadID']))
        {
            $Clean['FeeHeadID'] = strip_tags(trim($_GET['FeeHeadID']));
        }
        
        if (isset($_GET['txtMobileNumber']))
        {
            $Clean['MobileNumber'] = strip_tags(trim($_GET['txtMobileNumber']));
        }
        else if (isset($_GET['MobileNumber']))
        {
            $Clean['MobileNumber'] = strip_tags(trim( (string) $_GET['MobileNumber']));
        }

        if (isset($_GET['optPaymentMode']))
        {
            $Clean['PaymentMode'] = strip_tags(trim($_GET['optPaymentMode']));
        }
        elseif (isset($_GET['PaymentMode']))
        {
            $Clean['PaymentMode'] = strip_tags(trim($_GET['PaymentMode']));
        }
        
        if (isset($_GET['chkDescription']))
        {
            $Clean['Description'] = $_GET['chkDescription'];
        }
        elseif (isset($_GET['Description']))
        {
            $Clean['Description'] = strip_tags(trim($_GET['Description']));
        }

        $SearchValidator = new Validator();

        if ($Clean['TransactionDate'] != '')
        {
            $SearchValidator->ValidateDate($Clean['TransactionDate'], 'Please enter valid transaction date.');
        }

        if ($Clean['TransactionFromDate'] != '')
        {
            $SearchValidator->ValidateDate($Clean['TransactionFromDate'], 'Please enter valid transaction from date.');
            $SearchValidator->ValidateDate($Clean['TransactionToDate'], 'Please enter valid transaction to date.');
        }
        
        if ($Clean['FeeHeadID'] > 0)
        {
            $SearchValidator->ValidateInSelect($Clean['FeeHeadID'], $ActiveFeeHeads, 'Please select a valid fee head.');
        }
        
        if ($Clean['MobileNumber'] != '')
        {
            $SearchValidator->ValidateStrings($Clean['MobileNumber'], 'Mobile number should be between 1 to 15.', 1, 15);
        }
        
        if ($Clean['PaymentMode'] != 0) 
        {
            $SearchValidator->ValidateInSelect($Clean['PaymentMode'], $PaymentModeList, 'Please select valid payment mode.');
        }

        if ($SearchValidator->HasNotifications())
        {
            $HasErrors = true;
            break;
        }

        //set record filters    
        $Filters['AcademicYearID'] = $Clean['AcademicYearID'];
        if ($Clean['TransactionDate'] != '') 
        {
            $Filters['TransactionDate'] = date('Y-m-d', strtotime(DateProcessing::ToggleDateDayAndMonth(($Clean['TransactionDate']))));
        }    
        
        if ($Clean['TransactionFromDate'] != '') 
        {
            $Filters['TransactionFromDate'] = date('Y-m-d', strtotime(DateProcessing::ToggleDateDayAndMonth(($Clean['TransactionFromDate']))));
        }
        
        if ($Clean['TransactionToDate'] != '') 
        {
            $Filters['TransactionToDate'] = date('Y-m-d', strtotime(DateProcessing::ToggleDateDayAndMonth(($Clean['TransactionToDate']))));
        }
        
        $Filters['FeeHeadID'] = $Clean['FeeHeadID'];
        
        $Filters['MobileNumber'] = $Clean['MobileNumber'];

        if ($Clean['PaymentMode'] != 0) 
        {
            $Filters['PaymentMode'] = $Clean['PaymentMode'];
        }
        
        if ($Clean['Description'] != 0) 
        {
            $Filters['Description'] = $Clean['Description'];
        }
        
        //get records count
        FeeCollection::SearchFeeTransactions($TotalRecords, true, $Filters);

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
                $FeeTransactionDetails = FeeCollection::SearchFeeTransactions($TotalRecords, false, $Filters, 0, $TotalRecords);
            }
            else
            {
                $FeeTransactionDetails = FeeCollection::SearchFeeTransactions($TotalRecords, false, $Filters, $Start, $Limit);
            }
            
            if (isset($_GET['report_submit']) && $_GET['report_submit'] == 2)
            {
                require_once('../excel/fee_transaction_report_download_xls.php');
            }
        }
        // echo "<pre>";
        // var_dump($FeeTransactionDetails);exit;
        break;

}

$LandingPageMode = '';
if (isset($_GET['Mode']))
{
    $LandingPageMode = $_GET['Mode'];
}

require_once('../html_header.php');
?>
<title>Fee Transaction Report</title>
<!-- DataTables CSS -->
<link href="../vendor/datatables-plugins/dataTables.bootstrap.css" rel="stylesheet">

<!-- DataTables Responsive CSS -->
<link href="../vendor/datatables-responsive/dataTables.responsive.css" rel="stylesheet">
<link href="../vendor/jquery-ui/jquery-ui.min.css" rel="stylesheet">
<style>
    #tooltip-top
    {
    color: red;
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
                    <h1 class="page-header">Fee Transaction Report</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
            <form class="form-horizontal" name="frmRoomReport" action="fee_transaction_report.php" method="get">
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
                                	<!--<input class="form-control" type="text" maxlength="10" id="AcademicYear" name="txtAcademicYear" readonly="readonly" value="<?php echo $AcademicYearName; ?>" />-->
                                	<select class="form-control" name="drdAcademicYear" id="AcademicYearID">
                                	    <option  value="0" >-- All Academic Year --</option>
    <?php
                                    if (is_array($AcademicYears) && count($AcademicYears) > 0)
                                    {
                                        foreach ($AcademicYears as $AcademicYearID => $AcademicYearDetails)
                                        {
                                            if ($Clean['AcademicYearID'] == 0)
                                            {
                                                if ($AcademicYearDetails['IsCurrentYear'] == 1)
                                                {
                                                    // $Clean['AcademicYearID'] = $AcademicYearID;   
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
                                <label for="TransactionDate" class="col-lg-2 control-label">Transaction Date</label>
                                <div class="col-lg-3">
                                    <input class="form-control select-date" type="text" maxlength="10" id="TransactionDate" name="txtTransactionDate" value="<?php echo $Clean['TransactionDate']; ?>" />
                                </div>
                                <!-- <label for="FeeHeadList" class="col-lg-2 control-label">Fee Head</label>
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
                                </div>  -->
                            </div>
                            <div class="form-group">                            
                                <label for="TransactionFromDate" class="col-lg-2 control-label">Transaction Between</label>
                                <div class="col-lg-3">
                                    <input class="form-control select-date" type="text" maxlength="10" id="TransactionFromDate" name="txtTransactionFromDate" value="<?php echo $Clean['TransactionFromDate']; ?>" />
                                </div>
                                <label for="TransactionToDate" class="col-lg-2 control-label">to</label>
                                <div class="col-lg-3">
                                    <input class="form-control select-date" type="text" maxlength="10" id="TransactionToDate" name="txtTransactionToDate" value="<?php echo $Clean['TransactionToDate']; ?>" />
                                </div>
                            </div>
                            <div class="form-group">
                                  <label for="PaymentMode" class="col-lg-2 control-label">Payment Mode</label>
                                    <div class="col-lg-8">
                                        <label class="radio-inline">
                                            <input type="radio" <?php echo (($Clean['PaymentMode'] == 0) ? 'checked="checked"' : ''); ?> name="optPaymentMode" id="PaymentMode" value="0" checked>All
                                        </label>
<?php
                                    foreach ($PaymentModeList as $PaymentModeID => $PaymentModeName) 
                                    {
?>
                                        <label class="radio-inline">
                                            <input type="radio" <?php echo (($Clean['PaymentMode'] == $PaymentModeID) ? 'checked="checked"' : ''); ?> name="optPaymentMode" class="PaymentMode" value="<?php echo $PaymentModeID; ?>"><?php echo $PaymentModeName; ?>
                                        </label>
<?php                                       
                                    }
?>  
                                    </div> 
                            </div>
                            <div class="form-group">                            
                                <label for="MobileNumber" class="col-lg-2 control-label">Mobile Number</label>
                                <div class="col-lg-7">
                                    <input class="form-control" type="text" maxlength="50" id="MobileNumber" name="txtMobileNumber" value="<?php echo $Clean['MobileNumber']; ?>" />
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="Description" class="col-lg-2 control-label">Only Description</label>
                                <div class="col-lg-4">
                                    <input type="checkbox" id="Description" name="chkDescription" <?php echo ($Clean['Description'] == 1) ? 'checked="checked"' : ''; ?> value="1" />
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
            
            if ($Clean['TransactionDate'] != '')
            {
                $ReportHeaderText .= ' Transaction Date : ' . $Clean['TransactionDate'] . ',';
            }

            if ($Clean['TransactionFromDate'] != '')
            {
                $ReportHeaderText .= ' Transaction Between : ' . $Clean['TransactionFromDate'] .' and '. $Clean['TransactionFromDate'] . ',';
            }
            
            if ($Clean['FeeHeadID'] > 0)
            {
                $ReportHeaderText .= ' Fee Head : ' . $ActiveFeeHeads[$Clean['FeeHeadID']]['FeeHead'] . ',';
            }
            
            if ($Clean['MobileNumber'] != '')
            {
                $ReportHeaderText .= ' Mobile Number : ' . $Clean['MobileNumber'] . ',';
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
                                    if ($TotalPages > 1)
                                    {
                                        $AllParameters = array('Process' => '7', 'AcademicYearID' => $Clean['AcademicYearID'], 'TransactionDate' => $Clean['TransactionDate'], 'TransactionFromDate' => $Clean['TransactionFromDate'], 'TransactionToDate' => $Clean['TransactionToDate'], 'MobileNumber' => $Clean['MobileNumber'], 'PaymentMode' => $Clean['PaymentMode']);
                                        echo UIHelpers::GetPager('fee_transaction_report.php', $TotalPages, $Clean['CurrentPage'], $AllParameters);
                                    }
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
                                        <div class="report-heading-container"><strong>Fee Transaction Report on <?php echo date('d-m-Y h:i A') . $ReportHeaderText; ?></strong></div>
                                    </div>
                                </div>
                                <div class="row" id="RecordTable">
                                    <div class="col-lg-12">
                                        <table width="100%" class="table table-striped table-bordered table-hover" id="DataTableRecords">
                                            <thead>
                                                <tr>
                                                    <th>S.No</th>
                                                    <th>Tr. ID</th>
                                                    <th>Student Name</th>
                                                    <th>Father Name</th>
                                                    <th>Mobile Number</th>
                                                    <th>Paid Amt</th>
                                                    <th>Fee Date</th>
                                                    <th>Pmt. Mode</th>
                                                    <th>Description</th>
                                                    <th class="print-hidden">Create User</th>
                                                    <th class="print-hidden">Create Date</th>
                                                    <!-- <th class="print-hidden">Details</th> -->
                                                    <th class="print-hidden">Opt</th>
                                                </tr>
                                            </thead>
                                            <tbody>
<?php
                                    if (is_array($FeeTransactionDetails) && count($FeeTransactionDetails) > 0)
                                    {
                                        $Counter = $Start;
                                        $TotalAmount = 0;
                                        $TotalDiscount = 0;
                                        $TotalAmountPaid = 0;
                                        
                                        $CashAmount = 0;
                                        $ChequeAmount = 0;
                                        $CardAmount = 0;
                                        $BankAmount = 0;
                                        $NetTransferAmount = 0;
                                        $WalletAmount = 0;
                                        
                                        foreach ($FeeTransactionDetails as $FeeTransactionID => $Details)
                                        {
                                            if ($FeeTransactionID > 0)
                                            {
                                                $TotalAmountPaid += $Details['TransactionAmount'];
?>
                                                <tr>
                                                    <td><?php echo ++$Counter; ?></td>
                                                    <td><?php echo $FeeTransactionID; ?></td>
                                                    <td>
<?php 
                                                    foreach ($Details['StudentDetails'] as $StudentID => $StudentDetails) 
                                                    {
                                                        echo $StudentDetails['FirstName'] .' '. $StudentDetails['LastName'] .' <br>'; 
                                                    }
?>
                                                            
                                                    </td>
                                                    <td><?php echo $Details['FatherName']; ?></td>
                                                    <td><?php echo $Details['FatherMobileNumber']; ?></td>
                                                    <td class="text-right"><?php echo $Details['TransactionAmount']; ?></td>
                                                    <td><?php echo date('d/m/Y', strtotime($Details['FeeDate'])); ?></td>
                                                    <td>
<?php 
                                                    if ($Details['TransactionAmount'] > 0)
                                                    {
                                                        foreach ($Details['PaymentModeDetails'] as $PaymentMode => $ModeAmount) 
                                                        {
                                                            if ($PaymentMode == 1)
                                                            {
                                                                $CashAmount += $ModeAmount;
                                                            }
                                                            
                                                            if ($PaymentMode == 2)
                                                            {
                                                                $ChequeAmount += $ModeAmount;
                                                            }
                                                            
                                                            if ($PaymentMode == 3)
                                                            {
                                                                $NetTransferAmount += $ModeAmount;
                                                            }
                                                            
                                                            if ($PaymentMode == 4)
                                                            {
                                                                $BankAmount += $ModeAmount;
                                                            }
                                                            
                                                            if ($PaymentMode == 5)
                                                            {
                                                                $CardAmount += $ModeAmount;
                                                            }
                                                            
                                                            if ($PaymentMode == 6)
                                                            {
                                                                $WalletAmount += $ModeAmount;
                                                            }
                                                            
                                                            echo $PaymentModeList[$PaymentMode] .' ('. $ModeAmount .')<br>'; 
                                                        }
                                                    }
                                                    else
                                                    {
                                                        echo 'Wave-Off';
                                                    }
?>
                                                            
                                                    </td>
                                                    <td class="print-hidden"><?php echo ($Details['Description'] != '') ? substr($Details['Description'], 0, 40).'...<i class="fa fa-info-circle" id="tooltip-top" data-toggle="tooltip" title ="'.$Details['Description'].'" aria-hidden="true"></i>' : '';?></td>
                                                    <td class="print-hidden"><?php echo $Details['CreateUserName']; ?></td>
                                                    <td class="print-hidden"><?php echo date('d/m/Y', strtotime($Details['CreateDate'])); ?></td>
                                                    <!-- <td class="print-hidden"><button type="button" class="btn btn-info btn-sm pull-right FeeDetails" data-toggle="modal" data-target="#ViewFeeDetails" value="<?php echo $FeeTransactionID; ?>">Details &nbsp;<i class="fa fa-angle-double-right"></i></button></td> -->
                                                    <td class="print-hidden">
<?php
                                                    
                                                    if ($LoggedUser->HasPermissionForTask(TASK_ADD_EDIT_FEE_GROUP) === true)
                                                    {
                                                        echo '<a href="fee_transaction_report.php?Process=5&amp;FeeTransactionID=' . $FeeTransactionID . '" class="delete-record">Delete</a>';
                                                    }
                                                    else
                                                    {
                                                        echo 'Delete';
                                                    }
                                                    
                                                    echo '&nbsp;|&nbsp;<a href="fee_receipt.php?FeeTransactionID=' . $FeeTransactionID . '">Receipt</a>';
?>
                                                        
                                                    </td>
                                                </tr>
<?php
                                            }
                                        }
?>
                                                <tr>
                                                    <th colspan="5">Grand Total : </th>
                                                    <th class="text-right"><?php echo number_format($TotalAmountPaid, 2); ?></th>
                                                    <th></th>
                                                    <th>
<?php
                                                        if ($CashAmount > 0)
                                                        {
                                                            echo 'Cash (' . number_format($CashAmount, 2) . ') <br>';
                                                        }
                                                        
                                                        if ($ChequeAmount > 0)
                                                        {
                                                            echo 'Cheque (' . number_format($ChequeAmount, 2) . ') <br>';
                                                        }
                                                        
                                                        if ($CardAmount > 0)
                                                        {
                                                            echo 'Card (' . number_format($CardAmount, 2) . ') <br>';
                                                        }
                                                        
                                                        if ($BankAmount > 0)
                                                        {
                                                            echo 'Bank (' . number_format($BankAmount, 2) . ') <br>';
                                                        }
                                                        
                                                        if ($NetTransferAmount > 0)
                                                        {
                                                            echo 'Net (' . number_format($NetTransferAmount, 2) . ') <br>';
                                                        }
                                                        
                                                        if ($WalletAmount > 0)
                                                        {
                                                            echo 'Wallet (' . number_format($WalletAmount, 2) . ') <br>';
                                                        }
?>
                                                    </th>
                                                    <!-- <th class="print-hidden"></th> -->
                                                    <th class="print-hidden"></th>
                                                    <th class="print-hidden"></th>
                                                    <th class="print-hidden"></th>
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
  <div class="modal-dialog">

    <!-- Modal content-->
    <div class="modal-content">
      <div class="modal-header btn-info">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title">Transaction Details</h4>
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
    
    $('[data-toggle="tooltip"]').tooltip();
    
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
    
    $('.FeeDetails').click(function(){

        var FeeCollectionID = 0;
        FeeCollectionID = parseInt($(this).val());
       
        if (FeeCollectionID <= 0)
        {
            alert('Error! No record found.');
            return;
        }
        
        $.post("/xhttp_calls/get_fee_collection_details_by_transaction.php", {SelectedFeeCollectionID:FeeCollectionID}, function(data)
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
});
</script>
<!-- JavaScript To Print A Report -->
<script src="../js/print-report.js"></script>
</body>
</html>
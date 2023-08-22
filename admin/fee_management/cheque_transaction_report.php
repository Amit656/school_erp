<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once("../../classes/class.ui_helpers.php");

require_once('../../classes/fee_management/class.fee_collection.php');
require_once('../../classes/school_administration/class.classes.php');
require_once('../../classes/school_administration/class.class_sections.php');
require_once('../../classes/school_administration/class.academic_years.php');

require_once('../../classes/school_administration/class.students.php');
require_once('../../classes/school_administration/class.student_details.php');

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

if ($LoggedUser->HasPermissionForTask(TASK_CHEQUE_TRANSACTION_REPORT) !== true)
{
    header('location:/admin/unauthorized_login_admin.php');
    exit;
}

$PaymentModeList = array(1 => 'Cash', 2 => 'Cheque', 3 => 'Net Transfer', 4 => 'Bank Transfer', 5 => 'Card Payment');
$ChequeStatusList = array('All' => 'All', 'Pending' => 'Pending', 'Cleared' => 'Cleared', 'Bounced' => 'Bounced');

$AcademicYears =  array();
$AcademicYears = AcademicYear::GetAllAcademicYears();

$ClassList =  array();
$ClassList = AddedClass::GetActiveClasses();

$ClassSectionsList = array();
$StudentsList =  array();

$Filters = array();

$ChequeTransactionDetails = array();

$RecordDeletedSuccessfully = false;
$HasErrors = false;
$TotalRecords = 0;

$Clean = array();
$Clean['Process'] = 0;

$Clean['AcademicYearID'] = 0;
$Clean['ClassID'] = 0;
$Clean['ClassSectionID'] = 0;
$Clean['StudentID'] = 0;

$Clean['TransactionDate'] = '';
$Clean['TransactionFromDate'] = '';
$Clean['TransactionToDate'] = '';
$Clean['StudentName'] = '';
$Clean['ChequeReferenceNo'] = '';
$Clean['ChequeStatus'] = 'All';

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
        
        if (isset($_GET['FeeCollectionID']))
        {
            $Clean['FeeCollectionID'] = (int) $_GET['FeeCollectionID'];           
        }
       
        if ($Clean['FeeCollectionID'] <= 0)
        {
            header('location:../error_page.php');
            exit;
        }                       
            
        try
        {
            $FeeCollectionToDelete = new FeeCollection($Clean['FeeCollectionID']);
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
           
        if (!$FeeCollectionToDelete->Remove())
        {
            $SearchValidator->AttachTextError(ProcessErrors($FeeCollectionToDelete->GetLastErrorCode()));
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
            $Clean['StudentID'] = strip_tags(trim($_GET['drdStudent']));
        }
        elseif (isset($_GET['StudentID']))
        {
            $Clean['StudentID'] = strip_tags(trim($_GET['StudentID']));
        }
        
        if (isset($_GET['txtStudentName']))
        {
            $Clean['StudentName'] = strip_tags(trim($_GET['txtStudentName']));
        }
        elseif (isset($_GET['StudentName']))
        {
            $Clean['StudentName'] = strip_tags(trim($_GET['StudentName']));
        }

        if (isset($_GET['txtChequeReferenceNo']))
        {
            $Clean['ChequeReferenceNo'] = strip_tags(trim($_GET['txtChequeReferenceNo']));
        }
        elseif (isset($_GET['ChequeReferenceNo']))
        {
            $Clean['ChequeReferenceNo'] = strip_tags(trim($_GET['ChequeReferenceNo']));
        }

        if (isset($_GET['optChequeStatus']))
        {
            $Clean['ChequeStatus'] = strip_tags(trim($_GET['optChequeStatus']));
        }
        elseif (isset($_GET['ChequeStatus']))
        {
            $Clean['ChequeStatus'] = strip_tags(trim($_GET['ChequeStatus']));
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
        

        if ($Clean['ClassID'] > 0)
        {
            $SearchValidator->ValidateInSelect($Clean['ClassID'], $ClassList, 'Please select a valid class.');
            $ClassSectionsList = ClassSections::GetClassSections($Clean['ClassID']);
        }
        
        if ($Clean['ClassSectionID'] > 0)
        {
            $SearchValidator->ValidateInSelect($Clean['ClassSectionID'], $ClassSectionsList, 'Please select a valid section.');
            
            $StudentsList = StudentDetail::GetStudentsByClassSectionID($Clean['ClassSectionID'], 'Active', $Clean['AcademicYearID']);
            
            if ($Clean['StudentID'] > 0)
            {
                $SearchValidator->ValidateInSelect($Clean['StudentID'], $StudentsList, 'Please select a valid student.');   
            }
        }

        if ($Clean['StudentName'] != '')
        {
            $SearchValidator->ValidateStrings($Clean['StudentName'], 'Student name should be between 2 to 50.', 2, 50);
        }
        
        if ($SearchValidator->HasNotifications())
        {
            $HasErrors = true;
            break;
        }

        //set record filters    
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

        $Filters['AcademicYearID'] = $Clean['AcademicYearID'];
        $Filters['ClassID'] = $Clean['ClassID'];
        $Filters['ClassSectionID'] = $Clean['ClassSectionID'];
        $Filters['StudentID'] = $Clean['StudentID'];
        $Filters['StudentName'] = $Clean['StudentName'];
        $Filters['ChequeReferenceNo'] = $Clean['ChequeReferenceNo'];

        if ($Clean['ChequeStatus'] != 'All') 
        {
            $Filters['ChequeStatus'] = $Clean['ChequeStatus'];
        }
        
        //get records count
        FeeCollection::SearchChequeTransactionDetails($TotalRecords, true, $Filters);

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
                $ChequeTransactionDetails = FeeCollection::SearchChequeTransactionDetails($TotalRecords, false, $Filters, 0, $TotalRecords);
            }
            else
            {
                $ChequeTransactionDetails = FeeCollection::SearchChequeTransactionDetails($TotalRecords, false, $Filters, $Start, $Limit);
            }
            
            if (isset($_GET['report_submit']) && $_GET['report_submit'] == 2)
            {
                require_once('../excel/cheque_transaction_report_download_xls.php');
            }
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
<title>Cheque Transaction Report</title>
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
                    <h1 class="page-header">Cheque Transaction Report</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
            <form class="form-horizontal" name="frmRoomReport" action="cheque_transaction_report.php" method="get">
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
                            </div>
                            <div class="form-group">                            
                                <label for="TransactionFromDate" class="col-lg-2 control-label">Transaction Between</label>
                                <div class="col-lg-3">
                                    <input class="form-control select-date" type="text" maxlength="10" id="TransactionFromDate" name="txtTransactionFromDate" value="<?php echo $Clean['TransactionFromDate']; ?>" />
                                </div>
                                <label for="TransactionToDate" class="col-lg-1 control-label">to</label>
                                <div class="col-lg-3">
                                    <input class="form-control select-date" type="text" maxlength="10" id="TransactionToDate" name="txtTransactionToDate" value="<?php echo $Clean['TransactionToDate']; ?>" />
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
                                <label for="ClassSection" class="col-lg-1 control-label">Section</label>
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
                                <label for="Student" class="col-lg-2 control-label">Select Student</label>
                                <div class="col-lg-7">
                                    <select class="form-control" name="drdStudent" id="Student">
                                        <option value="0">-- All Student --</option>
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
                            <div class="form-group">                            
                                <label for="StudentName" class="col-lg-2 control-label">Student Name</label>
                                <div class="col-lg-7">
                                    <input class="form-control" type="text" maxlength="50" id="StudentName" name="txtStudentName" value="<?php echo $Clean['StudentName']; ?>" />
                                </div>
                            </div>

                            <div class="form-group">                            
                                <label for="ChequeReferenceNo" class="col-lg-2 control-label">Cheque No.</label>
                                <div class="col-lg-3">
                                    <input class="form-control" type="text" maxlength="50" id="ChequeReferenceNo" name="txtChequeReferenceNo" value="<?php echo $Clean['ChequeReferenceNo']; ?>" />
                                </div>
                                <label for="ChequeStatus" class="col-lg-1 control-label">Status</label>
                                <div class="col-lg-5">
<?php
                                foreach ($ChequeStatusList as $ChequeStatusID => $ChequeStatus) 
                                {
?>
                                    <label class="col-sm-3"><input class="custom-radio" type="radio" id="<?php echo $ChequeStatusID; ?>" name="optChequeStatus" value="<?php echo $ChequeStatus; ?>" <?php echo ($ChequeStatus == $Clean['ChequeStatus']) ? 'checked="checked"' : ''; ?>>&nbsp;&nbsp;<?php echo $ChequeStatus; ?></label>
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

            if ($Clean['ClassID'] > 0)
            {
                $ReportHeaderText .= ' Class : ' . $ClassList[$Clean['ClassID']] . ',';
            }

            if ($Clean['ClassSectionID'] > 0)
            {
                $ReportHeaderText .= ' Section : ' . $ClassSectionsList[$Clean['ClassSectionID']] . ',';
            }
            
            if ($Clean['StudentID'] > 0)
            {
                $ReportHeaderText .= ' Student : ' . $StudentsList[$Clean['StudentID']]['FirstName'] . ',';
            }
            
            if ($Clean['StudentName'] != '')
            {
                $ReportHeaderText .= ' Student Name : ' . $Clean['StudentName'] . ',';
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
                                        $AllParameters = array('Process' => '7', 'AcademicYearID' => $Clean['AcademicYearID'], 'TransactionDate' => $Clean['TransactionDate'], 'TransactionFromDate' => $Clean['TransactionFromDate'], 'TransactionToDate' => $Clean['TransactionToDate'], 'ClassID' => $Clean['ClassID'], 'ClassSectionID' => $Clean['ClassSectionID'], 'StudentID' => $Clean['StudentID'], 'StudentName' => $Clean['StudentName']);
                                        echo UIHelpers::GetPager('cheque_transaction_report.php', $TotalPages, $Clean['CurrentPage'], $AllParameters);
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
                                        <div class="report-heading-container"><strong>Cheque Transaction Report on <?php echo date('d-m-Y h:i A') . $ReportHeaderText; ?></strong></div>
                                    </div>
                                </div>
                                <div class="row" id="RecordTable">
                                    <div class="col-lg-12">
                                        <table width="100%" class="table table-striped table-bordered table-hover" id="DataTableRecords">
                                            <thead>
                                                <!-- <tr><th colspan="13"><?php echo (count($ChequeTransactionDetails) > 0) ? 'Year: '. $ChequeTransactionDetails['AcademicYearName'] : ''; ?></th></tr> -->
                                                <tr>
                                                    <th>S.No</th>
                                                    <th>Tr. ID</th>
                                                    <th>Student Name</th>
                                                    <th>Class</th>
                                                    <th>Cheque Amt</th>
                                                    <th>Fee Date</th>
                                                    <th>Cheque No</th>
                                                    <th>Status</th>
                                                    <!-- <th class="print-hidden">Tr. Date</th> -->
                                                    <th class="print-hidden">Status Changed By</th>
                                                    <th class="print-hidden">Status Changed Date</th>
                                                </tr>
                                            </thead>
                                            <tbody>
<?php
                                    if (is_array($ChequeTransactionDetails) && count($ChequeTransactionDetails) > 0)
                                    {
                                        $Counter = $Start;
                                        $TotalAmount = 0;
                                        $TotalDiscount = 0;
                                        $TotalAmountPaid = 0;
                                        
                                        foreach ($ChequeTransactionDetails as $FeePaymentModeDetailID => $Details)
                                        {
                                            if ($FeePaymentModeDetailID > 0)
                                            {
                                                $TotalAmount += $Details['Amount'];
?>
                                                <tr>
                                                    <td><?php echo ++$Counter; ?></td>
                                                    <td><?php echo $Details['FeeTransactionID']; ?></td>
                                                    <td><?php echo $Details['StudentName']; ?></td>
                                                    <td><?php echo $Details['ClassName'] .'('. $Details['SectionName'] .')'; ?></td>
                                                    <td><?php echo $Details['Amount']; ?></td>
                                                    <td><?php echo date('d/m/Y', strtotime($Details['FeeDate'])); ?></td>

                                                    <td><?php echo $Details['ChequeReferenceNo']; ?></td>
                                                    <td class="<?php echo ($Details['ChequeStatus'] == 'Cleared') ? 'text-success' : 'text-danger' ; ?>">
<?php 
                                                    if ($Details['ChequeStatus'] == 'Pending') 
                                                    {
                                                        echo '<button type="button" class="btn btn-success btn-sm ChequeClearBtn print-hidden" data-toggle="tooltip" title="Clear" id="Clear'. $FeePaymentModeDetailID.'"><span class="glyphicon glyphicon-ok"></span> </button>&nbsp;&nbsp;';

                                                        echo '<button type="button" class="btn btn-danger btn-sm ChequeBounceBtn print-hidden" data-toggle="tooltip" title="Bounce" id="Bounce'. $FeePaymentModeDetailID.'"><span class="glyphicon glyphicon-remove"></span> </button>';
                                                    }
                                                    else
                                                    {
                                                        echo $Details['ChequeStatus']; 
                                                    }
?>
                                                        
                                                    </td>

                                                    <td class="print-hidden"><?php echo $Details['StatusChangedBy']; ?></td>
                                                    <td class="print-hidden"><?php echo ($Details['StatusChangedDate'] != '0000-00-00') ? date('d/m/Y', strtotime($Details['StatusChangedDate'])) : ''; ?></td>
                                                </tr>
<?php
                                            }
                                        }
?>
                                                <!-- <tr>
                                                    <th colspan="4">Grand Total : </th>
                                                    <th><?php echo number_format($TotalAmount, 2); ?></th>
                                                    <th></th>
                                                    <th></th>
                                                    <th></th>
                                                    
                                                    <th class="print-hidden"></th>
                                                    <th class="print-hidden"></th>
                                                </tr> -->
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

    $("body").on('click', '.ChequeClearBtn', function()
    {   
        if (!confirm("Are you sure to clear this cheque?"))
        {
            return false;
        }

        var FeePaymentModeDetailID = $(this).attr('id').slice(5);
        var ChequeStatus = 'Cleared';

        var This = $(this);
        
        $.post("/xhttp_calls/update_cheque_status.php", {FeePaymentModeDetailID:FeePaymentModeDetailID, ChequeStatus:ChequeStatus}, function(data)
        {
            ResultArray = data.split("|*****|");
            
            if (ResultArray[0] == 'error')
            {
                alert (ResultArray[1]);
                return false;
            }
            else
            {
                This.parent().html('<span class="text-success">Cleared</span>');
            }
        });
    });

    $("body").on('click', '.ChequeBounceBtn', function()
    {   
        var ChequeBouncedDescription = '';
            ChequeBouncedDescription = prompt('Are you sure to bounce this cheque?\n Enter Description (if any).');

            if (ChequeBouncedDescription == null)
            {
                return false;
            }

        var FeePaymentModeDetailID = $(this).attr('id').slice(6);
        var ChequeStatus = 'Bounced';

        var This = $(this);
        
        $.post("/xhttp_calls/update_cheque_status.php", {FeePaymentModeDetailID:FeePaymentModeDetailID, ChequeStatus:ChequeStatus, ChequeBouncedDescription:ChequeBouncedDescription}, function(data)
        {
            ResultArray = data.split("|*****|");
            
            if (ResultArray[0] == 'error')
            {
                alert (ResultArray[1]);
                return false;
            }
            else
            {
                This.parent().html('<span class="text-danger">Bounced</span>');
            }
        });
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
            $('#Student').html('<option value="0">-- All Student --</option>');
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
                $('#Student').html('<option value="0">-- All Student --</option>' + ResultArray[1]);
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
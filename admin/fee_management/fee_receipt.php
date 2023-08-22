<?php
require_once('../../classes/class.users.php');
require_once('../../classes/class.validation.php');
require_once('../../classes/class.authentication.php');

require_once('../../classes/class.ui_helpers.php');

require_once('../../classes/school_administration/class.academic_years.php');
require_once('../../classes/school_administration/class.classes.php');
require_once('../../classes/school_administration/class.class_sections.php');

require_once('../../classes/school_administration/class.students.php');
require_once('../../classes/school_administration/class.student_details.php');
require_once('../../classes/school_administration/class.parent_details.php');

require_once('../../classes/fee_management/class.fee_collection.php');
// require_once('../../classes/fee_management/class.fee_receipt_terms_condition.php');

require_once('../../includes/global_defaults.inc.php');

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

if ($LoggedUser->HasPermissionForTask(TASK_FEE_COLLECTION_REPORT) !== true)
{
    header('location:/admin/unauthorized_login_admin.php');
    exit;
}

// END OF 1. //

$ClassList =  array();
$ClassList = AddedClass::GetActiveClasses();

$AcademicYearID = 0;
$AcademicYearName = '';

$AcademicYearID = AcademicYear::GetCurrentAcademicYear($AcademicYearName);

$AcademicYears =  array();
$AcademicYears = AcademicYear::GetAllAcademicYears();

$PaymentModeList = array(1 => 'Cash', 2 => 'Cheque', 3 => 'Net Transfer', 4 => 'Bank Transfer', 5 => 'Card Payment');

$Filters = array();

$FeeCollectionDetails = array();

$AllFeeTermconditions = array();
// $AllFeeTermconditions = FeeReceiptTermCondition::GetAllTermConditionMessage();

$RecordDeletedSuccessfully = false;
$HasErrors = false;
$TotalRecords = 0;

$Clean = array();
$Clean['Process'] = 0;

$Clean['AcademicYearID'] = 1;
$Clean['FeeTransactionID'] = '';
$SelectedFeeCollectionIDs = array();
$StudentDetails = array();
$TransactionDetails = array();

// Additional Variable

$SchoolName = 'LUCKNOW INTERNATIONAL PUBLIC SCHOOL';
$AffilationDetails = 'Affiliated C.B.S.E Board New Delhi';
$Address = 'N.H. 24, Chandpur-Khanipur (Near Itaunja)';
$Pincode = 226003;
$City = 'Sitapur Road, Lucknow';
$State = 'UP';
$ContactNumber = '0535-2634282';
$AffilationNumber = '2132953';

$FeeDate = '';

if (isset($_GET['FeeTransactionID']))
{
    $Clean['FeeTransactionID'] = (string) $_GET['FeeTransactionID'];
}

if ($Clean['FeeTransactionID'] <= 0)
{
    header('location:admin/error.php');
    exit;
}

$SelectedFeeCollectionIDs = FeeCollection::GetFeeCollectionIDsByTransactionID($Clean['FeeTransactionID']);

if (count($SelectedFeeCollectionIDs) <= 0)
{
    header('location:admin/error.php');
    exit;
}

try
{
    foreach ($SelectedFeeCollectionIDs as $Key => $FeeCollectionID) 
    {
        $FeeCollectionObject = new FeeCollection($FeeCollectionID);
        $StudentDetailObject = new StudentDetail($FeeCollectionObject->GetStudentID());
        
        $Clean['AcademicYearID'] = $StudentDetailObject->GetAcademicYearID();
        
        $StudentDetails[$FeeCollectionObject->GetStudentID()]['StudentName'] = $StudentDetailObject->GetFirstName() . ' ' . $StudentDetailObject->GetLastName();

        $ClassSectionDetails = new ClassSections($StudentDetailObject->GetClassSectionID());

        $ClassSectionsList = ClassSections::GetClassSections($ClassSectionDetails->GetClassID());

        $StudentDetails[$FeeCollectionObject->GetStudentID()]['ClassName'] = $ClassList[$ClassSectionDetails->GetClassID()] . ' ' . $ClassSectionsList[$StudentDetailObject->GetClassSectionID()];

        $StudentDetails[$FeeCollectionObject->GetStudentID()]['RollNumber'] = ($StudentDetailObject->GetRollNumber() ? $StudentDetailObject->GetRollNumber() : '--');
        $StudentDetails[$FeeCollectionObject->GetStudentID()]['Address'] = ($StudentDetailObject->GetAddress1() ? $StudentDetailObject->GetAddress1() : '--');

        $NewParentDetailObject = new ParentDetail($StudentDetailObject->GetParentID());

        $StudentDetails[$FeeCollectionObject->GetStudentID()]['FatherName'] = $NewParentDetailObject->GetFatherFirstName() . ' ' . $NewParentDetailObject->GetFatherLastName();
        $StudentDetails[$FeeCollectionObject->GetStudentID()]['FatherMobileNumber'] = ($NewParentDetailObject->GetFatherMobileNumber() ? $NewParentDetailObject->GetFatherMobileNumber() : '--');
        $StudentDetails[$FeeCollectionObject->GetStudentID()]['FeeCollectionID'] = $FeeCollectionID;
        $StudentDetails[$FeeCollectionObject->GetStudentID()]['EnrollmentID'] = $StudentDetailObject->GetEnrollmentID();

        $TransactionDetails[$FeeCollectionID]['StudentName'] = $StudentDetailObject->GetFirstName();
        $TransactionDetails[$FeeCollectionID]['StudentID'] = $FeeCollectionObject->GetStudentID();
        
        $StudentID = $FeeCollectionObject->GetStudentID();
    }
}
catch (ApplicationDBException $e)
{
    header('location:admin/error.php');
    exit;
}
catch (Exception $e)
{
    header('location:admin/error.php');
    exit;
}

$FeeDate = $FeeCollectionObject->GetFeeDate();

/*$FeeCollectionDetails = array();
$FeeCollectionDetails = FeeCollection::GetFeeTransactionDetails($Clean['FeeTransactionID']);

$OtherChargesDetails = array();
$OtherChargesDetails = FeeCollection::GetFeeTransactionOtherChargesDetails($Clean['FeeTransactionID']);*/

$LandingPageMode = '';
if (isset($_GET['Mode']))
{
    $LandingPageMode = $_GET['Mode'];
}

require_once('../html_header.php');
?>
<title>Fee Receipt</title>
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
                    <h1 class="page-header">Fee Receipt</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
            <!-- /.row -->
            <div class="row">
                <div class="col-lg-12">
                    <div class="panel panel-default">
                        <div class="panel-body">
                            <div>
                                <div class="row">
                                    <div class="col-lg-6">                                     
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="print-btn-container"><button id="PrintButton1" type="submit" class="btn btn-primary">Print</button></div>
                                    </div>
                                </div>
                                <div id="RecordTableHeading">
                                    <div class="align-center" style="height:700px; width:500px; border:1px solid black; display:block;  border-collapse: collapse;">
                                        <div style="height:100px; width:150px; float:left; margin-top:5px;">
                                            <img src="../../site_images/school_logo/school_logo.png" height="90px;" width="120px;" style="margin-left:5px; margin-top:10px;" align="center;">
                                            <!-- <br><br><strong><small>Affilation No: <?php echo $AffilationNumber; ?></small></strong> -->
                                        </div>
                                        
                                        <div style="height:120px; width:310px; float:left; margin-top:10px; text-align:center;">
                                            <strong style="font-size:19px;"><?php echo $SchoolName; ?></strong>
                                            <!-- <br><strong><?php echo $AffilationDetails; ?></strong> -->
                                            <br><strong><?php echo $Address; ?></strong>
                                            <br><strong><?php echo $City; ?></strong>
                                            <!--<br><strong style="line-height:30px;"><u>Fee Receipt</u> ( <?php echo $AcademicYearName; ?> )</strong>-->
                                            <br><strong style="line-height:30px;"><u>Fee Receipt</u> ( <?php echo date('Y', strtotime($AcademicYears[$Clean['AcademicYearID']]['StartDate'])) .' - '. date('Y', strtotime($AcademicYears[$Clean['AcademicYearID']]['EndDate'])); ?> )</strong>
                                        </div>
                                        <div style="float:right; margin-right:7px; text-align:right;">
                                            <small style="text-align:right;"><b>Phone No. :</b> <?php echo $ContactNumber; ?></small>
                                        </div>
                                        <hr style="border-top: dotted 1px; margin:0px; height:1px; width:500px; ">
                                        <table width="97%" cellspacing="0px" cellpadding="1px;">
                                            
                                            <tr>
                                                <td><b>&nbsp;&nbsp;Receipt No : </b><?php echo $Clean['FeeTransactionID']; ?></td>
                                                <td style="text-align:right;" ><b>Admission No : </b><?php echo $StudentDetails[$StudentID]['EnrollmentID']; ?></td>
                                            </tr>
                                            <tr>
                                                <td><b>&nbsp;&nbsp;Receipt Date : </b><?php echo date('d/m/Y', strtotime($FeeDate)); ?></td>
                                                <td style="text-align:right;"><b>Class : </b><?php echo $StudentDetails[$StudentID]['ClassName']; ?></td>
                                            </tr>
<?php
                                        foreach ($StudentDetails as $StudentID => $Details) 
                                        {
?>
                                            <tr>
                                                <td><b>&nbsp;&nbsp;Student Name : </b><?php echo $Details['StudentName'] .' '. ((count($StudentDetails) > 1) ?  '<small>('. $Details['ClassName'] .')</small>' : ''); ?></td>
                                            
                                            </tr>
<?php
                                        }
?>
                                            <tr>
                                                <td><b>&nbsp;&nbsp;Father Name : </b><?php echo $Details['FatherName']; ?></td>
                                                <!--<td><b>Transaction ID : </b><?php echo $Details['FeeCollectionID']; ?></td>-->
                                            </tr>
                                        </table>
                                        <hr style="border-top: dotted 1px; margin:0px; margin-top:8px;">
                                        <table width="100%" id="DataTableRecords" border="1px" cellspacing="0px" style="margin-top:10px;" >
                                            <thead>
                                                <tr>
                                                    <th>S.No</th>
                                                    <th>Month</th>
                                                    <th>Fee Head</th>
                                                    <th>Payable Amount</th>
                                                    <th>Paid Amount</th>
                                                    <th>Due Amount</th>
                                                </tr>
                                            </thead>
                                            <tbody>
<?php
                                    $Counter = 0;
                                    $TotalFeeAmount = 0;
                                    $TotalDiscountAmount = 0;
                                    $TotalAmountAfterDiscount = 0;
                                    $TotalPaidAmount = 0;
                                    $TotalRestAmount = 0;
                                    
                                    foreach ($TransactionDetails as $FeeCollectionID => $StudentDetails)
                                    {
                                        $FeeCollectionDetails = array();
                                        $FeeCollectionDetails = FeeCollection::GetFeeTransactionDetails($FeeCollectionID);
                                        
                                        foreach ($FeeCollectionDetails as $AcademicYearID => $FeeDetails)
                                        {
                                            $Year = '';
                                            
                                            foreach ($FeeDetails as $MonthName => $CollectionDetails)
                                            {
                                                if (count($FeeCollectionDetails) > 1) 
                                                {   
                                                    if ($MonthName == 'January' || $MonthName == 'February' || $MonthName == 'March') 
                                                    {
                                                        $Year = '['. date('y', strtotime($AcademicYears[$AcademicYearID]['EndDate'])) .']';
                                                    }
                                                    else
                                                    {
                                                        $Year = '['. date('y', strtotime($AcademicYears[$AcademicYearID]['StartDate'])) .']';
                                                    }
                                                }
?>
                                               <tr>
                                                    <td><?php echo ++$Counter .'.'; ?></td>
                                                    <td ><?php echo $MonthName . $Year; ?>
                                                        <?php echo (count($TransactionDetails) > 1) ? '<br>('. $StudentDetails['StudentName'] .')' : ''; ?>
                                                    </td>
                                                    <td> 
                                                        <table width="100%" cellspacing="0" cellpadding="0">
<?php
                                                        $Count = 1;
                                                        foreach ($CollectionDetails as $FeeHeadID => $Details) 
                                                        {
                                                            if ($Count > 1)
                                                            {
                                                                // echo '<tr height="1px;"><td><hr></td></tr>';
                                                            }
                                                            $Count++;
?>
                                                            <tr height="20px;">
                                                                <td style="border-bottom: 1px;"><?php echo $Details['FeeHead']; ?></td>
                                                            </tr>
<?php
                                                        }
?>
                                                        </table> 
                                                    </td>
    
                                                    <td> 
                                                        <table width="100%">
<?php
                                                        $Count = 1;
                                                        foreach ($CollectionDetails as $FeeHeadID => $Details) 
                                                        {
                                                            $TotalAmountAfterDiscount += $Details['FeeAmount'] - $Details['DiscountAmount'];
                                                            if ($Count > 1)
                                                            {
                                                                // echo '<tr><td><hr></td></tr>';
                                                            }
                                                            $Count++;
?>
                                                            <tr>
                                                                <td align="right" style="padding-right: 4px;"><?php echo number_format($Details['FeeAmount'] - $Details['DiscountAmount'], 2); ?></td>
                                                            </tr>
<?php
                                                        }
?>
                                                        </table> 
                                                    </td>
                                                    <td> 
                                                        <table width="100%">
<?php
                                                        $Count = 1;
                                                        foreach ($CollectionDetails as $FeeHeadID => $Details) 
                                                        {
                                                            $TotalPaidAmount += $Details['PaidAmount'];
                                                            if ($Count > 1)
                                                            {
                                                                // echo '<tr><td><hr></td></tr>';
                                                            }
                                                            $Count++;
?>
                                                            <tr>
                                                                <td align="right" style="padding-right: 4px;"><?php echo number_format($Details['PaidAmount'], 2); ?></td>
                                                            </tr>
<?php
                                                        }
?>
                                                        </table> 
                                                    </td>
                                                    <td class="text-danger"> 
                                                        <table width="100%">
<?php
                                                        $Count = 1;
                                                        foreach ($CollectionDetails as $FeeHeadID => $Details) 
                                                        {
                                                            $TotalRestAmount += $Details['RestAmount'];
                                                            if ($Count > 1)
                                                            {
                                                                // echo '<tr><td><hr></td></tr>';
                                                            }
                                                            $Count++;
?>
                                                            <tr>
                                                                <td align="right" style="padding-right: 4px;"><?php echo number_format($Details['RestAmount'], 2); ?></td>
                                                            </tr>
<?php
                                                        }
?>
                                                        </table> 
                                                    </td>
                                                </tr>
<?php
                                            }
                                        }
                                        
                                        
                                        $OtherChargesDetails = array();
                                        $OtherChargesDetails = FeeCollection::GetFeeTransactionOtherChargesDetails($FeeCollectionID);

                                        foreach ($OtherChargesDetails as $OtherChargesDetailID => $OtherDetails)
                                        {
                                            $AmountPayable = $OtherDetails['Amount'];
                                            
                                            if ($OtherDetails['FeeType'] == 'PreviousYearDue')
                                            {
                                                $StudentObject = new StudentDetail($StudentDetails['StudentID']);
                                                $PreviousYearDue = $StudentObject->GetStudentPreviousYearDue();
                                                
                                                $AmountPayable += $PreviousYearDue;
                                            }
                                            
                                            $TotalAmountAfterDiscount += $AmountPayable;
                                            $TotalPaidAmount += $OtherDetails['Amount'];
                                            $TotalRestAmount += $AmountPayable - $OtherDetails['Amount'];
?>
                                           <tr>
                                                <td><?php echo ++$Counter .'.'; ?></td>
                                                <td ></td>
                                                <td align="right" style="padding-right: 4px;"><?php echo $OtherDetails['FeeDescription']; ?></td>
                                                <td align="right" style="padding-right: 4px;"><?php echo number_format($AmountPayable, 2); ?></td>
                                                <td align="right" style="padding-right: 4px;"><?php echo $OtherDetails['Amount']; ?></td>
                                                <td class="text-right text-danger" align="right" style="padding-right: 4px;"><?php echo number_format(($AmountPayable-$OtherDetails['Amount']), 2); ?></td>
                                            </tr>
<?php
                                        }
                                    }
?>  
                                                <tr>
                                                    <th colspan="3" align="right" class="text-right">Grand Total :</th>
                                                    <th align="right" class="text-right" style="padding-right: 4px;"><?php echo number_format($TotalAmountAfterDiscount, 2);?></th>
                                                    <th align="right" class="text-right" style="padding-right: 4px;"><?php echo number_format($TotalPaidAmount, 2);?></th>
                                                    <th align="right" class="text-right text-danger" style="padding-right: 4px;"><?php echo number_format($TotalRestAmount, 2);?></th>
                                                </tr>
                                            </tbody>
                                        </table>

                                         <hr style="border-top: dotted 1px; margin:0px; margin-top:5px;">
                                         <div style="text-align:left; margin-left: 10px; margin-top: 1px;"><strong>Total Paid Amt.: &nbsp;&nbsp;&nbsp;</strong><?php echo number_format($TotalPaidAmount, 2);?></div>
                                         <div style="text-align:left; margin-left: 10px; margin-top: 1px;"><strong>Balance Amt.: &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</strong><?php echo number_format($TotalRestAmount, 2);?></div>
                                         <hr style="border-top: dotted 1px; margin:0px; height:1px;">
                                            <div style="text-align:right; margin-right: 50px; margin-top: 30px;"><strong>Authorised Signature</strong></div>
                                            <div style="text-align:right; margin-right: 30px;margin-bottom: 50px;">Thank You! Have a great day.</div>
                                            
                                            <div style="position: absolute; text-align:left; margin-left: 6px; bottom:35px;">
                                                <small> Note: 1. Last Date of payment 5th of every month, Parents are requested to deposit the fee.</small><br>
                                            </div>
                                            
                                            <div style="position: absolute; bottom: 15px; text-align: center; margin-left: 175px;"><small>Powered by : <img src="../..//images/added_logo.png" height="12px;" width="60px;" style="margin-left:4px; "></small></div>
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
    $(".select-date").datepicker({
        changeMonth: true,
        changeYear: true,
        dateFormat: 'dd/mm/yy'
    });

    $('#DataTableRecords').DataTable({
        responsive: true,
        bPaginate: false,
        bSort: false,
        searching: false, 
        info: false
    });

});

$('#PrintButton1').on('click',function(){

    var divToPrint=document.getElementById('RecordTableHeading');

     var newWin=window.open('','Print-Window');
    
     newWin.document.open();
    
     newWin.document.write('<html><body onload="window.print()"><div style="float: left; width:842px; height:610px; display:block;"> <div style="float: left; width:310px; height:590px; display:block;">'+divToPrint.innerHTML+'</div></div></body></html>');
    
     newWin.document.close();
});
</script>
<!-- JavaScript To Print A Report -->
<script src="../js/print-report.js"></script>
</body>
</html>
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
require_once('../../classes/school_administration/class.academic_year_months.php');
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

if ($LoggedUser->HasPermissionForTask(TASK_STUDENT_DISCOUNT_DETAILS) !== true)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}

$PaymentModeList = array(1 => 'Cash', 2 => 'Cheque', 3 => 'Net Transfer', 4 => 'Bank Transfer', 5 => 'Card Payment');
$DiscountTypeList = array('Discount' => 'Discount', 'Concession' => 'Concession', 'WaveOff' => 'WaveOff');

$AcademicYearMonths =  array();
$AcademicYearMonths =  AcademicYearMonth::GetMonthsByFeePriority();

$AcademicYears =  array();
$AcademicYears = AcademicYear::GetAllAcademicYears();

$ClassList =  array();
$ClassList = AddedClass::GetActiveClasses();

$ClassSectionsList = array();
$StudentsList =  array();

$Filters = array();

$StudentDiscountDetails = array();

$RecordDeletedSuccessfully = false;
$HasErrors = false;
$TotalRecords = 0;

$Clean = array();
$Clean['Process'] = 0;

$Clean['AcademicYearID'] = 0;
$Clean['ClassID'] = 0;
$Clean['ClassSectionID'] = 0;
$Clean['StudentID'] = 0;

$Clean['MonthList'] = array();
$SelectedMonths = '';

$Clean['TransactionDate'] = '';
$Clean['TransactionFromDate'] = '';
$Clean['TransactionToDate'] = '';
$Clean['StudentName'] = '';
$Clean['MobileNumber'] = '';
$Clean['ChequeReferenceNo'] = '';
$Clean['DiscountType'] = 'WaveOff';

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
        
        if (isset($_GET['FeeDiscountID']))
        {
            $Clean['FeeDiscountID'] = (int) $_GET['FeeDiscountID'];           
        }

        if (isset($_GET['Type']))
        {
            $Clean['DiscountType'] = (string) $_GET['Type'];           
        }
       
        if ($Clean['FeeDiscountID'] <= 0)
        {
            header('location:../error_page.php');
            exit;
        }                       
            
        try
        {
            $FeeDiscountToDelete = new FeeDiscount($Clean['FeeDiscountID']);
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

        $SearchValidator->ValidateInSelect($Clean['DiscountType'], $DiscountTypeList, 'Unknown error, please try again.');

        if ($SearchValidator->HasNotifications())
        {
            $HasErrors = true;
            break;
        }
           
        if (!FeeDiscount::RemoveDiscount($Clean['DiscountType'], $Clean['FeeDiscountID']))
        {
            $SearchValidator->AttachTextError('Error in executing query.');
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
        
        if (isset($_GET['txtMobileNumber']))
        {
            $Clean['MobileNumber'] = strip_tags(trim($_GET['txtMobileNumber']));
        }
        else if (isset($_GET['MobileNumber']))
        {
            $Clean['MobileNumber'] = strip_tags(trim( (string) $_GET['MobileNumber']));
        }

        if (isset($_GET['optDiscountType']))
        {
            $Clean['DiscountType'] = strip_tags(trim($_GET['optDiscountType']));
        }
        elseif (isset($_GET['DiscountType']))
        {
            $Clean['DiscountType'] = strip_tags(trim($_GET['DiscountType']));
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
        
        if ($Clean['MobileNumber'] != '')
        {
            $SearchValidator->ValidateStrings($Clean['MobileNumber'], 'Mobile number should be between 1 to 15.', 1, 15);
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
        $Filters['StudentID'] = $Clean['StudentID'];
        $Filters['StudentName'] = $Clean['StudentName'];
        $Filters['MobileNumber'] = $Clean['MobileNumber'];

        $Filters['MonthList'] = $Clean['MonthList'];

        if ($Clean['DiscountType'] == 'Discount') 
        {
            $Filters['Discount'] = 1;
        }

        if ($Clean['DiscountType'] == 'Concession') 
        {
            $Filters['Concession'] = 1;
        }

        if ($Clean['DiscountType'] == 'WaveOff') 
        {
            $Filters['WaveOff'] = 1;
        }
        
        //get records count
        FeeDiscount::SearchStudentDiscountDetails($TotalRecords, true, $Filters);

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
                $StudentDiscountDetails = FeeDiscount::SearchStudentDiscountDetails($TotalRecords, false, $Filters, 0, $TotalRecords);
            }
            else
            {
                $StudentDiscountDetails = FeeDiscount::SearchStudentDiscountDetails($TotalRecords, false, $Filters, $Start, $Limit);
            }
            
            if (isset($_GET['report_submit']) && $_GET['report_submit'] == 2)
            {
                require_once('../excel/student_discount_details_download_xls.php');
            }

            $SelectedMonths = '';
            $SelectedMonths = implode(',', $Clean['MonthList']);
        }
        // echo "<pre>";
        // var_dump($StudentDiscountDetails);exit;
        break;

}

$LandingPageMode = '';
if (isset($_GET['Mode']))
{
    $LandingPageMode = $_GET['Mode'];
}

require_once('../html_header.php');
?>
<title>Student Discount Details</title>
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
                    <h1 class="page-header">Student Discount Details</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
            <form class="form-horizontal" name="frmRoomReport" action="student_discount_details.php" method="get">
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
                                <label for="MobileNumber" class="col-lg-2 control-label">Mobile Number</label>
                                <div class="col-lg-7">
                                    <input class="form-control" type="text" maxlength="50" id="MobileNumber" name="txtMobileNumber" value="<?php echo $Clean['MobileNumber']; ?>" />
                                </div>
                            </div>
                            <div class="form-group">                            
                                <label for="DiscountType" class="col-lg-2 control-label">Discount Type</label>
                                <div class="col-lg-6">
<?php
                                foreach ($DiscountTypeList as $DiscountTypeID => $DiscountType) 
                                {
?>
                                    <label class="col-sm-3"><input class="custom-radio" type="radio" id="<?php echo $DiscountTypeID; ?>" name="optDiscountType" value="<?php echo $DiscountType; ?>" <?php echo ($DiscountType == $Clean['DiscountType']) ? 'checked="checked"' : ''; ?>>&nbsp;&nbsp;<?php echo $DiscountType; ?></label>
<?php
                                }
?>
                                </div>
                            </div>
                            <div class="form-group">                            
                                <label for="Month" class="col-lg-2 control-label">By Month</label>
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
            
            if ($Clean['StudentID'] > 0)
            {
                $ReportHeaderText .= ' Student : ' . $StudentsList[$Clean['StudentID']]['FirstName'] . ',';
            }
            
            if ($Clean['StudentName'] != '')
            {
                $ReportHeaderText .= ' Student Name : ' . $Clean['StudentName'] . ',';
            }
            
            if ($Clean['MobileNumber'] != '')
            {
                $ReportHeaderText .= ' Mobile Number : ' . $Clean['MobileNumber'] . ',';
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
                                    if ($TotalPages > 1)
                                    {
                                        $AllParameters = array('Process' => '7', 'AcademicYearID' => $Clean['AcademicYearID'], 'ClassID' => $Clean['ClassID'], 'ClassSectionID' => $Clean['ClassSectionID'], 'StudentID' => $Clean['StudentID'], 'StudentName' => $Clean['StudentName'], 'DiscountType' => $Clean['DiscountType'], 'MonthList' => $SelectedMonths);
                                        echo UIHelpers::GetPager('student_discount_details.php', $TotalPages, $Clean['CurrentPage'], $AllParameters);
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
                                        <div class="report-heading-container"><strong>Student Discount Details on <?php echo date('d-m-Y h:i A') . $ReportHeaderText; ?></strong></div>
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
                                                    <th>Month</th>
                                                    <th>Fee Head</th>
                                                    <th>Total Amt</th>
                                                    <th><?php echo $Clean['DiscountType']; ?> Amt</th>
                                                    <th class="print-hidden">Acton</th>
                                                </tr>
                                            </thead>
                                            <tbody>
<?php
                                    if (is_array($StudentDiscountDetails) && count($StudentDiscountDetails) > 0)
                                    {
                                        $Counter = $Start;
                                        $TotalDiscountAmount = 0;
                                        $TotalAmount = 0;
                                        
                                        foreach ($StudentDiscountDetails as $FeeDiscountID => $Details)
                                        {
?>
                                            <tr>
                                                <td><?php echo ++$Counter; ?></td>
                                                <td><?php echo $Details['StudentName']; ?></td>
                                                <td><?php echo $Details['ClassName'] .'('. $Details['SectionName'] .')'; ?></td>

                                                <td><?php echo $Details['MonthName']; ?></td>
                                                <td><?php echo $Details['FeeHead']; ?></td>
                                                <td><?php echo number_format($Details['TotalAmount'], 2); ?></td>
                                                <td>
<?php
                                                $TotalAmount += $Details['TotalAmount'];
                                                
                                                if ($Clean['DiscountType'] == 'Discount') 
                                                {
                                                    echo number_format($Details['DiscountAmount'], 2); 
                                                    
                                                    $TotalDiscountAmount += $Details['DiscountAmount'];
                                                }
                                                else if ($Clean['DiscountType'] == 'Concession') 
                                                {
                                                    echo number_format($Details['TotalConcession'], 2); 
                                                    
                                                    $TotalDiscountAmount += $Details['TotalConcession'];
                                                }
                                                else if ($Clean['DiscountType'] == 'WaveOff') 
                                                {
                                                    echo number_format($Details['TotalWaveOff'], 2); 
                                                    
                                                    $TotalDiscountAmount += $Details['TotalWaveOff'];
                                                }
?>
                                                        
                                                </td>

                                                <td class="print-hidden">
<?php
                                                    
                                                    if ($LoggedUser->HasPermissionForTask(TASK_ADD_EDIT_FEE_GROUP) === true)
                                                    {
                                                        echo '<a href="student_discount_details.php?Process=5&amp;FeeDiscountID=' . $FeeDiscountID . '&amp;Type='. $Clean['DiscountType'] .'" class="delete-record">Delete</a>';
                                                    }
                                                    else
                                                    {
                                                        echo 'Delete';
                                                    }
                                                    
?>
                                                        
                                                </td>
                                            </tr>
<?php
                                        }
?>
                                            <tr>
                                                <th Colspan="5" class="text-right">Grand Total</th>
                                                <th><?php echo number_format($TotalAmount, 2) ; ?></th>
                                                <th><?php echo number_format($TotalDiscountAmount, 2) ; ?></th>
                                                <th></th>
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
    
});
</script>
<!-- JavaScript To Print A Report -->
<script src="../js/print-report.js"></script>
</body>
</html>
<?php
require_once('../../classes/class.users.php');
require_once('../../classes/class.validation.php');
require_once('../../classes/class.authentication.php');

require_once('../../classes/class.ui_helpers.php');
require_once('../../classes/class.sms_queue.php');

require_once('../../classes/school_administration/class.academic_years.php');
require_once('../../classes/school_administration/class.academic_year_months.php');
require_once('../../classes/school_administration/class.classes.php');
require_once('../../classes/school_administration/class.class_sections.php');

require_once('../../classes/school_administration/class.students.php');
require_once('../../classes/school_administration/class.student_details.php');

require_once('../../classes/fee_management/class.fee_heads.php');
require_once('../../classes/fee_management/class.fee_collection.php');

require_once('../../classes/class.global_settings.php');

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
// END OF 1. //

if ($LoggedUser->HasPermissionForTask(FEE_DEFAULTER) !== true)
{
    header('location:/admin/unauthorized_login_admin.php');
    exit;
}

$StudentStatusList = array('Active' => 'Active', 'InActive' => 'InActive');
$PaymentModeList = array(1 => 'Cash', 2 => 'Cheque', 3 => 'Net Transfer');

$Filters = array();

$ClassList =  array();
$ClassList = AddedClass::GetActiveClasses();

$FeeHeadList =  array();
$FeeHeadList = FeeHead::GetActiveFeeHeads();

$AcademicYears =  array();
$AcademicYears = AcademicYear::GetAllAcademicYears();

$AcademicYearMonths =  array();
$AcademicYearMonths =  AcademicYearMonth::GetMonthsByFeePriority();

$GlobalSettingObject = new GlobalSetting();

$FeeSubmissionLastDate = '';
$FeeSubmissionFrequency = 0;
$FeeSubmissionType = '';

$FeeSubmissionLastDate = $GlobalSettingObject->GetFeeSubmissionLastDate();
$FeeSubmissionFrequency = $GlobalSettingObject->GetFeeSubmissionFrequency();
$FeeSubmissionType = $GlobalSettingObject->GetFeeSubmissionType();

$AcademicYearMonthID = 0;
$AcademicYearMonthID = AcademicYearMonth::GetMonthIDByMonthName(date('M'));

$FeePriority = 0;

foreach (array_chunk($AcademicYearMonths, $FeeSubmissionFrequency, true) as $Key => $Value) 
{
    if (array_key_exists($AcademicYearMonthID, $Value)) 
    {
        end($Value);
        $FeePriority = $Value[key($Value)]['FeePriority'];
    }
}

$ClassSectionsList =  array();
$StudentsList =  array();

$ActiveFeeHeads = array();
$ActiveFeeHeads = FeeHead::GetActiveFeeHeads();

$DefaulterList = array();

$RecordDeletedSuccessfully = false;
$HasErrors = false;
$TotalRecords = 0;

$Clean = array();
$Clean['Process'] = 0;

$Clean['AcademicYearID'] = 0;
$Clean['ClassID'] = 0;
$Clean['ClassSectionID'] = 0;
$Clean['StudentID'] = 0;
$Clean['FeeHeadID'] = 0;
$Clean['MobileNumber'] = '';
$Clean['DueMonths'] = 0;

$Clean['Status'] = 'Active';

$Clean['StudentName'] = '';
$Clean['MonthList'] = array();

$SelectedMonths = '';

$Clean['Message'] = 'Dear Parents, kindly submit your ward`s pending overdue fees at the earliest. 
Kindly ignore if submitted dues upto';
// paging and sorting variables start here  //

$Clean['AllRecords'] = '';
$Clean['CurrentPage'] = 1;
$TotalPages = 0;

$Start = 0;
$Limit = 50;

$DefaulterList =array();
$SMSSendSuccessfully = false;

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
    case 1:        
        if (isset($_GET['txtMessage'])) 
        {
            $Clean['Message'] = strip_tags(trim($_GET['txtMessage']));
        }
        
        if (isset($_POST['hdnAcademicYearID'])) 
		{
			$Clean['AcademicYearID'] = (int) $_POST['hdnAcademicYearID'];
		}
		
		if (isset($_POST['hdnDueMonths'])) 
		{
			$Clean['DueMonths'] = (int) $_POST['hdnDueMonths'];
		}
		
        if (isset($_GET['hdnClassID']))
        {
            $Clean['ClassID'] = strip_tags(trim($_GET['hdnClassID']));
        }

        if (isset($_GET['hdnClassSectionID']))
        {
            $Clean['ClassSectionID'] = strip_tags(trim($_GET['hdnClassSectionID']));
        }
        
        if (isset($_GET['hdnStudentID']))
        {
            $Clean['StudentID'] = strip_tags(trim($_GET['hdnStudentID']));
        }

        if (isset($_GET['hdnStudentName']))
        {
            $Clean['StudentName'] = strip_tags(trim($_GET['hdnStudentName']));
        }
        
        if (isset($_GET['hdnStatus']))
        {
            $Clean['Status'] = strip_tags(trim($_GET['hdnStatus']));
        }
        
        if (isset($_GET['hdnMonthList']))
        {
            $SelectedMonths = $_GET['hdnMonthList'];
        }
        
        if ($SelectedMonths != '')
        {
            $Clean['MonthList'] = explode(',', $SelectedMonths);   
        }
        
        $SearchValidator = new Validator();

        $SearchValidator->ValidateStrings($Clean['Message'], 'Message content is required and should be between 10 and 1500 characters.', 10, 1500);

        if ($Clean['ClassID'] > 0)
        {
            $SearchValidator->ValidateInSelect($Clean['ClassID'], $ClassList, 'Unknown error, please try again.');
            $ClassSectionsList = ClassSections::GetClassSections($Clean['ClassID']);
        }

        if ($Clean['ClassSectionID'] > 0)
        {
            $SearchValidator->ValidateInSelect($Clean['ClassSectionID'], $ClassSectionsList, 'Unknown error, please try again.');
            
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
        
        if (count($Clean['MonthList']) > 0)
        {
            $FeePriority = $AcademicYearMonths[end($Clean['MonthList'])]['FeePriority'];
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
        $Filters['DueMonths'] = $Clean['DueMonths'];
        
        //get records count
        FeeCollection::SearchFeeDefaulters($TotalRecords, true, $Filters, $FeePriority);

        if ($TotalRecords > 0)
        {
            $DefaulterList = FeeCollection::SearchFeeDefaulters($TotalRecords, false, $Filters, $FeePriority, 0, $TotalRecords);
            
            foreach ($DefaulterList as $StudentID => $Details) 
            {
                $SMSOriginalContent = '';
                $SMSOriginalContent = $Clean['Message'];

                $SMSOriginalContent .= '\r\n month : '. $AcademicYearMonths[end($Clean['MonthList'])]['MonthName'];
                $SMSOriginalContent .= ' \r\n Due Amount is : '. $Details['TotalDue'];
                $SMSOriginalContent .= ' \r\n Regards, \r\n Lucknow International Public School';

                $MatchResults = array();
                preg_match_all("/\{(\w+)\}/i", $SMSOriginalContent, $MatchResults, PREG_PATTERN_ORDER);
                
                $SMSContent = $SMSOriginalContent;

                if (is_array($MatchResults) && count($MatchResults) > 0)
                {
                    $NewSMSQueue = new SMSQueue();

                    if ($Details['FatherMobileNumber'] != '') 
                    {
                        $NewSMSQueue->SetPhoneNumber($Details['FatherMobileNumber']);
                    }
                    else
                    {
                        $NewSMSQueue->SetPhoneNumber($Details['MotherMobileNumber']);
                    }
                    
                    $NewSMSQueue->SetSMSMessage(nl2br($SMSContent));
                    $NewSMSQueue->SetCreateUserID($LoggedUser->GetUserID());

                    $NewSMSQueue->Save();
                }
            }
        }

        $SelectedMonths = '';
        $SelectedMonths = implode(',', $Clean['MonthList']);

        $SMSSendSuccessfully = true;
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
        
        if (isset($_GET['drdFeeHead']))
        {
            $Clean['FeeHeadID'] = strip_tags(trim($_GET['drdFeeHead']));
        }
        elseif (isset($_GET['FeeHeadID']))
        {
            $Clean['FeeHeadID'] = strip_tags(trim($_GET['FeeHeadID']));
        }
        
        if (isset($_GET['txtDueMonths']))
        {
            $Clean['DueMonths'] = (int) $_GET['txtDueMonths'];
        }
        elseif (isset($_GET['DueMonths']))
        {
            $Clean['DueMonths'] = (int) $_GET['DueMonths'];
        }
        
        if (isset($_GET['optStatus']))
        {
            $Clean['Status'] =  strip_tags(trim( (string) $_GET['optStatus']));
        }
        elseif (isset($_GET['Status']))
        {
            $Clean['Status'] =  strip_tags(trim( (string) $_GET['Status']));
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
            $SearchValidator->ValidateInSelect($Clean['ClassID'], $ClassList, 'Unknown error, please try again.');
            $ClassSectionsList = ClassSections::GetClassSections($Clean['ClassID']);
        }

        if ($Clean['ClassSectionID'] > 0)
        {
            $SearchValidator->ValidateInSelect($Clean['ClassSectionID'], $ClassSectionsList, 'Unknown error, please try again.');
            
            $StudentsList = StudentDetail::GetStudentsByClassSectionID($Clean['ClassSectionID'], 'Active', $Clean['AcademicYearID']);
            
            if ($Clean['StudentID'] > 0)
            {
                $SearchValidator->ValidateInSelect($Clean['StudentID'], $StudentsList, 'Please select a valid student.');   
            }
        }
        
        if ($Clean['FeeHeadID'] > 0)
        {
            $SearchValidator->ValidateInSelect($Clean['FeeHeadID'], $ActiveFeeHeads, 'Please select a valid fee head.');
        }
        
        if ($Clean['DueMonths'] > 0)
        {
            $SearchValidator->ValidateInteger($Clean['DueMonths'], 'Unknown error in due months.', 0);
        }
        
        if ($Clean['Status'] != '')
        {
            $SearchValidator->ValidateInSelect($Clean['Status'], $StudentStatusList, 'Unknown Error in status, Please try again.');
        }
        
        if ($Clean['StudentName'] != '')
        {
            $SearchValidator->ValidateStrings($Clean['StudentName'], 'Student name should be between 2 to 50.', 2, 50);
        }
        
        if ($Clean['MobileNumber'] != '')
        {
            $SearchValidator->ValidateStrings($Clean['MobileNumber'], 'Mobile number should be between 1 to 15.', 1, 15);
        }
        
        if (count($Clean['MonthList']) > 0)
        {
            $FeePriority = $AcademicYearMonths[end($Clean['MonthList'])]['FeePriority'];
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
        $Filters['DueMonths'] = $Clean['DueMonths'];
        $Filters['StudentID'] = $Clean['StudentID'];
        $Filters['StudentName'] = $Clean['StudentName'];
        $Filters['MobileNumber'] = $Clean['MobileNumber'];
        $Filters['Status'] = $Clean['Status'];
        
        //get records count
        FeeCollection::SearchFeeDefaulters($TotalRecords, true, $Filters, $FeePriority);

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
            if (isset($_GET['report_submit']) && $_GET['report_submit'] == 2)
            {
                require_once('../excel/fee_defaulter_list_download_xls.php');
            }
            if ($Clean['AllRecords'] == 'All') 
            {
                $DefaulterList = FeeCollection::SearchFeeDefaulters($TotalRecords, false, $Filters, $FeePriority, 0, $TotalRecords);
            }
            else
            {
                $DefaulterList = FeeCollection::SearchFeeDefaulters($TotalRecords, false, $Filters, $FeePriority, $Start, $Limit);
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
<title>Defaulter List</title>
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
                    <h1 class="page-header">Defaulter List</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
            <form class="form-horizontal" name="frmRoomReport" action="fee_defaulter_list.php" method="get">
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
                            else if ($SMSSendSuccessfully == true)
                            {
                                echo '<div class="alert alert-danger">SMS Send successfully.</div>';
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
                                <label for="ClassList" class="col-lg-2 control-label">Class</label>
                                <div class="col-lg-3">
                                    <select class="form-control" name="drdClass" id="Class">
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
                                <label for="ClassSection" class="col-lg-1 control-label">Section</label>
                                <div class="col-lg-3">
                                    <select class="form-control" name="drdClassSection" id="ClassSection">
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
                                <label for="Student" class="col-lg-2 control-label">Select Student</label>
                                <div class="col-lg-7">
                                    <select class="form-control" name="drdStudent" id="Student">
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
                                <label for="DueMonths" class="col-lg-2 control-label">Due Months >= </label>
                                <div class="col-lg-2">
                                    <input class="form-control" type="text" maxlength="2" id="DueMonths" name="txtDueMonths" value="<?php echo ($Clean['DueMonths']) ? $Clean['DueMonths'] : ''; ?>" />
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
            
            if ($Clean['StudentID'] > 0)
            {
                $ReportHeaderText .= ' Student : ' . $StudentsList[$Clean['StudentID']]['FirstName'] . ',';
            }
            
            if ($Clean['FeeHeadID'] > 0)
            {
                $ReportHeaderText .= ' Fee Head : ' . $ActiveFeeHeads[$Clean['FeeHeadID']]['FeeHead'] . ',';
            }
            
            if ($Clean['DueMonths'] != '')
            {
                $ReportHeaderText .= ' Due Months >= : ' . $Clean['DueMonths'] . ',';
            }

            if ($Clean['StudentName'] != '')
            {
                $ReportHeaderText .= ' Student Name : ' . $Clean['StudentName'] . ',';
            }
            
            if ($Clean['MobileNumber'] != '')
            {
                $ReportHeaderText .= ' Mobile Number : ' . $Clean['MobileNumber'] . ',';
            }
            
            if ($Clean['Status'] != '')
            {
                $ReportHeaderText .= ' Status: ' . $Clean['Status'] . ' students,';
            }
            
            if (count($Clean['MonthList']) > 1)
            {
                $ReportHeaderText .= ' Months ';
            }
            
            foreach ($Clean['MonthList'] as $Key => $MonthID) 
            {
                $ReportHeaderText .= ' '. $AcademicYearMonths[$MonthID]['MonthName'] . ', ';
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
                                        $AllParameters = array('Process' => '7', 'AcademicYearID' => $Clean['AcademicYearID'], 'ClassID' => $Clean['ClassID'], 'ClassSectionID' => $Clean['ClassSectionID'], 'StudentID' => $Clean['StudentID'], 'FeeHeadID' => $Clean['FeeHeadID'], 'DueMonths' => $Clean['DueMonths'], 'StudentName' => $Clean['StudentName'], 'Status' => $Clean['Status'], 'MonthList' => $SelectedMonths);
                                        echo UIHelpers::GetPager('fee_defaulter_list.php', $TotalPages, $Clean['CurrentPage'], $AllParameters);
                                    }
?>                                        
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="print-btn-container"><button id="PrintButton" type="submit" class="btn btn-primary">Print</button>
                                        <button id="" onclick="$('#get_excel').val(2); $('#SubmitSearch').click();$('#get_excel').val(0);" type="submit" class="btn btn-primary">Export to Excel</button>
                                        <a class="btn btn-primary" data-toggle="modal" data-target="#SendSmsModal" id="SendSMS"><i class="fa fa-send"></i>&nbsp;Send SMS</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="row" id="RecordTableHeading">
                                    <div class="col-lg-12">
                                        <div class="report-heading-container"><strong>Defaulter List on <?php echo date('d-m-Y h:i A') . $ReportHeaderText; ?></strong></div>
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
                                                    <th>Previous Due</th>
<?php
                                                foreach ($FeeHeadList as $FeeHeadID => $FeeHeadDetails) 
                                                {
                                                    $FeeHeadTotalDue[$FeeHeadID] = 0;
                                                    echo '<th>'. $FeeHeadDetails['FeeHead'] .'</th>';
                                                }
?>
                                                    <th>Total Due</th>
                                                    <th>Due Months</th>
                                                    <th class="print-hidden">Details</th>
                                                </tr>
                                            </thead>
                                            <tbody>
<?php
                                    $TotalDue = 0;
                                    $TotalPreviousYearDue = 0;
                                    if (is_array($DefaulterList) && count($DefaulterList) > 0)
                                    {
                                        $Counter = $Start;
                                        
                                        foreach ($DefaulterList as $StudentID => $Details)
                                        {
                                            $PreviousDefaultedFees = array();
                                            $PreviousDueAmount = 0;

                                            if ($Clean['AcademicYearID'] == 2) 
                                            {
                                                $PreviousDefaultedFees = FeeCollection::GetFeeDefaulterDues($StudentID, 120, 1, $PreviousYearDue);
                                                
                                                foreach ($PreviousDefaultedFees as $Month => $FeeDetails) 
                                                {
                                                    $PreviousDueAmount += array_sum(array_column($FeeDetails,'FeeHeadAmount'));
                                                }

                                                if ($PreviousDueAmount < 0) 
                                                {
                                                    $PreviousDueAmount = 0;
                                                }
                                            }

                                            $PreviousYearDue = 0;
                                            $FeeDefaulterDues = array();
                                            $FeeDefaulterDues = FeeCollection::GetFeeDefaulterDues($StudentID, $FeePriority, $Clean['AcademicYearID'], $PreviousYearDue);
                                            
                                            $TotalDue += $Details['TotalDue'] + $PreviousDueAmount;
                                            $TotalPreviousYearDue += ($PreviousYearDue + $PreviousDueAmount);
?>
                                                <tr>
                                                    <td><?php echo ++$Counter; ?></td>
                                                    <td><?php echo $Details['StudentName']; ?></td>
                                                    <td><?php echo $Details['Class']; ?></td>
                                                    <td><?php echo $Details['FatherMobileNumber'] .'<br> '. $Details['MotherMobileNumber']; ?></td>
                                                    <td class="text-right"><?php echo number_format($PreviousYearDue + $PreviousDueAmount, 2); ?></td>
<?php
                                                    foreach ($FeeHeadList as $FeeHeadID => $FeeHeadDetails) 
                                                    {
                                                        $FeeHeadDueAmount = 0;
                                                        foreach ($FeeDefaulterDues as $Month => $DefaulterDetails) 
                                                        {
                                                            if ($Clean['FeeHeadID'] > 0)
                                                            {
                                                                if ($Clean['FeeHeadID'] == $FeeHeadID  && array_key_exists($Clean['FeeHeadID'], $DefaulterDetails))
                                                                {
                                                                    $FeeHeadDueAmount += $DefaulterDetails[$Clean['FeeHeadID']]['FeeHeadAmount'];   
                                                                }
                                                            }
                                                            else 
                                                            {
                                                                if (array_key_exists($FeeHeadID, $DefaulterDetails)) 
                                                                {
                                                                    $FeeHeadDueAmount += $DefaulterDetails[$FeeHeadID]['FeeHeadAmount'];   
                                                                }
                                                            }
                                                        }

                                                        echo '<td class="text-right">'. number_format($FeeHeadDueAmount, 2) .'</td>';

                                                        $FeeHeadTotalDue[$FeeHeadID] += $FeeHeadDueAmount;
                                                    }
?>
                                                    <td class="text-right"><?php echo number_format($Details['TotalDue'] + $PreviousDueAmount, 2); ?></td>
                                                    <td class="text-right"><?php echo $Details['DueMonths'] . ' Month'; ?></td>
                                                    <td class="print-hidden"><button type="button" class="btn btn-info btn-sm DueDetails" data-toggle="modal" data-target="#ViewDueDetails" value="<?php echo $StudentID; ?>">Details &nbsp;<i class="fa fa-angle-double-right"></i></button></td>
                                                </tr>
<?php
                                        }
                                    }
?>
                                                <tr>
                                                    <th class="text-right"> </th>
                                                    <th class="text-right"> </th>
                                                    <th class="text-right"> </th>
                                                    <th class="text-right">Grand Total : </th>
                                                    <th class="text-right"><?php echo number_format($TotalPreviousYearDue, 2) ?></th>
<?php
                                                    foreach ($FeeHeadTotalDue as $FeeHeadID => $FeeHeadDue) 
                                                    {
                                                        echo '<th class="text-right">'. number_format($FeeHeadDue, 2) .'</th>';
                                                    }
?>
                                                    <th class="text-right"><?php echo number_format($TotalDue, 2); ?></th>
                                                    <th class="print-hidden"></th>
                                                    <th class="print-hidden"></th>
                                                </tr>
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
<div id="ViewDueDetails" class="modal fade" role="dialog">
  <div class="modal-dialog">

    <!-- Modal content-->
    <div class="modal-content">
      <div class="modal-header btn-info">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title">Transaction Details</h4>
      </div>
      <div class="modal-body">
        <div class="row">
            <div class="col-lg-12" id="DueDetails"></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>  

<div id="SendSmsModal" class="modal fade" role="dialog">
  <div class="modal-dialog">

    <!-- Modal content-->
    <div class="modal-content">
      <div class="modal-header btn-info">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title">SMS Details</h4>
      </div>
      <div class="modal-body">
        <div class="row">
            <div class="col-lg-12" id="SMSDetails">
                <form class="form-horizontal" name="AddFeedStudentmarks" action="fee_defaulter_list.php" method="get">
                    <div class="form-group">
                        <label for="Message" class="col-lg-2 control-label">Message</label>
                        <div class="col-lg-8">
                            <textarea name="txtMessage" class="form-control" rows="7" id="Message"><?php echo $Clean['Message']; ?>

Regards,
Lucknow International Public School
                        </textarea>
                        <small>(This message will autometically send particular student due amount and month.)</small>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="col-sm-offset-2 col-lg-10">
                            <input type="hidden" name="hdnProcess" value="1" />
                            <input type="hidden" name="hdnAcademicYearID" value="<?php echo $Clean['AcademicYearID']; ?>" />
                            <input type="hidden" name="hdnClassID" value="<?php echo $Clean['ClassID']; ?>" />
                            <input type="hidden" name="hdnClassSectionID" value="<?php echo $Clean['ClassSectionID']; ?>" />
                            <input type="hidden" name="hdnStudentID" value="<?php echo $Clean['StudentID']; ?>" />
                            <input type="hidden" name="hdnStudentName" value="<?php echo $Clean['StudentName']; ?>" />
                            <input type="hidden" name="hdnStatus" value="<?php echo $Clean['Status']; ?>" />
                            <input type="hidden" name="hdnMonthList" value="<?php echo $SelectedMonths; ?>" />
                            <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i>&nbsp;Send SMS</button>
                        </div>
                    </div>
                </form>
            </div>
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

    // $('#DataTableRecords').DataTable({
    //     responsive: true,
    //     bPaginate: false,
    //     bSort: false,
    //     searching: false, 
    //     info: false
    // });

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
    
    $('#ClassSection').change(function(){

        var ClassSectionID = parseInt($(this).val());
        var AcademicYearID = parseInt($('#AcademicYearID').val());
        
        if (ClassSectionID <= 0)
        {
            $('#Student').html('<option value="0">Select Student</option>');
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
                $('#Student').html('<option value="0">Select Student</option>' + ResultArray[1]);
            }
        });
    });

    $('.DueDetails').click(function(){

        var StudentID = 0;
        StudentID = parseInt($(this).val());
        AcademicYearID = parseInt($('#AcademicYearID').val());

        FeePriority = <?php echo $FeePriority;?>;

        if (StudentID <= 0 || FeePriority <= 0 || AcademicYearID <= 0)
        {
            alert('Error! No record found.');
            return;
        }
        
        $.post("/xhttp_calls/get_fee_defaulter_dues.php", {SelectedStudentID:StudentID, AcademicYearID:AcademicYearID, FeePriority:FeePriority}, function(data)
        {
            ResultArray = data.split("|*****|");
            
            if (ResultArray[0] == 'error')
            {
                alert (ResultArray[1]);
                return false;
            }
            else
            {
                $('#DueDetails').html(ResultArray[1]);
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
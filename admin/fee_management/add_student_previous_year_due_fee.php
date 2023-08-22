<?php

require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once("../../classes/school_administration/class.classes.php");
require_once('../../classes/school_administration/class.class_sections.php');
require_once('../../classes/school_administration/class.students.php');
require_once('../../classes/school_administration/class.student_details.php');

require_once('../../classes/class.sms_queue.php');

require_once("../../classes/fee_management/class.previous_year_fee_details.php");

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

if ($LoggedUser->HasPermissionForTask(TASK_ADD_PREVIOUS_YEAR_DUE_FEE) !== true)
{
    header('location:/admin/unauthorized_login_admin.php');
    exit;
}
// END OF 1. //

$HasErrors = false;

$ClassList =  array();
$ClassList = AddedClass::GetActiveClasses();

$ClassSectionsList =  array();
$ClassSubjectsList =  array();

$StudentsList = array();

$Clean = array();
$Clean['ClassID'] = 0;

$Clean['WaveOffDue'] = array();
$Clean['PayableAmount'] = array();
$Clean['PayableAmount'] = PreviousYearFeeDetail::GetAllPreviousYearFeeDetails();

$Clean['Process'] = 0;

$Clean['Message'] = '';
$PreviousDueList = array();

if (isset($_POST['hdnProcess']))
{
	$Clean['Process'] = (int) $_POST['hdnProcess'];
}
switch ($Clean['Process'])
{
	case 1:
        if (isset($_POST['txtPayableAmount']) && is_array($_POST['txtPayableAmount'])) 
        {
            $Clean['PayableAmount'] = $_POST['txtPayableAmount'];
        }
        
        if (isset($_POST['txtWaveOffDue']) && is_array($_POST['txtWaveOffDue'])) 
        {
            $Clean['WaveOffDue'] = $_POST['txtWaveOffDue'];
        }

        if (isset($_POST['hdnClassSectionID'])) 
        {
            $Clean['ClassSectionID'] = (int) $_POST['hdnClassSectionID'];
        }

        $NewRecordValidator = new Validator();

        if (count($Clean['PayableAmount']) <= 0) 
        {
            $NewRecordValidator->AttachTextError('Please enter details.');
            $HasErrors = true;
            break;
        }

        $StudentsList = StudentDetail::GetStudentsByClassSectionID($Clean['ClassSectionID'], 'Active');

        foreach ($Clean['PayableAmount'] as $StudentID => $DueAmount) 
        {
            if (!array_key_exists($StudentID, $StudentsList))
            {
                header('location:/admin/error.php');
                exit;
            }
            
            $DueAmount = (int) $DueAmount;
            
            $NewRecordValidator->ValidateInteger($DueAmount, 'Due amount is required and should be integer ', 0);
            
            $PreviousDueList[$StudentID]['PayableAmount'] = $DueAmount;
            $PreviousDueList[$StudentID]['WaveOffDue'] = (int) $Clean['WaveOffDue'][$StudentID];

        }
        
        if ($NewRecordValidator->HasNotifications())
        {
            $HasErrors = true;
            break;
        }
                
        $NewPreviousYearFeeDetail = new PreviousYearFeeDetail();
                
        $NewPreviousYearFeeDetail->SetPreviousYearFeeDetails($PreviousDueList);

        if (!$NewPreviousYearFeeDetail->Save())
        {
            $NewRecordValidator->AttachTextError(ProcessErrors($NewPreviousYearFeeDetail->GetLastErrorCode()));
            $HasErrors = true;
            break;
        }
        
        header('location:add_student_previous_year_due_fee.php?Mode=AS');
        exit;
    break;

    case 2:
		if (isset($_POST['txtMessage'])) 
		{
			$Clean['Message'] = strip_tags(trim($_POST['txtMessage']));
		}

		if (isset($_POST['hdnClassSectionID'])) 
		{
			$Clean['ClassSectionID'] = (int) $_POST['hdnClassSectionID'];
		}

		$NewRecordValidator = new Validator();

        $NewRecordValidator->ValidateStrings($Clean['Message'], 'Message content is required and should be between 10 and 1500 characters.', 10, 1500);

        $StudentsList = StudentDetail::GetStudentsByClassSectionID($Clean['ClassSectionID']);

        $Clean['PayableAmount'] = PreviousYearFeeDetail::GetAllPreviousYearFeeDetails();

        $SMSList = array();
        $CounterForStudents = 0;

        $SMSOriginalContent = '';
        $SMSOriginalContent = $Clean['Message'];

        $MatchResults = array();
        preg_match_all("/\{(\w+)\}/i", $SMSOriginalContent, $MatchResults, PREG_PATTERN_ORDER);
        
        $SMSContent = $SMSOriginalContent;

        foreach ($StudentsList as $StudentID => $StudentDetails) 
        {
            $DueAmount = 0;

            if (isset($Clean['PayableAmount'][$StudentID]['PayableAmount'])) 
            {
                $DueAmount = $Clean['PayableAmount'][$StudentID]['PayableAmount'] - $Clean['PayableAmount'][$StudentID]['PaidAmount'];
            }

            if ($DueAmount > 0) 
            {
                if ($StudentDetails['MobileNumber'] == '')
                {
                    continue;
                }

                $CounterForStudents++;

                if (is_array($MatchResults) && count($MatchResults) > 0)
                {
                    $SMSList[$CounterForStudents]['MobileNumber'] = $StudentDetails['MobileNumber'];
                    $SMSList[$CounterForStudents]['Message'] = $SMSContent;
                }
            }
        }
		
		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}
				
		foreach ($SMSList as $Counter => $SMSDetails)
        {
            $NewSMSQueue = new SMSQueue();

            $NewSMSQueue->SetPhoneNumber($SMSDetails['MobileNumber']);
            $NewSMSQueue->SetSMSMessage($SMSDetails['Message']);
            $NewSMSQueue->SetCreateUserID($LoggedUser->GetUserID());

            $NewSMSQueue->Save();
        }
		
		header('location:add_student_previous_year_due_fee.php?Mode=SS');
		exit;
	break;

	case 7:
        if (isset($_POST['drdClass'])) 
        {
            $Clean['ClassID'] = (int) $_POST['drdClass'];
        }

        if (isset($_POST['drdClassSection'])) 
        {
            $Clean['ClassSectionID'] = (int) $_POST['drdClassSection'];
        }
        
        if (isset($_POST['report_submit']) && $_POST['report_submit'] == 2)
        {
            require_once('../excel/student_previous_year_due_fee_download_xls.php');
        }
        
        $NewRecordValidator = new Validator();

        if ($NewRecordValidator->ValidateInSelect($Clean['ClassID'], $ClassList, 'Unknown error, please try again.')) 
        {
            $CurrentClass = new AddedClass($Clean['ClassID']);
            $ClassSectionsList = ClassSections::GetClassSections($Clean['ClassID']);

            $NewRecordValidator->ValidateInSelect($Clean['ClassSectionID'], $ClassSectionsList, 'Unknown error, please try again.');
        }
        
        if ($NewRecordValidator->HasNotifications())
        {
            $HasErrors = true;
            break;
        }

        $StudentsList = StudentDetail::GetStudentsByClassSectionID($Clean['ClassSectionID'], 'Active');
                
    break;
}

$LandingPageMode = '';
if (isset($_GET['Mode']))
{
    $LandingPageMode = $_GET['Mode'];
}

require_once('../html_header.php');
?>
<title>Student Previous Due Details</title>
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
                    <h1 class="page-header">Student Previous Due Details</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="AddFeedStudentmarks" action="add_student_previous_year_due_fee.php" method="post">
            	<div class="panel panel-default">
                    <div class="panel-heading">
                        Enter Details
                        <button id="" onclick="$('#get_excel').val(2); $('#SubmitSearch').click();$('#get_excel').val(0);" type="button" class="btn btn-sm btn-primary pull-right">Export to Excel</button><br>
                    </div>
                    <div class="panel-body">
<?php
						if ($HasErrors == true)
						{
							echo $NewRecordValidator->DisplayErrors();
						}
						else if ($LandingPageMode == 'AS')
                        {
                            echo '<div class="alert alert-success alert-top-margin">Record saved successfully.</div><br>';
                        }
                        else if ($LandingPageMode == 'SS')
                        {
                            echo '<div class="alert alert-success alert-top-margin">SMS send successfully.</div><br>';
                        }
?>                      
						<div class="form-group">
                            <label for="Class" class="col-lg-2 control-label">Class</label>
                            <div class="col-lg-4">
                                <select class="form-control"  name="drdClass" id="Class">
                                    <option  value="0" >-- Select Class --</option>
<?php
                                    if (is_array($ClassList) && count($ClassList) > 0)
                                    {
                                        foreach ($ClassList as $ClassID => $ClassName)
	                                    {
	?>
	                                        <option <?php echo ($ClassID == $Clean['ClassID'] ? 'selected="selected"' : ''); ?> value="<?php echo $ClassID; ?>"><?php echo $ClassName; ?></option>
	<?php
	                                    }
                                    }
?>
                                </select>
                            </div>
                            <label for="ClassSection" class="col-lg-2 control-label">Section</label>
                            <div class="col-lg-4">
                                <select class="form-control" name="drdClassSection" id="ClassSection">
                                    <option value="0">-- Select Section --</option>
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
                    </div>
                    <div class="form-group">
                        <div class="col-sm-offset-2 col-lg-10">
                        	<input type="hidden" name="hdnProcess" value="7" />
                        	<input type="hidden" name="report_submit" id="get_excel" value="0" />
							<button type="submit" class="btn btn-primary" id="SubmitSearch"><i class="fa fa-search"></i>&nbsp;Search</button>
                        </div>
                  	</div>
                </div>
            </form>
<?php
        if ($Clean['Process'] == 7 && $HasErrors == false || $Clean['Process'] == 1)
        {
?>
            <div class="panel panel-default">
                <div class="panel-heading">
                    Enter Student Due Details
                </div>
                <div class="panel-body">
                    <form class="form-horizontal" name="AddFeedStudentmarks" action="add_student_previous_year_due_fee.php" method="post">
                        <div class="row" id="RecordTable">
                            <div class="col-lg-12">
                                <table width="100%" class="table table-striped table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th>S. No</th>          
                                            <th>Student Name</th>
                                            <th>Previous Due</th>
                                            <th>Paid</th>
                                            <th>Wave-Off</th>
                                        </tr>
									</thead>
									<tbody>
<?php
                                        $RowCounter = 1;
                                        if (count($StudentsList) > 0) 
                                        {
                                            foreach ($StudentsList as $StudentID => $StudentDetails) 
                                            {
?>
                                           <tr>
                                                <td><?php echo $RowCounter++; ?></td>
                                                <td><?php echo $StudentDetails['FirstName'] . $StudentDetails['LastName'] .'('. $StudentDetails['RollNumber']. ')'; ?></td>
                                                <td>
                                                    <?php
                                                        $DueAmount = '';
                                                        if (isset($Clean['PayableAmount'][$StudentID]['PayableAmount'])) 
                                                        {
                                                            $DueAmount = $Clean['PayableAmount'][$StudentID]['PayableAmount'];
                                                        }
                                                    ?>
                                                    <input type="text" class="form-control" id="<?php echo $StudentID; ?>" maxlength="10" name="txtPayableAmount[<?php echo $StudentID ;?>]" value="<?php echo $DueAmount; ?>"/>
                                                </td>
                                                <td>
                                                    <?php
                                                        $PaidAmount = '';
                                                        if (isset($Clean['PayableAmount'][$StudentID]['PaidAmount'])) 
                                                        {
                                                            $PaidAmount = $Clean['PayableAmount'][$StudentID]['PaidAmount'];
                                                        }
                                                    ?>
                                                    <input type="text" class="form-control" value="<?php echo $PaidAmount; ?>" disabled="disabled"/>
                                                </td>
                                                <td>
                                                	<?php
                                                        $WaveOffDue = '';
                                                        if (isset($Clean['PayableAmount'][$StudentID]['WaveOffDue'])) 
                                                        {
                                                            $WaveOffDue = $Clean['PayableAmount'][$StudentID]['WaveOffDue'];
                                                        }
                                                    ?>
                                                	<input type="text" class="form-control" maxlength="10" name="txtWaveOffDue[<?php echo $StudentID ;?>]" value="<?php echo $WaveOffDue; ?>"/>
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
						<div class="form-group">
                            <div class="col-sm-offset-2 col-lg-10">
                                <input type="hidden" name="hdnProcess" value="1" />
                                <input type="hidden" name="hdnClassSectionID" value="<?php echo $Clean['ClassSectionID']; ?>" />
                                <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i>&nbsp;Save</button>
                                <a class="btn btn-primary" id="SendSMS"><i class="fa fa-send"></i>&nbsp;Send SMS</a>
                            </div>
                        </div>
                    </form>

                    <div style="display: none;" id="SMSBox">
                        <form class="form-horizontal" name="AddFeedStudentmarks" action="add_student_previous_year_due_fee.php" method="post">
                            <div class="form-group">
                                <label for="Message" class="col-lg-2 control-label">Message</label>
                                <div class="col-lg-8">
                                    <textarea name="txtMessage" class="form-control" rows="5" id="Message"><?php echo $Clean['Message']; ?></textarea>
                                </div>
                            </div>

                            <div class="form-group">
                                <div class="col-sm-offset-2 col-lg-10">
                                    <input type="hidden" name="hdnProcess" value="2" />
                                    <input type="hidden" name="hdnClassSectionID" value="<?php echo $Clean['ClassSectionID']; ?>" />
                                    <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i>&nbsp;Send SMS</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
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
<script src="../vendor/jquery-ui/jquery-ui.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.5.2/jquery.fancybox.min.js"></script>
<script type="text/javascript">
$(document).ready(function(){

	$('#Class').change(function(){

        var ClassID = parseInt($('#Class').val());
        
        if (ClassID <= 0)
        {
            $('#ClassSection').html('<option value="0">-- Select Section --</option>');
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
                $('#ClassSection').html('<option value="0">-- Select Section --</option>' + ResultArray[1]);
            }
        });
    });

    $('#SendSMS').click(function(){

        $('#SMSBox').slideDown();
        $('#SendSMS').hide();

    });
});
</script>
</body>
</html>
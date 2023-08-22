<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once("../../classes/school_administration/class.notices_circulars.php");
require_once("../../classes/school_administration/class.branch_staff.php");
require_once("../../classes/school_administration/class.classes.php");

require_once("../../classes/class.fcm_send_notification.php");
require_once("../../classes/class.date_processing.php");
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

$HasErrors = false;

$ClassList =  array();
$ClassList = AddedClass::GetActiveClasses();

$StaffList = array();
$StaffList = BranchStaff::SearchBranchStaff();
	
$Clean = array();
$Clean['Process'] = 0;

$Clean['SelectedStaffList'] = array();
$Clean['SelectedClassList'] = array();

$Clean['NoticeCircularDate'] = '';
$Clean['NoticeCircularSubject'] = '';
$Clean['NoticeCircularDetails'] = '';

if (isset($_POST['hdnProcess']))
{
	$Clean['Process'] = (int) $_POST['hdnProcess'];
}
switch ($Clean['Process'])
{
	case 1:						
		if (isset($_POST['drdSelectedStaffList']) && is_array($_POST['drdSelectedStaffList']))
		{
			$Clean['SelectedStaffList'] = $_POST['drdSelectedStaffList'];
		}

		if (isset($_POST['chkSelectedClassList']) && is_array($_POST['chkSelectedClassList']))
        {
            $Clean['SelectedClassList'] = $_POST['chkSelectedClassList'];
        }

		if (isset($_POST['txtNoticeCircularDate']))
		{
			$Clean['NoticeCircularDate'] = strip_tags(trim($_POST['txtNoticeCircularDate']));
		}

		if (isset($_POST['txtNoticeCircularSubject']))
        {
            $Clean['NoticeCircularSubject'] = strip_tags(trim($_POST['txtNoticeCircularSubject']));
        }

        if (isset($_POST['txtNoticeCircularDetails']))
        {
            $Clean['NoticeCircularDetails'] = strip_tags(trim($_POST['txtNoticeCircularDetails']));
        }

		$NewRecordValidator = new Validator();

        if (count($Clean['SelectedStaffList']) <= 0 && count($Clean['SelectedClassList']) <= 0) 
        {
            $NewRecordValidator->AttachTextError('Please select atleast one staff or class.');  
            $HasErrors = true;
            break;
        }

        $Counter = 0;

		if (count($Clean['SelectedStaffList']) > 0)
        {
            foreach ($Clean['SelectedStaffList'] as $StaffID) 
            {
                $NewRecordValidator->ValidateInSelect($StaffID, $StaffList, 'Unknown Error, Please try again.');

                $NoticeCircularApplicableFor[$Counter]['ApplicableFor'] = 'Staff';
                $NoticeCircularApplicableFor[$Counter]['StaffOrClassID'] = $StaffID;

                $Counter++;
            }
        }

        if (count($Clean['SelectedClassList']) > 0)
        {
            foreach ($Clean['SelectedClassList'] as $ClassID)
            {
                $NewRecordValidator->ValidateInSelect($ClassID, $ClassList, 'Unknown Error, Please Try Again.');

                $NoticeCircularApplicableFor[$Counter]['ApplicableFor'] = 'Class';
                $NoticeCircularApplicableFor[$Counter]['StaffOrClassID'] = $ClassID;

                $Counter++;
            }
        }
        
		$NewRecordValidator->ValidateDate($Clean['NoticeCircularDate'], 'Please enter a valid date.');
		$NewRecordValidator->ValidateStrings($Clean['NoticeCircularSubject'], 'Notice subject is required and should be between 1 and 100 characters.', 1, 100);
		$NewRecordValidator->ValidateStrings($Clean['NoticeCircularDetails'], 'Notice details is required and should be between 1 and characters characters.', 1, 2000);
		
		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}
				
		$NewNoticeCircular = new NoticeCircular();
				
		$NewNoticeCircular->SetNoticeCircularDate(date('Y-m-d', strtotime(DateProcessing::ToggleDateDayAndMonth(($Clean['NoticeCircularDate'])))));
		$NewNoticeCircular->SetNoticeCircularSubject($Clean['NoticeCircularSubject']);
        $NewNoticeCircular->SetNoticeCircularDetails($Clean['NoticeCircularDetails']);
        
		$NewNoticeCircular->SetIsActive(1);

        $NewNoticeCircular->SetCreateUserID($LoggedUser->GetUserID());
        
        $NewNoticeCircular->SetNoticeCircularApplicableFor($NoticeCircularApplicableFor);

		if (!$NewNoticeCircular->Save())
		{
			$NewRecordValidator->AttachTextError(ProcessErrors($NewNoticeCircular->GetLastErrorCode()));
			$HasErrors = true;
			break;
		}
	
		FcmSendNotification::SendNoticeNotification($Clean['NoticeCircularSubject'], $NoticeCircularApplicableFor);
		
		header('location:notices_circulars_list.php?Mode=AS');
		exit;
	break;
}

require_once('../html_header.php');
?>
<title>Add Notice & Circular</title>
<link href="../vendor/jquery-ui/jquery-ui.min.css" rel="stylesheet">
<style>
.fileds_selector
{
	height: 150px;
	overflow: auto;
	border: 1px solid #ccc;
	padding: 5px;
	width: 280px;
	line-height: 25px;
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
                    <h1 class="page-header">Add Notice & Circular</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="AddNoticeCircular" action="add_notices_circulars.php" method="post">
            	<div class="panel panel-default">
                    <div class="panel-heading">
                        Enter Notice & Circular Details
                    </div>
                    <div class="panel-body">
<?php
						if ($HasErrors == true)
						{
							echo $NewRecordValidator->DisplayErrors();
						}
?>                    
                    	<div class="form-group">
                            <label for="SelectedStaffList" class="col-lg-2 control-label">Select Staff</label>
                            <div class="col-lg-5" style="position: relative;">
								<div class="fileds_selector">
								    <input type="text" name="filter_staff" id="filter_staff" class="form-control" style="margin-bottom:10px;" />
<?php
									foreach ($StaffList as $StaffID => $StaffName)
									{
										$Checked = (in_array($StaffID, $Clean['SelectedStaffList'])) ? ' checked="checked"' : '';
										echo '<div><label style="font-weight: normal;"><input type="checkbox" name="drdSelectedStaffList[]" class="report_columns_filter"  ' . $Checked . ' value="' . $StaffID . '">&nbsp;' . $StaffName['FirstName'] . ' ' . $StaffName['LastName'] . '</label></div>';
									}
?>
								</div>
                            </div>
							<div class="col-lg-5"><a href="javascript:void(0);" id="select-all">Select All</a></div>
                        </div>
                        <div class="form-group">
                            <label for="SelectedClassList" class="col-lg-2 control-label">Class</label>
                            <div class="col-lg-8">
                            <label class="checkbox-inline"><input class="custom-radio" type="checkbox" id="AllClasses" name="chkAllClasses" value="" />All </label>
<?php
                                foreach ($ClassList as $ClassID => $ClassName) 
                                {
?>
                                    <label class="checkbox-inline"><input class="custom-radio" type="checkbox" id="<?php echo $ClassID; ?>" name="chkSelectedClassList[]" <?php echo (in_array($ClassID, $Clean['SelectedClassList']) ? 'checked="checked"' : ''); ?> value="<?php echo $ClassID; ?>" />
                                        <?php echo $ClassName; ?>
                                    </label>
<?php
                                }
?>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="NoticeCircularDate" class="col-lg-2 control-label">Notice/Circular Date</label>
                            <div class="col-lg-5">
                            	<input class="form-control select-date" type="text" maxlength="10" id="NoticeCircularDate" name="txtNoticeCircularDate" value="<?php echo $Clean['NoticeCircularDate']; ?>" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="NoticeCircularSubject" class="col-lg-2 control-label">Notice/Circular subject</label>
                            <div class="col-lg-5">
                            	<input class="form-control" type="text" maxlength="100" id="NoticeCircularSubject" name="txtNoticeCircularSubject" value="<?php echo $Clean['NoticeCircularSubject']; ?>" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="NoticeCircularDetails" class="col-lg-2 control-label">Notice/Circular Details</label>
                            <div class="col-lg-9">
                            	<textarea class="form-control"  id="NoticeCircularDetails" name="txtNoticeCircularDetails" rows="12"><?php echo $Clean['NoticeCircularDetails']; ?></textarea>
                            </div>
                        </div>
                        <div class="form-group">
                        <div class="col-sm-offset-2 col-lg-10">
                        	<input type="hidden" name="hdnProcess" value="1" />
							<button type="submit" class="btn btn-primary">Save</button>
                        </div>
                      </div>
                    </div>
                </div>
            </form>
        </div>
        <!-- /#page-wrapper -->

    </div>
    <!-- /#wrapper -->
<?php
require_once('../footer.php');
?>
<script src="../vendor/jquery-ui/jquery-ui.min.js"></script>
	<script type="text/javascript">
	var StaffData = $('.fileds_selector').html();
	$(document).ready(function() {
		$(".select-date").datepicker({
				changeMonth: true,
				changeYear: true,
				dateFormat: 'dd/mm/yy'
		});
		
		$("#AllClasses").change(function () {
	        $("input:checkbox").prop('checked', $(this).prop("checked"));
		});
		
		$('#select-all').click(function (){
			if ($(this).text() == 'Select All')
			{
				$('.report_columns_filter').each(function (){ $(this).prop('checked', 'checked') });
				$(this).text('Remove All');
			}
			else
			{
				$('.report_columns_filter').each(function (){ $(this).prop('checked', false) });
				$(this).text('Select All');
			}
		});
		
		if ($('.report_columns_filter:checked').length == '<?php echo count($StaffList); ?>')
		{
			$('#select-all').text('Remove All');
		}
		else
		{
			$('#select-all').text('Select All');
		}
		
		$('#filter_staff').keyup(function () {
		    $('.fileds_selector div label').css("display", "block");
		    
		    if ($(this).val() == '')
		    {
		        return;
		    }
		    
		    FilterData = $(this).val();
		    
		    $('.fileds_selector div label').filter(function () {
		        if (!$.trim($(this).closest('label').text().toLowerCase()).includes(FilterData.toLowerCase()))
		        {
		            return true;
		        }
		    }).css("display", "none");
		});
	});
    </script>
</body>
</html>
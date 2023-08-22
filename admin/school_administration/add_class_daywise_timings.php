<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once("../../classes/school_administration/class.school_session_class_daywise_timings.php");
require_once("../../classes/school_administration/class.classes.php");
require_once("../../classes/school_administration/class.school_sessions.php");
require_once("../../classes/school_administration/class.school_timing_parts_master.php");

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

$ClassList = array();
$ClassList = AddedClass::GetAllClasses(true);

$SessionList = array();
$SessionList = SchoolSessions::GetAllSchoolSessions(true);

$AllPeriods = array();

for($i = 1; $i <= 8; $i++)
{
    $AllPeriods[$i]['TimingPart'] = '';
    $AllPeriods[$i]['PeriodStartTime'] = '';
    $AllPeriods[$i]['PeriodEndTime'] = '';
}

$TimingPartList = array();
$TimingPartList = SchoolTimingPartsMaster::GetTimingPartDetails();

$DayList = array('1'=>'Monday', '2'=>'Tuesday', '3'=>'Wednesday', '4'=>'Thursday', '5'=>'Friday', '6'=>'Saturday', '7'=>'Sunday');

$Clean = array();

$Clean['Process'] = 0;

$Clean['SchoolSessionID'] = 0;

$Clean['ClassList'] = array(); 
$Clean['DayList'] = array();

$Clean['StartTime'] = '';
$Clean['EndTime'] = '';

$Clean['SchoolTimingPartList'] = array();

$Clean['PeriodStartTimeList'] = array();
$Clean['PeriodEndTimeList'] = array();

$TimingPartsErrors = array();

if (isset($_POST['hdnProcess']))
{
	$Clean['Process'] = (int) $_POST['hdnProcess'];
}
switch ($Clean['Process'])
{
	case 1:
		if (isset($_POST['drdSchoolSessionID']))
		{
			$Clean['SchoolSessionID'] = (int) $_POST['drdSchoolSessionID'];
		}

		if (isset($_POST['chkClass']))
        {
            $Clean['ClassList'] = $_POST['chkClass'];
        }

        if (isset($_POST['chkDay']))
        {
            $Clean['DayList'] = $_POST['chkDay'];
        }

		if (isset($_POST['txtStartTime']))
		{
			$Clean['StartTime'] = strip_tags(trim($_POST['txtStartTime']));
		}

		if (isset($_POST['txtEndTime']))
        {
            $Clean['EndTime'] = strip_tags(trim($_POST['txtEndTime']));;
        }

        if (isset($_POST['drdSchoolTimingPartID']) && is_array($_POST['drdSchoolTimingPartID']))
        {
            $Clean['SchoolTimingPartList'] = $_POST['drdSchoolTimingPartID'];
        }

        if (isset($_POST['txtPeriodStartTime']))
        {
            $Clean['PeriodStartTimeList'] = $_POST['txtPeriodStartTime'];
        }

        if (isset($_POST['txtPeriodEndTime']))
        {
            $Clean['PeriodEndTimeList'] = $_POST['txtPeriodEndTime'];
        }
        
        $NewRecordValidator = new Validator();

        $NewRecordValidator->ValidateInSelect($Clean['SchoolSessionID'], $SessionList, "Unknown Error, Please Try Again.");

        if (count($Clean['ClassList']) <= 0) 
        {
            $NewRecordValidator->AttachTextError('Please select atleast one class.');
        }
        else
        {
            foreach ($Clean['ClassList'] as $ClassID)
            {
                $NewRecordValidator->ValidateInSelect($ClassID, $ClassList, "Unknown Error, Please Try Again.");
            }
        }

        if (count($Clean['DayList']) <= 0) 
        {
            $NewRecordValidator->AttachTextError('Please select atleast one day.');
        }
        else
        {
            foreach ($Clean['DayList'] as $Day)
            {
                $NewRecordValidator->ValidateInSelect($Day, $DayList, "Unknown Error, Please Try Again." );
            }
        }

        $NewRecordValidator->ValidateTimeFormatted($Clean['StartTime'], "Please enter correct start time." );

        if (isset($Clean['SchoolTimingPartList'])) 
        {
            $RecordValidator = new Validator();

            $AllPeriods = array();
            $Counter = 1;

			/*echo '<pre>';
			print_r($Clean['PeriodStartTimeList']);
			echo 'PeriodEndTimeList: '."<br />";
			print_r($Clean['PeriodEndTimeList']);*/
			
            foreach ($Clean['SchoolTimingPartList'] as $SNo => $TimingPartID) 
            {
                $PeriodStartTime = '';
                $PeriodEndTime = '';

                if (isset($Clean['PeriodStartTimeList'][$SNo]))
                {
                    $PeriodStartTime = strip_tags(trim($Clean['PeriodStartTimeList'][$SNo]));
                }

                if (isset($Clean['PeriodEndTimeList'][$SNo]))
                {
                    $PeriodEndTime = strip_tags(trim($Clean['PeriodEndTimeList'][$SNo]));
                }

                if ($TimingPartID == 0 &&  $PeriodStartTime == '' && $PeriodEndTime == '')
                {
                    continue;
                }

                if (!$RecordValidator->ValidateInSelect($TimingPartID, $TimingPartList, "Please select a valid period." ))
                {
                    $TimingPartsErrors[$Counter]['TimingPart'] = 'Please select a valid period.';
                }
                if (!$RecordValidator->ValidateTimeFormatted($PeriodStartTime, "Please enter correct start time." ))
                {
                    $TimingPartsErrors[$Counter]['PeriodStartTime'] = 'Please enter correct start time.';
                }
                if (!$RecordValidator->ValidateTimeFormatted($PeriodEndTime, "Please enter correct end time." ))
                {
                    $TimingPartsErrors[$Counter]['PeriodEndTime'] = 'Please enter correct end time.';
                }

                $AllPeriods[$Counter]['TimingPart'] = $TimingPartID;
                $AllPeriods[$Counter]['PeriodStartTime'] = date('H:i:s', strtotime($PeriodStartTime));
                $AllPeriods[$Counter]['PeriodEndTime'] = date('H:i:s', strtotime($PeriodEndTime));
                
                $Counter++;
            }

            if ($RecordValidator->HasNotifications())
            {
                $NewRecordValidator->AttachTextError('There were error in the period details entered by you, please scrool down to see indivisual errors.');
            }
        }
		
        $NewRecordValidator->ValidateTimeFormatted($Clean['EndTime'], "Please enter correct end time." );

		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}
		
		$NewSchoolSessionClassDaywiseTiming = new SchoolSessionClassDaywiseTiming();
				
		$NewSchoolSessionClassDaywiseTiming->SetSchoolSessionID($Clean['SchoolSessionID']);
        $NewSchoolSessionClassDaywiseTiming->SetClassList($Clean['ClassList']);
		$NewSchoolSessionClassDaywiseTiming->SetDayList($Clean['DayList']);

		$NewSchoolSessionClassDaywiseTiming->SetStartTime(date('H:i:s', strtotime($Clean['StartTime'])));
		$NewSchoolSessionClassDaywiseTiming->SetEndTime(date('H:i:s', strtotime($Clean['EndTime'])));
		
        $NewSchoolSessionClassDaywiseTiming->SetCreateUserID($LoggedUser->GetUserID());

		$NewSchoolSessionClassDaywiseTiming->SetAllPeriods($AllPeriods);

		if (!$NewSchoolSessionClassDaywiseTiming->Save())
		{
			$NewRecordValidator->AttachTextError(ProcessErrors($NewSchoolSessionClassDaywiseTiming->GetLastErrorCode()));
			$HasErrors = true;

			break;
		}
		
		header('location:school_timing_parts_list.php?Mode=AS');
		exit;
	break;
}

require_once('../html_header.php');
?>
<title>Add Daywise Timing</title>
<link rel="stylesheet" href="/admin/vendor/bootstrap-datetimepicker-master/build/css/bootstrap-datetimepicker.min.css" />
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
                    <h1 class="page-header">Add Daywise Timing</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="AddDaywiseTiming" id="AddDaywiseTiming" action="add_class_daywise_timings.php" method="post">
            	<div class="panel panel-default">
                    <div class="panel-heading">
                        Enter Timing Details
                    </div>
                    <div class="panel-body">
<?php
						if ($HasErrors == true)
						{
							echo $NewRecordValidator->DisplayErrors();
						}
?>                    
                    	<div class="form-group">
                            <label for="SchoolSessionID" class="col-lg-2 control-label">Session</label>
                            <div class="col-lg-4">
                                <select class="form-control" name="drdSchoolSessionID" id="SchoolSessionID">
<?php
                                foreach ($SessionList as $SessionID => $Session) 
                                {
                                    echo '<option '.($Clean['SchoolSessionID'] == $SessionID ? 'selected="selected"' : '').' value="'.$SessionID.'">'.$Session.'</option>';
                                }
?>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="ClassID" class="col-lg-2 control-label">Class</label>
                            <div class="col-lg-8">
                            <label class="checkbox-inline"><input class="custom-radio" type="checkbox" id="AllClasses" name="chkAllClasses" value="" />All </label>
<?php
                                foreach ($ClassList as $ClassID => $Class) 
                                {
?>
                                    <label class="checkbox-inline"><input class="custom-radio" type="checkbox" id="<?php echo $ClassID; ?>" name="chkClass[]" <?php echo (in_array($ClassID, $Clean['ClassList']) ? 'checked="checked"' : ''); ?> value="<?php echo $ClassID; ?>" />
                                        <?php echo $Class; ?>
                                    </label>
<?php
                                }
?>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="DayID" class="col-lg-2 control-label">Day</label>
                            <div class="col-lg-8">
                            <label class="checkbox-inline"><input class="custom-radio" type="checkbox" id="AllDay" name="chkAllDay" value="" />All </label>
<?php
                                foreach ($DayList as $DayID => $Day) 
                                {
?>
                                    <label class="checkbox-inline"><input class="custom-radio" type="checkbox" id="<?php echo $DayID; ?>" name="chkDay[]" <?php echo (in_array($DayID, $Clean['DayList']) ? 'checked="checked"' : ''); ?> value="<?php echo $DayID; ?>" />
                                        <?php echo $Day; ?>
                                    </label>
<?php
                                }
?>
                            </div>
                        </div>
                            
                        <div class="form-group">
                            <label for="StartTime" class="col-lg-2 control-label">Start Time</label>
                            <div class="col-lg-4">
                            	<input class="form-control TimeMatch" id="StartTime" name="txtStartTime" value="<?php echo ($Clean['StartTime'] ? $Clean['StartTime'] : ''); ?>" />
                            </div>
                        </div>

<?php
                    foreach ($AllPeriods as $SNo => $PeriodDetails)
                    {
?>
                         <div class="form-group PeriodDetailContainer">
                            <label for="SchoolTimingPartID" class="col-lg-2 control-label">Period Detail</label>
                            <div class="col-lg-3<?php echo isset($TimingPartsErrors[$SNo]['TimingPart']) ? ' has-error' : ''; ?>">
                                <select data-toggle="tooltip" title="<?php echo isset($TimingPartsErrors[$SNo]['TimingPart']) ? $TimingPartsErrors[$SNo]['TimingPart'] : ''; ?>" class="form-control SchoolTimingPart" name="drdSchoolTimingPartID[<?php echo $SNo; ?>]" id="SchoolTimingPartID<?php echo $SNo; ?>">
                                    <option value="0">Select Period</option>
<?php
                                foreach ($TimingPartList as $TimingPartID => $TimingPart) 
                                {
                                    echo '<option '.($PeriodDetails['TimingPart'] == $TimingPartID ? 'selected="selected"' : '').' default-duration="'.$TimingPart['DefaultDuration'].'" value="'.$TimingPartID.'">'.$TimingPart['TimingPart'].' ('.$TimingPart['PartType'].')</option>';
                                }
?>
                                </select>
                            </div>
                            <div class="col-lg-2<?php echo isset($TimingPartsErrors[$SNo]['PeriodStartTime']) ? ' has-error' : ''; ?>">
                                <input data-toggle="tooltip" title="<?php echo isset($TimingPartsErrors[$SNo]['PeriodStartTime']) ? $TimingPartsErrors[$SNo]['PeriodStartTime'] : ''; ?>" <?php echo ($PeriodDetails['TimingPart'] ? '' : 'readonly="readonly"'); ?> class="form-control PeriodFrom TimeMatch" id="PeriodStartTime<?php echo $SNo; ?>" name="txtPeriodStartTime[<?php echo $SNo; ?>]" value="<?php echo $PeriodDetails['PeriodStartTime']; ?>" />
                            </div>
                            <div class="col-lg-2<?php echo isset($TimingPartsErrors[$SNo]['PeriodEndTime']) ? ' has-error' : ''; ?>">
                                <input data-toggle="tooltip" title="<?php echo isset($TimingPartsErrors[$SNo]['PeriodEndTime']) ? $TimingPartsErrors[$SNo]['PeriodEndTime'] : ''; ?>" <?php echo ($PeriodDetails['TimingPart'] ? '' : 'readonly="readonly"'); ?> class="form-control PeriodTo TimeMatch" id="PeriodEndTime<?php echo $SNo; ?>" name="txtPeriodEndTime[<?php echo $SNo; ?>]" value="<?php echo $PeriodDetails['PeriodEndTime']; ?>" />
                            </div>
                            <div class="col-lg-2">
                                <button type="button" class="btn btn-sm btn-danger RemovePeriodDetailContainer">Remove</button>
                            </div>
                        </div>
<?php   
                    }

                    if (count($AllPeriods) < 8)
                    {
                        for ($Counter = (count($AllPeriods) + 1); $Counter <= 8; $Counter++)
                        {
?>
                            <div class="form-group PeriodDetailContainer">
                                <label for="SchoolTimingPartID" class="col-lg-2 control-label">Period Detail</label>
                                <div class="col-lg-3">
                                    <select class="form-control SchoolTimingPart" name="drdSchoolTimingPartID[<?php echo $Counter; ?>]" id="SchoolTimingPartID<?php echo $Counter; ?>">
                                        <option value="0">Select Period</option>
<?php
                                    foreach ($TimingPartList as $TimingPartID => $TimingPart) 
                                    {
                                        echo '<option default-duration="'.$TimingPart['DefaultDuration'].'" value="'.$TimingPartID.'">'.$TimingPart['TimingPart'].' ('.$TimingPart['PartType'].')</option>';
                                    }
?>
                                    </select>
                                </div>
                                <div class="col-lg-2">
                                    <input readonly="readonly" class="form-control PeriodFrom TimeMatch" id="PeriodStartTime<?php echo $Counter; ?>" name="txtPeriodStartTime[<?php echo $Counter; ?>]" value="" />
                                </div>
                                <div class="col-lg-2">
                                    <input readonly="readonly" class="form-control PeriodTo TimeMatch" id="PeriodEndTime<?php echo $Counter; ?>" name="txtPeriodEndTime[<?php echo $Counter; ?>]" value="" />
                                </div>
                                <div class="col-lg-2">
                                    <button type="button" class="btn btn-sm btn-danger RemovePeriodDetailContainer">Remove</button>
                                </div>
                            </div>
<?php
                        }
                    }
?>                        
                        <div id="AddPeriod">
                            
                        </div>

                        <div class="form-group">
                            <div class="col-sm-offset-2 col-lg-10">
                                <button type="button" class="btn btn-sm btn-info" id="NewPeriod">Add New Period</button>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="EndTime" class="col-lg-2 control-label">End Time</label>
                            <div class="col-lg-4">
                            	<input class="form-control TimeMatch" id="EndTime" readonly="readonly" name="txtEndTime" value="<?php echo ($Clean['EndTime'] ? $Clean['EndTime'] : ''); ?>" />
                            </div>
                        </div>
                        <div class="form-group">
                        <div class="col-sm-offset-2 col-lg-10">
                        	<input type="hidden" name="hdnProcess" value="1" />
							<button type="submit" id="SaveButtonID" class="btn btn-primary">Save</button>
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
<script type="text/javascript" src="https://momentjs.com/downloads/moment-with-locales.js"></script>
<script type="text/javascript" src="/admin/vendor/bootstrap-datetimepicker-master/build/js/bootstrap-datetimepicker.min.js"></script>
<script type="text/javascript">
    $(document).ready(function() {
        $('[data-toggle="tooltip"]').tooltip();

		$('.TimeMatch').datetimepicker({
			format: 'LT'
		});
		
        $('#NewPeriod').click(function(){
            var Counter = 0;
            Counter = parseInt($('.PeriodDetailContainer').length) + 1;

            var Data = '<div class="form-group PeriodDetailContainer">'
                Data += '<label for="SchoolTimingPartID" class="col-lg-2 control-label">Period Detail</label>'
                Data += '<div class="col-lg-3">'
                Data += '<select class="form-control SchoolTimingPart" name="drdSchoolTimingPartID['+Counter+']" id="SchoolTimingPartID'+Counter+'">'
                Data += '<option value="0">Select Period</option>'
<?php
            foreach ($TimingPartList as $TimingPartID => $TimingPart) 
            {
?>
                Data += '<option default-duration="<?php echo $TimingPart['DefaultDuration']; ?>" value="<?php echo $TimingPartID; ?>"><?php echo $TimingPart['TimingPart'].' ('.$TimingPart['PartType'].')'; ?></option>'
<?php
            }
?>
                Data += '</select>'
                Data += '</div>'
                Data += '<div class="col-lg-2">'
                Data += '<input readonly="readonly" class="form-control PeriodFrom TimeMatch" id="PeriodStartTime'+Counter+'" name="txtPeriodStartTime['+Counter+']" value="" />'
                Data += '</div>'
                Data += '<div class="col-lg-2">'
                Data += '<input readonly="readonly" class="form-control PeriodTo TimeMatch" id="PeriodEndTime'+Counter+'" name="txtPeriodEndTime['+Counter+']" value="" />'
                Data += '</div>'
                Data += '<div class="col-lg-2">'
                Data += '<button type="button" class="btn btn-sm btn-danger RemovePeriodDetailContainer">Remove</button>'
                Data += '</div>'
                Data += '</div>';

            $('#AddPeriod').append(Data);
        });

        $('body').on('click', '.RemovePeriodDetailContainer', function(){
            if ($('.PeriodDetailContainer').length <= 1)
            {
                alert('At least one period is required, thus you cannot delete this section.');
                return false;
            }

            $(this).closest('.PeriodDetailContainer').remove();
        });

        $('#StartTime').focusout(function(){
            $('.PeriodDetailContainer').find('.TimeMatch').eq(0).val($(this).val());
        });

        $('body').on('focusout', '.PeriodTo', function(){
            NextPeriodFromElement = $(this).closest('.PeriodDetailContainer').next().find('.PeriodFrom');
            
            if (NextPeriodFromElement.length > 0)
            {
                /*if (NextPeriodFromElement.val() == '')
                {
                    NextPeriodFromElement.val($(this).val());
                }*/

                NextPeriodFromElement.val($(this).val());
            }
            else
            {
                $('#EndTime').val($(this).val());
            }
        });

        $('body').on('change', '.SchoolTimingPart', function(){
            if ($(this).val() > 0)
            {
                // Unlocking time textboxes
                $(this).parent().removeClass('has-error');
                $(this).closest('.PeriodDetailContainer').find('.TimeMatch').prop('readonly', false);

                // Automatically fill end time of the period & start time of next period
                var StartTime = $(this).closest('.PeriodDetailContainer').find('.PeriodFrom').val();
                
                if (StartTime == '')
                {
                    StartTime = $(this).closest('.PeriodDetailContainer').prev().find('.PeriodTo').val();
                    
                    if (StartTime == null)
                    {
                        /*if ($('.PeriodDetailContainer').length > 1)
                        {
                            alert('Two PeriodDetailContainer found but error.');
                            //console.log($(this).closest('.PeriodDetailContainer'));
                        }*/
                        
                        StartTime = $('#StartTime').val();
                    }

                    $(this).closest('.PeriodDetailContainer').find('.PeriodFrom').val(StartTime);
                }

                var EndTime = moment.utc(StartTime,'LT').add($(this).find('option:selected').attr('default-duration'),'minutes').format('LT');
				
                $(this).closest('.PeriodDetailContainer').find('.PeriodTo').val(EndTime).focusout();
            }
            else
            {
                $(this).closest('.PeriodDetailContainer').find('.TimeMatch').val('').prop('readonly', true);
            }
        });
        
        $('.TimeMatch').focusout(function(){
            if ($(this).val() != '')
            {
                $(this).parent().removeClass('has-error');
            }
        });

        $('#SaveButtonID').click(function(){
            var TotalTimingParts = $('.PeriodDetailContainer').length;
            
            var Counter = 0;
            var CurrentElementTime = '';
            
            var ReturnValue = true;

            $('.TimeMatch').each(function() {
                Counter++;

                if (Counter == 1)
                {
                    if ($(this).val() == '')
                    {
                        $(this).parent().addClass('has-error');
                        ReturnValue = false;
                        return;
                    }

                    CurrentElementTime = $(this).val();
                    return;
                }

                if ($(this).val() == '')
                {
                    $(this).parent().addClass('has-error');
                    ReturnValue = false;
                    return;
                }
            });

            return ReturnValue;
        });
    });
</script>
</body>
</html>
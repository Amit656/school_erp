<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once("../../classes/school_administration/class.academic_calendar.php");

require_once("../../classes/class.date_processing.php");

require_once("../../classes/school_administration/class.classes.php");

require_once("../../includes/global_defaults.inc.php");

require_once("../../includes/helpers.inc.php");

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

if ($LoggedUser->HasPermissionForTask(TASK_EDIT_ACADEMIC_CALENDER_EVENT) !== true)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}

$Clean = array();
$Clean['Process'] = 0;

$Clean['AcademicCalendarID'] = 0;

if (isset($_GET['AcademicCalendarID']))
{
    $Clean['AcademicCalendarID'] = (int) $_GET['AcademicCalendarID'];
}
elseif (isset($_POST['hdnAcademicCalendarID']))
{
    $Clean['AcademicCalendarID'] = (int) $_POST['hdnAcademicCalendarID'];
}

if ($Clean['AcademicCalendarID'] <= 0)
{
    header('location:../error.php');
    exit;
}   

try
{
    $AcademicCalendarToEdit = new AcademicCalendar($Clean['AcademicCalendarID']);
}
catch (ApplicationDBException $e)
{
    header('location:../error.php');
    exit;
}
catch (Exception $e)
{
    header('location:../error.php');
    exit;
}

$HasErrors = false;

$AddedClass = new AddedClass();
$AllClassSections =  $AddedClass->GetClassAndSections();

$ValidateRadio = array(0,1);

$AllUsers['Students']['LableName'] = 'Students';
$AllUsers['Students']['IDNameHoliday'] = 'HolidayForStudent';
$AllUsers['Students']['IDNameNotification'] = 'Students';

$AllUsers['TeachingStaff']['LableName'] = 'Teaching Staff';
$AllUsers['TeachingStaff']['IDNameHoliday'] = 'HolidayTeachingStaff';
$AllUsers['TeachingStaff']['IDNameNotification'] = 'TeachingStaff';

$AllUsers['NonTeachingStaff']['LableName'] = 'Non Teaching Staff';
$AllUsers['NonTeachingStaff']['IDNameHoliday'] = 'HolidayNonTeachingStaff';
$AllUsers['NonTeachingStaff']['IDNameNotification'] = 'NonTeachingStaff';

$Clean['Process'] = 0;

$Clean['EventStartDate'] = '';
$Clean['EventEndDate'] = '';

$Clean['EventName'] = '';
$Clean['EventDetails'] = '';

$Clean['IsHoliday'] = 0;
$Clean['ToNotify'] = 0;

$Clean['NotificationMessage'] = '';
$Clean['NotificationDate'] = '';

$Clean['HolidayForUsers'] = array();
$Clean['HolidayForClasses'] = array();
$Clean['NotificationForUsers'] = array();
$Clean['NotificationForClasses'] = array();

$Clean['AllEventsDates'] = array();

$Clean['HolidayAll'] = '';
$Clean['NotificationAll'] = '';

if (isset($_POST['hdnProcess']))
{
	$Clean['Process'] = (int) $_POST['hdnProcess'];
}
else if (isset($_GET['Process']))
{
    $Clean['Process'] = (int) $_GET['Process'];
}
switch ($Clean['Process'])
{
	case 3:		
		if (isset($_POST['txtEventStartDate']))
		{
			$Clean['EventStartDate'] = strip_tags(trim($_POST['txtEventStartDate']));
		}	
		if (isset($_POST['txtEventEndDate']))
		{
			$Clean['EventEndDate'] = strip_tags(trim($_POST['txtEventEndDate']));
		}

		if (isset($_POST['txtEventName']))
		{
			$Clean['EventName'] = strip_tags(trim($_POST['txtEventName']));
		}
		if (isset($_POST['txtEventDetails']))
		{
			$Clean['EventDetails'] = strip_tags(trim($_POST['txtEventDetails']));
		}
		if (isset($_POST['rdbHoliday']))
		{
			$Clean['IsHoliday'] = (int) $_POST['rdbHoliday'];
		}
		if (isset($_POST['rdbNotification']))
		{
			$Clean['ToNotify'] = (int)$_POST['rdbNotification'];
		}  
		if (isset($_POST['txtMessage']))
		{
			$Clean['NotificationMessage'] = strip_tags(trim($_POST['txtMessage']));
		}
		if (isset($_POST['txtNotificatonDate']))
		{
			$Clean['NotificationDate'] = strip_tags(trim($_POST['txtNotificatonDate']));
		}
		if (isset($_POST['chkHolidayForUsers']) && is_array($_POST['chkHolidayForUsers']))
		{
			$Clean['HolidayForUsers'] = $_POST['chkHolidayForUsers'];
		}
		if (isset($_POST['chkHolidayForClasses']) && is_array($_POST['chkHolidayForClasses']))
		{
			$Clean['HolidayForClasses'] = $_POST['chkHolidayForClasses'];
		}
		if (isset($_POST['chkNotificationForUsers']) && is_array($_POST['chkNotificationForUsers']))
		{
			$Clean['NotificationForUsers'] = $_POST['chkNotificationForUsers'];
		}
		if (isset($_POST['chkNotificationForClasses']))
		{
			$Clean['NotificationForClasses'] = $_POST['chkNotificationForClasses'];
		}
        if (isset($_POST['HolidayAll']))
        {
          $Clean['HolidayAll'] = $_POST['HolidayAll'];
        }
        if (isset($_POST['NotificationAll']))
        {
          $Clean['NotificationAll'] = $_POST['NotificationAll'];
        }

    	$NewRecordValidator = new Validator();
        $NewRecordValidator->ValidateDate($Clean['EventStartDate'], 'Please Select Valid Date.');
        
        $NewRecordValidator->ValidateDate($Clean['EventEndDate'], 'Please Select Valid Date.');
        
        if (strtotime(DateProcessing::ToggleDateDayAndMonth($Clean['EventStartDate'])) > strtotime(DateProcessing::ToggleDateDayAndMonth($Clean['EventEndDate'])))
        {
            $NewRecordValidator->AttachTextError('Start Date Should be Greater Then End Date.');
        }

    	$NewRecordValidator->ValidateStrings($Clean['EventName'], 'Event Name is required and should be between 1 and 100 characters.', 1, 100);
    	
    	$NewRecordValidator->ValidateStrings($Clean['EventDetails'], 'Event Details is required and should be between 1 and 1000 characters.', 1, 1000);

        $NewRecordValidator->ValidateInSelect($Clean['IsHoliday'], $ValidateRadio, 'Unknown Error, Please Try Again.' );
        
        if ($Clean['IsHoliday'] == 1)
        {
            foreach ($Clean['HolidayForUsers'] as $key => $HolidayFor)
            {
                $NewRecordValidator->ValidateInSelect($HolidayFor, $AllUsers, 'Unknown Error, Please Try Again.' );

                if ($HolidayFor == 'Students')
                {   
                    if (count($Clean['HolidayForClasses']) <= 0) 
                    {
                        $NewRecordValidator->AttachTextError('Please select atleast one class section for holiday.');
                        $HasErrors = true;
                        break;
                    }
                    
                    foreach ($Clean['HolidayForClasses'] as $HolidayClassSectionID => $Value)
                    {
                      $NewRecordValidator->ValidateInSelect($HolidayClassSectionID , $AllClassSections, 'Unknown Error, Please Try Again.' );
                    }
                }

            }
        }

        $NewRecordValidator->ValidateInSelect($Clean['ToNotify'], $ValidateRadio, 'Please Select valid Option.');

        if ($Clean['ToNotify'] == 1)
        {
            $NewRecordValidator->ValidateStrings($Clean['NotificationMessage'], 'Notification Message is required and should be between 1 and 1000 characters.', 1, 1000);  
            $NewRecordValidator->ValidateDate($Clean['NotificationDate'], 'Please Select Valid Date.'); 

            foreach ($Clean['NotificationForUsers'] as $key => $NotificationFor)
            {
              $NewRecordValidator->ValidateInSelect($NotificationFor , $AllUsers, 'Unknown Error, Please Try Again.');
              
                if ($NotificationFor == 'Students')
                {   
                    if (count($Clean['NotificationForClasses']) <= 0) 
                    {
                        $NewRecordValidator->AttachTextError('Please select atleast one class section for notification.');
                        $HasErrors = true;
                        break;
                    }

                    foreach ($Clean['NotificationForClasses'] as $NotificationForClassSectionID => $NotificationForClasses)
                    {
                        $NewRecordValidator->ValidateInSelect($NotificationForClassSectionID , $AllClassSections, 'Unknown Error, Please Try Again.');
                    }
                }
            }
        }

        if ($NewRecordValidator->HasNotifications())
    	{
    		$HasErrors = true;
    		break;
    	}
        
        $AcademicCalendarToEdit->SetEventStartDate(DateProcessing::ToggleDateDayAndMonth($Clean['EventStartDate']));
        $AcademicCalendarToEdit->SetEventEndDate(DateProcessing::ToggleDateDayAndMonth($Clean['EventEndDate']));
     
        $AcademicCalendarToEdit->SetEventName($Clean['EventName']);
        $AcademicCalendarToEdit->SetEventDetails($Clean['EventDetails']);

        $AcademicCalendarToEdit->SetIsHoliday($Clean['IsHoliday']);
        $AcademicCalendarToEdit->SetToNotify($Clean['ToNotify']);

        $AcademicCalendarToEdit->SetNotificationMessage($Clean['NotificationMessage']);

        if ($Clean['ToNotify'] == 1 && $Clean['NotificationDate'] != '')
        {
           $AcademicCalendarToEdit->SetNotificationDate(DateProcessing::ToggleDateDayAndMonth($Clean['NotificationDate'])); 
        }   

        $AcademicCalendarToEdit->SetHolidayForUsers($Clean['HolidayForUsers']);
        $AcademicCalendarToEdit->SetHolidayForClasses($Clean['HolidayForClasses']);
        $AcademicCalendarToEdit->SetNotificationForUsers($Clean['NotificationForUsers']);
        $AcademicCalendarToEdit->SetNotificationForClasses($Clean['NotificationForClasses']);

        $Clean['AllEventsDates'] = GetRangeDates(DateProcessing::ToggleDateDayAndMonth($Clean['EventStartDate']), DateProcessing::ToggleDateDayAndMonth($Clean['EventEndDate']));

        $AcademicCalendarToEdit->SetAllEventsDates($Clean['AllEventsDates']);
        $AcademicCalendarToEdit->SetCreateUserID($LoggedUser->GetUserID());

    	if (!$AcademicCalendarToEdit->Save())
    	{
    		$NewRecordValidator->AttachTextError(ProcessErrors($AcademicCalendarToEdit->GetLastErrorCode()));
    		$HasErrors = true;
    		break;
    	}
    		
    	header('location:academic_calendar_list.php?Mode=UD');
    	exit;
	break;

    case 2:
        $Clean['EventStartDate'] = date('d/m/Y', strtotime($AcademicCalendarToEdit->GetEventStartDate()));
        $Clean['EventEndDate'] = date('d/m/Y', strtotime($AcademicCalendarToEdit->GetEventEndDate()));

        $Clean['EventName'] = $AcademicCalendarToEdit->GetEventName();
        $Clean['EventDetails'] = $AcademicCalendarToEdit->GetEventDetails();

        $Clean['IsHoliday'] = $AcademicCalendarToEdit->GetIsHoliday();
        $Clean['ToNotify'] = $AcademicCalendarToEdit->GetToNotify();

        $Clean['NotificationMessage'] = $AcademicCalendarToEdit->GetNotificationMessage();

        if ($AcademicCalendarToEdit->GetNotificationDate() != '0000-00-00')
        {
            $Clean['NotificationDate'] = date('d/m/Y',strtotime($AcademicCalendarToEdit->GetNotificationDate()));     
        }

        $Clean['HolidayForUsers'] = $AcademicCalendarToEdit->GetHolidayForUsers();
        $Clean['HolidayForClasses'] = $AcademicCalendarToEdit->GetHolidayForClasses();
        $Clean['NotificationForUsers'] = $AcademicCalendarToEdit->GetNotificationForUsers();
        $Clean['NotificationForClasses'] = $AcademicCalendarToEdit->GetNotificationForClasses();

        if (count($Clean['HolidayForClasses']) == Count($AllClassSections))
        {
          $Clean['HolidayAll'] = 'All'; 
        }

        if (count($Clean['NotificationForClasses']) == Count($AllClassSections))
        {
          $Clean['NotificationAll'] = 'NotificationAll';    
        }

        break;
}

require_once('../html_header.php');
?>
<title>Edit Academic Calendar</title>
<link href="../vendor/jquery-ui/jquery-ui.min.css" rel="stylesheet">
<style type="text/css">
.col-md-3:not(:empty)
{
    border: 1px solid #ccc;
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
                    <h1 class="page-header">Edit Academic Calendar</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="EditAcademicCalendar" action="edit_academic_calendar.php" method="post">
            	<div class="panel panel-default">
                    <div class="panel-heading">
                        Enter Academic Calendar Details
                    </div>
                    <div class="panel-body">
<?php
						if ($HasErrors == true)
						{
							echo $NewRecordValidator->DisplayErrors();
						}
?>                    
                    	<div class="form-group">
                            <label for="EventStartDate" class="col-lg-2 control-label">Event Start Date</label>
                            <div class="col-lg-8">
                                <input class="form-control dtepicker" type="text" id="EventStartDate" name="txtEventStartDate" value="<?php echo $Clean['EventStartDate']; ?>"/>
                            </div>
                        </div>
                    	<div class="form-group">
                            <label for="EventEndDate" class="col-lg-2 control-label">Event End Date</label>
                            <div class="col-lg-8">
                                <input class="form-control dtepicker" type="text" id="EventEndDate" name="txtEventEndDate" value="<?php echo $Clean['EventEndDate']; ?>"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="EventName" class="col-lg-2 control-label">Event Name</label>
                            <div class="col-lg-8">
                            	<input class="form-control" type="text" maxlength="100" id="EventName" name="txtEventName" value="<?php echo $Clean['EventName']; ?>" />
                            </div>
                        </div>
			            <div class="form-group">
                            <label for="EventDetails" class="col-lg-2 control-label">Event Details</label>
                            <div class="col-lg-8">
                            	<input class="form-control" type="text" maxlength="1000" id="EventDetails" name="txtEventDetails" value="<?php echo $Clean['EventDetails']; ?>" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="Holiday" class="col-lg-2 control-label">Holiday</label>
                            <div class="col-lg-8">
                            	<label style="font-weight: normal;">
                                 <input type="radio" class="txtHoliday" name="rdbHoliday" <?php echo ($Clean['IsHoliday'] == 1 ? 'checked="checked"' : ''); ?> value="1" />&nbsp;Yes&nbsp;
                                </label>
                                <label style="font-weight: normal;">
                                    <input type="radio" class="txtHoliday" name="rdbHoliday" <?php echo ($Clean['IsHoliday'] == 0 ? 'checked="checked"' : ''); ?> value="0"/>&nbsp;No
                                </label>
                            </div>
                        </div>
                        <div class="form-group" id="HolidayDiv" style="<?php echo ($Clean['IsHoliday'] ? 'display: block;' : 'display: none;'); ?>">
                            <label class="col-lg-2 control-label">Holiday For Users</label>
                            <div class="col-lg-8">
<?php
                            foreach ($AllUsers as $HolidayFor => $HolidayForDetails)
                            {
                              echo '<label style="font-weight: normal;"><input type="checkbox" id="' . $HolidayForDetails['IDNameHoliday'] . '" ' . (in_array($HolidayFor, $Clean['HolidayForUsers']) ? 'checked="checked"' : '') . ' id="' . $HolidayFor . '" name="chkHolidayForUsers[]" value="' . $HolidayFor . '"/>&nbsp;' . $HolidayForDetails['LableName'] .'</label>&nbsp;&nbsp;';
                            }
?>
                            </div>
                        </div>  
                        <div class="form-group" id="HolidayclassesDiv" style="<?php echo (!in_array('Students', $Clean['HolidayForUsers']) ? 'display: none;' : 'display: block;'); ?>">
                            <label for="HolidayForUser" class="col-lg-2 control-label">Holiday For Classes</label>
                            <div class="col-lg-8">
<?php
                            if (count($AllClassSections) > 0)
                            {
?>
                                <div class="row">
                                    <div class="col-md-3" style="border: 1px solid #ccc;">
                                        <label><input type="checkbox" id="HolidaySelectAll" name="chkHolidaySelectAll" value="1" <?php echo(count($AllClassSections) == count($Clean['HolidayForClasses'])) ? 'checked="checked"' : '0';?> />Select All</label>
                                    </div>
                                    <div class="col-md-3" style="border: 1px solid #ccc;">
                                        <label><input type="checkbox" id="HolidayClearAll" name="chkHolidayClearAll" value="1"  />Clear All</label>
                                    </div>
                                </div>
<?php
                            $TotalClassAndSections = count($AllClassSections);

                            if ($TotalClassAndSections > 0)
                            {
                                $CurrentClassAndSectionCounter = 1;
                                $CurrentRowTDCounter = 1;
                              
                                foreach($AllClassSections as $ClassAndSectionID => $ClassAndSectionName)
                                {
                                    if ($CurrentClassAndSectionCounter == 1 || ($CurrentClassAndSectionCounter - 1) % 3 == 0)
                                    {
                                        echo '<div class="row">';
                                    }
                                    if (array_key_exists($ClassAndSectionID, $Clean['HolidayForClasses']))
                                    {
?>
                                        
                                        <div class="col-md-3"><label class="checkbox-inline" style="font-size:14px; font-weight:400;"><input style="vertical-align:top;" type="checkbox" class="ClassAndSectionList" checked="checked" id="ClassSection<?php echo $ClassAndSectionID;?>" name="chkHolidayForClasses[<?php echo $ClassAndSectionID;?>]" value="1"  /><?php echo $ClassAndSectionName;?></label></div>
<?php
                                    }
                                    else
                                    {
?>
                                        <div class="col-md-3"><label class="checkbox-inline" style="font-size:14px; font-weight:400;"><input style="vertical-align:top;" type="checkbox" class="ClassAndSectionList" id="ClassSection<?php echo $ClassAndSectionName?>" name="chkHolidayForClasses[<?php echo $ClassAndSectionID?>]" value="1" /><?php echo $ClassAndSectionName?></label></div>
<?php
                                    }
                                    
                                    $CurrentRowTDCounter++;
                                    
                                    if ($CurrentClassAndSectionCounter % 3 == 0)
                                    {
                                        $CurrentRowTDCounter = 1;
                                        echo '</div>';
                                    }
                                    
                                    $CurrentClassAndSectionCounter++;                                                                       
                                }
                            }
                            if ($CurrentRowTDCounter > 1)
                            {
                                for ($i = 1; $i <= (4 - $CurrentRowTDCounter); $i++)
                                { 
                                    echo '<div class="col-md-3"></div>';
                                }
                                
                                echo '</div><br>';
                            }
                        }
?>  	
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="Notification" class="col-lg-2 control-label">Notification</label>
                            <div class="col-lg-8">
                                <label style="font-weight: normal;">
                                    <input type="radio" class="txtNotification"  id="Notification" name="rdbNotification" <?php echo ($Clean['ToNotify'] == 1 ? 'checked="checked"' : ''); ?> value="1" />&nbsp;Yes&nbsp;
                                </label>
                                <label style="font-weight: normal;">
                                    <input type="radio" class="txtNotification" id="Notification" name="rdbNotification" <?php echo ($Clean['ToNotify'] == 0 ? 'checked="checked"' : ''); ?> value="0" />&nbsp;No
                                </label>
                            </div>
                        </div>
                        <div class="form-group" id="NotificationMessage" style="<?php echo ($Clean['ToNotify'] ? 'display: block;' : 'display: none;'); ?>">
                        	 <label for="NotificationForUser" class="col-lg-2 control-label">Notificaton Message</label>
                            <div class="col-lg-8">
                        		<textarea class="form-control" rows="5" cols="80" name="txtMessage" id="txtMessage"><?php echo $Clean['NotificationMessage'];?></textarea>	
                            </div>	
                        </div>
                        <div class="form-group" id="NotificationDate" style="<?php echo ($Clean['ToNotify'] ? 'display: block;' : 'display: none;'); ?>">
                        	 <label for="NotificatonDate" class="col-lg-2 control-label">Notificaton Date</label>
                        	<div class="col-lg-8">
                            <input class="form-control dtepicker" type="text" id="txtNotificatonDate" name="txtNotificatonDate" value="<?php echo $Clean['NotificationDate'];?>"/>
                        	</div>
                        </div>
                        <div class="form-group" id="NotificationDiv" style="<?php echo ($Clean['ToNotify'] ? 'display: block;' : 'display: none;'); ?>">
                          <label for="NotificationForUser" class="col-lg-2 control-label">Notification For Users</label>
                          <div class="col-lg-8">
<?php
                            foreach ($AllUsers as $NotificationForUserType => $NotificationForDetails)
                            {
                              echo '<label style="font-weight:normal;">
                                    <input type="checkbox" id="' . $NotificationForDetails['IDNameNotification'] . '" ' . (in_array($NotificationForUserType, $Clean['NotificationForUsers']) ? 'checked="checked"' : '') . ' id="' . $NotificationForUserType . '" name="chkNotificationForUsers[]" value="' . $NotificationForUserType . '"/>&nbsp;' . $NotificationForDetails['LableName'] . '</label>&nbsp;&nbsp;';
                            }
?>
                          </div> 
                        </div>
                        <div class="form-group" id="classes" style="<?php echo(in_array('Students', $Clean['NotificationForUsers']) ? 'display: block;' : 'display: none;')?>">
                            <label for="NotificationForUser" class="col-lg-2 control-label">Notification For Classes</label>
                            <div class="col-lg-8">
<?php
                            if (count($AllClassSections) > 0)
                            {
?>
                                <div class="row">
                                    <div class="col-md-3" style="border: 1px solid #ccc;">
                                        <label><input type="checkbox" id="NotificationSelectAll" name="chkNotificationSelectAll" value="1" <?php echo(count($AllClassSections) == count($Clean['HolidayForClasses'])) ? 'checked="checked"' : '0';?> />Select All</label>
                                    </div>
                                    <div class="col-md-3" style="border: 1px solid #ccc;">
                                        <label><input type="checkbox" id="NotificationClearAll" name="chkNotificationClearAll" value="1"  />Clear All</label>
                                    </div>
                                </div>
<?php
                            $TotalClassAndSections = count($AllClassSections);

                            if ($TotalClassAndSections > 0)
                            {
                                $CurrentClassAndSectionCounter = 1;
                                $CurrentRowTDCounter = 1;
                              
                                foreach($AllClassSections as $ClassAndSectionID => $ClassAndSectionName)
                                {
                                    if ($CurrentClassAndSectionCounter == 1 || ($CurrentClassAndSectionCounter - 1) % 3 == 0)
                                    {
                                        echo '<div class="row">';
                                    }
                                    if (array_key_exists($ClassAndSectionID, $Clean['NotificationForClasses']))
                                    {
?>
                                        
                                        <div class="col-md-3"><label class="checkbox-inline" style="font-size:14px; font-weight:400;"><input style="vertical-align:top;" type="checkbox" class="NotificationClassAndSectionList" checked="checked" id="ClassSection<?php echo $ClassAndSectionID;?>" name="chkNotificationForClasses[<?php echo $ClassAndSectionID;?>]" value="1"  /><?php echo $ClassAndSectionName;?></label></div>
<?php
                                    }
                                    else
                                    {
?>
                                        <div class="col-md-3"><label class="checkbox-inline" style="font-size:14px; font-weight:400;"><input style="vertical-align:top;" type="checkbox" class="NotificationClassAndSectionList" id="ClassSection<?php echo $ClassAndSectionName?>" name="chkNotificationForClasses[<?php echo $ClassAndSectionID?>]" value="1" /><?php echo $ClassAndSectionName?></label></div>
<?php
                                    }
                                    
                                    $CurrentRowTDCounter++;
                                    
                                    if ($CurrentClassAndSectionCounter % 3 == 0)
                                    {
                                        $CurrentRowTDCounter = 1;
                                        echo '</div>';
                                    }
                                    
                                    $CurrentClassAndSectionCounter++;                                                                       
                                }
                            }
                            if ($CurrentRowTDCounter > 1)
                            {
                                for ($i = 1; $i <= (4 - $CurrentRowTDCounter); $i++)
                                { 
                                    echo '<div class="col-md-3"></div>';
                                }
                                
                                echo '</div><br>';
                            }
                        }
?>   	
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="col-sm-offset-2 col-lg-10">
                            	<input type="hidden" name="hdnProcess" value="3"/>
                                <input type="hidden" name="hdnAcademicCalendarID" value="<?php echo $Clean['AcademicCalendarID']; ?>" />
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
<script src="/admin/vendor/jquery-ui/jquery-ui.min.js"></script>
<script type="text/javascript">
    var ViewOnly = 0;

<?php
    if (isset($_GET['ViewOnly'])) 
    {
?>
        ViewOnly = 1;
<?php
    }
?>
$(document).ready(function() 
{
    $(".dtepicker").datepicker({
            changeMonth: true,
            changeYear: true,
            dateFormat: 'dd/mm/yy'
    });

    console.log(ViewOnly);
    if (ViewOnly)
    {
        $('input, select, textarea').prop('disabled', true);
        $('button[type="submit"]').text('Close').attr('onClick', 'window.close();');
    }

    $('#HolidaySelectAll').click(function()
    {
        if ($('#HolidaySelectAll').prop("checked"))
        {
            $('#HolidayClearAll').prop("checked", "");
            $('.ClassAndSectionList').prop("checked", "checked");
        }
        else
        {
            $('.ClassAndSectionList').prop("checked", false);
        }
    });

    $('#HolidayClearAll').click(function()
    {
        if ($('#HolidayClearAll').prop("checked"))
        {
            $('#HolidaySelectAll').prop("checked", "");
            $('.HolidayClassAndSectionList').prop("checked", "");
        }
    });

    $('#NotificationSelectAll').click(function()
    {
        if ($('#NotificationSelectAll').prop("checked"))
        {
            $('#NotificationClearAll').prop("checked", "");
            $('.NotificationClassAndSectionList').prop("checked", "checked");
        }
        else
        {
            $('.NotificationClassAndSectionList').prop("checked", false);
        }
    });

    $('#NotificationClearAll').click(function()
    {
        if ($('#NotificationClearAll').prop("checked"))
        {
            $('#NotificationSelectAll').prop("checked", "");
            $('.NotificationClassAndSectionList').prop("checked", "");
        }
    });

    $(".txtHoliday").click(function()
    {
       	if ($(this).val() == 1)
       	{
       	 	$("#HolidayDiv").show();
		}
		else
		{
		 	$(".HolidayCheckClasses").prop('checked', false);	
		 	$("#HolidayAll").prop('checked', false);	
		 	$("#HolidayForStudent").prop('checked', false);
		 	$("#HolidayTeachingStaff").prop('checked', false);
		 	$("#HolidayNonTeachingStaff").prop('checked', false);
		 	$("#HolidayDiv").hide();
		 	$("#HolidayclassesDiv").hide();
		 	
		}
    });

  $('#HolidayForStudent').click(function()
    {
		  if ($(this).is(':checked'))
        {
           // Do something...
           $("#HolidayclassesDiv").show();
        }
        else
        {
        	$(".HolidayCheckClasses").attr('checked', false);	
  		 	$("#HolidayAll").prop('checked', false);
        	$("#HolidayclassesDiv").hide();
        }
    });

    $('#HolidayAll').click(function()
	{
		if ($(this).is(':checked'))
        {
           $(".HolidayCheckClasses").attr('checked', true);
        }
        else
        {
        	$(".HolidayCheckClasses").attr('checked', false);
        }
	});

    $(".txtNotification").click(function()
     {
     	if ($(this).val() == 1)
     	{
     	 	$("#NotificationDiv").show();
     	 	$("#NotificationMessage").show();
     	 	$("#NotificationDate").show();
  		}
  		else
  		{
  		 	$("#CheckClasses").attr('checked', false);
  		 	$(".CheckClasses").attr('checked', false);	
  		 	$("#NotificationAll").prop('checked', false);	
  		 	$("#Students").prop('checked', false);
  		 	$("#TeachingStaff").prop('checked', false);
  		 	$("#NonTeachingStaff").prop('checked', false);
  		 	$("#txtMessage").val('');
  		 	$("#txtNotificatonDate").val('');
  		 	$("#NotificationDiv").hide();
  		 	$("#classes").hide();
  		 	$("#NotificationMessage").hide();
     	 	$("#NotificationDate").hide();
  		 	
  		}
     });

    $('#Students').click(function()
    {
		if ($(this).is(':checked'))
        {
           $("#classes").show();
           
        }
        else
        {
        	$("#classes").hide();
        }
    });

    $('#NotificationAll').click(function()
	{
		if ($(this).is(':checked'))
        {
           $(".CheckClasses").attr('checked', true);
        }
        else
        {
        	$(".CheckClasses").attr('checked', false);
        }
	});
});
</script>
</body>
</html>
<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once("../../classes/school_administration/class.academic_calendar.php");
require_once("../../classes/school_administration/class.classes.php");

require_once("../../classes/class.date_processing.php");

require_once("../../includes/helpers.inc.php");

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
	header('location:unauthorized_login_admin.php');
	exit;
}
catch (Exception $e)
{
	header('location:unauthorized_login_admin.php');
	exit;
}
// END OF 1. //

if ($LoggedUser->HasPermissionForTask(TASK_LIST_ACADEMIC_CALENDER_EVENT) !== true)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}

$HasErrors = false;

$LandingPage = '';

$Clean = array();
$Clean['Process'] = 0;

$Clean['AcademicCalendarID'] = 0;

if (isset($_GET['Process']))
{
	$Clean['Process'] = (int) $_GET['Process'];
}
if (isset($_GET['Mode']))
{
    $LandingPage = (int) $_GET['Mode'];
}

switch ($Clean['Process'])
{
	case 5:
		if ($LoggedUser->HasPermissionForTask(TASK_DELETE_ACADEMIC_CALENDER_EVENT) !== true)
		{
			header('location:unauthorized_login_admin.php');
			exit;
		}
		
		if (isset($_GET['AcademicCalendarID']))
		{
			$Clean['AcademicCalendarID'] = (int) $_GET['AcademicCalendarID'];			
		}
		
		if ($Clean['AcademicCalendarID'] <= 0)
		{
			header('location:../error_page.php');
			exit;
		}						

		try
		{
			$AcademicCalendarDelete = new AcademicCalendar($Clean['AcademicCalendarID']);
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
		
		$RecordValidator = new Validator();
				
		if (!$AcademicCalendarDelete->Remove())
		{
			$RecordValidator->AttachTextError(ProcessErrors($AcademicCalendarDelete->GetLastErrorCode()));
			$HasErrors = true;
			break;
		}
		
		header('location:academic_calendar_list.php?Mode=DD');
	break;
}

$AllAcademicCalendar = array();
$AllAcademicCalendar = AcademicCalendar::GetAllEvents();

require_once('../html_header.php');
?>
<title>All Academic Calender Events</title>
<link href='../calender_required_libraries_files/fullcalendar.min.css' rel='stylesheet' />
<link href='../calender_required_libraries_files/fullcalendar.print.min.css' rel='stylesheet' media='print' />
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
                    <h1 class="page-header">All Academic Calender Events</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
            <div class="row">
                <div class="col-lg-12">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <strong>Academic Calender</strong>
                        </div>
                        <!-- /.panel-heading -->
                        <div class="panel-body">
                            <div id='Calendar'></div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /.row -->
            <div class="row">
                <div class="col-lg-12">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <strong>Total Records Returned: <?php echo count($AllAcademicCalendar); ?></strong>
                        </div>
                        <!-- /.panel-heading -->
                        <div class="panel-body">
                            <div>
                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="add-new-btn-container"><a href="add_academic_calendar.php" class="btn btn-primary<?php echo $LoggedUser->HasPermissionForTask(TASK_ADD_ACADEMIC_CALENDER_EVENT) === true ? '' : ' disabled'; ?>" role="button">Add New Event</a></div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="print-btn-container"><button id="PrintButton" type="submit" class="btn btn-primary">Print</button></div>
                                    </div>
                                </div>
<?php
                            if ($HasErrors == true)
                            {
                                echo $RecordValidator->DisplayErrorsInTable();
                            }

?>
                                <div class="row" id="RecordTableHeading">
                                    <div class="col-lg-12">
                                    	<div class="report-heading-container"><strong>All Academic Calender Events on <?php echo date('d-m-Y h:i A'); ?></strong></div>
                                    </div>
								</div>
                                <div class="row" id="RecordTable">
                                    <div class="col-lg-12">
                                        <table width="100%" class="table table-striped table-bordered table-hover">
                                            <thead>
                                                <tr>
                                                    <th>S. No</th>
                                                    <th>Event Start Date</th>
                                                    <th>Event End Date</th>
                                                    <th>Event Name</th>
                                                    <th>IsHoliday</th>
                                                    <th>ToNotify</th>
                                                    <th>Create User Name</th>
                                                    <th>Create Date</th>
                                                    <th class="print-hidden">Operations</th>
                                                </tr>
                                            </thead>
                                            <tbody>
    <?php
                                    if (is_array($AllAcademicCalendar) && count($AllAcademicCalendar) > 0)
                                    {
                                        $Counter = 0;
                                        foreach ($AllAcademicCalendar as $AcademicCalendarID => $AllAcademicCalendarDetails)
                                        {
    ?>
                                            <tr>
                                                <td><?php echo ++$Counter; ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($AllAcademicCalendarDetails['EventStartDate'])); ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($AllAcademicCalendarDetails['EventEndDate'])); ?></td>
                                                <td><?php echo $AllAcademicCalendarDetails['EventName']; ?></td>
                                                <td><?php echo (($AllAcademicCalendarDetails['IsHoliday']) ? 'Yes' : 'No'); ?></td>
                                                <td><?php echo (($AllAcademicCalendarDetails['ToNotify']) ? 'Yes' : 'No'); ?></td>
                                                <td><?php echo $AllAcademicCalendarDetails['CreateUserName']; ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($AllAcademicCalendarDetails['CreateDate'])); ?></td>

                                                <td class="print-hidden">
<?php
                                                echo '<a href="edit_academic_calendar.php?Process=2&amp;AcademicCalendarID='.$AcademicCalendarID.'&amp;ViewOnly=1" target="_blank">View</a>';
                                                
                                                echo '&nbsp;|&nbsp;';

                                                if ($LoggedUser->HasPermissionForTask(TASK_EDIT_ACADEMIC_CALENDER_EVENT) === true)
                                                {
                                                    echo '<a href="edit_academic_calendar.php?Process=2&amp;AcademicCalendarID='.$AcademicCalendarID.'">Edit</a>';
                                                }
                                                else
                                                {
                                                    echo 'Edit';
                                                }

                                                echo '&nbsp;|&nbsp;';

                                                if ($LoggedUser->HasPermissionForTask(TASK_DELETE_ACADEMIC_CALENDER_EVENT) === true)
                                                {
                                                    echo '<a class="delete-record" href="academic_calendar_list.php?Process=5&amp;AcademicCalendarID='.$AcademicCalendarID.'">Delete</a>';
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
        </div>
        <!-- /#page-wrapper -->

    </div>
    <!-- /#wrapper -->
<?php
require_once('../footer.php');
if (PrintMessage($_GET, $Message))
{
?>
    <script type="text/javascript">
        alert('<?php echo $Message; ?>');
    </script>
<?php
}
?>

<script src='../calender_required_libraries_files/lib/moment.min.js'></script>
<script src='../calender_required_libraries_files/lib/jquery.min.js'></script>
<script src='../calender_required_libraries_files/fullcalendar.min.js'></script>
<script type="text/javascript">
$(document).ready(function() 
{
	$(".delete-task").click(function()
    {	
        if (!confirm("Are you sure you want to delete this Academic Calendar Event?"))
        {
            return false;
        }
    });

     $('#Calendar').fullCalendar({
      header: {
        left: 'prev,next today',
        center: 'title',
        right: ''
      },
      defaultDate: '<?php echo date("Y-m-d");?>',
      navLinks: true, // can click day/week names to navigate views
      editable: false,
      eventLimit: true, // allow "more" link when too many events
      events: [

<?php

        foreach ($AllAcademicCalendar as $AcademicCalendarID => $AllAcademicCalendarDetails) 
        {
            $EventEndDate = date('Y-m-d', strtotime($AllAcademicCalendarDetails['EventEndDate'] . ' +1 day'));
?>
        {
            title: "<?php echo $AllAcademicCalendarDetails['EventName'];?>",
            start: "<?php echo $AllAcademicCalendarDetails['EventStartDate'];?>",
            end: "<?php echo $EventEndDate;?>",
            color:"<?php echo (($AllAcademicCalendarDetails['IsHoliday']) ? 'Red' : 'Green'); ?>",
        },
<?php
        }
?>
    ]
    });
});
</script>
<!-- JavaScript To Print A Report -->
<script src="../js/print-report.js"></script>
</body>
</html>
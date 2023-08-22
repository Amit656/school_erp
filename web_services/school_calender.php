<?php
header ("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");

require_once("../classes/school_administration/class.academic_calendar.php");
require_once("../classes/school_administration/class.classes.php");

require_once("../classes/class.date_processing.php");

require_once("../includes/helpers.inc.php");

$AllAcademicCalendar = array();
$AllAcademicCalendar = AcademicCalendar::GetAllEvents();

$EventDetails = array();

$Counter = 0;
foreach ($AllAcademicCalendar as $Details)
{
    $EventEndDate = date('Y-m-d', strtotime($Details['EventEndDate'] . ' +1 day'));
    
    $EventDetails[$Counter]['title'] = $Details['EventName'];
    $EventDetails[$Counter]['start'] = $Details['EventStartDate'];
    $EventDetails[$Counter]['end'] = $EventEndDate;
    $EventDetails[$Counter]['color'] = $Details['IsHoliday'] ? 'Red' : 'Green';
    
    $Counter++;
}

echo json_encode($EventDetails) . '|';

?>                              
<div class="panel-body">
    <div id='Calendar'></div>
</div>
<table width="100%" class="table table-striped table-bordered table-hover" id="DataTableRecords">
    <thead>
        <tr>
            <th>S. No</th>
            <th>Event Start Date</th>
            <th>Event End Date</th>
            <th>Event Name</th>
            <th>IsHoliday</th>
            <th>ToNotify</th>
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
        </tr>
<?php
}
}
?>
    </tbody>
        </table>
        
<script src='http://lucknowips.addedschools.com/admin/calender_required_libraries_files/lib/moment.min.js'></script>
<script src='http://lucknowips.addedschools.com/admin/calender_required_libraries_files/lib/jquery.min.js'></script>
<script src='http://lucknowips.addedschools.com/admin/calender_required_libraries_files/fullcalendar.min.js'></script>
<script type="text/javascript">
$(document).ready(function() 
{
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
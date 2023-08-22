<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");
require_once("../../classes/class.date_processing.php");

require_once("../../classes/academic_supervision/class.topic_schedules.php");

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

if ($LoggedUser->HasPermissionForTask(TASK_UPDATE_SCHEDULED_LESSON_PLAN) !== true)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}

$Clean = array();

$Clean['TopicScheduleID'] = 0;

if (isset($_GET['TopicScheduleID']))
{
    $Clean['TopicScheduleID'] = (int) $_GET['TopicScheduleID'];
}
else if (isset($_POST['hdnTopicScheduleID']))
{
    $Clean['TopicScheduleID'] = (int) $_POST['hdnTopicScheduleID'];
}

if ($Clean['TopicScheduleID'] <= 0)
{
    header('location:../error.php');
    exit;
}   

try
{
    $TopicScheduleObject = new TopicSchedule($Clean['TopicScheduleID']);
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

$StatusList = array('Pending' => 'Pending', 'InProgress' => 'InProgress', 'Completed' => 'Completed');

$ScheduledTopicsList = array();

$HasErrors = false;
$SearchHasErrors = false;

$Clean['Process'] = 0;

$Clean['Status'] = 'Pending';
$Clean['Remark'] = '';
$Clean['EndDate'] = '';

$TopicScheduleDetails = array();
$Errors = array();

// paging and sorting variables start here  //
$Clean['CurrentPage'] = 1;
$TotalPages = 0;

$Start = 0;
$Limit = 3;
// end of paging variables//

$Filters = array();

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
	case 7:
		$TopicScheduleObject->FillTopicScheduleDetails();
		$ScheduledTopicsList = $TopicScheduleObject->GetTopicScheduleDetails();

	break;
}

$LandingPageMode = '';
if (isset($_GET['Mode']))
{
    $LandingPageMode = $_GET['Mode'];
}

require_once('../html_header.php');
?>
<title>Update Topic Schedule</title>
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
                    <h1 class="page-header">Update Topic Schedule</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
        	<div class="panel panel-default">
                <div class="panel-heading">
                    <strong>Topic Schedule Details</strong>
                </div>
                <div class="panel-body">
<?php
					if ($SearchHasErrors == true)
					{
						echo $SearchValidator->DisplayErrors();
					}
					else if ($LandingPageMode == 'AS')
                    {
                        echo '<div class="alert alert-success">Record saved successfully.</div>';
                    }

		if (count($ScheduledTopicsList) > 0) 
		{
?>
					<form class="form-horizontal" name="AddTopicSchedule" id="TopicScheduleForm" action="update_topic_schedules.php" method="post">
		            	<div class="panel-group">
		            	<div id="ChapterDetailsContainer">
<?php
						if ($HasErrors == true)
						{
							echo $SearchValidator->DisplayErrors();
							echo $RecordValidator->DisplayErrors();
						}
?>  
<?php 					
						$Counter = 0;
						foreach ($ScheduledTopicsList as $ChapterName => $ScheduleDetails) 
						{
							$Counter++;
?>	
							<div class="panel panel-default ChapterDetailsPanel">
			                	<div class="panel-heading">
			                        <strong>Chapter: <?php echo $ChapterName; ?></strong>
			                        <!-- <strong class="pull-right"><a data-toggle="collapse" data-parent="#ChapterDetailsContainer" href="#<?php echo $Counter ;?>"><i class="fa fa-minus"></i></a></strong> -->
			                    </div>
			                    <div id="<?php echo $Counter;?>" class="panel-collapse collapse in">
				                	<div class="panel-body">
				                        <div class="row" id="RecordTable">
				                            <div class="col-lg-12">
				                                <table width="100%" class="table table-striped table-bordered table-hover">
				                                    <thead>
				                                        <tr>
				                                            <th>S. No.</th>
				                                            <th>Topic Name</th>                                        
				                                            <th>Expected Classes</th>
				                                            <th>Start Date</th>
				                                            <th>Status</th>
				                                            <th>Operation</th>
				                                        </tr>
				                                    </thead>
				                                    <tbody>
<?php
				                                if (is_array($ScheduleDetails) && count($ScheduleDetails) > 0)
				                                {
				                                    $Count = 0;
				                                    
				                                    foreach ($ScheduleDetails as $TopicScheduleDetailID => $TopicScheduleDetail)
				                                    {
?>
				                                        <tr>
				                                            <td><?php echo ++$Count; ?></td>
				                                            <td><?php echo $TopicScheduleDetail['TopicName']; ?></td>
				                                            <td><?php echo $TopicScheduleDetail['ExpectedClasses']; ?></td>
				                                            <td><?php echo date('d/m/Y', strtotime($TopicScheduleDetail['StartDate'])); ?></td>
				                                            <td id="<?php echo $TopicScheduleDetailID; ?>"><?php echo $TopicScheduleDetail['Status']; ?>
				                                            </td>

				                                            <input class="form-control select-date" type="hidden" maxlength="10" id="EndDate<?php echo $TopicScheduleDetailID; ?>" name="hdnEndDate"  value="<?php echo (($TopicScheduleDetail['EndDate'] > 0) ? $TopicScheduleDetail['EndDate'] : ''); ?>"/>

				                                            <input type="hidden" name="hdnRemark" id="Remark<?php echo $TopicScheduleDetailID; ?>" value="<?php echo $TopicScheduleDetail['Remark']; ?>">

				                                            <td><button type="button" class="btn btn-info btn-sm" data-toggle="modal" data-target="#UpdateStatus" value="<?php echo $TopicScheduleDetailID; ?>">Update Status&nbsp;<i class="fa fa-angle-double-right"></i></button></td>
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
				                </div>
				            </div>                        				        
<?php
						}						
?>				
							</div>
						</div>

						<div id="UpdateStatus" class="modal fade" role="dialog">
							<div class="modal-dialog">
							<!-- Modal content-->
								<div class="modal-content">
									<div class="modal-header btn-info">
										<button type="button" class="close" data-dismiss="modal">&times;</button>
										<h4 class="modal-title">Topic Status Details</h4>
									</div>
									<div class="modal-body">
										<div class="row">
											<div class="col-lg-12">
												<div class="form-group">
													<input type="hidden" name="hdnTopicScheduleDetailID" id="TopicScheduleDetailID" value="">
						                            <div class="col-sm-offset-2 col-lg-10">
<?php
						                            foreach($StatusList as $StatusID => $Status)
						                            {
?>                              
						                                <label class="col-lg-4"><input class="custom-radio" type="radio" id="<?php echo $StatusID; ?>" name="optStatus" value="<?php echo $StatusID; ?>" <?php echo ($Clean['Status'] == $StatusID ? 'checked="checked"' : ''); ?> >&nbsp;&nbsp;<?php echo $Status; ?></label>            
<?php                                       
						                            }
?>
						                            </div>
						                        </div>
						                        <div class="form-group">
						                            <label for="EndDate" class="col-lg-2 control-label">End Date</label>
						                            <div class="col-lg-3">
						                                <input class="form-control select-date" type="text" maxlength="10" id="EndDate" name="txtEndDate"  value=""/>
						                            </div>
						                        </div> 
						                        <div class="form-group">
						                            <label for="Remark" class="col-lg-2 control-label">Remark</label>
						                            <div class="col-lg-9">
						                                <textarea class="form-control" maxlength="500" id="Remark" name="txtRemark" placeholder="If you want to give a note, type here......."><?php echo $Clean['Remark']; ?></textarea>
						                            </div>
						                        </div>  
											</div>
										</div>
									</div>
									<div class="modal-footer">
										<button type="button" class="btn btn-info" id="UpdateStatusButton"><i class="fa fa-upload"></i>&nbsp;Update</button>
										<button type="button" class="btn btn-info" data-dismiss="modal">Close</button>
									</div>
								</div>
							</div>
						</div>
	            	</form>
<?php					
		}
?>
	            </div>
	        </div>
        </div>
        <!-- /#page-wrapper -->

    </div>
    <!-- /#wrapper -->
<?php
require_once('../footer.php');
?>
<script src="../vendor/jquery-ui/jquery-ui.min.js"></script>
<script type="text/javascript">
$(document).ready(function(){

    $(".select-date").datepicker({
        changeMonth: true,
        changeYear: true,
        dateFormat: 'dd/mm/yy'
    });

    $('button[data-toggle=modal]').click(function(){
    	var TopicScheduleDetailID = 0;
    	var TopicStatus = '';
    	var Remark = '';
    	var EndDate = '';

    	TopicScheduleDetailID = $(this).val();
    	TopicStatus = $('#'+ TopicScheduleDetailID).text();
    	Remark = $('#Remark'+ TopicScheduleDetailID).val();

    	EndDate = $('#EndDate'+ TopicScheduleDetailID).datepicker('getDate');
    	var date = new Date(Date.parse(EndDate)); 
    	date.setDate(date.getDate());

    	$('#TopicScheduleDetailID').val(TopicScheduleDetailID);
    	$("input[name=optStatus][value=" + TopicStatus + "]").prop('checked', true);
    	$('#Remark').val(Remark);
    	$('#EndDate').datepicker('setDate', EndDate);
    });

    $('#UpdateStatusButton').click(function(){
    	var TopicScheduleDetailID = 0;
    	var TopicStatus = '';
    	var Remark = '';
    	var EndDate = '';

    	TopicScheduleDetailID = $('#TopicScheduleDetailID').val();
    	TopicStatus = $("input[name='optStatus']:checked").val();
    	Remark = $('#Remark').val();
    	EndDate = $('#EndDate').val();

    	if (TopicScheduleDetailID <= 0 || TopicStatus == '') 
    	{
    		alert('Unknown error, please try again.');
    		return false;
    	}

    	$.post("/xhttp_calls/update_topic_status.php", {SelectedTopicScheduleDetailID:TopicScheduleDetailID, SelectedTopicStatus:TopicStatus, Remark:Remark, EndDate:EndDate}, function(data)
        {
            ResultArray = data.split("|*****|");
            
            if (ResultArray[0] == 'error')
            {
                alert (ResultArray[1]);
                return false;
            }
            else
            {	
            	$('#'+ TopicScheduleDetailID).text(ResultArray[1]);
            	$('#EndDate'+ TopicScheduleDetailID).val(ResultArray[2]);
            	$('#Remark'+ TopicScheduleDetailID).val(ResultArray[3]);
            	$('#UpdateStatus').modal('hide');
            }
        });
    });
});

</script>
</body>
</html>
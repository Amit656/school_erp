<?php
require_once('../../classes/class.users.php');
require_once('../../classes/class.validation.php');
require_once('../../classes/class.authentication.php');

require_once('../../classes/class.sms_queue.php');
require_once('../../classes/class.ui_helpers.php');

require_once('../../classes/class.date_processing.php');

require_once('../../includes/global_defaults.inc.php');
require_once('../../includes/helpers.inc.php');

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

if ($LoggedUser->HasPermissionForTask(TASK_SMS_REPORT) !== true)
{
 	header('location:/admin/unauthorized_login_admin.php');
 	exit;
 }

$Filters = array();

$MessageStatusList = array('Not Sent' => 'Not Sent', 'Pending' => 'Pending', 'Delivered' => 'Delivered', 'Failed' => 'Failed');

$SMSList = array();

$SMSInQueue = array();
$SMSInQueue = SMSQueue::SMSInQueue();

$HasSearchErrors = false;

$Clean = array();

$Clean['CreateDate'] = '';
$Clean['CreateFromDate'] = '';
$Clean['CreateToDate'] = '';
$Clean['SMSMessage'] = '';
$Clean['PhoneNumber'] = '';
$Clean['SMSStatus'] = '';

// paging variables start here 	//
$Clean['AllRecords'] = '';
$Clean['CurrentPage'] = 1;
$TotalPages = 0;

$Start = 0;
$Limit = 100;
// end of paging variables		//

$Clean['Process'] = 0;

if (isset($_GET['hdnProcess']))
{
	$Clean['Process'] = (int) $_GET['hdnProcess'];
}
else if (isset($_GET['Process']))
{
	$Clean['Process'] = (int) $_GET['Process'];
}
switch ($Clean['Process'])
{		
	case 7:
		 if (isset($_GET['txtCreateDate']))
        {
            $Clean['CreateDate'] = strip_tags(trim($_GET['txtCreateDate']));
        }
        elseif (isset($_GET['CreateDate']))
        {
            $Clean['CreateDate'] = strip_tags(trim($_GET['CreateDate']));
        }

        if (isset($_GET['txtCreateFromDate']))
        {
            $Clean['CreateFromDate'] = strip_tags(trim($_GET['txtCreateFromDate']));
        }
        elseif (isset($_GET['CreateFromDate']))
        {
            $Clean['CreateFromDate'] = strip_tags(trim($_GET['CreateFromDate']));
        }

        if (isset($_GET['txtCreateToDate']))
        {
            $Clean['CreateToDate'] = strip_tags(trim($_GET['txtCreateToDate']));
        }
        elseif (isset($_GET['CreateToDate']))
        {
            $Clean['CreateToDate'] = strip_tags(trim($_GET['CreateToDate']));
        }

        if (isset($_GET['txtSMSMessage']))
		{
			$Clean['SMSMessage'] =  $_GET['txtSMSMessage'];
		}
		elseif (isset($_GET['SMSMessage'])) 
		{
			$Clean['SMSMessage'] = $_GET['SMSMessage'];
		}

        if (isset($_GET['txtPhoneNumber']))
        {
            $Clean['PhoneNumber'] =  $_GET['txtPhoneNumber'];
        }
        elseif (isset($_GET['PhoneNumber'])) 
        {
            $Clean['PhoneNumber'] = $_GET['PhoneNumber'];
        }

		if (isset($_GET['chkSMSStatus'])) 
		{
			$Clean['SMSStatus'] = (string) $_GET['chkSMSStatus'];
		}
		elseif (isset($_GET['SMSStatus'])) 
		{
			$Clean['SMSStatus'] = (string) $_GET['SMSStatus'];
		}

		$SearchValidator = new Validator();

		if ($Clean['CreateDate'] != '')
        {
            $SearchValidator->ValidateDate($Clean['CreateDate'], 'Please enter valid sent date.');
        }

        if ($Clean['CreateFromDate'] != '')
        {
            $SearchValidator->ValidateDate($Clean['CreateFromDate'], 'Please enter valid transaction from date.');
            $SearchValidator->ValidateDate($Clean['CreateToDate'], 'Please enter valid transaction to date.');
        }

		if ($Clean['SMSMessage'] != '') 
		{
			$SearchValidator->ValidateStrings($Clean['SMSMessage'], 'Message content is should be upto 500 characters.', 1, 500);
		}
        if ($Clean['PhoneNumber'] != '') 
        {
            $SearchValidator->ValidateStrings($Clean['PhoneNumber'], 'PhoneNumber is required and should be between 4 and 15 characters.', 4, 15);
        }

		if ($Clean['SMSStatus'] != '') 
		{
			$SearchValidator->ValidateInSelect($Clean['SMSStatus'], $MessageStatusList, 'Unknown error, please try again.');
		}
		
		if ($SearchValidator->HasNotifications())
		{
			$HasSearchErrors = true;
			break;
		}	
		//set record filters

		 if ($Clean['CreateDate'] != '') 
        {
            $Filters['CreateDate'] = date('Y-m-d', strtotime(DateProcessing::ToggleDateDayAndMonth(($Clean['CreateDate']))));
        }    
        
        if ($Clean['CreateFromDate'] != '') 
        {
            $Filters['CreateFromDate'] = date('Y-m-d', strtotime(DateProcessing::ToggleDateDayAndMonth(($Clean['CreateFromDate']))));
        }
        
        if ($Clean['CreateToDate'] != '') 
        {
            $Filters['CreateToDate'] = date('Y-m-d', strtotime(DateProcessing::ToggleDateDayAndMonth(($Clean['CreateToDate']))));
        }

        if ($Clean['SMSMessage'] != '') 
        {
            $Filters['SMSMessage'] = $Clean['SMSMessage'];
        }

        if ($Clean['PhoneNumber'] != '') 
        {
            $Filters['PhoneNumber'] = $Clean['PhoneNumber'];
        }

        if ($Clean['SMSStatus'] != '') 
        {
            $Filters['SMSStatus'] = $Clean['SMSStatus'];
        }

		SMSQueue::GetSMSReport($TotalRecords, $TotalSMSUsed, true, $Filters);
		
		// Paging and sorting calculations start here.
		if ($TotalRecords > 0)
		{
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
			
			// now get the actual records			
			if ($Clean['AllRecords'] == 'All') 
			{
				$SMSList = SMSQueue::GetSMSReport($TotalRecords, $TotalSMSUsed, false, $Filters, 0, $TotalRecords);
			}
			else
			{
				$SMSList = SMSQueue::GetSMSReport($TotalRecords, $TotalSMSUsed, false, $Filters, $Start, $Limit);
			}
		}
	break;
}

require_once('../html_header.php');
?>
<title>Sent SMS Report</title>
<!-- DataTables CSS -->
<link href="/admin/vendor/datatables-plugins/dataTables.bootstrap.css" rel="stylesheet">
	
<!-- DataTables Responsive CSS -->
<link href="/admin/vendor/datatables-responsive/dataTables.responsive.css" rel="stylesheet">

<link href="/admin/vendor/jquery-ui/jquery-ui.min.css" rel="stylesheet">
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
                    <h1 class="page-header">Sent SMS Report</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
            <form class="form-horizontal" name="frmUserReport" action="sent_sms_report.php" method="get">
                <div class="panel panel-default" id="accordion">
                    <div class="panel-heading" style="height: 50px; vertical-align: middle;">
                        <strong>Filters</strong>
                        <button type="button" class="btn btn-primary btn-sm pull-right" style="margin-left: 5px;">Total Length of SMS: <span class="badge badge-light"><?php echo ($SMSInQueue['TotalSMSLength'] == null ? 0 : $SMSInQueue['TotalSMSLength']); ?></span></button>
                        <button type="button" class="btn btn-primary btn-sm pull-right">SMS in Queue: <span class="badge badge-light"><?php echo $SMSInQueue['TotalSMS']; ?></span></button>						
                    </div>
                    <div id="collapseOne" class="panel-collapse collapse in">
                        <div class="panel-body">
<?php
                            if ($HasSearchErrors == true)
                            {
                                echo $SearchValidator->DisplayErrorsInTable();
                            }
?>
                           
                             <div class="form-group">                            
                                <label for="CreateDate" class="col-lg-2 control-label">Sent Date</label>
                                <div class="col-lg-4">
                                    <input class="form-control select-date" type="text" maxlength="10" id="CreateDate" name="txtCreateDate" value="<?php echo $Clean['CreateDate']; ?>" />
                                </div>
                            </div>
                            <div class="form-group">                            
                                <label for="CreateFromDate" class="col-lg-2 control-label">Sent Date Between</label>
                                <div class="col-lg-4">
                                    <input class="form-control select-date" type="text" maxlength="10" id="CreateFromDate" name="txtCreateFromDate" value="<?php echo $Clean['CreateFromDate']; ?>" />
                                </div>
                                <label for="CreateToDate" class="col-lg-2 control-label">To</label>
                                <div class="col-lg-4">
                                    <input class="form-control select-date" type="text" maxlength="10" id="CreateToDate" name="txtCreateToDate" value="<?php echo $Clean['CreateToDate']; ?>" />
                                </div>
                            </div>
                              <div class="form-group">
                                <label for="SMSMessage" class="col-lg-2 control-label">Message</label>
                                <div class="col-lg-4">
                                    <input class="form-control" type="text" maxlength="500" id="SMSMessage" name="txtSMSMessage" value="<?php echo $Clean['SMSMessage']; ?>" />
                                </div>
                                <label for="PhoneNumber" class="col-lg-2 control-label">Phone Number</label>
                                <div class="col-lg-4">
                                    <input class="form-control" type="text" maxlength="10" id="PhoneNumber" name="txtPhoneNumber" value="<?php echo $Clean['PhoneNumber']; ?>" />
                                </div>
                            </div>
                            <div class="form-group">
                            	  <label for="SMSStatus" class="col-lg-2 control-label">Status</label>
		                            <div class="col-lg-8">
		                                <label class="radio-inline">
                                            <input type="radio" <?php echo (($Clean['SMSStatus'] == '') ? 'checked="checked"' : ''); ?> name="chkSMSStatus" id="SMSStatus" value="" checked>All Records
                                        </label>
<?php
									foreach ($MessageStatusList as $IsMessageSentID => $IsMessageSentName) 
									{
?>
                                        <label class="radio-inline">
                                            <input type="radio" <?php echo (($Clean['SMSStatus'] == $IsMessageSentID) ? 'checked="checked"' : ''); ?> name="chkSMSStatus" class="SMSStatus" value="<?php echo $IsMessageSentID; ?>"><?php echo $IsMessageSentName; ?>
                                        </label>
<?php										
									}
?>	
		                            </div> 
                            </div>
                            <div class="form-group">
                                <div class="col-lg-offset-2 col-lg-10">
                                    <input type="hidden" name="hdnProcess" value="7" />
                                    <button type="submit" class="btn btn-primary">Search</button>
                                </div>
                            </div>
                        </div>
                    </div>
				</div>
            </form>
            <!-- /.row -->
<?php
		if ($Clean['Process'] == 7 && $HasSearchErrors == false)
		{
?>
            <div class="row">
                <div class="col-lg-12">
                    <div class="panel panel-default"> 
 						<div class="panel-heading">
                            <strong>Total Records Returned: <?php echo $TotalRecords; ?></strong>
                            <strong class="pull-right">Total SMS Consumed: <?php echo $TotalSMSUsed; ?></strong>
                        </div>
                        <!-- /.panel-heading -->
                        <div class="panel-body">
                            <div>
                            <div class="row">
								<div class="col-lg-6">
<?php
								if ($TotalPages > 1)
								{
									$AllParameters = array('Process' => '7', 'CreateDate' => $Clean['CreateDate'], 'CreateFromDate' => $Clean['CreateFromDate'], 'CreateToDate' => $Clean['CreateToDate'], 'SMSMessage' => $Clean['SMSMessage'], 'PhoneNumber' => $Clean['PhoneNumber'], 'SMSStatus' => $Clean['SMSStatus']);
									echo UIHelpers::GetPager('sent_sms_report.php', $TotalPages, $Clean['CurrentPage'], $AllParameters);
								}
?>                                        
								</div>
								<div class="col-lg-6">
									<div class="print-btn-container"><button id="PrintButton" type="submit" class="btn btn-primary">Print</button></div>
								</div>
							</div>
                                <div class="row" id="RecordTableHeading">
                                    <div class="col-lg-12">
                                    	<div class="report-heading-container"><strong>Sent SMS List on <?php echo date('d-m-Y h:i A'); ?></strong></div>
                                    </div>
								</div>
                                <div class="row" id="RecordTable">
                                    <div class="col-lg-12">
                                        <table width="100%" class="table table-striped table-bordered table-hover" id="DataTableRecords">
                                            <thead>
                                                <tr>
                                                    <th>Sr. No.</th>
													<th>Phone Number</th>
													<th>Message</th>
                                                    <th>Status</th>
                                                    <th>SMS Used</th>
													<th>Sent Date Time</th>
													<th>Sent By</th>
                                                </tr>
                                            </thead>
                                            <tbody>
<?php
    										if (is_array($SMSList) && count($SMSList) > 0)
    										{
    											$Counter = $Start;

    											foreach ($SMSList as $SMSQueueID => $SMSDetails)
    											{

?>
												<tr>
													<td><?php echo ++$Counter; ?></td>
													<td><?php echo $SMSDetails['PhoneNumber']; ?></td>
													<td><?php echo $SMSDetails['SMSMessage']; ?></td>
													<td><?php echo $SMSDetails['SMSStatus']; ?></td>
                                                    <td><?php echo $SMSDetails['SMSUsed']; ?></td>
                                                    <td><?php echo date('d/m/Y h:i A', strtotime($SMSDetails['CreateDate'])); ?></td>
                                                    <td><?php echo $SMSDetails['CreateByUser']; ?></td>
												</tr>
<?php
                                                }
                                            }
?>											</tbody>
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
<script src="/admin/vendor/datatables/js/jquery.dataTables.min.js"></script>
<script src="/admin/vendor/datatables-plugins/dataTables.bootstrap.min.js"></script>
<script src="/admin/vendor/datatables-responsive/dataTables.responsive.js"></script>

<script src="/admin/vendor/jquery-ui/jquery-ui.min.js"></script>

<!-- JavaScript To Print A Report -->
<script src="/admin/js/print-report.js"></script>

<script type="text/javascript">

$(document).ready(function() 
{
   $(".select-date").datepicker({
        changeMonth: true,
        changeYear: true,
        dateFormat: 'dd/mm/yy'
    });
});
</script>
</body>
</html>
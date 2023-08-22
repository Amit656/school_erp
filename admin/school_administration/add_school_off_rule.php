<?php
require_once('../../classes/class.users.php');
require_once('../../classes/class.validation.php');
require_once('../../classes/class.authentication.php');

require_once("../../classes/school_administration/class.classes.php");
require_once('../../classes/school_administration/class.school_off_rules.php');

require_once('../../includes/global_defaults.inc.php');
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

if ($LoggedUser->HasPermissionForTask(TASK_ADD_SCHOOL_BASIC_DETAILS) !== true)
{
	header('location:../unauthorized_login_admin.php');
	exit;
}

$OffType = array(0 => 'Second Saturday OFF', 1 => 'Month End OFF', 2 => 'Last Saturday Of Month OFF');
$SchoolOffRuleDetailType = array('Every' => 'Every', 'EveryEven' => 'Every Even', 'EveryOdd' => 'Every Odd');
$Weekdays = array('Sunday' => 'Sunday', 'Monday' => 'Monday', 'Tuesday' => 'Tuesday', 'Wednesday' => 'Wednesday', 'Thursday' => 'Thursday', 'Friday' => 'Friday', 'Saturday' => 'Saturday');
$BrachStaffList = array('Teaching' => 'Teaching', 'NonTeaching' => 'NonTeaching');

$ClassAndSectionsList = array();
$ClassAndSectionsList = AddedClass::GetClassAndSections();

$AllSchoolOffRules = array();
$AllSchoolOffRules = SchoolOffRule::GetAllSchoolOffRules();

$HasErrors = false;

$Clean = array();
$Clean['Process'] = 0;

$Clean['ApplicableOffType'] = array();
$Clean['IsWeeklyOff'] = 0;
$Clean['AppliesToSpecificClasses'] = 0;

$Clean['IsTeachingStaffApplicable'] = 0;
$Clean['IsNonTeachingStaffApplicable'] = 0;
$Clean['CreateUserID'] = 0;

$Clean['SchoolOffRuleType'] = array();
$Clean['ApplicableWeekdays'] = array();
$Clean['ApplicableBranchStaff'] = array();
$Clean['ApplicableClasses'] = array();

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
	case 1:
		if (isset($_POST['chkOffType']) && is_array($_POST['chkOffType']))
		{
			$Clean['ApplicableOffType'] = $_POST['chkOffType'];
		}
		if (isset($_POST['chkIsWeeklyOff']))
		{
			$Clean['IsWeeklyOff'] = 1;
		}

		if ($Clean['IsWeeklyOff'] == 1) 
		{
			if (isset($_POST['chkSchoolOffRuleType']) && is_array($_POST['chkSchoolOffRuleType']))
			{
				$Clean['SchoolOffRuleType'] = $_POST['chkSchoolOffRuleType'];
			}
			if (isset($_POST['chkApplicableWeekdays']) && is_array($_POST['chkApplicableWeekdays']))
			{
				$Clean['ApplicableWeekdays'] = $_POST['chkApplicableWeekdays'];
			}
			if (isset($_POST['chkApplicableBranchStaff']) && is_array($_POST['chkApplicableBranchStaff']))
			{
				$Clean['ApplicableBranchStaff'] = $_POST['chkApplicableBranchStaff'];
			}
			if (isset($_POST['chkApplicableClasses']) && is_array($_POST['chkApplicableClasses']))
			{
				$Clean['ApplicableClasses'] = $_POST['chkApplicableClasses'];
			}
		}
		
		$NewRecordValidator = new Validator();

		$OffTypeRecordValidator = new Validator();

		$CounterOffType = 0;

		foreach ($Clean['ApplicableOffType'] as $OffTypeValue) 
		{
			$OffTypeRecordValidator->ValidateInSelect($OffTypeValue, $OffType, 'Unknown Error, Please try again.');
		}

		if ($OffTypeRecordValidator->HasNotifications())
		{
			$NewRecordValidator->AttachTextError('Unknown Error, Please try again.');
		}

		unset($OffTypeRecordValidator);

		$NotApplicableClasses = array();

		if (count($Clean['ApplicableOffType']) == 0 && $Clean['IsWeeklyOff'] == 0) 
		{
			$NewRecordValidator->AttachTextError('Please select a off type.');
		}

		if ($Clean['IsWeeklyOff'] == 1 && count($Clean['SchoolOffRuleType']) == 0 && count($Clean['ApplicableWeekdays']) == 0) 
		{
			$NewRecordValidator->AttachTextError('Please select atleast a day from weekdays.');
		}

		if ($Clean['IsWeeklyOff'] && count($Clean['ApplicableBranchStaff']) == 0 && count($Clean['ApplicableClasses']) == 0) 
		{
			$NewRecordValidator->AttachTextError('Please select atleast one applicable user.');
		}

		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}

		if ($Clean['IsWeeklyOff']) 
		{	

			$SchoolOffRuleTypeRecordValidator = new Validator();

			foreach ($Clean['SchoolOffRuleType'] as $SchoolOffRuleType) 
			{
				$SchoolOffRuleTypeRecordValidator->ValidateInSelect($SchoolOffRuleType, $SchoolOffRuleDetailType, 'Unknown Error, Please try again.');
			}

			if ($SchoolOffRuleTypeRecordValidator->HasNotifications())
			{
				$NewRecordValidator->AttachTextError('Unknown Error, Please try again.');
			}

			unset($SchoolOffRuleTypeRecordValidator);
			
			$WeekdaysRecordValidator = new Validator();	

			$IsAnyWeekDaySelected = 1;

			foreach ($Clean['ApplicableWeekdays'] as $SchoolOffRuleType => $ApplicableWeekdayDetails) 
			{
				$WeekdaysRecordValidator->ValidateInSelect($SchoolOffRuleType, $SchoolOffRuleDetailType, 'Unknown Error, Please try again.');

				foreach ($ApplicableWeekdayDetails as $ApplicableWeekday) 
				{		
					$IsAnyWeekDaySelected = 0;
					$WeekdaysRecordValidator->ValidateInSelect($ApplicableWeekday, $Weekdays, 'Unknown Error, Please try again.');
				}
			}

			if ($WeekdaysRecordValidator->HasNotifications())
			{
				$NewRecordValidator->AttachTextError('Unknown Error, Please try again.');
			}

			unset($WeekdaysRecordValidator);

			if ($IsAnyWeekDaySelected) 
			{
				$NewRecordValidator->AttachTextError('Please select atleast a day from weekdays.');
			}

			$BranchStaffRecordValidator = new Validator();	

			foreach ($Clean['ApplicableBranchStaff'] as $BranchStaff) 
			{
				$BranchStaffRecordValidator->ValidateInSelect($BranchStaff, $BrachStaffList, 'Unknown Error, Please try again.');
			}

			if ($BranchStaffRecordValidator->HasNotifications())
			{
				$NewRecordValidator->AttachTextError('Unknown Error, Please try again.');
			}

			unset($BranchStaffRecordValidator);

			$ClassesRecordValidator = new Validator();

			foreach ($Clean['ApplicableClasses'] as $ClassSectionID => $Value) 
			{
				$ClassesRecordValidator->ValidateInSelect($ClassSectionID, $ClassAndSectionsList, 'Unknown Error, Please try again.');
			}

			if ($ClassesRecordValidator->HasNotifications())
			{
				$NewRecordValidator->AttachTextError('Unknown Error, Please try again.');
			}

			unset($ClassesRecordValidator);

			if (count($Clean['ApplicableClasses']) > 0) 
			{
				$NotApplicableClasses = array_diff_key($ClassAndSectionsList, $Clean['ApplicableClasses']);
			}
		}

		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}
		
		$IsOffTypeApplicable = 0;
		$AppliesToSpecificClasses = 0;
		$IsTeachingStaffApplicable = 0;
		$IsNonTeachingStaffApplicable = 0;

		foreach ($Clean['ApplicableBranchStaff'] as $BranchStaff) 
		{
			if ($BranchStaff == 'Teaching') 
			{
				$IsTeachingStaffApplicable = 1;
			}
			else
			{
				$IsNonTeachingStaffApplicable = 1;
			}
		}

		if (count($Clean['ApplicableOffType']) > 0) 
		{
			$IsOffTypeApplicable = 1;
		}

		if (count($Clean['ApplicableClasses']) > 0) 
		{
			$AppliesToSpecificClasses = 1;
		}

		$NewschoolOffRule = new schoolOffRule();
				
		$NewschoolOffRule->SetIsOffTypeApplicable($IsOffTypeApplicable);
		$NewschoolOffRule->SetIsWeeklyOff($Clean['IsWeeklyOff']);
		$NewschoolOffRule->SetAppliesToSpecificClasses($AppliesToSpecificClasses);
		$NewschoolOffRule->SetIsTeachingStaffApplicable($IsTeachingStaffApplicable);
		$NewschoolOffRule->SetIsNonTeachingStaffApplicable($IsNonTeachingStaffApplicable);
		
		$NewschoolOffRule->SetCreateUserID($LoggedUser->GetUserID());

		$NewschoolOffRule->SetApplicableOffType($Clean['ApplicableOffType']);
		$NewschoolOffRule->SetApplicableWeekOff($Clean['ApplicableWeekdays']);

		if (!$NewschoolOffRule->Save($NotApplicableClasses))
		{
			$NewRecordValidator->AttachTextError(ProcessErrors($NewschoolOffRule->GetLastErrorCode()));
			$HasErrors = true;
			break;
		}

		header('location:add_school_off_rule.php?Mode=ED');
		exit;
	break;

	case 5:
		if ($LoggedUser->HasPermissionForTask(TASK_DELETE_SCHOOL_BASIC_DETAILS) !== true)
		{
			header('location:unauthorized_login_admin.php');
			exit;
		}
		
		if (isset($_GET['SchoolOffRuleID']))
		{
			$Clean['SchoolOffRuleID'] = (int) $_GET['SchoolOffRuleID'];
		}
		
		if ($Clean['SchoolOffRuleID'] <= 0)
		{
			header('location:../error_page.php');
			exit;
		}						
			
		try
		{
			$schoolOffRuleToDelete = new schoolOffRule($Clean['SchoolOffRuleID']);
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
				
		if (!$schoolOffRuleToDelete->Remove())
		{
			$RecordValidator->AttachTextError(ProcessErrors($schoolOffRuleToDelete->GetLastErrorCode()));
			$HasErrors = true;
			break;
		}
		
		header('location:add_school_off_rule.php?Mode=DD');
	break;
}

require_once('../html_header.php');
?>
<title>Add School Off Rule</title>
<link href="/admin/vendor/jquery-ui/jquery-ui.min.css" rel="stylesheet">
<!-- DataTables CSS -->
<link href="/admin/vendor/datatables-plugins/dataTables.bootstrap.css" rel="stylesheet">

<!-- DataTables Responsive CSS -->
<link href="/admin/vendor/datatables-responsive/dataTables.responsive.css" rel="stylesheet">
<style type="text/css">
.col-md-3:not(:empty)
{
    border: 1px solid #ccc;
}
.col-md-3 input
{
    margin-left: 10px;
}

.col-md-4 input
{
    margin-left: 10px;
}
.col-md-4:not(:empty)
{
    width: 31.000%;
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
                    <h1 class="page-header">Add School Off Rule</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="AddSchoolOffRule" action="add_school_off_rule.php" method="post">
            	<div class="panel panel-default">
                    <div class="panel-heading">
                        Enter School Off Rule Details
                    </div>
                    <div class="panel-body">
<?php
						if ($HasErrors == true)
						{
							echo $NewRecordValidator->DisplayErrors();
						}
?>                    
						<div class="form-group">
                            <label for="OffType" class="col-lg-2 control-label">Off Type</label>
                            <div class="col-lg-10">
<?php
								foreach ($OffType as $key => $OffTypeName) 
								{
?>
									<label class="checkbox-inline">
			            				<input class="chkOffType" type="checkbox" name="chkOffType[]" <?php echo array_key_exists($key, $Clean['ApplicableOffType']) ? 'Checked="Checked"' : ''; ?> value="<?php echo $key;?>"/><?php echo $OffTypeName; ?>
			                		</label>							
<?php
								}
?>	
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="IsWeeklyOff" class="col-lg-2 control-label">Is Weekly Off</label>
                            <div class="col-lg-10">
								<label for="IsWeeklyOff">
		            				<input type="checkbox" id="IsWeeklyOff" <?php echo ($Clean['IsWeeklyOff']) ? 'Checked="Checked"' : ''; ?> name="chkIsWeeklyOff"/>
		                		</label>
                            </div>
                        </div>
                        <div class="row WeeklyOffType" style="<?php echo ($Clean['IsWeeklyOff']) ? '' : 'display: none';?>"w >
                            <div class="col-lg-12">
                                <table width="100%" class="table table-striped table-bordered table-hover" id="DataTableRecords">
                                    <thead>
                                        <tr>
                                            <th width="30%">Weekly Off Type</th>
                                            <th>Applicable Days</th>
                                        </tr>
                                    </thead>
                                    <tbody>
<?php
									foreach ($SchoolOffRuleDetailType as $SchoolOffRuleType => $SchoolOffRuleTypeName) 
									{

?>			
									<tr>
										<td width="30%">
											<label class="checkbox-inline">
												<input class="SchoolOffRuleType" SchoolOffRuleType="<?php echo $SchoolOffRuleType;?>" <?php echo (in_array($SchoolOffRuleType, $Clean['SchoolOffRuleType']) ? 'checked="Checked"' : '');?> type="checkbox" name="chkSchoolOffRuleType[]" value="<?php echo $SchoolOffRuleType;?>" /><?php echo $SchoolOffRuleTypeName;?>
											</label>
										</td>
										<td>
<?php
										$KeySchoolOffRuleType = array();

										foreach ($Weekdays as $Weekday => $WeekdaysName) 
										{
											if (isset($Clean['ApplicableWeekdays'][$SchoolOffRuleType])) 
											{
												$KeySchoolOffRuleType = $Clean['ApplicableWeekdays'][$SchoolOffRuleType];
											}
?>
											<label class="checkbox-inline">
												<input class="<?php echo $SchoolOffRuleType;?>" type="checkbox" name="chkApplicableWeekdays[<?php echo $SchoolOffRuleType;?>][]" value="<?php echo $Weekday;?>" <?php echo in_array($Weekday, $KeySchoolOffRuleType) ? 'checked="checked"' : '';?> <?php echo (in_array($SchoolOffRuleType, $Clean['SchoolOffRuleType']) ? '' : 'disabled="disabled"');?> /><?php echo $Weekday;?>
											</label>
<?php
										}
?>
										</td>
									</tr>
<?php
									}
?>
                                    </tbody>
                                </table>
							</div>
                    	</div>                      
                        <div class="form-group ApplicableBranchStaff" style="<?php echo ($Clean['IsWeeklyOff']) ? '' : 'display: none';?>">
                        	<label for="ApplicableBranchStaff" class="col-lg-2 control-label">Applicable Branch Staff</label>
                        	<div class="col-lg-10">
<?php
							foreach ($BrachStaffList as $BrachStaff => $BrachStaffName) 
							{
?>
								<label class="checkbox-inline">
									<input class="BrachStaff" type="checkbox" name="chkApplicableBranchStaff[]" value="<?php echo $BrachStaff;?>" <?php echo in_array($BrachStaff, $Clean['ApplicableBranchStaff']) ? 'checked="checked"' : '';?> /><?php echo $BrachStaff;?>
								</label>
<?php
							}
?>
                        	</div>
                        </div>
                        <div class="form-group ApplicableClasses" style="<?php echo ($Clean['IsWeeklyOff']) ? '' : 'display: none';?>">
                            <label for="ApplicableClasses" class="col-lg-2 control-label" style="width: 15.66666667%; padding-right: 4px;">Applicable Classes</label>
                            <div class="col-lg-10">
<?php
							if(count($ClassAndSectionsList) > 0)
							{
?>
								<div class="row">
									<div class="col-md-4" style="border: 1px solid #ccc;">
		                                <label><input type="checkbox" id="SelectAll" name="chkSelectAll" value="1" <?php echo(count($ClassAndSectionsList) == count($Clean['ApplicableClasses'])) ? 'checked="checked"' : '0';?> />Select All</label>
		                            </div>
		                            <div class="col-md-4" style="border: 1px solid #ccc;">
		                                <label><input type="checkbox" id="ClearAll" name="chkClearAll" value="1" />Clear All</label>
		                            </div>
		                            <div class="col-md-4">                             
		                            </div>
	                            </div>
	                        </div>
<?php
	                        $TotalClassAndSections = count($ClassAndSectionsList);

	                        if ($TotalClassAndSections > 0)
	                        {
	                            $CurrentClassAndSectionCounter = 1;
	                            $CurrentRowTDCounter = 1;
	                          
	                            foreach($ClassAndSectionsList as $ClassAndSectionID => $ClassAndSectionName)
	                            {
	                                if ($CurrentClassAndSectionCounter == 1 || ($CurrentClassAndSectionCounter - 1) % 3 == 0)
	                                {
	                                    echo '<div class="row"><div class="col-md-2"></div>';
	                                }
	                                if (array_key_exists($ClassAndSectionID, $Clean['ApplicableClasses']))
	                                {
?>
									
										<div class="col-md-3"><label class="checkbox-inline" style="font-size:14px; font-weight:400;"><input style="vertical-align:top;" type="checkbox" class="ClassAndSectionList" checked="checked" id="ClassSection<?php echo $ClassAndSectionID;?>" name="chkApplicableClasses[<?php echo $ClassAndSectionID;?>]" value="1"  /><?php echo $ClassAndSectionName;?></label></div>
<?php
	                                }
	                                else
	                                {
?>
	                                    <div class="col-md-3"><label class="checkbox-inline" style="font-size:14px; font-weight:400;"><input style="vertical-align:top;" type="checkbox" class="ClassAndSectionList" id="ClassSection<?php echo $ClassAndSectionName?>" name="chkApplicableClasses[<?php echo $ClassAndSectionID?>]" value="1" /><?php echo $ClassAndSectionName?></label></div>
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
                        <div class="form-group">
                        <div class="col-sm-offset-2 col-lg-10">
                        	<input type="hidden" name="hdnProcess" value="1" />
							<button type="submit" class="btn btn-primary">Save</button>
                        </div>
                      </div>
                    </div>
                </div>
            </form>
<?php
			if ($LoggedUser->HasPermissionForTask(TASK_LIST_SCHOOL_BASIC_DETAILS) === true)
			{
?>
				<div class="panel panel-default">
                <div class="panel-heading">
                    School Off Rule Details List
                </div>
                <div class="panel-body">
                	<div>
                        <div class="col-lg-12" style="text-align: right;">
                            <div class="print-btn-container"><button id="PrintButton" type="submit" class="btn btn-primary">Print</button></div>
                        </div>
                        <div class="row" id="RecordTableHeading">
                            <div class="col-lg-12">
                            	<div class="report-heading-container"><strong>School Off Rule Details List on <?php echo date('d-m-Y h:i A'); ?></strong></div>
                            </div>
                            <div class="row">
                                <div class="col-lg-12">
                                    <table width="100%" class="table table-striped table-bordered table-hover" id="DataTableRecords">
                                        <thead>
                                            <tr>
                                                <th>S. No</th>
                                                <th>Is Off TypeApplicable</th>
                                                <th>Is Weekly Off</th>
                                                <th>Applies To Specific Classes</th>
                                                <th>Is Teaching Staff Applicable</th>
                                                <th>Is Non Teaching Staff Applicable</th>
                                                <th>Create User</th>
                                                <th>Create Date</th>
                                                <th class="print-hidden">Operations</th>
                                            </tr>
                                        </thead>
                                        <tbody>
<?php
                                    if (is_array($AllSchoolOffRules) && count($AllSchoolOffRules) > 0)
                                    {
                                        $Counter = 0;
                                        foreach ($AllSchoolOffRules as $SchoolOffRuleID => $SchoolOffRuleDetails)
                                        {
?>
                                        <tr>
                                            <td><?php echo ++$Counter; ?></td>
                                            <td><?php echo (($SchoolOffRuleDetails['IsOffTypeApplicable']) ? 'Yes' : 'No'); ?></td>
                                            <td><?php echo (($SchoolOffRuleDetails['IsWeeklyOff']) ? 'Yes' : 'No'); ?></td>
                                            <td><?php echo (($SchoolOffRuleDetails['AppliesToSpecificClasses']) ? 'Yes' : 'No'); ?></td>
                                            <td><?php echo (($SchoolOffRuleDetails['IsTeachingStaffApplicable']) ? 'Yes' : 'No'); ?></td>
                                            <td><?php echo (($SchoolOffRuleDetails['IsNonTeachingStaffApplicable']) ? 'Yes' : 'No'); ?></td>
                                            <td><?php echo $SchoolOffRuleDetails['CreateUserName']; ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($SchoolOffRuleDetails['CreateDate'])); ?></td>
                                            <td class="print-hidden">
<?php
                                                /*if ($LoggedUser->HasPermissionForTask(TASK_EDIT_MENU) === true)
                                                {
                                                    echo '<a href="edit_subject_master.php?Process=2&amp;SchoolOffRuleID='.$SchoolOffRuleID.'">Edit</a>';
                                                }
                                                else
                                                {
                                                    echo 'Edit';
                                                }

                                                echo '&nbsp;|&nbsp;';
                                                */ 

                                                if ($LoggedUser->HasPermissionForTask(TASK_DELETE_SCHOOL_BASIC_DETAILS) === true)
                                                {
                                                    echo '<a class="delete-record" href="add_school_off_rule.php?Process=5&amp;SchoolOffRuleID='.$SchoolOffRuleID.'">Delete</a>'; 
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
							</div>
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
if (PrintMessage($_GET, $Message))
{
?>
    <script type="text/javascript">
        alert('<?php echo $Message; ?>');
    </script>
<?php
}
?>
<script src="/admin/vendor/jquery-ui/jquery-ui.min.js"></script>
<!-- DataTables JavaScript -->
<script src="/admin/vendor/datatables/js/jquery.dataTables.min.js"></script>
<script src="/admin/vendor/datatables-plugins/dataTables.bootstrap.min.js"></script>
<script src="/admin/vendor/datatables-responsive/dataTables.responsive.js"></script>

<!-- JavaScript To Print A Report -->
<script src="/admin/js/print-report.js"></script>
<script type="text/javascript">
$(function()
{	
	$('#DataTableRecords').DataTable(
	{
        responsive: true,
        bPaginate: false,
        bSort: false,
        searching: false, 
        info: false
    });

	$('#SelectAll').click(function()
	{
		if($('#SelectAll').prop("checked"))
		{
			$('#ClearAll').prop("checked", "");
			$('.ClassAndSectionList').prop("checked", "checked");
		}
		else
		{
			$('.ClassAndSectionList').prop("checked", false);
		}
	});
	
	$('#ClearAll').click(function()
	{
		if($('#ClearAll').prop("checked"))
		{
			$('#SelectAll').prop("checked", "");
			$('.ClassAndSectionList').prop("checked", "");
		}
	});
	
	$('.ClassAndSectionList').click(function()
	{
		$('#SelectAll').prop("checked", "");
		$('#ClearAll').prop("checked", "");		
	});

	$('#IsWeeklyOff').click(function()
	{
		if ($(this).is(':checked'))
		{
			$('.ApplicableClasses').css('display', 'block');
			$('.ApplicableBranchStaff').css('display', 'block');
			$('.WeeklyOffType').css('display', 'block');
		}
		else
		{
			$('.ApplicableClasses').css('display', 'none');	
			$('.ApplicableBranchStaff').css('display', 'none');
			$('.WeeklyOffType').css('display', 'none');
		}
	});

	$('.SchoolOffRuleType').click(function()
	{
		if ($(this).attr('SchoolOffRuleType') == 'Every') 
		{
			if ($(this).is(':checked'))
			{
				$('.Every').prop('disabled', false);
			}
			else
			{
				$('.Every').prop('disabled', true);
			}
		}

		if ($(this).attr('SchoolOffRuleType') == 'EveryEven') 
		{
			if ($(this).is(':checked'))
			{
				$('.EveryEven').prop('disabled', false);
			}
			else
			{
				$('.EveryEven').prop('disabled', true);
			}
		}

		if ($(this).attr('SchoolOffRuleType') == 'EveryOdd') 
		{
			if ($(this).is(':checked'))
			{
				$('.EveryOdd').prop('disabled', false);
			}
			else
			{
				$('.EveryOdd').prop('disabled', true);
			}
		}
	});
});
</script>
</body>
</html>
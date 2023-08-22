<?php
require_once('../../classes/class.users.php');
require_once('../../classes/class.validation.php');
require_once('../../classes/class.authentication.php');

require_once('../../classes/school_administration/class.classes.php');
require_once('../../classes/school_administration/class.students.php');
require_once('../../classes/school_administration/class.student_details.php');
require_once('../../classes/school_administration/class.branch_staff.php');

require_once('../../classes/class.sms_queue.php');

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

if ($LoggedUser->HasPermissionForTask(TASK_SEND_BULK_INDIVIDUAL_SMS) !== true)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}

$AllClasses = array();
$AllClasses = AddedClass::GetActiveClasses();

$StudentList = array();
$TeachingFacultyList = array();
$NonTeachingFacultyList = array();

$SectionList = array();

$SMSReceiverTypeList = array('Student' => 'Students', 'TeachingStaff' => 'Teaching Staff', 'NonTeachingStaff' => 'Non Teaching Staff');

$HasBulkSMSErrors = false;
$HasIndividualSMSErrors = false;

$Clean = array();

$Clean['BulkMessage'] = '';
$Clean['SelectedBulkSMSReceiverType'] = 'Student';

$Clean['SelectedClassList'] = array();

$Clean['IndividualMessage'] = '';
$Clean['SelectedIndividualSMSReceiverType'] = 'Student';

$Clean['ClassID'] = 0;
$Clean['ClassSectionID'] = 0;

$Clean['Process'] = 0;

if (isset($_POST['hdnProcess']))
{
	$Clean['Process'] = (int) $_POST['hdnProcess'];
}
elseif(isset($_GET['Process']))
{
	$Clean['Process'] = $_GET['Process'];
}
switch ($Clean['Process'])
{
	case 1:		//	Send Bulk SMS
		if (isset($_POST['txtBulkMessage']))
		{
			$Clean['BulkMessage'] = strip_tags(trim($_POST['txtBulkMessage']));
		}
		
		if (isset($_POST['optBulkSMSReceiverType']))
		{
			$Clean['SelectedBulkSMSReceiverType'] = strip_tags(trim($_POST['optBulkSMSReceiverType']));
		}

		if (isset($_POST['chkClassToSendSMS']))
		{
			$Clean['SelectedClassList'] = $_POST['chkClassToSendSMS'];
		}
		
		$NewRecordValidator = new Validator();

		$NewRecordValidator->ValidateStrings($Clean['BulkMessage'], 'Message content is required and should be between 10 and 1500 characters.', 10, 1500);

		if ($NewRecordValidator->ValidateInSelect($Clean['SelectedBulkSMSReceiverType'], $SMSReceiverTypeList, 'Unknown error, please try again.'))
		{
			if ($Clean['SelectedBulkSMSReceiverType'] == 'Student')
			{
				if (empty($Clean['SelectedClassList']))
				{
					$NewRecordValidator->AttachTextError('Please select atleast one class.');
					$HasBulkSMSErrors = true;
					break;
				}

				foreach ($Clean['SelectedClassList'] as $ClassID => $Value)
				{
					if (!array_key_exists($ClassID, $AllClasses))
					{
						$NewRecordValidator->AttachTextError('Unknown error, please try again.');
						$HasBulkSMSErrors = true;
						break 2;
					}
				}
			}
		}

		if ($NewRecordValidator->HasNotifications())
		{
			$HasBulkSMSErrors = true;
			break;
		}

		$SMSList = array();
		$CounterForStudents = 0;

		$SMSOriginalContent = '';
		$SMSOriginalContent = $Clean['BulkMessage'];
		
		switch ($Clean['SelectedBulkSMSReceiverType'])
		{
			case 'Student':
				$MatchResults = array();
				preg_match_all("/\{(\w+)\}/i", $SMSOriginalContent, $MatchResults, PREG_PATTERN_ORDER);
				
				foreach ($Clean['SelectedClassList'] as $ClassID => $Value)
				{
					$SectionList = AddedClass::GetClassSections($ClassID);

					foreach ($SectionList as $ClassSectionID => $SectionName)
					{
						$StudentList = StudentDetail::GetStudentsByClassSectionID($ClassSectionID);

						foreach ($StudentList as $StudentID => $StudentDetails)
						{
							$SMSContent = $SMSOriginalContent;
							
							if ($StudentDetails['MobileNumber'] == '')
							{
								continue;
							}

							$CounterForStudents++;

							if (is_array($MatchResults) && count($MatchResults) > 0)
							{
								foreach ($MatchResults[1] as $key1=>$values)
								{
									switch ($values)
									{
										case 'FirstName':
											$SMSContent = str_replace('{FirstName}', ucwords($StudentDetails['FirstName']), $SMSContent);
										break;
										
										case 'FullName':
											$SMSContent = str_replace('{FullName}', ucfirst($StudentDetails['FirstName'] . ' ' . $StudentDetails['LastName']), $SMSContent);
										break;
										
										case 'FatherName':
											$SMSContent = str_replace('{FatherName}', ucwords($StudentDetails['FatherName']), $SMSContent);
										break;
										
										case 'RollNumber':
											$SMSContent = str_replace('{RollNumber}', ucwords($StudentDetails['RollNumber']), $SMSContent);
										break;
									}
								}

								$SMSList[$CounterForStudents]['MobileNumber'] = $StudentDetails['MobileNumber'];
								$SMSList[$CounterForStudents]['Message'] = $SMSContent;
							}
						}
					}
				}
			break;

			case 'TeachingStaff':
				$MatchResults = array();
				preg_match_all("/\{(\w+)\}/i", $SMSOriginalContent, $MatchResults, PREG_PATTERN_ORDER);

				$TeachingFacultyList = BranchStaff::GetActiveBranchStaff('Teaching');

				foreach ($TeachingFacultyList as $BranchStaffID => $BranchStaffDetails)
				{
					$SMSContent = $SMSOriginalContent;
					
					if ($BranchStaffDetails['MobileNumber1'] == '')
					{
						continue;
					}

					$CounterForStudents++;

					if (is_array($MatchResults) && count($MatchResults) > 0)
					{
						foreach ($MatchResults[1] as $key1=>$values)
						{
							switch ($values)
							{
								case 'FirstName':
									$SMSContent = str_replace('{FirstName}', ucwords($BranchStaffDetails['FirstName']), $SMSContent);
								break;
								
								case 'FullName':
									$SMSContent = str_replace('{FullName}', ucfirst($BranchStaffDetails['FirstName'] . ' ' . $BranchStaffDetails['LastName']), $SMSContent);
								break;
							}
						}

						$SMSList[$CounterForStudents]['MobileNumber'] = $BranchStaffDetails['MobileNumber1'];
						$SMSList[$CounterForStudents]['Message'] = $SMSContent;
					}
				}
			break;

			case 'NonTeachingStaff':
				$MatchResults = array();
				preg_match_all("/\{(\w+)\}/i", $SMSOriginalContent, $MatchResults, PREG_PATTERN_ORDER);

				$NonTeachingFacultyList = BranchStaff::GetActiveBranchStaff('NonTeaching');

				foreach ($NonTeachingFacultyList as $BranchStaffID => $BranchStaffDetails)
				{
					$SMSContent = $SMSOriginalContent;
					
					if ($BranchStaffDetails['MobileNumber1'] == '')
					{
						continue;
					}

					$CounterForStudents++;

					if (is_array($MatchResults) && count($MatchResults) > 0)
					{
						foreach ($MatchResults[1] as $key1=>$values)
						{
							switch ($values)
							{
								case 'FirstName':
									$SMSContent = str_replace('{FirstName}', ucwords($BranchStaffDetails['FirstName']), $SMSContent);
								break;
								
								case 'FullName':
									$SMSContent = str_replace('{FullName}', ucfirst($BranchStaffDetails['FirstName'] . ' ' . $BranchStaffDetails['LastName']), $SMSContent);
								break;
							}
						}

						$SMSList[$CounterForStudents]['MobileNumber'] = $BranchStaffDetails['MobileNumber1'];
						$SMSList[$CounterForStudents]['Message'] = $SMSContent;
					}
				}
			break;
		}

		//echo '<pre>';var_dump($SMSList);exit;
		foreach ($SMSList as $Counter => $SMSDetails)
		{
			$NewSMSQueue = new SMSQueue();

			$NewSMSQueue->SetPhoneNumber($SMSDetails['MobileNumber']);
			$NewSMSQueue->SetSMSMessage($SMSDetails['Message']);
			$NewSMSQueue->SetCreateUserID($LoggedUser->GetUserID());

			$NewSMSQueue->Save();
		}

		header('location:send_sms.php?Mode=ED');
		exit;
	break;

	case 2:		//	Send Individual SMS
		if (isset($_POST['txtIndividualMessage']))
		{
			$Clean['IndividualMessage'] = strip_tags(trim($_POST['txtIndividualMessage']));
		}
		
		if (isset($_POST['optIndividualSMSReceiverType']))
		{
			$Clean['SelectedIndividualSMSReceiverType'] = strip_tags(trim($_POST['optIndividualSMSReceiverType']));
		}

		if (isset($_POST['drdClassID']))
		{
			$Clean['ClassID'] = (int) $_POST['drdClassID'];
		}

		if (isset($_POST['drdClassSectionID']))
		{
			$Clean['ClassSectionID'] = (int) $_POST['drdClassSectionID'];
		}

		$NewRecordValidator = new Validator();

		$NewRecordValidator->ValidateStrings($Clean['IndividualMessage'], 'Message content is required and should be between 10 and 1500 characters.', 10, 1500);
		
		$NewRecordValidator->ValidateInSelect($Clean['SelectedIndividualSMSReceiverType'], $SMSReceiverTypeList, 'Unknown error, please try again.');
		
		$SMSList = array();
		$CounterForStudents = 0;

		$SMSOriginalContent = '';
		$SMSOriginalContent = $Clean['IndividualMessage'];
		
		switch ($Clean['SelectedIndividualSMSReceiverType'])
		{
			case 'Student':
				
				if ($NewRecordValidator->ValidateInSelect($Clean['ClassID'], $AllClasses, 'Unknown error, please try again.'))
				{
					$SectionList = AddedClass::GetClassSections($Clean['ClassID']);
					
					if ($NewRecordValidator->ValidateInSelect($Clean['ClassSectionID'], $SectionList, 'Unknown error, please try again.'))
					{
						$StudentList = StudentDetail::GetStudentsByClassSectionID($Clean['ClassSectionID']);
					}
				}
				
				$SelectedStudentsList = array();
				if (isset($_POST['chkStudents']))
				{
					$SelectedStudentsList = $_POST['chkStudents'];
				}
				
				$MatchResults = array();
				preg_match_all("/\{(\w+)\}/i", $SMSOriginalContent, $MatchResults, PREG_PATTERN_ORDER);
				
				foreach ($SelectedStudentsList as $StudentID => $Value)
				{
					$SMSContent = $SMSOriginalContent;
					
					if (array_key_exists($StudentID, $StudentList))
					{
						$StudentDetails = array();
						$StudentDetails = $StudentList[$StudentID];
					}
					else
					{
						header('location:/admin/error.php');
						exit;
					}
					
					if ($StudentDetails['MobileNumber'] == '')
					{
						continue;
					}

					$CounterForStudents++;

					if (is_array($MatchResults) && count($MatchResults) > 0)
					{
						foreach ($MatchResults[1] as $key1=>$values)
						{
							switch ($values)
							{
								case 'FirstName':
									$SMSContent = str_replace('{FirstName}', ucwords($StudentDetails['FirstName']), $SMSContent);
								break;

								case 'FullName':
									$SMSContent = str_replace('{FullName}', ucfirst($StudentDetails['FirstName'] . ' ' . $StudentDetails['LastName']), $SMSContent);
								break;

								case 'FatherName':
									$SMSContent = str_replace('{FatherName}', ucwords($StudentDetails['FatherName']), $SMSContent);
								break;

								case 'RollNumber':
									$SMSContent = str_replace('{RollNumber}', ucwords($StudentDetails['RollNumber']), $SMSContent);
								break;
							}
						}

						$SMSList[$CounterForStudents]['MobileNumber'] = $StudentDetails['MobileNumber'];
						$SMSList[$CounterForStudents]['Message'] = $SMSContent;
					}
				}
			break;

			case 'TeachingStaff':
				$SelectedTeachingStaffList = array();
				if (isset($_POST['chkTeachingStaff']))
				{
					$SelectedTeachingStaffList = $_POST['chkTeachingStaff'];
				}
				
				$MatchResults = array();
				preg_match_all("/\{(\w+)\}/i", $SMSOriginalContent, $MatchResults, PREG_PATTERN_ORDER);

				$TeachingFacultyList = BranchStaff::GetActiveBranchStaff('Teaching');
				
				foreach ($SelectedTeachingStaffList as $BranchStaffID => $Value)
				{
					$SMSContent = $SMSOriginalContent;
					
					if (array_key_exists($BranchStaffID, $TeachingFacultyList))
					{
						$BranchStaffDetails = array();
						$BranchStaffDetails = $TeachingFacultyList[$BranchStaffID];
					}
					else
					{
						header('location:/admin/error.php');
						exit;
					}
					//var_dump($BranchStaffDetails);continue;
					if ($BranchStaffDetails['MobileNumber1'] == '')
					{
						continue;
					}

					$CounterForStudents++;

					if (is_array($MatchResults) && count($MatchResults) > 0)
					{
						foreach ($MatchResults[1] as $key1=>$values)
						{
							switch ($values)
							{
								case 'FirstName':
									$SMSContent = str_replace('{FirstName}', ucwords($BranchStaffDetails['FirstName']), $SMSContent);
								break;
								
								case 'FullName':
									$SMSContent = str_replace('{FullName}', ucfirst($BranchStaffDetails['FirstName'] . ' ' . $BranchStaffDetails['LastName']), $SMSContent);
								break;
							}
						}
						
						$SMSList[$CounterForStudents]['MobileNumber'] = $BranchStaffDetails['MobileNumber1'];
						$SMSList[$CounterForStudents]['Message'] = $SMSContent;
					}
				}
			break;

			case 'NonTeachingStaff':
				$SelectedNonTeachingStaffList = array();
				if (isset($_POST['chkNonTeachingStaff']))
				{
					$SelectedNonTeachingStaffList = $_POST['chkNonTeachingStaff'];
				}
				
				$MatchResults = array();
				preg_match_all("/\{(\w+)\}/i", $SMSOriginalContent, $MatchResults, PREG_PATTERN_ORDER);

				$NonTeachingFacultyList = BranchStaff::GetActiveBranchStaff('NonTeaching');

				foreach ($SelectedNonTeachingStaffList as $BranchStaffID => $Value)
				{
					$SMSContent = $SMSOriginalContent;
					
					if (array_key_exists($BranchStaffID, $NonTeachingFacultyList))
					{
						$BranchStaffDetails = array();
						$BranchStaffDetails = $NonTeachingFacultyList[$BranchStaffID];
					}
					else
					{
						header('location:/admin/error.php');
						exit;
					}
					
					if ($BranchStaffDetails['MobileNumber1'] == '')
					{
						continue;
					}

					$CounterForStudents++;

					if (is_array($MatchResults) && count($MatchResults) > 0)
					{
						foreach ($MatchResults[1] as $key1=>$values)
						{
							switch ($values)
							{
								case 'FirstName':
									$SMSContent = str_replace('{FirstName}', ucwords($BranchStaffDetails['FirstName']), $SMSContent);
								break;
								
								case 'FullName':
									$SMSContent = str_replace('{FullName}', ucfirst($BranchStaffDetails['FirstName'] . ' ' . $BranchStaffDetails['LastName']), $SMSContent);
								break;
							}
						}

						$SMSList[$CounterForStudents]['MobileNumber'] = $BranchStaffDetails['MobileNumber1'];
						$SMSList[$CounterForStudents]['Message'] = $SMSContent;
					}
				}
			break;
		}
		
		if ($NewRecordValidator->HasNotifications())
		{
			$HasIndividualSMSErrors = true;
			break;
		}
		
		//echo '<pre>';var_dump($SMSList);exit;
		foreach ($SMSList as $Counter => $SMSDetails)
		{
			$NewSMSQueue = new SMSQueue();

			$NewSMSQueue->SetPhoneNumber($SMSDetails['MobileNumber']);
			$NewSMSQueue->SetSMSMessage($SMSDetails['Message']);
			$NewSMSQueue->SetCreateUserID($LoggedUser->GetUserID());

			$NewSMSQueue->Save();
		}

		header('location:send_sms.php?Mode=ED');
		exit;
	break;
}

require_once('../html_header.php');
?>
<title>Send SMS</title>
<link href="../vendor/jquery-ui/jquery-ui.min.css" rel="stylesheet">
<style>
.place-holder-text a {
	color: gray !important;
	cursor: pointer;
	text-decoration: none;
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
                    <h1 class="page-header">Send SMS</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
			<form class="form-horizontal" name="SendSMS" action="send_sms.php" method="post">
				<div class="panel panel-default">
					<div class="panel-heading">
						<strong><a data-toggle="collapse" data-parent="#accordion" href="#collapseOne">Send Bulk SMS</a></strong>
					</div>
					<div id="collapseOne" class="panel-collapse collapse in">
						<div class="panel-body">
<?php
							if ($HasBulkSMSErrors == true)
							{
								echo $NewRecordValidator->DisplayErrors();
							}
?>
							<div class="form-group">
								<label for="MessageBulk" class="col-lg-2 control-label">Message</label>
								<div class="col-lg-8">
									<textarea name="txtBulkMessage" class="form-control" id="MessageBulk"><?php echo $Clean['BulkMessage']; ?></textarea>
								</div>
							</div>
							
							<div class="form-group">
								<div class="col-sm-offset-2 col-lg-10 place-holder-text">
									<a href="javascript:void(0);">{FirstName}</a> | <a href="javascript:void(0);">{FullName}</a>
								</div>
							</div>

							<div class="form-group">
								<label class="col-lg-2 control-label">Select User</label>
								<div class="col-lg-8">
<?php
								foreach ($SMSReceiverTypeList as $SMSReceiverType => $SMSReceiverTypeName)
								{
?>
									<label class="radio-inline">
										<input class="select-type" data-type="bulk" type="radio" <?php echo (($Clean['SelectedBulkSMSReceiverType'] == $SMSReceiverType) ? 'checked="checked"' : ''); ?> name="optBulkSMSReceiverType" value="<?php echo $SMSReceiverType; ?>"><?php echo $SMSReceiverTypeName; ?>
									</label>
<?php
								}
?>
								</div>
							</div>

							<div class="form-group select-class-bulk" style="<?php echo ($Clean['SelectedBulkSMSReceiverType'] == 'Student') ? '' : 'display:none;'; ?>">
								<label class="col-lg-2 control-label">&nbsp;</label>
								<div class="col-lg-8">
									<div class="panel panel-primary">
										<div class="panel-heading">
											<div class="panel-title">
												Select Classes
												<label class="pull-right" style="font-weight:normal;"><input type="checkbox" name="chkAllClasses" id="CheckAllClasses" value="1">&nbsp;Check All</label>
											</div>
										</div>
										<div class="panel-body">
<?php
										foreach ($AllClasses as $ClassID => $ClassName)
										{
?>
											<label class="checkbox-inline">
												<input class="classes" type="checkbox" <?php echo (in_array($ClassID, $Clean['SelectedClassList']) ? 'checked="checked"' : ''); ?> name="chkClassToSendSMS[<?php echo $ClassID; ?>]" value="1"><?php echo $ClassName; ?>
											</label>
<?php
										}
?>
										</div>
									</div>
								</div>
							</div>
							
							<div class="form-group">
								<div class="col-sm-offset-2 col-lg-10">
									<input type="hidden" name="hdnProcess" value="1" />
									<button type="submit" class="btn btn-primary"><i class="fa fa-paper-plane">&nbsp;</i> Send</button>
								</div>
							</div>
						</div>
					</div>
				</div>
			</form>
			<form class="form-horizontal" name="SendSMSIndividual" action="send_sms.php" method="post">
				<div class="panel panel-default">
					<div class="panel-heading">
						<strong><a data-toggle="collapse" data-parent="#accordion" href="#collapseOne1">Send Individual SMS</a></strong>
					</div>
					<div id="collapseOne1" class="panel-collapse collapse in">
						<div class="panel-body">
<?php
							if ($HasIndividualSMSErrors == true)
							{
								echo $NewRecordValidator->DisplayErrors();
							}
?>
							<div class="form-group">
								<label for="Message" class="col-lg-2 control-label">Message</label>
								<div class="col-lg-8">
									<textarea name="txtIndividualMessage" class="form-control" id="Message"><?php echo $Clean['IndividualMessage']; ?></textarea>
								</div>
							</div>

							<div class="form-group">
								<div class="col-sm-offset-2 col-lg-10 place-holder-text">
									<a href="javascript:void(0);">{FirstName}</a> | <a href="javascript:void(0);">{FullName}</a>
								</div>
							</div>
							
							<div class="form-group">
								<label class="col-lg-2 control-label">Select User</label>
								<div class="col-lg-8">
<?php
								foreach ($SMSReceiverTypeList as $SMSReceiverType => $SMSReceiverTypeName)
								{
?>
									<label class="radio-inline">
										<input class="select-type" data-type="individual" type="radio" <?php echo (($Clean['SelectedIndividualSMSReceiverType'] == $SMSReceiverType) ? 'checked="checked"' : ''); ?> name="optIndividualSMSReceiverType" value="<?php echo $SMSReceiverType; ?>"><?php echo $SMSReceiverTypeName; ?>
									</label>
<?php
								}
?>
								</div>
							</div>

							<div class="form-group select-class-individual" style="<?php echo ($Clean['SelectedIndividualSMSReceiverType'] == 'Student') ? '' : 'display:none;'; ?>">
								<label for="Class" class="col-lg-2 control-label">Class</label>
								<div class="col-lg-3">
									<select class="form-control"  name="drdClassID" id="ClassID">
										<option value="0">-- Select --</option>
<?php
										foreach ($AllClasses as $ClassID => $ClassesName)
										{
?>
											<option <?php echo ($ClassID == $Clean['ClassID'] ? 'selected="selected"' : ''); ?> value="<?php echo $ClassID; ?>"><?php echo $ClassesName; ?></option>
<?php
										}
?>
									</select>
								</div>

								<label for="Section" class="col-lg-2 control-label">Section</label>
								<div class="col-lg-3">
									<select class="form-control" name="drdClassSectionID" id="SectionID">
										<option value="0">-- Select --</option>
<?php
										if (is_array($SectionList) && count($SectionList) > 0)
										{
											foreach ($SectionList as $ClassSectionID => $SectionName) 
											{
												echo '<option ' . ($Clean['ClassSectionID'] == $ClassSectionID ? 'selected="selected"' : '') . ' value="' . $ClassSectionID . '">' . $SectionName . '</option>'	;
											}
										}
?>
									</select>
								</div>
							</div>
							
							<div class="form-group">
								<div class="col-sm-offset-2 col-lg-10 user-list-container">
<?php
							if ($Clean['SelectedIndividualSMSReceiverType'] == 'Student')
							{
?>
								<table class="table table-dark table-striped table-bordered table-hover">
									<thead>
										<tr>
											<th>S. No.</th>
											<th>Select</th>
											<th>Student Name</th>
											<th>Roll Number</th>
											<th>Mobile Number</th>
										</tr>
									</thead>
									<tbody>
<?php
								if (count($StudentList) > 0)
								{
									$Counter = 0;
									foreach ($StudentList as $StudentID => $StudentDetails)
									{
?>
										<tr>
											<td><?php echo ++$Counter; ?></td>
											<td class="text-center"><input <?php echo $StudentDetails['MobileNumber'] == '' ? 'disabled="disabled"' : ''; ?> type="checkbox" name="chkStudents[<?php echo $StudentID; ?>]" value="1" /></td>
											<td><?php echo $StudentDetails['FirstName'] . ' ' . $StudentDetails['FirstName']; ?></td>
											<td><?php echo $StudentDetails['RollNumber']; ?></td>
											<td><?php echo $StudentDetails['MobileNumber']; ?></td>
										</tr>
<?php
									}
								}
								else
								{
									echo '<tr><td colspan="5">No Records</td></tr>';
								}
?>
									</tbody>
								</table>
<?php
							}
							else if ($Clean['SelectedIndividualSMSReceiverType'] == 'TeachingStaff')
							{
?>
								<table class="table table-dark table-striped table-bordered table-hover">
									<thead>
										<tr>
											<th>S. No.</th>
											<th>Select</th>
											<th>Faculty Name</th>
											<th>Mobile Number</th>
										</tr>
									</thead>
									<tbody>
<?php
								if (count($TeachingFacultyList) > 0)
								{
									$Counter = 0;
									foreach ($TeachingFacultyList as $BranchStaffID => $BranchStaffDetails)
									{
?>
										<tr>
											<td><?php echo ++$Counter; ?></td>
											<td class="text-center"><input <?php echo $BranchStaffDetails['MobileNumber1'] == '' ? 'disabled="disabled"' : ''; ?> type="checkbox" name="chkTeachingStaff[<?php echo $BranchStaffID; ?>]" value="1" /></td>
											<td><?php echo $BranchStaffDetails['FirstName'] . ' ' . $BranchStaffDetails['FirstName']; ?></td>
											<td><?php echo $BranchStaffDetails['MobileNumber1']; ?></td>
										</tr>
<?php
									}
								}
								else
								{
									echo '<tr><td colspan="4">No Records</td></tr>';
								}
?>
									</tbody>
								</table>
<?php
							}
							else if ($Clean['SelectedIndividualSMSReceiverType'] == 'NonTeachingStaff')
							{
?>
								<table class="table table-dark table-striped table-bordered table-hover">
									<thead>
										<tr>
											<th>S. No.</th>
											<th>Select</th>
											<th>Faculty Name</th>
											<th>Mobile Number</th>
										</tr>
									</thead>
									<tbody>
<?php
								if (count($NonTeachingFacultyList) > 0)
								{
									$Counter = 0;
									foreach ($NonTeachingFacultyList as $BranchStaffID => $BranchStaffDetails)
									{
?>
										<tr>
											<td><?php echo ++$Counter; ?></td>
											<td class="text-center"><input <?php echo $BranchStaffDetails['MobileNumber1'] == '' ? 'disabled="disabled"' : ''; ?> type="checkbox" name="chkNonTeachingStaff[]" value="<?php echo $BranchStaffID; ?>" /></td>
											<td><?php echo $BranchStaffDetails['FirstName'] . ' ' . $BranchStaffDetails['FirstName']; ?></td>
											<td><?php echo $BranchStaffDetails['MobileNumber1']; ?></td>
										</tr>
<?php
									}
								}
								else
								{
									echo '<tr><td colspan="4">No Records</td></tr>';
								}
?>
									</tbody>
								</table>
<?php
							}
?>
								</div>
							</div>
							
							<div class="form-group">
								<div class="col-sm-offset-2 col-lg-10">
									<input type="hidden" name="hdnProcess" value="2" />
									<button type="submit" class="btn btn-primary"><i class="fa fa-paper-plane">&nbsp;</i> Send</button>
								</div>
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
<script src="https://cdn.jsdelivr.net/npm/gasparesganga-jquery-loading-overlay@2.1.6/dist/loadingoverlay.min.js"></script>

<script type="text/javascript">
$(function(){
	$('#CheckAllClasses').change(function(){
		if ($(this).is(':checked'))
		{
			$('.classes').prop('checked', true);
		}
		else
		{
			$('.classes').prop('checked', false);
		}
	});
	
	$('.place-holder-text a').click(function(){
		var SMSContent = $(this).closest('.form-group').prev().find('textarea').val();
		$(this).closest('.form-group').prev().find('textarea').val(SMSContent + $(this).text());
	});
	
	$('.select-type').change(function(){
		var type = $(this).attr('data-type');
		
		if ($(this).val() == 'Student')
		{
			$('.select-class-' + type).slideDown();
		}
		else
		{
			$('.select-class-' + type).slideUp();
		}
		
		$.LoadingOverlay("show");
		
		var form_data = new FormData();
		form_data.append('UserType', $(this).val());

		GetUserList(form_data);
	});

	$('#ClassID').change(function(){
		var ClassID = parseInt($(this).val());
		
		if (ClassID <= 0)
		{
			$('#SectionID').html('<option value="0">-- Select --</option>');
			return false;
		}
		
		$.post("/xhttp_calls/get_sections_by_classs.php", {SelectedClassID:ClassID}, function(data) {
			ResultArray = data.split("|*****|");
			
			if (ResultArray[0] == 'error')
			{
				$('#SectionID').html('<option value="0">-- Select --</option>');
				alert (ResultArray[1]);
				return false;
			}

			$('#SectionID').html('<option value="0">-- Select --</option>' + ResultArray[1]);
		});
	});
	
	$('#SectionID').change(function(){
		var ClassSectionID = $(this).val();
		
		$.LoadingOverlay("show");
		
			var form_data = new FormData();
			form_data.append('UserType', 'Student');
			form_data.append('SelectedClassSectionID', ClassSectionID);
			
			GetUserList(form_data);
	});
});

function GetUserList($param)
{
	$.ajax({
		url: '/xhttp_calls/school_administration/get_user_list_sms.php',
		dataType: 'text',
		cache: false,
		contentType: false,
		processData: false,
		data: $param, 
		type: 'post',
		success: function(response){
			ResultArray = response.split("|*****|");
			$.LoadingOverlay("hide");

			if (ResultArray[0] == 'error')
			{
				alert (ResultArray[1]);
				return false;
			}
			else
			{
				$('.user-list-container').html(ResultArray);
			}
		}
	});
}
</script>
</body>
</html>
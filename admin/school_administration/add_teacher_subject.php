<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once('../../classes/school_administration/class.subject_master.php');
require_once("../../classes/school_administration/class.branch_staff.php");

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
	header('location:../unauthorized_login_admin.php');
	exit;
}
catch (Exception $e)
{
	header('location:../unauthorized_login_admin.php');
	exit;
}
// END OF 1. //
if ($LoggedUser->HasPermissionForTask(TASK_ADD_TEACHER_SUBJECTS) !== true)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}

$UserMenusArray = array();
$UserMenusArray = $LoggedUser->GetUserMenus();

if (!is_array($UserMenusArray) || count($UserMenusArray) <= 0)
{
	//header('location:../logout.php');
	//exit;
}	

$StaffCategory = array();
$StaffCategory = array('Teaching' => 'Teaching Staff', 'NonTeaching' => 'Non Teaching Staff');

$AllBranchStaffList = array();
$BranchStaffAssignedSubjects = array();

$AllMasterSubjects = array();
$AllMasterSubjects = SubjectMaster::GetActiveSubjectMasters();

$HasErrors = false;

$Clean = array();
$Clean['Process'] = 0;

$Clean['StaffCategory']  = '';
$Clean['StaffCategory']  = key($StaffCategory);

$AllBranchStaffList = BranchStaff::GetActiveBranchStaff($Clean['StaffCategory']);

$Clean['BranchStaffID'] = 1;
$Clean['AssignedSubjects'] = array();

if (isset($_POST['hdnProcess']))
{
	$Clean['Process'] = (int) $_POST['hdnProcess'];
}
elseif(isset($_GET['Process']))
{
	$Clean['Process'] = (int) $_GET['Process'];
}
switch ($Clean['Process'])
{
	case 1:
		if (isset($_POST['hdnStaffCategory']))
		{
			$Clean['StaffCategory'] = strip_tags(trim($_POST['hdnStaffCategory']));
		}

		if (isset($_POST['hdnBranchStaffID']))
		{
			$Clean['BranchStaffID'] = (int) $_POST['hdnBranchStaffID'];
		}

		if ($Clean['BranchStaffID'] <= 0)
		{
		    header('location:/admin/error.php');
		    exit;
		}

		if (isset($_POST['chkAssignedSubjects']) && is_array($_POST['chkAssignedSubjects']))
		{
			$Clean['AssignedSubjects'] = $_POST['chkAssignedSubjects'];
		}

		$NewRecordValidator = new Validator();

		$NewRecordValidator->ValidateInSelect($Clean['StaffCategory'], $StaffCategory, 'Unknown error, please try again.');

		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}

		$AllBranchStaffList = BranchStaff::GetActiveBranchStaff($Clean['StaffCategory']);

		$NewRecordValidator->ValidateInSelect($Clean['BranchStaffID'], $AllBranchStaffList, 'Unknown error, please try again.');

		if(count($Clean['AssignedSubjects']) == 0)
		{
			$NewRecordValidator->AttachTextError('please select subjects');
			$HasErrors = true;
			break;
		}

		foreach ($Clean['AssignedSubjects'] as $SubjectID => $Value) 
		{
			$NewRecordValidator->ValidateInSelect($SubjectID, $AllMasterSubjects, 'Unknown error, please try again.');		
		}

		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}

		$AssignedSubjectsToCurrrentBranchStaff = new BranchStaff($Clean['BranchStaffID']);

		$AssignedSubjectsToCurrrentBranchStaff->SetBranchStaffSubjectList($Clean['AssignedSubjects']);
		$AssignedSubjectsToCurrrentBranchStaff->SetCreateUserID($LoggedUser->GetUserID());

		if (!$AssignedSubjectsToCurrrentBranchStaff->SaveBranchStaffSubjects())
		{
			$NewRecordValidator->AttachTextError(ProcessErrors($AssignedSubjectsToCurrrentBranchStaff->GetLastErrorCode()));
			$HasErrors = true;
			break;
		}

		header('location:add_teacher_subject.php?Mode=ED&Process=7&BranchStaffID=' . $Clean['BranchStaffID']);
		exit;
		break;

	case 7:
		if (isset($_POST['drdStaffCategory']))
		{
			$Clean['StaffCategory'] = strip_tags(trim($_POST['drdStaffCategory']));
		}

		if (isset($_POST['drdBranchStaffID']))
		{
			$Clean['BranchStaffID'] = (int) $_POST['drdBranchStaffID'];
		}
		else if (isset($_GET['BranchStaffID']))
		{
			$Clean['BranchStaffID'] = (int) $_GET['BranchStaffID'];
		}
		
		$NewRecordValidator = new Validator();

		$NewRecordValidator->ValidateInSelect($Clean['StaffCategory'], $StaffCategory, 'Unknown error, please try again.');

		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}
		
		$AllBranchStaffList = BranchStaff::GetActiveBranchStaff($Clean['StaffCategory']);

		$NewRecordValidator->ValidateInSelect($Clean['BranchStaffID'], $AllBranchStaffList, 'Unknown error, please try again.');

		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}

		$CurrentBranchStaffSubjects =  new BranchStaff($Clean['BranchStaffID']);
		$CurrentBranchStaffSubjects->FillBranchStaffSubjects();

		$BranchStaffAssignedSubjects = $CurrentBranchStaffSubjects->GetBranchStaffSubjectList();
		break;		
}

require_once('../html_header.php');
?>
<title>Set Branch Staff Subject</title>
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
                    <h1 class="page-header">Set Branch Staff Subjects</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="SearchTeacherSubject" action="add_teacher_subject.php" method="post">
            	<div class="panel panel-default">
                    <div class="panel-heading">
                       Branch Staff Subjects Details
                    </div>
                    <div class="panel-body">
<?php
						if ($HasErrors == true)
						{
							echo $NewRecordValidator->DisplayErrors();
						}
?>                    	
						<div class="form-group">
	                        <label for="StaffCategory" class="col-lg-2 control-label">Staff Category</label>
	                        <div class="col-lg-4">
	                            <select class="form-control" name="drdStaffCategory" id="StaffCategory">
<?php
	                                    foreach ($StaffCategory as $Key => $StaffCategoryDetails)
	                                    {
?>
	                                        <option <?php echo ($Key == $Clean['StaffCategory'] ? 'selected="selected"' : ''); ?> value="<?php echo $Key; ?>"><?php echo $StaffCategoryDetails; ?></option>
<?php
	                                    }

?>
	                            </select>
	                        </div>
	                    </div>	
                    	<div class="form-group">
                            <label for="BranchStaffID" class="col-lg-2 control-label">Staff List</label>
                            <div class="col-lg-4">
                            	<select class="form-control" name="drdBranchStaffID" id="BranchStaffID">
<?php
									foreach ($AllBranchStaffList as $BranchStaffID => $BranchStaffDetails)
									{
?>
										<option <?php echo ($BranchStaffID == $Clean['BranchStaffID'] ? 'selected="selected"' : ''); ?> value="<?php echo $BranchStaffID; ?>"><?php echo $BranchStaffDetails['FirstName'] .' '. $BranchStaffDetails['LastName']; ?></option>
<?php
									}
?>
                            	</select>
                            </div>
                        </div> 
                        <div class="form-group">
						    <div class="col-sm-offset-2 col-lg-10">
						    	<input type="hidden" name="hdnProcess" value="7"/>
						        <button type="submit" class="btn btn-primary">View Subjects</button>
						    </div>
						</div>
                    </div>
                </div>
            </form>
<?php
		if($Clean['Process'] == 7 && $HasErrors == false)
		{	
?>
			 <form class="form-horizontal" name="AddTeacherSubject" action="add_teacher_subject.php" method="post">
            	<div class="panel panel-default">
                    <div class="panel-heading">
                       Teacher Subjects Details
                    </div>
                    <div class="panel-body">
<?php
				$counter = 1;

?>
					<div class="col-lg-12">
                        <table width="100%" class="table table-striped table-bordered table-hover">
                            <thead>
                               <tr>
                                    <th>S. No</th>
                                    <th>Subject Name</th>

                                    <th><input type="checkbox" <?php echo (count($BranchStaffAssignedSubjects) == count($AllMasterSubjects) ? 'checked="checked"' : '');  ?> id="CheckAll" name="CheckAll" />&nbsp;<label for="CheckAll" class="control-label">Clear All</label></th>	
                                </tr>
                            </thead>
                            <tbody>
<?php
								foreach ($AllMasterSubjects as $SubjectID => $SubjectName) 
								{
?>
								  	<tr>
									  <td><?php echo $counter++;?></td>
									  <td><?php echo $SubjectName; ?></td>
									  <td><?php echo'<input type="checkbox" class="AssignedSubjects"' . (in_array($SubjectID, $BranchStaffAssignedSubjects) ? 'checked="checked"' : '') . ' name="chkAssignedSubjects[' . $SubjectID . ']" value="1"/>' ?></td>
								  	</tr>
<?php
								}
?>
							</tbody>
						</table>
						<div class="form-group">
						    <div class="col-sm-offset-2 col-lg-10">
						    	<input type="hidden" name="hdnBranchStaffID" value="<?php echo $Clean['BranchStaffID']; ?>"/>
						    	<input type="hidden" name="hdnStaffCategory" value="<?php echo $Clean['StaffCategory']; ?>"/>
						    	<input type="hidden" name="hdnProcess" value="1" />
						        <button type="submit" class="btn btn-primary">Save</button>
						    </div>
						</div>
<?php							
		}
?>					</div>
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
<script type="text/javascript">
$(function()
	{	
		$('#StaffCategory').change(function()
		{
			StaffCategory = $(this).val();
			
			if (StaffCategory <= 0)
			{
				$('#BranchStaffID').html('<option value="0">Select Section</option>');
				return;
			}
			
			$.post("/xhttp_calls/get_branch_staff_by_staff_category.php", {SelectedStaffCategory:StaffCategory}, function(data)
			{
				ResultArray = data.split("|*****|");
				
				if (ResultArray[0] == 'error')
				{
					alert(ResultArray[1]);
				}
				else
				{
					$('#BranchStaffID').html(ResultArray[1]);
				}
			 });
		});

		$('#CheckAll').click(function()
		{
			if ($(this).is(':checked'))
	        {
	           $(".AssignedSubjects").attr('checked', true);
	        }
	        else
	        {
	        	$(".AssignedSubjects").attr('checked', false);
	        }
		});
	});
</script>
</body>
</html>
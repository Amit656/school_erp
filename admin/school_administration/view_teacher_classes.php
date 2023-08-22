<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once('../../classes/school_administration/class.classes.php');
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
if ($LoggedUser->HasPermissionForTask(TASK_VIEW_TEACHER_CLASSES) !== true)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}

$StaffCategory = array();
$StaffCategory = array('Teaching' => 'Teaching Staff', 'NonTeaching' => 'Non Teaching Staff');

$AllClasses = array();
$AllClasses = AddedClass::GetActiveClasses();

$AllBranchStaffList = array();
$TeacherApplicableClasses = array();

$HasErrors = false;

$Clean = array();
$Clean['Process'] = 0;

$Clean['StaffCategory'] = '';
$Clean['StaffCategory'] = key($StaffCategory);

$AllBranchStaffList = BranchStaff::GetActiveBranchStaff($Clean['StaffCategory']);

$Clean['BranchStaffID'] = 0;
$Clean['AssignedTeacherClasses'] = array();

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
	case 7:
		if (isset($_POST['drdStaffCategory']))
		{
			$Clean['StaffCategory'] = strip_tags(trim($_POST['drdStaffCategory']));
		}		
		if (isset($_POST['drdBranchStaffID']))
		{
			$Clean['BranchStaffID'] = (int) $_POST['drdBranchStaffID'];
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

		$CurrentBranchStaffClasses = New BranchStaff($Clean['BranchStaffID']);
		$CurrentBranchStaffClasses->FillAssignedClasses();

		$Clean['AssignedTeacherClasses'] = $CurrentBranchStaffClasses->GetAssignedClasses();
		$TeacherApplicableClasses = $CurrentBranchStaffClasses->GetTeacherApplicableClasses($Clean['BranchStaffID']);
		break;
}

require_once('../html_header.php');
?>
<title>View Teacher Classes</title>
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
                    <h1 class="page-header">View Teacher Classes</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="SearchTeacherSubject" action="view_teacher_classes.php" method="post">
            	<div class="panel panel-default">
                    <div class="panel-heading">
                       View Teacher Subjects 
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
	                                    foreach ($StaffCategory as $Key => $StaffCategoryName)
	                                    {
?>
	                                        <option <?php echo ($Key == $Clean['StaffCategory'] ? 'selected="selected"' : ''); ?> value="<?php echo $Key; ?>"><?php echo $StaffCategoryName; ?></option>
<?php
	                                    }
?>
	                            </select>
	                        </div>
	                    </div>
                    	<div class="form-group">
                            <label for="BranchStaffID" class="col-lg-2 control-label">BranchStaff List</label>
                            <div class="col-lg-4">
                            	<select class="form-control"  name="drdBranchStaffID" id="BranchStaffID">
<?php
									foreach ($AllBranchStaffList as $BranchStaffID => $BranchStaffDetails)
									{
?>
										<option <?php echo ($BranchStaffID == $Clean['BranchStaffID'] ? 'selected="selected"' : ''); ?> value="<?php echo $BranchStaffID; ?>"><?php echo $BranchStaffDetails['FirstName'] .' ' . $BranchStaffDetails['LastName']; ?></option>
<?php
									}

?>
                            	</select>
                            </div>
                        </div> 
                        <div class="form-group">
						    <div class="col-sm-offset-2 col-lg-10">
						    	<input type="hidden" name="hdnProcess" value="7"/>
						        <button type="submit" class="btn btn-primary">View Classes</button>
						    </div>
						</div>
                    </div>
                </div>
            </form>
<?php
		if($Clean['Process'] == 7 && $HasErrors == false)
		{	
?>
            	<div class="panel panel-default">
                    <div class="panel-heading">
                       Teacher Classes
                    </div>
                    <div class="panel-body">
						<div class="col-lg-12">
                            <table width="100%" class="table table-striped table-bordered table-hover">
                                <thead>
                                   <tr>
                                        <th>S. No</th>
                                        <th>Class Name</th>
                                    </tr>
                                </thead>
                                <tbody>
<?php
									$counter = 1;

									foreach ($Clean['AssignedTeacherClasses'] as $ClassID => $ClassName) 
									{
?>
									  	<tr>
										  <td><?php echo $counter++;?></td>
										  <td><?php echo 'Class '. $ClassName; ?></td>
									  	</tr>
<?php
									}
?>
								</tbody>
							</table>
<?php
									
		}
?>					</div>
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
<script type="text/javascript">
	$(function()
		{	
			$('#StaffCategory').change(function()
			{
				StaffCategory = $(this).val();
				
				if (StaffCategory <= 0)
				{
					$('#BranchStaffID').html('<option value="0">Select Section</option>');
					return false;
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
		});
</script>
</body>
</html>
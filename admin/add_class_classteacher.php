<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once('../../classes/school_administration/class.classes.php');
require_once("../../classes/school_administration/class.branch_staff.php");
require_once("../../classes/school_administration/class.class_classteachers.php");

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
if ($LoggedUser->HasPermissionForTask(TASK_ADD_CLASS_TEACHER) !== true)
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

$AllAssignedClassList = array();
$ClassSectionsList =  array();
$AllBranchStaffList = array();

$HasErrors = false;

$Clean = array();
$Clean['Process'] = 0;

$Clean['StaffCategory'] = 'Teaching';
$Clean['BranchStaffID'] = 0;

if (isset($_POST['hdnBranchStaff']))
{
	$Clean['BranchStaffID'] = (int) $_POST['hdnBranchStaff'];

	if ($Clean['BranchStaffID'] <= 0)
	{
		header('location:/admin/error.php');
		exit;
	}
}

$Clean['ClassID'] = 0;
$Clean['ClassSectionID'] = 0;

$AllBranchStaffList = BranchStaff::GetActiveBranchStaff($Clean['StaffCategory']);

if (isset($_POST['hdnProcess']))
{
	$Clean['Process'] = (int) $_POST['hdnProcess'];
}
switch ($Clean['Process'])
{
	case 1:
		if (isset($_POST['drdClass']))
		{
			$Clean['ClassID'] =  (int) $_POST['drdClass'];
		}
			
		if (isset($_POST['drdClassSection']))
		{
			$Clean['ClassSectionID'] = (int) $_POST['drdClassSection'];
		}

		if (isset($_POST['hdnStaffCategory']))
		{
			$Clean['StaffCategory'] = strip_tags(trim($_POST['hdnStaffCategory']));
		}

		$CurrentBranchStaffClasses = New BranchStaff($Clean['BranchStaffID']);
		$CurrentBranchStaffClasses->FillAssignedClasses();

		$AllAssignedClassList = $CurrentBranchStaffClasses->GetAssignedClasses();
		
		$NewRecordValidator = new Validator();

		$NewRecordValidator->ValidateInSelect($Clean['ClassID'], $AllAssignedClassList, 'Unknown error, please try again1.');
		$NewRecordValidator->ValidateInSelect($Clean['StaffCategory'], $StaffCategory, 'Unknown error, please try again.');

		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}

		$ClassSectionsList = AddedClass::GetClassSections($Clean['ClassID']);
		$AllBranchStaffList = BranchStaff::GetActiveBranchStaff($Clean['StaffCategory']);

		$NewRecordValidator->ValidateInSelect($Clean['ClassSectionID'], $ClassSectionsList, 'Unknown error, please try again.');
		$NewRecordValidator->ValidateInSelect($Clean['BranchStaffID'], $AllBranchStaffList, 'Unknown error, please try again.');
		
		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}

		$NewAddedClassClassteacher = new ClassClassteacher();

		$NewAddedClassClassteacher->SetClassSectionID($Clean['ClassSectionID']);
		$NewAddedClassClassteacher->SetBranchStaffID($Clean['BranchStaffID']);

		$NewAddedClassClassteacher->SetCreateUserID($LoggedUser->GetUserID());

		if ($NewAddedClassClassteacher->RecordExists())
		{
			$NewRecordValidator->AttachTextError('Already class teacher assigned to current teacher');
			$HasErrors = true;
			break;
		}

		if (!$NewAddedClassClassteacher->Save())
		{
			$NewRecordValidator->AttachTextError(ProcessErrors($NewAddedClassClassteacher->GetLastErrorCode()));
			$HasErrors = true;
			break;
		}

		header('location:class_classteachers_list.php');
		exit;
		break;

	case 7:
		if (isset($_POST['drdStaffCategory']))
		{
			$Clean['StaffCategory'] = strip_tags(trim($_POST['drdStaffCategory']));
		}
			
		if (isset($_POST['drdBranchStaff']))
		{
			$Clean['BranchStaffID'] = (int) $_POST['drdBranchStaff'];
		}
		
		$NewRecordValidator = new Validator();

		$NewRecordValidator->ValidateInSelect($Clean['StaffCategory'], $StaffCategory, 'Unknown error, please try again1.');

		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}

		$AllBranchStaffList = BranchStaff::GetActiveBranchStaff($Clean['StaffCategory']);
		$NewRecordValidator->ValidateInSelect($Clean['BranchStaffID'], $AllBranchStaffList, 'Unknown error, please try again2.');

		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}

		$CurrentBranchStaffClasses = New BranchStaff($Clean['BranchStaffID']);
		$CurrentBranchStaffClasses->FillAssignedClasses();

		$AllAssignedClassList = $CurrentBranchStaffClasses->GetAssignedClasses();
		$Clean['ClassID'] = key($AllAssignedClassList);

		$ClassSectionsList = AddedClass::GetClassSections($Clean['ClassID']);
		break;
}

require_once('../html_header.php');
?>
<title>Add Class Teacher</title>
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
                    <h1 class="page-header">Add Class Teacher</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="AddClassTeacher" action="add_class_classteacher.php" method="post">
            	<div class="panel panel-default">
                    <div class="panel-heading">
                       Class Teacher Details
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
	                        <label for="BranchStaffID" class="col-lg-2 control-label">BranchStaff List</label>
                            <div class="col-lg-4">
                            	<select class="form-control" name="drdBranchStaff" id="BranchStaffID">
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
						        <button type="submit" class="btn btn-primary">View Assigned Classes</button>
						    </div>
						</div>
                    </div>
                </div>
            </form>
<?php
		if($Clean['Process'] == 7)
		{	
?>
			<form class="form-horizontal" name="AddClassTeacher" action="add_class_classteacher.php" method="post">
            	<div class="panel panel-default">
                    <div class="panel-heading">
                       Assign Class Teacher To Class
                    </div>
                    <div class="panel-body">
<?php
						if (count($AllAssignedClassList) > 0) 
						{
?>
						<div class="form-group">
	                        <label for="ClassID" class="col-lg-2 control-label">Assigned Classes</label>
	                        <div class="col-lg-4">
	                            <select class="form-control" name="drdClass" id="ClassID">
<?php
								foreach ($AllAssignedClassList as $ClassID => $ClassName)
                                {
?>
                                    <option value="<?php echo $ClassID; ?>" <?php echo ($ClassID == $Clean['ClassID'] ? 'selected="selected"' : ''); ?> ><?php echo $ClassName; ?></option>
<?php
                                }
								                             
?>
	                            </select>
	                        </div>
	                        <label for="ClassSections" class="col-lg-2 control-label">Class Sections</label>
	                        <div class="col-lg-4">
	                            <select class="form-control" name="drdClassSection" id="SectionID">
<?php
                                if (is_array($ClassSectionsList) && count($ClassSectionsList) > 0)
                                {
                                    foreach ($ClassSectionsList as $ClassSectionID => $SectionName) 
                                    {
                                        echo '<option '.($Clean['ClassSectionID'] == $ClassSectionID ? 'selected="selected"' : '') .' value="' . $ClassSectionID . '">' . $SectionName . '</option>' ;
                                    }
                                }
?>
								</select>
	                        </div>
	                    </div>
<?php
						}
						else
						{
							echo'<script>alert("No Classes Assiged To Current Branch Staff");</script>';
						}  
?>
						<div class="form-group">
						    <div class="col-sm-offset-2 col-lg-10">
						    	<input type="hidden" name="hdnBranchStaff" value="<?php echo $Clean['BranchStaffID']; ?>"/>
						    	<input type="hidden" name="hdnProcess" value="1"/>
						        <button type="submit" class="btn btn-primary">Save</button>
						    </div>
						</div>
					</div>
				</div>
			</form>
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

		$('#ClassID').change(function(){
		var ClassID = parseInt($(this).val());
		
		if (ClassID <= 0)
		{
			$('#SectionID').html('<option value="0">Select Section</option>');
			return;
		}
		
		$.post("/xhttp_calls/get_sections_by_classs.php", {SelectedClassID:ClassID}, function(data){
		
			ResultArray = data.split("|*****|");
			
			if (ResultArray[0] == 'error')
			{
				alert (ResultArray[1]);
				return false;
			}
			else
			{
				$('#SectionID').html(ResultArray[1]);
			}
		 });
	});
	});
</script>
</body>
</html>
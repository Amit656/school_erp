<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once("../../classes/school_administration/class.classes.php");
require_once('../../classes/school_administration/class.class_sections.php');
require_once('../../classes/school_administration/class.section_master.php');

require_once('../../classes/school_administration/class.students.php');
require_once('../../classes/school_administration/class.student_details.php');

require_once("../../classes/academic_supervision/class.achievements_master.php");
require_once("../../classes/academic_supervision/class.achievements_students.php");

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

if ($LoggedUser->HasPermissionForTask(TASK_ALLOT_ACHIEVEMENT_TO_STUDENT) !== true)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}

$ClassList =  array();
$ClassList = AddedClass::GetActiveClasses();

$ActiveAchievementMasters = array();
$ActiveAchievementMasters = AchievementMaster::GetActiveAchievementMasters();

$ClassSectionsList =  array();
$StudentsList = array();

$HasErrors = false;

$Clean = array();
$Clean['Process'] = 0;

$Clean['ClassID'] = 0;
$Clean['ClassSectionID'] = 0;
$Clean['AchievementID'] = 0;

$Clean['StudentIDList'] = array();
$Clean['RemoveStudentIDList'] = array();

if (isset($_POST['hdnProcess']))
{
	$Clean['Process'] = (int) $_POST['hdnProcess'];
}
switch ($Clean['Process'])
{
	case 1:
        if (isset($_POST['hdnAchievementID'])) 
        {
            $Clean['AchievementID'] = (int) $_POST['hdnAchievementID'];
        }
		if (isset($_POST['hdnClassID'])) 
		{
			$Clean['ClassID'] = (int) $_POST['hdnClassID'];
		}
		if (isset($_POST['hdnClassSectionID'])) 
        {
            $Clean['ClassSectionID'] = (int) $_POST['hdnClassSectionID'];
        }

        if (isset($_POST['chkStudentIDList']) && is_array($_POST['chkStudentIDList']))
        {
            $Clean['StudentIDList'] = $_POST['chkStudentIDList'];
        }
		
		$NewRecordValidator = new Validator();

		if ($NewRecordValidator->ValidateInSelect($Clean['ClassID'], $ClassList, 'Please select a valid class.')) 
		{
			$ClassSectionsList = ClassSections::GetClassSections($Clean['ClassID']);

			if ($NewRecordValidator->ValidateInSelect($Clean['ClassSectionID'], $ClassSectionsList, 'Please select a valid section.')) 
			{
				$StudentsList = StudentDetail::GetStudentsByClassSectionID($Clean['ClassSectionID']);

                if (count($Clean['StudentIDList']) <= 0) 
                {
                    $NewRecordValidator->AttachTextError('Please select atleast one student.');   
                    $HasErrors = true;
                    break;
                }

                $AllreadyPresentStudents = AchievementsStudent::FillAchievementsRecordsToStudent($Clean['AchievementID'], $Clean['ClassSectionID']);
                
                foreach ($AllreadyPresentStudents as $AchievementsStudentID => $StudentID)
                {
                    if (!in_array($StudentID, $Clean['StudentIDList'])) 
                    {
                        $Clean['RemoveStudentIDList'][$AchievementsStudentID] = $StudentID;
                    }
                }
                
                foreach ($Clean['StudentIDList'] as $StudentID => $Student)
                {
                    $NewRecordValidator->ValidateInSelect($StudentID, $StudentsList, 'Unknown error, please try again.');

                    if (in_array($StudentID, $AllreadyPresentStudents)) 
                    {
                        unset($Clean['StudentIDList'][$StudentID]);
                    }
                }
			}
		}
        
		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}
				
		$NewAchievementsStudent = new AchievementsStudent();
				
		$NewAchievementsStudent->SetAchievementID($Clean['AchievementID']);

		$NewAchievementsStudent->SetIsActive(1);
		$NewAchievementsStudent->SetCreateUserID($LoggedUser->GetUserID());

        $NewAchievementsStudent->SetStudentIDList($Clean['StudentIDList']);
        $NewAchievementsStudent->SetRemoveStudentIDList($Clean['RemoveStudentIDList']);

		if (!$NewAchievementsStudent->Save())
		{
			$NewRecordValidator->AttachTextError(ProcessErrors($NewAchievementsStudent->GetLastErrorCode()));
			$HasErrors = true;
			break;
		}
		
		header('location:add_achievements_students.php?Mode=AS');
		exit;
	break;

    case 7:
        if (isset($_POST['drdAchievement'])) 
        {
            $Clean['AchievementID'] = (int) $_POST['drdAchievement'];
        }

        if (isset($_POST['drdClass'])) 
        {
            $Clean['ClassID'] = (int) $_POST['drdClass'];
        }

        if (isset($_POST['drdClassSection']))
        {
            $Clean['ClassSectionID'] = (int) $_POST['drdClassSection'];
        }
        
        $NewRecordValidator = new Validator();

        $NewRecordValidator->ValidateInSelect($Clean['AchievementID'], $ActiveAchievementMasters, 'Unknown error, please  select a valid achievement.');
        $NewRecordValidator->ValidateInSelect($Clean['ClassID'], $ClassList, 'Unknown error, please select a valid class.');

        $ClassSectionsList = ClassSections::GetClassSections($Clean['ClassID']);

        $NewRecordValidator->ValidateInSelect($Clean['ClassSectionID'], $ClassSectionsList, 'Unknown error, please select a valid section.');

        if ($NewRecordValidator->HasNotifications())
        {
            $HasErrors = true;
            break;
        }

        $Clean['StudentIDList'] = AchievementsStudent::FillAchievementsRecordsToStudent($Clean['AchievementID'], $Clean['ClassSectionID']);

        $StudentsList = StudentDetail::GetStudentsByClassSectionID($Clean['ClassSectionID']);        
    break; 
}

$LandingPageMode = '';
if (isset($_GET['Mode']))
{
    $LandingPageMode = $_GET['Mode'];
}

require_once('../html_header.php');
?>
<title>Achievements To Students</title>
<link href="vendor/jquery-ui/jquery-ui.min.css" rel="stylesheet">
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
                    <h1 class="page-header">Achievements To Students</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
            <form class="form-horizontal" name="AddAchievementsStudent" action="add_achievements_students.php" method="post">
            	<div class="panel panel-default">
                    <div class="panel-heading">
                        <strong>Enter Achievements Student Details</strong>
                    </div>
                    <div class="panel-body">
<?php
						if ($HasErrors == true)
						{
							echo $NewRecordValidator->DisplayErrors();
						}
						else if ($LandingPageMode == 'AS')
	                    {
	                        echo '<div class="alert alert-success">Record saved successfully.</div>';
	                    }
?>                    
                        <div class="form-group">
                            <label for="Achievement" class="col-lg-2 control-label">Achievement</label>
                            <div class="col-lg-4">
                                <select class="form-control" name="drdAchievement" id="Achievement">
<?php
                                if (is_array($ActiveAchievementMasters) && count($ActiveAchievementMasters) > 0)
                                {
                                    foreach($ActiveAchievementMasters as $AchievementID => $AchievementName)
                                    {
                                        echo '<option ' . (($Clean['AchievementID'] == $AchievementID) ? 'selected="selected"' : '') . ' value="' . $AchievementID . '">' . $AchievementName . '</option>';
                                    }
                                }
?>
                                </select>
                            </div>
                        </div>
                    	<div class="form-group">
                            <label for="Class" class="col-lg-2 control-label">Class List</label>
                            <div class="col-lg-4">
                                <select class="form-control"  name="drdClass" id="Class">
                                    <option  value="0" >-- Select Class --</option>
<?php
                                    if (is_array($ClassList) && count($ClassList) > 0)
                                    {
                                        foreach ($ClassList as $ClassID => $ClassName)
	                                    {
	?>
	                                        <option <?php echo ($ClassID == $Clean['ClassID'] ? 'selected="selected"' : ''); ?> value="<?php echo $ClassID; ?>"><?php echo $ClassName; ?></option>
	<?php
	                                    }
                                    }
?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="ClassSection" class="col-lg-2 control-label">Section</label>
                            <div class="col-lg-4">
                                <select class="form-control" name="drdClassSection" id="ClassSection">
                                    <option value="0">-- Select Section --</option>
<?php
                                        if (is_array($ClassSectionsList) && count($ClassSectionsList) > 0)
                                        {
                                            foreach ($ClassSectionsList as $ClassSectionID => $SectionName) 
                                            {
                                                echo '<option ' . ($Clean['ClassSectionID'] == $ClassSectionID ? 'selected="selected"' : '') . ' value="' . $ClassSectionID . '">' . $SectionName . '</option>' ;
                                            }
                                        }
?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
	                        <div class="col-sm-offset-2 col-lg-10">
	                        	<input type="hidden" name="hdnProcess" value="7" />
								<button type="submit" class="btn btn-primary"><i class="fa fa-search"></i>&nbsp;Search</button>
	                        </div>
                      	</div>
                    </div>
                </div>
            </form>

<?php
if($Clean['Process'] == 7 && count($StudentsList) > 0)
{   
?>
             <form class="form-horizontal" name="AddCClassAttendence" action="add_achievements_students.php" method="post">
                <div class="panel panel-default">
                    <div class="panel-heading">
                       <strong>Studnet List</strong>
                    </div>
                    <div class="panel-body">
                        <div class="row" id="RecordTable">
                            <div class="col-lg-12">
                                <table width="100%" class="table table-striped table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th>S. No.</th>
                                            <th>Student Name</th>                                        
                                            <th>Roll No.</th>
                                            <th>Select</th>
                                        </tr>
                                    </thead>
                                    <tbody>
<?php
                                    if (is_array($StudentsList) && count($StudentsList) > 0)
                                    {
                                        $Counter = 0;
                                        foreach ($StudentsList as $StudentID => $StudentDetails)
                                        {
?>
                                                <tr>
                                                    <td><?php echo ++$Counter; ?></td>
                                                    <td><?php echo $StudentDetails['FirstName'] . ' ' . $StudentDetails['LastName']; ?></td>
                                                    <td><?php echo $StudentDetails['RollNumber']; ?></td>
                                                    <td>
                                                        <?php echo '<input class="custom-radio" type="checkbox" id="StudentID' . $StudentID . '" name="chkStudentIDList[' . $StudentID . ']" value="' . $StudentID . '" '. (in_array($StudentID, $Clean['StudentIDList']) ? 'checked="checked"' : '') . ' />'; ?>
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
                        
                    <div class="row">
                        <div class="col-lg-12">                         
                            <div class="form-group">
                                <div class="col-sm-offset-2 col-lg-10">
                                    <input type="hidden" name="hdnAchievementID" value="<?php echo $Clean['AchievementID']; ?>" />
                                    <input type="hidden" name="hdnClassID" value="<?php echo $Clean['ClassID']; ?>" />
                                    <input type="hidden" name="hdnClassSectionID" value="<?php echo $Clean['ClassSectionID']; ?>" />
                                    <input type="hidden" name="hdnProcess" value="1" />
                                    <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i>&nbsp;Save</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
<?php
                        }

?>                          
                            
            </form>
        </div>
        <!-- /#page-wrapper -->

    </div>
    <!-- /#wrapper -->
<?php
require_once('../footer.php');
?>
<script type="text/javascript">
$(document).ready(function(){
	$('#Class').change(function(){

        var ClassID = parseInt($(this).val());
                
        if (ClassID <= 0)
        {
            $('#ClassSection').html('<option value="0">-- Select Section --</option>');
            return;
        }
        
        $.post("/xhttp_calls/get_sections_by_classs.php", {SelectedClassID:ClassID}, function(data)
        {
            ResultArray = data.split("|*****|");
            
            if (ResultArray[0] == 'error')
            {
                alert (ResultArray[1]);
                return false;
            }
            else
            {
                $('#ClassSection').html(ResultArray[1]);
            }
        });
    });
});
</script>
</body>
</html>
<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once("../../classes/school_administration/class.classes.php");
require_once('../../classes/school_administration/class.class_sections.php');

require_once('../../classes/school_administration/class.students.php');
require_once('../../classes/school_administration/class.student_details.php');

require_once("../../classes/academic_supervision/class.remarks.php");

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

if ($LoggedUser->HasPermissionForTask(TASK_ADD_STUDENT_REMARK) !== true)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}

$ClassList =  array();
$ClassList = AddedClass::GetActiveClasses();

$ClassSectionsList =  array();
$StudentsList = array();

$RemarkTypeList = array('Positive' => 'Positive', 'Negative' => 'Negative');

$HasErrors = false;

$Clean = array();
$Clean['Process'] = 0;

$Clean['ClassID'] = 0;
$Clean['ClassSectionID'] = 0;
$Clean['StudentID'] = 0;

$Clean['RemarkType'] = 'Positive';
$Clean['Remark'] = '';

if (isset($_POST['hdnProcess']))
{
	$Clean['Process'] = (int) $_POST['hdnProcess'];
}
switch ($Clean['Process'])
{
	case 1:
		if (isset($_POST['drdClass'])) 
		{
			$Clean['ClassID'] = (int) $_POST['drdClass'];
		}
		if (isset($_POST['drdClassSection'])) 
		{
			$Clean['ClassSectionID'] = (int) $_POST['drdClassSection'];
		}
		if (isset($_POST['drdStudent'])) 
		{
			$Clean['StudentID'] = (int) $_POST['drdStudent'];
		}
		if (isset($_POST['optRemarkType']))
		{
			$Clean['RemarkType'] = strip_tags(trim($_POST['optRemarkType']));
		}
		if (isset($_POST['txtRemark']))
		{
			$Clean['Remark'] = strip_tags(trim($_POST['txtRemark']));
		}
		
		$NewRecordValidator = new Validator();

		if ($NewRecordValidator->ValidateInSelect($Clean['ClassID'], $ClassList, 'Please select a valid class.')) 
		{
			$ClassSectionsList = ClassSections::GetClassSections($Clean['ClassID']);

			if ($NewRecordValidator->ValidateInSelect($Clean['ClassSectionID'], $ClassSectionsList, 'Please select a valid section.')) 
			{
				$StudentsList = StudentDetail::GetStudentsByClassSectionID($Clean['ClassSectionID']);

				$NewRecordValidator->ValidateInSelect($Clean['StudentID'], $StudentsList, 'Please select a valid student.');
			}
		}

		$NewRecordValidator->ValidateInSelect($Clean['RemarkType'], $RemarkTypeList, 'Unknown error, please try again.');
		$NewRecordValidator->ValidateStrings($Clean['Remark'], 'Remark is required and should be between 1 and 500 characters.', 1, 500);
		
		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}
				
		$NewRemark = new Remark();
				
		$NewRemark->SetStudentID($Clean['StudentID']);
		$NewRemark->SetRemarkType($Clean['RemarkType']);
		$NewRemark->SetRemark($Clean['Remark']);

		$NewRemark->SetIsActive(1);
		$NewRemark->SetCreateUserID($LoggedUser->GetUserID());

		if (!$NewRemark->Save())
		{
			$NewRecordValidator->AttachTextError(ProcessErrors($NewRemark->GetLastErrorCode()));
			$HasErrors = true;
			break;
		}
		
		header('location:add_remark.php?Mode=AS');
		exit;
	break;
}

$LandingPageMode = '';
if (isset($_GET['Mode']))
{
    $LandingPageMode = $_GET['Mode'];
}

require_once('../html_header.php');
?>
<title>Give Remark To Student</title>
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
                    <h1 class="page-header">Give Remark To Student</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
            <form class="form-horizontal" name="AddRemark" action="add_remark.php" method="post">
            	<div class="panel panel-default">
                    <div class="panel-heading">
                        Enter Remark Details
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
                            <label for="Class" class="col-lg-2 control-label">Class</label>
                            <div class="col-lg-3">
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
                            <label for="ClassSection" class="col-lg-2 control-label">Section</label>
                            <div class="col-lg-3">
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
                            <label for="Student" class="col-lg-2 control-label">Student</label>
                            <div class="col-lg-8">
                                <select class="form-control" name="drdStudent" id="Student">
                                    <option value="0">-- Select Student --</option>
<?php
                                        if (is_array($StudentsList) && count($StudentsList) > 0)
                                        {
                                            foreach ($StudentsList as $StudentID=>$StudentDetails)
                                            {
                                                echo '<option ' . ($Clean['StudentID'] == $StudentID ? 'selected="selected"' : '') . ' value="' . $StudentID . '">' . $StudentDetails['FirstName'] . ' ' . $StudentDetails['LastName'] . ' (' . $StudentDetails['RollNumber'] . ')</option>'; 
                                            }
                                        }
?>
                                </select>
                            </div>                                
                        </div>
                        <div class="form-group">
                            <label for="RemarkType" class="col-lg-2 control-label">RemarkType</label>
                            <div class="col-sm-4">
<?php
                            foreach($RemarkTypeList as $RemarkTypeID => $RemarkType)
                            {
?>                              
                                <label class="col-sm-6"><input class="custom-radio" type="radio" id="<?php echo $RemarkTypeID; ?>" name="optRemarkType" value="<?php echo $RemarkTypeID; ?>" <?php echo ($Clean['RemarkType'] == $RemarkTypeID ? 'checked="checked"' : ''); ?> >&nbsp;&nbsp;<?php echo $RemarkType; ?></label>            
<?php                                       
                            }
?>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="Remark" class="col-lg-2 control-label">Remark</label>
                            <div class="col-lg-8">
                                <textarea class="form-control"  id="Remark" name="txtRemark"><?php echo $Clean['Remark']; ?></textarea>
                            </div>
                        </div>
						                        
                        <div class="form-group">
	                        <div class="col-sm-offset-2 col-lg-10">
	                        	<input type="hidden" name="hdnProcess" value="1" />
								<button type="submit" class="btn btn-primary"><i class="fa fa-save"></i>&nbsp;Give Remark</button>
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
?>
<script type="text/javascript">
$(document).ready(function(){
	$('#Class').change(function(){

        var ClassID = parseInt($(this).val());
        $('#Student').html('<option value="0">-- Select Student --</option>');
        
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
                $('#ClassSection').html('<option value="0">-- Select Section --</option>' + ResultArray[1]);
            }
        });
    });

    $('#ClassSection').change(function(){

        var ClassSectionID = parseInt($(this).val());
        
        if (ClassSectionID <= 0)
        {
            $('#Student').html('<option value="0">-- Select Student --</option>');
            return;
        }
        
        $.post("/xhttp_calls/get_students_by_class_section.php", {SelectedClassSectionID:ClassSectionID}, function(data)
        {
            ResultArray = data.split("|*****|");
            
            if (ResultArray[0] == 'error')
            {
                alert (ResultArray[1]);
                return false;
            }
            else
            {
                $('#Student').html('<option value="0">-- Select Student --</option>' + ResultArray[1]);
            }
        });
    });
});
</script>
</body>
</html>
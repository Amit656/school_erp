<?php
require_once('../../classes/class.users.php');
require_once('../../classes/class.validation.php');
require_once('../../classes/class.authentication.php');

require_once('../../classes/school_administration/class.classes.php');
require_once('../../classes/school_administration/class.class_sections.php');

require_once('../../classes/examination/class.exam_types.php');
require_once('../../classes/examination/class.exams.php');

require_once('../../includes/global_defaults.inc.php');

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

if ($LoggedUser->HasPermissionForTask(TASK_ADD_EXAM) !== true)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}

$ExamTypeList =  array();
$ExamTypeList = ExamType::GetActiveExamTypes();

$ClassList =  array();
$ClassList = AddedClass::GetActiveClasses();

$ClassSubjectsList = array();
$ClassSectionsList = array();

$HasErrors = false;
$SearchErrors = false;

$Clean = array();
$Clean['Process'] = 0;

$Clean['ExamTypeID'] = 0;
$Clean['ClassID'] = 0;

$Clean['ExamName'] = '';
$Clean['IsOffline'] = 0;

$Clean['ClassSectionList'] = array();
$Clean['ClassSubjectList'] = array();
$Clean['MaximumMarkList'] = array();

$MaximumMarkDetails = array();

if (isset($_POST['hdnProcess']))
{
	$Clean['Process'] = (int) $_POST['hdnProcess'];
}
switch ($Clean['Process'])
{
	case 1:
		if (isset($_POST['hdnExamTypeID'])) 
		{
			$Clean['ExamTypeID'] = (int) $_POST['hdnExamTypeID'];
		}
		if (isset($_POST['hdnExamName'])) 
		{
			$Clean['ExamName'] = strip_tags(trim($_POST['hdnExamName']));
		}
		if (isset($_POST['hdnClassID'])) 
        {
            $Clean['ClassID'] = (int) $_POST['hdnClassID'];
        }
        if (isset($_POST['hdnIsOffline'])) 
        {
            $Clean['IsOffline'] = (int) $_POST['hdnIsOffline'];
        }
		if (isset($_POST['chkClassSection']) && is_array($_POST['chkClassSection']))
        {
            $Clean['ClassSectionList'] = $_POST['chkClassSection'];
        }
        if (isset($_POST['chkClassSubject']) && is_array($_POST['chkClassSubject']))
        {
            $Clean['ClassSubjectList'] = $_POST['chkClassSubject'];
        }
		if (isset($_POST['txtMaximumMarks']) && is_array($_POST['txtMaximumMarks']))
        {
            $Clean['MaximumMarkList'] = $_POST['txtMaximumMarks'];
        }

		$NewRecordValidator = new Validator();

		if ($NewRecordValidator->ValidateInSelect($Clean['ClassID'], $ClassList, 'Unknown error, please try again.')) 
		{
			$ClassSectionsList = ClassSections::GetClassSections($Clean['ClassID']);

            foreach ($Clean['ClassSectionList'] as $ClassSectionID => $ClassSectionID) 
            {
                $NewRecordValidator->ValidateInSelect($ClassSectionID, $ClassSectionsList, 'Unknown error, please try again.');
            }
            
            $SelectedAddedClass = new AddedClass($Clean['ClassID']);
            $SelectedAddedClass->FillAssignedSubjects();

            $ClassSubjectsList = $SelectedAddedClass->GetAssignedSubjects();

            foreach ($Clean['ClassSubjectList'] as $ClassSubjectID => $ClassSubjectID) 
            {
                $NewRecordValidator->ValidateInSelect($ClassSubjectID, $ClassSubjectsList, 'Unknown error, please try again.'); 

                if ($ClassSubjectsList[$ClassSubjectID]['SubjectMarksType'] == 'Grade') 
                {
                    $MaximumMarkDetails[$ClassSubjectID] = 0;
                    continue;
                }

                $NewRecordValidator->ValidateInteger($Clean['MaximumMarkList'][$ClassSubjectID], 'Please enter valid maximum marks of subject '. $ClassSubjectsList[$ClassSubjectID]['Subject'] .'.', 1); 
                $MaximumMarkDetails[$ClassSubjectID] = $Clean['MaximumMarkList'][$ClassSubjectID];
            }
		}

		$NewRecordValidator->ValidateInSelect($Clean['ExamTypeID'], $ExamTypeList, 'Please select a valid exam type.');	

        $NewRecordValidator->ValidateStrings($Clean['ExamName'], 'Exam name is required and should be between 1 and 100 characters.', 1, 100);
		
		if ($NewRecordValidator->HasNotifications())
		{
			$SearchErrors = true;
			break;
		}

        foreach ($Clean['ClassSubjectList'] as $ClassSubjectID => $ClassSubjectID) 
        {
            
        }
				
		$NewExam = new Exam();
				
		$NewExam->SetExamTypeID($Clean['ExamTypeID']);
        $NewExam->SetExamName($Clean['ExamName']);

		$NewExam->SetIsOffline($Clean['IsOffline']);

		$NewExam->SetCreateUserID($LoggedUser->GetUserID());

        $NewExam->SetClassSectionList($Clean['ClassSectionList']);
        $NewExam->SetMaximumMarkDetails($MaximumMarkDetails);

		if (!$NewExam->Save())
		{
			$NewRecordValidator->AttachTextError(ProcessErrors($NewExam->GetLastErrorCode()));
			$HasErrors = true;
			break;
		}
		
		header('location:exam_list.php?Mode=AS');
		exit;
	break;

    case 7:
        if (isset($_POST['drdExamType'])) 
        {
            $Clean['ExamTypeID'] = (int) $_POST['drdExamType'];
        }
        if (isset($_POST['txtExamName']))
        {
            $Clean['ExamName'] = strip_tags(trim($_POST['txtExamName']));
        }
        
        if (isset($_POST['drdClass'])) 
        {
            $Clean['ClassID'] = (int) $_POST['drdClass'];
        }
        
        if (isset($_POST['chkIsOffline']))
        {
            $Clean['IsOffline'] = 1;
        }

        $NewRecordValidator = new Validator();

        $NewRecordValidator->ValidateInSelect($Clean['ExamTypeID'], $ExamTypeList, 'Please select a valid exam type.'); 

        $NewRecordValidator->ValidateStrings($Clean['ExamName'], 'Exam name is required and should be between 3 and 100 characters.', 3, 100);
        $NewRecordValidator->ValidateInSelect($Clean['ClassID'], $ClassList, 'Please select a valid class.');
        
        if ($NewRecordValidator->HasNotifications())
        {
            $HasErrors = true;
            break;
        }
        
        $SelectedAddedClass = new AddedClass($Clean['ClassID']);
        $SelectedAddedClass->FillAssignedSubjects();

        $ClassSubjectsList = $SelectedAddedClass->GetAssignedSubjects();    

        $ClassSectionsList = ClassSections::GetClassSections($Clean['ClassID']);    
                
    break;
}

require_once('../html_header.php');
?>
<title>Add Exam</title>
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
                    <h1 class="page-header">Add Exam</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="AddExam" action="add_exam.php" method="post">
            	<div class="panel panel-default">
                    <div class="panel-heading">
                        Enter Exam Details
                    </div>
                    <div class="panel-body">
<?php
						if ($HasErrors == true)
						{
							echo $NewRecordValidator->DisplayErrorsInTable();
						}
?>                    
						<br>
                        <div class="form-group">
                            <label for="ExamType" class="col-lg-2 control-label">Exam Type</label>
                            <div class="col-lg-4">
                                <select class="form-control" name="drdExamType" id="ExamType">
                                        <option  value="0" >-- Select Exam Type --</option>
<?php
                                        if (is_array($ExamTypeList) && count($ExamTypeList) > 0)
                                        {
                                            foreach ($ExamTypeList as $ExamTypeID => $ExamType) 
                                            {
                                                echo '<option ' . ($Clean['ExamTypeID'] == $ExamTypeID ? 'selected="selected"' : '') . ' value="' . $ExamTypeID . '">' . $ExamType . '</option>' ;
                                            }
                                        }
?>
                                </select>
                            </div>
                            <label for="ExamName" class="col-lg-2 control-label">Exam Name</label>
                            <div class="col-lg-4">
                                <input class="form-control" type="text" maxlength="100" id="ExamName" name="txtExamName" value="<?php echo $Clean['ExamName']; ?>" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="Class" class="col-lg-2 control-label">Class</label>
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
                            <label for="IsOffline" class="col-lg-2 control-label">Is Offline</label>
                            <div class="col-lg-4" >
                                <label for="IsOffline" class="checkbox-inline" ><input type="checkbox" id="IsOffline" name="chkIsOffline" <?php echo ($Clean['IsOffline'] == 1) ? 'checked="checked"' : ''; ?> value="1" />  &nbsp;Yes</label>
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
        if ($Clean['Process'] == 7 && $HasErrors == false || $Clean['Process'] == 1)
        {
?>
            <!-- /.row -->
            <div class="row">
                <div class="col-lg-12">
                    <div class="panel panel-default">
                        <div class="panel-body">
                            <div>
<?php
                            if ($SearchErrors == true)
                            {
                                echo $NewRecordValidator->DisplayErrorsInTable();
                            }
?>
                            <form class="form-horizontal" name="AddExam" action="add_exam.php" method="post">
                                <div class="form-group">
                                    <label for="" class="col-lg-2 control-label">Select Section</label>
                                    <div class="col-lg-8">
                                        <label for="AllSection" class="checkbox-inline" ><input type="checkbox" id="AllSection" name="chkAllSection" <?php echo count($Clean['ClassSectionList']) == count($ClassSectionsList) ? 'checked="checked"' : ''; ?> value="" />  &nbsp;All</label>
<?php
                                    foreach ($ClassSectionsList as $ClassSectionID => $SectionName) 
                                    {
?>
                                        <label for="<?php echo $SectionName; ?>" class="checkbox-inline" ><input type="checkbox" class="check-all-section" id="<?php echo $SectionName; ?>" name="chkClassSection[<?php echo $ClassSectionID; ?>]" <?php echo in_array($ClassSectionID, $Clean['ClassSectionList']) ? 'checked="checked"' : ''; ?> value="<?php echo $ClassSectionID; ?>" />  &nbsp;<?php echo $SectionName?></label>
                                        
<?php
                                    }
?>
                                    </div>      
                                </div>

                                <div class="row" id="RecordTable">
                                    <div class="col-lg-12">
                                        <table width="100%" class="table table-striped table-bordered table-hover" id="DataTableRecords">
                                            <thead>
                                                <tr>
                                                    <th>S. No</th>
                                                    <th>
                                                        Subject &nbsp;&nbsp;<label for="AllSubject" class="checkbox-inline" ><input type="checkbox" id="AllSubject" name="chkAllSubject" <?php echo count($Clean['ClassSubjectList']) == count($ClassSubjectsList) ? 'checked="checked"' : ''; ?> value="" />  &nbsp;All</label>
                                                    </th>
                                                    <th>Maximum Marks</th>
                                                </tr>
                                            </thead>
                                            <tbody>
<?php
                                    if (is_array($ClassSubjectsList) && count($ClassSubjectsList) > 0)
                                    {
                                        $Counter = 0;

                                        foreach ($ClassSubjectsList as $ClassSubjectID => $ClassSubjectDetail)
                                        {
?>
                                            <tr>
                                                <td><?php echo ++$Counter; ?></td>
                                                <td>
                                                    <label for="<?php echo $ClassSubjectDetail['Subject']; ?>" class="checkbox-inline" >
                                                        <input type="checkbox" class="check-all-subject" id="<?php echo $ClassSubjectDetail['Subject']; ?>" name="chkClassSubject[<?php echo $ClassSubjectID; ?>]" <?php echo (in_array($ClassSubjectID
                                                        , $Clean['ClassSubjectList'])) ? 'checked="checked"' : ''; ?> value="<?php echo $ClassSubjectID; ?>" /> &nbsp;<?php echo $ClassSubjectDetail['Subject']; ?></label>
                                                </td>
                                                <td>
                                                    <input class="form-control marks" type="text" maxlength="3" id="MaximumMarks<?php echo $ClassSubjectID; ?>" name="txtMaximumMarks[<?php echo $ClassSubjectID; ?>]" value="<?php echo (array_key_exists($ClassSubjectID, $Clean['MaximumMarkList']) ? $Clean['MaximumMarkList'][$ClassSubjectID] : ''); ?>" <?php echo ($ClassSubjectDetail['SubjectMarksType'] == 'Grade'  ? 'disabled="disabled"' : ''); ?><?php echo ($ClassSubjectDetail['SubjectMarksType'] == 'Grade'  ? 'title="Grade Subject"' : ''); ?> />
                                                </td>
                                            </tr>
<?php
                                        }
                                    }
?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="form-group">
                                        <div class="col-sm-offset-2 col-lg-10">
                                            <input type="hidden" name="hdnExamTypeID" value="<?php echo $Clean['ExamTypeID'];?>" />
                                            <input type="hidden" name="hdnExamName" value="<?php echo $Clean['ExamName'];?>" />
                                            <input type="hidden" name="hdnClassID" value="<?php echo $Clean['ClassID'];?>" />
                                            <input type="hidden" name="hdnIsOffline" value="<?php echo $Clean['IsOffline'];?>" />
                                            <input type="hidden" name="hdnProcess" value="1" />
                                            <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i>&nbsp;Save</button>
                                        </div>
                                    </div>
                                </div>
                            </form>
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
<script src="../vendor/jquery-ui/jquery-ui.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.5.2/jquery.fancybox.min.js"></script>
<script type="text/javascript">
$(document).ready(function(){

	$('#Class').change(function(){

        var ClassID = parseInt($('#Class').val());
        $('#ClassSubject').html('<option value="0">-- Select Subject --</option>');
        
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

        var ClassID = parseInt($('#Class').val());
        $('#ClassSubject').html('<option value="0">-- Select Subject --</option>');
        
        if (ClassID <= 0)
        {
            $('#ClassSection').html('<option value="0">-- Select Section --</option>');
            return;
        }
        
        $.post("/xhttp_calls/get_subjects_by_class.php", {SelectedClassID:ClassID}, function(data)
        {
            ResultArray = data.split("|*****|");
            
            if (ResultArray[0] == 'error')
            {
                alert (ResultArray[1]);
                return false;
            }
            else
            {
                $('#ClassSubject').html(ResultArray[1]);
            }
        });
    });
    
    $('#AllSection').change(function(){
        if ($(this).prop("checked") == true)
        {
            $('.check-all-section').prop('checked',true);
        }
        else
        {
            $('.check-all-section').prop('checked',false);
        }
    });
    
    $('#AllSubject').change(function(){
        if ($(this).prop("checked") == true)
        {
            $('.check-all-subject').prop('checked',true);
        }
        else
        {
            $('.check-all-subject').prop('checked',false);
        }
    });
	
	$('.marks').keyup(function()
	{   
	   if ($(this).val().length >= 3) 
	   {
			$(this).closest('tr').next('tr').find('.marks').focus().select();
	   }
	});
});
</script>
</body>
</html>
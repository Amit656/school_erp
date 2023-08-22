<?php
require_once('../../classes/class.users.php');
require_once('../../classes/class.validation.php');
require_once('../../classes/class.authentication.php');

require_once('../../classes/school_administration/class.academic_years.php');
require_once('../../classes/school_administration/class.classes.php');
require_once('../../classes/school_administration/class.class_sections.php');
require_once('../../classes/school_administration/class.students.php');
require_once('../../classes/school_administration/class.student_details.php');

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

if ($LoggedUser->HasPermissionForTask(TASK_GENERATE_REPORT_CARD) !== true)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}

$AcademicYears =  array();
$AcademicYears = AcademicYear::GetAllAcademicYears();

$ClassList =  array();
$ClassList = AddedClass::GetActiveClasses();

$AllHappendedExamList = array();

$ClassSectionsList = array();

$HasErrors = false;
$SearchErrors = false;

$Clean = array();
$Clean['Process'] = 0;

$Clean['AcademicYearID'] = 0;
$Clean['ClassID'] = 0;
$Clean['ClassSectionID'] = 0;

$Clean['ExamTypeIDList'] = array();

$Clean['StudentIDList'] = array();

$StudentsList = array();

$Clean['SelectedExamType'] = '';

if (isset($_POST['hdnProcess']))
{
	$Clean['Process'] = (int) $_POST['hdnProcess'];
}
switch ($Clean['Process'])
{
	case 1:
		if (isset($_POST['hdnAcademicYearID'])) 
        {
            $Clean['AcademicYearID'] = (int) $_POST['hdnAcademicYearID'];
        }

        if (isset($_POST['hdnClassID'])) 
        {
            $Clean['ClassID'] = (int) $_POST['hdnClassID'];
        }

        if (isset($_POST['hdnClassSection'])) 
        {
            $Clean['ClassSectionID'] = (int) $_POST['hdnClassSection'];
        }

        if (isset($_POST['hdnSelectedExamType'])) 
        {
            $Clean['SelectedExamType'] = strip_tags(trim($_POST['hdnSelectedExamType']));
        }

        if (isset($_POST['chkStudentList']) && is_array($_POST['chkStudentList'])) 
        {
            $Clean['StudentIDList'] = $_POST['chkStudentList'];
        }

		$NewRecordValidator = new Validator();

		if ($NewRecordValidator->ValidateInSelect($Clean['ClassID'], $ClassList, 'Unknown error, please try again.')) 
		{
			$ClassSectionsList = ClassSections::GetClassSections($Clean['ClassID']);

            if ($NewRecordValidator->ValidateInSelect($Clean['ClassSectionID'], $ClassSectionsList, 'Unknown error, please try again.')) 
            {   
                $AllHappendedExamList = Exam::GetAllExamsForReportCard($Clean['ClassSectionID'], false);

                $Clean['ExamTypeIDList'] = explode(',', $Clean['SelectedExamType']);

                foreach ($Clean['ExamTypeIDList'] as $ExamTypeID) 
                {
                    $NewRecordValidator->ValidateInSelect($ExamTypeID, $AllHappendedExamList, 'Unknown error, please try again.');
                }

                $StudentsList = StudentDetail::GetStudentsByClassSectionID($Clean['ClassSectionID'], 'Active', $Clean['AcademicYearID']);

                $SelectedStudents = '';

                if (count($Clean['StudentIDList']) <= 0) 
                {
                    $NewRecordValidator->AttachTextError('Please select at least one student.');
                    $SearchErrors = true;
                    break;
                }

                foreach ($Clean['StudentIDList'] as $Key => $StudentID) 
                {
                    if (!array_key_exists($StudentID, $StudentsList)) 
                    {
                        header('location:admin/error.php');
                    }

                    $SelectedStudents = implode('$', $Clean['StudentIDList']);
                }
            }
		}
		
		if ($NewRecordValidator->HasNotifications())
		{
			$SearchErrors = true;
			break;
		}

		header('location:student_report_card.php?ClassSectionID=' . $Clean['ClassSectionID'] . '&SelectedStudents='.$SelectedStudents . '&SelectedExamType=' . $Clean['SelectedExamType']);
		exit;
	break;

    case 7:
        if (isset($_POST['drdAcademicYear'])) 
        {
            $Clean['AcademicYearID'] = (int) $_POST['drdAcademicYear'];
        }

        if (isset($_POST['drdClass'])) 
        {
            $Clean['ClassID'] = (int) $_POST['drdClass'];
        }

        if (isset($_POST['drdClassSection'])) 
        {
            $Clean['ClassSectionID'] = (int) $_POST['drdClassSection'];
        }

        if (isset($_POST['chkExamTypes']) && is_array($_POST['chkExamTypes'])) 
        {
            $Clean['ExamTypeIDList'] = $_POST['chkExamTypes'];
        }
        
        $NewRecordValidator = new Validator();

        if ($NewRecordValidator->ValidateInSelect($Clean['ClassID'], $ClassList, 'Unknown error, please try again.')) 
        {
            $CurrentClass = new AddedClass($Clean['ClassID']);
            $ClassSectionsList = ClassSections::GetClassSections($Clean['ClassID']);

            if ($NewRecordValidator->ValidateInSelect($Clean['ClassSectionID'], $ClassSectionsList, 'Unknown error, please try again.')) 
            {   
                $AllHappendedExamList = Exam::GetAllExamsForReportCard($Clean['ClassSectionID'], false);

                foreach ($Clean['ExamTypeIDList'] as $ExamTypeID => $Value) 
                {   
                    $NewRecordValidator->ValidateInSelect($ExamTypeID, $AllHappendedExamList, 'Unknown error, please try again.');
                }

                $Clean['SelectedExamType'] = implode(',', array_keys($Clean['ExamTypeIDList']));
            }
        }
        
        if ($NewRecordValidator->HasNotifications())
        {
            $HasErrors = true;
            break;
        }

        $StudentsList = StudentDetail::GetStudentsByClassSectionID($Clean['ClassSectionID'], 'Active', $Clean['AcademicYearID']);
                
    break;
}

require_once('../html_header.php');
?>
<title>Generate Report Cards</title>
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
                    <h1 class="page-header">Generate Report Cards</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="GenerateReportCards" action="generate_report_cards.php" method="post">
            	<div class="panel panel-default">
                    <div class="panel-heading">
                        Enter Details
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
                            <label for="AcademicYear" class="col-lg-2 control-label">Academic Year</label>
                            <div class="col-lg-4">
                                <select class="form-control" name="drdAcademicYear" id="AcademicYearID">
<?php
                                if (is_array($AcademicYears) && count($AcademicYears) > 0)
                                {
                                    foreach ($AcademicYears as $AcademicYearID => $AcademicYearDetails)
                                    {
                                        if ($Clean['AcademicYearID'] == 0)
                                        {
                                            if ($AcademicYearDetails['IsCurrentYear'] == 1)
                                            {
                                                // $Clean['AcademicYearID'] = $AcademicYearID;   
                                            }
                                        }
                                        
                                        echo '<option ' . ($Clean['AcademicYearID'] == $AcademicYearID ? 'selected="selected"' : '') . ' value="' . $AcademicYearID . '" >' . date('Y', strtotime($AcademicYearDetails['StartDate'])) .' - '. date('Y', strtotime($AcademicYearDetails['EndDate'])) . '</option>';
                                    }
                                }
?>
                                </select>
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
                            <label for="Class" class="col-lg-2 control-label">Section</label>
                            <div class="col-lg-4">
                                <select class="form-control" name="drdClassSection" id="ClassSection">
                                    <option value="0">-- Select Section --</option>
<?php
                                if (is_array($ClassSectionsList) && count($ClassSectionsList) > 0)
                                {
                                    foreach ($ClassSectionsList as $ClassSectionID => $SectionName) 
                                    {
                                        echo '<option '.($Clean['ClassSectionID'] == $ClassSectionID ? 'selected="selected"' : '') .' value="'. $ClassSectionID .'">'. $SectionName .'</option>';
                                    }
                                }
?>
                                </select>
                            </div>
                        </div>
                        <div id="ExamList">
<?php
                        if ($Clean['ClassSectionID'] > 0 && count($Clean['ExamTypeIDList']) > 0) 
                        {
?>
                            <div class="form-group">
                                <label for="Class" class="col-lg-2 control-label">Exams</label>
                                <div class="col-lg-8">
                                    <label class="checkbox-inline">
                                        <input type="checkbox" id="CheckAllExams" name="chkCheckAll" value="1"  <?php echo (count($Clean['ExamTypeIDList']) == count($AllHappendedExamList)) ? 'checked="checked"' : '';?>/>All
                                    </label>
<?php
                                foreach ($AllHappendedExamList as $ExamTypeID => $ExamTypeDetails) 
                                {
?>
                                    <label class="checkbox-inline"><input type="checkbox" class="CheckAllExams" <?php echo array_key_exists($ExamTypeID, $Clean['ExamTypeIDList']) ? 'checked="checked"' : '';?> name="chkExamTypes[<?php echo $ExamTypeID; ?>]"><?php echo $ExamTypeDetails['ExamType']; ?></label>
<?php
                                }
?>
                                </div>
                            </div>
<?php
                        }
?>
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
                            <form class="form-horizontal" name="GenerateReportCards" action="generate_report_cards.php" method="post">

                                <div class="row" id="RecordTable">
                                    <div class="col-lg-12">
                                        <table width="100%" class="table table-striped table-bordered table-hover" id="DataTableRecords">
                                            <thead>
                                                <tr>
                                                    <th>S. No</th>
                                                    <th>
                                                        Student&nbsp;&nbsp;<label for="AllStudent"><input type="checkbox" id="AllStudent" name="chkAllStudent" <?php echo count($Clean['StudentIDList']) == count($StudentsList) ? 'checked="checked"' : ''; ?> value="" />  &nbsp;All</label>
                                                    </th>
                                                    <!-- <th>Print</th> -->
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
                                                <td>
                                                    <label for="<?php echo $StudentID; ?>" class="checkbox-inline" ><input type="checkbox" id="<?php echo $StudentID; ?>" class="check-all-student" name="chkStudentList[<?php echo $StudentID; ?>]" <?php echo (in_array($StudentID, $Clean['StudentIDList'])) ? 'checked="checked"' : ''; ?> value="<?php echo $StudentID; ?>" /> &nbsp;<?php echo $StudentDetails['FirstName'] . $StudentDetails['LastName'] .' ( '. $StudentDetails['RollNumber'] .' )'; ?></label>
                                                </td>
                                                <!-- <td></td> -->
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
                                            <input type="hidden" name="hdnSelectedExamType" value="<?php echo $Clean['SelectedExamType'];?>" />
                                            <input type="hidden" name="hdnAcademicYearID" value="<?php echo $Clean['AcademicYearID'];?>" />
                                            <input type="hidden" name="hdnClassID" value="<?php echo $Clean['ClassID'];?>" />
                                            <input type="hidden" name="hdnClassSection" value="<?php echo $Clean['ClassSectionID'];?>" />
                                            <input type="hidden" name="hdnProcess" value="1" />
                                            <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i>&nbsp;Generate Report Cards</button>
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
$(document).ready(function()
{   
    $('body').on('click', '#CheckAllExams', function()
    {
        if($(this).is(":checked"))
        {
            $('.CheckAllExams').prop('checked', true);
        }
        else if($(this).is(":not(:checked)"))
        {
            $('.CheckAllExams').prop('checked', false);
        }
    });

	$('#Class').change(function()
    {
        var ClassID = parseInt($('#Class').val());
        
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

    $('#ClassSection').change(function()
    {
        var ClassSectionID = parseInt($('#ClassSection').val());
        
        if (ClassSectionID <= 0)
        {
            $('#ClassSection').html('<option value="0">-- Select Section --</option>');
            return;
        }
        
        $.post("/xhttp_calls/get_exams_by_classs_section.php", {SelectedClassSectionID:ClassSectionID}, function(data)
        {
            ResultArray = data.split("|*****|");
            
            if (ResultArray[0] == 'error')
            {
                alert (ResultArray[1]);
                return false;
            }
            else
            {
                $('#ExamList').html(ResultArray[1]);
            }
        });
    });

    $('#AllStudent').change(function(){
        if ($(this).prop("checked") == true)
        {
            $('.check-all-student').prop('checked',true);
        }
        else
        {
            $('.check-all-student').prop('checked',false);
        }
    });

    $('.check-all-student').change(function(){

        var TotalStudent = <?php echo count($StudentsList);?>;
        
        if ($('input.check-all-student:checked').length == TotalStudent)
        {
            $('#AllStudent').prop('checked', true);
        }
        else
        {
            $('#AllStudent').prop('checked', false);
        }
    });

    $('#AcademicYearID').change(function(){

        $('#Class').val(0);
        $('#ClassSection').html('<option value="0">Select Section</option>');
        $('#ExamList').html('');
    });
});
</script>
</body>
</html>
<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once('../../classes/school_administration/class.academic_years.php');
require_once('../../classes/school_administration/class.academic_year_months.php');

require_once('../../classes/school_administration/class.classes.php');
require_once('../../classes/school_administration/class.class_sections.php');
require_once('../../classes/school_administration/class.section_master.php');

require_once('../../classes/school_administration/class.students.php');
require_once('../../classes/school_administration/class.student_details.php');
require_once('../../classes/school_administration/class.parent_details.php');

require_once('../../classes/fee_management/class.fee_heads.php');
require_once('../../classes/fee_management/class.fee_collection.php');
require_once('../../classes/fee_management/class.late_fee_rules.php');

require_once("../../classes/class.date_processing.php");
require_once("../../classes/class.helpers.php");

require_once("../../classes/class.global_settings.php");

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

if ($LoggedUser->HasPermissionForTask(TASK_FEE_COLLECTION) !== true)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}

if (isset($_POST['btnCancel']))
{
    header('location:fee_collection_report.php');
    exit;
}

$Clean = array();

$Clean['FeeCollectionID'] = 0;

if (isset($_GET['FeeCollectionID']))
{
    $Clean['FeeCollectionID'] = (int) $_GET['FeeCollectionID'];
}
else if (isset($_POST['hdnFeeCollectionID']))
{
    $Clean['FeeCollectionID'] = (int) $_POST['hdnFeeCollectionID'];
}

if ($Clean['FeeCollectionID'] <= 0)
{
    header('location:../error.php');
    exit;
}   

try
{
    $FeeCollectionToEdit = new FeeCollection($Clean['FeeCollectionID']);
}
catch (ApplicationDBException $e)
{
    header('location:../error.php');
    exit;
}
catch (Exception $e)
{
    header('location:../error.php');
    exit;
}

$HasErrors = false;
    
$Clean['Process'] = 0;

$Clean['StudentID'] = 0;
$Clean['ClassID'] = 0;
$Clean['ClassSectionID'] = 0;

$AcademicYearID = 0;
$AcademicYearName = '';

$AcademicYearID = AcademicYear::GetCurrentAcademicYear($AcademicYearName);

$ClassList =  array();
$ClassList = AddedClass::GetActiveClasses();

$ClassSectionsList =  array();
$StudentsList = array();

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
    case 3:           

        if (isset($_POST['txtFeeDate']))
        {
            $Clean['FeeDate'] = strip_tags(trim($_POST['txtFeeDate']));
        }
        
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
        
        $NewRecordValidator = new Validator();
        
        $NewRecordValidator->ValidateDate($Clean['FeeDate'], 'Please enter a valid fee date.');
        
        if ($NewRecordValidator->ValidateInSelect($Clean['ClassID'], $ClassList, 'Please select a valid class.')) 
		{
			$ClassSectionsList = ClassSections::GetClassSections($Clean['ClassID']);

			if ($NewRecordValidator->ValidateInSelect($Clean['ClassSectionID'], $ClassSectionsList, 'Please select a valid section.')) 
			{
				$StudentsList = StudentDetail::GetStudentsByClassSectionID($Clean['ClassSectionID']);

				$NewRecordValidator->ValidateInSelect($Clean['StudentID'], $StudentsList, 'Please select a valid student.');
			}
		}
        
        if ($NewRecordValidator->HasNotifications())
        {
            $HasErrors = true;
            break;
        }
                        
        $FeeCollectionToEdit->SetFeeDate(date('Y-m-d', strtotime(DateProcessing::ToggleDateDayAndMonth(($Clean['FeeDate'])))));
        $FeeCollectionToEdit->SetStudentID($Clean['StudentID']);
        
        if (!$FeeCollectionToEdit->Save())
        {
            $NewRecordValidator->AttachTextError(ProcessErrors($FeeCollectionToEdit->GetLastErrorCode()));
            $HasErrors = true;

            break;
        }
        
        header('location:fee_collection_report.php?Mode=UD');
        exit;
    break;

    case 2:        
        $Clean['FeeDate'] = date('d/m/Y', strtotime($FeeCollectionToEdit->GetFeeDate()));;
        $Clean['StudentID'] = $FeeCollectionToEdit->GetStudentID();
        
        $StudentDetailObject = new StudentDetail($Clean['StudentID']);
        
        $Clean['ClassSectionID'] = $StudentDetailObject->GetClassSectionID();
        $ClassSectionObject = new ClassSections($Clean['ClassSectionID']);
        
        $Clean['ClassID'] = $ClassSectionObject->GetClassID();
        
        $ClassSectionsList = ClassSections::GetClassSections($Clean['ClassID']);
        
        $StudentsList = StudentDetail::GetStudentsByClassSectionID($Clean['ClassSectionID']);
        

    break;
}

require_once('../html_header.php');
?>
<title>Edit Fee Group</title>
<link href="../vendor/jquery-ui/jquery-ui.min.css" rel="stylesheet">
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
                    <h1 class="page-header">Edit Transaction</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="FeeCollection" action="edit_fee_transaction.php" method="post">
            	<div class="panel panel-default">
                    <div class="panel-heading">
                        <strong>Fee Collection Details</strong>
                    </div>
                    <div class="panel-body">
<?php
						if ($HasErrors == true)
						{
							echo $NewRecordValidator->DisplayErrors();
						}
?>                    
						<div class="form-group">
                            <label for="AcademicYear" class="col-lg-2 control-label">Academic Year</label>
                            <div class="col-lg-3">
                            	<input class="form-control" type="text" maxlength="10" id="AcademicYear" name="txtAcademicYear" readonly="readonly" value="<?php echo $AcademicYearName; ?>" />
                            </div>
                            <label for="FeeDate" class="col-lg-2 control-label">Fee Date</label>
                            <div class="col-lg-3">
                            	<input class="form-control select-date" type="text" maxlength="10" id="FeeDate" name="txtFeeDate" value="<?php echo $Clean['FeeDate']; ?>" />
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="ClassList" class="col-lg-2 control-label">Class</label>
                            <div class="col-lg-3">
                                <select class="form-control" name="drdClass" id="Class">
                                    <option  value="0" >Select Class</option>
<?php
                                    foreach ($ClassList as $ClassID => $ClassName)
                                    {
?>
                                        <option <?php echo ($ClassID == $Clean['ClassID'] ? 'selected="selected"' : ''); ?> value="<?php echo $ClassID; ?>"><?php echo $ClassName; ?></option>
<?php
                                    }
?>
                                </select>
                            </div>
                            <label for="ClassSection" class="col-lg-2 control-label">Section</label>
                            <div class="col-lg-3">
                                <select class="form-control" name="drdClassSection" id="ClassSection">
                                    <option value="0">Select Section</option>
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
                                    <option value="0">Select Student</option>
<?php
                                        if (is_array($StudentsList) && count($StudentsList) > 0)
                                        {
                                            foreach ($StudentsList as $StudentID=>$StudentDetails)
                                            {
                                                echo '<option ' . ($Clean['StudentID'] == $StudentID ? 'selected="selected"' : '') . ' value="' . $StudentID . '">' . $StudentDetails['FirstName'] . ' ' . $StudentDetails['LastName'] . '(' . $StudentDetails['RollNumber'] . ')</option>'; 
                                            }
                                        }
?>
                                </select>
                            </div>                                
                        </div>
                        
                        <div class="form-group">
                        <div class="col-sm-offset-2 col-lg-10">
                            <input type="hidden" name="hdnProcess" value="3"/>
                            <input type="hidden" name="hdnFeeCollectionID" value="<?php echo $Clean['FeeCollectionID']; ?>" />
                            <button type="submit" class="btn btn-primary">Update</button>
                            <button type="submit" class="btn btn-primary" name="btnCancel">Cancel</button>
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
<script src="../vendor/jquery-ui/jquery-ui.min.js"></script>
<script type="text/javascript">
$(document).ready(function() {
    $(".select-date").datepicker({
		changeMonth: true,
		changeYear: true,
		dateFormat: 'dd/mm/yy'
	});
	
	$('#Class').change(function(){

        var ClassID = parseInt($(this).val());
        
        if (ClassID <= 0)
        {
            $('#ClassSection').html('<option value="0">Select Section</option>');
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
                $('#ClassSection').html('<option value="0">Select Section</option>' + ResultArray[1]);
            }
         });
    });
    
    $('#ClassSection').change(function(){

        var ClassSectionID = parseInt($(this).val());
        
        if (ClassSectionID <= 0)
        {
            $('#Student').html('<option value="0">Select Student</option>');
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
                $('#Student').html(ResultArray[1]);
            }
        });
    });
});
</script>
</body>
</html>
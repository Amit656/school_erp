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

if ($LoggedUser->HasPermissionForTask(TASK_EDIT_STUDENT_REMARK) !== true)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}

if (isset($_POST['btnCancel']))
{
    header('location:remark_report.php');
    exit;
}

$Clean = array();

$Clean['RemarkID'] = 0;

if (isset($_GET['RemarkID']))
{
    $Clean['RemarkID'] = (int) $_GET['RemarkID'];
}
else if (isset($_POST['hdnRemarkID']))
{
    $Clean['RemarkID'] = (int) $_POST['hdnRemarkID'];
}

if ($Clean['RemarkID'] <= 0)
{
    header('location:../error.php');
    exit;
}   

try
{
    $RemarkToEdit = new Remark($Clean['RemarkID']);
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

$ClassList =  array();
$ClassList = AddedClass::GetActiveClasses();

$ClassSectionsList =  array();
$StudentsList = array();

$RemarkTypeList = array('Positive' => 'Positive', 'Negative' => 'Negative');

$HasErrors = false;

$Clean['Process'] = 0;

$Clean['ClassID'] = 0;
$Clean['ClassSectionID'] = 0;
$Clean['StudentID'] = 0;

$Clean['RemarkType'] = 'Positive';
$Clean['Remark'] = '';

$Clean['IsActive'] = 0;

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

        if (isset($_POST['chkIsActive']))
        {
            $Clean['IsActive'] = 1;
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
                
        $RemarkToEdit->SetStudentID($Clean['StudentID']);
        $RemarkToEdit->SetRemarkType($Clean['RemarkType']);
        $RemarkToEdit->SetRemark($Clean['Remark']);

        $RemarkToEdit->SetIsActive($Clean['IsActive']);

        if (!$RemarkToEdit->Save())
        {
            $NewRecordValidator->AttachTextError(ProcessErrors($RemarkToEdit->GetLastErrorCode()));
            $HasErrors = true;

            break;
        }
        
        header('location:remark_report.php?Mode=UD');
        exit;
    break;

    case 2:
        $Clean['StudentID'] = $RemarkToEdit->GetStudentID();

        try
        {
            $StudentDetailObject = new StudentDetail($Clean['StudentID']);    
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

        $Clean['ClassSectionID'] = $StudentDetailObject->GetClassSectionID();

        $StudentsList = StudentDetail::GetStudentsByClassSectionID($Clean['ClassSectionID']);

        $ClassSectionDetails = new ClassSections($StudentDetailObject->GetClassSectionID());
        $Clean['ClassID'] = $ClassSectionDetails->GetClassID();

        $ClassSectionsList = ClassSections::GetClassSections($Clean['ClassID']);

        $Clean['RemarkType'] = $RemarkToEdit->GetRemarkType();
        $Clean['Remark'] = $RemarkToEdit->GetRemark();

        $Clean['IsActive'] = $RemarkToEdit->GetIsActive();

    break;
}

require_once('../html_header.php');
?>
<title>Give Remark To Student</title>
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
                    <h1 class="page-header">Give Remark To Student</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="EditRemark" action="edit_remark.php" method="post">
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
                            <label for="IsActive" class="col-lg-2 control-label">Is Active</label>
                            <div class="col-lg-4">
                                <input type="checkbox" id="IsActive" name="chkIsActive" <?php echo ($Clean['IsActive'] == 1) ? 'checked="checked"' : ''; ?> value="1" />
                            </div>
                        </div>
                        <div class="form-group">
                        <div class="col-sm-offset-2 col-lg-10">
                            <input type="hidden" name="hdnProcess" value="3"/>
                            <input type="hidden" name="hdnRemarkID" value="<?php echo $Clean['RemarkID']; ?>" />
                            <button type="submit" class="btn btn-primary"><i class="fa fa-upload"></i>&nbsp;Update</button>
                            <button type="submit" class="btn btn-primary" name="btnCancel">Cancel</button>
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
?>
<script type="text/javascript">
    /*$(document).ready(function(){

        $('select').hover(function(){
        $(this).prop('title', "You can't change it.");

        $('select').css('pointer-events','none');
    });
    });*/
</script>
</body>
</html>
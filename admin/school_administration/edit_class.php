<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once("../../classes/school_administration/class.academic_years.php");
require_once("../../classes/school_administration/class.classes.php");

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

if ($LoggedUser->HasPermissionForTask(TASK_ADD_EDIT_CLASS) !== true)
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

if (isset($_POST['btnCancel']))
{
    header('location:class_list.php');
    exit;
}

$Clean = array();

$Clean['ClassID'] = 0;

if (isset($_GET['ClassID']))
{
    $Clean['ClassID'] = (int) $_GET['ClassID'];
}
elseif (isset($_POST['hdnClassID']))
{
    $Clean['ClassID'] = (int) $_POST['hdnClassID'];
}

if ($Clean['ClassID'] <= 0)
{
    header('location:../error.php');
    exit;
}   

try
{
    $AddedClassToEdit = new AddedClass($Clean['ClassID']);
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

$AcademicYearID = 0;
$AcademicYearID = AcademicYear::GetCurrentAcademicYear();

$CurrentAcademicYear = new AcademicYear($AcademicYearID);
$CurrentAcademicYearText = date('d/m/Y', strtotime($CurrentAcademicYear->GetStartDate())) . ' - ' . date('d/m/Y', strtotime($CurrentAcademicYear->GetEndDate()));

$HasErrors = false;
    
$Clean['Process'] = 0;

$Clean['ClassName'] = '';
$Clean['ClassSymbol'] = '';
$Clean['HasDifferentSubjects'] = 0;

$Clean['Priority'] = 0;
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
        if (isset($_POST['txtClassName']))
        {
            $Clean['ClassName'] = strip_tags(trim($_POST['txtClassName']));
        }

        if (isset($_POST['txtClassSymbol']))
        {
            $Clean['ClassSymbol'] = strip_tags(trim($_POST['txtClassSymbol']));
        }
        
        if (isset($_POST['chkHasDifferentSubjects']))
        {
            $Clean['HasDifferentSubjects'] = (int) $_POST['chkHasDifferentSubjects'];
        }

        if (isset($_POST['txtPriority']))
        {
            $Clean['Priority'] = strip_tags(trim($_POST['txtPriority']));
        }
        
        if (isset($_POST['chkIsActive']))
        {
            $Clean['IsActive'] = 1;
        }

        $NewRecordValidator = new Validator();
        $NewRecordValidator->ValidateStrings($Clean['ClassName'], 'Class Name is required and should be between 1 and 15 characters.', 1, 15);
    
        $NewRecordValidator->ValidateStrings($Clean['ClassSymbol'], 'Class Symbol is required and should be between 1 and 15 characters.', 1, 15);
        $NewRecordValidator->ValidateInteger($Clean['Priority'], 'Priority is required And should Greater than 0.', 0);

        if ($NewRecordValidator->HasNotifications())
        {
            $HasErrors = true;
            break;
        }

        $AddedClassToEdit->SetClassName($Clean['ClassName']);
        $AddedClassToEdit->SetClassSymbol($Clean['ClassSymbol']);
        $AddedClassToEdit->SetHasDifferentSubjects($Clean['HasDifferentSubjects']);

        $AddedClassToEdit->SetPriority($Clean['Priority']);
        $AddedClassToEdit->SetIsActive($Clean['IsActive']);

        if ($AddedClassToEdit->RecordExists())
        {
            $NewRecordValidator->AttachTextError('The Class Name you have added already exists.');
            $HasErrors = true;
            break;
        }
        
        if (!$AddedClassToEdit->Save())
        {
            $NewRecordValidator->AttachTextError(ProcessErrors($AddedClassToEdit->GetLastErrorCode()));
            $HasErrors = true;
            break;
        }
        
        header('location:class_list.php?Mode=UD');
        exit;
    break;

    case 2:
        $Clean['AcademicYearID'] = $AddedClassToEdit->GetAcademicYearID();

        $Clean['ClassName'] = $AddedClassToEdit->GetClassName();
        $Clean['ClassSymbol'] = $AddedClassToEdit->GetClassSymbol();
        $Clean['HasDifferentSubjects'] = $AddedClassToEdit->GetHasDifferentSubjects();

        $Clean['Priority'] = $AddedClassToEdit->GetPriority();
        $Clean['IsActive'] = $AddedClassToEdit->GetIsActive();
    break;
}

require_once('../html_header.php');
?>
<title>Edit Class</title>
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
                    <h1 class="page-header">Edit Class</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="EditClass" action="edit_class.php" method="post">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        Enter Class Details
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
                            <div class="col-lg-4">
                                <input class="form-control" type="text" maxlength="20" id="AcademicYear" name="txtAcademicYear" disabled="disabled" value="<?php echo($AcademicYearID == 0) ? 'Not Yet Defined' : $CurrentAcademicYearText;?>"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="ClassName" class="col-lg-2 control-label">Class Name</label>
                            <div class="col-lg-4">
                                <input class="form-control" type="text" maxlength="15" id="ClassName" name="txtClassName" value="<?php echo $Clean['ClassName']; ?>" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="ClassSymbol" class="col-lg-2 control-label">Class Symbol</label>
                            <div class="col-lg-4">
                                <input class="form-control" type="text" maxlength="15" id="ClassSymbol" name="txtClassSymbol" value="<?php echo $Clean['ClassSymbol']; ?>" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="Priority" class="col-lg-2 control-label">Class Priority</label>
                            <div class="col-lg-4">
                                <input class="form-control" type="text" maxlength="15" id="Priority" name="txtPriority" value="<?php echo $Clean['Priority']; ?>" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="HasDifferentSubjects" class="col-lg-2 control-label">Has Different Subjects</label>
                            <div class="col-lg-4">
                                <input type="checkbox" id="HasDifferentSubjects" name="chkHasDifferentSubjects" value="1" <?php echo ($Clean['HasDifferentSubjects'] == 1) ? 'checked="checked"' : ''; ?>/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="IsActive" class="col-lg-2 control-label">Is Active</label>
                            <div class="col-lg-4">
                                <input type="checkbox" id="IsActive" name="chkIsActive" value="1" <?php echo ($Clean['IsActive'] == 1) ? 'checked="checked"' : ''; ?>/>
                            </div>
                        </div>
                        <div class="form-group">
                        <div class="col-sm-offset-2 col-lg-10">
                            <input type="hidden" name="hdnProcess" value="3"/>
                            <input type="hidden" name="hdnClassID" value="<?php echo $Clean['ClassID'];?>" />
                            <button type="submit" class="btn btn-primary">Save</button>
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
</body>
</html>
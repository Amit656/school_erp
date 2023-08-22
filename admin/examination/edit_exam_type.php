<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once("../../classes/examination/class.exam_types.php");

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

if ($LoggedUser->HasPermissionForTask(TASK_EDIT_EXAM_TYPE) !== true)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}

if (isset($_POST['btnCancel']))
{
    header('location:exam_type_list.php');
    exit;
}

$Clean = array();

$Clean['ExamTypeID'] = 0;

if (isset($_GET['ExamTypeID']))
{
    $Clean['ExamTypeID'] = (int) $_GET['ExamTypeID'];
}
else if (isset($_POST['hdnExamTypeID']))
{
    $Clean['ExamTypeID'] = (int) $_POST['hdnExamTypeID'];
}

if ($Clean['ExamTypeID'] <= 0)
{
    header('location:/admin/error.php');
    exit;
}   

try
{
    $ExamTypeToEdit = new ExamType($Clean['ExamTypeID']);
}
catch (ApplicationDBException $e)
{
    header('location:/admin/error.php');
    exit;
}
catch (Exception $e)
{
    header('location:/admin/error.php');
    exit;
}

$HasErrors = false;

$Clean['Process'] = 0;

$Clean['ExamType'] = '';

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
        if (isset($_POST['txtExamType']))
        {
            $Clean['ExamType'] = strip_tags(trim($_POST['txtExamType']));
        }

        if (isset($_POST['chkIsActive']))
        {
            $Clean['IsActive'] = 1;
        }

        $NewRecordValidator = new Validator();

        $NewRecordValidator->ValidateStrings($Clean['ExamType'], 'Exam type is required and should be between 3 and 100 characters.', 3, 100);
        
        if ($NewRecordValidator->HasNotifications())
        {
            $HasErrors = true;
            break;
        }
                        
        $ExamTypeToEdit->SetExamType($Clean['ExamType']);

        $ExamTypeToEdit->SetIsActive($Clean['IsActive']);

        if (!$ExamTypeToEdit->Save())
        {
            $NewRecordValidator->AttachTextError(ProcessErrors($ExamTypeToEdit->GetLastErrorCode()));
            $HasErrors = true;

            break;
        }
        
        header('location:exam_type_list.php?Mode=UD');
        exit;
    break;

    case 2:
        $Clean['ExamType'] = $ExamTypeToEdit->GetExamType();
        
        $Clean['IsActive'] = $ExamTypeToEdit->GetIsActive();

    break;
}

require_once('../html_header.php');
?>
<title>Edit Exam Type</title>
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
                    <h1 class="page-header">Edit Exam Type</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="EditExamType" action="edit_exam_type.php" method="post">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        Edit Exam Type
                    </div>
                    <div class="panel-body">
<?php
                        if ($HasErrors == true)
                        {
                            echo $NewRecordValidator->DisplayErrors();
                        }
?>                    
                        <div class="form-group">
                            <label for="ExamType" class="col-lg-2 control-label">Exam Type</label>
                            <div class="col-lg-4">
                                <input class="form-control" type="text" maxlength="100" id="ExamType" name="txtExamType" value="<?php echo $Clean['ExamType']; ?>" />
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
                            <input type="hidden" name="hdnExamTypeID" value="<?php echo $Clean['ExamTypeID']; ?>" />
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
</body>
</html>
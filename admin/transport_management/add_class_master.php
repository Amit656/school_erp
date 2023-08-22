<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once("../../classes/transport_management/class.class_master.php");

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

$UserMenusArray = array();
$UserMenusArray = $LoggedUser->GetUserMenus();

if (!is_array($UserMenusArray) || count($UserMenusArray) <= 0)  
{
    //header('location:../logout.php');
    //exit;
}

$HasErrors = false;

$Clean = array();

$Clean['Process'] = 0;
$Clean['ClassMasterID'] = 0;

$Clean['ClassName'] = '';
$Clean['SectionName'] = '';

if (isset($_POST['hdnProcess']))
{
    $Clean['Process'] = (int) $_POST['hdnProcess'];
}
switch ($Clean['Process'])
{
    case 1:
        if (isset($_POST['txtClassName']))
        {
            $Clean['ClassName'] = strip_tags(trim($_POST['txtClassName']));
        }

        if (isset($_POST['txtSectionName']))
        {
            $Clean['SectionName'] = strip_tags(trim($_POST['txtSectionName']));
        }

        $NewRecordValidator = new Validator();

        $NewRecordValidator->ValidateStrings($Clean['ClassName'], 'Class name is required and should be between 1 and 150 characters.', 1, 150);
        $NewRecordValidator->ValidateStrings($Clean['SectionName'], 'Section name is required and should be between 1 and 150 characters.', 1, 150);
        
        if ($NewRecordValidator->HasNotifications())
        {
            $HasErrors = true;
            break;
        }
                
        $NewClassMaster = new ClassMaster();

        $NewClassMaster->SetClassName($Clean['ClassName']);
        $NewClassMaster->SetSectionName($Clean['SectionName']);
        $NewClassMaster->SetIsActive(1);
        $NewClassMaster->SetCreateUserID($LoggedUser->GetUserID());

        if (!$NewClassMaster->Save())
        {
            $NewRecordValidator->AttachTextError(ProcessErrors($NewClassMaster->GetLastErrorCode()));
            $HasErrors = true;
            break;
        }
        
        header('location:class_master_report.php?Mode=AS');
        exit;
    break;
}

require_once('../html_header.php');
?>
<title>Add Class</title>
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
                    <h1 class="page-header">Add Class</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="AddClassMaster" action="add_class_master.php" method="post">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <strong>Enter Class Details</strong>
                    </div>
                    <div class="panel-body">
<?php
                        if ($HasErrors == true)
                        {
                            echo $NewRecordValidator->DisplayErrors();

                            
                        }
?>       
                        <div class="form-group">
                            <label for="ClassName" class="col-lg-2 control-label">Enter Class Name</label>
                            <div class="col-lg-3">
                                <input class="form-control" type="text" maxlength="10" id="Class" name="txtClassName" value="" />
                            </div>
                        </div>  
                        <div class="form-group">
                            <label for="SectionName" class="col-lg-2 control-label">Enter Section Name</label>
                            <div class="col-lg-3">
                                <input class="form-control" type="text" maxlength="10" id="Section" name="txtSectionName" value="" />
                            </div>
                        </div>
                        <div class="form-group">
                        <div class="col-sm-offset-2 col-lg-10">
                            <input type="hidden" name="hdnProcess" value="1" /> 
                            <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i>&nbsp;Submit</button>
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

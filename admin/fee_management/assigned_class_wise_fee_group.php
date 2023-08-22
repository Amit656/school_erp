<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once("../../classes/fee_management/class.fee_groups.php");

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

if ($LoggedUser->HasPermissionForTask(TASK_ASSIGN_GROUP_TO_CLASS) !== true)
{
    header('location:/admin/unauthorized_login_admin.php');
    exit;
}

if (isset($_POST['btnCancel']))
{
    header('location:fee_group_list.php');
    exit;
}

$Clean = array();

$Clean['FeeGroupID'] = 0;

if (isset($_GET['FeeGroupID']))
{
    $Clean['FeeGroupID'] = (int) $_GET['FeeGroupID'];
}
else if (isset($_POST['hdnFeeGroupID']))
{
    $Clean['FeeGroupID'] = (int) $_POST['hdnFeeGroupID'];
}

if ($Clean['FeeGroupID'] <= 0)
{
    header('location:../error.php');
    exit;
}   

try
{
    $RecordToEdit = new FeeGroup($Clean['FeeGroupID']);
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

$FeeGrouplist = array();
$FeeGrouplist = FeeGroup::GetActiveFeeGroups('Class');

$ClassList =  array();
$ClassList = AddedClass::GetActiveClasses();

$HasErrors = false;

$Clean['Process'] = 0;

$Clean['RecordIDList'] = array();

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
        if (isset($_POST['drdFeeGroupID']))
        {
            $Clean['FeeGroupID'] = (int) $_POST['drdFeeGroupID'];
        }

        if (isset($_POST['chkRecordIDList']) && is_array($_POST['chkRecordIDList']))
        {
            $Clean['RecordIDList'] = $_POST['chkRecordIDList'];
        }
        
        $NewRecordValidator = new Validator();

        $NewRecordValidator->ValidateInSelect($Clean['FeeGroupID'], $FeeGrouplist, 'Unknown group, Please try again.');
    
        foreach ($Clean['RecordIDList'] as $key => $ClassID)
        {
          $NewRecordValidator->ValidateInSelect($ClassID, $ClassList, 'Unknown Error, Please Try Again.');
        }
        
        if (count($Clean['RecordIDList']) <= 0) 
        {
            $NewRecordValidator->AttachTextError('Please select atleast one class.');   
            $HasErrors = true;
            break;
        }
        
        if ($NewRecordValidator->HasNotifications())
        {
            $HasErrors = true;
            break;
        }
                
        $RecordToEdit->SetRecordIDList($Clean['RecordIDList']);

        if (!$RecordToEdit->AssignFeeGroup('Class'))
        {
            $NewRecordValidator->AttachTextError(ProcessErrors($RecordToEdit->GetLastErrorCode()));
            $HasErrors = true;
            break;
        }
        
        header('location:fee_group_list.php?Mode=UD');
        exit;
    break;

    case 2:
        $RecordToEdit->FillAssignedRecordsToFeeGroup();
        $Clean['RecordIDList'] = $RecordToEdit->GetRecordIDList();

    break;
}

require_once('../html_header.php');
?>
<title>Assign Class Wise Fee Group</title>
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
                    <h1 class="page-header">Assign Class Wise Fee Group</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="AddFeeGroup" action="assigned_class_wise_fee_group.php" method="post">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        Assign Fee Group To Class
                    </div>
                    <div class="panel-body">
<?php
                        if ($HasErrors == true)
                        {
                            echo $NewRecordValidator->DisplayErrors();
                        }
?>                    
                        <div class="form-group">
                        <label for="FeeGroupID" class="col-lg-2 control-label">Fee Group</label>
                            <div class="col-lg-4">
                                <select class="form-control" name="drdFeeGroupID" id="FeeGroupID" disabled="disabled">
<?php
                                if (is_array($FeeGrouplist) && count($FeeGrouplist) > 0)
                                {
                                    foreach($FeeGrouplist as $FeeGroupID=>$FeeGroupName)
                                    {
                                        echo '<option ' . (($Clean['FeeGroupID'] == $FeeGroupID) ? 'selected="selected"' : '') . ' value="' . $FeeGroupID . '">' . $FeeGroupName . '</option>';
                                    }
                                }
?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="RecordID" class="col-lg-2 control-label">Class</label>
                            <div class="col-lg-8">
<?php
                                foreach ($ClassList as $ClassID => $ClassName) 
                                {
?>
                                    <label class="checkbox-inline"><input class="custom-radio" type="checkbox" id="<?php echo $ClassID; ?>" name="chkRecordIDList[]" <?php echo (in_array($ClassID, $Clean['RecordIDList']) ? 'checked="checked"' : ''); ?> value="<?php echo $ClassID; ?>" />
                                        <?php echo $ClassName; ?>
                                    </label>
<?php
                                }
?>
                            </div>
                        </div>                        
                        <div class="form-group">
                        <div class="col-sm-offset-2 col-lg-10">
                            <input type="hidden" name="hdnProcess" value="3"/>
                            <input type="hidden" name="hdnFeeGroupID" value="<?php echo $Clean['FeeGroupID']; ?>" />
                            <button type="submit" class="btn btn-primary">Save</button>
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
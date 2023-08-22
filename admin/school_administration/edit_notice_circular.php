<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once("../../classes/school_administration/class.notices_circulars.php");
require_once("../../classes/school_administration/class.branch_staff.php");
require_once("../../classes/school_administration/class.classes.php");

require_once("../../classes/class.date_processing.php");
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

if (isset($_POST['btnCancel']))
{
    header('location:notices_circulars_list.php');
    exit;
}

$Clean = array();

$Clean['NoticeCircularID'] = 0;

if (isset($_GET['NoticeCircularID']))
{
    $Clean['NoticeCircularID'] = (int) $_GET['NoticeCircularID'];
}
else if (isset($_POST['hdnNoticeCircularID']))
{
    $Clean['NoticeCircularID'] = (int) $_POST['hdnNoticeCircularID'];
}

if ($Clean['NoticeCircularID'] <= 0)
{
    header('location:../error.php');
    exit;
}   

try
{
    $NoticeToEdit = new NoticeCircular($Clean['NoticeCircularID']);
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
    
$ClassList =  array();
$ClassList = AddedClass::GetActiveClasses();

$StaffList = array();
$StaffList = BranchStaff::SearchBranchStaff();

$Clean['Process'] = 0;

$Clean['SelectedStaffList'] = array();
$Clean['SelectedClassList'] = array();

$Clean['NoticeCircularDate'] = '';
$Clean['NoticeCircularSubject'] = '';
$Clean['NoticeCircularDetails'] = '';

$Clean['IsActive'] = 1;

$NoticeCircularApplicableFor = array();

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
        if (isset($_POST['drdSelectedStaffList']) && is_array($_POST['drdSelectedStaffList']))
        {
            $Clean['SelectedStaffList'] = $_POST['drdSelectedStaffList'];
        }

        if (isset($_POST['chkSelectedClassList']) && is_array($_POST['chkSelectedClassList']))
        {
            $Clean['SelectedClassList'] = $_POST['chkSelectedClassList'];
        }

        if (isset($_POST['txtNoticeCircularDate']))
        {
            $Clean['NoticeCircularDate'] = strip_tags(trim($_POST['txtNoticeCircularDate']));
        }

        if (isset($_POST['txtNoticeCircularSubject']))
        {
            $Clean['NoticeCircularSubject'] = strip_tags(trim($_POST['txtNoticeCircularSubject']));
        }

        if (isset($_POST['txtNoticeCircularDetails']))
        {
            $Clean['NoticeCircularDetails'] = strip_tags(trim($_POST['txtNoticeCircularDetails']));
        }

        if (!isset($_POST['chkIsActive']))
        {
            $Clean['IsActive'] = 0;
        }

        $NewRecordValidator = new Validator();

        if (count($Clean['SelectedStaffList']) <= 0 && count($Clean['SelectedClassList']) <= 0) 
        {
            $NewRecordValidator->AttachTextError('Please select atleast one staff or class.');  
            $HasErrors = true;
            break;
        }

        $Counter = 0;

        if (count($Clean['SelectedStaffList']) > 0)
        {
            foreach ($Clean['SelectedStaffList'] as $StaffID) 
            {
                $NewRecordValidator->ValidateInSelect($StaffID, $StaffList, 'Unknown Error, Please try again.');

                $NoticeCircularApplicableFor[$Counter]['ApplicableFor'] = 'Staff';
                $NoticeCircularApplicableFor[$Counter]['StaffOrClassID'] = $StaffID;
                $Counter++;
            }
        }

        if (count($Clean['SelectedClassList']) > 0)
        {
            foreach ($Clean['SelectedClassList'] as $ClassID)
            {
                $NewRecordValidator->ValidateInSelect($ClassID, $ClassList, 'Unknown Error, Please Try Again.');

                $NoticeCircularApplicableFor[$Counter]['ApplicableFor'] = 'Class';
                $NoticeCircularApplicableFor[$Counter]['StaffOrClassID'] = $ClassID;
                $Counter++;
            }
        }

        $NewRecordValidator->ValidateDate($Clean['NoticeCircularDate'], 'Please enter a valid date.');
        $NewRecordValidator->ValidateStrings($Clean['NoticeCircularSubject'], 'Notice subject is required and should be between 1 and 100 characters.', 1, 100);
        $NewRecordValidator->ValidateStrings($Clean['NoticeCircularDetails'], 'Notice details is required and should be between 1 and characters characters.', 1, 2000);
        
        if ($NewRecordValidator->HasNotifications())
        {
            $HasErrors = true;
            break;
        }
                
        $NoticeToEdit->SetNoticeCircularDate(date('Y-m-d', strtotime(DateProcessing::ToggleDateDayAndMonth(($Clean['NoticeCircularDate'])))));
        $NoticeToEdit->SetNoticeCircularSubject($Clean['NoticeCircularSubject']);
        $NoticeToEdit->SetNoticeCircularDetails($Clean['NoticeCircularDetails']);
        $NoticeToEdit->SetIsActive($Clean['IsActive']);
        
        $NoticeToEdit->SetNoticeCircularApplicableFor($NoticeCircularApplicableFor);

        if (!$NoticeToEdit->Save())
        {
            $NewRecordValidator->AttachTextError(ProcessErrors($NoticeToEdit->GetLastErrorCode()));
            $HasErrors = true;

            break;
        }
        
        header('location:notices_circulars_list.php?Mode=UD');
        exit;
    break;

    case 2:
        $Clean['NoticeCircularDate'] = date('d/m/Y', strtotime($NoticeToEdit->GetNoticeCircularDate()));
        $Clean['NoticeCircularSubject'] = $NoticeToEdit->GetNoticeCircularSubject();
        $Clean['NoticeCircularDetails'] = $NoticeToEdit->GetNoticeCircularDetails();
        $Clean['IsActive'] = $NoticeToEdit->GetIsActive();

        $NoticeToEdit->FillNoticeCircularApplicableFor();
        $NoticeCircularApplicableFor = $NoticeToEdit->GetNoticeCircularApplicableFor();

        foreach ($NoticeCircularApplicableFor as $Key => $NoticeCircularApplicableForDetails) 
        {
            if ($NoticeCircularApplicableForDetails['ApplicableFor'] == 'Staff') 
            {
                $Clean['SelectedStaffList'][] = $NoticeCircularApplicableForDetails['StaffOrClassID'];
            }
            else if ($NoticeCircularApplicableForDetails['ApplicableFor'] == 'Class') 
            {
               $Clean['SelectedClassList'][] = $NoticeCircularApplicableForDetails['StaffOrClassID'];
            }
        }

    break;
}

require_once('../html_header.php');
?>
<title>Edit Notice & Circular</title>
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
                    <h1 class="page-header">Edit Notice & Circular</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="EditNoticeCircular" action="edit_notice_circular.php" method="post">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        Enter Notice & Circular Details
                    </div>
                    <div class="panel-body">
<?php
                        if ($HasErrors == true)
                        {
                            echo $NewRecordValidator->DisplayErrors();
                        }
?>                    
                        <div class="form-group">
                            <label for="SelectedStaffList" class="col-lg-2 control-label">Select Staff</label>
                            <div class="col-lg-5">
                                <select class="form-control" name="drdSelectedStaffList[]" id="SelectedStaffList" multiple="multiple">
<?php
                                if (is_array($StaffList) && count($StaffList) > 0)
                                {
                                    foreach ($StaffList as $StaffID => $StaffName)
                                    {
                                        echo '<option ' . (in_array($StaffID, $Clean['SelectedStaffList']) ? 'selected="selected"' : '') . ' value="' . $StaffID . '">' . $StaffName['FirstName'] . ' ' . $StaffName['LastName'] . '</option>';                                    
                                    }
                                }
?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="SelectedClassList" class="col-lg-2 control-label">Class</label>
                            <div class="col-lg-8">
                            <label class="checkbox-inline"><input class="custom-radio" type="checkbox" id="AllClasses" name="chkAllClasses" value="" />All </label>
<?php
                                foreach ($ClassList as $ClassID => $ClassName) 
                                {
?>
                                    <label class="checkbox-inline"><input class="custom-radio" type="checkbox" id="<?php echo $ClassID; ?>" name="chkSelectedClassList[]" <?php echo (in_array($ClassID, $Clean['SelectedClassList']) ? 'checked="checked"' : ''); ?> value="<?php echo $ClassID; ?>" />
                                        <?php echo $ClassName; ?>
                                    </label>
<?php
                                }
?>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="NoticeCircularDate" class="col-lg-2 control-label">Notice/Circular Date</label>
                            <div class="col-lg-5">
                                <input class="form-control select-date" type="text" maxlength="10" id="NoticeCircularDate" name="txtNoticeCircularDate" value="<?php echo $Clean['NoticeCircularDate']; ?>" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="NoticeCircularSubject" class="col-lg-2 control-label">Notice/Circular subject</label>
                            <div class="col-lg-5">
                                <input class="form-control" type="text" maxlength="100" id="NoticeCircularSubject" name="txtNoticeCircularSubject" value="<?php echo $Clean['NoticeCircularSubject']; ?>" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="NoticeCircularDetails" class="col-lg-2 control-label">Notice/Circular Details</label>
                            <div class="col-lg-9">
                                <textarea class="form-control"  id="NoticeCircularDetails" name="txtNoticeCircularDetails" rows="12"><?php echo $Clean['NoticeCircularDetails']; ?></textarea>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="IsActive" class="col-lg-2 control-label">Is Active</label>
                            <div class="col-lg-5">
                                <input type="checkbox" id="IsActive" name="chkIsActive" <?php echo ($Clean['IsActive'] == 1) ? 'checked="checked"' : ''; ?> value="1" />
                            </div>
                        </div>
                        <div class="form-group">
                        <div class="col-sm-offset-2 col-lg-10">
                            <input type="hidden" name="hdnProcess" value="3"/>
                            <input type="hidden" name="hdnNoticeCircularID" value="<?php echo $Clean['NoticeCircularID']; ?>" />
                            <button type="submit" class="btn btn-primary">Update</button>
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
<script src="../vendor/jquery-ui/jquery-ui.min.js"></script>
    <script type="text/javascript">
    $(document).ready(function() {
        $(".select-date").datepicker({
                changeMonth: true,
                changeYear: true,
                dateFormat: 'dd/mm/yy'
        });

        $("#AllClasses").change(function () {
        $("input:checkbox").prop('checked', $(this).prop("checked"));
        }); 
    });
    </script>
</body>
</html>
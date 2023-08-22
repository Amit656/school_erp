<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once("../../classes/school_administration/class.school_timing_parts_master.php");

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

if ($LoggedUser->HasPermissionForTask(TASK_EDIT_SCHOOL_TIMINGS_PARTS) !== true)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}

if (isset($_POST['btnCancel']))
{
    header('location:school_timing_parts_list.php');
    exit;
}

$Clean = array();

$Clean['SchoolTimingPartID'] = 0;

if (isset($_GET['SchoolTimingPartID']))
{
    $Clean['SchoolTimingPartID'] = (int) $_GET['SchoolTimingPartID'];
}
else if (isset($_POST['hdnSchoolTimingPartID']))
{
    $Clean['SchoolTimingPartID'] = (int) $_POST['hdnSchoolTimingPartID'];
}

if ($Clean['SchoolTimingPartID'] <= 0)
{
    header('location:../error.php');
    exit;
}   

try
{
    $TimingPartToEdit = new SchoolTimingPartsMaster($Clean['SchoolTimingPartID']);
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
    
$PartTypeList = array('Warning'=>'Warning', 'Assembly'=>'Assembly', 'Break'=>'Break', 'Class'=>'Class');

$Clean['Process'] = 0;

$Clean['TimingPart'] = '';
$Clean['PartType'] = '';

$Clean['DefaultDuration'] = 0;
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
        if (isset($_POST['txtTimingPart']))
        {
            $Clean['TimingPart'] = strip_tags(trim($_POST['txtTimingPart']));
        }

        if (isset($_POST['drdPartType']))
        {
            $Clean['PartType'] = strip_tags(trim($_POST['drdPartType']));
        }

        if (isset($_POST['txtDefaultDuration']))
        {
            $Clean['DefaultDuration'] = (int) $_POST['txtDefaultDuration'];
        }

        if (isset($_POST['txtPriority']))
        {
            $Clean['Priority'] = (int) $_POST['txtPriority'];
        }

        if (isset($_POST['chkIsActive']))
        {
            $Clean['IsActive'] = 1;
        }

        $NewRecordValidator = new Validator();

        $NewRecordValidator->ValidateStrings($Clean['TimingPart'], 'Timing part is required and should be between 3 and 50 characters.', 3, 50);
        $NewRecordValidator->ValidateInSelect($Clean['PartType'], $PartTypeList, 'Unknown error, please try again.');

        $NewRecordValidator->ValidateInteger($Clean['DefaultDuration'], 'Please Enter valid duration.', 1);
        $NewRecordValidator->ValidateInteger($Clean['Priority'], 'Please Enter valid priority.', 1);
        
        if ($NewRecordValidator->HasNotifications())
        {
            $HasErrors = true;
            break;
        }
                
        $TimingPartToEdit->SetTimingPart($Clean['TimingPart']);
        $TimingPartToEdit->SetPartType($Clean['PartType']);

        $TimingPartToEdit->SetDefaultDuration($Clean['DefaultDuration']);
        $TimingPartToEdit->SetPriority($Clean['Priority']);

        $TimingPartToEdit->SetIsActive($Clean['IsActive']);

        if ($TimingPartToEdit->RecordExists())
        {
            $NewRecordValidator->AttachTextError('The timing part you have added already exists.');
            $HasErrors = true;
            break;
        }

        if (!$TimingPartToEdit->Save())
        {
            $NewRecordValidator->AttachTextError(ProcessErrors($TimingPartToEdit->GetLastErrorCode()));
            $HasErrors = true;
            break;
        }
        
        header('location:school_timing_parts_list.php?Mode=UD');
        exit;

    break;

    case 2:
        $Clean['TimingPart'] = $TimingPartToEdit->GetTimingPart();
        $Clean['PartType'] = $TimingPartToEdit->GetPartType();

        $Clean['DefaultDuration'] = $TimingPartToEdit->GetDefaultDuration();
        $Clean['Priority'] = $TimingPartToEdit->GetPriority();

        $Clean['IsActive'] = $TimingPartToEdit->GetIsActive();

    break;
}

require_once('../html_header.php');
?>
<title>Edit Timing Parts</title>
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
                    <h1 class="page-header">Edit Timing Parts</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="AddTimingParts" action="edit_school_timing_part.php" method="post">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        Enter Timing Part Details
                    </div>
                    <div class="panel-body">
<?php
                        if ($HasErrors == true)
                        {
                            echo $NewRecordValidator->DisplayErrors();
                        }
?>                    
                        <div class="form-group">
                            <label for="TimingPart" class="col-lg-2 control-label">Timing Part</label>
                            <div class="col-lg-4">
                                <input class="form-control" type="text" maxlength="50" id="TimingPart" name="txtTimingPart" value="<?php echo $Clean['TimingPart']; ?>" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="PartType" class="col-lg-2 control-label">Part Type</label>
                            <div class="col-lg-4">
                                <select class="form-control" name="drdPartType" id="PartType">
<?php
                                foreach ($PartTypeList as $PartTypeID => $PartType) 
                                {
                                    echo '<option' . (($Clean['PartType'] == $PartTypeID) ? ' selected="selected"' : '') . ' value="' . $PartTypeID . '">' . $PartType . '</option>';
                                }
?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="DefaultDuration" class="col-lg-2 control-label">Default Duration</label>
                            <div class="col-lg-4">
                                <input class="form-control" type="text" maxlength="5" id="DefaultDuration" name="txtDefaultDuration" value="<?php echo ($Clean['DefaultDuration'] ? $Clean['DefaultDuration'] : ''); ?>" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="Priority" class="col-lg-2 control-label">Priority</label>
                            <div class="col-lg-4">
                                <input class="form-control" type="text" maxlength="5" id="Priority" name="txtPriority" value="<?php echo ($Clean['Priority'] ? $Clean['Priority'] : ''); ?>" />
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
                            <input type="hidden" name="hdnSchoolTimingPartID" value="<?php echo $Clean['SchoolTimingPartID']; ?>" />
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
<script type="text/javascript">
    $(document).ready(function() {
        $('#PartType').change(function(){
            if ($(this).val() == 'Warning')
            {
                $('#DefaultDuration').val('').prop('disabled', true);
            }
            else
            {
                $('#DefaultDuration').prop('disabled', false);
            }
        });
    });
</script>
</body>
</html>
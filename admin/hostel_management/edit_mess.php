<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once("../../classes/hostel_management/class.mess.php");

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
    header('location:mess_list.php');
    exit;
}

$Clean = array();

$Clean['MessID'] = 0;

if (isset($_GET['MessID']))
{
    $Clean['MessID'] = (int) $_GET['MessID'];
}
else if (isset($_POST['hdnMessID']))
{
    $Clean['MessID'] = (int) $_POST['hdnMessID'];
}

if ($Clean['MessID'] <= 0)
{
    header('location:../error.php');
    exit;
}   

try
{
    $MessToEdit = new Mess($Clean['MessID']);
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

$MessTypelist = array('Veg' => 'Veg', 'NonVeg' => 'NonVeg', 'Both' => 'Both');

$HasErrors = false;

$Clean['Process'] = 0;

$Clean['MessType'] = '';
$Clean['MessName'] = '';

$Clean['MonthlyFee'] = 0;
$Clean['QuarterlyFee'] = 0;
$Clean['SemiAnnualFee'] = 0;
$Clean['AnnualFee'] = 0;

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
        if (isset($_POST['txtMessName']))
        {
            $Clean['MessName'] = strip_tags(trim($_POST['txtMessName']));
        }
        if (isset($_POST['drdMessType']))
        {
            $Clean['MessType'] = strip_tags(trim($_POST['drdMessType']));
        }
        if (isset($_POST['txtMonthlyFee']))
        {
            $Clean['MonthlyFee'] = strip_tags(trim($_POST['txtMonthlyFee']));
        }
        if (isset($_POST['txtQuarterlyFee']))
        {
            $Clean['QuarterlyFee'] = strip_tags(trim($_POST['txtQuarterlyFee']));
        }
        if (isset($_POST['txtSemiAnnualFee']))
        {
            $Clean['SemiAnnualFee'] = strip_tags(trim($_POST['txtSemiAnnualFee']));
        }
        if (isset($_POST['txtAnnualFee']))
        {
            $Clean['AnnualFee'] = strip_tags(trim($_POST['txtAnnualFee']));
        }
        if (isset($_POST['chkIsActive']))
        {
            $Clean['IsActive'] = 1;
        }

        $NewRecordValidator = new Validator();

        $NewRecordValidator->ValidateStrings($Clean['MessName'], 'Mess name is required and should be between 3 and 25 characters.', 3, 25);
        $NewRecordValidator->ValidateInSelect($Clean['MessType'], $MessTypelist, 'Unknown Error, Please try again.');
        
        if ($Clean['MonthlyFee'] <= 0 && $Clean['QuarterlyFee'] <= 0 && $Clean['SemiAnnualFee'] <= 0 && $Clean['AnnualFee'] <= 0) 
        {
            $NewRecordValidator->AttachTextError('Please enter atleast one type of fee.');
            $HasErrors = true;
            break;
        }

        if ($Clean['MonthlyFee'] > 0) 
        {
            $NewRecordValidator->ValidateNumeric($Clean['MonthlyFee'], 'Invalid monthly fee, please enter valid numeric value.');
        }
        if ($Clean['QuarterlyFee'] > 0) 
        {
            $NewRecordValidator->ValidateNumeric($Clean['QuarterlyFee'], 'Invalid quarterly fee, please enter valid numeric value.');
        }
        if ($Clean['SemiAnnualFee'] > 0) 
        {
            $NewRecordValidator->ValidateNumeric($Clean['SemiAnnualFee'], 'Invalid semi annually fee, please enter valid numeric value.');
        }
        if ($Clean['AnnualFee'] > 0) 
        {
            $NewRecordValidator->ValidateNumeric($Clean['AnnualFee'], 'Invalid annually fee, please enter valid numeric value.');
        }
        
        if ($NewRecordValidator->HasNotifications())
        {
            $HasErrors = true;
            break;
        }
                
        $MessToEdit->SetMessName($Clean['MessName']);
        $MessToEdit->SetMessType($Clean['MessType']);

        $MessToEdit->SetMonthlyFee($Clean['MonthlyFee']);
        $MessToEdit->SetQuarterlyFee($Clean['QuarterlyFee']);
        $MessToEdit->SetSemiAnnualFee($Clean['SemiAnnualFee']);
        $MessToEdit->SetAnnualFee($Clean['AnnualFee']);
        $MessToEdit->SetIsActive($Clean['IsActive']);

        if (!$MessToEdit->Save())
        {
            $NewRecordValidator->AttachTextError(ProcessErrors($MessToEdit->GetLastErrorCode()));
            $HasErrors = true;

            break;
        }
        
        header('location:mess_list.php?Mode=UD');
        exit;
    break;

    case 2:
        $Clean['MessType'] = $MessToEdit->GetMessType();
        $Clean['MessName'] = $MessToEdit->GetMessName();

        $Clean['MonthlyFee'] = $MessToEdit->GetMonthlyFee();
        $Clean['QuarterlyFee'] = $MessToEdit->GetQuarterlyFee();
        $Clean['SemiAnnualFee'] = $MessToEdit->GetSemiAnnualFee();
        $Clean['AnnualFee'] = $MessToEdit->GetAnnualFee();
        
        $Clean['IsActive'] = $MessToEdit->GetIsActive();

    break;
}

require_once('../html_header.php');
?>
<title>Edit Mess</title>
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
                    <h1 class="page-header">Edit Mess</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="EditMess" action="edit_mess.php" method="post">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        Enter Mess Details
                    </div>
                    <div class="panel-body">
<?php
                        if ($HasErrors == true)
                        {
                            echo $NewRecordValidator->DisplayErrors();
                        }
?>                    
                        <div class="form-group">
                            <label for="MessName" class="col-lg-2 control-label">Mess Name</label>
                            <div class="col-lg-4">
                                <input class="form-control" type="text" maxlength="25" id="MessName" name="txtMessName" value="<?php echo $Clean['MessName']; ?>" />
                            </div>
                            <label for="MessType" class="col-lg-2 control-label">Mess Type</label>
                            <div class="col-lg-4">
                                <select class="form-control" name="drdMessType" id="MessType">
<?php
                                if (is_array($MessTypelist) && count($MessTypelist) > 0)
                                {
                                    foreach($MessTypelist as $MessTypeID => $MessTypeName)
                                    {
                                        echo '<option ' . (($Clean['MessType'] == $MessTypeID) ? 'selected="selected"' : '' ) . ' value="' . $MessTypeID . '">' . $MessTypeName . '</option>';
                                    }
                                }
?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="MonthlyFee" class="col-lg-2 control-label">Monthly Fee</label>
                            <div class="col-lg-4">
                                <div class="input-group">
                                    <input class="form-control" type="text" maxlength="10" id="MonthlyFee" name="txtMonthlyFee" value="<?php echo ($Clean['MonthlyFee']) ? $Clean['MonthlyFee'] : ''; ?>" />
                                    <span class="input-group-addon">Per student</span>
                                </div>
                            </div>
                            <label for="QuarterlyFee" class="col-lg-2 control-label">Quarterly Fee</label>
                            <div class="col-lg-4">
                                <div class="input-group">
                                    <input class="form-control" type="text" maxlength="10" id="QuarterlyFee" name="txtQuarterlyFee" value="<?php echo ($Clean['QuarterlyFee']) ? $Clean['QuarterlyFee'] : ''; ?>" />
                                    <span class="input-group-addon">Per student</span>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="SemiAnnualFee" class="col-lg-2 control-label">Semi-Annual Fee</label>
                            <div class="col-lg-4">
                                <div class="input-group">
                                    <input class="form-control" type="text" maxlength="10" id="SemiAnnualFee" name="txtSemiAnnualFee" value="<?php echo ($Clean['SemiAnnualFee']) ? $Clean['SemiAnnualFee'] : ''; ?>" />
                                    <span class="input-group-addon">Per student</span>
                                </div>
                            </div>
                            <label for="AnnualFee" class="col-lg-2 control-label">Annual Fee</label>
                            <div class="col-lg-4">
                                <div class="input-group">
                                    <input class="form-control" type="text" maxlength="10" id="AnnualFee" name="txtAnnualFee" value="<?php echo ($Clean['AnnualFee']) ? $Clean['AnnualFee'] : ''; ?>" />
                                    <span class="input-group-addon">Per student</span>
                                </div>
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
                            <input type="hidden" name="hdnMessID" value="<?php echo $Clean['MessID']; ?>" />
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
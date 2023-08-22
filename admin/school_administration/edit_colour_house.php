<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once("../../classes/school_administration/class.colour_houses.php");

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

if ($LoggedUser->HasPermissionForTask(TASK_ADD_EDIT_COLOR_HOUSE) !== true)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}

if (isset($_POST['btnCancel']))
{
    header('location:colour_houses_list.php');
    exit;
}

$Clean = array();

$Clean['ColourHouseID'] = 0;

if (isset($_GET['ColourHouseID']))
{
    $Clean['ColourHouseID'] = (int) $_GET['ColourHouseID'];
}
elseif (isset($_POST['hdnColourHouseID']))
{
    $Clean['ColourHouseID'] = (int) $_POST['hdnColourHouseID'];
}

if ($Clean['ColourHouseID'] <= 0)
{
    header('location:../error.php');
    exit;
}   

try
{
    $ColourHouseToEdit = new ColourHouse($Clean['ColourHouseID']);
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

$Clean['HouseName'] = '';
$Clean['HouseColour'] = '';

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
        if (isset($_POST['txtHouseName']))
        {
            $Clean['HouseName'] = strip_tags(trim($_POST['txtHouseName']));
        }

        if (isset($_POST['txtHouseColour']))
        {
            $Clean['HouseColour'] = strip_tags(trim($_POST['txtHouseColour']));
        }

        if (isset($_POST['chkIsActive']))
        {
            $Clean['IsActive'] = 1;
        }

        $NewRecordValidator = new Validator();
        $NewRecordValidator->ValidateStrings($Clean['HouseName'], "House Name is required and should be between 3 and 25 characters.", 3, 25);
        $NewRecordValidator->ValidateStrings($Clean['HouseColour'], "House Colour is required and should be between 3 and 15 characters.", 3, 15);
        
        if ($NewRecordValidator->HasNotifications())
        {
            $HasErrors = true;
            break;
        }

        $ColourHouseToEdit->SetHouseName($Clean['HouseName']);
        $ColourHouseToEdit->SetHouseColour($Clean['HouseColour']);
        $ColourHouseToEdit->SetIsActive($Clean['IsActive']);

        if ($ColourHouseToEdit->RecordExists())
        {
            $NewRecordValidator->AttachTextError('The house name you have added already exists.');
            $HasErrors = true;
            break;
        }

        if (!$ColourHouseToEdit->Save())
        {
            $NewRecordValidator->AttachTextError(ProcessErrors($ColourHouseToEdit->GetLastErrorCode()));
            $HasErrors = true;
            break;
        }
        
        header('location:colour_houses_list.php?Mode=UD');
        exit;
    break;

    case 2:
        $Clean['HouseName'] = $ColourHouseToEdit->GetHouseName();
        $Clean['HouseColour'] = $ColourHouseToEdit->GetHouseColour();
        $Clean['IsActive'] = $ColourHouseToEdit->GetIsActive();

    break;
}

require_once('../html_header.php');
?>
<title>Edit House</title>
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
                    <h1 class="page-header">Edit House</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="EditAcademicYear" action="edit_colour_house.php" method="post">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        Edit House Details
                    </div>
                    <div class="panel-body">
<?php
                        if ($HasErrors == true)
                        {
                            echo $NewRecordValidator->DisplayErrors();
                        }
?>                    
                        <div class="form-group">
                            <label for="HouseName" class="col-lg-2 control-label">House Name</label>
                            <div class="col-lg-4">
                                <input class="form-control" type="text" maxlength="25" id="HouseName" name="txtHouseName" value="<?php echo $Clean['HouseName']; ?>" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="HouseColour" class="col-lg-2 control-label">House Colour</label>
                            <div class="col-lg-4">
                                <input class="form-control" type="text" maxlength="15" id="HouseColour" name="txtHouseColour" value="<?php echo $Clean['HouseColour']; ?>" />
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
                            <input type="hidden" name="hdnColourHouseID" value="<?php echo $Clean['ColourHouseID']; ?>" />
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
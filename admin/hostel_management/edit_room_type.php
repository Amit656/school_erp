<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once("../../classes/hostel_management/class.room_types.php");

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
    header('location:room_type_list.php');
    exit;
}

$Clean = array();

$Clean['RoomTypeID'] = 0;

if (isset($_GET['RoomTypeID']))
{
    $Clean['RoomTypeID'] = (int) $_GET['RoomTypeID'];
}
else if (isset($_POST['hdnRoomTypeID']))
{
    $Clean['RoomTypeID'] = (int) $_POST['hdnRoomTypeID'];
}

if ($Clean['RoomTypeID'] <= 0)
{
    header('location:/admin/error.php');
    exit;
}   

try
{
    $RoomTypeToEdit = new RoomType($Clean['RoomTypeID']);
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

$Clean['RoomType'] = '';

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
        if (isset($_POST['txtRoomType']))
        {
            $Clean['RoomType'] = strip_tags(trim($_POST['txtRoomType']));
        }

        if (isset($_POST['chkIsActive']))
        {
            $Clean['IsActive'] = 1;
        }

        $NewRecordValidator = new Validator();

        $NewRecordValidator->ValidateStrings($Clean['RoomType'], 'Room type is required and should be between 3 and 50 characters.', 3, 50);
        
        if ($NewRecordValidator->HasNotifications())
        {
            $HasErrors = true;
            break;
        }
                        
        $RoomTypeToEdit->SetRoomType($Clean['RoomType']);

        $RoomTypeToEdit->SetIsActive($Clean['IsActive']);

        if (!$RoomTypeToEdit->Save())
        {
            $NewRecordValidator->AttachTextError(ProcessErrors($RoomTypeToEdit->GetLastErrorCode()));
            $HasErrors = true;

            break;
        }
        
        header('location:room_type_list.php?Mode=UD');
        exit;
    break;

    case 2:
        $Clean['RoomType'] = $RoomTypeToEdit->GetRoomType();
        
        $Clean['IsActive'] = $RoomTypeToEdit->GetIsActive();

    break;
}

require_once('../html_header.php');
?>
<title>Edit Room Type</title>
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
                    <h1 class="page-header">Edit Room Type</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="EditRoomType" action="edit_room_type.php" method="post">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        Edit Room Type
                    </div>
                    <div class="panel-body">
<?php
                        if ($HasErrors == true)
                        {
                            echo $NewRecordValidator->DisplayErrors();
                        }
?>                    
                        <div class="form-group">
                            <label for="RoomType" class="col-lg-2 control-label">Room Type</label>
                            <div class="col-lg-4">
                                <input class="form-control" type="text" maxlength="50" id="RoomType" name="txtRoomType" value="<?php echo $Clean['RoomType']; ?>" />
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
                            <input type="hidden" name="hdnRoomTypeID" value="<?php echo $Clean['RoomTypeID']; ?>" />
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
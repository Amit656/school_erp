<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once("../../classes/school_administration/class.academic_years.php");
require_once("../../classes/school_administration/class.classes.php");
require_once('../../classes/school_administration/class.class_sections.php');
require_once('../../classes/school_administration/class.section_master.php');

require_once('../../classes/school_administration/class.students.php');
require_once('../../classes/school_administration/class.student_details.php');

require_once("../../classes/transport_management/class.routes.php");
require_once("../../classes/transport_management/class.vehicle.php");
require_once("../../classes/transport_management/class.areawise_fee.php");
require_once("../../classes/transport_management/class.student_vehicle.php");

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

if ($LoggedUser->HasPermissionForTask(TASK_EDIT_STUDENT_VEHICLE) !== true)
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
    header('location:student_vehicle_report.php');
    exit;
}

$Clean = array();

$Clean['StudentVehicleID'] = 0;

if (isset($_GET['StudentVehicleID']))
{
    $Clean['StudentVehicleID'] = (int) $_GET['StudentVehicleID'];
}
else if (isset($_POST['hdnStudentVehicleID']))
{
    $Clean['StudentVehicleID'] = (int) $_POST['hdnStudentVehicleID'];
}

if ($Clean['StudentVehicleID'] <= 0)
{
    header('location:../error.php');
    exit;
}   

try
{
    $StudentVehicleToEdit = new StudentVehicle($Clean['StudentVehicleID']);
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

$RouteList = array();
$RouteList = Route::GetActiveRoutes();

$VehicleList = array();
$VehicleList = vehicle::GetActiveVehicle();

$ClassList =  array();
$ClassList = AddedClass::GetActiveClasses();

$AllSectionMasters = array();
$AllSectionMasters = SectionMaster::GetActiveSectionMasters();

$AcademicYears = array();
$AcademicYears = AcademicYear::GetAllAcademicYears();

$ClassSectionsList =  array();
$StudentsList = array();

$HasErrors = false;

$Clean['Process'] = 0;

$Clean['RouteID'] = 0;
$Clean['VehicleID'] = 0;
$Clean['AreaWiseFeeID'] = 0;

$Clean['ClassID'] = 0;
$Clean['ClassSectionID'] = 0;
$Clean['StudentID'] = 0;
$Clean['AcademicYearID'] = 0;

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
        if (isset($_POST['drdRouteID'])) 
        {
            $Clean['RouteID'] = (int) $_POST['drdRouteID'];
        }
        if (isset($_POST['drdVehicleID'])) 
        {
            $Clean['VehicleID'] = (int) $_POST['drdVehicleID'];
        }
        if (isset($_POST['drdAreaWiseFeeID'])) 
        {
            $Clean['AreaWiseFeeID'] = (int) $_POST['drdAreaWiseFeeID'];
        }     
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
        if (isset($_POST['drdAcademicYearID']))
        {
            $Clean['AcademicYearID'] = (int) $_POST['drdAcademicYearID'];
        }
        if (isset($_POST['chkIsActive']))
        {
            $Clean['IsActive'] = 1;
        }

        $NewRecordValidator = new Validator();
        
        $NewRecordValidator->ValidateInSelect($Clean['AcademicYearID'], $AcademicYears, 'Unknown error, please try again.');
        
        if ($NewRecordValidator->ValidateInSelect($Clean['RouteID'], $RouteList, 'Please select a valid Route.')) 
        {
            //$AreasList = AreaWiseFee::GetRouteAreas($Clean['RouteID']);
            $AreasList = AreaWiseFee::GetRouteAreasByAcademicYear($Clean['RouteID'], $Clean['AcademicYearID']);

            $NewRecordValidator->ValidateInSelect($Clean['AreaWiseFeeID'], $AreasList, 'Please select a valid area.');
        }

         $NewRecordValidator->ValidateInSelect($Clean['VehicleID'], $VehicleList, 'Please select a valid vehicle.');

        if ($NewRecordValidator->ValidateInSelect($Clean['ClassID'], $ClassList, 'Please select a valid class.')) 
        {
            $ClassSectionsList = ClassSections::GetClassSections($Clean['ClassID']);

            if ($NewRecordValidator->ValidateInSelect($Clean['ClassSectionID'], $ClassSectionsList, 'Please select a valid section.')) 
            {
                //$StudentsList = StudentDetail::GetStudentsByClassSectionID($Clean['ClassSectionID']);
                $StudentsList = StudentDetail::GetStudentsByClassSectionID($Clean['ClassSectionID'], 'Active', $Clean['AcademicYearID']);

                $NewRecordValidator->ValidateInSelect($Clean['StudentID'], $StudentsList, 'Please select a valid student.');
            }
        }

        if ($NewRecordValidator->HasNotifications())
        {
            $HasErrors = true;
            break;
        }

        $StudentVehicleToEdit->SetAreaWiseFeeID($Clean['AreaWiseFeeID']); 
        $StudentVehicleToEdit->SetVehicleID($Clean['VehicleID']); 
        $StudentVehicleToEdit->SetStudentID($Clean['StudentID']);
        $StudentVehicleToEdit->SetAcademicYearID($Clean['AcademicYearID']);
        $StudentVehicleToEdit->SetIsActive($Clean['IsActive']);
        
        if ($StudentVehicleToEdit->RecordExists())
        {
            $NewRecordValidator->AttachTextError('This student is already assigned to a Vehicle in this academic year, if you want to change vehicle, please use edit option.');
            $HasErrors = true;
            break;
        }
        
        if (!$StudentVehicleToEdit->Save())
        {
            $NewRecordValidator->AttachTextError(ProcessErrors($StudentVehicleToEdit->GetLastErrorCode()));
            $HasErrors = true;

            break;
        }
        
        header('location:student_vehicle_report.php?Mode=UD');
        exit;
    break;

    case 2:
        $Clean['StudentID'] = $StudentVehicleToEdit->GetStudentID();

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

        $Clean['AreaWiseFeeID'] = $StudentVehicleToEdit->GetAreaWiseFeeID();
        $Clean['AcademicYearID'] = $StudentVehicleToEdit->GetAcademicYearID();

        $NewAreaWiseFee = new AreaWiseFee($Clean['AreaWiseFeeID']);

        $Clean['RouteID'] = $NewAreaWiseFee->GetRouteID();
        $AreasList = AreaWiseFee::GetRouteAreasByAcademicYear($Clean['RouteID'], $Clean['AcademicYearID']);
        //$AreasList = AreaWiseFee::GetRouteAreas($Clean['RouteID']);
        $ClassSectionDetails = new ClassSections($StudentDetailObject->GetClassSectionID());
        $Clean['ClassID'] = $ClassSectionDetails->GetClassID();

        $Clean['VehicleID'] = $StudentVehicleToEdit->GetVehicleID();
        $Clean['ClassSectionID'] = $StudentDetailObject->GetClassSectionID();

        //$StudentsList = StudentDetail::GetStudentsByClassSectionID($Clean['ClassSectionID']);
        $StudentsList = StudentDetail::GetStudentsByClassSectionID($Clean['ClassSectionID'], 'Active', $Clean['AcademicYearID']);

        $ClassSectionDetails = new ClassSections($StudentDetailObject->GetClassSectionID());
        $Clean['ClassID'] = $ClassSectionDetails->GetClassID();

        $ClassSectionsList = ClassSections::GetClassSections($Clean['ClassID']);

        $Clean['IsActive'] = $StudentVehicleToEdit->GetIsActive();

    break;
}

require_once('../html_header.php');
?>
<title>Edit Assign Vehicle To Student</title>
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
                    <h1 class="page-header">Edit Assign Vehicle To Student</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="EditStudentVehicle" action="edit_student_vehicle.php" method="post">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        Enter Student Vehicle Details
                    </div>
                    <div class="panel-body">
<?php
                        if ($HasErrors == true)
                        {
                            echo $NewRecordValidator->DisplayErrors();
                        }
?>                    

                       <div class="form-group">
                            <label for="AcademicYearID" class="col-lg-2 control-label">Academic Session</label>
                            <div class="col-lg-3">
                                <select class="form-control" name="drdAcademicYearID" id="AcademicYearID">
                                    <?php
                                    if (is_array($AcademicYears) && count($AcademicYears) > 0)
                                    {
                                        foreach ($AcademicYears as $AcademicYearID => $AcademicYearDetails)
                                        {
                                            echo '<option ' . ($Clean['AcademicYearID'] == $AcademicYearID ? 'selected="selected"' : '') . ' value="' . $AcademicYearID . '">' . date('Y', strtotime($AcademicYearDetails['StartDate'])) . ' - ' . date('Y', strtotime($AcademicYearDetails['EndDate'])) . '</option>';
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                            <label for="RouteID" class="col-lg-2 control-label">Route</label>
                                <div class="col-lg-3">
                                    <select class="form-control"  name="drdRouteID" id="Route">
                                    <option  value="0" >-- Select Route --</option>
<?php
                                if (is_array($RouteList) && count($RouteList) > 0)
                                {
                                    foreach($RouteList as $RouteID => $RouteNumber)
                                    {
    ?>
                                            <option <?php echo ($RouteID == $Clean['RouteID'] ? 'selected="selected"' : ''); ?> value="<?php echo $RouteID; ?>"><?php echo $RouteNumber; ?></option>
    <?php
                                        }
                                    }
?>
                                    </select>
                                </div>
                        </div>
                        <div class="form-group">

                            <label for="AreaWiseFeeID" class="col-lg-2 control-label">Area</label>
                                <div class="col-lg-3">
                                <select class="form-control"  name="drdAreaWiseFeeID" id="AreaWiseFeeID">
                                    <option  value="0" >-- Select Area --</option>
<?php
                                if (is_array($AreasList) && count($AreasList) > 0)
                                {
                                    foreach($AreasList as $AreaWiseFeeID => $AreaName)
                                    {
                                                echo '<option ' . ($Clean['AreaWiseFeeID'] == $AreaWiseFeeID ? 'selected="selected"' : '') . ' value="' . $AreaWiseFeeID . '">' . $AreaName . '</option>' ;
                                            }
                                        }
?>
                                    </select>
                                </div>
                            <label for="VehicleID" class="col-lg-2 control-label">Vehicle</label>
                            <div class="col-lg-3"> 
                                 <select class="form-control"  name="drdVehicleID" id="Vehicles">
                                    <option  value="0" >-- Select Vehicles --</option>
<?php
                                if (is_array($VehicleList) && count($VehicleList) > 0)
                                {
                                    foreach($VehicleList as $VehicleID => $vehicleName)
                                    {
                                            echo '<option ' . (($Clean['VehicleID'] == $VehicleID) ? 'selected="selected"' : '' ) . ' value="' . $VehicleID . '">' . $vehicleName . '</option>';
                                    }
                                }
?>
                                </select>

                            </div>
                            </div >

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
                            <div class="col-lg-3">
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
                            <label for="IsActive" class="col-lg-2 control-label">Is Active</label>
                            <div class="col-lg-4">
                                <input type="checkbox" id="IsActive" name="chkIsActive" <?php echo ($Clean['IsActive'] == 1) ? 'checked="checked"' : ''; ?> value="1" />
                            </div>
                        </div>
                        <div class="form-group">
                        <div class="col-sm-offset-2 col-lg-10">
                            <input type="hidden" name="hdnProcess" value="3"/>
                            <input type="hidden" name="hdnStudentVehicleID" value="<?php echo $Clean['StudentVehicleID']; ?>" />
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
    $ (document).ready (function ()
        {

            $ ('#AcademicYearID').change (function ()
            {
                $ ('#AreaWiseFeeID').html ('<option value="0">-- Select Area --</option>');
                
                $ ('#ClassSection').html ('<option value="0">-- Select Section --</option>');
                $ ('#Student').html ('<option value="0">-- Select Student --</option>');

                $.post ("/xhttp_calls/get_classes.php", {}, function ( data )
                {
                    ResultArray = data.split ("|*****|");

                    if (ResultArray[0] == 'error')
                    {
                        alert (ResultArray[1]);
                        return false;
                    }
                    else
                    {
                        $ ('#Class').html ('<option value="0">-- Select Class --</option>' + ResultArray[1]);
                    }
                });

                $.post ("/xhttp_calls/get_routes.php", {}, function ( data )
                {
                    ResultArray = data.split ("|*****|");

                    if (ResultArray[0] == 'error')
                    {
                        alert (ResultArray[1]);
                        return false;
                    }
                    else
                    {
                        $ ('#Route').html ('<option value="0">-- Select Route --</option>' + ResultArray[1]);
                    }
                });
            });

            $ ('#Class').change (function ()
            {

                var ClassID = parseInt ($ (this).val ());
                $ ('#Student').html ('<option value="0">-- Select Student --</option>');

                if (ClassID <= 0)
                {
                    $ ('#ClassSection').html ('<option value="0">-- Select Section --</option>');
                    return;
                }

                $.post ("/xhttp_calls/get_sections_by_classs.php", {SelectedClassID: ClassID}, function ( data )
                {
                    ResultArray = data.split ("|*****|");

                    if (ResultArray[0] == 'error')
                    {
                        alert (ResultArray[1]);
                        return false;
                    }
                    else
                    {
                        $ ('#ClassSection').html ('<option value="0">-- Select Section --</option>' + ResultArray[1]);
                    }
                });
            });

            $ ('#ClassSection').change (function ()
            {

                var ClassSectionID = parseInt ($ (this).val ());
                var AcademicYearID = parseInt ($ ('#AcademicYearID').val ());

                if (AcademicYearID <= 0)
                {
                    alert ('Please select academic year!')
                    return false;

                }

                if (ClassSectionID <= 0)
                {
                    $ ('#Student').html ('<option value="0">-- Select Student --</option>');
                    return;
                }

                $.post ("/xhttp_calls/get_students_by_class_section.php", {SelectedClassSectionID: ClassSectionID, SelectedAcademicYearID: AcademicYearID}, function ( data )
                {
                    ResultArray = data.split ("|*****|");

                    if (ResultArray[0] == 'error')
                    {
                        alert (ResultArray[1]);
                        return false;
                    }
                    else
                    {
                        $ ('#Student').html ('<option value="0">-- Select Student --</option>' + ResultArray[1]);
                    }
                });
            });

            $ ('#Route').change (function ()
            {
                var RouteID = parseInt ($ (this).val ());
                var AcademicYearID = parseInt ($ ('#AcademicYearID').val ());
                
                if (RouteID <= 0)
                {
                    $ ('#AreaWiseFeeID').html ('<option value="0">-- Select Area --</option>');
                    return;
                }

                $.post ("/xhttp_calls/get_areas_by_routes.php", {SelectedRouteID: RouteID, SelectedAcademicYearID: AcademicYearID}, function ( data )
                {
                    ResultArray = data.split ("|*****|");

                    if (ResultArray[0] == 'error')
                    {
                        alert (ResultArray[1]);   
                        $ ('#AreaWiseFeeID').html ('<option value="0">-- Select Area --</option>');                  
                        return false;
                    }
                    else
                    {
                        $('#AreaWiseFeeID').html ( ResultArray[1]);
                    }
                });
            });
    });
</script>
</body>
</html>
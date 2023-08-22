<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once("../../classes/school_administration/class.classes.php");
require_once('../../classes/school_administration/class.class_sections.php');
require_once('../../classes/school_administration/class.section_master.php');

require_once('../../classes/school_administration/class.students.php');
require_once('../../classes/school_administration/class.student_details.php');

require_once("../../classes/hostel_management/class.wings.php");
require_once("../../classes/hostel_management/class.room_types.php");
require_once("../../classes/hostel_management/class.rooms.php");
require_once("../../classes/hostel_management/class.mess.php");
require_once("../../classes/hostel_management/class.student_hostel_allotment.php");

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

$ClassList =  array();
$ClassList = AddedClass::GetActiveClasses();

$AllSectionMasters = array();
$AllSectionMasters = SectionMaster::GetActiveSectionMasters();

$ClassSectionsList =  array();
$StudentsList = array();

$Winglist = array();
$Winglist = Wing::GetActiveWings();

$RoomTypelist = array();
$RoomTypelist = RoomType::GetActiveRoomTypes();

$MessTypelist = array('Veg' => 'Veg', 'NonVeg' => 'NonVeg', 'Both' => 'Both');

$Messlist = array();

$AvailableRooms = array();

$HasErrors = false;

$Clean = array();
$Clean['Process'] = 0;

$Clean['ClassID'] = 0;
$Clean['ClassSectionID'] = 0;
$Clean['StudentID'] = 0;

$Clean['WingID'] = 0;
$Clean['RoomTypeID'] = 0;
$Clean['RoomID'] = 0;

$Clean['MessType'] = '';
$Clean['MessID'] = 0;

if (isset($_POST['hdnProcess']))
{
	$Clean['Process'] = (int) $_POST['hdnProcess'];
}
switch ($Clean['Process'])
{
	case 1:
		if (isset($_POST['hdnClassID'])) 
		{
			$Clean['ClassID'] = (int) $_POST['hdnClassID'];
		}
		if (isset($_POST['hdnClassSectionID'])) 
		{
			$Clean['ClassSectionID'] = (int) $_POST['hdnClassSectionID'];
		}
		if (isset($_POST['hdnStudentID']))
		{
			$Clean['StudentID'] = (int) $_POST['hdnStudentID'];
		}
		if (isset($_POST['hdnWingID']))
		{
			$Clean['WingID'] = (int) $_POST['hdnWingID'];
		}
		if (isset($_POST['hdnRoomTypeID']))
		{
			$Clean['RoomTypeID'] = (int) $_POST['hdnRoomTypeID'];
		}
		if (isset($_POST['drdMessType']))
		{
			$Clean['MessType'] = strip_tags(trim($_POST['drdMessType']));
		}
		if (isset($_POST['drdMess']))
		{
			$Clean['MessID'] = (int) $_POST['drdMess'];
		}
		if (isset($_POST['chkRoomID']))
		{
			$Clean['RoomID'] = (int) $_POST['chkRoomID'];
		}		

		$NewRecordValidator = new Validator();

		if ($NewRecordValidator->ValidateInSelect($Clean['ClassID'], $ClassList, 'Please select a valid class.')) 
		{
			$ClassSectionsList = ClassSections::GetClassSections($Clean['ClassID']);

			if ($NewRecordValidator->ValidateInSelect($Clean['ClassSectionID'], $ClassSectionsList, 'Please select a valid section.')) 
			{
				$StudentsList = StudentDetail::GetStudentsByClassSectionID($Clean['ClassSectionID']);

				$NewRecordValidator->ValidateInSelect($Clean['StudentID'], $StudentsList, 'Please select a valid student.');
			}
		}

		$NewRecordValidator->ValidateInSelect($Clean['WingID'], $Winglist, 'Please select a valid wing.');
		$NewRecordValidator->ValidateInSelect($Clean['RoomTypeID'], $RoomTypelist, 'Please select a valid room type.');

		$AvailableRooms = Room::GetAvailableRooms($Clean['WingID'], $Clean['RoomTypeID']);

		$NewRecordValidator->ValidateInSelect($Clean['RoomID'], $AvailableRooms, 'Please select a valid room.');

		if ($Clean['MessType'] != '') 
		{
			if($NewRecordValidator->ValidateInSelect($Clean['MessType'], $MessTypelist, 'Please select a valid mess type.'))
			{
				$Messlist = Mess::GetMessByType($Clean['MessType']);

				$NewRecordValidator->ValidateInSelect($Clean['MessID'], $Messlist, 'Please select a valid mess.');
			}
		}
		
		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}
				
		$NewStudentHostelAllotment = new StudentHostelAllotment();
				
		$NewStudentHostelAllotment->SetStudentID($Clean['StudentID']);
		$NewStudentHostelAllotment->SetRoomID($Clean['RoomID']);
		$NewStudentHostelAllotment->SetMessID($Clean['MessID']);

		$NewStudentHostelAllotment->SetIsActive(1);
		$NewStudentHostelAllotment->SetCreateUserID($LoggedUser->GetUserID());

		if (!$NewStudentHostelAllotment->Save())
		{
			$NewRecordValidator->AttachTextError(ProcessErrors($NewStudentHostelAllotment->GetLastErrorCode()));
			$HasErrors = true;
			break;
		}
		
		header('location:student_hostel_allotment.php?Mode=AS');
		exit;
	break;

	case 7:
		$NewRecordValidator = new Validator();

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
		if (isset($_POST['drdWing'])) 
		{
			$Clean['WingID'] = (int) $_POST['drdWing'];
		}
		if (isset($_POST['drdRoomType'])) 
		{
			$Clean['RoomTypeID'] = (int) $_POST['drdRoomType'];
		}

		if ($NewRecordValidator->ValidateInSelect($Clean['ClassID'], $ClassList, 'Please select a valid class.')) 
		{
			$ClassSectionsList = ClassSections::GetClassSections($Clean['ClassID']);

			if ($NewRecordValidator->ValidateInSelect($Clean['ClassSectionID'], $ClassSectionsList, 'Please select a valid section.')) 
			{
				$StudentsList = StudentDetail::GetStudentsByClassSectionID($Clean['ClassSectionID']);

				$NewRecordValidator->ValidateInSelect($Clean['StudentID'], $StudentsList, 'Please select a valid student.');
			}
		}

		$NewRecordValidator->ValidateInSelect($Clean['WingID'], $Winglist, 'Please select a valid wing.');
		$NewRecordValidator->ValidateInSelect($Clean['RoomTypeID'], $RoomTypelist, 'Please select a valid room type.');

		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}

		$MessTypelist = array('Veg' => 'Veg', 'NonVeg' => 'NonVeg', 'Both' => 'Both');
		$AvailableRooms = Room::GetAvailableRooms($Clean['WingID'], $Clean['RoomTypeID']);

	break;
}

$LandingPageMode = '';
if (isset($_GET['Mode']))
{
    $LandingPageMode = $_GET['Mode'];
}

require_once('../html_header.php');
?>
<title>Allot Hostel To Student</title>
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
                    <h1 class="page-header">Allot Hostel To Student</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="AddWing" action="student_hostel_allotment.php" method="post">
            	<div class="panel panel-default">
                    <div class="panel-heading">
                        Enter Hostel Allotment Details
                    </div>
                    <div class="panel-body">
<?php
						if ($HasErrors == true)
						{
							echo $NewRecordValidator->DisplayErrors();
						}
						else if ($LandingPageMode == 'AS')
	                    {
	                        echo '<div class="alert alert-success">Record saved successfully.</div>';
	                    }
?>                    
                    	<div class="form-group">
                            <label for="Class" class="col-lg-2 control-label">Class List</label>
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
                            <label for="ClassSection" class="col-lg-2 control-label">Section List</label>
                            <div class="col-lg-3">
                                <select class="form-control" name="drdClassSection" id="ClassSection">
                                    <option value="0">-- Select Section --</option>
<?php
                                        if (is_array($ClassSectionsList) && count($ClassSectionsList) > 0)
                                        {
                                            foreach ($ClassSectionsList as $ClassSectionID => $SectionMasterID) 
                                            {
                                                echo '<option ' . ($Clean['ClassSectionID'] == $ClassSectionID ? 'selected="selected"' : '') . ' value="' . $ClassSectionID . '">' . $AllSectionMasters[$SectionMasterID] . '</option>' ;
                                            }
                                        }
?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="Student" class="col-lg-2 control-label">Student</label>
                            <div class="col-lg-8">
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
							<label for="Wing" class="col-lg-2 control-label">Wing</label>
                            <div class="col-lg-3">
                            	<select class="form-control" name="drdWing" id="Wing">
                            		<option value="">-- Select Wing --</option>
<?php
								if (is_array($Winglist) && count($Winglist) > 0)
								{
									foreach($Winglist as $WingID => $WingName)
									{
										echo '<option ' . (($Clean['WingID'] == $WingID) ? 'selected="selected"' : '' ) . ' value="' . $WingID . '">' . $WingName . '</option>';
									}
								}
?>
								</select>
                            </div>
                            <label for="RoomType" class="col-lg-2 control-label">Room Type</label>
                            <div class="col-lg-3">
                            	<select class="form-control" name="drdRoomType" id="RoomType">
                            		<option value="">-- Select Room Type --</option>
<?php
								if (is_array($RoomTypelist) && count($RoomTypelist) > 0)
								{
									foreach($RoomTypelist as $RoomTypeID => $RoomType)
									{
										echo '<option ' . (($Clean['RoomTypeID'] == $RoomTypeID) ? 'selected="selected"' : '' ) . ' value="' . $RoomTypeID . '">' . $RoomType . '</option>';
									}
								}
?>
								</select>
                            </div>
                        </div>
						                        
                        <div class="form-group">
	                        <div class="col-sm-offset-2 col-lg-10">
	                        	<input type="hidden" name="hdnProcess" value="7" />
								<button type="submit" class="btn btn-primary"><i class="fa fa-search"></i>&nbsp;Search Available Room</button>
	                        </div>
                      	</div>
                    </div>
            </form>

<?php
				if ($Clean['Process'] == 1 || $Clean['Process'] == 7 && $Clean['StudentID'] > 0) 
				{

?>	
					<form class="form-horizontal" name="Allotment" id="RecordForm" action="student_hostel_allotment.php" method="post">
						<div class="form-group"> 
							<label for="MessType" class="col-lg-2 control-label">Mess Type</label>
                            <div class="col-lg-3">
                            	<select class="form-control" name="drdMessType" id="MessType">
                            		<option value="">-- Select Mess Type --</option>
<?php
								if (is_array($MessTypelist) && count($MessTypelist) > 0)
								{
									foreach($MessTypelist as $MessTypeID => $MessType)
									{
										echo '<option ' . (($Clean['MessType'] == $MessTypeID) ? 'selected="selected"' : '' ) . ' value="' . $MessTypeID . '">' . $MessType . '</option>';
									}
								}
?>
								</select>
                            </div>
							<label for="Mess" class="col-lg-2 control-label">Mess</label>
                            <div class="col-lg-3">
                            	<select class="form-control" name="drdMess" id="Mess">
                            		<option value="0">-- Select Mess --</option>
<?php
								if (is_array($Messlist) && count($Messlist) > 0)
								{
									foreach($Messlist as $MessID => $MessName)
									{
										echo '<option ' . (($Clean['MessID'] == $MessID) ? 'selected="selected"' : '' ) . ' value="' . $MessID . '">' . $MessName . '</option>';
									}
								}
?>
								</select>
                            </div>
                        </div>
                        <div class="row" id="RecordTable">
                            <div class="col-lg-12">
                                <table width="100%" class="table table-striped table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th>S. No.</th>
                                            <th>Room Name</th>
                                            <th>Total Bed</th>
                                            <th>Available Bed</th>
                                            <th>Select</th>
                                        </tr>
                                    </thead>
                                    <tbody>
<?php
                                    if (is_array($AvailableRooms) && count($AvailableRooms) > 0)
                                    {
                                        $Counter = 0;
                                        foreach ($AvailableRooms as $RoomID => $RoomDetails)
                                        {
?>
                                                <tr>
                                                    <td><?php echo ++$Counter; ?></td>
                                                    <td><?php echo $RoomDetails['RoomName']; ?></td>
                                                    <td><?php echo $RoomDetails['BedCount']; ?></td>
                                                    <td><?php echo $RoomDetails['FreeSpace']; ?></td>
                                                    <td>
                                                    	<?php echo '<input class="custom-radio RoomCheckbox" type="checkbox" id="' . $RoomID . '" name="chkRoomID" '. (($Clean['RoomID'] == $RoomID) ? 'checked="checked"' : '') . ' value="' . $RoomID . '" />'; ?>
                                                    </td>
                                                </tr>
    <?php
                                        }
                                    }
                                    else
                                    {
    ?>
                                                <tr>
                                                    <td colspan="5">No Records</td>
                                                </tr>
    <?php
                                    }
    ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="form-group">
	                        <div class="col-sm-offset-2 col-lg-10">
	                        	<input type="hidden" name="hdnClassID" value="<?php echo $Clean['ClassID'] ;?>" />
	                        	<input type="hidden" name="hdnClassSectionID" value="<?php echo $Clean['ClassSectionID'] ;?>" />
	                        	<input type="hidden" name="hdnStudentID" value="<?php echo $Clean['StudentID'] ;?>" />
	                        	<input type="hidden" name="hdnWingID" value="<?php echo $Clean['WingID'] ;?>" />
	                        	<input type="hidden" name="hdnRoomTypeID" value="<?php echo $Clean['RoomTypeID'] ;?>" />
	                        	<input type="hidden" name="hdnProcess" value="1" />
								<button type="submit" class="btn btn-primary"><i class="fa fa-check"></i>&nbsp;Allot</button>
	                        </div>
                        </div>

                    </form>
                    </div>
<?php 				
				}
?>
        </div>
        <!-- /#page-wrapper -->

    </div>
    <!-- /#wrapper -->
<?php
require_once('../footer.php');
?>
<script type="text/javascript">
$(document).ready(function(){
	$('#Class').change(function(){

        var ClassID = parseInt($(this).val());
        $('#Student').html('<option value="0">-- Select Student --</option>');
        
        if (ClassID <= 0)
        {
            $('#ClassSection').html('<option value="0">-- Select Section --</option>');
            return;
        }
        
        $.post("/xhttp_calls/get_sections_by_classs.php", {SelectedClassID:ClassID}, function(data)
        {
            ResultArray = data.split("|*****|");
            
            if (ResultArray[0] == 'error')
            {
                alert (ResultArray[1]);
                return false;
            }
            else
            {
                $('#ClassSection').html('<option value="0">-- Select Section --</option>' + ResultArray[1]);
            }
        });
    });

    $('#ClassSection').change(function(){

        var ClassSectionID = parseInt($(this).val());
        
        if (ClassSectionID <= 0)
        {
            $('#Student').html('<option value="0">-- Select Student --</option>');
            return;
        }
        
        $.post("/xhttp_calls/get_students_by_class_section.php", {SelectedClassSectionID:ClassSectionID}, function(data)
        {
            ResultArray = data.split("|*****|");
            
            if (ResultArray[0] == 'error')
            {
                alert (ResultArray[1]);
                return false;
            }
            else
            {
                $('#Student').html('<option value="0">-- Select Student --</option>' + ResultArray[1]);
            }
        });
    });

    $('#MessType').change(function(){

        var MessType = $(this).val();
        
        if (MessType <= 0)
        {
            $('#Mess').html('<option value="0">-- Select Mess --</option>');
            return;
        }
        
        $.post("/xhttp_calls/get_mess_by_mess_type.php", {SelectedMessType:MessType}, function(data)
        {
            ResultArray = data.split("|*****|");
            
            if (ResultArray[0] == 'error')
            {
                alert (ResultArray[1]);
                return false;
            }
            else
            {
                $('#Mess').html(ResultArray[1]);
            }
        });
    });

    $(".RoomCheckbox").change(function() {
	    var checked = $(this).is(':checked');

	    $(".RoomCheckbox").prop('checked',false);
	    if(checked) 
	    {
	        $(this).prop('checked',true);
	    }
	});
});
</script>
</body>
</html>
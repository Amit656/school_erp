<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once('../../classes/school_administration/class.academic_years.php');

require_once("../../classes/school_administration/class.classes.php");
require_once('../../classes/school_administration/class.class_sections.php');
require_once('../../classes/school_administration/class.section_master.php');

require_once('../../classes/school_administration/class.students.php');
require_once('../../classes/school_administration/class.student_details.php');

require_once("../../classes/fee_management/class.fee_groups.php");

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
    header('location:fee_group_list.php');
    exit;
}

$Clean = array();

$Clean['AcademicYearID'] = 0;
$Clean['FeeGroupID'] = 0;
$Clean['ClassID'] = 0;
$Clean['ClassSectionID'] = 0;

$AcademicYears =  array();
$AcademicYears = AcademicYear::GetAllAcademicYears();

$AcademicYearID = 0;
$AcademicYearID = AcademicYear::GetCurrentAcademicYear($AcademicYearName);

$FeeGrouplist = array();
$FeeGrouplist = FeeGroup::GetActiveFeeGroups();

$ClassList =  array();
$ClassList = AddedClass::GetActiveClasses();

$ClassSectionsList =  array();
$ClassSectionsList = ClassSections::GetClassSections($Clean['ClassID']);

$StudentsList = array();
$RecordList = array();

$HasSearchErrors = false;
$HasErrors = false;

$Clean['Process'] = 0;

$Clean['RecordIDList'] = array();

foreach ($FeeGrouplist as $FeeGroupID => $FeeGroup) 
{
    $Clean['RecordIDList'][$FeeGroupID] = array();
}

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
    case 1:     
        if (isset($_POST['chkRecordIDList']) && is_array($_POST['chkRecordIDList']))
        {
            $Clean['RecordIDList'] = $_POST['chkRecordIDList'];
        }
        
        if (isset($_POST['hdnAcademicYearID'])) 
		{
			$Clean['AcademicYearID'] = (int) $_POST['hdnAcademicYearID'];
		}
        
        if (isset($_POST['hdnClassID'])) 
        {
            $Clean['ClassID'] = (int) $_POST['hdnClassID'];
        }

        if (isset($_POST['hdnClassSectionID'])) 
        {
            $Clean['ClassSectionID'] = (int) $_POST['hdnClassSectionID'];
        }
        
        $StudentsList = StudentDetail::GetStudentsByClassSectionID($Clean['ClassSectionID'], 'Active', $Clean['AcademicYearID']);

        $NewRecordValidator = new Validator();
        
        $ErrorCounter = 0;
        
        $RecordIDList = array();

        foreach ($FeeGrouplist as $FeeGroupID => $FeeGroup) 
        {
            $AssignRecord = new FeeGroup($FeeGroupID);

            $AssignRecord->FillAssignedRecordsToFeeGroup();

            $RecordIDList[$FeeGroupID] = $AssignRecord->GetRecordIDList();
        }
        
        foreach ($Clean['RecordIDList'] as $StudentID => $FeeGroupID) 
        {
            $NewRecordValidator->ValidateInSelect($StudentID, $StudentsList, 'Unknown error, please try again.');

            // $RecordList[$FeeGroupID][$StudentID] = $StudentID;

            foreach ($RecordIDList as $PreviousFeeGroupID => $DataArray) 
            {
                if (in_array($StudentID, $DataArray)) 
                {
                    $RecordList[$FeeGroupID][$StudentID] = $PreviousFeeGroupID;
                }
            }
        }

        if ($NewRecordValidator->HasNotifications())
        {
            $HasErrors = true;
            break;
        }

        $AssignRecord = new FeeGroup();        

        if (!$AssignRecord->AssignFeeGroup($RecordList, $Clean['ClassID'], $Clean['AcademicYearID'], $LoggedUser->GetUserID()))
        {
            $NewRecordValidator->AttachTextError('Error in executing query.');
            $HasErrors = true;
            
            foreach ($FeeGrouplist as $FeeGroupID => $FeeGroup) 
            {
                $AssignRecord = new FeeGroup($FeeGroupID);

                $AssignRecord->FillAssignedRecordsToFeeGroup();

                $Clean['RecordIDList'][$FeeGroupID] = $AssignRecord->GetRecordIDList();
            }
            break;
        }
        
        header('location:assigned_student_wise_fee_group.php?Mode=UD');
        exit;
    break;

    case 7:
        
        if (isset($_POST['drdAcademicYear'])) 
		{
			$Clean['AcademicYearID'] = (int) $_POST['drdAcademicYear'];
		}
		else if (isset($_GET['AcademicYearID'])) 
		{
			$Clean['AcademicYearID'] = (int) $_GET['AcademicYearID'];
		}
		
        if (isset($_POST['drdClassID'])) 
        {
            $Clean['ClassID'] = (int) $_POST['drdClassID'];
        }

        if (isset($_POST['drdClassSectionID']))
        {
            $Clean['ClassSectionID'] = (int) $_POST['drdClassSectionID'];
        }
        
        $ClassSectionsList = ClassSections::GetClassSections($Clean['ClassID']);

        $NewRecordValidator = new Validator();

        $NewRecordValidator->ValidateInSelect($Clean['ClassSectionID'], $ClassSectionsList, 'Unknown error, please try again.');

        if ($NewRecordValidator->HasNotifications())
        {
            $HasSearchErrors = true;
            break;
        }

        foreach ($FeeGrouplist as $FeeGroupID => $FeeGroup) 
        {
            $AssignRecord = new FeeGroup($FeeGroupID);

            $AssignRecord->FillAssignedRecordsToFeeGroup();

            $Clean['RecordIDList'][$FeeGroupID] = $AssignRecord->GetRecordIDList();
        }
        
        $StudentsList = StudentDetail::GetStudentsByClassSectionID($Clean['ClassSectionID'], 'Active', $Clean['AcademicYearID']);
    break;
}

$LandingPageMode = '';
if (isset($_GET['Mode']))
{
    $LandingPageMode = $_GET['Mode'];
}

require_once('../html_header.php');
?>
<title>Assign Student Wise Fee Group</title>
<!-- DataTables CSS -->
<link href="../vendor/datatables-plugins/dataTables.bootstrap.css" rel="stylesheet">

<!-- DataTables Responsive CSS -->
<link href="../vendor/datatables-responsive/dataTables.responsive.css" rel="stylesheet">
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
                    <h1 class="page-header">Assign Student Wise Fee Group</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="AddFeeGroup" action="assigned_student_wise_fee_group.php" method="post">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <strong>Assign Fee Group To Student</strong>
                    </div>
                    <div class="panel-body">
<?php
                        if ($HasSearchErrors == true)
                        {
                            echo $NewRecordValidator->DisplayErrors();
                        }
                        else if ($LandingPageMode == 'UD')
                        {
                            echo '<div class="alert alert-success alert-top-margin">Record updated successfully.</div><br>';
                        }
?>                                    
                        <div class="form-group">
                            <label for="AcademicYear" class="col-lg-2 control-label">Academic Year</label>
                            <div class="col-lg-3">
                            	<!--<input class="form-control" type="text" maxlength="10" id="AcademicYear" name="txtAcademicYear" readonly="readonly" value="<?php echo $AcademicYearName; ?>" />-->
                            	<select class="form-control" name="drdAcademicYear" id="AcademicYearID">
<?php
                                if (is_array($AcademicYears) && count($AcademicYears) > 0)
                                {
                                    foreach ($AcademicYears as $AcademicYearID => $AcademicYearDetails)
                                    {
                                        if ($Clean['AcademicYearID'] == 0)
                                        {
                                            if ($AcademicYearDetails['IsCurrentYear'] == 1)
                                            {
                                                $Clean['AcademicYearID'] = $AcademicYearID;   
                                            }
                                        }
                                        
                                        echo '<option ' . ($Clean['AcademicYearID'] == $AcademicYearID ? 'selected="selected"' : '') . ' value="' . $AcademicYearID . '">' . date('Y', strtotime($AcademicYearDetails['StartDate'])) .' - '. date('Y', strtotime($AcademicYearDetails['EndDate'])) . '</option>';
                                    }
                                }
?>
								</select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="ClassList" class="col-lg-2 control-label">Class</label>
                            <div class="col-lg-3">
                                <select class="form-control"  name="drdClassID" id="ClassID">
                                    <option  value="0" >Select Class</option>
<?php
                                    foreach ($ClassList as $ClassID => $Class)
                                    {
?>
                                        <option <?php echo ($ClassID == $Clean['ClassID'] ? 'selected="selected"' : ''); ?> value="<?php echo $ClassID; ?>"><?php echo $Class; ?></option>
<?php
                                    }
?>
                                </select>
                            </div>
                            <label for="SectionID" class="col-lg-2 control-label">Section</label>
                            <div class="col-lg-3">
                                <select class="form-control" name="drdClassSectionID" id="SectionID">
                                    <option value="0">Select Section</option>
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
                        <div class="col-sm-offset-2 col-lg-10">                            
                            <input type="hidden" name="hdnProcess" value="7"/>
                            <button type="submit" class="btn btn-primary">View Students</button>
                        </div>
                      </div>
                    </div>
<?php
                    if ($HasErrors == true)
                    {
                        echo $NewRecordValidator->DisplayErrors();
                    }
?>
                </div>
            </form>
<?php
        if($Clean['Process'] == 7 && $HasSearchErrors == false || $Clean['Process'] == 1)
        {
?>
            <form class="form-horizontal" name="form" action="assigned_student_wise_fee_group.php" method="post">
                <div class="panel panel-default">
                    <div class="panel-body" >
                        <div class="row" id="RecordTable" style="overflow-x:auto;">
                            <div class="col-lg-12" >
                                <table width="100%" class="table table-striped table-bordered table-hover" id="DataTableRecords">
                                    <thead>
                                        <tr>
                                            <th>S. No.</th>
                                            <th>Student Name</th>
        <?php
                                        foreach ($FeeGrouplist as $FeeGroupID => $FeeGroup) 
                                        {
                                            echo '<th>'. $FeeGroup .'</th>';
                                        }
        ?>                                    
                                        </tr>
                                    </thead>
                                    <tbody>
        <?php
                                    if (is_array($StudentsList) && count($StudentsList) > 0)
                                    {
                                        $Counter = 0;
                                        foreach($StudentsList as $StudentID => $StudentsDetails)
                                        {
        ?>
                                            <tr>
                                                <td><?php echo ++$Counter; ?></td>
                                                <td><?php echo $StudentsDetails['FirstName'] .' '. $StudentsDetails['LastName'] . '(' . $StudentsDetails['RollNumber'] . ')' ; ?></td>
        <?php
                                        foreach ($FeeGrouplist as $FeeGroupID => $FeeGroup) 
                                        {                                    
                                            echo '<td><input class="custom-radio" type="radio" id="RecordID' . $FeeGroupID . $StudentID . '" name="chkRecordIDList['. $StudentID .']" value="' . $FeeGroupID . '" '.(in_array($StudentID, $Clean['RecordIDList'][$FeeGroupID]) ? 'checked="checked"' : '').' /></td>';
                                        }
        ?>                                            
                                                
                                            </tr>
        <?php
                                        }
                                    }
        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-lg-12">                         
                        <div class="form-group">
                            <div class="col-sm-offset-2 col-lg-10">
                                <input type="hidden" name="hdnAcademicYearID" value="<?php echo $Clean['AcademicYearID']; ?>" />
                                <input type="hidden" name="hdnFeeGroupID" value="<?php echo $Clean['FeeGroupID']; ?>" />
                                <input type="hidden" name="hdnClassID" value="<?php echo $Clean['ClassID']; ?>" />
                                <input type="hidden" name="hdnClassSectionID" value="<?php echo $Clean['ClassSectionID']; ?>" />
                                <input type="hidden" name="hdnProcess" value="1" />
                                <button type="submit" class="btn btn-primary">Save</button>
                            </div>
                        </div>
                    </div>
                </div>  
<?php                
            }
?>                                                    
                             
            </form>
        </div>
        <!-- /#page-wrapper -->

    </div>
    <!-- /#wrapper -->
<?php
require_once('../footer.php');
?>
<script src="/admin/vendor/jquery-ui/jquery-ui.min.js"></script>
<script src="../vendor/datatables/js/jquery.dataTables.min.js"></script>
<script src="../vendor/datatables-plugins/dataTables.bootstrap.min.js"></script>
<script src="../vendor/datatables-responsive/dataTables.responsive.js"></script>   
<script type="text/javascript">
$(document).ready(function(){

    $('#DataTableRecords').DataTable({
        responsive: false,
        bPaginate: false,
        bSort: false,
        searching: false, 
        info: false,
        scrollX: true
    });

    $('#ClassID').change(function() {
        var ClassID = parseInt($(this).val());
        
        if (ClassID <= 0)
        {
            $('#SectionID').html('<option value="0">Select Section</option>');
            return;
        }
        
        $.post("/xhttp_calls/get_sections_by_classs.php", {SelectedClassID:ClassID}, function(data) {
            ResultArray = data.split("|*****|");
            
            if (ResultArray[0] == 'error')
            {
                alert (ResultArray[1]);
                return false;
            }
            else
            {
                $('#SectionID').html(ResultArray[1]);
            }
        });
    }); 

});
</script>
</body>
</html>
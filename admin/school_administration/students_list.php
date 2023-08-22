<?php
require_once("../../classes/class.users.php");
require_once('../../classes/class.validation.php');
require_once("../../classes/class.authentication.php");

require_once("../../classes/school_administration/class.academic_years.php");
require_once("../../classes/school_administration/class.classes.php");
require_once("../../classes/school_administration/class.section_master.php");
require_once("../../classes/school_administration/class.class_sections.php");

require_once("../../classes/school_administration/class.students.php");
require_once("../../classes/school_administration/class.student_details.php");

require_once("../../classes/class.helpers.php");

require_once("../../classes/class.ui_helpers.php");
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

if ($LoggedUser->HasPermissionForTask(TASK_LIST_STUDENT) !== true)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}

$HasErrors = false;
$RecordDeletedSuccessfully = false;

$BloodGroupList = array('A+'=>'A+', 'A-'=>'A-', 'B+'=>'B+', 'AB+'=>'AB+', 'AB-'=>'AB-', 'O+'=>'O+', 'O-'=>'O-');
$GenderList = array('Male'=>'Male', 'Female'=>'Female', 'Others'=>'Others');
$CategoryList = array('General'=>'General', 'OBC'=>'OBC', 'SC'=>'SC', 'ST'=>'ST');
$OtherCategoryList = array('isEWS'=>'EWS', 'hasDisability'=>'Disability', 'isSingleGirl'=>'SingleGirl');
$StudentStatusList = array('Active' => 'Active', 'InActive' => 'InActive', 'Suspended' =>'Suspended', 'Terminated' =>'Terminated', 'Passout' => 'Passout');

$AcademicYears =  array();
$AcademicYears = AcademicYear::GetAllAcademicYears();

$ClassList =  array();
$ClassList = AddedClass::GetAllClasses(true);

$SectionList =  array();
$SectionList = SectionMaster::GetAllSectionMasters(true);

$ClassSectionsList =  array();

$AllStudents = array();
        
$Clean = array();
$Clean['Process'] = 0;

$Clean['AcademicYearID'] = 0;
$Clean['StudentID'] = 0;

$Clean['ClassID'] = 0;
$Clean['ClassSectionID'] = 0;

$Clean['Gender'] = array();
$Clean['Category'] = array();
$Clean['Other'] = array();
$Clean['BloodGroup'] = array();

$Clean['StudentName'] = '';
$Clean['FatherName'] = '';
$Clean['Status'] = 'Active';

// paging and sorting variables start here  //
$Clean['AllRecords'] = '';
$Clean['CurrentPage'] = 1;
$TotalPages = 0;
$TotalRecords = 0;

$Start = 0;
$Limit = 10;
// end of paging variables      //

if (isset($_GET['Process']))
{
	$Clean['Process'] = (int) $_GET['Process'];
}
else if (isset($_GET['hdnProcess'])) 
{
    $Clean['Process'] = (int) $_GET['hdnProcess'];
}
switch ($Clean['Process'])
{
	case 5:
		if ($LoggedUser->HasPermissionForTask(TASK_DELETE_STUDENT) !== true)
		{
			header('location:unauthorized_login_admin.php');
			exit;
		}
		
		if (isset($_GET['StudentID']))
		{
			$Clean['StudentID'] = (int) $_GET['StudentID'];
		}
		
		if ($Clean['StudentID'] <= 0)
		{
			header('location:../error_page.php');
			exit;
		}						
			
		try
		{
			$StudentToDelete = new StudentDetail($Clean['StudentID']);
		}
		catch (ApplicationDBException $e)
		{
			header('location:../error_page.php');
			exit;
		}
		catch (Exception $e)
		{
			header('location:../error_page.php');
			exit;
		}
		
		$RecordValidator = new Validator();
		
		if ($StudentToDelete->CheckDependencies())
		{
			$RecordValidator->AttachTextError('This student cannot be deleted. There are dependent records for this student.');
			$HasErrors = true;
			break;
		}
				
		if (!$StudentToDelete->Remove())
		{
			$RecordValidator->AttachTextError(ProcessErrors($StudentToDelete->GetLastErrorCode()));
			$HasErrors = true;
			break;
		}
		
// 		if (!Helpers::ShiftClassRollNumbers($Clean['ClassSectionID']))
//         {
//             error_log('Criticle Error: There was a error while shifting the roll numbers.');
//         }
		
		$RecordDeletedSuccessfully = true;
	break;

    case 7:
        if (isset($_GET['drdAcademicYear']))
        {
            $Clean['AcademicYearID'] = (int) $_GET['drdAcademicYear'];
        }
        elseif (isset($_GET['AcademicYearID']))
        {
            $Clean['AcademicYearID'] = (int) $_GET['AcademicYearID'];
        }
        
        if (isset($_GET['drdClassID']))
        {
            $Clean['ClassID'] = (int) $_GET['drdClassID'];
        }
        elseif (isset($_GET['ClassID']))
        {
            $Clean['ClassID'] = (int) $_GET['ClassID'];
        }

        if (isset($_GET['drdClassSectionID']))
        {
            $Clean['ClassSectionID'] = (int) $_GET['drdClassSectionID'];
        }
        elseif (isset($_GET['ClassSectionID']))
        {
            $Clean['ClassSectionID'] = (int) $_GET['ClassSectionID'];
        }

        if (isset($_GET['txtGender']))
        {
            $Clean['Gender'] = $_GET['txtGender'];
        }
        elseif (isset($_GET['Gender']))
        {
            $Clean['Gender'] = $_GET['Gender'];
        }

        if (isset($_GET['txtCategory']))
        {
            $Clean['Category'] = $_GET['txtCategory'];
        }
        elseif (isset($_GET['Category']))
        {
            $Clean['Category'] = $_GET['Category'];
        }

        if (isset($_GET['txtOther']))
        {
            $Clean['Other'] = $_GET['txtOther'];
        }
        elseif (isset($_GET['Other']))
        {
            $Clean['Other'] = $_GET['Other'];
        }

        if (isset($_GET['txtBloodGroup']))
        {
            $Clean['BloodGroup'] = $_GET['txtBloodGroup'];
        }
        elseif (isset($_GET['BloodGroup']))
        {
            $Clean['BloodGroup'] = $_GET['BloodGroup'];
        }
        
        if (isset($_GET['txtStudentName']))
        {
            $Clean['StudentName'] = strip_tags(trim($_GET['txtStudentName']));
        }
        else if (isset($_GET['StudentName']))
        {
            $Clean['StudentName'] = strip_tags(trim( (string) $_GET['StudentName']));
        }
        
        if (isset($_GET['txtFatherName']))
        {
            $Clean['FatherName'] = strip_tags(trim($_GET['txtFatherName']));
        }
        else if (isset($_GET['FatherName']))
        {
            $Clean['FatherName'] = strip_tags(trim( (string) $_GET['FatherName']));
        }
        
        if (isset($_GET['optStatus']))
        {
            $Clean['Status'] =  strip_tags(trim( (string) $_GET['optStatus']));
        }
        elseif (isset($_GET['Status']))
        {
            $Clean['Status'] =  strip_tags(trim( (string) $_GET['Status']));
        }

        $RecordValidator = new Validator();
        
        $RecordValidator->ValidateInSelect($Clean['AcademicYearID'], $AcademicYears, 'Unknown error, please try again.');
        
        if ($Clean['ClassID'] != 0)
        {
            if ($RecordValidator->ValidateInSelect($Clean['ClassID'], $ClassList, 'Unknown error, please try again.'))
            {
                $ClassSectionsList = ClassSections::GetClassSections($Clean['ClassID']);
                
                if ($Clean['ClassSectionID'] > 0) 
                {
                    $RecordValidator->ValidateInSelect($Clean['ClassSectionID'], $ClassSectionsList, 'Unknown error, please try again.');
                }
            }
        }

        if (!empty($Clean['Gender']))
        {
            foreach ($Clean['Gender'] as $Gender) 
            {
                $RecordValidator->ValidateInSelect($Gender, $GenderList, 'Unknown Error in gender, Please try again.');
            }
        }

        if (!empty($Clean['Category']))
        {
            foreach ($Clean['Category'] as $Category) 
            {
                $RecordValidator->ValidateInSelect($Category, $CategoryList, 'Unknown Error in category, Please try again.');
            }
        }

        if (!empty($Clean['Other']))
        {
            foreach ($Clean['Other'] as $Other) 
            {
                $RecordValidator->ValidateInSelect($Other, $OtherCategoryList, 'Unknown Error in other category, Please try again.');

                if ($Other == 'isEWS') 
                {
                    $Key = 'CheckEWS';
                }
                else if ($Other == 'hasDisability') 
                {
                    $Key = 'CheckDisability';
                }
                else if ($Other == 'isSingleGirl') 
                {
                    $Key = 'CheckSingleGirl';
                }

                $Filters['Other'][$Key] = 1;
            }
        }

        if (!empty($Clean['BloodGroup']))
        {
            foreach ($Clean['BloodGroup'] as $BloodGroup) 
            {
                $RecordValidator->ValidateInSelect($BloodGroup, $BloodGroupList, 'Unknown Error in blood group, Please try again.');
            }
        }
        
        if ($Clean['StudentName'] != '')
        {
            $RecordValidator->ValidateStrings($Clean['StudentName'], 'Student name should be between 1 and 30 characters.', 1, 30);
        }
        
        if ($Clean['FatherName'] != '')
        {
            $RecordValidator->ValidateStrings($Clean['FatherName'], 'Father name should be between 1 and 30 characters.', 1, 30);
        }
        
        if ($Clean['Status'] != '')
        {
            $RecordValidator->ValidateInSelect($Clean['Status'], $StudentStatusList, 'Unknown Error in status, Please try again.');
        }

        if ($RecordValidator->HasNotifications())
        {
            $HasErrors = true;
            break;
        }
        
        $Filters['AcademicYearID'] = $Clean['AcademicYearID'];
        $Filters['ClassID'] = $Clean['ClassID'];
        $Filters['ClassSectionID'] = $Clean['ClassSectionID'];
        $Filters['Gender'] = $Clean['Gender'];
        $Filters['Category'] = $Clean['Category'];
        $Filters['BloodGroup'] = $Clean['BloodGroup'];
        $Filters['StudentName'] = $Clean['StudentName'];
        $Filters['FatherName'] = $Clean['FatherName'];
        $Filters['Status'] = $Clean['Status'];

        //get records count
        $TotalStudents = StudentDetail::GetAllStudents($TotalRecords, true, $Filters);

        if ($TotalRecords > 0)
        {
            // Paging and sorting calculations start here.
            $TotalPages = (($TotalRecords % $Limit) == 0) ? $TotalRecords / $Limit : floor($TotalRecords / $Limit) + 1;

            if (isset($_GET['CurrentPage']))
            {
                $Clean['CurrentPage'] = (int) $_GET['CurrentPage'];
            }
            
            if (isset($_GET['AllRecords']))
            {
                $Clean['AllRecords'] = (string) $_GET['AllRecords'];
            }

            if ($Clean['CurrentPage'] <= 0)
            {
                $Clean['CurrentPage'] = 1;
            }
            elseif ($Clean['CurrentPage'] > $TotalPages)
            {
                $Clean['CurrentPage'] = $TotalPages;
            }

            if ($Clean['CurrentPage'] > 1)
            {
                $Start = ($Clean['CurrentPage'] - 1) * $Limit;
            }
            // end of Paging and sorting calculations.
            // now get the actual  records
            if (isset($_GET['report_submit']) && $_GET['report_submit'] == 2)
            {
                require_once('../excel/student_list_download_xls.php');
            }
            else if (isset($_GET['print_report']) && $_GET['print_report'] == 2)
            {
                $Limit = $TotalRecords;
                $AllStudents = StudentDetail::GetAllStudents($TotalRecords, false, $Filters, $Start, $Limit);
                
                require_once('../report_print/print_students_list.php');
                exit;
            }
            else if ($Clean['AllRecords'] == 'All') 
            {
                $AllStudents = StudentDetail::GetAllStudents($TotalRecords, false, $Filters, 0, $TotalRecords);
            }
            else
            {
                $AllStudents = StudentDetail::GetAllStudents($TotalRecords, false, $Filters, $Start, $Limit);
            }
        }
    break;
}

$LandingPageMode = '';
if (isset($_GET['Mode']))
{
    $LandingPageMode = $_GET['Mode'];
}

require_once('../html_header.php');
?>
<title>Students</title>
<!-- DataTables CSS -->
<link href="../vendor/datatables-plugins/dataTables.bootstrap.css" rel="stylesheet">

<!-- DataTables Responsive CSS -->
<link href="../vendor/datatables-responsive/dataTables.responsive.css" rel="stylesheet">
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
                    <h1 class="page-header">Students</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
            <!-- /.row -->
            <form class="form-horizontal" id="FormSearchReport" name="AddStudent" action="students_list.php" method="get">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <strong>Apply Filters </strong>
                    </div>
                    <div class="panel-body">
<?php
                        if ($HasErrors == true)
                        {
                            echo $RecordValidator->DisplayErrors();
                        }
?>                    
                        <div class="form-group">
                            <label for="AcademicYearID" class="col-lg-2 control-label">Academic Session</label>
                            <div class="col-lg-2">
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
                            <label for="ClassID" class="col-lg-2 control-label">Class</label>
                            <div class="col-lg-2">
                                <select class="form-control" name="drdClassID" id="ClassID">
                                    <option value="0">Select Class</option>
<?php
                                if (is_array($ClassList) && count($ClassList) > 0)
                                {
                                    foreach ($ClassList as $ClassID => $ClassName) {
                                        echo '<option '.($Clean['ClassID'] == $ClassID ? 'selected="selected"' : '').' value="'.$ClassID.'">'.$ClassName.'</option>';
                                    }
                                }
?>
                                </select>
                            </div>
                            <label for="ClassSectionID" class="col-lg-2 control-label">Section</label>
                            <div class="col-lg-2">
                                <select class="form-control" name="drdClassSectionID" id="ClassSectionID">
                                    <option value="0">Select Section</option>
<?php
                                if (is_array($ClassSectionsList) && count($ClassSectionsList) > 0)
                                {
                                    foreach ($ClassSectionsList as $ClassSectionID => $SectionName) 
                                    {
                                        echo '<option '.($Clean['ClassSectionID'] == $ClassSectionID ? 'selected="selected"' : '').' value="'.$ClassSectionID.'">'.$SectionName.'</option>';
                                    }
                                }
?>
                                </select>
                            </div>

                            <label for="Gender" class="col-lg-2 control-label">Gender</label>
                            <div class="col-lg-2">
                                <select class="form-control" name="txtGender[]" id="Gender" multiple="multiple">
<?php
                                if (is_array($GenderList) && count($GenderList) > 0)
                                {
                                    $SelectedText = '';
                                    foreach ($GenderList as $Gender => $GenderName)
                                    {
                                        if (count($Clean['Gender']) > 0) 
                                        {
                                            foreach ($Clean['Gender'] as $GenderValue)
                                             {
                                                if ($GenderValue == $Gender) 
                                                {
                                                    $SelectedText = 'selected="selected"';
                                                }
                                            }
                                        }
                                        
                                        echo '<option '.$SelectedText.' value="'.$Gender.'">'.$GenderName.'</option>';
                                        $SelectedText = '';
                                    }
                                }
?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="Category" class="col-lg-2 control-label">Category</label>
                            <div class="col-lg-2">
                                <select class="form-control" name="txtCategory[]" id="Category" multiple="multiple">
<?php
                                if (is_array($CategoryList) && count($CategoryList) > 0)
                                {
                                    foreach ($CategoryList as $CategoryValue => $CategoryName) 
                                    {
                                        if (count($Clean['Category']) > 0) 
                                        {
                                            foreach ($Clean['Category'] as $Category)
                                             {
                                                if ($CategoryValue == $Category) 
                                                {
                                                    $SelectedText = 'selected="selected"';
                                                }
                                            }
                                        }
                                        echo '<option '.$SelectedText.' value="'.$CategoryValue.'">'.$CategoryName.'</option>';
                                        $SelectedText = '';
                                    }
                                }
?>
                                </select>
                            </div>
                            <label for="OtherCategory" class="col-lg-2 control-label">Other Category</label>
                            <div class="col-lg-2">
                                <select class="form-control" name="txtOther[]" id="Other" multiple="multiple">
<?php
                                if (is_array($OtherCategoryList) && count($OtherCategoryList) > 0)
                                {
                                    foreach ($OtherCategoryList as $OtherCategoryValue => $OtherCategoryName) 
                                    {
                                        if (count($Clean['Other']) > 0) 
                                        {
                                            foreach ($Clean['Other'] as $OtherCategory)
                                             {
                                                if ($OtherCategoryValue == $OtherCategory) 
                                                {
                                                    $SelectedText = 'selected="selected"';
                                                }
                                            }
                                        }
                                        echo '<option '.$SelectedText.' value="'.$OtherCategoryValue.'">'.$OtherCategoryName.'</option>';                                     
                                        $SelectedText = '';
                                    }
                                }
?>
                                </select>
                            </div>
                            <label for="BloodGroup" class="col-lg-2 control-label">Blood Group</label>
                            <div class="col-lg-2">
                                <select class="form-control" name="txtBloodGroup[]" id="BloodGroup" multiple="multiple">
<?php
                                if (is_array($BloodGroupList) && count($BloodGroupList) > 0)
                                {
                                    foreach ($BloodGroupList as $BloodGroupID => $BloodGroupName) 
                                    {
                                        if (count($Clean['BloodGroup']) > 0) 
                                        {
                                            foreach ($Clean['BloodGroup'] as $BloodGroup)
                                             {
                                                if ($BloodGroupID == $BloodGroup) 
                                                {
                                                    $SelectedText = 'selected="selected"';
                                                }
                                            }
                                        }
                                        echo '<option '.$SelectedText.' value="'.$BloodGroupID.'">'.$BloodGroupName.'</option>';
                                        
                                        $SelectedText = '';
                                    }
                                }
?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="StudentName" class="col-lg-2 control-label">By Name</label>
                            <div class="col-lg-4">
                                <input class="form-control" type="text" maxlength="50" id="StudentName" name="txtStudentName" value="<?php echo $Clean['StudentName']; ?>" />
                            </div>
                             <label for="FatherName" class="col-lg-2 control-label">By Father Name</label>
                            <div class="col-lg-4">
                                <input class="form-control" type="text" maxlength="50" id="FatherName" name="txtFatherName" value="<?php echo $Clean['FatherName']; ?>" />
                            </div>
                        </div> 
                        <div class="form-group">
                            <label for="Status" class="col-lg-2 control-label">Student Status</label>
                            <div class="col-lg-5">
                                <label class="col-sm-4"><input class="custom-radio" type="radio" id="All" name="optStatus" value="" <?php echo ($Clean['Status'] == '' ? 'checked="checked"' : ''); ?> >&nbsp;&nbsp;All</label>
<?php
                            foreach ($StudentStatusList as $StatusKey => $StatusName)
                            {
?>
                                <label class="col-sm-4"><input class="custom-radio" type="radio" id="<?php echo $StatusKey;?>" name="optStatus" value="<?php echo $StatusKey;?>" <?php echo (($Clean['Status'] == $StatusKey) ? 'checked="checked"' : ''); ?> >&nbsp;&nbsp;<?php echo $StatusName;?></label>
<?php
                            }
?>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="col-sm-offset-5 col-lg-7">
                                <input type="hidden" name="hdnProcess" value="7"/>
                                 <input type="hidden" name="report_submit" id="get_excel" value="0" />
                                <input type="hidden" name="print_report" id="print_report" value="0" />
                                <button type="submit" class="btn btn-primary" id="SubmitSearch">Search</button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
<?php
        if ($Clean['Process'] == 7 && $HasErrors == false)
        {
            $ReportHeaderText = '';

            if ($Clean['AcademicYearID'] != 0)
            {
                $ReportHeaderText .= ' Session: ' . date('Y', strtotime($AcademicYears[$Clean['AcademicYearID']]['StartDate'])) .' - '. date('Y', strtotime($AcademicYears[$Clean['AcademicYearID']]['EndDate'])) . ',';
            }
            
            if ($Clean['ClassID'] != 0)
            {
                $ReportHeaderText .= ' Class: ' . $ClassList[$Clean['ClassID']] . ',';
            }

            if ($Clean['ClassSectionID'] != 0)
            {
                $ReportHeaderText .= ' Section: ' . $ClassSectionsList[$Clean['ClassSectionID']] . ',';
            }

            if (!empty($Clean['Gender']) && is_array($Clean['Gender']))
            {
                $Genders = '';
                foreach ($Clean['Gender'] as $Gender) 
                {
                    $Genders .= $Gender . ', ';
                }

                $ReportHeaderText .= ' Gender: ' . $Genders;
            }

            if (!empty($Clean['Category']) && is_array($Clean['Category']))
            {
                $Categories = '';
                foreach ($Clean['Category'] as $Category) 
                {
                    $Categories .= $Category . ', ';
                }

                $ReportHeaderText .= ' Category: ' . $Categories;
            }

            if (!empty($Clean['Other']) && is_array($Clean['Other']))
            {
                $OtherCategories = '';
                foreach ($Clean['Other'] as $OtherCategory) 
                {
                    $OtherCategories .= $OtherCategoryList[$OtherCategory] . ', ';
                }

                $ReportHeaderText .= ' Other Category: ' . $OtherCategories;
            }

            if (!empty($Clean['BloodGroup']) && is_array($Clean['BloodGroup']))
            {
                $BloodGroups = '';
                foreach ($Clean['BloodGroup'] as $BloodGroup) 
                {
                    $BloodGroups .= $BloodGroup . ', ';
                }

                $ReportHeaderText .= ' Blood Group: ' . $BloodGroups;
            }

            if ($Clean['StudentName'] != '')
            {
                $ReportHeaderText .= ' Student Name: ' . $Clean['StudentName'] . ',';
            }
            
            if ($Clean['FatherName'] != '')
            {
                $ReportHeaderText .= ' Father Name: ' . $Clean['FatherName'] . ',';
            }
            
            if ($Clean['Status'] != '')
            {
                $ReportHeaderText .= ' Status: ' . $Clean['Status'] . ',';
            }

            if ($ReportHeaderText != '')
            {
                $ReportHeaderText = ' for' . rtrim($ReportHeaderText, ',');
            }
?>
            <div class="row">
                <div class="col-lg-12">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <strong>Total Records Returned: <?php echo $TotalRecords; ?></strong>
                        </div>
                        <!-- /.panel-heading -->
                        <div class="panel-body">
                            <div>
                                <div class="row">
                                    <div class="col-lg-3">
                                    <div class="add-new-btn-container"><a href="add_student.php" class="btn btn-primary" role="button">Add New Student</a></div>

                                    </div>
                                    <div class="col-lg-6">
<?php
                                    if ($TotalPages > 1)
                                    {

                                        $AllParameters = array('Process' => '7', 'AcademicYearID' => $Clean['AcademicYearID'], 'ClassID' => $Clean['ClassID'], 'ClassSectionID' => $Clean['ClassSectionID'], 'Gender' => $Clean['Gender'], 'Category' => $Clean['Category'], 'Other' => $Clean['Other'], 'BloodGroup' => $Clean['BloodGroup'], 'StudentName' => $Clean['StudentName'], 'FatherName' => $Clean['FatherName'], 'Status' => $Clean['Status']);

                                        echo UIHelpers::GetPager('students_list.php', $TotalPages, $Clean['CurrentPage'], $AllParameters);
                                    }
?>  
                                    </div>
                                    <!--<div class="col-lg-3">-->
                                    <!--    <div class="print-btn-container"><button id="PrintButton" type="submit" class="btn btn-primary">Print</button></div>-->
                                    <!--</div>-->
                                      <div class="col-lg-3">
                                        <div class="print-btn-container">
                                            <button id="" onclick="$('#print_report').val(2);$('#FormSearchReport').attr('target', '_blank');$('#SubmitSearch').click();$('#print_report').val(0);$('#FormSearchReport').attr('target', '');" type="submit" class="btn btn-primary">Print</button>
                                            <button id="" onclick="$('#get_excel').val(2); $('#SubmitSearch').click();$('#get_excel').val(0);" type="submit" class="btn btn-primary">Export to Excel</button>
                                        </div>  
                                      </div>
                                </div>
<?php
                            if ($HasErrors == true)
                            {
                                echo $RecordValidator->DisplayErrorsInTable();
                            }
                            elseif ($RecordDeletedSuccessfully == true)
                            {
                                echo '<div class="alert alert-success alert-top-margin">The record was deleted successfully.</div>';
                            }
                            else if ($LandingPageMode == 'AS')
                            {
                                echo '<div class="alert alert-success alert-top-margin">Record saved successfully.</div>';
                            }
                            else if ($LandingPageMode == 'DD')
                            {
                                echo '<div class="alert alert-success alert-top-margin">Record deleted successfully.</div>';
                            }
                            else if ($LandingPageMode == 'UD')
                            {
                                echo '<div class="alert alert-success alert-top-margin">Record Updated successfully.</div>';
                            }
?>
                                <div class="row" id="RecordTableHeading">
                                    <div class="col-lg-12">
                                    	<div class="report-heading-container"><strong>Students on <?php echo date('d-m-Y h:i A'); ?></strong></div>
                                    </div>
								</div>
                                <div class="row" id="RecordTable">
                                    <div class="col-lg-12">
                                        <table width="100%" class="table table-striped table-bordered table-hover" id="DataTableRecords">
                                            <thead>
                                                <tr>
                                                    <th>S. No</th>
                                                    <th>Sr. No</th>
                                                    <th>Student Name</th>
                                                    <th>Roll Number</th>
                                                    <th>Class</th>
                                                     <th>Status</th>
                                                    <th>Father Name</th>
                                                    <th>Mother Name</th>
                                                    <th>Contact</th>
                                                    <th>User Name</th>
                                                    <th>Gender</th>
                                                    <th>Dob</th>
                                                    <th>Category</th>
                                                    <th>Fee Code</th>
                                                    <th>Create User</th>
                                                    <th>Create Date</th>
                                                    <th class="print-hidden">Operations</th>
                                                    <!--<th class="print-hidden">Certificates</th>-->
                                                </tr>
                                            </thead>
                                            <tbody>
<?php
                                    if (is_array($AllStudents) && count($AllStudents) > 0)
                                    {
                                        $Counter = $Start;
                                        foreach ($AllStudents as $StudentID => $StudentDetails)
                                        {
?>
                                            <tr>
                                                <td><?php echo ++$Counter; ?></td>
                                                <td><?php echo $StudentDetails['EnrollmentID']; ?></td>
                                                <td><?php echo $StudentDetails['FirstName'].' '.$StudentDetails['LastName']; ?></td>
                                                <td><?php echo $StudentDetails['RollNumber']; ?></td>
                                                <td><?php echo $StudentDetails['ClassSymbol'].' '.$StudentDetails['SectionName']; ?></td>
                                                <td><?php echo $StudentDetails['Status']. (($StudentDetails['Status'] == 'InActive') ? (' ('. date('d/m/Y', strtotime($StudentDetails['DateFromInActive'])) .') '): ''); ?></td>
                                                <td><?php echo $StudentDetails['FatherFirstName'].' '.$StudentDetails['FatherLastName']; ?></td>
                                                <td><?php echo $StudentDetails['MotherFirstName'].' '.$StudentDetails['MotherLastName']; ?></td>
                                                <td><?php echo $StudentDetails['FatherMobileNumber'].'<br>'.$StudentDetails['MotherMobileNumber']; ?></td>
                                                <td><?php echo $StudentDetails['UserName']; ?></td>
                                                <td><?php echo $StudentDetails['Gender']; ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($StudentDetails['Dob'])); ?></td>
                                                <td><?php echo $StudentDetails['Category']; ?></td>
                                                <td><?php echo $StudentDetails['FeeCode']; ?></td>
                                                <td><?php echo $StudentDetails['CreateUserName']; ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($StudentDetails['CreateDate'])); ?></td>
                                                <td class="print-hidden">
<?php
                                                if ($LoggedUser->HasPermissionForTask(TASK_EDIT_STUDENT) === true)
                                                {
                                                    echo '<a href="edit_student.php?Process=2&amp;StudentID='.$StudentID.'">View</a>';
                                                }
                                                else
                                                {
                                                    echo 'View';
                                                }

                                                echo '&nbsp;|&nbsp;';

                                                if ($LoggedUser->HasPermissionForTask(TASK_EDIT_STUDENT) === true)
                                                {
                                                    echo '<a href="edit_student.php?Process=2&amp;StudentID='.$StudentID.'">Edit</a>';
                                                }
                                                else
                                                {
                                                    echo 'Edit';
                                                }

                                                echo '&nbsp;|&nbsp;';

                                                if ($LoggedUser->HasPermissionForTask(TASK_DELETE_STUDENT) === true)
                                                {
                                                    echo '<a class="delete-record" href="students_list.php?Process=5&amp;StudentID='.$StudentID.'">Delete</a>';
                                                }
                                                else
                                                {
                                                    echo 'Delete';
                                                }

                                                echo '&nbsp;|&nbsp;<a href="student_status_change_log.php?StudentID='.$StudentID.'">View Status Change Log</a>';
?>
                                                </td>
                                                <!--<td class="print-hidden">-->
                                                <!--    <div class="btn-group">-->
                                                <!--        <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown">Certificate-->
                                                <!--          <span class="caret"></span>-->
                                                <!--        </button>-->
                                                <!--        <ul class="dropdown-menu" role="menu">-->
                                                <!--          <li><a href="certificate_formats/age_certificate.php?Process=2&amp;StudentID=<?php echo $StudentID;?>" target="_blank">Age</a></li>-->
                                                <!--          <li><a href="certificate_formats/character_certificate.php?Process=2&amp;StudentID=<?php echo $StudentID;?>" target="_blank">Character</a></li>-->
                                                <!--          <li><a href="certificate_formats/study_certificate.php?Process=2&amp;StudentID=<?php echo $StudentID;?>" target="_blank">Study</a></li>-->
                                                <!--          <li><a href="certificate_formats/transfer_certificate.php?Process=2&amp;StudentID=<?php echo $StudentID;?>" target="_blank">TC</a></li>-->
                                                <!--        </ul>-->
                                                <!--    </div>-->
                                                <!--</td>-->
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
                            <!-- /.table-responsive -->
                        </div>
                        <!-- /.panel-body -->
                    </div>
                    <!-- /.panel -->
                </div>
                <!-- /.col-lg-12 -->
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
	$(document).ready(function() {
		$(".delete-task").click(function()
        {	
            if (!confirm("Are you sure to delete this student ?"))
            {
                return false;
            }
        });
	});

    $('#ClassID').change(function(){
        var ClassID = parseInt($(this).val());
        
        if (ClassID <= 0)
        {
            $('#ClassSectionID').html('<option value="0">Select Section</option>');
            return;
        }
        
        $.post("/xhttp_calls/get_sections_by_classs.php", {SelectedClassID:ClassID}, function(data){
        
            ResultArray = data.split("|*****|");
            
            if (ResultArray[0] == 'error')
            {
                alert (ResultArray[1]);
                return false;
            }
            else
            {
                $('#ClassSectionID').html(ResultArray[1]);
            }
         });
    });
    </script>
	<!-- JavaScript To Print A Report -->
    <script src="/admin/js/print-report.js"></script>
    
    <!-- DataTables JavaScript -->
<script src="../vendor/datatables/js/jquery.dataTables.min.js"></script>
<script src="../vendor/datatables-plugins/dataTables.bootstrap.min.js"></script>
<script src="../vendor/datatables-responsive/dataTables.responsive.js"></script>   
<script type="text/javascript">
    
$(document).ready(function() {
    // $("body").on('click', '.delete-record', function()
    // {   
    //     if (!confirm("Are you sure you want to delete this Record?"))
    //     {
    //         return false;
    //     }
    // });

    $('#DataTableRecords').DataTable({
        responsive: true,
        bPaginate: false,
        bSort: false,
        searching: false, 
        info: false
    });
    
    $('body .dropdown-toggle').dropdown(); 
});
</script>
</body>
</html> 

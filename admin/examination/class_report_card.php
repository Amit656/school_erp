<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once('../../classes/school_administration/class.students.php');
require_once('../../classes/school_administration/class.student_details.php');
require_once("../../classes/school_administration/class.parent_details.php");
require_once("../../classes/school_administration/class.classes.php");
require_once('../../classes/school_administration/class.class_sections.php');
require_once('../../classes/school_administration/class.academic_years.php');
require_once("../../classes/school_administration/class.section_master.php");

require_once("../../classes/examination/class.exams.php");

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

$AcademicYear = '';
AcademicYear::GetCurrentAcademicYear($AcademicYear);

$HasErrors = false;

$Clean = array();

$Clean['StudentID'] = 0;

if (isset($_GET['StudentID']))
{
    $Clean['StudentID'] = (int) $_GET['StudentID'];
}
elseif (isset($_POST['hdnStudentID']))
{
    $Clean['StudentID'] = (int) $_POST['hdnStudentID'];
}

if ($Clean['StudentID'] <= 0)
{
    header('location:../error.php');
    exit;
}

try
{
    $CurrentStudent = new StudentDetail($Clean['StudentID']);    
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

try
{
    $CurrentParent = new ParentDetail($CurrentStudent->GetParentID());    
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

$AllHappendedExamList = array();
$AllHappendedExamList = Exam::GetAllExamsForReportCard($CurrentStudent->GetClassSectionID());

try
{
    $CurrentClassSections = new ClassSections($CurrentStudent->GetClassSectionID());
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

try
{
    $CurrentClass = new AddedClass($CurrentClassSections->GetClassID());
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

$CurrentClass->FillAssignedSubjects();

$AllClassSubjects = array();
$AllClassSubjects = $CurrentClass->GetAssignedSubjects();

try
{
    $CurrentSectionMaster = new SectionMaster($CurrentClassSections->GetSectionMasterID());
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

$Clean['Process'] = 0;

if (isset($_GET['Process']))
{
	$Clean['Process'] = (int) $_GET['Process'];
}
switch ($Clean['Process'])
{
	
}
require_once('../html_header.php');
?>
<title>Class Report Card</title>
<!-- DataTables CSS -->
<link href="../vendor/datatables-plugins/dataTables.bootstrap.css" rel="stylesheet">

<!-- DataTables Responsive CSS -->
<link href="../vendor/datatables-responsive/dataTables.responsive.css" rel="stylesheet">
<style type="text/css">
.report-header
{
    text-align: center;
}
</style>
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
                    <h1 class="page-header">Class Report Card</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
            <!-- /.row -->
            <div class="row">
                <div class="col-lg-12">
                    <div class="panel panel-default">
                        <!-- /.panel-heading -->
                        <div class="panel-body">
                            <div>
                                <div class="row">
                                    <div class="col-lg-12" style="text-align: right;">
                                        <div class="print-btn-container"><button id="PrintButton" type="submit" class="btn btn-primary">Print</button></div>
                                    </div>
                                </div>
                                <div class="row" id="RecordTable">
                                    <div class="col-lg-12">
                                        <table width="100%" border="0">
                                            <thead>
                                                <tr>
                                                    <th class="report-header">GRACE MODERN JUNIOR HIGH SCHOOL</th>
                                                </tr>
                                                <tr>
                                                    <th class="report-header">CIVIL LINES, SITAPUR</th>
                                                </tr>
                                                <tr>
                                                    <th class="report-header">ENGLISH MEDIUM - RECOGNISED</th>
                                                </tr>
                                                <tr>
                                                    <th class="report-header">PROGRESS REPORT : &nbsp;<?php echo $AcademicYear ;?></th>
                                                </tr>
                                            </thead>
                                        </table>
                                        <table width="100%" border="0">
                                            <thead>
                                                <tr>
                                                    <th>NAME &nbsp;:<span style="font-weight: normal;">&nbsp;&nbsp;&nbsp;<?php echo $CurrentStudent->GetFirstName() . ' '. $CurrentStudent->GetLastName();?></span></th>
                                                    <th>R. NO&nbsp;:<span style="font-weight: normal;"><?php echo $CurrentStudent->GetRollNumber();?></span></th>
                                                    <th style="text-align: right;"><span style="width: 30%; margin-left:55%;">CLASS</span>&nbsp;:&nbsp;<span style="width: 50%; font-weight: normal;"><?php echo $CurrentClass->GetClassName() .' - ' . $CurrentSectionMaster->GetSectionName();?>&nbsp;&nbsp;</span></th>
                                                </tr>
                                                <tr>
                                                    <th>FATHER'S NAME &nbsp;:<span style="font-weight: normal;">&nbsp;&nbsp;&nbsp;<?php echo $CurrentParent->GetFatherFirstName() . ' '. $CurrentParent->GetFatherLastName();?></span></th>
                                                    <th></th>
                                                    <th style="text-align: right;"><span style="width: 30%; margin-left:55%;">SCHOLAR NO.</span>&nbsp;:&nbsp;<span style="width: 50%; font-weight: normal;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span></th>
                                                </tr>
                                                <tr>
                                                    <th>MOTHER'S NAME &nbsp;:<span style="font-weight: normal;">&nbsp;&nbsp;&nbsp;<?php echo $CurrentParent->GetMotherFirstName() . ' '. $CurrentParent->GetMotherLastName();?></span></th>
                                                    <th></th>
                                                    <th style="text-align: right;"><span style="width: 30%; margin-left:55%;">DATE OF BIRTH</span>&nbsp;:&nbsp;<span style="width: 50%; font-weight: normal;"><?php echo ($CurrentStudent->GetDOB() != '0000-00-00') ? date('d/m/Y', strtotime($CurrentStudent->GetDOB())) : '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';?></span></th>
                                                </tr>
                                            </thead>
                                        </table>
                                        <table width="100%" border="1">
                                            <thead>
                                                <tr>
                                                    <th>SN</th>
                                                    <th>SUBJECT Pass marks = 40 %</th>
<?php
                                                    foreach ($AllHappendedExamList as $ExamID => $ExamName) 
                                                    {
?>
                                                        <th style="text-align: center;"><?php echo $ExamName; ?></th>
<?php
                                                    }
?>
                                                    <th style="text-align: center;">Grand Total</th>
                                                </tr>
<?php
                                                $RowCounnter = 1;
                                                foreach ($AllClassSubjects as $ClassSubjectID => $Details) 
                                                {
?>
                                                    <tr>
                                                        <td style="text-align: center;"><?php echo $RowCounnter++; ?></td>
                                                        <td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $Details['Subject']; ?></td>
<?php
                                                        $AllExamSubjectNumber = array();
                                                        $AllExamSubjectNumber = Exam::GetAllExamSubjectNumbers($Clean['StudentID'], $ClassSubjectID);

                                                        $TotalObtainMarks = 0;
                                                        $TotalMaximumMarks = 0;

                                                        if (count($AllExamSubjectNumber) > 0) 
                                                        {
                                                            foreach ($AllExamSubjectNumber as $StudentExamMarkID => $Details) 
                                                            {
                                                                $TotalObtainMarks += $Details['Marks'];
                                                                $TotalMaximumMarks += $Details['MaximumMarks'];
?>
                                                                <td style="text-align: center;"><?php echo $Details['Marks'] . '/' . $Details['MaximumMarks']; ?></td> 
<?php

                                                            }
?>

<?php
                                                        }
                                                        else
                                                        {

                                                            foreach ($AllHappendedExamList as $key => $value) 
                                                            {
                                                                echo '<td style="text-align: center;">-</td>';
                                                            }
                                                        }
?>
                                                        <td style="text-align: center;">
                                                            <?php 
                                                                echo $TotalObtainMarks .'/'. $TotalMaximumMarks;
                                                                $TotalObtainMarks = 0;
                                                                $TotalMaximumMarks = 0;
                                                            ?>
                                                                
                                                        </td>
                                                    </tr>
<?php
                                                }
?>
                                                <tr>
                                                    <th colspan="2" style="text-align: center;">Grand Total</th>
<?php
                                                    $TotalObtainMarks = 0;
                                                    $TotalMaximumMarks = 0;

                                                    $AllExamSubjectGrandTotals = array();
                                                    $AllExamSubjectGrandTotals = Exam::GetAllExamSubjectGrandTotals($Clean['StudentID']);

                                                    foreach ($AllExamSubjectGrandTotals as $StudentExamMarkID => $Details) 
                                                    {
                                                        $TotalObtainMarks += $Details['Marks'];
                                                        $TotalMaximumMarks += $Details['MaximumMarks'];

?>
                                                        <td style="text-align: center;"><?php echo $Details['Marks'] . '/' . $Details['MaximumMarks']; ?></td>                            
<?php
                                                    }
?>

                                                    <td style="text-align: center;">
                                                        <?php
                                                            echo $TotalObtainMarks .'/'. $TotalMaximumMarks;
                                                        ?>
                                                    </td>
                                                    <th style="text-align: center;"></th>
                                                </tr>
                                                <tr>
                                                    <th colspan="2" style="text-align: center;">Percentage</th>
<?php
                                                    $TotalObtainMarks = 0;
                                                    $TotalMaximumMarks = 0;

                                                    $AllExamSubjectGrandTotals = array();
                                                    $AllExamSubjectGrandTotals = Exam::GetAllExamSubjectGrandTotals($Clean['StudentID']);

                                                    foreach ($AllExamSubjectGrandTotals as $StudentExamMarkID => $Details) 
                                                    {
                                                        $TotalObtainMarks += $Details['Marks'];
                                                        $TotalMaximumMarks += $Details['MaximumMarks'];

?>
                                                        <td style="text-align: center;"><?php echo number_format(($Details['Marks'] / $Details['MaximumMarks']) * 100) . '%'; ?></td>                            
<?php
                                                    }
?>

                                                    <td style="text-align: center;">
                                                        <?php
                                                            echo number_format(($TotalObtainMarks / $TotalMaximumMarks) * 100) . '%';
                                                        ?>
                                                    </td>
                                                    <th style="text-align: center;"></th>
                                                </tr>
                                            </thead>
                                        </table>

                                        <hr style="border-top: dotted 1px;">
                                        
                                        <table width="100%" border="0">
                                            <thead>
                                                <tr height="40px"><th colspan="3" style="text-align: right;">Promoted To LKG</th></tr>
                                                <tr>
                                                    <th>Half Yarly</th>
                                                    <th></th>
                                                    <th>Annual</th>
                                                </tr>
                                                <tr>
                                                    <th>Attedance &nbsp;:<span style="font-weight: normal;">166</span></th>
                                                    <th></th>
                                                    <th><span style="font-weight: normal;">211/365</span></th>
                                                </tr>
                                                <tr>
                                                    <th>Health &nbsp;:<span style="font-weight: normal;">Normal</span></th>
                                                    <th></th>
                                                    <th><span style="font-weight: normal;">Normal</span></th>
                                                </tr>
                                                <tr>
                                                    <th>Conduct &nbsp;:<span style="font-weight: normal;">Satisfactory</span></th>
                                                    <th></th>
                                                    <th><span style="font-weight: normal;">Satisfactory</span></th>
                                                </tr>
                                                <tr>
                                                    <th>Remark &nbsp;:<span style="font-weight: normal;">You can do much better</span></th>
                                                    <th></th>
                                                    <th></th>
                                                </tr>
                                            </thead>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="row" style="margin-top: 10px;">
                                <div class="col-lg-12">
                                    <b>Principle's Remarks</b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;_________________________________________________________________
                                </div>
                                <div class="col-lg-2">
                                    <span style="line-height: 40px"><b>Teacher's Sign</b></span>
                                </div>
                                <div class="col-lg-3" style="border: 1px solid black; height: 70px;"></div>
                                <div class="col-lg-1"></div>
                                <div class="col-lg-2">
                                    <span style="line-height: 40px"><b>Priciple's Sign</b></span>
                                </div>
                                <div class="col-lg-3" style="border: 1px solid black; height: 70px;"></div>
                            </div>
                            <div class="row" style="margin-top: 10px;">
                                <div class="col-lg-3"></div>
                                <div class="col-lg-2"></div>
                                <div class="col-lg-3"><span style="margin-left: 38%;"><b>Stamp</b></span></div>

                                <div class="col-lg-3" style="border: 1px solid black; height: 70px; text-align: right;"></div>
                            </div>
                            <div class="row" style="margin-top: 1%;">
                                <div class="col-lg-12">
                                    <table width="100%" border="1">
                                        <thead>
                                            <tr>
                                                <th>&nbsp;Grade: A - 75 And above ( Excellent )</th>
                                                <th>&nbsp;Grade: B - 65 to 74 ( Very Good )</th>
                                            </tr>
                                            <tr>
                                                <th>&nbsp;Grade: C - 55 to 64 ( Good )</th>
                                                <th>&nbsp;Grade:D - 40 to 54 ( Fair )</th>
                                            </tr>
                                            <tr>
                                                <th>&nbsp;Grade: F - 39 And Below ( Poor )</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                    </table>
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
        </div>
        <!-- /#page-wrapper -->

    </div>
    <!-- /#wrapper -->
<?php
require_once('../footer.php');
?>
<!-- DataTables JavaScript -->
<script src="../vendor/datatables/js/jquery.dataTables.min.js"></script>
<script src="../vendor/datatables-plugins/dataTables.bootstrap.min.js"></script>
<script src="../vendor/datatables-responsive/dataTables.responsive.js"></script>   
<script type="text/javascript">
$(document).ready(function() {
    $("body").on('click', '.delete-record', function()
    {   
        if (!confirm("Are you sure you want to delete this exam?"))
        {
            return false;
        }
    });

    $('#DataTableRecords').DataTable({
        responsive: true,
        bPaginate: false,
        bSort: false,
        searching: false, 
        info: false
    });
});
</script>
<!-- JavaScript To Print A Report -->
<script src="js/print-report.js"></script>
<script src="/admin/js/print-report.js"></script>
</body>
</html>
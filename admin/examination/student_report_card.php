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

require_once("../../classes/school_administration/class.grades.php");

require_once("../../classes/examination/class.exam_types.php");
require_once("../../classes/examination/class.exams.php");

require_once("../../classes/examination/class.student_exam_mark.php");

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

$AllGrades = array();
$AllGrades = Grade::GetAllGrades();
$HasErrors = false;

$Clean = array();

$Clean['ClassSectionID'] = 0;
$Clean['SelectedStudents'] = 1;
$Clean['SelectedExamType'] = '';

$MininumMarks = 20;

if (isset($_GET['ClassSectionID']))
{
    $Clean['ClassSectionID'] = (int) $_GET['ClassSectionID'];
}

if ($Clean['ClassSectionID'] <= 0)
{
    header('location:admin/error.php');
    exit;
}

if (isset($_GET['SelectedStudents']))
{
    $Clean['SelectedStudents'] = (string) $_GET['SelectedStudents'];
}

if ($Clean['SelectedStudents'] == '')
{
    header('location:admin/error.php');
    exit;
}

if (isset($_GET['SelectedExamType']))
{
    $Clean['SelectedExamType'] = (string) $_GET['SelectedExamType'];
}

if ($Clean['SelectedExamType'] == '')
{
    header('location:admin/error.php');
    exit;
}

$Clean['SelectedStudentList'] = explode('$', $Clean['SelectedStudents']);
$Clean['SelectedExamTypeList'] = explode(',', $Clean['SelectedExamType']);

require_once('../html_header.php');
?>

<title>Print Report Card</title>
<!-- DataTables CSS -->
<link href="../vendor/datatables-plugins/dataTables.bootstrap.css" rel="stylesheet">

<!-- DataTables Responsive CSS -->
<link href="../vendor/datatables-responsive/dataTables.responsive.css" rel="stylesheet">
</head>

<body>
    <div id="wrapper">
        <!-- Navigation -->

        <div>
            <div class="row">
                <div class="col-lg-12">
                    
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
                                        <div class="print-btn-container"><button id="PrintButton" type="submit" class="btn btn-primary">Print All</button></div>
                                    </div>
                                </div>
                                <div class="row" id="RecordTable" style="page-break-before: always;">
<?php

                            foreach ($Clean['SelectedStudentList'] as $key => $StudentID) 
                            {
                                try
                                {
                                    $CurrentStudent = new StudentDetail($StudentID); 
                                    $CurrentParent = new ParentDetail($CurrentStudent->GetParentID());   
                                }
                                catch (ApplicationDBException $e)
                                {
                                    header('location:admin/error.php');
                                    exit;
                                }
                                catch (Exception $e)
                                {
                                    header('location:admin/error.php');
                                    exit;
                                }

                                foreach ($Clean['SelectedExamTypeList'] as $ExamTypeID) 
                                {
                                    try
                                    {
                                        $ExamTypeToEdit = new ExamType($ExamTypeID);
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
                                }

                                $AllHappendedExamList = array();
                                $AllHappendedExamList = Exam::GetAllExamsForReportCard($Clean['ClassSectionID'], false, $Clean['SelectedExamType']);
                                // echo '<pre>';
                                // var_dump($AllHappendedExamList);exit;

                                try
                                {
                                    $CurrentClassSections = new ClassSections($Clean['ClassSectionID']);
                                    $CurrentClass = new AddedClass($CurrentClassSections->GetClassID());
                                }
                                catch (ApplicationDBException $e)
                                {
                                    header('location:admin/error.php');
                                    exit;
                                }
                                catch (Exception $e)
                                {
                                    header('location:admin/error.php');
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
                                    header('location:admin/error.php');
                                    exit;
                                }
                                catch (Exception $e)
                                {
                                    header('location:admin/error.php');
                                    exit;
                                }
?>
                                    <!--<div class="row " style="border:1px solid black;">-->
                                    <!--        <thead>-->
                                    <!--        <div class="col-md-2" style="float: left;" >-->
                                    <!--           <img src="../../site_images/school_logo/school_logo.png" height="80px;" width="120px;" > -->
                                    <!--        </div>-->
                                    <!--            <div class="col-md-8" style= "text-align:center;">-->
                                    <!--                <span style="font-weight: bold;font-size: 15px;">LUCKNOW INTERNATIONAL PUBLIC SCHOOL</span><br>-->
                                    <!--                <span style="font-weight: bold;font-size: 14px;">&nbsp;N.H 24, Chandanpur - Khanipur(Near Itaunja), Sitapur Road, Lucknow - 226203</span><br>-->
                                    <!--                <span style="font-weight: bold;font-size: 14px;"> Phone : 7521800903-4, Web : www.lucknowips.com, e-mail : lucknowips@gmail.com</span><br>-->
                                    <!--                <center><span style="font-weight: bold;font-size: 15px;">REPORT CARD FOR SESSION : &nbsp;<?php echo $AcademicYear ;?></span></center>                                                -->
                                    <!--            </div>-->
                                    <!--        </thead>-->
                                    <!-- </div>-->
                                     
                                     <div class=" text-center" style="border:1px solid black; width:100%; height: 90px;">
                                        <img src="../../site_images/school_logo/lips.jpg" height="80px;" width="120px;" style="float:left; margin-left: 12px; margin-top: 5px;" >
                                        <div style= "float:left; margin-left: 9px; text-align:center;">
                                            <span style="font-weight: bold;font-size: 15px;">LUCKNOW INTERNATIONAL PUBLIC SCHOOL</span><br>
                                            <span style="font-weight: bold;font-size: 12px;">&nbsp;N.H 24, Chandpur - Khanipur(Near Itaunja), Sitapur Road, Lucknow - 226203</span><br>
                                            <span style="font-weight: bold;font-size: 12px;"> Phone : 7521800903-4, Web : www.lucknowips.com, e-mail : lucknowips@gmail.com</span><br>
                                            <span style="font-weight: bold;font-size: 15px;">REPORT CARD FOR SESSION : &nbsp;<?php echo $AcademicYear ;?></span></center>                                                
                                        </div>
                                        <img src="../../site_images/school_logo/school_logo.png" height="75px;" width="120px;" style="float:left; margin-left: 12px; margin-top: 5px;">
                                     </div>
                                     
                                         <table width="100%" border="1" style="margin-bottom: 15px;font-size: 10px;margin-top: 12px;">
                                            <thead>
                                                <tr>
                                                    <th style="width: 250px;">&nbsp;&nbsp;ROLL NO &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;:<span style="font-weight: normal;">&nbsp;<?php echo $CurrentStudent->GetRollNumber();?></span></th>
                                                    <th style="width: 450px;">STUDENT'S NAME :<span style="font-weight: normal;"><?php echo $CurrentStudent->GetFirstName() . ' '. $CurrentStudent->GetLastName();?></span></th>
                                                      <th style="text-align: left;width: 550px;"><span style="width: 30%;">&nbsp;FATHER'S NAME</span>&nbsp;:&nbsp;<span style="width: 40%; font-weight: normal;"><?php echo $CurrentParent->GetFatherFirstName() .' ' . $CurrentParent->GetFatherLastName();?>&nbsp;&nbsp;</span></th>
                                                </tr>
                                                <tr>
                                                    <th style="width: 550px;">&nbsp;&nbsp;MOTHER'S NAME &nbsp;&nbsp;&nbsp;:<span style="font-weight: normal;width: 40%;">&nbsp;<?php echo $CurrentParent->GetMotherFirstName() . ' '. $CurrentParent->GetMotherLastName();?></span></th>
                                                    <th>
                                                      &nbsp;CLASS &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;:<span style="font-weight: normal;">&nbsp;<?php echo $CurrentClass->GetClassName() . ' '. $CurrentSectionMaster->GetSectionName();?></span> 
                                                    </th>
                                                     <th style="text-align: left;"><span style="width: 30%;">&nbsp;&nbsp;ADMISSION NO.</span>&nbsp;:&nbsp;<span style="width: 50%; font-weight: normal;"><?php echo $CurrentStudent->GetEnrollmentID();?>
                                                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span></th>
                                                </tr>
                                                <tr>
                                                      <th colspan="3">&nbsp;&nbsp;DOB&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;:<span style="font-weight: normal;">&nbsp;<?php echo ($CurrentStudent->GetDOB() != '0000-00-00') ? date('d/m/Y', strtotime($CurrentStudent->GetDOB())) : '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';?></span></th>
                                                </tr>
                                            </thead>
                                        </table>
                                        <table width="100%" border="1" style="font-size: 12px;">
                                            <thead>
                                               <tr> 
                                                <th>SCHOLASTIC AREAS</th>
                                                 <!--<th></th> -->
                                                  <th colspan="6" style="text-align: center;">Term-1(100 MARKS)</th>
<?php                                                  
                                                  if(array_key_exists(8, $AllHappendedExamList))
                                                  {
                                                      echo '<th colspan="6" style="text-align: center;">Term-2(100 MARKS)</th>';
                                                      echo '<th colspan= "2" style="text-align: center;">TOTAL</th>';
                                                  }
 ?>                                                 
                                               </tr>     
                                                <tr>
                                                    <!--<th>SN</th> -->
                                                  <th>&nbsp;&nbsp;SUBJECTS</th>                                                
<?php                                               
                                                    foreach ($AllHappendedExamList as $ExamTypeID => $Details) 
                                                    {
?>
 <?php                                                  
                                                    if ($ExamTypeID == 1)
                                                     {
                                                         echo '<th style="text-align: center;">'.$Details['ExamName'].'<br>(10)</th>';
                                                     }
                                                     if ($ExamTypeID == 2)
                                                     {
                                                         echo '<th style="text-align: center;">'.$Details['ExamName'].'<br>(5)</th>';
                                                     }
                                                     if ($ExamTypeID == 3)
                                                     {
                                                         echo '<th style="text-align: center;">'.$Details['ExamName'].'<br>(5)</th>';
                                                     }
                                                     if ($ExamTypeID == 4)
                                                     {
                                                         echo '<th style="text-align: center;">'.$Details['ExamName'].'<br>(80)</th>';
                                                         echo '<th style="text-align: center;">MARKS OBT.<br>(100)</th>';
                                                         echo '<th style="text-align: center;">GR</th>';
                                                     }

                                                     if ($ExamTypeID == 5)
                                                     {
                                                         echo '<th style="text-align: center;">'.$Details['ExamName'].'<br>(10)</th>';
                                                     }
                                                     if ($ExamTypeID == 6)
                                                     {
                                                         echo '<th style="text-align: center;">'.$Details['ExamName'].'<br>(5)</th>';
                                                     }
                                                     if ($ExamTypeID == 7)
                                                     {
                                                         echo '<th style="text-align: center;">'.$Details['ExamName'].'<br>(5)</th>';
                                                     }
                                                     if ($ExamTypeID == 8)
                                                     {
                                                         echo '<th style="text-align: center;">'.$Details['ExamName'].'<br>(80)</th>';
                                                         echo '<th style="text-align: center;">MARKS OBT.<br>(100)</th>';
                                                         echo '<th style="text-align: center;">GR</th>';
                                                         echo '<th style="text-align: center;">MARKS OBT.<br>(200)</th>';
                                                         echo '<th style="text-align: center;">GR</th>';
                                                     }

                                                   ?>
<?php
                                                    }
?>                                                     
                                                </tr>
<?php
                                                $RowCounter = 1;
                                                $AllSubjectsGrandTotalObtainedMarks = 0;
                                                $IsFailInExamType = array();
                                                $MininumMarks = 0;
                                                
                                                $ReportCardSubjects = array();
                                                $ReportCardSubjects = Exam::GetReportCardSubject($Clean['ClassSectionID']);

                                                foreach ($AllClassSubjects as $ClassSubjectID => $ClassSubjectDetails) 
                                                {
                                                    if ($ClassSubjectDetails['SubjectMarksType'] == 'Grade' || !array_key_exists($ClassSubjectID, $ReportCardSubjects))
                                                    {
                                                        continue;
                                                    }
?>
                                                    <tr>
                                                        <!--<td style="text-align: center;"><?php echo $RowCounter++; ?></td>-->
                                                        <td style="text-align: left;font-size:10px;width:250px;">&nbsp;&nbsp;<?php echo $ClassSubjectDetails['Subject']; ?></td>
<?php
                                                        $SubjectObtainedMarksGrandTotal = 0;
                                                        $SubjectMaximumMarksGrandTotal = 0;

                                                        $Term1SubjectObtainedTotalMarks = 0;
                                                        $Term2SubjectObtainedTotalMarks = 0;

                                                        foreach ($Clean['SelectedExamTypeList'] as $ExamTypeID)
                                                        {
                                                            $SubjectMarks = StudentExamMark::GetStudentSubjectMarks($ExamTypeID, $ClassSubjectID, $StudentID);

                                                            if (count($SubjectMarks) == 0) 
                                                            {
                                                                echo '<td style="text-align: center;">-</td>';

                                                                if ($ExamTypeID == 4)
                                                                 {
                                                                    echo '<td style="text-align: center;">-</td>';
                                                                    echo '<td style="text-align: center;">-</td>';
                                                                 }

                                                                 if ($ExamTypeID == 8)
                                                                 {
                                                                     echo '<td style="text-align: center;">-</td>';
                                                                     echo '<td style="text-align: center;">-</td>';
                                                                    //  echo '<td style="text-align: center;">-</td>';
                                                                    //  echo '<td style="text-align: center;">-</td>';
                                                                 }
                                                            } 

                                                            else
                                                            {       
                                                                foreach ($SubjectMarks as $key => $SubjectMarksDetails) 
                                                                {
                                                                    if ($SubjectMarksDetails['Status'] != '' && $SubjectMarksDetails['Status'] != 'Present' && $ClassSubjectDetails['SubjectMarksType'] == 'Number') 
                                                                    {                                                                        
                                                                        echo '<td style="text-align: center;">';
                                                                        echo $SubjectMarksDetails['Status'];
                                                                        echo '</td>';
                                                                        
                                                                        if ($ExamTypeID == 4)
                                                                          {
                                                                               echo '<td style="text-align: center;">'. $Term1SubjectObtainedTotalMarks .'</td>';
    
                                                                               $Grade = '';
    
                                                                               foreach ($AllGrades as $GradeID => $GradeDetails) 
                                                                               {
                                                                                   if (round($Term1SubjectObtainedTotalMarks) >= $GradeDetails['FromPercentage'] && round($Term1SubjectObtainedTotalMarks) <= $GradeDetails['ToPercentage'])
                                                                                   {
                                                                                       $Grade = $GradeDetails['Grade'];
                                                                                   }
                                                                                   else if (($Term1SubjectObtainedTotalMarks < $GradeDetails['FromPercentage'] && $Term1SubjectObtainedTotalMarks > ($GradeDetails['FromPercentage'] - 1)) && ($Term1SubjectObtainedTotalMarks <= $GradeDetails['ToPercentage'])) 
                                                                                   {
                                                                                       $Grade = $GradeDetails['Grade'];
                                                                                   }
                                                                               }    
                                                                               echo '<td style="text-align: center;">'. $Grade .'</td>';
                                                                          }
    
                                                                         if ($ExamTypeID == 8)
                                                                         {
                                                                               echo '<td style="text-align: center;">'. $Term2SubjectObtainedTotalMarks .'</td>';
    
                                                                                $Grade = '';
    
                                                                               foreach ($AllGrades as $GradeID => $GradeDetails) 
                                                                               {
                                                                                   if (round($Term2SubjectObtainedTotalMarks) >= $GradeDetails['FromPercentage'] && round($Term2SubjectObtainedTotalMarks) <= $GradeDetails['ToPercentage'])
                                                                                   {
                                                                                       $Grade = $GradeDetails['Grade'];
                                                                                   }
                                                                                   else if (($Term2SubjectObtainedTotalMarks < $GradeDetails['FromPercentage'] && $Term2SubjectObtainedTotalMarks > ($GradeDetails['FromPercentage'] - 1)) && ($Term2SubjectObtainedTotalMarks <= $GradeDetails['ToPercentage'])) 
                                                                                   {
                                                                                       $Grade = $GradeDetails['Grade'];
                                                                                   }
                                                                               }    
                                                                               echo '<td style="text-align: center;">'. $Grade .'</td>';
                                                                          }
                                                                    }
                                                                    elseif ($ClassSubjectDetails['SubjectMarksType'] == 'Number') 
                                                                    {
                                                                        if ($SubjectMarksDetails['Marks'] < $MininumMarks)
                                                                        {
                                                                            $IsFailInExamType[$ExamTypeID] = 1;
                                                                        }
                                                                        
                                                                        echo '<td style="text-align: center;">';
                                                        
                                                                        echo $SubjectMarksDetails['Marks'];

                                                                        $Term1SubjectObtainedTotalMarks += $SubjectMarksDetails['Marks'];

                                                                        if ($ExamTypeID > 4) 
                                                                        {
                                                                            $Term2SubjectObtainedTotalMarks += $SubjectMarksDetails['Marks'];
                                                                        } 

                                                                     if ($ExamTypeID == 4)
                                                                      {
                                                                           echo '<td style="text-align: center;">'. $Term1SubjectObtainedTotalMarks .'</td>';

                                                                           $Grade = '';

                                                                           foreach ($AllGrades as $GradeID => $GradeDetails) 
                                                                           {
                                                                               if (round($Term1SubjectObtainedTotalMarks) >= $GradeDetails['FromPercentage'] && round($Term1SubjectObtainedTotalMarks) <= $GradeDetails['ToPercentage'])
                                                                               {
                                                                                   $Grade = $GradeDetails['Grade'];
                                                                               }
                                                                               else if (($Term1SubjectObtainedTotalMarks < $GradeDetails['FromPercentage'] && $Term1SubjectObtainedTotalMarks > ($GradeDetails['FromPercentage'] - 1)) && ($Term1SubjectObtainedTotalMarks <= $GradeDetails['ToPercentage'])) 
                                                                               {
                                                                                   $Grade = $GradeDetails['Grade'];
                                                                               }
                                                                           }    
                                                                           echo '<td style="text-align: center;">'. $Grade .'</td>';
                                                                      }

                                                                     if ($ExamTypeID == 8)
                                                                     {
                                                                           echo '<td style="text-align: center;">'. $Term2SubjectObtainedTotalMarks .'</td>';

                                                                            $Grade = '';

                                                                           foreach ($AllGrades as $GradeID => $GradeDetails) 
                                                                           {
                                                                               if (round($Term2SubjectObtainedTotalMarks) >= $GradeDetails['FromPercentage'] && round($Term2SubjectObtainedTotalMarks) <= $GradeDetails['ToPercentage'])
                                                                               {
                                                                                   $Grade = $GradeDetails['Grade'];
                                                                               }
                                                                               else if (($Term2SubjectObtainedTotalMarks < $GradeDetails['FromPercentage'] && $Term2SubjectObtainedTotalMarks > ($GradeDetails['FromPercentage'] - 1)) && ($Term2SubjectObtainedTotalMarks <= $GradeDetails['ToPercentage'])) 
                                                                               {
                                                                                   $Grade = $GradeDetails['Grade'];
                                                                               }
                                                                           }    
                                                                           echo '<td style="text-align: center;">'. $Grade .'</td>';
                                                                      }

                                                                        $SubjectObtainedMarksGrandTotal += $SubjectMarksDetails['Marks']; 

                                                                        $AllSubjectsGrandTotalObtainedMarks += $SubjectObtainedMarksGrandTotal;
                                                                        echo '</td>';
                                                                    }
                                                                }
                                                            }
                                                        }

                                                         if ($ExamTypeID == 8 && $SubjectObtainedMarksGrandTotal > 0)
                                                         {
                                                            echo '<td style="text-align: center;">'. $SubjectObtainedMarksGrandTotal .'</td>';

                                                             $Grade = '';

                                                           foreach ($AllGrades as $GradeID => $GradeDetails) 
                                                           {
                                                               if ((round($SubjectObtainedMarksGrandTotal /2) >= $GradeDetails['FromPercentage']) && (round($SubjectObtainedMarksGrandTotal /2) <= $GradeDetails['ToPercentage'])) 
                                                               {
                                                                   $Grade = $GradeDetails['Grade'];
                                                               }
                                                           }    

                                                            echo '<td style="text-align: center;">'. $Grade .'</td>';
                                                         }                                                          
?>
                                                    </tr>
<?php
                                                }
?>
                                                    <tr>
                                                        <th>&nbsp;&nbsp;TOTAL</th>
<?php
                                                        $Term1TotalMarks = 0;
                                                        $Term1MximumTotalMarks = 0;

                                                        $Term2TotalMarks = 0;
                                                        $Term2MximumTotalMarks = 0;

                                                        foreach ($Clean['SelectedExamTypeList'] as $ExamTypeID)
                                                        {
                                                            $ExamTypeObtainMarks = 0;
                                                            $ExamTypeMaximumMarks = 0;

                                                            foreach ($AllClassSubjects as $ClassSubjectID => $ClassSubjectDetails)
                                                            {   
                                                                $SubjectMarks = StudentExamMark::GetStudentSubjectMarks($ExamTypeID, $ClassSubjectID, $StudentID);

                                                                foreach ($SubjectMarks as $key => $SubjectMarksDetails) 
                                                                {

                                                                    if ($ClassSubjectDetails['SubjectMarksType'] == 'Number') 
                                                                    {
                                                                        $ExamTypeObtainMarks += $SubjectMarksDetails['Marks']; 
                                                                         
                                                                        $ExamTypeMaximumMarks += $SubjectMarksDetails['MaximumMarks'];  
                                                                    }
                                                                }
                                                            }

                                                            if ($ExamTypeID <= 4) 
                                                            {   
                                                                $Term1TotalMarks += $ExamTypeObtainMarks;
                                                                $Term1MximumTotalMarks += $ExamTypeMaximumMarks;
                                                            }
                                                            else
                                                            {
                                                                $Term2TotalMarks += $ExamTypeObtainMarks;
                                                                $Term2MximumTotalMarks += $ExamTypeMaximumMarks;
                                                            }

                                                            if ($ExamTypeObtainMarks > 0) 
                                                            {   
                                                                echo '<td style="text-align: center;"><strong>'.$ExamTypeObtainMarks.'/'.$ExamTypeMaximumMarks.'</strong></td>';
                                                                // echo '<td style="text-align: center;"><strong></strong></td>';
                                                            }                                                
                                                            else
                                                            {
                                                                echo '<td style="text-align: center;"><strong>-</strong></td>';
                                                            }

                                                            if ($ExamTypeID == 4) 
                                                            {
                                                                echo '<td style="text-align: center;"><strong>'.$Term1TotalMarks.'/'.$Term1MximumTotalMarks.'</strong></td>';
                                                                echo '<td style="text-align: center;"><strong></strong></td>';
                                                            }
                            
                                                            if ($ExamTypeID == 8) 
                                                            {
                                                                echo '<td style="text-align: center;"><strong>'.$Term2TotalMarks.'/'.$Term2MximumTotalMarks.'</strong></td>';
                                                                echo '<td style="text-align: center;"><strong></strong></td>';
                                                            }
                                                        }
                                                        
                                                        if ($ExamTypeID == 8) 
                                                        {
                                                            // echo '<td style="text-align: center;"><strong>'.$AllSubjectsGrandTotalObtainedMarks.'/'.$AllSubjectsGrandTotalMaximumMarks.'</strong></td>';
                                                            echo '<td style="text-align: center;"><strong>'.($Term1TotalMarks + $Term2TotalMarks).'/'.($Term1MximumTotalMarks + $Term2MximumTotalMarks).'</strong></td>';
                                                            // echo '<td style="text-align: center;"><strong>'.($Term1TotalMarks + $Term2TotalMarks).'</strong></td>';
                                                            echo '<td style="text-align: center;"><strong></strong></td>';   
                                                        }
?>                                                      
                                                    </tr>
                                                    <tr>
                                                        <th>&nbsp;&nbsp;PERCENTAGE</th>
<?php
                                                        foreach ($Clean['SelectedExamTypeList'] as $ExamTypeID)
                                                        {
                                                            $ExamTypeObtainMarks = 0;
                                                            $ExamTypeMaximumMarks = 0;
                                                            $ExamPercentage = 0;

                                                            foreach ($AllClassSubjects as $ClassSubjectID => $ClassSubjectDetails)
                                                            {
                                                                $SubjectMarks = StudentExamMark::GetStudentSubjectMarks($ExamTypeID, $ClassSubjectID, $StudentID);

                                                                foreach ($SubjectMarks as $key => $SubjectMarksDetails) 
                                                                {
                                                                    $TotalMaximumMarks = 0;
                                                                    if ($ClassSubjectDetails['SubjectMarksType'] == 'Number') 
                                                                    {   
                                                                        $ExamTypeObtainMarks += $SubjectMarksDetails['Marks']; 
                                                                        $ExamTypeMaximumMarks += $SubjectMarksDetails['MaximumMarks']; 
                                                                    }
                                                                }
                                                            }

                                                            if ($ExamTypeObtainMarks > 0) 
                                                            {       
                                                                // echo '<td style="text-align: center;"><strong>'.$ExamTypeObtainMarks . $ExamTypeMaximumMarks.'%</strong></td>'; 
                                                                echo '<td style="text-align: center;"><strong>'.number_format((($ExamTypeObtainMarks / $ExamTypeMaximumMarks) * 100), 2).'%</strong></td>';
                                                                // echo '<td style="text-align: center;"><strong></strong></td>';
                                                            }
                                                            else
                                                            {   
                                                                echo '<td style="text-align: center;"><strong>-</strong></td>';  
                                                            }

                                                            if ($ExamTypeID == 4) 
                                                            {
                                                                echo '<td style="text-align: center;"><strong>'. (($Term1MximumTotalMarks) ?  number_format((($Term1TotalMarks / $Term1MximumTotalMarks) * 100), 2) : '-') .'%</strong></td>';
                                                                echo '<td style="text-align: center;"><strong></strong></td>';
                                                            }

                                                            if ($ExamTypeID == 8) 
                                                            {
                                                                echo '<td style="text-align: center;"><strong>'. (($Term2MximumTotalMarks) ? number_format((($Term2TotalMarks / $Term2MximumTotalMarks) * 100), 2) : '-').'%</strong></td>';
                                                                echo '<td style="text-align: center;"><strong></strong></td>';
                                                            }
                                                        }
                                                        
                                                        if ($ExamTypeID == 8)
                                                        {
                                                            if ($Term1TotalMarks > 0 || $Term2TotalMarks > 0) 
                                                            {
                                                                echo '<td style="text-align: center;"><strong>'.number_format(((($Term1TotalMarks+$Term2TotalMarks) / ($Term1MximumTotalMarks + $Term2MximumTotalMarks)) * 100),2).'%</strong></td>';
                                                                echo '<td style="text-align: center;"><strong></strong></td>';
                                                            }
                                                            else
                                                            {
                                                                echo '<td style="text-align: center;"><strong></strong></td>';
                                                                echo '<td style="text-align: center;"><strong></strong></td>';
                                                            }   
                                                        }
?>
                                                    </tr>
  <!--code start for rank-->                 
  <!--
                                                   <tr>
                                                        <th colspan="2">&nbsp;&nbsp;RANK</th>
<?php
                                                        foreach ($Clean['SelectedExamTypeList'] as $ExamTypeID)
                                                        {
                                                            $ExamTypeObtainMarks = 0;
                                                            $ExamTypeMaximumMarks = 0;
                                                            $ExamPercentage = 0;

                                                            foreach ($AllClassSubjects as $ClassSubjectID => $ClassSubjectDetails)
                                                            {
                                                                $SubjectMarks = StudentExamMark::GetStudentSubjectMarks($ExamTypeID, $ClassSubjectID, $StudentID);

                                                                foreach ($SubjectMarks as $key => $SubjectMarksDetails) 
                                                                {
                                                                    $TotalMaximumMarks = 0;
                                                                    
                                                                    if ($ClassSubjectDetails['SubjectMarksType'] == 'Number') 
                                                                    {                                                                       
                                                                        $ExamTypeObtainMarks += $SubjectMarksDetails['Marks']; 
                                                                        $ExamTypeMaximumMarks += $SubjectMarksDetails['MaximumMarks']; 
                                                                    }
                                                                }
                                                            }

                                                            // if ($ExamTypeObtainMarks > 0) 
                                                            // {
                                                            //     if (array_key_exists($ExamTypeID, $IsFailInExamType))
                                                            //     {
                                                            //         echo '<td style="text-align: center;"><strong><span style="color:red;">Failed</span></strong></td>';
                                                            //     }
                                                            //     else
                                                            //     {
                                                            //         echo '<td style="text-align: center;"> '.((($ExamTypeObtainMarks / $ExamTypeMaximumMarks) * 100) >= 40.0 ? StudentExamMark::GetStudentRankInClass($ExamTypeID, $Clean['ClassSectionID'], $MininumMarks, $StudentID) : '').' ';
                                                            //         echo '<strong>'.((($ExamTypeObtainMarks / $ExamTypeMaximumMarks) * 100) >= 40.0 ? 'Passed' : '<span style="color:red;">Failed</span>').'</strong></td>';   
                                                            //     }
                                                            // }
                                                            // else
                                                            // {
                                                            //     echo '<td style="text-align: center;"><strong>-</strong></td>';
                                                            // }
                                                            
                                                            echo '<td style="text-align: center;"></td>';
                                                            
                                                            if ($ExamTypeID == 4)
                                                            {
                                                                echo '<td style="text-align: center;"></td>';
                                                                echo '<td style="text-align: center;"></td>';
                                                            }
                                                            
                                                            if ($ExamTypeID == 8)
                                                            {
                                                                echo '<td style="text-align: center;"></td>';
                                                                echo '<td style="text-align: center;"></td>';
                                                                echo '<td style="text-align: center;"></td>';
                                                                echo '<td style="text-align: center;"></td>';
                                                            }
                                                        }
?>
                                                    </tr> 
                                                    -->
  <!--code end for rank-->  

                                                    <tr>
                                                       <th>&nbsp;&nbsp;POSITION </th>
                                                       <td style="text-align: center;"></td>
                                                       <td style="text-align: center;"></td>
                                                       <td style="text-align: center;"></td>
                                                       <td style="text-align: center;"></td>
                                                       <td style="text-align: center;"></td>
                                                       <td style="text-align: center;"></td>
<?php
                                                        if ($ExamTypeID == 8)
                                                        {
?>
                                                            <td style="text-align: center;"></td>
                                                            <td style="text-align: center;"></td>
                                                            <td style="text-align: center;"></td>
                                                            <td style="text-align: center;"></td>
                                                            <td style="text-align: center;"></td>
                                                            <td style="text-align: center;"></td>
                                                            <td style="text-align: center;"></td>
                                                            <td style="text-align: center;"></td>
<?php
                                                        }
?>
                                                    </tr>
                                            </thead>
                                        </table>

                                        <table width="100%" border="1" style="margin-top: 10px;font-size: 12px;">
                                            <thead>  
                                                <tr>
                                                    <th style="font-size: 12px;font-weight: bold;">&nbsp;&nbsp;CO SCHOLASTIC AREAS TERM-1 <br>
                                                      [ ON A 3-POINT(A-C) GRADING SCALE ] </th>
                                                 <th style="text-align: center;font-size: 12px;">GRADE</th>
<?php
                                                if ($ExamTypeID == 8)
                                                {
?>
                                                    <th style="font-size: 12px;">&nbsp;&nbsp;CO SCHOLASTIC AREAS TERM-2<br>
                                                      &nbsp;&nbsp;&nbsp;[ ON A 3-POINT(A-C) GRADING SCALE ]</th>
                                                    <th style="text-align: center;">GRADE</th>
<?php
                                                }
?> 
                                                </tr>

<?php
                                                $RowCounter = 0;
                                                
                                                $ReportCardGradeSubjects = array();
                                                $ReportCardGradeSubjects = Exam::GetReportCardSubject($Clean['ClassSectionID']);

                                                foreach ($AllClassSubjects as $ClassSubjectID => $ClassSubjectDetails) 
                                                {
                                                    if ($ClassSubjectDetails['SubjectMarksType'] != 'Grade' || !array_key_exists($ClassSubjectID, $ReportCardSubjects)) 
                                                    {
                                                        continue;
                                                    }

                                                    echo '<tr>';
                                                    echo '<td>'; 
                                                    echo '&nbsp; '. $ClassSubjectDetails['Subject'];
                                                    echo '</td>';

                                                        $SubjectMarks = StudentExamMark::GetStudentSubjectMarks(4, $ClassSubjectID, $StudentID);

                                                        if (count($SubjectMarks) == 0) 
                                                        {
                                                            echo '<td style="text-align: center;">-</td>';
                                                        }
                                                        else
                                                        {
                                                            foreach ($SubjectMarks as $key => $SubjectMarksDetails) 
                                                            {
                                                                if ($ClassSubjectDetails['SubjectMarksType'] == 'Grade') 
                                                                {
                                                                    echo '<td style="text-align: center;">';
                                                                    echo $SubjectMarksDetails['Grade'];
                                                                    echo '</td>';
                                                                }
                                                            }
                                                        }
                                                    if ($ExamTypeID == 8)
                                                    {
                                                        echo '<td style="text-align:left;">'; 
                                                        echo '&nbsp; '. $ClassSubjectDetails['Subject'];
                                                        echo '</td>';
                                                            $SubjectMarks = StudentExamMark::GetStudentSubjectMarks(8, $ClassSubjectID, $StudentID);
    
                                                            if (count($SubjectMarks) == 0) 
                                                            {
                                                                echo '<td style="text-align: center;">-</td>';
                                                            }
                                                            else
                                                            {
                                                                foreach ($SubjectMarks as $key => $SubjectMarksDetails) 
                                                                {
                                                                    if ($ClassSubjectDetails['SubjectMarksType'] == 'Grade') 
                                                                    {
                                                                        echo '<td style="text-align: center;">';
                                                                        echo $SubjectMarksDetails['Grade'];
                                                                        echo '</td>';
                                                                    }
                                                                }
                                                            }   
                                                    }
?>
                                                    </tr>
  <?php
                                                 }
  ?>                                                      
        
                                            </thead>
                                        </table>

                                          <table width="100%" border="1" style="margin-top: 10px;">
                                            <thead>
                                                <tr style="font-size: 12px;">
                                                    <th style="font-size: 12px;font-weight: bold;">&nbsp;&nbsp;DISCIPLINE TERM-1<br>
                                                      &nbsp;&nbsp;&nbsp;[ ON A 3-POINT(A-C) GRADING SCALE ]</th>
                                                     <th style="text-align: center;font-size: 12px;">GRADE</th>
    <?php
                                                    if ($ExamTypeID == 8)
                                                    {
    ?>
                                                        <th style="font-size: 12px;">&nbsp;&nbsp;DISCIPLINE TERM-2<br>
                                                          &nbsp;&nbsp;&nbsp;[ ON A 3-POINT(A-C) GRADING SCALE ]</th>
                                                        <th style="text-align: center;">GRADE</th>
    <?php
                                                    }
?>
                                                </tr>
                                                <tr>
                                                   <td style="font-size: 12px;">&nbsp;&nbsp;DISCIPLINE</td>
                                                   <td style="text-align: center;">&nbsp;&nbsp;</td>
<?php
                                                if ($ExamTypeID == 8)
                                                {
?>
                                                    <td style="text-align: left;font-size: 12px;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;DISCIPLINE</td>
                                                    <td style="text-align: center;">&nbsp;&nbsp;</td>
<?php
                                                }
?> 
                                                </tr>
                                            </thead>
                                         </table>

                                         <table width="100%" border="1" style="margin-top: 3px;font-size: 12px;">
                                              <thead>
                                                <tr>
                                                   <td style="text-align: left;width: 524px;">&nbsp;&nbsp;ATTENDANCE</td>
                                                   <td style="text-align: center;width:115px;"></td>
                                                    <td style="text-align: center;width: 585px;"></td>
                                                    <td style="text-align: center;width:110px;"></td>

                                                </tr>
                                             </thead>
                                          </table>
                                            <br>

                                          <table width="100%" border="1">
                                                 <tr colspan="2" style="height: 40px;font-size: 12px;">
                                                    <th colspan="3">&nbsp;&nbsp;CLASS TEACHER'S REMARKS:</th>
                                                </tr>
<?php
                                                if ($ExamTypeID == 8)
                                                {
?>
                                                    <tr colspan="2" style="font-size: 12px;">
                                                        <th>&nbsp;&nbsp;PASSED & PROMOTED TO CLASS: &nbsp;</th>
                                                        <th>&nbsp;&nbsp;SCHOOL REOPENS ON: &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</th>
                                                    </tr>
<?php
                                                }
?>
                                         </table>

                                        <table width="100%" border="1" style="margin-top: 10px">
                                             <tr colspan="2" style="font-size: 12px; height: 70px;font-weight: bold;">
                                                <td style="width: 250px;">PLACE<br><br><br>DATE</td>
                                                 <td style="text-align: center;padding-top: 45px;width: 250px;">CLASS TEACHER</td>
                                                 <td style="text-align: center;padding-top: 45px;width: 250px;">PRINCIPAL</td>
                                                 <td style="text-align: center;padding-top: 45px;width: 250px;">PARENTS</td>
                                            </tr>
                                        </table>

                                            <div class="row" style="margin-top: 10px;font-size: 11px;">
                                                <div class="col-lg-12">
                                                    <table width="100%" border="1">
                                                         <thead>
                                                            <tr>
                                                               <th colspan="6" style="text-align: center;">INSTRUCTIONS</th>
                                                            </tr>
                                                            <tr>
                                                               <th style="text-align: center;" colspan="4">GRADING SCALE FOR SCHOLASTIC AREAS 8-POINT GRADING SCALE</th>
                                                            </tr>
                                                            <tr>
                                                                <th style="text-align: center;">MARKS RANGE</th> 
                                                                <th style="text-align: center;">GRADE</th>
                                                                <th style="text-align: center;">MARKS RANGE</th>
                                                                <th style="text-align: center;">GRADE</th>
                                                            </tr>
<?php
                                                                $RowCounter = 0;    

                                                                foreach ($AllGrades as $GradeDetails) 
                                                                { 
                                                                    if ($GradeDetails['FromPercentage'] == 0 && $GradeDetails['ToPercentage'] == 0)
                                                                    {
                                                                        continue;
                                                                    }

                                                                    if ($RowCounter == 0) 
                                                                    {
                                                                        echo '<tr>';
                                                                    } 
                                                                            
                                                                    $GradeType = '';

                                                                    echo '<th style="text-align:center;">'; 
                                                                    echo '&nbsp;(' .$GradeDetails['FromPercentage'] . ' - '.$GradeDetails['ToPercentage'] .$GradeType.')';
                                                                    
                                                                    if ($GradeDetails['Grade'] == 'E' )
                                                                    {
                                                                        if ($CurrentClassSections->GetClassID() >= 12)
                                                                        {
                                                                            $GradeType = '(FAILED)';   
                                                                        }
                                                                        else
                                                                        {
                                                                            $GradeType = '(NEEDS IMPROVEMENT)';   
                                                                        }
                                                                    }
                                                                    
                                                                    echo '<th style="text-align:center;">'; 
                                                                                echo '&nbsp; '.$GradeDetails['Grade'].$GradeType;
                                                                              echo '</th>';
                                                                    echo '</th>'; 
        
                                                                    $RowCounter++;  

                                                                    if ($RowCounter > 1) 
                                                                    {   
                                                                        echo '</tr>';
                                                                        $RowCounter = 0;
                                                                    }
                                                                }
 ?>                                                  
                                                        </thead>
                                                     </table>
                                                </div>
                                            </div>
                                    
                                        <br>
                                        <br>
                                        <br>
                                   <!--  </div> -->
                                    <div style="page-break-after: always;"></div>                                      
<?php    
                                }
?>                                        
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
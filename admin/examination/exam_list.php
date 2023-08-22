<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once('../../classes/school_administration/class.classes.php');
require_once('../../classes/school_administration/class.class_sections.php');

require_once('../../classes/examination/class.exam_types.php');
require_once('../../classes/examination/class.exams.php');

require_once('../../classes/class.ui_helpers.php');

require_once('../../includes/helpers.inc.php');
require_once('../../includes/global_defaults.inc.php');

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

if ($LoggedUser->HasPermissionForTask(TASK_LIST_EXAM) !== true)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}

$HasErrors = false;
$ExamClosedSuccessfully = false;
$RecordDeletedSuccessfully = false;

$ClassList =  array();
$ClassList = AddedClass::GetActiveClasses();

$ClassSectionList =  array();

$ExamTypeList = array();
$ExamTypeList = ExamType::GetActiveExamTypes();

$Clean = array();

$Clean['Process'] = 0;

$Clean['ExamID'] = 0;

$Clean['ClassID'] = 0;
$Clean['ClassSectionID'] = 0;
$Clean['ExamTypeID'] = 0;

// paging and sorting variables start here  //
$Clean['AllRecords'] = '';
$Clean['CurrentPage'] = 1;
$TotalPages = 0;

$Start = 0;
$Limit = 10;
// end of paging variables //

$AllExams = array();

if (isset($_GET['hdnProcess']))
{
    $Clean['Process'] = (int) $_GET['hdnProcess'];
}
else if (isset($_GET['Process']))
{
    $Clean['Process'] = (int) $_GET['Process'];
}
switch ($Clean['Process'])
{
	case 3:
        /*if ($LoggedUser->HasPermissionForTask(TASK_ADD_EDIT_TASK) !== true)
        {
            header('location:/admin/unauthorized_login_admin.php');
            exit;
        }*/
        
        if (isset($_GET['ExamID']))
        {
            $Clean['ExamID'] = (int) $_GET['ExamID'];           
        }
        
        if ($Clean['ExamID'] <= 0)
        {
            header('location:/admin/error.php');
            exit;
        }                       
            
        try
        {
            $ExamToClose = new Exam($Clean['ExamID']);
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
        
        $NewRecordValidator = new Validator();
        
        if ($ExamToClose->CheckMarksFeed())
        {
            $NewRecordValidator->AttachTextError('This exam cannot be closed. Marks not fedded for this exam.');
            $HasErrors = true;
            break;
        }
                
        if (!$ExamToClose->MarkExamAsClosed())
        {
            $NewRecordValidator->AttachTextError(ProcessErrors($ExamToClose->GetLastErrorCode()));
            $HasErrors = true;
            break;
        }
        
        $ExamClosedSuccessfully = true;
    break;

    case 5:
		/*if ($LoggedUser->HasPermissionForTask(TASK_ADD_EDIT_TASK) !== true)
		{
			header('location:/admin/unauthorized_login_admin.php');
			exit;
		}*/
		
		if (isset($_GET['ExamID']))
		{
			$Clean['ExamID'] = (int) $_GET['ExamID'];			
		}
		
		if ($Clean['ExamID'] <= 0)
		{
			header('location:/admin/error.php');
			exit;
		}						
			
		try
		{
			$ExamToDelete = new Exam($Clean['ExamID']);
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
		
		$NewRecordValidator = new Validator();
		
		if ($ExamToDelete->CheckDependencies())
		{
			$NewRecordValidator->AttachTextError('This exam cannot be deleted. There are dependent records for this exam.');
			$HasErrors = true;
			break;
		}
				
		if (!$ExamToDelete->Remove())
		{
			$NewRecordValidator->AttachTextError(ProcessErrors($ExamToDelete->GetLastErrorCode()));
			$HasErrors = true;
			break;
		}
		
		$RecordDeletedSuccessfully = true;
	break;

    case 7:        
        if (isset($_GET['drdClass']))
        {
            $Clean['ClassID'] = (int) $_GET['drdClass'];
        }
        elseif (isset($_GET['ClassID']))
        {
            $Clean['ClassID'] = (int) $_GET['ClassID'];
        }

        if (isset($_GET['drdClassSection']))
        {
            $Clean['ClassSectionID'] = (int) $_GET['drdClassSection'];
        }
        elseif (isset($_GET['ClassSectionID']))
        {
            $Clean['ClassSectionID'] = (int) $_GET['ClassSectionID'];
        }

        if (isset($_GET['drdExamType']))
        {
            $Clean['ExamTypeID'] = (int) $_GET['drdExamType'];
        }
        elseif (isset($_GET['ExamTypeID']))
        {
            $Clean['ExamTypeID'] = (int) $_GET['ExamTypeID'];
        }

        $NewRecordValidator = new Validator();
        
        if ($Clean['ClassID'] > 0 && $NewRecordValidator->ValidateInSelect($Clean['ClassID'], $ClassList, 'Please select a valid class.')) 
        {
            $ClassSectionList = ClassSections::GetClassSections($Clean['ClassID']);
            
            if ($Clean['ClassSectionID'] > 0)
            {
                $NewRecordValidator->ValidateInSelect($Clean['ClassSectionID'], $ClassSectionList, 'Please select a valid section.');
            }
        }

        if ($Clean['ExamTypeID'] > 0) 
        {
            $NewRecordValidator->ValidateInSelect($Clean['ExamTypeID'], $ExamTypeList, 'Please select a exam type.');
        }

        if ($NewRecordValidator->HasNotifications())
        {
            $HasErrors = true;
            break;
        }

        //set record filters        
        $Filters['ClassID'] = $Clean['ClassID'];
        $Filters['ClassSectionID'] = $Clean['ClassSectionID'];
        $Filters['ExamTypeID'] = $Clean['ExamTypeID'];        

        //get records count
        Exam::SearchExams($TotalRecords, true, $Filters);

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
             if ($Clean['AllRecords'] == 'All') 
            {
                $AllExams = Exam::SearchExams($TotalRecords, false, $Filters, 0, $TotalRecords);
            }
            else
            {
             $AllExams = Exam::SearchExams($TotalRecords, false, $Filters, $Start, $Limit);   
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
<title>Exam List</title>
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
                    <h1 class="page-header">Exam List</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
            <!-- /.row -->
            <div class="row">
                <div class="col-lg-12">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <strong>Filters</strong>
                        </div>
                         <div class="panel-body">
                            <div>
                                <div class="row" >
                                    <div class="col-lg-12">
<?php
                                    if ($HasErrors == true)
                                    {
                                        echo $NewRecordValidator->DisplayErrorsInTable() .'<br />';
                                    }
                                    else if ($LandingPageMode == 'AS')
                                    {
                                        echo '<div class="alert alert-success alert-top-margin">Record saved successfully.</div>';
                                    }
                                    else if ($RecordDeletedSuccessfully == true)
                                    {
                                        echo '<div class="alert alert-danger alert-top-margin">Record deleted successfully.</div>';
                                    }
                                    else if ($ExamClosedSuccessfully == true)
                                    {
                                        echo '<div class="alert alert-danger alert-top-margin">Exam closed successfully.</div>';
                                    }
                                    else if ($LandingPageMode == 'UD')
                                    {
                                        echo '<div class="alert alert-success alert-top-margin">Record updated successfully.</div>';
                                    }
?>
                                        <br>    
                                         <form class="form-horizontal" name="ExamList" id="ExamList" action="exam_list.php" method="get">
                                             
                                            <div class="form-group">
                                                <label for="ClassID" class="col-lg-2 control-label">Class</label>
                                                <div class="col-lg-4">
                                                    <select class="form-control" id="ClassID" name="drdClass">
                                                        <option value="0">-- All Class --</option>
<?php
                                                    foreach ($ClassList as $ClassID => $ClassName)
                                                    {
                                                        echo '<option' . (($Clean['ClassID'] == $ClassID) ? ' selected="selected"' : '') . ' value="' . $ClassID . '">' . $ClassName . '</option>';
                                                    }
?>
                                                    </select>
                                                </div>
                                                <label for="SectionID" class="col-lg-2 control-label">Section</label>
                                                <div class="col-lg-4">
                                                    <select class="form-control" id="ClassSectionID" name="drdClassSection">
                                                        <option value="0">-- All Section --</option>
<?php
                                                    foreach ($ClassSectionList as $ClassSectionID => $SectionName)
                                                    {
                                                        echo '<option' . (($Clean['ClassSectionID'] == $ClassSectionID) ? ' selected="selected"' : '') . ' value="' . $ClassSectionID . '">' . $SectionName . '</option>';
                                                    }
?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label for="ExamTypeID" class="col-lg-2 control-label">Exam Type</label>
                                                <div class="col-lg-4">
                                                    <select class="form-control" id="ExamTypeID" name="drdExamType">
                                                        <option value="0">-- All Exam Type --</option>
<?php
                                                    foreach ($ExamTypeList as $ExamTypeID => $ExamTypeName)
                                                    {
                                                        echo '<option' . (($Clean['ExamTypeID'] == $ExamTypeID) ? ' selected="selected"' : '') . ' value="' . $ExamTypeID . '">' . $ExamTypeName . '</option>';
                                                    }
?>
                                                    </select>
                                                </div>
                                            </div>
                                             
                                            <div class="form-group">
                                                <div class="col-sm-offset-2 col-lg-10">
                                                    <input type="hidden" name="hdnProcess" value="7"/>  
                                                    <button type="submit" class="btn btn-primary">Search</button>
                                                </div>
                                             </div>
                                        </form>
                                     </div>
                                </div>
                            </div>
                         </div>
                     </div>
                </div>
            </div>
<?php
        if ($Clean['Process'] == 7 && $HasErrors == false)
        {
?>
            <!-- /.row -->
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
                                   <div class="col-lg-6">
<?php
                                    if ($TotalPages > 1)
                                    {
                                        $AllParameters = $Filters;
                                        $AllParameters['Process'] = '7';
                                        $AllParameters = array('Process' => '7', 'ClassID' => $Clean['ClassID'], 'ClassSectionID' => $Clean['ClassSectionID'], 'ExamTypeID' => $Clean['ExamTypeID']);
                                        echo UIHelpers::GetPager('exam_list.php', $TotalPages, $Clean['CurrentPage'], $AllParameters);
                                    }
?>                                        
                                    </div>
                                    <div class="col-lg-6"></div>  
                                </div>
                                <div class="row">
                                    <div class="col-lg-6">
                                    <div class="add-new-btn-container"><a href="add_exam.php" class="btn btn-primary<?php //echo $LoggedUser->HasPermissionForTask(TASK_ADD_MENU) === true ? '' : ' disabled'; ?>" role="button">Add New Exam</a></div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="print-btn-container"><button id="PrintButton" type="submit" class="btn btn-primary">Print</button></div>
                                    </div>
                                </div>
<?php
                            if ($HasErrors == true)
                            {
                                echo $NewRecordValidator->DisplayErrorsInTable();
                            }
?>
                                <div class="row" id="RecordTableHeading">
                                    <div class="col-lg-12">
                                    	<div class="report-heading-container"><strong>Exam Details on <?php echo date('d-m-Y h:i A'); ?></strong></div>
                                    </div>
								</div>
                                <div class="row" id="RecordTable">
                                    <div class="col-lg-12">
                                        <table width="100%" class="table table-striped table-bordered table-hover" id="DataTableRecords">
                                            <thead>
                                                <tr>
                                                    <th>S. No</th>
                                                    <th>Exam</th>
                                                    <th>Exam Type</th>
                                                    <th>Class</th>
                                                    <th>Subject</th>
                                                    <th>Max Marks</th>
                                                    <th>Is Offline</th>
                                                    <th>Create User</th>
                                                    <th>Create Date</th>
													<th class="print-hidden">Feed Marks</th>
                                                    <th class="print-hidden">Operations</th>
                                                </tr>
                                            </thead>
                                            <tbody>
<?php
                                    if (is_array($AllExams) && count($AllExams) > 0)
                                    {
                                        $Counter = 0;
                                        foreach ($AllExams as $ExamID => $ExamDetails)
                                        {
?>
											<tr>
												<td><?php echo ++$Counter; ?></td>
												<td><?php echo $ExamDetails['ExamName']; ?></td>
												<td><?php echo $ExamDetails['ExamType']; ?></td>
												<td><?php echo $ExamDetails['Class']; ?></td>
                                                <td><?php echo $ExamDetails['Subject']; ?></td>
												<td><?php echo $ExamDetails['MaximumMarks']; ?></td>
												<td><?php echo (($ExamDetails['IsOffline']) ? 'Yes' : 'No'); ?></td>
												<td><?php echo $ExamDetails['CreateUserName']; ?></td>
												<td><?php echo date('d/m/Y', strtotime($ExamDetails['CreateDate'])); ?></td>

												<td>
<?php 
                                                    // echo ($ExamDetails['ExamClosed'] ? '<a href="feed_student_marks.php?ExamID=' . $ExamID . '" target="_blank">View Marks</a>' : '<a href="feed_student_marks.php?ExamID=' . $ExamID . '" target="_blank">Feed Marks</a>'); 
                
                                                    // echo '&nbsp;|&nbsp;'; 
                                                    echo ($ExamDetails['ExamClosed'] ? 'Exam Closed' : '<a href="exam_list.php?Process=3&amp;ExamID=' . $ExamID . '" class="close-exam">Close Exam</a>');
?>                                   
                                                </td>

												<td class="print-hidden">
<?php
												
												if ($LoggedUser->HasPermissionForTask(TASK_EDIT_EXAM) === true)
												{
													echo '<a href="edit_exam.php?Process=2&amp;ExamID=' . $ExamID . '">Edit</a>';
												}
												else
												{
													echo 'Edit';
												}

												echo '&nbsp;|&nbsp;';

												if ($LoggedUser->HasPermissionForTask(TASK_DELETE_EXAM) === true)
												{
													echo '<a href="exam_list.php?Process=5&amp;ExamID=' . $ExamID . '" class="delete-record">Delete</a>' ;
												}
												else
												{
													echo 'Delete';
												}
?>
												</td>
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

    $("body").on('click', '.close-exam', function()
    {   
        if (!confirm("You can not able to feed marks after closing this exam. Are you sure you want to close this exam?"))
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

    $('#ClassID').change(function(){

        var ClassID = parseInt($('#ClassID').val());
        
        if (ClassID <= 0)
        {
            $('#ClassSectionID').html('<option value="0">-- All Section --</option>');
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
                $('#ClassSectionID').html('<option value="0">-- All Section --</option>' + ResultArray[1]);
            }
        });
    });
});
</script>
<!-- JavaScript To Print A Report -->
<script src="js/print-report.js"></script>
<script src="/admin/js/print-report.js"></script>
</body>
</html>
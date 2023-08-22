<?php
require_once('../../classes/class.users.php');
require_once('../../classes/class.validation.php');
require_once('../../classes/class.authentication.php');

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
    header('location:/admin/unauthorized_login_admin.php');
    exit;
}
catch (Exception $e)
{
    header('location:/admin/unauthorized_login_admin.php');
    exit;
}
// END OF 1. //

if ($LoggedUser->HasPermissionForTask(TASK_REPORT_STUDENT_MARKS_FEEDING) !== true)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}

$ClassList =  array();
$ClassList = AddedClass::GetActiveClasses();

$ClassSectionList =  array();
$ClassSubjectList = array();

$ExamTypeList = array();
$ExamTypeList = ExamType::GetActiveExamTypes();

$TotalRecords = 0;

$Filters = array();

$HasErrors = false;

$ExamDetailList = array();

$Clean = array();

$Clean['Process'] = 0;

$Clean['ClassID'] = 0;
$Clean['ClassSectionID'] = 0;
$Clean['ExamTypeID'] = 0;

$Clean['PercentageRangeFirst'] = 0;
$Clean['PercentageRangeSecond'] = 0;

// paging and sorting variables start here  //
$Clean['CurrentPage'] = 1;
$TotalPages = 0;

$Start = 0;
$Limit = 100;
// end of paging variables //

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
    case 7:
        if (isset($_GET['drdClassID']))
        {
            $Clean['ClassID'] = (int) $_GET['drdClassID'];
        }
		else if (isset($_GET['ClassID']))
		{
			$Clean['ClassID'] = (int) $_GET['ClassID'];
		}
		
		if (isset($_GET['drdClassSectionID'])) 
		{
			$Clean['ClassSectionID'] = (int) $_GET['drdClassSectionID'];
		}
		else if (isset($_GET['ClassSectionID']))
		{
			$Clean['ClassSectionID'] = (int) $_GET['ClassSectionID'];
		}
		
        if (isset($_GET['drdExamTypeID'])) 
        {
            $Clean['ExamTypeID'] = (int) $_GET['drdExamTypeID'];
        }
		else if (isset($_GET['ExamTypeID']))
		{
			$Clean['ExamTypeID'] = (int) $_GET['ExamTypeID'];
		}
		
		if (isset($_GET['txtPercentageRangeFirst']))
        {
            $Clean['PercentageRangeFirst'] = (int) $_GET['txtPercentageRangeFirst'];
        }
		else if (isset($_GET['PercentageRangeFirst']))
		{
			$Clean['PercentageRangeFirst'] = (int) $_GET['PercentageRangeFirst'];
		}
		
		if (isset($_GET['txtPercentageRangeSecond']))
        {
            $Clean['PercentageRangeSecond'] = (int) $_GET['txtPercentageRangeSecond'];
        }
		else if (isset($_GET['PercentageRangeSecond']))
		{
			$Clean['PercentageRangeSecond'] = (int) $_GET['PercentageRangeSecond'];
		}
		
        $NewRecordValidator = new Validator();

		$NewRecordValidator->ValidateInSelect($Clean['ExamTypeID'], $ExamTypeList, 'Please select a exam.');
		
		if ($Clean['ClassID'] > 0 && $NewRecordValidator->ValidateInSelect($Clean['ClassID'], $ClassList, 'Please select a valid class.')) 
		{
			$ClassSectionList = ClassSections::GetClassSections($Clean['ClassID']);
			
			if ($Clean['ClassSectionID'] > 0)
			{
				$NewRecordValidator->ValidateInSelect($Clean['ClassSectionID'], $ClassSectionList, 'Please select a valid section.');
			}
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
        $Filters['PercentageRangeFirst'] = $Clean['PercentageRangeFirst'];
		$Filters['PercentageRangeSecond'] = $Clean['PercentageRangeSecond'];
 
        //get records count
        Exam::SearchExamReport($TotalRecords, true, $Filters);
  
        if ($TotalRecords > 0)
        {
            // Paging and sorting calculations start here.
            $TotalPages = (($TotalRecords % $Limit) == 0) ? $TotalRecords / $Limit : floor($TotalRecords / $Limit) + 1;

            if (isset($_GET['CurrentPage']))
            {
                $Clean['CurrentPage'] = (int) $_GET['CurrentPage'];
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
			
            $ExamDetailList = Exam::SearchExamReport($TotalRecords, false, $Filters, $Start, $Limit);
        }
	break;
}

require_once('../html_header.php');
?>
<title>Exam Report</title>
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
                    <h1 class="page-header">Exam Report</h1>
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
?>
                                         <form class="form-horizontal" name="FilterBranchStaff" id="FilterBranchStaff" action="exam_report_student_wise.php" method="get">
											<div class="form-group">
												<label for="ExamTypeID" class="col-lg-2 control-label">Select Exam</label>
                                                <div class="col-lg-4">
													<select class="form-control" id="ExamTypeID" name="drdExamTypeID">
														<option value="0">Select</option>
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
                                                <label for="ClassID" class="col-lg-2 control-label">Select Class</label>
                                                <div class="col-lg-4">
													<select class="form-control" id="ClassID" name="drdClassID">
														<option value="0">Select</option>
<?php
													foreach ($ClassList as $ClassID => $ClassName)
													{
														echo '<option' . (($Clean['ClassID'] == $ClassID) ? ' selected="selected"' : '') . ' value="' . $ClassID . '">' . $ClassName . '</option>';
													}
?>
													</select>
												</div>
												<label for="SectionID" class="col-lg-2 control-label">Select Section</label>
                                                <div class="col-lg-4">
													<select class="form-control" id="ClassSectionID" name="drdClassSectionID">
														<option value="0">Select</option>
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
                                                <label for="PercentageRangeFirst" class="col-lg-2 control-label">Percentage Between</label>
                                                <div class="col-lg-4">
													<input class="form-control" type="text" maxlength="3" id="PercentageRangeFirst" name="txtPercentageRangeFirst" value="<?php echo $Clean['PercentageRangeFirst'] ? $Clean['PercentageRangeFirst'] : ''; ?>" />
												</div>
												<label for="PercentageRangeSecond" class="col-lg-2 control-label">&nbsp;</label>
                                                <div class="col-lg-4">
													<input class="form-control" type="text" maxlength="3" id="PercentageRangeSecond" name="txtPercentageRangeSecond" value="<?php echo $Clean['PercentageRangeSecond'] ? $Clean['PercentageRangeSecond'] : ''; ?>" />
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

                                        echo UIHelpers::GetPager('exam_report_student_wise.php', $TotalPages, $Clean['CurrentPage'], $AllParameters);
                                    }
?>                                        
                                    </div>
                                    <div class="col-lg-6"></div>  
                                </div>
                                <div class="row" id="RecordTableHeading">
                                    <div class="col-lg-12">
                                        <div class="report-heading-container"><strong>Exam Details on <?php echo date('d-m-Y h:i A'); ?></strong></div>
                                    </div>
                                </div>
                                <div class="row" id="RecordTable">
                                    <div class="col-lg-12">
                                        <table width="100%" class="table table-striped table-bordered table-hover">
                                            <thead>
                                                <tr>
                                                    <th>S. No</th>
													<th>Class</th>
													
                                                    <th>Student Name</th>
													<th>Roll No.</th>
													
                                                    <th class="print-hidden">Operations</th>
                                                </tr>
                                            </thead>
                                            <tbody>
<?php
                                    if (is_array($ExamDetailList) && count($ExamDetailList) > 0)
                                    {
                                        $Counter = 0;

                                        foreach ($ExamDetailList as $StudentID => $StudentDetails)
                                        {
?>
											<tr>
												<td><?php echo ++$Counter; ?></td>
												<td><?php echo $StudentDetails['ClassName'] . ' (' . $StudentDetails['SectionName'] . ')'; ?></td>
												<td><?php echo $StudentDetails['FirstName'] . ' ' . $StudentDetails['LastName']; ?></td>
												<td><?php echo $StudentDetails['RollNumber']; ?></td>
												
												<td class="print-hidden">
													<a class="btn btn-primary" data-toggle="modal" data-target="#exampleModal<?php echo $StudentID; ?>">View Marks</a>
													
													<!-- Modal -->
													<div class="modal fade" id="exampleModal<?php echo $StudentID; ?>" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
													  <div class="modal-dialog" role="document">
														<div class="modal-content">
														  <div class="modal-header">
															  <h5 class="modal-title" id="exampleModalLabel"><strong>Exam Result</strong>
																<button type="button" class="close" data-dismiss="modal" aria-label="Close">
																	<span aria-hidden="true">&times;</span>
																  </button>
															</h5>
														  </div>
														  <div class="modal-body">
															  <div class="row">
																  <div class="form-group">
																	<label for="" class="col-lg-3 control-label">Student Name: </label>
																	<div class="col-lg-3">
																		<?php echo $StudentDetails['FirstName'] . ' ' . $StudentDetails['LastName']; ?>
																	</div>
																	
																	<label for="" class="col-lg-3 control-label">Class/Section: </label>
																	<div class="col-lg-3">
																		<?php echo $StudentDetails['ClassName'] . ' (' . $StudentDetails['SectionName'] . ')'; ?>
																	</div>
																</div>
																  
																<br />
																<div class="col-lg-12">
																	<table width="100%" class="table table-striped table-bordered table-hover">
																		<thead>
																			<tr>
																				<th>Subject</th>
																				<th>Maximum Marks</th>
																				<th>Maximum Obtained</th>
																			</tr>
  <?php
																		  foreach ($StudentDetails['SubjectMarksList'] as $ExamID => $ExamDetails)
																		  {
  ?>
																			<tr>
																				<td><?php echo $ExamDetails['Subject']; ?></td>
																				<td><?php echo $ExamDetails['MaximumMarks']; ?></td>
																				<td><?php echo $ExamDetails['Marks']; ?></td>
																			</tr>
  <?php
																		  }
  ?>
																		</thead>
																	</table>
																</div>
															  </div>
														  </div>
														  <div class="modal-footer">
															<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
															<button type="button" class="btn btn-primary" data-dismiss="modal" onclick="alert('Customisations are under process, please check back after some time !');">Generate Report Card</button>
														  </div>
														</div>
													  </div>
													</div>
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
if (PrintMessage($_GET, $Message))
{
?>
<script type="text/javascript">
    alert('<?php echo $Message; ?>');
</script>
<?php
}
?>  
<script type="text/javascript">
    $(document).ready(function() {
		$('#ClassID').change(function(){

			var ClassID = parseInt($(this).val());

			if (ClassID <= 0)
			{
				$('#ClassSectionID').html('<option value="0">Select</option>');
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
					$('#ClassSectionID').html('<option value="0">Select</option>' + ResultArray[1]);
				}
			});
		});
    });
</script>
<!-- JavaScript To Print A Report -->
<script src="/admin/js/print-report.js"></script>
<script src="/admin/js/print-report.js"></script>
</body>
</html>
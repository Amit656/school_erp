<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once("../../classes/school_administration/class.academic_years.php");
require_once('../../classes/school_administration/class.branch_staff.php');
require_once('../../classes/school_administration/class.classes.php');
require_once('../../classes/school_administration/class.class_sections.php');
require_once('../../classes/school_administration/class.students.php');
require_once('../../classes/school_administration/class.student_details.php');
require_once("../../classes/school_administration/class.class_attendence.php");

require_once("../../classes/class.sms_queue.php");

require_once("../../classes/class.date_processing.php");
require_once('../../classes/class.helpers.php');

require_once("../../includes/global_defaults.inc.php");	
require_once("../../includes/helpers.inc.php");

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

$IsClassTeacher = false;

if ($LoggedUser->HasPermissionForTask(TASK_ADD_SELF_CLASS_STUDENT_ATTENDANCE) == true)
{
    $IsClassTeacher = true;
}
else if ($LoggedUser->HasPermissionForTask(TASK_ADD_STUDENT_ATTENDANCE) !== true)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}

$AcademicYearName = '';
$AcademicYearID = AcademicYear::GetCurrentAcademicYear($AcademicYearName);

$AllClasses = array();
$AllClasses = AddedClass::GetActiveClasses();

$StudentsList = array();
$ClassSectionsList = array();

$ClassAttendenceID = 0;

$HasErrors = false;

$Clean = array();

$Clean['ClassSectionID'] = 0;

$Clean['Process'] = 0;
$Clean['AcademicYearID'] = $AcademicYearID;

$Clean['ClassID'] = 0;
$Clean['ClassID'] = key($AllClasses);

$ClassSectionsList = AddedClass::GetClassSections($Clean['ClassID']);

$Clean['ClassSectionID'] = 0;
$Clean['ClassSectionID'] = key($ClassSectionsList);

$Clean['AttendanceDate'] = '';
$Clean['AttendanceDate'] = date('d/m/Y');

$Clean['AttendenceStatusPresentStudentsList'] = array();

if ($IsClassTeacher)
{
    try
    {
        $CurrentBranchStaff = new BranchStaff(0, $LoggedUser->GetUserName());
        
        if (!$CurrentBranchStaff->GetClassSectionID($Clean['ClassSectionID']))
        {
            throw new Exception('xxx');
        }
        
        $CurrentClassSection = new ClassSections($Clean['ClassSectionID']);
        
        $Clean['ClassID'] = $CurrentClassSection->GetClassID();
        $Clean['ClassSectionID'] = $CurrentClassSection->GetClassSectionID();
        
        $ClassSectionsList = AddedClass::GetClassSections($Clean['ClassID']);
    }
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
}

if (isset($_POST['hdnProcess']))
{
	$Clean['Process'] = (int) $_POST['hdnProcess'];
}
elseif(isset($_GET['Process']))
{
	$Clean['Process'] = $_GET['Process'];
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
		
		if (isset($_POST['hdnAttendanceDate']))
		{
			$Clean['AttendanceDate'] = strip_tags(trim($_POST['hdnAttendanceDate']));
		}

		if (isset($_POST['chkAttendenceStatusPresentStudentsList']) && is_array($_POST['chkAttendenceStatusPresentStudentsList']))
		{
			$Clean['AttendenceStatusPresentStudentsList'] = $_POST['chkAttendenceStatusPresentStudentsList'];
		}

		$NewRecordValidator = new Validator();

		$NewRecordValidator->ValidateInSelect($Clean['ClassID'], $AllClasses, 'Unknown error, please try again');
		$NewRecordValidator->ValidateDate($Clean['AttendanceDate'], 'Please enter a valid attendence date.');
		
		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}

		$ClassSectionsList = AddedClass::GetClassSections($Clean['ClassID']);
		$NewRecordValidator->ValidateInSelect($Clean['ClassSectionID'], $ClassSectionsList, 'Unknown error, please try again.');

		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}

		$StudentsList = StudentDetail::GetStudentsByClassSectionID($Clean['ClassSectionID'], 'Active', $Clean['AcademicYearID']);

		foreach ($Clean['AttendenceStatusPresentStudentsList'] as $StudentID => $StudentsDetails)
		{
			$NewRecordValidator->ValidateInSelect($StudentID, $StudentsList, 'Unknown error, please try again.');
		}

		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}

		$AttendenceDate = date('Y-m-d', strtotime(DateProcessing::ToggleDateDayAndMonth(($Clean['AttendanceDate']))));

		$AttendenceStatusAbsentStudentsList = array();
		$AttendenceStatusAbsentStudentsList = array_diff_key($StudentsList, $Clean['AttendenceStatusPresentStudentsList']);

		if (ClassAttendence::IsAttendenceTaken($Clean['ClassSectionID'], date('Y-m-d', strtotime(DateProcessing::ToggleDateDayAndMonth(($Clean['AttendanceDate'])))), $ClassAttendenceID))
		{
			$CurrentClassAttendence = new ClassAttendence($ClassAttendenceID);
		}
		else
		{
			$CurrentClassAttendence = new ClassAttendence();

			$CurrentClassAttendence->SetAttendenceDate(date('Y-m-d', strtotime(DateProcessing::ToggleDateDayAndMonth(($Clean['AttendanceDate'])))));
			$CurrentClassAttendence->SetCreateUserID($LoggedUser->GetUserID());
		}
		
		$CurrentClassAttendence->SetClassSectionID($Clean['ClassSectionID']);
		$CurrentClassAttendence->SetAttendenceStatusAbsentStudentsList($AttendenceStatusAbsentStudentsList);
		
		if (!$CurrentClassAttendence->Save())
		{
			$NewRecordValidator->AttachTextError(ProcessErrors($CurrentClassAttendence->GetLastErrorCode()));
			$HasErrors = true;
			break;
		}
		
		/*if (date('Y-m-d', strtotime(DateProcessing::ToggleDateDayAndMonth(($Clean['AttendanceDate'])))) == date('Y-m-d'))
		{
		    $MSGContent = 'Dear Parents, 
    		Your ward ({StudentName}) is absent today.';
    		
    		if (count($AttendenceStatusAbsentStudentsList) > 0)
    		{
    		    foreach ($AttendenceStatusAbsentStudentsList as $StudentID => $Details)
    		    {
    		        $CurrentStudent = new StudentDetail($StudentID);
    		        
    		        $MSG = str_replace('{StudentName}', $CurrentStudent->GetFirstName() . ' ' . $CurrentStudent->GetLastName(), $MSGContent);
    		        
    		        $NewSMSQueue = new SMSQueue();
    
        			$NewSMSQueue->SetPhoneNumber($CurrentStudent->GetMobileNumber());
        			$NewSMSQueue->SetSMSMessage($MSG);
        			$NewSMSQueue->SetCreateUserID($LoggedUser->GetUserID());
        
        			$NewSMSQueue->Save();
    		    }
    		}
		}*/
		
		$GetNextClassSection = new AddedClass($Clean['ClassID']);
		$GetNextClassSection->GetNextClassIDSectionID($Clean['ClassSectionID'], $NextClassID, $NextClassSectionID);	

		header('location:add_class_attendence.php?Mode=ED&AttendanceDate=' . $Clean['AttendanceDate'] . '&ClassID=' . $NextClassID . '&ClassSectionID=' . $NextClassSectionID);
		exit;
		break;

	case 7:
		if (isset($_POST['txtAttendanceDate']))
		{
			$Clean['AttendanceDate'] = strip_tags(trim($_POST['txtAttendanceDate']));
		}

		if (isset($_POST['drdClassID']))
		{
			$Clean['ClassID'] = (int) $_POST['drdClassID'];
		}

		if (isset($_POST['drdClassSectionID']))
		{
			$Clean['ClassSectionID'] = (int) $_POST['drdClassSectionID'];
		}

		$NewRecordValidator = new Validator();

		$NewRecordValidator->ValidateDate($Clean['AttendanceDate'], 'Please enter a valid attendence date.');
		$NewRecordValidator->ValidateInSelect($Clean['ClassID'], $AllClasses, 'Unknown error, please try again');

		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}

		$ClassSectionsList = AddedClass::GetClassSections($Clean['ClassID']);
		$NewRecordValidator->ValidateInSelect($Clean['ClassSectionID'], $ClassSectionsList, 'Unknown error, please try again.');

		$AttendenceDate = date('Y-m-d', strtotime(DateProcessing::ToggleDateDayAndMonth(($Clean['AttendanceDate']))));
        $StartDate = '';	

        $StartDate = Helpers::GetStartDateOfTheMonth(date('M', strtotime($AttendenceDate)));

        $WorkingDayStartDate = $StartDate;
        $WorkingDayEndDate = date('Y-m-t', strtotime($AttendenceDate));

		if (!Helpers::GetIsClassAttendanceDateIsWorkingDate($WorkingDayStartDate, $WorkingDayEndDate, $Clean['ClassSectionID'], $AttendenceDate))
		{
			$NewRecordValidator->AttachTextError('this attendance date is not a working day, accordingly to global setting or academic event.');
		}

		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}

		$StudentsList = StudentDetail::GetStudentsByClassSectionID($Clean['ClassSectionID'], 'Active', $Clean['AcademicYearID']);

		if (!ClassAttendence::IsAttendenceTaken($Clean['ClassSectionID'], $AttendenceDate, $ClassAttendenceID))
		{
			$Clean['AttendenceStatusPresentStudentsList'] = $StudentsList;
		}
		else
		{
			$NewRecordValidator->AttachTextError('Attendence is already taken of this class, section on date.');

			$CurrentClassAttendence = new ClassAttendence($ClassAttendenceID);
			$CurrentClassAttendence->FillAttendenceStatus();

			$Clean['AttendenceStatusPresentStudentsList'] = $CurrentClassAttendence->GetAttendenceStatusPresentStudentsList();
		
			$HasErrors = true;
		}
		break;

	default:
		if (isset($_GET['ClassID']))
		{
			$Clean['ClassID'] = (int) $_GET['ClassID'];

			if(in_array($Clean['ClassID'], $AllClasses))
			{
				$Clean['ClassID'] = key($AllClasses);
			}

			$ClassSectionsList = AddedClass::GetClassSections($Clean['ClassID']);

			if (isset($_GET['ClassSectionID']))
			{
				$Clean['ClassSectionID'] = (int) $_GET['ClassSectionID'];

				if(in_array($Clean['ClassSectionID'], $ClassSectionsList))
				{
					$Clean['ClassSectionID'] = key($ClassSectionsList);
				}
			}
		}

		if (isset($_GET['AttendanceDate']))
		{
			$Clean['AttendanceDate'] = strip_tags(trim($_GET['AttendanceDate']));
		}
		break;
}
require_once('../html_header.php');
?>
<title>Mark Class Attendence</title>
<link href="../vendor/jquery-ui/jquery-ui.min.css" rel="stylesheet">
<style type="text/css">
.col-md-4:not(:empty)
{
    border: 1px solid #ccc;
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
                    <h1 class="page-header">Mark Class Attendence</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="SearchClassAttendence" action="add_class_attendence.php" method="post">
            	<div class="panel panel-default">
                    <div class="panel-heading">
                       Select Class and Section for Attendance
                    </div>
                    <div class="panel-body">
<?php
						if ($HasErrors == true)
						{
							echo $NewRecordValidator->DisplayErrors();
						}
?>                    
                        <div class="form-group">
                            <label for="AttendanceDate" class="col-lg-2 control-label">Attendance Date</label>
                            <div class="col-lg-3">
                            	<input type="text" class="form-control dtepicker" maxlength="10" id="AttendanceDate" name="txtAttendanceDate" value="<?php echo $Clean['AttendanceDate']; ?>"/>
                            </div>
                        </div>
                    	<div class="form-group">
                            <label for="ClassList" class="col-lg-2 control-label">Class List</label>
                            <div class="col-lg-3">
                            	<select class="form-control"  name="drdClassID" id="ClassID" <?php echo $IsClassTeacher ? 'disabled="disabled"' : ''; ?>>
<?php
									foreach ($AllClasses as $ClassID => $ClassesName)
									{
?>
										<option <?php echo ($ClassID == $Clean['ClassID'] ? 'selected="selected"' : ''); ?> value="<?php echo $ClassID; ?>"><?php echo $ClassesName; ?></option>
<?php
									}
?>
                            	</select>
                            </div>
                            <label for="SectionID" class="col-lg-2 control-label">Section List</label>
                            <div class="col-lg-3">
                            	<select class="form-control" name="drdClassSectionID" id="SectionID" <?php echo $IsClassTeacher ? 'disabled="disabled"' : ''; ?>>
									<option value="0">Select Section</option>
<?php
									if (is_array($ClassSectionsList) && count($ClassSectionsList) > 0)
									{
										foreach ($ClassSectionsList as $ClassSectionID => $SectionName) 
										{
											echo '<option ' . ($Clean['ClassSectionID'] == $ClassSectionID ? 'selected="selected"' : '') . ' value="' . $ClassSectionID . '">' . $SectionName . '</option>'	;
										}
									}
?>
								</select>
                            </div>
                        </div> 
                        <div class="form-group">
						    <div class="col-sm-offset-2 col-lg-10">
						    	<input type="hidden" name="hdnProcess" value="7" />
						        <button type="submit" class="btn btn-primary">View Students</button>
						    </div>
						</div>
                    </div>
                </div>
            </form>
<?php
			if($Clean['Process'] == 7)
			{	
?>
				<form class="form-horizontal" name="AddClassAttendence" action="add_class_attendence.php" method="post">
	            	<div class="panel panel-default">
	                    <div class="panel-heading">
	                       Studnet List
	                    </div>
	                    <div class="panel-body">
	                    	<div class="row">
<?php
							if(count($StudentsList) > 0)
							{
?>
								<div class="col-md-4">
	                                <label><input type="checkbox" id="SelectAll" name="chkSelectAll" value="1" <?php echo(count($StudentsList) == count($Clean['AttendenceStatusPresentStudentsList'])) ? 'checked="checked"' : '0';?> />Select All</label>
	                            </div>
	                            <div class="col-md-4">
	                                <label><input type="checkbox" id="ClearAll" name="chkClearAll" value="1" />Clear All</label>
	                            </div>
	                            <div class="col-md-4">                             
	                            </div>
	                        </div>
<?php
		                        $TotalStudents = count($StudentsList);

		                        if ($TotalStudents > 0)
		                        {
		                            $CurrentStudentCounter = 1;
		                            $CurrentRowTDCounter = 1;
		                          
		                            foreach($StudentsList as $StudentID => $StudentsListDetails)
		                            {
		                                if ($CurrentStudentCounter == 1 || ($CurrentStudentCounter - 1) % 3 == 0)
		                                {
		                                    echo '<div class="row">';
		                                }
		                                
		                                if (array_key_exists($StudentID, $Clean['AttendenceStatusPresentStudentsList']))
		                                {
		                                    echo '<div class="col-md-4"><label style="font-size:14px; font-weight:400;"><input style="vertical-align:top;" type="checkbox" class="StudentList" checked="checked" id="AttendenceStatusPresentStudentsList' . $StudentID . '" name="chkAttendenceStatusPresentStudentsList[' . $StudentID . ']" value="' . $StudentID . '" /> ' . $StudentsListDetails['FirstName'] .' '. $StudentsListDetails['LastName'] .'(' . $StudentsListDetails['RollNumber'] . ')</label></div>';
		                                }
		                                else
		                                {
		                                    echo '<div class="col-md-4"><label style="font-size:14px; font-weight:400;"><input style="vertical-align:top;" type="checkbox" class="StudentList" id="AttendenceStatusPresentStudentsList' . $StudentID . '" name="chkAttendenceStatusPresentStudentsList[' . $StudentID . ']" value="' . $StudentID . '" /> ' . $StudentsListDetails['FirstName']  .' '. $StudentsListDetails['LastName'] . '(' . $StudentsListDetails['RollNumber'] . ')</label></div>';
		                                }
		                                
		                                $CurrentRowTDCounter++;
		                                
		                                if ($CurrentStudentCounter % 3 == 0)
		                                {
		                                    $CurrentRowTDCounter = 1;
		                                    echo '</div>';
		                                }
		                                
		                                $CurrentStudentCounter++;																		
		                            }
		                        }
		                        if ($CurrentRowTDCounter > 1)
		                        {
		                            for ($i = 1; $i <= (4 - $CurrentRowTDCounter); $i++)
		                            { 
		                                echo '<div class="col-md-4"></div>';
		                            }
		                            
		                            echo '</div><br>';
		                        }
?>                  
								<div class="row">
									<div class="col-lg-12">							
										<div class="form-group">
										    <div class="col-sm-offset-2 col-lg-10">
										    	<input type="hidden" name="hdnAttendanceDate" value="<?php echo $Clean['AttendanceDate']; ?>" />
										    	<input type="hidden" name="hdnClassID" value="<?php echo $Clean['ClassID']; ?>" />
										    	<input type="hidden" name="hdnClassSectionID" value="<?php echo $Clean['ClassSectionID']; ?>" />
										    	<input type="hidden" name="hdnProcess" value="1"/>
										        <button type="submit" class="btn btn-primary">Save</button>
										    </div>
										</div>
<?php
							}
?>
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
if (PrintMessage($_GET, $Message))
{
?>
    <script type="text/javascript">
        alert('<?php echo $Message; ?>');
    </script>
<?php
}
?>	ss
<script src="/admin/vendor/jquery-ui/jquery-ui.min.js"></script>
<script type="text/javascript">
	$(function()
		{
			$(".dtepicker").datepicker({
                changeMonth: true,
                changeYear: true,
                dateFormat: 'dd/mm/yy'
        	});

        	$('#ClassID').change(function()
        	{
				var ClassID = parseInt($(this).val());
				
				if (ClassID <= 0)
				{
					$('#SectionID').html('<option value="0">Select Section</option>');
					return false;
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
						$('#SectionID').html(ResultArray[1]);
					}
				 });
			});

			$('#SelectAll').click(function()
			{
				if($('#SelectAll').prop("checked"))
				{
					$('#ClearAll').prop("checked", "");
					$('.StudentList').prop("checked", "checked");
				}
				else
				{
					$('.StudentList').prop("checked", false);
				}
			});
		
		$('#ClearAll').click(function()
		{
			if($('#ClearAll').prop("checked"))
			{
				$('#SelectAll').prop("checked", "");
				$('.StudentList').prop("checked", "");
			}
		});
		
		$('.StudentList').click(function()
		{
			$('#SelectAll').prop("checked", "");
			$('#ClearAll').prop("checked", "");		
		});
	});
</script>
</body>
</html>
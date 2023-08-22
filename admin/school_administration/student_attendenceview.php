<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once("../../classes/school_administration/class.academic_years.php");
require_once('../../classes/school_administration/class.classes.php');
require_once('../../classes/school_administration/class.students.php');
require_once('../../classes/school_administration/class.student_details.php');
require_once("../../classes/school_administration/class.class_attendence.php");

require_once("../../classes/class.date_processing.php");

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

if ($LoggedUser->HasPermissionForTask(TASK_VIEW_STUDENT_ATTENDANCE) !== true)
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

$AcademicYearName = '';
$AcademicYearID = AcademicYear::GetCurrentAcademicYear($AcademicYearName);

$AllClasses = array();
$AllClasses = AddedClass::GetActiveClasses();

$StudentsList = array();
$ClassSectionsList = array();

$ClassAttendenceID = 0;

$HasErrors = false;

$Clean = array();
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

$TotalPages = 0;

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

		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}

		$StudentsList = StudentDetail::GetStudentsByClassSectionID($Clean['ClassSectionID'], 'Active', $Clean['AcademicYearID']);

		if (!ClassAttendence::IsAttendenceTaken($Clean['ClassSectionID'], date('Y-m-d', strtotime(DateProcessing::ToggleDateDayAndMonth(($Clean['AttendanceDate'])))), $ClassAttendenceID))
		{	
			$NewRecordValidator->AttachTextError('Attendence is not taken of this class, section on date.');
			$HasErrors = true;
		}
		else
		{
			$CurrentClassAttendence = new ClassAttendence($ClassAttendenceID);
			$CurrentClassAttendence->ViewstudentAttendenceStatus();

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
<title>Student Attendence</title>
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
                    <h1 class="page-header">Student View Attendence</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="SearchClassAttendence" action="student_attendenceview.php" method="post">
            	<div class="panel panel-default">
                    <div class="panel-heading">
                      Student View Attendance
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
                            	<select class="form-control"  name="drdClassID" id="ClassID">
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
                            	<select class="form-control" name="drdClassSectionID" id="SectionID">
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
						    <div class="col-sm-offset-2 col-lg-12">
						    	<input type="hidden" name="hdnProcess" value="7" />
						        <button type="submit" class="btn btn-primary">Search</button>
						    </div>
						</div>
                    </div>
                </div>
            </form>

        <!-- /#page-wrapper -->
        <!--table start-->

        <?php
        if ($Clean['Process'] == 7 && count($Clean['AttendenceStatusPresentStudentsList']) > 0)
        {
?>				
            <div class="row">
                <div class="col-lg-12">
                    <div class="panel panel-default">
                        <!-- /.panel-heading -->
                        <div class="panel-body">
                            <div>
                                <br/>
                                <!-- <div class="row">
                                   <div class="col-lg-6">
<?php
                                    if ($TotalPages > 1)
                                    {
                                        $AllParameters = $Filters;
                                        $AllParameters['Process'] = '7';

                                        echo UIHelpers::GetPager('branch_staff_list.php', $TotalPages, $Clean['CurrentPage'], $AllParameters);
                                    }
?>                                        
                                    </div>
                                    <div class="col-lg-6"></div>  
                                </div> -->
<!-- <?php
                            if ($HasErrors == true)
                            {
                                echo $NewRecordValidator->DisplayErrorsInTable();
                            }
?> -->
                                <div class="row" id="RecordTableHeading">
                                    <div class="col-lg-12">
                                        <div class="report-heading-container"><strong>Student Attendence on <?php echo date('d-m-Y h:i A'); ?></strong></div>
                                    </div>
                                </div>
                                <div class="row" id="RecordTable">
                                    <div class="col-lg-12">
                                        <table width="100%" class="table table-striped table-bordered table-hover">
                                            <thead>
                                                <tr>
                                                    <th>S. No</th>
                                                    <th>Student Name</th>
                                                    <th>Attendence Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
    <?php
                                    if (is_array($StudentsList) && count($StudentsList) > 0)
                                    {
                                        $Counter = 0;

                                        foreach ($StudentsList as $StudentID => $StudentsListDetails)
                                        {
    ?>
                                                <tr>
                                                    <td><?php echo ++$Counter; ?></td>
                                                    <td><?php echo $StudentsListDetails['FirstName'] .' '. $StudentsListDetails['LastName']; ?></td>
                                                    <td><?php echo (array_key_exists($StudentID, $Clean['AttendenceStatusPresentStudentsList'])) ? '<button class="btn btn-success">P</button>' : '<button class="btn btn-danger">A</button>'; ?></td>
                                                </tr>
    <?php
                                        }
                                    }
                                    else
                                    {
    ?>
                                                <tr>
                                                    <td colspan="11">No Records</td>
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
       
         <!--table end-->
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
	});
</script>
</body>
</html>
<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once("../../classes/class.ui_helpers.php");

require_once("../../classes/school_administration/class.academic_years.php");
require_once("../../classes/school_administration/class.classes.php");
require_once('../../classes/school_administration/class.class_sections.php');
require_once('../../classes/school_administration/class.section_master.php');

require_once('../../classes/school_administration/class.students.php');
require_once('../../classes/school_administration/class.student_details.php');

require_once("../../classes/transport_management/class.routes.php");
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
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}
catch (Exception $e)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}
// END OF 1. //

if ($LoggedUser->HasPermissionForTask(TASK_REPORT_STUDENT_VEHICLE) !== true)
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

$Filters = array();

$RouteList = array();
$RouteList = Route::GetActiveRoutes();

$AcademicYears = array();
$AcademicYears = AcademicYear::GetAllAcademicYears();

$AllSectionMasters = array();
$AllSectionMasters = SectionMaster::GetActiveSectionMasters();

$ClassList = array();
$ClassList = AddedClass::GetActiveClasses();

$ClassSectionsList = array();
$StudentsList = array();
$AreasList = array();

$StudentVehicleList = array();

$RecordDeletedSuccessfully = false;
$HasErrors = false;
$TotalRecords = 0;

$Clean = array();
$Clean['Process'] = 0;

$Clean['RouteID'] = 0;

$Clean['AcademicYearID'] = 0;

$Clean['ClassID'] = 0;
$Clean['ClassSectionID'] = 0;
$Clean['AreaWiseFeeID'] = 0;

$Clean['StudentID'] = 0;

$Clean['ActiveStatus'] = 0;

// paging and sorting variables start here  //
$Clean['AllRecords'] = '';
$Clean['CurrentPage'] = 1;
$TotalPages = 0;

$Start = 0;
$Limit = 30;
// end of paging variables//

if (isset($_GET['hdnProcess']))
{
	$Clean['Process'] = (int) $_GET['hdnProcess'];
}
elseif (isset($_GET['Process']))
{
	$Clean['Process'] = (int) $_GET['Process'];
}
switch ($Clean['Process'])
{
	case 5:
		if ($LoggedUser->HasPermissionForTask(TASK_DELETE_STUDENT_VEHICLE) !== true)
		{
			header('location:/admin/unauthorized_login_admin.php');
			exit;
		}

		if (isset($_GET['StudentVehicleID']))
		{
			$Clean['StudentVehicleID'] = (int) $_GET['StudentVehicleID'];
		}

		if ($Clean['StudentVehicleID'] <= 0)
		{
			header('location:../error_page.php');
			exit;
		}

		try
		{
			$StudentVehicleToDelete = new StudentVehicle($Clean['StudentVehicleID']);
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

		// $SearchValidator = new Validator();
		// if ($StudentVehicleToDelete->CheckDependencies())
		// {
		//     $SearchValidator->AttachTextError('This Student Vehicle cannot be deleted. There are dependent records for this Student Vehicle.');
		//     $HasErrors = true;
		//     break;
		// }

		if (!$StudentVehicleToDelete->Remove())
		{
			$SearchValidator->AttachTextError(ProcessErrors($StudentVehicleToDelete->GetLastErrorCode()));
			$HasErrors = true;
			break;
		}

		$RecordDeletedSuccessfully = true;
		break;

	case 7:
		if (isset($_GET['drdRoute']))
		{
			$Clean['RouteID'] = (int) $_GET['drdRoute'];
		}
		elseif (isset($_GET['RouteID']))
		{
			$Clean['RouteID'] = (int) $_GET['RouteID'];
		}
		if (isset($_GET['drdAreaWiseFeeID']))
		{
			$Clean['AreaWiseFeeID'] = (int) $_GET['drdAreaWiseFeeID'];
		}
		elseif (isset($_GET['AreaWiseFeeID']))
		{
			$Clean['AreaWiseFeeID'] = (int) $_GET['AreaWiseFeeID'];
		}
		if (isset($_GET['drdAcademicYearID']))
		{
			$Clean['AcademicYearID'] = (int) $_GET['drdAcademicYearID'];
		}
		elseif (isset($_GET['AcademicYearID']))
		{
			$Clean['AcademicYearID'] = (int) $_GET['AcademicYearID'];
		}
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
		if (isset($_GET['drdStudent']))
		{
			$Clean['StudentID'] = (int) $_GET['drdStudent'];
		}
		elseif (isset($_GET['StudentID']))
		{
			$Clean['StudentID'] = (int) $_GET['StudentID'];
		}
		if (isset($_GET['optActiveStatus']))
		{
			$Clean['ActiveStatus'] = (int) $_GET['optActiveStatus'];
		}
		elseif (isset($_GET['ActiveStatus']))
		{
			$Clean['ActiveStatus'] = (int) $_GET['ActiveStatus'];
		}

		$SearchValidator = new Validator();

		if ($Clean['RouteID'] != 0)
		{
			if ($SearchValidator->ValidateInSelect($Clean['RouteID'], $RouteList, 'Please select a valid route.'))
			{
				$AreasList = AreaWiseFee::GetRouteAreas($Clean['RouteID']);

				if ($Clean['AreaWiseFeeID'] != 0)
				{
					$SearchValidator->ValidateInSelect($Clean['AreaWiseFeeID'], $AreasList, 'Please select a valid area.');
				}
			}
		}

		$SearchValidator->ValidateInSelect($Clean['AcademicYearID'], $AcademicYears, 'Unknown error, please try again.');

		if ($Clean['ClassID'] != 0)
		{
			if ($SearchValidator->ValidateInSelect($Clean['ClassID'], $ClassList, 'Please select a valid class.'))
			{
				$ClassSectionsList = ClassSections::GetClassSections($Clean['ClassID']);

				if ($Clean['ClassSectionID'] != 0)
				{
					if ($SearchValidator->ValidateInSelect($Clean['ClassSectionID'], $ClassSectionsList, 'Please select a valid section.'))
					{
						$StudentsList = StudentDetail::GetStudentsByClassSectionID($Clean['ClassSectionID'], 'Active', $Clean['AcademicYearID']);
						if ($Clean['StudentID'] != 0)
						{
							$SearchValidator->ValidateInSelect($Clean['StudentID'], $StudentsList, 'Please select a valid student.');
						}
					}
				}
			}
		}

		if ($Clean['ActiveStatus'] < 0 || $Clean['ActiveStatus'] > 2)
		{
			$SearchValidator->AttachTextError('Unknown Error, Please try again.');
		}

		if ($SearchValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}

		//set record filters
		$Filters['RouteID'] = $Clean['RouteID'];
		$Filters['AreaWiseFeeID'] = $Clean['AreaWiseFeeID'];
		$Filters['AcademicYearID'] = $Clean['AcademicYearID'];
		$Filters['ClassID'] = $Clean['ClassID'];
		$Filters['ClassSectionID'] = $Clean['ClassSectionID'];
		$Filters['StudentID'] = $Clean['StudentID'];
		$Filters['ActiveStatus'] = $Clean['ActiveStatus'];

		//get records count
		StudentVehicle::SearchStudentVehicles($TotalRecords, true, $Filters);

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
				$StudentVehicleList = StudentVehicle::SearchStudentVehicles($TotalRecords, false, $Filters, 0, $TotalRecords);
			}
			else
			{
				$StudentVehicleList = StudentVehicle::SearchStudentVehicles($TotalRecords, false, $Filters, $Start, $Limit);
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
<title>Student Vehicle Report</title>
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
                    <h1 class="page-header">Student Vehicle Report</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
            <form class="form-horizontal" name="frmstudentVehicleReport" action="student_vehicle_report.php" method="get">
                <div class="panel panel-default" id="accordion">
                    <div class="panel-heading">
                        <strong><a data-toggle="collapse" data-parent="#accordion" href="#collapseOne">Filters</a></strong>
                    </div>
                    <div id="collapseOne" class="panel-collapse collapse in">
                        <div class="panel-body">
							<?php
							if ($HasErrors == true)
							{
								echo $SearchValidator->DisplayErrors();
							}
							else if ($LandingPageMode == 'AS')
							{
								echo '<div class="alert alert-success">Record saved successfully.</div>';
							}
							else if ($RecordDeletedSuccessfully == true)
							{
								echo '<div class="alert alert-danger">Record deleted successfully.</div>';
							}
							else if ($LandingPageMode == 'UD')
							{
								echo '<div class="alert alert-success">Record updated successfully.</div>';
							}
							?>
							<div class="form-group">
                                <label for="AcademicYearID" class="col-lg-2 control-label">Academic Session</label>
                                <div class="col-lg-4">
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
                            </div>
                            <div class="form-group">
                                <label for="Route" class="col-lg-2 control-label">Route</label>
                                <div class="col-lg-4">
                                    <select class="form-control"  name="drdRoute" id="Route">
                                        <option  value="0" >-- All Route --</option>
										<?php
										if (is_array($RouteList) && count($RouteList) > 0)
										{
											foreach ($RouteList as $RouteID => $RouteNumber)
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
                                <div class="col-lg-4">
                                    <select class="form-control"  name="drdAreaWiseFeeID" id="Areas">
                                        <option  value="0" >-- All Area --</option>
										<?php
										if (is_array($AreasList) && count($AreasList) > 0)
										{
											foreach ($AreasList as $AreaWiseFeeID => $AreaName)
											{
												echo '<option ' . ($Clean['AreaWiseFeeID'] == $AreaWiseFeeID ? 'selected="selected"' : '') . ' value="' . $AreaWiseFeeID . '">' . $AreaName . '</option>';
											}
										}
										?>
                                    </select>
                                </div>
                            </div>
							
                            <div class="form-group">
                                <label for="Class" class="col-lg-2 control-label">Class</label>
                                <div class="col-lg-4">
                                    <select class="form-control"  name="drdClass" id="Class">
                                        <option  value="0" >-- All Class --</option>
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
                            </div>
                            <div class="form-group">
                                <label for="ClassSection" class="col-lg-2 control-label">Section</label>
                                <div class="col-lg-4">
                                    <select class="form-control" name="drdClassSection" id="ClassSection">
                                        <option value="0">-- All Section --</option>
										<?php
										if (is_array($ClassSectionsList) && count($ClassSectionsList) > 0)
										{
											foreach ($ClassSectionsList as $ClassSectionID => $SectionName)
											{
												echo '<option ' . ($Clean['ClassSectionID'] == $ClassSectionID ? 'selected="selected"' : '') . ' value="' . $ClassSectionID . '">' . $SectionName . '</option>';
											}
										}
										?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="Student" class="col-lg-2 control-label"> Student</label>
                                <div class="col-lg-4">
                                    <select class="form-control" name="drdStudent" id="Student">
                                        <option value="0">-- All Student --</option>
										<?php
										if (is_array($StudentsList) && count($StudentsList) > 0)
										{
											foreach ($StudentsList as $StudentID => $StudentDetails)
											{
												echo '<option ' . ($Clean['StudentID'] == $StudentID ? 'selected="selected"' : '') . ' value="' . $StudentID . '">' . $StudentDetails['FirstName'] . ' ' . $StudentDetails['LastName'] . ' (' . $StudentDetails['RollNumber'] . ')</option>';
											}
										}
										?>
                                    </select>
                                </div>                                
                            </div>

                            <div class="form-group">
                                <label for="ActiveStatus" class="col-lg-2 control-label">Status</label>
                                <div class="col-lg-6">
                                    <label class="radio-inline">
                                        <input type="radio" <?php echo (($Clean['ActiveStatus'] == 0) ? 'checked="checked"' : ''); ?> name="optActiveStatus" id="ActiveStatus" value="0" checked>All Student vehicle
                                    </label>
                                    <label class="radio-inline">
                                        <input type="radio" <?php echo (($Clean['ActiveStatus'] == 1) ? 'checked="checked"' : ''); ?> name="optActiveStatus" id="ActiveStatus" value="1">Active Student vehicle
                                    </label>
                                    <label class="radio-inline">
                                        <input type="radio" <?php echo (($Clean['ActiveStatus'] == 2) ? 'checked="checked"' : ''); ?> name="optActiveStatus" id="ActiveStatus" value="2">In-Active Student vehicle
                                    </label>
                                </div>
                            </div>                    
                            <div class="form-group">
                                <div class="col-lg-offset-2 col-lg-10">
                                    <input type="hidden" name="hdnProcess" value="7" />
                                    <button type="submit" class="btn btn-primary"><i class="fa fa-search"></i>&nbsp;Search</button>
                                </div>
                            </div>
                        </div>
                    </div>
				</div>
            </form>
            <!-- /.row -->
			<?php
			if ($Clean['Process'] == 7 && $HasErrors == false)
			{
				$ReportHeaderText = '';

				if ($Clean['RouteID'] != 0)
				{
					$ReportHeaderText .= ' RouteNumber: ' . $RouteList[$Clean['RouteID']] . ',';
				}

				if ($Clean['AreaWiseFeeID'] != 0)
				{
					$ReportHeaderText .= ' AreaName: ' . $AreasList[$Clean['AreaWiseFeeID']] . ',';
				}

				if ($Clean['ClassID'] != 0)
				{
					$ReportHeaderText .= ' ClassName: ' . $ClassList[$Clean['ClassID']] . ',';
				}

				if ($Clean['ClassSectionID'] != 0)
				{
					$ReportHeaderText .= ' SectionName: ' . $ClassSectionsList[$Clean['ClassSectionID']] . ',';
				}

				if ($Clean['StudentID'] != 0)
				{
					$ReportHeaderText .= ' StudentName: ' . $StudentsList[$Clean['StudentID']]['FirstName'] . ' ' . $StudentsList[$Clean['StudentID']]['LastName'] . ',';
				}

				if ($Clean['ActiveStatus'] == 1)
				{
					$ReportHeaderText .= ' Status: Active,';
				}
				else if ($Clean['ActiveStatus'] == 2)
				{
					$ReportHeaderText .= ' Status: In-Active,';
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
										<div class="col-lg-6">
											<?php
											if ($TotalPages > 1)
											{
												$AllParameters = array('Process' => '7', 'RouteID' => $Clean['RouteID'], 'AreaWiseFeeID' => $Clean['AreaWiseFeeID'], 'ClassSectionID' => $Clean['ClassSectionID'], 'StudentID' => $Clean['StudentID'], 'ActiveStatus' => $Clean['ActiveStatus']);
												echo UIHelpers::GetPager('student_vehicle_report.php', $TotalPages, $Clean['CurrentPage'], $AllParameters);
											}
											?>                                        
										</div>
										<div class="col-lg-6">
											<div class="print-btn-container"><button id="PrintButton" type="submit" class="btn btn-primary">Print</button></div>
										</div>
									</div>
									<div class="row" id="RecordTableHeading">
										<div class="col-lg-12">
											<div class="report-heading-container"><strong>Student Vehicle Report on <?php echo date('d-m-Y h:i A') . $ReportHeaderText; ?></strong></div>
										</div>
									</div>
									<div class="row" id="RecordTable">
										<div class="col-lg-12">
											<table width="100%" class="table table-striped table-bordered table-hover" id="DataTableRecords">
												<thead>
													<tr>
														<th>S.No</th>
														<th>Route Number</th>
														<th>Route Name</th>
														<th>Area Name</th>
														<th>Student Name</th>
														<th>Class</th>
														<th>Fee</th>
														<th>Is Active</th>
														<th>Create User</th>
														<th>Create Date</th>
														<th class="print-hidden">Operations</th>
													</tr>
												</thead>
												<tbody>
													<?php
													if (is_array($StudentVehicleList) && count($StudentVehicleList) > 0)
													{
														$Counter = $Start;
														foreach ($StudentVehicleList as $StudentVehicleID => $StudentVehicleDetails)
														{
															?>
															<tr>
																<td><?php echo ++$Counter; ?></td>
																<td><?php echo $StudentVehicleDetails['RouteNumber']; ?></td>
																<td><?php echo $StudentVehicleDetails['RouteName']; ?></td>
																<td><?php echo $StudentVehicleDetails['AreaName']; ?></td>
																<td><?php echo $StudentVehicleDetails['StudentName']; ?></td>
																<td><?php echo $StudentVehicleDetails['Class']; ?></td>
																<td><?php echo $StudentVehicleDetails['Amount']; ?></td>
																<td><?php echo (($StudentVehicleDetails['IsActive']) ? 'Yes' : 'No'); ?></td>
																<td><?php echo $StudentVehicleDetails['CreateUserName']; ?></td>
																<td><?php echo date('d/m/Y', strtotime($StudentVehicleDetails['CreateDate'])); ?></td>
																<td class="print-hidden">
																	<?php
																	// echo '<a href="edit_student_vehicle.php?Process=2&amp;StudentVehicleID=' . $StudentVehicleID . '">Edit</a>';
																	// echo '&nbsp;|&nbsp;';
																	// echo '<a href="student_vehicle_report.php?Process=5&amp;StudentVehicleID=' . $StudentVehicleID . '" class="delete-record">Delete</a>'; 

																	if ($LoggedUser->HasPermissionForTask(TASK_EDIT_STUDENT_VEHICLE) === true)
																	{
																		echo '<a href="edit_student_vehicle.php?Process=2&amp;StudentVehicleID=' . $StudentVehicleID . '">Edit</a>';
																	}
																	else
																	{
																		echo 'Edit';
																	}

																	echo '&nbsp;|&nbsp;';

																	if ($LoggedUser->HasPermissionForTask(TASK_DELETE_STUDENT_VEHICLE) === true)
																	{
																		echo '<a href="student_vehicle_report.php?Process=5&amp;StudentVehicleID=' . $StudentVehicleID . '" class="delete-record">Delete</a>';
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
													else
													{
														?>
														<tr>
															<td colspan="8">No Records</td>
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
	?>
	<!-- DataTables JavaScript -->
	<script src="../vendor/datatables/js/jquery.dataTables.min.js"></script>
	<script src="../vendor/datatables-plugins/dataTables.bootstrap.min.js"></script>
	<script src="../vendor/datatables-responsive/dataTables.responsive.js"></script>   
	<script type="text/javascript">
		$ (document).ready (function ()
		{
			$ ("body").on ('click', '.delete-record', function ()
			{
				if (! confirm ("Are you sure you want to delete this record?"))
				{
					return false;
				}
			});

			$ ('#DataTableRecords').DataTable ({
				responsive: true,
				bPaginate: false,
				bSort: false,
				searching: false,
				info: false
			});

			$ ('#AcademicYearID').change (function ()
			{
				$ ('#ClassSection').html ('<option value="0">-- All Section --</option>');
				$ ('#Student').html ('<option value="0">-- All Student --</option>');

				$.post ("/xhttp_calls/get_class.php", {}, function ( data )
				{
					ResultArray = data.split ("|*****|");

					if (ResultArray[0] == 'error')
					{
						alert (ResultArray[1]);
						return false;
					}
					else
					{
						$ ('#Class').html ('<option value="0">-- All Class --</option>' + ResultArray[1]);
					}
				});
			});

			$ ('#Class').change (function ()
			{

				var ClassID = parseInt ($ (this).val ());
				$ ('#Student').html ('<option value="0">-- All Student --</option>');

				if (ClassID <= 0)
				{
					$ ('#ClassSection').html ('<option value="0">-- All Section --</option>');
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
						$ ('#ClassSection').html ('<option value="0">-- All Section --</option>' + ResultArray[1]);
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
					$ ('#Student').html ('<option value="0">-- All Student --</option>');
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
						$ ('#Student').html ('<option value="0">-- All Student --</option>' + ResultArray[1]);
					}
				});
			});

			$ ('#Route').change (function ()
			{
				var RouteID = parseInt ($ (this).val ());
				if (RouteID <= 0)
				{
					$ ('#Areas').html ('<option value="0">-- Select Area --</option>');
					return;
				}

				$.post ("/xhttp_calls/get_areas_by_routes.php", {SelectedRouteID: RouteID}, function ( data )
				{
					ResultArray = data.split ("|*****|");

					if (ResultArray[0] == 'error')
					{
						alert (ResultArray[1]);
						$ ('#Areas').html ('<option value="0">-- Select Area --</option>');
						return false;
					}
					else
					{
						$ ('#Areas').html (ResultArray[1]);
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
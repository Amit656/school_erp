<?php
ob_start();
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once('../../classes/school_administration/class.subject_master.php');
require_once("../../classes/school_administration/class.classes.php");

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

if ($LoggedUser->HasPermissionForTask(TASK_ADD_CLASS_SUBJECT) !== true)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}

$AllClasses = array();
$AllClasses = AddedClass::GetActiveClasses();

$SubjectMarksTypeList = array('Number' => 'Number System', 'Grade' => 'Grade System');

$AllMasterSubjects = array();
$AllMasterSubjects = SubjectMaster::GetActiveSubjectMasters();

$HasErrors = false;

$Clean = array();
$Clean['Process'] = 0;

$Clean['ClassID'] = 0;
$Clean['AssignedSubjects'] = array();
$Clean['SubjectMarksType'] = array();

if (isset($_POST['hdnProcess']))
{
	$Clean['Process'] = (int) $_POST['hdnProcess'];
}
elseif(isset($_GET['Process']))
{
	$Clean['Process'] = (int) $_GET['Process'];
}
switch ($Clean['Process'])
{
	case 1:		
		if (isset($_POST['hdnClassID']))
		{
			$Clean['ClassID'] = (int) $_POST['hdnClassID'];
		}

		if (isset($_POST['chkAssignedSubjects']))
		{
			$Clean['AssignedSubjects'] = $_POST['chkAssignedSubjects'];
		}

		if (isset($_POST['rdbMarksType']))
		{
			$Clean['SubjectMarksType'] = $_POST['rdbMarksType'];
		}

		$NewRecordValidator = new Validator();

		$NewRecordValidator->ValidateInSelect($Clean['ClassID'], $AllClasses , 'Unknown Error, Please Try Again.');

		$SubjectMarksType = '';
		$AssignedSubjectsToBeSave = array();

		$IsError = 0;

		if(count($Clean['AssignedSubjects']) > 0)
		{
			foreach ($Clean['AssignedSubjects'] as $SubjectID => $Value) 
			{	
				if (isset($Clean['SubjectMarksType'][$SubjectID])) 
				{
					$SubjectMarksType = $Clean['SubjectMarksType'][$SubjectID];
				}

				if (!$NewRecordValidator->ValidateInSelect($SubjectMarksType, $SubjectMarksTypeList , 'Unknown Error, Please Try Again.')) 
				{
					$IsError = 1;
				}

				if (!$NewRecordValidator->ValidateInSelect($SubjectID, $AllMasterSubjects , 'Unknown Error, Please Try Again.')) 
				{
					$IsError = 1;
				}	

				$AssignedSubjectsToBeSave[$SubjectID] = $SubjectMarksType;
			}
		}

		if ($IsError)
		{
			$NewRecordValidator->AttachTextError('Unknown Error, Please Try Again.');
		}

		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}

		$SelectedAddedClass = new AddedClass($Clean['ClassID']);
		$SelectedAddedClass->FillAssignedSubjects();
		
		$ClassSubjectToBeRemoved = array();
		$ClassSubjectToBeRemoved = array_diff_key($SelectedAddedClass->GetAssignedSubjects(), $Clean['AssignedSubjects']);

		if (count($ClassSubjectToBeRemoved) > 0)
		{
			if($SelectedAddedClass->CheckClassSubjectsDependencies($ClassSubjectToBeRemoved))
			{
				$NewRecordValidator->AttachTextError('The Subjects for this class cannot be updated. There are dependent records for the Subjects that have been unselected.');
			}

			if ($NewRecordValidator->HasNotifications())
			{
				$HasErrors = true;
				break;
			}
		}
		
		$SelectedAddedClass->SetAssignedSubjects($AssignedSubjectsToBeSave);
		$SelectedAddedClass->SetCreateUserID($LoggedUser->GetUserID());

		if (!$SelectedAddedClass->SaveAssignedSubjects())
		{
			$NewRecordValidator->AttachTextError(ProcessErrors($SelectedAddedClass->GetLastErrorCode()));
			$HasErrors = true;
			break;
		}
		
		header('location:add_class_subjects.php?Mode=UD&Process=7&ClassID='.$Clean['ClassID']);
		exit;
	break;

	case 7:
		if (isset($_POST['drdClassID']))
		{
			$Clean['ClassID'] = (int) $_POST['drdClassID'];
		}
		elseif(isset($_GET['ClassID']))
		{
			$Clean['ClassID'] = (int) $_GET['ClassID'];
		}

		$NewRecordValidator = new Validator();

		$NewRecordValidator->ValidateInSelect($Clean['ClassID'], $AllClasses, 'Unknown Error, Please Try Again.');

		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}

		$SelectedAddedClass = New AddedClass($Clean['ClassID']);
		$SelectedAddedClass->FillAssignedSubjects();

		$Clean['AssignedSubjects'] = $SelectedAddedClass->GetAssignedSubjects();

		$SelectedAddedClass->FillAssignedSubjectsMarksType();

		$Clean['SubjectMarksType'] = $SelectedAddedClass->GetAssignedSubjectsMarksType();
		break;		
}

require_once('../html_header.php');
?>
<title>Set Class Subjects</title>
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
                    <h1 class="page-header">Set Class Subjects</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="SetClassSubjects" action="add_class_subjects.php" method="post">
            	<div class="panel panel-default">
                    <div class="panel-heading">
                       Add Class Subjects
                    </div>
                    <div class="panel-body">
<?php
						if ($HasErrors == true)
						{
							echo $NewRecordValidator->DisplayErrors();
						}
?>                    
                    	<div class="form-group">
                            <label for="ClassList" class="col-lg-2 control-label">Class</label>
                            <div class="col-lg-8">
                            	<select class="form-control"  name="drdClassID">
                            		<option  value="0" >Select Class</option>
<?php
									foreach ($AllClasses as $ClassID => $ClassName)
									{
?>
										<option <?php echo ($ClassID == $Clean['ClassID'] ? 'selected="selected"' : ''); ?> value="<?php echo $ClassID; ?>"><?php echo $ClassName;?></option>
<?php
									}

?>
                            	</select>
                            </div>
                        </div> 
                        <div class="form-group">
						    <div class="col-sm-offset-2 col-lg-10">
						    	<input type="hidden" name="hdnProcess" value="7"/>
						        <button type="submit" class="btn btn-primary">View Subjects</button>
						    </div>
						</div>
                    </div>
                </div>
            </form>
<?php
			if($Clean['Process'] == 7 && $HasErrors == false)
			{	
?>
			 <form class="form-horizontal" name="AddClassSubject" action="add_class_subjects.php" method="post">
            	<div class="panel panel-default">
                    <div class="panel-heading">
         				Subjects List
                    </div>
                    <div class="panel-body">
<?php
						$Counter = 1;
?>
							<div class="col-lg-12">
                                <table width="100%" class="table table-striped table-bordered table-hover">
                                    <thead>
                                       <tr>
	                                        <th>S. No</th>
                                            <th>Subject Name</th>
                                            <th>Assigned Subjects</th>	
                                            <th>Subject Marks Type</th>	
                                        </tr>
                                    </thead>
                                    <tbody>
<?php
										$SubjectMarksType = 'Number';

										foreach ($AllMasterSubjects as $SubjectID => $SubjectName) 
										{
											if (isset($Clean['SubjectMarksType'][$SubjectID])) 
											{
												$SubjectMarksType = $Clean['SubjectMarksType'][$SubjectID];
											}
?>
										  	<tr>
											  <td><?php echo $Counter++;?></td>
											  <td><?php echo $SubjectName; ?></td>
											  <td><?php echo'<input type="checkbox" class="AssignedSubjects"' . (in_array($SubjectID, array_column($Clean['AssignedSubjects'], 'SubjectID')) ? 'checked="checked"' : '') . ' name="chkAssignedSubjects[' . $SubjectID . ']" value="' . $SubjectID . '"/>' ?></td>
											  <td>											  	
<?php
												foreach ($SubjectMarksTypeList as $MarksType => $MarksTypeName) 
												{
?>
													<label class="radio-inline"><input type="radio" <?php echo ($SubjectMarksType == $MarksType) ? 'checked="checked"' : ''; ?> name="rdbMarksType[<?php echo $SubjectID;?>]" value="<?php echo $MarksType;?>"><?php echo $MarksTypeName;?></label>	
<?php
												}
?>
											  </td>
										  	</tr>
<?php
										$SubjectMarksType = 'Number';
										}
?>
									</tbody>
								</table>
								<div class="form-group">
								    <div class="col-sm-offset-2 col-lg-10">
								    	<input type="hidden" name="hdnClassID" value="<?php echo $Clean['ClassID']; ?>" />
								    	<input type="hidden" name="hdnProcess" value="1" />
								        <button type="submit" class="btn btn-primary">Save</button>
								    </div>
								</div>
<?php								
}
?>					</div>
				</div>
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
?>
</body>
</html>
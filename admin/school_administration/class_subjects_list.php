<?php
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

if ($LoggedUser->HasPermissionForTask(TASK_LIST_CLASS_SUBJECT) !== true)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}

$AllClasses = array();
$AllClasses = AddedClass::GetActiveClasses();

$AllClassSubjects = array();

$HasErrors = false;
$HasSearchErrors = false;

$Clean = array();
$Clean['Process'] = 0;

$Clean['ClassID'] = 0;
$Clean['SubjectPriority'] = array();

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
	case 3:	
		if (isset($_POST['hdnClassID']))
		{
			$Clean['ClassID'] = (int) $_POST['hdnClassID'];
		}

		if (isset($_POST['txtSubjectPriority']) && is_array($_POST['txtSubjectPriority']))
		{
			$Clean['SubjectPriority'] = $_POST['txtSubjectPriority'];
		}

		try
		{
		    $CurrentClass = new AddedClass($Clean['ClassID']);
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

		if (count($Clean['SubjectPriority']) <= 0) 
		{
			header('location:/admin/error.php');
		    exit;
		}

		$RecordValidator = new Validator();

		$AllClassSubjects = AddedClass::GetClassSubjects($Clean['ClassID'], false);

		$Counter = 1;
		$IsError = 0;

		foreach ($Clean['SubjectPriority'] as $ClassSubjectID => $Details) 
		{	
			if (!$RecordValidator->ValidateInSelect($ClassSubjectID, $AllClassSubjects, 'Unknown Error, Please Try Again.')) 
			{
				$IsError = 1;
			}

			$RecordValidator->ValidateNumeric($Details['Priority'], 'Priority should be numeric at row '.$Counter);
			
			$Counter++;
		}

		if ($IsError) 
		{
			$RecordValidator->AttachTextError('Unknown Error, Please Try Again.');
		}

		if ($RecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}

		$CurrentClass = new AddedClass($Clean['ClassID']);
		$CurrentClass->SetAssignedSubjects($Clean['SubjectPriority']);
		
		if (!$CurrentClass->SaveClassSubjectPriority())
		{
			$RecordValidator->AttachTextError(ProcessErrors($CurrentClass->GetLastErrorCode()));
			$HasErrors = true;
			break;
		}
		
		header('location:class_subjects_list.php?Mode=UD&Process=7&ClassID='.$Clean['ClassID']);
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

		$RecordValidator = new Validator();

		$RecordValidator->ValidateInSelect($Clean['ClassID'], $AllClasses, 'Unknown Error, Please Try Again.');

		if ($RecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}

		$AllClassSubjects = AddedClass::GetClassSubjects($Clean['ClassID'], false);
	break;		
}

require_once('../html_header.php');
?>
<title>Class Subjects List</title>
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
                    <h1 class="page-header">Class Subjects List</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="SetClassSubjectsList" action="class_subjects_list.php" method="post">
            	<div class="panel panel-default">
                    <div class="panel-heading">
                       Class Subjects List
                    </div>
                    <div class="panel-body">
<?php
						if ($HasSearchErrors == true)
						{
							echo $RecordValidator->DisplayErrors();
						}
?>                    
                    	<div class="form-group">
                            <label for="ClassList" class="col-lg-2 control-label">Class</label>
                            <div class="col-lg-4">
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
			if(($Clean['Process'] == 7 && $HasSearchErrors == false) || $Clean['Process'] == 3)
			{	
?>
			 <form class="form-horizontal" name="ClassSubjectList" action="class_subjects_list.php" method="post">
            	<div class="panel panel-default">
                    <div class="panel-heading">
         				Subjects List
                    </div>
                    <div class="panel-body">
        	<?php
						if ($HasErrors == true)
						{
							echo $RecordValidator->DisplayErrors();
						}
?>
                    	<div class="col-lg-12" style="text-align: right;">
	                		<div class="add-new-btn-container">
	                        	<a href="add_class_subjects.php?Process=7&ClassID=<?php echo $Clean['ClassID'];?>" class="btn btn-primary<?php echo $LoggedUser->HasPermissionForTask(TASK_ADD_CLASS_SUBJECT) === true ? '' : ' disabled'; ?>" role="button">Add/Edit Class Subject</a>
	                        </div>
	                	</div>
	                	<br/>
<?php
						$Counter = 1;
?>
							<div class="col-lg-12">
                                <table width="100%" class="table table-striped table-bordered table-hover">
                                    <thead>
                                       <tr>
	                                        <th>S. No</th>
                                            <th>Subject Name</th>
                                            <th style="text-align: center;">Priority</th>	
                                        </tr>
                                    </thead>
                                    <tbody>
<?php
									$Priority = 0;
									foreach ($AllClassSubjects as $ClassSubjectID => $Details) 
									{
										if (count($Clean['SubjectPriority']) > 0) 
										{
											if (isset($Clean['SubjectPriority'][$ClassSubjectID]['Priority'])) 
											{
												$Priority = $Clean['SubjectPriority'][$ClassSubjectID]['Priority'];
											}
										}
										else
										{
											if (isset($Details['Priority'])) 
											{
												$Priority = $Details['Priority'];
											}
										}
?>
									  	<tr>
										  <td><?php echo $Counter++;?></td>
										  <td><?php echo $Details['SubjectName']; ?></td>
										  <td style="text-align: center; width: 10%;"><input type="text" class="form-control" maxlength="3" style="width: 60px;" name="txtSubjectPriority[<?php echo $ClassSubjectID;?>][Priority]"value="<?php echo $Priority;?>"/></td>
									  	</tr>
<?php
									}
?>
									</tbody>
								</table>
								<div class="form-group">
									<div class="col-sm-offset-4 col-lg-10">
										<input type="hidden" name="hdnClassID" value="<?php echo $Clean['ClassID']; ?>" />
										<input type="hidden" name="hdnProcess" value="3" />
										<input type="submit" class="btn btn-success col-lg-4" name="btnUpdatePriority" value="Update Priority">
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
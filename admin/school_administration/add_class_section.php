<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once('../../classes/school_administration/class.section_master.php');
require_once("../../classes/school_administration/class.classes.php");

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

$UserMenusArray = array();
$UserMenusArray = $LoggedUser->GetUserMenus();

if (!is_array($UserMenusArray) || count($UserMenusArray) <= 0)
{
	//header('location:../logout.php');
	//exit;
}

$Clean = array();

$Clean['ClassID'] = 0;

if (isset($_GET['ClassID']))
{
    $Clean['ClassID'] = (int) $_GET['ClassID'];
}
elseif (isset($_POST['hdnClassID']))
{
    $Clean['ClassID'] = (int) $_POST['hdnClassID'];
}

if ($Clean['ClassID'] <= 0)
{
    header('location:../error.php');
    exit;
}

try
{
	$CurrentAddedClass = new AddedClass($Clean['ClassID']);
	$CurrentAddedClass->FillClassSections();
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

$AllSections = array();
$AllSections = SectionMaster::GetActiveSectionMasters();

$CurrentlyAssignedSections = array();
$CurrentlyAssignedSections = $CurrentAddedClass->GetClassSectionsList();

$HasErrors = false;
$PriorityErrors = array();

$Clean['Process'] = 0;

$Clean['AssignedSections'] = array();
$Clean['ClassSectionPriority'] = array();

if (isset($_POST['hdnProcess']))
{
	$Clean['Process'] = (int) $_POST['hdnProcess'];
}
switch ($Clean['Process'])
{
	case 1:		
		if (isset($_POST['chkAssignedSections']) && is_array($_POST['chkAssignedSections']))
		{
			$Clean['AssignedSections'] = $_POST['chkAssignedSections'];
		}

		if (isset($_POST['txtClassSectionPriority']) && is_array($_POST['txtClassSectionPriority']))
		{
			$Clean['ClassSectionPriority'] = $_POST['txtClassSectionPriority'];
		}

		$Counter = 1;

		$NewRecordValidator = new Validator();

		if(count($Clean['AssignedSections']) > 0)
		{
			foreach ($Clean['AssignedSections'] as $SectionID => $Value) 
			{
				$NewRecordValidator->ValidateInSelect($SectionID, $AllSections , 'Unknown Error, Please Try Again.');

				if (!isset($Clean['ClassSectionPriority'][$SectionID]))
				{
					header('location:../error.php');
					exit;
				}

				if(!($NewRecordValidator->ValidateInteger($Clean['ClassSectionPriority'][$SectionID], 'Priority is required And should be a integer at row = ' . $Counter, 0)))
				{
					$PriorityErrors[$SectionID] = $SectionID;
				}

				$Counter++;		
			}
		}

		if ($NewRecordValidator->HasNotifications())
        {
            $HasErrors = true;
            break;
        }

		$NewAssignedSections = array();

        if(count($Clean['AssignedSections']) > 0)
		{
			foreach ($Clean['AssignedSections'] as $SectionID => $Value) 
			{
				$NewAssignedSections[$SectionID] = $Clean['ClassSectionPriority'][$SectionID];
			}
		}

		$SectionsToBeRemoved = array();
		$SectionsToBeRemoved = array_diff_key($Clean['AssignedSections'], $CurrentlyAssignedSections);

		if(count($SectionsToBeRemoved) > 0)
		{
			if($CurrentAddedClass->CheckClassSectionsDependencies($SectionsToBeRemoved))
			{
				$NewRecordValidator->AttachTextError('The sections for this class cannot be updated. There are dependent records for the sections that have been unselected.');
			}

			if ($NewRecordValidator->HasNotifications())
			{
				$HasErrors = true;
				break;
			}
		}

		$CurrentAddedClass->SetClassSectionsList($NewAssignedSections);
		$CurrentAddedClass->SetCreateUserID($LoggedUser->GetUserID());

		if (!$CurrentAddedClass->SaveClassSections())
		{
			$NewRecordValidator->AttachTextError(ProcessErrors($CurrentAddedClass->GetLastErrorCode()));
			$HasErrors = true;
			break;
		}
		
		header('location:class_list.php?Mode=UD');
		exit;
	break;
}

require_once('../html_header.php');
?>
<title>Set Class Sections</title>
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
                    <h1 class="page-header">Set Class Sections</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="AddClassSections" action="add_class_section.php" method="post">
            	<div class="panel panel-default">
                    <div class="panel-heading">
                       Add Class Sections
                    </div>
                    <div class="panel-body">
<?php
						if ($HasErrors == true)
						{
							echo $NewRecordValidator->DisplayErrors();
						}
?>
						<div class="alert alert-info">
						  <strong>Class: </strong><?php echo $CurrentAddedClass->GetClassName(); ?>
						</div>                    
                    	<div class="row" id="RecordTable">
                            <div class="col-lg-12">
                                <table width="100%" class="table table-striped table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th>S. No</th>
                                            <th>Section Name</th>
                                            <th>Select Section</th>
                                            <th>Priority</th>
                                        </tr>
                                    </thead>
                                    <tbody>
<?php
										$Counter = 0;
										 
 										foreach ($AllSections as $SectionID => $SectionName)
										{
?>
										 <tr>
                                        	<td><?php echo ++$Counter; ?></td>
                                        	<td><?php echo $SectionName; ?></td>
                                        	<td><input type="checkbox" name="chkAssignedSections[<?php echo $SectionID; ?>]"  <?php echo(array_key_exists($SectionID, $CurrentlyAssignedSections)) ? 'checked="checked"' : '' ?> value="<?php echo $SectionID;?>"></td>
                                        	<td <?php echo(array_key_exists($SectionID, $PriorityErrors) ? 'class="has-error"' : '') ?>>
<?php
												if(array_key_exists($SectionID, $CurrentlyAssignedSections))
												{
?>		
													<input class="form-control" type="text" maxlength="3" id="ClassSectionPriority<?php echo $SectionID;?>" name="txtClassSectionPriority[<?php echo $SectionID;?>]" value="<?php echo (($CurrentlyAssignedSections[$SectionID]) != '' ? $CurrentlyAssignedSections[$SectionID] : ''); ?>">
<?php
												}
												else
												{
?>
													<input class="form-control" type="text" maxlength="3" id="ClassSectionPriority<?php echo $SectionID;?>" name="txtClassSectionPriority[<?php echo $SectionID;?>]" value="">
<?php
												}
?>                                              		
                                        	</td>
                                        </tr>

<?php											
										}
?>                                    	
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="form-group">
						    <div class="col-sm-offset-2 col-lg-10">
						    	<input type="hidden" name="hdnProcess" value="1"/>
						    	<input type="hidden" name="hdnClassID" value="<?php echo $Clean['ClassID'];?>"/>
						        <button type="submit" class="btn btn-primary">Assign Section</button>
						    </div>
						</div>
                    </div>
                </div>
            </form>
					</div>
				</div>
			</form>
        </div>
        <!-- /#page-wrapper -->

    </div>
    <!-- /#wrapper -->
<?php
require_once('../footer.php');
?>
</body>
</html>
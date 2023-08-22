<?php
require_once("../../classes/class.users.php");
require_once('../../classes/class.validation.php');
require_once("../../classes/class.authentication.php");

require_once("../../classes/school_administration/class.school_sessions.php");

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

if ($LoggedUser->HasPermissionForTask(TASK_LIST_SCHOOL_SESSION) !== true)
{
    header('location:/admin/unauthorized_login_admin.php');
    exit;
}

$HasErrors = false;
$RecordDeletedSuccessfully = false;

$AllSchoolSessions = array();
$AllSchoolSessions = SchoolSessions::GetAllSchoolSessions();

$ActivatedSession = array();
$ActivatedSession = SchoolSessions::GetActivatedSession();

$Counter = 1;

$Clean = array();
$ClassPriority = array();
$Clean['Process'] = 0;

$Clean['SchoolSessionID'] = 0;

if (isset($_POST['hdnProcess']))
{
    $Clean['Process'] = (int) $_POST['hdnProcess'];
}
elseif (isset($_GET['Process']))
{
	$Clean['Process'] = (int) $_GET['Process'];
}

switch ($Clean['Process'])
{
    case 3:
        if (isset($_POST['hdnSchoolSessionID']))
        {           
            $Clean['SchoolSessionID'] = (int) $_POST['hdnSchoolSessionID'];
        }
        if (isset($_POST['txtSchoolSessionPriority']))
        {           
            $SchoolSessionPriority = $_POST['txtSchoolSessionPriority'];
        }

        $RecordValidator = new Validator();

        foreach($SchoolSessionPriority as $SchoolSessionID => $Priority )
        { 
            
            if (!array_key_exists($SchoolSessionID, $AllSchoolSessions))
            {
                header('location:../error.php');
                exit;
            }

            $RecordValidator->ValidateInteger($Priority, 'Invalid Priority value in row: '.$Counter.'.', 1);
            $Counter++;

         }
        if ($RecordValidator->HasNotifications())
        {
            $HasErrors = true;
            break;
        }

        if (!SchoolSessions::UpdatePriorities($SchoolSessionPriority))
        {
            $RecordValidator->AttachTextError('There was an error in updating record.');
            $HasErrors = true;
            break;
        }
                
        header('location:school_sessions_list.php?Mode=UD');
        exit;
    break;

	case 5:
		/*if ($LoggedUser->HasPermissionForClass(Class_ADD_EDIT_Class) !== true)
		{
			header('location:unauthorized_login_admin.php');
			exit;
		}*/
		
		if (isset($_GET['SchoolSessionID']))
		{
			$Clean['SchoolSessionID'] = (int) $_GET['SchoolSessionID'];
		}
		
		if ($Clean['SchoolSessionID'] <= 0)
		{
			header('location:../error_page.php');
			exit;
		}						
			
		try
		{
			$SchoolSessionToDelete = new SchoolSessions($Clean['SchoolSessionID']);
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
		
		$RecordValidator = new Validator();
		
		if ($SchoolSessionToDelete->CheckDependencies())
		{
			$RecordValidator->AttachTextError('This School Session cannot be deleted. There are dependent records for this School Session.');
			$HasErrors = true;
			break;
		}
				
		if (!$SchoolSessionToDelete->Remove())
		{
			$RecordValidator->AttachTextError(ProcessErrors($SchoolSessionToDelete->GetLastErrorCode()));
			$HasErrors = true;
			break;
		}
		
		header('location:school_sessions_list.php?Mode=DD');
	break;
}

require_once('../html_header.php');
?>
<title>School Sessions</title>
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
                    <h1 class="page-header">School Sessions</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
            <!-- /.row -->
            <div class="row">
                <div class="col-lg-12">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <strong>Total Records Returned: <?php echo count($AllSchoolSessions); ?></strong>
                        </div>
                        <!-- /.panel-heading -->
                        <div class="panel-body">
                            <div>
                                <div class="row">
                                    <div class="col-lg-6">
                                    <div class="add-new-btn-container"><a href="add_school_sessions.php" class="btn btn-primary" role="button">Add New School Sessions</a></div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="print-btn-container"><button id="PrintButton" type="submit" class="btn btn-primary">Print</button></div>
                                    </div>
                                </div>
<?php
                            if ($HasErrors == true)
                            {
                                echo $RecordValidator->DisplayErrorsInTable();
                            }
                            elseif ($RecordDeletedSuccessfully == true)
                            {
                                echo '<div class="alert alert-success alert-top-margin">The record was deleted successfully.</div>';
                            }
?>
                                <div class="row" >
                                    <div class="col-lg-12" id="UpdateMessage">
                                        
                                    </div>
                                </div>
                                <div class="row" id="RecordTableHeading">
                                    <div class="col-lg-12">
                                    	<div class="report-heading-container"><strong>All SchoolSessions on <?php echo date('d-m-Y h:i A'); ?></strong></div>
                                    </div>
								</div>
                               <form action="school_sessions_list.php" method="post">
                                    <div class="row" id="RecordTable">
                                        <div class="col-lg-12">
                                            <table width="100%" class="table table-striped table-bordered table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>S. No</th>
                                                        <th>Session Name</th>
                                                        <th>Session Desciption</th>
                                                        <th class="print-hidden">Priority</th>
                                                        <th>Activate/Deactivate</th>
                                                        <th>Create User</th>
                                                        <th>Create Date</th>
                                                        <th class="print-hidden">Opreration</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
        <?php
                                        if (is_array($AllSchoolSessions) && count($AllSchoolSessions) > 0)
                                        {
                                            $Counter = 0;
                                            foreach ($AllSchoolSessions as $SchoolSessionID => $AllSchoolSessionsDetails)
                                            {
        ?>
                                                    <tr>
                                                        <td><?php echo ++$Counter; ?></td>
                                                        <td><?php echo $AllSchoolSessionsDetails['SessionName']; ?></td>
                                                        <td><?php echo $AllSchoolSessionsDetails['SessionDesciption']; ?></td>
                                                        <td><input type="text" class="form-control print-hidden" name="txtSchoolSessionPriority[<?php echo $SchoolSessionID; ?>]" style="width: 50px;" value="<?php echo $AllSchoolSessionsDetails['Priority']; ?>"></td>
                                                        <td>
                                                            <input type="radio" name="rdoIsActivated" class="custom-radio IsActivated print-hidden" id="IsActivated" value="<?php echo $SchoolSessionID; ?>" <?php echo (array_key_exists($SchoolSessionID, $ActivatedSession)) ? 'checked="checked"' : ''; ?>>
                                                            <label for="rdoIsActivated" class="control-label" style="font-weight: normal;"><?php echo(array_key_exists($SchoolSessionID, $ActivatedSession)) ? 'Yes' : 'No';?></label>
                                                            
                                                        </td>
                                                        <td><?php echo $AllSchoolSessionsDetails['CreateUserName']; ?></td>
                                                        <td><?php echo date('d/m/Y',strtotime($AllSchoolSessionsDetails['CreateDate'])); ?></td>
                                                        <td class="print-hidden"> <a href='edit_school_sessions.php?Process=2&SchoolSessionID=<?php echo $SchoolSessionID;?>'>Edit</a> | <a href='school_sessions_list.php?Process=5&amp;SchoolSessionID=<?php echo $SchoolSessionID?>'>Delete</a></td>
                                                    </tr>
        <?php
                                            }
        ?>                                      
                                                    <tr class="print-hidden"><td colspan="8" style="text-align:right;">
                                                    <input type="hidden" value="3" name="hdnProcess" />
                                                    <input type="hidden" value="'.$Clean['SchoolSessionID'].'" name="hdnSchoolSessionID" />
                                                    <button type="submit" class="btn btn-success">Update</button>
                                                    </td></tr>

        <?php
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
                                </form>
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
	$(document).ready(function() 
    {
		$(".delete-class").click(function()
        {	
            if (!confirm("Are you sure you want to delete this School Session ?"))
            {
                return false;
            }
        });

        $(".IsActivated").click(function()
        {
             if (!confirm("Are you sure you want to update this School Session ?"))
            {
                return false;
            }

            var ActivateSessionID = $(this).val();
            $('#UpdateMessage').load('../../xhttp_calls/activate_session.php?ActivateSessionID='+ActivateSessionID);

        });
	});
</script>
<!-- JavaScript To Print A Report -->
<script src="/admin/js/print-report.js"></script>
</body>
</html>
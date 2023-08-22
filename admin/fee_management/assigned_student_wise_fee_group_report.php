<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once("../../classes/fee_management/class.fee_groups.php");

require_once("../../classes/school_administration/class.classes.php");
require_once('../../classes/school_administration/class.class_sections.php');
require_once('../../classes/school_administration/class.section_master.php');

require_once('../../classes/school_administration/class.students.php');
require_once('../../classes/school_administration/class.student_details.php');

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

if ($LoggedUser->HasPermissionForTask(TASK_VIEW_ASSIGNED_STUDENRS_TO_GROUP) !== true)
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

$FeeGrouplist = array();
$FeeGrouplist = FeeGroup::GetActiveFeeGroups('Student');

$AllRecords = array();

$HasErrors = false;
$RecordDeletedSuccessfully = false;

$Clean = array();

$Clean['Process'] = 0;

$Clean['FeeGroupID'] = 0;

if (isset($_GET['FeeGroupID']))
{
    $Clean['FeeGroupID'] = (int) $_GET['FeeGroupID'];
}

if ($Clean['FeeGroupID'] <= 0)
{
    header('location:/admin/error.php');
    exit;
}

try
{
    $FeeGroupToEdit = new FeeGroup($Clean['FeeGroupID']);
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

$AllRecords = FeeGroup::FeeGroupTypeWiseReports($Clean['FeeGroupID']);

if (isset($_GET['Process']))
{
	$Clean['Process'] = (int) $_GET['Process'];
}

switch ($Clean['Process'])
{
	case 5:
        if (isset($_GET['RecordID'])) 
        {
            $Clean['RecordID'] = (int) $_GET['RecordID'];
        }

        if ($Clean['RecordID'] <= 0)
        {
            header('location:/admin/error.php');
            exit;
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
<title>Assigned Fee Group Report</title>
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
                    <h1 class="page-header">Student Wise Fee Group Report</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
            <!-- /.row -->
            <div class="row">
                <div class="col-lg-12">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <strong>Total Records Returned: <?php echo count($AllRecords); ?></strong>
                        </div>
                        <!-- /.panel-heading -->
                        <div class="panel-body">
                            <div>
                                <div class="row">
                                    <div class="col-lg-4">
                                    <div class="add-new-btn-container"><a href="assigned_student_wise_fee_group.php?FeeGroupID=<?php echo $Clean['FeeGroupID'] ;?>" class="btn btn-primary<?php //echo $LoggedUser->HasPermissionForTask(TASK_ADD_MENU) === true ? '' : ' disabled'; ?>" role="button">Assign Fee Group</a></div>
                                    </div>
                                    <div class="col-lg-4">
                                        <strong>Details of Group : 
                                        <?php echo((array_key_exists($Clean['FeeGroupID'], $FeeGrouplist)) ? $FeeGrouplist[$Clean['FeeGroupID']] : '') ;?>
                                        </strong>
                                    </div>
                                    <div class="col-lg-4">
                                        <div class="print-btn-container"><button id="PrintButton" type="submit" class="btn btn-primary">Print</button></div>
                                    </div>
                                </div>
<?php
                            if ($HasErrors == true)
                            {
                                echo $RecordValidator->DisplayErrorsInTable();
                            }
                            else if ($LandingPageMode == 'AS')
                            {
                                echo '<div class="alert alert-success alert-top-margin">Record saved successfully.</div>';
                            }
                            else if ($RecordDeletedSuccessfully == true)
                            {
                                echo '<div class="alert alert-danger alert-top-margin">Record deleted successfully.</div>';
                            }
                            else if ($LandingPageMode == 'UD')
                            {
                                echo '<div class="alert alert-success alert-top-margin">Record updated successfully.</div>';
                            }
?>
                                
                            </div>
                            <!-- /.table-responsive -->
                        </div>
                        <!-- /.panel-body -->
<?php
                        if (count($AllRecords) > 0 && is_array($AllRecords)) 
                        {
                            foreach ($AllRecords as $ClassID => $StudentDetails) 
                            {
?>
                            <div class="panel panel-default">
                                <div class="panel-heading">
                                    <strong>Studnet of class : <?php echo $ClassID;?></strong>
                                </div>
                                <div class="panel-body">
                                    <div class="row">
                                        <div class="row" id="RecordTable">
                                            <div class="col-lg-12">
                                                <table width="100%" class="table table-striped table-bordered table-hover">
                                                    <thead>
                                                        <tr>
                                                            <th>S. No</th>
                                                            <th>Sttudent Name</th>
                                                            <th>Roll No</th>
                                                            <th>Class</th>
                                                            <th>Assigned by</th>
                                                            <th>Assigned Date</th>
                                                            <th class="print-hidden">Operations</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
<?php
                                            if (is_array($StudentDetails) && count($StudentDetails) > 0)
                                            {
                                                $Counter = 0;
                                                foreach ($StudentDetails as $StudentID => $Details)
                                                {
?>
                                                    <tr>
                                                        <td><?php echo ++$Counter; ?></td>
                                                        <td><?php echo $Details['FirstName'] . ' ' . $Details['LastName']; ?></td>
                                                        <td><?php echo $Details['RollNumber']; ?></td>
                                                        <td><?php echo $Details['ClassName'] . ' ' . $Details['SectionName']; ?></td>
                                                        <td><?php echo $Details['CreateUserName']; ?></td>
                                                        <td><?php echo date('d/m/Y', strtotime($Details['CreateDate'])); ?></td>
                                                        <td class="print-hidden">
                                                            <?php echo '<a href="../school_administration/edit_student.php?Process=2&amp;StudentID=' . $StudentID . '">More Details</a>';?>
                                                        </td>
                                                    </tr>
<?php
                                                }
                                            }
                                            else
                                            {
?>
                                                <tr>
                                                    <td colspan="7">No Records</td>
                                                </tr>
<?php
                                            }
?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
<?php                           
                            } 
                        }
?>
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
	
<script type="text/javascript">
$(document).ready(function() {
    $(".delete-record").click(function()
    {	
        if (!confirm("Are you sure you want to delete this Fee Group?"))
        {
            return false;
        }
    });
});
</script>
<!-- JavaScript To Print A Report -->
<script src="js/print-report.js"></script>
</body>
</html>
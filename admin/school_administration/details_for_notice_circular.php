<?php
require_once("../../classes/class.users.php");
require_once('../../classes/class.validation.php');
require_once("../../classes/class.authentication.php");

require_once("../../classes/school_administration/class.notices_circulars.php");
require_once("../../classes/school_administration/class.branch_staff.php");
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

$HasErrors = false;
$RecordDeletedSuccessfully = false;

$ClassList =  array();
$ClassList = AddedClass::GetActiveClasses();

$StaffList = array();
$StaffList = BranchStaff::SearchBranchStaff();

$Clean = array();
$Clean['Process'] = 0;

$Clean['ClassIDList'] = array();
$Clean['StaffIDList'] = array();

$Clean['NoticeCircularID'] = 0;

if (isset($_GET['Process']))
{
	$Clean['Process'] = (int) $_GET['Process'];
}
switch ($Clean['Process'])
{
	case 7:
		/*if ($LoggedUser->HasPermissionForTask(TASK_ADD_EDIT_TASK) !== true)
		{
			header('location:unauthorized_login_admin.php');
			exit;
		}*/
		
		if (isset($_GET['NoticeCircularID']))
		{
			$Clean['NoticeCircularID'] = (int) $_GET['NoticeCircularID'];
		}
		
		if ($Clean['NoticeCircularID'] <= 0)
		{
			header('location:../error_page.php');
			exit;
		}						
        
	break;
}

$NoticeCircularDetails = array();
$NoticeCircularDetails = new NoticeCircular($Clean['NoticeCircularID']);

$Clean['NoticeCircularDate'] = date('d/m/Y', strtotime($NoticeCircularDetails->GetNoticeCircularDate()));
$Clean['NoticeCircularSubject'] = $NoticeCircularDetails->GetNoticeCircularSubject();
$Clean['NoticeCircularDetails'] = $NoticeCircularDetails->GetNoticeCircularDetails();

$NoticeCircularDetails->FillNoticeCircularApplicableFor();
$NoticeCircularApplicableFor = $NoticeCircularDetails->GetNoticeCircularApplicableFor();

foreach ($NoticeCircularApplicableFor as $Key => $NoticeCircularApplicableForDetails) 
{
    if ($NoticeCircularApplicableForDetails['ApplicableFor'] == 'Staff') 
    {
        $Clean['StaffIDList'][] = $NoticeCircularApplicableForDetails['StaffOrClassID'];
    }
    else if ($NoticeCircularApplicableForDetails['ApplicableFor'] == 'Class') 
    {
       $Clean['ClassIDList'][] = $NoticeCircularApplicableForDetails['StaffOrClassID'];
    }
}

require_once('../html_header.php');
?>
<title>Notice/Circular Details</title>
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
                    <h1 class="page-header">Notice/Circular Details</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
            <!-- /.row -->
            <div class="row">
                <div class="col-lg-12">
                    <div class="">
                        
                        <!-- /.panel-heading -->
                        <div class="">
                            <div>
                                
<?php
                            if ($HasErrors == true)
                            {
                                echo $RecordValidator->DisplayErrorsInTable();
                            }
                            else if ($RecordDeletedSuccessfully == true)
                            {
                                echo '<div class="alert alert-success alert-top-margin">The record was deleted successfully.</div>';
                            }
?>
                                <div class="row">
                                    <div class="col-lg-12">
                                        <div class="panel panel-default">
                                            <div class="panel-heading">
                                                <strong>Notice/Circular Basic Details</strong>
                                            </div>
                                            <!-- /.panel-heading -->
                                            <div class="panel-body">
                                                <div>
                                                    <div class="row">
<?php
                                                     echo'<div class="col-lg-6"><b>Notice Subject: </b>' . $Clean['NoticeCircularSubject'] . '</div>';                                                                
                                                     echo'<div class="col-lg-6"><b>Notice Date: </b>' . $Clean['NoticeCircularDate'] . '</div><br><br>';                                                                   
                                                     echo'<div class="col-lg-10"><b>Notice Detail: </b>' . $Clean['NoticeCircularDetails'] . '</div>';                                                                   
?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row" id="RecordTable">
                                    <div class="col-lg-12">
                                        <div class="panel panel-default">
                                            <div class="panel-heading">
                                                <strong>Applicable Details For Classes</strong>
                                            </div>
                                            <!-- /.panel-heading -->
                                            <div class="panel-body">
                                                <table width="100%" class="table table-striped table-bordered table-hover">
                                                    <thead>
                                                        <tr>
                                                            <th>S. No</th>
                                                            <th>Class Name</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
<?php
                                        if (is_array($Clean['ClassIDList']) && count($Clean['ClassIDList']) > 0)
                                        {
                                            $Counter = 0;
                                            foreach ($Clean['ClassIDList'] as $ClassID)
                                            {
?>
                                                <tr>
                                                    <td><?php echo ++$Counter; ?></td>
                                                    <td><?php echo (array_key_exists($ClassID, $ClassList) ? $ClassList[$ClassID] : ''); ?></td>
                                                </tr>   
<?php
                                                
                                            }
                                        }
                                        else
                                        {
?>
                                                <tr>
                                                    <td colspan="2">No Records</td>
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
                                <div class="row" id="RecordTable">
                                    <div class="col-lg-12">
                                        <div class="panel panel-default">
                                            <div class="panel-heading">
                                                <strong>Applicable Details For Staff</strong>
                                            </div>
                                            <!-- /.panel-heading -->
                                            <div class="panel-body">
                                                <table width="100%" class="table table-striped table-bordered table-hover">
                                                    <thead>
                                                        <tr>
                                                            <th>S. No</th>
                                                            <th>Staff Name</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
            <?php
                                        if (is_array($Clean['StaffIDList']) && count($Clean['StaffIDList']) > 0)
                                        {
                                            $Counter = 0;
                                            foreach ($Clean['StaffIDList'] as $StaffID)
                                            {
                                                
?>
                                                <tr>
                                                    <td><?php echo ++$Counter; ?></td>
                                                    <td><?php echo (array_key_exists($StaffID, $StaffList) ? $StaffList[$StaffID]['FirstName'] . ' ' . $StaffList[$StaffID]['LastName'] : ''); ?></td>
                                                </tr>   
<?php
                                            }
                                        }
                                        else
                                        {
?>
                                                <tr>
                                                    <td colspan="2">No Records</td>
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
?>
</body>
</html>
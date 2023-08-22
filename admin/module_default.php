<?php
require_once("../classes/class.users.php");
require_once("../classes/class.authentication.php");

require_once("../includes/global_defaults.inc.php");

require_once("../classes/class.helpers.php");

//1. RECHECK IF THE USER IS VALID //
try
{
	$AuthObject = new ApplicationAuthentication;
	$LoggedUser = new User(0, $AuthObject->CheckValidUser());
}

// THIS CATCH BLOCK BUBBLES THE EXCEPTION TO THE BUILT IN 'Exception' CLASS IF THERE ARE ANY UNCAUGHT ERRORS //
catch (ApplicationAuthException $e)
{
	header('location:unauthorized_login_admin.php');
	exit;
}
catch (Exception $e)
{
	header('location:unauthorized_login_admin.php');
	exit;
}
// END OF 1. //

$Clean = array();

$Clean['CurrentModuleID'] = 0;
$Clean['CurrentModuleName'] = '';

if ($_GET['CurrentModuleID'])
{
    $Clean['CurrentModuleID'] = (int) $_GET['CurrentModuleID'];
}

if ($Clean['CurrentModuleID'] <= 0)
{
    header('location:/admin/error.php');
    exit;
}

$ModuleList = array();
$ModuleList = Helpers::GetApplicableModules($LoggedUser->GetUserID());

$Summary = array();
$Summary = Helpers::GetSchoolSummary();

if (!array_key_exists($Clean['CurrentModuleID'], $ModuleList))
{
    header('location:/admin/error.php');
    exit;
}

$Clean['CurrentModuleName'] = $ModuleList[$Clean['CurrentModuleID']];
$_SESSION['CurrentModuleID'] = $Clean['CurrentModuleID'];

require_once('html_header.php');
?>
<title>Added: <?php echo $Clean['CurrentModuleName']; ?></title>
</head>

<body>

    <div id="wrapper">
    	<!-- Navigation -->
        <nav class="navbar navbar-default navbar-static-top" role="navigation" style="margin-bottom: 0">
<?php 
			require_once('site_header.php');
			require_once('left_navigation_menu.php');
?>                    
            <!-- /.navbar-static-side -->
        </nav>

        <div id="page-wrapper">
            <div class="row">
                <div class="col-lg-12">
                    <h1 class="page-header">Dashboard: <?php echo $Clean['CurrentModuleName']; ?></h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
            <!-- /.row -->
            <div class="row">
                <div class="col-lg-4 col-md-6">
                    <div class="panel panel-primary">
                        <div class="panel-heading">
                            <div class="tile-header text-center">Students Strength</div>
                            <div class="row">
                                <div class="col-xs-3">
                                    <i class="fa fa-users fa-5x"></i>
                                </div>
                                <div class="col-xs-9 text-right">
                                    <div class="huge"><?php echo (isset($Summary['TotalStudents']) ? $Summary['TotalStudents'] : 0); ?></div>
                                    <div>Students and still counting...</div>
                                </div>
                            </div>
                        </div>
                        <a href="/admin/school_administration/students_list.php">
                            <div class="panel-footer">
                                <span class="pull-left">View Details</span>
                                <span class="pull-right"><i class="fa fa-arrow-circle-right"></i></span>
                                <div class="clearfix"></div>
                            </div>
                        </a>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="panel panel-green">
                        <div class="panel-heading">
                            <div class="tile-header text-center">Faculties Strength</div>
                            <div class="row">
                                <div class="col-xs-3">
                                    <i class="fa fa-user fa-5x"></i>
                                </div>
                                <div class="col-xs-9 text-right">
                                    <div class="huge"><?php echo (isset($Summary['TotalFaculty']) ? $Summary['TotalFaculty'] : 0); ?></div>
                                    <div>Faculties Working!</div>
                                </div>
                            </div>
                        </div>
                        <a href="/admin/school_administration/branch_staff_list.php">
                            <div class="panel-footer">
                                <span class="pull-left">View Details</span>
                                <span class="pull-right"><i class="fa fa-arrow-circle-right"></i></span>
                                <div class="clearfix"></div>
                            </div>
                        </a>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="panel panel-yellow">
                        <div class="panel-heading">
                            <div class="tile-header text-center">Other Staff Strength</div>
                            <div class="row">
                                <div class="col-xs-3">
                                    <i class="fa fa-university fa-5x"></i>
                                </div>
                                <div class="col-xs-9 text-right">
                                    <div class="huge"><?php echo (isset($Summary['TotalNonTeachingStaff']) ? $Summary['TotalNonTeachingStaff'] : 0); ?></div>
                                    <div>Other Staff!</div>
                                </div>
                            </div>
                        </div>
                        <a href="/admin/school_administration/branch_staff_list.php">
                            <div class="panel-footer">
                                <span class="pull-left">View Details</span>
                                <span class="pull-right"><i class="fa fa-arrow-circle-right"></i></span>
                                <div class="clearfix"></div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
            <!-- /.row -->
            <hr style="clear:both;" />
        </div>
        <!-- /#page-wrapper -->

    </div>
    <!-- /#wrapper -->
<?php
require_once('footer.php');
?>
</body>
</html>
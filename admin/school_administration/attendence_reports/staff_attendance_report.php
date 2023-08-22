<?php
require_once('../../../classes/class.users.php');
require_once('../../../classes/class.validation.php');
require_once('../../../classes/class.authentication.php');

require_once('../../../classes/class.ui_helpers.php');

require_once('../../../includes/global_defaults.inc.php');

//1. RECHECK IF THE USER IS VALID //
try
{
	$AuthObject = new ApplicationAuthentication;
	$LoggedUser = new User(0, $AuthObject->CheckValidUser());
}

// THIS CATCH BLOCK BUBBLES THE EXCEPTION TO THE BUILT IN 'Exception' CLASS IF THERE ARE ANY UNCAUGHT ERRORS //
catch (ApplicationAuthException $e)
{
	header('location:../../unauthorized_login_admin.php');
	exit;
}
catch (Exception $e)
{
	header('location:../../unauthorized_login_admin.php');
	exit;
}
// END OF 1. //

if ($LoggedUser->HasPermissionForTask(TASK_TASK_LIST) !== true)
{
	header('location:../../unauthorized_login_admin.php');
	exit;
}

$HasErrors = false;

require_once('../../html_header.php');
?>
<title>Staff Attendace Report</title>
<!-- DataTables CSS -->
<link href="/admin/vendor/datatables-plugins/dataTables.bootstrap.css" rel="stylesheet">

<!-- DataTables Responsive CSS -->
<link href="/admin/vendor/datatables-responsive/dataTables.responsive.css" rel="stylesheet">
</head>

<body>

    <div id="wrapper">
    	<!-- Navigation -->
        <nav class="navbar navbar-default navbar-static-top" role="navigation" style="margin-bottom: 0">
<?php 
			require_once('../../site_header.php');
			require_once('../../left_navigation_menu.php');
?>                    
            <!-- /.navbar-static-side -->
        </nav>

        <div id="page-wrapper">
            <div class="row">
                <div class="col-lg-12">
                    <h1 class="page-header">Staff Attendace Report</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
            <form class="form-horizontal" name="frmMonthlyAttendanceRegister" action="staff_attendace.php" method="get">
                <div class="panel panel-default" id="accordion">
                    <div class="panel-heading">
                        <strong>Filters</strong>
                    </div>
                    <div id="collapseOne" class="panel-collapse collapse in">
                        <div class="panel-body">
<?php
                            if ($HasSearchErrors == true)
                            {
                                echo $SearchValidator->DisplayErrors();
                            }
?>
                            <div class="form-group">
                                <label for="Staff" class="col-lg-2 control-label">Staff</label>
                                <div class="col-lg-4">
                                    <input class="form-control" type="text" name="txtDate" value="16/11/2018">
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="col-lg-offset-2 col-lg-10">
                                    <input type="hidden" name="hdnProcess" value="7" />
                                    <button type="submit" class="btn btn-primary">Search</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    </div>
            </form>
            <!-- /.row -->
            <div class="row">
                <div class="col-lg-12">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <strong>Total Records Returned: <?php echo 17; ?></strong>
                        </div>
                        <!-- /.panel-heading -->
                        <div class="panel-body">
                            <div>
                                <div class="row">
                                    <div class="col-lg-12" style="text-align: right;">
                                        <div class="print-btn-container"><button id="PrintButton" type="submit" class="btn btn-primary">Print</button></div>
                                    </div>
                                </div>
<?php
                            if ($HasErrors == true)
                            {
                                echo $RecordValidator->DisplayErrorsInTable();
                            }
?>
                                <div class="row" id="RecordTableHeading">
                                    <div class="col-lg-12">
                                        <div class="report-heading-container"><strong>Staff Attendace Report on <?php echo date('d-m-Y h:i A'); ?></strong></div>
                                    </div>
                                </div>
                                <div class="row" id="RecordTable">
                                    <div class="col-lg-12">
                                        <table width="100%" class="table table-striped table-bordered table-hover" id="DataTableRecords">
                                            <thead>
                                                <tr>
                                                    <th>S. No</th>
                                                    <th>Staff Category</th>
                                                    <th>Name</th>
                                                    <th>Total Working Days</th>
                                                    <th>Present</th>
                                                    <th>Absent</th>
                                                    <th>LWP</th>
                                                    <th>Total Persent(%)</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>1</td>
                                                    <td>Teaching</td>
                                                    <td>AJITA SINGH</td>
                                                    <td>116</td>
                                                    <td>107</td>
                                                    <td>6</td>
                                                    <td>3</td>
                                                    <td>94.8</td>
                                                </tr>
                                                <tr>
                                                    <td>2</td>
                                                    <td>Teaching</td>
                                                    <td>SHRADDHA VERMA</td>
                                                    <td>116</td>
                                                    <td>115</td>
                                                    <td>1</td>
                                                    <td>0</td>
                                                    <td>99</td>
                                                </tr>
                                                <tr>
                                                    <td>3</td>
                                                    <td>Teaching</td>
                                                    <td>SATYAM KUMAR</td>
                                                    <td>116</td>
                                                    <td>116</td>
                                                    <td>0</td>
                                                    <td>0</td>
                                                    <td>100</td>
                                                </tr>
                                                <tr>
                                                    <td>4</td>
                                                    <td>Teaching</td>
                                                    <td>RANJANA SRIVASTAVA</td>
                                                    <td>116</td>
                                                    <td>107</td>
                                                    <td>9</td>
                                                    <td>0</td>
                                                    <td>90</td>
                                                </tr>
                                                <tr>
                                                    <td>5</td>
                                                    <td>Teaching</td>
                                                    <td>DURGESH SAHU</td>
                                                    <td>116</td>
                                                    <td>99</td>
                                                    <td>17</td>
                                                    <td>0</td>
                                                    <td>85.3</td>
                                                </tr>
                                                <tr>
                                                    <td>6</td>
                                                    <td>Teaching</td>
                                                    <td>DEEPIKA JAISWAL</td>
                                                    <td>116</td>
                                                    <td>102</td>
                                                    <td>13</td>
                                                    <td>2</td>
                                                    <td>89.6</td>
                                                </tr>                                                
                                                <tr>
                                                    <td>7</td>
                                                    <td>Teaching</td>
                                                    <td>DEVYANSHI MISHRA</td>
                                                    <td>116</td>
                                                    <td>108</td>
                                                    <td>8</td>
                                                    <td>0</td>
                                                    <td>93.1</td>
                                                </tr>
                                                <tr>
                                                    <td>8</td>
                                                    <td>Teaching</td>
                                                    <td>DEVESH KUMAR</td>
                                                    <td>116</td>
                                                    <td>113</td>
                                                    <td>3</td>
                                                    <td>0</td>
                                                    <td>97.4</td>
                                                </tr>
                                                <tr>
                                                    <td>9</td>
                                                    <td>Teaching</td>
                                                    <td>AKANKSHA GAUTAM</td>
                                                    <td>116</td>
                                                    <td>104</td>
                                                    <td>16</td>
                                                    <td>0</td>
                                                    <td>89.6</td>
                                                </tr>
                                                <tr>
                                                    <td>10</td>
                                                    <td>Teaching</td>
                                                    <td>SAMEER SINGH</td>
                                                    <td>116</td>
                                                    <td>90</td>
                                                    <td>20</td>
                                                    <td>6</td>
                                                    <td>82.7</td>
                                                </tr>
                                                <tr>
                                                    <td>11</td>
                                                    <td>Teaching</td>
                                                    <td>RAJNEESH DUBEY</td>
                                                    <td>116</td>
                                                    <td>107</td>
                                                    <td>6</td>
                                                    <td>3</td>
                                                    <td>94.8</td>
                                                </tr>
                                                <tr>
                                                    <td>12</td>
                                                    <td>Teaching</td>
                                                    <td>AKHIL KUMAR</td>
                                                    <td>116</td>
                                                    <td>116</td>
                                                    <td>116</td>
                                                    <td>0</td>
                                                    <td>100</td>
                                                </tr>
                                                <tr>
                                                    <td>13</td>
                                                    <td>Teaching</td>
                                                    <td>AMRESH KUMAR SINGH</td>
                                                    <td>116</td>
                                                    <td>109</td>
                                                    <td>6</td>
                                                    <td>1</td>
                                                    <td>94.8</td>
                                                </tr>
                                                <tr>
                                                    <td>14</td>
                                                    <td>Teaching</td>
                                                    <td>ABHINAV KHATRI</td>
                                                    <td>116</td>
                                                    <td>114</td>
                                                    <td>2</td>
                                                    <td>0</td>
                                                    <td>98.2</td>
                                                </tr>
                                                <tr>
                                                    <td>15</td>
                                                    <td>Teaching</td>
                                                    <td>AJITA SINGH</td>
                                                    <td>116</td>
                                                    <td>107</td>
                                                    <td>6</td>
                                                    <td>3</td>
                                                    <td>94.8</td>
                                                </tr>
                                                <tr>
                                                    <td>16</td>
                                                    <td>Teaching</td>
                                                    <td>SHIVAM SINGH</td>
                                                    <td>116</td>
                                                    <td>116</td>
                                                    <td>0</td>
                                                    <td>0</td>
                                                    <td>100</td>
                                                </tr>
                                                <tr>
                                                    <td>17</td>
                                                    <td>Non Teaching</td>
                                                    <td>Abhishek Pratap Singh</td>
                                                    <td>116</td>
                                                    <td>101</td>
                                                    <td>6</td>
                                                    <td>3</td>
                                                    <td>89.6</td>
                                                </tr>

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
        </div>
        <!-- /#page-wrapper -->

    </div>
    <!-- /#wrapper -->
<?php
require_once('../../footer.php');
?>
    <!-- DataTables JavaScript -->
    <script src="/admin/vendor/datatables/js/jquery.dataTables.min.js"></script>
    <script src="/admin/vendor/datatables-plugins/dataTables.bootstrap.min.js"></script>
	<script src="/admin/vendor/datatables-responsive/dataTables.responsive.js"></script>
	
	<!-- JavaScript To Print A Report -->
    <script src="/admin/js/print-report.js"></script>
	
	<script type="text/javascript">
<?php
    if (isset($_GET['POMode']))
    {
        $PageOperationResultMessage = '';
        $PageOperationResultMessage = UIHelpers::GetPageOperationResultMessage($_GET['POMode']);

        if ($PageOperationResultMessage != '')
        {
            echo 'alert("' . $PageOperationResultMessage . '");';
        }
    }
?>

	$(document).ready(function() {
		
        $("body").on('click', '.delete-record', function()
        {	
            if (!confirm("Are you sure you want to delete this task?"))
            {
                return false;
            }
        });

        $('#DataTableRecords').DataTable({
            responsive: true,
            bPaginate: false,
            bSort: false,
            searching: false, 
            info: false
        });
	});
    </script>
</body>
</html>
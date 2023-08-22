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
<title>Daily Class Attendace Report</title>
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
                    <h1 class="page-header">Daily Class Attendace Report</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
            <!-- /.row -->
            <div class="row">
                <div class="col-lg-12">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <strong>Total Records Returned: <?php echo 16; ?></strong>
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
                                        <div class="report-heading-container"><strong>Daily Class Attendace Report on <?php echo date('d-m-Y h:i A'); ?></strong></div>
                                    </div>
                                </div>
                                <div class="row" id="RecordTable">
                                    <div class="col-lg-12">
                                        <table width="100%" class="table table-striped table-bordered table-hover" id="DataTableRecords">
                                            <thead>
                                                <tr>
                                                    <th>S. No</th>
                                                    <th>Class Section</th>
                                                    <th>Total Student</th>
                                                    <th>Present</th>
                                                    <th>Absent</th>
                                                    <th>Class Teacher's Name</th>
                                                    <th>Signature</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>1</td>
                                                    <td>1(A)</td>
                                                    <td>37</td>
                                                    <td>30</td>
                                                    <td>7</td>
                                                    <td>AJITA SINGH</td>
                                                    <td></td>
                                                </tr>
                                                <tr>
                                                    <td>2</td>
                                                    <td>1(B)</td>
                                                    <td>25</td>
                                                    <td>25</td>
                                                    <td>0</td>
                                                    <td>SHRADDHA VERMA</td>
                                                    <td></td>
                                                </tr>
                                                <tr>
                                                    <td>3</td>
                                                    <td>1(C)</td>
                                                    <td>30</td>
                                                    <td>22</td>
                                                    <td>8</td>
                                                    <td>SATYAM KUMAR</td>
                                                    <td></td>
                                                </tr>
                                                <tr>
                                                    <td>4</td>
                                                    <td>1(D)</td>
                                                    <td>20</td>
                                                    <td>18</td>
                                                    <td>2</td>
                                                    <td>RANJANA SRIVASTAVA</td>
                                                    <td></td>
                                                </tr>
                                                <tr>
                                                    <td>5</td>
                                                    <td>2(A)</td>
                                                    <td>30</td>
                                                    <td>26</td>
                                                    <td>4</td>
                                                    <td>DURGESH SAHU</td>
                                                    <td></td>
                                                </tr>
                                                <tr>
                                                    <td>6</td>
                                                    <td>2(B)</td>
                                                    <td>33</td>
                                                    <td>30</td>
                                                    <td>3</td>
                                                    <td>DEEPIKA JAISWAL</td>
                                                    <td></td>
                                                </tr>
                                                <tr>
                                                    <td>7</td>
                                                    <td>2(C)</td>
                                                    <td>17</td>
                                                    <td>17</td>
                                                    <td>0</td>
                                                    <td>DEVYANSHI MISHRA</td>
                                                    <td></td>
                                                </tr>
                                                <tr>
                                                    <td>8</td>
                                                    <td>3(A)</td>
                                                    <td>37</td>
                                                    <td>30</td>
                                                    <td>7</td>
                                                    <td>DEVESH KUMAR</td>
                                                    <td></td>
                                                </tr>
                                                <tr>
                                                    <td>9</td>
                                                    <td>4(A)</td>
                                                    <td>24</td>
                                                    <td>20</td>
                                                    <td>4</td>
                                                    <td>AKANKSHA GAUTAM</td>
                                                    <td></td>
                                                </tr>
                                                <tr>
                                                    <td>10</td>
                                                    <td>4(B)</td>
                                                    <td>29</td>
                                                    <td>29</td>
                                                    <td>0</td>
                                                    <td>SAMEER SINGH</td>
                                                    <td></td>
                                                </tr>
                                                <tr>
                                                    <td>11</td>
                                                    <td>5(A)</td>
                                                    <td>42</td>
                                                    <td>40</td>
                                                    <td>2</td>
                                                    <td>RAJNEESH DUBEY</td>
                                                    <td></td>
                                                </tr>
                                                <tr>
                                                    <td>12</td>
                                                    <td>6(A)</td>
                                                    <td>37</td>
                                                    <td>30</td>
                                                    <td>7</td>
                                                    <td>AKHIL KUMAR</td>
                                                    <td></td>
                                                </tr>
                                                <tr>
                                                    <td>13</td>
                                                    <td>6(B)</td>
                                                    <td>34</td>
                                                    <td>30</td>
                                                    <td>4</td>
                                                    <td>AMRESH KUMAR SINGH</td>
                                                    <td></td>
                                                </tr>
                                                <tr>
                                                    <td>14</td>
                                                    <td>7(A)</td>
                                                    <td>32</td>
                                                    <td>32</td>
                                                    <td>0</td>
                                                    <td>ABHINAV KHATRI</td>
                                                    <td></td>
                                                </tr>
                                                <tr>
                                                    <td>15</td>
                                                    <td>8(A)</td>
                                                    <td>36</td>
                                                    <td>36</td>
                                                    <td>0</td>
                                                    <td>SHIVAM SINGH</td>
                                                    <td></td>
                                                </tr>
                                                <tr>
                                                    <td>16</td>
                                                    <td>8(b)</td>
                                                    <td>19</td>
                                                    <td>18</td>
                                                    <td>1</td>
                                                    <td>ANURAG GAUTAM</td>
                                                    <td></td>
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
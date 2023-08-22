<?php
require_once('../../classes/class.users.php');
require_once('../../classes/class.validation.php');
require_once('../../classes/class.authentication.php');

require_once('../../classes/school_administration/class.classes.php');
require_once('../../classes/school_administration/class.class_sections.php');

require_once('../../classes/school_administration/class.students.php');
require_once('../../classes/school_administration/class.student_details.php');

require_once('../../classes/class.ui_helpers.php');

require_once('../../includes/global_defaults.inc.php');

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

// if ($LoggedUser->HasPermissionForTask(TASK_TASK_LIST) !== true)
// {
// 	header('location:../unauthorized_login_admin.php');
// 	exit;
// }

$Filters = array();

$ClassList =  array();
$ClassList = AddedClass::GetAllClasses(true);

$ClassSectionsList =  array();

$HasErrors = false;

$Clean = array();

$Clean['Process'] = 0;

$Clean['SiblingName'] = '';

$Clean['ClassID'] = 0;
$Clean['ClassSectionID'] = 0;

$Clean['EnrollmentID'] = '';

$Clean['FatherName'] = '';
$Clean['MotherName'] = '';

$Clean['ParentAadharNumber'] = 0;

if (isset($_GET['hdnProcess']))
{
	$Clean['Process'] = (int) $_GET['hdnProcess'];
}
else if (isset($_GET['Process']))
{
	$Clean['Process'] = (int) $_GET['Process'];
}
switch ($Clean['Process'])
{
    case 7:
		if (isset($_GET['drdClassID']))
		{
			$Clean['ClassID'] = (int) $_GET['drdClassID'];
        }
        if (isset($_GET['drdClassSectionID']))
		{
			$Clean['ClassSectionID'] = (int) $_GET['drdClassSectionID'];
        }
        
        if (isset($_GET['txtSiblingName']))
		{
			$Clean['SiblingName'] = strip_tags(trim($_GET['txtSiblingName']));
        }
        if (isset($_GET['txtEnrollmentID']))
		{
			$Clean['EnrollmentID'] = strip_tags(trim($_GET['txtEnrollmentID']));
        }
        if (isset($_GET['txtFatherName']))
		{
			$Clean['FatherName'] = strip_tags(trim($_GET['txtFatherName']));
        }
        if (isset($_GET['txtMotherName']))
		{
			$Clean['MotherName'] = strip_tags(trim($_GET['txtMotherName']));
        }
        if (isset($_GET['txtParentAadharNumber']))
		{
			$Clean['ParentAadharNumber'] = (int) $_GET['txtParentAadharNumber'];
		}

		$RecordValidator = new Validator();

        if ($Clean['ClassID'] > 0)
        {
            if ($RecordValidator->ValidateInSelect($Clean['ClassID'], $ClassList, 'Unknown error, please try again.'))
            {
                $ClassSectionsList = ClassSections::GetClassSections($Clean['ClassID']);

                if ($Clean['ClassSectionID'] > 0) 
                {
                    $RecordValidator->ValidateInSelect($Clean['ClassSectionID'], $ClassSectionsList, 'Unknown error in, please try again.');
                }
            }
        }

        if ($Clean['SiblingName'] != '')
        {
            $RecordValidator->ValidateStrings($Clean['SiblingName'], 'Please enter a valid sibling name between 3 to 51 chars.', 3, 51);
        }

        if ($Clean['EnrollmentID'] != '')
        {
            $RecordValidator->ValidateStrings($Clean['EnrollmentID'], 'Please enter a valid enrollment ID between 5 to 50 chars.', 5, 50);
        }

        if ($Clean['FatherName'] != '')
        {
            $RecordValidator->ValidateStrings($Clean['FatherName'], 'Please enter a valid father name between 2 to 51 chars.', 2, 51);
        }

        if ($Clean['MotherName'] != '')
        {
            $RecordValidator->ValidateStrings($Clean['MotherName'], 'Please enter a valid mother name between 2 to 51 chars.', 2, 51);
        }

        if ($Clean['ParentAadharNumber'] != '')
        {
            $RecordValidator->ValidateStrings($Clean['ParentAadharNumber'], 'Please enter a valid 12 digit aadhaar number.', 12, 12);
        }
		
		if ($RecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
        }
        
        //set record filters
		$Filters['ClassID'] = $Clean['ClassID'];
		$Filters['ClassSectionID'] = $Clean['ClassSectionID'];
        $Filters['SiblingName'] = $Clean['SiblingName'];
        $Filters['EnrollmentID'] = $Clean['EnrollmentID'];
        $Filters['FatherName'] = $Clean['FatherName'];
        $Filters['MotherName'] = $Clean['MotherName'];
        $Filters['ParentAadharNumber'] = $Clean['ParentAadharNumber'];

        $ExistingParentDetails = StudentDetail::SearchExistingParent($Filters);
    break;
}

require_once('../html_header.php');
?>
<title>Search Existing Parent</title>
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
			require_once('../site_header.php');
			require_once('../left_navigation_menu.php');
?>                    
            <!-- /.navbar-static-side -->
        </nav>

        <div id="page-wrapper">
            <div class="row">
                <div class="col-lg-12">
                    <h1 class="page-header">Search Existing Parent</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
            <form class="form-horizontal" name="frmUserReport" action="search_existing_parent.php" method="get">
                <div class="panel panel-default" id="accordion">
                    <div class="panel-heading">
						<strong>Filters</strong>
                    </div>
                    <div id="collapseOne" class="panel-collapse collapse in">
                        <div class="panel-body">
<?php
                            if ($HasErrors == true)
                            {
                                echo $RecordValidator->DisplayErrors();
                            }
?>
                            <div class="form-group">
                                <label for="Class" class="col-lg-2 control-label">Select Class</label>
                                <div class="col-lg-4">
                                    <select class="form-control" name="drdClassID" id="Class">
                                        <option value="0">Select Class</option>
    <?php
                                    if (is_array($ClassList) && count($ClassList) > 0)
                                    {
                                        foreach ($ClassList as $ClassID => $ClassName) {
                                            echo '<option '.($Clean['ClassID'] == $ClassID ? 'selected="selected"' : '').' value="'.$ClassID.'">'.$ClassName.'</option>';
                                        }
                                    }
    ?>
                                    </select>
                                </div>

                                <label for="ClassSection" class="col-lg-2 control-label">Select Section</label>
                                <div class="col-lg-4">
                                    <select class="form-control" name="drdClassSectionID" id="ClassSection">
                                        <option value="0">Select Section</option>
    <?php
                                    if (is_array($ClassSectionsList) && count($ClassSectionsList) > 0)
                                    {
                                        foreach ($ClassSectionsList as $ClassSectionID => $SectionName) {
                                            echo '<option '.($Clean['ClassSectionID'] == $ClassSectionID ? 'selected="selected"' : '').' value="'.$ClassSectionID.'">'.$SectionName.'</option>';
                                        }
                                    }
    ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="SiblingName" class="col-lg-2 control-label">Sibling Name</label>
                                <div class="col-lg-4">
                                    <input type="text" name="txtSiblingName" id="SiblingName" class="form-control" value="<?php echo $Clean['SiblingName']; ?>" />
                                </div>

                                <label for="EnrollmentID" class="col-lg-2 control-label">Enrollment ID</label>
                                <div class="col-lg-4">
                                    <input type="text" name="txtEnrollmentID" id="EnrollmentID" class="form-control" value="<?php echo $Clean['EnrollmentID']; ?>" />
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="FatherName" class="col-lg-2 control-label">Father Name</label>
                                <div class="col-lg-4">
                                    <input type="text" name="txtFatherName" id="FatherName" class="form-control" value="<?php echo $Clean['FatherName']; ?>" />
                                </div>

                                <label for="MotherName" class="col-lg-2 control-label">Mother Name</label>
                                <div class="col-lg-4">
                                    <input type="text" name="txtMotherName" id="MotherName" class="form-control" value="<?php echo $Clean['MotherName']; ?>" />
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="ParentAadharNumber" class="col-lg-2 control-label">Parent Aadhar Number</label>
                                <div class="col-lg-4">
                                    <input type="text" name="txtParentAadharNumber" id="ParentAadharNumber" class="form-control" value="<?php echo $Clean['ParentAadharNumber'] ? $Clean['ParentAadharNumber'] : ''; ?>" />
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="col-lg-offset-2 col-lg-10">
                                    <input type="hidden" name="hdnProcess" value="7" />
                                    <button type="submit" class="btn btn-primary"><i class="fa fa-search">&nbsp;</i>Search</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    </div>
            </form>
            <!-- /.row -->
<?php
		if ($HasErrors == false && $Clean['Process'] == 7)
		{
?>
            <div class="row">
                <div class="col-lg-12">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <strong>Total Records Returned: <?php echo count($ExistingParentDetails); ?></strong>
                        </div>
                        <!-- /.panel-heading -->
                        <div class="panel-body">
                            <div>
                                <div class="row">
                                    <div class="col-lg-6">&nbsp;</div>
                                    <div class="col-lg-6">
                                        <div class="print-btn-container"><button id="PrintButton" type="submit" class="btn btn-primary"><i class="fa fa-print">&nbsp;</i>Print</button></div><br />
                                    </div>
                                </div>
                                <div class="row" id="RecordTable">
                                    <div class="col-lg-12">
                                        <table width="100%" class="table table-striped table-bordered table-hover" id="DataTableRecords">
                                            <thead>
                                                <tr>
                                                    <th>S. No</th>
                                                    <th>Father Name</th>
                                                    <th>Mother Name</th>

                                                    <th>Sibling Name</th>
                                                    <th>Enr. ID</th>
                                                    <th>Gender</th>
                                                    <th>Address</th>
                                                    
                                                    <th>Parent Aadhar No.</th>

                                                    <th>Class/Section</th>
                                                    <th class="print-hidden">Operations</th>
                                                </tr>
                                            </thead>
                                            <tbody>
<?php
                                    if (is_array($ExistingParentDetails) && count($ExistingParentDetails) > 0)
                                    {
                                        $Counter = 0;
                                        foreach ($ExistingParentDetails as $ParentID => $StudentDetails)
                                        {
?>
                                        <tr>
                                            <td><?php echo ++$Counter; ?></td>
                                            <td><?php echo $StudentDetails['FatherFirstName'].' '.$StudentDetails['FatherLastName'].($StudentDetails['FatherMobileNumber'] ? '<br />('.$StudentDetails['FatherMobileNumber'].')' : ''); ?></td>
                                            <td><?php echo $StudentDetails['MotherFirstName'].' '.$StudentDetails['MotherLastName'].($StudentDetails['MotherMobileNumber'] ? '<br />('.$StudentDetails['MotherMobileNumber'].')' : ''); ?></td>
                                            <td><?php echo $StudentDetails['FirstName'].' '.$StudentDetails['LastName']; ?></td>

                                            <td><?php echo $StudentDetails['EnrollmentID']; ?></td>
                                            <td><?php echo $StudentDetails['Gender']; ?></td>
                                            <td><?php echo $StudentDetails['Address1'].'<br />'.$StudentDetails['Address2']; ?></td>

                                            <td><?php echo ($StudentDetails['ParentAadharNumber'] ? $StudentDetails['ParentAadharNumber'] : ''); ?></td>

                                            <td><?php echo $StudentDetails['ClassName'].'/'.$StudentDetails['SectionName']; ?></td>

                                            <td class="print-hidden"><label><input type="radio" name="rdbParentID" class="ParentID" value="<?php echo $ParentID; ?>" />&nbsp;Select</label></td>
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
    <script src="/admin/vendor/datatables/js/jquery.dataTables.min.js"></script>
    <script src="/admin/vendor/datatables-plugins/dataTables.bootstrap.min.js"></script>
	<script src="/admin/vendor/datatables-responsive/dataTables.responsive.js"></script>
	
	<!-- JavaScript To Print A Report -->
    <script src="/admin/js/print-report.js"></script>
	
	<script type="text/javascript">
	$(document).ready(function() {
		
        $("body").on('click', '.delete-record', function()
        {	
            if (!confirm("Are you sure you want to delete this task?"))
            {
                return false;
            }
        });

        $("body").on('click', '.ParentID', function()
        {	
            if (!confirm("Do you really want to continue?"))
            {
                return false;
            }

            opener.CallParent($(this).val());
            window.close();
        });

        $('#DataTableRecords').DataTable({
            responsive: true,
            bPaginate: false,
            bSort: false,
            searching: false, 
            info: false
        });

        $('#Class').change(function(){

        var ClassID = parseInt($(this).val());
                
        if (ClassID <= 0)
        {
            $('#ClassSection').html('<option value="0">-- Select Section --</option>');
            return;
        }
        
        $.post("/xhttp_calls/get_sections_by_classs.php", {SelectedClassID:ClassID}, function(data)
        {
            ResultArray = data.split("|*****|");
            
            if (ResultArray[0] == 'error')
            {
                alert (ResultArray[1]);
                return false;
            }
            else
            {
                $('#ClassSection').html('<option value="0">-- Select Section --</option>' + ResultArray[1]);
            }
        });
    });

	});
    </script>
</body>
</html>
<?php
require_once('../html_header.php');
?>
<title>Students</title>
<!-- DataTables CSS -->
<link href="../vendor/datatables-plugins/dataTables.bootstrap.css" rel="stylesheet">

<!-- DataTables Responsive CSS -->
<link href="../vendor/datatables-responsive/dataTables.responsive.css" rel="stylesheet">
</head>
<body>

    <div id="wrapper">
    	<!-- Navigation -->

        <div id="page-wrapper" style="margin-left: 0px;">            
<?php
        if ($Clean['Process'] == 7 && $HasErrors == false)
        {
            $ReportHeaderText = '';

            if ($Clean['ClassID'] != 0)
            {
                $ReportHeaderText .= ' Class: ' . $ClassList[$Clean['ClassID']] . ',';
            }

            if ($Clean['ClassSectionID'] != 0)
            {
                $ReportHeaderText .= ' Section: ' . $ClassSectionsList[$Clean['ClassSectionID']] . ',';
            }

            if (!empty($Clean['Gender']) && is_array($Clean['Gender']))
            {
                $Genders = '';
                foreach ($Clean['Gender'] as $Gender) 
                {
                    $Genders .= $Gender . ', ';
                }

                $ReportHeaderText .= ' Gender: ' . $Genders;
            }

            if (!empty($Clean['Category']) && is_array($Clean['Category']))
            {
                $Categories = '';
                foreach ($Clean['Category'] as $Category) 
                {
                    $Categories .= $Category . ', ';
                }

                $ReportHeaderText .= ' Category: ' . $Categories;
            }

            if (!empty($Clean['Other']) && is_array($Clean['Other']))
            {
                $OtherCategories = '';
                foreach ($Clean['Other'] as $OtherCategory) 
                {
                    $OtherCategories .= $OtherCategoryList[$OtherCategory] . ', ';
                }

                $ReportHeaderText .= ' Other Category: ' . $OtherCategories;
            }

            if (!empty($Clean['BloodGroup']) && is_array($Clean['BloodGroup']))
            {
                $BloodGroups = '';
                foreach ($Clean['BloodGroup'] as $BloodGroup) 
                {
                    $BloodGroups .= $BloodGroup . ', ';
                }

                $ReportHeaderText .= ' Blood Group: ' . $BloodGroups;
            }

            if ($Clean['StudentName'] != '')
            {
                $ReportHeaderText .= ' Student Name: ' . $Clean['StudentName'] . ',';
            }
            
            if ($Clean['FatherName'] != '')
            {
                $ReportHeaderText .= ' Father Name: ' . $Clean['FatherName'] . ',';
            }

            if ($ReportHeaderText != '')
            {
                $ReportHeaderText = ' for' . rtrim($ReportHeaderText, ',');
            }
?>
            <div class="row">
                <div class="col-lg-12">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <strong>Total Records Returned: <?php echo $TotalRecords; ?></strong>
                        </div>
                        <!-- /.panel-heading -->
                        <div class="panel-body">
                            <div>
                                <div class="row">
                                    <div class="col-lg-12">
                                        <div class="print-btn-container">
											<button id="PrintButton" type="submit" class="btn btn-primary">Print</button>
										</div>
                                    </div>
                                </div>
                                <div class="row" id="RecordTableHeading">
                                    <div class="col-lg-12">
                                    	<div class="report-heading-container"><strong>Students List on <?php echo date('d-m-Y h:i A'); ?></strong></div>
                                    </div>
								</div>
                                <div class="row" id="RecordTable">
                                    <div class="col-lg-12">
                                        <table width="100%" class="table table-striped table-bordered table-hover" id="DataTableRecords">
                                            <thead>
                                                <tr>
                                                    <th>S. No</th>
                                                    <th>Sr. No</th>
                                                    <th>Student Name</th>
                                                    <th>Roll Number</th>
                                                    <th>Class</th>
                                                    <th>Father Name</th>
                                                    <th>Mother Name</th>
                                                    <th>Category</th>
                                                    <th>Gender</th>
                                                    <th>DOB</th>
                                                    <th>Contact</th>
                                                    <th>Fee Code</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
<?php
                                    if (is_array($AllStudents) && count($AllStudents) > 0)
                                    {
                                        $Counter = $Start;
                                        foreach ($AllStudents as $StudentID => $StudentDetails)
                                        {
?>
                                            <tr>
                                                <td><?php echo ++$Counter; ?></td>
                                                <td><?php echo $StudentDetails['EnrollmentID']; ?></td>
                                                <td><?php echo $StudentDetails['FirstName'].' '.$StudentDetails['LastName']; ?></td>
                                                <td><?php echo $StudentDetails['RollNumber']; ?></td>
                                                <td><?php echo $StudentDetails['ClassSymbol'].' '.$StudentDetails['SectionName']; ?></td>
                                                <td><?php echo $StudentDetails['FatherFirstName'].' '.$StudentDetails['FatherLastName']; ?></td>
                                                <td><?php echo $StudentDetails['MotherFirstName'].' '.$StudentDetails['MotherLastName']; ?></td>
                                                <td><?php echo $StudentDetails['Category']; ?></td>
                                                <td><?php echo $StudentDetails['Gender']; ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($StudentDetails['Dob'])); ?></td>
                                                <td><?php echo $StudentDetails['FatherMobileNumber'].'<br>'.$StudentDetails['MotherMobileNumber']; ?></td>
                                                <td><?php echo $StudentDetails['FeeCode']; ?></td>
                                                <td><?php echo $StudentDetails['Status']; ?></td>
                                            </tr>
    <?php
                                        }
                                    }
                                    else
                                    {
    ?>
                                                <tr>
                                                    <td colspan="13">No Records</td>
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
	
	<script type="text/javascript">
    </script>
	
	<!-- JavaScript To Print A Report -->
    <script src="/admin/js/print-report.js"></script>
    
    <!-- DataTables JavaScript -->
	<script src="../vendor/datatables/js/jquery.dataTables.min.js"></script>
	<script src="../vendor/datatables-plugins/dataTables.bootstrap.min.js"></script>
	<script src="../vendor/datatables-responsive/dataTables.responsive.js"></script>   
	<script type="text/javascript">

	$(document).ready(function() {
		$('#PrintButton').click();
	});
	</script>
</body>
</html> 

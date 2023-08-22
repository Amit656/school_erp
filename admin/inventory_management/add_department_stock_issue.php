<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once("../../classes/inventory_management/class.products.php");
require_once("../../classes/inventory_management/class.stock_issue.php");
require_once("../../classes/inventory_management/class.departments.php");
require_once("../../classes/school_administration/class.branch_staff.php");
require_once("../../classes/school_administration/class.classes.php");
require_once("../../classes/school_administration/class.students.php");
require_once('../../classes/school_administration/class.student_details.php');
require_once("../../classes/inventory_management/class.department_stock_issue.php");

require_once("../../classes/class.date_processing.php");

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
	header('location:unauthorized_login_admin.php');
	exit;
}
catch (Exception $e)
{
	header('location:unauthorized_login_admin.php');
	exit;
}
// END OF 1. //

if ($LoggedUser->HasPermissionForTask(TASK_ADD_STOCK_ISSUE_TO_DEPARTMENT) !== true)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}

$IssuedToTypeList = array('Student' => 'Student', 'Staff' => 'Staff');

$StaffCategoryList = array('Teaching' => 'Teaching Staff', 'NonTeaching' => 'Non Teaching Staff');

$AllBranchStaffList = array();
$AllBranchStaffList = BranchStaff::GetActiveBranchStaff('Teaching');

$AllClassList = array();
$AllClassList = AddedClass::GetActiveClasses();

$StudentsList = array();
$ClassSectionsList = array();

$AllDepartmentList = array();
$AllDepartmentList = Department::GetActiveDepartments();

$AllProductList = array();
$ProductStockQuantity = 0;

$HasErrors = false;

$Clean = array();
$Clean['Process'] = 0;

$Clean['DepartmentID'] = 0;
$Clean['DepartmentID'] = key($AllDepartmentList);

$AllProductList = StockIssue::GetProductsByDepartmentID($Clean['DepartmentID']);

$Clean['ProductID'] = 0;
$Clean['ProductID'] = key($AllProductList);

$ProductStockQuantity = StockIssue::GetStockQunatity($Clean['ProductID']);

$Clean['IssuedToType'] = 'Student';

$Clean['StaffCategory'] = '';
$Clean['IssuedToID'] = 0;

$Clean['ClassID'] = 0;
$Clean['ClassID'] = key($AllClassList);

$ClassSectionsList = AddedClass::GetClassSections($Clean['ClassID']);

$Clean['ClassSectionID'] = 0;
$Clean['ClassSectionID'] = key($ClassSectionsList);

$StudentsList = StudentDetail::GetStudentsByClassSectionID($Clean['ClassSectionID']);

$Clean['StudentID'] = 0;

$Clean['IssuedQuantity'] = 0;
$Clean['IssueDate'] = '';
$Clean['ReturnDate'] = '';

if (isset($_POST['hdnProcess']))
{
	$Clean['Process'] = (int) $_POST['hdnProcess'];
}
switch ($Clean['Process'])
{
	case 1:	
		if (isset($_POST['drdDepartment']))
		{
			$Clean['DepartmentID'] = (int) $_POST['drdDepartment'];
		}

		if (isset($_POST['drdProduct']))
		{
			$Clean['ProductID'] = (int) $_POST['drdProduct'];
		}

		if (isset($_POST['drdIssuedToType']))
		{
			$Clean['IssuedToType'] = strip_tags(trim($_POST['drdIssuedToType']));
		}

		if (isset($_POST['txtIssuedQuantity']))
		{
			$Clean['IssuedQuantity'] = strip_tags(trim($_POST['txtIssuedQuantity']));
		}

		if (isset($_POST['drdClass']))
		{
			$Clean['ClassID'] = (int) $_POST['drdClass'];
		}

		if (isset($_POST['drdClassSection']))
		{
			$Clean['ClassSectionID'] = (int) $_POST['ClassSection'];
		}

		if (isset($_POST['drdStaffCategory']))
		{
			$Clean['StaffCategory'] = strip_tags(trim($_POST['drdStaffCategory']));
		}

		if (isset($_POST['drdStudent']))
		{
			$Clean['IssuedToID'] = (int) $_POST['drdStudent'];
		}
		elseif (isset($_POST['drdBranchStaff']))
		{
			$Clean['IssuedToID'] = (int) $_POST['drdBranchStaff'];
		}

		if (isset($_POST['txtIssueDate']))
		{
			$Clean['IssueDate'] = strip_tags(trim($_POST['txtIssueDate']));
		}

		if (isset($_POST['txtReturnDate']))
		{
			$Clean['ReturnDate'] = strip_tags(trim($_POST['txtReturnDate']));
		}
		
		$NewRecordValidator = new Validator();
		$NewRecordValidator->ValidateInSelect($Clean['DepartmentID'], $AllDepartmentList, 'Unknown error, please try again.');
		
		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}

		$AllProductList = StockIssue::GetProductsByDepartmentID($Clean['DepartmentID']);

		$NewRecordValidator->ValidateInSelect($Clean['ProductID'], $AllProductList, 'Unknown error, please try again.');

		$ProductStockQuantity = StockIssue::GetStockQunatity($Clean['ProductID']);

		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}

		$NewRecordValidator->ValidateInSelect($Clean['IssuedToType'], $IssuedToTypeList, 'Unknown error, please try again.');
		$NewRecordValidator->ValidateInteger($Clean['IssuedQuantity'], 'Issued quantity should be integer.', 1);

		if (isset($_POST['drdStudent'])) 
		{
			if ($NewRecordValidator->ValidateInSelect($Clean['ClassID'], $AllClassList, 'Unknown error, please try again.')) 
			{
				$ClassSectionsList = AddedClass::GetClassSections($Clean['ClassID']);

				if ($NewRecordValidator->ValidateInSelect($Clean['ClassSectionID'], $ClassSectionsList, 'Unknown error, please try again.')) 
				{
					$StudentsList = StudentDetail::GetStudentsByClassSectionID($Clean['ClassSectionID']);

					$NewRecordValidator->ValidateInSelect($Clean['IssuedToID'], $StudentsList, 'Unknown error, please try again.');
				}
			}
		}
		elseif (isset($_POST['drdBranchStaff']))
		{
			$NewRecordValidator->ValidateInSelect($Clean['StaffCategory'], $StaffCategoryList, 'Unknown error, please try again.');

			if ($NewRecordValidator->HasNotifications())
			{
				$HasErrors = true;
				break;
			}

			$AllBranchStaffList = BranchStaff::GetActiveBranchStaff($Clean['StaffCategory']);

			$NewRecordValidator->ValidateInSelect($Clean['IssuedToID'], $AllBranchStaffList, 'Unknown error, please try again.');
		}

		$NewRecordValidator->ValidateDate($Clean['IssueDate'], 'Please enter valid issue date.');

		$ReturnDate = '';
		if ($Clean['ReturnDate'] != '') 
		{
			$NewRecordValidator->ValidateDate($Clean['ReturnDate'], 'Please enter valid return  date.');
			$ReturnDate = date('Y-m-d',strtotime(DateProcessing::ToggleDateDayAndMonth($Clean['ReturnDate'])));

			if ($Clean['ReturnDate'] <=  $Clean['IssueDate']) 
			{
				$NewRecordValidator->AttachTextError('return date cannot be less than issue date.');
			}
		}

		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}
			
		$NewDepartmentStockIssue = new DepartmentStockIssue();
				
		$NewDepartmentStockIssue->SetDepartmentID($Clean['DepartmentID']);
		$NewDepartmentStockIssue->SetProductID($Clean['ProductID']);
		$NewDepartmentStockIssue->SetIssuedToType($Clean['IssuedToType']);
		$NewDepartmentStockIssue->SetIssuedToID($Clean['IssuedToID']);
		$NewDepartmentStockIssue->SetIssuedQuantity($Clean['IssuedQuantity']);
		$NewDepartmentStockIssue->SetIssueDate(date('Y-m-d',strtotime(DateProcessing::ToggleDateDayAndMonth($Clean['IssueDate']))));
		$NewDepartmentStockIssue->SetReturnDate($ReturnDate);
		
		$NewDepartmentStockIssue->SetCreateUserID($LoggedUser->GetUserID());

		// if ($NewDepartmentStockIssue->RecordExists())
  //       {
  //           $NewRecordValidator->AttachTextError('Department stock issue you have added already exists.');
  //           $HasErrors = true;
  //           break;
  //       }

		if ($ProductStockQuantity < 0) 
		{
			$NewRecordValidator->AttachTextError('product not in stock.');
            $HasErrors = true;
            break;
		}

		if (!$NewDepartmentStockIssue->Save())
		{
			$NewRecordValidator->AttachTextError(ProcessErrors($NewDepartmentStockIssue->GetLastErrorCode()));
			$HasErrors = true;

			break;
		}

		header('location:department_stock_issues_list.php?Mode=ED');
		exit;
		break;
}

require_once('../html_header.php');
?>
<title>Add Department Stock Issue</title>
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
                    <h1 class="page-header">Add Department Stock Issue</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="AddDepartmentStockIssue" action="add_department_stock_issue.php" method="post">
            	<div class="panel panel-default">
                    <div class="panel-heading">
                        Enter Department Stock Issue Details
                    </div>
                    <div class="panel-body">
<?php
						if ($HasErrors == true)
						{
							echo $NewRecordValidator->DisplayErrors();
						}
?>                    
                    	<div class="form-group">
                            <label for="Department" class="col-lg-2 control-label">Department</label>
							<div class="col-lg-4">
								<select class="form-control" name="drdDepartment" id="Department">
<?php
								foreach ($AllDepartmentList as $DepartmentID => $DepartmentName)
								{
?>
									<option <?php echo ($Clean['DepartmentID'] == $DepartmentID) ? 'selected="selected"' : ''; ?> value="<?php echo $DepartmentID; ?>"><?php echo $DepartmentName; ?></option>
<?php
								}
?>
								</select>
							</div>
							<label for="Product" class="col-lg-2 control-label">Product</label>
							<div class="col-lg-4">
								<select class="form-control" name="drdProduct" id="Product">
<?php
								foreach ($AllProductList as $ProductID => $ProductName)
								{
?>
									<option <?php echo ($Clean['ProductID'] == $ProductID) ? 'selected="selected"' : ''; ?> value="<?php echo $ProductID; ?>"><?php echo $ProductName; ?></option>
<?php
								}
?>
								</select>
								<span id="StockQuantity" style="color: red;">Product Stock Quantity = <?php echo $ProductStockQuantity;?></span>
							</div>
                        </div>
                        <div class="form-group">
                        	<label for="IssuedToType" class="col-lg-2 control-label">Issued To Type</label>
							<div class="col-lg-4">
								<select class="form-control" name="drdIssuedToType" id="IssuedToType">
<?php
								foreach ($IssuedToTypeList as $IssuedToType => $IssueTypeName)
								{
?>
									<option <?php echo ($Clean['IssuedToType'] == $IssuedToType) ? 'selected="selected"' : ''; ?> value="<?php echo $IssuedToType; ?>"><?php echo $IssueTypeName; ?></option>
<?php
								}
?>
								</select>
							</div>
							<label for="IssuedQuantity" class="col-lg-2 control-label">Issued Quantity</label>
                            <div class="col-lg-4">
                            	<input class="form-control" type="text" name="txtIssuedQuantity" value="<?php echo ($Clean['IssuedQuantity']) ? $Clean['IssuedQuantity'] : ''; ?>"/>	
                            </div>
                        </div>
                        <div class="form-group Staff" <?php echo($Clean['IssuedToType'] == 'Student') ? 'style="display:none;"' : ''?>>
	                        <label for="StaffCategory" class="col-lg-2 control-label">Staff Category</label>
	                        <div class="col-lg-4">
	                            <select class="form-control IssuedToBranchStaff" name="drdStaffCategory" id="StaffCategory" <?php echo($Clean['IssuedToType'] == 'Student') ? 'disabled="disabled"' : ''?>>
<?php
                                foreach ($StaffCategoryList as $StaffCategory => $StaffCategoryName)
                                {
?>
                                    <option <?php echo ($StaffCategory == $Clean['StaffCategory'] ? 'selected="selected"' : ''); ?> value="<?php echo $StaffCategory; ?>"><?php echo $StaffCategoryName; ?></option>
<?php
                                }
?>
	                            </select>
	                        </div>
	                        <label for="BranchStaff" class="col-lg-2 control-label">Branch Staff</label>
	                        <div class="col-lg-4">
	                            <select class="form-control IssuedToBranchStaff" name="drdBranchStaff" id="BranchStaffID" <?php echo($Clean['IssuedToType'] == 'Student') ? 'disabled="disabled"' : ''?>>
<?php
                                foreach ($AllBranchStaffList as $BranchStaffID => $BranchStaffName)
                                {
?>
                                    <option <?php echo ($BranchStaffID == $Clean['IssuedToID'] ? 'selected="selected"' : ''); ?> value="<?php echo $BranchStaffID; ?>"><?php echo $BranchStaffName['FirstName'] . " ". $BranchStaffName['LastName']; ?></option>
<?php
                                }
?>
	                            </select>
	                        </div>
	                    </div>
	                    <div class="form-group Student" <?php echo($Clean['IssuedToType'] == 'Student') ? '' : 'style="display:none"'?>>
                            <label for="ClassList" class="col-lg-2 control-label">Class List</label>
                            <div class="col-lg-4">
                            	<select class="form-control IssuedToStudent" name="drdClassID" id="Class" <?php echo($Clean['IssuedToType'] == 'Student') ? '' : 'disabled="disabled"'?> >
<?php
								foreach ($AllClassList as $ClassID => $ClassesName)
								{
?>
									<option <?php echo ($ClassID == $Clean['ClassID'] ? 'selected="selected"' : ''); ?> value="<?php echo $ClassID; ?>"><?php echo $ClassesName; ?></option>
<?php
								}
?>
                            	</select>
                            </div>
                            <label for="SectionID" class="col-lg-2 control-label">Section List</label>
                            <div class="col-lg-4">
                            	<select class="form-control IssuedToStudent" name="drdClassSectionID" id="ClassSection" <?php echo($Clean['IssuedToType'] == 'Student') ? '' : 'disabled="disabled"'?> >
<?php
									if (is_array($ClassSectionsList) && count($ClassSectionsList) > 0)
									{
										foreach ($ClassSectionsList as $ClassSectionID => $SectionName) 
										{
											echo '<option ' . ($Clean['ClassSectionID'] == $ClassSectionID ? 'selected="selected"' : '') . ' value="' . $ClassSectionID . '">' . $SectionName . '</option>'	;
										}
									}
?>
								</select>
                            </div>
                        </div>
                        <div class="form-group Student" <?php echo($Clean['IssuedToType'] == 'Student') ? '' : 'style="display:none"'?>>
                            <label for="Student" class="col-lg-2 control-label">Student</label>
                            <div class="col-lg-4">
                                <select class="form-control IssuedToStudent" name="drdStudent" id="Student">
<?php
                                if (is_array($StudentsList) && count($StudentsList) > 0)
                                {
                                    foreach ($StudentsList as $StudentID=>$StudentDetails)
                                    {
                                        echo '<option ' . ($Clean['StudentID'] == $StudentID ? 'selected="selected"' : '') . ' value="' . $StudentID . '">' . $StudentDetails['FirstName'] . ' ' . $StudentDetails['LastName'] . ' (' . $StudentDetails['RollNumber'] . ')</option>'; 
                                    }
                                }
?>
                                </select>
                            </div>                                
                        </div>
                        <div class="form-group">
	                        <label for="IssueDate" class="col-lg-2 control-label">Issue Date</label>
                            <div class="col-lg-4">
                            	<input class="form-control dtepicker" type="text" name="txtIssueDate" value="<?php echo $Clean['IssueDate']; ?>"/>	
                            </div>
	                        <label for="ReturnDate" class="col-lg-2 control-label">Return Date</label>
                            <div class="col-lg-4">
                            	<input class="form-control dtepicker" type="text" name="txtReturnDate" value="<?php echo $Clean['ReturnDate']; ?>"/>	
                            </div>
	                    </div>
                        <div class="form-group">
                        <div class="col-sm-offset-2 col-lg-10">
                        	<input type="hidden" name="hdnProcess" value="1" />
							<button type="submit" class="btn btn-primary">Save</button>
                        </div>
                      </div>
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
<script src="/admin/vendor/jquery-ui/jquery-ui.min.js"></script>
<script type="text/javascript">
$(document).ready(function(){

	$(".dtepicker").datepicker({
            changeMonth: true,
            changeYear: true,
            dateFormat: 'dd/mm/yy'
    });

    $('#Department').change(function() 
	{
		var DepartmentID = parseInt($(this).val());
		$('#StockQuantity').html('');

		if (DepartmentID <= 0)
		{
			alert("Please Select Product Category");
			return false;
		}

		$.get("/xhttp_calls/get_products_by_department.php", {SelectedDepartmentID: DepartmentID}, function(data)
		{	
			ResultArray = data.split("|*****|");
	
			if (ResultArray[0] == 'error')
			{
				alert (ResultArray[1]);
				$('#Product').html('<option value="0">Select</option>');
				return false;
			}
			else
			{	
				$('#Product').html(ResultArray[1]);
				GetStockQunatity(parseInt($('#Product').val()), DepartmentID);
			}			
		});
	});

    $('#IssuedToType').change(function()
	{
		if ($(this).val() == 'Staff') 
		{
			$('.Student').hide();
			$('.Staff').show();
			$('.IssuedToStudent').attr('disabled', true);
			$('.IssuedToBranchStaff').attr('disabled', false);
			return true;
		}

		$('.Staff').hide();
		$('.IssuedToBranchStaff').attr('disabled', true);
		$('.IssuedToStudent').attr('disabled', false);
		$('.Student').show();
		return true;
	});

	$('#StaffCategory').change(function()
	{

		StaffCategory = $(this).val();
		
		if (StaffCategory <= 0)
		{
			$('#BranchStaffID').html('<option value="0">Select Section</option>');
			return;
		}
		
		$.post("/xhttp_calls/get_branch_staff_by_staff_category.php", {SelectedStaffCategory:StaffCategory}, function(data)
		{
		
			ResultArray = data.split("|*****|");
			
			if (ResultArray[0] == 'error')
			{
				alert(ResultArray[1]);
				$('#StaffCategory').val(StaffCategoryBeforeChange);
			}
			else
			{
				$('#BranchStaffID').html(ResultArray[1]);
			}
		 });
	});

	$('#Class').change(function(){

        var ClassID = parseInt($(this).val());
        $('#Student').html('<option value="0">Select Student-</option>');
        
        $.post("/xhttp_calls/get_sections_by_classs.php", {SelectedClassID:ClassID}, function(data)
        {
            ResultArray = data.split("|*****|");
            
            if (ResultArray[0] == 'error')
            {
                alert (ResultArray[1]);
                $('#ClassSection').html('<option value="0">Select Section-</option>');
                $('#Student').html('<option value="0">Select Student-</option>');
                return false;
            }
            else
            {
                $('#ClassSection').html(ResultArray[1]);
                GetStudents($('#ClassSection').val());
            }
        });
    });

    $('#ClassSection').change(function(){

        var ClassSectionID = parseInt($(this).val());
       
        $.post("/xhttp_calls/get_students_by_class_section.php", {SelectedClassSectionID:ClassSectionID}, function(data)
        {
            ResultArray = data.split("|*****|");
            
            if (ResultArray[0] == 'error')
            {
                alert (ResultArray[1]);
                $('#Student').html('<option value="0">Select Student-</option>');
                return false;
            }
            else
            {
                $('#Student').html(ResultArray[1]);
            }
        });
    });

    $('#Product').change(function()
	{	
		var Product = parseInt($('#Product').val())		
		var Department = parseInt($('#Department').val())		

		if (Product <= 0 || Department <= 0) 
		{
			allert('Unknown error, please try agin');
			return false;
		}

		GetStockQunatity(Product);
	});
});

function GetStudents(SelectedClassSectionID)
{
	var ClassSectionID = parseInt(SelectedClassSectionID);
	$.post("/xhttp_calls/get_students_by_class_section.php", {SelectedClassSectionID:ClassSectionID}, function(data)
    {
        ResultArray = data.split("|*****|");
        
        if (ResultArray[0] == 'error')
        {
            alert (ResultArray[1]);
            return false;
        }
        else
        {
            $('#Student').html(ResultArray[1]);
        }
    });
}

function GetStockQunatity(Product)
{
	$.post("/xhttp_calls/get_product_stock_quantity_by_productAndDepartment.php", {SelectedProduct:Product}, function(data)
	{
	
		ResultArray = data.split("|*****|");
		
		if (ResultArray[0] == 'error')
		{
			alert(ResultArray[1]);
		}
		else
		{		
			$('#StockQuantity').html(ResultArray[1]);
		}
	});
}
</script>
</body>
</html>
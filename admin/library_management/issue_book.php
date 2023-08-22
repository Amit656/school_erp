<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");
require_once("../../classes/class.date_processing.php");

require_once("../../classes/school_administration/class.classes.php");
require_once('../../classes/school_administration/class.class_sections.php');
require_once('../../classes/school_administration/class.section_master.php');

require_once('../../classes/school_administration/class.students.php');
require_once('../../classes/school_administration/class.student_details.php');
require_once("../../classes/school_administration/class.branch_staff.php");

require_once("../../classes/library_management/class.books.php");
require_once("../../classes/library_management/class.book_categories.php");
require_once("../../classes/library_management/class.books_issue_conditions.php");
require_once("../../classes/library_management/class.book_issue.php");

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
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}
catch (Exception $e)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}
// END OF 1. //

if ($LoggedUser->HasPermissionForTask(TASK_ISSUE_BOOK) !== true)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}

$HasErrors = false;

$ParentCategoryList = array();
$ParentCategoryList = BookCategory::GetActiveParentCategories();

$BookCategoryList = array();

$IssuedToUserTypeList = array();

$ClassList =  array();
$ClassList = AddedClass::GetActiveClasses();

$AllSectionMasters = array();
$AllSectionMasters = SectionMaster::GetActiveSectionMasters();

$ClassSectionsList =  array();
$StudentsList = array();
$AllBranchStaffList = array();

$Clean = array();
$Clean['Process'] = 0;

$Clean['ParentCategoryID'] = 0;
$Clean['BookCategoryID'] = 0;

$Clean['BookID'] = 0;
$Clean['BooksCopyID'] = 0;

$Clean['IssuedToUserType'] = '';
$Clean['IssuedToID'] = 0;

$Clean['ClassID'] = 0;
$Clean['ClassSectionID'] = 0;
$Clean['StudentID'] = 0;

$Clean['BranchStaffID'] = 0;

$Clean['IssueDate'] = date('d/m/Y');
$Clean['ExpectedReturnDate'] = '';

if (isset($_POST['hdnProcess']))
{
	$Clean['Process'] = (int) $_POST['hdnProcess'];
}
switch ($Clean['Process'])
{
	case 1:
		if (isset($_POST['drdParentCategory']))
		{
			$Clean['ParentCategoryID'] = (int) $_POST['drdParentCategory'];
		}
		if (isset($_POST['drdBookCategory']))
		{
			$Clean['BookCategoryID'] = (int) $_POST['drdBookCategory'];
		}
		if (isset($_POST['drdBook']))
		{
			$Clean['BookID'] = (int) $_POST['drdBook'];
		}
		if (isset($_POST['drdBooksCopy']))
		{
			$Clean['BooksCopyID'] = (int) $_POST['drdBooksCopy'];
		}
		if (isset($_POST['drdIssuedToUserType']))
		{
			$Clean['IssuedToUserType'] = strip_tags(trim($_POST['drdIssuedToUserType']));
		}

		if (isset($_POST['drdClass']))
		{
			$Clean['ClassID'] = (int) $_POST['drdClass'];
		}
		if (isset($_POST['drdClassSection']))
		{
			$Clean['ClassSectionID'] = (int) $_POST['drdClassSection'];
		}
		if (isset($_POST['drdStudent']))
		{
			$Clean['StudentID'] = (int) $_POST['drdStudent'];
		}
		if (isset($_POST['drdBranchStaff']))
		{
			$Clean['BranchStaffID'] = (int) $_POST['drdBranchStaff'];
		}

		if (isset($_POST['txtIssueDate']))
		{
			$Clean['IssueDate'] = strip_tags(trim($_POST['txtIssueDate']));
		}
		if (isset($_POST['txtExpectedReturnDate']))
		{
			$Clean['ExpectedReturnDate'] = strip_tags(trim($_POST['txtExpectedReturnDate']));
		}
		
		$NewRecordValidator = new Validator();

		if ($NewRecordValidator->ValidateInSelect($Clean['ParentCategoryID'], $ParentCategoryList, 'Please select valid category.')) 
		{
			$BookCategoryList = BookCategory::GetActiveSubCategories($Clean['ParentCategoryID']);

			if ($NewRecordValidator->ValidateInSelect($Clean['BookCategoryID'], $BookCategoryList, 'Please select valid sub category.')) 
			{
				$BookList = Book::GetBooksByCategory($Clean['BookCategoryID']);	

				if ($NewRecordValidator->ValidateInSelect($Clean['BookID'], $BookList, 'Please select valid book.')) 
				{
					$BooksCopyList = Book::GetBookCopiesByBook($Clean['BookID']);
					$NewRecordValidator->ValidateInSelect($Clean['BooksCopyID'], $BooksCopyList, 'Please select a book copy.');

					$BookIssuableTo = Book::GetBookIssuableToByBook($Clean['BookID']);

					$BookFor = key($BookIssuableTo);
					if ($BookFor == 'All') 
					{
						$IssuedToUserTypeList = array('Student' => 'Student', 'Teaching' => 'Teaching', 'NonTeaching' => 'Non-Teaching');
					}
					else if ($BookFor == 'StudentTeaching') 
					{
						$IssuedToUserTypeList = array('Student' => 'Student', 'Teaching' => 'Teaching');
					}
					else if ($BookFor == 'AllStaff') 
					{
						$IssuedToUserTypeList = array('Teaching' => 'Teaching', 'NonTeaching' => 'Non-Teaching');
					}
					else
					{
						$IssuedToUserTypeList = array($BookFor => $BookFor);
					}
					
					if ($NewRecordValidator->ValidateInSelect($Clean['IssuedToUserType'], $IssuedToUserTypeList, 'Unknown error, please try again.')) 
					{
						if ($Clean['IssuedToUserType'] == 'Student') 
						{
							if ($NewRecordValidator->ValidateInSelect($Clean['ClassID'], $ClassList, 'Please select a valid class.')) 
							{
								$ClassSectionsList = ClassSections::GetClassSections($Clean['ClassID']);

								if ($NewRecordValidator->ValidateInSelect($Clean['ClassSectionID'], $ClassSectionsList, 'Please select a valid section.')) 
								{
									$StudentsList = StudentDetail::GetStudentsByClassSectionID($Clean['ClassSectionID']);

									if ($NewRecordValidator->ValidateInSelect($Clean['StudentID'], $StudentsList, 'Please select a valid student.')) 
									{
										$Clean['IssuedToID'] = $Clean['StudentID'];
									}
								}
							}
						}
						else
						{
							$AllBranchStaffList = BranchStaff::GetActiveBranchStaff($Clean['IssuedToUserType']);
							
							if ($NewRecordValidator->ValidateInSelect($Clean['BranchStaffID'], $AllBranchStaffList, 'Please select a valid staff.')) 
							{
								$Clean['IssuedToID'] = $Clean['BranchStaffID'];
							}
						}
						$BooksIssueConditions = BooksIssueCondition::GetBooksIssueConditions($Clean['IssuedToUserType']);
						$AllreadyIssuedBooksAtPresentTime = BookIssue::GetAllreadyIssuedBooksToUser($Clean['IssuedToUserType'], $Clean['BranchStaffID']);
						if ((key($BooksIssueConditions) - $AllreadyIssuedBooksAtPresentTime) <= 0) 
						{
							$NewRecordValidator->AttachTextError('Book issue quota is full for this user.');						
						}	
					}									
				}
			}			
		}

		$NewRecordValidator->ValidateDate($Clean['IssueDate'], 'Please enter valid issue date.');
		$NewRecordValidator->ValidateDate($Clean['ExpectedReturnDate'], 'Please enter valid return date.');		
		
		if ($NewRecordValidator->HasNotifications())
		{	
			$HasErrors = true;
			break;
		}

		$NewBookIssue = new BookIssue();
				
		$NewBookIssue->SetBooksCopyID($Clean['BooksCopyID']);

		$NewBookIssue->SetIssuedToUserType($Clean['IssuedToUserType']);
		$NewBookIssue->SetIssuedToID($Clean['IssuedToID']);				
		$NewBookIssue->SetIssueDate(date('Y-m-d', strtotime(DateProcessing::ToggleDateDayAndMonth(($Clean['IssueDate'])))));
		$NewBookIssue->SetExpectedReturnDate(date('Y-m-d', strtotime(DateProcessing::ToggleDateDayAndMonth(($Clean['ExpectedReturnDate'])))));
			
		$NewBookIssue->SetCreateUserID($LoggedUser->GetUserID());
	
		if (!$NewBookIssue->Save())
		{
			$NewRecordValidator->AttachTextError(ProcessErrors($NewBookIssue->GetLastErrorCode()));
			$HasErrors = true;
			break;
		}
		
		header('location:issued_book_report.php?Mode=AS');
		exit;
	break;
}

require_once('../html_header.php');
?>
<title>Issue Book</title>
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
                    <h1 class="page-header">Issue Book</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="IssueBook" action="issue_book.php" method="post">
            	<div class="panel panel-default">
                    <div class="panel-heading">
                        <strong>Enter Book Issue Details</strong>                        
                    </div>
                    <div class="panel-body">
<?php
						if ($HasErrors == true)
						{
							echo $NewRecordValidator->DisplayErrors();
						}
?>                    
						<div class="form-group">
							<label for="ParentCategory" class="col-lg-2 control-label">Category</label>
                            <div class="col-lg-3">
                            	<select class="form-control" name="drdParentCategory" id="ParentCategory">
                            		<option value="0">-- Select Category --</option>
<?php
								if (is_array($ParentCategoryList) && count($ParentCategoryList) > 0)
								{
									foreach($ParentCategoryList as $ParentCategoryID => $ParentCategoryName)
									{
										echo '<option ' . (($Clean['ParentCategoryID'] == $ParentCategoryID) ? 'selected="selected"' : '' ) . ' value="' . $ParentCategoryID . '">' . $ParentCategoryName . '</option>';
									}
								}
?>
								</select>
                            </div>
                            <label for="BookCategory" class="col-lg-2 control-label">Sub Category</label>
                            <div class="col-lg-3">
                            	<select class="form-control" name="drdBookCategory" id="BookCategory">
                            		<option value="">-- Select Sub Category --</option>
<?php
								if (is_array($BookCategoryList) && count($BookCategoryList) > 0)
								{
									foreach($BookCategoryList as $BookCategoryID => $BookCategoryName)
									{
										echo '<option ' . (($Clean['BookCategoryID'] == $BookCategoryID) ? 'selected="selected"' : '' ) . ' value="' . $BookCategoryID . '">' . $BookCategoryName . '</option>';
									}
								}
?>
								</select>
                            </div>
                        </div>
						<div class="form-group">
							<label for="Book" class="col-lg-2 control-label">Book Name</label>
                            <div class="col-lg-8">
                            	<select class="form-control" name="drdBook" id="Book">
                            		<option value="">-- Select Book --</option>
<?php
								if (is_array($BookList) && count($BookList) > 0)
								{
									foreach($BookList as $BookID => $BookName)
									{
										echo '<option ' . (($Clean['BookID'] == $BookID) ? 'selected="selected"' : '' ) . ' value="' . $BookID . '">' . $BookName . '</option>';
									}
								}
?>
								</select>
                            </div>							                    
                        </div>
                        
                        <div class="form-group">
                        	<label for="BooksCopy" class="col-lg-2 control-label">Book Copy</label>
                            <div class="col-lg-3">
                            	<select class="form-control" name="drdBooksCopy" id="BooksCopy">
                            		<option value="">-- Select Book Copy --</option>
<?php
								if (is_array($BooksCopyList) && count($BooksCopyList) > 0)
								{
									foreach($BooksCopyList as $BooksCopyID => $BooksCopyName)
									{
										echo '<option ' . (($Clean['BooksCopyID'] == $BooksCopyID) ? 'selected="selected"' : '' ) . ' value="' . $BooksCopyID . '"> Copy ' . $BooksCopyName . '</option>';
									}
								}
?>
								</select>
                            </div>       
							<label for="IssuedToUserType" class="col-lg-2 control-label">Issue To</label>
                            <div class="col-lg-3">                            	
                            	<select class="form-control" name="drdIssuedToUserType" id="IssuedToUserType">  
                            		<option value="">-- Select User --</option>                          		
<?php
								if (is_array($IssuedToUserTypeList) && count($IssuedToUserTypeList) > 0)
								{
									foreach($IssuedToUserTypeList as $IssuedToUserTypeID => $IssuedToUserTypeName)
									{
										echo '<option ' . (($Clean['IssuedToUserType'] == $IssuedToUserTypeID) ? 'selected="selected"' : '' ) . ' value="' . $IssuedToUserTypeID . '">' . $IssuedToUserTypeName . '</option>';
									}
								}
?>
								</select>
                            </div>                            
                        </div>
                        <div id="StudentDetail" <?php echo (($Clean['IssuedToUserType'] == 'Student') ? '' : ' class="collapse"'); ?>>
                        	<div class="form-group">
	                            <label for="Class" class="col-lg-2 control-label">Class List</label>
	                            <div class="col-lg-3">
	                                <select class="form-control"  name="drdClass" id="Class">
	                                    <option  value="0" >-- Select Class --</option>
	<?php
	                                    if (is_array($ClassList) && count($ClassList) > 0)
	                                    {
	                                        foreach ($ClassList as $ClassID => $ClassName)
		                                    {
		?>
		                                        <option <?php echo ($ClassID == $Clean['ClassID'] ? 'selected="selected"' : ''); ?> value="<?php echo $ClassID; ?>"><?php echo $ClassName; ?></option>
		<?php
		                                    }
	                                    }
	?>
	                                </select>
	                            </div>
	                            <label for="ClassSection" class="col-lg-2 control-label">Section</label>
	                            <div class="col-lg-3">
	                                <select class="form-control" name="drdClassSection" id="ClassSection">
	                                    <option value="0">-- Select Section --</option>
	<?php
	                                        if (is_array($ClassSectionsList) && count($ClassSectionsList) > 0)
	                                        {
	                                            foreach ($ClassSectionsList as $ClassSectionID => $SectionMasterID) 
	                                            {
	                                                echo '<option ' . ($Clean['ClassSectionID'] == $ClassSectionID ? 'selected="selected"' : '') . ' value="' . $ClassSectionID . '">' . $AllSectionMasters[$SectionMasterID] . '</option>' ;
	                                            }
	                                        }
	?>
	                                </select>
	                            </div>
	                        </div>
	                        <div class="form-group">
	                            <label for="Student" class="col-lg-2 control-label">Student</label>
	                            <div class="col-lg-8">
	                                <select class="form-control" name="drdStudent" id="Student" onchange="GetBookQuotaAndIssueDetails(this.value)">
	                                    <option value="0">-- Select Student --</option>
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
                        </div>
                        <div id="Staff" <?php echo (($Clean['IssuedToUserType'] == 'Teaching' || $Clean['IssuedToUserType'] == 'NonTeaching') ? '' : ' class="collapse"'); ?>>
                        	<div class="form-group">                    	
		                        <label for="BranchStaff" class="col-lg-2 control-label">Branch Staff</label>
		                        <div class="col-lg-8">
		                            <select class="form-control IssuedToBranchStaff"  name="drdBranchStaff" id="BranchStaff" onchange="GetBookQuotaAndIssueDetails(this.value)">
		                            	<option value="0">-- Select Staff --</option>
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
	                    </div>                        
                        <div class="form-group">
                            <label for="IssueDate" class="col-lg-2 control-label">Issue Date</label>
                            <div class="col-lg-3"> 
                            	<input class="form-control select-date" type="text" maxlength="10" id="IssueDate" name="txtIssueDate" value="<?php echo $Clean['IssueDate']; ?>" />
                            </div>
                            <label for="ExpectedReturnDate" class="col-lg-2 control-label">Return Date</label>
                            <div class="col-lg-3"> 
                            	<input class="form-control select-date" type="text" maxlength="10" id="ExpectedReturnDate" name="txtExpectedReturnDate" value="<?php echo $Clean['ExpectedReturnDate']; ?>" />
                            </div>
                        </div>
                        <div class="form-group">
                        	<div class="col-sm-offset-2 col-lg-5 text-danger <?php echo (($Clean['IssuedToID'] <= 0) ? 'collapse' : ''); ?>" id="QuotaDetails">
                        		<strong>Issued Books:&nbsp;&nbsp;<span id="IssuedBooks"></span></strong><br>
                        		<strong>Available Quota:&nbsp;&nbsp;<span id="AvailableQuota"></span></strong>
                        	</div>
                        </div>
	                    <div class="form-group">
			                <div class="col-sm-offset-2 col-lg-10">
			                	<input type="hidden" name="hdnProcess" value="1" />
								<button type="submit" class="btn btn-primary"><i class="fa fa-save"></i>&nbsp;Save</button>
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
<script src="../vendor/jquery-ui/jquery-ui.min.js"></script>

<script type="text/javascript">
$(document).ready(function(){ 

	$(".select-date").datepicker({
		changeMonth: true,
		changeYear: true,
		dateFormat: 'dd/mm/yy'
	});

	$('#ParentCategory').change(function(){

        var ParentCategoryID = parseInt($(this).val());
        
        if (ParentCategoryID <= 0)
        {
            $('#BookCategory').html('<option value="0">-- Select Sub Category --</option>');
            return false;
        }
        
        $.post("/xhttp_calls/get_sub_category_by_parent_category.php", {SelectedParentCategoryID:ParentCategoryID}, function(data)
        {
            ResultArray = data.split("|*****|");
            
            if (ResultArray[0] == 'error')
            {
                alert (ResultArray[1]);
                return false;
            }
            else
            {
                $('#BookCategory').html('<option value="">-- Select Sub Category --</option>' + ResultArray[1]);
            }
        });
    });

    $('#BookCategory').change(function(){

        var BookCategoryID = parseInt($(this).val());
        
        if (BookCategoryID <= 0)
        {
            $('#Book').html('<option value="0">-- Select Book --</option>');
            return false;
        }
        
        $.post("/xhttp_calls/get_books_by_book_category.php", {SelectedBookCategoryID:BookCategoryID}, function(data)
        {
            ResultArray = data.split("|*****|");
            
            if (ResultArray[0] == 'error')
            {
                alert (ResultArray[1]);
                $('#Book').html('<option value="0">-- Select Book --</option>');
                return false;
            }
            else
            {
                $('#Book').html('<option value="">-- Select Book --</option>' + ResultArray[1]);
            }
        });
    });

    $('#Book').change(function(){

        var BookID = parseInt($(this).val());
        
        if (BookID <= 0)
        {
            $('#BooksCopy').html('<option value="0">-- Select Book --</option>');
            $('#IssuedToUserType').html('<option value="0">-- Select User --</option>');
            $('#Class').html('<option value="0">-- Select Class --</option>');
            return false;
        }
        
        $.post("/xhttp_calls/get_book_copies_by_book.php", {SelectedBookID:BookID}, function(data)
        {
            ResultArray = data.split("|*****|");
            
            if (ResultArray[0] == 'error')
            {
                alert (ResultArray[1]);
                $('#BooksCopy').html('<option value="0">-- Select Book --</option>');
            	$('#IssuedToUserType').html('<option value="0">-- Select User --</option>');
            	$('#Class').html('<option value="0">-- Select Class --</option>');
                return false;
            }
            else
            {
                $('#BooksCopy').html(ResultArray[1]);
                $('#IssuedToUserType').html('<option value="0">-- Select User --</option>' + ResultArray[2]);
                $('#Class').html('<option value="0">-- Select Class --</option>' + ResultArray[3]);
            }
        });
    });

    $('#IssuedToUserType').change(function(){

    	var IssuedToUserType = $(this).val();
    	
    	if (IssuedToUserType == 0) 
    	{
    		$('#StudentDetail').slideUp();
    		$('#Staff').slideUp();
    		return false;
    	}

    	if (IssuedToUserType == 'Student') 
    	{
    		$('#Staff').slideUp();
    		$('#StudentDetail').slideDown();    
    		$('#QuotaDetails').slideUp();		
    	}
    	else
    	{
    		$('#StudentDetail').slideUp();
    		$('#Staff').slideDown();
    		$('#QuotaDetails').slideUp();

    		$.post("/xhttp_calls/get_branch_staff_by_staff_category.php", {SelectedStaffCategory:IssuedToUserType}, function(data)
			{
			
				ResultArray = data.split("|*****|");
				
				if (ResultArray[0] == 'error')
				{
					alert(ResultArray[1]);
					$('#BranchStaff').html('<option value="0">-- Select staff --</option>');
				}
				else
				{
					$('#BranchStaff').html('<option value="0">-- Select staff --</option>' + ResultArray[1]);
				}
			});
    	}

    });

    $('#Class').change(function(){

        var ClassID = parseInt($(this).val());
        $('#Student').html('<option value="0">-- Select Student --</option>');
        
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

    $('#ClassSection').change(function(){

        var ClassSectionID = parseInt($(this).val());
        
        if (ClassSectionID <= 0)
        {
            $('#Student').html('<option value="0">-- Select Student --</option>');
            return;
        }
        
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
                $('#Student').html('<option value="0">-- Select Student --</option>' + ResultArray[1]);
            }
        });
    });
});

function GetBookQuotaAndIssueDetails(IssuedToID)
{
	var IssuedToUserType = $('#IssuedToUserType').val();
	var IssueDate = $('#IssueDate').datepicker('getDate');
	var date = new Date(Date.parse(IssueDate));

	$.post("/xhttp_calls/get_books_issue_conditions_by_user_type.php", {SelectedUserType:IssuedToUserType, SelectedIssuedToID:IssuedToID}, function(data)
	{

		ResultArray = data.split("|*****|");
		
		if (ResultArray[0] == 'error')
		{
			$('#QuotaDetails').slideUp();
			alert(ResultArray[1]);				
		}
		else
		{			
			date.setDate(date.getDate() + parseInt(ResultArray[1]-1));		
			ExpectedReturnDate = new Date(Date.parse(date.toDateString()));
			$('#ExpectedReturnDate').datepicker('setDate', ExpectedReturnDate);

			$('#QuotaDetails').show();			
			$('#IssuedBooks').text(ResultArray[2]);
			$('#AvailableQuota').text(ResultArray[3]);
		}
	});
}

</script>
</body>
</html>
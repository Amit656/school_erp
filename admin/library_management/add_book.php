<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");
require_once("../../classes/class.date_processing.php");

require_once("../../classes/school_administration/class.classes.php");

require_once("../../classes/library_management/class.books.php");
require_once("../../classes/library_management/class.book_categories.php");

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

if ($LoggedUser->HasPermissionForTask(TASK_ADD_BOOK) !== true)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}

$HasErrors = false;

$ParentCategoryList = array();
$ParentCategoryList = BookCategory::GetActiveParentCategories();

$BookCategoryList = array();

$BookTypeList = array('Academic' => 'Academic', 'NonAcademic' => 'Non-Academic');
$IssuableToList = array('All' => 'All', 'Student' => 'Student', 'Teaching' => 'Teaching', 'NonTeaching' => 'Non-Teaching', 'AllStaff' => 'All Staff', 'StudentTeaching' => 'Student & Teaching');

$ClassList =  array();
$ClassList = AddedClass::GetActiveClasses();

$Clean = array();
$Clean['Process'] = 0;

$Clean['ParentCategoryID'] = 0;
$Clean['BookCategoryID'] = 0;

$Clean['BookName'] = '';
$Clean['AuthorNameList'] = array(1 => '');
$Clean['AuthorIDList'] = array();
$Clean['ISBN'] = '';
$Clean['BookType'] = 'NonAcademic';
$Clean['IssuableTo'] = 'All';
$Clean['TakeHomeAllowed'] = 0;
$Clean['ClassIDList'] = array();
$Clean['Volume'] = '';
$Clean['NumberOfPages'] = 0;

$Clean['Price'] = 0;
$Clean['PurchaseDate'] = '';
$Clean['Quantity'] = 0;
$Clean['ShelfNumber'] = '';

$Clean['PublishedYear'] = '';
$Clean['Edition'] = '';
$Clean['PublisherID'] = 0;
$Clean['PublisherName'] = '';

$AuthorDetails = array();
$Errors = array();
$AllReadyExistAuthor = array();

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
		if (isset($_POST['txtBookName']))
		{
			$Clean['BookName'] = strip_tags(trim($_POST['txtBookName']));
		}
		if (isset($_POST['txtAuthorName']) && is_array($_POST['txtAuthorName']))
		{
			$Clean['AuthorNameList'] = $_POST['txtAuthorName'];
		}
		if (isset($_POST['hdnAuthorID']) && is_array($_POST['hdnAuthorID']))
		{
			$Clean['AuthorIDList'] = $_POST['hdnAuthorID'];
		}
		if (isset($_POST['txtISBN']))
		{
			$Clean['ISBN'] = strip_tags(trim($_POST['txtISBN']));
		}
		if (isset($_POST['optBookType']))
		{
			$Clean['BookType'] = strip_tags(trim($_POST['optBookType']));
		}
		if (isset($_POST['drdIssuableTo']))
		{
			$Clean['IssuableTo'] = strip_tags(trim($_POST['drdIssuableTo']));
		}
		if (isset($_POST['chkTakeHomeAllowed']))
		{
			$Clean['TakeHomeAllowed'] = 1;
		}
		if (isset($_POST['drdClass']) && is_array($_POST['drdClass']))
		{
			$Clean['ClassIDList'] = $_POST['drdClass'];
		}
		if (isset($_POST['txtVolume']))
		{
			$Clean['Volume'] = strip_tags(trim($_POST['txtVolume']));
		}
		if (isset($_POST['txtNumberOfPages']))
		{
			$Clean['NumberOfPages'] = strip_tags(trim($_POST['txtNumberOfPages']));
		}
		if (isset($_POST['txtPrice']))
		{
			$Clean['Price'] = strip_tags(trim($_POST['txtPrice']));
		}
		if (isset($_POST['txtPurchaseDate']))
		{
			$Clean['PurchaseDate'] = strip_tags(trim($_POST['txtPurchaseDate']));
		}
		if (isset($_POST['txtQuantity']))
		{
			$Clean['Quantity'] = strip_tags(trim($_POST['txtQuantity']));
		}
		if (isset($_POST['txtShelfNumber']))
		{
			$Clean['ShelfNumber'] = strip_tags(trim($_POST['txtShelfNumber']));
		}

		if (isset($_POST['txtPublishedYear']))
		{
			$Clean['PublishedYear'] = strip_tags(trim($_POST['txtPublishedYear']));
		}
		if (isset($_POST['txtEdition']))
		{
			$Clean['Edition'] = strip_tags(trim($_POST['txtEdition']));
		}
		if (isset($_POST['txtPublisherName']))
		{
			$Clean['PublisherName'] = strip_tags(trim($_POST['txtPublisherName']));
		}
		if (isset($_POST['txtPublisherID']))
		{
			$Clean['PublisherID'] = strip_tags(trim($_POST['txtPublisherID']));
		}

		$NewRecordValidator = new Validator();

		if ($NewRecordValidator->ValidateInSelect($Clean['ParentCategoryID'], $ParentCategoryList, 'Please select valid category.')) 
		{
			$BookCategoryList = BookCategory::GetActiveSubCategories($Clean['ParentCategoryID']);
			
			if ($Clean['BookCategoryID'] != 0) 
			{
				$NewRecordValidator->ValidateInSelect($Clean['BookCategoryID'], $BookCategoryList, 'Please select valid sub category.');	
			}	
		}

		$NewRecordValidator->ValidateInSelect($Clean['IssuableTo'], $IssuableToList, 'Unknown error, please try again.');	
		$NewRecordValidator->ValidateStrings($Clean['BookName'], 'Book name is required and should be between 3 and 150 characters.', 3, 150);

		foreach ($Clean['AuthorNameList'] as $Counter => $AuthorName) 
		{
			if ($AuthorName == '') 
			{
				$Errors[$Counter] = 'Error';
			}
			
			if (in_array($AuthorName, array_column($AuthorDetails, 'AuthorName'))) 
			{
				$AllReadyExistAuthor[$Counter] = 'The author you enter, is allready in list.';
			}

			if ($Clean['AuthorIDList'][$Counter] <= 0) 
			{
				$AuthorDetails[$Counter]['AuthorID'] = 0;
				$AuthorDetails[$Counter]['AuthorName'] = $AuthorName;
			}
			else
			{
				$AuthorDetails[$Counter]['AuthorID'] = $Clean['AuthorIDList'][$Counter];
				$AuthorDetails[$Counter]['AuthorName'] = $AuthorName;
			}
		}

		if (count($Errors) > 0) 
		{
			$NewRecordValidator->AttachTextError('Please enter author name in all available fields.');
		}
		if (count($AllReadyExistAuthor) > 0) 
		{
			$NewRecordValidator->AttachTextError('Please enter different author names.');
		}

		$NewRecordValidator->ValidateStrings($Clean['ISBN'], 'ISBN number is required and should be between 5 and 100 characters.', 5, 100);
		$NewRecordValidator->ValidateInSelect($Clean['BookType'], $BookTypeList, 'Please select valid book type.');

		if ($Clean['BookType'] == 'Academic') 
		{			
			if (count($Clean['ClassIDList']) > 0) 
			{
				foreach ($Clean['ClassIDList'] as $key => $ClassID) 
				{
					$NewRecordValidator->ValidateInSelect($ClassID, $ClassList, 'Please select valid class.');
				}				
			}
			else
			{
				$NewRecordValidator->AttachTextError('Please select atleast one class.');
			}
			
		}
		
		$NewRecordValidator->ValidateStrings($Clean['Volume'], 'Volume is required and should be between 1 and 25 characters.', 1, 25);
		$NewRecordValidator->ValidateInteger($Clean['NumberOfPages'], 'Please enter numeric value for number of pages.', 1);
		$NewRecordValidator->ValidateNumeric($Clean['Price'], 'Please enter numeric value for price.');
		$NewRecordValidator->ValidateDate($Clean['PurchaseDate'], 'Please enter valid purchase date.');
		$NewRecordValidator->ValidateInteger($Clean['Quantity'], 'Please enter numeric value for quantity of books.', 1);
		$NewRecordValidator->ValidateStrings($Clean['ShelfNumber'], 'Shelf number is required and should be between 1 and 50 characters.', 1, 50);
		$NewRecordValidator->ValidateInteger($Clean['PublishedYear'], 'Please enter numeric value for published year.', 1);
		$NewRecordValidator->ValidateStrings($Clean['Edition'], 'Book edition is required and should be between 1 and 100 characters.', 1, 100);

		if ($Clean['PublisherName'] == '' && $Clean['PublisherID'] <= 0) 
		{
			$NewRecordValidator->AttachTextError('Please enter publisher name.');
		}

		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}
				
		$NewBook = new Book();
				
		$NewBook->SetBookCategoryID($Clean['BookCategoryID']);

		$NewBook->SetISBN($Clean['ISBN']);
		$NewBook->SetBookName($Clean['BookName']);
		$NewBook->SetBookType($Clean['BookType']);
		$NewBook->SetIssuableTo($Clean['IssuableTo']);
		$NewBook->SetTakeHomeAllowed($Clean['TakeHomeAllowed']);		
		$NewBook->SetVolume($Clean['Volume']);
		$NewBook->SetNumberOfPages($Clean['NumberOfPages']); 

		$NewBook->SetPrice($Clean['Price']);
		$NewBook->SetPurchaseDate(date('Y-m-d', strtotime(DateProcessing::ToggleDateDayAndMonth(($Clean['PurchaseDate'])))));
		$NewBook->SetQuantity($Clean['Quantity']);
		$NewBook->SetShelfNumber($Clean['ShelfNumber']);

		$NewBook->SetPublishedYear($Clean['PublishedYear']);
		$NewBook->SetEdition($Clean['Edition']);

		$NewBook->SetIsActive(1);
		$NewBook->SetCreateUserID($LoggedUser->GetUserID());

		$NewBook->SetClassList($Clean['ClassIDList']);
		$NewBook->SetAuthorDetails($AuthorDetails);

		if ($Clean['PublisherID'] <= 0) 
		{
			$NewBook->SetPublisherName($Clean['PublisherName']);
		}
		else
		{
			$NewBook->SetPublisherID($Clean['PublisherID']);
		}

		if (!$NewBook->Save())
		{
			$NewRecordValidator->AttachTextError(ProcessErrors($NewBook->GetLastErrorCode()));
			$HasErrors = true;
			break;
		}
		
		header('location:book_report.php?Mode=AS');
		exit;
	break;
}

require_once('../html_header.php');
?>
<title>Add Book</title>
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
                    <h1 class="page-header">Add Book</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="AddBook" action="add_book.php" method="post">
            	<div class="panel panel-default">
                    <div class="panel-heading">
                        <strong>Enter Book Details</strong>
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
                            <label for="BookName" class="col-lg-2 control-label">Book Name</label>
                            <div class="col-lg-8">
                            	<input class="form-control" type="text" maxlength="150" id="BookName" name="txtBookName" value="<?php echo $Clean['BookName']; ?>" />
                            </div>
                        </div>
                        <div id="AuthorNameContainer">
<?php 
						foreach ($Clean['AuthorNameList'] as $Counter => $AuthorName) 
						{
?>
							<div class="form-group AuthorNameContainer">
	                            <label for="AuthorName<?php echo $Counter; ?>" class="col-lg-2 control-label">Author Name</label>
	                            <div class="col-lg-8<?php echo isset($Errors[$Counter]) ? ' has-error' : ''; ?>">
	                            	<input class="form-control SearchAuthor" list="AuthorNameList<?php echo $Counter; ?>" maxlength="100" id="AuthorName<?php echo $Counter; ?>" name="txtAuthorName[<?php echo $Counter; ?>]" placeholder="Start typing...." value="<?php echo $AuthorName ; ?>" />
	                                <datalist id="AuthorNameList<?php echo $Counter; ?>">
	                                </datalist>
	                                <?php echo isset($AllReadyExistAuthor[$Counter]) ? '<small class="error text-danger">( Author name allready entered by you.)</small>' : ''; ?> 
	                                <input type="hidden" name="hdnAuthorID[<?php echo $Counter; ?>]" id="AuthorID<?php echo $Counter; ?>" value="<?php echo (array_key_exists($Counter, $Clean['AuthorIDList']) ? $Clean['AuthorIDList'][$Counter] : ''); ?>">
	                            </div>
<?php
							if ($Counter == 1) 
							{
?>
								<div class="col-lg-2">
		                       		<button type="button" class="btn btn-primary" id="AddMore">Add More&nbsp;<i class="fa fa-plus"></i></button>
		                    	</div>
<?php								
							}
							else
							{
?>
								<div class="col-lg-2">
		                       		<button type="button" class="btn btn-sm btn-danger RemovePeriodDetailContainer">Remove</button>
		                    	</div>
<?php								
							}
?>	                            
	                        </div>
<?php
						}
?>
                        </div>
                        <div class="form-group">
                            <label for="ISBN" class="col-lg-2 control-label">ISBN</label>
                            <div class="col-lg-8">
                            	<input class="form-control" type="text" maxlength="100" id="ISBN" name="txtISBN" value="<?php echo $Clean['ISBN']; ?>" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="BookType" class="col-lg-2 control-label">Book Type</label>
                            <div class="col-sm-4">
<?php
                            foreach($BookTypeList as $BookTypeID => $BookTypeName)
                            {
?>                              
                                <label class="col-sm-6"><input class="custom-radio" type="radio" id="<?php echo $BookTypeID; ?>" name="optBookType" value="<?php echo $BookTypeID; ?>" <?php echo ($Clean['BookType'] == $BookTypeID ? 'checked="checked"' : ''); ?> >&nbsp;&nbsp;<?php echo $BookTypeName; ?></label>            
<?php                                       
                            }
?>
                            </div>
                            <label for="Class" class="col-lg-1 control-label">Class</label>
                            <div class="col-lg-3">
                            	<select class="form-control" name="drdClass[]" id="Class" multiple="multiple">									
<?php 
                                if (is_array($ClassList) && count($ClassList) > 0)
                                {
                                    foreach ($ClassList as $ClassID => $ClassName)
                                    {
                                        echo '<option ' . (in_array($ClassID, $Clean['ClassIDList']) ? 'selected="selected"' : '') . ' value="' . $ClassID . '">' . $ClassName . '</option>';
                                    }
                                }
?>
								</select>
                            </div>
                        </div>
                        <div class="form-group">
							<label for="IssuableTo" class="col-lg-2 control-label">Issue To</label>
                            <div class="col-lg-3">
                            	<select class="form-control" name="drdIssuableTo" id="IssuableTo">                            		
<?php
								if (is_array($IssuableToList) && count($IssuableToList) > 0)
								{
									foreach($IssuableToList as $IssuableToID => $IssuableToName)
									{
										echo '<option ' . (($Clean['IssuableToID'] == $IssuableToID) ? 'selected="selected"' : '' ) . ' value="' . $IssuableToID . '">' . $IssuableToName . '</option>';
									}
								}
?>
								</select>
                            </div>
                            <label for="TakeHomeAllowed" class="col-lg-2 control-label">Allowed For Home</label>
                            <div class="col-lg-4">
                                <input type="checkbox" id="TakeHomeAllowed" name="chkTakeHomeAllowed" <?php echo ($Clean['TakeHomeAllowed'] == 1) ? 'checked="checked"' : ''; ?> value="1" />&nbsp; <label for="TakeHomeAllowed" style="font-weight: normal;">Yes</label>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="Volume" class="col-lg-2 control-label">Volume</label>
                            <div class="col-lg-3">
                            	<input class="form-control" type="text" maxlength="25" id="Volume" name="txtVolume" value="<?php echo $Clean['Volume']; ?>" />
                            </div>
                            <label for="NumberOfPages" class="col-lg-2 control-label">NumberOfPages</label>
                            <div class="col-lg-3">
                            	<input class="form-control" type="text" maxlength="6" id="NumberOfPages" name="txtNumberOfPages" value="<?php echo ($Clean['NumberOfPages']) ? $Clean['NumberOfPages'] : ''; ?>" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="Price" class="col-lg-2 control-label">Price</label>
                            <div class="col-lg-3">
                            	<input class="form-control" type="text" maxlength="10" id="Price" name="txtPrice" value="<?php echo ($Clean['Price']) ? $Clean['Price'] : ''; ?>" />
                            </div>
                            <label for="PurchaseDate" class="col-lg-2 control-label">Purchase Date</label>
                            <div class="col-lg-3"> 
                            	<input class="form-control select-date" type="text" maxlength="10" id="PurchaseDate" name="txtPurchaseDate" value="<?php echo $Clean['PurchaseDate']; ?>" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="Quantity" class="col-lg-2 control-label">Quantity</label>
                            <div class="col-lg-3">
                            	<input class="form-control" type="text" maxlength="6" id="Quantity" name="txtQuantity" value="<?php echo ($Clean['Quantity']) ? $Clean['Quantity'] : ''; ?>" />
                            </div>
                            <label for="ShelfNumber" class="col-lg-2 control-label">Shelf Number</label>
                            <div class="col-lg-3">
                            	<input class="form-control" type="text" maxlength="50" id="ShelfNumber" name="txtShelfNumber" value="<?php echo $Clean['ShelfNumber']; ?>" />
                            </div>
                        </div>
                        
                    </div>
                </div>
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <strong>Enter Publication Details</strong>
                    </div>
                    <div class="panel-body">
                    	<div class="form-group">
                            <label for="PublishedYear" class="col-lg-2 control-label">Published Year</label>
                            <div class="col-lg-3">
                            	<input class="form-control" type="text" maxlength="4" id="PublishedYear" name="txtPublishedYear" placeholder="Enter year like 2018" value="<?php echo $Clean['PublishedYear']; ?>" />
                            </div>
                            <label for="Edition" class="col-lg-2 control-label">Edition</label>
                            <div class="col-lg-3">
                            	<input class="form-control" type="text" maxlength="100" id="Edition" name="txtEdition" value="<?php echo $Clean['Edition']; ?>" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="PublisherName" class="col-lg-2 control-label">Publisher Name</label>
                            <div class="col-lg-8">
                                <input class="form-control" list="PublisherNameList" maxlength="200" id="PublisherName" name="txtPublisherName" placeholder="Start typing...." value="<?php echo ($Clean['PublisherName']); ?>" />
                                <datalist id="PublisherNameList">
                                </datalist>
                                <input type="hidden" name="txtPublisherID" id="PublisherID" value="<?php echo ($Clean['PublisherID']) ? $Clean['PublisherID'] : '' ; ?>">
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
                $('#BookCategory').html(ResultArray[1]);
            }
        });
    });

    $("input[name='optBookType']").change(function(){
    	if ($("input[name='optBookType']:checked").val() == 'Academic') 
    	{
    		$('#Class').prop('disabled', false);
    	}
    	else
    	{
    		$('#Class').val(0);
    		$('#Class').prop('disabled', true);
    	}
    }).trigger('change');

	$('body').on('keyup', '.SearchAuthor', function(){

        var AuthorName = $(this).val();
        var ID = $(this).attr('id').split('AuthorName');
        var Counter = ID[1];

        if (AuthorName == '') 
        {
            $('#AuthorNameList'+ Counter +'').html('');
            return false;
        }
               
        $.post("/xhttp_calls/get_author_detail_by_name.php", {SelectedAuthorName:AuthorName}, function(data)
        {
            ResultArray = data.split("|*****|");
            
            if (ResultArray[0] == 'error')
            {
                alert (ResultArray[1]);
                return false;
            }
            else
            {
                $('#AuthorNameList'+ Counter +'').html(ResultArray[1]);
                
                var AuthorName = $('#AuthorName'+ Counter +'').val();
                var AuthorID = $('#AuthorNameList'+ Counter +'').find('option[value="' + AuthorName + '"]').attr('id');

                if (AuthorID != undefined) 
                {
                    $('#AuthorID'+ Counter +'').val(AuthorID);
                }
                if ($('#AuthorNameList'+ Counter +'').find('option').length <= 0) 
                {
                	$('#AuthorID'+ Counter +'').val('');
                }
            }
        });
    });

	$('body').on('change', '.SearchAuthor', function(){

        var AuthorName = $(this).val();
        var ID = $(this).attr('id').split('AuthorName');
        var Counter = ID[1];
    
        var AuthorID = $('#AuthorNameList'+ Counter +'').find('option[value="' + AuthorName + '"]').attr('id');

        if (AuthorID != undefined) 
        {
            $('#AuthorID'+ Counter +'').val(AuthorID);
        }
    });

	$('#PublisherName').keyup(function(){

        var PublisherName = $(this).val();

        if (PublisherName == '') 
        {
            $('#PublisherNameList').html('');
            return false;
        }
               
        $.post("/xhttp_calls/get_publisher_detail_by_name.php", {SelectedPublisherName:PublisherName}, function(data)
        {
            ResultArray = data.split("|*****|");
            
            if (ResultArray[0] == 'error')
            {
                alert (ResultArray[1]);
                return false;
            }
            else
            {
                $('#PublisherNameList').html(ResultArray[1]);
                
                var PublisherName = $('#PublisherName').val();
                var PublisherID = $('#PublisherNameList').find('option[value="' + PublisherName + '"]').attr('id');

                if (PublisherID != undefined) 
                {
                    $('#PublisherID').val(PublisherID);
                }
                if ($('#PublisherNameList').find('option').length <= 0) 
                {
                	$('#PublisherID').val('');
                }
            }
        });
    });

    $('#PublisherName').change(function(){

        var PublisherName = $('#PublisherName').val();
        var PublisherID = $('#PublisherNameList').find('option[value="' + PublisherName + '"]').attr('id');

        if (PublisherID != undefined) 
        {
            $('#PublisherID').val(PublisherID);
        }
    });

    $('#AddMore').click(function(){
        
        var Counter = 0;
        Counter = parseInt($('.AuthorNameContainer').length) + 1;

        var Data = '<div class="form-group AuthorNameContainer">'
            Data += '<label for="AuthorName'+ Counter +'" class="col-lg-2 control-label">Author Name</label>'
            Data += '<div class="col-lg-8">'
            Data += '<input class="form-control SearchAuthor" list="AuthorNameList'+ Counter +'" maxlength="100" id="AuthorName'+ Counter +'" name="txtAuthorName['+ Counter +']" placeholder="Start typing...." value="<?php echo (array_key_exists($Counter, $Clean['AuthorNameList']) ? $Clean['AuthorNameList'][$Counter] : ''); ?>" />'
            Data += '<datalist id="AuthorNameList'+ Counter +'">'
            Data += '</datalist>'
            Data += '<input type="hidden" name="hdnAuthorID['+ Counter +']" id="AuthorID'+ Counter +'" value="<?php echo (array_key_exists($Counter, $Clean['AuthorIDList']) ? $Clean['AuthorIDList'][$Counter] : ''); ?>">'
            Data += '</div>'
            Data += '<div class="col-lg-2">'
            Data += '<button type="button" class="btn btn-sm btn-danger RemovePeriodDetailContainer">Remove</button>'
            Data += '</div>'
            Data += '</div>'
            
            $('#AuthorNameContainer').append(Data);
    });

    $('body').on('click', '.RemovePeriodDetailContainer', function(){
        if ($('.AuthorNameContainer').length <= 1)
        {
            alert('At least one author is required, thus you cannot delete this section.');
            return false;
        }
        $(this).closest('.AuthorNameContainer').remove();
    });
});

</script>
</body>
</html>
<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

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

if ($LoggedUser->HasPermissionForTask(TASK_ADD_BOOK_CATEGORY) !== true)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}

$HasErrors = false;

$ParentCategorylist = array();
$ParentCategorylist = BookCategory::GetActiveParentCategories();

$Clean = array();
$Clean['Process'] = 0;

$Clean['ParentCategoryID'] = 0;
$Clean['BookCategoryName'] = '';

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
		if (isset($_POST['txtBookCategoryName']))
		{
			$Clean['BookCategoryName'] = strip_tags(trim($_POST['txtBookCategoryName']));
		}

		$NewRecordValidator = new Validator();

		if ($Clean['ParentCategoryID'] > 0) 
		{
			$NewRecordValidator->ValidateInSelect($Clean['ParentCategoryID'], $ParentCategorylist, 'Please select valid parent category.');
		}

		$NewRecordValidator->ValidateStrings($Clean['BookCategoryName'], 'Book category name is required and should be between 3 and 100 characters.', 3, 100);
		
		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}
				
		$NewBookCategory = new BookCategory();
				
		$NewBookCategory->SetParentCategoryID($Clean['ParentCategoryID']);
		$NewBookCategory->SetBookCategoryName($Clean['BookCategoryName']);

		$NewBookCategory->SetIsActive(1);
		$NewBookCategory->SetCreateUserID($LoggedUser->GetUserID());

		if (!$NewBookCategory->Save())
		{
			$NewRecordValidator->AttachTextError(ProcessErrors($NewBookCategory->GetLastErrorCode()));
			$HasErrors = true;
			break;
		}
		
		header('location:book_category_report.php?Mode=AS');
		exit;
	break;
}

require_once('../html_header.php');
?>
<title>Add Book Category</title>
<link href="vendor/jquery-ui/jquery-ui.min.css" rel="stylesheet">
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
                    <h1 class="page-header">Add Book Category</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="AddBookCategory" action="add_book_category.php" method="post">
            	<div class="panel panel-default">
                    <div class="panel-heading">
                        <strong>Enter Book Category Details</strong>
                    </div>
                    <div class="panel-body">
<?php
						if ($HasErrors == true)
						{
							echo $NewRecordValidator->DisplayErrors();
						}
?>                    
						<div class="form-group">
							<label for="ParentCategory" class="col-lg-2 control-label">Parent Category</label>
                            <div class="col-lg-4">
                            	<select class="form-control" name="drdParentCategory" id="ParentCategory">
                            		<option value="">-- Select Parent Category --</option>
<?php
								if (is_array($ParentCategorylist) && count($ParentCategorylist) > 0)
								{
									foreach($ParentCategorylist as $ParentCategoryID => $ParentCategoryName)
									{
										echo '<option ' . (($Clean['ParentCategoryID'] == $ParentCategoryID) ? 'selected="selected"' : '' ) . ' value="' . $ParentCategoryID . '">' . $ParentCategoryName . '</option>';
									}
								}
?>
								</select>
								<small>( Select parent category, if you want to add a sub-category. )</small>
                            </div>
                        </div>
						<div class="form-group">
                            <label for="BookCategoryName" class="col-lg-2 control-label">Book Category Name</label>
                            <div class="col-lg-4">
                            	<input class="form-control" type="text" maxlength="100" id="BookCategoryName" name="txtBookCategoryName" value="<?php echo $Clean['BookCategoryName']; ?>" />
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
</body>
</html>
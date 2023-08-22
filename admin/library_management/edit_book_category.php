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

if ($LoggedUser->HasPermissionForTask(TASK_EDIT_BOOK_CATEGORY) !== true)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}

if (isset($_POST['btnCancel']))
{
    header('location:book_category_report.php');
    exit;
}

$Clean = array();

$Clean['BookCategoryID'] = 0;

if (isset($_GET['BookCategoryID']))
{
    $Clean['BookCategoryID'] = (int) $_GET['BookCategoryID'];
}
else if (isset($_POST['hdnBookCategoryID']))
{
    $Clean['BookCategoryID'] = (int) $_POST['hdnBookCategoryID'];
}

if ($Clean['BookCategoryID'] <= 0)
{
    header('location:/admin/error.php');
    exit;
}   

try
{
    $BookCategoryToEdit = new BookCategory($Clean['BookCategoryID']);
}
catch (ApplicationDBException $e)
{
    header('location:/admin/error.php');
    exit;
}
catch (Exception $e)
{
    header('location:/admin/error.php');
    exit;
}

$ParentCategorylist = array();
$ParentCategorylist = BookCategory::GetActiveParentCategories();

$HasErrors = false;

$Clean['Process'] = 0;

$Clean['ParentCategoryID'] = 0;
$Clean['BookCategoryName'] = '';

$Clean['IsActive'] = 0;

if (isset($_POST['hdnProcess']))
{
    $Clean['Process'] = (int) $_POST['hdnProcess'];
}
else if (isset($_GET['Process']))
{
    $Clean['Process'] = (int) $_GET['Process'];
}
switch ($Clean['Process'])
{
    case 3:     
        if (isset($_POST['drdParentCategory']))
        {
            $Clean['ParentCategoryID'] = (int) $_POST['drdParentCategory'];
        }
        if (isset($_POST['txtBookCategoryName']))
        {
            $Clean['BookCategoryName'] = strip_tags(trim($_POST['txtBookCategoryName']));
        }

        if (isset($_POST['chkIsActive']))
        {
            $Clean['IsActive'] = 1;
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
                        
        $BookCategoryToEdit->SetParentCategoryID($Clean['ParentCategoryID']);
        $BookCategoryToEdit->SetBookCategoryName($Clean['BookCategoryName']);

        $BookCategoryToEdit->SetIsActive($Clean['IsActive']);

        if (!$BookCategoryToEdit->Save())
        {
            $NewRecordValidator->AttachTextError(ProcessErrors($BookCategoryToEdit->GetLastErrorCode()));
            $HasErrors = true;

            break;
        }
        
        header('location:book_category_report.php?Mode=UD');
        exit;
    break;

    case 2:
        $Clean['ParentCategoryID'] = $BookCategoryToEdit->GetParentCategoryID();
        $Clean['BookCategoryName'] = $BookCategoryToEdit->GetBookCategoryName();
        
        $Clean['IsActive'] = $BookCategoryToEdit->GetIsActive();

    break;
}

require_once('../html_header.php');
?>
<title>Edit Book Category</title>
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
                    <h1 class="page-header">Edit Book Category</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="EditBookCategory" action="edit_book_category.php" method="post">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        Edit Book Category
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
                                <select class="form-control" name="drdParentCategory" id="ParentCategory" <?php echo (($Clean['ParentCategoryID']) ? '' : 'disabled="disabled"');?> >
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
                            <label for="IsActive" class="col-lg-2 control-label">Is Active</label>
                            <div class="col-lg-4">
                                <input type="checkbox" id="IsActive" name="chkIsActive" <?php echo ($Clean['IsActive'] == 1) ? 'checked="checked"' : ''; ?> value="1" />
                            </div>
                        </div>
                        <div class="form-group">
                        <div class="col-sm-offset-2 col-lg-10">
                            <input type="hidden" name="hdnProcess" value="3"/>
                            <input type="hidden" name="hdnBookCategoryID" value="<?php echo $Clean['BookCategoryID']; ?>" />
                            <button type="submit" class="btn btn-primary"><i class="fa fa-upload"></i>&nbsp;Update</button>
                            <button type="submit" class="btn btn-primary" name="btnCancel">Cancel</button>
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
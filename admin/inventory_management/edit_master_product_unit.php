<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once("../../classes/inventory_management/class.master_product_units.php");

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

if ($LoggedUser->HasPermissionForTask(TASK_EDIT_PRODUCT_UNIT) !== true)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}

$Clean = array();

$Clean['ProductUnitID'] = 0;

if (isset($_GET['ProductUnitID']))
{
    $Clean['ProductUnitID'] = (int) $_GET['ProductUnitID'];
}
elseif (isset($_POST['hdnProductUnitID']))
{
    $Clean['ProductUnitID'] = (int) $_POST['hdnProductUnitID'];
}

if ($Clean['ProductUnitID'] <= 0)
{
    header('location:../error.php');
    exit;
}   

try
{
    $MasterProductUnitToEdit = new MasterProductUnit($Clean['ProductUnitID']);
}
catch (ApplicationDBException $e)
{
    header('location:../error.php');
    exit;
}
catch (Exception $e)
{
    header('location:../error.php');
    exit;
}

$HasErrors = false;

$Clean['Process'] = 0;

$Clean['ProductUnitName'] = '';
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
		if (isset($_POST['txtProductUnitName']))
		{
			$Clean['ProductUnitName'] = strip_tags(trim($_POST['txtProductUnitName']));
		}

		if (isset($_POST['chkIsActive']))
        {
            $Clean['IsActive'] = 1;
        }
		
		$NewRecordValidator = new Validator();
		$NewRecordValidator->ValidateStrings($Clean['ProductUnitName'], 'Product unit name is required and should be between 3 and 25 characters.', 3, 25);
		
		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}			
				
		$MasterProductUnitToEdit->SetProductUnitName($Clean['ProductUnitName']);
		$MasterProductUnitToEdit->SetIsActive($Clean['IsActive']);
		
		$MasterProductUnitToEdit->SetCreateUserID($LoggedUser->GetUserID());

        if ($MasterProductUnitToEdit->RecordExists())
        {
            $NewRecordValidator->AttachTextError('master product unit you have added already exists.');
            $HasErrors = true;
            break;
        }

		if (!$MasterProductUnitToEdit->Save())
		{
			$NewRecordValidator->AttachTextError(ProcessErrors($MasterProductUnitToEdit->GetLastErrorCode()));
			$HasErrors = true;

			break;
		}
		
		header('location:master_product_units_list.php?Mode=ED');
		exit;
		break;

	case 2:
		$Clean['ProductUnitName'] = $MasterProductUnitToEdit->GetProductUnitName();
		$Clean['IsActive'] = $MasterProductUnitToEdit->GetIsActive();
}

require_once('../html_header.php');
?>
<title>Edit Product Unit</title>
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
                    <h1 class="page-header">Edit Product Unit</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="EditMasterProductUnit" action="edit_master_product_unit.php" method="post">
            	<div class="panel panel-default">
                    <div class="panel-heading">
                        Enter Product Unit Details
                    </div>
                    <div class="panel-body">
<?php
						if ($HasErrors == true)
						{
							echo $NewRecordValidator->DisplayErrors();
						}
?>                    
                    	<div class="form-group">
                            <label for="ProductUnitName" class="col-lg-2 control-label">Product Unit Name</label>
                            <div class="col-lg-4">
                            	<input class="form-control" type="text" maxlength="25" id="ProductUnitName" name="txtProductUnitName" value="<?php echo $Clean['ProductUnitName']; ?>" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="IsActive" class="col-lg-2 control-label">Is Active</label>
                            <div class="col-lg-4">
                                <input type="checkbox" id="IsActive" name="chkIsActive" value="1" <?php echo ($Clean['IsActive'] == 1) ? 'checked="checked"' : ''; ?>/>
                            </div>
                        </div>
                        <div class="form-group">
                        <div class="col-sm-offset-2 col-lg-10">
                        	<input type="hidden" name="hdnProcess" value="3" />
                        	<input type="hidden" name="hdnProductUnitID" value="<?php echo $Clean['ProductUnitID'];?>" />
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
</body>
</html>
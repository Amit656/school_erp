<?php
require_once("../../classes/class.users.php");
require_once('../../classes/class.validation.php');
require_once("../../classes/class.authentication.php");

require_once("../../classes/school_administration/class.countries.php");
require_once("../../classes/school_administration/class.states.php");
require_once("../../classes/school_administration/class.cities.php");
require_once("../../classes/inventory_management/class.product_vendors.php");

require_once("../../classes/class.ui_helpers.php");

require_once("../../includes/helpers.inc.php");
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

if ($LoggedUser->HasPermissionForTask(TASK_LIST_PRODUCT_VENDOR) !== true)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}

$CountryList = array();
$CountryList = Country::GetAllCountries();

$StateList = array();
$StateList = State::GetAllStates(key($CountryList));

$CityList = array();
$DistrictList = array();

$AllProductVendors = array();

$ActiveStatusList = array(1 => 'Yes', 0 => 'No');

$HasErrors = false;

$Clean = array();
$Clean['Process'] = 0;

$Clean['ProductVendorID'] = 0;
$Clean['VendorName'] = '';

$Clean['CountryID'] = 0;
$Clean['StateID'] = 0;
$Clean['CityID'] = 0;
$Clean['DistrictID'] = 0;

$Clean['PinCode'] = '';

$Clean['PhoneNumber'] = '';
$Clean['MobileNumber'] = '';

$Clean['ContactName'] = '';
$Clean['ContactPhoneNumber'] = '';
$Clean['ActiveStatus'] = 1;

// paging and sorting variables start here  //
$Clean['CurrentPage'] = 1;
$TotalPages = 0;

$Start = 0;
$Limit = 10;
// end of paging variables //

if (isset($_GET['Process']))
{
	$Clean['Process'] = (int) $_GET['Process'];
}
elseif (isset($_GET['hdnProcess']))
{
    $Clean['Process'] = (int) $_GET['hdnProcess'];
}
switch ($Clean['Process'])
{
	case 5:
		if ($LoggedUser->HasPermissionForTask(TASK_DELETE_PRODUCT_VENDOR) !== true)
		{
			header('location:unauthorized_login_admin.php');
			exit;
		}
		
		if (isset($_GET['ProductVendorID']))
		{
			$Clean['ProductVendorID'] = (int) $_GET['ProductVendorID'];			
		}
		
		if ($Clean['ProductVendorID'] <= 0)
		{
			header('location:../error.php');
			exit;
		}						
			
		try
		{
			$ProductVendorToDelete = new ProductVendor($Clean['ProductVendorID']);
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
		
		$RecordValidator = new Validator();
		
		// if ($ProductVendorToDelete->CheckDependencies())
		// {
		// 	$RecordValidator->AttachTextError('This master product Vendor cannot be deleted. There are dependent records for this master product Vendor.');
		// 	$HasErrors = true;
		// 	break;
		// }
				
		if (!$ProductVendorToDelete->Remove())
		{
			$RecordValidator->AttachTextError(ProcessErrors($ProductVendorToDelete->GetLastErrorCode()));
			$HasErrors = true;
			break;
		}
		
		$RecordDeletedSuccessfully = true;
	    break;

    case 7;
        if (isset($_GET['txtVendorName']))
        {
            $Clean['VendorName'] = strip_tags(trim($_GET['txtVendorName']));
        } 
        elseif (isset($_GET['VendorName']))
        {
            $Clean['VendorName'] = strip_tags(trim($_GET['VendorName']));
        }

        if (isset($_GET['drdCountry']))
        {
            $Clean['CountryID'] = (int) $_GET['drdCountry'];
        }
        elseif (isset($_GET['CountryID']))
        {
            $Clean['CountryID'] = (int) $_GET['CountryID'];
        }

        if (isset($_GET['drdState']))
        {
            $Clean['StateID'] = (int) $_GET['drdState'];
        }
        elseif (isset($_GET['StateID']))
        {
            $Clean['StateID'] = (int) $_GET['StateID'];
        }

        if (isset($_GET['drdDistrict']))
        {
            $Clean['DistrictID'] = (int) $_GET['drdDistrict'];
        }
        elseif (isset($_GET['DistrictID']))
        {
            $Clean['DistrictID'] = (int) $_GET['DistrictID'];
        }

        if (isset($_GET['drdCity']))
        {
            $Clean['CityID'] = (int) $_GET['drdCity'];
        }
        elseif (isset($_GET['CityID']))
        {
            $Clean['CityID'] = (int) $_GET['CityID'];
        }

        if (isset($_GET['txtMobileNumber']))
        {
            $Clean['MobileNumber'] = strip_tags(trim($_GET['txtMobileNumber']));
        }
        elseif (isset($_GET['MobileNumber']))
        {
            $Clean['MobileNumber'] = strip_tags(trim($_GET['MobileNumber']));
        }

        if (isset($_GET['txtContactName']))
        {
            $Clean['ContactName'] = strip_tags(trim($_GET['txtContactName']));
        }
        elseif (isset($_GET['ContactName']))
        {
            $Clean['ContactName'] = strip_tags(trim($_GET['ContactName']));
        }

        if (isset($_GET['txtContactPhoneNumber']))
        {
            $Clean['ContactPhoneNumber'] = strip_tags(trim($_GET['txtContactPhoneNumber']));
        }
        elseif (isset($_GET['ContactPhoneNumber']))
        {
            $Clean['ContactPhoneNumber'] = strip_tags(trim($_GET['ContactPhoneNumber']));
        }

        if (isset($_GET['rbdActiveStatus']))
        {
            $Clean['ActiveStatus'] = (int) $_GET['rbdActiveStatus'];
        }
        elseif (isset($_GET['ActiveStatus']))
        {
            $Clean['ActiveStatus'] = (int) $_GET['ActiveStatus'];
        }

        $RecordValidator = new Validator();

        if ($Clean['VendorName'] != '') 
        {
            $RecordValidator->ValidateStrings($Clean['VendorName'], 'Product vemdor name is required and should be between 1 and 25 characters.', 1, 25);
        }

        if ($Clean['CountryID'] != 0) 
        {
            $RecordValidator->ValidateInSelect($Clean['CountryID'], $CountryList, 'Unknown Error, Please try again.');
        }

        if ($Clean['StateID'] != 0) 
        {
            $RecordValidator->ValidateInSelect($Clean['StateID'], $StateList, 'Unknown Error, Please try again.');
        }

        if ($Clean['DistrictID'] != 0) 
        {
            $RecordValidator->ValidateInSelect($Clean['DistrictID'], $DistrictList, 'Unknown Error, Please try again.');
        }

        if ($Clean['CityID'] != 0) 
        {
            $RecordValidator->ValidateInSelect($Clean['CityID'], $CityList, 'Unknown Error, Please try again.');
        }
        
        if ($Clean['MobileNumber'] != '') 
        {
            $RecordValidator->ValidateStrings($Clean['MobileNumber'], 'Mobile Number is required and should be between 1 and 15 characters.', 1, 15);
        }

        if ($Clean['ContactName'] != '') 
        {
            $RecordValidator->ValidateStrings($Clean['ContactName'], 'Contact Name is required and should be between 1 and 30 characters.', 1, 30);
        }

        if ($Clean['ContactPhoneNumber'] != '') 
        {
            $RecordValidator->ValidateStrings($Clean['ContactPhoneNumber'], 'Contact phone number is required and should be between 1 and 15 characters.', 1, 15);
        }

        $RecordValidator->ValidateInSelect($Clean['ActiveStatus'], $ActiveStatusList, 'Unknown Error, Please try again.');
        
        if ($RecordValidator->HasNotifications())
        {
            $HasErrors = true;
            break;
        }

        //set record filters
        $Filters['VendorName'] = $Clean['VendorName'];
        $Filters['CountryID'] = $Clean['CountryID'];
        $Filters['StateID'] = $Clean['StateID'];
        $Filters['DistrictID'] = $Clean['DistrictID'];
        $Filters['CityID'] = $Clean['CityID'];
        $Filters['MobileNumber'] = $Clean['MobileNumber'];
        $Filters['ContactName'] = $Clean['ContactName'];
        $Filters['ContactPhoneNumber'] = $Clean['ContactPhoneNumber'];
        $Filters['ActiveStatus'] = $Clean['ActiveStatus'];  

        //get records count
        ProductVendor::SearchProductVendor($TotalRecords, true, $Filters);
  
        if ($TotalRecords > 0)
        {
            // Paging and sorting calculations start here.
            $TotalPages = (($TotalRecords % $Limit) == 0) ? $TotalRecords / $Limit : floor($TotalRecords / $Limit) + 1;

            if (isset($_GET['CurrentPage']))
            {
                $Clean['CurrentPage'] = (int) $_GET['CurrentPage'];
            }

            if ($Clean['CurrentPage'] <= 0)
            {
                $Clean['CurrentPage'] = 1;
            }
            elseif ($Clean['CurrentPage'] > $TotalPages)
            {
                $Clean['CurrentPage'] = $TotalPages;
            }

            if ($Clean['CurrentPage'] > 1)
            {
                $Start = ($Clean['CurrentPage'] - 1) * $Limit;
            }
            
            // end of Paging and sorting calculations.
            // now get the actual  records
            $AllProductVendors = ProductVendor::SearchProductVendor($TotalRecords, false, $Filters, $Start, $Limit);
        }
        break;
}

require_once('../html_header.php');
?>
<title>Product Vendor List</title>
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
                    <h1 class="page-header">Product Vendor List</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
            <form class="form-horizontal" name="SearchProductVendor" action="product_vendors_list.php" method="get">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        Filter
                    </div>
<?php
                    if ($HasErrors == true)
                    {
                        echo $RecordValidator->DisplayErrorsInTable();
                    }
?>
                    <div class="panel-body">
                        <div class="form-group">
                            <label for="VendorName" class="col-lg-2 control-label">Vendor Name</label>
                            <div class="col-lg-4">
                                <input class="form-control" type="text" maxlength="25" id="VendorName" name="txtVendorName" value="<?php echo $Clean['VendorName']; ?>" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="CountryID" class="col-lg-2 control-label">Country</label>
                            <div class="col-lg-4">
                                <select class="form-control" name="drdCountry" id="CountryID" disabled="disabled">
                                    <option value="0">Select-</option>
<?php
                                    if (is_array($CountryList) && count($CountryList) > 0)
                                    {
                                        foreach ($CountryList as $CountryID => $CountryName) 
                                        {
                                            echo '<option ' . ($Clean['CountryID'] == $CountryID ? 'selected="selected"' : '') . ' value="' . $CountryID . '">' . $CountryName . '</option>';
                                        }
                                    }
?>
                                </select>
                            </div>
                            <label for="StateID" class="col-lg-2 control-label">State</label>
                            <div class="col-lg-4"> 
                                <select class="form-control" name="drdState" id="StateID" disabled="disabled">
                                    <option value="0">Select-</option>
<?php
                                    if (is_array($StateList) && count($StateList) > 0)
                                    {
                                        foreach ($StateList as $StateID => $StateName) 
                                        {
                                            echo '<option ' . ($Clean['StateID'] == $StateID ? 'selected="selected"' : '') . ' value="' . $StateID . '">' . $StateName . '</option>';
                                        }
                                    }
?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="DistrictID" class="col-lg-2 control-label">District</label>
                            <div class="col-lg-4">
                                <select class="form-control" name="drdDistrict" id="DistrictID" disabled="disabled">
                                    <option value="0">Select-</option>
<?php
                                    if (is_array($DistrictList) && count($DistrictList) > 0)
                                    {
                                        foreach ($DistrictList as $DistrictID => $DistrictName) 
                                        {
                                            echo '<option ' . ($Clean['DistrictID'] == $DistrictID ? 'selected="selected"' : '') . ' value="' . $DistrictID . '">' . $DistrictName . '</option>';
                                        }
                                    }
?>
                                </select>
                            </div>
                            <label for="CityID" class="col-lg-2 control-label">City</label>
                            <div class="col-lg-4">
                                <select class="form-control" name="drdCity" id="CityID" disabled="disabled">
                                    <option value="0">Select-</option>
<?php
                                    if (is_array($CityList) && count($CityList) > 0)
                                    {
                                        foreach ($CityList as $CityID => $CityName) {
                                            echo '<option ' . ($Clean['CityID'] == $CityID ? 'selected="selected"' : '') . ' value="' . $CityID . '">' . $CityName . '</option>';
                                        }
                                    }
?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="MobileNumber" class="col-lg-2 control-label">MobileNumber</label>
                            <div class="col-lg-4">
                                <input class="form-control" type="MobileNumber1" maxlength="15" id="MobileNumber" name="txtMobileNumber" value="<?php echo $Clean['MobileNumber']; ?>"/>
                            </div>
                            <label for="ContactName" class="col-lg-2 control-label">Contact Person Name</label>
                            <div class="col-lg-4">
                                <input class="form-control" type="ContactName" maxlength="30" id="PhoneNumber" name="txtContactName" value="<?php echo $Clean['ContactName']; ?>"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="ContactPhoneNumber" class="col-lg-2 control-label">Contact Phone Number</label>
                            <div class="col-lg-4">
                                <input class="form-control" type="text" maxlength="15" id="ContactPhoneNumber" name="txtContactPhoneNumber" value="<?php echo $Clean['ContactPhoneNumber']; ?>" />
                            </div>
                            <label for="ActiveStatus" class="col-lg-2 control-label">Is Active</label>
                            <div class="col-lg-4">
<?php
                            foreach ($ActiveStatusList as $ActiveStatus => $ActiveStatusName) 
                            {
?>
                            <label style="font-weight: normal;">
                                <input type="radio" id="rbdActiveStatus" name="rbdActiveStatus" value="<?php echo $ActiveStatus; ?>" <?php echo($Clean['ActiveStatus'] == $ActiveStatus ? 'checked="checked"' : ''); ?> />&nbsp;<?php echo $ActiveStatusName; ?>
                            </label>
<?php
                            }
?>
                            </div>  
                        </div>
                        <div class="form-group">
                            <div class="col-sm-offset-2 col-lg-10">
                                <input type="hidden" name="hdnProcess" value="7" />
                                <button type="submit" class="btn btn-primary">Search</button>
                            </div>
                      </div>
                    </div>  
                </div>
            </form>
            <!-- /.row -->
<?php
        if ($Clean['Process'] == 7)
        {
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
                                    <div class="col-lg-6">
                                    <div class="add-new-btn-container"><a href="add_product_Vendor.php" class="btn btn-primary<?php echo $LoggedUser->HasPermissionForTask(TASK_ADD_PRODUCT_VENDOR) === true ? '' : ' disabled'; ?>" role="button">Add New Product Vendor</a></div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="print-btn-container"><button id="PrintButton" type="submit" class="btn btn-primary">Print</button></div>
                                    </div>
                                </div>
                                <br/>
                                <div class="row">
                                   <div class="col-lg-6">
<?php
                                    if ($TotalPages > 1)
                                    {
                                        $AllParameters = $Filters;
                                        $AllParameters['Process'] = '7';

                                        echo UIHelpers::GetPager('product_vendors_list.php', $TotalPages, $Clean['CurrentPage'], $AllParameters);
                                    }
?>                                        
                                    </div>
                                    <div class="col-lg-6"></div>  
                                </div>
                                <div class="row" id="RecordTableHeading">
                                    <div class="col-lg-12">
                                        <div class="report-heading-container"><strong>Product Vendors on <?php echo date('d-m-Y h:i A'); ?></strong></div>
                                    </div>
                                </div>
                                <div class="row" id="RecordTable">
                                    <div class="col-lg-12">
                                        <table width="100%" class="table table-striped table-bordered table-hover" id="DataTableRecords">
                                            <thead>
                                                <tr>
                                                    <th>S. No</th>
                                                    <th>Product Vendor Name</th>
                                                    <th>Phone Number</th>
                                                    <th>Mobile Number</th>
                                                    <th>Contact Person Name</th>
                                                    <th>Contact Phone Number</th>
                                                    <th>Is Active</th>
                                                    <th>Create User</th>
                                                    <th>Create Date</th>
                                                    <th class="print-hidden">Operations</th>
                                                </tr>
                                            </thead>
                                            <tbody>
<?php
                                    if (is_array($AllProductVendors) && count($AllProductVendors) > 0)
                                    {
                                        $Counter = 0;
                                        foreach ($AllProductVendors as $ProductVendorID => $ProductVendorDetails)
                                        {
?>
                                        <tr>
                                            <td><?php echo ++$Counter; ?></td>
                                            <td><?php echo $ProductVendorDetails['VendorName']; ?></td>
                                            <td><?php echo $ProductVendorDetails['PhoneNumber']; ?></td>
                                            <td><?php echo $ProductVendorDetails['MobileNumber1']; ?></td>
                                            <td><?php echo $ProductVendorDetails['ContactName']; ?></td>
                                            <td><?php echo $ProductVendorDetails['ContactPhoneNumber']; ?></td>
                                            <td><?php echo ($ProductVendorDetails['IsActive']) ? 'Yes' : 'No'?></td>
                                            <td><?php echo $ProductVendorDetails['CreateUserName']; ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($ProductVendorDetails['CreateDate'])); ?></td>
                                            <td class="print-hidden">
<?php
                                            if ($LoggedUser->HasPermissionForTask(TASK_EDIT_PRODUCT_VENDOR) === true)
                                            {
                                                echo '<a href="edit_product_vendor.php?Process=2&amp;ProductVendorID='. $ProductVendorID .'">Edit</a>';
                                            }
                                            else
                                            {
                                                echo 'Edit';
                                            }

                                            echo '&nbsp;|&nbsp;';

                                            if ($LoggedUser->HasPermissionForTask(TASK_DELETE_PRODUCT_VENDOR) === true)
                                            {
                                                echo '<a class="delete-record" href="product_vendors_list.php?Process=5&amp;ProductVendorID=' . $ProductVendorID . '">Delete</a>'; 
                                            }
                                            else
                                            {
                                                echo 'Delete';
                                            }
?>
                                            </td>
                                        </tr>
<?php
                                        }
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
if (PrintMessage($_GET, $Message))
{
?>
<script type="text/javascript">
    alert('<?php echo $Message; ?>');
</script>
<?php
}
?>
<!-- DataTables JavaScript -->
<script src="/admin/vendor/datatables/js/jquery.dataTables.min.js"></script>
<script src="/admin/vendor/datatables-plugins/dataTables.bootstrap.min.js"></script>
<script src="/admin/vendor/datatables-responsive/dataTables.responsive.js"></script>	
<script type="text/javascript">
$(document).ready(function() {
    $("body").on('click', '.delete-record', function()
    {   
        if (!confirm("Are you sure you want to delete this product Vendor?"))
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
<!-- JavaScript To Print A Report -->
<script src="/admin/js/print-report.js"></script>
</body>
</html>
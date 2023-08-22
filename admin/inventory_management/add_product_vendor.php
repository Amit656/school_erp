<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once("../../classes/school_administration/class.countries.php");
require_once("../../classes/school_administration/class.states.php");
require_once("../../classes/school_administration/class.cities.php");
require_once("../../classes/inventory_management/class.product_vendors.php");

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

if ($LoggedUser->HasPermissionForTask(TASK_ADD_PRODUCT_VENDOR) !== true)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}

$CountryList = array();
$CountryList = Country::GetAllCountries();

$StateList = array();
$CityList = array();
$DistrictList = array();

$HasErrors = false;

$Clean = array();
$Clean['Process'] = 0;

$Clean['VendorName'] = '';

$Clean['Address1'] = '';
$Clean['Address2'] = '';

$Clean['CountryID'] = '';
$Clean['CountryID'] = key($CountryList);

$StateList = State::GetAllStates($Clean['CountryID']);
$Clean['StateID'] = key($StateList);

$DistrictList = City::GetAllDistricts($Clean['StateID']);
$Clean['DistrictID'] = '80';

$CityList = City::GetAllCities($Clean['StateID'], $Clean['DistrictID']);
$Clean['CityID'] = '377';

$Clean['PinCode'] = '';

$Clean['PhoneNumber'] = '';
$Clean['MobileNumber1'] = '';
$Clean['MobileNumber2'] = '';

$Clean['ContactName'] = '';
$Clean['ContactPhoneNumber'] = '';
$Clean['Description'] = '';

if (isset($_POST['hdnProcess']))
{
	$Clean['Process'] = (int) $_POST['hdnProcess'];
}
switch ($Clean['Process'])
{	
	case 1:						
		if (isset($_POST['txtVendorName']))
		{
			$Clean['VendorName'] = strip_tags(trim($_POST['txtVendorName']));
		}

		if (isset($_POST['txtAddress1']))
		{
			$Clean['Address1'] = strip_tags(trim($_POST['txtAddress1']));
		}

		if (isset($_POST['txtAddress2']))
		{
			$Clean['Address2'] = strip_tags(trim($_POST['txtAddress2']));
		}

		if (isset($_POST['drdCountry']))
		{
			$Clean['CountryID'] = (int) $_POST['drdCountry'];
		}

		if (isset($_POST['drdState']))
		{
			$Clean['StateID'] = (int) $_POST['drdState'];
		}

		if (isset($_POST['drdDistrict']))
		{
			$Clean['DistrictID'] = (int) $_POST['drdDistrict'];
		}

		if (isset($_POST['drdCity']))
		{
			$Clean['CityID'] = (int) $_POST['drdCity'];
		}

		if (isset($_POST['txtPinCode']))
		{
			$Clean['PinCode'] = strip_tags(trim($_POST['txtPinCode']));
		}

		if (isset($_POST['txtPhoneNumber']))
		{
			$Clean['PhoneNumber'] = strip_tags(trim($_POST['txtPhoneNumber']));
		}

		if (isset($_POST['txtMobileNumber1']))
		{
			$Clean['MobileNumber1'] = strip_tags(trim($_POST['txtMobileNumber1']));
		}

		if (isset($_POST['txtMobileNumber2']))
		{
			$Clean['MobileNumber2'] = strip_tags(trim($_POST['txtMobileNumber2']));
		}

		if (isset($_POST['txtContactName']))
		{
			$Clean['ContactName'] = strip_tags(trim($_POST['txtContactName']));
		}

		if (isset($_POST['txtContactPhoneNumber']))
		{
			$Clean['ContactPhoneNumber'] = strip_tags(trim($_POST['txtContactPhoneNumber']));
		}

		if (isset($_POST['txtDescription']))
		{
			$Clean['Description'] = strip_tags(trim($_POST['txtDescription']));
		}
		
		$NewRecordValidator = new Validator();

		$NewRecordValidator->ValidateStrings($Clean['VendorName'], 'Product vemdor name is required and should be between 3 and 250 characters.', 3, 250);
		
		if ($Clean['Address1'] != '')
		{
		    $NewRecordValidator->ValidateStrings($Clean['Address1'], 'Address1 is required and should be between 3 and 150 characters.', 3, 150);
		}

		if ($Clean['Address2'] != '')
        {
            $NewRecordValidator->ValidateStrings($Clean['Address2'], 'Address2 is required and should be between 3 and 150 characters.', 3, 150);
        }   

        if ($NewRecordValidator->ValidateInSelect($Clean['CountryID'], $CountryList, 'Unknown Error, Please try again.'))
        {
            $StateList = State::GetAllStates($Clean['CountryID']);

            if ($NewRecordValidator->ValidateInSelect($Clean['StateID'], $StateList, 'Unknown Error, Please try again.'))
            {
                $DistrictList = City::GetAllDistricts($Clean['StateID']);

                if ($NewRecordValidator->ValidateInSelect($Clean['DistrictID'], $DistrictList, 'Unknown Error, Please try again.'))
                {
                    $CityList = City::GetAllCities($Clean['StateID'], $Clean['DistrictID']);

                    $NewRecordValidator->ValidateInSelect($Clean['CityID'], $CityList, 'Unknown Error, Please try again.');
                }
            }
        }
        
        if ($Clean['PinCode'] != '')
        {
            $NewRecordValidator->ValidateStrings($Clean['PinCode'], 'Pin Code is required and should be 6 characters.', 6, 6);
        }

        if ($Clean['PhoneNumber'] != '')
        {
            $NewRecordValidator->ValidateStrings($Clean['PhoneNumber'], 'Phone Number is required and should be between 7 and 15 characters.', 7, 15);    
        }
        
        if ($Clean['MobileNumber1'] != '')
        {
            $NewRecordValidator->ValidateStrings($Clean['MobileNumber1'], 'Mobile Number1 is required and should be between 10 and 15 characters.', 10, 15);
        }
        
        if ($Clean['MobileNumber2'] != '')
        {
            $NewRecordValidator->ValidateStrings($Clean['MobileNumber2'], 'Mobile Number2 is required and should be between 10 and 15 characters.', 10, 15);
        }
        
        if ($Clean['ContactName'] != '')
        {
            $NewRecordValidator->ValidateStrings($Clean['ContactName'], 'Contact Name is required and should be between 3 and 30 characters.', 3, 30);
        }
        
        if ($Clean['ContactPhoneNumber'] != '')
        {
            $NewRecordValidator->ValidateStrings($Clean['ContactPhoneNumber'], 'Contact phone number is required and should be between 10 and 15 characters.', 10, 15);
        }
        
        if ($Clean['Description'] != '')
        {
            $NewRecordValidator->ValidateStrings($Clean['Description'], 'Description is required and should be between 3 and 25 characters.', 3, 25);
        }
		
		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}
				
		$NewProductVendor = new ProductVendor();
				
		$NewProductVendor->SetVendorName($Clean['VendorName']);
		$NewProductVendor->SetAddress1($Clean['Address1']);
		$NewProductVendor->SetAddress2($Clean['Address2']);
		$NewProductVendor->SetCountryID($Clean['CountryID']);
		$NewProductVendor->SetStateID($Clean['StateID']);
		$NewProductVendor->SetDistrictID($Clean['DistrictID']);
		$NewProductVendor->SetCityID($Clean['CityID']);
		$NewProductVendor->SetPinCode($Clean['PinCode']);
		$NewProductVendor->SetPhoneNumber($Clean['PhoneNumber']);
		$NewProductVendor->SetMobileNumber1($Clean['MobileNumber1']);
		$NewProductVendor->SetMobileNumber2($Clean['MobileNumber2']);
		$NewProductVendor->SetContactName($Clean['ContactName']);
		$NewProductVendor->SetContactPhoneNumber($Clean['ContactPhoneNumber']);
		$NewProductVendor->SetDescription($Clean['Description']);
		$NewProductVendor->SetIsActive(1);
		
		$NewProductVendor->SetCreateUserID($LoggedUser->GetUserID());

		if ($NewProductVendor->RecordExists())
        {
            $NewRecordValidator->AttachTextError('Vendor you have added already exists.');
            $HasErrors = true;
            break;
        }

		if (!$NewProductVendor->Save())
		{
			$NewRecordValidator->AttachTextError(ProcessErrors($NewProductVendor->GetLastErrorCode()));
			$HasErrors = true;

			break;
		}
		
		header('location:product_vendors_list.php?Mode=ED');
		exit;
		break;
}

require_once('../html_header.php');
?>
<title>Add Product Vendor</title>
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
                    <h1 class="page-header">Add Product Vendor</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="AddProductVendor" action="add_product_vendor.php" method="post">
            	<div class="panel panel-default">
                    <div class="panel-heading">
                        Enter Product Vendor Details
                    </div>
                    <div class="panel-body">
<?php
						if ($HasErrors == true)
						{
							echo $NewRecordValidator->DisplayErrors();
						}
?>                    
                    	<div class="form-group">
                            <label for="VendorName" class="col-lg-2 control-label">Vendor Name</label>
                            <div class="col-lg-4">
                            	<input class="form-control" type="text" maxlength="250" id="VendorName" name="txtVendorName" value="<?php echo $Clean['VendorName']; ?>" />
                            </div>
                        </div>
                        <div class="form-group">
                        	<label for="Address1" class="col-lg-2 control-label">Address 1</label>
                            <div class="col-lg-4">
                            	<textarea class="form-control" maxlength="250" name="txtAddress1"><?php echo $Clean['Address1'];?></textarea>
                            </div>
                            <label for="Address2" class="col-lg-2 control-label">Address 2</label>
                            <div class="col-lg-4">
                            	<textarea class="form-control" maxlength="250" name="txtAddress2"><?php echo $Clean['Address2'];?></textarea>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="CountryID" class="col-lg-2 control-label">Country</label>
                            <div class="col-lg-4">
                                <select class="form-control" name="drdCountry" id="Country">
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
                                <select class="form-control" name="drdState" id="State">
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
                                <select class="form-control" name="drdDistrict" id="District">
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
                                <select class="form-control" name="drdCity" id="City">
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
                            <label for="PinCode" class="col-lg-2 control-label">PIN Code</label>
                            <div class="col-lg-4">
                                <input class="form-control" type="PinCode" maxlength="6" id="PinCode" name="txtPinCode" value="<?php echo $Clean['PinCode']; ?>"/>
                            </div>
                            <label for="PhoneNumber" class="col-lg-2 control-label">Phone Number</label>
                            <div class="col-lg-4">
                                <input class="form-control" type="text" maxlength="15" id="PhoneNumber" name="txtPhoneNumber" value="<?php echo $Clean['PhoneNumber']; ?>" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="MobileNumber1" class="col-lg-2 control-label">Mobile Number 1</label>
                            <div class="col-lg-4">
                                <input class="form-control" type="MobileNumber1" maxlength="15" id="MobileNumber1" name="txtMobileNumber1" value="<?php echo $Clean['MobileNumber1']; ?>"/>
                            </div>
                            <label for="MobileNumber2" class="col-lg-2 control-label">Mobile Number 2</label>
                            <div class="col-lg-4">
                                <input class="form-control" type="text" maxlength="15" id="MobileNumber2" name="txtMobileNumber2" value="<?php echo $Clean['MobileNumber2']; ?>" />
                            </div>
                        </div>
                        <div class="form-group">
                        	<label for="ContactName" class="col-lg-2 control-label">Contact Name</label>
                            <div class="col-lg-4">
                                <input class="form-control" type="ContactName" maxlength="30" id="PhoneNumber" name="txtContactName" value="<?php echo $Clean['ContactName']; ?>"/>
                            </div>
                            <label for="ContactPhoneNumber" class="col-lg-2 control-label">Contact Phone Number</label>
                            <div class="col-lg-4">
                                <input class="form-control" type="text" maxlength="15" id="ContactPhoneNumber" name="txtContactPhoneNumber" value="<?php echo $Clean['ContactPhoneNumber']; ?>" />
                            </div>
                        </div>
                        <div class="form-group">
                        	<label for="Description" class="col-lg-2 control-label">Description</label>
                            <div class="col-lg-4">
                            	<textarea class="form-control" maxlength="250" name="txtDescription"><?php echo $Clean['Description'];?></textarea>
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

<script type="text/javascript">
$(document).ready(function(){ 

    $('#Country').change(function(){
        $('#State').load('/xhttp_calls/get_all_states.php', { SelectedCountryID: $(this).val() });
    });

    $('#State').change(function(){
        var StateID = parseInt($(this).val());
        
        if (StateID <= 0)
        {
            $('#District').html('<option value="0">Select District</option>');
            return;
        }
        
        $.post("/xhttp_calls/get_all_district.php", {SelectedStateID:StateID}, function(data){
        
            ResultArray = data.split("|*****|");
            
            if (ResultArray[0] == 'error')
            {
                alert (ResultArray[1]);
                return false;
            }
            else
            {
                $('#District').html(ResultArray[1]);
            }
         });
    });
    
    $('#District').change(function(){
        var DistrictID = parseInt($(this).val());
        var StateID = parseInt($('#State').val());
        
        if (DistrictID <= 0)
        {
            $('#City').html('<option value="0">Select District</option>');
            return;
        }
        
        $.post("/xhttp_calls/get_all_cities.php", {SelectedStateID:StateID, SelectedDistrictID:DistrictID}, function(data){
        
            ResultArray = data.split("|*****|");
            
            if (ResultArray[0] == 'error')
            {
                alert (ResultArray[1]);
                return false;
            }
            else
            {
                $('#City').html(ResultArray[1]);
            }
         });
    });
});
</script>

</body>
</html>
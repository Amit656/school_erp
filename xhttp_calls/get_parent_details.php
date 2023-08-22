<?php
//header('Content-Type: application/json');

require_once('../classes/class.users.php');
require_once('../classes/class.authentication.php');

try
{
	$AuthObject = new ApplicationAuthentication;
	$LoggedUser = new User(0, $AuthObject->CheckValidUser());
}

// THIS CATCH BLOCK BUBBLES THE EXCEPTION TO THE BUILT IN 'Exception' CLASS IF THERE ARE ANY UNCAUGHT ERRORS //
catch (ApplicationAuthException $e)
{
	echo 'error|*****|Unknown error, please try again.';
	exit;
}
catch (Exception $e)
{
	echo 'error|*****|Unknown error, please try again.';
	exit;
}

require_once("../classes/school_administration/class.countries.php");
require_once("../classes/school_administration/class.states.php");
require_once("../classes/school_administration/class.cities.php");
require_once('../classes/school_administration/class.parent_details.php');

$Clean['ParentID'] = 0;

if (isset($_POST['SelectedParentID']))
{
	$Clean['ParentID'] = (int) $_POST['SelectedParentID'];
}

$CountryList =  array();
$CountryList = Country::GetAllCountries();

$StateList =  array();
$CityList =  array();

$Clean['CountryID'] = key($CountryList);
$StateList = State::GetAllStates($Clean['CountryID']);

$Clean['StateID'] = key($StateList);

$DistrictList = City::GetAllDistricts($Clean['StateID']);
$Clean['DistrictID'] = key($DistrictList);

$CityList = City::GetAllCities($Clean['StateID'], $Clean['DistrictID']);
$Clean['CityID'] = key($CityList);

$Clean['FatherFirstName'] = '';
$Clean['FatherLastName'] = '';
$Clean['MotherFirstName'] = '';
$Clean['MotherLastName'] = '';

$Clean['FatherOccupation'] = '';
$Clean['MotherOccupation'] = '';
$Clean['FatherOfficeName'] = '';
$Clean['MotherOfficeName'] = '';
$Clean['FatherOfficeAddress'] = '';
$Clean['MotherOfficeAddress'] = '';

$Clean['ResidentailAddress'] = '';
$Clean['ResidentailCountryID'] = key($CountryList);

$ResidentailStateList = State::GetAllStates($Clean['ResidentailCountryID']);
$Clean['ResidentailStateID'] = key($ResidentailStateList);

$ResidentailDistrictList = City::GetAllDistricts($Clean['ResidentailStateID']);
$Clean['ResidentailDistrictID'] = key($ResidentailDistrictList);

$ResidentailCityList = City::GetAllCities($Clean['ResidentailStateID'], $Clean['ResidentailDistrictID']);
$Clean['ResidentailCityID'] = key($ResidentailCityList);

$Clean['ResidentailPinCode'] = '';

$Clean['PermanentAddress'] = '';
$Clean['PermanentCountryID'] = key($CountryList);

$PermanentStateList = State::GetAllStates($Clean['PermanentCountryID']);
$Clean['PermanentStateID'] = key($PermanentStateList);

$PermanentDistrictList = City::GetAllDistricts($Clean['PermanentStateID']);
$Clean['PermanentDistrictID'] = key($PermanentDistrictList);

$PermanentCityList = City::GetAllCities($Clean['PermanentStateID'], $Clean['PermanentDistrictID']);
$Clean['PermanentCityID'] = key($PermanentCityList);

$Clean['PermanentPinCode'] = '';
$Clean['PhoneNumber'] = '';
$Clean['FatherMobileNumber'] = '';
$Clean['MotherMobileNumber'] = '';
$Clean['FatherEmail'] = '';
$Clean['MotherEmail'] = '';

$Clean['ParentAadharNumber'] = 0;
$Clean['IsActive'] = 0;

if ($Clean['ParentID'] > 0)
{
	$ParentDetails = new ParentDetail($Clean['ParentID']);

	$Clean['FatherFirstName'] = $ParentDetails->GetFatherFirstName();
	$Clean['FatherLastName'] = $ParentDetails->GetFatherLastName();
	$Clean['MotherFirstName'] = $ParentDetails->GetMotherFirstName();
	$Clean['MotherLastName'] = $ParentDetails->GetMotherLastName();

	$Clean['FatherOccupation'] = $ParentDetails->GetFatherOccupation();
	$Clean['MotherOccupation'] = $ParentDetails->GetMotherOccupation();
	$Clean['FatherOfficeName'] = $ParentDetails->GetFatherOfficeName();
	$Clean['MotherOfficeName'] = $ParentDetails->GetMotherOfficeName();
	$Clean['FatherOfficeAddress'] = $ParentDetails->GetFatherOfficeAddress();
	$Clean['MotherOfficeAddress'] = $ParentDetails->GetMotherOfficeAddress();

	$Clean['ResidentailAddress'] = $ParentDetails->GetResidentailAddress();
	$Clean['ResidentailCityID'] = $ParentDetails->GetResidentailCityID();
	$Clean['ResidentailDistrictID'] = $ParentDetails->GetResidentailDistrictID();
	$Clean['ResidentailStateID'] = $ParentDetails->GetResidentailStateID();
	$Clean['ResidentailCountryID'] = $ParentDetails->GetResidentailCountryID();
	$Clean['ResidentailPinCode'] = $ParentDetails->GetResidentailPinCode();

	$Clean['PermanentAddress'] = $ParentDetails->GetPermanentAddress();
	$Clean['PermanentCityID'] = $ParentDetails->GetPermanentCityID();
	$Clean['PermanentDistrictID'] = $ParentDetails->GetPermanentDistrictID();
	$Clean['PermanentStateID'] = $ParentDetails->GetPermanentStateID();
	$Clean['PermanentCountryID'] = $ParentDetails->GetPermanentCountryID();
	$Clean['PermanentPinCode'] = $ParentDetails->GetPermanentPinCode();

	$Clean['PhoneNumber'] = $ParentDetails->GetPhoneNumber();
	$Clean['FatherMobileNumber'] = $ParentDetails->GetFatherMobileNumber();
	$Clean['MotherMobileNumber'] = $ParentDetails->GetMotherMobileNumber();

	$Clean['FatherEmail'] = $ParentDetails->GetFatherEmail();
	$Clean['MotherEmail'] = $ParentDetails->GetMotherEmail();

	$Clean['ParentAadharNumber'] = $ParentDetails->GetAadharNumber();
	$Clean['IsActive'] = $ParentDetails->GetIsActive();
}
?>

<div class="form-group">
    <div class="col-lg-4">
        <input class="form-control" type="hidden" maxlength="20" id="ParentID" name="txtParentID" value="<?php echo ($Clean['ParentID']) ? $Clean['ParentID'] : ''; ?>" />
    </div>
</div>
<div class="form-group">
    <label for="FatherFirstName" class="col-lg-2 control-label">Father's First Name</label>
    <div class="col-lg-4">
        <input class="form-control" type="text" maxlength="20" id="FatherFirstName" name="txtFatherFirstName" value="<?php echo $Clean['FatherFirstName']; ?>" />
    </div>
    <label for="FatherLastName" class="col-lg-2 control-label">Father's Last Name</label>
    <div class="col-lg-4">
        <input class="form-control" type="text" maxlength="30" id="FatherLastName" name="txtFatherLastName" value="<?php echo $Clean['FatherLastName']; ?>" />
    </div>
</div>
<div class="form-group">
    <label for="MotherFirstName" class="col-lg-2 control-label">Mother's First Name</label>
    <div class="col-lg-4">
        <input class="form-control" type="text" maxlength="20" id="MotherFirstName" name="txtMotherFirstName" value="<?php echo $Clean['MotherFirstName']; ?>" />
    </div>
    <label for="MotherLastName" class="col-lg-2 control-label">Mother's Last Name</label>
    <div class="col-lg-4">
        <input class="form-control" type="text" maxlength="30" id="MotherLastName" name="txtMotherLastName" value="<?php echo $Clean['MotherLastName']; ?>" />
    </div>
</div>
<div class="form-group">
    <label for="FatherOccupation" class="col-lg-2 control-label">Father's Occupation</label>
    <div class="col-lg-4">
        <input class="form-control" type="text" maxlength="30" id="FatherOccupation" name="txtFatherOccupation" value="<?php echo $Clean['FatherOccupation']; ?>" />
    </div>
    <label for="MotherOccupation" class="col-lg-2 control-label">Mother's Occupation</label>
    <div class="col-lg-4">
        <input class="form-control" type="text" maxlength="30" id="MotherOccupation" name="txtMotherOccupation" value="<?php echo $Clean['MotherOccupation']; ?>" />
    </div>
</div>
<div class="form-group">
    <label for="FatherOfficeName" class="col-lg-2 control-label">Father's Office Name</label>
    <div class="col-lg-4">
        <input class="form-control" type="text" maxlength="150" id="FatherOfficeName" name="txtFatherOfficeName" value="<?php echo $Clean['FatherOfficeName']; ?>" />
    </div>
    <label for="MotherOfficeName" class="col-lg-2 control-label">Mother's Office Name</label>
    <div class="col-lg-4">
        <input class="form-control" type="text" maxlength="150" id="MotherOfficeName" name="txtMotherOfficeName" value="<?php echo $Clean['MotherOfficeName']; ?>" />
    </div>
</div>
<div class="form-group">
    <label for="FatherOfficeAddress" class="col-lg-2 control-label">Father's Office Address</label>
    <div class="col-lg-4">
        <textarea class="form-control"  id="FatherOfficeAddress" name="txtFatherOfficeAddress"><?php echo $Clean['FatherOfficeAddress']; ?></textarea>
    </div>
    <label for="MotherOfficeAddress" class="col-lg-2 control-label">Mother's Office Address</label>
    <div class="col-lg-4">
        <textarea class="form-control"  id="MotherOfficeAddress" name="txtMotherOfficeAddress"><?php echo $Clean['MotherOfficeAddress']; ?></textarea>
    </div>
</div>
<div class="form-group">
    <label for="ResidentailAddress" class="col-lg-2 control-label">Residentail Address</label>
    <div class="col-lg-4">
        <textarea class="form-control"  id="ResidentailAddress" name="txtResidentailAddress"><?php echo $Clean['ResidentailAddress']; ?></textarea>
    </div>
    <label for="ResidentailCountryID" class="col-lg-2 control-label">Residentail Country</label>
    <div class="col-lg-4">
        <select class="form-control" name="drdResidentailCountryID" id="ResidentailCountryID">
<?php
        if (is_array($CountryList) && count($CountryList) > 0)
        {
            foreach ($CountryList as $CountryID => $CountryName) {
                echo '<option ' . ($Clean['ResidentailCountryID'] == $CountryID ? 'selected="selected"' : '') . ' value="' . $CountryID . '">' . $CountryName . '</option>';
            }
        }
?>
        </select>
    </div>
</div>
<div class="form-group">
    <label for="ResidentailStateID" class="col-lg-2 control-label">Residentail State</label>
    <div class="col-lg-4">
        <select class="form-control" name="drdResidentailStateID" id="ResidentailStateID">
<?php
        if (is_array($StateList) && count($StateList) > 0)
        {
            foreach ($StateList as $StateID => $StateName) {
                echo '<option ' . ($Clean['ResidentailStateID'] == $StateID ? 'selected="selected"' : '') . ' value="' . $StateID . '">' . $StateName . '</option>';
            }
        }
?>
        </select>
    </div>
    <label for="ResidentailDistrictID" class="col-lg-2 control-label">Residentail District</label>
    <div class="col-lg-4">
        <select class="form-control" name="drdResidentailDistrictID" id="ResidentailDistrictID">
<?php
        if (is_array($CityList) && count($CityList) > 0)
        {
            foreach ($CityList as $CityID => $CityName) {
                echo '<option ' . ($Clean['ResidentailDistrictID'] == $CityID ? 'selected="selected"' : '') . ' value="' . $CityID . '">' . $CityName . '</option>';
            }
        }
?>
        </select>
    </div>
</div>
<div class="form-group">
    <label for="ResidentailCityID" class="col-lg-2 control-label">Residentail City</label>
    <div class="col-lg-4">
        <select class="form-control" name="drdResidentailCityID" id="ResidentailCityID">
<?php
        if (is_array($CityList) && count($CityList) > 0)
        {
            foreach ($CityList as $CityID => $CityName) {
                echo '<option ' . ($Clean['ResidentailCityID'] == $CityID ? 'selected="selected"' : '') . ' value="' . $CityID . '">' . $CityName . '</option>';
            }
        }
?>
        </select>
    </div>
    <label for="ResidentailPinCode" class="col-lg-2 control-label">Residentail Pincode</label>
    <div class="col-lg-4">
        <input class="form-control" type="text" maxlength="15" id="ResidentailPinCode" name="txtResidentailPinCode" value="<?php echo $Clean['ResidentailPinCode']; ?>" />
    </div>
</div>
<div class="form-group">
    <label for="PermanentAddress" class="col-lg-2 control-label">Permanent Address</label>
    <div class="col-lg-4">
        <textarea class="form-control"  id="PermanentAddress" name="txtPermanentAddress"><?php echo $Clean['PermanentAddress']; ?></textarea>
    </div>
    <label for="PermanentCountryID" class="col-lg-2 control-label">Permanent Country</label>
    <div class="col-lg-4">
        <select class="form-control" name="txtPermanentCountryID" id="PermanentCountryID">
<?php
        if (is_array($CountryList) && count($CountryList) > 0)
        {
            foreach ($CountryList as $CountryID => $CountryName) {
                echo '<option ' . ($Clean['PermanentCountryID'] == $CountryID ? 'selected="selected"' : '') . ' value="' . $CountryID . '">' . $CountryName . '</option>';
            }
        }
?>
        </select>
    </div>
</div>
<div class="form-group">
    <label for="PermanentStateID" class="col-lg-2 control-label">Permanent State</label>
    <div class="col-lg-4">
        <select class="form-control" name="txtPermanentStateID" id="PermanentStateID">
<?php
        if (is_array($StateList) && count($StateList) > 0)
        {
            foreach ($StateList as $StateID => $StateName) {
                echo '<option ' . ($Clean['PermanentStateID'] == $StateID ? 'selected="selected"' : '') . ' value="' . $StateID . '">' . $StateName . '</option>';
            }
        }
?>
        </select>
    </div>
    <label for="PermanentDistrictID" class="col-lg-2 control-label">Permanent District</label>
    <div class="col-lg-4">
        <select class="form-control" name="txtPermanentDistrictID" id="PermanentDistrictID">
<?php
        if (is_array($CityList) && count($CityList) > 0)
        {
            foreach ($CityList as $CityID => $CityName) {
                echo '<option ' . ($Clean['PermanentDistrictID'] == $CityID ? 'selected="selected"' : '') . ' value="' . $CityID . '">' . $CityName . '</option>';
            }
        }
?>
        </select>
    </div>
</div>
<div class="form-group">
    <label for="PermanentCityID" class="col-lg-2 control-label">Permanent City</label>
    <div class="col-lg-4">
        <select class="form-control" name="txtPermanentCityID" id="PermanentCityID">
<?php
        if (is_array($CityList) && count($CityList) > 0)
        {
            foreach ($CityList as $CityID => $CityName) {
                echo '<option ' . ($Clean['PermanentCityID'] == $CityID ? 'selected="selected"' : '') . ' value="' . $CityID . '">' . $CityName . '</option>';
            }
        }
?>
        </select>
    </div>
    <label for="PermanentPinCode" class="col-lg-2 control-label">Permanent Pincode</label>
    <div class="col-lg-4">
        <input class="form-control" type="text" maxlength="6" id="PermanentPinCode" name="txtPermanentPinCode" value="<?php echo $Clean['PermanentPinCode']; ?>" />
    </div>
</div>
<div class="form-group">
    <label for="PhoneNumber" class="col-lg-2 control-label">Phone Number</label>
    <div class="col-lg-4">
        <input class="form-control" type="text" maxlength="15" id="PhoneNumber" name="txtPhoneNumber" value="<?php echo $Clean['PhoneNumber']; ?>" />
    </div>
    <label for="ParentAadharNumber" class="col-lg-2 control-label">Aadhar Number</label>
    <div class="col-lg-4">
        <input class="form-control" type="text" maxlength="12" id="ParentAadharNumber" name="txtParentAadharNumber" value="<?php echo ($Clean['ParentAadharNumber'] ? $Clean['ParentAadharNumber'] : ''); ?>" />
    </div>
</div>
<div class="form-group">
    <label for="FatherMobileNumber" class="col-lg-2 control-label">Father Mobile Number</label>
    <div class="col-lg-4">
        <input class="form-control" type="text" maxlength="15" id="FatherMobileNumber" name="txtFatherMobileNumber" value="<?php echo $Clean['FatherMobileNumber']; ?>" />
    </div>
    <label for="MotherMobileNumber" class="col-lg-2 control-label">Mother Mobile Number</label>
    <div class="col-lg-4">
        <input class="form-control" type="text" maxlength="15" id="MotherMobileNumber" name="txtMotherMobileNumber" value="<?php echo $Clean['MotherMobileNumber']; ?>" />
    </div>
</div>
<div class="form-group">
    <label for="FatherEmail" class="col-lg-2 control-label">Father Email</label>
    <div class="col-lg-4">
        <input class="form-control" type="text" maxlength="150" id="FatherEmail" name="txtFatherEmail" value="<?php echo $Clean['FatherEmail']; ?>" />
    </div>
    <label for="MotherEmail" class="col-lg-2 control-label">Mother Email</label>
    <div class="col-lg-4">
        <input class="form-control" type="text" maxlength="150" id="MotherEmail" name="txtMotherEmail" value="<?php echo $Clean['MotherEmail']; ?>" />
    </div>
</div>

<?php
exit;
?>
<?php
require_once '../includes/global_defaults.inc.php';
require_once '../classes/class.helpers.php';

try
{
	$DBConnObject = new DBConnect();

	$RSSearchClosedCustomers = $DBConnObject->Prepare('SELECT branchStaffID, userName, firstName, lastName, dob FROM asa_branch_staff;');
	$RSSearchClosedCustomers->Execute();

	if ($RSSearchClosedCustomers->Result->num_rows <= 0)
	{
		echo 'No Records Found.';
		exit;
	}

	while ($SearchRow = $RSSearchClosedCustomers->FetchRow())
	{
	    $Username = $SearchRow->userName;
		
		$Password = strtoupper( substr($SearchRow->firstName . $SearchRow->lastName, 0, 4) . date('Y', strtotime($SearchRow->dob)) );
		echo $Username . '--' .$Password .'<br />';continue;
		
		$Username = Helpers::GenerateUniqueAddedID($SearchRow->firstName . $SearchRow->lastName, $SearchRow->dob);
		$Password = strtoupper( substr($SearchRow->firstName . $SearchRow->lastName, 0, 4) . date('Y', strtotime($SearchRow->dob)) );
		
		$RSUpdateBranchStaff = $DBConnObject->Prepare('UPDATE asa_branch_staff SET userName = :|1 WHERE branchStaffID = :|2 LIMIT 1;');
		$RSUpdateBranchStaff->Execute($Username, $SearchRow->branchStaffID);
		
		$RSCreateStaffLogin = $DBConnObject->Prepare('INSERT INTO users (userName, password, roleID, isActive, createUserID, createDate) VALUES (:|1, :|2, :|3, :|4, :|5, NOW());');
		$RSCreateStaffLogin->Execute($Username, SHA1($Password), ROLE_SITE_FACULTY, 1, 1000001);
		
		if (!Helpers::SaveUniqueID($Username))
		{
			throw new Exception('Generated unique ID could not be saved into master db.');
		}
	}
}
catch (ApplicationDBException $e)
{
	error_log('DEBUG: ApplicationDBException at script.php. Stack Trace: '.$e->getTraceAsString());
	exit;
}
catch (Exception $e)
{
	error_log('DEBUG: Exception at script.php. Stack Trace: '.$e->getTraceAsString());
	exit;
}
?>
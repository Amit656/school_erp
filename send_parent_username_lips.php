<?php
require_once 'classes/class.helpers.php';

$Counter = 0;
$DBConnectObj = new DBConnect();

$RSSearchParent = $DBConnectObj->Prepare('SELECT pd.*, u.password 
										  FROM asa_parent_details pd 
										  INNER JOIN users u ON u.userName = pd.userName 
										  WHERE pd.parentID IN (SELECT parentID FROM asa_students);');
$RSSearchParent->Execute();

while ($SearchParentsRow = $RSSearchParent->FetchRow())
{
	$Clean = array();
	
	$Clean['ParentID'] = $SearchParentsRow->parentID;
	$Clean['Username'] = $SearchParentsRow->userName;
	$Clean['Password'] = $SearchParentsRow->password;
	
	$RSSearchChild = $DBConnectObj->Prepare('SELECT dob 
											 FROM asa_students s 
											 INNER JOIN asa_student_details asd ON asd.studentID = s.studentID 
											 WHERE s.parentID = :|1 
											 ORDER BY createDate LIMIT 1;');
	
	$RSSearchChild->Execute($Clean['ParentID']);
	
	$DOB = date('Y', strtotime($RSSearchChild->FetchRow()->dob));
	
	$Password = strtoupper(substr($Clean['Username'], 0, 4)) . $DOB;
	
	$RSUpdatePassword = $DBConnectObj->Prepare('UPDATE users SET password = :|1 WHERE userName = :|2 LIMIT 1;');
	$RSUpdatePassword->Execute(sha1($Password), $Clean['Username']);

	if ($SearchParentsRow->fatherMobileNumber == '')
	{
		continue;
	}
	
	$SMSMessage = 'Dear Parents, Welcome to ADD-Ed services. 

Please click the below link to download the parent app. Link - https://play.google.com/store/apps/details?id=app.added.parents 

Your login details are: 
Login ID: '.$Clean['Username'].'
Login Password: '.$Password.' 

Regards, 
Lucknow International Public School. 

For Any Query Contact: 7007461045, 8303403734 
Timing For the Call - 10:00 AM to 5:00 PM.

IGNORE IF ALREADY DOWNLOADED';
	
	$SaveSMS = $DBConnectObj->Prepare('INSERT INTO sms_queue (phoneNumber, smsMessage, createDate) VALUES (:|1, :|2, NOW());');
	$SaveSMS->Execute($SearchParentsRow->fatherMobileNumber, $SMSMessage);
	
	$Counter++;
}

echo 'success' . $Counter;
exit;
?>
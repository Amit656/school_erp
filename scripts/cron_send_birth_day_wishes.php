<?php
//if (php_sapi_name() !='cli') exit;

error_log('Send Birthday SMS Cron Run Start: ' . date('d/m/Y h:i A'));

require_once('../classes/class.db_connect.php');
require_once("../classes/class.sms_queue.php");
require_once("../classes/school_administration/class.sms_templates.php");

try
{
	$DBConnObject = new DBConnect();

	$DBConnObject->BeginTransaction();
	
	$RSStudentBirthday = $DBConnObject->Prepare('SELECT asd.firstName, asd.lastName, asd.dob, asd.mobileNumber, 
													apd.fatherFirstName, apd.fatherLastName, apd.motherFirstName, apd.motherLastName
													FROM asa_student_details asd
													INNER JOIN asa_students ast ON ast.studentID = asd.studentID
													INNER JOIN asa_parent_details apd ON apd.parentID = ast.parentID
													WHERE MONTH(dob) = MONTH(CURDATE())
													AND DAY(dob) = DAY(CURDATE())
													AND ast.status = "Active";');
    $RSStudentBirthday->Execute();
	
	if ($RSStudentBirthday->Result->num_rows <= 0)
    {
		echo 'success';
		exit;
    }
	
	while ($StudentBirthdayRow = $RSStudentBirthday->FetchRow())
	{
		try
		{
			$NewSMSTemplate = new SMSTemplate(0 , 'StudentBirthday');
			$NewSMSQueue = new SMSQueue();
		}

		catch (ApplicationDBException $e)
		{
			echo 'error';
			exit;
		}
		catch (Exception $e)
		{
			echo 'error';
			exit;
		}

		if ($NewSMSTemplate->GetIsActive())
        {
            $MSGContent = $NewSMSTemplate->GetSMSTemplate();

            $MSGContent = str_replace('{StudentFirstName}', $StudentBirthdayRow->firstName, $MSGContent);
            $MSGContent = str_replace('{StudentLastName}', $StudentBirthdayRow->lastName, $MSGContent);
            $MSGContent = str_replace('{FatherName}', $StudentBirthdayRow->fatherFirstName . ' ' . $StudentBirthdayRow->fatherLastName, $MSGContent);
            $MSGContent = str_replace('{MotherName}', $StudentBirthdayRow->motherFirstName . ' ' . $StudentBirthdayRow->motherLastName, $MSGContent);
            $MSGContent = str_replace('{Class}', '', $MSGContent);
            $MSGContent = str_replace('{Section}', '', $MSGContent);
            $MSGContent = str_replace('{EntranceExamDateTime}', '', $MSGContent);            

            $NewSMSQueue->SetPhoneNumber($StudentBirthdayRow->mobileNumber);
            $NewSMSQueue->SetSMSMessage($MSGContent);
            $NewSMSQueue->SetCreateUserID('1000005');

            $NewSMSQueue->Save();
        }
	}

	$DBConnObject->CommitTransaction();
	
	echo 'success';
	exit;
}
catch (ApplicationDBException $e)
{
	$DBConnObject->RollBackTransaction();
	echo 'error';
	exit;
}
catch (Exception $e)
{
	$DBConnObject->RollBackTransaction();
	echo 'error';
	exit;
}
?>


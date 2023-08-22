<?php
//if (php_sapi_name() !='cli') exit;

error_log('Send Follow Up SMS Cron Run Start: ' . date('d/m/Y h:i A'));

require_once('../classes/class.db_connect.php');
require_once("../classes/class.sms_queue.php");
require_once("../classes/school_administration/class.sms_templates.php");

try
{
	$DBConnObject = new DBConnect();

	$DBConnObject->BeginTransaction();
	
	$RSFolloUpStudent = $DBConnObject->Prepare('SELECT ae.firstName, ae.lastName, ae.fatherName, ae.motherName, ae.mobileNumber, ac.className
												FROM aad_enquiries ae
												INNER JOIN asa_classes ac ON ac.classID = ae.classID 
												WHERE isAdmissionTaken = 0;');
    $RSFolloUpStudent->Execute();
	
	if ($RSFolloUpStudent->Result->num_rows <= 0)
    {
		echo 'success';
		exit;
    }
	
	while ($FolloUpStudentRow = $RSFolloUpStudent->FetchRow())
	{
		try
		{
			$NewSMSTemplate = new SMSTemplate(0 , 'EnquiryFollowUp');
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

            $MSGContent = str_replace('{StudentFirstName}', $FolloUpStudentRow->firstName, $MSGContent);
            $MSGContent = str_replace('{StudentLastName}', $FolloUpStudentRow->lastName, $MSGContent);
            $MSGContent = str_replace('{FatherName}', $FolloUpStudentRow->fatherName, $MSGContent);
            $MSGContent = str_replace('{MotherName}', $FolloUpStudentRow->motherName, $MSGContent);
            $MSGContent = str_replace('{Class}', $FolloUpStudentRow->className, $MSGContent);
            $MSGContent = str_replace('{Section}', '', $MSGContent);
            $MSGContent = str_replace('{EntranceExamDateTime}', '', $MSGContent);            

            $NewSMSQueue->SetPhoneNumber($FolloUpStudentRow->mobileNumber);
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


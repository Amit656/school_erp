<?php
require_once('class.db_connect.php');
require_once('class.date_processing.php');

require_once('class.fcm_push.php');
require_once('class.fcm_firebase.php');

error_reporting(E_ALL);

class FcmSendNotification
{
    private $DeviceTokens = array();
    
    static function SendSubstitutionNotification($SubstitutionsList)
    {

		try
		{
			$DBConnObject = new DBConnect();

			foreach ($SubstitutionsList as $ClassTimeTableDetailID => $TeacherClassID)
			{
			    if ($TeacherClassID > 0)
			    {
			        $Message = '';
			    
    				$RSSearchFcmTokens = $DBConnObject->Prepare('SELECT DISTINCT(us.fcmToken) FROM asa_teacher_classes atc 
    				                                                INNER JOIN asa_branch_staff abs ON abs.branchStaffID = atc.branchStaffID
    				                                                INNER JOIN users u ON u.userName = abs.userName
    				                                                INNER JOIN user_sessions us ON us.userID = u.userID
    				                                                WHERE atc.teacherClassID = :|1 AND us.fcmToken != "";');
    				$RSSearchFcmTokens->Execute($TeacherClassID);
    				
    				$RSSearchClassSubject = $DBConnObject->Prepare('SELECT sm.subject, ac.className, asm.sectionName FROM asa_class_time_table_details acttd 
    				                                                INNER JOIN asa_class_time_table actt ON actt.classTimeTableID = acttd.classTimeTableID
    				                                                INNER JOIN asa_class_sections cs ON cs.classSectionID = actt.classSectionID
    				                                                INNER JOIN asa_section_master asm ON asm.sectionMasterID = cs.sectionMasterID
    				                                                INNER JOIN asa_class_subjects acs ON acs.classSubjectID = acttd.classSubjectID
    				                                                INNER JOIN asa_classes ac ON ac.classID = acs.classID
    				                                                INNER JOIN asa_subject_master sm ON sm.subjectID = acs.subjectID
    				                                                WHERE acttd.classTimeTableDetailID = :|1;');
    				$RSSearchClassSubject->Execute($ClassTimeTableDetailID);
    				
    				$SearchClassSubject = $RSSearchClassSubject->FetchRow();
    				$Message = 'Class '. $SearchClassSubject->className .' '. $SearchClassSubject->sectionName .' '. $SearchClassSubject->subject;
    				// $Message = 'Message';
    				if ($RSSearchFcmTokens->Result->num_rows > 0)
                    {
                        $Counter = 0;
                        while($SearchRow = $RSSearchFcmTokens->FetchRow())
                        {
                            $DeviceTokens[$Counter++] = $SearchRow->fcmToken;
                        }
                        
                        FcmSendNotification::SendPush('Substitution', $Message, '', '{Substitution}', $DeviceTokens, $AppCategory = 'Teacher');
                    }
			    }
			    
			}

			return true;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at FcmSendNotification::SendSubstitutionNotification(). Stack Trace: ' . $e->getTraceAsString());
			return false;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at FcmSendNotification::SendSubstitutionNotification(). Stack Trace: ' . $e->getTraceAsString());
			return false;
		}
	}
	
	static function SendNoticeNotification($NoticeCircularSubject, $NoticeCircularApplicableFor)
    {

		try
		{
			$DBConnObject = new DBConnect();

			foreach ($NoticeCircularApplicableFor as $Key => $NoticeCircularApplicableForDetails) 
			{
			    if ($NoticeCircularApplicableForDetails['ApplicableFor'] == 'Staff')
			    {
			        $Message = '';
			    
    				$RSSearchFcmTokens = $DBConnObject->Prepare('SELECT DISTINCT(us.fcmToken) FROM asa_branch_staff abs 
    				                                                INNER JOIN users u ON u.userName = abs.userName
    				                                                INNER JOIN user_sessions us ON us.userID = u.userID
    				                                                WHERE abs.branchStaffID = :|1 AND us.fcmToken != "";');
    				$RSSearchFcmTokens->Execute($NoticeCircularApplicableForDetails['StaffOrClassID']);
    				
    				$Message = $NoticeCircularSubject;
    				
    				if ($RSSearchFcmTokens->Result->num_rows > 0)
                    {
                        $Counter = 0;
                        while($SearchRow = $RSSearchFcmTokens->FetchRow())
                        {
                            $DeviceTokens[$Counter++] = $SearchRow->fcmToken;
                        }
                        
                        FcmSendNotification::SendPush('Notice', $Message, '', '{Notice}', $DeviceTokens, $AppCategory = 'Teacher');
                    }
			    }
			    
			    if ($NoticeCircularApplicableForDetails['ApplicableFor'] == 'Class')
			    {
			        $Message = '';
			        
			        $RSSearchFcmTokens = $DBConnObject->Prepare('SELECT DISTINCT(us.fcmToken) FROM asa_parent_details apd 
    				                                                INNER JOIN asa_students ass ON ass.parentID = apd.parentID
    				                                                INNER JOIN users u ON u.userName = apd.userName
    				                                                INNER JOIN user_sessions us ON us.userID = u.userID
    				                                                WHERE ass.classSectionID IN (SELECT classSectionID FROM asa_class_sections WHERE classID = :|1) AND us.fcmToken != "";');
    				$RSSearchFcmTokens->Execute($NoticeCircularApplicableForDetails['StaffOrClassID']);
    				
    				$Message = $NoticeCircularSubject;
    				
    				if ($RSSearchFcmTokens->Result->num_rows > 0)
                    {
                        $Counter = 0;
                        while($SearchRow = $RSSearchFcmTokens->FetchRow())
                        {
                            $DeviceTokens[$Counter++] = $SearchRow->fcmToken;
                        }
                        
                        FcmSendNotification::SendPush('Notice', $Message, '', '{Notice}', $DeviceTokens, $AppCategory = 'Parent');
                    }
			    }
			}

			return true;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at FcmSendNotification::SendNoticeNotification(). Stack Trace: ' . $e->getTraceAsString());
			return false;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at FcmSendNotification::SendNoticeNotification(). Stack Trace: ' . $e->getTraceAsString());
			return false;
		}
	}
	
	static function SendChatNotification($SenderType, $SenderID, $ReceiverType, $ReceiverID, $Message)
    {

		try
		{
			$DBConnObject = new DBConnect();

			if ($ReceiverType == 'Teacher')
		    {
				$RSSearchFcmTokens = $DBConnObject->Prepare('SELECT DISTINCT(us.fcmToken) FROM asa_branch_staff abs 
				                                                INNER JOIN users u ON u.userName = abs.userName
				                                                INNER JOIN user_sessions us ON us.userID = u.userID
				                                                WHERE abs.branchStaffID = :|1 AND us.fcmToken != "";');
				$RSSearchFcmTokens->Execute($ReceiverID);
				
				if ($RSSearchFcmTokens->Result->num_rows > 0)
                {
                    $Counter = 0;
                    $Title =  'Chat';
                    
                    while($SearchRow = $RSSearchFcmTokens->FetchRow())
                    {
                        $DeviceTokens[$Counter++] = $SearchRow->fcmToken;
                    }
                    
                    if ($SenderType == 'Teacher')
                    {
                        $RSSearchTeacher = $DBConnObject->Prepare('SELECT firstName, lastName FROM asa_branch_staff  
    				                                                WHERE branchStaffID = :|1;');
    				    $RSSearchTeacher->Execute($SenderID);
    				    
    				    if ($RSSearchTeacher->Result->num_rows > 0)
    				    {
    				        $SearchSenderRow = $RSSearchTeacher->FetchRow();
    				        $Title = $SearchSenderRow->firstName .' '. $SearchSenderRow->lastName;   
    				    }
                    }
                    else
                    {
                        $RSSearchStudent = $DBConnObject->Prepare('SELECT firstName, lastName FROM asa_student_details  
				                                                WHERE studentID = :|1;');
    				    $RSSearchStudent->Execute($SenderID);
    				    
    				    if ($RSSearchStudent->Result->num_rows > 0)
    				    {
    				        $SearchSenderRow = $RSSearchStudent->FetchRow();
    				        $Title = $SearchSenderRow->firstName .' '. $SearchSenderRow->lastName;  
    				    }
                    }
                    
                    
                    FcmSendNotification::SendPush($Title, $Message, '', '{Chat Message}', $DeviceTokens, $AppCategory = 'Teacher');
                }
		    }
		    
		    if ($ReceiverType == 'Parent')
		    {
		        $RSSearchFcmTokens = $DBConnObject->Prepare('SELECT DISTINCT(us.fcmToken) FROM asa_parent_details apd 
				                                                INNER JOIN asa_students ass ON ass.parentID = apd.parentID
				                                                INNER JOIN asa_student_details asd ON asd.studentID = ass.studentID
				                                                INNER JOIN users u ON u.userName = apd.userName
				                                                INNER JOIN user_sessions us ON us.userID = u.userID
				                                                WHERE apd.parentID = :|1 AND us.fcmToken != "";');
				$RSSearchFcmTokens->Execute($ReceiverID);
				
				if ($RSSearchFcmTokens->Result->num_rows > 0)
                {
                    $Counter = 0;
                    $Title =  'Chat';
                    
                    while($SearchRow = $RSSearchFcmTokens->FetchRow())
                    {
                        $DeviceTokens[$Counter++] = $SearchRow->fcmToken;
                    }
                    
                    if ($SenderType == 'Teacher')
                    {
                        $RSSearchTeacher = $DBConnObject->Prepare('SELECT firstName, lastName FROM asa_branch_staff  
    				                                                WHERE branchStaffID = :|1;');
    				    $RSSearchTeacher->Execute($SenderID);
    				    
    				    if ($RSSearchTeacher->Result->num_rows > 0)
    				    {
    				        $SearchSenderRow = $RSSearchTeacher->FetchRow();
    				        $Title = $SearchSenderRow->firstName .' '. $SearchSenderRow->lastName; 
    				    }
                    }
                    else
                    {
                        $RSSearchStudent = $DBConnObject->Prepare('SELECT firstName, lastName FROM asa_student_details  
				                                                WHERE studentID = :|1;');
    				    $RSSearchStudent->Execute($SenderID);
    				    
    				    if ($RSSearchStudent->Result->num_rows > 0)
    				    {
    				        $SearchSenderRow = $RSSearchStudent->FetchRow();
    				        $Title = $SearchSenderRow->firstName .' '. $SearchSenderRow->lastName; 
    				    }
                    }
				    
                    FcmSendNotification::SendPush($Title, $Message, '', '{Chat Message}', $DeviceTokens, $AppCategory = 'Parent');
                }
		    }

			return true;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at FcmSendNotification::SendChatNotification(). Stack Trace: ' . $e->getTraceAsString());
			return false;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at FcmSendNotification::SendChatNotification(). Stack Trace: ' . $e->getTraceAsString());
			return false;
		}
	}
	
	static function SendPush($Title, $Message, $ImageUrl = '', $Payload, $DeviceTokens, $AppCategory)
	{
	    try
	    {
	        $PushObject = new Push();

    		$PushObject->SetTitle($Title);
    		$PushObject->SetMessage($Message);
    		$PushObject->SetImage($ImageUrl);
    		
     		$PushObject->SetPayload($Payload);
    
    		$PushNotification = $PushObject->GetPush();
    // 		echo '<pre>';
    // 		var_dump($DeviceTokens);
    		
            // $DeviceTokens = ["d17URLwExBk:APA91bEILeLdmVqAraTSSDdFOybKoIq5_vjeIfE4tC7D4HNIp3AEHfjmIAj-m1yaJT-XkkBMdX_cpEv7lnX8GMl3632fcp9_oqCL_l6Zv3alJEVzuWRIMuS3hAbsH8xef-nHKwjG9cWK"];
    		//sending push notification and displaying result 
    // 		var_dump($AppCategory);exit;
            
            if ($AppCategory == 'Teacher')
            {
                $ApiKey = 'AAAAuzFc9iM:APA91bEXAiIWyX8bPDAHimceD6OGqOFoKr9pGfnST4UZZvQTWQX7mTwUNo65nQnxZ8K6iU34ayZO2ZG3lncj_2NEILcfzQsqVH3zN1oPwQa1S-hMP2jCCHYBcqSyzISKjBpgNmCsiif-';
            }
            else if ($AppCategory == 'Parent')
            {
                $ApiKey = 'AAAA2dhw7rY:APA91bHyoM2sNTKsWPNfJaUqPOMq6US7jgCkre6cDJz_U31PlgLc0NaRJETkY2YJYZafPNaeED7AQP89QjbOOpSZsSS1jo_kGueauWbUCuhhoOaEvUjpLzQyA94LW_C-F58h_lvElDu_';
            }
            
            $FirebaseObject = new Firebase(); 
    		$FirebaseObject->Send($DeviceTokens, $PushNotification, $ApiKey);
	    
	        return true;
	    }
	    catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at FcmSendNotification::SendPush(). Stack Trace: ' . $e->getTraceAsString());
			return false;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at FcmSendNotification::SendPush(). Stack Trace: ' . $e->getTraceAsString());
			return false;
		}
	}
}
?>
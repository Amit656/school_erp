<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.db_connect.php');
error_reporting(E_ALL);

class AppParentDetail extends ParentDetail
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	private $UniqueAppToken;
	
	private $ClassID;
	private $ClassSectionID;

	// PUBLIC METHODS START HERE	//
	public function __construct($UniqueAppToken)
	{
		$this->UniqueAppToken = $UniqueAppToken;
		
		$this->DBObject = new DBConnect;

		$this->GetParentDetailByUniqueAppToken();

		parent::__construct($this->ParentID);
		
// 		$this->GetClassSectionDetails();
	}
	
	public function GetClassSectionID()
	{
		return $this->ClassSectionID;
	}
	
	public function GetClassID()
	{
		return $this->ClassID;
	}
	
	//  END OF GETTER AND SETTER FUNCTIONS 	//
	public function GetUserInfo()
	{
		$UserInfo = array();

		try
        {
	        $RSSearch = $this->DBObject->Prepare('SELECT apd.*, aas.studentID, asd.firstName, asd.lastName, asd.dob, asd.address1, asd.studentPhoto, ac.className, ac.classSymbol, asm.sectionName, ac.classID, aas.classSectionID 
												  FROM asa_parent_details apd 
												  INNER JOIN asa_students aas ON apd.parentID = aas.parentID 
												  INNER JOIN asa_student_details asd ON asd.studentID = aas.studentID 
												  
												  INNER JOIN asa_class_sections acs ON acs.classSectionID = aas.classSectionID
												  INNER JOIN asa_classes ac ON ac.classID = acs.classID 
												  INNER JOIN asa_section_master asm ON asm.sectionMasterID = acs.sectionMasterID 
												  
												  WHERE apd.parentID = :|1 LIMIT 1;');

            $RSSearch->Execute($this->ParentID);

            if ($RSSearch->Result->num_rows <= 0)
            {
                return $UserInfo;
            }

			$SearchUserInfoRow = $RSSearch->FetchRow();
			
			$UserInfo['FatherFirstName'] = $SearchUserInfoRow->fatherFirstName;
			$UserInfo['FatherLastName'] = $SearchUserInfoRow->fatherLastName;
			
			$UserInfo['MotherFirstName'] = $SearchUserInfoRow->motherFirstName;
			$UserInfo['MotherLastName'] = $SearchUserInfoRow->motherLastName;
			
			$UserInfo['PhoneNumber'] = $SearchUserInfoRow->phoneNumber;
			$UserInfo['FatherMobileNumber'] = $SearchUserInfoRow->fatherMobileNumber;
			$UserInfo['MotherMobileNumber'] = $SearchUserInfoRow->motherMobileNumber;
			
			$UserInfo['StudentID'] = $SearchUserInfoRow->studentID;
			$UserInfo['FirstName'] = $SearchUserInfoRow->firstName;
			$UserInfo['LastName'] = $SearchUserInfoRow->lastName;
			$UserInfo['DOB'] = date('d/m/Y', strtotime($SearchUserInfoRow->dob)) ;
			$UserInfo['Address'] = $SearchUserInfoRow->address1;
			$UserInfo['StudentPhoto'] = $SearchUserInfoRow->studentID . '/' . $SearchUserInfoRow->studentPhoto;
			
			$UserInfo['ClassTeacherClassName'] = $SearchUserInfoRow->className;
			$UserInfo['ClassTeacherClassSymbol'] = $SearchUserInfoRow->classSymbol;
			$UserInfo['SectionName'] = $SearchUserInfoRow->sectionName;
			
			$UserInfo['ClassID'] = $SearchUserInfoRow->classID;
			$UserInfo['ClassSectionID'] = $SearchUserInfoRow->classSectionID;

            return $UserInfo;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at AppParentDetail::GetUserInfo(). Stack Trace: ' . $e->getTraceAsString());
            return $UserInfo;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception at AppParentDetail::GetUserInfo(). Stack Trace: ' . $e->getTraceAsString());
            return $UserInfo;
        }
	}
	
	public function GetApplicableStudents()
	{
		$StudentDetails = array();

		try
        {
	        $RSSearch = $this->DBObject->Prepare('SELECT aas.studentID, aas.classSectionID, aas.rollNumber, asd.firstName, asd.lastName, asd.address1, asd.studentPhoto, ac.classID, ac.className, ac.classSymbol, asm.sectionName 
												  FROM asa_parent_details apd 
												  INNER JOIN asa_students aas ON apd.parentID = aas.parentID 
												  INNER JOIN asa_student_details asd ON asd.studentID = aas.studentID 
												  
												  INNER JOIN asa_class_sections acs ON acs.classSectionID = aas.classSectionID
												  INNER JOIN asa_classes ac ON ac.classID = acs.classID 
												  INNER JOIN asa_section_master asm ON asm.sectionMasterID = acs.sectionMasterID 
												  
												  WHERE apd.parentID = :|1;');

            $RSSearch->Execute($this->ParentID);

            if ($RSSearch->Result->num_rows <= 0)
            {
                return $StudentDetails;
            }

			while ($SearchStudentDetailsRow = $RSSearch->FetchRow())
			{
				$StudentDetails[$SearchStudentDetailsRow->studentID]['StudentID'] = $SearchStudentDetailsRow->studentID;
				$StudentDetails[$SearchStudentDetailsRow->studentID]['FirstName'] = $SearchStudentDetailsRow->firstName;
				$StudentDetails[$SearchStudentDetailsRow->studentID]['LastName'] = $SearchStudentDetailsRow->lastName;
				$StudentDetails[$SearchStudentDetailsRow->studentID]['StudentPhoto'] = $SearchStudentDetailsRow->studentID . '/' . $SearchStudentDetailsRow->studentPhoto;
				
				$StudentDetails[$SearchStudentDetailsRow->studentID]['RollNumber'] = $SearchStudentDetailsRow->rollNumber;
				$StudentDetails[$SearchStudentDetailsRow->studentID]['ClassName'] = $SearchStudentDetailsRow->className;
				$StudentDetails[$SearchStudentDetailsRow->studentID]['ClassSymbol'] = $SearchStudentDetailsRow->classSymbol;
				$StudentDetails[$SearchStudentDetailsRow->studentID]['SectionName'] = $SearchStudentDetailsRow->sectionName;

				$StudentDetails[$SearchStudentDetailsRow->studentID]['ClassID'] = $SearchStudentDetailsRow->classID;
				$StudentDetails[$SearchStudentDetailsRow->studentID]['ClassSectionID'] = $SearchStudentDetailsRow->classSectionID;
				
				$StudentDetails[$SearchStudentDetailsRow->studentID]['Address'] = $SearchStudentDetailsRow->address1;
			}

            return $StudentDetails;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at AppParentDetail::GetApplicableStudents(). Stack Trace: ' . $e->getTraceAsString());
            return $StudentDetails;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception at AppParentDetail::GetApplicableStudents(). Stack Trace: ' . $e->getTraceAsString());
            return $StudentDetails;
        }
	}
	
	public function GetApplicableTeachers()
	{
		$TeachersList = array();

		try
        {
	       // $RSSearch = $this->DBObject->Prepare('SELECT abs.branchStaffID, abs.firstName, abs.lastName, abs.staffPhoto
								// 				  FROM asa_class_time_table actt 
								// 				  INNER JOIN asa_class_time_table_details acttd ON acttd.classTimeTableID = actt.classTimeTableID 
								// 				  INNER JOIN asa_teacher_classes atc ON atc.teacherClassID = acttd.teacherClassID 
								// 				  INNER JOIN asa_branch_staff abs ON abs.branchStaffID = atc.branchStaffID 
								// 				  LEFT JOIN asa_chat_rooms acr ON 
        //                                 			((acr.firstUserType = \'Teacher\' AND acr.firstRecordID = abs.branchStaffID) AND (acr.secondUserType = \'Parent\' AND acr.secondRecordID = :|1)) OR 
        //                                 			((acr.firstUserType = \'Parent\' AND acr.firstRecordID = :|2) AND (acr.secondUserType = \'Teacher\' AND acr.secondRecordID = abs.branchStaffID)) 
								// 				  LEFT JOIN asa_chat_room_messages acrm ON acrm.chatRoomID = acr.chatRoomID
								// 				  WHERE actt.classSectionID = :|3 
								// 				  GROUP BY abs.branchStaffID 
								// 				  ORDER BY acrm.createDate DESC;');

        //     $RSSearch->Execute($this->ParentID, $this->ParentID, $this->ClassSectionID);
            
            $RSSearch = $this->DBObject->Prepare('SELECT abs.branchStaffID, abs.firstName, abs.lastName, abs.staffPhoto
												  FROM asa_branch_staff abs 
												  WHERE abs.branchStaffID = :|1;');

            $RSSearch->Execute(156); // 156 branchStaffID of supporthelp@added	

            if ($RSSearch->Result->num_rows <= 0)
            {
                return $TeachersList;
            }

			while ($SearchTeachersRow = $RSSearch->FetchRow())
			{
				$TeachersList[$SearchTeachersRow->branchStaffID]['BranchStaffID'] = $SearchTeachersRow->branchStaffID;
				$TeachersList[$SearchTeachersRow->branchStaffID]['FirstName'] = $SearchTeachersRow->firstName;
				$TeachersList[$SearchTeachersRow->branchStaffID]['LastName'] = $SearchTeachersRow->lastName;
				
				$TeachersList[$SearchTeachersRow->branchStaffID]['Image'] = '';
				
				if (file_exists(SITE_FS_PATH . '/site_images/branch_staff_images/' . $SearchTeachersRow->branchStaffID . '/' . $SearchTeachersRow->staffPhoto)) 
                {   
                    $TeachersList[$SearchTeachersRow->branchStaffID]['Image'] = SITE_HTTP_PATH . '/site_images/branch_staff_images/' . $SearchTeachersRow->branchStaffID . '/' . $SearchTeachersRow->staffPhoto;
                }
				
				$TeachersList[$SearchTeachersRow->branchStaffID]['LastMessage'] = '';
				$TeachersList[$SearchTeachersRow->branchStaffID]['IsActive'] = 1;
				
				$TeachersList[$SearchTeachersRow->branchStaffID]['Subjects'] = array();
				
				# NOW FETCH Teacher's Subject
				$RSSearchTeacherSubject = $this->DBObject->Prepare('SELECT DISTINCT(sm.subject), cs.classSubjectID  FROM asa_class_time_table ctt
                                                                    INNER JOIN asa_class_time_table_details cttd ON cttd.classTimeTableID = ctt.classTimeTableID
                                                                    INNER JOIN asa_teacher_classes tc ON tc.teacherClassID = cttd.teacherClassID
                                                                    INNER JOIN asa_class_subjects cs ON cs.classSubjectID = cttd.classSubjectID
                                                                    INNER JOIN asa_subject_master sm ON sm.subjectID = cs.subjectID
                                                                    WHERE ctt.`classSectionID` = :|1 AND tc.branchStaffID = :|2;');
				
				$RSSearchTeacherSubject->Execute($this->ClassSectionID, $SearchTeachersRow->branchStaffID);
				
				if ($RSSearchTeacherSubject->Result->num_rows > 0)
				{
				    $Counter = 0;
					while ($SearchTeacherSubject = $RSSearchTeacherSubject->FetchRow())
					{
					    $TeachersList[$SearchTeachersRow->branchStaffID]['Subjects'][$Counter]['ClassSubjectID'] = $SearchTeacherSubject->classSubjectID;   
					    $TeachersList[$SearchTeachersRow->branchStaffID]['Subjects'][$Counter]['Subject'] = $SearchTeacherSubject->subject;   
					    
					    $Counter++;
					}
				}
				else
				{
				    $TeachersList[$SearchTeachersRow->branchStaffID]['Subjects'][0]['ClassSubjectID'] = '0';   
			        $TeachersList[$SearchTeachersRow->branchStaffID]['Subjects'][0]['Subject'] = 'Support';  
				}
				
				# NOW FETCH LAST MESSAGE OF THE CHAT
				$RSSearchChatRoom = $this->DBObject->Prepare('SELECT acrm.message, acrm.isSeen, acr.isActive FROM asa_chat_room_messages acrm 
															  INNER JOIN asa_chat_rooms acr ON acr.chatRoomID = acrm.chatRoomID 
															  WHERE ( (acr.firstUserType = \'Parent\' AND acr.firstRecordID = :|1) AND (acr.secondUserType != \'Parent\' AND acr.secondRecordID = :|2) ) 
																	  OR 
																	( (acr.secondUserType = \'Parent\' AND acr.secondRecordID = :|3) AND (acr.firstUserType != \'Parent\' AND acr.firstRecordID = :|4) ) 
															  ORDER BY acrm.chatRoomMessageID DESC LIMIT 1;');
				
				$RSSearchChatRoom->Execute($this->ParentID, $SearchTeachersRow->branchStaffID, $this->ParentID, $SearchTeachersRow->branchStaffID);
				
				if ($RSSearchChatRoom->Result->num_rows > 0)
				{
					$SearchChatRoomRow = $RSSearchChatRoom->FetchRow();
					
					$TeachersList[$SearchTeachersRow->branchStaffID]['LastMessage'] = $SearchChatRoomRow->message;
					$TeachersList[$SearchTeachersRow->branchStaffID]['IsSeen'] = $SearchChatRoomRow->isSeen;
					$TeachersList[$SearchTeachersRow->branchStaffID]['IsActive'] = $SearchChatRoomRow->isActive;
				}
			}
			
            return $TeachersList;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at AppParentDetail::GetApplicableTeachers(). Stack Trace: ' . $e->getTraceAsString());
            return $TeachersList;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception at AppParentDetail::GetApplicableTeachers(). Stack Trace: ' . $e->getTraceAsString());
            return $TeachersList;
        }
	}
	
	public function SendChatMessage($BranchStaffID, $Message)
	{
		try
        {
			$ChatRoomID = 0;
			
			$RSSearchChatRoom = $this->DBObject->Prepare('SELECT chatRoomID FROM asa_chat_rooms 
															WHERE ( (firstUserType = \'Parent\' AND firstRecordID = :|1) AND (secondUserType != \'Parent\' AND secondRecordID = :|2) ) 
																	OR 
																  ( (secondUserType = \'Parent\' AND secondRecordID = :|3) AND (firstUserType != \'Parent\' AND firstRecordID = :|4) )
															LIMIT 1;');

			$RSSearchChatRoom->Execute($this->ParentID, $BranchStaffID, $this->ParentID, $BranchStaffID);

			$this->DBObject->BeginTransaction();
			
			if ($RSSearchChatRoom->Result->num_rows > 0)
            {
				$ChatRoomID = $RSSearchChatRoom->FetchRow()->chatRoomID;
            }
			else
			{
				$RSCreateChatRoom = $this->DBObject->Prepare('INSERT INTO asa_chat_rooms (firstUserType, firstRecordID, secondUserType, secondRecordID, isActive, createDate) 
																VALUES (\'Parent\', :|1, \'Teacher\', :|2, 1, NOW());');

				$RSCreateChatRoom->Execute($this->ParentID, $BranchStaffID);

				$ChatRoomID = $RSCreateChatRoom->LastID;
			}
			
			$RSSaveChatMessage = $this->DBObject->Prepare('INSERT INTO asa_chat_room_messages (chatRoomID, userType, recordID, message, createDate) 
															VALUES (:|1, \'Parent\', :|2, :|3, NOW());');

			$RSSaveChatMessage->Execute($ChatRoomID, $this->ParentID, $Message);
			
			$this->DBObject->CommitTransaction();
			
            return $ChatRoomID;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at AppParentDetail::GetApplicableTeachers(). Stack Trace: ' . $e->getTraceAsString());
			$this->DBObject->RollBackTransaction();
            return false;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception at AppParentDetail::GetApplicableTeachers(). Stack Trace: ' . $e->getTraceAsString());
			$this->DBObject->RollBackTransaction();
            return false;
        }
	}
	
	public function FetchChat($BranchStaffID, $LastChatRoomMessageID)
	{
		$Chats = array();

		try
        {
			$QueryString = '';
			
			if ($LastChatRoomMessageID > 0)
			{
				$QueryString = ' AND acrm.chatRoomMessageID < ' . $this->DBObject->RealEscapeVariable($LastChatRoomMessageID);
			}
			
			$RSSearchChatRoom = $this->DBObject->Prepare('SELECT acrm.* FROM asa_chat_room_messages acrm 
															INNER JOIN asa_chat_rooms acr ON acr.chatRoomID = acrm.chatRoomID 
															WHERE (( (acr.firstUserType = \'Parent\' AND acr.firstRecordID = :|1) AND (acr.secondUserType != \'Parent\' AND acr.secondRecordID = :|2) ) 
																	OR 
																  ( (acr.secondUserType = \'Parent\' AND acr.secondRecordID = :|3) AND (acr.firstUserType != \'Parent\' AND acr.firstRecordID = :|4) ))
															' . $QueryString . '
															ORDER BY acrm.chatRoomMessageID DESC LIMIT 25;');

			$RSSearchChatRoom->Execute($this->ParentID, $BranchStaffID, $this->ParentID, $BranchStaffID);

			if ($RSSearchChatRoom->Result->num_rows <= 0)
            {
                return $Chats;
            }

			while ($SearchTeachersRow = $RSSearchChatRoom->FetchRow())
			{
				$Chats[$SearchTeachersRow->chatRoomMessageID]['isSelfMessage'] = 0;
				
				if ($SearchTeachersRow->userType == 'Parent' && $SearchTeachersRow->recordID == $this->ParentID)
				{
					$Chats[$SearchTeachersRow->chatRoomMessageID]['isSelfMessage'] = 1;
				}
				
				$Chats[$SearchTeachersRow->chatRoomMessageID]['Message'] = $SearchTeachersRow->message;
				$Chats[$SearchTeachersRow->chatRoomMessageID]['IsDelivered'] = $SearchTeachersRow->isDelivered;
				$Chats[$SearchTeachersRow->chatRoomMessageID]['IsSeen'] = $SearchTeachersRow->isSeen;
				$Chats[$SearchTeachersRow->chatRoomMessageID]['SeenDateTime'] = $SearchTeachersRow->seenDateTime;
				$Chats[$SearchTeachersRow->chatRoomMessageID]['CreateDate'] = $SearchTeachersRow->createDate;
			}
			
            return $Chats;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at AppParentDetail::GetApplicableTeachers(). Stack Trace: ' . $e->getTraceAsString());
            return $Chats;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception at AppParentDetail::GetApplicableTeachers(). Stack Trace: ' . $e->getTraceAsString());
            return $Chats;
        }
	}
	
	public function GetNewsFeeds($StudentID, $FeeSubmissionLastMonthPriority)
	{
		$NewsFeeds = array();

		$MasterKeys = array();
		
		$MasterKeys['Date'] = '';
		$MasterKeys['ItemType'] = '';
		$MasterKeys['RecordID'] = '';
		
		$MasterKeys['NoticeCircularSubject'] = '';
		$MasterKeys['NoticeCircularDetails'] = '';
		
		$MasterKeys['FirstName'] = '';
		$MasterKeys['LastName'] = '';
		
		$MasterKeys['Subject'] = '';
		$MasterKeys['ChapterName'] = '';
		$MasterKeys['TopicName'] = '';
		$MasterKeys['AssignmentHeading'] = '';
		$MasterKeys['Assignment'] = '';
		
		$MasterKeys['EventStartDate'] = '';
		$MasterKeys['EventEndDate'] = '';
		$MasterKeys['EventName'] = '';
		$MasterKeys['EventDetails'] = '';
		$MasterKeys['NotificationMessage'] = '';
		
		$MasterKeys['EventName'] = '';
		$MasterKeys['EventDescription'] = '';
		
		$MasterKeys['EventGalleryID'] = '';
		$MasterKeys['ImageName'] = '';
		$MasterKeys['ImageDescription'] = '';
		
		$MasterKeys['AttendenceStatus'] = '';
		$MasterKeys['DueAmount'] = 0;
		
		$MasterKeys['TopicStatus'] = '';
				
		$Data = array();
		
		try
        {
			# NOTICES
	        $RSSearch = $this->DBObject->Prepare('SELECT nc.*, u.userName AS createUserName 
												FROM asa_notices_circulars nc 
												INNER JOIN asa_notices_circulars_applicable_for anca ON anca.noticeCircularID = nc.noticeCircularID 
            									INNER JOIN users u ON nc.createUserID = u.userID 
												WHERE DATE(nc.createDate) > CURRENT_DATE() - INTERVAL 30 DAY AND nc.isActive = 1 AND anca.applicableFor = \'Class\' AND anca.staffOrClassID = :|1 
												ORDER BY nc.createDate DESC;');
            $RSSearch->Execute($this->ClassID);
			
			if ($RSSearch->Result->num_rows > 0)
            {
				while ($SearchRow = $RSSearch->FetchRow())
				{
					$Data[] = ['Date' => strtotime(date('Y-m-d', strtotime($SearchRow->createDate))), 'ItemType' => 'Notice', 'RecordID' => $SearchRow->noticeCircularID, 'NoticeCircularSubject' => $SearchRow->noticeCircularSubject, 'NoticeCircularDetails' => $SearchRow->noticeCircularDetails];
				}
            }
			
			# BIRTHDAYS
	        $RSSearch = $this->DBObject->Prepare('SELECT ass.studentID, asd.firstName, asd.lastName FROM asa_students ass 
												INNER JOIN asa_student_details asd ON asd.studentID = ass.studentID 
												INNER JOIN asa_class_sections acs ON acs.classSectionID = ass.classSectionID
												WHERE acs.classID = :|1 AND asd.dob = CURRENT_DATE;');
            $RSSearch->Execute($this->ClassID);
			
			if ($RSSearch->Result->num_rows > 0)
            {
				while ($SearchRow = $RSSearch->FetchRow())
				{
					$Data[] = ['Date' => strtotime(date('Y-m-d')), 'ItemType' => 'Birthday', 'RecordID' => $SearchRow->studentID, 'FirstName' => $SearchRow->firstName, 'LastName' => $SearchRow->lastName];
				}
            }
			
			# Homework
	        $RSSearch = $this->DBObject->Prepare('SELECT asa.*, act.topicName, asm.subject, ac.chapterName, u.userName AS createUserName 
												FROM aas_student_assignment asa
												INNER JOIN aas_chapter_topics act ON act.chapterTopicID = asa.chapterTopicID 
												INNER JOIN aas_chapters ac ON ac.chapterID = act.chapterID 
												INNER JOIN asa_class_subjects acsub ON acsub.classSubjectID = ac.classSubjectID 
												INNER JOIN asa_subject_master asm ON asm.subjectID = acsub.subjectID 
												INNER JOIN users u ON ac.createUserID = u.userID 
												WHERE asa.classSectionID = :|1 AND DATE(asa.createDate) > CURRENT_DATE() - INTERVAL 30 DAY AND asa.isActive = 1 AND asa.isDraft = 0;');
            $RSSearch->Execute($this->ClassSectionID);
			
			if ($RSSearch->Result->num_rows > 0)
            {
				while ($SearchRow = $RSSearch->FetchRow())
				{
					$Data[] = ['Date' => strtotime(date('Y-m-d', strtotime($SearchRow->createDate))), 'ItemType' => 'Homework', 'RecordID' => $SearchRow->assignmentID, 'Subject' => $SearchRow->subject, 'ChapterName' => $SearchRow->chapterName, 'TopicName' => $SearchRow->topicName, 'AssignmentHeading' => $SearchRow->assignmentHeading, 'Assignment' => $SearchRow->assignment];
				}
            }
			
			# Holiday
	        $RSSearch = $this->DBObject->Prepare('SELECT aac.*
												FROM asa_academic_calendar aac 
												INNER JOIN asa_academic_calendar_event_dates aaced ON aac.academicCalendarID = aaced.academicCalendarID
												INNER JOIN asa_academic_calendar_rules_on aacro ON aaced.academicCalendarID = aacro.academicCalendarID
												WHERE aac.isHoliday = 1 AND aacro.ruleOn = \'Students\' AND aacro.ruleOnClassSectionID = :|1 AND 
												aaced.eventDate >= CURRENT_DATE AND aaced.eventDate < CURRENT_DATE + INTERVAL 30 DAY 
												GROUP BY aac.academicCalendarID;');
            $RSSearch->Execute($this->ClassSectionID);
			
			if ($RSSearch->Result->num_rows > 0)
            {
				while ($SearchRow = $RSSearch->FetchRow())
				{
					$Data[] = ['Date' => strtotime(date('Y-m-d', strtotime($SearchRow->createDate))), 'ItemType' => 'Holiday', 'RecordID' => $SearchRow->academicCalendarID, 'EventStartDate' => $SearchRow->eventStartDate, 'EventEndDate' => $SearchRow->eventEndDate, 'EventName' => $SearchRow->eventName, 'EventDetails' => $SearchRow->eventDetails, 'NotificationMessage' => $SearchRow->notificationMessage];
				}
            }
			
			# Event Gallery
	        $RSSearch = $this->DBObject->Prepare('SELECT * FROM asa_event_gallery WHERE isActive = 1 AND DATE(createDate) > CURRENT_DATE() - INTERVAL 30 DAY ORDER BY createDate DESC;');
            $RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows > 0)
            {
				while ($SearchRow = $RSSearch->FetchRow())
				{
					$EventImages = array();
					
					$RSSearchEventImage = $this->DBObject->Prepare('SELECT * FROM asa_event_gallery_images WHERE eventGalleryID = :|1;');
					$RSSearchEventImage->Execute($SearchRow->eventGalleryID);
					
					if ($RSSearchEventImage->Result->num_rows > 0)
					{
					    while ($SearchEventImageRow = $RSSearchEventImage->FetchRow())
    					{
    						$Data[] = ['Date' => strtotime(date('Y-m-d', strtotime($SearchRow->createDate))), 'ItemType' => 'EventGalleryImage', 'RecordID' => $SearchEventImageRow->eventGalleryImageID, 'EventGalleryID' => $SearchRow->eventGalleryID, 'ImageName' => $SearchEventImageRow->imageName, 'ImageDescription' => $SearchEventImageRow->description];
    					}
					}
					
					$Data[] = ['Date' => strtotime(date('Y-m-d', strtotime($SearchRow->createDate))), 'ItemType' => 'EventGallery', 'RecordID' => $SearchRow->eventGalleryID, 'EventName' => $SearchRow->name, 'EventDescription' => $SearchRow->description];
				}
            }
            
            # Student Attendance status 
	        $RSSearch = $this->DBObject->Prepare('SELECT acad.attendenceStatus FROM asa_class_attendence aca 
												INNER JOIN asa_class_attendence_details acad ON acad.classAttendenceID = aca.classAttendenceID 
												WHERE aca.classSectionID = :|1  AND aca.attendenceDate = CURRENT_DATE AND acad.studentID = :|2  LIMIT 1;');
            $RSSearch->Execute($this->ClassSectionID, $StudentID);
			
			if ($RSSearch->Result->num_rows > 0)
            {
				$Data[] = ['Date' => strtotime(date('Y-m-d')), 'ItemType' => 'AttendenceStatus', 'RecordID' => $StudentID, 'AttendenceStatus' => $RSSearch->FetchRow()->attendenceStatus];
            }
            
            # Student Fee status 
            $RSSearch = $this->DBObject->Prepare('SELECT SUM(sfs.amountPayable) AS totalPayable, SUM(fcd.amountPaid) AS totalPaid,
                                                    SUM((CASE WHEN afd.discountType = "Absolute" THEN afd.discountValue ELSE (sfs.amountPayable * afd.discountValue) / 100 END)) AS firstDiscountValue, 
                                                    SUM((CASE WHEN afd1.discountType = "Absolute" THEN afd1.discountValue ELSE (sfs.amountPayable * afd1.discountValue) / 100 END)) AS secondDiscountValue

													FROM afm_fee_structure_details fsd 
													INNER JOIN afm_student_fee_structure sfs ON sfs.feeStructureDetailID = fsd.feeStructureDetailID 
													INNER JOIN afm_fee_structure fs ON fs.feeStructureID = fsd.feeStructureID
													INNER JOIN afm_fee_heads fh ON fh.feeHeadID = fsd.feeHeadID
													INNER JOIN asa_academic_year_months aaym ON aaym.academicYearMonthID = fsd.academicYearMonthID

													LEFT JOIN (SELECT feeCollectionDetailID, feeCollectionID, studentFeeStructureID, SUM(amountPaid) AS amountPaid FROM afm_fee_collection_details GROUP BY studentFeeStructureID) AS fcd ON fcd.studentFeeStructureID = sfs.studentFeeStructureID
													LEFT JOIN afm_fee_collection fc ON fc.feeCollectionID = fcd.feeCollectionID 
													LEFT JOIN afm_fee_discounts afd ON afd.feeGroupID = fs.feeGroupID AND afd.feeStructureDetailID = fsd.feeStructureDetailID AND afd.feeDiscountType = \'Group\' 
													LEFT JOIN afm_fee_discounts afd1 ON afd1.studentID = sfs.studentID AND afd1.feeStructureDetailID = fsd.feeStructureDetailID AND afd1.feeDiscountType = \'Student\' 

													WHERE sfs.studentID = :|1 AND aaym.feePriority <= :|2 
																								
													ORDER BY aaym.feePriority, fh.priority ASC;');

            $RSSearch->Execute($StudentID, $FeeSubmissionLastMonthPriority);

            if ($RSSearch->Result->num_rows > 0)
            {
                while ($SearchRow = $RSSearch->FetchRow())
                {
                    if ($SearchRow->secondDiscountValue > 0)
                    {
                        $Data[] = ['Date' => strtotime(date('Y-m-d')), 'ItemType' => 'DueFeeStatus', 'RecordID' => $StudentID, 'DueAmount' => $SearchRow->totalPayable - $SearchRow->secondDiscountValue - $SearchRow->totalPaid];      
                    }
                    else
                    {
                        $Data[] = ['Date' => strtotime(date('Y-m-d')), 'ItemType' => 'DueFeeStatus', 'RecordID' => $StudentID, 'DueAmount' => $SearchRow->totalPayable - $SearchRow->firstDiscountValue - $SearchRow->totalPaid];   
                    }
                }
            }
            
            # Student Syllabus status 
	        $RSSearch = $this->DBObject->Prepare('SELECT act.topicName, ac.chapterName, asm.subject, atsd.topicScheduleDetailID FROM `aas_topic_schedule_details` atsd
                                                    INNER JOIN aas_topic_schedules ats ON ats.topicScheduleID = atsd.topicScheduleID
                                                    INNER JOIN aas_chapter_topics act ON act.chapterTopicID = atsd.chapterTopicID
                                                    INNER JOIN aas_chapters ac ON ac.chapterID = act.chapterID
                                                    INNER JOIN asa_class_subjects acs ON acs.classSubjectID = ac.classSubjectID
                                                    INNER JOIN asa_subject_master asm ON asm.subjectID = acs.subjectID
                                                    WHERE ats.classSectionID = :|1 AND atsd.status = \'Completed\'  AND atsd.statusUpdatedOn = CURRENT_DATE;');
            $RSSearch->Execute($this->ClassSectionID);
			
			if ($RSSearch->Result->num_rows > 0)
            {
                while ($SearchRow = $RSSearch->FetchRow())
                {
                    $Data[] = ['Date' => strtotime(date('Y-m-d')), 'ItemType' => 'TopicStatus', 'RecordID' => $SearchRow->topicScheduleDetailID, 'TopicStatus' => 'Completed', 'Subject' => $SearchRow->subject, 'ChapterName' => $SearchRow->chapterName, 'TopicName' => $SearchRow->topicName];   
                }
            }

			error_reporting(0);
			
			usort($Data, function($a, $b) {
				return $b['Date'] - $a['Date'];
			});
			
			$NewData = array();
			
			$Counter = 0;
			foreach($Data as $Details)
			{
				$NewData[$Counter] = $Details;
				
				$NewData[$Counter] = array_merge($MasterKeys, $Details);
				
				if (isset($Details['Date']))
				{
					$NewData[$Counter]['Date'] = date('Y-m-d', $Details['Date']);
				}
				
				$Counter++;
			}
			
            return $NewData;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at AppParentDetail::GetNewsFeeds(). Stack Trace: ' . $e->getTraceAsString());
            return $NewsFeeds;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception at AppParentDetail::GetNewsFeeds(). Stack Trace: ' . $e->getTraceAsString());
            return $NewsFeeds;
        }
	}
	
	public function GetClassSectionDetails($StudentID)
	{	
		try
        {
	        $RSSearch = $this->DBObject->Prepare('SELECT aas.classSectionID, ac.classID, ac.className, ac.classSymbol, asm.sectionName 
												  FROM asa_parent_details apd 
												  INNER JOIN asa_students aas ON apd.parentID = aas.parentID 
												  INNER JOIN asa_student_details asd ON asd.studentID = aas.studentID 
												  
												  INNER JOIN asa_class_sections acs ON acs.classSectionID = aas.classSectionID
												  INNER JOIN asa_classes ac ON ac.classID = acs.classID 
												  INNER JOIN asa_section_master asm ON asm.sectionMasterID = acs.sectionMasterID 
												  
												  WHERE apd.parentID = :|1 AND aas.studentID = :|2 LIMIT 1;');

            $RSSearch->Execute($this->ParentID, $StudentID);

            if ($RSSearch->Result->num_rows > 0)
            {
				$SearchUserInfoRow = $RSSearch->FetchRow();
			
				$this->ClassSectionID = $SearchUserInfoRow->classSectionID;
				$this->ClassID = $SearchUserInfoRow->classID;
				
				return true;
            }
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at AppParentDetail::GetClassSectionID(). Stack Trace: ' . $e->getTraceAsString());
            return false;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception at AppParentDetail::GetClassSectionID(). Stack Trace: ' . $e->getTraceAsString());
            return false;
        }
	}
	
	public function GetExamsOfStudent()
	{	
	    $AllExams = array();
	    
		try
        {
	        $RSSearch = $this->DBObject->Prepare('SELECT aet.examTypeID, aet.examType
												  FROM aem_exams ae
												  INNER JOIN aem_exam_types aet ON aet.examTypeID = ae.examTypeID 
												  
												  WHERE ae.classSectionID = :|1
												  GROUP BY aet.examTypeID;');

            $RSSearch->Execute($this->ClassSectionID);

            if ($RSSearch->Result->num_rows > 0)
            {
				while($SearchRow = $RSSearch->FetchRow())
                {
    				$AllExams[$SearchRow->examTypeID]['ExamTypeID'] = $SearchRow->examTypeID;
    				$AllExams[$SearchRow->examTypeID]['ExamType'] = $SearchRow->examType;
                }
				
				return $AllExams;
            }
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at AppParentDetail::GetExamsOfStudent(). Stack Trace: ' . $e->getTraceAsString());
            return $AllExams;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception at AppParentDetail::GetExamsOfStudent(). Stack Trace: ' . $e->getTraceAsString());
            return $AllExams;
        }
	}
	
	public function GetStudentFeeDetails($StudentID)
    {
		$FeeDetails = array();

        try
        {
	        $RSSearch = $this->DBObject->Prepare('SELECT aaym.academicYearMonthID, aaym.monthName, aaym.feePriority, fh.feeHead, sfs.studentFeeStructureID, sfs.amountPayable, fcd.amountPaid,
													(CASE WHEN afd.discountType = "Absolute" THEN afd.discountValue ELSE (sfs.amountPayable * afd.discountValue) / 100 END) AS firstDiscountValue, 
													(CASE WHEN afd1.discountType = "Absolute" THEN afd1.discountValue ELSE (sfs.amountPayable * afd1.discountValue) / 100 END) AS secondDiscountValue

													FROM afm_fee_structure_details fsd 
													INNER JOIN afm_student_fee_structure sfs ON sfs.feeStructureDetailID = fsd.feeStructureDetailID 
													INNER JOIN afm_fee_structure fs ON fs.feeStructureID = fsd.feeStructureID
													INNER JOIN afm_fee_heads fh ON fh.feeHeadID = fsd.feeHeadID
													INNER JOIN asa_academic_year_months aaym ON aaym.academicYearMonthID = fsd.academicYearMonthID

													LEFT JOIN (SELECT feeCollectionDetailID, feeCollectionID, studentFeeStructureID, SUM(amountPaid) AS amountPaid FROM afm_fee_collection_details GROUP BY studentFeeStructureID) AS fcd ON fcd.studentFeeStructureID = sfs.studentFeeStructureID
													LEFT JOIN afm_fee_collection fc ON fc.feeCollectionID = fcd.feeCollectionID 
													LEFT JOIN afm_fee_discounts afd ON afd.feeGroupID = fs.feeGroupID AND afd.feeStructureDetailID = fsd.feeStructureDetailID AND afd.feeDiscountType = \'Group\' 
													LEFT JOIN afm_fee_discounts afd1 ON afd1.studentID = sfs.studentID AND afd1.feeStructureDetailID = fsd.feeStructureDetailID AND afd1.feeDiscountType = \'Student\' 

													WHERE sfs.studentID = :|1 AND fs.academicYearID IN (SELECT academicYearID FROM asa_academic_years WHERE isCurrentYear = 1)
																								
													ORDER BY aaym.feePriority, fh.priority ASC;');

            $RSSearch->Execute($StudentID);

            if ($RSSearch->Result->num_rows <= 0)
            {
                return $FeeDetails;
            }

            while($SearchRow = $RSSearch->FetchRow())
            {
            	$DiscountValue = 0;

            	if ($SearchRow->secondDiscountValue > 0) 
            	{
            		$DiscountValue = $SearchRow->secondDiscountValue;
            	}
            	else
            	{
            		$DiscountValue = $SearchRow->firstDiscountValue;
            	}
                
                $FeeDetails[$SearchRow->monthName]['AcademicYearMonthID'] = $SearchRow->academicYearMonthID;    
                $FeeDetails[$SearchRow->monthName]['MonthName'] = $SearchRow->monthName;    
            	$FeeDetails[$SearchRow->monthName]['FeePriority'] = $SearchRow->feePriority;
				$FeeDetails[$SearchRow->monthName][$SearchRow->feeHead]['StudentFeeStructureID'] = $SearchRow->studentFeeStructureID;
				$FeeDetails[$SearchRow->monthName][$SearchRow->feeHead]['AmountPayable'] = $SearchRow->amountPayable - $DiscountValue;
				$FeeDetails[$SearchRow->monthName][$SearchRow->feeHead]['AmountPaid'] = $SearchRow->amountPaid;
				$FeeDetails[$SearchRow->monthName][$SearchRow->feeHead]['DueAmount'] = $SearchRow->amountPayable - $DiscountValue - $SearchRow->amountPaid;
            }

            return $FeeDetails;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at AppParentDetail::GetStudentFeeDetails(). Stack Trace: ' . $e->getTraceAsString());
            return $FeeDetails;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception at AppParentDetail::GetStudentFeeDetails(). Stack Trace: ' . $e->getTraceAsString());
            return $FeeDetails;
        }
	}
	
	public function GetStudentChapterStatusDetails($ClassSectionID, $ClassSubjectID)
    {
		$ChapterStatusDetails = array();

        try
        {
	        $RSSearch = $this->DBObject->Prepare('SELECT act.chapterTopicID, act.topicName, ac.chapterName, asm.subject, acl.className, atsd.status, atsd.statusUpdatedOn
													FROM aas_chapter_topics act
													INNER JOIN aas_chapters ac ON ac.chapterID = act.chapterID 
													INNER JOIN asa_class_subjects acs ON acs.classSubjectID = ac.classSubjectID 
													INNER JOIN asa_subject_master asm ON asm.subjectID = acs.subjectID 
													INNER JOIN asa_classes acl ON acl.classID = acs.classID 
													INNER JOIN asa_class_sections cs ON cs.classID = acl.classID 

													LEFT JOIN aas_topic_schedules ats ON ats.classSectionID = cs.classSectionID
													LEFT JOIN aas_topic_schedule_details atsd ON atsd.chapterTopicID = act.chapterTopicID

													WHERE cs.classSectionID = :|1 AND acs.classSubjectID = :|2
													ORDER BY ac.priority, act.priority;');

            $RSSearch->Execute($ClassSectionID, $ClassSubjectID);

            if ($RSSearch->Result->num_rows <= 0)
            {
                return $ChapterStatusDetails;
            }

            while($SearchRow = $RSSearch->FetchRow())
            {
				$ChapterStatusDetails[$SearchRow->chapterName][$SearchRow->chapterTopicID]['TopicName'] = $SearchRow->topicName;
				$ChapterStatusDetails[$SearchRow->chapterName][$SearchRow->chapterTopicID]['Status'] = $SearchRow->status;
				$ChapterStatusDetails[$SearchRow->chapterName][$SearchRow->chapterTopicID]['StatusUpdatedOn'] = $SearchRow->statusUpdatedOn;
            }

            return $ChapterStatusDetails;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at AppParentDetail::GetStudentChapterStatusDetails(). Stack Trace: ' . $e->getTraceAsString());
            return $ChapterStatusDetails;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception at AppParentDetail::GetStudentChapterStatusDetails(). Stack Trace: ' . $e->getTraceAsString());
            return $ChapterStatusDetails;
        }
	}
	// END OF PUBLIC METHODS	//
	
	// START OF STATIC METHODS	//
	
	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//
	private function GetParentDetailByUniqueAppToken()
	{
		$RSParentDetailDetail = $this->DBObject->Prepare('SELECT apd.parentID 
														 FROM asa_parent_details apd 
														 INNER JOIN users u ON u.userName = apd.userName 
														 WHERE userID = (SELECT userID FROM user_sessions WHERE uniqueToken = :|1 LIMIT 1) 
														 LIMIT 1;');
		
		$RSParentDetailDetail->Execute($this->UniqueAppToken);
		
		$ParentDetailDetailRow = $RSParentDetailDetail->FetchRow();

		$this->ParentID = $ParentDetailDetailRow->parentID;
	}
}
?>
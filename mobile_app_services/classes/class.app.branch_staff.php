<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.db_connect.php');
error_reporting(E_ALL);

class AppBranchStaff extends BranchStaff
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	private $UniqueAppToken;
	private $StaffCategory;
	private $UserID;
	private $BranchStaffID;

	// PUBLIC METHODS START HERE	//
	public function __construct($UniqueAppToken)
	{
		$this->UniqueAppToken = $UniqueAppToken;
		$this->StaffCategory = 'Teaching';
		$this->UserID = 0;
		$this->BranchStaffID = 0;

		$this->DBObject = new DBConnect;

		$this->GetBranchStaffDetailByUniqueAppToken();

		parent::__construct($this->BranchStaffID);
	}

	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetStaffCategory()
	{
		return $this->StaffCategory;
	}

	public function GetUserID()
	{
		return $this->UserID;
	}
	
	public function GetBranchStaffID()
	{
		return $this->BranchStaffID;
	}
	//  END OF GETTER AND SETTER FUNCTIONS 	//
	
	public function GetUserInfo()
	{
		$UserInfo = array();

		try
        {
	        $RSSearch = $this->DBObject->Prepare('SELECT abs.*, ac.className, ac.classSymbol, asm.sectionName 
												  FROM asa_branch_staff abs 
												  LEFT JOIN asa_class_classteachers acc ON acc.branchStaffID = abs.branchStaffID 
												  LEFT JOIN asa_class_sections acs ON acs.classSectionID = acc.classSectionID
												  LEFT JOIN asa_classes ac ON ac.classID = acs.classID AND ac.academicYearID = (SELECT academicYearID FROM asa_academic_years WHERE isCurrentYear = 1 LIMIT 1)
												  LEFT JOIN asa_section_master asm ON asm.sectionMasterID = acs.sectionMasterID 
												  WHERE abs.branchStaffID = :|1;');

            $RSSearch->Execute($this->BranchStaffID);

            if ($RSSearch->Result->num_rows <= 0)
            {
                return $UserInfo;
            }

			$SearchUserInfoRow = $RSSearch->FetchRow();
			
			$UserInfo['BranchStaffID'] = $this->BranchStaffID;
			
			$UserInfo['FirstName'] = $SearchUserInfoRow->firstName;
			$UserInfo['LastName'] = $SearchUserInfoRow->lastName;
			$UserInfo['PhoneNumber'] = $SearchUserInfoRow->phoneNumber;
			$UserInfo['MobileNumber1'] = $SearchUserInfoRow->mobileNumber1;
			$UserInfo['MobileNumber2'] = $SearchUserInfoRow->mobileNumber2;
			$UserInfo['Email'] = $SearchUserInfoRow->email;
			$UserInfo['Dob'] = $SearchUserInfoRow->dob;
			$UserInfo['StaffCategory'] = $SearchUserInfoRow->staffCategory;
			$UserInfo['JoiningDate'] = $SearchUserInfoRow->joiningDate;
			$UserInfo['UserName'] = $SearchUserInfoRow->userName;
			$UserInfo['ClassTeacherClassName'] = $SearchUserInfoRow->className;
			$UserInfo['ClassTeacherClassSymbol'] = $SearchUserInfoRow->classSymbol;
			$UserInfo['SectionName'] = $SearchUserInfoRow->sectionName;
			
			$UserInfo['Image'] = $SearchUserInfoRow->staffPhoto;
			
			$UserInfo['ClockInOutType'] = 'ClockIN';
			
			$RSClockInOutDetails = $this->DBObject->Prepare('SELECT clockInOutType FROM teacher_clock_in_out_log WHERE branchStaffID = :|1 AND clockInOutDate = :|2 ORDER BY teacherClockInOutLog DESC LIMIT 1;');
            $RSClockInOutDetails->Execute($this->BranchStaffID, date('Y-m-d'));
            
            if ($RSClockInOutDetails->Result->num_rows > 0)
            {
                $ClockType = $RSClockInOutDetails->FetchRow()->clockInOutType;
                
                if ($ClockType == 'ClockIN')
                {
                    $UserInfo['ClockInOutType'] = 'ClockOUT';
                }
                else
                {
                    $UserInfo['ClockInOutType'] = 'ClockIN';
                }
            }

            return $UserInfo;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at AppBranchStaff::GetUserInfo(). Stack Trace: ' . $e->getTraceAsString());
            return $UserInfo;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception at AppBranchStaff::GetUserInfo(). Stack Trace: ' . $e->getTraceAsString());
            return $UserInfo;
        }
	}
	
	public function SendChatMessage($RecordType, $RecordID, $Message)
	{
		try
        {
			$ChatRoomID = 0;
			
			$RSSearchChatRoom = $this->DBObject->Prepare('SELECT chatRoomID FROM asa_chat_rooms 
															WHERE ( (firstUserType = \'Teacher\' AND firstRecordID = :|1) AND (secondUserType = :|2 AND secondRecordID = :|3) ) 
																	OR 
																  ( (secondUserType = \'Teacher\' AND secondRecordID = :|4) AND (firstUserType = :|5 AND firstRecordID = :|6) )
															LIMIT 1;');

			$RSSearchChatRoom->Execute($this->BranchStaffID, $RecordType, $RecordID, $this->BranchStaffID, $RecordType, $RecordID);

			$this->DBObject->BeginTransaction();
			
			if ($RSSearchChatRoom->Result->num_rows > 0)
            {
				$ChatRoomID = $RSSearchChatRoom->FetchRow()->chatRoomID;
            }
			else
			{
				$RSCreateChatRoom = $this->DBObject->Prepare('INSERT INTO asa_chat_rooms (firstUserType, firstRecordID, secondUserType, secondRecordID, isActive, createDate) 
																VALUES (\'Teacher\', :|1, :|2, :|3, 1, NOW());');

				$RSCreateChatRoom->Execute($this->BranchStaffID, $RecordType, $RecordID);

				$ChatRoomID = $RSCreateChatRoom->LastID;
			}
			
			$RSSaveChatMessage = $this->DBObject->Prepare('INSERT INTO asa_chat_room_messages (chatRoomID, userType, recordID, message, createDate) 
															VALUES (:|1, \'Teacher\', :|2, :|3, NOW());');

			$RSSaveChatMessage->Execute($ChatRoomID, $this->BranchStaffID, $Message);
			
			$this->DBObject->CommitTransaction();
			
            return $ChatRoomID;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at AppBranchStaff::SendChatMessage(). Stack Trace: ' . $e->getTraceAsString());
			$this->DBObject->RollBackTransaction();
            return false;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception at AppBranchStaff::SendChatMessage(). Stack Trace: ' . $e->getTraceAsString());
			$this->DBObject->RollBackTransaction();
            return false;
        }
	}
	
	public function FetchBranchStaffForChat($StaffCategory = '')
	{
	    $AllBranchStaff = array();

        $DBConnObject = new DBConnect();

        $Query = '';

        if(!empty($StaffCategory))
        {
        	$Query = " AND abs.staffCategory =" . $DBConnObject->RealEscapeVariable($StaffCategory);
        }

        try
        {
	        $RSSearch = $DBConnObject->Prepare('SELECT abs.*, u.userName AS createUserName 
	                                            FROM asa_branch_staff abs 
	                                            INNER JOIN users u ON abs.createUserID = u.userID
												LEFT JOIN asa_chat_rooms acr ON 
                                        			((acr.firstRecordID = abs.branchStaffID) OR (acr.secondRecordID = abs.branchStaffID)) 
											    LEFT JOIN asa_chat_room_messages acrm ON acrm.chatRoomID = acr.chatRoomID
												WHERE abs.isActive = 1 AND abs.branchStaffID != :|1' . $Query . '
												ORDER BY abs.firstName;');
												
            $RSSearch->Execute($this->BranchStaffID);

            if ($RSSearch->Result->num_rows <= 0)
            {
                return $AllBranchStaff;
            }

            while($SearchRow = $RSSearch->FetchRow())
            {
                $AllBranchStaff[$SearchRow->branchStaffID]['FirstName'] = $SearchRow->firstName;
                $AllBranchStaff[$SearchRow->branchStaffID]['LastName'] = $SearchRow->lastName;

                $AllBranchStaff[$SearchRow->branchStaffID]['Gender'] = $SearchRow->gender;
		        $AllBranchStaff[$SearchRow->branchStaffID]['AadharNumber'] = $SearchRow->aadharNumber;

		        $AllBranchStaff[$SearchRow->branchStaffID]['DOB'] = $SearchRow->dob;
		        $AllBranchStaff[$SearchRow->branchStaffID]['StaffCategory'] = $SearchRow->staffCategory;
		        $AllBranchStaff[$SearchRow->branchStaffID]['JoiningDate'] = $SearchRow->joiningDate;
		        $AllBranchStaff[$SearchRow->branchStaffID]['StaffPhoto'] = $SearchRow->staffPhoto;
		        
		        $AllBranchStaff[$SearchRow->branchStaffID]['UserName'] = $SearchRow->userName;

		        $AllBranchStaff[$SearchRow->branchStaffID]['IsActive'] = $SearchRow->isActive;

		        $AllBranchStaff[$SearchRow->branchStaffID]['CreateUserID'] = $SearchRow->createUserID;
		        $AllBranchStaff[$SearchRow->branchStaffID]['CreateUserName'] = $SearchRow->createUserName;
				$AllBranchStaff[$SearchRow->branchStaffID]['CreateDate'] = $SearchRow->createDate;
            }

            return $AllBranchStaff;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at BranchStaff::GetAllBranchStaff(). Stack Trace: ' . $e->getTraceAsString());
            return $AllBranchStaff;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception at BranchStaff::GetAllBranchStaff(). Stack Trace: ' . $e->getTraceAsString());
            return $AllBranchStaff;
        }
	}
	
	public function FetchChat($RecordType, $RecordID, $LastChatRoomMessageID)
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
															WHERE (( (acr.firstUserType = \'Teacher\' AND acr.firstRecordID = :|1) AND (acr.secondUserType = :|2 AND acr.secondRecordID = :|3) ) 
																	OR 
																  ( (acr.secondUserType = \'Teacher\' AND acr.secondRecordID = :|4) AND (acr.firstUserType = :|5 AND acr.firstRecordID = :|6) ))
															' . $QueryString . '
															ORDER BY acrm.chatRoomMessageID DESC LIMIT 25;');

			$RSSearchChatRoom->Execute($this->BranchStaffID, $RecordType, $RecordID, $this->BranchStaffID, $RecordType, $RecordID);

			if ($RSSearchChatRoom->Result->num_rows <= 0)
            {
                return $Chats;
            }

			while ($SearchTeachersRow = $RSSearchChatRoom->FetchRow())
			{
				$Chats[$SearchTeachersRow->chatRoomMessageID]['IsSelfMessage'] = '0';
				
				if ($SearchTeachersRow->userType == 'Teacher' && $SearchTeachersRow->recordID == $this->BranchStaffID)
				{
					$Chats[$SearchTeachersRow->chatRoomMessageID]['IsSelfMessage'] ='1';
				}
				
				$Chats[$SearchTeachersRow->chatRoomMessageID]['Message'] = (string) $SearchTeachersRow->message;
				$Chats[$SearchTeachersRow->chatRoomMessageID]['IsDelivered'] = (string) $SearchTeachersRow->isDelivered;
				$Chats[$SearchTeachersRow->chatRoomMessageID]['IsSeen'] = (string) $SearchTeachersRow->isSeen;
				$Chats[$SearchTeachersRow->chatRoomMessageID]['SeenDateTime'] = (string) $SearchTeachersRow->seenDateTime;
				$Chats[$SearchTeachersRow->chatRoomMessageID]['CreateDate'] = (string) $SearchTeachersRow->createDate;
			}
			
            return $Chats;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at AppBranchStaff::FetchChat(). Stack Trace: ' . $e->getTraceAsString());
            return $Chats;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception at AppBranchStaff::FetchChat(). Stack Trace: ' . $e->getTraceAsString());
            return $Chats;
        }
	}
	
	public function FetchSubstitutions()
    {
		$SubstitutionsList = array();

        try
        {
	        $RSSearch = $this->DBObject->Prepare('SELECT acs.classSubstitutionID, acs.substitutionStatus, ac.className, ac.classSymbol, asm.sectionName, asubm.subject, asscpt.periodStartTime, asscpt.periodEndTime, astpm.timingPart 
												  FROM asa_class_substitution acs 
												  INNER JOIN asa_class_time_table_details acttd ON acttd.classTimeTableDetailID = acs.classTimeTableDetailID 
												  INNER JOIN asa_class_time_table actt ON actt.classTimeTableID = acttd.classTimeTableID 
												  INNER JOIN asa_class_sections acsection ON acsection.classSectionID = actt.classSectionID 
												  INNER JOIN asa_classes ac ON ac.classID = acsection.classID 
												  INNER JOIN asa_section_master asm ON asm.sectionMasterID = acsection.sectionMasterID 
												  INNER JOIN asa_class_subjects acsub ON acsub.classSubjectID = acttd.classSubjectID 
												  INNER JOIN asa_subject_master asubm ON asubm.subjectID = acsub.subjectID 
												  INNER JOIN asa_teacher_classes atc ON atc.teacherClassID = acs.teacherClassID 
												  INNER JOIN asa_school_session_class_period_timings asscpt ON asscpt.periodTimingID = acttd.periodTimingID 
												  INNER JOIN asa_school_timing_parts_master astpm ON astpm.schoolTimingPartID = asscpt.schoolTimingPartID 
												  WHERE atc.branchStaffID = :|1 AND acs.substitutionDate = CURRENT_DATE;');
			
            $RSSearch->Execute($this->BranchStaffID);

            if ($RSSearch->Result->num_rows <= 0)
            {
                return $SubstitutionsList;
            }

            while($SearchRow = $RSSearch->FetchRow())
            {
				$SubstitutionsList[$SearchRow->classSubstitutionID]['SubstitutionStatus'] = $SearchRow->substitutionStatus;
				
				$SubstitutionsList[$SearchRow->classSubstitutionID]['ClassName'] = $SearchRow->className;
				$SubstitutionsList[$SearchRow->classSubstitutionID]['ClassSymbol'] = $SearchRow->classSymbol;
				$SubstitutionsList[$SearchRow->classSubstitutionID]['SectionName'] = $SearchRow->sectionName;
				
				$SubstitutionsList[$SearchRow->classSubstitutionID]['Subject'] = $SearchRow->subject;
				$SubstitutionsList[$SearchRow->classSubstitutionID]['PeriodStartTime'] = $SearchRow->periodStartTime;
				$SubstitutionsList[$SearchRow->classSubstitutionID]['PeriodEndTime'] = $SearchRow->periodEndTime;
				$SubstitutionsList[$SearchRow->classSubstitutionID]['TimingPart'] = $SearchRow->timingPart;
            }

            return $SubstitutionsList;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at AppBranchStaff::FetchSubstitutions(). Stack Trace: ' . $e->getTraceAsString());
            return $SubstitutionsList;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception at AppBranchStaff::FetchSubstitutions(). Stack Trace: ' . $e->getTraceAsString());
            return $SubstitutionsList;
        }
	}
	
	public function ChangeSubstitutionStatus($SubstitutionStatus, $ClassSubstitutionID)
    {
        try
        {
	        $RSUpdateSubstitution = $this->DBObject->Prepare('UPDATE asa_class_substitution SET substitutionStatus = :|1 WHERE classSubstitutionID = :|2 LIMIT 1;');
            $RSUpdateSubstitution->Execute($SubstitutionStatus, $ClassSubstitutionID);
			
			return true;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at AppBranchStaff::ChangeSubstitutionStatus(). Stack Trace: ' . $e->getTraceAsString());
            return false;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception at AppBranchStaff::ChangeSubstitutionStatus(). Stack Trace: ' . $e->getTraceAsString());
            return false;
        }
	}
    
    public function GetAllClassSections()
    {
		$AllClassSections = array();

        try
        {
	        $RSSearch = $this->DBObject->Prepare('SELECT cs.classSectionID, ac.className, ac.classID, ac.classSymbol, sm.sectionName 
												  FROM asa_class_sections cs 
												  INNER JOIN asa_classes ac ON ac.classID = cs.classID 
												  INNER JOIN asa_section_master sm ON sm.sectionMasterID = cs.sectionMasterID 
												  ORDER BY cs.classSectionID, ac.classID;');

            $RSSearch->Execute();

            if ($RSSearch->Result->num_rows <= 0)
            {
                return $AllClassSections;
            }

            while($SearchRow = $RSSearch->FetchRow())
            {
				$AllClassSections[$SearchRow->classSectionID]['ClassID'] = $SearchRow->classID;
				$AllClassSections[$SearchRow->classSectionID]['ClassName'] = $SearchRow->className;
				$AllClassSections[$SearchRow->classSectionID]['ClassSymbol'] = $SearchRow->classSymbol;
				$AllClassSections[$SearchRow->classSectionID]['SectionName'] = $SearchRow->sectionName;
            }

            return $AllClassSections;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at AppBranchStaff::GetAllClassSections(). Stack Trace: ' . $e->getTraceAsString());
            return $AllClassSections;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception at AppBranchStaff::GetAllClassSections(). Stack Trace: ' . $e->getTraceAsString());
            return $AllClassSections;
        }
	}
	
	public function GetApplicableClassSections()
    {
		$ApplicableClassSections = array();

        try
        {
	        $RSSearch = $this->DBObject->Prepare('SELECT cs.classSectionID, ac.className, ac.classID, ac.classSymbol, sm.sectionName 
												  FROM asa_teacher_classes tc 
												  INNER JOIN asa_class_sections cs ON cs.classID = tc.classID 
												  INNER JOIN asa_classes ac ON ac.classID = tc.classID 
												  INNER JOIN asa_section_master sm ON sm.sectionMasterID = cs.sectionMasterID 
												  WHERE tc.branchStaffID = :|1;');

            $RSSearch->Execute($this->BranchStaffID);

            if ($RSSearch->Result->num_rows <= 0)
            {
                return $ApplicableClassSections;
            }

            while($SearchRow = $RSSearch->FetchRow())
            {
				$ApplicableClassSections[$SearchRow->classSectionID]['ClassID'] = $SearchRow->classID;
				$ApplicableClassSections[$SearchRow->classSectionID]['ClassName'] = $SearchRow->className;
				$ApplicableClassSections[$SearchRow->classSectionID]['ClassSymbol'] = $SearchRow->classSymbol;
				$ApplicableClassSections[$SearchRow->classSectionID]['SectionName'] = $SearchRow->sectionName;
            }

            return $ApplicableClassSections;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at AppBranchStaff::GetApplicableClassSections(). Stack Trace: ' . $e->getTraceAsString());
            return $ApplicableClassSections;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception at AppBranchStaff::GetApplicableClassSections(). Stack Trace: ' . $e->getTraceAsString());
            return $ApplicableClassSections;
        }
	}
	
	public function GetApplicableClassSectionsForAttendance($AttendenceDate)
	{
		$ApplicableClassSections = array();

        try
        {
        	$RSSearchSubstituedClassSectionTeachers = $this->DBObject->Prepare('SELECT actt.classSectionID, acttd.teacherClassID, atc.classID, ac.classSymbol, ac.className, asm.sectionName
																				FROM asa_class_substitution acs
																				INNER JOIN asa_teacher_classes atc ON acs.teacherClassID = atc.teacherClassID
																				INNER JOIN asa_class_time_table_details acttd ON acs.classTimeTableDetailID = acttd.classTimeTableDetailID
																				INNER JOIN asa_class_time_table actt ON acttd.classTimeTableID = actt.classTimeTableID
																				INNER JOIN asa_class_sections acsec ON actt.classSectionID = acsec.classSectionID
																				INNER JOIN asa_classes ac ON acsec.classID = ac.classID
																				INNER JOIN asa_section_master asm ON acsec.sectionMasterID = asm.sectionMasterID
																				WHERE substitutionDate = CURDATE() AND atc.branchStaffID = :|1;');

            $RSSearchSubstituedClassSectionTeachers->Execute($this->BranchStaffID);

            // Substitution Part
            if ($RSSearchSubstituedClassSectionTeachers->Result->num_rows > 0) 
            {
            	while ($RSSearchRow = $RSSearchSubstituedClassSectionTeachers->FetchRow()) 
            	{	
            		// is absent branch staff is class teacher for the class section
            		$RSSearchIsClassTeacher = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords
																		FROM asa_class_classteachers acct
																		WHERE classSectionID = :|1 AND acct.branchStaffID = (SELECT branchStaffID FROM asa_teacher_classes WHERE teacherClassID = :|2);');

            		$RSSearchIsClassTeacher->Execute($RSSearchRow->classSectionID, $RSSearchRow->teacherClassID);

            		if ($RSSearchIsClassTeacher->FetchRow()->totalRecords > 0) 
            		{
            			$ApplicableClassSections[$RSSearchRow->classSectionID]['ClassSymbol'] = $RSSearchRow->classSymbol;
            			$ApplicableClassSections[$RSSearchRow->classSectionID]['ClassName'] = $RSSearchRow->className;
            			$ApplicableClassSections[$RSSearchRow->classSectionID]['SectionName'] = $RSSearchRow->sectionName;
            			$ApplicableClassSections[$RSSearchRow->classSectionID]['IsAttendanceTaken'] = 0;

            			$RSSearchIsAttedanceTaken = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM asa_class_attendence WHERE classSectionID = :|1 AND attendenceDate = CURDATE();');	

            			$RSSearchIsAttedanceTaken->Execute($RSSearchRow->classSectionID);

            			if ($RSSearchIsAttedanceTaken->FetchRow()->totalRecords > 0) 
            			{
            				$ApplicableClassSections[$RSSearchRow->classSectionID]['IsAttendanceTaken'] = 1;
            			}	
            		}
            	}
            }

            // logged teacher classes

            $RSSearchLoggedTeacher = $this->DBObject->Prepare('SELECT ac.classSymbol, ac.className, asm.sectionName, acct.classSectionID
																FROM asa_class_classteachers acct
																INNER JOIN asa_class_sections acsec ON acct.classSectionID = acsec.classSectionID
																INNER JOIN asa_classes ac ON acsec.classID = ac.classID
																INNER JOIN asa_section_master asm ON acsec.sectionMasterID = asm.sectionMasterID
																WHERE acct.branchStaffID = :|1;');

    		$RSSearchLoggedTeacher->Execute($this->BranchStaffID);

    		if ($RSSearchLoggedTeacher->Result->num_rows > 0) 
    		{	
    			while ($SearchRowClassTeacher = $RSSearchLoggedTeacher->FetchRow()) 
    			{
    				$ApplicableClassSections[$SearchRowClassTeacher->classSectionID]['ClassSymbol'] = $SearchRowClassTeacher->classSymbol;
	    			$ApplicableClassSections[$SearchRowClassTeacher->classSectionID]['ClassName'] = $SearchRowClassTeacher->className;
	    			$ApplicableClassSections[$SearchRowClassTeacher->classSectionID]['SectionName'] = $SearchRowClassTeacher->sectionName;
	    			$ApplicableClassSections[$SearchRowClassTeacher->classSectionID]['IsAttendanceTaken'] = 0;

	    			$RSSearchIsAttedanceTaken = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM asa_class_attendence WHERE classSectionID = :|1 AND attendenceDate = CURDATE();');	

	    			$RSSearchIsAttedanceTaken->Execute($SearchRowClassTeacher->classSectionID);

	    			if ($RSSearchIsAttedanceTaken->FetchRow()->totalRecords > 0) 
	    			{
	    				$ApplicableClassSections[$SearchRowClassTeacher->classSectionID]['IsAttendanceTaken'] = 1;
	    			}
    			}	
    		}

    		return $ApplicableClassSections;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at AppBranchStaff::GetApplicableClassSectionsForAttendance(). Stack Trace: ' . $e->getTraceAsString());
            return $ApplicableClassSections;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception at AppBranchStaff::GetApplicableClassSectionsForAttendance(). Stack Trace: ' . $e->getTraceAsString());
            return $ApplicableClassSections;
        }
	}

	public function GetTeacherClassSubjects($ClassID)
    {
		$AllSubjects = array();

        try
        {
	        $RSSearch = $this->DBObject->Prepare('SELECT acs.classSubjectID, asm.subject FROM asa_class_subjects acs
													INNER JOIN asa_teacher_subjects ats ON acs.subjectID = ats.subjectID
													INNER JOIN asa_subject_master asm ON acs.subjectID = asm.subjectID
													WHERE acs.classID = :|1 AND ats.branchStaffID = :|2;');

            $RSSearch->Execute($ClassID, $this->BranchStaffID);

            if ($RSSearch->Result->num_rows <= 0)
            {
                return $AllSubjects;
            }

            while($SearchRow = $RSSearch->FetchRow())
            {
				$AllSubjects[$SearchRow->classSubjectID]['SubjectName'] = $SearchRow->subject;
            }

            return $AllSubjects;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at AppBranchStaff::GetTeacherClassSubjects(). Stack Trace: ' . $e->getTraceAsString());
            return $AllSubjects;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception at AppBranchStaff::GetTeacherClassSubjects(). Stack Trace: ' . $e->getTraceAsString());
            return $AllSubjects;
        }
	}
	
	public function IsTeacherClockIn()
    {
        try
        {
	        $RSSearch = $this->DBObject->Prepare('SELECT clockInOutType FROM teacher_clock_in_out_log WHERE branchStaffID = :|1 AND clockInOutDate = CURRENT_DATE ORDER BY teacherClockInOutLog DESC LIMIT 1;');

            $RSSearch->Execute($this->BranchStaffID);

            if ($RSSearch->Result->num_rows <= 0)
            {
                return false;
            }
            
            if ($RSSearch->FetchRow()->clockInOutType == 'ClockIN')
            {
			    return true;	
            }
            else
            {
                return false;
            }
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at AppBranchStaff::IsTeacherClockIn(). Stack Trace: ' . $e->getTraceAsString());
            return false;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception at AppBranchStaff::IsTeacherClockIn(). Stack Trace: ' . $e->getTraceAsString());
            return false;
        }
	}
	
	public function MarkClockInOut($ClockInOutType = 'ClockIN')
    {
        try
        {
			$StaffAttendenceID = 0;
			
			$RSCheckCurrentDayAttandence = $this->DBObject->Prepare('SELECT staffAttendenceID FROM asa_staff_attendence WHERE staffCategory = \'Teaching\' AND attendenceDate = CURRENT_DATE LIMIT 1;');
			$RSCheckCurrentDayAttandence->Execute();
			
			if ($RSCheckCurrentDayAttandence->Result->num_rows > 0)
			{
				$StaffAttendenceID = $RSCheckCurrentDayAttandence->FetchRow()->staffAttendenceID;
			}
			else
			{
				$RSMarkAttandence = $this->DBObject->Prepare('INSERT INTO asa_staff_attendence (staffCategory, attendenceDate, createUserID, createDate) VALUES (\'Teaching\', CURRENT_DATE, :|1, NOW());');
				$RSMarkAttandence->Execute($this->UserID);
				
				$StaffAttendenceID = $RSMarkAttandence->LastID;
			}
			
			$RSCheckStaffCurrentDayAttendance = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM asa_staff_attendence_details WHERE staffAttendenceID = :|1 AND branchStaffID = :|2;');
			$RSCheckStaffCurrentDayAttendance->Execute($StaffAttendenceID, $this->BranchStaffID);
			
			if ($RSCheckStaffCurrentDayAttendance->FetchRow()->totalRecords <= 0)
			{
				$RSMarkStaffAttendance = $this->DBObject->Prepare('INSERT INTO asa_staff_attendence_details (staffAttendenceID, branchStaffID, attendenceStatus) VALUES (:|1, :|2, \'Present\');');
				$RSMarkStaffAttendance->Execute($StaffAttendenceID, $this->BranchStaffID);
			}
			
	        $RSMarkClockInOut = $this->DBObject->Prepare('INSERT INTO teacher_clock_in_out_log (branchStaffID, clockInOutDate, clockInOutType, clockInOutTime) 
															VALUES (:|1, CURRENT_DATE, :|2, CURRENT_TIME);');

            $RSMarkClockInOut->Execute($this->BranchStaffID, $ClockInOutType);

            return true;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at AppBranchStaff::MarkClockInOut(). Stack Trace: ' . $e->getTraceAsString());
            return false;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception at AppBranchStaff::MarkClockInOut(). Stack Trace: ' . $e->getTraceAsString());
            return false;
        }
	}

	public function GetNewsFeeds()
	{
		$NewsFeeds = array();

		try
        {
			# NOTICES
	        $RSSearch = $this->DBObject->Prepare('SELECT nc.*, u.userName AS createUserName 
													FROM asa_notices_circulars nc 
													INNER JOIN asa_notices_circulars_applicable_for anca ON anca.noticeCircularID = nc.noticeCircularID 
		        									INNER JOIN users u ON nc.createUserID = u.userID 
													WHERE DATE(nc.createDate) > CURRENT_DATE() - INTERVAL 30 DAY AND nc.isActive = 1 AND anca.applicableFor = \'Staff\' AND anca.staffOrClassID = :|1 
													ORDER BY nc.createDate DESC;');

            $RSSearch->Execute($this->BranchStaffID);
			
			$Counter = 0;

			if ($RSSearch->Result->num_rows > 0)
            {
				while ($SearchRow = $RSSearch->FetchRow())
				{	
					$NewsFeeds[$Counter]['ItemType'] = 'Notice';
					$NewsFeeds[$Counter]['Date'] = date('Y-m-d', strtotime($SearchRow->createDate));
					
					$NewsFeeds[$Counter]['NoticeCircularSubject'] = $SearchRow->noticeCircularSubject;
					$NewsFeeds[$Counter]['NoticeCircularDetails'] = $SearchRow->noticeCircularDetails;

					$NewsFeeds[$Counter]['GalleryEventName'] = '';
					$NewsFeeds[$Counter]['GalleryEventDescription'] = '';
					$NewsFeeds[$Counter]['GalleryImageName'] = '';
					$NewsFeeds[$Counter]['GalleryImageDescription'] = '';

					$NewsFeeds[$Counter]['StudentFirstName'] = '';
					$NewsFeeds[$Counter]['StudentLastName'] = '';

					$NewsFeeds[$Counter]['Holiday'] = '';
					$NewsFeeds[$Counter]['EventStartDate'] = '';
					$NewsFeeds[$Counter]['EventEndDate'] = '';
					$NewsFeeds[$Counter]['EventName'] = '';
					$NewsFeeds[$Counter]['EventDetails'] = '';
					$NewsFeeds[$Counter]['NotificationMessage'] = '';
					
					$NewsFeeds[$Counter]['SubstitutionStatus'] = '';
					$NewsFeeds[$Counter]['ClassName'] = '';
					$NewsFeeds[$Counter]['ClassSymbol'] = '';
					$NewsFeeds[$Counter]['SectionName'] = '';
					$NewsFeeds[$Counter]['Subject'] = '';
					$NewsFeeds[$Counter]['PeriodStartTime'] = '';
					$NewsFeeds[$Counter]['PeriodEndTime'] = '';
					$NewsFeeds[$Counter]['TimingPart'] = '';

					$Counter++;
				}	
            }

			# BIRTHDAYS
	        $RSSearch = $this->DBObject->Prepare('SELECT ass.studentID, asd.firstName, asd.lastName, asd.dob 
	        										FROM asa_students ass 
	        										INNER JOIN asa_student_details asd ON asd.studentID = ass.studentID 
	        										INNER JOIN asa_class_sections acs ON acs.classSectionID = ass.classSectionID 
	        										INNER JOIN asa_teacher_classes atc ON acs.classID = atc.classID 
	        										WHERE atc.branchStaffID = :|1 AND asd.dob LIKE  "%-'. date('m-d') .'";');

            $RSSearch->Execute($this->BranchStaffID);
            
			if ($RSSearch->Result->num_rows > 0)
            {
				while ($SearchRow = $RSSearch->FetchRow())
				{
					$NewsFeeds[$Counter]['ItemType'] = 'Birthday';
					$NewsFeeds[$Counter]['Date'] = date('Y-m-d', strtotime($SearchRow->dob));
					
					$NewsFeeds[$Counter]['NoticeCircularSubject'] = '';
					$NewsFeeds[$Counter]['NoticeCircularDetails'] = '';

					$NewsFeeds[$Counter]['GalleryEventName'] = '';
					$NewsFeeds[$Counter]['GalleryEventDescription'] = '';
					$NewsFeeds[$Counter]['GalleryImageName'] = '';
					$NewsFeeds[$Counter]['GalleryImageDescription'] = '';

					$NewsFeeds[$Counter]['StudentFirstName'] = $SearchRow->firstName;
					$NewsFeeds[$Counter]['StudentLastName'] = $SearchRow->lastName;

					$NewsFeeds[$Counter]['Holiday'] = '';
					$NewsFeeds[$Counter]['EventStartDate'] = '';
					$NewsFeeds[$Counter]['EventEndDate'] = '';
					$NewsFeeds[$Counter]['EventName'] = '';
					$NewsFeeds[$Counter]['EventDetails'] = '';
					$NewsFeeds[$Counter]['NotificationMessage'] = '';
					
					$NewsFeeds[$Counter]['SubstitutionStatus'] = '';
					$NewsFeeds[$Counter]['ClassName'] = '';
					$NewsFeeds[$Counter]['ClassSymbol'] = '';
					$NewsFeeds[$Counter]['SectionName'] = '';
					$NewsFeeds[$Counter]['Subject'] = '';
					$NewsFeeds[$Counter]['PeriodStartTime'] = '';
					$NewsFeeds[$Counter]['PeriodEndTime'] = '';
					$NewsFeeds[$Counter]['TimingPart'] = '';

					$Counter++;
				}
            }

            $StaffCategory = 'TeachingStaff';

            if ($this->StaffCategory != 'Teaching') 
            {
            	$StaffCategory = 'NonTeachingStaff';
            }

			# Holiday
	        $RSSearch = $this->DBObject->Prepare('SELECT aac.* 
	        										FROM asa_academic_calendar aac 
        											INNER JOIN asa_academic_calendar_event_dates aaced ON aac.academicCalendarID = aaced.academicCalendarID 
    												INNER JOIN asa_academic_calendar_rules_on aacro ON aaced.academicCalendarID = aacro.academicCalendarID 
    												WHERE aac.isHoliday = 1 AND aacro.ruleOn = :|1 AND aaced.eventDate >= CURRENT_DATE AND aaced.eventDate < CURRENT_DATE + INTERVAL 30 DAY GROUP BY aac.academicCalendarID;');

            $RSSearch->Execute($StaffCategory);

			if ($RSSearch->Result->num_rows > 0)
            {
				while ($SearchRow = $RSSearch->FetchRow())
				{	
					$NewsFeeds[$Counter]['ItemType'] = 'Holiday';
					$NewsFeeds[$Counter]['Date'] = date('Y-m-d', strtotime($SearchRow->createDate));
					
					$NewsFeeds[$Counter]['NoticeCircularSubject'] = '';
					$NewsFeeds[$Counter]['NoticeCircularDetails'] = '';

					$NewsFeeds[$Counter]['GalleryEventName'] = '';
					$NewsFeeds[$Counter]['GalleryEventDescription'] = '';
					$NewsFeeds[$Counter]['GalleryImageName'] = '';
					$NewsFeeds[$Counter]['GalleryImageDescription'] = '';

					$NewsFeeds[$Counter]['StudentFirstName'] = '';
					$NewsFeeds[$Counter]['StudentLastName'] = '';

					$NewsFeeds[$Counter]['EventStartDate'] = $SearchRow->eventStartDate;
					$NewsFeeds[$Counter]['EventEndDate'] = $SearchRow->eventEndDate;
					$NewsFeeds[$Counter]['EventName'] = $SearchRow->eventName;
					$NewsFeeds[$Counter]['EventDetails'] = $SearchRow->eventDetails;
					$NewsFeeds[$Counter]['NotificationMessage'] = $SearchRow->notificationMessage;
					
					$NewsFeeds[$Counter]['SubstitutionStatus'] = '';
					$NewsFeeds[$Counter]['ClassName'] = '';
					$NewsFeeds[$Counter]['ClassSymbol'] = '';
					$NewsFeeds[$Counter]['SectionName'] = '';
					$NewsFeeds[$Counter]['Subject'] = '';
					$NewsFeeds[$Counter]['PeriodStartTime'] = '';
					$NewsFeeds[$Counter]['PeriodEndTime'] = '';
					$NewsFeeds[$Counter]['TimingPart'] = '';
					
					$Counter ++;
				}
            }

			# Event Gallery
	        $RSSearch = $this->DBObject->Prepare('SELECT aeg.*,
													(
														SELECT imageName FROM asa_event_gallery_images WHERE eventGalleryID = aeg.eventGalleryID LIMIT 1
													) AS imageName
													FROM asa_event_gallery aeg 
													WHERE aeg.isActive = 1 AND DATE(aeg.createDate) > CURRENT_DATE() - INTERVAL 30 DAY ORDER BY aeg.createDate DESC;');
            $RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows > 0)
            {
				while ($SearchRow = $RSSearch->FetchRow())
				{
					$NewsFeeds[$Counter]['ItemType'] = 'EventGallery';
					$NewsFeeds[$Counter]['Date'] = date('Y-m-d', strtotime($SearchRow->createDate));
					
					$NewsFeeds[$Counter]['NoticeCircularSubject'] = '';
					$NewsFeeds[$Counter]['NoticeCircularDetails'] = '';

					$NewsFeeds[$Counter]['GalleryEventName'] = $SearchRow->name;
					$NewsFeeds[$Counter]['GalleryEventDescription'] = $SearchRow->description;
					$NewsFeeds[$Counter]['GalleryImageName'] = SITE_HTTP_PATH . '/site_images/' . $SearchRow->imageName;
					$NewsFeeds[$Counter]['GalleryImageDescription'] = $SearchRow->description;

					$NewsFeeds[$Counter]['StudentFirstName'] = '';
					$NewsFeeds[$Counter]['StudentLastName'] = '';

					$NewsFeeds[$Counter]['EventStartDate'] = '';
					$NewsFeeds[$Counter]['EventEndDate'] = '';
					$NewsFeeds[$Counter]['EventName'] = '';
					$NewsFeeds[$Counter]['EventDetails'] = '';
					$NewsFeeds[$Counter]['NotificationMessage'] = '';
					
					$NewsFeeds[$Counter]['SubstitutionStatus'] = '';
					$NewsFeeds[$Counter]['ClassName'] = '';
					$NewsFeeds[$Counter]['ClassSymbol'] = '';
					$NewsFeeds[$Counter]['SectionName'] = '';
					$NewsFeeds[$Counter]['Subject'] = '';
					$NewsFeeds[$Counter]['PeriodStartTime'] = '';
					$NewsFeeds[$Counter]['PeriodEndTime'] = '';
					$NewsFeeds[$Counter]['TimingPart'] = '';
					
					$Counter++;
				}
            }
			
			# Substitution
			$SubstitutionList = $this->FetchSubstitutions();
			
			if (count($SubstitutionList) > 0)
			{
				foreach ($SubstitutionList as $ClassSubstitutionID => $ClassSubstitutionDetails)
				{
					$NewsFeeds[$Counter]['ItemType'] = 'Substitution';
					$NewsFeeds[$Counter]['Date'] = date('Y-m-d');
					
					$NewsFeeds[$Counter]['NoticeCircularSubject'] = '';
					$NewsFeeds[$Counter]['NoticeCircularDetails'] = '';

					$NewsFeeds[$Counter]['GalleryEventName'] = '';
					$NewsFeeds[$Counter]['GalleryEventDescription'] = '';
					$NewsFeeds[$Counter]['GalleryImageName'] = '';
					$NewsFeeds[$Counter]['GalleryImageDescription'] = '';

					$NewsFeeds[$Counter]['StudentFirstName'] = '';
					$NewsFeeds[$Counter]['StudentLastName'] = '';

					$NewsFeeds[$Counter]['EventStartDate'] = '';
					$NewsFeeds[$Counter]['EventEndDate'] = '';
					$NewsFeeds[$Counter]['EventName'] = '';
					$NewsFeeds[$Counter]['EventDetails'] = '';
					$NewsFeeds[$Counter]['NotificationMessage'] = '';
					
					$NewsFeeds[$Counter]['SubstitutionStatus'] = $ClassSubstitutionDetails['SubstitutionStatus'];
					$NewsFeeds[$Counter]['ClassName'] = $ClassSubstitutionDetails['ClassName'];
					$NewsFeeds[$Counter]['ClassSymbol'] = $ClassSubstitutionDetails['ClassSymbol'];
					$NewsFeeds[$Counter]['SectionName'] = $ClassSubstitutionDetails['SectionName'];
					$NewsFeeds[$Counter]['Subject'] = $ClassSubstitutionDetails['Subject'];
					$NewsFeeds[$Counter]['PeriodStartTime'] = $ClassSubstitutionDetails['PeriodStartTime'];
					$NewsFeeds[$Counter]['PeriodEndTime'] = $ClassSubstitutionDetails['PeriodEndTime'];
					$NewsFeeds[$Counter]['TimingPart'] = $ClassSubstitutionDetails['TimingPart'];
					
					$Counter++;
				}
			}

            return $NewsFeeds;
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

	public function GetAllNotices()
	{
		$AllNotices = array();

		try
        {
			# NOTICES
	        $RSSearch = $this->DBObject->Prepare('SELECT nc.*, u.userName AS createUserName 
													FROM asa_notices_circulars nc 
													INNER JOIN asa_notices_circulars_applicable_for anca ON anca.noticeCircularID = nc.noticeCircularID 
		        									INNER JOIN users u ON nc.createUserID = u.userID 
													WHERE nc.isActive = 1 AND anca.applicableFor = \'Staff\' AND anca.staffOrClassID = :|1 
													ORDER BY nc.createDate DESC;');

            $RSSearch->Execute($this->BranchStaffID);
			
			if ($RSSearch->Result->num_rows > 0)
            {
				while ($SearchRow = $RSSearch->FetchRow())

				{	
					$AllNotices[$SearchRow->noticeCircularID]['NoticeCircularSubject'] = $SearchRow->noticeCircularSubject;
					$AllNotices[$SearchRow->noticeCircularID]['NoticeCircularDetails'] = $SearchRow->noticeCircularDetails;
					$AllNotices[$SearchRow->noticeCircularID]['NoticeCircularDate'] = date('Y-m-d', strtotime($SearchRow->noticeCircularDate));
				}	
            }

           return $AllNotices;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at AppParentDetail::GetAllNotices(). Stack Trace: ' . $e->getTraceAsString());

            return $AllNotices;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception at AppParentDetail::GetAllNotices(). Stack Trace: ' . $e->getTraceAsString());

            return $AllNotices;
        }
	}

	public function GetTimeTable($BranchStaffID = 0)
	{
		$AllPeriodsDetails = array();

		try
        {
	        $RSSearch = $this->DBObject->Prepare('SELECT acttd.classTimeTableDetailID, asscdt.dayID, asm.subject, actt.daywiseTimingsID, asscpt.periodStartTime, asscpt.periodEndTime, 
	        										ac.className, ac.classSymbol, asecm.sectionName, astpm.timingPart 
													FROM asa_teacher_classes atc 
													INNER JOIN asa_class_time_table_details acttd ON atc.teacherClassID = acttd.teacherClassID 
													INNER JOIN asa_class_time_table actt ON acttd.classTimeTableID = actt.classTimeTableID
													INNER JOIN asa_class_sections acsec ON actt.classSectionID = acsec.classSectionID
													INNER JOIN asa_classes ac ON acsec.classID = ac.classID
													INNER JOIN asa_section_master asecm ON acsec.sectionMasterID = asecm.sectionMasterID
													INNER JOIN asa_school_session_class_period_timings asscpt ON acttd.periodTimingID = asscpt.periodTimingID
													INNER JOIN asa_school_timing_parts_master astpm ON astpm.schoolTimingPartID = asscpt.schoolTimingPartID 
													INNER JOIN asa_school_session_class_daywise_timings asscdt ON actt.daywiseTimingsID = asscdt.daywiseTimingsID
													INNER JOIN asa_class_subjects acs ON acs.classSubjectID = acttd.classSubjectID 
													INNER JOIN asa_subject_master asm ON asm.subjectID = acs.subjectID  
													WHERE atc.branchStaffID = :|1 ORDER BY astpm.schoolTimingPartID;');
            
            if ($BranchStaffID > 0)
            {
                $RSSearch->Execute($BranchStaffID);
            }
            else
            {
                $RSSearch->Execute($this->BranchStaffID);
            }
            
			
			if ($RSSearch->Result->num_rows > 0)
            {
				while ($SearchRow = $RSSearch->FetchRow())
				{
					$AllPeriodsDetails[$SearchRow->classTimeTableDetailID]['ClassName'] = $SearchRow->className;
					$AllPeriodsDetails[$SearchRow->classTimeTableDetailID]['ClassSymbol'] = $SearchRow->classSymbol;
					$AllPeriodsDetails[$SearchRow->classTimeTableDetailID]['SectionName'] = $SearchRow->sectionName;

                    $AllPeriodsDetails[$SearchRow->classTimeTableDetailID]['PeriodName'] = $SearchRow->timingPart;
					$AllPeriodsDetails[$SearchRow->classTimeTableDetailID]['Subject'] = $SearchRow->subject;
					$AllPeriodsDetails[$SearchRow->classTimeTableDetailID]['PeriodStartTime'] = $SearchRow->periodStartTime;
					$AllPeriodsDetails[$SearchRow->classTimeTableDetailID]['PeriodEndTime'] = $SearchRow->periodEndTime;
					$AllPeriodsDetails[$SearchRow->classTimeTableDetailID]['DayID'] = $SearchRow->dayID;
				}	
            }

            return $AllPeriodsDetails;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at AppParentDetail::GetTimeTable(). Stack Trace: ' . $e->getTraceAsString());

            return $AllPeriodsDetails;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception at AppParentDetail::GetTimeTable(). Stack Trace: ' . $e->getTraceAsString());

            return $AllPeriodsDetails;
        }
	}

	public function IsClassSectionValid($ClassSectionID)
	{
		$AllPeriodsDetails = array();

		try
        {
			# NOTICES
	        $RSSearch = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords 
	        										FROM asa_class_time_table actt 
	        										INNER JOIN asa_class_time_table_details acttd ON actt.classTimeTableID = acttd.classTimeTableID 
	        										INNER JOIN asa_teacher_classes atc ON acttd.teacherClassID = atc.teacherClassID 
	        										WHERE actt.classSectionID = :|1 AND atc.branchStaffID = :|2;');

            $RSSearch->Execute($ClassSectionID, $this->BranchStaffID);
			
			if ($RSSearch->FetchRow()->totalRecords > 0)
			{
				return true;
			}

            return false;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at AppParentDetail::IsClassSectionValid(). Stack Trace: ' . $e->getTraceAsString());

            return false;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception at AppParentDetail::IsClassSectionValid(). Stack Trace: ' . $e->getTraceAsString());

            return false;
        }
	}
	// END OF PUBLIC METHODS	//
	
	// START OF STATIC METHODS	//
	
	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//
	private function GetBranchStaffDetailByUniqueAppToken()
	{
		$RSBranchStaffDetail = $this->DBObject->Prepare('SELECT abs.branchStaffID, abs.staffCategory, userID
														 FROM asa_branch_staff abs 
														 INNER JOIN users u ON u.userName = abs.userName 
														 WHERE userID = (SELECT userID FROM user_sessions WHERE uniqueToken = :|1 LIMIT 1) AND abs.isActive = 1
														 LIMIT 1;');
		
		$RSBranchStaffDetail->Execute($this->UniqueAppToken);
		//$RSBranchStaffDetail = $this->DBObject->Prepare('SELECT branchStaffID FROM asa_branch_staff WHERE uniqueAppToken = :|1 LIMIT 1;');
		
		$BranchStaffDetailRow = $RSBranchStaffDetail->FetchRow();

		$this->BranchStaffID = $BranchStaffDetailRow->branchStaffID;
		$this->StaffCategory = $BranchStaffDetailRow->staffCategory;
		$this->UserID = $BranchStaffDetailRow->userID;
	}
}
?>
<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.db_connect.php');
error_reporting(E_ALL);

class APIHelpers
{
	static function GetUniqueToken()
	{
		try
		{
			$DBConnObject = new DBConnect();
			
			$UniqueToken = md5(uniqid(rand(), true));

			$RSCheckUniqueTokenExists = $DBConnObject->Prepare('SELECT COUNT(*) AS totalRecords FROM user_sessions WHERE uniqueToken = :|1;');
			$RSCheckUniqueTokenExists->Execute($UniqueToken);

			if ($RSCheckUniqueTokenExists->FetchRow()->totalRecords > 0)
			{
				return self::GetUniqueToken();
			}

			return $UniqueToken;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at APIHelpers::GetUniqueToken(). Stack Trace: ' . $e->getTraceAsString());
			return '';
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at APIHelpers::GetUniqueToken(). Stack Trace: ' . $e->getTraceAsString());
			return '';
		}
	}
	
	// save user device token
	static function SaveFcmToken($UniqueToken, $FcmToken)
	{
		try
		{
			$DBConnObject = new DBConnect();

			$RSUpdate = $DBConnObject->Prepare("UPDATE user_sessions SET fcmToken = :|1 WHERE uniqueToken = :|2 LIMIT 1;");			
			$RSUpdate->Execute($FcmToken, $UniqueToken);

			return true;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at APIHelpers::SaveFcmToken(). Stack Trace: ' . $e->getTraceAsString());
			return false;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at APIHelpers::SaveFcmToken(). Stack Trace: ' . $e->getTraceAsString());
			return false;
		}
	}
	
	// save app log
	static function SaveAppLog($BranchStaffID = 0, $AppName, $Duration)
	{
		try
		{
			$DBConnObject = new DBConnect();

			$RSUpdate = $DBConnObject->Prepare('INSERT INTO asa_teacher_current_day_app_log (branchStaffID, appName, duration, date) 
											    VALUES (:|1, :|2, :|3, NOW());');			
			$RSUpdate->Execute($BranchStaffID, $AppName, $Duration);

			return true;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at APIHelpers::SaveAppLog(). Stack Trace: ' . $e->getTraceAsString());
			return false;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at APIHelpers::SaveAppLog(). Stack Trace: ' . $e->getTraceAsString());
			return false;
		}
	}
	
	public function FetchChatDashboard($UserType, $UserID)
	{
	    $ChatDashboard = array();

        $DBConnObject = new DBConnect();

        try
        {
	        $RSSearch = $DBConnObject->Prepare('SELECT * FROM asa_chat_rooms acr WHERE (firstUserType = :|1 AND firstRecordID = :|2) OR (secondUserType = :|3 AND secondRecordID = :|4);');
            $RSSearch->Execute($UserType, $UserID, $UserType, $UserID);

            if ($RSSearch->Result->num_rows <= 0)
            {
                return $ChatDashboard;
            }

            while ($SearchRow = $RSSearch->FetchRow())
            {
                $ChatWithUserType = '';
                $ChatWithUserID = '';
                
                $FirstName = '';
                $LastName = '';
                $Photo = '';
                
                if ($SearchRow->firstUserType == $UserType && $SearchRow->firstRecordID == $UserID)
                {
                    $ChatWithUserType = $SearchRow->secondUserType;
                    $ChatWithUserID = $SearchRow->secondRecordID;
                    
                    if ($ChatWithUserType == 'Management' || $ChatWithUserType == 'Teacher')
                    {
                        $SearchChatWithDetails = $DBConnObject->Prepare('SELECT firstName, lastName, staffPhoto FROM asa_branch_staff WHERE branchStaffID = :|1 LIMIT 1;');
                        $SearchChatWithDetails->Execute($SearchRow->secondRecordID);
                        
                        if ($SearchChatWithDetails->Result->num_rows <= 0)
                        {
                            error_log('Criticle Error: Chat records not found.');
                        }
                        
                        $SearchChatWithRow = $SearchChatWithDetails->FetchRow();
                        
                        $FirstName = $SearchChatWithRow->firstName;
                        $LastName = $SearchChatWithRow->lastName;
                        $Photo = $SearchChatWithRow->staffPhoto;
                    }
                    else
                    {
                        $SearchChatWithDetails = $DBConnObject->Prepare('SELECT firstName, lastName, studentPhoto 
                                                                         FROM asa_student_details asd 
                                                                         INNER JOIN asa_students astu ON astu.studentID = asd.studentID 
                                                                         WHERE astu.studentID = :|1 LIMIT 1;');
                                                                         
                        $SearchChatWithDetails->Execute($SearchRow->secondRecordID);
                        
                        if ($SearchChatWithDetails->Result->num_rows <= 0)
                        {
                            error_log('Criticle Error: Chat records not found.');
                        }
                        
                        $SearchChatWithRow = $SearchChatWithDetails->FetchRow();
                        
                        $FirstName = $SearchChatWithRow->firstName;
                        $LastName = $SearchChatWithRow->lastName;
                        $Photo = $SearchChatWithRow->studentPhoto;
                    }
                }
                else
                {
                    $ChatWithUserType = $SearchRow->firstUserType;
                    $ChatWithUserID = $SearchRow->firstRecordID;
                    
                    if ($ChatWithUserType == 'Management' || $ChatWithUserType == 'Teacher')
                    {
                        $SearchChatWithDetails = $DBConnObject->Prepare('SELECT firstName, lastName, staffPhoto FROM asa_branch_staff WHERE branchStaffID = :|1 LIMIT 1;');
                        $SearchChatWithDetails->Execute($SearchRow->firstRecordID);
                        
                        if ($SearchChatWithDetails->Result->num_rows <= 0)
                        {
                            error_log('Criticle Error: Chat records not found.');
                        }
                        
                        $SearchChatWithRow = $SearchChatWithDetails->FetchRow();
                        
                        $FirstName = $SearchChatWithRow->firstName;
                        $LastName = $SearchChatWithRow->lastName;
                        $Photo = $SearchChatWithRow->staffPhoto;
                    }
                    else
                    {
                        $SearchChatWithDetails = $DBConnObject->Prepare('SELECT firstName, lastName, studentPhoto 
                                                                         FROM asa_student_details asd 
                                                                         INNER JOIN asa_students astu ON astu.studentID = asd.studentID 
                                                                         WHERE astu.studentID = :|1 LIMIT 1;');
                                                                         
                        $SearchChatWithDetails->Execute($SearchRow->firstRecordID);
                        
                        if ($SearchChatWithDetails->Result->num_rows <= 0)
                        {
                            error_log('Criticle Error: Chat records not found.');
                        }
                        
                        $SearchChatWithRow = $SearchChatWithDetails->FetchRow();
                        
                        $FirstName = $SearchChatWithRow->firstName;
                        $LastName = $SearchChatWithRow->lastName;
                        $Photo = $SearchChatWithRow->studentPhoto;
                    }
                }
                
                $ChatDashboard[$SearchRow->chatRoomID]['UserType'] = $ChatWithUserType;
                $ChatDashboard[$SearchRow->chatRoomID]['ReportID'] = $ChatWithUserID;
                
                $ChatDashboard[$SearchRow->chatRoomID]['FirstName'] = $FirstName;
                $ChatDashboard[$SearchRow->chatRoomID]['LastName'] = $LastName;
                $ChatDashboard[$SearchRow->chatRoomID]['Photo'] = $Photo;
            }

            return $ChatDashboard;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at APIHelpers::FetchChatDashboard(). Stack Trace: ' . $e->getTraceAsString());
            return $ChatDashboard;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception at APIHelpers::FetchChatDashboard(). Stack Trace: ' . $e->getTraceAsString());
            return $ChatDashboard;
        }
	}
}
?>
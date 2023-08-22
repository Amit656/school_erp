<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.db_connect.php');

error_reporting(E_ALL);

class ChatSettings
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	
	private $ChatSettingID;
	private $FromUserType;
	private $ToUserType;
	private $IsActive;
	
	// PUBLIC METHODS START HERE	//
	public function __construct($ChatSettingID = 0)
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;
		
		if ($ChatSettingID != 0)
		{
			$this->ChatSettingID = $ChatSettingID;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetChatSettingByID();
		}
		else
		{
			//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
			$this->ChatSettingID = 0;
			$this->FromUserType = '';
			$this->ToUserType = '';
			$this->IsActive = 0;
		}
	}
	
	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetChatSettingID()
	{
		return $this->ChatSettingID;
	}
	
	public function GetFromUserType()
	{
		return $this->FromUserType;
	}
	public function SetFromUserType($FromUserType)
	{
		$this->FromUserType = $FromUserType;
	}
	
	public function GetToUserType()
	{
		return $this->ToUserType;
	}
	public function SetToUserType($ToUserType)
	{
		$this->ToUserType = $ToUserType;
	}
	
	public function GetIsActive()
	{
		return $this->IsActive;
	}
	public function SetIsActive($IsActive)
	{
		$this->IsActive = $IsActive;
	}
	
	public function GetLastErrorCode()
	{
		return $this->LastErrorCode;
	}
	
	//  END OF GETTER AND SETTER FUNCTIONS 	//
	
	public function Save()
	{
		try
		{
			return $this->SaveDetails();
		}
		catch (ApplicationDBException $e)
		{
			$this->LastErrorCode = $e->getCode();
			return false;
		}
		catch (ApplicationARException $e)
		{
			$this->LastErrorCode = $e->getCode();
			return false;
		}
		catch (Exception $e)
		{
			$this->LastErrorCode = APP_ERROR_UNDEFINED_ERROR;
			return false;
		}
	}
	
	public function ToggleChatSetting()
	{
		try
		{
			$RSToggleChatSetting = $this->DBObject->Prepare('UPDATE asa_chat_settings SET isActive = !(isActive) WHERE chatSettingID = :|1 LIMIT 1;');
			$RSToggleChatSetting->Execute($this->ChatSettingID);

			return true;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at ChatSettings::ToggleChatSetting(). Stack Trace: ' . $e->getTraceAsString());
			return false;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at ChatSettings::ToggleChatSetting(). Stack Trace: ' . $e->getTraceAsString());
			return false;
		}
	}
		
	// END OF PUBLIC METHODS	//
	
	// START OF STATIC METHODS	//
	static function GetChatEnabledBetweenUsersOrNot($FromUserType, $ToUserType)
	{
		try
        {
            $DBConnObject = new DBConnect();

            $RSChatSettings = $DBConnObject->Prepare('SELECT COUNT(*) AS totalRecords FROM asa_chat_settings WHERE fromUserType = :|1 AND toUserType = :|2 AND isActive = 1;');
            $RSChatSettings->Execute($FromUserType, $ToUserType);

            if ($RSChatSettings->FetchRow()->totalRecords > 0) 
            {
				return true;
            }
			
			return false;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at ChatSettings::GetChatEnabledBetweenUsersOrNot(). Stack Trace: ' . $e->getTraceAsString());
            return false;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception at ChatSettings::GetChatEnabledBetweenUsersOrNot(). Stack Trace: ' . $e->getTraceAsString());
            return false;
        }
	}
	
	static function GetChatEnabledBetweenUsersList()
	{
		$ChatEnabledBetweenUsersList = array();

		try
        {
            $DBConnObject = new DBConnect();

            $RSChatSettings = $DBConnObject->Prepare('SELECT * FROM asa_chat_settings ORDER BY chatSettingID ASC;');
            $RSChatSettings->Execute();

            if ($RSChatSettings->Result->num_rows <= 0) 
            {
                return $ChatEnabledBetweenUsersList;
            }
			
			while ($SearchChatSettingsRow = $RSChatSettings->FetchRow())
			{
				$ChatEnabledBetweenUsersList[$SearchChatSettingsRow->chatSettingID]['FromUserType'] = $SearchChatSettingsRow->fromUserType;
				$ChatEnabledBetweenUsersList[$SearchChatSettingsRow->chatSettingID]['ToUserType'] = $SearchChatSettingsRow->toUserType;
				$ChatEnabledBetweenUsersList[$SearchChatSettingsRow->chatSettingID]['IsActive'] = $SearchChatSettingsRow->isActive;
			}

            return $ChatEnabledBetweenUsersList;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at ChatSettings::GetChatEnabledBetweenUsersList(). Stack Trace: ' . $e->getTraceAsString());
            return $ChatEnabledBetweenUsersList;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception at ChatSettings::GetChatEnabledBetweenUsersList(). Stack Trace: ' . $e->getTraceAsString());
            return $ChatEnabledBetweenUsersList;
        }
	}
	
	static function SaveChatSettings($ChatSettings)
	{
		try
        {
            $DBConnObject = new DBConnect();

			$DBConnObject->BeginTransaction();
			
			$RSUpdateAllSettings = $DBConnObject->Prepare('UPDATE asa_chat_settings SET isActive = 0;');
            $RSUpdateAllSettings->Execute();
			
			foreach ($ChatSettings as $ChatSettingID => $Value)
			{
				$RSUpdateSetting = $DBConnObject->Prepare('UPDATE asa_chat_settings SET isActive = 1 WHERE chatSettingID = :|1 LIMIT 1;');
				$RSUpdateSetting->Execute($ChatSettingID);
			}
			
			$DBConnObject->CommitTransaction();

            return true;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at ChatSettings::SaveChatSettings(). Stack Trace: ' . $e->getTraceAsString());
			$DBConnObject->RollBackTransaction();
            return false;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception at ChatSettings::SaveChatSettings(). Stack Trace: ' . $e->getTraceAsString());
			$DBConnObject->RollBackTransaction();
            return false;
        }
	}
	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//
	private function SaveDetails()
	{
		if ($this->ChatSettingID == 0)
		{
			$RSSave = $this->DBObject->Prepare('INSERT INTO asa_chat_settings (fromUserType, toUserType, isActive)
														VALUES (:|1, :|2, :|3);');
			
			$RSSave->Execute($this->FromUserType, $this->ToUserType, $this->IsActive);
			
			$this->ChatSettingID = $RSSave->LastID;
		}
		else
		{
			$RSUpdate = $this->DBObject->Prepare('UPDATE asa_chat_settings
													SET	fromUserType = :|1, 
														toUserType = :|2, 
														isActive = :|3 
													WHERE chatSettingID = :|4 LIMIT 1;');
													
			$RSUpdate->Execute($this->FromUserType, $this->ToUserType, $this->IsActive, $this->ChatSettingID);
		}
		
		return true;
	}
	
	private function GetChatSettingByID()
	{
		$RSChatSetting = $this->DBObject->Prepare('SELECT * FROM asa_chat_settings WHERE chatSettingID = :|1 LIMIT 1;');
		$RSChatSetting->Execute($this->ChatSettingID);
		
		$ChatSettingRow = $RSChatSetting->FetchRow();
		
		$this->SetAttributesFromDB($ChatSettingRow);				
	}
	
	private function SetAttributesFromDB($ChatSettingRow)
	{
		$this->ChatSettingID = $ChatSettingRow->chatSettingID;
		$this->FromUserType = $ChatSettingRow->fromUserType;
		$this->ToUserType = $ChatSettingRow->toUserType;
		$this->IsActive = $ChatSettingRow->isActive;
	}	
}
?>
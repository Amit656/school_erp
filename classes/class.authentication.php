<?php
require_once('class.db_connect.php');
error_reporting(E_ALL);

class ApplicationAuthentication
{
	private $DBObject = NULL;
	
	public $UserName = NULL;
	public $Password = NULL;
	
	public function __construct()
	{
		$this->DBObject = new DBConnect;
	}

	public function Login($UserIP)
	{
		$RSLogin = $this->DBObject->Prepare("SELECT COUNT(*) AS RecordCount FROM users WHERE userName = :|1 AND password = :|2 AND isActive = 1;");
		$RSLogin->Execute($this->UserName, sha1($this->Password));
		
		$LoginRow = $RSLogin->FetchRow();
		
		if ($LoginRow->RecordCount != 1)
		{
			throw new ApplicationAuthException('', APP_AUTH_ERROR_LOGIN_FAIL);
		}
		
		if (!$this->CheckUserTimeLimit($this->UserName))
		{
			throw new ApplicationAuthException('', APP_AUTH_ERROR_LOGIN_FAIL);
		}
		
		$RSUpdate = $this->DBObject->Prepare("UPDATE users SET lastLoginDate = lastLogoutDate WHERE userName = :|1 LIMIT 1;");			
		$RSUpdate->Execute($this->UserName);
		
		$RSLogUserTime = $this->DBObject->Prepare('INSERT INTO user_time_logs (userName, userIPAddress, loginDateTime) VALUES (:|1, :|2, NOW());');
		$RSLogUserTime->Execute($this->UserName, $UserIP);
		
		$this->InitializeSession();
	}
	
	public function AppLogin($UniqueToken, $LoggedUserRole)
	{
		$RSLogin = $this->DBObject->Prepare("SELECT COUNT(*) AS RecordCount FROM users WHERE userName = :|1 AND password = :|2 AND roleID = :|3 AND isActive = 1;");
		$RSLogin->Execute($this->UserName, sha1($this->Password), $LoggedUserRole);
		
		$LoginRow = $RSLogin->FetchRow();
		
		if ($LoginRow->RecordCount != 1)
		{
			throw new ApplicationAuthException('', APP_AUTH_ERROR_LOGIN_FAIL);
		}
		
		if (!$this->CheckUserTimeLimit($this->UserName))
		{
			throw new ApplicationAuthException('', APP_AUTH_ERROR_LOGIN_FAIL);
		}

		$RSGetUser = $this->DBObject->Prepare("SELECT userID FROM users WHERE userName = :|1 LIMIT 1;");			
		$RSGetUser->Execute($this->UserName);

		$UserID = $RSGetUser->FetchRow()->userID;
		
		$RSUpdate = $this->DBObject->Prepare("UPDATE users SET lastLoginDate = lastLogoutDate WHERE userID = :|1 LIMIT 1;");			
		$RSUpdate->Execute($UserID);
		
		$RSLogUserTime = $this->DBObject->Prepare('INSERT INTO user_time_logs (userName, userIPAddress, loginDateTime) VALUES (:|1, :|2, NOW());');
		$RSLogUserTime->Execute($this->UserName, 'AppLogin');

		$RSLogUserTime = $this->DBObject->Prepare('INSERT INTO user_sessions (userID, uniqueToken, createDate) VALUES (:|1, :|2, NOW());');
		$RSLogUserTime->Execute($UserID, $UniqueToken);
	}

	public function LoginAfterRegistration()
	{
		$this->InitializeSession();
	}
	
	private function CheckUserTimeLimit($UserName)
	{
		$RSUserTimeLimit = $this->DBObject->Prepare('SELECT accountExpiryDate, hasLoginTimeLimit, roleID, loginStartTime, loginEndTime FROM users WHERE userName = :|1 LIMIT 1;');
		$RSUserTimeLimit->Execute($UserName);
		
		if($RSUserTimeLimit->Result->num_rows <= 0)
		{
			return false;
		}		
		
		$SearchRow = $RSUserTimeLimit->FetchRow();
		
		$CurrentDate = date('m/d/Y');

		$UserStartTime = $CurrentDate.' '.$SearchRow->loginStartTime;
		$UserEndTime = $CurrentDate.' '.$SearchRow->loginEndTime;
		
		if ($SearchRow->hasLoginTimeLimit == 0 || $SearchRow->roleID == 11)
		{
			return true;
		}
		
		if ($SearchRow->accountExpiryDate != '0000-00-00 00:00:00' && (time() >= strtotime($SearchRow->accountExpiryDate)))
		{			
			return false;
		}
			
		if(strtotime($UserStartTime) <= time() && strtotime($UserEndTime) > time())
		{
			return true;
		}
		
		return false;
	}
	
	private function InitializeSession() 
	{
		session_start();
		$_SESSION['ValidUser'] = $this->UserName;
	}
	
	private function UpdateUserLogoutTime($UserName)
	{		
		$RSUpdate = $this->DBObject->Prepare("UPDATE users SET lastLogoutDate = NOW() WHERE userName = :|1 LIMIT 1;");			
		$RSUpdate->Execute($UserName);
	}
	
	private function UpdateUserTimeLog($UserName)
	{
		$RSUserTimeLogs = $this->DBObject->Prepare('SELECT userTimeLogID FROM user_time_logs WHERE userName = :|1 ORDER BY loginDateTime DESC LIMIT 1;');
		$RSUserTimeLogs->Execute($UserName);
		
		if($RSUserTimeLogs->Result->num_rows > 0)
		{
			$RSUpdateUserTimeLogs = $this->DBObject->Prepare('UPDATE user_time_logs SET logoutDateTime = NOW() WHERE userTimeLogID = :|1 LIMIT 1;');			
			$RSUpdateUserTimeLogs->Execute($RSUserTimeLogs->FetchRow()->userTimeLogID);
		}					
	}
	
	public function CheckValidUser()
	{
		session_start();
		if (!isset($_SESSION['ValidUser']))
		{
			throw new ApplicationAuthException('', APP_AUTH_ERROR_INVALID_USER);
		}
		
		if (!$this->CheckUserTimeLimit($_SESSION['ValidUser']))
		{
			$this->Logout();
			throw new ApplicationAuthException('', APP_AUTH_ERROR_LOGIN_FAIL);
		}
		
		$this->UpdateUserLogoutTime($_SESSION['ValidUser']);
		
		return($_SESSION['ValidUser']);
	}
	
	public function Logout()
	{
		$OutUser = '';
		if(!isset($_SESSION) || !is_array($_SESSION))
		{
			session_start();
		}
		// GET THE CURRENT USER TO A VARIABLE //
		if (isset($_SESSION['ValidUser']))
		{
			$OutUser = $_SESSION['ValidUser'];
		}
				
		// NOW UNSET THE SESSION VARIABLES AND DESTROY THE SESSION //
		$_SESSION['ValidUser'] = null;
		unset($_SESSION['ValidUser']);
		$_SESSION = array();
		$SessionDestroyResult = session_destroy();
		
		// CHECK IF THE USER WAS LOGGED OUT //
		if (!empty($OutUser))
		{
			if($SessionDestroyResult)
			{
				// SUCCESSFUL LOGOUT //
				$this->UpdateUserLogoutTime($OutUser);
				$this->UpdateUserTimeLog($OutUser);
				return true;
			}
			else
			{
				// SESSION DESTROY FAILED AND HENCE CANNOT LOGOUT
				$_SESSION['ValidUser'] = $OutUser;
				throw new ApplicationAuthException('', APP_AUTH_ERROR_CANNOT_LOGOUT);
			}
		}
		else
		{
			// SESSION LOGOUT WAS CALLED WITHOUT A VALID LOGGED USER //
			throw new ApplicationAuthException('', APP_AUTH_ERROR_LOGOUT_WITHOUT_LOGIN);
		}
	}
}

?>
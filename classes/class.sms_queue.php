<?php
require_once('class.db_connect.php');
error_reporting(E_ALL);
	
class SMSQueue
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	
	private $SMSQueueID;
	private $PhoneNumber;
	private $SMSMessage;

	private $SMSStatus;
	private $SentDateTime;

	private $CreateUserID;
	private $CreateDate;
		
	// PUBLIC METHODS START HERE	//
	public function __construct($SMSQueueID = 0)
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;
		
		if($SMSQueueID != 0)
		{
			$this->SMSQueueID = $SMSQueueID;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetSMSQueueByID();
		}
		else
		{
			//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
			$this->SMSQueueID = 0;
			$this->PhoneNumber = '';
			$this->SMSMessage = '';

			$this->SMSStatus = '';
			$this->SentDateTime = '';
			
			$this->CreateUserID = 0;
			$this->CreateDate = '';
		}
	}
	
	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetSMSQueueID()
	{
		return $this->SMSQueueID;
	}
		
	public function GetPhoneNumber()
	{
		return $this->PhoneNumber;
	}	
	public function SetPhoneNumber($PhoneNumber)
	{
		$this->PhoneNumber = $PhoneNumber;
	}
	
	public function GetSMSMessage()
	{
		return $this->SMSMessage;
	}	
	public function SetSMSMessage($SMSMessage)
	{
		$this->SMSMessage = $SMSMessage;
	}

	public function GetSMSStatus()
	{
		return $this->SMSStatus;
	}	
	public function SetSMSStatus($SMSStatus)
	{
		$this->SMSStatus = $SMSStatus;
	}

	public function GetSentDateTime()
	{
		return $this->SentDateTime;
	}
	
	public function GetCreateUserID()
	{
		return $this->CreateUserID;
	}
	public function SetCreateUserID($CreateUserID)
	{
		$this->CreateUserID = $CreateUserID;
	}

	public function GetCreateDate()
	{
		return $this->CreateDate;
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
		
	// END OF PUBLIC METHODS	//
	
	// START OF STATIC METHODS	//
	static function SMSInQueue()
	{
		$SMSInQueue = array();

		try
		{
			$DBConnObject = new DBConnect();

			$RSTotalSMS = $DBConnObject->Prepare('SELECT COUNT(smsQueueID) AS totalSMS FROM sms_queue WHERE smsStatus = "Not Sent";');
			$RSTotalSMS->Execute();

			$SMSInQueue['TotalSMS'] = $RSTotalSMS->FetchRow()->totalSMS;

			$RSTotalSMSLength = $DBConnObject->Prepare('SELECT SUM(CEIL((LENGTH(smsMessage))/160)) AS totalSMSLength FROM sms_queue WHERE smsStatus = "Not Sent";');
			$RSTotalSMSLength->Execute();	

			$SMSInQueue['TotalSMSLength'] = $RSTotalSMSLength->FetchRow()->totalSMSLength;

			return $SMSInQueue;
		}

		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at School::SMSInQueue(). Stack Trace: ' . $e->getTraceAsString());
			return $SMSInQueue;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at School::SMSInQueue(). Stack Trace: ' . $e->getTraceAsString());
			return $SMSInQueue;
		}		
	}

	static function GetSMSReport(&$TotalRecords = 0, &$TotalSMSUsed = 0, $GetTotalsOnly = false, $Filters = array(), $Start = 0, $Limit = 100)
	{
		$SMSList = array();
		$QueryString = '';

		try
		{
			$DBConnObject = new DBConnect();

			$Conditions = array();
			
			if (count($Filters) > 0)
			{
				if (!empty($Filters['CreateDate']))
				{
					$Conditions[] = 'DATE(sq.createDate) = '. $DBConnObject->RealEscapeVariable($Filters['CreateDate']);
				}
					
				if (!empty($Filters['CreateFromDate']))
				{
					$Conditions[] = 'DATE(sq.createDate) BETWEEN '. $DBConnObject->RealEscapeVariable($Filters['CreateFromDate']) .'AND'. $DBConnObject->RealEscapeVariable($Filters['CreateToDate']);
				}

				if (!empty($Filters['SMSMessage']))
				{
					$Conditions[] = 'sq.smsMessage LIKE '.$DBConnObject->RealEscapeVariable('%' . $Filters['SMSMessage'] . '%');
				}

				if (!empty($Filters['PhoneNumber']))
				{
					$Conditions[] = 'sq.phoneNumber = '.$DBConnObject->RealEscapeVariable($Filters['PhoneNumber']);
				}

				if (!empty($Filters['SMSStatus']))
				{
					$Conditions[] = 'sq.smsStatus = '.$DBConnObject->RealEscapeVariable($Filters['SMSStatus']);
				}

			}
			
			$QueryString = '';

			if (count($Conditions) > 0)
			{
				$QueryString = implode(') AND (', $Conditions);
				
				$QueryString = ' WHERE (' . $QueryString . ')';
			}

			if ($GetTotalsOnly)
			{
				
				$RSTotals = $DBConnObject->Prepare('SELECT COUNT(*) AS totalRecords FROM sms_queue sq'.$QueryString.';');
				$RSTotals->Execute();
				
				$TotalRecords = $RSTotals->FetchRow()->totalRecords;

				$RSTotalSMSUsed = $DBConnObject->Prepare('SELECT SUM(smsUsed) AS totalSMSUsed FROM sms_queue sq'.$QueryString.';');
				$RSTotalSMSUsed->Execute();
				
				$TotalSMSUsed = $RSTotalSMSUsed->FetchRow()->totalSMSUsed;
				return;
			}
			
			$RSSearch = $DBConnObject->Prepare('SELECT sq.*, u.userName 
			                                    FROM sms_queue sq 
			                                    LEFT JOIN users u ON u.userID = sq.createUserID 
			                                    '.$QueryString.' ORDER BY sq.createDate DESC LIMIT '.(int) $Start.', '.(int) $Limit.';');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $SMSList;
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$SMSList[$SearchRow->smsQueueID]['PhoneNumber'] = $SearchRow->phoneNumber;
				$SMSList[$SearchRow->smsQueueID]['SMSMessage'] = $SearchRow->smsMessage;
				$SMSList[$SearchRow->smsQueueID]['SMSStatus'] = $SearchRow->smsStatus;
				$SMSList[$SearchRow->smsQueueID]['SMSUsed'] = $SearchRow->smsUsed;
				$SMSList[$SearchRow->smsQueueID]['CreateDate'] = $SearchRow->createDate;
				
				$SMSList[$SearchRow->smsQueueID]['CreateByUser'] = $SearchRow->userName;
			}
			
			return $SMSList;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at SMSQueue::GetSMSReport(). Stack Trace: '.$e->getTraceAsString());
			return $SMSList;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at SMSQueue::GetSMSReport(). Stack Trace: '.$e->getTraceAsString());
			return $SMSList;
		}		
	}
	
	static function UpdateSMSSent($SMSQueueID)
	{
		try
		{
			$DBConnObject = new DBConnect();

			$RSUpdateSMSQueue = $DBConnObject->Prepare('UPDATE sms_queue SET smsStatus = \'Pending\', sentDateTime = NOW() WHERE smsQueueID = :|1 LIMIT 1;');
			$RSUpdateSMSQueue->Execute($SMSQueueID);

			return true;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException: SMSQueue::UpdateSMSSent(). SMS Queue ID:'.$SMSQueueID.', Error Message: '.$e->getMessage().', Error Code: '.$e->getCode().', Stack Trace: '.$e->getTraceAsString());
			return false;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception: SMSQueue::UpdateSMSSent(). SMS Queue ID:'.$SMSQueueID.', Error Message: '.$e->getMessage().', Error Code: '.$e->getCode().', Stack Trace: '.$e->getTraceAsString());
			return false;
		}		
	}
	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//
	private function SaveDetails()
	{
		if ($this->SMSQueueID == 0)
		{
		    $SMSUsed = (CEIL(strlen($this->SMSMessage)/160));
			$RSSave = $this->DBObject->Prepare('INSERT INTO sms_queue (phoneNumber, smsMessage, smsUsed, createUserID, createDate)
														VALUES (:|1, :|2, :|3, :|4, NOW());');

			$RSSave->Execute($this->PhoneNumber, $this->SMSMessage, $SMSUsed, $this->CreateUserID);
			
			$this->SMSQueueID = $RSSave->LastID;
		}
		
		return true;
	}
	
	private function GetSMSQueueByID()
	{
		$RSSMSQueue = $this->DBObject->Prepare('SELECT * FROM sms_queue WHERE smsQueueID = :|1 LIMIT 1;');
		$RSSMSQueue->Execute($this->SMSQueueID);
		
		$SMSQueueRow = $RSSMSQueue->FetchRow();
		
		$this->SetAttributesFromDB($SMSQueueRow);				
	}
	
	private function SetAttributesFromDB($SMSQueueRow)
	{
		$this->SMSQueueID = $SMSQueueRow->smsQueueID;
		$this->PhoneNumber = $SMSQueueRow->phoneNumber;
		$this->SMSMessage = $SMSQueueRow->smsMessage;

		$this->SMSStatus = $SMSQueueRow->smsStatus;
		$this->SentDateTime = $SMSQueueRow->sentDateTime;
		
		$this->CreateUserID = $SMSQueueRow->createUserID;
		$this->CreateDate = $SMSQueueRow->createDate;
	}
}
?>
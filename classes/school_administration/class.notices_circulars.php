<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.db_connect.php');
error_reporting(E_ALL);

class NoticeCircular
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	
	private $NoticeCircularID;
	private $NoticeCircularDate;

	private $NoticeCircularSubject;
	private $NoticeCircularDetails;

	private $IsActive;
	private $CreateUserID;
	private $CreateDate;

	private $NoticeCircularApplicableFor = array();
	
	// PUBLIC METHODS START HERE	//
	public function __construct($NoticeCircularID = 0)
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;
		
		if($NoticeCircularID != 0)
		{
			$this->NoticeCircularID = $NoticeCircularID;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetNoticeCircularByID();
		}
		else
		{
			//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
			$this->NoticeCircularID = 0;
			$this->NoticeCircularDate = '';

			$this->NoticeCircularSubject = '';
			$this->NoticeCircularDetails = '';

			$this->IsActive = 0;
			$this->CreateUserID = 0;
			$this->CreateDate = '0000-00-00 00:00:00';

			$this->NoticeCircularApplicableFor = array();
		}
	}
	
	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetNoticeCircularID()
	{
		return $this->NoticeCircularID;
	}
	
	public function GetNoticeCircularDate()
	{
		return $this->NoticeCircularDate;
	}
	public function SetNoticeCircularDate($NoticeCircularDate)
	{
		$this->NoticeCircularDate = $NoticeCircularDate;
	}

	public function GetNoticeCircularSubject()
	{
		return $this->NoticeCircularSubject;
	}
	public function SetNoticeCircularSubject($NoticeCircularSubject)
	{
		$this->NoticeCircularSubject = $NoticeCircularSubject;
	}
	
	public function GetNoticeCircularDetails()
	{
		return $this->NoticeCircularDetails;
	}
	public function SetNoticeCircularDetails($NoticeCircularDetails)
	{
		$this->NoticeCircularDetails = $NoticeCircularDetails;
	}

	public function GetIsActive()
	{
		return $this->IsActive;
	}
	public function SetIsActive($IsActive)
	{
		$this->IsActive = $IsActive;
	}

	public function GetCreateUserID()
	{
		return $this->CreateUserID;
	}
	public function SetCreateUserID($CreateUserID)
	{
		$this->CreateUserID = $CreateUserID;
	}

	public function GetNoticeCircularApplicableFor()
	{
		return $this->NoticeCircularApplicableFor;
	}
	public function SetNoticeCircularApplicableFor($NoticeCircularApplicableFor)
	{
		$this->NoticeCircularApplicableFor = $NoticeCircularApplicableFor;
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
			$this->DBObject->BeginTransaction();
			if ($this->SaveDetails())
			{
				$this->DBObject->CommitTransaction();
				return true;
			}
			
			$this->DBObject->RollBackTransaction();
			return false;
		}
		catch (ApplicationDBException $e)
		{
			$this->DBObject->RollBackTransaction();
			$this->LastErrorCode = $e->getCode();
			return false;
		}
		catch (ApplicationARException $e)
		{
			$this->DBObject->RollBackTransaction();
			$this->LastErrorCode = $e->getCode();
			return false;
		}
		catch (Exception $e)
		{
			$this->LastErrorCode = APP_ERROR_UNDEFINED_ERROR;
			return false;
		}
	}

	public function Remove()
    {
        try
        {
            $this->DBObject->BeginTransaction();
			if ($this->RemoveNoticeCircular())
			{
				$this->DBObject->CommitTransaction();
				return true;
			}
			
			$this->DBObject->RollBackTransaction();
			return false;
        }
        catch (ApplicationDBException $e)
        {
        	$this->DBObject->RollBackTransaction();
            $this->LastErrorCode = $e->getCode();
            return false;
        }
        catch (ApplicationARException $e)
        {
        	$this->DBObject->RollBackTransaction();
            $this->LastErrorCode = $e->getCode();
            return false;
        }
        catch (Exception $e)
        {
            $this->LastErrorCode = APP_ERROR_UNDEFINED_ERROR;
            return false;
        }
    }

	public function FillNoticeCircularApplicableFor()
    {
		try
        {	
        	$RSSearch = $this->DBObject->Prepare('SELECT * FROM asa_notices_circulars_applicable_for WHERE noticeCircularID = :|1;');
			$RSSearch->Execute($this->NoticeCircularID);

			if($RSSearch->Result->num_rows <= 0)
			{
				return true;
			}

			while($SearchRow = $RSSearch->FetchRow())
			{
				$this->NoticeCircularApplicableFor[$SearchRow->noticeCircularApplicableID]['ApplicableFor'] = $SearchRow->applicableFor;
				$this->NoticeCircularApplicableFor[$SearchRow->noticeCircularApplicableID]['StaffOrClassID'] = $SearchRow->staffOrClassID;
			}

			return true;   
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException: NoticeCircular::FillNoticeCircularApplicableFor(). Stack Trace: ' . $e->getTraceAsString());
            return false;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception: NoticeCircular::FillNoticeCircularApplicableFor(). Stack Trace: ' . $e->getTraceAsString());
            return false;
        }
    }
		
	// END OF PUBLIC METHODS	//
	
	// START OF STATIC METHODS	//
	static function NoticeCircularReports($IntervalDays = 0) // $IntervalDays used as last 30 days or last 20 days as you want
    {
        $AllNoticeCircularDetails = array();

        try
        {
        	$DBConnObject = new DBConnect();
        	
        	$QueryString = '';
        	
        	if ($IntervalDays > 0)
        	{
        	    $QueryString = ' WHERE nc.noticeCircularDate >= CURRENT_DATE - INTERVAL '. $IntervalDays .' DAY';
        	}
        	
            $RSSearch = $DBConnObject->Prepare('SELECT nc.*, u.userName AS createUserName FROM asa_notices_circulars nc 
            									INNER JOIN users u ON nc.createUserID = u.userID 
            									'. $QueryString .'
            									ORDER BY noticeCircularDate;');
            $RSSearch->Execute();            
            
            if ($RSSearch->Result->num_rows <= 0)
            {
                return $AllNoticeCircularDetails;
            }

            while($SearchRow = $RSSearch->FetchRow())
            {
            	$AllNoticeCircularDetails[$SearchRow->noticeCircularID]['NoticeCircularDate'] = $SearchRow->noticeCircularDate;
            	$AllNoticeCircularDetails[$SearchRow->noticeCircularID]['NoticeCircularSubject'] = $SearchRow->noticeCircularSubject;
            	$AllNoticeCircularDetails[$SearchRow->noticeCircularID]['NoticeCircularDetails'] = $SearchRow->noticeCircularDetails;
            	
            	$AllNoticeCircularDetails[$SearchRow->noticeCircularID]['TotalClassApplicable'] = 0;
				$AllNoticeCircularDetails[$SearchRow->noticeCircularID]['TotalStaffApplicable'] = 0;
				
				$RSSearchForApplicability = $DBConnObject->Prepare('SELECT applicableFor, COUNT(*) AS totalRecords FROM asa_notices_circulars_applicable_for
	            														WHERE noticeCircularID = :|1 GROUP BY applicableFor;');
	            $RSSearchForApplicability->Execute($SearchRow->noticeCircularID);

            	if ($RSSearchForApplicability->Result->num_rows > 0)
		        {
		            while ($SearchForApplicabilityRow = $RSSearchForApplicability->FetchRow()) 
		            {
		            	if ($SearchForApplicabilityRow->applicableFor == 'Class') 
		            	{
		            		$AllNoticeCircularDetails[$SearchRow->noticeCircularID]['TotalClassApplicable'] = $SearchForApplicabilityRow->totalRecords;
		            	}
		            	elseif ($SearchForApplicabilityRow->applicableFor == 'Staff') 
		            	{
		            		$AllNoticeCircularDetails[$SearchRow->noticeCircularID]['TotalStaffApplicable'] = $SearchForApplicabilityRow->totalRecords;
		            	}
		            }
		        }

            	$AllNoticeCircularDetails[$SearchRow->noticeCircularID]['IsActive'] = $SearchRow->isActive;
            	
            	$AllNoticeCircularDetails[$SearchRow->noticeCircularID]['CreateUserID'] = $SearchRow->createUserID;
				$AllNoticeCircularDetails[$SearchRow->noticeCircularID]['CreateUserName'] = $SearchRow->createUserName;
				
            	$AllNoticeCircularDetails[$SearchRow->noticeCircularID]['CreateDate'] = $SearchRow->createDate;
            }

            return $AllNoticeCircularDetails;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at NoticeCircular::NoticeCircularReports(). Stack Trace: ' . $e->getTraceAsString());
            return $AllNoticeCircularDetails;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception at NoticeCircular::NoticeCircularReports(). Stack Trace: ' . $e->getTraceAsString());
            return $AllNoticeCircularDetails;
        }
    }
	
	static function GetClassNotices($ClassID)
	{
		$Notices = array();

        try
        {
        	$DBConnObject = new DBConnect();
        	
            $RSSearch = $DBConnObject->Prepare('SELECT nc.*, u.userName AS createUserName 
												FROM asa_notices_circulars nc 
												INNER JOIN asa_notices_circulars_applicable_for anca ON anca.noticeCircularID = nc.noticeCircularID 
            									INNER JOIN users u ON nc.createUserID = u.userID 
												WHERE anca.applicableFor = \'Class\' AND anca.staffOrClassID = :|1 
												ORDER BY nc.noticeCircularDate DESC;');
            $RSSearch->Execute($ClassID);
            
            if ($RSSearch->Result->num_rows <= 0)
            {
                return $Notices;
            }

            while ($SearchRow = $RSSearch->FetchRow())
            {
            	$Notices[$SearchRow->noticeCircularID]['NoticeCircularDate'] = $SearchRow->noticeCircularDate;
            	$Notices[$SearchRow->noticeCircularID]['NoticeCircularSubject'] = $SearchRow->noticeCircularSubject;
            	$Notices[$SearchRow->noticeCircularID]['NoticeCircularDetails'] = $SearchRow->noticeCircularDetails;

            	$Notices[$SearchRow->noticeCircularID]['IsActive'] = $SearchRow->isActive;
            	
            	$Notices[$SearchRow->noticeCircularID]['CreateUserID'] = $SearchRow->createUserID;
				$Notices[$SearchRow->noticeCircularID]['CreateUserName'] = $SearchRow->createUserName;
				
            	$Notices[$SearchRow->noticeCircularID]['CreateDate'] = $SearchRow->createDate;
            }

            return $Notices;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at NoticeCircular::GetClassNotices(). Stack Trace: ' . $e->getTraceAsString());
            return $Notices;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception at NoticeCircular::GetClassNotices(). Stack Trace: ' . $e->getTraceAsString());
            return $Notices;
        }
	}
	
	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//
	private function SaveDetails()
	{
		if ($this->NoticeCircularID == 0)
		{	
			$RSSave = $this->DBObject->Prepare('INSERT INTO asa_notices_circulars (noticeCircularDate, noticeCircularSubject, noticeCircularDetails, 
																					isActive, createUserID, createDate)
														VALUES (:|1, :|2, :|3, :|4, :|5, NOW());');
		
			$RSSave->Execute($this->NoticeCircularDate, $this->NoticeCircularSubject, $this->NoticeCircularDetails, 
								$this->IsActive, $this->CreateUserID);
			
			$this->NoticeCircularID = $RSSave->LastID;
		}
		else
		{
			$RSUpdate = $this->DBObject->Prepare('UPDATE asa_notices_circulars
													SET	noticeCircularDate = :|1,
														noticeCircularSubject = :|2,
														noticeCircularDetails = :|3,
														isActive = :|4
													WHERE noticeCircularID = :|5 LIMIT 1;');
													
			$RSUpdate->Execute($this->NoticeCircularDate, $this->NoticeCircularSubject, $this->NoticeCircularDetails, 
							$this->IsActive, $this->NoticeCircularID);

			$RSDeleteNoticeCircularApplicableFor = $this->DBObject->Prepare('DELETE FROM asa_notices_circulars_applicable_for WHERE noticeCircularID = :|1;');
			$RSDeleteNoticeCircularApplicableFor->Execute($this->NoticeCircularID);
		}

		foreach ($this->NoticeCircularApplicableFor as $Key => $NoticeCircularApplicableForDetails) 
		{
			$RSSaveNoticeCircularApplicableFor = $this->DBObject->Prepare('INSERT INTO asa_notices_circulars_applicable_for (noticeCircularID, applicableFor, staffOrClassID)
												VALUES (:|1, :|2, :|3);');

			$RSSaveNoticeCircularApplicableFor->Execute($this->NoticeCircularID, $NoticeCircularApplicableForDetails['ApplicableFor'], $NoticeCircularApplicableForDetails['StaffOrClassID']);
		}
		
		return true;
	}
	
	private function RemoveNoticeCircular()
    {
        if (!isset($this->NoticeCircularID)) 
        {
            throw new ApplicationARException('', APP_AR_ERROR_DELETE_WITHOUT_ID);
        }
        
        $RSDeleteNoticeCircularApplicableFor = $this->DBObject->Prepare('DELETE FROM asa_notices_circulars_applicable_for WHERE noticeCircularID = :|1;');
		$RSDeleteNoticeCircularApplicableFor->Execute($this->NoticeCircularID);

        $RSDeleteNoticeCircular = $this->DBObject->Prepare('DELETE FROM asa_notices_circulars WHERE noticeCircularID = :|1 LIMIT 1;');
		$RSDeleteNoticeCircular->Execute($this->NoticeCircularID);
		
		return true;
    }

	private function GetNoticeCircularByID()
	{
		$RSNoticeCircular = $this->DBObject->Prepare('SELECT * FROM asa_notices_circulars WHERE noticeCircularID = :|1 LIMIT 1;');
		$RSNoticeCircular->Execute($this->NoticeCircularID);
		
		$NoticeCircularRow = $RSNoticeCircular->FetchRow();
		
		$this->SetAttributesFromDB($NoticeCircularRow);				
	}
	
	private function SetAttributesFromDB($NoticeCircularRow)
	{
		$this->NoticeCircularID = $NoticeCircularRow->noticeCircularID;
		$this->NoticeCircularDate = $NoticeCircularRow->noticeCircularDate;

		$this->NoticeCircularSubject = $NoticeCircularRow->noticeCircularSubject;
		$this->NoticeCircularDetails = $NoticeCircularRow->noticeCircularDetails;

		$this->IsActive = $NoticeCircularRow->isActive;
		$this->CreateUserID = $NoticeCircularRow->createUserID;
		$this->CreateDate = $NoticeCircularRow->createDate;
	}
}
?>
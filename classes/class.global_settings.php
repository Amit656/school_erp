<?php
require_once('class.db_connect.php');
error_reporting(E_ALL);

class GlobalSetting
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	
	private $SitePaging;

	private $FeeSubmissionLastDate;
	private $FeeSubmissionFrequency;
	private $FeeSubmissionType;
		
	// PUBLIC METHODS START HERE	//
	public function __construct()
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;
		
		$this->SitePaging = 0;	

		$this->FeeSubmissionLastDate = 0;	
		$this->FeeSubmissionFrequency = 0;	
		$this->FeeSubmissionType = '';	
		
		$this->GetGlobalSettings();
	}
	
	// GETTER AND SETTER FUNCTIONS START HERE	//

	public function GetSitePaging()
	{
		return $this->SitePaging;
	}
	public function SetSitePaging($SitePaging)
	{
		$this->SitePaging = $SitePaging;
	}
		
	public function GetFeeSubmissionLastDate()
	{
		return $this->FeeSubmissionLastDate;
	}
	public function SetFeeSubmissionLastDate($FeeSubmissionLastDate)
	{
		$this->FeeSubmissionLastDate = $FeeSubmissionLastDate;
	}	
	
	public function GetFeeSubmissionFrequency()
	{
		return $this->FeeSubmissionFrequency;
	}
	public function SetFeeSubmissionFrequency($FeeSubmissionFrequency)
	{
		$this->FeeSubmissionFrequency = $FeeSubmissionFrequency;
	}
	
	public function GetFeeSubmissionType()
	{
		return $this->FeeSubmissionType;
	}
	public function SetFeeSubmissionType($FeeSubmissionType)
	{
		$this->FeeSubmissionType = $FeeSubmissionType;
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
			$this->DBObject->RollBackTransaction();
			$this->LastErrorCode = APP_ERROR_UNDEFINED_ERROR;
			return false;
		}
	}
		
	// END OF PUBLIC METHODS	//
	
	// START OF STATIC METHODS	//
	
	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//
	private function SaveDetails()
	{
		
		$RSUpdateGlobalSetting = $this->DBObject->Prepare('UPDATE global_settings 
															SET sitePaging = :|1, 															
																feeSubmissionLastDate = :|2,
																feeSubmissionFrequency = :|3,
																feeSubmissionType = :|4
															LIMIT 1;');
															
		$RSUpdateGlobalSetting->Execute($this->SitePaging, $this->FeeSubmissionLastDate, $this->FeeSubmissionFrequency, $this->FeeSubmissionType);
		
		return true;
	}
	
	private function GetGlobalSettings()
	{
		$RSGlobalSetting = $this->DBObject->Prepare('SELECT * FROM global_settings LIMIT 1;');
		$RSGlobalSetting->Execute();
		
		$GlobalSettingRow = $RSGlobalSetting->FetchRow();
		
		$this->SetAttributesFromDB($GlobalSettingRow);
	}
	
	private function SetAttributesFromDB($GlobalSettingRow)
	{
		$this->SitePaging = $GlobalSettingRow->sitePaging;	

		$this->FeeSubmissionLastDate = $GlobalSettingRow->feeSubmissionLastDate;	
		$this->FeeSubmissionFrequency = $GlobalSettingRow->feeSubmissionFrequency;	
		$this->FeeSubmissionType = $GlobalSettingRow->feeSubmissionType;
	}	
}
?>
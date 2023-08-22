<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.db_connect.php');
error_reporting(E_ALL);

class AcademicYearMonth
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	
	private $AcademicYearMonthID;
	private $MonthName;
	private $MonthShortName;

	private $FeePriority;
	private $HRPriority;
	
	// PUBLIC METHODS START HERE	//
	public function __construct($AcademicYearMonthID = 0)
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;
		
		if($AcademicYearMonthID != 0)
		{
			$this->AcademicYearMonthID = $AcademicYearMonthID;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetAcademicYearMonthByID();
		}
		else
		{
			//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
			$this->AcademicYearMonthID = 0;
			$this->MonthName = '';
			$this->MonthShortName = '';
	
			$this->FeePriority = 0;
			$this->HRPriority = 0;
		}
	}
	
	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetAcademicYearMonthID()
	{
		return $this->AcademicYearMonthID;
	}
	
	public function GetMonthName()
	{
		return $this->MonthName;
	}
	public function SetMonthName($MonthName)
	{
		$this->MonthName = $MonthName;
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
	static function GetMonthsByFeePriority()
	{
		$AllMonths = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT * FROM asa_academic_year_months 
												ORDER BY feePriority;');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllMonths; 
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$AllMonths[$SearchRow->academicYearMonthID]['MonthName'] = $SearchRow->monthName;
				$AllMonths[$SearchRow->academicYearMonthID]['MonthShortName'] = $SearchRow->monthShortName;

				$AllMonths[$SearchRow->academicYearMonthID]['FeePriority'] = $SearchRow->feePriority;
				$AllMonths[$SearchRow->academicYearMonthID]['HRPriority'] = $SearchRow->hrPriority;
			}
			
			return $AllMonths;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at AcademicYearMonth::GetMonthsByFeePriority(). Stack Trace: ' . $e->getTraceAsString());
			return $AllMonths;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at AcademicYearMonth::GetMonthsByFeePriority(). Stack Trace: ' . $e->getTraceAsString());
			return $AllMonths;
		}
	}
	
	static function GetMonthIDByMonthName($MonthShortName)
	{
		$AcademicYearMonthID = 0;
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT academicYearMonthID FROM asa_academic_year_months 
												WHERE monthShortName = :|1;');
			$RSSearch->Execute($MonthShortName);
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AcademicYearMonthID; 
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$AcademicYearMonthID = $SearchRow->academicYearMonthID;
			}
			
			return $AcademicYearMonthID;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at AcademicYearMonth::GetMonthIDByMonthName(). Stack Trace: ' . $e->getTraceAsString());
			return $AcademicYearMonthID;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at AcademicYearMonth::GetMonthIDByMonthName(). Stack Trace: ' . $e->getTraceAsString());
			return $AcademicYearMonthID;
		}
	}
	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//
	/*private function SaveDetails()
	{
		if ($this->AcademicYearMonthID == 0)
		{
			$RSSave = $this->DBObject->Prepare('INSERT INTO asa_academic_year_months (userName)
														VALUES (:|1);');
		
			$RSSave->Execute($this->MonthName);
			
			$this->AcademicYearMonthID = $RSSave->LastID;
		}
		else
		{
			$RSUpdate = $this->DBObject->Prepare('UPDATE asa_academic_year_months
													SET	userName = :|1
													WHERE academicYearMonthID = :|2;');
													
			$RSUpdate->Execute($this->MonthName, $this->AcademicYearMonthID);
		}
		
		return true;
	}*/
	
	private function GetAcademicYearMonthByID()
	{
		$RSAcademicYearMonth = $this->DBObject->Prepare('SELECT * FROM asa_academic_year_months WHERE academicYearMonthID = :|1  LIMIT 1;');
		$RSAcademicYearMonth->Execute($this->AcademicYearMonthID);
		
		$AcademicYearMonthRow = $RSAcademicYearMonth->FetchRow();
		
		$this->SetAttributesFromDB($AcademicYearMonthRow);				
	}
	
	private function SetAttributesFromDB($AcademicYearMonthRow)
	{
		$this->AcademicYearMonthID = $AcademicYearMonthRow->academicYearMonthID;
		$this->MonthName = $AcademicYearMonthRow->monthName;
		$this->MonthShortName = $AcademicYearMonthRow->monthShortName;

		$this->FeePriority = $AcademicYearMonthRow->feePriority;
		$this->HRPriority = $AcademicYearMonthRow->hrPriority;
	}	
}
?>
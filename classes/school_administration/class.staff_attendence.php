<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.db_connect.php');
error_reporting(E_ALL);

class StaffAttendence
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	
	private $StaffAttendenceID;
	private $StaffCategory;
	private $AttendenceDate;

	private $CreateUserID;
	private $CreateDate;

	private $AttendenceStatusPresentStaffList = array();
	private $AttendenceStatusAbsentStaffList = array();
	
	// PUBLIC METHODS START HERE	//
	public function __construct($StaffAttendenceID = 0)
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;
		
		if ($StaffAttendenceID != 0)
		{
			$this->StaffAttendenceID = $StaffAttendenceID;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetStaffAttendenceByID();
		}
		else
		{
			//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
			$this->StaffAttendenceID = 0;
			$this->StaffCategory = '';
			$this->AttendenceDate = '0000-00-00';

			$this->CreateUserID = 0;
			$this->CreateDate = '0000-00-00 00:00:00';

			$this->AttendenceStatusPresentStaffList = array();
			$this->AttendenceStatusAbsentStaffList = array();
		}
	}
	
	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetStaffAttendenceID()
	{
		return $this->StaffAttendenceID;
	}
	
	public function GetStaffCategory()
	{
		return $this->StaffCategory;
	}
	public function SetStaffCategory($StaffCategory)
	{
		$this->StaffCategory = $StaffCategory;
	}
	
	public function GetAttendenceDate()
	{
		return $this->AttendenceDate;
	}
	public function SetAttendenceDate($AttendenceDate)
	{
		$this->AttendenceDate = $AttendenceDate;
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
	
	public function GetAttendenceStatusPresentStaffList()
	{
		return $this->AttendenceStatusPresentStaffList;
	}
	public function SetAttendenceStatusPresentStaffList($AttendenceStatusPresentStaffList)
	{
		$this->AttendenceStatusPresentStaffList = $AttendenceStatusPresentStaffList;
	}

	public function GetAttendenceStatusAbsentStaffList()
	{
		return $this->AttendenceStatusAbsentStaffList;
	}
	public function SetAttendenceStatusAbsentStaffList($AttendenceStatusAbsentStaffList)
	{
		$this->AttendenceStatusAbsentStaffList = $AttendenceStatusAbsentStaffList;
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

	public function FillAttendenceStatus()
	{
		try
        {
			$RSPresentBranchStaffSearch = $this->DBObject->Prepare('SELECT asad.branchStaffID FROM asa_staff_attendence asa
													INNER JOIN asa_staff_attendence_details asad ON asa.staffAttendenceID = asad.staffAttendenceID
													WHERE staffCategory = :|1 AND attendenceDate = :|2 AND asad.attendenceStatus = \'Present\';');
            $RSPresentBranchStaffSearch->Execute($this->StaffCategory, $this->AttendenceDate);

            if ($RSPresentBranchStaffSearch->Result->num_rows > 0)
            {
        		while($SearchRow = $RSPresentBranchStaffSearch->FetchRow())
	            {
	            	$this->AttendenceStatusPresentStaffList[$SearchRow->branchStaffID] = 1;
	            }
            }

            $RSAbsentBranchStaffSearch = $this->DBObject->Prepare('SELECT asad.branchStaffID FROM asa_staff_attendence asa
													INNER JOIN asa_staff_attendence_details asad ON asa.staffAttendenceID = asad.staffAttendenceID
													WHERE staffCategory = :|1 AND attendenceDate = :|2 AND asad.attendenceStatus = \'Present\';');
            $RSAbsentBranchStaffSearch->Execute($this->StaffCategory, $this->AttendenceDate);

            if ($RSAbsentBranchStaffSearch->Result->num_rows > 0)
            {
        		while($SearchRow = $RSAbsentBranchStaffSearch->FetchRow())
	            {
	            	$this->AttendenceStatusAbsentStaffList[$SearchRow->branchStaffID] = 1;
	            }
            }

	        return true;	
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at StaffAttendence::FillAttendenceStatus(). Stack Trace: ' . $e->getTraceAsString());
            return false;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception at StaffAttendence::FillAttendenceStatus(). Stack Trace: ' . $e->getTraceAsString());
            return false;
        }
	}
	
	public function ViewStaffAttendenceStatus()
	{
		try
        {
			$RSPresentBranchStaffSearch = $this->DBObject->Prepare('SELECT asad.branchStaffID FROM asa_staff_attendence asa
																	INNER JOIN asa_staff_attendence_details asad ON asa.staffAttendenceID = asad.staffAttendenceID
																	WHERE staffCategory = :|1 AND attendenceDate = :|2 AND asad.attendenceStatus = \'Present\';');
            $RSPresentBranchStaffSearch->Execute($this->StaffCategory, $this->AttendenceDate);

            if ($RSPresentBranchStaffSearch->Result->num_rows > 0)
            {
        		while($SearchRow = $RSPresentBranchStaffSearch->FetchRow())
	            {
	            	$this->AttendenceStatusPresentStaffList[$SearchRow->branchStaffID] = 1;
	            }
            }

            $RSAbsentBranchStaffSearch = $this->DBObject->Prepare('SELECT asad.branchStaffID FROM asa_staff_attendence asa
																	INNER JOIN asa_staff_attendence_details asad ON asa.staffAttendenceID = asad.staffAttendenceID
																	WHERE staffCategory = :|1 AND attendenceDate = :|2 AND asad.attendenceStatus = \'Present\';');
            $RSAbsentBranchStaffSearch->Execute($this->StaffCategory, $this->AttendenceDate);

            if ($RSAbsentBranchStaffSearch->Result->num_rows > 0)
            {
        		while($SearchRow = $RSAbsentBranchStaffSearch->FetchRow())
	            {
	            	$this->AttendenceStatusAbsentStaffList[$SearchRow->branchStaffID] = 1;
	            }
            }

	        return true;	
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at StaffAttendence::ViewStaffAttendenceStatus(). Stack Trace: ' . $e->getTraceAsString());
            return false;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception at StaffAttendence::ViewStaffAttendenceStatus(). Stack Trace: ' . $e->getTraceAsString());
            return false;
        }
	}

	// END OF PUBLIC METHODS	//
	
	// START OF STATIC METHODS	//
	
	static function GetStaffAttendance($BranchStaffID)
	{
		$StaffAttendanceList = array();
		
		try
        {
            $DBConnObject = new DBConnect();

            $RSStaffAttendence = $DBConnObject->Prepare('SELECT attendenceDate FROM asa_staff_attendence asa 
														 INNER JOIN asa_staff_attendence_details asad ON asad.staffAttendenceID = asa.staffAttendenceID 
														 WHERE asad.branchStaffID = :|1 AND asad.attendenceStatus = \'Absent\';');
			
            $RSStaffAttendence->Execute($BranchStaffID);
         
            if ($RSStaffAttendence->Result->num_rows > 0) 
            {
				while ($SearchRow = $RSStaffAttendence->FetchRow())
				{
					$StaffAttendanceList[] = $SearchRow->attendenceDate;
				}
            }

            return $StaffAttendanceList;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at StaffAttendence::GetStaffAttendance(). Stack Trace: ' . $e->getTraceAsString());
            return $StaffAttendanceList;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception at StaffAttendence::GetStaffAttendance(). Stack Trace: ' . $e->getTraceAsString());
            return $StaffAttendanceList;
        }
	}
	
	static function GetOverAllStaffAttendance($StaffCategory)
	{
		$StaffAttendanceList = array();

		try
        {
            $DBConnObject = new DBConnect();

            $RSSearch = $DBConnObject->Prepare('SELECT abs.branchStaffID, abs.firstName, abs.lastName, 
												(
													SELECT COUNT(*) FROM asa_staff_attendence_details astd 
												    WHERE astd.branchStaffID = abs.branchStaffID AND astd.attendenceStatus = \'Present\'
												)AS totalPresents,
												(
													SELECT COUNT(*) FROM asa_staff_attendence_details astd 
												    WHERE astd.branchStaffID = abs.branchStaffID AND astd.attendenceStatus = \'Absent\'
												)AS totalAbsents
												FROM asa_branch_staff abs 
												WHERE abs.staffCategory = :|1;');
            $RSSearch->Execute($StaffCategory);
         
            if ($RSSearch->Result->num_rows <= 0) 
            {
            	return $StaffAttendanceList;
            }

            while ($SearchRow = $RSSearch->FetchRow()) 
            {
            	$StaffAttendanceList[$SearchRow->branchStaffID]['FirstName'] = $SearchRow->firstName;
            	$StaffAttendanceList[$SearchRow->branchStaffID]['LastName'] = $SearchRow->lastName;
            	$StaffAttendanceList[$SearchRow->branchStaffID]['TotalPresents'] = $SearchRow->totalPresents;
            	$StaffAttendanceList[$SearchRow->branchStaffID]['TotalAbsents'] = $SearchRow->totalAbsents;
            }

            return $StaffAttendanceList;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at StaffAttendence::GetOverAllStaffAttendance(). Stack Trace: ' . $e->getTraceAsString());
            return $StaffAttendanceList;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception at StaffAttendence::GetOverAllStaffAttendance(). Stack Trace: ' . $e->getTraceAsString());
            return $StaffAttendanceList;
        }
	}

	static function SearchStaffAttendence(&$TotalRecords = 0, $GetTotalsOnly = false, $Filters = array(), $Start = 0, $Limit = 100, $TotalWorkingDays = 0)
    {
    	$StaffAttendanceList = array();

    	try
		{
			$DBConnObject = new DBConnect();
			
			$Conditions = array();
			
			if (count($Filters) > 0)
			{
				if (!empty($Filters['StaffCategory']))
				{
					$Conditions[] = 'abs.staffCategory = ' . $DBConnObject->RealEscapeVariable($Filters['StaffCategory']);
				}

				if (!empty($Filters['BranchStaffID']))
				{
					$Conditions[] = 'abs.branchStaffID = ' . $DBConnObject->RealEscapeVariable($Filters['BranchStaffID']);
				}
			}

			$QueryString = '';

			if (count($Conditions) > 0)
			{
				$QueryString = implode(' AND ',$Conditions);
				
				$QueryString = ' WHERE ' . $QueryString;
			}
			
			if ($GetTotalsOnly)
			{
			 
				$RSTotal = $DBConnObject->Prepare('SELECT COUNT(*) AS totalRecords 
													FROM asa_branch_staff abs 
													INNER JOIN users u ON abs.createUserID = u.userID' . $QueryString . ';');
				$RSTotal->Execute();
				
				$TotalRecords = $RSTotal->FetchRow()->totalRecords;
				return;
			}
			
			$RSSearch = $DBConnObject->Prepare('SELECT abs.branchStaffID, abs.firstName, abs.lastName, 
												(
													SELECT COUNT(*) FROM asa_staff_attendence_details astd 
												    WHERE astd.branchStaffID = abs.branchStaffID AND astd.attendenceStatus = \'Present\'
												)AS totalPresents
												FROM asa_branch_staff abs 
												'. $QueryString . ' 
												ORDER BY abs.branchStaffID 
												LIMIT ' . (int) $Start . ', ' . (int) $Limit . ';');
			$RSSearch->Execute();

			while($RSSearchRow = $RSSearch->FetchRow())
			{
				$IsPercentInRange = 0;
				$PresentPercentage = ($RSSearchRow->totalPresents / $TotalWorkingDays) * 100;

				if (!empty($Filters['AttendanePercentRangeFrom']) || !empty($Filters['AttendanePercentRangeTo'])) 
				{
					if (!empty($Filters['AttendanePercentRangeFrom']) && !($PresentPercentage >= $Filters['AttendanePercentRangeFrom'])) 
					{
						$IsPercentInRange = 1;
					}

					if (!empty($Filters['AttendanePercentRangeTo']) && !($PresentPercentage <= $Filters['AttendanePercentRangeTo'])) 
					{
						$IsPercentInRange = 1;
					}
				}

				if ($IsPercentInRange) 
				{
					continue;
				}

				$StaffAttendanceList[$RSSearchRow->branchStaffID]['FirstName'] = $RSSearchRow->firstName;
            	$StaffAttendanceList[$RSSearchRow->branchStaffID]['LastName'] = $RSSearchRow->lastName;
            	$StaffAttendanceList[$RSSearchRow->branchStaffID]['TotalPresentDays'] = $RSSearchRow->totalPresents;
			}
			
			return $StaffAttendanceList;	
				
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at StaffAttendence::SearchStaffAttendence(). Stack Trace: ' . $e->getTraceAsString());
			return $StaffAttendanceList;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at StaffAttendence::SearchStaffAttendence(). Stack Trace: ' . $e->getTraceAsString());
			return $StaffAttendanceList;
		}	
    }

    static function GetMonthlyAttendance($BranchStaffID, $AttendanceStartDate, $AttendanceEndDate)
	{
		$MonthlyAttendanceList = array();

		try
        {
            $DBConnObject = new DBConnect();

            $RSSearch = $DBConnObject->Prepare('SELECT asa.attendenceDate, asad.staffAttendenceDetailID, asad.attendenceStatus FROM asa_staff_attendence asa 
            									INNER JOIN asa_staff_attendence_details asad ON asa.staffAttendenceID = asad.staffAttendenceID 
            									WHERE asad.branchStaffID = :|1 AND asa.attendenceDate BETWEEN :|2 AND :|3;');
            $RSSearch->Execute($BranchStaffID, $AttendanceStartDate, $AttendanceEndDate);
         
            if ($RSSearch->Result->num_rows <= 0) 
            {
            	return $MonthlyAttendanceList;
            }

            while ($SearchRow = $RSSearch->FetchRow()) 
            {
            	$MonthlyAttendanceList[$SearchRow->staffAttendenceDetailID]['AttendenceDate'] = $SearchRow->attendenceDate;
            	$MonthlyAttendanceList[$SearchRow->staffAttendenceDetailID]['Status'] = $SearchRow->attendenceStatus;
            }

            return $MonthlyAttendanceList;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at StaffAttendence::GetMonthlyAttendance(). Stack Trace: ' . $e->getTraceAsString());
            return $MonthlyAttendanceList;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception at StaffAttendence::GetMonthlyAttendance(). Stack Trace: ' . $e->getTraceAsString());
            return $MonthlyAttendanceList;
        }
	}

	static function IsAttendenceTaken($StaffCategory, $AttendenceDate, &$StaffAttendenceID = 0)
	{
		$StaffAttendenceID = 0;

		try
        {
            $DBConnObject = new DBConnect();

            $RSStaffAttendenceID = $DBConnObject->Prepare('SELECT staffAttendenceID FROM asa_staff_attendence WHERE staffCategory = :|1 AND attendenceDate = :|2;');
            $RSStaffAttendenceID->Execute($StaffCategory, $AttendenceDate);

            if ($RSStaffAttendenceID->Result->num_rows > 0) 
            {
            	$StaffAttendenceID = $RSStaffAttendenceID->FetchRow()->staffAttendenceID;
                return true;
            }

            return false;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at StaffAttendence::IsAttendenceTaken(). Stack Trace: ' . $e->getTraceAsString());
            return false;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception at StaffAttendence::IsAttendenceTaken(). Stack Trace: ' . $e->getTraceAsString());
            return false;
        }

        return true;
	}
    
    static function GetClockInOutDetails($BranchStaffID, $AttendanceDate)
	{
		$ClockInOutDetails = array();

		try
        {
            $DBConnObject = new DBConnect();

            $RSClockInOutDetails = $DBConnObject->Prepare('SELECT * FROM teacher_clock_in_out_log WHERE branchStaffID = :|1 AND clockInOutDate = :|2;');
            $RSClockInOutDetails->Execute($BranchStaffID, $AttendanceDate);

            if ($RSClockInOutDetails->Result->num_rows <= 0) 
            {
                return $ClockInOutDetails;
            }

            while ($SearchRow = $RSClockInOutDetails->FetchRow()) 
            {
            	$ClockInOutDetails[$SearchRow->teacherClockInOutLog]['ClockInOutType'] = $SearchRow->clockInOutType;
            	$ClockInOutDetails[$SearchRow->teacherClockInOutLog]['ClockInOutTime'] = $SearchRow->clockInOutTime;
            }

            return $ClockInOutDetails;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at StaffAttendence::GetClockInOutDetails(). Stack Trace: ' . $e->getTraceAsString());
            return $ClockInOutDetails;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception at StaffAttendence::GetClockInOutDetails(). Stack Trace: ' . $e->getTraceAsString());
            return $ClockInOutDetails;
        }
	}
	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//
	private function SaveDetails()
	{
		$AbsentBranchStaffIDs = '';

		if (count($this->AttendenceStatusAbsentStaffList) > 0)
		{
			$AbsentBranchStaffIDs = implode(',', array_keys($this->AttendenceStatusAbsentStaffList));
		}

		if ($this->StaffAttendenceID == 0)
		{
			$RSSave = $this->DBObject->Prepare('INSERT INTO asa_staff_attendence (staffCategory, attendenceDate, createUserID, createDate)
														VALUES (:|1, :|2, :|3, NOW());');
			$RSSave->Execute($this->StaffCategory, $this->AttendenceDate, $this->CreateUserID);

        	$this->StaffAttendenceID = $RSSave->LastID;
		}
		else
		{
			$RSDeleteRecords = $this->DBObject->Prepare('DELETE FROM asa_staff_attendence_details WHERE staffAttendenceID = :|1;');
			$RSDeleteRecords->Execute($this->StaffAttendenceID);
		}

		if ($AbsentBranchStaffIDs != '')
    	{
    		$RSSavePresentStaff = $this->DBObject->Prepare('INSERT INTO asa_staff_attendence_details (staffAttendenceID, branchStaffID, attendenceStatus)	
																SELECT :|1, branchStaffID, "Present" FROM asa_branch_staff WHERE asa_branch_staff.staffCategory = :|2 AND branchStaffID NOT IN (' . $AbsentBranchStaffIDs . ');');
			$RSSavePresentStaff->Execute($this->StaffAttendenceID, $this->StaffCategory);

			$RSSaveAbsentStaff = $this->DBObject->Prepare('INSERT INTO asa_staff_attendence_details (staffAttendenceID, branchStaffID, attendenceStatus) 
																SELECT :|1, branchStaffID, "Absent" FROM asa_branch_staff WHERE asa_branch_staff.staffCategory = :|2 AND branchStaffID IN (' . $AbsentBranchStaffIDs . ');');
			$RSSaveAbsentStaff->Execute($this->StaffAttendenceID, $this->StaffCategory);
    	}
		else
		{
			$RSSavePresentStaff = $this->DBObject->Prepare('INSERT INTO asa_staff_attendence_details (staffAttendenceID, branchStaffID, attendenceStatus)	
																SELECT :|1, branchStaffID, "Present" FROM asa_branch_staff WHERE staffCategory = :|2;');
			$RSSavePresentStaff->Execute($this->StaffAttendenceID, $this->StaffCategory);
		}

		return true;
	}
	
	private function GetStaffAttendenceByID()
	{
		$RSStaffAttendence = $this->DBObject->Prepare('SELECT * FROM asa_staff_attendence WHERE staffAttendenceID = :|1 LIMIT 1;');
		$RSStaffAttendence->Execute($this->StaffAttendenceID);
		
		$StaffAttendenceRow = $RSStaffAttendence->FetchRow();
		
		$this->SetAttributesFromDB($StaffAttendenceRow);				
	}
	
	private function SetAttributesFromDB($StaffAttendenceRow)
	{
		$this->StaffAttendenceID = $StaffAttendenceRow->staffAttendenceID;
		$this->StaffCategory = $StaffAttendenceRow->staffCategory;
		$this->AttendenceDate = $StaffAttendenceRow->attendenceDate;
		
		$this->CreateUserID = $StaffAttendenceRow->createUserID;
		$this->CreateDate = $StaffAttendenceRow->createDate;
	}	
}
?>
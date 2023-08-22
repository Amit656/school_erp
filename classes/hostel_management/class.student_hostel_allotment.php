<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.db_connect.php');
error_reporting(E_ALL);

class StudentHostelAllotment
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	
	private $StudentHostelAllotmentID;
	private $StudentID;
	private $RoomID;
	private $MessID;
	
	private $IsActive;
	private $CreateUserID;
	private $CreateDate;
	// PUBLIC METHODS START HERE	//
	public function __construct($StudentHostelAllotmentID = 0)
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;
		
		if($StudentHostelAllotmentID != 0)
		{
			$this->StudentHostelAllotmentID = $StudentHostelAllotmentID;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetStudentHostelAllotmentByID();
		}
		else
		{
			//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
			$this->StudentHostelAllotmentID = 0;
			$this->StudentID = 0;
			$this->RoomID = 0;
			$this->MessID = 0;

			$this->IsActive = 0;
			$this->CreateUserID = 0;
			$this->CreateDate = '0000-00-00 00:00:00';
		}
	}
	
	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetStudentHostelAllotmentID()
	{
		return $this->StudentHostelAllotmentID;
	}
	
	public function GetStudentID()
	{
		return $this->StudentID;
	}
	public function SetStudentID($StudentID)
	{
		$this->StudentID = $StudentID;
	}

	public function GetRoomID()
	{
		return $this->RoomID;
	}
	public function SetRoomID($RoomID)
	{
		$this->RoomID = $RoomID;
	}

	public function GetMessID()
	{
		return $this->MessID;
	}
	public function SetMessID($MessID)
	{
		$this->MessID = $MessID;
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
	
	static function SearchHostellerStudents(&$TotalRecords = 0, $GetTotalsOnly = false, $Filters = array(), $Start = 0, $Limit = 100)
	{
		$HostellerStudentsList = array();
		
		try
		{
			$DBConnObject = new DBConnect();

			$Conditions = array();
			
			if (count($Filters) > 0)
			{
				if (!empty($Filters['WingID']))
				{
					$Conditions[] = 'ar.wingID = '.$DBConnObject->RealEscapeVariable($Filters['WingID']);
				}
				
				if (!empty($Filters['RoomTypeID']))
				{
					$Conditions[] = 'ar.roomTypeID = '.$DBConnObject->RealEscapeVariable($Filters['RoomTypeID']);
				}

				if (!empty($Filters['RoomID']))
				{
					$Conditions[] = 'ar.roomID = '.$DBConnObject->RealEscapeVariable($Filters['RoomID']);
				}

				if (!empty($Filters['StudentName']))
				{
					$Conditions[] = 'CONCAT(asd.firstName, " ", asd.lastName) LIKE ' . $DBConnObject->RealEscapeVariable('%' . $Filters['StudentName'] . '%');
				}
				
				if (!empty($Filters['ActiveStatus']))
				{
					if ($Filters['ActiveStatus'] == 1) //active records
					{
						$Conditions[] = 'ar.isActive = 1';
					}
					else if ($Filters['ActiveStatus'] == 2) //non active records
					{
						$Conditions[] = 'ar.isActive = 0';
					}
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
				$RSTotal = $DBConnObject->Prepare('SELECT COUNT(*) AS totalRecords 
													FROM ahm_student_hostel_allotment asha
													INNER JOIN ahm_rooms ar ON asha.roomID = ar.roomID 
													INNER JOIN ahm_wings aw ON aw.wingID = ar.wingID 
													INNER JOIN ahm_room_types art ON art.roomTypeID = ar.roomTypeID 
													INNER JOIN asa_student_details asd ON asha.studentID = asd.studentID 
													INNER JOIN users u ON asha.createUserID = u.userID
													'. $QueryString .';');
				$RSTotal->Execute();
				
				$TotalRecords = $RSTotal->FetchRow()->totalRecords;
				return;
			}
			
			$RSSearch = $DBConnObject->Prepare('SELECT asha.*, asd.firstName, asd.lastName, aw.wingName, art.roomType, ar.roomName, am.messName, am.messType, u.userName AS createUserName 
												FROM ahm_student_hostel_allotment asha
												INNER JOIN ahm_rooms ar ON asha.roomID = ar.roomID 
												INNER JOIN ahm_wings aw ON aw.wingID = ar.wingID 
												INNER JOIN ahm_room_types art ON art.roomTypeID = ar.roomTypeID 
												LEFT JOIN ahm_mess am ON am.messID = asha.messID 
												INNER JOIN asa_student_details asd ON asha.studentID = asd.studentID 
												INNER JOIN users u ON asha.createUserID = u.userID
												'. $QueryString .' 
												ORDER BY asd.firstName LIMIT ' . (int) $Start . ', ' . (int) $Limit . ';');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $HostellerStudentsList; 
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$HostellerStudentsList[$SearchRow->studentHostelAllotmentID]['StudentName'] = $SearchRow->firstName .' '. $SearchRow->lastName;
				$HostellerStudentsList[$SearchRow->studentHostelAllotmentID]['WingName'] = $SearchRow->wingName;

				$HostellerStudentsList[$SearchRow->studentHostelAllotmentID]['RoomType'] = $SearchRow->roomType;
				$HostellerStudentsList[$SearchRow->studentHostelAllotmentID]['RoomName'] = $SearchRow->roomName;

				$HostellerStudentsList[$SearchRow->studentHostelAllotmentID]['MessName'] = $SearchRow->messName;
				$HostellerStudentsList[$SearchRow->studentHostelAllotmentID]['Mess'] = $SearchRow->messName .' ( '. $SearchRow->messType .' )';
				
				$HostellerStudentsList[$SearchRow->studentHostelAllotmentID]['IsActive'] = $SearchRow->isActive;				
				$HostellerStudentsList[$SearchRow->studentHostelAllotmentID]['CreateUserName'] = $SearchRow->createUserName;
				$HostellerStudentsList[$SearchRow->studentHostelAllotmentID]['CreateDate'] = $SearchRow->createDate;
			}
			
			return $HostellerStudentsList;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at StudentHostelAllotment::SearchHostellerStudents(). Stack Trace: ' . $e->getTraceAsString());
			return $HostellerStudentsList;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at StudentHostelAllotment::SearchHostellerStudents(). Stack Trace: ' . $e->getTraceAsString());
			return $HostellerStudentsList;
		}
	}

	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//
	private function SaveDetails()
	{
		if ($this->StudentHostelAllotmentID == 0)
		{
			$RSSave = $this->DBObject->Prepare('INSERT INTO ahm_student_hostel_allotment (studentID, roomID, messID, isActive, createUserID, createDate)
														VALUES (:|1, :|2, :|3, :|4, :|5, NOW());');
		
			$RSSave->Execute($this->StudentID, $this->RoomID, $this->MessID, $this->IsActive, $this->CreateUserID);
			
			$this->StudentHostelAllotmentID = $RSSave->LastID;
		}
		else
		{
			$RSUpdate = $this->DBObject->Prepare('UPDATE ahm_student_hostel_allotment
													SET	studentID = :|1,
														roomID = :|2,
														messID = :|3,
														isActive = :|4
													WHERE studentHostelAllotmentID = :|5;');
													
			$RSUpdate->Execute($this->StudentID, $this->RoomID, $this->MessID, $this->IsActive, $this->StudentHostelAllotmentID);
		}
		
		return true;
	}
	
	private function GetStudentHostelAllotmentByID()
	{
		$RSStudentHostelAllotment = $this->DBObject->Prepare('SELECT * FROM ahm_student_hostel_allotment WHERE studentHostelAllotmentID = :|1 LIMIT 1;');
		$RSStudentHostelAllotment->Execute($this->StudentHostelAllotmentID);
		
		$StudentHostelAllotmentRow = $RSStudentHostelAllotment->FetchRow();
		
		$this->SetAttributesFromDB($StudentHostelAllotmentRow);				
	}
	
	private function SetAttributesFromDB($StudentHostelAllotmentRow)
	{
		$this->StudentHostelAllotmentID = $StudentHostelAllotmentRow->studentHostelAllotmentID;
		$this->StudentID = $StudentHostelAllotmentRow->studentID;
		$this->RoomID = $StudentHostelAllotmentRow->roomID;
		$this->MessID = $StudentHostelAllotmentRow->messID;

		$this->IsActive = $StudentHostelAllotmentRow->isActive;
		$this->CreateUserID = $StudentHostelAllotmentRow->createUserID;
		$this->CreateDate = $StudentHostelAllotmentRow->createDate;
	}	
}
?>
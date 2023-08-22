<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.db_connect.php');
error_reporting(E_ALL);

class Room
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	
	private $RoomID;
	private $WingID;
	private $RoomTypeID;

	private $RoomName;
	private $BedCount;

	private $MonthlyFee;
	private $QuarterlyFee;
	private $SemiAnnualFee;
	private $AnnualFee;

	private $IsActive;
	private $CreateUserID;
	private $CreateDate;

	private $NoOfRoom;
	
	// PUBLIC METHODS START HERE	//
	public function __construct($RoomID = 0)
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;
		
		if($RoomID != 0)
		{
			$this->RoomID = $RoomID;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetRoomByID();
		}
		else
		{
			//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
			$this->RoomID = 0;
			$this->WingID = 0;
			$this->RoomTypeID = 0;

			$this->RoomName = '';
			$this->BedCount = 0;

			$this->MonthlyFee = 0;
			$this->QuarterlyFee = 0;
			$this->SemiAnnualFee = 0;
			$this->AnnualFee = 0;

			$this->IsActive = 0;
			$this->CreateUserID = 0;
			$this->CreateDate = '0000-00-00 00:00:00';

			$this->NoOfRoom = 0;
		}
	}
	
	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetRoomID()
	{
		return $this->RoomID;
	}
	
	public function GetWingID()
	{
		return $this->WingID;
	}
	public function SetWingID($WingID)
	{
		$this->WingID = $WingID;
	}
	
	public function GetRoomTypeID()
	{
		return $this->RoomTypeID;
	}
	public function SetRoomTypeID($RoomTypeID)
	{
		$this->RoomTypeID = $RoomTypeID;
	}

	public function GetRoomName()
	{
		return $this->RoomName;
	}
	public function SetRoomName($RoomName)
	{
		$this->RoomName = $RoomName;
	}
	
	public function GetBedCount()
	{
		return $this->BedCount;
	}
	public function SetBedCount($BedCount)
	{
		$this->BedCount = $BedCount;
	}

	public function GetMonthlyFee()
	{
		return $this->MonthlyFee;
	}
	public function SetMonthlyFee($MonthlyFee)
	{
		$this->MonthlyFee = $MonthlyFee;
	}
	
	public function GetQuarterlyFee()
	{
		return $this->QuarterlyFee;
	}
	public function SetQuarterlyFee($QuarterlyFee)
	{
		$this->QuarterlyFee = $QuarterlyFee;
	}
	
	public function GetSemiAnnualFee()
	{
		return $this->SemiAnnualFee;
	}
	public function SetSemiAnnualFee($SemiAnnualFee)
	{
		$this->SemiAnnualFee = $SemiAnnualFee;
	}
	
	public function GetAnnualFee()
	{
		return $this->AnnualFee;
	}
	public function SetAnnualFee($AnnualFee)
	{
		$this->AnnualFee = $AnnualFee;
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

	public function SetNoOfRoom($NoOfRoom)
	{
		$this->NoOfRoom = $NoOfRoom;
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

	public function Remove()
    {
        try
        {
            $this->RemoveRoom();
            return true;
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

    public function CheckDependencies()
    {
        try
        {
            /*$RSRoomCount = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM ahm_rooms WHERE roomID = :|1;');
            $RSRoomCount->Execute($this->RoomID);

            if ($RSRoomCount->FetchRow()->totalRecords > 0) 
            {
                return true;
            }*/

            return false;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at Room::CheckDependencies(). Stack Trace: ' . $e->getTraceAsString());
            return true;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: ApplicationDBException at Room::CheckDependencies(). Stack Trace: ' . $e->getTraceAsString());
            return true;
        }       
    }
		
	// END OF PUBLIC METHODS	//
		
	// START OF STATIC METHODS	//
	static function SearchRooms(&$TotalRecords = 0, $GetTotalsOnly = false, $Filters = array(), $Start = 0, $Limit = 100)
	{
		$AllRooms = array();
		
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
													FROM ahm_rooms ar
													INNER JOIN ahm_wings aw ON aw.wingID = ar.wingID 
													INNER JOIN ahm_room_types art ON art.roomTypeID = ar.roomTypeID 
													INNER JOIN users u ON ar.createUserID = u.userID
													'. $QueryString .';');
				$RSTotal->Execute();
				
				$TotalRecords = $RSTotal->FetchRow()->totalRecords;
				return;
			}
			
			$RSSearch = $DBConnObject->Prepare('SELECT ar.*, aw.wingName, art.roomType, u.userName AS createUserName, 
												(SELECT COUNT(*) FROM ahm_student_hostel_allotment WHERE roomID = ar.roomID AND isActive = 1) AS totalRecords 
												FROM ahm_rooms ar
												INNER JOIN ahm_wings aw ON aw.wingID = ar.wingID 
												INNER JOIN ahm_room_types art ON art.roomTypeID = ar.roomTypeID 
												INNER JOIN users u ON ar.createUserID = u.userID
												'. $QueryString .' 
												ORDER BY ar.roomName LIMIT ' . (int) $Start . ', ' . (int) $Limit . ';');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllRooms; 
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				/*$RSCount = $DBConnObject->Prepare('SELECT COUNT(*) AS totalRecords FROM ahm_student_hostel_allotment 
													WHERE roomID = :|1 AND isActive = 1;');
				$RSCount->Execute($SearchRow->roomID);
*/
				$AllRooms[$SearchRow->roomID]['WingName'] = $SearchRow->wingName;
				$AllRooms[$SearchRow->roomID]['RoomType'] = $SearchRow->roomType;

				$AllRooms[$SearchRow->roomID]['RoomName'] = $SearchRow->roomName;
				$AllRooms[$SearchRow->roomID]['BedCount'] = $SearchRow->bedCount;
				$AllRooms[$SearchRow->roomID]['FreeBed'] = $SearchRow->bedCount - $SearchRow->totalRecords;

				$AllRooms[$SearchRow->roomID]['MonthlyFee'] = $SearchRow->monthlyFee;
				$AllRooms[$SearchRow->roomID]['QuarterlyFee'] = $SearchRow->quarterlyFee;
				$AllRooms[$SearchRow->roomID]['SemiAnnualFee'] = $SearchRow->semiAnnualFee;
				$AllRooms[$SearchRow->roomID]['AnnualFee'] = $SearchRow->annualFee;

				$AllRooms[$SearchRow->roomID]['IsActive'] = $SearchRow->isActive;
				$AllRooms[$SearchRow->roomID]['CreateUserID'] = $SearchRow->createUserID;
				$AllRooms[$SearchRow->roomID]['CreateUserName'] = $SearchRow->createUserName;
				$AllRooms[$SearchRow->roomID]['CreateDate'] = $SearchRow->createDate;
			}
			
			return $AllRooms;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at Room::SearchRooms(). Stack Trace: ' . $e->getTraceAsString());
			return $AllRooms;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at Room::SearchRooms(). Stack Trace: ' . $e->getTraceAsString());
			return $AllRooms;
		}
	}

	static function GetActiveRooms()
	{
		$ActiveRooms = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT * FROM ahm_rooms WHERE isActive = 1;');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $ActiveRooms;
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$ActiveRooms[$SearchRow->roomID] = $SearchRow->roomName;
			}
			
			return $ActiveRooms;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at Room::GetActiveRooms(). Stack Trace: ' . $e->getTraceAsString());
			return $ActiveRooms;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at Room::GetActiveRooms(). Stack Trace: ' . $e->getTraceAsString());
			return $ActiveRooms;
		}		
	}

	static function GetAvailableRooms($WingID, $RoomTypeID)
	{
		$AvailableRooms = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT *, (SELECT COUNT(*) FROM ahm_student_hostel_allotment WHERE roomID = ahm_rooms.roomID AND isActive = 1) AS totalRecords  FROM ahm_rooms 
												WHERE wingID = :|1 AND roomTypeID = :|2 AND isActive = 1
												ORDER BY roomName;');
			$RSSearch->Execute($WingID, $RoomTypeID);
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AvailableRooms;
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$AvailableRooms[$SearchRow->roomID]['RoomName'] = $SearchRow->roomName;
				$AvailableRooms[$SearchRow->roomID]['BedCount'] = $SearchRow->bedCount;
				$AvailableRooms[$SearchRow->roomID]['FreeSpace'] = ($SearchRow->bedCount - $SearchRow->totalRecords);
			}
			
			return $AvailableRooms;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at Room::GetAvailableRooms(). Stack Trace: ' . $e->getTraceAsString());
			return $AvailableRooms;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at Room::GetAvailableRooms(). Stack Trace: ' . $e->getTraceAsString());
			return $AvailableRooms;
		}		
	}
		
	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//
	private function SaveDetails()
	{
		if ($this->RoomID == 0)
		{
			$Count = $this->RoomName + $this->NoOfRoom;

			for ($this->RoomName = $this->RoomName; $this->RoomName < $Count; $this->RoomName++) 
			{ 
				$RSSave = $this->DBObject->Prepare('INSERT INTO ahm_rooms (wingID, roomTypeID, roomName, bedCount, monthlyFee, quarterlyFee, 
																			semiAnnualFee, annualFee, isActive, createUserID, createDate)
															VALUES (:|1, :|2, :|3, :|4, :|5, :|6, :|7, :|8, :|9, :|10, NOW());');
			
				$RSSave->Execute($this->WingID, $this->RoomTypeID, $this->RoomName, $this->BedCount, $this->MonthlyFee, $this->QuarterlyFee, 
								$this->SemiAnnualFee, $this->AnnualFee, $this->IsActive, $this->CreateUserID);
			}
		}
		else
		{
			$RSUpdate = $this->DBObject->Prepare('UPDATE ahm_rooms
													SET	wingID = :|1,
														roomTypeID = :|2,
														roomName = :|3,
														bedCount = :|4,
														monthlyFee = :|5,
														quarterlyFee = :|6,
														semiAnnualFee = :|7,
														annualFee = :|8,
														isActive = :|9
													WHERE roomID = :|10;');
													
			$RSUpdate->Execute($this->WingID, $this->RoomTypeID, $this->RoomName, $this->BedCount, $this->MonthlyFee, $this->QuarterlyFee, 
							$this->SemiAnnualFee, $this->AnnualFee, $this->IsActive, $this->RoomID);
		}
		
		return true;
	}

	private function RemoveRoom()
    {
        if(!isset($this->RoomID)) 
        {
            throw new ApplicationARException('', APP_AR_ERROR_DELETE_WITHOUT_ID);
        }
        
        $RSDeleteRoom = $this->DBObject->Prepare('DELETE FROM ahm_rooms WHERE roomID = :|1 LIMIT 1;');
        $RSDeleteRoom->Execute($this->RoomID);  

        return true;              
    }
	
	private function GetRoomByID()
	{
		$RSRoom = $this->DBObject->Prepare('SELECT * FROM ahm_rooms WHERE roomID = :|1 LIMIT 1;');
		$RSRoom->Execute($this->RoomID);
		
		$RoomRow = $RSRoom->FetchRow();
		
		$this->SetAttributesFromDB($RoomRow);				
	}
	
	private function SetAttributesFromDB($RoomRow)
	{
		$this->RoomID = $RoomRow->roomID;
		$this->WingID = $RoomRow->wingID;
		$this->RoomTypeID = $RoomRow->roomTypeID;

		$this->RoomName = $RoomRow->roomName;
		$this->BedCount = $RoomRow->bedCount;

		$this->MonthlyFee = $RoomRow->monthlyFee;
		$this->QuarterlyFee = $RoomRow->quarterlyFee;
		$this->SemiAnnualFee = $RoomRow->semiAnnualFee;
		$this->AnnualFee = $RoomRow->annualFee;

		$this->IsActive = $RoomRow->isActive;
		$this->CreateUserID = $RoomRow->createUserID;
		$this->CreateDate = $RoomRow->createDate;
	}	
}
?>
<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.db_connect.php');
error_reporting(E_ALL);

class RoomType
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	
	private $RoomTypeID;
	private $RoomType;

	private $IsActive;

	private $CreateUserID;
	private $CreateDate;
	
	// PUBLIC METHODS START HERE	//
	public function __construct($RoomTypeID = 0)
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;
		
		if($RoomTypeID != 0)
		{
			$this->RoomTypeID = $RoomTypeID;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetRoomTypeByID();
		}
		else
		{
			//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
			$this->RoomTypeID = 0;
			$this->RoomType = '';

			$this->IsActive = 0;

			$this->CreateUserID = 0;
			$this->CreateDate = '0000-00-00 00:00:00';
		}
	}
	
	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetRoomTypeID()
	{
		return $this->RoomTypeID;
	}
	
	public function GetRoomType()
	{
		return $this->RoomType;
	}
	public function SetRoomType($RoomType)
	{
		$this->RoomType = $RoomType;
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
	public function Remove()
    {
        try
        {
            $this->RemoveRoomType();
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
            $RSRoomTypeCount = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM ahm_rooms WHERE roomTypeID = :|1;');
            $RSRoomTypeCount->Execute($this->RoomTypeID);

            if ($RSRoomTypeCount->FetchRow()->totalRecords > 0) 
            {
                return true;
            }

            return false;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at RoomType::CheckDependencies(). Stack Trace: ' . $e->getTraceAsString());
            return true;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: ApplicationDBException at RoomType::CheckDependencies(). Stack Trace: ' . $e->getTraceAsString());
            return true;
        }       
    }
		
	// END OF PUBLIC METHODS	//
		
	// START OF STATIC METHODS	//
	static function GetAllRoomTypes()
	{
		$AllRoomTypes = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT art.*, u.userName AS createUserName FROM ahm_room_types art
												INNER JOIN users u ON art.createUserID = u.userID 
												ORDER BY art.roomType;');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllRoomTypes; 
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$AllRoomTypes[$SearchRow->roomTypeID]['RoomType'] = $SearchRow->roomType;

				$AllRoomTypes[$SearchRow->roomTypeID]['IsActive'] = $SearchRow->isActive;

				$AllRoomTypes[$SearchRow->roomTypeID]['CreateUserID'] = $SearchRow->createUserID;
				$AllRoomTypes[$SearchRow->roomTypeID]['CreateUserName'] = $SearchRow->createUserName;
				$AllRoomTypes[$SearchRow->roomTypeID]['CreateDate'] = $SearchRow->createDate;
			}
			
			return $AllRoomTypes;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at RoomType::GetAllRoomTypes(). Stack Trace: ' . $e->getTraceAsString());
			return $AllRoomTypes;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at RoomType::GetAllRoomTypes(). Stack Trace: ' . $e->getTraceAsString());
			return $AllRoomTypes;
		}
	}

	static function GetActiveRoomTypes()
	{
		$ActiveRoomTypes = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT * FROM ahm_room_types WHERE isActive = 1;');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $ActiveRoomTypes;
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$ActiveRoomTypes[$SearchRow->roomTypeID] = $SearchRow->roomType;				
			}
			
			return $ActiveRoomTypes;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at RoomType::GetActiveRoomTypes(). Stack Trace: ' . $e->getTraceAsString());
			return $ActiveRoomTypes;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at RoomType::GetActiveRoomTypes(). Stack Trace: ' . $e->getTraceAsString());
			return $ActiveRoomTypes;
		}		
	}
	
	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//
	private function SaveDetails()
	{
		if ($this->RoomTypeID == 0)
		{
			$RSSave = $this->DBObject->Prepare('INSERT INTO ahm_room_types (roomType, isActive, createUserID, createDate)
														VALUES (:|1, :|2, :|3, NOW());');
		
			$RSSave->Execute($this->RoomType, $this->IsActive, $this->CreateUserID);
			
			$this->RoomTypeID = $RSSave->LastID;
		}
		else
		{
			$RSUpdate = $this->DBObject->Prepare('UPDATE ahm_room_types
													SET	roomType = :|1,
														isActive = :|2
													WHERE roomTypeID = :|3;');
													
			$RSUpdate->Execute($this->RoomType, $this->IsActive, $this->RoomTypeID);
		}
		
		return true;
	}

	private function RemoveRoomType()
    {
        if(!isset($this->RoomTypeID)) 
        {
            throw new ApplicationARException('', APP_AR_ERROR_DELETE_WITHOUT_ID);
        }
        
        $RSDeleteRoomType = $this->DBObject->Prepare('DELETE FROM ahm_room_types WHERE roomTypeID = :|1 LIMIT 1;');
        $RSDeleteRoomType->Execute($this->RoomTypeID);  

        return true;              
    }
	
	private function GetRoomTypeByID()
	{
		$RSRoomType = $this->DBObject->Prepare('SELECT * FROM ahm_room_types WHERE roomTypeID = :|1 LIMIT 1;');
		$RSRoomType->Execute($this->RoomTypeID);
		
		$RoomTypeRow = $RSRoomType->FetchRow();
		
		$this->SetAttributesFromDB($RoomTypeRow);				
	}
	
	private function SetAttributesFromDB($RoomTypeRow)
	{
		$this->RoomTypeID = $RoomTypeRow->roomTypeID;
		$this->RoomType = $RoomTypeRow->roomType;

		$this->IsActive = $RoomTypeRow->isActive;

		$this->CreateUserID = $RoomTypeRow->createUserID;
		$this->CreateDate = $RoomTypeRow->createDate;
	}	
}
?>
<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.db_connect.php');
error_reporting(E_ALL);

class VehicleType
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	
	private $VehicleTypeID;
	private $VehicleType;

	private $IsActive;
	private $CreateUserID;
	private $CreateDate;

	// PUBLIC METHODS START HERE	//
	public function __construct($VehicleTypeID = 0)
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;
		
		if($VehicleTypeID != 0)
		{
			$this->VehicleTypeID = $VehicleTypeID;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetVehicleTypeByID();
		}
		else
		{
			//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
			$this->VehicleTypeID = 0;
			$this->VehicleType = '';

			$this->IsActive = 0;
			$this->CreateUserID = 0;
			$this->CreateDate = '0000-00-00 00:00:00';
		}
	}
	
	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetVehicleTypeID()
	{
		return $this->VehicleTypeID;
	}
	
	public function GetVehicleType()
	{
		return $this->VehicleType;
	}
	public function SetVehicleType($VehicleType)
	{
		$this->VehicleType = $VehicleType;
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
            $this->RemoveVehicleType();
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
            $RSVehicleTypeCount = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM atm_vehicle WHERE vehicleTypeID = :|1;');
            $RSVehicleTypeCount->Execute($this->VehicleTypeID);

            if ($RSVehicleTypeCount->FetchRow()->totalRecords > 0) 
            {
                return true;
            }

            return false;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at VehicleType::CheckDependencies(). Stack Trace: ' . $e->getTraceAsString());
            return true;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: ApplicationDBException at VehicleType::CheckDependencies(). Stack Trace: ' . $e->getTraceAsString());
            return true;
        }       
    }
    
     public function RecordExists()
	{
		try
		{
			$QueryString = '';
			
			if ($this->VehicleTypeID > 0)
			{
				$QueryString = ' AND vehicleTypeID != ' . $this->DBObject->RealEscapeVariable($this->VehicleTypeID);
			}

			$RSTotal = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM atm_vehicle_type WHERE vehicleType = :|1' . $QueryString . ';');
			$RSTotal->Execute($this->VehicleType);
			
			if ($RSTotal->FetchRow()->totalRecords > 0)
			{
				return true;
			}
			
			return false;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at VehicleType::RecordExists(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at VehicleType::RecordExists(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
	}
	
	// END OF PUBLIC METHODS	//
	
	// START OF STATIC METHODS	//

	static function GetActiveVehicleType()
	{
		$ActiveVehicleType = array();
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT * FROM atm_vehicle_type WHERE isActive = 1;');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $ActiveVehicleType;
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$ActiveVehicleType[$SearchRow->vehicleTypeID] = $SearchRow->vehicleType;
			}
			
			return $ActiveVehicleType;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at VehicleType::GetActiveVehicleType(). Stack Trace: ' . $e->getTraceAsString());
			return $ActiveVehicleType;
		}
		catch (Exception $e) 
		{
			error_log('DEBUG: Exception at VehicleType::GetActiveVehicleType(). Stack Trace: ' . $e->getTraceAsString());
			return $ActiveVehicleType;
		}		
	}
	
	static function GetAllVehicleType()
	{
		$AllVehicleType = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT avt.*, u.userName AS createUserName FROM atm_vehicle_type avt
												INNER JOIN users u ON avt.createUserID = u.userID 
												ORDER BY avt.vehicleTypeID;');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllVehicleType; 
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$AllVehicleType[$SearchRow->vehicleTypeID]['VehicleType'] = $SearchRow->vehicleType;
				$AllVehicleType[$SearchRow->vehicleTypeID]['IsActive'] = $SearchRow->isActive;

				$AllVehicleType[$SearchRow->vehicleTypeID]['CreateUserID'] = $SearchRow->createUserID;
				$AllVehicleType[$SearchRow->vehicleTypeID]['CreateUserName'] = $SearchRow->createUserName;
				$AllVehicleType[$SearchRow->vehicleTypeID]['CreateDate'] = $SearchRow->createDate;

			}
			
			return $AllVehicleType;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at VehicleType::GetAllVehicleType(). Stack Trace: ' . $e->getTraceAsString());
			return $AllVehicleType;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at VehicleType::GetAllVehicleType(). Stack Trace: ' . $e->getTraceAsString());
			return $AllVehicleType;
		}
	}
	
	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//
	private function SaveDetails()
	{
		if ($this->VehicleTypeID == 0)
		{
			$RSSave = $this->DBObject->Prepare('INSERT INTO atm_vehicle_type (vehicleType, isActive, createUserID, createDate)
														VALUES (:|1, :|2, :|3, NOW());');
		
			$RSSave->Execute($this->VehicleType, $this->IsActive, $this->CreateUserID);
			
			$this->VehicleTypeID = $RSSave->LastID;
		}
		else
		{
			$RSUpdate = $this->DBObject->Prepare('UPDATE atm_vehicle_type
													SET	vehicleType = :|1,
														isActive = :|2
													WHERE vehicleTypeID = :|3;');
													
			$RSUpdate->Execute($this->VehicleType, $this->IsActive, $this->VehicleTypeID);
		}
		
		return true;
	}

	private function RemoveVehicleType()
    {
        if(!isset($this->VehicleTypeID)) 
        {
            throw new ApplicationARException('', APP_AR_ERROR_DELETE_WITHOUT_ID);
        }
        
        $RSDeleteVehicleType = $this->DBObject->Prepare('DELETE FROM atm_vehicle_type WHERE vehicleTypeID = :|1 LIMIT 1;');
        $RSDeleteVehicleType->Execute($this->VehicleTypeID); 

        return true;               
    }

	private function GetVehicleTypeByID()
	{
		$RSVehicleType = $this->DBObject->Prepare('SELECT * FROM atm_vehicle_type WHERE vehicleTypeID = :|1;');
		$RSVehicleType->Execute($this->VehicleTypeID);
		
		$VehicleTypeRow = $RSVehicleType->FetchRow();
		
		$this->SetAttributesFromDB($VehicleTypeRow);				
	}
	
	private function SetAttributesFromDB($VehicleTypeRow)
	{
		$this->VehicleTypeID = $VehicleTypeRow->vehicleTypeID;
		$this->VehicleType = $VehicleTypeRow->vehicleType;

		$this->IsActive = $VehicleTypeRow->isActive;
		$this->CreateUserID = $VehicleTypeRow->createUserID;
		$this->CreateDate = $VehicleTypeRow->createDate;

	}	
}
?>
<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.db_connect.php');
error_reporting(E_ALL);

class VehicleDrivers
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	
	private $VehicleDriverID;
	private $VehicleID;
	private $DriverID;

	private $IsActive;
	private $CreateUserID;
	private $CreateDate;

	// PUBLIC METHODS START HERE	//
	public function __construct($VehicleDriverID = 0)
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;
		
		if($VehicleDriverID != 0)
		{
			$this->VehicleDriverID = $VehicleDriverID;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetVehicleDriversByID();
		}
		else
		{
			//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
			$this->VehicleDriverID = 0;

			$this->VehicleID = 0;
			$this->DriverID = 0;
			$this->IsActive = 0;
			$this->CreateUserID = 0;
			$this->CreateDate = '0000-00-00 00:00:00';
		}
	}
	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetVehicleDriverID()
	{
		return $this->VehicleDriverID;
	}

	public function GetVehicleID()
	{
		return $this->VehicleID;
	}
	public function SetVehicleID($VehicleID)
	{
		$this->VehicleID = $VehicleID;
	}

	public function GetDriverID()
	{
		return $this->DriverID;
	}
	public function SetDriverID($DriverID)
	{
		$this->DriverID = $DriverID;
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
            $this->RemoveVehicleDrivers();
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
            $RSVehicleDriversCount = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM atm_vehicle WHERE vehicleTypeID = :|1;');
            $RSVehicleDriversCount = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM atm_driver WHERE driverID = :|1;');

            $RSVehicleDriversCount->Execute($this->VehicleDriverID);

            if ($RSVehicleDriversCount->FetchRow()->totalRecords > 0) 
            {
                return true;
            }

            return false;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at VehicleDrivers::CheckDependencies(). Stack Trace: ' . $e->getTraceAsString());
            return true;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: ApplicationDBException at VehicleDrivers::CheckDependencies(). Stack Trace: ' . $e->getTraceAsString());
            return true;
        }       
    }
	// END OF PUBLIC METHODS	//
	
	// START OF STATIC METHODS	//
 static function SearchVehicleDrivers(&$TotalRecords = 0, $GetTotalsOnly = false, $Filters = array(), $Start = 0, $Limit = 100)
 {
		$AllVehicleDriver = array();

		try
		{
			$DBConnObject = new DBConnect();
			
			$Conditions = array();
			
			if (count($Filters) > 0)
			{	
				if (!empty($Filters['VehicleTypeID']))
				{
					$Conditions[] = 'avt.vehicleTypeID = '. $DBConnObject->RealEscapeVariable($Filters['VehicleTypeID']);
				}	

				if (!empty($Filters['DriverName']))
				{
					$Conditions[] = 'CONCAT(ad.driverFirstName, " ", ad.driverLastName) LIKE ' . $DBConnObject->RealEscapeVariable('%' . $Filters['DriverName'] . '%');
				}

				if (!empty($Filters['ActiveStatus']))
				{
					if ($Filters['ActiveStatus'] == 1) //active records
					{
						$Conditions[] = 'avd.isActive = 1';
					}
					else if ($Filters['ActiveStatus'] == 2) //non active records
					{
						$Conditions[] = 'avd.isActive = 0';
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
													FROM atm_vehicle_drivers avd
													INNER JOIN atm_vehicle av ON av.vehicleID = avd.vehicleID 
													INNER JOIN atm_vehicle_type avt ON avt.vehicleTypeID = av.vehicleTypeID 
													INNER JOIN atm_driver ad ON ad.driverID = avd.driverID
													INNER JOIN users u ON avd.createUserID = u.userID 
													'. $QueryString .';');
				$RSTotal->Execute();
				
				$TotalRecords = $RSTotal->FetchRow()->totalRecords;
				return;
			}
			
			$RSSearch = $DBConnObject->Prepare('SELECT avd.*, avt.vehicleType, av.vehicleName, av.vehicleNumber, ad.driverFirstName, ad.driverLastName, ad.fatherName, ad.contactNumber, u.userName AS createUserName
												FROM atm_vehicle_drivers avd
												INNER JOIN atm_vehicle av ON av.vehicleID = avd.vehicleID
												INNER JOIN atm_vehicle_type avt ON avt.vehicleTypeID = av.vehicleTypeID 
												INNER JOIN atm_driver ad ON ad.driverID = avd.driverID
												INNER JOIN users u ON avd.createUserID = u.userID 
												'. $QueryString .' 
												ORDER BY avt.vehicleType LIMIT ' . (int) $Start . ', ' . (int) $Limit . ';');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllVehicleDriver; 
			}

			while($SearchRow = $RSSearch->FetchRow())
			{   
			    $AllVehicleDriver[$SearchRow->vehicleDriverID]['DriverFirstName'] = $SearchRow->driverFirstName;
				$AllVehicleDriver[$SearchRow->vehicleDriverID]['DriverLastName'] = $SearchRow->driverLastName;
				$AllVehicleDriver[$SearchRow->vehicleDriverID]['FatherName'] = $SearchRow->fatherName;

				$AllVehicleDriver[$SearchRow->vehicleDriverID]['VehicleType'] = $SearchRow->vehicleType;
			    $AllVehicleDriver[$SearchRow->vehicleDriverID]['VehicleName'] = $SearchRow->vehicleName;
				$AllVehicleDriver[$SearchRow->vehicleDriverID]['VehicleNumber'] = $SearchRow->vehicleNumber;

				$AllVehicleDriver[$SearchRow->vehicleDriverID]['ContactNumber'] = $SearchRow->contactNumber;
				$AllVehicleDriver[$SearchRow->vehicleDriverID]['IsActive'] = $SearchRow->isActive;
				$AllVehicleDriver[$SearchRow->vehicleDriverID]['CreateUserName'] = $SearchRow->createUserName;
				$AllVehicleDriver[$SearchRow->vehicleDriverID]['CreateDate'] = $SearchRow->createDate;
			}
			
			return $AllVehicleDriver;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at VehicleDrivers::SearchVehicleDrivers(). Stack Trace: ' . $e->getTraceAsString());
			return $AllVehicleDriver;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at VehicleDrivers::SearchVehicleDrivers(). Stack Trace: ' . $e->getTraceAsString());
			return $AllVehicleDriver;
		}
	}

	static function GetActiveVehicleDrivers()
	{
		$ActiveVehicleDrivers = array();
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT * FROM atm_vehicle_drivers WHERE isActive = 1;');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $ActiveVehicleDrivers;
			}
			while($SearchRow = $RSSearch->FetchRow())
			{
				$ActiveVehicleDrivers[$SearchRow->vehicleDriverID] = $SearchRow->vehicleID;
			}

			return $ActiveVehicleDrivers;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at VehicleDrivers::GetActiveVehicleDrivers(). Stack Trace: ' . $e->getTraceAsString());
			return $ActiveVehicleDrivers;
		}
		catch (Exception $e) 
		{
			error_log('DEBUG: Exception at VehicleDrivers::GetActiveVehicleDrivers(). Stack Trace: ' . $e->getTraceAsString());
			return $ActiveVehicleDrivers;
		}		
	}

	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//
	 private function SaveDetails()
	 {
		if ($this->VehicleDriverID == 0)
		{
			$RSSave = $this->DBObject->Prepare('INSERT INTO atm_vehicle_drivers (vehicleID, driverID, isActive, createUserID, createDate)
														VALUES (:|1, :|2, :|3, :|4, NOW());');
		
			$RSSave->Execute($this->VehicleID, $this->DriverID, $this->IsActive, $this->CreateUserID);
			
			$this->VehicleDriverID = $RSSave->LastID;
		}

		else
		{
			$RSUpdate = $this->DBObject->Prepare('UPDATE atm_vehicle_drivers
													SET	vehicleID = :|1,
													    driverID = :|2,
														isActive = :|3
													WHERE VehicleDriverID = :|4;');
													
			$RSUpdate->Execute($this->VehicleID, $this->DriverID, $this->IsActive, $this->VehicleDriverID);
		}
		
		return true;
	}

	private function RemoveVehicleDrivers()
    {
        if(!isset($this->VehicleDriverID)) 
        {
            throw new ApplicationARException('', APP_AR_ERROR_DELETE_WITHOUT_ID);
        }
        
        $RSDeleteVehicleDrivers = $this->DBObject->Prepare('DELETE FROM atm_vehicle_drivers WHERE vehicleDriverID = :|1 LIMIT 1;');
        $RSDeleteVehicleDrivers->Execute($this->VehicleDriverID); 

        return true;               
    }

	private function GetVehicleDriversByID()
	{
		$RSVehicleDrivers = $this->DBObject->Prepare('SELECT * FROM atm_vehicle_drivers WHERE vehicleDriverID = :|1;');
		$RSVehicleDrivers->Execute($this->VehicleDriverID);
		
		$VehicleDriversRow = $RSVehicleDrivers->FetchRow();
		
		$this->SetAttributesFromDB($VehicleDriversRow);				
	}
	
	private function SetAttributesFromDB($VehicleDriversRow)
	{
		$this->VehicleDriverID = $VehicleDriversRow->vehicleDriverID;
		$this->VehicleID = $VehicleDriversRow->vehicleID;
		$this->DriverID = $VehicleDriversRow->driverID;

		$this->IsActive = $VehicleDriversRow->isActive;
		$this->CreateUserID = $VehicleDriversRow->createUserID;
		$this->CreateDate = $VehicleDriversRow->createDate;

	}	
}
?>
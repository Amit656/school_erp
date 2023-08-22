<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.db_connect.php');
error_reporting(E_ALL);

class Driver
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	
	private $DriverID;
	private $DriverFirstName;
	private $DriverLastName;
	private $FatherName;

	private $DOB;
	private $Address;

	private $ContactNumber;
	private $Email;

	private $IsAdharCard;
	private $AdharNumber;

	private $IsPanCard;
	private $PanNumber;

	private $IsVoterID;
	private $VoterIDNumber;

	private $DrivingLicenceNumber;
	private $DrivingLicenceValidityFrom;
	private $DrivingLicenceValidityTo;

	private $IsActive;
	private $CreateUserID;
	private $CreateDate;
	
	// PUBLIC METHODS START HERE	//
	public function __construct($DriverID = 0)
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;
		
		if($DriverID != 0)
		{
			$this->DriverID = $DriverID;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetDriverByID();
		}
		else
		{
			//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
			$this->DriverID = 0;
			$this->DriverFirstName = '';
			$this->DriverLastName = '';

			$this->FatherName = '';
			$this->DOB = '';
			$this->Address = '';

			$this->ContactNumber = '';
			$this->Email = '';
			$this->IsAdharCard = 0;
			$this->AdharNumber = 0;

			$this->IsPanCard = 0;
			$this->PanNumber = '';
			$this->IsVoterID = 0;
			$this->VoterIDNumber = '';
			$this->DrivingLicenceNumber = '';

			$this->DrivingLicenceValidityFrom = '';
			$this->DrivingLicenceValidityTo = '';

			$this->IsActive = 0;
			$this->CreateUserID = 0;
			$this->CreateDate = '0000-00-00 00:00:00';
		}
	}
	
	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetDriverID()
	{
		return $this->DriverID;
	}
	
	public function GetDriverFirstName()
	{
		return $this->DriverFirstName;
	}
	public function SetDriverFirstName($DriverFirstName)
	{
		$this->DriverFirstName = $DriverFirstName;
	}

	public function GetDriverLastName()
	{
		return $this->DriverLastName;
	}
	public function SetDriverLastName($DriverLastName)
	{
		$this->DriverLastName = $DriverLastName;
	}

	public function GetFatherName()
	{
		return $this->FatherName;
	}
	public function SetFatherName($FatherName)
	{
		$this->FatherName = $FatherName;
	}

	public function GetDOB()
	{
		return $this->DOB;
	}
	public function SetDOB($DOB)
	{
		$this->DOB = $DOB;
	}

	public function GetAddress()
	{
		return $this->Address;
	}
	public function SetAddress($Address)
	{
		$this->Address = $Address;
	}

	public function GetContactNumber()
	{
		return $this->ContactNumber;
	}
	public function SetContactNumber($ContactNumber)
	{
		$this->ContactNumber = $ContactNumber;
	}

	public function GetEmail()
	{
		return $this->Email;
	}
	public function SetEmail($Email)
	{
		$this->Email = $Email;
	}

	public function GetIsAdharCard()
	{
		return $this->IsAdharCard;
	}
	public function SetIsAdharCard($IsAdharCard)
	{
		$this->IsAdharCard = $IsAdharCard;
	}

	public function GetAdharNumber()
	{
		return $this->AdharNumber;
	}
	public function SetAdharNumber($AdharNumber)
	{
		$this->AdharNumber = $AdharNumber;
	}

	public function GetIsPanCard()
	{
		return $this->IsPanCard;
	}
	public function SetIsPanCard($IsPanCard)
	{
		$this->IsPanCard = $IsPanCard;
	}

	public function GetPanNumber()
	{
		return $this->PanNumber;
	}
	public function SetPanNumber($PanNumber)
	{
		$this->PanNumber = $PanNumber;
	}

	public function GetIsVoterID()
	{
		return $this->IsVoterID;
	}
	public function SetIsVoterID($IsVoterID)
	{
		$this->IsVoterID = $IsVoterID;
	}

	public function GetVoterIDNumber()
	{
		return $this->VoterIDNumber;
	}
	public function SetVoterIDNumber($VoterIDNumber)
	{
		$this->VoterIDNumber = $VoterIDNumber;
	}

	public function GetDrivingLicenceNumber()
	{
		return $this->DrivingLicenceNumber;
	}
	public function SetDrivingLicenceNumber($DrivingLicenceNumber)
	{
		$this->DrivingLicenceNumber = $DrivingLicenceNumber;
	}

	public function GetDrivingLicenceValidityFrom()
	{
		return $this->DrivingLicenceValidityFrom;
	}
	public function SetDrivingLicenceValidityFrom($DrivingLicenceValidityFrom)
	{
		$this->DrivingLicenceValidityFrom = $DrivingLicenceValidityFrom;
	}

	public function GetDrivingLicenceValidityTo()
	{
		return $this->DrivingLicenceValidityTo;
	}
	public function SetDrivingLicenceValidityTo($DrivingLicenceValidityTo)
	{
		$this->DrivingLicenceValidityTo = $DrivingLicenceValidityTo;
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
            $this->DBObject->BeginTransaction();
			if ($this->RemoveDriver())
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
		
	// END OF PUBLIC METHODS	//
	
	// START OF STATIC METHODS	//
		static function GetActiveDriver()
	   {
		$ActiveDriver = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT * FROM atm_driver WHERE isActive = 1;');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $ActiveDriver;
			}	
			while($SearchRow = $RSSearch->FetchRow())
			{
				$ActiveDriver[$SearchRow->driverID] = $SearchRow->driverFirstName .' '. $SearchRow->driverLastName;
			}
			
			return $ActiveDriver;	
		}	
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at Driver::GetActiveDriver(). Stack Trace: ' . $e->getTraceAsString());
			return $ActiveDriver;
		}
		catch (Exception $e) 
		{
			error_log('DEBUG: Exception at Driver::GetActiveDriver(). Stack Trace: ' . $e->getTraceAsString());
			return $ActiveDriver;
		}		
	}

	static function SearchDrivers(&$TotalRecords = 0, $GetTotalsOnly = false, $Filters = array(), $Start = 0, $Limit = 100)
	{
		$AllDriver = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$Conditions = array();
			
			if (count($Filters) > 0)
			{	
				if (!empty($Filters['DriverName']))
				{
					$Conditions[] = 'CONCAT(ad.driverFirstName, " ", ad.driverLastName) LIKE ' . $DBConnObject->RealEscapeVariable('%' . $Filters['DriverName'] . '%');
				}

				if (!empty($Filters['ContactNumber']))
				{
					$Conditions[] = 'ad.contactNumber = '.$DBConnObject->RealEscapeVariable($Filters['ContactNumber']);
				}

				if (!empty($Filters['ActiveStatus']))
				{
					if ($Filters['ActiveStatus'] == 1) //active records
					{
						$Conditions[] = 'ad.isActive = 1';
					}
					else if ($Filters['ActiveStatus'] == 2) //non active records
					{
						$Conditions[] = 'ad.isActive = 0';
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
													FROM atm_driver ad
													INNER JOIN users u ON ad.createUserID = u.userID 
													'. $QueryString .';');
				$RSTotal->Execute();
				
				$TotalRecords = $RSTotal->FetchRow()->totalRecords;
				return;
			}
			
			$RSSearch = $DBConnObject->Prepare('SELECT ad.*, u.userName AS createUserName
												FROM atm_driver ad
												INNER JOIN users u ON ad.createUserID = u.userID 
												'. $QueryString .' 
												ORDER BY ad.driverFirstName LIMIT ' . (int) $Start . ', ' . (int) $Limit . ';');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllDriver; 
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$AllDriver[$SearchRow->driverID]['DriverFirstName'] = $SearchRow->driverFirstName;
				$AllDriver[$SearchRow->driverID]['DriverLastName'] = $SearchRow->driverLastName;

				$AllDriver[$SearchRow->driverID]['FatherName'] = $SearchRow->fatherName;
				$AllDriver[$SearchRow->driverID]['DOB'] = $SearchRow->DOB;

				$AllDriver[$SearchRow->driverID]['Address'] = $SearchRow->address;
				$AllDriver[$SearchRow->driverID]['ContactNumber'] = $SearchRow->contactNumber;
				$AllDriver[$SearchRow->driverID]['Email'] = $SearchRow->email;

				$AllDriver[$SearchRow->driverID]['AdharNumber'] = $SearchRow->adharNumber;
				$AllDriver[$SearchRow->driverID]['PanNumber'] = $SearchRow->panNumber;
				$AllDriver[$SearchRow->driverID]['VoterIDNumber'] = $SearchRow->voterIDNumber;

				$AllDriver[$SearchRow->driverID]['DrivingLicenceNumber'] = $SearchRow->drivingLicenceNumber;
				
				$AllDriver[$SearchRow->driverID]['DrivingLicenceValidityFrom'] = $SearchRow->drivingLicenceValidityFrom;
				$AllDriver[$SearchRow->driverID]['DrivingLicenceValidityTo'] = $SearchRow->drivingLicenceValidityTo;

				$AllDriver[$SearchRow->driverID]['IsActive'] = $SearchRow->isActive;
				$AllDriver[$SearchRow->driverID]['CreateUserName'] = $SearchRow->createUserName;
				$AllDriver[$SearchRow->driverID]['CreateDate'] = $SearchRow->createDate;
			}
			
			return $AllDriver;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at Driver::SearchDrivers(). Stack Trace: ' . $e->getTraceAsString());
			return $AllDriver;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at Driver::SearchDrivers(). Stack Trace: ' . $e->getTraceAsString());
			return $AllDriver;
		}
	}
	
	static function GetAllVehicleRoutesForGraphs()
    {
		$AllVehicleRoutesForGraph = array();
		
		try
        {	
        	$DBConnObject = new DBConnect();

        	$RSSearch = $DBConnObject->Prepare('SELECT count(ar.routeID) AS totalRecords, ar.routeNumber FROM atm_routes ar
        										INNER JOIN atm_route_vehicle av ON av.vehicleID = avr.vehicleID
        										GROUP By av.vehicleID;');
			$RSSearch->Execute();

			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllVehicleRoutesForGraph;
			}

			while($SearchRow = $RSSearch->FetchRow())
			{
				$AllVehicleRoutesForGraph[$SearchRow->routeNumber] = $SearchRow->totalRecords;
			}

			return $AllVehicleRoutesForGraph;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException: RouteVehicle::GetAllVehicleRoutesForGraphs(). Stack Trace: ' . $e->getTraceAsString());
            return $AllVehicleRoutesForGraph;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception: RouteVehicle::GetAllVehicleRoutesForGraphs(). Stack Trace: ' . $e->getTraceAsString());
            return $AllVehicleRoutesForGraph;
        }
    }
 
	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//
	private function SaveDetails()
	{
		if ($this->DriverID == 0)
		{
			$RSSave = $this->DBObject->Prepare('INSERT INTO atm_driver (driverFirstName, driverLastName, fatherName, DOB, address, contactNumber, email, isAdharCard, adharNumber, isPanCard, panNumber, isVoterID, voterIDNumber, drivingLicenceNumber, drivingLicenceValidityFrom, drivingLicenceValidityTo, isActive, createUserID, createDate)
														VALUES (:|1, :|2, :|3, :|4, :|5, :|6, :|7, :|8, :|9, :|10, :|11, :|12, :|13, :|14, :|15, :|16, :|17, :|18, NOW());');

			$RSSave->Execute($this->DriverFirstName, $this->DriverLastName, $this->FatherName, $this->DOB, $this->Address, $this->ContactNumber, $this->Email, $this->IsAdharCard, $this->AdharNumber, $this->IsPanCard, $this->PanNumber, $this->IsVoterID, $this->VoterIDNumber,  $this->DrivingLicenceNumber, $this->DrivingLicenceValidityFrom, $this->DrivingLicenceValidityTo, $this->IsActive, $this->CreateUserID);
			
			$this->DriverID = $RSSave->LastID;
		}
		else
		{
			$RSUpdate = $this->DBObject->Prepare('UPDATE atm_driver
													SET	driverFirstName = :|1,									
													   	driverLastName = :|2,
													   	fatherName = :|3,
													   	DOB = :|4,
													  	address = :|5,
													   	contactNumber = :|6,
													  	email = :|7,
													   	isAdharCard = :|8,
													   	adharNumber = :|9,
													   	isPanCard = :|10,
													   	panNumber = :|11,
													   	isVoterID = :|12,
													   	voterIDNumber = :|13,
													   	drivingLicenceNumber = :|14,
													   	drivingLicenceValidityFrom = :|15,
													   	drivingLicenceValidityTo = :|16,
														isActive = :|17
													WHERE driverID = :|18;');
													
			$RSUpdate->Execute($this->DriverFirstName, $this->DriverLastName, $this->FatherName, $this->DOB, $this->Address, $this->ContactNumber, $this->Email, $this->IsAdharCard, $this->AdharNumber, $this->IsPanCard, $this->PanNumber, $this->IsVoterID, $this->VoterIDNumber, $this->DrivingLicenceNumber, $this->DrivingLicenceValidityFrom, $this->DrivingLicenceValidityTo, $this->IsActive, $this->DriverID);
		}
		return true;
	}

	private function RemoveDriver()
    {
        if(!isset($this->DriverID)) 
        {
            throw new ApplicationARException('', APP_AR_ERROR_DELETE_WITHOUT_ID);
        }
        
        $RSDeleteDriver = $this->DBObject->Prepare('DELETE FROM atm_driver WHERE driverID = :|1 LIMIT 1;');
        $RSDeleteDriver->Execute($this->DriverID); 

        return true;               
    }

	private function GetDriverByID()
	{
		$RSDriver = $this->DBObject->Prepare('SELECT * FROM atm_driver WHERE driverID = :|1;');
		$RSDriver->Execute($this->DriverID);
		
		$DriverRow = $RSDriver->FetchRow();
		
		$this->SetAttributesFromDB($DriverRow);				
	}
	
	private function SetAttributesFromDB($DriverRow)
	{
		$this->DriverID = $DriverRow->driverID;
		$this->DriverFirstName = $DriverRow->driverFirstName;
		$this->DriverLastName = $DriverRow->driverLastName;

		$this->FatherName = $DriverRow->fatherName;
		$this->DOB = $DriverRow->DOB;
		$this->Address = $DriverRow->address;

		$this->ContactNumber = $DriverRow->contactNumber;
		$this->Email = $DriverRow->email;
		$this->IsAdharCard = $DriverRow->isAdharCard;
		$this->AdharNumber = $DriverRow->adharNumber;

		$this->IsPanCard = $DriverRow->isPanCard;
		$this->PanNumber = $DriverRow->panNumber;
		$this->IsVoterID = $DriverRow->isVoterID;
		$this->VoterIDNumber = $DriverRow->voterIDNumber;

		$this->DrivingLicenceNumber = $DriverRow->drivingLicenceNumber;
		$this->DrivingLicenceValidityFrom = $DriverRow->drivingLicenceValidityFrom;
		$this->DrivingLicenceValidityTo = $DriverRow->drivingLicenceValidityTo;

		$this->IsActive = $DriverRow->isActive;
		$this->CreateUserID = $DriverRow->createUserID;
		$this->CreateDate = $DriverRow->createDate;
	}	
}
?>
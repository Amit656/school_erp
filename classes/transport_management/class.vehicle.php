<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.db_connect.php');
error_reporting(E_ALL);

class Vehicle
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	
	private $VehicleID;
	private $VehicleTypeID;

	private $VehicleName;
	private $VehicleNumber;

	private $RegistrationFrom;
	private $RegistrationTo;
	private $InsuranceFrom;
	private $InsuranceTo;

	private $PollutionFrom;
	private $PollutionTo;
	private $ApprovalTo;

	private $FitnessDocumentNumber;
	private $PermitNumber;

	private $AvailableSaets;
	private $IsDiesel;
	private $IsPetrol;
	private $IsGas;

	private $LastServicedDate;
	private $ServiceDueDate;

	private $IsActive;
	private $CreateUserID;
	private $CreateDate;

	// PUBLIC METHODS START HERE	//
	public function __construct($VehicleID = 0)
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;
		
		if($VehicleID != 0)
		{
			$this->VehicleID = $VehicleID;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetVehicleByID();
		}
		else
		{
			//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
			$this->VehicleID = 0;
			$this->VehicleTypeID = 0;

			$this->VehicleName = '';
			$this->VehicleNumber = '';

			$this->RegistrationFrom = '';
			$this->RegistrationTo = '';
			$this->InsuranceFrom = '';
			$this->InsuranceTo = '';

			$this->PollutionFrom = '';
			$this->PollutionTo = '';
			$this->ApprovalFrom = '';
			$this->ApprovalTo = '';

			$this->FitnessDocumentNumber = '';
			$this->PermitNumber = '';
			$this->AvailableSeats = '';

			$this->IsDiesel = '';
			$this->IsPetrol = '';
			$this->IsGas = '';

			$this->LastServicedDate = '';
			$this->ServiceDueDate = '';

			$this->IsActive = 0;
			$this->CreateUserID = 0;
			$this->CreateDate = '0000-00-00 00:00:00';
		}
	}
	
	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetVehicleID()
	{
		return $this->VehicleID;
	}

	public function GetVehicleTypeID()
	{
		return $this->VehicleTypeID;
	}
	public function SetVehicleTypeID($VehicleTypeID)
	{
		$this->VehicleTypeID = $VehicleTypeID;
	}

	public function GetVehicleName()
	{
		return $this->VehicleName;
	}
	public function SetVehicleName($VehicleName)
	{
		$this->VehicleName = $VehicleName;
	}

	public function GetVehicleNumber()
	{
	return $this->VehicleNumber;
	}
	public function SetVehicleNumber($VehicleNumber)
	{
	$this->VehicleNumber = $VehicleNumber;
	}

	public function GetRegistrationFrom()
	{
		return $this->RegistrationFrom;
	}
	public function SetRegistrationFrom($RegistrationFrom)
	{
		$this->RegistrationFrom = $RegistrationFrom;
	}

	public function GetRegistrationTo()
	{
		return $this->RegistrationTo;
	}
	public function SetRegistrationTo($RegistrationTo)
	{
		$this->RegistrationTo = $RegistrationTo;
	}

	public function GetInsuranceFrom()
	{
		return $this->InsuranceFrom;
	}
	public function SetInsuranceFrom($InsuranceFrom)
	{
		$this->InsuranceFrom = $InsuranceFrom;
	}

	public function GetInsuranceTo()
	{
		return $this->InsuranceTo;
	}
	public function SetInsuranceTo($InsuranceTo)
	{
		$this->InsuranceTo = $InsuranceTo;
	}

	public function GetPollutionFrom()
	{
		return $this->PollutionFrom;
	}
	public function SetPollutionFrom($PollutionFrom)
	{
		$this->PollutionFrom = $PollutionFrom;
	}

	public function GetPollutionTo()
	{
		return $this->PollutionTo;
	}
	public function SetPollutionTo($PollutionTo)
	{
		$this->PollutionTo = $PollutionTo;
	}

	public function GetApprovalFrom()
	{
		return $this->ApprovalFrom;
	}
	public function SetApprovalFrom($ApprovalFrom)
	{
		$this->ApprovalFrom = $ApprovalFrom;
	}

	public function GetApprovalTo()
	{
		return $this->ApprovalTo;
	}
	public function SetApprovalTo($ApprovalTo)
	{
		$this->ApprovalTo = $ApprovalTo;
	}

	public function GetFitnessDocumentNumber()
	{
		return $this->FitnessDocumentNumber;
	}
	public function SetFitnessDocumentNumber($FitnessDocumentNumber)
	{
		$this->FitnessDocumentNumber = $FitnessDocumentNumber;
	}

	public function GetPermitNumber()
	{
		return $this->PermitNumber;
	}
	public function SetPermitNumber($PermitNumber)
	{
		$this->PermitNumber = $PermitNumber;
	}

	public function GetAvailableSeats()
	{
		return $this->AvailableSeats;
	}
	public function SetAvailableSeats($AvailableSeats)
	{
		$this->AvailableSeats = $AvailableSeats;
	}

	public function GetIsIsDiesel()
	{
		return $this->IsDiesel;
	}
	public function SetIsDiesel($IsDiesel)
	{
		$this->IsDiesel = $IsDiesel;
	}

	public function GetIsPetrol()
	{
		return $this->IsPetrol;
	}
	public function SetIsPetrol($IsPetrol)
	{
		$this->IsPetrol = $IsPetrol;
	}

   public function GetIsGas()
	{
		return $this->IsGas;
	}
	public function SetIsGas($IsGas)
	{
		$this->IsGas = $IsGas;
	}

	public function GetLastServicedDate()
	{
		return $this->LastServicedDate;
	}
	public function SetLastServicedDate($LastServicedDate)
	{
		$this->LastServicedDate = $LastServicedDate;
	}

	public function GetServiceDueDate()
	{
		return $this->ServiceDueDate;
	}
	public function SetServiceDueDate($ServiceDueDate)
	{
		$this->ServiceDueDate = $ServiceDueDate;
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
            $this->RemoveVehicle();
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
            $RSVehicleNameCount = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM atm_vehicle WHERE vehicleID = :|1;');
            $RSVehicleNameCount->Execute($this->VehicleID);

            if ($RSVehicleNameCount->FetchRow()->totalRecords > 0) 
            {
                return true;
            }

            return false;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at Vehicle::CheckDependencies(). Stack Trace: ' . $e->getTraceAsString());
            return true;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: ApplicationDBException at Vehicle::CheckDependencies(). Stack Trace: ' . $e->getTraceAsString());
            return true;
        }       
    }

     public function RecordExists()
	{
		try
		{
			$QueryString = '';
			
			if ($this->VehicleID > 0)
			{
				$QueryString = ' AND vehicleID != ' . $this->DBObject->RealEscapeVariable($this->VehicleID);
			}

			$RSTotal = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM atm_vehicle WHERE vehicleName = :|1' . $QueryString . ';');
			$RSTotal->Execute($this->VehicleName);
						
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

	static function SearchVehicles(&$TotalRecords = 0, $GetTotalsOnly = false, $Filters = array(), $Start = 0, $Limit = 100)
	{
		$AllVehicle = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$Conditions = array();
			
			if (count($Filters) > 0)
			{	
				if (!empty($Filters['VehicleTypeID']))
				{
					$Conditions[] = 'av.vehicleTypeID = '.$DBConnObject->RealEscapeVariable($Filters['VehicleTypeID']);
				}			
				if (!empty($Filters['ActiveStatus']))
				{
					if ($Filters['ActiveStatus'] == 1) //active records
					{
						$Conditions[] = 'av.isActive = 1';
					}
					else if ($Filters['ActiveStatus'] == 2) //non active records
					{
						$Conditions[] = 'av.isActive = 0';
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
													FROM atm_vehicle av
													INNER JOIN atm_vehicle_type avt ON avt.vehicleTypeID = av.vehicleTypeID 
													INNER JOIN users u ON av.createUserID = u.userID 
													'. $QueryString .';');
				$RSTotal->Execute();
				
				$TotalRecords = $RSTotal->FetchRow()->totalRecords;
				return;
			}
			
			$RSSearch = $DBConnObject->Prepare('SELECT av.*, avt.vehicleType, u.userName AS createUserName 
												FROM atm_vehicle av
												INNER JOIN atm_vehicle_type avt ON avt.vehicleTypeID = av.vehicleTypeID 
												INNER JOIN users u ON av.createUserID = u.userID 
												'. $QueryString .' 
												ORDER BY av.vehicleName LIMIT ' . (int) $Start . ', ' . (int) $Limit . ';');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllVehicle; 
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$AllVehicle[$SearchRow->vehicleID]['VehicleTypeID'] = $SearchRow->vehicleTypeID;
				$AllVehicle[$SearchRow->vehicleID]['VehicleName'] = $SearchRow->vehicleName;
				$AllVehicle[$SearchRow->vehicleID]['VehicleType'] = $SearchRow->vehicleType;
				$AllVehicle[$SearchRow->vehicleID]['VehicleNumber'] = $SearchRow->vehicleNumber;

				$AllVehicle[$SearchRow->vehicleID]['RegistrationFrom'] = $SearchRow->registrationFrom;
				$AllVehicle[$SearchRow->vehicleID]['RegistrationTo'] = $SearchRow->registrationTo;
				$AllVehicle[$SearchRow->vehicleID]['InsuranceFrom'] = $SearchRow->insuranceFrom;
				$AllVehicle[$SearchRow->vehicleID]['InsuranceTo'] = $SearchRow->insuranceTo;

				$AllVehicle[$SearchRow->vehicleID]['PollutionFrom'] = $SearchRow->pollutionFrom;
				$AllVehicle[$SearchRow->vehicleID]['PollutionTo'] = $SearchRow->pollutionTo;
				$AllVehicle[$SearchRow->vehicleID]['ApprovalFrom'] = $SearchRow->approvalFrom;
				$AllVehicle[$SearchRow->vehicleID]['ApprovalTo'] = $SearchRow->approvalTo;

				$AllVehicle[$SearchRow->vehicleID]['FitnessDocumentNumber'] = $SearchRow->fitnessDocumentNumber;
				$AllVehicle[$SearchRow->vehicleID]['PermitNumber'] = $SearchRow->permitNumber;
				$AllVehicle[$SearchRow->vehicleID]['AvailableSaets'] = $SearchRow->availableSeats;

				$AllVehicle[$SearchRow->vehicleID]['IsDiesel'] = $SearchRow->isDiesel;
				$AllVehicle[$SearchRow->vehicleID]['IsPetrol'] = $SearchRow->isPetrol;
				$AllVehicle[$SearchRow->vehicleID]['IsGas'] = $SearchRow->isGas;

				$AllVehicle[$SearchRow->vehicleID]['LastServicedDate'] = $SearchRow->lastServicedDate;
				$AllVehicle[$SearchRow->vehicleID]['ServiceDueDate'] = $SearchRow->serviceDueDate;

				$AllVehicle[$SearchRow->vehicleID]['IsActive'] = $SearchRow->isActive;

				$AllVehicle[$SearchRow->vehicleID]['CreateUserID'] = $SearchRow->createUserID;
				$AllVehicle[$SearchRow->vehicleID]['CreateUserName'] = $SearchRow->createUserName;
				$AllVehicle[$SearchRow->vehicleID]['CreateDate'] = $SearchRow->createDate;
			}
			
			return $AllVehicle;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at Vehicle::SearchVehicles(). Stack Trace: ' . $e->getTraceAsString());
			return $AllVehicle;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at Vehicle::SearchVehicles(). Stack Trace: ' . $e->getTraceAsString());
			return $AllVehicle;
		}
	}

	static function GetActiveVehicle()
	{
		$ActiveVehicle = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT * FROM atm_vehicle WHERE isActive = 1;');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $ActiveVehicle;
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$ActiveVehicle[$SearchRow->vehicleID] = $SearchRow->vehicleName;
			}
			
			return $ActiveVehicle;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at Vehicle::GetActiveVehicle(). Stack Trace: ' . $e->getTraceAsString());
			return $ActiveVehicle;
		}
		catch (Exception $e) 
		{
			error_log('DEBUG: Exception at Vehicle::GetActiveVehicle(). Stack Trace: ' . $e->getTraceAsString());
			return $ActiveVehicle;
		}		
	}

	static function GetAllVehicle()
	{
		$AllVehicle = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT av.*, u.userName AS createUserName FROM atm_vehicle av
												INNER JOIN users u ON av.createUserID = u.userID 
												ORDER BY av.vehicleID;');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllVehicle; 
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$AllVehicle[$SearchRow->vehicleID]['VehicleTypeID'] = $SearchRow->vehicleTypeID;

				$AllVehicle[$SearchRow->vehicleID]['VehicleName'] = $SearchRow->vehicleName;
				$AllVehicle[$SearchRow->vehicleID]['VehicleNumber'] = $SearchRow->vehicleNumber;

				$AllVehicle[$SearchRow->vehicleID]['RegistrationFrom'] = $SearchRow->registrationFrom;
				$AllVehicle[$SearchRow->vehicleID]['RegistrationTo'] = $SearchRow->registrationTo;
				$AllVehicle[$SearchRow->vehicleID]['InsuranceFrom'] = $SearchRow->insuranceFrom;
				$AllVehicle[$SearchRow->vehicleID]['InsuranceTo'] = $SearchRow->insuranceTo;

				$AllVehicle[$SearchRow->vehicleID]['PollutionFrom'] = $SearchRow->pollutionFrom;
				$AllVehicle[$SearchRow->vehicleID]['PollutionTo'] = $SearchRow->pollutionTo;
				$AllVehicle[$SearchRow->vehicleID]['ApprovalFrom'] = $SearchRow->approvalFrom;
				$AllVehicle[$SearchRow->vehicleID]['ApprovalTo'] = $SearchRow->approvalTo;

				$AllVehicle[$SearchRow->vehicleID]['FitnessDocumentNumber'] = $SearchRow->fitnessDocumentNumber;
				$AllVehicle[$SearchRow->vehicleID]['PermitNumber'] = $SearchRow->permitNumber;
				$AllVehicle[$SearchRow->vehicleID]['AvailableSeats'] = $SearchRow->availableSeats;

				$AllVehicle[$SearchRow->vehicleID]['IsDiesel'] = $SearchRow->isDiesel;
				$AllVehicle[$SearchRow->vehicleID]['IsPetrol'] = $SearchRow->isPetrol;
				$AllVehicle[$SearchRow->vehicleID]['IsGas'] = $SearchRow->isGas;

				$AllVehicle[$SearchRow->vehicleID]['LastServicedDate'] = $SearchRow->lastServicedDate;
				$AllVehicle[$SearchRow->vehicleID]['ServiceDueDate'] = $SearchRow->serviceDueDate;

				$AllVehicle[$SearchRow->vehicleID]['IsActive'] = $SearchRow->isActive;
				$AllVehicle[$SearchRow->vehicleID]['CreateUserID'] = $SearchRow->createUserID;
				$AllVehicle[$SearchRow->vehicleID]['CreateDate'] = $SearchRow->createDate;
			}
			
			return $AllVehicle;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at FeeHead::GetAllVehicle(). Stack Trace: ' . $e->getTraceAsString());
			return $AllVehicle;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at FeeHead::GetAllVehicle(). Stack Trace: ' . $e->getTraceAsString());
			return $AllVehicle;
		}
	}

	static function GetAllVehiclesForGraphs()
    {
		$AllVehiclesForGraph = array();
		
		try
        {	
        	$DBConnObject = new DBConnect();

        	$RSSearch = $DBConnObject->Prepare('SELECT count(av.vehicleID) AS totalRecords, av.vehicleName FROM atm_vehicle av
        										INNER JOIN atm_vehicle_type avt ON avt.vehicleTypeID = av.vehicleTypeID
        										GROUP By avt.vehicleTypeID;');
			$RSSearch->Execute();

			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllVehiclesForGraph;
			}

			while($SearchRow = $RSSearch->FetchRow())
			{
				$AllVehiclesForGraph[$SearchRow->vehicleName] = $SearchRow->totalRecords;
			}

			return $AllVehiclesForGraph;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException: VehicleType::GetAllVehiclesForGraphs(). Stack Trace: ' . $e->getTraceAsString());
            return $AllVehiclesForGraph;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception: VehicleType::GetAllVehiclesForGraphs(). Stack Trace: ' . $e->getTraceAsString());
            return $AllVehiclesForGraph;
        }
    }
	
	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//
	private function SaveDetails()
	{
		if ($this->VehicleID == 0)
		{
			$RSSave = $this->DBObject->Prepare('INSERT INTO atm_vehicle (vehicleTypeID, vehicleName, vehicleNumber, registrationFrom, registrationTo, insuranceFrom, insuranceTo, pollutionFrom, pollutionTo, approvalFrom, approvalTo, fitnessDocumentNumber, permitNumber, availableSeats, isDiesel, isPetrol, isGas, lastServicedDate, serviceDueDate, isActive, createUserID, createDate)
														VALUES (:|1, :|2, :|3, :|4, :|5, :|6, :|7, :|8, :|9, :|10, :|11, :|12, :|13, :|14, :|15, :|16, :|17, :|18, :|19, :|20, :|21, NOW());');

			$RSSave->Execute($this->VehicleTypeID, $this->VehicleName, $this->VehicleNumber, $this->RegistrationFrom, $this->RegistrationTo, $this->InsuranceFrom, $this->InsuranceTo, $this->PollutionFrom, $this->PollutionTo, $this->ApprovalFrom, $this->ApprovalTo, $this->FitnessDocumentNumber, $this->PermitNumber, $this->AvailableSeats,  $this->IsDiesel, $this->IsPetrol, $this->IsGas, $this->LastServicedDate, $this->ServiceDueDate, $this->IsActive, $this->CreateUserID);

			$this->VehicleID = $RSSave->LastID;	
		}
		else
		{	
			$RSUpdate = $this->DBObject->Prepare('UPDATE atm_vehicle
													SET	vehicleTypeID = :|1,
														vehicleName = :|2,													   
													   	vehicleNumber = :|3,
													   	registrationFrom = :|4,
													   	registrationTo = :|5,
													  	insuranceFrom = :|6,
													   	insuranceTo = :|7,
													  	 pollutionFrom = :|8,
													   	pollutionTo = :|9,
													   	approvalFrom = :|10,
													   	approvalTo = :|11,
													   	fitnessDocumentNumber = :|12,
													   	permitNumber = :|13,
													   	availableSeats = :|14,
													   	isDiesel = :|15,
													   	isPetrol = :|16,
													   	isGas = :|17,
													   	lastServicedDate = :|18,
													   	serviceDueDate = :|19,
														isActive = :|20
													WHERE vehicleID = :|21;');
													
			$RSUpdate->Execute($this->VehicleTypeID, $this->VehicleName, $this->VehicleNumber, $this->RegistrationFrom, $this->RegistrationTo, $this->InsuranceFrom, $this->InsuranceTo, $this->PollutionFrom, $this->PollutionTo, $this->ApprovalFrom, $this->ApprovalTo, $this->FitnessDocumentNumber, $this->PermitNumber, $this->AvailableSeats, $this->IsDiesel, $this->IsPetrol, $this->IsGas, $this->LastServicedDate, $this->ServiceDueDate, $this->IsActive, $this->VehicleID);
		}

		return true;
	}

	private function RemoveVehicle()
    {
        if(!isset($this->VehicleID)) 
        {
            throw new ApplicationARException('', APP_AR_ERROR_DELETE_WITHOUT_ID);
        }
        
        $RSDeleteVehicle = $this->DBObject->Prepare('DELETE FROM atm_vehicle WHERE vehicleID = :|1 LIMIT 1;');
        $RSDeleteVehicle->Execute($this->VehicleID); 

        return true;               
    }

	private function GetVehicleByID()
	{
		$RSVehicle = $this->DBObject->Prepare('SELECT * FROM atm_vehicle WHERE vehicleID = :|1 LIMIT 1;');
		$RSVehicle->Execute($this->VehicleID);
		
		$VehicleRow = $RSVehicle->FetchRow();
		
		$this->SetAttributesFromDB($VehicleRow);				
	}
	
	private function SetAttributesFromDB($VehicleRow)
	{
		$this->VehicleID = $VehicleRow->vehicleID;
		$this->VehicleTypeID = $VehicleRow->vehicleTypeID;

		$this->VehicleName = $VehicleRow->vehicleName;
		$this->VehicleNumber = $VehicleRow->vehicleNumber;

		$this->RegistrationFrom = $VehicleRow->registrationFrom;
		$this->RegistrationTo = $VehicleRow->registrationTo;
		$this->InsuranceFrom = $VehicleRow->insuranceFrom;
		$this->InsuranceTo = $VehicleRow->insuranceTo;

		$this->PollutionFrom = $VehicleRow->pollutionFrom;
		$this->PollutionTo = $VehicleRow->pollutionTo;
		$this->ApprovalFrom = $VehicleRow->approvalFrom;
		$this->ApprovalTo = $VehicleRow->approvalTo;

		$this->FitnessDocumentNumber = $VehicleRow->fitnessDocumentNumber;
		$this->PermitNumber = $VehicleRow->permitNumber;
		$this->AvailableSeats = $VehicleRow->availableSeats;

		$this->IsDiesel = $VehicleRow->isDiesel;
		$this->IsPetrol = $VehicleRow->isPetrol;
		$this->IsGas = $VehicleRow->isGas;

		$this->LastServicedDate = $VehicleRow->lastServicedDate	;
		$this->ServiceDueDate = $VehicleRow->serviceDueDate;

		$this->IsActive = $VehicleRow->isActive;
		$this->CreateUserID = $VehicleRow->createUserID;
		$this->CreateDate = $VehicleRow->createDate;

	}	
}
?>

<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.db_connect.php');
error_reporting(E_ALL);

class RouteVehicle
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	
	private $RouteVehicleID;
	private $RouteID;
	private $VehicleID;

	private $IsActive;
	private $CreateUserID;
	private $CreateDate;

	
	// PUBLIC METHODS START HERE	//
	public function __construct($RouteVehicleID = 0)
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;
		
		if($RouteVehicleID != 0)
		{
			$this->RouteVehicleID = $RouteVehicleID;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetRouteVehicleByID();
		}
		else
		{
			//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
			$this->RouteVehicleID = 0;
			$this->RouteID = 0;
			$this->VehicleID = 0;

			$this->IsActive = 0;
			$this->CreateUserID = 0;
			$this->CreateDate = '0000-00-00 00:00:00';
		}
	}
	
	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetRouteVehicleID()
	{
		return $this->RouteVehicleID;
	}
	
	public function GetRouteID()
	{
		return $this->RouteID;
	}
	public function SetRouteID($RouteID)
	{
		$this->RouteID = $RouteID;
	}

	public function GetVehicleID()
	{
		return $this->VehicleID;
	}
	public function SetVehicleID($VehicleID)
	{
		$this->VehicleID = $VehicleID;
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
            $this->RemoveRouteVehicle();
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

 //    public function RecordExists()
	// {
	// 	try
	// 	{
	// 		$QueryString = '';
			
	// 		if ($this->RouteVehicleID > 0)
	// 		{
	// 			$QueryString = ' AND routeID != ' . $this->DBObject->RealEscapeVariable($this->RouteVehicleID);
	// 		}

	// 		$RSTotal = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM atm_routes WHERE routeNumber = :|1' . $QueryString . ';');
	// 		$RSTotal->Execute($this->RouteID);
			
	// 		if ($RSTotal->FetchRow()->totalRecords > 0)
	// 		{
	// 			return true;
	// 		}
			
	// 		return false;
	// 	}
	// 	catch (ApplicationDBException $e)
	// 	{
	// 		error_log('DEBUG: ApplicationDBException at Route::RecordExists(). Stack Trace: ' . $e->getTraceAsString());
	// 		return true;
	// 	}
	// 	catch (Exception $e)
	// 	{
	// 		error_log('DEBUG: Exception at Route::RecordExists(). Stack Trace: ' . $e->getTraceAsString());
	// 		return true;
	// 	}
	// }

	// END OF PUBLIC METHODS	//
	
	// START OF STATIC METHODS	//
      
      static function GetActiveRoutevehicle()
	 {
		$ActiveRoutevehicle = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT * FROM atm_route_vehicle WHERE isActive = 1;');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $ActiveRoutevehicle;
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$ActiveRoutevehicle[$SearchRow->routeVehicleID] = $SearchRow->routeID;
			}

			return $ActiveRoutevehicle;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at RouteVehicle::GetActiveRoutevehicle(). Stack Trace: ' . $e->getTraceAsString());
			return $ActiveRoutevehicle;
		}
		catch (Exception $e) 
		{
			error_log('DEBUG: Exception at RouteVehicle::GetActiveRoutevehicle(). Stack Trace: ' . $e->getTraceAsString());
			return $ActiveRoutevehicle;
		}		
	}

	static function GetAllRouteVehicle()
	{
		$AllRouteVehicle = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT arv.*, u.userName AS createUserName FROM atm_route_vehicle arv
												INNER JOIN users u ON atr.createUserID = u.userID 
												ORDER BY atr.routeID;');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllRouteVehicle; 
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$AllRouteVehicle[$SearchRow->routeID]['RouteID'] = $SearchRow->routeNumber;
				$AllRouteVehicle[$SearchRow->routeID]['RouteName'] = $SearchRow->routeName;
				
				$AllRouteVehicle[$SearchRow->routeID]['IsActive'] = $SearchRow->isActive;

				$AllRouteVehicle[$SearchRow->routeID]['CreateUserID'] = $SearchRow->createUserID;
				$AllRouteVehicle[$SearchRow->routeID]['CreateUserName'] = $SearchRow->createUserName;
				$AllRouteVehicle[$SearchRow->routeID]['CreateDate'] = $SearchRow->createDate;
			}
			
			return $AllRouteVehicle;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at RouteVehicle::GetAllRouteVehicle(). Stack Trace: ' . $e->getTraceAsString());
			return $AllRouteVehicle;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at RouteVehicle::GetAllRouteVehicle(). Stack Trace: ' . $e->getTraceAsString());
			return $AllRouteVehicle;
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
	
	static function SearchRouteVehicles(&$TotalRecords = 0, $GetTotalsOnly = false, $Filters = array(), $Start = 0, $Limit = 100)
	{
		$AllRouteVehicles = array();

		try
		{
			$DBConnObject = new DBConnect();
			
			$Conditions = array();
			
			if (count($Filters) > 0)
			{				
               
                if (!empty($Filters['RouteID']))
				{
					$Conditions[] = 'arv.RouteID = '.$DBConnObject->RealEscapeVariable($Filters['RouteID']);
				}
				if (!empty($Filters['VehicleID']))
				{
					$Conditions[] = 'arv.VehicleID = '.$DBConnObject->RealEscapeVariable($Filters['VehicleID']);
				}
				
				if (!empty($Filters['ActiveStatus']))
				{
					if ($Filters['ActiveStatus'] == 1) //active records
					{
						$Conditions[] = 'arv.isActive = 1';
					}
					else if ($Filters['ActiveStatus'] == 2) //non active records
					{
						$Conditions[] = 'arv.isActive = 0';
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
													FROM atm_route_vehicle arv
													INNER JOIN atm_routes ar ON arv.routeID = ar.routeID
													INNER JOIN atm_vehicle av ON arv.vehicleID = av.vehicleID  
													INNER JOIN users u ON arv.createUserID = u.userID 
													'. $QueryString .';');
				$RSTotal->Execute();
				
				$TotalRecords = $RSTotal->FetchRow()->totalRecords;
				return;
			}
			
			$RSSearch = $DBConnObject->Prepare('SELECT arv.*, ar.routeNumber, ar.routeName,  av.vehicleName,  av.vehicleNumber,  u.userName AS createUserName 
												FROM atm_route_vehicle arv
												INNER JOIN atm_routes ar ON arv.routeID = ar.routeID
												INNER JOIN atm_vehicle av ON arv.vehicleID = av.vehicleID
												INNER JOIN users u ON arv.createUserID = u.userID 
												'. $QueryString .' 
												ORDER BY arv.routeVehicleID LIMIT ' . (int) $Start . ', ' . (int) $Limit . ';');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllRouteVehicles; 
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
                $AllRouteVehicles[$SearchRow->routeVehicleID]['RouteNumber'] = $SearchRow->routeNumber;
                $AllRouteVehicles[$SearchRow->routeVehicleID]['RouteName'] = $SearchRow->routeName;  
                $AllRouteVehicles[$SearchRow->routeVehicleID]['VehicleName'] = $SearchRow->vehicleName; 
                $AllRouteVehicles[$SearchRow->routeVehicleID]['VehicleNumber'] = $SearchRow->vehicleNumber; 

                $AllRouteVehicles[$SearchRow->routeVehicleID]['IsActive'] = $SearchRow->isActive; 
				$AllRouteVehicles[$SearchRow->routeVehicleID]['CreateUserID'] = $SearchRow->createUserID;
				$AllRouteVehicles[$SearchRow->routeVehicleID]['CreateUserName'] = $SearchRow->createUserName;
				$AllRouteVehicles[$SearchRow->routeVehicleID]['CreateDate'] = $SearchRow->createDate;
			}
			return $AllRouteVehicles;	
		}	
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at RouteVehicle::SearchRouteVehicles(). Stack Trace: ' . $e->getTraceAsString());
			return $AllRouteVehicles;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at RouteVehicle::SearchRouteVehicles(). Stack Trace: ' . $e->getTraceAsString());
			return $AllRouteVehicles;
		}
	}
	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//
	private function SaveDetails()
	{
		if ($this->RouteVehicleID == 0)
		{
			$RSSave = $this->DBObject->Prepare('INSERT INTO atm_route_vehicle (routeID, vehicleID, isActive, createUserID, createDate)
														VALUES (:|1, :|2, :|3, :|4, NOW());');
		
			$RSSave->Execute($this->RouteID, $this->VehicleID, $this->IsActive, $this->CreateUserID);
			
			$this->RouteVehicleID = $RSSave->LastID;
		}
		{
			$RSUpdate = $this->DBObject->Prepare('UPDATE atm_route_vehicle
													SET	routeID = :|1,
													    vehicleID   = :|2,
													    isActive    = :|3
													WHERE routeVehicleID   = :|4;');
													
			$RSUpdate->Execute($this->RouteID, $this->VehicleID, $this->IsActive, $this->RouteVehicleID);
		}
		
		return true;
	}
	
	private function RemoveRouteVehicle()
    {
        if(!isset($this->RouteVehicleID)) 
        {
            throw new ApplicationARException('', APP_AR_ERROR_DELETE_WITHOUT_ID);
        }
        
        $RSDeleteRouteVehicle = $this->DBObject->Prepare('DELETE FROM atm_route_vehicle WHERE routeVehicleID = :|1 LIMIT 1;');
        $RSDeleteRouteVehicle->Execute($this->RouteVehicleID);  

        return true;              
    }
	
	private function GetRouteVehicleByID()
	{
		$RSRouteVehicle = $this->DBObject->Prepare('SELECT * FROM atm_route_vehicle WHERE routeVehicleID = :|1;');
		$RSRouteVehicle->Execute($this->RouteVehicleID);
		
		$RouteVehicleRow = $RSRouteVehicle->FetchRow();
		
		$this->SetAttributesFromDB($RouteVehicleRow);				
	}
	
	private function SetAttributesFromDB($RouteVehicleRow)
	{
		$this->RouteVehicleID = $RouteVehicleRow->routeVehicleID;
		$this->RouteID = $RouteVehicleRow->routeID;
		$this->VehicleID = $RouteVehicleRow->vehicleID;
		$this->IsActive = $RouteVehicleRow->isActive;
		$this->CreateUserID = $RouteVehicleRow->createUserID;
		$this->CreateDate = $RouteVehicleRow->createDate;
	}	
}
?>
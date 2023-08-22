<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.db_connect.php');
error_reporting(E_ALL);

class Route
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	
	private $RouteID;
	private $RouteNumber;
	private $RouteName;

	private $IsActive;
	private $CreateUserID;
	private $CreateDate;

	
	// PUBLIC METHODS START HERE	//
	public function __construct($RouteID = 0)
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;
		
		if($RouteID != 0)
		{
			$this->RouteID = $RouteID;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetRouteByID();
		}
		else
		{
			//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
			$this->RouteID = 0;
			$this->RouteNumber = '';
			$this->RouteName = '';

			$this->IsActive = 0;
			$this->CreateUserID = 0;
			$this->CreateDate = '0000-00-00 00:00:00';
		}
	}
	
	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetRouteID()
	{
		return $this->RouteID;
	}
	
	public function GetRouteNumber()
	{
		return $this->RouteNumber;
	}
	public function SetRouteNumber($RouteNumber)
	{
		$this->RouteNumber = $RouteNumber;
	}

	public function GetRouteName()
	{
		return $this->RouteName;
	}
	public function SetRouteName($RouteName)
	{
		$this->RouteName = $RouteName;
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
            $this->RemoveRoute();
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

 public function RecordExists()
	{
		try
		{
			$QueryString = '';
			
			if ($this->RouteID > 0)
			{
				$QueryString = ' AND routeID != ' . $this->DBObject->RealEscapeVariable($this->RouteID);
			}

			$RSTotal = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM atm_routes WHERE routeName = :|1' . $QueryString . ';');
			$RSTotal->Execute($this->RouteName);
			
			if ($RSTotal->FetchRow()->totalRecords > 0)
			{
				return true;
			}
			
			return false;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at Route::RecordExists(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at Route::RecordExists(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
	}

	// END OF PUBLIC METHODS	//
	
	// START OF STATIC METHODS	//
      
      static function GetActiveRoutes()
	 {
		$ActiveRoute = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT * FROM atm_routes WHERE isActive = 1;');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $ActiveRoute;
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$ActiveRoute[$SearchRow->routeID] = $SearchRow->routeNumber;
				// $ActiveRoute[$SearchRow->routeID] = $SearchRow->routeName;

			}

			return $ActiveRoute;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at Route::GetActiveRoute(). Stack Trace: ' . $e->getTraceAsString());
			return $ActiveRoute;
		}
		catch (Exception $e) 
		{
			error_log('DEBUG: Exception at Route::GetActiveRoute(). Stack Trace: ' . $e->getTraceAsString());
			return $ActiveRoute;
		}		
	}

	static function GetAllRoutes()
	{
		$AllRoute = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT atr.*, u.userName AS createUserName FROM atm_routes atr
												INNER JOIN users u ON atr.createUserID = u.userID 
												ORDER BY atr.routeID;');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllRoute; 
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$AllRoute[$SearchRow->routeID]['RouteNumber'] = $SearchRow->routeNumber;
				$AllRoute[$SearchRow->routeID]['RouteName'] = $SearchRow->routeName;
				
				$AllRoute[$SearchRow->routeID]['IsActive'] = $SearchRow->isActive;

				$AllRoute[$SearchRow->routeID]['CreateUserID'] = $SearchRow->createUserID;
				$AllRoute[$SearchRow->routeID]['CreateUserName'] = $SearchRow->createUserName;
				$AllRoute[$SearchRow->routeID]['CreateDate'] = $SearchRow->createDate;
			}
			
			return $AllRoute;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at Route::GetAllRoute(). Stack Trace: ' . $e->getTraceAsString());
			return $AllRoute;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at Route::GetAllRoute(). Stack Trace: ' . $e->getTraceAsString());
			return $AllRoute;
		}
	}
	
	static function GetAllVehicleRoutesForGraphs()
    {
		$AllVehicleRoutesForGraph = array();
		
		try
        {	
        	$DBConnObject = new DBConnect();

        	$RSSearch = $DBConnObject->Prepare('SELECT count(av.vehicleID) AS totalRecords, av.vehicleName FROM atm_vehicle av
        										INNER JOIN atm_routes ar ON ar.routeID = av.vehicleTypeID
        										GROUP By avt.vehicleTypeID;');
			$RSSearch->Execute();

			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllVehicleRoutesForGraph;
			}

			while($SearchRow = $RSSearch->FetchRow())
			{
				$AllVehicleRoutesForGraph[$SearchRow->vehicleName] = $SearchRow->totalRecords;
			}

			return $AllVehicleRoutesForGraph;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException: Route::GetAllVehiclesForGraphs(). Stack Trace: ' . $e->getTraceAsString());
            return $AllVehicleRoutesForGraph;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception: Route::GetAllVehiclesForGraphs(). Stack Trace: ' . $e->getTraceAsString());
            return $AllVehicleRoutesForGraph;
        }
    }
	
	// END OF STATIC METHODS	//
	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//
	private function SaveDetails()
	{
		if ($this->RouteID == 0)
		{
			$RSSave = $this->DBObject->Prepare('INSERT INTO atm_routes (routeNumber, routeName, isActive, createUserID, createDate)
														VALUES (:|1, :|2, :|3, :|4, NOW());');
		
			$RSSave->Execute($this->RouteNumber, $this->RouteName, $this->IsActive, $this->CreateUserID);
			
			$this->RouteID = $RSSave->LastID;
		}
		{
			$RSUpdate = $this->DBObject->Prepare('UPDATE atm_routes
													SET	routeNumber = :|1,
													    routeName   = :|2,
													    isActive    = :|3
													WHERE routeID   = :|4;');
													
			$RSUpdate->Execute($this->RouteNumber, $this->RouteName, $this->IsActive, $this->RouteID);
		}
		
		return true;
	}
	
	private function RemoveRoute()
    {
        if(!isset($this->RouteID)) 
        {
            throw new ApplicationARException('', APP_AR_ERROR_DELETE_WITHOUT_ID);
        }
        
        $RSDeleteRoute = $this->DBObject->Prepare('DELETE FROM atm_routes WHERE routeID = :|1 LIMIT 1;');
        $RSDeleteRoute->Execute($this->RouteID);  

        return true;              
    }
	
	private function GetRouteByID()
	{
		$RSRoute = $this->DBObject->Prepare('SELECT * FROM atm_routes WHERE routeID = :|1;');
		$RSRoute->Execute($this->RouteID);
		
		$RouteRow = $RSRoute->FetchRow();
		
		$this->SetAttributesFromDB($RouteRow);				
	}
	
	private function SetAttributesFromDB($RouteRow)
	{
		$this->RouteID = $RouteRow->routeID;
		$this->RouteNumber = $RouteRow->routeNumber;
		$this->RouteName = $RouteRow->routeName;

		$this->IsActive = $RouteRow->isActive;
		$this->CreateUserID = $RouteRow->createUserID;
		$this->CreateDate = $RouteRow->createDate;
	}	
}
?>
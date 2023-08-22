<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.db_connect.php');
error_reporting(E_ALL);

class AreaWiseFee
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //

	private $AreaWiseFeeID;
	private $RouteID;
	private $AreaID;
	private $AcademicYearID;
	private $Amount;

	private $IsActive;
	private $CreateUserID;
	private $CreateDate;
	// PUBLIC METHODS START HERE	//
	public function __construct($AreaWiseFeeID = 0)
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;
		
		if($AreaWiseFeeID != 0)
		{
			$this->AreaWiseFeeID = $AreaWiseFeeID;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetAreaWiseFeeByID();
		}
		else
		{
			//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
			$this->AreaWiseFeeID = 0;
			$this->RouteID = 0;
			$this->AreaID = 0;
			$this->AcademicYearID = 0;
			$this->Amount = 0;

			$this->IsActive = 0;
			$this->CreateUserID = 0;
			$this->CreateDate = '0000-00-00 00:00:00';
		}
	}
	
	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetAreaWiseFeeID()
	{
		return $this->AreaWiseFeeID;
	}
	
	public function GetRouteID()
	{
		return $this->RouteID;
	}
	public function SetRouteID($RouteID)
	{
		$this->RouteID = $RouteID;
	}

	public function GetAreaID()
	{
       return $this->AreaID;
	}
	public function SetAreaID($AreaID)
	{
		$this->AreaID = $AreaID;
	}

	public function GetAcademicYearID()
	{
       return $this->AcademicYearID;
	}
	public function SetAcademicYearID($AcademicYearID)
	{
		$this->AcademicYearID = $AcademicYearID;
	}

	public function GetAmount()
	{
       return $this->Amount;
	}
	public function SetAmount($Amount)
	{
		$this->Amount = $Amount;
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
            $this->RemoveAreaWiseFee();
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
			
			if ($this->AreaWiseFeeID > 0)
			{
				$QueryString = ' AND areaWiseFeeID != ' . $this->DBObject->RealEscapeVariable($this->AreaWiseFeeID);
			}

			$RSTotal = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM atm_area_wise_fee WHERE routeID = :|1 AND areaID = :|2 AND academicYearID = :|3 ' . $QueryString . ';');
			$RSTotal->Execute($this->RouteID, $this->AreaID, $this->AcademicYearID);
			
			if ($RSTotal->FetchRow()->totalRecords > 0)
			{
				return true;
			}
			
			return false;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at AreaWiseFee::RecordExists(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at AreaWiseFee::RecordExists(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
	}

	// END OF PUBLIC METHODS	//
	
	// START OF STATIC METHODS	//
	 static function SearchAreaWiseFee(&$TotalRecords = 0, $GetTotalsOnly = false, $Filters = array(), $Start = 0, $Limit = 100)
	{
		$AllAreaWiseFee = array();

		try
		{
			$DBConnObject = new DBConnect();
			
			$Conditions = array();
			
			if (count($Filters) > 0)
			{	
				if (!empty($Filters['RouteID']))
				{
					$Conditions[] = 'aawf.routeID = '. $DBConnObject->RealEscapeVariable($Filters['RouteID']);
				}	

				if (!empty($Filters['AreaID']))
				{
					$Conditions[] = 'aawf.areaID = '. $DBConnObject->RealEscapeVariable($Filters['AreaID']);
				}	

				if (!empty($Filters['AcademicYearID']))
				{
					$Conditions[] = 'aawf.academicYearID = '. $DBConnObject->RealEscapeVariable($Filters['AcademicYearID']);
				}	

				if (!empty($Filters['ActiveStatus']))
				{
					if ($Filters['ActiveStatus'] == 1) //active records
					{
						$Conditions[] = 'aawf.isActive = 1';
					}
					else if ($Filters['ActiveStatus'] == 2) //non active records
					{
						$Conditions[] = 'aawf.isActive = 0';
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
													FROM atm_area_wise_fee aawf
													INNER JOIN atm_routes ar ON ar.routeID = aawf.routeID 
													INNER JOIN atm_area_master aam ON aam.areaID = aawf.areaID
													INNER JOIN users u ON aawf.createUserID = u.userID 
													'. $QueryString .';');
				$RSTotal->Execute();
				
				$TotalRecords = $RSTotal->FetchRow()->totalRecords;
				return;
			}
			
			$RSSearch = $DBConnObject->Prepare('SELECT aawf.*, ar.routeNumber, ar.routeName, aam.areaName, aawf.amount, u.userName AS createUserName
												FROM atm_area_wise_fee aawf
												INNER JOIN atm_routes ar ON ar.routeID = aawf.routeID
												INNER JOIN atm_area_master aam ON aam.areaID = aawf.areaID
												INNER JOIN users u ON aawf.createUserID = u.userID 
												'. $QueryString .' 
												ORDER BY ar.routeName LIMIT ' . (int) $Start . ', ' . (int) $Limit . ';');
			$RSSearch->Execute();

			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllAreaWiseFee; 
			}

			while($SearchRow = $RSSearch->FetchRow())
			{   
			    $AllAreaWiseFee[$SearchRow->areaWiseFeeID]['RouteNumber'] = $SearchRow->routeNumber;
				$AllAreaWiseFee[$SearchRow->areaWiseFeeID]['RouteName'] = $SearchRow->routeName;
				$AllAreaWiseFee[$SearchRow->areaWiseFeeID]['AreaName'] = $SearchRow->areaName;
				$AllAreaWiseFee[$SearchRow->areaWiseFeeID]['Amount'] = $SearchRow->amount;

				$AllAreaWiseFee[$SearchRow->areaWiseFeeID]['IsActive'] = $SearchRow->isActive;
				$AllAreaWiseFee[$SearchRow->areaWiseFeeID]['CreateUserName'] = $SearchRow->createUserName;
				$AllAreaWiseFee[$SearchRow->areaWiseFeeID]['CreateDate'] = $SearchRow->createDate;
			}
			
			return $AllAreaWiseFee;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at AreaWiseFee::SearchAreaWiseFee(). Stack Trace: ' . $e->getTraceAsString());
			return $AllAreaWiseFee;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at AreaWiseFee::SearchAreaWiseFee(). Stack Trace: ' . $e->getTraceAsString());
			return $AllAreaWiseFee;
		}
	}
	
	static function GetRouteAreas($RouteID)
    {
        $RouteAreaList = array();

        try
        {
            $DBConnObject = new DBConnect();

			$RSSearch = $DBConnObject->Prepare('SELECT aawf.areaWiseFeeID, aam.areaName
												FROM atm_area_wise_fee aawf					     
												INNER JOIN atm_area_master aam ON aam.areaID = aawf.areaID
												WHERE aawf.routeID = :|1 ;');	

            $RSSearch->Execute($RouteID);

            if ($RSSearch->Result->num_rows <= 0)
            {	
                return $RouteAreaList;
            }
            
            while($SearchRow = $RSSearch->FetchRow())
            {

            	$RouteAreaList[$SearchRow->areaWiseFeeID] = $SearchRow->areaName;
            }

            return $RouteAreaList;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at AreaWiseFee::GetRouteAreas(). Stack Trace: '.$e->getTraceAsString());
            return $RouteAreaList;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception at AreaWiseFee::GetRouteAreas(). Stack Trace: '.$e->getTraceAsString());
            return $RouteAreaList;
        }
    }
	
	static function GetRouteAreasByAcademicYear($RouteID, $AcademicYearID)
    {
        $RouteAreaList = array();

        try
        {
            $DBConnObject = new DBConnect();

			$RSSearch = $DBConnObject->Prepare('SELECT aawf.areaWiseFeeID, aam.areaName
												FROM atm_area_wise_fee aawf					     
												INNER JOIN atm_area_master aam ON aam.areaID = aawf.areaID
												WHERE aawf.routeID = :|1 AND aawf.academicYearID = :|2;');	

            $RSSearch->Execute($RouteID, $AcademicYearID);

            if ($RSSearch->Result->num_rows <= 0)
            {	
                return $RouteAreaList;
            }
            
            while($SearchRow = $RSSearch->FetchRow())
            {

            	$RouteAreaList[$SearchRow->areaWiseFeeID] = $SearchRow->areaName;
            }

            return $RouteAreaList;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at AreaWiseFee::GetRouteAreasByAcademicYear(). Stack Trace: '.$e->getTraceAsString());
            return $RouteAreaList;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception at AreaWiseFee::GetRouteAreasByAcademicYear(). Stack Trace: '.$e->getTraceAsString());
            return $RouteAreaList;
        }
    }
	
	static function GetActiveAreaWiseFee()
	{
		$ActiveAreaWiseFee = array();
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT * FROM atm_area_wise_fee WHERE isActive = 1;');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $ActiveAreaWiseFee;
			}
			while($SearchRow = $RSSearch->FetchRow())
			{
				$ActiveAreaWiseFee[$SearchRow->areaWiseFeeID] = $SearchRow->routeID;
			}

			return $ActiveAreaWiseFee;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at AreaWiseFee::GetActiveAreaWiseFee(). Stack Trace: ' . $e->getTraceAsString());
			return $ActiveAreaWiseFee;
		}
		catch (Exception $e) 
		{
			error_log('DEBUG: Exception at AreaWiseFee::GetActiveAreaWiseFee(). Stack Trace: ' . $e->getTraceAsString());
			return $ActiveAreaWiseFee;
		}		
	}
     
	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//
	private function SaveDetails()
	{
		if ($this->AreaWiseFeeID == 0)
		{
			$RSSave = $this->DBObject->Prepare('INSERT INTO atm_area_wise_fee (routeID, areaID, academicYearID, amount, isActive, createUserID, createDate)
														VALUES (:|1, :|2, :|3, :|4, :|5, :|6, NOW());');
		
			$RSSave->Execute($this->RouteID, $this->AreaID, $this->AcademicYearID, $this->Amount, $this->IsActive, $this->CreateUserID);
			
			$this->AreaWiseFeeID = $RSSave->LastID;
		}
		else
		{
			$RSUpdate = $this->DBObject->Prepare('UPDATE atm_area_wise_fee
													SET	routeID = :|1,
													    areaID = :|2,
													    academicYearID = :|3,
													    amount = :|4,
														isActive = :|5
													WHERE areaWiseFeeID = :|6;');
													
			$RSUpdate->Execute($this->RouteID, $this->AreaID, $this->AcademicYearID, $this->Amount, $this->IsActive, $this->AreaWiseFeeID);
		}
		
		return true;
	}

	private function RemoveAreaWiseFee()
    {
        if(!isset($this->AreaWiseFeeID)) 
        {
            throw new ApplicationARException('', APP_AR_ERROR_DELETE_WITHOUT_ID);
        }
        
        $RSDeleteAreaWiseFee = $this->DBObject->Prepare('DELETE FROM atm_area_wise_fee WHERE areaWiseFeeID = :|1 LIMIT 1;');
        $RSDeleteAreaWiseFee->Execute($this->AreaWiseFeeID); 

        return true;               
    }

	
	private function GetAreaWiseFeeByID()
	{
		$RSAreaWiseFee = $this->DBObject->Prepare('SELECT * FROM atm_area_wise_fee WHERE areaWiseFeeID = :|1;');
		$RSAreaWiseFee->Execute($this->AreaWiseFeeID);
		
		$AreaWiseFeeRow = $RSAreaWiseFee->FetchRow();
		
		$this->SetAttributesFromDB($AreaWiseFeeRow);				
	}
	
	private function SetAttributesFromDB($AreaWiseFeeRow)
	{
		$this->AreaWiseFeeID = $AreaWiseFeeRow->areaWiseFeeID;
		$this->RouteID = $AreaWiseFeeRow->routeID;
		$this->AreaID = $AreaWiseFeeRow->areaID;
		$this->AcademicYearID = $AreaWiseFeeRow->academicYearID;

		$this->Amount = $AreaWiseFeeRow->amount;
		$this->IsActive = $AreaWiseFeeRow->isActive;
		$this->CreateUserID = $AreaWiseFeeRow->createUserID;
		$this->CreateDate = $AreaWiseFeeRow->createDate;

	}	
}
?>
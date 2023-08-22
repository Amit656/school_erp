<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.db_connect.php');
error_reporting(E_ALL);

class EventGallery
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	
	private $EventGalleryID;
	private $Name;
	private $Description;

	private $IsActive;
	private $CreateUserID;
	private $CreateDate;
	
	// PUBLIC METHODS START HERE	//
	public function __construct($EventGalleryID = 0)
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;
		
		if($EventGalleryID != 0)
		{
			$this->EventGalleryID = $EventGalleryID;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetEventGalleryByID();
		}
		else
		{
			//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
			$this->EventGalleryID = 0;
			$this->Name = '';
			$this->Description = '';

			$this->IsActive = 0;
			$this->CreateUserID = 0;
			$this->CreateDate = '0000-00-00 00:00:00';
		}
	}
	
	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetEventGalleryID()
	{
		return $this->EventGalleryID;
	}
	
	public function GetName()
	{
		return $this->Name;
	}
	public function SetName($Name)
	{
		$this->Name = $Name;
	}
	
	public function GetDescription()
	{
		return $this->Description;
	}
	public function SetDescription($Description)
	{
		$this->Description = $Description;
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
			return $this->RemoveEventGallery();
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
	public function CheckDependencies()
	{
		try
		{
			$RSTotal = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM asa_event_gallery_images WHERE eventGalleryID = :|1;');
			$RSTotal->Execute($this->EventGalleryID);
	
			if ($RSTotal->FetchRow()->totalRecords > 0) 
			{
				return true;
			}
			
			return false;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException: EventGallery::CheckDependencies(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception: EventGallery::CheckDependencies(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
	}

	public function RecordExists()
	{
		try
		{
			$QueryString = '';

			if ($this->EventGalleryID > 0)
			{
				$QueryString = ' AND eventGalleryID != ' . $this->DBObject->RealEscapeVariable($this->EventGalleryID);
			}

			$RSTotal = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM asa_event_gallery WHERE name = :|1' . $QueryString . ';');
			$RSTotal->Execute($this->Name);
			
			if ($RSTotal->FetchRow()->totalRecords > 0)
			{
				return true;
			}
			
			return false;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at EventGallery::RecordExists(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at EventGallery::RecordExists(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
	}
	
	public function GetEventGalleryImages()
	{
		$AllEventImages = array();
		
		try
		{
			$RSSearch = $this->DBObject->Prepare('SELECT aegi.*, u.userName 
													FROM asa_event_gallery_images aegi 
													INNER JOIN users u ON aegi.createUserID = u.userID 
													WHERE aegi.eventGalleryID = :|1 
													ORDER BY aegi.createDate DESC;');
			
			$RSSearch->Execute($this->EventGalleryID);
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllEventImages;
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$AllEventImages[$SearchRow->eventGalleryImageID]['ImageName'] = $SearchRow->imageName;
				$AllEventImages[$SearchRow->eventGalleryImageID]['Description'] = $SearchRow->description;

				$AllEventImages[$SearchRow->eventGalleryImageID]['CreateUserID'] = $SearchRow->createUserID;
				$AllEventImages[$SearchRow->eventGalleryImageID]['UserName'] = $SearchRow->userName;

				$AllEventImages[$SearchRow->eventGalleryImageID]['CreateDate'] = $SearchRow->createDate;
			}
			
			return $AllEventImages;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at EventGallery::GetEventGalleryImages(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at EventGallery::GetEventGalleryImages(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
	}

	static function Search(&$TotalRecords = 0, $GetTotalsOnly = false, $Filters = array(), $Start = 0, $Limit = 100)
	{
		$AllEventGallery = array();

		try
		{
			$DBConnObject = new DBConnect();
			
			$Conditions = array();
			
			if (count($Filters) > 0)
			{
				$Conditions[] = 'aeg.isActive = ' . $DBConnObject->RealEscapeVariable($Filters['IsActive']);
			}
			
			$QueryString = '';
			if (count($Conditions) > 0)
			{
				$QueryString = ' WHERE ' . implode(" AND ", $Conditions);
			}
			
			if ($GetTotalsOnly)
			{
				$RSTotal = $DBConnObject->Prepare('SELECT COUNT(*) AS totalRecords 
													FROM asa_event_gallery aeg
													INNER JOIN users u ON aeg.createUserID = u.userID 
													' . $QueryString . ';');
				$RSTotal->Execute();
				
				$TotalRecords = $RSTotal->FetchRow()->totalRecords;
				return;
			}
			
			$RSSearch = $DBConnObject->Prepare('SELECT aeg.*, u.userName 
													FROM asa_event_gallery aeg 
													INNER JOIN users u ON aeg.createUserID = u.userID 
													' . $QueryString . ' 
													ORDER BY aeg.createDate LIMIT '.(int) $Start.', '.(int) $Limit.';');
			$RSSearch->Execute();
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$AllEventGallery[$SearchRow->eventGalleryID]['Name'] = $SearchRow->name;
				$AllEventGallery[$SearchRow->eventGalleryID]['Description'] = $SearchRow->description;

				$AllEventGallery[$SearchRow->eventGalleryID]['IsActive'] = $SearchRow->isActive;

				$AllEventGallery[$SearchRow->eventGalleryID]['CreateUserID'] = $SearchRow->createUserID;
				$AllEventGallery[$SearchRow->eventGalleryID]['CreateUserName'] = $SearchRow->userName;

				$AllEventGallery[$SearchRow->eventGalleryID]['CreateDate'] = $SearchRow->createDate;
			}
			
			return $AllEventGallery;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at EventGallery::Search(). Stack Trace: '.$e->getTraceAsString());
			return $AllEventGallery;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at EventGallery::Search(). Stack Trace: '.$e->getTraceAsString());
			return $AllEventGallery;
		}
	}

	static function GetActiveEventGallery($GetOnlyName = true)
	{
		$AllEventGallery = array();

		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT aeg.*, 
												(
													SELECT imageName FROM asa_event_gallery_images aegi WHERE aegi.eventGalleryID = aeg.eventGalleryID LIMIT 1
												) AS imageName, (SELECT COUNT(*) FROM asa_event_gallery_images WHERE eventGalleryID = aeg.eventGalleryID) AS totalRecords, u.userName 
												FROM asa_event_gallery aeg 
												INNER JOIN users u ON aeg.createUserID = u.userID 
												WHERE aeg.isActive = 1 ORDER BY aeg.createDate;');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllEventGallery;
			}
			
			while ($SearchRow = $RSSearch->FetchRow())
			{
				if ($GetOnlyName)
				{
					$AllEventGallery[$SearchRow->eventGalleryID] = $SearchRow->name;
					continue;
				}
				
				$AllEventGallery[$SearchRow->eventGalleryID]['Name'] = $SearchRow->name;
				$AllEventGallery[$SearchRow->eventGalleryID]['Description'] = $SearchRow->description;

				$AllEventGallery[$SearchRow->eventGalleryID]['IsActive'] = $SearchRow->isActive;

				$AllEventGallery[$SearchRow->eventGalleryID]['CreateUserID'] = $SearchRow->createUserID;
				$AllEventGallery[$SearchRow->eventGalleryID]['CreateUserName'] = $SearchRow->userName;

				$AllEventGallery[$SearchRow->eventGalleryID]['ImageName'] = $SearchRow->imageName;

				$AllEventGallery[$SearchRow->eventGalleryID]['CreateDate'] = $SearchRow->createDate;
				
				$AllEventGallery[$SearchRow->eventGalleryID]['TotalImages'] = $SearchRow->totalRecords;
			}
			
			return $AllEventGallery;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at EventGallery::GetActiveEventGallery(). Stack Trace: '.$e->getTraceAsString());
			return $AllEventGallery;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at EventGallery::GetActiveEventGallery(). Stack Trace: '.$e->getTraceAsString());
			return $AllEventGallery;
		}
	}
	
	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//
	private function SaveDetails()
	{
		if ($this->EventGalleryID == 0)
		{
			$RSSave = $this->DBObject->Prepare('INSERT INTO asa_event_gallery (name, description, isActive, createUserID, createDate)
														VALUES (:|1, :|2, :|3, :|4, NOW());');
		
			$RSSave->Execute($this->Name, $this->Description, $this->IsActive, $this->CreateUserID);
			
			$this->EventGalleryID = $RSSave->LastID;
		}
		else
		{
			$RSUpdate = $this->DBObject->Prepare('UPDATE asa_event_gallery
													SET	name = :|1,
														description = :|2,
														isActive = :|3
													WHERE eventGalleryID = :|4 LIMIT 1;');
													
			$RSUpdate->Execute($this->Name, $this->Description, $this->IsActive, $this->EventGalleryID);
		}
		
		return true;
	}

	private function RemoveEventGallery()
    {
        if(!isset($this->EventGalleryID)) 
        {
            throw new ApplicationARException('', APP_AR_ERROR_DELETE_WITHOUT_ID);
        }
        
        $RSDeleteEvent = $this->DBObject->Prepare('DELETE FROM asa_event_gallery WHERE eventGalleryID = :|1 LIMIT 1;');
        $RSDeleteEvent->Execute($this->EventGalleryID);
           
        return true;
    }
	
	private function GetEventGalleryByID()
	{
		$RSEventGallery = $this->DBObject->Prepare('SELECT * FROM asa_event_gallery WHERE eventGalleryID = :|1 LIMIT 1;');
		$RSEventGallery->Execute($this->EventGalleryID);
		
		$EventGalleryRow = $RSEventGallery->FetchRow();
		
		$this->SetAttributesFromDB($EventGalleryRow);				
	}
	
	private function SetAttributesFromDB($EventGalleryRow)
	{
		$this->EventGalleryID = $EventGalleryRow->eventGalleryID;
		$this->Name = $EventGalleryRow->name;
		$this->Description = $EventGalleryRow->description;

		$this->IsActive = $EventGalleryRow->isActive;
		$this->CreateUserID = $EventGalleryRow->createUserID;
		$this->CreateDate = $EventGalleryRow->createDate;
	}	
}
?>
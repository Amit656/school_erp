<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.db_connect.php');
error_reporting(E_ALL);

class EventGalleryImage
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	
	private $eventGalleryImageID;
	private $EventGalleryID;
	private $ImageName;
	private $Description;

	private $CreateUserID;
	private $CreateDate;
	
	// PUBLIC METHODS START HERE	//
	public function __construct($EventGalleryImageID = 0)
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;
		
		if($EventGalleryImageID != 0)
		{
			$this->EventGalleryImageID = $EventGalleryImageID;

			// SET THE VALUES FROM THE DATABASE.
			$this->GetEventGalleryImageByID();
		}
		else
		{
			//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
			$this->EventGalleryImageID = 0;
			$this->EventGalleryID = 0;
			$this->ImageName = '';
			$this->Description = '';

			$this->CreateUserID = 0;
			$this->CreateDate = '0000-00-00 00:00:00';
		}
	}
	
	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetEventGalleryImageID()
	{
		return $this->EventGalleryImageID;
	}
	
	public function GetEventGalleryID()
	{
		return $this->EventGalleryID;
	}
	public function SetEventGalleryID($EventGalleryID)
	{
		$this->EventGalleryID = $EventGalleryID;
	}
	
	public function GetImageName()
	{
		return $this->ImageName;
	}
	public function SetImageName($ImageName)
	{
		$this->ImageName = $ImageName;
	}
	
	public function GetDescription()
	{
		return $this->Description;
	}
	public function SetDescription($Description)
	{
		$this->Description = $Description;
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
			return $this->RemoveEventGalleryImage();
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
	public function RecordExists()
	{
		try
		{
			$QueryString = '';

			if ($this->EventGalleryImageID > 0)
			{
				$QueryString = ' AND eventGalleryImageID != ' . $this->DBObject->RealEscapeVariable($this->EventGalleryImageID);
			}

			$RSTotal = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM asa_event_gallery_images WHERE eventGalleryID = :|1 AND  imageName = :|2' . $QueryString . ';');
			$RSTotal->Execute($this->EventGalleryID, $this->ImageName);
			
			if ($RSTotal->FetchRow()->totalRecords > 0)
			{
				return true;
			}
			
			return false;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at EventGalleryImage::RecordExists(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at EventGalleryImage::RecordExists(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
	}
	
	static function Search(&$TotalRecords = 0, $GetTotalsOnly = false, $Filters = array(), $Start = 0, $Limit = 100)
	{
		$AllEventImages = array();

		try
		{
			$DBConnObject = new DBConnect();
			
			$Conditions = array();
			
			$Conditions[] = 'aegi.eventGalleryID = ' . $DBConnObject->RealEscapeVariable($Filters['EventGalleryID']);
			
			$QueryString = '';
			
			$QueryString = ' WHERE ' . implode(" AND ", $Conditions);
			
			if ($GetTotalsOnly)
			{
				$RSTotal = $DBConnObject->Prepare('SELECT COUNT(*) AS totalRecords 
													FROM asa_event_gallery_images aegi
													INNER JOIN users u ON aegi.createUserID = u.userID 
													' . $QueryString . ';');
				$RSTotal->Execute();
				
				$TotalRecords = $RSTotal->FetchRow()->totalRecords;
				return;
			}
			
			$RSSearch = $DBConnObject->Prepare('SELECT aegi.*, u.userName 
													FROM asa_event_gallery_images aegi 
													INNER JOIN users u ON aegi.createUserID = u.userID 
													' . $QueryString . ' 
													ORDER BY aegi.createDate LIMIT '.(int) $Start.', '.(int) $Limit.';');
			$RSSearch->Execute();
			
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
			error_log('DEBUG: ApplicationDBException at EventGalleryImage::Search(). Stack Trace: '.$e->getTraceAsString());
			return $AllEventImages;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at EventGalleryImage::Search(). Stack Trace: '.$e->getTraceAsString());
			return $AllEventImages;
		}
	}

	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//
	private function SaveDetails()
	{
		if ($this->EventGalleryImageID == 0)
		{
			$RSSave = $this->DBObject->Prepare('INSERT INTO asa_event_gallery_images (eventGalleryID, imageName, description, createUserID, createDate)
														VALUES (:|1, :|2, :|3, :|4, NOW());');
		
			$RSSave->Execute($this->EventGalleryID, $this->ImageName, $this->Description, $this->CreateUserID);
			
			$this->EventGalleryImageID = $RSSave->LastID;
		}
		else
		{
			$RSUpdate = $this->DBObject->Prepare('UPDATE asa_event_gallery_images
													SET	imageName = :|1,
														eventGalleryID = :|2,
														description = :|3
													WHERE eventGalleryImageID = :|4 LIMIT 1;');
													
			$RSUpdate->Execute($this->ImageName, $this->EventGalleryID, $this->Description, $this->EventGalleryImageID);
		}
		
		return true;
	}

	private function RemoveEventGalleryImage()
    {
        if(!isset($this->EventGalleryImageID)) 
        {
            throw new ApplicationARException('', APP_AR_ERROR_DELETE_WITHOUT_ID);
        }

        var_dump($this->EventGalleryImageID);
        $RSDeleteEvent = $this->DBObject->Prepare('DELETE FROM asa_event_gallery_images WHERE eventGalleryImageID = :|1 LIMIT 1;');
        $RSDeleteEvent->Execute($this->EventGalleryImageID);
           
        return true;
    }
	
	private function GetEventGalleryImageByID()
	{
		$RSEventGalleryImage = $this->DBObject->Prepare('SELECT * FROM asa_event_gallery_images WHERE eventGalleryImageID = :|1 LIMIT 1;');
		$RSEventGalleryImage->Execute($this->EventGalleryImageID);
		
		var_dump($this->EventGalleryImageID);
		$EventGalleryImageRow = $RSEventGalleryImage->FetchRow();
		
		$this->SetAttributesFromDB($EventGalleryImageRow);				
	}
	
	private function SetAttributesFromDB($EventGalleryImageRow)
	{
		$this->EventGalleryImageID = $EventGalleryImageRow->eventGalleryImageID;
		$this->EventGalleryID = $EventGalleryImageRow->eventGalleryID;
		$this->ImageName = $EventGalleryImageRow->imageName;
		$this->Description = $EventGalleryImageRow->description;

		$this->CreateUserID = $EventGalleryImageRow->createUserID;
		$this->CreateDate = $EventGalleryImageRow->createDate;
	}	
}
?>
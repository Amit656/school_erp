<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.db_connect.php');
error_reporting(E_ALL);

class SMSTemplate
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	
	private $SMSTemplateID;

	private $SMSType;
	private $SMSTemplate;
	private $Description;
	
	private $IsActive;
	private $CreateUserID;
	private $CreateDate;
	
	// PUBLIC METHODS START HERE	//
	public function __construct($SMSTemplateID = 0, $SMSType = '')
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;
		
		if($SMSTemplateID != 0)
		{
			$this->SMSTemplateID = $SMSTemplateID;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetSMSTemplateByID();
		}
		else if ($SMSType != '')
		{
			$this->SMSType = $SMSType;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetSMSTemplateBySMSType();
		}
		else
		{
			//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
			$this->SMSTemplateID = 0;
			$this->SMSType = '';
			$this->SMSTemplate = '';
			$this->Description = '';
			
			$this->IsActive = 0;
			$this->CreateUserID = 0;
			$this->CreateDate = '0000-00-00 00:00:00';
		}
	}
	
	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetSMSTemplateID()
	{
		return $this->SMSTemplateID;
	}
	
	public function GetSMSType()
	{
		return $this->SMSType;
	}
	public function SetSMSType($SMSType)
	{
		$this->SMSType = $SMSType;
	}

	public function GetSMSTemplate()
	{
		return $this->SMSTemplate;
	}
	public function SetSMSTemplate($SMSTemplate)
	{
		$this->SMSTemplate = $SMSTemplate;
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
			$this->DBObject->BeginTransaction();
			if ($this->SaveDetails())
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
			$this->DBObject->RollBackTransaction();
			$this->LastErrorCode = APP_ERROR_UNDEFINED_ERROR;
			return false;
		}
	}

	public function Remove()
    {
        try
        {
            $this->RemoveSMSTemplate();
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
            $RSClassClassteachersCount = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM asa_class_classteachers WHERE smsTemplateID = :|1;');
            $RSClassClassteachersCount->Execute($this->SMSTemplateID);
           
            if ($RSClassClassteachersCount->FetchRow()->totalRecords > 0) 
            {
                return true;
            }
			
			$RSTeacherClassesCount = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM asa_teacher_classes WHERE smsTemplateID = :|1;');
            $RSTeacherClassesCount->Execute($this->SMSTemplateID);
           
            if ($RSTeacherClassesCount->FetchRow()->totalRecords > 0) 
            {
                return true;
            }

            return false;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException: SMSTemplate::CheckDependencies . Stack Trace: ' . $e->getTraceAsString());
            return false;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception: SMSTemplate::CheckDependencies . Stack Trace: ' . $e->getTraceAsString());
            return false;
        }       
    }

	// END OF PUBLIC METHODS	//
	
	// START OF STATIC METHODS	//
    static function GetAllSMSTemplate()
	{
		$AllSMSTemplate = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT smst.*, u.userName AS createUserName 
												FROM sms_templates smst 
												INNER JOIN users u ON smst.createUserID = u.userID
												ORDER BY smst.smsTemplateID;');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllSMSTemplate;
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$AllSMSTemplate[$SearchRow->smsTemplateID]['SMSType'] = $SearchRow->smsType;
            	$AllSMSTemplate[$SearchRow->smsTemplateID]['SMSTemplate'] = $SearchRow->smsTemplate;

				$AllSMSTemplate[$SearchRow->smsTemplateID]['Description'] = $SearchRow->description;
				$AllSMSTemplate[$SearchRow->smsTemplateID]['IsActive'] = $SearchRow->isActive;

				$AllSMSTemplate[$SearchRow->smsTemplateID]['CreateUserName'] = $SearchRow->createUserName;
				$AllSMSTemplate[$SearchRow->smsTemplateID]['CreateDate'] = $SearchRow->createDate;
			}
			
			return $AllSMSTemplate;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at SMSTemplate::GetAllSMSTemplate(). Stack Trace: ' . $e->getTraceAsString());
			return $AllSMSTemplate;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at SMSTemplate::GetAllSMSTemplate(). Stack Trace: ' . $e->getTraceAsString());
			return $AllSMSTemplate;
		}		
	}

    static function SearchSMSTemplate(&$TotalRecords = 0, $GetTotalsOnly = false, $Filters = array(), $Start = 0, $Limit = 100)
    {
    	$AllSMSTemplate = array();

    	try
		{
			$DBConnObject = new DBConnect();
			
			$Conditions = array();
			
			if (count($Filters) > 0)
			{
				if (!empty($Filters['Genders']))
				{
					$Conditions[] = 'smst.gender IN (' . $DBConnObject->RealEscapeVariable($Filters['Genders']) . ')';
				}
				
				if (!empty($Filters['StaffCategories']))
				{
					$Conditions[] = 'smst.staffCategory IN (' . $DBConnObject->RealEscapeVariable($Filters['StaffCategories']) . ')';
				}
				
				if (!empty($Filters['TeacherName']))
				{
					$Conditions[] = 'smst.smsType LIKE ' . $DBConnObject->RealEscapeVariable("%" . $Filters['TeacherName'] . "%") . ' OR smst.smsTemplate LIKE ' . 
										$DBConnObject->RealEscapeVariable("%" . $Filters['TeacherName'] . "%");
				}

				if($Filters['ActiveStatus'] == 1)
				{
					$Conditions[] = 'smst.isActive = 1';
				}
				else
				{
					$Conditions[] = 'smst.isActive = 0';
				}
			}

			$QueryString = '';

			if (count($Conditions) > 0)
			{
				$QueryString = implode(' AND ',$Conditions);
				
				$QueryString = ' WHERE ' . $QueryString;
			}
			
			if ($GetTotalsOnly)
			{
			 
				$RSTotal = $DBConnObject->Prepare('SELECT COUNT(*) AS totalRecords 
													FROM sms_templates smst 
													INNER JOIN users u ON smst.createUserID = u.userID' . $QueryString . ';');
				$RSTotal->Execute();
				
				$TotalRecords = $RSTotal->FetchRow()->totalRecords;
				return;
			}
			
			$RSAllSMSTemplate = $DBConnObject->Prepare('SELECT smst.*, u.userName AS createUserName FROM sms_templates smst 
														INNER JOIN users u ON smst.createUserID = u.userID' . $QueryString . ' 
														ORDER BY smst.smsTemplateID 
														LIMIT ' . (int) $Start . ', ' . (int) $Limit . ';');
			$RSAllSMSTemplate->Execute();

			while($RSAllSMSTemplateRow = $RSAllSMSTemplate->FetchRow())
			{
				$AllSMSTemplate[$RSAllSMSTemplateRow->smsTemplateID]['SMSType'] = $RSAllSMSTemplateRow->smsType;
				$AllSMSTemplate[$RSAllSMSTemplateRow->smsTemplateID]['SMSTemplate'] = $RSAllSMSTemplateRow->smsTemplate;
				
				$AllSMSTemplate[$RSAllSMSTemplateRow->smsTemplateID]['StaffCategory'] = $RSAllSMSTemplateRow->staffCategory;
				
				$AllSMSTemplate[$RSAllSMSTemplateRow->smsTemplateID]['JoiningDate'] = $RSAllSMSTemplateRow->joiningDate;
				$AllSMSTemplate[$RSAllSMSTemplateRow->smsTemplateID]['DOB'] = $RSAllSMSTemplateRow->dob;
				$AllSMSTemplate[$RSAllSMSTemplateRow->smsTemplateID]['MobileNumber1'] = $RSAllSMSTemplateRow->mobileNumber1;
				$AllSMSTemplate[$RSAllSMSTemplateRow->smsTemplateID]['MobileNumber2'] = $RSAllSMSTemplateRow->mobileNumber2;
				$AllSMSTemplate[$RSAllSMSTemplateRow->smsTemplateID]['UserName'] = $RSAllSMSTemplateRow->userName;

				$AllSMSTemplate[$RSAllSMSTemplateRow->smsTemplateID]['IsActive'] = $RSAllSMSTemplateRow->isActive;

				$AllSMSTemplate[$RSAllSMSTemplateRow->smsTemplateID]['CreateUserName'] = $RSAllSMSTemplateRow->createUserName;
				$AllSMSTemplate[$RSAllSMSTemplateRow->smsTemplateID]['CreateUserID'] = $RSAllSMSTemplateRow->createUserID;
				$AllSMSTemplate[$RSAllSMSTemplateRow->smsTemplateID]['CreateDate'] = $RSAllSMSTemplateRow->createDate;
			}
			
			return $AllSMSTemplate;	
				
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at SMSTemplate::SearchSMSTemplate(). Stack Trace: ' . $e->getTraceAsString());
			return $AllSMSTemplate;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at SMSTemplate::SearchSMSTemplate(). Stack Trace: ' . $e->getTraceAsString());
			return $AllSMSTemplate;
		}	
    }

	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//
	private function SaveDetails()
	{
		if ($this->SMSTemplateID == 0)
		{
			$RSSave = $this->DBObject->Prepare('INSERT INTO sms_templates (smsType, smsTemplate, description, isActive, createUserID, createDate)
														VALUES (:|1, :|2, :|3, :|4, :|5, NOW());');
			$RSSave->Execute($this->SMSType, $this->SMSTemplate, $this->Description, $this->IsActive, $this->CreateUserID);

			$this->SMSTemplateID = $RSSave->LastID;
		}
		else
		{
			$RSUpdate = $this->DBObject->Prepare('UPDATE sms_templates
													SET	smsType = :|1,
														smsTemplate = :|2,
														description = :|3,
														isActive = :|4
													WHERE smsTemplateID = :|5;');

			$RSUpdate->Execute($this->SMSType, $this->SMSTemplate, $this->Description, $this->IsActive, $this->SMSTemplateID);
		}

		return true;
	}

	private function RemoveSMSTemplate()
	{
		if(!isset($this->SMSTemplateID)) 
        {
            throw new ApplicationARException('', APP_AR_ERROR_DELETE_WITHOUT_ID);
        }
        
        $RSDeleteSMSTemplate = $this->DBObject->Prepare('DELETE FROM sms_templates WHERE smsTemplateID = :|1	LIMIT 1;');
        $RSDeleteSMSTemplate->Execute($this->SMSTemplateID);
	}
	
	private function GetSMSTemplateByID()
	{
		$RSSMSTemplate = $this->DBObject->Prepare('SELECT * FROM sms_templates WHERE smsTemplateID = :|1 LIMIT 1;');
		$RSSMSTemplate->Execute($this->SMSTemplateID);
		
		$SMSTemplateRow = $RSSMSTemplate->FetchRow();
		
		$this->SetAttributesFromDB($SMSTemplateRow);				
	}
	
	private function GetSMSTemplateBySMSType()
	{
		$RSSMSTemplate = $this->DBObject->Prepare('SELECT * FROM sms_templates WHERE smsType = :|1 LIMIT 1;');
		$RSSMSTemplate->Execute($this->SMSType);
		
		$SMSTemplateRow = $RSSMSTemplate->FetchRow();
		
		$this->SetAttributesFromDB($SMSTemplateRow);				
	}
	
	private function SetAttributesFromDB($SMSTemplateRow)
	{
		$this->SMSTemplateID = $SMSTemplateRow->smsTemplateID;
		$this->SMSType = $SMSTemplateRow->smsType;
		$this->SMSTemplate = $SMSTemplateRow->smsTemplate;
		$this->Description = $SMSTemplateRow->description;
		
		$this->IsActive = $SMSTemplateRow->isActive;
		$this->CreateUserID = $SMSTemplateRow->createUserID;
		$this->CreateDate = $SMSTemplateRow->createDate;
	}	
}
?>
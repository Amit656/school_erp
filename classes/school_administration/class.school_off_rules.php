<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.db_connect.php');
error_reporting(E_ALL);

class schoolOffRule
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	
	private $SchoolOffRuleID;
	private $IsOffTypeApplicable;
	private $IsWeeklyOff;

	private $AppliesToSpecificClasses;
	private $IsTeachingStaffApplicable;
	private $IsNonTeachingStaffApplicable;
	
	private $CreateUserID;
	private $CreateDate;

	private $ApplicableOffType = array();
	private $ApplicableWeekOff = array();
	
	// PUBLIC METHODS START HERE	//
	public function __construct($SchoolOffRuleID = 0)
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;	
		
		if($SchoolOffRuleID != 0)
		{
			$this->SchoolOffRuleID = $SchoolOffRuleID;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetschoolOffRulesByID();
		}
		else
		{
			//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
			$this->SchoolOffRuleID = 0;
			$this->IsOffTypeApplicable = 0;
			$this->IsWeeklyOff = 0;

			$this->AppliesToSpecificClasses = 0;
			$this->IsTeachingStaffApplicable = 0;
			$this->IsNonTeachingStaffApplicable = 0;
			
			$this->CreateUserID = 0;
			$this->CreateDate = '0000-00-00 00:00:00';

			$this->ApplicableOffType = array();
			$this->ApplicableWeekOff = array();
		}
	}
	
	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetSchoolOffRuleID()
	{
		return $this->SchoolOffRuleID;
	}
	
	public function GetIsOffTypeApplicable()
	{
		return $this->IsOffTypeApplicable;
	}
	public function SetIsOffTypeApplicable($IsOffTypeApplicable)
	{
		$this->IsOffTypeApplicable = $IsOffTypeApplicable;
	}
	
	public function GetIsWeeklyOff()
	{
		return $this->IsWeeklyOff;
	}
	public function SetIsWeeklyOff($IsWeeklyOff)
	{
		$this->IsWeeklyOff = $IsWeeklyOff;
	}

	public function GetAppliesToSpecificClasses()
	{
		return $this->AppliesToSpecificClasses;
	}
	public function SetAppliesToSpecificClasses($AppliesToSpecificClasses)
	{
		$this->AppliesToSpecificClasses = $AppliesToSpecificClasses;
	}
	
	public function GetIsTeachingStaffApplicable()
	{
		return $this->IsTeachingStaffApplicable;
	}
	public function SetIsTeachingStaffApplicable($IsTeachingStaffApplicable)
	{
		$this->IsTeachingStaffApplicable = $IsTeachingStaffApplicable;
	}

	public function GetIsNonTeachingStaffApplicable()
	{
		return $this->IsNonTeachingStaffApplicable;
	}
	public function SetIsNonTeachingStaffApplicable($IsNonTeachingStaffApplicable)
	{
		$this->IsNonTeachingStaffApplicable = $IsNonTeachingStaffApplicable;
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

	public function GetApplicableOffType()
	{
		return $this->ApplicableOffType;
	}
	public function SetApplicableOffType($ApplicableOffType)
	{
		$this->ApplicableOffType = $ApplicableOffType;
	}
	
	public function GetApplicableWeekOff()
	{
		return $this->ApplicableWeekOff;
	}
	public function SetApplicableWeekOff($ApplicableWeekOff)
	{
		$this->ApplicableWeekOff = $ApplicableWeekOff;
	}

	public function GetLastErrorCode()
	{
		return $this->LastErrorCode;
	}
	
	//  END OF GETTER AND SETTER FUNCTIONS 	//
	
	public function Save($NotApplicableClasses)
	{
		try
		{
			$this->DBObject->BeginTransaction();
			if ($this->SaveDetails($NotApplicableClasses))
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
			$this->DBObject->BeginTransaction();
			if ($this->RemoveDetails())
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
		
	// END OF PUBLIC METHODS	//
	
	// START OF STATIC METHODS	//
	static function GetAllSchoolOffRules()
	{
		$AllSchoolOffRules = array();

		try
        {
            $DBConnObject = new DBConnect();

            $RSSearch = $DBConnObject->Prepare('SELECT asor.*, u.userName AS createUserName
    												FROM asa_school_off_rules asor
													INNER JOIN users u ON asor.createUserID = u.userID;');
            $RSSearch->Execute();

            if ($RSSearch->Result->num_rows <= 0)
            {
                return $AllSchoolOffRules;
            }

            while($SearchRow = $RSSearch->FetchRow())
            {
            	$AllSchoolOffRules[$SearchRow->schoolOffRuleID]['IsOffTypeApplicable'] = $SearchRow->isOffTypeApplicable;	
            	$AllSchoolOffRules[$SearchRow->schoolOffRuleID]['IsWeeklyOff'] = $SearchRow->isWeeklyOff;	
            	$AllSchoolOffRules[$SearchRow->schoolOffRuleID]['AppliesToSpecificClasses'] = $SearchRow->appliesToSpecificClasses;	
            	$AllSchoolOffRules[$SearchRow->schoolOffRuleID]['IsTeachingStaffApplicable'] = $SearchRow->isTeachingStaffApplicable;	
            	$AllSchoolOffRules[$SearchRow->schoolOffRuleID]['IsNonTeachingStaffApplicable'] = $SearchRow->isNonTeachingStaffApplicable;	

            	$AllSchoolOffRules[$SearchRow->schoolOffRuleID]['CreateUserName'] = $SearchRow->createUserName;	
            	$AllSchoolOffRules[$SearchRow->schoolOffRuleID]['CreateUserID'] = $SearchRow->createUserID;	
            	
            	$AllSchoolOffRules[$SearchRow->schoolOffRuleID]['CreateDate'] = $SearchRow->createDate;	
            }

            return $AllSchoolOffRules;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at SchoolOffRule::GetAllSchoolOffRules(). Stack Trace: ' . $e->getTraceAsString());
            return $AllSchoolOffRules;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception at SchoolOffRule::GetAllSchoolOffRules(). Stack Trace: ' . $e->getTraceAsString());
            return $AllSchoolOffRules;
        }
	}
	
	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//
	private function SaveDetails($NotApplicableClasses)
	{
		if ($this->SchoolOffRuleID == 0)
		{
			$RSSaveSchoolOffRule = $this->DBObject->Prepare('INSERT INTO asa_school_off_rules (isOffTypeApplicable, isWeeklyOff, appliesToSpecificClasses, isTeachingStaffApplicable, 
																		isNonTeachingStaffApplicable, createUserID, createDate)
																VALUES (:|1, :|2, :|3, :|4, :|5, :|6, NOW());');
		
			$RSSaveSchoolOffRule->Execute($this->IsOffTypeApplicable, $this->IsWeeklyOff, $this->AppliesToSpecificClasses, $this->IsTeachingStaffApplicable, 
													$this->IsNonTeachingStaffApplicable, $this->CreateUserID);
			
			$this->SchoolOffRuleID = $RSSaveSchoolOffRule->LastID;

			if ($this->IsOffTypeApplicable) 
			{
				foreach ($this->ApplicableOffType as $OffType) 
				{
					$RSSaveSchoolOffRule = $this->DBObject->Prepare('INSERT INTO asa_applicable_off_type (schoolOffRuleID, offType)
																			VALUES (:|1, :|2);');
			
					$RSSaveSchoolOffRule->Execute($this->SchoolOffRuleID, $OffType);
				}
			}

			if ($this->IsWeeklyOff) 
			{
				foreach ($this->ApplicableWeekOff as $SchoolOffRuleType => $ApplicableWeekOffDetails) 
				{	
					$Weekdays = implode(',', $ApplicableWeekOffDetails);
					$RSSaveWeeklyOff = $this->DBObject->Prepare('INSERT INTO asa_school_off_rules_weekly_details (schoolOffRuleID, schoolOffRuleDetailType, weekdays)
																			VALUES (:|1, :|2, :|3);');
			
					$RSSaveWeeklyOff->Execute($this->SchoolOffRuleID, $SchoolOffRuleType, $Weekdays);
				}
			}

			if ($this->AppliesToSpecificClasses) 
			{	
				$NotApplicableClasseSectionIDs = '';

				if (count($this->AppliesToSpecificClasses) > 0) 
				{
					$NotApplicableClasseSectionIDs = implode(',', array_keys($NotApplicableClasses));
				}

				if ($NotApplicableClasseSectionIDs != '') 
				{
					$RSSaveWeeklyOff = $this->DBObject->Prepare('INSERT INTO asa_school_off_rule_applicable_classes (schoolOffRuleID, classSectionID)
																				SELECT :|1, classSectionID FROM asa_class_sections WHERE classSectionID NOT IN ( ' . rtrim($NotApplicableClasseSectionIDs, ',') . ');');
					
					$RSSaveWeeklyOff->Execute($this->SchoolOffRuleID);
				}
				else
				{
					$RSSaveWeeklyOff = $this->DBObject->Prepare('INSERT INTO asa_school_off_rule_applicable_classes (schoolOffRuleID, classSectionID)
																				SELECT :|1, classSectionID FROM asa_class_sections;');
					
					$RSSaveWeeklyOff->Execute($this->SchoolOffRuleID);
				}
			}
		}
		
		return true;
	}

	private function RemoveDetails()
    {
        if(!isset($this->SchoolOffRuleID)) 
        {
            throw new ApplicationARException('', APP_AR_ERROR_DELETE_WITHOUT_ID);
        }
        
        $RSDeleteApplicableClasses = $this->DBObject->Prepare('DELETE FROM asa_school_off_rule_applicable_classes WHERE schoolOffRuleID = :|1;');
        $RSDeleteApplicableClasses->Execute($this->SchoolOffRuleID);    

        $RSDeleteWeeklyDetails = $this->DBObject->Prepare('DELETE FROM asa_school_off_rules_weekly_details WHERE schoolOffRuleID = :|1;');
        $RSDeleteWeeklyDetails->Execute($this->SchoolOffRuleID);

        $RSDeleteOffType = $this->DBObject->Prepare('DELETE FROM asa_applicable_off_type WHERE schoolOffRuleID = :|1;');
        $RSDeleteOffType->Execute($this->SchoolOffRuleID); 

        $RSDeleteSchoolsOffRules = $this->DBObject->Prepare('DELETE FROM asa_school_off_rules WHERE schoolOffRuleID = :|1 LIMIT 1;');
        $RSDeleteSchoolsOffRules->Execute($this->SchoolOffRuleID);

        return true;          
    }
	
	private function GetschoolOffRulesByID()
	{
		$RSschoolOffRules = $this->DBObject->Prepare('SELECT * FROM asa_school_off_rules WHERE schoolOffRuleID = :|1 LIMIT 1;');
		$RSschoolOffRules->Execute($this->SchoolOffRuleID);
		
		$schoolOffRulesRow = $RSschoolOffRules->FetchRow();
		
		$this->SetAttributesFromDB($schoolOffRulesRow);				
	}
	
	private function SetAttributesFromDB($schoolOffRulesRow)
	{
		$this->SchoolOffRuleID = $schoolOffRulesRow->schoolOffRuleID;
		$this->IsOffTypeApplicable = $schoolOffRulesRow->isOffTypeApplicable;
		$this->IsWeeklyOff = $schoolOffRulesRow->isWeeklyOff;

		$this->AppliesToSpecificClasses = $schoolOffRulesRow->appliesToSpecificClasses;
		$this->IsTeachingStaffApplicable = $schoolOffRulesRow->isTeachingStaffApplicable;
		$this->IsNonTeachingStaffApplicable = $schoolOffRulesRow->isNonTeachingStaffApplicable;
		
		$this->CreateUserID = $schoolOffRulesRow->createUserID;
		$this->CreateDate = $schoolOffRulesRow->createDate;
	}	
}
?>
<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.db_connect.php');
error_reporting(E_ALL);

class ParentDetail
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	
	private $ParentID;
	private $FatherFirstName;
	private $FatherLastName;
	private $MotherFirstName;
	private $MotherLastName;

	private $FatherOccupation;
	private $MotherOccupation;

	private $FatherOfficeName;
	private $MotherOfficeName;
	private $FatherOfficeAddress;
	private $MotherOfficeAddress;

	private $ResidentailAddress;
	private $ResidentailCityID;
	private $ResidentailDistrictID;
	private $ResidentailStateID;
	private $ResidentailCountryID;
	private $ResidentailPinCode;

	private $PermanentAddress;
	private $PermanentCityID;
	private $PermanentDistrictID;
	private $PermanentStateID;
	private $PermanentCountryID;
	private $PermanentPinCode;

	private $PhoneNumber;
	private $FatherMobileNumber;
	private $MotherMobileNumber;

	private $FatherEmail;
	private $MotherEmail;

	private $UserName;
	private $FeeCode;
	private $AadharNumber;
	private $IsActive;
	
	// PUBLIC METHODS START HERE	//
	public function __construct($ParentID = 0)
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;
		
		if($ParentID != 0)
		{
			$this->ParentID = $ParentID;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetParentDetailByID();
		}
		else
		{
			//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
			$this->ParentID = 0;
			$this->FatherFirstName = '';
			$this->FatherLastName = '';
			$this->MotherFirstName = '';
			$this->MotherLastName = '';

			$this->FatherOccupation = '';
			$this->MotherOccupation = '';

			$this->FatherOfficeName = '';
			$this->MotherOfficeName = '';
			$this->FatherOfficeAddress = '';
			$this->MotherOfficeAddress = '';

			$this->ResidentailAddress = '';
			$this->ResidentailCityID = 0;
			$this->ResidentailDistrictID = 0;
			$this->ResidentailStateID = 0;
			$this->ResidentailCountryID = 0;
			$this->ResidentailPinCode = '';

			$this->PermanentAddress = '';
			$this->PermanentCityID = 0;
			$this->PermanentDistrictID = 0;
			$this->PermanentStateID = 0;
			$this->PermanentCountryID = 0;
			$this->PermanentPinCode = '';

			$this->PhoneNumber = '';
			$this->FatherMobileNumber = '';
			$this->MotherMobileNumber = '';
			
			$this->FatherEmail = '';
			$this->MotherEmail = '';

			$this->UserName = '';
			$this->FeeCode = '';
			$this->AadharNumber = 0;
			$this->IsActive = 0;
		}
	}
	
	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetParentID()
	{
		return $this->ParentID;
	}
	
	public function GetFatherFirstName()
	{
		return $this->FatherFirstName;
	}
	public function SetFatherFirstName($FatherFirstName)
	{
		$this->FatherFirstName = $FatherFirstName;
	}

	public function GetFatherLastName()
	{
		return $this->FatherLastName;
	}
	public function SetFatherLastName($FatherLastName)
	{
		$this->FatherLastName = $FatherLastName;
	}

	public function GetMotherFirstName()
	{
		return $this->MotherFirstName;
	}
	public function SetMotherFirstName($MotherFirstName)
	{
		$this->MotherFirstName = $MotherFirstName;
	}

	public function GetMotherLastName()
	{
		return $this->MotherLastName;
	}
	public function SetMotherLastName($MotherLastName)
	{
		$this->MotherLastName = $MotherLastName;
	}

	public function GetFatherOccupation()
	{
		return $this->FatherOccupation;
	}
	public function SetFatherOccupation($FatherOccupation)
	{
		$this->FatherOccupation = $FatherOccupation;
	}

	public function GetMotherOccupation()
	{
		return $this->MotherOccupation;
	}
	public function SetMotherOccupation($MotherOccupation)
	{
		$this->MotherOccupation = $MotherOccupation;
	}

	public function GetFatherOfficeName()
	{
		return $this->FatherOfficeName;
	}
	public function SetFatherOfficeName($FatherOfficeName)
	{
		$this->FatherOfficeName = $FatherOfficeName;
	}

	public function GetMotherOfficeName()
	{
		return $this->MotherOfficeName;
	}
	public function SetMotherOfficeName($MotherOfficeName)
	{
		$this->MotherOfficeName = $MotherOfficeName;
	}

	public function GetFatherOfficeAddress()
	{
		return $this->FatherOfficeAddress;
	}
	public function SetFatherOfficeAddress($FatherOfficeAddress)
	{
		$this->FatherOfficeAddress = $FatherOfficeAddress;
	}

	public function GetMotherOfficeAddress()
	{
		return $this->MotherOfficeAddress;
	}
	public function SetMotherOfficeAddress($MotherOfficeAddress)
	{
		$this->MotherOfficeAddress = $MotherOfficeAddress;
	}

	public function GetResidentailAddress()
	{
		return $this->ResidentailAddress;
	}
	public function SetResidentailAddress($ResidentailAddress)
	{
		$this->ResidentailAddress = $ResidentailAddress;
	}

	public function GetResidentailCityID()
	{
		return $this->ResidentailCityID;
	}
	public function SetResidentailCityID($ResidentailCityID)
	{
		$this->ResidentailCityID = $ResidentailCityID;
	}

	public function GetResidentailDistrictID()
	{
		return $this->ResidentailDistrictID;
	}
	public function SetResidentailDistrictID($ResidentailDistrictID)
	{
		$this->ResidentailDistrictID = $ResidentailDistrictID;
	}

	public function GetResidentailStateID()
	{
		return $this->ResidentailStateID;
	}
	public function SetResidentailStateID($ResidentailStateID)
	{
		$this->ResidentailStateID = $ResidentailStateID;
	}

	public function GetResidentailCountryID()
	{
		return $this->ResidentailCountryID;
	}
	public function SetResidentailCountryID($ResidentailCountryID)
	{
		$this->ResidentailCountryID = $ResidentailCountryID;
	}

	public function GetResidentailPinCode()
	{
		return $this->ResidentailPinCode;
	}
	public function SetResidentailPinCode($ResidentailPinCode)
	{
		$this->ResidentailPinCode = $ResidentailPinCode;
	}

	public function GetPermanentAddress()
	{
		return $this->PermanentAddress;
	}
	public function SetPermanentAddress($PermanentAddress)
	{
		$this->PermanentAddress = $PermanentAddress;
	}

	public function GetPermanentCityID()
	{
		return $this->PermanentCityID;
	}
	public function SetPermanentCityID($PermanentCityID)
	{
		$this->PermanentCityID = $PermanentCityID;
	}

	public function GetPermanentDistrictID()
	{
		return $this->PermanentDistrictID;
	}
	public function SetPermanentDistrictID($PermanentDistrictID)
	{
		$this->PermanentDistrictID = $PermanentDistrictID;
	}

	public function GetPermanentStateID()
	{
		return $this->PermanentStateID;
	}
	public function SetPermanentStateID($PermanentStateID)
	{
		$this->PermanentStateID = $PermanentStateID;
	}

	public function GetPermanentCountryID()
	{
		return $this->PermanentCountryID;
	}
	public function SetPermanentCountryID($PermanentCountryID)
	{
		$this->PermanentCountryID = $PermanentCountryID;
	}

	public function GetPermanentPinCode()
	{
		return $this->PermanentPinCode;
	}
	public function SetPermanentPinCode($PermanentPinCode)
	{
		$this->PermanentPinCode = $PermanentPinCode;
	}

	public function GetPhoneNumber()
	{
		return $this->PhoneNumber;
	}
	public function SetPhoneNumber($PhoneNumber)
	{
		$this->PhoneNumber = $PhoneNumber;
	}

	public function GetFatherMobileNumber()
	{
		return $this->FatherMobileNumber;
	}
	public function SetFatherMobileNumber($FatherMobileNumber)
	{
		$this->FatherMobileNumber = $FatherMobileNumber;
	}

	public function GetMotherMobileNumber()
	{
		return $this->MotherMobileNumber;
	}
	public function SetMotherMobileNumber($MotherMobileNumber)
	{
		$this->MotherMobileNumber = $MotherMobileNumber;
	}

	public function GetFatherEmail()
	{
		return $this->FatherEmail;
	}
	public function SetFatherEmail($FatherEmail)
	{
		$this->FatherEmail = $FatherEmail;
	}

	public function GetMotherEmail()
	{
		return $this->MotherEmail;
	}
	public function SetMotherEmail($MotherEmail)
	{
		$this->MotherEmail = $MotherEmail;
	}
	
	public function GetUserName()
	{
		return $this->UserName;
	}
	public function SetUserName($UserName)
	{
		$this->UserName = $UserName;
	}
	
	public function GetFeeCode()
	{
		return $this->FeeCode;
	}
	public function SetFeeCode($FeeCode)
	{
		$this->FeeCode = $FeeCode;
	}

	public function GetAadharNumber()
	{
		return $this->AadharNumber;
	}
	public function SetAadharNumber($AadharNumber)
	{
		$this->AadharNumber = $AadharNumber;
	}

	public function GetIsActive()
	{
		return $this->IsActive;
	}
	public function SetIsActive($IsActive)
	{
		$this->IsActive = $IsActive;
	}
	
	public function GetLastErrorCode()
	{
		return $this->LastErrorCode;
	}
	
	//  END OF GETTER AND SETTER FUNCTIONS 	//
		
	// END OF PUBLIC METHODS	//
	
	// START OF STATIC METHODS	//
	
	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//	
	private function GetParentDetailByID()
	{
		$RSParentDetail = $this->DBObject->Prepare('SELECT * FROM asa_parent_details WHERE parentID = :|1 LIMIT 1;');
		$RSParentDetail->Execute($this->ParentID);
		
		$ParentDetailRow = $RSParentDetail->FetchRow();
		
		$this->SetAttributesFromDB($ParentDetailRow);				
	}
	
	private function SetAttributesFromDB($ParentDetailRow)
	{
		$this->ParentID = $ParentDetailRow->parentID;
		$this->FatherFirstName = $ParentDetailRow->fatherFirstName;
		$this->FatherLastName = $ParentDetailRow->fatherLastName;
		$this->MotherFirstName = $ParentDetailRow->motherFirstName;
		$this->MotherLastName = $ParentDetailRow->motherLastName;

		$this->FatherOccupation = $ParentDetailRow->fatherOccupation;
		$this->MotherOccupation = $ParentDetailRow->motherOccupation;

		$this->FatherOfficeName = $ParentDetailRow->fatherOfficeName;
		$this->MotherOfficeName = $ParentDetailRow->motherOfficeName;
		$this->FatherOfficeAddress = $ParentDetailRow->fatherOfficeAddress;
		$this->MotherOfficeAddress = $ParentDetailRow->motherOfficeAddress;

		$this->ResidentailAddress = $ParentDetailRow->residentailAddress;
		$this->ResidentailCityID = $ParentDetailRow->residentailCityID;
		$this->ResidentailDistrictID = $ParentDetailRow->residentailDistrictID;
		$this->ResidentailStateID = $ParentDetailRow->residentailStateID;
		$this->ResidentailCountryID = $ParentDetailRow->residentailCountryID;
		$this->ResidentailPinCode = $ParentDetailRow->residentailPinCode;

		$this->PermanentAddress = $ParentDetailRow->permanentAddress;
		$this->PermanentCityID = $ParentDetailRow->permanentCityID;
		$this->PermanentDistrictID = $ParentDetailRow->permanentDistrictID;
		$this->PermanentStateID = $ParentDetailRow->permanentStateID;
		$this->PermanentCountryID = $ParentDetailRow->permanentCountryID;
		$this->PermanentPinCode = $ParentDetailRow->permanentPinCode;

		$this->PhoneNumber = $ParentDetailRow->phoneNumber;
		$this->FatherMobileNumber = $ParentDetailRow->fatherMobileNumber;
		$this->MotherMobileNumber = $ParentDetailRow->motherMobileNumber;
		
		$this->FatherEmail = $ParentDetailRow->fatherEmail;
		$this->MotherEmail = $ParentDetailRow->motherEmail;

		$this->UserName = $ParentDetailRow->userName;
		$this->FeeCode = $ParentDetailRow->feeCode;
		$this->AadharNumber = $ParentDetailRow->aadharNumber;
		$this->IsActive = $ParentDetailRow->isActive;
	}
}
?>
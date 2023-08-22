<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.db_connect.php');
error_reporting(E_ALL);

class ProductVendor
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	
	private $ProductVendorID;
	private $VendorName;

	private $Address1;
	private $Address2;
	
	private $CityID;
	private $DistrictID;
	private $StateID;
	private $CountryID;
	private $PinCode;
	
	private $PhoneNumber;
	private $MobileNumber1;
	private $MobileNumber2;
	
	private $ContactName;
	private $ContactPhoneNumber;
	private $description;

	private $IsActive;
	private $CreateUserID;
	private $CreateDate;
	
	// PUBLIC METHODS START HERE	//
	public function __construct($ProductVendorID = 0)
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;
		
		if($ProductVendorID != 0)
		{
			$this->ProductVendorID = $ProductVendorID;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetProductVendorByID();
		}
		else
		{
			//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
			$this->ProductVendorID = 0;
			$this->VendorName = '';

			$this->Address1 = '';
			$this->Address2 = '';
			
			$this->CityID = 0;
			$this->DistrictID = 0;
			$this->StateID = 0;
			$this->CountryID = 0;
			$this->PinCode = '';
			
			$this->PhoneNumber = '';
			$this->MobileNumber1 = '';
			$this->MobileNumber2 = '';
			
			$this->ContactName = '';
			$this->ContactPhoneNumber = '';
			$this->Description = '';

			$this->IsActive = 0;
			$this->CreateUserID = 0;
			$this->CreateDate = '0000-00-00 00:00:00';
		}
	}
	
	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetProductVendorID()
	{
		return $this->ProductVendorID;
	}
	
	public function GetVendorName()
	{
		return $this->VendorName;
	}
	public function SetVendorName($VendorName)
	{
		$this->VendorName = $VendorName;
	}
	
	public function GetAddress1()
	{
		return $this->Address1;
	}
	public function SetAddress1($Address1)
	{
		$this->Address1 = $Address1;
	}
	
	public function GetAddress2()
	{
		return $this->Address2;
	}
	public function SetAddress2($Address2)
	{
		$this->Address2 = $Address2;
	}
	
	public function GetCityID()
	{
		return $this->CityID;
	}
	public function SetCityID($CityID)
	{
		$this->CityID = $CityID;
	}
	
	public function GetDistrictID()
	{
		return $this->DistrictID;
	}
	public function SetDistrictID($DistrictID)
	{
		$this->DistrictID = $DistrictID;
	}
	
	public function GetStateID()
	{
		return $this->StateID;
	}
	public function SetStateID($StateID)
	{
		$this->StateID = $StateID;
	}
	
	public function GetCountryID()
	{
		return $this->CountryID;
	}
	public function SetCountryID($CountryID)
	{
		$this->CountryID = $CountryID;
	}
	
	public function GetPinCode()
	{
		return $this->PinCode;
	}
	public function SetPinCode($PinCode)
	{
		$this->PinCode = $PinCode;
	}
	
	public function GetPhoneNumber()
	{
		return $this->PhoneNumber;
	}
	public function SetPhoneNumber($PhoneNumber)
	{
		$this->PhoneNumber = $PhoneNumber;
	}
	
	public function GetMobileNumber1()
	{
		return $this->MobileNumber1;
	}
	public function SetMobileNumber1($MobileNumber1)
	{
		$this->MobileNumber1 = $MobileNumber1;
	}
	
	public function GetMobileNumber2()
	{
		return $this->MobileNumber2;
	}
	public function SetMobileNumber2($MobileNumber2)
	{
		$this->MobileNumber2 = $MobileNumber2;
	}
	
	public function GetContactName()
	{
		return $this->ContactName;
	}
	public function SetContactName($ContactName)
	{
		$this->ContactName = $ContactName;
	}
	
	public function GetContactPhoneNumber()
	{
		return $this->ContactPhoneNumber;
	}
	public function SetContactPhoneNumber($ContactPhoneNumber)
	{
		$this->ContactPhoneNumber = $ContactPhoneNumber;
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
			$this->RemoveProductVendor();
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
			$RSTotal = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM role_group_roles WHERE productVendorID = :|1;');
			$RSTotal->Execute($this->ProductVendorID);
	
			if ($RSTotal->FetchRow()->totalRecords > 0) 
			{
				return true;
			}
			
			return false;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException: ProductVendor::CheckDependencies(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception: ProductVendor::CheckDependencies(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
	}

	public function RecordExists()
	{
		try
		{
			$QueryString = '';
			
			if ($this->ProductVendorID > 0)
			{
				$QueryString = ' AND productVendorID != ' . $this->DBObject->RealEscapeVariable($this->ProductVendorID);
			}

			$RSTotal = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM aim_product_vendors WHERE vendorName = :|1' . $QueryString . ';');
			$RSTotal->Execute($this->VendorName);
			
			if ($RSTotal->FetchRow()->totalRecords > 0)
			{
				return true;
			}
			
			return false;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at ProductVendor::RecordExists(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at ProductVendor::RecordExists(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
	}

	// END OF PUBLIC METHODS	//
	
	// START OF STATIC METHODS	//
	static function SearchProductVendor(&$TotalRecords = 0, $GetTotalsOnly = false, $Filters = array(), $Start = 0, $Limit = 100)
    {
    	$AllProductVendors = array();

    	try
		{
			$DBConnObject = new DBConnect();
			
			$Conditions = array();
			
			if (count($Filters) > 0)
			{
				
				if (!empty($Filters['VendorName']))
				{
					$Conditions[] = 'apv.vendorName LIKE ' . $DBConnObject->RealEscapeVariable("%" . $Filters['VendorName'] . "%");
				}

				if (!empty($Filters['CountryID']))
				{
					$Conditions[] = 'apv.countryID = ' . $DBConnObject->RealEscapeVariable($Filters['CountryID']);
				}

				if (!empty($Filters['StateID']))
				{
					$Conditions[] = 'apv.stateID = ' . $DBConnObject->RealEscapeVariable($Filters['StateID']);
				}

				if (!empty($Filters['DistrictID']))
				{
					$Conditions[] = 'apv.districtID = ' . $DBConnObject->RealEscapeVariable($Filters['DistrictID']);
				}

				if (!empty($Filters['CityID']))
				{
					$Conditions[] = 'apv.cityID = ' . $DBConnObject->RealEscapeVariable($Filters['CityID']);
				}

				if (!empty($Filters['MobileNumber']))
				{
					$Conditions[] = 'apv.mobileNumber1 LIKE ' . $DBConnObject->RealEscapeVariable("%" . $Filters['MobileNumber'] . "%");
				}

				if (!empty($Filters['ContactName']))
				{
					$Conditions[] = 'apv.contactName LIKE ' . $DBConnObject->RealEscapeVariable("%" . $Filters['ContactName'] . "%");
				}

				if (!empty($Filters['ContactPhoneNumber']))
				{
					$Conditions[] = 'apv.contactPhoneNumber LIKE ' . $DBConnObject->RealEscapeVariable("%" . $Filters['ContactPhoneNumber'] . "%");
				}

				if($Filters['ActiveStatus'] == 1)
				{
					$Conditions[] = 'apv.isActive = 1';
				}
				else
				{
					$Conditions[] = 'apv.isActive = 0';
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
													FROM aim_product_vendors apv 
													INNER JOIN users u ON apv.createUserID = u.userID' . $QueryString . ';');
				$RSTotal->Execute();
				
				$TotalRecords = $RSTotal->FetchRow()->totalRecords;
				return;
			}
			
			$RSSearch = $DBConnObject->Prepare('SELECT apv.*, u.userName AS createUserName FROM aim_product_vendors apv 
													INNER JOIN users u ON apv.createUserID = u.userID' . $QueryString . ' 
													ORDER BY apv.vendorName 
													LIMIT ' . (int) $Start . ', ' . (int) $Limit . ';');
			$RSSearch->Execute();

			while($SearchRow = $RSSearch->FetchRow())
			{
				$AllProductVendors[$SearchRow->productVendorID]['VendorName'] = $SearchRow->vendorName;
                $AllProductVendors[$SearchRow->productVendorID]['PhoneNumber'] = $SearchRow->phoneNumber;
                $AllProductVendors[$SearchRow->productVendorID]['MobileNumber1'] = $SearchRow->mobileNumber1;

                $AllProductVendors[$SearchRow->productVendorID]['ContactName'] = $SearchRow->contactName;
                $AllProductVendors[$SearchRow->productVendorID]['ContactPhoneNumber'] = $SearchRow->contactPhoneNumber;

				$AllProductVendors[$SearchRow->productVendorID]['IsActive'] = $SearchRow->isActive;
                $AllProductVendors[$SearchRow->productVendorID]['CreateUserID'] = $SearchRow->createUserID;
                $AllProductVendors[$SearchRow->productVendorID]['CreateUserName'] = $SearchRow->createUserName;

                $AllProductVendors[$SearchRow->productVendorID]['CreateDate'] = $SearchRow->createDate;
			}
			
			return $AllProductVendors;	
				
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at ProductVendor::SearchProductVendor(). Stack Trace: ' . $e->getTraceAsString());
			return $AllProductVendors;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at ProductVendor::SearchProductVendor(). Stack Trace: ' . $e->getTraceAsString());
			return $AllProductVendors;
		}	
    }

	static function GetAllProductVendors()
    { 
		$AllProductVendors = array();
		
    	try
        {	
        	$DBConnObject = new DBConnect();
        	$RSSearch = $DBConnObject->Prepare('SELECT apv.*, u.userName AS createUserName FROM aim_product_vendors apv 
													INNER JOIN users u ON apv.createUserID = u.userID 
        											ORDER BY apv.vendorName;');
            $RSSearch->Execute();

            if ($RSSearch->Result->num_rows <= 0)
            {
                return $AllProductVendors;
            }

            while($SearchRow = $RSSearch->FetchRow())
            {
                $AllProductVendors[$SearchRow->productVendorID]['VendorName'] = $SearchRow->vendorName;
                $AllProductVendors[$SearchRow->productVendorID]['PhoneNumber'] = $SearchRow->phoneNumber;
                $AllProductVendors[$SearchRow->productVendorID]['MobileNumber1'] = $SearchRow->mobileNumber1;

                $AllProductVendors[$SearchRow->productVendorID]['ContactName'] = $SearchRow->contactName;
                $AllProductVendors[$SearchRow->productVendorID]['ContactPhoneNumber'] = $SearchRow->contactPhoneNumber;

				$AllProductVendors[$SearchRow->productVendorID]['IsActive'] = $SearchRow->isActive;
                $AllProductVendors[$SearchRow->productVendorID]['CreateUserID'] = $SearchRow->createUserID;
                $AllProductVendors[$SearchRow->productVendorID]['CreateUserName'] = $SearchRow->createUserName;

                $AllProductVendors[$SearchRow->productVendorID]['CreateDate'] = $SearchRow->createDate;
           }
            
            return $AllProductVendors;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException::ProductVendor::GetAllProductVendors(). Stack Trace: '. $e->getTraceAsString());
            return $AllProductVendors;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception: ProductVendor::GetAllProductVendors() . Stack Trace: '. $e->getTraceAsString());
            return $AllProductVendors;
        }
    }

    static function GetActiveProductVendors()
	{
		$AllProductVendors = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT * FROM aim_product_vendors WHERE isActive = 1;');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllProductVendors;
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$AllProductVendors[$SearchRow->productVendorID] = $SearchRow->vendorName;
			}
			
			return $AllProductVendors;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at ProductVendor::GetActiveProductVendors(). Stack Trace: ' . $e->getTraceAsString());
			return $AllProductVendors;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at ProductVendor::GetActiveProductVendors(). Stack Trace: ' . $e->getTraceAsString());
			return $AllProductVendors;
		}		
	}
    
	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//
	private function SaveDetails()
	{
		if ($this->ProductVendorID == 0)
		{
			$RSSave = $this->DBObject->Prepare('INSERT INTO aim_product_vendors (vendorName, address1, address2, cityID, 
																				districtID, stateID, countryID, pinCode, 
																				phoneNumber, MobileNumber1, mobileNumber2, contactName, 
																				contactPhoneNumber, description, isActive, createUserID, createDate)
													VALUES (:|1, :|2, :|3, :|4, :|5, :|6, :|7, :|8, :|9, :|10, :|11, :|12, :|13, :|14, :|15, :|16, NOW());');
			$RSSave->Execute($this->VendorName, $this->Address1, $this->Address2, $this->CityID, 
							$this->DistrictID, $this->StateID, $this->CountryID, $this->PinCode, 
							$this->PhoneNumber, $this->MobileNumber1, $this->MobileNumber2, $this->ContactName, 
							$this->ContactPhoneNumber, $this->Description, $this->IsActive, $this->CreateUserID);

			$this->ProductVendorID = $RSSave->LastID;
		}
		else
		{
			$RSUpdate = $this->DBObject->Prepare('UPDATE aim_product_vendors
													SET	vendorName = :|1,
														address1 = :|2,
														address2 = :|3,
														cityID = :|4,
														districtID = :|5,
														stateID = :|6,
														countryID = :|7,
														pinCode = :|8,
														phoneNumber = :|9,
														MobileNumber1 = :|10,
														mobileNumber2 = :|11,
														contactName = :|12,
														contactPhoneNumber = :|13,
														description = :|14,
														isActive = :|15
													WHERE productVendorID = :|16 LIMIT 1;');
			$RSUpdate->Execute($this->VendorName,  $this->Address1, $this->Address2, $this->CityID, 
							$this->DistrictID, $this->StateID, $this->CountryID, $this->PinCode, 
							$this->PhoneNumber, $this->MobileNumber1, $this->MobileNumber2, $this->ContactName, 
							$this->ContactPhoneNumber, $this->Description,  $this->IsActive, $this->ProductVendorID);
		}
		
		return true;
	}

	private function RemoveProductVendor()
	{
		if(!isset($this->ProductVendorID)) 
		{
    		throw new ApplicationARException('', APP_AR_ERROR_DELETE_WITHOUT_ID);
		}
		
		$RSDeleteProductVendor = $this->DBObject->Prepare('DELETE FROM aim_product_vendors WHERE productVendorID = :|1 LIMIT 1;');
		$RSDeleteProductVendor->Execute($this->ProductVendorID);				
	}
	
	private function GetProductVendorByID()
	{
		$RSProductVendor = $this->DBObject->Prepare('SELECT * FROM aim_product_vendors WHERE productVendorID = :|1 LIMIT 1;');
		$RSProductVendor->Execute($this->ProductVendorID);
		
		$ProductVendorRow = $RSProductVendor->FetchRow();
		
		$this->SetAttributesFromDB($ProductVendorRow);				
	}
	
	private function SetAttributesFromDB($ProductVendorRow)
	{
		$this->ProductVendorID = $ProductVendorRow->productVendorID;
		$this->VendorName = $ProductVendorRow->vendorName;

		$this->Address1 = $ProductVendorRow->address1;
		$this->Address2 = $ProductVendorRow->address2;
		
		$this->CityID = $ProductVendorRow->cityID;
		$this->DistrictID = $ProductVendorRow->districtID;
		$this->StateID = $ProductVendorRow->stateID;
		$this->CountryID = $ProductVendorRow->countryID;
		$this->PinCode = $ProductVendorRow->pinCode;
		
		$this->PhoneNumber = $ProductVendorRow->phoneNumber;
		$this->MobileNumber1 = $ProductVendorRow->mobileNumber1;
		$this->MobileNumber2 = $ProductVendorRow->mobileNumber2;
		
		$this->ContactName = $ProductVendorRow->contactName;
		$this->ContactPhoneNumber = $ProductVendorRow->contactPhoneNumber;
		$this->Description = $ProductVendorRow->description;

		$this->IsActive = $ProductVendorRow->isActive;
		$this->CreateUserID = $ProductVendorRow->createUserID;
		$this->CreateDate = $ProductVendorRow->createDate;
	}	
}
?>
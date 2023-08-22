<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.db_connect.php');
error_reporting(E_ALL);

class MasterProductUnit
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	
	private $ProductUnitID;
	private $ProductUnitName;

	private $IsActive;
	private $CreateUserID;
	private $CreateDate;
	
	// PUBLIC METHODS START HERE	//
	public function __construct($ProductUnitID = 0)
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;
		
		if($ProductUnitID != 0)
		{
			$this->ProductUnitID = $ProductUnitID;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetMasterProductUnitByID();
		}
		else
		{
			//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
			$this->ProductUnitID = 0;
			$this->ProductUnitName = '';

			$this->IsActive = 0;
			$this->CreateUserID = 0;
			$this->CreateDate = '0000-00-00 00:00:00';
		}
	}
	
	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetProductUnitID()
	{
		return $this->ProductUnitID;
	}
	
	public function GetProductUnitName()
	{
		return $this->ProductUnitName;
	}
	public function SetProductUnitName($ProductUnitName)
	{
		$this->ProductUnitName = $ProductUnitName;
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
			$this->RemoveMasterProductUnit();
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
			$RSTotal = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM role_group_roles WHERE productUnitID = :|1;');
			$RSTotal->Execute($this->ProductUnitID);
	
			if ($RSTotal->FetchRow()->totalRecords > 0) 
			{
				return true;
			}
			
			return false;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException: MasterProductUnit::CheckDependencies(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception: MasterProductUnit::CheckDependencies(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
	}

	public function RecordExists()
	{
		try
		{
			$QueryString = '';
			
			if ($this->ProductUnitID > 0)
			{
				$QueryString = ' AND productUnitID != ' . $this->DBObject->RealEscapeVariable($this->ProductUnitID);
			}

			$RSTotal = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM aim_master_product_units WHERE productUnitName = :|1' . $QueryString . ';');
			$RSTotal->Execute($this->ProductUnitName);
			
			if ($RSTotal->FetchRow()->totalRecords > 0)
			{
				return true;
			}
			
			return false;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at MasterProductUnit::RecordExists(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at MasterProductUnit::RecordExists(). Stack Trace: ' . $e->getTraceAsString());
			return true;
		}
	}

	// END OF PUBLIC METHODS	//
	
	// START OF STATIC METHODS	//
	static function GetAllMasterProductUnits()
    { 
		$AllMasterProductUnits = array();
		
    	try
        {	
        	$DBConnObject = new DBConnect();
        	$RSSearch = $DBConnObject->Prepare('SELECT ampu.*, u.userName AS createUserName FROM aim_master_product_units ampu 
													INNER JOIN users u ON ampu.createUserID = u.userID 
        											ORDER BY ampu.productUnitName;');
            $RSSearch->Execute();

            if ($RSSearch->Result->num_rows <= 0)
            {
                return $AllMasterProductUnits;
            }

            while($SearchRow = $RSSearch->FetchRow())
            {
                $AllMasterProductUnits[$SearchRow->productUnitID]['ProductUnitName'] = $SearchRow->productUnitName;

				$AllMasterProductUnits[$SearchRow->productUnitID]['IsActive'] = $SearchRow->isActive;
                $AllMasterProductUnits[$SearchRow->productUnitID]['CreateUserID'] = $SearchRow->createUserID;
                $AllMasterProductUnits[$SearchRow->productUnitID]['CreateUserName'] = $SearchRow->createUserName;

                $AllMasterProductUnits[$SearchRow->productUnitID]['CreateDate'] = $SearchRow->createDate;
           }
            
            return $AllMasterProductUnits;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException::MasterProductUnit::GetAllMasterProductUnits(). Stack Trace: '. $e->getTraceAsString());
            return $AllMasterProductUnits;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception: MasterProductUnit::GetAllMasterProductUnits() . Stack Trace: '. $e->getTraceAsString());
            return $AllMasterProductUnits;
        }
    }

    static function GetActiveMasterProductUnits()
	{
		$AllMasterProductUnits = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT * FROM aim_master_product_units WHERE isActive = 1;');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllMasterProductUnits;
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$AllMasterProductUnits[$SearchRow->productUnitID] = $SearchRow->productUnitName;
			}
			
			return $AllMasterProductUnits;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at MasterProductUnit::GetActiveMasterProductUnits(). Stack Trace: ' . $e->getTraceAsString());
			return $AllMasterProductUnits;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at MasterProductUnit::GetActiveMasterProductUnits(). Stack Trace: ' . $e->getTraceAsString());
			return $AllMasterProductUnits;
		}		
	}
    
	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//
	private function SaveDetails()
	{
		if ($this->ProductUnitID == 0)
		{
			$RSSave = $this->DBObject->Prepare('INSERT INTO aim_master_product_units (productUnitName, isActive, createUserID, createDate)
														VALUES (:|1, :|2, :|3, NOW());');
			$RSSave->Execute($this->ProductUnitName, $this->IsActive, $this->CreateUserID);

			$this->ProductUnitID = $RSSave->LastID;
		}
		else
		{
			$RSUpdate = $this->DBObject->Prepare('UPDATE aim_master_product_units
													SET	productUnitName = :|1,
														isActive = :|2
													WHERE productUnitID = :|3 LIMIT 1;');
			$RSUpdate->Execute($this->ProductUnitName, $this->IsActive, $this->ProductUnitID);
		}
		
		return true;
	}

	private function RemoveMasterProductUnit()
	{
		if(!isset($this->ProductUnitID)) 
		{
    		throw new ApplicationARException('', APP_AR_ERROR_DELETE_WITHOUT_ID);
		}
		
		$RSDeleteMasterProductUnit = $this->DBObject->Prepare('DELETE FROM aim_master_product_units WHERE productUnitID = :|1 LIMIT 1;');
		$RSDeleteMasterProductUnit->Execute($this->ProductUnitID);				
	}
	
	private function GetMasterProductUnitByID()
	{
		$RSMasterProductUnit = $this->DBObject->Prepare('SELECT * FROM aim_master_product_units WHERE productUnitID = :|1 LIMIT 1;');
		$RSMasterProductUnit->Execute($this->ProductUnitID);
		
		$MasterProductUnitRow = $RSMasterProductUnit->FetchRow();
		
		$this->SetAttributesFromDB($MasterProductUnitRow);				
	}
	
	private function SetAttributesFromDB($MasterProductUnitRow)
	{
		$this->ProductUnitID = $MasterProductUnitRow->productUnitID;
		$this->ProductUnitName = $MasterProductUnitRow->productUnitName;

		$this->IsActive = $MasterProductUnitRow->isActive;
		$this->CreateUserID = $MasterProductUnitRow->createUserID;
		$this->CreateDate = $MasterProductUnitRow->createDate;
	}	
}
?>
<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.db_connect.php');
error_reporting(E_ALL);

class FeeCollectionDetail
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	
	private $FeeCollectionDetailID;
	private $FeeCollectionID;

	private $StudentFeeStructureID;
	private $AmountPaid;
	
	// PUBLIC METHODS START HERE	//
	public function __construct($FeeCollectionDetailID = 0)
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;
		
		if($FeeCollectionDetailID != 0)
		{
			$this->FeeCollectionDetailID = $FeeCollectionDetailID;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetFeeCollectionDetailByID();
		}
		else
		{
			//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
			$this->FeeCollectionDetailID = 0;
			$this->FeeCollectionID = 0;

			$this->StudentFeeStructureID = 0;
			$this->AmountPaid = 0;
		}
	}
	
	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetFeeCollectionDetailID()
	{
		return $this->FeeCollectionDetailID;
	}
	
	public function GetFeeCollectionID()
	{
		return $this->FeeCollectionID;
	}
	public function SetFeeCollectionID($FeeCollectionID)
	{
		$this->FeeCollectionID = $FeeCollectionID;
	}

	public function GetStudentFeeStructureID()
	{
		return $this->StudentFeeStructureID;
	}
	public function SetStudentFeeStructureID($StudentFeeStructureID)
	{
		$this->StudentFeeStructureID = $StudentFeeStructureID;
	}

	public function GetAmountPaid()
	{
		return $this->AmountPaid;
	}
	public function SetAmountPaid($AmountPaid)
	{
		$this->AmountPaid = $AmountPaid;
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
		
	// END OF PUBLIC METHODS	//
	
	// START OF STATIC METHODS	//
	
	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//
	private function SaveDetails()
	{
		if ($this->FeeCollectionDetailID == 0)
		{
			$RSSave = $this->DBObject->Prepare('INSERT INTO afm_fee_collection_details (feeCollectionID, studentFeeStructureID, amountPaid)
														VALUES (:|1, :|2, :|3);');
		
			$RSSave->Execute($this->FeeCollectionID, $this->StudentFeeStructureID, $this->AmountPaid);
			
			$this->FeeCollectionDetailID = $RSSave->LastID;
		}
		else
		{
			$RSUpdate = $this->DBObject->Prepare('UPDATE afm_fee_collection_details
													SET	feeCollectionID = :|1
													WHERE feeCollectionDetailID = :|2;');
													
			$RSUpdate->Execute($this->FeeCollectionID, $this->FeeCollectionDetailID);
		}
		
		return true;
	}
	
	private function GetFeeCollectionDetailByID()
	{
		$RSFeeCollectionDetail = $this->DBObject->Prepare('SELECT * FROM afm_fee_collection_details WHERE feeCollectionDetailID = :|1;');
		$RSFeeCollectionDetail->Execute($this->FeeCollectionDetailID);
		
		$FeeCollectionDetailRow = $RSFeeCollectionDetail->FetchRow();
		
		$this->SetAttributesFromDB($FeeCollectionDetailRow);				
	}
	
	private function SetAttributesFromDB($FeeCollectionDetailRow)
	{
		$this->FeeCollectionDetailID = $FeeCollectionDetailRow->feeCollectionDetailID;
		$this->FeeCollectionID = $FeeCollectionDetailRow->feeCollectionID;

		$this->StudentFeeStructureID = $FeeCollectionDetailRow->studentFeeStructureID;
		$this->AmountPaid = $FeeCollectionDetailRow->amountPaid;
	}	
}
?>
<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.db_connect.php');
error_reporting(E_ALL);

class PreviousYearFeeDetail
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	
	private $PreviousYearFeeDetailID;

	private $StudentID;
	private $PayableAmount;
	private $PaidAmount;
	private $WaveOffDue;

	private $PreviousYearFeeDetails = array();
	
	// PUBLIC METHODS START HERE	//
	public function __construct($PreviousYearFeeDetailID = 0)
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;
		
		if($PreviousYearFeeDetailID != 0)
		{
			$this->PreviousYearFeeDetailID = $PreviousYearFeeDetailID;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetPreviousYearFeeDetailByID();
		}
		else
		{
			//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
			$this->PreviousYearFeeDetailID = 0;

			$this->StudentID = '';
			$this->PayableAmount = 0;
			$this->PaidAmount = 0;
			$this->WaveOffDue = 0;
			
			$this->PreviousYearFeeDetails = array();
		}
	}
	
	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetPreviousYearFeeDetailID()
	{
		return $this->PreviousYearFeeDetailID;
	}
	
	public function GetStudentID()
	{
		return $this->StudentID;
	}
	public function SetStudentID($StudentID)
	{
		$this->StudentID = $StudentID;
	}

	public function GetPayableAmount()
	{
		return $this->PayableAmount;
	}
	public function SetPayableAmount($PayableAmount)
	{
		$this->PayableAmount = $PayableAmount;
	}

	public function GetPaidAmount()
	{
		return $this->PaidAmount;
	}
	public function SetPaidAmount($PaidAmount)
	{
		$this->PaidAmount = $PaidAmount;
	}
	
	public function GetWaveOffDue()
	{
		return $this->WaveOffDue;
	}
	public function SetWaveOffDue($WaveOffDue)
	{
		$this->WaveOffDue = $WaveOffDue;
	}

	public function GetPreviousYearFeeDetails()
	{
		return $this->PreviousYearFeeDetails;
	}
	public function SetPreviousYearFeeDetails($PreviousYearFeeDetails)
	{
		$this->PreviousYearFeeDetails = $PreviousYearFeeDetails;
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
            $this->RemovePreviousYearFeeDetail();
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
		
	// END OF PUBLIC METHODS	//
	
	// START OF STATIC METHODS	//
	static function GetAllPreviousYearFeeDetails()
	{
		$AllPreviousYearFeeDetails = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT * FROM afm_previous_year_fee_details ORDER BY previousYearFeeDetailID;');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllPreviousYearFeeDetails; 
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$AllPreviousYearFeeDetails[$SearchRow->studentID]['PayableAmount'] = $SearchRow->payableAmount;
				$AllPreviousYearFeeDetails[$SearchRow->studentID]['PaidAmount'] = $SearchRow->paidAmount;				
				$AllPreviousYearFeeDetails[$SearchRow->studentID]['WaveOffDue'] = $SearchRow->waveOffDue;				
			}
			
			return $AllPreviousYearFeeDetails;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at PreviousYearFeeDetail::GetAllPreviousYearFeeDetails(). Stack Trace: ' . $e->getTraceAsString());
			return $AllPreviousYearFeeDetails;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at PreviousYearFeeDetail::GetAllPreviousYearFeeDetails(). Stack Trace: ' . $e->getTraceAsString());
			return $AllPreviousYearFeeDetails;
		}
	}
	
	static function GetStudentPreviousYearDueDetails()
	{
		$StudentPreviousYearDueDetails = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT apyfd.*, asd.firstName, asd.lastName, ac.className, ac.classSymbol, asm.sectionName, apd.fatherFirstName, apd.fatherLastName, apd.fatherMobileNumber
												FROM afm_previous_year_fee_details apyfd 
												INNER JOIN asa_student_details asd ON asd.studentID = apyfd.studentID 
												INNER JOIN asa_students ass ON ass.studentID = asd.studentID 
												INNER JOIN asa_parent_details apd ON apd.parentID = ass.parentID 
												INNER JOIN asa_class_sections acs ON acs.classSectionID = ass.classSectionID 
												INNER JOIN asa_classes ac ON ac.classID = acs.classID   
												INNER JOIN asa_section_master asm ON asm.sectionMasterID = acs.sectionMasterID 
												ORDER BY previousYearFeeDetailID;');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $StudentPreviousYearDueDetails; 
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{	
				$StudentPreviousYearDueDetails[$SearchRow->studentID]['FirstName'] = $SearchRow->firstName;				
				$StudentPreviousYearDueDetails[$SearchRow->studentID]['LastName'] = $SearchRow->lastName;

				$StudentPreviousYearDueDetails[$SearchRow->studentID]['ClassName'] = $SearchRow->className;	
				$StudentPreviousYearDueDetails[$SearchRow->studentID]['ClassSymbol'] = $SearchRow->classSymbol;	
				$StudentPreviousYearDueDetails[$SearchRow->studentID]['SectionName'] = $SearchRow->sectionName;

				$StudentPreviousYearDueDetails[$SearchRow->studentID]['FatherFirstName'] = $SearchRow->fatherFirstName;			
				$StudentPreviousYearDueDetails[$SearchRow->studentID]['FatherLastName'] = $SearchRow->fatherLastName;
							
				$StudentPreviousYearDueDetails[$SearchRow->studentID]['FatherMobileNumber'] = $SearchRow->fatherMobileNumber;	
				$StudentPreviousYearDueDetails[$SearchRow->studentID]['PayableAmount'] = $SearchRow->payableAmount;
				$StudentPreviousYearDueDetails[$SearchRow->studentID]['PaidAmount'] = $SearchRow->paidAmount;	
				$StudentPreviousYearDueDetails[$SearchRow->studentID]['WaveOffDue'] = $SearchRow->waveOffDue;	

			}
			
			return $StudentPreviousYearDueDetails;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at PreviousYearFeeDetail::GetStudentPreviousYearDueDetails(). Stack Trace: ' . $e->getTraceAsString());
			return $StudentPreviousYearDueDetails;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at PreviousYearFeeDetail::GetStudentPreviousYearDueDetails(). Stack Trace: ' . $e->getTraceAsString());
			return $StudentPreviousYearDueDetails;
		}
	}
	
	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//
	private function SaveDetails()
	{
		if (count($this->PreviousYearFeeDetails) > 0)
		{
			foreach ($this->PreviousYearFeeDetails as $StudentID => $Details) 
			{
				$RSSearch = $this->DBObject->Prepare('SELECT * FROM afm_previous_year_fee_details 
																	WHERE studentID = :|1 LIMIT 1;');
				$RSSearch->Execute($StudentID);
				
				if ($RSSearch->Result->num_rows > 0)
				{
					if ($Details['PayableAmount'] <= 0) 
					{
						$RSDelete = $this->DBObject->Prepare('DELETE FROM afm_previous_year_fee_details WHERE studentID = :|1 LIMIT 1;');
        				$RSDelete->Execute($StudentID);  
					}
					else
					{
						$RSUpdate = $this->DBObject->Prepare('UPDATE afm_previous_year_fee_details
																SET	payableAmount = :|1,
																	waveOffDue = :|2
																WHERE studentID = :|3 LIMIT 1;');
						$RSUpdate->Execute($Details['PayableAmount'], $Details['WaveOffDue'], $StudentID); 
					}	
				}
				else if ($Details['PayableAmount'] > 0)
				{
					$RSSave = $this->DBObject->Prepare('INSERT INTO afm_previous_year_fee_details (studentID, payableAmount, paidAmount, waveOffDue)
														VALUES (:|1, :|2, :|3, :|4);');
				
					$RSSave->Execute($StudentID, $Details['PayableAmount'], $this->PaidAmount, $Details['WaveOffDue']);
				}
			}
		}
		
		return true;
	}

	private function RemovePreviousYearFeeDetail()
    {
        if(!isset($this->PreviousYearFeeDetailID)) 
        {
            throw new ApplicationARException('', APP_AR_ERROR_DELETE_WITHOUT_ID);
        }
        
        $RSDeletePreviousYearFeeDetail = $this->DBObject->Prepare('DELETE FROM afm_previous_year_fee_details WHERE previousYearFeeDetailID = :|1 LIMIT 1;');
        $RSDeletePreviousYearFeeDetail->Execute($this->PreviousYearFeeDetailID);  

        return true;              
    }
	
	private function GetPreviousYearFeeDetailByID()
	{
		$RSPreviousYearFeeDetail = $this->DBObject->Prepare('SELECT * FROM afm_previous_year_fee_details WHERE previousYearFeeDetailID = :|1 LIMIT 1;');
		$RSPreviousYearFeeDetail->Execute($this->PreviousYearFeeDetailID);
		
		$PreviousYearFeeDetailRow = $RSPreviousYearFeeDetail->FetchRow();
		
		$this->SetAttributesFromDB($PreviousYearFeeDetailRow);				
	}
	
	private function SetAttributesFromDB($PreviousYearFeeDetailRow)
	{
		$this->PreviousYearFeeDetailID = $PreviousYearFeeDetailRow->previousYearFeeDetailID;

		$this->StudentID = $PreviousYearFeeDetailRow->studentID;
		$this->PayableAmount = $PreviousYearFeeDetailRow->payableAmount;
		$this->PaidAmount = $PreviousYearFeeDetailRow->paidAmount;
	}	
}
?>
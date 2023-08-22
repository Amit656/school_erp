<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.db_connect.php');
error_reporting(E_ALL);

class BooksFine
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	
	private $BooksFineID;
	private $BookIssueID;

	private $FineType;
	private $FineAmount;
	private $Description;

	private $IsPaid;
	private $PaidDate;

	private $PaymentReceivedBy;
	private $PaymentReceivedOn;

	private $CreateUserID;
	private $CreateDate;
	
	// PUBLIC METHODS START HERE	//
	public function __construct($BooksFineID = 0)
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;
		
		if($BooksFineID != 0)
		{
			$this->BooksFineID = $BooksFineID;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetBooksFineByID();
		}
		else
		{
			//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
			$this->BooksFineID = 0;
			$this->BookIssueID = '';

			$this->FineType = '';
			$this->FineAmount = 0;
			$this->Description = '';

			$this->IsPaid = 0;
			$this->PaidDate = '0000-00-00';

			$this->PaymentReceivedBy = 0;
			$this->PaymentReceivedOn = '0000-00-00 00:00:00';

			$this->CreateUserID = 0;
			$this->CreateDate = '0000-00-00 00:00:00';
		}
	}
	
	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetBooksFineID()
	{
		return $this->BooksFineID;
	}
	
	public function GetBookIssueID()
	{
		return $this->BookIssueID;
	}
	public function SetBookIssueID($BookIssueID)
	{
		$this->BookIssueID = $BookIssueID;
	}
	
	public function GetFineType()
	{
		return $this->FineType;
	}
	public function SetFineType($FineType)
	{
		$this->FineType = $FineType;
	}
	
	public function GetFineAmount()
	{
		return $this->FineAmount;
	}
	public function SetFineAmount($FineAmount)
	{
		$this->FineAmount = $FineAmount;
	}
	
	public function GetDescription()
	{
		return $this->Description;
	}
	public function SetDescription($Description)
	{
		$this->Description = $Description;
	}
	
	public function GetIsPaid()
	{
		return $this->IsPaid;
	}
	public function SetIsPaid($IsPaid)
	{
		$this->IsPaid = $IsPaid;
	}
	
	public function GetPaidDate()
	{
		return $this->PaidDate;
	}
	public function SetPaidDate($PaidDate)
	{
		$this->PaidDate = $PaidDate;
	}
	
	public function GetPaymentReceivedBy()
	{
		return $this->PaymentReceivedBy;
	}
	public function SetPaymentReceivedBy($PaymentReceivedBy)
	{
		$this->PaymentReceivedBy = $PaymentReceivedBy;
	}
	
	public function GetPaymentReceivedOn()
	{
		return $this->PaymentReceivedOn;
	}
	public function SetPaymentReceivedOn($PaymentReceivedOn)
	{
		$this->PaymentReceivedOn = $PaymentReceivedOn;
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
		
	// END OF PUBLIC METHODS	//
	
	// START OF STATIC METHODS	//

	static function SearchBooksFine(&$TotalRecords = 0, $GetTotalsOnly = false, $Filters = array(), $Start = 0, $Limit = 100)
	{
		$AllBooksFineDetails = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$Conditions = array();
			$ConditionByUserName = '';	
			
			if (count($Filters) > 0)
			{	
					
				if (!empty($Filters['FineType']))
				{
					$Conditions[] = 'abf.fineType = '.$DBConnObject->RealEscapeVariable($Filters['FineType']);
				}

				if (!empty($Filters['UserType']))
				{
					$Conditions[] = 'abi.issuedToUserType = '.$DBConnObject->RealEscapeVariable($Filters['UserType']);
				}

				if (!empty($Filters['UserName']) && $Filters['UserType'] == 'Student')
				{					
					$ConditionByUserName = 'AND CONCAT(asd.firstName, " ", asd.lastName) LIKE ' . $DBConnObject->RealEscapeVariable('%' . $Filters['UserName'] . '%');
				}
				else if (!empty($Filters['UserName']) && $Filters['UserType'] == 'Teaching' || $Filters['UserType'] == 'NonTeaching')
				{					
					$ConditionByUserName = 'AND CONCAT(abs.firstName, " ", abs.lastName) LIKE ' . $DBConnObject->RealEscapeVariable('%' . $Filters['UserName'] . '%');
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
													FROM alm_books_fine abf
													INNER JOIN alm_book_issue abi ON abf.bookIssueID = abi.bookIssueID 
													INNER JOIN alm_books_copies abc ON abc.booksCopyID = abi.booksCopyID 
													INNER JOIN alm_books ab ON ab.bookID = abc.bookID 
													INNER JOIN alm_book_categories abct ON abct.bookCategoryID = ab.bookCategoryID
													INNER JOIN users u ON abi.createUserID = u.userID 
													'. $QueryString .';');
				$RSTotal->Execute();
				
				$TotalRecords = $RSTotal->FetchRow()->totalRecords;
				return;
			}
			
			$RSSearch = $DBConnObject->Prepare('SELECT abf.*, abi.*, ab.bookID, abct.bookCategoryName, ab.bookName, usr.userName AS retunedReceivedBy, u.userName AS createUserName 
												FROM alm_books_fine abf
												INNER JOIN alm_book_issue abi ON abf.bookIssueID = abi.bookIssueID 
												INNER JOIN alm_books_copies abc ON abc.booksCopyID = abi.booksCopyID 
												INNER JOIN alm_books ab ON ab.bookID = abc.bookID 
												INNER JOIN alm_book_categories abct ON abct.bookCategoryID = ab.bookCategoryID
												INNER JOIN users u ON abi.createUserID = u.userID  
												INNER JOIN users usr ON abi.retunedReceivedBy = usr.userID  
												'. $QueryString .' 
												ORDER BY ab.bookName LIMIT ' . (int) $Start . ', ' . (int) $Limit . ';');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllBooksFineDetails; 
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{				
				$AllBooksFineDetails[$SearchRow->booksFineID]['BookName'] = $SearchRow->bookName .' ( Copy '. $SearchRow->booksCopyID .')';
				$AllBooksFineDetails[$SearchRow->booksFineID]['BooksCopyID'] = $SearchRow->booksCopyID;
								
				$AllBooksFineDetails[$SearchRow->booksFineID]['UserType'] = $SearchRow->issuedToUserType;
				$AllBooksFineDetails[$SearchRow->booksFineID]['IssueDate'] = $SearchRow->issueDate;
				$AllBooksFineDetails[$SearchRow->booksFineID]['ActualReturnDate'] = $SearchRow->actualReturnDate;
				$AllBooksFineDetails[$SearchRow->booksFineID]['FineType'] = $SearchRow->fineType;
				$AllBooksFineDetails[$SearchRow->booksFineID]['FineAmount'] = $SearchRow->fineAmount;
				$AllBooksFineDetails[$SearchRow->booksFineID]['IsPaid'] = $SearchRow->isPaid;
				$AllBooksFineDetails[$SearchRow->booksFineID]['RetunedReceivedBy'] = $SearchRow->retunedReceivedBy;

				if ($SearchRow->issuedToUserType == 'Student') 
				{
					$RSSearchStudent = $DBConnObject->Prepare('SELECT asd.firstName, asd.lastName, ac.className, asm.sectionName FROM asa_student_details asd
															INNER JOIN asa_students ass ON ass.studentID = asd.studentID
															INNER JOIN asa_class_sections acs ON ass.classSectionID = acs.classSectionID
															INNER JOIN asa_classes ac ON acs.classID = ac.classID
															INNER JOIN asa_section_master asm ON acs.sectionMasterID = asm.sectionMasterID
															WHERE asd.studentID = :|1 '. $ConditionByUserName .';');
					$RSSearchStudent->Execute($SearchRow->issuedToID);

					if ($RSSearchStudent->Result->num_rows > 0)
					{
						$SearchStudentRow = $RSSearchStudent->FetchRow();

						$AllBooksFineDetails[$SearchRow->booksFineID]['UserName'] = $SearchStudentRow->firstName . ' ' . $SearchStudentRow->lastName;
						$AllBooksFineDetails[$SearchRow->booksFineID]['Class'] = $SearchStudentRow->className . ' ' . $SearchStudentRow->sectionName;					
					}
				}
				else if ($SearchRow->issuedToUserType == 'Teaching')
				{
					$RSSearchBranchStaff = $DBConnObject->Prepare('SELECT abs.firstName, abs.lastName FROM asa_branch_staff abs															WHERE abs.branchStaffID = :|1 '. $ConditionByUserName .';');
					$RSSearchBranchStaff->Execute($SearchRow->issuedToID);

					if ($RSSearchBranchStaff->Result->num_rows > 0)
					{
						$SearchBranchStaffRow = $RSSearchBranchStaff->FetchRow();

						$AllBooksFineDetails[$SearchRow->booksFineID]['UserName'] = $SearchBranchStaffRow->firstName . ' ' . $SearchBranchStaffRow->lastName;			
						$AllBooksFineDetails[$SearchRow->booksFineID]['Class'] = '';			
					}
				}				

				$AllBooksFineDetails[$SearchRow->booksFineID]['CreateUserID'] = $SearchRow->createUserID;
				$AllBooksFineDetails[$SearchRow->booksFineID]['CreateUserName'] = $SearchRow->createUserName;
				$AllBooksFineDetails[$SearchRow->booksFineID]['CreateDate'] = $SearchRow->createDate;					
			}
			
			return $AllBooksFineDetails;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at BooksFine::SearchBooksFine(). Stack Trace: ' . $e->getTraceAsString());
			return $AllBooksFineDetails;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at BooksFine::SearchBooksFine(). Stack Trace: ' . $e->getTraceAsString());
			return $AllBooksFineDetails;
		}
	}
	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//
	private function SaveDetails()
	{
		if ($this->BooksFineID == 0)
		{
			$RSSave = $this->DBObject->Prepare('INSERT INTO alm_books_fine (bookIssueID, fineType, fineAmount, description, isPaid, paidDate, 
																			paymentReceivedBy, paymentReceivedOn, createUserID, createDate)
														VALUES (:|1, :|2, :|3, :|4, :|5, :|6, :|7, :|8, :|9, NOW());');
		
			$RSSave->Execute($this->BookIssueID, $this->FineType, $this->FineAmount, $this->Description, $this->IsPaid, $this->PaidDate,
							$this->PaymentReceivedBy, $this->PaymentReceivedOn, $this->CreateUserID);
			
			$this->BooksFineID = $RSSave->LastID;
		}
		else
		{
			$RSUpdate = $this->DBObject->Prepare('UPDATE alm_books_fine
													SET	bookIssueID = :|1,														
														fineType = :|2, 
														fineAmount = :|3, 
														description = :|4, 
														isPaid = :|5, 
														paidDate = :|6, 
														paymentReceivedBy = :|7, 
														paymentReceivedOn = :|8
													WHERE booksFineID = :|9;');
													
			$RSUpdate->Execute($this->BookIssueID, $this->FineType, $this->FineAmount, $this->Description, $this->IsPaid, $this->PaidDate,
								$this->PaymentReceivedBy, $this->PaymentReceivedOn, $this->BooksFineID);
		}
		
		return true;
	}
	
	private function GetBooksFineByID()
	{
		$RSBooksFine = $this->DBObject->Prepare('SELECT * FROM alm_books_fine WHERE booksFineID = :|1 LIMIT 1;');
		$RSBooksFine->Execute($this->BooksFineID);
		
		$BooksFineRow = $RSBooksFine->FetchRow();
		
		$this->SetAttributesFromDB($BooksFineRow);				
	}
	
	private function SetAttributesFromDB($BooksFineRow)
	{
		$this->BooksFineID = $BooksFineRow->booksFineID;
		$this->BookIssueID = $BooksFineRow->bookIssueID;

		$this->FineType = $BooksFineRow->fineType;
		$this->FineAmount = $BooksFineRow->fineAmount;
		$this->Description = $BooksFineRow->description;

		$this->IsPaid = $BooksFineRow->isPaid;
		$this->PaidDate = $BooksFineRow->paidDate;

		$this->PaymentReceivedBy = $BooksFineRow->paymentReceivedBy;
		$this->PaymentReceivedOn = $BooksFineRow->paymentReceivedOn;

		$this->CreateUserID = $BooksFineRow->createUserID;
		$this->CreateDate = $BooksFineRow->createDate;
	}	
}
?>
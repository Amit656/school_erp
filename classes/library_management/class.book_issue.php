<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.db_connect.php');
error_reporting(E_ALL);

class BookIssue
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	
	private $BookIssueID;
	private $BooksCopyID;

	private $IssuedToUserType;
	private $IssuedToID;
	private $IssueDate;
	private $ExpectedReturnDate;

	private $IsReturned;
	private $ActualReturnDate;
	private $RetunedReceivedBy;
	private $RetunedReceivedOn;

	private $CreateUserID;
	private $CreateDate;
	
	// PUBLIC METHODS START HERE	//
	public function __construct($BookIssueID = 0)
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;
		
		if($BookIssueID != 0)
		{
			$this->BookIssueID = $BookIssueID;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetBookIssueByID();
		}
		else
		{
			//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
			$this->BookIssueID = 0;
			$this->BooksCopyID = 0;

			$this->IssuedToUserType = '';
			$this->IssuedToID = 0;
			$this->IssueDate = '0000-00-00';
			$this->ExpectedReturnDate = '0000-00-00';

			$this->IsReturned = 0;
			$this->ActualReturnDate = '0000-00-00';
			$this->RetunedReceivedBy = 0;
			$this->RetunedReceivedOn = '0000-00-00 00:00:00';

			$this->CreateUserID = 0;
			$this->CreateDate = '0000-00-00 00:00:00';
		}
	}
	
	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetBookIssueID()
	{
		return $this->BookIssueID;
	}
	
	public function GetBooksCopyID()
	{
		return $this->BooksCopyID;
	}
	public function SetBooksCopyID($BooksCopyID)
	{
		$this->BooksCopyID = $BooksCopyID;
	}
		
	public function GetIssuedToUserType()
	{
		return $this->IssuedToUserType;
	}
	public function SetIssuedToUserType($IssuedToUserType)
	{
		$this->IssuedToUserType = $IssuedToUserType;
	}
	
	public function GetIssuedToID()
	{
		return $this->IssuedToID;
	}
	public function SetIssuedToID($IssuedToID)
	{
		$this->IssuedToID = $IssuedToID;
	}
	
	public function GetIssueDate()
	{
		return $this->IssueDate;
	}
	public function SetIssueDate($IssueDate)
	{
		$this->IssueDate = $IssueDate;
	}
	
	public function GetExpectedReturnDate()
	{
		return $this->ExpectedReturnDate;
	}
	public function SetExpectedReturnDate($ExpectedReturnDate)
	{
		$this->ExpectedReturnDate = $ExpectedReturnDate;
	}
	
	public function GetIsReturned()
	{
		return $this->IsReturned;
	}
	public function SetIsReturned($IsReturned)
	{
		$this->IsReturned = $IsReturned;
	}
	
	public function GetActualReturnDate()
	{
		return $this->ActualReturnDate;
	}
	public function SetActualReturnDate($ActualReturnDate)
	{
		$this->ActualReturnDate = $ActualReturnDate;
	}
	
	public function GetRetunedReceivedBy()
	{
		return $this->RetunedReceivedBy;
	}
	public function SetRetunedReceivedBy($RetunedReceivedBy)
	{
		$this->RetunedReceivedBy = $RetunedReceivedBy;
	}
	
	public function GetRetunedReceivedOn()
	{
		return $this->RetunedReceivedOn;
	}
	public function SetRetunedReceivedOn($RetunedReceivedOn)
	{
		$this->RetunedReceivedOn = $RetunedReceivedOn;
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
	
	static function GetAllreadyIssuedBooksToUser($UserType, $IssuedToID)
	{
		$AllreadyIssuedBooksAtPresentTime = 0;
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT count(*) AS totalRecords FROM alm_book_issue 
												WHERE issuedToUserType = :|1 AND issuedToID = :|2 AND isReturned = 0;');
			$RSSearch->Execute($UserType, $IssuedToID);
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllreadyIssuedBooksAtPresentTime;
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$AllreadyIssuedBooksAtPresentTime = $SearchRow->totalRecords;
			}
			
			return $AllreadyIssuedBooksAtPresentTime;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at BookIssue::GetAllreadyIssuedBooksToUser(). Stack Trace: ' . $e->getTraceAsString());
			return $AllreadyIssuedBooksAtPresentTime;
		}
		catch (Exception $e) 
		{
			error_log('DEBUG: Exception at BookIssue::GetAllreadyIssuedBooksToUser(). Stack Trace: ' . $e->getTraceAsString());
			return $AllreadyIssuedBooksAtPresentTime;
		}		
	}

	static function GetIssueBookDetails($BooksCopyID)
	{
		$IssueBookDetails = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearchIssueBookDetail = $DBConnObject->Prepare('SELECT abi.*, ab.bookName, ab.price FROM alm_book_issue abi
																INNER JOIN alm_books_copies abc ON abc.booksCopyID = abi.booksCopyID
																INNER JOIN alm_books ab ON ab.bookID = abc.bookID
																WHERE abi.booksCopyID = :|1 AND abi.isReturned = 0;');
			$RSSearchIssueBookDetail->Execute($BooksCopyID);

			$SearchIssueBookDetailRow = $RSSearchIssueBookDetail->FetchRow();

			if ($RSSearchIssueBookDetail->Result->num_rows <= 0)
			{
				return $IssueBookDetails;
			}
			
			$IssueBookDetails[$SearchIssueBookDetailRow->bookIssueID]['BookName'] = $SearchIssueBookDetailRow->bookName . ' ( ' . $SearchIssueBookDetailRow->booksCopyID . ' ) ';
			$IssueBookDetails[$SearchIssueBookDetailRow->bookIssueID]['BookPrice'] = $SearchIssueBookDetailRow->price;
			$IssueBookDetails[$SearchIssueBookDetailRow->bookIssueID]['IssueDate'] = $SearchIssueBookDetailRow->issueDate;
			$IssueBookDetails[$SearchIssueBookDetailRow->bookIssueID]['ExpectedReturnDate'] = $SearchIssueBookDetailRow->expectedReturnDate;
			$IssueBookDetails[$SearchIssueBookDetailRow->bookIssueID]['IssuedToUserType'] = $SearchIssueBookDetailRow->issuedToUserType;

			$IssueBookDetails[$SearchIssueBookDetailRow->bookIssueID]['Class'] = '';

			if ($SearchIssueBookDetailRow->issuedToUserType == 'Student') 
			{
				$RSSearchStudent = $DBConnObject->Prepare('SELECT asd.firstName, asd.lastName, ac.className, asm.sectionName FROM asa_student_details asd
														INNER JOIN asa_students ass ON ass.studentID = asd.studentID
														INNER JOIN asa_class_sections acs ON ass.classSectionID = acs.classSectionID
														INNER JOIN asa_classes ac ON acs.classID = ac.classID
														INNER JOIN asa_section_master asm ON acs.sectionMasterID = asm.sectionMasterID
														WHERE asd.studentID = :|1;');
				$RSSearchStudent->Execute($SearchIssueBookDetailRow->issuedToID);

				if ($RSSearchStudent->Result->num_rows > 0)
				{
					$SearchStudentRow = $RSSearchStudent->FetchRow();

					$IssueBookDetails[$SearchIssueBookDetailRow->bookIssueID]['UserName'] = $SearchStudentRow->firstName . ' ' . $SearchStudentRow->lastName;
					$IssueBookDetails[$SearchIssueBookDetailRow->bookIssueID]['Class'] = $SearchStudentRow->className . ' ' . $SearchStudentRow->sectionName;					
				}
			}
			else if ($SearchIssueBookDetailRow->issuedToUserType == 'Teaching')
			{
				$RSSearchBranchStaff = $DBConnObject->Prepare('SELECT abs.firstName, abs.lastName FROM asa_branch_staff abs															WHERE abs.branchStaffID = :|1;');
				$RSSearchBranchStaff->Execute($SearchIssueBookDetailRow->issuedToID);

				if ($RSSearchBranchStaff->Result->num_rows > 0)
				{
					$SearchBranchStaffRow = $RSSearchBranchStaff->FetchRow();

					$IssueBookDetails[$SearchIssueBookDetailRow->bookIssueID]['UserName'] = $SearchBranchStaffRow->firstName . ' ' . $SearchBranchStaffRow->lastName;						
				}
			}						
			
			return $IssueBookDetails;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at BookIssue::GetIssueBookDetails(). Stack Trace: ' . $e->getTraceAsString());
			return $IssueBookDetails;
		}
		catch (Exception $e) 
		{
			error_log('DEBUG: Exception at BookIssue::GetIssueBookDetails(). Stack Trace: ' . $e->getTraceAsString());
			return $IssueBookDetails;
		}		
	}

	static function SearchIssuedBooks(&$TotalRecords = 0, $GetTotalsOnly = false, $Filters = array(), $Start = 0, $Limit = 100)
	{
		$AllIssuedBooks = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$Conditions = array();			
			
			if (count($Filters) > 0)
			{	
					
				if (!empty($Filters['ParentCategoryID']))
				{
					$Conditions[] = 'abct.parentCategoryID = '.$DBConnObject->RealEscapeVariable($Filters['ParentCategoryID']);
				}

				if (!empty($Filters['BookCategoryID']))
				{
					$Conditions[] = 'ab.bookCategoryID = '.$DBConnObject->RealEscapeVariable($Filters['BookCategoryID']);
				}

				if (!empty($Filters['BookName']))
				{
					$Conditions[] = 'ab.bookName LIKE '. $DBConnObject->RealEscapeVariable($Filters['BookName'] . '%');
				}
				
				if (!empty($Filters['IsReturned']))
				{
					if ($Filters['IsReturned'] == 1) //active records
					{
						$Conditions[] = 'abi.isReturned = 1';
					}
					else if ($Filters['IsReturned'] == 2) //non active records
					{
						$Conditions[] = 'abi.isReturned = 0';
					}
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
													FROM alm_book_issue abi
													INNER JOIN alm_books_copies abc ON abc.booksCopyID = abi.booksCopyID 
													INNER JOIN alm_books ab ON ab.bookID = abc.bookID 
													INNER JOIN alm_book_categories abct ON abct.bookCategoryID = ab.bookCategoryID
													INNER JOIN users u ON abi.createUserID = u.userID 
													'. $QueryString .';');
				$RSTotal->Execute();
				
				$TotalRecords = $RSTotal->FetchRow()->totalRecords;
				return;
			}
			
			$RSSearch = $DBConnObject->Prepare('SELECT abi.*, ab.bookID, abct.bookCategoryName, ab.bookName, u.userName AS createUserName 
												FROM alm_book_issue abi
												INNER JOIN alm_books_copies abc ON abc.booksCopyID = abi.booksCopyID 
												INNER JOIN alm_books ab ON ab.bookID = abc.bookID 
												INNER JOIN alm_book_categories abct ON abct.bookCategoryID = ab.bookCategoryID
												INNER JOIN users u ON abi.createUserID = u.userID 
												'. $QueryString .' 
												ORDER BY ab.bookName LIMIT ' . (int) $Start . ', ' . (int) $Limit . ';');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllIssuedBooks; 
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{				
				$RSSearchAuthor = $DBConnObject->Prepare('SELECT GROUP_CONCAT(aa.authorName SEPARATOR ", ") AS authorName
															FROM alm_book_authors aba
															INNER JOIN alm_authors aa ON aa.authorID = aba.authorID
															WHERE aba.bookId = :|1;');
				$RSSearchAuthor->Execute($SearchRow->bookID);

				$GetAuthorName = '';
				$GetAuthorName = $RSSearchAuthor->FetchRow()->authorName ;

				if ($GetAuthorName != '' && $RSSearchAuthor->Result->num_rows > 0)
				{
					$AllIssuedBooks[$SearchRow->bookIssueID]['BookName'] = $SearchRow->bookName .' ( Copy '. $SearchRow->booksCopyID .')';
					$AllIssuedBooks[$SearchRow->bookIssueID]['BooksCopyID'] = $SearchRow->booksCopyID;
					$AllIssuedBooks[$SearchRow->bookIssueID]['BookCategoryName'] = $SearchRow->bookCategoryName;
					$AllIssuedBooks[$SearchRow->bookIssueID]['AuthorName'] = $GetAuthorName;					
					
					$AllIssuedBooks[$SearchRow->bookIssueID]['UserType'] = $SearchRow->issuedToUserType;
					$AllIssuedBooks[$SearchRow->bookIssueID]['IssueDate'] = $SearchRow->issueDate;
					$AllIssuedBooks[$SearchRow->bookIssueID]['ActualReturnDate'] = $SearchRow->actualReturnDate;
					$AllIssuedBooks[$SearchRow->bookIssueID]['IsReturned'] = $SearchRow->isReturned;

					if ($SearchRow->issuedToUserType == 'Student') 
					{
						$RSSearchStudent = $DBConnObject->Prepare('SELECT asd.firstName, asd.lastName, ac.className, asm.sectionName FROM asa_student_details asd
																INNER JOIN asa_students ass ON ass.studentID = asd.studentID
																INNER JOIN asa_class_sections acs ON ass.classSectionID = acs.classSectionID
																INNER JOIN asa_classes ac ON acs.classID = ac.classID
																INNER JOIN asa_section_master asm ON acs.sectionMasterID = asm.sectionMasterID
																WHERE asd.studentID = :|1;');
						$RSSearchStudent->Execute($SearchRow->issuedToID);

						if ($RSSearchStudent->Result->num_rows > 0)
						{
							$SearchStudentRow = $RSSearchStudent->FetchRow();

							$AllIssuedBooks[$SearchRow->bookIssueID]['UserName'] = $SearchStudentRow->firstName . ' ' . $SearchStudentRow->lastName;
							$AllIssuedBooks[$SearchRow->bookIssueID]['Class'] = $SearchStudentRow->className . ' ' . $SearchStudentRow->sectionName;					
						}
					}
					else if ($SearchRow->issuedToUserType == 'Teaching')
					{
						$RSSearchBranchStaff = $DBConnObject->Prepare('SELECT abs.firstName, abs.lastName FROM asa_branch_staff abs															WHERE abs.branchStaffID = :|1;');
						$RSSearchBranchStaff->Execute($SearchRow->issuedToID);

						if ($RSSearchBranchStaff->Result->num_rows > 0)
						{
							$SearchBranchStaffRow = $RSSearchBranchStaff->FetchRow();

							$AllIssuedBooks[$SearchRow->bookIssueID]['UserName'] = $SearchBranchStaffRow->firstName . ' ' . $SearchBranchStaffRow->lastName;			
							$AllIssuedBooks[$SearchRow->bookIssueID]['Class'] = '';			
						}
					}				

					$AllIssuedBooks[$SearchRow->bookIssueID]['CreateUserID'] = $SearchRow->createUserID;
					$AllIssuedBooks[$SearchRow->bookIssueID]['CreateUserName'] = $SearchRow->createUserName;
					$AllIssuedBooks[$SearchRow->bookIssueID]['CreateDate'] = $SearchRow->createDate;					
				}				
			}
			
			return $AllIssuedBooks;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at BookIssue::SearchIssuedBooks(). Stack Trace: ' . $e->getTraceAsString());
			return $AllIssuedBooks;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at BookIssue::SearchIssuedBooks(). Stack Trace: ' . $e->getTraceAsString());
			return $AllIssuedBooks;
		}
	}

	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//
	private function SaveDetails()
	{
		if ($this->BookIssueID == 0)
		{
			$RSSave = $this->DBObject->Prepare('INSERT INTO alm_book_issue (booksCopyID, issuedToUserType, issuedToID, issueDate, expectedReturnDate, 
																			isReturned, actualReturnDate, retunedReceivedBy, retunedReceivedOn, 
																			createUserID, createDate)
														VALUES (:|1, :|2, :|3, :|4, :|5, :|6, :|7, :|8, :|9, :|10, NOW());');
		
			$RSSave->Execute($this->BooksCopyID, $this->IssuedToUserType, $this->IssuedToID, $this->IssueDate, $this->ExpectedReturnDate, 
							$this->IsReturned, $this->ActualReturnDate, $this->RetunedReceivedBy, $this->RetunedReceivedOn, $this->CreateUserID);
			
			$this->BookIssueID = $RSSave->LastID;
		}
		else
		{
			$RSUpdate = $this->DBObject->Prepare('UPDATE alm_book_issue
													SET	isReturned = :|1,
														actualReturnDate = :|2, 
														retunedReceivedBy = :|3, 
														retunedReceivedOn = NOW() 														
													WHERE bookIssueID = :|4;');
													
			$RSUpdate->Execute($this->IsReturned, $this->ActualReturnDate, $this->RetunedReceivedBy, $this->BookIssueID);
		}
		
		return true;
	}
	
	private function GetBookIssueByID()
	{
		$RSBookIssue = $this->DBObject->Prepare('SELECT * FROM alm_book_issue WHERE bookIssueID = :|1 LIMIT 1;');
		$RSBookIssue->Execute($this->BookIssueID);
		
		$BookIssueRow = $RSBookIssue->FetchRow();
		
		$this->SetAttributesFromDB($BookIssueRow);				
	}
	
	private function SetAttributesFromDB($BookIssueRow)
	{
		$this->BookIssueID = $BookIssueRow->bookIssueID;
		$this->BooksCopyID = $BookIssueRow->booksCopyID;

		$this->IssuedToUserType = $BookIssueRow->issuedToUserType;
		$this->IssuedToID = $BookIssueRow->issuedToID;
		$this->IssueDate = $BookIssueRow->issueDate;
		$this->ExpectedReturnDate = $BookIssueRow->expectedReturnDate;

		$this->IsReturned = $BookIssueRow->isReturned;
		$this->ActualReturnDate = $BookIssueRow->actualReturnDate;
		$this->RetunedReceivedBy = $BookIssueRow->retunedReceivedBy;
		$this->RetunedReceivedOn = $BookIssueRow->retunedReceivedOn;

		$this->CreateUserID = $BookIssueRow->createUserID;
		$this->CreateDate = $BookIssueRow->createDate;
	}	
}
?>
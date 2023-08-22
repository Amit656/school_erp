<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.db_connect.php');
error_reporting(E_ALL);

class Book
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	private $LastErrorCode;
	private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //
	
	private $BookID;
	private $BookCategoryID;
	private $ISBN;

	private $BookName;
	private $BookType;
	private $IssuableTo;
	private $TakeHomeAllowed;
	private $Volume;
	private $NumberOfPages;

	private $Price;
	private $PurchaseDate;
	private $Quantity;
	private $ShelfNumber;

	private $PublishedYear;
	private $Edition;
	private $PublisherID;

	private $IsActive;
	private $CreateUserID;
	private $CreateDate;

	private $ClassList = array();
	private $AuthorDetails = array();
	private $PublisherName;
	
	private $RemovedClassList = array();
	private $RemovedAuthorList = array();

	private $PreviousQuantity;
	// PUBLIC METHODS START HERE	//
	public function __construct($BookID = 0)
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;
		
		if($BookID != 0)
		{
			$this->BookID = $BookID;
			// SET THE VALUES FROM THE DATABASE.
			$this->GetBookByID();
		}
		else
		{
			//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
			$this->BookID = 0;
			$this->BookCategoryID = 0;
			$this->ISBN = '';

			$this->BookName = '';
			$this->BookType = '';
			$this->IssuableTo = '';
			$this->TakeHomeAllowed = 0;			
			$this->Volume = '';
			$this->NumberOfPages = 0;
			
			$this->Price = 0;
			$this->PurchaseDate = '';
			$this->Quantity = 0;
			$this->ShelfNumber = '';

			$this->PublishedYear = '';
			$this->Edition = '';
			$this->PublisherID = 0;

			$this->IsActive = 0;
			$this->CreateUserID = 0;
			$this->CreateDate = '0000-00-00 00:00:00';

			$this->ClassList = array();
			$this->AuthorDetails = array();
			$this->PublisherName = '';

			$this->RemovedClassList = array();
			$this->RemovedAuthorList = array();

			$this->PreviousQuantity = 0;
		}
	}
	
	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetBookID()
	{
		return $this->BookID;
	}
	
	public function GetBookCategoryID()
	{
		return $this->BookCategoryID;
	}
	public function SetBookCategoryID($BookCategoryID)
	{
		$this->BookCategoryID = $BookCategoryID;
	}
	
	public function GetISBN()
	{
		return $this->ISBN;
	}
	public function SetISBN($ISBN)
	{
		$this->ISBN = $ISBN;
	}

	public function GetBookName()
	{
		return $this->BookName;
	}
	public function SetBookName($BookName)
	{
		$this->BookName = $BookName;
	}
	
	public function GetBookType()
	{
		return $this->BookType;
	}
	public function SetBookType($BookType)
	{
		$this->BookType = $BookType;
	}
	
	public function GetIssuableTo()
	{
		return $this->IssuableTo;
	}
	public function SetIssuableTo($IssuableTo)
	{
		$this->IssuableTo = $IssuableTo;
	}

	public function GetTakeHomeAllowed()
	{
		return $this->TakeHomeAllowed;
	}
	public function SetTakeHomeAllowed($TakeHomeAllowed)
	{
		$this->TakeHomeAllowed = $TakeHomeAllowed;
	}
	
	public function GetVolume()
	{
		return $this->Volume;
	}
	public function SetVolume($Volume)
	{
		$this->Volume = $Volume;
	}

	public function GetNumberOfPages()
	{
		return $this->NumberOfPages;
	}
	public function SetNumberOfPages($NumberOfPages)
	{
		$this->NumberOfPages = $NumberOfPages;
	}
		
	public function GetPrice()
	{
		return $this->Price;
	}
	public function SetPrice($Price)
	{
		$this->Price = $Price;
	}
	
	public function GetPurchaseDate()
	{
		return $this->PurchaseDate;
	}
	public function SetPurchaseDate($PurchaseDate)
	{
		$this->PurchaseDate = $PurchaseDate;
	}
	
	public function GetQuantity()
	{
		return $this->Quantity;
	}
	public function SetQuantity($Quantity)
	{
		$this->Quantity = $Quantity;
	}
	
	public function GetShelfNumber()
	{
		return $this->ShelfNumber;
	}
	public function SetShelfNumber($ShelfNumber)
	{
		$this->ShelfNumber = $ShelfNumber;
	}
	
	public function GetPublishedYear()
	{
		return $this->PublishedYear;
	}
	public function SetPublishedYear($PublishedYear)
	{
		$this->PublishedYear = $PublishedYear;
	}
	
	public function GetEdition()
	{
		return $this->Edition;
	}
	public function SetEdition($Edition)
	{
		$this->Edition = $Edition;
	}
	
	public function GetPublisherID()
	{
		return $this->PublisherID;
	}
	public function SetPublisherID($PublisherID)
	{
		$this->PublisherID = $PublisherID;
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

	public function GetPublisherName()
	{
		return $this->PublisherName;
	}
	public function SetPublisherName($PublisherName)
	{
		$this->PublisherName = $PublisherName;
	}

	public function GetClassList()
	{
		return $this->ClassList;
	}
	public function SetClassList($ClassList)
	{
		$this->ClassList = $ClassList;
	}

	public function GetAuthorDetails()
	{
		return $this->AuthorDetails;
	}
	public function SetAuthorDetails($AuthorDetails)
	{
		$this->AuthorDetails = $AuthorDetails;
	}

	public function GetCreateDate()
	{
		return $this->CreateDate;
	}

	public function SetRemovedClassList($RemovedClassList)
	{
		$this->RemovedClassList = $RemovedClassList;
	}

	public function SetRemovedAuthorList($RemovedAuthorList)
	{
		$this->RemovedAuthorList = $RemovedAuthorList;
	}

	public function SetPreviousQuantity($PreviousQuantity)
	{
		$this->PreviousQuantity = $PreviousQuantity;
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
            $this->RemoveBook();
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
           /* $RSBookCount = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM alm_books WHERE bookID = :|1;');
            $RSBookCount->Execute($this->BookID);

            if ($RSBookCount->FetchRow()->totalRecords > 0) 
            {
                return true;
            }*/

            return false;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at Book::CheckDependencies(). Stack Trace: ' . $e->getTraceAsString());
            return true;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: ApplicationDBException at Book::CheckDependencies(). Stack Trace: ' . $e->getTraceAsString());
            return true;
        }       
    }	
	// END OF PUBLIC METHODS	//
	
	// START OF STATIC METHODS	//
	static function SearchPublishers($PublisherName)
	{
		$PublisherList = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT publisherID, publisherName FROM alm_publishers 
												WHERE isActive = 1 
												AND publisherName LIKE '. $DBConnObject->RealEscapeVariable($PublisherName . '%') .' 
												ORDER BY publisherName LIMIT 0, 20;');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $PublisherList;
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$PublisherList[$SearchRow->publisherID] = $SearchRow->publisherName;
			}
			
			return $PublisherList;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at Book::SearchPublishers(). Stack Trace: ' . $e->getTraceAsString());
			return $PublisherList;
		}
		catch (Exception $e) 
		{
			error_log('DEBUG: Exception at Book::SearchPublishers(). Stack Trace: ' . $e->getTraceAsString());
			return $PublisherList;
		}		
	}

	static function SearchAuthors($AuthorName)
	{
		$AuthorList = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT authorID, authorName FROM alm_authors 
												WHERE isActive = 1 
												AND authorName LIKE '. $DBConnObject->RealEscapeVariable($AuthorName . '%') .' 
												ORDER BY authorName LIMIT 0, 20;');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AuthorList;
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$AuthorList[$SearchRow->authorID] = $SearchRow->authorName;
			}
			
			return $AuthorList;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at Book::SearchAuthors(). Stack Trace: ' . $e->getTraceAsString());
			return $AuthorList;
		}
		catch (Exception $e) 
		{
			error_log('DEBUG: Exception at Book::SearchAuthors(). Stack Trace: ' . $e->getTraceAsString());
			return $AuthorList;
		}		
	}

	static function SearchBooks(&$TotalRecords = 0, $GetTotalsOnly = false, $Filters = array(), $Start = 0, $Limit = 100)
	{
		$AllBooks = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$Conditions = array();
			$AuthorName = '';
			
			if (count($Filters) > 0)
			{	
					
				if (!empty($Filters['ParentCategoryID']))
				{
					$Conditions[] = 'abc.parentCategoryID = '.$DBConnObject->RealEscapeVariable($Filters['ParentCategoryID']);
				}

				if (!empty($Filters['BookCategoryID']))
				{
					$Conditions[] = 'ab.bookCategoryID = '.$DBConnObject->RealEscapeVariable($Filters['BookCategoryID']);
				}

				if (!empty($Filters['BookName']))
				{
					$Conditions[] = 'ab.bookName LIKE '. $DBConnObject->RealEscapeVariable($Filters['BookName'] . '%');
				}

				if (!empty($Filters['AuthorName']))
				{
					$AuthorName = 'AND aa.authorName LIKE '. $DBConnObject->RealEscapeVariable($Filters['AuthorName'] . '%');
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
													FROM alm_books ab
													INNER JOIN alm_book_categories abc ON abc.bookCategoryID = ab.bookCategoryID 
													INNER JOIN alm_publishers ap ON ap.publisherID = ab.publisherID 
													INNER JOIN users u ON abc.createUserID = u.userID 
													'. $QueryString .';');
				$RSTotal->Execute();
				
				$TotalRecords = $RSTotal->FetchRow()->totalRecords;
				return;
			}
			
			$RSSearch = $DBConnObject->Prepare('SELECT ab.*, abc.bookCategoryName, ap.publisherName, u.userName AS createUserName 
												FROM alm_books ab
												INNER JOIN alm_book_categories abc ON abc.bookCategoryID = ab.bookCategoryID 
												INNER JOIN alm_publishers ap ON ap.publisherID = ab.publisherID 
												INNER JOIN users u ON abc.createUserID = u.userID 
												'. $QueryString .' 
												ORDER BY ab.bookName LIMIT ' . (int) $Start . ', ' . (int) $Limit . ';');
			$RSSearch->Execute();
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllBooks; 
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$RSSearchAuthor = $DBConnObject->Prepare('SELECT GROUP_CONCAT(aa.authorName SEPARATOR ", ") AS authorName
															FROM alm_book_authors aba
															INNER JOIN alm_authors aa ON aa.authorID = aba.authorID
															WHERE aba.bookId = :|1 '. $AuthorName .';');
				$RSSearchAuthor->Execute($SearchRow->bookID);

				$GetAuthorName = '';
				$GetAuthorName = $RSSearchAuthor->FetchRow()->authorName ;

				if ($GetAuthorName != '' && $RSSearchAuthor->Result->num_rows > 0)
				{
					$AllBooks[$SearchRow->bookID]['BookName'] = $SearchRow->bookName;
					$AllBooks[$SearchRow->bookID]['BookCategoryName'] = $SearchRow->bookCategoryName;
					$AllBooks[$SearchRow->bookID]['AuthorName'] = $GetAuthorName;
					$AllBooks[$SearchRow->bookID]['Edition'] = $SearchRow->edition;
					$AllBooks[$SearchRow->bookID]['Quantity'] = $SearchRow->quantity;
					$AllBooks[$SearchRow->bookID]['ShelfNumber'] = $SearchRow->shelfNumber;
					
					$AllBooks[$SearchRow->bookID]['IsActive'] = $SearchRow->isActive;
					$AllBooks[$SearchRow->bookID]['CreateUserID'] = $SearchRow->createUserID;
					$AllBooks[$SearchRow->bookID]['CreateUserName'] = $SearchRow->createUserName;
					$AllBooks[$SearchRow->bookID]['CreateDate'] = $SearchRow->createDate;
				}
				
			}
			
			return $AllBooks;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at Book::SearchBooks(). Stack Trace: ' . $e->getTraceAsString());
			return $AllBooks;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at Book::SearchBooks(). Stack Trace: ' . $e->getTraceAsString());
			return $AllBooks;
		}
	}

	static function GetBooksByCategory($BookCategoryID)
	{
		$AllBooks = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT bookID, bookName FROM alm_books WHERE isActive = 1 AND bookCategoryID = :|1;');
			$RSSearch->Execute($BookCategoryID);
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $AllBooks;
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$AllBooks[$SearchRow->bookID] = $SearchRow->bookName;
			}
			
			return $AllBooks;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at Book::GetBooksByCategory(). Stack Trace: ' . $e->getTraceAsString());
			return $AllBooks;
		}
		catch (Exception $e) 
		{
			error_log('DEBUG: Exception at Book::GetBooksByCategory(). Stack Trace: ' . $e->getTraceAsString());
			return $AllBooks;
		}		
	}

	static function GetBookCopiesByBook($BookID)
	{
		$BookCopies = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT booksCopyID FROM alm_books_copies 
												WHERE bookID = :|1
												AND booksCopyID NOT IN (SELECT booksCopyID FROM alm_book_issue WHERE isReturned = 0);');
			$RSSearch->Execute($BookID);
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $BookCopies;
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$BookCopies[$SearchRow->booksCopyID] = $SearchRow->booksCopyID;
			}
			
			return $BookCopies;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at Book::GetBookCopiesByBook(). Stack Trace: ' . $e->getTraceAsString());
			return $BookCopies;
		}
		catch (Exception $e) 
		{
			error_log('DEBUG: Exception at Book::GetBookCopiesByBook(). Stack Trace: ' . $e->getTraceAsString());
			return $BookCopies;
		}		
	}

	static function GetBookIssuableToByBook($BookID)
	{
		$BookIssuableTo = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT issuableTo FROM alm_books WHERE bookID = :|1;');
			$RSSearch->Execute($BookID);
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $BookIssuableTo;
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$BookIssuableTo[$SearchRow->issuableTo] = $SearchRow->issuableTo;
			}
			
			return $BookIssuableTo;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at Book::GetBookIssuableToByBook(). Stack Trace: ' . $e->getTraceAsString());
			return $BookIssuableTo;
		}
		catch (Exception $e) 
		{
			error_log('DEBUG: Exception at Book::GetBookIssuableToByBook(). Stack Trace: ' . $e->getTraceAsString());
			return $BookIssuableTo;
		}		
	}

	static function GetBookClasses($BookID)
	{
		$BookClasses = array();
		
		try
		{
			$DBConnObject = new DBConnect();
			
			$RSSearch = $DBConnObject->Prepare('SELECT abc.* FROM alm_books_classes abc
												INNER JOIN alm_books ab ON ab.bookID = abc.bookID
												WHERE abc.bookID = :|1 AND ab.bookType = :|2;');
			$RSSearch->Execute($BookID, 'Academic');
			
			if ($RSSearch->Result->num_rows <= 0)
			{
				return $BookClasses;
			}
			
			while($SearchRow = $RSSearch->FetchRow())
			{
				$BookClasses[$SearchRow->booksClassID] = $SearchRow->classID;
			}
			
			return $BookClasses;	
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at Book::GetBookClasses(). Stack Trace: ' . $e->getTraceAsString());
			return $BookClasses;
		}
		catch (Exception $e) 
		{
			error_log('DEBUG: Exception at Book::GetBookClasses(). Stack Trace: ' . $e->getTraceAsString());
			return $BookClasses;
		}		
	}
	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//
	private function SaveDetails()
	{	
		$BookAuthers = array();	

		if ($this->PublisherName != '')
		{
			$RSSavePublisher = $this->DBObject->Prepare('INSERT INTO alm_publishers (publisherName, isActive, createUserID, createDate)
														VALUES (:|1, :|2, :|3, NOW());');
		
			$RSSavePublisher->Execute($this->PublisherName, $this->IsActive, $this->CreateUserID);
			
			$this->PublisherID = $RSSavePublisher->LastID;
		}

		if ($this->BookID == 0)
		{		
			$RSSaveBook = $this->DBObject->Prepare('INSERT INTO alm_books (bookCategoryID, ISBN, bookName, bookType, issuableTo,
																		takeHomeAllowed, volume, numberOfPages, price, purchaseDate, 
																		quantity, shelfNumber, publishedYear, edition, publisherID, 
																		isActive, createUserID, createDate)
														VALUES (:|1, :|2, :|3, :|4, :|5, :|6, :|7, :|8, :|9, :|10, :|11, :|12, :|13, :|14, :|15, :|16, :|17, NOW());');
		
			$RSSaveBook->Execute($this->BookCategoryID, $this->ISBN, $this->BookName, $this->BookType, $this->IssuableTo, 
							$this->TakeHomeAllowed, $this->Volume, $this->NumberOfPages, $this->Price, $this->PurchaseDate, 
							$this->Quantity, $this->ShelfNumber, $this->PublishedYear, $this->Edition, $this->PublisherID, 
							$this->IsActive, $this->CreateUserID);
			
			$this->BookID = $RSSaveBook->LastID;

			for ($i = 0; $i < $this->Quantity; $i++) 
			{ 
				$RSSaveBooksCopies = $this->DBObject->Prepare('INSERT INTO alm_books_copies (bookID)
															VALUES (:|1);');
			
				$RSSaveBooksCopies->Execute($this->BookID);
			}

			foreach ($this->ClassList as $Counter => $ClassID) 
			{
				$RSSavebooksClasses = $this->DBObject->Prepare('INSERT INTO alm_books_classes (bookID, classID)
															VALUES (:|1, :|2);');
			
				$RSSavebooksClasses->Execute($this->BookID, $ClassID);
			}

			foreach ($this->AuthorDetails as $Counter => $Details) 
			{
				if ($Details['AuthorID'] == 0) 
				{
					$RSSaveAuthor = $this->DBObject->Prepare('INSERT INTO alm_authors (authorName, isActive, createUserID, createDate)
																VALUES (:|1, :|2, :|3, NOW());');
				
					$RSSaveAuthor->Execute($Details['AuthorName'], $this->IsActive, $this->CreateUserID);
					
					$BookAuthers[$RSSaveAuthor->LastID] = $this->BookID;
				}
				else
				{
					$BookAuthers[$Details['AuthorID']] = $this->BookID;
				}
			}

			foreach ($BookAuthers as $AuthorID => $BookID) 
			{
				$RSSaveBookAuthor = $this->DBObject->Prepare('INSERT INTO alm_book_authors (bookID, authorID)
															VALUES (:|1, :|2);');
			
				$RSSaveBookAuthor->Execute($BookID, $AuthorID);
			}
		}
		else
		{
			$RSUpdate = $this->DBObject->Prepare('UPDATE alm_books
													SET	bookCategoryID = :|1, 
														ISBN = :|2,
														bookName = :|3,
														bookType = :|4,
														issuableTo = :|5,
														takeHomeAllowed = :|6,
														volume = :|7,
			 											numberOfPages = :|8,
			 											price = :|9,
			 											purchaseDate = :|10,
			 											quantity = :|11,
			 											shelfNumber = :|12, 
			 											publishedYear = :|13,
			 											edition = :|14,
			 											publisherID = :|15,
			 											isActive = :|16
													WHERE bookID = :|17;');
													
			$RSUpdate->Execute($this->BookCategoryID, $this->ISBN, $this->BookName, $this->BookType, $this->IssuableTo, 
							$this->TakeHomeAllowed, $this->Volume, $this->NumberOfPages, $this->Price, $this->PurchaseDate, 
							$this->Quantity, $this->ShelfNumber, $this->PublishedYear, $this->Edition, $this->PublisherID, 
							$this->IsActive, $this->BookID);

			if ($this->PreviousQuantity > 0 && $this->PreviousQuantity < $this->Quantity) 
			{
				$Limit = $this->Quantity - $this->PreviousQuantity;

				for ($i = 0; $i < $Limit; $i++) 
				{ 
					$RSSaveBooksCopies = $this->DBObject->Prepare('INSERT INTO alm_books_copies (bookID)
																VALUES (:|1);');
				
					$RSSaveBooksCopies->Execute($this->BookID);
				}
			}
			else if ($this->PreviousQuantity > 0 && $this->PreviousQuantity > $this->Quantity) 
			{
				$Limit = $this->PreviousQuantity - $this->Quantity;
				
				$DeleteBooksCopies = $this->DBObject->Prepare('DELETE FROM alm_books_copies 
																WHERE bookID = :|1 AND booksCopyID NOT IN (SELECT booksCopyID FROM alm_book_issue WHERE bookID = :|2) LIMIT '. $Limit .';');
				
				$DeleteBooksCopies->Execute($this->BookID, $this->BookID);				
			}

			foreach ($this->RemovedClassList as $BooksClassID => $ClassID) 
			{
				$DeleteBooksClasses = $this->DBObject->Prepare('DELETE FROM alm_books_classes WHERE booksClassID = :|1 LIMIT 1;');
			
				$DeleteBooksClasses->Execute($BooksClassID);
			}

			foreach ($this->ClassList as $Counter => $ClassID) 
			{
				$RSSavebooksClasses = $this->DBObject->Prepare('INSERT INTO alm_books_classes (bookID, classID)
															VALUES (:|1, :|2);');
			
				$RSSavebooksClasses->Execute($this->BookID, $ClassID);
			}

			foreach ($this->RemovedAuthorList as $BookAuthorID => $AuthorID) 
			{
				$DeleteBookAuthor = $this->DBObject->Prepare('DELETE FROM alm_book_authors WHERE bookAuthorID = :|1 LIMIT 1;');
			
				$DeleteBookAuthor->Execute($BookAuthorID);
			}

			foreach ($this->AuthorDetails as $Counter => $Details) 
			{
				if ($Details['AuthorName'] != '') 
				{
					$RSSaveAuthor = $this->DBObject->Prepare('INSERT INTO alm_authors (authorName, isActive, createUserID, createDate)
																VALUES (:|1, :|2, :|3, NOW());');
				
					$RSSaveAuthor->Execute($Details['AuthorName'], $this->IsActive, $this->CreateUserID);
					
					$BookAuthers[$RSSaveAuthor->LastID] = $this->BookID;
				}
				else
				{
					$BookAuthers[$Details['AuthorID']] = $this->BookID;
				}
			}

			foreach ($BookAuthers as $AuthorID => $BookID) 
			{
				$RSSaveBookAuthor = $this->DBObject->Prepare('INSERT INTO alm_book_authors (bookID, authorID)
															VALUES (:|1, :|2);');
			
				$RSSaveBookAuthor->Execute($BookID, $AuthorID);
			}
		}
		
		return true;
	}
	
	private function RemoveBook()
    {
        if(!isset($this->BookID)) 
        {
            throw new ApplicationARException('', APP_AR_ERROR_DELETE_WITHOUT_ID);
        }
        
        $RSDeleteBook = $this->DBObject->Prepare('DELETE FROM alm_books WHERE bookID = :|1 LIMIT 1;');
        $RSDeleteBook->Execute($this->BookID);  

        return true;              
    }

	private function GetBookByID()
	{
		$RSBook = $this->DBObject->Prepare('SELECT * FROM alm_books WHERE bookID = :|1 LIMIT 1;');
		$RSBook->Execute($this->BookID);
		
		$BookRow = $RSBook->FetchRow();
		
		$this->SetAttributesFromDB($BookRow);				
	}
	
	private function SetAttributesFromDB($BookRow)
	{
		$this->BookID = $BookRow->bookID;
		$this->BookCategoryID = $BookRow->bookCategoryID;
		$this->ISBN = $BookRow->ISBN;

		$this->BookName = $BookRow->bookName;
		$this->BookType = $BookRow->bookType;
		$this->IssuableTo = $BookRow->issuableTo;
		$this->TakeHomeAllowed = $BookRow->takeHomeAllowed;
		$this->Volume = $BookRow->volume;
		$this->NumberOfPages = $BookRow->numberOfPages;

		$this->Price = $BookRow->price;
		$this->PurchaseDate = $BookRow->purchaseDate;
		$this->Quantity = $BookRow->quantity;
		$this->ShelfNumber = $BookRow->shelfNumber;

		$this->PublishedYear = $BookRow->publishedYear;
		$this->Edition = $BookRow->edition;
		$this->PublisherID = $BookRow->publisherID;

		$this->IsActive = $BookRow->isActive;
		$this->CreateUserID = $BookRow->createUserID;
		$this->CreateDate = $BookRow->createDate;

		$RSBooksClasses = $this->DBObject->Prepare('SELECT * FROM alm_books_classes WHERE bookID = :|1;');
		$RSBooksClasses->Execute($this->BookID);

		if ($RSBooksClasses->Result->num_rows > 0) 
		{
			while($SearchRow = $RSBooksClasses->FetchRow())
			{
				$this->ClassList[$SearchRow->booksClassID] = $SearchRow->classID;
			}
		}

		$RSAuthorDetails = $this->DBObject->Prepare('SELECT aba.bookAuthorID, aa.authorID, aa.authorName FROM alm_book_authors aba 
													INNER JOIN alm_authors aa ON aa.authorID = aba.authorID
													WHERE aba.bookID = :|1;');
		$RSAuthorDetails->Execute($this->BookID);

		if ($RSAuthorDetails->Result->num_rows > 0) 
		{
			while($SearchRow = $RSAuthorDetails->FetchRow())
			{
				$this->AuthorDetails[$SearchRow->bookAuthorID]['AuthorID'] = $SearchRow->authorID;
				$this->AuthorDetails[$SearchRow->bookAuthorID]['AuthorName'] = $SearchRow->authorName;
			}
		}

		$RSPublisherDetails = $this->DBObject->Prepare('SELECT publisherName FROM alm_publishers WHERE publisherID = :|1;');
		$RSPublisherDetails->Execute($this->PublisherID);

		$this->PublisherName = $RSPublisherDetails->FetchRow()->publisherName;		
	}	
}
?>
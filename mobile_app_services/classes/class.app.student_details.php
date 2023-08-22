<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.db_connect.php');
error_reporting(E_ALL);

class AppStudentDetail extends StudentDetail
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	protected $StudentID;
	protected $StudentPhoto;
	
	// PUBLIC METHODS START HERE	//
	public function __construct($StudentID)
	{
		$this->DBObject = new DBConnect;
		$this->LastErrorCode = 0;
		
		$this->StudentID = $StudentID;
		// SET THE VALUES FROM THE DATABASE.
		parent::__construct($this->StudentID);
	}

	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetStudentID()
	{
		return $this->StudentID;
	}
	
	public function GetStudentPhoto()
	{
		return $this->StudentPhoto;
	}
	//  END OF GETTER AND SETTER FUNCTIONS 	//


	public function SaveStudentPhoto($StudentPhoto)
	{
		try
		{
			$RSSave = $this->DBObject->Prepare('UPDATE asa_student_details SET studentPhoto = :|1 WHERE studentID = :|2 LIMIT 1;');
			$RSSave->Execute($StudentPhoto, $this->StudentID);
			return true;
		}

		catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at AppStudentDetail::SaveStudentPhoto(). Stack Trace: ' . $e->getTraceAsString());
            return false;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception at AppStudentDetail::SaveStudentPhoto(). Stack Trace: ' . $e->getTraceAsString());
            return false;
        }
	}
	
	// END OF PUBLIC METHODS	//
	
	// START OF STATIC METHODS	//
	
	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//
	
}
?>
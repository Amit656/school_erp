<?php
class ApplicationDBException extends Exception
{
	function __construct($message='', $code = 0)
	{
		parent::__construct($message, $code);
	}
}

class ApplicationARException extends Exception
{
	function __construct($message='', $code = 0)
	{
		parent::__construct($message, $code);
	}
}

class ApplicationAuthException extends Exception
{
	function __construct($message='', $code = 0)
	{
		parent::__construct($message, $code);
	}
}



define ("APP_ERROR_NO_ERROR", 0);
define ("APP_ERROR_UNDEFINED_ERROR", 101);

// THESE ERROR CONSTANTS WILL BE USED BY THE DATABASE CLASSES. THESE ERRORS START WITH 90001	//
define ("APP_DB_ERROR_NO_CONNECTION", 90001);
define ("APP_DB_ERROR_NO_DATABASE", 90002);
define ("APP_DB_ERROR_QUERY_FAILED", 90003);
define ("APP_DB_ERROR_NO_RECORDS", 90004);
define ("APP_DB_ERROR_DUPLICATE_RECORD", 90005);
define ("APP_DB_ERROR_SP_FAILED", 90006);
define ("APP_DB_ERROR_DUPLICATE_RECORD_LOGIN", 90007);

// THESE ERROR CONSTANTS WILL BE USED BY THE ACTIVE RECORD PATTERN CLASSES. THESE START WITH 30001 //
define ("APP_AR_ERROR_SAVE_ERROR", 30001);
define ("APP_AR_ERROR_REMOVE_ERROR", 30002);
define ("APP_AR_ERROR_INSERT_WITH_AUTO_INCREMENT_ID", 30003);
define ("APP_AR_ERROR_UPDATE_WITHOUT_ID", 30004);
define ("APP_AR_ERROR_DELETE_WITHOUT_ID", 30005);

// THESE ERROR CONSTANTS WILL BE USED BY THE AUTHNTICATION CLASSES. THESE START WITH 70001	//
define ("APP_AUTH_ERROR_LOGIN_FAIL", 70001);
define ("APP_AUTH_ERROR_INVALID_USER", 70002);
define ("APP_AUTH_ERROR_CANNOT_LOGOUT", 70003);
define ("APP_AUTH_ERROR_LOGOUT_WITHOUT_LOGIN", 70004);


// THIS IS THE CENTRAL FUNCTION WHERE ALL ERRORS WILL BE PROCESSED AND APPROPRIATE ACTIONS TAKEN //
function ProcessErrors($ErrorCode)
{
	$ErrorMessage = '';
	switch ($ErrorCode)
	{	
		case 0:
			break;
			
		// DB ERRORS START HERE	//
		case 90001:
			$ErrorMessage .= "There was an error in the db connection";
			break;
		case 90002:
			$ErrorMessage .= "There was an error in the db connection";
			break;
		case 90003:
			$ErrorMessage .= "There was an error in executing the query";
			break;
		case 90004:
			$ErrorMessage .= "The query did not return any records";
			break;
		case 90005:
			$ErrorMessage .= "This record is already present";
			break;
		case 90006:
			$ErrorMessage .= "There was a database error.";
			break;		
		case 90007:
			$ErrorMessage .= "This Login Name has already been taken. Please select another Login Name.";
			break;		
		//	END OF DB ERRORS	
		
		//	START OF AR ERRORS	//
		case 30001:
			$ErrorMessage .= "There was an error in saving to the database";
			break;
		case 30002:
			$ErrorMessage .= "There was an error in removing the records";
			break;
		case 30003:
			$ErrorMessage .= "There was an error. An ID was supplied for an auto increment insert ID operation";
			break;
		case 30004:
			$ErrorMessage .= "There was an error. An ID was not supplied for an update operation";
			break;
		case 30005:
			$ErrorMessage .= "There was an error. An ID was not supplied for a delete operation";
			break;
		//	END OF AR ERRORS	
		
		//	START OF AUTHENTICATION ERRORS	//
		case 70001:
			$ErrorMessage .= "You could not be logged in. Invalid username or password";
			break;
		case 70002:
			$ErrorMessage .= "You are not logged in. Please login";
			$ErrorMessage .= '<p><a href="'.get_url().'admin/index.php">Login</a></p>';
			exit;
			break;
		case 70003:
			$ErrorMessage .= "There was an error.";
			break;
		case 70004:
			$ErrorMessage .= "There was an error.";
			break;		
		//	END OF AUTHENTICATION ERRORS	
		
		case 101:
			$ErrorMessage .= "There was an undefined error.";
			break;	
	}
	return $ErrorMessage;
}
?>
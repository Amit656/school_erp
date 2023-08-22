<?php
require_once('class.db_connect.php');
error_reporting(E_ALL);
	
class DateProcessing
{
	// CLASS MEMBERS ARE DEFINED HERE	//
		
	// PUBLIC METHODS START HERE	//
	public function __construct()
	{
		
	}
	
		
	// END OF PUBLIC METHODS	//
	
	// START OF STATIC METHODS	//
	
	static function ToggleDateDayAndMonth($DateSupplied)
	{
		$DatepartsArray = array();
		
		$DatepartsArray = explode('/', $DateSupplied);
				
		return $DatepartsArray[1].'/'.$DatepartsArray[0].'/'.$DatepartsArray[2];		
	}
	
	static function MakeFirstDayOfMonth($DateSupplied)
	{
		$DatepartsArray = array();
		
		$DatepartsArray = explode('/', $DateSupplied);
				
		return $DatepartsArray[1].'/01/'.$DatepartsArray[2];		
	}
	
	// END OF STATIC METHODS	//
	
	// START OF PRIVATE METHODS	//	
}
?>
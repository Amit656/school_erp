<?php
error_reporting(E_ALL);

class JSONResponse
{
	// CLASS MEMBERS ARE DEFINED HERE	//
	private $Error;
	private $ErrorCode;
	private $Message;

	private $Data = array();

	private $Response;

	// PUBLIC METHODS START HERE	//
	public function __construct()
	{		
		//SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
		$this->Error = 0;
		$this->ErrorCode = 0;
		$this->Message = '';

		$this->Data = array();

		$this->Response = array();
	}
	
	// GETTER AND SETTER FUNCTIONS START HERE	//
	public function GetError()
	{
		return $this->Error;
	}
	public function SetError($Error)
	{
		$this->Error = $Error;
	}

	public function GetErrorCode()
	{
		return $this->ErrorCode;
	}
	public function SetErrorCode($ErrorCode)
	{
		$this->ErrorCode = $ErrorCode;
	}

	public function GetMessage()
	{
		return $this->Message;
	}
	public function SetMessage($Message)
	{
		$this->Message = $Message;
	}

	public function GetData()
	{
		return $this->Data;
	}
	public function SetData($Data)
	{
		$this->Data = $Data;
	}

	public function SetDataOnKey($Key, $SubmittedArray)
	{
		$this->Data[$Key] = array();

		$this->Data[$Key] = $SubmittedArray;
	}
	
	public function PushData($Key, $Value, $DirectPush = false)
	{
		$this->Data[$Key] = array();
		
		if ($DirectPush === false)
		{
			if (is_array($Value))
			{
				return $this->ConvertArray($Value, $Key);
			}
		}
		
		$this->Data[$Key] = $Value;
	}
	
	public function ConvertArray($SubmittedArray, $SetToKey)
	{
		//$RecordIDs = array_keys($SubmittedArray);
		
		foreach ($SubmittedArray as $Key => $Value)
		{
			array_push($this->Data[$SetToKey], $this->DoSomething($Value, $Key));
		}
		
		return $this->Data[$SetToKey];
	}
	
	public function DoSomething($SubmittedArray, $SubmittedKey)
	{
		$NewArray = array();
		$NewArray['id'] = $SubmittedKey;
		
		foreach ($SubmittedArray as $Key => $Value)
		{
			$NewArray[$Key] = $Value;
		}
		
		return $NewArray;
	}

	public function GetResponseAsArray()
	{
		$this->Response['error'] = $this->Error;
		$this->Response['error_code'] = $this->ErrorCode;
		$this->Response['message'] = $this->Message;
		$this->Response['data'] = $this->Data;

		return $this->Response;
	}
	//  END OF GETTER AND SETTER FUNCTIONS 	//
	
	// END OF PUBLIC METHODS	//
	
	// START OF PRIVATE METHODS	//
}
?>
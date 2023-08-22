<?php
// GENERALIZED CLASS FOR VALIDATION OF USER INPUT //
// THIS CLASS USES THE OBSERVER DESIGN PATTERN //
class Validator
{
	private $ErrorNotifications; // ARRAY TO HOLD ALL THE ERROR MESSAGES
	
	// INITIALIZE THE CLASS AND SET THE NOTIFICATION ARRAY //
	public function __construct()
	{
		$this->ErrorNotifications = array();
	}
	
	// METHOD TO ADD AN ERROR MESSAGE TO THE NOTIFICATION ARRAY //
	private function AttachNotification($ErrorMessage)
	{
		$this->ErrorNotifications[] = $ErrorMessage;
	}

	// THIS FUNCTION IS USED TO DISPLAY THE ERROR MESSAGES WITH PROPER FORMATTING //
	public function DisplayErrors()
	{
		echo '<div class="form-group has-error"><ul>';
		foreach($this->ErrorNotifications as $Notification)
		{
			echo '<li class="control-label" style="text-align: left;font-weight: bold;">'.$Notification.'</li>';
		}
		echo '</ul></div>';
	}
	
	public function DisplayErrorsInTable()
	{
		echo '<div style="margin-bottom: 0; margin-top: 15px;" class="alert alert-danger">';
		foreach($this->ErrorNotifications as $Notification)
		{
			echo '<div>- '.$Notification.'</div>';
		}
		echo '</div>';
	}

	// THIS FUNCTION CHECKS IF THE ERROR OBJECT HAS ANY ERRORS, IF IT HAS, CALL THE 'displayErrors' METHOD //
	public function HasNotifications()
	{
		if(count($this->ErrorNotifications) > 0)
		{
			// ERRORS ARE FOUND //
			return true;
		}
		else
		{
			// NO ERRORS //
			return false;
		}
	}
	
	public function AttachTextError($ErrorMessage)
	{
		$this->AttachNotification($ErrorMessage);
		return false;
	}
	
	// ****** IFSC Code Validation ****** //
	function ValidateIFSCCode($Field, $ErrorMessage)
	{
		$RegNumber = "([A-Z|a-z]{4}[0][A-Z|a-z|0-9]{6}$)";
	
		if (!preg_match($RegNumber, $Field))
		{
			$this->AttachNotification($ErrorMessage);
			return false;
		}
		return true;
	}
	
	// ****** THIS FUNCTION WILL CHECK IF THE VALUE IS AN INTEGER ****** //
	function ValidateInteger($Field, $ErrorMessage, $Mode)
	{
		if ($Mode == 1)
		{
			$RegNumber = "(^(0+[1-9]|[1-9])[0-9]*$)";      //THIS TO CHECK POSITIVE INTEGERS GREATER THAN 0//
		}
		else
		{
			$RegNumber = "(^\d+$)";               			 //THIS TO CHECK POSITIVE INTEGERS INCLUDING 0//
		}
	
		if (!preg_match($RegNumber, $Field))
		{
			$this->AttachNotification($ErrorMessage);
			return false;
		}
		return true;
	}
	
	// PUBLIC METHOD TO VALIDATE Email Address //
	public function ValidateEmail($Field, $ErrorMessage, $Min = 3, $Max = 32)
	{
		if(!isset($Field) || $Field == '' || strlen($Field) < $Min || strlen($Field) > $Max)
		{
			// IF AN ERROR IS PRESENT ADD THE ERROR MESSAGE TO THE NOTIFICATION ARRAY //
			$this->AttachNotification($ErrorMessage);
			return false;
		}
		else
		{
			if (!preg_match('/^[a-zA-Z0-9 \._\-]+@([a-zA-Z0-9][a-zA-Z0-9\-]*\.)+[a-zA-Z]+$/', $Field))
			{
				$this->AttachNotification($ErrorMessage);
				return false;
			}
		}
		return true;
	}
	
	// PUBLIC METHOD TO VALIDATE Address Address //
    //Address (Whitelist :/\,.-'()&)
	public function ValidateAddress($Field, $ErrorMessage)
	{
        if (preg_match('#[^\w .&\-,\\\/()]+#', $Field))
        {
            $this->AttachNotification($ErrorMessage);
            return false;
        }
		return true;
	}
	
	// PUBLIC METHOD TO VALIDATE IF A STRING IS NOT EMPTY AND HAS A PARTICULAR LENGTH //
	public function ValidateStrings($Field, $ErrorMessage, $Min = 3, $Max = 32)
	{		
		if(!isset($Field) || $Field == '' || strlen($Field) < $Min || strlen($Field) > $Max)
		{
			// IF AN ERROR IS PRESENT ADD THE ERROR MESSAGE TO THE NOTIFICATION ARRAY //
			$this->AttachNotification($ErrorMessage);
			return false;
		}
		return true;
	}
	
	public function ValidateStringsSpecialChar($Field, $ValidChars, $ErrorMessage, $Min = 3, $Max = 32)
	{
		if(!isset($Field) || $Field == '' || strlen($Field) < $Min || strlen($Field) > $Max)
		{
			// IF AN ERROR IS PRESENT ADD THE ERROR MESSAGE TO THE NOTIFICATION ARRAY //
			$this->AttachNotification($ErrorMessage);
			return false;
		}
		else
		{
			if(!preg_match("/^[a-z$ValidChars]+$/i", $Field))
			{
				$this->AttachNotification($ErrorMessage);
				return false;
			}  
		}
		return true;
	}
	
	public function ValidateNumeric($Field, $ErrorMessage)
	{
		if (!is_numeric($Field))
		{
			$this->AttachNotification($ErrorMessage);
			return false;
		} 
		return true;
	}
	
	// PUBLIC METHOD TO VALIDATE A DATE //
	public function ValidateDate($DateReturned, $ErrorMessage)
	{
		if((strlen($DateReturned) < 10) || (strlen($DateReturned) > 10))
		{
			// IF AN ERROR IS PRESENT ADD THE ERROR MESSAGE TO THE NOTIFICATION ARRAY //
			$this->AttachNotification($ErrorMessage);
			return false;
		}
		else
		{
			if((substr_count($DateReturned, "/")) <> 2)
			{
				$this->AttachNotification($ErrorMessage);
                return false;
			}
			else
			{
				$MonthPart = substr($DateReturned, 3, 2);
				$DatePart = substr($DateReturned, 0, 2);
				$YearPart = substr($DateReturned, 6, 4);
				
				if (!isset($MonthPart) || ($MonthPart == '') || !isset($DatePart) || ($DatePart == '') || !isset($YearPart) || ($YearPart == '') || (!is_numeric($MonthPart)) || floatval($MonthPart) != intval($MonthPart) || (!is_numeric($DatePart)) || floatval($DatePart) != intval($DatePart) || (!is_numeric($YearPart)) || floatval($YearPart) != intval($YearPart))
				{
					$this->AttachNotification($ErrorMessage);
                    return false;
				}
				else
				{
					if (!checkdate($MonthPart, $DatePart, $YearPart))
					{
						$this->AttachNotification($ErrorMessage);
                        return false;
					}
				}
			}
		}
        
        return true;
	}

	public function ValidateTime($EnteredTime, $ErrorMessage)
	{
		if (!preg_match("/^(?:(?:([01]?\d|2[0-3]):)?([0-5]?\d):)?([0-5]?\d)$/", $EnteredTime))
		{
			$this->AttachNotification($ErrorMessage);
			return false;
		}
		return true;
	}
	
	public function ValidateTimeFormatted($EnteredTime, $ErrorMessage)
	{
		if (!preg_match("/^(0?[1-9]|1[012])(:[0-5]\d) [APap][mM]$/", $EnteredTime))
		{
			$this->AttachNotification($ErrorMessage);
			return false;
		}
		return true;
	}
	
	
	// PUBLIC METHOD TO VALIDATE IF THE SUPPLIED PASSWORDS HAVE VALID LENGTH AND ARE SAME //
	public function ValidatePassword($Password1, $Password2, $ErrorMessage1, $ErrorMessage2, $Min = 4, $Max = 10)
	{
		if(!isset($Password1) || $Password1 == '' || strlen($Password1) < $Min || strlen($Password1) > $Max)
		{
			// IF AN ERROR IS PRESENT ADD THE ERROR MESSAGE TO THE NOTIFICATION ARRAY //
			$this->AttachNotification($ErrorMessage1);
			return false;
		}
		else
		{
			if(!preg_match("/^[a-z0-9_.@-]+$/i", $Password1))
			{
				$this->AttachNotification($ErrorMessage1);
				return false;
			}
			else
			{
				if($Password1 != $Password2)
				{
					$this->AttachNotification($ErrorMessage2);
					return false;
				}
			}
		}
		return true;
	}
	
	// PUBLIC METHOD TO VALIDATE IF A CORRECT ID IS RETURNED FOR A SELECTDROP DOWN //
	public function ValidateInSelect($SubmittedRecord, $RecordArray, $ErrorMessage)
	{
		if(!array_key_exists($SubmittedRecord, $RecordArray))
		{
			// IF AN ERROR IS PRESENT ADD THE ERROR MESSAGE TO THE NOTIFICATION ARRAY //
			$this->AttachNotification($ErrorMessage);
			return false;
		}
		return true;
	}
	
	// PUBLIC METHOD TO VALIDATE IF A SELECTION IS MADE IN A DROPDOWN //
	public function ValidateSelectRequired($SubmittedRecord, $ErrorMessage)
	{
		if($SubmittedRecord == 0)
		{
			// IF AN ERROR IS PRESENT ADD THE ERROR MESSAGE TO THE NOTIFICATION ARRAY //
			$this->AttachNotification($ErrorMessage);
		}
	}
	
	public function ValidateAge($DateReturned, $AgeToValidate, $ErrorMessage)
	{
		$MonthPart = substr($DateReturned, 3, 2);
		$DatePart = substr($DateReturned, 0, 2);
		$YearPart = substr($DateReturned, 6, 4);
				
		$YearDiff = date("Y") - $YearPart;
		$MonthDiff = date("m") - $MonthPart;
		$DayDiff = date("d") - $DatePart;
		
		if ($MonthDiff < 0) 
		{
			$YearDiff--;
		}
		else
		{
			if (($MonthDiff == 0) && ($DayDiff < 0))
			{
				$YearDiff--;
			}
		}
		
		if ($YearDiff < $AgeToValidate)
		{
			$this->AttachNotification($ErrorMessage);
			return false;
		}
		
		return true;
	}	
	
	public function ValidateMinorAge($DateReturned, $AgeToValidate, $ErrorMessage)
	{
		$MonthPart = substr($DateReturned, 3, 2);
		$DatePart = substr($DateReturned, 0, 2);
		$YearPart = substr($DateReturned, 6, 4);
				
		$YearDiff = date("Y") - $YearPart;
		$MonthDiff = date("m") - $MonthPart;
		$DayDiff = date("d") - $DatePart;
		
		if ($MonthDiff < 0) 
		{
			$YearDiff--;
		}
		else
		{
			if (($MonthDiff == 0) && ($DayDiff < 0))
			{
				$YearDiff--;
			}
		}
		
		if ($YearDiff < $AgeToValidate)
		{			
			return true;
		}
		
		$this->AttachNotification($ErrorMessage);
		return false;
	}
	
	public function PassedDateIsInRange($PassedDate, $ErrorMessage, $NoOfDaysBeforePassedDate = 30, $NoOfDaysAfterPassedDate = 3)
	{
		if (strtotime($PassedDate) < strtotime('-'.($NoOfDaysBeforePassedDate - 1).' days') || strtotime($PassedDate) > strtotime('+'.$NoOfDaysAfterPassedDate.' days'))
		{
			$this->AttachNotification($ErrorMessage);
			return false;
		}
		
		return true;
	}
}
?>
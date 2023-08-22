<?php
function PrintMessage($GetResult = array(), &$Result = '')
{
	if (!isset($GetResult['Mode']))
	{
		return false;
	}
	
	$Result = '';
	
	switch ($GetResult['Mode'])
	{
		case 'ED':
			$Result = 'Record saved successfully.';
		break;
		
		case 'UD':
			$Result = 'Record updated successfully.';
		break;
		
		case 'DD':
			$Result = 'Record deleted successfully.';
		break;
	}
	
	return true;
}

function NumberToWords($no)
{
	$words = array('0'=> '' ,'1'=> 'one' ,'2'=> 'two' ,'3' => 'three','4' => 'four','5' => 'five','6' => 'six','7' => 'seven','8' => 'eight','9' => 'nine','10' => 'ten','11' => 'eleven','12' => 'twelve','13' => 'thirteen','14' => 'fouteen','15' => 'fifteen','16' => 'sixteen','17' => 'seventeen','18' => 'eighteen','19' => 'nineteen','20' => 'twenty','30' => 'thirty','40' => 'fourty','50' => 'fifty','60' => 'sixty','70' => 'seventy','80' => 'eighty','90' => 'ninty','100' => 'hundred &','1000' => 'thousand','100000' => 'lakh','10000000' => 'crore');
	
	if($no == 0)
	{
		return ' ';
	}
	else 
	{
		$novalue='';
		$highno=$no;
		$remainno=0;
		$value=100;
		$value1=1000;
		
		while($no>=100) 
		{
			if(($value <= $no) &&($no < $value1)) 
			{
				$novalue=$words["$value"];
				$highno = (int)($no/$value);
				$remainno = $no % $value;
				break;
			}
			
			$value= $value1;
			$value1 = $value * 100;
		}
		
		if(array_key_exists("$highno",$words))
		{
			return $words["$highno"]." ".$novalue." ".NumberToWords($remainno);
		}
		else 
		{
			$unit=$highno%10;
			$ten =(int)($highno/10)*10;
			return $words["$ten"]." ".$words["$unit"]." ".$novalue." ".NumberToWords($remainno);
		}
	}
}

function GetFloorNumberText($FloorNumber, $AddSuperScript = true)
{
	if ($FloorNumber == 0)
	{
		return 'Ground Floor';
	}
	else
	{
		$FloorNamePart = '';
		
		if ($FloorNumber % 10 == 1)
		{
			$FloorNamePart = 'st';
		}
		else if ($FloorNumber % 10 == 2)
		{
			$FloorNamePart = 'nd';
		}
		else if ($FloorNumber % 10 == 3)
		{
			$FloorNamePart = 'rd';
		}
		else
		{
			$FloorNamePart = 'th';
		}
		
		if  ($AddSuperScript == true)
		{
			return $FloorNumber.'<sup>'.$FloorNamePart.'</sup> Floor';
		}
		else
		{
			return $FloorNumber.$FloorNamePart.' Floor';
		}
	}
}

function GetRangeDates($FromDate, $Todate)
{
	$BetweenDates = array();
	while(strtotime($Todate) >= strtotime($FromDate))
	{
		$BetweenDates[]  = date('Y-m-d',strtotime($FromDate));
		$FromDate = date('Y-m-d', strtotime("+1 day", strtotime($FromDate)));
	}

  return $BetweenDates;	
}
?>

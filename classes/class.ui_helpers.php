<?php
require_once('class.db_connect.php');
require_once('class.date_processing.php');
error_reporting(E_ALL);

class UIHelpers
{
	static function GetPager($CurrentScript, $TotalPages, $CurrentPage, $Parameters)
	{
		$PagerHTML = '';
			
		$ParameterString = '';
		if (is_array($Parameters))
		{
			$ParameterString = http_build_query($Parameters, '', '&amp;');
		}
		
		$PreviousPageURL = '';
		if ($CurrentPage > 1)
		{
			$PreviousPageURL = '<a href="' . $CurrentScript . '?' . $ParameterString . '&amp;CurrentPage=' . ($CurrentPage - 1) . '" class="btn btn-default">&lt;&lt;</a>';
		}
		else
		{
			$PreviousPageURL = '<a href="#" class="btn btn-default disabled">&lt;&lt;</a>';
		}
		
		$NextPageURL = '';
		if ($CurrentPage < $TotalPages)
		{
			$NextPageURL = '<a href="' . $CurrentScript . '?' . $ParameterString . '&amp;CurrentPage=' . ($CurrentPage + 1) . '" class="btn btn-default">&gt;&gt;</a>';
		}
		else
		{
			$NextPageURL = '<a href="#" class="btn btn-default disabled">&gt;&gt;</a>';
		}
		
		$AllRecordURL = '';
		$AllRecordURL = '<a href="' . $CurrentScript . '?' . $ParameterString . '&amp;AllRecords=All" class="btn btn-primary">All Record</a>';
		
		$PagerHTML = '
										<form name="frmPager" class="form-inline" action="' . $CurrentScript . '" method="get">
											<div class="paging-container" style="">
												<ul>
													<li>' . $PreviousPageURL . '</li>
													<li><strong>Page</strong></li>
													<li><input class="form-control" id="page-number" type="text" name="CurrentPage" style="" value="' . $CurrentPage . '"></li>
													<li><strong>of ' . $TotalPages . '</strong></li>
													<li>
														' . self::BuildHiddenVariablesForPager($Parameters) . '
														<button type="submit" class="btn btn-primary">Go</button>
													</li>
													<li>' . $NextPageURL . '</li>
													<li>' . $AllRecordURL . '</li>
												</ul>
											</div>
										</form>';

		return $PagerHTML;
	}
	
	static function BuildHiddenVariablesForPager($Parameters)
	{
		$HiddenVariablesString = '';
		
		if (is_array($Parameters) && count($Parameters) > 0)
		{
			foreach ($Parameters as $ParameterName => $ParameterValue)
			{
				if (!is_string($ParameterValue) && is_array($ParameterValue))
				{
					foreach ($ParameterValue as $key => $value) {
						$HiddenVariablesString .= '<input type="hidden" name="' . $ParameterName . '" value="' . $value . '" />';
					}
				}
				else
				{
					$HiddenVariablesString .= '<input type="hidden" name="' . $ParameterName . '" value="' . $ParameterValue . '" />';
				}
			}
		}
		
		return $HiddenVariablesString;
	}

	static function GetPageOperationResultMessage($MessageCode)
	{
		$PageOperationResultMessage = '';
	
		switch ($MessageCode)
		{
			case 'RA':
				$PageOperationResultMessage = 'Record saved successfully.';
			break;
			
			case 'RU':
				$PageOperationResultMessage = 'Record updated successfully.';
			break;
			
			case 'RD':
				$PageOperationResultMessage = 'Record deleted successfully.';
			break;

			case 'UTU':
				$PageOperationResultMessage = 'User tasks updated successfully.';
			break;

			case 'RTU':
				$PageOperationResultMessage = 'Role tasks updated successfully.';
			break;

			case 'RGU':
				$PageOperationResultMessage = 'Role group roles updated successfully.';
			break;

			default:
				$PageOperationResultMessage = 'Unknown message.';
			break;
		}
		
		return $PageOperationResultMessage;
	}
}
?>
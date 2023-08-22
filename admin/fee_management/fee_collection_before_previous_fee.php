<?php
require_once('../../classes/class.users.php');
require_once('../../classes/class.validation.php');
require_once('../../classes/class.authentication.php');

require_once('../../classes/school_administration/class.academic_years.php');
require_once('../../classes/school_administration/class.academic_year_months.php');

require_once('../../classes/school_administration/class.classes.php');
require_once('../../classes/school_administration/class.class_sections.php');
require_once('../../classes/school_administration/class.section_master.php');

require_once('../../classes/school_administration/class.students.php');
require_once('../../classes/school_administration/class.student_details.php');
require_once('../../classes/school_administration/class.parent_details.php');

require_once('../../classes/fee_management/class.fee_heads.php');
require_once('../../classes/fee_management/class.fee_collection.php');
require_once('../../classes/fee_management/class.late_fee_rules.php');

require_once("../../classes/class.date_processing.php");
require_once("../../classes/class.helpers.php");

require_once("../../classes/class.global_settings.php");

require_once('../../includes/global_defaults.inc.php');

//1. RECHECK IF THE USER IS VALID //
try
{
	$AuthObject = new ApplicationAuthentication;
	$LoggedUser = new User(0, $AuthObject->CheckValidUser());
}

// THIS CATCH BLOCK BUBBLES THE EXCEPTION TO THE BUILT IN 'Exception' CLASS IF THERE ARE ANY UNCAUGHT ERRORS //
catch (ApplicationAuthException $e)
{
	header('location:../unauthorized_login_admin.php');
	exit;
}
catch (Exception $e)
{
	header('location:../unauthorized_login_admin.php');
	exit;
}
// END OF 1. //

if ($LoggedUser->HasPermissionForTask(TASK_FEE_COLLECTION) !== true)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}

$AcademicYearID = 0;
$AcademicYearName = '';

$AcademicYearID = AcademicYear::GetCurrentAcademicYear($AcademicYearName);

$AcademicYearExplode = explode('-' , $AcademicYearName);

$AcademicYearMonths =  array();
$AcademicYearMonths =  AcademicYearMonth::GetMonthsByFeePriority();

$GlobalSettingObject = new GlobalSetting();

$FeeSubmissionLastDate = '';
$FeeSubmissionFrequency = 0;
$FeeSubmissionType = '';

$FeeSubmissionLastDate = $GlobalSettingObject->GetFeeSubmissionLastDate();
$FeeSubmissionFrequency = $GlobalSettingObject->GetFeeSubmissionFrequency();
$FeeSubmissionType = $GlobalSettingObject->GetFeeSubmissionType();

$AcademicYearMonthID = 0;
$AcademicYearMonthID = AcademicYearMonth::GetMonthIDByMonthName(date('M'));

$FeePriority = 0;

$MonthWiseLateDays = array();
$LateFeeRules = array();

$LateCharge = 0;
$ChargeMethod = '';

$ClassList =  array();
$ClassList = AddedClass::GetActiveClasses();

$ClassSectionsList =  array();
$StudentsList = array();

$PaymentModeList = array(1 => 'Cash', 2 => 'Cheque', 3 => 'Net Transfer', 4 => 'Bank Transfer', 5 => 'Card Payment');

$HasErrors = false;

$Clean = array();

$Clean['Process'] = 0;

$Clean['FeeDate'] = date('d/m/Y');
$Clean['SearchType'] = 'ClassWise';

$Clean['FirstName'] = '';
$Clean['LastName'] = '';

$Clean['ClassName'] = '';
$Clean['ClassSectionID'] = '';

$Clean['StudentName'] = '';
$Clean['StudentID'] = 0;
$Clean['ClassID'] = 0;
$Clean['FeeCode'] = 0;

$Clean['OtherFee'] = 0;
$Clean['OtherFeeDiscription'] = '';
$Clean['OtherFeeAmount'] = 0;

$Clean['LateFee'] = 0;
$Clean['LateFeeDiscription'] = '';
$Clean['LateFeeAmount'] = 0;

$Clean['AmountPayable'] = 0;
$Clean['AmountPaid'] = 0;

$Clean['PaymentMode'] = 1;
$Clean['BankMode'] = 'ByCheck';

$Clean['ChequeReferenceNo'] = '';
$Clean['DDNumber'] = '';
$Clean['BankName'] = '';
$Clean['IFSC'] = '';
$Clean['MICR'] = '';

$Clean['MonthlyPayableAmount'] = 0;

$Clean['StudentFeeStructureList'] = array();
$Clean['MonthList'] = array();
$Clean['DueMonthList'] = array();

$Clean['FeeCollectionDetails'] = array();
$OtherChargesDetails = array();

$TotalAmount = 0;
$TotalDiscount = 0;

$AmountPaid = 0;

$FeeMonths = array();
$DueFeeMonths = array();
$AllStudents = array();

if (isset($_POST['hdnProcess']))
{
	$Clean['Process'] = (int) $_POST['hdnProcess'];
}
switch ($Clean['Process'])
{
	case 1:

		$NewRecordValidator = new Validator();

		if (isset($_POST['hdnStudentID'])) 
		{
			$Clean['StudentID'] = (int) $_POST['hdnStudentID'];
		}
		
		if (isset($_POST['hdnFeeDate'])) 
		{
			$Clean['FeeDate'] = strip_tags(trim($_POST['hdnFeeDate']));
		}

		if (isset($_POST['hdnSearchType'])) 
		{
			$Clean['SearchType'] = strip_tags(trim($_POST['hdnSearchType']));
		}

		if ($Clean['SearchType'] == 'StudentNameWise') 
		{
			if (isset($_POST['hdnStudentName'])) 
			{
				$Clean['StudentName'] = strip_tags(trim($_POST['hdnStudentName']));
			}
		}
		else if ($Clean['SearchType'] == 'ClassWise') 
		{
			if (isset($_POST['hdnClassID'])) 
			{
				$Clean['ClassID'] = (int) $_POST['hdnClassID'];
			}
			if (isset($_POST['hdnClassSectionID'])) 
			{
				$Clean['ClassSectionID'] = (int) $_POST['hdnClassSectionID'];
			}			

			if ($NewRecordValidator->ValidateInSelect($Clean['ClassID'], $ClassList, 'Please select a valid class.')) 
			{
				$ClassSectionsList = ClassSections::GetClassSections($Clean['ClassID']);

				if ($NewRecordValidator->ValidateInSelect($Clean['ClassSectionID'], $ClassSectionsList, 'Please select a valid section.')) 
				{
					$StudentsList = StudentDetail::GetStudentsByClassSectionID($Clean['ClassSectionID']);

					$NewRecordValidator->ValidateInSelect($Clean['StudentID'], $StudentsList, 'Please select a valid student.');
				}
			}
		}
		else if ($Clean['SearchType'] == 'FeeCodeWise')
		{
			if (isset($_POST['hdnFeeCode'])) 
			{
				$Clean['FeeCode'] = strip_tags(trim($_POST['hdnFeeCode']));
			}

			$NewRecordValidator->ValidateStrings($Clean['FeeCode'], 'Please enter fee code and it should be between 1 and 10 characters.', 1, 10);
		}
        
        if (isset($_POST['chkStudentFeeStructureID']) && is_array($_POST['chkStudentFeeStructureID']))
		{
			$Clean['StudentFeeStructureList'] = $_POST['chkStudentFeeStructureID'];
		}
		
		if (isset($_POST['chkMonth']) && is_array($_POST['chkMonth']))
		{
			$Clean['MonthList'] = $_POST['chkMonth'];
		}

		if (isset($_POST['chkDueMonth']) && is_array($_POST['chkDueMonth']))
		{
			$Clean['DueMonthList'] = $_POST['chkDueMonth'];
		}

       	if (isset($_POST['txtAmountPayable'])) 
		{
			$Clean['AmountPayable'] = strip_tags(trim($_POST['txtAmountPayable']));
		}

		if (isset($_POST['txtAmountPaid'])) 
		{
			$Clean['AmountPaid'] = strip_tags(trim($_POST['txtAmountPaid']));
		}

		if (isset($_POST['optPaymentMode'])) 
		{
			$Clean['PaymentMode'] = (int) $_POST['optPaymentMode'];
		}

		if ($NewRecordValidator->ValidateInSelect($Clean['PaymentMode'], $PaymentModeList, 'Invalid payment mode, please try again.')) 
		{
			if ($Clean['PaymentMode'] != 1) 
			{
				if (isset($_POST['txtChequeReferenceNo'])) 
				{
					$Clean['ChequeReferenceNo'] = strip_tags(trim($_POST['txtChequeReferenceNo']));
				}

				$NewRecordValidator->ValidateStrings($Clean['ChequeReferenceNo'], 'Cheque/Reference number is required and should be between 3 and 30 characters.', 3, 30);
			}
		}
        
        $NewRecordValidator->ValidateDate($Clean['FeeDate'], 'Please enter a valid fee date.');
		$NewRecordValidator->ValidateInteger($Clean['AmountPayable'], 'Unknown error in payable amount.', 1);
		$NewRecordValidator->ValidateInteger($Clean['AmountPaid'], 'Please enter valid paid amount.', 1);

		$AmountPaid = $Clean['AmountPaid'];

        if (isset($_POST['chkOtherFee'])) 
		{
			$Clean['OtherFee'] = 1;

			if (isset($_POST['txtOtherFeeDiscription'])) 
			{
				$Clean['OtherFeeDiscription'] = strip_tags(trim($_POST['txtOtherFeeDiscription']));
			}
			if (isset($_POST['hdnOtherFeeAmount'])) 
			{
				$Clean['OtherFeeAmount'] = (int) $_POST['hdnOtherFeeAmount'];
			}

			$NewRecordValidator->ValidateStrings($Clean['OtherFeeDiscription'], 'Other fee discription is required and should be between 3 and 50 characters.', 3, 50);
			$NewRecordValidator->ValidateInteger($Clean['OtherFeeAmount'], 'Please enter valid other fee amount.', 1);
		}

		if (isset($_POST['chkLateFee'])) 
		{
			$Clean['LateFee'] = 1;

			if (isset($_POST['txtLateFeeDiscription'])) 
			{
				$Clean['LateFeeDiscription'] = strip_tags(trim($_POST['txtLateFeeDiscription']));
			}
			if (isset($_POST['hdnLateFeeAmount'])) 
			{
				$Clean['LateFeeAmount'] = strip_tags(trim($_POST['hdnLateFeeAmount']));
			}

			$NewRecordValidator->ValidateInteger($Clean['LateFeeAmount'], 'Please enter valid late fee amount.', 1);
		}

		if ($Clean['OtherFee'] == 1) 
		{
			$OtherChargesDetails['OtherFee']['FeeType'] = 'OtherFee';
			$OtherChargesDetails['OtherFee']['FeeDescription'] = $Clean['OtherFeeDiscription'];
			$OtherChargesDetails['OtherFee']['Amount'] = $Clean['OtherFeeAmount'];
		}

		if ($Clean['LateFee'] == 1) 
		{
			$OtherChargesDetails['LateFee']['FeeType'] = 'LateFee';
			$OtherChargesDetails['LateFee']['FeeDescription'] = $Clean['LateFeeDiscription'];
			$OtherChargesDetails['LateFee']['Amount'] = $Clean['LateFeeAmount'];
		}

		if ($Clean['SearchType'] == 'FeeCodeWise') 
		{
			$AllStudents = Student::GetStudentsByFeeCode($Clean['FeeCode']);

			foreach ($AllStudents as $StudentID => $Details) 
			{
				try
				{
				    $StudentDetailObject = new StudentDetail($StudentID);    
				}
				catch (ApplicationDBException $e)
				{
				    header('location:/admin/error.php');
				    exit;
				}
				catch (Exception $e)
				{
				    header('location:/admin/error.php');
				    exit;
				}

				$FeeMonths[$StudentID] = $StudentDetailObject->GetStudentFeeMonths();

				$DueFeeMonths[$StudentID] = $StudentDetailObject->GetStudentDueFeeMonths();
			}

			// Get Monthly Fee Details
		}
		else
		{
			if ($Clean['StudentID'] <= 0) 
			{
				header('location:/admin/error.php');
			    exit;
			}

			try
			{
			    $StudentDetailObject = new StudentDetail($Clean['StudentID']);
			}
			catch (ApplicationDBException $e)
			{
			    header('location:/admin/error.php');
			    exit;
			}
			catch (Exception $e)
			{
			    header('location:/admin/error.php');
			    exit;
			}

			$FeeMonths[$Clean['StudentID']] = $StudentDetailObject->GetStudentFeeMonths();
			$DueFeeMonths[$Clean['StudentID']] = $StudentDetailObject->GetStudentDueFeeMonths();

			$AllStudents[$Clean['StudentID']]['StudentName'] = $StudentDetailObject->GetFirstName() . ' ' . $StudentDetailObject->GetLastName();

			$ClassSectionsList = ClassSections::GetClassSections($Clean['ClassID']);

			$AllStudents[$Clean['StudentID']]['Class'] = $ClassList[$Clean['ClassID']] . ' ' . $ClassSectionsList[$Clean['ClassSectionID']];
			$AllStudents[$Clean['StudentID']]['RollNumber'] = $StudentDetailObject->GetRollNumber();

			$NewParentDetailObject = new ParentDetail($StudentDetailObject->GetParentID());

			$AllStudents[$Clean['StudentID']]['FatherName'] = $NewParentDetailObject->GetFatherFirstName() . ' ' . $NewParentDetailObject->GetFatherLastName();
			$AllStudents[$Clean['StudentID']]['Contact'] = ($NewParentDetailObject->GetFatherMobileNumber() ? $NewParentDetailObject->GetFatherMobileNumber() : '--');

			/*$Clean['StudentName'] = $StudentDetailObject->GetFirstName() . ' ' . $StudentDetailObject->GetLastName();

			$Clean['ClassSectionID'] = $StudentDetailObject->GetClassSectionID();

	        $StudentsList = StudentDetail::GetStudentsByClassSectionID($Clean['ClassSectionID']);

	        $ClassSectionDetails = new ClassSections($Clean['ClassSectionID']);
	        $Clean['ClassID'] = $ClassSectionDetails->GetClassID();

	        $ClassSectionsList = ClassSections::GetClassSections($Clean['ClassID']);

	        $Clean['ClassName'] = $ClassList[$Clean['ClassID']] . ' ' . $ClassSectionsList[$Clean['ClassSectionID']];

	        $NewParentDetailObject = new ParentDetail($StudentDetailObject->GetParentID());*/

	       	// Get Monthly Fee Details    
		}

		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}

		if (isset($_POST['chkDueMonth']) && is_array($_POST['chkDueMonth']))
		{
			$Clean['DueMonthList'] = $_POST['chkDueMonth'];

			foreach ($Clean['DueMonthList'] as $StudentID => $SelectedMonths) 
			{
				$StudentAmountPaid = 0;
				$TotalAmount = 0;
				$TotalDiscount = 0;

				$StudentDetailObject = new StudentDetail($StudentID);    

				foreach ($SelectedMonths as $MonthID => $Details)
				{
					if ($AmountPaid <= 0) 
					{
						$NewRecordValidator->AttachTextError('Amount you paid is more less, please pay more or uncheck a month.');
						$HasErrors = true;
						break;
					}

					$StudentMonthlyFeeDetails = array();
					
					$StudentDetailObject->GetStudentMonthlyFeeDetails($MonthID, $StudentMonthlyFeeDetails);

					foreach ($StudentMonthlyFeeDetails as $StudentFeeStructureID => $MonthlyFeeDetail)
					{
						$FeeHeadPayableAmount = ($MonthlyFeeDetail['FeeAmount'] - $MonthlyFeeDetail['DiscountAmount'] - $MonthlyFeeDetail['AmountPaid']);

						if ($AmountPaid <= 0) 
						{
							$AmountPaid = 0;
						}

						if ($FeeHeadPayableAmount < $AmountPaid) 
						{
							$Clean['FeeCollectionDetails'][$StudentID]['StudentFeeCollectionDetails'][$StudentFeeStructureID] = $FeeHeadPayableAmount;
						}
						else
						{
							$Clean['FeeCollectionDetails'][$StudentID]['StudentFeeCollectionDetails'][$StudentFeeStructureID] = $AmountPaid;
						}
						
						$AmountPaid = $AmountPaid - $FeeHeadPayableAmount;
					}
				}

			}
		}  

        if (isset($_POST['chkMonth']) && is_array($_POST['chkMonth']))
		{
			$Clean['MonthList'] = $_POST['chkMonth'];

			foreach ($Clean['MonthList'] as $StudentID => $SelectedMonths) 
			{
				$StudentAmountPaid = 0;
				$TotalAmount = 0;
				$TotalDiscount = 0;

				$StudentDetailObject = new StudentDetail($StudentID);    

				foreach ($SelectedMonths as $MonthID => $Details)
				{
					if ($AmountPaid <= 0) 
					{
						$NewRecordValidator->AttachTextError('Amount you paid is more less, please pay more or uncheck a month.');
						$HasErrors = true;
						break;
					}

					$StudentMonthlyFeeDetails = array();

					$StudentDetailObject->GetStudentMonthlyFeeDetails($MonthID, $StudentMonthlyFeeDetails);

					foreach ($StudentMonthlyFeeDetails as $StudentFeeStructureID => $MonthlyFeeDetail)
					{
						if (array_key_exists($StudentFeeStructureID, $Clean['StudentFeeStructureList'])) 
						{
							$TotalAmount += $MonthlyFeeDetail['FeeAmount'];
							$TotalDiscount += $MonthlyFeeDetail['DiscountAmount'];

							$FeeHeadPayableAmount = ($MonthlyFeeDetail['FeeAmount'] - $MonthlyFeeDetail['DiscountAmount']);

							if ($AmountPaid <= 0) 
							{
								$AmountPaid = 0;
							}

							if ($FeeHeadPayableAmount < $AmountPaid) 
							{
								$Clean['FeeCollectionDetails'][$StudentID]['StudentFeeCollectionDetails'][$StudentFeeStructureID] = $FeeHeadPayableAmount;
								$StudentAmountPaid += $FeeHeadPayableAmount;
							}
							else
							{
								$Clean['FeeCollectionDetails'][$StudentID]['StudentFeeCollectionDetails'][$StudentFeeStructureID] = $AmountPaid;
								$StudentAmountPaid += $AmountPaid;
							}
							
							$AmountPaid = $AmountPaid - $FeeHeadPayableAmount;
						}
						else
						{
						    $Clean['FeeCollectionDetails'][$StudentID]['StudentFeeCollectionDetails'][$StudentFeeStructureID] = 0;
						}
					}
				}

				$Clean['AmountPayable'] += $Clean['OtherFeeAmount'] + $Clean['LateFeeAmount'];

				$TotalAmount += $Clean['OtherFeeAmount'] + $Clean['LateFeeAmount'];

				$Clean['FeeCollectionDetails'][$StudentID]['TotalAmount'] = $TotalAmount;
				$Clean['FeeCollectionDetails'][$StudentID]['TotalDiscount'] = $TotalDiscount;
				$Clean['FeeCollectionDetails'][$StudentID]['StudentAmountPaid'] = $StudentAmountPaid;
			}
		}    

		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}

		if (count($Clean['MonthList']) <= 0 && count($Clean['DueMonthList']) <= 0) 
		{
			$NewRecordValidator->AttachTextError('Please select atleast one month.');
			$HasErrors = true;
			break;
		}
        
		$NewFeeCollection = new FeeCollection();
				
		$NewFeeCollection->SetStudentID($Clean['StudentID']);
		$NewFeeCollection->SetFeeDate(date('Y-m-d', strtotime(DateProcessing::ToggleDateDayAndMonth(($Clean['FeeDate'])))));

		$NewFeeCollection->SetTotalAmount($TotalAmount);
		$NewFeeCollection->SetTotalDiscount($TotalDiscount);
		$NewFeeCollection->SetAmountPaid($Clean['AmountPaid']);
		$NewFeeCollection->SetPaymentMode($Clean['PaymentMode']);
		$NewFeeCollection->SetChequeReferenceNo($Clean['ChequeReferenceNo']);

		$NewFeeCollection->SetCreateUserID($LoggedUser->GetUserID());

		$NewFeeCollection->SetFeeCollectionDetails($Clean['FeeCollectionDetails']);		
		$NewFeeCollection->SetOtherChargesDetails($OtherChargesDetails);		

		if (!$NewFeeCollection->Save())
		{
			$NewRecordValidator->AttachTextError(ProcessErrors($NewFeeCollection->GetLastErrorCode()));
			$HasErrors = true;
			break;
		}
		
		$SelectedFeeCollectionIDs = implode('$', $NewFeeCollection->GetCurrentTransactionIDs());
		
		header('location:fee_receipt.php?FeeCollectionID='. $SelectedFeeCollectionIDs);
		exit;
	break;

	case 7:
		$NewRecordValidator = new Validator();

		if (isset($_POST['optSearchType'])) 
		{
			$Clean['SearchType'] = strip_tags(trim($_POST['optSearchType']));
		}
		
		if (isset($_POST['txtFeeDate'])) 
		{
			$Clean['FeeDate'] = strip_tags(trim($_POST['txtFeeDate']));
		}

		if ($Clean['SearchType'] == 'StudentNameWise') 
		{
			if (isset($_POST['txtStudentID'])) 
			{
				$Clean['StudentID'] = (int) $_POST['txtStudentID'];
			}
			if (isset($_POST['txtStudentName'])) 
			{
				$Clean['StudentName'] = strip_tags(trim($_POST['txtStudentName']));
			}

			$NewRecordValidator->ValidateStrings($Clean['StudentName'], 'Please enter student name and it should be between 3 and 50 characters.', 3, 50);

			if ($Clean['StudentID'] <= 0) 
			{
				$NewRecordValidator->AttachTextError('Student not found, please enter valid student name.');
				$HasErrors = true;
				break;
			}
		}
		else if ($Clean['SearchType'] == 'ClassWise') 
		{
			if (isset($_POST['drdClass'])) 
			{
				$Clean['ClassID'] = (int) $_POST['drdClass'];
			}
			if (isset($_POST['drdClassSection'])) 
			{
				$Clean['ClassSectionID'] = (int) $_POST['drdClassSection'];
			}
			if (isset($_POST['drdStudent'])) 
			{
				$Clean['StudentID'] = (int) $_POST['drdStudent'];
			}

			if ($NewRecordValidator->ValidateInSelect($Clean['ClassID'], $ClassList, 'Please select a valid class.')) 
			{
				$ClassSectionsList = ClassSections::GetClassSections($Clean['ClassID']);

				if ($NewRecordValidator->ValidateInSelect($Clean['ClassSectionID'], $ClassSectionsList, 'Please select a valid section.')) 
				{
					$StudentsList = StudentDetail::GetStudentsByClassSectionID($Clean['ClassSectionID']);

					$NewRecordValidator->ValidateInSelect($Clean['StudentID'], $StudentsList, 'Please select a valid student.');
				}
			}
		}
		else if ($Clean['SearchType'] == 'FeeCodeWise') 
		{
			if (isset($_POST['txtFeeCode'])) 
			{
				$Clean['FeeCode'] = strip_tags(trim($_POST['txtFeeCode']));
			}

			$NewRecordValidator->ValidateStrings($Clean['FeeCode'], 'Please enter fee code and it should be between 1 and 10 characters.', 1, 10);
		}

		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}

		if ($Clean['SearchType'] == 'FeeCodeWise')
		{
			$AllStudents = Student::GetStudentsByFeeCode($Clean['FeeCode']);

			foreach ($AllStudents as $StudentID => $Details) 
			{
				try
				{
				    $StudentDetailObject = new StudentDetail($StudentID);    
				}
				catch (ApplicationDBException $e)
				{
				    header('location:/admin/error.php');
				    exit;
				}
				catch (Exception $e)
				{
				    header('location:/admin/error.php');
				    exit;
				}

				$FeeMonths[$StudentID] = $StudentDetailObject->GetStudentFeeMonths();

				$DueFeeMonths[$StudentID] = $StudentDetailObject->GetStudentDueFeeMonths();

				// Late Fee Calculation
				foreach (array_chunk($AcademicYearMonths, $FeeSubmissionFrequency, true) as $Key => $Value) 
				{
					if (array_key_exists($AcademicYearMonthID, $Value)) 
				    {
				        end($Value);
				        $DefaultedLastFeeMonthPriority = $Value[key($Value)]['FeePriority'];
				    }
				}

				$DefaultedFeeMonths = array();
				$DefaultedFeeMonths[$StudentID] = FeeCollection::GetDefaultedFeeMonths($StudentID, $DefaultedLastFeeMonthPriority);

				$CurrentDate = time();
				$LateDays = 0;

				foreach (array_chunk($AcademicYearMonths, $FeeSubmissionFrequency, true) as $Key => $Value) 
				{
					$FeeSubmissionMonthID = key($Value);

					foreach ($Value as $MonthID => $Details) 
					{
						if (array_key_exists($MonthID, $DefaultedFeeMonths[$StudentID])) 
					    {
							if ($Value[$FeeSubmissionMonthID]['MonthName'] == 'January' || $Value[$FeeSubmissionMonthID]['MonthName'] == 'February' || $Value[$FeeSubmissionMonthID]['MonthName'] == 'March') 
							{
								$FeeSubmissionDate = $FeeSubmissionLastDate .' '. $Value[$FeeSubmissionMonthID]['MonthName'] .' '. $AcademicYearExplode[1];
							}
							else
							{
								$FeeSubmissionDate = $FeeSubmissionLastDate .' '. $Value[$FeeSubmissionMonthID]['MonthName'] .' '. $AcademicYearExplode[0];
							}

							$LateDays = intval(($CurrentDate - strtotime($FeeSubmissionDate)) / 86400) ;

							$MonthWiseLateDays[$MonthID] = $LateDays; 
					    }
					}	
					// $MonthWiseLateDays[$MonthID] = $LateDays; 	    
				}
			}
		}
		else
		{
			if ($Clean['StudentID'] <= 0) 
			{
				header('location:/admin/error.php');
			    exit;
			}

			try
			{
			    $StudentDetailObject = new StudentDetail($Clean['StudentID']);    
			}
			catch (ApplicationDBException $e)
			{
			    header('location:/admin/error.php');
			    exit;
			}
			catch (Exception $e)
			{
			    header('location:/admin/error.php');
			    exit;
			}

			$FeeMonths = array();
			$FeeMonths[$Clean['StudentID']] = $StudentDetailObject->GetStudentFeeMonths();

			$DueFeeMonths = array();
			$DueFeeMonths[$Clean['StudentID']] = $StudentDetailObject->GetStudentDueFeeMonths();

			$AllStudents[$Clean['StudentID']]['StudentName'] = $StudentDetailObject->GetFirstName() . ' ' . $StudentDetailObject->GetLastName();

			$ClassSectionsList = ClassSections::GetClassSections($Clean['ClassID']);

			$AllStudents[$Clean['StudentID']]['Class'] = $ClassList[$Clean['ClassID']] . ' ' . $ClassSectionsList[$Clean['ClassSectionID']];
			$AllStudents[$Clean['StudentID']]['RollNumber'] = $StudentDetailObject->GetRollNumber();

			$NewParentDetailObject = new ParentDetail($StudentDetailObject->GetParentID());

			$AllStudents[$Clean['StudentID']]['FatherName'] = $NewParentDetailObject->GetFatherFirstName() . ' ' . $NewParentDetailObject->GetFatherLastName();
			$AllStudents[$Clean['StudentID']]['Contact'] = ($NewParentDetailObject->GetFatherMobileNumber() ? $NewParentDetailObject->GetFatherMobileNumber() : '--');

			// $Clean['StudentName'] = $StudentDetailObject->GetFirstName() . ' ' . $StudentDetailObject->GetLastName();

			/*$Clean['ClassSectionID'] = $StudentDetailObject->GetClassSectionID();

	        $StudentsList = StudentDetail::GetStudentsByClassSectionID($Clean['ClassSectionID']);
	        
			$Clean['ClassID'] = $ClassSectionDetails->GetClassID();

	        $ClassSectionsList = ClassSections::GetClassSections($Clean['ClassID']);

	        $Clean['ClassName'] = $ClassList[$Clean['ClassID']] . ' ' . $ClassSectionsList[$Clean['ClassSectionID']];*/

			// Late Fee Calculation
			foreach (array_chunk($AcademicYearMonths, $FeeSubmissionFrequency, true) as $Key => $Value) 
			{
				if (array_key_exists($AcademicYearMonthID, $Value)) 
			    {
			        end($Value);
			        $DefaultedLastFeeMonthPriority = $Value[key($Value)]['FeePriority'];
			    }
			}

			$DefaultedFeeMonths = array();
			$DefaultedFeeMonths[$Clean['StudentID']] = FeeCollection::GetDefaultedFeeMonths($Clean['StudentID'], $DefaultedLastFeeMonthPriority);

			$CurrentDate = time();
			$LateDays = 0;

			foreach (array_chunk($AcademicYearMonths, $FeeSubmissionFrequency, true) as $Key => $Value) 
			{
				$FeeSubmissionMonthID = key($Value);

				foreach ($Value as $MonthID => $Details) 
				{
					if (array_key_exists($MonthID, $DefaultedFeeMonths)) 
				    {
						if ($Value[$FeeSubmissionMonthID]['MonthName'] == 'January' || $Value[$FeeSubmissionMonthID]['MonthName'] == 'February' || $Value[$FeeSubmissionMonthID]['MonthName'] == 'March') 
						{
							$FeeSubmissionDate = $FeeSubmissionLastDate .' '. $Value[$FeeSubmissionMonthID]['MonthName'] .' '. $AcademicYearExplode[1];
						}
						else
						{
							$FeeSubmissionDate = $FeeSubmissionLastDate .' '. $Value[$FeeSubmissionMonthID]['MonthName'] .' '. $AcademicYearExplode[0];
						}

						$LateDays = intval(($CurrentDate - strtotime($FeeSubmissionDate)) / 86400) ;

						$MonthWiseLateDays[$MonthID] = $LateDays; 
				    }
				}	
				// $MonthWiseLateDays[$MonthID] = $LateDays; 	    
			}
		}

		$LateFeeRules = LateFeeRule::GetAllLateFeeRules();

	break;
}

$LandingPageMode = '';
if (isset($_GET['Mode']))
{
    $LandingPageMode = $_GET['Mode'];
}

require_once('../html_header.php');
?>
<title>Fee Collection</title>
<link href="../vendor/jquery-ui/jquery-ui.min.css" rel="stylesheet">
</head>

<body>

    <div id="wrapper">
    	<!-- Navigation -->
        <nav class="navbar navbar-default navbar-static-top" role="navigation" style="margin-bottom: 0">
<?php 
			require_once('../site_header.php');
			require_once('../left_navigation_menu.php');
?>                    
            <!-- /.navbar-static-side -->
        </nav>

        <div id="page-wrapper">
            <div class="row">
                <div class="col-lg-12">
                    <h1 class="page-header">Fee Collection</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="FeeCollection" action="fee_collection.php" method="post">
            	<div class="panel panel-default">
                    <div class="panel-heading">
                        <strong>Fee Collection Details</strong>
                    </div>
                    <div class="panel-body">
<?php
						if ($HasErrors == true)
						{
							echo $NewRecordValidator->DisplayErrors();
						}
	                    else if ($LandingPageMode == 'AS')
	                    {
	                        echo '<div class="alert alert-success">Record saved successfully.</div>';
	                    }
?>                    
						<div class="form-group">
                            <label for="AcademicYear" class="col-lg-2 control-label">Academic Year</label>
                            <div class="col-lg-3">
                            	<input class="form-control" type="text" maxlength="10" id="AcademicYear" name="txtAcademicYear" readonly="readonly" value="<?php echo $AcademicYearName; ?>" />
                            </div>
                            <label for="FeeDate" class="col-lg-2 control-label">Fee Date</label>
                            <div class="col-lg-3">
                            	<input class="form-control select-date" type="text" maxlength="10" id="FeeDate" name="txtFeeDate" value="<?php echo $Clean['FeeDate']; ?>" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="SearchType" class="col-lg-2 control-label"></label>
                            <div class="col-lg-8">
                                <label class="col-sm-4"><input class="custom-radio" type="radio" name="optSearchType" value="StudentNameWise" <?php echo ($Clean['SearchType'] == 'StudentNameWise' ? 'checked="checked"' : ''); ?> >&nbsp;&nbsp;By Student</label>
                                <label class="col-sm-4"><input class="custom-radio" type="radio" name="optSearchType" value="ClassWise" <?php echo ($Clean['SearchType'] == 'ClassWise' ? 'checked="checked"' : ''); ?> >&nbsp;&nbsp;By Class</label>
                                <label class="col-sm-4"><input class="custom-radio" type="radio" name="optSearchType" value="FeeCodeWise" <?php echo ($Clean['SearchType'] == 'FeeCodeWise' ? 'checked="checked"' : ''); ?> >&nbsp;&nbsp;By Fee Code</label>
                            </div>
                        </div>

                        <div id="StudentNameWise" class="SearchType" <?php echo ($Clean['SearchType'] == 'StudentNameWise') ? '' : 'style="display: none;"' ;?>>
                        	<div class="form-group">
	                            <label for="StudentName" class="col-lg-2 control-label">Student Name</label>
	                            <div class="col-lg-8">
	                                <input class="form-control" list="StudentList" maxlength="50" id="StudentName" name="txtStudentName" value="<?php echo ($Clean['StudentName']); ?>" />
	                                <datalist id="StudentList">
                                    </datalist>
                                    <input type="hidden" name="txtStudentID" id="StudentID" value="<?php echo $Clean['StudentID'] ? $Clean['StudentID'] : '' ; ?>">
	                            </div>
	                        </div>
                        </div>

                        <div id="ClassWise" class="SearchType" <?php echo ($Clean['SearchType'] == 'ClassWise') ? '' : 'style="display: none;"' ;?>>
                        	<div class="form-group">
                                <label for="ClassList" class="col-lg-2 control-label">Class List</label>
                                <div class="col-lg-3">
                                    <select class="form-control" name="drdClass" id="Class">
                                        <option  value="0" >Select Class</option>
<?php
                                        foreach ($ClassList as $ClassID => $ClassName)
                                        {
?>
                                            <option <?php echo ($ClassID == $Clean['ClassID'] ? 'selected="selected"' : ''); ?> value="<?php echo $ClassID; ?>"><?php echo $ClassName; ?></option>
<?php
                                        }
?>
                                    </select>
                                </div>
                                <label for="ClassSection" class="col-lg-2 control-label">Section List</label>
                                <div class="col-lg-3">
                                    <select class="form-control" name="drdClassSection" id="ClassSection">
                                        <option value="0">Select Section</option>
<?php
                                            if (is_array($ClassSectionsList) && count($ClassSectionsList) > 0)
                                            {
                                                foreach ($ClassSectionsList as $ClassSectionID => $SectionName) 
                                                {
                                                    echo '<option ' . ($Clean['ClassSectionID'] == $ClassSectionID ? 'selected="selected"' : '') . ' value="' . $ClassSectionID . '">' . $SectionName . '</option>' ;
                                                }
                                            }
?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="Student" class="col-lg-2 control-label">Student</label>
                                <div class="col-lg-8">
                                    <select class="form-control" name="drdStudent" id="Student">
                                        <option value="0">Select Student</option>
<?php
                                            if (is_array($StudentsList) && count($StudentsList) > 0)
                                            {
                                                foreach ($StudentsList as $StudentID=>$StudentDetails)
                                                {
                                                    echo '<option ' . ($Clean['StudentID'] == $StudentID ? 'selected="selected"' : '') . ' value="' . $StudentID . '">' . $StudentDetails['FirstName'] . ' ' . $StudentDetails['LastName'] . '(' . $StudentDetails['RollNumber'] . ')</option>'; 
                                                }
                                            }
?>
                                    </select>
                                </div>                                
                            </div>
                        </div>

                        <div id="FeeCodeWise" class="SearchType" <?php echo ($Clean['SearchType'] == 'FeeCodeWise') ? '' : 'style="display: none;"' ;?>>
                        	<div class="form-group">
	                            <label for="FeeCode" class="col-lg-2 control-label">Fee Code</label>
	                            <div class="col-lg-8">
                                    <input type="text" class="form-control" name="txtFeeCode" id="FeeCode" value="<?php echo $Clean['FeeCode'] ? $Clean['FeeCode'] : '' ;?>">
	                            </div>
	                        </div>
                        </div>
                        
                        <div class="form-group">
                        <div class="col-sm-offset-2 col-lg-10">
                        	<input type="hidden" name="hdnProcess" value="7" />
							<button type="submit" class="btn btn-primary"><i class="fa fa-search"></i>&nbsp;Search</button>
                        </div>
                      </div>
                  </form>
<?php
				if ($Clean['Process'] == 1 || $Clean['Process'] == 7 && count($AllStudents) > 0) 
				{
?>
					<div class="row">
                        <div class="col-lg-12">
                            <div class="panel panel-default">
                                <div class="panel-heading">
                                    <strong>Student's Basic Details:</strong>
                                </div>
                                <!-- /.panel-heading -->
                                <div class="panel-body">
                                    <div>
                                        <div class="row">
<?php
                                    if (is_array($AllStudents) && count($AllStudents) > 0)
                                    {
										foreach ($AllStudents as $StudentID => $Details) 
										{
?>
											<div class="col-lg-4"><b>Student Name: </b><?php echo $Details['StudentName']; ?></div>
											<div class="col-lg-4"><b>Class: </b><?php echo $Details['Class']; ?></div>
											<div class="col-lg-4"><b>Roll Number: </b><?php echo ($Details['RollNumber'] ? $Details['RollNumber'] : '--'); ?></div>
											<br /><br />
<?php
										}
?>
											<div class="col-lg-4"><b>Father Name: </b><?php echo $Details['FatherName']; ?></div>
											<div class="col-lg-4"><b>Contact No: </b><?php echo ($Details['Contact'] ? $Details['Contact'] : '--'); ?></div>
<?php
                                    }
?>		
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                  	<form class="form-horizontal" name="FeeCollection" id="RecordForm" action="fee_collection.php" method="post">
                        <div class="row" id="RecordTable">
                            <div class="col-lg-12">
                                <table width="100%" class="table table-striped table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th>Fee Types</th>
                                            <th colspan="2" class="text-center">Details</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    	<tr>
                                    		<td>
<?php
										foreach ($AllStudents as $StudentID => $Details) 
										{
											echo 'Monthly Fee (<small>'. $Details['StudentName'] .'</small>)<br>';
										}
?>
                                    		</td>
                                    		<td colspan="2">
<?php 
									foreach ($AllStudents as $StudentID => $Details) 
									{
										foreach ($FeeMonths[$StudentID] as $MonthID => $MonthDetails) 
										{
											echo '<label class="checkbox-inline"><input class="custom-radio MonthID" type="checkbox" id="' . $MonthID . '" name="chkMonth['. $StudentID .']['. $MonthID .']" '.(array_key_exists($StudentID, $Clean['MonthList']) ? (array_key_exists($MonthID, $Clean['MonthList'][$StudentID]) ? 'checked="checked"' : '') : '' ).'  value="' . $StudentID . '" />
                                                ' . $MonthDetails['MonthShortName'] . '</label>';
										}
										echo '<br>';
									}	
?>		
												<button type="button" class="btn btn-info btn-sm pull-right" data-toggle="modal" data-target="#ViewFeeDetails">View Details &nbsp;<i class="fa fa-angle-double-right"></i></button>
                                    		</td>
                                    	</tr>
                                    	<tr>
                                    		<td>Fee Due Details</td>
                                    		<td colspan="2">
<?php 
									foreach ($AllStudents as $StudentID => $Details) 
									{	
										foreach ($DueFeeMonths[$StudentID] as $MonthID => $DueMonthDetails) 
										{
											echo '<label class="checkbox-inline"><input class="custom-radio DueMonthID" type="checkbox" id="Due' . $MonthID . '" name="chkDueMonth['. $StudentID .']['. $MonthID .']" '.(array_key_exists($StudentID, $Clean['DueMonthList']) ? (array_key_exists($MonthID, $Clean['DueMonthList'][$StudentID]) ? 'checked="checked"' : '') : '' ).'  value="' . $StudentID . '" />
                                                ' . $DueMonthDetails['MonthShortName'] . ' ( <small class="text-danger" id="' . $MonthID . 'Due"><i class="fa fa-inr"></i>&nbsp;' .$DueMonthDetails['DueAmount']. ' </small>)</label>';
										}
									}
?>
											</td>
                                    	</tr>
                                    	<tr>
                                    		<td><label class="checkbox-inline"><input class="custom-radio" type="checkbox" id="OtherFee" name="chkOtherFee" <?php echo $Clean['OtherFee'] ? 'checked="checked"' : '' ;?> />Other Fee</label>
                                            </td>
                                    		<td><input type="text" class="form-control" name="txtOtherFeeDiscription" id="OtherFeeDiscription" placeholder="Enter Description" <?php echo $Clean['OtherFee'] ? '' : 'disabled="disabled"' ;?> value="<?php echo $Clean['OtherFeeDiscription'] ;?>"></td>
                                    		<td><input type="text" class="form-control" name="txtOtherFeeAmount" id="OtherFeeAmount" placeholder="Enter Other Fee Amount" disabled="disabled" value="<?php echo ($Clean['OtherFeeAmount']) ? $Clean['OtherFeeAmount'] : '' ;?>">
                                    			<input type="hidden" name="hdnOtherFeeAmount" id="hdnOtherFeeAmount" value="<?php echo $Clean['OtherFeeAmount'] ;?>"></td>
                                    	</tr>
                                    	<tr>
                                    		<td><label class="checkbox-inline"><input class="custom-radio" type="checkbox" id="LateFee" name="chkLateFee"  <?php echo ($Clean['LateFee'] ? 'checked="checked"' : '') ;?> />Late Fee</label>
                                    		</td>
                                    		<!-- <td><input type="text" class="form-control" name="txtLateFeeDiscription" id="LateFeeDiscription" placeholder="Enter Description" <?php echo $Clean['LateFee'] ? '' : 'disabled="disabled"' ;?> value="<?php echo $Clean['LateFeeDiscription'] ;?>">
                                    		</td> -->
                                    		<td>
<?php
									$TotalLateCharge = 0;
									foreach ($MonthWiseLateDays as $MonthID => $LateDays) 
									{
										foreach ($LateFeeRules as $Key => $Details) 
										{
											if ($LateDays >= $Details['RangeFromDay'] && $LateDays <= $Details['RangeToDay']) 
											{
												$LateCharge = $Details['LateFeeAmount'];
												$ChargeMethod = $Details['ChargeMethod'];
												break;
											}
											else
											{
											    end($LateFeeRules);
												$LateCharge = $LateFeeRules[key($LateFeeRules)]['LateFeeAmount'];
												$ChargeMethod = $LateFeeRules[key($LateFeeRules)]['ChargeMethod'];
											}
										}

										if ($ChargeMethod == 'PerDay') 
										{
											echo '<i class="fa fa-circle text-danger" style="font-size:6px;"></i>&nbsp;&nbsp;<small class="text-danger">'.$AcademicYearMonths[$MonthID]['MonthName']. ' Late by '. $LateDays .' days = '. number_format($LateCharge * $LateDays, 2) .'</small><br>';

											$TotalLateCharge += $LateCharge * $LateDays;
										}
										else
										{
											echo '<i class="fa fa-circle text-danger" style="font-size:6px;"></i>&nbsp;&nbsp;<small class="text-danger">'. $AcademicYearMonths[$MonthID]['MonthName']. ' Late by '. $LateDays.' days = '. number_format($LateCharge, 2) .'</small><br>';
											$TotalLateCharge += $LateCharge;
										}
									}

									if ($Clean['LateFeeAmount'] == 0) 
									{
										$Clean['LateFeeAmount'] = $TotalLateCharge;
									}
?>                                    			
                                    		</td>
                                    		<td><input type="text" class="form-control" name="txtLateFeeAmount" id="LateFeeAmount" placeholder="Enter Late Fee Amount" disabled="disabled" value="<?php echo $Clean['LateFeeAmount'] ? $Clean['LateFeeAmount'] : '' ;?>">
                                    		<input type="hidden" name="hdnLateFeeAmount" id="hdnLateFeeAmount" value="<?php echo $Clean['LateFeeAmount'] ;?>"></td>
                                    	</tr>
                                    	<tr>
                                    		<td>Amount Payable</td>
                                    		<td><input type="text" class="form-control" name="txtAmountPayable" id="AmountPayable" readonly="readonly" value="<?php echo $Clean['AmountPayable'] ? $Clean['AmountPayable'] : '' ;?>">
                                    		</td>
                                    		<td></td>                                   		
                                    	</tr>
                                    	<tr>
                                    		<td>Amount Paid</td>
                                    		<td><input type="text" class="form-control" name="txtAmountPaid" id="AmountPaid"  value="<?php echo $Clean['AmountPaid'] ? $Clean['AmountPaid'] : '' ;?>">
                                    		</td>
                                    		<td></td>                                   		
                                    	</tr>
                                    </tbody>
                                </table>

                                <div class="form-group">
		                            <label for="PaymentMode" class="col-lg-2 control-label">Payment Mode</label>
		                            <div class="col-lg-7">
<?php
									foreach ($PaymentModeList as $PaymentModeID => $PaymentModeName) 
									{
?>
										<label class="col-sm-3"><input class="custom-radio" type="radio" name="optPaymentMode" value="<?php echo $PaymentModeID; ?>" <?php echo ($Clean['PaymentMode'] == $PaymentModeID ? 'checked="checked"' : ''); ?> >&nbsp;&nbsp;<?php echo $PaymentModeName; ?></label>
<?php										
									}
?>		                            	
		                            </div>
		                        </div>
		                        <div id="CheckDetails" <?php echo ($Clean['PaymentMode'] == 1 ? 'style="display: none;"' : ''); ?>>
		                        	<div class="form-group">
			                            <label for="ChequeReferenceNo" class="col-lg-2 control-label">Cheque No. / Ref.No.</label>
			                            <div class="col-lg-4">
			                                <input class="form-control" type="text" maxlength="30" id="ChequeReferenceNo" name="txtChequeReferenceNo" value="<?php echo ($Clean['ChequeReferenceNo']); ?>"/>
			                            </div>
			                        </div>
		                        </div>
                            </div>
                            <div class="form-group">
								<div class="col-sm-offset-5 col-lg-12">
									<input type="hidden" name="hdnClassID" value="<?php echo $Clean['ClassID']; ?>" />
									<input type="hidden" name="hdnClassSectionID" value="<?php echo $Clean['ClassSectionID'] ;?>" />
									<input type="hidden" name="hdnStudentID" value="<?php echo $Clean['StudentID'] ;?>" />
									<input type="hidden" name="hdnStudentName" value="<?php echo $Clean['StudentName'] ;?>" />
									<input type="hidden" name="hdnFeeCode" value="<?php echo $Clean['FeeCode'] ;?>" />
									<input type="hidden" name="hdnSearchType" value="<?php echo $Clean['SearchType'] ;?>" />
									<input type="hidden" name="hdnFeeDate" value="<?php echo $Clean['FeeDate'] ;?>" />
									<input type="hidden" name="hdnProcess" value="1" />
									<button type="submit" class="btn btn-primary"><i class="fa fa-inr"></i>&nbsp;Pay</button>
								</div>
							</div>
                        </div>

                        <div id="ViewFeeDetails" class="modal fade" role="dialog">
						  <div class="modal-dialog">

						    <!-- Modal content-->
						    <div class="modal-content">
						      <div class="modal-header btn-info">
						        <button type="button" class="close" data-dismiss="modal">&times;</button>
						        <h4 class="modal-title">Fee Details</h4>
						      </div>
						      <div class="modal-body">
						        <div class="row">
			                        <div class="col-lg-12" id="FeeDetails"></div>
			                    </div>
						      </div>
						      <div class="modal-footer">
						        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
						      </div>
						    </div>
						  </div>
						</div>

                    </form>
<?php
				}
 ?>
                </div>
            </div>

        </div>
        <!-- /#page-wrapper -->

    </div>
    <!-- /#wrapper -->
<?php
require_once('../footer.php');
?>
<script src="../vendor/jquery-ui/jquery-ui.min.js"></script>
<script type="text/javascript">
$(document).ready(function() {
	$(".select-date").datepicker({
		changeMonth: true,
		changeYear: true,
		dateFormat: 'dd/mm/yy'
	});

	$("input[name='optSearchType']").change(function() {
        var check = $(this).attr('checked', 'checked').val();

        if ($(this).attr('checked', 'checked').val() == 'StudentNameWise') 
        {
        	$('#ClassWise').slideUp();
        	$('#StudentNameWise').slideDown();
        	$('#FeeCodeWise').slideUp();
        }
        
        if ($(this).attr('checked', 'checked').val() == 'ClassWise') 
        {
        	$('#StudentNameWise').slideUp();
        	$('#ClassWise').slideDown();
        	$('#FeeCodeWise').slideUp();	
        }

        if ($(this).attr('checked', 'checked').val() == 'FeeCodeWise') 
        {
        	$('#FeeCodeWise').slideDown();
        	$('#StudentNameWise').slideUp();
        	$('#ClassWise').slideUp();	
        }
    });

    $("input[name='optPaymentMode']").change(function() {
        
        if ($(this).val() == 2 || $(this).val() == 3) 
        {
        	$("#CheckDetails").slideDown();
        }
        else
        {
        	$("#CheckDetails").slideUp();
        }
    });

    $("input[name='optBankMode']").change(function() {
        
        if ($(this).val() == 'ByDemandDraft') 
        {
        	$("#DemandDraftDetails").slideDown();
        	$("#CheckDetails").slideUp();
        }
        else
        {
        	$("#DemandDraftDetails").slideUp();
        	$("#CheckDetails").slideDown();
        }
    });

	$('#Class').change(function(){

        var ClassID = parseInt($(this).val());
        
        if (ClassID <= 0)
        {
            $('#ClassSection').html('<option value="0">Select Section</option>');
            return;
        }
        
        $.post("/xhttp_calls/get_sections_by_classs.php", {SelectedClassID:ClassID}, function(data)
        {
            ResultArray = data.split("|*****|");
            
            if (ResultArray[0] == 'error')
            {
                alert (ResultArray[1]);
                return false;
            }
            else
            {
                $('#ClassSection').html('<option value="0">Select Section</option>' + ResultArray[1]);
            }
         });
    });

    $('#ClassSection').change(function(){

        var ClassSectionID = parseInt($(this).val());
        
        if (ClassSectionID <= 0)
        {
            $('#Student').html('<option value="0">Select Student</option>');
            return;
        }
        
        $.post("/xhttp_calls/get_students_by_class_section.php", {SelectedClassSectionID:ClassSectionID}, function(data)
        {
            ResultArray = data.split("|*****|");
            
            if (ResultArray[0] == 'error')
            {
                alert (ResultArray[1]);
                return false;
            }
            else
            {
                $('#Student').html(ResultArray[1]);
            }
        });
    });

    $('#StudentName').keyup(function(){

        var StudentName = $(this).val();

        if (StudentName == '') 
        {
        	return false;
        }
               
        $.post("/xhttp_calls/get_students_by_name.php", {SelectedStudentName:StudentName}, function(data)
        {
            ResultArray = data.split("|*****|");
            
            if (ResultArray[0] == 'error')
            {
                alert (ResultArray[1]);
                return false;
            }
            else
            {
                $('#StudentList').html(ResultArray[1]);
                
                var StudentName = $('#StudentName').val();
		    	var StudentID = $('#StudentList').find('option[value="' + StudentName + '"]').attr('id');

		    	if (StudentID != undefined) 
		    	{
		    		$('#StudentID').val(StudentID);
		    	}
            }
        });
    });

    $('#StudentName').change(function(){

    	var StudentName = $('#StudentName').val();
    	var StudentID = $('#StudentList').find('option[value="' + StudentName + '"]').attr('id');

    	if (StudentID != undefined) 
    	{
    		$('#StudentID').val(StudentID);
    	}
    });

<?php 
    	 
	if (count($Clean['MonthList']) > 0) 
	{
		foreach ($Clean['MonthList'] as $StudentID => $Details)
		{
			foreach ($Details as $MonthID => $Details) 
			{
?>
			var MonthID = '<?php echo $MonthID; ?>';
			var StudentID = '<?php echo $StudentID; ?>';	       
		   
		    if (MonthID <= 0 || StudentID <= 0)
		    {
		        alert('Error! No record found.');
		        return;
		    }
		    
		    $.post("/xhttp_calls/get_student_fees_by_months.php", {SelectedMonthID:MonthID, SelectedStudentID:StudentID}, function(data)
		    {
		        ResultArray = data.split("|*****|");
		        
		        if (ResultArray[0] == 'error')
		        {
		            alert (ResultArray[1]);
		            $('#' + MonthID).prop('checked', false);
		            return false;
		        }
		        else
		        {
		        	if ($('#' + MonthID).is(':checked')) 
		        	{
		        		var ModalHtml = $('#FeeDetails').html();
		            	$('#FeeDetails').html(ModalHtml + ResultArray[2]); //Fee Breakup By month
		        	}
		        }
		    });

<?php
			}
		}
	}
?>

    $('.MonthID').change(function(){

        var MonthID = parseInt($(this).attr('id'));
        var StudentID = parseInt($(this).val());
        var AmountPayable = parseInt($('#AmountPayable').val());
       
       	var Check = $(this);
// alert(Check.val());
    	if (isNaN(AmountPayable)) 
    	{
    		AmountPayable = 0;
    	}

        if (MonthID <= 0 || StudentID <= 0)
        {
            alert('Error! No record found.');
            return;
        }
        
        $.post("/xhttp_calls/get_student_fees_by_months.php", {SelectedMonthID:MonthID, SelectedStudentID:StudentID}, function(data)
        {
            ResultArray = data.split("|*****|");
            
            if (ResultArray[0] == 'error')
            {
                alert (ResultArray[1]);
                Check.prop('checked', false);
                return false;
            }
            else
            {
            	var TotalMonthlyFeeAmount = parseInt(ResultArray[1]); // Total Amount Payable
            	
            	if (isNaN(TotalMonthlyFeeAmount)) 
            	{
            		alert('There is an error in fee amount.');
            		Check.prop('checked', false);
            		return false;
            	}
            	
            	if (Check.prop('checked') == true) 
            	{
            		TotalPayableAmount = AmountPayable + TotalMonthlyFeeAmount;

            		var ModalHtml = $('#FeeDetails').html();
                	$('#FeeDetails').html(ModalHtml + ResultArray[2]); //Fee Breakup By month
            	}
            	else
            	{
            // 		TotalPayableAmount = AmountPayable - TotalMonthlyFeeAmount;
            		var MonthTotalFee = 0;
            		
            		$('.FeeHeadCheckBox' + MonthID).each(function(){
            		    
            		    if ($(this).prop('checked') == true)
            		    {
            		        MonthTotalFee = (MonthTotalFee + parseInt($(this).val()));    
            		    }
            		});
            		
            		TotalPayableAmount = AmountPayable - MonthTotalFee;
            		
                	$('#RecordTable' + MonthID).remove();
                	// Check.val('');
            	}
            	
                $('#AmountPayable').val(TotalPayableAmount);
                $('#AmountPaid').val(TotalPayableAmount);
            }
        });
    });

    $('.DueMonthID').change(function(){

        var MonthID = $(this).attr('id').slice(3);
        var AmountPayable = parseInt($('#AmountPayable').val());
        var DueAmount = parseInt($('#' + MonthID + 'Due').text());
       	
    	if (isNaN(AmountPayable)) 
    	{
    		AmountPayable = 0;
    	}
    	
    	if (isNaN(DueAmount)) 
    	{
    		DueAmount = 0;
    	}

    	if ($(this).is(':checked')) 
       	{
       		$('#AmountPayable').val(AmountPayable + DueAmount);
       		$('#AmountPaid').val(AmountPayable + DueAmount);
       	}
       	else
       	{
       		$('#AmountPayable').val(AmountPayable - DueAmount);	
       		$('#AmountPaid').val(AmountPayable - DueAmount);	
       	}
    });
    
    $('body').on('change', '.StudentFeeStructureID', function(){

    	if ($(this).is(':checked')) 
    	{
    		var ID = $(this).attr('id');
    		var HeadAmount = parseInt($('#HeadAmount'+ ID).val());

    		var AmountPayable = parseInt($('#AmountPayable').val());

    		if (isNaN(AmountPayable)) 
	    	{
	    		AmountPayable = 0;
	    	}

	    	if (isNaN(HeadAmount)) 
	    	{
	    		HeadAmount = 0;
	    	}

    		$('#AmountPayable').val(AmountPayable + HeadAmount);
    		$('#AmountPaid').val(AmountPayable + HeadAmount);
    	}
    	else
    	{
    		var ID = $(this).attr('id');
    		var HeadAmount = parseInt($('#HeadAmount'+ ID).val());

    		var AmountPayable = parseInt($('#AmountPayable').val());

    		if (isNaN(AmountPayable)) 
	    	{
	    		AmountPayable = 0;
	    	}

	    	if (isNaN(HeadAmount)) 
	    	{
	    		HeadAmount = 0;
	    	}

    		$('#AmountPayable').val(AmountPayable - HeadAmount);
    		$('#AmountPaid').val(AmountPayable - HeadAmount);
    	}
    });

    $('#OtherFee').change(function(){
    	if ($(this).is(':checked')) 
    	{
    		$('#OtherFeeDiscription').prop('disabled', false);
    		$('#OtherFeeAmount').prop('disabled', false);
    	}
    	else
    	{
    		$('#OtherFeeDiscription').prop('disabled', true);
    		$('#OtherFeeAmount').prop('disabled', true);
    		
    		if ($('#OtherFeeAmount').val() != '') 
    		{
    			var AmountPayable = parseInt($('#AmountPayable').val());
	    		var OtherFeeAmount = parseInt($('#OtherFeeAmount').val());

	    		$('#AmountPayable').val(AmountPayable - OtherFeeAmount);
	    		$('#AmountPaid').val(AmountPayable - OtherFeeAmount);
	    		$('#OtherFeeAmount').val('');
	    		$('#OtherFeeDiscription').val('');
    		}
    	}
    });

    $('#OtherFeeAmount').focusout(function(){
    	if ($(this).val() != '') 
    	{
    		var AmountPayable = parseInt($('#AmountPayable').val());
    		var OtherFeeAmount = parseInt($('#OtherFeeAmount').val());

	    	if (isNaN(AmountPayable)) 
	    	{
	    		AmountPayable = 0;
	    	}

	    	$('#AmountPayable').val(AmountPayable + OtherFeeAmount);
	    	$('#AmountPaid').val(AmountPayable + OtherFeeAmount);
	    	$('#OtherFeeAmount').prop('disabled', true);

	    	$('#hdnOtherFeeAmount').val(OtherFeeAmount);
    	}
    });

    $('#LateFee').change(function(){
    	if ($(this).is(':checked')) 
    	{
    		// $('#LateFeeDiscription').prop('disabled', false);
    		$('#LateFeeAmount').prop('disabled', false);
    		if ($('#LateFeeAmount').val() != '') 
    		{
    			var AmountPayable = parseInt($('#AmountPayable').val());
	    		var LateFeeAmount = parseInt($('#LateFeeAmount').val());

	    		if (isNaN(AmountPayable)) 
		    	{
		    		AmountPayable = 0;
		    	}

		    	if (isNaN(LateFeeAmount)) 
		    	{
		    		LateFeeAmount = 0;
		    	}

	    		$('#AmountPayable').val(AmountPayable + LateFeeAmount);
	    		$('#AmountPaid').val(AmountPayable + LateFeeAmount);
	    		$('#hdnLateFeeAmount').val(LateFeeAmount);
    		}
    	}
    	else
    	{
    		// $('#LateFeeDiscription').prop('disabled', true);
    		$('#LateFeeAmount').prop('disabled', true);
    		
    		if ($('#LateFeeAmount').val() != '') 
    		{
    			var AmountPayable = parseInt($('#AmountPayable').val());
	    		var LateFeeAmount = parseInt($('#LateFeeAmount').val());

	    		$('#AmountPayable').val(AmountPayable - LateFeeAmount);
	    		$('#AmountPaid').val(AmountPayable - LateFeeAmount);
    		}
    	}
    });

    $('#LateFeeAmount').focusin(function(){
    	if ($(this).val() != '') 
    	{
    		var AmountPayable = parseInt($('#AmountPayable').val());
    		var LateFeeAmount = parseInt($('#LateFeeAmount').val());

	    	if (isNaN(AmountPayable)) 
	    	{
	    		AmountPayable = 0;
	    	}

	    	if (isNaN(LateFeeAmount)) 
	    	{
	    		LateFeeAmount = 0;
	    	}

	    	$('#AmountPayable').val(AmountPayable - LateFeeAmount);
	    	$('#AmountPaid').val(AmountPayable - LateFeeAmount);
    	}
    });

    $('#LateFeeAmount').focusout(function(){
    	if ($(this).val() != '') 
    	{
    		var AmountPayable = parseInt($('#AmountPayable').val());
    		var LateFeeAmount = parseInt($('#LateFeeAmount').val());

	    	if (isNaN(AmountPayable)) 
	    	{
	    		AmountPayable = 0;
	    	}

	    	if (isNaN(LateFeeAmount)) 
	    	{
	    		LateFeeAmount = 0;
	    	}

	    	$('#AmountPayable').val(AmountPayable + LateFeeAmount);
	    	$('#AmountPaid').val(AmountPayable + LateFeeAmount);
    	}

    	$('#hdnLateFeeAmount').val(LateFeeAmount);
    });
});
</script>
</body>
</html>
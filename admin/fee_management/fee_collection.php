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

require_once('../../classes/fee_management/class.fee_discounts.php');
require_once('../../classes/fee_management/class.fee_heads.php');
require_once('../../classes/fee_management/class.fee_collection.php');
require_once('../../classes/fee_management/class.late_fee_rules.php');

require_once('../../classes/class.date_processing.php');
require_once('../../classes/class.helpers.php');

require_once('../../classes/class.global_settings.php');

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

// if ($LoggedUser->GetUserID() != 1000005)
// {
//     echo '<center>';
//     echo '<h2> This page is under maintenance. Please try after 1 hour.</h2>';
//     echo '<h2> Thank You.</h2>';
//     echo '</center>';
// 	exit;
// }

if ($LoggedUser->HasPermissionForTask(TASK_FEE_COLLECTION) !== true)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}

$AcademicYears =  array();
$AcademicYears = AcademicYear::GetAllAcademicYears();

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

$StudentAdvanceFee = 0;

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

$Clean['AcademicYearID'] = 0;
$Clean['FeeDate'] = date('d/m/Y');
$Clean['SearchType'] = 'ClassWise';

$Clean['FirstName'] = '';
$Clean['LastName'] = '';

$Clean['ClassName'] = '';
$Clean['ClassSectionID'] = '';

$Clean['ParentID'] = 0;
$Clean['StudentName'] = '';
$Clean['StudentID'] = 0;
$Clean['ClassID'] = 0;
$Clean['ContactNumber'] = 0;

$Clean['PreviousYearDue'] = array();
$Clean['PreviousYearDueDiscription'] = '';
$Clean['PreviousYearDueAmount'] = array();
$Clean['PreviousYearWaveOffAmount'] = array();

$Clean['OtherFee'] = 0;
$Clean['OtherFeeDiscription'] = '';
$Clean['OtherFeeAmount'] = 0;

$Clean['LateFee'] = 0;
$Clean['LateFeeDiscription'] = '';
$Clean['LateFeeAmount'] = 0;

$Clean['AmountPayable'] = 0;
$Clean['AmountPaidList'] = array();

$Clean['TotalAmountPaid'] = 0;
$Clean['AdjustAdvanceFee'] = 0;
$Clean['AdvanceFeeDetails'] = array();

$Clean['Description'] = '';

$Clean['PaymentModeList'][1] = 1;
$Clean['BankMode'] = 'ByCheck';

$Clean['ChequeReferenceNoList'] = array();
$Clean['DDNumber'] = '';
$Clean['BankName'] = '';
$Clean['IFSC'] = '';
$Clean['MICR'] = '';

$Clean['MonthlyPayableAmount'] = 0;

$Clean['StudentFeeStructureList'] = array();
$Clean['StudentFeeHeadAmountList'] = array();
$Clean['StudentDueFeeHeadAmountList'] = array();
$Clean['ConcessionAmountList'] = array();
$Clean['WaveOffList'] = array();

$Clean['MonthList'] = array();
$Clean['DueMonthList'] = array();

$Clean['FeeCollectionDetails'] = array();
$Clean['PaymentModeDetails'] = array();
$OtherChargesDetails = array();

$TotalAmount = 0;
$TotalDiscount = 0;

$AmountPaid = 0;
$StudentAmountPaid = array();
$StudentAmountPayable = array();

$PreviousYearDue = array();
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
		
		if (isset($_POST['hdnAcademicYearID'])) 
		{
			$Clean['AcademicYearID'] = (int) $_POST['hdnAcademicYearID'];
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
					$StudentsList = StudentDetail::GetStudentsByClassSectionID($Clean['ClassSectionID'], 'Active', $Clean['AcademicYearID']);

					$NewRecordValidator->ValidateInSelect($Clean['StudentID'], $StudentsList, 'Please select a valid student.');
				}
			}
		}
		else if ($Clean['SearchType'] == 'FeeCodeWise')
		{
			if (isset($_POST['hdnContactNumber'])) 
			{
				$Clean['ContactNumber'] = strip_tags(trim($_POST['hdnContactNumber']));
			}

			$NewRecordValidator->ValidateStrings($Clean['ContactNumber'], 'Please enter contact number and it should be between 1 and 15 characters.', 1, 15);
		}
        
        if (isset($_POST['chkStudentFeeStructureID']) && is_array($_POST['chkStudentFeeStructureID']))
		{
			$Clean['StudentFeeStructureList'] = $_POST['chkStudentFeeStructureID'];
		}
		
		if (isset($_POST['txtStudentFeeHeadAmount']) && is_array($_POST['txtStudentFeeHeadAmount']))
		{
			$Clean['StudentFeeHeadAmountList'] = $_POST['txtStudentFeeHeadAmount'];
		}

		if (isset($_POST['txtStudentDueFeeHeadAmount']) && is_array($_POST['txtStudentDueFeeHeadAmount']))
		{
			$Clean['StudentDueFeeHeadAmountList'] = $_POST['txtStudentDueFeeHeadAmount'];
		}
		
		if (isset($_POST['txtConcessionAmount']) && is_array($_POST['txtConcessionAmount']))
		{
			$Clean['ConcessionAmountList'] = $_POST['txtConcessionAmount']; 
		}

		if (isset($_POST['chkWaveOff']) && is_array($_POST['chkWaveOff']))
		{
			$Clean['WaveOffList'] = $_POST['chkWaveOff']; 
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

		if (isset($_POST['txtAmountPaid']) && is_array($_POST['txtAmountPaid'])) 
		{
			$Clean['AmountPaidList'] = $_POST['txtAmountPaid'];
		}

		if (isset($_POST['drdPaymentMode']) && is_array($_POST['drdPaymentMode'])) 
		{
			$Clean['PaymentModeList'] = $_POST['drdPaymentMode'];
		}

		if (isset($_POST['txtChequeReferenceNo']) && is_array($_POST['txtChequeReferenceNo'])) 
		{
			$Clean['ChequeReferenceNoList'] = $_POST['txtChequeReferenceNo'];
		}

		if (isset($_POST['txtDescription'])) 
		{
			$Clean['Description'] = $_POST['txtDescription'];
		}

		if ($Clean['Description'] != '') 
		{
			$NewRecordValidator->ValidateStrings($Clean['Description'], 'Description should be less than 50 characters.', 0, 50);
		}

		$Counter = 0;
		foreach ($Clean['PaymentModeList'] as $Counter => $PaymentModeID) 
		{
			if ($NewRecordValidator->ValidateInSelect($PaymentModeID, $PaymentModeList, 'Invalid payment mode in line '. $Counter .', please try again.')) 
			{
			    if ($PaymentModeID == 2 || $PaymentModeID == 3)
				{
					$NewRecordValidator->ValidateStrings($Clean['ChequeReferenceNoList'][$Counter], 'Cheque/Reference number is required and should be between 3 and 30 characters at payment mode '. $Counter .'.', 3, 30);
				}
			}

			$NewRecordValidator->ValidateInteger($Clean['AmountPaidList'][$Counter], 'Please enter valid paid amount in payment mode '. $Counter .'.', 0);

			$Clean['PaymentModeDetails'][$Counter]['PaymentMode'] = $PaymentModeID;
			$Clean['PaymentModeDetails'][$Counter]['Amount'] = $Clean['AmountPaidList'][$Counter];
			$Clean['PaymentModeDetails'][$Counter]['ChequeReferenceNo'] = '';

			if (array_key_exists($Counter, $Clean['ChequeReferenceNoList'])) 
			{
				$Clean['PaymentModeDetails'][$Counter]['ChequeReferenceNo'] = $Clean['ChequeReferenceNoList'][$Counter];
			}

			$Clean['TotalAmountPaid'] += $Clean['AmountPaidList'][$Counter];
		}
		
		if (isset($_POST['chkAdjustAdvanceFee'])) 
		{
			if (isset($_POST['txtAdjustAdvanceFee'])) 
			{
				$Clean['AdjustAdvanceFee'] = (int) $_POST['txtAdjustAdvanceFee'];
			}

			$NewRecordValidator->ValidateInteger($Clean['AdjustAdvanceFee'], 'Please enter valid advance amount.', 0);

			$Counter++;

			$Clean['PaymentModeDetails'][$Counter]['PaymentMode'] = 6; // 6 for Advance Fee
			$Clean['PaymentModeDetails'][$Counter]['Amount'] = $Clean['AdjustAdvanceFee'];
			$Clean['PaymentModeDetails'][$Counter]['ChequeReferenceNo'] = '';

			$Clean['TotalAmountPaid'] += $Clean['AdjustAdvanceFee'];
		}

        $NewRecordValidator->ValidateDate($Clean['FeeDate'], 'Please enter a valid fee date.');
		$NewRecordValidator->ValidateInteger($Clean['AmountPayable'], 'Unknown error in payable amount.', 0);

		$AmountPaid = $Clean['TotalAmountPaid'];

		if (isset($_POST['hdnPreviousYearDueAmount']) && is_array($_POST['hdnPreviousYearDueAmount'])) 
		{
			$Clean['PreviousYearDueAmount'] = $_POST['hdnPreviousYearDueAmount'];
		}

		if (isset($_POST['txtPreviousYearWaveOffAmount']) && is_array($_POST['txtPreviousYearWaveOffAmount'])) 
		{
			$Clean['PreviousYearWaveOffAmount'] = $_POST['txtPreviousYearWaveOffAmount'];
		}
        
        $TotalPreviousYearDueAmount = 0;
        if (isset($_POST['chkPreviousYearDue']) && is_array($_POST['chkPreviousYearDue'])) 
		{
			$Clean['PreviousYearDue'] = $_POST['chkPreviousYearDue'];

			foreach ($Clean['PreviousYearDue'] as $StudentID => $Value) 
			{
				$Clean['PreviousYearDue'][$StudentID] = $Value;

				if (array_key_exists($StudentID, $Clean['PreviousYearDueAmount'])) 
				{
					$NewRecordValidator->ValidateInteger($Clean['PreviousYearDueAmount'][$StudentID], 'Please enter valid previous year due fee amount.', 0);

					$TotalPreviousYearDueAmount += $Clean['PreviousYearDueAmount'][$StudentID];
				}

				if (array_key_exists($StudentID, $Clean['PreviousYearWaveOffAmount']) && $Clean['PreviousYearWaveOffAmount'][$StudentID] != '') 
				{
					$NewRecordValidator->ValidateInteger($Clean['PreviousYearWaveOffAmount'][$StudentID], 'Please enter valid previous year wave off amount.', 0);
				}
			}
		}

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
		
		if ($Clean['SearchType'] == 'FeeCodeWise') 
		{
			$AllStudents = Student::GetStudentsByContactNumber($Clean['ContactNumber']);

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
				
				$AllStudents[$StudentID]['StudentName'] = $StudentDetailObject->GetFirstName() . ' ' . $StudentDetailObject->GetLastName();

    			$ClassSectionsList = ClassSections::GetClassSections($Clean['ClassID']);
    
    			$AllStudents[$StudentID]['Class'] = $ClassList[$Clean['ClassID']] . ' ' . $ClassSectionsList[$Clean['ClassSectionID']];
    			$AllStudents[$StudentID]['RollNumber'] = $StudentDetailObject->GetRollNumber();
    
    			$NewParentDetailObject = new ParentDetail($StudentDetailObject->GetParentID());
    
    			$AllStudents[$StudentID]['FatherName'] = $NewParentDetailObject->GetFatherFirstName() . ' ' . $NewParentDetailObject->GetFatherLastName();
    			$AllStudents[$StudentID]['Contact'] = ($NewParentDetailObject->GetFatherMobileNumber() ? $NewParentDetailObject->GetFatherMobileNumber() : '--');

				$FeeMonths[$StudentID] = $StudentDetailObject->GetStudentFeeMonths();

				$DueFeeMonths[$StudentID] = $StudentDetailObject->GetStudentDueFeeMonths();
				
				$PreviousYearDue[$StudentID] = $StudentDetailObject->GetStudentPreviousYearDue();

				$Clean['ParentID'] = $StudentDetailObject->GetParentID();

				$StudentAmountPaid[$StudentID] = 0;
				$StudentAmountPayable[$StudentID] = 0;
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
			
			$PreviousYearDue[$Clean['StudentID']] = $StudentDetailObject->GetStudentPreviousYearDue();

			$AllStudents[$Clean['StudentID']]['StudentName'] = $StudentDetailObject->GetFirstName() . ' ' . $StudentDetailObject->GetLastName();

			$ClassSectionsList = ClassSections::GetClassSections($Clean['ClassID']);

			$AllStudents[$Clean['StudentID']]['Class'] = $ClassList[$Clean['ClassID']] . ' ' . $ClassSectionsList[$Clean['ClassSectionID']];
			$AllStudents[$Clean['StudentID']]['RollNumber'] = $StudentDetailObject->GetRollNumber();

			$NewParentDetailObject = new ParentDetail($StudentDetailObject->GetParentID());

			$AllStudents[$Clean['StudentID']]['FatherName'] = $NewParentDetailObject->GetFatherFirstName() . ' ' . $NewParentDetailObject->GetFatherLastName();
			$AllStudents[$Clean['StudentID']]['Contact'] = ($NewParentDetailObject->GetFatherMobileNumber() ? $NewParentDetailObject->GetFatherMobileNumber() : '--');

			$Clean['ParentID'] = $StudentDetailObject->GetParentID();

			$StudentAmountPaid[$Clean['StudentID']] = 0;
			$StudentAmountPayable[$Clean['StudentID']] = 0;

			$StudentID = $Clean['StudentID'];
		}

		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}

		if (count($Clean['PreviousYearDue']) > 0) 
		{
			foreach ($Clean['PreviousYearDue'] as $key => $StudentID) 
			{
				// if ($AmountPaid <= 0) 
				// {
				// 	$NewRecordValidator->AttachTextError('Amount you paid is more less, please pay more for previous year due fee.');
				// 	$HasErrors = true;
				// 	break;
				// }
				
				$PreviousYearDue = $Clean['PreviousYearDueAmount'][$StudentID];
				
				if ($Clean['PreviousYearDueAmount'][$StudentID] > $AmountPaid)
				{
				    $PreviousYearDue = $AmountPaid;
				}
				
				$OtherChargesDetails[$StudentID]['PreviousYearDue']['FeeType'] = 'PreviousYearDue';
				$OtherChargesDetails[$StudentID]['PreviousYearDue']['FeeDescription'] = 'PreviousYearDue';
				$OtherChargesDetails[$StudentID]['PreviousYearDue']['Amount'] = $PreviousYearDue;
				$OtherChargesDetails[$StudentID]['PreviousYearDue']['WaveOffAmount'] = 0;

				if (array_key_exists($StudentID, $Clean['PreviousYearWaveOffAmount']) && $Clean['PreviousYearWaveOffAmount'][$StudentID] != '') 
				{
					$OtherChargesDetails[$StudentID]['PreviousYearDue']['WaveOffAmount'] = $Clean['PreviousYearWaveOffAmount'][$StudentID];
				}
				
				$AmountPaid = $AmountPaid - $PreviousYearDue;
				
				if ($AmountPaid <= 0) 
				{
					$AmountPaid = 0;
				}
				
				$StudentAmountPaid[$StudentID] = $PreviousYearDue;
				$StudentAmountPayable[$StudentID] = $PreviousYearDue;
			}
		}

		if ($Clean['OtherFee'] == 1) 
		{
		    if ($AmountPaid <= 0) 
			{
				$NewRecordValidator->AttachTextError('Amount you paid is more less to pay other fee, please pay more or manage details.');
				$HasErrors = true;
				break;
			}
			
			$OtherFeeAmount = $Clean['OtherFeeAmount'];
			
			if ($Clean['OtherFeeAmount'] > $AmountPaid)
			{
			    $OtherFeeAmount = $AmountPaid;
			}
			
			$OtherChargesDetails[$StudentID]['OtherFee']['FeeType'] = 'OtherFee';
			$OtherChargesDetails[$StudentID]['OtherFee']['FeeDescription'] = $Clean['OtherFeeDiscription'];
			$OtherChargesDetails[$StudentID]['OtherFee']['Amount'] = $OtherFeeAmount;
			
			$AmountPaid = $AmountPaid - $OtherFeeAmount;
			
			if ($AmountPaid <= 0) 
			{
				$AmountPaid = 0;
			}
			
			$StudentAmountPaid[$StudentID] = $OtherFeeAmount;
			$StudentAmountPayable[$StudentID] = $OtherFeeAmount;
		}

		if ($Clean['LateFee'] == 1) 
		{
		    if ($AmountPaid <= 0) 
			{
				$NewRecordValidator->AttachTextError('Amount you paid is more less to pay late fee, please pay more or manage details.');
				$HasErrors = true;
				break;
			}
			
			$LateFee = $Clean['LateFeeAmount'];
			
			if ($Clean['LateFeeAmount'] > $AmountPaid)
			{
			    $LateFee = $AmountPaid;
			}
			
			$OtherChargesDetails[$StudentID]['LateFee']['FeeType'] = 'LateFee';
			$OtherChargesDetails[$StudentID]['LateFee']['FeeDescription'] = $Clean['LateFeeDiscription'];
			$OtherChargesDetails[$StudentID]['LateFee']['Amount'] = $LateFee;
			
			$AmountPaid = $AmountPaid - $LateFee;
			
			if ($AmountPaid <= 0) 
			{
				$AmountPaid = 0;
			}
			
			$StudentAmountPaid[$StudentID] = $LateFee;
			$StudentAmountPayable[$StudentID] = $LateFee;
		}

		$TotalAmount = 0;
		$TotalDiscount = 0;

// 		$Clean['AmountPayable'] += $Clean['OtherFeeAmount'] + $TotalPreviousYearDueAmount + $Clean['LateFeeAmount'];

		$TotalAmount += $Clean['OtherFeeAmount'] + $TotalPreviousYearDueAmount + $Clean['LateFeeAmount'];
		
		$Clean['FeeCollectionDetails'][$StudentID]['TotalDiscount'] = $TotalDiscount;
		$Clean['FeeCollectionDetails'][$StudentID]['StudentAmountPaid'] = $StudentAmountPaid[$StudentID];
		$Clean['FeeCollectionDetails'][$StudentID]['StudentAmountPayable'] = $StudentAmountPayable[$StudentID];
		$Clean['FeeCollectionDetails'][$StudentID]['StudentFeeCollectionDetails'] = array();

		if (isset($_POST['chkDueMonth']) && is_array($_POST['chkDueMonth']))
		{
			$Clean['DueMonthList'] = $_POST['chkDueMonth'];

			foreach ($Clean['DueMonthList'] as $StudentID => $SelectedMonths) 
			{
				$StudentDetailObject = new StudentDetail($StudentID);    

				foreach ($SelectedMonths as $MonthID => $AcademicYearID)
				{
					$StudentMonthlyFeeDetails = array();

					$TotalMonthlyFeeAmount = 0;
					$TotalMonthlyFeeAmount = $StudentDetailObject->GetStudentMonthlyFeeDetails($MonthID, $StudentMonthlyFeeDetails, $AcademicYearID);

					$FeeHeadCounter = 0;

					foreach ($StudentMonthlyFeeDetails as $StudentFeeStructureID => $MonthlyFeeDetail)
					{
						if (in_array($StudentFeeStructureID, $Clean['StudentFeeStructureList'])) 
						{
							if (!array_key_exists($StudentFeeStructureID, $Clean['WaveOffList'])) 
							{
								if ($AmountPaid <= 0 && $FeeHeadCounter < 1) 
								{
									$NewRecordValidator->AttachTextError('Amount you paid is more less, please pay more or uncheck a month.');
									$HasErrors = true;
									break;
								}

								$TotalAmount += $MonthlyFeeDetail['FeeAmount'] - $MonthlyFeeDetail['AmountPaid'];
								$TotalDiscount += $MonthlyFeeDetail['DiscountAmount'];

								$ConcessionAmount = 0;
								if ($Clean['ConcessionAmountList'][$StudentFeeStructureID]) 
								{
									$ConcessionAmount = $Clean['ConcessionAmountList'][$StudentFeeStructureID];
									
									if ($ConcessionAmount > ($MonthlyFeeDetail['FeeAmount'] - $MonthlyFeeDetail['DiscountAmount'])) 
									{
										$NewRecordValidator->AttachTextError('Concession amount should not be greater than payable amount.');
									}
								}
	                            
								$TotalDiscount += $ConcessionAmount;
								// $FeeHeadPayableAmount = ($MonthlyFeeDetail['FeeAmount'] - $MonthlyFeeDetail['DiscountAmount']);
								$FeeHeadPayableAmount = $Clean['StudentDueFeeHeadAmountList'][$StudentFeeStructureID] - $ConcessionAmount;
								$StudentAmountPayable[$StudentID] += $FeeHeadPayableAmount;

								if ($AmountPaid <= 0) 
								{
									$AmountPaid = 0;
								}

								if ($FeeHeadPayableAmount < $AmountPaid) 
								{
									$Clean['FeeCollectionDetails'][$StudentID]['StudentFeeCollectionDetails'][$StudentFeeStructureID] = $FeeHeadPayableAmount;
									$StudentAmountPaid[$StudentID] += $FeeHeadPayableAmount;
								}
								else
								{
									$Clean['FeeCollectionDetails'][$StudentID]['StudentFeeCollectionDetails'][$StudentFeeStructureID] = $AmountPaid;
									$StudentAmountPaid[$StudentID] += $AmountPaid;
								}
								
								$AmountPaid = $AmountPaid - $FeeHeadPayableAmount;

								$FeeHeadCounter++;
							}
							else
							{
								$TotalAmount += 0;
								$TotalDiscount += 0;

								$FeeHeadPayableAmount = 0;
								$StudentAmountPayable[$StudentID] += $FeeHeadPayableAmount;

								$Clean['FeeCollectionDetails'][$StudentID]['StudentFeeCollectionDetails'][$StudentFeeStructureID] = $FeeHeadPayableAmount;
								$StudentAmountPaid[$StudentID] += $FeeHeadPayableAmount;
							}
						}
					}
				}

				// $Clean['FeeCollectionDetails'][$StudentID]['TotalAmount'] = $TotalAmount;
				$Clean['FeeCollectionDetails'][$StudentID]['TotalDiscount'] = $TotalDiscount;
				$Clean['FeeCollectionDetails'][$StudentID]['StudentAmountPaid'] = $StudentAmountPaid[$StudentID];
				$Clean['FeeCollectionDetails'][$StudentID]['StudentAmountPayable'] = $StudentAmountPayable[$StudentID];
			}
		}  

        if (isset($_POST['chkMonth']) && is_array($_POST['chkMonth']))
		{
			$Clean['MonthList'] = $_POST['chkMonth'];

			foreach ($Clean['MonthList'] as $StudentID => $SelectedMonths) 
			{
				$StudentDetailObject = new StudentDetail($StudentID);    

				foreach ($SelectedMonths as $MonthID => $Details)
				{
					$StudentMonthlyFeeDetails = array();

					$TotalMonthlyFeeAmount = 0;
					$TotalMonthlyFeeAmount = $StudentDetailObject->GetStudentMonthlyFeeDetails($MonthID, $StudentMonthlyFeeDetails);

					$LastYearSubmitedFee = 0;
					$FeeHeadCounter = 0;

					foreach ($StudentMonthlyFeeDetails as $StudentFeeStructureID => $MonthlyFeeDetail)
					{
						if (in_array($StudentFeeStructureID, $Clean['StudentFeeStructureList'])) 
						{
							if (!array_key_exists($StudentFeeStructureID, $Clean['WaveOffList'])) 
							{
								if ($AmountPaid <= 0 && $FeeHeadCounter < 1) 
								{
									$NewRecordValidator->AttachTextError('Amount you paid is more less, please pay more or uncheck a month.');
									$HasErrors = true;
									break;
								}

								$TotalAmount += $MonthlyFeeDetail['FeeAmount'] - $MonthlyFeeDetail['AmountPaid'];
								$TotalDiscount += $MonthlyFeeDetail['DiscountAmount'];

								$ConcessionAmount = 0;

								if ($Clean['ConcessionAmountList'][$StudentFeeStructureID]) 
								{
									$ConcessionAmount = $Clean['ConcessionAmountList'][$StudentFeeStructureID];
									
									if ($ConcessionAmount > ($MonthlyFeeDetail['FeeAmount'] - $MonthlyFeeDetail['DiscountAmount'])) 
									{
										$NewRecordValidator->AttachTextError('Concession amount should not be greater than payable amount.');
									}
								}

								$TotalDiscount += $ConcessionAmount;
								// $FeeHeadPayableAmount = ($MonthlyFeeDetail['FeeAmount'] - $MonthlyFeeDetail['DiscountAmount']);
								$FeeHeadPayableAmount = $Clean['StudentFeeHeadAmountList'][$StudentFeeStructureID] - $ConcessionAmount;
								$StudentAmountPayable[$StudentID] += $FeeHeadPayableAmount;

								if ($FeeHeadPayableAmount < $AmountPaid) 
								{
									$Clean['FeeCollectionDetails'][$StudentID]['StudentFeeCollectionDetails'][$StudentFeeStructureID] = $FeeHeadPayableAmount;
									$StudentAmountPaid[$StudentID] += $FeeHeadPayableAmount;
								}
								else
								{
									$Clean['FeeCollectionDetails'][$StudentID]['StudentFeeCollectionDetails'][$StudentFeeStructureID] = $AmountPaid;
									$StudentAmountPaid[$StudentID] += $AmountPaid;
								}
								
								$AmountPaid = $AmountPaid - $FeeHeadPayableAmount;

								if ($AmountPaid <= 0) 
								{
									$AmountPaid = 0;
								}

								$FeeHeadCounter++;
							}
							else
							{
								$TotalAmount += 0;
								$TotalDiscount += 0;

								$FeeHeadPayableAmount = 0;
								$StudentAmountPayable[$StudentID] += $FeeHeadPayableAmount;

								$Clean['FeeCollectionDetails'][$StudentID]['StudentFeeCollectionDetails'][$StudentFeeStructureID] = $FeeHeadPayableAmount;
								$StudentAmountPaid[$StudentID] += $FeeHeadPayableAmount;
							}
						}
					}
				}

				// $Clean['FeeCollectionDetails'][$StudentID]['TotalAmount'] = $TotalAmount;
				// $Clean['FeeCollectionDetails'][$StudentID]['TransactionAmount'] = $TotalAmount; 
				$Clean['FeeCollectionDetails'][$StudentID]['TotalDiscount'] = $TotalDiscount;
				$Clean['FeeCollectionDetails'][$StudentID]['StudentAmountPaid'] = $StudentAmountPaid[$StudentID]; // In table afm_fee_collection  amountPaid column
				$Clean['FeeCollectionDetails'][$StudentID]['StudentAmountPayable'] = $StudentAmountPayable[$StudentID]; // In table afm_fee_collection  totalAmount column
			}
		}    
		
		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}

		if ($Clean['OtherFee'] <= 0 && $Clean['PreviousYearDue'] <= 0 && $Clean['LateFee'] <= 0 && count($Clean['MonthList']) <= 0 && count($Clean['DueMonthList']) <= 0)
		{
			$NewRecordValidator->AttachTextError('Please select atleast one month or any other head.');
			$HasErrors = true;
			break;
		}

		if ($Clean['TotalAmountPaid'] > $Clean['AmountPayable']) 
		{
			$Clean['AdvanceFeeDetails']['ParentID'] = $Clean['ParentID'];
			$Clean['AdvanceFeeDetails']['AdvanceFee'] = $Clean['TotalAmountPaid'] - $Clean['AmountPayable'];
		}

		$NewFeeCollection = new FeeCollection();
	
		$NewFeeCollection->SetStudentID($Clean['StudentID']);
		$NewFeeCollection->SetFeeDate(date('Y-m-d', strtotime(DateProcessing::ToggleDateDayAndMonth(($Clean['FeeDate'])))));

		$NewFeeCollection->SetTransactionAmount($Clean['TotalAmountPaid']); // In afm_fee_transactions transactionAmount

        $NewFeeCollection->SetTotalAmount($Clean['AmountPayable']);
		$NewFeeCollection->SetTotalDiscount($TotalDiscount);
		$NewFeeCollection->SetAmountPaid($Clean['TotalAmountPaid']);
		$NewFeeCollection->SetPaymentModeDetails($Clean['PaymentModeDetails']);
		// $NewFeeCollection->SetChequeReferenceNo($Clean['ChequeReferenceNo']);

		$NewFeeCollection->SetCreateUserID($LoggedUser->GetUserID());

		$NewFeeCollection->SetFeeCollectionDetails($Clean['FeeCollectionDetails']);		
		$NewFeeCollection->SetOtherChargesDetails($OtherChargesDetails);		
		$NewFeeCollection->SetAdvanceFeeDetails($Clean['AdvanceFeeDetails']);		
		$NewFeeCollection->SetParentID($Clean['ParentID']);		
		$NewFeeCollection->SetDescription($Clean['Description']);		

		if (!$NewFeeCollection->Save())
		{
			$NewRecordValidator->AttachTextError(ProcessErrors($NewFeeCollection->GetLastErrorCode()));
			$HasErrors = true;
			break;
		}

		if (count($Clean['WaveOffList']) > 0)
		{
			if (!FeeDiscount::SetWaveOffFee($Clean['StudentID'], $Clean['WaveOffList']))
			{
				$NewRecordValidator->AttachTextError('Error in executing query.');
				$HasErrors = true;
				break;
			}	
		}

		if (!FeeDiscount::SetFeeConcession($Clean['StudentID'], $Clean['ConcessionAmountList']))
		{
			$NewRecordValidator->AttachTextError('Error in executing query.');
			$HasErrors = true;
			break;
		}
		
		// $SelectedFeeCollectionIDs = implode('$', $NewFeeCollection->GetCurrentTransactionID());
		$SelectedFeeCollectionID = $NewFeeCollection->GetCurrentTransactionID();
		
		if ($SelectedFeeCollectionID > 0 && $Clean['AmountPayable'] > 0) 
		{
			header('location:fee_receipt.php?FeeTransactionID='. $SelectedFeeCollectionID);
			exit;
		}
		else
		{
			header('location:fee_collection.php?Mode=AS');
			exit;
		}
		
		
	break;

	case 7:
		$NewRecordValidator = new Validator();
        
        if (isset($_POST['drdAcademicYear'])) 
		{
			$Clean['AcademicYearID'] = (int) $_POST['drdAcademicYear'];
		}
		else if (isset($_GET['AcademicYearID'])) 
		{
			$Clean['AcademicYearID'] = (int) $_GET['AcademicYearID'];
		}
		
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
					$StudentsList = StudentDetail::GetStudentsByClassSectionID($Clean['ClassSectionID'], 'Active', $Clean['AcademicYearID']);

					$NewRecordValidator->ValidateInSelect($Clean['StudentID'], $StudentsList, 'Please select a valid student.');
				}
			}
		}
		else if ($Clean['SearchType'] == 'FeeCodeWise') 
		{
			if (isset($_POST['txtContactNumber'])) 
			{
				$Clean['ContactNumber'] = strip_tags(trim($_POST['txtContactNumber']));
			}

			$NewRecordValidator->ValidateStrings($Clean['ContactNumber'], 'Please enter contact number and it should be between 1 and 15 characters.', 1, 15);
		}

		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}

		if ($Clean['SearchType'] == 'FeeCodeWise')
		{
			$AllStudents = Student::GetStudentsByContactNumber($Clean['ContactNumber']);

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
				
				$PreviousYearDue[$StudentID] = $StudentDetailObject->GetStudentPreviousYearDue();

				$StudentAdvanceFee = $StudentDetailObject->GetStudentAdvanceFee();

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
			    
			    $ClassSectionObj = new ClassSections($StudentDetailObject->GetClassSectionID());    
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
			
			$PreviousYearDue = array();
			$PreviousYearDue[$Clean['StudentID']] = $StudentDetailObject->GetStudentPreviousYearDue();

			$StudentAdvanceFee = $StudentDetailObject->GetStudentAdvanceFee();

			$AllStudents[$Clean['StudentID']]['StudentName'] = $StudentDetailObject->GetFirstName() . ' ' . $StudentDetailObject->GetLastName();
            
            $Clean['ClassID'] = $ClassSectionObj->GetClassID();
            $Clean['ClassSectionID'] = $StudentDetailObject->GetClassSectionID();
            
			$ClassSectionsList = ClassSections::GetClassSections($Clean['ClassID']);

			$AllStudents[$Clean['StudentID']]['Class'] = $ClassList[$Clean['ClassID']] . ' ' . $ClassSectionsList[$Clean['ClassSectionID']];
			$AllStudents[$Clean['StudentID']]['RollNumber'] = $StudentDetailObject->GetRollNumber();

			$NewParentDetailObject = new ParentDetail($StudentDetailObject->GetParentID());

			$AllStudents[$Clean['StudentID']]['FatherName'] = $NewParentDetailObject->GetFatherFirstName() . ' ' . $NewParentDetailObject->GetFatherLastName();
			$AllStudents[$Clean['StudentID']]['Contact'] = ($NewParentDetailObject->GetFatherMobileNumber() ? $NewParentDetailObject->GetFatherMobileNumber() : '--');

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
                            	<!--<input class="form-control" type="text" maxlength="10" id="AcademicYear" name="txtAcademicYear" readonly="readonly" value="<?php echo $AcademicYearName; ?>" />-->
                            	<select class="form-control" name="drdAcademicYear" id="AcademicYearID">
<?php
                                if (is_array($AcademicYears) && count($AcademicYears) > 0)
                                {
                                    foreach ($AcademicYears as $AcademicYearID => $AcademicYearDetails)
                                    {
                                        if ($Clean['AcademicYearID'] == 0)
                                        {
                                            if ($AcademicYearDetails['IsCurrentYear'] == 1)
                                            {
                                                $Clean['AcademicYearID'] = $AcademicYearID;   
                                            }
                                        }
                                        
                                        echo '<option ' . ($Clean['AcademicYearID'] == $AcademicYearID ? 'selected="selected"' : '') . ' value="' . $AcademicYearID . '">' . date('Y', strtotime($AcademicYearDetails['StartDate'])) .' - '. date('Y', strtotime($AcademicYearDetails['EndDate'])) . '</option>';
                                    }
                                }
?>
								</select>
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
                                <label class="col-sm-4"><input class="custom-radio" type="radio" name="optSearchType" value="FeeCodeWise" <?php echo ($Clean['SearchType'] == 'FeeCodeWise' ? 'checked="checked"' : ''); ?> >&nbsp;&nbsp;By Parent</label>
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
	                            <label for="ContactNumber" class="col-lg-2 control-label">Mobile No</label>
	                            <div class="col-lg-8">
                                    <input type="text" class="form-control" name="txtContactNumber" id="ContactNumber" value="<?php echo $Clean['ContactNumber'] ? $Clean['ContactNumber'] : '' ;?>">
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
                                            <th colspan="3" class="text-center">Details</th>
                                        </tr>
                                    </thead>
                                    <tbody id="TableBody">
                                    	<tr>
                                    		<td>
<?php
										foreach ($AllStudents as $StudentID => $Details) 
										{
											echo 'Monthly Fee (<small>'. $Details['StudentName'] .'</small>)<br>';
										}
?>
                                    		</td>
                                    		<td colspan="3">
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
                                    		<td>
<?php
										if (count($AllStudents) > 1) 
										{
											foreach ($AllStudents as $StudentID => $Details) 
											{
												echo 'Fee Due Details (<small>'. $Details['StudentName'] .'</small>)<br>';
											}
										}
										else
										{
											echo 'Fee Due Details';
										}
?>
                                    		</td>
                                    		<td colspan="3">
<?php 
									foreach ($AllStudents as $StudentID => $Details) 
									{	
									    $Count = 0;
										foreach ($DueFeeMonths[$StudentID] as $AcademicYearID => $DueFeeMonthDetail) 
										{
											foreach ($DueFeeMonthDetail as $MonthID => $DueMonthDetails) 
											{
												$Year = '';
												
												if ($MonthID == 101 || $MonthID == 102 || $MonthID == 103) 
												{
													$Year = '['. date('y', strtotime($AcademicYears[$AcademicYearID]['EndDate'])) .']';
												}
												else
												{
													$Year = '['. date('y', strtotime($AcademicYears[$AcademicYearID]['StartDate'])) .']';
												}

												echo '<label class="checkbox-inline"><input class="custom-radio DueMonthID" type="checkbox" id="Due' . $MonthID . '" AcademicYearID="' . $AcademicYearID . '" StudentID="' . $StudentID . '" name="chkDueMonth['. $StudentID .']['. $MonthID .']" '.(array_key_exists($StudentID, $Clean['DueMonthList']) ? (array_key_exists($MonthID, $Clean['DueMonthList'][$StudentID]) ? 'checked="checked"' : '') : '' ).'  value="' . $AcademicYearID . '" />
                                                ' . $DueMonthDetails['MonthShortName'] . $Year . ' ( <small class="text-danger" id="' . $MonthID . 'Due"><i class="fa fa-inr"></i>&nbsp;' .$DueMonthDetails['DueAmount']. ' </small>)</label>';
											}

											$Count++;
										}
										
										echo '<br>';
									}
?>
											</td>
                                    	</tr>
<?php
								foreach ($AllStudents as $StudentID => $Details) 
								{
									if ($PreviousYearDue[$StudentID] > 0) 
									{
										$Clean['PreviousYearDueAmount'][$StudentID] = $PreviousYearDue[$StudentID];
?>
										<tr>
                                    		<td><label class="checkbox-inline"><input class="custom-radio PreviousYearDue" type="checkbox" id="PreviousYearDue<?php echo $StudentID; ?>" name="chkPreviousYearDue[<?php echo $StudentID; ?>]" <?php echo (array_key_exists($StudentID, $Clean['PreviousYearDue']) ? ($Clean['PreviousYearDue'][$StudentID] ? 'checked="checked"' : '') : '' ) ;?> value="<?php echo $StudentID; ?>" />Previous Due</label>
                                            </td>
                                    		<td><input type="text" class="form-control PreviousYearDueDiscription" name="txtPreviousYearDueDiscription" id="PreviousYearDueDiscription<?php echo $StudentID; ?>" placeholder="Previous Year Due" <?php echo $Clean['PreviousYearDue'] ? '' : 'disabled="disabled"' ;?> value="<?php echo $Clean['PreviousYearDueDiscription'] ;?>"></td>
                                    		<td><input type="text" class="form-control PreviousYearDueAmount" name="txtPreviousYearDueAmount" id="PreviousYearDueAmount<?php echo $StudentID; ?>" disabled="disabled" value="<?php echo (array_key_exists($StudentID, $Clean['PreviousYearDueAmount']) ? ($Clean['PreviousYearDueAmount'][$StudentID] ? $Clean['PreviousYearDueAmount'][$StudentID] : '' ) : '') ;?>">
                                    			<span class="text-danger" id="RestDue<?php echo $StudentID; ?>"></span>
                                    			<input type="hidden" name="hdnPreviousYearDueAmount[<?php echo $StudentID; ?>]" id="hdnPreviousYearDueAmount<?php echo $StudentID; ?>" value="<?php echo (array_key_exists($StudentID, $Clean['PreviousYearDueAmount']) ? ($Clean['PreviousYearDueAmount'][$StudentID] ? $Clean['PreviousYearDueAmount'][$StudentID] : '' ) : '') ;?>"></td>
                                    		<td><span >Wave Off</span><input type="text" class="form-control PreviousYearWaveOffAmount" style="float: right; width: 75%;" name="txtPreviousYearWaveOffAmount[<?php echo $StudentID; ?>]" id="PreviousYearWaveOffAmount<?php echo $StudentID; ?>" disabled="disabled" value=""></td>
                                    	</tr>
<?php
									}
								}
?>
                                    	<tr>
                                    		<td><label class="checkbox-inline"><input class="custom-radio" type="checkbox" id="OtherFee" name="chkOtherFee" <?php echo $Clean['OtherFee'] ? 'checked="checked"' : '' ;?> />Other Fee</label>
                                            </td>
                                    		<td><input type="text" class="form-control" name="txtOtherFeeDiscription" id="OtherFeeDiscription" placeholder="Enter Discription" <?php echo $Clean['OtherFee'] ? '' : 'disabled="disabled"' ;?> value="<?php echo $Clean['OtherFeeDiscription'] ;?>"></td>
                                    		<td colspan="2"><input type="text" class="form-control" name="txtOtherFeeAmount" id="OtherFeeAmount" placeholder="Enter Other Fee Amount" disabled="disabled" value="<?php echo ($Clean['OtherFeeAmount']) ? $Clean['OtherFeeAmount'] : '' ;?>">
                                    			<input type="hidden" name="hdnOtherFeeAmount" id="hdnOtherFeeAmount" value="<?php echo $Clean['OtherFeeAmount'] ;?>"></td>
                                    	</tr>
                                    	<tr>
                                    		<td><label class="checkbox-inline"><input class="custom-radio" type="checkbox" id="LateFee" name="chkLateFee"  <?php echo ($Clean['LateFee'] ? 'checked="checked"' : '') ;?> />Late Fee</label>
                                    		</td>
                                    		<!-- <td><input type="text" class="form-control" name="txtLateFeeDiscription" id="LateFeeDiscription" placeholder="Enter Discription" <?php echo $Clean['LateFee'] ? '' : 'disabled="disabled"' ;?> value="<?php echo $Clean['LateFeeDiscription'] ;?>">
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
                                    		<td colspan="2"><input type="text" class="form-control" name="txtLateFeeAmount" id="LateFeeAmount" placeholder="Enter Late Fee Amount" disabled="disabled" value="<?php echo $Clean['LateFeeAmount'] ? $Clean['LateFeeAmount'] : '' ;?>">
                                    		<input type="hidden" name="hdnLateFeeAmount" id="hdnLateFeeAmount" value="<?php echo $Clean['LateFeeAmount'] ;?>"></td>
                                    	</tr>
                                    	<tr>
                                    		<td>Amount Payable</td>
                                    		<td><input type="text" class="form-control" name="txtAmountPayable" id="AmountPayable" readonly="readonly" value="<?php echo $Clean['AmountPayable'] ? $Clean['AmountPayable'] : '' ;?>">
                                    		</td>
                                    		<td><label class="checkbox-inline"><input class="custom-radio" type="checkbox" id="AdjustAdvanceFee" name="chkAdjustAdvanceFee"  <?php echo ($Clean['AdjustAdvanceFee'] ? 'checked="checked"' : '') ;?> />Advance <?php echo $StudentAdvanceFee; ?></label>
                                    			<input type="text" class="form-control" name="txtAdjustAdvanceFee" id="AdvanceAmount" style="<?php echo ($Clean['AdjustAdvanceFee']) ? '' : 'display:none;'; ?>"  value="<?php echo $Clean['AdjustAdvanceFee'] ? $Clean['AdjustAdvanceFee'] : '' ;?>">
                                    		</td>                                   		
                                    		<td class="text-danger" id="RestAmount"></td>                                   		
                                    	</tr>

<?php
                                    	$PaymentRowCounter = 1;
                                    	$PaymentRows = 1;

                                    	for ($PaymentRowCounter = 1; $PaymentRowCounter <= $PaymentRows; $PaymentRowCounter++) 
                                    	{ 
?>
											<tr class="PaymentRow" id="PaymentRow<?php echo $PaymentRowCounter; ?>">
	                                    		<td>Amount Paid</td>
	                                    		<td><input type="text" class="form-control" name="txtAmountPaid[<?php echo $PaymentRowCounter; ?>]" id="AmountPaid"  value="<?php echo (array_key_exists($PaymentRowCounter, $Clean['AmountPaidList'])) ? $Clean['AmountPaidList'][$PaymentRowCounter] : '' ;?>">
	                                    		</td>
	                                    		<td id="ChequeDetails<?php echo $PaymentRowCounter; ?>">
	                                    			<select class="form-control PaymentMode" name="drdPaymentMode[<?php echo $PaymentRowCounter; ?>]" id="PaymentMode<?php echo $PaymentRowCounter; ?>">
<?php
			                                            if (is_array($PaymentModeList) && count($PaymentModeList) > 0)
			                                            {
			                                                foreach ($PaymentModeList as $PaymentModeID => $PaymentModeName) 
			                                                {
			                                                    echo '<option ' . ($Clean['PaymentModeList'][$PaymentRowCounter] == $PaymentModeID ? 'selected="selected"' : '') . ' value="' . $PaymentModeID . '">' . $PaymentModeName . '</option>' ;
			                                                }
			                                            }
?>
				                                    </select>
	                                    		</td>
<?php 
												if ($PaymentRowCounter == 1) 
												{
													echo '<td><button type="button" class="btn btn-sm btn-success pull-right" id="AddPaymentRow"><i class="fa fa-plus"></i>&nbsp;Add More</button></td>';
												}
												else
												{
													echo '<td><button type="button" class="btn btn-sm btn-danger pull-right RemovePaymentRow" id="'. $PaymentRowCounter .'"><i class="fa fa-remove"></i>&nbsp;Remove</button></td>';
												}
?>
	                                    	</tr>
<?php
                                    	}
?>

                                    	
                                    </tbody>
                                    <tr>
                                		<td>Description<small> ( if required)</small></td>
                                		<td><input type="text" class="form-control" name="txtDescription" id="Description"  value="<?php echo $Clean['Description'] ;?>"></td>
                                		<td></td>
                                		<td></td>
                                	</tr>
                                </table>
                            </div>
                            <div class="form-group">
								<div class="col-sm-offset-5 col-lg-12">
								    <input type="hidden" name="hdnAcademicYearID" value="<?php echo $Clean['AcademicYearID']; ?>" />
									<input type="hidden" name="hdnClassID" value="<?php echo $Clean['ClassID']; ?>" />
									<input type="hidden" name="hdnClassSectionID" value="<?php echo $Clean['ClassSectionID'] ;?>" />
									<input type="hidden" name="hdnStudentID" value="<?php echo $Clean['StudentID'] ;?>" />
									<input type="hidden" name="hdnStudentName" value="<?php echo $Clean['StudentName'] ;?>" />
									<input type="hidden" name="hdnContactNumber" value="<?php echo $Clean['ContactNumber'] ;?>" />
									<input type="hidden" name="hdnSearchType" value="<?php echo $Clean['SearchType'] ;?>" />
									<input type="hidden" name="hdnFeeDate" value="<?php echo $Clean['FeeDate'] ;?>" />
									<input type="hidden" name="hdnProcess" value="1" />
									<button type="submit" class="btn btn-primary"><i class="fa fa-inr"></i>&nbsp;Pay</button>
								</div>
							</div>
                        </div>

                        <div id="ViewFeeDetails" class="modal fade" role="dialog">
						  <div class="modal-dialog modal-lg">

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
						      	<div class="row">
			                        <div class="col-lg-9 text-danger"><b>Grand Total : <span id="TotalFee"></span></b></div>
			                        <div class="col-lg-2">
			                        	<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
			                        </div>
			                        
			                    </div>
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

    $('body').on('change', '.PaymentMode', function() {	
        
        RowNumber = $(this).attr('id').slice(11) ;

        if ($(this).val() == 2 || $(this).val() == 3) 
        {
        	var Data = '<input class="form-control ChequeDetails" type="text" maxlength="30" id="ChequeReferenceNo'+ RowNumber +'" name="txtChequeReferenceNo['+ RowNumber +']" value="" placeholder="Enter cheque/ reference number"/>';

        	if (!$('#ChequeDetails' + RowNumber).children('.ChequeDetails').length) 
        	{
        		$('#ChequeDetails' + RowNumber).append(Data);
        	}
        }
        else
        {
        	$('#ChequeDetails' + RowNumber).find('.ChequeDetails').remove();
        }
    });

    $("#AddPaymentRow").click(function() {
        
        CountPaymentRow = $('.PaymentRow').length + 1;

        var Data = '<tr Class="PaymentRow" id="PaymentRow'+ CountPaymentRow +'">';
        
    		Data += '<td>Amount Paid</td>'
    		Data += '<td><input type="text" class="form-control" name="txtAmountPaid['+ CountPaymentRow +']" id="AmountPaid'+ CountPaymentRow +'"  value="">'
    		Data += '</td>'
    		Data += '<td id="ChequeDetails'+ CountPaymentRow +'">'
    		Data +=	'<select class="form-control PaymentMode" name="drdPaymentMode['+ CountPaymentRow +']" id="PaymentMode'+ CountPaymentRow +'">'
<?php
        if (is_array($PaymentModeList) && count($PaymentModeList) > 0)
        {
            foreach ($PaymentModeList as $PaymentModeID => $PaymentModeName) 
            {
?>
			Data += '<option value="<?php echo $PaymentModeID; ?>"><?php echo $PaymentModeName; ?></option>'
<?php
            }
        }
?>
            Data += '</select>'
    		Data += '</td>'
    		Data += '<td><button type="button" class="btn btn-sm btn-danger pull-right RemovePaymentRow" id="'+ CountPaymentRow +'"><i class="fa fa-remove"></i>&nbsp;Remove</button></td>'
    		Data += '</tr>';

    		$('#TableBody').append(Data);
    });

    $('body').on('click', '.RemovePaymentRow', function() {	

        RowNumber = $(this).attr('id') ;

        $('#PaymentRow' + RowNumber).remove();
    });

    $("input[name='optBankMode']").change(function() {
        
        if ($(this).val() == 'ByDemandDraft') 
        {
        	$("#DemandDraftDetails").slideDown();
        	$("#ChequeDetails").slideUp();
        }
        else
        {
        	$("#DemandDraftDetails").slideUp();
        	$("#ChequeDetails").slideDown();
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
        var AcademicYearID = parseInt($('#AcademicYearID').val());
        
        if (ClassSectionID <= 0)
        {
            $('#Student').html('<option value="0">Select Student</option>');
            return;
        }
        
        $.post("/xhttp_calls/get_students_by_class_section.php", {SelectedClassSectionID:ClassSectionID,SelectedAcademicYearID:AcademicYearID}, function(data)
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
    
    $('#AcademicYearID').change(function(){

        $('#Class').val(0);
        $('#ClassSection').html('<option value="0">Select Section</option>');
        $('#Student').html('<option value="0">Select Student</option>');
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
		            	$('#FeeDetails').append(ResultArray[2]); //Fee Breakup By month
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
        var TotalFee = parseInt($('#TotalFee').text());
       
       	var Check = $(this);
// alert(Check.val());
    	if (isNaN(AmountPayable)) 
    	{
    		AmountPayable = 0;
    	}

    	if (isNaN(TotalFee)) 
    	{
    		TotalFee = 0;
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

            // 		var ModalHtml = $('#FeeDetails').html();
                	$('#FeeDetails').append(ResultArray[2]); //Fee Breakup By month

                	$('#TotalFee').text(TotalFee + TotalMonthlyFeeAmount);
            	}
            	else
            	{
            		// TotalPayableAmount = AmountPayable - TotalMonthlyFeeAmount;

            		var MonthTotalFee = 0;
            		
            		$('.CheckedFeeHead' + MonthID).each(function(){
            		    
            		    StudentFeeStructureID = $(this).attr('id').slice(10);

            		    if ($('#' + StudentFeeStructureID).prop('checked') == true)
            		    {
            		        ConcessionAmount = parseInt($('#ConcessionAmount' + StudentFeeStructureID).val());

            		    	if (isNaN(ConcessionAmount)) 
            		    	{
            		    		ConcessionAmount = 0;
            		    	}

            		        MonthTotalFee = (MonthTotalFee + parseInt($(this).val()) - ConcessionAmount);     
            		    }
            		});
            		
            		TotalPayableAmount = AmountPayable - MonthTotalFee;
            		
                	$('#RecordTable' + MonthID).remove();
                	$('#TotalFee').text(TotalFee - TotalMonthlyFeeAmount);
                	// Check.val('');
            	}
            	
                $('#AmountPayable').val(TotalPayableAmount);
                $('#AmountPaid').val(TotalPayableAmount);
            }
        });
    });

    $('.DueMonthID').change(function(){

        var AcademicYearID = $(this).attr('AcademicYearID');
        var StudentID = $(this).attr('StudentID');
        
        var MonthID = $(this).attr('id').slice(3);
        var AmountPayable = parseInt($('#AmountPayable').val());
        var DueAmount = parseInt($('#' + MonthID + 'Due').text());

        var TotalFee = parseInt($('#TotalFee').text());

        // var StudentID = parseInt($(this).val());
       	
    	if (isNaN(AmountPayable)) 
    	{
    		AmountPayable = 0;
    	}
    	
    	if (isNaN(DueAmount)) 
    	{
    		DueAmount = 0;
    	}

    	if (isNaN(TotalFee)) 
    	{
    		TotalFee = 0;
    	}

    	var Check = $(this);
    
    	$.post("/xhttp_calls/get_student_due_fees_by_months.php", {SelectedMonthID:MonthID, SelectedStudentID:StudentID, SelectedAcademicYearID:AcademicYearID}, function(data)
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

            // 		var ModalHtml = $('#FeeDetails').html();
                	$('#FeeDetails').append(ResultArray[2]); //Fee Breakup By month
                	$('#TotalFee').text(TotalFee + TotalMonthlyFeeAmount);
            	}
            	else
            	{
            		// TotalPayableAmount = AmountPayable - TotalMonthlyFeeAmount;
            		var MonthTotalFee = 0;
            		
            		$('.CheckedFeeHead' + MonthID).each(function(){
            		    
            		    StudentFeeStructureID = $(this).attr('id').slice(10);

            		    if ($('#' + StudentFeeStructureID).prop('checked') == true)
            		    {
            		        ConcessionAmount = parseInt($('#ConcessionAmount' + StudentFeeStructureID).val());

            		    	if (isNaN(ConcessionAmount)) 
            		    	{
            		    		ConcessionAmount = 0;
            		    	}

            		        MonthTotalFee = (MonthTotalFee + parseInt($(this).val()) - ConcessionAmount);     
            		    }
            		});
            		
            		TotalPayableAmount = AmountPayable - MonthTotalFee;
            		
                	$('#RecordTable' +AcademicYearID+ MonthID).remove();
                	$('#TotalFee').text(TotalFee - MonthTotalFee);
                	// Check.val('');
            	}
            	
                $('#AmountPayable').val(TotalPayableAmount);
                $('#AmountPaid').val(TotalPayableAmount);
            }
        });

    	// if ($(this).is(':checked')) 
     //   	{
     //   		$('#AmountPayable').val(AmountPayable + DueAmount);
     //   		$('#AmountPaid').val(AmountPayable + DueAmount);
     //   	}
     //   	else
     //   	{
     //   		$('#AmountPayable').val(AmountPayable - DueAmount);	
     //   		$('#AmountPaid').val(AmountPayable - DueAmount);	
     //   	}
    });

    $('body').on('change', '#AdjustAdvanceFee', function(){

    	var AdvanceFee = '<?php echo $StudentAdvanceFee; ?>';

    	var AmountPayable = parseInt($('#AmountPayable').val());
		var AmountPaid = parseInt($('#AmountPaid').val());

		if (isNaN(AmountPayable)) 
    	{
    		AmountPayable = 0;
    	}

    	if (isNaN(AmountPaid)) 
    	{
    		AmountPaid = 0;
    	}

    	if (isNaN(AdvanceFee)) 
    	{
    		AdvanceFee = 0;
    	}

    	if ($(this).is(':checked')) 
    	{
    		if (AmountPayable == 0) 
    		{
    			alert('Please select any fee to submit.');
    			$(this).prop('checked', false);
    			return false;
    		}

    		$('#AdvanceAmount').val(AdvanceFee);
			$('#AdvanceAmount').slideDown();
			$('#AmountPaid').val(AmountPaid - AdvanceFee);
    	}
    	else
    	{
    		var AdvanceFee = parseInt($('#AdvanceAmount').val());
    		
    		if (isNaN(AdvanceFee)) 
	    	{
	    		AdvanceFee = 0;
	    	}

	    	$('#AmountPaid').val(AmountPaid + AdvanceFee);

    		$('#AdvanceAmount').slideUp();
    		$('#AdvanceAmount').val('');
    	}
    });

    $('body').on('focusout', '#AdvanceAmount', function(){

    	var AdvanceFee = parseInt($(this).val());
		var AmountPayable = parseInt($('#AmountPayable').val());

		if (isNaN(AdvanceFee)) 
    	{
    		AdvanceFee = 0;
    	}

    	var AmountPaid = parseInt($('#AmountPaid').val());

		if (isNaN(AmountPaid)) 
    	{
    		AmountPaid = 0;
    	}

    	if ($('#AdjustAdvanceFee').is(':checked')) 
    	{
			$('#AmountPaid').val(AmountPaid - AdvanceFee);
    	}
    });

    $('body').on('focusin', '#AdvanceAmount', function(){

    	var AdvanceFee = parseInt($(this).val());
		var AmountPayable = parseInt($('#AmountPayable').val());

		if (isNaN(AdvanceFee)) 
    	{
    		AdvanceFee = 0;
    	}

    	var AmountPaid = parseInt($('#AmountPaid').val());

		if (isNaN(AmountPaid)) 
    	{
    		AmountPaid = 0;
    	}

    	if ($('#AdjustAdvanceFee').is(':checked')) 
    	{
			$('#AmountPaid').val(AmountPaid + AdvanceFee);
    	}
    });

    $('body').on('change', '.WaveOff', function(){

    	var ID = $(this).attr('id').slice(7);
		var HeadAmount = parseInt($('#HeadAmount'+ ID).val());
		var ConcessionAmount = parseInt($('#ConcessionAmount'+ ID).val());

		var AmountPayable = parseInt($('#AmountPayable').val());

		var TotalFee = parseInt($('#TotalFee').text());

		if (isNaN(AmountPayable)) 
    	{
    		AmountPayable = 0;
    	}

    	if (isNaN(ConcessionAmount)) 
    	{
    		ConcessionAmount = 0;
    	}

    	if (isNaN(TotalFee)) 
    	{
    		TotalFee = 0;
    	}

    	if ($(this).is(':checked')) 
    	{
	    	$('#AmountPayable').val(AmountPayable + ConcessionAmount - HeadAmount);
			$('#AmountPaid').val(AmountPayable + ConcessionAmount - HeadAmount);
			$('#TotalFee').text(TotalFee - HeadAmount);

			$('#ConcessionAmount'+ ID).val('');
    		
    		// $(this).parent().parent().find('tr').find('input').addClass('readonly');
    	}
    	else
    	{
    		$('#AmountPayable').val(AmountPayable + HeadAmount);
			$('#AmountPaid').val(AmountPayable + HeadAmount);
			$('#TotalFee').text(TotalFee + HeadAmount);
    	}
    	
    });

    $('body').on('change', '.StudentFeeStructureID', function(){

    	if ($(this).is(':checked')) 
    	{
    		var ID = $(this).attr('id');
    		var HeadAmount = parseInt($('#HeadAmount'+ ID).val());
    		var ConcessionAmount = parseInt($('#ConcessionAmount'+ ID).val());

    		var AmountPayable = parseInt($('#AmountPayable').val());

    		var TotalFee = parseInt($('#TotalFee').text());

    		if (isNaN(AmountPayable)) 
	    	{
	    		AmountPayable = 0;
	    	}

	    	if (isNaN(HeadAmount)) 
	    	{
	    		HeadAmount = 0;
	    	}

	    	if (isNaN(ConcessionAmount)) 
	    	{
	    		ConcessionAmount = 0;
	    	}

	    	if (isNaN(TotalFee)) 
	    	{
	    		TotalFee = 0;
	    	}

    		$('#AmountPayable').val(AmountPayable + HeadAmount - ConcessionAmount);
    		$('#AmountPaid').val(AmountPayable + HeadAmount - ConcessionAmount);
    		$('#TotalFee').text(TotalFee + HeadAmount);
    	}
    	else
    	{
    		var ID = $(this).attr('id');
    		var HeadAmount = parseInt($('#HeadAmount'+ ID).val());
    		var ConcessionAmount = parseInt($('#ConcessionAmount'+ ID).val());
    		
    		var AmountPayable = parseInt($('#AmountPayable').val());
    		var TotalFee = parseInt($('#TotalFee').text());

    		if (isNaN(AmountPayable)) 
	    	{
	    		AmountPayable = 0;
	    	}

	    	if (isNaN(HeadAmount)) 
	    	{
	    		HeadAmount = 0;
	    	}

	    	if (isNaN(ConcessionAmount)) 
	    	{
	    		ConcessionAmount = 0;
	    	}

	    	if (isNaN(TotalFee)) 
	    	{
	    		TotalFee = 0;
	    	}

    		$('#AmountPayable').val(AmountPayable - HeadAmount + ConcessionAmount);
    		$('#AmountPaid').val(AmountPayable - HeadAmount + ConcessionAmount);
    		
    		$('#TotalFee').text(TotalFee - HeadAmount + ConcessionAmount);
    	}
    });
    
    $('.PreviousYearDue').change(function(){

    	var StudentID = $(this).val();
    	if ($(this).is(':checked')) 
    	{
    		$('#PreviousYearDueAmount'+ StudentID).prop('disabled', false);
    		$('#PreviousYearWaveOffAmount'+ StudentID).prop('disabled', false);

    		if ($('#PreviousYearDueAmount'+ StudentID).val() != '') 
    		{
    			var AmountPayable = parseInt($('#AmountPayable').val());
	    		var PreviousYearDueAmount = parseInt($('#PreviousYearDueAmount'+ StudentID).val());

	    		if (isNaN(AmountPayable)) 
		    	{
		    		AmountPayable = 0;
		    	}

		    	if (isNaN(PreviousYearDueAmount)) 
		    	{
		    		PreviousYearDueAmount = 0;
		    	}

	    		$('#AmountPayable').val(AmountPayable + PreviousYearDueAmount);
	    		$('#AmountPaid').val(AmountPayable + PreviousYearDueAmount);
	    		$('#hdnPreviousYearDueAmount'+ StudentID).val(PreviousYearDueAmount);
    		}
    	}
    	else
    	{
    		$('#PreviousYearDueDiscription'+ StudentID).prop('disabled', true);
    		$('#PreviousYearDueAmount'+ StudentID).prop('disabled', true);
    		$('#PreviousYearWaveOffAmount'+ StudentID).prop('disabled', true);
    		
    		if ($('#PreviousYearDueAmount'+ StudentID).val() != '') 
    		{
    			var AmountPayable = parseInt($('#AmountPayable').val());
	    		var PreviousYearDueAmount = parseInt($('#PreviousYearDueAmount'+ StudentID).val());

	    		if (isNaN(AmountPayable)) 
		    	{
		    		AmountPayable = 0;
		    	}

		    	if (isNaN(PreviousYearDueAmount)) 
		    	{
		    		PreviousYearDueAmount = 0;
		    	}

	    		$('#AmountPayable').val(AmountPayable - PreviousYearDueAmount);
	    		$('#AmountPaid').val(AmountPayable - PreviousYearDueAmount);
	    		// $('#PreviousYearDueAmount').val('');
	    		// $('#hdnPreviousYearDueAmount').val('');
    		}
    	}
    });

    $('.PreviousYearDueAmount').focusin(function(){
    	if ($(this).val() != '') 
    	{
    		var StudentID = $(this).attr('id').slice(21);
    		var AmountPayable = parseInt($('#AmountPayable').val());
    		var PreviousYearDueAmount = parseInt($(this).val());

	    	if (isNaN(AmountPayable)) 
	    	{
	    		AmountPayable = 0;
	    	}

	    	if (isNaN(PreviousYearDueAmount)) 
	    	{
	    		PreviousYearDueAmount = 0;
	    	}

	    	$('#AmountPayable').val(AmountPayable - PreviousYearDueAmount);
	    	$('#AmountPaid').val(AmountPayable - PreviousYearDueAmount);

	    	$('#RestDue'+ StudentID).text('');
    	}
    });

    $('.PreviousYearDueAmount').focusout(function(){
    	if ($(this).val() != '') 
    	{
    		var StudentID = $(this).attr('id').slice(21);
    		var AmountPayable = parseInt($('#AmountPayable').val());
    		var PreviousYearDueAmount = parseInt($(this).val());

    		var PreviousYearDueArray = <?php echo json_encode($PreviousYearDue); ?>;
    		var ActualDueAmount = 0;

    		$.each(PreviousYearDueArray, function(key, value) {
			    if (StudentID == key) 
			    {
			    	ActualDueAmount = value;
			    }
			});

	    	if (isNaN(AmountPayable)) 
	    	{
	    		AmountPayable = 0;
	    	}

	    	if (isNaN(PreviousYearDueAmount)) 
	    	{
	    		PreviousYearDueAmount = 0;
	    	}

	    	if (PreviousYearDueAmount > ActualDueAmount) 
	    	{
	    		alert('Entered previous due should not be greater than actual due amount.');
	    		PreviousYearDueAmount = ActualDueAmount;
	    		$(this).val(PreviousYearDueAmount);
	    	}

	    	$('#AmountPayable').val(AmountPayable + PreviousYearDueAmount);
	    	$('#AmountPaid').val(AmountPayable + PreviousYearDueAmount);
	    	$('#PreviousYearDueAmount').prop('disabled', true);

	    	$('#hdnPreviousYearDueAmount'+ StudentID).val(PreviousYearDueAmount);

	    	$('#RestDue'+ StudentID).text('Rest Due: ' + (ActualDueAmount - PreviousYearDueAmount));
    	}
    });

    $('.PreviousYearWaveOffAmount').focusout(function(){
    	if ($(this).val() != '') 
    	{
    		var StudentID = $(this).attr('id').slice(25);

    		var WaveOffAmount = parseInt($(this).val());
    		var PreviousYearDueAmount = parseInt($('#PreviousYearDueAmount' + StudentID).val());

    		var PreviousYearDueArray = <?php echo json_encode($PreviousYearDue); ?>;
    		var ActualDueAmount = 0;

    		$.each(PreviousYearDueArray, function(key, value) 
    		{
			    if (StudentID == key) 
			    {
			    	ActualDueAmount = value;
			    }
			});

	    	if (isNaN(PreviousYearDueAmount)) 
	    	{
	    		PreviousYearDueAmount = 0;
	    	}

	    	if (WaveOffAmount < 0) 
	    	{
	    		alert('Wave off amount should not be negative.');
	    		$(this).val('');
	    		return false;
	    	}

	    	if (WaveOffAmount > (ActualDueAmount - PreviousYearDueAmount)) 
	    	{
	    		alert('Wave off amount should not be greater than rest due amount.');
	    		$(this).val('');
	    		return false;
	    	}

	    	$('#RestDue'+ StudentID).text('Rest Due: ' + (ActualDueAmount - PreviousYearDueAmount - WaveOffAmount));
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
    
    $('body').on('focusin', '.StudentFeeHeadAmount', function(){

    	StudentFeeStructureID = $(this).attr('id').slice(10);
    	var HeadAmount = 0;
    	var ConcessionAmount = 0;

    	if ($('#' + StudentFeeStructureID).prop('checked') == true) 
    	{
    		var AmountPayable = parseInt($('#AmountPayable').val());
    			HeadAmount = parseInt($('#HeadAmount' + StudentFeeStructureID).val());
    			ConcessionAmount = parseInt($('#ConcessionAmount' + StudentFeeStructureID).val());
            
            var TotalFee = parseInt($('#TotalFee').text());

    		if (isNaN(TotalFee)) 
	    	{
	    		TotalFee = 0;
	    	}
	    	
    		if (isNaN(AmountPayable)) 
	    	{
	    		AmountPayable = 0;
	    	}

	    	if (isNaN(HeadAmount)) 	
	    	{
	    		HeadAmount = 0;
	    	}

	    	if (isNaN(ConcessionAmount)) 
	    	{
	    		ConcessionAmount = 0;
	    	}

	    	$('#AmountPayable').val(AmountPayable - HeadAmount + ConcessionAmount);
	    	$('#AmountPaid').val(AmountPayable - HeadAmount + ConcessionAmount);
	    	
	    	$('#TotalFee').text(TotalFee - HeadAmount);
    	}
    });

    $('body').on('focusout', '.StudentFeeHeadAmount', function(){

    	StudentFeeStructureID = $(this).attr('id').slice(10);
    	var HeadAmount = 0;
    	var ConcessionAmount = 0;

    	if ($('#' + StudentFeeStructureID).prop('checked') == true) 
    	{
    		var AmountPayable = parseInt($('#AmountPayable').val());
    			HeadAmount = parseInt($('#HeadAmount' + StudentFeeStructureID).val());
    			ConcessionAmount = parseInt($('#ConcessionAmount' + StudentFeeStructureID).val());
            
            var TotalFee = parseInt($('#TotalFee').text());

    		if (isNaN(TotalFee)) 
	    	{
	    		TotalFee = 0;
	    	}
	    	
    		if (isNaN(AmountPayable)) 
	    	{
	    		AmountPayable = 0;
	    	}

	    	if (isNaN(HeadAmount))
	    	{
	    		HeadAmount = 0;
	    	}

	    	if (isNaN(ConcessionAmount))
	    	{
	    		ConcessionAmount = 0;
	    	}

	    	$('#AmountPayable').val(AmountPayable + HeadAmount - ConcessionAmount);
	    	$('#AmountPaid').val(AmountPayable + HeadAmount - ConcessionAmount);
	    	
	    	$('#TotalFee').text(TotalFee + HeadAmount);
    	}    	
    });

    $('body').on('focusin', '.ConcessionAmount', function(){

    	StudentFeeStructureID = $(this).attr('id').slice(16);
    	var ConcessionAmount = 0;

    	if ($('#' + StudentFeeStructureID).prop('checked') == true) 
    	{
    		var AmountPayable = parseInt($('#AmountPayable').val());
    		var	ConcessionAmount = parseInt($('#ConcessionAmount' + StudentFeeStructureID).val());

    		var TotalFee = parseInt($('#TotalFee').text());

    		if (isNaN(AmountPayable)) 
	    	{
	    		AmountPayable = 0;
	    	}

	    	if (isNaN(ConcessionAmount)) 
	    	{
	    		ConcessionAmount = 0;
	    	}

	    	if (isNaN(TotalFee)) 
	    	{
	    		TotalFee = 0;
	    	}

	    	$('#AmountPayable').val(AmountPayable + ConcessionAmount);
	    	$('#AmountPaid').val(AmountPayable + ConcessionAmount);

	    	$('#TotalFee').text(TotalFee + ConcessionAmount);
    	}
    });

    $('body').on('focusout', '.ConcessionAmount', function(){

    	StudentFeeStructureID = $(this).attr('id').slice(16);
    	var ConcessionAmount = 0;

    	if ($('#' + StudentFeeStructureID).prop('checked') == true) 
    	{
    		var AmountPayable = parseInt($('#AmountPayable').val());
    		    HeadAmount = parseInt($('#HeadAmount' + StudentFeeStructureID).val());
    			ConcessionAmount = parseInt($('#ConcessionAmount' + StudentFeeStructureID).val());
    		var TotalFee = parseInt($('#TotalFee').text());	

    		if (isNaN(AmountPayable)) 
	    	{
	    		AmountPayable = 0;
	    	}

	    	if (isNaN(ConcessionAmount))
	    	{
	    		ConcessionAmount = 0;
	    	}

	    	if (isNaN(TotalFee))
	    	{
	    		TotalFee = 0;
	    	}
            
            if (ConcessionAmount > HeadAmount) 
	    	{
	    		alert('Concession amount should not be greater than final amount.');
	    		$('#ConcessionAmount' + StudentFeeStructureID).val('');
	    		$('#ConcessionAmount' + StudentFeeStructureID).focus();
	    		return false;
	    	}
	    	
	    	$('#AmountPayable').val(AmountPayable - ConcessionAmount);
	    	$('#AmountPaid').val(AmountPayable - ConcessionAmount);

	    	$('#TotalFee').text(TotalFee - ConcessionAmount);
    	}    	
    });
    
    $('#AmountPaid').focusout(function(){
    	if ($(this).val() != '') 
    	{
    		var AmountPayable = parseInt($('#AmountPayable').val());
    		var AmountPaid = parseInt($('#AmountPaid').val());

	    	if (isNaN(AmountPayable)) 
	    	{
	    		AmountPayable = 0;
	    	}

	    	if (isNaN(LateFeeAmount)) 
	    	{
	    		LateFeeAmount = 0;
	    	}

	    	var RestAmount = AmountPayable - AmountPaid;
	    	if (RestAmount > 0) 
	    	{
	    		$('#RestAmount').text('Rest Amount: ' + RestAmount);
	    	}
	    	else
	    	{
	    		$('#RestAmount').text('');
	    	}
    	}
    });
});
</script>
</body>
</html>
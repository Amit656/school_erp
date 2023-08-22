<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");
require_once("../../classes/class.date_processing.php");

require_once("../../classes/library_management/class.book_issue.php");
require_once("../../classes/library_management/class.books_fine.php");

require_once("../../includes/global_defaults.inc.php");

//1. RECHECK IF THE USER IS VALID //
try
{
	$AuthObject = new ApplicationAuthentication;
	$LoggedUser = new User(0, $AuthObject->CheckValidUser());
}

// THIS CATCH BLOCK BUBBLES THE EXCEPTION TO THE BUILT IN 'Exception' CLASS IF THERE ARE ANY UNCAUGHT ERRORS //
catch (ApplicationAuthException $e)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}
catch (Exception $e)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}
// END OF 1. //

if ($LoggedUser->HasPermissionForTask(TASK_RETURN_BOOK) !== true)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}

$HasErrors = false;

$IssuedBookDetails = array();

$FineTypeList = array('LateFine' => 'LateFine', 'MissingBook' => 'Missing Book', 'DamageBook' => 'Damage Book');

$Clean = array();
$Clean['Process'] = 0;

$Clean['BooksCopyID'] = 0;
$Clean['ActualReturnDate'] = date('d/m/Y');

$Clean['FineType'] = '';
$Clean['FineAmount'] = '';
$Clean['Description'] = '';

$Clean['IsPaid'] = 0;
$Clean['PaidDate'] = '';

$Clean['PaymentReceivedBy'] = 0;
$Clean['PaymentReceivedOn'] = '';

$Clean['ApplicableFine'] = 0;

if (isset($_POST['hdnProcess']))
{
	$Clean['Process'] = (int) $_POST['hdnProcess'];
}
else if (isset($_GET['Process']))
{
	$Clean['Process'] = (int) $_GET['Process'];
}
switch ($Clean['Process'])
{
	case 1:
		if (isset($_POST['hdnBookIssueID']))
		{
			$Clean['BookIssueID'] = (int) $_POST['hdnBookIssueID'];
		}
		if (isset($_POST['txtActualReturnDate'])) 
		{
			$Clean['ActualReturnDate'] = strip_tags(trim($_POST['txtActualReturnDate']));
		}
		if (isset($_POST['chkApplicableFine'])) 
		{
			$Clean['ApplicableFine'] = 1;
		}

		if (isset($_POST['drdFineType'])) 
		{
			$Clean['FineType'] = strip_tags(trim($_POST['drdFineType']));
		}
		if (isset($_POST['txtFineAmount'])) 
		{
			$Clean['FineAmount'] = strip_tags(trim($_POST['txtFineAmount']));
		}
		if (isset($_POST['txtDescription'])) 
		{
			$Clean['Description'] = strip_tags(trim($_POST['txtDescription']));
		}

		if (isset($_POST['chkIsPaid'])) 
		{
			$Clean['IsPaid'] = 1;
		}
		if (isset($_POST['txtPaidDate'])) 
		{
			$Clean['PaidDate'] = strip_tags(trim($_POST['txtPaidDate']));
		}
		
		$NewRecordValidator = new Validator();

		if ($Clean['ApplicableFine'] == 1) 
		{
			$NewRecordValidator->ValidateInSelect($Clean['FineType'], $FineTypeList, 'Please select fine type.');
			$NewRecordValidator->ValidateNumeric($Clean['FineAmount'], 'Please enter numeric value for fine amount.');

			if ($Clean['Description'] != '') 
			{
				$NewRecordValidator->ValidateStrings($Clean['Description'], 'Description should not be between 3 to 500.', 3, 500);
			}

			if ($Clean['IsPaid'] == 1) 
			{
				$NewRecordValidator->ValidateDate($Clean['PaidDate'], 'Please enter valid paid date.');
			}
		}

		try
		{
		    $BookIssueObject = new BookIssue($Clean['BookIssueID']);
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

		$BookIssueObject->SetIsReturned(1);				
		$BookIssueObject->SetActualReturnDate(date('Y-m-d', strtotime(DateProcessing::ToggleDateDayAndMonth(($Clean['ActualReturnDate'])))));
					
		$BookIssueObject->SetRetunedReceivedBy($LoggedUser->GetUserID());

		if (!$BookIssueObject->Save())
		{			
			$NewRecordValidator->AttachTextError(ProcessErrors($BookIssueObject->GetLastErrorCode()));
			$HasErrors = true;
			break;
		}		

		if ($Clean['ApplicableFine'] == 1) 
		{
			$NewBooksFine = new BooksFine();

			$NewBooksFine->SetBookIssueID($Clean['BookIssueID']);

			$NewBooksFine->SetFineType($Clean['FineType']);
			$NewBooksFine->SetFineAmount($Clean['FineAmount']);
			$NewBooksFine->SetDescription($Clean['Description']);

			$NewBooksFine->SetIsPaid($Clean['IsPaid']);

			if ($Clean['IsPaid'] == 1) 
			{
				$NewBooksFine->SetPaidDate(date('Y-m-d', strtotime(DateProcessing::ToggleDateDayAndMonth(($Clean['PaidDate'])))));

				$NewBooksFine->SetPaymentReceivedBy($LoggedUser->GetUserID());
				$NewBooksFine->SetPaymentReceivedOn(date('Y-m-d h:i:sa'));
			}

			$NewBooksFine->SetCreateUserID($LoggedUser->GetUserID());

			if (!$NewBooksFine->Save())
			{
				$NewRecordValidator->AttachTextError(ProcessErrors($NewBooksFine->GetLastErrorCode()));
				$HasErrors = true;
				break;
			}
		}
		
		header('location:return_book.php?Mode=AS');
		exit;
	break;

	case 7:

		if (isset($_POST['txtBooksCopyID'])) 
		{
			$Clean['BooksCopyID'] = (int) $_POST['txtBooksCopyID'];
		}
		else if (isset($_GET['BooksCopyID'])) 
		{
			$Clean['BooksCopyID'] = (int) $_GET['BooksCopyID'];
		}

		$NewRecordValidator = new Validator();

		if ($Clean['BooksCopyID'] <= 0) 
		{
			$NewRecordValidator->AttachTextError('Please enter book copy id.');
		}

		if ($NewRecordValidator->HasNotifications())
		{	
			$HasErrors = true;
			break;
		}

		$IssuedBookDetails = BookIssue::GetIssueBookDetails($Clean['BooksCopyID']);	

		if (count($IssuedBookDetails) <= 0) 
		{
			$NewRecordValidator->AttachTextError('This book is not issued to anyone.');
			$HasErrors = true;
			break;
		}	

	break;
}

$LandingPageMode = '';
if (isset($_GET['Mode']))
{
    $LandingPageMode = $_GET['Mode'];
}

require_once('../html_header.php');
?>
<title>Return Book</title>
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
                    <h1 class="page-header">Return Book</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             
        	<div class="panel panel-default">
                <div class="panel-heading">
                    <strong>Enter Return Book Copy ID</strong>                        
                </div>
                <div class="panel-body">
<?php
					if ($HasErrors == true)
					{
						echo $NewRecordValidator->DisplayErrors();
					}
					else if ($LandingPageMode == 'AS')
                    {
                        echo '<div class="alert alert-success">Book returned successfully.</div>';
                    }
?>                   
					<form class="form-horizontal" name="ReturnBook" action="return_book.php" method="post"> 
						<div class="form-group">
		                    <label for="BooksCopyID" class="col-lg-2 control-label">Book Copy ID</label>
		                    <div class="col-lg-4">
		                    	<input class="form-control" type="text" maxlength="20" id="BooksCopyID" name="txtBooksCopyID" value="<?php echo ($Clean['BooksCopyID']) ? $Clean['BooksCopyID'] : ''; ?>" placeholder="Enter issued book copy id" />
		                    </div>
		                </div>
		                <div class="form-group">
	                        <div class="col-sm-offset-2 col-lg-10">
	                        	<input type="hidden" name="hdnProcess" value="7" />
								<button type="submit" class="btn btn-primary"><i class="fa fa-search"></i>&nbsp;Search</button>
	                        </div>
                      	</div>
		            </form>		            
		        </div>		        
            </div>
<?php
		if (count($IssuedBookDetails)) 
		{
			foreach ($IssuedBookDetails as $BookIssueID => $Details) 
			{
							
?>
				<div class="panel panel-default">
	                <div class="panel-heading">
	                    <strong>Return Book Details</strong>                        
	                </div>
	                <div class="panel-body">         
						<form class="form-horizontal" name="ReturnBook" action="return_book.php" method="post"> 
							<div class="form-group">
			                    <label for="BookID" class="col-lg-2 control-label">Book</label>
			                    <div class="col-lg-8">
			                    	<input class="form-control" type="text" maxlength="200" id="BookID" name="txtBookID" value="<?php echo $Details['BookName']; ?>" disabled='disabled'/>
			                    </div>			                    
			                </div>
			                <div class="form-group">
			                	<label for="IssueDate" class="col-lg-2 control-label">Issue Date</label>
			                    <div class="col-lg-3">
			                    	<input class="form-control" type="text" maxlength="200" id="IssueDate" name="txtIssueDate" value="<?php echo date('d/m/Y', strtotime($Details['IssueDate'])); ?>" disabled='disabled'/>
			                    </div>
			                    <label for="ExpectedReturnDate" class="col-lg-2 control-label">Expected Return Date</label>
			                    <div class="col-lg-3">
			                    	<input class="form-control select-date" type="text" maxlength="200" id="ExpectedReturnDate" name="txtExpectedReturnDate" value="<?php echo date('d/m/Y', strtotime($Details['ExpectedReturnDate'])); ?>" disabled='disabled'/>
			                    </div>			                    
			                </div>
			                <div class="form-group">
			                    <label for="UserName" class="col-lg-2 control-label">User</label>
			                    <div class="col-lg-8">
			                    	<input class="form-control" type="text" maxlength="200" id="UserName" name="txtUserName" value="<?php echo $Details['UserName']; ?>" disabled='disabled'/>
			                    </div>			                    
			                </div>
			                <div class="form-group">
			                    <label for="UserType" class="col-lg-2 control-label">User Type</label>
			                    <div class="col-lg-3">
			                    	<input class="form-control" type="text" maxlength="50" id="UserType" name="txtUserType" value="<?php echo $Details['IssuedToUserType']; ?>" disabled='disabled'/>
			                    </div>	
<?php
							if ($Details['Class'] != '') 
							{
?>
								<label for="Class" class="col-lg-2 control-label">Class</label>
			                    <div class="col-lg-3">
			                    	<input class="form-control" type="text" maxlength="50" id="Class" name="txtClass" value="<?php echo $Details['Class']; ?>" disabled='disabled'/>
			                    </div>
<?php								
							}
?>			                    			                   
			                </div>
			                <div class="form-group">
			                	<label for="ActualReturnDate" class="col-lg-2 control-label">Return Date</label>
	                            <div class="col-lg-3"> 
	                            	<input class="form-control select-date" type="text" maxlength="10" id="ActualReturnDate" name="txtActualReturnDate" value="<?php echo $Clean['ActualReturnDate']; ?>" />
	                            </div>
	                            <label for="ApplicableFine" class="col-lg-2 control-label">Applicable Fine</label>
		                        <div class="col-lg-4">
		                            <input type="checkbox" class="checkbox-inline" id="ApplicableFine" name="chkApplicableFine" <?php echo ($Clean['ApplicableFine'] == 1) ? 'checked="checked"' : ''; ?> value="1" />&nbsp; <label for="ApplicableFine" style="font-weight: normal;">Yes</label>
		                        </div>
			                </div>

			                <div id="FineDetailsContainer" <?php echo ($Clean['ApplicableFine'] == 0) ? 'class="collapse"' : '';?>>
                            	<div class="form-group">
		                            <div class="col-lg-5"></div>
		                            <label for="BookPrice" class="col-lg-2 control-label text-danger">Book Price</label>
		                            <div class="col-lg-3">
		                            	<div class="input-group">
		                            		<input class="form-control" type="text" maxlength="10" id="BookPrice" name="txtBookPrice" value="<?php echo ($Details['BookPrice']) ? $Details['BookPrice'] : ''; ?>" />
		                            		<span class="input-group-addon">Rs</span>
		                            	</div>		                                
		                            </div>
		                        </div>
		                        <div class="form-group">
		                            <label for="FineType" class="col-lg-2 control-label">Fine Type</label>
		                            <div class="col-lg-3">
		                                <select class="form-control" name="drdFineType" id="FineType"> 
		                                	<option value="0">-- Select Fine Type --</option>                                 
		<?php
		                                if (is_array($FineTypeList) && count($FineTypeList) > 0)
		                                {
		                                    foreach($FineTypeList as $FineType => $FineTypeName)
		                                    {
		                                        echo '<option ' . (($Clean['FineType'] == $FineType) ? 'selected="selected"' : '' ) . ' value="' . $FineType . '">' . $FineTypeName . '</option>';
		                                    }
		                                }
		?>
		                                </select>
		                            </div>
		                            <label for="FineAmount" class="col-lg-2 control-label">Fine Amount</label>
		                            <div class="col-lg-3">
		                            	<div class="input-group">
		                            		<input class="form-control" type="text" maxlength="10" id="FineAmount" name="txtFineAmount" value="<?php echo ($Clean['FineAmount']) ? $Clean['FineAmount'] : ''; ?>" />
		                            		<span class="input-group-addon">Rs</span>
		                            	</div>		                                
		                            </div>
		                        </div>
		                        <div class="form-group">
				                    <label for="Description" class="col-lg-2 control-label">Description</label>
				                    <div class="col-lg-8">
				                    	<textarea class="form-control"  id="Description" name="txtDescription"><?php echo $Clean['Description']; ?></textarea>
				                    </div>			                    
				                </div>
				                <div class="form-group">
				                	<label for="IsPaid" class="col-lg-2 control-label">Is Paid</label>
			                        <div class="col-lg-3">
			                            <input type="checkbox" class="checkbox-inline" id="IsPaid" name="chkIsPaid" <?php echo ($Clean['IsPaid'] == 1) ? 'checked="checked"' : ''; ?> value="1" />&nbsp; <label for="IsPaid" style="font-weight: normal;">Yes</label>
			                        </div>
				                	<label for="PaidDate" class="col-lg-2 control-label">Paid Date</label>
		                            <div class="col-lg-3"> 
		                            	<input class="form-control select-date" type="text" maxlength="10" id="PaidDate" name="txtPaidDate" value="<?php echo $Clean['PaidDate']; ?>" />
		                            </div>		                            
				                </div>
                        	</div>
			                <div class="form-group">
		                        <div class="col-sm-offset-2 col-lg-10">
		                        	<input type="hidden" name="hdnProcess" value="1" />
		                        	<input type="hidden" name="hdnBookIssueID" value="<?php echo $BookIssueID; ?>" />
									<button type="submit" class="btn btn-primary"><i class="fa fa-save"></i>&nbsp;Return</button>
		                        </div>
	                      	</div>
			            </form>		            
			        </div>		        
	            </div>
<?php		
			}		
		}
?>                       
               	            
        </div>
        <!-- /#page-wrapper -->

    </div>
    <!-- /#wrapper -->
<?php
require_once('../footer.php');
?>
<script src="../vendor/jquery-ui/jquery-ui.min.js"></script>

<script type="text/javascript">
$(document).ready(function(){ 

	$(".select-date").datepicker({
		changeMonth: true,
		changeYear: true,
		dateFormat: 'dd/mm/yy'
	});

	$('#ApplicableFine').change(function(){
		if ($(this).is(':checked')) 
		{
			$('#FineDetailsContainer').slideDown();
		}
		else
		{
			$('#FineDetailsContainer').slideUp();
		}
	});

	$('#FineType').change(function(){

		if ($(this).val() == 'LateFine') 
		{
			var UserType = $('#UserType').val();

			var ActualReturnDate = $("#ActualReturnDate").datepicker("getDate");
		    var ExpectedReturnDate = $("#ExpectedReturnDate").datepicker("getDate");

		    if (ActualReturnDate.getTime() <= ExpectedReturnDate.getTime()) 
		    {
		    	alert('Not late in returning book.');
		    	return false;
		    }

			var OneDay = 24*60*60*1000; //in milliseconds				
    		var ExtraDays = 0;

		    ExtraDays = Math.round(Math.abs((ActualReturnDate.getTime() - ExpectedReturnDate.getTime())/(OneDay)));		    		   
			
			$.post("/xhttp_calls/get_late_fine_by_user_type.php", {SelectedUserType:UserType, ExtraDays:ExtraDays}, function(data)
			{
				ResultArray = data.split("|*****|");
				
				if (ResultArray[0] == 'error')
				{
					$('#FineAmount').val('');
					alert(ResultArray[1]);				
				}
				else
				{			
					$('#FineAmount').val(ResultArray[1]);
				}
			});
		}
		else
		{
			$('#FineAmount').val('');
		}
	});
});
</script>
</body>
</html>
<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");
require_once("../../classes/class.date_processing.php");

require_once("../../classes/fee_management/class.fee_receipt_terms_condition.php");

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
	header('location:../unauthorized_login_admin.php');
	exit;
}
catch (Exception $e)
{
	header('location:../unauthorized_login_admin.php');
	exit;
}
// END OF 1. //

if ($LoggedUser->HasPermissionForTask(TASK_ADD_FEE_RECEIPT_TERM_CONDITION) !== true)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}

$HasErrors = false;

$Clean = array();
$Clean['Process'] = 0;

$Clean['TermConditionMessageList'] =  array();
$Clean['TermConditionMessageList'] = FeeReceiptTermCondition::GetAllTermConditionMessage();

if (count($Clean['TermConditionMessageList']) <= 0) 
{
	$Clean['TermConditionMessageList'] = array(1 => '');
}

$Clean['TermConditionMessage'] = '';

$Errors = array();

if (isset($_POST['hdnProcess']))
{
	$Clean['Process'] = (int) $_POST['hdnProcess'];
}
switch ($Clean['Process'])
{
	case 1:

		if (isset($_POST['txtTermConditionMessage']) && is_array($_POST['txtTermConditionMessage']))
		{
			$Clean['TermConditionMessageList'] = $_POST['txtTermConditionMessage'];
			
		}

		$NewRecordValidator = new Validator();

		foreach ($Clean['TermConditionMessageList'] as $Counter => $TermConditionMessage) 
		{
			if ($TermConditionMessage == '') 
			{
				$Errors[$Counter] = 'Error';
				//validate strings//
			$NewRecordValidator->ValidateStrings($Clean['TermConditionMessage'], 'Term condition message is required and should be between 3 and 250 characters.', 3, 250);

			}	
		}

		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}
				
		$NewFeeReceiptTermCondition = new FeeReceiptTermCondition();
				
		$NewFeeReceiptTermCondition->SetTermConditionMessageList($Clean['TermConditionMessageList']);
		$NewFeeReceiptTermCondition->SetCreateUserID($LoggedUser->GetUserID());

		if (!$NewFeeReceiptTermCondition->Save())
		{
			$NewRecordValidator->AttachTextError(ProcessErrors($NewFeeReceiptTermCondition->GetLastErrorCode()));
			$HasErrors = true;
			break;
		}
		
		header('location:fee_receipt_terms_condition.php?Mode=AS');
		exit;
	break;
}

require_once('../html_header.php');
?>
<title>Add Fee Receipt Terms & Condition</title>
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
                    <h1 class="page-header">Add Fee Receipt Terms & Conditions</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="AddBook" action="fee_receipt_terms_condition.php" method="post">
            	<div class="panel panel-default">
                    <div class="panel-heading">
                        <strong>Enter Fee Receipt Terms & Condition Details</strong>
                    </div>
                    <div class="panel-body">
<?php
						if ($HasErrors == true)
						{
							echo $NewRecordValidator->DisplayErrors();
						}
?>                    
                        <div id="TermConditionMessageContainer">
<?php 
						$Counter = 0;
						foreach ($Clean['TermConditionMessageList'] as $Key => $TermConditionMessage) 
						{
							$Counter++;
?>
							<div class="form-group TermConditionMessageContainer">
	                            <label for="TermConditionMessage<?php echo $Counter; ?>" class="col-lg-3 control-label">Term Condition Message</label>
	                            <div class="col-lg-7<?php echo isset($Errors[$Counter]) ? ' has-error' : ''; ?>">
	                            	<input class="form-control" type="text" maxlength="250" id="TermConditionMessage<?php echo $Counter; ?>" name="txtTermConditionMessage[<?php echo $Counter; ?>]" placeholder="Start typing...." value="<?php echo $TermConditionMessage ; ?>" />
	                            </div>
<?php
							if ($Counter == 1) 
							{
?>
								<div class="col-lg-2">
		                       		<button type="button" class="btn btn-primary" id="AddMore">Add More&nbsp;<i class="fa fa-plus"></i></button>
		                    	</div>
<?php								
							}
							else
							{
?>
								<div class="col-lg-2">
		                       		<button type="button" class="btn btn-sm btn-danger RemoveTermConditionMessageContainer">Remove</button>
		                    	</div>
<?php								
							}
?>	                            
	                        </div>
<?php
						}
?>
                        </div>
                        <div class="form-group">
	                        <div class="col-sm-offset-2 col-lg-10">
	                        	<input type="hidden" name="hdnProcess" value="1" />
								<button type="submit" class="btn btn-primary"><i class="fa fa-save"></i>&nbsp;Save</button>
	                        </div>
                      	</div>
                        
                    </div>
                </div>
            </form>
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

    $('#AddMore').click(function(){
        var Counter = 0;
        Counter = parseInt($('.TermConditionMessageContainer').length) + 1;

        var Data = '<div class="form-group TermConditionMessageContainer">'
            Data += '<label for="TermConditionMessage'+ Counter +'" class="col-lg-3 control-label">Term Condition Message</label>'
            Data += '<div class="col-lg-7">'
            Data += '<input class="form-control" type ="text" list="TermConditionMessageList'+ Counter +'" maxlength="250" id="TermConditionMessage'+ Counter +'" name="txtTermConditionMessage['+ Counter +']" placeholder="Start typing...." />'
            Data += '</div>'
            Data += '<div class="col-lg-2">'
            Data += '<button type="button" class="btn btn-sm btn-danger RemoveTermConditionMessageContainer">Remove</button>'
            Data += '</div>'
            Data += '</div>'
            
            $('#TermConditionMessageContainer').append(Data);
    });

    $('body').on('click', '.RemoveTermConditionMessageContainer', function(){
        if ($('.TermConditionMessageContainer').length <= 1)
        {
            alert('At least one term & condition is required, thus you cannot delete this term & condition.');
            return false;
        }
        
        $(this).closest('.TermConditionMessageContainer').remove();
    });
});

</script>
</body>
</html>
<?php
ob_start();
require_once('../classes/class.users.php');
require_once('../classes/class.validation.php');
require_once('../classes/class.authentication.php');

require_once('../classes/class.user_ip.php');

$Clean = array();

$Clean['UserName'] = '';
$Clean['Password'] = '';

$HasErrors = false;
$ExceptionErrorCode = 0;

$Clean['Process'] = 0;

if (isset($_POST['Process']))
{
	$Clean['Process'] = (int) $_POST['Process'];
}
switch ($Clean['Process'])
{
	case 1:
		if (isset($_POST['txtUserName']))
		{
			$Clean['UserName'] = strip_tags(trim((string) $_POST['txtUserName']));
		}
		
		if (isset($_POST['txtPassword']))
		{
			$Clean['Password'] = strip_tags(trim((string) $_POST['txtPassword']));
		}
		
		$RecordValidator = new Validator();
		
		$RecordValidator->ValidateStrings($Clean['UserName'], 'User Name must be supplied and must be valid and between 4 and 150 chars', 4, 150);
		$RecordValidator->ValidateStrings($Clean['Password'], 'Password must be supplied and must be between 4 and 12 chars', 4, 12);
		
		if ($RecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}
		
		$UserIP = '';
		$UserIP = UserIP::GetUserIP();
		
		//1. CHECK IF THE USER IS VALID //
		try
		{
			$AuthObject = new ApplicationAuthentication;
			$AuthObject->UserName = $Clean['UserName'];
			$AuthObject->Password = $Clean['Password'];
			$AuthObject->Login($UserIP);
		}
		
		// THIS CATCH BLOCK BUBBLES THE EXCEPTION TO THE BUILT IN 'Exception' CLASS IF THERE ARE ANY UNCAUGHT ERRORS //
		catch (ApplicationDBException $e)
		{
			$ExceptionErrorCode = $e->getCode();
			return false;
		}
		catch (ApplicationAuthException $e)
		{
			$ExceptionErrorCode = $e->getCode();
		}
		catch (Exception $e)
		{
			$ExceptionErrorCode= APP_ERROR_UNDEFINED_ERROR;
		}
		// END OF 1. //
		
		if ($ExceptionErrorCode === 0)
		{
			header('location:admin_default.php');
			exit;
		}
		else
		{
			$RecordValidator->AttachTextError(ProcessErrors($ExceptionErrorCode));
			$HasErrors = true;
		}
	break;	
}
?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Added</title>

    <!-- Bootstrap Core CSS -->
    <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">

    <!-- MetisMenu CSS -->
    <link href="vendor/metisMenu/metisMenu.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link href="dist/css/sb-admin-2.css" rel="stylesheet">

    <!-- Custom Fonts -->
    <link href="vendor/font-awesome/css/font-awesome.min.css" rel="stylesheet" type="text/css">

	<link rel="shortcut icon" href="/images/favicon.ico" />
    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
        <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
        <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->

</head>

<body>

    <div class="container">
        <div class="row">
            <div class="col-md-4 col-md-offset-4">
                <div class="login-panel panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title">Please Sign In</h3>
                    </div>
                    <div class="panel-body">
                        <form name="LoginForm" action="login.php" method="post">
                            <fieldset>
<?php
								if ($HasErrors == true)
								{
									echo $RecordValidator->DisplayErrors();
								}
?>                            
                                <div class="form-group">
                                    <input class="form-control" placeholder="User Name" name="txtUserName" type="text" maxlength="150" value="<?php echo $Clean['UserName']; ?>" autofocus>
                                </div>
                                <div class="form-group">
                                    <input class="form-control" placeholder="Password" name="txtPassword" type="password" maxlength="12" value="">
                                </div>                                
                                <!-- Change this to a button or input when using this as a form -->
                                <input type="hidden" name="Process" value="1" />
                                <button type="submit" class="btn btn-lg btn-success btn-block">Login</button>
                            </fieldset>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery -->
    <script src="vendor/jquery/jquery.min.js"></script>

    <!-- Bootstrap Core JavaScript -->
    <script src="vendor/bootstrap/js/bootstrap.min.js"></script>

    <!-- Metis Menu Plugin JavaScript -->
    <script src="vendor/metisMenu/metisMenu.min.js"></script>

    <!-- Custom Theme JavaScript -->
    <script src="dist/js/sb-admin-2.js"></script>

</body>

</html>

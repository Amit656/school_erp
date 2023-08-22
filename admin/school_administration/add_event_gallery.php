<?php
require_once('../../classes/class.users.php');
require_once('../../classes/class.validation.php');
require_once('../../classes/class.authentication.php');

require_once('../../classes/school_administration/class.event_gallery.php');

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

if ($LoggedUser->HasPermissionForTask(TASK_ADD_EVENT_GALLERY) !== true)
{
	header('location:../unauthorized_login_admin.php');
	exit;
}

$HasErrors = false;

$Clean = array();

$Clean['EventGallery'] = '';
$Clean['Description'] = '';

$Clean['Process'] = 0;

if (isset($_POST['hdnProcess']))
{
	$Clean['Process'] = (int) $_POST['hdnProcess'];
}
switch ($Clean['Process'])
{
	case 1:
		if (isset($_POST['txtEventGallery']))
		{
			$Clean['EventGallery'] = strip_tags(trim($_POST['txtEventGallery']));
		}

		if (isset($_POST['txtDescription']))
		{
			$Clean['Description'] = strip_tags(trim($_POST['txtDescription']));
		}
		
		$NewRecordValidator = new Validator();

		$NewRecordValidator->ValidateStrings($Clean['EventGallery'], 'The event gallery is required and should be between 4 and 30 characters.', 2, 60);
		$NewRecordValidator->ValidateStrings($Clean['Description'], 'The description is required and should be between 2 and 500 characters.', 4, 500);
		
		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}
		
		$NewEventGallery = new EventGallery();
			
		$NewEventGallery->SetName($Clean['EventGallery']);
		$NewEventGallery->SetDescription($Clean['Description']);
		$NewEventGallery->SetIsActive(1);
		
		$NewEventGallery->SetCreateUserID($LoggedUser->GetUserID());

		if ($NewEventGallery->RecordExists())
		{
			$NewRecordValidator->AttachTextError('The event gallery name you have added already exists.');
			$HasErrors = true;
			break;
		}

		if (!$NewEventGallery->Save())
		{
			$NewRecordValidator->AttachTextError(ProcessErrors($NewEventGallery->GetLastErrorCode()));
			$HasErrors = true;
			break;
		}
		
		header('location:event_gallery_list.php?Mode=ED');
		exit;
	break;
}

require_once('../html_header.php');
?>
<title>Add Event Gallery</title>
<link href="/admin/vendor/jquery-ui/jquery-ui.min.css" rel="stylesheet">
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
                    <h1 class="page-header">Add Event Gallery</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="AddEvent Gallery" action="add_event_gallery.php" method="post">
            	<div class="panel panel-default">
                    <div class="panel-heading">
                        Enter Event Gallery Details
                    </div>
                    <div class="panel-body">
<?php
						if ($HasErrors == true)
						{
							echo $NewRecordValidator->DisplayErrors();
						}
?>                    
						<div class="form-group">
                            <label for="EventGallery" class="col-lg-2 control-label">Event Gallery</label>
                            <div class="col-lg-4">
                            	<input class="form-control" type="text" maxlength="60" id="EventGallery" name="txtEventGallery" value="<?php echo $Clean['EventGallery']; ?>" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="Description" class="col-lg-2 control-label">Description</label>
                            <div class="col-lg-4">
                            	<textarea class="form-control" name="txtDescription" rows="3" id="Description" ><?php echo $Clean['Description'];?></textarea>
                            </div>
                        </div>
                        <div class="form-group">
                        <div class="col-sm-offset-2 col-lg-10">
                        	<input type="hidden" name="hdnProcess" value="1" />
							<button type="submit" class="btn btn-primary">Save</button>
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
</body>
</html>
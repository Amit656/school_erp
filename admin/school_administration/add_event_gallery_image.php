<?php
require_once('../../classes/class.users.php');
require_once('../../classes/class.validation.php');
require_once('../../classes/class.authentication.php');

require_once('../../classes/school_administration/class.event_gallery.php');
require_once('../../classes/school_administration/class.event_gallery_images.php');

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

if ($LoggedUser->HasPermissionForTask(TASK_ADD_EVENT_GALLERY_IMAGE) !== true)
{
	header('location:../unauthorized_login_admin.php');
	exit;
}

$acceptable_extensions = array('jpeg', 'jpg', 'png', 'gif');

$acceptable_mime_types = array(
    'image/jpeg',
    'image/jpg', 
    'image/png', 
    'image/gif' 
);

$EventGalleryList = array();
$EventGalleryList = EventGallery::GetActiveEventGallery();

$HasErrors = false;

$Clean = array();

$Clean['EventGalleryID'] = 0;
$Clean['EventGalleryID'] = key($EventGalleryList);

if (isset($_GET['EventGalleryID'])) 
{
    $Clean['EventGalleryID'] = (int) $_GET['EventGalleryID'];
}

if (!array_key_exists($Clean['EventGalleryID'], $EventGalleryList)) 
{
    header('location:../error.php');
    exit;
}

$Clean['UploadFile'] = array();
$Clean['Description'] = '';

$Clean['Process'] = 0;

if (isset($_POST['hdnProcess']))
{
	$Clean['Process'] = (int) $_POST['hdnProcess'];
}
switch ($Clean['Process'])
{
	case 1:
		if (isset($_POST['drdEventGallery']))
		{
			$Clean['EventGalleryID'] = (int) $_POST['drdEventGallery'];
		}
		
		if (isset($_POST['txtDescription']))
		{
			$Clean['Description'] = strip_tags(trim($_POST['txtDescription']));
		}

		if (isset($_FILES['fleEventImage']) && is_array($_FILES['fleEventImage']))
        {
            $Clean['UploadFile'] = $_FILES['fleEventImage'];
        }

		$NewRecordValidator = new Validator();

		$NewRecordValidator->ValidateInSelect($Clean['EventGalleryID'], $EventGalleryList, 'Unknown Error, Please try again.');
		$NewRecordValidator->ValidateStrings($Clean['Description'], 'The description is required and should be between 2 and 500 characters.', 4, 500);

        if ($Clean['UploadFile']['error'] == 4) 
        {
            $NewRecordValidator->AttachTextError('please select a image.');
            $HasErrors = true;
			break;
        }

        $FileName = '';
        $FileExtension = '';

        if ($Clean['UploadFile']['size'] > MAX_UPLOADED_FILE_SIZE || $Clean['UploadFile']['size'] <= 0) 
        {
            $NewRecordValidator->AttachTextError('File size cannot be greater then ' . (MAX_UPLOADED_FILE_SIZE / 1024 /1024) . ' MB.');
        }

        $FileExtension = strtolower(pathinfo($Clean['UploadFile']['name'], PATHINFO_EXTENSION));

        if (!in_array($Clean['UploadFile']['type'], $acceptable_mime_types) || !in_array($FileExtension, $acceptable_extensions))
        {
           $NewRecordValidator->AttachTextError('Only ' . implode(', ', $acceptable_extensions) . ' files are allowed.');
        }

        if (strlen($Clean['UploadFile']['name']) > MAX_UPLOADED_FILE_NAME_LENGTH)
        {
            $NewRecordValidator->AttachTextError('Uploaded file name cannot be greater then ' . MAX_UPLOADED_FILE_NAME_LENGTH . ' chars.');
        }

        $FileName = $Clean['UploadFile']['name'];

		if ($NewRecordValidator->HasNotifications())
		{
			$HasErrors = true;
			break;
		}

		$NewEventGalleryImage = new EventGalleryImage();

		$NewEventGalleryImage->SetEventGalleryID($Clean['EventGalleryID']);
		$NewEventGalleryImage->SetImageName($FileName);

    // Generate a Unique Name for the uploaded document
       $FileName = md5(uniqid(rand(), true) . $NewEventGalleryImage->GetEventGalleryImageID()) . '.' . $FileExtension;
    // var_dump($FileName);exit;
     
		$NewEventGalleryImage->SetDescription($Clean['Description']);
		
		$NewEventGalleryImage->SetCreateUserID($LoggedUser->GetUserID());

           if (!$NewEventGalleryImage->Save())
           {
            echo 'error|*****|Unknown error, please try again.';
            exit;
           }

		if ($FileName != '') 
        {
            $UniqueUserFileUploadDirectory = SITE_FS_PATH . '/site_images/';

            if (!is_dir($UniqueUserFileUploadDirectory))
            {
                mkdir($UniqueUserFileUploadDirectory);
            }
            
            // now move the uploaded file to application document folder
            move_uploaded_file($Clean['UploadFile']['tmp_name'], $UniqueUserFileUploadDirectory . $FileName);

            $NewEventGalleryImage->SetImageName($FileName);
        }

        if (!$NewEventGalleryImage->Save())
        {
            $NewRecordValidator->AttachTextError(ProcessErrors($NewEventGalleryImage->GetLastErrorCode()));
            $HasErrors = true;
            break;
        }
		

		header('location:view_event_images.php?Mode=ED&EventGalleryID='.$Clean['EventGalleryID']);
		exit;
	break;
}

require_once('../html_header.php');
?>
<title>Add Event Gallery Image</title>
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
                    <h1 class="page-header">Add Event Gallery Image</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
             <form class="form-horizontal" name="AddEvent Gallery" action="add_event_gallery_image.php" method="post" enctype="multipart/form-data">
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
                        <div class="row">
                            <div class="col-lg-8">
                                <div class="form-group">
                                    <label for="EventGallery" class="col-lg-3 control-label">Event Gallery</label>
                                    <div class="col-lg-8">
                                        <select class="form-control" id="EventGallery" name="drdEventGallery">
<?php
                                        foreach ($EventGalleryList as $EventGalleryID => $EventGalleryName) 
                                        {
?>
                                            <option <?php echo (($Clean['EventGalleryID'] == $EventGalleryID) ? 'selected="selected"' : '') ;?> value="<?php echo $EventGalleryID; ?>"><?php echo $EventGalleryName; ?></option>
<?php
                                        }
?>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="Description" class="col-lg-3 control-label">Description</label>
                                    <div class="col-lg-8">
                                        <textarea class="form-control" name="txtDescription" rows="3" id="Description" ><?php echo $Clean['Description'];?></textarea>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="Upload" class="col-lg-3 control-label">Upload Image</label>
                                    <div class="col-lg-8">
                                        <input class="form-control" type="file" name="fleEventImage" onchange="readURL(this);"/>
                                    </div>
                                </div>
                                <div class="form-group">
                                <div class="col-sm-offset-3 col-lg-10">
                                    <input type="hidden" name="hdnProcess" value="1" />
                                    <button type="submit" class="btn btn-primary">Save</button>
                                </div>
                              </div>
                            </div>
                            <div class="col-lg-4 EventImage" style="display: none;">
                                <img class="img-responsive center-block" src="" style="height: 180px; width: 180px;" />
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
<script type="text/javascript">
    function readURL(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();

            $('.EventImage').removeAttr('style');
            reader.onload = function (e) {
                $('.img-responsive').attr('src', e.target.result);
            }

            reader.readAsDataURL(input.files[0]);
        }
    }
</script>
</body>
</html>
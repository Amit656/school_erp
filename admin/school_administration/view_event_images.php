<?php
ob_start();
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once('../../classes/school_administration/class.event_gallery.php');
require_once('../../classes/school_administration/class.event_gallery_images.php');

require_once("../../classes/class.ui_helpers.php");

require_once("../../includes/helpers.inc.php");

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
	header('location:unauthorized_login_admin.php');
	exit;
}
catch (Exception $e)
{
	header('location:unauthorized_login_admin.php');
	exit;
}
// END OF 1. //

if ($LoggedUser->HasPermissionForTask(TASK_LIST_EVENT_GALLERY_IMAGE) !== true)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}

$Clean = array();

$Clean['EventGalleryID'] = 0;

if (isset($_GET['EventGalleryID']))
{
    $Clean['EventGalleryID'] = (int) $_GET['EventGalleryID'];
}
elseif (isset($_POST['hdnEventGalleryID']))
{
    $Clean['EventGalleryID'] = (int) $_POST['hdnEventGalleryID'];
}

if ($Clean['EventGalleryID'] <= 0)
{
    header('location:../error.php');
    exit;
} 

try
{
   $EventGalleryDetails = new EventGallery($Clean['EventGalleryID']);
}
catch (ApplicationDBException $e)
{
    header('location:../error.php');
    exit;
}
catch (Exception $e)
{
    header('location:../error.php');
    exit;
}

$AllEventImages = array();

$HasErrors = false;

// paging and sorting variables start here  //
$Clean['CurrentPage'] = 1;
$TotalPages = 0;

$Start = 0;
$Limit = 25;
// end of paging variables      //

$Clean['Process'] = 7;

if (isset($_GET['Process']))
{
	$Clean['Process'] = (int) $_GET['Process'];
}
switch ($Clean['Process'])
{
	case 5:
		if ($LoggedUser->HasPermissionForTask(TASK_DELETE_EVENT_GALLERY_IMAGE) !== true)
		{
			header('location:unauthorized_login_admin.php');
			exit;
		}
		
		if (isset($_GET['EventGalleryImageID']))
		{
			$Clean['EventGalleryImageID'] = (int) $_GET['EventGalleryImageID'];			
		}
		
		if ($Clean['EventGalleryImageID'] <= 0)
		{
			header('location:../error_page.php');
			exit;
		}

		try
		{
			$EventGalleryImageDelete = new EventGalleryImage($Clean['EventGalleryImageID']);
		}
		catch (ApplicationDBException $e)
		{
			header('location:../error_page.php');
			exit;
		}
		catch (Exception $e)
		{
			header('location:../error_page.php');
			exit;
		}

        unlink(SITE_FS_PATH . '/site_images/' . $EventGalleryImageDelete->GetImageName());

		$RecordValidator = new Validator();
				
		if (!$EventGalleryImageDelete->Remove())
		{
			$RecordValidator->AttachTextError(ProcessErrors($EventGalleryImageDelete->GetLastErrorCode()));
			$HasErrors = true;
			break;
		}
		
		header('location:view_event_images.php?EventGalleryID='. $Clean['EventGalleryID']);
	break;

    case 7:
        $Filters['EventGalleryID'] = $Clean['EventGalleryID'];
        //get records count
        EventGalleryImage::Search($TotalRecords, true, $Filters);
        if ($TotalRecords > 0)
        {
            // Paging and sorting calculations start here.
            $TotalPages = (($TotalRecords % $Limit) == 0) ? $TotalRecords / $Limit : floor($TotalRecords / $Limit) + 1;

            if (isset($_GET['CurrentPage']))
            {
                $Clean['CurrentPage'] = (int) $_GET['CurrentPage'];
            }

            if ($Clean['CurrentPage'] <= 0)
            {
                $Clean['CurrentPage'] = 1;
            }
            elseif ($Clean['CurrentPage'] > $TotalPages)
            {
                $Clean['CurrentPage'] = $TotalPages;
            }

            if ($Clean['CurrentPage'] > 1)
            {
                $Start = ($Clean['CurrentPage'] - 1) * $Limit;
            }
            // end of Paging and sorting calculations.
            // now get the actual  records
            $AllEventImages = EventGalleryImage::Search($TotalRecords, false, $Filters, $Start, $Limit);
        }
        break;
}

require_once('../html_header.php');
?>
<title>All Event Images</title>
<link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.5.2/jquery.fancybox.min.css">
<style type="text/css">
.ImageDiv 
{
border-width:1px;  
border-style:solid;
margin-left: 3px;
}
</style>
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
                    <h1 class="page-header">All <?php echo $EventGalleryDetails->GetName(); ?> Images</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
            <!-- /.row -->
            <div class="row">
                <div class="col-lg-12">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <strong><?php echo $EventGalleryDetails->GetName(); ?> Images</strong>
                        </div>
                        <!-- /.panel-heading -->
                        <div class="panel-body">
                            <div class="row">
                                <div class="col-lg-6">
                                    <div class="print-btn-container">
<?php
                                    if ($TotalPages > 1)
                                    {
                                        $AllParameters = $Filters;
                                        $AllParameters['Process'] = '7';

                                        echo UIHelpers::GetPager('view_event_images.php', $TotalPages, $Clean['CurrentPage'], $AllParameters);
                                    }
?>
                                    </div>
                                </div>
                                <div class="col-lg-6" style="text-align: right;">
                                    <div class="add-new-btn-container">
                                        <a href="add_event_gallery_image.php?EventGalleryID=<?php echo $Clean['EventGalleryID'];?>" class="btn btn-primary<?php echo $LoggedUser->HasPermissionForTask(TASK_DELETE_EVENT_GALLERY_IMAGE) === true ? '' : ' disabled'; ?>" role="button">Add New Image</a>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
<?php
                            if (count($AllEventImages) > 0) 
                            {   
                                foreach ($AllEventImages as $EventGalleryImageID => $EventImageDetails) 
                                {
?>
                                    <div class="col-lg-3">
                                        <a class="img-thumbnail" href="<?php echo SITE_HTTP_PATH;?>/site_images/<?php echo $EventImageDetails['ImageName'];?>" data-fancybox="images" data-caption="<?php echo $EventImageDetails['Description']; ?>">
                                            <img src="<?php echo SITE_HTTP_PATH;?>/site_images/<?php echo $EventImageDetails['ImageName'];?>" style="-webkit-user-select: none;background-position: 0px 0px, 10px 10px;background-size: 20px 20px;background-image:linear-gradient(45deg, #eee 25%, transparent 25%, transparent 75%, #eee 75%, #eee 100%),linear-gradient(45deg, #eee 25%, white 25%, white 75%, #eee 75%, #eee 100%);cursor: pointer;"  width="100%" height="150" />
                                        </a>
                                        <br>        
                                        <a href="view_event_images.php?EventGalleryID=<?php echo $Clean['EventGalleryID'];?>&amp;Process=5&amp;EventGalleryImageID=<?php echo $EventGalleryImageID;?>" class="btn btn-danger<?php /*echo $LoggedUser->HasPermissionForTask(TASK_ADD_ACADEMIC_CALENDER_EVENT) === true ? '' : ' disabled'; */?>" role="button" style="width: 100%;">Delete</a>
                                    </div>

<?php
                                }
                            }
                            else
                            {
?>
                                <div class="alert alert-info" role="alert">
                                    There is no images in rocords.
                                </div>
<?php
                            }
?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- /#page-wrapper -->

    </div>
    <!-- /#wrapper -->
<?php
require_once('../footer.php');
if (PrintMessage($_GET, $Message))
{
?>
    <script type="text/javascript">
        alert('<?php echo $Message; ?>');
    </script>
<?php
}
?>
<!-- JavaScript To Print A Report -->
<script src="../js/print-report.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.5.2/jquery.fancybox.min.js"></script>
<script type="text/javascript">
$(document).ready(function()
{
    $('[data-fancybox="images"]').fancybox({
      buttons : [ 
        'slideShow',
        'zoom',
        'fullScreen',
        'close'
      ],
      thumbs : {
        autoStart : true
      }
    });
});
</script>
</body>
</html>
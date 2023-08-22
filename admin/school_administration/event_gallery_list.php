<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once('../../classes/school_administration/class.event_gallery.php');

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

if ($LoggedUser->HasPermissionForTask(TASK_LIST_EVENT_GALLERY) !== true)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}

$Filters = array();

$HasErrors = false;
$TotalRecords = 0;

$Clean = array();

$Clean['EventGalleryID'] = 0;

// paging and sorting variables start here  //
$Clean['CurrentPage'] = 1;
$TotalPages = 0;

$Start = 0;
$Limit = 10;
// end of paging variables      //

$Clean['Process'] = 0;

if (isset($_GET['Process']))
{
	$Clean['Process'] = (int) $_GET['Process'];
}
switch ($Clean['Process'])
{
	case 5:
		if ($LoggedUser->HasPermissionForTask(TASK_DELETE_EVENT_GALLERY) !== true)
		{
			header('location:unauthorized_login_admin.php');
			exit;
		}
		
		if (isset($_GET['EventGalleryID']))
		{
			$Clean['EventGalleryID'] = (int) $_GET['EventGalleryID'];			
		}
		
		if ($Clean['EventGalleryID'] <= 0)
		{
			header('location:../error_page.php');
			exit;
		}						

		try
		{
			$EventGalleryDelete = new EventGallery($Clean['EventGalleryID']);
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
		
		$RecordValidator = new Validator();
				
		if (!$EventGalleryDelete->Remove())
		{
			$RecordValidator->AttachTextError(ProcessErrors($EventGalleryDelete->GetLastErrorCode()));
			$HasErrors = true;
			break;
		}
		
		header('location:event_gallery_list.php?Mode=DD&Process=7');
	break;
}

$AllEventGallery = array();
$AllEventGallery = EventGallery::Search($TotalRecords, false, $Filters, $Start, $Limit);

require_once('../html_header.php');
?>
<title>All Event Gallery</title>
<link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.5.2/jquery.fancybox.min.css">
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
                    <h1 class="page-header">All Event Galleries</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
            <!-- /.row -->
            <div class="row">
                <div class="col-lg-12">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <strong>Total Records Returned: <?php echo count($AllEventGallery); ?></strong>
                        </div>
                        <!-- /.panel-heading -->
                        <div class="panel-body">
                            <div>
                                <div id='calendar'></div>
                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="add-new-btn-container"><a href="add_event_gallery.php" class="btn btn-primary<?php echo $LoggedUser->HasPermissionForTask(TASK_ADD_EVENT_GALLERY) === true ? '' : ' disabled'; ?>" role="button">Add New Event</a></div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="print-btn-container"><button id="PrintButton" type="submit" class="btn btn-primary">Print</button></div>
                                    </div>
                                </div>
<?php
                            if ($HasErrors == true)
                            {
                                echo $RecordValidator->DisplayErrorsInTable();
                            }

?>
                                <div class="row" id="RecordTableHeading">
                                    <div class="col-lg-12">
                                    	<div class="report-heading-container"><strong>All Event Galleries on <?php echo date('d-m-Y h:i A'); ?></strong></div>
                                    </div>
								</div>
                                <div class="row" id="RecordTable">
                                    <div class="col-lg-12">
                                        <table width="100%" class="table table-striped table-bordered table-hover">
                                            <thead>
                                                <tr>
                                                    <th>S. No</th>
                                                    <th>Event Gallery Name</th>
                                                    <th>Description</th>
                                                    <th>Is Active</th>
                                                    <th>Create By User</th>
                                                    <th>Create Date</th>
                                                    <th class="print-hidden">Operations</th>
                                                </tr>
                                            </thead>
                                            <tbody>
    <?php
                                    if (is_array($AllEventGallery) && count($AllEventGallery) > 0)
                                    {
                                        $Counter = 0;
                                        foreach ($AllEventGallery as $EventGalleryID => $AllEventGalleryDetails)
                                        {
    ?>
                                            <tr>
                                                <td><?php echo ++$Counter; ?></td>
                                                <td><?php echo $AllEventGalleryDetails['Name']; ?></td>
                                                <td><?php echo $AllEventGalleryDetails['Description']; ?></td>
                                                <td><?php echo (($AllEventGalleryDetails['IsActive']) ? 'Yes' : 'No'); ?></td>
                                                <td><?php echo $AllEventGalleryDetails['CreateUserName']; ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($AllEventGalleryDetails['CreateDate'])); ?></td>

                                                <td class="print-hidden">
<?php
                                                if ($LoggedUser->HasPermissionForTask(TASK_EDIT_EVENT_GALLERY) === true)
                                                {
                                                    echo '<a href="edit_event_gallery.php?Process=2&amp;EventGalleryID='.$EventGalleryID.'">Edit</a>';
                                                }
                                                else
                                                {
                                                    echo 'Edit';
                                                }

                                                echo '&nbsp;|&nbsp;';

                                                if ($LoggedUser->HasPermissionForTask(TASK_DELETE_EVENT_GALLERY) === true)
                                                {
                                                    echo '<a class="delete-record" href="event_gallery_list.php?Process=5&amp;EventGalleryID='.$EventGalleryID.'">Delete</a>';
                                                }
                                                else
                                                {
                                                    echo 'Delete';
                                                }
?>
                                                </td> 
                                            </tr>
<?php
                                        }
                                    }
?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <!-- /.table-responsive -->
                        </div>
                        <!-- /.panel-body -->
                    </div>
                    <!-- /.panel -->
                </div>
                <!-- /.col-lg-12 -->
            </div>
            <div class="row">
                <div class="col-lg-12">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <strong>View Event Gallery</strong>
                        </div>
                        <!-- /.panel-heading -->
                        <div class="panel-body">
                            <div class="row">
<?php
                            foreach ($AllEventGallery as $EventGalleryID => $AllEventGalleryDetails) 
                            {
?>
                                <div class="col-lg-3 text-center">
                                    <a href="view_event_images.php?EventGalleryID=<?php echo $EventGalleryID;?>" target="_blank" style="text-decoration: none; color: black;">
                                        &nbsp;<?php echo $AllEventGalleryDetails['Name']; ?><br/>
                                    <img src="<?php echo SITE_HTTP_PATH;?>/images/image-foder-icon.png" style="width: 100; height:100px;">
                                    </a>
                                    
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
        'share',
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
<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once("../../classes/class.ui_helpers.php");

require_once("../../classes/school_administration/class.classes.php");

require_once("../../classes/academic_supervision/class.chapters.php");
require_once("../../classes/academic_supervision/class.chapter_topics.php");

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

if ($LoggedUser->HasPermissionForTask(TASK_LIST_CHAPTER_TOPIC) !== true)
{
    header('location:/admin/unauthorized_login_admin.php');
    exit;
}

$Filters = array();

$ClassList =  array();
$ClassList = AddedClass::GetActiveClasses();

$ClassSubjectsList = array();
$ChaptersList = array();
$ChapterTopicsList = array();

$RecordDeletedSuccessfully = false;
$HasErrors = false;
$TotalRecords = 0;

$Clean = array();
$Clean['Process'] = 0;

$Clean['ClassID'] = 0;
$Clean['ClassSubjectID'] = 0;
$Clean['ChapterID'] = 0;

$Clean['ActiveStatus'] = 0;

// paging and sorting variables start here  //
$Clean['AllRecords'] = '';
$Clean['CurrentPage'] = 1;
$TotalPages = 0;

$Start = 0;
$Limit = GLOBAL_SITE_PAGGING;
// end of paging variables//

if (isset($_GET['hdnProcess']))
{
    $Clean['Process'] = (int) $_GET['hdnProcess'];
}
elseif (isset($_GET['Process']))
{
    $Clean['Process'] = (int) $_GET['Process'];
}
switch ($Clean['Process'])
{
    case 5:
        if ($LoggedUser->HasPermissionForTask(TASK_DELETE_CHAPTER_TOPIC) !== true)
        {
            header('location:/admin/unauthorized_login_admin.php');
            exit;
        }
        
        if (isset($_GET['ChapterTopicID']))
        {
            $Clean['ChapterTopicID'] = (int) $_GET['ChapterTopicID'];           
        }
        
        if ($Clean['ChapterTopicID'] <= 0)
        {
            header('location:../error_page.php');
            exit;
        }                       
            
        try
        {
            $ChapterTopicToDelete = new ChapterTopic($Clean['ChapterTopicID']);
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
        
        $SearchValidator = new Validator();
        
        if ($ChapterTopicToDelete->CheckDependencies())
        {
            $SearchValidator->AttachTextError('This topic cannot be deleted. There are dependent records for this topic.');
            $HasErrors = true;
            break;
        }
                
        if (!$ChapterTopicToDelete->Remove())
        {
            $SearchValidator->AttachTextError(ProcessErrors($ChapterTopicToDelete->GetLastErrorCode()));
            $HasErrors = true;
            break;
        }
        
        $RecordDeletedSuccessfully = true;
    break;

    case 7:        
        if (isset($_GET['drdClass']))
        {
            $Clean['ClassID'] = (int) $_GET['drdClass'];
        }
        elseif (isset($_GET['ClassID']))
        {
            $Clean['ClassID'] = (int) $_GET['ClassID'];
        }

        if (isset($_GET['drdClassSubject']))
        {
            $Clean['ClassSubjectID'] = (int) $_GET['drdClassSubject'];
        }
        elseif (isset($_GET['ClassSubjectID']))
        {
            $Clean['ClassSubjectID'] = (int) $_GET['ClassSubjectID'];
        }

        if (isset($_GET['drdChapter']))
        {
            $Clean['ChapterID'] = (int) $_GET['drdChapter'];
        }
        elseif (isset($_GET['ChapterID']))
        {
            $Clean['ChapterID'] = (int) $_GET['ChapterID'];
        }

        if (isset($_GET['optActiveStatus']))
        {
            $Clean['ActiveStatus'] = (int) $_GET['optActiveStatus'];
        }
        elseif (isset($_GET['ActiveStatus']))
        {
            $Clean['ActiveStatus'] = (int) $_GET['ActiveStatus'];
        }

        $SearchValidator = new Validator();

        if ($Clean['ClassID'] != 0)
        {          
            if ($SearchValidator->ValidateInSelect($Clean['ClassID'], $ClassList, 'Unknown error, please try again.')) 
            {
                $SelectedAddedClass = New AddedClass($Clean['ClassID']);
                $SelectedAddedClass->FillAssignedSubjects();

                $ClassSubjectsList = $SelectedAddedClass->GetAssignedSubjects();

                if ($Clean['ClassSubjectID'] != 0)
                {
                    if ($SearchValidator->ValidateInSelect($Clean['ClassSubjectID'], $ClassSubjectsList, 'Unknown error, please try again.')) 
                    {
                        $ChaptersList = Chapter::GetChapterByClassSubject($Clean['ClassSubjectID']);

                        if ($Clean['ChapterID'] != 0)
                        {
                            $SearchValidator->ValidateInSelect($Clean['ChapterID'], $ChaptersList, 'Unknown error, please try again.');
                        }
                    }
                }
            }
        }

        if ($Clean['ActiveStatus'] < 0 || $Clean['ActiveStatus'] > 2)
        {
            $SearchValidator->AttachTextError('Unknown error, please try again.');
        }
        
        if ($SearchValidator->HasNotifications())
        {
            $HasErrors = true;
            break;
        }

        //set record filters
        $Filters['ClassID'] = $Clean['ClassID'];
        $Filters['ClassSubjectID'] = $Clean['ClassSubjectID'];
        $Filters['ChapterID'] = $Clean['ChapterID'];
        $Filters['ActiveStatus'] = $Clean['ActiveStatus'];

        //get records count
        ChapterTopic::SearchChapterTopics($TotalRecords, true, $Filters);

        if ($TotalRecords > 0)
        {
            // Paging and sorting calculations start here.
            $TotalPages = (($TotalRecords % $Limit) == 0) ? $TotalRecords / $Limit : floor($TotalRecords / $Limit) + 1;

            if (isset($_GET['CurrentPage']))
            {
                $Clean['CurrentPage'] = (int) $_GET['CurrentPage'];
            }
            
            if (isset($_GET['AllRecords']))
            {
                $Clean['AllRecords'] = (string) $_GET['AllRecords'];
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
            if ($Clean['AllRecords'] == 'All') 
            {
                $ChapterTopicsList = ChapterTopic::SearchChapterTopics($TotalRecords, false, $Filters, 0, $TotalRecords);
            }
            else
            {
                $ChapterTopicsList = ChapterTopic::SearchChapterTopics($TotalRecords, false, $Filters, $Start, $Limit);
            }
            
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
<title>Chapter Topic Report</title>
<!-- DataTables CSS -->
<link href="../vendor/datatables-plugins/dataTables.bootstrap.css" rel="stylesheet">

<!-- DataTables Responsive CSS -->
<link href="../vendor/datatables-responsive/dataTables.responsive.css" rel="stylesheet">
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
                    <h1 class="page-header">Chapter Topic Report</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
            <form class="form-horizontal" name="frmChapterTopicReport" action="chapter_topics_report.php" method="get">
                <div class="panel panel-default" id="accordion">
                    <div class="panel-heading">
                        <strong><a data-toggle="collapse" data-parent="#accordion" href="#collapseOne">Filters</a></strong>
                    </div>
                    <div id="collapseOne" class="panel-collapse collapse in">
                        <div class="panel-body">
<?php
                            if ($HasErrors == true)
                            {
                                echo $SearchValidator->DisplayErrors();
                            }
                            else if ($LandingPageMode == 'AS')
                            {
                                echo '<div class="alert alert-success">Record saved successfully.</div>';
                            }
                            else if ($RecordDeletedSuccessfully == true)
                            {
                                echo '<div class="alert alert-danger">Record deleted successfully.</div>';
                            }
                            else if ($LandingPageMode == 'UD')
                            {
                                echo '<div class="alert alert-success">Record updated successfully.</div>';
                            }
?>
                            <div class="form-group">
                                <label for="Class" class="col-lg-2 control-label">Class</label>
                                <div class="col-lg-4">
                                    <select class="form-control"  name="drdClass" id="Class">
                                        <option value="0" >-- All Class --</option>
<?php
                                        if (is_array($ClassList) && count($ClassList) > 0)
                                        {
                                            foreach ($ClassList as $ClassID => $ClassName)
                                            {
?>
                                                <option <?php echo ($ClassID == $Clean['ClassID'] ? 'selected="selected"' : ''); ?> value="<?php echo $ClassID; ?>"><?php echo $ClassName; ?></option>
<?php
                                            }
                                        }
?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="ClassSubject" class="col-lg-2 control-label">Subject</label>
                                <div class="col-lg-4">
                                    <select class="form-control" name="drdClassSubject" id="ClassSubject">
                                            <option value="0" >-- All Subject --</option>
<?php
                                            if (is_array($ClassSubjectsList) && count($ClassSubjectsList) > 0)
                                            {
                                                foreach ($ClassSubjectsList as $ClassSubjectID => $ClassSubjectDetail) 
                                                {
                                                    echo '<option ' . ($Clean['ClassSubjectID'] == $ClassSubjectID ? 'selected="selected"' : '') . ' value="' . $ClassSubjectID . '">' . $ClassSubjectDetail['Subject'] . '</option>' ;
                                                }
                                            }
?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="Chapter" class="col-lg-2 control-label">Chapter</label>
                                <div class="col-lg-4">
                                    <select class="form-control" name="drdChapter" id="Chapter">
                                            <option value="0" >-- All Chapter --</option>
<?php
                                            if (is_array($ChaptersList) && count($ChaptersList) > 0)
                                            {
                                                foreach ($ChaptersList as $ChapterID => $ChapterName) 
                                                {
                                                    echo '<option ' . ($Clean['ChapterID'] == $ChapterID ? 'selected="selected"' : '') . ' value="' . $ChapterID . '">' . $ChapterName . '</option>' ;
                                                }
                                            }
?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="ActiveStatus" class="col-lg-2 control-label">Status</label>
                                <div class="col-lg-6">
                                    <label class="radio-inline">
                                        <input type="radio" <?php echo (($Clean['ActiveStatus'] == 0) ? 'checked="checked"' : ''); ?> name="optActiveStatus" id="ActiveStatus" value="0" checked>All Topic
                                    </label>
                                    <label class="radio-inline">
                                        <input type="radio" <?php echo (($Clean['ActiveStatus'] == 1) ? 'checked="checked"' : ''); ?> name="optActiveStatus" id="ActiveStatus" value="1">Active Topic
                                    </label>
                                    <label class="radio-inline">
                                        <input type="radio" <?php echo (($Clean['ActiveStatus'] == 2) ? 'checked="checked"' : ''); ?> name="optActiveStatus" id="ActiveStatus" value="2">In-Active Topic
                                    </label>
                                </div>
                            </div>                    
                            <div class="form-group">
                                <div class="col-lg-offset-2 col-lg-10">
                                    <input type="hidden" name="hdnProcess" value="7" />
                                    <button type="submit" class="btn btn-primary"><i class="fa fa-search"></i>&nbsp;Search</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    </div>
            </form>
            <!-- /.row -->
<?php
        if ($Clean['Process'] == 7 && $HasErrors == false)
        {
            $ReportHeaderText = '';

            if ($Clean['ClassID'] != 0)
            {
                $ReportHeaderText .= ' Class: ' . $ClassList[$Clean['ClassID']] . ',';
            }

            if ($Clean['ClassSubjectID'] != 0)
            {
                $ReportHeaderText .= ' Subject: ' . $ClassSubjectsList[$Clean['ClassSubjectID']]['Subject'] . ',';
            }

            if ($Clean['ChapterID'] != 0)
            {
                $ReportHeaderText .= ' Chapter: ' . $ChaptersList[$Clean['ChapterID']] . ',';
            }

            if ($Clean['ActiveStatus'] == 1)
            {
                $ReportHeaderText .= ' Status: Active,';
            }
            else if ($Clean['ActiveStatus'] == 2)
            {
                $ReportHeaderText .= ' Status: In-Active,';
            }

            if ($ReportHeaderText != '')
            {
                $ReportHeaderText = ' for' . rtrim($ReportHeaderText, ',');
            }
?>
            <div class="row">
                <div class="col-lg-12">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <strong>Total Records Returned: <?php echo $TotalRecords; ?></strong>
                        </div>
                        <!-- /.panel-heading -->
                        <div class="panel-body">
                            <div>
                                <div class="row">
                                    <div class="col-lg-6">
<?php
                                    if ($TotalPages > 1)
                                    {
                                        $AllParameters = array('Process' => '7', 'ClassID' => $Clean['ClassID'], 'ClassSubjectID' => $Clean['ClassSubjectID'], 'ChapterID' => $Clean['ChapterID'], 'ActiveStatus' => $Clean['ActiveStatus']);
                                        echo UIHelpers::GetPager('chapter_topics_report.php', $TotalPages, $Clean['CurrentPage'], $AllParameters);
                                    }
?>                                        
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="print-btn-container"><button id="PrintButton" type="submit" class="btn btn-primary">Print</button></div>
                                    </div>
                                </div>
                                <div class="row" id="RecordTableHeading">
                                    <div class="col-lg-12">
                                        <div class="report-heading-container"><strong>Chapter Topic Report on <?php echo date('d-m-Y h:i A') . $ReportHeaderText; ?></strong></div>
                                    </div>
                                </div>
                                <div class="row" id="RecordTable">
                                    <div class="col-lg-12">
                                        <table width="100%" class="table table-striped table-bordered table-hover" id="DataTableRecords">
                                            <thead>
                                                <tr>
                                                    <th>S.No</th>
                                                    <th>Topic Name</th>
                                                    <th>Chapter Name</th>
                                                    <th>Subject Name</th>
                                                    <th>Class</th>
                                                    <th>Expected Classes</th>
                                                    <th>Priority</th>
                                                    <th>Is Active</th>
                                                    <th>Create User</th>
                                                    <th>Create Date</th>
                                                    <th class="print-hidden">Operations</th>
                                                </tr>
                                            </thead>
                                            <tbody>
<?php
                                    if (is_array($ChapterTopicsList) && count($ChapterTopicsList) > 0)
                                    {
                                        $Counter = $Start;
                                        foreach ($ChapterTopicsList as $ChapterTopicID => $ChapterTopicDetails)
                                        {
?>
                                                <tr>
                                                    <td><?php echo ++$Counter; ?></td>
                                                    <td><?php echo $ChapterTopicDetails['TopicName']; ?></td>
                                                    <td><?php echo $ChapterTopicDetails['ChapterName']; ?></td>
                                                    <td><?php echo $ChapterTopicDetails['SubjectName']; ?></td>
                                                    <td><?php echo $ChapterTopicDetails['ClassName']; ?></td>
                                                    <td><?php echo $ChapterTopicDetails['ExpectedClasses']; ?></td>
                                                    <td><?php echo $ChapterTopicDetails['Priority']; ?></td>
                                                    <td><?php echo (($ChapterTopicDetails['IsActive']) ? 'Yes' : 'No'); ?></td>
                                                    <td><?php echo $ChapterTopicDetails['CreateUserName']; ?></td>
                                                    <td><?php echo date('d/m/Y', strtotime($ChapterTopicDetails['CreateDate'])); ?></td>
                                                    <td class="print-hidden">
<?php
                                                    if ($LoggedUser->HasPermissionForTask(TASK_EDIT_CHAPTER_TOPIC) === true)
                                                    {
                                                        echo '<a href="edit_chapter_topic.php?Process=2&amp;ChapterTopicID=' . $ChapterTopicID . '">Edit</a>';
                                                    }
                                                    else
                                                    {
                                                        echo 'Edit';
                                                    }

                                                    echo '&nbsp;|&nbsp;';

                                                    if ($LoggedUser->HasPermissionForTask(TASK_DELETE_CHAPTER_TOPIC) === true)
                                                    {
                                                        echo '<a href="chapter_topics_report.php?Process=5&amp;ChapterTopicID=' . $ChapterTopicID . '" class="delete-record">Delete</a>';
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
<?php
        }
?>            
        </div>
        <!-- /#page-wrapper -->

    </div>
    <!-- /#wrapper -->
<?php
require_once('../footer.php');
?>
<!-- DataTables JavaScript -->
<script src="../vendor/datatables/js/jquery.dataTables.min.js"></script>
<script src="../vendor/datatables-plugins/dataTables.bootstrap.min.js"></script>
<script src="../vendor/datatables-responsive/dataTables.responsive.js"></script>   
<script type="text/javascript">
$(document).ready(function() {
    $("body").on('click', '.delete-record', function()
    {   
        if (!confirm("Are you sure you want to delete this topic?"))
        {
            return false;
        }
    });

    $('#DataTableRecords').DataTable({
        responsive: true,
        bPaginate: false,
        bSort: false,
        searching: false, 
        info: false
    });

    $('#Class').change(function(){

        var ClassID = parseInt($(this).val());
        
        if (ClassID <= 0)
        {
            $('#ClassSubject').html('<option value="0" >-- All Subject --</option>');
            $('#Chapter').html('<option value="0" >-- All Chapter --</option>');
            return;
        }
        
        $.post("/xhttp_calls/get_subjects_by_class.php", {SelectedClassID:ClassID}, function(data)
        {
            ResultArray = data.split("|*****|");
            
            if (ResultArray[0] == 'error')
            {
                alert (ResultArray[1]);
                return false;
            }
            else
            {
                $('#ClassSubject').html('<option value="0" >-- All Subject --</option>' + ResultArray[1]);
            }
        });
    });

    $('#ClassSubject').change(function(){

        var ClassSubjectID = parseInt($(this).val());
        
        if (ClassSubjectID <= 0)
        {
            return;
        }
        
        $.post("/xhttp_calls/get_chapter_by_class_subject.php", {SelectedClassSubjectID:ClassSubjectID}, function(data)
        {
            ResultArray = data.split("|*****|");
            
            if (ResultArray[0] == 'error')
            {
                alert (ResultArray[1]);
                $('#Chapter').html('<option value="0" >-- All Chapter --</option>');
                return false;
            }
            else
            {
                $('#Chapter').html('<option value="0" >-- All Chapter --</option>' + ResultArray[1]);
            }
        });
    });
        
});
</script>
<!-- JavaScript To Print A Report -->
<script src="js/print-report.js"></script>
<script src="/admin/js/print-report.js"></script>
</body>
</html>
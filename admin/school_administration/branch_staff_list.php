<?php
require_once("../../classes/class.users.php");
require_once('../../classes/class.validation.php');
require_once("../../classes/class.authentication.php");

require_once("../../classes/school_administration/class.branch_staff.php");

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
    header('location:../unauthorized_login_admin.php');
    exit;
}
catch (Exception $e)
{
    header('location:../unauthorized_login_admin.php');
    exit;
}
// END OF 1. //

if ($LoggedUser->HasPermissionForTask(TASK_LIST_BRANCH_STAFF) !== true)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}

$GenderList = array('Male' => 'Male','Female' => 'Female');

$StaffCategoryList = array('Teaching' => 'Teaching', 'NonTeaching' => 'Non Teaching', 'Management' => 'Management');

$Filters = array();

$Filters['Genders'] = '';
$Filters['StaffCategories'] = '';
$Filters['TeacherName'] = '';
$Filters['ActiveStatus'] = '';

$HasErrors = false;
$RecordDeletedSuccessfully = false;

$AllBranchStaff = array();

$Clean = array();

$Clean['Process'] = 0;

$Clean['BranchStaffID'] = 0;

$Clean['Genders'] = '';
$Clean['StaffCategory'] = '';
$Clean['TeacherName'] = '';

$Clean['ActiveStatus'] = 1;

// paging and sorting variables start here  //
$Clean['AllRecords'] = '';
$Clean['CurrentPage'] = 1;
$TotalPages = 0;

$Start = 0;
$Limit = 100;
// end of paging variables //

if (isset($_GET['hdnProcess']))
{
    $Clean['Process'] = (int) $_GET['hdnProcess'];
}
else if (isset($_GET['Process']))
{
    $Clean['Process'] = (int) $_GET['Process'];
}
switch ($Clean['Process'])
{
    case 11:
        if ($LoggedUser->HasPermissionForTask(TASK_DELETE_BRANCH_STAFF) !== true)
        {
            header('location:unauthorized_login_admin.php');
            exit;
        }
        
        if (isset($_GET['BranchStaffID']))
        {
            $Clean['BranchStaffID'] = (int) $_GET['BranchStaffID'];
        }
        
        if ($Clean['BranchStaffID'] <= 0)
        {
            header('location:/admin/error.php');
            exit;
        }                       
            
        try
        {
            $BranchStaffToDelete = new BranchStaff($Clean['BranchStaffID']);
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
        
        $RecordValidator = new Validator();
        
        /*if ($BranchStaffToDelete->CheckDependencies())
        {
            $RecordValidator->AttachTextError('This branch staff cannot be deleted. There are dependent records for this Branch Staff.');
            $HasErrors = true;
            break;
        }*/
                
        if (!$BranchStaffToDelete->InActiveBranchStaff())
        {
            $RecordValidator->AttachTextError(ProcessErrors($BranchStaffToDelete->GetLastErrorCode()));
            $HasErrors = true;
            break;
        }
        
        header('location:branch_staff_list.php?Mode=DD');
        break;
    break;
    
    case 5:
        if ($LoggedUser->HasPermissionForTask(TASK_DELETE_BRANCH_STAFF) !== true)
        {
            header('location:unauthorized_login_admin.php');
            exit;
        }
        
        if (isset($_GET['BranchStaffID']))
        {
            $Clean['BranchStaffID'] = (int) $_GET['BranchStaffID'];
        }
        
        if ($Clean['BranchStaffID'] <= 0)
        {
            header('location:/admin/error.php');
            exit;
        }                       
            
        try
        {
            $BranchStaffToDelete = new BranchStaff($Clean['BranchStaffID']);
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
        
        $RecordValidator = new Validator();
        
        if ($BranchStaffToDelete->CheckDependencies())
        {
            $RecordValidator->AttachTextError('This branch staff cannot be deleted. There are dependent records for this Branch Staff.');
            $HasErrors = true;
            break;
        }
                
        if (!$BranchStaffToDelete->Remove())
        {
            $RecordValidator->AttachTextError(ProcessErrors($BranchStaffToDelete->GetLastErrorCode()));
            $HasErrors = true;
            break;
        }
        
        header('location:branch_staff_list.php?Mode=DD');
        break;

    case 7:
        if(isset($_GET['drdGender']))
        {
            $Clean['Genders'] = strip_tags($_GET['drdGender']);
        }
        
        if (isset($_GET['drdStaffCategory']))
        {
            $Clean['StaffCategory'] = strip_tags(trim($_GET['drdStaffCategory']));
        }

        if (isset($_GET['txtTeacherName']))
        {
            $Clean['TeacherName'] = strip_tags(trim($_GET['txtTeacherName']));
        }

        if (isset($_GET['rbdActiveStatus']))
        {
            $Clean['ActiveStatus'] = (int) $_GET['rbdActiveStatus'];
        }
        
        $RecordValidator  = new Validator();

        if ($Clean['Genders'] > 0)
        {
            $RecordValidator->ValidateInSelect($Clean['Genders'], $GenderList, 'Unknown error, please try again.');
        }

        if ($Clean['StaffCategory'] != '')
        {
            $RecordValidator ->ValidateInSelect($Clean['StaffCategory'], $StaffCategoryList, 'Unknown error, please try again.');
        }

        if ($Clean['TeacherName'] != '')
        {
            $RecordValidator ->ValidateStrings($Clean['TeacherName'], 'Name should be between 1 and 20 characters.', 1, 20);
        }

        if ($Clean['ActiveStatus'] != 1 && $Clean['ActiveStatus'] != 0)
        {
            $RecordValidator->AttachTextError('Unknown Error.');
        }

        if ($RecordValidator ->HasNotifications())
        {
            $HasErrors = true;
            break;
        }

        //set record filters
        $Filters['Genders'] = $Clean['Genders'];
        $Filters['StaffCategories'] = $Clean['StaffCategory'];
        $Filters['TeacherName'] = $Clean['TeacherName'];
        $Filters['ActiveStatus'] = $Clean['ActiveStatus'];
 
        //get records count
        BranchStaff::SearchBranchStaff($TotalRecords, true, $Filters);
  
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
                $AllBranchStaff = BranchStaff::SearchBranchStaff($TotalRecords, false, $Filters, 0, $TotalRecords);
            }
            
            else
            {
                $AllBranchStaff = BranchStaff::SearchBranchStaff($TotalRecords, false, $Filters, $Start, $Limit);
            }
            
        }
        break;
}

require_once('../html_header.php');
?>
<title>Branch Staff List</title>
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
                    <h1 class="page-header">Branch Staff List</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
            <!-- /.row -->
             <div class="row">
                <div class="col-lg-12">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <strong>Filter All Branch Staff</strong>
                        </div>
                         <div class="panel-body">
                            <div>
<?php
                            if ($HasErrors == true)
                            {
                                echo $RecordValidator->DisplayErrorsInTable();
                                echo '<br>';
                            }
?>
                                <div class="row" >
                                    <div class="col-lg-12">
                                         <form class="form-horizontal" name="FilterBranchStaff" id="FilterBranchStaff" action="branch_staff_list.php" method="get">
                                            <div class="form-group">
                                                <label for="Gender" class="col-lg-2 control-label">Gender</label>
                                                <div class="col-lg-4">
                                                    <select class="form-control" id="drdGender" name="drdGender">
                                                        <option value="0">Select</option>
<?php
                                                    foreach ($GenderList as $Gender => $GenderValue)
													{
                                                        echo '<option' . (array_key_exists($Clean['Genders'], $GenderList) ? ' selected="selected"' : '') . ' value="' . $Gender . '">' . $GenderValue . '</option>';
                                                    }
?>
                                                    </select>
                                                 </div>
                                                 <label for="StaffCategories" class="col-lg-2 control-label">Staff Category</label>
                                                <div class="col-lg-4">
                                                    <select class="form-control" id="drdStaffCategories" name="drdStaffCategory">
                                                        <option value="">Select</option>
<?php
                                                        foreach ($StaffCategoryList as $StaffCategory => $StaffCategoryName) 
                                                        {
                                                            echo '<option' . ((array_key_exists($Clean['StaffCategory'], $StaffCategoryList)) ? ' selected="selected"' : '') . ' value="' . $StaffCategory . '">' . $StaffCategoryName . '</option>';
                                                        }
?>
                                                    </select>
                                                </div>   
                                            </div>
                                            <div class="form-group">
                                                <label for="SearchByName" class="col-lg-2 control-label">Branch Staff Name</label>
                                                <div class="col-lg-4">
                                                    <input type="text" class="form-control" maxlength="20" id="txtSearchByName" name="txtTeacherName" 
                                                    placeholder="Search By Name"/>
                                                </div>
                                                <label for="ActiveStatus" class="col-lg-2 control-label">Is Active</label>
                                                <div class="col-lg-4">
                                                    <input type="radio" id="rbdActiveStatus" name="rbdActiveStatus" value="1" <?php echo($Clean['ActiveStatus'] == 1 ? 'checked="checked"' : ''); ?> />&nbsp;Yes
                                                    <input type="radio" id="rbdNonActiveStatus" name="rbdActiveStatus" value="0" <?php echo($Clean['ActiveStatus'] == 0 ? 'checked="checked"' : '') ?> />&nbsp;No
                                                </div>    
                                            </div>
                                            <div class="form-group">
                                                <div class="col-sm-offset-2 col-lg-10">
                                                    <input type="hidden" name="hdnProcess" value="7"/>  
                                                    <button type="submit" class="btn btn-primary">Search</button>
                                                </div>
                                             </div>
                                        </form>
                                     </div>
                                </div>
                            </div>
                         </div>
                     </div>
                </div>
            </div>

<?php
        if ($Clean['Process'] == 7)
        {
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
                                    <div class="add-new-btn-container"><a href="add_branch_staff.php" class="btn btn-primary<?php echo $LoggedUser->HasPermissionForTask(TASK_ADD_BRANCH_STAFF) === true ? '' : ' disabled'; ?>" role="button">Add New Branch Staff</a></div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="print-btn-container"><button id="PrintButton" type="submit" class="btn btn-primary">Print</button></div>
                                    </div>
                                </div>
                                <br/>
                                <div class="row">
                                   <div class="col-lg-6">
<?php
                                    if ($TotalPages > 1)
                                    {
                                        $AllParameters = $Filters;
                                        $AllParameters['Process'] = '7';

                                        echo UIHelpers::GetPager('branch_staff_list.php', $TotalPages, $Clean['CurrentPage'], $AllParameters);
                                    }
?>                                        
                                    </div>
                                    <div class="col-lg-6"></div>  
                                </div>
<?php
                            if ($HasErrors == true)
                            {
                                echo $RecordValidator->DisplayErrorsInTable();
                            }
?>
                                <div class="row" id="RecordTableHeading">
                                    <div class="col-lg-12">
                                        <div class="report-heading-container"><strong>Branch Staff on <?php echo date('d-m-Y h:i A'); ?></strong></div>
                                    </div>
                                </div>
                                <div class="row" id="RecordTable">
                                    <div class="col-lg-12">
                                        <table width="100%" class="table table-striped table-bordered table-hover" id="DataTableRecords">
                                            <thead>
                                                <tr>
                                                    <th>S. No</th>
                                                    <th>First Name</th>
                                                    <th>Last Name</th>
                                                    <th>Staff Category</th>
                                                    <th>DOB</th>
                                                    <th>Contact Number</th>
                                                    <th>joining Date</th>
                                                    <th>user Name</th>
                                                    <th>Is Active</th>
                                                    <!--<th>Create User</th>-->
                                                    <th>Create Date</th>
                                                    <th class="print-hidden">Opreations</th>
                                                </tr>
                                            </thead>
                                            <tbody>
    <?php
                                    if (is_array($AllBranchStaff) && count($AllBranchStaff) > 0)
                                    {
                                        $Counter = 0;

                                        foreach ($AllBranchStaff as $BranchStaffID => $BranchStaffDetails)
                                        {
    ?>
                                                <tr>
                                                    <td><?php echo ++$Counter; ?></td>
                                                    <td><?php echo $BranchStaffDetails['FirstName']; ?></td>
                                                    <td><?php echo $BranchStaffDetails['LastName']; ?></td>
                                                    <td><?php echo $BranchStaffDetails['StaffCategory']; ?></td>
                                                    <td><?php echo date('d/m/Y',strtotime($BranchStaffDetails['DOB'])); ?></td>
                                                    <td><?php echo (($BranchStaffDetails['MobileNumber1']) ? $BranchStaffDetails['MobileNumber1'] : '').' <br>'.(($BranchStaffDetails['MobileNumber2']) ? $BranchStaffDetails['MobileNumber2'] : ''); ?></td>
                                                    <td><?php echo date('d/m/Y',strtotime($BranchStaffDetails['JoiningDate'])); ?></td>
                                                    <td><?php echo $BranchStaffDetails['UserName']; ?></td>
                                                    <td><?php echo ($BranchStaffDetails['IsActive']) ? 'Yes' : 'No'; ?></td>
                                                    <!--<td><?php echo $BranchStaffDetails['CreateUserName']; ?></td>-->
                                                    <td><?php echo date('d/m/Y', strtotime($BranchStaffDetails['CreateDate'])); ?></td>
                                                    <td class="print-hidden">
<?php
                                                    if ($LoggedUser->HasPermissionForTask(TASK_EDIT_BRANCH_STAFF) === true)
                                                    {
                                                        echo '<a href="edit_branch_staff.php?Process=2&amp;BranchStaffID='.$BranchStaffID.'">Edit</a>';
                                                    }
                                                    else
                                                    {
                                                        echo 'Edit';
                                                    }

                                                    echo '&nbsp;|&nbsp;';

                                                    if ($LoggedUser->HasPermissionForTask(TASK_DELETE_BRANCH_STAFF) === true)
                                                    {
                                                        echo '<a class="delete-record" href="branch_staff_list.php?Process=5&amp;BranchStaffID='.$BranchStaffID.'">Delete</a>'; 
                                                    }
                                                    else
                                                    {
                                                        echo 'Delete';
                                                    }
                                                    
                                                    echo '&nbsp;|&nbsp;';
                                                        
                                                    if ($LoggedUser->HasPermissionForTask(TASK_DELETE_BRANCH_STAFF) === true)
                                                    {
                                                        echo '<a class="deactivate-record" href="branch_staff_list.php?Process=11&amp;BranchStaffID='.$BranchStaffID.'">' . ($BranchStaffDetails['IsActive'] ? 'Deactivate' : 'Activate') . '</a>'; 
                                                    }
                                                    else
                                                    {
                                                        echo 'In Active';
                                                    }
?>
                                                        </td>
                                                </tr>
    <?php
                                        }
                                    }
                                    else
                                    {
    ?>
                                                <tr>
                                                    <td colspan="11">No Records</td>
                                                </tr>
    <?php
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
if (PrintMessage($_GET, $Message))
{
?>
<script type="text/javascript">
    alert('<?php echo $Message; ?>');
</script>
<?php
}
?>  
<script type="text/javascript">
    $(document).ready(function() {
        $(".delete-record").click(function()
        {   
            if (!confirm("Are you sure you want to delete this Task Branch Staff?"))
            {
                return false;
            }
        });
        
        $(".deactivate-record").click(function()
        {   
            if (!confirm("Are you sure you want to change status of this Branch Staff?"))
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
    });
</script>
<!-- JavaScript To Print A Report -->
<script src="js/print-report.js"></script>
<script src="/admin/js/print-report.js"></script>

 <!-- DataTables JavaScript -->
<script src="../vendor/datatables/js/jquery.dataTables.min.js"></script>
<script src="../vendor/datatables-plugins/dataTables.bootstrap.min.js"></script>
<script src="../vendor/datatables-responsive/dataTables.responsive.js"></script> 
</body>
</html>
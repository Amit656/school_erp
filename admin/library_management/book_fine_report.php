<?php
require_once("../../classes/class.users.php");
require_once("../../classes/class.validation.php");
require_once("../../classes/class.authentication.php");

require_once("../../classes/class.ui_helpers.php");

require_once("../../classes/library_management/class.books.php");
require_once("../../classes/library_management/class.book_categories.php");
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

if ($LoggedUser->HasPermissionForTask(TASK_VIEW_FINE_COLLECTION_REPORT) !== true)
{
	header('location:/admin/unauthorized_login_admin.php');
	exit;
}

$Filters = array();

$FineTypeList = array('LateFine' => 'LateFine', 'MissingBook' => 'Missing Book', 'DamageBook' => 'Damage Book');
$UserTypeList = array('Student' => 'Student', 'Teaching' => 'Teaching', 'NonTeaching' => 'Non-Teaching');
$BooksFineList = array();

$RecordDeletedSuccessfully = false;
$HasErrors = false;
$TotalRecords = 0;

$Clean = array();
$Clean['Process'] = 0;

$Clean['FineType'] = '';
$Clean['UserType'] = '';

$Clean['UserName'] = '';
$Clean['AuthorName'] = '';
$Clean['ActiveStatus'] = 0;

// paging and sorting variables start here  //
$Clean['CurrentPage'] = 1;
$TotalPages = 0;

$Start = 0;
$Limit = 3;
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
    case 7:        
        if (isset($_GET['drdFineType']))
        {
            $Clean['FineType'] = strip_tags(trim($_GET['drdFineType']));
        }
        elseif (isset($_GET['FineType']))
        {
            $Clean['FineType'] = strip_tags(trim($_GET['FineType']));
        }

        if (isset($_GET['drdUserType']))
        {
            $Clean['UserType'] = strip_tags(trim($_GET['drdUserType']));
        }
        elseif (isset($_GET['UserType']))
        {
            $Clean['UserType'] = strip_tags(trim($_GET['UserType']));
        }

        if (isset($_GET['txtUserName']))
        {
            $Clean['UserName'] = strip_tags(trim($_GET['txtUserName']));
        }
        elseif (isset($_GET['UserName']))
        {
            $Clean['UserName'] = strip_tags(trim($_GET['UserName']));
        }

        $SearchValidator = new Validator();

        if ($Clean['FineType'] != '')
        {
            $SearchValidator->ValidateInSelect($Clean['FineType'], $FineTypeList, 'Unknown Error, Please try again.');                    
        }

        if ($Clean['UserType'] != '')
        {
            $SearchValidator->ValidateInSelect($Clean['UserType'], $UserTypeList, 'Unknown Error, Please try again.');
        }

        if ($Clean['UserName'] != '')
        {
            $SearchValidator->ValidateStrings($Clean['UserName'], 'User name is required and should be between 3 and 100 characters.', 3, 100);
        }

        if ($Clean['UserName'] != '' && $Clean['UserType'] == '') 
        {
            $SearchValidator->AttachTextError('You must select an user type, when search by name.');
        }

        if ($SearchValidator->HasNotifications())
        {
            $HasErrors = true;
            break;
        }

        //set record filters        
        $Filters['FineType'] = $Clean['FineType'];
        $Filters['UserType'] = $Clean['UserType'];
        $Filters['UserName'] = $Clean['UserName'];        

        //get records count
        BooksFine::SearchBooksFine($TotalRecords, true, $Filters);

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
            $BooksFineList = BooksFine::SearchBooksFine($TotalRecords, false, $Filters, $Start, $Limit);
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
<title>Book Fine Report</title>
<link href="../vendor/jquery-ui/jquery-ui.min.css" rel="stylesheet">
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
                    <h1 class="page-header">Book Fine Report</h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>
            <form class="form-horizontal" name="frmBookReport" action="book_fine_report.php" method="get">
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
                                <label for="FineType" class="col-lg-2 control-label">Fine Type</label>
                                <div class="col-lg-4">
                                    <select class="form-control" name="drdFineType" id="FineType">
                                        <option value="">-- All Type --</option>
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
                            </div>
                            <div class="form-group">
                                <label for="UserType" class="col-lg-2 control-label">User Type</label>
                                <div class="col-lg-4">
                                    <select class="form-control" name="drdUserType" id="UserType">
                                        <option value="">-- All Type --</option>
    <?php
                                    if (is_array($UserTypeList) && count($UserTypeList) > 0)
                                    {
                                        foreach($UserTypeList as $UserType => $UserTypeName)
                                        {
                                            echo '<option ' . (($Clean['UserType'] == $UserType) ? 'selected="selected"' : '' ) . ' value="' . $UserType . '">' . $UserTypeName . '</option>';
                                        }
                                    }
    ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="UserName" class="col-lg-2 control-label">User Name</label>
                                <div class="col-lg-4">
                                    <input class="form-control" type="text" maxlength="150" id="UserName" name="txtUserName" value="<?php echo $Clean['UserName']; ?>" />
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

            if ($Clean['FineType'] != '')
            {
                $ReportHeaderText .= ' Category: ' . $FineTypeList[$Clean['FineType']] . ',';
            }

            if ($Clean['UserType'] != '')
            {
                $ReportHeaderText .= ' Issued to: ' . $UserTypeList[$Clean['UserType']] . ',';
            }

            if ($Clean['UserName'] != '')
            {
                $ReportHeaderText .= ' User Name: ' . $Clean['UserName'] . ',';
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
                                        $AllParameters = array('Process' => '7', 'FineType' => $Clean['FineType'], 'UserType' => $Clean['UserType'], 'UserName' => $Clean['UserName']);
                                        echo UIHelpers::GetPager('book_fine_report.php', $TotalPages, $Clean['CurrentPage'], $AllParameters);
                                    }
?>                                        
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="print-btn-container"><button id="PrintButton" type="submit" class="btn btn-primary">Print</button></div>
                                    </div>
                                </div>
                                <div class="row" id="RecordTableHeading">
                                    <div class="col-lg-12">
                                        <div class="report-heading-container"><strong>Book Fine Report on <?php echo date('d-m-Y h:i A') . $ReportHeaderText; ?></strong></div>
                                    </div>
                                </div>
                                <div class="row" id="RecordTable">
                                    <div class="col-lg-12">
                                        <table width="100%" class="table table-striped table-bordered table-hover" id="DataTableRecords">
                                            <thead>
                                                <tr>
                                                    <th>S.No</th>
                                                    <th>User Name</th>
                                                    <th>User Type</th> 
                                                    <th>Book Name</th>                                                    
                                                    <th>Return Date</th>
                                                    <th>Received By</th>
                                                    <th>Fine Type</th>
                                                    <th>Fine (Rs.)</th>
                                                    <th class="print-hidden">Operations</th>
                                                </tr>
                                            </thead>
                                            <tbody>
<?php
                                    if (is_array($BooksFineList) && count($BooksFineList) > 0)
                                    {
                                        $Counter = $Start;
                                        foreach ($BooksFineList as $BooksFineID => $BooksFineDetails)
                                        {
?>
                                            <tr>
                                                <td><?php echo ++$Counter; ?></td>
                                                <td><?php echo $BooksFineDetails['UserName']; ?></td>
                                                <td><?php echo $BooksFineDetails['UserType']; ?></td>
                                                <td><?php echo $BooksFineDetails['BookName']; ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($BooksFineDetails['ActualReturnDate'])); ?></td>
                                                <td><?php echo $BooksFineDetails['RetunedReceivedBy']; ?></td>
                                                <td><?php echo $BooksFineDetails['FineType']; ?></td>
                                                <td id="<?php echo $BooksFineID; ?>"><?php echo $BooksFineDetails['FineAmount']; ?></td>
                                                <td class="print-hidden" id="print-hidden<?php echo $BooksFineID; ?>">
<?php
                                                if ($BooksFineDetails['IsPaid'] == 1) 
                                                {
                                                    echo 'Paid';
                                                }
                                                else if ($LoggedUser->HasPermissionForTask(TASK_COLLECT_FINE) === true)
                                                {
                                                    echo '<button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#PayFine" value="'. $BooksFineID .'">Pay &nbsp;<i class="fa fa-angle-double-right"></i></button>';
                                                }
                                                else
                                                {
                                                    echo 'Pay';
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
<div id="PayFine" class="modal fade" role="dialog">
    <div class="modal-dialog">
    <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header btn-info">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Fine Payment Details</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="form-group">
                            <input type="hidden" name="hdnBooksFineID" id="BooksFineID" value="">
                            <label for="FineAmount" class="col-lg-3 control-label">Fine Amount</label>
                            <div class="col-lg-3">
                                <input class="form-control" type="text" maxlength="10" id="FineAmount" readonly="readonly" name="txtFineAmount"  value=""/>
                            </div>
                            <label for="PaymentDate" class="col-lg-3 control-label">Payment Date</label>
                            <div class="col-lg-3">
                                <input class="form-control select-date" type="text" maxlength="10" id="PaymentDate" name="txtPaymentDate"  value="<?php echo date('d/m/Y') ; ?>"/>
                            </div>
                        </div>                            
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-info" id="PayFineButton">&nbsp;Pay</button>
                <button type="button" class="btn btn-info" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<?php
require_once('../footer.php');
?>
<!-- DataTables JavaScript -->
<script src="../vendor/jquery-ui/jquery-ui.min.js"></script>
<script src="../vendor/datatables/js/jquery.dataTables.min.js"></script>
<script src="../vendor/datatables-plugins/dataTables.bootstrap.min.js"></script>
<script src="../vendor/datatables-responsive/dataTables.responsive.js"></script>   
<script type="text/javascript">
$(document).ready(function() {
    
    $('#DataTableRecords').DataTable({
        responsive: true,
        bPaginate: false,
        bSort: false,
        searching: false, 
        info: false
    });

    $(".select-date").datepicker({
        changeMonth: true,
        changeYear: true,
        dateFormat: 'dd/mm/yy'
    });

    $('button[data-toggle=modal]').click(function(){
        var BooksFineID = 0;
        var FineAmount = 0;
        BooksFineID = $(this).val();
        FineAmount = $('#'+ BooksFineID).text();
        
        $('#BooksFineID').val(BooksFineID);
        $('#FineAmount').val(FineAmount);
    });

    $('#PayFineButton').click(function(){
        var BooksFineID = 0;
        var PaymentDate = '';

        BooksFineID = $('#BooksFineID').val();
        PaymentDate = $('#PaymentDate').val();        

        if (BooksFineID <= 0 || PaymentDate == '') 
        {
            alert('Unknown error, please try again.');
            return false;
        }

        $.post("/xhttp_calls/pay_book_fine.php", {SelectedBooksFineID:BooksFineID, PaymentDate:PaymentDate}, function(data)
        {
            ResultArray = data.split("|*****|");
            
            if (ResultArray[0] == 'error')
            {
                alert (ResultArray[1]);
                return false;
            }
            else
            {   
                if (ResultArray[1] > 0) 
                {
                    $('#'+ BooksFineID).text('0.00');
                    $('#print-hidden'+ BooksFineID).html('Paid');
                }
                
                $('#PayFine').modal('hide');
            }
        });
    });
});
</script>
<!-- JavaScript To Print A Report -->
<script src="/admin/js/print-report.js"></script>
</body>
</html>
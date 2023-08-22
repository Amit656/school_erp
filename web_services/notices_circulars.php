<?php
header ("Access-Control-Allow-Origin: *");
header("Content-Type:application/json");

require_once("../classes/school_administration/class.notices_circulars.php");
require_once("../classes/school_administration/class.branch_staff.php");
require_once("../classes/school_administration/class.classes.php");

$AllNoticeCirculars = array();
$AllNoticeCirculars = NoticeCircular::NoticeCircularReports(30); //Used for last 30 days//

?>
                                <table width="100%" class="table table-striped table-bordered table-hover" id="DataTableRecords">
                                            <thead>
                                                <tr>
                                                    <th>Sr. No.</th>
                                                    <th>Title</th>
                                                    <th>Details</th>
                                                    <th>Date</th>
                                                    <th>Applicable For Classes </th>
                                                    <th>Applicable For Staff</th>
                                                </tr>
                                            </thead>
                                            <tbody>
<?php
                                    if (is_array($AllNoticeCirculars) && count($AllNoticeCirculars) > 0)
                                    {
                                        $Counter = 0;
                                        foreach ($AllNoticeCirculars as $NoticeCircularID => $NoticeCircularDetails)
                                        {
?>
                                                <tr>
                                                    <td><?php echo ++$Counter; ?></td>
                                                    <td><?php echo $NoticeCircularDetails['NoticeCircularSubject']; ?></td>
                                                    <td><?php echo $NoticeCircularDetails['NoticeCircularDetails']; ?></td>
                                                    <td><?php echo date('d/m/Y', strtotime($NoticeCircularDetails['NoticeCircularDate'])); ?></td>
                                                    <td><?php echo (($NoticeCircularDetails['TotalClassApplicable'] > 0) ? 'Yes' : 'No'); ?></td>
                                                    <td><?php echo (($NoticeCircularDetails['TotalStaffApplicable'] > 0) ? 'Yes' : 'No'); ?></td>
                                                </tr>
<?php
                                        }
                                    }
                                    
                                    else
                                    {
?>                                        
                                                <tr>
                                                    <td colspan="9">No Records Found</td>
                                                </tr>
<?php                                               
                                    }
?>
                                            </tbody>
                                        </table>
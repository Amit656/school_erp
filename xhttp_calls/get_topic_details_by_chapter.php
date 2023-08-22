<?php
//header('Content-Type: application/json');

require_once('../classes/class.users.php');
require_once('../classes/class.authentication.php');

try
{
	$AuthObject = new ApplicationAuthentication;
	$LoggedUser = new User(0, $AuthObject->CheckValidUser());
}

// THIS CATCH BLOCK BUBBLES THE EXCEPTION TO THE BUILT IN 'Exception' CLASS IF THERE ARE ANY UNCAUGHT ERRORS //
catch (ApplicationAuthException $e)
{
	echo 'error|*****|Unknown error, please try again.';
	exit;
}
catch (Exception $e)
{
	echo 'error|*****|Unknown error, please try again.';
	exit;
}

require_once("../classes/academic_supervision/class.chapter_topics.php");

$ChapterID = 0;
$Counter = 0;

if (isset($_POST['SelectedChapterID']))
{
	$ChapterID = (int) $_POST['SelectedChapterID'];
}

if (isset($_POST['Counter']))
{
	$Counter = (int) $_POST['Counter'];
}

if ($ChapterID <= 0 || $Counter <= 0)
{
	echo 'error|*****|Unknown error, please try again.';
	exit;
}

$Clean['ChapterIDList'] = array();
$Clean['ChapterTopicIDList'] = array();
$Clean['ExpectedClassesList'] = array();
$Clean['StartDateList'] = array();
$TopicDetailList = array();
$TopicDetailList = ChapterTopic::GetChapterTopicsByChapter($ChapterID);

echo 'success|*****|';
?>
<thead>
    <tr>
        <th>S. No.</th>
        <th>Topic Name</th>                                        
        <th>Expected Classes</th>
        <th>Start Date</th>
    </tr>
</thead>
<tbody>
<?php
if (is_array($TopicDetailList) && count($TopicDetailList) > 0)
{
	$Count = 0;
	foreach ($TopicDetailList as $ChapterTopicID => $TopicName)
	{
?>
		<tr>
		<td><?php echo ++$Count; ?></td>
		<td>
		    <label class="checkbox-inline"><input class="custom-radio" type="checkbox" id="ChapterTopicID<?php echo $ChapterTopicID; ?>" name="chkChapterTopicIDList[<?php echo $Counter;?>][<?php echo $ChapterTopicID; ?>]" <?php echo (array_key_exists($Counter, $Clean['ChapterTopicIDList']) ? in_array($ChapterTopicID, $Clean['ChapterTopicIDList'][$Counter]) ? 'checked="checked"' : '' : ''); ?> value="<?php echo $ChapterTopicID; ?>" /><?php echo $TopicName; ?>
		    </label>
		<td class="<?php echo isset($Errors[$ChapterTopicID]['ExpectedClassesList']) ? ' has-error' : ''; ?>">
			<input class="form-control" type="text" maxlength="5" id="ExpectedClasses<?php echo $ChapterTopicID; ?>" name="txtExpectedClasses[<?php echo $ChapterTopicID;?>]" value="<?php echo ((array_key_exists($ChapterTopicID, $Clean['ExpectedClassesList'])) ? $Clean['ExpectedClassesList'][$ChapterTopicID] : ''); ?>" />

			<?php echo isset($Errors[$ChapterTopicID]['ExpectedClassesList']) ? '<small class="error text-danger">( Enter numeric value.)</small>' : ''; ?>
		</td>
		<td class="<?php echo isset($Errors[$ChapterTopicID]['StartDateList']) ? ' has-error' : ''; ?>">
		    <input class="form-control select-date" type="text" maxlength="10" id="StartDate<?php echo $ChapterTopicID; ?>" name="txtStartDate[<?php echo $ChapterTopicID;?>]" value="<?php echo ((array_key_exists($ChapterTopicID, $Clean['StartDateList'])) ? $Clean['StartDateList'][$ChapterTopicID] : ''); ?>" />
		    
		    <?php echo isset($Errors[$ChapterTopicID]['StartDateList']) ? '<small class="error text-danger">( Enter valid date.)</small>' : ''; ?>
		</td>
		</tr>
<?php
    }
}
?>
</tbody>
exit;
?>
<script type="text/javascript">
	$(document).ready(function(){

    $(".select-date").datepicker({
                changeMonth: true,
                changeYear: true,
                dateFormat: 'dd/mm/yy'
        });
});
</script>>
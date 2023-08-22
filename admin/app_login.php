<?php
// error_log(json_encode($_REQUEST));
require_once('../classes/class.users.php');
require_once('../classes/examination/class.exam_types.php');

$ActiveExamTypes = ExamType::GetActiveExamTypes();

$Clean = array();

$Clean['Token'] = '';
$Clean['FileName'] = '';
$Clean['SelectedExamType'] = '&SelectedExamType=' . implode(',', array_keys($ActiveExamTypes));

$Clean['UserName'] = '';

if (isset($_GET['Token']))
{
    $Clean['Token'] = strip_tags(trim($_GET['Token']));
}

if (isset($_GET['FileName']))
{
    $Clean['FileName'] = strip_tags(trim($_GET['FileName']));
}

if ($Clean['Token'] == '')
{
    header('/admin/error.php');
    exit;
}

try
{
    $UserObject = new User('', '', $Clean['Token']);
}
catch (ApplicationDBException $e)
{
    header('/admin/error.php');
    exit;
}
catch (Exception $e)
{
    header('/admin/error.php');
    exit;
}

session_start();
$_SESSION['ValidUser'] = $UserObject->GetUserName();

header('location:' . $Clean['FileName']. $Clean['SelectedExamType']);
exit;
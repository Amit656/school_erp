<?php
//header('Content-Type: application/json');

require_once('../../classes/class.users.php');
require_once('../../classes/class.authentication.php');

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

$UserTypeList = array('Student' => 'Students', 'TeachingStaff' => 'Teaching Staff', 'NonTeachingStaff' => 'Non Teaching Staff');

$UserType = 'NonTeachingStaff';
$ClassSectionID = 0;

if (isset($_POST['UserType']))
{
	$UserType = strip_tags(trim($_POST['UserType']));
}

if (isset($_POST['SelectedClassSectionID']))
{
	$ClassSectionID = (int) $_POST['SelectedClassSectionID'];
}

if (!array_key_exists($UserType, $UserTypeList))
{
	echo 'error|*****|Unknown error, please try again.';
	exit;
}

require_once('../../classes/school_administration/class.class_sections.php');
require_once('../../classes/school_administration/class.students.php');
require_once('../../classes/school_administration/class.student_details.php');
require_once('../../classes/school_administration/class.branch_staff.php');

if ($UserType == 'Student')
{
	/*if ($ClassSectionID <= 0)
	{
		echo 'error|*****|Unknown error, please try again.';
		exit;
	}*/
	
	try
	{
		$CurrentClassSection = new ClassSections($ClassSectionID);
	}
	catch (ApplicationDBException $e)
	{
		echo 'error|*****|Unknown error, please try again.';
		exit;
	}
	catch (Exception $e)
	{
		echo 'error|*****|Unknown error, please try again.';
		exit;
	}
	
	$StudentList = array();
	$StudentList = StudentDetail::GetStudentsByClassSectionID($ClassSectionID);
	
	#HTML here
?>
	<table class="table table-dark table-striped table-bordered table-hover">
		<thead>
			<tr>
				<th>S. No.</th>
				<th>Select</th>
				<th>Student Name</th>
				<th>Roll Number</th>
				<th>Mobile Number</th>
			</tr>
		</thead>
		<tbody>
<?php
	if (count($StudentList) > 0)
	{
		$Counter = 0;
		foreach ($StudentList as $StudentID => $StudentDetails)
		{
?>
			<tr>
				<td><?php echo ++$Counter; ?></td>
				<td class="text-center"><input <?php echo $StudentDetails['MobileNumber'] == '' ? 'disabled="disabled"' : ''; ?> type="checkbox" name="chkStudents[<?php echo $StudentID; ?>]" value="1" /></td>
				<td><?php echo $StudentDetails['FirstName'] . ' ' . $StudentDetails['LastName']; ?></td>
				<td><?php echo $StudentDetails['RollNumber']; ?></td>
				<td><?php echo $StudentDetails['MobileNumber']; ?></td>
			</tr>
<?php
		}
	}
	else
	{
		echo '<tr><td colspan="5">No Records</td></tr>';
	}
?>
		</tbody>
	</table>
<?php
}
else if ($UserType == 'TeachingStaff')
{
	$TeachingFacultyList = array();
	$TeachingFacultyList = BranchStaff::GetActiveBranchStaff('Teaching');
	
	#HTML here
?>
	<table class="table table-dark table-striped table-bordered table-hover">
		<thead>
			<tr>
				<th>S. No.</th>
				<th>Select</th>
				<th>Faculty Name</th>
				<th>Mobile Number</th>
			</tr>
		</thead>
		<tbody>
<?php
	if (count($TeachingFacultyList) > 0)
	{
		$Counter = 0;
		foreach ($TeachingFacultyList as $BranchStaffID => $BranchStaffDetails)
		{
?>
			<tr>
				<td><?php echo ++$Counter; ?></td>
				<td class="text-center"><input <?php echo $BranchStaffDetails['MobileNumber1'] == '' ? 'disabled="disabled"' : ''; ?> type="checkbox" name="chkTeachingStaff[<?php echo $BranchStaffID; ?>]" value="1" /></td>
				<td><?php echo $BranchStaffDetails['FirstName'] . ' ' . $BranchStaffDetails['FirstName']; ?></td>
				<td><?php echo $BranchStaffDetails['MobileNumber1']; ?></td>
			</tr>
<?php
		}
	}
	else
	{
		echo '<tr><td colspan="4">No Records</td></tr>';
	}
?>
		</tbody>
	</table>
<?php
}
else if ($UserType == 'NonTeachingStaff')
{
	$NonTeachingFacultyList = array();
	$NonTeachingFacultyList = BranchStaff::GetActiveBranchStaff('NonTeaching');
	
	#HTML here
?>
	<table class="table table-dark table-striped table-bordered table-hover">
		<thead>
			<tr>
				<th>S. No.</th>
				<th>Select</th>
				<th>Faculty Name</th>
				<th>Mobile Number</th>
			</tr>
		</thead>
		<tbody>
<?php
	if (count($NonTeachingFacultyList) > 0)
	{
		$Counter = 0;
		foreach ($NonTeachingFacultyList as $BranchStaffID => $BranchStaffDetails)
		{
?>
			<tr>
				<td><?php echo ++$Counter; ?></td>
				<td class="text-center"><input <?php echo $BranchStaffDetails['MobileNumber1'] == '' ? 'disabled="disabled"' : ''; ?> type="checkbox" name="chkNonTeachingStaff[<?php echo $BranchStaffID; ?>]" value="1" /></td>
				<td><?php echo $BranchStaffDetails['FirstName'] . ' ' . $BranchStaffDetails['LastName']; ?></td>
				<td><?php echo $BranchStaffDetails['MobileNumber1']; ?></td>
			</tr>
<?php
		}
	}
	else
	{
		echo '<tr><td colspan="4">No Records</td></tr>';
	}
?>
		</tbody>
	</table>
<?php
}
exit;
?>
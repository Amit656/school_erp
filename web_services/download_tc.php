<?php
$TCList['256/18-19'] = array('imgPath' => 'http://lucknowips.addedschools.com/web_services/tc/IMG-20191017-WA0000.jpg', 'imgName' => '256/18-19', 'imgNameReal' => 'IMG-20191017-WA0000.jpg');
$TCList['199/17-18'] = array('imgPath' => 'http://lucknowips.addedschools.com/web_services/tc/IMG-20191017-WA0001.jpg', 'imgName' => '199/17-18', 'imgNameReal' => 'IMG-20191017-WA0001.jpg');
$TCList['200/17-18'] = array('imgPath' => 'http://lucknowips.addedschools.com/web_services/tc/IMG-20191017-WA0002.jpg', 'imgName' => '200/17-18', 'imgNameReal' => 'IMG-20191017-WA0002.jpg');
$TCList['007/17-18'] = array('imgPath' => 'http://lucknowips.addedschools.com/web_services/tc/IMG-20191017-WA0003.jpg', 'imgName' => '007/17-18', 'imgNameReal' => 'IMG-20191017-WA0003.jpg');
$TCList['179/17-18'] = array('imgPath' => 'http://lucknowips.addedschools.com/web_services/tc/IMG-20191017-WA0004.jpg', 'imgName' => '179/17-18', 'imgNameReal' => 'IMG-20191017-WA0004.jpg');
$TCList['261/18-19'] = array('imgPath' => 'http://lucknowips.addedschools.com/web_services/tc/IMG-20191017-WA0005.jpg', 'imgName' => '261/18-19', 'imgNameReal' => 'IMG-20191017-WA0005.jpg');
$TCList['202/17-18'] = array('imgPath' => 'http://lucknowips.addedschools.com/web_services/tc/IMG-20191017-WA0006.jpg', 'imgName' => '202/17-18', 'imgNameReal' => 'IMG-20191017-WA0006.jpg');
$TCList['209/17-18'] = array('imgPath' => 'http://lucknowips.addedschools.com/web_services/tc/IMG-20191017-WA0007.jpg', 'imgName' => '209/17-18', 'imgNameReal' => 'IMG-20191017-WA0007.jpg');
$TCList['180/17-18'] = array('imgPath' => 'http://lucknowips.addedschools.com/web_services/tc/IMG-20191017-WA0008.jpg', 'imgName' => '180/17-18', 'imgNameReal' => 'IMG-20191017-WA0008.jpg');

$ImageName = $TCList[$_REQUEST['AdmissionNumber']]['imgNameReal'];

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename='.$ImageName);

readfile('tc/' . $ImageName);

exit;
?>
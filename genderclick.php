<?php
// genderclick.php
include("genderad.php");
$adid = $_REQUEST['adid'];
$deviceid = $_REQUEST['deviceid'];
$siteid = $_REQUEST['siteid'];

$current_device->gender = $adid;

if($adid == 'girl')
{
	$current_site->num_girls += 1;
	$current_site->girl['clicks'] += 1;
}
else
{
	$current_site->num_guys += 1;
	$current_site->guy['clicks'] += 1;
}

$memcache->set($device_key, $current_device);
$memcache->set($site_key, $current_site);

//header("Location: http://www.speeddate.com/?adid=$adid");

echo "<br>========== debug info genderclick ===========<br>";
print_r($current_device);
echo "<br>";
print_r($current_site);
print "<br>========== ******** ===========";

?>
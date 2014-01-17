<?php
// genderclick.php
include("genderad.php");
$adid = $_REQUEST['adid'];
$deviceid = $_REQUEST['deviceid'];
$siteid = $_REQUEST['siteid'];

$site_key = "SITE".$siteid;
$device_key = "DEVICE".$deviceid;

$memcache = apc_fetch('memcache');

$memcache->devices[$device_key] = $adid;
#store the correspondence between number of clicks and current CPC
$memcache->sites[$site_key][$adid]['clicks'][$adid_cpc[$adid]*100]+=1;

#echo "<br>========== genderclick ===========<br>";
#print_r($memcache);
#echo "<br>======================================";

apc_store('memcache',$memcache);

header("Location: http://www.speeddate.com/?adid=$adid");

?>
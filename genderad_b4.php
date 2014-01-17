<?php
// genderad.php

define('LOCAL_MEMCACHE_PORT', 83 );
define('LOCAL_MEMCACHE_HOST', '127.0.0.1' );

$adid_cpc = array("girl" => .06, "guy" => 1.70);   // THIS COULD CHANGE EACH DAY
$siteid = $_REQUEST['siteid'];
$deviceid = $_REQUEST['deviceid'];
$device_key = "DEVICE".$deviceid;
$site_key = "SITE".$siteid;

#Memcache Class definition
class Memcache
{
  #device_key => { "gender" : "girl" }
	public $devices;
	public $sites;
	public function __construct()
	{
		# Should read data from file
    $devices = array();
    $sites = array();
	}
	public function addServer($host,$port,$open)
	{
    return $open;
  }
  #Calculate eCPM given site and gender, use eCPM = 1000*earnings/impressions
  public function eCPM($site_key,$gender)
  {
    return 1000*$this->earnings($site_key,$gender)/$this->sites[$site_key][$gender]['impres'];
  }
  #Calculate earnings given site and gender, sum of the number of clicks * correspond CPCs
  public function earnings($site_key,$gender)
  {
    $earns = 0;
    foreach(array_keys($this->sites[$site_key][$gender]['clicks']) as $cpc)
      $earns += $this->sites[$site_key][$gender]['clicks'][$cpc]/100 * $cpc;
    return $earns;
  }
  #Given a gender, find an ad with the maximum eCPM value
  public function max_eCPM($gender)
  {
    $max_value = -INF;
    $max_site_key = null;
    foreach(array_keys($this->sites) as $site_key)
    {
      if($max_value < $this->eCPM($site_key,$gender))
      {
        $max_value = $this->eCPM($site_key,$gender);
        $max_site_key = $site_key;
      }
    }
    return $max_site_key;
  }
}

// open connection to memcache
$memcache = apc_fetch('memcache');
if($memcache == null)
  $memcache = new Memcache;

$gender_found = null;
if ( $memcache ) { 
  if ( $memcache->addServer(LOCAL_MEMCACHE_HOST, LOCAL_MEMCACHE_PORT, TRUE) ) {
      // fetch device and site objects using device_key and site_key

      # encounter a new device
      if( !array_key_exists($device_key, $memcache->devices) )
      {
          $memcache->devices[$device_key] = null;
      }
      # encounter a new site
      if( !array_key_exists($site_key, $memcache->sites) )
          //$memcache->sites[$site_key] = array("girl"=>array("clicks"=>0,"impres"=>0),"guy"=>array("clicks"=>0,"impres"=>0));
          $memcache->sites[$site_key] = array("girl"=>array("clicks"=>array(),"impres"=>0),"guy"=>array("clicks"=>array(),"impres"=>0));

      # get the gender, if unknown -> $gender_found == null
      if( array_key_exists($device_key, $memcache->devices) )
          $gender_found = $memcache->devices[$device_key];
  }
 }

 #A siteid that has 90% guys should be serving mostly ads for guys when the gender is unknown and the ad CPCs are equal
$condition; #condition (true -> girl, false -> guy)
 if(is_null($gender_found) && $adid_cpc['girl'] == $adid_cpc['guy'])
 {
    #property 1
 	  $r = .10;
 	  $condition = rand(1, 100)/100.0 < $r;
 } elseif($gender_found!=null) {
    #known device
    $condition = $gender_found == 'girl';
 }else{
    #we don't know the gender, bias the gender according to cpc
    #$r = .50;
    $sum = $adid_cpc['guy']+$adid_cpc['girl'];
    $r_girl = $adid_cpc['girl']/$sum;
    $condition = rand(1, 100)/100.0 < $r_girl;
 }

if ( $condition ) { #sever an ad with max eCPM for girls
    $max_site_key = $memcache->max_eCPM('girl');
    if($max_site_key!=null)
      $site_key = $max_site_key;
    $memcache->sites[$site_key]['girl']['impres']+=1;
    $siteid = substr($site_key,4,strlen($site_key));
    echo "<a href='genderclick.php?adid=girl&siteid=$siteid&deviceid=$deviceid'>Hey Girls, find hot guys!</a>";
} else {    #sever an ad with max eCPM for guys
    $max_site_key = $memcache->max_eCPM('guy');
    if($max_site_key!=null)
      $site_key = $max_site_key;
    $memcache->sites[$site_key]['guy']['impres']+=1;
    $siteid = substr($site_key,4,strlen($site_key));
    echo "<a href='genderclick.php?adid=guy&siteid=$siteid&deviceid=$deviceid'>Hey Guys, find hot girls!</a>";
}

#echo "<br>========== genderad ===========<br>";
#print_r($memcache);
#print "<br>========== ******** ===========";
apc_store('memcache', $memcache);
?>
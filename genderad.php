<?php
// genderad.php
define('LOCAL_MEMCACHE_PORT', 80 );
define('LOCAL_MEMCACHE_HOST', '127.0.0.1' );

$adid_cpc = array("girl" => .01, "guy" => .50);   // THIS COULD CHANGE EACH DAY
$siteid = $_REQUEST['siteid'];
$deviceid = $_REQUEST['deviceid'];
$device_key = "DEVICE".$deviceid;
$site_key = "SITE".$siteid;

#device model
class device
{
	public $device_id;
	public $gender;
	function __construct($deid, $ge = null)
	{
		$this->device_id = $deid;
		$this->gender = $ge;
	}
}
#site model
class site
{
	public $site_id;
	public $girl;
	public $guy;
	public $num_girls;
	public $num_guys;

	function __construct($sid)
	{
		$this->site_id = $sid;
		$this->num_guys = 0;
		$this->num_girls = 0;
		$this->girl = array('clicks'=>0,'impressions'=>0);
		$this->guy = array('clicks'=>0,'impressions'=>0);
	}
	public function guy_ratio()  #return the ration of number of guys
	{
		return $this->num_guys/($this->num_guys+$this->num_girls);
	}
	public function CTR_of($gender)  #calculate CTR
	{
		if($gender == 'guy')
		{
			if($this->guy['impressions'] != 0)
				return $this->guy['clicks']/$this->guy['impressions'];
			else
				return 0;
		}
		if($this->girl['impressions'] != 0)
			return $this->girl['clicks']/$this->girl['impressions'];
		return 0;
	}
	public function eCPM_of($gender,$cpc) #calculate eCPM
	{
		return 1000 * $this->CTR_of($gender) * $cpc[$gender];
	}
}

$current_device = null;
$current_site = null;
// open connection to memcache
$memcache = new Memcache;

if ( $memcache ) {
	if ( $memcache->addServer(LOCAL_MEMCACHE_HOST, LOCAL_MEMCACHE_PORT, TRUE) ) {
    	// fetch device and site objects using device_key and site_key
		$memcache->connect(LOCAL_MEMCACHE_HOST, LOCAL_MEMCACHE_PORT);
		$current_device = $memcache->get($device_key);
		$current_site = $memcache->get($site_key);
	}
}
#new device
if($current_device==null)
	$current_device = new device($deviceid);
#new site
if($current_site==null)
	$current_site = new site($siteid);

$user_gender = $current_device->gender;
$ad_for_girl;  #indicator of showing ad for girls

if($user_gender != null) #case of known gender
{
	$ad_for_girl = $user_gender == 'girl';
}
else  # case of unknown gender
{
	#A siteid that has 90% guys should be serving mostly ads for guys when the gender is unknown and the ad CPCs are equal
	if($adid_cpc['girl'] == $adid_cpc['guy'] && $current_site->guy_ratio >= 0.9)
	{
		$r = .10;
		$ad_for_girl = rand(1, 100)/100.0 < $r;
	}
	else
	{
		#use the site eCPM to decide which ad we going to use
		if($current_site->eCPM_of('guy',$adid_cpc) > $current_site->eCPM_of('girl',$adid_cpc))
			$ad_for_girl = false;
		else
			$ad_for_girl = true;
	}
}	

if ( $ad_for_girl )  #show girl ad
{
	$current_site->girl['impressions'] += 1;
	echo "<a href='genderclick.php?adid=girl&siteid=$siteid&deviceid=$deviceid'>Hey Girls, find hot guys!</a>";
}
else  #show guy ad
{
	$current_site->guy['impressions'] += 1;
	echo "<a href='genderclick.php?adid=guy&siteid=$siteid&deviceid=$deviceid'>Hey Guys, find hot girls!</a>";
}
/*echo "<br>========== debug info genderad ===========<br>";
print_r($current_device);
echo "<br>";
print_r($current_site);
print "<br>========== ******** ===========";*/
$memcache->set($device_key,$current_device);
$memcache->set($site_key,$current_site);
?>
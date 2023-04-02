<?php 
header("Content-Type:application/json");
header('Access-Control-Allow-Origin: *');
date_default_timezone_set("Asia/Jakarta");

/*Connection to MySQL DB*/
$connMy= mysqli_connect('localhost','vehicle','DBvehicle');
if ($connMy) {
	mysqli_select_db($connMy,'vehicle_db');
} else create_log("Failed to connect MySQL DB");

$sqlMy = "SELECT * FROM mst_device WHERE id='CTR101'";
$resMy = mysqli_query($connMy,$sqlMy);
if (!$resMy) create_log("Failed ".mysqli_error($connMy)." <> ".$sqlMy);		
$rec=mysqli_fetch_array($resMy,MYSQLI_ASSOC);
$ControlIP = $rec['device_url'];

$sqlMy = "SELECT * FROM mst_device WHERE is_active='1'";
$resMy = mysqli_query($connMy,$sqlMy);
if (!$resMy) create_log("Failed ".mysqli_error($connMy)." <> ".$sqlMy);		
$rec=mysqli_fetch_array($resMy,MYSQLI_ASSOC);
$DeviceID = $rec['id'];

create_log("Send Beacon From ".$DeviceID." to ".$ControlIP);	

$payload = array(	 
	'task' => "BEACON",
	'vehicle' => $DeviceID,
	'route' => "0",
	'data_latitude' => "0",
	'data_longitude'=> "0",
	'data_speed' => "0",
	'data_track' => "0",
	'destination' => "LC",
	'timestamp' => date("Y-m-d h:i:sa")
);

$curl = curl_init();
curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($payload) );
curl_setopt($curl, CURLOPT_URL, $ControlIP);
curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 0);
curl_setopt($curl, CURLOPT_TIMEOUT, 10);

$result = curl_exec($curl);
curl_close($curl);
create_log($result);


function create_log($logc){
	$MySystem = PHP_OS;
	if ($MySystem == "Linux")
		$path = "/home/vehicle/log/logvps_";
	else
		$path = "d:\laragon\www\log\logvps_";
	list($usec, $sec) = explode(' ', microtime());
	$usec = str_replace("0.", ".", $usec);
	$time=date('H:i:s', $sec) . $usec;
	$msglog="[".$time."] : -> ".json_encode($logc)."\n";
	echo $msglog."\n" ;
	$flog = fopen($path.date('Ymd').".txt","a");
	fwrite($flog,$msglog);
	fclose($flog);
}



?>
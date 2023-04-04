<?php 
header("Content-Type:application/json");
header('Access-Control-Allow-Origin: *');
date_default_timezone_set("Asia/Jakarta");

$host = "127.0.0.1";
$port = 8090;
// No Timeout 
set_time_limit(0);

/*Connection to MySQL DB*/
$connMy= mysqli_connect('localhost','vehicle','DBvehicle');
if ($connMy) {
	mysqli_select_db($connMy,'vehicle_db');
} else create_log("Failed to connect MySQL DB");

$sqlMy = "SELECT * FROM cek_poin WHERE is_active='1'";
$resMy = mysqli_query($connMy,$sqlMy); 
if (!$resMy) 
	create_log("Failed ".mysqli_error($connMy)." <> ".$resMy);

while($recDest=mysqli_fetch_array($resMy,MYSQLI_ASSOC)) {   
	echo  $recDest['location_name'] ;
	$dtime = date('Y-m-d H:i:s');
	$dlat = $recDest['latitude'];
	$dlon = $recDest['longitude'];
	
	$message = '{"task":"TX_PIPE",
		"dtime":"'.$dtime.'",
		"dlat":"'.$dlat.'",
		"dlon":"'.$dlon.'",
		"dspeed":"0.00",
		"dtrack":"0.00"
		}';
	$socket = socket_create(AF_INET, SOCK_STREAM, 0) or die("Could not create socket\n");
	$result = socket_connect($socket, $host, $port) or die("Could not connect toserver\n");
	socket_write($socket, $message, strlen($message)) or die("Could not send data to server\n");
	sleep(5);
}



$sqlMy = "SELECT * FROM cek_poin WHERE is_active='1' AND id < 13 order by id DESC";
$resMy = mysqli_query($connMy,$sqlMy); 
if (!$resMy) 
	create_log("Failed ".mysqli_error($connMy)." <> ".$resMy);

while($recDest=mysqli_fetch_array($resMy,MYSQLI_ASSOC)) {   
	echo  $recDest['location_name'] ;
	$dtime = date('Y-m-d H:i:s');
	$dlat = $recDest['latitude'];
	$dlon = $recDest['longitude'];
	
	$message = '{"task":"TX_PIPE",
		"dtime":"'.$dtime.'",
		"dlat":"'.$dlat.'",
		"dlon":"'.$dlon.'",
		"dspeed":"0.00",
		"dtrack":"0.00"
		}';
	$socket = socket_create(AF_INET, SOCK_STREAM, 0) or die("Could not create socket\n");
	$result = socket_connect($socket, $host, $port) or die("Could not connect toserver\n");
	socket_write($socket, $message, strlen($message)) or die("Could not send data to server\n");
	sleep(5);
}

/*

$sqlMy = "SELECT * FROM cek_poin WHERE is_active='1' AND id < 13 order by id DESC";
$resMy = mysqli_query($connMy,$sqlMy); 
if (!$resMy) 
	create_log("Failed ".mysqli_error($connMy)." <> ".$resMy);

while($recDest=mysqli_fetch_array($resMy,MYSQLI_ASSOC)) {   
	echo  $recDest['location_name'] ;
	$sqlMy = "INSERT INTO track_data (id, task, device_id, route_id, pos_latitude, pos_longitude) 
		VALUES(REPLACE(UUID(),'-',''), 'SET_POSITION', 'DTR101', 'TXR1',".$recDest['latitude'].",".$recDest['longitude'].")";
	mysqli_query($connMy,$sqlMy);
	sleep(7);
}
*/
?>
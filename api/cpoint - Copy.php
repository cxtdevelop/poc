<?php 
header("Content-Type:application/json");
header('Access-Control-Allow-Origin: *');
date_default_timezone_set("Asia/Jakarta");

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
	$sqlMy = "INSERT INTO track_data (id, task, device_id, route_id, pos_latitude, pos_longitude) 
		VALUES(REPLACE(UUID(),'-',''), 'SET_POSITION', 'DTR101', 'TXR1',".$recDest['latitude'].",".$recDest['longitude'].")";
	mysqli_query($connMy,$sqlMy);
	sleep(5);
}


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

?>
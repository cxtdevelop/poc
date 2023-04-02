<?php 
header("Content-Type:application/json");
header('Access-Control-Allow-Origin: *');
date_default_timezone_set("Asia/Jakarta");

/*Connection to MySQL DB*/
$connMy= mysqli_connect('localhost','vehicle','DBvehicle');
if ($connMy) {
	mysqli_select_db($connMy,'vehicle_db');
} else create_log("Failed to connect MySQL DB");

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
	$sqlMy = "CALL sp_vehicle('GET_ID', '0', '0', 0, 0, 0, 0,'0')";
	$res = mysqli_query($connMy,$sqlMy);
	$rec = mysqli_fetch_array($res,MYSQLI_ASSOC);
	echo "ID: ".$rec['id']."  ".$rec['device_name']." ==== url: ".$rec['device_url']; 
return;
}

$task = $data['task'];
$vehicle = $data['vehicle'];
$route =  $data['route'];
$latitude = $data['data_latitude'];
$longitude = $data['data_longitude'];
$speed = $data['data_speed'];
$track = $data['data_track'];
$destination = $data['destination'];

create_log($data);
$sqlMy = "INSERT INTO api_log (id, json_task) VALUES(REPLACE(UUID(),'-',''),'".json_encode($data)."')";
mysqli_query($connMy,$sqlMy);

switch ($task) {
	case "TX_REGISTER":
	case "TX_LOG":
	case "TX_PIPE":
	case "BEACON":
		$sqlMy = "CALL sp_vehicle('".$task."','".$vehicle."','".$route."',".$latitude.",".$longitude.",".$speed.",".$track.",'".$destination."')";
		mysqli_query($connMy,$sqlMy);
		create_log($sqlMy);
	break;

	case "REQ_DUELON":
	case "REQ_DUELOFF":
		$sqlMy = "SELECT * FROM mst_device WHERE id='".$route."'";
		$resMy = mysqli_query($connMy,$sqlMy);
		if (!$resMy) create_log("Failed ".mysqli_error($connMy)." <> ".$sqlMy);
		$rec=mysqli_fetch_array($resMy,MYSQLI_ASSOC);

		$row_cnt = mysqli_num_rows($resMy);
		if ($row_cnt == 0) {
			echo "Simpang not found";
			exit;
		}

		$device_url = $rec['device_url'];
		echo $device_url;
		$new_task = str_replace('REQ_', 'INS_', $task);
		$payload = array(	 
			'task' => $new_task,
			'vehicle' => $vehicle,
			'route' => $route,
			'data_latitude' => $latitude,
			'data_longitude'=> $longitude ,
			'data_speed' => $speed,
			'data_track' => $track,
			'destination' => $destination,
			'timestamp' => date("Y-m-d h:i:sa")
		);

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($payload) );
		curl_setopt($curl, CURLOPT_URL, $device_url);
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 0);
		curl_setopt($curl, CURLOPT_TIMEOUT, 10);

		$result = curl_exec($curl);
		curl_close($curl);
		create_log($result);
		
	break;



	case "INS_DUELON":
	case "INS_DUELOFF":	
		if(!($sock = socket_create(AF_INET, SOCK_STREAM, 0))) {
			$errorcode = socket_last_error();
    		$errormsg = socket_strerror($errorcode);
    		die("Couldn't create socket: [$errorcode] $errormsg \n");
		}

		if(!socket_connect($sock , '127.0.0.1' , 8090)){
			$errorcode = socket_last_error();
    		$errormsg = socket_strerror($errorcode);
    		die("Could not connect: [$errorcode] $errormsg \n");
		}

		$message = $task.",".$vehicle.",".$route.",".$latitude.",".$longitude.",".$speed.",".$track.",".$destination.",";
		//Send the message to the server
		if( ! socket_send ( $sock , $message , strlen($message) , 0)){
			$errorcode = socket_last_error();
    		$errormsg = socket_strerror($errorcode);
    		die("Could not send data: [$errorcode] $errormsg \n");
		}
		socket_close($sock);
		//Bagian kirim WA
		$data_array=array();
		$data = array(
			'phone' => "6281234561013",
			'message' => $message,
		);
		array_push($data_array,$data);

		
		$payload = array("data"=>$data_array);
		$curl = curl_init();
		$token = "fuHCim0Rp7zMW75JUlEsPJq2SC6f30EiDECVc2wQMwp6prnBTOfInuDiPpHx3IBW"; //6281554215822
			curl_setopt($curl, CURLOPT_HTTPHEADER,
			array(
				"Authorization: $token",
				"Content-Type: application/json"
				)
			);
	
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($payload) );
		curl_setopt($curl, CURLOPT_URL, "https://solo.wablas.com/api/v2/send-message");
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
	 
		$result = curl_exec($curl);
		curl_close($curl);
		//Bagian kirim WA
	break;
}


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
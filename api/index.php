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
	$sqlMy = "CALL sp_vehicle('GET_ID', 'device', 'route', 'param', 1, 2, 3, 4, 5, 6, 7, 8)";
	$res = mysqli_query($connMy,$sqlMy);
	$rec = mysqli_fetch_array($res,MYSQLI_ASSOC);
	echo "ID: ".$rec['id']."  ".$rec['device_name']." ==== url: ".$rec['device_url']; 
return;
}

create_log($data);
$sqlMy = "INSERT INTO api_log (id, json_task) VALUES(REPLACE(UUID(),'-',''),'".json_encode($data)."')";
mysqli_query($connMy,$sqlMy);

$task = $data['task'];
$device = $data['device'];
$route = $data['route'];
$plat = $data['plat'];
$plon = $data['plon'];
$param = $data['param'];
$pspeed = $data['pspeed'];
$ptrack = $data['ptrack'];
$dista = $data['dista'];
$distb = $data['distb'];
$dist040 = $data['dist040'];
$dist112 = $data['dist112'];


switch ($task) {
	case "TX_REGISTER":
	case "TX_LOG":
	case "BEACON":	
		$sqlMy = "CALL sp_vehicle('".$task."','".$device."','".$route."','".$param."',".$plat.",".$plon.",".$pspeed.",".$ptrack.",".$dista.",".$distb.",".$dist040.",".$dist112.")";
		mysqli_query($connMy,$sqlMy);
		create_log($sqlMy);
	break;

	case "REQ_DUEL":
		$sqlMy = "CALL sp_vehicle('".$task."','".$device."','".$route."','".$param."',".$plat.",".$plon.",".$pspeed.",".$ptrack.",".$dista.",".$distb.",".$dist040.",".$dist112.")";
		mysqli_query($connMy,$sqlMy);
		create_log($sqlMy);

		$sqlMy = "SELECT * FROM mst_device WHERE id='".$device."'";
		$resMy = mysqli_query($connMy,$sqlMy);
		if (!$resMy) create_log("Failed ".mysqli_error($connMy)." <> ".$sqlMy);
		$rec=mysqli_fetch_array($resMy,MYSQLI_ASSOC);
		$row_cnt = mysqli_num_rows($resMy);
		if ($row_cnt == 0) {
			echo "Simpang not found";
			exit;
		}

		$device_url = $rec['device_url'];
		$payload = array(	 
			'task' => 'INS_DUEL',
			'device' => $device,
			'route' => $route,
			'param' => $param,          
			'plat' => $plat,
			'plon' => $plon,
			'pspeed' => $pspeed,
			'ptrack' => $ptrack,
			'dista' => $dista,
			'distb' => $distb,
			'dist040' => $dist040,
			'dist112' => $dist112
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

	case "INS_DUEL":	
		$sqlMy = "CALL sp_vehicle('".$task."','".$device."','".$route."','".$param."',".$plat.",".$plon.",".$pspeed.",".$ptrack.",".$dista.",".$distb.",".$dist040.",".$dist112.")";
		mysqli_query($connMy,$sqlMy);
		create_log($sqlMy);
		if ($device == 'RXS112') {
			switch ($route) {
				case "RAJAWALI_SELATAN":	
					$port1 = "OFF";
					$port2 = "ON";
					$port3 = "ON";
					$port4 = "ON";
				break;
				case "RAJAWALI_TENGAH":	
					$port1 = "ON";
					$port2 = "ON";
					$port3 = "ON";
					$port4 = "ON";
				break;
				case "PURABAYA_TENGAH":	
					$port1 = "ON";
					$port2 = "OFF";
					$port3 = "ON";
					$port4 = "ON";
				break;
				case "PURABAYA_SELATAN":	
					$port1 = "ON";
					$port2 = "ON";
					$port3 = "ON";
					$port4 = "ON";
				break;
			}
		}

		else if ($device == 'RXS040') {
			switch ($route) {
				case "RAJAWALI_TENGAH":	
					$port1 = "OFF";
					$port2 = "ON";
					$port3 = "ON";
					$port4 = "ON";
				break;
				case "RAJAWALI_UTARA":	
					$port1 = "ON";
					$port2 = "ON";
					$port3 = "ON";
					$port4 = "ON";
				break;
				case "PURABAYA_UTARA":	
					$port1 = "OFF";
					$port2 = "ON";
					$port3 = "ON";
					$port4 = "ON";
				break;
				case "PURABAYA_TENGAH":	
					$port1 = "ON";
					$port2 = "ON";
					$port3 = "ON";
					$port4 = "ON";
				break;
			}
		}

		$payload = array(	 
			'task' => 'EXE_RELAY',
			'device' => $device,
			'route' => $route,
			'param' => $param,          
			'port1' => $port1,
			'port2' => $port2,
			'port3' => $port3,
			'port4' => $port4
		); 

		create_log($payload);


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
	
		if( ! socket_send ( $sock , json_encode($payload) , strlen(json_encode($payload)) , 0)){
			$errorcode = socket_last_error();
			$errormsg = socket_strerror($errorcode);
			die("Could not send data: [$errorcode] $errormsg \n");
		}
		socket_close($sock);


		//Bagian kirim WA
		if( strpos(json_encode($payload), "OFF")) {
			$pesan = "AKTIFFFFFF ".json_encode($payload) ;
		}
		else {
			$pesan = "NORMAL ".json_encode($payload) ;
		}
		$data_array=array();
		$data = array(
			'phone' => "6281234561013",
			//'message' => json_encode($payload),
			'message' => $pesan,
		);
		array_push($data_array,$data);
		/*
		$data = array(
			'phone' => "628113361629",
			'message' => json_encode($payload),
		);
		array_push($data_array,$data);
		*/
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
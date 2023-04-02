<?php
    $parameter = $_GET['bis'];
    $bus_id = "TXR10".$parameter;

    echo "Bus ".$parameter." ".$bus_id. "<br>";

    /*Connection to MySQL DB*/
    $connMy= mysqli_connect('localhost','vehicle','DBvehicle');
    if ($connMy) {
        mysqli_select_db($connMy,'vehicle_db');
    } else create_log("Failed to connect MySQL DB");

    $sqlMy = "SELECT * FROM vw_location WHERE device_id='".$bus_id."' AND pos_latitude<>0 LIMIT 1";
    $resMy = mysqli_query($connMy,$sqlMy);
    if (!$resMy) create_log("Failed ".mysqli_error($connMy)." <> ".$sqlMy);		
    $rec=mysqli_fetch_array($resMy,MYSQLI_ASSOC);
    $latitude = $rec['pos_latitude'];
    $longitude = $rec['pos_longitude'];
    $last_update = $rec['created_time'];
    echo $last_update;

	?>

	<iframe width="100%" height="100%" src="https://maps.google.com/maps?q=<?php echo $latitude; ?>,<?php echo $longitude; ?>&output=embed"></iframe>

	<?php
?>
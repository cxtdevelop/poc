<?php

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

$message = "Helloooooooooo";
//$message = $task.",".$vehicle.",".$route.",".$latitude.",".$longitude.",".$speed.",".$track.",".$destination.",";
//Send the message to the server
if( ! socket_send ( $sock , $message , strlen($message) , 0)){
    $errorcode = socket_last_error();
    $errormsg = socket_strerror($errorcode);
    die("Could not send data: [$errorcode] $errormsg \n");
}
socket_close($sock);	

echo "Send message"
?>

#!/bin/bash

param1=$1
param2=$2

result=$(mysql -u vehicle -pDBvehicle -D vehicle_db -e "SELECT device_url FROM mst_device WHERE id='CTR101'")
ControlIP=${result:11:50}
echo $ControlIP
result=$(mysql -u vehicle -pDBvehicle -D vehicle_db -e "SELECT id, device_name FROM mst_device WHERE is_active='1'")
DeviceID=${result:15:6}
#echo $result
echo $DeviceID
DeviceName=${result:22:50}
echo $DeviceName

if [ "$param1" == "beacon" ] || ([ "$param1" == "apps" ] || [ "$param1" == "gps" ]); then
   echo $param1
   result=$(mysql -u vehicle -pDBvehicle -D vehicle_db -e "UPDATE vps_reference SET value='$param2' WHERE id='$param1'")
   if [ "$param2" == "0" ]; then
      echo $param2
      killall python
      pkill -9 -e -f gpspipe.sh
   fi
fi

if [ "$param1" == "lan" ]; then
   eval "network='`ifconfig | egrep 'eth0|wlan0|broadcast'`'"
   echo -e "Network Interface Card :\n$network"
   eval "wifi='`iwgetid`'"
   echo -e "SSID :\n$wifi"
fi

result=$(mysql -u vehicle -pDBvehicle -D vehicle_db -e "SELECT value FROM vps_reference WHERE id='BEACON'")
value=${result:6:1}
echo $result   
if [ "$value" == "1" ]; then
   payload='{"task":"BEACON",
            "device":"'"$DeviceID"'",
            "route":"'"$DeviceName"'",
            "param":"0",
            "plat":"0",
            "plon":"0",
            "pspeed":"0",
            "ptrack":"0",
            "dista":"0",
            "distb":"0",
            "dist040":"0",
            "dist112":"0"}'
    curl -d "${payload}" -i $ControlIP
fi

result=$(mysql -u vehicle -pDBvehicle -D vehicle_db -e "SELECT value FROM vps_reference WHERE id='APPS'")
value=${result:6:1}
echo $result
if [ "$value" == "1" ]; then
   if pgrep python >/dev/null 2>&1; then
      echo "Application already running..."
      else
         (python /home/vehicle/poc/devproc.py) &	
          echo "Application started...."
   fi
fi

result=$(mysql -u vehicle -pDBvehicle -D vehicle_db -e "SELECT value FROM vps_reference WHERE id='GPS'")
value=${result:6:1}
echo $result
if [ "$value" == "1" ]; then
   if pgrep gpspipe.sh 2>&1; then
      echo "GPSPIPE already running..."
      else
         echo "GPS started...."
         /home/vehicle/poc/gpspipe.sh

   fi
fi

#Normalisasi
result=$(mysql -u vehicle -pDBvehicle -D vehicle_db -e "SELECT TIMESTAMPDIFF(SECOND, created_time, NOW()) VALUE FROM api_log WHERE json_task NOT LIKE '%LOCALHOST%' ORDER BY created_time DESC LIMIT 1")
value=${result:6:3}
echo $value
if [ "$value" -ge 60 ] && [ "$value" -le 300 ]; then
   echo "send LC...."
   payload='{"task":"INS_DUEL",
            "device":"'"$DeviceID"'",
            "route":"LOCALHOST",
            "param":"0",
            "plat":"0",
            "plon":"0",
            "pspeed":"0",
            "ptrack":"0",
            "dista":"0",
            "distb":"0",
            "dist040":"0",
            "dist112":"0"}'
   curl -d "${payload}" -i http://127.0.0.1/api/
fi
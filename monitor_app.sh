#!/bin/bash

param1=$1
param2=$2

if [ "$param1" != "" ]; then
   echo $param1
   result=$(mysql -u vehicle -pDBvehicle -D vehicle_db -e "UPDATE vps_reference SET value='$param2' WHERE id='$param1'")
   if [ "$param2" == "0" ]; then
      echo $param2
      killall python
      pkill -9 -e -f gpspipe.sh
   fi
fi

result=$(mysql -u vehicle -pDBvehicle -D vehicle_db -e "SELECT value FROM vps_reference WHERE id='BEACON'")
value=${result:6:1}
echo $result   
if [ "$value" == "1" ]; then
   echo "Send Beacon"
   curl -k http://localhost/api/beacon.php
fi


result=$(mysql -u vehicle -pDBvehicle -D vehicle_db -e "SELECT value FROM vps_reference WHERE id='APPS'")
value=${result:6:1}
echo $result
if [ "$value" == "1" ]; then
   if pgrep python >/dev/null 2>&1; then
      echo "Application already running..."
      else
         (python /home/vehicle/vetrans.py) &	
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
         (/home/vehicle/gpspipe.sh) &	
          echo "GPS started...."
   fi
fi

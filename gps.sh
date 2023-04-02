#!/bin/bash

macVal=$(cat /sys/class/net/eth0/address|tr -d :|tr [a-z] [A-Z])
loop=1
while [ $loop -lt 10 ]; do

##gpsDeviceVal=$(ls /dev/ttyACM*)
#gpsDeviceVal=$?
# case  "$gpsDeviceVal" in
#	0)
#  	pingVal=$(echo "gpsLife")
#	;;
#	1)  
#	pingVal=$(echo "gpsDown")
#	;;
# esac
# echo $pingVal

gpspipeVal=$(gpspipe -w|grep -m 1 TPV)
gpsTime=$(echo $gpspipeVal|cut -d',' -f5|cut -d'"' -f4|tr -d 'Z')
gpsTPVVal=$(echo $gpspipeVal|tr -d {}|tr ',' '&'|tr -d '"'|tr ':' '='|cut -d '&' -f1,2,3,6,7,8,9,10,11,12,13,14,15)

echo "123.108.10.200/konimexbi2/getparam.php?IMEI=${macVal}&GPSTime=${gpsTime}&${gpsTPVVal}"

#gpsCatRMCVal=$(cat -vt /dev/ttyACM1|grep -m 2 '$GPRMC')
#gpsCatVTGVal=$(cat /dev/ttyACM1|grep -m 1 VTG)

#gpsTime=$(echo $gpsCatVal|grep RMC|cut -d',' -f5|cut -d'"' -f4|tr -d 'Z')
#echo $gpsCatRMCVal
#echo $gpsCatVTGVal
#echo $gpsTime


#gpsTPVVal=$(echo $gpspipeVal|tr -d {}|tr ',' '&'|tr -d '"'|tr ':' '='|cut -d '&' -f1,2,3,6,7,8,9,10,11,12,13,14,15)
#echo $gpsTPVVal

curlVal=$(curl -v "123.108.10.200/konimexbi2/getparam.php?IMEI=${macVal}&GPSTime=${gpsTime}&${gpsTPVVal}")
curlVal=$(curl -v "123.108.10.53/blackbox/index.php?IMEI=${macVal}&GPSTime=${gpsTime}&${gpsTPVVal}")
echo $curlVal
sleep 5
done

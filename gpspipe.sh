#!/bin/bash
while :
do
    gpspipeVal=$(gpspipe -w|grep -m 1 TPV)
    echo "============================================================================================================"
    #gpsTime=$(echo $gpspipeVal|cut -d',' -f4|cut -d'"' -f4|tr -d 'Z')
    gpsTime=$(echo "$gpspipeVal" | jq -r '.time')
    #gpsLat=$(echo $gpspipeVal|cut -d',' -f7|cut -d':' -f2)
    gpsLat=$(echo "$gpspipeVal" | jq -r '.lat')
    gpsLon=$(echo "$gpspipeVal" | jq -r '.lon')
    gpsTrack=$(echo "$gpspipeVal" | jq -r '.track')
    gpsSpeed=$(echo "$gpspipeVal" | jq -r '.speed')

    echo "Status on" $gpsTime "  lat:" $gpsLat "  lon:" $gpsLon "  speed:" $gpsSpeed "  track:" $gpsTrack

    gpsState='{"task":"TX_PIPE",
            "dtime":"'"$gpsTime"'",
            "dlat":"'"$gpsLat"'",
            "dlon":"'"$gpsLon"'",
            "dspeed":"'"$gpsSpeed"'",
            "dtrack":"'"$gpsTrack"'"
            }'

    echo $gpsState | nc 127.0.0.1 8090
    echo $gpsState 

    gpsSum='{"task":"TX_PIPE",
            "vehicle": "PIPE",
            "route": "0",
            "data_latitude":"'"$gpsLat"'",
            "data_longitude":"'"$gpsLon"'",
            "data_speed":"'"$gpsSpeed"'",
            "data_track":"'"$gpsTrack"'",
            "destination":"0"}'
#    curl -d "${gpsSum}" -i http://127.0.0.1/api/
    sleep 3 
done
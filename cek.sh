#!/bin/bash

eval "network='`ifconfig | egrep 'eth0|wlan0|broadcast'`'"
echo -e "Network Interface Card :\n$network"
eval "wifi='`iwgetid`'"
echo -e "SSID :\n$wifi"




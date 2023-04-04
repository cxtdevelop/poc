import requests
import json
import datetime
import mysql.connector
import socket
import logging
import time
from math import sin, cos, sqrt, atan2, radians
from gps import *
from gpiozero import LED
from sys import platform

# Create and configure logger
logging.basicConfig(filename = "/home/vehicle/poc/log/" + datetime.datetime.today().strftime('%Y%m%d') + ".log", 
                    format = '%(asctime)s : %(message)s', 
                    filemode = 'a',
                    level = logging.WARNING)
console = logging.StreamHandler()
logging.getLogger('').addHandler(console)

#Setting varibael
local_url = 'http://127.0.0.1/api/'
DistanceAOld1 = 0
DistanceBOld1 = 0



def sendapi(api_url,payload):
  try:
    requests.post(api_url, data=json.dumps(payload), timeout=2)
    logging.warning("Success connect to : %s " % (api_url))
  except requests.Timeout:
    logging.warning("Timeout connect to : %s " % (api_url)) # back off and retry
    pass
  except requests.ConnectionError:
    logging.warning("Connection Error to : %s " % (api_url))
    pass


def getDistance(lati1, long1, lati2, long2):
  R = 6373000       # approximate radius of earth in m
  lat1 = radians(lati1)
  lon1 = radians(long1)
  lat2 = radians(lati2)
  lon2 = radians(long2)
  dlon = lon2 - lon1
  dlat = lat2 - lat1
  a = sin(dlat / 2)**2 + cos(lat1) * cos(lat2) * sin(dlon / 2)**2
  c = 2 * atan2(sqrt(a), sqrt(1 - a))
  resDistance = R * c
  return resDistance


#######Start Main Code ################################################################################################
#MySQL connect
db = mysql.connector.connect(host="localhost", user="vehicle", passwd="DBvehicle", db="vehicle_db")
dbcon = db.cursor()

#Get Server Control ID ########################
dbcon.callproc('sp_vehicle', ['GET_SERVER', 'device', 'route', 'param', 1, 2, 3, 4, 5, 6, 7, 8])
for result in dbcon.stored_results():
  records = result.fetchall()
  for record in records:
    control_url = record[2]
logging.warning("Got Server on : %s " % (control_url))

#Get Device ID ########################
dbcon.callproc('sp_vehicle', ['GET_ID', 'device', 'route', 'param', 1, 2, 3, 4, 5, 6, 7, 8])
for result in dbcon.stored_results():
  records = result.fetchall()
  for record in records:
    deviceID = record[0]
    deviceName = record[1]
logging.warning("Starting: %s === : %s on %s" % (deviceID, deviceName, sys.platform))

#Get Simpang Profile ########################
sparam = 40
dbcon.callproc('sp_vehicle', ['GET_SIMPANG', 'device', 'route', sparam, 1, 2, 3, 4, 5, 6, 7, 8])
for result in dbcon.stored_results():
  records = result.fetchall()
  for record in records:
    rx040_name = record[1]
    rx040_latitude = record[2]
    rx040_longitude = record[3]
    rx040_phase_ab = record[5]
    rx040_phase_ba = record[6]
    rx040_url = record[7]

sparam = 112
dbcon.callproc('sp_vehicle', ['GET_SIMPANG', 'deviceID', 'route', sparam, 1, 2, 3, 4, 5, 6, 7, 8])
for result in dbcon.stored_results():
  records = result.fetchall()
  for record in records:
    rx112_name = record[1]
    rx112_latitude = record[2]
    rx112_longitude = record[3]
    rx112_phase_ab = record[5]
    rx112_phase_ba = record[6]
    rx112_url = record[7]
  
## Code for Transmitter / Bus Tayo  ################################################################################################
routeID = deviceID[:4]
if routeID in ['TXR1', 'TXR2', 'DTR1']:
  dbcon.callproc('sp_vehicle', ['GET_ROUTE', 'deviceID', routeID, 'param', 1, 2, 3, 4, 5, 6, 7, 8])
  for result in dbcon.stored_results():
    records = result.fetchall()
    for record in records:
      a_name = record[2]
      a_latitude = record[3]
      a_longitude = record[4]
      b_name = record[5]
      b_latitude = record[6]
      b_longitude = record[7]
  DistanceAR040 = int(getDistance(a_latitude, a_longitude, rx040_latitude, rx040_longitude))
  DistanceAR112 = int(getDistance(a_latitude, a_longitude, rx112_latitude, rx112_longitude))
  DistanceBR040 = int(getDistance(b_latitude, b_longitude, rx040_latitude, rx040_longitude))
  DistanceBR112 = int(getDistance(b_latitude, b_longitude, rx112_latitude, rx112_longitude))  
  logging.warning("Distance A terminal %s to %s = %s ; to %s = %s" % (a_name, rx112_name, DistanceAR112, rx040_name, DistanceAR040))
  logging.warning("Distance B terminal %s to %s = %s ; to %s = %s" % (b_name, rx112_name, DistanceBR112, rx040_name, DistanceBR040))
  payload = {
    'task':'TX_REGISTER',
    'device': deviceID,
    'route': deviceName,
    'param':'',
    'plat': 0,
    'plon': 0,
    'pspeed': 0,
    'ptrack': 0,
    'dista': 0,
    'distb': 0,
    'dist040': 0,
    'dist112': 0
  }
  sendapi(control_url, payload)
  sendapi(local_url, payload)
  

  
#Code for Receiver / LC Simpang######################################################################################
if deviceID[:2] in ['RX']:
  phase1 = LED(27) 
  phase2 = LED(22) 
  phase3 = LED(23) 
  phase4 = LED(24)
  phase1.on()
  phase2.on()
  phase3.on()
  phase4.on()

# Create socket and bind it to TCP address &amp; port
server_socket = socket.socket()    # Create a socket object
host = 'localhost'                 # Get local machine name
port = 8090                        # Reserve a port for your service.
server_socket.bind((host, port))   # Bind to the port

try:
  while True:
    logging.warning("========================================================")  
    logging.warning("%s %s Waiting task on port : %s " % (deviceID, deviceName, port))
    server_socket.listen(3)                 # Now wait for client connection.
    conn, address = server_socket.accept()  # accept new connection
    Msg = conn.recv(1024).decode()
    if not Msg:         
      break # if data is not received break
      
    conn.close()  # close the connection
    MsgLen = len(Msg)
    logging.warning("Receive: %s <=> Length: %s  " % (Msg, MsgLen))
    sock_data = (json.loads(Msg))
    task = sock_data["task"]

    if task in ['TX_PIPE']: ########## Receive Data from GPS
      dtime = sock_data["dtime"] 
      if sock_data["dlat"] in ['null']:
        dlat = 0
      else:
        dlat = float(sock_data["dlat"])

      if sock_data["dlon"] in ['null']:
        dlon = 0
      else:
        dlon = float(sock_data["dlon"])
        
      dspeed = sock_data["dspeed"]
      dtrack = sock_data["dtrack"] 
      logging.warning("%s location at latitude : %s ; longitude : %s With speed : %s & Track : %s" % (dtime, dlat, dlon, dspeed, dtrack))
      
      DistanceANew = int(getDistance(a_latitude, a_longitude, dlat, dlon))
      DistanceBNew = int(getDistance(b_latitude, b_longitude, dlat, dlon))
      DistanceR040New = int(getDistance(rx040_latitude, rx040_longitude, dlat, dlon))
      DistanceR112New = int(getDistance(rx112_latitude, rx112_longitude, dlat, dlon))
      #logging.warning("=====================================================================")
      logging.warning("Distance to cek point : %s : %s ; %s : %s ; %s : %s ; %s : %s" 
                      % (a_name, DistanceANew, rx112_name, DistanceR112New, rx040_name, DistanceR040New, b_name, DistanceBNew))
      
      if ((DistanceANew - DistanceAOld1) > 10) or ((DistanceBNew - DistanceBOld1) > 10): # Ada pergerakan 
        logging.warning("Bergerak >>>>>>>>>>>>")
        DistanceAOld2 = DistanceAOld1 
        DistanceAOld1 = DistanceANew
        DistanceBOld2 = DistanceBOld1 
        DistanceBOld1 = DistanceBNew
        DistanceR040Old2 = DistanceR040Old1
        DistanceR040Old1 = DistanceR040New
        DistanceR112Old2 = DistanceR112Old1
        DistanceR112Old1 = DistanceR112New

        if DistanceANew <= DistanceAR112:
          TayoLocation = "SELATAN"
        elif DistanceANew > DistanceAR040:
          TayoLocation = "UTARA"  
        elif (DistanceANew < DistanceAR040) and (DistanceANew > DistanceAR112):
          TayoLocation = "TENGAH"
        else:
          TayoLocation = "Unknown"

        if DistanceAOld2 == 0:
          TayoDirection = "Unknown"
        elif DistanceAOld1 > DistanceAOld2:
          TayoDirection = "RAJAWALI"
        else:
          TayoDirection = "PURABAYA"
          
        TayoDestination = TayoDirection + "_" + TayoLocation


        if (DistanceR112New < 200): #mendekati simpang bengawan
          payload = {
            'task':'REQ_DUEL',
            'device': 'RXS112',
            'route': TayoDestination,
            'param': deviceID,
            'plat': dlat,
            'plon': dlon,
            'pspeed': dspeed,
            'ptrack': dtrack,
            'dista': DistanceANew,
            'distb': DistanceBNew,
            'dist040': DistanceR040New,
            'dist112': DistanceR112New
          }
          sendapi(control_url, payload)

        if (DistanceR040New < 200): #mendekati simpang pandegiling
          payload = {
            'task':'REQ_DUEL',
            'device': 'RXS040',
            'route': TayoDestination,
            'param': deviceID,
            'plat': dlat,
            'plon': dlon,
            'pspeed': dspeed,
            'ptrack': dtrack,
            'dista': DistanceANew,
            'distb': DistanceBNew,
            'dist040': DistanceR040New,
            'dist112': DistanceR112New
          }
          sendapi(control_url, payload)
        logging.warning("Distance from A terminal %s = %s => %s => %s " % (a_name, DistanceANew, DistanceAOld1, DistanceAOld2))
        logging.warning("Distance from B terminal %s = %s => %s => %s " % (b_name, DistanceBNew, DistanceBOld1, DistanceBOld2))
        logging.warning("Distance from %s = %s => %s => %s" % (rx112_name, DistanceR112New, DistanceR112Old1, DistanceR112Old2))
        logging.warning("Distance from %s = %s => %s => %s " % (rx040_name, DistanceR040New, DistanceR040Old1, DistanceR040Old2))
        logging.warning("Destination = %s " % (TayoDestination))  

      else:
        logging.warning("XXXXX Berhenti XXXXXXX")
        
      payload = {
          'task':'TX_LOG',
          'device': deviceID,
          'route': TayoDestination,
          'param': '',          
          'plat': dlat,
          'plon': dlon,
          'pspeed': dspeed,
          'ptrack': dtrack,
          'dista': DistanceANew,
          'distb': DistanceBNew,
          'dist040': DistanceR040New,
          'dist112': DistanceR112New
        }
      sendapi(control_url, payload)
   
    elif task in ['EXE_RELAY']: ########## Receive Data from Controller to Relay
      lcsimpang =  sock_data["device"]
      port1 = sock_data["port1"]
      port2 = sock_data["port2"]
      port3 = sock_data["port3"]
      port4 = sock_data["port4"]
      logging.warning("Receive order %s ==> DUEL : %s : %s : %s : %s" % (lcsimpang, port1, port2, port3, port4))

      if lcsimpang == deviceID:
        logging.warning("Execute order %s ==> DUEL : %s : %s : %s : %s" % (lcsimpang, port1, port2, port3, port4))
        if port1 == "OFF":
          phase1.off()
        else:
          phase1.on()

        if port2 == "OFF":
          phase2.off()
        else:
          phase2.on()

        if port3 == "OFF":
          phase3.off()
        else:
          phase3.on()

        if port4 == "OFF":
          phase4.off()
        else:
          phase4.on()

      else:
        logging.warning("Mismatch ID %s <==> %s " % (lcsimpang, deviceID))

    elif task in ['EXIT']:
      exit()
      
    else:
      logging.warning(">>>Not for me ")         


except KeyboardInterrupt:
  print("Oh! you pressed CTRL + C.")
  print("Program interrupted.")

finally:
  print("This was an important code, ran at the end.")


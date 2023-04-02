from gps import *
import time

running = True

curr_latitude = 0
curr_longitude = 0
curr_speed = 0
curr_track = 0
curr_route = 0


def getPositionData(gps):
  global curr_latitude
  global curr_longitude
  global curr_speed
  global curr_track
  nx = gpsd.next()
  # For a list of all supported classes and fields refer to:
  # https://gpsd.gitlab.io/gpsd/gpsd_json.html
  if nx['class'] == 'TPV':
    curr_latitude = getattr(nx,'lat', "Unknown")
    curr_longitude = getattr(nx,'lon', "Unknown")
    #print ("Your position: latitude = " + str(latitude) + ", longitude = " + str(longitude))

gpsd = gps(mode=WATCH_ENABLE|WATCH_NEWSTYLE)

try:
    print ("Application started!")
    while running:
        getPositionData(gpsd)
        print ("Your position: latitude = " + str(curr_latitude) + ", longitude = " + str(curr_longitude))
        time.sleep(1.0)

except (KeyboardInterrupt):
    running = False
    print ("Applications closed!")

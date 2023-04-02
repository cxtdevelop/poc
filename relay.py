from gpiozero import LED 
from time import sleep 

phase1 = LED(27) 
phase2 = LED(22) 
phase3 = LED(23) 
phase4 = LED(24) 
 
while True: 
  phase1.on()    #turn led on
  print ("phase1.on()") 
  sleep(5)    
  phase1.off()   #turn led off 
  print ("phase1.off()")
  sleep(5)
  phase2.on()    #turn led on
  print ("phase2.on()") 
  sleep(5)    
  phase2.off()   #turn led off 
  print ("phase2.off()")
  sleep(5)
  phase3.on()    #turn led on
  print ("phase3.on()") 
  sleep(5)    
  phase3.off()   #turn led off 
  print ("phase3.off()")
  sleep(5)
  phase4.on()    #turn led on
  print ("phase4.on()") 
  sleep(5)    
  phase4.off()   #turn led off 
  print ("phase4.off()")
  sleep(5)
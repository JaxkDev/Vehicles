Things to get done
--

Files to rewrite:
- [x] Main.php 
- [x] VehicleType.php
- [ ] Factory.php
- [ ] VehicleBase.php
- [ ] Vehicle.php


Vehicle NBT Structure (not including base entity data):

```
CompoundTag EntityNBT
 - vehicle(int) (Vehicle Version)
 - VehicleData(CompundTag)
   - type(int)
   - name(string)
   - design(string)
   - gravity(double)
   - forwardSpeeed(double)
   - backwardSpeed(double)
   - leftSpeed(double)
   - rightSpeed(double)
   - bbox(ListTag)
     - x(float)
     - y(float)
     - z(float)
     - x2(float)
     - y2(float)
     - z2(float)
   - driverSeat(ListTag)
     - x(float)
     - y(float)
     - z(float)
   - passengerSeats(ListTag)
     - (ListTag) x How many seats there are
       - x(float)
       - y(float)
       - z(float)
```
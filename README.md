# Vehicles
[WIP] A [PocketMine-MP](https://github.com/pmmp/PocketMine-MP) plugin that brings vehicles to your server, made by [JaxkDev](https://github.com/JaxkDev)

#### Default Vehicles:
- Basic-Car

# NOTICE !
This is a experimental plugin and is not for performance and 100% not the best way to do such plugin but this is great experience for myself.


# For Developers
Feel free to add custom vehicle classes in another plugin to customise it even more (only so much you can do in a config) or implement your own system on top of the current logic.

Vehicle NBT Structure (not including base entity data):

```
CompoundTag EntityNBT
 - vehicle(int) (Vehicle Version)
 - VehicleData(CompundTag)
   - type(int)
   - name(string)
   - design(string)
   - gravity(float)
   - scale(float)
   - baseOffset(float)
   - forwardSpeeed(float)
   - backwardSpeed(float)
   - leftSpeed(float)
   - rightSpeed(float)
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

Events will be added in the near future.

# License:
Code provided is licensed under the Open Software License ("OSL") v. 3.0
The default resource's license can be found in the resources folder.

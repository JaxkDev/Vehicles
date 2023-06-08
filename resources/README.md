Vehicle data layouts.
--
Basic layout of the directory is as follows:

```
plugin_data
 - Vehicles
   - Vehicles
     This directory holds all the car data such as speed name,
     Design to link to etc.

   - Designs
     This directory holds all the designs geo data and skins.
```

---

Layout of the Vehicles data (`plugin_data/Vehicles/Vehicles/*.json`):

A skeleton file for this data can be found at `plugin_data/Vehicles/skeleton.json`

(`//` Comments cannot be in the .json they are used here just for help in pointing things out to the user)

```
{
  "name": "VehicleName", // No spaces in vehicle name.
  "design": "Design Name", // Must be in design_manifest scroll down for info on that.
  "type": 0, // 0 = Land, 1 = Water, 2 = Air, 3 = Rail, 9 = Unknown
  "version": 1, // <-- The minor version the vehicle supports, Vehicles v0.1.x = 1, v0.2.x = 2 etc.
  "scale": 1.0, // Specify scale of entity, useful when modelling small.
  "baseOffset": 1.0 // Specify the base offset of the vehicle

  "seatPositions": {
    "driver": [0.55, -0.4, 0.1], // <-- X,Y,Z position of seat in relation to scale + design (requires playing around with)
    "passengers": [[-0.55, -0.4, 0.1]] // <-- Same as above but another array for holding multiple seats. eg [[X,Y,Z],[X,Y,Z],[X,Y,Z]]
  },

  "BBox": [0,0,0,1,1,1],

  "gravity": 1.0,  // Self explanatory i hope.

  //The below are just the speeds for forward, back, left, right.
  "speedMultiplier": {
    "forward": 2.5,
    "backward": 1.5
  },

  "directionMultiplier": {
    "left": 6,
    "right": 6
  }
}
```

---

Layout of skin (`plugin_data/Vehicles/Designs/*.png | *.json`):

Skin can either be in generated json (for those with no GD extension)
or be in the simple form of a png.

---

Geometry data (`plugin_data/Vehicles/Designs/SkinName_Geometry.json`):

As shown above the geometry file is a specific file name, `SkinName_Geometry.json` this is to reduce clutter in the manifest.

For more info on making your own vehicles please refer to the [wiki](https://github.com/JaxkDev/Vehicles/Wiki) (Coming soon)

---

Layout of Design Manifest (`plugin_data/Vehicles/Designs/Design_Manifest.json`):

```
[
  {
    "name": "BasicCar", // Name of the design (file name for the png must be same)
    "uuid": "73192065-b069-4e15-897a-b6af73dbb5bd" // Random UUID.
  },
  {
    "name": "SecondCar", // Multiple designs can be added.
    "uuid": "1c22b31c-0bf8-4abb-8562-350a7bc78267"
  }
]
```
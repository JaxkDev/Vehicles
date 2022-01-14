<?php
/*
 * Vehicles, PocketMine-MP Plugin.
 *
 * Licensed under the Open Software License version 3.0 (OSL-3.0)
 * Copyright (C) 2019-present JaxkDev
 *
 * Twitter :: @JaxkDev
 * Discord :: JaxkDev#2698
 * Email   :: JaxkDev@gmail.com
 */

declare(strict_types=1);

namespace JaxkDev\Vehicles;

use DirectoryIterator;

use pocketmine\world\World;
use pocketmine\entity\Skin;
use pocketmine\entity\Location;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\entity\EntityFactory;
use pocketmine\plugin\PluginException;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\InvalidSkinException;
use pocketmine\network\mcpe\protocol\types\skin\SkinData;
use pocketmine\network\mcpe\protocol\types\skin\SkinImage;

use JaxkDev\Vehicles\Exceptions\InvalidDesignException;
use JaxkDev\Vehicles\Exceptions\VehicleException;

class Factory{
	/** @var Main */
	private $plugin;

	/** @var array<string, SkinData> */
	private $designs = [];

	/** @var array<string, mixed[]>*/
	private $vehicles = [];

	public function __construct(Main $plugin)
	{
		EntityFactory::getInstance()->register(Vehicle::class, function(World $world, CompoundTag $nbt): Vehicle{
			return new Vehicle(EntityDataHelper::parseLocation($nbt, $world), $nbt);
		}, ["Vehicle"]);
		$this->plugin = $plugin;
	}

	/**
	 * Spawns a vehicle, with specified data.
	 * @param mixed[] $vehicleData
	 * @param Location $loc
	 * @return Vehicle|null
	 */
	public function spawnVehicle($vehicleData, Location $loc): ?Vehicle{
		$passengerSeats = new ListTag();
		foreach($vehicleData["seatPositions"]["passengers"] as $seat){
			$passengerSeats->push(new ListTag([
				new FloatTag($seat[0]),
				new FloatTag($seat[1]),
				new FloatTag($seat[2])
			]));
		}

		$vehicleNBT = new CompoundTag();
		$vehicleNBT->setInt("type", $vehicleData["type"])
			->setString("name", $vehicleData["name"])
			->setString("design", $vehicleData["design"])
			->setFloat("gravity", $vehicleData["gravity"])
			->setFloat("scale", $vehicleData["scale"])
			->setFloat("gravity", $vehicleData["gravity"])
			->setFloat("scale", $vehicleData["scale"])
			->setFloat("baseOffset", $vehicleData["baseOffset"])
			->setFloat("forwardSpeed", $vehicleData["speedMultiplier"]["forward"])
			->setFloat("backwardSpeed", $vehicleData["speedMultiplier"]["backward"])
			->setFloat("leftSpeed", $vehicleData["directionMultiplier"]["left"])
			->setFloat("rightSpeed", $vehicleData["directionMultiplier"]["right"])
			->setTag("bbox", new ListTag([
				new FloatTag($vehicleData["BBox"][0]),
				new FloatTag($vehicleData["BBox"][1]),
				new FloatTag($vehicleData["BBox"][2]),
				new FloatTag($vehicleData["BBox"][3]),
				new FloatTag($vehicleData["BBox"][4]),
				new FloatTag($vehicleData["BBox"][5]),
			]))
			->setTag("driverSeat", new ListTag([
				new FloatTag($vehicleData["seatPositions"]["driver"][0]),
				new FloatTag($vehicleData["seatPositions"]["driver"][1]),
				new FloatTag($vehicleData["seatPositions"]["driver"][2]),
			]))
			->setTag("passengerSeats", $passengerSeats);

		$nbt = new CompoundTag();
		$nbt->setTag("vehicleData", $vehicleNBT)
			->setTag("Pos", new ListTag([
				new DoubleTag($loc->x),
				new DoubleTag($loc->y),
				new DoubleTag($loc->z)
			]))
			->setTag("Motion", new ListTag([
				new DoubleTag(0.0),
				new DoubleTag(0.0),
				new DoubleTag(0.0)
			]))
			->setTag("Rotation", new ListTag([
				new FloatTag(0.0),
				new FloatTag(0.0)
			]))
			->setInt("vehicle", Main::$vehicleDataVersion);

		$entity = new Vehicle($loc, $nbt);

		$this->plugin->getLogger()->debug("Spawning vehicle.");

		$entity->spawnToAll();

		$this->plugin->getLogger()->debug("Vehicle '{$vehicleData["name"]}' spawned.");

		return $entity;
	}

	/**
	 * Register all vehicles from plugin_data/Vehicles/Vehicles/*.json into memory.
	 * Can be used to reload data but with argument force being true to overwrite existing vehicles.
	 *
	 * @param bool $force
	 */
	public function registerVehicles($force = false): void{
		foreach(new DirectoryIterator($this->plugin->getDataFolder() . "Vehicles/") as $file){
			$fName = $file->getFilename();
			if($fName[0] === ".") continue;

			$path = $this->plugin->getDataFolder() . "Vehicles/{$fName}";
			$data = json_decode(file_get_contents($path), true);

			// Type Checks on data.
			if(($name = $data["name"] ?? null) === null) throw new VehicleException("{$fName} has no name specified.");

			if(($data["design"] ?? null) === null) throw new VehicleException("Vehicle {$name} in {$fName} has no design specified.");

			if(($data["type"] ?? null) === null) throw new VehicleException("Vehicle {$name} in {$fName} has no type specified.");

			if(($data["version"] ?? null) === null) throw new VehicleException("Vehicle {$name} in {$fName} has no version specified.");

			if(($data["scale"] ?? null) === null){
				$data["scale"] = 1.0;
				$this->plugin->getLogger()->warning("Vehicle {$name} in {$fName} has no scale specified, reverting to default of '1.0'");
			}

			if(($data["baseOffset"] ?? null) === null){
				$data["baseOffset"] = 1.0;
				$this->plugin->getLogger()->warning("Vehicle {$name} in {$fName} has no baseOffset specified, reverting to default of '1.0'");
			}

			if(($seatPositions = $data["seatPositions"] ?? null) === null) throw new VehicleException("Vehicle {$name} in {$fName} has no seat positions specified.");
			if(($seatPositions["driver"] ?? null) === null) throw new VehicleException("Vehicle {$name} in {$fName} has no driver seat position specified .");
			if(count($seatPositions["driver"]) !== 3) throw new VehicleException("Vehicle {$name} in {$fName} has an invalid driver seat position ( format: [X,Y,Z] )");
			if(($seatPositions["passengers"] ?? null) === null){
				$data["seatPositions"]["passengers"] = []; //Default
				$this->plugin->getLogger()->warning("Vehicle {$name} in {$fName} has no passenger seats, reverting to default of '[]'");
			} else{
				if(!is_array($seatPositions["passengers"]) || (
				count($seatPositions["passengers"]) !== 0 && 
				count($seatPositions["passengers"][0]) !== 3)) throw new VehicleException("Vehicle {$name} in {$fName} has invalid passenger seat positions ( format: [[x,y,z],[x,y,z] etc... ] ");
			}

			if(($data["BBox"] ?? null) === null){
				$data["BBox"] = [0,0,0,1,1,1];
				$this->plugin->getLogger()->warning("Vehicle {$name} in {$fName} has no BBox, reverting to default of '[0,0,0,1,1,1]'");
			} else if(!is_array($data["BBox"]) || count($data["BBox"]) !== 6) throw new VehicleException("Vehicle {$name} in {$fName} has a invalid BBox of '{$data["BBox"]}' (format: [x,y,z,x2,y2,z2])");


			if(($data["gravity"] ?? null) === null){
				$data["gravity"] = 1.0; //Default
				$this->plugin->getLogger()->warning("Vehicle {$name} in {$fName} has no gravity specified, reverting to default of '1.0'");
			} else if($data["gravity"] < 0) $this->plugin->getLogger()->warning("IMPORTANT, A gravity of < 1 can cause serious issues if not correctly handled (vehicle - {$name}).");

			if(($data["speedMultiplier"] ?? null) === null){
				$data["speedMultiplier"] = ["forward" => 1, "backward" => 1];
				$this->plugin->getLogger()->warning("Vehicle {$name} in {$fName} has no speedMultipliers, reverting to default of 'Forward = 1, Backward = 1'");
			} else {
				if(($data["speedMultiplier"]["forward"] ?? null) === null){
					$data["speedMultiplier"]["forward"] = 1;
					$this->plugin->getLogger()->warning("Vehicle {$name} in {$fName} has no forward speedMultiplier, reverting to default of '1'");
				}
				if(($data["speedMultiplier"]["backward"] ?? null) === null){
					$data["speedMultiplier"]["backward"] = 1;
					$this->plugin->getLogger()->warning("Vehicle {$name} in {$fName} has no backward speedMultiplier, reverting to default of '1'");
				}
			}


			// Validate data.
			if(($this->vehicles[$name] ?? null) !== null && !$force) throw new VehicleException("Vehicle '{$name}' cannot be registered, its already registered (Could be due to a server 'reload' which is not supported).");

			$currentV = Main::$vehicleDataVersion;
			if($currentV !== $data["version"]) throw new PluginException("Vehicle {$name} has a version of {$data["version"]} while the plugin only supports {$currentV}.");

			if($this->getDesign($data["design"]) === null) throw new VehicleException("Vehicle {$name} in {$fName} is using a design ({$data["design"]}) that has not been registered.");


			// Done all validations. (I think)

			$this->vehicles[$name] = $data;

			$this->plugin->getLogger()->debug("Registered vehicle {$name} from {$fName}");
		}
	}

	/**
	 * Register all designs from plugin_data/Vehicles/Designs/Design_Manifest.json into memory.
	 * Can be used to reload data by setting force to true, it will overwrite existing vehicles.
	 *
	 * @param bool $force
	 */
	public function registerDesigns(bool $force = false): void{
		$manifest = json_decode(file_get_contents($this->plugin->getDataFolder() . "Designs/Design_Manifest.json"), true) ?? [];

		if(count($manifest) === 0){
			throw new InvalidDesignException("No designs found in manifest, it is either invalid JSON or empty (delete the file to generate the default).");
		}

		foreach($manifest as $data){
			$uuid = $data["uuid"] ?? "";
			$name = $data["name"] ?? "";
			$geometry = $name . "_Geometry.json"; //New standard (0.1.0+)

			if(array_key_exists($name, $this->designs) && !$force){
				throw new InvalidDesignException("Failed to register design '{$name}', design already loaded.");
			}

			if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $uuid) !== 1) {
				throw new InvalidDesignException("Failed to register design '{$name}', design has an invalid UUID of '{$uuid}'");
			}

			$this->plugin->saveResource("Designs/" . $name . ".png", false);
			$this->plugin->saveResource("Designs/" . $name . ".json", false);  //Load in if default vehicles.
			$this->plugin->saveResource("Designs/" . $geometry, false);

			if(file_exists($this->plugin->getDataFolder() . "Designs/" . $name . ".json")){
				$design = $this->readDesignFile($this->plugin->getDataFolder() . "Designs/" . $name . ".json");
			} elseif (file_exists($this->plugin->getDataFolder() . "Designs/" . $name . ".png")){
				$design = $this->readDesignFile($this->plugin->getDataFolder() . "Designs/" . $name . ".png");
			} else {
				throw new InvalidDesignException("Failed to register design '{$name}', Design file '{$name}.png/.json' does not exist.");
			}

			if(file_exists($this->plugin->getDataFolder() . "Designs/" . $geometry)){
				$geoData = (array)json_decode(file_get_contents($this->plugin->getDataFolder() . "Designs/" . $geometry));
			} else {
				throw new InvalidDesignException("Failed to register design '{$name}', Geometry file '{$geometry}' does not exist.");
			}

			try{
				$skin = new Skin($uuid,$design,"",$geoData["minecraft:geometry"][0]->description->identifier,json_encode($geoData));
			} catch (InvalidSkinException $e){
				throw new InvalidDesignException("Failed to register design '{$name}', Design data (skin/UV) is invalid: {$e->getMessage()}");
			}

			// MCPE 1.13.0/1.16.0 changes to SkinData:
			$this->designs[$name] = $this->skinToSkinData($skin);

			$this->plugin->getLogger()->debug("Successfully registered design '{$name}'");
		}
	}

	/**
	 * Retrieve vehicle data by type name.
	 * @param string $name
	 * @return mixed[]|null
	 */
	public function getVehicleData(string $name){
		foreach($this->vehicles as $vehicleName => $vehicle){
			if(strtolower($name) === strtolower($vehicleName)) return $vehicle;
		}
		return null;
	}

	/**
	 * Retrieve all Vehicle data.
	 * @return array<string, mixed[]>
	 */
	public function getAllVehicleData(){
		return $this->vehicles;
	}

	/**
	 * Retrieve the Design for a vehicle.
	 * @param string $name
	 * @return SkinData|null
	 */
	public function getDesign(string $name): ?SkinData{
		foreach($this->designs as $designName => $design){
			if(strtolower($name) === strtolower($designName)) return $design;
		}
		return null;
	}

	/**
	 * Retrieve all Design's.
	 * @return mixed[]
	 */
	public function getAllDesigns(){
		return $this->designs;
	}

	/**
	 * Return the RGBA Byte array ready for use from a UV Map (png/json)
	 * @param string $path
	 * @return string|null RGBA Bytes to use.
	 * @throws PluginException|InvalidDesignException
     */
	public function readDesignFile(string $path): ?string{
		$type = pathinfo($path, PATHINFO_EXTENSION);
		if($type === "png"){
			/*if(file_exists(rtrim($path,"png")."json")){
				$data = json_decode(file_get_contents($path));
				$data = base64_decode($data->data);
				$this->plugin->getLogger()->debug("Loaded design from generated json.");
				return $data;
			}*/
			if (!extension_loaded("gd")) {
				throw new PluginException("GD library is not enabled, to load png designs it must be enabled. *See php.ini to enable it*");
			}
			$img = @imagecreatefrompng($path);
			$bytes = '';
			for ($y = 0; $y < imagesy($img); $y++) {
				for ($x = 0; $x < imagesx($img); $x++) {
					$rgba = @imagecolorat($img, $x, $y);
					$a = chr(((~(($rgba >> 24))) << 1) & 0xff);
					$r = chr(($rgba >> 16) & 0xff);
					$g = chr(($rgba >> 8) & 0xff);
					$b = chr($rgba & 0xff);
					$bytes .= $r . $g . $b . $a;
				}
			}
			@imagedestroy($img);
			//file_put_contents(rtrim($path, "png") . "json", json_encode(["data" => base64_encode($bytes)]));
			//$this->plugin->getLogger()->debug("Saved design to json.");
			return $bytes;
		} elseif ($type === "json") {
			$this->plugin->getLogger()->debug("Loaded design from original json.");
			$data = json_decode(file_get_contents($path));
            return base64_decode($data->data, true);
		} else {
			throw new InvalidDesignException("Unknown design type '{$type}' received.");
			//Should never get here unless using as API.
		}
	}
	
	/**
	 * Copy of pmmp's legacy converter except sets skin to trusted, this is used instead of changing client settings.
	 */
	private function skinToSkinData(Skin $skin): SkinData{
		$capeData = $skin->getCapeData();
		$capeImage = $capeData === "" ? new SkinImage(0, 0, "") : new SkinImage(32, 64, $capeData);
		$geometryName = $skin->getGeometryName();
		if($geometryName === ""){
			$geometryName = "geometry.humanoid.custom";
		}
		return new SkinData(
			$skin->getSkinId(),
            "",
			json_encode(["geometry" => ["default" => $geometryName]]),
			SkinImage::fromLegacy($skin->getSkinData()), [],
			$capeImage,
			$skin->getGeometryData()
		);
	}
}

<?php
/*
 * Vehicles, PocketMine-MP Plugin.
 *
 * Licensed under the Open Software License version 3.0 (OSL-3.0)
 * Copyright (C) 2019-2020 JaxkDev
 *
 * Twitter :: @JaxkDev
 * Discord :: JaxkDev#8860
 * Email   :: JaxkDev@gmail.com
 */

declare(strict_types=1);

namespace JaxkDev\Vehicles;

use DirectoryIterator;
use InvalidArgumentException;

use pocketmine\entity\Skin;
use pocketmine\plugin\PluginException;
use pocketmine\network\mcpe\protocol\types\SkinData;
use pocketmine\network\mcpe\protocol\types\SkinAdapterSingleton;
use JaxkDev\Vehicles\Exceptions\DesignException;

class Factory{
	/** @var Main */
	private $plugin;

	/** @var String|SkinData[] */
	private $designs = [];

	/** @var String[] */
	private $vehicles = [];

	public function __construct(Main $plugin)
	{
		$this->plugin = $plugin;
	}

	/*
	 * Spawns a vehicle.
	 * @param string $type
	 * @param Level $level
	 * @param Vector3 $pos
	 * @return Vehicle
	 */
	/*public function spawnVehicle(string $type, Level $level, Vector3 $pos): Vehicle{
	
		
		Todo: completely rewrite the shit out of this.
	
	
		if(!$this->isRegistered($type)) throw new InvalidArgumentException("Type \"${$type} is not a registered vehicle.");

		$type = $this->findClass($type);
		if($type === null){
			throw new ClassNotFoundException("Vehicle type \"${$type}\" Has escaped our reaches and cant be found...");
		}
		$entity = Entity::createEntity($type, $level, Entity::createBaseNBT($pos));
		if($entity === null){
			throw new InvalidArgumentException("Type '${type}' is not a registered vehicle.");
		}
		$entity->spawnToAll();

		$this->plugin->getLogger()->debug("Vehicle \"".$type."\" spawned at ".$pos." in the level ".$level->getName());

		return $entity;
	}*/

	/**
	 * Register all vehicles from plugin_data/Vehicles/Vehicles/*.json into memory.
	 * Can be used to reload data but with argument force being true to overwrite existing vehicles.
	 *
	 * @param bool $force
	 */
	public function registerVehicles($force = false): void{
		//TODO New method as discussed.
		$this->plugin->saveResource("Designs/BasicCar.json"); //Default

		foreach(new DirectoryIterator($this->plugin->getDataFolder() . "Vehicles/") as $file){
			$name = $file->getFilename();
			if($name === "." || $name === "..") continue;

			$path = $this->plugin->getDataFolder() . "Vehicles/{$name}";
			$data = json_decode(file_get_contents($path), true);

			//Actually register here:
			//TODO
		}
	}

	/**
	 * Register all designs from plugin_data/Vehicles/Designs/Design_Manifest.json into memory.
	 * Can be used to reload data but with argument force being true to overwrite existing vehicles.
	 *
	 * @param bool $force
	 */
	public function registerDesigns($force = false): void{
		$this->plugin->saveResource("Designs/Design_Manifest.json");

		$manifest = json_decode(file_get_contents($this->plugin->getDataFolder() . "Designs/Design_Manifest.json"), true) ?? [];

		if(count($manifest) === 0){
			throw new DesignException("No designs found in manifest, it is either invalid JSON or empty (delete the file to generate the default).");
		}

		foreach($manifest as $data){
			$uuid = $data["uuid"] ?? "";
			$name = $data["name"] ?? "";
			$geometry = $name . "_Geometry.json"; //New standard (0.1.0+)
			
			if(array_key_exists($name, $this->designs) && !$force){
				throw new DesignException("Failed to register design '{$name}', design already loaded.");
			}

			if (!is_string($uuid) || $uuid === "" || (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $uuid) !== 1)) {
				throw new DesignException("Failed to register design '{$name}', design has an invalid UUID of '{$uuid}'");
			}

			$this->plugin->saveResource("Designs/" . $name . ".png", true);
			$this->plugin->saveResource("Designs/" . $name . ".json", true);
			$this->plugin->saveResource("Designs/" . $geometry, true);

			if(file_exists($this->plugin->getDataFolder() . "Designs/" . $name . ".json")){
				$design = $this->readDesignFile($this->plugin->getDataFolder() . "Designs/" . $name . ".json");
			} elseif (file_exists($this->plugin->getDataFolder() . "Designs/" . $name . ".png")){
				$design = $this->readDesignFile($this->plugin->getDataFolder() . "Designs/" . $name . ".png");
			} else {
				throw new DesignException("Failed to register design '{$name}', Design file '{$name}.png/.json' does not exist.");
			}

			if(file_exists($this->plugin->getDataFolder() . "Designs/" . $geometry)){
				$geoData = json_decode(file_get_contents($this->plugin->getDataFolder() . "Designs/" . $geometry));
			} else {
				throw new DesignException("Failed to register design '{$name}', Geometry file '{$geometry}' does not exist.");
			}
			$oldSkin = new Skin($uuid,$design,"",$name,json_encode($geoData));
			try{
				$oldSkin->validate();
			} catch (InvalidArgumentException $e){
				throw new DesignException("Failed to register design '{$name}', Design data (skin/UV) is invalid: {$e->getMessage()}");
			}

			// MCPE 1.13.0 change to SkinData:
			$this->designs[$name] = SkinAdapterSingleton::get()->toSkinData($oldSkin);

			$this->plugin->getLogger()->debug("Successfully registered design '{$name}'");
		}
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
	 * Return the RGBA Byte array ready for use from a UV Map (png/json)
	 * @param string $path
	 * @return string|null RGBA Bytes to use.
	 * @throws PluginException|DesignException
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
					$a = chr(((~((int)($rgba >> 24))) << 1) & 0xff);
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
			$data = base64_decode($data->data);
			return $data;
		} else {
			throw new DesignException("Unknown design type '{$type}' received.");
			//Should never get here unless using as API.
		}
	}
}

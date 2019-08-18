<?php
/*
 * Vehicles, PocketMine-MP Plugin.
 *
 * Licensed under the Open Software License version 3.0 (OSL-3.0)
 * Copyright (C) 2019 Jackthehack21 (Jackthehaxk21/JaxkDev)
 *
 * Twitter :: @JaxkDev
 * Discord :: Jackthehaxk21#8860
 * Email   :: gangnam253@gmail.com
 */

declare(strict_types=1);

namespace Jackthehack21\Vehicles\Vehicle;

use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\entity\Entity;
use Jackthehack21\Vehicles\Main;

use reflectionClass;
use ReflectionException;
use ClassNotFoundException;
use InvalidArgumentException;

class VehicleFactory
{
	/** @var Main */
	private $plugin;

	/** @var string[]|string[][] */
	private $registeredTypes = [];

	/**
	 * @internal Should only be done once, may corrupt data further down the line.
	 * VehicleFactory constructor.
	 * @param Main $plugin
	 */
	public function __construct(Main $plugin)
	{
		$this->plugin = $plugin;
	}

	/**
	 * @return string[]
	 */
	public function getTypes(): array{
		return $this->registeredTypes;
	}

	/**
	 * Check is the type provided exists and ready to create/spawn.
	 * @param string $type
	 * @return bool
	 */
	public function isRegistered(string $type): bool
	{
		return ($this->findClass($type) === null ? false : true);
	}

	/**
	 * Searches the loaded types in hope of finding a match.
	 * @param string $type
	 * @return string|null
	 */
	public function findClass(string $type): ?string{
		$new = array_map("strtolower", array_keys($this->getTypes()));
		$index = array_search(strtolower($type), $new, true);
		if($index !== false and count($new) > 0) return $this->getTypes()[array_keys($this->getTypes())[$index]];
		return null;
	}

	public function registerExternalVehicles(){
		$scan = scandir($this->plugin->getDataFolder()."Vehicles/");
		$dir = [];
		foreach($scan as $file) {
			if(pathinfo($this->plugin->getDataFolder()."Vehicles/".$file, PATHINFO_EXTENSION) === "php") $dir[$this->plugin->getDataFolder()."Vehicles/".$file] = rtrim($file,".php");
		}
		foreach($dir as $path => $file){
			if($this->isRegistered($file)){
				$this->plugin->getLogger()->warning("External vehicle '".$file."' already exists.");
				continue;
			}
			/** @noinspection PhpIncludeInspection */
			require $path;
			$className = "Jackthehack21\\Vehicles\\External\\".$file;
			$rc = new reflectionClass($className);
			/** @var Vehicle $class */
			$class = $rc->newInstanceWithoutConstructor();
			if(!is_a($class, Vehicle::class)){
				$this->plugin->getLogger()->warning("External vehicle '".$file."' is not of type Vehicle.");
				continue;
			}
			$this->registerVehicle($class);
		}
		$this->plugin->getLogger()->info("Registered (".count($this->registeredTypes).") vehicle(s)");
	}

	public function registerDefaultVehicles(){
		Entity::registerEntity(BasicCar::class, false);
		$this->registeredTypes[BasicCar::getName()] = "BasicCar";
		//Todo others.

		foreach(array_keys($this->registeredTypes) as $name){
			$this->plugin->getLogger()->debug("Registered Vehicle '${name}'");
		}
	}

	/**
	 * Register the vehicle entity with the server.
	 * @param Vehicle $vehicle
	 * @throws ReflectionException
	 */
	public function registerVehicle(Vehicle $vehicle){
		Entity::registerEntity(get_class($vehicle), false);
		$this->registeredTypes[$vehicle::getName()] = (new ReflectionClass($vehicle))->getShortName();;
		$this->plugin->getLogger()->debug("Registered Vehicle '".$vehicle::getName()."'");
	}

	/**
	 * Spawns a vehicle.
	 * @param string $type
	 * @param Level $level
	 * @param Vector3 $pos
	 * @return Vehicle
	 */
	public function spawnVehicle(string $type, Level $level, Vector3 $pos): Vehicle{
		if(!$this->isRegistered($type)) throw new InvalidArgumentException("Type \"${$type} is not a registered vehicle.");

		$type = $this->findClass($type);
		if($type === null){
			throw new ClassNotFoundException("Vehicle type \"${$type}\" Has escaped our reaches and cant be found...");
		}
		/** @var Vehicle|null $entity */
		$entity = Entity::createEntity($type, $level, Entity::createBaseNBT($pos));
		if($entity === null){
			throw new InvalidArgumentException("Type '${type}' is not a registered vehicle.");
		}
		$entity->spawnToAll();

		$this->plugin->getLogger()->debug("Vehicle \"".$type."\" spawned at ".$pos." in the level ".$level->getName());

		return $entity;
	}
}
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

use ClassNotFoundException;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\entity\Entity;
use Jackthehack21\Vehicles\Main;

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

	public function registerDefaultVehicles(){
		//$this->plugin->getLogger()->debug("Registered Vehicle 'VehicleNameHere'");
		//Todo others.
	}

	/**
	 * Register the vehicle entity with the server.
	 * @param Vehicle $vehicle
	 */
	public function registerVehicle(Vehicle $vehicle){
		Entity::registerEntity(get_class($vehicle), false);
		$this->registeredTypes[$vehicle::getName()] = get_class($vehicle);
		$this->plugin->getLogger()->debug("Registered Vehicle '".$vehicle::getName()."'");
	}

	public function spawnVehicle(string $type, Level $level, Vector3 $pos): bool{
		if(!$this->isRegistered($type)) return false;

		$type = $this->findClass($type);
		if($type === null){
			throw new ClassNotFoundException("Vehicle type \"".$type."\" Has escaped our reaches and cant be found...");
		}
		$entity = Entity::createEntity($type, $level, Entity::createBaseNBT($pos));
		$entity->spawnToAll();

		$this->plugin->getLogger()->info("Vehicle \"".$type."\" spawned at ".$pos." in the level ".$level->getName());

		return true;
	}
}
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

namespace Jackthehack21\Vehicles\Object;

use ClassNotFoundException;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\entity\Entity;
use Jackthehack21\Vehicles\Main;

class ObjectFactory
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

	public function registerDefaultObjects(){
		Entity::registerEntity(TrafficConeSmall::class, false);
		$this->registeredTypes[TrafficConeSmall::getName()] = "TrafficConeSmall";

		//Todo others.
	}

	/**
	 * Register the vehicle entity with the server.
	 * @param DisplayObject $object
	 */
	public function registerObject(DisplayObject $object){
		Entity::registerEntity(get_class($object), false);
		$this->registeredTypes[$object::getName()] = get_class($object);
	}

	public function spawnObject(string $type, Level $level, Vector3 $pos): bool{
		if(!$this->isRegistered($type)) return false;

		$type = $this->findClass($type);
		if($type === null){
			throw new ClassNotFoundException("Object \"".$type."\" Has escaped our reaches and cannot be found...");
		}

		$entity = Entity::createEntity($type, $level, Entity::createBaseNBT($pos));
		$entity->spawnToAll();

		$this->plugin->getServer()->getLogger()->info($this->plugin->prefix."Object \"".$type."\" spawned at ".$pos." in the level ".$level->getName());

		return true;
	}
}
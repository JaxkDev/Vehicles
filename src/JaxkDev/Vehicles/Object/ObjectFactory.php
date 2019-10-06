<?php
/*
 * Vehicles, PocketMine-MP Plugin.
 *
 * Licensed under the Open Software License version 3.0 (OSL-3.0)
 * Copyright (C) 2019 JaxkDev
 *
 * Twitter :: @JaxkDev
 * Discord :: Jackthehaxk21#8860
 * Email   :: JaxkDev@gmail.com
 */

declare(strict_types=1);

namespace JaxkDev\Vehicles\Object;

use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\entity\Entity;
use JaxkDev\Vehicles\Main;

use ReflectionClass;
use ReflectionException;
use ClassNotFoundException;
use InvalidArgumentException;

class ObjectFactory
{
	/** @var Main */
	private $plugin;

	/** @var string[]|string[][] */
	private $registeredTypes = [];

	/**
	 * @internal Should only be done once, may corrupt data further down the line.
	 * objectFactory constructor.
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

	/**
	 * Look here and become scarred.
	 * @throws ReflectionException
	 */
	public function registerExternalObjects(){
		$scan = scandir($this->plugin->getDataFolder()."Objects/");
		$dir = [];
		foreach($scan as $file) {
			if(pathinfo($this->plugin->getDataFolder()."Objects\\".$file, PATHINFO_EXTENSION) === "php") $dir[$this->plugin->getDataFolder()."Objects\\".$file] = rtrim($file,".php");
		}
		foreach($dir as $path => $file){
			if($this->isRegistered($file)){
				$this->plugin->getLogger()->warning("External Object '".$file."' already exists.");
				continue;
			}
			/** @noinspection PhpIncludeInspection */
			require $path;
			$className = "JaxkDev\\Vehicles\\External\\".$file;
			$rc = new reflectionClass($className);
			/** @var DisplayObject $class */
			$class = $rc->newInstanceWithoutConstructor();
			if(!is_a($class, DisplayObject::class)){
				$this->plugin->getLogger()->warning("External object '".$file."' is not of type DisplayObject.");
				continue;
			}
			$this->registerObject($class);
		}
		$this->plugin->getLogger()->info("Registered (".count($this->registeredTypes).") object(s)");
	}

	public function registerDefaultObjects(){
		Entity::registerEntity(TrafficCone::class, false);
		$this->registeredTypes[TrafficCone::getName()] = "TrafficCone";
		Entity::registerEntity(StopSign::class, true);
		$this->registeredTypes[StopSign::getName()] = "StopSign";
		Entity::registerEntity(NoEntrySign::class, false);
		$this->registeredTypes[NoEntrySign::getName()] = "NoEntrySign";

		//others here

		foreach(array_keys($this->registeredTypes) as $name){
			$this->plugin->getLogger()->debug("Registered Object '{$name}'");
		}
	}

	/**
	 * Register the vehicle entity with the server.
	 * @param DisplayObject $object
	 * @throws ReflectionException
	 */
	public function registerObject(DisplayObject $object){
		Entity::registerEntity(get_class($object), false);
		$this->registeredTypes[$object::getName()] =  (new ReflectionClass($object))->getShortName();;

		$this->plugin->getLogger()->debug("Registered Object '".$object::getName()."'");
	}

	/**
	 * Spawn a object in.
	 * @param string $type
	 * @param Level $level
	 * @param Vector3 $pos
	 * @return DisplayObject
	 */
	public function spawnObject(string $type, Level $level, Vector3 $pos): DisplayObject{
		if(!$this->isRegistered($type)) throw new InvalidArgumentException("Type \"${$type} is not a registered object.");

		$type = $this->findClass($type);
		if($type === null){
			throw new ClassNotFoundException("Object \"".$type."\" Has escaped our reaches and cannot be found...");
		}

		/** @var DisplayObject|null $entity */
		$entity = Entity::createEntity($type, $level, Entity::createBaseNBT($pos));
		if($entity === null){
			throw new InvalidArgumentException("Type \"${$type} is not a registered object.");
		}
		$entity->spawnToAll();

		$this->plugin->getLogger()->debug("Object \"".$type."\" spawned at ".$pos." in the level ".$level->getName());

		return $entity;
	}
}
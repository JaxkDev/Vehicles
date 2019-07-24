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

class VehicleFactory
{
	/** @var Main */
	private $plugin;

	/** @var string[] */
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
		if(in_array(strtolower($type), array_map("strtolower", $this->getTypes()))) return true;
		return false;
	}

	public function loadTypes(){
		$this->registeredTypes[] = CarVehicle::getVehicleName();
	}

	public function registerVehicles(){
		Entity::registerEntity(CarVehicle::class, false, CarVehicle::getSaveNames());
	}

	public function spawnVehicle(string $type, Level $level, Vector3 $pos): bool{
		if(!$this->isRegistered($type)) return false;

		$entity = Entity::createEntity($type, $level, Entity::createBaseNBT($pos));
		$entity->spawnToAll();

		return true;
	}
}
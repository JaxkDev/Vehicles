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
use pocketmine\entity\Entity;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\utils\TextFormat as C;
use pocketmine\entity\Vehicle as PmVehicle;

abstract class Vehicle extends PmVehicle
{

	/**
	 * Vehicle constructor.
	 * @param Level $level
	 * @param CompoundTag $nbt
	 */
	public function __construct(Level $level, CompoundTag $nbt){
		parent::__construct($level, $nbt);
		$this->setNameTag(C::RED."[Vehicle] ".C::GOLD.$this->getVehicleName());
		$this->setNameTagAlwaysVisible(true);
		$this->setCanSaveWithChunk(false); //Separated in the future as saving will be optional. (maybe will end up saving with chunks)
	}

	/**
	 * Should return the type of vehicle it is, eg Car, Plane, Boat (MUST BE UNIQUE !)
	 * @return string
	 */
	abstract static function getVehicleName(): string;

	/**
	 * Return all the names that it can/is saved under
	 * @return string[]
	 */
	abstract static function getSaveNames(): array;

	public function isFireProof(): bool
	{
		return true;
	}

	protected function initEntity(): void
	{
		parent::initEntity();
		$this->propertyManager->setString(Entity::DATA_INTERACTIVE_TAG, "Ride");
	}
}
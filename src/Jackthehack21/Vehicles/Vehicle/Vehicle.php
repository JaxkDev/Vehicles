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

use pocketmine\entity\EntityIds;
use pocketmine\entity\Skin;
use pocketmine\level\Level;
use pocketmine\entity\Entity;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Player;
use pocketmine\utils\TextFormat as C;
use pocketmine\entity\Vehicle as PmVehicle;
use pocketmine\utils\UUID;

abstract class Vehicle extends PmVehicle
{

	public const NETWORK_ID = EntityIds::PLAYER;

	protected $gravity = 0.1; //float down. todo change to harsher.
	protected $drag = 0.5;

	/** @var null|Player */
	protected $rider = null;     //Todo once i have a skin, find accurate numbers for offsets etc.

	protected $riderOffset = 0;
	protected $baseOffset = 0;

	/** @var UUID Used for spawning and handling in terms of reference to the entity*/
	protected $uuid;

	/**
	 * Vehicle constructor.
	 * @param Level $level
	 * @param CompoundTag $nbt
	 */
	public function __construct(Level $level, CompoundTag $nbt){
		$this->uuid = UUID::fromRandom();

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
	 * Returns the Design of the vehicle.
	 * @return Skin
	 */
	abstract static function getDesign(): Skin;

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
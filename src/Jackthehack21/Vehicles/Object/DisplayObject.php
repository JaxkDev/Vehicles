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

use pocketmine\entity\Entity;
use pocketmine\entity\EntityIds;
use pocketmine\entity\Skin;
use pocketmine\level\Level;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\utils\UUID;

abstract class DisplayObject extends Entity{
	public const NETWORK_ID = EntityIds::PLAYER;

	/** @var int */
	protected $gravity = 1; //todo find. (remember not to put negative.......)

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
		$this->setNameTagAlwaysVisible(false);
		$this->setCanSaveWithChunk(true); //Separated in the future as saving will be optional. (maybe will end up saving with chunks)
	}

	/**
	 * Should return the type of vehicle it is, eg Car, Plane, Boat (MUST BE UNIQUE !)
	 * @return string
	 */
	abstract static function getName(): string;

	/**
	 * Returns the Design of the object.
	 * @return Skin
	 */
	abstract static function getDesign(): Skin;

	public function isFireProof(): bool
	{
		return true;
	}
}
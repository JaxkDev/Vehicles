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
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\entity\Entity;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\AddPlayerPacket;
use pocketmine\network\mcpe\protocol\PlayerListPacket;
use pocketmine\network\mcpe\protocol\types\PlayerListEntry;
use pocketmine\Player;
use pocketmine\utils\TextFormat as C;
use pocketmine\entity\Vehicle as PmVehicle;
use pocketmine\utils\UUID;

abstract class Vehicle extends PmVehicle
{
	public const NETWORK_ID = EntityIds::PLAYER;

	protected $gravity = 0.1; //float down. todo change to harsher. (remember not to make negative...)
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

		$this->setNameTag(C::RED."[Vehicle] ".C::GOLD.$this->getName());
		$this->setNameTagAlwaysVisible(true);
		$this->setCanSaveWithChunk(false); //Separated in the future as saving will be optional. (maybe will end up saving with chunks)
	}

	/**
	 * Should return the vehicle name shown in-game.
	 * @return string
	 */
	abstract static function getName(): string;

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

	protected function sendInitPacket(Player $player, Vehicle $obj) : void{
		$skin = $obj->getDesign();
		$skin->validate(); //Leave it to throw the exception as it should not be invalid this far in.

		//Below adds the entity ID + skin to the list to be used in the AddPlayerPacket (WITHOUT THIS DEFAULT SKIN WILL BE USED).
		$pk = new PlayerListPacket();
		$pk->type = PlayerListPacket::TYPE_ADD;
		$pk->entries[] = PlayerListEntry::createAdditionEntry($obj->uuid, $obj->id, $obj::getName(), $obj::getDesign());;
		$player->sendDataPacket($pk);

		//Below adds the actual entity and puts the pieces together.
		$pk = new AddPlayerPacket();
		$pk->uuid = $obj->uuid;
		$pk->item = Item::get(Item::AIR);
		$pk->motion = $obj->getMotion();
		$pk->position = $obj->asVector3();
		$pk->entityRuntimeId = $obj->getId();
		$pk->metadata = $obj->propertyManager->getAll();
		$pk->username = $obj::getName()."-".$obj->id; //Unique.
		$player->sendDataPacket($pk);

		//Dont want to keep a fake person there...
		$pk = new PlayerListPacket();
		$pk->type = $pk::TYPE_REMOVE;
		$pk->entries = [PlayerListEntry::createRemovalEntry($obj->uuid)];
		$player->sendDataPacket($pk);
	}
}
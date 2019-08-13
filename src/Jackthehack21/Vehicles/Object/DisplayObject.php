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
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\AddPlayerPacket;
use pocketmine\network\mcpe\protocol\PlayerListPacket;
use pocketmine\network\mcpe\protocol\types\PlayerListEntry;
use pocketmine\Player;
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

	protected function sendInitPacket(Player $player, DisplayObject $obj) : void{
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
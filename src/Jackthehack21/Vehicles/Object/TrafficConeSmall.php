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

use Jackthehack21\Vehicles\Main;
use pocketmine\entity\Skin;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\entity\EntityIds;
use pocketmine\level\Level;
use pocketmine\network\mcpe\protocol\AddPlayerPacket;
use pocketmine\network\mcpe\protocol\PlayerListPacket;
use pocketmine\network\mcpe\protocol\types\PlayerListEntry;
use pocketmine\utils\UUID;
use pocketmine\Player;
use TypeError;

class TrafficConeSmall extends DisplayObject{
	public const NETWORK_ID = EntityIds::PLAYER;

	/** @var UUID Used for spawning and handling in terms of reference to the entity*/
	protected $uuid;

	public $width = 0.4; //Todo measure.
	public $height = 0.4;

	public function __construct(Level $level, CompoundTag $nbt)
	{
		$this->uuid = UUID::fromRandom();
		parent::__construct($level, $nbt);
		$this->setNameTagAlwaysVisible(false);
		$this->setCanSaveWithChunk(true);
	}

	public function onCollideWithPlayer(Player $player) : void{
		var_dump($player->getPosition());
		if($player->asLocation()->distance($this->getPosition()) < 1){
			$player->resetLastMovements(); //big todo
		}
	}

	public function canBeCollidedWith() : bool{
		return true;
	}

	public function canBeMovedByCurrents() : bool{
		return true;
	}

	static function getName(): string{
		return "Traffic-Cone-Small";
	}

	static function getDesign(): Skin
	{
		return Main::getInstance()->getDesign(TrafficConeSmall::getName());
	}

	protected function sendSpawnPacket(Player $player) : void{
		//todo move.
		$skin = $this->getDesign();
		if(!$skin->isValid()) throw new TypeError("Skin is invalid for object \"".self::getName()."\""); //TODO

		//Below adds the entity ID + skin to the list to be used in the AddPlayerPacket (WITHOUT THIS DEFAULT SKIN WILL BE USED).
		$pk = new PlayerListPacket();
		$pk->type = PlayerListPacket::TYPE_ADD;
		$pk->entries[] = PlayerListEntry::createAdditionEntry($this->uuid, $this->id, self::getName(), self::getDesign());;
		$player->sendDataPacket($pk);

		//Below adds the actual entity and puts the pieces together.
		$pk = new AddPlayerPacket();
		$pk->uuid = $this->uuid;
		$pk->item = Item::get(Item::AIR);
		$pk->motion = $this->getMotion();
		$pk->position = $this->asVector3();
		$pk->entityRuntimeId = $this->getId();
		$pk->metadata = $this->propertyManager->getAll();
		$pk->username = self::getName()."-".$this->id; //Unique.
		$player->sendDataPacket($pk);

		//Dont want to keep a fake person there...
		$pk = new PlayerListPacket();
		$pk->type = $pk::TYPE_REMOVE;
		$pk->entries = [PlayerListEntry::createRemovalEntry($this->uuid)];
		$player->sendDataPacket($pk);
	}
}
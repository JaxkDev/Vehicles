<?php
/*
 * Vehicles, PocketMine-MP Plugin.
 *
 * Licensed under the Open Software License version 3.0 (OSL-3.0)
 * Copyright (C) 2019-2020 JaxkDev
 *
 * Twitter :: @JaxkDev
 * Discord :: JaxkDev#8860
 * Email   :: JaxkDev@gmail.com
 */

declare(strict_types=1);

namespace JaxkDev\Vehicles;

use pocketmine\Player;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\AddPlayerPacket;
use pocketmine\network\mcpe\protocol\types\EntityLink;
use pocketmine\network\mcpe\protocol\PlayerListPacket;
use pocketmine\network\mcpe\protocol\SetActorLinkPacket;
use pocketmine\network\mcpe\protocol\types\PlayerListEntry;

class Vehicle extends VehicleBase
{
	/**
	 * Vehicles constructor.
	 * @param Level $level
	 * @param CompoundTag $nbt
	 */
	public function __construct(Level $level, CompoundTag $nbt){
		parent::__construct($level, $nbt);

		$this->setCanSaveWithChunk(true);
		$this->saveNBT();
	}

	/**
	 * Handle player input.
	 * @param float $x
	 * @param float $y
	 */
	function updateMotion(float $x, float $y): void{
		//todo
	}

	public function isFireProof(): bool
	{
		return true;
	}

	//Without this the player will not do the things it should be (driving, sitting etc)
	protected function broadcastLink(Player $player, int $type = EntityLink::TYPE_RIDER): void{
		foreach($this->getViewers() as $viewer) {
			if (!isset($viewer->getViewers()[$player->getLoaderId()])) {
				$player->spawnTo($viewer);
			}
			$pk = new SetActorLinkPacket();
			$pk->link = new EntityLink($this->getId(), $player->getId(), $type);
			$viewer->sendDataPacket($pk);
		}
	}

	protected function sendSpawnPacket(Player $player) : void{
		$skin = $this->getVehicleDesign();

		//Below adds the entity ID + skin to the list to be used in the AddPlayerPacket (WITHOUT THIS DEFAULT/NO SKIN WILL BE USED).
		$pk = new PlayerListPacket();
		$pk->type = PlayerListPacket::TYPE_ADD;
		$pk->entries[] = PlayerListEntry::createAdditionEntry($this->uuid, $this->id, $this->getVehicleName()."-".$this->id, $skin);
		$player->sendDataPacket($pk);

		//Below adds the actual entity and puts the pieces together.
		$pk = new AddPlayerPacket();
		$pk->uuid = $this->uuid;
		$pk->item = Item::get(Item::AIR);
		$pk->motion = $this->getMotion();
		$pk->position = $this->asVector3();
		$pk->entityRuntimeId = $this->getId();
		$pk->metadata = $this->propertyManager->getAll();
		$pk->username = $this->getVehicleName()."-".$this->id; //Unique.
		$player->sendDataPacket($pk);

		//Dont want to keep a fake person there...
		$pk = new PlayerListPacket();
		$pk->type = $pk::TYPE_REMOVE;
		$pk->entries = [PlayerListEntry::createRemovalEntry($this->uuid)];
		$player->sendDataPacket($pk);
	}
}
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

use Jackthehack21\Vehicles\Main;
use pocketmine\entity\Skin;
use pocketmine\item\Item;
use pocketmine\network\mcpe\protocol\AddPlayerPacket;
use pocketmine\network\mcpe\protocol\PlayerListPacket;
use pocketmine\network\mcpe\protocol\types\PlayerListEntry;
use pocketmine\Player;

use TypeError;

class PoliceCar extends Vehicle
{

	public $width = 1; //Todo measure once i have a skin. (1,2 for person)
	public $height = 2;

	public function canBeMovedByCurrents() : bool{
		return false;
	}

	static function getVehicleName(): string
	{
		return "Police-Car";
	}

	static function getDesign(): Skin
	{
		return Main::getInstance()->getDesign("test");
	}

	protected function sendSpawnPacket(Player $player) : void{
		$skin = $this->getDesign();
		if(!$skin->isValid()) throw new TypeError("Skin is invalid for vehicle type \"".self::getVehicleName()."\""); //TODO

		//Below adds the entity ID + skin to the list to be used in the AddPlayerPacket (WITHOUT THIS DEFAULT SKIN WILL BE USED).
		$pk = new PlayerListPacket();

		$pk->type = PlayerListPacket::TYPE_ADD;
		$pk->entries[] = PlayerListEntry::createAdditionEntry($this->uuid, $this->id, self::getVehicleName(), self::getDesign());;

		$player->sendDataPacket($pk);

		//Below adds the actual entity and puts the pieces together.
		$pk = new AddPlayerPacket();

		$pk->uuid = $this->uuid;
		$pk->item = Item::get(Item::AIR);
		$pk->motion = $this->getMotion();
		$pk->position = $this->asVector3();
		$pk->entityRuntimeId = $this->getId();
		$pk->metadata = $this->propertyManager->getAll();
		$pk->username = self::getVehicleName()."-".$this->id; //Unique, remove once 'owners' have been added.

		$player->sendDataPacket($pk);

		//Dont want to keep a fake person there...
		$pk = new PlayerListPacket();

		$pk->type = $pk::TYPE_REMOVE;
		$pk->entries = [PlayerListEntry::createRemovalEntry($this->uuid)];

		$player->sendDataPacket($pk);
	}
}
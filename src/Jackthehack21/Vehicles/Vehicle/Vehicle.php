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
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\item\Item;
use pocketmine\utils\UUID;
use pocketmine\level\Level;
use pocketmine\entity\Skin;
use pocketmine\entity\Entity;
use pocketmine\entity\Rideable;
use pocketmine\entity\EntityIds;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\utils\TextFormat as C;
use pocketmine\network\mcpe\protocol\AddPlayerPacket;
use pocketmine\network\mcpe\protocol\types\EntityLink;
use pocketmine\network\mcpe\protocol\PlayerListPacket;
use pocketmine\network\mcpe\protocol\SetActorLinkPacket;
use pocketmine\network\mcpe\protocol\types\PlayerListEntry;

abstract class Vehicle extends Entity implements Rideable
{
	public const NETWORK_ID = EntityIds::HORSE;

	protected $gravity = 1; //todo find best value. (remember not to make negative...)
	protected $drag = 0.5;

	/** @var null|Player */
	protected $driver;

	/** @var null|Vector3 */
	protected $driverPosition = null;

	/** @var UUID Used for spawning and handling in terms of reference to the entity*/
	protected $uuid;

	/** @var Main */
	private $plugin;

	/**
	 * Vehicle constructor.
	 * @param Level $level
	 * @param CompoundTag $nbt
	 */
	public function __construct(Level $level, CompoundTag $nbt){
		$this->uuid = UUID::fromRandom();
		$this->plugin = Main::getInstance();

		parent::__construct($level, $nbt);

		$this->setNameTag(C::RED."[Vehicle] ".C::GOLD.$this->getName());
		$this->setNameTagAlwaysVisible($this->plugin->cfg["vehicles"]["show-nametags"]);
		$this->setCanSaveWithChunk(true);
	}

	public function getRiderSeatPosition(){
		if($this->driverPosition === null) return new Vector3(0, $this->height, 0);
		else return $this->driverPosition;
	}

	/**
	 * Should return the vehicle name shown in-game.
	 * @return string
	 */
	abstract static function getName(): string;

	/**
	 * Returns the Design of the vehicle.
	 * @return Skin|null
	 */
	abstract static function getDesign(): ?Skin;

	/**
	 * Handle player input.
	 * @param float $x
	 * @param float $y
	 */
	abstract function updateMotion(float $x, float $y): void;

	/**
	 * Removes the driver if possible.
	 * @return bool
	 */
	public function removeDriver(): bool{
		if($this->driver === null) return false;
		$this->driver->setGenericFlag(Entity::DATA_FLAG_RIDING, true);
		$this->driver->setGenericFlag(Entity::DATA_FLAG_SITTING, true);
		$this->driver->setGenericFlag(Entity::DATA_FLAG_WASD_CONTROLLED, true);

		$this->setGenericFlag(Entity::DATA_FLAG_SADDLED, true);
		$this->driver->sendMessage(C::GREEN."You are no longer driving this vehicle.");
		$this->broadcastDriverLink(EntityLink::TYPE_REMOVE);
		unset(Main::$driving[$this->driver->getRawUniqueId()]);
		$this->driver = null;
		return true;
	}

	/**
	 * Sets the driver to the given player if possible.
	 * @param Player $player
	 * @return bool
	 */
	public function setDriver(Player $player): bool{
		if($this->driver !== null){
			if($this->driver->getUniqueId() === $player->getUniqueId()){
				$player->sendMessage(C::RED."You are already driving this vehicle.");
				return false;
			}
			$player->sendMessage(C::RED.$this->driver->getName()." is driving this vehicle.");
			return false;
		}

		$player->setGenericFlag(Entity::DATA_FLAG_RIDING, true);
		$player->setGenericFlag(Entity::DATA_FLAG_SITTING, true);
		$player->setGenericFlag(Entity::DATA_FLAG_WASD_CONTROLLED, true);
		$player->getDataPropertyManager()->setVector3(Entity::DATA_RIDER_SEAT_POSITION, $this->getRiderSeatPosition());

		$this->setGenericFlag(Entity::DATA_FLAG_SADDLED, true);
		$this->driver = $player;
		Main::$driving[$this->driver->getRawUniqueId()] = $this;
		$player->sendMessage(C::GREEN."You are now driving this vehicle.");
		$this->broadcastDriverLink();
		$player->sendPopup(C::GREEN."Sneak/Jump to leave the vehicle.", "[Vehicles]");
		return true;
	}

	/**
	 * Returns the driver if there is one.
	 * @return Player|null
	 */
	public function getDriver(): ?Player{
		return $this->driver;
	}

	/**
	 * Checks if the vehicle as a driver.
	 * @return bool
	 */
	public function hasDriver(): bool{
		return $this->driver !== null;
	}

	public function isFireProof(): bool
	{
		return true;
	}

	protected function initEntity(): void
	{
		parent::initEntity();
	}

	//Without this the player will not do the things it should be (driving, sitting etc)
	protected function broadcastDriverLink(int $type = EntityLink::TYPE_RIDER): void{
		if($this->driver === null) return;

		foreach($this->getViewers() as $viewer) {
			if (!isset($viewer->getViewers()[$this->driver->getLoaderId()])) {
				$this->driver->spawnTo($viewer);
			}
			$pk = new SetActorLinkPacket();
			$pk->link = new EntityLink($this->getId(), $this->driver->getId(), $type);
			$viewer->sendDataPacket($pk);
		}
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
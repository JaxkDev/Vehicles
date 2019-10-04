<?php
/*
 * Vehicles, PocketMine-MP Plugin.
 *
 * Licensed under the Open Software License version 3.0 (OSL-3.0)
 * Copyright (C) 2019 JaxkDev
 *
 * Twitter :: @JaxkDev
 * Discord :: Jackthehaxk21#8860
 * Email   :: JaxkDev@gmail.com
 */

declare(strict_types=1);

namespace JaxkDev\Vehicles\Vehicle;

use JaxkDev\Vehicles\Main;

use pocketmine\Player;
use pocketmine\item\Item;
use pocketmine\utils\UUID;
use pocketmine\level\Level;
use pocketmine\entity\Skin;
use pocketmine\math\Vector3;
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

use LogicException;

abstract class Vehicle extends Entity implements Rideable
{
	public const NETWORK_ID = EntityIds::HORSE;

	protected $gravity = 1; //todo find best value. (remember not to make negative...)
	protected $drag = 0.5;

	/** @var null|UUID */
	protected $owner = null;

	/** @var null|Player */
	protected $driver = null;

	/** @var Player[] */
	protected $passengers = [];

	/** @var null|Vector3 */
	protected $driverPosition = null;

	/** @var Vector3[] */
	protected $passengerPositions = [];

	/** @var bool */
	protected $locked = false;   //Todo think about moving to 'status'

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
		$owner = $this->namedtag->getString("ownerUUID", "NA");
		//$this->plugin->getLogger()->debug("ownerUUID: ".$owner);
		if($owner !== "NA"){
			$this->owner = UUID::fromString($owner);
		}
		$locked = $this->namedtag->getByte("locked", 0);
		//$this->plugin->getLogger()->debug("locked: ".($locked === 0 ? "un-locked" : "locked"));
		if($locked === 1) $this->locked = true;
		if($this->owner === null){
			$this->locked = false;
			$this->updateNBT();
		}
		$this->setCanSaveWithChunk(true);
		$this->saveNBT();
	}

	public function getDriverSeatPosition() : ?Vector3{
		if($this->driverPosition === null) return new Vector3(0, $this->height, 0);
		else return $this->driverPosition;
	}

	public function getPassengerSeatPosition(int $seatNumber) : ?Vector3{
		if(isset($this->passengerPositions[$seatNumber])) return $this->passengerPositions[$seatNumber];
		return null;
	}

	public function getNextAvailableSeat(): ?int{
		$max = count($this->passengerPositions);
		$current = count($this->passengers);
		if($max === $current) return null;
		for($i = 0; $i < $max; $i++){
			if(!isset($this->passengers[$i])) return $i;
		}
		throw new LogicException("No seat found when max seats doesnt match currently used seats.");
	}

	public function isEmpty(): bool{
		if(count($this->passengers) === 0 and $this->driver === null) return true;
		return false;
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
	 * Remove the given player from the vehicle
	 * @param Player $player
	 * @return bool
	 */
	public function removePlayer(Player $player): bool{
		if($this->driver !== null){
			if($this->driver->getUniqueId()->equals($player->getUniqueId())) return $this->removeDriver();
		}
		return $this->removePassengerByUUID($player->getUniqueId());
	}

	public function removePassengerByUUID(UUID $id): bool{
		foreach(array_keys($this->passengers) as $i){
			if($this->passengers[$i]->getUniqueId() === $id){
				return $this->removePassenger($i);
			}
		}
		return false;
	}

	/**
	 * Remove passenger by seat number.
	 * @param int $seat
	 * @return bool
	 */
	public function removePassenger($seat): bool{
		if(isset($this->passengers[$seat])){
			$player = $this->passengers[$seat];
			unset($this->passengers[$seat]);
			unset(Main::$inVehicle[$player->getRawUniqueId()]);
			$player->setGenericFlag(Entity::DATA_FLAG_RIDING, false);
			$player->setGenericFlag(Entity::DATA_FLAG_SITTING, false);
			$this->broadcastLink($player, EntityLink::TYPE_REMOVE);
			$player->sendMessage(C::GREEN."You are no longer in this vehicle.");
			return true;
		}
		return false;
	}

	/**
	 * Removes the driver if possible.
	 * @return bool
	 */
	public function removeDriver(): bool{
		if($this->driver === null) return false;
		$this->driver->setGenericFlag(Entity::DATA_FLAG_RIDING, false);
		$this->driver->setGenericFlag(Entity::DATA_FLAG_SITTING, false);
		$this->driver->setGenericFlag(Entity::DATA_FLAG_WASD_CONTROLLED, false);

		$this->setGenericFlag(Entity::DATA_FLAG_SADDLED, false);
		$this->driver->sendMessage(C::GREEN."You are no longer driving this vehicle.");
		$this->broadcastLink($this->driver, EntityLink::TYPE_REMOVE);
		unset(Main::$inVehicle[$this->driver->getRawUniqueId()]);
		$this->driver = null;
		return true;
	}

	public function setPassenger(Player $player, ?int $seat = null): bool{
		if($this->isLocked() && !$player->getUniqueId()->equals($this->getOwner())){
			$player->sendMessage(C::RED."This vehicle is locked.");
			return false;
		}
		if($seat !== null){
			if(isset($this->passengers[$seat])) return false;
		} else {
			$seat = $this->getNextAvailableSeat();
			if($seat === null) return false;
		}
		$this->passengers[$seat] = $player;
		Main::$inVehicle[$player->getRawUniqueId()] = $this;
		$player->setGenericFlag(Entity::DATA_FLAG_RIDING, true);
		$player->setGenericFlag(Entity::DATA_FLAG_SITTING, true);
		$player->getDataPropertyManager()->setVector3(Entity::DATA_RIDER_SEAT_POSITION, $this->getPassengerSeatPosition($seat));
		$this->broadcastLink($player, EntityLink::TYPE_PASSENGER);
		$player->sendMessage(C::GREEN."You are now a passenger in this vehicle.");
		return true;
	}

	/**
	 * Sets the driver to the given player if possible.
	 * @param Player $player
	 * @return bool
	 */
	public function setDriver(Player $player): bool{
		if($this->isLocked() && !$player->getUniqueId()->equals($this->getOwner())){
			$player->sendMessage(C::RED."This vehicle is locked, you must be the owner to enter.");
			return false;
		}
		if($this->driver !== null){
			if($this->driver->getUniqueId()->equals($player->getUniqueId())){
				$player->sendMessage(C::RED."You are already driving this vehicle.");
				return false;
			}
			$player->sendMessage(C::RED.$this->driver->getName()." is driving this vehicle.");
			return false;
		}

		$player->setGenericFlag(Entity::DATA_FLAG_RIDING, true);
		$player->setGenericFlag(Entity::DATA_FLAG_SITTING, true);
		$player->setGenericFlag(Entity::DATA_FLAG_WASD_CONTROLLED, true);
		$player->getDataPropertyManager()->setVector3(Entity::DATA_RIDER_SEAT_POSITION, $this->getDriverSeatPosition());

		$this->setGenericFlag(Entity::DATA_FLAG_SADDLED, true);
		$this->driver = $player;
		Main::$inVehicle[$this->driver->getRawUniqueId()] = $this;
		$player->sendMessage(C::GREEN."You are now driving this vehicle.");
		$this->broadcastLink($this->driver);
		$player->sendPopup(C::GREEN."Sneak/Jump to leave the vehicle.", "[Vehicles]");

		if($this->owner === null){
			$this->setOwner($player);
			$player->sendMessage(C::GREEN."You have claimed this vehicle, you are now its owner.");
		}
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

	/**
	 * Check if vehicle is locked.
	 * @return bool
	 */
	public function isLocked(): bool{
		return $this->locked;
	}

	/**
	 * Set the vehicle as locked/unlocked.
	 * @param bool $var
	 */
	public function setLocked(bool $var): void{
		$this->locked = $var;
		$this->namedtag->setByte("locked", $var ? 1 : 0);
		$this->saveNBT();
	}

	/**
	 * Get the vehicles owner.
	 * @return UUID|null
	 */
	public function getOwner(): ?UUID{
		return $this->owner;
	}

	public function setOwner(Player $player): void{
		$this->owner = $player->getUniqueId();
		$this->updateNBT();
	}

	public function removeOwner(): void{
		$this->owner = null;
		$this->locked = false; //Cant be locked and no owner, causes endless loop.
		$this->updateNBT();
	}

	public function updateNBT(): void{
		$this->namedtag->setString("ownerUUID", $this->owner !== null ? $this->owner->toString() : "NA");
		$this->namedtag->setByte("locked", $this->locked ? 1 : 0);
		$this->saveNBT();
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

	protected function sendInitPacket(Player $player, Vehicle $obj) : void{
		$skin = $obj->getDesign();
		$skin->validate(); //Leave it to throw the exception as it should not be invalid this far in.

		//Below adds the entity ID + skin to the list to be used in the AddPlayerPacket (WITHOUT THIS DEFAULT/NO SKIN WILL BE USED).
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
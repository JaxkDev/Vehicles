<?php
/*
 * Vehicles, PocketMine-MP Plugin.
 *
 * Licensed under the Open Software License version 3.0 (OSL-3.0)
 * Copyright (C) 2019-2020 JaxkDev
 *
 * Twitter :: @JaxkDev
 * Discord :: JaxkDev#0001
 * Email   :: JaxkDev@gmail.com
 */

declare(strict_types=1);

namespace JaxkDev\Vehicles;

use LogicException;
use pocketmine\uuid\UUID;
use pocketmine\player\Player;
use pocketmine\entity\Location;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;
use pocketmine\network\mcpe\protocol\types\entity\EntityLink;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;

class Vehicle extends VehicleBase
{
	/** @var Player|null */
	private $driver = null;

	/** @var Player[] */
	private $passengers = [];

	/**
	 * Vehicles constructor.
	 * @param Location $loc
	 * @param CompoundTag $nbt
	 */
	public function __construct(Location $loc, CompoundTag $nbt){
		parent::__construct($loc, $nbt);

		$this->setCanSaveWithChunk(true);
		$this->saveNBT();
	}

	//---------- Logic below... ------------

	/**
	 * Handle player input.
	 * @param float $x
	 * @param float $y
	 */
	public function updateMotion(float $x, float $y): void{
		//				(1 if only one button, 0.7 if two)
		//+y = forward. (+1/+0.7)
		//-y = backward. (-1/-0.7)
		//+x = left (+1/+0.7)
		//-x = right (-1/-0.7)
		if($x !== 0){
			if($x > 0) $this->location->yaw -= $x*$this->getVehicleSpeed()["left"];
			if($x < 0) $this->location->yaw -= $x*$this->getVehicleSpeed()["right"];
			$this->motion = $this->getDirectionVector();
		}

		if($y > 0){
			//forward
			$this->motion = $this->getDirectionVector()->multiply($y*$this->getVehicleSpeed()["forward"]);
			$this->location->yaw = $this->driver->getLocation()->getYaw();// - turn based on players rotation
		} elseif ($y < 0){
			//reverse
			$this->motion = $this->getDirectionVector()->multiply($y*$this->getVehicleSpeed()["backward"]);
		}
	}

    protected function broadcastMovement(bool $teleport = false) : void{
        $pk = new MovePlayerPacket();
        $pk->entityRuntimeId = $this->getId();
        $pk->position = $this->getOffsetPosition($this->getPosition());
        $pk->pitch = $this->getLocation()->getPitch();
        $pk->headYaw = $this->getLocation()->getYaw();
        $pk->yaw = $this->getLocation()->getYaw();
        $pk->mode = MovePlayerPacket::MODE_NORMAL;

        $this->getWorld()->broadcastPacketToViewers($this->getPosition(), $pk);
    }

	public function isVehicleEmpty(): bool{
		return ($this->driver === null && count($this->passengers) === 0);
	}

	public function getDriver(): ?Player{
		return $this->driver;
	}

	public function setDriver(Player $player, bool $override = false): bool{
		if($this->driver !== null) {
			if($override) $this->removeDriver();
			else return false;
		}

		$player->getNetworkProperties()->setGenericFlag(EntityMetadataFlags::RIDING, true);
		$player->getNetworkProperties()->setGenericFlag(EntityMetadataFlags::SITTING, true);
		$player->getNetworkProperties()->setGenericFlag(EntityMetadataFlags::WASD_CONTROLLED, true);
		$player->getNetworkProperties()->setVector3(EntityMetadataProperties::RIDER_SEAT_POSITION, $this->getVehicleDriverSeat());
		// TODO Possibly limit rotation.

		$this->getNetworkProperties()->setGenericFlag(EntityMetadataFlags::SADDLED, true);
		$this->driver = $player;
		Main::$inVehicle[$this->driver->getUniqueId()->toString()] = $this;
		$player->sendMessage("You are now driving this vehicle.");
		$this->broadcastLink($this->driver);
		$player->sendTip("Sneak/Jump to leave the vehicle.");
		return true;
	}

	public function removeDriver(?string $message = "You are no longer driving this vehicle."): bool{
		if($this->driver === null) return false;
		$this->driver->getNetworkProperties()->setGenericFlag(EntityMetadataFlags::RIDING, false);
		$this->driver->getNetworkProperties()->setGenericFlag(EntityMetadataFlags::SITTING, false);
		$this->driver->getNetworkProperties()->setGenericFlag(EntityMetadataFlags::WASD_CONTROLLED, false);

		$this->getNetworkProperties()->setGenericFlag(EntityMetadataFlags::SADDLED, false);
		if($message !== null) $this->driver->sendMessage($message);
		$this->broadcastLink($this->driver, EntityLink::TYPE_REMOVE);
		unset(Main::$inVehicle[$this->driver->getUniqueId()->toString()]);
		$this->driver = null;
		return true;
	}

	/**
	 * @return Player[]
	 */
	public function getPassengers(){
		return $this->passengers;
	}

	public function addPassenger(Player $player, ?int $seat = null, bool $force = false): bool{
		if((count($this->getPassengers())) === count($this->getVehiclePassengerSeats()) || isset($this->getPassengers()[$seat])){
			if($force && $seat === null) return false;
			if(!$force) return false;
			if(!$this->removePassengerBySeat($seat, "Your seat has been given to '{$player->getName()}'")) throw new LogicException("Well this is embarrassing... (who knew 1 !== 1)");
		}
		if($seat === null){
			$seat = $this->getNextPassengerSeat();
			if($seat === null) return false; //No space...
		}
		$this->passengers[$seat] = $player;
		Main::$inVehicle[$player->getUniqueId()->toString()] = $this;
		$player->getNetworkProperties()->setGenericFlag(EntityMetadataFlags::RIDING, true);
		$player->getNetworkProperties()->setGenericFlag(EntityMetadataFlags::SITTING, true);
		$player->getNetworkProperties()->setVector3(EntityMetadataProperties::RIDER_SEAT_POSITION, $this->getVehiclePassengerSeats()[$seat]);
		$this->broadcastLink($player, EntityLink::TYPE_PASSENGER);
		$player->sendTip("Sneak/Jump to leave the vehicle.");
		return true;
	}

	public function removePassengerBySeat(int $seat, ?string $message = null): bool{
		if(isset($this->passengers[$seat])){
			$player = $this->passengers[$seat];
			unset($this->passengers[$seat]);
			unset(Main::$inVehicle[$player->getUniqueId()->toString()]);
			$player->getNetworkProperties()->setGenericFlag(EntityMetadataFlags::RIDING, false);
			$player->getNetworkProperties()->setGenericFlag(EntityMetadataFlags::SITTING, false);
			$this->broadcastLink($player, EntityLink::TYPE_REMOVE);
			if($message !== null) $player->sendMessage($message);
			return true;
		}
		return false;
	}

	/**
	 * @param Player|UUID $player
	 * @param string|null $message
	 * @return bool
	 */
	public function removePassenger($player, ?string $message = null): bool{
		if($player instanceof Player) $player = $player->getUniqueId();
		foreach(array_keys($this->passengers) as $i){
			if($this->passengers[$i]->getUniqueId() === $player){
				return $this->removePassengerBySeat($i, $message);
			}
		}
		return false;
	}

	public function removePlayer(Player $player): bool{
		if($this->driver !== null){
			if($this->driver->getUniqueId() === $player->getUniqueId()) return $this->removeDriver();
		}
		return $this->removePassenger($player);
	}

	public function getNextPassengerSeat(): ?int{
		$max = count($this->getVehiclePassengerSeats());
		$current = count($this->passengers);
		if($max === $current) return null;
		for($i = 0; $i < $max; $i++){
			if(!isset($this->passengers[$i])) return $i;
		}
		throw new LogicException("No seat found when max seats doesnt match currently used seats.");
	}
}
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

use LogicException;
use pocketmine\Player;
use pocketmine\level\Level;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\EntityLink;
use pocketmine\utils\UUID;

class Vehicle extends VehicleBase
{
	/** @var Player|null */
	private $driver = null;

	/** @var Player[] */
	private $passengers = [];

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
			if($x > 0) $this->yaw -= $x*$this->getVehicleSpeed()["left"];
			if($x < 0) $this->yaw -= $x*$this->getVehicleSpeed()["right"];
			$this->motion = $this->getDirectionVector();
		}

		if($y > 0){
			//forward
			$this->motion = $this->getDirectionVector()->multiply($y*$this->getVehicleSpeed()["forward"]);
			$this->yaw = $this->driver->getYaw();// - turn based on players rotation
		} elseif ($y < 0){
			//reverse
			$this->motion = $this->getDirectionVector()->multiply($y*$this->getVehicleSpeed()["backward"]);
		}
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

		$player->setGenericFlag(self::DATA_FLAG_RIDING, true);
		$player->setGenericFlag(self::DATA_FLAG_SITTING, true);
		$player->setGenericFlag(self::DATA_FLAG_WASD_CONTROLLED, true);
		$player->getDataPropertyManager()->setVector3(self::DATA_RIDER_SEAT_POSITION, $this->getVehicleDriverSeat());

		$this->setGenericFlag(self::DATA_FLAG_SADDLED, true);
		$this->driver = $player;
		Main::$inVehicle[$this->driver->getRawUniqueId()] = $this;
		$player->sendMessage("You are now driving this vehicle.");
		$this->broadcastLink($this->driver);
		$player->sendTip("Sneak/Jump to leave the vehicle.");
		return true;
	}

	public function removeDriver(?string $message = "You are no longer driving this vehicle."): bool{
		if($this->driver === null) return false;
		$this->driver->setGenericFlag(self::DATA_FLAG_RIDING, false);
		$this->driver->setGenericFlag(self::DATA_FLAG_SITTING, false);
		$this->driver->setGenericFlag(self::DATA_FLAG_WASD_CONTROLLED, false);

		$this->setGenericFlag(self::DATA_FLAG_SADDLED, false);
		if($message !== null) $this->driver->sendMessage($message);
		$this->broadcastLink($this->driver, EntityLink::TYPE_REMOVE);
		unset(Main::$inVehicle[$this->driver->getRawUniqueId()]);
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
		Main::$inVehicle[$player->getRawUniqueId()] = $this;
		$player->setGenericFlag(self::DATA_FLAG_RIDING, true);
		$player->setGenericFlag(self::DATA_FLAG_SITTING, true);
		$player->getDataPropertyManager()->setVector3(self::DATA_RIDER_SEAT_POSITION, $this->getVehiclePassengerSeats()[$seat]);
		$this->broadcastLink($player, EntityLink::TYPE_PASSENGER);
		$player->sendTip("Sneak/Jump to leave the vehicle.");
		return true;
	}

	public function removePassengerBySeat(int $seat, ?string $message = null): bool{
		if(isset($this->passengers[$seat])){
			$player = $this->passengers[$seat];
			unset($this->passengers[$seat]);
			unset(Main::$inVehicle[$player->getRawUniqueId()]);
			$player->setGenericFlag(self::DATA_FLAG_RIDING, false);
			$player->setGenericFlag(self::DATA_FLAG_SITTING, false);
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
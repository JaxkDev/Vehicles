<?php
/*
 * Vehicles, PocketMine-MP Plugin.
 *
 * Licensed under the Open Software License version 3.0 (OSL-3.0)
 * Copyright (C) 2019-present JaxkDev
 *
 * Twitter :: @JaxkDev
 * Discord :: JaxkDev#2698
 * Email   :: JaxkDev@gmail.com
 */

declare(strict_types=1);

namespace JaxkDev\Vehicles;

use LogicException;
use pocketmine\player\Player;
use pocketmine\entity\Location;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;
use pocketmine\network\mcpe\protocol\types\entity\EntityLink;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\Server;
use Ramsey\Uuid\Uuid;

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

    public function doRidingMovement(float $motionX, float $motionZ, float $yaw, float $pitch=0.0): void {
        $speed_factor = 2 * 1.5;
        $direction_plane = $this->getDirectionPlane();
        $x = $direction_plane->x / $speed_factor;
        $z = $direction_plane->y / $speed_factor;

        if(!$this->isOnGround()) {
            if($this->motion->y > -$this->gravity * 2) {
                $this->motion->y = -$this->gravity * 2;
            } else {
                $this->motion->y -= $this->gravity;
            }
        } else {
            $this->motion->y -= $this->gravity;
        }

        $finalMotionX = 0;
        $finalMotionZ = 0;

        switch($motionZ) {
            case 1:
                $finalMotionX = $x;
                $finalMotionZ = $z;
                break;
            case 0:
                break;
            case -1:
                $finalMotionX = -$x;
                $finalMotionZ = -$z;
                break;
            default:
                $average = $x + $z / 2;
                $finalMotionX = $average / 1.414 * $motionZ;
                $finalMotionZ = $average / 1.414 * $motionX;
                break;
        }

        switch($motionX) {
            case 1:
                $finalMotionX = $z;
                $finalMotionZ = -$x;
                break;
            case 0:
                break;
            case -1:
                $finalMotionX = -$z;
                $finalMotionZ = $x;
                break;
        }

        if (!$this->isClosed()){
            $this->setRotation($yaw, $pitch);
            $this->move($finalMotionX, $this->motion->y, $finalMotionZ);
            $this->updateMovement();
            if ($this->getDriver() instanceof Player){
                $this->getDriver()->setRotation($yaw, $pitch);
                $this->getDriver()->move($finalMotionX, $this->motion->y, $finalMotionZ);
                $this->getDriver()->updateMovement();
                foreach ($this->getPassengers() as $passenger){
                    if ($passenger instanceof Player){
                        $passenger->setRotation($yaw, $pitch);
                        $passenger->move($finalMotionX, $this->motion->y, $finalMotionZ);
                        $passenger->updateMovement();
                    }
                }
            }
        }
    }

    protected function broadcastMovement(bool $teleport = false) : void{
        $pk = new MovePlayerPacket();
        $pk->actorRuntimeId = $this->getId();
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
	public function getPassengers(): array{
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

	public function removePassenger(Player|Uuid $player, ?string $message = null): bool{
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
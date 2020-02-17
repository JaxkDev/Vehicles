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
use pocketmine\utils\UUID;
use pocketmine\level\Level;
use pocketmine\entity\Entity;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\entity\Rideable;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\SkinData;
use pocketmine\network\mcpe\protocol\AddPlayerPacket;
use pocketmine\network\mcpe\protocol\PlayerListPacket;
use pocketmine\network\mcpe\protocol\types\EntityLink;
use pocketmine\network\mcpe\protocol\SetActorLinkPacket;
use pocketmine\network\mcpe\protocol\types\PlayerListEntry;
use JaxkDev\Vehicles\Exceptions\VehicleException;

class VehicleBase extends Entity implements Rideable
{
	public const VEHICLE_TYPE_LAND = 0;
	public const VEHICLE_TYPE_WATER = 1;
	public const VEHICLE_TYPE_AIR = 2;
	public const VEHICLE_TYPE_RAIL = 3;
	public const VEHICLE_TYPE_UNKNOWN = 9;

	public const NETWORK_ID = 23;

	/** @var UUID|null */
	protected $uuid = null;

	public $gravity = 1;

	public $width = 1;
	public $height = 1;

	//---------------------

	/** @var Main|null */
	private $plugin = null;

	/** @var string|null */
	private $name = null;

	/** @var int|null */
	private $version = null;

	/** @var int */
	private $type = 9;

	/** @var float */
	private $scale = 1.0;

	/** @var string|null */
	private $designName = null;

	/** @var SkinData|null */
	private $design = null;

	private $bbox = [0,0,0,0,0,0];
	private $seats = ["driver" => [], "passengers" => []];
	private $speed = ["forward" => null, "backward" => null, "left" => null, "right" => null];

	public function __construct(Level $level, CompoundTag $nbt)
	{
		$this->plugin = Main::getInstance();
		$this->loadFromNBT($nbt);
		parent::__construct($level, $nbt);
		$this->saveIntoNBT(); //Save anything that reverted to default.
	}

	/**
	 * @param CompoundTag $nbt
	 * @throws VehicleException
	 */
	public function loadFromNBT(CompoundTag $nbt): void{
		if(Main::$vehicleDataVersion !== $nbt->getInt("vehicle", -1)){
			throw new VehicleException("Vehicle version {$nbt->getInt("vehicle",-1)} does not match expected version ".Main::$vehicleDataVersion);
		}
		$this->version = $nbt->getInt("vehicle");

		/** @var CompoundTag $data */
		$data = $nbt->getCompoundTag("vehicleData");

		$this->uuid = UUID::fromString($data->getString("uuid", UUID::fromRandom()->toString()));
		$this->type = $data->getInt("type", 9);
		$this->name = $data->getString("name");
		$this->designName = $data->getString("design");
		if($this->designName === null) throw new VehicleException("Vehicle '{$this->name}' has no design stored.");
		$this->design = $this->plugin->factory->getDesign($this->designName);
		$this->gravity = $data->getDouble("gravity", 1.0);
		$this->scale = $data->getFloat("scale", 1.0);

		$this->speed["forward"] = $data->getDouble("forwardSpeed", 1.0);
		$this->speed["backward"] = $data->getDouble("backwardSpeed", 1.0);
		$this->speed["left"] = $data->getDouble("leftSpeed", 1.0);
		$this->speed["right"] = $data->getDouble("rightSpeed", 1.0);

		$this->bbox = $data->getListTag("bbox")->getAllValues();

		$this->width = max($this->bbox[0],$this->bbox[3])-min($this->bbox[0],$this->bbox[3]);
		$this->height = max($this->bbox[1],$this->bbox[4])-min($this->bbox[1],$this->bbox[4]);

		$this->seats["driver"] = $data->getListTag("driverSeat")->getAllValues();

		foreach($data->getListTag("passengerSeats")->getAllValues() as $ltag){
			$this->seats["passengers"][] = $ltag->getAllValues();
		}

	}

	public function saveIntoNBT(): void{
		$nbt = $this->namedtag;
		$nbt->setInt("vehicle", $this->version ?? Main::$vehicleDataVersion);

		$passengerSeats = [];

		foreach($this->seats["passengers"] as $seat){
			$passengerSeats[] = new ListTag("", [
				new FloatTag("x", $seat[0]),
				new FloatTag("y", $seat[1]),
				new FloatTag("z", $seat[2])
			]);
		}

		$vehicleData = new CompoundTag("vehicleData", [
			new IntTag("type", $this->type),
			new StringTag("uuid", $this->uuid->toString()),
			new StringTag("name", $this->name),
			new StringTag("design", $this->designName),
			new DoubleTag("gravity", $this->gravity),
			new DoubleTag("forwardSpeed", $this->speed["forward"]),
			new DoubleTag("backwardSpeed", $this->speed["backward"]),
			new DoubleTag("leftSpeed", $this->speed["left"]),
			new DoubleTag("rightSpeed", $this->speed["right"]),
			new ListTag("bbox", [
				new FloatTag("x", $this->bbox[0]),
				new FloatTag("y", $this->bbox[1]),
				new FloatTag("z", $this->bbox[2]),
				new FloatTag("x2", $this->bbox[3]),
				new FloatTag("y2", $this->bbox[4]),
				new FloatTag("z2", $this->bbox[5]),
			]),
			new ListTag("driverSeat", [
				new FloatTag("x", $this->seats["driver"][0]),
				new FloatTag("y", $this->seats["driver"][1]),
				new FloatTag("z", $this->seats["driver"][2]),
			]),
			new ListTag("passengerSeats", $passengerSeats)
		]);

		$nbt->setTag($vehicleData, true);
		$this->saveNBT();
	}

	public function getUUID(): ?UUID{
		return $this->uuid;
	}

	public function getVehicleName(): ?string{
		return $this->name;
	}

	public function getVehicleVersion(): ?int{
		return $this->version;
	}

	public function getVehicleType(): ?int{
		return $this->type;
	}

	public function getVehicleScale(): float{
		return $this->scale;
	}

	public function getVehicleDesignName(): ?string{
		return $this->designName;
	}

	public function getVehicleDesign(): ?SkinData{
		return $this->design;
	}

	public function getVehicleSpeed(): array{
		return $this->speed;
	}

	public function getVehicleSeats(): array{
		return $this->seats;
	}

	public function getVehicleDriverSeat(): array{
		return $this->seats["driver"];
	}

	public function getVehiclePassengerSeats(): array{
		return $this->seats["passengers"];
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
}
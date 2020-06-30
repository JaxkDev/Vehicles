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

use pocketmine\uuid\UUID;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\entity\Entity;
use pocketmine\entity\Location;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\SkinData;
use pocketmine\network\mcpe\protocol\AddPlayerPacket;
use pocketmine\network\mcpe\protocol\PlayerListPacket;
use pocketmine\network\mcpe\protocol\SetActorLinkPacket;
use pocketmine\network\mcpe\protocol\types\PlayerListEntry;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\network\mcpe\protocol\types\entity\EntityLink;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStack;

use JaxkDev\Vehicles\Exceptions\VehicleException;

class VehicleBase extends Entity
{
	public const VEHICLE_TYPE_LAND = 0;
	public const VEHICLE_TYPE_WATER = 1;
	public const VEHICLE_TYPE_AIR = 2;
	public const VEHICLE_TYPE_RAIL = 3;
	public const VEHICLE_TYPE_UNKNOWN = 9;

	/** @var UUID|null */
	protected $uuid = null;

	/** @var float */
	public $gravity = 1.0;

	/** @var float */
	public $width = 1.0;

	/** @var float */
	public $height = 1.0;

	/** @var float */
	public $baseOffset = 1.0;

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
	protected $scale = 1.0;

	/** @var string|null */
	private $designName = null;

	/** @var SkinData|null */
	private $design = null;

	/** @var float[] */
	private $bbox = [0, 0, 0, 0, 0, 0];

	/**
	 * @var array
	 * @phpstan-var array<string, null|Vector3|array<Vector3>>
	 */
	private $seats = ["driver" => null, "passengers" => []];

	/**
	 * @var array
	 * @phpstan-var array<string, float|null>
	 */
	private $speed = ["forward" => null, "backward" => null, "left" => null, "right" => null];

	public function __construct(Location $loc, CompoundTag $nbt)
	{
		var_dump("construct");
		$this->plugin = Main::getInstance();
		parent::__construct($loc, $nbt);
		$this->loadFromNBT($nbt);
		$this->setCanSaveWithChunk(true);
	}

	/**
	 * @param CompoundTag $nbt
	 * @throws VehicleException
	 */
	public function loadFromNBT(CompoundTag $nbt): void
	{
		var_dump("base - loadNbt");
		if (Main::$vehicleDataVersion !== $nbt->getInt("vehicle", -1)) {
			//TODO
			throw new VehicleException("Vehicle version {$nbt->getInt("vehicle",-1)} does not match expected version " . Main::$vehicleDataVersion);
		}
		$this->version = $nbt->getInt("vehicle");

		/** @var CompoundTag $data */
		$data = $nbt->getCompoundTag("vehicleData");

		$this->uuid = UUID::fromString($data->getString("uuid", UUID::fromRandom()->toString()));
		$this->type = $data->getInt("type", 9);
		$this->name = $data->getString("name");
		$this->designName = $data->getString("design");
		if ($this->designName === null) throw new VehicleException("Vehicle '{$this->name}' has no design stored.");
		$this->design = $this->plugin->factory->getDesign($this->designName);
		$this->gravity = $data->getFloat("gravity", 1.0);
		$this->scale = $data->getFloat("scale", 1.0);
		$this->baseOffset = $data->getFloat("baseOffset", 1.0);

		$this->speed["forward"] = $data->getFloat("forwardSpeed", 1.0);
		$this->speed["backward"] = $data->getFloat("backwardSpeed", 1.0);
		$this->speed["left"] = $data->getFloat("leftSpeed", 1.0);
		$this->speed["right"] = $data->getFloat("rightSpeed", 1.0);

		$this->bbox = $data->getListTag("bbox")->getAllValues();

		$this->width = max(max($this->bbox[0], $this->bbox[3]) - min($this->bbox[0], $this->bbox[3]), max($this->bbox[2], $this->bbox[5]) - min($this->bbox[2], $this->bbox[5]));
		$this->height = max($this->bbox[1], $this->bbox[4]) - min($this->bbox[1], $this->bbox[4]);

		$seat = $data->getListTag("driverSeat")->getAllValues();
		$this->seats["driver"] = new Vector3($seat[0], $seat[1], $seat[2]);

		foreach ($data->getListTag("passengerSeats")->getAllValues() as $ltag) {
			$seat = $ltag->getAllValues();
			$this->seats["passengers"][] = new Vector3($seat[0], $seat[1], $seat[2]);
		}

		// Handlers
		$this->setScale($this->scale); //TODO BBox
	}

	public function saveNBT(): CompoundTag
	{
		var_dump("base-saveNbt");
		$nbt = parent::saveNBT();
		$nbt->setInt("vehicle", $this->version ?? Main::$vehicleDataVersion);

		$passengerSeats = new ListTag();

		/** @var Vector3 $seat */
		foreach ($this->seats["passengers"] as $seat) {
			$passengerSeats->push(new ListTag([
				new FloatTag($seat->getX()),
				new FloatTag($seat->getY()),
				new FloatTag($seat->getZ())
			]));
		}

		$vehicleData = new CompoundTag();
		$vehicleData->setInt("type", $this->type)
			->setString("uuid", $this->uuid->toString())
			->setString("name", $this->name)
			->setString("design", $this->designName)
			->setFloat("gravity", $this->gravity)
			->setFloat("scale", $this->scale)
			->setFloat("baseOffset", $this->baseOffset)
			->setFloat("forwardSpeed", $this->speed["forward"])
			->setFloat("backwardSpeed", $this->speed["backward"])
			->setFloat("leftSpeed", $this->speed["left"])
			->setFloat("rightSpeed", $this->speed["right"])
			->setTag("bbox", new ListTag([
				new FloatTag($this->bbox[0]),
				new FloatTag($this->bbox[1]),
				new FloatTag($this->bbox[2]),
				new FloatTag($this->bbox[3]),
				new FloatTag($this->bbox[4]),
				new FloatTag($this->bbox[5]),
			]))
			->setTag("driverSeat", new ListTag([
				new FloatTag($this->seats["driver"]->getX()),
				new FloatTag($this->seats["driver"]->getY()),
				new FloatTag($this->seats["driver"]->getZ()),
			]))
			->setTag("passengerSeats", $passengerSeats);

		$nbt->setTag("vehicleData", $vehicleData);
		return $nbt;
	}

	public function getUUID(): ?UUID
	{
		return $this->uuid;
	}

	public function getVehicleName(): ?string
	{
		return $this->name;
	}

	public function getVehicleVersion(): ?int
	{
		return $this->version;
	}

	public function getVehicleType(): ?int
	{
		return $this->type;
	}

	public function getVehicleScale(): float
	{
		return $this->scale;
	}

	public function getVehicleDesignName(): ?string
	{
		return $this->designName;
	}

	public function getVehicleDesign(): ?SkinData
	{
		return $this->design;
	}

	/**
	 * @return array<string, float|null>
	 */
	public function getVehicleSpeed()
	{
		return $this->speed;
	}

	/**
	 * @return array<string, null|Vector3|array<Vector3>>
	 */
	public function getVehicleSeats()
	{
		return $this->seats;
	}

	public function getVehicleDriverSeat(): Vector3
	{
		return $this->seats["driver"];
	}

	/**
	 * @return Vector3[]
	 */
	public function getVehiclePassengerSeats(): array
	{
		return $this->seats["passengers"];
	}

	protected function sendSpawnPacket(Player $player): void
	{
		$skin = $this->getVehicleDesign();

		//Below adds the entity ID + skin to the list to be used in the AddPlayerPacket (WITHOUT THIS DEFAULT/NO SKIN WILL BE USED).
		$pk = new PlayerListPacket();
		$pk->type = PlayerListPacket::TYPE_ADD;
		$pk->entries[] = PlayerListEntry::createAdditionEntry($this->uuid, $this->id, $this->getVehicleName() . "-" . $this->id, $skin);
		$player->getNetworkSession()->sendDataPacket($pk);

		//Below adds the actual entity and puts the pieces together.
		$pk = new AddPlayerPacket();
		$pk->uuid = $this->uuid;
		$pk->item = ItemStack::null();
		$pk->motion = $this->getMotion();
		$pk->position = $this->getPosition();
		$pk->entityRuntimeId = $this->getId();
		$pk->metadata = $this->getNetworkProperties()->getAll();
		$pk->username = $this->getVehicleName() . "-" . $this->id; //Unique.
		$player->getNetworkSession()->sendDataPacket($pk);

		//Dont want to keep a fake person there...
		$pk = new PlayerListPacket();
		$pk->type = $pk::TYPE_REMOVE;
		$pk->entries = [PlayerListEntry::createRemovalEntry($this->uuid)];
		$player->getNetworkSession()->sendDataPacket($pk);
	}

	//Without this the player will not do the things it should be (driving, sitting etc)
	protected function broadcastLink(Player $player, int $type = EntityLink::TYPE_RIDER): void
	{
		foreach ($this->getViewers() as $viewer) {
			$player->spawnTo($viewer);
			$pk = new SetActorLinkPacket();
			$pk->link = new EntityLink($this->getId(), $player->getId(), $type, true, true);
			$viewer->getNetworkSession()->sendDataPacket($pk);
		}
	}

	public static function getNetworkTypeId(): string
	{
		return EntityIds::PLAYER;
	}
}
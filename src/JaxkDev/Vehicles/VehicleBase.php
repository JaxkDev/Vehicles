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

use pocketmine\entity\Entity;
use pocketmine\entity\Rideable;
use pocketmine\entity\EntityIds;
use pocketmine\network\mcpe\protocol\types\SkinData;

class VehicleBase extends Entity implements Rideable
{
	public const VEHICLE_TYPE_LAND = 0;
	public const VEHICLE_TYPE_WATER = 1;
	public const VEHICLE_TYPE_AIR = 2;
	public const VEHICLE_TYPE_RAIL = 3;
	public const VEHICLE_TYPE_OTHER = 9;

	public const NETWORK_ID = EntityIds::PLAYER;

	private $seats = ["driver" => [], "passengers" => []];
	private $speed = ["forward" => null, "backward" => null, "left" => null, "right" => null];

	/** @var string|null */
	private $name = null;

	/** @var SkinData|null */
	private $design = null;

	/** @var int|null */
	private $version = null;

	/** @var float|null */
	private $gravity = null;
}
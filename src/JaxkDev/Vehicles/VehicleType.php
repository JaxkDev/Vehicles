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

interface VehicleType{
	public const TYPE_LAND = 0;
	public const TYPE_WATER = 1;
	public const TYPE_AIR = 2;
	public const TYPE_RAIL = 3;

	public const TYPE_OTHER = 9;
}
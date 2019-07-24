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

class CarVehicle extends Vehicle
{
	static function getVehicleName(): string
	{
		return "Basic-Car";
	}

	static function getSaveNames(): array
	{
		return ["Basic-Car","BasicCar"];
	}
}
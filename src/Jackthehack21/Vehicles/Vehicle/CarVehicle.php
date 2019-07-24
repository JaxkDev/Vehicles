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
use pocketmine\entity\Skin;

class CarVehicle extends Vehicle
{

	public $width = 1; //Todo measure once i have a skin.
	public $height = 3;

	static function getVehicleName(): string
	{
		return "Basic-Car";
	}

	static function getSaveNames(): array
	{
		return ["Basic-Car","BasicCar"];
	}

	static function getSkin(): Skin
	{
		return Main::getSkin("Basic-Car");
	}

	//TODO Spawn packet.
}
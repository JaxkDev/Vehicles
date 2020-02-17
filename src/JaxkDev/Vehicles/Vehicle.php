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

use pocketmine\level\Level;
use pocketmine\nbt\tag\CompoundTag;

class Vehicle extends VehicleBase
{
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

	/**
	 * Handle player input.
	 * @param float $x
	 * @param float $y
	 */
	public function updateMotion(float $x, float $y): void{
		//todo
	}

	//TODO Logic (eg passengers)
}
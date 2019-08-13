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
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\level\Level;
use pocketmine\utils\UUID;
use pocketmine\Player;

class TestCar extends Vehicle {
	public $width = 3; //rough, probably no where near.
	public $height = 2;

	protected $baseOffset = 1.615;

	public function __construct(Level $level, CompoundTag $nbt)
	{
		$this->uuid = UUID::fromRandom();
		parent::__construct($level, $nbt);
		$this->setNameTagAlwaysVisible(true);
		$this->setCanSaveWithChunk(true);

		$this->setScale(0.85);

		//driver - [ 1.57, 0.5, -1 ]
	}

	public function recalculateBoundingBox(): void
	{
		//todo....
		parent::recalculateBoundingBox();
	}

	static function getName(): string{
		return "Test-Car";
	}

	static function getDesign(): Skin
	{
		return Main::getInstance()->designFactory->getDesign("Test-Car");
	}

	protected function sendSpawnPacket(Player $player) : void{
		parent::sendInitPacket($player, $this);
	}

	/**
	 * Handle player input.
	 * @param float $x
	 * @param float $y
	 */
	function updateMotion(float $x, float $y): void
	{
		if($x > 0 or $x < 0){
			$this->yaw = $this->driver->getYaw();
		}

		if($y > 0){
			$this->motion = $this->getDirectionVector()->multiply($y);
			$this->yaw = $this->driver->getYaw();
		} elseif ($y < 0){
			$this->motion = $this->getDirectionVector()->multiply($y);
			//$this->yaw = 0-$this->driver->getYaw();
		}

	}
}
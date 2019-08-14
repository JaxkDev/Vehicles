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
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\level\Level;
use pocketmine\utils\UUID;
use pocketmine\Player;

class BasicCar extends Vehicle {
	public $width = 3; //rough, probably no where near.
	public $height = 2;

	protected $baseOffset = 1.615;
	protected $driverPosition = null;

	public function __construct(Level $level, CompoundTag $nbt)
	{
		$this->driverPosition = new Vector3(0.55, $this->height-2.4, 0.1);
		$this->uuid = UUID::fromRandom();
		parent::__construct($level, $nbt);
		$this->setNameTagAlwaysVisible(true);
		$this->setCanSaveWithChunk(true);

		$this->setScale(1.4);
	}

	public function recalculateBoundingBox(): void
	{
		//todo....
		parent::recalculateBoundingBox();
	}

	static function getName(): string{
		return "Basic-Car";
	}

	static function getDesign(): Skin
	{
		return Main::getInstance()->designFactory->getDesign(self::getName());
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
		//				(1 if only one button, 0.7 if two)
		//+y = forward. (+1/+0.7)
		//-y = backward. (-1/-0.7)
		//+x = left (+1/+0.7)
		//-x = right (-1/-0.7)
		//var_dump($x,$y);
		//todo find the cause of entity rotating 45^ ish when reversing.
		if($x !== 0){
			$this->yaw -= $x*3;
			$this->motion = $this->getDirectionVector();
		}

		if($y > 0){
			//forward
			$this->motion = $this->getDirectionVector()->multiply($y);
			//$this->yaw = $this->driver->getYaw(); - turn based on players rotation
		} elseif ($y < 0){
			//reverse
			$this->motion = $this->getDirectionVector()->multiply($y);
		}
	}
}
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
	public $width = 5; //rough, probably no where near.
	public $height = 2;

	protected $baseOffset = 1.615;

	public function __construct(Level $level, CompoundTag $nbt)
	{
		$this->uuid = UUID::fromRandom();
		parent::__construct($level, $nbt);
		$this->setNameTagAlwaysVisible(false);
		$this->setCanSaveWithChunk(true);
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
}
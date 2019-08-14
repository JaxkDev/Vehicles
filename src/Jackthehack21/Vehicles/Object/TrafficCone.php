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

namespace Jackthehack21\Vehicles\Object;

use Jackthehack21\Vehicles\Main;

use pocketmine\nbt\tag\CompoundTag;
use pocketmine\entity\Skin;
use pocketmine\level\Level;
use pocketmine\Player;

class TrafficCone extends DisplayObject{
	public $width = 0.6; //rough, probably no where near.
	public $height = 1;

	protected $baseOffset = 1.615;

	public function __construct(Level $level, CompoundTag $nbt)
	{
		parent::__construct($level, $nbt);
	}

	public function canBeMovedByCurrents() : bool{
		return true;
	}

	static function getName(): string{
		return "Traffic-Cone";
	}

	static function getDesign(): Skin
	{
		return Main::getInstance()->designFactory->getDesign(self::getName());
	}

	protected function sendSpawnPacket(Player $player) : void{
		parent::sendInitPacket($player, $this);
	}
}
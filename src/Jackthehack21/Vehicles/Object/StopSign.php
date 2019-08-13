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
use pocketmine\utils\UUID;
use pocketmine\Player;

class StopSign extends DisplayObject{
	public $width = 0.6; //rough, probably no where near.
	public $height = 3;

	protected $baseOffset = 1.615;

	public function __construct(Level $level, CompoundTag $nbt)
	{
		$this->uuid = UUID::fromRandom();
		parent::__construct($level, $nbt);
		$this->setNameTagAlwaysVisible(false);
		$this->setCanSaveWithChunk(true);

		$this->setScale(0.26);
	}

	static function getName(): string{
		return "Stop-Sign";
	}

	static function getDesign(): Skin
	{
		return Main::getInstance()->designFactory->getDesign("Stop-Sign");
	}

	protected function sendSpawnPacket(Player $player) : void{
		parent::sendInitPacket($player, $this);
	}
}
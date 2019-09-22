<?php
/*
 * Vehicles, PocketMine-MP Plugin.
 *
 * Licensed under the Open Software License version 3.0 (OSL-3.0)
 * Copyright (C) 2019 JaxkDev
 *
 * Twitter :: @JaxkDev
 * Discord :: Jackthehaxk21#8860
 * Email   :: JaxkDev@gmail.com
 */

declare(strict_types=1);

namespace JaxkDev\Vehicles\Object;

use JaxkDev\Vehicles\Main;

use pocketmine\nbt\tag\CompoundTag;
use pocketmine\entity\Skin;
use pocketmine\level\Level;
use pocketmine\Player;

class NoEntrySign extends DisplayObject{
	public $width = 0.6; //rough, probably no where near.
	public $height = 3;

	protected $baseOffset = 1.615;

	public function __construct(Level $level, CompoundTag $nbt)
	{
		parent::__construct($level, $nbt);
		$this->setScale(0.26);
	}

	static function getName(): string{
		return "No-Entry-Sign";
	}

	static function getDesign(): Skin
	{
		return Main::getInstance()->designFactory->getDesign(self::getName());
	}

	protected function sendSpawnPacket(Player $player) : void{
		parent::sendInitPacket($player, $this);
	}
}
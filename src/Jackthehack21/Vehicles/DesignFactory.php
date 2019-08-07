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

namespace Jackthehack21\Vehicles;

use pocketmine\entity\Skin;

class DesignFactory{

	/** @var Main */
	private $plugin;

	public function __construct(Main $plugin)
	{
		$this->plugin = $plugin;
	}

	/**
	 * Retrieve the Design for a vehicle.
	 * @param string $name
	 * @return Skin|null
	 */
	public function getDesign(string $name): ?Skin{
		foreach($this->designs as $designName => $design){
			if(strtolower($name) === strtolower($designName)) return $design;
		}
		return null;
	}

	/**
	 * Return the RGBA Byte array ready for use from a UV Map (png)
	 * @param string $path
	 * @return string|null
	 */
	public function readDesignFile(string $path): ?string{
		$img = @imagecreatefrompng($path);
		$bytes = '';
		$l = (int) @getimagesize($path)[1];
		for ($y = 0; $y < $l; $y++) {
			for ($x = 0; $x < 64; $x++) {
				$rgba = @imagecolorat($img, $x, $y);
				$a = ((~((int)($rgba >> 24))) << 1) & 0xff;
				$r = ($rgba >> 16) & 0xff;
				$g = ($rgba >> 8) & 0xff;
				$b = $rgba & 0xff;
				$bytes .= chr($r) . chr($g) . chr($b) . chr($a);
			}
		}
		@imagedestroy($img);
		return $bytes;
	}

	public static function getInstance() : self{
		return self::$instance;
	}
}
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

use Exception;
use InvalidArgumentException;

use pocketmine\entity\Skin;
use pocketmine\plugin\PluginException;

class DesignFactory{
	private static $instance;

	/** @var Main */
	private $plugin;

	/** @var String|Skin[] */
	private $designs = [];

	public function __construct(Main $plugin)
	{
		self::$instance = $this;
		$this->plugin = $plugin;
	}
	
	public function loadAll(): void{
		$this->plugin->saveResource("Designs/Design_Manifest.json",true);

		if(file_exists($this->plugin->getDataFolder()."Designs/Design_Manifest.json")){
			$designManifest = json_decode(file_get_contents($this->plugin->getDataFolder()."Designs/Design_Manifest.json"), true) ?? [];
			foreach($designManifest as $design){
				$this->plugin->saveResource("Designs/".$design["designFile"], true);
				$this->plugin->saveResource("Designs/".$design["geometryFile"], true);
				if(file_exists($this->plugin->getDataFolder()."Designs/".$design["designFile"])){
					$design["designData"] = $this->readDesignFile($this->plugin->getDataFolder()."Designs/".$design["designFile"]);
				} else {
					//todo
					throw new Exception("File '".$design["designFile"]."' does not exist.");
				}
				if(file_exists($this->plugin->getDataFolder()."Designs/".$design["geometryFile"])){
					$design["geometryData"] = json_decode(file_get_contents($this->plugin->getDataFolder()."Designs/".$design["geometryFile"]));
				} else {
					//todo
					throw new Exception("File '".$design["geometryFile"]."' does not exist.");
				}
				$this->designs[$design["name"]] = new Skin($design["designId"],$design["designData"],"",$design["geometryName"],json_encode($design["geometryData"]));
				try{
					$this->designs[$design["name"]]->validate();
				} catch (InvalidArgumentException $e){
					unset($this->designs[$design["name"]]);
					$this->plugin->getLogger()->warning("'".$design["name"]."' has not got valid skin data, and so it has been disabled.");
					continue;
				}
				$this->plugin->getLogger()->debug("Loaded '".$design["name"]."'");
			}
		}
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
		if(!extension_loaded("gd")) {
			throw new PluginException("GD library is not enabled, to load designs it must be enabled in php.ini");
		}
		$img = @imagecreatefrompng($path);
		$bytes = '';
		for ($y = 0; $y < imagesy($img); $y++) {
			for ($x = 0; $x < imagesx($img); $x++) {
				$rgba = @imagecolorat($img, $x, $y);
				$a = chr(((~((int)($rgba >> 24))) << 1) & 0xff);
				$r = chr(($rgba >> 16) & 0xff);
				$g = chr(($rgba >> 8) & 0xff);
				$b = chr($rgba & 0xff);
				$bytes .= $r.$g.$b.$a;
			}
		}
		@imagedestroy($img);
		return $bytes;
	}

	public static function getInstance() : self{
		return self::$instance;
	}
}
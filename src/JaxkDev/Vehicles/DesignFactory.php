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

namespace JaxkDev\Vehicles;

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
		$this->plugin->saveResource("Designs/Design_Manifest.json");

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
					$this->plugin->getLogger()->debug($e);
					$this->plugin->getLogger()->warning("'".$design["name"]."' has not got valid skin data, and so it has been disabled.");
					continue;
				}
				$this->plugin->getLogger()->debug("Loaded '".$design["name"]."'");
			}
			if(count($designManifest) === 0){
				$this->plugin->getLogger()->warning("No designs found in manifest, it is either invalid JSON or empty.");
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
	 * @return string|null RGBA Bytes to use.
	 * @throws Exception
	 */
	public function readDesignFile(string $path): ?string{
		$type = pathinfo($path, PATHINFO_EXTENSION);
		if($type === "png"){
			/*if(file_exists(rtrim($path,"png")."json")){
				$data = json_decode(file_get_contents($path));
				$data = base64_decode($data->data);
				$this->plugin->getLogger()->debug("Loaded design from generated json.");
				return $data;
			}*/
			if (!extension_loaded("gd")) {
				throw new PluginException("GD library is not enabled, to load designs it must be enabled. *See php.ini to enable it*");
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
					$bytes .= $r . $g . $b . $a;
				}
			}
			@imagedestroy($img);
			//file_put_contents(rtrim($path, "png") . "json", json_encode(["data" => base64_encode($bytes)]));
			$this->plugin->getLogger()->debug("Saved design to json.");
			return $bytes;
		} elseif ($type === "json") {
			$this->plugin->getLogger()->debug("Loaded design from original json.");
			$data = json_decode(file_get_contents($path));
			$data = base64_decode($data->data);
			return $data;
		} else throw new Exception("Unknown data type ${type} received.");
	}

	public static function getInstance() : self{
		return self::$instance;
	}
}
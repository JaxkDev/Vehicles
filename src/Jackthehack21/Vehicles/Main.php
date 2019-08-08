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
use pocketmine\entity\Skin;
use pocketmine\event\Listener;
use pocketmine\command\Command;
use pocketmine\plugin\PluginBase;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat as C;
use Jackthehack21\Vehicles\Vehicle\Vehicle;
use Jackthehack21\Vehicles\Object\DisplayObject;
use Jackthehack21\Vehicles\Object\ObjectFactory;
use pocketmine\event\entity\EntityDamageByEntityEvent;

use Jackthehack21\Vehicles\Vehicle\VehicleFactory; //weirdest namespace ive ever used (3x vehicles *lmao*).

class Main extends PluginBase implements Listener
{

	private static $instance;

	public $prefix = C::GRAY."[".C::AQUA."Vehicles".C::GRAY."] ".C::GOLD."> ".C::RESET;

	/** @var CommandHandler */
	private $commandHandler;

	/** @var VehicleFactory */
	public $vehicleFactory;

	/** @var ObjectFactory */
	public $objectFactory;

	/** @var String|Skin[] */
	private $designs = [];

	public function onLoad()
	{
		self::$instance = $this;
		$this->getServer()->getLogger()->debug($this->prefix."Loading all resources...");
		//resources here.
		//Parse data to load previous vehicles.
		//Prep all objects. (spawn onEnable)

		//Add handlers and others here.
		$this->commandHandler = new CommandHandler($this);
		$this->vehicleFactory = new VehicleFactory($this);
		$this->objectFactory = new ObjectFactory($this);

		$this->saveResource("Designs/Design_Manifest.json");
		//TODO CACHE AND ITS OWN HANDLER.
		if(file_exists($this->getDataFolder()."Designs/Design_Manifest.json")){
			$designManifest = json_decode(file_get_contents($this->getDataFolder()."Designs/Design_Manifest.json"), true) ?? [];
			foreach($designManifest as $design){
				$this->saveResource("Designs/".$design["designFile"]);
				$this->saveResource("Designs/".$design["geometryFile"]);
				if(file_exists($this->getDataFolder()."Designs/".$design["designFile"])){
					$design["designData"] = $this->readDesignFile($this->getDataFolder()."Designs/".$design["designFile"]);
				} else {
					throw new Exception("File '".$design["designFile"]."' does not exist.");
				}
				if(file_exists($this->getDataFolder()."Designs/".$design["geometryFile"])){
					$design["geometryData"] = json_decode(file_get_contents($this->getDataFolder()."Designs/".$design["geometryFile"]));
				} else {
					throw new Exception("File '".$design["geometryFile"]."' does not exist.");
				}
				$this->designs[$design["name"]] = new Skin($design["designId"],$design["designData"],"",$design["geometryName"],json_encode($design["geometryData"]));
				$this->designs[$design["name"]]->validate();
				if($this->designs[$design["name"]]->isValid()) $this->getServer()->getLogger()->debug($this->prefix."Loaded '".$design["name"]."'");
				else $this->getServer()->getLogger()->warning($this->prefix."'".$design["name"]."' has not got valid data.");
			}
		}
		$this->getServer()->getLogger()->debug($this->prefix."Resources now loaded !");
	}

	public function onInteract(EntityDamageByEntityEvent $event){
		if($event->getEntity() instanceof DisplayObject or $event->getEntity() instanceof Vehicle){
			$event->setCancelled(); //stops the ability to 'kill' a object/vehicle. (In long future, add vehicle condition *shrug*
			//todo sub commands like /vehicles remove
		}
	}

	public function onEnable()
	{
		$this->getServer()->getLogger()->debug($this->prefix."Registering objects...");
		$this->objectFactory->registerDefaultObjects();
		$this->getServer()->getLogger()->debug($this->prefix."That's all done now.");

		$this->getServer()->getLogger()->debug($this->prefix."Registering vehicles with the DVLA :)");
		$this->vehicleFactory->registerDefaultVehicles();
		$this->getServer()->getLogger()->debug($this->prefix."That's all done now, remember no speeding ! *chuckles*");

		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool
	{
		$this->commandHandler->handleCommand($sender, $command, $args);
		return true;
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
	 * Return the RGBA Byte array in string format ready for use by skin (from a UV Map (png))
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
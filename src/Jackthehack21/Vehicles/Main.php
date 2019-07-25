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
use pocketmine\event\Listener;
use pocketmine\command\Command;
use pocketmine\plugin\PluginBase;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat as C;

use Jackthehack21\Vehicles\Vehicle\VehicleFactory; //weirdest namespace ive ever used (3x vehicles *lmao*).

class Main extends PluginBase implements Listener
{

	private static $instance;

	public $prefix = C::GRAY."[".C::AQUA."Vehicles".C::GRAY."] ".C::GOLD."> ".C::RESET;

	/** @var CommandHandler */
	private $commandHandler;

	/** @var VehicleFactory */
	public $vehicleFactory;

	/** @var String|Skin[] */
	private $designs = [];

	public function onLoad()
	{
		self::$instance = $this;
		$this->getServer()->getLogger()->debug($this->prefix."Bringing back resources and any previous vehicles back from the dead...");
		//resources here.
		//Parse data to load previous vehicles.
		//Prep all objects. (spawn onEnable)

		//Add handlers and others here.
		$this->commandHandler = new CommandHandler($this);
		$this->vehicleFactory = new VehicleFactory($this);

		$this->saveResource("designs.json"); //TODO Save all designs in format design_VEHICLENAME.json to make custom skins easier.

		if(file_exists($this->getDataFolder()."designs.json")){
			$this->designs = json_decode(file_get_contents($this->getDataFolder()."designs.json"), true) ?? [];
		}

		foreach($this->designs as $name => $design){
			$this->designs[$name] = new Skin($design["skinId"], base64_decode($design["skinData"]), base64_decode($design["capeData"]), $design["geometryName"], $design["geometryData"]);
		}

		$this->getServer()->getLogger()->debug($this->prefix."Resources now back to life !");
	}

	public function onEnable()
	{
		$this->getServer()->getLogger()->debug($this->prefix."Registering vehicles with the DVLA :)");

		$this->vehicleFactory->registerDefaultVehicles();

		$this->getServer()->getLogger()->debug($this->prefix."That's all done now, remember no speeding ! <chuckles>");
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
		foreach($this->designs as $designName => $class){
			if(strtolower($name) === strtolower($designName)) return $class;
		}
		return null;
	}

	public static function getInstance() : self{
		return self::$instance;
	}
}
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

use pocketmine\utils\Config;
use pocketmine\command\Command;
use pocketmine\plugin\PluginBase;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat as C;

use JaxkDev\Vehicles\Vehicle\Vehicle;
use JaxkDev\Vehicles\Object\ObjectFactory;
use JaxkDev\Vehicles\Vehicle\VehicleFactory;

class Main extends PluginBase
{

	private static $instance;

	/** @var String|Vehicle[] */
	public static $inVehicle = [];

	public $prefix = C::GRAY."[".C::AQUA."Vehicles".C::GRAY."] ".C::GOLD."> ".C::RESET;

	/** @var CommandHandler */
	private $commandHandler;

	/** @var EventHandler */
	private $eventHandler;

	/** @var VehicleFactory */
	public $vehicleFactory;

	/** @var ObjectFactory */
	public $objectFactory;

	/** @var DesignFactory */
	public $designFactory;

	/** @var String|String[]|String[] */
	public $interactCommands = [];

	/** @var Config */
	private $cfgObject;

	/** @var array */
	public $cfg;

	public function onLoad()
	{
		self::$instance = $this;
		$this->getLogger()->debug("Loading all resources...");

		//Save defaults here.
		$this->saveConfig();
		$this->saveResource("Objects/README.md");
		$this->saveResource("Vehicles/README.md");

		//Add handlers and others here.
		$this->commandHandler = new CommandHandler($this);
		$this->vehicleFactory = new VehicleFactory($this);
		$this->objectFactory = new ObjectFactory($this);
		$this->designFactory = new DesignFactory($this);
		$this->eventHandler = new EventHandler($this);

		//Load any that need to be loaded.
		$this->designFactory->loadAll();

		$this->cfgObject = $this->getConfig();
		$this->cfg = $this->cfgObject->getAll();
		$this->getLogger()->debug("Loaded Config file, Version: {$this->cfg["version"]}");

		$this->getLogger()->debug("Resources now loaded !");
	}

	public function onEnable()
	{
		$this->getLogger()->debug("Registering default objects...");
		$this->objectFactory->registerDefaultObjects();
		$this->getLogger()->debug("Registering external objects...");
		$this->objectFactory->registerExternalObjects();
		$this->getLogger()->debug("That's all done now.");

		$this->getLogger()->debug("Registering default vehicles...");
		$this->vehicleFactory->registerDefaultVehicles();
		$this->getLogger()->debug("Registering external vehicles...");
		$this->vehicleFactory->registerExternalVehicles();
		$this->getLogger()->debug("That's all done now.");

		$this->getServer()->getPluginManager()->registerEvents($this->eventHandler, $this);
	}

	public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool
	{
		$this->commandHandler->handleCommand($sender, $args);
		return true;
	}

	public function saveCfg() : void
	{
		$this->cfgObject->setAll($this->cfg);
		$this->cfgObject->save();
	}

	public static function getInstance() : self{
		return self::$instance;
	}
}
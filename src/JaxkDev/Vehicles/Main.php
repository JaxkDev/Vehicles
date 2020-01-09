<?php
/*
 * Vehicles, PocketMine-MP Plugin.
 *
 * Licensed under the Open Software License version 3.0 (OSL-3.0)
 * Copyright (C) 2019-2020 JaxkDev
 *
 * Twitter :: @JaxkDev
 * Discord :: JaxkDev#8860
 * Email   :: JaxkDev@gmail.com
 */

declare(strict_types=1);

namespace JaxkDev\Vehicles;

use JaxkDev\Vehicles\Exceptions\DesignException;
use pocketmine\command\Command;
use pocketmine\plugin\PluginBase;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat as C;

use JaxkDev\Vehicles\Factory;

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

	/** @var Factory */
	public $factory;

	/** @var String|String[] */
	public $interactCommands = [];

	public function onLoad()
	{
		self::$instance = $this;
		$this->getLogger()->debug("Loading all resources...");

		//Save defaults here.
		$this->saveConfig();

		//Add handlers and others here.
		$this->commandHandler = new CommandHandler($this);
		$this->factory = new Factory($this);
		$this->eventHandler = new EventHandler($this);

		//Load any that need to be loaded.
		try{
			$this->factory->loadDesigns();
		} catch (DesignException $e){
			$this->getLogger()->critical($e->getMessage());
			$this->getServer()->getPluginManager()->disablePlugin($this);
		}

		$this->getLogger()->debug("Loaded all resources !");
	}

	public function onEnable()
	{
		$this->getLogger()->debug("Registering default vehicles...");
		$this->factory->registerVehicles();
		
		/*$this->getLogger()->debug("Registering external vehicles...");
		 **Rewrite** */

		$this->getServer()->getPluginManager()->registerEvents($this->eventHandler, $this);
	}

	public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool
	{
		$this->commandHandler->handleCommand($sender, $args);
		return true;
	}

	public static function getInstance() : self{
		return self::$instance;
	}
}

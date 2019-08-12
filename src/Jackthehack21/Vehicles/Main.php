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

use pocketmine\Player;
use pocketmine\event\Listener;
use pocketmine\command\Command;
use pocketmine\plugin\PluginBase;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat as C;
use Jackthehack21\Vehicles\Vehicle\Vehicle; //weirdest namespace ive ever used (3x vehicles *lmao*).
use Jackthehack21\Vehicles\Object\DisplayObject;
use Jackthehack21\Vehicles\Object\ObjectFactory;
use pocketmine\event\entity\EntityDamageByEntityEvent;

use Jackthehack21\Vehicles\Vehicle\VehicleFactory;

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

	/** @var DesignFactory */
	public $designFactory;

	/** @var String|String[]|String[] */
	public $interactCommands = [];

	public function onLoad()
	{
		self::$instance = $this;
		$this->getLogger()->debug("Loading all resources...");
		//resources here.
		//Parse data to load previous vehicles.
		//Prep all objects. (spawn onEnable)

		//Add handlers and others here.
		$this->commandHandler = new CommandHandler($this);
		$this->vehicleFactory = new VehicleFactory($this);
		$this->objectFactory = new ObjectFactory($this);
		$this->designFactory = new DesignFactory($this);

		$this->designFactory->loadAll();

		$this->getLogger()->debug("Resources now loaded !");
	}

	public function onInteract(EntityDamageByEntityEvent $event){
		if($event->getEntity() instanceof DisplayObject or $event->getEntity() instanceof Vehicle){
			$event->setCancelled(); //stops the ability to 'kill' a object/vehicle. (In long future, add vehicle condition *shrug*
			if(!($event->getDamager() instanceof Player)) return;
			/** @noinspection PhpUndefinedMethodInspection */
			if(($index = array_search(strtolower($event->getDamager()->getName()),array_keys($this->interactCommands))) !== false){
				$command = $this->interactCommands[array_keys($this->interactCommands)[$index]][0];
				$args = $this->interactCommands[array_keys($this->interactCommands)[$index]][1];
				$attacker = $event->getDamager();
				switch($command){
					case 'remove':
						$event->getEntity()->close();
						/** @noinspection PhpUndefinedMethodInspection */
						$attacker->sendMessage($this->prefix."'".$event->getEntity()->getName()."' has been removed.");
						/** @noinspection PhpUndefinedMethodInspection */
						unset($this->interactCommands[strtolower($attacker->getName())]);
						break;
					default:
						$this->getLogger()->warning("Unknown interact command '{$command}'");
				}
			}
		}
	}

	public function onEnable()
	{
		$this->getLogger()->debug("Registering objects...");
		$this->objectFactory->registerDefaultObjects();
		$this->getLogger()->debug("That's all done now.");

		$this->getLogger()->debug("Registering vehicles...");
		$this->vehicleFactory->registerDefaultVehicles();
		$this->getLogger()->debug("That's all done now.");

		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool
	{
		$this->commandHandler->handleCommand($sender, $command, $args);
		return true;
	}

	public static function getInstance() : self{
		return self::$instance;
	}
}
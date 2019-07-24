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
	public $prefix = C::GRAY."[".C::AQUA."Vehicles".C::GRAY."] ".C::GOLD."> ".C::RESET;

	/** @var CommandHandler */
	private $commandHandler;

	/** @var VehicleFactory */
	public $vehicleFactory;

	public function onLoad()
	{
		$this->getServer()->getLogger()->debug($this->prefix."Bringing back resources and any previous vehicles back from the dead...");
		//resources here.
		//Parse data to load previous vehicles.
		//Prep all objects. (spawn onEnable)

		//Add handlers and others here.
		$this->commandHandler = new CommandHandler($this);
		$this->vehicleFactory = new VehicleFactory($this);
	}

	public function onEnable()
	{
		$this->getServer()->getLogger()->debug($this->prefix."Registering vehicles with the DVLA :)");

		$this->vehicleFactory->loadTypes();
		$this->vehicleFactory->registerVehicles();

		$this->getServer()->getLogger()->debug($this->prefix."That's all done now, remember no speeding ! <chuckles>");
	}

	public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool
	{
		$this->commandHandler->handleCommand($sender, $command, $args);
		return true;
	}

	/**
	 * Statically retrieve the skin/design for a vehicle.
	 * @param string $name
	 * @return Skin|null
	 */
	static function getSkin(string $name): ?Skin{}
}
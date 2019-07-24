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

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\utils\TextFormat as C;

class CommandHandler
{
	/** @var Main */
	private $plugin;

	public function __construct(Main $plugin)
	{
		$this->plugin = $plugin;
	}

	/**
	 * @internal Used directly from pmmp, no other plugins should be passing commands here (if really needed, dispatch command from server).
	 *
	 * @param CommandSender $sender
	 * @param Command $command
	 * @param array $args
	 */
	function handleCommand(CommandSender $sender, Command $command, array $args): void{
		if($sender instanceof ConsoleCommandSender){
			$sender->sendMessage($this->plugin->prefix.C::RED."Commands for Vehicles cannot be run from console.");
			return;
		}
		if(!$sender->hasPermission("vehicles.command.use")){
			$sender->sendMessage($this->plugin->prefix.C::RED."You do not have permission to use vehicle commands.");
			return;
		}
		if(strtolower($command->getName()) !== "vehicles"){
			return; //Does pmmp do this for us, if only registering one command ?
		}
		if(count($args) == 0){
			$sender->sendMessage($this->plugin->prefix.C::RED."Usage: /vehicles help");
			return;
		}
		$subCommand = $args[0];
		array_shift($args);
		switch($subCommand){
			case 'help':
				$sender->sendMessage($this->plugin->prefix.C::RED."Help coming soon.");
				break;
			case 'spawn':
			case 'create':
			case 'new':
				if(count($args) === 0){
					$sender->sendMessage($this->plugin->prefix.C::RED."Usage: /vehicles spawn (Type)");
					$sender->sendMessage($this->plugin->prefix.C::AQUA."Types: ".join(", ", $this->plugin->vehicleFactory->getTypes()));
					return;
				}

				if(!$this->plugin->vehicleFactory->spawnVehicle($args[0], $sender->getLevel(), $sender->asVector3())){
					$sender->sendMessage($this->plugin->prefix.C::RED."The type \"".$args[0]."\" does not exist.");
					return;
				};
		}
	}
}
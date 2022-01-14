<?php
/*
 * Vehicles, PocketMine-MP Plugin.
 *
 * Licensed under the Open Software License version 3.0 (OSL-3.0)
 * Copyright (C) 2019-present JaxkDev
 *
 * Twitter :: @JaxkDev
 * Discord :: JaxkDev#2698
 * Email   :: JaxkDev@gmail.com
 */

declare(strict_types=1);

namespace JaxkDev\Vehicles\Handlers;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat as C;
use pocketmine\command\CommandSender;
use pocketmine\plugin\PluginException;
use JaxkDev\Vehicles\Main;

class CommandHandler
{
	/** @var Main */
	private $plugin;
	
	/** @var string */
	private $prefix;

	public function __construct(Main $plugin)
	{
		$this->plugin = $plugin;
		$this->prefix = Main::$prefix;
	}
	
	//TODO part of rewrite for handling nbt+id's

	/**
	 * @internal 
	 * Used directly from pmmp, no other plugins should be passing commands here (if really needed, dispatch command from server).
	 *
	 * @param CommandSender $sender
	 * @param string[] $args
	 */
	function handleCommand(CommandSender $sender, array $args): void{
		if(!$sender instanceof Player){
			$sender->sendMessage($this->prefix.C::RED."Commands for Vehicles cannot be run from console.");
			return;
		}
		$sender = $this->plugin->getServer()->getPlayerExact($sender->getName());
		if($sender === null) throw new PluginException("So this happened... (Unknown player using vehicle commands !)");
		if(count($args) == 0){
			$sender->sendMessage($this->prefix.C::RED."Usage: /vehicles help");
			return;
		}
		$subCommand = $args[0];
		array_shift($args);
		switch($subCommand){
			case 'help':
				$sender->sendMessage($this->prefix.C::RED."-- HELP --");
				$sender->sendMessage($this->prefix.C::GOLD."/vehicles help");
				$sender->sendMessage($this->prefix.C::GOLD."/vehicles credits");
				$sender->sendMessage($this->prefix.C::GOLD."/vehicles spawn [type]");
				$sender->sendMessage($this->prefix.C::GOLD."/vehicles types/list");
				$sender->sendMessage($this->prefix.C::GOLD."/vehicles remove");
				break;
			case 'credits':
			case 'creds':
				$sender->sendMessage($this->prefix.C::GOLD."--- Credits ---");
				$sender->sendMessage($this->prefix.C::GREEN."Developer: ".C::RED."JaxkDev\n".$this->prefix.C::GREEN."Testers: ".C::RED."Kevin (@kevinishawesome), 'Simule City' beta players.");
				break;
			case 'list':
			case 'types':
			case 'type':
				$sender->sendMessage($this->prefix.C::RED."To spawn: /vehicles spawn <type>");
				$sender->sendMessage($this->prefix.C::AQUA."Vehicles's Available:\n- ".join("\n- ", array_keys($this->plugin->factory->getAllVehicleData())));
				break;
			case 'spawn':
			case 'create':
			case 'new':
				if(!$sender->hasPermission("vehicles.command.spawn")){
					$sender->sendMessage($this->prefix.C::RED."You do not have permission to use that command.");
					return;
				}
				if(count($args) === 0){
					$sender->sendMessage($this->prefix.C::RED."Usage: /vehicles spawn (Type)");
					$sender->sendMessage($this->prefix.C::AQUA."Vehicle's Available:\n- ".join("\n- ", array_keys($this->plugin->factory->getAllVehicleData())));
					return;
				}
				if($this->plugin->factory->getVehicleData($args[0]) !== null){
                    $this->plugin->factory->spawnVehicle($this->plugin->factory->getVehicleData($args[0]), $sender->getLocation());
				}
				else{
					$sender->sendMessage($this->prefix.C::RED."\"".$args[0]."\" does not exist.");
					return;
				}
				$sender->sendMessage($this->prefix.C::GOLD."\"".$args[0]."\" Created.");
				break;
			case 'del':
			case 'rem':
			case 'delete':
			case 'remove':
				if(!$sender->hasPermission("vehicles.command.remove")){
					$sender->sendMessage($this->prefix.C::RED."You do not have permission to use that command.");
					return;
				}
				$this->plugin->interactCommands[strtolower($sender->getName())] = ["remove", [$args]];
				$sender->sendMessage($this->prefix.C::GREEN."Tap the vehicle you wish to remove.");
				break;
			default:
				$sender->sendMessage($this->prefix.C::RED."Unknown command, please check ".C::GREEN."/vehicles help".C::RED." For all available commands.");
		}
	}
}

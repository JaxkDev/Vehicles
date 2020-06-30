<?php
/*
 * Vehicles, PocketMine-MP Plugin.
 *
 * Licensed under the Open Software License version 3.0 (OSL-3.0)
 * Copyright (C) 2019-2020 JaxkDev
 *
 * Twitter :: @JaxkDev
 * Discord :: JaxkDev#0001
 * Email   :: JaxkDev@gmail.com
 */

declare(strict_types=1);

namespace JaxkDev\Vehicles\Handlers;

use JaxkDev\Vehicles\Vehicle;
use pocketmine\player\Player;
use pocketmine\event\Listener;
use pocketmine\utils\TextFormat as C;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\InteractPacket;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\network\mcpe\protocol\PlayerInputPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemOnEntityTransactionData;

use JaxkDev\Vehicles\Main;

class EventHandler implements Listener
{
	/** @var Main */
	public $plugin;

	public function __construct(Main $plugin)
	{
		$this->plugin = $plugin;
	}

	public function onPlayerLeaveEvent(PlayerQuitEvent $event): void{
		$player = $event->getPlayer();
		if(isset(Main::$inVehicle[$player->getUniqueId()->toString()])){
			Main::$inVehicle[$player->getUniqueId()->toString()]->removePlayer($player);
			$this->plugin->getLogger()->debug($player->getName()." Has left the server while in a vehicle, they have been kicked from the vehicle.");
		}
	}

	public function onPlayerDeathEvent(PlayerDeathEvent $event): void{
		$player = $event->getPlayer();
		if(isset(Main::$inVehicle[$player->getUniqueId()->toString()])){
			Main::$inVehicle[$player->getUniqueId()->toString()]->removePlayer($player);
			$player->sendMessage(C::RED."You were killed so you have been kicked from your vehicle.");
			$this->plugin->getLogger()->debug($player->getName()." Has died while in a vehicle, they have been kicked from the vehicle.");
		}
	}

	public function onPlayerTeleportEvent(EntityTeleportEvent $event): void{
		if($event->getEntity() instanceof Player){
			/** @var Player $player */
			$player = $event->getEntity();
			if(isset(Main::$inVehicle[$player->getUniqueId()->toString()])){
				Main::$inVehicle[$player->getUniqueId()->toString()]->removePlayer($player);
				$player->sendMessage(C::RED."You cannot teleport with a vehicle, you have been kicked from your vehicle.");
				$this->plugin->getLogger()->debug($player->getName()." Has teleported while in a vehicle, they have been kicked from their vehicle.");
			}
		}
	}

	/**
	 * @param EntityDamageByEntityEvent $event
	 * @priority Lowest
	 * Some interruption by MultiWorld
	 */
	public function onEntityDamageEvent(EntityDamageByEntityEvent $event): void{
		if($event->getEntity() instanceof Vehicle){
			$event->setCancelled(); //stops the ability to 'kill' a object/vehicle. (In long future, add vehicle condition *shrug*
			if(!($event->getDamager() instanceof Player)) return;
			/** @var Player $attacker */
			$attacker = $event->getDamager();
			/** @var Vehicle $entity */
			$entity = $event->getEntity();
			if(($index = array_search(strtolower($attacker->getName()),array_keys($this->plugin->interactCommands))) !== false){
				$command = $this->plugin->interactCommands[array_keys($this->plugin->interactCommands)[$index]][0];
				/** @noinspection PhpUnusedLocalVariableInspection */
				$args = $this->plugin->interactCommands[array_keys($this->plugin->interactCommands)[$index]][1];
				switch($command){
					case 'remove':
						if($entity instanceof Vehicle){
							if(!$entity->isVehicleEmpty()) {
								$attacker->sendMessage(Main::$prefix.C::RED."You cannot remove a vehicle with players in it.");
							}
							else {
								$entity->close();
								$attacker->sendMessage(Main::$prefix . "'" . $entity->getVehicleName() . "' has been removed.");
							}
						}
						unset($this->plugin->interactCommands[strtolower($attacker->getName())]);
						break;
					default:
						$this->plugin->getLogger()->error("Unknown interact command '{$command}'");
				}
			} else {
				if($entity instanceof Vehicle) {
					if (!$attacker->hasPermission("vehicles.drive")) {
						$attacker->sendMessage(C::RED . "You do not have permission to drive vehicles.");
						return;
					}
					if($entity->getDriver() === null) $entity->setDriver($attacker);
					else{
						if(!$entity->addPassenger($attacker)){
							$attacker->sendMessage(C::RED."This vehicle is full.");
						}
					}
				}
			}
		}
	}

	/**
	 * Handle a players motion when driving.
	 * @param DataPacketReceiveEvent $event
	 */
	public function onPlayerInputPacket($event): void{
		/** @var PlayerInputPacket $packet */
		$packet = $event->getPacket();
		$player = $event->getOrigin();

		if(isset(Main::$inVehicle[$player->getPlayer()->getUniqueId()->toString()])){
			$event->setCancelled();
			if($packet->motionX === 0.0 and $packet->motionY === 0.0) {
				return;
			} //MCPE Likes to send a lot of useless packets, this cuts down the ones we handle.
			/** @var Vehicle $vehicle */
			$vehicle = Main::$inVehicle[$player->getPlayer()->getUniqueId()->toString()];
			if($vehicle->getDriver() === null) return;
			if($vehicle->getDriver()->getUniqueId()->equals($player->getPlayer()->getUniqueId())) $vehicle->updateMotion($packet->motionX, $packet->motionY);
		}
	}

	/**
	 * Handle a players interact.
	 * @param DataPacketReceiveEvent $event
	 */
	public function onInteractPacket($event): void{
		/** @var InteractPacket $packet */
		$packet = $event->getPacket();

		if($packet->action === InteractPacket::ACTION_LEAVE_VEHICLE){
			$player = $event->getOrigin()->getPlayer();
			$vehicle = $player->getWorld()->getEntity($packet->target);
			if($vehicle instanceof Vehicle) {
				$vehicle->removePlayer($player);
				$event->setCancelled();
			}
		}
	}

	/**
	 * Handle InventoryTransaction.
	 * @param DataPacketReceiveEvent $event
	 */
	public function onInventoryTransactionPacket($event): void{
		/** @var InventoryTransactionPacket $packet */
		$packet = $event->getPacket();

		if($packet->trData instanceof UseItemOnEntityTransactionData){
			$player = $event->getOrigin()->getPlayer();
			$vehicle = $player->getWorld()->getEntity($packet->trData->getEntityRuntimeId());
			if($vehicle instanceof Vehicle){
				if($packet->trData->getActionType() === UseItemOnEntityTransactionData::ACTION_INTERACT) {
					if($vehicle->getDriver() !== null) $vehicle->addPassenger($player);
					else $vehicle->setDriver($player);
					$event->setCancelled();
				}
			}
		}
	}

	/**
	 * @param DataPacketReceiveEvent $event
	 */
	public function onDataPacketEvent(DataPacketReceiveEvent $event): void{
		$packet = $event->getPacket();
		$pid = $packet->pid();
		switch($pid){
			case InteractPacket::NETWORK_ID:
				$this->onInteractPacket($event);
				break;
			case InventoryTransactionPacket::NETWORK_ID:
				$this->onInventoryTransactionPacket($event);
				break;
			case PlayerInputPacket::NETWORK_ID:
				$this->onPlayerInputPacket($event);
				break;
		}
	}
}

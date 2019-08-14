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
use pocketmine\utils\TextFormat as C;
use Jackthehack21\Vehicles\Vehicle\Vehicle; //Only 3 'vehicle's in one namespace *HAHA*
use Jackthehack21\Vehicles\Object\DisplayObject;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\InteractPacket;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\network\mcpe\protocol\PlayerInputPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;

class EventHandler implements Listener
{
	/** @var Main */
	public $plugin;


	public function __construct(Main $plugin)
	{
		$this->plugin = $plugin;
	}

	public function onEntityDamageEvent(EntityDamageByEntityEvent $event){
		if($event->getEntity() instanceof DisplayObject or $event->getEntity() instanceof Vehicle){
			$event->setCancelled(); //stops the ability to 'kill' a object/vehicle. (In long future, add vehicle condition *shrug*
			if(!($event->getDamager() instanceof Player)) return;
			/** @var Player $attacker */
			$attacker = $event->getDamager();
			/** @var Vehicle|DisplayObject $entity */
			$entity = $event->getEntity();
			if(($index = array_search(strtolower($attacker->getName()),array_keys($this->plugin->interactCommands))) !== false){
				$command = $this->plugin->interactCommands[array_keys($this->plugin->interactCommands)[$index]][0];
				$args = $this->plugin->interactCommands[array_keys($this->plugin->interactCommands)[$index]][1];
				switch($command){
					case 'remove':
						$event->getEntity()->close();
						$attacker->sendMessage($this->plugin->prefix."'".$entity->getName()."' has been removed.");
						unset($this->plugin->interactCommands[strtolower($attacker->getName())]);
						break;
					default:
						$this->plugin->getLogger()->warning("Unknown interact command '{$command}'");
				}
			} else {
				if(!$attacker->hasPermission("vehicles.drive")){
					$attacker->sendMessage(C::RED."You do not have permission to drive vehicles.");
					return;
				}
				$entity->setDriver($attacker);
			}
		}
	}

	/**
	 * Handle a players motion when driving.
	 * @param DataPacketReceiveEvent $event
	 */
	public function onPlayerInputPacket($event){
		/** @var PlayerInputPacket $packet */
		$packet = $event->getPacket();
		$player = $event->getPlayer();

		if(isset(Main::$driving[$player->getRawUniqueId()])){
			$event->setCancelled();
			if($packet->motionX === 0.0 and $packet->motionY === 0.0) {
				return;
			} //MCPE Likes to send a lot of useless packets, this cuts down the ones we handle.
			/** @var Vehicle $vehicle */
			$vehicle = Main::$driving[$player->getRawUniqueId()];
			$vehicle->updateMotion($packet->motionX, $packet->motionY);
		}
	}

	/**
	 * Handle a players interact.
	 * @param DataPacketReceiveEvent $event
	 */
	public function onInteractPacket($event){
		/** @var InteractPacket $packet */
		$packet = $event->getPacket();

		if($packet->action === InteractPacket::ACTION_LEAVE_VEHICLE){
			$player = $event->getPlayer();
			$vehicle = $player->getLevel()->getEntity($packet->target);
			if($vehicle instanceof Vehicle) {
				if ($vehicle->getDriver()->getUniqueId() === $event->getPlayer()->getUniqueId()) {
					$vehicle->removeDriver();
					$event->setCancelled();
				} else {
					$this->plugin->getLogger()->warning("Unknown player tried to leave a vehicle '{$vehicle->getName()}' but is not its driver...");
				}
			}
		}
	}

	/**
	 * Handle InventoryTransaction.
	 * @param DataPacketReceiveEvent $event
	 */
	public function onInventoryTransactionPacket($event){
		/** @var InventoryTransactionPacket $packet */
		$packet = $event->getPacket();

		if($packet->transactionType === InventoryTransactionPacket::TYPE_USE_ITEM_ON_ENTITY){
			$player = $event->getPlayer();
			$vehicle = $player->getLevel()->getEntity($packet->trData->entityRuntimeId);
			if($vehicle instanceof Vehicle){
				if($packet->trData->actionType === InventoryTransactionPacket::USE_ITEM_ON_ENTITY_ACTION_INTERACT) {
					$vehicle->setDriver($player);
					$event->setCancelled();
				}
			}
		}
	}

	/**
	 * @param DataPacketReceiveEvent $event
	 */
	public function onDataPacketEvent(DataPacketReceiveEvent $event){
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
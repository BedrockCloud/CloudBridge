<?php

namespace bedrockcloud\cloudbridge\listener;

use bedrockcloud\cloudbridge\api\CloudAPI;
use bedrockcloud\cloudbridge\api\object\player\CloudPlayer;
use bedrockcloud\cloudbridge\api\object\server\CloudServer;
use bedrockcloud\cloudbridge\api\provider\PlayerProvider;
use bedrockcloud\cloudbridge\CloudBridge;
use bedrockcloud\cloudbridge\language\Language;
use bedrockcloud\cloudbridge\network\Network;
use bedrockcloud\cloudbridge\network\packet\impl\normal\PlayerConnectPacket;
use bedrockcloud\cloudbridge\network\packet\impl\normal\PlayerDisconnectPacket;
use bedrockcloud\cloudbridge\network\packet\impl\request\CheckPlayerExistsRequestPacket;
use bedrockcloud\cloudbridge\network\packet\impl\request\CheckPlayerMaintenanceRequestPacket;
use bedrockcloud\cloudbridge\network\packet\impl\request\CheckPlayerNotifyRequestPacket;
use bedrockcloud\cloudbridge\network\packet\impl\response\CheckPlayerExistsResponsePacket;
use bedrockcloud\cloudbridge\network\packet\impl\response\CheckPlayerMaintenanceResponsePacket;
use bedrockcloud\cloudbridge\network\packet\impl\response\CheckPlayerNotifyResponsePacket;
use bedrockcloud\cloudbridge\network\request\RequestManager;
use bedrockcloud\cloudbridge\util\NotifyList;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;

class EventListener implements Listener {

    public function onLogin(PlayerLoginEvent $event): void
    {
        $player = $event->getPlayer();
        Network::getInstance()->sendPacket(new PlayerConnectPacket(CloudPlayer::fromPlayer($player)));

        RequestManager::getInstance()->sendRequest(new CheckPlayerExistsRequestPacket($player->getName()))->then(function (CheckPlayerExistsResponsePacket $packet) use ($player): void {
            if (!$packet->getValue()) {
                $player->kick("§cPlease join through the proxy.");
            } else {
                if (CloudAPI::templateProvider()->current()->isMaintenance()) {
                    RequestManager::getInstance()->sendRequest(new CheckPlayerMaintenanceRequestPacket($player->getName()))->then(function (CheckPlayerMaintenanceResponsePacket $packet) use ($player): void {
                        if (!$packet->getValue() && !$player->hasPermission("bedrockcloud.maintenance.bypass")) {
                            $player->kick(Language::current()->translate("inGame.template.kick.maintenance"));
                        }
                    });
                }

                RequestManager::getInstance()->sendRequest(new CheckPlayerNotifyRequestPacket($player->getName()))->then(function (CheckPlayerNotifyResponsePacket $packet) use ($player): void {
                    if ($packet->getValue()) NotifyList::put($player);
                });
            }
        });
    }

    public function onQuit(PlayerQuitEvent $event): void {
        $player = $event->getPlayer();
        Network::getInstance()->sendPacket(new PlayerDisconnectPacket($player->getName()));
    }
}
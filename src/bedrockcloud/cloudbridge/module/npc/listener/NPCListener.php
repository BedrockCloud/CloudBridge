<?php

namespace bedrockcloud\cloudbridge\module\npc\listener;

use dktapps\pmforms\MenuForm;
use dktapps\pmforms\MenuOption;
use bedrockcloud\cloudbridge\api\CloudAPI;
use bedrockcloud\cloudbridge\api\object\server\CloudServer;
use bedrockcloud\cloudbridge\api\object\server\status\ServerStatus;
use bedrockcloud\cloudbridge\CloudBridge;
use bedrockcloud\cloudbridge\language\Language;
use bedrockcloud\cloudbridge\module\npc\CloudNPC;
use bedrockcloud\cloudbridge\module\npc\CloudNPCModule;
use bedrockcloud\cloudbridge\util\GeneralSettings;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\MoveActorAbsolutePacket;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemOnEntityTransactionData;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\world\Position;

class NPCListener implements Listener {

    public function onMove(PlayerMoveEvent $event): void {
        $player = $event->getPlayer();

        foreach (array_filter(CloudNPCModule::get()->getCloudNPCs(), fn(CloudNPC $npc) => $npc->isHeadRotation()) as $cloudNPC) {
            if (($entity = $cloudNPC->getEntity()) !== null) {
                if ($entity->getPosition()->distance($player->getPosition()) <= 9) {
                    $horizontal = sqrt(($player->getPosition()->x - $entity->getPosition()->x) ** 2 + ($player->getPosition()->z - $entity->getLocation()->z) ** 2);
                    $vertical = $player->getPosition()->y - $entity->getLocation()->getY();
                    $pitch = -atan2($vertical, $horizontal) / M_PI * 180;

                    $xDist = $player->getPosition()->x - $entity->getLocation()->x;
                    $zDist = $player->getPosition()->z - $entity->getLocation()->z;

                    $yaw = atan2($zDist, $xDist) / M_PI * 180 - 90;
                    if ($yaw < 0) $yaw += 360.0;

                    $player->getNetworkSession()->sendDataPacket(MoveActorAbsolutePacket::create($entity->getId(), Position::fromObject($entity->getOffsetPosition($entity->getPosition()), $entity->getWorld()), $pitch, $yaw, $yaw, 0));
                }
            }
        }
    }

    public function onHit(EntityDamageByEntityEvent $event): void {
        $entity = $event->getEntity();
        $damager = $event->getDamager();

        if (($cloudNPC = CloudNPCModule::get()->getCloudNPC($entity->getPosition())) !== null) {
            $event->cancel();
            if ($damager instanceof Player) {
                if (isset(CloudNPCModule::get()->npcDetection[$damager->getName()])) {
                    unset(CloudNPCModule::get()->npcDetection[$damager->getName()]);
                    if (CloudNPCModule::get()->removeCloudNPC($cloudNPC)) {
                        $damager->sendMessage(Language::current()->translate("inGame.cloudnpc.removed"));
                    } else $damager->sendMessage(CloudBridge::getPrefix() . "§cAn error occurred while removing the npc. Please report that incident on our discord.");
                } else {
                    $servers = array_filter(
                        $cloudNPC->getServers(),
                        fn(CloudServer $server) => $server->getName() !== GeneralSettings::getServerName() &&
                            $server->getServerStatus() === ServerStatus::ONLINE() &&
                            !($server->getTemplate()->isMaintenance() && !$damager->hasPermission("bedrockcloud.maintenance.bypass"))
                    );

                    $name = $cloudNPC->hasTemplateGroup() ? $cloudNPC->getTemplate()->getDisplayName() : $cloudNPC->getTemplate()->getName();
                    $damager->sendForm(new MenuForm(
                        Language::current()->translate("inGame.ui.cloudnpc.choose_server.title", $name),
                        Language::current()->translate("inGame.ui.cloudnpc.choose_server.text", count($servers), $name),
                        (count($servers) == 0 ? [new MenuOption(Language::current()->translate("inGame.ui.cloudnpc.choose_server.no.server"))] : array_map(fn(CloudServer $server) => new MenuOption(Language::current()->translate("inGame.ui.cloudnpc.choose_server.button.server", $server->getName(), count($server->getCloudPlayers()), $server->getCloudServerData()->getMaxPlayers())), $servers)),
                        function(Player $player, int $data) use($servers): void {
                            /** @var CloudServer $server */
                            if (($server = ($servers[$data] ?? null)) instanceof CloudServer) {
                                $player->sendMessage(Language::current()->translate("inGame.server.connect", $server->getName()));
                                if (!CloudAPI::playerProvider()->transferPlayer($player, $server)) {
                                    $player->sendMessage(Language::current()->translate("inGame.server.connect.failed", $server->getName()));
                                }
                            }
                        }
                    ));
                }
            }
        }
    }
    
    public function onJoin(PlayerJoinEvent $event): void {
        CloudNPCModule::get()->spawnAll();
    }

    public function onInteractWithEntity(DataPacketReceiveEvent $event): void {
        $packet = $event->getPacket();
        $origin = $event->getOrigin();
        $player = $origin->getPlayer();

        if ($player instanceof Player) {
            if ($packet instanceof InventoryTransactionPacket) {
                /** @var UseItemOnEntityTransactionData $trData */
                if (($trData = $packet->trData) instanceof UseItemOnEntityTransactionData) {
                    if ($trData->getActionType() !== $trData::ACTION_ATTACK) {
                        if (($entity = $player->getWorld()->getEntity($trData->getActorRuntimeId())) !== null) {
                            if (($cloudNPC = CloudNPCModule::get()->getCloudNPC($entity->getPosition())) !== null) {
                                if (!isset(CloudNPCModule::get()->npcDelay[$player->getName()])) CloudNPCModule::get()->npcDelay[$player->getName()] = 0;
                                if (Server::getInstance()->getTick() >= CloudNPCModule::get()->npcDelay[$player->getName()]) {
                                    CloudNPCModule::get()->npcDelay[$player->getName()] = Server::getInstance()->getTick() + 10;
                                    if ($cloudNPC->hasTemplateGroup()) {
                                        $player->sendForm(new MenuForm(
                                            Language::current()->translate("inGame.ui.cloudnpc.choose_template.title", $cloudNPC->getTemplate()->getDisplayName()),
                                            Language::current()->translate("inGame.ui.cloudnpc.choose_template.text", $cloudNPC->getTemplate()->getDisplayName()),
                                            array_map(
                                                fn(string $template) => new MenuOption(Language::current()->translate(
                                                    "inGame.ui.cloudnpc.choose_template.button.template",
                                                    $template,
                                                    count(CloudAPI::playerProvider()->getPlayersOfTemplate($template = CloudAPI::templateProvider()->getTemplate($template))),
                                                    $template->getMaxPlayerCount()
                                                )),
                                                $templates = $cloudNPC->getTemplate()->getTemplates()
                                            ),
                                            function (Player $player, int $data) use($templates): void {
                                                $template = $templates[$data];
                                                if (($template = CloudAPI::templateProvider()->getTemplate($template)) !== null) {
                                                    if (($bestServer = CloudAPI::serverProvider()->getFreeServerByTemplate($template, [GeneralSettings::getServerName()])) !== null) {
                                                        $player->sendMessage(Language::current()->translate("inGame.server.connect", $bestServer->getName()));
                                                        if (!CloudAPI::playerProvider()->transferPlayer($player, $bestServer)) {
                                                            $player->sendMessage(Language::current()->translate("inGame.server.connect.failed", $bestServer->getName()));
                                                        }
                                                    } else $player->sendMessage(Language::current()->translate("inGame.cloudnpc.quickjoin.no_server"));
                                                } else $player->sendMessage(Language::current()->translate("inGame.cloudnpc.quickjoin.no_server"));
                                            }
                                        ));
                                    } else {
                                        if (($bestServer = CloudAPI::serverProvider()->getFreeServerByTemplate($cloudNPC->getTemplate(), [GeneralSettings::getServerName()])) !== null) {
                                            $player->sendMessage(Language::current()->translate("inGame.server.connect", $bestServer->getName()));
                                            if (!CloudAPI::playerProvider()->transferPlayer($player, $bestServer)) {
                                                $player->sendMessage(Language::current()->translate("inGame.server.connect.failed", $bestServer->getName()));
                                            }
                                        } else $player->sendMessage(Language::current()->translate("inGame.cloudnpc.quickjoin.no_server"));
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}

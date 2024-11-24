<?php

namespace bedrockcloud\cloudbridge\module\sign\listener;

use bedrockcloud\cloudbridge\module\sign\CloudSign;
use bedrockcloud\cloudbridge\api\CloudAPI;
use bedrockcloud\cloudbridge\CloudBridge;
use bedrockcloud\cloudbridge\language\Language;
use bedrockcloud\cloudbridge\module\sign\CloudSignModule;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\SignChangeEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\Server;

class SignListener implements Listener {

    public function onChange(SignChangeEvent $event): void {
        if ($event->getNewText()->getLine(0) == "[Cloud]") {
            if ($event->getPlayer()->hasPermission("bedrockcloud.cloudsign.add")) {
                if (($template = CloudAPI::templateProvider()->getTemplate($event->getNewText()->getLine(1))) !== null) {
                    CloudSignModule::get()->addCloudSign(new CloudSign($template, $event->getSign()->getPosition()));
                }
            }
        }
    }

    public function onInteract(PlayerInteractEvent $event): void {
        if ($event->getAction() === $event::LEFT_CLICK_BLOCK) return;
        if (($sign = CloudSignModule::get()->getCloudSign($event->getBlock()->getPosition())) !== null) {
            if (!isset(CloudBridge::getInstance()->signDelay[$event->getPlayer()->getName()])) CloudBridge::getInstance()->signDelay[$event->getPlayer()->getName()] = 0;
            if (Server::getInstance()->getTick() >= CloudBridge::getInstance()->signDelay[$event->getPlayer()->getName()]) {
                CloudBridge::getInstance()->signDelay[$event->getPlayer()->getName()] = Server::getInstance()->getTick() + 10;
                if ($sign->hasUsingServer() && !$sign->getUsingServer()->getTemplate()->isMaintenance()) {
                    if (CloudAPI::serverProvider()->current()?->getName() == $sign->getUsingServer()->getName()) {
                        $event->getPlayer()->sendMessage(Language::current()->translate("inGame.server.already.connected", $sign->getUsingServer()->getName()));
                    } else {
                        $event->getPlayer()->sendMessage(Language::current()->translate("inGame.server.connect", $sign->getUsingServer()->getName()));
                        if (!CloudAPI::playerProvider()->transferPlayer($event->getPlayer(), $sign->getUsingServer())) {
                            $event->getPlayer()->sendMessage(Language::current()->translate("inGame.server.connect.failed", $sign->getUsingServer()->getName()));
                        }
                    }
                }
            }
        }
    }

    public function onBreak(BlockBreakEvent $event): void {
        if (($sign = CloudSignModule::get()->getCloudSign($event->getBlock()->getPosition())) !== null) {
            if ($event->getPlayer()->hasPermission("bedrockcloud.cloudsign.remove")) {
                CloudSignModule::get()->removeCloudSign($sign);
            } else $event->cancel();
        }
    }
}